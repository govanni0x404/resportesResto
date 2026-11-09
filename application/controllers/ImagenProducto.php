<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Imágenes de productos de la Carta (se ven en la Caja). No usa columnas nuevas: cada imagen se guarda como
 * archivo uploads/<rut de la empresa>/productos/<id_producto>.webp (ej. uploads/76193938-6/productos/15.webp),
 * redimensionada a un máximo de 600 px. WEBP pesa bastante menos que JPG con la misma calidad.
 * Acciones (JSON): subir (archivo), buscar (imágenes libres en Openverse), desdeUrl (guarda una de la búsqueda),
 * eliminar e info.
 */
class ImagenProducto extends CI_Controller
{
	const MAX_BYTES = 6291456;   // 6 MB
	const MAX_LADO = 600;

	public function info()
	{
		$this->responder(function () {
			$id = $this->idProducto($this->input->get('id_producto'));
			return array('url' => self::urlImagen($this->carpetaEmpresa(), $id));
		});
	}

	public function subir()
	{
		$this->responder(function () {
			$id = $this->idProducto($this->input->post('id_producto'));
			if (empty($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
				throw new Exception('No se recibió la imagen', 400);
			}
			if ($_FILES['imagen']['size'] > self::MAX_BYTES) throw new Exception('La imagen supera los 6 MB', 400);
			$datos = file_get_contents($_FILES['imagen']['tmp_name']);
			return array('url' => $this->guardar($id, $datos));
		}, true);
	}

	/**
	 * Busca imágenes de uso libre por nombre. Primero en Wikimedia Commons (responde en ~1 s); si no responde o no
	 * encuentra nada, en Openverse (Flickr y otros), que con palabras nuevas tarda ~10 s y a veces se cae (error 504).
	 * Los resultados se guardan 7 días en application/cache: buscar de nuevo lo mismo es inmediato.
	 */
	public function buscar()
	{
		$this->responder(function () {
			$q = trim((string)$this->input->get('q'));
			if (mb_strlen($q) < 2) throw new Exception('Escribe al menos 2 letras para buscar', 400);
			$q = mb_strtolower(mb_substr($q, 0, 80));
			$cache = APPPATH . 'cache/imagenes_' . md5($q) . '.json';
			if (is_file($cache) && filemtime($cache) > time() - 7 * 86400) {
				$res = json_decode((string)file_get_contents($cache), true);
				if (is_array($res)) return array('imagenes' => $res);
			}
			$res = array();
			$fallos = 0;
			foreach (array('buscarWikimedia', 'buscarOpenverse') as $buscador) {
				try {
					$res = $this->$buscador($q);
				} catch (Exception $e) {
					log_message('error', 'ImagenProducto ' . $buscador . ': ' . $e->getMessage());
					$res = array();
					$fallos++;
				}
				if ($res) break;
			}
			if (!$res && $fallos === 2) throw new Exception('Los buscadores de imágenes no respondieron. Intenta de nuevo en un momento o sube la imagen desde el computador.', 502);
			if ($res) @file_put_contents($cache, json_encode($res), LOCK_EX);
			return array('imagenes' => $res);
		});
	}

	private function buscarWikimedia($q)
	{
		$url = 'https://commons.wikimedia.org/w/api.php?action=query&format=json&generator=search&gsrnamespace=6&gsrlimit=18'
			. '&gsrsearch=' . rawurlencode($q . ' filetype:bitmap')
			. '&prop=imageinfo&iiprop=url|mime|extmetadata&iiextmetadatafilter=Artist|LicenseShortName|ObjectName&iiurlwidth=800';
		$r = json_decode($this->descargar($url, 'application/json', 2097152), true);
		if (!is_array($r)) throw new Exception('respuesta no válida');
		$paginas = isset($r['query']['pages']) ? $r['query']['pages'] : array();
		uasort($paginas, function ($a, $b) { return (isset($a['index']) ? $a['index'] : 0) - (isset($b['index']) ? $b['index'] : 0); });
		$res = array();
		foreach ($paginas as $p) {
			$ii = isset($p['imageinfo'][0]) ? $p['imageinfo'][0] : null;
			if (!$ii || empty($ii['thumburl']) || !in_array(isset($ii['mime']) ? $ii['mime'] : '', array('image/jpeg', 'image/png', 'image/webp'), true)) continue;
			$meta = isset($ii['extmetadata']) ? $ii['extmetadata'] : array();
			$texto = function ($k) use ($meta) { return isset($meta[$k]['value']) ? trim(strip_tags((string)$meta[$k]['value'])) : ''; };
			$res[] = array(
				'url' => (string)$ii['thumburl'],   // 800 px: suficiente (se guarda a 600) y no pesa como el original
				'miniatura' => preg_replace('#/800px-#', '/320px-', (string)$ii['thumburl']),
				'titulo' => $texto('ObjectName') ?: preg_replace('/^File:|\.\w+$/', '', (string)$p['title']),
				'autor' => mb_substr($texto('Artist'), 0, 80),
				'licencia' => $texto('LicenseShortName'),
			);
		}
		return $res;
	}

	private function buscarOpenverse($q)
	{
		$url = 'https://api.openverse.org/v1/images/?page_size=18&mature=false&q=' . rawurlencode($q);
		$r = json_decode($this->descargar($url, 'application/json', 1048576), true);
		if (!is_array($r) || !isset($r['results'])) throw new Exception('respuesta no válida');
		$res = array();
		foreach ($r['results'] as $i) {
			if (empty($i['url'])) continue;
			$res[] = array(
				'url' => (string)$i['url'],
				'miniatura' => !empty($i['thumbnail']) ? (string)$i['thumbnail'] : (string)$i['url'],
				'titulo' => isset($i['title']) ? (string)$i['title'] : '',
				'autor' => isset($i['creator']) ? (string)$i['creator'] : '',
				'licencia' => isset($i['license']) ? strtoupper((string)$i['license']) . (isset($i['license_version']) ? ' ' . $i['license_version'] : '') : '',
			);
		}
		return $res;
	}

	/** Guarda como imagen del producto una de las imágenes encontradas (la descarga el servidor). */
	public function desdeUrl()
	{
		$this->responder(function () {
			$id = $this->idProducto($this->input->post('id_producto'));
			$url = trim((string)$this->input->post('url'));
			$datos = $this->descargar($url, 'image/', self::MAX_BYTES);
			return array('url' => $this->guardar($id, $datos));
		}, true);
	}

	public function eliminar()
	{
		$this->responder(function () {
			$id = $this->idProducto($this->input->post('id_producto'));
			$ruta = self::rutaImagen($this->carpetaEmpresa(), $id);
			if (is_file($ruta)) @unlink($ruta);
			return array('url' => null);
		}, true);
	}

	// ====================== utilidades (también las usa Mcaja para el catálogo) ======================

	const CARPETA = 'uploads/';

	/** Carpeta de la empresa: su RUT con guion y sin puntos (ej. 76193938-6). Solo números, K y guion. */
	public static function carpetaDe($rutEmpresa)
	{
		// El login guarda el RUT a veces con puntos y un 0 adelante (09.209.734-K) y a veces sin (9209734-K): misma carpeta
		$c = ltrim(preg_replace('/[^0-9k-]/', '', strtolower((string)$rutEmpresa)), '0');
		return preg_match('/^\d+-[0-9k]$/', $c) ? $c : 'general';
	}

	public static function rutaImagen($carpeta, $idProducto)
	{
		return FCPATH . self::CARPETA . $carpeta . '/productos/' . (int)$idProducto . '.webp';
	}

	/** URL pública con marca de tiempo (evita que el navegador muestre la imagen anterior) o null. */
	public static function urlImagen($carpeta, $idProducto)
	{
		$ruta = self::rutaImagen($carpeta, $idProducto);
		if (!is_file($ruta)) return null;
		return base_url() . self::CARPETA . $carpeta . '/productos/' . (int)$idProducto . '.webp?v=' . filemtime($ruta);
	}

	/** id_producto => url de todas las imágenes de la empresa (para el catálogo de la Caja). */
	public static function imagenesDe($carpeta)
	{
		$res = array();
		foreach ((array)glob(FCPATH . self::CARPETA . $carpeta . '/productos/*.webp') as $f) {
			$res[(int)basename($f, '.webp')] = base_url() . self::CARPETA . $carpeta . '/productos/' . basename($f) . '?v=' . filemtime($f);
		}
		return $res;
	}

	private function carpetaEmpresa()
	{
		return self::carpetaDe($this->session->userdata('rut_cliente'));
	}

	private function idProducto($valor)
	{
		$id = (int)$valor;
		if ($id <= 0) throw new Exception('Primero guarda el producto para poder agregarle una imagen', 400);
		return $id;
	}

	/** Valida que sea una imagen real, la redimensiona y la guarda como WEBP. */
	private function guardar($idProducto, $datos)
	{
		if (!function_exists('imagecreatefromstring')) throw new Exception('El servidor no tiene la extensión GD para procesar imágenes', 500);
		$info = @getimagesizefromstring($datos);
		if (!$info || !in_array($info[2], array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP), true)) {
			throw new Exception('El archivo no es una imagen JPG, PNG, GIF o WEBP', 400);
		}
		$img = @imagecreatefromstring($datos);
		if (!$img) throw new Exception('No se pudo leer la imagen', 400);
		$w = imagesx($img);
		$h = imagesy($img);
		$escala = min(1, self::MAX_LADO / max($w, $h));
		$nw = max(1, (int)round($w * $escala));
		$nh = max(1, (int)round($h * $escala));
		if (!function_exists('imagewebp')) throw new Exception('El servidor no puede guardar imágenes WEBP (falta soporte WebP en GD)', 500);
		$destino = imagecreatetruecolor($nw, $nh);
		// Fondo blanco para PNG/GIF con transparencia (en la Caja se ven sobre fondo claro)
		imagefill($destino, 0, 0, imagecolorallocate($destino, 255, 255, 255));
		imagecopyresampled($destino, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);

		$carpeta = $this->carpetaEmpresa();
		$ruta = self::rutaImagen($carpeta, $idProducto);
		$dir = dirname($ruta);
		if (!is_dir($dir) && !@mkdir($dir, 0775, true)) throw new Exception('No se pudo crear la carpeta de imágenes en el servidor', 500);
		// Se escribe a un temporal y se renombra: la Caja nunca ve una imagen a medio escribir
		$tmp = $ruta . '.' . bin2hex(random_bytes(4)) . '.tmp';
		if (!@imagewebp($destino, $tmp, 80) || !@rename($tmp, $ruta)) {
			@unlink($tmp);
			throw new Exception('No se pudo guardar la imagen en el servidor', 500);
		}
		imagedestroy($img);
		imagedestroy($destino);
		return self::urlImagen($carpeta, $idProducto);
	}

	/**
	 * Descarga por HTTPS con límites de tamaño y tiempo. Para evitar que se use para entrar a la red interna,
	 * solo se aceptan direcciones públicas (se rechazan localhost, IP privadas y reservadas). Las redirecciones
	 * se siguen a mano (máximo 3) validando cada destino.
	 */
	private function descargar($url, $tipoEsperado, $maxBytes)
	{
		if (!function_exists('curl_init')) throw new Exception('El servidor no tiene la extensión cURL', 500);
		for ($salto = 0; $salto <= 3; $salto++) {
			$p = parse_url($url);
			if (!$p || empty($p['host']) || !isset($p['scheme']) || strtolower($p['scheme']) !== 'https') {
				throw new Exception('Dirección de imagen no válida', 400);
			}
			$ips = @gethostbynamel($p['host']);
			if (!$ips) throw new Exception('No se pudo resolver ' . $p['host'], 502);
			foreach ($ips as $ip) {
				if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
					throw new Exception('Dirección de imagen no permitida', 400);
				}
			}
			$datos = '';
			$ubicacion = '';
			$ch = curl_init($url);
			curl_setopt_array($ch, array(
				CURLOPT_RETURNTRANSFER => false,
				CURLOPT_FOLLOWLOCATION => false,
				CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
				CURLOPT_CONNECTTIMEOUT => 8,
				CURLOPT_TIMEOUT => 20,
				CURLOPT_USERAGENT => 'RestoWeb/1.0 (imagenes de productos)',
				// Se conecta a la IP ya validada (evita que el nombre cambie de IP entre la validación y la descarga)
				CURLOPT_RESOLVE => array($p['host'] . ':' . (isset($p['port']) ? (int)$p['port'] : 443) . ':' . $ips[0]),
				CURLOPT_HEADERFUNCTION => function ($ch, $linea) use (&$ubicacion) {
					if (stripos($linea, 'location:') === 0) $ubicacion = trim(substr($linea, 9));
					return strlen($linea);
				},
				CURLOPT_WRITEFUNCTION => function ($ch, $trozo) use (&$datos, $maxBytes) {
					$datos .= $trozo;
					return strlen($datos) > $maxBytes ? 0 : strlen($trozo);   // corta si se pasa del máximo
				},
			));
			$ok = curl_exec($ch);
			$codigo = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$tipo = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
			curl_close($ch);
			if (strlen($datos) > $maxBytes) throw new Exception('La imagen es demasiado grande', 400);
			if ($codigo >= 300 && $codigo < 400 && $ubicacion !== '') {
				// Location relativa: se arma con el mismo host
				if (!preg_match('#^https?://#i', $ubicacion)) $ubicacion = 'https://' . $p['host'] . '/' . ltrim($ubicacion, '/');
				$url = $ubicacion;
				continue;
			}
			if ($ok === false || $codigo !== 200 || $datos === '') {
				throw new Exception('No se pudo descargar la imagen (código ' . $codigo . '), prueba con otra', 502);
			}
			if ($tipoEsperado === 'image/' && $tipo !== '' && stripos($tipo, 'image/') !== 0) throw new Exception('Esa dirección no es una imagen', 400);
			return $datos;
		}
		throw new Exception('Demasiadas redirecciones, prueba con otra imagen', 502);
	}

	private function responder($accion, $soloPost = false)
	{
		$this->output->set_content_type('application/json', 'utf-8');
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			return $this->output->set_status_header(401)->set_output(json_encode(array('error' => 'Sesión expirada, vuelve a ingresar')));
		}
		if ($soloPost && $this->input->method() !== 'post') {
			return $this->output->set_status_header(405)->set_output(json_encode(array('error' => 'Método no permitido')));
		}
		try {
			$this->output->set_output(json_encode($accion()));
		} catch (Exception $e) {
			$codigo = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
			$this->output->set_status_header($codigo)->set_output(json_encode(array('error' => $e->getMessage())));
		}
	}
}
