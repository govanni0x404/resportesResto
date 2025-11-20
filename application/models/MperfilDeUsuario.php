<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MperfilDeUsuario extends CI_Model
{
  function __construct()
  {
    parent::__construct();
  }

  public function obtenerDatosDelUsuario($id_usuario)
  {
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    $q = " SELECT u.nombre_usuario, u.clave, r.nombre_rol, u.esadmin, u.id_usuario
        from usuario u
        join rol r on u.id_rol=r.id_rol
        where id_usuario=$id_usuario";
    $query = $this->db->query($q)->result();
    return $query;
  }

  public function ActualizarNombreYPasswordDeUsuarioLocal($id_usuario, $data)
  {
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    $this->db->where('id_usuario', $id_usuario);
    $this->db->update('usuario', $data);
    $consulta = $this->db->query("select id_usuario from usuario where id_usuario=$id_usuario");
    return $consulta->result();
  }

  public function obtenerCorreoAccesoWebIntranet($id_usuario)
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $this->db = $this->load->database('intranet', TRUE);
    $q = " SELECT u.correo_acceso
        from usuario_appweb u
        where id_usuario_cliente=$id_usuario";
    $query = $this->db->query($q)->result();
    return $query;
  }

  public function ActualizarNombreYPasswordDeUsuarioIntranet($id_usuario_cliente, $id_cliente, $data)
  {
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    $this->db->where('id_cliente', $id_cliente);
    $this->db->where('id_usuario_cliente', $id_usuario_cliente);
    $this->db->update('usuario_appweb', $data);
    $consulta = $this->db->query("select id_usuario_cliente, id_cliente from usuario_appweb where id_usuario_cliente=$id_usuario_cliente and id_cliente=id_cliente");
    return $consulta->result();
  }
}
