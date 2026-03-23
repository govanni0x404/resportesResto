<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MsubFamilias extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function ListadoDeSubFamilias()
    {
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
		  redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $q = "SELECT s.id_subfamilia, s.nombre_subfamilia, f.nombre_familia from subfamilia s 
			join familia f on s.id_familia=f.id_familia
			order by s.id_subfamilia asc";

        $consulta = $this->db->query($q);
        $sampleData = $consulta->result();
        $data = array();

        foreach ($sampleData as $key) {
            array_push($data, array(
                "id_subfamilia" => $key->id_subfamilia,
                "nombre_subfamilia" => $key->nombre_subfamilia,
                "nombre_familia" => $key->nombre_familia,
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

    public function grabarNuevaSubFamilia($id, $data)
    {
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
		  redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
        if ($id) {
            $this->db->where('id_subfamilia', $id);
            $this->db->update('subfamilia', $data);
            $consulta = $this->db->query("select " . $id . " as id_subfamilia");
        } else {
            $this->db->insert('subfamilia', $data);
            $consulta = $this->db->query("SELECT CURRVAL('subfamilia_id_subfamilia_seq'::regclass) as id_subfamilia");
        }
        return $consulta->result();
    }

    public function ListadoSubFamiliasXFamilias($id_familia)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
          }
          $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $query = $this->db->query("SELECT id_subfamilia, nombre_subfamilia from subfamilia where id_familia= $id_familia order by id_familia");
        return $query->result();
    }
}