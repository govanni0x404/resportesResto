<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261111100000
 * Timestamp: 2026-11-11 10:00:00
 *
 * Emisión con facturacion.datamaule.cl (SimpleFactura) y tipo de autenticación ante el proveedor DTE.
 * Mismas columnas que usa miPDV (migraciones 20261023090000 y 20261031100000), pero el token vive en
 * configuracion (en la caja web "Acceso facturación" está en Configuraciones, no en la ficha de empresa).
 *
 * OJO CON EL NÚMERO (Regla 8): al escribir esta, el máximo de la carpeta era 20261110220000.
 *
 * - configuracion.proveedor_dte    varchar(20): 'datamaule' | 'acierto'. NULL o vacío = acierto (Cloud Apps),
 *                                   que es lo que la caja web hacía siempre.
 * - configuracion.tipo_auth_dte    varchar(10): 'apikey' | 'bearer' | 'token' | 'basic'. NULL o vacío = basic
 *                                   (usuario y contraseña, el encabezado de siempre).
 * - configuracion.token_dte_enc    text: API Key / token cifrado (nunca en claro). Ver helpers/dte_auth_helper.php.
 * - configuracion.token_dte_last4  varchar(4): últimos 4 caracteres, solo para mostrar que hay uno guardado.
 *
 * Solo agrega columnas: no toca datos, funciones ni triggers. Idempotente (Regla 3), sin DEFAULT, una sentencia
 * por columna y verificada en el catálogo (Regla 7). El código no asume que corrió: lee con to_jsonb y, si no
 * están las columnas, sigue con Cloud Apps y basic como antes.
 */
class Migration_dte_proveedor_auth extends CI_Migration
{
    private $columnas = array(
        'proveedor_dte'   => 'varchar(20)',
        'tipo_auth_dte'   => 'varchar(10)',
        'token_dte_enc'   => 'text',
        'token_dte_last4' => 'varchar(4)',
    );

    public function up()
    {
        foreach ($this->columnas as $columna => $tipo) {
            $this->ejecutar(
                "ALTER TABLE public.configuracion ADD COLUMN IF NOT EXISTS $columna $tipo",
                "ADD COLUMN configuracion.$columna"
            );
        }

        // Regla 7: comprobar en el catálogo antes de darse por aplicada.
        foreach (array_keys($this->columnas) as $columna) {
            $q = $this->db->query(
                "SELECT count(*) AS n FROM information_schema.columns
                  WHERE table_schema = 'public' AND table_name = 'configuracion' AND column_name = ?",
                array($columna));
            $fila = ($q && method_exists($q, 'row')) ? $q->row() : null;
            if (!$fila || (int) $fila->n === 0) {
                throw new RuntimeException(
                    __METHOD__ . ": configuracion.$columna no existe después del ALTER. " .
                    'No se marca como aplicada: se reintenta en la próxima ejecución.');
            }
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
        // No destructivo a propósito: borrar las columnas perdería el proveedor elegido y la API Key guardada.
        log_message('info', __METHOD__ . ': sin cambios (down no destructivo).');
    }
}
