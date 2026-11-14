<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261114130000
 * Timestamp: 2026-11-14 13:00:00
 *
 * configuracion.imprimir_comandas_app: la Caja web imprime las comandas de los pedidos que llegan de la app de
 * garzones (API2: detalle_cotizacion con desde_android = true e impreso = false), cada producto en su destino de
 * impresión. Hasta ahora esas comandas las imprimía solo el POS de escritorio; en un local que trabaja solo con la web
 * no salían en cocina.
 *
 * - Sin DEFAULT: NULL = apagada. Así no se imprime dos veces donde sigue funcionando el POS de escritorio; se enciende
 *   en Configuraciones solo en los locales sin POS.
 * - ADD COLUMN IF NOT EXISTS sin DEFAULT: no reescribe la tabla. El código no asume que corrió (sin la columna = apagada).
 */
class Migration_add_configuracion_comandas_app extends CI_Migration
{
    public function up()
    {
        if (!$this->existe('configuracion', 'id_configuracion') && !$this->tabla('configuracion')) {
            log_message('info', __METHOD__ . ': sin tabla configuracion, nada que agregar.');
            return;
        }
        $this->ejecutar('ALTER TABLE public.configuracion ADD COLUMN IF NOT EXISTS imprimir_comandas_app boolean',
            'ADD COLUMN configuracion.imprimir_comandas_app');
        $this->ejecutar("COMMENT ON COLUMN public.configuracion.imprimir_comandas_app IS 'Caja web: imprime las comandas de la app de garzones (solo en locales sin POS de escritorio)'",
            'COMMENT configuracion.imprimir_comandas_app');

        // Regla 7: comprobar en el catálogo antes de darse por aplicada.
        if (!$this->existe('configuracion', 'imprimir_comandas_app')) {
            throw new RuntimeException(__METHOD__ . ': falta configuracion.imprimir_comandas_app después de crearla. ' .
                'No se marca como aplicada: se reintenta en la próxima ejecución.');
        }
    }

    private function tabla($tabla)
    {
        $q = $this->db->query("SELECT to_regclass(?) IS NOT NULL AS hay", array('public.' . $tabla));
        $f = ($q && method_exists($q, 'row')) ? $q->row() : null;
        return $f && ($f->hay === 't' || $f->hay === true);
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

    /** Regla 7: con db_debug en false query() devuelve FALSE; se lanza para no grabar la versión. */
    private function ejecutar($sql, $etiqueta)
    {
        $resultado = null;
        $errorSql = '';
        try {
            $resultado = $this->db->query($sql);
        } catch (Throwable $e) {
            $resultado = false;
            $errorSql = $e->getMessage();
        }
        if ($resultado === false) {
            if ($errorSql === '' && method_exists($this->db, 'error')) {
                $err = $this->db->error();
                $errorSql = is_array($err) && !empty($err['message']) ? $err['message'] : 'sin detalle';
            }
            throw new RuntimeException(__CLASS__ . ": $etiqueta no se aplicó: $errorSql");
        }
        return $resultado;
    }

    public function down()
    {
        log_message('info', __METHOD__ . ': sin cambios (down no destructivo).');
    }
}
