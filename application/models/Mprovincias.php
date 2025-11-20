<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mprovincias extends CI_Model
{
  function __construct()
  {
    parent::__construct();
  }

  public function ListadoProvincias()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    $q = "SELECT id_provincia, nombre_provincia from provincia order by nombre_provincia asc";
    $query = $this->db->query($q)->result();
    return $query;
  }

  public function ListadoDeProvinciasDatatable()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    $q = "SELECT p.id_provincia, p.nombre_provincia from provincia p 
    order by p.id_provincia asc";

    $consulta = $this->db->query($q);
    $sampleData = $consulta->result();
    $data = array();

    foreach ($sampleData as $key) {
      array_push($data, array(
        "id_provincia" => $key->id_provincia,
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

  public function grabarNuevaProvincia($id, $data)
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    if ($id) {
      $this->db->where('id_provincia', $id);
      $this->db->update('provincia', $data);
      $consulta = $this->db->query("select " . $id . " as id_provincia");
    } else {
      $this->db->insert('provincia', $data);
      $consulta = $this->db->query("SELECT CURRVAL('provincia_id_provincia_seq'::regclass) as id_provincia");
    }
    return $consulta->result();
  }
}