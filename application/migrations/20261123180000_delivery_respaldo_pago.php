<?php
defined('BASEPATH') or exit('No direct script access allowed');

/** Respaldo privado de cobros declarados por repartidores (se sirve solo con sesión y autorización). */
class Migration_delivery_respaldo_pago extends CI_Migration
{
	public function up()
	{
		if (!$this->db->table_exists('delivery_pedido') || !$this->db->table_exists('delivery_cobro')) throw new RuntimeException(__METHOD__ . ': faltan tablas de Delivery previas; se reintenta.');
		$this->db->query("CREATE TABLE IF NOT EXISTS public.delivery_evidencia (
			id_delivery_evidencia serial PRIMARY KEY,
			id_delivery integer NOT NULL,
			id_usuario integer NOT NULL,
			nombre_archivo character varying(64) NOT NULL UNIQUE,
			nombre_original character varying(120) NOT NULL,
			tipo_mime character varying(40) NOT NULL CHECK (tipo_mime IN ('image/jpeg','image/png','application/pdf')),
			tamano integer NOT NULL CHECK (tamano > 0 AND tamano <= 5242880),
			fecha timestamp with time zone NOT NULL DEFAULT now()
		)");
		$this->db->query('CREATE INDEX IF NOT EXISTS ix_delivery_evidencia_pedido_fecha ON public.delivery_evidencia (id_delivery, fecha DESC)');
		if (!$this->db->table_exists('delivery_evidencia')) throw new RuntimeException(__METHOD__ . ': no quedó creada delivery_evidencia.');
	}

	public function down()
	{
		// No se eliminan respaldos de cobros.
	}
}
