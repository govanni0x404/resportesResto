<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261112090000
 * Timestamp: 2026-11-12 09:00:00
 *
 * control_caja.id_sucursal_intranet: la sucursal de la intranet a la que pertenece cada caja (lista_cajas_autorizadas de
 * validarCliente). Con ella Control de cajas filtra por la sucursal elegida y los documentos de una caja toman los datos
 * de su sucursal (empresa_sucursal.id_sucursal_intranet).
 *
 * - ALTER TABLE: una sola vez aquí (Regla 6), columna sin DEFAULT: no reescribe la tabla.
 * - Las cajas que ya vinieron de la intranet (id_control_caja_web) guardaban esa sucursal en id_sucursal: se copia a la
 *   columna nueva solo donde está vacía. Las filas que no vienen de la intranet no se tocan.
 * - Índice simple (no único).
 * - El código no asume que esta migración ya corrió: sin la columna sigue usando control_caja.id_sucursal.
 */
class Migration_add_control_caja_sucursal_intranet extends CI_Migration
{
    public function up()
    {
        if (!$this->existe('control_caja', 'id_control_caja')) {
            // La tabla la crea 20261111190000; si aún no existe, se reintenta en la próxima ejecución
            throw new RuntimeException(__METHOD__ . ': falta la tabla control_caja (migración 20261111190000).');
        }
        $this->ejecutar('ALTER TABLE public.control_caja ADD COLUMN IF NOT EXISTS id_sucursal_intranet integer',
            'ADD COLUMN control_caja.id_sucursal_intranet');
        if ($this->existe('control_caja', 'id_sucursal') && $this->existe('control_caja', 'id_control_caja_web')) {
            $this->ejecutar('UPDATE public.control_caja SET id_sucursal_intranet = id_sucursal
                WHERE id_sucursal_intranet IS NULL AND COALESCE(id_control_caja_web, 0) > 0 AND COALESCE(id_sucursal, 0) > 0',
                'UPDATE control_caja.id_sucursal_intranet');
        }
        $this->ejecutar('CREATE INDEX IF NOT EXISTS ix_control_caja_sucursal_intranet ON public.control_caja (id_sucursal_intranet)',
            'INDEX ix_control_caja_sucursal_intranet');

        // Regla 7: comprobar en el catálogo antes de darse por aplicada.
        if (!$this->existe('control_caja', 'id_sucursal_intranet')) {
            throw new RuntimeException(
                __METHOD__ . ': falta control_caja.id_sucursal_intranet después de crearla. ' .
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
        // No destructivo a propósito: borrar la columna perdería a qué sucursal de la intranet pertenece cada caja.
        // Si hiciera falta quitarla, va en una migración nueva.
        log_message('info', __METHOD__ . ': sin cambios (down no destructivo).');
    }
}
