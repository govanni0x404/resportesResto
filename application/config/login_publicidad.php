<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| PUBLICIDAD DEL LOGIN (11-11-2026) — igual idea que mipdv_web
| -------------------------------------------------------------------------
| Anuncios que rotan a la izquierda del login, sobre la foto de fondo
| (application/views/login/login.php). Para agregar, quitar o cambiar
| anuncios basta con editar esta lista; el orden de la lista es el orden en
| que se muestran.
|
| Campos de cada anuncio:
|   etiqueta          Texto corto sobre el título (opcional). Ej. "Novedad".
|   titulo            Primera línea del título (obligatorio).
|   titulo_destacado  Segunda línea, en verde (opcional).
|   texto             Bajada bajo el título (opcional).
|   boton_texto       Texto del botón (opcional). Si falta, no hay botón.
|   boton_url         Enlace del botón: https://..., mailto: o tel:
|                     Se abre en una pestaña nueva.
|
| Si la lista queda vacía, el login muestra el anuncio de respaldo de la vista.
*/

// Segundos que se muestra cada anuncio (mínimo 4).
$config['intervalo_segundos'] = 7;

$config['anuncios'] = array(
	array(
		'etiqueta'         => 'dat@Resto',
		'titulo'           => 'Tu restaurante,',
		'titulo_destacado' => 'en orden y a tiempo.',
		'texto'            => 'Mesas, comandas a cocina, caja y reportes en una sola plataforma web.',
		'boton_texto'      => '',
		'boton_url'        => '',
	),
	array(
		'etiqueta'         => 'Mesas y garzones',
		'titulo'           => 'Cada pedido',
		'titulo_destacado' => 'llega a la cocina.',
		'texto'            => 'Plano de mesas por sector, comandas a cocina, precuenta y cambio de mesa o de garzón.',
		'boton_texto'      => '',
		'boton_url'        => '',
	),
	array(
		'etiqueta'         => 'Dividir cuenta',
		'titulo'           => 'Cada comensal',
		'titulo_destacado' => 'paga lo suyo.',
		'texto'            => 'Divide por comensal, en partes iguales o libre, con su propia precuenta y documento.',
		'boton_texto'      => '',
		'boton_url'        => '',
	),
	array(
		'etiqueta'         => 'Caja',
		'titulo'           => 'Cierres de caja',
		'titulo_destacado' => 'claros y auditados.',
		'texto'            => 'Cierre ciego, ventas en espera y auditoría completa de cada turno.',
		'boton_texto'      => '',
		'boton_url'        => '',
	),
	array(
		'etiqueta'         => 'Boleta electrónica',
		'titulo'           => 'Boletea y factura',
		'titulo_destacado' => 'directo desde la caja.',
		'texto'            => 'Documentos electrónicos al día con el SII, con el respaldo de Datamaule.',
		'boton_texto'      => 'Cotizar por WhatsApp',
		'boton_url'        => 'https://wa.me/56942910206',
	),
	array(
		'etiqueta'         => 'Reportes',
		'titulo'           => 'Decide con datos',
		'titulo_destacado' => 'en tiempo real.',
		'texto'            => 'Ventas del día, productos más vendidos, garzones y formas de pago, desde cualquier lugar.',
		'boton_texto'      => 'Hablar con un ejecutivo',
		'boton_url'        => 'https://wa.me/56940676939',
	),
);
