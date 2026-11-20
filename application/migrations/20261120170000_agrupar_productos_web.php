<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261120170000
 * Timestamp: 2026-11-20 17:00:00
 *
 * Agrupar productos en la Caja (pedido del usuario, 20-11-2026, v1.24.8). Configuraciones > "Agrupar productos iguales
 * en la Caja": encendida (como siempre), dos pizzas van en una sola fila con cantidad 2; apagada, cada pizza va en su
 * propia fila, para ponerle a cada una su propio mensaje (ej. una sin cebolla y otra normal).
 *
 * - configuracion.agrupar_productos_web, ENCENDIDA por defecto: la Caja sigue igual que antes hasta que alguien la apague.
 *
 * Nombre con "_web": la tabla la comparte el POS de escritorio, que no la usa. Tabla de una fila: el DEFAULT no pesa.
 * Idempotente (IF NOT EXISTS). Funciona en PostgreSQL 10 (VPS). El código no asume que corrió (Mcaja::configuracion).
 */
class Migration_agrupar_productos_web extends CI_Migration
{
    public function up()
    {
        if (!$this->existe('configuracion', 'id_configuracion')) {
            throw new RuntimeException(__METHOD__ . ': falta la tabla configuracion. Se reintenta en la próxima ejecución.');
        }
        $this->ejecutar('ALTER TABLE public.configuracion ADD COLUMN IF NOT EXISTS agrupar_productos_web boolean DEFAULT true',
            'ADD COLUMN configuracion.agrupar_productos_web');

        // Regla 7: comprobar en el catálogo antes de darse por aplicada
        if (!$this->existe('configuracion', 'agrupar_productos_web')) {
            throw new RuntimeException(__METHOD__ . ': falta configuracion.agrupar_productos_web después de crearla. ' .
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
