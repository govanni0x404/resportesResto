<?php
defined('BASEPATH') or exit('No direct script access allowed');

class InsumosPorBodega extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model(array('MinsumosPorBodega', 'Mroles'));
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
		$this->load->view('insumosporbodega/insumosporbodega.php');
		$this->load->view('menuFooter.php');
		$this->load->view('insumosporbodega/js.php');
	}

	public function inventarioPorBodega()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id_bodega = $this->input->post('id_bodega');
		$id_grupo_insumo = $this->input->post('id_grupo_insumo');
		echo json_encode($this->MinsumosPorBodega->inventarioPorBodega($id_bodega, $id_grupo_insumo));
	}

	public function consultaBodegas()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		echo json_encode($this->MinsumosPorBodega->consultaBodegas());
	}

	public function detalleStockInsumos()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		$id_insumo = $this->input->post('id_insumo');
		echo json_encode($this->MinsumosPorBodega->detalleStockInsumos($id_insumo));
	}

	public function descuentosInsumos()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		echo json_encode($this->MinsumosPorBodega->descuentosInsumos());
	}
}