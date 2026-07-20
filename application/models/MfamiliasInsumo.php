<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MfamiliasInsumo extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function ListadoFamiliasInsumo()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $q = "SELECT g.id_grupo_insumo, g.nombre_grupo_insumo from grupo_insumo g order by g.id_grupo_insumo asc";

        $consulta = $this->db->query($q);
        $sampleData = $consulta->result();
        $data = array();

        foreach ($sampleData as $key) {
            array_push($data, array(
                "id_grupo_insumo" => $key->id_grupo_insumo,
                "nombre_grupo_insumo" => $key->nombre_grupo_insumo,
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

    public function grabarNuevaFamiliaInsumo($id, $data)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        if ($id) {
            $this->db->where('id_grupo_insumo', $id);
            $this->db->update('grupo_insumo', $data);
            return array(
                'success' => true,
                'id_grupo_insumo' => (int)$id,
                'message' => 'Familia/Grupo Insumo actualizado exitosamente'
            );
        } else {
            $this->db->insert('grupo_insumo', $data);
            $consulta = $this->db->query("SELECT CURRVAL('grupo_insumo_id_grupo_insumo_seq'::regclass) as id_grupo_insumo");
            $resultado = $consulta->row();
            if ($resultado) {
                return array(
                    'success' => true,
                    'id_grupo_insumo' => (int)$resultado->id_grupo_insumo,
                    'message' => 'Familia/Grupo Insumo grabado exitosamente'
                );
            }
        }
        return array(
            'success' => false,
            'message' => 'No fue posible grabar la familia/grupo de insumo'
        );
    }

    public function ListarFamiliasInsumo()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $q = "SELECT id_grupo_insumo, nombre_grupo_insumo from grupo_insumo order by nombre_grupo_insumo asc";
        $query = $this->db->query($q)->result();
        return $query;
    }
}
