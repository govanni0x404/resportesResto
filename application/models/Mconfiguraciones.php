<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mconfiguraciones extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    public function get_configuracion() {
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $query = $this->db->get('configuracion');
        $row = $query->row_array();
        if (!$row) {
            return [];
        }
        // PostgreSQL entrega los boolean como 't' / 'f' (texto) y en PHP 'f' cuenta como verdadero:
        // por eso las casillas apagadas se veían marcadas. Se convierten a true / false de verdad.
        foreach ($query->field_data() as $campo) {
            if ($campo->type === 'bool' && array_key_exists($campo->name, $row) && $row[$campo->name] !== null) {
                $row[$campo->name] = ($row[$campo->name] === 't' || $row[$campo->name] === true);
            }
        }
        return $row;
    }

    public function get_empresa() {
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $query = $this->db->get('empresa');
        $row = $query->row_array();
        
        // Si no existe ningún registro, crear uno vacío
        if (!$row) {
            $data = [
                'rut_empresa' => 0,
                'dv_empresa' => '',
                'razon_social' => '',
                'giro' => '',
                'codigo_actividad_economica' => '',
                'direccion_empresa' => '',
                'id_comuna' => 0,
                'codigo_sucursal' => '',
                'nombre_sucursal' => '',
                'direccion_sucursal' => '',
                'id_comuna_sucursal' => 0,
                'codigo_sii' => 0,
                'mail' => '',
                'nombre_fantasia' => '',
            ];
            $this->db->insert('empresa', $data);
            
            // Obtener el registro recién creado
            $query = $this->db->get('empresa');
            $row = $query->row_array();
        }
        
        return $row;
    }

    public function get_bodegas() {
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        // Obtener bodegas de la tabla bodega con alias para compatibilidad
        $this->db->select('id_bodega as id, nombre_bodega as nombre');
        $query = $this->db->get('bodega');
        return $query->result();
    }

    /**
     * Valores por defecto de la pestaña "Valores por defecto", iguales en todas las bases de clientes.
     * Se busca cada valor por su nombre (o código SII) porque los id cambian de una base a otra:
     *  - Documento de compra: FACTURA ELECTRONICA (33)     - Forma de pago compra, por defecto y vuelto: EFECTIVO
     *  - Crédito a clientes: CREDITO DE CONFIANZA            - Centro de costo del crédito: PAGO CREDITO ...
     *  - Bodegas compra y venta: BODEGA PRINCIPAL            - Cliente boleta: CLIENTE HABITUAL (RUT 66.666.666-6)
     *  - Lista de precios: LISTA PUBLICO                     - Documento de venta: BOLETA ELECTRONICA (39)
     *  - Documento cortesías: COMPROBANTE INTERNO            - Impuesto: IVA 19%
     *  - Ind. traslado venta: OPERACIÓN CONSTITUYE VENTA      - Empresa proveedora DTE: ACIERTO (si no hay ninguna)
     *  - Activadas: permitir retiro fondo fijo, inventario de insumos al cierre, repetir producto en el menú.
     *  - Desactivadas: enviar cierre de caja por correo e informar anulaciones y comisiones.
     *
     * Las listas se rellenan solo si están vacías (0 o sin valor): nunca se pisa lo que se eligió.
     * Las casillas se ajustan una sola vez por base (queda marcado en configuracion.defectos_web); después,
     * si alguien las apaga, quedan apagadas. Se llama al entrar a la web y al abrir Configuraciones.
     */
    public function aplicar_defectos() {
        $this->load->helper('tenant'); $db = get_tenant_db($this);
        if (!$db->table_exists('configuracion')) return;
        $debug = $db->db_debug;
        $db->db_debug = false;   // si algo falla (base distinta, solo lectura), no se corta la entrada a la web
        $nivel = error_reporting(error_reporting() & ~E_WARNING);   // y tampoco ensucia la pantalla (queda en el log)
        try {
            $fila = $db->query('SELECT * FROM configuracion ORDER BY id_configuracion LIMIT 1');
            $fila = $fila ? $fila->row_array() : null;
            if (!$fila) return;

            $primero = function ($tabla, $sql) use ($db) {
                if (!$db->table_exists($tabla)) return 0;
                $r = $db->query($sql);
                $f = $r ? $r->row_array() : null;
                return $f ? (int)reset($f) : 0;
            };
            $efectivo = array('fpago', "SELECT id_fpago FROM fpago WHERE nombre_fpago ILIKE '%EFECTIVO%' ORDER BY id_fpago LIMIT 1");
            $bodega = array('bodega', "SELECT id_bodega FROM bodega ORDER BY (nombre_bodega ILIKE '%PRINCIPAL%') DESC, id_bodega LIMIT 1");
            $reglas = array(
                'id_tdocumento_compra' => array('tdocumento', "SELECT id_tdocumento FROM tdocumento
                    WHERE COALESCE(cod_sii, 0) = 33 OR (nombre_tdocumento ILIKE 'FACTURA%' AND nombre_tdocumento NOT ILIKE '%EXENTA%')
                    ORDER BY (COALESCE(cod_sii, 0) = 33) DESC, id_tdocumento LIMIT 1"),
                'id_fpago_compra' => $efectivo,
                'id_fpago_creditodirecto' => array('fpago', "SELECT id_fpago FROM fpago
                    WHERE nombre_fpago ILIKE '%CR_DITO%' AND nombre_fpago NOT ILIKE '%TARJETA%'
                    ORDER BY (nombre_fpago ILIKE '%CONFIANZA%') DESC, id_fpago LIMIT 1"),
                'id_pagocreditodirecto' => array('ccosto', "SELECT id_ccosto FROM ccosto WHERE nombre_ccosto ILIKE '%CR_DITO%'
                    ORDER BY (nombre_ccosto ILIKE '%PAGO%') DESC, id_ccosto LIMIT 1"),
                'id_bodega_compra' => $bodega,
                'id_bodega_venta' => $bodega,
                'id_cliente_publico' => array('cliente', "SELECT id_cliente FROM cliente
                    WHERE nombre_cliente ILIKE '%HABITUAL%' OR rut::text IN ('6666666', '66666666')
                    ORDER BY (nombre_cliente ILIKE '%HABITUAL%') DESC, id_cliente LIMIT 1"),
                'id_lpreciopublico' => array('lprecio', "SELECT id_lprecio FROM lprecio ORDER BY (nombre_lprecio ILIKE '%P_BLICO%') DESC, id_lprecio LIMIT 1"),
                'id_docventa_defecto' => array('tdocumento', "SELECT id_tdocumento FROM tdocumento
                    WHERE COALESCE(cod_sii, 0) = 39 OR nombre_tdocumento ILIKE 'BOLETA%'
                    ORDER BY (COALESCE(cod_sii, 0) = 39) DESC, (nombre_tdocumento ILIKE '%EXENTA%'), id_tdocumento LIMIT 1"),
                'id_fpago_defecto' => $efectivo,
                'vueltofpago' => $efectivo,
                'id_tdocumento_ticket' => array('tdocumento', "SELECT id_tdocumento FROM tdocumento
                    WHERE nombre_tdocumento ILIKE '%COMPROBANTE%' AND nombre_tdocumento NOT ILIKE '%NOTA%'
                    ORDER BY (nombre_tdocumento ILIKE '%INTERNO%') DESC, id_tdocumento LIMIT 1"),
                'id_impuesto_iva' => array('impuesto', "SELECT id_impuesto FROM impuesto WHERE nombre_impuesto ILIKE 'IVA%' ORDER BY id_impuesto LIMIT 1"),
                'id_indicador_traslado_venta' => array('indicador_traslado', "SELECT id_indicador_traslado FROM indicador_traslado
                    WHERE nombre_indicador_traslado ILIKE 'OPERACI_N CONSTITUYE VENTA%' ORDER BY id_indicador_traslado LIMIT 1"),
            );

            $cambios = array();
            foreach ($reglas as $campo => $r) {
                if (!array_key_exists($campo, $fila) || (int)$fila[$campo] > 0) continue;
                $id = $primero($r[0], $r[1]);
                if ($id > 0) $cambios[$campo] = $id;
            }

            // Documentos electrónicos con un documento del tipo equivocado (ej. "Nota de Crédito Electrónica" = NOTA DE
            // CREDITO INTERNA): se cambia por el electrónico correcto. "SIN DEFINIR" (0) se respeta.
            foreach (array('id_tdocumento_factura' => '33, 34', 'id_tdocumento_nota_credito' => '61',
                           'id_tdocumento_boleta_electronica' => '39, 41') as $campo => $cods) {
                if (!array_key_exists($campo, $fila) || (int)$fila[$campo] <= 0 || !$db->table_exists('tdocumento')) continue;
                $ok = $db->query('SELECT 1 FROM tdocumento WHERE id_tdocumento = ? AND COALESCE(cod_sii, 0) IN (' . $cods . ')', array((int)$fila[$campo]));
                if ($ok && $ok->num_rows() > 0) continue;
                $id = $primero('tdocumento', 'SELECT id_tdocumento FROM tdocumento WHERE COALESCE(cod_sii, 0) IN (' . $cods . ')
                    ORDER BY COALESCE(cod_sii, 0), id_tdocumento LIMIT 1');
                if ($id > 0) $cambios[$campo] = $id;
            }

            // Empresa proveedora DTE: ACIERTO si no hay ninguna elegida
            if (array_key_exists('acepta', $fila) && array_key_exists('acierto', $fila)) {
                $si = function ($v) { return $v === 't' || $v === true; };
                if (!$si($fila['acepta']) && !$si($fila['acierto'])) $cambios['acierto'] = true;
            }

            // Casillas: una sola vez por base
            if (!array_key_exists('defectos_web', $fila)) {
                $db->query('ALTER TABLE configuracion ADD COLUMN IF NOT EXISTS defectos_web boolean NOT NULL DEFAULT false');
                $hayMarca = (bool)$db->query("SELECT 1 FROM information_schema.columns WHERE table_schema = 'public'
                    AND table_name = 'configuracion' AND column_name = 'defectos_web'")->row();
                $marcado = false;
            } else {
                $hayMarca = true;
                $marcado = $fila['defectos_web'] === 't' || $fila['defectos_web'] === true;
            }
            if ($hayMarca && !$marcado) {
                foreach (array('permitir_retiro_fondo_fijo', 'inventario_cierre_caja', 'permitir_repetir_producto_menu') as $c) {
                    if (array_key_exists($c, $fila)) $cambios[$c] = true;
                }
                // Informes por correo: apagados hasta que alguien los active y escriba los correos
                foreach (array('usar_correo_electronico', 'informar_anulaciones_mail') as $c) {
                    if (array_key_exists($c, $fila)) $cambios[$c] = false;
                }
                $cambios['defectos_web'] = true;
            }

            if ($cambios) {
                $db->where('id_configuracion', $fila['id_configuracion'])->update('configuracion', $cambios);
            }
        } catch (Throwable $e) {
            log_message('error', 'Mconfiguraciones::aplicar_defectos: ' . $e->getMessage());
        } finally {
            $db->db_debug = $debug;
            error_reporting($nivel);
        }
    }

    /**
     * Opciones reales de cada lista de Configuraciones (antes estaban escritas a mano con valores de ejemplo 0/1 y al
     * grabar se guardaban esos números, por ejemplo la forma de pago de crédito quedaba en 0 o en EFECTIVO).
     * Devuelve tipo => array(id => nombre). Si una tabla no existe en este cliente, su lista queda vacía.
     */
    public function get_opciones($config = array()) {
        $this->load->helper('tenant'); $db = get_tenant_db($this);
        $lista = function ($sql) use ($db) {
            $res = array();
            if (!$db->table_exists(preg_replace('/^.*\bFROM\s+(\w+).*$/is', '$1', $sql))) return $res;
            $r = $db->query($sql);
            if ($r) foreach ($r->result() as $f) $res[(int)$f->id] = (string)$f->nombre;
            return $res;
        };
        $o = array(
            'fpago' => $lista('SELECT id_fpago AS id, nombre_fpago AS nombre FROM fpago ORDER BY nombre_fpago'),
            'tdocumento' => $lista('SELECT id_tdocumento AS id, nombre_tdocumento AS nombre FROM tdocumento ORDER BY nombre_tdocumento'),
            // Facturación electrónica: cada lista solo con documentos de su tipo SII
            'tdoc_factura' => $lista('SELECT id_tdocumento AS id, nombre_tdocumento AS nombre FROM tdocumento WHERE COALESCE(cod_sii, 0) IN (33, 34) ORDER BY nombre_tdocumento'),
            'tdoc_nc' => $lista('SELECT id_tdocumento AS id, nombre_tdocumento AS nombre FROM tdocumento WHERE COALESCE(cod_sii, 0) = 61 ORDER BY nombre_tdocumento'),
            'tdoc_boleta' => $lista('SELECT id_tdocumento AS id, nombre_tdocumento AS nombre FROM tdocumento WHERE COALESCE(cod_sii, 0) IN (39, 41) ORDER BY nombre_tdocumento'),
            'bodega' => $lista('SELECT id_bodega AS id, nombre_bodega AS nombre FROM bodega ORDER BY nombre_bodega'),
            'lprecio' => $lista('SELECT id_lprecio AS id, nombre_lprecio AS nombre FROM lprecio ORDER BY nombre_lprecio'),
            'impuesto' => $lista('SELECT id_impuesto AS id, nombre_impuesto AS nombre FROM impuesto ORDER BY nombre_impuesto'),
            'ccosto' => $lista('SELECT id_ccosto AS id, nombre_ccosto AS nombre FROM ccosto ORDER BY nombre_ccosto'),
            'dimpresion' => array(),
            'indicador_traslado' => $lista('SELECT id_indicador_traslado AS id, nombre_indicador_traslado AS nombre FROM indicador_traslado ORDER BY id_indicador_traslado'),
            // Los clientes pueden ser miles: los primeros 1000 por nombre más el que está elegido
            'cliente' => $lista('SELECT id_cliente AS id, nombre_cliente AS nombre FROM cliente ORDER BY nombre_cliente LIMIT 1000'),
        );
        // Destinos de impresión por nombre (Impresora para cierre de caja guarda el nombre)
        if ($db->table_exists('dimpresion')) {
            foreach ($db->query("SELECT DISTINCT trim(nombre_dimpresion) AS n FROM dimpresion WHERE trim(COALESCE(nombre_dimpresion, '')) <> ''
                AND upper(trim(nombre_dimpresion)) <> 'SIN IMPRESION' ORDER BY 1")->result() as $f) $o['dimpresion'][] = (string)$f->n;
        }
        $elegido = isset($config['id_cliente_publico']) ? (int)$config['id_cliente_publico'] : 0;
        if ($elegido && !isset($o['cliente'][$elegido])) {
            $f = $db->query('SELECT nombre_cliente FROM cliente WHERE id_cliente = ?', array($elegido))->row();
            if ($f) $o['cliente'] = array($elegido => (string)$f->nombre_cliente) + $o['cliente'];
        }
        return $o;
    }

    public function update_configuracion($data) {
        if (empty($data)) {
            return false;
        }
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        // Cada cliente tiene su versión de la tabla: se guardan solo las columnas que existen
        // (antes una sola columna faltante hacía fallar todo el guardado, terminal y RUT incluidos)
        $data = array_intersect_key($data, array_flip($this->db->list_fields('configuracion')));
        if (empty($data)) {
            return false;
        }
        $existente = $this->db->select('id_configuracion')->get('configuracion')->row();
        if (!$existente) {
            return false;
        }
        $this->db->where('id_configuracion', $existente->id_configuracion);
        return $this->db->update('configuracion', $data);
    }

    /**
     * Columnas de acceso a Cloud Apps en configuracion. terminal_dte es nueva (la caja web
     * la necesita); usuario/clave ya existen en las bases del POS, pero no en todas.
     */
    public function asegurar_columnas_dte() {
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $existentes = array();
        foreach ($this->db->query("SELECT column_name FROM information_schema.columns
            WHERE table_schema = 'public' AND table_name = 'configuracion'
            AND column_name IN ('usuario_dte', 'clave_usuario_dte', 'terminal_dte', 'modo_caja_web')")->result() as $c) {
            $existentes[] = $c->column_name;
        }
        // modo_caja_web: 'directa', 'mesas' o 'ambos' (Caja web; el POS de escritorio no la usa)
        $tipos = array('usuario_dte' => 'varchar(100)', 'clave_usuario_dte' => 'varchar(100)', 'terminal_dte' => 'varchar(60)', 'modo_caja_web' => 'varchar(10)');
        foreach ($tipos as $col => $tipo) {
            if (!in_array($col, $existentes)) {
                $this->db->query("ALTER TABLE configuracion ADD COLUMN IF NOT EXISTS $col $tipo");
            }
        }
    }

    /** Tipos de documento electrónicos (con código SII) para consultar sus folios. */
    public function get_documentos_dte() {
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        return $this->db->query('SELECT id_tdocumento, nombre_tdocumento, cod_sii FROM tdocumento
            WHERE cod_sii IN (33, 34, 39, 41, 43, 46, 52, 56, 61, 110, 111, 112)
            ORDER BY cod_sii, id_tdocumento')->result();
    }

    /** Cómo se desglosan los montos en los PDF de Dashboard y Consolidado (si la base no tiene las columnas: neto/IVA/total). */
    public function obtenerConfiguracionesRestoWeb() {
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $cols = $this->db->list_fields('configuracion');
        $sel = array();
        foreach (array('desglozar_monto_neto_iva_total_en_reportes' => 't', 'desglozar_solo_monto_total_en_reportes' => 'f') as $c => $defecto) {
            $sel[] = in_array($c, $cols) ? "CASE WHEN $c IS NULL THEN '$defecto' WHEN $c THEN 't' ELSE 'f' END AS $c" : "'$defecto' AS $c";
        }
        $r = $this->db->query('SELECT ' . implode(', ', $sel) . ' FROM configuracion LIMIT 1')->result();
        return $r ? $r : array((object)array('desglozar_monto_neto_iva_total_en_reportes' => 't', 'desglozar_solo_monto_total_en_reportes' => 'f'));
    }

    public function update_empresa($data) {
        if (empty($data)) {
            return false;
        }
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $data = array_intersect_key($data, array_flip($this->db->list_fields('empresa')));
        if (empty($data)) {
            return false;
        }
        $existente = $this->db->select('id_empresa')->get('empresa')->row();
        if (!$existente) {
            return $this->db->insert('empresa', $data);
        }
        $this->db->where('id_empresa', $existente->id_empresa);
        return $this->db->update('empresa', $data);
    }
}
?>