<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261126120000
 * Timestamp: 2026-11-26 12:00:00
 *
 * v1.41.0 (pedido del usuario: "una tabla intermedia para llevar todo ordenado"):
 *  1. promo_producto_web: los productos de cada promo normal (Carta > Promos). Antes estaban en las tablas de menú
 *     (menu / categoria_menu / detalle_categoria_menu). Fija: cada producto con su cantidad. "El cliente elige N": la lista
 *     de productos (cantidad 1); N está en promo_web.elegir.
 *  2. detalle_promo_web: qué productos lleva cada promo VENDIDA. Une la fila de la promo con la fila de cada producto,
 *     en el pedido (detalle_cotizacion) y en la venta cobrada (detalle_venta). Las filas siguen como antes (precio, stock,
 *     impresora, informes): la tabla solo dice qué va con qué.
 *  3. detalle_agregado_web: los agregados de un producto cuando son VARIOS (con uno solo queda como antes, en
 *     id_agregado). Cada agregado con su orden, mensaje y valor; el primero va en la misma fila del producto (sin fila
 *     propia) y los demás en su fila. En el pedido y en la venta cobrada.
 *  Las promos que ya existen se pasan a promo_producto_web; su menú queda no vigente (no se borra) y el producto deja de
 *  ser menú. Los pedidos abiertos de antes siguen funcionando (el código también lee la unión antigua).
 *
 * Idempotente. Funciona en PostgreSQL 10 (VPS). El código no asume que corrió (revisa las tablas).
 */
class Migration_promo_agregados_tablas_web extends CI_Migration
{
    public function up()
    {
        foreach (array('producto', 'promo_web', 'detalle_cotizacion', 'detalle_venta') as $t) {
            if (!$this->tabla($t)) {
                throw new RuntimeException(__METHOD__ . ': falta la tabla ' . $t . '. Se reintenta en la próxima ejecución.');
            }
        }
        $this->ejecutar('CREATE TABLE IF NOT EXISTS public.promo_producto_web (
                id_promo_producto_web serial PRIMARY KEY,
                id_producto_promo integer NOT NULL,
                id_producto integer NOT NULL,
                cantidad integer NOT NULL DEFAULT 1,
                orden integer NOT NULL DEFAULT 0,
                vigente boolean NOT NULL DEFAULT true
            )', 'CREATE TABLE promo_producto_web');
        $this->ejecutar('CREATE INDEX IF NOT EXISTS promo_producto_web_promo_idx ON public.promo_producto_web (id_producto_promo)', 'INDEX promo_producto_web');
        $this->ejecutar('CREATE TABLE IF NOT EXISTS public.detalle_promo_web (
                id_detalle_promo_web serial PRIMARY KEY,
                id_detalle_cotizacion integer,
                id_detalle_cotizacion_producto integer,
                id_venta integer,
                id_detalle_venta integer,
                id_detalle_venta_producto integer,
                id_producto_promo integer NOT NULL,
                id_producto integer NOT NULL,
                cantidad numeric(12,3) NOT NULL DEFAULT 1,
                mensaje varchar(250),
                creado timestamp NOT NULL DEFAULT now()
            )', 'CREATE TABLE detalle_promo_web');
        $this->ejecutar('CREATE INDEX IF NOT EXISTS detalle_promo_web_cot_idx ON public.detalle_promo_web (id_detalle_cotizacion_producto)', 'INDEX detalle_promo_web cot');
        $this->ejecutar('CREATE INDEX IF NOT EXISTS detalle_promo_web_venta_idx ON public.detalle_promo_web (id_venta)', 'INDEX detalle_promo_web venta');
        $this->ejecutar('CREATE TABLE IF NOT EXISTS public.detalle_agregado_web (
                id_detalle_agregado_web serial PRIMARY KEY,
                id_detalle_cotizacion integer,
                id_detalle_cotizacion_agregado integer,
                id_venta integer,
                id_detalle_venta integer,
                id_detalle_venta_agregado integer,
                id_producto integer NOT NULL,
                orden integer NOT NULL DEFAULT 1,
                mensaje varchar(250),
                valor integer NOT NULL DEFAULT 0,
                creado timestamp NOT NULL DEFAULT now()
            )', 'CREATE TABLE detalle_agregado_web');
        $this->ejecutar('CREATE INDEX IF NOT EXISTS detalle_agregado_web_cot_idx ON public.detalle_agregado_web (id_detalle_cotizacion)', 'INDEX detalle_agregado_web cot');
        $this->ejecutar('CREATE INDEX IF NOT EXISTS detalle_agregado_web_hijo_idx ON public.detalle_agregado_web (id_detalle_cotizacion_agregado)', 'INDEX detalle_agregado_web hijo');
        $this->ejecutar('CREATE INDEX IF NOT EXISTS detalle_agregado_web_venta_idx ON public.detalle_agregado_web (id_venta)', 'INDEX detalle_agregado_web venta');

        // Las promos que ya existen: sus productos pasan del menú a promo_producto_web (solo las que aún no tienen filas)
        if ($this->tabla('categoria_menu') && $this->tabla('detalle_categoria_menu') && $this->existe('promo_web', 'id_menu')) {
            $elegir = $this->existe('promo_web', 'elegir') ? 'COALESCE(w.elegir, 0)' : '0';
            $this->ejecutar("INSERT INTO public.promo_producto_web (id_producto_promo, id_producto, cantidad, orden, vigente)
                SELECT w.id_producto, d.id_producto,
                       CASE WHEN $elegir > 0 THEN 1 ELSE GREATEST(COALESCE(c.cantidad_minima, 1), 1) END,
                       row_number() OVER (PARTITION BY w.id_producto ORDER BY COALESCE(c.orden, 0), c.id_categoria_menu, d.id_detalle_categoria_menu), true
                FROM public.promo_web w
                JOIN public.categoria_menu c ON c.id_menu = w.id_menu AND c.vigente IS NOT FALSE
                JOIN public.detalle_categoria_menu d ON d.id_categoria_menu = c.id_categoria_menu AND d.vigente IS NOT FALSE
                WHERE NOT EXISTS (SELECT 1 FROM public.promo_producto_web x WHERE x.id_producto_promo = w.id_producto)",
                'INSERT promo_producto_web desde el menú');
            // El producto de la promo deja de ser menú y su menú queda no vigente (no se borra)
            $this->ejecutar('UPDATE public.menu SET vigente = false WHERE id_menu IN (SELECT id_menu FROM public.promo_web WHERE COALESCE(id_menu, 0) > 0)', 'UPDATE menu de las promos');
            $this->ejecutar('UPDATE public.producto SET es_menu = false, id_menu = 0 WHERE id_producto IN (SELECT id_producto FROM public.promo_web)', 'UPDATE producto de las promos');
        }
        foreach (array('promo_producto_web', 'detalle_promo_web', 'detalle_agregado_web') as $t) {
            if (!$this->tabla($t)) throw new RuntimeException(__METHOD__ . ': falta ' . $t . ' después de crearla. Se reintenta en la próxima ejecución.');
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
