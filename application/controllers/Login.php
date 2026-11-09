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



	public function new_user(){
		$username = $this->input->post('username');
		$password = $this->input->post('password');
		
		$idSoftware = 5;
		$check_user = $this->Mlogin->login_user($username, $password, $idSoftware);

		if ($check_user == TRUE) {
			$rut = "";
			if ($check_user->rut < 10000000) {
				$rut = "0" . number_format($check_user->rut, 0, ',', '.');
			} else {
				$rut = number_format($check_user->rut, 0, ',', '.');
			}

			$rutCliente = $rut . "-" . $check_user->dv;
			// Id de sesión nuevo al entrar: una sesión creada antes del login (ej. un enlace con un id puesto por otro) no sirve
			$this->session->sess_regenerate(TRUE);
			$data = array(
				'nombre_conexion' => $check_user->nombre_conexion,
				'rut_cliente' => $rutCliente
			);
			$this->session->set_userdata($data);

			// Obtener datos del usuario
			$datosUsuario = $this->Mlogin->getNombreUsuario($check_user->id_usuario_cliente);
			$obtenerSucursal = $this->Mlogin->obtenerSucursal($check_user->id_cliente);
			
			// Obtener id_rol de forma segura
			$rolUsuario = isset($datosUsuario->id_rol) ? $datosUsuario->id_rol : 0;

			$params = "user=cAR105_2019.,&pass=123TitinaCaturra2019.,&id=" . $rutCliente . "&ns=" . $check_user->abreviatura . "&isis=&id_sucursal=" . $obtenerSucursal->id_sucursal;

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://datamaule.cl/validarPagos/validarCliente.php");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			$server_output = curl_exec($ch);
			$resultado = json_decode($server_output, true);
			curl_close($ch);

			$idCliente = isset($resultado[0]['id']) ? (int)$resultado[0]['id'] : (int)$check_user->id_cliente;
			if ($check_user->autoriza_cambio_conexion == 't') {
				$listaConexiones = $this->Mlogin->obtenerListaConexionesUsuario($idCliente);
			} else {
				$listaConexiones = array('nombre_conexion' => $check_user->nombre_conexion);
			}

			// Usar el id_rol obtenido correctamente
			$data = array(
				'is_logued_in' => TRUE,
				'id_usuario' => $check_user->id_usuario_cliente,
				'rut_cliente' => $check_user->rut . "-" . $check_user->dv,
				'nombre_usuario' => $check_user->nombre_usuario,
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
			$idSoftware=5;
			$check_user = $this->Mlogin->relogin_user($idEmpresa,$username,$idSoftware);

			if ($check_user == TRUE) {
				$rut="";
				if ($check_user->rut<10000000) {
					$rut="0".number_format($check_user->rut, 0, ',', '.');
				}else {
					$rut=number_format($check_user->rut, 0, ',', '.');
				}

				$rutCliente= $rut."-".$check_user->dv;
				$data = array(
					'nombre_conexion'   =>    $check_user->nombre_conexion,
					'rut_cliente'   =>    $rutCliente
				);
				$this->session->set_userdata($data);

				//$dte = $this->Mlogin->login_dte();


				$datosUsuario =(object) array('id_rol' => 0 );

				$datosUsuario=$this->Mlogin->getNombreUsuario($check_user->id_usuario_cliente);
				$obtenerSucursal=$this->Mlogin->obtenerSucursal($check_user->id_cliente);
				$rolUsuario=0;
				$rolUsuario= $datosUsuario->id_rol;






				$params = "user=cAR105_2019.,&pass=123TitinaCaturra2019.,&id=" .$rutCliente."&ns=".$check_user->abreviatura."&isis=&id_sucursal=".$obtenerSucursal->id_sucursal;

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, "https://datamaule.cl/validarPagos/validarCliente.php");
				curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
				$server_output = curl_exec($ch);
				$resultado = json_decode($server_output,true);
				curl_close($ch);



				$idCliente = isset($resultado[0]['id']) ? (int)$resultado[0]['id'] : (int)$check_user->id_cliente;
				if ($check_user->autoriza_cambio_conexion=='t') {
					$listaConexiones =$this->Mlogin->obtenerListaConexionesUsuario($idCliente);
				}else {
					$listaConexiones = array('nombre_conexion' =>$check_user->nombre_conexion);
				}
/*
				 $data = array(
				 	'rut_cliente'   =>    $check_user->rut . "-" . $check_user->dv
				 );
				 $this->session->set_userdata($data);
				 */
				// $dte = $this->Mlogin->login_dte();
				// $datosUsuario = $this->Mlogin->getNombreUsuario($check_user->id_usuario_cliente);

				$data = array(
					'is_logued_in'        => TRUE,
					'id_usuario'          => $check_user->id_usuario_cliente,
				  'rut_cliente'         => $check_user->rut . "-" . $check_user->dv,
					'nombre_usuario'      => $check_user->nombre_usuario,
					'nombre_conexion'=>($this->conexionPermitida($nombreConexion, $listaConexiones) ? $nombreConexion : $check_user->nombre_conexion),
					'nombre_cliente'			=> $check_user->nombre_cliente,
					'correo_acceso' =>$username,
					'id_software'=>$idSoftware,
					'autoriza_cambio_conexion'=> $check_user->autoriza_cambio_conexion,

					'lista_conexiones'=>$listaConexiones,
					// 'nombre_controlador'  => $check_user->nombre_controlador,
					 'id_rol'              => $check_user->id_rol,
					// 'admin'               => $check_user->admin,
					// 'user_dte'            => $config->rut_dte,
					// 'pass_dte'            => $config->clave_dte,
					// 'iva'                 => $config->valor_iva,
					// 'terminal'            => 'DATAPDV-CAJA1'

				);
				$this->session->set_userdata($data);
				$this->aplicarDefectos();
				$this->index();
			} else {
				redirect(base_url());
			}


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
