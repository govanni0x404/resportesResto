<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261121100000
 * Timestamp: 2026-11-21 10:00:00
 *
 * Clave de administrador por tipo de anulación (pedido del usuario, 21-11-2026, v1.25.3). Antes una sola casilla
 * ("Requerir clave admin para anulacion", admin_anulacion_eliminacion, la del POS de escritorio) valía para todo.
 * Ahora la Caja web pregunta por separado, y con la casilla marcada la pide a todos (también a un administrador):
 *
 * - admin_anular_venta_web          anular una boleta / factura / comprobante
 * - admin_devolucion_web            devolución (nota de crédito)
 * - admin_anular_espera_web         anular una venta en espera
 * - admin_anular_producto_mesa_web  anular un producto ya enviado a la mesa
 * - admin_anular_pedido_mesa_web    anular el pedido completo de la mesa
 *
 * Valor inicial: las tres primeras toman el de admin_anulacion_eliminacion (la Caja sigue igual); las de mesa quedan
 * ENCENDIDAS (antes a un cajero siempre se le pedía la clave para anular en la mesa). Solo se copian al crear la
 * columna: volver a correr la migración no pisa lo que se eligió después.
 *
 * Nombre con "_web": la tabla la comparte el POS de escritorio, que no las usa. Tabla de una fila.
 * Idempotente (IF NOT EXISTS). Funciona en PostgreSQL 10 (VPS). El código no asume que corrió (Mcaja::configuracion).
 */
class Migration_clave_anulaciones_web extends CI_Migration
{
    public function up()
    {
        if (!$this->existe('configuracion', 'id_configuracion')) {
            throw new RuntimeException(__METHOD__ . ': falta la tabla configuracion. Se reintenta en la próxima ejecución.');
        }
        $general = $this->existe('configuracion', 'admin_anulacion_eliminacion')
            ? 'COALESCE(admin_anulacion_eliminacion, false)' : 'false';
        $columnas = array(
            'admin_anular_venta_web' => $general,
            'admin_devolucion_web' => $general,
            'admin_anular_espera_web' => $general,
            'admin_anular_producto_mesa_web' => 'true',
            'admin_anular_pedido_mesa_web' => 'true',
        );
        foreach ($columnas as $col => $inicial) {
            $nueva = !$this->existe('configuracion', $col);
            $this->ejecutar("ALTER TABLE public.configuracion ADD COLUMN IF NOT EXISTS $col boolean DEFAULT false",
                "ADD COLUMN configuracion.$col");
            if ($nueva) {
                $this->ejecutar("UPDATE public.configuracion SET $col = $inicial", "valor inicial de configuracion.$col");
            }
            // Regla 7: comprobar en el catálogo antes de darse por aplicada
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
