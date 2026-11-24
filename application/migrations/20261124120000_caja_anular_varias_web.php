<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261124120000
 * Timestamp: 2026-11-24 12:00:00
 *
 * v1.33.0 (pedidos del usuario en cambios.md): anular varias cosas de una sola vez, cada una con su casilla en
 * Configuraciones > Modo de la Caja web (las dos parten apagadas):
 *  - configuracion.esperas_anular_todas_web  boolean  Ventas en espera: botón "Anular todas"
 *  - configuracion.mesas_anular_varias_web   boolean  Mesas: botón "Anular varias mesas"
 *
 * Idempotente (IF NOT EXISTS). Funciona en PostgreSQL 10 (VPS). El código no asume que corrió (revisa las columnas).
 */
class Migration_caja_anular_varias_web extends CI_Migration
{
    public function up()
    {
        if (!$this->tabla('configuracion')) {
            throw new RuntimeException(__METHOD__ . ': falta la tabla configuracion. Se reintenta en la próxima ejecución.');
        }
        foreach (array('esperas_anular_todas_web', 'mesas_anular_varias_web') as $col) {
            $this->ejecutar("ALTER TABLE public.configuracion ADD COLUMN IF NOT EXISTS $col boolean NOT NULL DEFAULT false",
                'ADD COLUMN configuracion.' . $col);
        }
        // Regla 7: comprobar en el catálogo antes de darse por aplicada
        foreach (array('esperas_anular_todas_web', 'mesas_anular_varias_web') as $col) {
            if (!$this->existe('configuracion', $col)) {
                throw new RuntimeException(__METHOD__ . ': falta configuracion.' . $col . ' después de crearla. ' .
                    'No se marca como aplicada: se reintenta en la próxima ejecución.');
            }
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
