<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MsalidaBodega extends CI_Model
{
  function __construct()
  {
    parent::__construct();
  }
  public function obtenerStockActualPorBodega($idInsumo,$idBodega)
  {
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    $q = "SELECT s.saldo, u.nombre_umedida from stock_insumo s 
    join insumo i on s.id_insumo=i.id_insumo
    join umedida u on i.id_umedida_inventario=u.id_umedida
    where s.id_insumo=$idInsumo and s.id_bodega=$idBodega" ;
    $consulta = $this->db->query($q);
    return $consulta->result();
  }

  
  public function grabarSalidaBodega($data)
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    $this->db->insert('esbodega', $data);
    $consulta = $this->db->query("SELECT CURRVAL('esbodega_id_esbodega_seq'::regclass) as id_esbodega");
    return $consulta->result();
  }

  public function grabarDetalleSalidaBodega($data, $dataUpdateStock)
  {
      if (strlen($this->session->userdata('nombre_usuario')) == 0) {
          redirect(base_url());
      }
      $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
      
      $this->db->insert('detalle_esbodega', $data);
      $consulta = $this->db->query("SELECT CURRVAL('detalle_esbodega_id_detalle_esbodega_seq'::regclass) as id_detalle_esbodega");

      $datos_update = array(
          'saldo' => $dataUpdateStock['nuevo_stock'], 
      );

      $this->db->where('id_insumo', $dataUpdateStock['id_insumo']);
      $this->db->where('id_bodega', $dataUpdateStock['id_bodega_stock']);
      
      // Ejecutar el UPDATE
      $this->db->update('stock_insumo', $datos_update);
      
      return $consulta->result();
  }
      
}