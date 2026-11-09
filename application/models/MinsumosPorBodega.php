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
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());//redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$q = "SELECT * from bodega";

		$query = $this->db->query($q)->result();
		return $query;
	}

	public function inventarioPorBodega($id_bodega, $id_grupo_insumo)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());//redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		/*	$q = "SELECT i.id_insumo, i.nombre_insumo, round(sum(si.saldo)) as saldo, gi.nombre_grupo_insumo, b.nombre_bodega
																		 from stock_insumo si
																		 join insumo i on si.id_insumo = i.id_insumo
																		 join bodega b on si.id_bodega=b.id_bodega
																		 join grupo_insumo gi on i.id_grupo_insumo = gi.id_grupo_insumo
																		 group by i.id_insumo, gi.id_grupo_insumo, b.nombre_bodega
																		 order by nombre_insumo";*/

		// Todas las bodegas: una fila por insumo con el stock total y cuántas bodegas lo tienen (el detalle por bodega
		// se ve en el modal). Una bodega: solo sus filas.
		$q = "SELECT i.id_insumo, i.nombre_insumo, u.nombre_umedida, round(sum(si.saldo)) as saldo, gi.nombre_grupo_insumo,
				count(DISTINCT si.id_bodega) AS bodegas, min(b.nombre_bodega) AS nombre_bodega
			from stock_insumo si
			join insumo i on si.id_insumo = i.id_insumo
			join bodega b on b.id_bodega = si.id_bodega
			join umedida u on i.id_umedida_inventario = u.id_umedida
			join grupo_insumo gi on i.id_grupo_insumo = gi.id_grupo_insumo
			where 1=1";
		if ($id_bodega > 0) {
			$q = $q . " and b.id_bodega=" . (int)$id_bodega;
		}
		if ($id_grupo_insumo > 0) {
			$q = $q . " and i.id_grupo_insumo=" . (int)$id_grupo_insumo;
		}
		$q = $q . " group by i.id_insumo, i.nombre_insumo, gi.nombre_grupo_insumo, u.nombre_umedida order by i.nombre_insumo";

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
					"nombre_bodega" => (int)$key->bodegas > 1 ? (int)$key->bodegas . ' bodegas' : $key->nombre_bodega,
					"bodegas" => (int)$key->bodegas,
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
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());//redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$q = "SELECT i.nombre_insumo, round(si.saldo) as saldo, b.nombre_bodega
			from stock_insumo si
			join insumo i on si.id_insumo = i.id_insumo
			join bodega b on si.id_bodega = b.id_bodega
			where si.id_insumo = " . (int)$id_insumo . "
			order by b.nombre_bodega";

		$query = $this->db->query($q)->result();
		return $query;
	}

	public function descuentosInsumos()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());//redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
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