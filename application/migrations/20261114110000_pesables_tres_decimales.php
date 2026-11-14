<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261114110000
 * Timestamp: 2026-11-14 11:00:00
 *
 * Pesables con 3 decimales (QA 14-11-2026). La Caja web cobra los productos pesables con 3 decimales (0,125 kg),
 * pero detalle_venta.cantidad era numeric(10,2): se grababa 0,13, el total de la línea no cuadraba con la cantidad y
 * el stock se descontaba de más. consolidado_ventas.cantidad (lo llena el trigger f_addconsolidado con new.cantidad)
 * y stock.cantidad_entrada/cantidad_salida/saldo tenían el mismo límite.
 *
 * - Solo AMPLÍA la columna a numeric(12,3): no pierde datos (cabe todo lo de numeric(10,2)) y el POS puede seguir
 *   grabando con 2 decimales. Ninguna vista depende de estas columnas (revisado en pg_depend).
 * - Idempotente: si la columna ya tiene 3 decimales o más, no se toca.
 * - OJO: ALTER ... TYPE reescribe la tabla (detalle_venta ~200 MB en laToscana) con bloqueo exclusivo mientras dura.
 *   Correrla fuera del horario de atención. MigracionesResto pone lock_timeout: si la tabla está ocupada, falla sin
 *   marcarse y se reintenta.
 */
class Migration_pesables_tres_decimales extends CI_Migration
{
    private $columnas = array(
        'detalle_venta' => array('cantidad'),
        'consolidado_ventas' => array('cantidad'),
        'stock' => array('cantidad_entrada', 'cantidad_salida', 'saldo'),
    );

    public function up()
    {
        foreach ($this->columnas as $tabla => $cols) {
            foreach ($cols as $col) {
                $t = $this->tipo($tabla, $col);
                if ($t === null) {
                    log_message('info', __METHOD__ . ": sin $tabla.$col, nada que ampliar.");
                    continue;
                }
                if ($t->data_type !== 'numeric' || $t->numeric_scale === null || (int)$t->numeric_scale >= 3) continue;
                $this->ejecutar("ALTER TABLE public.$tabla ALTER COLUMN $col TYPE numeric(12,3)", "ALTER $tabla.$col numeric(12,3)");
            }
        }

        // Regla 7: comprobar en el catálogo antes de darse por aplicada.
        foreach ($this->columnas as $tabla => $cols) {
            foreach ($cols as $col) {
                $t = $this->tipo($tabla, $col);
                if ($t !== null && $t->data_type === 'numeric' && $t->numeric_scale !== null && (int)$t->numeric_scale < 3) {
                    throw new RuntimeException(__METHOD__ . ": $tabla.$col sigue con " . (int)$t->numeric_scale . ' decimales. ' .
                        'No se marca como aplicada: se reintenta en la próxima ejecución.');
                }
            }
        }
    }

    private function tipo($tabla, $columna)
    {
        $q = $this->db->query(
            "SELECT data_type, numeric_scale FROM information_schema.columns
              WHERE table_schema = 'public' AND table_name = ? AND column_name = ?",
            array($tabla, $columna));
        return ($q && method_exists($q, 'row')) ? $q->row() : null;
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
        // No se vuelve a 2 decimales: se perderían los gramos ya grabados.
        log_message('info', __METHOD__ . ': sin cambios (down no destructivo).');
    }
}
