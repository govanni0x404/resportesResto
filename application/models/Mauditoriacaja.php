<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Consultas > Auditoría de caja y Consultas > Anulación de ventas (10-11-2026). Solo lectura.
 *
 * Auditoría de una caja: encabezado, resumen del sistema (el mismo de la Caja: Mcaja::resumenCaja), arqueo (esperado
 * contra rendido por forma de pago), línea de tiempo de los hechos de control y el detalle de cada uno:
 *  - documentos (anulados, notas de crédito, descuentos, cortesías), ingresos y retiros, productos anulados en mesas;
 *  - bitácora de la Caja web (caja_auditoria): ventas en espera creadas, recuperadas y anuladas, productos de mesa
 *    omitidos al salir o recargar, ventas vaciadas y gaveta abierta.
 * Anulación de ventas: ventas en espera anuladas (con su justificación) y documentos anulados, en un período.
 *
 * Las columnas que no todas las bases tienen se preguntan al catálogo; las tablas de la Caja web (migración
 * 20261110210000) también: sin ellas el informe sale igual, sin esa parte.
 */
class Mauditoriacaja extends CI_Model
{
	const TZ = 'America/Santiago';
	const NC_INTERNA = 'NOTA DE CREDITO INTERNA';
	const COD_SII_NC = 61;
	const ORIGENES = array('manual' => 'Manual', 'menu' => 'Al salir de la caja por el menú', 'recarga' => 'Al recargar o cerrar la página',
		'salir_mesa' => 'Al salir de la mesa sin enviar', 'cambio_mesa' => 'Al cambiar de mesa sin enviar');

	private $db2 = null;
	private $cols = array();
	private $orden = 0;

	function __construct()
	{
		parent::__construct();
		date_default_timezone_set(self::TZ);
	}

	// ====================== Auditoría de caja ======================

	/** Cajas abiertas en el período (caja.fecha, como el Dashboard), con sus totales y alertas. */
	public function cajas($desde, $hasta)
	{
		list($de, $a) = $this->periodo($desde, $hasta);
		$c = $this->columnasDe('caja');
		$enNc = $this->sqlNc();
		$anulada = "COALESCE(v.mensaje_pedido, '') LIKE 'ANULADA%'";
		$cortesia = isset($this->columnasDe('venta')['es_cortesia']) ? 'v.es_cortesia IS TRUE' : 'false';
		$diferencia = isset($c['diferencia']) ? 'COALESCE(c.diferencia, 0)' : '0';
		$rendicion = isset($c['fecha_rendicion']) ? "to_char(c.fecha_rendicion, 'DD/MM/YYYY HH24:MI')" : "''";
		$aud = $this->hayAuditoria();
		$selAud = $aud ? "COALESCE(a.esperas, 0) AS esperas, COALESCE(a.esperas_anuladas, 0) AS esperas_anuladas, COALESCE(a.omitidos, 0) AS omitidos,
				COALESCE(a.vaciadas, 0) AS vaciadas, COALESCE(a.gaveta, 0) AS gaveta" : '0 AS esperas, 0 AS esperas_anuladas, 0 AS omitidos, 0 AS vaciadas, 0 AS gaveta';
		$joinAud = $aud ? "LEFT JOIN (SELECT id_caja, count(*) FILTER (WHERE evento = 'espera_creada') AS esperas,
					count(*) FILTER (WHERE evento = 'espera_anulada') AS esperas_anuladas,
					count(*) FILTER (WHERE evento = 'productos_omitidos') AS omitidos,
					count(*) FILTER (WHERE evento = 'venta_vaciada') AS vaciadas,
					count(*) FILTER (WHERE evento = 'gaveta_abierta') AS gaveta
				FROM caja_auditoria WHERE id_caja IN (SELECT id_caja FROM caja WHERE fecha::date BETWEEN ? AND ?) GROUP BY id_caja) a ON a.id_caja = c.id_caja" : '';
		$binds = array($de, $a);
		if ($aud) $binds = array_merge($binds, array($de, $a));
		$binds = array_merge($binds, array($de, $a));
		$filas = $this->q("SELECT c.id_caja, COALESCE(c.nombre_computador, '') AS computador, COALESCE(u.nombre_usuario, '') AS usuario,
				to_char(c.fecha_movimiento, 'DD/MM/YYYY HH24:MI') AS apertura, $rendicion AS cierre, (c.cerrada IS TRUE) AS cerrada,
				$diferencia AS diferencia,
				COALESCE(v.ventas, 0) AS ventas, COALESCE(v.docs, 0) AS docs, COALESCE(v.anuladas, 0) AS anuladas,
				COALESCE(v.nc, 0) AS nc, COALESCE(v.descuentos, 0) AS descuentos, COALESCE(v.cortesias, 0) AS cortesias,
				$selAud
			FROM caja c
			LEFT JOIN usuario u ON u.id_usuario = c.id_usuario
			LEFT JOIN (SELECT v.id_caja,
					COALESCE(sum(v.total) FILTER (WHERE NOT ($anulada) AND NOT ($cortesia)), 0) AS ventas,
					count(*) FILTER (WHERE NOT ($anulada) AND NOT (v.id_tdocumento $enNc)) AS docs,
					count(*) FILTER (WHERE $anulada) AS anuladas,
					count(*) FILTER (WHERE NOT ($anulada) AND v.id_tdocumento $enNc) AS nc,
					count(*) FILTER (WHERE NOT ($anulada) AND COALESCE(v.descuento, 0) > 0 AND NOT (v.id_tdocumento $enNc)) AS descuentos,
					count(*) FILTER (WHERE NOT ($anulada) AND $cortesia) AS cortesias
				FROM venta v WHERE v.id_caja IN (SELECT id_caja FROM caja WHERE fecha::date BETWEEN ? AND ?) GROUP BY v.id_caja) v ON v.id_caja = c.id_caja
			$joinAud
			WHERE c.fecha::date BETWEEN ? AND ?
			ORDER BY c.id_caja DESC LIMIT 500", $binds);
		$res = array();
		foreach ($filas as $f) {
			$f = $this->tipos($f, array('computador', 'usuario', 'apertura', 'cierre'));
			$f['cerrada'] = $f['cerrada'] === 't' || $f['cerrada'] === true;
			$f['web'] = $f['computador'] === 'CAJA WEB';
			$f['alertas'] = $f['anuladas'] + $f['nc'] + $f['esperas_anuladas'] + $f['omitidos'] + $f['vaciadas'] + ($f['cerrada'] && $f['diferencia'] != 0 ? 1 : 0);
			$res[] = $f;
		}
		return array('desde' => $de, 'hasta' => $a, 'cajas' => $res, 'bitacora' => $aud);
	}

	/** Todo lo de una caja. */
	public function detalle($idCaja)
	{
		$idCaja = (int)$idCaja;
		$c = $this->columnasDe('caja');
		$sel = array();
		foreach (array('fecha_rendicion', 'id_usuario_rendicion', 'diferencia', 'efectivo_entregado', 'monto_entregado', 'fondo_fijo', 'fondo_fijo_anterior') as $col) {
			if (isset($c[$col])) $sel[] = 'c.' . $col;
		}
		$caja = $this->q("SELECT c.id_caja, c.id_usuario, c.cerrada, c.fecha_movimiento, COALESCE(c.nombre_computador, '') AS computador,
				to_char(c.fecha_movimiento, 'DD/MM/YYYY HH24:MI') AS apertura, COALESCE(u.nombre_usuario, '') AS usuario"
				. ($sel ? ', ' . implode(', ', $sel) : '') . "
			FROM caja c LEFT JOIN usuario u ON u.id_usuario = c.id_usuario WHERE c.id_caja = ?", array($idCaja));
		if (!$caja) throw new Exception('La caja no existe', 404);
		$caja = (object)$caja[0];
		$cerrada = $caja->cerrada === 't' || $caja->cerrada === true;
		$web = $caja->computador === 'CAJA WEB';
		if (!isset($caja->fondo_fijo)) $caja->fondo_fijo = 0;
		if (!isset($caja->fondo_fijo_anterior)) $caja->fondo_fijo_anterior = 0;
		$cajero = $caja->usuario;
		$usuarioCierre = '';
		if (!empty($caja->id_usuario_rendicion)) {
			$u = $this->q('SELECT nombre_usuario FROM usuario WHERE id_usuario = ?', array((int)$caja->id_usuario_rendicion));
			$usuarioCierre = $u ? (string)$u[0]['nombre_usuario'] : '';
		}

		// Resumen y arqueo con el mismo cálculo que la Caja
		$this->load->model('Mcaja');
		$resumen = $this->Mcaja->resumenCaja($caja);
		$arqueo = array();
		if ($web) {
			$d = $this->Mcaja->datosCierre($idCaja);
			foreach ($d ? $d['formas'] : array() as $f) {
				$arqueo[] = array('nombre' => $f['nombre'], 'efectivo' => (bool)$f['efectivo'], 'esperado' => (int)$f['esperado'],
					'rendido' => $f['rendido'] === null ? null : (int)$f['rendido']);
			}
		} else {
			// Caja del POS de escritorio: lo esperado del sistema y lo rendido al cerrar (detalle_pagocaja)
			$rendido = array();
			if (isset($this->columnasDe('detalle_pagocaja')['id_caja'])) {
				foreach ($this->q('SELECT d.id_fpago, sum(d.monto) AS monto FROM detalle_pagocaja d WHERE d.id_caja = ? GROUP BY d.id_fpago', array($idCaja)) as $r) {
					$rendido[(int)$r['id_fpago']] = (int)$r['monto'];
				}
			}
			foreach ($resumen['por_forma_pago'] as $f) {
				if ($f['cortesia']) continue;
				$arqueo[] = array('nombre' => $f['nombre'], 'efectivo' => (bool)$f['efectivo'],
					'esperado' => $f['efectivo'] ? (int)$resumen['efectivo_esperado'] : (int)($f['monto'] + $f['propina']),
					'rendido' => $cerrada ? (isset($rendido[$f['id_fpago']]) ? $rendido[$f['id_fpago']] : 0) : null);
			}
		}

		$documentos = $this->documentos($idCaja);
		$movimientos = $this->movimientos($idCaja);
		$mesa = $this->anulacionesMesa($idCaja);
		$eventos = $this->eventos($idCaja);
		$esperas = $this->esperasDeCaja($idCaja);

		// Línea de tiempo: solo hechos de control (las ventas normales están en Documentos)
		$t = array();
		$t[] = $this->hito($caja->fecha_movimiento, 'apertura', 'Apertura de caja', 'Fondo inicial ' . $this->pesos($resumen['fondo']), $resumen['fondo'], $cajero, 'info');
		foreach ($movimientos as $m) {
			$t[] = $this->hito($m['fecha'], $m['ingreso'] > 0 ? 'ingreso' : 'retiro', $m['ingreso'] > 0 ? 'Ingreso de caja' : 'Retiro de caja',
				$m['observacion'], $m['ingreso'] > 0 ? $m['ingreso'] : -$m['egreso'], $cajero, 'info');
		}
		foreach ($documentos as $d) {
			$ref = $d['documento'] . ($d['folio'] ? ' N° ' . $d['folio'] : '');
			if ($d['anulada']) {
				$t[] = $this->hito($d['fecha_anulacion'] ?: $d['fecha'], 'anulacion', 'Documento anulado: ' . $ref, $d['motivo'], -$d['monto_anulado'],
					$d['usuario_anulacion'], 'alerta');
				continue;
			}
			if ($d['es_nc']) $t[] = $this->hito($d['fecha'], 'devolucion', 'Devolución: ' . $ref, $d['referencia'], $d['total'], $d['usuario'], 'alerta');
			if ($d['cortesia']) $t[] = $this->hito($d['fecha'], 'cortesia', 'Cortesía: ' . $ref, $d['pagos'], $d['total'], $d['usuario'], 'info');
			if ($d['descuento'] > 0 && !$d['es_nc']) $t[] = $this->hito($d['fecha'], 'descuento', 'Descuento en ' . $ref, 'Total ' . $this->pesos($d['total']), -$d['descuento'], $d['usuario'], 'info');
		}
		foreach ($mesa as $m) {
			$t[] = $this->hito($m['fecha'], 'anulacion_mesa', 'Producto anulado en ' . $m['mesa'] . ': ' . $this->cant($m['cantidad']) . '× ' . $m['producto'],
				$m['motivo'], $m['total'], $m['usuario'], 'alerta');
		}
		foreach ($eventos as $e) {
			$t[] = $this->hito($e['fecha'], $e['evento'], $e['titulo'], $e['texto'], $e['monto'], $e['usuario'], $e['nivel']);
		}
		if ($cerrada && !empty($caja->fecha_rendicion)) {
			$dif = isset($caja->diferencia) ? (int)$caja->diferencia : 0;
			$t[] = $this->hito($caja->fecha_rendicion, 'cierre', 'Cierre de caja', $dif === 0 ? 'Sin diferencia' : 'Diferencia ' . $this->pesos($dif),
				$dif, $usuarioCierre ?: $cajero, $dif === 0 ? 'info' : 'critico');
		}
		usort($t, function ($x, $y) { return strcmp($x['fecha'], $y['fecha']) ?: $x['orden'] - $y['orden']; });

		// Indicadores
		$k = array(
			'anuladas' => 0, 'monto_anulado' => 0, 'descuentos' => 0, 'monto_descuentos' => 0, 'cortesias' => 0, 'monto_cortesias' => 0,
			'nc' => 0, 'monto_nc' => 0, 'anulaciones_mesa' => count($mesa), 'monto_anulaciones_mesa' => 0,
			'esperas' => 0, 'esperas_recuperadas' => 0, 'esperas_anuladas' => 0, 'esperas_pendientes' => 0, 'monto_esperas_anuladas' => 0,
			'omitidos' => 0, 'monto_omitidos' => 0, 'vaciadas' => 0, 'monto_vaciadas' => 0, 'gaveta' => 0,
		);
		foreach ($documentos as $d) {
			if ($d['anulada']) { $k['anuladas']++; $k['monto_anulado'] += $d['monto_anulado']; continue; }
			if ($d['es_nc']) { $k['nc']++; $k['monto_nc'] += abs($d['total']); }
			if ($d['cortesia']) { $k['cortesias']++; $k['monto_cortesias'] += $d['total']; }
			if ($d['descuento'] > 0 && !$d['es_nc']) { $k['descuentos']++; $k['monto_descuentos'] += $d['descuento']; }
		}
		foreach ($mesa as $m) $k['monto_anulaciones_mesa'] += $m['total'];
		foreach ($eventos as $e) {
			if ($e['evento'] === 'productos_omitidos') { $k['omitidos']++; $k['monto_omitidos'] += $e['monto']; }
			if ($e['evento'] === 'venta_vaciada') { $k['vaciadas']++; $k['monto_vaciadas'] += $e['monto']; }
			if ($e['evento'] === 'gaveta_abierta') $k['gaveta']++;
		}
		foreach ($esperas as $e) {
			if ($e['id_caja'] === $idCaja) $k['esperas']++;
			if ($e['estado'] === 'recuperada' && $e['id_caja_cierre'] === $idCaja) $k['esperas_recuperadas']++;
			if ($e['estado'] === 'anulada' && $e['id_caja_cierre'] === $idCaja) { $k['esperas_anuladas']++; $k['monto_esperas_anuladas'] += $e['total']; }
			if ($e['estado'] === 'espera' && $e['id_caja'] === $idCaja) $k['esperas_pendientes']++;
		}

		return array(
			'caja' => array(
				'id_caja' => $idCaja, 'computador' => $caja->computador, 'web' => $web, 'usuario' => $cajero, 'usuario_cierre' => $usuarioCierre,
				'apertura' => $caja->apertura, 'cierre' => $cerrada && !empty($caja->fecha_rendicion) ? date('d/m/Y H:i', strtotime($caja->fecha_rendicion)) : '',
				'cerrada' => $cerrada, 'diferencia' => isset($caja->diferencia) ? (int)$caja->diferencia : 0,
				'entregado' => isset($caja->efectivo_entregado) ? (int)$caja->efectivo_entregado : 0,
			),
			'resumen' => $resumen,
			'arqueo' => $arqueo,
			'indicadores' => $k,
			'linea' => $t,
			'documentos' => $documentos,
			'movimientos' => $movimientos,
			'anulaciones_mesa' => $mesa,
			'eventos' => $eventos,
			'esperas' => $esperas,
			'bitacora' => $this->hayAuditoria(),
		);
	}

	// ====================== Anulación de ventas ======================

	/** Ventas en espera anuladas y documentos anulados del período. $tipo: todas | espera | documento */
	public function anulaciones($desde, $hasta, $tipo)
	{
		list($de, $a) = $this->periodo($desde, $hasta);
		$filas = array();
		if ($tipo !== 'documento' && $this->hayAuditoria()) {
			foreach ($this->q("SELECT e.id_venta_espera, e.nombre, e.total, e.unidades, e.datos, e.origen, e.id_caja, e.id_caja_cierre,
					to_char(e.fecha_cierre, 'YYYY-MM-DD HH24:MI:SS') AS fecha, to_char(e.fecha, 'DD/MM/YYYY HH24:MI') AS creada,
					COALESCE(e.motivo_anulacion, '') AS motivo, COALESCE(ua.nombre_usuario, '') AS usuario, COALESCE(uc.nombre_usuario, '') AS creada_por
				FROM caja_venta_espera e
				LEFT JOIN usuario ua ON ua.id_usuario = e.id_usuario_cierre
				LEFT JOIN usuario uc ON uc.id_usuario = e.id_usuario
				WHERE e.estado = 'anulada' AND e.fecha_cierre::date BETWEEN ? AND ?", array($de, $a)) as $e) {
				$d = json_decode((string)$e['datos'], true);
				$filas[] = array(
					'tipo' => 'espera', 'fecha' => $e['fecha'], 'referencia' => 'Venta en espera «' . $e['nombre'] . '»',
					'monto' => (int)$e['total'], 'detalle' => $this->textoLineas(isset($d['lineas']) ? $d['lineas'] : array()),
					'motivo' => $e['motivo'], 'usuario' => $e['usuario'], 'id_caja' => (int)$e['id_caja_cierre'],
					'origen' => 'Creada ' . $e['creada'] . ' por ' . ($e['creada_por'] ?: '-') . ' (caja N° ' . (int)$e['id_caja'] . ')',
				);
			}
		}
		if ($tipo !== 'espera') {
			$v = $this->columnasDe('venta');
			$fecha = isset($v['fecha_anulacion']) ? 'COALESCE(v.fecha_anulacion, v.fecha_transaccion)' : 'v.fecha_transaccion';
			$motivo = isset($v['motivo_anulacion']) ? "COALESCE(NULLIF(v.motivo_anulacion, ''), v.mensaje_pedido, '')" : "COALESCE(v.mensaje_pedido, '')";
			$usuario = isset($v['id_usuario_anulacion']) ? 'COALESCE(NULLIF(v.id_usuario_anulacion, 0), v.id_usuario)' : 'v.id_usuario';
			$pg = $this->q("SELECT v.id_venta, v.folio, v.id_caja, COALESCE(td.nombre_tdocumento, 'Documento') AS documento,
					to_char($fecha, 'YYYY-MM-DD HH24:MI:SS') AS fecha, $motivo AS motivo, COALESCE(v.mensaje_pedido, '') AS mensaje,
					COALESCE(substring(v.mensaje_pedido from '^ANULADA \\(\\$(-{0,1}[0-9]+)'), '0')::numeric AS monto,
					COALESCE(u.nombre_usuario, '') AS usuario, COALESCE(cl.nombre_cliente, '') AS cliente
				FROM venta v
				LEFT JOIN tdocumento td ON td.id_tdocumento = v.id_tdocumento
				LEFT JOIN usuario u ON u.id_usuario = $usuario
				LEFT JOIN cliente cl ON cl.id_cliente = v.id_cliente
				WHERE COALESCE(v.mensaje_pedido, '') LIKE 'ANULADA%' AND ($fecha)::date BETWEEN ? AND ?
				ORDER BY 5 DESC LIMIT 2000", array($de, $a));
			foreach ($pg as $d) {
				$filas[] = array(
					'tipo' => 'documento', 'fecha' => $d['fecha'], 'referencia' => $d['documento'] . ($d['folio'] ? ' N° ' . $d['folio'] : ''),
					'monto' => (int)$d['monto'], 'detalle' => $d['cliente'] !== '' ? 'Cliente: ' . $d['cliente'] : '',
					'motivo' => $this->motivoDocumento($d['motivo']), 'usuario' => $d['usuario'], 'id_caja' => (int)$d['id_caja'], 'origen' => '',
				);
			}
		}
		usort($filas, function ($x, $y) { return strcmp($y['fecha'], $x['fecha']); });
		$tot = array('cantidad' => count($filas), 'monto' => 0, 'espera' => 0, 'monto_espera' => 0, 'documento' => 0, 'monto_documento' => 0, 'usuarios' => array());
		foreach ($filas as &$f) {
			$tot['monto'] += $f['monto'];
			$tot[$f['tipo']]++;
			$tot['monto_' . $f['tipo']] += $f['monto'];
			$u = $f['usuario'] !== '' ? $f['usuario'] : 'Sin usuario';
			if (!isset($tot['usuarios'][$u])) $tot['usuarios'][$u] = array('usuario' => $u, 'cantidad' => 0, 'monto' => 0);
			$tot['usuarios'][$u]['cantidad']++;
			$tot['usuarios'][$u]['monto'] += $f['monto'];
			$f['hora'] = date('d/m/Y H:i', strtotime($f['fecha']));
		}
		unset($f);
		$tot['usuarios'] = array_values($tot['usuarios']);
		usort($tot['usuarios'], function ($x, $y) { return $y['monto'] <=> $x['monto']; });
		return array('desde' => $de, 'hasta' => $a, 'filas' => $filas, 'totales' => $tot, 'bitacora' => $this->hayAuditoria());
	}

	// ====================== Partes del detalle ======================

	private function documentos($idCaja)
	{
		$v = $this->columnasDe('venta');
		$enNc = $this->sqlNc();
		$cortesia = isset($v['es_cortesia']) ? 'v.es_cortesia IS TRUE' : 'false';
		$fAnul = isset($v['fecha_anulacion']) ? "to_char(v.fecha_anulacion, 'YYYY-MM-DD HH24:MI:SS')" : "''";
		$motivo = isset($v['motivo_anulacion']) ? "COALESCE(NULLIF(v.motivo_anulacion, ''), v.mensaje_pedido, '')" : "COALESCE(v.mensaje_pedido, '')";
		$uAnul = isset($v['id_usuario_anulacion']) ? 'COALESCE(NULLIF(v.id_usuario_anulacion, 0), v.id_usuario)' : 'v.id_usuario';
		$mesa = isset($v['id_mesa']) ? 'COALESCE(v.id_mesa, 0)' : '0';
		$ref = isset($v['folio_referencia']) ? "COALESCE(tr.nombre_tdocumento || ' N° ' || v.folio_referencia, '')" : "''";
		$joinRef = isset($v['folio_referencia'], $v['id_tdocumento_referencia']) ? "LEFT JOIN tdocumento tr ON tr.id_tdocumento = v.id_tdocumento_referencia AND v.id_tdocumento $enNc" : '';
		if ($joinRef === '') $ref = "''";
		$filas = $this->q("SELECT v.id_venta, v.folio, COALESCE(v.total, 0) AS total, COALESCE(v.descuento, 0) AS descuento,
				to_char(v.fecha_transaccion, 'YYYY-MM-DD HH24:MI:SS') AS fecha, to_char(v.fecha_transaccion, 'HH24:MI') AS hora,
				COALESCE(td.nombre_tdocumento, 'Documento') AS documento, COALESCE(cl.nombre_cliente, '') AS cliente,
				(COALESCE(v.mensaje_pedido, '') LIKE 'ANULADA%') AS anulada,
				COALESCE(substring(v.mensaje_pedido from '^ANULADA \\(\\$(-{0,1}[0-9]+)'), '0')::numeric AS monto_anulado,
				$fAnul AS fecha_anulacion, $motivo AS motivo, COALESCE(ua.nombre_usuario, '') AS usuario_anulacion,
				($cortesia OR COALESCE(pg.cortesia, false)) AS cortesia, (v.id_tdocumento $enNc) AS es_nc,
				$mesa AS id_mesa, $ref AS referencia, COALESCE(pg.pagos, '') AS pagos, COALESCE(u.nombre_usuario, '') AS usuario
			FROM venta v
			LEFT JOIN tdocumento td ON td.id_tdocumento = v.id_tdocumento
			LEFT JOIN cliente cl ON cl.id_cliente = v.id_cliente
			LEFT JOIN usuario u ON u.id_usuario = v.id_usuario
			LEFT JOIN usuario ua ON ua.id_usuario = $uAnul
			LEFT JOIN (SELECT pv.id_venta, string_agg(f.nombre_fpago, ', ') AS pagos, bool_or(f.escortesia IS TRUE) AS cortesia
				FROM pago_venta pv JOIN fpago f ON f.id_fpago = pv.id_fpago
				WHERE pv.id_venta IN (SELECT id_venta FROM venta WHERE id_caja = ?)
				GROUP BY pv.id_venta) pg ON pg.id_venta = v.id_venta
			$joinRef
			WHERE v.id_caja = ?
			ORDER BY v.fecha_transaccion, v.id_venta LIMIT 3000", array($idCaja, $idCaja));
		$res = array();
		foreach ($filas as $f) {
			$f = $this->tipos($f, array('fecha', 'hora', 'documento', 'cliente', 'fecha_anulacion', 'motivo', 'usuario_anulacion', 'referencia', 'pagos', 'usuario'));
			foreach (array('anulada', 'cortesia', 'es_nc') as $b) $f[$b] = $f[$b] === 't' || $f[$b] === true;
			if ($f['anulada']) {
				$f['motivo'] = $this->motivoDocumento($f['motivo']);
				if ($f['usuario_anulacion'] === '') $f['usuario_anulacion'] = $f['usuario'];
			} else {
				$f['motivo'] = '';
			}
			$res[] = $f;
		}
		return $res;
	}

	private function movimientos($idCaja)
	{
		$filas = $this->q("SELECT COALESCE(ingreso, 0) AS ingreso, COALESCE(egreso, 0) AS egreso, COALESCE(observacion, '') AS observacion,
				to_char(fecha_movimiento, 'YYYY-MM-DD HH24:MI:SS') AS fecha
			FROM detalle_caja WHERE id_caja = ? ORDER BY fecha_movimiento, id_detalle_caja", array((int)$idCaja));
		return array_map(function ($f) { return $this->tipos($f, array('observacion', 'fecha')); }, $filas);
	}

	/** Productos anulados en mesas desde esta caja (detalle_cotizacion.id_caja, si la base lo tiene). */
	private function anulacionesMesa($idCaja)
	{
		$dc = $this->columnasDe('detalle_cotizacion');
		if (!isset($dc['id_caja'], $dc['esanulado'], $dc['hora_anulacion'])) return array();
		$usuario = isset($dc['id_cajero']) ? 'COALESCE(NULLIF(dc.id_cajero, 0), dc.id_garzon)' : 'dc.id_garzon';
		$motivo = isset($dc['motivo_anulacion']) ? "COALESCE(dc.motivo_anulacion, '')" : "''";
		$plano = $this->columnasDe('mesa_plano');
		$nombreMesa = isset($plano['nombre']) ? "COALESCE(NULLIF(trim(mp.nombre), ''), 'Mesa ' || c.id_mesa)" : "'Mesa ' || c.id_mesa";
		$joinPlano = isset($plano['nombre']) ? 'LEFT JOIN mesa_plano mp ON mp.id_mesa = c.id_mesa' : '';
		$filas = $this->q("SELECT to_char(dc.hora_anulacion, 'YYYY-MM-DD HH24:MI:SS') AS fecha, COALESCE(p.nombre_producto, 'Producto') AS producto,
				COALESCE(dc.cantidad, 0) AS cantidad, COALESCE(dc.total_bruto, 0) AS total, $motivo AS motivo,
				COALESCE(u.nombre_usuario, '') AS usuario, $nombreMesa AS mesa
			FROM detalle_cotizacion dc
			JOIN cotizacion c ON c.id_cotizacion = dc.id_cotizacion
			LEFT JOIN producto p ON p.id_producto = dc.id_producto
			LEFT JOIN usuario u ON u.id_usuario = $usuario
			$joinPlano
			WHERE dc.id_caja = ? AND dc.esanulado IS TRUE
			ORDER BY dc.hora_anulacion LIMIT 2000", array((int)$idCaja));
		return array_map(function ($f) { return $this->tipos($f, array('fecha', 'producto', 'motivo', 'usuario', 'mesa')); }, $filas);
	}

	private function eventos($idCaja)
	{
		if (!$this->hayAuditoria()) return array();
		$filas = $this->q("SELECT a.evento, COALESCE(a.origen, '') AS origen, COALESCE(a.id_mesa, 0) AS id_mesa, COALESCE(a.id_venta_espera, 0) AS id_venta_espera,
				a.monto, a.unidades, a.detalle, COALESCE(a.motivo, '') AS motivo, to_char(a.fecha, 'YYYY-MM-DD HH24:MI:SS') AS fecha,
				COALESCE(u.nombre_usuario, '') AS usuario
			FROM caja_auditoria a LEFT JOIN usuario u ON u.id_usuario = a.id_usuario
			WHERE a.id_caja = ? ORDER BY a.fecha, a.id_caja_auditoria", array((int)$idCaja));
		$res = array();
		foreach ($filas as $f) {
			$d = json_decode((string)$f['detalle'], true);
			if (!is_array($d)) $d = array();
			$lineas = $this->textoLineas(isset($d['lineas']) ? $d['lineas'] : array());
			$nombre = isset($d['nombre']) ? (string)$d['nombre'] : '';
			$origen = isset(self::ORIGENES[$f['origen']]) ? self::ORIGENES[$f['origen']] : $f['origen'];
			$nivel = 'info';
			switch ($f['evento']) {
				case 'espera_creada':
					$titulo = 'Venta en espera «' . $nombre . '»';
					$texto = ($f['origen'] !== 'manual' ? $origen . ' · ' : '') . $lineas;
					break;
				case 'espera_recuperada':
					$titulo = 'Venta en espera recuperada «' . $nombre . '»';
					$texto = 'Vuelve a la venta';
					break;
				case 'espera_anulada':
					$titulo = 'Venta en espera anulada «' . $nombre . '»';
					$texto = 'Justificación: ' . $f['motivo'] . ($lineas !== '' ? ' · ' . $lineas : '');
					$nivel = 'alerta';
					break;
				case 'productos_omitidos':
					$titulo = 'Productos omitidos' . (!empty($d['mesa']) ? ' en ' . $d['mesa'] : '');
					$texto = $origen . ' · ' . $lineas;
					$nivel = 'alerta';
					break;
				case 'venta_vaciada':
					$titulo = 'Venta vaciada sin cobrar';
					$texto = $lineas;
					$nivel = 'alerta';
					break;
				case 'gaveta_abierta':
					$titulo = 'Gaveta abierta sin venta';
					$texto = '';
					break;
				case 'garzon_cambiado':
					$titulo = 'Cambio de garzón' . (!empty($d['mesa']) ? ' en ' . $d['mesa'] : '');
					$texto = 'De ' . (!empty($d['de']) ? $d['de'] : '-') . ' a ' . (isset($d['a']) ? $d['a'] : '-');
					break;
				default:
					$titulo = $f['evento'];
					$texto = $lineas;
			}
			$res[] = array(
				'fecha' => $f['fecha'], 'evento' => $f['evento'], 'titulo' => $titulo, 'texto' => $texto, 'origen' => $origen,
				'monto' => (int)$f['monto'], 'unidades' => (float)$f['unidades'], 'usuario' => $f['usuario'], 'nivel' => $nivel,
				'motivo' => $f['motivo'], 'lineas' => $lineas, 'nombre' => $nombre, 'mesa' => isset($d['mesa']) ? (string)$d['mesa'] : '',
			);
		}
		return $res;
	}

	/** Ventas en espera creadas en esta caja o resueltas (recuperadas / anuladas) desde ella. */
	private function esperasDeCaja($idCaja)
	{
		if (!$this->hayAuditoria()) return array();
		$filas = $this->q("SELECT e.id_venta_espera, e.nombre, e.estado, e.origen, e.id_caja, COALESCE(e.id_caja_cierre, 0) AS id_caja_cierre,
				e.total, e.unidades, e.datos, COALESCE(e.motivo_anulacion, '') AS motivo,
				to_char(e.fecha, 'YYYY-MM-DD HH24:MI:SS') AS fecha, COALESCE(to_char(e.fecha_cierre, 'YYYY-MM-DD HH24:MI:SS'), '') AS fecha_cierre,
				COALESCE(u.nombre_usuario, '') AS usuario, COALESCE(uc.nombre_usuario, '') AS usuario_cierre
			FROM caja_venta_espera e
			LEFT JOIN usuario u ON u.id_usuario = e.id_usuario
			LEFT JOIN usuario uc ON uc.id_usuario = e.id_usuario_cierre
			WHERE e.id_caja = ? OR e.id_caja_cierre = ?
			ORDER BY e.fecha, e.id_venta_espera", array((int)$idCaja, (int)$idCaja));
		$res = array();
		foreach ($filas as $f) {
			$d = json_decode((string)$f['datos'], true);
			$res[] = array(
				'id' => (int)$f['id_venta_espera'], 'nombre' => $f['nombre'], 'estado' => $f['estado'],
				'origen' => isset(self::ORIGENES[$f['origen']]) ? self::ORIGENES[$f['origen']] : $f['origen'],
				'id_caja' => (int)$f['id_caja'], 'id_caja_cierre' => (int)$f['id_caja_cierre'], 'total' => (int)$f['total'],
				'unidades' => (float)$f['unidades'], 'productos' => $this->textoLineas(isset($d['lineas']) ? $d['lineas'] : array()),
				'motivo' => $f['motivo'], 'fecha' => $f['fecha'], 'fecha_cierre' => $f['fecha_cierre'],
				'usuario' => $f['usuario'], 'usuario_cierre' => $f['usuario_cierre'],
			);
		}
		return $res;
	}

	// ====================== Utilidades ======================

	private function hito($fecha, $tipo, $titulo, $texto, $monto, $usuario, $nivel)
	{
		$fecha = (string)$fecha;
		$ts = $fecha !== '' ? strtotime($fecha) : false;
		return array('orden' => ++$this->orden, 'fecha' => $ts ? date('Y-m-d H:i:s', $ts) : '', 'hora' => $ts ? date('d/m H:i', $ts) : '', 'tipo' => $tipo,
			'titulo' => (string)$titulo, 'texto' => (string)$texto, 'monto' => (int)$monto, 'usuario' => (string)$usuario, 'nivel' => $nivel);
	}

	/** "2× Café (Leche avena) · 1× Kuchen" */
	private function textoLineas($lineas)
	{
		$t = array();
		foreach (is_array($lineas) ? $lineas : array() as $l) {
			if (!is_array($l)) continue;
			$t[] = $this->cant(isset($l['cantidad']) ? $l['cantidad'] : 0) . '× ' . (isset($l['nombre']) ? $l['nombre'] : '')
				. (!empty($l['opciones']) ? ' (' . $l['opciones'] . ')' : '') . (!empty($l['nota']) ? ' «' . $l['nota'] . '»' : '');
		}
		return implode(' · ', $t);
	}

	/** "ANULADA ($5000): cliente se arrepintió" -> "cliente se arrepintió" */
	private function motivoDocumento($m)
	{
		$m = trim((string)$m);
		return trim(preg_replace('/^ANULADA\s*\(\$-?[0-9]+\)\s*:?\s*/u', '', $m));
	}

	private function cant($n)
	{
		$n = round((float)$n, 3);
		return $n == floor($n) ? (string)(int)$n : rtrim(rtrim(number_format($n, 3, '.', ''), '0'), '.');
	}

	private function pesos($n)
	{
		$n = (int)round($n);
		return ($n < 0 ? '-$' : '$') . number_format(abs($n), 0, '.', ',');
	}

	private function hayAuditoria()
	{
		static $hay = null;
		if ($hay === null) {
			$r = $this->q("SELECT (to_regclass('public.caja_venta_espera') IS NOT NULL AND to_regclass('public.caja_auditoria') IS NOT NULL) AS ok");
			$hay = $r && ($r[0]['ok'] === 't' || $r[0]['ok'] === true);
		}
		return $hay;
	}

	private function sqlNc()
	{
		$ids = array();
		foreach ($this->q('SELECT id_tdocumento FROM tdocumento WHERE cod_sii = ? OR upper(trim(nombre_tdocumento)) = ?',
			array(self::COD_SII_NC, self::NC_INTERNA)) as $r) {
			$ids[] = (int)$r['id_tdocumento'];
		}
		$ids = array_values(array_unique($ids));
		return $ids ? 'IN (' . implode(', ', $ids) . ')' : 'IN (-1)';
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

	/** dd-mm-aaaa, dd/mm/aaaa o aaaa-mm-dd -> aaaa-mm-dd (si no se entiende, hoy). Ordena desde/hasta. */
	private function periodo($desde, $hasta)
	{
		$de = $this->fecha($desde);
		$a = $this->fecha($hasta);
		return $a < $de ? array($a, $de) : array($de, $a);
	}

	private function fecha($s)
	{
		$s = trim((string)$s);
		foreach (array('d-m-Y', 'd/m/Y', 'Y-m-d') as $f) {
			$d = DateTime::createFromFormat('!' . $f, $s);
			if ($d && $d->format($f) === $s) return $d->format('Y-m-d');
		}
		return date('Y-m-d');
	}

	/** Montos y cantidades llegan como texto desde PostgreSQL: a número, salvo las columnas de texto. */
	private function tipos($f, $texto)
	{
		foreach ($f as $k => $v) {
			if (in_array($k, $texto, true)) $f[$k] = (string)$v;
			elseif (is_numeric($v)) $f[$k] = $v + 0;
		}
		return $f;
	}

	private function q($sql, $binds = array())
	{
		if ($this->db2 === null) {
			$this->load->helper('tenant');
			$this->db2 = get_tenant_db($this);
			$this->db2->db_debug = FALSE;
		}
		$r = $this->db2->query($sql, $binds);
		if ($r === FALSE) {
			$e = $this->db2->error();
			log_message('error', 'Mauditoriacaja: ' . $e['message'] . ' | ' . $sql);
			throw new RuntimeException('No se pudo leer la auditoría de caja.');
		}
		return $r->result_array();
	}
}
