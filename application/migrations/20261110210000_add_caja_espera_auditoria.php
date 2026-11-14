<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261110210000
 * Timestamp: 2026-11-10 21:00:00
 *
 * Caja web: ventas en espera y bitácora de auditoría.
 *
 * - caja_venta_espera: la venta directa que el cajero deja en espera (Opciones > Poner en espera), o que la caja deja
 *   sola al salir por el menú o al recargar la página. Se recupera (vuelve a la venta) o se anula con justificación.
 *   `datos` guarda el carro tal como estaba (productos, opciones, cliente, descuento, documento, lista de precios).
 * - caja_auditoria: un evento por fila (espera creada/recuperada/anulada, productos de mesa omitidos al salir o
 *   recargar, venta vaciada, gaveta abierta). Lo leen Consultas > Auditoría de caja y Anulación de ventas.
 * - `uid` (lo genera la pantalla) evita duplicados cuando el navegador manda dos veces lo mismo al cerrar la página.
 * - Tablas nuevas: CREATE TABLE IF NOT EXISTS no toma locks sobre tablas existentes (Regla 6). Sin claves foráneas a
 *   propósito: no se toca ni se bloquea caja, venta ni usuario.
 * - El código no asume que esta migración ya corrió: Mventasespera pregunta al catálogo (to_regclass) y, si faltan
 *   las tablas, la Caja funciona como antes, sin ventas en espera.
 */
class Migration_add_caja_espera_auditoria extends CI_Migration
{
    public function up()
    {
        $this->ejecutar(<<<'SQL'
CREATE TABLE IF NOT EXISTS public.caja_venta_espera (
    id_venta_espera serial PRIMARY KEY,
    uid varchar(48) NOT NULL,
    nombre varchar(80) NOT NULL,
    estado varchar(12) NOT NULL DEFAULT 'espera',
    origen varchar(12) NOT NULL DEFAULT 'manual',
    id_caja integer NOT NULL DEFAULT 0,
    id_usuario integer NOT NULL DEFAULT 0,
    fecha timestamp without time zone NOT NULL,
    total integer NOT NULL DEFAULT 0,
    unidades numeric(12,3) NOT NULL DEFAULT 0,
    lineas integer NOT NULL DEFAULT 0,
    datos jsonb NOT NULL,
    id_caja_cierre integer,
    id_usuario_cierre integer,
    fecha_cierre timestamp without time zone,
    motivo_anulacion varchar(250)
)
SQL
            , 'CREATE caja_venta_espera');

        $this->ejecutar('CREATE UNIQUE INDEX IF NOT EXISTS ux_caja_venta_espera_uid ON public.caja_venta_espera (uid)',
            'INDEX ux_caja_venta_espera_uid');
        $this->ejecutar('CREATE INDEX IF NOT EXISTS ix_caja_venta_espera_estado ON public.caja_venta_espera (estado, fecha)',
            'INDEX ix_caja_venta_espera_estado');
        $this->ejecutar('CREATE INDEX IF NOT EXISTS ix_caja_venta_espera_caja ON public.caja_venta_espera (id_caja)',
            'INDEX ix_caja_venta_espera_caja');

        $this->ejecutar(<<<'SQL'
CREATE TABLE IF NOT EXISTS public.caja_auditoria (
    id_caja_auditoria serial PRIMARY KEY,
    uid varchar(48),
    fecha timestamp without time zone NOT NULL,
    id_caja integer NOT NULL DEFAULT 0,
    id_usuario integer NOT NULL DEFAULT 0,
    evento varchar(30) NOT NULL,
    origen varchar(20),
    id_mesa integer,
    id_venta_espera integer,
    monto integer NOT NULL DEFAULT 0,
    unidades numeric(12,3) NOT NULL DEFAULT 0,
    detalle jsonb,
    motivo varchar(250)
)
SQL
            , 'CREATE caja_auditoria');

        $this->ejecutar('CREATE UNIQUE INDEX IF NOT EXISTS ux_caja_auditoria_uid ON public.caja_auditoria (uid)',
            'INDEX ux_caja_auditoria_uid');
        $this->ejecutar('CREATE INDEX IF NOT EXISTS ix_caja_auditoria_caja ON public.caja_auditoria (id_caja, fecha)',
            'INDEX ix_caja_auditoria_caja');
        $this->ejecutar('CREATE INDEX IF NOT EXISTS ix_caja_auditoria_fecha ON public.caja_auditoria (fecha)',
            'INDEX ix_caja_auditoria_fecha');

        $this->ejecutar("COMMENT ON TABLE public.caja_venta_espera IS 'Caja web: ventas directas dejadas en espera (Opciones > Poner en espera, o automáticas al salir o recargar)'",
            'COMMENT caja_venta_espera');
        $this->ejecutar("COMMENT ON TABLE public.caja_auditoria IS 'Caja web: bitácora de auditoría (esperas, productos omitidos, ventas vaciadas, gaveta)'",
            'COMMENT caja_auditoria');

        // Regla 7: comprobar en el catálogo antes de darse por aplicada.
        $q = $this->db->query(
            "SELECT (to_regclass('public.caja_venta_espera') IS NOT NULL)::int
                  + (to_regclass('public.caja_auditoria') IS NOT NULL)::int
                  + (to_regclass('public.ux_caja_venta_espera_uid') IS NOT NULL)::int
                  + (to_regclass('public.ux_caja_auditoria_uid') IS NOT NULL)::int AS n");
        $fila = ($q && method_exists($q, 'row')) ? $q->row() : null;
        if (!$fila || (int) $fila->n !== 4) {
            throw new RuntimeException(
                __METHOD__ . ': faltan tablas o índices de ventas en espera / auditoría después de crearlos. ' .
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
        // No destructivo a propósito: borrar las tablas perdería las ventas en espera y la bitácora de auditoría.
        // Si hiciera falta quitarlas, va en una migración nueva.
        log_message('info', __METHOD__ . ': sin cambios (down no destructivo).');
    }
}
