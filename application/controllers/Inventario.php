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
        // Obtener primera bodega por defecto
        $bodegas = $this->Minventario->get_bodegas();
        $id_bodega_default = !empty($bodegas) ? $bodegas[0]->id_bodega : null;
        
        $data['bodegas'] = $bodegas;
        $data['inventarios'] = $this->Minventario->get_inventarios($id_bodega_default);
        
        $this->load->view('menuTop.php', $data);
        $this->load->view('menuFooter.php');
        $this->load->view('inventario/listadoinventario', $data);
        $this->load->view('inventario/js.php');
    }

    // AJAX: Obtener detalle de inventario
    public function detalle()
    {
        log_message('error', 'ENTRÓ A DETALLE');
        log_message('error', print_r($_POST, true));

        $id_inventario = $this->input->post('id_inventario');

        if (!$id_inventario) {
            echo json_encode([]);
            return;
        }

        $detalle = $this->Mdetalleinventario->get_detalle($id_inventario);
        echo json_encode($detalle);
    }

    // AJAX: Filtrar por bodega
    public function listar_por_bodega()
    {
        $id_bodega = $this->input->post('id_bodega');
        $inventarios = $this->Minventario->get_inventarios($id_bodega);
        
        header('Content-Type: application/json');
        echo json_encode($inventarios);
    }

    // AJAX: Eliminar items
    public function eliminar_items()
    {
        $ids = $this->input->post('ids');
        
        if (empty($ids)) {
            echo json_encode(['success' => false, 'message' => 'No se recibieron IDs']);
            return;
        }

        $resultado = $this->Mdetalleinventario->eliminar_items($ids);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $resultado,
            'message' => $resultado ? 'Items eliminados' : 'Error al eliminar'
        ]);
    }

    // // Ir a crear nuevo inventario
    // public function nuevo()
    // {
    //     $this->load->view('menuTop.php');
    //     $this->load->view('inventario/nuevo');
    //     $this->load->view('menuFooter.php');
    // }
}