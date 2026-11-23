<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261123100000
 * Timestamp: 2026-11-23 10:00:00
 *
 * v1.30.3 (pedido del usuario): máximo de agregados a elegir por producto en la Caja. Ej. el producto X tiene 5
 * agregados pero se pueden elegir máximo 3; el producto Y, máximo 2.
 *
 * - producto.max_agregados_web  integer, 0 = sin límite (lo normal). Se pone en la Carta > Agregados del producto.
 *
 * Nombre con "_web": la tabla la comparte el POS de escritorio, que no la usa.
 * Idempotente (IF NOT EXISTS). Funciona en PostgreSQL 10 (VPS). El código no asume que corrió (revisa la columna).
 */
class Migration_max_agregados_producto_web extends CI_Migration
{
    public function up()
    {
        if (!$this->existe('producto', 'id_producto')) {
            throw new RuntimeException(__METHOD__ . ': falta la tabla producto. Se reintenta en la próxima ejecución.');
        }
        $this->ejecutar("ALTER TABLE public.producto ADD COLUMN IF NOT EXISTS max_agregados_web integer DEFAULT 0",
            'ADD COLUMN producto.max_agregados_web');
        // Regla 7: comprobar en el catálogo antes de darse por aplicada
        if (!$this->existe('producto', 'max_agregados_web')) {
            throw new RuntimeException(__METHOD__ . ': falta producto.max_agregados_web después de crearla. ' .
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
