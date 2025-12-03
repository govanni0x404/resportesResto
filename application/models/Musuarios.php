<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Musuarios extends CI_Model
{
  function __construct()
  {
    parent::__construct();
  }

  public function ListadoDeUsuarios()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url());
    }
    $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    
    // Verificar si existe la tabla rol, si no, usar solo usuario
    $q = "SELECT u.id_usuario, u.nombre_usuario, u.clave, u.esadmin, 
                 u.esgarzon, u.id_rol, u.vigente
          FROM usuario u
          ORDER BY u.id_usuario ASC";

    try {
      $consulta = $this->db->query($q);
      $sampleData = $consulta->result();
      $data = array();

      foreach ($sampleData as $key) {
        array_push($data, array(
          "id_usuario" => $key->id_usuario,
          "nombre_usuario" => $key->nombre_usuario,
          "clave_usuario" => $key->clave,
          "es_admin" => $key->esadmin,
          "es_garzon" => $key->esgarzon,
          "id_rol" => $key->id_rol,
          "vigente" => $key->vigente,
        ));
      }
      
      $results = array(
        "sEcho" => 1,
        "iTotalRecords" => count($data),
        "iTotalDisplayRecords" => count($data),
        "aaData" => $data
      );

      return $results;
    } catch (Exception $e) {
      error_log('Error en ListadoDeUsuarios: ' . $e->getMessage());
      return array(
        "sEcho" => 1,
        "iTotalRecords" => 0,
        "iTotalDisplayRecords" => 0,
        "aaData" => array()
      );
    }
  }

  public function grabarNuevoUsuario($id, $data)
  {
      if (strlen($this->session->userdata('nombre_usuario')) == 0) {
          redirect(base_url());
      }

      $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);

      try {

          // Valores por defecto obligatorios
          $data['esadmin'] = false;
          $data['esgarzon'] = false;

          if ($id && $id > 0) {

              // --- ACTUALIZAR USUARIO ---
              $this->db->where('id_usuario', $id);
              $this->db->update('usuario', $data);

              return [['id_usuario' => $id]];

          } else {

              // --- INSERTAR USUARIO ---
              $this->db->insert('usuario', $data);

              // Obtener ID correctamente en PostgreSQL
              $nuevo_id = $this->db->insert_id('usuario_id_usuario_seq');

              return [['id_usuario' => $nuevo_id]];
          }

      } catch (Exception $e) {

          error_log('Error en grabarNuevoUsuario: ' . $e->getMessage());
          return ['error' => $e->getMessage()];
      }
  }


  public function editarUsuario($id, $data)
  {
      if (strlen($this->session->userdata('nombre_usuario')) == 0) {
          redirect(base_url());
      }
      
      $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);

      try {
          $data['esadmin'] = false;
          $data['esgarzon'] = false;

          $this->db->where('id_usuario', $id);
          $this->db->update('usuario', $data);

          if ($this->db->error()['code'] != 0) {
              throw new Exception($this->db->error()['message']);
          }

          return [['id_usuario' => $id, 'success' => true]];
          
      } catch (Exception $e) {
          error_log('Error en editarUsuario: ' . $e->getMessage());
          return ['error' => $e->getMessage()];
      }
  }
}