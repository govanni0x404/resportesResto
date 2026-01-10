<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mdetalleinventario extends CI_Model {

    public function get_detalle($id_inventario)
    {
        $this->db->select('
            di.id_detalle_inventario,
            ins.nombre AS producto,
            um.nombre AS unidad_medida,
            di.actual,
            di.nuevo,
            di.diferencia,
            di.costo_un,
            di.valor_diferencia_costo
        ');
        $this->db->from('detalle_inventario di');
        $this->db->join('insumo ins', 'ins.id_insumo = di.id_insumo');
        $this->db->join('unidad_medida um', 'um.id_unidad = ins.id_unidad');
        $this->db->where('di.id_inventario', $id_inventario);
        $this->db->order_by('ins.nombre', 'ASC');

        return $this->db->get()->result();
    }
}
