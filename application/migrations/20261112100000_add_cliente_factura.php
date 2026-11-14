<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261112100000
 * Timestamp: 2026-11-12 10:00:00
 *
 * Datos de factura de cada cliente (Clientes y ventana "Datos de la factura" de la Caja):
 *
 * - sucursal_cliente: direcciones (sucursales) del cliente. Misma tabla y columnas que el POS / mipdv_web
 *   (nombre_sucursal_cliente = dirección); se agregan ciudad y vigente (se quita con vigente = false, como mipdv_web).
 * - giro_cliente: giros del cliente (misma tabla que el POS / mipdv_web; codigo_sii = código de actividad, puede ir vacío).
 * - solicitante_cliente: personas que piden la factura en nombre del cliente (RUTSolicita del SII), con nombre.
 * - venta_receptor.motivo: glosa libre del motivo de la solicitud, se imprime en la factura.
 *
 * - CREATE TABLE IF NOT EXISTS: si el POS ya las creó, solo se agregan las columnas que falten (ADD COLUMN IF NOT EXISTS,
 *   sin DEFAULT: no reescribe tablas). Sin claves foráneas; índices simples por id_cliente.
 * - El código no asume que esta migración ya corrió (Mclientefactura::disponible()): sin ella, todo como antes.
 */
class Migration_add_cliente_factura extends CI_Migration
{
    public function up()
    {
        $this->ejecutar('CREATE TABLE IF NOT EXISTS public.sucursal_cliente (
            id_sucursal_cliente serial PRIMARY KEY,
            id_cliente integer DEFAULT 0,
            nombre_sucursal_cliente character varying(150) NOT NULL,
            id_comuna integer DEFAULT 0,
            referencia_direccion character varying(80),
            telefono character varying(100),
            mail character varying(100)
        )', 'CREATE sucursal_cliente');
        foreach (array('id_cliente' => 'integer', 'id_comuna' => 'integer', 'referencia_direccion' => 'character varying(80)',
            'ciudad' => 'character varying(40)', 'vigente' => 'boolean') as $c => $t) {
            $this->ejecutar("ALTER TABLE public.sucursal_cliente ADD COLUMN IF NOT EXISTS $c $t", "ADD COLUMN sucursal_cliente.$c");
        }
        $this->ejecutar('CREATE INDEX IF NOT EXISTS ix_sucursal_cliente_cliente ON public.sucursal_cliente (id_cliente)', 'INDEX sucursal_cliente');

        $this->ejecutar("CREATE TABLE IF NOT EXISTS public.giro_cliente (
            id_giro_cliente serial PRIMARY KEY,
            id_cliente integer DEFAULT 0,
            codigo_sii character varying(50) NOT NULL DEFAULT '',
            nombre_giro_cliente character varying(200) NOT NULL
        )", 'CREATE giro_cliente');
        $this->ejecutar('CREATE INDEX IF NOT EXISTS ix_giro_cliente_cliente ON public.giro_cliente (id_cliente)', 'INDEX giro_cliente');

        $this->ejecutar('CREATE TABLE IF NOT EXISTS public.solicitante_cliente (
            id_solicitante_cliente serial PRIMARY KEY,
            id_cliente integer NOT NULL,
            rut integer NOT NULL,
            dv character varying(1) NOT NULL,
            nombre character varying(80) NOT NULL,
            cargo character varying(60),
            mail character varying(100),
            vigente boolean
        )', 'CREATE solicitante_cliente');
        $this->ejecutar('CREATE INDEX IF NOT EXISTS ix_solicitante_cliente_cliente ON public.solicitante_cliente (id_cliente)', 'INDEX solicitante_cliente');
        $this->ejecutar("COMMENT ON TABLE public.solicitante_cliente IS 'Quienes piden la factura en nombre del cliente (RUTSolicita del SII)'",
            'COMMENT solicitante_cliente');

        // venta_receptor la crea 20261111180000 (corre antes); si no está, el motivo simplemente no se guarda
        if ($this->existe('venta_receptor', 'id_venta')) {
            $this->ejecutar('ALTER TABLE public.venta_receptor ADD COLUMN IF NOT EXISTS motivo character varying(200)', 'ADD COLUMN venta_receptor.motivo');
        }

        // Regla 7: comprobar en el catálogo antes de darse por aplicada.
        $faltan = array();
        foreach (array('sucursal_cliente' => array('id_sucursal_cliente', 'id_cliente', 'nombre_sucursal_cliente', 'id_comuna', 'ciudad', 'vigente'),
            'giro_cliente' => array('id_giro_cliente', 'id_cliente', 'codigo_sii', 'nombre_giro_cliente'),
            'solicitante_cliente' => array('id_solicitante_cliente', 'id_cliente', 'rut', 'dv', 'nombre', 'vigente')) as $tabla => $cols) {
            foreach ($cols as $c) if (!$this->existe($tabla, $c)) $faltan[] = "$tabla.$c";
        }
        if ($this->existe('venta_receptor', 'id_venta') && !$this->existe('venta_receptor', 'motivo')) $faltan[] = 'venta_receptor.motivo';
        if ($faltan) {
            throw new RuntimeException(
                __METHOD__ . ': faltan ' . implode(', ', $faltan) . ' después de crearlas. ' .
                'No se marca como aplicada: se reintenta en la próxima ejecución.');
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
        // No destructivo a propósito: borrar las tablas perdería las direcciones, giros y solicitantes de los clientes.
        // Si hiciera falta quitarlas, va en una migración nueva.
        log_message('info', __METHOD__ . ': sin cambios (down no destructivo).');
    }
}
