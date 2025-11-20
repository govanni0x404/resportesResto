<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MingresoEventos extends CI_Model
{
  function __construct()
  {
    parent::__construct();
  }

  public function ListadoDeIngresoEventos()
  {
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    $q = "SELECT i.id_ingreso_evento, i.nombre_ingreso_evento from ingreso_evento i
    order by i.id_ingreso_evento asc";

    $consulta = $this->db->query($q);
    $sampleData = $consulta->result();
    $data = array();

    foreach ($sampleData as $key) {
      array_push($data, array(
        "id_ingreso_evento" => $key->id_ingreso_evento,
        "nombre_ingreso_evento" => $key->nombre_ingreso_evento,
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

  public function grabarNuevoIngresoEvento($id, $data)
  {
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    if ($id) {
      $this->db->where('id_ingreso_evento', $id);
      $this->db->update('ingreso_evento', $data);
      $consulta = $this->db->query("select " . $id . " as id_ingreso_evento");
    } else {
      $this->db->insert('ingreso_evento', $data);
      $consulta = $this->db->query("SELECT CURRVAL('ingreso_evento_id_ingreso_evento_seq'::regclass) as id_ingreso_evento");
    }
    return $consulta->result();
  }
}