<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261124160000
 * Timestamp: 2026-11-24 16:00:00
 *
 * v1.36.0 (pedido del usuario): promos normales (ej. Completo + Bebida a $X) además de las promos de sushi.
 *  - promo_web: qué productos de la Carta son promos normales. Cada promo es un producto con su precio y un MENÚ
 *    (tablas menu / categoria_menu / detalle_categoria_menu, las mismas del POS) con sus productos fijos: una categoría
 *    por producto, con su cantidad (mínimo = máximo, obligatoria). Así cada producto descuenta su stock y sale en su
 *    impresora, como los menús.
 *  - configuracion.promos_sushi_web: botón "Promos Sushi" en la Carta. Queda encendida si ya hay promos de sushi.
 *
 * Idempotente. Funciona en PostgreSQL 10 (VPS). El código no asume que corrió (revisa las tablas).
 */
class Migration_promos_normales_web extends CI_Migration
{
    public function up()
    {
        foreach (array('configuracion', 'producto', 'menu', 'categoria_menu', 'detalle_categoria_menu') as $t) {
            if (!$this->tabla($t)) {
                throw new RuntimeException(__METHOD__ . ': falta la tabla ' . $t . '. Se reintenta en la próxima ejecución.');
            }
        }
        $this->ejecutar('CREATE TABLE IF NOT EXISTS public.promo_web (
                id_producto integer PRIMARY KEY,
                id_menu integer NOT NULL,
                creada timestamp NOT NULL DEFAULT now()
            )', 'CREATE TABLE promo_web');
        $nueva = !$this->existe('configuracion', 'promos_sushi_web');
        $this->ejecutar('ALTER TABLE public.configuracion ADD COLUMN IF NOT EXISTS promos_sushi_web boolean NOT NULL DEFAULT false',
            'ADD COLUMN configuracion.promos_sushi_web');
        // Quien ya usa promos de sushi las sigue viendo
        if ($nueva && $this->tabla('promo_sushi_web')) {
            $this->ejecutar('UPDATE public.configuracion SET promos_sushi_web = true WHERE EXISTS (SELECT 1 FROM public.promo_sushi_web)',
                'UPDATE configuracion.promos_sushi_web');
        }
        if (!$this->existe('promo_web', 'id_menu') || !$this->existe('configuracion', 'promos_sushi_web')) {
            throw new RuntimeException(__METHOD__ . ': faltan promo_web o configuracion.promos_sushi_web después de crearlas. ' .
                'No se marca como aplicada: se reintenta en la próxima ejecución.');
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
