<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261111180000
 * Timestamp: 2026-11-11 18:00:00
 *
 * Caja web > factura: los datos del receptor elegidos al cobrar (razón social, giro, dirección, comuna, contacto) y quién
 * solicita la factura (RUT y nombre), una fila por venta. Un mismo cliente puede facturar con otro giro o a otra dirección
 * sin tocar su ficha; la próxima vez la Caja ofrece los datos ya usados.
 *
 * - La emisión (Mdte) y el ticket usan estos datos si la venta los tiene; si no, los de la ficha del cliente, como antes.
 *   La nota de crédito de una factura copia los de la factura.
 * - Tabla nueva: CREATE TABLE IF NOT EXISTS no toma locks sobre tablas existentes (Regla 6). Sin claves foráneas a
 *   propósito: no se toca ni se bloquea venta ni cliente.
 * - El código no asume que esta migración ya corrió: Mcaja y Mdte preguntan al catálogo (to_regclass) y, si falta la
 *   tabla, la factura sale con los datos de la ficha del cliente, como antes.
 */
class Migration_add_venta_receptor extends CI_Migration
{
    public function up()
    {
        $this->ejecutar(<<<'SQL'
CREATE TABLE IF NOT EXISTS public.venta_receptor (
    id_venta integer PRIMARY KEY,
    id_cliente integer NOT NULL DEFAULT 0,
    razon_social varchar(100) NOT NULL DEFAULT '',
    giro varchar(80) NOT NULL DEFAULT '',
    direccion varchar(100) NOT NULL DEFAULT '',
    id_comuna integer NOT NULL DEFAULT 0,
    comuna varchar(40) NOT NULL DEFAULT '',
    ciudad varchar(40) NOT NULL DEFAULT '',
    contacto varchar(80) NOT NULL DEFAULT '',
    rut_solicita integer,
    dv_solicita varchar(1),
    nombre_solicita varchar(80),
    fecha timestamp without time zone NOT NULL
)
SQL
            , 'CREATE venta_receptor');

        $this->ejecutar('CREATE INDEX IF NOT EXISTS ix_venta_receptor_cliente ON public.venta_receptor (id_cliente, fecha)',
            'INDEX ix_venta_receptor_cliente');

        $this->ejecutar("COMMENT ON TABLE public.venta_receptor IS 'Caja web: receptor y solicitante elegidos al emitir una factura (una fila por venta)'",
            'COMMENT venta_receptor');

        // Regla 7: comprobar en el catálogo antes de darse por aplicada.
        $q = $this->db->query(
            "SELECT (to_regclass('public.venta_receptor') IS NOT NULL)::int
                  + (to_regclass('public.ix_venta_receptor_cliente') IS NOT NULL)::int AS n");
        $fila = ($q && method_exists($q, 'row')) ? $q->row() : null;
        if (!$fila || (int) $fila->n !== 2) {
            throw new RuntimeException(
                __METHOD__ . ': falta la tabla o el índice de los datos de factura después de crearlos. ' .
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
        // No destructivo a propósito: borrar la tabla perdería con qué datos se emitió cada factura.
        // Si hiciera falta quitarla, va en una migración nueva.
        log_message('info', __METHOD__ . ': sin cambios (down no destructivo).');
    }
}
