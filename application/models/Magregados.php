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
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$q = "SELECT a.id_agregado, a.id_producto_maestro, a.id_producto, round(a.cantidad) as cantidad, a.valor, a.vigente, p.nombre_producto, pm.nombre_producto as producto_maestro, a.usar_destino_impresion
        from agregados a
        join producto p on p.id_producto=a.id_producto
        join producto pm on pm.id_producto=a.id_producto_maestro
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
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}

		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);

		if ($id) {
			// Actualizar existente por id_agregado
			$this->db->where('id_agregado', $id);
			$this->db->update('agregados', $data);
			$consulta = $this->db->query("SELECT " . $id . " as id_agregado");
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
				$consulta = $this->db->query("SELECT " . $existe->id_agregado . " as id_agregado");
			} else {
				// Si no existe, insertamos
				$this->db->insert('agregados', $data);
				$consulta = $this->db->query("SELECT CURRVAL('agregados_id_agregado_seq'::regclass) as id_agregado");
			}
		}

		return $consulta->result();
	}
}
