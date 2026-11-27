<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261124100000
 * Timestamp: 2026-11-24 10:00:00
 *
 * v1.32.1 (pedido del usuario): máximo de bases por roll en las promos de sushi (como el de envoltorios y rellenos).
 *
 * - promo_sushi_web.max_bases_web  integer, 1 = una base por roll (como antes).
 *
 * Idempotente (IF NOT EXISTS). Funciona en PostgreSQL 10 (VPS). El código no asume que corrió (revisa la columna).
 */
class Migration_promo_sushi_bases_web extends CI_Migration
{
    public function up()
    {
        if (!$this->existe('promo_sushi_web', 'id_producto')) {
            throw new RuntimeException(__METHOD__ . ': falta la tabla promo_sushi_web. Se reintenta en la próxima ejecución.');
        }
        $this->ejecutar("ALTER TABLE public.promo_sushi_web ADD COLUMN IF NOT EXISTS max_bases_web integer NOT NULL DEFAULT 1",
            'ADD COLUMN promo_sushi_web.max_bases_web');
        // Regla 7: comprobar en el catálogo antes de darse por aplicada
        if (!$this->existe('promo_sushi_web', 'max_bases_web')) {
            throw new RuntimeException(__METHOD__ . ': falta promo_sushi_web.max_bases_web después de crearla. ' .
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
