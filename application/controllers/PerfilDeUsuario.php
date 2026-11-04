<?php
defined('BASEPATH') or exit('No direct script access allowed');

class PerfilDeUsuario extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model(array('MperfilDeUsuario', 'Mroles'));
	}

	public function index()
	{

		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$data = array(
			'nombreUsuario' => $this->session->userdata('nombre_usuario'),
			//'modulosDisponibles' => json_decode($this->Mroles->modulosDisponibles($this->session->userdata('id_rol')), true)
		);
		$this->load->view('menuTop.php', $data);
		$this->load->view('perfilDeUsuario/perfilDeUsuario.php');
		$this->load->view('menuFooter.php');
		$this->load->view('perfilDeUsuario/js.php');
	}

	public function obtenerDatosDelUsuario()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		//$id_usuario = $this->input->post('id_usuario');
		$id_usuario = $this->session->userdata('id_usuario');
		echo json_encode($this->MperfilDeUsuario->obtenerDatosDelUsuario($id_usuario));
	}

	/** El perfil es solo de lectura: los accesos a la web los administra DataMaule aparte. */
	public function ActualizarNombreYPasswordDeUsuarioLocal()
	{
		header('Content-Type: application/json; charset=utf-8');
		http_response_code(403);
		echo json_encode(array('error' => 'El perfil no se puede modificar desde la web'));
	}

	public function obtenerCorreoAccesoWebIntranet()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		//$id_usuario = $this->input->post('id_usuario');
		$id_usuario = $this->session->userdata('id_usuario');
		echo json_encode($this->MperfilDeUsuario->obtenerCorreoAccesoWebIntranet($id_usuario));
	}


	/** El perfil es solo de lectura: los accesos a la web los administra DataMaule aparte. */
	public function ActualizarNombreYPasswordDeUsuarioIntranet()
	{
		header('Content-Type: application/json; charset=utf-8');
		http_response_code(403);
		echo json_encode(array('error' => 'El perfil no se puede modificar desde la web'));
	}
}