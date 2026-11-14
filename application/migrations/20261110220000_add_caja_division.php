<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261110220000
 * Timestamp: 2026-11-10 22:00:00
 *
 * Caja web > Dividir cuenta: lo armado para dividir la cuenta de una mesa (comensales y sus productos, partes iguales y
 * lo ya cobrado de cada parte), guardado en la base para que se vea desde cualquier caja.
 *
 * - Una fila por pedido abierto de la mesa (cotizacion.id_cotizacion). `datos` es el JSON de la división y `version` sube
 *   en cada cambio: si dos cajas la cambian a la vez, la segunda recibe la versión nueva en vez de pisarla.
 * - Tabla nueva: CREATE TABLE IF NOT EXISTS no toma locks sobre tablas existentes (Regla 6). Sin claves foráneas a
 *   propósito: no se toca ni se bloquea cotizacion ni mesa.
 * - El código no asume que esta migración ya corrió: Mdivisioncuenta pregunta al catálogo (to_regclass) y, si falta la
 *   tabla, la Caja guarda la división solo en ese computador, como antes.
 */
class Migration_add_caja_division extends CI_Migration
{
    public function up()
    {
        $this->ejecutar(<<<'SQL'
CREATE TABLE IF NOT EXISTS public.caja_division (
    id_cotizacion integer PRIMARY KEY,
    id_mesa integer NOT NULL DEFAULT 0,
    datos jsonb NOT NULL,
    version integer NOT NULL DEFAULT 1,
    id_usuario integer NOT NULL DEFAULT 0,
    fecha timestamp without time zone NOT NULL
)
SQL
            , 'CREATE caja_division');

        $this->ejecutar('CREATE INDEX IF NOT EXISTS ix_caja_division_fecha ON public.caja_division (fecha)',
            'INDEX ix_caja_division_fecha');

        $this->ejecutar("COMMENT ON TABLE public.caja_division IS 'Caja web: Dividir cuenta de una mesa (comensales, partes iguales y lo cobrado), una fila por pedido abierto'",
            'COMMENT caja_division');

        // Regla 7: comprobar en el catálogo antes de darse por aplicada.
        $q = $this->db->query(
            "SELECT (to_regclass('public.caja_division') IS NOT NULL)::int
                  + (to_regclass('public.ix_caja_division_fecha') IS NOT NULL)::int AS n");
        $fila = ($q && method_exists($q, 'row')) ? $q->row() : null;
        if (!$fila || (int) $fila->n !== 2) {
            throw new RuntimeException(
                __METHOD__ . ': falta la tabla o el índice de Dividir cuenta después de crearlos. ' .
                'No se marca como aplicada: se reintenta en la próxima ejecución.');
        }
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
        // No destructivo a propósito: borrar la tabla perdería las divisiones en curso de las mesas abiertas.
        // Si hiciera falta quitarla, va en una migración nueva.
        log_message('info', __METHOD__ . ': sin cambios (down no destructivo).');
    }
}
