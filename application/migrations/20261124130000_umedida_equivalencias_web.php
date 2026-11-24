<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261124130000
 * Timestamp: 2026-11-24 13:00:00
 *
 * v1.34.0 (pedido del usuario en cambios.md: "si dice 1 kg, se sabe que son 1000 gr"): cada unidad de medida sabe a
 * cuánto equivale, para que recetas y producción conviertan solas entre kilos y gramos o litros y mililitros.
 *
 * umedida:
 *  - tipo_web    varchar(10)  'peso', 'volumen' o 'unidad' (NULL = sin equivalencia: se convierte solo con lo que diga
 *                             cada insumo, como antes)
 *  - factor_web  numeric      cuántos gramos / mililitros / unidades es 1 de esta unidad (KILOGRAMO = 1000, GRAMO = 1)
 * Se llenan según el nombre (KILOGRAMO, KG, GRAMO, GR, LITRO, LT, MILILITRO, ML, CC, UNIDAD...). Si no existe una unidad
 * de gramos o de mililitros, se crean GRAMO y MILILITRO (para poder usar recetas en gramos con insumos comprados en kilos).
 *
 * Idempotente. Funciona en PostgreSQL 10 (VPS). El código no asume que corrió (revisa las columnas).
 */
class Migration_umedida_equivalencias_web extends CI_Migration
{
    /** Misma regla que MuMedida::equivalenciaPorNombre (nombre en mayúsculas, sin puntos ni espacios de más). */
    const REGLAS = array(
        array('peso', 1000, array('KILOGRAMO', 'KILOGRAMOS', 'KILO', 'KILOS', 'KG', 'KGS', 'KGR')),
        array('peso', 1, array('GRAMO', 'GRAMOS', 'GR', 'GRS', 'G', 'GRAMAJE')),
        array('volumen', 1000, array('LITRO', 'LITROS', 'LT', 'LTS', 'L', 'LTR')),
        array('volumen', 1, array('MILILITRO', 'MILILITROS', 'ML', 'MLS', 'CC', 'CM3')),
        array('unidad', 1, array('UNIDAD', 'UNIDADES', 'UN', 'UND', 'UNID', 'U', 'UNI')),
    );

    public function up()
    {
        if (!$this->tabla('umedida')) {
            throw new RuntimeException(__METHOD__ . ': falta la tabla umedida. Se reintenta en la próxima ejecución.');
        }
        $this->ejecutar('ALTER TABLE public.umedida ADD COLUMN IF NOT EXISTS tipo_web varchar(10)', 'ADD COLUMN umedida.tipo_web');
        $this->ejecutar('ALTER TABLE public.umedida ADD COLUMN IF NOT EXISTS factor_web numeric', 'ADD COLUMN umedida.factor_web');

        {
            // Equivalencias según el nombre (solo las que no tienen; la migración corre una vez y después las maneja
            // Mantención > Unidad de medida)
            foreach (self::REGLAS as $r) {
                $nombres = "'" . implode("', '", $r[2]) . "'";
                $this->ejecutar("UPDATE public.umedida SET tipo_web = '{$r[0]}', factor_web = {$r[1]}
                    WHERE tipo_web IS NULL AND regexp_replace(upper(trim(nombre_umedida)), '[.\\s]+', '', 'g') IN ($nombres)",
                    'UPDATE umedida ' . $r[2][0]);
            }
            // Gramo y mililitro, si no hay
            foreach (array(array('GRAMO', 'peso'), array('MILILITRO', 'volumen')) as $u) {
                $this->ejecutar("INSERT INTO public.umedida (nombre_umedida, tipo_web, factor_web)
                    SELECT '{$u[0]}', '{$u[1]}', 1 WHERE NOT EXISTS (SELECT 1 FROM public.umedida WHERE tipo_web = '{$u[1]}' AND factor_web = 1)",
                    'INSERT umedida ' . $u[0]);
            }
        }

        // Regla 7: comprobar en el catálogo antes de darse por aplicada
        foreach (array('tipo_web', 'factor_web') as $col) {
            if (!$this->existe('umedida', $col)) {
                throw new RuntimeException(__METHOD__ . ': falta umedida.' . $col . ' después de crearla. ' .
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
