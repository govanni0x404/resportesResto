<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261119100000
 * Timestamp: 2026-11-19 10:00:00
 *
 * Pago electrónico (pedido del usuario, 19-11-2026): la Caja web cobra con tarjeta en la máquina POS por el programa
 * local ApiPagoElectronico (Integracion_Maquinas_Pos, http://localhost:5005). La web le manda la petición, el programa
 * valida los datos con los suyos y la pasa a la máquina (Getnet, Transbank, Mercado Pago o TUU / Haulmer).
 *
 * - pago_electronico_web: Mantención > Pago electrónico. Cada fila es una máquina: el modelo (de una lista fija, ver
 *   Mpagoelectronico::MODELOS; de él sale el tipo que va en la petición), id de sucursal, nombre de caja, terminal ID,
 *   API key (Mercado Pago: access token; TUU: API key), tiempo de espera y las formas de pago que se cobran en ella
 *   (ids de fpago separados por coma). La dirección del programa no se muestra: http://localhost:5005.
 * - configuracion.pago_electronico_usuario_api / pago_electronico_clave_api: Configuraciones > General, el usuario y
 *   la clave de la API del programa (los mismos para todas las cajas). Sin DEFAULT: no reescribe la tabla.
 * - control_caja.id_pago_electronico_web: Herramientas > Control de cajas, la máquina que usa cada caja (NULL = no usa).
 * - pago_electronico_web_tx: cada cobro enviado a la máquina, con la venta (JSON, para registrarla después), el estado
 *   (enviando, pendiente, aprobado, rechazado, cancelado, registrada) y la respuesta. Los que quedan sin terminar se
 *   muestran arriba en la Caja para cancelarlos, volver a enviarlos o aprobarlos.
 *
 * Nombres con "_web" a propósito: la base la comparte el POS de escritorio, que ya tiene sus propias tablas de pago
 * electrónico (pago_electronico, proveedor_pago_electronico, control_caja.id_pago_electronico, pago_venta.id_pago_electronico).
 * Esas no se tocan. (La primera versión de esta migración usaba esos mismos nombres y no se aplicó: corregida antes de
 * publicarse.)
 *
 * Idempotente (IF NOT EXISTS); la columna de control_caja va sin DEFAULT (no reescribe la tabla). El código no asume
 * que esta migración ya corrió: sin las tablas, la Caja cobra como antes.
 */
class Migration_add_pago_electronico extends CI_Migration
{
    public function up()
    {
        $this->ejecutar("CREATE TABLE IF NOT EXISTS public.pago_electronico_web (
            id_pago_electronico_web serial PRIMARY KEY,
            modelo character varying(40) NOT NULL,
            nombre character varying(80) NOT NULL,
            tipo character varying(20) NOT NULL,
            url_servicio character varying(200) NOT NULL DEFAULT 'http://localhost:5005',
            id_sucursal_pos character varying(30) NOT NULL DEFAULT '',
            nombre_caja_pos character varying(60) NOT NULL DEFAULT '',
            terminal_id character varying(100) NOT NULL DEFAULT '',
            api_key character varying(300) NOT NULL DEFAULT '',
            timeout_seg integer NOT NULL DEFAULT 120,
            formas_pago character varying(300) NOT NULL DEFAULT '',
            vigente boolean NOT NULL DEFAULT true,
            fecha_creacion timestamp with time zone NOT NULL DEFAULT now(),
            fecha_modificacion timestamp with time zone
        )", 'CREATE pago_electronico_web');
        $this->ejecutar("COMMENT ON TABLE public.pago_electronico_web IS 'Caja web: maquinas POS del pago electronico (datos de la peticion a ApiPagoElectronico)'",
            'COMMENT pago_electronico_web');

        if ($this->existe('control_caja', 'id_control_caja')) {
            $this->ejecutar('ALTER TABLE public.control_caja ADD COLUMN IF NOT EXISTS id_pago_electronico_web integer',
                'ADD COLUMN control_caja.id_pago_electronico_web');
        }
        if ($this->existe('configuracion', 'id_configuracion')) {
            $this->ejecutar('ALTER TABLE public.configuracion ADD COLUMN IF NOT EXISTS pago_electronico_usuario_api character varying(60)',
                'ADD COLUMN configuracion.pago_electronico_usuario_api');
            $this->ejecutar('ALTER TABLE public.configuracion ADD COLUMN IF NOT EXISTS pago_electronico_clave_api character varying(200)',
                'ADD COLUMN configuracion.pago_electronico_clave_api');
        }
        // Sin control_caja (return legítimo de esa parte): la base aún no tiene Control de cajas; la columna la agrega
        // una migración nueva si hace falta. La Caja sin control de cajas no usa el pago electrónico.

        $this->ejecutar("CREATE TABLE IF NOT EXISTS public.pago_electronico_web_tx (
            id_pago_electronico_web_tx serial PRIMARY KEY,
            tx_id character varying(64) NOT NULL,
            id_pago_electronico_web integer,
            id_control_caja integer,
            id_caja integer,
            id_usuario integer,
            monto integer NOT NULL DEFAULT 0,
            estado character varying(15) NOT NULL DEFAULT 'enviando',
            descripcion character varying(150),
            venta jsonb,
            respuesta jsonb,
            mensaje character varying(400),
            codigo_autorizacion character varying(80),
            id_venta integer,
            fecha_creacion timestamp with time zone NOT NULL DEFAULT now(),
            fecha_actualizacion timestamp with time zone NOT NULL DEFAULT now()
        )", 'CREATE pago_electronico_web_tx');
        $this->ejecutar('CREATE UNIQUE INDEX IF NOT EXISTS ux_pago_electronico_web_tx_tx ON public.pago_electronico_web_tx (tx_id)',
            'INDEX pago_electronico_web_tx.tx_id');
        $this->ejecutar('CREATE INDEX IF NOT EXISTS ix_pago_electronico_web_tx_caja ON public.pago_electronico_web_tx (id_control_caja, estado)',
            'INDEX pago_electronico_web_tx.id_control_caja');
        $this->ejecutar("COMMENT ON TABLE public.pago_electronico_web_tx IS 'Caja web: cobros enviados a la maquina POS (pendientes se resuelven en la Caja)'",
            'COMMENT pago_electronico_web_tx');

        // Regla 7: comprobar en el catálogo antes de darse por aplicada.
        $exigidas = array(array('pago_electronico_web', 'modelo'), array('pago_electronico_web', 'api_key'), array('pago_electronico_web', 'formas_pago'), array('pago_electronico_web_tx', 'tx_id'), array('pago_electronico_web_tx', 'venta'));
        if ($this->existe('control_caja', 'id_control_caja')) $exigidas[] = array('control_caja', 'id_pago_electronico_web');
        if ($this->existe('configuracion', 'id_configuracion')) $exigidas[] = array('configuracion', 'pago_electronico_clave_api');
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
        // No destructivo a propósito: se perderían las máquinas configuradas y los cobros. Si hiciera falta, va en una
        // migración nueva.
        log_message('info', __METHOD__ . ': sin cambios (down no destructivo).');
    }
}
