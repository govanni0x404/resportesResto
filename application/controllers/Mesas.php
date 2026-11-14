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
    if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
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
    if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url());
    }
    echo json_encode($this->Mmesas->ListadoDeMesas());
  }

  public function crearMultiplesMesas()
  {
    if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url());
    }
    
    // 14-11-2026 (QA): antes no había límite (se crearon 500 mesas en medio segundo). Ahora: solo gerencia,
    // de 1 a 50 por vez y hasta 300 mesas en total (Mmesas::MAX_MESAS).
    if ((int)$this->session->userdata('id_rol') !== 1) {
      echo json_encode(array('error' => 'Solo gerencia puede crear mesas'));
      return;
    }
    $d = $this->input->post('prod');
    $cantidad = (is_array($d) && isset($d['cantidad'])) ? intval($d['cantidad']) : 1;
    if ($cantidad < 1 || $cantidad > Mmesas::MAX_POR_VEZ) {
      echo json_encode(array('error' => 'Se pueden crear de 1 a ' . Mmesas::MAX_POR_VEZ . ' mesas por vez'));
      return;
    }

    $r = $this->Mmesas->crearMultiplesMesas($cantidad);
    echo json_encode($r);
  }

  public function editarMesa()
  {
      if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
          redirect(base_url());
      }
      
      $d = $this->input->post('prod');
      if (!is_array($d) || empty($d['id_mesa'])) {
          echo json_encode(array('error' => 'Datos inválidos'));
          return;
      }
      $id = intval($d['id_mesa']);
      
      // Convertir string a booleano correctamente
      $vigente_value = $d['vigente'] ?? '';
      if ($vigente_value === 'true' || $vigente_value === true || $vigente_value === 't') {
          $vigente_bool = true;
      } else {
          $vigente_bool = false;
      }
      
      $miData = array(
        'vigente' => $vigente_bool
      );
      
      $r = $this->Mmesas->editarMesa($id, $miData);
      echo json_encode($r);
  }
}