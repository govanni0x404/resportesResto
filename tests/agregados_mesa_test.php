<?php
/**
 * Prueba de los arreglos de la v1.30.3 en la Caja (pedidos del usuario en cambios.md):
 *  - máximo de agregados por producto (Carta > Agregados): la Caja y el servidor no dejan pasar más;
 *  - en la mesa, el segundo agregado en adelante queda UNIDO a su producto (antes salía como un producto aparte):
 *    se ve debajo del producto y al anular una parte del producto se anula la misma parte de sus agregados;
 *  - el mensaje del producto va junto al producto y el del agregado junto al agregado (pantalla y comanda);
 *  - Descuento máximo 0 = no se permite ningún descuento (antes 0 era "sin límite").
 *
 * ¡CREA AGREGADOS, ENVÍA PEDIDOS Y CAMBIA CONFIGURACIONES! Solo con una COPIA de la base:
 *   php tests/agregados_mesa_test.php http://localhost:8002/index.php COPIA
 */
require __DIR__ . '/lib_pruebas.php';
if (($argv[2] ?? '') !== 'COPIA') {
	echo "Esta prueba envía pedidos y cambia productos. Córrela solo contra una COPIA de la base:\n"
		. "  php tests/agregados_mesa_test.php http://localhost:8002/index.php COPIA\n";
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
function texto($html) { return preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($html))); }
$aut = getenv('PRUEBA_CLAVE_ADMIN') ?: '';

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
if (count($simples) < 4) terminar('agregados en la mesa');
$prin = $simples[0];
$idPrin = (int)$prin['id_producto'];
$extras = array_slice($simples, 1, 3);
foreach ($extras as $k => $e) {
	list(, $b) = pedir(BASE . '/Agregados/ListadoDeAgregados', array());
	$idEx = 0;
	foreach ((json($b)['aaData'] ?? array()) as $a) if ((int)$a['id_producto_maestro'] === $idPrin && (int)$a['id_producto'] === (int)$e['id_producto']) $idEx = (int)$a['id_agregado'];
	pedir(BASE . '/Agregados/grabarNuevoAgregado', array('prod' => array('id_agregado' => $idEx, 'id_producto' => (int)$e['id_producto'],
		'cantidad' => 1, 'vigente' => 'true', 'id_producto_maestro' => $idPrin, 'valor' => 100 * ($k + 1), 'usar_destino_impresion' => 'false')));
}
list($c, $b) = pedir(BASE . '/Productos/maxAgregados', array('id_producto' => $idPrin, 'maximo' => 2));
revisar('Carta > Agregados: guardar "máximo 2 agregados"', $c === 200 && (json($b)['maximo'] ?? 0) === 2, "código $c: " . substr($b, 0, 200));
list(, $b) = pedir(BASE . '/Productos/maxAgregados', array('id_producto' => $idPrin));
$mx = json($b);
revisar('al reabrir dice máximo 2 y cuenta sus agregados', ($mx['maximo'] ?? 0) === 2 && ($mx['agregados'] ?? 0) >= 3, substr($b, 0, 200));
list($c, $b) = pedir(BASE . '/Productos/maxAgregados', array('id_producto' => $idPrin, 'maximo' => -5));
revisar('un máximo negativo queda sin límite (-1)', (json($b)['maximo'] ?? 0) === -1, substr($b, 0, 200));
pedir(BASE . '/Productos/maxAgregados', array('id_producto' => $idPrin, 'maximo' => 2));

list(, $b) = pedir(BASE . '/Caja/catalogo?id_lprecio=1');
$cat = json($b);
$ags = array();
foreach ($cat['agregados'] ?? array() as $a) {
	foreach ($extras as $k => $e) if ((int)$a['id_producto_maestro'] === $idPrin && $a['nombre'] === $e['nombre']) $ags[$k] = (int)$a['id_agregado'];
}
ksort($ags);
$pCat = null;
foreach ($cat['productos'] as $p) if ((int)$p['id_producto'] === $idPrin) $pCat = $p;
revisar('la Caja recibe el máximo del producto', ($pCat['max_agregados'] ?? 0) === 2);
revisar('y sus 3 agregados', count($ags) === 3, json_encode($ags));
revisar('el catálogo trae subfamilias y la subfamilia de cada producto', isset($cat['subfamilias']) && array_key_exists('id_subfamilia', $pCat ?? array()));

echo "Mesa:\n";
list(, $b) = pedir(BASE . '/Caja/estado');
if (strpos($b, '"caja":null') !== false) pedir(BASE . '/Caja/abrir', array('fondo' => '0'));
list(, $b) = pedir(BASE . '/Caja/mesas');
$mesa = 0;
foreach ((json($b)['mesas'] ?? array()) as $m) if ($m['estado'] === 'libre' && empty($m['con_pedido']) && empty($m['reserva'])) { $mesa = (int)$m['id_mesa']; break; }
revisar('hay una mesa libre', $mesa > 0);
if (!$mesa || count($ags) < 3) terminar('agregados en la mesa');
$linea = function ($agregados) use ($idPrin, $ags) {
	return array('id_producto' => $idPrin, 'cantidad' => 2, 'agregados' => $agregados, 'notas_agregados' => array($ags[0] => 'PICANTE'),
		'nota' => 'SIN SAL', 'menu' => array(), 'sushi' => null);
};
list($c, , $b) = postJson(BASE . '/Caja/enviarMesa', array('id_mesa' => $mesa, 'id_lprecio' => 1, 'observacion' => '', 'detalle' => array($linea(array_values($ags)))));
revisar('3 agregados en un producto de máximo 2: el servidor lo rechaza', $c === 400 && strpos($b, 'hasta 2') !== false, "código $c: " . substr($b, 0, 200));
list($c, $r, $b) = postJson(BASE . '/Caja/enviarMesa', array('id_mesa' => $mesa, 'id_lprecio' => 1, 'observacion' => '', 'detalle' => array($linea(array($ags[0], $ags[1])))));
revisar('con 2 agregados el pedido se envía', $c === 200, "código $c: " . substr($b, 0, 300));
$h = texto(pedir(BASE . '/Caja/comanda?comanda_id=' . rawurlencode((string)($r['comanda_id'] ?? '')), null, false)[1]);
$pNota = mb_stripos($h, 'SIN SAL');
$pAg = mb_stripos($h, mb_strtoupper($extras[0]['nombre']));
revisar('comanda: el mensaje del producto sale antes que sus agregados', $pNota !== false && $pAg !== false && $pNota < $pAg, mb_substr($h, 0, 300));

list(, $b) = pedir(BASE . '/Caja/detalleMesa?id_mesa=' . $mesa);
$d = json($b);
$padre = null; $hijo = null;
foreach ($d['lineas'] ?? array() as $l) {
	if ((int)$l['id_producto'] === $idPrin) $padre = $l;
	if ((int)$l['id_producto'] === (int)$extras[1]['id_producto']) $hijo = $l;
}
revisar('la mesa tiene el producto y su segundo agregado', $padre && $hijo);
revisar('el segundo agregado queda unido a su producto (no es un producto aparte)', $padre && $hijo && (int)$hijo['menu_de'] === (int)$padre['id_detalle'],
	json_encode(array($padre['id_detalle'] ?? null, $hijo['menu_de'] ?? null)));
// v1.42.0: con varios agregados el 1.º también va en su fila: el mensaje del producto en el producto y el del agregado en el agregado
$hijo1 = null;
foreach ($d['lineas'] ?? array() as $l) if ((int)$l['id_producto'] === (int)$extras[0]['id_producto']) $hijo1 = $l;
revisar('el mensaje del producto y el del agregado vienen separados', ($padre['nota_producto'] ?? '') === 'SIN SAL' && ($hijo1['nota'] ?? '') === 'PICANTE'
	&& $hijo1 && (int)$hijo1['menu_de'] === (int)$padre['id_detalle'],
	json_encode(array($padre['nota'] ?? null, $padre['nota_producto'] ?? null, $hijo1['nota'] ?? null)));

list($c, , $b) = postJson(BASE . '/Caja/anularLineaMesa', array('id_mesa' => $mesa, 'id_detalle' => (int)$padre['id_detalle'], 'cantidad' => 1, 'motivo' => 'Prueba automática', 'autorizacion' => $aut));
$r = json($b);
if ($c === 403 || ($c >= 400 && stripos($b, 'clave') !== false)) {
	echo "  (la empresa pide clave de administrador para anular: no se probó. Dala con PRUEBA_CLAVE_ADMIN=...)\n";
} else {
	revisar('anular 1 de los 2 productos', $c === 200, "código $c: " . substr($b, 0, 300));
	$pa = null; $hi = null;
	foreach (($r['detalle']['lineas'] ?? array()) as $l) {
		if ((int)$l['id_producto'] === $idPrin) $pa = $l;
		if ((int)$l['id_producto'] === (int)$extras[1]['id_producto']) $hi = $l;
	}
	revisar('queda 1 producto y 1 de su agregado (se anuló junto con él)', $pa && $hi && (float)$pa['cantidad'] === 1.0 && (float)$hi['cantidad'] === 1.0,
		json_encode(array($pa['cantidad'] ?? null, $hi['cantidad'] ?? null)));
	revisar('y sigue unido a su producto', $pa && $hi && (int)$hi['menu_de'] === (int)$pa['id_detalle']);
	$an = texto(pedir(BASE . '/Caja/comanda?comanda_id=' . rawurlencode((string)($r['comanda_id'] ?? '')), null, false)[1]);
	revisar('la comanda de anulación trae el agregado como agregado del producto ("+ ...")', mb_stripos($an, '+ ' . mb_strtoupper($extras[1]['nombre'])) !== false, mb_substr($an, 0, 300));
}
list($c, , $b) = postJson(BASE . '/Caja/anularPedidoMesa', array('id_mesa' => $mesa, 'motivo' => 'Prueba automática', 'autorizacion' => $aut));
revisar('anular el pedido completo libera la mesa', $c === 200 || $c === 403, "código $c: " . substr($b, 0, 200));

echo "Permite varios agregados y 0 es 0 (v1.43.0):\n";
pedir(BASE . '/Productos/maxAgregados', array('id_producto' => $idPrin, 'maximo' => -1));
list($c, $b) = pedir(BASE . '/Productos/opcionesAgregados', array('id_producto' => $idPrin, 'accion' => 'varios', 'valor' => 0));
list(, $b2) = pedir(BASE . '/Productos/maxAgregados', array('id_producto' => $idPrin));
revisar('apagar "Permite varios agregados" y al reabrir sale apagada', $c === 200 && (json($b2)['varios'] ?? true) === false, substr($b . $b2, 0, 200));
list(, $b) = pedir(BASE . '/Caja/catalogo?id_lprecio=1');
$pc = null; foreach (json($b)['productos'] ?? array() as $p) if ((int)$p['id_producto'] === $idPrin) $pc = $p;
revisar('sin la casilla la Caja recibe máximo 1', ($pc['max_agregados'] ?? null) === 1, json_encode($pc['max_agregados'] ?? null));
list($c, , $b) = postJson(BASE . '/Caja/enviarMesa', array('id_mesa' => $mesa, 'id_lprecio' => 1, 'observacion' => '', 'detalle' => array($linea(array($ags[0], $ags[1])))));
revisar('sin la casilla, 2 agregados se rechazan', $c === 400 && strpos($b, 'hasta 1') !== false, "código $c: " . substr($b, 0, 200));
list($c, , $b) = postJson(BASE . '/Caja/enviarMesa', array('id_mesa' => $mesa, 'id_lprecio' => 1, 'observacion' => '', 'detalle' => array($linea(array($ags[1])))));
revisar('sin la casilla, 1 agregado se envía (en id_agregado, como siempre)', $c === 200, "código $c: " . substr($b, 0, 200));
postJson(BASE . '/Caja/anularPedidoMesa', array('id_mesa' => $mesa, 'motivo' => 'Prueba automática', 'autorizacion' => $aut));
pedir(BASE . '/Productos/opcionesAgregados', array('id_producto' => $idPrin, 'accion' => 'varios', 'valor' => 1));
pedir(BASE . '/Productos/maxAgregados', array('id_producto' => $idPrin, 'maximo' => 0));
list(, $b) = pedir(BASE . '/Caja/catalogo?id_lprecio=1');
$pc = null; foreach (json($b)['productos'] ?? array() as $p) if ((int)$p['id_producto'] === $idPrin) $pc = $p;
revisar('máximo 0 = ninguno (0 es 0): la Caja recibe 0', ($pc['max_agregados'] ?? null) === 0, json_encode($pc['max_agregados'] ?? null));
list($c, , $b) = postJson(BASE . '/Caja/enviarMesa', array('id_mesa' => $mesa, 'id_lprecio' => 1, 'observacion' => '', 'detalle' => array($linea(array($ags[1])))));
revisar('con máximo 0, un agregado se rechaza', $c === 400 && strpos($b, 'no lleva agregados') !== false, "código $c: " . substr($b, 0, 200));
pedir(BASE . '/Productos/maxAgregados', array('id_producto' => $idPrin, 'maximo' => -1));
list(, $b) = pedir(BASE . '/Caja/catalogo?id_lprecio=1');
$pc = null; foreach (json($b)['productos'] ?? array() as $p) if ((int)$p['id_producto'] === $idPrin) $pc = $p;
revisar('vacío / ∞ = sin límite: la Caja recibe -1', ($pc['max_agregados'] ?? null) === -1, json_encode($pc['max_agregados'] ?? null));

echo "Descuento máximo 0:\n";
pedir(BASE . '/Configuraciones/guardar', array('por_maximodescuento' => '0'));
list(, $b) = pedir(BASE . '/Caja/catalogo?id_lprecio=1');
$cat = json($b);
revisar('la Caja recibe tope 0', ($cat['config']['por_maximo_descuento'] ?? null) === 0, json_encode($cat['config']['por_maximo_descuento'] ?? null));
$efectivo = null; $doc = null;
foreach ($cat['formas_pago'] ?? array() as $f) if (!empty($f['efectivo'])) { $efectivo = $f; break; }
foreach ($cat['documentos'] ?? array() as $dd) if (empty($dd['electronico']) && empty($dd['requiere_cliente']) && !empty($dd['habilitado'])) { $doc = $dd; break; }
$otro = $simples[3];
$venta = array('id_mesa' => 0, 'parcial' => null, 'id_tdocumento' => (int)$doc['id_tdocumento'], 'id_lprecio' => 1, 'id_cliente' => 0,
	'descuento' => 1, 'propina' => 0, 'observacion' => 'PRUEBA AUTOMATICA', 'retira' => '', 'delivery' => null, 'id_delivery' => 0, 'factura' => null,
	'detalle' => array(array('id_producto' => (int)$otro['id_producto'], 'cantidad' => 1, 'agregados' => array(), 'nota' => '', 'notas_agregados' => new stdClass(), 'menu' => array(), 'sushi' => null)),
	'pagos' => array(array('id_fpago' => (int)$efectivo['id_fpago'], 'monto' => $otro['precio'])), 'token' => bin2hex(random_bytes(16)));
list($c, , $b) = postJson(BASE . '/Caja/vender', $venta);
revisar('con máximo 0, un descuento de $1 se rechaza', $c === 400 && stripos($b, 'No se permiten descuentos') !== false, "código $c: " . substr($b, 0, 200));
pedir(BASE . '/Configuraciones/guardar', array('por_maximodescuento' => '100'));
list(, $b) = pedir(BASE . '/Caja/catalogo?id_lprecio=1');
revisar('con 100 queda sin límite', (json($b)['config']['por_maximo_descuento'] ?? null) === 100);

pedir(BASE . '/Productos/maxAgregados', array('id_producto' => $idPrin, 'maximo' => -1));   // v1.43.0: sin límite (0 es 0)
terminar('agregados en la mesa');
