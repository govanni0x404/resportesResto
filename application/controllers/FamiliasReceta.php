<?php
defined('BASEPATH') or exit('No direct script access allowed');

class FamiliasReceta extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model(array('MfamiliasReceta', 'Mroles'));
    }

    public function index()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $data = array(
            'nombreUsuario' => $this->session->userdata('nombre_usuario'),
            //'modulosDisponibles' => json_decode($this->Mroles->modulosDisponibles($this->session->userdata('id_rol')), true),
            //'verificarSiEsAdmin' => json_decode($this->Mroles->verificarSiEsAdmin($this->session->userdata('id_rol')), true)
        );
        $this->load->view('menuTop.php', $data);
        $this->load->view('familiasReceta/familiasReceta.php');
        $this->load->view('menuFooter.php');
        $this->load->view('familiasReceta/listadoFamiliasReceta.php');
        $this->load->view('familiasReceta/jsNuevaFamilia.php');
        $this->load->view('funciones.php');
    }

    public function ListadoFamiliasReceta()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        echo json_encode($this->MfamiliasReceta->ListadoFamiliasReceta());
    }

    public function grabarNuevaFamiliaReceta()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $d = $this->input->post('prod');
        $id = intval($d['id_familia_receta']);
        $miData = array(
            'nombre_familia_receta' => $d['nombre_familia_receta'],
        );
        $r = $this->MfamiliasReceta->grabarNuevaFamiliaReceta($id, $miData);
        echo json_encode($r);
    }
}