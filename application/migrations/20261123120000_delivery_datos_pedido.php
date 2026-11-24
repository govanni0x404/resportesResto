<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Etapa 1 de Delivery: registra el origen y el medio de pago previsto del pedido, y permite reintentos seguros
 * del guardado mediante una clave idempotente. No crea ventas ni movimientos de caja.
 */
class Migration_delivery_datos_pedido extends CI_Migration
{
	public function up()
	{
		if (!$this->existeTabla('delivery_pedido')) {
			throw new RuntimeException(__METHOD__ . ': falta delivery_pedido (migración 20261118100000). Se reintenta en la próxima ejecución.');
		}
		$this->ejecutar('ALTER TABLE public.delivery_pedido ADD COLUMN IF NOT EXISTS solicitud_id character varying(64)', 'ADD solicitud_id');
		$this->ejecutar('ALTER TABLE public.delivery_pedido ADD COLUMN IF NOT EXISTS origen character varying(20)', 'ADD origen');
		$this->ejecutar('ALTER TABLE public.delivery_pedido ADD COLUMN IF NOT EXISTS medio_pago_previsto character varying(30)', 'ADD medio_pago_previsto');
		$this->ejecutar('ALTER TABLE public.delivery_pedido ADD COLUMN IF NOT EXISTS comanda_snapshot jsonb', 'ADD comanda_snapshot');
		$this->ejecutar("UPDATE public.delivery_pedido SET origen = 'manual' WHERE origen IS NULL", 'BACKFILL origen');
		$this->ejecutar('CREATE UNIQUE INDEX IF NOT EXISTS ux_delivery_pedido_solicitud ON public.delivery_pedido (solicitud_id) WHERE solicitud_id IS NOT NULL',
			'INDEX solicitud_id');
		foreach (array('solicitud_id', 'origen', 'medio_pago_previsto', 'comanda_snapshot') as $columna) {
			if (!$this->existeColumna('delivery_pedido', $columna)) {
				throw new RuntimeException(__METHOD__ . ": falta delivery_pedido.$columna después de crearla. Se reintenta en la próxima ejecución.");
			}
		}
	}

	private function existeTabla($tabla)
	{
		$r = $this->db->query("SELECT count(*) AS n FROM information_schema.tables WHERE table_schema = 'public' AND table_name = ?", array($tabla));
		$f = $r ? $r->row() : null;
		return $f && (int)$f->n > 0;
	}

	private function existeColumna($tabla, $columna)
	{
		$r = $this->db->query("SELECT count(*) AS n FROM information_schema.columns WHERE table_schema = 'public' AND table_name = ? AND column_name = ?",
			array($tabla, $columna));
		$f = $r ? $r->row() : null;
		return $f && (int)$f->n > 0;
	}

	private function ejecutar($sql, $etiqueta)
	{
		try {
			$r = $this->db->query($sql);
		} catch (Throwable $e) {
			throw new RuntimeException(__CLASS__ . ": $etiqueta no se aplicó: " . $e->getMessage());
		}
		if ($r === false) {
			$e = $this->db->error();
			throw new RuntimeException(__CLASS__ . ": $etiqueta no se aplicó: " . (isset($e['message']) ? $e['message'] : 'sin detalle'));
		}
		return $r;
	}

	public function down()
	{
		// No se borran datos de pedidos.
	}
}
