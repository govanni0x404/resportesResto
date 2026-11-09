<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Permisos de la web por rol (tabla permiso_web, ver sql/permiso_web.sql).
 *
 *  - Gerencia (id_rol 1) entra a todo y es la única que ve "Privilegios" para habilitar o quitar módulos a los demás roles.
 *  - Los demás roles entran solo a los módulos que gerencia les dejó habilitados.
 *  - Si la tabla permiso_web todavía no existe se usa la regla antigua (psistema 'RESTO WEB' + pusuario), para no dejar a
 *    nadie sin acceso antes de correr el script.
 *
 * Lo usan el menú (menu_visible en menuTop) y el control del servidor (hooks/Permisos.php): la misma regla en los dos lados.
 */

/** Módulos de la web agrupados como en el menú: nombre del controlador => nombre visible. */
function permisos_web_catalogo()
{
	return array(
		'Ventas' => array(
			'Dashboard' => 'Mis ventas (inicio)',
			'Caja' => 'Caja',
			'Mesas' => 'Mesas (herramientas)',
		),
		'Procesos' => array(
			'EntradaBodega' => 'Entrada a bodega',
			'SalidaBodega' => 'Salida de bodega',
			'Compras' => 'Compras',
			'Inventario' => 'Inventario',
		),
		'Mantención' => array(
			'Productos' => 'Productos de la carta',
			'Insumos' => 'Insumos',
			'Recetas' => 'Recetas',
			'ListaDePrecios' => 'Lista de precios',
			'Cliente' => 'Clientes',
			'Proveedor' => 'Proveedores',
			'Recintos' => 'Recintos',
			'MesasPorRecinto' => 'Mesas por recinto',
		),
		'Tablas' => array(
			'Agregados' => 'Agregados',
			'Bodegas' => 'Bodegas',
			'CanalDeVenta' => 'Canal de venta',
			'CentroCosto' => 'Centro de costo',
			'Comuna' => 'Comunas',
			'Provincias' => 'Provincias',
			'Delivery' => 'Sectores de delivery',
			'DestinoDeImpresion' => 'Destinos de impresión',
			'GastoEventos' => 'Gastos en eventos',
			'IngresoEventos' => 'Ingresos en eventos',
			'TipoEvento' => 'Tipos de evento',
			'Familias' => 'Familias de productos',
			'FamiliasInsumos' => 'Familias de insumos',
			'FamiliasReceta' => 'Familias de recetas',
			'SubFamilias' => 'Subfamilias',
			'FormasDePago' => 'Formas de pago',
			'DocumentosTributarios' => 'Documentos tributarios',
			'Impuestos' => 'Impuestos',
			'MensajesPorProductos' => 'Mensajes por producto',
			'Moneda' => 'Moneda',
			'Turnos' => 'Turnos',
			'Umedida' => 'Unidades de medida',
			'ItemGastosMensuales' => 'Ítems de gastos mensuales',
		),
		'Consultas' => array(
			'InsumosPorBodega' => 'Stock de insumos',
			'DetalleOperaciones' => 'Detalle de ventas',
			'CierresDeCaja' => 'Cierres de caja',
			'ResumenDeCompras' => 'Resumen de compras',
			'ConsolidadoVentas' => 'Consolidado de ventas',
			'VentasPorProducto' => 'Ventas por producto',
			'PropinasPorGarzon' => 'Propinas por garzón',
			'ResumenAnulaciones' => 'Resumen de anulaciones',
			'ListadoDeClientes' => 'Listado de clientes',
			'VentasMensualesTotalizadas' => 'Resumen de ventas mensuales',
			'ResumenVentasProductosMes' => 'Venta de productos mensual',
			'ResumenEntradaBodega' => 'Movimientos: entradas de stock',
			'ResumenSalidaBodega' => 'Movimientos: salidas de stock',
		),
		'Administración' => array(
			'Usuario' => 'Usuarios',
			'Configuraciones' => 'Configuraciones',
		),
	);
}

/** Módulos que con la regla antigua exigían permiso explícito. */
function permisos_web_sensibles()
{
	return array('usuario', 'privilegiosusuario', 'configuraciones');
}

/** nombre en minúsculas => true, de todos los módulos del catálogo. */
function permisos_web_modulos()
{
	static $mods = null;
	if ($mods === null) {
		$mods = array();
		foreach (permisos_web_catalogo() as $grupo) foreach ($grupo as $m => $nombre) $mods[strtolower($m)] = true;
	}
	return $mods;
}

/** ¿Existe la tabla permiso_web en la base del cliente? */
function permisos_web_tabla($db)
{
	static $hay = array();
	$k = spl_object_hash($db);
	if (!isset($hay[$k])) {
		$r = $db->query("SELECT to_regclass('public.permiso_web') IS NOT NULL AS hay");
		$f = $r ? $r->row() : null;
		$hay[$k] = $f && ($f->hay === 't' || $f->hay === true);
	}
	return $hay[$k];
}

/**
 * Accesos del rol: array('modo' => 'web'|'antiguo', 'accesos' => array(modulo en minúsculas => bool)).
 * Se lee una vez por petición.
 */
function permisos_web_accesos($CI, $idRol)
{
	static $cache = array();
	$idRol = (int)$idRol;
	if (isset($cache[$idRol])) return $cache[$idRol];
	$res = array('modo' => 'antiguo', 'accesos' => array());
	try {
		$CI->load->helper('tenant');
		$db = get_tenant_db($CI);
		if (permisos_web_tabla($db)) {
			$res['modo'] = 'web';
			$r = $db->query('SELECT modulo, autoriza FROM permiso_web WHERE id_rol = ?', array($idRol));
			if ($r) foreach ($r->result() as $f) {
				$res['accesos'][strtolower(trim($f->modulo))] = ($f->autoriza === 't' || $f->autoriza === true);
			}
		} else {
			$r = $db->query("SELECT ps.nombre_vb, COALESCE(pu.autoriza, false) AS autoriza
				FROM psistema ps LEFT JOIN pusuario pu ON pu.id_psistema = ps.id_psistema AND pu.id_rol = ?
				WHERE ps.vigente = true AND ps.nombre_psistema = 'RESTO WEB'", array($idRol));
			if ($r) foreach ($r->result() as $f) {
				$k = strtolower(trim($f->nombre_vb));
				$res['accesos'][$k] = !empty($res['accesos'][$k]) || $f->autoriza === 't' || $f->autoriza === true;
			}
		}
	} catch (Throwable $e) {
		log_message('error', 'permisos_web: no se pudieron leer los privilegios: ' . $e->getMessage());
	}
	return $cache[$idRol] = $res;
}

/** ¿Puede el rol entrar al módulo (nombre del controlador)? */
function permiso_web($CI, $modulo, $idRol = null)
{
	if ($idRol === null) $idRol = (int)$CI->session->userdata('id_rol');
	$idRol = (int)$idRol;
	if ($idRol === 1) return true;
	$k = strtolower(trim((string)$modulo));
	if ($k === 'privilegiosusuario') return false;   // solo gerencia administra los permisos
	$a = permisos_web_accesos($CI, $idRol);
	if ($a['modo'] === 'web') {
		$mods = permisos_web_modulos();
		// Fuera del catálogo (pantallas de apoyo sin menú): se permite, salvo las sensibles
		if (!isset($mods[$k])) return !in_array($k, permisos_web_sensibles(), true);
		return !empty($a['accesos'][$k]);
	}
	if (!array_key_exists($k, $a['accesos'])) return !in_array($k, permisos_web_sensibles(), true);
	return $a['accesos'][$k];
}

/**
 * Clave anti-CSRF de la sesión. La página la manda en cada POST (cabecera X-CSRF-Token o campo csrf_web, lo agrega solo
 * el script de menuTop) y hooks/Permisos.php la compara: una página de otro sitio no la conoce, así que no puede hacer
 * acciones en nombre del usuario aunque el navegador mande la cookie de sesión.
 */
function csrf_web_token($CI)
{
	$t = (string)$CI->session->userdata('csrf_web');
	if (strlen($t) !== 64) {
		$t = bin2hex(random_bytes(32));
		$CI->session->set_userdata('csrf_web', $t);
	}
	return $t;
}
