<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261120140000
 * Timestamp: 2026-11-20 14:00:00
 *
 * Opciones de propina (pedido del usuario, 20-11-2026, v1.23.0), en Configuraciones > Propinas:
 * - configuracion.usar_propina_web: apagada = la empresa no usa propina; no se pregunta al cobrar ni sale en la
 *   precuenta ni en ningún documento.
 * - configuracion.propina_mesas_web: propina al cobrar las mesas (como ya existía "Propina en venta directa").
 * Las dos parten encendidas: así la Caja sigue igual que antes hasta que alguien las cambie.
 *
 * Nombres con "_web": la tabla la comparte el POS de escritorio, que no las usa. Tabla de una fila: el DEFAULT no pesa.
 * Idempotente (IF NOT EXISTS). Funciona en PostgreSQL 10 (VPS). El código no asume que corrió (Mcaja::configuracion).
 */
class Migration_opciones_propina extends CI_Migration
{
    public function up()
    {
        if (!$this->existe('configuracion', 'id_configuracion')) {
            throw new RuntimeException(__METHOD__ . ': falta la tabla configuracion. Se reintenta en la próxima ejecución.');
        }
        $this->ejecutar('ALTER TABLE public.configuracion ADD COLUMN IF NOT EXISTS usar_propina_web boolean DEFAULT true',
            'ADD COLUMN configuracion.usar_propina_web');
        $this->ejecutar('ALTER TABLE public.configuracion ADD COLUMN IF NOT EXISTS propina_mesas_web boolean DEFAULT true',
            'ADD COLUMN configuracion.propina_mesas_web');

        // Regla 7: comprobar en el catálogo antes de darse por aplicada
        foreach (array('usar_propina_web', 'propina_mesas_web') as $col) {
            if (!$this->existe('configuracion', $col)) {
                throw new RuntimeException(__METHOD__ . ": falta configuracion.$col después de crearla. " .
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
