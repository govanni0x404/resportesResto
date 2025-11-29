<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MesasPorRecinto extends CI_Controller
{
  function __construct()
  {
    parent::__construct();
    $this->load->model('MmesasPorRecinto');
  }

  public function index()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url());
    }
    
    $data = array(
      'nombreUsuario' => $this->session->userdata('nombre_usuario'),
    );
    
    $this->load->view('menuTop.php', $data);
    $this->load->view('mesasPorRecinto/asignarMesasRecinto.php');
    $this->load->view('menuFooter.php');
    $this->load->view('mesasPorRecinto/listadoMesasSinAsignar.php');
    $this->load->view('mesasPorRecinto/jsAsignarMesa.php');
    $this->load->view('funciones.php');
  }

  public function ListadoDeMesasSinAsignar()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url());
    }
    echo json_encode($this->MmesasPorRecinto->ListadoDeMesasSinAsignar());
  }

  public function ListadoDeRecintos()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url());
    }
    echo json_encode($this->MmesasPorRecinto->ListadoDeRecintos());
  }

  public function asignarMesaARecinto()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url());
    }
    
    $d = $this->input->post('prod');
    $id_mesa = intval($d['id_mesa']);
    $id_recinto = intval($d['id_recinto']);
    
    if ($id_recinto == 0) {
      echo json_encode(array('error' => 'Debe seleccionar un recinto'));
      return;
    }
    
    // Calcular pos = id_mesa - 1
    $pos = $id_mesa - 1;
    
    $miData = array(
      'id_recinto' => $id_recinto,
      'pos' => $pos
    );
    
    error_log('Asignando mesa. ID Mesa: ' . $id_mesa . ', ID Recinto: ' . $id_recinto . ', Pos: ' . $pos);
    
    $r = $this->MmesasPorRecinto->asignarMesaARecinto($id_mesa, $miData);
    echo json_encode($r);
  }
}