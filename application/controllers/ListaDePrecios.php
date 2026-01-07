<?php
defined('BASEPATH') or exit('No direct script access allowed');

class ListaDePrecios extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model(array('MlistaDePrecios', 'Mroles'));
    }

    public function index()
    {

        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $data = array(
            'nombreUsuario' => $this->session->userdata('nombre_usuario'),
        );
        $this->load->view('menuTop.php', $data);
        $this->load->view('listaDePrecios/listaDePrecios.php');
        $this->load->view('menuFooter.php');
        $this->load->view('listaDePrecios/js.php');
    }


    public function ListadoListasDePrecios()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        echo json_encode($this->MlistaDePrecios->ListadoListasDePrecios());
    }

    public function ListadoDeFamilias()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        echo json_encode($this->MlistaDePrecios->ListadoDeFamilias());
    }

    public function InformeListaDePrecios()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $id_lprecio = $this->input->post('id_lprecio');
        $id_familia = $this->input->post('id_familia');
        echo json_encode($this->MlistaDePrecios->InformeListaDePrecios($id_lprecio, $id_familia));
    }

    public function grabarNuevaListaDePrecios()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $d = $this->input->post('prod');
        $id = intval($d['id_lprecio']);
        $miData = array(
            'nombre_lprecio' => $d['nombre_lprecio'],
        );
        $r = $this->MlistaDePrecios->grabarNuevaListaDePrecios($id, $miData);
        echo json_encode($r);
    }

    public function obtenerListayPreciosDeVenta()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $idProducto = $this->input->post('id_producto');
        echo json_encode($this->MlistaDePrecios->obtenerListayPreciosDeVenta($idProducto));
    }

    public function grabarPrecios()
    { // grabar precios de las distitntas listas
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $d = $this->input->post('detalle');
        $respuesta = array();
        foreach ($d as $value) {
            //$idProducto=$value['id_producto'];
            $idDetalleLprecio = $value['id_detalle_lprecio'];
            $miData = array(
                'total' => $value['total']
            );
            $r = $this->MlistaDePrecios->grabarPrecios($idDetalleLprecio, $miData);
            $respueta[] = $r;
        }
        echo json_encode($respueta);
    }
}
