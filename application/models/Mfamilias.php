<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mfamilias extends CI_Model
{
  function __construct()
  {
    parent::__construct();
  }

  public function ListadoDeFamilias()
  {
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
    $q = "SELECT id_familia, nombre_familia, venta_directa, orden_familia, imprimir_en_detalle_pedido, alias
		from familia 
    order by id_familia asc";

    $consulta = $this->db->query($q);
    $sampleData = $consulta->result();
    $data = array();

    foreach ($sampleData as $key) {
      array_push($data, array(
        "id_familia" => $key->id_familia,
        "nombre_familia" => $key->nombre_familia,
        "venta_directa" => $key->venta_directa == 't' ? 'SI': 'NO',
        "orden_familia" => $key->orden_familia,
        "imprimir_en_detalle_pedido" => $key->imprimir_en_detalle_pedido,
        "alias" => $key->alias == '' ? '-' : $key->alias
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

  public function grabarNuevaFamilia($id, $data)
  {
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
    if ($id) {
      $this->db->where('id_familia', $id);
      $this->db->update('familia', $data);
      $consulta = $this->db->query("select " . $id . " as id_familia");
    } else {
      $this->db->insert('familia', $data);
      $consulta = $this->db->query("SELECT CURRVAL('familia_id_familia_seq'::regclass) as id_familia");
    }
    return $consulta->result();
  }
}