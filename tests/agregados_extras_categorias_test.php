<?php
/**
 * Prueba de la v1.35.0 (pedidos del usuario en cambios.md), cada uno con su casilla en Configuraciones:
 *  1. Agregados extra con costo: con el máximo lleno se pueden sumar más agregados, que cobran su precio extra
 *     (0 = su valor de siempre), hasta el máximo de extras del producto. Apagado: el máximo es un tope (como antes).
 *  2. Categorías de agregados: mantenedor Tablas > Categorías de agregados (poner varios agregados de una vez) y límite por
 *     categoría en cada producto, además del total. La Caja recibe las categorías y los límites.
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
		'valor_extra' => 0, 'id_categoria' => 0)));
}
list(, $b) = pedir(BASE . '/Productos/obtenerAgregadosDelProducto', array('id_producto_maestro' => $idPrin));
$ags = array();
foreach (json($b) ?: array() as $a) foreach ($extras as $k => $e) if ((int)$a['id_producto'] === (int)$e['id_producto']) $ags[$k] = (int)$a['id_agregado'];
ksort($ags);
revisar('el producto tiene sus 3 agregados', count($ags) === 3, json_encode($ags));
if (count($ags) < 3) terminar('agregados extra y categorías');
pedir(BASE . '/Productos/maxAgregados', array('id_producto' => $idPrin, 'maximo' => 2));

echo "Carta > Agregados del producto:\n";
list($c, $b) = opcion($idPrin, array('accion' => 'campo', 'id_agregado' => $ags[0], 'campo' => 'valor_extra', 'valor' => 1000));
revisar('poner el precio extra de un agregado', $c === 200 && (json($b)['valor'] ?? 0) === 1000, "código $c: " . substr($b, 0, 200));
list($c, $b) = opcion($idPrin, array('accion' => 'campo', 'id_agregado' => $ags[2], 'campo' => 'valor_extra', 'valor' => 3000));
list($c, $b) = opcion($idPrin, array('accion' => 'campo', 'id_agregado' => $ags[1], 'campo' => 'cantidad', 'valor' => 2));
revisar('la cantidad de la tabla ahora se guarda', $c === 200 && (json($b)['valor'] ?? 0) === 2, "código $c: " . substr($b, 0, 200));
opcion($idPrin, array('accion' => 'campo', 'id_agregado' => $ags[1], 'campo' => 'cantidad', 'valor' => 1));
list($c, $b) = opcion($idPrin, array('accion' => 'campo', 'id_agregado' => $ags[1], 'campo' => 'nada', 'valor' => 1));
revisar('un dato desconocido se rechaza', $c === 400, "código $c");
list($c, $b) = opcion($idPrin + 100000, array('accion' => 'campo', 'id_agregado' => $ags[1], 'campo' => 'valor', 'valor' => 1));
revisar('no se cambia un agregado desde otro producto', $c === 400, "código $c: " . substr($b, 0, 120));
list($c, $b) = opcion($idPrin, array('accion' => 'max_extras', 'maximo' => 1));
revisar('máximo de extras = 1', $c === 200 && (json($b)['maximo'] ?? 0) === 1, "código $c: " . substr($b, 0, 200));
list(, $b) = pedir(BASE . '/Productos/obtenerAgregadosDelProducto', array('id_producto_maestro' => $idPrin));
$vx = array();
foreach (json($b) ?: array() as $a) $vx[(int)$a['id_agregado']] = (int)$a['valor_extra'];
revisar('la tabla trae el precio extra de cada agregado', ($vx[$ags[0]] ?? -1) === 1000 && ($vx[$ags[2]] ?? -1) === 3000 && ($vx[$ags[1]] ?? -1) === 0, json_encode($vx));

echo "Agregados extra apagado:\n";
pedir(BASE . '/Configuraciones/guardar', array('agregados_extra_web' => '0', 'agregados_categorias_web' => '0'));
list(, $b) = pedir(BASE . '/Caja/estado');
if (strpos($b, '"caja":null') !== false) pedir(BASE . '/Caja/abrir', array('fondo' => '0'));
list(, $b) = pedir(BASE . '/Caja/mesas');
$mesa = 0;
foreach ((json($b)['mesas'] ?? array()) as $m) if ($m['estado'] === 'libre' && empty($m['con_pedido']) && empty($m['reserva'])) { $mesa = (int)$m['id_mesa']; break; }
revisar('hay una mesa libre', $mesa > 0);
if (!$mesa) terminar('agregados extra y categorías');
$linea = function ($agregados, $extrasL) use ($idPrin) {
	return array('id_producto' => $idPrin, 'cantidad' => 1, 'agregados' => $agregados, 'extras' => $extrasL, 'notas_agregados' => array(),
		'nota' => '', 'menu' => array(), 'sushi' => null);
};
$enviar = function ($agregados, $extrasL) use ($mesa, $linea) {
	return postJson(BASE . '/Caja/enviarMesa', array('id_mesa' => $mesa, 'id_lprecio' => 1, 'observacion' => '', 'detalle' => array($linea($agregados, $extrasL))));
};
list(, $b) = pedir(BASE . '/Caja/catalogo?id_lprecio=1');
$cat = json($b);
revisar('la Caja recibe las 2 opciones apagadas', ($cat['config']['agregados_extra'] ?? null) === false && ($cat['config']['agregados_categorias'] ?? null) === false);
list($c, , $b) = $enviar(array_values($ags), array($ags[2]));
revisar('apagado: un extra se rechaza', $c === 400 && strpos(json($b)['error'] ?? '', 'no están activados') !== false, "código $c: " . substr($b, 0, 200));
list($c, , $b) = $enviar(array_values($ags), array());
revisar('apagado: 3 agregados con máximo 2 se rechaza (como antes)', $c === 400 && strpos($b, 'hasta 2') !== false, "código $c: " . substr($b, 0, 200));

echo "Agregados extra encendido:\n";
pedir(BASE . '/Configuraciones/guardar', array('agregados_extra_web' => '1'));
list(, $b) = pedir(BASE . '/Caja/catalogo?id_lprecio=1');
$cat = json($b);
$aCat = array(); $pCat = null;
foreach ($cat['agregados'] ?? array() as $a) $aCat[(int)$a['id_agregado']] = $a;
foreach ($cat['productos'] as $p) if ((int)$p['id_producto'] === $idPrin) $pCat = $p;
revisar('la Caja recibe la opción encendida', ($cat['config']['agregados_extra'] ?? null) === true);
revisar('la Caja recibe el precio extra (0 = su valor)', ($aCat[$ags[2]]['valor_extra'] ?? 0) === 3000 && ($aCat[$ags[1]]['valor_extra'] ?? 0) === 200,
	json_encode(array($aCat[$ags[2]]['valor_extra'] ?? null, $aCat[$ags[1]]['valor_extra'] ?? null)));
revisar('y el máximo de extras del producto', ($pCat['max_extras'] ?? -1) === 1);
list($c, , $b) = $enviar(array($ags[0], $ags[1]), array($ags[1]));
revisar('un extra que todavía cabe sin costo se rechaza', $c === 400 && strpos(json($b)['error'] ?? '', 'todavía cabe') !== false, "código $c: " . substr($b, 0, 200));
pedir(BASE . '/Productos/maxAgregados', array('id_producto' => $idPrin, 'maximo' => 1));
list($c, , $b) = $enviar(array_values($ags), array($ags[1], $ags[2]));
revisar('más extras que el máximo de extras (1) se rechaza', $c === 400 && strpos($b, 'extra') !== false, "código $c: " . substr($b, 0, 200));
pedir(BASE . '/Productos/maxAgregados', array('id_producto' => $idPrin, 'maximo' => 2));
list($c, $r, $b) = $enviar(array_values($ags), array($ags[2]));
revisar('2 sin costo + 1 extra: el pedido se envía', $c === 200, "código $c: " . substr($b, 0, 300));
list(, $b) = pedir(BASE . '/Caja/detalleMesa?id_mesa=' . $mesa);
$precios = array();
foreach (json($b)['lineas'] ?? array() as $l) $precios[(int)$l['id_producto']] = (int)($l['unitario'] ?? 0);
$idA3 = (int)$extras[2]['id_producto'];
$idA2 = (int)$extras[1]['id_producto'];
revisar('el extra se cobra con su precio extra ($3.000)', ($precios[$idA3] ?? 0) === 3000, json_encode($precios));
revisar('el que va sin costo cobra su valor de siempre ($200)', ($precios[$idA2] ?? 0) === 200, json_encode($precios));
revisar('el producto lleva su primer agregado con su valor ($100)', ($precios[$idPrin] ?? 0) === (int)$prin['precio'] + 100, json_encode($precios));
list($c) = postJson(BASE . '/Caja/anularPedidoMesa', array('id_mesa' => $mesa, 'motivo' => 'Prueba automática', 'autorizacion' => $aut));
revisar('anular el pedido de prueba', $c === 200 || $c === 403, "código $c");

echo "Ventas en espera guardan los extras:\n";
list($c, , $b) = postJson(BASE . '/Caja/ponerEnEspera', array('nombre' => 'Prueba extras ' . date('His'), 'total' => 1,
	'lineas' => array(array('id_producto' => $idPrin, 'nombre' => $prin['nombre'], 'precio' => (int)$prin['precio'], 'cantidad' => 1,
		'agregados' => array_values($ags), 'extras' => array($ags[2], 999999)))));
revisar('dejar en espera con un extra', $c === 200, "código $c: " . substr($b, 0, 200));
list(, $b) = pedir(BASE . '/Caja/esperas');
$idEsp = 0;
foreach ((json($b)['esperas'] ?? array()) as $e) if (strpos($e['nombre'], 'Prueba extras ') === 0) $idEsp = (int)$e['id'];
list($c, , $b) = postJson(BASE . '/Caja/recuperarEspera', array('id' => $idEsp));
$lin = json($b)['datos']['lineas'][0] ?? null;
if ($c === 404 || $lin === null) {
	echo "  (no se pudo leer la venta en espera de vuelta: código $c; se revisa solo que se guardó)\n";
} else {
	revisar('al retomarla trae solo el extra válido', ($lin['extras'] ?? null) === array($ags[2]), json_encode($lin['extras'] ?? null));
}
if ($idEsp) postJson(BASE . '/Caja/anularEspera', array('id' => $idEsp, 'motivo' => 'Prueba automática', 'autorizacion' => $aut ? array('id_usuario' => 0, 'clave' => $aut) : null));

echo "Categorías de agregados:\n";
list($c, $b) = pedir(BASE . '/CategoriasAgregados/guardar', array('id' => 0, 'nombre' => 'Prueba prote ' . date('His'), 'orden' => 0, 'vigente' => 1));
$r = json($b);
$idProte = (int)($r['id'] ?? 0);
revisar('crear la categoría (queda en mayúsculas)', $c === 200 && $idProte > 0 && strpos(json_encode($r['categorias'] ?? array()), 'PRUEBA PROTE') !== false, "código $c: " . substr($b, 0, 200));
list($c, $b) = pedir(BASE . '/CategoriasAgregados/guardar', array('id' => 0, 'nombre' => 'prueba PROTE ' . date('His'), 'vigente' => 1));
revisar('no deja dos categorías con el mismo nombre', $c === 409, "código $c");
list($c, $b) = pedir(BASE . '/CategoriasAgregados/guardar', array('id' => 0, 'nombre' => 'Prueba verdu ' . date('His'), 'vigente' => 1));
$idVerdu = (int)(json($b)['id'] ?? 0);
list($c, $b) = pedir(BASE . '/CategoriasAgregados/guardar', array('id' => 0, 'nombre' => '   ', 'vigente' => 1));
revisar('no deja una categoría sin nombre', $c === 400, "código $c");
list($c, $b) = pedir(BASE . '/CategoriasAgregados/asignar', array('id_categoria' => $idProte, 'poner' => array($ags[0], $ags[1]), 'quitar' => array()));
revisar('poner 2 agregados de una vez en la categoría', $c === 200 && (json($b)['puestos'] ?? 0) === 2, "código $c: " . substr($b, 0, 200));
pedir(BASE . '/CategoriasAgregados/asignar', array('id_categoria' => $idVerdu, 'poner' => array($ags[2])));
list(, $b) = pedir(BASE . '/CategoriasAgregados/agregados');
$catDe = array();
foreach (json($b)['agregados'] ?? array() as $a) $catDe[(int)$a['id']] = (int)$a['id_categoria'];
revisar('la lista de agregados trae su categoría', ($catDe[$ags[0]] ?? 0) === $idProte && ($catDe[$ags[2]] ?? 0) === $idVerdu, json_encode(array_intersect_key($catDe, array_flip($ags))));
list($c, $b) = opcion($idPrin, array('accion' => 'limite_categoria', 'id_categoria' => $idProte, 'maximo' => 1));
revisar('límite de 1 en la categoría para este producto', $c === 200 && (json($b)['maximo'] ?? 0) === 1, "código $c: " . substr($b, 0, 200));
pedir(BASE . '/Productos/maxAgregados', array('id_producto' => $idPrin, 'maximo' => 0));
opcion($idPrin, array('accion' => 'max_extras', 'maximo' => 0));   // extras sin tope
pedir(BASE . '/Configuraciones/guardar', array('agregados_categorias_web' => '1'));
list(, $b) = pedir(BASE . '/Productos/opcionesAgregados', array('id_producto' => $idPrin));
$o = json($b);
revisar('la ventana del producto recibe las categorías y su límite', !empty($o['categorias']) && (int)($o['limites'][(string)$idProte] ?? 0) === 1, substr($b, 0, 200));
list(, $b) = pedir(BASE . '/Caja/catalogo?id_lprecio=1');
$cat = json($b);
$pCat = null;
foreach ($cat['productos'] as $p) if ((int)$p['id_producto'] === $idPrin) $pCat = $p;
$aCat = array();
foreach ($cat['agregados'] ?? array() as $a) $aCat[(int)$a['id_agregado']] = $a;
$ids = array_map(function ($c) { return (int)$c['id']; }, $cat['categorias_agregados'] ?? array());
revisar('la Caja recibe las categorías', in_array($idProte, $ids, true) && in_array($idVerdu, $ids, true));
revisar('la categoría de cada agregado', ($aCat[$ags[0]]['id_categoria'] ?? 0) === $idProte && ($aCat[$ags[2]]['id_categoria'] ?? 0) === $idVerdu);
revisar('y el límite por categoría del producto', (int)($pCat['limites_categoria'][(string)$idProte] ?? 0) === 1, json_encode($pCat['limites_categoria'] ?? null));
list($c, , $b) = $enviar(array_values($ags), array());
revisar('2 de una categoría de límite 1 sin extra se rechaza', $c === 400 && strpos(json($b)['error'] ?? '', 'de una categoría') !== false, "código $c: " . substr($b, 0, 200));
list($c, , $b) = $enviar(array_values($ags), array($ags[1], $ags[2]));
revisar('el extra de otra categoría (que cabe) se rechaza', $c === 400 && strpos(json($b)['error'] ?? '', 'todavía cabe') !== false, "código $c: " . substr($b, 0, 200));
list($c, $r, $b) = $enviar(array_values($ags), array($ags[1]));
revisar('el segundo de la categoría como extra: se envía', $c === 200, "código $c: " . substr($b, 0, 300));
list(, $b) = pedir(BASE . '/Caja/detalleMesa?id_mesa=' . $mesa);
$precios = array();
foreach (json($b)['lineas'] ?? array() as $l) $precios[(int)$l['id_producto']] = (int)($l['unitario'] ?? 0);
revisar('el extra sin precio extra cobra su valor ($200)', ($precios[$idA2] ?? 0) === 200, json_encode($precios));
revisar('el de la otra categoría va con su valor ($300)', ($precios[$idA3] ?? 0) === 300, json_encode($precios));
postJson(BASE . '/Caja/anularPedidoMesa', array('id_mesa' => $mesa, 'motivo' => 'Prueba automática', 'autorizacion' => $aut));

echo "Dejar como estaba:\n";
list($c, $b) = pedir(BASE . '/CategoriasAgregados/eliminar', array('id' => $idProte));
revisar('eliminar la categoría deja sus agregados sin categoría', $c === 200 && (json($b)['sin_categoria'] ?? 0) === 2, "código $c: " . substr($b, 0, 200));
pedir(BASE . '/CategoriasAgregados/eliminar', array('id' => $idVerdu));
list(, $b) = pedir(BASE . '/Productos/opcionesAgregados', array('id_producto' => $idPrin));
revisar('y se borra su límite en el producto', !isset(json($b)['limites'][(string)$idProte]), substr($b, 0, 200));
opcion($idPrin, array('accion' => 'extra_todos', 'valor' => 0));
opcion($idPrin, array('accion' => 'max_extras', 'maximo' => 0));
pedir(BASE . '/Productos/maxAgregados', array('id_producto' => $idPrin, 'maximo' => 0));
list(, $b) = pedir(BASE . '/Configuraciones/guardar', array('agregados_extra_web' => '0', 'agregados_categorias_web' => '0'));
list(, $b) = pedir(BASE . '/Caja/catalogo?id_lprecio=1');
revisar('las 2 opciones quedan apagadas', (json($b)['config']['agregados_extra'] ?? null) === false && (json($b)['config']['agregados_categorias'] ?? null) === false);

terminar('agregados extra y categorías');
