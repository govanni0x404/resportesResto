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
// v1.25.3: 127.0.0.1 y no "localhost". En Windows "localhost" a veces intenta primero por IPv6 (::1), donde el agente no
// escucha, y espera 5 segundos antes de usar 127.0.0.1: algunas impresiones tardaban ~7 s en vez de ~2 s (medido el
// 21-11-2026: con 127.0.0.1 el agente recibe el trabajo en menos de 0,1 s, siempre).
defined('RESTO_IMPRESION_LOCAL_URL_DEFECTO') OR define('RESTO_IMPRESION_LOCAL_URL_DEFECTO', 'http://127.0.0.1:5050');
defined('RESTO_IMPRESION_LOCAL_CLAVE_DEFECTO') OR define('RESTO_IMPRESION_LOCAL_CLAVE_DEFECTO', 'admin:12345');   // la del agente impresionWeb.exe

$config['impresion_local_url'] = rtrim((string)(_resto_env_raw('RESTO_IMPRESION_LOCAL_URL', '') ?: RESTO_IMPRESION_LOCAL_URL_DEFECTO), '/');
// Aunque la variable diga "localhost", se usa 127.0.0.1 (ver arriba)
$config['impresion_local_url'] = preg_replace('#^(https?://)localhost(?=[:/]|$)#i', '${1}127.0.0.1', $config['impresion_local_url']);
$config['impresion_local_clave'] = (string)(_resto_env_raw('RESTO_IMPRESION_LOCAL_CLAVE', '') ?: RESTO_IMPRESION_LOCAL_CLAVE_DEFECTO);
