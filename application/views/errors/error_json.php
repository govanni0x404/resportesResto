<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * 16-11-2026 (QA): errores para las pantallas que llaman por AJAX.
 * Antes un error de PHP o de la base llegaba como página HTML: la pantalla (que espera JSON) mostraba un mensaje
 * confuso o se quedaba sin responder. Si la petición es AJAX se responde {"error": "..."} con el mismo código HTTP.
 * El detalle completo queda siempre en application/logs; en producción no se muestra (podría traer SQL o datos).
 * Lo usan las vistas de views/errors/html/ (error_db, error_exception, error_general, error_php).
 */
if (!function_exists('resto_es_ajax')) {
	function resto_es_ajax()
	{
		if (is_cli()) return false;
		$xrw = isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) : '';
		$accept = isset($_SERVER['HTTP_ACCEPT']) ? strtolower($_SERVER['HTTP_ACCEPT']) : '';
		return $xrw === 'xmlhttprequest' || (strpos($accept, 'application/json') !== false && strpos($accept, 'text/html') === false);
	}
}

if (!function_exists('resto_error_json')) {
	/**
	 * Si la petición es AJAX, escribe el error como JSON y devuelve true (la vista no debe dibujar el HTML).
	 * $tipo: 'db' | 'php' | 'general'. $detalle: texto del error (solo se muestra en desarrollo, una línea).
	 */
	function resto_error_json($tipo, $detalle = '')
	{
		if (!resto_es_ajax()) return false;
		$mensajes = array(
			'db' => 'No se pudo completar la operación: error en la base de datos.',
			'php' => 'No se pudo completar la operación: error interno del servidor.',
			'general' => 'No se pudo completar la operación.',
		);
		$msg = isset($mensajes[$tipo]) ? $mensajes[$tipo] : $mensajes['general'];
		if (defined('ENVIRONMENT') && ENVIRONMENT === 'development' && $detalle !== '') {
			$linea = trim(strtok(strip_tags(html_entity_decode((string)$detalle, ENT_QUOTES, 'UTF-8')), "\n"));
			if ($linea !== '') $msg .= ' (' . mb_substr($linea, 0, 200) . ')';
		}
		$msg .= ' Si se repite, avisa a soporte.';
		if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array('error' => $msg, 'ok' => false), JSON_UNESCAPED_UNICODE);
		return true;
	}
}
