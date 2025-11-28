<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mmesas extends CI_Model
{
  function __construct()
  {
    parent::__construct();
  }

  public function ListadoDeMesas()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url());
    }
    $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    
    $q = "SELECT m.id_mesa, m.disponible, m.bloqueo_caja, m.id_cotizacion, 
                 m.bloqueo_pedido, m.id_garzon, m.id_recinto, m.vigente, m.cod_dispositivo
          FROM mesa m
          ORDER BY m.id_mesa ASC";

    $consulta = $this->db->query($q);
    $sampleData = $consulta->result();
    $data = array();

    foreach ($sampleData as $key) {
      array_push($data, array(
        "id_mesa" => $key->id_mesa,
        "disponible" => $key->disponible,
        "bloqueo_caja" => $key->bloqueo_caja,
        "id_cotizacion" => $key->id_cotizacion,
        "bloqueo_pedido" => $key->bloqueo_pedido,
        "id_garzon" => $key->id_garzon,
        "id_recinto" => $key->id_recinto,
        "vigente" => $key->vigente,
        "cod_dispositivo" => $key->cod_dispositivo,
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

  public function crearMultiplesMesas($cantidad)
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url());
    }
    $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    
    $resultados = array();
    
    for ($i = 0; $i < $cantidad; $i++) {
      $data = array(
        'disponible' => true,
        'bloqueo_caja' => false,
        'id_cotizacion' => 0,
        'bloqueo_pedido' => false,
        'id_garzon' => 0,
        'id_recinto' => 0,
        'vigente' => true,
        'cod_dispositivo' => null
      );
      
      $this->db->insert('mesa', $data);
      $consulta = $this->db->query("SELECT CURRVAL('mesa_id_mesa_seq'::regclass) as id_mesa");
      $resultado = $consulta->result();
      array_push($resultados, $resultado[0]);
    }
    
    return array(
      'success' => true,
      'cantidad_creada' => $cantidad,
      'mesas' => $resultados
    );
  }

  public function editarMesa($id, $data)
  {
      if (strlen($this->session->userdata('nombre_usuario')) == 0) {
          redirect(base_url());
      }
      $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
      
      $this->db->where('id_mesa', $id);
      $exists = $this->db->get('mesa');
      
      if ($exists->num_rows() > 0) {
          $this->db->where('id_mesa', $id);
          $this->db->update('mesa', $data);
          
          // Verificar si se actualizó
          if ($this->db->affected_rows() > 0) {
              error_log('Mesa actualizada correctamente. ID: ' . $id);
          } else {
              error_log('No se actualizó ninguna fila. ID: ' . $id);
          }
      } else {
          error_log('Mesa no encontrada. ID: ' . $id);
      }
      
      $consulta = $this->db->query("SELECT " . $id . " as id_mesa");
      return $consulta->result();
  }
}