<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Recintos extends CI_Controller
{
  function __construct()
  {
    parent::__construct();
    $this->load->model(array('Mrecintos', 'Mroles'));
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
    $this->load->view('recintos/recinto.php');
    $this->load->view('menuFooter.php');
    $this->load->view('recintos/listadoRecintos.php');
    $this->load->view('recintos/jsNuevoRecinto.php');
    $this->load->view('funciones.php');
  }

  public function ListadoDeRecintos()
  {
    if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    echo json_encode($this->Mrecintos->ListadoDeRecintos());
  }

  public function grabarNuevoRecinto()
  {
      if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
          redirect(base_url());
      }

      $d = $this->input->post('prod');
      if (!is_array($d) || trim((string)($d['nombre_recinto'] ?? '')) === '') {
          echo json_encode(array('error' => 'Datos inválidos: falta el nombre del recinto'));
          return;
      }
      $id = intval($d['id_recinto'] ?? 0);

      if (empty($d['id_dimpresion']) || intval($d['id_dimpresion']) === 0) {
        $ultimoDestino = $this->Mrecintos->obtenerUltimoDestinoImpresion();
        $idDestino = $ultimoDestino ? $ultimoDestino->id_dimpresion : 0;
      } else {
        $idDestino = intval($d['id_dimpresion']);
      }

      $miData = array(
          'nombre_recinto'  => $d['nombre_recinto'],
          'vigente'         => $d['vigente'] ?? 1,
          'id_dimpresion'   => $idDestino, 
          'ruta'            => 0,
          'ultima_pos_mesa' => 0,
      );

      $r = $this->Mrecintos->grabarNuevoRecinto($id, $miData);
      echo json_encode($r);
  }
}