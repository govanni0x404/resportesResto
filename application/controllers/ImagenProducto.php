<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Imágenes de productos de la Carta (se ven en la Caja). No usa columnas nuevas: cada imagen se guarda como
 * archivo assets/uploads/productos/<cliente>/<id_producto>.jpg, redimensionada a un máximo de 600 px.
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
			return array('url' => self::urlImagen($this->carpetaCliente(), $id));
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

	/** Busca imágenes de uso libre por nombre (Openverse: Flickr, Wikimedia y otros, sin clave de API). */
	public function buscar()
	{
		$this->responder(function () {
			$q = trim((string)$this->input->get('q'));
			if (mb_strlen($q) < 2) throw new Exception('Escribe al menos 2 letras para buscar', 400);
			$url = 'https://api.openverse.org/v1/images/?page_size=18&mature=false&q=' . rawurlencode(mb_substr($q, 0, 80));
			$json = $this->descargar($url, 'application/json', 1048576);
			$r = json_decode($json, true);
			if (!is_array($r) || !isset($r['results'])) throw new Exception('El buscador de imágenes no respondió', 502);
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
			return array('imagenes' => $res);
		});
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
			$ruta = self::rutaImagen($this->carpetaCliente(), $id);
			if (is_file($ruta)) @unlink($ruta);
			return array('url' => null);
		}, true);
	}

	// ====================== utilidades (también las usa Mcaja para el catálogo) ======================

	/** Carpeta del cliente actual, a partir de la conexión de la sesión (solo letras, números, - y _). */
	public static function carpetaDe($nombreConexion)
	{
		$c = preg_replace('/[^a-z0-9_-]/', '', strtolower((string)$nombreConexion));
		return $c !== '' ? $c : 'general';
	}

	public static function rutaImagen($carpeta, $idProducto)
	{
		return FCPATH . 'assets/uploads/productos/' . $carpeta . '/' . (int)$idProducto . '.jpg';
	}

	/** URL pública con marca de tiempo (evita que el navegador muestre la imagen anterior) o null. */
	public static function urlImagen($carpeta, $idProducto)
	{
		$ruta = self::rutaImagen($carpeta, $idProducto);
		if (!is_file($ruta)) return null;
		return base_url() . 'assets/uploads/productos/' . $carpeta . '/' . (int)$idProducto . '.jpg?v=' . filemtime($ruta);
	}

	private function carpetaCliente()
	{
		return self::carpetaDe($this->session->userdata('nombre_conexion'));
	}

	private function idProducto($valor)
	{
		$id = (int)$valor;
		if ($id <= 0) throw new Exception('Primero guarda el producto para poder agregarle una imagen', 400);
		return $id;
	}

	/** Valida que sea una imagen real, la redimensiona y la guarda como JPEG. */
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
		$destino = imagecreatetruecolor($nw, $nh);
		// Fondo blanco para PNG/GIF con transparencia (el JPEG no la soporta)
		imagefill($destino, 0, 0, imagecolorallocate($destino, 255, 255, 255));
		imagecopyresampled($destino, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);

		$carpeta = $this->carpetaCliente();
		$dir = dirname(self::rutaImagen($carpeta, $idProducto));
		if (!is_dir($dir) && !@mkdir($dir, 0775, true)) throw new Exception('No se pudo crear la carpeta de imágenes en el servidor', 500);
		if (!@imagejpeg($destino, self::rutaImagen($carpeta, $idProducto), 85)) throw new Exception('No se pudo guardar la imagen en el servidor', 500);
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
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
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
