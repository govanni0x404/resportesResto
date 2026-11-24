<?php
/**
 * Prueba de la v1.35.1 (pedidos del usuario en cambios.md), cada uno con su casilla en Configuraciones:
 *  1. Extras con cobro: casilla del producto en la Carta. Los agregados hasta el máximo van sin costo y los extras cobran
 *     el valor adicional de su agregado, hasta "cuántos extras" de Configuraciones. Sin la casilla: como siempre.
 *  2. Categorías de agregados: la categoría es del producto agregado (Mantención > Categorías de agregados, varios de una
 *     vez) y el máximo por categoría está en Configuraciones.
 *
 * ¡CREA AGREGADOS Y CATEGORÍAS, ENVÍA PEDIDOS Y CAMBIA CONFIGURACIONES! Solo con una COPIA de la base:
 *   php tests/agregados_extras_categorias_test.php http://localhost:8002/index.php COPIA
 */
require __DIR__ . '/lib_pruebas.php';
if (($argv[2] ?? '') !== 'COPIA') {
	echo "Esta prueba envía pedidos y cambia productos. Córrela solo contra una COPIA de la base:\n"
		. "  php tests/agregados_extras_categorias_test.php http://localhost:8002/index.php COPIA\n";
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
function opcion($idProd, $datos) { return pedir(BASE . '/Productos/opcionesAgregados', array('id_producto' => $idProd) + $datos); }

echo "Preparar:\n";
list(, $b) = pedir(BASE . '/Caja/catalogo?id_lprecio=1');
$cat = json($b);
$esp = array();
foreach (array_merge($cat['menus'] ?? array(), $cat['sushis'] ?? array()) as $x) $esp[(int)$x['id_producto']] = true;
$simples = array();
foreach ($cat['productos'] ?? array() as $p) {
	if ($p['precio'] > 0 && empty($p['fraccion']) && empty($p['pedir_cantidad']) && empty($p['exento']) && !isset($esp[(int)$p['id_producto']])) $simples[] = $p;
}
revisar('hay 4 productos simples para probar', count($simples) >= 4);
if (count($simples) < 4) terminar('agregados extra y categorías');
$prin = $simples[0];
$idPrin = (int)$prin['id_producto'];
$extras = array_slice($simples, 1, 3);
foreach ($extras as $k => $e) {
	list(, $b) = pedir(BASE . '/Agregados/ListadoDeAgregados', array());
	$idEx = 0;
	foreach ((json($b)['aaData'] ?? array()) as $a) if ((int)$a['id_producto_maestro'] === $idPrin && (int)$a['id_producto'] === (int)$e['id_producto']) $idEx = (int)$a['id_agregado'];
	pedir(BASE . '/Agregados/grabarNuevoAgregado', array('prod' => array('id_agregado' => $idEx, 'id_producto' => (int)$e['id_producto'],
		'cantidad' => 1, 'vigente' => 'true', 'id_producto_maestro' => $idPrin, 'valor' => 100 * ($k + 1), 'usar_destino_impresion' => 'false',
		)));
}
list(, $b) = pedir(BASE . '/Productos/obtenerAgregadosDelProducto', array('id_producto_maestro' => $idPrin));
$ags = array();
foreach (json($b) ?: array() as $a) foreach ($extras as $k => $e) if ((int)$a['id_producto'] === (int)$e['id_producto']) $ags[$k] = (int)$a['id_agregado'];
ksort($ags);
revisar('el producto tiene sus 3 agregados', count($ags) === 3, json_encode($ags));
if (count($ags) < 3) terminar('agregados extra y categorías');
pedir(BASE . '/Productos/maxAgregados', array('id_producto' => $idPrin, 'maximo' => 2));

echo "Carta > Agregados del producto:\n";
list($c, $b) = opcion($idPrin, array('accion' => 'campo', 'id_agregado' => $ags[1], 'campo' => 'cantidad', 'valor' => 2));
revisar('la cantidad de la tabla ahora se guarda', $c === 200 && (json($b)['valor'] ?? 0) === 2, "código $c: " . substr($b, 0, 200));
opcion($idPrin, array('accion' => 'campo', 'id_agregado' => $ags[1], 'campo' => 'cantidad', 'valor' => 1));
list($c) = opcion($idPrin, array('accion' => 'campo', 'id_agregado' => $ags[1], 'campo' => 'valor_extra', 'valor' => 1));
revisar('un dato que ya no existe (precio extra) se rechaza', $c === 400, "código $c");
list($c, $b) = opcion($idPrin + 100000, array('accion' => 'campo', 'id_agregado' => $ags[1], 'campo' => 'valor', 'valor' => 1));
revisar('no se cambia un agregado desde otro producto', $c === 400, "código $c");
list($c, $b) = opcion($idPrin, array('accion' => 'extras', 'valor' => 1));
revisar('marcar "Extras con cobro" en el producto', $c === 200 && (json($b)['extras'] ?? null) === true, "código $c: " . substr($b, 0, 200));
pedir(BASE . '/Productos/maxAgregados', array('id_producto' => $idPrin, 'maximo' => 2));

echo "Agregados extra apagado en Configuraciones:\n";
pedir(BASE . '/Configuraciones/guardar', array('agregados_extra_web' => '0', 'agregados_categorias_web' => '0', 'agregados_max_extras_web' => '1', 'agregados_max_categoria_web' => '0'));
list(, $b) = pedir(BASE . '/Caja/estado');
if (strpos($b, '"caja":null') !== false) pedir(BASE . '/Caja/abrir', array('fondo' => '0'));
list(, $b) = pedir(BASE . '/Caja/mesas');
$mesa = 0;
foreach ((json($b)['mesas'] ?? array()) as $m) if ($m['estado'] === 'libre' && empty($m['con_pedido']) && empty($m['reserva'])) { $mesa = (int)$m['id_mesa']; break; }
revisar('hay una mesa libre', $mesa > 0);
if (!$mesa) terminar('agregados extra y categorías');
$enviar = function ($agregados, $extrasL) use ($mesa, $idPrin) {
	return postJson(BASE . '/Caja/enviarMesa', array('id_mesa' => $mesa, 'id_lprecio' => 1, 'observacion' => '', 'detalle' => array(array('id_producto' => $idPrin,
		'cantidad' => 1, 'agregados' => $agregados, 'extras' => $extrasL, 'notas_agregados' => array(), 'nota' => '', 'menu' => array(), 'sushi' => null))));
};
$precios = function () use ($mesa) {
	list(, $b) = pedir(BASE . '/Caja/detalleMesa?id_mesa=' . $mesa);
	$r = array();
	foreach (json($b)['lineas'] ?? array() as $l) $r[(int)$l['id_producto']] = (int)($l['unitario'] ?? 0);
	return $r;
};
$anular = function () use ($mesa, $aut) {
	return postJson(BASE . '/Caja/anularPedidoMesa', array('id_mesa' => $mesa, 'motivo' => 'Prueba automática', 'autorizacion' => $aut))[0];
};
$err = function ($b) { return json($b)['error'] ?? ''; };
$idA1 = (int)$extras[0]['id_producto']; $idA2 = (int)$extras[1]['id_producto']; $idA3 = (int)$extras[2]['id_producto'];
list($c, , $b) = $enviar(array_values($ags), array($ags[2]));
revisar('apagado: un extra se rechaza', $c === 400 && strpos($err($b), 'no tiene agregados extra') !== false, "código $c: " . substr($b, 0, 200));
list($c, , $b) = $enviar(array($ags[0], $ags[1]), array());
revisar('apagado: 2 agregados se envían', $c === 200, "código $c: " . substr($b, 0, 200));
$pr = $precios();
revisar('apagado: cada agregado cobra su valor como siempre ($100 y $200)', ($pr[$idPrin] ?? 0) === (int)$prin['precio'] + 100 && ($pr[$idA2] ?? 0) === 200, json_encode($pr));
$anular();

echo "Agregados extra con cobro:\n";
pedir(BASE . '/Configuraciones/guardar', array('agregados_extra_web' => '1'));
list(, $b) = pedir(BASE . '/Caja/catalogo?id_lprecio=1');
$cat = json($b);
$pCat = null;
foreach ($cat['productos'] as $p) if ((int)$p['id_producto'] === $idPrin) $pCat = $p;
revisar('la Caja recibe la opción, cuántos extras y la casilla del producto', ($cat['config']['agregados_extra'] ?? null) === true
	&& ($cat['config']['max_extras_agregados'] ?? null) === 1 && ($pCat['extras'] ?? null) === true, json_encode(array($cat['config']['max_extras_agregados'] ?? null, $pCat['extras'] ?? null)));
list($c, , $b) = $enviar(array($ags[0], $ags[1]), array($ags[1]));
revisar('un extra que todavía cabe sin costo se rechaza', $c === 400 && strpos($err($b), 'todavía cabe') !== false, "código $c: " . substr($b, 0, 200));
pedir(BASE . '/Productos/maxAgregados', array('id_producto' => $idPrin, 'maximo' => 1));
list($c, , $b) = $enviar(array_values($ags), array($ags[1], $ags[2]));
revisar('más extras que los de Configuraciones (1) se rechaza', $c === 400 && strpos($err($b), 'extra') !== false, "código $c: " . substr($b, 0, 200));
pedir(BASE . '/Productos/maxAgregados', array('id_producto' => $idPrin, 'maximo' => 2));
list($c, , $b) = $enviar(array_values($ags), array($ags[2]));
revisar('2 sin costo + 1 extra: el pedido se envía', $c === 200, "código $c: " . substr($b, 0, 300));
$pr = $precios();
revisar('los 2 dentro del máximo van sin costo', ($pr[$idPrin] ?? 0) === (int)$prin['precio'] && ($pr[$idA2] ?? -1) === 0, json_encode($pr));
revisar('el extra cobra el valor de su agregado ($300)', ($pr[$idA3] ?? 0) === 300, json_encode($pr));
$anular();
opcion($idPrin, array('accion' => 'extras', 'valor' => 0));
list($c, , $b) = $enviar(array($ags[0], $ags[1]), array());
$pr = $precios();
revisar('sin la casilla del producto: cobran su valor como siempre', $c === 200 && ($pr[$idA2] ?? 0) === 200, json_encode($pr));
$anular();

echo "Ventas en espera guardan los extras:\n";
list($c, , $b) = postJson(BASE . '/Caja/ponerEnEspera', array('nombre' => 'Prueba extras ' . date('His'), 'total' => 1,
	'lineas' => array(array('id_producto' => $idPrin, 'nombre' => $prin['nombre'], 'precio' => (int)$prin['precio'], 'cantidad' => 1,
		'agregados' => array_values($ags), 'extras' => array($ags[2], 999999)))));
revisar('dejar en espera con un extra', $c === 200, "código $c: " . substr($b, 0, 200));
list(, $b) = pedir(BASE . '/Caja/esperas');
$idEsp = 0;
foreach ((json($b)['esperas'] ?? array()) as $e) if (strpos($e['nombre'], 'Prueba extras ') === 0) $idEsp = (int)$e['id'];
list($c, , $b) = postJson(BASE . '/Caja/recuperarEspera', array('id' => $idEsp));
revisar('al retomarla trae solo el extra válido', (json($b)['datos']['lineas'][0]['extras'] ?? null) === array($ags[2]), substr($b, 0, 200));
if ($idEsp) postJson(BASE . '/Caja/anularEspera', array('id' => $idEsp, 'motivo' => 'Prueba automática', 'autorizacion' => $aut ? array('id_usuario' => 0, 'clave' => $aut) : null));

echo "Categorías de agregados:\n";
$suf = date('His') . mt_rand(10, 99);
list($c, $b) = pedir(BASE . '/CategoriasAgregados/guardar', array('id' => 0, 'nombre' => 'Prueba prote ' . $suf, 'orden' => 0, 'vigente' => 1));
$idProte = (int)(json($b)['id'] ?? 0);
revisar('crear la categoría (queda en mayúsculas)', $c === 200 && $idProte > 0 && strpos(json_encode(json($b)['categorias'] ?? array(), JSON_UNESCAPED_UNICODE), 'PRUEBA PROTE') !== false, "código $c: " . substr($b, 0, 200));
list($c) = pedir(BASE . '/CategoriasAgregados/guardar', array('id' => 0, 'nombre' => 'prueba PROTE ' . $suf, 'vigente' => 1));
revisar('no deja dos categorías con el mismo nombre', $c === 409, "código $c");
list($c) = pedir(BASE . '/CategoriasAgregados/guardar', array('id' => 0, 'nombre' => '   ', 'vigente' => 1));
revisar('no deja una categoría sin nombre', $c === 400, "código $c");
list(, $b) = pedir(BASE . '/CategoriasAgregados/guardar', array('id' => 0, 'nombre' => 'Prueba verdu ' . $suf, 'vigente' => 1));
$idVerdu = (int)(json($b)['id'] ?? 0);
list($c, $b) = pedir(BASE . '/CategoriasAgregados/asignar', array('id_categoria' => $idProte, 'poner' => array($idA1, $idA2), 'quitar' => array()));
revisar('poner 2 productos de una vez en la categoría', $c === 200 && (json($b)['puestos'] ?? 0) === 2, "código $c: " . substr($b, 0, 200));
pedir(BASE . '/CategoriasAgregados/asignar', array('id_categoria' => $idVerdu, 'poner' => array($idA3)));
list(, $b) = pedir(BASE . '/CategoriasAgregados/agregados');
$catDe = array();
foreach (json($b)['agregados'] ?? array() as $a) $catDe[(int)$a['id']] = $a;
revisar('la lista trae la categoría del producto y dónde se usa', ($catDe[$idA1]['id_categoria'] ?? 0) === $idProte && ($catDe[$idA3]['id_categoria'] ?? 0) === $idVerdu
	&& ($catDe[$idA1]['usado'] ?? 0) >= 1);
pedir(BASE . '/Productos/maxAgregados', array('id_producto' => $idPrin, 'maximo' => 0));
pedir(BASE . '/Configuraciones/guardar', array('agregados_categorias_web' => '1', 'agregados_max_categoria_web' => '1'));
list(, $b) = pedir(BASE . '/Productos/opcionesAgregados', array('id_producto' => $idPrin));
$o = json($b);
revisar('la ventana del producto recibe las categorías y la de cada producto', !empty($o['categorias']) && (int)($o['categoria_productos'][(string)$idA1] ?? 0) === $idProte
	&& ($o['max_categoria'] ?? 0) === 1, substr($b, 0, 200));
list(, $b) = pedir(BASE . '/Productos/obtenerAgregadosDelProducto', array('id_producto_maestro' => $idPrin));
$ok = false;
foreach (json($b) ?: array() as $a) if ((int)$a['id_agregado'] === $ags[2]) $ok = (int)$a['id_categoria'] === $idVerdu;
revisar('la tabla del producto trae la categoría de cada agregado', $ok);
list(, $b) = pedir(BASE . '/Caja/catalogo?id_lprecio=1');
$cat = json($b);
$aCat = array();
foreach ($cat['agregados'] ?? array() as $a) $aCat[(int)$a['id_agregado']] = $a;
$ids = array_map(function ($c) { return (int)$c['id']; }, $cat['categorias_agregados'] ?? array());
revisar('la Caja recibe las categorías y el máximo por categoría', in_array($idProte, $ids, true) && ($cat['config']['max_categoria_agregados'] ?? 0) === 1);
revisar('la categoría de cada agregado es la de su producto', ($aCat[$ags[0]]['id_categoria'] ?? 0) === $idProte && ($aCat[$ags[2]]['id_categoria'] ?? 0) === $idVerdu);
list($c, , $b) = $enviar(array($ags[0], $ags[1]), array());
revisar('2 de una categoría con máximo 1 se rechaza', $c === 400 && strpos($err($b), 'de cada categoría') !== false, "código $c: " . substr($b, 0, 200));
list($c, , $b) = $enviar(array($ags[0], $ags[2]), array());
revisar('1 de cada categoría se envía (sin máximo del producto)', $c === 200, "código $c: " . substr($b, 0, 200));
$anular();
opcion($idPrin, array('accion' => 'extras', 'valor' => 1));
pedir(BASE . '/Productos/maxAgregados', array('id_producto' => $idPrin, 'maximo' => 1));
list($c, , $b) = $enviar(array($ags[0], $ags[1]), array($ags[1]));
revisar('el extra también respeta el máximo de su categoría', $c === 400 && strpos($err($b), 'de cada categoría') !== false, "código $c: " . substr($b, 0, 200));
list($c, , $b) = $enviar(array($ags[0], $ags[2]), array($ags[2]));
revisar('máximo 1 lleno: el de otra categoría va como extra', $c === 200, "código $c: " . substr($b, 0, 300));
$pr = $precios();
revisar('el extra cobra su valor ($300) y el primero va sin costo', ($pr[$idA3] ?? 0) === 300 && ($pr[$idPrin] ?? 0) === (int)$prin['precio'], json_encode($pr));
$anular();
pedir(BASE . '/Productos/maxAgregados', array('id_producto' => $idPrin, 'maximo' => 2));
list($c, , $b) = $enviar(array($ags[0], $ags[2]), array($ags[2]));
revisar('con el máximo sin llenar no hay extra (el máximo por categoría no da extras)', $c === 400 && strpos($err($b), 'todavía cabe') !== false, "código $c: " . substr($b, 0, 200));

echo "Dejar como estaba:\n";
list($c, $b) = pedir(BASE . '/CategoriasAgregados/eliminar', array('id' => $idProte));
revisar('las categorías no se borran (no hay eliminar)', $c === 404, "código $c");
list($c, $b) = pedir(BASE . '/CategoriasAgregados/guardar', array('id' => $idProte, 'nombre' => 'Prueba prote ' . $suf . ' x', 'vigente' => 0));
$cp = null;
foreach (json($b)['categorias'] ?? array() as $x) if ((int)$x['id'] === $idProte) $cp = $x;
revisar('se deja como no activa', $c === 200 && $cp && $cp['vigente'] === false, "código $c: " . substr($b, 0, 200));
list(, $b) = pedir(BASE . '/Caja/catalogo?id_lprecio=1');
$ids = array_map(function ($x) { return (int)$x['id']; }, json($b)['categorias_agregados'] ?? array());
revisar('una categoría no activa ya no sale en la Caja', !in_array($idProte, $ids, true));
pedir(BASE . '/CategoriasAgregados/asignar', array('id_categoria' => $idProte, 'quitar' => array($idA1, $idA2)));
pedir(BASE . '/CategoriasAgregados/asignar', array('id_categoria' => $idVerdu, 'quitar' => array($idA3)));
pedir(BASE . '/CategoriasAgregados/guardar', array('id' => $idVerdu, 'nombre' => 'Prueba verdu ' . $suf . ' x', 'vigente' => 0));
opcion($idPrin, array('accion' => 'extras', 'valor' => 0));
pedir(BASE . '/Productos/maxAgregados', array('id_producto' => $idPrin, 'maximo' => 0));
pedir(BASE . '/Configuraciones/guardar', array('agregados_extra_web' => '0', 'agregados_categorias_web' => '0', 'agregados_max_extras_web' => '1', 'agregados_max_categoria_web' => '0'));
list(, $b) = pedir(BASE . '/Caja/catalogo?id_lprecio=1');
revisar('las 2 opciones quedan apagadas', (json($b)['config']['agregados_extra'] ?? null) === false && (json($b)['config']['agregados_categorias'] ?? null) === false);

terminar('agregados extra y categorías');
