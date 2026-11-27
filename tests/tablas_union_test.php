<?php
/**
 * Prueba de la v1.41.0 (pedido del usuario: "una tabla intermedia para llevar todo ordenado", migración 20261126120000):
 *  - agregados (v1.42.0, "como el menú, con sus propias tablas"): con VARIOS, el producto queda sin id_agregado y marcado
 *    es_con_agregados_web, cada agregado (también el 1.º) en su fila marcada es_agregado_web y todos unidos en detalle_agregado_web;
 *    con UNO solo no cambia nada (id_agregado);
 *  - promos normales: sus productos están en promo_producto_web (ya no en el menú) y cada promo vendida anota en
 *    detalle_promo_web qué productos lleva;
 *  - las filas no usan la columna id_detalle_menu_web, la mesa los sigue mostrando unidos, al mover / anular una parte se
 *    copian las uniones y al cobrar (mesa o venta directa) se anotan también para la venta cobrada.
 *
 * Revisa la base directo con psql: PRUEBA_PSQL = comando que recibe el SQL por -c, por ejemplo
 *   PRUEBA_PSQL="docker exec pg10prueba psql -U postgres -d DataRestoWeb" php tests/tablas_union_test.php http://localhost:8002/index.php COPIA
 * ¡CREA PROMOS Y AGREGADOS, ENVÍA PEDIDOS Y COBRA! Solo con una COPIA de la base.
 */
require __DIR__ . '/lib_pruebas.php';
if (($argv[2] ?? '') !== 'COPIA') {
	echo "Esta prueba crea promos, envía pedidos y cobra. Córrela solo contra una COPIA de la base:\n"
		. "  PRUEBA_PSQL=\"docker exec pg10prueba psql -U postgres -d DataRestoWeb\" php tests/tablas_union_test.php http://localhost:8002/index.php COPIA\n";
	exit(1);
}
$psql = getenv('PRUEBA_PSQL') ?: '';
if ($psql === '') { echo "(Sin PRUEBA_PSQL no se puede revisar la base: no se corrió la prueba de tablas de unión)\nOK: 0 pruebas de tablas de unión (omitida)\n"; exit(0); }
/** Una consulta: filas como arreglos de columnas (separadas por |) */
function sql($q)
{
	$out = shell_exec($GLOBALS['psql'] . ' -At -c ' . escapeshellarg($q) . ' 2>&1');
	$filas = array();
	foreach (preg_split('/\R/', trim((string)$out)) as $l) if ($l !== '') $filas[] = explode('|', $l);
	return $filas;
}
function uno($q) { $f = sql($q); return $f ? $f[0][0] : null; }
entrar();
pedir(BASE . '/Login/postLoginWarmup', array());
function postJson($url, $datos)
{
	$ch = curl_init($url);
	curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $GLOBALS['__cookies'], CURLOPT_COOKIEFILE => $GLOBALS['__cookies'],
		CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($datos), CURLOPT_TIMEOUT => 60,
		CURLOPT_HTTPHEADER => array('Content-Type: application/json', 'X-Requested-With: XMLHttpRequest', 'X-CSRF-Token: ' . $GLOBALS['__token'])));
	$b = (string)curl_exec($ch);
	$c = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	return array($c, json($b), $b);
}
$aut = getenv('PRUEBA_CLAVE_ADMIN') ?: '';

echo "Base:\n";
revisar('existen las tablas promo_producto_web, detalle_promo_web y detalle_agregado_web',
	(int)uno("SELECT count(*) FROM information_schema.tables WHERE table_name IN ('promo_producto_web','detalle_promo_web','detalle_agregado_web')") === 3);
revisar('ninguna promo sigue siendo menú', (int)uno("SELECT count(*) FROM producto WHERE id_producto IN (SELECT id_producto FROM promo_web) AND (es_menu IS TRUE OR COALESCE(id_menu,0) > 0)") === 0);

echo "Preparar:\n";
list(, $b) = pedir(BASE . '/Caja/catalogo?id_lprecio=1');
$cat = json($b);
$esp = array();
foreach (array_merge($cat['menus'] ?? array(), $cat['sushis'] ?? array()) as $x) $esp[(int)$x['id_producto']] = true;
$simples = array();
foreach ($cat['productos'] ?? array() as $p) {
	if ($p['precio'] > 0 && empty($p['fraccion']) && empty($p['pedir_cantidad']) && empty($p['exento']) && !isset($esp[(int)$p['id_producto']])) $simples[] = $p;
}
revisar('hay 5 productos simples para probar', count($simples) >= 5);
if (count($simples) < 5) terminar('tablas de unión');
$prin = $simples[0]; $idPrin = (int)$prin['id_producto'];
$extras = array_slice($simples, 1, 3);
foreach ($extras as $k => $e) {
	list(, $b) = pedir(BASE . '/Agregados/ListadoDeAgregados', array());
	$idEx = 0;
	foreach ((json($b)['aaData'] ?? array()) as $a) if ((int)$a['id_producto_maestro'] === $idPrin && (int)$a['id_producto'] === (int)$e['id_producto']) $idEx = (int)$a['id_agregado'];
	pedir(BASE . '/Agregados/grabarNuevoAgregado', array('prod' => array('id_agregado' => $idEx, 'id_producto' => (int)$e['id_producto'],
		'cantidad' => 1, 'vigente' => 'true', 'id_producto_maestro' => $idPrin, 'valor' => 0, 'usar_destino_impresion' => 'false')));
}
pedir(BASE . '/Productos/opcionesAgregados', array('id_producto' => $idPrin, 'accion' => 'varios', 'valor' => 1));   // v1.44.0: Con Varios Agregados
pedir(BASE . '/Productos/maxAgregados', array('id_producto' => $idPrin, 'maximo' => -1));   // v1.43.0: sin límite (0 es 0)
list(, $b) = pedir(BASE . '/Productos/promos');
$fam = (int)((json($b)['familias'][0]['id_familia']) ?? 0);
$suf = date('His') . mt_rand(10, 99);
$a = $simples[3]; $bb = $simples[4];
list($c, $b) = pedir(BASE . '/Productos/grabarPromo', array('id_producto' => 0, 'nombre' => 'UNION FIJA ' . $suf, 'precio' => 3000, 'id_familia' => $fam, 'vigente' => 1, 'elegir' => 0,
	'items' => array(array('id_producto' => $a['id_producto'], 'cantidad' => 1), array('id_producto' => $bb['id_producto'], 'cantidad' => 2))));
$idFija = (int)(json($b)['id_producto'] ?? 0);
list($c2, $b2) = pedir(BASE . '/Productos/grabarPromo', array('id_producto' => 0, 'nombre' => 'UNION ELIGE ' . $suf, 'precio' => 4000, 'id_familia' => $fam, 'vigente' => 1, 'elegir' => 2,
	'items' => array(array('id_producto' => $a['id_producto'], 'cantidad' => 1), array('id_producto' => $bb['id_producto'], 'cantidad' => 1),
		array('id_producto' => $extras[0]['id_producto'], 'cantidad' => 1))));
$idElige = (int)(json($b2)['id_producto'] ?? 0);
revisar('crear una promo fija y una "elige 2"', $c === 200 && $c2 === 200 && $idFija && $idElige, substr($b . $b2, 0, 300));
if (!$idFija || !$idElige) terminar('tablas de unión');
revisar('sus productos quedan en promo_producto_web (2 y 3), no en el menú',
	(int)uno("SELECT count(*) FROM promo_producto_web WHERE vigente AND id_producto_promo = $idFija") === 2
	&& (int)uno("SELECT count(*) FROM promo_producto_web WHERE vigente AND id_producto_promo = $idElige") === 3
	&& (int)uno("SELECT COALESCE(id_menu,0) FROM producto WHERE id_producto = $idFija") === 0);

list(, $b) = pedir(BASE . '/Caja/catalogo?id_lprecio=1');
$cat = json($b);
$ags = array();
foreach ($cat['agregados'] ?? array() as $x) foreach ($extras as $k => $e) if ((int)$x['id_producto_maestro'] === $idPrin && $x['nombre'] === $e['nombre']) $ags[$k] = (int)$x['id_agregado'];
ksort($ags);
$mFija = null; $mElige = null;
foreach ($cat['menus'] ?? array() as $m) {
	if ((int)$m['id_producto'] === $idFija) $mFija = $m;
	if ((int)$m['id_producto'] === $idElige) $mElige = $m;
}
revisar('la Caja ofrece las promos desde sus tablas (fija: 2 categorías fijas; elige: 1 categoría de 3, elige 2)',
	$mFija && count($mFija['categorias']) === 2 && $mElige && count($mElige['categorias']) === 1 && $mElige['categorias'][0]['min'] === 2
	&& count($mElige['categorias'][0]['opciones']) === 3, json_encode(array($mFija, $mElige)));
if (count($ags) < 2 || !$mFija || !$mElige) terminar('tablas de unión');

echo "Mesa:\n";
list(, $b) = pedir(BASE . '/Caja/estado');
if (strpos($b, '"caja":null') !== false) pedir(BASE . '/Caja/abrir', array('fondo' => '0'));
list(, $b) = pedir(BASE . '/Caja/mesas');
$mesa = 0;
foreach ((json($b)['mesas'] ?? array()) as $m) if ($m['estado'] === 'libre' && empty($m['con_pedido']) && empty($m['reserva'])) { $mesa = (int)$m['id_mesa']; break; }
revisar('hay una mesa libre', $mesa > 0);
if (!$mesa) terminar('tablas de unión');
$selFija = array_map(function ($k) { return array('id' => $k['opciones'][0]['id'], 'cantidad' => $k['min']); }, $mFija['categorias']);
$op = $mElige['categorias'][0]['opciones'];
$selElige = array(array('id' => $op[0]['id'], 'cantidad' => 1, 'nota' => 'sin cebolla'), array('id' => $op[1]['id'], 'cantidad' => 1));
list($c, $r, $b) = postJson(BASE . '/Caja/enviarMesa', array('id_mesa' => $mesa, 'id_lprecio' => 1, 'observacion' => '', 'detalle' => array(
	array('id_producto' => $idPrin, 'cantidad' => 2, 'agregados' => array($ags[0], $ags[1]), 'notas_agregados' => array($ags[1] => 'PICANTE'), 'nota' => '', 'menu' => array(), 'sushi' => null),
	array('id_producto' => $idPrin, 'cantidad' => 1, 'agregados' => array($ags[2]), 'notas_agregados' => new stdClass(), 'nota' => '', 'menu' => array(), 'sushi' => null),
	array('id_producto' => $idFija, 'cantidad' => 1, 'agregados' => array(), 'nota' => '', 'menu' => $selFija, 'sushi' => null),
	array('id_producto' => $idElige, 'cantidad' => 1, 'agregados' => array(), 'nota' => '', 'menu' => $selElige, 'sushi' => null))));
revisar('enviar a la mesa: producto con 2 agregados, otro con 1, promo fija y promo elige', $c === 200, "código $c: " . substr($b, 0, 300));
$cot = (int)uno("SELECT id_cotizacion FROM mesa WHERE id_mesa = $mesa");
$filas = function () use ($cot) {
	$r = array();
	foreach (sql("SELECT id_detalle_cotizacion, id_producto, cantidad, COALESCE(id_agregado,0), COALESCE(id_detalle_menu_web,0),
			(es_con_agregados_web IS TRUE)::int, (es_agregado_web IS TRUE)::int FROM detalle_cotizacion
		WHERE id_cotizacion = $cot AND vigente IS NOT FALSE ORDER BY id_detalle_cotizacion") as $f) $r[] = array('id' => (int)$f[0], 'prod' => (int)$f[1], 'cant' => (float)$f[2], 'ag' => (int)$f[3], 'col' => (int)$f[4],
			'con' => (int)$f[5], 'es_ag' => (int)$f[6]);
	return $r;
};
$fs = $filas();
$padre2 = null; $padre1 = null; $hijoAg = null; $hijo0 = null;
foreach ($fs as $f) {
	if ($f['prod'] === $idPrin && $f['ag'] === 0) $padre2 = $f;
	if ($f['prod'] === $idPrin && $f['ag'] === (int)$extras[2]['id_producto']) $padre1 = $f;
	if ($f['prod'] === (int)$extras[0]['id_producto']) $hijo0 = $f;
	if ($f['prod'] === (int)$extras[1]['id_producto']) $hijoAg = $f;
}
revisar('1 agregado: como antes, en id_agregado de la fila del producto', $padre1 && $padre1['con'] === 0);
revisar('varios agregados: el producto SIN id_agregado y marcado es_con_agregados_web; cada agregado (también el 1.º) en su fila marcada es_agregado_web',
	$padre2 && $padre2['con'] === 1 && $hijo0 && $hijo0['es_ag'] === 1 && $hijoAg && $hijoAg['es_ag'] === 1, json_encode($fs));
if (!$padre2 || !$hijoAg || !$padre1 || !$hijo0) terminar('tablas de unión');
$ag = sql("SELECT id_detalle_cotizacion, COALESCE(id_detalle_cotizacion_agregado,0), id_producto, orden, COALESCE(mensaje,'') FROM detalle_agregado_web
	WHERE id_detalle_cotizacion IN ({$padre2['id']}, {$padre1['id']}) ORDER BY orden");
revisar('detalle_agregado_web: los 2 agregados unidos al producto, cada uno con su fila, su orden y su mensaje', count($ag) === 2
	&& (int)$ag[0][0] === $padre2['id'] && (int)$ag[0][1] === $hijo0['id'] && (int)$ag[0][3] === 1
	&& (int)$ag[1][0] === $padre2['id'] && (int)$ag[1][1] === $hijoAg['id'] && (int)$ag[1][3] === 2 && $ag[1][4] === 'PICANTE', json_encode($ag));
revisar('con 1 solo agregado no se anota nada (queda como antes)', (int)uno("SELECT count(*) FROM detalle_agregado_web WHERE id_detalle_cotizacion = {$padre1['id']}") === 0);
revisar('los agregados no usan las columnas del menú (id_detalle_menu_web)', $hijoAg['col'] === 0 && $hijo0['col'] === 0);
$promoFila = null; $promoElige = null;
foreach ($fs as $f) { if ($f['prod'] === $idFija) $promoFila = $f; if ($f['prod'] === $idElige) $promoElige = $f; }
$pr = sql("SELECT id_detalle_cotizacion, id_detalle_cotizacion_producto, id_producto, cantidad, COALESCE(mensaje,'') FROM detalle_promo_web
	WHERE id_detalle_cotizacion IN (" . (int)$promoFila['id'] . ", " . (int)$promoElige['id'] . ") ORDER BY id_detalle_promo_web");
$deFija = array_values(array_filter($pr, function ($x) use ($promoFila) { return (int)$x[0] === $promoFila['id']; }));
$deElige = array_values(array_filter($pr, function ($x) use ($promoElige) { return (int)$x[0] === $promoElige['id']; }));
revisar('detalle_promo_web: la promo fija con sus 2 productos (1 y 2) y la "elige" con los 2 elegidos y el comentario',
	count($deFija) === 2 && (float)$deFija[1][3] === 2.0 && count($deElige) === 2 && $deElige[0][4] === 'sin cebolla', json_encode($pr));
$colPromo = (int)uno("SELECT count(*) FROM detalle_cotizacion WHERE id_detalle_cotizacion IN (SELECT id_detalle_cotizacion_producto FROM detalle_promo_web) AND id_cotizacion = $cot AND COALESCE(id_detalle_menu_web,0) > 0");
revisar('los productos de la promo ya no usan la columna id_detalle_menu_web', $colPromo === 0);
// v1.45.0: la promo con sus propias marcas, no las del menú
$mc = uno("SELECT sum((es_promo_web IS TRUE)::int) || ',' || sum((es_detalle_promo_web IS TRUE)::int) || ',' || sum((es_menu IS TRUE OR es_detalle_menu IS TRUE)::int)
	FROM detalle_cotizacion WHERE id_cotizacion = $cot AND (id_producto IN ($idFija, $idElige) OR id_detalle_cotizacion IN (SELECT id_detalle_cotizacion_producto FROM detalle_promo_web))");
revisar('en el pedido: 2 filas es_promo_web, 4 es_detalle_promo_web y ninguna con es_menu / es_detalle_menu (el menú queda solo para el menú)', $mc === '2,4,0', "marcas $mc");

list(, $b) = pedir(BASE . '/Caja/detalleMesa?id_mesa=' . $mesa);
$porId = array();
foreach (json($b)['lineas'] ?? array() as $l) $porId[(int)$l['id_detalle']] = $l;
revisar('la mesa muestra los 2 agregados unidos a su producto (leído de la tabla)', (int)($porId[$hijoAg['id']]['menu_de'] ?? 0) === $padre2['id']
	&& (int)($porId[$hijo0['id']]['menu_de'] ?? 0) === $padre2['id']);
$okPromo = true;
foreach ($pr as $x) $okPromo = $okPromo && (int)($porId[(int)$x[1]]['menu_de'] ?? 0) === (int)$x[0] && !empty($porId[(int)$x[1]]['es_detalle_menu']);
revisar('y los productos de cada promo unidos a su promo', $okPromo && !empty($porId[$promoFila['id']]['es_menu']));

// Anular 1 de los 2 productos con 2 agregados: la parte anulada es una copia; la que queda sigue unida
list($c, , $b) = postJson(BASE . '/Caja/anularLineaMesa', array('id_mesa' => $mesa, 'id_detalle' => $padre2['id'], 'cantidad' => 1, 'motivo' => 'Prueba automática', 'autorizacion' => $aut));
if ($c === 403 || ($c >= 400 && stripos($b, 'clave') !== false)) {
	echo "  (la empresa pide clave de administrador para anular: no se probó la copia. Dala con PRUEBA_CLAVE_ADMIN=...)\n";
} else {
	revisar('anular 1 de los 2 productos con agregados', $c === 200, "código $c: " . substr($b, 0, 200));
	list(, $b) = pedir(BASE . '/Caja/detalleMesa?id_mesa=' . $mesa);
	$pa = null; $hi = null;
	foreach (json($b)['lineas'] ?? array() as $l) {
		if ((int)$l['id_producto'] === $idPrin && (int)$l['id_agregado'] === 0) $pa = $l;
		if ((int)$l['id_producto'] === (int)$extras[1]['id_producto']) $hi = $l;
	}
	revisar('queda 1 producto con su agregado unido (cantidad 1)', $pa && $hi && (float)$pa['cantidad'] === 1.0 && (float)$hi['cantidad'] === 1.0 && (int)$hi['menu_de'] === (int)$pa['id_detalle'],
		json_encode(array($pa, $hi)));
	$copias = (int)uno("SELECT count(*) FROM detalle_agregado_web d JOIN detalle_cotizacion c ON c.id_detalle_cotizacion = d.id_detalle_cotizacion_agregado
		WHERE c.id_cotizacion = $cot AND c.esanulado IS TRUE");
	revisar('la parte anulada (copia) también quedó anotada en la tabla (sus 2 agregados)', $copias === 2, "copias: $copias");
}

echo "Cobrar la mesa:\n";
$efectivo = null; $doc = null;
foreach ($cat['formas_pago'] ?? array() as $f) if (!empty($f['efectivo'])) { $efectivo = $f; break; }
foreach ($cat['documentos'] ?? array() as $dd) if (empty($dd['electronico']) && empty($dd['requiere_cliente']) && !empty($dd['habilitado'])) { $doc = $dd; break; }
$totalMesa = (int)uno("SELECT COALESCE(sum(total_bruto),0) FROM detalle_cotizacion WHERE id_cotizacion = $cot AND vigente IS NOT FALSE AND esanulado IS NOT TRUE AND eseliminado IS NOT TRUE");
list($c, $r, $b) = postJson(BASE . '/Caja/vender', array('id_mesa' => $mesa, 'parcial' => null, 'id_tdocumento' => (int)$doc['id_tdocumento'], 'id_lprecio' => 1,
	'id_cliente' => 0, 'descuento' => 0, 'propina' => 0, 'observacion' => 'PRUEBA AUTOMATICA', 'retira' => '', 'delivery' => null, 'id_delivery' => 0, 'factura' => null,
	'detalle' => array(), 'pagos' => array(array('id_fpago' => (int)$efectivo['id_fpago'], 'monto' => $totalMesa)), 'token' => bin2hex(random_bytes(16))));
revisar('cobrar la mesa completa', $c === 200 && !empty($r['id_venta']), "código $c: " . substr($b, 0, 300));
$idVenta = (int)($r['id_venta'] ?? 0);
$agV = (int)uno("SELECT count(*) FROM detalle_agregado_web WHERE id_venta = $idVenta");
$prV = (int)uno("SELECT count(*) FROM detalle_promo_web WHERE id_venta = $idVenta");
$okV = (int)uno("SELECT count(*) FROM detalle_agregado_web a JOIN detalle_venta dv ON dv.id_detalle_venta = a.id_detalle_venta_agregado WHERE a.id_venta = $idVenta AND dv.id_venta = $idVenta");
$flagsV = uno("SELECT (sum((es_con_agregados_web IS TRUE)::int))::text || ',' || (sum((es_agregado_web IS TRUE)::int))::text FROM detalle_venta WHERE id_venta = $idVenta");
revisar('la venta cobrada también queda anotada: 2 agregados y 4 productos de promos, apuntando a sus filas de detalle_venta, con sus marcas',
	$agV === 2 && $prV === 4 && $okV === 2 && $flagsV === '1,2', "agregados $agV, promos $prV, unidos $okV, marcas $flagsV");
$mv = uno("SELECT sum((es_promo_web IS TRUE)::int) || ',' || sum((es_detalle_promo_web IS TRUE)::int) || ',' || sum((es_menu IS TRUE OR es_detalle_menu IS TRUE)::int) FROM detalle_venta WHERE id_venta = $idVenta");
revisar('en la venta cobrada: 2 es_promo_web, 4 es_detalle_promo_web y nada con las marcas del menú', $mv === '2,4,0', "marcas $mv");

echo "Venta directa:\n";
list($c, $r, $b) = postJson(BASE . '/Caja/vender', array('id_mesa' => 0, 'parcial' => null, 'id_tdocumento' => (int)$doc['id_tdocumento'], 'id_lprecio' => 1,
	'id_cliente' => 0, 'descuento' => 0, 'propina' => 0, 'observacion' => 'PRUEBA AUTOMATICA', 'retira' => '', 'delivery' => null, 'id_delivery' => 0, 'factura' => null,
	'detalle' => array(
		array('id_producto' => $idPrin, 'cantidad' => 1, 'agregados' => array($ags[0], $ags[1], $ags[2]), 'notas_agregados' => new stdClass(), 'nota' => '', 'menu' => array(), 'sushi' => null),
		array('id_producto' => $idElige, 'cantidad' => 1, 'agregados' => array(), 'nota' => '', 'menu' => array(array('id' => $op[2]['id'], 'cantidad' => 2)), 'sushi' => null)),
	'pagos' => array(array('id_fpago' => (int)$efectivo['id_fpago'], 'monto' => (int)$prin['precio'] + 4000 + 10000)), 'token' => bin2hex(random_bytes(16))));
revisar('venta directa con 3 agregados y una promo "elige" (2 del mismo)', $c === 200 && !empty($r['id_venta']), "código $c: " . substr($b, 0, 300));
$idV2 = (int)($r['id_venta'] ?? 0);
$bol = pedir(BASE . '/Caja/ticket/' . $idV2, null, false)[1];
revisar('en la boleta los agregados salen debajo del producto con "+"', mb_substr_count(html_entity_decode(strip_tags($bol)), '+ ') >= 3);
revisar('quedan anotados 3 agregados (orden 1, 2 y 3) y 1 producto de la promo con cantidad 2',
	uno("SELECT string_agg(orden::text, ',' ORDER BY orden) FROM detalle_agregado_web WHERE id_venta = $idV2") === '1,2,3'
	&& uno("SELECT string_agg(cantidad::int::text, ',') FROM detalle_promo_web WHERE id_venta = $idV2") === '2');
$mv2 = uno("SELECT sum((es_promo_web IS TRUE)::int) || ',' || sum((es_detalle_promo_web IS TRUE)::int) || ',' || sum((es_menu IS TRUE OR es_detalle_menu IS TRUE)::int) FROM detalle_venta WHERE id_venta = $idV2");
revisar('venta directa: la promo con es_promo_web / es_detalle_promo_web, sin las marcas del menú', $mv2 === '1,1,0', "marcas $mv2");
revisar('en la boleta el producto de la promo sale debajo con "+"', mb_strpos(html_entity_decode(strip_tags($bol)), '+ ' . $op[2]['nombre']) !== false || mb_substr_count(html_entity_decode(strip_tags($bol)), '+ ') >= 4);

pedir(BASE . '/Productos/grabarPromo', array('id_producto' => $idFija, 'nombre' => 'UNION FIJA ' . $suf, 'precio' => 3000, 'id_familia' => $fam, 'vigente' => 0, 'elegir' => 0,
	'items' => array(array('id_producto' => $a['id_producto'], 'cantidad' => 1))));
pedir(BASE . '/Productos/grabarPromo', array('id_producto' => $idElige, 'nombre' => 'UNION ELIGE ' . $suf, 'precio' => 4000, 'id_familia' => $fam, 'vigente' => 0, 'elegir' => 2,
	'items' => array(array('id_producto' => $a['id_producto'], 'cantidad' => 1))));
terminar('tablas de unión');
