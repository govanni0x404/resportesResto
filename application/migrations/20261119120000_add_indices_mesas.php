<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261119120000
 * Timestamp: 2026-11-19 12:00:00
 *
 * Mesas lentas (pedido del usuario, 19-11-2026): al entrar a "Mesas y recintos" la grilla tardaba ~3 s en salir.
 * Mcaja::mesas() busca el pedido abierto de cada mesa y la base no tenía índices para eso: por cada mesa recorría las
 * ~300.000 cotizaciones y, para las mesas sin pedido marcado, también detalle_cotizacion entero.
 *
 * - ix_cotizacion_abierta_mesa: solo las cotizaciones sin pagar (unas cientas), por mesa. Chico; se crea normal.
 * - ix_detalle_cotizacion_cotizacion: los productos de cada cotización. Es una tabla grande y la usa también el POS de
 *   escritorio en el local: se crea CONCURRENTLY para no bloquear las ventas mientras se arma (no corre en transacción:
 *   MigracionesResto no abre una). Si una creación anterior quedó a medias (índice inválido), se borra y se rehace.
 *
 * Solo índices: no cambia datos ni columnas. Idempotente (IF NOT EXISTS).
 */
class Migration_add_indices_mesas extends CI_Migration
{
    public function up()
    {
        if ($this->existeTabla('cotizacion')) {
            $this->ejecutar('CREATE INDEX IF NOT EXISTS ix_cotizacion_abierta_mesa ON public.cotizacion (id_mesa, id_cotizacion DESC)
                WHERE pagado IS NOT TRUE', 'INDEX cotizacion abiertas por mesa');
            $this->exigirValido('ix_cotizacion_abierta_mesa');
        }
        if ($this->existeTabla('detalle_cotizacion')) {
            if ($this->indice('ix_detalle_cotizacion_cotizacion') === false) {
                $this->ejecutar('DROP INDEX CONCURRENTLY IF EXISTS public.ix_detalle_cotizacion_cotizacion', 'DROP índice inválido de detalle_cotizacion');
            }
            $this->ejecutar('CREATE INDEX CONCURRENTLY IF NOT EXISTS ix_detalle_cotizacion_cotizacion ON public.detalle_cotizacion (id_cotizacion)',
                'INDEX detalle_cotizacion.id_cotizacion');
            $this->exigirValido('ix_detalle_cotizacion_cotizacion');
        }
        // Sin esas tablas (return legítimo): el cliente no tiene mesas ni pedidos.
    }

    /** true = existe y es válido; false = existe pero quedó inválido; null = no existe. */
    private function indice($nombre)
    {
        $q = $this->db->query("SELECT i.indisvalid AS ok FROM pg_class c JOIN pg_index i ON i.indexrelid = c.oid
            JOIN pg_namespace n ON n.oid = c.relnamespace WHERE n.nspname = 'public' AND c.relname = ?", array($nombre));
        $f = ($q && method_exists($q, 'row')) ? $q->row() : null;
        if (!$f) return null;
        return $f->ok === true || $f->ok === 't';
    }

    /** Regla 7: comprobar en el catálogo antes de darse por aplicada. */
    private function exigirValido($nombre)
    {
        if ($this->indice($nombre) !== true) {
            throw new RuntimeException(__METHOD__ . ": el índice $nombre no quedó creado y válido. " .
                'No se marca como aplicada: se reintenta en la próxima ejecución.');
        }
    }

    private function existeTabla($tabla)
    {
        $q = $this->db->query("SELECT count(*) AS n FROM information_schema.tables WHERE table_schema = 'public' AND table_name = ?", array($tabla));
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
        // Los índices no estorban y los usa también el POS de escritorio: no se borran.
        log_message('info', __METHOD__ . ': sin cambios (down no destructivo).');
    }
}
