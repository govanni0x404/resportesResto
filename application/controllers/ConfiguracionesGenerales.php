<?php
// application/controllers/Configuracionesgenerales.php
defined('BASEPATH') OR exit('No direct script access allowed');

class ConfiguracionesGenerales extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Mconfiguracionesgenerales');
    }

    public function index() {
        $data['config'] = $this->Mconfiguracionesgenerales->get_configuracion();
        $this->load->view('menuTop.php', $data);
        $this->load->view('configuracionesgenerales/configuracionesgenerales', $data);
        $this->load->view('menuFooter.php');
        $this->load->view('configuracionesgenerales/js', $data);
    }

    public function guardar() {
        $post = $this->input->post();
        $update = [];

        // BOOLEANOS
        $boolean_fields = [
            'ver_delivery_caja',
            'ver_barra_caja',
            'mostraridpedido',
            'mostrar_nombre_cliente_venta',
            'mostrar_nombre_garzon_venta',
            'usar_correo_electronico',
            'informar_anulaciones_mail',
            'garzon_emite_precuenta',
            'usar_impresora_local_precuenta',
            'ocultar_cuenta_garzon',
            'omitir_productos_sin_precio_precuenta',
            'mostrar_propina_precuenta',
            'preguntar_calculo_propina_en_pago',
            'mostrar_boton_propina_garzon',
            'propina_en_delivery',
            'propina_en_venta_directa',
            'ordenar_carta',
            'imprimir_comprobante_anulacion',
            'imprimir_valor_producto_cocina',
            'habilitar_facturacion_electronica',
            'imprimir_pdf_dte',
            'imprimir_copia_cedible',
            'enviar_dte_sii'
        ];

        foreach ($boolean_fields as $f) {
            $update[$f] = isset($post[$f]) ? 1 : 0;
        }

        // NUMÉRICOS
        $numeric_fields = [
            'por_maximodescuento',
            'seg_bloqueo_seleccion_mesa',
            'seg_envio_pedido_mesa',
            'copias_dte',
            'timeout_dte'
        ];

        foreach ($numeric_fields as $f) {
            if (isset($post[$f])) {
                $update[$f] = $post[$f];
            }
        }

        // TEXTO / OTROS
        $text_fields = [
            'correo_electronico',
            'impresora_local_precuenta',
            'impresion_grafica_precuenta',
            'nombre_impresora_cierre',
            'ruta_dte',
            'ruta_dte_pdf',
            'formato_pdf_dte'
        ];

        foreach ($text_fields as $f) {
            if (isset($post[$f])) {
                $update[$f] = $post[$f];
            }
        }

        // RADIO proveedor DTE
        if (isset($post['proveedor_dte'])) {
            $update['proveedor_dte'] = $post['proveedor_dte'];
        }

        $ok = $this->Mconfiguracionesgenerales->update_configuracion($update);

        echo json_encode(['status' => $ok ? 'success' : 'error']);
    }
}
