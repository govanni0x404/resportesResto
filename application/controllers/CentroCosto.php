<?php
defined('BASEPATH') or exit('No direct script access allowed');

class CentroCosto extends CI_Controller
{
  function __construct()
  {
    parent::__construct();
    $this->load->model('McentroCosto');
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
    $this->load->view('centroCosto/centroCosto.php');
    $this->load->view('menuFooter.php');
    $this->load->view('centroCosto/listadoCentroCosto.php');
    $this->load->view('centroCosto/jsNuevoCentroCosto.php');
    $this->load->view('funciones.php');
  }

  public function ListadoDeCentroDeCostos()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    echo json_encode($this->McentroCosto->ListadoDeCentroDeCostos());
  }

  public function grabarNuevoCentroCosto()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $d = $this->input->post('prod');
    $id = intval($d['id_ccosto']);
    $miData = array(
      'nombre_ccosto' => $d['nombre_ccosto'],
      'esingreso' => $d['esingreso'],
    );
    $r = $this->McentroCosto->grabarNuevoCentroCosto($id, $miData);
    echo json_encode($r);
  }
}
