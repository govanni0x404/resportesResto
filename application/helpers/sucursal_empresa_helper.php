<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Sucursal de la caja para los documentos (11-11-2026, Configuraciones > Datos de la Empresa).
 *
 * La caja abierta con una caja de Control de cajas (caja.id_control_caja) pertenece a una sucursal de la intranet
 * (control_caja.id_sucursal = empresa_sucursal.id_sucursal_intranet). Boletas, facturas y tickets de esa caja llevan la
 * dirección, comuna, teléfono y código SII de esa sucursal. Emite siempre la empresa principal (la primera de la tabla
 * empresa, como antes): si la sucursal es de otra empresa, el documento queda con los datos de la empresa, como antes.
 * Sin la migración 20261111200000, sin caja de Control de cajas o sin dirección en la sucursal: todo como antes.
 */

/**
 * Sucursal de la intranet de una caja de Control de cajas, como expresión SQL: control_caja.id_sucursal_intranet
 * (migración 20261112090000); sin esa columna, control_caja.id_sucursal, donde se guardaba antes. $alias = alias de control_caja.
 */
function control_caja_sucursal_sql($db, $alias = 'cc')
{
	static $hay = array();
	$k = spl_object_hash($db);
	if (!isset($hay[$k])) {
		$r = $db->query("SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'control_caja'
			AND column_name = 'id_sucursal_intranet'");
		$hay[$k] = $r && $r->row();
	}
	$p = $alias !== '' ? $alias . '.' : '';
	return $hay[$k] ? "COALESCE({$p}id_sucursal_intranet, {$p}id_sucursal)" : "{$p}id_sucursal";
}

/** ¿Están las columnas para ligar caja -> control_caja -> empresa_sucursal? (una vez por conexión) */
function sucursal_empresa_disponible($db)
{
	static $hay = array();
	$k = spl_object_hash($db);
	if (!isset($hay[$k])) {
		$r = $db->query("SELECT count(*) AS n FROM information_schema.columns WHERE table_schema = 'public'
			AND ((table_name = 'caja' AND column_name = 'id_control_caja')
				OR (table_name = 'control_caja' AND column_name = 'id_sucursal')
				OR (table_name = 'empresa_sucursal' AND column_name IN ('id_sucursal_intranet', 'id_empresa', 'direccion', 'codigo_sii_manual')))");
		$f = $r ? $r->row() : null;
		$hay[$k] = $f && (int)$f->n === 6;
	}
	return $hay[$k];
}

/**
 * Sucursal (de la empresa principal) de la caja $idCaja, o null.
 * @return object|null nombre_sucursal, direccion, comuna, telefono, codigo_sii (el de la intranet o el completado aquí)
 */
function sucursal_de_caja($db, $idCaja)
{
	$idCaja = (int)$idCaja;
	if ($idCaja <= 0 || !sucursal_empresa_disponible($db)) return null;
	$suc = control_caja_sucursal_sql($db, 'cc');
	$r = $db->query("SELECT COALESCE(es.nombre_sucursal, '') AS nombre_sucursal, trim(COALESCE(es.direccion, '')) AS direccion,
			trim(COALESCE(co.nombre_comuna, es.comuna, '')) AS comuna, trim(COALESCE(es.telefono, '')) AS telefono,
			COALESCE(NULLIF(es.codigo_sii, 0), es.codigo_sii_manual, 0) AS codigo_sii
		FROM caja c
		JOIN control_caja cc ON cc.id_control_caja = c.id_control_caja
		JOIN empresa_sucursal es ON es.id_sucursal_intranet = $suc AND es.vigente IS NOT FALSE
		LEFT JOIN comuna co ON co.id_comuna = es.id_comuna AND es.id_comuna > 0
		WHERE c.id_caja = ? AND COALESCE($suc, 0) > 0
			AND es.id_empresa = (SELECT min(id_empresa) FROM empresa)
		ORDER BY es.id_empresa_sucursal LIMIT 1", array($idCaja));
	$s = $r ? $r->row() : null;
	if (!$s) return null;
	$s->codigo_sii = (int)$s->codigo_sii;
	return $s;
}

/**
 * La fila de empresa para imprimir, con la dirección / comuna / teléfono de la sucursal (si la sucursal tiene dirección).
 * No cambia el objeto recibido. Agrega codigo_sii_sucursal y nombre_sucursal_documento.
 */
function empresa_con_sucursal($empresa, $sucursal)
{
	if (!$empresa || !$sucursal) return $empresa;
	$e = clone $empresa;
	$e->codigo_sii_sucursal = (int)$sucursal->codigo_sii;
	$e->nombre_sucursal_documento = (string)$sucursal->nombre_sucursal;
	if ($sucursal->direccion !== '') {
		$e->direccion_empresa = $sucursal->direccion;
		// Sin comuna en la sucursal queda la de la empresa, igual que en el documento electrónico (CmnaOrigen)
		if ($sucursal->comuna !== '') $e->comuna = $sucursal->comuna;
	}
	if ($sucursal->telefono !== '') $e->telefono = $sucursal->telefono;
	return $e;
}
