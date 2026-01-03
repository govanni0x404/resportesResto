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
            // Obtener datos JSON
            $jsonData = $this->input->post('produccion');
            $d = json_decode($jsonData, true);
            
            if (!$d || !isset($d['insumos']) || count($d['insumos']) == 0) {
                echo json_encode(array(
                    'success' => false,
                    'message' => 'No se recibieron insumos para la producción'
                ));
                return;
            }
            
            // Iniciar transacción
            $this->db->trans_start();
            
            // Procesar cada insumo de la receta
            $costoTotalProduccion = 0;
            foreach ($d['insumos'] as $insumo) {
                // Obtener stock actual
                $stock_actual = $this->Mproduccion->obtenerStockActual(
                    $insumo['id_insumo'], 
                    $insumo['id_bodega']
                );
                
                // Validar que hay suficiente stock
                if ($stock_actual < $insumo['cantidad']) {
                    $this->db->trans_rollback();
                    echo json_encode(array(
                        'success' => false,
                        'message' => 'Stock insuficiente para ' . $insumo['nombre_insumo'] . 
                                   '. Disponible: ' . $stock_actual . ', Requerido: ' . $insumo['cantidad']
                    ));
                    return;
                }
                
                // Calcular nuevo stock (restar lo usado)
                $nuevo_stock = $stock_actual - floatval($insumo['cantidad']);
                
                // Registrar movimiento de salida
                $miData = array(
                    'fecha' => $d['fecha'],
                    'id_insumo' => $insumo['id_insumo'],
                    'id_bodega' => $insumo['id_bodega'],
                    'cantidad' => -1 * $insumo['cantidad'], // Negativo porque es salida
                    'stock_actual' => $stock_actual,
                    'nuevo_stock' => $nuevo_stock,
                    'costo_total' => $insumo['costo_total'],
                    'costo_unitario' => $insumo['costo_neto'],
                    'id_usuario' => $this->session->userdata('id_usuario'),
                    'fecha_transaccion' => date('Y-m-d H:i:s'),
                    'observaciones' => 'Consumo para producción - ' . $d['nombre_insumo_elaborado']
                );
                
                $this->Mproduccion->grabarProduccion($miData);
                
                // Actualizar stock (restar)
                $this->Mproduccion->actualizarStock(
                    $insumo['id_insumo'], 
                    $insumo['id_bodega'], 
                    $nuevo_stock
                );
                
                $costoTotalProduccion += floatval($insumo['costo_total']);
            }
            
            // TODO: Agregar entrada del producto elaborado a la bodega destino
            // Esto dependerá de tu estructura de base de datos y lógica de negocio
            // Ejemplo:
            /*
            $stock_destino_actual = $this->Mproduccion->obtenerStockActual(
                $id_insumo_elaborado, 
                $d['id_bodega_destino']
            );
            
            $nuevo_stock_destino = $stock_destino_actual + $d['cantidad_producida'];
            $costo_unitario_elaborado = $costoTotalProduccion / $d['cantidad_producida'];
            
            $miDataEntrada = array(
                'fecha' => $d['fecha'],
                'id_insumo' => $id_insumo_elaborado,
                'id_bodega' => $d['id_bodega_destino'],
                'cantidad' => $d['cantidad_producida'],
                'stock_actual' => $stock_destino_actual,
                'nuevo_stock' => $nuevo_stock_destino,
                'costo_total' => $costoTotalProduccion,
                'costo_unitario' => $costo_unitario_elaborado,
                'id_usuario' => $this->session->userdata('id_usuario'),
                'fecha_transaccion' => date('Y-m-d H:i:s'),
                'observaciones' => 'Producción de ' . $d['nombre_insumo_elaborado'] . ' - ' . $d['observaciones']
            );
            
            $id_produccion_entrada = $this->Mproduccion->grabarProduccion($miDataEntrada);
            
            // Actualizar stock destino
            $this->Mproduccion->actualizarStock(
                $id_insumo_elaborado, 
                $d['id_bodega_destino'], 
                $nuevo_stock_destino
            );
            
            // Actualizar precio promedio ponderado del producto elaborado
            $this->Mproduccion->actualizarPPP(
                $id_insumo_elaborado,
                $d['id_bodega_destino'],
                $costo_unitario_elaborado,
                $nuevo_stock_destino
            );
            */
            
            // Finalizar transacción
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                echo json_encode(array(
                    'success' => false,
                    'message' => 'Error al procesar la transacción'
                ));
            } else {
                echo json_encode(array(
                    'success' => true,
                    'message' => 'Producción registrada exitosamente',
                    'costo_total' => $costoTotalProduccion
                ));
            }
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
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