<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261126100000
 * Timestamp: 2026-11-26 10:00:00
 *
 * v1.39.5 (pedido del usuario): promos normales de dos tipos.
 *  - promo_web.elegir: NULL (o 0) = promo fija (siempre los mismos productos); N > 0 = el cliente elige N productos de la
 *    lista de la promo en la Caja (como las promos de sushi). Sin valor por defecto para no reescribir la tabla en PG10.
 *
 * Idempotente. Funciona en PostgreSQL 10 (VPS). El código no asume que corrió (revisa la columna).
 */
class Migration_promo_web_elegir extends CI_Migration
{
    public function up()
    {
        if (!$this->tabla('promo_web')) {
            throw new RuntimeException(__METHOD__ . ': falta la tabla promo_web. Se reintenta en la próxima ejecución.');
        }
        $this->ejecutar('ALTER TABLE public.promo_web ADD COLUMN IF NOT EXISTS elegir integer', 'ADD COLUMN promo_web.elegir');
        if (!$this->existe('promo_web', 'elegir')) {
            throw new RuntimeException(__METHOD__ . ': falta promo_web.elegir después de crearla. Se reintenta en la próxima ejecución.');
        }
    }

    private function tabla($tabla)
    {
        $q = $this->db->query("SELECT count(*) AS n FROM information_schema.tables WHERE table_schema = 'public' AND table_name = ?", array($tabla));
        $fila = ($q && method_exists($q, 'row')) ? $q->row() : null;
        return $fila && (int) $fila->n > 0;
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
