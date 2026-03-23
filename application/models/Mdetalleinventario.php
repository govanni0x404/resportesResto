<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mdetalleinventario extends CI_Model {

    public function get_detalle($id_inventario)
    {
        $this->db->select('
            di.id_detalle_inventario,
            di.id_inventario,
            di.id_insumo,
            di.actual,
            di.nuevo,
            di.diferencia,
            di.costo_un,
            di.valor_diferencia_costo,
            di.valor_diferencia_venta,
            ins.nombre_insumo AS nombre_insumo
        ');
        $this->db->from('detalle_inventario di');
        $this->db->join('insumo ins', 'ins.id_insumo = di.id_insumo', 'left');
        $this->db->where('di.id_inventario', $id_inventario);
        $this->db->order_by('ins.nombre_insumo', 'ASC');

        return $this->db->get()->result();
    }

    public function eliminar_items($ids)
    {
        $this->db->where_in('id_detalle_inventario', $ids);
        return $this->db->delete('detalle_inventario');
    }
}