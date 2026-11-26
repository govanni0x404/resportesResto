<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261126140000
 * Timestamp: 2026-11-26 14:00:00
 *
 * v1.43.0 (pedido del usuario): en la Carta, cada producto dice si permite VARIOS agregados (como producto.es_menu).
 *  - producto.varios_agregados_web: sí = puede llevar varios (hasta su máximo); no = un solo agregado (va en id_agregado).
 *    NULL = sí. Opción elegida por el usuario: los que hoy ya permiten varios (máximo distinto de 1) quedan marcados; los de
 *    máximo 1, sin marcar. Así nada cambia al subirlo.
 *  - producto.max_agregados_web: "0 es 0" (pedido del usuario): NULL = sin límite, 0 = ningún agregado, N = hasta N. Hasta
 *    ahora 0 era "sin límite": esos 0 pasan a NULL (una sola vez, al crear la columna nueva) y la columna ya no parte en 0.
 *
 * Idempotente. Funciona en PostgreSQL 10 (VPS). El código no asume que corrió (revisa la columna).
 */
class Migration_varios_agregados_producto_web extends CI_Migration
{
    public function up()
    {
        if (!$this->tabla('producto') || !$this->existe('producto', 'max_agregados_web')) {
            throw new RuntimeException(__METHOD__ . ': faltan producto o producto.max_agregados_web. Se reintenta en la próxima ejecución.');
        }
        $nueva = !$this->existe('producto', 'varios_agregados_web');
        $this->ejecutar('ALTER TABLE public.producto ADD COLUMN IF NOT EXISTS varios_agregados_web boolean', 'ADD COLUMN producto.varios_agregados_web');
        if ($nueva) {
            // Opción (a): marcados los que hoy permiten varios; sin marcar los de máximo 1
            $this->ejecutar('UPDATE public.producto SET varios_agregados_web = (COALESCE(max_agregados_web, 0) <> 1)', 'UPDATE producto.varios_agregados_web');
            // "0 es 0": el 0 de antes era "sin límite" => NULL (una sola vez)
            $this->ejecutar('UPDATE public.producto SET max_agregados_web = NULL WHERE max_agregados_web = 0', 'UPDATE producto.max_agregados_web 0 => NULL');
        }
        $this->ejecutar('ALTER TABLE public.producto ALTER COLUMN max_agregados_web DROP DEFAULT', 'DROP DEFAULT producto.max_agregados_web');
        if (!$this->existe('producto', 'varios_agregados_web')) {
            throw new RuntimeException(__METHOD__ . ': falta producto.varios_agregados_web después de crearla. Se reintenta en la próxima ejecución.');
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
