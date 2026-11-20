<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261119150000
 * Timestamp: 2026-11-19 15:00:00
 *
 * Códigos SII de los ILA (impuesto.codigo_sii), para informarlos en facturas y notas de crédito (Detalle.CodImpAdic y
 * Totales.ImptoReten). Venían en 0 y sin código el ILA no se puede declarar. Tabla de impuestos adicionales del SII:
 *   27 = bebidas analcohólicas 10%      271 = bebidas analcohólicas con elevado azúcar 18%
 *   26 = cervezas 20,5%                 25  = vinos 20,5%                 24 = licores y destilados 31,5%
 * Solo completa los que están en 0 (no pisa un código ya puesto). El ILA 20,5% "VINOS/CERVEZAS" queda como cervezas (26)
 * y se agrega "ILA 20.5% VINOS" (25) si no hay ninguno con ese código. No cambia montos ni productos.
 */
class Migration_codigos_sii_ila extends CI_Migration
{
    public function up()
    {
        if (!$this->existeColumna('impuesto', 'codigo_sii')) return;   // otra versión de la base: nada que hacer

        $this->ejecutar("UPDATE impuesto SET codigo_sii = 27 WHERE COALESCE(codigo_sii, 0) = 0 AND nombre_impuesto ILIKE 'ILA%' AND valor = 10", 'ILA 10% -> 27');
        $this->ejecutar("UPDATE impuesto SET codigo_sii = 271 WHERE COALESCE(codigo_sii, 0) = 0 AND nombre_impuesto ILIKE 'ILA%' AND valor = 18", 'ILA 18% -> 271');
        $this->ejecutar("UPDATE impuesto SET codigo_sii = 24 WHERE COALESCE(codigo_sii, 0) = 0 AND nombre_impuesto ILIKE 'ILA%' AND valor = 31.5", 'ILA 31,5% -> 24');
        $this->ejecutar("UPDATE impuesto SET codigo_sii = 25 WHERE COALESCE(codigo_sii, 0) = 0 AND nombre_impuesto ILIKE 'ILA%' AND valor = 20.5
            AND COALESCE(observacion, '') ILIKE '%VINO%' AND COALESCE(observacion, '') NOT ILIKE '%CERVEZA%'", 'ILA 20,5% vinos -> 25');
        $this->ejecutar("UPDATE impuesto SET codigo_sii = 26,
                observacion = CASE WHEN observacion = 'VINOS/CERVEZAS' THEN 'CERVEZAS' ELSE observacion END
            WHERE COALESCE(codigo_sii, 0) = 0 AND nombre_impuesto ILIKE 'ILA%' AND valor = 20.5", 'ILA 20,5% -> 26');
        $this->ejecutar("INSERT INTO impuesto (nombre_impuesto, valor, observacion, recuperable, codigo_sii)
            SELECT 'ILA 20.5% VINOS', 20.5, 'VINOS', false, 25
            WHERE NOT EXISTS (SELECT 1 FROM impuesto WHERE codigo_sii = 25)", 'INSERT ILA vinos');

        // Regla 7: los ILA con tasa conocida tienen que quedar con código
        $q = $this->db->query("SELECT count(*) AS n FROM impuesto WHERE nombre_impuesto ILIKE 'ILA%'
            AND valor IN (10, 18, 20.5, 31.5) AND COALESCE(codigo_sii, 0) = 0");
        $f = ($q && method_exists($q, 'row')) ? $q->row() : null;
        if (!$f || (int)$f->n > 0) {
            throw new RuntimeException(__METHOD__ . ': quedaron ILA sin código SII. No se marca como aplicada.');
        }
    }

    private function existeColumna($tabla, $columna)
    {
        $q = $this->db->query("SELECT count(*) AS n FROM information_schema.columns WHERE table_schema = 'public' AND table_name = ? AND column_name = ?", array($tabla, $columna));
        $fila = ($q && method_exists($q, 'row')) ? $q->row() : null;
        return $fila && (int)$fila->n > 0;
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
        // No se borran códigos: las facturas emitidas con ILA los necesitan para sus notas de crédito.
    }
}
