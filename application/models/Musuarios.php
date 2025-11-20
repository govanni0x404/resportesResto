<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Musuarios extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    // LISTAR USUARIOS DEL SISTEMA (Local) - Sin cambios
    public function ListarUsuariosDelSistema()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        $q = "SELECT u.*, r.nombre_rol from usuario u
             join rol r on r.id_rol=u.id_rol
             order by nombre_usuario asc";
        // ... (resto del código de ListarUsuariosDelSistema es funcional) ...
        $consulta = $this->db->query($q);
        $sampleData = $consulta->result();
        $data = array();

        foreach ($sampleData as $key) {
            array_push($data, array(
                "id_usuario" => $key->id_usuario,
                "nombre_usuario" => $key->nombre_usuario,
                "clave" => $key->clave,
                'esadmin' => ($key->esadmin == 't' ? 'SI' : 'NO'),
                'esgarzon' => ($key->esgarzon == 't' ? 'SI' : 'NO'),
                "nombre_rol" => $key->nombre_rol,
                'vigente' => ($key->vigente == 't' ? 'SI' : 'NO'),
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

    // LISTAR ROLES DE USUARIOS (Local) - Sin cambios
    public function ListarRolesUsuarios()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        $q = "SELECT id_rol, nombre_rol from rol order by nombre_rol asc";
        $query = $this->db->query($q)->result();
        return $query;
    }

    // GRABAR USUARIOS (Local) - Sin cambios, ya que estaba bien para la BD local.
    public function grabarUsuariosLocal($id, $data)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        if ($id) {
            $this->db->where('id_usuario', $id);
            $this->db->update('usuario', $data);
            $consulta = $this->db->query("select " . $id . " as id_usuario");
        } else {
            $this->db->insert('usuario', $data);
            // Asegúrate que el nombre de la secuencia es 'usuario_id_usuario_seq'
            $consulta = $this->db->query("SELECT CURRVAL('usuario_id_usuario_seq'::regclass) as id_usuario");
        }
        return $consulta->result();
    }
    
    // ELIMINAMOS EL MÉTODO grabarUsuariosIntranet
}

  /*public function grabarUsuariosIntranet($id, $miDataIntranet, $idCliente)
  {
    if (strlen($this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
    if ($id) {
      $this->db->where('id_cliente', $idCliente);
      $this->db->where('id_usuario_cliente', $id);
      $this->db->update('usuario_appweb', $miDataIntranet);
      $consulta = $this->db->query("select " . $id . " as id_usuario_appweb");
    } else {
      $this->db->insert('usuario_appweb', $miDataIntranet);
      $consulta = $this->db->query("SELECT CURRVAL('usuario_appweb_id_usuario_usuario_appweb_seq'::regclass) as id_usuario_appweb");
    }
    return $consulta->result();
  }*/