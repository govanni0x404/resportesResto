<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MfamiliasReceta extends CI_Model
{
  function __construct()
  {
    parent::__construct();
  }

  public function ListadoFamiliasReceta()
  {
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    $q = "SELECT f.id_familia_receta, f.nombre_familia_receta from familia_receta f order by f.id_familia_receta asc";

    $consulta = $this->db->query($q);
    $sampleData = $consulta->result();
    $data = array();

    foreach ($sampleData as $key) {
      array_push($data, array(
        "id_familia_receta" => $key->id_familia_receta,
        "nombre_familia_receta" => $key->nombre_familia_receta,
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

  public function grabarNuevaFamiliaReceta($id, $data)
  {
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    if ($id) {
      $this->db->where('id_familia_receta', $id);
      $this->db->update('familia_receta', $data);
      $consulta = $this->db->query("select " . $id . " as id_familia_receta");
    } else {
      $this->db->insert('familia_receta', $data);
      $consulta = $this->db->query("SELECT CURRVAL('familia_receta_id_familia_receta_seq'::regclass) as id_familia_receta");
    }
    return $consulta->result();
  }
}