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
        
        try {
            // Consulta con los campos reales de tu tabla insumo
            // Basado en la imagen: umedida_inventario, umedida_receta, id_umedida_inventario, id_umedida_receta
            $q = "SELECT 
                    i.id_insumo,
                    i.nombre_insumo,
                    i.umedida_inventario,
                    i.umedida_receta,
                    i.id_umedida_inventario,
                    i.id_umedida_receta
                FROM insumo i
                WHERE i.id_insumo = " . intval($id_insumo);
            
            $consulta = $this->db->query($q);
            
            if ($consulta->num_rows() > 0) {
                $insumo = $consulta->row();
                
                // Buscar el nombre de la unidad de medida en la tabla correspondiente
                // Primero intentamos con id_umedida_inventario
                if (isset($insumo->id_umedida_inventario) && $insumo->id_umedida_inventario > 0) {
                    // Intentar encontrar la tabla de unidades de medida
                    $tablas_posibles = array(
                        'unidad_medida',
                        'unidadmedida',
                        'unidades_medida',
                        'tbl_unidad_medida',
                        'cat_unidad_medida',
                        'umedida',
                        'medidas'
                    );
                    
                    $nombre_unidad = null;
                    $abreviatura = null;
                    
                    foreach ($tablas_posibles as $tabla) {
                        try {
                            // Intentar con diferentes nombres de campos
                            $campos_id = array('id_unidad_medida', 'id_umedida', 'id_medida', 'id');
                            $campos_nombre = array('nombre_unidad_medida', 'nombre_umedida', 'nombre', 'descripcion');
                            $campos_abrev = array('abreviatura', 'abrev', 'sigla', 'simbolo');
                            
                            foreach ($campos_id as $campo_id) {
                                $q_um = "SELECT * FROM " . $tabla . " WHERE " . $campo_id . " = " . intval($insumo->id_umedida_inventario) . " LIMIT 1";
                                
                                $consulta_um = $this->db->query($q_um);
                                
                                if ($consulta_um && $consulta_um->num_rows() > 0) {
                                    $um = $consulta_um->row_array();
                                    
                                    // Buscar el nombre en los diferentes campos posibles
                                    foreach ($campos_nombre as $campo_nombre) {
                                        if (isset($um[$campo_nombre])) {
                                            $nombre_unidad = $um[$campo_nombre];
                                            break;
                                        }
                                    }
                                    
                                    // Buscar la abreviatura
                                    foreach ($campos_abrev as $campo_abrev) {
                                        if (isset($um[$campo_abrev])) {
                                            $abreviatura = $um[$campo_abrev];
                                            break;
                                        }
                                    }
                                    
                                    if ($nombre_unidad || $abreviatura) {
                                        break 2; // Salir de ambos loops
                                    }
                                }
                            }
                        } catch (Exception $e) {
                            continue;
                        }
                    }
                    
                    // Si encontramos el nombre o abreviatura, usarlos
                    $insumo->nombre_unidad_medida = $nombre_unidad ? $nombre_unidad : 'UNIDAD';
                    $insumo->abreviatura = $abreviatura ? $abreviatura : ($insumo->umedida_inventario ? $insumo->umedida_inventario : 'UND');
                    
                } else {
                    // Si no hay ID de unidad, usar el valor numérico directamente
                    $insumo->nombre_unidad_medida = $insumo->umedida_inventario ? $insumo->umedida_inventario : 'UNIDAD';
                    $insumo->abreviatura = $insumo->umedida_inventario ? $insumo->umedida_inventario : 'UND';
                }
                
                return $insumo;
            }
            
            return null;
            
        } catch (Exception $e) {
            log_message('error', 'Error en obtenerDatosInsumoCompleto: ' . $e->getMessage());
            
            // Retornar datos básicos del insumo
            try {
                $q_simple = "SELECT 
                        id_insumo,
                        nombre_insumo,
                        umedida_inventario,
                        umedida_receta
                    FROM insumo
                    WHERE id_insumo = " . intval($id_insumo);
                
                $consulta_simple = $this->db->query($q_simple);
                
                if ($consulta_simple->num_rows() > 0) {
                    $insumo = $consulta_simple->row();
                    $insumo->nombre_unidad_medida = $insumo->umedida_inventario ? $insumo->umedida_inventario : 'UNIDAD';
                    $insumo->abreviatura = $insumo->umedida_inventario ? $insumo->umedida_inventario : 'UND';
                    return $insumo;
                }
            } catch (Exception $e2) {
                log_message('error', 'Error en consulta simple: ' . $e2->getMessage());
            }
            
            return null;
        }
    }
}