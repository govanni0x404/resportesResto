<?php
defined('BASEPATH') or exit('No direct script access allowed');

class McentroCosto extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}

	public function ListadoDeCentroDeCostos()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$q = "SELECT c.id_ccosto, c.nombre_ccosto, c.esingreso from ccosto c order by c.id_ccosto asc";

		$consulta = $this->db->query($q);
		$sampleData = $consulta->result();
		$data = array();

		foreach ($sampleData as $key) {
			array_push($data, array(
				"id_ccosto" => $key->id_ccosto,
				"nombre_ccosto" => $key->nombre_ccosto,
				"esingreso" => ($key->esingreso == 't' ? 'SI' : 'NO'),
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

	public function grabarNuevoCentroCosto($id, $data)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		if ($id) {
			$this->db->where('id_ccosto', $id);
			$this->db->update('ccosto', $data);
			$consulta = $this->db->query("select " . $id . " as id_ccosto");
		} else {
			$this->db->insert('ccosto', $data);
			$consulta = $this->db->query("SELECT CURRVAL('ccosto_id_ccosto_seq'::regclass) as id_ccosto");
		}
		return $consulta->result();
	}
}
