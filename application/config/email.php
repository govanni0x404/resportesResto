<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Correo de salida del sistema (11-11-2026)
| -------------------------------------------------------------------------
| Lo usa "¿Olvidaste tu contraseña?" del login (libraries/CodigoCorreo.php):
| el código de verificación y el aviso de contraseña cambiada.
| Los cierres de caja por correo NO usan este archivo: van con el correo que
| cada empresa configura en su base (libraries/Correo_resto.php).
|
| Las claves NO van en el código: se leen del entorno (SetEnv de Apache,
| fastcgi_param de Nginx o el .env.php de la raíz). Ver .env.example.
|   MIPDV_SMTP_HOST    ej. mail.datamaule.cl (sin "ssl://")
|   MIPDV_SMTP_PORT    465 (ssl) o 587 (tls)
|   MIPDV_SMTP_USER    la cuenta que envía (también es el remitente)
|   MIPDV_SMTP_PASS
|   MIPDV_SMTP_CRYPTO  ssl o tls
| Sin MIPDV_SMTP_HOST / _USER la recuperación de contraseña avisa que no
| está disponible y no intenta enviar.
*/
$config['protocol']     = 'smtp';
$config['smtp_host']    = function_exists('_resto_env_raw') ? (string)_resto_env_raw('MIPDV_SMTP_HOST', '') : '';
$config['smtp_port']    = function_exists('_resto_env_raw') ? (int)_resto_env_raw('MIPDV_SMTP_PORT', 465) : 465;
$config['smtp_user']    = function_exists('_resto_env_raw') ? (string)_resto_env_raw('MIPDV_SMTP_USER', '') : '';
$config['smtp_pass']    = function_exists('_resto_env_raw') ? (string)_resto_env_raw('MIPDV_SMTP_PASS', '') : '';
$config['smtp_crypto']  = function_exists('_resto_env_raw') ? (string)_resto_env_raw('MIPDV_SMTP_CRYPTO', 'ssl') : 'ssl';
$config['smtp_timeout'] = 10;
$config['mailtype']     = 'html';
$config['charset']      = 'utf-8';
$config['newline']      = "\r\n";
