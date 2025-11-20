<?php
defined('BASEPATH') or exit('No direct script access allowed');

class VentasPorProducto extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model(array('MventasPorProducto', 'Mroles', 'Mconfiguraciones'));
    }

    public function index()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $data = array(
            'nombreUsuario' => $this->session->userdata('nombre_usuario'),
            //'modulosDisponibles' => json_decode($this->Mroles->modulosDisponibles($this->session->userdata('id_rol')), true),
        );
        $this->load->view('menuTop.php', $data);
        $this->load->view('ventasPorProducto/ventasPorProducto.php', $data);
        $this->load->view('menuFooter.php');
        $this->load->view('ventasPorProducto/js.php' , $data);
    }

    public function ListadoVentasPorProducto()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $desde = $this->input->post('desde');
        $hasta = $this->input->post('hasta');
        echo json_encode($this->MventasPorProducto->ListadoVentasPorProducto($desde, $hasta));
    }

    public function pieVentasPorProducto()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $desde = $this->input->post('desde');
        $hasta = $this->input->post('hasta');
        echo json_encode($this->MventasPorProducto->pieVentasPorProducto($desde, $hasta));
    }

}