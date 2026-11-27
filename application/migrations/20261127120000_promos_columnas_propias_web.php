<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261127120000
 * Timestamp: 2026-11-27 12:00:00
 *
 * v1.45.0 (pedido del usuario: "el menú déjalo como estaba; las promos normales y de sushi, en tablas aparte"):
 * al vender, las promos ya no usan las marcas del menú (es_menu / es_detalle_menu / id_detalle_menu_web), sino las suyas:
 *  - Promo normal: detalle_cotizacion / detalle_venta .es_promo_web (la fila de la promo) y .es_detalle_promo_web (cada
 *    producto de la promo); se unen en detalle_promo_web (ya existe).
 *  - Promo de sushi: .es_promo_sushi_web y .es_detalle_promo_sushi_web; se unen en la tabla nueva detalle_promo_sushi_web.
 *  El menú sigue igual. Las ventas ya grabadas no se tocan (el sistema sigue leyendo las antiguas).
 *  Columnas sin valor por defecto (NULL = no) para no reescribir tablas grandes en PostgreSQL 10.
 *
 * Idempotente. Funciona en PostgreSQL 10 (VPS). El código no asume que corrió (revisa las columnas).
 */
class Migration_promos_columnas_propias_web extends CI_Migration
{
    public function up()
    {
        $this->ejecutar('CREATE TABLE IF NOT EXISTS public.detalle_promo_sushi_web (
                id_detalle_promo_sushi_web serial PRIMARY KEY,
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
            )', 'CREATE TABLE detalle_promo_sushi_web');
        $this->ejecutar('CREATE INDEX IF NOT EXISTS detalle_promo_sushi_web_cot_idx ON public.detalle_promo_sushi_web (id_detalle_cotizacion_producto)', 'INDEX detalle_promo_sushi_web cot');
        $this->ejecutar('CREATE INDEX IF NOT EXISTS detalle_promo_sushi_web_venta_idx ON public.detalle_promo_sushi_web (id_venta)', 'INDEX detalle_promo_sushi_web venta');
        $columnas = array('es_promo_web', 'es_detalle_promo_web', 'es_promo_sushi_web', 'es_detalle_promo_sushi_web');
        foreach (array('detalle_cotizacion', 'detalle_venta') as $t) {
            if (!$this->tabla($t)) {
                throw new RuntimeException(__METHOD__ . ': falta la tabla ' . $t . '. Se reintenta en la próxima ejecución.');
            }
            foreach ($columnas as $c) $this->ejecutar("ALTER TABLE public.$t ADD COLUMN IF NOT EXISTS $c boolean", "ADD COLUMN $t.$c");
            foreach ($columnas as $c) {
                if (!$this->existe($t, $c)) throw new RuntimeException(__METHOD__ . ": falta $t.$c después de crearla. Se reintenta en la próxima ejecución.");
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
