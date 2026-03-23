<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mclientes extends CI_Model
{
  function __construct()
  {
    parent::__construct();
  }

  public function ListadoDeClientes()
  {
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
    $q = "SELECT c.id_cliente, c.rut, c.dv, c.nombre_cliente, c.giro, c.direccion, co.nombre_comuna, c.telefono, lp.nombre_lprecio,
    c.mail, c.creditomaximo, c.referencia_direccion
    from cliente c 
    join comuna co on c.id_comuna=co.id_comuna
    join lprecio lp on c.id_lprecio=lp.id_lprecio
    order by c.id_cliente";

    $consulta = $this->db->query($q);
    $sampleData = $consulta->result();
    $data = array();

    foreach ($sampleData as $key) {
      array_push($data, array(
        "id_cliente" => $key->id_cliente,
        "rut" => $key->rut,
        "dv" => $key->dv,
        "rut_completo" => $key->rut . "-" . $key->dv,
        "nombre_cliente" => $key->nombre_cliente,
        "giro" => $key->giro,
        "direccion" => $key->direccion,
        "nombre_comuna" => $key->nombre_comuna,
        'telefono' => ($key->telefono == '' ? 'SIN TELEFONO' : $key->telefono),
        "nombre_lprecio" => $key->nombre_lprecio,
        "mail" => $key->mail,
        "creditomaximo" => $key->creditomaximo,
        "referencia_direccion" => $key->referencia_direccion,
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

  public function busquedaClientePorRut($rut)
  {
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
    $q = "SELECT c.id_cliente, c.rut, c.dv, c.nombre_cliente, c.giro, c.direccion, /*co.nombre_comuna, */c.telefono  from cliente c 
    /*join comuna co on c.id_comuna=co.id_comuna*/
    where c.rut='" . $rut . "' order by c.id_cliente";
    $consulta = $this->db->query($q);
    return $consulta->result();
  }

  public function grabarNuevoCliente($id, $data)
  {
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
    if ($id) {
      $this->db->where('id_cliente', $id);
      $this->db->update('cliente', $data);
      $consulta = $this->db->query("select " . $id . " as id_cliente");
    } else {
      $this->db->insert('cliente', $data);
      $consulta = $this->db->query("SELECT CURRVAL('cliente_id_cliente_seq'::regclass) as id_cliente");
    }
    return $consulta->result();
  }
}
