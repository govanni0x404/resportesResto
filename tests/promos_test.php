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

echo "Botones de la Carta:\n";
pedir(BASE . '/Configuraciones/guardar', array('promos_sushi_web' => '0'));
list(, $h) = pedir(BASE . '/Productos', null, false);
revisar('sushi apagado: sale Promos y no Promos Sushi', strpos($h, 'abrirPromos()') !== false && strpos($h, 'onclick="abrirPromosSushi()"') === false);
pedir(BASE . '/Configuraciones/guardar', array('promos_sushi_web' => '1'));
list(, $h) = pedir(BASE . '/Productos', null, false);
revisar('sushi encendido: salen los 2 botones', strpos($h, 'abrirPromos()') !== false && strpos($h, 'onclick="abrirPromosSushi()"') !== false);

terminar('promos');
