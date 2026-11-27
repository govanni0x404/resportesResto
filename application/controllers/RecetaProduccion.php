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
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        echo json_encode($this->MrecetaProduccion->ListadoRecetasProduccion());
    }

    public function grabarRecetaProduccion()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
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
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $nombre = $this->input->post('nombre');
        echo json_encode($this->MrecetaProduccion->buscarRecetaPorNombre($nombre));
    }

    public function obtenerDetalleReceta()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $id = $this->input->post('id_receta_produccion');
        echo json_encode($this->MrecetaProduccion->obtenerDetalleReceta($id));
    }

    public function grabarDetalleReceta()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
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
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $id = $this->input->post('id_detalle');
        $r = $this->MrecetaProduccion->eliminarDetalleReceta($id);
        echo json_encode(array('success' => $r));
    }

    /** v1.37.0: editor de la receta de producción (Insumos > Producción). Sin id: recetas e insumos; con id: una receta. */
    public function editor()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) { set_status_header(401); echo json_encode(array('error' => 'Sesión expirada')); return; }
        $id = (int)$this->input->get_post('id');
        $r = $id > 0 ? $this->MrecetaProduccion->completa($id) : $this->MrecetaProduccion->datosEditor((int)$this->input->get_post('id_insumo'));
        if (isset($r['error'])) set_status_header(400);
        echo json_encode($r);
    }

    /** v1.37.0: graba la receta de producción con todos sus insumos de una vez. */
    public function guardarCompleta()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) { set_status_header(401); echo json_encode(array('error' => 'Sesión expirada')); return; }
        $r = $this->MrecetaProduccion->guardarCompleta($this->input->post());
        if (isset($r['error'])) set_status_header(400);
        echo json_encode($r);
    }
}