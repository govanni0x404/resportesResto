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
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
    $q = " SELECT u.nombre_usuario, r.nombre_rol, u.esadmin, u.id_usuario
        from usuario u
        join rol r on u.id_rol=r.id_rol
        where id_usuario=?";
    $query = $this->db->query($q, array((int)$id_usuario))->result();
    return $query;
  }

  public function ActualizarNombreYPasswordDeUsuarioLocal($id_usuario, $data)
  {
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
    $this->db->where('id_usuario', $id_usuario);
    $this->db->update('usuario', $data);
    $consulta = $this->db->query("select id_usuario from usuario where id_usuario=?", array((int)$id_usuario));
    return $consulta->result();
  }

  public function obtenerCorreoAccesoWebIntranet($id_usuario)
  {
    if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    // 10-11-2026: la base de la intranet ya no se abre desde aquí (todo por API) y la API no
    // busca correos por id de usuario. El correo de quien inició sesión está en la sesión;
    // se devuelve con la misma forma de antes: arreglo de objetos con correo_acceso.
    if ((int)$id_usuario !== (int)$this->session->userdata('id_usuario')) {
      return array();
    }
    return array((object)array('correo_acceso' => (string)$this->session->userdata('correo_acceso')));
  }

  public function ActualizarNombreYPasswordDeUsuarioIntranet($id_usuario_cliente, $id_cliente, $data)
  {
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
    $this->db->where('id_cliente', $id_cliente);
    $this->db->where('id_usuario_cliente', $id_usuario_cliente);
    $this->db->update('usuario_appweb', $data);
    $consulta = $this->db->query("select id_usuario_cliente, id_cliente from usuario_appweb where id_usuario_cliente=? and id_cliente=id_cliente", array((int)$id_usuario_cliente));
    return $consulta->result();
  }
}
