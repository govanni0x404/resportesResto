<?php
defined('BASEPATH') or exit('No direct script access allowed');

class DestinoDeImpresion extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model(array('MdestinoDeImpresion', 'Mroles'));
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
		$this->load->view('destinoImpresion/destinoImpresion.php');
		$this->load->view('menuFooter.php');
		$this->load->view('destinoImpresion/listadoDestinoImpresion.php');

		$this->load->view('destinoImpresion/jsNuevoDestinoDeImpresion.php');
		$this->load->view('funciones.php');
	}

	public function ListadoDestinoDeImpresionDatatable()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		echo json_encode($this->MdestinoDeImpresion->ListadoDestinoDeImpresionDatatable());
	}

	public function grabarNuevoDestinoDeImpresion()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$d = $this->input->post('prod');
		$id = intval($d['id_dimpresion']);
		$miData = array(
			'nombre_dimpresion' => $d['nombre_dimpresion'],
			'ip' => $d['ip'],
			'puerto' => $d['puerto'],
			'ruta_windows' => $d['ruta_windows'],
			'impresion_grafica_windows' => $d['impresion_grafica_windows'],
			'permite_precuenta' => $d['permite_precuenta'],
			'impresion_local' => $d['impresion_local'],
			'usar_logo_precuenta_pos' => $d['usar_logo_precuenta_pos'],
			'size_font' => $d['size_font'],
			'sin_impresion' => $d['sin_impresion'],
			'usar_dimpresion_recinto' => $d['usar_dimpresion_recinto'],
			'usar_dimpresion_precuenta_android' => $d['usar_dimpresion_precuenta_android'],
		);
		$r = $this->MdestinoDeImpresion->grabarNuevoDestinoDeImpresion($id, $miData);
		echo json_encode($r);
	}
}
