<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Agregados extends CI_Controller
{
  function __construct()
  {
    parent::__construct();
    $this->load->model(array('Magregados', 'Mroles'));
  }

  public function index()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $data = array(
      'nombreUsuario' => $this->session->userdata('nombre_usuario'),
    );
    $this->load->view('menuTop.php', $data);
    $this->load->view('agregados/agregados.php');
    $this->load->view('menuFooter.php');
    $this->load->view('agregados/listadoAgregados.php');
    $this->load->view('agregados/jsNuevoAgregado.php');
    $this->load->view('funciones.php');
  }

  public function ListadoDeAgregados()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    echo json_encode($this->Magregados->ListadoDeAgregados());
  }

  public function grabarNuevoAgregado()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $d = $this->input->post('prod');
    $id = intval($d['id_agregado']);
    $miData = array(
      'id_producto' => $d['id_producto'],
      'cantidad' => $d['cantidad'],
      'vigente' => $d['vigente'],
      'id_producto_maestro' => $d['id_producto_maestro'],
      'valor' => $d['valor'],
      'usar_destino_impresion' => $d['usar_destino_impresion'],
      'eliminado' => false,
      'id_dimpresion_maestro' => 0,
      'id_dimpresion_agregado' => 0,
    );
    $r = $this->Magregados->grabarNuevoAgregado($id, $miData);
    echo json_encode($r);
  }
}
