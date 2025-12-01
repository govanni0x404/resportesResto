<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MprivilegiosUsuario extends CI_Model
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
    
    $q = "SELECT u.id_usuario, u.nombre_usuario, u.id_rol, r.nombre_rol, u.vigente
          FROM usuario u
          LEFT JOIN rol r ON u.id_rol = r.id_rol
          WHERE u.vigente = true
          ORDER BY u.nombre_usuario ASC";

    try {
      $consulta = $this->db->query($q);
      $sampleData = $consulta->result();
      $data = array();

      foreach ($sampleData as $key) {
        array_push($data, array(
          "id_usuario" => $key->id_usuario,
          "nombre_usuario" => $key->nombre_usuario,
          "id_rol" => $key->id_rol,
          "nombre_rol" => $key->nombre_rol ? $key->nombre_rol : 'Sin Rol',
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

  public function ListarPrivilegiosDeUsuario($id_usuario)
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url());
    }
    $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    
    // Obtener el rol del usuario
    $q_usuario = "SELECT id_rol FROM usuario WHERE id_usuario = " . $id_usuario;
    $consulta_usuario = $this->db->query($q_usuario);
    $usuario = $consulta_usuario->row();
    
    if (!$usuario) {
      return array();
    }
    
    $id_rol = $usuario->id_rol;
    
    // Obtener privilegios del usuario
    $q = "SELECT pu.id_pusuario, pu.id_rol, pu.id_psistema, ps.nombre_psistema, 
                 ps.nombre_vb, pu.autoriza
          FROM pusuario pu
          JOIN psistema ps ON pu.id_psistema = ps.id_psistema
          WHERE pu.id_rol = " . $id_rol . " AND ps.vigente = true
          ORDER BY ps.nombre_vb ASC";
    
    try {
      $consulta = $this->db->query($q);
      return $consulta->result();
    } catch (Exception $e) {
      error_log('Error en ListarPrivilegiosDeUsuario: ' . $e->getMessage());
      return array();
    }
  }

  public function actualizarPrivilegio($id_pusuario, $autoriza)
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url());
    }
    $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    
    try {
      $data = array('autoriza' => $autoriza);
      $this->db->where('id_pusuario', $id_pusuario);
      $this->db->update('pusuario', $data);
      
      error_log('Privilegio actualizado. ID: ' . $id_pusuario . ', Autoriza: ' . ($autoriza ? 'true' : 'false'));
      return array('success' => true, 'message' => 'Privilegio actualizado correctamente');
    } catch (Exception $e) {
      error_log('Error en actualizarPrivilegio: ' . $e->getMessage());
      return array('success' => false, 'message' => $e->getMessage());
    }
  }

  public function HabilitarTodosLosPrivilegios($id_rol)
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url());
    }
    $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    
    try {
      $q = "UPDATE pusuario SET autoriza = true WHERE id_rol = " . $id_rol;
      $this->db->query($q);
      
      error_log('Todos los privilegios habilitados para rol: ' . $id_rol);
      return array('success' => true, 'message' => 'Todos los privilegios habilitados');
    } catch (Exception $e) {
      error_log('Error en HabilitarTodosLosPrivilegios: ' . $e->getMessage());
      return array('success' => false, 'message' => $e->getMessage());
    }
  }

  public function BloquearTodosLosPrivilegios($id_rol)
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url());
    }
    $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    
    try {
      $q = "UPDATE pusuario SET autoriza = false WHERE id_rol = " . $id_rol;
      $this->db->query($q);
      
      error_log('Todos los privilegios bloqueados para rol: ' . $id_rol);
      return array('success' => true, 'message' => 'Todos los privilegios bloqueados');
    } catch (Exception $e) {
      error_log('Error en BloquearTodosLosPrivilegios: ' . $e->getMessage());
      return array('success' => false, 'message' => $e->getMessage());
    }
  }
}