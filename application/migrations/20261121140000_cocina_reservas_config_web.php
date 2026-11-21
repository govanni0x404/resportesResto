<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261121140000
 * Timestamp: 2026-11-21 14:00:00
 *
 * v1.29.0 (PRUEBA, rama prueba/pantalla-cocina), pedido del usuario:
 * - configuracion.usar_cocina_web           la pantalla de cocina se activa o no en Configuraciones (apagada al crearla)
 * - configuracion.usar_reservas_web         las reservas se activan o no en Configuraciones (apagadas al crearlas)
 * - configuracion.minutos_bloqueo_reserva_web  minutos antes de la hora de una reserva en que su mesa queda bloqueada
 *                                           para pedidos ("Bloqueada por reserva"). 60 por defecto.
 * - cocina_toma_web: quién tomó cada comanda en la pantalla de cocina ("Siendo preparado por ...").
 *
 * Nombres con "_web": el POS de escritorio no los usa. Idempotente (IF NOT EXISTS). Funciona en PostgreSQL 10.
 * No se edita la 20261121130000 (ya pudo correr en alguna base): esto va en una nueva (regla 4).
 */
class Migration_cocina_reservas_config_web extends CI_Migration
{
    public function up()
    {
        if (!$this->existe('configuracion', 'id_configuracion')) {
            throw new RuntimeException(__METHOD__ . ': falta la tabla configuracion. Se reintenta en la próxima ejecución.');
        }
        $this->ejecutar("ALTER TABLE public.configuracion ADD COLUMN IF NOT EXISTS usar_cocina_web boolean DEFAULT false", 'ADD COLUMN configuracion.usar_cocina_web');
        $this->ejecutar("ALTER TABLE public.configuracion ADD COLUMN IF NOT EXISTS usar_reservas_web boolean DEFAULT false", 'ADD COLUMN configuracion.usar_reservas_web');
        $this->ejecutar("ALTER TABLE public.configuracion ADD COLUMN IF NOT EXISTS minutos_bloqueo_reserva_web integer DEFAULT 60", 'ADD COLUMN configuracion.minutos_bloqueo_reserva_web');
        $this->ejecutar("CREATE TABLE IF NOT EXISTS public.cocina_toma_web (
            origen char(1) NOT NULL,
            id_detalle integer NOT NULL,
            id_usuario integer NOT NULL DEFAULT 0,
            nombre varchar(60) NOT NULL DEFAULT '',
            desde timestamp with time zone NOT NULL DEFAULT now(),
            CONSTRAINT cocina_toma_web_pk PRIMARY KEY (origen, id_detalle)
        )", 'CREATE TABLE cocina_toma_web');
        // Regla 7: comprobar en el catálogo antes de darse por aplicada
        foreach (array('configuracion' => 'minutos_bloqueo_reserva_web', 'cocina_toma_web' => 'nombre') as $tabla => $col) {
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
