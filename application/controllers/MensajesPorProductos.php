<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MensajesPorProductos extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model(array('MmensajePorProducto', 'Mroles'));
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
		$this->load->view('mensajesPorProductos/mensajesPorProducto.php');
		$this->load->view('menuFooter.php');
		$this->load->view('mensajesPorProductos/listadoMensajesPorProductos.php');
		$this->load->view('mensajesPorProductos/jsNuevomensaje.php');
		$this->load->view('funciones.php');
	}

	public function ListadoDeMensajesPorProductos()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		echo json_encode($this->MmensajePorProducto->ListadoDeMensajesPorProductos());
	}

	public function grabarMsgProductos()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$d = $this->input->post('prod');
		$id = intval($d['id_mensaje_producto']);
		$miData = array(
			'id_producto' => $d['id_producto'],
			'mensaje' => $d['mensaje'],
			'vigente' => $d['vigente']
		);
		$r = $this->MmensajePorProducto->grabarMsgProductos($id, $miData);
		echo json_encode($r);
	}
}