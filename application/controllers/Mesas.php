<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mesas extends CI_Controller
{
  function __construct()
  {
    parent::__construct();
    $this->load->model(array('Mmesas'));
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
    $this->load->view('mesas/Mesa.php');
    $this->load->view('menuFooter.php');
    $this->load->view('mesas/listadoMesas.php');
    $this->load->view('mesas/jsNuevaMesa.php');
    $this->load->view('funciones.php');
  }

  public function ListadoDeMesas()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url());
    }
    echo json_encode($this->Mmesas->ListadoDeMesas());
  }

  public function crearMultiplesMesas()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url());
    }
    
    $d = $this->input->post('prod');
    $cantidad = isset($d['cantidad']) ? intval($d['cantidad']) : 1;
    
    $r = $this->Mmesas->crearMultiplesMesas($cantidad);
    echo json_encode($r);
  }

  public function editarMesa()
  {
      if (strlen($this->session->userdata('nombre_usuario')) == 0) {
          redirect(base_url());
      }
      
      $d = $this->input->post('prod');
      $id = intval($d['id_mesa']);
      
      // Convertir string a booleano correctamente
      $vigente_value = $d['vigente'];
      if ($vigente_value === 'true' || $vigente_value === true || $vigente_value === 't') {
          $vigente_bool = true;
      } else {
          $vigente_bool = false;
      }
      
      $miData = array(
        'vigente' => $vigente_bool
      );
      
      error_log('ID Mesa: ' . $id);
      error_log('Vigente recibido: ' . print_r($d['vigente'], true));
      error_log('Vigente convertido: ' . ($vigente_bool ? 'true' : 'false'));
      
      $r = $this->Mmesas->editarMesa($id, $miData);
      echo json_encode($r);
  }
}