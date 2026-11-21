<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * v1.28.0 (PRUEBA): pantalla de cocina (Cocina). Muestra lo que se mandó a preparar y todavía no está "Listo".
 *
 * - Mesas y delivery: detalle_cotizacion (cada envío a la mesa = una comanda: misma cotización y misma hora de impresión).
 * - Venta directa: detalle_venta de ventas directas no anuladas (cada venta = una comanda).
 * - Solo lo de las últimas HORAS horas, lo que no está anulado, y lo que sale en la comanda impresa (los menús salen por
 *   sus productos elegidos; un producto del menú con "Imprimir" apagado no sale).
 * - Destino: el de impresión de cada producto (COCINA, BARRA...). La pantalla filtra por uno o muestra todos.
 * - "Listo" se guarda en cocina_listo_web (tabla propia): no toca las columnas de cocina del POS de escritorio.
 */
class Mcocina extends CI_Model
{
	const HORAS = 6;
	const TZ = 'America/Santiago';

	private function db()
	{
		$this->load->helper('tenant');
		return get_tenant_db($this);
	}

	/** ¿Corrió la migración 20261121130000? */
	public function disponible()
	{
		return $this->db()->table_exists('cocina_listo_web');
	}

	/** Destinos de impresión (para el filtro de la pantalla). */
	public function destinos()
	{
		return array_map(function ($r) { return array('id' => (int)$r->id_dimpresion, 'nombre' => $r->nombre_dimpresion); },
			$this->db()->query('SELECT id_dimpresion, nombre_dimpresion FROM dimpresion ORDER BY nombre_dimpresion')->result());
	}

	/**
	 * Comandas pendientes, la más antigua primero.
	 * @param int $idDestino 0 = todos los destinos
	 * @return array [['clave', 'origen', 'titulo', 'detalle', 'segundos', 'lineas' => [['id', 'cantidad', 'nombre', 'nota', 'destino']]]]
	 */
	public function pendientes($idDestino = 0)
	{
		$db = $this->db();
		if (!$this->disponible()) return array();
		$db->query('SET TIME ZONE ' . $db->escape(self::TZ));
		$idDestino = (int)$idDestino;
		$desde = date('Y-m-d H:i:s', time() - self::HORAS * 3600);
		$destinoProducto = 'COALESCE(NULLIF(p.id_dimpresion, 0), NULLIF(f.id_dimpresion, 0), 0)';
		$filtroC = $idDestino > 0 ? ' AND COALESCE(NULLIF(d.id_dimpresion, 0), ' . $destinoProducto . ') = ' . $idDestino : '';
		$filtroV = $idDestino > 0 ? ' AND ' . $destinoProducto . ' = ' . $idDestino : '';

		// Mesas y delivery (pedido abierto o ya cobrado: igual hay que prepararlo)
		$mesas = $db->query("SELECT 'c' AS origen, d.id_detalle_cotizacion AS id, d.id_cotizacion AS grupo,
				to_char(d.fecha_hora_impresion, 'YYYY-MM-DD HH24:MI:SS') AS envio, d.cantidad, p.nombre_producto AS nombre,
				COALESCE(d.mensaje, '') AS nota, COALESCE(dp.nombre_dimpresion, '') AS destino,
				COALESCE(c.id_mesa, 0) AS id_mesa, COALESCE(r.nombre_recinto, '') AS recinto, COALESCE(c.esdelivery, false) AS delivery,
				COALESCE(u.nombre_usuario, '') AS garzon, COALESCE(c.observaciones_generales, '') AS mensaje_mesa,
				EXTRACT(EPOCH FROM (now() - d.fecha_hora_impresion))::bigint AS segundos
			FROM detalle_cotizacion d
			JOIN cotizacion c ON c.id_cotizacion = d.id_cotizacion
			JOIN producto p ON p.id_producto = d.id_producto
			LEFT JOIN familia f ON f.id_familia = p.id_familia
			LEFT JOIN dimpresion dp ON dp.id_dimpresion = COALESCE(NULLIF(d.id_dimpresion, 0), $destinoProducto)
			LEFT JOIN mesa m ON m.id_mesa = c.id_mesa
			LEFT JOIN recinto r ON r.id_recinto = m.id_recinto
			LEFT JOIN usuario u ON u.id_usuario = d.id_garzon
			WHERE d.fecha_hora_impresion >= ? AND d.vigente IS NOT FALSE AND d.esanulado IS NOT TRUE AND d.eseliminado IS NOT TRUE
				AND c.esanulado IS NOT TRUE AND c.eseliminado IS NOT TRUE
				AND d.es_menu IS NOT TRUE AND d.imprimir IS NOT FALSE
				AND NOT EXISTS (SELECT 1 FROM cocina_listo_web k WHERE k.origen = 'c' AND k.id_detalle = d.id_detalle_cotizacion)
				$filtroC
			ORDER BY d.fecha_hora_impresion, d.id_detalle_cotizacion", array($desde))->result();

		// Venta directa (cada venta es una comanda)
		$directas = $db->query("SELECT 'v' AS origen, dv.id_detalle_venta AS id, v.id_venta AS grupo,
				to_char(v.fecha_transaccion, 'YYYY-MM-DD HH24:MI:SS') AS envio, dv.cantidad, p.nombre_producto AS nombre,
				'' AS nota, COALESCE(dp.nombre_dimpresion, '') AS destino, v.folio, COALESCE(v.nombre_retira, '') AS retira,
				COALESCE(v.mensaje_pedido, '') AS mensaje_mesa,
				EXTRACT(EPOCH FROM (now() - v.fecha_transaccion))::bigint AS segundos
			FROM detalle_venta dv
			JOIN venta v ON v.id_venta = dv.id_venta
			JOIN producto p ON p.id_producto = dv.id_producto
			LEFT JOIN familia f ON f.id_familia = p.id_familia
			LEFT JOIN dimpresion dp ON dp.id_dimpresion = $destinoProducto
			WHERE v.fecha_transaccion >= ? AND COALESCE(v.venta_directa, false) AND COALESCE(v.id_mesa, 0) = 0
				AND COALESCE(v.mensaje_pedido, '') NOT LIKE 'ANULADA%' AND dv.es_menu IS NOT TRUE
				AND NOT EXISTS (SELECT 1 FROM cocina_listo_web k WHERE k.origen = 'v' AND k.id_detalle = dv.id_detalle_venta)
				$filtroV
			ORDER BY v.fecha_transaccion, dv.id_detalle_venta", array($desde))->result();

		$comandas = array();
		foreach (array_merge($mesas, $directas) as $l) {
			$clave = $l->origen . ':' . $l->grupo . ':' . $l->envio;
			if (!isset($comandas[$clave])) {
				if ($l->origen === 'c') {
					$l->delivery = ($l->delivery === 't' || $l->delivery === true);   // PostgreSQL entrega 't' / 'f' (texto)
					$titulo = $l->delivery ? 'Delivery' : ((int)$l->id_mesa ? 'Mesa ' . (int)$l->id_mesa : 'Pedido ' . (int)$l->grupo);
					$detalle = trim(($l->recinto !== '' && !$l->delivery ? $l->recinto : '') . ($l->garzon !== '' ? ' · ' . $l->garzon : ''), ' ·');
				} else {
					$titulo = 'Venta directa N° ' . (int)$l->folio;
					$detalle = $l->retira !== '' ? 'Retira: ' . $l->retira : '';
				}
				$comandas[$clave] = array('clave' => $clave, 'origen' => $l->origen, 'titulo' => $titulo, 'detalle' => $detalle,
					'mensaje' => trim((string)$l->mensaje_mesa), 'segundos' => max(0, (int)$l->segundos), 'lineas' => array());
			}
			$comandas[$clave]['lineas'][] = array('id' => (int)$l->id, 'cantidad' => round((float)$l->cantidad, 3), 'nombre' => $l->nombre,
				'nota' => trim((string)$l->nota), 'destino' => $l->destino);
		}
		$lista = array_values($comandas);
		usort($lista, function ($a, $b) { return $b['segundos'] - $a['segundos']; });
		return $lista;
	}

	/**
	 * Marca listas líneas de una comanda. $lineas = [['origen' => 'c'|'v', 'id' => int], ...]
	 * @return int cuántas quedaron marcadas
	 */
	public function marcarListo($lineas, $idUsuario)
	{
		$db = $this->db();
		$n = 0;
		foreach ((array)$lineas as $l) {
			$origen = isset($l['origen']) && in_array($l['origen'], array('c', 'v'), true) ? $l['origen'] : null;
			$id = isset($l['id']) ? (int)$l['id'] : 0;
			if (!$origen || $id <= 0) continue;
			$db->query('INSERT INTO cocina_listo_web (origen, id_detalle, id_usuario) VALUES (?, ?, ?) ON CONFLICT DO NOTHING',
				array($origen, $id, (int)$idUsuario));
			$n += $db->affected_rows();
		}
		return $n;
	}

	/** Deshace un "Listo" (se tocó por error): las líneas vuelven a la pantalla. */
	public function deshacer($lineas)
	{
		$db = $this->db();
		$n = 0;
		foreach ((array)$lineas as $l) {
			$origen = isset($l['origen']) && in_array($l['origen'], array('c', 'v'), true) ? $l['origen'] : null;
			$id = isset($l['id']) ? (int)$l['id'] : 0;
			if (!$origen || $id <= 0) continue;
			$db->query('DELETE FROM cocina_listo_web WHERE origen = ? AND id_detalle = ?', array($origen, $id));
			$n += $db->affected_rows();
		}
		// Limpieza: lo de más de 2 días ya no sirve
		$db->query("DELETE FROM cocina_listo_web WHERE listo < now() - interval '2 days'");
		return $n;
	}
}
