<?php
defined('BASEPATH') or exit('No direct script access allowed');

class McanalDeVenta extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}

	public function ListadoCanalDeVenta()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$q = "SELECT c.id_canal_venta, c.nombre_canal_venta, c.vigente, c.cortesia, l.nombre_lprecio
			from canal_venta c 
			join lprecio l on c.id_lprecio=l.id_lprecio
			order by c.id_canal_venta asc";

		$consulta = $this->db->query($q);
		$sampleData = $consulta->result();
		$data = array();

		foreach ($sampleData as $key) {
			array_push($data, array(
				"id_canal_venta" => $key->id_canal_venta,
				"nombre_canal_venta" => $key->nombre_canal_venta,
				"vigente" => ($key->vigente == 't' ? 'SI' : 'NO'),
				"cortesia" => ($key->cortesia == 't' ? 'SI' : 'NO'),
				"nombre_lprecio" => $key->nombre_lprecio,
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

	public function grabarNuevoCanalDeVenta($id, $data)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		if ($id) {
			$this->db->where('id_canal_venta', $id);
			$this->db->update('canal_venta', $data);
			$consulta = $this->db->query("select " . $id . " as id_canal_venta");
		} else {
			$this->db->insert('canal_venta', $data);
			$consulta = $this->db->query("SELECT CURRVAL('canal_venta_id_canal_venta_seq'::regclass) as id_canal_venta");
		}
		return $consulta->result();
	}
}
