<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Inventario extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model([
            'Minventario',
            'Mdetalleinventario'
        ]);
    }

    public function index()
    {
        $data['inventarios'] = $this->Minventario->get_inventarios();
        $this->load->view('menuTop.php', $data);
        $this->load->view('inventario/listadoinventario', $data);
        $this->load->view('menuFooter.php');
    }

    // AJAX
    public function detalle($id_inventario)
    {
        $detalle = $this->Mdetalleinventario->get_detalle($id_inventario);
        echo json_encode($detalle);
    }
}
