<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 14-11-2026: validación de RUT para los mantenedores de Clientes y Proveedores (QA: aceptaban RUT duplicado y
 * dígito verificador inválido). La Caja usa su propia versión (Mcaja::separarRut), con la misma cuenta.
 */

/** Dígito verificador (módulo 11) de un RUT sin DV: '0'..'9' o 'K'. */
function rut_dv($cuerpo)
{
	$cuerpo = preg_replace('/\D/', '', (string)$cuerpo);
	$suma = 0;
	$mult = 2;
	for ($i = strlen($cuerpo) - 1; $i >= 0; $i--) {
		$suma += (int)$cuerpo[$i] * $mult;
		$mult = $mult === 7 ? 2 : $mult + 1;
	}
	$r = 11 - ($suma % 11);
	return $r === 11 ? '0' : ($r === 10 ? 'K' : (string)$r);
}

/**
 * Revisa RUT + DV de un formulario y que no esté en otra fila de la tabla.
 * Devuelve array(rut int, dv) o array('error' => mensaje).
 * $tabla / $pk: 'cliente'/'id_cliente' o 'proveedor'/'id_proveedor'; $id = fila que se edita (0 al crear).
 */
function rut_validar_unico($db, $tabla, $pk, $id, $rut, $dv, $quien)
{
	$rutTxt = preg_replace('/\D/', '', (string)$rut);
	$dv = strtoupper(trim((string)$dv));
	if ($rutTxt === '' || (int)$rutTxt <= 0 || strlen($rutTxt) > 8) return array('error' => 'Ingrese un RUT válido para el ' . $quien);
	// Al editar sin cambiar el RUT ni el DV no se revisa nada: hay bases con duplicados antiguos y con RUT genéricos
	// (cliente público 6.666.666-6, cuyo DV real no es 6) que igual se tienen que poder editar.
	if ((int)$id > 0) {
		$actual = $db->query("SELECT rut, dv FROM $tabla WHERE $pk = ?", array((int)$id))->row();
		if ($actual && (int)$actual->rut === (int)$rutTxt && strtoupper(trim((string)$actual->dv)) === $dv) return array((int)$rutTxt, $dv);
	}
	if ($dv === '' || rut_dv($rutTxt) !== $dv) return array('error' => 'El dígito verificador no corresponde al RUT ' . (int)$rutTxt);
	$otro = $db->query("SELECT $pk AS id FROM $tabla WHERE rut = ? AND $pk <> ? LIMIT 1", array((int)$rutTxt, (int)$id))->row();
	if ($otro) return array('error' => 'Ya existe un ' . $quien . ' con el RUT ' . (int)$rutTxt . '-' . $dv . ' (N° ' . (int)$otro->id . ')');
	return array((int)$rutTxt, $dv);
}
