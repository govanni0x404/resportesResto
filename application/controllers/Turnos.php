<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Turnos extends CI_Controller
{
  function __construct()
  {
    parent::__construct();
    $this->load->model(array('Mturnos', 'Mroles'));
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
    $this->load->view('turnos/turnos.php');
    $this->load->view('menuFooter.php');
    $this->load->view('turnos/listadoDeTurnos.php');
    $this->load->view('turnos/jsNuevoTurno.php');
    $this->load->view('funciones.php');
  }

  public function ListadoDeTurnos()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    echo json_encode($this->Mturnos->ListadoDeTurnos());
  }

  public function grabarNuevoTurno()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $d = $this->input->post('prod');
    $id = intval($d['id_turno']);
    $miData = array(
      'nombre_turno' => $d['nombre_turno'],
      'cierre_turno' => $d['cierre_turno'],
      'informe_z' => $d['informe_z'],
      'impedir_cierre_con_mesas_abiertas' => $d['impedir_cierre_con_mesas_abiertas'],
    );
    $r = $this->Mturnos->grabarNuevoTurno($id, $miData);
    echo json_encode($r);
  }
}