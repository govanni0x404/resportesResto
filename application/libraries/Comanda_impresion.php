<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Comandas directas a la impresora de cada destino de impresión (cocina, barra...), sin pasar por el navegador.
 * Se arma un texto ESC/POS (térmica) y se envía desde el servidor:
 *  - ruta de Windows ("//equipo/impresora", impresora compartida) cuando el servidor es Windows, o
 *  - IP y puerto (impresora de red, normalmente 9100) en cualquier sistema.
 */
class Comanda_impresion
{
	const ANCHO = 42;   // columnas de una térmica de 80 mm

	/** Texto ESC/POS de la comanda de un destino. $grupo = ['nombre' =>, 'lineas' => [...]]. */
	public function armar($grupo, $venta, $observacion)
	{
		$W = self::ANCHO;
		$centro = "\x1ba\x01";
		$izq = "\x1ba\x00";
		$normal = "\x1d!\x00\x1bE\x00\x1dB\x00";
		$doble = "\x1d!\x11";          // doble ancho y alto
		$alto = "\x1d!\x01";           // doble alto
		$neg = "\x1bE\x01";            // negrita
		$inv = "\x1dB\x01";            // fondo negro, letra blanca

		$b = "\x1b@\x1bt\x13" . $normal;                       // iniciar + CP858 (acentos)
		// Cabecera: destino en recuadro negro
		$b .= $centro . $inv . $doble . $this->t(str_pad('', 0)) . $this->centrar(mb_strtoupper($grupo['nombre']), $W / 2) . $normal . "\n";
		$b .= $centro . $neg . "C O M A N D A" . $normal . "\n";
		if ($venta) {
			$b .= $centro . $doble . $this->t('N ' . $venta->folio) . $normal . "\n";
			$b .= $centro . $this->t((string)$venta->nombre_tdocumento) . "\n";
		}
		$hora = $venta && !empty($venta->fecha_texto) ? $venta->fecha_texto : date('d/m/Y H:i');
		$b .= $centro . $this->t($hora) . "\n";
		if ($venta && !empty($venta->nombre_usuario)) $b .= $centro . $this->t('Atiende: ' . $venta->nombre_usuario) . "\n";
		$b .= $izq . str_repeat('=', $W) . "\n";

		// Productos
		$total = 0;
		$n = count($grupo['lineas']);
		foreach ($grupo['lineas'] as $i => $l) {
			$total += (float)$l['cantidad'];
			$cant = rtrim(rtrim(number_format((float)$l['cantidad'], 3, ',', ''), '0'), ',');
			$b .= $izq . $neg . $alto . $this->envolver($cant . ' x ' . mb_strtoupper($l['nombre']), $W) . $normal;
			foreach ($l['agregados'] as $a) $b .= $neg . $this->envolver('   + ' . $a, $W) . $normal;
			if ($l['nota'] !== '') {
				$b .= "\n" . $inv . $neg . $this->envolver(' >> ' . mb_strtoupper($l['nota']), $W) . $normal;
			}
			$b .= $i < $n - 1 ? str_repeat('-', $W) . "\n" : "\n";
		}
		$b .= str_repeat('=', $W) . "\n";
		$cantTxt = rtrim(rtrim(number_format($total, 3, ',', ''), '0'), ',');
		$b .= $neg . $this->t('TOTAL: ' . $cantTxt . ($total == 1 ? ' producto' : ' productos')) . $normal . "\n";
		if ($observacion !== '') {
			$b .= "\n" . $inv . $neg . $this->t(str_pad(' OBSERVACION', $W)) . $normal . "\n" . $neg . $alto . $this->envolver($observacion, $W) . $normal;
		}
		$b .= "\n" . $izq;
		return $b . "\n\n\n\n\x1dVB\x00";   // avanzar y cortar
	}

	/** Texto centrado en un ancho de $cols columnas (con espacios a los lados, para el recuadro negro). */
	private function centrar($s, $cols)
	{
		$cols = (int)$cols;
		$s = mb_substr($s, 0, $cols);
		$izq = (int)floor(($cols - mb_strlen($s)) / 2);
		return $this->t(str_repeat(' ', $izq) . $s . str_repeat(' ', $cols - mb_strlen($s) - $izq));
	}

	/** Envía los bytes a la impresora del destino. Devuelve [ok, mensaje]. */
	public function enviar($destino, $bytes)
	{
		$ruta = trim((string)$destino['ruta_windows']);
		$esUnc = (bool)preg_match('#^(\\\\\\\\|//)[^/\\\\]+[/\\\\].+#', $ruta);
		$windows = stripos(PHP_OS_FAMILY, 'Windows') === 0;
		$ip = trim((string)$destino['ip']);
		$puerto = (int)$destino['puerto'];
		$ipValida = $ip !== '' && $puerto > 1 && (filter_var($ip, FILTER_VALIDATE_IP) || preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/i', $ip));

		if ($esUnc && $windows) {
			$ok = @file_put_contents(str_replace('/', '\\', $ruta), $bytes);
			if ($ok !== false) return array(true, 'impresora ' . $ruta);
			if (!$ipValida) return array(false, 'No se pudo escribir en la impresora ' . $ruta . ' (revisa que esté compartida y encendida)');
		}
		if ($ipValida) {
			$s = @stream_socket_client('tcp://' . $ip . ':' . $puerto, $errno, $errstr, 3);
			if (!$s) return array(false, 'No se pudo conectar a ' . $ip . ':' . $puerto . ' (' . $errstr . ')');
			stream_set_timeout($s, 5);
			$total = strlen($bytes);
			$escrito = 0;
			while ($escrito < $total) {
				$n = @fwrite($s, substr($bytes, $escrito));
				if ($n === false || $n === 0) break;
				$escrito += $n;
			}
			fclose($s);
			return $escrito === $total ? array(true, 'impresora ' . $ip . ':' . $puerto) : array(false, 'La impresora ' . $ip . ':' . $puerto . ' no recibió todo el texto');
		}
		if ($esUnc) return array(false, 'El servidor no es Windows: configura IP y puerto de la impresora en Destino de impresión');
		return array(false, 'El destino no tiene una ruta ni una IP y puerto válidos');
	}

	private function t($s)
	{
		$r = @iconv('UTF-8', 'CP858//TRANSLIT//IGNORE', (string)$s);
		return $r === false ? preg_replace('/[^\x20-\x7e]/', '?', (string)$s) : $r;
	}

	private function envolver($s, $ancho)
	{
		$ancho = max(10, (int)$ancho);
		$out = '';
		foreach (explode("\n", wordwrap(trim(preg_replace('/\s+/', ' ', $s), ' '), $ancho, "\n", true) ?: '') as $linea) {
			$out .= $this->t($linea) . "\n";
		}
		return $out;
	}
}
