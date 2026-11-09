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

        $this->Mconfiguraciones->asegurar_columnas_dte();
        $this->Mconfiguraciones->aplicar_defectos();
        $data['config'] = $this->Mconfiguraciones->get_configuracion();
        $data['documentos_dte'] = $this->Mconfiguraciones->get_documentos_dte();
        $data['empresa'] = $this->Mconfiguraciones->get_empresa();
        $data['bodegas'] = $this->Mconfiguraciones->get_bodegas();
        $data['opciones'] = $this->Mconfiguraciones->get_opciones($data['config']);
        $this->load->view('menuTop.php', $data);
        $this->load->view('configuraciones/configuraciones', $data);
        $this->load->view('menuFooter.php');
        $this->load->view('configuraciones/js', $data);
    }

    public function obtenerConfiguracionesRestoWeb() {
        $this->load->helper('tenant'); 
        $this->db = get_tenant_db($this);
        // Solo lo que usan los reportes (cómo mostrar neto/IVA); nunca claves ni datos del SII
        echo json_encode($this->Mconfiguraciones->obtenerConfiguracionesRestoWeb());
    }

    /**
     * Consulta a Cloud Apps los folios disponibles de un tipo de documento.
     * Usa los datos del formulario (aunque no estén guardados) para poder probarlos antes.
     */
    public function obtenerFolios() {
        header('Content-Type: application/json');
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            http_response_code(401);
            echo json_encode(array('ok' => false, 'mensaje' => 'Sesión expirada'));
            return;
        }
        $this->load->model('Mdte');
        // Siempre con los datos de Acceso facturación de la pantalla (RUT válido, contraseña y terminal obligatorios)
        echo json_encode($this->Mdte->consultarFolios(
            trim((string)$this->input->post('usuario')),
            (string)$this->input->post('clave'),
            trim((string)$this->input->post('terminal')),
            (int)$this->input->post('tipo')
        ));
    }

    public function guardar() {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0 || (int)$this->session->userdata('id_rol') !== 1) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['status' => 'error', 'mensaje' => 'Solo un administrador puede cambiar las configuraciones']);
            return;
        }
        $post = $this->input->post();
        $update_config = [];
        $update_empresa = [];

        // ==================== CONFIGURACION ====================
        // BOOLEANOS - Todos los checkboxes (nombres exactos según la BD).
        // Las opciones que la web no usa (Ver en caja, Precuentas, botón ver propina, propina en delivery, valor en comandas,
        // precios por sector, comisión, anfitriona, comensales, hora en productos, preguntar propina en pago,
        // cambio del costo de insumo, ordenar carta)
        // ya no están en la pantalla ni aquí, para que al grabar no se apaguen: quedan como estén en la base.
        $boolean_fields = [
            'calcular_valor_propina',
            'propina_en_venta_directa',
            'imprimir_comprobante_anulacion',
            'usar_correo_electronico',
            'informar_anulaciones_mail',
            'ver_ingresos_egresos_cierre_caja',
            'ver_creditos_a_clientes_cierre_caja',
            'ver_cortesias_cierre_caja',
            'ver_anulaciones_eliminaciones_cierre_caja',
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
            'inventario_cierre_caja',
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
            'id_tdocumento_ticket',          // documento para cortesías / comprobante interno (lo usa la Caja)
            'id_impuesto_iva',
            'id_tdocumento_factura',
            'id_tdocumento_nota_credito',
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
            'terminal_dte',
            'ruta_api_get_pdf',
            'ruta_api_get_qr',
            'url_consulta_folio_dte'
        ];

        foreach ($text_fields as $f) {
            if (isset($post[$f])) {
                $update_config[$f] = $post[$f];
            }
        }
        // Usuario de Cloud Apps: RUT sin puntos (con puntos Cloud Apps responde "clave inválida")
        if (isset($update_config['usuario_dte']) && trim($update_config['usuario_dte']) !== '') {
            $this->load->model('Mdte');
            $update_config['usuario_dte'] = Mdte::rutUsuario($update_config['usuario_dte']);
        }
        if (isset($post['modo_caja_web']) && in_array($post['modo_caja_web'], array('directa', 'mesas', 'ambos'), true)) {
            $update_config['modo_caja_web'] = $post['modo_caja_web'];
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

        foreach (array('usuario_dte', 'clave_usuario_dte', 'terminal_dte') as $f) {
            if (isset($update_config[$f])) $update_config[$f] = trim($update_config[$f]);
        }
        $this->Mconfiguraciones->asegurar_columnas_dte();

        // Guardar ambas tablas
        $ok_config = $this->Mconfiguraciones->update_configuracion($update_config);
        $ok_empresa = $this->Mconfiguraciones->update_empresa($update_empresa);

        header('Content-Type: application/json');
        // La configuración debe quedar guardada; empresa puede venir sin cambios
        $ok = $ok_config && ($ok_empresa || empty($update_empresa));
        echo json_encode(['status' => $ok ? 'success' : 'error']);
    }

    /** Correo de prueba (botón "Probar envío") a los correos escritos, con el servidor de correo del sistema. */
    public function probarCorreo() {
        header('Content-Type: application/json; charset=utf-8');
        if ((int)$this->session->userdata('id_rol') !== 1 || $this->input->method() !== 'post') {
            http_response_code(403);
            echo json_encode(array('ok' => false, 'detalle' => 'Solo un administrador puede probar el correo'));
            return;
        }
        $this->load->helper('tenant');
        $this->load->library('Correo_resto');
        $correos = (string)$this->input->post('correos');
        if (!Correo_resto::destinatarios($correos)) {
            echo json_encode(array('ok' => false, 'detalle' => 'Ningún correo válido. Sepáralos por coma, ej: uno@correo.cl, otro@correo.cl'));
            return;
        }
        list($ok, $detalle) = $this->correo_resto->enviar($this, get_tenant_db($this), 'Prueba de correo - Resto Web',
            '<p>Este es un correo de prueba de <b>Resto Web</b>.</p><p>Si lo recibiste, los informes por correo (cierre de caja y anulaciones) llegarán a esta dirección.</p>', $correos);
        echo json_encode(array('ok' => $ok, 'detalle' => $detalle), JSON_UNESCAPED_UNICODE);
    }
}
