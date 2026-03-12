<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mmoneda extends CI_Model
{
  function __construct()
  {
    parent::__construct();
  }

  public function ListadoDeMonedas()
  {
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
    $q = "SELECT m.id_moneda, m.nombre_moneda from moneda m
    order by m.id_moneda asc";

    $consulta = $this->db->query($q);
    $sampleData = $consulta->result();
    $data = array();

    foreach ($sampleData as $key) {
      array_push($data, array(
        "id_moneda" => $key->id_moneda,
        "nombre_moneda" => $key->nombre_moneda,
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

  public function grabarNuevaMoneda($id, $data)
  {
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
    if ($id) {
      $this->db->where('id_moneda', $id);
      $this->db->update('moneda', $data);
      $consulta = $this->db->query("select " . $id . " as id_moneda");
    } else {
      $this->db->insert('moneda', $data);
      $consulta = $this->db->query("SELECT CURRVAL('moneda_id_moneda_seq'::regclass) as id_moneda");
    }
    return $consulta->result();
  }
}