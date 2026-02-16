<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Provincias extends CI_Controller
{
  function __construct()
  {
    parent::__construct();
    $this->load->model(array('Mprovincias', 'Mroles'));
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
    $this->load->view('provincia/provincia.php');
    $this->load->view('menuFooter.php');
    $this->load->view('provincia/listadoProvincia.php');
    $this->load->view('provincia/jsNuevaProvincia.php');
    $this->load->view('funciones.php');
  }

  public function ListadoProvincias()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    echo json_encode($this->Mprovincias->ListadoProvincias());
  }

  public function ListadoDeProvinciasDatatable()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    echo json_encode($this->Mprovincias->ListadoDeProvinciasDatatable());
  }

  public function grabarNuevaProvincia()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $d = $this->input->post('prod');
    $id = intval($d['id_provincia']);
    $miData = array(
      'nombre_provincia' => $d['nombre_provincia'],
    );
    $r = $this->Mprovincias->grabarNuevaProvincia($id, $miData);
    echo json_encode($r);
  }
}