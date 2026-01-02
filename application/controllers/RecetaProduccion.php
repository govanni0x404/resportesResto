<?php
defined('BASEPATH') or exit('No direct script access allowed');

class RecetaProduccion extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model(array('MrecetaProduccion', 'Minsumos'));
    }

    public function ListadoRecetasProduccion()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        echo json_encode($this->MrecetaProduccion->ListadoRecetasProduccion());
    }

    public function grabarRecetaProduccion()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        try {
            $d = $this->input->post('receta');
            $id = intval($d['id_receta_produccion']);

            $miData = array(
                'nombre_receta_produccion' => $d['nombre_receta_produccion'],
                'cantidad' => $d['cantidad'],
                'id_insumo' => $d['id_insumo'],
                'id_bodega' => isset($d['id_bodega']) ? $d['id_bodega'] : 0, // Bodega por defecto
                'id_umedida' => $d['id_umedida'],
                'vigente' => true  // Siempre vigente al crear
            );
            
            $r = $this->MrecetaProduccion->grabarRecetaProduccion($id, $miData);
            
            header('Content-Type: application/json');
            echo json_encode($r);
            
        } catch (Exception $e) {
            log_message('error', 'Error en grabarRecetaProduccion: ' . $e->getMessage());
            echo json_encode(array(
                'error' => true,
                'message' => 'Error al procesar la solicitud: ' . $e->getMessage()
            ));
        }
    }

    public function buscarRecetaPorNombre()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $nombre = $this->input->post('nombre');
        echo json_encode($this->MrecetaProduccion->buscarRecetaPorNombre($nombre));
    }

    public function obtenerDetalleReceta()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $id = $this->input->post('id_receta_produccion');
        echo json_encode($this->MrecetaProduccion->obtenerDetalleReceta($id));
    }

    public function grabarDetalleReceta()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $data = array(
            'id_receta_produccion' => $this->input->post('id_receta_produccion'),
            'id_insumo' => $this->input->post('id_insumo'),
            'cantidad' => $this->input->post('cantidad'),
            'id_umedida' => $this->input->post('id_umedida')
        );
        
        $r = $this->MrecetaProduccion->grabarDetalleReceta($data);
        echo json_encode(array('success' => true, 'id' => $r));
    }

    public function eliminarDetalleReceta()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $id = $this->input->post('id_detalle');
        $r = $this->MrecetaProduccion->eliminarDetalleReceta($id);
        echo json_encode(array('success' => $r));
    }
}