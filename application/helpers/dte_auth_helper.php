<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Proveedor de emisión DTE y autenticación ante él (11-11-2026).
 * Basado en mipdv_web/application/helpers/dte_auth_helper.php y dte_proveedor_helper.php.
 *
 * Configuraciones > Facturación electrónica:
 *
 *   Empresa proveedora DTE (configuracion.proveedor_dte):
 *     acierto    (por defecto) Cloud Apps / nubika (https://api.cloud-apps.cl). NULL o vacío = acierto.
 *     datamaule  SimpleFactura de datamaule.cl (https://facturacion.datamaule.cl/api/v1/integracion).
 *
 *   Tipo de autenticación (configuracion.tipo_auth_dte):
 *     apikey   X-API-Key: {API Key}                 (el recomendado por el manual de SimpleFactura)
 *     bearer   Authorization: Bearer {API Key}
 *     token    X-MIFACTURADOR-TOKEN: {API Key}      (SimpleFactura también lo acepta)
 *     basic    (por defecto) Authorization: Basic base64(usuario:contraseña). Es el encabezado de siempre.
 *              Con datamaule la contraseña es la API Key (el usuario puede ser el RUT).
 *
 * La API Key / token se guarda cifrada en configuracion.token_dte_enc con la clave del entorno
 * RESTO_DTE_TOKEN_KEY (64 caracteres hexadecimales). Nunca se escribe en un log ni vuelve al navegador.
 *
 * Las lecturas usan to_jsonb(...) ->> 'columna': en una base que todavía no corrió la migración
 * 20261111100000 no fallan, devuelven NULL y todo sigue con Cloud Apps y basic, como antes.
 */

if (!function_exists('dte_proveedores_validos')) {
	function dte_proveedores_validos()
	{
		return array('acierto', 'datamaule');
	}
}

if (!function_exists('dte_auth_tipos_validos')) {
	function dte_auth_tipos_validos()
	{
		return array('apikey', 'bearer', 'token', 'basic');
	}
}

if (!function_exists('dte_auth_etiquetas')) {
	/** Texto de cada tipo en la pantalla. */
	function dte_auth_etiquetas()
	{
		return array(
			'apikey' => 'X-API-Key',
			'bearer' => 'Bearer (Authorization: Bearer)',
			'token'  => 'Token (X-MIFACTURADOR-TOKEN)',
			'basic'  => 'Basic (usuario y contraseña)',
		);
	}
}

if (!function_exists('dte_config_valor')) {
	/** Valor de una columna de configuracion, o '' si la columna no existe (base sin migrar). */
	function dte_config_valor($db, $columna)
	{
		try {
			$q = $db->query("SELECT to_jsonb(c) ->> ? AS v FROM (SELECT * FROM configuracion ORDER BY 1 LIMIT 1) c", array((string)$columna));
			$r = ($q !== false && is_object($q)) ? $q->row() : null;
			return $r ? trim((string)$r->v) : '';
		} catch (Throwable $e) {
			log_message('error', 'dte_config_valor(' . $columna . '): ' . $e->getMessage());
			return '';
		}
	}
}

if (!function_exists('dte_proveedor_actual')) {
	/** 'acierto' | 'datamaule'. Ausente, vacío o raro = acierto. */
	function dte_proveedor_actual($db)
	{
		$v = strtolower(dte_config_valor($db, 'proveedor_dte'));
		return in_array($v, dte_proveedores_validos(), true) ? $v : 'acierto';
	}
}

if (!function_exists('dte_proveedor_nombre')) {
	function dte_proveedor_nombre($proveedor)
	{
		return $proveedor === 'datamaule' ? 'datamaule.cl' : 'Cloud Apps';
	}
}

if (!function_exists('dte_auth_tipo')) {
	/** Tipo configurado. Cualquier valor raro o ausente = basic. */
	function dte_auth_tipo($db)
	{
		$v = strtolower(dte_config_valor($db, 'tipo_auth_dte'));
		return in_array($v, dte_auth_tipos_validos(), true) ? $v : 'basic';
	}
}

if (!function_exists('dte_auth_columnas_listas')) {
	/** true si la base ya tiene las columnas de la migración 20261111100000. */
	function dte_auth_columnas_listas($db)
	{
		try {
			$q = $db->query("SELECT count(*) AS n FROM information_schema.columns WHERE table_schema = 'public'
				AND table_name = 'configuracion' AND column_name IN ('proveedor_dte', 'tipo_auth_dte', 'token_dte_enc', 'token_dte_last4')");
			$r = ($q !== false && is_object($q)) ? $q->row() : null;
			return $r && (int)$r->n === 4;
		} catch (Throwable $e) {
			return false;
		}
	}
}

// ---------------------------------------------------------------------------
// Cifrado de la API Key / token
// ---------------------------------------------------------------------------

if (!function_exists('dte_auth_clave_cifrado')) {
	/** Clave binaria (32 bytes) desde RESTO_DTE_TOKEN_KEY, o '' si no está o no tiene 64 hexadecimales. */
	function dte_auth_clave_cifrado()
	{
		$hex = function_exists('_resto_env_raw') ? (string)_resto_env_raw('RESTO_DTE_TOKEN_KEY', '') : (string)getenv('RESTO_DTE_TOKEN_KEY');
		$hex = trim($hex);
		return preg_match('/^[0-9a-fA-F]{64}$/', $hex) ? hex2bin($hex) : '';
	}
}

if (!function_exists('dte_auth_cifrador')) {
	/** Librería encryption de CI con la clave de RESTO_DTE_TOKEN_KEY, o null si no hay clave. */
	function dte_auth_cifrador()
	{
		$clave = dte_auth_clave_cifrado();
		if ($clave === '') {
			return null;
		}
		$CI =& get_instance();
		$CI->load->library('encryption');
		$CI->encryption->initialize(array('driver' => 'openssl', 'cipher' => 'aes-256', 'mode' => 'cbc', 'key' => $clave));
		return $CI->encryption;
	}
}

if (!function_exists('dte_auth_cifrar')) {
	/** Texto cifrado, o false si falta RESTO_DTE_TOKEN_KEY o no se pudo cifrar. */
	function dte_auth_cifrar($texto)
	{
		$c = dte_auth_cifrador();
		if (!$c) {
			return false;
		}
		$enc = $c->encrypt((string)$texto);
		return is_string($enc) && $enc !== '' ? $enc : false;
	}
}

if (!function_exists('dte_auth_token_info')) {
	/** array('tiene' => bool, 'last4' => '1234') sin descifrar. */
	function dte_auth_token_info($db)
	{
		$enc = dte_config_valor($db, 'token_dte_enc');
		return array('tiene' => $enc !== '', 'last4' => $enc !== '' ? dte_config_valor($db, 'token_dte_last4') : '');
	}
}

if (!function_exists('dte_auth_token')) {
	/** API Key / token descifrado, o '' si no hay o no se pudo descifrar. */
	function dte_auth_token($db)
	{
		$enc = dte_config_valor($db, 'token_dte_enc');
		if ($enc === '') {
			return '';
		}
		$c = dte_auth_cifrador();
		if (!$c) {
			log_message('error', 'dte_auth_token: hay API Key guardada pero falta RESTO_DTE_TOKEN_KEY en el entorno para descifrarla');
			return '';
		}
		$tok = $c->decrypt($enc);
		if (!is_string($tok)) {
			log_message('error', 'dte_auth_token: no se pudo descifrar la API Key (¿cambió RESTO_DTE_TOKEN_KEY?)');
			return '';
		}
		// Un salto de línea en un encabezado permitiría inyectar otros encabezados
		return trim(str_replace(array("\r", "\n"), '', $tok));
	}
}

// ---------------------------------------------------------------------------
// Encabezados
// ---------------------------------------------------------------------------

if (!function_exists('dte_auth_headers')) {
	/**
	 * Encabezado de autenticación para una llamada al proveedor DTE.
	 * @return array('ok' => bool, 'headers' => array(lineas), 'mensaje' => string)
	 */
	function dte_auth_headers($tipo, $usuario, $clave, $token)
	{
		$tipo = in_array($tipo, dte_auth_tipos_validos(), true) ? $tipo : 'basic';
		if ($tipo === 'basic') {
			$ok = ((string)$usuario !== '' && (string)$clave !== '');
			return array(
				'ok' => $ok,
				'headers' => $ok ? array('Authorization: Basic ' . base64_encode($usuario . ':' . $clave)) : array(),
				'mensaje' => $ok ? '' : 'Faltan el usuario y la contraseña en Acceso facturación',
			);
		}
		$token = trim(str_replace(array("\r", "\n"), '', (string)$token));
		if ($token === '') {
			$et = dte_auth_etiquetas();
			return array('ok' => false, 'headers' => array(),
				'mensaje' => 'Falta la API Key en Acceso facturación (tipo de autenticación: ' . $et[$tipo] . ')');
		}
		$cabeceras = array(
			'apikey' => 'X-API-Key: ',
			'bearer' => 'Authorization: Bearer ',
			'token'  => 'X-MIFACTURADOR-TOKEN: ',
		);
		return array('ok' => true, 'headers' => array($cabeceras[$tipo] . $token), 'mensaje' => '');
	}
}
