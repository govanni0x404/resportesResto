<?php
defined('BASEPATH') or exit('No direct script access allowed');

/** Etapa 3 de Delivery: respuesta de asignación y retiro confirmados por el repartidor. */
class Migration_delivery_portal_repartidor extends CI_Migration
{
	public function up()
	{
		$this->db->query("ALTER TABLE public.delivery_pedido ADD COLUMN IF NOT EXISTS estado_entrega character varying(20) NOT NULL DEFAULT 'sin_asignar'");
		$this->db->query('ALTER TABLE public.delivery_pedido ADD COLUMN IF NOT EXISTS asignado_en timestamp with time zone');
		$this->db->query('ALTER TABLE public.delivery_pedido ADD COLUMN IF NOT EXISTS respondido_en timestamp with time zone');
		$this->db->query('ALTER TABLE public.delivery_pedido ADD COLUMN IF NOT EXISTS fecha_retiro timestamp with time zone');
		$this->db->query('ALTER TABLE public.delivery_pedido ADD COLUMN IF NOT EXISTS motivo_rechazo character varying(200)');
		$this->db->query("UPDATE public.delivery_pedido SET estado_entrega = CASE
			WHEN estado = 'anulado' THEN 'cancelado' WHEN estado = 'entregado' THEN 'entregado'
			WHEN estado = 'en_camino' THEN 'retirado' WHEN id_repartidor IS NOT NULL THEN 'asignado' ELSE 'sin_asignar' END
			WHERE estado_entrega = 'sin_asignar' AND (estado <> 'preparando' OR id_repartidor IS NOT NULL)");
		$this->db->query("CREATE INDEX IF NOT EXISTS ix_delivery_pedido_repartidor_estado ON public.delivery_pedido (id_repartidor, estado_entrega, fecha DESC)");
	}

	public function down()
	{
		// Se conservan estados operativos históricos.
	}
}
