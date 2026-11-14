<?php
defined('BASEPATH') or exit('No direct script access allowed');


include(APPPATH . 'libraries/GroceryCrudEnterprise/autoload.php');

use GroceryCrud\Core\GroceryCrud;


class Login extends CI_Controller
{

	function __construct()
	{
		parent::__construct();
		$this->load->library(array('session', 'form_validation', 'parser'));
		$this->load->helper('url_helper');
		$this->load->model('Mlogin');
	}

	public function index()
	{
		if ($this->session->userdata('nombre_usuario')) {
			redirect(base_url() . 'index.php/Dashboard');
		} else {
			$this->load->view('login/login.php');
		}
	}

	/**
	 * SOLO DESARROLLO (13-11-2026): entrar sin la intranet, eligiendo un usuario de la base local.
	 *   http://localhost:8001/index.php/login/dev
	 * Solo responde si CI_ENV=development y la petición viene de este mismo computador; si no, 404.
	 * 14-11-2026: RESTO_DEV_IPS (.env.php) agrega otras IP exactas de la red local, separadas por coma
	 * (ej. RESTO_DEV_IPS=192.168.100.143). Solo IP privadas; sin la variable, solo este computador.
	 * Base: RESTO_DEV_SERVIDOR / RESTO_DEV_BASE / RESTO_DEV_CONEXION del .env.php (por defecto localhost / laToscana).
	 * El login normal (new_user) no cambia.
	 */
	public function dev()
	{
		$ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
		$permitidas = array('127.0.0.1', '::1');
		foreach (explode(',', (string)getenv('RESTO_DEV_IPS')) as $extra) {
			$extra = trim($extra);
			// Solo IP privadas (192.168.x.x, 10.x.x.x, 172.16-31.x.x): la entrada sin clave nunca se abre a internet
			if ($extra !== '' && filter_var($extra, FILTER_VALIDATE_IP) && !filter_var($extra, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
				$permitidas[] = $extra;
			}
		}
		if (ENVIRONMENT !== 'development' || !in_array($ip, $permitidas, true)) {
			show_404();
			return;
		}
		$srv = getenv('RESTO_DEV_SERVIDOR') ?: 'localhost';
		$bd = getenv('RESTO_DEV_BASE') ?: 'laToscana';
		$conexion = getenv('RESTO_DEV_CONEXION') ?: '76193938-6';

		$this->session->set_userdata(array('db_server' => $srv, 'bd_name' => $bd, 'nombre_conexion' => $conexion));
		$this->load->helper('tenant');
		$db = get_tenant_db($this);
		$usuarios = $db->query('SELECT id_usuario, nombre_usuario, id_rol, vigente FROM usuario ORDER BY id_usuario')->result();

		if ($this->input->method() === 'post') {
			$token = (string)$this->session->userdata('dev_login_token');
			if ($token === '' || !hash_equals($token, (string)$this->input->post('token'))) {
				show_error('Token inválido, vuelve a abrir la página.', 403);
				return;
			}
			$id = (int)$this->input->post('id_usuario');
			$u = null;
			foreach ($usuarios as $fila) {
				if ((int)$fila->id_usuario === $id) $u = $fila;
			}
			if (!$u) {
				show_error('Usuario no encontrado en la base local.', 404);
				return;
			}
			$partes = explode('-', $conexion);
			$this->session->sess_regenerate(TRUE);
			$this->session->set_userdata(array(
				'nombre_conexion' => $conexion,
				'rut_cliente' => $conexion,
				'rut_cliente_intranet' => $this->formatearRut((int)$partes[0], $partes[1] ?? ''),
				'db_server' => $srv,
				'bd_name' => $bd,
				'id_cliente' => 0,
				'id_sucursal' => 0,
				'cajas_intranet' => array(),
				'cajas_intranet_sinc' => 1,
				'abreviatura_software' => 'RESTO',
				'is_logued_in' => TRUE,
				'id_usuario' => (int)$u->id_usuario,
				'nombre_usuario' => trim((string)$u->nombre_usuario) !== '' ? $u->nombre_usuario : 'DEV',
				'nombre_cliente' => $bd . ' (desarrollo)',
				'correo_acceso' => 'dev@localhost',
				'id_software' => RESTO_ID_SOFTWARE,
				'autoriza_cambio_conexion' => 'f',
				'lista_conexiones' => array('nombre_conexion' => $conexion),
				'id_rol' => (int)$u->id_rol,
			));
			$this->session->unset_userdata('dev_login_token');
			$this->aplicarDefectos();
			redirect(base_url() . 'index.php/Dashboard');
			return;
		}

		$token = bin2hex(random_bytes(16));
		$this->session->set_userdata('dev_login_token', $token);
		$h = function ($t) { return htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8'); };
		echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Login desarrollo</title>'
			. '<style>body{font-family:sans-serif;max-width:520px;margin:40px auto;padding:0 16px}'
			. 'button{display:block;width:100%;margin:6px 0;padding:10px;text-align:left;cursor:pointer}'
			. '.aviso{background:#fff3cd;padding:10px;border-radius:6px}</style></head><body>'
			. '<h2>Login de desarrollo</h2><p class="aviso">Solo local. Base: <b>' . $h($srv . ' / ' . $bd) . '</b>. Elige un usuario:</p>';
		foreach ($usuarios as $u) {
			echo '<form method="post"><input type="hidden" name="token" value="' . $h($token) . '">'
				. '<input type="hidden" name="id_usuario" value="' . (int)$u->id_usuario . '">'
				. '<button>#' . (int)$u->id_usuario . ' · ' . $h($u->nombre_usuario) . ' · rol ' . (int)$u->id_rol
				. ($this->esVerdadero($u->vigente) ? '' : ' · (no vigente)') . '</button></form>';
		}
		echo '</body></html>';
	}



	/**
	 * Login por la API de la intranet (10-11-2026), igual que mipdv_web:
	 *   1) AppWebLogin/index valida usuario y clave para RESTO_ID_SOFTWARE y entrega un Bearer.
	 *   2) validarCliente entrega el servidor y la base de la empresa (con / con_sucursal).
	 *   3) La sesión guarda db_server + bd_name; get_tenant_db() abre la base con DbManager.
	 * Ninguna conexión a la base de la intranet.
	 */
	public function new_user(){
		$username = trim((string)$this->input->post('username'));
		$password = (string)$this->input->post('password');

		$idSoftware = RESTO_ID_SOFTWARE;
		$check_user = $this->Mlogin->login_user($username, $password, $idSoftware);

		if ($check_user == TRUE) {
			$rutCliente = $this->formatearRut($check_user->rut, $check_user->dv);
			// Id de sesión nuevo al entrar: una sesión creada antes del login (ej. un enlace con un id puesto por otro) no sirve
			$this->session->sess_regenerate(TRUE);

			// Sucursal con la que parte: la del usuario en la intranet o, si no tiene, la por defecto del cliente
			$idSucursal = $this->sucursalInicio($check_user);

			// Servidor y base de la empresa, desde la intranet
			$this->load->library('ConexionesTenant');
			$con = $this->conexionestenant->resolverDesdeIntranet($rutCliente, $idSucursal, $idSoftware, $check_user->abreviatura);
			if (!$con['ok']) {
				$this->falloConexion($con['motivo']);
				return;
			}
			$this->conexionestenant->guardar($check_user->nombre_conexion, $con['db_server'], $con['bd_name']);

			$data = array(
				'nombre_conexion' => $check_user->nombre_conexion,
				'rut_cliente' => $rutCliente,
				'db_server' => $con['db_server'],
				'bd_name' => $con['bd_name'],
				'id_cliente' => (int)$check_user->id_cliente,
				'id_sucursal' => $idSucursal,
				// Cajas autorizadas en la intranet: la Caja y Control de cajas las guardan en control_caja (Mcontrolcaja)
				'cajas_intranet' => $con['cajas'],
				'cajas_intranet_sinc' => 0,
				'abreviatura_software' => (string)$check_user->abreviatura,
				'rut_cliente_intranet' => $rutCliente,
			);
			$this->session->set_userdata($data);

			// Obtener datos del usuario (base de la empresa)
			$datosUsuario = $this->Mlogin->getNombreUsuario($check_user->id_usuario_cliente);

			// Obtener id_rol de forma segura
			$rolUsuario = isset($datosUsuario->id_rol) ? $datosUsuario->id_rol : 0;

			$idCliente = $con['id_cliente_intranet'] > 0 ? (int)$con['id_cliente_intranet'] : (int)$check_user->id_cliente;
			if ($this->esVerdadero($check_user->autoriza_cambio_conexion)) {
				$listaConexiones = $this->Mlogin->obtenerListaConexionesUsuario($idCliente);
			} else {
				$listaConexiones = array('nombre_conexion' => $check_user->nombre_conexion);
			}

			// Usar el id_rol obtenido correctamente
			$data = array(
				'is_logued_in' => TRUE,
				'id_usuario' => $check_user->id_usuario_cliente,
				'rut_cliente' => $check_user->rut . "-" . $check_user->dv,
				'nombre_usuario' => $this->nombreUsuario($datosUsuario, $check_user, $username),
				'nombre_conexion' => $check_user->nombre_conexion,
				'nombre_cliente' => $check_user->nombre_cliente,
				'correo_acceso' => $username,
				'id_software' => $idSoftware,
				'autoriza_cambio_conexion' => $check_user->autoriza_cambio_conexion,
				'lista_conexiones' => $listaConexiones,
				'id_rol' => $rolUsuario  // Usar la variable correcta
			);
			$this->session->set_userdata($data);
			$this->aplicarDefectos();
			$this->index();

		} else {
			$this->session->set_flashdata('usuario_incorrecto', $this->Mlogin->mensajeUltimoFallo());
			redirect(base_url());
		}
	}

		public function relogin(){
			// Cambio de empresa desde el menú: solo con sesión iniciada y hacia una empresa del mismo correo
			// (antes bastaba un POST con cualquier correo, sin clave, y se podía elegir cualquier conexión)
			if (!$this->session->userdata('is_logued_in')) {
				redirect(base_url());
			}
			$username = (string)$this->session->userdata('correo_acceso');
			if ((string)$this->input->post('username') !== $username) {
				redirect(base_url());
			}
			$idEmpresa = (int)$this->input->post('empresa');
			$nombreConexion = trim((string)$this->input->post('nConexion'));
			$idSoftware = RESTO_ID_SOFTWARE;

			// Si algo falla se deja la sesión como estaba (incluido el Bearer de la empresa actual)
			$bearerAntes = array(
				'awl_bearer_access_token' => $this->session->userdata('awl_bearer_access_token'),
				'awl_bearer_issued_at' => $this->session->userdata('awl_bearer_issued_at'),
				'awl_bearer_expires_at' => $this->session->userdata('awl_bearer_expires_at'),
			);

			$check_user = $this->Mlogin->relogin_user($idEmpresa,$username,$idSoftware);

			if ($check_user == TRUE) {
				$rutCliente = $this->formatearRut($check_user->rut, $check_user->dv);
				$idSucursal = $this->sucursalInicio($check_user);

				$this->load->library('ConexionesTenant');
				$con = $this->conexionestenant->resolverDesdeIntranet($rutCliente, $idSucursal, $idSoftware, $check_user->abreviatura);
				if (!$con['ok']) {
					if (!empty($bearerAntes['awl_bearer_access_token'])) {
						$this->session->set_userdata($bearerAntes);
					}
					log_message('error', 'Login::relogin sin conexión para la empresa ' . $idEmpresa . ': ' . $con['motivo']);
					$this->session->set_flashdata('usuario_incorrecto', 'No se pudo obtener la base de datos de la empresa seleccionada. Intente nuevamente.');
					redirect(base_url());
					return;
				}
				$this->conexionestenant->guardar($check_user->nombre_conexion, $con['db_server'], $con['bd_name']);

				$idCliente = $con['id_cliente_intranet'] > 0 ? (int)$con['id_cliente_intranet'] : (int)$check_user->id_cliente;
				if ($this->esVerdadero($check_user->autoriza_cambio_conexion)) {
					$listaConexiones =$this->Mlogin->obtenerListaConexionesUsuario($idCliente);
				}else {
					$listaConexiones = array('nombre_conexion' =>$check_user->nombre_conexion);
				}

				// Conexión elegida en el menú (si es una de las del usuario y el diccionario la conoce); si no, la de la intranet
				$nombreFinal = $check_user->nombre_conexion;
				$dbServer = $con['db_server'];
				$bdName = $con['bd_name'];
				if ($nombreConexion !== '' && $nombreConexion !== $check_user->nombre_conexion && $this->conexionPermitida($nombreConexion, $listaConexiones)) {
					$par = $this->conexionestenant->buscar($nombreConexion);
					if ($par !== null) {
						$nombreFinal = $nombreConexion;
						$dbServer = $par['srv'];
						$bdName = $par['bd'];
					}
				}

				$data = array(
					'nombre_conexion'   =>    $nombreFinal,
					'rut_cliente'   =>    $rutCliente,
					'db_server' => $dbServer,
					'bd_name' => $bdName,
					'id_cliente' => (int)$check_user->id_cliente,
					'id_sucursal' => $idSucursal,
					// Cajas autorizadas en la intranet de la empresa elegida (ver new_user)
					'cajas_intranet' => $con['cajas'],
					'cajas_intranet_sinc' => 0,
					'abreviatura_software' => (string)$check_user->abreviatura,
					'rut_cliente_intranet' => $rutCliente,
				);
				$this->session->set_userdata($data);

				$datosUsuario=$this->Mlogin->getNombreUsuario($check_user->id_usuario_cliente);
				// El rol es el del usuario en la base de la empresa (el id_rol que trae la API es de la intranet)
				$rolUsuario = isset($datosUsuario->id_rol) ? $datosUsuario->id_rol : 0;

				$data = array(
					'is_logued_in'        => TRUE,
					'id_usuario'          => $check_user->id_usuario_cliente,
				  'rut_cliente'         => $check_user->rut . "-" . $check_user->dv,
					'nombre_usuario'      => $this->nombreUsuario($datosUsuario, $check_user, $username),
					'nombre_conexion'=> $nombreFinal,
					'nombre_cliente'			=> $check_user->nombre_cliente,
					'correo_acceso' =>$username,
					'id_software'=>$idSoftware,
					'autoriza_cambio_conexion'=> $check_user->autoriza_cambio_conexion,
					'lista_conexiones'=>$listaConexiones,
					'id_rol'              => $rolUsuario,
				);
				$this->session->set_userdata($data);
				$this->aplicarDefectos();
				$this->index();
			} else {
				if (!empty($bearerAntes['awl_bearer_access_token']) && $this->session->userdata('awl_bearer_access_token')) {
					$this->session->set_userdata($bearerAntes);
				}
				$this->session->set_flashdata('usuario_incorrecto', $this->Mlogin->mensajeUltimoFallo());
				redirect(base_url());
			}


		}

	/**
	 * Migraciones de la base de la empresa (application/migrations, ver migrations/LEEME_RESTO.md).
	 * La llama menuFooter por AJAX, en segundo plano, la primera vez que se abre una página con esta empresa
	 * (también después de cambiar de empresa): no bloquea el login ni lo que el usuario está mirando.
	 */
	public function postLoginWarmup()
	{
		if ($this->input->method() !== 'post' || strlen((string)$this->session->userdata('nombre_usuario')) === 0) {
			echo json_encode(array('ok' => false));
			return;
		}
		$esGerencia = (int)$this->session->userdata('id_rol') === 1;
		$this->load->library('MigracionesResto');
		$r = $this->migracionesresto->aplicarEnSesion();
		$salida = array('ok' => !empty($r['ok']), 'aplicadas' => count(isset($r['aplicadas']) ? $r['aplicadas'] : array()));
		// El detalle del error solo a gerencia (queda completo en application/logs/migraciones_AAAAMM.log)
		if ($esGerencia && empty($r['ok']) && !empty($r['error'])) {
			$salida['aviso'] = 'No se pudo actualizar la base de la empresa: ' . $r['error'];
		}
		echo json_encode($salida);
	}

	public function logout_ci()
	{
		$this->session->unset_userdata('nombre_usuario');
		$this->session->sess_destroy();
		redirect(base_url());
	}

	/** La conexión pedida está en la lista de conexiones del usuario (JSON [{nombre_conexion}] o arreglo). */
	private function conexionPermitida($nombre, $lista)
	{
		if ($nombre === '') return false;
		$items = is_string($lista) ? json_decode($lista, true) : (array)$lista;
		if (isset($items['nombre_conexion'])) $items = array($items);
		foreach ((array)$items as $it) {
			$it = (array)$it;
			if (isset($it['nombre_conexion']) && $it['nombre_conexion'] === $nombre) return true;
		}
		return false;
	}

	/** RUT con puntos y guion, con 0 a la izquierda si es menor a 10 millones (como siempre lo armó el login). */
	private function formatearRut($rut, $dv)
	{
		$rut = (int)$rut;
		$txt = number_format($rut, 0, ',', '.');
		if ($rut < 10000000) {
			$txt = "0" . $txt;
		}
		return $txt . "-" . $dv;
	}

	/** Sucursal de inicio: la del usuario en la intranet; si no tiene, la por defecto del cliente (API). */
	private function sucursalInicio($check_user)
	{
		$idSucursal = (int)($check_user->id_sucursal ?? 0);
		if ($idSucursal > 0) {
			return $idSucursal;
		}
		$suc = $this->Mlogin->obtenerSucursal($check_user->id_cliente);
		return ($suc && isset($suc->id_sucursal)) ? (int)$suc->id_sucursal : 0;
	}

	/** Nombre a mostrar: el del usuario en la base de la empresa; si no, el de la intranet; si no, el correo. */
	private function nombreUsuario($datosUsuario, $check_user, $correo)
	{
		foreach (array($datosUsuario->nombre_usuario ?? '', $check_user->nombre_usuario ?? '', $correo) as $n) {
			if (trim((string)$n) !== '') {
				return $n;
			}
		}
		return $correo;
	}

	/** Booleano de PostgreSQL tal como viaja en el JSON de la intranet ('t'/'f', true/false). */
	private function esVerdadero($v)
	{
		return $v === true || $v === 1 || in_array(strtolower(trim((string)$v)), array('t', 'true', '1'), true);
	}

	/** La intranet validó al usuario pero no entregó una base usable: se corta el login con un mensaje claro. */
	private function falloConexion($motivo)
	{
		$mensajes = array(
			'sin_credenciales_validar_cliente' => 'Falta configurar el acceso a validarCliente (validar_cliente_legacy_user / validar_cliente_legacy_pass).',
			'empresa_sin_conexion' => 'La intranet no tiene una base de datos registrada para esta empresa en este sistema.',
			'servidor_desconocido' => 'El servidor de base de datos de la empresa no está configurado en este sistema.',
		);
		// Sin sess_destroy(): el mensaje (flashdata) tiene que sobrevivir a la redirección
		$this->session->unset_userdata(array('awl_bearer_access_token', 'awl_bearer_issued_at', 'awl_bearer_expires_at'));
		$this->session->set_flashdata('usuario_incorrecto', isset($mensajes[$motivo]) ? $mensajes[$motivo] : 'No se pudo obtener la base de datos de la empresa desde la intranet. Intente nuevamente.');
		redirect(base_url());
	}

	/* =====================================================================
	 * RECUPERAR CONTRASEÑA DESDE EL LOGIN (11-11-2026), igual que mipdv_web:
	 *   1. desafioRecuperarClave         → pregunta anti-bot.
	 *   2. recuperarClaveEnviarCodigo    → el correo debe tener acceso a dat@Resto en la intranet;
	 *                                      código de 6 dígitos al correo.
	 *   3. recuperarClaveVerificarCodigo → valida el código en el servidor.
	 *   4. recuperarClaveGuardar         → contraseña nueva + repetición; la aplica la intranet
	 *                                      (api/AppWebLogin/restablecer_clave, firmado).
	 * El código nunca viaja al navegador (CodigoCorreo guarda solo su hash en la sesión).
	 * ===================================================================== */
	const RECUPERAR_CONSULTAS_MAX = 10;   // consultas de correo por sesión/IP cada 15 minutos
	const RECUPERAR_VENTANA_SEG = 900;

	public function desafioRecuperarClave()
	{
		$a = random_int(2, 9);
		$b = random_int(1, 9);
		$this->session->set_userdata('recuperar_clave_desafio', array('respuesta' => $a + $b, 'emitido' => time(), 'intentos' => 0));
		$this->rcJson(array('ok' => true, 'pregunta' => '¿Cuánto es ' . $a . ' + ' . $b . '?'));
	}

	public function recuperarClaveEnviarCodigo()
	{
		$correo = strtolower(trim((string)$this->input->post('correo')));
		$respuesta = trim((string)$this->input->post('verificacion'));
		$desafio = $this->session->userdata('recuperar_clave_desafio');

		// Campo trampa: las personas no lo ven; si viene con datos, es un bot.
		if (trim((string)$this->input->post('sitio_web')) !== '') {
			$this->rcJson(array('ok' => true, 'msg' => 'Te enviamos un código a tu correo.', 'reenvio_seg' => 60));
			return;
		}
		if (!is_array($desafio) || empty($desafio['emitido']) || (time() - (int)$desafio['emitido']) > 3600) {
			$this->rcJson(array('ok' => false, 'msg' => 'La verificación expiró. Responde la nueva pregunta.', 'nuevo_desafio' => true));
			return;
		}
		if ($respuesta === '' || !ctype_digit($respuesta) || (int)$respuesta !== (int)$desafio['respuesta']) {
			$desafio['intentos'] = (int)$desafio['intentos'] + 1;
			$this->session->set_userdata('recuperar_clave_desafio', $desafio);
			if ($desafio['intentos'] >= 3) {
				$this->rcJson(array('ok' => false, 'msg' => 'Respuesta incorrecta. Responde la nueva pregunta.', 'nuevo_desafio' => true, 'campo' => 'verificacion'));
				return;
			}
			$this->rcJson(array('ok' => false, 'msg' => 'La respuesta de la pregunta no es correcta.', 'campo' => 'verificacion'));
			return;
		}

		// Antes de enviar el código, el correo debe tener acceso a dat@Resto (RESTO_ID_SOFTWARE). Cada consulta
		// cuesta una pregunta anti-bot y hay un máximo por IP, para que no sirva para adivinar correos.
		if ($correo === '' || strlen($correo) > 100 || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
			$this->rcJson(array('ok' => false, 'msg' => 'Ingresa un correo válido.', 'campo' => 'correo'));
			return;
		}
		if (count($this->rcRegistros('recuperar_consultas')) >= self::RECUPERAR_CONSULTAS_MAX) {
			$this->rcJson(array('ok' => false, 'msg' => 'Demasiadas consultas. Intenta nuevamente en 15 minutos.'));
			return;
		}
		$this->rcRegistrar('recuperar_consultas');
		$existe = $this->Mlogin->existeCorreoAppWeb($correo);
		if (empty($existe['ok'])) {
			$this->rcJson(array('ok' => false, 'msg' => $existe['mensaje']));
			return;
		}
		if (empty($existe['existe'])) {
			$this->session->unset_userdata('recuperar_clave_desafio');
			$this->rcJson(array(
				'ok' => false,
				'no_existe' => true,
				'nuevo_desafio' => true,
				'campo' => 'correo',
				'msg' => 'Ese correo no tiene una cuenta de dat@Resto. Revisa que esté bien escrito o escríbenos a Datamaule.'
			));
			return;
		}

		$this->load->library('CodigoCorreo', null, 'codigocorreo');
		$this->rcJson($this->codigocorreo->enviar('recuperar_clave', $correo, 'Recuperar contraseña',
			'Usa este código para crear una nueva contraseña de dat@Resto:'));
	}

	public function recuperarClaveVerificarCodigo()
	{
		$this->load->library('CodigoCorreo', null, 'codigocorreo');
		$this->rcJson($this->codigocorreo->verificar('recuperar_clave', (string)$this->input->post('correo'), (string)$this->input->post('codigo')));
	}

	public function recuperarClaveGuardar()
	{
		$this->load->helper('clave_segura');
		$this->load->library('CodigoCorreo', null, 'codigocorreo');

		$correo = strtolower(trim((string)$this->input->post('correo')));
		$nueva = (string)$this->input->post('password_nuevo');
		$confirmacion = (string)$this->input->post('password_confirmacion');

		if (!$this->codigocorreo->estaVerificado('recuperar_clave', $correo)) {
			$this->rcJson(array('ok' => false, 'msg' => 'Tu verificación venció. Solicita un nuevo código.', 'codigo_expirado' => true));
			return;
		}
		$err = clave_segura_error($nueva);
		if ($err !== null) {
			$this->rcJson(array('ok' => false, 'msg' => $err . '.', 'campo' => 'password_nuevo'));
			return;
		}
		if ($nueva !== $confirmacion) {
			$this->rcJson(array('ok' => false, 'msg' => 'La repetición de la contraseña no coincide.', 'campo' => 'password_confirmacion'));
			return;
		}

		$r = $this->Mlogin->restablecerClaveAppWeb($correo, $nueva);
		if (empty($r['ok'])) {
			$this->rcJson(array('ok' => false, 'msg' => $r['mensaje']));
			return;
		}

		$this->codigocorreo->limpiar('recuperar_clave');
		$this->session->unset_userdata('recuperar_clave_desafio');

		if ((int)$r['filas'] <= 0) {
			$this->rcJson(array('ok' => false, 'msg' => 'No encontramos una cuenta de dat@Resto con ese correo. Si crees que es un error, escríbenos a Datamaule.', 'sin_cuenta' => true));
			return;
		}

		// Correo de confirmación del cambio: la contraseña ya quedó cambiada; si el correo falla solo se registra en el log.
		$avisoEnviado = $this->codigocorreo->enviarAvisoCambioClave($correo, 'recuperar_clave');
		$msg = 'Contraseña actualizada. Ya puedes iniciar sesión con tu nueva contraseña.';
		if ($avisoEnviado) {
			$msg .= ' Te enviamos un correo de confirmación.';
		}
		$this->rcJson(array('ok' => true, 'msg' => $msg, 'aviso_enviado' => $avisoEnviado));
	}

	/** Marcas de tiempo de los últimos 15 minutos para un tipo, combinando la sesión y un registro por IP. */
	private function rcRegistros($tipo)
	{
		$ahora = time();
		$registros = (array)$this->session->userdata('rc_' . $tipo);
		$archivo = APPPATH . 'cache/recuperar_clave_' . $tipo . '_' . md5((string)$this->input->ip_address()) . '.json';
		if (is_file($archivo)) {
			$porIp = json_decode((string)@file_get_contents($archivo), true);
			if (is_array($porIp)) {
				$registros = array_merge($registros, $porIp);
			}
		}
		$ventana = self::RECUPERAR_VENTANA_SEG;
		return array_values(array_unique(array_filter($registros, function ($t) use ($ahora, $ventana) {
			return is_numeric($t) && ($ahora - (int)$t) < $ventana;
		})));
	}

	private function rcRegistrar($tipo)
	{
		$registros = $this->rcRegistros($tipo);
		$registros[] = time();
		$this->session->set_userdata('rc_' . $tipo, $registros);
		@file_put_contents(APPPATH . 'cache/recuperar_clave_' . $tipo . '_' . md5((string)$this->input->ip_address()) . '.json', json_encode($registros), LOCK_EX);
	}

	private function rcJson(array $data)
	{
		$this->output->set_content_type('application/json', 'utf-8')->set_output(json_encode($data, JSON_UNESCAPED_UNICODE));
	}

	/** Deja los valores por defecto de Configuraciones en la base del cliente (ver Mconfiguraciones::aplicar_defectos). */
	private function aplicarDefectos()
	{
		try {
			$this->load->model('Mconfiguraciones');
			$this->Mconfiguraciones->aplicar_defectos();
		} catch (Throwable $e) {
			log_message('error', 'Login: no se pudieron aplicar los valores por defecto: ' . $e->getMessage());
		}
	}
}
