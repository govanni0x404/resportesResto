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
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        
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
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        $this->db->insert('produccion', $data);
        return $this->db->insert_id();
    }

    public function obtenerStockActual($id_insumo, $id_bodega)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        
        $q = "SELECT saldo as stock_actual 
              FROM stock_insumo 
              WHERE id_insumo = " . intval($id_insumo) . " 
              AND id_bodega = " . intval($id_bodega);
        
        $consulta = $this->db->query($q);
        
        if ($consulta->num_rows() > 0) {
            return $consulta->row()->stock_actual;
        }
        
        return 0;
    }

    public function actualizarStock($id_insumo, $id_bodega, $nuevo_stock)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        
        $this->db->where('id_insumo', $id_insumo);
        $this->db->where('id_bodega', $id_bodega);
        $this->db->update('stock_insumo', array('saldo' => $nuevo_stock));
        
        return $this->db->affected_rows() > 0;
    }
    
    public function obtenerCostoPPP($id_insumo)
    {
        $this->db = $this->load->database(
            $this->session->userdata('nombre_conexion'), TRUE
        );

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
        $this->db = $this->load->database(
            $this->session->userdata('nombre_conexion'), TRUE
        );

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