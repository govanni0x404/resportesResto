<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Produccion extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model(array('Mproduccion', 'Minsumos', 'MrecetaProduccion'));
    }

    public function listadoProducciones()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $data = $this->Mproduccion->listadoProducciones();
        echo json_encode($data);
    }

    public function grabarProduccion()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        try {
            $d = $this->input->post('produccion');
            
            // Obtener stock actual
            $stock_actual = $this->Mproduccion->obtenerStockActual(
                $d['id_insumo'], 
                $d['id_bodega']
            );
            
            $nuevo_stock = $stock_actual + floatval($d['cantidad']);
            
            $miData = array(
                'fecha' => $d['fecha'],
                'id_insumo' => $d['id_insumo'],
                'id_bodega' => $d['id_bodega'],
                'cantidad' => $d['cantidad'],
                'stock_actual' => $stock_actual,
                'nuevo_stock' => $nuevo_stock,
                'costo_total' => $d['costo_total'],
                'costo_unitario' => $d['costo_unitario'],
                'id_usuario' => $this->session->userdata('id_usuario'),
                'fecha_transaccion' => date('Y-m-d H:i:s'),
                'observaciones' => isset($d['observaciones']) ? $d['observaciones'] : ''
            );
            
            $id_produccion = $this->Mproduccion->grabarProduccion($miData);
            
            // Actualizar stock
            $this->Mproduccion->actualizarStock(
                $d['id_insumo'], 
                $d['id_bodega'], 
                $nuevo_stock
            );
            
            echo json_encode(array(
                'success' => true,
                'id_produccion' => $id_produccion,
                'message' => 'Producción registrada exitosamente'
            ));
            
        } catch (Exception $e) {
            log_message('error', 'Error en grabarProduccion: ' . $e->getMessage());
            echo json_encode(array(
                'success' => false,
                'message' => 'Error al procesar la solicitud: ' . $e->getMessage()
            ));
        }
    }

    public function obtenerStockActual()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $id_insumo = $this->input->post('id_insumo');
        $id_bodega = $this->input->post('id_bodega');
        
        $stock = $this->Mproduccion->obtenerStockActual($id_insumo, $id_bodega);
        
        echo json_encode(array('stock_actual' => $stock));
    }

    public function obtenerCostoPPP()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $id_insumo = $this->input->post('id_insumo');
        $id_bodega = $this->input->post('id_bodega');
        
        $costo = $this->Mproduccion->obtenerCostoPPP($id_insumo, $id_bodega);
        
        echo json_encode(array('costo_ppp' => $costo));
    }

    public function obtenerDatosInsumoCompleto()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $id_insumo = $this->input->post('id_insumo');
        
        $datos = $this->Mproduccion->obtenerDatosInsumoCompleto($id_insumo);
        
        echo json_encode($datos);
    }
}