<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MitemGastosMensuales extends CI_Model
{
  function __construct()
  {
    parent::__construct();
  }

  public function ListadoDeItemGastosMensuales()
  {
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
    $q = "SELECT i.id_item_gastos, i.nombre_item_gastos, i.vigente from item_gastos i
    order by i.id_item_gastos asc";

    $consulta = $this->db->query($q);
    $sampleData = $consulta->result();
    $data = array();

    foreach ($sampleData as $key) {
      array_push($data, array(
        "id_item_gastos" => $key->id_item_gastos,
        "nombre_item_gastos" => $key->nombre_item_gastos,
        'vigente' => ($key->vigente == 't' ? 'SI' : 'NO'),
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

  public function grabarNuevoItemGastoMensual($id, $data)
  {
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
    if ($id) {
      $this->db->where('id_item_gastos', $id);
      $this->db->update('item_gastos', $data);
      // 16-11-2026 (QA): si no se actualizó ninguna fila el registro no existe: error (404) y no "éxito"
      if ($this->db->affected_rows() < 1) {
      	set_status_header(404);
      	return array('error' => 'El registro ya no existe (pudo haberlo borrado otra persona). Recarga la página.');
      }
      $consulta = $this->db->query("select " . (int)$id . " as id_item_gastos");
    } else {
      $this->db->insert('item_gastos', $data);
      $consulta = $this->db->query("SELECT CURRVAL('item_gastos_id_item_gastos_seq'::regclass) as id_item_gastos");
    }
    return $consulta->result();
  }
}