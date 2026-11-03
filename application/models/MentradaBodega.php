<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MentradaBodega extends CI_Model
{
  function __construct()
  {
    parent::__construct();
  }
  public function obtenerStockActualPorBodega($idInsumo,$idBodega)
  {
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
    $q = "SELECT s.saldo, u.nombre_umedida from stock_insumo s 
    join insumo i on s.id_insumo=i.id_insumo
    join umedida u on i.id_umedida_inventario=u.id_umedida
    where s.id_insumo=? and s.id_bodega=?" ;
    $consulta = $this->db->query($q, array((int)$idInsumo, (int)$idBodega));
    return $consulta->result();
  }

  public function grabarEntradaBodega($data)
  {
    if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
    $this->db->insert('esbodega', $data);
    $consulta = $this->db->query("SELECT CURRVAL('esbodega_id_esbodega_seq'::regclass) as id_esbodega");
    return $consulta->result();
  }

  public function grabarDetalleEntradaBodega($data, $dataUpdateStock)
  {
      if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
          redirect(base_url());
      }
      $this->load->helper('tenant'); $this->db = get_tenant_db($this);
      
      $this->db->insert('detalle_esbodega', $data);
      $consulta = $this->db->query("SELECT CURRVAL('detalle_esbodega_id_detalle_esbodega_seq'::regclass) as id_detalle_esbodega");

      // Se actualiza igual que los triggers de la BD (compras, ventas, producción): cantidad_entrada y saldo = entrada - salida.
      // Antes solo se fijaba 'saldo' y el siguiente trigger lo recalculaba desde entrada/salida, perdiendo este movimiento.
      $cant = (float)$dataUpdateStock['cantidad'];
      $this->db->query("UPDATE stock_insumo SET cantidad_entrada = cantidad_entrada + ?, saldo = cantidad_entrada + ? - cantidad_salida WHERE id_insumo = ? AND id_bodega = ?",
        array($cant, $cant, (int)$dataUpdateStock['id_insumo'], (int)$dataUpdateStock['id_bodega_stock']));

      // Si el insumo aún no tiene fila de stock en esa bodega se crea (antes el movimiento no quedaba en el stock)
      if ($this->db->affected_rows() == 0) {
        $this->db->insert('stock_insumo', array(
          'id_insumo' => (int)$dataUpdateStock['id_insumo'],
          'id_bodega' => (int)$dataUpdateStock['id_bodega_stock'],
          'cantidad_entrada' => $cant, 'cantidad_salida' => 0, 'saldo' => $cant,
        ));
      }

      return $consulta->result();
  }
}