<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Comuna extends CI_Controller
{
  function __construct()
  {
    parent::__construct();
    $this->load->model(array('Mcomuna', 'Mroles'));
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
    $this->load->view('comunas/comunas.php');
    $this->load->view('menuFooter.php');
    $this->load->view('comunas/listadoComunas.php');
    $this->load->view('comunas/jsNuevaComuna.php');
    $this->load->view('funciones.php');
  }

  public function ListadoDeComunas()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    echo json_encode($this->Mcomuna->ListadoDeComunas());
  }

  public function grabarNuevaComuna()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $d = $this->input->post('prod');
    $id = intval($d['id_comuna']);
    $miData = array(
      'nombre_comuna' => $d['nombre_comuna'],
      'id_provincia' => $d['id_provincia'],
    );
    $r = $this->Mcomuna->grabarNuevaComuna($id, $miData);
    if ($r && isset($r['id_comuna'])) {
        echo json_encode(array(
            'success' => true,
            'id_comuna' => $r['id_comuna'],
            'message' => 'Comuna grabada exitosamente'
        ));
    } else {
        echo json_encode($r);
    }
  }

  public function ListarComunas()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    echo json_encode($this->Mcomuna->ListarComunas());
  }
}
