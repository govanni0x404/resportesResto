<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261112120000
 * Timestamp: 2026-11-12 12:00:00
 *
 * venta.nombre_retira: nombre de quien retira el pedido en venta directa (lo escribe el cajero en la ventana Cobrar,
 * opcional). Se imprime en la comanda y en el documento (boleta, factura, comprobante).
 *
 * - ADD COLUMN IF NOT EXISTS sin DEFAULT: en PostgreSQL no reescribe la tabla venta (solo catálogo).
 * - El código no asume que la columna existe (Mcaja::insertar filtra por las columnas de la tabla).
 */
class Migration_add_venta_nombre_retira extends CI_Migration
{
    public function up()
    {
        if (!$this->existe('venta', 'id_venta')) {
            log_message('info', __METHOD__ . ': sin tabla venta, nada que agregar.');
            return;
        }
        $this->ejecutar('ALTER TABLE public.venta ADD COLUMN IF NOT EXISTS nombre_retira character varying(60)', 'ADD COLUMN venta.nombre_retira');
        $this->ejecutar("COMMENT ON COLUMN public.venta.nombre_retira IS 'Venta directa: nombre de quien retira el pedido (Caja web)'",
            'COMMENT venta.nombre_retira');

        // Regla 7: comprobar en el catálogo antes de darse por aplicada.
        if (!$this->existe('venta', 'nombre_retira')) {
            throw new RuntimeException(
                __METHOD__ . ': falta venta.nombre_retira después de crearla. ' .
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
        // No destructivo a propósito: se perderían los nombres ya guardados. Si hiciera falta, va en una migración nueva.
        log_message('info', __METHOD__ . ': sin cambios (down no destructivo).');
    }
}
