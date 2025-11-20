<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MtipoEvento extends CI_Model
{
  function __construct()
  {
    parent::__construct();
  }

  public function ListadoDeTipoEventos()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    $q = "SELECT t.id_tipo_evento, t.nombre_tipo_evento from tipo_evento t
    order by t.id_tipo_evento asc";

    $consulta = $this->db->query($q);
    $sampleData = $consulta->result();
    $data = array();

    foreach ($sampleData as $key) {
      array_push($data, array(
        "id_tipo_evento" => $key->id_tipo_evento,
        "nombre_tipo_evento" => $key->nombre_tipo_evento,
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

  public function grabarNuevoTipoEvento($id, $data)
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    if ($id) {
      $this->db->where('id_tipo_evento', $id);
      $this->db->update('tipo_evento', $data);
      $consulta = $this->db->query("select " . $id . " as id_tipo_evento");
    } else {
      $this->db->insert('tipo_evento', $data);
      $consulta = $this->db->query("SELECT CURRVAL('tipo_evento_id_tipo_evento_seq'::regclass) as id_tipo_evento");
    }
    return $consulta->result();
  }
}