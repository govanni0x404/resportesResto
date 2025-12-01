<?php
defined('BASEPATH') or exit('No direct script access allowed');

class PrivilegiosUsuario extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('MprivilegiosUsuario');
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
        $this->load->view('privilegios/privilegiosUsuario.php');
        $this->load->view('menuFooter.php');
        $this->load->view('privilegios/listadoUsuariosPrivilegios.php');
        $this->load->view('privilegios/jsPrivilegiosUsuario.php');
        $this->load->view('funciones.php');
    }

    public function ListadoDeUsuarios()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        echo json_encode($this->MprivilegiosUsuario->ListadoDeUsuarios());
    }

    public function ListarPrivilegiosDeUsuario()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        $id_usuario = $this->input->post('id_usuario');
        echo json_encode($this->MprivilegiosUsuario->ListarPrivilegiosDeUsuario($id_usuario));
    }

    public function actualizarPrivilegio()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $d = $this->input->post('prod');
        $id_pusuario = intval($d['id_pusuario']);
        $autoriza = $d['autoriza'] === 'true' || $d['autoriza'] === true;
        
        $r = $this->MprivilegiosUsuario->actualizarPrivilegio($id_pusuario, $autoriza);
        echo json_encode($r);
    }

    public function HabilitarTodosLosPrivilegios()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        $id_rol = $this->input->post('id_rol');
        echo json_encode($this->MprivilegiosUsuario->HabilitarTodosLosPrivilegios($id_rol));
    }

    public function BloquearTodosLosPrivilegios()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        $id_rol = $this->input->post('id_rol');
        echo json_encode($this->MprivilegiosUsuario->BloquearTodosLosPrivilegios($id_rol));
    }
}