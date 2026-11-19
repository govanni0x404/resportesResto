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
    if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
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
    if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    echo json_encode($this->Mfamilias->ListadoDeFamilias());
  }

  public function grabarNuevaFamilia()
  {
    if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $d = $this->input->post('prod');
    if (!is_array($d)) { echo json_encode(array('error' => 'Datos incompletos')); return; }
    $id = intval($d['id_familia']);
		// 19-11-2026 (auditoría): nombre repetido = aviso claro (antes un error genérico de la base, o se grababa repetido)
		$this->load->helper('tenant');
		$dbDup = get_tenant_db($this);
		$nombreDup = trim((string)$d['nombre_familia']);
		if ($nombreDup === '') { set_status_header(400); echo json_encode(array('error' => 'Escribe el nombre')); return; }
		if ($dbDup->query('SELECT 1 FROM familia WHERE upper(trim(nombre_familia)) = upper(trim(?)) AND id_familia <> ? LIMIT 1',
			array($nombreDup, (int)$id))->row()) {
			set_status_header(409);
			echo json_encode(array('error' => 'Ya existe una familia llamada "' . $nombreDup . '"'));
			return;
		}

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
