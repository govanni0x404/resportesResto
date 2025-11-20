<?php
defined('BASEPATH') or exit('No direct script access allowed');

class IngresoEventos extends CI_Controller
{
  function __construct()
  {
    parent::__construct();
    $this->load->model(array('MingresoEventos', 'Mroles'));
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
    $this->load->view('ingresoEvento/ingresoEvento.php');
    $this->load->view('menuFooter.php');
    $this->load->view('ingresoEvento/listadoIngresoEvento.php');
    $this->load->view('ingresoEvento/jsNuevoIngresoEvento.php');
    $this->load->view('funciones.php');
  }

  public function ListadoDeIngresoEventos()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    echo json_encode($this->MingresoEventos->ListadoDeIngresoEventos());
  }

  public function grabarNuevoIngresoEvento()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $d = $this->input->post('prod');
    $id = intval($d['id_ingreso_evento']);
    $miData = array(
      'nombre_ingreso_evento' => $d['nombre_ingreso_evento'],
    );
    $r = $this->MingresoEventos->grabarNuevoIngresoEvento($id, $miData);
    echo json_encode($r);
  }
}