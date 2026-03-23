<?php
defined('BASEPATH') or exit('No direct script access allowed');

class FamiliasInsumos extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model(array('MfamiliasInsumo', 'Mroles'));
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
        $this->load->view('familiasInsumo/familiaInsumo.php');
        $this->load->view('menuFooter.php');
        $this->load->view('familiasInsumo/listadoFamiliaInsumo.php');
        $this->load->view('familiasInsumo/jsNuevaFamilia.php');
        $this->load->view('funciones.php');
    }

    public function ListadoFamiliasInsumo()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        echo json_encode($this->MfamiliasInsumo->ListadoFamiliasInsumo());
    }

    public function grabarNuevaFamiliaInsumo()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $d = $this->input->post('prod');
        $id = intval($d['id_grupo_insumo']);
        $miData = array(
            'nombre_grupo_insumo' => $d['nombre_grupo_insumo'],
        );
        $r = $this->MfamiliasInsumo->grabarNuevaFamiliaInsumo($id, $miData);
        if ($r && isset($r['id_grupo_insumo'])) {
            echo json_encode(array(
                'success' => true,
                'id_grupo_insumo' => $r['id_grupo_insumo'],
                'message' => 'Familia/Grupo Insumo grabado exitosamente'
            ));
        } else {
            echo json_encode($r);
        }
    }

    public function ListarFamiliasInsumo()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        echo json_encode($this->MfamiliasInsumo->ListarFamiliasInsumo());
    }
}