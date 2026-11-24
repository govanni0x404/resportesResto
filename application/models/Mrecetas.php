<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mrecetas extends CI_Model
{
	/**
	 * v1.34.0: costo neto de 1 unidad de receta del insumo (alias i), igual que el trigger del POS al comprar
	 * (actualizar_costo_insumo_receta: costo_pp = costo_neto_ppp / umedida_receta).
	 */
	const SQL_COSTO_RECETA = 'COALESCE(i.costo_neto_ppp, 0) / COALESCE(NULLIF(i.umedida_receta, 0), 1)';

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
		// v1.34.0: costo_receta = costo de 1 unidad de receta (ej. 1 gramo de un insumo comprado por kilo). Antes la pantalla
		// multiplicaba los gramos por el costo del kilo (200 gr de $10.000/kg daban $2.000.000).
		$q = "SELECT i.*, um.nombre_umedida, " . self::SQL_COSTO_RECETA . " AS costo_receta
		from insumo i
		join umedida um on i.id_umedida_receta = um.id_umedida where i.vigente = true
		order by i.nombre_insumo";
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
		$q = "SELECT dr.*, i.*, um.*, r.preparacion, " . self::SQL_COSTO_RECETA . " AS costo_receta
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

	/**
	 * Guarda la receta y reemplaza sus insumos en una sola transacción. Antes se marcaban los insumos anteriores como no
	 * vigentes y después se insertaban los nuevos, sin transacción: si un insumo fallaba la receta quedaba sin insumos.
	 * $reemplazar = false (la pantalla no mandó la lista) deja los insumos como estaban.
	 */
	public function grabarRecetaCompleta($id, $cabecera, $lineas, $reemplazar)
	{
		$this->load->helper('tenant');
		$db = get_tenant_db($this);
		$debug = $db->db_debug;
		$db->db_debug = false;
		$db->trans_begin();
		try {
			$ok = function ($r) use ($db) {
				if ($r === false) { $e = $db->error(); throw new Exception('Error de base de datos: ' . $e['message']); }
				return $r;
			};
			if ($id > 0) {
				$ok($db->where('id_receta', (int)$id)->update('receta', $cabecera));
				$idReceta = (int)$id;
			} else {
				$ok($db->insert('receta', $cabecera));
				$idReceta = (int)$ok($db->query("SELECT CURRVAL('receta_id_receta_seq'::regclass) AS id"))->row()->id;
			}
			if ($reemplazar) {
				$ok($db->query('UPDATE detalle_receta SET vigente = false WHERE id_receta = ?', array($idReceta)));
				foreach ($lineas as $l) {
					// v1.34.0: el costo sale del insumo en la base (por unidad de receta), no de lo que mandó la pantalla
					$c = $ok($db->query('SELECT ' . self::SQL_COSTO_RECETA . ' AS costo, (i.esreceta IS TRUE) AS sub FROM insumo i WHERE i.id_insumo = ?',
						array((int)$l['id_insumo'])))->row();
					if (!$c) throw new Exception('Un insumo de la receta ya no existe (id ' . (int)$l['id_insumo'] . ')');
					$costoPp = (float)$c->costo;
					$ok($db->insert('detalle_receta', array(
						'id_receta' => $idReceta,
						'id_insumo' => (int)$l['id_insumo'],
						'cantidad' => $l['cantidad'],
						'costo_pp' => $costoPp,
						'costo_total' => round((float)$l['cantidad'] * $costoPp),
						'es_receta' => ($c->sub === 't' || $c->sub === true) ? 't' : 'f',
						'vigente' => 't',
						'um_receta' => 0,
					)));
				}
			}
			// v1.34.0: costo de la receta, de la porción y margen, igual que el POS (recalculo_rendimiento_insumo). Antes la
			// web no los calculaba: la lista de recetas mostraba el costo viejo hasta la próxima compra de un insumo.
			$this->recalcularCostosReceta($db, $idReceta, $ok);
			$db->trans_commit();
			$db->db_debug = $debug;
			return $idReceta;
		} catch (Throwable $e) {
			$db->trans_rollback();
			$db->db_debug = $debug;
			log_message('error', 'Recetas: ' . $e->getMessage());
			throw new Exception(strpos($e->getMessage(), 'Error de base de datos') === 0 ? 'No se pudo guardar la receta. Intenta de nuevo.' : $e->getMessage());
		}
	}

	/** v1.34.0: costo de insumos, costo de la porción, margen y porcentajes de la receta (fórmulas del POS). */
	private function recalcularCostosReceta($db, $idReceta, $ok)
	{
		$ok($db->query('UPDATE receta r SET costo_insumos = COALESCE((SELECT sum(d.costo_total) FROM detalle_receta d
				WHERE d.id_receta = r.id_receta AND d.vigente = true), 0) WHERE r.id_receta = ?', array((int)$idReceta)));
		$ok($db->query('UPDATE receta SET costo_porcion = costo_insumos / NULLIF(rendimiento, 0) WHERE id_receta = ?', array((int)$idReceta)));
		$iva = '(1 + COALESCE((SELECT c.por_iva FROM configuracion c LIMIT 1), 19) / 100.0)';
		$ok($db->query("UPDATE receta SET margen_ganancia = precio_venta / $iva - costo_porcion,
				por_ganancia = CASE WHEN precio_venta > 0 AND costo_porcion > 0 THEN 100 - (costo_porcion / (precio_venta / $iva)) * 100 ELSE por_ganancia END
			WHERE id_receta = ?", array((int)$idReceta)));
		$ok($db->query('UPDATE receta SET por_costo = 100 - por_ganancia WHERE id_receta = ? AND por_ganancia IS NOT NULL', array((int)$idReceta)));
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
			// 16-11-2026 (QA): si no se actualizó ninguna fila el registro no existe: error (404) y no "éxito"
			if ($this->db->affected_rows() < 1) {
				set_status_header(404);
				return array('error' => 'El registro ya no existe (pudo haberlo borrado otra persona). Recarga la página.');
			}
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
			// 16-11-2026 (QA): si no se actualizó ninguna fila el registro no existe: error (404) y no "éxito"
			if ($this->db->affected_rows() < 1) {
				set_status_header(404);
				return array('error' => 'El registro ya no existe (pudo haberlo borrado otra persona). Recarga la página.');
			}
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