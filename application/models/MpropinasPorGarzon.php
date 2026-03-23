<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MpropinasPorGarzon extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}

	public function resumenDePropinasEntreFechas($desde, $hasta)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}

		$jsDateTS = strtotime($desde);
		if ($jsDateTS !== false) {
			$de = date('Y-m-d', $jsDateTS);
		}

		$jsDateTS = strtotime($hasta);
		if ($jsDateTS !== false) {
			$a = date('Y-m-d', $jsDateTS);
		}

		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$q = "SELECT v.id_caja, to_char(v.fecha, 'DD/MM/YYYY') as fecha, sum(v.valor_propina) as total_propina, u.nombre_usuario, fp.nombre_fpago from venta v
        join usuario u on v.id_usuario_garzon=u.id_usuario
        join fpago fp on v.id_fpagopropina=fp.id_fpago
        where v.fecha between '" . $de . "' and '" . $a . "'
        group by v.id_caja, v.fecha, u.nombre_usuario, fp.nombre_fpago
        order by v.id_caja, v.fecha,  u.nombre_usuario, fp.nombre_fpago asc";

		$consulta = $this->db->query($q);
		$sampleData = $consulta->result();
		$data = array();

		foreach ($sampleData as $key) {
			array_push($data, array(
				"id_caja" => $key->id_caja,
				"fecha" => $key->fecha,
				"nombre_usuario" => $key->nombre_usuario,
				"total_propina" => $key->total_propina,
				"nombre_fpago" => $key->nombre_fpago
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

	public function resumenTotalPropinaPorGarzon($desde, $hasta)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}

		$jsDateTS = strtotime($desde);
		if ($jsDateTS !== false) {
			$de = date('Y-m-d', $jsDateTS);
		}

		$jsDateTS = strtotime($hasta);
		if ($jsDateTS !== false) {
			$a = date('Y-m-d', $jsDateTS);
		}

		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$q = "SELECT u.nombre_usuario, sum(v.valor_propina) as total_propina from venta v
        join usuario u on v.id_usuario_garzon=u.id_usuario
        where v.fecha between '" . $de . "' and '" . $a . "'
        group by u.nombre_usuario
        order by u.nombre_usuario asc";
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function resumenTotalPropinaPorFormaDePago($desde, $hasta)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}

		$jsDateTS = strtotime($desde);
		if ($jsDateTS !== false) {
			$de = date('Y-m-d', $jsDateTS);
		}

		$jsDateTS = strtotime($hasta);
		if ($jsDateTS !== false) {
			$a = date('Y-m-d', $jsDateTS);
		}

		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$q = "SELECT fp.nombre_fpago, sum(v.valor_propina) as total_propina from venta v
        join fpago fp on v.id_fpagopropina=fp.id_fpago
        where v.fecha between '" . $de . "' and '" . $a . "'
        group by fp.nombre_fpago
        order by fp.nombre_fpago asc";
		$query = $this->db->query($q)->result();
		return $query;
	}
}