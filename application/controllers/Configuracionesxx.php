<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Configuraciones extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model(array('Mconfiguraciones', 'Mroles'));
    }

    public function index()
    {
        /*
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $data = array(
            'nombreUsuario' => $this->session->userdata('nombre_usuario'),
            'modulosDisponibles' => json_decode($this->Mroles->modulosDisponibles($this->session->userdata('id_rol')), true),
        );
        $this->load->view('menuTop.php', $data);
        $this->load->view('configuraciones/configuraciones.php');
        $this->load->view('menuFooter.php');
        $this->load->view('configuraciones/js.php');*/
        $data = array(
            'nombreUsuario' => $this->session->userdata('nombre_usuario'),
           // 'modulosDisponibles' => json_decode($this->Mroles->modulosDisponibles($this->session->userdata('id_rol')), true)
        );

        if ($this->session->userdata('nombre_usuario')) {
            if ($this->session->userdata('id_rol') == 1) {
                $this->load->view('menuTop.php', $data);
                $this->load->view('configuraciones/configuraciones.php');
                $this->load->view('menuFooter.php');
                $this->load->view('configuraciones/js.php');
            } else {
                redirect(base_url());
            }
        }
    }

    public function obtenerConfiguracionesRestoWeb()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        echo json_encode($this->Mconfiguraciones->obtenerConfiguracionesRestoWeb());
    }

    public function grabarConfiguraciones()
    {
        if (strlen($this->session->userdata('nombre_usuario')) > 0) {
            $d = $this->input->post('prod');
            $miData = array(
                'desglozar_monto_neto_iva_total_en_reportes' => $d['desglozar_monto_neto_iva_total_en_reportes'],
                'desglozar_solo_monto_total_en_reportes' => $d['desglozar_solo_monto_total_en_reportes'],
            );
            $r = $this->Mconfiguraciones->grabarConfiguraciones($miData);
            echo json_encode($r);
        }
    }
}
