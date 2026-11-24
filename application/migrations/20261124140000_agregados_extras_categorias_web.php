<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261124140000
 * Timestamp: 2026-11-24 14:00:00
 *
 * v1.35.0 (pedidos del usuario en cambios.md), cada uno con su casilla en Configuraciones (parten apagadas):
 *
 * 1. Agregados extra con costo: con el máximo del producto lleno, se pueden sumar más agregados pagando su precio extra.
 *    - configuracion.agregados_extra_web   boolean
 *    - agregados.valor_extra_web           integer  precio del agregado cuando va como extra (0 = su valor de siempre)
 *    - producto.max_extras_web             integer  cuántos extras se pueden sumar (0 = sin tope)
 *
 * 2. Categorías de agregados (Proteína, Verdura...): en la Caja los agregados salen agrupados en un cuadro por categoría,
 *    con límite por categoría además del total.
 *    - configuracion.agregados_categorias_web     boolean
 *    - categoria_agregado_web                     mantenedor Tablas > Categorías de agregados
 *    - agregados.id_categoria_agregado_web        integer  0 = sin categoría
 *    - producto_categoria_agregado_web            límite de cada categoría en cada producto (0 = sin límite)
 *
 * Idempotente (IF NOT EXISTS). Funciona en PostgreSQL 10 (VPS). El código no asume que corrió (revisa las columnas).
 */
class Migration_agregados_extras_categorias_web extends CI_Migration
{
    public function up()
    {
        foreach (array('configuracion', 'agregados', 'producto') as $t) {
            if (!$this->tabla($t)) {
                throw new RuntimeException(__METHOD__ . ': falta la tabla ' . $t . '. Se reintenta en la próxima ejecución.');
            }
        }
        foreach (array('agregados_extra_web', 'agregados_categorias_web') as $col) {
            $this->ejecutar("ALTER TABLE public.configuracion ADD COLUMN IF NOT EXISTS $col boolean NOT NULL DEFAULT false",
                'ADD COLUMN configuracion.' . $col);
        }
        $this->ejecutar('ALTER TABLE public.agregados ADD COLUMN IF NOT EXISTS valor_extra_web integer NOT NULL DEFAULT 0',
            'ADD COLUMN agregados.valor_extra_web');
        $this->ejecutar('ALTER TABLE public.agregados ADD COLUMN IF NOT EXISTS id_categoria_agregado_web integer NOT NULL DEFAULT 0',
            'ADD COLUMN agregados.id_categoria_agregado_web');
        $this->ejecutar('ALTER TABLE public.producto ADD COLUMN IF NOT EXISTS max_extras_web integer NOT NULL DEFAULT 0',
            'ADD COLUMN producto.max_extras_web');
        $this->ejecutar('CREATE TABLE IF NOT EXISTS public.categoria_agregado_web (
                id_categoria_agregado serial PRIMARY KEY,
                nombre varchar(60) NOT NULL,
                orden integer NOT NULL DEFAULT 0,
                vigente boolean NOT NULL DEFAULT true,
                eliminado boolean NOT NULL DEFAULT false
            )', 'CREATE TABLE categoria_agregado_web');
        $this->ejecutar('CREATE TABLE IF NOT EXISTS public.producto_categoria_agregado_web (
                id_producto integer NOT NULL,
                id_categoria_agregado integer NOT NULL,
                maximo integer NOT NULL DEFAULT 0,
                PRIMARY KEY (id_producto, id_categoria_agregado)
            )', 'CREATE TABLE producto_categoria_agregado_web');

        // Regla 7: comprobar en el catálogo antes de darse por aplicada
        $deben = array(
            array('configuracion', 'agregados_extra_web'), array('configuracion', 'agregados_categorias_web'),
            array('agregados', 'valor_extra_web'), array('agregados', 'id_categoria_agregado_web'),
            array('producto', 'max_extras_web'), array('categoria_agregado_web', 'nombre'),
            array('producto_categoria_agregado_web', 'maximo'),
        );
        foreach ($deben as $d) {
            if (!$this->existe($d[0], $d[1])) {
                throw new RuntimeException(__METHOD__ . ': falta ' . $d[0] . '.' . $d[1] . ' después de crearla. ' .
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
