<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Usuario extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model(array('Musuarios'));
    }

    public function index()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        $data = array(
            'nombreUsuario' => $this->session->userdata('nombre_usuario'),
        );
        $this->load->view('menuTop.php', $data);
        $this->load->view('usuarios/usuarios.php');
        $this->load->view('menuFooter.php');
        $this->load->view('usuarios/listadoUsuarios.php');
        $this->load->view('usuarios/jsNuevoUsuario.php');
        $this->load->view('funciones.php');
    }

    public function ListadoDeUsuarios()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        // AGREGAR ESTO TEMPORALMENTE PARA VER ERRORES
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        try {
            $resultado = $this->Musuarios->ListadoDeUsuarios();
            echo json_encode($resultado);
        } catch (Exception $e) {
            echo json_encode(array(
                'error' => true,
                'message' => $e->getMessage()
            ));
        }
    }

    public function grabarNuevoUsuario()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $d = $this->input->post('prod');
        $id = intval($d['id_usuario']);
        
        $miData = array(
            'nombre_usuario' => $d['nombre_usuario'],
            'clave' => $d['clave_usuario'],
            'esadmin' => false,
            'esgarzon' => false,
            'id_rol' => 4, // Rol por defecto
            'vigente' => true
        );
        
        $r = $this->Musuarios->grabarNuevoUsuario($id, $miData);
        echo json_encode($r);
    }

    public function editarUsuario()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }

        $d = $this->input->post('prod');
        $id = intval($d['id_usuario']);

        // Convertir vigente a boolean
        $vigente_value = $d['vigente'];
        $vigente_bool = ($vigente_value === 'true' || $vigente_value === true || $vigente_value === 't');

        // Datos que ahora sí se pueden editar
        $miData = array(
            'nombre_usuario' => $d['nombre_usuario'],
            'clave'          => $d['clave_usuario'],
            'vigente'        => $vigente_bool
        );

        $r = $this->Musuarios->editarUsuario($id, $miData);
        echo json_encode($r);
    }
}