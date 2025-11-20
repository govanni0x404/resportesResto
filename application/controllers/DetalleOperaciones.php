<?php
defined('BASEPATH') or exit('No direct script access allowed');

class DetalleOperaciones extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model(array('MdetalleOperaciones', 'Mroles'));
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
		$this->load->view('detalleoperaciones/detalleoperaciones.php');
		$this->load->view('menuFooter.php');
		$this->load->view('detalleoperaciones/js.php');
	}

	public function resumenDeOperaciones()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$desde = $this->input->post('desde');
		$hasta = $this->input->post('hasta');
		$idFpago = $this->input->post('id_Fpago');
		$idUsuario = $this->input->post('id_usuario');

		echo json_encode($this->MdetalleOperaciones->resumenDeOperaciones($desde, $hasta, $idFpago, $idUsuario));
	}

	public function ResumenDetalleOperaciones()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id_venta = $this->input->post('id_venta');
		echo json_encode($this->MdetalleOperaciones->ResumenDetalleOperaciones($id_venta));
	}

	public function ListarFormasDePago()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		echo json_encode($this->MdetalleOperaciones->ListarFormasDePago());
	}

	public function ListarUsuarios()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		echo json_encode($this->MdetalleOperaciones->ListarUsuarios());
	}

	public function DetalleFormaDePagoVenta()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id_venta = $this->input->post('id_venta');
		echo json_encode($this->MdetalleOperaciones->DetalleFormaDePagoVenta($id_venta));
	}

	public function actualizarFormaDePagoVenta()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$d = $this->input->post('prod');
		$id = intval($d['id_venta']);
		$miData = array(
			'id_fpago' => $d['id_fpago'],
		);
		$r = $this->MdetalleOperaciones->actualizarFormaDePagoVenta($id, $miData);
		echo json_encode($r);
	}
}