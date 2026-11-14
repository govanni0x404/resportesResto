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
    if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url());
    }
    $data = array(
      'nombreUsuario' => $this->session->userdata('nombre_usuario'),
      'idDocCompra' => $this->documentoCompraDefecto(),
    );
    $this->load->view('menuTop.php', $data);
    // La vista va antes del pie (como en Mesas o Productos) para quedar dentro del área de contenido
    $this->load->view('compras/compras.php');
    $this->load->view('menuFooter.php');
    $this->load->view('compras/funciones.php');
    $this->load->view('compras/js.php', $data);
  }

  public function grabarCompra()
  {
    if (strlen((string)$this->session->userdata('nombre_usuario')) > 0) {
      $d = $this->input->post('el_json');
      
      // Validar datos recibidos
      if (empty($d)) {
        log_message('error', 'grabarCompra: No se recibieron datos');
        echo json_encode(['error' => 'No se recibieron datos']);
        return;
      }
      // Validar la cabecera antes de usarla (evita avisos de PHP y errores SQL con datos incompletos)
      if (!is_array($d) || (int)($d['id_proveedor'] ?? 0) <= 0 || (int)($d['id_tdocumento'] ?? 0) <= 0
        || !is_numeric($d['numero_documento'] ?? null) || empty($d['fecha'])) {
        echo json_encode(['error' => 'Faltan datos de la compra (proveedor, documento, folio o fecha)']);
        return;
      }
      
      // 14-11-2026: si una receta/insumo haría fallar el detalle, no se graba ni la cabecera (quedaba compra sin detalle)
      $bloqueo = $this->recetasQueBloqueanCompras();
      if ($bloqueo !== '') {
        echo json_encode(['error' => $bloqueo]);
        return;
      }

      // VALIDACIÓN ADICIONAL: Verificar si ya existe la compra
      $existente = $this->Mcompra->verificarSiExisteLaCompra(
        $d['id_proveedor'], 
        $d['id_tdocumento'], 
        $d['numero_documento']
      );
      
      if (!empty($existente) && $existente[0]['id_compra'] > 0) {
        log_message('info', 'Intento de grabar compra duplicada: Proveedor ' . $d['id_proveedor'] . 
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
    if (strlen((string)$this->session->userdata('nombre_usuario')) > 0) {
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
      
      if (!is_array($d)) {
        echo json_encode(['error' => 'Datos incompletos']);
        return;
      }

      // 14-11-2026: el trigger f_addptocomprado (del POS) recalcula TODAS las recetas en cada línea de compra y
      // divide por rendimiento y por umedida_receta. Si alguna está en 0 la compra caía con 500 "division by zero"
      // y quedaba la cabecera sin detalle. Se avisa antes, con el nombre de lo que hay que corregir.
      $bloqueo = $this->recetasQueBloqueanCompras();
      if ($bloqueo !== '') {
        echo json_encode(['error' => $bloqueo]);
        return;
      }

      foreach ($d as $key => $value) {
        // Limpiar valores numéricos. La vista manda el número con punto decimal y sin separador de miles
        // (ej. "1.5"); antes se borraban todos los puntos y 1.5 kg quedaba grabado como 15.
        $miData = array(
          'id_compra' => (int)$idCompra,
          'cantidad' => $this->numeroDesdeTexto($value['cantidad'] ?? 0),
          'bruto_unitario' => $this->numeroDesdeTexto($value['bruto_unitario'] ?? 0),
          'bruto_total' => $this->numeroDesdeTexto($value['bruto_total'] ?? 0),
          'id_insumo' => (int)($value['id_insumo'] ?? 0),
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
    if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url());
    }
    echo json_encode($this->Mcompra->ListadoTipoDeDocumento());
  }

  public function verificarSiExisteLaCompra()
  {
    if (strlen((string)$this->session->userdata('nombre_usuario')) > 0) {
      $d = $this->input->post('midata');
      
      if (empty($d)) {
        echo json_encode(['error' => 'No se recibieron datos']);
        return;
      }
      
      // Normalizar tipos: un id vacío o un folio no numérico hacían fallar la consulta en Postgres
      $idProveedor = (int)($d['id_proveedor'] ?? 0);
      $idTdocumento = (int)($d['id_tdocumento'] ?? 0);
      $numero_documento = is_numeric($d['numero_documento'] ?? null) ? (float)$d['numero_documento'] : 0;
      
      echo json_encode($this->Mcompra->verificarSiExisteLaCompra($idProveedor, $idTdocumento, $numero_documento));
    }
  }

  /**
   * Convierte a número lo que manda la vista. Acepta "1234.5" (formato de los input number, sin miles),
   * "1,234.5" y también el formato chileno "1.234,5" o "1.234.567".
   */
  /** '' si se puede comprar; si no, el mensaje con las recetas/insumos que tienen rendimiento o unidad de receta en 0. */
  private function recetasQueBloqueanCompras()
  {
    $this->load->helper('tenant');
    $db = get_tenant_db($this);
    $nombres = array();
    try {
      $q = $db->query("SELECT nombre_receta FROM receta WHERE COALESCE(rendimiento, 0) <= 0 ORDER BY 1 LIMIT 10");
      if ($q) foreach ($q->result() as $f) $nombres[] = 'receta "' . trim((string)$f->nombre_receta) . '" (rendimiento 0)';
      $q = $db->query("SELECT i.nombre_insumo FROM insumo i
                        WHERE COALESCE(i.umedida_receta, 0) <= 0
                          AND EXISTS (SELECT 1 FROM detalle_receta dr WHERE dr.id_insumo = i.id_insumo)
                        ORDER BY 1 LIMIT 10");
      if ($q) foreach ($q->result() as $f) $nombres[] = 'insumo "' . trim((string)$f->nombre_insumo) . '" (unidad de receta 0)';
    } catch (Throwable $e) {
      log_message('error', 'recetasQueBloqueanCompras: ' . $e->getMessage());
      return '';
    }
    if (!$nombres) return '';
    return 'No se puede grabar la compra: corrija primero ' . implode(', ', $nombres) . '.';
  }

  private function numeroDesdeTexto($valor)
  {
    $t = trim((string)$valor);
    if (strpos($t, ',') !== false && strpos($t, '.') !== false && strrpos($t, ',') < strrpos($t, '.')) {
      $t = str_replace(',', '', $t); // "1,234.5": la coma es separador de miles
    } elseif (strpos($t, ',') !== false) {
      $t = str_replace(',', '.', str_replace('.', '', $t)); // "1.234,5": formato chileno
    } elseif (substr_count($t, '.') > 1) {
      $t = str_replace('.', '', $t); // "1.234.567": solo separadores de miles
    }
    return (float)$t;
  }

  /** Documento de compra por defecto: Configuraciones > Valores por defecto > Documento de compra (0 = ninguno). */
  private function documentoCompraDefecto()
  {
    try {
      $this->load->helper('tenant');
      $db = get_tenant_db($this);
      if (!$db->field_exists('id_tdocumento_compra', 'configuracion')) return 0;
      $r = $db->query('SELECT COALESCE(id_tdocumento_compra, 0) AS id FROM configuracion LIMIT 1')->row();
      return $r ? (int)$r->id : 0;
    } catch (Throwable $e) {
      return 0;
    }
  }
}