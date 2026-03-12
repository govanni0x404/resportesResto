<?php
require_once "/home/govanni/Documentos/RestoWeb/reportesResto/application/config/database.php";
$conn = pg_connect("host=" . $db["default"]["hostname"] . " dbname=" . $db["default"]["database"] . " user=" . $db["default"]["username"] . " password=" . $db["default"]["password"]);
$res = pg_query($conn, "SELECT column_name FROM information_schema.columns WHERE table_name = 'cliente' OR table_name = 'software'");
while ($row = pg_fetch_assoc($res)) {
    echo $row["column_name"] . "\n";
}
