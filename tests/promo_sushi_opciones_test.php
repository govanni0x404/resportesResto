<?php
/**
 * Prueba de las promos de sushi de la v1.32.0 (cambios.md): agregar varios productos y toda una familia, salsas aparte,
 * máximos (envoltorios por roll, extras, salsas), precio según el envoltorio y promo fija.
 *
 * ¡CREA UNA PROMO Y PRODUCTOS, CAMBIA CONFIGURACIONES Y VENDE! Solo con una COPIA de la base:
 *   php tests/promo_sushi_opciones_test.php http://localhost:8002/index.php COPIA
 */
require __DIR__ . '/lib_pruebas.php';
if (($argv[2] ?? '') !== 'COPIA') {
	echo "Esta prueba crea promos y vende. Córrela solo contra una COPIA de la base:\n"
		. "  php tests/promo_sushi_opciones_test.php http://localhost:8002/index.php COPIA\n";
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
function ps($accion, $datos = array()) { list($c, $b) = pedir(BASE . '/Productos/' . $accion, $datos); return array($c, json($b), $b); }

echo "Configuraciones:\n";
list($c) = pedir(BASE . '/Configuraciones/guardar', array('sushi_salsas_web' => '1', 'sushi_bases_web' => '0', 'sushi_por_pieza_web' => '0'));
list(, $d) = ps('obtenerPromosSushi');
revisar('salsas encendidas, bases y por pieza apagadas', ($d['config']['salsas'] ?? null) === true && ($d['config']['bases'] ?? null) === false && ($d['config']['por_pieza'] ?? null) === false,
	json_encode($d['config'] ?? null));
$fam = 0;
foreach ($d['familias'] as $f) { $fam = (int)$f['id_familia']; break; }
list($c, $r, $b) = ps('grabarPromoSushi', array('id_producto' => 0, 'nombre' => 'PRUEBA PIEZA ' . rand(100, 999), 'piezas' => 20, 'precio' => 5000, 'paso' => 1,
	'max_rellenos' => 3, 'id_familia' => $fam, 'id_dimpresion' => 0, 'vigente' => 1, 'modo' => 'pieza'));
revisar('con "por pieza" apagado no se crea una promo por pieza', $c === 400, "código $c: " . substr($b, 0, 150));

echo "Crear la promo:\n";
$nombre = 'PRUEBA ROLLS ' . rand(1000, 9999);
list($c, $r, $b) = ps('grabarPromoSushi', array('id_producto' => 0, 'nombre' => $nombre, 'rolls' => 2, 'piezas_rol' => 10, 'precio' => 9000, 'paso' => 10,
	'max_rellenos' => 2, 'id_familia' => $fam, 'id_dimpresion' => 0, 'vigente' => 1, 'modo' => 'rol',
	'precio_envoltorio' => 1, 'max_envoltorios' => 2, 'max_extras' => 1, 'max_salsas' => 2));
$idPromo = (int)($r['id_producto'] ?? 0);
revisar('promo por roll creada con precio según envoltorio y máximos', $c === 200 && $idPromo > 0, "código $c: " . substr($b, 0, 200));
if (!$idPromo) terminar('promos de sushi');
list(, $p) = ps('obtenerPromoSushi', array('id_producto' => $idPromo));
revisar('al reabrirla trae los máximos y el precio por envoltorio', !empty($p['promo']['precio_envoltorio']) && $p['promo']['max_envoltorios'] === 2
	&& $p['promo']['max_extras'] === 1 && $p['promo']['max_salsas'] === 2, json_encode($p['promo'] ?? null));

// Opciones: se crean productos nuevos (crearOpcion) y se agregan varios a la vez
$crear = function ($tipo, $n, $valor) use ($idPromo) {
	list(, $r) = ps('crearOpcionSushi', array('id_promo' => $idPromo, 'tipo' => $tipo, 'nombre' => $n, 'valor' => $valor));
	return (int)($r['id_producto'] ?? 0);
};
$sufijo = rand(1000, 9999);
$idsRell = array();
foreach (array('POLLO', 'PALTA', 'QUESO') as $n) { $idsRell[] = $crear('relleno', "PRU $n $sufijo", 0); }
$crear('envoltorio', "PRU NORI $sufijo", 0);
$crear('envoltorio', "PRU PANKO $sufijo", 0);
$crear('envoltorio', "PRU TEMPURA $sufijo", 0);   // sin precio: el roll lleva su parte del precio de la promo
$crear('extra', "PRU GYOZA $sufijo", 500);
$crear('salsa', "PRU SOYA $sufijo", 0);
$crear('salsa', "PRU TERIYAKI $sufijo", 300);
list(, $p) = ps('obtenerPromoSushi', array('id_producto' => $idPromo));
revisar('las salsas quedan aparte de los extras', count($p['opciones']['salsa'] ?? array()) === 2 && count($p['opciones']['extra'] ?? array()) === 1);

// Varios a la vez en "envoltorio": los rellenos creados recién (como si se marcaran en la lista)
list($c, $r, $b) = ps('agregarVariasSushi', array('id_promo' => $idPromo, 'tipo' => 'base', 'ids' => $idsRell, 'valor' => 0));
revisar('agregar varios a la vez (3 marcados)', $c === 200 && ($r['agregados'] ?? 0) === 3, "código $c: " . substr($b, 0, 200));
list($c, $r) = ps('agregarVariasSushi', array('id_promo' => $idPromo, 'tipo' => 'base', 'ids' => $idsRell, 'valor' => 0));
revisar('repetirlo no los duplica (se saltan)', ($r['agregados'] ?? -1) === 0 && ($r['saltados'] ?? 0) === 3);
list($c, $r, $b) = ps('agregarVariasSushi', array('id_promo' => $idPromo, 'tipo' => 'base', 'id_familia' => $fam, 'valor' => 0));
revisar('agregar toda una familia de una vez', $c === 200 && isset($r['agregados']), "código $c: " . substr($b, 0, 200));

// Precio de cada roll con su envoltorio
list(, $p) = ps('obtenerPromoSushi', array('id_producto' => $idPromo));
foreach ($p['opciones']['envoltorio'] as $o) {
	if (strpos($o['nombre'], 'NORI') !== false) ps('precioOpcionSushi', array('id_opcion' => $o['id_opcion'], 'precio' => 8000));
	if (strpos($o['nombre'], 'PANKO') !== false) ps('precioOpcionSushi', array('id_opcion' => $o['id_opcion'], 'precio' => 9500));
}

echo "Límites de cada sección (parte 2):\n";
list($c, $r) = ps('limitePromoSushi', array('id_promo' => $idPromo, 'tipo' => 'base', 'valor' => 2));
revisar('máximo de bases por roll = 2', $c === 200 && ($r['valor'] ?? 0) === 2, "código $c");
list($c) = ps('limitePromoSushi', array('id_promo' => $idPromo, 'tipo' => 'envoltorio', 'valor' => 9));
revisar('9 envoltorios por roll se rechaza (máximo 5)', $c === 400, "código $c");
list($c, $r) = ps('limitePromoSushi', array('id_promo' => $idPromo, 'tipo' => 'extra', 'valor' => 1));
list(, $p) = ps('obtenerPromoSushi', array('id_producto' => $idPromo));
revisar('al reabrir la promo trae los límites guardados', ($p['promo']['max_bases'] ?? 0) === 2 && ($p['promo']['max_extras'] ?? 0) === 1);
// v1.32.5: 0 es 0 (la sección no se ofrece) y "sin límite" se guarda como -1
list($c, $r) = ps('limitePromoSushi', array('id_promo' => $idPromo, 'tipo' => 'salsa', 'valor' => -1));
revisar('"Sin límite" en salsas se guarda como -1', $c === 200 && ($r['valor'] ?? 0) === -1, "código $c");
ps('limitePromoSushi', array('id_promo' => $idPromo, 'tipo' => 'salsa', 'valor' => 0));
list(, $b) = pedir(BASE . '/Caja/catalogo?id_lprecio=1');
$suCero = null;
foreach (json($b)['sushis'] ?? array() as $x) if ((int)$x['id_producto'] === $idPromo) $suCero = $x;
revisar('salsas con máximo 0: la Caja no ofrece salsas', $suCero && count($suCero['salsas']) === 0 && $suCero['max_salsas'] === 0, json_encode($suCero['max_salsas'] ?? null));
ps('limitePromoSushi', array('id_promo' => $idPromo, 'tipo' => 'salsa', 'valor' => 2));

echo "Vender en la Caja:\n";
list(, $b) = pedir(BASE . '/Caja/estado');
if (strpos($b, '"caja":null') !== false) pedir(BASE . '/Caja/abrir', array('fondo' => '0'));
list(, $b) = pedir(BASE . '/Caja/catalogo?id_lprecio=1');
$cat = json($b);
$su = null;
foreach ($cat['sushis'] ?? array() as $x) if ((int)$x['id_producto'] === $idPromo) $su = $x;
revisar('la Caja recibe la promo con salsas y sin bases (apagadas)', $su && count($su['salsas']) === 2 && count($su['bases']) === 0 && $su['max_envoltorios'] === 2,
	json_encode(array_keys($su ?? array())));
if (!$su) terminar('promos de sushi');
$idEnv = function ($n) use ($su) { foreach ($su['envoltorios'] as $o) if (strpos($o['nombre'], $n) !== false) return $o['id']; return 0; };
$idOp = function ($lista, $n) use ($su) { foreach ($su[$lista] as $o) if (strpos($o['nombre'], $n) !== false) return $o['id']; return 0; };
$rell = $su['rellenos'];
$grupo = function ($envs) use ($rell) { return array('piezas' => 10, 'base' => 0, 'envoltorios' => $envs, 'rellenos' => array($rell[0]['id'], $rell[1]['id']), 'nota' => ''); };
$efectivo = null; $doc = null;
foreach ($cat['formas_pago'] as $f) if (!empty($f['efectivo'])) { $efectivo = $f; break; }
foreach ($cat['documentos'] as $dd) if (empty($dd['electronico']) && empty($dd['requiere_cliente']) && !empty($dd['habilitado'])) { $doc = $dd; break; }
$vender = function ($sushi, $total) use ($idPromo, $efectivo, $doc) {
	return postJson(BASE . '/Caja/vender', array('id_mesa' => 0, 'parcial' => null, 'id_tdocumento' => (int)$doc['id_tdocumento'], 'id_lprecio' => 1, 'id_cliente' => 0,
		'descuento' => 0, 'propina' => 0, 'observacion' => 'PRUEBA AUTOMATICA', 'retira' => '', 'delivery' => null, 'id_delivery' => 0, 'factura' => null,
		'detalle' => array(array('id_producto' => $idPromo, 'cantidad' => 1, 'agregados' => array(), 'nota' => '', 'notas_agregados' => new stdClass(), 'menu' => array(), 'sushi' => $sushi)),
		'pagos' => array(array('id_fpago' => (int)$efectivo['id_fpago'], 'monto' => $total)), 'token' => bin2hex(random_bytes(16))));
};
$nori = $idEnv('NORI'); $panko = $idEnv('PANKO');
list($c, , $b) = $vender(array('grupos' => array($grupo(array($nori, $panko)), $grupo(array($nori))), 'extras' => array(array('id' => $idOp('extras', 'GYOZA'), 'cantidad' => 2)), 'salsas' => array()), 20000);
revisar('2 extras con máximo 1: se rechaza', $c === 400 && strpos($b, 'hasta 1 extras') !== false, "código $c: " . substr($b, 0, 200));
list($c, , $b) = $vender(array('grupos' => array($grupo(array($nori, $panko, $nori)), $grupo(array($nori))), 'extras' => array(), 'salsas' => array()), 20000);
revisar('un envoltorio repetido se rechaza', $c === 400, "código $c: " . substr($b, 0, 200));
list($c, , $b) = $vender(array('grupos' => array($grupo(array($nori, $panko)), $grupo(array($nori))), 'extras' => array(),
	'salsas' => array(array('id' => $idOp('salsas', 'SOYA'), 'cantidad' => 2), array('id' => $idOp('salsas', 'TERIYAKI'), 'cantidad' => 1))), 20000);
revisar('3 salsas con máximo 2: se rechaza', $c === 400 && strpos($b, 'hasta 2 salsas') !== false, "código $c: " . substr($b, 0, 200));
// Cada roll con su precio: roll 1 NORI+PANKO (el más caro, $9.500) y roll 2 NORI ($8.000) + 1 teriyaki ($300) = $17.800
$selA = array('grupos' => array($grupo(array($nori, $panko)), $grupo(array($nori))), 'extras' => array(),
	'salsas' => array(array('id' => $idOp('salsas', 'TERIYAKI'), 'cantidad' => 1)));
list($c, , $b) = $vender($selA, 17799);
revisar('pagar $17.799 no alcanza (el total es $17.800)', $c >= 400, "código $c: " . substr($b, 0, 200));
list($c, $r, $b) = $vender($selA, 17800);
revisar('cada roll con el precio de su envoltorio: $9.500 + $8.000 + salsa $300 = $17.800', $c === 200 && !empty($r['id_venta']), "código $c: " . substr($b, 0, 300));
// v1.45.0 (pedido del usuario: "el menú déjalo como estaba; las promos en tablas aparte"): la promo de sushi se graba con sus
// propias marcas (es_promo_sushi_web / es_detalle_promo_sushi_web) y se une en detalle_promo_sushi_web, no con las del menú
$psqlSu = getenv('PRUEBA_PSQL') ?: '';
$uno = function ($q) use ($psqlSu) { return trim((string)shell_exec($psqlSu . ' -At -c ' . escapeshellarg($q) . ' 2>&1')); };
if ($psqlSu !== '') {
	$idVsu = (int)($r['id_venta'] ?? 0);
	$m = $uno("SELECT sum((es_promo_sushi_web IS TRUE)::int) || ',' || sum((es_detalle_promo_sushi_web IS TRUE)::int) || ',' || sum((es_menu IS TRUE OR es_detalle_menu IS TRUE)::int) || ',' || count(*) FROM detalle_venta WHERE id_venta = $idVsu");
	$t = $uno("SELECT count(*) FROM detalle_promo_sushi_web WHERE id_venta = $idVsu AND id_producto_promo = $idPromo");
	list($padres, $hijos, $menu, $filas) = array_map('intval', explode(',', $m . ',,,'));
	revisar('venta directa: la promo de sushi con es_promo_sushi_web (1) y sus productos con es_detalle_promo_sushi_web, sin marcas del menú',
		$padres === 1 && $hijos === $filas - 1 && $hijos > 0 && $menu === 0, "marcas $m");
	revisar('y cada producto unido a la promo en detalle_promo_sushi_web', (int)$t === $hijos, "tabla $t, hijos $hijos");
	$bolSu = html_entity_decode(strip_tags(pedir(BASE . '/Caja/ticket/' . $idVsu, null, false)[1]));
	revisar('en la boleta los productos de la promo salen debajo con "+", como antes', mb_substr_count($bolSu, '+ ') >= $hijos, substr($bolSu, 0, 300));

	// En una mesa: enviar, ver unida, cobrar
	list(, $b) = pedir(BASE . '/Caja/mesas');
	$mesaSu = 0;
	foreach ((json($b)['mesas'] ?? array()) as $mm) if ($mm['estado'] === 'libre' && empty($mm['con_pedido']) && empty($mm['reserva'])) { $mesaSu = (int)$mm['id_mesa']; break; }
	if ($mesaSu) {
		list($c, , $b) = postJson(BASE . '/Caja/enviarMesa', array('id_mesa' => $mesaSu, 'id_lprecio' => 1, 'observacion' => '', 'detalle' => array(
			array('id_producto' => $idPromo, 'cantidad' => 1, 'agregados' => array(), 'nota' => '', 'notas_agregados' => new stdClass(), 'menu' => array(), 'sushi' => $selA))));
		revisar('enviar la promo de sushi a una mesa', $c === 200, "código $c: " . substr($b, 0, 200));
		$cotSu = (int)$uno("SELECT id_cotizacion FROM mesa WHERE id_mesa = $mesaSu");
		$m = $uno("SELECT sum((es_promo_sushi_web IS TRUE)::int) || ',' || sum((es_detalle_promo_sushi_web IS TRUE)::int) || ',' || sum((es_menu IS TRUE OR es_detalle_menu IS TRUE)::int) || ',' || sum((COALESCE(id_detalle_menu_web,0) > 0)::int) FROM detalle_cotizacion WHERE id_cotizacion = $cotSu AND vigente IS NOT FALSE");
		$hijosMesa = (int)explode(',', $m)[1];
		revisar('en el pedido: con sus marcas, sin las del menú ni la columna id_detalle_menu_web', strpos($m, '1,') === 0 && $hijosMesa > 0 && substr($m, -4) === ',0,0', "marcas $m");
		revisar('y unidos en detalle_promo_sushi_web', (int)$uno("SELECT count(*) FROM detalle_promo_sushi_web d JOIN detalle_cotizacion c ON c.id_detalle_cotizacion = d.id_detalle_cotizacion_producto WHERE c.id_cotizacion = $cotSu") === $hijosMesa);
		list(, $b) = pedir(BASE . '/Caja/detalleMesa?id_mesa=' . $mesaSu);
		$ls = json($b)['lineas'] ?? array();
		$padreSu = 0; $unidos = 0;
		foreach ($ls as $l) if (!empty($l['es_menu'])) $padreSu = (int)$l['id_detalle'];
		foreach ($ls as $l) if (!empty($l['es_detalle_menu']) && (int)$l['menu_de'] === $padreSu) $unidos++;
		revisar('la mesa la muestra como antes: la promo y sus productos unidos debajo', $padreSu > 0 && $unidos === $hijosMesa, "unidos $unidos de $hijosMesa");
		$totalMesa = (int)$uno("SELECT COALESCE(sum(total_bruto),0) FROM detalle_cotizacion WHERE id_cotizacion = $cotSu AND vigente IS NOT FALSE AND esanulado IS NOT TRUE AND eseliminado IS NOT TRUE");
		list($c, $r2, $b) = postJson(BASE . '/Caja/vender', array('id_mesa' => $mesaSu, 'parcial' => null, 'id_tdocumento' => (int)$doc['id_tdocumento'], 'id_lprecio' => 1,
			'id_cliente' => 0, 'descuento' => 0, 'propina' => 0, 'observacion' => 'PRUEBA AUTOMATICA', 'retira' => '', 'delivery' => null, 'id_delivery' => 0, 'factura' => null,
			'detalle' => array(), 'pagos' => array(array('id_fpago' => (int)$efectivo['id_fpago'], 'monto' => $totalMesa)), 'token' => bin2hex(random_bytes(16))));
		$idVm = (int)($r2['id_venta'] ?? 0);
		$m = $uno("SELECT sum((es_promo_sushi_web IS TRUE)::int) || ',' || sum((es_detalle_promo_sushi_web IS TRUE)::int) || ',' || sum((es_menu IS TRUE OR es_detalle_menu IS TRUE)::int) FROM detalle_venta WHERE id_venta = $idVm");
		revisar('cobrar la mesa ($17.800): la venta con sus marcas y unida en detalle_promo_sushi_web', $c === 200 && $totalMesa === 17800 && $m === '1,' . $hijosMesa . ',0'
			&& (int)$uno("SELECT count(*) FROM detalle_promo_sushi_web WHERE id_venta = $idVm") === $hijosMesa, "código $c, total $totalMesa, marcas $m: " . substr($b, 0, 200));
	} else {
		echo "  (no hay mesa libre: no se probó la promo de sushi en la mesa)\n";
	}
}
$tempura = $idEnv('TEMPURA');
list($c, , $b) = $vender(array('grupos' => array($grupo(array($tempura)), $grupo(array($panko))), 'extras' => array(), 'salsas' => array()), 13999);
revisar('roll con envoltorio sin precio = su parte de la promo: $4.500 + $9.500 = $14.000 ($13.999 no alcanza)', $c >= 400, "código $c");
list($c, , $b) = $vender(array('grupos' => array($grupo(array($tempura)), $grupo(array($panko))), 'extras' => array(), 'salsas' => array()), 14000);
revisar('y con $14.000 se cobra', $c === 200, "código $c: " . substr($b, 0, 200));

echo "Promo fija:\n";
$armado = array('grupos' => array($grupo(array($panko)), $grupo(array($nori))), 'extras' => array(), 'salsas' => array(array('id' => $idOp('salsas', 'SOYA'), 'cantidad' => 1)));
list($c, $r, $b) = postJson(BASE . '/Caja/fijarPromoSushi', array('id_producto' => $idPromo, 'fija' => true, 'sushi' => $armado));
revisar('dejarla fija desde la Caja con lo armado', $c === 200 && !empty($r['fija']), "código $c: " . substr($b, 0, 200));
list(, $b) = pedir(BASE . '/Caja/catalogo?id_lprecio=1');
foreach (json($b)['sushis'] as $x) if ((int)$x['id_producto'] === $idPromo) $su = $x;
revisar('la Caja recibe su armado fijo (2 rolls)', count($su['fijo']['grupos'] ?? array()) === 2 && count($su['fijo']['salsas'] ?? array()) === 1, json_encode($su['fijo'] ?? null));
list(, $p) = ps('obtenerPromoSushi', array('id_producto' => $idPromo));
revisar('en Carta > Promos Sushi sale como fija', !empty($p['promo']['fija']));
list($c, , $b) = postJson(BASE . '/Caja/fijarPromoSushi', array('id_producto' => $idPromo, 'fija' => true, 'sushi' => array('grupos' => array($grupo(array($nori))), 'extras' => array())));
revisar('un armado incompleto no se deja fijo', $c === 400, "código $c: " . substr($b, 0, 200));
$datosPromo = array('id_producto' => $idPromo, 'nombre' => $nombre, 'rolls' => 2, 'piezas_rol' => 10, 'precio' => 9000, 'paso' => 10, 'max_rellenos' => 2,
	'id_familia' => $fam, 'id_dimpresion' => 0, 'vigente' => 1, 'modo' => 'rol', 'precio_envoltorio' => 1, 'max_envoltorios' => 2, 'max_extras' => 1, 'max_salsas' => 2);
list($c) = ps('quitarFijaSushi', array('id_promo' => $idPromo));
list(, $p) = ps('obtenerPromoSushi', array('id_producto' => $idPromo));
revisar('"Quitar fija" en la Carta la deja para armar otra vez', $c === 200 && empty($p['promo']['fija']));

// Dejar la copia como estaba
ps('grabarPromoSushi', array_merge($datosPromo, array('vigente' => 0)));
pedir(BASE . '/Configuraciones/guardar', array('sushi_salsas_web' => '0', 'sushi_bases_web' => '1', 'sushi_por_pieza_web' => '1'));
terminar('promos de sushi');
