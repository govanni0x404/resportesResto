<?php
defined('BASEPATH') or exit('No direct script access allowed');

class EntradaBodega extends CI_Controller
{
  function __construct()
  {
    parent::__construct();
    $this->load->model('MentradaBodega');
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
    $this->load->view('entradaBodega/entradaBodega.php');
    $this->load->view('entradaBodega/funciones.php');
    $this->load->view('menuFooter.php');
    $this->load->view('entradaBodega/js.php');
  }

  public function obtenerStockActualPorBodega()
  {
    if (strlen($this->session->userdata('nombre_usuario')) > 0) {

      $d = $this->input->post('midata');
      $idInsumo =  $this->input->post('id_insumo');
      $idBodega = $this->input->post('id_bodega');
      echo json_encode($this->MentradaBodega->obtenerStockActualPorBodega($idInsumo, $idBodega));
    }
  }

  public function grabarEntradaBodega(){
    if (strlen($this->session->userdata('nombre_usuario')) > 0) {
      
      // Decodificar el JSON recibido
      $d = json_decode($this->input->post('el_json'), true);

      if (!$d) {
        echo json_encode(array("error" => "JSON inválido o vacío"));
        return;
      }

      $miData = array(
        'folio' => 0,
        'fecha' => $d['fecha'],
        'id_bodega' => $d['id_bodega'],
        'entrada_a_bodega' => true,
        'id_usuario' => $this->session->userdata('id_usuario'),
        'observaciones' => $d['observaciones'],
      );

      $r = $this->MentradaBodega->grabarEntradaBodega($miData);
      echo json_encode($r);
    }
  }


  public function grabarDetalleEntradaBodega(){
    if (strlen($this->session->userdata('nombre_usuario'))>0) {
        $d = $this->input->post('detalle');
        $idEsBodega = $this->input->post('id');

        $idBodegaStock = $this->input->post('id_bodega_stock');

        $respuesta=array();

        foreach ($d as $value) {
          $miData=array(
            'id_esbodega'=>$idEsBodega,
            'id_insumo'=>$value['id_insumo'],
            'cantidad'=>$value['cantidad'],
            'stock_anterior'=>$value['stock_anterior'],
            'nuevo_stock'=>$value['stock_anterior']+$value['cantidad'],
          );

          $dataUpdateStock = $miData;
          $dataUpdateStock['id_bodega_stock'] = $idBodegaStock; // Añadimos la bodega solo aquí

          $r=$this->MentradaBodega->grabarDetalleEntradaBodega($miData, $dataUpdateStock);
          $respueta[]=$r;
        }
        echo json_encode($respueta);
    }// fin validacion de usuario

  }//fin grabarDetalleEntrada

}