<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Codigos de verificacion enviados al correo.
 * Copia de mipdv_web (21-10-2026), adaptada a dat@Resto el 11-11-2026.
 *
 * Hoy lo usa 'recuperar_clave' (recuperar la contrasena desde el login).
 * El correo sale con application/config/email.php (variables MIPDV_SMTP_* del entorno).
 *
 * Reglas (mismas que la Mesa de ayuda del login):
 *   - El codigo NUNCA se devuelve al navegador; en la sesion se guarda solo su hash.
 *   - Vence en 10 minutos y admite 5 intentos fallidos.
 *   - 60 s entre envios y como maximo 5 codigos cada 15 minutos por IP y por correo.
 *   - Un correo verificado vale 15 minutos para completar el flujo.
 */
class CodigoCorreo
{
  const VIGENCIA_MIN = 10;
  const INTENTOS_MAX = 5;
  const REENVIO_SEG = 60;
  const MAX_POR_VENTANA = 5;
  const VENTANA_SEG = 900;
  const VERIFICADO_SEG = 900;

  /** @var CI_Controller */
  private $CI;

  public function __construct()
  {
    $this->CI =& get_instance();
    $this->CI->load->library('session');
  }

  private function _clave($proposito)
  {
    return 'codigo_correo_' . preg_replace('/[^a-z_]/', '', (string)$proposito);
  }

  public function correoValido($correo)
  {
    $correo = (string)$correo;
    if ($correo === '' || strlen($correo) > 100 || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
      return false;
    }
    $dominio = substr(strrchr($correo, '@'), 1);
    if ($dominio === false || $dominio === '') {
      return false;
    }
    // Si el servidor no puede consultar DNS, no se bloquea.
    if (!function_exists('checkdnsrr')) {
      return true;
    }
    return checkdnsrr($dominio, 'MX') || checkdnsrr($dominio, 'A');
  }

  /**
   * @return array ok, msg, reenvio_seg
   */
  public function enviar($proposito, $correo, $titulo, $texto)
  {
    $correo = strtolower(trim((string)$correo));
    if (!$this->correoValido($correo)) {
      return array('ok' => false, 'msg' => 'Ingresa un correo válido (el dominio debe existir).', 'campo' => 'correo');
    }

    $ip = (string)$this->CI->input->ip_address();
    $porIp = $this->_registros('ip_' . $proposito . '_' . $ip);
    $porCorreo = $this->_registros('correo_' . $correo);
    $todos = array_merge($porIp, $porCorreo);

    if (!empty($todos) && (time() - max($todos)) < self::REENVIO_SEG) {
      $espera = self::REENVIO_SEG - (time() - max($todos));
      return array('ok' => false, 'msg' => 'Espera ' . $espera . ' segundos para solicitar otro código.', 'reenvio_seg' => $espera);
    }
    if (count($porIp) >= self::MAX_POR_VENTANA || count($porCorreo) >= self::MAX_POR_VENTANA) {
      return array('ok' => false, 'msg' => 'Ya se enviaron varios códigos. Intenta nuevamente en 15 minutos.');
    }

    if (!$this->_correoConfigurado()) {
      log_message('error', 'CodigoCorreo[' . $proposito . ']: falta configurar el correo de salida (MIPDV_SMTP_HOST / _USER / _PASS en el entorno).');
      return array('ok' => false, 'msg' => 'La recuperación de contraseña no está disponible por ahora. Escríbenos a Datamaule para ayudarte.');
    }

    $codigo = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    $enviado = false;
    try {
      $from = $this->_prepararCorreo();
      $this->CI->email->from($from, 'dat@Resto Datamaule');
      $this->CI->email->to($correo);
      $this->CI->email->subject($titulo . ': tu código es ' . $codigo);
      $this->CI->email->message($this->CI->load->view('login/email_codigo_verificacion', array(
        'codigo' => $codigo,
        'minutos' => self::VIGENCIA_MIN,
        'titulo' => $titulo,
        'texto' => $texto
      ), true));
      $enviado = (bool)$this->CI->email->send(false);
    } catch (Throwable $e) {
      log_message('error', 'CodigoCorreo[' . $proposito . ']: excepción al enviar: ' . $e->getMessage());
    }

    if (!$enviado) {
      log_message('error', 'CodigoCorreo[' . $proposito . ']: no se pudo enviar el código (servidor de correo).');
      return array('ok' => false, 'msg' => 'No pudimos enviar el código en este momento. Intenta nuevamente en unos minutos.');
    }

    $this->_registrar('ip_' . $proposito . '_' . $ip);
    $this->_registrar('correo_' . $correo);

    $this->CI->session->set_userdata($this->_clave($proposito), array(
      'correo' => $correo,
      'hash' => password_hash($codigo, PASSWORD_DEFAULT),
      'expira' => time() + (self::VIGENCIA_MIN * 60),
      'intentos' => 0,
      'verificado_en' => 0
    ));

    return array(
      'ok' => true,
      'msg' => 'Te enviamos un código de 6 dígitos a ' . $correo . '. Revisa también spam.',
      'reenvio_seg' => self::REENVIO_SEG
    );
  }

  /**
   * @return array ok, msg, expirado
   */
  public function verificar($proposito, $correo, $codigo)
  {
    $correo = strtolower(trim((string)$correo));
    $codigo = trim((string)$codigo);
    $estado = $this->CI->session->userdata($this->_clave($proposito));

    if (!is_array($estado) || empty($estado['hash']) || $estado['correo'] !== $correo) {
      return array('ok' => false, 'msg' => 'Primero solicita un código para este correo.', 'expirado' => true);
    }
    if (time() > (int)$estado['expira']) {
      $this->limpiar($proposito);
      return array('ok' => false, 'msg' => 'El código venció. Solicita uno nuevo.', 'expirado' => true);
    }
    if (!preg_match('/^\d{6}$/', $codigo) || !password_verify($codigo, $estado['hash'])) {
      $estado['intentos'] = (int)$estado['intentos'] + 1;
      if ($estado['intentos'] >= self::INTENTOS_MAX) {
        $this->limpiar($proposito);
        return array('ok' => false, 'msg' => 'Demasiados intentos. Solicita un código nuevo.', 'expirado' => true);
      }
      $this->CI->session->set_userdata($this->_clave($proposito), $estado);
      return array('ok' => false, 'msg' => 'El código no es correcto. Te quedan ' . (self::INTENTOS_MAX - $estado['intentos']) . ' intentos.', 'expirado' => false);
    }

    $estado['verificado_en'] = time();
    $estado['hash'] = '';   // un codigo se usa una sola vez
    $this->CI->session->set_userdata($this->_clave($proposito), $estado);
    return array('ok' => true, 'msg' => 'Correo verificado.', 'expirado' => false);
  }

  /** ¿Este correo se verifico con codigo en los ultimos 15 minutos? */
  public function estaVerificado($proposito, $correo)
  {
    $correo = strtolower(trim((string)$correo));
    $estado = $this->CI->session->userdata($this->_clave($proposito));
    return is_array($estado)
      && $correo !== ''
      && $estado['correo'] === $correo
      && (int)$estado['verificado_en'] > 0
      && (time() - (int)$estado['verificado_en']) <= self::VERIFICADO_SEG;
  }

  public function limpiar($proposito)
  {
    $this->CI->session->unset_userdata($this->_clave($proposito));
  }

  /**
   * 27-10-2026: aviso al correo de que la contrasena se cambio (confirmacion).
   * Se llama solo cuando el cambio ya quedo aplicado. Si el correo falla, la
   * contrasena sigue cambiada: solo se registra en el log y devuelve false.
   *
   * @param string $correo  correo de la cuenta (el mismo que se verifico)
   * @param string $origen  'recuperar_clave' (login) o 'cambio_clave' (con sesion)
   * @return bool  true si el correo salio
   */
  public function enviarAvisoCambioClave($correo, $origen = 'recuperar_clave')
  {
    $correo = strtolower(trim((string)$correo));
    if ($correo === '' || strlen($correo) > 100 || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
      return false;
    }

    $enviado = false;
    try {
      $fecha = new DateTime('now', new DateTimeZone('America/Santiago'));

      $navegador = '';
      $this->CI->load->library('user_agent');
      if (!empty($this->CI->agent)) {
        $partes = array_filter(array(
          trim((string)$this->CI->agent->browser()),
          trim((string)$this->CI->agent->platform())
        ), 'strlen');
        $navegador = implode(' en ', $partes);
      }

      $from = $this->_prepararCorreo();
      $this->CI->email->from($from, 'dat@Resto Datamaule');
      $this->CI->email->to($correo);
      $this->CI->email->subject('Tu contraseña de dat@Resto fue cambiada');
      $this->CI->email->message($this->CI->load->view('login/email_aviso_cambio_clave', array(
        'correo' => $correo,
        'fecha' => $fecha->format('d-m-Y'),
        'hora' => $fecha->format('H:i'),
        'medio' => $origen === 'cambio_clave'
          ? 'Cambio de contraseña con sesión iniciada'
          : 'Recuperar contraseña desde el inicio de sesión',
        'ip' => (string)$this->CI->input->ip_address(),
        'navegador' => $navegador !== '' ? $navegador : 'No identificado',
        'url_login' => (string)$this->CI->config->base_url()
      ), true));
      $enviado = (bool)$this->CI->email->send(false);
    } catch (Throwable $e) {
      log_message('error', 'CodigoCorreo[aviso_' . $origen . ']: excepción al enviar el aviso de cambio de clave: ' . $e->getMessage());
    }

    if (!$enviado) {
      log_message('error', 'CodigoCorreo[aviso_' . $origen . ']: no se pudo enviar el aviso de cambio de clave (servidor de correo).');
    }
    return $enviado;
  }

  /** ¿Hay servidor de correo de salida configurado? (config/email.php lee MIPDV_SMTP_* del entorno) */
  private function _correoConfigurado()
  {
    $this->CI->config->load('email', TRUE, TRUE);
    $cfg = (array)$this->CI->config->item('email');
    return trim((string)($cfg['smtp_host'] ?? '')) !== '' && trim((string)($cfg['smtp_user'] ?? '')) !== '';
  }

  /** Configuracion de correo de config/email.php (igual que miPDV). */
  private function _prepararCorreo()
  {
    $this->CI->config->load('email', TRUE, TRUE);
    $cfg = (array)$this->CI->config->item('email');
    if (!empty($cfg['smtp_host'])) {
      $host = trim((string)$cfg['smtp_host']);
      if (preg_match('#^(ssl|tls)://#i', $host, $pref)) {
        $host = substr($host, strlen($pref[0]));
        if (empty($cfg['smtp_crypto'])) {
          $cfg['smtp_crypto'] = 'ssl';
        }
      }
      $cfg['smtp_host'] = $host;
    }
    $cfg['newline'] = "\r\n";
    $cfg['crlf'] = "\r\n";
    $cfg['mailtype'] = 'html';
    $this->CI->load->library('email');
    $this->CI->email->initialize($cfg);
    $this->CI->email->clear(true);
    return !empty($cfg['smtp_user']) ? (string)$cfg['smtp_user'] : 'no-reply@datamaule.cl';
  }

  private function _archivo($llave)
  {
    return APPPATH . 'cache/codigo_correo_' . md5((string)$llave) . '.json';
  }

  /** Marcas de tiempo de los ultimos 15 minutos para una llave (IP o correo). */
  private function _registros($llave)
  {
    $archivo = $this->_archivo($llave);
    $registros = array();
    if (is_file($archivo)) {
      $j = json_decode((string)@file_get_contents($archivo), true);
      if (is_array($j)) {
        $registros = $j;
      }
    }
    $ahora = time();
    $ventana = self::VENTANA_SEG;
    return array_values(array_filter($registros, function ($t) use ($ahora, $ventana) {
      return is_numeric($t) && ($ahora - (int)$t) < $ventana;
    }));
  }

  private function _registrar($llave)
  {
    $registros = $this->_registros($llave);
    $registros[] = time();
    @file_put_contents($this->_archivo($llave), json_encode($registros), LOCK_EX);
  }
}
