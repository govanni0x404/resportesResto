<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Helper de conexión dinámica multi-tenant
 *
 * Lee los datos de BD guardados en sesión durante el login
 * y crea la conexión dinámica sin necesidad de tocar database.php
 *
 * Uso en cualquier modelo:
 *   $this->load->helper('tenant');
 *   $this->db = get_tenant_db($this);
 */
function get_tenant_db(&$CI)
{
    $nombre_conexion = $CI->session->userdata('nombre_conexion');

    if (empty($nombre_conexion)) {
        log_message('error', 'get_tenant_db: no hay nombre_conexion en sesión');
        $CI->session->sess_destroy();
        redirect(base_url());
        exit;
    }

    // Key única para cachear la conexión y no reconectar en cada llamada
    $conn_key = 'tenant_' . md5($nombre_conexion);

    $CI_instance = &get_instance();
    if (isset($CI_instance->$conn_key)) {
        return $CI_instance->$conn_key;
    }

    // Cargamos la configuración de database de CodeIgniter para extraer las credenciales globales
    include(APPPATH . 'config/database.php');

    if (!isset($db[$nombre_conexion])) {
        show_error('No se encontró el grupo de conexión '.$nombre_conexion.' en database.php. Faltan registrar las credenciales de este cliente en el archivo.');
    }

    $config = $db[$nombre_conexion];
    
    // Forzamos return de un array para load->database
    $db_conn = $CI->load->database($config, TRUE);
    
    $CI_instance->$conn_key = $db_conn;

    return $db_conn;
}