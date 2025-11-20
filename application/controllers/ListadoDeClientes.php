<?php
defined('BASEPATH') or exit('No direct script access allowed');

class ListadoDeClientes extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model(array('MlistadoDeClientes', 'Mroles'));
    }

    public function index()
    {

        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $data = array(
            'nombreUsuario' => $this->session->userdata('nombre_usuario'),
            //'modulosDisponibles' => json_decode($this->Mroles->modulosDisponibles($this->session->userdata('id_rol')), true)
        );
        $this->load->view('menuTop.php', $data);
        $this->load->view('listadoDeClientes/listadoDeClientes.php');
        $this->load->view('menuFooter.php');
        $this->load->view('listadoDeClientes/js.php');

        } 
    

    public function ListadoDeClientes()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        echo json_encode($this->MlistadoDeClientes->ListadoDeClientes());
    }
}