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
    if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url());
    }
    $this->load->helper('tenant'); $this->db = get_tenant_db($this);
    
    // Verificar si existe la tabla rol, si no, usar solo usuario
    // 14-11-2026: la clave NO se lee ni se manda al navegador (antes el listado traía la clave de todos)
    $q = "SELECT u.id_usuario, u.nombre_usuario, u.esadmin, 
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
      if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
          redirect(base_url());
      }

      $this->load->helper('tenant'); $this->db = get_tenant_db($this);

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
              $this->db->db_debug = FALSE;
              // Sin el aviso de PHP en pantalla: el error de clave duplicada trae la clave en su detalle
              $nivel = error_reporting(0);
              $ok = $this->db->insert('usuario', $data);
              error_reporting($nivel);
              if (!$ok) {
                  throw new Exception($this->db->error()['message']);
              }

              // Obtener ID correctamente en PostgreSQL
              $nuevo_id = $this->db->insert_id('usuario_id_usuario_seq');

              return [['id_usuario' => $nuevo_id]];
          }

      } catch (Exception $e) {

          error_log('Error en grabarNuevoUsuario: ' . $e->getMessage());
          return ['error' => $this->mensajeError($e->getMessage())];
      }
  }


  public function editarUsuario($id, $data)
  {
      if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
          redirect(base_url());
      }
      
      $this->load->helper('tenant'); $this->db = get_tenant_db($this);

      try {
          // No se tocan esadmin/esgarzon: el formulario no los edita y antes se dejaban en false
          // (le quitaba el perfil de administrador/garzón del POS a quien se editaba)
          $this->db->db_debug = FALSE;
          $this->db->where('id_usuario', $id);
          $nivel = error_reporting(0);   // ver grabarNuevoUsuario: el detalle del error trae la clave
          $this->db->update('usuario', $data);
          error_reporting($nivel);

          if (!empty($this->db->error()['code'])) {
              throw new Exception($this->db->error()['message']);
          }

          return [['id_usuario' => $id, 'success' => true]];
          
      } catch (Exception $e) {
          error_log('Error en editarUsuario: ' . $e->getMessage());
          return ['error' => $this->mensajeError($e->getMessage())];
      }
  }

  /**
   * 14-11-2026: usuario.clave y usuario.nombre_usuario son únicos en la base (la app de garzones identifica al usuario
   * solo por su clave). El error de PostgreSQL no se muestra tal cual; para la clave no se dice de quién es.
   */
  private function mensajeError($msg)
  {
      if (stripos($msg, 'usuario_clave_key') !== false) return 'Esa clave no se puede usar: elige otra';
      if (stripos($msg, 'usuario_nombre_usuario_key') !== false) return 'Ya existe un usuario con ese nombre';
      return 'No se pudo grabar el usuario';
  }
}
