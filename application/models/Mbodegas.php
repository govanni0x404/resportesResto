<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mbodegas extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}

	public function ListadoDeBodegas()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$q = "SELECT b.id_bodega, b.nombre_bodega from bodega b order by b.id_bodega asc";

		$consulta = $this->db->query($q);
		$sampleData = $consulta->result();
		$data = array();

		foreach ($sampleData as $key) {
			array_push($data, array(
				"id_bodega" => $key->id_bodega,
				"nombre_bodega" => $key->nombre_bodega,
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

	public function grabarNuevaBodega($id, $data)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		if ($id) {
			$this->db->where('id_bodega', $id);
			$this->db->update('bodega', $data);
			$consulta = $this->db->query("select " . $id . " as id_bodega");
		} else {
			$this->db->insert('bodega', $data);
			$consulta = $this->db->query("SELECT CURRVAL('bodega_id_bodega_seq'::regclass) as id_bodega");
		}
		return $consulta->result();
	}
}