<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mconfiguraciones extends CI_Model
{
  function __construct()
  {
    parent::__construct();
  }

  public function obtenerConfiguracionesRestoWeb()
  {
		if (strlen($this->session->userdata('rut_cliente')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    $q = " SELECT desglozar_monto_neto_iva_total_en_reportes, desglozar_solo_monto_total_en_reportes  from configuracion";
    $query = $this->db->query($q)->result();
    return $query;
  }

  public function grabarConfiguraciones($data)
  { //
		if (strlen($this->session->userdata('rut_cliente')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    $r = $this->db->update('configuracion', $data);
    return $r;
  }
}
