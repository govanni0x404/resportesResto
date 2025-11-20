<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Proveedor extends CI_Controller
{
  function __construct()
  {
    parent::__construct();
    $this->load->model(array('Mproveedor', 'Mroles'));
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
    $this->load->view('proveedor/proveedor.php');
    $this->load->view('menuFooter.php');
    $this->load->view('proveedor/listadoProveedor.php');
    $this->load->view('proveedor/jsNuevoProveedor.php');
    $this->load->view('funciones.php');
  }

  public function ListadoDeProveedores()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    echo json_encode($this->Mproveedor->ListadoDeProveedores());
  }

  public function grabarNuevoProveedor()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $d = $this->input->post('prod');
    $id = intval($d['id_proveedor']);

    // Validar el campo creditomaximo para evitar el error en PostgreSQL
    $creditomaximo = trim($d['creditomaximo']);
    if ($creditomaximo === '' || $creditomaximo === null) {
        $creditomaximo = null; // o 0 si prefieres asignar valor por defecto
    }

    $miData = array(
      'nombre_proveedor' => $d['nombre_proveedor'],
      'rut' => $d['rut'],
      'dv' => $d['dv'],
      'direccion' => $d['direccion'],
      'telefono' => $d['telefono'],
      'mail' => $d['mail'],
      'giro' => $d['giro'],
      'creditomaximo' => $creditomaximo,
    );
    $r = $this->Mproveedor->grabarNuevoProveedor($id, $miData);
    echo json_encode($r);
  }

  public function busquedaProveedorPorRut()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $rut = $this->input->post('rut');
        echo json_encode($this->Mproveedor->busquedaProveedorPorRut($rut));
    }

    public function ListarProveedores()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        echo json_encode($this->Mproveedor->ListarProveedores());
    }
}