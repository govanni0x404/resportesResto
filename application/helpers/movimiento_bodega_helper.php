<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 16-11-2026 (QA): entradas y salidas de bodega (esbodega + detalle_esbodega + stock_insumo).
 * Antes cada línea se grababa por su lado: las inválidas se saltaban sin avisar, una cabecera inexistente se aceptaba,
 * si fallaba una línea la anterior quedaba grabada y una salida podía dejar el saldo en negativo.
 * Ahora: se revisa todo antes, se graba todo o nada, y una salida no puede sacar más de lo que hay.
 */

/**
 * Revisa y graba el detalle de un movimiento de bodega.
 * $tipo: 'entrada' | 'salida'. $grabarLinea($miData, $dataUpdateStock) graba una línea (los modelos de siempre).
 * Devuelve array de resultados o array('error' => mensaje).
 */
function movimiento_bodega_grabar($db, $tipo, $idEsBodega, $idBodega, $detalle, $grabarLinea)
{
	$db->db_debug = FALSE;
	$salida = $tipo === 'salida';
	if (!$db->query('SELECT 1 FROM esbodega WHERE id_esbodega = ?', array((int)$idEsBodega))->row()) {
		return array('error' => 'El movimiento de bodega no existe (vuelve a intentarlo)');
	}
	if (!$db->query('SELECT 1 FROM bodega WHERE id_bodega = ?', array((int)$idBodega))->row()) {
		movimiento_bodega_borrar_vacio($db, $idEsBodega);
		return array('error' => 'La bodega elegida no existe');
	}

	// Líneas: insumo existente y cantidad > 0. Se suman las repetidas para revisar el stock de una salida.
	$lineas = array();
	$porInsumo = array();
	foreach (array_values($detalle) as $n => $value) {
		$fila = $n + 1;
		$idInsumo = (int)($value['id_insumo'] ?? 0);
		// Igual que antes: la pantalla manda punto decimal y la coma es separador de miles
		$cantidad = (float)str_replace(',', '', (string)($value['cantidad'] ?? 0));
		$ins = $idInsumo > 0 ? $db->query('SELECT nombre_insumo FROM insumo WHERE id_insumo = ?', array($idInsumo))->row() : null;
		if (!$ins) {
			movimiento_bodega_borrar_vacio($db, $idEsBodega);
			return array('error' => "Línea $fila: el insumo no existe");
		}
		if (!($cantidad > 0)) {
			movimiento_bodega_borrar_vacio($db, $idEsBodega);
			return array('error' => 'Línea ' . $fila . ' (' . trim($ins->nombre_insumo) . '): la cantidad debe ser mayor que 0');
		}
		$lineas[] = array('id_insumo' => $idInsumo, 'cantidad' => $cantidad, 'nombre' => trim($ins->nombre_insumo));
		$porInsumo[$idInsumo] = ($porInsumo[$idInsumo] ?? 0) + $cantidad;
	}
	if (!$lineas) {
		movimiento_bodega_borrar_vacio($db, $idEsBodega);
		return array('error' => 'El movimiento no tiene insumos');
	}

	$db->trans_begin();
	$nivel = error_reporting(0);   // un error de la base no sale como página HTML
	try {
		// Saldo actual bloqueado hasta terminar: dos movimientos a la vez no se pisan
		$saldos = array();
		foreach (array_keys($porInsumo) as $idInsumo) {
			$r = $db->query('SELECT saldo FROM stock_insumo WHERE id_insumo = ? AND id_bodega = ? FOR UPDATE', array($idInsumo, (int)$idBodega))->row();
			$saldos[$idInsumo] = $r ? (float)$r->saldo : 0.0;
		}
		if ($salida) {
			foreach ($lineas as $l) {
				$pide = $porInsumo[$l['id_insumo']];
				if ($pide > $saldos[$l['id_insumo']] + 0.0005) {
					throw new Exception('Stock insuficiente de ' . $l['nombre'] . ': hay ' . rtrim(rtrim(number_format($saldos[$l['id_insumo']], 3, ',', '.'), '0'), ',')
						. ' y se quiere sacar ' . rtrim(rtrim(number_format($pide, 3, ',', '.'), '0'), ','));
				}
			}
		}
		$res = array();
		foreach ($lineas as $l) {
			$anterior = $saldos[$l['id_insumo']];
			$nuevo = $salida ? $anterior - $l['cantidad'] : $anterior + $l['cantidad'];
			$saldos[$l['id_insumo']] = $nuevo;   // el mismo insumo dos veces: la segunda parte del saldo de la primera
			$miData = array('id_esbodega' => (int)$idEsBodega, 'id_insumo' => $l['id_insumo'], 'cantidad' => $l['cantidad'],
				'stock_anterior' => $anterior, 'nuevo_stock' => $nuevo);
			$res[] = $grabarLinea($miData, $miData + array('id_bodega_stock' => (int)$idBodega));
			$e = $db->error();
			if (!empty($e['code'])) throw new Exception('No se pudo grabar ' . $l['nombre'] . ': ' . trim(preg_replace('/^ERROR:\s*/', '', (string)$e['message'])));
		}
		$db->trans_commit();
		error_reporting($nivel);
		return $res;
	} catch (Exception $e) {
		$db->trans_rollback();
		error_reporting($nivel);
		movimiento_bodega_borrar_vacio($db, $idEsBodega);
		return array('error' => $e->getMessage() . '. No se grabó nada.');
	}
}

/** Cabecera sin ninguna línea (quedaba sola cuando fallaba el detalle): se borra. */
function movimiento_bodega_borrar_vacio($db, $idEsBodega)
{
	if ((int)$idEsBodega <= 0) return;
	if ($db->query('SELECT 1 FROM detalle_esbodega WHERE id_esbodega = ? LIMIT 1', array((int)$idEsBodega))->row()) return;
	$nivel = error_reporting(0);
	$db->query('DELETE FROM esbodega WHERE id_esbodega = ?', array((int)$idEsBodega));
	error_reporting($nivel);
}
