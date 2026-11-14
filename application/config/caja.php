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

// Empresa proveedora DTE = datamaule.cl (Configuraciones > Facturación electrónica): API de integración
// SimpleFactura. Se usan /newDte/ para emitir y /cafInfo/ para consultar folios. La credencial es la
// API Key de la empresa (tipo de autenticación en Configuraciones; ver helpers/dte_auth_helper.php).
$config['caja_dte_url_datamaule'] = 'https://facturacion.datamaule.cl/api/v1/integracion';

// Pie de boletas y facturas electrónicas: las boletas se verifican en el sitio del emisor
// (las facturas y notas de crédito, en www.sii.cl)
$config['caja_url_verificacion_boleta'] = 'http://dte.datamaule.cl';
$config['caja_ticket_publicidad'] = array('CONTRATE BOLETA ELECTRONICA CON NOSOTROS', 'datamaule@gmail.com');

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

// Solo fuera de producción: si caja_local.php llegara al servidor, no debe hacer que todos emitan con la cuenta demo.
// En el equipo de desarrollo: CI_ENV=development php -S 0.0.0.0:8001
if (is_file(__DIR__ . '/caja_local.php') && defined('ENVIRONMENT') && ENVIRONMENT !== 'production') {
	include __DIR__ . '/caja_local.php';
}
