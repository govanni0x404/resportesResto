<?php
defined('BASEPATH') or exit('No direct script access allowed');

class ItemGastosMensuales extends CI_Controller
{
  function __construct()
  {
    parent::__construct();
    $this->load->model(array('MitemGastosMensuales', 'Mroles'));
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
    $this->load->view('itemGastosMensuales/itemGastosMensuales.php');
    $this->load->view('menuFooter.php');
    $this->load->view('itemGastosMensuales/listadoItemGastosMensuales.php');
    $this->load->view('itemGastosMensuales/jsNuevoItem.php');
    $this->load->view('funciones.php');
  }

  public function ListadoDeItemGastosMensuales()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    echo json_encode($this->MitemGastosMensuales->ListadoDeItemGastosMensuales());
  }

  public function grabarNuevoItemGastoMensual()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $d = $this->input->post('prod');
    $id = intval($d['id_item_gastos']);
    $miData = array(
      'nombre_item_gastos' => $d['nombre_item_gastos'],
      'vigente' => $d['vigente'],
    );
    $r = $this->MitemGastosMensuales->grabarNuevoItemGastoMensual($id, $miData);
    echo json_encode($r);
  }
}