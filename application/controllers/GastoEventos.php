<?php
defined('BASEPATH') or exit('No direct script access allowed');

class GastoEventos extends CI_Controller
{
  function __construct()
  {
    parent::__construct();
    $this->load->model(array('MgastoEvento', 'Mroles'));
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
    $this->load->view('gastoEvento/gastoEvento.php');
    $this->load->view('menuFooter.php');
    $this->load->view('gastoEvento/listadoGastoEvento.php');
    $this->load->view('gastoEvento/jsNuevoGastoEvento.php');
    $this->load->view('funciones.php');
  }

  public function ListadoDeGastosEventos()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    echo json_encode($this->MgastoEvento->ListadoDeGastosEventos());
  }

  public function grabarNuevoGastoEvento()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $d = $this->input->post('prod');
    $id = intval($d['id_gasto_evento']);
    $miData = array(
      'nombre_gasto_evento' => $d['nombre_gasto_evento'],
    );
    $r = $this->MgastoEvento->grabarNuevoGastoEvento($id, $miData);
    echo json_encode($r);
  }
}