<?php
define('BASEPATH', __DIR__ . '/system/');
define('APPPATH', __DIR__ . '/application/');
define('ENVIRONMENT', 'development');
require_once APPPATH . 'config/database.php';
$config = $db["default"];

// probando default
$conn_string = "host=" . $config["hostname"] . " dbname=" . $config["database"] . " user=" . $config["username"] . " password=" . $config["password"];
$conn = pg_connect($conn_string);
if (!$conn) {
    echo "Falla BD default: " . pg_last_error() . "\n";
} else {
    echo "Exito BD default: " . $config["database"] . "\n";
}

// probando la que asumimos que es nombre_conexion de la bd
$config["database"] = "ccerda_pruebasrestoweb";
$conn_string = "host=" . $config["hostname"] . " dbname=" . $config["database"] . " user=" . $config["username"] . " password=" . $config["password"];
$conn = pg_connect($conn_string);
if (!$conn) {
    echo "Falla BD pruebasrestoweb: " . pg_last_error() . "\n";
} else {
    echo "Exito BD pruebasrestoweb!\n";
}
