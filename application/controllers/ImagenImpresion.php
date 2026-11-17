<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 17-11-2026: entrega al agente de impresión (impresionWeb.exe) la imagen de un documento que subió la Caja
 * (libraries/Imagen_impresion.php). Es pública porque el agente no tiene sesión (hooks/Permisos.php, $publicos): solo
 * responde a una clave aleatoria de 128 bits que vence a los 10 minutos.
 */
class ImagenImpresion extends CI_Controller
{
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
