<?php
/**
 * Corre todas las pruebas automáticas de una vez (v1.27.0).
 *
 *   php tests/todas.php                                         (web en http://localhost:8001/index.php)
 *   php tests/todas.php http://localhost:8002/index.php COPIA   (también las que graban ventas: SOLO con una copia de la base)
 *
 * Termina con 0 si todas pasaron y 1 si alguna falló.
 */
$base = $argv[1] ?? 'http://localhost:8001/index.php';
$copia = ($argv[2] ?? '') === 'COPIA';
$pruebas = array(
	'totales_test.php' => array(),                       // cálculo de totales (no necesita la web)
	'humo_test.php' => array($base),                     // todas las pantallas y consultas sin errores
	'consultas_sesion_test.php' => array($base),         // consultas con "?" y sesión vencida
	'caja_js_test.php' => array($base),                  // la Caja dividida completa y el JavaScript de cada pantalla
	'respaldo_test.php' => array($base),                 // respaldos de la base y fotos en webp
	'navegador_test.mjs' => array($base),                // cada pantalla abre en un navegador sin errores de JavaScript (Node + Chrome)
);
if ($copia) {
	$pruebas['venta_caja_test.php'] = array($base, 'COPIA');   // abrir, cobrar, anular y cerrar (graba)
	$pruebas['stock_bajo_test.php'] = array($base, 'COPIA');   // aviso de stock bajo de la Caja (crea un insumo)
	$pruebas['doble_comanda_test.php'] = array($base, 'COPIA');   // un producto en dos impresoras y el agregado en la suya
	$pruebas['privilegios_test.php'] = array($base, 'COPIA');   // lo nuevo con un rol que no es gerencia (ver el archivo)
	$pruebas['agregados_mesa_test.php'] = array($base, 'COPIA');   // v1.30.3: máximo de agregados, agregados unidos en la mesa, descuento 0
	$pruebas['promo_sushi_opciones_test.php'] = array($base, 'COPIA');   // v1.32.0: promos de sushi: varios a la vez, salsas, máximos, precio por envoltorio, fija
	$pruebas['anular_varias_test.php'] = array($base, 'COPIA');
	$pruebas['recetas_unidades_test.php'] = array($base, 'COPIA');   // v1.34.0: kilos y gramos en insumos, recetas y producción   // v1.33.0: anular todas las ventas en espera y varias mesas a la vez
	$pruebas['agregados_extras_categorias_test.php'] = array($base, 'COPIA');   // v1.35.0: agregados extra con costo y categorías de agregados
	$pruebas['promos_test.php'] = array($base, 'COPIA');   // v1.36.0: promos normales (Carta > Promos)
	$pruebas['receta_produccion_test.php'] = array($base, 'COPIA');   // v1.37.0: receta de producción completa, costos y producción
	$pruebas['unidades_volumen_test.php'] = array($base, 'COPIA');   // v1.37.16: litros y mililitros en insumos, recetas y producción
	$pruebas['tablas_union_test.php'] = array($base, 'COPIA');   // v1.41.0: tablas de agregados varios y promos (revisa la base con PRUEBA_PSQL)
	if (is_file(__DIR__ . '/cocina_reservas_test.php')) $pruebas['cocina_reservas_test.php'] = array($base, 'COPIA');   // pantalla de cocina y reservas (rama de prueba)
}

$php = escapeshellarg(PHP_BINARY);
$fallaron = array();
foreach ($pruebas as $archivo => $args) {
	if (!is_file(__DIR__ . '/' . $archivo)) continue;
	echo "\n=== $archivo ===\n";
	$programa = substr($archivo, -4) === '.mjs' ? 'node' : $php;   // las pruebas .mjs corren con Node.js
	passthru($programa . ' ' . escapeshellarg(__DIR__ . '/' . $archivo) . ' ' . implode(' ', array_map('escapeshellarg', $args)), $cod);
	if ($cod !== 0) $fallaron[] = $archivo;
}
echo "\n";
if (!$copia) echo "(No corrí venta_caja_test.php ni stock_bajo_test.php: graban datos. Úsala con una copia: php tests/todas.php <url de la copia> COPIA)\n";
if ($fallaron) { echo 'FALLARON: ' . implode(', ', $fallaron) . "\n"; exit(1); }
echo "TODAS LAS PRUEBAS PASARON\n";
exit(0);
