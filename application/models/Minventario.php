<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Minventario extends CI_Model {

    public function __construct()
    {
        parent::__construct();
    }

    public function get_inventarios($id_bodega = null)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }

        $this->load->helper('tenant');
        $db_tenant = get_tenant_db($this);

        // Solo seleccionar columnas que existen en la tabla
        $db_tenant->select('
            i.id_inventario,
            i.fecha_movimiento,
            i.fecha_inventario,
            i.id_bodega,
            i.id_usuario,
            COALESCE(u.nombre_usuario, \'SIN USUARIO\') AS usuario
        ');
        $db_tenant->from('inventario i');
        $db_tenant->join('usuario u', 'u.id_usuario = i.id_usuario', 'left');
        
        if ($id_bodega) {
            $db_tenant->where('i.id_bodega', $id_bodega);
        }
        
        $db_tenant->order_by('i.fecha_movimiento', 'DESC');

        return $db_tenant->get()->result();
    }

    public function get_bodegas()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }

        $this->load->helper('tenant');
        $db_tenant = get_tenant_db($this);

        $db_tenant->select('id_bodega, nombre_bodega');
        $db_tenant->from('bodega');
        $db_tenant->order_by('nombre_bodega', 'ASC');
        
        return $db_tenant->get()->result();
    }
}
