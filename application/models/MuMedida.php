<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MuMedida extends CI_Model
{
  function __construct()
  {
    parent::__construct();
  }

  public function ListadoDeUmedida()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $this->load->helper('tenant'); $this->db = get_tenant_db($this);
    $q = "SELECT u.id_umedida, u.nombre_umedida from umedida u
    order by u.id_umedida asc";

    $consulta = $this->db->query($q);
    $sampleData = $consulta->result();
    $data = array();

    foreach ($sampleData as $key) {
      array_push($data, array(
        "id_umedida" => $key->id_umedida,
        "nombre_umedida" => $key->nombre_umedida,
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

  public function grabarNuevaUmedida($id, $data)
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $this->load->helper('tenant'); $this->db = get_tenant_db($this);
    if ($id) {
      $this->db->where('id_umedida', $id);
      $this->db->update('umedida', $data);
      $consulta = $this->db->query("select " . $id . " as id_umedida");
    } else {
      $this->db->insert('umedida', $data);
      $consulta = $this->db->query("SELECT CURRVAL('umedida_id_umedida_seq'::regclass) as id_umedida");
    }
    return $consulta->result();
  }
}