<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MformasDePago extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function ListadoDeFormasDePago()
    {
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        $q = "SELECT fp.id_fpago, fp.nombre_fpago, fp.escortesia, fp.boleta_electronica, fp.vigente, fp.permite_propina
        from fpago fp
        order by fp.id_fpago asc";

        $consulta = $this->db->query($q);
        $sampleData = $consulta->result();
        $data = array();

        foreach ($sampleData as $key) {
            array_push($data, array(
                "id_fpago" => $key->id_fpago,
                "nombre_fpago" => $key->nombre_fpago,
                "escortesia" => ($key->escortesia == 't' ? 'SI' : 'NO'),
                "boleta_electronica" => ($key->boleta_electronica == 't' ? 'SI' : 'NO'),
                "vigente" => ($key->vigente == 't' ? 'SI' : 'NO'),
                "permite_propina" => ($key->permite_propina == 't' ? 'SI' : 'NO'),
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

    public function grabarNuevaFormaDePago($id, $data)
    {
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        if ($id) {
            $this->db->where('id_fpago', $id);
            $this->db->update('fpago', $data);
            $consulta = $this->db->query("select " . $id . " as id_fpago");
        } else {
            $this->db->insert('fpago', $data);
            $consulta = $this->db->query("SELECT CURRVAL('fpago_id_fpago_seq'::regclass) as id_fpago");
        }
        return $consulta->result();
    }
}