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
 *
 * Pedidos a mesas (14-11-2026). Usan las mismas funciones de la Caja web (Mcaja), así que un pedido de la app queda
 * igual que uno hecho en la caja: la mesa se ocupa, aparece en la pestaña Mesas con su total y la comanda sale en
 * cocina/barra según el destino de impresión de cada producto. Necesitan conexión (no son offline).
 *   GET  index.php/ApiMovil/mesas                      mesas con estado libre / ocupada / bloqueada
 *   GET  index.php/ApiMovil/mesa?id_mesa=N             pedido abierto de la mesa (líneas y total)
 *   POST index.php/ApiMovil/pedidoMesa                 {uuid, id_mesa, id_garzon?, clave?, id_lprecio?,
 *                                                       detalle: [{id_producto, cantidad, agregados?: [id], nota?}]}
 *        uuid: lo genera la app por pedido y lo repite si reintenta (no se duplica).
 *        id_garzon + clave: quién atiende (se valida la clave del usuario); sin ellos, el usuario del login.
 */
class ApiMovil extends CI_Controller
{
	// Mismo id_software que usa el login web (Login::new_user), ver config/constants.php
	const ID_SOFTWARE = RESTO_ID_SOFTWARE;
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
		// Solo texto: un arreglo en correo/clave provocaba TypeError en trim() o "Array to string conversion"
		$correo = isset($body['correo']) && is_scalar($body['correo']) ? trim((string)$body['correo']) : '';
		$clave = isset($body['clave']) && is_scalar($body['clave']) ? (string)$body['clave'] : '';
		if ($correo === '' || $clave === '') {
			return $this->error(400, 'Correo y clave son obligatorios');
		}

		$usuario = $this->MapiMovil->validarUsuario($correo, $clave, self::ID_SOFTWARE);
		if (!$usuario) {
			return $this->error(401, 'Los datos introducidos son incorrectos');
		}
		if (isset($usuario['error'])) {
			return $this->error(isset($usuario['status']) ? $usuario['status'] : 503, $usuario['error']);
		}

		$payload = array(
			'con' => $usuario['nombre_conexion'],
			// Servidor (clave del diccionario) y base de la empresa, entregados por la intranet
			'srv' => $usuario['db_server'],
			'bd' => $usuario['bd_name'],
			'uid' => $usuario['id_usuario'],
			'cid' => $usuario['id_cliente'],
			'exp' => time() + self::DURACION_TOKEN,
		);
		$token = $this->firmarToken($payload);
		if ($token === null) {
			return $this->error(500, 'Falta configurar api_movil_secret en application/config/api_movil.php');
		}

		unset($usuario['db_server'], $usuario['bd_name']);   // ya van dentro del token; no se repiten en la respuesta
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
	// Pedidos a mesas (14-11-2026)

	public function mesas()
	{
		$sesion = $this->requerirToken();
		if (!$sesion) return;
		$this->conMesas($sesion, function () {
			$r = $this->Mcaja->mesas();
			if (empty($r['habilitado'])) throw new Exception('Este local no usa mesas (Configuraciones > modo de la caja)', 409);
			return array('mesas' => $r['mesas'], 'sectores' => $r['sectores']);
		});
	}

	public function mesa()
	{
		$sesion = $this->requerirToken();
		if (!$sesion) return;
		$this->conMesas($sesion, function () {
			$id = (int)$this->input->get('id_mesa');
			if ($id <= 0) throw new Exception('Falta id_mesa', 400);
			return array('mesa' => $this->Mcaja->detalleMesa($id), 'nombre' => $this->Mcaja->etiquetaMesa($id));
		});
	}

	public function pedidoMesa()
	{
		if ($this->input->method() !== 'post') {
			return $this->error(405, 'Método no permitido');
		}
		$sesion = $this->requerirToken();
		if (!$sesion) return;
		$body = $this->leerBody();
		$this->conMesas($sesion, function ($db) use ($sesion, $body) {
			$uuid = isset($body['uuid']) && is_scalar($body['uuid']) ? trim((string)$body['uuid']) : '';
			if (strlen($uuid) < 8 || strlen($uuid) > 80) throw new Exception('Falta el uuid del pedido (lo genera la app y lo repite si reintenta)', 400);
			$idMesa = (int)(isset($body['id_mesa']) ? $body['id_mesa'] : 0);
			if ($idMesa <= 0) throw new Exception('Falta id_mesa', 400);
			if (empty($body['detalle']) || !is_array($body['detalle']) || count($body['detalle']) > 200) {
				throw new Exception('El pedido no tiene productos (o tiene más de 200 líneas)', 400);
			}

			// Quién atiende: el garzón elegido en la app (con su clave) o el usuario del login
			$idGarzon = (int)$sesion['uid'];
			if (!empty($body['id_garzon'])) {
				$idGarzon = (int)$body['id_garzon'];
				$claveGarzon = isset($body['clave']) && is_scalar($body['clave']) ? (string)$body['clave'] : '';
				$g = $db->query('SELECT clave FROM usuario WHERE id_usuario = ? AND vigente IS NOT FALSE', array($idGarzon))->row();
				if (!$g || $claveGarzon === '' || !hash_equals((string)$g->clave, $claveGarzon)) throw new Exception('Garzón o clave incorrectos', 403);
			}
			$u = $db->query('SELECT id_usuario, COALESCE(id_rol, 0) AS id_rol FROM usuario WHERE id_usuario = ? AND vigente IS NOT FALSE', array($idGarzon))->row();
			if (!$u) throw new Exception('El usuario no existe o no está vigente en la empresa', 403);

			$clave = 'pedido:' . $uuid;
			// Dos envíos del mismo pedido a la vez: el segundo espera al primero y luego lo ve registrado
			$db->query('SELECT pg_advisory_lock(hashtext(?))', array($clave));
			try {
				$hecho = $db->query('SELECT id_web FROM movil_sync WHERE clave = ?', array($clave))->row();
				if ($hecho) {
					return array('id_cotizacion' => (int)$hecho->id_web, 'duplicado' => true, 'comandas' => array(),
						'mesa' => $this->Mcaja->detalleMesa($idMesa, false));
				}
				$p = array(
					'id_mesa' => $idMesa,
					'id_lprecio' => isset($body['id_lprecio']) ? (int)$body['id_lprecio'] : 0,
					'detalle' => $body['detalle'],
					'desde_app' => true,
				);
				$r = $this->Mcaja->enviarMesa(null, $idGarzon, $p, (int)$u->id_rol);
				$db->query("INSERT INTO movil_sync (clave, tipo, id_web) VALUES (?, 'pedido', ?)", array($clave, (int)$r['id_cotizacion']));
			} finally {
				$db->query('SELECT pg_advisory_unlock(hashtext(?))', array($clave));
			}
			return array('id_cotizacion' => (int)$r['id_cotizacion'], 'duplicado' => false,
				'comandas' => $this->imprimirComandaMesa($r['comanda'], $idMesa), 'mesa' => $r['detalle']);
		});
	}

	/**
	 * Prepara la "sesión" que usan Mcaja y Mcola_impresion (get_tenant_db lee la base de la sesión), ejecuta y
	 * responde JSON. La sesión se destruye al final: la API no guarda estado.
	 */
	private function conMesas($sesion, $accion)
	{
		try {
			$this->load->library('ConexionesTenant');
			$par = (!empty($sesion['srv']) && !empty($sesion['bd'])) ? array('srv' => $sesion['srv'], 'bd' => $sesion['bd'])
				: $this->conexionestenant->buscar($sesion['con']);
			if (!$par) throw new Exception('No se encontró la base de la empresa', 503);
			$this->session->set_userdata(array(
				'nombre_conexion' => $sesion['con'], 'db_server' => $par['srv'], 'bd_name' => $par['bd'],
				'id_usuario' => (int)$sesion['uid'], 'nombre_usuario' => 'APP MOVIL',
			));
			$this->load->helper('tenant');
			$db = get_tenant_db($this);
			$db->db_debug = FALSE;
			$this->load->model('Mcaja');
			$movil = $db->query("SELECT to_regclass('public.movil_sync') IS NOT NULL AS hay")->row();
			if (!$movil || ($movil->hay !== 't' && $movil->hay !== true)) {
				throw new Exception('Falta la tabla movil_sync: corre las migraciones (php index.php despliegue esquema)', 409);
			}
			$res = $accion($db);
			$this->session->sess_destroy();
			$this->responder(array('ok' => true) + $res);
		} catch (AutorizacionRequerida $e) {
			$this->session->sess_destroy();
			$this->error(403, $e->getMessage() . ' (se necesita autorización de un administrador: hazlo desde la caja)');
		} catch (Exception $e) {
			$this->session->sess_destroy();
			$code = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
			$this->error($code, $e->getMessage());
		} catch (Throwable $e) {
			$this->session->sess_destroy();
			log_message('error', 'ApiMovil mesas: ' . $e->getMessage() . ' en ' . basename($e->getFile()) . ':' . $e->getLine());
			$this->error(500, 'Error interno del servidor');
		}
	}

	/**
	 * Comanda del pedido a cocina/barra: lo mismo que Caja::imprimirComanda (agrupa por destino de impresión;
	 * con un agente de Windows activo va a la cola, si no el servidor la manda directo a la IP o ruta).
	 */
	private function imprimirComandaMesa($lineas, $idMesa)
	{
		if (!is_array($lineas) || !$lineas) return array();
		$texto = function ($v, $max) { return mb_substr(trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string)$v)), 0, $max); };
		$limpias = array();
		foreach ($lineas as $l) {
			if (empty($l['id_producto'])) continue;
			$ag = array();
			foreach ((isset($l['agregados']) && is_array($l['agregados'])) ? $l['agregados'] : array() as $a) {
				$a = $texto($a, 80);
				if ($a !== '') $ag[] = $a;
			}
			$limpias[] = array('id_producto' => (int)$l['id_producto'], 'nombre' => $texto(isset($l['nombre']) ? $l['nombre'] : '', 120),
				'cantidad' => max(0, (float)(isset($l['cantidad']) ? $l['cantidad'] : 0)), 'agregados' => $ag,
				'nota' => $texto(isset($l['nota']) ? $l['nota'] : '', 200));
		}
		$destinos = $this->Mcaja->destinosComanda(array_column($limpias, 'id_producto'));
		$grupos = array();
		foreach ($limpias as $l) {
			if (!isset($destinos[$l['id_producto']])) continue;
			$d = $destinos[$l['id_producto']];
			if (!isset($grupos[$d['id']])) $grupos[$d['id']] = array('nombre' => $d['nombre'], 'destino' => $d, 'lineas' => array());
			$grupos[$d['id']]['lineas'][] = $l;
		}
		if (!$grupos) return array();
		$this->load->library('Comanda_impresion');
		$this->load->model('Mcola_impresion');
		$this->Mcola_impresion->conectar(null, false);
		$conAgente = $this->Mcola_impresion->hayAgenteActivo();
		$mesa = $this->Mcaja->etiquetaMesa((int)$idMesa);
		$res = array();
		foreach ($grupos as $g) {
			try {
				$bytes = $this->comanda_impresion->armar($g, null, 'Pedido desde la app', $mesa, null);
				if ($conAgente) {
					$d = $g['destino'];
					$ipOk = Comanda_impresion::ipPermitida(trim((string)$d['ip']), $d['puerto']);
					$this->Mcola_impresion->encolar($g['nombre'], Mcola_impresion::nombreImpresora($d['ruta_windows']),
						$ipOk ? trim((string)$d['ip']) : '', $ipOk ? (int)$d['puerto'] : 0, $bytes);
					$ok = true;
					$detalle = 'en cola para el agente';
				} else {
					list($ok, $detalle) = $this->comanda_impresion->enviar($g['destino'], $bytes);
				}
			} catch (Throwable $e) {
				$ok = false;
				$detalle = $e->getMessage();
			}
			if (!$ok) log_message('error', 'ApiMovil comanda mesa ' . (int)$idMesa . ' a ' . $g['nombre'] . ': ' . $detalle);
			$res[] = array('destino' => $g['nombre'], 'ok' => (bool)$ok, 'detalle' => $detalle);
		}
		return $res;
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
		// Tokens nuevos traen servidor y base (firmados); los anteriores se resuelven con el diccionario local
		if (!empty($payload['con']) && !empty($payload['srv']) && !empty($payload['bd'])) {
			$this->load->library('ConexionesTenant');
			$this->conexionestenant->recordar($payload['con'], $payload['srv'], $payload['bd']);
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
