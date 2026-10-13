<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * API para la app móvil de caja (React Native).
 *
 * No usa la sesión de CodeIgniter: el login entrega un token firmado (HMAC)
 * que contiene la conexión del cliente, y la app lo manda en cada request
 * con el header "X-Api-Token" (o "Authorization: Bearer <token>").
 *
 * Endpoints (todos responden JSON):
 *   POST index.php/ApiMovil/login      {correo, clave}
 *   GET  index.php/ApiMovil/yo
 *   GET  index.php/ApiMovil/catalogo   ?version=<hash>&solo_moviles=1
 *   POST index.php/ApiMovil/subir      {caja, movimientos, ventas, cierre?}
 *   GET  index.php/ApiMovil/ping
 */
class ApiMovil extends CI_Controller
{
	// Mismo id_software que usa el login web (Login::new_user)
	const ID_SOFTWARE = 5;
	const DURACION_TOKEN = 2592000; // 30 días: la caja trabaja offline

	function __construct()
	{
		parent::__construct();
		$this->config->load('api_movil', TRUE, TRUE);
		$this->load->model('MapiMovil');
	}

	public function ping()
	{
		$this->responder(array('ok' => true, 'servidor' => 'reportesResto', 'hora' => date('c')));
	}

	public function login()
	{
		if ($this->input->method() !== 'post') {
			return $this->error(405, 'Método no permitido');
		}
		$body = $this->leerBody();
		$correo = isset($body['correo']) ? trim($body['correo']) : '';
		$clave = isset($body['clave']) ? (string)$body['clave'] : '';
		if ($correo === '' || $clave === '') {
			return $this->error(400, 'Correo y clave son obligatorios');
		}

		$usuario = $this->MapiMovil->validarUsuario($correo, $clave, self::ID_SOFTWARE);
		if (!$usuario) {
			return $this->error(401, 'Los datos introducidos son incorrectos');
		}

		$payload = array(
			'con' => $usuario['nombre_conexion'],
			'uid' => $usuario['id_usuario'],
			'cid' => $usuario['id_cliente'],
			'exp' => time() + self::DURACION_TOKEN,
		);
		$token = $this->firmarToken($payload);
		if ($token === null) {
			return $this->error(500, 'Falta configurar api_movil_secret en application/config/api_movil.php');
		}

		$this->responder(array(
			'ok' => true,
			'token' => $token,
			'expira' => date('c', $payload['exp']),
			'usuario' => $usuario,
		));
	}

	public function yo()
	{
		$sesion = $this->requerirToken();
		if (!$sesion) return;
		$this->responder(array('ok' => true, 'sesion' => $sesion));
	}

	public function catalogo()
	{
		$sesion = $this->requerirToken();
		if (!$sesion) return;

		$soloMoviles = $this->input->get('solo_moviles') === '1';
		$datos = $this->MapiMovil->catalogo($sesion['con'], $soloMoviles);
		if (isset($datos['error'])) {
			return $this->error(500, $datos['error']);
		}

		// La app manda la versión que ya tiene; si no cambió nada evitamos reenviar todo
		$version = md5(json_encode($datos));
		if ($this->input->get('version') === $version) {
			return $this->responder(array('ok' => true, 'sin_cambios' => true, 'version' => $version));
		}

		$this->responder(array(
			'ok' => true,
			'sin_cambios' => false,
			'version' => $version,
			'generado' => date('c'),
			'datos' => $datos,
		));
	}

	/**
	 * Recibe una caja del teléfono con sus movimientos, un lote de ventas y,
	 * si la caja ya se cerró, el cierre. Es idempotente: reenviar lo mismo no duplica.
	 */
	public function subir()
	{
		if ($this->input->method() !== 'post') {
			return $this->error(405, 'Método no permitido');
		}
		$sesion = $this->requerirToken();
		if (!$sesion) return;

		$body = $this->leerBody();
		if (!isset($body['caja']['uuid']) || !is_string($body['caja']['uuid'])) {
			return $this->error(400, 'Falta la caja');
		}
		$ventas = isset($body['ventas']) && is_array($body['ventas']) ? $body['ventas'] : array();
		if (count($ventas) > 100) {
			return $this->error(400, 'Máximo 100 ventas por envío');
		}

		$r = $this->MapiMovil->subir($sesion['con'], $body, array(
			'zona' => $this->config->item('api_movil_zona', 'api_movil'),
			'id_tdocumento' => $this->config->item('api_movil_id_tdocumento', 'api_movil'),
		));
		if (isset($r['error'])) {
			return $this->error(isset($r['status']) ? $r['status'] : 500, $r['error']);
		}
		$r['ok'] = true;
		$this->responder($r);
	}

	// ---------------------------------------------------------------------

	private function requerirToken()
	{
		$token = $this->input->get_request_header('X-Api-Token', TRUE);
		if (!$token) {
			$auth = $this->input->get_request_header('Authorization', TRUE);
			if ($auth && stripos($auth, 'Bearer ') === 0) {
				$token = trim(substr($auth, 7));
			}
		}
		$payload = $token ? $this->validarToken($token) : null;
		if (!$payload) {
			$this->error(401, 'Sesión inválida o expirada');
			return null;
		}
		return $payload;
	}

	private function secreto()
	{
		$secreto = $this->config->item('api_movil_secret', 'api_movil');
		return (is_string($secreto) && strlen($secreto) >= 32) ? $secreto : null;
	}

	private function firmarToken($payload)
	{
		$secreto = $this->secreto();
		if ($secreto === null) return null;
		$cuerpo = $this->b64url(json_encode($payload));
		return $cuerpo . '.' . $this->b64url(hash_hmac('sha256', $cuerpo, $secreto, true));
	}

	private function validarToken($token)
	{
		$secreto = $this->secreto();
		$partes = explode('.', $token);
		if ($secreto === null || count($partes) !== 2) return null;

		$firma = $this->b64url(hash_hmac('sha256', $partes[0], $secreto, true));
		if (!hash_equals($firma, $partes[1])) return null;

		$payload = json_decode(base64_decode(strtr($partes[0], '-_', '+/')), true);
		if (!is_array($payload) || !isset($payload['exp'], $payload['con']) || $payload['exp'] < time()) {
			return null;
		}
		return $payload;
	}

	private function b64url($data)
	{
		return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
	}

	private function leerBody()
	{
		$json = json_decode($this->input->raw_input_stream, true);
		return is_array($json) ? $json : $this->input->post();
	}

	private function responder($data, $status = 200)
	{
		$this->output
			->set_status_header($status)
			->set_content_type('application/json', 'utf-8')
			->set_output(json_encode($data, JSON_UNESCAPED_UNICODE));
	}

	private function error($status, $mensaje)
	{
		$this->responder(array('ok' => false, 'error' => $mensaje), $status);
	}
}
