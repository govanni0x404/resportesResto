<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MrecetaProduccion extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function ListadoRecetasProduccion()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        
        $q = "SELECT 
                rp.id_receta_produccion,
                rp.nombre_receta_produccion,
                rp.cantidad,
                i.nombre_insumo as insumo_elaborado,
                um.nombre_umedida,
                rp.id_insumo,
                rp.id_bodega,
                rp.id_umedida,
                rp.vigente
            FROM receta_produccion rp
            LEFT JOIN insumo i ON rp.id_insumo = i.id_insumo
            LEFT JOIN umedida um ON rp.id_umedida = um.id_umedida
            WHERE rp.vigente = true
            ORDER BY rp.nombre_receta_produccion ASC";

        $consulta = $this->db->query($q);
        $sampleData = $consulta->result();
        $data = array();

        foreach ($sampleData as $key) {
            array_push($data, array(
                "id_receta_produccion" => $key->id_receta_produccion,
                "nombre_receta_produccion" => $key->nombre_receta_produccion,
                "cantidad" => $key->cantidad,
                "insumo_elaborado" => $key->insumo_elaborado,
                "nombre_umedida" => $key->nombre_umedida,
                "id_insumo" => $key->id_insumo,
                "id_bodega" => $key->id_bodega,
                "id_umedida" => $key->id_umedida,
                "responsive" => ""
            ));
        }

        $results = array(
            "sEcho" => 1,
            "iTotalRecords" => count($data),
            "iTotalDisplayRecords" => count($data),
            "aaData" => $data
        );

        return $results;
    }

    public function grabarRecetaProduccion($id, $data)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        
        if ($id && $id > 0) {
            // Actualizar
            $this->db->where('id_receta_produccion', $id);
            $this->db->update('receta_produccion', $data);
            return array(
                array('id_receta_produccion' => $id)
            );
        } else {
            // Insertar
            $this->db->insert('receta_produccion', $data);
            $consulta = $this->db->query("SELECT CURRVAL('receta_produccion_id_receta_produccion_seq'::regclass) as id_receta_produccion");
            return $consulta->result_array();
        }
    }

    public function buscarRecetaPorNombre($nombre)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        
        $q = "SELECT * FROM receta_produccion 
              WHERE UPPER(nombre_receta_produccion) = UPPER('" . $nombre . "')
              AND vigente = true";
        
        $consulta = $this->db->query($q);
        return $consulta->result();
    }

    public function obtenerDetalleReceta($id_receta_produccion)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        
        $q = "SELECT 
                drp.id_detalle_receta_produccion,
                drp.id_receta_produccion,
                drp.id_insumo,
                i.nombre_insumo,
                drp.cantidad,
                drp.id_umedida,
                um.nombre_umedida
            FROM detalle_receta_produccion drp
            JOIN insumo i ON drp.id_insumo = i.id_insumo
            JOIN umedida um ON drp.id_umedida = um.id_umedida
            WHERE drp.id_receta_produccion = " . intval($id_receta_produccion);

        $consulta = $this->db->query($q);
        $sampleData = $consulta->result();
        $data = array();

        foreach ($sampleData as $key) {
            array_push($data, array(
                "id_detalle_receta_produccion" => $key->id_detalle_receta_produccion,
                "id_insumo" => $key->id_insumo,
                "nombre_insumo" => $key->nombre_insumo,
                "cantidad" => $key->cantidad,
                "nombre_umedida" => $key->nombre_umedida,
                "responsive" => ""
            ));
        }

        $results = array(
            "sEcho" => 1,
            "iTotalRecords" => count($data),
            "iTotalDisplayRecords" => count($data),
            "aaData" => $data
        );

        return $results;
    }

    public function grabarDetalleReceta($data)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $this->db->insert('detalle_receta_produccion', $data);
        return $this->db->insert_id();
    }

    public function eliminarDetalleReceta($id_detalle)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $this->db->where('id_detalle_receta_produccion', $id_detalle);
        return $this->db->delete('detalle_receta_produccion');
    }
}