<?php
/**
 * Prueba del aviso de stock bajo de la Caja (v1.27.0).
 *
 * ¡CREA UN INSUMO DE PRUEBA! Úsala SOLO con una COPIA de la base (pide la palabra COPIA):
 *   php tests/stock_bajo_test.php http://localhost:8002/index.php COPIA
 *
 * - Un insumo con stock mínimo y stock por debajo aparece en Caja/stockBajo; con mínimo 0 no aparece.
 * - Grabar el insumo desde otra pantalla (sin el campo stock mínimo) no le borra el mínimo.
 * - Un mínimo negativo o con letras se rechaza.
 * - Caja/stockBajo responde rápido (no frena la Caja).
 */
require __DIR__ . '/lib_pruebas.php';
if (($argv[2] ?? '') !== 'COPIA') {
	echo "Esta prueba crea un insumo. Córrela solo contra una COPIA de la base:\n  php tests/stock_bajo_test.php http://localhost:8002/index.php COPIA\n";
	exit(1);
}
entrar();

function bajos()
{
	list($c, $b) = pedir(BASE . '/Caja/stockBajo');
	return array($c, json($b)['insumos'] ?? null);
}
function grabarInsumo($datos)
{
	$base = array('id_insumo' => 0, 'cbarra' => '', 'nombre_insumo' => '', 'id_grupo_insumo' => 0, 'id_impto_iva' => 1, 'id_impto_adicional' => 0,
		'umedida_inventario' => 1, 'id_umedida_inventario' => 1, 'umedida_receta' => 1, 'id_umedida_receta' => 1, 'costo_neto_ppp' => 0,
		'costo_bruto_ppp' => 0, 'precio_ucompra' => 0, 'observaciones' => '', 'inv_cierre' => 0);
	list($c, $b) = pedir(BASE . '/Insumos/grabarInsumo', array('prod' => array_merge($base, $datos)));
	return array($c, json($b), $b);
}
function esta($lista, $id)
{
	foreach ((array)$lista as $i) if ((int)$i['id_insumo'] === (int)$id) return $i;
	return null;
}

$codigo = 'PRB' . substr((string)time(), -6);
$nombre = 'INSUMO PRUEBA ' . $codigo;
list($c, $r, $b) = grabarInsumo(array('cbarra' => $codigo, 'nombre_insumo' => $nombre, 'stock_minimo' => '5'));
$id = (int)($r['id_insumo'] ?? 0);
revisar('se crea un insumo con stock mínimo 5', $c === 200 && $id > 0, "código $c: " . substr($b, 0, 200));
if (!$id) terminar('stock bajo');

$t = microtime(true);
list($c, $lista) = bajos();
$ms = round((microtime(true) - $t) * 1000);
revisar('Caja/stockBajo responde', $c === 200 && is_array($lista), "código $c");
revisar("y rápido ($ms ms, máximo 500)", $ms < 500);
$i = esta($lista, $id);
revisar('el insumo sin stock aparece en el aviso', $i !== null);
revisar('con su mínimo y su stock', $i && (float)$i['minimo'] === 5.0 && (float)$i['saldo'] <= 0, json_encode($i));

// v1.27.1: al reabrir el insumo (la lista de Insumos) tiene que venir el mínimo grabado; antes salía 0
list(, $b) = pedir(BASE . '/Insumos/ListadoInsumos', array());
$fila = null;
foreach ((json($b)['aaData'] ?? array()) as $f) if ((int)$f['id_insumo'] === $id) $fila = $f;
revisar('al reabrir el insumo se ve el mínimo grabado (5)', $fila && (float)($fila['stock_minimo_web'] ?? -1) === 5.0, json_encode($fila));

list($c, $r, $b) = grabarInsumo(array('id_insumo' => $id, 'cbarra' => $codigo, 'nombre_insumo' => $nombre));   // sin stock_minimo (otra pantalla)
list(, $lista) = bajos();
revisar('grabarlo sin el campo (desde otra pantalla) no le borra el mínimo', $c === 200 && esta($lista, $id) !== null, "código $c");

foreach (array('-1', 'abc') as $malo) {
	list($c, $r, $b) = grabarInsumo(array('id_insumo' => $id, 'cbarra' => $codigo, 'nombre_insumo' => $nombre, 'stock_minimo' => $malo));
	revisar("un mínimo \"$malo\" se rechaza", empty($r['success']) && stripos((string)($r['msg'] ?? ''), 'stock mínimo') !== false, substr($b, 0, 200));
}

list($c, $r, $b) = grabarInsumo(array('id_insumo' => $id, 'cbarra' => $codigo, 'nombre_insumo' => $nombre, 'stock_minimo' => '0'));
list(, $lista) = bajos();
revisar('con mínimo 0 sale del aviso', $c === 200 && esta($lista, $id) === null);

list($c, $html) = pedir(BASE . '/Caja', null, false);
revisar('la Caja tiene la franja del aviso (arriba, sin tapar nada)', strpos($html, 'id="stockBajo"') !== false && strpos($html, "base + 'stockBajo'") !== false);

terminar('stock bajo');
