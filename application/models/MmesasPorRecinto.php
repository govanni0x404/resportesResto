<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MmesasPorRecinto extends CI_Model
{
  function __construct()
  {
    parent::__construct();
  }

  // public function ListadoDeMesasSinAsignar()
  // {
  //   if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
  //     redirect(base_url());
  //   }
  //   $this->load->helper('tenant'); $this->db = get_tenant_db($this);
    
  //   // Obtener mesas sin asignar (id_recinto = 0) y vigentes
  //   $q = "SELECT m.id_mesa, m.disponible, m.id_recinto, m.vigente, m.pos, 
  //         FROM mesa m
  //         WHERE m.id_recinto = 0 AND m.vigente = true
  //         ORDER BY m.id_mesa ASC";

  //   $consulta = $this->db->query($q);
  //   $sampleData = $consulta->result();
  //   $data = array();

  //   foreach ($sampleData as $key) {
  //     array_push($data, array(
  //       "id_mesa" => $key->id_mesa,
  //       "disponible" => $key->disponible,
  //       "id_recinto" => $key->id_recinto,
  //       "vigente" => $key->vigente,
  //       "pos" => $key->pos,
  //     ));
  //   }
    
  //   $results = array(
  //     "sEcho" => 1,
  //     "iTotalRecords" => count($data),
  //     "iTotalDisplayRecords" => count($data),
  //     "aaData" => $data
  //   );

  //   return $results;
  // }

  public function ListadoDeMesas()
  {
      $this->load->helper('tenant'); $this->db = get_tenant_db($this);

      $q = " SELECT 
              m.id_mesa,
              m.disponible,
              m.id_recinto,
              m.vigente,
              m.pos,
              r.nombre_recinto
          FROM mesa m
          LEFT JOIN recinto r ON r.id_recinto = m.id_recinto
          WHERE m.vigente = true
          ORDER BY m.id_mesa ASC
      ";

      $consulta = $this->db->query($q);
      $data = [];

      foreach ($consulta->result() as $key) {
          $data[] = [
              "id_mesa" => $key->id_mesa,
              "disponible" => $key->disponible,
              "id_recinto" => $key->id_recinto,
              "nombre_recinto" => $key->nombre_recinto,
              "vigente" => $key->vigente,
              "pos" => $key->pos,
          ];
      }

      return [
          "sEcho" => 1,
          "iTotalRecords" => count($data),
          "iTotalDisplayRecords" => count($data),
          "aaData" => $data
      ];
  }

  public function ListadoDeRecintos()
  {
    if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url());
    }
    $this->load->helper('tenant'); $this->db = get_tenant_db($this);
    
    // Obtener todos los recintos activos
    $q = "SELECT r.id_recinto, r.nombre_recinto
          FROM recinto r
          WHERE r.vigente = true
          ORDER BY r.nombre_recinto ASC";

    $consulta = $this->db->query($q);
    $data = $consulta->result();
    
    return $data;
  }

  public function asignarMesaARecinto($id_mesa, $data)
  {
    if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url());
    }
    $this->load->helper('tenant'); $this->db = get_tenant_db($this);
    
    $this->db->where('id_mesa', $id_mesa);
    $exists = $this->db->get('mesa');
    
    if ($exists->num_rows() > 0) {
      $this->db->where('id_mesa', $id_mesa);
      $this->db->update('mesa', $data);
      
      if ($this->db->affected_rows() > 0) {
        error_log('Mesa asignada correctamente. ID Mesa: ' . $id_mesa . ', ID Recinto: ' . $data['id_recinto'] . ', Pos: ' . $data['pos']);
        return array('success' => true, 'message' => 'Mesa asignada correctamente');
      } else {
        error_log('No se pudo asignar la mesa. ID: ' . $id_mesa);
        return array('success' => false, 'message' => 'No se pudo actualizar la mesa');
      }
    } else {
      error_log('Mesa no encontrada. ID: ' . $id_mesa);
      return array('success' => false, 'message' => 'Mesa no encontrada');
    }
  }

  /**
   * v1.38.3 (pedido del usuario): asigna varias mesas a un recinto de una vez (antes de a una). Igual que de a una:
   * id_recinto y pos = id_mesa - 1. Devuelve cuántas quedaron asignadas.
   */
  public function asignarVarias($ids, $idRecinto)
  {
    $this->load->helper('tenant'); $db = get_tenant_db($this);
    $idRecinto = (int)$idRecinto;
    $ids = array_values(array_unique(array_filter(array_map('intval', is_array($ids) ? $ids : array()), function ($x) { return $x > 0; })));
    if ($idRecinto <= 0) return array('error' => 'Elige el recinto');
    if (!$ids) return array('error' => 'Marca al menos una mesa');
    if (count($ids) > 1000) return array('error' => 'Demasiadas mesas de una vez');
    if (!$db->query('SELECT 1 FROM recinto WHERE id_recinto = ?', array($idRecinto))->row()) return array('error' => 'El recinto ya no existe. Recarga la página.');
    $db->query('UPDATE mesa SET id_recinto = ?, pos = id_mesa - 1 WHERE id_mesa IN (' . implode(',', $ids) . ')', array($idRecinto));
    return array('ok' => true, 'asignadas' => (int)$db->affected_rows());
  }
}