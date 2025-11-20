<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Compras extends CI_Controller
{
  function __construct()
  {
    parent::__construct();
    $this->load->model(array('Mcompra', 'Mroles'));
  }

  public function index()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url());
    }
    $data = array(
      'nombreUsuario' => $this->session->userdata('nombre_usuario'),
    );
    $this->load->view('menuTop.php', $data);
    $this->load->view('compras/compras.php');
    $this->load->view('compras/funciones.php');
    $this->load->view('menuFooter.php');
    $this->load->view('compras/js.php');
  }

  public function grabarCompra()
  {
    if (strlen($this->session->userdata('nombre_usuario')) > 0) {
      $d = $this->input->post('el_json');
      
      // Validar datos recibidos
      if (empty($d)) {
        log_message('error', 'grabarCompra: No se recibieron datos');
        echo json_encode(['error' => 'No se recibieron datos']);
        return;
      }
      
      // VALIDACIÓN ADICIONAL: Verificar si ya existe la compra
      $existente = $this->Mcompra->verificarSiExisteLaCompra(
        $d['id_proveedor'], 
        $d['id_tdocumento'], 
        $d['numero_documento']
      );
      
      if (!empty($existente) && $existente[0]['id_compra'] > 0) {
        log_message('warning', 'Intento de grabar compra duplicada: Proveedor ' . $d['id_proveedor'] . 
                    ', Doc ' . $d['id_tdocumento'] . ', Folio ' . $d['numero_documento']);
        echo json_encode([
          'code' => 'DUPLICATE',
          'message' => 'Esta compra ya existe en el sistema',
          'fecha' => $existente[0]['fecha']
        ]);
        return;
      }
      
      // Convertir fecha de DD-MM-YYYY a YYYY-MM-DD HH:MM:SS
      $fecha = $d['fecha'];
      if (strpos($fecha, '-') !== false) {
        $partes = explode('-', $fecha);
        if (count($partes) == 3 && strlen($partes[0]) == 2) {
          // Está en formato DD-MM-YYYY, convertir a YYYY-MM-DD
          $fecha = $partes[2] . '-' . $partes[1] . '-' . $partes[0] . ' 00:00:00';
        } else {
          // Ya está en formato YYYY-MM-DD
          $fecha = $fecha . ' 00:00:00';
        }
      }
      
      $miData = array(
        'id_proveedor' => (int)$d['id_proveedor'],
        'fecha' => $fecha,
        'id_tdocumento' => (int)$d['id_tdocumento'],
        'numero_documento' => (float)$d['numero_documento'],
        'total' => (int)$d['total'],
        'id_usuario' => (int)$this->session->userdata('id_usuario'),
      );
      
      $r = $this->Mcompra->grabarCompra($miData);

      echo json_encode($r);
      return;
    }
  }

  public function grabarDetalleCompra()
  {
    if (strlen($this->session->userdata('nombre_usuario')) > 0) {
      $d = $this->input->post('detalle');
      $idCompra = $this->input->post('id_compra');
      
      // Validar datos recibidos
      if (empty($d) || empty($idCompra)) {
        log_message('error', 'grabarDetalleCompra: Datos incompletos. ID Compra: ' . $idCompra . ', Detalle: ' . print_r($d, true));
        echo json_encode(['error' => 'Datos incompletos']);
        return;
      }
      
      $respuesta = array();
      $errores = array();
      
      foreach ($d as $key => $value) {
        // Limpiar valores numéricos
        $cantidad_str = str_replace(',', '.', str_replace('.', '', (string)$value['cantidad']));
        $bruto_unitario_str = str_replace(',', '.', str_replace('.', '', (string)$value['bruto_unitario']));
        $bruto_total_str = str_replace(',', '.', str_replace('.', '', (string)$value['bruto_total']));
        
        $miData = array(
          'id_compra' => (int)$idCompra,
          'cantidad' => (float)$cantidad_str,
          'bruto_unitario' => (float)$bruto_unitario_str,
          'bruto_total' => (float)$bruto_total_str,
          'id_insumo' => (int)$value['id_insumo'],
        );
        
        $r = $this->Mcompra->grabarDetalleCompra($miData);
        
        // Verificar si hay error
        if (isset($r['error'])) {
          log_message('error', 'Error al insertar detalle #' . ($key + 1) . ': ' . print_r($r['error'], true));
          $errores[] = $r;
        } else {
          log_message('debug', 'Detalle #' . ($key + 1) . ' insertado correctamente');
          $respuesta[] = $r;
        }
      }
      
      // Si hubo errores, incluirlos en la respuesta
      if (!empty($errores)) {
        log_message('error', 'Se encontraron errores al grabar detalles: ' . print_r($errores, true));
        echo json_encode([
          'success' => $respuesta,
          'errors' => $errores
        ]);
      } else {
        log_message('debug', 'Todos los detalles se insertaron correctamente');
        echo json_encode($respuesta);
      }
    }
  }

  public function ListadoTipoDeDocumento()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url());
    }
    echo json_encode($this->Mcompra->ListadoTipoDeDocumento());
  }

  public function verificarSiExisteLaCompra()
  {
    if (strlen($this->session->userdata('nombre_usuario')) > 0) {
      $d = $this->input->post('midata');
      
      if (empty($d)) {
        echo json_encode(['error' => 'No se recibieron datos']);
        return;
      }
      
      $idProveedor = $d['id_proveedor'];
      $idTdocumento = $d['id_tdocumento'];
      $numero_documento = $d['numero_documento'];
      
      echo json_encode($this->Mcompra->verificarSiExisteLaCompra($idProveedor, $idTdocumento, $numero_documento));
    }
  }
}