<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261124110000
 * Timestamp: 2026-11-24 11:00:00
 *
 * v1.32.5 (pedido del usuario): en los límites de las promos de sushi "0 es 0" (no se ofrece esa sección) y
 * "sin límite" pasa a ser -1. Columnas: promo_sushi_web.max_rellenos, max_extras_web y max_salsas_web.
 *
 * - max_rellenos: su regla era BETWEEN 0 AND 20; pasa a BETWEEN -1 AND 20.
 * - max_extras_web y max_salsas_web: por defecto -1 (sin límite).
 * - Lo que ya estaba en 0 significaba "sin límite": pasa a -1 UNA sola vez (solo cuando la regla vieja todavía está,
 *   así una segunda ejecución no cambia un 0 puesto a propósito).
 *
 * Funciona en PostgreSQL 10 (VPS).
 */
class Migration_promo_sushi_sin_limite_web extends CI_Migration
{
    public function up()
    {
        if (!$this->existe('promo_sushi_web', 'max_rellenos')) {
            throw new RuntimeException(__METHOD__ . ': falta promo_sushi_web. Se reintenta en la próxima ejecución.');
        }
        $q = $this->db->query("SELECT count(*) AS n FROM pg_constraint WHERE conrelid = 'public.promo_sushi_web'::regclass
            AND contype = 'c' AND conname = 'promo_sushi_web_max_rellenos_check' AND pg_get_constraintdef(oid) LIKE '%>= 0%'");
        $vieja = $q && (int)$q->row()->n > 0;
        $conOp = $this->existe('promo_sushi_web', 'max_extras_web') && $this->existe('promo_sushi_web', 'max_salsas_web');
        if ($vieja) {
            $this->ejecutar('ALTER TABLE public.promo_sushi_web DROP CONSTRAINT IF EXISTS promo_sushi_web_max_rellenos_check', 'DROP CONSTRAINT max_rellenos');
            $this->ejecutar('UPDATE public.promo_sushi_web SET max_rellenos = -1 WHERE max_rellenos = 0', 'UPDATE max_rellenos 0 -> -1');
            $this->ejecutar('ALTER TABLE public.promo_sushi_web ADD CONSTRAINT promo_sushi_web_max_rellenos_check CHECK (max_rellenos BETWEEN -1 AND 20)',
                'ADD CONSTRAINT max_rellenos -1..20');
            if ($conOp) {
                $this->ejecutar('UPDATE public.promo_sushi_web SET max_extras_web = -1 WHERE max_extras_web = 0', 'UPDATE max_extras_web 0 -> -1');
                $this->ejecutar('UPDATE public.promo_sushi_web SET max_salsas_web = -1 WHERE max_salsas_web = 0', 'UPDATE max_salsas_web 0 -> -1');
            }
        }
        if ($conOp) {
            $this->ejecutar('ALTER TABLE public.promo_sushi_web ALTER COLUMN max_extras_web SET DEFAULT -1', 'DEFAULT max_extras_web');
            $this->ejecutar('ALTER TABLE public.promo_sushi_web ALTER COLUMN max_salsas_web SET DEFAULT -1', 'DEFAULT max_salsas_web');
        }
        // Regla 7: comprobar que quedó la regla nueva
        $q = $this->db->query("SELECT count(*) AS n FROM pg_constraint WHERE conrelid = 'public.promo_sushi_web'::regclass
            AND conname = 'promo_sushi_web_max_rellenos_check' AND pg_get_constraintdef(oid) LIKE '%-1%'");
        if (!$q || (int)$q->row()->n === 0) {
            throw new RuntimeException(__METHOD__ . ': no quedó la regla nueva de max_rellenos. Se reintenta en la próxima ejecución.');
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
