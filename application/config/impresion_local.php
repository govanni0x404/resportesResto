<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
| Impresión directa en el equipo del cliente (12-11-2026), como mipdv_web: el navegador llama al agente de impresión
| local (http://localhost:5050) con el trabajo armado (assets/js/impresion-local/printerJob.js).
|
| v1.24.6 (pedido del usuario): ya no hace falta el .env para imprimir. Si no está definida, se usa la clave con que
| viene el agente (impresionWeb.exe). Esta clave NO es secreta: la Caja se la entrega al navegador de cada local para que
| este llame al agente de su computador (igual que en mipdv_web).
|
| Si algún día el agente se instala con otra clave, se puede fijar sin tocar el código (gana sobre lo de abajo):
|   RESTO_IMPRESION_LOCAL_URL   dirección del agente en cada equipo (por defecto http://localhost:5050)
|   RESTO_IMPRESION_LOCAL_CLAVE "usuario:clave" del agente (Basic)
| en el .env.php o en la configuración del servidor (SetEnv de Apache / fastcgi_param de Nginx).
*/
defined('RESTO_IMPRESION_LOCAL_URL_DEFECTO') OR define('RESTO_IMPRESION_LOCAL_URL_DEFECTO', 'http://localhost:5050');
defined('RESTO_IMPRESION_LOCAL_CLAVE_DEFECTO') OR define('RESTO_IMPRESION_LOCAL_CLAVE_DEFECTO', 'admin:12345');   // la del agente impresionWeb.exe

$config['impresion_local_url'] = rtrim((string)(_resto_env_raw('RESTO_IMPRESION_LOCAL_URL', '') ?: RESTO_IMPRESION_LOCAL_URL_DEFECTO), '/');
$config['impresion_local_clave'] = (string)(_resto_env_raw('RESTO_IMPRESION_LOCAL_CLAVE', '') ?: RESTO_IMPRESION_LOCAL_CLAVE_DEFECTO);
