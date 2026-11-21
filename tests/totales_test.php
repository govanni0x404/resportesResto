<?php
/**
 * Prueba de los montos de la Caja (v1.20.0). No usa la base de datos ni la web.
 *
 *   C:\xampp\php\php.exe tests\totales_test.php            (2000 ventas al azar)
 *   C:\xampp\php\php.exe tests\totales_test.php 10000      (las que quieras)
 *
 * Compara, venta por venta:
 *   1. Mcaja::montos() (lo que graba el servidor) con totales() de views/caja/js.php (lo que ve el cajero). Si no
 *      coinciden, el servidor rechaza el pago ("Falta pagar") o cobra distinto de lo que muestra la pantalla.
 *   2. Sin ILA, que los montos sean los mismos de antes de v1.20.0 (no cambia nada para quien no usa ILA).
 *   3. Reglas: boleta / interno total = suma de precios - descuento; factura IVA = round(Neto x 19%);
 *      total = neto + IVA + ILA + exento.
 * Necesita Node.js para la parte 1 (si no está, esa parte se salta y lo avisa).
 * Termina con código 0 si todo está bien y 1 si algo falla (sirve para revisarlo antes de subir al VPS).
 */
error_reporting(E_ALL);
define('BASEPATH', __DIR__);
class CI_Model { public function __get($k) { return null; } }

$raiz = dirname(__DIR__);
require $raiz . '/application/models/Mcaja.php';

$casos = max(1, (int)($argv[1] ?? 2000));
mt_srand(20261119);   // siempre las mismas ventas: si algo falla, se puede repetir

$caja = (new ReflectionClass('Mcaja'))->newInstanceWithoutConstructor();
$montos = new ReflectionMethod('Mcaja', 'montos');
$montos->setAccessible(true);

$tasasIla = array(0, 0, 0, 10, 18, 20.5, 31.5);     // la mayoría sin ILA, como en la vida real
$precios = array(1500, 1800, 2200, 2990, 3290, 3490, 3990, 4290, 8990, 9490, 10490, 13990, 14490, 15990, 16990);
$fallas = array();
$lote = array();

for ($n = 0; $n < $casos; $n++) {
	$lineas = array();
	for ($i = 0, $k = mt_rand(1, 6); $i < $k; $i++) {
		$exento = mt_rand(1, 12) === 1;
		$lineas[] = array(
			'bruto' => $precios[array_rand($precios)] * mt_rand(1, 4),
			'exento' => $exento,
			'ila' => $exento ? 0 : $tasasIla[array_rand($tasasIla)],
		);
	}
	$sub = array_sum(array_column($lineas, 'bruto'));
	$descuento = mt_rand(1, 3) === 1 ? mt_rand(0, (int)($sub * 0.3)) : 0;
	$factura = mt_rand(0, 1) === 1;
	$php = $montos->invoke($caja, $lineas, $descuento, $factura, 19);
	unset($php['lineas']);
	$lote[] = array('lineas' => $lineas, 'desc' => $descuento, 'factura' => $factura, 'php' => $php);

	// 2. Sin ILA: la fórmula de siempre (antes de v1.20.0)
	if (!array_filter(array_column($lineas, 'ila'))) {
		$viejo = montosAntes($lineas, $descuento, $factura, 19);
		foreach ($viejo as $c => $v) {
			if ((int)$php[$c] !== (int)$v) $fallas[] = "Caso $n (sin ILA): $c = {$php[$c]}, antes era $v";
		}
	}
	// 3. Reglas
	if ($php['total'] !== $php['neto'] + $php['iva'] + $php['ila'] + $php['exento']) $fallas[] = "Caso $n: total no es neto + IVA + ILA + exento";
	if (!$factura && $php['total'] !== $sub - $php['descuento']) $fallas[] = "Caso $n: en boleta el total no es la suma de precios menos el descuento";
	if ($factura && $php['iva'] !== (int)round($php['neto'] * 0.19)) $fallas[] = "Caso $n: en factura el IVA no es round(neto x 19%)";
	if ($php['ila'] < 0 || $php['iva'] < 0 || $php['neto'] < 0) $fallas[] = "Caso $n: monto negativo";
}

// 1. Servidor vs pantalla (Node)
// v1.27.0: el JavaScript de la Caja está dividido en views/caja/js/ (js.php los incluye en orden): se juntan todas las partes
$jsCaja = file_get_contents($raiz . '/application/views/caja/js.php');
foreach ((array)glob($raiz . '/application/views/caja/js/*.php') as $parte) $jsCaja .= "\n" . file_get_contents($parte);
$jsOk = preg_match('/\n(\t\tfunction totales\(\) \{.*?\n\t\t\})\r?\n/s', $jsCaja, $m);
$node = trim((string)@shell_exec('node --version 2>&1'));
if (!$jsOk) {
	$fallas[] = 'No encontré function totales() en views/caja/js.php ni en views/caja/js/ (¿se cambió de nombre o de sangría?)';
} elseif (strpos($node, 'v') !== 0) {
	echo "AVISO: sin Node.js no se compara con la pantalla (instálalo para la prueba completa)\n";
} else {
	$tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dataresto_totales_' . getmypid();
	file_put_contents($tmp . '.json', json_encode($lote));
	file_put_contents($tmp . '.js', 'const fs = require("fs");
const lote = JSON.parse(fs.readFileSync(process.argv[2], "utf8"));
const each = (a, f) => { if (Array.isArray(a)) a.forEach((v, i) => f(i, v)); else Object.keys(a).forEach(k => f(k, a[k])); };
const res = lote.map(c => {
  const $ = () => ({ val: () => String(c.desc) }); $.each = each;
  const fn = new Function("lineasBrutas", "tipoDesc", "catalogo", "documentoActual", "$", ' . json_encode($m[1]) . ' + "; return totales();");
  return fn(() => c.lineas, "monto", { config: { por_iva: 19 } }, () => ({ es_factura: c.factura }), $);
});
process.stdout.write(JSON.stringify(res));');
	$js = json_decode((string)shell_exec('node ' . escapeshellarg($tmp . '.js') . ' ' . escapeshellarg($tmp . '.json')), true);
	@unlink($tmp . '.js'); @unlink($tmp . '.json');
	if (!is_array($js) || count($js) !== count($lote)) {
		$fallas[] = 'No se pudo correr totales() en Node';
	} else {
		foreach ($lote as $n => $c) {
			foreach (array('subtotal', 'descuento', 'exento', 'neto', 'iva', 'ila', 'total') as $campo) {
				if ((int)$c['php'][$campo] !== (int)$js[$n][$campo]) {
					$fallas[] = "Caso $n: $campo servidor = {$c['php'][$campo]}, pantalla = {$js[$n][$campo]} " . json_encode($c['lineas']) . " desc {$c['desc']}" . ($c['factura'] ? ' factura' : ' boleta');
				}
			}
		}
	}
}

if ($fallas) {
	echo count($fallas) . " FALLA(S):\n - " . implode("\n - ", array_slice($fallas, 0, 20)) . "\n";
	exit(1);
}
echo "OK: $casos ventas al azar" . (strpos($node, 'v') === 0 ? ", servidor y pantalla iguales (Node $node)" : '') . ", sin ILA igual que antes y reglas cumplidas\n";
exit(0);

/** Mcaja::montos() de antes de v1.20.0 (sin ILA), para comprobar que no cambió nada. */
function montosAntes($lineas, $descuento, $esFactura, $porIva)
{
	$factor = 1 + $porIva / 100;
	$subtotal = 0; $subAfecto = 0;
	foreach ($lineas as $l) { $subtotal += $l['bruto']; if (empty($l['exento'])) $subAfecto += $l['bruto']; }
	$descuento = min(max(0, (int)$descuento), $subtotal);
	$descAfecto = $subtotal > 0 ? (int)round($descuento * $subAfecto / $subtotal) : 0;
	$exento = ($subtotal - $subAfecto) - ($descuento - $descAfecto);
	if ($esFactura) {
		$netoLineas = 0;
		foreach ($lineas as $l) if (empty($l['exento'])) $netoLineas += (int)round($l['bruto'] / $factor);
		$neto = $subAfecto > 0 ? $netoLineas - (int)round($descAfecto / $factor) : 0;
		$iva = (int)round($neto * $porIva / 100);
	} else {
		$neto = (int)round(($subAfecto - $descAfecto) / $factor);
		$iva = ($subAfecto - $descAfecto) - $neto;
	}
	return array('subtotal' => $subtotal, 'descuento' => $descuento, 'exento' => $exento, 'neto' => $neto, 'iva' => $iva, 'total' => $neto + $iva + $exento);
}
