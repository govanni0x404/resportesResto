<?php
defined('BASEPATH') or exit('No direct script access allowed');

/** Etapa 5: rendiciones parciales, recepción verificada y diferencias auditables. */
class Migration_delivery_rendiciones extends CI_Migration
{
	public function up()
	{
		if (!$this->db->table_exists('delivery_cobro')) throw new RuntimeException(__METHOD__ . ': falta delivery_cobro; se reintenta.');
		if ($this->db->table_exists('configuracion')) $this->db->query('ALTER TABLE public.configuracion ADD COLUMN IF NOT EXISTS delivery_limite_rendicion integer NOT NULL DEFAULT 0');
		$this->db->query("CREATE TABLE IF NOT EXISTS public.delivery_rendicion (
			id_delivery_rendicion serial PRIMARY KEY,
			accion_id character varying(64) NOT NULL UNIQUE,
			id_repartidor integer NOT NULL,
			id_caja integer,
			estado character varying(20) NOT NULL DEFAULT 'presentada',
			monto_esperado integer NOT NULL CHECK (monto_esperado >= 0),
			monto_declarado integer NOT NULL CHECK (monto_declarado > 0),
			monto_contado integer,
			diferencia integer,
			observacion character varying(250),
			id_verificador integer,
			fecha_presentada timestamp with time zone NOT NULL DEFAULT now(),
			fecha_verificada timestamp with time zone
		)");
		$this->db->query("CREATE TABLE IF NOT EXISTS public.delivery_rendicion_detalle (
			id_delivery_rendicion_detalle serial PRIMARY KEY,
			id_delivery_rendicion integer NOT NULL,
			id_delivery_cobro integer NOT NULL,
			monto_declarado integer NOT NULL CHECK (monto_declarado > 0),
			monto_recibido integer NOT NULL DEFAULT 0 CHECK (monto_recibido >= 0),
			UNIQUE (id_delivery_rendicion, id_delivery_cobro)
		)");
		$this->db->query('ALTER TABLE public.delivery_rendicion ADD COLUMN IF NOT EXISTS diferencia_resuelta boolean NOT NULL DEFAULT false');
		$this->db->query('ALTER TABLE public.delivery_rendicion ADD COLUMN IF NOT EXISTS resolucion character varying(32)');
		$this->db->query('ALTER TABLE public.delivery_rendicion ADD COLUMN IF NOT EXISTS nota_resolucion character varying(250)');
		$this->db->query('ALTER TABLE public.delivery_rendicion ADD COLUMN IF NOT EXISTS resuelta_por integer');
		$this->db->query('ALTER TABLE public.delivery_rendicion ADD COLUMN IF NOT EXISTS resuelta_en timestamp with time zone');
		$this->db->query('ALTER TABLE public.delivery_rendicion_detalle ADD COLUMN IF NOT EXISTS monto_ajustado integer NOT NULL DEFAULT 0');
		$this->db->query('CREATE INDEX IF NOT EXISTS ix_delivery_rendicion_estado_fecha ON public.delivery_rendicion (estado, fecha_presentada)');
		$this->db->query('CREATE INDEX IF NOT EXISTS ix_delivery_rendicion_repartidor ON public.delivery_rendicion (id_repartidor, fecha_presentada DESC)');
		$this->db->query('CREATE INDEX IF NOT EXISTS ix_delivery_rendicion_detalle_cobro ON public.delivery_rendicion_detalle (id_delivery_cobro)');
		$this->db->query('CREATE INDEX IF NOT EXISTS ix_delivery_rendicion_caja ON public.delivery_rendicion (id_caja, fecha_verificada) WHERE id_caja IS NOT NULL');
		$this->db->query("DO $$ BEGIN
			IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_delivery_rendicion_estado') THEN
				ALTER TABLE public.delivery_rendicion ADD CONSTRAINT ck_delivery_rendicion_estado CHECK (estado IN ('presentada','verificada','diferencia'));
			END IF;
		END $$");
		if (!$this->db->table_exists('delivery_rendicion') || !$this->db->table_exists('delivery_rendicion_detalle')) {
			throw new RuntimeException(__METHOD__ . ': faltan tablas luego de la migración; se reintenta.');
		}
	}

	public function down()
	{
		// No se eliminan rendiciones ni sus diferencias.
	}
}
