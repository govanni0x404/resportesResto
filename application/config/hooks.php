<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Hooks
| -------------------------------------------------------------------------
| This file lets you define "hooks" to extend CI without hacking the core
| files.  Please see the user guide for info:
|
|	https://codeigniter.com/userguide3/general/hooks.html
|
*/

// Control de acceso por módulo en el servidor (antes los privilegios solo ocultaban el menú)
$hook['post_controller_constructor'][] = array(
	'class'    => 'Permisos',
	'function' => 'verificar',
	'filename' => 'Permisos.php',
	'filepath' => 'hooks',
);
// Lo impreso con echo json_encode(...) sale como application/json
$hook['post_controller'][] = array(
	'class'    => 'Permisos',
	'function' => 'cerrar',
	'filename' => 'Permisos.php',
	'filepath' => 'hooks',
);
