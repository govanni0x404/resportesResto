<?php
require_once "/home/govanni/Documentos/RestoWeb/reportesResto/application/config/database.php";
$config = $db["default"];
$config["database"] = "ccerda_pruebasrestoweb";

$conn_string = "host=" . $config["hostname"] . " dbname=" . $config["database"] . " user=" . $config["username"] . " password=" . $config["password"];
$conn = pg_connect($conn_string);
if (!$conn) {
    echo "Falla BD 1: " . pg_last_error() . "\n";
} else {
    echo "Exito BD 1!\n";
}

$config["database"] = "ccerda_escuela_artilleria";
$conn_string = "host=" . $config["hostname"] . " dbname=" . $config["database"] . " user=" . $config["username"] . " password=" . $config["password"];
$conn = pg_connect($conn_string);
if (!$conn) {
    echo "Falla BD 2: " . pg_last_error() . "\n";
} else {
    echo "Exito BD 2!\n";
}
