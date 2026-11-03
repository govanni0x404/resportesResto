<?php
defined('BASEPATH') or exit('No direct script access allowed');

class SalidaBodega extends CI_Controller
{
  function __construct()
  {
    parent::__construct();
    $this->load->model('MsalidaBodega');
  }

  public function index()
  {
    if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $data = array(
      'nombreUsuario' => $this->session->userdata('nombre_usuario'),
    );
    $this->load->view('menuTop.php', $data);
    $this->load->view('salidaBodega/salidaBodega.php');
    $this->load->view('salidaBodega/funciones.php');
    $this->load->view('menuFooter.php');
    $this->load->view('salidaBodega/js.php');
  }

  public function obtenerStockActualPorBodega()
  {
    if (strlen((string)$this->session->userdata('nombre_usuario')) > 0) {

      $d = $this->input->post('midata');
      $idInsumo =  $this->input->post('id_insumo');
      $idBodega = $this->input->post('id_bodega');
      echo json_encode($this->MsalidaBodega->obtenerStockActualPorBodega($idInsumo, $idBodega));
    }
  }

  
  public function grabarSalidaBodega(){
    if (strlen((string)$this->session->userdata('nombre_usuario')) > 0) {
      
      // Decodificar el JSON recibido
      $d = json_decode((string)$this->input->post('el_json'), true);

      if (!is_array($d) || (int)($d['id_bodega'] ?? 0) <= 0 || empty($d['fecha'])) {
        echo json_encode(array("error" => "JSON inválido o vacío"));
        return;
      }

      $miData = array(
        'folio' => 0,
        'fecha' => $d['fecha'],
        'id_bodega' => $d['id_bodega'],
        'entrada_a_bodega' => false,
        'id_usuario' => $this->session->userdata('id_usuario'),
        'observaciones' => (string)($d['observaciones'] ?? ''),
      );

      $r = $this->MsalidaBodega->grabarSalidaBodega($miData);
      echo json_encode($r);
    }
  }


  public function grabarDetalleSalidaBodega(){
    if (strlen((string)$this->session->userdata('nombre_usuario'))>0) {
        $d = $this->input->post('detalle');
        $idEsBodega = (int)$this->input->post('id');
        $idBodegaStock = (int)$this->input->post('id_bodega_stock');

        // Validar antes de recorrer (sin detalle PHP avisaba "foreach() argument must be of type array")
        if (!is_array($d) || $idEsBodega <= 0 || $idBodegaStock <= 0) {
          echo json_encode(array("error" => "Datos incompletos"));
          return;
        }

        $respueta = array();
        foreach ($d as $value) {
          $idInsumo = (int)($value['id_insumo'] ?? 0);
          $cantidad = (float)str_replace(',', '', (string)($value['cantidad'] ?? 0));
          if ($idInsumo <= 0 || $cantidad <= 0) {
            continue;
          }
          // El stock anterior se lee de la BD y no del valor que mostraba la pantalla: si el mismo insumo
          // venía dos veces en la grilla, o el stock cambió mientras tanto, se perdía una de las cantidades.
          $stock = $this->MsalidaBodega->obtenerStockActualPorBodega($idInsumo, $idBodegaStock);
          $stockAnterior = !empty($stock) ? (float)$stock[0]->saldo : (float)str_replace(',', '', (string)($value['stock_anterior'] ?? 0));

          $miData=array(
            'id_esbodega'=>$idEsBodega,
            'id_insumo'=>$idInsumo,
            'cantidad'=>$cantidad,
            'stock_anterior'=>$stockAnterior,
            'nuevo_stock'=>$stockAnterior - $cantidad,
          );

          $dataUpdateStock = $miData;
          $dataUpdateStock['id_bodega_stock'] = $idBodegaStock; // Añadimos la bodega solo aquí

          $r=$this->MsalidaBodega->grabarDetalleSalidaBodega($miData, $dataUpdateStock);
          $respueta[]=$r;
        }
        echo json_encode($respueta);
    }// fin validacion de usuario

  }//fin grabarDetalleSalida
  

}