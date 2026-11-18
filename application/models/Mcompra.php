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