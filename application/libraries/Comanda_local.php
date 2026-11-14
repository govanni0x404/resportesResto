<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Comanda de un destino de impresión (cocina, barra...) para el agente de impresión local del equipo (12-11-2026): mismo
 * contenido que Comanda_impresion::armar(), pero en operaciones de PrinterJob (assets/js/impresion-local/printerJob.js);
 * la Caja se las manda al agente (http://localhost:5050), que imprime en la impresora del destino (la de Windows/CUPS de
 * ese equipo o la IP y puerto). Así funciona con el sistema en un servidor y las impresoras en el local.
 *
 * Tamaños como Ticket_local: fontsize 1 = normal (40 columnas en 80 mm, 28 en 58 mm), 2 = destacado (se corta a la mitad).
 */
class Comanda_local
{
	private $ops = array();

	/**
	 * $grupo = ['nombre' =>, 'destino' => [...], 'lineas' => [...]] (Caja::datosComanda); $mesa: nombre visible de la mesa
	 * o '' en venta directa; $aviso: null o ['tipo' => 'anulacion' | 'traslado' | 'reenvio', ...] (igual que Comanda_impresion).
	 */
	public function operaciones($grupo, $venta, $observacion, $mesa = '', $aviso = null)
	{
		$this->ops = array();
		$papel = self::papel(isset($grupo['destino']) ? $grupo['destino'] : array());
		$W = $papel == 58 ? 28 : 40;
		$W2 = (int)floor($W / 2);   // texto destacado (fontsize 2)
		$tipo = $aviso ? $aviso['tipo'] : '';
		$anula = $tipo === 'anulacion';

		$this->op('fontsize', 1);
		$this->op('fontbold', false);
		$this->op('textaling', 'center');
		// Cabecera: destino y tipo de comanda
		$this->grande(mb_strtoupper($grupo['nombre']), $W2);
		if ($anula) {
			$this->grande('ANULACION', $W2);
			$this->negrita(array('NO PREPARAR'));
		} elseif ($tipo === 'traslado') {
			$this->grande('CAMBIO DE MESA', $W2);
			$this->negrita(array('ENTREGAR EN LA MESA NUEVA'));
		} elseif ($tipo === 'reenvio') {
			$this->grande('REENVIO', $W2);
			$this->negrita(array('YA SE HABIA PEDIDO'));
		} else {
			$this->negrita(array('C O M A N D A'));
		}
		if ($mesa !== '' && $mesa !== 0) {
			$this->grande(mb_strtoupper($mesa), $W2);
			if ($tipo === 'traslado') $this->negrita($this->lineasTexto('Antes: ' . mb_strtoupper($aviso['desde']), $W));
		} elseif ($venta) {
			$this->grande('N ' . $venta->folio, $W2);
			$this->texto((string)$venta->nombre_tdocumento);
		}
		// Quien retira el pedido (venta directa)
		if ($venta && !empty($venta->nombre_retira)) $this->grande('RETIRA: ' . mb_strtoupper($venta->nombre_retira), $W2);
		$this->texto($venta && !empty($venta->fecha_texto) ? $venta->fecha_texto : date('d/m/Y H:i'));
		if ($venta && !empty($venta->nombre_usuario)) $this->texto('Atiende: ' . $venta->nombre_usuario);
		$this->op('textaling', 'left');
		$this->linea($W, '=');

		// Productos (destacados para leerlos de lejos), agregados y nota del garzón
		$total = 0;
		$n = count($grupo['lineas']);
		foreach ($grupo['lineas'] as $i => $l) {
			$total += (float)$l['cantidad'];
			$cant = rtrim(rtrim(number_format((float)$l['cantidad'], 3, ',', ''), '0'), ',');
			$this->grande($cant . ' x ' . mb_strtoupper($l['nombre']), $W2);
			$agregados = array();
			foreach ($l['agregados'] as $a) $agregados = array_merge($agregados, $this->lineasTexto('   + ' . $a, $W));
			$this->negrita($agregados);
			if ($l['nota'] !== '') $this->negrita($this->lineasTexto(' >> ' . mb_strtoupper($l['nota']), $W));
			if ($i < $n - 1) $this->linea($W);
		}
		$this->linea($W, '=');
		$cantTxt = rtrim(rtrim(number_format($total, 3, ',', ''), '0'), ',');
		$this->negrita(array(($anula ? 'ANULADO: ' : ($tipo === 'traslado' ? 'CAMBIAN DE MESA: ' : 'TOTAL: ')) . $cantTxt . ($total == 1 ? ' producto' : ' productos')));
		if ($anula && $aviso['motivo'] !== '') $this->negrita($this->lineasTexto('Motivo: ' . $aviso['motivo'], $W));
		if ($observacion !== '') {
			$this->op('feed', 1);
			$this->negrita(array('OBSERVACION'));
			$this->grande($observacion, $W2);
		}
		$this->op('feed', 4);
		$this->op('cut', '');
		return $this->ops;
	}

	/** Ancho del papel por el nombre de la impresora del destino (58 si lo dice, si no 80), como impresionLocal.js */
	public static function papel($destino)
	{
		$s = mb_strtolower(trim((string)(isset($destino['ruta_windows']) ? $destino['ruta_windows'] : '')) . ' ' . (isset($destino['nombre']) ? $destino['nombre'] : ''));
		return preg_match('/(^|[^0-9])58([^0-9]|$)|58mm/', $s) ? 58 : 80;
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
		if (!$lineas) return;
		$this->op('fontbold', true);
		foreach ((array)$lineas as $l) $this->texto($l);
		$this->op('fontbold', false);
	}

	/** Texto destacado (fontsize 2, negrita), cortado a la mitad del ancho */
	private function grande($s, $W2)
	{
		$this->op('fontsize', 2);
		$this->op('fontbold', true);
		foreach ($this->lineasTexto($s, $W2) as $l) $this->texto($l);
		$this->op('fontbold', false);
		$this->op('fontsize', 1);
	}

	private function linea($W, $caracter = '-')
	{
		$this->op('linea', array('caracter' => $caracter, 'alineacion' => 'left', 'ancho' => $W, 'ancho_maximo' => $W));
	}

	/** Corta por palabras contando caracteres (no bytes) */
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
