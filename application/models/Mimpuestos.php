<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mimpuestos extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function ListadoDeImpuestos()
    {
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        $q = "SELECT i.id_impuesto, i.nombre_impuesto, i.valor, i.observacion, i.codigo_sii
        from impuesto i
        order by i.id_impuesto asc";

        $consulta = $this->db->query($q);
        $sampleData = $consulta->result();
        $data = array();

        foreach ($sampleData as $key) {
            array_push($data, array(
                "id_impuesto" => $key->id_impuesto,
                "nombre_impuesto" => $key->nombre_impuesto,
                "valor" => $key->valor,
                "observacion" => $key->observacion == '' ? '-' : $key->observacion,
                "codigo_sii" => $key->codigo_sii,
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

    public function grabarNuevoImpuesto($id, $data)
    {
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        if ($id) {
            $this->db->where('id_impuesto', $id);
            $this->db->update('impuesto', $data);
            $consulta = $this->db->query("select " . $id . " as id_impuesto");
        } else {
            $this->db->insert('impuesto', $data);
            $consulta = $this->db->query("SELECT CURRVAL('impuesto_id_impuesto_seq'::regclass) as id_impuesto");
        }
        return $consulta->result();
    }
}