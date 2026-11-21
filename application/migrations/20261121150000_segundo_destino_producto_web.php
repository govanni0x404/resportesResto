<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261121150000
 * Timestamp: 2026-11-21 15:00:00
 *
 * v1.30.0 (pedido del usuario): un producto puede salir en DOS comandas. Ej. "arroz con carne": el arroz se hace en la
 * cocina y la carne en la parrilla, al otro lado del local.
 *
 * - producto.id_dimpresion2_web  integer, 0 = sin segundo destino (lo normal). Se elige en la ficha del producto (Carta)
 *   con la casilla "Imprimir también en otro destino". La comanda sale completa en su destino de siempre Y en este.
 *
 * (El agregado que sale en su propio destino ya tenía su casilla: agregados.usar_destino_impresion.)
 * Nombre con "_web": la tabla la comparte el POS de escritorio, que no la usa.
 * Idempotente (IF NOT EXISTS). Funciona en PostgreSQL 10 (VPS). El código no asume que corrió (revisa la columna).
 */
class Migration_segundo_destino_producto_web extends CI_Migration
{
    public function up()
    {
        if (!$this->existe('producto', 'id_producto')) {
            throw new RuntimeException(__METHOD__ . ': falta la tabla producto. Se reintenta en la próxima ejecución.');
        }
        $this->ejecutar("ALTER TABLE public.producto ADD COLUMN IF NOT EXISTS id_dimpresion2_web integer DEFAULT 0",
            'ADD COLUMN producto.id_dimpresion2_web');
        // Regla 7: comprobar en el catálogo antes de darse por aplicada
        if (!$this->existe('producto', 'id_dimpresion2_web')) {
            throw new RuntimeException(__METHOD__ . ': falta producto.id_dimpresion2_web después de crearla. ' .
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
