<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MinsumosPorBodega extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}

	public function consultaBodegas()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());//redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$q = "SELECT * from bodega";

		$query = $this->db->query($q)->result();
		return $query;
	}

	public function inventarioPorBodega($id_bodega, $id_grupo_insumo)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());//redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		/*	$q = "SELECT i.id_insumo, i.nombre_insumo, round(sum(si.saldo)) as saldo, gi.nombre_grupo_insumo, b.nombre_bodega
																		 from stock_insumo si
																		 join insumo i on si.id_insumo = i.id_insumo
																		 join bodega b on si.id_bodega=b.id_bodega
																		 join grupo_insumo gi on i.id_grupo_insumo = gi.id_grupo_insumo
																		 group by i.id_insumo, gi.id_grupo_insumo, b.nombre_bodega
																		 order by nombre_insumo";*/

		$q = "SELECT i.id_insumo, i.nombre_insumo, u.nombre_umedida, round(sum(si.saldo)) as saldo, gi.nombre_grupo_insumo, b.nombre_bodega
		from stock_insumo si
		join insumo i on si.id_insumo = i.id_insumo
		join bodega b on b.id_bodega=si.id_bodega
		join umedida u on i.id_umedida_inventario=u.id_umedida
		join grupo_insumo gi on i.id_grupo_insumo = gi.id_grupo_insumo
		where 1=1

";
		if ($id_bodega > 0) {
			$q = $q . " and b.id_bodega=$id_bodega";
		}
		if ($id_grupo_insumo > 0) {
			$q = $q . " and i.id_grupo_insumo=$id_grupo_insumo";
		}
		
		$q = $q . " group by i.id_insumo, gi.id_grupo_insumo, b.nombre_bodega, u.nombre_umedida";

		$consulta = $this->db->query($q);
		$sampleData = $consulta->result();
		$data = array();

		foreach ($sampleData as $key) {
			array_push(
				$data,
				array(
					"id_insumo" => $key->id_insumo,
					"nombre_insumo" => $key->nombre_insumo,
					"saldo" => $key->saldo,
					"nombre_umedida" => $key->nombre_umedida,
					"nombre_bodega" => $key->nombre_bodega,
					"nombre_grupo_insumo" => $key->nombre_grupo_insumo,
				)
			);
		}
		$results = array(
			"sEcho" => 1,
			"iTotalRecords" => count($data),
			"iTotalDisplayRecords" => count($data),
			"aaData" => $data
		);

		return $results;
	}

	public function detalleStockInsumos($id_insumo)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());//redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$q = "SELECT i.nombre_insumo, cast(si.saldo as int) as saldo, b.nombre_bodega
			from stock_insumo si
			join insumo i on si.id_insumo = i.id_insumo
			join bodega b on si.id_bodega = b.id_bodega
			where si.id_insumo = $id_insumo";

		$query = $this->db->query($q)->result();
		return $query;
	}

	public function descuentosInsumos()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());//redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$q = "SELECT coalesce(sum((((i.umedida_inventario/i.umedida_receta)*dr.cantidad)/r.rendimiento)*dc.cantidad), 0) as cantidad_salida, i.nombre_insumo, i.id_insumo
		from detalle_receta dr
		left outer join insumo i on dr.id_insumo = i.id_insumo
		left outer  join receta r on dr.id_receta = r.id_receta
		left outer  join producto p on r.id_receta = p.id_receta
		left outer  join detalle_cotizacion dc on p.id_producto = dc.id_producto
		left outer join stock_insumo si on i.id_insumo = si.id_insumo
		left outer join cotizacion co on dc.id_cotizacion = co.id_cotizacion
		where dc.vigente = true and dc.es_merma = false and dc.esmerma = false and dc.eseliminado = false and dc.esanulado = false and co.pagado = false
		group by i.id_insumo
		order by 2";

		$query = $this->db->query($q)->result();
		return $query;
	}
}