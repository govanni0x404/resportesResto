<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261114100000
 * Timestamp: 2026-11-14 10:00:00
 *
 * caja_venta_token: un registro por cobro de la Caja web (venta directa o mesa). La pantalla manda un token al
 * confirmar el pago y lo repite si reintenta; el servidor lo inserta en la MISMA transacción de la venta, así que
 * un doble clic, un reintento por red lenta o dos pestañas no crean dos ventas (QA 14-11-2026: el mismo cuerpo
 * enviado 3 veces dejaba 3 ventas).
 *
 * - Tabla nueva, sin tocar tablas del POS. Idempotente (IF NOT EXISTS).
 * - El código no asume que corrió: sin la tabla la venta se registra como antes (Mcaja::registrarVenta).
 */
class Migration_add_caja_venta_token extends CI_Migration
{
    public function up()
    {
        $this->ejecutar("CREATE TABLE IF NOT EXISTS public.caja_venta_token (
                token character varying(64) NOT NULL,
                id_venta integer,
                id_caja integer,
                id_usuario integer,
                fecha timestamp with time zone DEFAULT now(),
                CONSTRAINT caja_venta_token_pkey PRIMARY KEY (token)
            )", 'CREATE TABLE caja_venta_token');
        $this->ejecutar('CREATE INDEX IF NOT EXISTS caja_venta_token_fecha_idx ON public.caja_venta_token (fecha)',
            'CREATE INDEX caja_venta_token_fecha_idx');
        $this->ejecutar("COMMENT ON TABLE public.caja_venta_token IS 'Caja web: token de cada cobro para no registrar dos veces la misma venta'",
            'COMMENT caja_venta_token');

        // Regla 7: comprobar en el catálogo antes de darse por aplicada.
        foreach (array('token', 'id_venta', 'id_caja', 'id_usuario', 'fecha') as $c) {
            if (!$this->existe('caja_venta_token', $c)) {
                throw new RuntimeException(__METHOD__ . ": falta caja_venta_token.$c después de crearla. " .
                    'No se marca como aplicada: se reintenta en la próxima ejecución.');
            }
        }
    }

    private function existe($tabla, $columna)
    {
        $q = $this->db->query(
            "SELECT count(*) AS n FROM information_schema.columns
              WHERE table_schema = 'public' AND table_name = ? AND column_name = ?",
            array($tabla, $columna));
        $fila = ($q && method_exists($q, 'row')) ? $q->row() : null;
        return $fila && (int) $fila->n > 0;
    }

    /** Regla 7: con db_debug en false query() devuelve FALSE; se lanza para no grabar la versión. */
    private function ejecutar($sql, $etiqueta)
    {
        $resultado = null;
        $errorSql = '';
        try {
            $resultado = $this->db->query($sql);
        } catch (Throwable $e) {
            $resultado = false;
            $errorSql = $e->getMessage();
        }
        if ($resultado === false) {
            if ($errorSql === '' && method_exists($this->db, 'error')) {
                $err = $this->db->error();
                $errorSql = is_array($err) && !empty($err['message']) ? $err['message'] : 'sin detalle';
            }
            throw new RuntimeException(__CLASS__ . ": $etiqueta no se aplicó: $errorSql");
        }
        return $resultado;
    }

    public function down()
    {
        // No destructivo: la tabla solo evita duplicados; si sobra no molesta.
        log_message('info', __METHOD__ . ': sin cambios (down no destructivo).');
    }
}
