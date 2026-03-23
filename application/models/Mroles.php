<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mroles extends CI_Model
{
  function __construct()
  {
    parent::__construct();
  }

  public function ListarRoles()
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $this->load->helper('tenant'); $this->db = get_tenant_db($this);
    $q = "SELECT r.id_rol, r.nombre_rol from rol r where r.id_rol>1 order by r.nombre_rol asc";

    $consulta = $this->db->query($q);
    $sampleData = $consulta->result();
    $data = array();

    foreach ($sampleData as $key) {
      array_push($data, array(
        "id_rol" => $key->id_rol,
        "nombre_rol" => $key->nombre_rol
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

  public function ListarPrivilegioDeRoles($id_rol)
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $this->load->helper('tenant'); $this->db = get_tenant_db($this);
    $q = " SELECT pu.id_pusuario, r.nombre_rol, ps.nombre_psistema, ps.nombre_vb, pu.autoriza, pu.id_rol, pu.id_psistema from pusuario pu
        join rol r on pu.id_rol=r.id_rol
        join psistema ps on pu.id_psistema=ps.id_psistema
        where ps.vigente=true AND ps.nombre_psistema='RESTO WEB' AND pu.id_rol=" . $id_rol . "
        order by ps.nombre_vb asc";
    $query = $this->db->query($q);
    return $query->result();
  }

  /*public function modulosDisponibles($id_rol)
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $this->load->helper('tenant'); $this->db = get_tenant_db($this);
    $q = "SELECT autoriza from pusuario where id_rol=$id_rol";
    $query = $this->db->query($q);
    return json_encode($query->result());
  }*/

  public function modulosDisponibles($id_rol)
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $this->load->helper('tenant'); $this->db = get_tenant_db($this);
    $q = "SELECT * from usuario u where u.id_rol=$id_rol";
    $query = $this->db->query($q);
    return json_encode($query->result());
  }

  public function BloquearAccesoModulos($id_pusuario)
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $this->load->helper('tenant'); $this->db = get_tenant_db($this);
    $q = "UPDATE pusuario set autoriza=false where id_pusuario=$id_pusuario";
    $consulta = $this->db->query($q);
    return $consulta;
  }

  public function HabilitarAccesoModulos($id_pusuario)
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $this->load->helper('tenant'); $this->db = get_tenant_db($this);
    $q = "UPDATE pusuario set autoriza=true where id_pusuario=$id_pusuario";
    $consulta = $this->db->query($q);
    return $consulta;
  }

  public function HabilitarAccesoTodosLosModulos($id_rol)
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $this->load->helper('tenant'); $this->db = get_tenant_db($this);
    $q = "UPDATE pusuario set autoriza=true where id_rol=$id_rol";
    $consulta = $this->db->query($q);
    return $consulta;
  }

  public function BloquearAccesoTodosLosModulos($id_rol)
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $this->load->helper('tenant'); $this->db = get_tenant_db($this);
    $q = "UPDATE pusuario set autoriza=false where id_rol=$id_rol";
    $consulta = $this->db->query($q);
    return $consulta;
  }


  public function grabarRoles($id, $data)
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $this->load->helper('tenant'); $this->db = get_tenant_db($this);
    if ($id) {
      $this->db->where('id_rol', $id);
      $this->db->update('rol', $data);
      $consulta = $this->db->query("select " . $id . " as id_rol");
    } else {
      $this->db->insert('rol', $data);
      $consulta = $this->db->query("SELECT CURRVAL('rol_id_rol_seq'::regclass) as id_rol");
    }
    return $consulta->result();
  }
}