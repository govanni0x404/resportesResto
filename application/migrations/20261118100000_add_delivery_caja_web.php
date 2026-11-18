<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migración: 20261118100000
 * Timestamp: 2026-11-18 10:00:00
 *
 * Delivery en la Caja web (pedido del usuario, 18-11-2026): un módulo aparte de venta directa y mesas, sin mesa, con
 * registro de los pedidos y mensaje para WhatsApp. Solo para la web: el POS de escritorio no lo usa (por eso no se usan
 * los campos de delivery de cotizacion).
 *
 * - delivery_pedido: cada pedido con los datos de entrega, lo pedido (detalle, JSON), el costo de envío, el estado
 *   (preparando, en_camino, entregado, anulado), el repartidor (opcional) y la venta (id_venta) cuando se cobra. En cada
 *   pedido se elige cobrar al tomarlo o al entregarlo (cobrar_al_entregar).
 * - configuracion.delivery_habilitado: muestra la pestaña Delivery en la Caja (NULL = apagado).
 * - configuracion.delivery_id_producto_envio / delivery_costo_envio: el envío es una línea de la boleta (el SII pide que
 *   todo lo cobrado sea una línea del documento), con ese producto y ese valor, el mismo para todos los pedidos.
 *
 * Idempotente (IF NOT EXISTS), sin DEFAULT en configuracion (no reescribe la tabla). El código no asume que corrió.
 */
class Migration_add_delivery_caja_web extends CI_Migration
{
    public function up()
    {
        $this->ejecutar("CREATE TABLE IF NOT EXISTS public.delivery_pedido (
            id_delivery serial PRIMARY KEY,
            fecha timestamp with time zone NOT NULL DEFAULT now(),
            id_caja integer,
            id_usuario integer,
            id_cliente integer,
            nombre character varying(120) NOT NULL,
            telefono character varying(30),
            direccion character varying(250),
            referencia character varying(250),
            id_sector integer,
            observacion character varying(250),
            id_lprecio integer,
            detalle jsonb NOT NULL DEFAULT '[]'::jsonb,
            subtotal integer NOT NULL DEFAULT 0,
            costo_envio integer NOT NULL DEFAULT 0,
            total integer NOT NULL DEFAULT 0,
            cobrar_al_entregar boolean NOT NULL DEFAULT false,
            id_venta integer,
            estado character varying(15) NOT NULL DEFAULT 'preparando',
            id_repartidor integer,
            fecha_estado timestamp with time zone,
            fecha_entrega timestamp with time zone,
            motivo_anulacion character varying(200)
        )", 'CREATE delivery_pedido');
        $this->ejecutar('CREATE INDEX IF NOT EXISTS ix_delivery_pedido_fecha ON public.delivery_pedido (fecha)', 'INDEX delivery_pedido.fecha');
        $this->ejecutar('CREATE INDEX IF NOT EXISTS ix_delivery_pedido_estado ON public.delivery_pedido (estado)', 'INDEX delivery_pedido.estado');
        $this->ejecutar('CREATE UNIQUE INDEX IF NOT EXISTS ux_delivery_pedido_venta ON public.delivery_pedido (id_venta) WHERE id_venta IS NOT NULL',
            'INDEX delivery_pedido.id_venta');
        $this->ejecutar("COMMENT ON TABLE public.delivery_pedido IS 'Caja web: pedidos de delivery (estado, datos de entrega y venta cuando se cobra)'",
            'COMMENT delivery_pedido');

        foreach (array('delivery_habilitado' => 'boolean', 'delivery_id_producto_envio' => 'integer', 'delivery_costo_envio' => 'integer') as $c => $t) {
            $this->ejecutar("ALTER TABLE public.configuracion ADD COLUMN IF NOT EXISTS $c $t", "ADD COLUMN configuracion.$c");
        }

        // Regla 7: comprobar en el catálogo antes de darse por aplicada.
        foreach (array(array('delivery_pedido', 'id_delivery'), array('delivery_pedido', 'detalle'), array('configuracion', 'delivery_habilitado'),
            array('configuracion', 'delivery_id_producto_envio'), array('configuracion', 'delivery_costo_envio')) as $tc) {
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

    /** Regla 7: con db_debug en false query() devuelve FALSE; se lanza para no grabar la versión. */
    private function ejecutar($sql, $etiqueta)
    {
        $resultado = null;
        $errorSql = '';
        try {
            $resultado = $this->db->query($sql);
        } catch (Throwable $e) {
            $resultado = false;
            $errorSql = $e->getMessage();
        }
        if ($resultado === false) {
            if ($errorSql === '' && method_exists($this->db, 'error')) {
                $err = $this->db->error();
                $errorSql = is_array($err) && !empty($err['message']) ? $err['message'] : 'sin detalle';
            }
            throw new RuntimeException(__CLASS__ . ": $etiqueta no se aplicó: $errorSql");
        }
        return $resultado;
    }

    public function down()
    {
        // Sin bajada automática: borraría los pedidos registrados.
    }
}
