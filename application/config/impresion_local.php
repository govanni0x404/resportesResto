<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
| Impresión directa en el equipo del cliente (12-11-2026), como mipdv_web: el navegador llama al agente de impresión
| local (http://localhost:5050) con el trabajo armado (assets/js/impresion-local/printerJob.js).
|
| La clave del agente NO va en el código: se lee del entorno (.env / SetEnv), igual que las demás claves.
|   RESTO_IMPRESION_LOCAL_URL   dirección del agente en cada equipo (por defecto http://localhost:5050)
|   RESTO_IMPRESION_LOCAL_CLAVE "usuario:clave" del agente (Basic). Vacía = se llama sin clave.
| La clave llega al navegador (es quien llama al agente), igual que en mipdv_web.
*/
$config['impresion_local_url'] = rtrim((string)_resto_env_raw('RESTO_IMPRESION_LOCAL_URL', 'http://localhost:5050'), '/');
$config['impresion_local_clave'] = (string)_resto_env_raw('RESTO_IMPRESION_LOCAL_CLAVE', '');
