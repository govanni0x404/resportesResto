<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261124150000
 * Timestamp: 2026-11-24 15:00:00
 *
 * v1.35.1 (el usuario pidió otro diseño para lo de la v1.35.0):
 *  - Extras: cada producto tiene su casilla "Extras con cobro" en la Carta. Los agregados dentro del máximo van sin costo
 *    y los extras cobran el valor adicional de su agregado. Cuántos extras: uno solo para todo, en Configuraciones.
 *      producto.extras_web                     boolean
 *      configuracion.agregados_max_extras_web  integer  (0 = sin tope; parte en 1)
 *  - Categorías: la categoría es del PRODUCTO agregado (Pollo es Proteína en todos los productos), y el máximo por
 *    categoría es uno solo, en Configuraciones.
 *      producto.id_categoria_agregado_web          integer  (0 = sin categoría). Se llena con la que ya tenían sus agregados.
 *      configuracion.agregados_max_categoria_web   integer  (0 = sin límite)
 * Las columnas de la 20261124140000 que ya no se usan (agregados.valor_extra_web, agregados.id_categoria_agregado_web,
 * producto.max_extras_web, producto_categoria_agregado_web) se dejan (no destructivo).
 *
 * Idempotente. Funciona en PostgreSQL 10 (VPS). El código no asume que corrió (revisa las columnas).
 */
class Migration_agregados_extras_producto_web extends CI_Migration
{
    public function up()
    {
        foreach (array('configuracion', 'agregados', 'producto') as $t) {
            if (!$this->tabla($t)) {
                throw new RuntimeException(__METHOD__ . ': falta la tabla ' . $t . '. Se reintenta en la próxima ejecución.');
            }
        }
        $this->ejecutar('ALTER TABLE public.producto ADD COLUMN IF NOT EXISTS extras_web boolean NOT NULL DEFAULT false', 'ADD COLUMN producto.extras_web');
        $this->ejecutar('ALTER TABLE public.producto ADD COLUMN IF NOT EXISTS id_categoria_agregado_web integer NOT NULL DEFAULT 0',
            'ADD COLUMN producto.id_categoria_agregado_web');
        $this->ejecutar('ALTER TABLE public.configuracion ADD COLUMN IF NOT EXISTS agregados_max_extras_web integer NOT NULL DEFAULT 1',
            'ADD COLUMN configuracion.agregados_max_extras_web');
        $this->ejecutar('ALTER TABLE public.configuracion ADD COLUMN IF NOT EXISTS agregados_max_categoria_web integer NOT NULL DEFAULT 0',
            'ADD COLUMN configuracion.agregados_max_categoria_web');
        // La categoría que ya tenían los agregados (v1.35.0) pasa a su producto
        if ($this->existe('agregados', 'id_categoria_agregado_web')) {
            $this->ejecutar('UPDATE public.producto p SET id_categoria_agregado_web = x.c
                FROM (SELECT id_producto, max(id_categoria_agregado_web) AS c FROM public.agregados
                      WHERE id_categoria_agregado_web > 0 AND eliminado IS NOT TRUE GROUP BY id_producto) x
                WHERE x.id_producto = p.id_producto AND p.id_categoria_agregado_web = 0', 'UPDATE producto.id_categoria_agregado_web');
        }

        // Regla 7: comprobar en el catálogo antes de darse por aplicada
        $deben = array(array('producto', 'extras_web'), array('producto', 'id_categoria_agregado_web'),
            array('configuracion', 'agregados_max_extras_web'), array('configuracion', 'agregados_max_categoria_web'));
        foreach ($deben as $d) {
            if (!$this->existe($d[0], $d[1])) {
                throw new RuntimeException(__METHOD__ . ': falta ' . $d[0] . '.' . $d[1] . ' después de crearla. ' .
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
