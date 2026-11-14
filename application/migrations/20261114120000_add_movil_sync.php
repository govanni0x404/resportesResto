<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261114120000
 * Timestamp: 2026-11-14 12:00:00
 *
 * movil_sync: lo que ya llegó desde la app móvil (cajas, ventas, movimientos y, desde hoy, pedidos a mesas), para que
 * reenviar lo mismo no lo duplique. Antes la tabla la creaba ApiMovil/subir dentro de la petición (Regla 6); queda
 * aquí con la misma definición y el código sigue funcionando si la base ya la tenía.
 *
 * - Tabla nueva, sin tocar tablas del POS. Idempotente (IF NOT EXISTS).
 */
class Migration_add_movil_sync extends CI_Migration
{
    public function up()
    {
        $this->ejecutar("CREATE TABLE IF NOT EXISTS public.movil_sync (
                clave character varying(100) NOT NULL,
                tipo character varying(20) NOT NULL,
                id_web integer NOT NULL DEFAULT 0,
                estado character varying(20) NOT NULL DEFAULT 'ok',
                fecha timestamp without time zone NOT NULL DEFAULT now(),
                CONSTRAINT movil_sync_pkey PRIMARY KEY (clave)
            )", 'CREATE TABLE movil_sync');
        $this->ejecutar("COMMENT ON TABLE public.movil_sync IS 'App móvil: lo ya recibido (caja, venta, movimiento, pedido a mesa) para no duplicarlo'",
            'COMMENT movil_sync');

        // Regla 7: comprobar en el catálogo antes de darse por aplicada.
        foreach (array('clave', 'tipo', 'id_web', 'estado', 'fecha') as $c) {
            if (!$this->existe('movil_sync', $c)) {
                throw new RuntimeException(__METHOD__ . ": falta movil_sync.$c después de crearla. " .
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
        // No destructivo: sin la tabla la app volvería a subir lo mismo y se duplicaría.
        log_message('info', __METHOD__ . ': sin cambios (down no destructivo).');
    }
}
