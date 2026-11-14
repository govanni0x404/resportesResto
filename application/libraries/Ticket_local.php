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
				$this->negrita($this->lineasTexto($e->nombre_fantasia ?: $e->razon_social, $W));
				if ($e->nombre_fantasia && $e->razon_social) $this->lineas($e->razon_social, $W);
				$this->texto('RUT ' . $rut($e->rut_empresa, $e->dv_empresa));
				if (!empty($e->giro)) $this->lineas($e->giro, $W);
				$dir = trim($e->direccion_empresa . (!empty($e->comuna) ? ', ' . $e->comuna : ''));
				if ($dir !== '') $this->lineas($dir, $W);
				if (!empty($e->telefono)) $this->texto('Fono ' . $e->telefono);
			}
			// Recuadro del documento
			$this->linea($W);
			$recuadro = array();
			if ($sii && $e) $recuadro[] = 'R.U.T.: ' . $rut($e->rut_empresa, $e->dv_empresa);
			$recuadro = array_merge($recuadro, $this->lineasTexto(mb_strtoupper($v->nombre_tdocumento), $W));
			$recuadro[] = 'N° ' . (int)$v->folio;
			$this->negrita($recuadro);
			$this->linea($W);
			if ($sii && !empty($sii['oficina'])) $this->negrita(array($sii['oficina']));
			if (!empty($doc['anulada'])) {
				$this->op('fontsize', 2);
				$this->negrita(array('ANULADA'));
				$this->op('fontsize', 1);
			}
			if ($nc) $this->negrita($this->lineasTexto('Devolución de ' . $doc['referencia'], $W));

			$this->op('textaling', 'left');
			$this->texto('Fecha: ' . $v->fecha_texto);
			$this->texto('Cajero: ' . $v->nombre_usuario);
			if (!empty($v->nombre_retira)) {
				// Quien retira el pedido (venta directa): destacado para llamarlo al entregar
				$this->op('fontsize', 2);
				$this->negrita($this->lineasTexto('Retira: ' . $v->nombre_retira, (int)floor($W / 2)));
				$this->op('fontsize', 1);
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
				$this->lineas($d->nombre . ($d->exento ? ' (EXENTO)' : ''), $W);
				$this->fila('  ' . $cant($d->cantidad) . ' x ' . $m($d->precio_mostrar), $m($d->monto_mostrar), $W);
			}
			$this->linea($W);
			$this->fila('Subtotal' . ($v->en_neto ? ' neto' : ''), $m($v->subtotal_mostrar), $W);
			if ((int)$v->descuento_mostrar > 0) $this->fila('Descuento', '-' . $m($v->descuento_mostrar), $W);
			$this->fila('Monto neto', $m($v->neto), $W);
			$this->fila('IVA (19%)', $m($v->iva), $W);
			if ($v->monto_exento > 0) $this->fila('Monto exento', $m($v->monto_exento), $W);
			$this->op('fontbold', true);
			$this->fila($nc ? 'TOTAL DEVUELTO' : 'TOTAL', $m($v->total), $W);
			$this->op('fontbold', false);
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
				$this->op('fontsize', 2);
				$this->negrita(array('CEDIBLE'));
				$this->op('fontsize', 1);
			}
			$this->op('textaling', 'left');
			$this->op('feed', 4);
			$this->op('cut', '');
		}
		return $this->ops;
	}

	// ---------------- apoyo ----------------

	private function op($accion, $valor = '')
	{
		$this->ops[] = array('accion' => $accion, 'valor' => $valor);
	}

	private function texto($s)
	{
		$this->op('texto', $this->limpiar($s));
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
		$s = trim($this->limpiar($s));
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
		return preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string)$s);
	}
}
