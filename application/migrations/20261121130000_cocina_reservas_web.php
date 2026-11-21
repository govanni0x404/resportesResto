<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261121130000
 * Timestamp: 2026-11-21 13:00:00
 *
 * v1.28.0 (PRUEBA, rama prueba/pantalla-cocina): pantalla de cocina y reservas de mesas.
 *
 * - cocina_listo_web: qué líneas de pedido ya marcó "Listo" la pantalla de cocina (Cocina). No se tocan las columnas de
 *   cocina de detalle_cotizacion (estado_plato, fecha_salidadecocina): esas son del POS de escritorio.
 *     origen  'c' = detalle_cotizacion (mesas y delivery)  ·  'v' = detalle_venta (venta directa)
 * - reserva_web: reservas de mesas (Reservas). estado: pendiente, llego, cancelada, no_vino.
 *
 * Tablas nuevas con "_web": el POS de escritorio no las usa. Idempotente (IF NOT EXISTS). Funciona en PostgreSQL 10.
 */
class Migration_cocina_reservas_web extends CI_Migration
{
    public function up()
    {
        $this->ejecutar("CREATE TABLE IF NOT EXISTS public.cocina_listo_web (
            origen char(1) NOT NULL,
            id_detalle integer NOT NULL,
            listo timestamp with time zone NOT NULL DEFAULT now(),
            id_usuario integer NOT NULL DEFAULT 0,
            CONSTRAINT cocina_listo_web_pk PRIMARY KEY (origen, id_detalle)
        )", 'CREATE TABLE cocina_listo_web');
        $this->ejecutar("CREATE INDEX IF NOT EXISTS cocina_listo_web_listo_idx ON public.cocina_listo_web (listo)", 'índice cocina_listo_web.listo');
        $this->ejecutar("CREATE TABLE IF NOT EXISTS public.reserva_web (
            id_reserva serial PRIMARY KEY,
            fecha date NOT NULL,
            hora time NOT NULL,
            nombre varchar(80) NOT NULL,
            telefono varchar(30) NOT NULL DEFAULT '',
            personas integer NOT NULL DEFAULT 2,
            id_mesa integer NOT NULL DEFAULT 0,
            nota varchar(250) NOT NULL DEFAULT '',
            estado varchar(12) NOT NULL DEFAULT 'pendiente',
            id_usuario integer NOT NULL DEFAULT 0,
            creado timestamp with time zone NOT NULL DEFAULT now()
        )", 'CREATE TABLE reserva_web');
        $this->ejecutar("CREATE INDEX IF NOT EXISTS reserva_web_fecha_idx ON public.reserva_web (fecha, hora)", 'índice reserva_web.fecha');
        // Regla 7: comprobar en el catálogo antes de darse por aplicada
        foreach (array('cocina_listo_web' => 'listo', 'reserva_web' => 'estado') as $tabla => $col) {
            if (!$this->existe($tabla, $col)) {
                throw new RuntimeException(__METHOD__ . ": falta $tabla.$col después de crearla. Se reintenta en la próxima ejecución.");
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
