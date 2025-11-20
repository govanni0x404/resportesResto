<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Bodegas extends CI_Controller
{
  function __construct()
  {
    parent::__construct();
    $this->load->model('Mbodegas');
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
    $this->load->view('bodegas/bodegas.php');
    $this->load->view('menuFooter.php');
    $this->load->view('bodegas/listadoDeBodegas.php');
    $this->load->view('bodegas/jsNuevaBodega.php');
    $this->load->view('funciones.php');
  }

  public function ListadoDeBodegas()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    echo json_encode($this->Mbodegas->ListadoDeBodegas());
  }

  public function grabarNuevaBodega()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $d = $this->input->post('prod');
    $id = intval($d['id_bodega']);
    $miData = array(
      'nombre_bodega' => $d['nombre_bodega'],
    );
    $r = $this->Mbodegas->grabarNuevaBodega($id, $miData);
    echo json_encode($r);
  }
}
