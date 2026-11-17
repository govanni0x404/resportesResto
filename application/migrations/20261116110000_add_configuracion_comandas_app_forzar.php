<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261116110000
 * Timestamp: 2026-11-16 11:00:00
 *
 * configuracion.imprimir_comandas_app_forzar: la Caja web imprime también los pedidos de la app de garzones que el
 * celular manda con "imprimir": 0. La API2 (recibirNuevaCotizacion.php) graba esas líneas con impreso = true y SIN
 * fecha_hora_impresion: nadie las imprimió. Hay celulares configurados así (ej. D1W7X4, 16-11-2026) y la comanda no
 * salía en cocina. Solo actúa junto con imprimir_comandas_app.
 *
 * - Sin DEFAULT: NULL = apagada. Ojo: si un celular imprime él mismo (Bluetooth), con esto saldría dos veces.
 * - ADD COLUMN IF NOT EXISTS sin DEFAULT: no reescribe la tabla. El código no asume que corrió (sin la columna = apagada).
 */
class Migration_add_configuracion_comandas_app_forzar extends CI_Migration
{
    public function up()
    {
        $this->ejecutar('ALTER TABLE public.configuracion ADD COLUMN IF NOT EXISTS imprimir_comandas_app_forzar boolean',
            'ADD COLUMN configuracion.imprimir_comandas_app_forzar');
        $this->ejecutar("COMMENT ON COLUMN public.configuracion.imprimir_comandas_app_forzar IS 'Caja web: imprime también los pedidos de la app que el celular manda sin pedir impresión'",
            'COMMENT configuracion.imprimir_comandas_app_forzar');

        // Regla 7: comprobar en el catálogo antes de darse por aplicada.
        if (!$this->existe('configuracion', 'imprimir_comandas_app_forzar')) {
            throw new RuntimeException(__METHOD__ . ': falta configuracion.imprimir_comandas_app_forzar después de crearla. ' .
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
