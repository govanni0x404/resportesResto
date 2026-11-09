<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Correos de la caja (cierre de caja, anulaciones, prueba) con el servidor de correo guardado en la configuración del
 * cliente (configuracion.servidor_correo, puerto_correo, correo_sistema, clave_correo_electronico, correo_ssl), el mismo
 * que usa el POS de escritorio. Los destinatarios son los de Configuraciones > Informes por correo (separados por coma).
 * Nunca corta lo que se estaba haciendo: si el correo falla, devuelve [false, motivo] y queda en el log.
 */
class Correo_resto
{
	/** Datos del servidor de correo de la base del cliente. */
	public function servidor($db)
	{
		$cols = array();
		foreach ($db->list_fields('configuracion') as $c) $cols[$c] = true;
		$quiero = array('servidor_correo', 'puerto_correo', 'correo_sistema', 'clave_correo_electronico', 'correo_ssl', 'correo_electronico');
		$sel = array_values(array_filter($quiero, function ($c) use ($cols) { return isset($cols[$c]); }));
		if (!$sel) return array();
		$f = $db->query('SELECT ' . implode(', ', $sel) . ' FROM configuracion LIMIT 1')->row_array();
		return is_array($f) ? $f : array();
	}

	/** "a@x.cl, b@y.cl" => ['a@x.cl', 'b@y.cl'] (solo los válidos). */
	public static function destinatarios($texto)
	{
		$res = array();
		foreach (preg_split('/[,;\s]+/', (string)$texto) as $c) {
			$c = trim($c);
			if ($c !== '' && filter_var($c, FILTER_VALIDATE_EMAIL)) $res[strtolower($c)] = $c;
		}
		return array_values($res);
	}

	/**
	 * Envía un correo HTML. $para = texto con correos separados por coma (si es null, los de Configuraciones).
	 * Devuelve [ok, detalle].
	 */
	public function enviar($CI, $db, $asunto, $html, $para = null)
	{
		try {
			$s = $this->servidor($db);
			$destinos = self::destinatarios($para === null ? (isset($s['correo_electronico']) ? $s['correo_electronico'] : '') : $para);
			if (!$destinos) return array(false, 'No hay correos de destino (Configuraciones > Informes por correo)');
			$host = trim((string)(isset($s['servidor_correo']) ? $s['servidor_correo'] : ''));
			$usuario = trim((string)(isset($s['correo_sistema']) ? $s['correo_sistema'] : ''));
			if ($host === '' || $usuario === '') return array(false, 'Falta el servidor de correo del sistema en la base (servidor_correo / correo_sistema)');
			$puerto = (int)(isset($s['puerto_correo']) ? $s['puerto_correo'] : 0) ?: 587;
			$ssl = isset($s['correo_ssl']) && ($s['correo_ssl'] === 't' || $s['correo_ssl'] === true);
			$cifrado = $ssl || $puerto === 465 ? 'ssl' : ($puerto === 587 ? 'tls' : '');

			$CI->load->library('email');
			$CI->email->clear(true);
			$CI->email->initialize(array(
				'protocol' => 'smtp',
				'smtp_host' => $host,
				'smtp_user' => $usuario,
				'smtp_pass' => (string)(isset($s['clave_correo_electronico']) ? $s['clave_correo_electronico'] : ''),
				'smtp_port' => $puerto,
				'smtp_crypto' => $cifrado,
				'smtp_timeout' => 10,
				'mailtype' => 'html',
				'charset' => 'utf-8',
				'newline' => "\r\n",
				'crlf' => "\r\n",
				'wordwrap' => false,
			));
			$CI->email->from($usuario, 'Resto Web');
			$CI->email->to($destinos);
			$CI->email->subject($asunto);
			$CI->email->message($html);
			if (@$CI->email->send(false)) return array(true, 'Enviado a ' . implode(', ', $destinos));
			$detalle = trim(strip_tags((string)$CI->email->print_debugger(array('headers'))));
			log_message('error', 'Correo_resto: no se pudo enviar "' . $asunto . '": ' . mb_substr($detalle, 0, 500));
			return array(false, 'El servidor de correo no aceptó el envío. Revisa el servidor, el usuario y la clave del correo del sistema.');
		} catch (Throwable $e) {
			log_message('error', 'Correo_resto: ' . $e->getMessage());
			return array(false, 'No se pudo enviar el correo: ' . $e->getMessage());
		}
	}
}
