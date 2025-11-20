<?php
defined('BASEPATH') or exit('No direct script access allowed');

class ResumenEntradaBodega extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model(array('MresumenEntradaBodega', 'Mroles'));
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
		$this->load->view('resumenEntradaBodega/resumenEntradaBodega.php');
		$this->load->view('menuFooter.php');
		$this->load->view('resumenEntradaBodega/js.php');
	}

	public function resumenEntradaBodega()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$desde = $this->input->post('desde');
		$hasta = $this->input->post('hasta');
		$id_usuario = $this->input->post('id_usuario');
		$id_bodega = $this->input->post('id_bodega');
		echo json_encode($this->MresumenEntradaBodega->resumenEntradaBodega($desde, $hasta, $id_usuario, $id_bodega));
	}

	public function detalleResumenEntradaBodega()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id_esbodega = $this->input->post('id_esbodega');
		echo json_encode($this->MresumenEntradaBodega->detalleResumenEntradaBodega($id_esbodega));
	}
}