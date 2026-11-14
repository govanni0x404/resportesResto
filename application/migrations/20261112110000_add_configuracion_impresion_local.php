<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261112110000
 * Timestamp: 2026-11-12 11:00:00
 *
 * configuracion.impresion_local_caja: la Caja imprime boletas, facturas y notas de crédito en el equipo del cajero, por
 * el agente de impresión local (http://localhost:5050, como mipdv_web), en el destino de Configuraciones > Impresora
 * para cierre de caja. Sin marcar (o sin la columna) la Caja imprime como antes (agente por internet, servidor o
 * navegador).
 *
 * - ADD COLUMN IF NOT EXISTS sin DEFAULT (NULL = desmarcado): no reescribe la tabla.
 * - Si la base no tiene la tabla configuracion no hay nada que agregar.
 */
class Migration_add_configuracion_impresion_local extends CI_Migration
{
    public function up()
    {
        if (!$this->existe('configuracion', 'id_configuracion')) {
            log_message('info', __METHOD__ . ': sin tabla configuracion, nada que agregar.');
            return;
        }
        $this->ejecutar('ALTER TABLE public.configuracion ADD COLUMN IF NOT EXISTS impresion_local_caja boolean', 'ADD COLUMN configuracion.impresion_local_caja');
        $this->ejecutar("COMMENT ON COLUMN public.configuracion.impresion_local_caja IS 'Caja web: documentos por el agente de impresion local del equipo'",
            'COMMENT configuracion.impresion_local_caja');

        // Regla 7: comprobar en el catálogo antes de darse por aplicada.
        if (!$this->existe('configuracion', 'impresion_local_caja')) {
            throw new RuntimeException(
                __METHOD__ . ': falta configuracion.impresion_local_caja después de crearla. ' .
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
        // No destructivo a propósito: se perdería la elección de cada empresa. Si hiciera falta, va en una migración nueva.
        log_message('info', __METHOD__ . ': sin cambios (down no destructivo).');
    }
}
