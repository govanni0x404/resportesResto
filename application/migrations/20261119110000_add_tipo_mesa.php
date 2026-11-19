<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261119110000
 * Timestamp: 2026-11-19 11:00:00
 *
 * Tipos de mesa (pedido del usuario, 19-11-2026): Mantención > Recintos > Tipos de mesa. Cada mesa tiene un tipo y el
 * tipo cambia el nombre con que sale en la Caja, la comanda y la precuenta. Ej: tipo "Normal" sin texto => "Mesa 1"
 * (como siempre); tipo "Delivery" con texto "D" => "Mesa D1"; tipo "Eventos" con texto "EVENT" y espacio =>
 * "Mesa EVENT 1". El nombre propio que se pone en el plano de la Caja (mesa_plano.nombre) sigue mandando.
 *
 * - tipo_mesa_web: nombre, texto antes del número (prefijo), si va un espacio entre el texto y el número, vigente.
 * - mesa.id_tipo_mesa_web: el tipo de cada mesa (NULL = sin tipo, nombre de siempre). Sin DEFAULT: no reescribe la tabla.
 *
 * Nombres con "_web": la base la comparte el POS de escritorio, que no conoce estos tipos (para él la mesa sigue igual).
 * Idempotente (IF NOT EXISTS). El código no asume que corrió: sin la tabla, las mesas se llaman como antes.
 */
class Migration_add_tipo_mesa extends CI_Migration
{
    public function up()
    {
        $this->ejecutar("CREATE TABLE IF NOT EXISTS public.tipo_mesa_web (
            id_tipo_mesa_web serial PRIMARY KEY,
            nombre character varying(40) NOT NULL,
            prefijo character varying(20) NOT NULL DEFAULT '',
            espacio boolean NOT NULL DEFAULT false,
            vigente boolean NOT NULL DEFAULT true,
            fecha_creacion timestamp with time zone NOT NULL DEFAULT now(),
            fecha_modificacion timestamp with time zone
        )", 'CREATE tipo_mesa_web');
        $this->ejecutar("COMMENT ON TABLE public.tipo_mesa_web IS 'Web: tipos de mesa; el prefijo cambia el nombre de la mesa en la Caja (Mesa D1)'",
            'COMMENT tipo_mesa_web');

        $exigidas = array(array('tipo_mesa_web', 'prefijo'), array('tipo_mesa_web', 'espacio'));
        if ($this->existe('mesa', 'id_mesa')) {
            $this->ejecutar('ALTER TABLE public.mesa ADD COLUMN IF NOT EXISTS id_tipo_mesa_web integer', 'ADD COLUMN mesa.id_tipo_mesa_web');
            $exigidas[] = array('mesa', 'id_tipo_mesa_web');
        }
        // Sin tabla mesa (return legítimo de esa parte): el cliente no tiene mesas; la columna la agrega una migración nueva.

        // Regla 7: comprobar en el catálogo antes de darse por aplicada.
        foreach ($exigidas as $tc) {
            if (!$this->existe($tc[0], $tc[1])) {
                throw new RuntimeException(__METHOD__ . ": falta {$tc[0]}.{$tc[1]} después de crearla. " .
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
        // No destructivo a propósito: se perderían los tipos y el tipo de cada mesa. Si hiciera falta, va en una migración nueva.
        log_message('info', __METHOD__ . ': sin cambios (down no destructivo).');
    }
}
