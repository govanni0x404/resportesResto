<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mturnos extends CI_Model
{
  function __construct()
  {
    parent::__construct();
  }

  public function ListadoDeTurnos()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    $q = "SELECT t.id_turno, t.nombre_turno, t.cierre_turno, t.informe_z, t.impedir_cierre_con_mesas_abiertas from turno t 
    order by t.id_turno asc";

    $consulta = $this->db->query($q);
    $sampleData = $consulta->result();
    $data = array();

    foreach ($sampleData as $key) {
      array_push($data, array(
        "id_turno" => $key->id_turno,
        "nombre_turno" => $key->nombre_turno,
        "cierre_turno" => ($key->cierre_turno == 't' ? 'SI' : 'NO'),
        "informe_z" => ($key->informe_z == 't' ? 'SI' : 'NO'),
        "impedir_cierre_con_mesas_abiertas" => ($key->impedir_cierre_con_mesas_abiertas == 't' ? 'SI' : 'NO'),
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

  public function grabarNuevoTurno($id, $data)
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    if ($id) {
      $this->db->where('id_turno', $id);
      $this->db->update('turno', $data);
      $consulta = $this->db->query("select " . $id . " as id_turno");
    } else {
      $this->db->insert('turno', $data);
      $consulta = $this->db->query("SELECT CURRVAL('turno_id_turno_seq'::regclass) as id_turno");
    }
    return $consulta->result();
  }
}