<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Regla unica de contrasena segura (copia de mipdv_web, 21-10-2026).
 * La misma que valida el navegador (login: Recuperar contraseña) y la API
 * de la intranet (AppWebLogin::claveSeguraError).
 *
 * @return string|null  mensaje del primer requisito que falta, o null si es valida
 */
if (!function_exists('clave_segura_error')) {
  function clave_segura_error($password)
  {
    $password = (string)$password;
    if (strlen($password) < 8) return 'Debe tener al menos 8 caracteres';
    if (strlen($password) > 72) return 'No debe superar los 72 caracteres';
    if (!preg_match('/[a-z]/', $password)) return 'Debe contener al menos una letra minúscula';
    if (!preg_match('/[A-Z]/', $password)) return 'Debe contener al menos una letra mayúscula';
    if (!preg_match('/\d/', $password)) return 'Debe contener al menos un número';
    if (!preg_match('/[^A-Za-z0-9]/', $password)) return 'Debe contener al menos un carácter especial (ej: @, #, !, $)';
    if (preg_match('/20\d{2}/', $password)) return 'No debe contener años (ej: 2024, 2025, 2026)';
    return null;
  }
}
