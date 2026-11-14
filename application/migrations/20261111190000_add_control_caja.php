<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261111190000
 * Timestamp: 2026-11-11 19:00:00
 *
 * Herramientas > Control de cajas (como control_caja de mipdv_web):
 *
 * - control_caja: las cajas autorizadas en la intranet para este sistema (lista_cajas_autorizadas de validarCliente:
 *   id_control_caja_web, sucursal, N° de caja, nombre, terminal, vigente) y lo que se configura aquí: lista de precios,
 *   bodega y si el cajero puede cambiar la lista. Una fila por caja de la intranet (id_control_caja_web).
 * - caja.id_control_caja: con qué caja se abrió cada turno (al abrir, el cajero elige la caja). La Caja toma de ahí la
 *   lista de precios y la bodega del stock.
 *
 * - CREATE TABLE IF NOT EXISTS no toma locks sobre tablas existentes (Regla 6). Si la base ya tuviera una tabla
 *   control_caja, se le agregan solo las columnas que falten (ADD COLUMN IF NOT EXISTS, sin DEFAULT): no se toca lo que ya hay.
 * - ALTER TABLE caja: una sola vez aquí (Regla 6), nunca desde el código. Columna sin DEFAULT: no reescribe la tabla.
 * - Sin claves foráneas a propósito. Índice simple (no único) por id_control_caja_web: una tabla existente con
 *   repetidos no haría fallar la migración.
 * - El código no asume que esta migración ya corrió: Mcontrolcaja pregunta al catálogo y, sin las columnas, la Caja abre
 *   como antes (sin elegir caja, lista pública y bodega de cada producto).
 */
class Migration_add_control_caja extends CI_Migration
{
    private $columnas = array(
        'id_control_caja_web'          => 'integer',
        'id_sucursal'                  => 'integer',
        'caja_n'                       => 'integer',
        'nombre_caja'                  => 'varchar(80)',
        'terminal'                     => 'varchar(80)',
        'vigente'                      => 'boolean',
        'id_software'                  => 'integer',
        'caja_administrador'           => 'boolean',
        'id_lprecio'                   => 'integer',
        'id_bodega'                    => 'integer',
        'permitir_cambio_lprecio_caja' => 'boolean',
        'fecha_sincronizacion'         => 'timestamp without time zone',
    );

    public function up()
    {
        $this->ejecutar(<<<'SQL'
CREATE TABLE IF NOT EXISTS public.control_caja (
    id_control_caja serial PRIMARY KEY,
    id_control_caja_web integer,
    id_sucursal integer,
    caja_n integer,
    nombre_caja varchar(80),
    terminal varchar(80),
    vigente boolean,
    id_software integer,
    caja_administrador boolean,
    id_lprecio integer,
    id_bodega integer,
    permitir_cambio_lprecio_caja boolean,
    fecha_sincronizacion timestamp without time zone
)
SQL
            , 'CREATE control_caja');

        // Si la tabla ya existía con otra forma: solo se agregan las columnas que falten
        foreach ($this->columnas as $columna => $tipo) {
            $this->ejecutar("ALTER TABLE public.control_caja ADD COLUMN IF NOT EXISTS $columna $tipo", "ADD COLUMN control_caja.$columna");
        }
        $this->ejecutar('CREATE INDEX IF NOT EXISTS ix_control_caja_web ON public.control_caja (id_control_caja_web)',
            'INDEX ix_control_caja_web');
        $this->ejecutar("COMMENT ON TABLE public.control_caja IS 'Caja web: cajas autorizadas en la intranet y su lista de precios / bodega (Herramientas > Control de cajas)'",
            'COMMENT control_caja');

        $this->ejecutar('ALTER TABLE public.caja ADD COLUMN IF NOT EXISTS id_control_caja integer', 'ADD COLUMN caja.id_control_caja');

        // Regla 7: comprobar en el catálogo antes de darse por aplicada.
        $faltan = array();
        foreach (array_merge(array('id_control_caja'), array_keys($this->columnas)) as $columna) {
            if (!$this->existe('control_caja', $columna)) $faltan[] = "control_caja.$columna";
        }
        if (!$this->existe('caja', 'id_control_caja')) $faltan[] = 'caja.id_control_caja';
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
        // No destructivo a propósito: borrar la tabla o la columna perdería qué caja usó cada turno y su configuración.
        // Si hiciera falta quitarlas, va en una migración nueva.
        log_message('info', __METHOD__ . ': sin cambios (down no destructivo).');
    }
}
