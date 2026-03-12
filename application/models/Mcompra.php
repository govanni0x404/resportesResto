<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mcompra extends CI_Model
{
  function __construct()
  {
    parent::__construct();
  }

  public function grabarCompra($data)
  {
      if (strlen($this->session->userdata('nombre_usuario')) > 0) {
          $this->load->helper('tenant'); $this->db = get_tenant_db($this);
          
          $insert_result = $this->db->insert('compra', $data);

          if ($insert_result) {
              
              $consulta = $this->db->query("SELECT CURRVAL('compra_id_compra_seq'::regclass) as id_compra");
              $r = $consulta->row();

              if ($r) {
                  return ['id' => $r->id_compra, 'message' => 'OK'];
              } else {
                  log_message('ERROR', 'grabarCompra: Falló al obtener el CURRVAL.');
                  return ['code' => '9999', 'message' => 'Error al obtener ID de compra.'];
              }
          } else {
              $error = $this->db->error();
              
              log_message('ERROR', 'SQL Fallido: ' . $this->db->last_query()); 
              
              log_message('ERROR', 'Error insert compra - Código DB: ' . $error['code'] . ', Mensaje: ' . $error['message']);
              return $error;
          }
      }
      // Si no hay sesión
      return ['code' => '403', 'message' => 'Sesión expirada.'];
  }

  public function grabarDetalleCompra($data)
  {
      if (strlen($this->session->userdata('nombre_usuario')) > 0) {
          $this->load->helper('tenant'); $this->db = get_tenant_db($this);
          
          $insert_ok = $this->db->insert('detalle_compra', $data); 
         
          if ($insert_ok === FALSE) {
            $error = $this->db->error();
             
            log_message('error', 'SQL FALLIDO: ' . $this->db->last_query());
            log_message('error', 'Error insert detalle_compra - Código DB: ' . $error['code'] . ', Mensaje: ' . $error['message']);
            
            return ['error' => $error];
          }

          $consulta = $this->db->query("SELECT CURRVAL('detalle_compra_id_detalle_compra_seq'::regclass) as id_detalle_compra");
          
          if ($consulta->num_rows() > 0) {
              return ['id' => $consulta->row()->id_detalle_compra, 'message' => 'OK'];
          } else {
              log_message('error', 'grabarDetalleCompra: Falló al obtener CURRVAL.');
              return ['error' => ['code' => '9999', 'message' => 'Error al obtener ID del detalle.']];
          }

      } else {
          // Si no hay sesión
          log_message('error', 'grabarDetalleCompra: Intento de grabar sin sesión.');
          return ['error' => ['code' => '403', 'message' => 'Sesión expirada.']];
      }
  }

  public function ListadoTipoDeDocumento()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url());
    }

    $this->load->helper('tenant'); $this->db = get_tenant_db($this);
    $q = " SELECT t.id_tdocumento, t.nombre_tdocumento from tdocumento t where t.para_compra=true
    order by t.nombre_tdocumento asc";
    $query = $this->db->query($q)->result();
    return $query;
  }

  public function verificarSiExisteLaCompra($idProveedor, $idTdocumento, $numero_documento)
  {
    if (strlen($this->session->userdata('nombre_usuario')) > 0) {
      $this->load->helper('tenant'); $this->db = get_tenant_db($this);
      
      $q = "SELECT id_compra, to_char(fecha, 'DD/MM/YYYY') AS fecha 
            FROM compra 
            WHERE id_proveedor=? AND id_tdocumento=? AND numero_documento=?";
      
      $query = $this->db->query($q, array($idProveedor, $idTdocumento, $numero_documento));
      
      $arr = array();
      foreach ($query->result() as $row) {
        $arr[] = array(
          'id_compra' => $row->id_compra,
          'fecha' => $row->fecha
        );
      }
      
      if (empty($arr)) {
        $arr[] = array(
          'id_compra' => 0,
          'fecha' => ""
        );
      }
      
      return $arr;
    }
  }
}