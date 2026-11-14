<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261110190000
 * Timestamp: 2026-11-10 19:00:00
 *
 * Agrega producto.favorito: los productos marcados con la estrella en Mantención > Carta aparecen en el
 * botón Favoritos (★) de la Caja web.
 *
 * - Sin DEFAULT a propósito (Regla 3: en PostgreSQL 10 un ADD COLUMN con DEFAULT reescribe la tabla entera).
 *   NULL equivale a "no favorito": el código siempre lee `favorito IS TRUE`.
 * - El código no asume que esta migración ya corrió: Mcaja::catalogo y Mproductos preguntan al catálogo si la
 *   columna existe y, si no está, la Caja y la Carta funcionan igual que antes, sin favoritos.
 * - Entrega una sola cosa: la columna (más su comentario). No toca funciones ni triggers.
 */
class Migration_add_producto_favorito extends CI_Migration
{
    public function up()
    {
        $this->ejecutar(
            'ALTER TABLE public.producto ADD COLUMN IF NOT EXISTS favorito boolean',
            'ALTER producto.favorito'
        );

        $this->ejecutar(
            "COMMENT ON COLUMN public.producto.favorito IS 'Favorito de la Caja web: se marca con la estrella en Mantención > Carta'",
            'COMMENT producto.favorito'
        );

        // Regla 7: comprobar en el catálogo antes de darse por aplicada.
        $q = $this->db->query(
            "SELECT count(*) AS n FROM information_schema.columns
              WHERE table_schema = 'public' AND table_name = 'producto'
                AND column_name = 'favorito'");
        $fila = ($q && method_exists($q, 'row')) ? $q->row() : null;
        if (!$fila || (int) $fila->n === 0) {
            throw new RuntimeException(
                __METHOD__ . ': producto.favorito no existe después del ALTER. ' .
                'No se marca como aplicada: se reintenta en la próxima ejecución.');
        }
    }

    /**
     * Regla 7. Con db_debug en false `query()` NO lanza: devuelve FALSE. Por eso se mira el RETORNO
     * y se lanza, para que CI_Migration no grabe la versión de una migración que no hizo su trabajo.
     */
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
        // No destructivo a propósito (ver "down() se ejecuta más de lo que crees" en CONVENCIONES.md):
        // borrar la columna perdería las marcas de favoritos. Si hiciera falta quitarla, va en una migración nueva.
        log_message('info', __METHOD__ . ': sin cambios (down no destructivo).');
    }
}
