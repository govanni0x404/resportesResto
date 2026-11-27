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
    if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
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
    if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    echo json_encode($this->MuMedida->ListadoDeUmedida());
  }

  /** v1.34.0: unidades con su equivalencia, para convertir en recetas, insumos y producción. */
  public function equivalencias()
  {
    if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url());
    }
    header('Content-Type: application/json');
    echo json_encode($this->MuMedida->equivalencias());
  }

  public function grabarNuevaUmedida()
  {
    if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $d = $this->input->post('prod');
    if (!is_array($d)) { // sin datos del formulario: no se graba nada
    	echo json_encode(array('error' => 'Datos incompletos'));
    	return;
    }
    $id = intval($d['id_umedida']);
    $miData = array(
      'nombre_umedida' => $d['nombre_umedida'],
    );
    // v1.34.0: a cuánto equivale (para convertir kilos y gramos en recetas y producción). 'auto' = según el nombre;
    // 'ninguna' = sin equivalencia. Solo si la migración 20261124130000 ya creó las columnas.
    if ($this->MuMedida->conEquivalencias()) {
      $eq = isset($d['equivalencia']) ? (string)$d['equivalencia'] : 'auto';
      if ($eq === 'auto') $eq = MuMedida::equivalenciaPorNombre($d['nombre_umedida']);
      $e = isset(MuMedida::EQUIVALENCIAS[$eq]) ? MuMedida::EQUIVALENCIAS[$eq] : array(null, null);
      $miData['tipo_web'] = $e[0];
      $miData['factor_web'] = $e[1];
    }
    $r = $this->MuMedida->grabarNuevaUmedida($id, $miData);
    if ($r && isset($r['id_umedida'])) {
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