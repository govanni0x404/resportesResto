<?php
/**
 * Prueba de la v1.36.0 (pedido del usuario): promos normales (Carta > Promos), ej. "Completo + Bebida a $X".
 *  - se crean y editan con productos fijos y su cantidad; validaciones;
 *  - la Caja las recibe como un menú con todo fijo y la venta lleva el precio de la promo y sus productos en $0;
 *  - Configuraciones > "Usar promos de sushi por roll" muestra u oculta el botón Promos Sushi; Promos sale siempre.
 *
 * ¡CREA PRODUCTOS Y ENVÍA PEDIDOS! Solo con una COPIA de la base:
 *   php tests/promos_test.php http://localhost:8002/index.php COPIA
 */
require __DIR__ . '/lib_pruebas.php';
if (($argv[2] ?? '') !== 'COPIA') {
	echo "Esta prueba crea promos y envía pedidos. Córrela solo contra una COPIA de la base:\n"
		. "  php tests/promos_test.php http://localhost:8002/index.php COPIA\n";
	exit(1);
}
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
$err = function ($b) { return json($b)['error'] ?? ''; };

echo "Preparar:\n";
list(, $b) = pedir(BASE . '/Productos/promos');
$d = json($b);
revisar('la lista de promos responde', !empty($d['disponible']), substr($b, 0, 200));
$prods = array_values(array_filter($d['productos'] ?? array(), function ($p) { return $p['precio'] > 0; }));
revisar('hay 2 productos con precio para la promo', count($prods) >= 2);
if (count($prods) < 2 || empty($d['familias'])) terminar('promos');
$a = $prods[0]; $bb = $prods[1];
$fam = (int)(is_array($d['familias'][0]) ? $d['familias'][0]['id_familia'] : $d['familias'][0]->id_familia);
$nombre = 'Promo prueba ' . date('His') . mt_rand(10, 99);

echo "Crear:\n";
list($c, $b) = pedir(BASE . '/Productos/grabarPromo', array('id_producto' => 0, 'nombre' => $nombre, 'precio' => 3990, 'id_familia' => $fam, 'vigente' => 1, 'items' => array()));
revisar('sin productos se rechaza', $c === 400 && strpos($err($b), 'al menos un producto') !== false, "código $c: " . substr($b, 0, 150));
list($c, $b) = pedir(BASE . '/Productos/grabarPromo', array('id_producto' => 0, 'nombre' => $nombre, 'precio' => 0, 'id_familia' => $fam, 'vigente' => 1,
	'items' => array(array('id_producto' => $a['id_producto'], 'cantidad' => 1))));
revisar('precio 0 se rechaza', $c === 400, "código $c");
list($c, $b) = pedir(BASE . '/Productos/grabarPromo', array('id_producto' => 0, 'nombre' => $nombre, 'precio' => 3990, 'id_familia' => $fam, 'vigente' => 1,
	'items' => array(array('id_producto' => $a['id_producto'], 'cantidad' => 1), array('id_producto' => $bb['id_producto'], 'cantidad' => 2))));
$idPromo = (int)(json($b)['id_producto'] ?? 0);
revisar('crear la promo con 1 + 2 productos', $c === 200 && $idPromo > 0, "código $c: " . substr($b, 0, 200));
if (!$idPromo) terminar('promos');
list(, $b) = pedir(BASE . '/Productos/promos?id=' . $idPromo);
$p = json($b)['promo'] ?? array();
revisar('al abrirla trae nombre en mayúsculas, precio y sus productos', ($p['nombre'] ?? '') === mb_strtoupper($nombre) && ($p['precio'] ?? 0) === 3990
	&& count($p['items'] ?? array()) === 2 && ($p['items'][1]['cantidad'] ?? 0) === 2, substr($b, 0, 300));
list($c, $b) = pedir(BASE . '/Productos/grabarPromo', array('id_producto' => $idPromo, 'nombre' => $nombre, 'precio' => 3990, 'id_familia' => $fam, 'vigente' => 1,
	'items' => array(array('id_producto' => $idPromo, 'cantidad' => 1))));
revisar('la promo no puede llevarse a sí misma', $c === 400, "código $c");

echo "Caja:\n";
list(, $b) = pedir(BASE . '/Caja/catalogo?id_lprecio=1');
$cat = json($b);
$menu = null; $prodCat = null;
foreach ($cat['menus'] ?? array() as $m) if ((int)$m['id_producto'] === $idPromo) $menu = $m;
foreach ($cat['productos'] ?? array() as $x) if ((int)$x['id_producto'] === $idPromo) $prodCat = $x;
revisar('la Caja ofrece la promo con su precio', $prodCat && $prodCat['precio'] === 3990);
$fijo = $menu && count($menu['categorias']) === 2;
foreach ($menu['categorias'] ?? array() as $k) $fijo = $fijo && count($k['opciones']) === 1 && $k['min'] === $k['max'] && $k['min'] >= 1;
revisar('y como un menú con todo fijo (se agrega sin preguntar)', $fijo, json_encode($menu));
list(, $b) = pedir(BASE . '/Caja/estado');
if (strpos($b, '"caja":null') !== false) pedir(BASE . '/Caja/abrir', array('fondo' => '0'));
list(, $b) = pedir(BASE . '/Caja/mesas');
$mesa = 0;
foreach ((json($b)['mesas'] ?? array()) as $m) if ($m['estado'] === 'libre' && empty($m['con_pedido']) && empty($m['reserva'])) { $mesa = (int)$m['id_mesa']; break; }
revisar('hay una mesa libre', $mesa > 0);
if ($mesa && $menu) {
	$sel = array_map(function ($k) { return array('id' => $k['opciones'][0]['id'], 'cantidad' => $k['min']); }, $menu['categorias']);
	list($c, , $b) = postJson(BASE . '/Caja/enviarMesa', array('id_mesa' => $mesa, 'id_lprecio' => 1, 'observacion' => '', 'detalle' => array(array(
		'id_producto' => $idPromo, 'cantidad' => 1, 'agregados' => array(), 'nota' => '', 'menu' => $sel, 'sushi' => null))));
	revisar('enviar la promo a la mesa', $c === 200, "código $c: " . substr($b, 0, 200));
	list(, $b) = pedir(BASE . '/Caja/detalleMesa?id_mesa=' . $mesa);
	$l = array();
	foreach (json($b)['lineas'] ?? array() as $x) $l[(int)$x['id_producto']] = array((float)$x['cantidad'], (int)$x['unitario']);
	revisar('la promo lleva su precio', ($l[$idPromo][1] ?? 0) === 3990, json_encode($l));
	revisar('y sus productos en $0 con su cantidad (1 y 2)', ($l[(int)$a['id_producto']] ?? null) === array(1.0, 0) && ($l[(int)$bb['id_producto']] ?? null) === array(2.0, 0), json_encode($l));
	postJson(BASE . '/Caja/anularPedidoMesa', array('id_mesa' => $mesa, 'motivo' => 'Prueba automática', 'autorizacion' => $aut));
}

echo "Editar:\n";
list($c, $b) = pedir(BASE . '/Productos/grabarPromo', array('id_producto' => $idPromo, 'nombre' => $nombre . ' b', 'precio' => 2990, 'id_familia' => $fam, 'vigente' => 0,
	'items' => array(array('id_producto' => $bb['id_producto'], 'cantidad' => 3))));
revisar('editar: cambiar precio, dejarla inactiva y con un solo producto', $c === 200, "código $c: " . substr($b, 0, 200));
list(, $b) = pedir(BASE . '/Productos/promos?id=' . $idPromo);
$p = json($b)['promo'] ?? array();
revisar('al abrirla trae solo ese producto (x3) y el precio nuevo', count($p['items'] ?? array()) === 1 && ($p['items'][0]['cantidad'] ?? 0) === 3
	&& ($p['precio'] ?? 0) === 2990 && ($p['vigente'] ?? null) === false, substr($b, 0, 300));
list(, $b) = pedir(BASE . '/Caja/catalogo?id_lprecio=1');
$esta = false;
foreach (json($b)['productos'] ?? array() as $x) if ((int)$x['id_producto'] === $idPromo) $esta = true;
revisar('inactiva: la Caja ya no la ofrece', !$esta);

echo "Promo a elegir (v1.39.5):\n";
$cc = $prods[2] ?? $prods[0];
$itemsElegir = array(array('id_producto' => $a['id_producto'], 'cantidad' => 1), array('id_producto' => $bb['id_producto'], 'cantidad' => 1),
	array('id_producto' => $cc['id_producto'], 'cantidad' => 1));
list($c, $b) = pedir(BASE . '/Productos/grabarPromo', array('id_producto' => 0, 'nombre' => $nombre . ' E', 'precio' => 5000, 'id_familia' => $fam, 'vigente' => 1,
	'elegir' => 0, 'items' => $itemsElegir));
$idE = (int)(json($b)['id_producto'] ?? 0);
revisar('crear como fija con 3 productos', $c === 200 && $idE > 0, "código $c: " . substr($b, 0, 200));
list($c, $b) = pedir(BASE . '/Productos/grabarPromo', array('id_producto' => $idE, 'nombre' => $nombre . ' E', 'precio' => 5000, 'id_familia' => $fam, 'vigente' => 1,
	'elegir' => 51, 'items' => $itemsElegir));
revisar('elegir 51 se rechaza', $c === 400, "código $c");
list($c, $b) = pedir(BASE . '/Productos/grabarPromo', array('id_producto' => $idE, 'nombre' => $nombre . ' E', 'precio' => 5000, 'id_familia' => $fam, 'vigente' => 1,
	'elegir' => 2, 'items' => $itemsElegir));
revisar('cambiarla a "el cliente elige 2"', $c === 200, "código $c: " . substr($b, 0, 200));
list(, $b) = pedir(BASE . '/Productos/promos?id=' . $idE);
$p = json($b)['promo'] ?? array();
revisar('al abrirla dice elegir 2 y trae los 3 productos', ($p['elegir'] ?? 0) === 2 && count($p['items'] ?? array()) === count(array_unique(array_column($itemsElegir, 'id_producto'))), substr($b, 0, 300));
list(, $b) = pedir(BASE . '/Productos/promos');
$det = '';
foreach (json($b)['promos'] ?? array() as $x) if ((int)$x['id_producto'] === $idE) $det = $x['detalle'];
revisar('en la lista dice "Elige 2: ..."', strpos($det, 'Elige 2:') === 0, $det);
list(, $b) = pedir(BASE . '/Caja/catalogo?id_lprecio=1');
$menuE = null;
foreach (json($b)['menus'] ?? array() as $m) if ((int)$m['id_producto'] === $idE) $menuE = $m;
$k0 = $menuE['categorias'][0] ?? array();
revisar('la Caja la ofrece como menú: 1 categoría, elige 2 de los productos', $menuE && count($menuE['categorias']) === 1 && ($k0['min'] ?? 0) === 2 && ($k0['max'] ?? 0) === 2
	&& count($k0['opciones'] ?? array()) === count(array_unique(array_column($itemsElegir, 'id_producto'))), json_encode($menuE));
list(, $b) = pedir(BASE . '/Caja/mesas');
$mesa = 0;
foreach ((json($b)['mesas'] ?? array()) as $m) if ($m['estado'] === 'libre' && empty($m['con_pedido']) && empty($m['reserva'])) { $mesa = (int)$m['id_mesa']; break; }
if ($mesa && $menuE) {
	list($c, , $b) = postJson(BASE . '/Caja/enviarMesa', array('id_mesa' => $mesa, 'id_lprecio' => 1, 'observacion' => '', 'detalle' => array(array(
		'id_producto' => $idE, 'cantidad' => 1, 'agregados' => array(), 'nota' => '', 'menu' => array(array('id' => $k0['opciones'][0]['id'], 'cantidad' => 1)), 'sushi' => null))));
	revisar('elegir 1 solo se rechaza (hay que elegir 2)', $c >= 400, "código $c: " . substr($b, 0, 200));
	list($c, , $b) = postJson(BASE . '/Caja/enviarMesa', array('id_mesa' => $mesa, 'id_lprecio' => 1, 'observacion' => '', 'detalle' => array(array(
		'id_producto' => $idE, 'cantidad' => 1, 'agregados' => array(), 'nota' => '', 'menu' => array(array('id' => $k0['opciones'][0]['id'], 'cantidad' => 2)), 'sushi' => null))));
	revisar('elegir 2 veces el mismo producto sí se puede', $c === 200, "código $c: " . substr($b, 0, 200));
	list(, $b) = pedir(BASE . '/Caja/detalleMesa?id_mesa=' . $mesa);
	$l = array();
	foreach (json($b)['lineas'] ?? array() as $x) $l[(int)$x['id_producto']] = array((float)$x['cantidad'], (int)$x['unitario']);
	revisar('la promo lleva su precio y el elegido sale x2 en $0', ($l[$idE][1] ?? 0) === 5000 && ($l[(int)$k0['opciones'][0]['id_producto']] ?? $l[(int)$a['id_producto']] ?? null) !== null, json_encode($l));
	postJson(BASE . '/Caja/anularPedidoMesa', array('id_mesa' => $mesa, 'motivo' => 'Prueba automática', 'autorizacion' => $aut));
}
list($c, $b) = pedir(BASE . '/Productos/grabarPromo', array('id_producto' => $idE, 'nombre' => $nombre . ' E', 'precio' => 5000, 'id_familia' => $fam, 'vigente' => 0,
	'elegir' => 0, 'items' => array(array('id_producto' => $a['id_producto'], 'cantidad' => 1), array('id_producto' => $bb['id_producto'], 'cantidad' => 2))));
list(, $b) = pedir(BASE . '/Productos/promos?id=' . $idE);
$p = json($b)['promo'] ?? array();
revisar('volver a fija: trae sus 2 productos con su cantidad (1 y 2)', $c === 200 && ($p['elegir'] ?? -1) === 0 && count($p['items'] ?? array()) === 2
	&& ($p['items'][1]['cantidad'] ?? 0) === 2, substr($b, 0, 300));

echo "Subfamilia y comentarios (v1.39.7):\n";
list(, $b) = pedir(BASE . '/Productos/promos');
$dd = json($b);
$subs = array_values(array_filter($dd['subfamilias'] ?? array(), function ($x) use ($fam) { return (int)$x['id_familia'] === $fam; }));
$otra = array_values(array_filter($dd['subfamilias'] ?? array(), function ($x) use ($fam) { return (int)$x['id_familia'] > 0 && (int)$x['id_familia'] !== $fam; }));
if ($subs) {
	list($c, $b) = pedir(BASE . '/Productos/grabarPromo', array('id_producto' => $idE, 'nombre' => $nombre . ' E', 'precio' => 5000, 'id_familia' => $fam,
		'id_subfamilia' => $subs[0]['id_subfamilia'], 'vigente' => 1, 'elegir' => 2, 'items' => $itemsElegir));
	list(, $b2) = pedir(BASE . '/Productos/promos?id=' . $idE);
	revisar('grabar con subfamilia y al abrirla la trae', $c === 200 && (int)(json($b2)['promo']['id_subfamilia'] ?? 0) === (int)$subs[0]['id_subfamilia'], substr($b . $b2, 0, 300));
} else echo "  (sin subfamilias en la familia " . $fam . ": no se probó grabar con subfamilia)\n";
if ($otra) {
	list($c, $b) = pedir(BASE . '/Productos/grabarPromo', array('id_producto' => $idE, 'nombre' => $nombre . ' E', 'precio' => 5000, 'id_familia' => $fam,
		'id_subfamilia' => $otra[0]['id_subfamilia'], 'vigente' => 1, 'elegir' => 2, 'items' => $itemsElegir));
	revisar('una subfamilia de otra familia se rechaza', $c === 400, "código $c");
}
pedir(BASE . '/Productos/grabarPromo', array('id_producto' => $idE, 'nombre' => $nombre . ' E', 'precio' => 5000, 'id_familia' => $fam, 'id_subfamilia' => 0,
	'vigente' => 1, 'elegir' => 2, 'items' => $itemsElegir));
list(, $b) = pedir(BASE . '/Caja/catalogo?id_lprecio=1');
$menuE = null;
foreach (json($b)['menus'] ?? array() as $m) if ((int)$m['id_producto'] === $idE) $menuE = $m;
list(, $b) = pedir(BASE . '/Caja/mesas');
$mesa = 0;
foreach ((json($b)['mesas'] ?? array()) as $m) if ($m['estado'] === 'libre' && empty($m['con_pedido']) && empty($m['reserva'])) { $mesa = (int)$m['id_mesa']; break; }
if ($mesa && $menuE) {
	$op = $menuE['categorias'][0]['opciones'][0];
	list($c, , $b) = postJson(BASE . '/Caja/enviarMesa', array('id_mesa' => $mesa, 'id_lprecio' => 1, 'observacion' => '', 'detalle' => array(array(
		'id_producto' => $idE, 'cantidad' => 1, 'agregados' => array(), 'nota' => '', 'menu' => array(array('id' => $op['id'], 'cantidad' => 2, 'nota' => 'sin cebolla')), 'sushi' => null))));
	revisar('enviar la promo con un comentario en su producto', $c === 200, "código $c: " . substr($b, 0, 200));
	list(, $b) = pedir(BASE . '/Caja/detalleMesa?id_mesa=' . $mesa);
	$conNota = false;
	foreach (json($b)['lineas'] ?? array() as $x) if ((int)$x['id_producto'] === (int)$op['id_producto'] && strpos((string)($x['nota'] ?? ''), 'sin cebolla') !== false) $conNota = true;
	revisar('el producto de la promo guarda su comentario', $conNota, substr($b, 0, 400));
	postJson(BASE . '/Caja/anularPedidoMesa', array('id_mesa' => $mesa, 'motivo' => 'Prueba automática', 'autorizacion' => $aut));
}

echo "Botones de la Carta:\n";
pedir(BASE . '/Configuraciones/guardar', array('promos_sushi_web' => '0'));
list(, $h) = pedir(BASE . '/Productos', null, false);
revisar('sushi apagado: sale Promos y no Promos Sushi', strpos($h, 'abrirPromos()') !== false && strpos($h, 'onclick="abrirPromosSushi()"') === false);
pedir(BASE . '/Configuraciones/guardar', array('promos_sushi_web' => '1'));
list(, $h) = pedir(BASE . '/Productos', null, false);
revisar('sushi encendido: salen los 2 botones', strpos($h, 'abrirPromos()') !== false && strpos($h, 'onclick="abrirPromosSushi()"') !== false);

terminar('promos');
