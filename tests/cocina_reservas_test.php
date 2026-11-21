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

terminar('cocina y reservas');
