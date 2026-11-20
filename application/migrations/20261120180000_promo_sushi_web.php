<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261120180000
 * Timestamp: 2026-11-20 18:00:00
 *
 * Promociones de sushi armables (pedido del usuario, 20-11-2026, v1.25.0). Una promo es un producto de la Carta
 * (precio, IVA, destino de impresión) con una cantidad de piezas (20, 30, 40, 50…). En la Caja el cliente reparte esas
 * piezas en grupos: cada grupo tiene sus piezas, una base, hasta N rellenos y un envoltorio. Salsas y extras aparte.
 *
 * - promo_sushi_web: qué productos son promo de sushi y sus reglas (piezas, de a cuántas piezas, máximo de rellenos).
 * - promo_sushi_opcion_web: el catálogo de cada promo (bases, rellenos, envoltorios y extras). Cada opción es un producto
 *   (con su receta, si tiene) y un valor adicional por pieza (bases, rellenos, envoltorios) o por unidad (extras); 0 = no cobra.
 *
 * Lo que pidió el cliente NO va en tablas nuevas: se graba en detalle_cotizacion / detalle_venta como los menús (línea de
 * la promo + líneas de lo elegido, con es_detalle_menu e id_detalle_menu_web), con los nombres en el mensaje. Así queda
 * tal cual aunque después cambie el catálogo, y mesas, cobros por partes, anulaciones y comandas lo manejan sin cambios.
 *
 * Nombres con "_web": la base la comparte el POS de escritorio, que no las usa. Idempotente (IF NOT EXISTS).
 * Funciona en PostgreSQL 10 (VPS). El código no asume que corrió (Mcaja::sushis y Mpromosushi preguntan al catálogo).
 */
class Migration_promo_sushi_web extends CI_Migration
{
    public function up()
    {
        $this->ejecutar("CREATE TABLE IF NOT EXISTS public.promo_sushi_web (
                id_producto integer PRIMARY KEY,
                piezas integer NOT NULL CHECK (piezas BETWEEN 1 AND 500),
                paso integer NOT NULL DEFAULT 1 CHECK (paso BETWEEN 1 AND 100),
                max_rellenos integer NOT NULL DEFAULT 3 CHECK (max_rellenos BETWEEN 0 AND 20),
                vigente boolean NOT NULL DEFAULT true,
                fecha_creacion timestamp without time zone NOT NULL DEFAULT now()
            )", 'CREATE TABLE promo_sushi_web');

        $this->ejecutar("CREATE TABLE IF NOT EXISTS public.promo_sushi_opcion_web (
                id_opcion serial PRIMARY KEY,
                id_producto_promo integer NOT NULL REFERENCES public.promo_sushi_web (id_producto) ON DELETE CASCADE,
                tipo varchar(12) NOT NULL CHECK (tipo IN ('base', 'relleno', 'envoltorio', 'extra')),
                id_producto integer NOT NULL,
                valor integer NOT NULL DEFAULT 0 CHECK (valor >= 0),
                orden integer NOT NULL DEFAULT 0
            )", 'CREATE TABLE promo_sushi_opcion_web');

        // Un producto va una sola vez en cada sección de la promo
        $this->ejecutar('CREATE UNIQUE INDEX IF NOT EXISTS promo_sushi_opcion_web_uk
            ON public.promo_sushi_opcion_web (id_producto_promo, tipo, id_producto)', 'CREATE INDEX promo_sushi_opcion_web_uk');

        // Regla 7: comprobar en el catálogo antes de darse por aplicada
        foreach (array(array('promo_sushi_web', 'max_rellenos'), array('promo_sushi_opcion_web', 'valor')) as $tc) {
            if (!$this->existe($tc[0], $tc[1])) {
                throw new RuntimeException(__METHOD__ . ': falta ' . $tc[0] . '.' . $tc[1] . ' después de crearla. ' .
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
