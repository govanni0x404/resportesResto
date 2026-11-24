<?php
defined('BASEPATH') or exit('No direct script access allowed');

/** Etapa 4 de Delivery: confirma resultado de entrega y declara cobros idempotentes, sin mover caja. */
class Migration_delivery_entrega_cobros extends CI_Migration
{
	public function up()
	{
		if (!$this->db->table_exists('delivery_pedido')) throw new RuntimeException(__METHOD__ . ': falta delivery_pedido; se reintenta en la próxima ejecución.');
		$this->db->query("ALTER TABLE public.delivery_pedido ADD COLUMN IF NOT EXISTS estado_pago character varying(24) NOT NULL DEFAULT 'pendiente'");
		$this->db->query("ALTER TABLE public.delivery_pedido ADD COLUMN IF NOT EXISTS resultado_entrega character varying(24)");
		$this->db->query("UPDATE public.delivery_pedido SET estado_pago = 'verificado' WHERE id_venta IS NOT NULL AND estado_pago = 'pendiente'");
		$this->db->query("CREATE TABLE IF NOT EXISTS public.delivery_cobro (
			id_delivery_cobro serial PRIMARY KEY,
			id_delivery integer NOT NULL,
			accion_id character varying(64) NOT NULL UNIQUE,
			id_usuario integer NOT NULL,
			medio_pago character varying(24) NOT NULL,
			monto integer NOT NULL CHECK (monto >= 0),
			en_custodia_repartidor boolean NOT NULL DEFAULT false,
			referencia character varying(120),
			estado character varying(24) NOT NULL DEFAULT 'pendiente_registro',
			id_venta integer,
			fecha timestamp with time zone NOT NULL DEFAULT now()
		)");
		$this->db->query('CREATE INDEX IF NOT EXISTS ix_delivery_cobro_pedido_fecha ON public.delivery_cobro (id_delivery, fecha, id_delivery_cobro)');
		$this->db->query('CREATE INDEX IF NOT EXISTS ix_delivery_cobro_venta ON public.delivery_cobro (id_venta) WHERE id_venta IS NOT NULL');
		$this->db->query('CREATE INDEX IF NOT EXISTS ix_delivery_cobro_estado ON public.delivery_cobro (estado, en_custodia_repartidor)');
		$this->db->query("DO $$ BEGIN
			IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_delivery_cobro_estado') THEN
				ALTER TABLE public.delivery_cobro ADD CONSTRAINT ck_delivery_cobro_estado CHECK (estado IN ('pendiente_registro','registrada','verificada','diferencia'));
			END IF;
		END $$");
		if (!$this->db->field_exists('estado_pago', 'delivery_pedido') || !$this->db->field_exists('resultado_entrega', 'delivery_pedido')
			|| !$this->db->table_exists('delivery_cobro') || !$this->db->field_exists('accion_id', 'delivery_cobro')
			|| !$this->db->field_exists('id_venta', 'delivery_cobro')) {
			throw new RuntimeException(__METHOD__ . ': faltan columnas o tabla luego de la migración; se reintenta.');
		}
	}

	public function down()
	{
		// Se conservan declaraciones de cobro y registros históricos.
	}
}
