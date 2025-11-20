<?php
defined('BASEPATH') or exit('No direct script access allowed');

class DocumentosTributarios extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model(array('Magregados', 'Mroles'));
	}

	public function index()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$data = array(
			'nombreUsuario' => $this->session->userdata('nombre_usuario'),
			//'modulosDisponibles' => json_decode($this->Mroles->modulosDisponibles($this->session->userdata('id_rol')), true),
			//'verificarSiEsAdmin' => json_decode($this->Mroles->verificarSiEsAdmin($this->session->userdata('id_rol')), true)
		);
		$this->load->view('menuTop.php', $data);
		$this->load->view('documentostributarios/documentostributarios.php');
		$this->load->view('menuFooter.php');
		$this->load->view('documentostributarios/js.php');
		$this->load->view('funciones.php');
	}
}