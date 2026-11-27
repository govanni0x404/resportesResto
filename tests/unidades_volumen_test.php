<?php
/**
 * Prueba de la v1.37.16 (pedido del usuario: "ya se probó de kg a gr, revisa que de litro a mililitro funcione"):
 * lo mismo que recetas_unidades_test y receta_produccion_test, pero con líquidos (1 LITRO = 1000 MILILITRO):
 *  - el insumo en LITRO con receta en MILILITRO queda 1 = 1000 solo;
 *  - la receta cobra cada mililitro (250 ml de un aceite de $4.000 el litro = $1.000);
 *  - la receta de producción acepta mililitros y litros, rechaza gramos para un líquido y dice cuánto sale el litro;
 *  - la producción descuenta en litros (500 ml = 0,5 L) y suma lo producido en litros.
 *
 * ¡CREA INSUMOS, UNA RECETA, STOCK Y UNA PRODUCCIÓN! Solo con una COPIA de la base:
 *   php tests/unidades_volumen_test.php http://localhost:8002/index.php COPIA
 */
require __DIR__ . '/lib_pruebas.php';
if (($argv[2] ?? '') !== 'COPIA') {
	echo "Esta prueba crea insumos, recetas, stock y producciones. Córrela solo contra una COPIA de la base:\n"
		. "  php tests/unidades_volumen_test.php http://localhost:8002/index.php COPIA\n";
	exit(1);
}
entrar();
pedir(BASE . '/Login/postLoginWarmup', array());
$cerca = function ($a, $b, $tol = 0.01) { return abs((float)$a - (float)$b) < $tol; };

echo "Unidades:\n";
list(, $b) = pedir(BASE . '/Umedida/equivalencias');
$id = array();
foreach ((array)json($b) as $u) {
	if ($u['tipo'] === 'volumen' && (float)$u['factor'] === 1000.0 && !isset($id['l'])) $id['l'] = (int)$u['id_umedida'];
	if ($u['tipo'] === 'volumen' && (float)$u['factor'] === 1.0 && !isset($id['ml'])) $id['ml'] = (int)$u['id_umedida'];
	if ($u['tipo'] === 'peso' && (float)$u['factor'] === 1.0 && !isset($id['gr'])) $id['gr'] = (int)$u['id_umedida'];
	if ($u['tipo'] === 'unidad' && !isset($id['un'])) $id['un'] = (int)$u['id_umedida'];
}
revisar('hay litro (1000) y mililitro (1) con su equivalencia', isset($id['l'], $id['ml']), substr($b, 0, 300));
if (!isset($id['l'], $id['ml'], $id['gr'])) terminar('unidades de volumen');

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
function insumoReceta($idInsumo)
{
	list(, $b) = pedir(BASE . '/Recetas/llenarCboInsumos', array('id_insumo' => 0));
	foreach ((array)json($b) as $i) if ((int)$i['id_insumo'] === (int)$idInsumo) return $i;
	return null;
}
$suf = substr((string)time(), -6) . mt_rand(1, 9);

echo "Insumos en litros con receta en mililitros:\n";
// Se manda la medida de receta mal escrita (5): con LITRO y MILILITRO el servidor la calcula (1000)
$lt = array('id_umedida_inventario' => $id['l'], 'umedida_inventario' => 1, 'id_umedida_receta' => $id['ml'], 'umedida_receta' => 5);
$aceite = grabarInsumo(array('cbarra' => 'ACE' . $suf, 'nombre_insumo' => 'ACEITE VOL ' . $suf, 'costo_neto_ppp' => 4000, 'costo_bruto_ppp' => 4760) + $lt);
$vinagre = grabarInsumo(array('cbarra' => 'VIN' . $suf, 'nombre_insumo' => 'VINAGRE VOL ' . $suf, 'costo_neto_ppp' => 2000, 'costo_bruto_ppp' => 2380) + $lt);
$salsa = grabarInsumo(array('cbarra' => 'SAL' . $suf, 'nombre_insumo' => 'ALINO VOL ' . $suf) + $lt);
revisar('se crean ACEITE ($4.000 el litro), VINAGRE ($2.000 el litro) y ALIÑO (en litros)', $aceite && $vinagre && $salsa);
if (!$aceite || !$vinagre || !$salsa) terminar('unidades de volumen');
$a = insumoReceta($aceite);
revisar('1 LITRO = 1000 MILILITRO lo calcula solo (aunque se mandó 5)', $a && (float)$a['umedida_receta'] === 1000.0 && (float)$a['umedida_inventario'] === 1.0,
	json_encode($a ? array($a['umedida_inventario'], $a['umedida_receta']) : null));
revisar('1 mililitro de aceite cuesta $4', $a && $cerca($a['costo_receta'], 4, 0.0001), json_encode($a['costo_receta'] ?? null));

echo "Receta (Carta):\n";
list(, $b) = pedir(BASE . '/Recetas/llenarFamiliaReceta', array());
$fam = (int)(json($b)[0]['id_familia_receta'] ?? 0);
list(, $b) = pedir(BASE . '/Recetas/llenarSeccionReceta', array());
$sec = (int)(json($b)[0]['id_seccion'] ?? 0);
list(, $b) = pedir(BASE . '/Recetas/grabarReceta', array(
	'prod' => array('id_receta' => 0, 'nombre_receta' => 'ENSALADA VOL ' . $suf, 'rendimiento' => 1, 'precio_venta' => 5950, 'id_umedida' => $id['un'] ?? 1,
		'id_familia_receta' => $fam, 'id_seccion' => $sec, 'preparacion' => ''),
	'detalleReceta' => array(
		array('id_detalle_receta' => 0, 'id_insumo' => $aceite, 'cantidad' => 250, 'costo_pp' => 1, 'costo_total' => 1, 'es_receta' => 'NO'),
		array('id_detalle_receta' => 0, 'id_insumo' => $vinagre, 'cantidad' => 15, 'costo_pp' => 1, 'costo_total' => 1, 'es_receta' => 'NO'),
	)));
$idReceta = (int)(json($b)['id_receta'] ?? 0);
revisar('se graba la receta (250 ml de aceite y 15 ml de vinagre)', $idReceta > 0, substr($b, 0, 200));
list(, $b) = pedir(BASE . '/Recetas/getInsumosReceta', array('id_receta' => $idReceta));
$l = array();
foreach ((array)json($b) as $x) $l[(int)$x['id_insumo']] = $x;
revisar('250 ml de aceite cuestan $1.000 (no $1.000.000)', isset($l[$aceite]) && $cerca($l[$aceite]['costo_total'], 1000), json_encode($l[$aceite] ?? null));
revisar('15 ml de vinagre cuestan $30', isset($l[$vinagre]) && $cerca($l[$vinagre]['costo_total'], 30), json_encode($l[$vinagre]['costo_total'] ?? null));

echo "Receta de producción:\n";
list(, $b) = pedir(BASE . '/Bodegas/ListadoDeBodegas', array());
$bodega = (int)(json($b)['aaData'][0]['id_bodega'] ?? 0);
$rp = function ($rinde, $um, $lineas) use ($salsa, $suf) {
	return pedir(BASE . '/RecetaProduccion/guardarCompleta', array('id' => 0, 'nombre' => 'alino vol ' . $suf, 'id_insumo' => $salsa,
		'cantidad' => $rinde, 'id_umedida' => $um, 'vigente' => 1, 'lineas' => $lineas));
};
list($c, $b) = $rp(1, $id['l'], array(array('id_insumo' => $aceite, 'cantidad' => 300, 'id_umedida' => $id['gr'], 'id_bodega' => $bodega)));
revisar('aceite en GRAMO se rechaza (un líquido no se pasa a gramos)', $c === 400 && strpos(json($b)['error'] ?? '', 'no se puede pasar') !== false, "código $c: " . substr($b, 0, 150));
// Rinde 1000 ML (= 1 L): 600 ml de aceite ($2.400) + 0,4 LITRO de vinagre ($800) = $3.200 el litro
list($c, $b) = $rp(1000, $id['ml'], array(array('id_insumo' => $aceite, 'cantidad' => 600, 'id_umedida' => $id['ml'], 'id_bodega' => $bodega),
	array('id_insumo' => $vinagre, 'cantidad' => 0.4, 'id_umedida' => $id['l'], 'id_bodega' => $bodega)));
$r = json($b);
revisar('receta de producción: rinde 1000 ml con 600 ml de aceite y 0,4 L de vinagre', $c === 200 && !empty($r['id']), "código $c: " . substr($b, 0, 200));
revisar('cuesta $3.200 y sale $3.200 el litro', $cerca($r['costo'] ?? 0, 3200) && $cerca($r['costo_unidad'] ?? 0, 3200), json_encode($r));
list(, $b) = pedir(BASE . '/Produccion/recetaParaProducir', array('id_insumo' => $salsa));
revisar('al cargarla en Producción rinde 1 litro (1000 ml)', $cerca(json($b)['receta']['cantidad_inventario'] ?? 0, 1, 0.0001), substr($b, 0, 200));

echo "Producción:\n";
list(, $b) = pedir(BASE . '/EntradaBodega/grabarEntradaBodega', array('el_json' => json_encode(array('id_bodega' => $bodega, 'fecha' => date('Y-m-d'), 'observaciones' => 'Prueba'))));
$es = (int)(json($b)[0]['id_esbodega'] ?? 0);
pedir(BASE . '/EntradaBodega/grabarDetalleEntradaBodega', array('id' => $es, 'id_bodega_stock' => $bodega,
	'detalle' => array(array('id_insumo' => $aceite, 'cantidad' => 5), array('id_insumo' => $vinagre, 'cantidad' => 2))));
$stock = function ($i) use ($bodega) {
	list(, $b) = pedir(BASE . '/Produccion/obtenerStockActual', array('id_insumo' => $i, 'id_bodega' => $bodega));
	return (float)(json($b)['stock_actual'] ?? -1);
};
revisar('entran 5 L de aceite y 2 L de vinagre', $cerca($stock($aceite), 5, 0.001) && $cerca($stock($vinagre), 2, 0.001));
// Producir 2 L de aliño = receta x2: 1200 ml de aceite y 800 ml de vinagre (la pantalla manda la cantidad en mililitros)
list(, $b) = pedir(BASE . '/Produccion/grabarProduccion', array('produccion' => json_encode(array(
	'fecha' => date('Y-m-d'), 'id_insumo_elaborado' => $salsa, 'cantidad_producida' => 2, 'id_bodega_destino' => $bodega,
	'observaciones' => 'Prueba', 'nombre_insumo_elaborado' => 'ALINO VOL ' . $suf,
	'insumos' => array(array('id_insumo' => $aceite, 'id_bodega' => $bodega, 'cantidad' => 1200, 'nombre_insumo' => 'ACEITE'),
		array('id_insumo' => $vinagre, 'id_bodega' => $bodega, 'cantidad' => 800, 'nombre_insumo' => 'VINAGRE'))))));
$r = json($b);
revisar('producir 2 L de aliño (1200 ml de aceite y 800 ml de vinagre)', !empty($r['success']), substr($b, 0, 200));
revisar('cuesta $6.400 (1,2 L x $4.000 + 0,8 L x $2.000)', $cerca($r['costo_total'] ?? 0, 6400), substr($b, 0, 200));
revisar('quedan 3,8 L de aceite (se descontaron 1,2 L, no 1.200 L)', $cerca($stock($aceite), 3.8, 0.0001), (string)$stock($aceite));
revisar('quedan 1,2 L de vinagre', $cerca($stock($vinagre), 1.2, 0.0001), (string)$stock($vinagre));
revisar('el aliño suma 2 L a su stock', $cerca($stock($salsa), 2, 0.0001), (string)$stock($salsa));
$s = insumoReceta($salsa);
revisar('el litro de aliño queda costando $3.200 ($3,20 el mililitro en las recetas)', $s && $cerca($s['costo_neto_ppp'], 3200) && $cerca($s['costo_receta'], 3.2, 0.0001),
	json_encode($s ? array($s['costo_neto_ppp'], $s['costo_receta']) : null));

terminar('unidades de volumen');
