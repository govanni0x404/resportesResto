<?php
// application/controllers/Configuracionesgenerales.php
defined('BASEPATH') OR exit('No direct script access allowed');

class Configuraciones extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Mconfiguraciones');
    }

    public function index() {
        $this->load->helper('tenant');

        $tenantDb = get_tenant_db($this);
        $data['conexion_actual'] = $this->session->userdata('nombre_conexion');
        $data['base_datos_actual'] = isset($tenantDb->database) ? $tenantDb->database : '';

        $data['config'] = $this->Mconfiguraciones->get_configuracion();
        $data['empresa'] = $this->Mconfiguraciones->get_empresa();
        $data['bodegas'] = $this->Mconfiguraciones->get_bodegas();
        $this->load->view('menuTop.php', $data);
        $this->load->view('configuraciones/configuraciones', $data);
        $this->load->view('menuFooter.php');
        $this->load->view('configuraciones/js', $data);
    }

    public function obtenerConfiguracionesRestoWeb() {
        $this->load->helper('tenant'); 
        $this->db = get_tenant_db($this);
        $config = $this->Mconfiguraciones->get_configuracion();
        echo json_encode([$config]);
    }

    public function guardar() {
        $post = $this->input->post();
        $update_config = [];
        $update_empresa = [];

        // ==================== CONFIGURACION ====================
        // BOOLEANOS - Todos los checkboxes (nombres exactos según la BD)
        $boolean_fields = [
            'ver_delivery_caja',
            'ver_barra_caja',
            'mostraridpedido',
            'mostrar_nombre_cliente_venta',
            'mostrar_nombre_garzon_venta',
            'garzon_emite_precuenta',
            'usar_impresora_local_precuenta',
            'ocultar_cuenta_garzon',
            'omitir_productos_sin_precio_precuenta',
            'calcular_valor_propina',
            'preguntar_calculo_propina_en_pago',
            'mostrar_boton_propina_garzon',
            'propina_en_delivery',
            'propina_en_venta_directa',
            'permitircambiarppp',
            'ordenar_carta',
            'imprimir_comprobante_anulacion',
            'imprimir_valor_producto_cocina',
            'usar_correo_electronico',
            'informar_anulaciones_mail',
            'ver_ingresos_egresos_cierre_caja',
            'ver_creditos_a_clientes_cierre_caja',
            'ver_cortesias_cierre_caja',
            'ver_anulaciones_eliminaciones_cierre_caja',
            'ver_detalle_atencion_cierre_caja',
            'mostrar_recaudacion_cierre_no_efectivo',
            'cierre_caja_manual',
            'imprimir_cierre_caja_impresora_termica',
            'requiere_cliente_descuento',
            'admin_anulacion_eliminacion',
            'requerir_usuario_admin_credito',
            'requerir_usuario_admin_cortesia',
            'requerir_usuario_admin_descuento',
            'boton_apertura_caja_solo_admin',
            'permitir_retiro_fondo_fijo',
            'lprecio_x_sector',
            'agregar_comision_a_venta',
            'pedir_anfitriona',
            'inventario_cierre_caja',
            'permitir_repetir_producto_menu',
            'pedir_comensales_en_mesa',
            'imprimir_hora_detalle_productos_pedido',
            'habilitar_facturacion_electronica',
            'imprimir_pdf_dte',
            'imprimir_copia_cedible',
            'enviar_dte_sii',
            'acepta',
            'acierto'
        ];

        foreach ($boolean_fields as $f) {
            $update_config[$f] = isset($post[$f]) ? true : false;
        }

        // NUMÉRICOS (nombres exactos según la BD)
        $numeric_fields = [
            'por_maximodescuento',
            'seg_bloqueo_seleccion_mesa',
            'copias_dte',
            'timeout',
            'anio_resolucion_sii',
            'resolucion_sii',
            'id_bodega_compra',
            'id_bodega_venta',
            'id_bodega_merma_inventario',
            'id_cliente_publico',
            'id_lpreciopublico',
            'id_docventa_defecto',
            'id_fpago_defecto',
            'vueltofpago',
            'id_tdocumento_comprobante_interno',
            'id_impuesto_iva',
            'id_tdocumento_factura',
            'id_tdocumento_nota_credito',
            'id_tdocumento_guia_despacho',
            'id_indicador_traslado_venta',
            'id_tdocumento_boleta_electronica',
            'id_comuna_sii',
            'id_tdocumento_compra',
            'id_fpago_compra',
            'id_fpago_creditodirecto',
            'id_pagocreditodirecto',
            'id_mesa_venta_directa'
        ];

        foreach ($numeric_fields as $f) {
            if (isset($post[$f]) && $post[$f] !== '') {
                $update_config[$f] = intval($post[$f]);
            }
        }

        // TEXTO / OTROS (nombres exactos según la BD)
        $text_fields = [
            'correo_electronico',
            'nombre_impresora_cierre',
            'ruta_dte_pdf',
            'formato_dte',
            'usuario_dte',
            'clave_usuario_dte',
            'ruta_api_get_pdf',
            'ruta_api_get_qr',
            'url_consulta_folio_dte'
        ];

        foreach ($text_fields as $f) {
            if (isset($post[$f])) {
                $update_config[$f] = $post[$f];
            }
        }

        // Campo especial para clave admin
        if (isset($post['txtclaveAdmin'])) {
            $update_config['pass_admin'] = $post['txtclaveAdmin'];
        }

        // RADIO proveedor DTE (acepta/acierto)
        if (isset($post['proveedor_dte'])) {
            $update_config['acepta'] = ($post['proveedor_dte'] == '1') ? true : false;
            $update_config['acierto'] = ($post['proveedor_dte'] == '2') ? true : false;
        }

        // ==================== EMPRESA ====================
        $empresa_fields = [
            'rut_empresa',
            'dv_empresa',
            'razon_social',
            'nombre_fantasia',
            'codigo_actividad_economica',
            'giro',
            'direccion_empresa',
            'id_comuna',
            'mail',
            'telefono',
            'codigo_sii',
            'codigo_sucursal',
            'nombre_sucursal',
            'direccion_sucursal',
            'id_comuna_sucursal'
        ];

        foreach ($empresa_fields as $f) {
            if (isset($post[$f])) {
                $update_empresa[$f] = $post[$f];
            }
        }

        // Guardar ambas tablas
        $ok_config = $this->Mconfiguraciones->update_configuracion($update_config);
        $ok_empresa = $this->Mconfiguraciones->update_empresa($update_empresa);

        header('Content-Type: application/json');
        echo json_encode(['status' => ($ok_config || $ok_empresa) ? 'success' : 'error']);
    }
}
