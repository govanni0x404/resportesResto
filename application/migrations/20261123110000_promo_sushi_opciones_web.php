<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261123110000
 * Timestamp: 2026-11-23 11:00:00
 *
 * v1.32.0 (pedidos del usuario en cambios.md): más opciones para las promos de sushi.
 *
 * promo_sushi_web:
 *  - fija_web               boolean  la promo se arma siempre igual: en la Caja no se pregunta (se arma la primera vez
 *                                    y se marca "Dejarla fija")
 *  - armado_fijo_web        text     lo armado (JSON {grupos, extras, salsas}) que se usa cuando es fija
 *  - precio_envoltorio_web  boolean  el precio de la promo sale del envoltorio elegido (el más caro si hay varios)
 *  - max_envoltorios_web    integer  envoltorios por roll / grupo (1 = como antes)
 *  - max_extras_web         integer  máximo de extras a elegir en total (0 = sin límite)
 *  - max_salsas_web         integer  máximo de salsas a elegir en total (0 = sin límite)
 * promo_sushi_opcion_web:
 *  - precio_promo_web       integer  precio de la promo con ese envoltorio (solo con precio_envoltorio_web)
 *  - tipo: se suma 'salsa' (las salsas van aparte de los extras)
 * configuracion (Configuraciones > Promos de sushi):
 *  - sushi_por_pieza_web    boolean  armar promos por pieza (pocos locales lo usan; queda encendido si ya tenían alguna)
 *  - sushi_bases_web        boolean  preguntar la base (queda encendido si alguna promo ya tenía bases)
 *  - sushi_salsas_web       boolean  sección de salsas aparte de los extras (parte apagado)
 *
 * Idempotente (IF NOT EXISTS). Funciona en PostgreSQL 10 (VPS). El código no asume que corrió (revisa las columnas).
 */
class Migration_promo_sushi_opciones_web extends CI_Migration
{
    public function up()
    {
        if (!$this->existe('promo_sushi_web', 'id_producto') || !$this->tabla('configuracion')) {
            throw new RuntimeException(__METHOD__ . ': faltan las tablas de las promos de sushi o configuracion. Se reintenta en la próxima ejecución.');
        }
        foreach (array(
            'fija_web boolean NOT NULL DEFAULT false',
            'armado_fijo_web text',
            'precio_envoltorio_web boolean NOT NULL DEFAULT false',
            'max_envoltorios_web integer NOT NULL DEFAULT 1',
            'max_extras_web integer NOT NULL DEFAULT 0',
            'max_salsas_web integer NOT NULL DEFAULT 0',
        ) as $col) {
            $this->ejecutar("ALTER TABLE public.promo_sushi_web ADD COLUMN IF NOT EXISTS $col", 'ADD COLUMN promo_sushi_web.' . strtok($col, ' '));
        }
        $this->ejecutar('ALTER TABLE public.promo_sushi_opcion_web ADD COLUMN IF NOT EXISTS precio_promo_web integer NOT NULL DEFAULT 0',
            'ADD COLUMN promo_sushi_opcion_web.precio_promo_web');
        // Tipo 'salsa': se cambia la regla de los tipos (el nombre es el que PostgreSQL le dio al crear la tabla)
        $this->ejecutar('ALTER TABLE public.promo_sushi_opcion_web DROP CONSTRAINT IF EXISTS promo_sushi_opcion_web_tipo_check',
            'DROP CONSTRAINT promo_sushi_opcion_web_tipo_check');
        $this->ejecutar("ALTER TABLE public.promo_sushi_opcion_web ADD CONSTRAINT promo_sushi_opcion_web_tipo_check
            CHECK (tipo IN ('base', 'relleno', 'envoltorio', 'extra', 'salsa'))", 'ADD CONSTRAINT promo_sushi_opcion_web_tipo_check');

        // Configuraciones: al crearlas quedan como el local ya las usaba
        $conModo = $this->existe('promo_sushi_web', 'modo');
        $nuevas = array(
            'sushi_por_pieza_web' => $conModo ? "EXISTS (SELECT 1 FROM public.promo_sushi_web WHERE modo = 'pieza')" : 'EXISTS (SELECT 1 FROM public.promo_sushi_web)',
            'sushi_bases_web' => "EXISTS (SELECT 1 FROM public.promo_sushi_opcion_web WHERE tipo = 'base')",
            'sushi_salsas_web' => 'false',
        );
        foreach ($nuevas as $col => $valor) {
            if ($this->existe('configuracion', $col)) continue;
            $this->ejecutar("ALTER TABLE public.configuracion ADD COLUMN IF NOT EXISTS $col boolean NOT NULL DEFAULT false", 'ADD COLUMN configuracion.' . $col);
            $this->ejecutar("UPDATE public.configuracion SET $col = $valor", 'UPDATE configuracion.' . $col);
        }

        // Regla 7: comprobar en el catálogo antes de darse por aplicada
        foreach (array(array('promo_sushi_web', 'fija_web'), array('promo_sushi_web', 'max_salsas_web'), array('promo_sushi_opcion_web', 'precio_promo_web'),
            array('configuracion', 'sushi_por_pieza_web'), array('configuracion', 'sushi_salsas_web')) as $tc) {
            if (!$this->existe($tc[0], $tc[1])) {
                throw new RuntimeException(__METHOD__ . ': falta ' . $tc[0] . '.' . $tc[1] . ' después de crearla. ' .
                    'No se marca como aplicada: se reintenta en la próxima ejecución.');
            }
        }
    }

    private function tabla($tabla)
    {
        $q = $this->db->query("SELECT count(*) AS n FROM information_schema.tables WHERE table_schema = 'public' AND table_name = ?", array($tabla));
        $fila = ($q && method_exists($q, 'row')) ? $q->row() : null;
        return $fila && (int) $fila->n > 0;
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
