<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Minsumos extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}

	public function listadoInsumos()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}

		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$q = "SELECT i.*, (select im.nombre_impuesto from impuesto im where id_impuesto = i.id_impto_iva) as iva, (select im.nombre_impuesto from impuesto im where id_impuesto = i.id_impto_adicional) as iva_adicional,
			(select um.nombre_umedida from umedida um where um.id_umedida = i.id_umedida_inventario) as u_medida_inv, (select um.nombre_umedida from umedida um where um.id_umedida = i.id_umedida_receta) as u_medida_receta,
			gi.nombre_grupo_insumo, COALESCE(dit.cantidad, 0) as inv_c_caja
			from insumo i
			join grupo_insumo gi on i.id_grupo_insumo = gi.id_grupo_insumo
			left outer join detalle_inventario_turno dit on i.id_insumo = dit.id_insumo";

		$consulta = $this->db->query($q);
		$sampleData = $consulta->result();
		$data = array();

		foreach ($sampleData as $key) {
			array_push($data, array(
				"id_insumo" => $key->id_insumo,
				"cbarra" => $key->cbarra,
				"nombre_insumo" => $key->nombre_insumo,
				"nombre_grupo_insumo" => $key->nombre_grupo_insumo,
				"umedida_inventario" => $key->umedida_inventario,
				"u_medida_inv" => $key->u_medida_inv,
				"umedida_receta" => $key->umedida_receta,
				"u_medida_receta" => $key->u_medida_receta,
				"iva" => $key->iva,
				"iva_adicional" => $key->iva_adicional,
				"costo_neto_ppp" => $key->costo_neto_ppp,
				"costo_bruto_ppp" => $key->costo_bruto_ppp,
				"merma" => $key->merma,
				"esreceta" => $key->esreceta == 't' ?  'SI' : 'NO',
				"inventario_cierre_turno" => $key->inventario_cierre_turno,
				"precio_ucompra" => $key->precio_ucompra,
				"inv_c_caja" => $key->inv_c_caja,
				"observaciones" => $key->observaciones,
				"responsive" => ""
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

	public function llenarCboGrupoInsumo()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}

		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$q = "SELECT * from grupo_insumo order by nombre_grupo_insumo asc";
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function llenarCboImpto()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}

		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$q = "SELECT * from impuesto order by nombre_impuesto asc";
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function llenarCboUnMedida()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}

		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$q = "SELECT * from umedida order by nombre_umedida asc";
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function getSiguienteInsumo()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}

		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$consulta = $this->db->query("SELECT last_value + 1 as id_insumo from insumo_id_insumo_seq");
		return $consulta->result();
	}

	public function llenarCboRecetaProduccion($id)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}

		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$q = "SELECT * from receta_produccion where id_insumo =" . $id;
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function grabarInsumo($id, $data)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}

		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		if ($id) {
			$this->db->where('id_insumo', $id);
			$this->db->update('insumo', $data);
			$consulta = $this->db->query("select " . $id . " as id_insumo");
		} else {
			$this->db->insert('insumo', $data);
			$consulta = $this->db->query("SELECT CURRVAL('insumo_id_insumo_seq'::regclass) as id_insumo");
		}
		return $consulta->result();
	}

	public function buscarInsumo($cbarra)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$consulta = $this->db->query("SELECT * from insumo where cbarra = '" . $cbarra . "'");
		return $consulta->result();
	}

	public function listadoStockInsumo($id)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$q = "SELECT si.id_insumo, b.nombre_bodega, si.saldo, um.nombre_umedida
			from stock_insumo si
			join bodega b on si.id_bodega = b.id_bodega
			join insumo i on si.id_insumo = i.id_insumo
			join umedida um on i.id_umedida_inventario = um.id_umedida
			where si.id_insumo =" . $id;

		$consulta = $this->db->query($q);
		$sampleData = $consulta->result();
		$data = array();

		foreach ($sampleData as $key) {
			array_push($data, array(
				"id_insumo" => $key->id_insumo,
				"nombre_bodega" => $key->nombre_bodega,
				"saldo" => $key->saldo,
				"nombre_umedida" => $key->nombre_umedida,
				"responsive" => ""
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

	public function listadoHistoricoCompras($id)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}

		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$q = "SELECT i.nombre_insumo, um.nombre_umedida, to_char(fecha, 'DD/MM/YYYY') as fecha_compra, p.nombre_proveedor, dc.cantidad, dc.bruto_unitario, td.nombre_tdocumento, c.numero_documento
			from compra c
			join detalle_compra dc on c.id_compra = dc.id_compra
			join insumo i on dc.id_insumo = i.id_insumo
			join umedida um on i.id_umedida_inventario = um.id_umedida
			join proveedor p on c.id_proveedor = p.id_proveedor
			join tdocumento td on c.id_tdocumento = td.id_tdocumento
			where dc.id_insumo =" . $id;

		$consulta = $this->db->query($q);
		$sampleData = $consulta->result();
		$data = array();

		foreach ($sampleData as $key) {
			array_push($data, array(
				"fecha_compra" => $key->fecha_compra,
				"nombre_proveedor" => $key->nombre_proveedor,
				"cantidad" => $key->cantidad,
				"bruto_unitario" => $key->bruto_unitario,
				"nombre_tdocumento" => $key->nombre_tdocumento,
				"numero_documento" => $key->numero_documento,
				"responsive" => ""
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

	public function listadoInsumosReceta($id)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}

		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$q = "SELECT i.id_insumo, i.nombre_insumo, drp.cantidad, um.nombre_umedida
		from detalle_receta_produccion drp 
		join receta_produccion rp on drp.id_receta_produccion = rp.id_receta_produccion
		join insumo i on drp.id_insumo = i.id_insumo
		join umedida um on drp.id_umedida = um.id_umedida
		where rp.id_insumo =" . $id;

		$consulta = $this->db->query($q);
		$sampleData = $consulta->result();
		$data = array();

		foreach ($sampleData as $key) {
			array_push($data, array(
				"nombre_insumo" => $key->nombre_insumo,
				"cantidad" => $key->cantidad,
				"nombre_umedida" => $key->nombre_umedida,
				"responsive" => ""
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

	public function listadoInsumoProduccion($id)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}

		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$q = "SELECT p.cantidad, um.nombre_umedida, p.costo_unitario, p.costo_total, to_char(p.fecha, 'DD/MM/YYYY') as fecha_proceso, to_char(p.fecha_transaccion,'DD/MM/YYYY HH24:MM:SS') as fecha_hora
		from produccion p
		join insumo i on p.id_insumo = i.id_insumo
		join umedida um on i.id_umedida_inventario = um.id_umedida
		where i.id_insumo =" . $id;

		$consulta = $this->db->query($q);
		$sampleData = $consulta->result();
		$data = array();

		foreach ($sampleData as $key) {
			array_push($data, array(
				"cantidad" => $key->cantidad,
				"nombre_umedida" => $key->nombre_umedida,
				"costo_unitario" => $key->costo_unitario,
				"costo_total" => $key->costo_total,
				"fecha_proceso" => $key->fecha_proceso,
				"fecha_hora" => $key->fecha_hora,
				"responsive" => ""
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

	public function listadoRecetas($id)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}

		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$q = "SELECT r.nombre_receta, r.rendimiento, um.nombre_umedida
		from receta r 
		join detalle_receta dr on r.id_receta = dr.id_receta
		join insumo i on dr.id_insumo = i.id_insumo
		join umedida um on i.id_umedida_receta = um.id_umedida
		where dr.id_insumo =" . $id;

		$consulta = $this->db->query($q);
		$sampleData = $consulta->result();
		$data = array();

		foreach ($sampleData as $key) {
			array_push($data, array(
				"nombre_receta" => $key->nombre_receta,
				"rendimiento" => $key->rendimiento,
				"nombre_umedida" => $key->nombre_umedida,
				"responsive" => ""
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

	public function ListarInsumos()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }

     $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    $q = " SELECT i.id_insumo, i.nombre_insumo from insumo i 
    order by i.nombre_insumo asc";
    $query = $this->db->query($q)->result();
    return $query;
  }

  public function ObtenerDatosInsumos($id)
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }

     $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    $q = " SELECT i.id_insumo, i.nombre_insumo from insumo i 
    where i.id_insumo =" . $id;
    $query = $this->db->query($q)->result();
    return $query;
  }

    public function obtenerUnidadMedidaPorId($id)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); 
        }
        
        $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        $q = "SELECT um.nombre_umedida AS unidad_medida 
              FROM insumo i 
              JOIN umedida um ON i.id_umedida_inventario = um.id_umedida 
              WHERE i.id_insumo = " . $id;

        $query = $this->db->query($q);
    
        if ($query->num_rows() > 0) {
            return $query->row_array(); 
        }
        return ['unidad_medida' => ''];
    }
}