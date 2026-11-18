<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mproduccion extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function listadoProducciones()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        
        $q = "SELECT 
                p.id_produccion,
                p.fecha,
                p.id_insumo,
                i.nombre_insumo,
                p.id_bodega,
                b.nombre_bodega,
                p.cantidad,
                p.stock_actual,
                p.nuevo_stock,
                p.costo_total,
                p.costo_unitario,
                p.observaciones,
                to_char(p.fecha_transaccion, 'DD/MM/YYYY HH24:MI:SS') as fecha_transaccion
            FROM produccion p
            LEFT JOIN insumo i ON p.id_insumo = i.id_insumo
            LEFT JOIN bodega b ON p.id_bodega = b.id_bodega
            ORDER BY p.fecha DESC";

        $consulta = $this->db->query($q);
        return $consulta->result();
    }

    public function grabarProduccion($data)
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $this->db->insert('produccion', $data);
        return $this->db->insert_id();
    }

    public function obtenerStockActual($id_insumo, $id_bodega, $bloquear = false)
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        
        $q = "SELECT saldo as stock_actual 
              FROM stock_insumo 
              WHERE id_insumo = " . intval($id_insumo) . " 
              AND id_bodega = " . intval($id_bodega) . ($bloquear ? " FOR UPDATE" : "");
        
        $consulta = $this->db->query($q);
        
        if ($consulta->num_rows() > 0) {
            return $consulta->row()->stock_actual;
        }
        
        return 0;
    }

    public function actualizarStock($id_insumo, $id_bodega, $nuevo_stock)
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        
        $this->db->where('id_insumo', $id_insumo);
        $this->db->where('id_bodega', $id_bodega);
        $this->db->update('stock_insumo', array('saldo' => $nuevo_stock));
        
        return $this->db->affected_rows() > 0;
    }
    
    /**
     * 18-11-2026: consumo de un insumo en la producción, como el resto del sistema (Caja, inventario, salidas de
     * bodega): suma a cantidad_salida y saldo = entradas - salidas. Antes solo se cambiaba el saldo, y la siguiente
     * entrada de bodega lo recalculaba con entradas - salidas: lo gastado en la producción "volvía" al stock.
     */
    public function salidaStock($id_insumo, $id_bodega, $cantidad)
    {
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $c = (float)$cantidad;
        $this->db->query('UPDATE stock_insumo SET cantidad_salida = COALESCE(cantidad_salida, 0) + ?,
                saldo = COALESCE(cantidad_entrada, 0) - (COALESCE(cantidad_salida, 0) + ?)
            WHERE id_insumo = ? AND id_bodega = ?', array($c, $c, (int)$id_insumo, (int)$id_bodega));
        return $this->db->affected_rows() > 0;
    }

    /**
     * 18-11-2026: entrada de lo producido (insumo elaborado) a la bodega de destino. Si el insumo no tenía fila de stock
     * en esa bodega, se crea. Los productos no tienen stock propio: al venderse descuentan los insumos de su receta, así
     * que el producto que usa este insumo (ej. "Pan") queda con stock para venderse.
     */
    public function entradaStock($id_insumo, $id_bodega, $cantidad)
    {
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $c = (float)$cantidad;
        $this->db->query('UPDATE stock_insumo SET cantidad_entrada = COALESCE(cantidad_entrada, 0) + ?,
                saldo = COALESCE(cantidad_entrada, 0) + ? - COALESCE(cantidad_salida, 0)
            WHERE id_insumo = ? AND id_bodega = ?', array($c, $c, (int)$id_insumo, (int)$id_bodega));
        if ($this->db->affected_rows() > 0) return true;
        $this->db->query('INSERT INTO stock_insumo (id_insumo, id_bodega, cantidad_entrada, cantidad_salida, saldo) VALUES (?, ?, ?, 0, ?)',
            array((int)$id_insumo, (int)$id_bodega, $c, $c));
        return $this->db->affected_rows() > 0;
    }

    /**
     * 18-11-2026: costo promedio ponderado del insumo elaborado después de producir: lo que había (en todas las bodegas)
     * a su costo, más lo producido a su costo. Llamar ANTES de entradaStock (usa el saldo anterior). Devuelve el costo
     * unitario nuevo. El bruto se ajusta en la misma proporción que tenía (o con IVA 19% si no tenía).
     */
    public function actualizarCostoElaborado($id_insumo, $cantidad, $costoTotal)
    {
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $i = $this->db->query('SELECT COALESCE(costo_neto_ppp, 0) AS neto, COALESCE(costo_bruto_ppp, 0) AS bruto FROM insumo WHERE id_insumo = ?',
            array((int)$id_insumo))->row();
        if (!$i) return 0;
        $s = $this->db->query('SELECT COALESCE(sum(saldo), 0) AS saldo FROM stock_insumo WHERE id_insumo = ?', array((int)$id_insumo))->row();
        $antes = max(0, (float)$s->saldo);   // un saldo negativo no aporta costo
        $cantidad = (float)$cantidad;
        if ($cantidad <= 0) return (float)$i->neto;
        $neto = round(($antes * (float)$i->neto + (float)$costoTotal) / ($antes + $cantidad), 4);
        $factor = (float)$i->neto > 0 && (float)$i->bruto > 0 ? (float)$i->bruto / (float)$i->neto : 1.19;
        $this->db->query('UPDATE insumo SET costo_neto_ppp = ?, costo_bruto_ppp = ? WHERE id_insumo = ?',
            array($neto, round($neto * $factor, 4), (int)$id_insumo));
        return $neto;
    }

    public function obtenerCostoPPP($id_insumo)
    {
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);

        $q = "SELECT costo_neto_ppp
            FROM insumo
            WHERE id_insumo = " . intval($id_insumo);

        $consulta = $this->db->query($q);

        if ($consulta->num_rows() > 0) {
            return floatval($consulta->row()->costo_neto_ppp);
        }

        return 0;
    }

    public function obtenerDatosInsumoCompleto($id_insumo)
    {
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);

        $q = "SELECT 
                i.id_insumo,
                i.nombre_insumo,

                -- Unidad receta
                ur.id_umedida AS id_umedida_receta,
                ur.nombre_umedida AS umedida_receta,

                -- Unidad inventario
                ui.id_umedida AS id_umedida_inventario,
                ui.nombre_umedida AS umedida_inventario

            FROM insumo i
            LEFT JOIN umedida ur ON i.id_umedida_receta = ur.id_umedida
            LEFT JOIN umedida ui ON i.id_umedida_inventario = ui.id_umedida
            WHERE i.id_insumo = " . intval($id_insumo);

        $consulta = $this->db->query($q);

        if ($consulta->num_rows() > 0) {
            return $consulta->row();
        }

        return null;
    }
}