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
    if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
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
    if (strlen((string)$this->session->userdata('nombre_usuario')) > 0) {

      $d = $this->input->post('midata');
      $idInsumo =  $this->input->post('id_insumo');
      $idBodega = $this->input->post('id_bodega');
      echo json_encode($this->MentradaBodega->obtenerStockActualPorBodega($idInsumo, $idBodega));
    }
  }

  public function grabarEntradaBodega(){
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
        'entrada_a_bodega' => true,
        'id_usuario' => $this->session->userdata('id_usuario'),
        'observaciones' => (string)($d['observaciones'] ?? ''),
      );

      $r = $this->MentradaBodega->grabarEntradaBodega($miData);
      echo json_encode($r);
    }
  }


  public function grabarDetalleEntradaBodega(){
    if (strlen((string)$this->session->userdata('nombre_usuario'))>0) {
        $d = $this->input->post('detalle');
        $idEsBodega = (int)$this->input->post('id');
        $idBodegaStock = (int)$this->input->post('id_bodega_stock');

        // Validar antes de recorrer (sin detalle PHP avisaba "foreach() argument must be of type array")
        if (!is_array($d) || $idEsBodega <= 0 || $idBodegaStock <= 0) {
          echo json_encode(array("error" => "Datos incompletos"));
          return;
        }

        // 16-11-2026 (QA): se revisa todo antes y se graba todo o nada (helpers/movimiento_bodega_helper.php)
        $this->load->helper(array('tenant', 'movimiento_bodega'));
        $db = get_tenant_db($this);
        $modelo = $this->MentradaBodega;
        $r = movimiento_bodega_grabar($db, 'entrada', $idEsBodega, $idBodegaStock, $d, function ($miData, $dataUpdateStock) use ($modelo) {
          return $modelo->grabarDetalleEntradaBodega($miData, $dataUpdateStock);
        });
        echo json_encode($r);
    }// fin validacion de usuario

  }//fin grabarDetalleEntrada

}