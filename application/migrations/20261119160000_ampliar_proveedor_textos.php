<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261119160000
 * Timestamp: 2026-11-19 16:00:00
 *
 * Proveedor con los mismos largos que el cliente: nombre_proveedor y giro 50 -> 200, direccion 50 -> 100,
 * telefono 20 -> 30. Con 50 no cabía una razón social o un giro del SII normales (ej. "VENTA AL POR MAYOR DE OTROS
 * PRODUCTOS N.C.P."). Solo agranda: una columna que ya mide eso o más (o sin límite) no se toca. Agrandar un varchar
 * no reescribe la tabla.
 */
class Migration_ampliar_proveedor_textos extends CI_Migration
{
    private $largos = array('nombre_proveedor' => 200, 'giro' => 200, 'direccion' => 100, 'telefono' => 30);

    public function up()
    {
        foreach ($this->largos as $col => $n) {
            $actual = $this->largo($col);
            if ($actual === null) continue;   // sin la columna: nada que hacer
            if ($actual > 0 && $actual < $n) {
                $this->ejecutar("ALTER TABLE public.proveedor ALTER COLUMN $col TYPE character varying($n)", "ALTER proveedor.$col varchar($n)");
            }
        }
        // Regla 7: comprobar en el catálogo antes de darse por aplicada
        foreach ($this->largos as $col => $n) {
            $d = $this->largo($col);
            if ($d !== null && $d !== 0 && $d < $n) {
                throw new RuntimeException(__METHOD__ . ": proveedor.$col sigue en varchar($d). No se marca como aplicada.");
            }
        }
    }

    /** null = no existe la columna; 0 = sin límite; n = varchar(n). */
    private function largo($columna)
    {
        $q = $this->db->query("SELECT character_maximum_length AS n FROM information_schema.columns
            WHERE table_schema = 'public' AND table_name = 'proveedor' AND column_name = ?", array($columna));
        $f = ($q && method_exists($q, 'row')) ? $q->row() : null;
        if (!$f) return null;
        return $f->n === null ? 0 : (int)$f->n;
    }

    /** Regla 7. Con db_debug en false `query()` NO lanza: devuelve FALSE. Por eso se mira el RETORNO. */
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
        // No se achica: podría cortar datos ya grabados.
    }
}
