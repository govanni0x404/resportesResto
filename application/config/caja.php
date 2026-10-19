<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
| -------------------------------------------------------------------
| Caja web: emisión electrónica (Cloud Apps)
| -------------------------------------------------------------------
| Usuario (RUT de la empresa), clave y terminal de Cloud Apps se configuran
| en Configuraciones > Facturación (configuracion.usuario_dte, clave_usuario_dte
| y terminal_dte). Sin terminal, la caja web no ofrece boletas ni facturas.
|
| Opcional: terminal por grupo de conexión (database.php), si no está en la base:
|	$config['caja_dte_terminales'] = array('76193938-6' => 'XXXXXXXX-XXXX-XXXX');
*/
$config['caja_dte_terminales'] = array();

$config['caja_dte_url'] = 'https://api.cloud-apps.cl/newDte/';

/*
| Modo pruebas (solo en desarrollo): emite con otra empresa de Cloud Apps
| (p. ej. la cuenta demo) en vez de las credenciales del cliente.
| Se define en config/caja_local.php, que no se sube al repositorio:
|
|	$config['caja_dte_pruebas'] = array(
|		'usuario' => '45000020-5', 'clave' => '...', 'terminal' => '...',
|		'rut_emisor' => '45000020-5', 'razon_social' => 'DEMO RESTAURANTE SPA',
|	);
*/
$config['caja_dte_pruebas'] = null;

if (is_file(__DIR__ . '/caja_local.php')) {
	include __DIR__ . '/caja_local.php';
}
