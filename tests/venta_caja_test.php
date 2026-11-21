<?php
/**
 * Prueba de la plata de la Caja (v1.27.0): abrir, cobrar, no cobrar dos veces, anular y cerrar.
 *
 * ¡GRABA VENTAS Y CIERRA LA CAJA! Úsala SOLO en una COPIA de prueba de la base, nunca en la de un cliente.
 * Por eso pide la palabra COPIA al final:
 *
 *   1) web apuntando a la copia:   MIPDV_DB_LOCALHOST_PORT=5440 php -S localhost:8002 router.php
 *   2) en otra consola:            php tests/venta_caja_test.php http://localhost:8002/index.php COPIA
 *
 * Revisa:
 *  - la venta queda registrada con su folio y la caja suma el total y el efectivo (con vuelto);
 *  - repetir el mismo cobro (doble clic, red lenta) NO registra otra venta;
 *  - un pago que no alcanza se rechaza y no graba nada;
 *  - la boleta (ticket) sale con el total y sale rápido (la impresión no se frena);
 *  - al anular, la venta sale de los totales de la caja;
 *  - el cierre cuadra (diferencia 0 contando lo esperado) y la caja queda cerrada.
 */
require __DIR__ . '/lib_pruebas.php';
if (($argv[2] ?? '') !== 'COPIA') {
	echo "Esta prueba graba ventas y cierra la caja. Córrela solo contra una COPIA de la base:\n"
		. "  php tests/venta_caja_test.php http://localhost:8002/index.php COPIA\n";
	exit(1);
}
entrar();

function caja($cuerpo = null)
{
	if ($cuerpo === null) { list(, $cuerpo) = pedir(BASE . '/Caja/estado'); }
	$j = json($cuerpo);
	return $j['caja'] ?? null;
}
function vender($venta)
{
	$ch = curl_init(BASE . '/Caja/vender');
	curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $GLOBALS['__cookies'], CURLOPT_COOKIEFILE => $GLOBALS['__cookies'],
		CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($venta), CURLOPT_TIMEOUT => 60,
		CURLOPT_HTTPHEADER => array('Content-Type: application/json', 'X-Requested-With: XMLHttpRequest', 'X-CSRF-Token: ' . $GLOBALS['__token'])));
	$b = (string)curl_exec($ch);
	$c = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	return array($c, json($b), $b);
}

echo "Abrir la caja:\n";
$c0 = caja();
if (!$c0) {
	list(, $b) = pedir(BASE . '/Caja/estado');
	$ap = json($b);
	$control = 0;
	foreach (array('cajas', 'controles', 'cajas_libres') as $k) if (!empty($ap[$k][0]['id_control_caja'])) { $control = (int)$ap[$k][0]['id_control_caja']; break; }
	list($c, $b) = pedir(BASE . '/Caja/abrir', array('fondo' => '10.000', 'id_control_caja' => $control));
	revisar('abrir la caja con fondo $10.000', $c === 200 && !empty(json($b)['ok']), "código $c: " . substr($b, 0, 200));
	list($c, $b) = pedir(BASE . '/Caja/abrir', array('fondo' => 'abc'));
	revisar('un fondo con letras se rechaza', $c >= 400, "código $c");
	$c0 = caja();
}
revisar('hay una caja abierta', is_array($c0), 'no se pudo abrir');
if (!$c0) terminar('venta en la Caja');

list(, $b) = pedir(BASE . '/Caja/catalogo?id_lprecio=1');
$cat = json($b);
$especiales = array();
foreach (array_merge($cat['menus'] ?? array(), $cat['sushis'] ?? array()) as $x) $especiales[(int)$x['id_producto']] = true;
$prod = null;
foreach ($cat['productos'] ?? array() as $p) {
	if ($p['precio'] > 0 && empty($p['fraccion']) && empty($p['pedir_cantidad']) && empty($p['exento']) && !isset($especiales[(int)$p['id_producto']])) { $prod = $p; break; }
}
$efectivo = null;
foreach ($cat['formas_pago'] ?? array() as $f) if (!empty($f['efectivo'])) { $efectivo = $f; break; }
$doc = null;
foreach ($cat['documentos'] ?? array() as $d) if (empty($d['electronico']) && empty($d['requiere_cliente']) && !empty($d['habilitado'])) { $doc = $d; break; }
revisar('el catálogo tiene un producto simple, efectivo y un comprobante interno', $prod && $efectivo && $doc);
if (!$prod || !$efectivo || !$doc) terminar('venta en la Caja');

echo "Cobrar (2 x {$prod['nombre']} a \${$prod['precio']}, paga con " . ($prod['precio'] * 2 + 1000) . "):\n";
$total = $prod['precio'] * 2;
$token = bin2hex(random_bytes(16));
$venta = array('id_mesa' => 0, 'parcial' => null, 'id_tdocumento' => (int)$doc['id_tdocumento'], 'id_lprecio' => 1, 'id_cliente' => 0,
	'descuento' => 0, 'propina' => 0, 'observacion' => 'PRUEBA AUTOMATICA', 'retira' => '', 'delivery' => null, 'id_delivery' => 0, 'factura' => null,
	'detalle' => array(array('id_producto' => (int)$prod['id_producto'], 'cantidad' => 2, 'agregados' => array(), 'nota' => '', 'notas_agregados' => new stdClass(), 'menu' => array(), 'sushi' => null)),
	'pagos' => array(array('id_fpago' => (int)$efectivo['id_fpago'], 'monto' => $total + 1000)), 'token' => $token);

$t = microtime(true);
list($c, $r, $b) = vender($venta);
$msVenta = round((microtime(true) - $t) * 1000);
$idVenta = (int)($r['id_venta'] ?? 0);
revisar('la venta se registra y trae su número', $c === 200 && $idVenta > 0, "código $c: " . substr($b, 0, 300));
revisar("y responde rápido ($msVenta ms, máximo 3000)", $msVenta < 3000);
$c1 = caja();
revisar('la caja suma el total de la venta', (int)$c1['ventas'] === (int)$c0['ventas'] + $total, "antes {$c0['ventas']}, después {$c1['ventas']}, venta $total");
revisar('la caja cuenta una venta más', (int)$c1['cantidad_ventas'] === (int)$c0['cantidad_ventas'] + 1);
revisar('el efectivo esperado sube el total (el vuelto no queda en la caja)', (int)$c1['efectivo_esperado'] === (int)$c0['efectivo_esperado'] + $total,
	"antes {$c0['efectivo_esperado']}, después {$c1['efectivo_esperado']}");

list($c, $r2, $b) = vender($venta);
$c2 = caja();
revisar('repetir el mismo cobro (doble clic) no registra otra venta', (int)$c2['cantidad_ventas'] === (int)$c1['cantidad_ventas'] && (int)$c2['ventas'] === (int)$c1['ventas'],
	"código $c, ventas {$c1['cantidad_ventas']} -> {$c2['cantidad_ventas']}");
revisar('y devuelve la misma venta', (int)($r2['id_venta'] ?? 0) === $idVenta || $c >= 400, substr($b, 0, 200));

$corta = $venta;
$corta['token'] = bin2hex(random_bytes(16));
$corta['pagos'][0]['monto'] = $total - 1;
list($c, , $b) = vender($corta);
$c3 = caja();
revisar('un pago que no alcanza se rechaza', $c >= 400, "código $c");
revisar('y no graba nada', (int)$c3['cantidad_ventas'] === (int)$c2['cantidad_ventas']);

echo "Boleta (ticket) de la venta:\n";
$t = microtime(true);
list($c, $html) = pedir(BASE . '/Caja/ticket/' . $idVenta, null, false);
$msTicket = round((microtime(true) - $t) * 1000);
revisar('el ticket abre', $c === 200, "código $c");
revisar('y muestra el total $' . number_format($total, 0, ',', '.'), strpos($html, number_format($total, 0, ',', '.')) !== false);
revisar("y sale rápido ($msTicket ms, máximo 1500)", $msTicket < 1500);

list($c, $b) = pedir(BASE . '/Caja/ventas');
$ids = array_map(function ($v) { return (int)($v['id_venta'] ?? 0); }, json($b)['ventas'] ?? array());
revisar('la venta aparece en Ventas de la caja', in_array($idVenta, $ids, true));

echo "Anular:\n";
list($c, $b) = pedir(BASE . '/Caja/anular', array('id_venta' => $idVenta, 'motivo' => ''));
revisar('anular sin motivo se rechaza', $c >= 400, "código $c");
// Si la empresa pide clave de administrador para anular: PRUEBA_CLAVE_ADMIN=la_clave php tests/venta_caja_test.php ...
$aut = getenv('PRUEBA_CLAVE_ADMIN') ? array('clave' => getenv('PRUEBA_CLAVE_ADMIN')) : '';
list($c, $b) = pedir(BASE . '/Caja/anular', array('id_venta' => $idVenta, 'motivo' => 'Prueba automática', 'autorizacion' => $aut));
$ra = json($b);
if ($c === 403 && !empty($ra['autorizacion'])) {
	echo "  (la empresa pide clave de administrador para anular: no se probó. Dala con PRUEBA_CLAVE_ADMIN=...)\n";
} else {
	revisar('la venta se anula', $c === 200 && !empty($ra['ok']), "código $c: " . substr($b, 0, 300));
	$c4 = caja();
	revisar('la caja descuenta la venta anulada', (int)$c4['ventas'] === (int)$c0['ventas'], "inicio {$c0['ventas']}, ahora {$c4['ventas']}");
	revisar('y el efectivo esperado vuelve al de antes', (int)$c4['efectivo_esperado'] === (int)$c0['efectivo_esperado']);
	list($c, $b) = pedir(BASE . '/Caja/anular', array('id_venta' => $idVenta, 'motivo' => 'otra vez'));
	revisar('no se puede anular dos veces', $c >= 400, "código $c");
}

echo "Cerrar la caja:\n";
$cf = caja();
$declarado = array();
foreach ($cf['por_forma_pago'] ?? array() as $f) if (empty($f['efectivo'])) $declarado[(int)$f['id_fpago']] = (int)$f['monto'];
// El cajero cuenta lo que hay; si el esperado es negativo (se pagaron propinas de tarjeta en efectivo) cuenta $0
$contado = max(0, (int)$cf['efectivo_esperado']);
list($c, $b) = pedir(BASE . '/Caja/cerrar', array('efectivo_contado' => $contado, 'declarado' => $declarado, 'fondo_siguiente' => ''));
$rc = json($b);
if ($c === 409) {
	echo "  (no se puede cerrar: " . ($rc['error'] ?? $b) . ")\n";
} else {
	revisar('la caja se cierra', $c === 200 && !empty($rc['id_caja']), "código $c: " . substr($b, 0, 300));
	revisar('y queda cerrada', caja() === null);
	list($c, $html) = pedir(BASE . '/Caja/cierreTicket/' . (int)($rc['id_caja'] ?? 0), null, false);
	revisar('el ticket de cierre abre', $c === 200, "código $c");
}

terminar('venta en la Caja');
