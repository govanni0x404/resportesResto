<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261116120000
 * Timestamp: 2026-11-16 12:00:00
 *
 * configuracion.documentos_en_navegador: la Caja imprime boletas, facturas y precuentas con el diseño de la página
 * (views/caja/ticket.php y precuenta.php: letra grande y timbre como imagen), por el navegador con impresión silenciosa
 * (acceso directo "Caja Web" de instaladores/crear_acceso_caja.bat), como antes del agente de impresión. Las comandas
 * siguen por el agente, cada una en la impresora de su destino. Pedido del usuario (16-11-2026): el diseño del agente
 * no se parece al de antes.
 *
 * - Sin DEFAULT: NULL = apagada (documentos por el agente, como hasta ahora).
 * - ADD COLUMN IF NOT EXISTS sin DEFAULT: no reescribe la tabla. El código no asume que corrió (sin la columna = apagada).
 */
class Migration_add_configuracion_documentos_en_navegador extends CI_Migration
{
    public function up()
    {
        $this->ejecutar('ALTER TABLE public.configuracion ADD COLUMN IF NOT EXISTS documentos_en_navegador boolean',
            'ADD COLUMN configuracion.documentos_en_navegador');
        $this->ejecutar("COMMENT ON COLUMN public.configuracion.documentos_en_navegador IS 'Caja web: boletas y precuentas con el diseño de la página, por el navegador (impresión silenciosa)'",
            'COMMENT configuracion.documentos_en_navegador');

        // Regla 7: comprobar en el catálogo antes de darse por aplicada.
        if (!$this->existe('configuracion', 'documentos_en_navegador')) {
            throw new RuntimeException(__METHOD__ . ': falta configuracion.documentos_en_navegador después de crearla. ' .
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
