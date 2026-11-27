<?php
/**
 * Prueba de la v1.34.0 (pedido del usuario en cambios.md: recetas, sub recetas y producción con kilos y gramos):
 *  - las unidades saben su equivalencia (1 KILOGRAMO = 1000 GRAMO) y la medida de receta del insumo sale sola;
 *  - la receta cobra cada insumo por su unidad de receta (200 gr de un insumo de $10.000 el kilo = $2.000) y calcula su
 *    costo total y por porción al grabar;
 *  - la producción descuenta en la unidad de inventario (500 gr = 0,5 kg) y el elaborado queda como sub receta;
 *  - la receta de producción del insumo se puede cargar al producir.
 *
 * ¡CREA INSUMOS, UNA RECETA, STOCK Y UNA PRODUCCIÓN! Solo con una COPIA de la base:
 *   php tests/recetas_unidades_test.php http://localhost:8002/index.php COPIA
 */
require __DIR__ . '/lib_pruebas.php';
if (($argv[2] ?? '') !== 'COPIA') {
	echo "Esta prueba crea insumos, recetas, stock y producciones. Córrela solo contra una COPIA de la base:\n"
		. "  php tests/recetas_unidades_test.php http://localhost:8002/index.php COPIA\n";
	exit(1);
}
entrar();
pedir(BASE . '/Login/postLoginWarmup', array());

echo "Unidades:\n";
list($c, $b) = pedir(BASE . '/Umedida/equivalencias');
$us = json($b);
$id = array();
foreach ((array)$us as $u) {
	if ($u['tipo'] === 'peso' && (float)$u['factor'] === 1000.0 && !isset($id['kg'])) $id['kg'] = (int)$u['id_umedida'];
	if ($u['tipo'] === 'peso' && (float)$u['factor'] === 1.0 && !isset($id['gr'])) $id['gr'] = (int)$u['id_umedida'];
	if ($u['tipo'] === 'unidad' && !isset($id['un'])) $id['un'] = (int)$u['id_umedida'];
}
revisar('hay kilo (1000) y gramo (1) con su equivalencia', isset($id['kg'], $id['gr']), substr($b, 0, 300));
if (!isset($id['kg'], $id['gr'])) terminar('recetas y unidades');

list(, $b) = pedir(BASE . '/Insumos/llenarCboGrupoInsumo', array());
$grupo = (int)(json($b)[0]['id_grupo_insumo'] ?? 0);
function grabarInsumo($datos)
{
	$base = array('id_insumo' => 0, 'cbarra' => '', 'nombre_insumo' => '', 'id_grupo_insumo' => $GLOBALS['grupo'], 'id_impto_iva' => 1, 'id_impto_adicional' => 0,
		'umedida_inventario' => 1, 'id_umedida_inventario' => 1, 'umedida_receta' => 1, 'id_umedida_receta' => 1, 'costo_neto_ppp' => 0,
		'costo_bruto_ppp' => 0, 'precio_ucompra' => 0, 'observaciones' => '', 'inv_cierre' => 0);
	list($c, $b) = pedir(BASE . '/Insumos/grabarInsumo', array('prod' => array_merge($base, $datos)));
	return array($c, json($b), $b);
}
function insumo($idInsumo)
{
	list(, $b) = pedir(BASE . '/Recetas/llenarCboInsumos', array('id_insumo' => 0));
	foreach ((array)json($b) as $i) if ((int)$i['id_insumo'] === (int)$idInsumo) return $i;
	return null;
}
$sufijo = substr((string)time(), -6);

echo "Insumo en kilos con receta en gramos:\n";
// Se manda la medida de receta mal escrita (999): con KILOGRAMO y GRAMO el servidor la calcula (1000)
list($c, $r, $b) = grabarInsumo(array('cbarra' => 'HAR' . $sufijo, 'nombre_insumo' => 'HARINA PRUEBA ' . $sufijo,
	'id_umedida_inventario' => $id['kg'], 'umedida_inventario' => 1, 'id_umedida_receta' => $id['gr'], 'umedida_receta' => 999,
	'costo_neto_ppp' => 10000, 'costo_bruto_ppp' => 11900));
$harina = (int)($r['id_insumo'] ?? 0);
revisar('se crea HARINA (inventario KILOGRAMO, receta GRAMO, $10.000 el kilo)', $c === 200 && $harina > 0, substr($b, 0, 200));
if (!$harina) terminar('recetas y unidades');
$h = insumo($harina);
revisar('la medida de receta queda en 1000 sola (1 kg = 1000 gr), aunque se escribió 999', $h && (float)$h['umedida_receta'] === 1000.0, json_encode($h ? $h['umedida_receta'] : null));
revisar('el costo de 1 gramo es $10', $h && abs((float)$h['costo_receta'] - 10) < 0.0001, json_encode($h ? $h['costo_receta'] : null));
list($c, $r, $b) = grabarInsumo(array('cbarra' => 'MAL' . $sufijo, 'nombre_insumo' => 'MAL PRUEBA ' . $sufijo,
	'id_umedida_inventario' => $id['gr'], 'umedida_inventario' => 1, 'id_umedida_receta' => $id['kg'], 'umedida_receta' => 1));
revisar('inventario en GRAMO y receta en KILOGRAMO se rechaza (la de receta debe ser la más chica)', empty($r['success']) && stripos((string)($r['msg'] ?? ''), 'más grande') !== false, substr($b, 0, 200));

list($c, $r, $b) = grabarInsumo(array('cbarra' => 'HUE' . $sufijo, 'nombre_insumo' => 'HUEVO PRUEBA ' . $sufijo,
	'id_umedida_inventario' => $id['un'] ?? 1, 'umedida_inventario' => 1, 'id_umedida_receta' => $id['un'] ?? 1, 'umedida_receta' => 1,
	'costo_neto_ppp' => 200, 'costo_bruto_ppp' => 238));
$huevo = (int)($r['id_insumo'] ?? 0);
revisar('se crea HUEVO (por unidad, $200)', $huevo > 0, substr($b, 0, 200));

echo "Receta:\n";
list(, $b) = pedir(BASE . '/Recetas/llenarFamiliaReceta', array());
$fam = (int)(json($b)[0]['id_familia_receta'] ?? 0);
list(, $b) = pedir(BASE . '/Recetas/llenarSeccionReceta', array());
$sec = (int)(json($b)[0]['id_seccion'] ?? 0);
$nombreReceta = 'RECETA PRUEBA ' . $sufijo;
// La pantalla manda costos inventados (1): el servidor los calcula con la base
list($c, $b) = pedir(BASE . '/Recetas/grabarReceta', array(
	'prod' => array('id_receta' => 0, 'nombre_receta' => $nombreReceta, 'rendimiento' => 2, 'precio_venta' => 11900, 'id_umedida' => $id['un'] ?? 1,
		'id_familia_receta' => $fam, 'id_seccion' => $sec, 'preparacion' => ''),
	'detalleReceta' => array(
		array('id_detalle_receta' => 0, 'id_insumo' => $harina, 'cantidad' => 200, 'costo_pp' => 1, 'costo_total' => 1, 'es_receta' => 'NO'),
		array('id_detalle_receta' => 0, 'id_insumo' => $huevo, 'cantidad' => 3, 'costo_pp' => 1, 'costo_total' => 1, 'es_receta' => 'NO'),
	)));
$rr = json($b);
$idReceta = (int)($rr['id_receta'] ?? 0);
revisar('se graba la receta (200 gr de harina y 3 huevos, rinde 2 porciones)', ($rr['status'] ?? '') === 'success' && $idReceta > 0, substr($b, 0, 200));
list(, $b) = pedir(BASE . '/Recetas/getInsumosReceta', array('id_receta' => $idReceta));
$lineas = array();
foreach ((array)json($b) as $l) $lineas[(int)$l['id_insumo']] = $l;
revisar('200 gr de harina cuestan $2.000 (no $2.000.000)', isset($lineas[$harina]) && (float)$lineas[$harina]['costo_total'] === 2000.0 && abs((float)$lineas[$harina]['costo_pp'] - 10) < 0.0001,
	json_encode($lineas[$harina] ?? null));
revisar('3 huevos cuestan $600', isset($lineas[$huevo]) && (float)$lineas[$huevo]['costo_total'] === 600.0, json_encode($lineas[$huevo]['costo_total'] ?? null));
list(, $b) = pedir(BASE . '/Recetas/listadoRecetas');
$rec = null;
foreach ((json($b)['aaData'] ?? array()) as $x) if ((int)$x['id_receta'] === $idReceta) $rec = $x;
revisar('la receta queda con costo de insumos $2.600 al grabar', $rec && (float)$rec['costo_insumos'] === 2600.0, json_encode($rec));
revisar('y su margen calculado (precio neto $10.000 - porción $1.300 = $8.700)', $rec && abs((float)$rec['margen_ganancia'] - 8700) < 1, json_encode($rec ? $rec['margen_ganancia'] : null));

echo "Producción:\n";
list($c, $r, $b) = grabarInsumo(array('cbarra' => 'MAS' . $sufijo, 'nombre_insumo' => 'MASA PRUEBA ' . $sufijo,
	'id_umedida_inventario' => $id['kg'], 'umedida_inventario' => 1, 'id_umedida_receta' => $id['gr'], 'umedida_receta' => 1000));
$masa = (int)($r['id_insumo'] ?? 0);
revisar('se crea MASA (el insumo elaborado, en kilos)', $masa > 0, substr($b, 0, 200));
list(, $b) = pedir(BASE . '/Bodegas/ListadoDeBodegas', array());
$bodega = (int)(json($b)['aaData'][0]['id_bodega'] ?? 0);
revisar('hay una bodega', $bodega > 0);
// 2 kilos de harina a la bodega
list(, $b) = pedir(BASE . '/EntradaBodega/grabarEntradaBodega', array('el_json' => json_encode(array('id_bodega' => $bodega, 'fecha' => date('Y-m-d'), 'observaciones' => 'Prueba'))));
$es = (int)(json($b)[0]['id_esbodega'] ?? 0);
list($c, $b) = pedir(BASE . '/EntradaBodega/grabarDetalleEntradaBodega', array('id' => $es, 'id_bodega_stock' => $bodega,
	'detalle' => array(array('id_insumo' => $harina, 'cantidad' => 2))));
list(, $b) = pedir(BASE . '/Produccion/obtenerStockActual', array('id_insumo' => $harina, 'id_bodega' => $bodega));
revisar('entran 2 kilos de harina', abs((float)(json($b)['stock_actual'] ?? 0) - 2) < 0.001, substr($b, 0, 200));
$prod = function ($gramos) use ($harina, $masa, $bodega, $sufijo) {
	return pedir(BASE . '/Produccion/grabarProduccion', array('produccion' => json_encode(array(
		'fecha' => date('Y-m-d'), 'id_insumo_elaborado' => $masa, 'cantidad_producida' => 1, 'id_bodega_destino' => $bodega,
		'costo_total' => 0, 'observaciones' => 'Prueba', 'nombre_insumo_elaborado' => 'MASA PRUEBA ' . $sufijo,
		'insumos' => array(array('id_insumo' => $harina, 'id_bodega' => $bodega, 'cantidad' => $gramos, 'nombre_insumo' => 'HARINA'))))));
};
list($c, $b) = $prod(3000);
revisar('3000 gr (3 kg) con 2 kg de stock: no alcanza y lo dice en kilos', empty(json($b)['success']) && strpos($b, 'Requerido: 3') !== false, substr($b, 0, 200));
list($c, $b) = $prod(500);
revisar('producir con 500 gr de harina', !empty(json($b)['success']), substr($b, 0, 300));
revisar('cuesta $5.000 (medio kilo), no $5.000.000', abs((float)(json($b)['costo_total'] ?? 0) - 5000) < 0.01, substr($b, 0, 200));
list(, $b) = pedir(BASE . '/Produccion/obtenerStockActual', array('id_insumo' => $harina, 'id_bodega' => $bodega));
revisar('quedan 1,5 kg de harina (se descontó medio kilo, no 500 kilos)', abs((float)(json($b)['stock_actual'] ?? 0) - 1.5) < 0.001, substr($b, 0, 200));
$m = insumo($masa);
revisar('la masa queda como sub receta (SI) con costo $5.000 el kilo', $m && $m['esreceta'] === 't' && abs((float)$m['costo_neto_ppp'] - 5000) < 0.01,
	json_encode($m ? array($m['esreceta'], $m['costo_neto_ppp']) : null));

echo "Receta de producción:\n";
list(, $b) = pedir(BASE . '/Produccion/recetaParaProducir', array('id_insumo' => $masa));
revisar('sin receta de producción responde vacío', array_key_exists('receta', (array)json($b)) && json($b)['receta'] === null, substr($b, 0, 200));
list($c, $b) = pedir(BASE . '/RecetaProduccion/grabarRecetaProduccion', array('receta' => array('id_receta_produccion' => 0,
	'nombre_receta_produccion' => 'MASA ' . $sufijo, 'id_insumo' => $masa, 'id_bodega' => $bodega, 'cantidad' => 2, 'id_umedida' => $id['kg'], 'vigente' => 'true')));
$idRp = (int)(json($b)[0]['id_receta_produccion'] ?? (json($b)['id_receta_produccion'] ?? 0));
revisar('se crea la receta de producción de la masa (rinde 2 kg)', $idRp > 0, substr($b, 0, 200));
if ($idRp) {
	pedir(BASE . '/RecetaProduccion/grabarDetalleReceta', array('id_receta_produccion' => $idRp, 'id_insumo' => $harina,
		'cantidad' => 1.2, 'id_umedida' => $id['kg']));
	list(, $b) = pedir(BASE . '/Produccion/recetaParaProducir', array('id_insumo' => $masa));
	$rp = json($b)['receta'] ?? null;
	$l = $rp['lineas'][0] ?? null;
	revisar('al producir se carga con su insumo y las unidades para convertir', $rp && (float)$rp['cantidad'] === 2.0 && $l && (int)$l['id_insumo'] === $harina
		&& (int)$l['id_umedida'] === $id['kg'] && (float)$l['m_receta'] === 1000.0, substr($b, 0, 400));
}
terminar('recetas y unidades');
