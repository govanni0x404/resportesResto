<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Magregados extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}

	public function ListadoDeAgregados()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		// v1.35.1: la categoría es la del producto agregado (migración 20261124150000)
		$v135 = (bool)$this->db->query("SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'categoria_agregado_web'")->row()
			&& (bool)$this->db->query("SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'producto' AND column_name = 'id_categoria_agregado_web'")->row();
		$q = "SELECT a.id_agregado, a.id_producto_maestro, a.id_producto, round(a.cantidad) as cantidad, a.valor, a.vigente, p.nombre_producto, pm.nombre_producto as producto_maestro, a.usar_destino_impresion,
			" . ($v135 ? "COALESCE(p.id_categoria_agregado_web, 0) AS id_categoria, COALESCE(c.nombre, '') AS categoria"
				: "0 AS id_categoria, '' AS categoria") . "
        from agregados a
        join producto p on p.id_producto=a.id_producto
        join producto pm on pm.id_producto=a.id_producto_maestro
        " . ($v135 ? "LEFT JOIN categoria_agregado_web c ON c.id_categoria_agregado = p.id_categoria_agregado_web AND c.eliminado IS NOT TRUE" : "") . "
        order by a.id_agregado asc";

		$consulta = $this->db->query($q);
		$sampleData = $consulta->result();
		$data = array();

		foreach ($sampleData as $key) {
			array_push($data, array(
				"id_agregado" => $key->id_agregado,
				"id_producto_maestro" => $key->id_producto_maestro,
				"id_producto" => $key->id_producto,
				"producto_maestro" => $key->producto_maestro,
				"nombre_producto" => $key->nombre_producto,
				"cantidad" => $key->cantidad,
				"valor" => $key->valor,
				"vigente" => ($key->vigente == 't' ? 'SI' : 'NO'),
				"usar_destino_impresion" => ($key->usar_destino_impresion == 't' ? 'SI' : 'NO'),
				"id_categoria" => (int)$key->id_categoria,    // v1.35.0
				"categoria" => (string)$key->categoria,
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

	public function grabarNuevoAgregado($id, $data)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}

		$this->load->helper('tenant'); $this->db = get_tenant_db($this);

		if ($id) {
			// Actualizar existente por id_agregado
			$this->db->where('id_agregado', $id);
			$this->db->update('agregados', $data);
			$consulta = $this->db->query("SELECT ?::int AS id_agregado", array((int)$id));
		} else {
			// Verificar si ya existe un agregado con la misma llave única
			$this->db->where([
				'id_producto' => $data['id_producto'],
				'id_producto_maestro' => $data['id_producto_maestro'],
				'eliminado' => false
			]);
			$existe = $this->db->get('agregados')->row();

			if ($existe) {
				// Si existe, hacemos un update sobre el registro existente
				$this->db->where('id_agregado', $existe->id_agregado);
				$this->db->update('agregados', $data);
				$consulta = $this->db->query("SELECT ?::int AS id_agregado", array((int)$existe->id_agregado));
			} else {
				// Si no existe, insertamos
				$this->db->insert('agregados', $data);
				$consulta = $this->db->query("SELECT CURRVAL('agregados_id_agregado_seq'::regclass) as id_agregado");
			}
		}

		return $consulta->result();
	}
}
