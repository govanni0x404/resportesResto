<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261119130000
 * Timestamp: 2026-11-19 13:00:00
 *
 * Índices para los reportes (auditoría del 19-11-2026): Cierres de caja, Auditoría de caja, Ventas por producto,
 * Detalle de operaciones y el Dashboard recorrían detalle_venta y cotizacion enteras (~680.000 filas) porque no había
 * índice por venta, por caja ni por fecha. Con un cliente nuevo no se nota al principio; con los meses sí.
 *
 * - ix_detalle_venta_venta: detalle_venta (id_venta)
 * - ix_venta_fecha: venta (fecha)
 * - ix_cotizacion_venta: cotizacion (id_venta)
 * - ix_detalle_cotizacion_caja: detalle_cotizacion (id_caja), si la columna existe
 *
 * Todas CONCURRENTLY: no bloquean las ventas (la base la usa también el POS de escritorio) y MigracionesResto no abre
 * transacción. Si una creación anterior quedó a medias (índice inválido) se borra y se rehace. Solo índices: no cambia
 * datos. Idempotente (IF NOT EXISTS). Sin la tabla o la columna, ese índice se salta (return legítimo de esa parte).
 */
class Migration_add_indices_reportes extends CI_Migration
{
    public function up()
    {
        $indices = array(
            array('ix_detalle_venta_venta', 'detalle_venta', 'id_venta'),
            array('ix_venta_fecha', 'venta', 'fecha'),
            array('ix_cotizacion_venta', 'cotizacion', 'id_venta'),
            array('ix_detalle_cotizacion_caja', 'detalle_cotizacion', 'id_caja'),
        );
        foreach ($indices as $ix) {
            list($nombre, $tabla, $columna) = $ix;
            if (!$this->existeColumna($tabla, $columna)) continue;
            if ($this->indice($nombre) === false) {
                $this->ejecutar("DROP INDEX CONCURRENTLY IF EXISTS public.$nombre", "DROP índice inválido $nombre");
            }
            $this->ejecutar("CREATE INDEX CONCURRENTLY IF NOT EXISTS $nombre ON public.$tabla ($columna)", "INDEX $tabla.$columna");
            $this->exigirValido($nombre);
        }
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

    private function existeColumna($tabla, $columna)
    {
        $q = $this->db->query("SELECT count(*) AS n FROM information_schema.columns WHERE table_schema = 'public' AND table_name = ? AND column_name = ?", array($tabla, $columna));
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
