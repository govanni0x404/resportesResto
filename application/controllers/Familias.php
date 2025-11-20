<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Familias extends CI_Controller
{
  function __construct()
  {
    parent::__construct();
    $this->load->model('Mfamilias');
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
    $this->load->view('familias/familias.php');
    $this->load->view('menuFooter.php');
    $this->load->view('familias/listadoFamilias.php');
    $this->load->view('familias/jsNuevaFamilia.php');
    $this->load->view('funciones.php');
  }

  public function ListadoDeFamilias()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    echo json_encode($this->Mfamilias->ListadoDeFamilias());
  }

  public function grabarNuevaFamilia()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $d = $this->input->post('prod');
    $id = intval($d['id_familia']);
    $miData = array(
      'nombre_familia' => $d['nombre_familia'],
      'venta_directa' => $d['venta_directa'],
      'orden_familia' => $d['orden_familia'],
      'imprimir_en_detalle_pedido' => $d['imprimir_en_detalle_pedido'],
      'alias' => $d['alias'],
    );
    $r = $this->Mfamilias->grabarNuevaFamilia($id, $miData);
    echo json_encode($r);
  }
}
