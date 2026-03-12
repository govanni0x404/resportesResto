<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MdestinoDeImpresion extends CI_Model
{
  function __construct()
  {
    parent::__construct();
  }

  public function ListadoDestinoDeImpresionDatatable()
  {
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
    $q = "SELECT d.id_dimpresion, d.nombre_dimpresion, d.ip, d.puerto, d.ruta_windows, d.impresion_grafica_windows, d.permite_precuenta, d.impresion_local,
    d.usar_logo_precuenta_pos, d.size_font, d.sin_impresion, d.usar_dimpresion_recinto, d.usar_dimpresion_precuenta_android
    from dimpresion d 
    order by d.id_dimpresion asc";

    $consulta = $this->db->query($q);
    $sampleData = $consulta->result();
    $data = array();

    foreach ($sampleData as $key) {
      array_push($data, array(
        "id_dimpresion" => $key->id_dimpresion,
        "nombre_dimpresion" => $key->nombre_dimpresion,
        "ip" => $key->ip,
        "puerto" => $key->puerto,
        "ruta_windows" => $key->ruta_windows,
        "impresion_grafica_windows" => ($key->impresion_grafica_windows == 't' ? 'SI' : 'NO'),
        "permite_precuenta" => ($key->permite_precuenta == 't' ? 'SI' : 'NO'),
        "impresion_local" => ($key->impresion_local == 't' ? 'SI' : 'NO'),
        "usar_logo_precuenta_pos" => ($key->usar_logo_precuenta_pos == 't' ? 'SI' : 'NO'),
        "size_font" => $key->size_font,
        "sin_impresion" => ($key->sin_impresion == 't' ? 'SI' : 'NO'),
        "usar_dimpresion_recinto" => ($key->usar_dimpresion_recinto == 't' ? 'SI' : 'NO'),
        "usar_dimpresion_precuenta_android" => ($key->usar_dimpresion_precuenta_android == 't' ? 'SI' : 'NO'),
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

  public function grabarNuevoDestinoDeImpresion($id, $data)
  {
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
    if ($id) {
      $this->db->where('id_dimpresion', $id);
      $this->db->update('dimpresion', $data);
      $consulta = $this->db->query("select " . $id . " as id_dimpresion");
    } else {
      $this->db->insert('dimpresion', $data);
      $consulta = $this->db->query("SELECT CURRVAL('dimpresion_id_dimpresion_seq'::regclass) as id_dimpresion");
    }
    return $consulta->result();
  }
}