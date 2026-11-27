<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261127110000
 * Timestamp: 2026-11-27 11:00:00
 *
 * v1.44.0 (pedido del usuario: "separa las cosas"): dos parámetros del producto, excluyentes:
 *  - "Con Agregado" (producto.con_agregado, la de siempre) = uno a uno, como antes (id_agregado);
 *  - "Con Varios Agregados" (producto.varios_agregados_web) = varios agregados, con su máximo a elegir, en las tablas nuevas.
 * Los productos que ya existen se ordenan una vez:
 *  - con agregados y un máximo de 2 o más (ya se usaban para varios) => Con Varios Agregados (y sin Con Agregado);
 *  - todos los demás => sin Con Varios Agregados (los que tienen Con Agregado quedan uno a uno).
 *
 * Funciona en PostgreSQL 10 (VPS).
 */
class Migration_con_varios_agregados_separado_web extends CI_Migration
{
    public function up()
    {
        if (!$this->existe('producto', 'varios_agregados_web') || !$this->existe('producto', 'max_agregados_web')) {
            throw new RuntimeException(__METHOD__ . ': faltan producto.varios_agregados_web o max_agregados_web. Se reintenta en la próxima ejecución.');
        }
        $this->ejecutar('UPDATE public.producto p SET varios_agregados_web = true, con_agregado = false
            WHERE COALESCE(p.max_agregados_web, 0) > 1
              AND EXISTS (SELECT 1 FROM public.agregados a WHERE a.id_producto_maestro = p.id_producto AND a.eliminado IS NOT TRUE)',
            'UPDATE producto con varios agregados');
        $this->ejecutar('UPDATE public.producto p SET varios_agregados_web = false
            WHERE NOT (COALESCE(p.max_agregados_web, 0) > 1
              AND EXISTS (SELECT 1 FROM public.agregados a WHERE a.id_producto_maestro = p.id_producto AND a.eliminado IS NOT TRUE))',
            'UPDATE producto uno a uno');
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
