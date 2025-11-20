<?php
defined('BASEPATH') or exit('No direct script access allowed');

class FormasDePago extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model(array('MformasDePago', 'Mroles'));
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
        $this->load->view('formasDePago/formasDePago.php');
        $this->load->view('menuFooter.php');
        $this->load->view('formasDePago/listadoformasDePago.php');
        $this->load->view('formasDePago/jsNuevaFormaDePago.php');
        $this->load->view('funciones.php');
    }

    public function ListadoDeFormasDePago()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        echo json_encode($this->MformasDePago->ListadoDeFormasDePago());
    }

    public function grabarNuevaFormaDePago()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $d = $this->input->post('prod');
        $id = intval($d['id_fpago']);
        $miData = array(
            'nombre_fpago' => $d['nombre_fpago'],
            'escortesia' => $d['escortesia'],
            'boleta_electronica' => $d['boleta_electronica'],
            'vigente' => $d['vigente'],
            'comision' => 0,
            'permite_propina' => $d['permite_propina'],
        );
        $r = $this->MformasDePago->grabarNuevaFormaDePago($id, $miData);
        echo json_encode($r);
    }
}