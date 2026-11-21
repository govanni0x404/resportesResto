<?php
/**
 * Prueba de las consultas con "?" y de la sesión vencida (v1.27.0).
 *
 *   1) prende la web en modo local:   C:\xampp\php\php.exe -S localhost:8001 router.php
 *   2) en otra consola:               C:\xampp\php\php.exe tests\consultas_sesion_test.php
 *
 * Vuelve a grabar un destino de impresión, un precio y un agregado CON LOS MISMOS DATOS que ya tienen
 * (no cambia nada en la base) y revisa que respondan el id correcto y que los datos sigan iguales.
 * Después cierra la sesión y revisa que el servidor conteste 401 (la página lleva al ingreso), no 403.
 */
require __DIR__ . '/lib_pruebas.php';
entrar();
echo "Consultas que antes pegaban el número en el texto:\n";

// --- Destino de impresión (MdestinoDeImpresion) ---
list($c, $b) = pedir(BASE . '/DestinoDeImpresion/ListadoDestinoDeImpresionDatatable', array());
$destinos = json($b)['aaData'] ?? array();
revisar('hay destinos de impresión para probar', count($destinos) > 0, "código $c");
if ($destinos) {
	$d = $destinos[0];
	$campos = array('impresion_grafica_windows', 'permite_precuenta', 'impresion_local', 'usar_logo_precuenta_pos', 'sin_impresion',
		'usar_dimpresion_recinto', 'usar_dimpresion_precuenta_android');
	$prod = array('id_dimpresion' => $d['id_dimpresion'], 'nombre_dimpresion' => $d['nombre_dimpresion'], 'ip' => $d['ip'],
		'puerto' => $d['puerto'], 'ruta_windows' => $d['ruta_windows'], 'size_font' => $d['size_font']);
	foreach ($campos as $k) $prod[$k] = $d[$k];
	list($c, $b) = pedir(BASE . '/DestinoDeImpresion/grabarNuevoDestinoDeImpresion', array('prod' => $prod));
	$r = json($b);
	revisar('grabar destino de impresión devuelve su mismo id', $c === 200 && (string)($r[0]['id_dimpresion'] ?? '') === (string)$d['id_dimpresion'], "código $c: " . substr($b, 0, 200));
	list($c, $b) = pedir(BASE . '/DestinoDeImpresion/ListadoDestinoDeImpresionDatatable', array());
	$despues = json($b)['aaData'][0] ?? array();
	revisar('el destino de impresión quedó igual que antes', $despues == $d, json_encode(array_diff_assoc($d, $despues)));
}

// --- Precio de una lista (MlistaDePrecios) ---
list($c, $b) = pedir(BASE . '/ListaDePrecios/obtenerListayPreciosDeVenta', array('id_producto' => 1));
$precios = json($b);
revisar('hay precios para probar', count($precios) > 0, "código $c");
if ($precios) {
	$p = $precios[0];
	list($c, $b) = pedir(BASE . '/ListaDePrecios/grabarPrecios', array('detalle' => array(array(
		'id_detalle_lprecio' => $p['id_detalle_lprecio'], 'total' => $p['total'], 'monto_comision' => $p['monto_comision'] ?? 0))));
	$r = json($b);
	revisar('grabar precio devuelve su mismo id', $c === 200 && (string)($r[0][0]['id_detalle_lprecio'] ?? '') === (string)$p['id_detalle_lprecio'], "código $c: " . substr($b, 0, 200));
	list($c, $b) = pedir(BASE . '/ListaDePrecios/obtenerListayPreciosDeVenta', array('id_producto' => 1));
	$despues = json($b)[0] ?? array();
	revisar('el precio (total, neto, IVA) quedó igual', ($despues['total'] ?? '') === $p['total'] && ($despues['neto'] ?? '') === $p['neto'] && ($despues['iva'] ?? '') === $p['iva']);
}

// --- Agregado (Magregados): con id 0 y el mismo producto y maestro, actualiza el que ya existe ---
list($c, $b) = pedir(BASE . '/Agregados/ListadoDeAgregados', array());
$agregados = json($b)['aaData'] ?? array();
revisar('hay agregados para probar', count($agregados) > 0, "código $c");
if ($agregados) {
	$a = $agregados[0];
	$bool = function ($v) { return $v === 'SI' ? 'true' : 'false'; };
	$prod = array('id_producto' => $a['id_producto'], 'cantidad' => $a['cantidad'], 'vigente' => $bool($a['vigente']),
		'id_producto_maestro' => $a['id_producto_maestro'], 'valor' => $a['valor'], 'usar_destino_impresion' => $bool($a['usar_destino_impresion']));
	foreach (array(0 => 'sin id (busca el que existe)', $a['id_agregado'] => 'con su id') as $id => $como) {
		list($c, $b) = pedir(BASE . '/Agregados/grabarNuevoAgregado', array('prod' => $prod + array('id_agregado' => $id)));
		$r = json($b);
		revisar("grabar agregado $como devuelve su mismo id", $c === 200 && (string)($r[0]['id_agregado'] ?? '') === (string)$a['id_agregado'], "código $c: " . substr($b, 0, 200));
	}
	list($c, $b) = pedir(BASE . '/Agregados/ListadoDeAgregados', array());
	$despues = json($b)['aaData'] ?? array();
	revisar('no se creó un agregado repetido', count($despues) === count($agregados), count($agregados) . ' antes, ' . count($despues) . ' después');
}

// --- Sesión vencida ---
echo "Sesión vencida:\n";
salir();
list($c, $b) = pedir(BASE . '/Dashboard/totalVentas', array('id_caja' => 0));
revisar('un recuadro del Dashboard sin sesión responde 401', $c === 401, "código $c");
revisar('y trae el mensaje "Tu sesión expiró"', strpos((string)(json($b)['error'] ?? ''), 'sesión expiró') !== false, substr($b, 0, 200));
list($c, $b) = pedir(BASE . '/Login/postLoginWarmup', array());
revisar('las migraciones sin sesión responden 401 (antes 403 "página desactualizada")', $c === 401, "código $c");
list($c, $b, $loc) = abrirPagina(BASE . '/Dashboard');
revisar('abrir el Dashboard sin sesión lleva al ingreso', $c >= 301 && $c <= 308 && $loc !== '', "código $c");
entrar();
list($c, $b) = pedir(BASE . '/Dashboard/totalVentas', array('id_caja' => 0), false);
revisar('con sesión pero sin la clave anti-CSRF sigue respondiendo 403', $c === 403, "código $c");

terminar('consultas y sesión');
