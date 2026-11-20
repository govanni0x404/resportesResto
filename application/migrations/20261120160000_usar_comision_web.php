<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261120160000
 * Timestamp: 2026-11-20 16:00:00
 *
 * Comisión opcional en el precio de los productos (pedido del usuario, 20-11-2026, v1.24.1). Configuraciones >
 * "Comisión en el precio de los productos": apagada, el recuadro de comisión no sale (Productos y Lista de Precios) y
 * se graba siempre 0; encendida, se graba lo que se escriba.
 *
 * - configuracion.usar_comision_web, apagada por defecto.
 * - Las bases que YA usan comisiones (algún precio con comisión distinta de 0) quedan con la opción encendida: así la
 *   migración no les esconde ni les cambia nada (no se tocan los precios ni sus comisiones).
 *
 * Nombre con "_web": la tabla la comparte el POS de escritorio, que no la usa. Tabla de una fila: el DEFAULT no pesa.
 * Idempotente (IF NOT EXISTS). Funciona en PostgreSQL 10 (VPS). El código no asume que corrió (Mproductos::comisionHabilitada).
 */
class Migration_usar_comision_web extends CI_Migration
{
    public function up()
    {
        if (!$this->existe('configuracion', 'id_configuracion')) {
            throw new RuntimeException(__METHOD__ . ': falta la tabla configuracion. Se reintenta en la próxima ejecución.');
        }
        $this->ejecutar('ALTER TABLE public.configuracion ADD COLUMN IF NOT EXISTS usar_comision_web boolean DEFAULT false',
            'ADD COLUMN configuracion.usar_comision_web');
        if ($this->existe('detalle_lprecio', 'monto_comision')) {
            $this->ejecutar('UPDATE public.configuracion SET usar_comision_web = true
                WHERE usar_comision_web IS NOT TRUE
                  AND EXISTS (SELECT 1 FROM public.detalle_lprecio WHERE COALESCE(monto_comision, 0) <> 0)',
                'UPDATE usar_comision_web en bases que ya usan comisiones');
        }

        // Regla 7: comprobar en el catálogo antes de darse por aplicada
        if (!$this->existe('configuracion', 'usar_comision_web')) {
            throw new RuntimeException(__METHOD__ . ': falta configuracion.usar_comision_web después de crearla. ' .
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
