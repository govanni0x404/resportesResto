<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Roles extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model('Mroles');
	}

	public function index()
	{
		/*
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $data = array(
      'nombreUsuario' => $this->session->userdata('nombre_usuario'),
      'modulosDisponibles' => json_decode($this->Mroles->modulosDisponibles($this->session->userdata('id_rol')), true),
      'verificarSiEsAdmin' => json_decode($this->Mroles->verificarSiEsAdmin($this->session->userdata('id_rol')), true)
    );
    $this->load->view('menuTop.php', $data);
    $this->load->view('roles/roles.php');
    $this->load->view('menuFooter.php');
    $this->load->view('roles/js.php');*/

		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$data = array(
			'nombreUsuario' => $this->session->userdata('nombre_usuario'),
			'modulosDisponibles' => json_decode($this->Mroles->modulosDisponibles($this->session->userdata('id_rol')), true)
		);

		if ($this->session->userdata('nombre_usuario')) {
			if ($this->session->userdata('id_rol') == 1) {
				$this->load->view('menuTop.php', $data);
				$this->load->view('roles/roles.php');
				$this->load->view('menuFooter.php');
				$this->load->view('roles/js.php');
			} else {
				redirect(base_url());
			}
		}
	}

	public function ListarRoles()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		echo json_encode($this->Mroles->ListarRoles());
	}

	public function ListarPrivilegioDeRoles()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id_rol = $this->input->post('id_rol');
		echo json_encode($this->Mroles->ListarPrivilegioDeRoles($id_rol));
	}

	public function BloquearAccesoModulos()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id_pusuario = $this->input->post('id_pusuario');
		echo json_encode($this->Mroles->BloquearAccesoModulos($id_pusuario));
	}

	public function HabilitarAccesoModulos()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id_pusuario = $this->input->post('id_pusuario');
		echo json_encode($this->Mroles->HabilitarAccesoModulos($id_pusuario));
	}

	public function HabilitarAccesoTodosLosModulos()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id_rol = $this->input->post('id_rol');
		echo json_encode($this->Mroles->HabilitarAccesoTodosLosModulos($id_rol));
	}

	public function BloquearAccesoTodosLosModulos()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id_rol = $this->input->post('id_rol');
		echo json_encode($this->Mroles->BloquearAccesoTodosLosModulos($id_rol));
	}

	public function grabarRoles()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$d = $this->input->post('prod');
		$id = intval($d['id_rol']);
		$miData = array(
			'nombre_rol' => $d['nombre_rol'],
		);
		$r = $this->Mroles->grabarRoles($id, $miData);
		echo json_encode($r);
	}

	public function modulosDisponibles()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id_rol = $this->input->post('id_rol');
		echo json_encode($this->Mroles->modulosDisponibles($id_rol));
	}
}
