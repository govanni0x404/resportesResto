<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Minventario extends CI_Model {

    public function get_inventarios($id_bodega = null)
    {
        if ($id_bodega) {
            $this->db->where('i.id_bodega', $id_bodega);
        }

        $this->db->select('
            i.id_inventario,
            i.fecha_movimiento,
            i.fecha_inventario,
            u.nombre_usuario AS usuario
        ');
        $this->db->from('inventario i');
        $this->db->join('usuario u', 'u.id_usuario = i.id_usuario', 'left');
        $this->db->order_by('i.fecha_movimiento', 'DESC');

        return $this->db->get()->result();
    }
}
