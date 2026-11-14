<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Login contra la intranet SOLO por API (10-11-2026), igual que mipdv_web.
 *
 * Copiado y adaptado de mipdv_web/application/models/Login_model.php:
 *   AppWebLogin/index                 login: usuario + clave + id_software, firma HMAC -> Bearer
 *   AppWebLogin/relogin               cambio de empresa con el Bearer de la sesión
 *   AppWebLogin/refrescar_token       renueva el Bearer antes de que venza (dura 1 h)
 *   AppWebLogin/sucursal_por_defecto, empresas_usuario, conexiones_usuario
 *
 * Ya no abre ninguna conexión a la base de la intranet (antes: grupo 'default'
 * de database.php). La base de la empresa la resuelve la librería
 * ConexionesTenant (validarCliente + DbManager).
 */
class Mlogin extends CI_Model
{
	/**
	 * Por qué falló el último login_user() / relogin_user().
	 * ['transitorio' => bool, 'http_code' => int, 'curl_errno' => int, 'motivo' => string, 'mensaje' => string]
	 */
	public $ultimo_fallo = null;

	function __construct()
	{
		parent::__construct();
		$this->load->library('session');
	}

	// ======================================================================
	// Mensaje para la pantalla de login
	// ======================================================================

	public function mensajeUltimoFallo()
	{
		$f = is_array($this->ultimo_fallo) ? $this->ultimo_fallo : array();
		$motivo = (string)($f['motivo'] ?? '');
		if ($motivo === 'sin_apikey') {
			return 'Falta configurar el acceso a la intranet (MIPDV_APP_WEB_LOGIN_APIKEY).';
		}
		if (!empty($f['transitorio'])) {
			return 'No se pudo contactar a la intranet. Intente nuevamente en unos minutos.';
		}
		if ((int)($f['http_code'] ?? 0) === 429) {
			return 'Por seguridad, el acceso quedó bloqueado tras varios intentos fallidos. Inténtelo nuevamente en unos minutos.';
		}
		return 'Los datos introducidos son incorrectos';
	}

	// ======================================================================
	// Configuración y transporte (copiado de Login_model de mipdv_web)
	// ======================================================================

	private function _appWebLoginCfg()
	{
		static $cfg = null;
		if ($cfg !== null) {
			return $cfg;
		}
		$cfg = array(
			'url'            => trim((string) $this->config->item('app_web_login_url')),
			'apikey'         => (string) $this->config->item('app_web_login_apikey'),
			'require_apikey' => (bool) $this->config->item('app_web_login_require_apikey'),
			'require_hmac'   => (bool) $this->config->item('app_web_login_require_hmac'),
			'curl_timeout'   => (int) $this->config->item('app_web_login_curl_timeout') ?: 15,
			'curl_ctimeout'  => (int) $this->config->item('app_web_login_curl_connect_tm') ?: 5,
		);
		if ($cfg['url'] === '') {
			$cfg['url'] = 'https://datamaule.cl/miIntranet/index.php/api/AppWebLogin/index';
		}
		return $cfg;
	}

	/** Firma: hmac_sha256(id_software|username|ts, apikey) — igual a la intranet. */
	private function _appWebLoginComputeSig($idSoftware, $username, $ts, $apikey)
	{
		$payload = (string)(int)$idSoftware . '|' . (string)$username . '|' . (string)(int)$ts;
		return strtolower(hash_hmac('sha256', $payload, (string)$apikey, false));
	}

	/**
	 * CA bundle multi-plataforma. '' => no se pasa CURLOPT_CAINFO y curl usa el suyo
	 * (pasar CURLOPT_CAINFO => '' produce el error 77).
	 */
	private function _appWebLoginCaBundlePath()
	{
		static $cache = null;
		if ($cache !== null) {
			return $cache;
		}
		$candidatos = array();
		$iniCainfo = (string)ini_get('curl.cainfo');
		$iniCafile = (string)ini_get('openssl.cafile');
		if ($iniCainfo !== '') $candidatos[] = $iniCainfo;
		if ($iniCafile !== '' && $iniCafile !== $iniCainfo) $candidatos[] = $iniCafile;
		if (function_exists('openssl_get_cert_locations')) {
			$locs = openssl_get_cert_locations();
			if (is_array($locs) && !empty($locs['default_cert_file']) && is_file($locs['default_cert_file'])) {
				$candidatos[] = $locs['default_cert_file'];
			}
		}
		foreach (array(
			'/etc/ssl/certs/ca-certificates.crt', '/etc/ssl/cert.pem', '/private/etc/ssl/cert.pem',
			'/opt/homebrew/etc/openssl@3/cert.pem', '/opt/homebrew/etc/openssl@1.1/cert.pem', '/opt/homebrew/etc/openssl/cert.pem',
			'/usr/local/etc/openssl@3/cert.pem', '/usr/local/etc/openssl@1.1/cert.pem', '/usr/local/etc/openssl/cert.pem',
			'/opt/local/etc/openssl/cert.pem',
		) as $p) {
			$candidatos[] = $p;
		}
		$cache = '';
		foreach ($candidatos as $p) {
			if (is_string($p) && $p !== '' && is_file($p) && is_readable($p)) {
				$cache = $p;
				break;
			}
		}
		return $cache;
	}

	/** URL de otro método del mismo AppWebLogin: .../AppWebLogin/index -> .../AppWebLogin/<metodo>. */
	private function _appWebLoginUrlFor($method)
	{
		$base = (string)$this->_appWebLoginCfg()['url'];
		$method = trim((string)$method, '/');
		if ($method === '' || $method === 'index') {
			return $base;
		}
		if (preg_match('#/AppWebLogin(/[^/]*)$#i', $base, $m)) {
			return substr($base, 0, -strlen($m[1])) . '/' . $method;
		}
		return rtrim($base, '/') . '/' . $method;
	}

	/** POST JSON a la intranet. $bearer '' = sin cabecera Authorization. */
	private function _postJson($url, array $body, $bearer = '')
	{
		$cfg = $this->_appWebLoginCfg();
		$t0 = microtime(true);
		$salida = array('http_code' => 0, 'curl_errno' => 0, 'curl_error' => '', 'decoded' => null, 'dur_ms' => 0, 'body_head' => '');

		$jsonBody = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if ($jsonBody === false) {
			$salida['curl_errno'] = -900;
			$salida['curl_error'] = 'json_encode_failed';
			return $salida;
		}
		$headers = array(
			'Content-Type: application/json; charset=utf-8',
			'Accept: application/json',
			'Content-Length: ' . strlen($jsonBody),
			'X-Forwarded-For: ' . (string)($this->input->ip_address() ?: '127.0.0.1'),
			'User-Agent: resto-web-app-web-login/' . (string)(ENVIRONMENT ?: 'production'),
		);
		if ($bearer !== '') {
			$headers[] = 'Authorization: Bearer ' . $bearer;
		}

		$ch = curl_init($url);
		$opts = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER         => false,
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => $jsonBody,
			CURLOPT_HTTPHEADER     => $headers,
			CURLOPT_CONNECTTIMEOUT => $cfg['curl_ctimeout'],
			CURLOPT_TIMEOUT        => $cfg['curl_timeout'],
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS      => 3,
			CURLOPT_FORBID_REUSE   => true,
			CURLOPT_FAILONERROR    => false,
		);
		$ca = $this->_appWebLoginCaBundlePath();
		if ($ca !== '') {
			$opts[CURLOPT_CAINFO] = $ca;
		}
		curl_setopt_array($ch, $opts);
		$resp = curl_exec($ch);
		$salida['http_code'] = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$salida['curl_errno'] = (int)curl_errno($ch);
		$salida['curl_error'] = (string)curl_error($ch);
		curl_close($ch);

		if (is_string($resp) && $resp !== '') {
			$dec = json_decode($resp, true);
			$salida['decoded'] = is_array($dec) ? $dec : null;
			$salida['body_head'] = substr($resp, 0, 200);
		}
		$salida['dur_ms'] = (int)round((microtime(true) - $t0) * 1000);
		return $salida;
	}

	/** Llamada a un endpoint con Bearer (el de la sesión), renovándolo antes si está por vencer. */
	private function _postBearer($method, array $body)
	{
		if ($method !== 'refrescar_token') {
			$this->_renovarBearerSiEstaPorVencer();
		}
		$tok = $this->_sessionBearerToken();
		if ($tok === '') {
			return array('http_code' => 401, 'curl_errno' => 0, 'curl_error' => 'bearer_missing_in_session', 'decoded' => null, 'dur_ms' => 0, 'body_head' => '');
		}
		/* Token ya vencido: la API (modo stealth) contestaría un 404 engañoso; se responde 401 sin gastar la llamada. */
		$exp = (int)$this->session->userdata('awl_bearer_expires_at');
		if ($exp > 0 && $exp <= time()) {
			return array('http_code' => 401, 'curl_errno' => 0, 'curl_error' => 'bearer_expirado_en_sesion', 'decoded' => null, 'dur_ms' => 0, 'body_head' => '');
		}
		return $this->_postJson($this->_appWebLoginUrlFor($method), $body, $tok);
	}

	private function _clasificarFallo(array $r, $motivo)
	{
		$http = (int)$r['http_code'];
		$cerr = (int)$r['curl_errno'];
		$dec = is_array($r['decoded']) ? $r['decoded'] : array();
		return array(
			'transitorio' => ($cerr !== 0) || in_array($http, array(0, 404, 408, 500, 501, 502, 503, 504), true),
			'http_code' => $http,
			'curl_errno' => $cerr,
			'motivo' => (string)$motivo,
			'mensaje' => (string)($dec['mensaje'] ?? $r['curl_error']),
		);
	}

	// ======================================================================
	// Bearer en sesión
	// ======================================================================

	private function _sessionBearerToken()
	{
		$tok = $this->session->userdata('awl_bearer_access_token');
		return is_string($tok) ? trim($tok) : '';
	}

	private function _sessionStoreBearerToken($accessToken, $expiresIn = null)
	{
		if (!is_string($accessToken) || $accessToken === '') {
			$this->session->unset_userdata(array('awl_bearer_access_token', 'awl_bearer_expires_at', 'awl_bearer_issued_at'));
			return;
		}
		$ei = (int)(is_numeric($expiresIn) ? $expiresIn : 3600);
		if ($ei < 60) { $ei = 60; }
		$this->session->set_userdata(array(
			'awl_bearer_access_token' => $accessToken,
			'awl_bearer_issued_at'    => time(),
			'awl_bearer_expires_at'   => time() + $ei,
		));
	}

	public function renovarBearerSiCorresponde()
	{
		$this->_renovarBearerSiEstaPorVencer();
	}

	/**
	 * Renueva el Bearer si le quedan menos de app_web_login_refresh_margin_seconds.
	 * Un token ya vencido no se puede renovar (hay que entrar de nuevo). Si la
	 * renovación falla se apaga un rato, para no golpear el limitador de la API
	 * (mismo criterio que mipdv_web).
	 */
	private function _renovarBearerSiEstaPorVencer($margenSegundos = null)
	{
		static $yaIntentado = false;
		if ($yaIntentado) {
			return;
		}
		if ($margenSegundos === null) {
			$margenSegundos = (int)$this->config->item('app_web_login_refresh_margin_seconds');
			if ($margenSegundos <= 0) { $margenSegundos = 900; }
		}
		if ((int)$this->session->userdata('awl_refresh_deshabilitado_hasta') > time()) {
			return;
		}
		$tok = $this->_sessionBearerToken();
		$exp = (int)$this->session->userdata('awl_bearer_expires_at');
		if ($tok === '' || $exp <= 0) {
			return;
		}
		$faltan = $exp - time();
		if ($faltan > (int)$margenSegundos || $faltan <= 0) {
			return;
		}
		$yaIntentado = true;
		$r = $this->_postJson($this->_appWebLoginUrlFor('refrescar_token'), array(), $tok);
		$dec = is_array($r['decoded']) ? $r['decoded'] : array();
		$nuevo = isset($dec['access_token']) ? (string)$dec['access_token'] : '';
		if ($r['http_code'] === 200 && !empty($dec['ok']) && $nuevo !== '') {
			$this->_sessionStoreBearerToken($nuevo, $dec['expires_in'] ?? null);
			$this->session->unset_userdata('awl_refresh_deshabilitado_hasta');
			return;
		}
		$castigo = ($r['http_code'] === 404) ? 1800 : 600;
		$this->session->set_userdata('awl_refresh_deshabilitado_hasta', time() + $castigo);
		log_message('error', sprintf('[MLOGIN][refrescar_token][FAIL] http=%d msg="%s" faltaban=%ds — renovación apagada %ds',
			$r['http_code'], substr((string)($dec['mensaje'] ?? $r['curl_error']), 0, 160), $faltan, $castigo));
	}

	// ======================================================================
	// Login / relogin
	// ======================================================================

	/** Fila de la API -> objeto con los campos que usa el resto del sistema. */
	private function _normalizarFila(array $data)
	{
		$row = (object)array_merge(array(
			'id_usuario_appweb' => null, 'id_cliente' => null, 'id_software' => null, 'id_sucursal' => null,
			'id_usuario_cliente' => null, 'correo_acceso' => null, 'nombre_conexion' => null,
			'autoriza_cambio_conexion' => null, 'id_rol' => null, 'nombre_usuario' => null,
			'rut' => null, 'dv' => null, 'nombre_cliente' => null, 'nombre_fantasia' => null, 'abreviatura' => null,
		), $data);
		/* nombre_conexion identifica a la empresa en el resto del sistema (carpeta
		   de uploads, agente de impresión, app móvil). Si la intranet no lo trae se
		   usa el RUT sin puntos, que es como están nombradas las conexiones. */
		if (trim((string)$row->nombre_conexion) === '' && (int)$row->rut > 0) {
			$row->nombre_conexion = (int)$row->rut . '-' . strtoupper((string)$row->dv);
		}
		return $row;
	}

	/**
	 * @param bool $guardarEnSesion false para la app móvil (no usa la sesión web).
	 * @return object|false
	 */
	public function login_user($username, $password, $id_software, $guardarEnSesion = true)
	{
		$this->ultimo_fallo = null;
		$username = trim((string)$username);
		$password = (string)$password;
		$id_software = (int)$id_software;
		$cfg = $this->_appWebLoginCfg();

		if ($username === '' || $password === '' || $id_software < 1) {
			$this->ultimo_fallo = array('transitorio' => false, 'http_code' => 0, 'curl_errno' => 0, 'motivo' => 'campos_vacios', 'mensaje' => '');
			return false;
		}
		if ($cfg['require_apikey'] && $cfg['apikey'] === '') {
			log_message('error', '[MLOGIN][login_user] Falta MIPDV_APP_WEB_LOGIN_APIKEY en el entorno (.env): no se puede firmar el login.');
			$this->ultimo_fallo = array('transitorio' => false, 'http_code' => 0, 'curl_errno' => 0, 'motivo' => 'sin_apikey', 'mensaje' => '');
			return false;
		}

		$ts = time();
		$body = array('username' => $username, 'password' => $password, 'id_software' => $id_software);
		if ($cfg['require_apikey']) {
			$body['apikey'] = $cfg['apikey'];
		}
		if ($cfg['require_hmac']) {
			$body['ts'] = (string)$ts;
			$body['sig'] = $this->_appWebLoginComputeSig($id_software, $username, $ts, $cfg['apikey']);
		}

		$r = $this->_postJson($cfg['url'], $body);
		$dec = is_array($r['decoded']) ? $r['decoded'] : array();
		$ok = ($r['http_code'] === 200 && !empty($dec['ok']) && is_array($dec['data'] ?? null) && !empty($dec['data']));
		if (!$ok) {
			$this->ultimo_fallo = $this->_clasificarFallo($r, 'api_rechazo');
			log_message('error', sprintf('[MLOGIN][login_user][FAIL] user=%s idsoft=%d http=%d cerr=%d msg="%s" dur_ms=%d',
				$username, $id_software, $r['http_code'], $r['curl_errno'], substr((string)$this->ultimo_fallo['mensaje'], 0, 160), $r['dur_ms']));
			return false;
		}

		if ($guardarEnSesion) {
			$this->_sessionStoreBearerToken((string)($dec['access_token'] ?? ''), $dec['expires_in'] ?? null);
		}
		return $this->_normalizarFila((array)$dec['data']);
	}

	/** Cambio de empresa sin pedir clave: la API comprueba con el Bearer que la empresa sea del usuario. */
	public function relogin_user($idEmpresa, $username, $id_software)
	{
		$this->ultimo_fallo = null;
		$idEmpresa = (int)$idEmpresa;
		$username = trim((string)$username);
		$id_software = (int)$id_software;
		$cfg = $this->_appWebLoginCfg();
		if ($idEmpresa < 1 || $username === '' || $id_software < 1) {
			return false;
		}

		$ts = time();
		$body = array('id_empresa' => $idEmpresa, 'username' => $username, 'id_software' => $id_software);
		/* apikey + firma en el cuerpo, como mipdv_web: sirve mientras la intranet
		   no exija el Bearer (app_web_login_relogin_exige_bearer). */
		if ($cfg['require_apikey'] && $cfg['apikey'] !== '') {
			$body['apikey'] = $cfg['apikey'];
		}
		if ($cfg['require_hmac'] && $cfg['apikey'] !== '') {
			$body['ts'] = (string)$ts;
			$body['sig'] = $this->_appWebLoginComputeSig($id_software, $username, $ts, $cfg['apikey']);
		}
		$this->_renovarBearerSiEstaPorVencer(120);
		$r = $this->_postJson($this->_appWebLoginUrlFor('relogin'), $body, $this->_sessionBearerToken());
		$dec = is_array($r['decoded']) ? $r['decoded'] : array();
		$ok = ($r['http_code'] === 200 && !empty($dec['ok']) && is_array($dec['data'] ?? null) && !empty($dec['data']));
		if (!$ok) {
			$this->ultimo_fallo = $this->_clasificarFallo($r, 'api_rechazo');
			$codigo = (string)($dec['code'] ?? '');
			if ($r['http_code'] === 401 && strpos($codigo, 'bearer_') === 0) {
				$this->_sessionStoreBearerToken(null);   // sesión de la intranet terminada: hay que entrar de nuevo
			}
			log_message('error', sprintf('[MLOGIN][relogin_user][FAIL] user=%s id_empresa=%d idsoft=%d http=%d code=%s msg="%s"',
				$username, $idEmpresa, $id_software, $r['http_code'], $codigo, substr((string)$this->ultimo_fallo['mensaje'], 0, 160)));
			return false;
		}
		$this->_sessionStoreBearerToken((string)($dec['access_token'] ?? ''), $dec['expires_in'] ?? null);
		return $this->_normalizarFila((array)$dec['data']);
	}

	// ======================================================================
	// Datos de la intranet con Bearer
	// ======================================================================

	/** Sucursal por defecto (la vigente de id más bajo) del cliente. @return object|null */
	public function obtenerSucursal($idCliente)
	{
		$idCliente = (int)$idCliente;
		if ($idCliente < 1) {
			return null;
		}
		$r = $this->_postBearer('sucursal_por_defecto', array('id_cliente' => $idCliente));
		$dec = is_array($r['decoded']) ? $r['decoded'] : array();
		if ($r['http_code'] === 200 && !empty($dec['ok']) && is_array($dec['data'] ?? null) && !empty($dec['data'])) {
			return (object)$dec['data'];
		}
		if ($r['http_code'] !== 200) {
			log_message('error', sprintf('[MLOGIN][obtenerSucursal][FAIL] idc=%d http=%d msg="%s"', $idCliente, $r['http_code'], substr((string)($dec['mensaje'] ?? $r['curl_error']), 0, 160)));
		}
		return null;
	}

	/** Empresas del usuario (selector "Cambiar Empresa"). Mismo formato de antes: JSON [{id_cliente, nombre_cliente, nombre_fantasia, ...}]. */
	public function getListaDeClientesParaElUsuario()
	{
		if (strlen((string)$this->session->userdata('rut_cliente')) === 0) {
			return null;
		}
		$r = $this->_postBearer('empresas_usuario', array());
		$dec = is_array($r['decoded']) ? $r['decoded'] : array();
		$filas = ($r['http_code'] === 200 && !empty($dec['ok']) && is_array($dec['data'] ?? null)) ? $dec['data'] : array();
		if ($r['http_code'] !== 200) {
			log_message('error', sprintf('[MLOGIN][empresas_usuario][FAIL] http=%d msg="%s"', $r['http_code'], substr((string)($dec['mensaje'] ?? $r['curl_error']), 0, 160)));
		}
		return json_encode($filas);
	}

	/** Conexiones del cliente. Mismo formato de antes: JSON [{nombre_conexion}]. */
	public function obtenerListaConexionesUsuario($idCliente)
	{
		if (strlen((string)$this->session->userdata('rut_cliente')) === 0) {
			return null;
		}
		$r = $this->_postBearer('conexiones_usuario', array('id_cliente' => (int)$idCliente));
		$dec = is_array($r['decoded']) ? $r['decoded'] : array();
		$filas = ($r['http_code'] === 200 && !empty($dec['ok']) && is_array($dec['data'] ?? null)) ? $dec['data'] : array();
		if ($r['http_code'] !== 200) {
			log_message('error', sprintf('[MLOGIN][conexiones_usuario][FAIL] idc=%d http=%d msg="%s"', (int)$idCliente, $r['http_code'], substr((string)($dec['mensaje'] ?? $r['curl_error']), 0, 160)));
		}
		return json_encode($filas);
	}

	// ======================================================================
	// Recuperar contraseña desde el login (11-11-2026, copia de Login_model de mipdv_web).
	// Sin sesión: firma HMAC con la apikey compartida. Solo cambia el id_software (RESTO_ID_SOFTWARE).
	// ======================================================================

	/**
	 * ¿El correo tiene acceso a dat@Resto (id_software=24) en la intranet? Se consulta antes de enviar el código.
	 * @return array ok(bool: la consulta respondió), existe(bool), mensaje(string)
	 */
	public function existeCorreoAppWeb($correo)
	{
		$cfg = $this->_appWebLoginCfg();
		$correo = strtolower(trim((string)$correo));
		$idSoftware = (int)RESTO_ID_SOFTWARE;
		if ((string)$cfg['apikey'] === '') {
			log_message('error', '[MLOGIN][existeCorreoAppWeb] MIPDV_APP_WEB_LOGIN_APIKEY vacía: no se puede firmar.');
			return array('ok' => false, 'existe' => false, 'mensaje' => 'La recuperación de contraseña no está disponible. Escríbenos a Datamaule para ayudarte.');
		}
		$ts = (string)time();
		$body = array(
			'correo'      => $correo,
			'id_software' => $idSoftware,
			'apikey'      => (string)$cfg['apikey'],
			'ts'          => $ts,
			'sig'         => strtolower(hash_hmac('sha256', implode('|', array('existe_correo', (string)$idSoftware, $correo, $ts)), (string)$cfg['apikey'])),
		);
		$resp = $this->_postJson($this->_appWebLoginUrlFor('existe_correo'), $body);
		$dec  = is_array($resp['decoded']) ? $resp['decoded'] : array();
		$http = (int)$resp['http_code'];
		if ($http === 200 && !empty($dec['ok'])) {
			return array('ok' => true, 'existe' => !empty($dec['existe']), 'mensaje' => '');
		}
		log_message('error', sprintf('[MLOGIN][existeCorreoAppWeb][FAIL] http=%d cerr=%d code=%s',
			$http, (int)$resp['curl_errno'], (string)($dec['code'] ?? '')));
		return array('ok' => false, 'existe' => false, 'mensaje' => 'No fue posible validar el correo en este momento. Intenta nuevamente en unos minutos.');
	}

	/**
	 * Aplica la contraseña nueva en la intranet (api/AppWebLogin/restablecer_clave). La firma incluye sha256 de la clave.
	 * @return array ok(bool), filas(int), mensaje(string)
	 */
	public function restablecerClaveAppWeb($correo, $claveNueva)
	{
		$cfg = $this->_appWebLoginCfg();
		$correo = strtolower(trim((string)$correo));
		$claveNueva = (string)$claveNueva;
		$idSoftware = (int)RESTO_ID_SOFTWARE;
		if ((string)$cfg['apikey'] === '') {
			log_message('error', '[MLOGIN][restablecerClaveAppWeb] MIPDV_APP_WEB_LOGIN_APIKEY vacía: no se puede firmar.');
			return array('ok' => false, 'filas' => 0, 'mensaje' => 'La recuperación de contraseña no está disponible. Escríbenos a Datamaule para ayudarte.');
		}
		$ts = (string)time();
		$payload = implode('|', array('restablecer_clave', (string)$idSoftware, $correo, $ts, hash('sha256', $claveNueva)));
		$body = array(
			'correo'         => $correo,
			'password_nuevo' => $claveNueva,
			'id_software'    => $idSoftware,
			'apikey'         => (string)$cfg['apikey'],
			'ts'             => $ts,
			'sig'            => strtolower(hash_hmac('sha256', $payload, (string)$cfg['apikey'])),
		);
		$resp = $this->_postJson($this->_appWebLoginUrlFor('restablecer_clave'), $body);
		$dec  = is_array($resp['decoded']) ? $resp['decoded'] : array();
		$http = (int)$resp['http_code'];
		if ($http === 200 && !empty($dec['ok'])) {
			$filas = (int)($dec['data']['filas_afectadas'] ?? 0);
			log_message('info', sprintf('[MLOGIN][restablecerClaveAppWeb][OK] correo=%s filas=%d dur_ms=%d', $correo, $filas, (int)$resp['dur_ms']));
			return array('ok' => true, 'filas' => $filas, 'mensaje' => '');
		}
		$msg = (string)($dec['mensaje'] ?? '');
		log_message('error', sprintf('[MLOGIN][restablecerClaveAppWeb][FAIL] correo=%s http=%d cerr=%d code=%s msg="%s"',
			$correo, $http, (int)$resp['curl_errno'], (string)($dec['code'] ?? ''), substr($msg, 0, 200)));
		if ($http === 400 && $msg !== '') {
			return array('ok' => false, 'filas' => 0, 'mensaje' => $msg);
		}
		return array('ok' => false, 'filas' => 0, 'mensaje' => 'No fue posible actualizar la contraseña en este momento. Intenta nuevamente en unos minutos.');
	}

	// ======================================================================
	// Base de la empresa (sin cambios)
	// ======================================================================

	public function getNombreUsuario($idUsuario){
		if (strlen((string)$this->session->userdata('rut_cliente'))>0) {
			$this->load->helper('tenant'); $this->db = get_tenant_db($this);
			$query = $this->db->query("select * from usuario where id_usuario = ?", array((int)$idUsuario));
			return $query->row();

		}
	}
}
