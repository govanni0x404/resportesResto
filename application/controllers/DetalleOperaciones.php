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
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
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
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
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
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id_venta = $this->input->post('id_venta');
		echo json_encode($this->MdetalleOperaciones->ResumenDetalleOperaciones($id_venta));
	}

	public function ListarFormasDePago()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		echo json_encode($this->MdetalleOperaciones->ListarFormasDePago());
	}

	public function ListarUsuarios()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		echo json_encode($this->MdetalleOperaciones->ListarUsuarios());
	}

	public function DetalleFormaDePagoVenta()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id_venta = $this->input->post('id_venta');
		echo json_encode($this->MdetalleOperaciones->DetalleFormaDePagoVenta($id_venta));
	}

	public function actualizarFormaDePagoVenta()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$d = $this->input->post('prod');
		$id = intval(is_array($d) && isset($d['id_venta']) ? $d['id_venta'] : 0);
		$idFpago = intval(is_array($d) && isset($d['id_fpago']) ? $d['id_fpago'] : 0);
		// Sin venta o forma de pago válida no se actualiza nada (antes: avisos de PHP y UPDATE con id_fpago NULL)
		if ($id <= 0 || $idFpago <= 0) {
			echo 'No se actualizó. ERROR: faltan la venta o la nueva forma de pago.'; // el JS busca "ERROR:" en posición > 0
			return;
		}
		$miData = array(
			'id_fpago' => $idFpago,
		);
		$r = $this->MdetalleOperaciones->actualizarFormaDePagoVenta($id, $miData);
		echo json_encode($r);
	}
}