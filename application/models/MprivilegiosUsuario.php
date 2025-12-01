<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MprivilegiosUsuario extends CI_Model
{
  function __construct()
  {
    parent::__construct();
  }

  public function ListadoDeRoles()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url());
    }
    $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    
    $q = "SELECT r.id_rol, r.nombre_rol 
          FROM rol r 
          ORDER BY r.nombre_rol ASC";

    try {
      $consulta = $this->db->query($q);
      return $consulta->result();
    } catch (Exception $e) {
      error_log('Error en ListadoDeRoles: ' . $e->getMessage());
      return array();
    }
  }

  public function ListarPrivilegiosPorRol($id_rol)
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url());
    }
    $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    
    // Obtener todos los módulos del sistema y verificar si el rol tiene acceso
    $q = "SELECT ps.id_psistema, ps.nombre_psistema, ps.nombre_vb,
                 pu.id_pusuario, pu.autoriza, pu.id_rol
          FROM psistema ps
          LEFT JOIN pusuario pu ON ps.id_psistema = pu.id_psistema AND pu.id_rol = " . $id_rol . "
          WHERE ps.vigente = true
          ORDER BY ps.nombre_vb ASC";
    
    try {
      $consulta = $this->db->query($q);
      return $consulta->result();
    } catch (Exception $e) {
      error_log('Error en ListarPrivilegiosPorRol: ' . $e->getMessage());
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

  public function crearPrivilegio($id_rol, $id_psistema)
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url());
    }
    $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    
    try {
      $data = array(
        'id_rol' => $id_rol,
        'id_psistema' => $id_psistema,
        'autoriza' => true
      );
      $this->db->insert('pusuario', $data);
      
      $consulta = $this->db->query("SELECT CURRVAL('pusuario_id_pusuario_seq'::regclass) as id_pusuario");
      $resultado = $consulta->result();
      
      error_log('Privilegio creado. Rol: ' . $id_rol . ', PSistema: ' . $id_psistema);
      return array('success' => true, 'id_pusuario' => $resultado[0]->id_pusuario);
    } catch (Exception $e) {
      error_log('Error en crearPrivilegio: ' . $e->getMessage());
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