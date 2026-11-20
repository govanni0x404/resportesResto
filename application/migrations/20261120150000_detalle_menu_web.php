<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261120150000
 * Timestamp: 2026-11-20 15:00:00
 *
 * Menús en la Caja web (pedido del usuario, 20-11-2026, v1.24.0). Un menú (producto con id_menu) se vende con lo que
 * el cliente elige de cada categoría: la línea del menú (es_menu) y una línea por cada producto elegido
 * (es_detalle_menu). detalle_cotizacion.id_detalle_menu_web dice de qué línea de menú es cada producto elegido, para
 * que al mover, cobrar una parte o anular, el menú y lo elegido nunca se separen.
 *
 * Nombre con "_web": la tabla la comparte el POS de escritorio, que no la usa. Sin DEFAULT: no reescribe la tabla.
 * Idempotente (IF NOT EXISTS). Funciona en PostgreSQL 10 (VPS). El código no asume que corrió (Mcaja::lineasDeCotizacion
 * usa el orden de las líneas si falta).
 */
class Migration_detalle_menu_web extends CI_Migration
{
    public function up()
    {
        if (!$this->existe('detalle_cotizacion', 'id_detalle_cotizacion')) {
            // Sin pedidos no hay mesas ni menús en mesas: nada que hacer (return legítimo)
            return;
        }
        $this->ejecutar('ALTER TABLE public.detalle_cotizacion ADD COLUMN IF NOT EXISTS id_detalle_menu_web integer',
            'ADD COLUMN detalle_cotizacion.id_detalle_menu_web');

        // Regla 7: comprobar en el catálogo antes de darse por aplicada
        if (!$this->existe('detalle_cotizacion', 'id_detalle_menu_web')) {
            throw new RuntimeException(__METHOD__ . ': falta detalle_cotizacion.id_detalle_menu_web después de crearla. ' .
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
