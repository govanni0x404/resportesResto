<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mdetalleinventario extends CI_Model {

    public function __construct()
    {
        parent::__construct();
    }

    public function get_detalle($id_inventario)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }

        $this->load->helper('tenant');
        $db_tenant = get_tenant_db($this);

        $db_tenant->select('
            di.id_detalle_inventario,
            di.id_inventario,
            di.id_insumo,
            di.actual,
            di.nuevo,
            di.diferencia,
            di.costo_un,
            di.valor_diferencia_costo,
            di.valor_diferencia_venta,
            COALESCE(ins.nombre_insumo, \'INSUMO SIN NOMBRE\') AS nombre_insumo
        ');
        $db_tenant->from('detalle_inventario di');
        $db_tenant->join('insumo ins', 'ins.id_insumo = di.id_insumo', 'left');
        $db_tenant->where('di.id_inventario', $id_inventario);
        $db_tenant->order_by('ins.nombre_insumo', 'ASC');

        return $db_tenant->get()->result();
    }

    public function eliminar_items($ids)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }

        $this->load->helper('tenant');
        $db_tenant = get_tenant_db($this);
        $db_tenant->where_in('id_detalle_inventario', $ids);
        return $db_tenant->delete('detalle_inventario');
    }
}
