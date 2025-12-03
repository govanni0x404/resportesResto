<?php

function get_git_version()
{
    // Moverse a la raíz del proyecto
    chdir(__DIR__ . '/../../'); 

    // Ejecutar git desde la raíz
    $version = @exec('git describe --tags --abbrev=0 2>&1');

    // Si no hay versión, fallback
    return $version ?: '0.0.0';
}
