<?php
defined('BASEPATH') or exit('No direct script access allowed');

/** Etapa 2 de Delivery: estado independiente de preparación y bitácora de eventos no destructiva. */
class Migration_delivery_cocina_auditoria extends CI_Migration
{
	public function up()
	{
		if (!$this->existeTabla('delivery_pedido')) {
			throw new RuntimeException(__METHOD__ . ': falta delivery_pedido. Se reintenta en la próxima ejecución.');
		}
		$this->ejecutar('ALTER TABLE public.delivery_pedido ADD COLUMN IF NOT EXISTS estado_preparacion character varying(16)', 'ADD estado_preparacion');
		$this->ejecutar("UPDATE public.delivery_pedido SET estado_preparacion = CASE WHEN estado = 'anulado' THEN 'cancelado' WHEN estado = 'entregado' THEN 'listo' ELSE 'pendiente' END WHERE estado_preparacion IS NULL",
			'BACKFILL estado_preparacion');
		$this->ejecutar("CREATE TABLE IF NOT EXISTS public.delivery_evento (
			id_delivery_evento serial PRIMARY KEY,
			id_delivery integer NOT NULL,
			fecha timestamp with time zone NOT NULL DEFAULT now(),
			id_usuario integer NOT NULL DEFAULT 0,
			accion character varying(40) NOT NULL,
			datos jsonb NOT NULL DEFAULT '{}'::jsonb
		)", 'CREATE delivery_evento');
		$this->ejecutar('CREATE INDEX IF NOT EXISTS ix_delivery_evento_pedido_fecha ON public.delivery_evento (id_delivery, fecha, id_delivery_evento)',
			'INDEX delivery_evento.pedido_fecha');
		if (!$this->existeColumna('delivery_pedido', 'estado_preparacion') || !$this->existeColumna('delivery_evento', 'accion')) {
			throw new RuntimeException(__METHOD__ . ': faltan objetos después de la migración. Se reintenta en la próxima ejecución.');
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
		// No se eliminan pedidos ni eventos históricos.
	}
}
