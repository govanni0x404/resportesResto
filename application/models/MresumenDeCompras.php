<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MresumenDeCompras extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}

	public function resumenDeCompras($desde, $hasta)
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

        $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$q = "SELECT c.id_compra, p.nombre_proveedor, td.nombre_tdocumento, c.numero_documento, to_char(c.fecha, 'DD-MM-YYYY') as fecha, c.total
			from compra c 
			join proveedor p on c.id_proveedor = p.id_proveedor
			join tdocumento td on c.id_tdocumento = td.id_tdocumento
			where fecha between '" . $de . "' and '" . $a . "'
			order by c.id_compra desc";

		$consulta = $this->db->query($q);
		$sampleData = $consulta->result();
		$data = array();

		foreach ($sampleData as $key) {
			array_push($data, array(
				// "btn_compras" => '<button onclick="resumenComprasPDF(' . $key->id_compra . ')" type="submit" class="btn btn-danger"><i class="fa fa-file-text-o"></i></button>',
				"id_compra" => $key->id_compra,
				"nombre_proveedor" => $key->nombre_proveedor,
				"nombre_tdocumento" => $key->nombre_tdocumento,
				'numero_documento' => $key->numero_documento,
				'fecha' => $key->fecha,
				'total' => $key->total,
				"btnVerDetalleCompras" => '<button type="button" onclick="detalleFacturas(' . $key->id_compra . ')" class="btn btn-labeled btn-success m-b-5" data-toggle="modal" data-target="#modalDetalleFacturas"><span class="btn-label"><i class="glyphicon glyphicon-ok"></i></span>Ver Detalle</button>',
				"test" => "",

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

	public function detalleFacturas($id_compra)
	{
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        
        $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$q = "SELECT i.nombre_insumo, dc.cantidad, round(dc.bruto_unitario) as bruto_unitario, round(dc.bruto_total) as total
			from detalle_compra dc 
			join insumo i on dc.id_insumo = i.id_insumo
			where dc.id_compra = $id_compra";

		$consulta = $this->db->query($q);
		$sampleData = $consulta->result();
		$data = array();

		foreach ($sampleData as $key) {
			array_push($data, array(
				"nombre_insumo" => $key->nombre_insumo,
				"cantidad" => $key->cantidad,
				"bruto_unitario" => $key->bruto_unitario,
				'total' => $key->total,
				"test" => "",

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

	public function resumenDeComprasPDF()
	{
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        
        $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$q = "SELECT c.id_compra, p.nombre_proveedor, td.nombre_tdocumento, c.numero_documento, to_char(c.fecha, 'DD-MM-YYYY') as fecha, c.total
			from compra c 
			join proveedor p on c.id_proveedor = p.id_proveedor
			join tdocumento td on c.id_tdocumento = td.id_tdocumento
			
			order by c.id_compra desc";

		$consulta = $this->db->query($q);
		return $consulta->result();
	}
}