<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Bodegas extends CI_Controller
{
  function __construct()
  {
    parent::__construct();
    $this->load->model('Mbodegas');
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
    $this->load->view('bodegas/bodegas.php');
    $this->load->view('menuFooter.php');
    $this->load->view('bodegas/listadoDeBodegas.php');
    $this->load->view('bodegas/jsNuevaBodega.php');
    $this->load->view('funciones.php');
  }

  public function ListadoDeBodegas()
  {
    if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    echo json_encode($this->Mbodegas->ListadoDeBodegas());
  }

  public function grabarNuevaBodega()
  {
    if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $d = $this->input->post('prod');
    if (!is_array($d)) { // sin datos del formulario: no se graba nada
    	echo json_encode(array('error' => 'Datos incompletos'));
    	return;
    }
    $id = intval($d['id_bodega']);
		// 19-11-2026 (auditoría): nombre repetido = aviso claro (antes un error genérico de la base, o se grababa repetido)
		$this->load->helper('tenant');
		$dbDup = get_tenant_db($this);
		$nombreDup = trim((string)$d['nombre_bodega']);
		if ($nombreDup === '') { set_status_header(400); echo json_encode(array('error' => 'Escribe el nombre')); return; }
		if ($dbDup->query('SELECT 1 FROM bodega WHERE upper(trim(nombre_bodega)) = upper(trim(?)) AND id_bodega <> ? LIMIT 1',
			array($nombreDup, (int)$id))->row()) {
			set_status_header(409);
			echo json_encode(array('error' => 'Ya existe una bodega llamada "' . $nombreDup . '"'));
			return;
		}
    $miData = array(
      'nombre_bodega' => $d['nombre_bodega'],
    );
    $r = $this->Mbodegas->grabarNuevaBodega($id, $miData);
    echo json_encode($r);
  }
}
