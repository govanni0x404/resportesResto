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
    if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $this->load->helper('tenant'); $this->db = get_tenant_db($this);
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
    if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $this->load->helper('tenant'); $this->db = get_tenant_db($this);
    if ($id) {
      $this->db->where('id_tipo_evento', $id);
      $this->db->update('tipo_evento', $data);
      // 16-11-2026 (QA): si no se actualizó ninguna fila el registro no existe: error (404) y no "éxito"
      if ($this->db->affected_rows() < 1) {
      	set_status_header(404);
      	return array('error' => 'El registro ya no existe (pudo haberlo borrado otra persona). Recarga la página.');
      }
      $consulta = $this->db->query("select " . (int)$id . " as id_tipo_evento");
    } else {
      $this->db->insert('tipo_evento', $data);
      $consulta = $this->db->query("SELECT CURRVAL('tipo_evento_id_tipo_evento_seq'::regclass) as id_tipo_evento");
    }
    return $consulta->result();
  }
}