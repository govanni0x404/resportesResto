<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MgastoEvento extends CI_Model
{
  function __construct()
  {
    parent::__construct();
  }

  public function ListadoDeGastosEventos()
  {
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    $q = "SELECT g.id_gasto_evento, g.nombre_gasto_evento from gasto_evento g
    order by g.id_gasto_evento asc";

    $consulta = $this->db->query($q);
    $sampleData = $consulta->result();
    $data = array();

    foreach ($sampleData as $key) {
      array_push($data, array(
        "id_gasto_evento" => $key->id_gasto_evento,
        "nombre_gasto_evento" => $key->nombre_gasto_evento,
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

  public function grabarNuevoGastoEvento($id, $data)
  {
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    if ($id) {
      $this->db->where('id_gasto_evento', $id);
      $this->db->update('gasto_evento', $data);
      $consulta = $this->db->query("select " . $id . " as id_gasto_evento");
    } else {
      $this->db->insert('gasto_evento', $data);
      $consulta = $this->db->query("SELECT CURRVAL('gasto_evento_id_gasto_evento_seq'::regclass) as id_gasto_evento");
    }
    return $consulta->result();
  }
}