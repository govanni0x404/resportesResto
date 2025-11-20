<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Impuestos extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model(array('Mimpuestos', 'Mroles'));
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
        $this->load->view('impuestos/impuestos.php');
        $this->load->view('menuFooter.php');
        $this->load->view('impuestos/listadoImpuestos.php');
        $this->load->view('impuestos/jsNuevoImpuesto.php');
        $this->load->view('funciones.php');
    }

    public function ListadoDeImpuestos()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        echo json_encode($this->Mimpuestos->ListadoDeImpuestos());
    }

    public function grabarNuevoImpuesto()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $d = $this->input->post('prod');
        $id = intval($d['id_impuesto']);
        $miData = array(
            'nombre_impuesto' => $d['nombre_impuesto'],
            'valor' => $d['valor'],
            'observacion' => $d['observacion'],
            'codigo_sii' => $d['codigo_sii'],
            'recuperable' => false,
        );
        $r = $this->Mimpuestos->grabarNuevoImpuesto($id, $miData);
        echo json_encode($r);
    }
}