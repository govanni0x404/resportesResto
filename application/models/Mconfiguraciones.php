<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mconfiguraciones extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    public function get_configuracion() {
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $query = $this->db->get_where('configuracion', array('id_configuracion' => 1));
        $row = $query->row_array();
        if (!$row) {
            return [];
        }
        return $row;
    }

    public function get_empresa() {
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $query = $this->db->get('empresa');
        $row = $query->row_array();
        
        // Si no existe ningún registro, crear uno vacío
        if (!$row) {
            $data = [
                'rut_empresa' => 0,
                'dv_empresa' => '',
                'razon_social' => '',
                'giro' => '',
                'codigo_actividad_economica' => '',
                'direccion_empresa' => '',
                'id_comuna' => 0,
                'codigo_sucursal' => '',
                'nombre_sucursal' => '',
                'direccion_sucursal' => '',
                'id_comuna_sucursal' => 0,
                'codigo_sii' => 0,
                'mail' => '',
                'nombre_fantasia' => '',
            ];
            $this->db->insert('empresa', $data);
            
            // Obtener el registro recién creado
            $query = $this->db->get('empresa');
            $row = $query->row_array();
        }
        
        return $row;
    }

    public function get_bodegas() {
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        // Obtener bodegas de la tabla bodega con alias para compatibilidad
        $this->db->select('id_bodega as id, nombre_bodega as nombre');
        $query = $this->db->get('bodega');
        return $query->result();
    }

    public function update_configuracion($data) {
        if (empty($data)) {
            return false;
        }
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $this->db->where('id_configuracion', 1);
        return $this->db->update('configuracion', $data);
    }

    public function update_empresa($data) {
        if (empty($data)) {
            return false;
        }
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $this->db->where('id_empresa', 1);
        return $this->db->update('empresa', $data);
    }
}
?>