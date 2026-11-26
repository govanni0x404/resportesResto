<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Boleta / factura / comprobante en texto ESC/POS para la impresora térmica de la caja, para que el agente de
 * impresión (o el servidor) la mande directo, igual que las comandas. Mismo contenido que la vista caja/ticket.php
 * (la que se abre en el navegador), incluido el timbre electrónico del SII como imagen y la copia cedible.
 */
class Ticket_impresion
{
	/**
	 * $doc = Mcaja::documento(); $papel = 80 | 58; $timbre = matriz PDF417 (TCPDF2DBarcode::getBarcodeArray) o null.
	 */
	public function armar($doc, $papel = 80, $timbre = null)
	{
		$W = $papel == 58 ? 32 : 42;
		$puntos = self::puntos($papel);
		$v = $doc['venta'];
		$e = $doc['empresa'];
		$sii = isset($doc['sii']) ? $doc['sii'] : null;
		$nc = !empty($doc['nc']);
		$m = function ($n) { return '$' . number_format((float)$n, 0, ',', '.'); };
		$cant = function ($n) { return rtrim(rtrim(number_format((float)$n, 3, ',', ''), '0'), ','); };
		$rut = function ($r, $dv) { return number_format((float)$r, 0, ',', '.') . '-' . $dv; };

		$centro = "\x1ba\x01";
		$izq = "\x1ba\x00";
		$normal = "\x1d!\x00\x1bE\x00";
		$neg = "\x1bE\x01";
		$doble = "\x1d!\x11";
		$alto = "\x1d!\x01";

		$copias = !empty($doc['cedible']) ? array('', 'CEDIBLE') : array('');
		$b = '';
		foreach ($copias as $copia) {
			$b .= "\x1b@\x1bt\x13" . $normal;   // iniciar + CP858 (acentos)
			if ($e) {
				$b .= $centro . $neg . $this->lineas($e->nombre_fantasia ?: $e->razon_social, $W) . $normal;
				if ($e->nombre_fantasia && $e->razon_social) $b .= $centro . $this->lineas($e->razon_social, $W);
				$b .= $centro . $this->t('RUT ' . $rut($e->rut_empresa, $e->dv_empresa)) . "\n";
				if (!empty($e->giro)) $b .= $centro . $this->lineas($e->giro, $W);
				$dir = trim($e->direccion_empresa . (!empty($e->comuna) ? ', ' . $e->comuna : ''));
				if ($dir !== '') $b .= $centro . $this->lineas($dir, $W);
				if (!empty($e->telefono)) $b .= $centro . $this->t('Fono ' . $e->telefono) . "\n";
			}
			// Recuadro del documento
			$b .= $centro . str_repeat('-', $W) . "\n" . $neg;
			if ($sii && $e) $b .= $this->t('R.U.T.: ' . $rut($e->rut_empresa, $e->dv_empresa)) . "\n";
			$b .= $this->lineas(mb_strtoupper($v->nombre_tdocumento), $W);
			$b .= $alto . $this->t('N° ' . (int)$v->folio) . $normal . "\n";
			$b .= $centro . str_repeat('-', $W) . "\n";
			if ($sii && !empty($sii['oficina'])) $b .= $centro . $neg . $this->t($sii['oficina']) . $normal . "\n";
			if (!empty($doc['anulada'])) $b .= $centro . $doble . $this->t('ANULADA') . $normal . "\n";
			if ($nc) $b .= $centro . $neg . $this->lineas('Devolución de ' . $doc['referencia'], $W) . $normal;

			$b .= $izq . $this->t('Fecha: ' . $v->fecha_texto) . "\n";
			$b .= $this->t('Cajero: ' . $v->nombre_usuario) . "\n";
			if (!empty($v->nombre_retira)) $b .= $neg . $alto . $this->lineas('Retira: ' . $v->nombre_retira, $W) . $normal;
			if ($v->rut) {
				$b .= $this->lineas('Cliente: ' . $v->nombre_cliente, $W);
				$b .= $this->t('RUT: ' . $rut($v->rut, $v->dv)) . "\n";
				if ($v->giro) $b .= $this->lineas('Giro: ' . $v->giro, $W);
				if ($v->direccion) $b .= $this->lineas('Dir: ' . $v->direccion, $W);
				if ($v->comuna) $b .= $this->lineas('Comuna: ' . $v->comuna, $W);
				if (!empty($v->solicitante)) $b .= $this->lineas('Solicita: ' . $v->solicitante, $W);
				if (!empty($v->motivo_factura)) $b .= $this->lineas('Motivo: ' . $v->motivo_factura, $W);
			}
			$b .= str_repeat('-', $W) . "\n";
			foreach ($doc['detalle'] as $d) {
				// v1.24.0: producto elegido de un menú: "+ nombre" debajo del menú; sin precio si no tiene costo adicional
				if (!empty($d->del_menu)) {
					$b .= $this->lineas('  + ' . ($cant($d->cantidad) !== '1' ? $cant($d->cantidad) . ' ' : '') . $d->nombre, $W);
					if ((int)round((float)$d->monto_mostrar) !== 0) $b .= $this->fila('    adicional', $m($d->monto_mostrar), $W);
					continue;
				}
				$b .= $this->lineas($d->nombre . ($d->exento ? ' (EXENTO)' : ''), $W);
				$b .= $this->fila('  ' . $cant($d->cantidad) . ' x ' . $m($d->precio_mostrar), $m($d->monto_mostrar), $W);
			}
			$b .= str_repeat('-', $W) . "\n";
			$b .= $this->fila('Subtotal' . ($v->en_neto ? ' neto' : ''), $m($v->subtotal_mostrar), $W);
			if ((int)$v->descuento_mostrar > 0) $b .= $this->fila('Descuento', '-' . $m($v->descuento_mostrar), $W);
			$b .= $this->fila('Monto neto', $m($v->neto), $W);
			$b .= $this->fila('IVA (19%)', $m($v->iva), $W);
			if ((int)abs((int)($v->ila ?? 0)) > 0) $b .= $this->fila('ILA', $m($v->ila), $W);   // v1.20.1
			if ($v->monto_exento > 0) $b .= $this->fila('Monto exento', $m($v->monto_exento), $W);
			$b .= $neg . $alto . $this->fila($nc ? 'TOTAL DEVUELTO' : 'TOTAL', $m($v->total), $W) . $normal;
			$propina = isset($v->valor_propina) ? (int)$v->valor_propina : 0;
			if ($propina > 0) {
				$b .= $this->fila('Propina (voluntaria)', $m($propina), $W);
				$b .= $neg . $this->fila('Total con propina', $m($v->total + $propina), $W) . $normal;
			}
			if ($doc['pagos']) {
				$b .= str_repeat('-', $W) . "\n";
				foreach ($doc['pagos'] as $p) {
					$b .= $this->fila(($nc ? 'Devuelto en ' : '') . $p->nombre_fpago, $m($p->paga_con), $W);
					if ((int)$p->vuelto > 0) $b .= $this->fila('Vuelto', $m($p->vuelto), $W);
				}
			}
			if ($v->mensaje_pedido) $b .= str_repeat('-', $W) . "\n" . $this->lineas($v->mensaje_pedido, $W);

			if (!empty($doc['electronico']) && empty($doc['emitido'])) {
				$b .= "\n" . $centro . $neg . $this->lineas('Documento pendiente de envío al SII.', $W) . $normal;
			} elseif ($timbre) {
				$b .= "\n" . $centro . $this->raster($timbre, $puntos);
				$b .= $centro . $neg . $this->t('Timbre Electrónico SII') . $normal . "\n";
				if (!empty($sii['resolucion'])) $b .= $centro . $this->t($sii['resolucion']) . "\n";
				if (!empty($sii['verificacion'])) $b .= $centro . $this->lineas('Verifique documento: ' . $sii['verificacion'], $W);
			}
			$b .= $izq . str_repeat('-', $W) . "\n";
			$b .= $centro . $this->t($nc ? 'Firma cliente: ______________' : '¡Gracias por su compra!') . "\n";
			if ($sii && !empty($sii['publicidad'])) {
				foreach ($sii['publicidad'] as $linea) $b .= $centro . $this->lineas($linea, $W);
			}
			if ($copia) {
				$b .= "\n" . $izq . $neg . $this->t('ACUSE DE RECIBO') . $normal . "\n";
				foreach (array('Nombre', 'R.U.T.', 'Fecha', 'Recinto', 'Firma') as $campo) {
					$b .= "\n" . $this->t($campo . ': ' . str_repeat('_', max(5, $W - mb_strlen($campo) - 2))) . "\n";
				}
				$b .= $this->lineas('El acuse de recibo que se declara en este acto, de acuerdo a lo dispuesto en la letra b) del Art. 4°, '
					. 'y la letra c) del Art. 5° de la Ley 19.983, acredita que la entrega de mercaderías o servicio(s) prestado(s) '
					. 'ha(n) sido recibido(s).', $W);
				$b .= $centro . $doble . $this->t('CEDIBLE') . $normal . "\n";
			}
			$b .= $izq . "\n\n\n\n\x1dVB\x00";   // avanzar y cortar
		}
		return $b;
	}

	/**
	 * Cierre de caja / informe X (mismo contenido que caja/cierre_ticket.php), para la impresora del cierre.
	 * $d = Mcaja::datosCierre(); las secciones siguen Configuraciones > Imprimir cierre de caja.
	 */
	public function cierre($d, $papel = 80)
	{
		// v1.39.4 (pedido del usuario: "mejora el diseño del impreso"): encabezado con los datos de la empresa, títulos de
		// sección en negativo, formas de pago en columnas (sistema / rendido / diferencia), cuadre del efectivo y la
		// diferencia en grande con SOBRA / FALTA / CUADRADA. Mismos datos y mismas opciones de Configuraciones que antes.
		$W = $papel == 58 ? 32 : 42;
		$c = $d['caja'];
		$r = $d['resumen'];
		$e = $d['empresa'];
		$cfg = isset($d['config']) ? $d['config'] : array();
		$ver = function ($k) use ($cfg) { return !isset($cfg[$k]) || $cfg[$k]; };
		$m = function ($n) { return ($n < 0 ? '-' : '') . '$' . number_format(abs((float)$n), 0, ',', '.'); };
		$cerrada = !empty($d['cerrada']);
		$centro = "\x1ba\x01"; $izq = "\x1ba\x00"; $normal = "\x1d!\x00\x1bE\x00"; $neg = "\x1bE\x01";
		$alto = "\x1d!\x01"; $grande = "\x1d!\x11";
		$doble = str_repeat('=', $W) . "\n";
		$linea = str_repeat('-', $W) . "\n";
		// Título de sección: en negativo (blanco sobre negro) a todo el ancho
		$seccion = function ($t) use ($W, $izq, $neg, $normal) {
			$t = ' ' . $t . ' ';
			$l = mb_strlen($t);
			$pad = max(0, $W - $l);
			$txt = str_repeat(' ', intdiv($pad, 2)) . $t . str_repeat(' ', $pad - intdiv($pad, 2));
			return $izq . $neg . "\x1dB\x01" . $this->t($txt) . "\x1dB\x00" . $normal . "\n";
		};
		// Etiqueta a la izquierda y dato a la derecha
		$dato = function ($k, $v) use ($W) { return $this->fila($k, (string)$v, $W); };
		// Tres columnas a la derecha (sistema / rendido / diferencia), cada una de igual ancho
		$cols = function ($a, $b, $c3, $sangria = 0) use ($W) {
			$cw = intdiv($W - $sangria, 3);
			$fit = mb_strlen($a) < $cw && mb_strlen($b) < $cw && mb_strlen($c3) < $cw;
			if (!$fit) return null;
			$izqPad = $W - 3 * $cw;
			return $this->t(str_repeat(' ', $izqPad) . str_pad($a, $cw, ' ', STR_PAD_LEFT) . str_pad($b, $cw, ' ', STR_PAD_LEFT)
				. str_pad($c3, $cw, ' ', STR_PAD_LEFT)) . "\n";
		};

		$b = "\x1b@\x1bt\x13" . $normal;
		// ---- Encabezado: la empresa ----
		if ($e) {
			$nombre = trim((string)($e->nombre_fantasia ?: $e->razon_social));
			$b .= $centro . $neg . $alto . $this->lineas($nombre, $W) . $normal;
			if ($e->nombre_fantasia && $e->razon_social && strcasecmp(trim($e->razon_social), $nombre) !== 0) $b .= $centro . $this->lineas($e->razon_social, $W);
			if (!empty($e->rut_empresa)) $b .= $centro . $this->t('RUT ' . number_format((float)$e->rut_empresa, 0, ',', '.') . '-' . $e->dv_empresa) . "\n";
			$dir = trim((string)(!empty($e->direccion_sucursal) ? $e->direccion_sucursal : $e->direccion_empresa));
			if ($dir !== '') $b .= $centro . $this->lineas($dir, $W);
		}
		// ---- Título ----
		$b .= $centro . $doble . $neg . $grande . $this->t($cerrada ? 'CIERRE DE CAJA' : 'INFORME X') . $normal . "\n";
		$b .= $centro . $neg . $this->t('Caja N° ' . (int)$c->id_caja) . $normal . "\n";
		if (!$cerrada) $b .= $centro . $this->t('Caja abierta - resumen parcial') . "\n";
		$b .= $doble . $izq;
		$b .= $dato('Cajero', $c->nombre_usuario);
		$b .= $dato('Apertura', $c->apertura);
		$b .= $cerrada ? $dato('Cierre', $c->cierre_texto) : $dato('Emitido', date('d/m/Y H:i'));
		$b .= "\n";

		// ---- Ventas ----
		$b .= $seccion('RESUMEN DE VENTAS');
		$b .= $dato('Ventas (' . (int)$r['cantidad_ventas'] . ')', $m($r['ventas'] + $r['devoluciones']));
		if ($r['cantidad_devoluciones']) $b .= $dato('Devoluciones (' . (int)$r['cantidad_devoluciones'] . ')', $m(-$r['devoluciones']));
		$b .= $neg . $alto . $dato('VENTA NETA', $m($r['ventas'])) . $normal;
		$extras = '';
		if ($r['descuentos']) $extras .= $dato('Descuentos otorgados', $m($r['descuentos']));
		if ($ver('cierre_creditos') && !empty($r['creditos'])) $extras .= $dato('Créditos a clientes', $m($r['creditos']));
		if ($ver('cierre_cortesias') && $r['cortesias']) $extras .= $dato('Cortesías', $m($r['cortesias']));
		if ($ver('cierre_anulaciones') && $r['anuladas']) $extras .= $dato('Ventas anuladas', (string)(int)$r['anuladas']);
		if ($r['propinas']) $extras .= $dato('Propinas (a garzones)', $m($r['propinas']));
		if (!empty($r['pagos_credito'])) $extras .= $dato('Pagos de créditos', $m($r['pagos_credito']));
		if ($ver('cierre_ingresos') && $r['ingresos']) $extras .= $dato('Ingresos de caja', $m($r['ingresos']));
		if ($ver('cierre_ingresos') && $r['egresos']) $extras .= $dato('Retiros de caja', $m(-$r['egresos']));
		if ($extras !== '') $b .= $linea . $extras;
		$b .= "\n";

		// ---- Formas de pago ----
		$b .= $seccion('FORMAS DE PAGO');
		if ($cerrada) {
			$cab = $cols('Sistema', 'Rendido', 'Dif.');
			$b .= $neg . ($cab !== null ? $cab : $this->fila('', 'Sistema / Rendido / Dif.', $W)) . $normal;
			$tS = 0; $tR = 0;
			foreach ($d['formas'] as $f) {
				$dif = (int)$f['rendido'] - (int)$f['esperado'];
				$tS += (int)$f['esperado']; $tR += (int)$f['rendido'];
				$b .= $neg . $this->lineas($f['nombre'] . ($f['efectivo'] ? ' (con fondo)' : ''), $W) . $normal;
				$fila = $cols($m($f['esperado']), $m($f['rendido']), $dif ? ($dif > 0 ? '+' : '') . $m($dif) : '0');
				$b .= $fila !== null ? $fila : $dato('  Sistema', $m($f['esperado'])) . $dato('  Rendido', $m($f['rendido'])) . $dato('  Diferencia', $m($dif));
			}
			$b .= $linea;
			$tD = $tR - $tS;
			$tot = $cols($m($tS), $m($tR), $tD ? ($tD > 0 ? '+' : '') . $m($tD) : '0');
			$b .= $neg . 'TOTAL' . "\n" . ($tot !== null ? $tot : $dato('  Sistema', $m($tS)) . $dato('  Rendido', $m($tR)) . $dato('  Diferencia', ($tD > 0 ? '+' : '') . $m($tD))) . $normal;
		} else {
			foreach ($d['formas'] as $f) $b .= $dato($f['nombre'] . ($f['efectivo'] ? ' (con fondo)' : ''), $m($f['esperado']));
		}
		$b .= "\n";

		// ---- Efectivo ----
		$b .= $seccion('EFECTIVO');
		$b .= $dato('Fondo inicial', $m($r['fondo']));
		if ($cerrada) {
			$b .= $dato('Efectivo esperado', $m($r['efectivo_esperado']));
			$b .= $dato('Efectivo entregado', $m($c->efectivo_entregado));
			$b .= $dato('Fondo próximo turno', $m($c->fondo_fijo));
			$dif = (int)$c->diferencia;
			$estado = $dif > 0 ? 'SOBRA ' . $m($dif) : ($dif < 0 ? 'FALTA ' . $m(-$dif) : 'CAJA CUADRADA');
			$b .= "\n" . $centro . $doble . $this->t('DIFERENCIA') . "\n" . $neg . $grande . $this->t($m($dif)) . $normal . "\n";
			$b .= $neg . $this->t($estado) . $normal . "\n" . $doble . $izq;
		} else {
			$b .= "\n" . $centro . $doble . $this->t('EFECTIVO EN CAJA') . "\n" . $neg . $grande . $this->t($m($r['efectivo_esperado'])) . $normal . "\n" . $doble . $izq;
		}

		// ---- Inventario ----
		if ($cerrada && !empty($d['inventario'])) {
			$cn = function ($n) { return rtrim(rtrim(number_format((float)$n, 3, ',', '.'), '0'), ','); };
			$b .= "\n" . $seccion('INVENTARIO DE CIERRE');
			$cab = $cols('Sistema', 'Contado', 'Dif.');
			if ($cab !== null) $b .= $neg . $cab . $normal;
			$bodega = null; $conDif = 0;
			foreach ($d['inventario'] as $iv) {
				if ($iv['bodega'] !== $bodega) { $bodega = $iv['bodega']; $b .= $neg . $this->lineas('> ' . $bodega, $W) . $normal; }
				$b .= $this->lineas($iv['nombre'] . ($iv['umedida'] ? ' (' . $iv['umedida'] . ')' : ''), $W);
				$hayDif = abs($iv['diferencia']) > 0.0005;
				if ($hayDif) $conDif++;
				$dif = $hayDif ? ($iv['diferencia'] > 0 ? '+' : '') . $cn($iv['diferencia']) : '0';
				$fila = $cols($cn($iv['stock']), $cn($iv['contado']), $dif);
				$b .= $fila !== null ? $fila : $this->fila('  Sist. ' . $cn($iv['stock']) . '  Cont. ' . $cn($iv['contado']), 'Dif. ' . $dif, $W);
			}
			$b .= $linea . $centro . $this->t(count($d['inventario']) . ' insumo(s) - ' . ($conDif ? $conDif . ' con diferencia' : 'sin diferencias')) . "\n" . $izq;
		}

		// ---- Firmas ----
		if ($cerrada) {
			$ancho = intdiv($W - 2, 2);
			$b .= "\n\n\n\n" . $izq . $this->t(str_repeat('_', $ancho) . '  ' . str_repeat('_', $ancho)) . "\n";
			$b .= $this->t(str_pad('Cajero', $ancho, ' ', STR_PAD_BOTH) . '  ' . str_pad('Supervisor', $ancho, ' ', STR_PAD_BOTH)) . "\n";
		} else {
			$b .= "\n" . $centro . $neg . $this->t('Informe parcial: no cierra la caja.') . $normal . "\n";
		}
		$b .= "\n" . $centro . $this->t('Impreso el ' . date('d/m/Y H:i')) . "\n";
		return $b . $izq . "\n\n\n\n\x1dVB\x00";
	}

	/** Comprobante de anulación de una venta. $a = lo que devuelve Mcaja::anularVenta(). */
	public function anulacion($a, $papel = 80)
	{
		$W = $papel == 58 ? 32 : 42;
		$centro = "\x1ba\x01"; $izq = "\x1ba\x00"; $normal = "\x1d!\x00\x1bE\x00"; $neg = "\x1bE\x01"; $doble = "\x1d!\x11";
		$m = function ($n) { return '$' . number_format((float)$n, 0, ',', '.'); };
		$b = "\x1b@\x1bt\x13" . $normal;
		$b .= $centro . $doble . $this->t('ANULACIÓN') . $normal . "\n";
		$b .= $centro . $this->t('Comprobante de anulación') . "\n" . str_repeat('-', $W) . "\n" . $izq;
		$b .= $this->lineas($a['documento'] . ' N° ' . (int)$a['folio'], $W);
		$b .= $neg . $this->fila('Monto anulado', $m($a['total']), $W) . $normal;
		$b .= $this->t('Fecha: ' . $a['fecha']) . "\n";
		$b .= $this->lineas('Anulada por: ' . $a['usuario'], $W);
		$b .= $this->lineas('Caja N° ' . (int)$a['id_caja'], $W);
		$b .= str_repeat('-', $W) . "\n" . $this->lineas('Motivo: ' . $a['motivo'], $W);
		$b .= "\n\n\n" . $centro . $this->t(str_repeat('_', 24)) . "\n" . $this->t('Firma') . "\n";
		return $b . $izq . "\n\n\n\n\x1dVB\x00";
	}

	/** Comprobante de pago de crédito. $c = Mcaja::comprobantePagoCredito(). */
	public function pagoCredito($c, $papel = 80)
	{
		$W = $papel == 58 ? 32 : 42;
		$centro = "\x1ba\x01"; $izq = "\x1ba\x00"; $normal = "\x1d!\x00\x1bE\x00"; $neg = "\x1bE\x01"; $alto = "\x1d!\x01";
		$m = function ($n) { return '$' . number_format((float)$n, 0, ',', '.'); };
		$linea = str_repeat('-', $W) . "\n";
		$e = $c['empresa'];
		$b = "\x1b@\x1bt\x13" . $normal;
		if ($e) $b .= $centro . $neg . $this->lineas($e->nombre_fantasia ?: $e->razon_social, $W) . $normal;
		$b .= $centro . $linea . $neg . $alto . $this->t('PAGO DE CRÉDITO') . $normal . "\n";
		$b .= $centro . $this->t('Comprobante N° ' . (int)$c['id_pago']) . "\n" . $linea . $izq;
		$b .= $this->t('Fecha: ' . $c['fecha']) . "\n";
		$b .= $this->lineas('Cliente: ' . $c['cliente'], $W);
		if ($c['rut'] !== '') $b .= $this->t('RUT: ' . $c['rut']) . "\n";
		$b .= $this->lineas('Cajero: ' . $c['usuario'] . '  (caja N° ' . (int)$c['id_caja'] . ')', $W) . $linea;
		$b .= $neg . $this->t('Ventas pagadas') . $normal . "\n";
		foreach ($c['detalle'] as $d) {
			$b .= $this->lineas($d['documento'] . ' N° ' . (int)$d['folio'] . ' (' . $d['fecha'] . ')', $W);
			$b .= $this->fila('', $m($d['monto']), $W);
		}
		$b .= $linea . $neg . $alto . $this->fila('TOTAL PAGADO', $m($c['monto']), $W) . $normal;
		$b .= $this->fila('Forma de pago', $c['fpago'], $W);
		if ($c['vuelto'] > 0) {
			$b .= $this->fila('Paga con', $m($c['paga_con']), $W);
			$b .= $this->fila('Vuelto', $m($c['vuelto']), $W);
		}
		$b .= $this->fila('Deuda pendiente', $m($c['deuda_restante']), $W);
		if ($c['observacion'] !== '') $b .= $linea . $this->lineas('Obs: ' . $c['observacion'], $W);
		$b .= "\n\n\n" . $centro . $this->t(str_repeat('_', 24)) . "\n" . $this->t('Firma') . "\n";
		return $b . $izq . "\n\n\n\n\x1dVB\x00";
	}

	/** Pulso para abrir la gaveta de dinero conectada a la impresora (ESC p, pin 2 y luego pin 5). */
	public static function gaveta()
	{
		return "\x1b@\x1bp\x00\x19\xfa\x1bp\x01\x19\xfa";
	}

	/** Ancho imprimible en puntos (8 puntos por mm): 64 mm en papel de 80 y 48 mm en papel de 58. */
	public static function puntos($papel)
	{
		return $papel == 58 ? 384 : 512;
	}

	/** Matriz PDF417 como imagen de puntos (GS v 0), escalada al ancho del papel y en bandas para no llenar el búfer. */
	private function raster($matriz, $maxPuntos)
	{
		$cols = (int)$matriz['num_cols'];
		$filas = (int)$matriz['num_rows'];
		if ($cols <= 0 || $filas <= 0) return '';
		// 2 puntos por módulo si cabe (timbreMatriz ya lo pide así); si no, se ajusta al ancho
		$ancho = min($maxPuntos, $cols * 2);
		$escala = $ancho / $cols;
		$alto = (int)round($filas * $escala);
		$bytesFila = (int)ceil($ancho / 8);
		$out = '';
		for ($y0 = 0; $y0 < $alto; $y0 += 128) {
			$h = min(128, $alto - $y0);
			$datos = '';
			for ($y = $y0; $y < $y0 + $h; $y++) {
				$fila = $matriz['bcode'][min($filas - 1, (int)floor($y / $escala))];
				for ($xb = 0; $xb < $bytesFila; $xb++) {
					$byte = 0;
					for ($bit = 0; $bit < 8; $bit++) {
						$x = $xb * 8 + $bit;
						if ($x < $ancho && !empty($fila[min($cols - 1, (int)floor($x / $escala))])) $byte |= 0x80 >> $bit;
					}
					$datos .= chr($byte);
				}
			}
			$out .= "\x1dv0\x00" . chr($bytesFila & 0xFF) . chr($bytesFila >> 8) . chr($h & 0xFF) . chr($h >> 8) . $datos;
		}
		return $out . "\n";
	}

	/** Texto a la izquierda y monto a la derecha en una línea (el texto largo pasa a la línea de arriba). */
	private function fila($texto, $monto, $W)
	{
		$texto = (string)$texto;
		$libre = $W - mb_strlen($monto) - 1;
		if (mb_strlen($texto) > $libre) return $this->lineas($texto, $W) . $this->t(str_pad('', $W - mb_strlen($monto)) . $monto) . "\n";
		return $this->t($texto . str_repeat(' ', $W - mb_strlen($texto) - mb_strlen($monto)) . $monto) . "\n";
	}

	private function lineas($s, $W)
	{
		$s = trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string)$s));
		if ($s === '') return '';
		$out = '';
		// wordwrap cuenta bytes: se corta por palabras contando caracteres
		$linea = '';
		foreach (preg_split('/\s+/u', $s) as $p) {
			while (mb_strlen($p) > $W) {
				if ($linea !== '') { $out .= $this->t($linea) . "\n"; $linea = ''; }
				$out .= $this->t(mb_substr($p, 0, $W)) . "\n";
				$p = mb_substr($p, $W);
			}
			if ($linea === '') $linea = $p;
			elseif (mb_strlen($linea) + 1 + mb_strlen($p) <= $W) $linea .= ' ' . $p;
			else { $out .= $this->t($linea) . "\n"; $linea = $p; }
		}
		if ($linea !== '') $out .= $this->t($linea) . "\n";
		return $out;
	}

	private function t($s)
	{
		$s = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string)$s);
		$r = @iconv('UTF-8', 'CP858//TRANSLIT//IGNORE', $s);
		return $r === false ? preg_replace('/[^\x20-\x7e]/', '?', $s) : $r;
	}
}
