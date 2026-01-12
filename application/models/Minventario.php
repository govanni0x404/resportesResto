<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Minventario extends CI_Model {

    public function get_inventarios($id_bodega = null)
    {
        // Solo seleccionar columnas que existen en la tabla
        $this->db->select('
            i.id_inventario,
            i.fecha_movimiento,
            i.fecha_inventario,
            i.id_bodega,
            i.id_usuario,
            COALESCE(u.nombre_usuario, \'SIN USUARIO\') AS usuario
        ');
        $this->db->from('inventario i');
        $this->db->join('usuario u', 'u.id_usuario = i.id_usuario', 'left');
        
        if ($id_bodega) {
            $this->db->where('i.id_bodega', $id_bodega);
        }
        
        $this->db->order_by('i.fecha_movimiento', 'DESC');

        return $this->db->get()->result();
    }

    public function get_bodegas()
    {
        $this->db->select('id_bodega, nombre_bodega');
        $this->db->from('bodega');
        // Si tienes columna 'activo', descomenta la siguiente línea:
        // $this->db->where('activo', 1);
        $this->db->order_by('nombre_bodega', 'ASC');
        
        return $this->db->get()->result();
    }
}