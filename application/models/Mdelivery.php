<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mdelivery extends CI_Model
{
  function __construct()
  {
    parent::__construct();
  }

  public function ListadoDeSectores()
  {
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
    $q = "SELECT s.id_sector, s.nombre_sector from sector s order by s.id_sector asc";

    $consulta = $this->db->query($q);
    $sampleData = $consulta->result();
    $data = array();

    foreach ($sampleData as $key) {
      array_push($data, array(
        "id_sector" => $key->id_sector,
        "nombre_sector" => $key->nombre_sector,
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

  public function grabarNuevoSector($id, $data)
  {
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
    if ($id) {
      $this->db->where('id_sector', $id);
      $this->db->update('sector', $data);
      $consulta = $this->db->query("select " . $id . " as id_sector");
    } else {
      $this->db->insert('sector', $data);
      $consulta = $this->db->query("SELECT CURRVAL('sector_id_sector_seq'::regclass) as id_sector");
    }
    return $consulta->result();
  }
}