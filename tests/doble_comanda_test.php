<?php
/**
 * Prueba de la doble comanda (v1.30.0): un producto que sale en DOS impresoras y un agregado que sale en la suya.
 *
 * ¡CREA DESTINOS DE IMPRESIÓN, CAMBIA PRODUCTOS Y ENVÍA PEDIDOS! Solo con una COPIA de la base:
 *   php tests/doble_comanda_test.php http://localhost:8002/index.php COPIA
 *
 * Con las mismas pantallas que usa el usuario (Destino de impresión, Carta y Agregados):
 *  - "ARROZ" va a COCINA y tiene "Imprimir también en otro destino" = PARRILLA;
 *  - su agregado "SALSA" (producto de la BARRA) tiene "Imprimir en su propio destino".
 * Envía el pedido a una mesa y revisa la comanda: una hoja por impresora (COCINA, PARRILLA y BARRA), cada una con lo
 * suyo, y que un producto sin nada marcado sigue saliendo en una sola. También el filtro de la pantalla de cocina.
 */
require __DIR__ . '/lib_pruebas.php';
if (($argv[2] ?? '') !== 'COPIA') {
	echo "Esta prueba cambia productos y destinos. Córrela solo contra una COPIA de la base:\n"
		. "  php tests/doble_comanda_test.php http://localhost:8002/index.php COPIA\n";
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
/** Crea (o reutiliza) un destino de impresión y devuelve su id. */
function destino($nombre)
{
	list(, $b) = pedir(BASE . '/DestinoDeImpresion/ListadoDestinoDeImpresionDatatable', array());
	foreach ((json($b)['aaData'] ?? array()) as $d) if ($d['nombre_dimpresion'] === $nombre) return (int)$d['id_dimpresion'];
	pedir(BASE . '/DestinoDeImpresion/grabarNuevoDestinoDeImpresion', array('prod' => array('id_dimpresion' => 0, 'nombre_dimpresion' => $nombre,
		'ip' => '127.0.0.1', 'puerto' => '9100', 'ruta_windows' => '', 'size_font' => 8)));
	list(, $b) = pedir(BASE . '/DestinoDeImpresion/ListadoDestinoDeImpresionDatatable', array());
	foreach ((json($b)['aaData'] ?? array()) as $d) if ($d['nombre_dimpresion'] === $nombre) return (int)$d['id_dimpresion'];
	return 0;
}
/** Graba un producto de la Carta cambiando solo lo que se pide (como la pantalla: el resto queda igual). */
function grabarProducto($p, $cambios)
{
	$bool = function ($v) { return $v === 'SI' ? 1 : 0; };
	$datos = array_merge(array(
		'id_producto' => $p['id_producto'], 'nombre_producto' => $p['nombre_producto'], 'cbarra' => $p['cbarra'], 'cbarra_auto' => 0,
		'impto_iva' => $p['impto_iva'], 'impto_ila' => $p['impto_ila'] ?? 0, 'ila_en_formulario' => 1, 'id_familia' => $p['id_familia'],
		'id_umedida' => $p['id_umedida'], 'precio_bruto' => 0, 'espack' => 0, 'vigente' => $bool($p['vigente']), 'inventariable' => 0,
		'valor_ila' => 0, 'precio_ucompra' => 0, 'pponderado' => 1, 'stock_minimo' => 0, 'cbarra_balanza' => 0,
		'con_agregado' => $bool($p['con_agregado']), 'venta_publico' => $bool($p['venta_publico']), 'descripcion_corta' => '',
		'permite_porcion' => $bool($p['permite_porcion']), 'id_subfamilia' => $p['id_subfamilia'], 'id_dimpresion' => $p['id_dimpresion'],
		'id_dimpresion2_web' => $p['id_dimpresion2_web'] ?? 0, 'id_receta' => $p['id_receta'], 'orden' => 0, 'permite_cambio' => 0,
		'valor1' => 0, 'valor2' => 0, 'id_bodega' => $p['id_bodega'], 'es_menu' => 0, 'id_menu' => $p['id_menu'],
		'venta_segun_stock' => $bool($p['venta_segun_stock']), 'elaboracion_propia' => $bool($p['elaboracion_propia']),
		'permitir_venta_sin_precio' => $bool($p['permitir_venta_sin_precio']), 'venta_uno_a_uno' => $bool($p['venta_uno_a_uno']),
		'pedir_cantidad_en_venta' => $bool($p['pedir_cantidad_en_venta']), 'detalle_preparacion' => '', 'tiempo_preparacion' => 0,
		'mostrar_detalle_insumos_receta' => 0, 'ver_en_dispositivos_moviles' => $bool($p['ver_en_dispositivos_moviles']),
		'permite_cortesia' => $bool($p['permite_cortesia']), 'favorito' => $bool($p['favorito'] ?? 'NO'),
	), $cambios);
	list($c, $b) = pedir(BASE . '/Productos/grabarProductoConPrecios', array('producto' => $datos, 'precios' => array()));
	return array($c, $b);
}
function carta()
{
	list(, $b) = pedir(BASE . '/Productos/ListadoCartaDeProductos', array());
	$res = array();
	foreach ((json($b)['aaData'] ?? array()) as $p) $res[(int)$p['id_producto']] = $p;
	return $res;
}
/** Hojas de la comanda (una por destino): nombre del destino => texto de la hoja. */
function hojas($comandaId)
{
	list($c, $html) = pedir(BASE . '/Caja/comanda?comanda_id=' . rawurlencode($comandaId), null, false);
	$res = array();
	$partes = preg_split('/<div class="destino">/', $html);
	array_shift($partes);
	foreach ($partes as $p) {
		$nombre = trim(html_entity_decode(strip_tags(substr($p, 0, strpos($p, '</div>')))));
		$res[$nombre] = preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($p)));
	}
	return $res;
}

echo "Preparar (con las pantallas del sistema):\n";
$cocina = destino('COCINA');
$parrilla = destino('PARRILLA');
$barra = destino('BARRA');
revisar('existen los destinos COCINA, PARRILLA y BARRA', $cocina && $parrilla && $barra, "$cocina / $parrilla / $barra");

list(, $b) = pedir(BASE . '/Caja/catalogo?id_lprecio=1');
$cat = json($b);
$esp = array();
foreach (array_merge($cat['menus'] ?? array(), $cat['sushis'] ?? array()) as $x) $esp[(int)$x['id_producto']] = true;
$simples = array();
foreach ($cat['productos'] ?? array() as $p) if ($p['precio'] > 0 && empty($p['fraccion']) && empty($p['pedir_cantidad']) && !isset($esp[(int)$p['id_producto']])) $simples[] = (int)$p['id_producto'];
$productos = carta();
// Principal (ARROZ CON CARNE): el primero simple; SALSA: otro producto; NORMAL: un tercero sin nada marcado
$idPrin = $simples[0] ?? 0;
$idSalsa = $simples[1] ?? 0;
$idNormal = $simples[2] ?? 0;
revisar('hay tres productos simples para probar', $idPrin && $idSalsa && $idNormal && isset($productos[$idPrin], $productos[$idSalsa], $productos[$idNormal]));
if (!$idPrin || !$idSalsa || !$idNormal) terminar('doble comanda');

list($c, $b) = grabarProducto($productos[$idPrin], array('id_dimpresion' => $cocina, 'id_dimpresion2_web' => $parrilla, 'con_agregado' => 1));
revisar('en la Carta: el producto en COCINA con "Imprimir también en" PARRILLA', $c === 200 && strpos($b, 'error') === false, "código $c: " . substr($b, 0, 200));
grabarProducto($productos[$idSalsa], array('id_dimpresion' => $barra, 'id_dimpresion2_web' => 0));
grabarProducto($productos[$idNormal], array('id_dimpresion' => $cocina, 'id_dimpresion2_web' => 0));
$productos = carta();
revisar('al reabrir el producto, el segundo destino quedó guardado', (int)($productos[$idPrin]['id_dimpresion2_web'] ?? -1) === $parrilla,
	json_encode($productos[$idPrin]['id_dimpresion2_web'] ?? null));
list($c, $b) = grabarProducto($productos[$idNormal], array('id_dimpresion' => $cocina, 'id_dimpresion2_web' => $cocina));
$productos = carta();
revisar('elegir el mismo destino dos veces no lo duplica (queda sin segundo destino)', (int)($productos[$idNormal]['id_dimpresion2_web'] ?? -1) === 0);

// Agregado SALSA del producto principal con "Imprimir en su propio destino"
list(, $b) = pedir(BASE . '/Agregados/ListadoDeAgregados', array());
$idAgExistente = 0;
foreach ((json($b)['aaData'] ?? array()) as $a) if ((int)$a['id_producto_maestro'] === $idPrin && (int)$a['id_producto'] === $idSalsa) $idAgExistente = (int)$a['id_agregado'];
list($c, $b) = pedir(BASE . '/Agregados/grabarNuevoAgregado', array('prod' => array('id_agregado' => $idAgExistente, 'id_producto' => $idSalsa,
	'cantidad' => 1, 'vigente' => 'true', 'id_producto_maestro' => $idPrin, 'valor' => 0, 'usar_destino_impresion' => 'true')));
revisar('en Agregados: SALSA con "Imprimir en su propio destino"', $c === 200, "código $c: " . substr($b, 0, 200));
list(, $b) = pedir(BASE . '/Caja/catalogo?id_lprecio=1');
$idAgregado = 0;
foreach ((json($b)['agregados'] ?? array()) as $a) if ((int)$a['id_producto_maestro'] === $idPrin && $a['nombre'] === $productos[$idSalsa]['nombre_producto']) $idAgregado = (int)$a['id_agregado'];
revisar('la Caja ofrece el agregado SALSA en el producto', $idAgregado > 0);

echo "Enviar a una mesa y revisar la comanda:\n";
list(, $b) = pedir(BASE . '/Caja/estado');
if (strpos($b, '"caja":null') !== false) pedir(BASE . '/Caja/abrir', array('fondo' => '0'));
list(, $b) = pedir(BASE . '/Caja/mesas');
$mesa = 0;
foreach ((json($b)['mesas'] ?? array()) as $m) if ($m['estado'] === 'libre' && empty($m['con_pedido']) && empty($m['reserva'])) { $mesa = (int)$m['id_mesa']; break; }
list($c, $r, $b) = postJson(BASE . '/Caja/enviarMesa', array('id_mesa' => $mesa, 'id_lprecio' => 1, 'observacion' => '', 'detalle' => array(
	array('id_producto' => $idPrin, 'cantidad' => 2, 'agregados' => array($idAgregado), 'notas_agregados' => array($idAgregado => 'picante'), 'nota' => 'BIEN COCIDO', 'menu' => array(), 'sushi' => null),
	array('id_producto' => $idNormal, 'cantidad' => 1, 'agregados' => array(), 'nota' => '', 'menu' => array(), 'sushi' => null))));
$comandaId = (string)($r['comanda_id'] ?? '');
revisar("se envía el pedido a la mesa $mesa", $c === 200 && $comandaId !== '', "código $c: " . substr($b, 0, 200));
$h = hojas($comandaId);
$nPrin = mb_strtoupper($productos[$idPrin]['nombre_producto']);
$nSalsa = mb_strtoupper($productos[$idSalsa]['nombre_producto']);
$nNormal = mb_strtoupper($productos[$idNormal]['nombre_producto']);
revisar('salen tres hojas: COCINA, PARRILLA y BARRA', isset($h['COCINA'], $h['PARRILLA'], $h['BARRA']) && count($h) === 3, implode(', ', array_keys($h)));
revisar('COCINA: el producto con su nota y "también sale en PARRILLA"', strpos($h['COCINA'] ?? '', $nPrin) !== false
	&& mb_stripos($h['COCINA'] ?? '', 'BIEN COCIDO') !== false && mb_stripos($h['COCINA'] ?? '', 'también sale en PARRILLA') !== false, $h['COCINA'] ?? '');
revisar('COCINA: el agregado anotado "sale en BARRA"', mb_stripos($h['COCINA'] ?? '', 'sale en BARRA') !== false);
revisar('COCINA: el producto sin nada marcado sale solo ahí', strpos($h['COCINA'] ?? '', $nNormal) !== false
	&& strpos($h['PARRILLA'] ?? '', $nNormal) === false && strpos($h['BARRA'] ?? '', $nNormal) === false);
revisar('PARRILLA: el mismo producto, 2 unidades, con "también sale en COCINA"', strpos($h['PARRILLA'] ?? '', '2 x ' . $nPrin) !== false
	&& mb_stripos($h['PARRILLA'] ?? '', 'también sale en COCINA') !== false, $h['PARRILLA'] ?? '');
revisar('BARRA: la SALSA, 2 unidades, con su mensaje y para qué producto es', strpos($h['BARRA'] ?? '', '2 x ' . $nSalsa) !== false
	&& mb_stripos($h['BARRA'] ?? '', 'picante') !== false && mb_stripos($h['BARRA'] ?? '', 'Para: ' . $nPrin) !== false, $h['BARRA'] ?? '');

echo "Pantalla de cocina:\n";
list(, $b) = pedir(BASE . '/Configuraciones/guardar', array('usar_cocina_web' => '1'));
$ve = function ($destino) use ($nPrin, $mesa) {
	list(, $b) = pedir(BASE . '/Cocina/pendientes?destino=' . $destino);
	foreach ((json($b)['comandas'] ?? array()) as $c) foreach ($c['lineas'] as $l) if (strpos(mb_strtoupper($l['nombre']), $nPrin) === 0) return $l;
	return null;
};
$enParrilla = $ve($parrilla);
revisar('filtrada por PARRILLA, la cocina muestra el producto', $enParrilla !== null);
revisar('con el agregado en la misma fila y sus dos destinos', $enParrilla && strpos(mb_strtoupper($enParrilla['nombre']), '+ ' . $nSalsa) !== false
	&& strpos($enParrilla['destino'], 'COCINA') !== false && strpos($enParrilla['destino'], 'PARRILLA') !== false, json_encode($enParrilla));
revisar('filtrada por COCINA también', $ve($cocina) !== null);

echo "Sin marcar, todo vuelve a ser como antes:\n";
grabarProducto(carta()[$idPrin], array('id_dimpresion' => $cocina, 'id_dimpresion2_web' => 0));
pedir(BASE . '/Agregados/grabarNuevoAgregado', array('prod' => array('id_agregado' => 0, 'id_producto' => $idSalsa, 'cantidad' => 1,
	'vigente' => 'true', 'id_producto_maestro' => $idPrin, 'valor' => 0, 'usar_destino_impresion' => 'false')));
list(, $b) = pedir(BASE . '/Caja/mesas');
foreach ((json($b)['mesas'] ?? array()) as $m) if ($m['estado'] === 'libre' && empty($m['con_pedido']) && empty($m['reserva']) && (int)$m['id_mesa'] !== $mesa) { $mesa = (int)$m['id_mesa']; break; }
list($c, $r) = postJson(BASE . '/Caja/enviarMesa', array('id_mesa' => $mesa, 'id_lprecio' => 1, 'observacion' => '', 'detalle' => array(
	array('id_producto' => $idPrin, 'cantidad' => 1, 'agregados' => array($idAgregado), 'nota' => '', 'menu' => array(), 'sushi' => null))));
$h = hojas((string)($r['comanda_id'] ?? ''));
revisar('una sola hoja (COCINA) con el producto y su agregado', array_keys($h) === array('COCINA') && strpos($h['COCINA'] ?? '', $nSalsa) !== false
	&& mb_stripos($h['COCINA'] ?? '', 'sale en') === false, implode(', ', array_keys($h)));

terminar('doble comanda');
