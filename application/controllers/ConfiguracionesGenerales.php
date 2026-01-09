<?php
// application/controllers/Configuracionesgenerales.php
defined('BASEPATH') OR exit('No direct script access allowed');

class ConfiguracionesGenerales extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('MconfiguracionesGenerales');
    }

    public function index() {
        $data['config'] = $this->MconfiguracionesGenerales->get_configuracion();
        $this->load->view('configuracionesgenerales/index', $data);
    }

    public function guardar() {
        $current = $this->MconfiguracionesGenerales->get_configuracion();
        $post_data = $this->input->post();

        $update_data = array_merge($current, $post_data);

        $boolean_fields = [
            'ver_delivery_caja', 'ver_barra_caja', 'ver_ingresos_egresos_cierre_caja', 'nombre_cliente', 'nombre_garzon',
            'usar_correo_electronico', 'informar_anulaciones_mail', 'sin_imagenes_de_recintos', 'cajero_agrega_cualquier_mesa',
            'validar_cortesia_venta_producto', 'happy_hour', 'happy_dia_lunes', 'happy_dia_martes', 'happy_dia_miercoles',
            'happy_dia_jueves', 'happy_dia_viernes', 'happy_dia_sabado', 'happy_dia_domingo', 'requiere_cliente_descuento',
            'mostrar', 'admin_anulacion_eliminacion', 'requerir_usuario_admin_cortesia', 'requerir_usuario_admin_credito',
            'requerir_usuario_admin_descuento', 'boton_apertura_caja_solo_admin', 'abrir_gaveta_solo_por_pago_efectivo',
            'omitir_mensaje_comprobante_no_valido_como_boleta', 'omitir_cuadratura_caja', 'reducir_texto_comprobante_pago',
            'imprimir_comprobante_venta', 'garzon_emite_precuenta', 'usar_impresora_local_precuenta', 'ocultar_cuenta_garzon',
            'omitir_productos_sin_precio_precuenta', 'mostrar_valor_propina_precuenta', 'preguntar_por_calculo_propina_en_pago',
            'mostrar_boton_propina_garzon', 'propina_en_venta_directa', 'permitircambiarppp', 'ordenar_carta',
            'impresion_grafica', 'imprimir_comprobante_anulacion', 'imprimir_valor_producto_cocina', 'imprimir_cierre_caja',
            'habilitar_facturacion_electronica', 'acepta', 'acierto', 'imprimir_copia_cedible', 'imprimir_pdf_dte',
            'mostrar_pdf_dte', 'copiar_pdf_dte', 'copiar_txt_dte_service', 'enviar_dte_sii', 'permitir_retiro_fondo_fijo',
            'agregar_comision_a_venta', 'pedir_numero_operacion_pago_electronico', 'pedir_anfitriona',
            'impedir_pago_delivery_sin_despacho', 'pedir_monto_vuelto_delivery', 'imprimir_datos_cliente_pedido_delivery',
            'incluir_mensaje_confirmacion_pedido_delivery', 'imprimir_hora_detalle_productos_pedido', 'pedir_comensales_en_mesa',
            'permitir_repetir_producto_menu', 'carta_en_mesa', 'lprecio_x_sector', 'validar_productos_cortesia',
            'desglozar_monto_neto_iva_total_en_reportes', 'desglozar_solo_monto_total_en_reportes', 'costo_por_ppp',
            'ver_creditos_a_clientes_cierre_caja', 'ver_cortesias_cierre_caja', 'ver_anulaciones_eliminaciones_cierre_caja',
            'ver_detalle_atencion_cierre_caja', 'mostrar_nombre_cliente_venta', 'mostrar_nombre_garzon_venta',
            'valorizar_ppp_inventario', 'detallar_nulos_cierre', 'usar_impresora_fiscal', 'obligar_cierre_gaveta',
            'imprimir_informe_z', 'calcular_valor_propina', 'correo_ssl', 'cierre_caja_manual', 'mostrar_recaudacion_cierre_no_efectivo',
            'permitir_solo_una_caja_abierta', 'bloqueo_total', 'imprimir_cierre_caja_impresora_termica', 'mostraridpedido',
            'propina_en_delivery', 'validar_el_pago_en_vps', 'requerir_admin_descuento', 'inventario_cierre_caja',
            'xsolutions', 'miutf8'
        ];

        foreach ($boolean_fields as $field) {
            if (isset($post_data[$field])) {
                $update_data[$field] = ($post_data[$field] == '1') ? 1 : 0;
            } else {
                $update_data[$field] = isset($current[$field]) ? $current[$field] : 0;
            }
        }

        // Handle numeric fields if needed, e.g., ensure they are integers
        $numeric_fields = ['por_propina', 'detalle_familia_alcierre', 'por_maximodescuento', 'por_iva', 'seg_bloqueo_seleccion_mesa', 'decimales_stock_insumo', 'copias_dte', 'timeout', 'resolucion_sii', 'anio_resolucion_sii', 'avisarcuandoquedenxfolios'];
        foreach ($numeric_fields as $field) {
            if (isset($post_data[$field])) {
                $update_data[$field] = (float) $post_data[$field];
            }
        }

        $result = $this->MconfiguracionesGenerales->update_configuracion($update_data);
        if ($result) {
            echo json_encode(array('status' => 'success'));
        } else {
            echo json_encode(array('status' => 'error'));
        }
    }
}
?>