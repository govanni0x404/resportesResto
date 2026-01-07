<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Umedida extends CI_Controller
{
  function __construct()
  {
    parent::__construct();
    $this->load->model(array('MuMedida', 'Mroles'));
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
    $this->load->view('umedida/umedida.php');
    $this->load->view('menuFooter.php');
    $this->load->view('umedida/listadoUmedida.php');
    $this->load->view('umedida/jsNuevaUmedida.php');
    $this->load->view('funciones.php');
  }

  public function ListadoDeUmedida()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    echo json_encode($this->MuMedida->ListadoDeUmedida());
  }

  public function grabarNuevaUmedida()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $d = $this->input->post('prod');
    $id = intval($d['id_umedida']);
    $miData = array(
      'nombre_umedida' => $d['nombre_umedida'],
    );
    $r = $this->MuMedida->grabarNuevaUmedida($id, $miData);
        if ($r && isset($r['id_proveedor'])) {
        echo json_encode(array(
            'success' => true,
            'id_umedida' => $r['id_umedida'],
            'message' => 'Unidad de Medida grabada exitosamente'
        ));
    } else {
        echo json_encode($r);
    }
  }
}