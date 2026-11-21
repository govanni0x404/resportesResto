<?php
/**
 * Prueba del JavaScript de las pantallas (v1.27.0).
 *
 *   1) prende la web en modo local:   C:\xampp\php\php.exe -S localhost:8001 router.php
 *   2) en otra consola:               C:\xampp\php\php.exe tests\caja_js_test.php
 *   Necesita Node.js instalado (node --check revisa que el JavaScript esté bien escrito, sin ejecutarlo).
 *
 * - La Caja está dividida en application/views/caja/js/: revisa que js.php incluya TODAS las partes, una sola vez y en
 *   orden (una parte olvidada dejaría la Caja sin una función y fallaría al cobrar o imprimir).
 * - Abre cada pantalla con menú (Caja, Dashboard, Productos...) y revisa con Node que cada <script> de la página sea
 *   JavaScript válido. Un error de escritura deja muerta toda la pantalla.
 */
require __DIR__ . '/lib_pruebas.php';
$raiz = dirname(__DIR__);

echo "Partes de la Caja:\n";
$js = file_get_contents($raiz . '/application/views/caja/js.php');
preg_match_all("#include __DIR__ \. '/js/([^']+)'#", $js, $m);
$incluidas = $m[1];
$enCarpeta = array_map('basename', glob($raiz . '/application/views/caja/js/*.php'));
sort($enCarpeta);
revisar('js.php incluye las ' . count($enCarpeta) . ' partes de la carpeta', $incluidas === $enCarpeta,
	'faltan: ' . implode(', ', array_diff($enCarpeta, $incluidas)) . ' | sobran: ' . implode(', ', array_diff($incluidas, $enCarpeta)));
revisar('ninguna parte está incluida dos veces', count($incluidas) === count(array_unique($incluidas)));

$node = trim((string)shell_exec(stripos(PHP_OS, 'WIN') === 0 ? 'where node 2>NUL' : 'command -v node 2>/dev/null'));
if ($node === '') { echo "No encontré Node.js: no puedo revisar el JavaScript de las pantallas.\n"; terminar('JavaScript'); }
$node = strtok($node, "\r\n");

entrar();
// Todas las pantallas: los controladores que tienen index (menos los de entrada y los que no tienen menú)
$pantallas = array();
foreach (glob($raiz . '/application/controllers/*.php') as $archivo) {
	$ctrl = basename($archivo, '.php');
	if (in_array($ctrl, array('Login', 'Welcome', 'Despliegue', 'Respaldo', 'ApiMovil', 'AgenteImpresion', 'ImagenImpresion'), true)) continue;   // Despliegue y Respaldo: solo consola
	if (preg_match('/public function index\s*\(/', file_get_contents($archivo))) $pantallas[] = $ctrl;
}
$tmp = tempnam(sys_get_temp_dir(), 'js');
echo "JavaScript de cada pantalla (node --check):\n";
foreach ($pantallas as $p) {
	list($c, $html) = pedir(BASE . '/' . $p, null, false);
	if ($c !== 200) { revisar("$p abre", false, "código $c"); continue; }
	if (stripos($html, '<html') === false) continue;   // no es una pantalla (responde datos o redirige)
	// Solo los <script> con código (sin src y sin type de plantilla)
	preg_match_all('#<script(\s[^>]*)?>(.*?)</script>#is', $html, $s, PREG_SET_ORDER);
	$malos = array();
	$n = 0;
	foreach ($s as $i => $bloque) {
		$attr = (string)$bloque[1];
		if (stripos($attr, 'src=') !== false) continue;
		if (preg_match('/type=["\']?([^"\'\s>]+)/i', $attr, $t) && !preg_match('#^(text/javascript|application/javascript|module)$#i', $t[1])) continue;
		if (trim($bloque[2]) === '') continue;
		$n++;
		file_put_contents($tmp, $bloque[2]);
		$salida = array();
		exec(escapeshellarg($node) . ' --check ' . escapeshellarg($tmp) . ' 2>&1', $salida, $cod);
		if ($cod !== 0) {
			$linea = '';
			foreach ($salida as $l) if (preg_match('/SyntaxError.*/', $l, $e)) { $linea = $e[0]; break; }
			$malos[] = "script #$i: " . ($linea ?: implode(' ', array_slice($salida, 0, 3)));
		}
	}
	revisar("$p: sus $n scripts son JavaScript válido", !$malos, implode("\n            ", $malos));
}
@unlink($tmp);
terminar('JavaScript');
