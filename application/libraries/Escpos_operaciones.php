<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 19-11-2026 (auditoría): pasa los bytes ESC/POS que arma Ticket_impresion (cierre / informe X, comprobante de anulación,
 * pago de crédito) a operaciones de PrinterJob (assets/js/impresion-local/printerJob.js), para que esos comprobantes
 * también salgan por el agente de impresión del equipo de la caja. Antes iban solo por el servidor (o su cola) y con el
 * sistema en un servidor Linux y la impresora en el local no salían.
 *
 * Entiende solo lo que usa Ticket_impresion en esos comprobantes: ESC @ (inicio), ESC t n (página de códigos), ESC a n
 * (alineación), ESC E n (negrita), GS ! n (tamaño), GS V (corte) y saltos de línea. El texto viene en CP858.
 */
class Escpos_operaciones
{
	/**
	 * $ancho: columnas del agente con letra normal (40 en papel de 80 mm, 28 en 58 mm, como Comanda_local). Ticket_impresion
	 * arma filas de 42 / 32: las más anchas se ajustan quitando espacios del medio (o se cortan, si son una raya).
	 */
	public static function convertir($bytes, $ancho = 40)
	{
		$ops = array(array('accion' => 'fontsize', 'valor' => 1), array('accion' => 'fontbold', 'valor' => false),
			array('accion' => 'textaling', 'valor' => 'left'));
		$estado = array('textaling' => 'left', 'fontbold' => false, 'fontsize' => 1);
		$pendiente = $estado;
		$linea = '';
		$formato = $pendiente;   // el formato con que empezó la línea (el "normal" suele venir antes del salto)
		$vacias = 0;
		$emitir = function () use (&$ops, &$estado, &$formato, &$linea, &$vacias, $ancho) {
			if ($linea === '') { $vacias++; return; }
			if ($vacias > 0) { $ops[] = array('accion' => 'feed', 'valor' => $vacias); $vacias = 0; }
			foreach (array('textaling', 'fontbold', 'fontsize') as $k) {
				if ($formato[$k] !== $estado[$k]) { $ops[] = array('accion' => $k, 'valor' => $formato[$k]); $estado[$k] = $formato[$k]; }
			}
			$t = @iconv('CP858', 'UTF-8//IGNORE', $linea);
			if ($t === false) $t = preg_replace('/[^\x20-\x7e]/', '?', $linea);
			$t = self::ajustar(rtrim($t), $ancho);
			$ops[] = array('accion' => 'texto', 'valor' => $t);
			$linea = '';
		};
		$n = strlen($bytes);
		for ($i = 0; $i < $n; $i++) {
			$c = $bytes[$i];
			$o = ord($c);
			if ($o === 0x1b && $i + 1 < $n) {   // ESC
				$cmd = $bytes[$i + 1];
				if ($cmd === '@') { $i += 1; continue; }
				if ($i + 2 >= $n) break;
				$arg = ord($bytes[$i + 2]);
				if ($cmd === 'a') $pendiente['textaling'] = $arg === 1 ? 'center' : ($arg === 2 ? 'right' : 'left');
				elseif ($cmd === 'E') $pendiente['fontbold'] = $arg === 1;
				$i += 2;   // ESC t n y cualquier otro ESC x n: se salta
				continue;
			}
			if ($o === 0x1d && $i + 2 < $n) {   // GS
				$cmd = $bytes[$i + 1];
				$arg = ord($bytes[$i + 2]);
				if ($cmd === '!') $pendiente['fontsize'] = $arg === 0 ? 1 : 2;
				if ($cmd === 'V') {
					if ($linea !== '') $emitir();
					$ops[] = array('accion' => 'feed', 'valor' => max(1, $vacias));
					$vacias = 0;
					$ops[] = array('accion' => 'cut', 'valor' => '');
					$i += ($arg === 65 || $arg === 66) ? 3 : 2;   // GS V m n (m = A/B lleva n)
					continue;
				}
				$i += 2;
				continue;
			}
			if ($c === "\n") { $emitir(); continue; }
			if ($o < 0x20) continue;
			if ($linea === '') $formato = $pendiente;
			$linea .= $c;
		}
		if ($linea !== '') $emitir();
		return $ops;
	}

	/** Deja la línea en $ancho columnas: una raya se corta; una fila "texto    monto" pierde espacios del medio. */
	private static function ajustar($t, $ancho)
	{
		$largo = mb_strlen($t);
		if ($largo <= $ancho) return $t;
		if (preg_match('/^([-_=])\1+$/u', $t)) return mb_substr($t, 0, $ancho);
		$sobra = $largo - $ancho;
		if (preg_match('/^(.*\S)( {2,})(\S.*)$/u', $t, $m) && mb_strlen($m[2]) - 1 >= $sobra) {
			return $m[1] . str_repeat(' ', mb_strlen($m[2]) - $sobra) . $m[3];
		}
		$t = ltrim($t);   // centrado con espacios: se quitan los de la izquierda
		return $t;
	}
}
