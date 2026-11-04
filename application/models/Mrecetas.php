<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mrecetas extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}

	public function listadoRecetas()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}

		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$q = "SELECT r.*, fr.nombre_familia_receta
        from receta r
        join familia_receta fr on r.id_familia_receta = fr.id_familia_receta";

		$consulta = $this->db->query($q);
		$sampleData = $consulta->result();
		$data = array();

		foreach ($sampleData as $key) {
			array_push($data, array(
				"id_receta" => $key->id_receta,
				"nombre_receta" => $key->nombre_receta,
				"nombre_familia_receta" => $key->nombre_familia_receta,
				"costo_insumos" => $key->costo_insumos,
				"margen_ganancia" => $key->margen_ganancia,
				"por_costo" => $key->por_costo,
				"por_ganancia" => $key->por_ganancia,
				'precio_venta' => $key->precio_venta,
				"rendimiento" => $key->rendimiento,
				"id_umedida" => $key->id_umedida,
				"id_familia_receta" => $key->id_familia_receta,
				"id_seccion" => $key->id_seccion,
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

	public function llenarCboInsumos()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}

		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$q = "SELECT i.*, um.nombre_umedida
		from insumo i
		join umedida um on i.id_umedida_receta = um.id_umedida where i.vigente = true";
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function llenarCboUnMedida()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}

		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$q = "SELECT * from umedida";
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function llenarFamiliaReceta()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}

		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$q = "SELECT * from familia_receta";
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function llenarSeccionReceta()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}

		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$q = "SELECT * from seccion";
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function getInsumosReceta($id)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}

		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$q = "SELECT dr.*, i.*, um.*, r.preparacion
		from detalle_receta dr
		join receta r on dr.id_receta = r.id_receta
		join insumo i on dr.id_insumo = i.id_insumo
		join umedida um on i.id_umedida_receta = um.id_umedida
		where dr.vigente = true and dr.id_receta =" . (int)$id;
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function grabarReceta($id, $data)
	{
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		if ($id) {
			$this->db->where('id_receta', $id);
			$this->db->update('receta', $data);
			return $id;
		} else {
			$this->db->insert('receta', $data);
			return $this->db->query("SELECT CURRVAL('receta_id_receta_seq'::regclass) as id_receta")->row()->id_receta;
		}
	}

	public function grabarDetalleReceta($idDetalle, $data)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		
		if ($idDetalle > 0) {
			// Actualizar detalle existente
			$this->db->where('id_detalle_receta', $idDetalle);
			$this->db->update('detalle_receta', $data);
			return array(array('id_detalle_receta' => $idDetalle));
		} else {
			// Insertar nuevo detalle
			$this->db->insert('detalle_receta', $data);
			$consulta = $this->db->query("SELECT CURRVAL('detalle_receta_id_detalle_receta_seq'::regclass) as id_detalle_receta");
			return $consulta->result();
		}
	}

	public function updateInsumoDelete($idReceta, $idDetalle, $idInsumo)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$sql = "UPDATE detalle_receta SET vigente = false where id_receta = ? and id_detalle_receta not in ? and id_insumo not in ?";
		// Lista vacía: "not in ()" es SQL inválido, se usa (0) que no coincide con ningún id
		$detalles = $this->listaIds($idDetalle) ?: array(0);
		$insumos = $this->listaIds($idInsumo) ?: array(0);
		$this->db->simple_query($this->db->compile_binds($sql, array((int)$idReceta, $detalles, $insumos)));
		return 1;
	}

	public function grabarNuevaFamiliaReceta($id, $data)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}

		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		if ($id) {
			$this->db->where('id_familia_receta', $id);
			$this->db->update('familia_receta', $data);
			$consulta = $this->db->query("select " . (int)$id . " as id_familia_receta");
		} else {
			$this->db->insert('familia_receta', $data);
			$consulta = $this->db->query("SELECT CURRVAL('familia_receta_id_familia_receta_seq'::regclass) as id_familia_receta");
		}
		return $consulta->result();
	}

	public function grabarNuevaSeccion($id, $data)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}

		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		if ($id) {
			$this->db->where('id_seccion', $id);
			$this->db->update('seccion', $data);
			$consulta = $this->db->query("select " . (int)$id . " as id_seccion");
		} else {
			$this->db->insert('seccion', $data);
			$consulta = $this->db->query("SELECT CURRVAL('seccion_id_seccion_seq'::regclass) as id_seccion");
		}
		return $consulta->result();
	}

	// Convierte la lista '1,2,3' en un array de enteros para usar con IN ?
	private function listaIds($lista)
	{
		return array_map('intval', array_filter(is_array($lista) ? $lista : explode(',', (string)$lista), 'strlen'));
	}
}