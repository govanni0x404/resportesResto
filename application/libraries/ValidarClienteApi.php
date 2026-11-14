<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Llamada unica a validarCliente para todo mipdv (2026-10-17).
 * (Copiado de mipdv_web el 10-11-2026, sin cambios.)
 *
 * Fuente principal: la API de la intranet (api/validarCliente). Usa consultas
 * parametrizadas, tiene limite de peticiones por IP, lee sus credenciales del
 * entorno y entrega `con_sucursal` (la base de cada sucursal).
 *
 * Respaldo: la legacy validarPagos/validarCliente.php, la misma que se usaba
 * hasta hoy. Se usa automaticamente si la intranet no contesta, responde
 * distinto de 200 (401 sin credenciales en el entorno, 429 limite, 5xx) o no
 * devuelve un arreglo JSON. Asi un problema de despliegue o configuracion de
 * la intranet nunca deja a los usuarios sin poder iniciar sesion.
 *
 * Configuracion (application/config/config.php, cada una con su variable de entorno):
 *   validar_cliente_url              MIPDV_VALIDAR_CLIENTE_URL
 *   validar_cliente_url_respaldo     MIPDV_VALIDAR_CLIENTE_URL_RESPALDO ('' = sin respaldo)
 *   validar_cliente_hmac_secret      MIPDV_VALIDAR_CLIENTE_HMAC_SECRET  ('' = sin firma)
 *   validar_cliente_omitir_sensibles  true: pide a la intranet no enviar
 *                                     credenciales de backup ni clave de correo
 *                                     (mipdv no las usa).
 *
 * Los parametros (incluido usuario/clave) los arma quien llama, igual que
 * antes; aqui nunca se escriben en el log.
 */
class ValidarClienteApi
{
	/** Cabecera X-ValidarCliente-Version minima aceptada de la intranet. */
	const VERSION_MINIMA = '2026-10-17';

	/** @var CI_Controller */
	private $CI;

	public function __construct()
	{
		$this->CI =& get_instance();
	}

	/**
	 * @param string $params   Cuerpo x-www-form-urlencoded que ya armaba cada llamador.
	 * @param int    $timeout  Segundos maximos para la respuesta de la fuente de respaldo.
	 * @param string $contexto Etiqueta para el log (no incluye datos sensibles).
	 * @return array body, http, errno, error, fuente ('intranet'|'legacy'), url, dur_ms, intentos
	 */
	public function post($params, $timeout = 60, $contexto = '')
	{
		$r = $this->_post($params, $timeout, $contexto);
		/* Resumen de la ultima llamada para los traces del login (que fuente
		   respondio y como). Sin parametros ni cuerpo. */
		$this->ultimo = [
			'fuente' => (string)$r['fuente'],
			'http' => (int)$r['http'],
			'version' => (string)($r['version'] ?? ''),
			'intentos' => $r['intentos'],
		];
		return $r;
	}

	/** @var array Resumen de la ultima llamada (fuente, http, version, intentos). */
	public $ultimo = [];

	private function _post($params, $timeout, $contexto)
	{
		$params = (string)$params;
		$timeout = max(5, (int)$timeout);
		$urlPrincipal = trim((string)$this->CI->config->item('validar_cliente_url'));
		$urlRespaldo = trim((string)$this->CI->config->item('validar_cliente_url_respaldo'));
		if ($urlPrincipal === '') {
			$urlPrincipal = $urlRespaldo !== '' ? $urlRespaldo : 'https://datamaule.cl/validarPagos/validarCliente.php';
		}
		$principalEsLegacy = $this->_esLegacy($urlPrincipal) || ($urlRespaldo !== '' && $urlPrincipal === $urlRespaldo);
		$intentos = [];

		if ($principalEsLegacy) {
			$r = $this->_curl($urlPrincipal, $params, $timeout);
			$r['fuente'] = 'legacy';
			$r['intentos'] = [$this->_resumen($r)];
			return $r;
		}

		// 1) Intranet, con los extras que la legacy no necesita.
		$r = $this->_curl($urlPrincipal, $this->_paramsIntranet($params), min($timeout, 25));
		$r['fuente'] = 'intranet';
		$intentos[] = $this->_resumen($r);
		$motivo = $this->_motivoInvalida($r);
		/* Solo se acepta la version de la API que respeta id_software y entrega
		   caja_administrador / con_sucursal. Una version anterior desplegada
		   devolveria cajas y bases filtradas por la abreviatura PDVWEB (otro
		   software): en ese caso se sigue con la legacy. */
		if ($motivo === '' && (string)$r['version'] < self::VERSION_MINIMA) {
			$motivo = 'version_intranet_' . ((string)$r['version'] === '' ? 'sin_cabecera' : 'antigua');
		}
		if ($motivo === '') {
			$r['intentos'] = $intentos;
			return $r;
		}

		$sinRespaldo = ($urlRespaldo === '' || $urlRespaldo === $urlPrincipal);
		log_message('error', sprintf(
			'[VALIDAR_CLIENTE][%s] intranet no valida (motivo=%s http=%d errno=%d dur_ms=%d)%s%s',
			(string)$contexto, $motivo, (int)$r['http'], (int)$r['errno'], (int)$r['dur_ms'],
			$this->_pista($r),
			$sinRespaldo ? ' ; sin respaldo configurado.' : ' ; se usa el respaldo legacy.'
		));
		if ($sinRespaldo) {
			$r['intentos'] = $intentos;
			return $r;
		}

		// 2) Respaldo legacy con los parametros originales, exactamente como antes.
		$l = $this->_curl($urlRespaldo, $params, $timeout);
		$l['fuente'] = 'legacy';
		$intentos[] = $this->_resumen($l);
		$l['intentos'] = $intentos;
		return $l;
	}

	/** Agrega omitir_sensibles y la firma HMAC (si hay secreto) para la intranet. */
	private function _paramsIntranet($params)
	{
		$arr = [];
		parse_str($params, $arr);
		$extra = [];
		if ($this->CI->config->item('validar_cliente_omitir_sensibles') && !isset($arr['omitir_sensibles'])) {
			$extra['omitir_sensibles'] = '1';
		}
		$secret = (string)$this->CI->config->item('validar_cliente_hmac_secret');
		if ($secret !== '' && !isset($arr['sig'])) {
			$ts = (string)time();
			$extra['ts'] = $ts;
			$extra['sig'] = self::firma($arr, $ts, $secret);
		}
		if (empty($extra)) {
			return $params;
		}
		return $params . ($params === '' ? '' : '&') . http_build_query($extra);
	}

	/**
	 * Firma igual que intranet api/ValidarCliente::firmaValida():
	 * hmac_sha256(id|ns|isis|id_sucursal|mac_pc|nombre_pc|version|user|ts[|id_software]).
	 * Normaliza como lo hace la intranet al leer los parametros.
	 */
	public static function firma(array $arr, $ts, $secret)
	{
		$idSoftware = isset($arr['id_software']) ? (int)$arr['id_software'] : 0;
		$payload = implode('|', [
			isset($arr['id']) ? (string)$arr['id'] : '',
			isset($arr['ns']) ? (string)$arr['ns'] : '',
			(string)(isset($arr['isis']) ? (int)$arr['isis'] : 0),
			(string)(isset($arr['id_sucursal']) ? (int)$arr['id_sucursal'] : 0),
			array_key_exists('mac_pc', $arr) ? (string)$arr['mac_pc'] : 'SIN MAC',
			isset($arr['nombre_pc']) ? (string)$arr['nombre_pc'] : '',
			isset($arr['version']) ? (string)$arr['version'] : '',
			isset($arr['user']) ? (string)$arr['user'] : '',
			(string)$ts,
		]);
		if ($idSoftware > 0) {
			$payload .= '|' . $idSoftware;
		}
		return hash_hmac('sha256', $payload, (string)$secret);
	}

	private function _curl($url, $postFields, $timeout)
	{
		$t0 = microtime(true);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min(10, (int)$timeout));
		curl_setopt($ch, CURLOPT_TIMEOUT, (int)$timeout);
		curl_setopt($ch, CURLOPT_NOSIGNAL, true);
		$version = '';
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($c, $linea) use (&$version) {
			if (stripos($linea, 'X-ValidarCliente-Version:') === 0) {
				$version = trim(substr($linea, strlen('X-ValidarCliente-Version:')));
			}
			return strlen($linea);
		});
		$body = curl_exec($ch);
		$errno = curl_errno($ch);
		$error = $body === false ? curl_error($ch) : '';
		$http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		return [
			'body' => $body === false ? '' : (string)$body,
			'http' => $http,
			'errno' => (int)$errno,
			'error' => (string)$error,
			'url' => (string)$url,
			'dur_ms' => (int)round((microtime(true) - $t0) * 1000),
			'version' => (string)$version,
		];
	}

	/** '' si la respuesta sirve; si no, el motivo. */
	private function _motivoInvalida(array $r)
	{
		if ((int)$r['errno'] !== 0) return 'sin_conexion';
		if ((int)$r['http'] !== 200) return 'http_' . (int)$r['http'];
		$dec = json_decode(trim((string)$r['body']), true);
		if (!is_array($dec) || count($dec) === 0) return 'sin_json';
		return '';
	}

	private function _pista(array $r)
	{
		switch ((int)$r['http']) {
			case 401: return ' [401: revisar validar_cliente_legacy_user/pass en el entorno de la intranet, o la firma HMAC]';
			case 429: return ' [429: limite por IP; agregar la IP de mipdv a validar_cliente_ddos_trusted_ips]';
			case 404: return ' [404: api/validarCliente no desplegada en la intranet]';
		}
		return '';
	}

	private function _esLegacy($url)
	{
		return stripos((string)$url, 'validarPagos/validarCliente.php') !== false;
	}

	private function _resumen(array $r)
	{
		return [
			'host' => (string)parse_url((string)$r['url'], PHP_URL_HOST),
			'http' => (int)$r['http'],
			'errno' => (int)$r['errno'],
			'dur_ms' => (int)$r['dur_ms'],
		];
	}
}
