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
    
    public function obtenerCostoPPP($id_insumo, $id_bodega)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        
        $q = "SELECT precio_promedio_ponderado as costo_ppp 
            FROM stock_insumo 
            WHERE id_insumo = " . intval($id_insumo) . " 
            AND id_bodega = " . intval($id_bodega);
        
        $consulta = $this->db->query($q);
        
        if ($consulta->num_rows() > 0) {
            return $consulta->row()->costo_ppp;
        }
        
        return 0;
    }

    public function obtenerDatosInsumoCompleto($id_insumo)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        
        $q = "SELECT 
                i.id_insumo,
                i.nombre_insumo,
                i.id_unidad_medida,
                um.nombre_unidad_medida,
                um.abreviatura
            FROM insumo i
            LEFT JOIN unidad_medida um ON i.id_unidad_medida = um.id_unidad_medida
            WHERE i.id_insumo = " . intval($id_insumo);
        
        $consulta = $this->db->query($q);
        
        if ($consulta->num_rows() > 0) {
            return $consulta->row();
        }
        
        return null;
    }
}