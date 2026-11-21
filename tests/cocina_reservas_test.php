<?php
/**
 * Prueba de la pantalla de cocina y de las reservas (v1.28.0, PRUEBA en la rama prueba/pantalla-cocina).
 *
 * ¡GRABA PEDIDOS, VENTAS Y RESERVAS! Solo con una COPIA de la base (pide la palabra COPIA):
 *   php tests/cocina_reservas_test.php http://localhost:8002/index.php COPIA
 *
 * Cocina: un pedido enviado a una mesa y una venta directa aparecen en Cocina/pendientes; "Listo" los saca, "Deshacer"
 * los devuelve; el filtro por destino funciona; responde rápido.
 * Reservas: se crea, se rechaza una hora mala o la misma mesa a menos de 2 horas, se cambia el estado y se lista por día.
 * v1.29.0: Configuraciones las enciende y apaga; "Tomar" deja "Siendo preparado por"; una reserva bloquea su mesa en la
 * Caja desde los minutos configurados y se desbloquea cuando llega el cliente.
 */
require __DIR__ . '/lib_pruebas.php';
if (($argv[2] ?? '') !== 'COPIA') {
	echo "Esta prueba graba pedidos y reservas. Córrela solo contra una COPIA de la base:\n  php tests/cocina_reservas_test.php http://localhost:8002/index.php COPIA\n";
	exit(1);
}
entrar();
pedir(BASE . '/Login/postLoginWarmup', array());   // migraciones (crea cocina_listo_web y reserva_web)

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
function pendientes($destino = 0)
{
	list($c, $b) = pedir(BASE . '/Cocina/pendientes?destino=' . (int)$destino);
	return array($c, json($b)['comandas'] ?? null);
}
function buscar($comandas, $prefijo)
{
	foreach ((array)$comandas as $c) if (strpos($c['clave'], $prefijo) === 0) return $c;
	return null;
}

function configurar($datos)
{
	list($c, $b) = pedir(BASE . '/Configuraciones/guardar', $datos);
	return $c;
}

echo "Encender y apagar en Configuraciones:\n";
configurar(array('_casillas' => 'usar_cocina_web,usar_reservas_web'));   // las dos apagadas
list($c, ) = pedir(BASE . '/Cocina/pendientes');
revisar('con la cocina apagada, la pantalla no entrega comandas', $c === 409, "código $c");
list($c, ) = pedir(BASE . '/Reservas/listado');
revisar('con las reservas apagadas, no se listan', $c === 409, "código $c");
list($c, $html) = pedir(BASE . '/Dashboard', null, false);
revisar('y el menú no muestra Cocina ni Reservas', strpos($html, 'index.php/Cocina"') === false && strpos($html, 'index.php/Reservas"') === false);
revisar('Configuraciones guarda las dos casillas y los minutos', configurar(array('usar_cocina_web' => '1', 'usar_reservas_web' => '1',
	'minutos_bloqueo_reserva_web' => '60')) < 400);
list($c, $html) = pedir(BASE . '/Dashboard', null, false);
revisar('encendidas, el menú muestra Cocina y Reservas', strpos($html, 'index.php/Cocina"') !== false && strpos($html, 'index.php/Reservas"') !== false);

echo "Pantalla de cocina:\n";
list($c, $html) = pedir(BASE . '/Cocina', null, false);
revisar('la pantalla abre', $c === 200 && strpos($html, 'id="kGrilla"') !== false, "código $c");

// Caja abierta y un producto simple
list(, $b) = pedir(BASE . '/Caja/estado');
if (strpos($b, '"caja":null') !== false) pedir(BASE . '/Caja/abrir', array('fondo' => '0'));
list(, $b) = pedir(BASE . '/Caja/catalogo?id_lprecio=1');
$cat = json($b);
$esp = array();
foreach (array_merge($cat['menus'] ?? array(), $cat['sushis'] ?? array()) as $x) $esp[(int)$x['id_producto']] = true;
$prod = null;
foreach ($cat['productos'] ?? array() as $p) if ($p['precio'] > 0 && empty($p['fraccion']) && empty($p['pedir_cantidad']) && !isset($esp[(int)$p['id_producto']])) { $prod = $p; break; }
$ef = null;
foreach ($cat['formas_pago'] ?? array() as $f) if (!empty($f['efectivo'])) { $ef = $f; break; }
$doc = null;
foreach ($cat['documentos'] ?? array() as $d) if (empty($d['electronico']) && empty($d['requiere_cliente']) && !empty($d['habilitado'])) { $doc = $d; break; }
revisar('hay producto, efectivo y comprobante para probar', $prod && $ef && $doc);
if (!$prod || !$ef || !$doc) terminar('cocina y reservas');

// Mesa libre
list(, $b) = pedir(BASE . '/Caja/mesas');
$mesa = 0;
foreach ((json($b)['mesas'] ?? array()) as $m) if (!empty($m['disponible']) && $m['disponible'] !== 'f' && empty($m['venta_directa'])) { $mesa = (int)$m['id_mesa']; break; }
if (!$mesa) $mesa = 3;
list($c, $r, $b) = postJson(BASE . '/Caja/enviarMesa', array('id_mesa' => $mesa, 'id_lprecio' => 1, 'observacion' => '',
	'detalle' => array(array('id_producto' => (int)$prod['id_producto'], 'cantidad' => 2, 'agregados' => array(), 'nota' => 'SIN SAL', 'menu' => array(), 'sushi' => null))));
$idCot = (int)($r['id_cotizacion'] ?? 0);
revisar("se envía un pedido a la mesa $mesa", $c === 200 && $idCot > 0, "código $c: " . substr($b, 0, 300));

list($c, $r, $b) = postJson(BASE . '/Caja/vender', array('id_mesa' => 0, 'id_tdocumento' => (int)$doc['id_tdocumento'], 'id_lprecio' => 1, 'id_cliente' => 0,
	'descuento' => 0, 'propina' => 0, 'observacion' => '', 'retira' => 'PRUEBA COCINA', 'delivery' => null, 'id_delivery' => 0, 'factura' => null,
	'detalle' => array(array('id_producto' => (int)$prod['id_producto'], 'cantidad' => 1, 'agregados' => array(), 'nota' => '', 'menu' => array(), 'sushi' => null)),
	'pagos' => array(array('id_fpago' => (int)$ef['id_fpago'], 'monto' => (int)$prod['precio'])), 'token' => bin2hex(random_bytes(16))));
$idVenta = (int)($r['id_venta'] ?? 0);
revisar('se hace una venta directa', $c === 200 && $idVenta > 0, "código $c: " . substr($b, 0, 300));

$t = microtime(true);
list($c, $coms) = pendientes();
$ms = round((microtime(true) - $t) * 1000);
revisar("Cocina/pendientes responde ($ms ms, máximo 800)", $c === 200 && is_array($coms) && $ms < 800, "código $c");
$cm = buscar($coms, 'c:' . $idCot . ':');
$cv = buscar($coms, 'v:' . $idVenta . ':');
revisar('el pedido de la mesa aparece en la cocina', $cm !== null);
revisar('con su cantidad, producto y nota', $cm && (float)$cm['lineas'][0]['cantidad'] === 2.0 && $cm['lineas'][0]['nota'] === 'SIN SAL', json_encode($cm));
revisar('la venta directa aparece en la cocina', $cv !== null && strpos($cv['detalle'], 'PRUEBA COCINA') !== false, json_encode($cv));

if ($cm) {
	$lineas = array_map(function ($l) { return array('origen' => 'c', 'id' => $l['id']); }, $cm['lineas']);
	// v1.29.0: tomar y soltar
	list($c, $b) = pedir(BASE . '/Cocina/tomar', array('lineas' => $lineas));
	list(, $coms) = pendientes();
	$tm = buscar($coms, 'c:' . $idCot . ':');
	revisar('"Tomar" deja la comanda "Siendo preparado por" el usuario', $c === 200 && $tm && $tm['tomado_por'] !== '', json_encode($tm['tomado_por'] ?? null));
	list($c, $b) = pedir(BASE . '/Cocina/tomar', array('lineas' => $lineas, 'soltar' => '1'));
	list(, $coms) = pendientes();
	$tm = buscar($coms, 'c:' . $idCot . ':');
	revisar('"Soltar" la deja libre', $c === 200 && $tm && $tm['tomado_por'] === '');
	pedir(BASE . '/Cocina/tomar', array('lineas' => $lineas));
	list($c, $b) = pedir(BASE . '/Cocina/listo', array('lineas' => $lineas));
	revisar('"Listo" marca la comanda', $c === 200 && (int)(json($b)['marcadas'] ?? 0) === count($lineas), "código $c: $b");
	list(, $coms) = pendientes();
	revisar('y ya no aparece', buscar($coms, 'c:' . $idCot . ':') === null);
	list($c, $b) = pedir(BASE . '/Cocina/listo', array('lineas' => $lineas));
	revisar('marcarla de nuevo no repite nada', $c === 200 && (int)(json($b)['marcadas'] ?? -1) === 0);
	list($c, $b) = pedir(BASE . '/Cocina/deshacer', array('lineas' => $lineas));
	list(, $coms) = pendientes();
	revisar('"Deshacer" la devuelve a la pantalla', $c === 200 && buscar($coms, 'c:' . $idCot . ':') !== null);
	pedir(BASE . '/Cocina/listo', array('lineas' => $lineas));
}
if ($cv) {
	$lineas = array_map(function ($l) { return array('origen' => 'v', 'id' => $l['id']); }, $cv['lineas']);
	pedir(BASE . '/Cocina/listo', array('lineas' => $lineas));
	list(, $coms) = pendientes();
	revisar('la venta directa también se marca lista', buscar($coms, 'v:' . $idVenta . ':') === null);
}
list($c, $coms) = pendientes(999999);
revisar('filtrar por un destino que no existe no muestra nada', $c === 200 && $coms === array());
list($c, ) = pedir(BASE . '/Cocina/listo', array('lineas' => array(array('origen' => 'x', 'id' => 1))));
revisar('un origen inválido no marca nada', $c === 200);

echo "Reservas:\n";
list($c, $html) = pedir(BASE . '/Reservas', null, false);
revisar('la pantalla abre', $c === 200 && strpos($html, 'id="rLista"') !== false, "código $c");
$dia = date('Y-m-d', strtotime('+' . random_int(30, 3000) . ' days'));   // un día distinto en cada vuelta: la prueba se puede repetir
$nueva = function ($datos, $id = 0) {
	list($c, $b) = pedir(BASE . '/Reservas/guardar', array('id' => $id, 'reserva' => $datos));
	return array($c, json($b), $b);
};
list($c, $r, $b) = $nueva(array('nombre' => 'Pérez', 'telefono' => '+56 9 1234 5678', 'fecha' => $dia, 'hora' => '21:00', 'personas' => 4, 'id_mesa' => 1, 'nota' => 'Cumpleaños'));
$idRes = (int)($r['reserva']['id'] ?? 0);
revisar('se crea una reserva', $c === 200 && $idRes > 0, "código $c: " . substr($b, 0, 200));
list($c, $r) = $nueva(array('nombre' => 'Soto', 'fecha' => $dia, 'hora' => '22:00', 'personas' => 2, 'id_mesa' => 1));
revisar('la misma mesa una hora después se rechaza', $c === 409, "código $c");
list($c, $r) = $nueva(array('nombre' => 'Soto', 'fecha' => $dia, 'hora' => '23:30', 'personas' => 2, 'id_mesa' => 1));
revisar('la misma mesa 2 horas y media después se acepta', $c === 200);
foreach (array(array('hora' => '25:00'), array('personas' => 0), array('nombre' => '  '), array('fecha' => '2026-02-31')) as $malo) {
	list($c, ) = $nueva(array_merge(array('nombre' => 'X', 'fecha' => $dia, 'hora' => '20:00', 'personas' => 2, 'id_mesa' => 0), $malo));
	revisar('se rechaza ' . json_encode($malo), $c === 400, "código $c");
}
list($c, $b) = pedir(BASE . '/Reservas/estado', array('id' => $idRes, 'estado' => 'llego'));
revisar('se marca "Llegó"', $c === 200 && (json($b)['reserva']['estado'] ?? '') === 'llego');
list($c, $b) = pedir(BASE . '/Reservas/estado', array('id' => $idRes, 'estado' => 'borrada'));
revisar('un estado inventado se rechaza', $c === 400);
list($c, $b) = pedir(BASE . '/Reservas/listado?fecha=' . $dia);
$l = json($b);
$nombres = array_column($l['reservas'] ?? array(), 'nombre');
revisar('el listado del día las trae por hora', $c === 200 && array_slice($nombres, 0, 2) === array('Pérez', 'Soto'), implode(', ', $nombres));
revisar('y el resumen del mes las cuenta', ($l['mes'][$dia]['reservas'] ?? 0) >= 2);


echo "Bloqueo de la mesa por reserva (Caja):\n";
list(, $b) = pedir(BASE . '/Caja/mesas');
$libre = 0;
foreach ((json($b)['mesas'] ?? array()) as $m) if ($m['estado'] === 'libre' && empty($m['con_pedido']) && empty($m['reserva'])) { $libre = (int)$m['id_mesa']; break; }
revisar('hay una mesa libre para probar', $libre > 0);
if ($libre) {
	$en30 = time() + 1800;   // la reserva es en 30 minutos: con 60 de bloqueo ya está bloqueada
	list($c, $r, $b) = $nueva(array('nombre' => 'Bloqueo', 'fecha' => date('Y-m-d', $en30), 'hora' => date('H:i', $en30), 'personas' => 3, 'id_mesa' => $libre));
	$idBloq = (int)($r['reserva']['id'] ?? 0);
	revisar('se reserva la mesa ' . $libre . ' para dentro de 30 minutos', $c === 200 && $idBloq > 0, substr($b, 0, 200));
	$mesaEnCaja = function ($id) {
		list(, $b) = pedir(BASE . '/Caja/mesas');
		foreach ((json($b)['mesas'] ?? array()) as $m) if ((int)$m['id_mesa'] === $id) return $m;
		return null;
	};
	$ms = $mesaEnCaja($libre);
	revisar('en la Caja la mesa sale bloqueada por la reserva', $ms && (int)($ms['reserva']['id'] ?? 0) === $idBloq, json_encode($ms['reserva'] ?? null));
	$pedido = array('id_mesa' => $libre, 'id_lprecio' => 1, 'observacion' => '',
		'detalle' => array(array('id_producto' => (int)$prod['id_producto'], 'cantidad' => 1, 'agregados' => array(), 'nota' => '', 'menu' => array(), 'sushi' => null)));
	list($c, $r, $b) = postJson(BASE . '/Caja/enviarMesa', $pedido);
	revisar('no se puede enviar un pedido a la mesa reservada', $c === 409 && stripos((string)($r['error'] ?? ''), 'reserva') !== false, "código $c: " . substr($b, 0, 200));
	configurar(array('minutos_bloqueo_reserva_web' => '10'));
	$ms = $mesaEnCaja($libre);
	revisar('con 10 minutos de bloqueo, una reserva en 30 minutos todavía no bloquea', $ms && empty($ms['reserva']));
	configurar(array('minutos_bloqueo_reserva_web' => '60'));
	list($c, $b) = pedir(BASE . '/Caja/reservaLlego', array('id_mesa' => $libre, 'id_reserva' => $idBloq));
	revisar('"Llegó el cliente" desbloquea la mesa', $c === 200, "código $c: $b");
	list($c, $r, $b) = postJson(BASE . '/Caja/enviarMesa', $pedido);
	revisar('y ya se le pueden enviar pedidos', $c === 200, "código $c: " . substr($b, 0, 200));
	list(, $b) = pedir(BASE . '/Reservas/listado?fecha=' . date('Y-m-d', $en30));
	$est = '';
	foreach ((json($b)['reservas'] ?? array()) as $x) if ((int)$x['id'] === $idBloq) $est = $x['estado'];
	revisar('la reserva queda como "llegó"', $est === 'llego', $est);
}

terminar('cocina y reservas');
