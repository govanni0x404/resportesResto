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
    if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
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
    if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    echo json_encode($this->Mproveedor->ListadoDeProveedores());
  }

  public function grabarNuevoProveedor()
  {
    if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $d = $this->input->post('prod');
    if (!is_array($d)) { // sin datos del formulario: no se graba nada
    	echo json_encode(array('error' => 'Datos incompletos'));
    	return;
    }
    $id = intval($d['id_proveedor'] ?? 0);

    // El crédito es solo para clientes: el proveedor ya no tiene crédito máximo (no se envía ni se toca en la base)
    $miData = array(
      'nombre_proveedor' => $d['nombre_proveedor'] ?? '',
      'rut' => $d['rut'] ?? '',
      'dv' => $d['dv'] ?? '',
      'direccion' => $d['direccion'] ?? '',
      'telefono' => $d['telefono'] ?? '',
      'mail' => $d['mail'] ?? '',
      'giro' => $d['giro'] ?? '',
    );
    // 14-11-2026 (QA): RUT con dígito verificador correcto y sin repetir; correo válido si viene.
    $this->load->helper(array('tenant', 'rut'));
    $db = get_tenant_db($this);
    $rut = rut_validar_unico($db, 'proveedor', 'id_proveedor', $id, $d['rut'] ?? '', $d['dv'] ?? '', 'proveedor');
    if (isset($rut['error'])) { echo json_encode($rut); return; }
    list($miData['rut'], $miData['dv']) = $rut;
    $miData['nombre_proveedor'] = trim((string)($d['nombre_proveedor'] ?? ''));
    if ($miData['nombre_proveedor'] === '') { echo json_encode(array('error' => 'El nombre del proveedor es obligatorio')); return; }
    $miData['mail'] = trim((string)($d['mail'] ?? ''));
    if ($miData['mail'] !== '' && !filter_var($miData['mail'], FILTER_VALIDATE_EMAIL)) {
      echo json_encode(array('error' => 'El correo del proveedor no es válido')); return;
    }
    $r = $this->Mproveedor->grabarNuevoProveedor($id, $miData);
    if ($r && isset($r['id_proveedor'])) {
        echo json_encode(array(
            'success' => true,
            'id_proveedor' => $r['id_proveedor'],
            'message' => 'Proveedor grabado exitosamente'
        ));
    } else {
        echo json_encode($r);
    }
  }

  public function busquedaProveedorPorRut()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $rut = $this->input->post('rut');
        echo json_encode($this->Mproveedor->busquedaProveedorPorRut($rut));
    }

    public function ListarProveedores()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        echo json_encode($this->Mproveedor->ListarProveedores());
    }
}