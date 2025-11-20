<?php
defined('BASEPATH') or exit('No direct script access allowed');

class CanalDeVenta extends CI_Controller
{
  function __construct()
  {
    parent::__construct();
    $this->load->model(array('McanalDeVenta', 'Mroles'));
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
    $this->load->view('canalventa/canalVenta.php');
    $this->load->view('menuFooter.php');
    $this->load->view('canalventa/listadoCanalventa.php');
    $this->load->view('canalventa/jsNuevoCanalventa.php');
    $this->load->view('funciones.php');
  }

  public function ListadoCanalDeVenta()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    echo json_encode($this->McanalDeVenta->ListadoCanalDeVenta());
  }

  public function grabarNuevoCanalDeVenta()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $d = $this->input->post('prod');
    $id = intval($d['id_canal_venta']);
    $miData = array(
      'nombre_canal_venta' => $d['nombre_canal_venta'],
      'id_lprecio' => $d['id_lprecio'],
      'vigente' => $d['vigente'],
      'cortesia' => $d['cortesia'],
    );
    $r = $this->McanalDeVenta->grabarNuevoCanalDeVenta($id, $miData);
    echo json_encode($r);
  }
}
