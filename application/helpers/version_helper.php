<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Helper de Versionamiento Automático
 * 
 * Funciones para mostrar la versión de la aplicación
 */

if (!function_exists('app_version')) {
    /**
     * Obtener versión de la aplicación
     * 
     * @param string $part Parte a obtener: 'number', 'commit', 'build', 'date', 'full', 'all'
     * @return mixed
     */
    function app_version($part = 'full') {
        $CI =& get_instance();
        
        // Ruta del archivo de versión
        $version_file = APPPATH . 'config/version.php';
        
        // Si el archivo existe, cargarlo
        if (file_exists($version_file)) {
            include($version_file);
            
            if (isset($config['version'])) {
                $version = $config['version'];
                
                switch ($part) {
                    case 'number':
                        return $version['number'];
                    case 'commit':
                        return $version['commit'];
                    case 'branch':
                        return $version['branch'];
                    case 'build':
                        return $version['build'];
                    case 'date':
                        return $version['date'];
                    case 'full':
                        return $version['full'];
                    case 'all':
                        return $version;
                    default:
                        return $version['full'];
                }
            }
        }
        
        // Si no existe el archivo, retornar versión por defecto
        return 'dev-unknown';
    }
}

if (!function_exists('app_version_short')) {
    /**
     * Versión corta (solo número)
     * 
     * @return string
     */
    function app_version_short() {
        return app_version('number');
    }
}

if (!function_exists('app_version_full')) {
    /**
     * Versión completa con commit
     * 
     * @return string
     */
    function app_version_full() {
        return app_version('full');
    }
}

if (!function_exists('app_build_number')) {
    /**
     * Número de build (total de commits)
     * 
     * @return int
     */
    function app_build_number() {
        return app_version('build');
    }
}

if (!function_exists('app_version_badge')) {
    /**
     * Badge HTML con información de versión
     * 
     * @param bool $show_details Mostrar detalles adicionales
     * @return string HTML
     */
    function app_version_badge($show_details = null) {
        // Si no se especifica, usar el ambiente
        if ($show_details === null) {
            $show_details = (ENVIRONMENT === 'development');
        }
        
        $version = app_version('all');
        
        $html = '<div class="app-version-badge">';
        $html .= '<span class="version-number">v' . $version['number'] . '</span>';
        
        if ($show_details) {
            $html .= ' <span class="version-details" title="Commit: ' . $version['commit'] . ' | Branch: ' . $version['branch'] . '">';
            $html .= '(Build #' . $version['build'] . ' - ' . $version['date'] . ')';
            $html .= '</span>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
}

if (!function_exists('app_version_json')) {
    /**
     * Versión en formato JSON (útil para APIs)
     * 
     * @return string JSON
     */
    function app_version_json() {
        $version = app_version('all');
        return json_encode($version, JSON_PRETTY_PRINT);
    }
}