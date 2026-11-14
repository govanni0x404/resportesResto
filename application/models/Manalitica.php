<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Panel "Análisis del período" del Dashboard (10-11-2026). Solo lectura.
 *
 * Mismo criterio de período que el resto del Dashboard: las cajas cuya fecha está entre desde y hasta (y una caja
 * si se elige). Qué cuenta como venta:
 *  - Venta bruta / documentos: no anulados, no cortesía y no notas de crédito.
 *  - Venta neta: la bruta menos las devoluciones (notas de crédito), como el resumen de la Caja.
 *  - Cortesía: venta marcada es_cortesia o pagada con una forma de pago de cortesía.
 *  - Anulada: mensaje_pedido "ANULADA ($monto): motivo" (la anulación deja los montos en 0; el monto sale del mensaje).
 * Las columnas que no todas las bases tienen (garzón, venta directa, pedido de la mesa) se preguntan al catálogo.
 */
class Manalitica extends CI_Model
{
	const NC_INTERNA = 'NOTA DE CREDITO INTERNA';
	const COD_SII_NC = 61;

	private $db2;
	private $cols = array();

	public function analitica($idCaja, $desde, $hasta)
	{
		$this->load->helper('tenant');
		$this->db2 = get_tenant_db($this);
		$this->db2->db_debug = false;   // los errores se lanzan como excepción (ver q) y el controlador responde JSON

		$de = $this->fecha($desde);
		$a = $this->fecha($hasta);
		if ($a < $de) {
			$t = $de; $de = $a; $a = $t;
		}
		$dias = (int)round((strtotime($a) - strtotime($de)) / 86400) + 1;
		$prevA = date('Y-m-d', strtotime($de . ' -1 day'));
		$prevDe = date('Y-m-d', strtotime($de . ' -' . $dias . ' day'));
		$idCaja = (int)$idCaja;

		$actual = $this->totales($de, $a, $idCaja);
		$anterior = $this->totales($prevDe, $prevA, $idCaja);

		list($cte, $binds) = $this->base($de, $a, $idCaja);
		$valida = 'NOT b.anulada AND NOT b.cortesia AND NOT b.es_nc';

		$porHora = $this->q("$cte SELECT EXTRACT(HOUR FROM b.fecha_transaccion)::int AS h, round(sum(b.total)) AS monto, count(*) AS docs
			FROM base b WHERE $valida AND b.fecha_transaccion IS NOT NULL GROUP BY 1 ORDER BY 1", $binds);
		$porDia = $this->q("$cte SELECT to_char(b.dia, 'YYYY-MM-DD') AS dia, round(sum(b.total)) AS monto, count(*) AS docs
			FROM base b WHERE $valida GROUP BY b.dia ORDER BY b.dia", $binds);

		// Productos (y familias a partir de ellos)
		$productos = $this->q("$cte SELECT dv.id_producto, COALESCE(p.nombre_producto, 'Producto ' || dv.id_producto) AS nombre,
				COALESCE(p.id_familia, 0) AS id_familia, COALESCE(f.nombre_familia, 'Sin familia') AS familia,
				sum(COALESCE(dv.cantidad, 0)) AS cantidad, round(sum(COALESCE(dv.total_bruto_con_descuento, 0))) AS monto
			FROM base b
			JOIN detalle_venta dv ON dv.id_venta = b.id_venta
			LEFT JOIN producto p ON p.id_producto = dv.id_producto
			LEFT JOIN familia f ON f.id_familia = p.id_familia
			WHERE $valida
			GROUP BY dv.id_producto, p.nombre_producto, p.id_familia, f.nombre_familia
			ORDER BY monto DESC, cantidad DESC", $binds);
		$familias = array();
		foreach ($productos as $p) {
			$k = (int)$p['id_familia'];
			if (!isset($familias[$k])) $familias[$k] = array('nombre' => $p['familia'], 'monto' => 0, 'cantidad' => 0, 'productos' => 0);
			$familias[$k]['monto'] += (float)$p['monto'];
			$familias[$k]['cantidad'] += (float)$p['cantidad'];
			$familias[$k]['productos']++;
		}
		usort($familias, function ($x, $y) { return $y['monto'] <=> $x['monto']; });
		$conVenta = array_values(array_filter($productos, function ($p) { return (float)$p['cantidad'] > 0; }));
		$menos = array_slice(array_reverse($conVenta), 0, 5);
		$sinVenta = $this->q("$cte SELECT count(*) AS n FROM producto p
			WHERE p.vigente IS TRUE AND p.venta_publico IS NOT FALSE
			  AND NOT EXISTS (SELECT 1 FROM base b JOIN detalle_venta dv ON dv.id_venta = b.id_venta
			                  WHERE $valida AND dv.id_producto = p.id_producto)", $binds);

		// Garzones
		$garzones = $this->q("$cte SELECT b.id_garzon, COALESCE(u.nombre_usuario, 'Sin asignar') AS nombre, round(sum(b.total)) AS venta,
				count(*) AS docs, round(sum(b.propina)) AS propina, count(*) FILTER (WHERE b.es_mesa) AS docs_mesa
			FROM base b LEFT JOIN usuario u ON u.id_usuario = b.id_garzon
			WHERE $valida GROUP BY b.id_garzon, u.nombre_usuario ORDER BY venta DESC", $binds);

		// Formas de pago (lo cobrado sin la propina, como el resumen de la Caja)
		$formas = $this->q("$cte SELECT f.nombre_fpago AS nombre, round(sum(COALESCE(pv.monto_pagado, 0) - COALESCE(pv.propina, 0))) AS monto,
				count(DISTINCT pv.id_venta) AS docs
			FROM base b JOIN pago_venta pv ON pv.id_venta = b.id_venta JOIN fpago f ON f.id_fpago = pv.id_fpago
			WHERE $valida AND f.escortesia IS NOT TRUE
			GROUP BY f.nombre_fpago ORDER BY monto DESC", $binds);

		return array(
			'periodo' => array('desde' => $de, 'hasta' => $a, 'dias' => $dias, 'anterior_desde' => $prevDe, 'anterior_hasta' => $prevA),
			'actual' => $actual,
			'anterior' => $anterior,
			'por_hora' => $this->numeros($porHora),
			'por_dia' => $this->numeros($porDia, array('dia')),
			'productos' => array(
				'top' => $this->numeros(array_slice($productos, 0, 10), array('nombre', 'familia')),
				'menos' => $this->numeros($menos, array('nombre', 'familia')),
				'con_venta' => count($conVenta),
				'sin_venta' => (int)$sinVenta[0]['n'],
			),
			'familias' => array_values($familias),
			'garzones' => $this->numeros($garzones, array('nombre')),
			'formas_pago' => $this->numeros($formas, array('nombre')),
			'mesas' => $this->mesas($cte, $binds, $valida),
		);
	}

	/** Totales del período (también para el período anterior, para comparar). */
	private function totales($de, $a, $idCaja)
	{
		list($cte, $binds) = $this->base($de, $a, $idCaja);
		$r = $this->q("$cte SELECT
				COALESCE(sum(total) FILTER (WHERE NOT anulada AND NOT cortesia AND NOT es_nc), 0) AS venta_bruta,
				count(*) FILTER (WHERE NOT anulada AND NOT cortesia AND NOT es_nc) AS documentos,
				COALESCE(sum(abs(total)) FILTER (WHERE NOT anulada AND es_nc), 0) AS devoluciones,
				count(*) FILTER (WHERE NOT anulada AND es_nc) AS docs_devolucion,
				COALESCE(sum(propina) FILTER (WHERE NOT anulada AND NOT es_nc), 0) AS propinas,
				COALESCE(sum(descuento) FILTER (WHERE NOT anulada AND NOT es_nc), 0) AS descuentos,
				count(*) FILTER (WHERE NOT anulada AND NOT es_nc AND descuento > 0) AS docs_descuento,
				COALESCE(sum(total) FILTER (WHERE NOT anulada AND cortesia AND NOT es_nc), 0) AS cortesias,
				count(*) FILTER (WHERE NOT anulada AND cortesia AND NOT es_nc) AS docs_cortesia,
				COALESCE(sum(monto_anulado) FILTER (WHERE anulada), 0) AS anulado,
				count(*) FILTER (WHERE anulada) AS docs_anulados,
				COALESCE(sum(total) FILTER (WHERE NOT anulada AND NOT cortesia AND NOT es_nc AND es_mesa), 0) AS venta_mesa,
				count(*) FILTER (WHERE NOT anulada AND NOT cortesia AND NOT es_nc AND es_mesa) AS docs_mesa,
				COALESCE(sum(total) FILTER (WHERE NOT anulada AND NOT cortesia AND NOT es_nc AND NOT es_mesa), 0) AS venta_directa,
				count(*) FILTER (WHERE NOT anulada AND NOT cortesia AND NOT es_nc AND NOT es_mesa) AS docs_directa
			FROM base", $binds);
		$t = array();
		foreach ($r[0] as $k => $v) $t[$k] = (int)round((float)$v);
		$t['venta_neta'] = $t['venta_bruta'] - $t['devoluciones'];
		$t['ticket_promedio'] = $t['documentos'] > 0 ? (int)round($t['venta_bruta'] / $t['documentos']) : 0;
		return $t;
	}

	/** Mesas atendidas (pedidos de mesa cobrados) y tiempo promedio desde el primer pedido hasta el cobro. */
	private function mesas($cte, $binds, $valida)
	{
		$res = array('mesas_atendidas' => 0, 'tiempo_promedio_seg' => null);
		$v = $this->columnasDe('venta');
		$c = $this->columnasDe('cotizacion');
		if (!isset($v['folio_cotizacion'], $c['fecha_transaccion'])) return $res;
		$padre = isset($c['id_divpedido'])
			? 'LEFT JOIN cotizacion cp ON cp.id_cotizacion = NULLIF(c.id_divpedido, 0)'
			: 'LEFT JOIN cotizacion cp ON false';
		// Un pedido dividido (cobro de una parte) se cuenta y se mide por su pedido de mesa original
		$r = $this->q("$cte SELECT count(DISTINCT COALESCE(cp.id_cotizacion, c.id_cotizacion)) AS mesas,
				avg(EXTRACT(EPOCH FROM (b.fecha_transaccion - COALESCE(cp.fecha_transaccion, c.fecha_transaccion))))
					FILTER (WHERE b.fecha_transaccion - COALESCE(cp.fecha_transaccion, c.fecha_transaccion) BETWEEN interval '1 minute' AND interval '12 hours') AS seg
			FROM base b
			JOIN cotizacion c ON c.id_cotizacion = b.folio_cotizacion
			$padre
			WHERE $valida AND b.es_mesa AND b.folio_cotizacion > 0", $binds);
		$res['mesas_atendidas'] = (int)$r[0]['mesas'];
		$res['tiempo_promedio_seg'] = $r[0]['seg'] === null ? null : (int)round((float)$r[0]['seg']);
		return $res;
	}

	/**
	 * CTE con una fila por venta del período y sus marcas (anulada, cortesía, NC, mesa, garzón).
	 * Sin "?" fuera de los parámetros: CodeIgniter los toma como marcadores.
	 */
	private function base($de, $a, $idCaja)
	{
		$v = $this->columnasDe('venta');
		$nc = $this->idsNotaCredito();
		$enNc = $nc ? 'IN (' . implode(', ', $nc) . ')' : 'IN (-1)';
		$directa = $this->mesaVentaDirecta();
		$mesa = '(COALESCE(v.id_mesa, 0) > 0 AND v.id_mesa <> ' . $directa
			. (isset($v['venta_directa']) ? ' AND v.venta_directa IS NOT TRUE' : '') . ')';
		$garzon = isset($v['id_usuario_garzon']) ? 'COALESCE(NULLIF(v.id_usuario_garzon, 0), v.id_usuario)' : 'v.id_usuario';
		$folioCot = isset($v['folio_cotizacion']) ? 'COALESCE(v.folio_cotizacion, 0)' : '0';
		$cortesia = isset($v['es_cortesia']) ? 'v.es_cortesia IS TRUE' : 'false';
		$filtroCaja = $idCaja > 0 ? ' AND v.id_caja = ' . (int)$idCaja : '';
		$sql = "WITH pagos AS (
				SELECT pv.id_venta, bool_or(f.escortesia IS TRUE) AS cortesia_pago
				FROM pago_venta pv JOIN fpago f ON f.id_fpago = pv.id_fpago
				WHERE pv.id_venta IN (SELECT v0.id_venta FROM venta v0 JOIN caja c0 ON c0.id_caja = v0.id_caja
					WHERE c0.fecha::date BETWEEN ? AND ?" . str_replace('v.id_caja', 'v0.id_caja', $filtroCaja) . ")
				GROUP BY pv.id_venta
			), base AS (
				SELECT v.id_venta, v.id_caja, v.fecha_transaccion, ca.fecha::date AS dia,
					COALESCE(v.total, 0) AS total, COALESCE(v.valor_propina, 0) AS propina, COALESCE(v.monto_descuento, 0) AS descuento,
					(COALESCE(v.mensaje_pedido, '') LIKE 'ANULADA%') AS anulada,
					COALESCE(substring(v.mensaje_pedido from '^ANULADA \\(\\$(-{0,1}[0-9]+)'), '0')::numeric AS monto_anulado,
					($cortesia OR COALESCE(p.cortesia_pago, false)) AS cortesia,
					(v.id_tdocumento $enNc) AS es_nc,
					$mesa AS es_mesa,
					$garzon AS id_garzon,
					$folioCot AS folio_cotizacion
				FROM venta v
				JOIN caja ca ON ca.id_caja = v.id_caja
				LEFT JOIN pagos p ON p.id_venta = v.id_venta
				WHERE ca.fecha::date BETWEEN ? AND ?$filtroCaja
			)";
		return array($sql, array($de, $a, $de, $a));
	}

	private function idsNotaCredito()
	{
		$ids = array();
		foreach ($this->q('SELECT id_tdocumento FROM tdocumento WHERE cod_sii = ? OR upper(trim(nombre_tdocumento)) = ?',
			array(self::COD_SII_NC, self::NC_INTERNA)) as $r) {
			$ids[] = (int)$r['id_tdocumento'];
		}
		return array_values(array_unique($ids));
	}

	/** Mesa que el POS usa para la venta directa (configuracion.id_mesa_venta_directa), si existe. */
	private function mesaVentaDirecta()
	{
		$c = $this->columnasDe('configuracion');
		if (!isset($c['id_mesa_venta_directa'])) return 0;
		$r = $this->q('SELECT COALESCE(id_mesa_venta_directa, 0) AS id FROM configuracion LIMIT 1');
		return $r ? (int)$r[0]['id'] : 0;
	}

	private function columnasDe($tabla)
	{
		if (!isset($this->cols[$tabla])) {
			$this->cols[$tabla] = array();
			foreach ($this->q("SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = ?", array($tabla)) as $r) {
				$this->cols[$tabla][$r['column_name']] = true;
			}
		}
		return $this->cols[$tabla];
	}

	/** dd-mm-aaaa (como lo manda el Dashboard) o aaaa-mm-dd -> aaaa-mm-dd; si no se entiende, hoy. */
	private function fecha($s)
	{
		$s = trim((string)$s);
		foreach (array('d-m-Y', 'd/m/Y', 'Y-m-d') as $f) {
			$d = DateTime::createFromFormat('!' . $f, $s);
			if ($d && $d->format($f) === $s) return $d->format('Y-m-d');
		}
		return date('Y-m-d');
	}

	/** Los montos y cantidades llegan como texto desde PostgreSQL: a número, salvo las columnas de texto. */
	private function numeros(array $filas, array $texto = array())
	{
		foreach ($filas as &$f) {
			foreach ($f as $k => $v) {
				if (!in_array($k, $texto, true) && is_numeric($v)) $f[$k] = $v + 0;
			}
		}
		return $filas;
	}

	private function q($sql, $binds = array())
	{
		$r = $this->db2->query($sql, $binds);
		if ($r === false) {
			$e = $this->db2->error();
			log_message('error', 'Manalitica: ' . $e['message']);
			throw new RuntimeException('No se pudo calcular el análisis del período.');
		}
		return $r->result_array();
	}
}
