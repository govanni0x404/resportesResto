<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261121120000
 * Timestamp: 2026-11-21 12:00:00
 *
 * v1.27.0 (pedido del usuario): stock mínimo de cada insumo, para el aviso rojo de la Caja "Quedan pocos".
 *
 * - insumo.stock_minimo_web  double precision, 0 = sin aviso. Se escribe en la ficha del insumo (Insumos), en la unidad
 *   de inventario. Cuando la suma del stock del insumo en todas las bodegas llega a ese número o baja de él, la Caja
 *   muestra arriba una franja roja con los insumos que quedan pocos (Caja::stockBajo). No tapa nada ni impide vender.
 *
 * Nombre con "_web": la tabla la comparte el POS de escritorio, que no la usa.
 * Idempotente (IF NOT EXISTS). Funciona en PostgreSQL 10 (VPS). El código no asume que corrió (revisa la columna).
 */
class Migration_stock_minimo_insumo_web extends CI_Migration
{
    public function up()
    {
        if (!$this->existe('insumo', 'id_insumo')) {
            throw new RuntimeException(__METHOD__ . ': falta la tabla insumo. Se reintenta en la próxima ejecución.');
        }
        $this->ejecutar("ALTER TABLE public.insumo ADD COLUMN IF NOT EXISTS stock_minimo_web double precision DEFAULT 0",
            'ADD COLUMN insumo.stock_minimo_web');
        // Regla 7: comprobar en el catálogo antes de darse por aplicada
        if (!$this->existe('insumo', 'stock_minimo_web')) {
            throw new RuntimeException(__METHOD__ . ': falta insumo.stock_minimo_web después de crearla. ' .
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
