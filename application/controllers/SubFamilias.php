<?php
defined('BASEPATH') or exit('No direct script access allowed');

class SubFamilias extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model(array('MsubFamilias', 'Mroles'));
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
		$this->load->view('subFamilias/subFamilias.php');
		$this->load->view('menuFooter.php');
		$this->load->view('subFamilias/listadoSubFamilia.php');
		$this->load->view('subFamilias/jsNuevaSubFamilia.php');
		$this->load->view('funciones.php');
	}

	public function ListadoDeSubFamilias()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		echo json_encode($this->MsubFamilias->ListadoDeSubFamilias());
	}

	public function grabarNuevaSubFamilia()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$d = $this->input->post('prod');
		$id = intval($d['id_subfamilia']);
		$miData = array(
			'nombre_subfamilia' => $d['nombre_subfamilia'],
			'id_familia' => $d['id_familia']
		);
		$r = $this->MsubFamilias->grabarNuevaSubFamilia($id, $miData);
		echo json_encode($r);
	}

	public function ListadoSubFamiliasXFamilias()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $id_familia = $this->input->post('id_familia');
        echo json_encode($this->MsubFamilias->ListadoSubFamiliasXFamilias($id_familia));
    }
}