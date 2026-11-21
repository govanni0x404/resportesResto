<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261121110000
 * Timestamp: 2026-11-21 11:00:00
 *
 * Promos de sushi por ROLL (pedido del usuario, 21-11-2026, v1.26.0). Hasta ahora una promo se armaba por piezas
 * (30 piezas repartidas en grupos de N piezas). Ahora también por rolls: una promo de 30 piezas son 3 rolls de 10, una
 * de 50 son 5 rolls. En la Caja el cliente arma cada roll (o grupo de rolls iguales) con base, rellenos y envoltorio.
 *
 * - promo_sushi_web.modo: 'pieza' (como antes) o 'rol'. Todas las promos que ya existen quedan 'pieza'.
 * - promo_sushi_web.piezas_rol: piezas de cada roll (10 por defecto). En una promo por roll, piezas = rolls x piezas_rol
 *   y el valor adicional de sus opciones es por roll (no por pieza).
 *
 * Nombre con "_web": la base la comparte el POS de escritorio, que no la usa. Idempotente (IF NOT EXISTS).
 * Funciona en PostgreSQL 10 (VPS). El código no asume que corrió (Mcaja::sushis y Mpromosushi preguntan al catálogo).
 */
class Migration_promo_sushi_rol_web extends CI_Migration
{
    public function up()
    {
        if (!$this->existe('promo_sushi_web', 'id_producto')) {
            throw new RuntimeException(__METHOD__ . ': falta la tabla promo_sushi_web (migración 20261120180000). ' .
                'Se reintenta en la próxima ejecución.');
        }
        $this->ejecutar("ALTER TABLE public.promo_sushi_web ADD COLUMN IF NOT EXISTS modo varchar(5) NOT NULL DEFAULT 'pieza'
                CHECK (modo IN ('pieza', 'rol'))", 'ADD COLUMN promo_sushi_web.modo');
        $this->ejecutar('ALTER TABLE public.promo_sushi_web ADD COLUMN IF NOT EXISTS piezas_rol integer NOT NULL DEFAULT 10
                CHECK (piezas_rol BETWEEN 1 AND 100)', 'ADD COLUMN promo_sushi_web.piezas_rol');

        // Regla 7: comprobar en el catálogo antes de darse por aplicada
        foreach (array('modo', 'piezas_rol') as $col) {
            if (!$this->existe('promo_sushi_web', $col)) {
                throw new RuntimeException(__METHOD__ . ": falta promo_sushi_web.$col después de crearla. " .
                    'No se marca como aplicada: se reintenta en la próxima ejecución.');
            }
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
