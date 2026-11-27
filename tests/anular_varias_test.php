<?php
/**
 * Prueba de la v1.33.0 (pedidos del usuario en cambios.md):
 *  - Ventas en espera > Anular todas: solo con la casilla de Configuraciones; anula las de la lista con una justificación;
 *  - Mesas > Anular varias: solo con la casilla; anula el pedido de varias mesas, las deja libres y da una comanda por mesa.
 *
 * ¡GRABA VENTAS EN ESPERA, ENVÍA PEDIDOS Y LOS ANULA! Solo con una COPIA de la base:
 *   php tests/anular_varias_test.php http://localhost:8002/index.php COPIA
 */
require __DIR__ . '/lib_pruebas.php';
if (($argv[2] ?? '') !== 'COPIA') {
	echo "Esta prueba graba y anula ventas en espera y pedidos de mesas. Córrela solo contra una COPIA de la base:\n"
		. "  php tests/anular_varias_test.php http://localhost:8002/index.php COPIA\n";
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
$autorizacion = $aut !== '' ? array('id_usuario' => 0, 'clave' => $aut) : null;

echo "Preparar:\n";
list(, $b) = pedir(BASE . '/Caja/estado');
if (strpos($b, '"caja":null') !== false) pedir(BASE . '/Caja/abrir', array('fondo' => '0'));
list(, $b) = pedir(BASE . '/Caja/catalogo?id_lprecio=1');
$cat = json($b);
$prod = null;
foreach ($cat['productos'] ?? array() as $p) {
	if ($p['precio'] > 0 && empty($p['fraccion']) && empty($p['pedir_cantidad'])) { $prod = $p; break; }
}
revisar('hay un producto para probar', $prod !== null);
if (!$prod) terminar('anular varias');

echo "Ventas en espera > Anular todas:\n";
pedir(BASE . '/Configuraciones/guardar', array('esperas_anular_todas_web' => '0', 'mesas_anular_varias_web' => '0'));
list(, $b) = pedir(BASE . '/Caja/catalogo?id_lprecio=1');
revisar('apagadas: la Caja recibe las dos opciones en false', (json($b)['config']['anular_todas_esperas'] ?? null) === false
	&& (json($b)['config']['anular_varias_mesas'] ?? null) === false, substr($b, 0, 100));
$ids = array();
foreach (array('Prueba A', 'Prueba B') as $nombre) {
	list($c, $r, $b) = postJson(BASE . '/Caja/ponerEnEspera', array('nombre' => $nombre . ' ' . date('His'), 'total' => (int)$prod['precio'],
		'lineas' => array(array('id_producto' => (int)$prod['id_producto'], 'nombre' => $prod['nombre'], 'precio' => (int)$prod['precio'], 'cantidad' => 1))));
	revisar("dejar en espera \"$nombre\"", $c === 200, "código $c: " . substr($b, 0, 200));
}
list(, $b) = pedir(BASE . '/Caja/esperas');
foreach ((json($b)['esperas'] ?? array()) as $e) if (strpos($e['nombre'], 'Prueba ') === 0) $ids[] = (int)$e['id'];
revisar('las 2 aparecen en la lista', count($ids) >= 2, count($ids) . ' encontradas');
list($c, , $b) = postJson(BASE . '/Caja/anularEsperasTodas', array('ids' => $ids, 'motivo' => 'Prueba automática', 'autorizacion' => $autorizacion));
revisar('con la casilla apagada el servidor no deja anular todas', $c === 403 && strpos($b, 'Configuraciones') !== false, "código $c: " . substr($b, 0, 200));
pedir(BASE . '/Configuraciones/guardar', array('esperas_anular_todas_web' => '1', 'mesas_anular_varias_web' => '1'));
list(, $b) = pedir(BASE . '/Caja/catalogo?id_lprecio=1');
revisar('encendidas: la Caja las recibe en true', (json($b)['config']['anular_todas_esperas'] ?? null) === true
	&& (json($b)['config']['anular_varias_mesas'] ?? null) === true);
list($c, , $b) = postJson(BASE . '/Caja/anularEsperasTodas', array('ids' => $ids, 'motivo' => 'abc', 'autorizacion' => $autorizacion));
// (si la empresa pide clave para anular, el servidor la pide antes de revisar la justificación: también es "no se anula")
revisar('sin justificación (menos de 5 letras) no se anula', $c === 400 || ($c === 403 && stripos($b, 'autorizacion') !== false), "código $c: " . substr($b, 0, 200));
list($c, $r, $b) = postJson(BASE . '/Caja/anularEsperasTodas', array('ids' => $ids, 'motivo' => 'Prueba automática', 'autorizacion' => $autorizacion));
if ($c === 403 && stripos($b, 'autorizacion') !== false) {
	echo "  (la empresa pide clave para anular ventas en espera: dala con PRUEBA_CLAVE_ADMIN=...)\n";
} else {
	revisar('anular todas de una vez', $c === 200 && ($r['anuladas'] ?? 0) === count($ids), "código $c: " . substr($b, 0, 200));
	list(, $b) = pedir(BASE . '/Caja/esperas');
	$quedan = 0;
	foreach ((json($b)['esperas'] ?? array()) as $e) if (in_array((int)$e['id'], $ids, true)) $quedan++;
	revisar('ya no queda ninguna en espera', $quedan === 0);
	list($c, , $b) = postJson(BASE . '/Caja/anularEsperasTodas', array('ids' => $ids, 'motivo' => 'Prueba automática', 'autorizacion' => $autorizacion));
	revisar('repetirlo avisa que ya no están en espera', $c === 409, "código $c: " . substr($b, 0, 200));
}

echo "Mesas > Anular varias:\n";
list(, $b) = pedir(BASE . '/Caja/mesas');
$libres = array();
foreach ((json($b)['mesas'] ?? array()) as $m) {
	if ($m['estado'] === 'libre' && empty($m['con_pedido']) && empty($m['reserva'])) $libres[] = (int)$m['id_mesa'];
	if (count($libres) === 2) break;
}
revisar('hay 2 mesas libres', count($libres) === 2);
if (count($libres) < 2) terminar('anular varias');
foreach ($libres as $idMesa) {
	list($c, , $b) = postJson(BASE . '/Caja/enviarMesa', array('id_mesa' => $idMesa, 'id_lprecio' => 1, 'observacion' => '',
		'detalle' => array(array('id_producto' => (int)$prod['id_producto'], 'cantidad' => 1, 'agregados' => array(), 'nota' => '', 'menu' => array(), 'sushi' => null))));
	revisar("pedido en la mesa $idMesa", $c === 200, "código $c: " . substr($b, 0, 200));
}
list($c, , $b) = postJson(BASE . '/Caja/anularVariasMesas', array('ids' => $libres, 'motivo' => '', 'autorizacion' => $autorizacion));
revisar('sin motivo no se anula', $c === 400, "código $c: " . substr($b, 0, 200));
pedir(BASE . '/Configuraciones/guardar', array('mesas_anular_varias_web' => '0'));
list($c, , $b) = postJson(BASE . '/Caja/anularVariasMesas', array('ids' => $libres, 'motivo' => 'Prueba automática', 'autorizacion' => $autorizacion));
revisar('con la casilla apagada el servidor no deja anular varias', $c === 403 && strpos($b, 'Configuraciones') !== false, "código $c: " . substr($b, 0, 200));
pedir(BASE . '/Configuraciones/guardar', array('mesas_anular_varias_web' => '1'));
list($c, $r, $b) = postJson(BASE . '/Caja/anularVariasMesas', array('ids' => $libres, 'motivo' => 'Prueba automática', 'autorizacion' => $autorizacion));
if ($c === 403 && stripos($b, 'autorizacion') !== false) {
	echo "  (la empresa pide clave para anular el pedido de la mesa: dala con PRUEBA_CLAVE_ADMIN=...)\n";
	foreach ($libres as $idMesa) postJson(BASE . '/Caja/anularPedidoMesa', array('id_mesa' => $idMesa, 'motivo' => 'Prueba automática'));
} else {
	revisar('se anulan las 2 mesas de una vez', $c === 200 && count($r['mesas'] ?? array()) === 2 && !($r['fallidas'] ?? array()), "código $c: " . substr($b, 0, 300));
	$conComanda = 0;
	foreach ($r['mesas'] ?? array() as $m) if (!empty($m['comanda_id'])) $conComanda++;
	revisar('cada mesa trae su comanda de anulación', $conComanda === 2);
	list(, $b) = pedir(BASE . '/Caja/mesas');
	$siguenOcupadas = 0;
	foreach ((json($b)['mesas'] ?? array()) as $m) if (in_array((int)$m['id_mesa'], $libres, true) && !empty($m['con_pedido'])) $siguenOcupadas++;
	revisar('las 2 mesas quedan libres', $siguenOcupadas === 0);
	list($c, , $b) = postJson(BASE . '/Caja/anularVariasMesas', array('ids' => $libres, 'motivo' => 'Prueba automática', 'autorizacion' => $autorizacion));
	revisar('repetirlo avisa que ya no tienen pedido', $c === 409 && strpos($b, 'pedido') !== false, "código $c: " . substr($b, 0, 200));
}
pedir(BASE . '/Configuraciones/guardar', array('esperas_anular_todas_web' => '0', 'mesas_anular_varias_web' => '0'));
terminar('anular varias');
