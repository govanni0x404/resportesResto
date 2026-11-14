<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Helper de conexión dinámica multi-tenant
 *
 * 10-11-2026: ya no lee database.php. La conexión sale de la sesión
 * (db_server + bd_name, que el login obtiene de la intranet por API) y se abre
 * con DbManager, el diccionario de servidores copiado de mipdv_web.
 *
 * Uso en cualquier modelo (sin cambios):
 *   $this->load->helper('tenant');
 *   $this->db = get_tenant_db($this);
 */
function get_tenant_db(&$CI)
{
    $dbServer = trim((string) $CI->session->userdata('db_server'));
    $bdName   = trim((string) $CI->session->userdata('bd_name'));
    $nombre_conexion = $CI->session->userdata('nombre_conexion');

    if (empty($nombre_conexion)) {
        log_message('error', 'get_tenant_db: no hay nombre_conexion en sesión');
        $CI->session->sess_destroy();
        redirect(base_url());
        exit;
    }

    $CI->load->library('ConexionesTenant');
    // La librería queda en el controlador: desde una vista $CI es el CI_Loader y no la tiene (13-11-2026)
    $tenant = get_instance()->conexionestenant;

    // Sesión iniciada antes del cambio (sin db_server/bd_name): se busca en el diccionario local
    if ($dbServer === '' || $bdName === '') {
        $par = $tenant->buscar($nombre_conexion);
        if ($par === null) {
            log_message('error', 'get_tenant_db: la sesión no tiene db_server/bd_name y "' . $nombre_conexion . '" no está en el diccionario; se pide iniciar sesión de nuevo');
            $CI->session->sess_destroy();
            redirect(base_url());
            exit;
        }
        $dbServer = $par['srv'];
        $bdName = $par['bd'];
        $CI->session->set_userdata(array('db_server' => $dbServer, 'bd_name' => $bdName));
    }

    // Key única para cachear la conexión y no reconectar en cada llamada
    $conn_key = 'tenant_' . md5($dbServer . '|' . $bdName);

    $CI_instance = &get_instance();
    if (isset($CI_instance->$conn_key)) {
        return $CI_instance->$conn_key;
    }

    if (!$tenant->esValida($dbServer, $bdName)) {
        show_error('El servidor "' . $dbServer . '" de la empresa no está en el diccionario de conexiones. Revisa las variables MIPDV_DB_' . strtoupper(preg_replace('/[^A-Za-z0-9]+/', '_', $dbServer)) . '_* del entorno (.env).');
    }

    // db_debug TRUE como tenían los grupos de database.php: un error de SQL muestra la página de error
    $db_conn = $tenant->conectar($dbServer, $bdName, array('db_debug' => TRUE));

    $CI_instance->$conn_key = $db_conn;

    return $db_conn;
}
