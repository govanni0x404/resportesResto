<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 17-11-2026: entrega al agente de impresión (impresionWeb.exe) la imagen de un documento que subió la Caja
 * (libraries/Imagen_impresion.php). Es pública porque el agente no tiene sesión (hooks/Permisos.php, $publicos): solo
 * responde a una clave aleatoria de 128 bits que vence a los 10 minutos.
 */
class ImagenImpresion extends CI_Controller
{
	/**
	 * 18-11-2026: POST ImagenImpresion/subir con la cabecera X-Firma-Impresion (la firma que la Caja recibe al cargar,
	 * libraries/Imagen_impresion::firma) y el PNG en "imagen". La pantalla la llama SIN cookies, así no espera el
	 * candado de la sesión. Responde {url} como Caja/subirImagenImpresion; si la firma no vale, 403 y la pantalla sube
	 * por ese otro camino (con la sesión).
	 */
	public function subir()
	{
		$this->load->library('Imagen_impresion');
		$codigo = 200;
		try {
			if ($this->input->method() !== 'post') throw new Exception('Método no permitido', 405);
			if (!$this->imagen_impresion->firmaValida((string)$this->input->get_request_header('X-Firma-Impresion'))) {
				throw new Exception('Firma de impresión inválida o vencida', 403);
			}
			$clave = $this->imagen_impresion->guardar(isset($_FILES['imagen']) ? $_FILES['imagen'] : null);
			$res = array('url' => $this->imagen_impresion->rutaPublica($clave));
		} catch (Exception $e) {
			$codigo = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
			$res = array('error' => $e->getMessage());
		}
		// Sin cookie, CodeIgniter abrió una sesión nueva y vacía: se borra para no dejar archivos sueltos
		if (session_status() === PHP_SESSION_ACTIVE) $this->session->sess_destroy();
		$this->output->set_status_header($codigo)->set_content_type('application/json', 'utf-8')->set_output(json_encode($res));
	}

	/** GET ImagenImpresion/ver/<clave>.png */
	public function ver($archivo = '')
	{
		$this->load->library('Imagen_impresion');
		$ruta = $this->imagen_impresion->ruta(preg_replace('/\.png$/i', '', (string)$archivo));
		if (!$ruta) {
			$this->output->set_status_header(404)->set_content_type('text/plain', 'utf-8')->set_output('No existe o venció');
			return;
		}
		// Cabeceras a mano: set_content_type de CodeIgniter agrega "; charset=UTF-8" y es una imagen (igual que un .png
		// servido por Apache, que es lo que se probó con el agente)
		$this->output
			->set_header('Content-Type: image/png')
			->set_header('Content-Length: ' . filesize($ruta))
			->set_header('Cache-Control: no-store')
			->set_header('X-Content-Type-Options: nosniff')
			->set_output(file_get_contents($ruta));
	}
}
