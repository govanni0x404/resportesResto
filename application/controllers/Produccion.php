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
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $data = $this->Mproduccion->listadoProducciones();
        echo json_encode($data);
    }

    public function grabarProduccion()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        try {
            // Obtener datos JSON
            $jsonData = $this->input->post('produccion');
            $d = json_decode((string)$jsonData, true);
            
            if (!$d || !isset($d['insumos']) || count($d['insumos']) == 0) {
                echo json_encode(array(
                    'success' => false,
                    'message' => 'No se recibieron insumos para la producción'
                ));
                return;
            }
            
            // Iniciar transacción sobre la BD del cliente (la misma conexión cacheada que usa Mproduccion).
            // Antes se usaba $this->db del controlador, que es la conexión por defecto y no la del tenant,
            // así que el rollback por stock insuficiente no deshacía los movimientos ya grabados.
            $this->load->helper('tenant');
            $dbTenant = get_tenant_db($this);
            $dbTenant->trans_start();
            
            // 18-11-2026 (prueba en copia): el trigger trg_produccion del POS (f_produccion) ya mueve el stock en cada fila que
            // se graba en produccion (cantidad_entrada += cantidad; negativa = consumo) y pisa insumo.costo_neto_ppp con el
            // costo_unitario. Si además se movía aquí, el consumo bajaba el doble y lo producido subía el doble. Solo se
            // mueve a mano si el trigger no está (o está desactivado) en este cliente.
            $porTrigger = $this->Mproduccion->triggerMueveStock();

            // Procesar cada insumo de la receta
            $costoTotalProduccion = 0;
            foreach ($d['insumos'] as $insumo) {
                // 14-11-2026 (QA): se valida lo que llega. Con cantidad -100 el stock SUBÍA de 20 a 120, y el costo lo
                // mandaba el navegador (el trigger f_produccion lo copia a insumo.costo_neto_ppp).
                $insumo['id_insumo'] = (int)($insumo['id_insumo'] ?? 0);
                $insumo['id_bodega'] = (int)($insumo['id_bodega'] ?? 0);
                $cant = str_replace(',', '.', trim((string)($insumo['cantidad'] ?? '')));
                $nombre = (string)($insumo['nombre_insumo'] ?? ('insumo ' . $insumo['id_insumo']));
                if ($insumo['id_insumo'] <= 0 || $insumo['id_bodega'] <= 0 || !is_numeric($cant) || (float)$cant <= 0) {
                    $dbTenant->trans_rollback();
                    echo json_encode(array('success' => false,
                        'message' => 'Cantidad inválida para ' . $nombre . ': debe ser un número mayor que 0'));
                    return;
                }
                // v1.34.0: la pantalla manda la cantidad en la unidad de RECETA del insumo (ej. 500 GRAMO); el stock y el
                // costo van en la unidad de INVENTARIO (0,5 KILOGRAMO). Antes se descontaban 500 kilos.
                $insumo['cantidad_receta'] = (float)$cant;
                $insumo['cantidad'] = $this->Mproduccion->recetaAInventario($insumo['id_insumo'], (float)$cant);
                if ($insumo['cantidad'] === null || $insumo['cantidad'] <= 0) {
                    $dbTenant->trans_rollback();
                    echo json_encode(array('success' => false, 'message' => 'El insumo ' . $nombre . ' no existe o su cantidad es muy chica'));
                    return;
                }
                // Costo: el promedio (PPP) del insumo en la base, no el que manda la pantalla
                $insumo['costo_neto'] = $this->Mproduccion->obtenerCostoPPP($insumo['id_insumo']);
                $insumo['costo_total'] = round($insumo['costo_neto'] * $insumo['cantidad'], 2);

                // Obtener stock actual (fila bloqueada hasta terminar: dos producciones a la vez no pisan el saldo)
                $stock_actual = $this->Mproduccion->obtenerStockActual(
                    $insumo['id_insumo'], 
                    $insumo['id_bodega'],
                    true
                );
                
                // Validar que hay suficiente stock
                if ($stock_actual < $insumo['cantidad']) {
                    $dbTenant->trans_rollback();
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
                
                // Actualizar stock (restar): lo hace el trigger al grabar la fila, pero como "entrada negativa"; se pasa a
                // salida (cantidad_salida) sin cambiar el saldo. Sin trigger, la salida se hace aquí.
                if ($porTrigger) {
                    $this->Mproduccion->entradaNegativaComoSalida($insumo['id_insumo'], $insumo['id_bodega'], $insumo['cantidad']);
                } else {
                    $this->Mproduccion->salidaStock($insumo['id_insumo'], $insumo['id_bodega'], $insumo['cantidad']);
                }
                
                $costoTotalProduccion += floatval($insumo['costo_total']);
            }
            
            // 18-11-2026: entrada de lo producido (insumo elaborado) a la bodega de destino. Pedido del usuario: "si se
            // crean 10 panes y existe el producto panes, se le suma el stock". Los productos no tienen stock propio (al
            // venderse descuentan los insumos de su receta), así que sumar al insumo elaborado es darle stock al producto.
            $id_insumo_elaborado = (int)(isset($d['id_insumo_elaborado']) ? $d['id_insumo_elaborado'] : 0);
            $id_bodega_destino = (int)(isset($d['id_bodega_destino']) ? $d['id_bodega_destino'] : 0);
            $cantidad_producida = (float)(isset($d['cantidad_producida']) ? $d['cantidad_producida'] : 0);
            if ($id_insumo_elaborado <= 0 || $id_bodega_destino <= 0 || !is_finite($cantidad_producida) || $cantidad_producida <= 0) {
                $dbTenant->trans_rollback();
                echo json_encode(array('success' => false, 'message' => 'Falta el producto elaborado, su bodega de destino o la cantidad producida (mayor que 0)'));
                return;
            }
            if (!$dbTenant->query('SELECT 1 FROM insumo WHERE id_insumo = ?', array($id_insumo_elaborado))->row()
                || !$dbTenant->query('SELECT 1 FROM bodega WHERE id_bodega = ?', array($id_bodega_destino))->row()) {
                $dbTenant->trans_rollback();
                echo json_encode(array('success' => false, 'message' => 'El producto elaborado o la bodega de destino no existen'));
                return;
            }
            // El trigger solo hace UPDATE: sin fila de stock en la bodega de destino, la entrada se perdía. Se crea en 0.
            $this->Mproduccion->asegurarFilaStock($id_insumo_elaborado, $id_bodega_destino);
            $stock_destino_actual = (float)$this->Mproduccion->obtenerStockActual($id_insumo_elaborado, $id_bodega_destino, true);
            // Costo promedio del elaborado: con el saldo que había ANTES de la entrada
            $saldo_elaborado_antes = $this->Mproduccion->saldoTotal($id_insumo_elaborado);
            $costo_elaborado_antes = $this->Mproduccion->costoActual($id_insumo_elaborado);
            $this->Mproduccion->grabarProduccion(array(
                'fecha' => $d['fecha'],
                'id_insumo' => $id_insumo_elaborado,
                'id_bodega' => $id_bodega_destino,
                'cantidad' => $cantidad_producida,   // positivo: entrada
                'stock_actual' => $stock_destino_actual,
                'nuevo_stock' => $stock_destino_actual + $cantidad_producida,
                'costo_total' => $costoTotalProduccion,
                'costo_unitario' => round($costoTotalProduccion / $cantidad_producida, 4),
                'id_usuario' => $this->session->userdata('id_usuario'),
                'fecha_transaccion' => date('Y-m-d H:i:s'),
                'observaciones' => mb_substr('Producción de ' . (isset($d['nombre_insumo_elaborado']) ? $d['nombre_insumo_elaborado'] : '')
                    . (!empty($d['observaciones']) ? ' - ' . $d['observaciones'] : ''), 0, 250),
            ));
            if (!$porTrigger) $this->Mproduccion->entradaStock($id_insumo_elaborado, $id_bodega_destino, $cantidad_producida);
            $this->Mproduccion->marcarElaborado($id_insumo_elaborado);   // v1.34.0: queda como sub receta
            // Después de grabar: el trigger dejó el costo de esta producción (promedio simple); se reemplaza por el ponderado
            $costo_unitario_elaborado = $this->Mproduccion->actualizarCostoElaborado($id_insumo_elaborado, $cantidad_producida,
                $costoTotalProduccion, $saldo_elaborado_antes, $costo_elaborado_antes);

            // Ejemplo original (reemplazado por lo de arriba el 18-11-2026):
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
            $dbTenant->trans_complete();
            
            if ($dbTenant->trans_status() === FALSE) {
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
            if (isset($dbTenant)) $dbTenant->trans_rollback();
            log_message('error', 'Error en grabarProduccion: ' . $e->getMessage());
            echo json_encode(array(
                'success' => false,
                'message' => 'Error al procesar la solicitud: ' . $e->getMessage()
            ));
        }
    }

    /** v1.34.0: la receta de producción del insumo con sus insumos, para cargarla al producir. */
    public function recetaParaProducir()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        header('Content-Type: application/json');
        echo json_encode(array('receta' => $this->Mproduccion->recetaParaProducir((int)$this->input->post('id_insumo'))));
    }

    public function obtenerStockActual()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $id_insumo = $this->input->post('id_insumo');
        $id_bodega = $this->input->post('id_bodega');
        
        $stock = $this->Mproduccion->obtenerStockActual($id_insumo, $id_bodega);
        
        echo json_encode(array('stock_actual' => $stock));
    }

    public function obtenerCostoPPP()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $id_insumo = $this->input->post('id_insumo');

        $costo = $this->Mproduccion->obtenerCostoPPP($id_insumo);
        
        echo json_encode(array('costo_ppp' => $costo));
    }

    public function obtenerDatosInsumoCompleto()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $id_insumo = $this->input->post('id_insumo');
        
        $datos = $this->Mproduccion->obtenerDatosInsumoCompleto($id_insumo);
        
        echo json_encode($datos);
    }
}