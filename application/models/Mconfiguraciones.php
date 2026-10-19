<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mconfiguraciones extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    public function get_configuracion() {
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $query = $this->db->get('configuracion');
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
        $existente = $this->db->select('id_configuracion')->get('configuracion')->row();
        if (!$existente) {
            return false;
        }
        $this->db->where('id_configuracion', $existente->id_configuracion);
        return $this->db->update('configuracion', $data);
    }

    /**
     * Columnas de acceso a Cloud Apps en configuracion. terminal_dte es nueva (la caja web
     * la necesita); usuario/clave ya existen en las bases del POS, pero no en todas.
     */
    public function asegurar_columnas_dte() {
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $existentes = array();
        foreach ($this->db->query("SELECT column_name FROM information_schema.columns
            WHERE table_schema = 'public' AND table_name = 'configuracion'
            AND column_name IN ('usuario_dte', 'clave_usuario_dte', 'terminal_dte')")->result() as $c) {
            $existentes[] = $c->column_name;
        }
        $tipos = array('usuario_dte' => 'varchar(100)', 'clave_usuario_dte' => 'varchar(100)', 'terminal_dte' => 'varchar(60)');
        foreach ($tipos as $col => $tipo) {
            if (!in_array($col, $existentes)) {
                $this->db->query("ALTER TABLE configuracion ADD COLUMN IF NOT EXISTS $col $tipo");
            }
        }
    }

    /** Tipos de documento electrónicos (con código SII) para consultar sus folios. */
    public function get_documentos_dte() {
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        return $this->db->query('SELECT id_tdocumento, nombre_tdocumento, cod_sii FROM tdocumento
            WHERE cod_sii IN (33, 34, 39, 41, 43, 46, 52, 56, 61, 110, 111, 112)
            ORDER BY cod_sii, id_tdocumento')->result();
    }

    public function update_empresa($data) {
        if (empty($data)) {
            return false;
        }
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $existente = $this->db->select('id_empresa')->get('empresa')->row();
        if (!$existente) {
            return $this->db->insert('empresa', $data);
        }
        $this->db->where('id_empresa', $existente->id_empresa);
        return $this->db->update('empresa', $data);
    }
}
?>