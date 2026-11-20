<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261120130000
 * Timestamp: 2026-11-20 13:00:00
 *
 * Mensaje general de la mesa una sola vez (pedido del usuario, 20-11-2026, v1.22.1): si la mesa ya tiene un mensaje
 * general impreso y después se agrega otro producto, la nueva comanda NO repite el mensaje; va solo con la nota del
 * producto o su agregado. cotizacion.mensaje_impreso_web guarda el mensaje que ya salió en una comanda (Mcaja::
 * mensajeParaComanda); si el mensaje se cambia, el próximo envío lleva el nuevo.
 *
 * Nombre con "_web": la tabla la comparte el POS de escritorio, que no la usa. Sin DEFAULT: no reescribe la tabla.
 * Idempotente (IF NOT EXISTS). Funciona en PostgreSQL 10 (VPS). El código no asume que corrió.
 */
class Migration_mensaje_mesa_impreso extends CI_Migration
{
    public function up()
    {
        if (!$this->existe('cotizacion', 'id_cotizacion')) {
            // Sin tabla cotizacion el cliente no tiene mesas: no hay nada que hacer (return legítimo)
            return;
        }
        $this->ejecutar('ALTER TABLE public.cotizacion ADD COLUMN IF NOT EXISTS mensaje_impreso_web character varying(200)',
            'ADD COLUMN cotizacion.mensaje_impreso_web');

        // Regla 7: comprobar en el catálogo antes de darse por aplicada
        if (!$this->existe('cotizacion', 'mensaje_impreso_web')) {
            throw new RuntimeException(__METHOD__ . ': falta cotizacion.mensaje_impreso_web después de crearla. ' .
                'No se marca como aplicada: se reintenta en la próxima ejecución.');
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

    /** Regla 7: con db_debug en false query() no lanza, devuelve FALSE: se revisa y se lanza. */
    private function ejecutar($sql, $etiqueta)
    {
        $tag = __CLASS__;
        $resultado = null;
        $errorSql  = '';
        try {
            $resultado = $this->db->query($sql);
        } catch (Throwable $e) {
            $resultado = false;
            $errorSql  = $e->getMessage();
        }
        if ($resultado === false) {
            if ($errorSql === '' && method_exists($this->db, 'error')) {
                $err = $this->db->error();
                $errorSql = is_array($err) && !empty($err['message']) ? $err['message'] : 'sin detalle';
            }
            throw new RuntimeException("$tag: $etiqueta no se aplicó: $errorSql");
        }
        return $resultado;
    }

    public function down()
    {
        // No destructivo a propósito. Si hiciera falta, va en una migración nueva.
        log_message('info', __METHOD__ . ': sin cambios (down no destructivo).');
    }
}
