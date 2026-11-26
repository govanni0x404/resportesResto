<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Boleta / factura / nota de crédito / comprobante para el agente de impresión local del equipo (12-11-2026), como
 * mipdv_web: en vez de bytes ESC/POS arma las operaciones de PrinterJob (assets/js/impresion-local/printerJob.js) y la
 * Caja se las manda al agente (http://localhost:5050). Mismo contenido que Ticket_impresion::armar() y que la vista
 * caja/ticket.php, con el timbre electrónico como PDF417 (lo dibuja el agente) y la copia cedible.
 *
 * Tamaños como los usa mipdv_web con el agente: fontsize 1 = texto normal, 2 = destacado; 41 columnas en 80 mm y 28 en 58 mm
 * (se usan 40 y 28 para no rozar el borde).
 */
class Ticket_local
{
	private $ops = array();

	/** $doc = Mcaja::documento(); $papel = 80 | 58. Devuelve [{accion, valor}]. */
	public function operaciones($doc, $papel = 80)
	{
		$this->ops = array();
		$W = $papel == 58 ? 28 : 40;
		$this->ancho = $W;
		$this->centrado = false;
		$v = $doc['venta'];
		$e = $doc['empresa'];
		$sii = isset($doc['sii']) ? $doc['sii'] : null;
		$nc = !empty($doc['nc']);
		$m = function ($n) { return '$' . number_format((float)$n, 0, ',', '.'); };
		$cant = function ($n) { return rtrim(rtrim(number_format((float)$n, 3, ',', ''), '0'), ','); };
		$rut = function ($r, $dv) { return number_format((float)$r, 0, ',', '.') . '-' . $dv; };
		$conTimbre = !empty($doc['emitido']) && trim((string)(isset($doc['ted']) ? $doc['ted'] : '')) !== '';

		$copias = !empty($doc['cedible']) ? array('', 'CEDIBLE') : array('');
		foreach ($copias as $copia) {
			$this->op('fontsize', 1);
			$this->op('fontbold', false);
			$this->op('textaling', 'center');
			if ($e) {
				// 16-11-2026: nombre del local en doble alto y negrita, centrado
				$this->destacado(mb_strtoupper($e->nombre_fantasia ?: $e->razon_social), 30);
				if ($e->nombre_fantasia && $e->razon_social) $this->lineas($e->razon_social, $W);
				$this->texto('RUT ' . $rut($e->rut_empresa, $e->dv_empresa));
				if (!empty($e->giro)) $this->lineas($e->giro, $W);
				$dir = trim($e->direccion_empresa . (!empty($e->comuna) ? ', ' . $e->comuna : ''));
				if ($dir !== '') $this->lineas($dir, $W);
				if (!empty($e->telefono)) $this->texto('Fono ' . $e->telefono);
			}
			// Recuadro del documento (v1.40.0 modo texto: líneas dobles, como el recuadro del diseño de la página)
			$this->linea($W, '=');
			$recuadro = array();
			if ($sii && $e) $recuadro[] = 'R.U.T.: ' . $rut($e->rut_empresa, $e->dv_empresa);
			$this->op('textaling', 'center');
			if ($recuadro) $this->negrita($recuadro);
			$this->destacado(mb_strtoupper($v->nombre_tdocumento), 26);   
			$this->destacado('N. ' . (int)$v->folio, 26);
			$this->linea($W, '=');
			if ($sii && !empty($sii['oficina'])) $this->negrita(array($sii['oficina']));
			$this->op('textaling', 'left');
			if (!empty($doc['anulada'])) {
				$this->destacado('ANULADA', 30);
			}
			if ($nc) $this->negrita($this->lineasTexto('Devolución de ' . $doc['referencia'], $W));

			$this->op('textaling', 'left');
			$this->texto('Fecha: ' . $v->fecha_texto);
			$this->texto('Cajero: ' . $v->nombre_usuario);
			if (!empty($v->nombre_retira)) {
				// Quien retira el pedido (venta directa): destacado para llamarlo al entregar
				$this->destacado('RETIRA: ' . mb_strtoupper($v->nombre_retira), 26, true, 'center');   // texto.3: solo centrado
			}
			if ($v->rut) {
				$this->lineas('Cliente: ' . $v->nombre_cliente, $W);
				$this->texto('RUT: ' . $rut($v->rut, $v->dv));
				if ($v->giro) $this->lineas('Giro: ' . $v->giro, $W);
				if ($v->direccion) $this->lineas('Dir: ' . $v->direccion, $W);
				if ($v->comuna) $this->lineas('Comuna: ' . $v->comuna, $W);
				if (!empty($v->solicitante)) $this->lineas('Solicita: ' . $v->solicitante, $W);
				if (!empty($v->motivo_factura)) $this->lineas('Motivo: ' . $v->motivo_factura, $W);
			}
			$this->linea($W);
			foreach ($doc['detalle'] as $d) {
				// v1.24.0: producto elegido de un menú: "+ nombre" debajo del menú; sin precio si no tiene costo adicional
				// v1.40.0 modo texto: igual que caja/ticket.php (lo del menú sin costo: solo "+ nombre"; con costo:
				// "+ nombre" y debajo "cant x precio ... total", como un producto)
				if (!empty($d->del_menu) && (int)round((float)$d->monto_mostrar) === 0) {
					$this->lineas('  + ' . ($cant($d->cantidad) !== '1' ? $cant($d->cantidad) . ' ' : '') . $d->nombre, $W);
					continue;
				}
				$this->lineas((!empty($d->del_menu) ? '  + ' : '') . $d->nombre . ($d->exento ? ' (EXENTO)' : ''), $W);
				$this->fila('  ' . $cant($d->cantidad) . ' x ' . $m($d->precio_mostrar), $m($d->monto_mostrar), $W);
			}
			$this->linea($W);
			$this->fila('Subtotal' . ($v->en_neto ? ' neto' : ''), $m($v->subtotal_mostrar), $W);
			if ((int)$v->descuento_mostrar > 0) $this->fila('Descuento', '-' . $m($v->descuento_mostrar), $W);
			$this->fila('Monto neto', $m($v->neto), $W);
			$this->fila('IVA (19%)', $m($v->iva), $W);
			if ((int)abs((int)($v->ila ?? 0)) > 0) $this->fila('ILA', $m($v->ila), $W);   // v1.20.1
			if ($v->monto_exento > 0) $this->fila('Monto exento', $m($v->monto_exento), $W);
			$this->destacado(($nc ? 'TOTAL DEVUELTO ' : 'TOTAL ') . $m($v->total), 30, true, 'right');   
			$this->op('fontbold', false);
			$this->op('fontsize', 1);
			$propina = isset($v->valor_propina) ? (int)$v->valor_propina : 0;
			if ($propina > 0) {
				$this->fila('Propina (voluntaria)', $m($propina), $W);
				$this->op('fontbold', true);
				$this->fila('Total con propina', $m($v->total + $propina), $W);
				$this->op('fontbold', false);
			}
			if ($doc['pagos']) {
				$this->linea($W);
				foreach ($doc['pagos'] as $p) {
					$this->fila(($nc ? 'Devuelto en ' : '') . $p->nombre_fpago, $m($p->paga_con), $W);
					if ((int)$p->vuelto > 0) $this->fila('Vuelto', $m($p->vuelto), $W);
				}
			}
			if ($v->mensaje_pedido) {
				$this->linea($W);
				$this->lineas($v->mensaje_pedido, $W);
			}

			if (!empty($doc['electronico']) && empty($doc['emitido'])) {
				$this->op('feed', 1);
				$this->op('textaling', 'center');
				$this->negrita($this->lineasTexto('Documento pendiente de envío al SII.', $W));
			} elseif ($conTimbre) {
				// Timbre electrónico: el agente dibuja el PDF417 con el TED (tamaños de mipdv_web)
				$this->op('feed', 1);
				$this->op('textaling', 'center');
				$this->op('pdf417', $papel == 58 ? array('valor' => (string)$doc['ted'], 'ancho' => 330, 'alto' => 170)
					: array('valor' => (string)$doc['ted'], 'ancho' => 480, 'alto' => 210));
				$this->negrita(array('Timbre Electrónico SII'));
				if (!empty($sii['resolucion'])) $this->texto($sii['resolucion']);
				if (!empty($sii['verificacion'])) $this->lineas('Verifique documento: ' . $sii['verificacion'], $W);
			}
			$this->op('textaling', 'left');
			$this->linea($W);
			$this->op('textaling', 'center');
			$this->texto($nc ? 'Firma cliente: ______________' : '¡Gracias por su compra!');
			if ($sii && !empty($sii['publicidad'])) {
				foreach ($sii['publicidad'] as $l) $this->lineas($l, $W);
			}
			if ($copia) {
				$this->op('feed', 1);
				$this->op('textaling', 'left');
				$this->negrita(array('ACUSE DE RECIBO'));
				foreach (array('Nombre', 'R.U.T.', 'Fecha', 'Recinto', 'Firma') as $campo) {
					$this->op('feed', 1);
					$this->texto($campo . ': ' . str_repeat('_', max(5, $W - mb_strlen($campo) - 2)));
				}
				$this->lineas('El acuse de recibo que se declara en este acto, de acuerdo a lo dispuesto en la letra b) del Art. 4°, '
					. 'y la letra c) del Art. 5° de la Ley 19.983, acredita que la entrega de mercaderías o servicio(s) prestado(s) '
					. 'ha(n) sido recibido(s).', $W);
				$this->op('textaling', 'center');
				$this->destacado('CEDIBLE', 26);
			}
			$this->op('textaling', 'left');
			$this->op('feed', 4);
			$this->op('cut', '');
		}
		return $this->ops;
	}

	/**
	 * 16-11-2026: precuenta de mesa para el agente de impresión local (antes solo en el navegador: abría el cuadro de
	 * impresión y había que elegir la impresora). Mismo contenido que views/caja/precuenta.php.
	 * $d = Mcaja::precuentaMesa() + descuento, titulo, parte, inicio. No es un documento tributario.
	 */
	public function precuenta($d, $papel = 80)
	{
		$this->ops = array();
		$W = $papel == 58 ? 28 : 40;
		$this->ancho = $W;
		$this->centrado = false;
		$m = function ($n) { return '$' . number_format((float)$n, 0, ',', '.'); };
		$cant = function ($n) { return rtrim(rtrim(number_format((float)$n, 3, ',', ''), '0'), ','); };
		$e = isset($d['empresa']) ? $d['empresa'] : null;
		$lineas = isset($d['lineas']) ? $d['lineas'] : array();
		$subtotal = (int)array_sum(array_column($lineas, 'bruto'));
		$descuento = min($subtotal, max(0, (int)(isset($d['descuento']) ? $d['descuento'] : 0)));
		$total = $subtotal - $descuento;
		$porPropina = (float)(isset($d['por_propina']) ? $d['por_propina'] : 0);
		$propina = $porPropina > 0 ? (int)round($total * $porPropina / 100) : 0;

		$this->op('fontsize', 1);
		$this->op('fontbold', false);
		$this->op('textaling', 'center');
		if ($e) {
			$this->destacado(mb_strtoupper($e->nombre_fantasia ?: $e->razon_social), 30);
			if (!empty($e->direccion_empresa)) $this->lineas($e->direccion_empresa, $W);
		}
		$this->linea($W);
		$this->negrita(array('PRECUENTA'));
		$this->destacado(mb_strtoupper((string)$d['nombre_mesa']), 28);
		if (!empty($d['titulo'])) $this->negrita($this->lineasTexto(mb_strtoupper($d['titulo']), $W));
		$this->linea($W);
		$this->op('textaling', 'left');
		if (!empty($d['desde'])) $this->texto('Llegada: ' . $d['desde']);
		$this->texto('Emitida: ' . date('d/m/Y H:i'));
		$this->linea($W);
		foreach ($lineas as $l) {
			// v1.24.0: producto elegido de un menú, debajo del menú; con precio solo si tiene costo adicional
			if (!empty($l['es_detalle_menu'])) {
				$t = '   + ' . ($cant($l['cantidad']) !== '1' ? $cant($l['cantidad']) . ' ' : '') . $l['nombre'];
				if ((int)$l['bruto'] !== 0) $this->fila($t, $m($l['bruto']), $W); else $this->lineas($t, $W);
				continue;
			}
			$this->fila($cant($l['cantidad']) . ' x ' . $l['nombre'], $m($l['bruto']), $W);
			if (isset($l['agregado']) && $l['agregado'] !== '') $this->lineas('   + ' . $l['agregado'], $W);
		}
		// v1.23.0: mensaje general de la mesa
		if (!empty($d['mensaje'])) {
			$this->linea($W);
			$this->negrita($this->lineasTexto('MENSAJE DE LA MESA: ' . $d['mensaje'], $W));
		}
		$this->linea($W);
		if ($descuento) {
			$this->fila('Subtotal', $m($subtotal), $W);
			$this->fila('Descuento', '-' . $m($descuento), $W);
		}
		$this->destacado('TOTAL ' . $m($total), 30, true, 'right');
		$this->op('fontbold', false);
		$this->op('fontsize', 1);
		if ($propina) {
			$this->fila('Propina sugerida (' . rtrim(rtrim(number_format($porPropina, 2, ',', ''), '0'), ',') . '%)', $m($propina), $W);
			$this->op('fontbold', true);
			$this->fila('TOTAL CON PROPINA', $m($total + $propina), $W);
			$this->op('fontbold', false);
		}
		// Partes iguales con el descuento repartido (igual que la vista)
		$partes = isset($d['partes']) && is_array($d['partes']) ? array_values($d['partes']) : array();
		if ($partes) {
			if ($descuento > 0) {
				$suma = (int)array_sum($partes);
				$rep = 0;
				foreach ($partes as $i => $mp) {
					$dd = $i === count($partes) - 1 ? $descuento - $rep : ($suma > 0 ? (int)round($descuento * $mp / $suma) : 0);
					$rep += $dd;
					$partes[$i] = max(0, (int)$mp - $dd);
				}
			}
			$inicio = (int)(isset($d['inicio']) ? $d['inicio'] : 0);
			$parte = (int)(isset($d['parte']) ? $d['parte'] : 0);
			$this->linea($W);
			$this->op('textaling', 'center');
			$this->negrita(array('DIVIDIDA EN ' . (count($partes) + $inicio) . ' PARTES'));
			$this->op('textaling', 'left');
			foreach ($partes as $i => $mp) {
				$marca = $parte === $i + 1;
				if ($marca) $this->op('fontbold', true);
				$this->fila(($marca ? '> ' : '') . 'Parte ' . ($i + 1 + $inicio), $m($mp), $W);
				if ($marca) $this->op('fontbold', false);
			}
		}
		$this->op('feed', 1);
		$this->op('textaling', 'center');
		if (!isset($d['con_propina']) || $d['con_propina']) $this->texto('La propina es voluntaria.');   // v1.23.0
		$this->texto('Este documento no es valido como boleta.');
		$this->op('textaling', 'left');
		$this->op('feed', 4);
		$this->op('cut', '');
		return $this->ops;
	}

	// ---------------- apoyo ----------------

	/**
	 * 16-11-2026: este agente no respeta textaling center (todo salía a la izquierda): mientras se pide centrado, cada
	 * texto se rellena con espacios a la izquierda según el ancho del papel ($this->ancho columnas).
	 */
	private $centrado = false;
	private $ancho = 40;

	private function op($accion, $valor = '')
	{
		if ($accion === 'textaling') $this->centrado = ($valor === 'center');
		$this->ops[] = array('accion' => $accion, 'valor' => $valor);
	}

	private function texto($s)
	{
		$s = $this->limpiar($s);
		if ($this->centrado) {
			$s = trim($s);
			$s = str_repeat(' ', max(0, (int)floor(($this->ancho - mb_strlen($s)) / 2))) . $s;
		}
		$this->op('texto', $s);
	}

	private function negrita($lineas)
	{
		$this->op('fontbold', true);
		foreach ((array)$lineas as $l) $this->texto($l);
		$this->op('fontbold', false);
	}

	private function lineas($s, $W)
	{
		foreach ($this->lineasTexto($s, $W) as $l) $this->texto($l);
	}

	private function linea($W, $caracter = '-')
	{
		$this->op('linea', array('caracter' => $caracter, 'alineacion' => 'left', 'ancho' => $W, 'ancho_maximo' => $W));
	}

	/** Texto a la izquierda y monto a la derecha en una línea (el texto largo pasa a la línea de arriba), como Ticket_impresion. */
	private function fila($texto, $monto, $W)
	{
		$texto = $this->limpiar($texto);
		$libre = $W - mb_strlen($monto) - 1;
		if (mb_strlen($texto) > $libre) {
			$this->lineas($texto, $W);
			$this->texto(str_repeat(' ', max(0, $W - mb_strlen($monto))) . $monto);
			return;
		}
		$this->texto($texto . str_repeat(' ', $W - mb_strlen($texto) - mb_strlen($monto)) . $monto);
	}

	/** Corta por palabras contando caracteres (no bytes), como Ticket_impresion::lineas() */
	private function lineasTexto($s, $W)
	{
		// v1.40.0 modo texto: respeta la sangría del comienzo ("  + agregado") en todas sus líneas
		$s = rtrim($this->limpiar($s));
		$sangria = (string)substr($s, 0, strlen($s) - strlen(ltrim($s, ' ')));
		if ($sangria !== '' && strlen($sangria) < $W - 8) {
			return array_map(function ($l) use ($sangria) { return $sangria . $l; }, $this->lineasTexto(ltrim($s, ' '), $W - strlen($sangria)));
		}
		$s = trim($s);
		if ($s === '') return array();
		$out = array();
		$linea = '';
		foreach (preg_split('/\s+/u', $s) as $p) {
			while (mb_strlen($p) > $W) {
				if ($linea !== '') { $out[] = $linea; $linea = ''; }
				$out[] = mb_substr($p, 0, $W);
				$p = mb_substr($p, $W);
			}
			if ($linea === '') $linea = $p;
			elseif (mb_strlen($linea) + 1 + mb_strlen($p) <= $W) $linea .= ' ' . $p;
			else { $out[] = $linea; $linea = $p; }
		}
		if ($linea !== '') $out[] = $linea;
		return $out;
	}

	private function limpiar($s)
	{
		$s = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string)$s);
		// 16-11-2026: el símbolo de grado sale como "ø" en este agente ("Nø 80068")
		return str_replace(array('N°', 'Nº', '°', 'º'), array('N.', 'N.', 'o', 'o'), $s);
	}

	private function destacado($texto, $tam, $negrita = true, $alinea = 'center')
	{
		// v1.40.0 (modo texto): lo destacado (local, documento, TOTAL...) en letra alta (tamaño 2) y negrita; el agente no
		// dibuja texto. A la derecha (TOTAL) = etiqueta y monto en una fila en letra normal negrita (con letra alta se descuadra).
		$t = trim((string)$texto);
		if ($t === '') return;
		$this->op('fontsize', 2);
		$this->op('fontbold', true);
		if ($alinea === 'right' && preg_match('/^(.*\S)\s+(\S+)$/u', $t, $m)) {
			// con la letra alta los espacios no miden lo mismo y el monto salía al medio: TOTAL en letra normal negrita
			$this->op('fontsize', 1);
			$antes = $this->centrado;
			$this->op('textaling', 'left');
			$this->fila($m[1], $m[2], $this->ancho);
			$this->op('textaling', $antes ? 'center' : 'left');
		} else {
			$antes = $this->centrado;
			$this->op('textaling', 'center');
			foreach ($this->lineasTexto($t, $this->ancho) as $l) $this->texto($l);
			$this->op('textaling', $antes ? 'center' : 'left');
		}
		$this->op('fontbold', false);
		$this->op('fontsize', 1);
	}

}
