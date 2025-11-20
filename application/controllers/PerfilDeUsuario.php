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

		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
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
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		//$id_usuario = $this->input->post('id_usuario');
		$id_usuario = $this->session->userdata('id_usuario');
		echo json_encode($this->MperfilDeUsuario->obtenerDatosDelUsuario($id_usuario));
	}

	public function ActualizarNombreYPasswordDeUsuarioLocal()
	{
		if (strlen($this->session->userdata('nombre_usuario')) > 0) {
			$d = $this->input->post('prod');
			$id_usuario = $this->session->userdata('id_usuario');
			$miData = array(
				'nombre_usuario' => $d['nombre_usuario'],
				'clave' => $d['clave'],
			);
			$r = $this->MperfilDeUsuario->ActualizarNombreYPasswordDeUsuarioLocal($id_usuario, $miData);
			echo json_encode($r);
		}
	}

	public function obtenerCorreoAccesoWebIntranet()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		//$id_usuario = $this->input->post('id_usuario');
		$id_usuario = $this->session->userdata('id_usuario');
		echo json_encode($this->MperfilDeUsuario->obtenerCorreoAccesoWebIntranet($id_usuario));
	}


	public function ActualizarNombreYPasswordDeUsuarioIntranet()
	{
		if (strlen($this->session->userdata('nombre_usuario')) > 0) {
			$d = $this->input->post('prodIntranet');
			$id_usuario_cliente = $this->session->userdata('id_usuario');
			$id_cliente = $this->session->userdata('id_cliente');
			$miData = array(
				'correo_acceso' => $d['correo_acceso'],
				'clave_acceso' => $d['clave_acceso'],
			);
			$r = $this->MperfilDeUsuario->ActualizarNombreYPasswordDeUsuarioIntranet($id_usuario_cliente, $id_cliente, $miData);
			echo json_encode($r);
		}
	}
}