<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261126130000
 * Timestamp: 2026-11-26 13:00:00
 *
 * v1.42.0 (pedido del usuario: "cuando son varios agregados, como el menú"):
 *  - detalle_cotizacion.es_con_agregados_web y detalle_venta.es_con_agregados_web: la fila del producto que lleva VARIOS
 *    agregados (como es_menu). Esa fila ya no usa id_agregado (queda en 0).
 *  - detalle_cotizacion.es_agregado_web y detalle_venta.es_agregado_web: la fila de CADA agregado de ese producto, también
 *    el primero (como es_detalle_menu). Todos quedan unidos al producto en detalle_agregado_web.
 *  Con UN solo agregado nada cambia: va en id_agregado de la fila del producto, como siempre.
 *  Sin valor por defecto (NULL = no) para no reescribir tablas grandes en PostgreSQL 10.
 *
 * Idempotente. Funciona en PostgreSQL 10 (VPS). El código no asume que corrió (revisa las columnas).
 */
class Migration_agregados_varios_columnas_web extends CI_Migration
{
    public function up()
    {
        foreach (array('detalle_cotizacion', 'detalle_venta') as $t) {
            if (!$this->tabla($t)) {
                throw new RuntimeException(__METHOD__ . ': falta la tabla ' . $t . '. Se reintenta en la próxima ejecución.');
            }
            $this->ejecutar("ALTER TABLE public.$t ADD COLUMN IF NOT EXISTS es_con_agregados_web boolean", "ADD COLUMN $t.es_con_agregados_web");
            $this->ejecutar("ALTER TABLE public.$t ADD COLUMN IF NOT EXISTS es_agregado_web boolean", "ADD COLUMN $t.es_agregado_web");
            if (!$this->existe($t, 'es_con_agregados_web') || !$this->existe($t, 'es_agregado_web')) {
                throw new RuntimeException(__METHOD__ . ": faltan las columnas en $t después de crearlas. Se reintenta en la próxima ejecución.");
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
