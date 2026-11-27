<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261127100000
 * Timestamp: 2026-11-27 10:00:00
 *
 * v1.43.1 (pedido del usuario): "Permite varios agregados" (producto.varios_agregados_web) parte APAGADO en los productos
 * nuevos: valor por defecto false (y NULL también cuenta como no). Los productos que ya existen no se tocan (quedaron como
 * se eligió en la migración 20261126140000). SET DEFAULT no reescribe la tabla en PostgreSQL 10.
 *
 * Idempotente. Funciona en PostgreSQL 10 (VPS).
 */
class Migration_varios_agregados_default_false_web extends CI_Migration
{
    public function up()
    {
        if (!$this->existe('producto', 'varios_agregados_web')) {
            throw new RuntimeException(__METHOD__ . ': falta producto.varios_agregados_web. Se reintenta en la próxima ejecución.');
        }
        $this->ejecutar('ALTER TABLE public.producto ALTER COLUMN varios_agregados_web SET DEFAULT false', 'SET DEFAULT producto.varios_agregados_web');
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
