<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * v1.28.0: pantalla de cocina (Cocina). Muestra lo que se mandó a preparar y todavía no está "Listo".
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

	/** ¿Corrieron las migraciones 20261121130000 y 20261121140000? */
	public function disponible()
	{
		return $this->db()->table_exists('cocina_listo_web') && $this->db()->table_exists('cocina_toma_web');
	}

	/** v1.29.0: ¿está encendida en Configuraciones > Cocina y reservas? */
	public function activa()
	{
		$this->load->helper('permisos_web');
		return opcion_config_activa($this, 'usar_cocina_web');
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
		// v1.30.0: un producto con "Imprimir también en otro destino" se ve en los dos (ej. COCINA y PARRILLA)
		$conDestino2 = $db->field_exists('id_dimpresion2_web', 'producto');
		// v1.45.0: la fila de una promo (normal o de sushi) tiene sus propias marcas; como la del menú, no va a cocina
		$sinPromoC = $db->field_exists('es_promo_web', 'detalle_cotizacion') ? ' AND d.es_promo_web IS NOT TRUE AND d.es_promo_sushi_web IS NOT TRUE' : '';
		$sinPromoV = $db->field_exists('es_promo_web', 'detalle_venta') ? ' AND dv.es_promo_web IS NOT TRUE AND dv.es_promo_sushi_web IS NOT TRUE' : '';
		$o2 = $conDestino2 ? ' OR COALESCE(p.id_dimpresion2_web, 0) = ' . $idDestino : '';
		$filtroC = $idDestino > 0 ? ' AND (COALESCE(NULLIF(d.id_dimpresion, 0), ' . $destinoProducto . ') = ' . $idDestino . $o2 . ')' : '';
		$filtroV = $idDestino > 0 ? ' AND (' . $destinoProducto . ' = ' . $idDestino . $o2 . ')' : '';
		$selD2 = $conDestino2 ? "COALESCE((SELECT nombre_dimpresion FROM dimpresion WHERE id_dimpresion = NULLIF(p.id_dimpresion2_web, 0)), '')" : "''";
		// v1.29.0: el mismo nombre de mesa que la Caja (nombre propio del plano, o el del tipo de mesa, ej. "Mesa D 2")
		$this->load->model('Mtipomesa');
		$joinMesa = $this->Mtipomesa->joinSql('m', 'tmw');
		$nombreMesa = $this->Mtipomesa->nombreSql('m', 'tmw');
		if ($db->table_exists('mesa_plano') && $db->field_exists('nombre', 'mesa_plano')) {
			$joinMesa .= ' LEFT JOIN mesa_plano pl ON pl.id_mesa = m.id_mesa';
			$nombreMesa = "COALESCE(NULLIF(trim(pl.nombre), ''), $nombreMesa)";
		}

		// Mesas y delivery (pedido abierto o ya cobrado: igual hay que prepararlo)
		$mesas = $db->query("SELECT 'c' AS origen, d.id_detalle_cotizacion AS id, d.id_cotizacion AS grupo,
				to_char(d.fecha_hora_impresion, 'YYYY-MM-DD HH24:MI:SS') AS envio, d.cantidad, p.nombre_producto AS nombre,
				COALESCE(d.mensaje, '') AS nota, COALESCE(dp.nombre_dimpresion, '') AS destino, $selD2 AS destino2,
				COALESCE((SELECT pa.nombre_producto FROM producto pa WHERE pa.id_producto = d.id_agregado AND d.id_agregado > 0), '') AS agregado,
				COALESCE(c.id_mesa, 0) AS id_mesa, COALESCE(r.nombre_recinto, '') AS recinto, COALESCE($nombreMesa, '') AS nombre_mesa, COALESCE(c.esdelivery, false) AS delivery,
				COALESCE(u.nombre_usuario, '') AS garzon, COALESCE(c.observaciones_generales, '') AS mensaje_mesa,
				EXTRACT(EPOCH FROM (now() - d.fecha_hora_impresion))::bigint AS segundos,
				COALESCE(t.nombre, '') AS tomado_por, COALESCE(t.id_usuario, 0) AS tomado_id,
				COALESCE(EXTRACT(EPOCH FROM (now() - t.desde))::bigint, 0) AS tomado_seg
			FROM detalle_cotizacion d
			LEFT JOIN cocina_toma_web t ON t.origen = 'c' AND t.id_detalle = d.id_detalle_cotizacion
			JOIN cotizacion c ON c.id_cotizacion = d.id_cotizacion
			JOIN producto p ON p.id_producto = d.id_producto
			LEFT JOIN familia f ON f.id_familia = p.id_familia
			LEFT JOIN dimpresion dp ON dp.id_dimpresion = COALESCE(NULLIF(d.id_dimpresion, 0), $destinoProducto)
			LEFT JOIN mesa m ON m.id_mesa = c.id_mesa
			LEFT JOIN recinto r ON r.id_recinto = m.id_recinto
			$joinMesa
			LEFT JOIN usuario u ON u.id_usuario = d.id_garzon
			WHERE d.fecha_hora_impresion >= ? AND d.vigente IS NOT FALSE AND d.esanulado IS NOT TRUE AND d.eseliminado IS NOT TRUE
				AND c.esanulado IS NOT TRUE AND c.eseliminado IS NOT TRUE
				AND d.es_menu IS NOT TRUE AND d.imprimir IS NOT FALSE $sinPromoC
				AND NOT EXISTS (SELECT 1 FROM cocina_listo_web k WHERE k.origen = 'c' AND k.id_detalle = d.id_detalle_cotizacion)
				$filtroC
			ORDER BY d.fecha_hora_impresion, d.id_detalle_cotizacion", array($desde))->result();

		// Venta directa (cada venta es una comanda)
		$directas = $db->query("SELECT 'v' AS origen, dv.id_detalle_venta AS id, v.id_venta AS grupo,
				to_char(v.fecha_transaccion, 'YYYY-MM-DD HH24:MI:SS') AS envio, dv.cantidad, p.nombre_producto AS nombre,
				'' AS nota, COALESCE(dp.nombre_dimpresion, '') AS destino, $selD2 AS destino2,
				COALESCE((SELECT pa.nombre_producto FROM producto pa WHERE pa.id_producto = dv.id_agregado AND dv.id_agregado > 0), '') AS agregado, v.folio, COALESCE(v.nombre_retira, '') AS retira,
				COALESCE(v.mensaje_pedido, '') AS mensaje_mesa,
				EXTRACT(EPOCH FROM (now() - v.fecha_transaccion))::bigint AS segundos,
				COALESCE(t.nombre, '') AS tomado_por, COALESCE(t.id_usuario, 0) AS tomado_id,
				COALESCE(EXTRACT(EPOCH FROM (now() - t.desde))::bigint, 0) AS tomado_seg
			FROM detalle_venta dv
			LEFT JOIN cocina_toma_web t ON t.origen = 'v' AND t.id_detalle = dv.id_detalle_venta
			JOIN venta v ON v.id_venta = dv.id_venta
			JOIN producto p ON p.id_producto = dv.id_producto
			LEFT JOIN familia f ON f.id_familia = p.id_familia
			LEFT JOIN dimpresion dp ON dp.id_dimpresion = $destinoProducto
			WHERE v.fecha_transaccion >= ? AND COALESCE(v.venta_directa, false) AND COALESCE(v.id_mesa, 0) = 0
				AND COALESCE(v.mensaje_pedido, '') NOT LIKE 'ANULADA%' AND dv.es_menu IS NOT TRUE $sinPromoV
				AND NOT EXISTS (SELECT 1 FROM cocina_listo_web k WHERE k.origen = 'v' AND k.id_detalle = dv.id_detalle_venta)
				$filtroV
			ORDER BY v.fecha_transaccion, dv.id_detalle_venta", array($desde))->result();

		$comandas = array();
		foreach (array_merge($mesas, $directas) as $l) {
			$clave = $l->origen . ':' . $l->grupo . ':' . $l->envio;
			if (!isset($comandas[$clave])) {
				if ($l->origen === 'c') {
					$l->delivery = ($l->delivery === 't' || $l->delivery === true);   // PostgreSQL entrega 't' / 'f' (texto)
					$titulo = $l->delivery ? 'Delivery' : ((int)$l->id_mesa ? ($l->nombre_mesa !== '' ? $l->nombre_mesa : 'Mesa ' . (int)$l->id_mesa) : 'Pedido ' . (int)$l->grupo);
					$detalle = trim(($l->recinto !== '' && !$l->delivery ? $l->recinto : '') . ($l->garzon !== '' ? ' · ' . $l->garzon : ''), ' ·');
				} else {
					$titulo = 'Venta directa N° ' . (int)$l->folio;
					$detalle = $l->retira !== '' ? 'Retira: ' . $l->retira : '';
				}
				$comandas[$clave] = array('clave' => $clave, 'origen' => $l->origen, 'titulo' => $titulo, 'detalle' => $detalle,
					'mensaje' => trim((string)$l->mensaje_mesa), 'segundos' => max(0, (int)$l->segundos), 'lineas' => array(),
					'tomado_por' => '', 'tomado_id' => 0, 'tomado_seg' => 0);
			}
			// v1.29.0: "Siendo preparado por": quien tomó alguna línea de la comanda
			if ($l->tomado_por !== '' && $comandas[$clave]['tomado_por'] === '') {
				$comandas[$clave]['tomado_por'] = $l->tomado_por;
				$comandas[$clave]['tomado_id'] = (int)$l->tomado_id;
				$comandas[$clave]['tomado_seg'] = max(0, (int)$l->tomado_seg);
			}
			// v1.30.0: el primer agregado va en la misma fila (antes no se veía su nombre) y "también en" el segundo destino
			$destinoTxt = $l->destino . ($l->destino2 !== '' && $l->destino2 !== $l->destino ? ' + ' . $l->destino2 : '');
			$comandas[$clave]['lineas'][] = array('id' => (int)$l->id, 'cantidad' => round((float)$l->cantidad, 3),
				'nombre' => $l->nombre . ($l->agregado !== '' ? ' + ' . $l->agregado : ''),
				'nota' => trim((string)$l->nota), 'destino' => $destinoTxt);
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

	/**
	 * v1.29.0: el cocinero toma la comanda ("Siendo preparado por <nombre>"). Si otro ya la tomó, se cambia a quien la
	 * toma ahora (se avisa en pantalla antes de hacerlo). $soltar = true la deja libre de nuevo.
	 */
	public function tomar($lineas, $idUsuario, $nombre, $soltar = false)
	{
		$db = $this->db();
		$n = 0;
		foreach ((array)$lineas as $l) {
			$origen = isset($l['origen']) && in_array($l['origen'], array('c', 'v'), true) ? $l['origen'] : null;
			$id = isset($l['id']) ? (int)$l['id'] : 0;
			if (!$origen || $id <= 0) continue;
			if ($soltar) {
				$db->query('DELETE FROM cocina_toma_web WHERE origen = ? AND id_detalle = ?', array($origen, $id));
			} else {
				$db->query('INSERT INTO cocina_toma_web (origen, id_detalle, id_usuario, nombre) VALUES (?, ?, ?, ?)
					ON CONFLICT (origen, id_detalle) DO UPDATE SET id_usuario = EXCLUDED.id_usuario, nombre = EXCLUDED.nombre, desde = now()',
					array($origen, $id, (int)$idUsuario, mb_substr((string)$nombre, 0, 60)));
			}
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
		$db->query("DELETE FROM cocina_toma_web WHERE desde < now() - interval '2 days'");
		return $n;
	}
}
