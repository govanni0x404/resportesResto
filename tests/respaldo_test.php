<?php
/**
 * Prueba de los respaldos de la base y de las fotos de productos (v1.27.0).
 *
 *   1) prende la web en modo local:   C:\xampp\php\php.exe -S localhost:8001 router.php
 *   2) en otra consola:               C:\xampp\php\php.exe tests\respaldo_test.php
 *
 * - Cuáles respaldos se guardan y cuáles se borran (7 días, 4 semanas, 3 meses), con archivos de mentira.
 * - Configuraciones > Respaldos: "Respaldar ahora" hace un respaldo de verdad (pg_dump), aparece en la lista y se descarga.
 * - Un nombre con ../ no descarga nada, y la carpeta backups/ no se ve por la web.
 * - Las fotos de productos están en uploads/<RUT>/productos/<id>.webp y son WEBP de verdad.
 */
require __DIR__ . '/lib_pruebas.php';
$raiz = dirname(__DIR__);
if (!defined('BASEPATH')) define('BASEPATH', $raiz . '/system/');
if (!defined('FCPATH')) define('FCPATH', $raiz . '/');
require_once $raiz . '/application/libraries/Respaldo_bd.php';

echo "Qué respaldos se guardan (archivos de mentira):\n";
$dir = sys_get_temp_dir() . '/prueba_respaldos_' . bin2hex(random_bytes(3));
mkdir($dir);
$hoy = strtotime('today 04:00');
// Uno por día durante 120 días, y 3 extra hoy (Respaldar ahora varias veces)
for ($d = 0; $d < 120; $d++) touch($dir . "/base_dia$d.dump", $hoy - $d * 86400);
foreach (array(1, 2, 3) as $h) touch($dir . "/base_hoy$h.dump", $hoy + $h * 3600);
$r = new Respaldo_bd();
$borrados = $r->limpiar($dir, $dir . '/base_hoy3.dump');
$quedan = array_map('basename', glob($dir . '/*.dump'));
revisar('quedan como máximo 14 respaldos (7 + 4 + 3)', count($quedan) <= 14, count($quedan) . ' quedaron');
revisar('el más nuevo (recién hecho) se guarda', in_array('base_hoy3.dump', $quedan, true));
revisar('los otros de hoy se borran (queda uno por día)', !in_array('base_hoy1.dump', $quedan, true) && !in_array('base_hoy2.dump', $quedan, true));
revisar('se guardan los 6 días anteriores', count(array_intersect(array('base_dia1.dump', 'base_dia2.dump', 'base_dia3.dump', 'base_dia4.dump', 'base_dia5.dump', 'base_dia6.dump'), $quedan)) === 6);
// Mensuales: el último de este mes, del anterior y del de antes (ej. noviembre, octubre y septiembre)
$meses = array();
foreach ($quedan as $q) if (preg_match('/dia(\d+)/', $q, $m)) $meses[date('Y-m', $hoy - (int)$m[1] * 86400)] = true;
$hace2 = date('Y-m', strtotime('first day of -2 month', $hoy));
revisar('se guarda uno de cada uno de los últimos 3 meses (hasta ' . $hace2 . ')', isset($meses[$hace2]) && isset($meses[date('Y-m', strtotime('first day of -1 month', $hoy))]), implode(', ', array_keys($meses)));
revisar('nada de hace 4 meses', !in_array('base_dia119.dump', $quedan, true));
array_map('unlink', glob($dir . '/*'));
rmdir($dir);

echo "Respaldar ahora (Configuraciones > Respaldos):\n";
entrar();
list($c, $b) = pedir(BASE . '/Configuraciones/respaldos');
$antes = json($b);
revisar('la lista de respaldos abre', $c === 200 && isset($antes['respaldos']), "código $c: " . substr($b, 0, 200));
if (empty($antes['pg_dump'])) {
	echo "  (este equipo no tiene pg_dump: no se prueba el respaldo de verdad)\n";
} else {
	$t = microtime(true);
	list($c, $b) = pedir(BASE . '/Configuraciones/respaldarAhora', array());
	$s = round(microtime(true) - $t, 1);
	$hecho = json($b);
	revisar("se hace el respaldo ($s s)", $c === 200 && !empty($hecho['ok']), "código $c: " . substr($b, 0, 300));
	$archivo = $hecho['archivo'] ?? '';
	revisar('aparece primero en la lista', ($hecho['respaldos'][0]['archivo'] ?? '') === $archivo);
	revisar('pesa algo y menos de 200 MB', ($hecho['bytes'] ?? 0) > 1000 && ($hecho['bytes'] ?? 0) < 200 * 1048576, ($hecho['bytes'] ?? 0) . ' bytes');
	revisar('no se acumulan: máximo 14', count($hecho['respaldos'] ?? array()) <= 14);
	list($c, $cuerpo) = pedir(BASE . '/Configuraciones/descargarRespaldo?archivo=' . rawurlencode($archivo), null, false);
	revisar('se descarga y es un respaldo de PostgreSQL (empieza con PGDMP)', $c === 200 && substr($cuerpo, 0, 5) === 'PGDMP', "código $c");
	revisar('lo descargado pesa lo mismo', strlen($cuerpo) === (int)$hecho['bytes']);
	list($c, ) = pedir(BASE . '/Configuraciones/descargarRespaldo?archivo=' . rawurlencode('../../.env.php'), null, false);
	revisar('pedir ../../.env.php no descarga nada', $c === 404, "código $c");
	// Por la web directa (sin pasar por Configuraciones) no se ve
	$url = preg_replace('#/index\.php$#', '', BASE);
	foreach (array('/backups/', '/backups/index.html', '/backups/x/' . $archivo) as $u) {
		list($c, ) = pedir($url . $u, null, false);
		revisar("la web no muestra $u", $c === 404 || $c === 403, "código $c (¿la web se prendió sin router.php?)");
	}
}

echo "Fotos de productos:\n";
$fotos = glob($raiz . '/uploads/*/productos/*');
$malas = array();
foreach ($fotos as $f) {
	$rel = substr($f, strlen($raiz) + 1);
	if (substr($f, -4) === '.tmp') continue;
	$cab = (string)file_get_contents($f, false, null, 0, 12);
	if (!preg_match('#^uploads/(\d+-[0-9k]|sin-rut-[A-Za-z0-9_]+-[A-Za-z0-9_]+)/productos/\d+\.webp$#', $rel)) $malas[] = "$rel (nombre o carpeta)";
	elseif (substr($cab, 0, 4) !== 'RIFF' || substr($cab, 8, 4) !== 'WEBP') $malas[] = "$rel (no es WEBP por dentro)";
}
revisar(count($fotos) . ' foto(s): todas en uploads/<RUT>/productos/<id>.webp y en WEBP', !$malas, implode(', ', $malas));
if (!class_exists('CI_Controller', false)) { eval('class CI_Controller {}'); }
require_once $raiz . '/application/controllers/ImagenProducto.php';
revisar('RUT con puntos y 0 adelante va a la misma carpeta', ImagenProducto::carpetaDe('09.209.734-K') === '9209734-k' && ImagenProducto::carpetaDe('9209734-k') === '9209734-k');
revisar('sin RUT, cada empresa tiene su carpeta (no una común)', ImagenProducto::carpetaDe('', 'localhost', 'baseA') !== ImagenProducto::carpetaDe('', 'localhost', 'baseB'));
revisar('un RUT con ../ no sale de uploads', strpos(ImagenProducto::carpetaDe('../../etc'), '..') === false && strpos(ImagenProducto::carpetaDe('', '../x', '../y'), '/') === false);

terminar('respaldos y fotos');
