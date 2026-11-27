<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261126110000
 * Timestamp: 2026-11-26 11:00:00
 *
 * v1.40.0 (pedido del usuario): Configuraciones > "Imprimir en modo texto (más rápido)".
 *  - configuracion.impresion_texto_web: NULL o false = boletas, precuentas y comandas como imagen con el diseño de la
 *    página (como hasta ahora); true = en texto directo al agente (más rápido, sin el diseño). Para el local que reclame
 *    que imprime lento. Sin valor por defecto para no reescribir la tabla en PostgreSQL 10.
 *
 * Idempotente. Funciona en PostgreSQL 10 (VPS). El código no asume que corrió (revisa la columna).
 */
class Migration_impresion_texto_web extends CI_Migration
{
    public function up()
    {
        if (!$this->tabla('configuracion')) {
            throw new RuntimeException(__METHOD__ . ': falta la tabla configuracion. Se reintenta en la próxima ejecución.');
        }
        $this->ejecutar('ALTER TABLE public.configuracion ADD COLUMN IF NOT EXISTS impresion_texto_web boolean', 'ADD COLUMN configuracion.impresion_texto_web');
        if (!$this->existe('configuracion', 'impresion_texto_web')) {
            throw new RuntimeException(__METHOD__ . ': falta configuracion.impresion_texto_web después de crearla. Se reintenta en la próxima ejecución.');
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
