<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mcompra extends CI_Model
{
  function __construct()
  {
    parent::__construct();
  }

  public function grabarCompra($data)
  {
      if (strlen((string)$this->session->userdata('nombre_usuario')) > 0) {
          $this->load->helper('tenant'); $this->db = get_tenant_db($this);
          
          $insert_result = $this->db->insert('compra', $data);

          if ($insert_result) {
              
              $consulta = $this->db->query("SELECT CURRVAL('compra_id_compra_seq'::regclass) as id_compra");
              $r = $consulta->row();

              if ($r) {
                  return ['id' => $r->id_compra, 'message' => 'OK'];
              } else {
                  log_message('ERROR', 'grabarCompra: Falló al obtener el CURRVAL.');
                  return ['code' => '9999', 'message' => 'Error al obtener ID de compra.'];
              }
          } else {
              $error = $this->db->error();
              
              log_message('ERROR', 'SQL Fallido: ' . $this->db->last_query()); 
              
              log_message('ERROR', 'Error insert compra - Código DB: ' . $error['code'] . ', Mensaje: ' . $error['message']);
              return $error;
          }
      }
      // Si no hay sesión
      return ['code' => '403', 'message' => 'Sesión expirada.'];
  }

  public function grabarDetalleCompra($data)
  {
      if (strlen((string)$this->session->userdata('nombre_usuario')) > 0) {
          $this->load->helper('tenant'); $this->db = get_tenant_db($this);
          
          $insert_ok = $this->db->insert('detalle_compra', $data); 
         
          if ($insert_ok === FALSE) {
            $error = $this->db->error();
             
            log_message('error', 'SQL FALLIDO: ' . $this->db->last_query());
            log_message('error', 'Error insert detalle_compra - Código DB: ' . $error['code'] . ', Mensaje: ' . $error['message']);
            
            return ['error' => $error];
          }

          $consulta = $this->db->query("SELECT CURRVAL('detalle_compra_id_detalle_compra_seq'::regclass) as id_detalle_compra");
          
          if ($consulta->num_rows() > 0) {
              return ['id' => $consulta->row()->id_detalle_compra, 'message' => 'OK'];
          } else {
              log_message('error', 'grabarDetalleCompra: Falló al obtener CURRVAL.');
              return ['error' => ['code' => '9999', 'message' => 'Error al obtener ID del detalle.']];
          }

      } else {
          // Si no hay sesión
          log_message('error', 'grabarDetalleCompra: Intento de grabar sin sesión.');
          return ['error' => ['code' => '403', 'message' => 'Sesión expirada.']];
      }
  }

  /** 18-11-2026: stock total (todas las bodegas) y costo bruto promedio del insumo, leídos antes de grabar una línea. */
  public function costoAntesDeComprar($idInsumo)
  {
      $this->load->helper('tenant'); $this->db = get_tenant_db($this);
      $r = $this->db->query('SELECT COALESCE((SELECT sum(saldo) FROM stock_insumo WHERE id_insumo = ?), 0) AS saldo,
              COALESCE(costo_bruto_ppp, 0) AS bruto FROM insumo WHERE id_insumo = ?', array((int)$idInsumo, (int)$idInsumo))->row();
      return array('saldo' => $r ? (float)$r->saldo : 0.0, 'bruto' => $r ? (float)$r->bruto : 0.0);
  }

  /**
   * 18-11-2026: después de grabar la línea (el trigger ya sumó el stock y dejó el promedio simple), deja el costo promedio
   * ponderado: (stock anterior x costo anterior + cantidad x precio) / (stock anterior + cantidad). Sin stock anterior (o
   * sin costo anterior) el costo es el de esta compra. El neto y el recálculo de recetas son los mismos que hace el
   * trigger (bruto sin IVA ni impuesto adicional, más el adicional), pero solo para las recetas que usan este insumo.
   * Devuelve false si falla una sentencia (quien llama deshace la compra).
   */
  public function aplicarCostoPonderado($idInsumo, $cantidad, $brutoUnitario, $antes)
  {
      $this->load->helper('tenant'); $this->db = get_tenant_db($this);
      $id = (int)$idInsumo;
      $c = (float)$cantidad;
      $s = max(0.0, (float)$antes['saldo']);
      $b0 = (float)$antes['bruto'];
      $bruto = ($s > 0 && $b0 > 0) ? round(($s * $b0 + $c * (float)$brutoUnitario) / ($s + $c)) : round((float)$brutoUnitario);
      $sql = array(
          array("UPDATE insumo i SET costo_bruto_ppp = ?,
                  costo_neto_ppp = round(? / (1 + (COALESCE((SELECT o.valor FROM impuesto o WHERE o.id_impuesto = i.id_impto_iva), 0)
                      + COALESCE((SELECT o.valor FROM impuesto o WHERE o.id_impuesto = i.id_impto_adicional), 0)) / 100.0))
                    * (1 + COALESCE((SELECT o.valor FROM impuesto o WHERE o.id_impuesto = i.id_impto_adicional), 0) / 100.0)
              WHERE i.id_insumo = ?", array($bruto, $bruto, $id)),
          array('UPDATE detalle_receta SET costo_pp = (SELECT i.costo_neto_ppp / NULLIF(i.umedida_receta, 0) FROM insumo i WHERE i.id_insumo = detalle_receta.id_insumo)
              WHERE id_insumo = ?', array($id)),
          array('UPDATE detalle_receta SET costo_total = round(cantidad * costo_pp) WHERE id_insumo = ? AND vigente = true', array($id)),
      );
      $recetas = '(SELECT DISTINCT id_receta FROM detalle_receta WHERE id_insumo = ' . $id . ')';
      foreach (array(
          "UPDATE receta SET costo_insumos = (SELECT sum(d.costo_total) FROM detalle_receta d WHERE d.id_receta = receta.id_receta AND d.vigente = true) WHERE id_receta IN $recetas",
          "UPDATE receta SET costo_porcion = costo_insumos / NULLIF(rendimiento, 0) WHERE id_receta IN $recetas",
          "UPDATE receta SET margen_ganancia = (SELECT (r.precio_venta / (1 + ((SELECT c.por_iva FROM configuracion c) / 100))) - r.costo_porcion FROM receta r
              WHERE r.id_receta = receta.id_receta AND r.costo_porcion > 0 AND r.precio_venta > 0) WHERE id_receta IN $recetas",
          "UPDATE receta SET por_ganancia = 100 - (SELECT (r.costo_porcion / (r.precio_venta / (1 + ((SELECT c.por_iva FROM configuracion c) / 100)))) * 100 FROM receta r
              WHERE r.id_receta = receta.id_receta AND r.costo_porcion > 0 AND r.precio_venta > 0) WHERE id_receta IN $recetas",
          "UPDATE receta SET por_costo = 100 - por_ganancia WHERE id_receta IN $recetas",
      ) as $q) $sql[] = array($q, array());
      foreach ($sql as $q) {
          if ($this->db->query($q[0], $q[1]) === false) {
              log_message('error', 'aplicarCostoPonderado(' . $id . '): ' . json_encode($this->db->error()));
              return false;
          }
      }
      return true;
  }

  /**
   * v1.24.19: graba la compra COMPLETA (cabecera + líneas) en una sola transacción. Antes eran dos peticiones (cabecera y
   * después detalle): si la segunda no llegaba quedaba una compra sin detalle, y dos clics a la vez podían repetir el folio.
   * - El folio se bloquea mientras se graba (pg_advisory_xact_lock): la revisión de duplicado y la grabación van juntas.
   * - El total de la compra es la suma de las líneas (lo calcula el servidor).
   * - El stock lo suma el trigger del POS (f_addptocomprado) con un UPDATE en la bodega de compras: si el insumo no tenía
   *   fila de stock en esa bodega, la compra se grababa y el stock NO subía. Ahora la fila se crea antes, y después de cada
   *   línea se comprueba que el saldo subió exactamente lo comprado; si no, no se graba nada.
   * $cab = {id_proveedor, id_tdocumento, numero_documento, fecha (Y-m-d)}; $lineas = [{id_insumo, cantidad, bruto_unitario, bruto_total}]
   * Devuelve {ok, id_compra, total} o {error}.
   */
  public function grabarCompraCompleta(array $cab, array $lineas, $idUsuario)
  {
      $this->load->helper('tenant'); $db = get_tenant_db($this);
      $db->db_debug = FALSE;
      $cfg = $db->query('SELECT COALESCE(id_bodega_compra, 0) AS b FROM configuracion LIMIT 1')->row();
      $bodega = $cfg ? (int)$cfg->b : 0;
      if ($bodega <= 0 || !$db->query('SELECT 1 FROM bodega WHERE id_bodega = ?', array($bodega))->row()) {
          return array('error' => 'Falta la bodega de compras: elígela en Configuraciones > Valores por defecto > Bodega de compra. Sin ella el stock no se puede sumar.');
      }
      $total = 0.0;
      foreach ($lineas as $l) $total += $l['bruto_total'];
      $total = (int)round($total);

      $db->trans_begin();
      $falla = function ($msg) use ($db) { $db->trans_rollback(); return array('error' => $msg); };
      $clave = 'compra:' . (int)$cab['id_proveedor'] . ':' . (int)$cab['id_tdocumento'] . ':' . sprintf('%.0f', $cab['numero_documento']);
      if ($db->query('SELECT pg_advisory_xact_lock(hashtext(?))', array($clave)) === false) return $falla('No se pudo grabar la compra, vuelve a intentarlo');
      $dup = $db->query("SELECT to_char(fecha, 'DD/MM/YYYY') AS fecha FROM compra WHERE id_proveedor = ? AND id_tdocumento = ? AND numero_documento = ? LIMIT 1",
          array((int)$cab['id_proveedor'], (int)$cab['id_tdocumento'], (float)$cab['numero_documento']))->row();
      if ($dup) {
          $db->trans_rollback();
          return array('code' => 'DUPLICATE', 'error' => 'Esta compra ya está registrada (mismo proveedor, documento y folio), ingresada el ' . $dup->fecha . '.', 'fecha' => $dup->fecha);
      }
      if ($db->query('INSERT INTO compra (id_proveedor, fecha, id_tdocumento, numero_documento, total, id_usuario) VALUES (?, ?, ?, ?, ?, ?)',
              array((int)$cab['id_proveedor'], $cab['fecha'] . ' 00:00:00', (int)$cab['id_tdocumento'], (float)$cab['numero_documento'], $total, (int)$idUsuario)) === false) {
          log_message('error', 'grabarCompraCompleta cabecera: ' . json_encode($db->error()));
          return $falla('No se pudo grabar la compra: ' . $this->mensajeBase($db));
      }
      $idCompra = (int)$db->query("SELECT CURRVAL('compra_id_compra_seq'::regclass) AS id")->row()->id;

      foreach ($lineas as $n => $l) {
          $fila = $n + 1;
          $id = (int)$l['id_insumo'];
          // Fila de stock del insumo en la bodega de compras: se crea si falta y los vacíos quedan en 0 (NULL + x = NULL)
          $filas = $db->query('SELECT count(*) AS n FROM stock_insumo WHERE id_insumo = ? AND id_bodega = ?', array($id, $bodega))->row();
          if ((int)$filas->n === 0) {
              if ($db->query('INSERT INTO stock_insumo (id_insumo, id_bodega, cantidad_entrada, cantidad_salida, saldo) VALUES (?, ?, 0, 0, 0)', array($id, $bodega)) === false) {
                  return $falla("Línea $fila: no se pudo crear el stock del insumo en la bodega de compras");
              }
          } elseif ((int)$filas->n > 1) {
              return $falla("Línea $fila: el insumo " . $this->nombreInsumo($db, $id) . ' tiene su stock repetido en la bodega de compras (la compra se sumaría dos veces). Corrígelo en Bodega antes de comprar.');
          }
          $db->query('UPDATE stock_insumo SET cantidad_entrada = COALESCE(cantidad_entrada, 0), cantidad_salida = COALESCE(cantidad_salida, 0),
                  saldo = COALESCE(cantidad_entrada, 0) - COALESCE(cantidad_salida, 0)
                WHERE id_insumo = ? AND id_bodega = ? AND (cantidad_entrada IS NULL OR cantidad_salida IS NULL OR saldo IS NULL)', array($id, $bodega));
          // FOR UPDATE: una venta en paralelo espera a que termine la compra (si no, la comprobación de abajo se confundiría)
          $saldoAntes = (float)$db->query('SELECT saldo FROM stock_insumo WHERE id_insumo = ? AND id_bodega = ? FOR UPDATE', array($id, $bodega))->row()->saldo;

          $antes = $this->costoAntesDeComprar($id);
          if ($db->query('INSERT INTO detalle_compra (id_compra, cantidad, bruto_unitario, bruto_total, id_insumo) VALUES (?, ?, ?, ?, ?)',
                  array($idCompra, $l['cantidad'], $l['bruto_unitario'], $l['bruto_total'], $id)) === false) {
              log_message('error', "grabarCompraCompleta línea $fila: " . json_encode($db->error()));
              return $falla("No se pudo grabar la línea $fila: " . $this->mensajeBase($db) . '. No se grabó nada.');
          }
          // numeric(10,2): lo grabado es lo que cuenta para el stock
          $cant = (float)$db->query('SELECT cantidad FROM detalle_compra WHERE id_detalle_compra = CURRVAL(\'detalle_compra_id_detalle_compra_seq\'::regclass)')->row()->cantidad;
          $saldoDespues = (float)$db->query('SELECT saldo FROM stock_insumo WHERE id_insumo = ? AND id_bodega = ?', array($id, $bodega))->row()->saldo;
          if (abs(($saldoDespues - $saldoAntes) - $cant) > 0.0001) {
              log_message('error', "grabarCompraCompleta línea $fila: stock antes $saldoAntes, después $saldoDespues, comprado $cant");
              return $falla("Línea $fila: el stock de " . $this->nombreInsumo($db, $id) . ' no subió lo comprado. No se grabó nada; revisa la bodega de compras.');
          }
          if (!$this->aplicarCostoPonderado($id, $cant, $l['bruto_unitario'], $antes)) {
              return $falla("Línea $fila: no se pudo actualizar el costo promedio del insumo. No se grabó nada.");
          }
      }
      if ($db->trans_status() === false) return $falla('No se pudo grabar la compra, vuelve a intentarlo');
      $db->trans_commit();
      return array('ok' => true, 'id_compra' => $idCompra, 'total' => $total, 'lineas' => count($lineas));
  }

  private function nombreInsumo($db, $id)
  {
      $r = $db->query('SELECT nombre_insumo FROM insumo WHERE id_insumo = ?', array((int)$id))->row();
      return $r ? '"' . trim((string)$r->nombre_insumo) . '"' : '#' . (int)$id;
  }

  private function mensajeBase($db)
  {
      $e = $db->error();
      return trim(preg_replace('/^ERROR:\s*/', '', (string)(is_array($e) ? ($e['message'] ?? '') : ''))) ?: 'error de la base de datos';
  }

  public function ListadoTipoDeDocumento()
  {
    if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url());
    }

    $this->load->helper('tenant'); $this->db = get_tenant_db($this);
    $q = " SELECT t.id_tdocumento, t.nombre_tdocumento from tdocumento t where t.para_compra=true
    order by t.nombre_tdocumento asc";
    $query = $this->db->query($q)->result();
    return $query;
  }

  public function verificarSiExisteLaCompra($idProveedor, $idTdocumento, $numero_documento)
  {
    if (strlen((string)$this->session->userdata('nombre_usuario')) > 0) {
      $this->load->helper('tenant'); $this->db = get_tenant_db($this);
      
      $q = "SELECT id_compra, to_char(fecha, 'DD/MM/YYYY') AS fecha 
            FROM compra 
            WHERE id_proveedor=? AND id_tdocumento=? AND numero_documento=?";
      
      $query = $this->db->query($q, array($idProveedor, $idTdocumento, $numero_documento));
      
      $arr = array();
      foreach ($query->result() as $row) {
        $arr[] = array(
          'id_compra' => $row->id_compra,
          'fecha' => $row->fecha
        );
      }
      
      if (empty($arr)) {
        $arr[] = array(
          'id_compra' => 0,
          'fecha' => ""
        );
      }
      
      return $arr;
    }
  }
}