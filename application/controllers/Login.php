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
			$server_output = curl_exec($ch);
			$resultado = json_decode($server_output, true);
			curl_close($ch);

			$idCliente = $resultado[0]['id'];
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
			$this->index();

		} else {
			redirect(base_url());
		}
	}

		public function relogin(){
			$username = $this->input->post('username');
			$idEmpresa = $this->input->post('empresa');
			$nombreConexion= $this->input->post('nConexion');
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
				$server_output = curl_exec($ch);
				$resultado = json_decode($server_output,true);
				curl_close($ch);



				$idCliente= $resultado[0]['id'];
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
					'nombre_conexion'=>(strlen($nombreConexion)>0 ? $nombreConexion :$check_user->nombre_conexion),
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
}
