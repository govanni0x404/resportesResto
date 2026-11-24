<?php
defined('BASEPATH') or exit('No direct script access allowed');

/** Etapa 6: tarifa general y por repartidor, comisión congelada por pedido y pago como egreso. */
class Migration_delivery_comisiones extends CI_Migration
{
	public function up()
	{
		if (!$this->db->table_exists('delivery_pedido') || !$this->db->table_exists('delivery_cobro')) throw new RuntimeException(__METHOD__ . ': faltan tablas previas de Delivery; se reintenta.');
		if ($this->db->table_exists('configuracion')) {
			$this->db->query("ALTER TABLE public.configuracion ADD COLUMN IF NOT EXISTS delivery_comision_tipo character varying(16) NOT NULL DEFAULT 'fija'");
			$this->db->query('ALTER TABLE public.configuracion ADD COLUMN IF NOT EXISTS delivery_comision_valor numeric(12,2) NOT NULL DEFAULT 0');
		}
		$this->db->query("CREATE TABLE IF NOT EXISTS public.delivery_tarifa_repartidor (
			id_usuario integer PRIMARY KEY,
			tipo character varying(16) NOT NULL CHECK (tipo IN ('fija','porcentaje','sin_comision')),
			valor numeric(12,2) NOT NULL CHECK (valor >= 0),
			actualizado_por integer NOT NULL,
			actualizado_en timestamp with time zone NOT NULL DEFAULT now()
		)");
		$this->db->query("CREATE TABLE IF NOT EXISTS public.delivery_comision (
			id_delivery_comision serial PRIMARY KEY,
			id_delivery integer NOT NULL UNIQUE,
			id_repartidor integer NOT NULL,
			tipo character varying(16) NOT NULL CHECK (tipo IN ('fija','porcentaje')),
			tarifa numeric(12,2) NOT NULL,
			base integer NOT NULL CHECK (base >= 0),
			monto integer NOT NULL CHECK (monto >= 0),
			estado character varying(16) NOT NULL DEFAULT 'pendiente' CHECK (estado IN ('pendiente','pagada')),
			fecha_generada timestamp with time zone NOT NULL DEFAULT now(),
			id_caja integer,
			id_pagador integer,
			medio_pago character varying(24),
			referencia character varying(120),
			accion_id character varying(64) UNIQUE,
			fecha_pago timestamp with time zone
		)");
		$this->db->query('CREATE INDEX IF NOT EXISTS ix_delivery_comision_estado_fecha ON public.delivery_comision (estado, fecha_generada)');
		$this->db->query('CREATE INDEX IF NOT EXISTS ix_delivery_comision_repartidor ON public.delivery_comision (id_repartidor, estado)');
		$this->db->query("CREATE TABLE IF NOT EXISTS public.delivery_liquidacion (
			id_delivery_liquidacion serial PRIMARY KEY,
			id_delivery_cobro integer NOT NULL UNIQUE,
			accion_id character varying(64) NOT NULL UNIQUE,
			monto_bruto integer NOT NULL CHECK (monto_bruto >= 0),
			monto_neto integer NOT NULL CHECK (monto_neto >= 0),
			cargo_plataforma integer NOT NULL CHECK (cargo_plataforma >= 0),
			otras_retenciones integer NOT NULL CHECK (otras_retenciones >= 0),
			referencia character varying(120),
			id_usuario integer NOT NULL,
			fecha timestamp with time zone NOT NULL DEFAULT now()
		)");
		if (!$this->db->table_exists('delivery_comision') || !$this->db->table_exists('delivery_tarifa_repartidor') || !$this->db->table_exists('delivery_liquidacion')) throw new RuntimeException(__METHOD__ . ': no quedaron creadas las tablas Delivery de tarifas, comisiones y liquidaciones.');
	}

	public function down()
	{
		// Se conservan tarifas, obligaciones y pagos históricos.
	}
}
