<?php
/**
 * Prueba de la v1.37.0 (pedido del usuario: revisar insumos, medidas, receta de producción y producción):
 *  - 1 KILOGRAMO = 1000 GRAMO en el insumo (la medida de receta sale sola);
 *  - la receta de producción se graba completa (rendimiento + sus insumos, cada uno en la unidad que se quiera) y dice
 *    cuánto cuesta y cuánto sale 1 unidad de lo producido; las unidades que no se pueden convertir se rechazan;
 *  - al cargarla en Producción, el rendimiento viene en la unidad de inventario (2000 GRAMO = 2 KILOGRAMO);
 *  - al producir se descuenta el gramaje exacto de cada insumo, sube el stock de lo producido y su costo cuadra.
 *
 * ¡CREA INSUMOS, STOCK, UNA RECETA DE PRODUCCIÓN Y UNA PRODUCCIÓN! Solo con una COPIA de la base:
 *   php tests/receta_produccion_test.php http://localhost:8002/index.php COPIA
 */
require __DIR__ . '/lib_pruebas.php';
if (($argv[2] ?? '') !== 'COPIA') {
	echo "Esta prueba crea insumos, stock y producciones. Córrela solo contra una COPIA de la base:\n"
		. "  php tests/receta_produccion_test.php http://localhost:8002/index.php COPIA\n";
	exit(1);
}
entrar();
pedir(BASE . '/Login/postLoginWarmup', array());
$cerca = function ($a, $b, $tol = 0.01) { return abs((float)$a - (float)$b) < $tol; };

echo "Preparar:\n";
list(, $b) = pedir(BASE . '/Umedida/equivalencias');
$id = array();
foreach ((array)json($b) as $u) {
	if ($u['tipo'] === 'peso' && (float)$u['factor'] === 1000.0 && !isset($id['kg'])) $id['kg'] = (int)$u['id_umedida'];
	if ($u['tipo'] === 'peso' && (float)$u['factor'] === 1.0 && !isset($id['gr'])) $id['gr'] = (int)$u['id_umedida'];
	if ($u['tipo'] === 'unidad' && !isset($id['un'])) $id['un'] = (int)$u['id_umedida'];
}
revisar('hay kilo, gramo y unidad', isset($id['kg'], $id['gr'], $id['un']));
if (!isset($id['kg'], $id['gr'], $id['un'])) terminar('receta de producción');
list(, $b) = pedir(BASE . '/Insumos/llenarCboGrupoInsumo', array());
$grupo = (int)(json($b)[0]['id_grupo_insumo'] ?? 0);
function grabarInsumo($datos)
{
	$base = array('id_insumo' => 0, 'cbarra' => '', 'nombre_insumo' => '', 'id_grupo_insumo' => $GLOBALS['grupo'], 'id_impto_iva' => 1, 'id_impto_adicional' => 0,
		'umedida_inventario' => 1, 'id_umedida_inventario' => 1, 'umedida_receta' => 1, 'id_umedida_receta' => 1, 'costo_neto_ppp' => 0,
		'costo_bruto_ppp' => 0, 'precio_ucompra' => 0, 'observaciones' => '', 'inv_cierre' => 0);
	list($c, $b) = pedir(BASE . '/Insumos/grabarInsumo', array('prod' => array_merge($base, $datos)));
	return (int)(json($b)['id_insumo'] ?? 0);
}
$suf = substr((string)time(), -6) . mt_rand(1, 9);
$kgGr = array('id_umedida_inventario' => $id['kg'], 'umedida_inventario' => 1, 'id_umedida_receta' => $id['gr'], 'umedida_receta' => 1);
$harina = grabarInsumo(array('cbarra' => 'RPH' . $suf, 'nombre_insumo' => 'HARINA RP ' . $suf, 'costo_neto_ppp' => 2000, 'costo_bruto_ppp' => 2380) + $kgGr);
$huevo = grabarInsumo(array('cbarra' => 'RPU' . $suf, 'nombre_insumo' => 'HUEVO RP ' . $suf, 'id_umedida_inventario' => $id['un'], 'id_umedida_receta' => $id['un'],
	'costo_neto_ppp' => 150, 'costo_bruto_ppp' => 178));
$masa = grabarInsumo(array('cbarra' => 'RPM' . $suf, 'nombre_insumo' => 'MASA RP ' . $suf) + $kgGr);
revisar('se crean HARINA ($2.000 el kilo), HUEVO ($150 c/u) y MASA (en kilos)', $harina && $huevo && $masa);
if (!$harina || !$huevo || !$masa) terminar('receta de producción');

echo "Medidas del insumo:\n";
list(, $b) = pedir(BASE . '/RecetaProduccion/editor?id_insumo=' . $masa);
$ed = json($b);
revisar('el insumo en KILOGRAMO con receta en GRAMO queda 1 kg = 1000 gr', ($ed['elaborado']['umedida_receta'] ?? 0) == 1000 && ($ed['elaborado']['umedida_inventario'] ?? 0) == 1,
	json_encode($ed['elaborado'] ?? null));
$ins = array();
foreach ($ed['insumos'] ?? array() as $i) $ins[$i['id_insumo']] = $i;
revisar('el editor ofrece los insumos con su costo por unidad de inventario', ($ins[$harina]['costo'] ?? 0) == 2000 && ($ins[$huevo]['costo'] ?? 0) == 150);
revisar('y no ofrece el mismo insumo que se produce', !isset($ins[$masa]));

echo "Receta de producción:\n";
$receta = function ($rinde, $um, $lineas, $idR = 0) use ($masa, $suf) {
	return pedir(BASE . '/RecetaProduccion/guardarCompleta', array('id' => $idR, 'nombre' => 'masa rp ' . $suf, 'id_insumo' => $masa,
		'cantidad' => $rinde, 'id_umedida' => $um, 'vigente' => 1, 'lineas' => $lineas));
};
list($c, $b) = $receta(2, $id['kg'], array());
revisar('sin insumos se rechaza', $c === 400, "código $c");
list($c, $b) = $receta(2, $id['un'], array(array('id_insumo' => $harina, 'cantidad' => 500, 'id_umedida' => $id['gr'])));
revisar('rendimiento en UNIDAD para un insumo en kilos se rechaza', $c === 400 && strpos(json($b)['error'] ?? '', 'no se puede pasar') !== false, "código $c: " . substr($b, 0, 150));
list($c, $b) = $receta(2, $id['kg'], array(array('id_insumo' => $huevo, 'cantidad' => 3, 'id_umedida' => $id['gr'])));
revisar('huevos en GRAMO se rechaza (no se puede convertir)', $c === 400, "código $c");
list($c, $b) = $receta(2, $id['kg'], array(array('id_insumo' => $masa, 'cantidad' => 1, 'id_umedida' => $id['kg'])));
revisar('la receta no puede llevar lo mismo que produce', $c === 400, "código $c");
// Rinde 2000 GRAMO (= 2 kg): 1 KILOGRAMO de harina ($2.000) + 4 huevos ($600) = $2.600; $1.300 el kilo de masa
list($c, $b) = $receta(2000, $id['gr'], array(array('id_insumo' => $harina, 'cantidad' => 1, 'id_umedida' => $id['kg']),
	array('id_insumo' => $huevo, 'cantidad' => 4, 'id_umedida' => $id['un'])));
$r = json($b);
$idR = (int)($r['id'] ?? 0);
revisar('se graba la receta completa (rinde 2000 gr; 1 kg de harina y 4 huevos)', $c === 200 && $idR > 0, "código $c: " . substr($b, 0, 200));
revisar('cuesta $2.600 y sale $1.300 el kilo de masa', $cerca($r['costo'] ?? 0, 2600) && $cerca($r['costo_unidad'] ?? 0, 1300) && ($r['unidad'] ?? '') !== '',
	json_encode($r));
list(, $b) = pedir(BASE . '/RecetaProduccion/editor?id=' . $idR);
$rc = json($b)['receta'] ?? array();
revisar('al abrirla trae sus 2 insumos con su unidad', count($rc['lineas'] ?? array()) === 2 && ($rc['lineas'][0]['id_umedida'] ?? 0) === $id['kg'], substr($b, 0, 300));
// Editar: 800 gr de harina en vez de 1 kg -> $1.600 + $600 = $2.200
list($c, $b) = $receta(2000, $id['gr'], array(array('id_insumo' => $harina, 'cantidad' => 800, 'id_umedida' => $id['gr']),
	array('id_insumo' => $huevo, 'cantidad' => 4, 'id_umedida' => $id['un'])), $idR);
revisar('editarla (800 gr de harina) recalcula: $2.200', $c === 200 && $cerca(json($b)['costo'] ?? 0, 2200), substr($b, 0, 200));
list(, $b) = pedir(BASE . '/RecetaProduccion/editor?id=' . $idR);
revisar('y no quedan insumos repetidos ni viejos', count(json($b)['receta']['lineas'] ?? array()) === 2);

echo "Cargar la receta en Producción:\n";
list(, $b) = pedir(BASE . '/Produccion/recetaParaProducir', array('id_insumo' => $masa));
$rp = json($b)['receta'] ?? array();
revisar('el rendimiento llega en la unidad de inventario (2000 gr = 2 kg)', $cerca($rp['cantidad_inventario'] ?? 0, 2, 0.0001) && count($rp['lineas'] ?? array()) === 2,
	substr($b, 0, 300));

echo "Producir:\n";
list(, $b) = pedir(BASE . '/Bodegas/ListadoDeBodegas', array());
$bodega = (int)(json($b)['aaData'][0]['id_bodega'] ?? 0);
list(, $b) = pedir(BASE . '/EntradaBodega/grabarEntradaBodega', array('el_json' => json_encode(array('id_bodega' => $bodega, 'fecha' => date('Y-m-d'), 'observaciones' => 'Prueba'))));
$es = (int)(json($b)[0]['id_esbodega'] ?? 0);
pedir(BASE . '/EntradaBodega/grabarDetalleEntradaBodega', array('id' => $es, 'id_bodega_stock' => $bodega,
	'detalle' => array(array('id_insumo' => $harina, 'cantidad' => 5), array('id_insumo' => $huevo, 'cantidad' => 30))));
$stock = function ($i) use ($bodega) {
	list(, $b) = pedir(BASE . '/Produccion/obtenerStockActual', array('id_insumo' => $i, 'id_bodega' => $bodega));
	return (float)(json($b)['stock_actual'] ?? -1);
};
revisar('entran 5 kg de harina y 30 huevos', $cerca($stock($harina), 5, 0.001) && $cerca($stock($huevo), 30, 0.001));
// Producir 4 kg de masa = la receta x2: 1600 gr de harina y 8 huevos (la pantalla manda la cantidad en la unidad de receta)
list($c, $b) = pedir(BASE . '/Produccion/grabarProduccion', array('produccion' => json_encode(array(
	'fecha' => date('Y-m-d'), 'id_insumo_elaborado' => $masa, 'cantidad_producida' => 4, 'id_bodega_destino' => $bodega,
	'observaciones' => 'Prueba', 'nombre_insumo_elaborado' => 'MASA RP ' . $suf,
	'insumos' => array(array('id_insumo' => $harina, 'id_bodega' => $bodega, 'cantidad' => 1600, 'nombre_insumo' => 'HARINA'),
		array('id_insumo' => $huevo, 'id_bodega' => $bodega, 'cantidad' => 8, 'nombre_insumo' => 'HUEVO'))))));
$r = json($b);
revisar('producir 4 kg de masa (1600 gr de harina y 8 huevos)', !empty($r['success']), substr($b, 0, 200));
revisar('cuesta $4.400 (1,6 kg x $2.000 + 8 x $150)', $cerca($r['costo_total'] ?? 0, 4400), substr($b, 0, 200));
revisar('se descuenta el gramaje exacto: quedan 3,4 kg de harina', $cerca($stock($harina), 3.4, 0.0001), (string)$stock($harina));
revisar('y quedan 22 huevos', $cerca($stock($huevo), 22, 0.0001), (string)$stock($huevo));
revisar('la masa producida suma 4 kg a su stock', $cerca($stock($masa), 4, 0.0001), (string)$stock($masa));
list(, $b) = pedir(BASE . '/RecetaProduccion/editor?id_insumo=' . $masa);
revisar('el kilo de masa queda costando $1.100 ($4.400 / 4 kg)', $cerca(json($b)['elaborado']['costo'] ?? 0, 1100), substr($b, 0, 200));
list(, $b) = pedir(BASE . '/RecetaProduccion/editor?id_insumo=' . $harina);
revisar('el costo de la harina no cambia ($2.000)', $cerca(json($b)['elaborado']['costo'] ?? 0, 2000));
// Segunda producción: 2 kg más con la receta tal cual ($2.200) -> promedio (4 x 1100 + 2200) / 6 = $1.100
list($c, $b) = pedir(BASE . '/Produccion/grabarProduccion', array('produccion' => json_encode(array(
	'fecha' => date('Y-m-d'), 'id_insumo_elaborado' => $masa, 'cantidad_producida' => 2, 'id_bodega_destino' => $bodega,
	'observaciones' => 'Prueba', 'nombre_insumo_elaborado' => 'MASA RP ' . $suf,
	'insumos' => array(array('id_insumo' => $harina, 'id_bodega' => $bodega, 'cantidad' => 800, 'nombre_insumo' => 'HARINA'),
		array('id_insumo' => $huevo, 'id_bodega' => $bodega, 'cantidad' => 4, 'nombre_insumo' => 'HUEVO'))))));
revisar('segunda producción: 2 kg más', !empty(json($b)['success']), substr($b, 0, 200));
revisar('la masa suma 6 kg en total', $cerca($stock($masa), 6, 0.0001), (string)$stock($masa));
list(, $b) = pedir(BASE . '/RecetaProduccion/editor?id_insumo=' . $masa);
revisar('y su costo promedio sigue cuadrando ($1.100 el kilo)', $cerca(json($b)['elaborado']['costo'] ?? 0, 1100), substr($b, 0, 200));

terminar('receta de producción');
