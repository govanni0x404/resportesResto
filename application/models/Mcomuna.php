<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mcomuna extends CI_Model
{
  function __construct()
  {
    parent::__construct();
  }

  public function ListadoDeComunas()
  {
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    $q = "SELECT c.id_comuna, c.nombre_comuna, p.nombre_provincia from comuna c 
    join provincia p on c.id_provincia=p.id_provincia
    order by c.id_comuna asc";

    $consulta = $this->db->query($q);
    $sampleData = $consulta->result();
    $data = array();

    foreach ($sampleData as $key) {
      array_push($data, array(
        "id_comuna" => $key->id_comuna,
        "nombre_comuna" => $key->nombre_comuna,
        "nombre_provincia" => $key->nombre_provincia,
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

  public function grabarNuevaComuna($id, $data)
  {
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    if ($id) {
      $this->db->where('id_comuna', $id);
      $this->db->update('comuna', $data);
      $consulta = $this->db->query("select " . $id . " as id_comuna");
    } else {
      $this->db->insert('comuna', $data);
      $consulta = $this->db->query("SELECT CURRVAL('comuna_id_comuna_seq'::regclass) as id_comuna");
    }
    return $consulta->result();
  }

  public function ListarComunas()
  {
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    $q = " SELECT c.id_comuna, c.nombre_comuna from comuna c 
    order by c.nombre_comuna asc";
    $query = $this->db->query($q)->result();
    return $query;
  }
}
