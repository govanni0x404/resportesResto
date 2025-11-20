<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Delivery extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model(array('Mdelivery', 'Mroles'));
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
        $this->load->view('delivery/sector.php');
        $this->load->view('menuFooter.php');
        $this->load->view('delivery/listadoDeSector.php');
        $this->load->view('delivery/jsNuevoSector.php');
        $this->load->view('funciones.php');
    }

    public function ListadoDeSectores()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        echo json_encode($this->Mdelivery->ListadoDeSectores());
    }

    public function grabarNuevoSector()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $d = $this->input->post('prod');
        $id = intval($d['id_sector']);
        $miData = array(
            'nombre_sector' => $d['nombre_sector'],
        );
        $r = $this->Mdelivery->grabarNuevoSector($id, $miData);
        echo json_encode($r);
    }
}
