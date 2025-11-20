<?php
defined('BASEPATH') or exit('No direct script access allowed');

class TipoEvento extends CI_Controller
{
  function __construct()
  {
    parent::__construct();
    $this->load->model(array('MtipoEvento', 'Mroles'));
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
    $this->load->view('tipoEvento/tipoEvento.php');
    $this->load->view('menuFooter.php');
    $this->load->view('tipoEvento/listadoTipoEvento.php');
    $this->load->view('tipoEvento/jsNuevoTipoEvento.php');
    $this->load->view('funciones.php');
  }

  public function ListadoDeTipoEventos()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    echo json_encode($this->MtipoEvento->ListadoDeTipoEventos());
  }

  public function grabarNuevoTipoEvento()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $d = $this->input->post('prod');
    $id = intval($d['id_tipo_evento']);
    $miData = array(
      'nombre_tipo_evento' => $d['nombre_tipo_evento'],
    );
    $r = $this->MtipoEvento->grabarNuevoTipoEvento($id, $miData);
    echo json_encode($r);
  }
}