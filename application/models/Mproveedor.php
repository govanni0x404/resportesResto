<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mproveedor extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}

	public function ListadoDeProveedores()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$q = "SELECT p.id_proveedor, p.nombre_proveedor, p.rut, p.dv, p.direccion, p.telefono, p.mail, p.giro, p.creditomaximo from proveedor p 
			order by p.id_proveedor asc";

		$consulta = $this->db->query($q);
		$sampleData = $consulta->result();
		$data = array();

		foreach ($sampleData as $key) {
			array_push($data, array(
				"id_proveedor" => $key->id_proveedor,
				"nombre_proveedor" => $key->nombre_proveedor,
				"rut" => $key->rut,
				"dv" => $key->dv,
				"rut_completo" => $key->rut . "-" . $key->dv,
				"direccion" => $key->direccion,
				"telefono" => $key->telefono,
				"mail" => $key->mail,
				"giro" => $key->giro,
				"creditomaximo" => $key->creditomaximo,

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

	public function grabarNuevoProveedor($id, $data)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		if ($id) {
			$this->db->where('id_proveedor', $id);
			$this->db->update('proveedor', $data);
			$consulta = $this->db->query("select " . $id . " as id_proveedor");
		} else {
			$this->db->insert('proveedor', $data);
			$consulta = $this->db->query("SELECT CURRVAL('proveedor_id_proveedor_seq'::regclass) as id_proveedor");
		}
		return $consulta->result();
	}

	public function busquedaProveedorPorRut($rut)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$q = "SELECT p.id_proveedor, p.nombre_proveedor, p.rut, p.dv, p.direccion, p.telefono, p.mail, p.giro from proveedor p 
			where p.rut='" . $rut . "' order by p.id_proveedor";
		$consulta = $this->db->query($q);
		return $consulta->result();
	}

	public function ListarProveedores()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$q = "SELECT id_proveedor, nombre_proveedor from proveedor order by nombre_proveedor asc";
		$query = $this->db->query($q)->result();
		return $query;
	}
}
