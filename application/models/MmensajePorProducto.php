<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MmensajePorProducto extends CI_Model
{
  function __construct()
  {
    parent::__construct();
  }

  public function ListadoDeMensajesPorProductos()
  {
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
    $q = "SELECT mp.id_mensaje_producto, p.nombre_producto, mp.mensaje, mp.vigente
        from mensaje_producto mp
        join producto p on p.id_producto=mp.id_producto
        order by mp.id_mensaje_producto";

    $consulta = $this->db->query($q);
    $sampleData = $consulta->result();
    $data = array();

    foreach ($sampleData as $key) {
      array_push($data, array(
        "id_mensaje_producto" => $key->id_mensaje_producto,
        "nombre_producto" => $key->nombre_producto,
        "mensaje" => $key->mensaje,
        "vigente" => ($key->vigente == 't' ? 'SI' : 'NO'),
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

  public function grabarMsgProductos($id, $data)
    {
			if (strlen($this->session->userdata('nombre_usuario')) == 0) {
				redirect(base_url()); //redireccionar al login
			}
			$this->load->helper('tenant'); $this->db = get_tenant_db($this);
        if ($id) {
            $this->db->where('id_mensaje_producto', $id);
            $this->db->update('mensaje_producto', $data);
            $consulta = $this->db->query("select " . $id . " as id_mensaje_producto");
        } else {
            $this->db->insert('mensaje_producto', $data);
            $consulta = $this->db->query("SELECT CURRVAL('mensaje_producto_id_mensaje_producto_seq'::regclass) as id_mensaje_producto");
        }
        return $consulta->result();
    }
}