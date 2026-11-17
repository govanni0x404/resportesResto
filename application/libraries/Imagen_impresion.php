<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 17-11-2026: imágenes de documentos para el agente de impresión (impresionWeb.exe).
 *
 * La Caja dibuja la boleta, precuenta o comanda con el diseño de la página (assets/js/impresion-local/imagenDocumento.js)
 * y la sube aquí (Caja/subirImagenImpresion). El agente solo imprime imágenes que descarga él mismo por dirección web
 * (orden img_url) y no tiene sesión, así que cada imagen se guarda con una clave aleatoria de 128 bits y se entrega
 * sin sesión en ImagenImpresion/ver/<clave>.png. Vence a los 10 minutos.
 *
 * Carpeta: application/cache/impresion (application/ no se sirve por la web); si no se puede escribir, la temporal del
 * sistema.
 */
class Imagen_impresion
{
	const VIDA = 600;            // segundos
	const MAX_BYTES = 3145728;   // 3 MB

	private function carpeta()
	{
		foreach (array(APPPATH . 'cache' . DIRECTORY_SEPARATOR . 'impresion', rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'resto_impresion') as $d) {
			if (!is_dir($d)) @mkdir($d, 0770, true);
			if (is_dir($d) && is_writable($d)) return $d;
		}
		throw new Exception('El servidor no tiene una carpeta para guardar la imagen a imprimir', 500);
	}

	/**
	 * Valida y guarda el PNG subido ($_FILES['imagen']). Devuelve la clave.
	 * Solo PNG del tamaño de una térmica: 100 a 1000 puntos de ancho y hasta 20000 de largo.
	 */
	public function guardar($archivo)
	{
		if (!is_array($archivo) || !isset($archivo['error']) || $archivo['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'])) {
			throw new Exception('No llegó la imagen a imprimir', 400);
		}
		if ($archivo['size'] <= 0 || $archivo['size'] > self::MAX_BYTES) throw new Exception('La imagen a imprimir es demasiado grande', 400);
		$info = @getimagesize($archivo['tmp_name']);
		if (!$info || $info[2] !== IMAGETYPE_PNG || $info[0] < 100 || $info[0] > 1000 || $info[1] < 20 || $info[1] > 20000) {
			throw new Exception('La imagen a imprimir no es válida', 400);
		}
		$dir = $this->carpeta();
		$this->limpiar($dir);
		$clave = bin2hex(random_bytes(16));
		if (!move_uploaded_file($archivo['tmp_name'], $dir . DIRECTORY_SEPARATOR . $clave . '.png')) {
			throw new Exception('No se pudo guardar la imagen a imprimir', 500);
		}
		return $clave;
	}

	/** Dirección (solo la ruta) donde el agente descarga la imagen; la pantalla le pone el equipo y el puerto. */
	public function rutaPublica($clave)
	{
		return rtrim((string)parse_url(base_url(), PHP_URL_PATH), '/') . '/index.php/ImagenImpresion/ver/' . $clave . '.png';
	}

	/**
	 * 18-11-2026: firma para subir imágenes SIN la sesión (ImagenImpresion/subir). Con sesiones en archivos, PHP atiende
	 * de a una las peticiones de un usuario: después de cobrar la subida esperaba ~2-3 s en la fila (medido con
	 * ?medir=1). La Caja recibe la firma al cargar; vale $horas. Formato: vence.azar.hmac
	 */
	public function firma($horas = 24)
	{
		$secreto = $this->secreto();
		if ($secreto === null) return null;
		$cuerpo = (time() + (int)$horas * 3600) . '.' . bin2hex(random_bytes(8));
		return $cuerpo . '.' . hash_hmac('sha256', $cuerpo, $secreto);
	}

	public function firmaValida($firma)
	{
		if (!is_string($firma) || !preg_match('/^(\d{10})\.([a-f0-9]{16})\.([a-f0-9]{64})$/', $firma, $m)) return false;
		if ((int)$m[1] < time()) return false;
		$secreto = $this->secreto();
		return $secreto !== null && hash_equals(hash_hmac('sha256', $m[1] . '.' . $m[2], $secreto), $m[3]);
	}

	/**
	 * Clave secreta de este servidor para las firmas: se crea sola la primera vez (64 hex) en la carpeta de las imágenes,
	 * que no se sirve por la web. Si se borra, se crea otra y las firmas viejas dejan de valer (la Caja sube con la sesión).
	 */
	private function secreto()
	{
		try {
			$f = $this->carpeta() . DIRECTORY_SEPARATOR . 'firma.key';
		} catch (Exception $e) {
			return null;
		}
		$s = @file_get_contents($f);
		if (is_string($s) && preg_match('/^[a-f0-9]{64}$/', $s)) return $s;
		if (@file_put_contents($f, bin2hex(random_bytes(32)), LOCK_EX) === false) return null;
		@chmod($f, 0600);
		// Otra petición pudo crearla a la vez: vale la que quedó escrita
		$s = @file_get_contents($f);
		return (is_string($s) && preg_match('/^[a-f0-9]{64}$/', $s)) ? $s : null;
	}

	/** Ruta del archivo de la clave, o null si no existe o venció. */
	public function ruta($clave)
	{
		if (!is_string($clave) || !preg_match('/^[a-f0-9]{32}$/', $clave)) return null;
		try {
			$r = $this->carpeta() . DIRECTORY_SEPARATOR . $clave . '.png';
		} catch (Exception $e) {
			return null;
		}
		if (!is_file($r) || filemtime($r) < time() - self::VIDA) return null;
		return $r;
	}

	/** Borra las vencidas. */
	private function limpiar($dir)
	{
		foreach (glob($dir . DIRECTORY_SEPARATOR . '*.png') ?: array() as $f) {
			if (@filemtime($f) < time() - self::VIDA) @unlink($f);
		}
	}
}
