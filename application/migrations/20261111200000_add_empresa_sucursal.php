<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261111200000
 * Timestamp: 2026-11-11 20:00:00
 *
 * Configuraciones > Datos de la Empresa: varias empresas y varias sucursales por empresa, con los datos de la intranet.
 *
 * - Las empresas las registran los técnicos de Datamaule en la tabla empresa (una fila por RUT). Por cada una se consulta
 *   la intranet (validarCliente con su RUT): empresa.id_cliente_intranet, la fecha y el resultado de la última consulta,
 *   la comuna como la informa la intranet y qué campos vinieron de ella (esos no se editan aquí).
 * - empresa_sucursal: las sucursales de cada empresa según la intranet (listasucursales), una fila por sucursal de la
 *   intranet (id_sucursal_intranet = control_caja.id_sucursal). Aquí solo se completan el código SII de la sucursal
 *   (codigo_sii_manual, si la intranet no lo trae) y la comuna (id_comuna, si el nombre no calza con la tabla comuna).
 * - Los documentos de una caja (Control de cajas) toman la dirección, comuna y código SII de su sucursal.
 *
 * - ALTER TABLE empresa: una sola vez aquí (Regla 6). Columnas sin DEFAULT: no reescribe la tabla.
 * - CREATE TABLE IF NOT EXISTS; si ya existía, solo se agregan las columnas que falten. Sin claves foráneas; índices
 *   simples (no únicos), para que una tabla existente con repetidos no haga fallar la migración.
 * - El código no asume que esta migración ya corrió (Mempresas::disponible()): sin ella todo sigue como antes.
 */
class Migration_add_empresa_sucursal extends CI_Migration
{
    private $empresa = array(
        'id_cliente_intranet'   => 'integer',
        'fecha_sincronizacion'  => 'timestamp without time zone',
        'estado_sincronizacion' => 'varchar(200)',
        'comuna_intranet'       => 'varchar(80)',
        'campos_intranet'       => 'varchar(300)',
    );

    private $sucursal = array(
        'id_empresa'                 => 'integer',
        'id_cliente_intranet'        => 'integer',
        'id_sucursal_intranet'       => 'integer',
        'nombre_sucursal'            => 'varchar(120)',
        'nombre_fantasia'            => 'varchar(120)',
        'direccion'                  => 'varchar(200)',
        'comuna'                     => 'varchar(80)',
        'id_comuna'                  => 'integer',
        'telefono'                   => 'varchar(60)',
        'mail'                       => 'varchar(150)',
        'giro'                       => 'varchar(200)',
        'casa_matriz'                => 'boolean',
        'codigo_sii'                 => 'integer',
        'codigo_sii_manual'          => 'integer',
        'imprimir_en_encabezado_dte' => 'boolean',
        'vigente'                    => 'boolean',
        'fecha_sincronizacion'       => 'timestamp without time zone',
    );

    public function up()
    {
        foreach ($this->empresa as $columna => $tipo) {
            $this->ejecutar("ALTER TABLE public.empresa ADD COLUMN IF NOT EXISTS $columna $tipo", "ADD COLUMN empresa.$columna");
        }

        $campos = array('id_empresa_sucursal serial PRIMARY KEY');
        foreach ($this->sucursal as $columna => $tipo) $campos[] = "$columna $tipo";
        $this->ejecutar('CREATE TABLE IF NOT EXISTS public.empresa_sucursal (' . implode(', ', $campos) . ')', 'CREATE empresa_sucursal');
        // Si la tabla ya existía con otra forma: solo se agregan las columnas que falten
        foreach ($this->sucursal as $columna => $tipo) {
            $this->ejecutar("ALTER TABLE public.empresa_sucursal ADD COLUMN IF NOT EXISTS $columna $tipo", "ADD COLUMN empresa_sucursal.$columna");
        }
        $this->ejecutar('CREATE INDEX IF NOT EXISTS ix_empresa_sucursal_intranet ON public.empresa_sucursal (id_sucursal_intranet)',
            'INDEX ix_empresa_sucursal_intranet');
        $this->ejecutar('CREATE INDEX IF NOT EXISTS ix_empresa_sucursal_empresa ON public.empresa_sucursal (id_empresa)',
            'INDEX ix_empresa_sucursal_empresa');
        $this->ejecutar("COMMENT ON TABLE public.empresa_sucursal IS 'Sucursales de cada empresa según la intranet (Configuraciones > Datos de la Empresa)'",
            'COMMENT empresa_sucursal');

        // Regla 7: comprobar en el catálogo antes de darse por aplicada.
        $faltan = array();
        foreach (array_keys($this->empresa) as $columna) {
            if (!$this->existe('empresa', $columna)) $faltan[] = "empresa.$columna";
        }
        foreach (array_merge(array('id_empresa_sucursal'), array_keys($this->sucursal)) as $columna) {
            if (!$this->existe('empresa_sucursal', $columna)) $faltan[] = "empresa_sucursal.$columna";
        }
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
        // No destructivo a propósito: borrar la tabla o las columnas perdería las sucursales y lo completado aquí.
        // Si hiciera falta quitarlas, va en una migración nueva.
        log_message('info', __METHOD__ . ': sin cambios (down no destructivo).');
    }
}
