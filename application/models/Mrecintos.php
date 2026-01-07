<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mrecintos extends CI_Model
{
  function __construct()
  {
    parent::__construct();
  }

  public function ListadoDeRecintos()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    $q = "SELECT r.id_recinto, r.nombre_recinto, r.vigente, d.nombre_dimpresion from recinto r 
    left join dimpresion d on r.id_dimpresion=d.id_dimpresion
    order by r.id_recinto asc";

    $consulta = $this->db->query($q);
    $sampleData = $consulta->result();
    $data = array();

    foreach ($sampleData as $key) {
      array_push($data, array(
        "id_recinto" => $key->id_recinto,
        "nombre_recinto" => $key->nombre_recinto,
        "vigente" => ($key->vigente == 't' ? 'SI' : 'NO'),
        "nombre_dimpresion" => $key->nombre_dimpresion,
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

  public function grabarNuevoRecinto($id, $data)
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    if ($id) {
      $this->db->where('id_recinto', $id);
      $this->db->update('recinto', $data);
      $consulta = $this->db->query("select " . $id . " as id_recinto");
    } else {
      $this->db->insert('recinto', $data);
      $consulta = $this->db->query("SELECT CURRVAL('recinto_id_recinto_seq'::regclass) as id_recinto");
    }
    return $consulta->result();
  }

  public function obtenerUltimoDestinoImpresion()
  {
      $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);

      return $this->db
          ->select('id_dimpresion')
          ->from('dimpresion') // 👈 TABLA CORRECTA
          ->order_by('id_dimpresion', 'DESC')
          ->limit(1)
          ->get()
          ->row();
  }
}