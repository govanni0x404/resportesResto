<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 18-11-2026: límite de intentos para las claves (de administrador en la Caja y del garzón en la app). Antes se podían
 * probar claves sin parar hasta acertar.
 *
 * Regla: 5 claves incorrectas en 10 minutos → bloqueo de 5 minutos para esa clave de conteo (ej. ese cajero en esa
 * empresa). Una clave correcta vuelve el contador a cero.
 *
 * Uso:
 *   $this->limite_intentos->revisar($clave);   // lanza Exception 429 si está bloqueado
 *   ...comparar la clave...
 *   $ok ? $this->limite_intentos->exito($clave) : $this->limite_intentos->fallo($clave);
 *
 * Se guarda en application/cache/intentos (no se sirve por la web); si no se puede escribir, en la temporal del sistema.
 * Si no hay dónde guardar, no bloquea (mejor no dejar al local sin poder autorizar).
 */
class Limite_intentos
{
	const MAX_FALLOS = 5;
	const VENTANA = 600;   // segundos en que se cuentan los fallos
	const BLOQUEO = 300;   // segundos de bloqueo

	private function carpeta()
	{
		foreach (array(APPPATH . 'cache' . DIRECTORY_SEPARATOR . 'intentos', rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'resto_intentos') as $d) {
			if (!is_dir($d)) @mkdir($d, 0770, true);
			if (is_dir($d) && is_writable($d)) return $d;
		}
		return null;
	}

	private function archivo($clave)
	{
		$d = $this->carpeta();
		return $d === null ? null : $d . DIRECTORY_SEPARATOR . hash('sha256', (string)$clave) . '.json';
	}

	/** Lee y, si $cambio, modifica el registro con candado (dos intentos a la vez no se pisan). */
	private function con($clave, $cambio = null)
	{
		$f = $this->archivo($clave);
		if ($f === null) return array('fallos' => array(), 'hasta' => 0);
		$h = @fopen($f, 'c+');
		if (!$h) return array('fallos' => array(), 'hasta' => 0);
		flock($h, $cambio ? LOCK_EX : LOCK_SH);
		$d = json_decode((string)stream_get_contents($h), true);
		if (!is_array($d)) $d = array();
		$d += array('fallos' => array(), 'hasta' => 0);
		if ($cambio) {
			$d = $cambio($d);
			ftruncate($h, 0);
			rewind($h);
			fwrite($h, json_encode($d));
			fflush($h);
		}
		flock($h, LOCK_UN);
		fclose($h);
		return $d;
	}

	/** Lanza Exception 429 si esa clave de conteo está bloqueada. */
	public function revisar($clave)
	{
		$d = $this->con($clave);
		$falta = (int)$d['hasta'] - time();
		if ($falta > 0) {
			$min = (int)ceil($falta / 60);
			throw new Exception('Demasiados intentos con clave incorrecta. Espera ' . $min . ' minuto' . ($min === 1 ? '' : 's') . ' antes de volver a intentarlo', 429);
		}
	}

	/** Cuenta un fallo; al llegar al máximo, bloquea. Devuelve los intentos que quedan antes del bloqueo. */
	public function fallo($clave)
	{
		$ahora = time();
		$d = $this->con($clave, function ($d) use ($ahora) {
			$d['fallos'] = array_values(array_filter((array)$d['fallos'], function ($t) use ($ahora) { return (int)$t > $ahora - Limite_intentos::VENTANA; }));
			$d['fallos'][] = $ahora;
			if (count($d['fallos']) >= Limite_intentos::MAX_FALLOS) {
				$d['hasta'] = $ahora + Limite_intentos::BLOQUEO;
				$d['fallos'] = array();
			}
			return $d;
		});
		return (int)$d['hasta'] > $ahora ? 0 : self::MAX_FALLOS - count($d['fallos']);
	}

	/** Clave correcta: el contador vuelve a cero. */
	public function exito($clave)
	{
		$f = $this->archivo($clave);
		if ($f !== null && is_file($f)) @unlink($f);
	}
}
