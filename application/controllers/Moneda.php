<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Moneda extends CI_Controller
{
  function __construct()
  {
    parent::__construct();
    $this->load->model(array('Mmoneda', 'Mroles'));
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
    $this->load->view('moneda/moneda.php');
    $this->load->view('menuFooter.php');
    $this->load->view('moneda/listadoMoneda.php');
    $this->load->view('moneda/jsNuevaMoneda.php');
    $this->load->view('funciones.php');
  }

  public function ListadoDeMonedas()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    echo json_encode($this->Mmoneda->ListadoDeMonedas());
  }

  public function grabarNuevaMoneda()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $d = $this->input->post('prod');
    $id = intval($d['id_moneda']);
    $miData = array(
      'nombre_moneda' => $d['nombre_moneda'],
    );
    $r = $this->Mmoneda->grabarNuevaMoneda($id, $miData);
    echo json_encode($r);
  }
}