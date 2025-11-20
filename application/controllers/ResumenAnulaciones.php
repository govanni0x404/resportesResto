<?php
defined('BASEPATH') or exit('No direct script access allowed');

class ResumenAnulaciones extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model(array('MresumenAnulaciones', 'Mroles'));
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
		$this->load->view('resumenAnulaciones/resumenAnulaciones.php');
		$this->load->view('menuFooter.php');
		$this->load->view('resumenAnulaciones/js.php');
	}

	public function resumenDeAnulaciones()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$desde = $this->input->post('desde');
		$hasta = $this->input->post('hasta');
        $id_usuario = $this->input->post('id_usuario');
		$todasLasAnulacionesYeliminaciones=$this->input->post('todasLasAnulacionesYeliminaciones');
        $soloAnulaciones=$this->input->post('soloAnulaciones');
        $soloEliminaciones=$this->input->post('soloEliminaciones');
		echo json_encode($this->MresumenAnulaciones->resumenDeAnulaciones($desde, $hasta, $id_usuario,$todasLasAnulacionesYeliminaciones,$soloAnulaciones,$soloEliminaciones));
	}


}