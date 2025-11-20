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
		$this->load->model(array('Mlogin', 'Mroles'));
	}

	public function index()
	{
		$data = array(
			'nombreUsuario' => $this->session->userdata('nombre_usuario'),
			'modulosDisponibles' => json_decode($this->Mroles->modulosDisponibles($this->session->userdata('id_rol')), true)
		);

		if ($this->session->userdata('nombre_usuario')) {
			if ($this->session->userdata('id_rol') == 1) {
				redirect(base_url() . 'index.php/Dashboard');
			} else {
				redirect(base_url() . 'index.php/Dashboard');
			}
		}

		
	}



	public function new_user()
	{
		$username = $this->input->post('username');
		$password = $this->input->post('password');
		$check_user = $this->Mlogin->login_user(strtoupper($username), $password);
		/*	$config = $this->Mlogin->datos_config();*/


		if ($check_user == TRUE) {

			// $data = array(
			// 	'rut_cliente'   =>    $check_user->rut . "-" . $check_user->dv
			// );
			// $this->session->set_userdata($data);
			// $dte = $this->Mlogin->login_dte();
			// $datosUsuario = $this->Mlogin->getNombreUsuario($check_user->id_usuario_cliente);

			$data = array(
				'is_logued_in'        => TRUE,
				'id_usuario'          => $check_user->id_usuario,
				// 'rut_cliente'         => $config->rut_dte,
				'nombre_usuario'      => $check_user->nombre_usuario,
				//'nombre_controlador'  => $check_user->nombre_controlador,
				'id_rol'              => $check_user->id_rol,
				'autoriza'              => $check_user->autoriza,
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


	// public function relogin()
	// {
	// 	$username = $this->input->post('username');
	// 	$password = $this->input->post('password');
	// 	$check_user = $this->Mlogin->login_user($username, $password);
	// 	$dte = $this->Mlogin->login_dte();

	// 	if ($check_user == TRUE) {
	// 		$data = array(
	// 			'is_logued_in'  =>    TRUE,
	// 			'usuario_id'  =>    $check_user->id_usuario,
	// 			'cargo'   =>    $check_user->nombre_rol,
	// 			//'username'    =>    $check_user->nick,
	// 			//'email'     =>    $check_user->correo_electronico,
	// 			'nombre'    =>      $check_user->nombre_usuario,
	// 			'rol' => $check_user->id_rol,
	// 			'rut_dte' => $dte->rut_empresa,
	// 			'ciudad' => $dte->nombre_comuna,
	// 			'giro' => $dte->giro,
	// 			'comuna' => $dte->nombre_comuna,
	// 			'razon_social' => $dte->razon_social,
	// 			'direccion' => $dte->direccion_empresa,
	// 			'user_dte' => $dte->user_dte,
	// 			'pass_dte' => $dte->pass_dte,
	// 			'bodega' => $check_user->id_bodega
	// 		);
	// 		$this->session->set_userdata($data);
	// 		$this->output->set_content_type('application/json');
	// 		$this->output->set_output(json_encode(1));
	// 	} else {
	// 		$this->output->set_content_type('application/json');
	// 		$this->output->set_output(json_encode(0));
	// 	}
	// }


	// public function validarSesion()
	// {
	// 	if (strlen($this->session->userdata('nombre_usuario')) == 0) {
	// 		redirect(base_url()); //redireccionar al login
	// 	}
	// }
	// public function consultaSesionValida()
	// {
	// 	if (strlen($this->session->userdata('nombre_usuario')) == 0) {
	// 		echo false;
	// 	} else {
	// 		echo true;
	// 	}
	// }

	public function logout_ci()
	{
		$this->session->unset_userdata('nombre_usuario');
		$this->session->sess_destroy();
		redirect(base_url());
	}
}
