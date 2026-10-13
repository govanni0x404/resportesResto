<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Caja web: apertura, ventas, movimientos, anulaciones y cierre.
 *
 * Escribe en las mismas tablas del POS (caja, venta, detalle_venta, pago_venta,
 * detalle_caja, detalle_pagocaja) para que los reportes existentes las muestren.
 * Las columnas que no existen en un cliente se omiten (ver insertar/actualizar).
 *
 * Stock: el trigger f_addconsolidado del POS descuenta stock_insumo según la receta
 * al insertar en detalle_venta. Si la base no tiene ese trigger, se descuenta acá
 * con la misma fórmula. Al anular, siempre se revierte con la misma fórmula.
 */
class Mcaja extends CI_Model
{
	const COMPUTADOR = 'CAJA WEB';
	const TZ = 'America/Santiago';
	// Códigos SII de documentos electrónicos (se imprimen como pendientes de emisión)
	const COD_SII_ELECTRONICOS = array(33, 34, 39, 41, 46, 52, 56, 61);
	const COD_SII_FACTURAS = array(33, 34, 46, 52, 56, 61);

	private $cols = array();

	function __construct()
	{
		parent::__construct();
		date_default_timezone_set(self::TZ);
	}

	// ====================== Lectura ======================

	public function cajaAbierta($idUsuario)
	{
		$db = $this->conexion();
		return $this->q($db, "SELECT c.*, to_char(c.fecha_movimiento, 'DD/MM/YYYY HH24:MI') AS apertura
			FROM caja c
			WHERE c.cerrada IS NOT TRUE AND c.nombre_computador = ? AND c.id_usuario = ?
			ORDER BY c.id_caja DESC LIMIT 1", array(self::COMPUTADOR, (int)$idUsuario))->row();
	}

	public function catalogo($idLprecio, $idUsuario, $idRol)
	{
		$db = $this->conexion();
		$config = $this->configuracion($db);
		$idLprecio = (int)$idLprecio ?: $config['id_lprecio_publico'];

		$tiene = $this->columnas($db, array('familia', 'producto', 'fpago', 'tdocumento', 'lprecio'));

		$q = "SELECT id_familia, nombre_familia FROM familia";
		if (isset($tiene['familia']['vigente'])) $q .= " WHERE vigente IS NOT FALSE";
		$familias = $this->q($db, $q . " ORDER BY nombre_familia")->result_array();

		// Precio de la lista elegida; si el producto no está en la lista, su precio_bruto
		$q = "SELECT p.id_producto, p.nombre_producto, COALESCE(p.cbarra, '') AS cbarra,
				COALESCE(p.id_familia, 0) AS id_familia,
				COALESCE(NULLIF(dl.total, 0), p.precio_bruto, 0) AS precio
			FROM producto p
			LEFT JOIN detalle_lprecio dl ON dl.id_producto = p.id_producto AND dl.id_lprecio = ?
			WHERE p.vigente IS TRUE AND p.venta_publico IS NOT FALSE";
		if (isset($tiene['producto']['permitir_venta_sin_precio'])) {
			$q .= " AND (COALESCE(NULLIF(dl.total, 0), p.precio_bruto, 0) > 0 OR p.permitir_venta_sin_precio IS TRUE)";
		} else {
			$q .= " AND COALESCE(NULLIF(dl.total, 0), p.precio_bruto, 0) > 0";
		}
		$productos = array();
		foreach ($this->q($db, $q . " ORDER BY p.nombre_producto", array($idLprecio))->result() as $p) {
			$productos[] = array(
				'id_producto' => (int)$p->id_producto,
				'nombre' => $p->nombre_producto,
				'cbarra' => trim($p->cbarra),
				'id_familia' => (int)$p->id_familia,
				'precio' => (int)round((float)$p->precio),
			);
		}

		$q = "SELECT id_fpago, nombre_fpago, (escortesia IS TRUE) AS cortesia FROM fpago";
		if (isset($tiene['fpago']['vigente'])) $q .= " WHERE vigente IS NOT FALSE";
		$formasPago = array();
		foreach ($this->q($db, $q . " ORDER BY id_fpago")->result() as $f) {
			$formasPago[] = array(
				'id_fpago' => (int)$f->id_fpago,
				'nombre' => $f->nombre_fpago,
				'cortesia' => $f->cortesia === 't' || $f->cortesia === true,
				'efectivo' => $this->esEfectivo($config, $f->id_fpago, $f->nombre_fpago),
				'credito' => (int)$f->id_fpago === $config['id_fpago_credito'],
			);
		}

		$documentos = array();
		foreach ($this->tiposDocumento($db) as $d) {
			$documentos[] = $d;
		}

		$listas = $this->q($db, "SELECT id_lprecio, nombre_lprecio FROM lprecio ORDER BY nombre_lprecio")->result_array();

		$agregados = array();
		foreach ($this->agregados($db, null) as $a) {
			$agregados[] = array(
				'id_agregado' => (int)$a->id_agregado,
				'id_producto_maestro' => (int)$a->id_producto_maestro,
				'nombre' => $a->nombre_producto,
				'valor' => (int)$a->valor,
			);
		}

		$mensajes = array();
		$tieneMsg = $this->columnas($db, array('mensaje_producto'));
		if (isset($tieneMsg['mensaje_producto'])) {
			$q = "SELECT id_producto, mensaje FROM mensaje_producto WHERE mensaje IS NOT NULL AND trim(mensaje) <> ''";
			if (isset($tieneMsg['mensaje_producto']['vigente'])) $q .= " AND vigente IS NOT FALSE";
			foreach ($this->q($db, $q . " ORDER BY id_producto, mensaje")->result() as $m) {
				$mensajes[] = array('id_producto' => (int)$m->id_producto, 'mensaje' => trim($m->mensaje));
			}
		}

		$config['es_admin'] = $this->esAdmin($db, $idUsuario, $idRol);
		$config['tiene_clave_admin'] = $this->claveAdminGeneral($db) !== '';
		// Nunca se envía la clave; solo quién puede autorizar
		$admins = array();
		foreach ($this->q($db, 'SELECT u.id_usuario, u.nombre_usuario FROM usuario u LEFT JOIN rol r ON r.id_rol = u.id_rol
			WHERE u.vigente IS NOT FALSE AND ' . self::SQL_ES_ADMIN . ' ORDER BY u.nombre_usuario')->result() as $u) {
			$admins[] = array('id_usuario' => (int)$u->id_usuario, 'nombre' => $u->nombre_usuario);
		}

		return array(
			'id_lprecio' => $idLprecio,
			'familias' => $familias,
			'productos' => $productos,
			'formas_pago' => $formasPago,
			'documentos' => $documentos,
			'listas_precio' => $listas,
			'agregados' => $agregados,
			'mensajes' => $mensajes,
			'administradores' => $admins,
			'config' => $config,
		);
	}

	/** Agregados vigentes (todos o de un producto maestro) con su producto. */
	private function agregados($db, $idMaestro)
	{
		$t = $this->columnas($db, array('agregados'));
		if (!isset($t['agregados'])) return array();
		$q = "SELECT a.id_agregado, a.id_producto_maestro, a.id_producto, p.nombre_producto, COALESCE(a.valor, 0) AS valor,
				COALESCE(p.id_receta, 0) AS id_receta, COALESCE(p.id_bodega, 0) AS id_bodega
			FROM agregados a JOIN producto p ON p.id_producto = a.id_producto
			WHERE p.vigente IS TRUE";
		if (isset($t['agregados']['vigente'])) $q .= " AND a.vigente IS NOT FALSE";
		if (isset($t['agregados']['eliminado'])) $q .= " AND a.eliminado IS NOT TRUE";
		$binds = array();
		if ($idMaestro !== null) {
			$q .= " AND a.id_producto_maestro = ?";
			$binds[] = (int)$idMaestro;
		}
		return $this->q($db, $q . " ORDER BY a.id_producto_maestro, p.nombre_producto", $binds)->result();
	}

	// ====================== Permisos y autorizaciones ======================

	// Administrador del POS: marcado esadmin o con rol de administración/gerencia
	const SQL_ES_ADMIN = "(u.esadmin IS TRUE OR r.nombre_rol ILIKE '%ADMIN%' OR r.nombre_rol ILIKE '%GERENCIA%')";
	const MODULO = 'Caja';
	const SISTEMA_WEB = 'RESTO WEB';

	/**
	 * Registra el módulo en psistema (como el resto del menú web) la primera vez,
	 * habilitado para todos los roles existentes, para no quitarle el acceso a nadie.
	 * Después se administra desde Privilegios de Usuario.
	 */
	public function registrarModulo()
	{
		$db = $this->conexion();
		$t = $this->columnas($db, array('psistema', 'pusuario', 'rol'));
		if (!isset($t['psistema'], $t['pusuario'], $t['rol'])) return;
		$existe = $this->q($db, 'SELECT id_psistema FROM psistema WHERE nombre_psistema = ? AND lower(nombre_vb) = lower(?) LIMIT 1',
			array(self::SISTEMA_WEB, self::MODULO))->row();
		if ($existe) return;
		$db->trans_begin();
		try {
			$id = $this->insertar($db, $t, 'psistema', array(
				'nombre_psistema' => self::SISTEMA_WEB,
				'nombre_vb' => self::MODULO,
				'vigente' => true,
			), 'id_psistema');
			$this->q($db, 'INSERT INTO pusuario (id_rol, id_psistema, autoriza) SELECT id_rol, ?, true FROM rol', array($id));
			$db->trans_commit();
		} catch (Exception $e) {
			$db->trans_rollback();
			log_message('error', 'Mcaja registrarModulo: ' . $e->getMessage());
		}
	}

	/** Mismo criterio que el menú: si el módulo no está registrado, se permite. */
	public function tienePermiso($idRol)
	{
		if ((int)$idRol === 1) return true;
		$db = $this->conexion();
		$r = $this->q($db, 'SELECT COALESCE(pu.autoriza, false) AS autoriza
			FROM psistema ps LEFT JOIN pusuario pu ON pu.id_psistema = ps.id_psistema AND pu.id_rol = ?
			WHERE ps.vigente = true AND ps.nombre_psistema = ? AND lower(ps.nombre_vb) = lower(?) LIMIT 1',
			array((int)$idRol, self::SISTEMA_WEB, self::MODULO))->row();
		return !$r || $r->autoriza === 't' || $r->autoriza === true;
	}

	private function esAdmin($db, $idUsuario, $idRol)
	{
		if ((int)$idRol === 1) return true;
		$r = $this->q($db, 'SELECT 1 FROM usuario u LEFT JOIN rol r ON r.id_rol = u.id_rol
			WHERE u.id_usuario = ? AND ' . self::SQL_ES_ADMIN, array((int)$idUsuario))->row();
		return (bool)$r;
	}

	private function claveAdminGeneral($db)
	{
		$t = $this->columnas($db, array('configuracion'));
		if (!isset($t['configuracion']['pass_admin'])) return '';
		$r = $this->q($db, 'SELECT COALESCE(pass_admin, \'\') AS clave FROM configuracion LIMIT 1')->row();
		return $r ? trim($r->clave) : '';
	}

	/**
	 * Valida que la acción la autorice un administrador.
	 * $aut = {id_usuario, clave}; id_usuario 0 = clave de administración de Configuraciones.
	 * Lanza 403 con autorizacion=true para que la pantalla pida la clave.
	 */
	private function exigirAutorizacion($db, $motivo, $idUsuario, $idRol, $aut)
	{
		if ($this->esAdmin($db, $idUsuario, $idRol)) return;
		$clave = is_array($aut) && isset($aut['clave']) ? (string)$aut['clave'] : '';
		if ($clave === '') throw new AutorizacionRequerida($motivo);
		$idAdmin = (int)(isset($aut['id_usuario']) ? $aut['id_usuario'] : 0);
		if ($idAdmin === 0) {
			$general = $this->claveAdminGeneral($db);
			if ($general !== '' && hash_equals($general, $clave)) return;
		} else {
			$u = $this->q($db, 'SELECT COALESCE(u.clave, \'\') AS clave FROM usuario u LEFT JOIN rol r ON r.id_rol = u.id_rol
				WHERE u.id_usuario = ? AND u.vigente IS NOT FALSE AND ' . self::SQL_ES_ADMIN, array($idAdmin))->row();
			if ($u && $u->clave !== '' && hash_equals($u->clave, $clave)) {
				log_message('info', 'Caja web: ' . $motivo . ' autorizado por usuario ' . $idAdmin . ' (cajero ' . (int)$idUsuario . ')');
				return;
			}
		}
		throw new AutorizacionRequerida($motivo, 'Clave de administrador incorrecta');
	}

	public function buscarClientes($texto)
	{
		$db = $this->conexion();
		$texto = trim((string)$texto);
		$binds = array();
		$q = "SELECT id_cliente, nombre_cliente, COALESCE(rut::text, '') AS rut, COALESCE(dv, '') AS dv,
				COALESCE(giro, '') AS giro, COALESCE(direccion, '') AS direccion
			FROM cliente WHERE nombre_cliente IS NOT NULL";
		if ($texto !== '') {
			$q .= " AND (nombre_cliente ILIKE ? OR rut::text || '-' || COALESCE(dv, '') ILIKE ?)";
			$like = '%' . str_replace(array('.', '%', '_'), array('', '\%', '\_'), $texto) . '%';
			$binds = array('%' . $texto . '%', $like);
		}
		return $this->q($db, $q . " ORDER BY nombre_cliente LIMIT 30", $binds)->result_array();
	}

	public function guardarCliente($d)
	{
		$db = $this->conexion();
		$nombre = trim(isset($d['nombre_cliente']) ? $d['nombre_cliente'] : '');
		if ($nombre === '') throw new Exception('El nombre del cliente es obligatorio', 400);
		list($rut, $dv) = $this->separarRut(isset($d['rut']) ? $d['rut'] : '');
		if ($rut) {
			$existe = $this->q($db, 'SELECT id_cliente FROM cliente WHERE rut = ? LIMIT 1', array($rut))->row();
			if ($existe) throw new Exception('Ya existe un cliente con ese RUT', 409);
		}
		$cols = $this->columnas($db, array('cliente'));
		$id = $this->insertar($db, $cols, 'cliente', array(
			'nombre_cliente' => mb_substr($nombre, 0, 100),
			'rut' => $rut ?: null,
			'dv' => $rut ? $dv : null,
			'giro' => mb_substr(trim(isset($d['giro']) ? $d['giro'] : ''), 0, 100),
			'direccion' => mb_substr(trim(isset($d['direccion']) ? $d['direccion'] : ''), 0, 100),
			'telefono' => mb_substr(trim(isset($d['telefono']) ? $d['telefono'] : ''), 0, 30),
			'mail' => mb_substr(trim(isset($d['mail']) ? $d['mail'] : ''), 0, 100),
			'id_comuna' => 0,
			'id_lprecio' => 0,
			'creditomaximo' => 0,
		), 'id_cliente');
		return array('id_cliente' => $id, 'nombre_cliente' => $nombre, 'rut' => $rut ? $rut . '-' . $dv : '');
	}

	public function ventasCaja($idCaja)
	{
		$db = $this->conexion();
		$ventas = $this->q($db, "SELECT v.id_venta, v.folio, v.total, v.descuento, (v.es_cortesia IS TRUE) AS cortesia,
				to_char(v.fecha_transaccion, 'HH24:MI') AS hora, td.nombre_tdocumento,
				COALESCE(c.nombre_cliente, '') AS nombre_cliente,
				(COALESCE(v.mensaje_pedido, '') LIKE 'ANULADA%') AS anulada,
				COALESCE((SELECT string_agg(f.nombre_fpago, ', ') FROM pago_venta pv JOIN fpago f ON f.id_fpago = pv.id_fpago
					WHERE pv.id_venta = v.id_venta), '') AS pagos
			FROM venta v
			LEFT JOIN tdocumento td ON td.id_tdocumento = v.id_tdocumento
			LEFT JOIN cliente c ON c.id_cliente = v.id_cliente
			WHERE v.id_caja = ?
			ORDER BY v.id_venta DESC", array((int)$idCaja))->result_array();
		foreach ($ventas as &$v) {
			$v['anulada'] = $v['anulada'] === 't' || $v['anulada'] === true;
			$v['cortesia'] = $v['cortesia'] === 't' || $v['cortesia'] === true;
		}
		return $ventas;
	}

	public function movimientosCaja($idCaja)
	{
		$db = $this->conexion();
		return $this->q($db, "SELECT id_detalle_caja, ingreso, egreso, COALESCE(observacion, '') AS observacion,
				to_char(fecha_movimiento, 'DD/MM HH24:MI') AS fecha
			FROM detalle_caja WHERE id_caja = ? ORDER BY id_detalle_caja DESC", array((int)$idCaja))->result_array();
	}

	/** Lo que el sistema espera en la caja, por forma de pago. */
	public function resumenCaja($caja)
	{
		$db = $this->conexion();
		$config = $this->configuracion($db);
		$idCaja = (int)$caja->id_caja;

		$t = $this->q($db, "SELECT COALESCE(sum(CASE WHEN es_cortesia IS TRUE THEN 0 ELSE total END), 0) AS ventas,
				COALESCE(sum(CASE WHEN es_cortesia IS TRUE THEN total ELSE 0 END), 0) AS cortesias,
				COALESCE(sum(descuento), 0) AS descuentos,
				count(*) FILTER (WHERE COALESCE(mensaje_pedido, '') NOT LIKE 'ANULADA%') AS cantidad,
				count(*) FILTER (WHERE COALESCE(mensaje_pedido, '') LIKE 'ANULADA%') AS anuladas
			FROM venta WHERE id_caja = ?", array($idCaja))->row();

		$m = $this->q($db, "SELECT COALESCE(sum(ingreso), 0) AS ingresos, COALESCE(sum(egreso), 0) AS egresos
			FROM detalle_caja WHERE id_caja = ?", array($idCaja))->row();

		$porFpago = array();
		$efectivoVentas = 0;
		$efectivoPropinas = 0;
		$propinas = 0;
		$rows = $this->q($db, "SELECT f.id_fpago, f.nombre_fpago, (f.escortesia IS TRUE) AS cortesia,
				COALESCE(sum(pv.monto_pagado), 0) AS monto, COALESCE(sum(pv.propina), 0) AS propina
			FROM pago_venta pv
			JOIN venta v ON v.id_venta = pv.id_venta
			JOIN fpago f ON f.id_fpago = pv.id_fpago
			WHERE v.id_caja = ?
			GROUP BY f.id_fpago, f.nombre_fpago, f.escortesia
			ORDER BY f.id_fpago", array($idCaja))->result();
		foreach ($rows as $r) {
			$efectivo = $this->esEfectivo($config, $r->id_fpago, $r->nombre_fpago);
			$cortesia = $r->cortesia === 't' || $r->cortesia === true;
			if ($efectivo) {
				$efectivoVentas += (int)$r->monto;
				$efectivoPropinas += (int)$r->propina;
			}
			$propinas += (int)$r->propina;
			$porFpago[] = array(
				'id_fpago' => (int)$r->id_fpago,
				'nombre' => $r->nombre_fpago,
				'monto' => (int)$r->monto,
				'propina' => (int)$r->propina,
				'efectivo' => $efectivo,
				'cortesia' => $cortesia,
			);
		}

		$fondo = (int)$caja->fondo_fijo_anterior ?: (int)$caja->fondo_fijo;
		return array(
			'id_caja' => $idCaja,
			'apertura' => $caja->apertura,
			'fondo' => $fondo,
			'ventas' => (int)$t->ventas,
			'cortesias' => (int)$t->cortesias,
			'descuentos' => (int)$t->descuentos,
			'cantidad_ventas' => (int)$t->cantidad,
			'anuladas' => (int)$t->anuladas,
			'ingresos' => (int)$m->ingresos,
			'egresos' => (int)$m->egresos,
			'propinas' => $propinas,
			'efectivo_ventas' => $efectivoVentas,
			// Lo que físicamente debe haber en efectivo, incluidas las propinas cobradas en efectivo
			'efectivo_esperado' => $fondo + $efectivoVentas + $efectivoPropinas + (int)$m->ingresos - (int)$m->egresos,
			'por_forma_pago' => $porFpago,
		);
	}

	/** Cabecera, detalle y pagos para imprimir un documento. */
	public function documento($idVenta)
	{
		$db = $this->conexion();
		$v = $this->q($db, "SELECT v.*, to_char(v.fecha_transaccion, 'DD/MM/YYYY HH24:MI') AS fecha_texto,
				td.nombre_tdocumento, COALESCE(td.cod_sii, 0) AS cod_sii,
				c.nombre_cliente, c.rut, c.dv, c.giro, c.direccion, u.nombre_usuario
			FROM venta v
			LEFT JOIN tdocumento td ON td.id_tdocumento = v.id_tdocumento
			LEFT JOIN cliente c ON c.id_cliente = v.id_cliente
			LEFT JOIN usuario u ON u.id_usuario = v.id_usuario
			WHERE v.id_venta = ?", array((int)$idVenta))->row();
		if (!$v) return null;

		$detalle = $this->q($db, "SELECT COALESCE(p.nombre_producto, 'Producto ' || dv.id_producto)
					|| COALESCE(' + ' || pa.nombre_producto, '') AS nombre,
				dv.cantidad, dv.bruto_un, dv.subtotal
			FROM detalle_venta dv
			LEFT JOIN producto p ON p.id_producto = dv.id_producto
			LEFT JOIN producto pa ON pa.id_producto = dv.id_agregado AND dv.id_agregado > 0
			WHERE dv.id_venta = ? ORDER BY dv.id_detalle_venta", array((int)$idVenta))->result();
		$pagos = $this->q($db, "SELECT f.nombre_fpago, pv.monto_pagado, pv.vuelto, pv.paga_con
			FROM pago_venta pv JOIN fpago f ON f.id_fpago = pv.id_fpago
			WHERE pv.id_venta = ? ORDER BY pv.id_pventa", array((int)$idVenta))->result();
		$empresa = $this->q($db, "SELECT * FROM empresa ORDER BY id_empresa LIMIT 1")->row();

		return array(
			'venta' => $v,
			'detalle' => $detalle,
			'pagos' => $pagos,
			'empresa' => $empresa,
			'anulada' => strpos((string)$v->mensaje_pedido, 'ANULADA') === 0,
			'electronico' => in_array((int)$v->cod_sii, self::COD_SII_ELECTRONICOS),
		);
	}

	// ====================== Escritura ======================

	public function abrirCaja($idUsuario, $fondo)
	{
		$db = $this->conexion();
		if ($this->cajaAbierta($idUsuario)) throw new Exception('Ya tienes una caja abierta', 409);
		$cols = $this->columnas($db, array('caja'));
		$ahora = date('Y-m-d H:i:s');
		$r = $this->q($db, 'SELECT min(id_turno) AS id FROM turno')->row();
		return $this->insertar($db, $cols, 'caja', array(
			'fecha' => date('Y-m-d') . ' 00:00:00',
			'id_usuario' => (int)$idUsuario,
			'ingresos' => 0,
			'egresos' => 0,
			'cerrada' => false,
			'fondo_fijo' => (int)$fondo,
			'fondo_fijo_anterior' => (int)$fondo,
			'fecha_movimiento' => $ahora,
			'id_turno' => $r && $r->id ? (int)$r->id : 0,
			'nombre_computador' => self::COMPUTADOR,
		), 'id_caja');
	}

	public function registrarMovimiento($caja, $tipo, $monto, $observacion)
	{
		$db = $this->conexion();
		$monto = (int)$monto;
		$observacion = trim((string)$observacion);
		if ($monto <= 0) throw new Exception('El monto debe ser mayor a cero', 400);
		if ($observacion === '') throw new Exception('Indica el motivo del movimiento', 400);
		$esIngreso = $tipo === 'ingreso';

		$cols = $this->columnas($db, array('detalle_caja', 'caja'));
		$db->trans_begin();
		try {
			$this->insertar($db, $cols, 'detalle_caja', array(
				'id_caja' => (int)$caja->id_caja,
				'ingreso' => $esIngreso ? $monto : 0,
				'egreso' => $esIngreso ? 0 : $monto,
				'observacion' => mb_substr($observacion, 0, 100),
				'descripcion_corta' => mb_substr($observacion, 0, 50),
				'id_ccosto' => 0,
				'fecha_movimiento' => date('Y-m-d H:i:s'),
				'vigente' => true,
			), null);
			$this->q($db, 'UPDATE caja SET ingresos = (SELECT COALESCE(sum(ingreso), 0) FROM detalle_caja WHERE id_caja = ?),
				egresos = (SELECT COALESCE(sum(egreso), 0) FROM detalle_caja WHERE id_caja = ?) WHERE id_caja = ?',
				array((int)$caja->id_caja, (int)$caja->id_caja, (int)$caja->id_caja));
			$db->trans_commit();
		} catch (Exception $e) {
			$db->trans_rollback();
			throw $e;
		}
	}

	/**
	 * Registra una venta. Los precios se recalculan desde la lista de precios
	 * (no se confía en los del navegador).
	 *
	 * $p = {id_tdocumento, id_cliente, id_lprecio, descuento, propina, observacion,
	 *       detalle: [{id_producto, cantidad, agregados: [id_agregado], nota}],
	 *       pagos: [{id_fpago, monto}], autorizacion: {id_usuario, clave}}
	 *
	 * Agregados como el POS: el primero va en la misma línea (detalle_venta.id_agregado
	 * = producto agregado, precio = producto + valor del agregado) y el trigger descuenta
	 * su receta; los siguientes van como línea propia del producto agregado a su valor.
	 */
	public function registrarVenta($caja, $idUsuario, $idRol, $p)
	{
		$db = $this->conexion();
		$config = $this->configuracion($db);
		$cols = $this->columnas($db, array('venta', 'detalle_venta', 'pago_venta', 'consolidado_ventas', 'tdocumento', 'producto'));
		$factor = 1 + $config['por_iva'] / 100;
		$idLprecio = !empty($p['id_lprecio']) ? (int)$p['id_lprecio'] : $config['id_lprecio_publico'];

		// --- Documento y cliente
		$idTdoc = (int)(isset($p['id_tdocumento']) ? $p['id_tdocumento'] : 0);
		$doc = null;
		foreach ($this->tiposDocumento($db) as $d) {
			if ($d['id_tdocumento'] === $idTdoc) $doc = $d;
		}
		if (!$doc) throw new Exception('Tipo de documento no válido para venta', 400);

		$idCliente = (int)(isset($p['id_cliente']) ? $p['id_cliente'] : 0);
		$cliente = $idCliente ? $this->q($db, 'SELECT id_cliente, nombre_cliente, rut, dv, giro, direccion FROM cliente WHERE id_cliente = ?',
			array($idCliente))->row() : null;
		if ($idCliente && !$cliente) throw new Exception('El cliente no existe', 400);
		$esPublico = !$cliente || (int)$cliente->id_cliente === $config['id_cliente_publico'];
		if ($doc['requiere_cliente'] && $esPublico) {
			throw new Exception('El documento ' . $doc['nombre'] . ' requiere seleccionar un cliente', 400);
		}
		if ($doc['es_factura'] && (!$cliente || !(int)$cliente->rut || trim((string)$cliente->giro) === '' || trim((string)$cliente->direccion) === '')) {
			throw new Exception('Para emitir ' . $doc['nombre'] . ' el cliente debe tener RUT, giro y dirección', 400);
		}
		if (!$cliente) $idCliente = $config['id_cliente_publico'];

		// --- Detalle con precios del servidor
		$items = isset($p['detalle']) && is_array($p['detalle']) ? $p['detalle'] : array();
		if (!$items) throw new Exception('La venta no tiene productos', 400);
		$ids = array();
		foreach ($items as $it) $ids[] = (int)$it['id_producto'];
		$selVss = isset($cols['producto']['venta_segun_stock']) ? '(p.venta_segun_stock IS TRUE)' : 'false';
		$productos = array();
		$q = "SELECT p.id_producto, p.nombre_producto, COALESCE(p.id_receta, 0) AS id_receta, COALESCE(p.id_bodega, 0) AS id_bodega,
				COALESCE(NULLIF(dl.total, 0), p.precio_bruto, 0) AS precio, $selVss AS segun_stock
			FROM producto p
			LEFT JOIN detalle_lprecio dl ON dl.id_producto = p.id_producto AND dl.id_lprecio = ?
			WHERE p.vigente IS TRUE AND p.id_producto IN (" . implode(',', array_map('intval', $ids)) . ")";
		foreach ($this->q($db, $q, array($idLprecio))->result() as $r) {
			$productos[(int)$r->id_producto] = $r;
		}
		$agregadosPorMaestro = array();
		foreach ($this->agregados($db, null) as $a) {
			$agregadosPorMaestro[(int)$a->id_producto_maestro][(int)$a->id_agregado] = $a;
		}

		$lineas = array();
		$notas = array();
		$subtotal = 0;
		foreach ($items as $it) {
			$id = (int)$it['id_producto'];
			$cantidad = round((float)$it['cantidad'], 3);
			if (!isset($productos[$id])) throw new Exception('El producto ' . $id . ' no existe o no está vigente', 400);
			$prod = $productos[$id];
			if ($cantidad <= 0) throw new Exception('Cantidad inválida para ' . $prod->nombre_producto, 400);
			$segunStock = $prod->segun_stock === 't' || $prod->segun_stock === true;

			$elegidos = array();
			foreach ((isset($it['agregados']) && is_array($it['agregados']) ? $it['agregados'] : array()) as $idAg) {
				if (!isset($agregadosPorMaestro[$id][(int)$idAg])) {
					throw new Exception('Un agregado de ' . $prod->nombre_producto . ' ya no está disponible', 400);
				}
				$elegidos[(int)$idAg] = $agregadosPorMaestro[$id][(int)$idAg];
			}
			$elegidos = array_values($elegidos);
			if ($config['max_agregados'] > 0 && count($elegidos) > $config['max_agregados']) {
				throw new Exception('Máximo ' . $config['max_agregados'] . ' agregado(s) por producto', 400);
			}

			$nota = trim(isset($it['nota']) ? (string)$it['nota'] : '');
			$nombres = array();
			foreach ($elegidos as $a) $nombres[] = '+' . $a->nombre_producto;
			if ($nota !== '' || $nombres) {
				$notas[] = $prod->nombre_producto . ($nombres ? ' ' . implode(' ', $nombres) : '') . ($nota !== '' ? ': ' . $nota : '');
			}

			$primero = $elegidos ? array_shift($elegidos) : null;
			$unitario = (int)round((float)$prod->precio) + ($primero ? (int)$primero->valor : 0);
			$bruto = (int)round($unitario * $cantidad);
			$subtotal += $bruto;
			$recetas = array(array((int)$prod->id_receta, (int)$prod->id_bodega));
			if ($primero) $recetas[] = array((int)$primero->id_receta, (int)$prod->id_bodega);
			$lineas[] = array(
				'id_producto' => $id, 'nombre' => $prod->nombre_producto, 'cantidad' => $cantidad,
				'unitario' => $unitario, 'bruto' => $bruto, 'id_agregado' => $primero ? (int)$primero->id_producto : 0,
				'nota' => $nota, 'segun_stock' => $segunStock, 'recetas' => $recetas,
			);
			foreach ($elegidos as $a) {
				$bruto = (int)round((int)$a->valor * $cantidad);
				$subtotal += $bruto;
				$lineas[] = array(
					'id_producto' => (int)$a->id_producto, 'nombre' => $a->nombre_producto, 'cantidad' => $cantidad,
					'unitario' => (int)$a->valor, 'bruto' => $bruto, 'id_agregado' => 0, 'nota' => '',
					'segun_stock' => $segunStock, 'recetas' => array(array((int)$a->id_receta, (int)$a->id_bodega)),
				);
			}
		}

		$descuento = max(0, (int)(isset($p['descuento']) ? $p['descuento'] : 0));
		if ($descuento > $subtotal) throw new Exception('El descuento no puede superar el subtotal', 400);
		$porDescuento = $subtotal > 0 ? round($descuento * 100 / $subtotal, 2) : 0;
		if ($config['por_maximo_descuento'] > 0 && $porDescuento > $config['por_maximo_descuento']) {
			throw new Exception('El descuento máximo permitido es ' . $config['por_maximo_descuento'] . '%', 400);
		}
		// El trigger f_adddescuentoproducto divide por el subtotal de la venta
		if ($subtotal <= 0) throw new Exception('La venta debe tener un monto mayor a cero', 400);
		$total = $subtotal - $descuento;
		$neto = (int)round($total / $factor);
		$iva = $total - $neto;

		// --- Pagos
		$fpagos = array();
		foreach ($this->catalogoFpago($db, $config) as $f) $fpagos[$f['id_fpago']] = $f;
		$pagos = array();
		$suma = 0;
		$cortesias = 0;
		$conCredito = false;
		$idxEfectivo = -1;
		foreach ((isset($p['pagos']) && is_array($p['pagos']) ? $p['pagos'] : array()) as $pg) {
			$idF = (int)$pg['id_fpago'];
			$monto = (int)$pg['monto'];
			if ($monto <= 0) continue;
			if (!isset($fpagos[$idF])) throw new Exception('Forma de pago no válida', 400);
			if ($fpagos[$idF]['cortesia']) $cortesias++;
			if ($fpagos[$idF]['credito']) {
				if ($esPublico) throw new Exception('El pago con ' . $fpagos[$idF]['nombre'] . ' requiere seleccionar un cliente', 400);
				$conCredito = true;
			}
			if ($fpagos[$idF]['efectivo']) $idxEfectivo = count($pagos);
			$pagos[] = array('id_fpago' => $idF, 'monto' => $monto, 'vuelto' => 0, 'propina' => 0);
			$suma += $monto;
		}
		if (!$pagos) throw new Exception('Ingresa al menos un pago', 400);
		if ($cortesias && $cortesias !== count($pagos)) throw new Exception('Una cortesía no se puede mezclar con otras formas de pago', 400);
		$esCortesia = $cortesias > 0;

		$propina = max(0, (int)(isset($p['propina']) ? $p['propina'] : 0));
		if ($propina > 0 && !$config['propina']) throw new Exception('La propina en venta directa no está habilitada en Configuraciones', 400);
		if ($propina > 0 && $esCortesia) throw new Exception('Una cortesía no lleva propina', 400);

		$requerido = $total + $propina;
		if ($suma < $requerido) throw new Exception('Falta pagar $' . number_format($requerido - $suma, 0, ',', '.'), 400);
		$vuelto = $suma - $requerido;
		if ($vuelto > 0) {
			if ($idxEfectivo < 0 || $pagos[$idxEfectivo]['monto'] <= $vuelto) {
				throw new Exception('Solo se puede dar vuelto sobre un pago en efectivo', 400);
			}
			$pagos[$idxEfectivo]['vuelto'] = $vuelto;
		}
		// La propina se registra en el pago en efectivo; si no hay, en el último pago
		$idxPropina = -1;
		if ($propina > 0) {
			$idxPropina = $idxEfectivo >= 0 ? $idxEfectivo : count($pagos) - 1;
			if ($pagos[$idxPropina]['monto'] - $pagos[$idxPropina]['vuelto'] < $propina) {
				throw new Exception('La propina debe quedar dentro de un solo pago (' . $fpagos[$pagos[$idxPropina]['id_fpago']]['nombre'] . ')', 400);
			}
			$pagos[$idxPropina]['propina'] = $propina;
		}

		// --- Autorizaciones de administrador (Configuraciones)
		$requiere = array();
		if ($descuento > 0 && $config['admin_descuento']) $requiere[] = 'descuento';
		if ($esCortesia && $config['admin_cortesia']) $requiere[] = 'cortesía';
		if ($conCredito && $config['admin_credito']) $requiere[] = 'venta a crédito';
		if ($requiere) {
			$this->exigirAutorizacion($db, implode(', ', $requiere), $idUsuario, $idRol,
				isset($p['autorizacion']) ? $p['autorizacion'] : null);
		}

		$observacion = trim(isset($p['observacion']) ? (string)$p['observacion'] : '');
		if ($notas) $observacion = trim($observacion . ($observacion !== '' ? ' | ' : '') . implode(' | ', $notas));
		// "ANULADA..." al inicio de mensaje_pedido es la marca de venta anulada
		if (stripos($observacion, 'ANULADA') === 0) $observacion = 'Obs: ' . $observacion;
		$ahora = date('Y-m-d H:i:s');
		$dia = date('Y-m-d');

		$db->trans_begin();
		try {
			$this->q($db, 'SET LOCAL TIME ZONE ' . $db->escape(self::TZ));
			// La caja pudo cerrarse en otra pestaña
			$c = $this->q($db, 'SELECT cerrada FROM caja WHERE id_caja = ? FOR UPDATE', array((int)$caja->id_caja))->row();
			if (!$c || $c->cerrada === 't' || $c->cerrada === true) throw new Exception('La caja ya está cerrada', 409);

			$this->validarStock($db, $lineas);

			$r = $this->q($db, 'UPDATE tdocumento SET folio = COALESCE(folio, 0) + 1 WHERE id_tdocumento = ?
				RETURNING folio, COALESCE(folio_final, 0) AS folio_final', array($idTdoc))->row();
			$folio = (int)$r->folio;
			if ($r->folio_final > 0 && $folio > $r->folio_final) {
				throw new Exception('No quedan folios disponibles para ' . $doc['nombre'], 409);
			}

			$idVenta = $this->insertar($db, $cols, 'venta', array(
				'id_tdocumento' => $idTdoc,
				'id_cliente' => $idCliente,
				'id_lprecio' => $idLprecio,
				'id_usuario' => (int)$idUsuario,
				'fecha' => $dia . ' 00:00:00', // como el POS: los reportes filtran por día
				'fecha_transaccion' => $ahora,
				'esconsignacion' => false,
				'subtotal' => $subtotal,
				'descuento' => $descuento,
				'neto' => $neto,
				'iva' => $iva,
				'ila' => 0,
				'total' => $total,
				'id_caja' => (int)$caja->id_caja,
				'folio' => $folio,
				'id_usuario_garzon' => (int)$idUsuario,
				'id_mesa' => 0,
				'id_fpagopropina' => $idxPropina >= 0 ? $pagos[$idxPropina]['id_fpago'] : 0,
				'valor_propina' => $propina,
				'propina_final' => $propina,
				'por_comision_propina' => 0,
				'monto_comision_propina' => 0,
				'por_descuento' => $porDescuento,
				'monto_descuento' => $descuento,
				'subtotal_condescuento' => $total,
				'nombre_computador' => self::COMPUTADOR,
				'version_pdv' => self::COMPUTADOR,
				'mensaje_pedido' => $observacion === '' ? null : mb_substr($observacion, 0, 200),
				'observacion' => $observacion === '' ? null : $observacion,
				'es_cortesia' => $esCortesia,
				'venta_directa' => true,
				'fecha_vencimiento' => $dia,
				'id_canal_venta' => $this->primerId($db, 'canal_venta', 'id_canal_venta', 1),
				'monto_exento' => 0,
				'monto_afecto' => $esCortesia ? 0 : $total,
				'monto_cortesia' => $esCortesia ? $total : 0,
				'vigente' => true,
				'esventa' => true,
				'subtotal_bruto' => $subtotal,
				'subtotal_neto' => $neto,
				'subtotal_neto_sin_descuento' => $subtotal / $factor,
				'monto_descuento_global' => $descuento,
				'porcentaje_descuento_global' => $porDescuento,
				'descuento_global_prorrateado_bruto' => $descuento,
				'descuento_global_prorrateado_neto' => $descuento / $factor,
				'subtotal_neto_con_descuento' => $neto,
				'monto_iva' => $iva,
				'total_bruto_con_descuento' => $total,
				'folio_dte' => 0,
			), 'id_venta');

			// El descuento global se reparte entre las líneas según su monto
			$repartido = 0;
			$n = count($lineas);
			foreach ($lineas as $i => $l) {
				$descLinea = $i === $n - 1 ? $descuento - $repartido
					: ($subtotal > 0 ? (int)round($descuento * $l['bruto'] / $subtotal) : 0);
				$repartido += $descLinea;
				$conDesc = $l['bruto'] - $descLinea;
				$netoLinea = $l['bruto'] / $factor;
				$netoConDesc = $conDesc / $factor;
				$this->insertar($db, $cols, 'detalle_venta', array(
					'id_venta' => $idVenta,
					'id_producto' => $l['id_producto'],
					'nombre_producto' => mb_substr($l['nombre'], 0, 200),
					'descripcion_general' => $l['nota'] !== '' ? $l['nota'] : null,
					'cantidad' => $l['cantidad'],
					'bruto_un' => $l['unitario'],
					'bruto_un_lista' => $l['unitario'],
					'precio_unitario_bruto' => $l['unitario'],
					'precio_unitario_neto' => $l['unitario'] / $factor,
					'neto_un' => $l['unitario'] / $factor,
					'neto_unitario' => $l['unitario'] / $factor,
					'subtotal' => $l['bruto'],
					'descuento' => 0,
					'descuento_porcentual' => 0,
					'total_bruto' => $l['bruto'],
					'neto' => $netoLinea,
					'iva' => (int)round($l['bruto'] - $netoLinea),
					'ila' => 0,
					'poriva' => $config['por_iva'],
					'porila' => 0,
					'espack' => false,
					'es_merma' => false,
					'esmerma' => false,
					'id_agregado' => $l['id_agregado'],
					'id_lprecio' => $idLprecio,
					'nombre_computador' => self::COMPUTADOR,
					'fecha_transaccion' => $ahora,
					'fecha_pedido' => $ahora,
					'fecha_venta' => $dia,
					'subtotal_neto' => $netoLinea,
					'subtotal_neto_sin_descuento' => $netoLinea,
					'monto_descuento_global' => $descLinea,
					'porcentaje_descuento_global' => $porDescuento,
					'descuento_global_prorrateado_bruto' => $descLinea,
					'descuento_global_prorrateado_neto' => $descLinea / $factor,
					'subtotal_neto_con_descuento' => $netoConDesc,
					'monto_iva' => $conDesc - $netoConDesc,
					'monto_ila' => 0,
					'total_bruto_con_descuento' => $conDesc,
					'monto_exento' => 0,
				), null);
			}

			if (!$this->tieneTriggerStock($db)) {
				$this->descontarStock($db, $cols, $idVenta);
			}

			foreach ($pagos as $pg) {
				$this->insertar($db, $cols, 'pago_venta', array(
					'id_cliente' => $idCliente,
					'id_venta' => $idVenta,
					'id_fpago' => $pg['id_fpago'],
					'fecha_vencimiento' => $dia,
					'numero_documento' => '',
					'valor_compra' => $total,
					// paga_con es lo que entregó el cliente; el trigger de crédito directo lo usa como deuda
					'paga_con' => $pg['monto'],
					'pagado' => 0,
					// monto_pagado es solo venta (así lo suman los reportes); la propina va aparte
					'propina' => $pg['propina'],
					'subtotal' => $pg['monto'] - $pg['vuelto'] - $pg['propina'],
					'monto_pagado' => $pg['monto'] - $pg['vuelto'] - $pg['propina'],
					'vuelto' => $pg['vuelto'],
					'fecha_movimiento' => $ahora,
					'fecha_pago' => $dia,
				), null);
			}

			$db->trans_commit();
			return array('id_venta' => $idVenta, 'folio' => $folio, 'total' => $total, 'propina' => $propina,
				'vuelto' => $vuelto, 'documento' => $doc['nombre']);
		} catch (Exception $e) {
			$db->trans_rollback();
			throw $e;
		}
	}

	/**
	 * Anula una venta de la caja abierta: revierte el stock de insumos, borra
	 * líneas y pagos (los reportes no filtran por vigente) y deja la cabecera en 0.
	 * El folio queda usado, como en el POS.
	 */
	public function anularVenta($caja, $idUsuario, $idRol, $idVenta, $motivo, $autorizacion)
	{
		$db = $this->conexion();
		$motivo = trim((string)$motivo);
		if ($motivo === '') throw new Exception('Indica el motivo de la anulación', 400);
		$config = $this->configuracion($db);
		if ($config['admin_anulacion']) $this->exigirAutorizacion($db, 'anular venta', $idUsuario, $idRol, $autorizacion);
		$cols = $this->columnas($db, array('venta', 'ctacte_cliente', 'consolidado_ventas', 'anulacion_ticket'));

		$db->trans_begin();
		try {
			$v = $this->q($db, 'SELECT id_venta, id_caja, folio, total, COALESCE(mensaje_pedido, \'\') AS mensaje_pedido
				FROM venta WHERE id_venta = ? FOR UPDATE', array((int)$idVenta))->row();
			if (!$v || (int)$v->id_caja !== (int)$caja->id_caja) throw new Exception('La venta no pertenece a tu caja abierta', 404);
			if (strpos($v->mensaje_pedido, 'ANULADA') === 0) throw new Exception('La venta ya estaba anulada', 409);

			$this->revertirStock($db, (int)$idVenta);

			$this->q($db, 'DELETE FROM pago_venta WHERE id_venta = ?', array((int)$idVenta));
			if (isset($cols['ctacte_cliente']['id_venta'])) {
				$this->q($db, 'DELETE FROM ctacte_cliente WHERE id_venta = ?', array((int)$idVenta));
			}
			if (isset($cols['consolidado_ventas']['id_venta'])) {
				$this->q($db, 'DELETE FROM consolidado_ventas WHERE id_venta = ?', array((int)$idVenta));
			}
			$this->q($db, 'DELETE FROM detalle_venta WHERE id_venta = ?', array((int)$idVenta));

			$cero = array();
			foreach (array('subtotal', 'descuento', 'neto', 'iva', 'total', 'monto_descuento', 'subtotal_condescuento',
				'monto_afecto', 'monto_cortesia', 'subtotal_bruto', 'subtotal_neto', 'subtotal_neto_sin_descuento',
				'monto_descuento_global', 'subtotal_neto_con_descuento', 'monto_iva', 'total_bruto_con_descuento',
				'monto_exento_con_descuento', 'monto_afecto_con_descuento') as $c) {
				$cero[$c] = 0;
			}
			$texto = 'ANULADA ($' . (int)$v->total . '): ' . $motivo;
			$this->actualizar($db, $cols, 'venta', $cero + array(
				'vigente' => false,
				'mensaje_pedido' => mb_substr($texto, 0, 200),
				'observacion' => $texto,
				'motivo_anulacion' => mb_substr($motivo, 0, 200),
				'fecha_anulacion' => date('Y-m-d H:i:s'),
				'id_usuario_anulacion' => (int)$idUsuario,
			), 'id_venta', (int)$idVenta);

			if (isset($cols['anulacion_ticket'])) {
				$this->insertar($db, $cols, 'anulacion_ticket', array(
					'folio_ticket' => (int)$v->folio,
					'motivo' => mb_substr($motivo, 0, 200),
					'fecha_movimiento' => date('Y-m-d H:i:s'),
					'id_usuario' => (int)$idUsuario,
				), null);
			}
			$db->trans_commit();
		} catch (Exception $e) {
			$db->trans_rollback();
			throw $e;
		}
	}

	/**
	 * Cierre con el mismo criterio del POS:
	 * detalle_pagocaja = lo rendido por forma de pago (efectivo = contado - fondo),
	 * diferencia = rendido - (ventas + ingresos - egresos).
	 * $declarado = {id_fpago: monto} para formas no efectivo; $efectivoContado incluye el fondo.
	 * Lo declarado incluye propinas: se descuentan para que la diferencia sea solo de ventas
	 * (las propinas quedan en caja.propina, como en el POS).
	 */
	public function cerrarCaja($caja, $idUsuario, $efectivoContado, $declarado)
	{
		$db = $this->conexion();
		$cols = $this->columnas($db, array('caja', 'detalle_pagocaja'));
		$res = $this->resumenCaja($caja);
		$fondo = $res['fondo'];
		$efectivoRendido = (int)$efectivoContado - $fondo;

		$rendido = array();
		$efectivoAsignado = false;
		foreach ($res['por_forma_pago'] as $f) {
			if ($f['cortesia']) continue;
			if ($f['efectivo']) {
				$rendido[$f['id_fpago']] = ($efectivoAsignado ? 0 : $efectivoRendido) - $f['propina'];
				$efectivoAsignado = true;
			} else {
				$rendido[$f['id_fpago']] = (isset($declarado[$f['id_fpago']]) ? (int)$declarado[$f['id_fpago']] : $f['monto'] + $f['propina']) - $f['propina'];
			}
		}
		if (!$efectivoAsignado && $efectivoRendido !== 0) {
			// Sin ventas en efectivo pero hubo movimientos: se rinde en la forma de pago efectivo
			$config = $this->configuracion($db);
			foreach ($this->catalogoFpago($db, $config) as $f) {
				if ($f['efectivo']) { $rendido[$f['id_fpago']] = $efectivoRendido; break; }
			}
		}
		$suma = $res['ventas'] + $res['ingresos'] - $res['egresos'];
		$diferencia = array_sum($rendido) - $suma;
		$ahora = date('Y-m-d H:i:s');

		$db->trans_begin();
		try {
			$c = $this->q($db, 'SELECT cerrada FROM caja WHERE id_caja = ? FOR UPDATE', array((int)$caja->id_caja))->row();
			if ($c->cerrada === 't' || $c->cerrada === true) throw new Exception('La caja ya está cerrada', 409);
			$this->actualizar($db, $cols, 'caja', array(
				'cerrada' => true,
				'fecha_rendicion' => $ahora,
				'id_usuario_rendicion' => (int)$idUsuario,
				'monto_entregado' => $efectivoRendido,
				'efectivo_entregado' => $efectivoRendido,
				'ventas' => $res['ventas'],
				'ventas_local' => $res['ventas'],
				'total_sistema' => $res['ventas'],
				'diferencia' => $diferencia,
				'descuento' => $res['descuentos'],
				'cortesia' => $res['cortesias'],
				'efectivo_fpago' => $res['efectivo_ventas'],
				'efectivo_venta' => $res['efectivo_ventas'],
				'suma_detalle_caja' => $suma,
				'ingresos' => $res['ingresos'],
				'egresos' => $res['egresos'],
				'propina' => $res['propinas'],
			), 'id_caja', (int)$caja->id_caja);

			if (isset($cols['detalle_pagocaja'])) {
				$this->q($db, 'DELETE FROM detalle_pagocaja WHERE id_caja = ?', array((int)$caja->id_caja));
				foreach ($rendido as $idFpago => $monto) {
					$this->insertar($db, $cols, 'detalle_pagocaja', array(
						'id_caja' => (int)$caja->id_caja,
						'id_fpago' => $idFpago,
						'monto' => $monto,
						'numero_documento' => '0',
						'fecha_vencimiento' => date('Y-m-d') . ' 00:00:00',
						'fecha_movimiento' => $ahora,
					), null);
				}
			}
			$db->trans_commit();
		} catch (Exception $e) {
			$db->trans_rollback();
			throw $e;
		}
		return array('id_caja' => (int)$caja->id_caja, 'diferencia' => $diferencia, 'rendido' => array_sum($rendido), 'esperado' => $suma);
	}

	// ====================== Stock ======================

	/** Insumos a descontar por cada línea, con la fórmula de f_addconsolidado. */
	private function consumoInsumos($db, $idReceta, $cantidad)
	{
		if ($idReceta <= 0) return array();
		return $this->q($db, "SELECT i.id_insumo, i.nombre_insumo,
				(((i.umedida_inventario / NULLIF(i.umedida_receta, 0)) * d.cantidad) / NULLIF(r.rendimiento, 0)) * ? AS cantidad
			FROM detalle_receta d
			JOIN receta r ON d.id_receta = r.id_receta
			JOIN insumo i ON d.id_insumo = i.id_insumo
			WHERE r.id_receta = ? AND d.vigente = true", array($cantidad, $idReceta))->result();
	}

	/** Productos marcados "venta según stock": no se venden si algún insumo no alcanza. */
	private function validarStock($db, $lineas)
	{
		$necesario = array();
		foreach ($lineas as $l) {
			if (!$l['segun_stock']) continue;
			foreach ($l['recetas'] as $rc) {
				list($idReceta, $idBodega) = $rc;
				foreach ($this->consumoInsumos($db, $idReceta, $l['cantidad']) as $ins) {
					$k = $idBodega . ':' . (int)$ins->id_insumo;
					if (!isset($necesario[$k])) $necesario[$k] = array('nombre' => $ins->nombre_insumo, 'producto' => $l['nombre'], 'cantidad' => 0);
					$necesario[$k]['cantidad'] += (float)$ins->cantidad;
				}
			}
		}
		foreach ($necesario as $k => $n) {
			list($idBodega, $idInsumo) = explode(':', $k);
			$s = $this->q($db, 'SELECT COALESCE(sum(saldo), 0) AS saldo FROM stock_insumo WHERE id_bodega = ? AND id_insumo = ?',
				array((int)$idBodega, (int)$idInsumo))->row();
			if ((float)$s->saldo < $n['cantidad']) {
				throw new Exception('Stock insuficiente de ' . $n['nombre'] . ' para ' . $n['producto']
					. ' (disponible ' . round((float)$s->saldo, 2) . ')', 409);
			}
		}
	}

	private function tieneTriggerStock($db)
	{
		$r = $this->q($db, "SELECT 1 FROM pg_trigger t JOIN pg_proc p ON p.oid = t.tgfoid
			WHERE t.tgrelid = 'public.detalle_venta'::regclass AND p.proname = 'f_addconsolidado' AND NOT t.tgisinternal LIMIT 1")->row();
		return (bool)$r;
	}

	/** Para bases sin el trigger del POS: mismo efecto que f_addconsolidado. */
	private function descontarStock($db, $cols, $idVenta)
	{
		foreach ($this->lineasStock($db, $idVenta) as $l) {
			if (isset($cols['consolidado_ventas'])) {
				$this->insertar($db, $cols, 'consolidado_ventas', array(
					'id_venta' => $idVenta,
					'id_dventa' => (int)$l->id_detalle_venta,
					'cantidad' => (float)$l->cantidad,
					'id_producto' => (int)$l->id_producto,
					'id_bodega' => (int)$l->id_bodega,
				), null);
			}
			$this->moverStock($db, (int)$l->id_receta, (int)$l->id_bodega, (float)$l->cantidad, 1);
			$this->moverStock($db, (int)$l->id_receta_agregado, (int)$l->id_bodega, (float)$l->cantidad, 1);
		}
	}

	private function revertirStock($db, $idVenta)
	{
		foreach ($this->lineasStock($db, $idVenta) as $l) {
			$this->moverStock($db, (int)$l->id_receta, (int)$l->id_bodega, (float)$l->cantidad, -1);
			$this->moverStock($db, (int)$l->id_receta_agregado, (int)$l->id_bodega, (float)$l->cantidad, -1);
		}
	}

	private function lineasStock($db, $idVenta)
	{
		return $this->q($db, 'SELECT d.id_detalle_venta, d.id_producto, d.cantidad, COALESCE(p.id_receta, 0) AS id_receta,
				COALESCE(p.id_bodega, 0) AS id_bodega, COALESCE(pa.id_receta, 0) AS id_receta_agregado
			FROM detalle_venta d
			JOIN producto p ON p.id_producto = d.id_producto
			LEFT JOIN producto pa ON pa.id_producto = d.id_agregado AND d.id_agregado > 0
			WHERE d.id_venta = ?', array((int)$idVenta))->result();
	}

	/** $signo 1 = salida (venta), -1 = devuelve la salida (anulación). */
	private function moverStock($db, $idReceta, $idBodega, $cantidad, $signo)
	{
		foreach ($this->consumoInsumos($db, $idReceta, $cantidad) as $ins) {
			$c = $signo * (float)$ins->cantidad;
			$this->q($db, 'UPDATE stock_insumo SET cantidad_salida = COALESCE(cantidad_salida, 0) + ?,
					saldo = COALESCE(cantidad_entrada, 0) - (COALESCE(cantidad_salida, 0) + ?)
				WHERE id_bodega = ? AND id_insumo = ?', array($c, $c, $idBodega, (int)$ins->id_insumo));
		}
	}

	// ====================== Configuración ======================

	private function configuracion($db)
	{
		$cols = $this->columnas($db, array('configuracion'));
		$mapa = array(
			'id_lpreciopublico' => 'id_lprecio_publico',
			'por_iva' => 'por_iva',
			'id_fpago_defecto' => 'id_fpago_defecto',
			'id_docventa_defecto' => 'id_tdocumento_defecto',
			'id_cliente_publico' => 'id_cliente_publico',
			'por_maximodescuento' => 'por_maximo_descuento',
			'id_fpago_creditodirecto' => 'id_fpago_credito',
			'vueltofpago' => 'id_fpago_vuelto',
			'por_propina' => 'por_propina',
			'cant_agregados' => 'max_agregados',
		);
		// Opciones on/off de Configuraciones
		$flags = array(
			'propina_en_venta_directa' => 'propina',
			'calcular_valor_propina' => 'calcular_propina',
			'requerir_usuario_admin_descuento' => 'admin_descuento',
			'requerir_admin_descuento' => 'admin_descuento_clave',
			'requerir_usuario_admin_cortesia' => 'admin_cortesia',
			'requerir_usuario_admin_credito' => 'admin_credito',
			'admin_anulacion_eliminacion' => 'admin_anulacion',
		);
		$config = array_fill_keys(array_values($mapa), 0) + array_fill_keys(array_values($flags), false);
		$config['por_iva'] = 19;
		$existentes = array_keys(isset($cols['configuracion']) ? $cols['configuracion'] : array());
		$sel = array_values(array_intersect(array_keys($mapa), $existentes));
		$selFlags = array_values(array_intersect(array_keys($flags), $existentes));
		if ($sel || $selFlags) {
			$campos = $sel;
			foreach ($selFlags as $c) $campos[] = "($c IS TRUE) AS $c";
			$fila = $this->q($db, 'SELECT ' . implode(', ', $campos) . ' FROM configuracion LIMIT 1')->row_array();
			foreach ($sel as $c) {
				if (isset($fila[$c]) && $fila[$c] !== null) $config[$mapa[$c]] = $c === 'por_iva' ? (float)$fila[$c] : (int)$fila[$c];
			}
			foreach ($selFlags as $c) {
				$config[$flags[$c]] = isset($fila[$c]) && ($fila[$c] === 't' || $fila[$c] === true);
			}
		}
		$config['admin_descuento'] = $config['admin_descuento'] || $config['admin_descuento_clave'];
		unset($config['admin_descuento_clave']);
		if (!$config['id_cliente_publico']) {
			$r = $this->q($db, 'SELECT min(id_cliente) AS id FROM cliente')->row();
			$config['id_cliente_publico'] = $r ? (int)$r->id : 0;
		}
		if (!$config['id_lprecio_publico']) {
			$r = $this->q($db, 'SELECT min(id_lprecio) AS id FROM lprecio')->row();
			$config['id_lprecio_publico'] = $r ? (int)$r->id : 0;
		}
		return $config;
	}

	private function catalogoFpago($db, $config)
	{
		$res = array();
		foreach ($this->q($db, 'SELECT id_fpago, nombre_fpago, (escortesia IS TRUE) AS cortesia FROM fpago')->result() as $f) {
			$res[] = array(
				'id_fpago' => (int)$f->id_fpago,
				'nombre' => $f->nombre_fpago,
				'cortesia' => $f->cortesia === 't' || $f->cortesia === true,
				'efectivo' => $this->esEfectivo($config, $f->id_fpago, $f->nombre_fpago),
				'credito' => (int)$f->id_fpago === $config['id_fpago_credito'],
			);
		}
		return $res;
	}

	private function esEfectivo($config, $idFpago, $nombre)
	{
		if ($config['id_fpago_vuelto'] > 0) return (int)$idFpago === $config['id_fpago_vuelto'];
		return stripos((string)$nombre, 'EFECTIVO') !== false;
	}

	/** Documentos de venta (tdocumento.para_venta). */
	private function tiposDocumento($db)
	{
		$cols = $this->columnas($db, array('tdocumento'));
		$t = isset($cols['tdocumento']) ? $cols['tdocumento'] : array();
		$selReq = isset($t['requiere_cliente']) ? '(requiere_cliente IS TRUE)' : 'false';
		$selFac = isset($t['categoria_factura']) ? '(categoria_factura IS TRUE)' : 'false';
		$q = "SELECT id_tdocumento, nombre_tdocumento, COALESCE(cod_sii, 0) AS cod_sii, $selReq AS requiere, $selFac AS factura
			FROM tdocumento";
		if (isset($t['para_venta'])) $q .= ' WHERE para_venta IS TRUE';
		$res = array();
		foreach ($this->q($db, $q . ' ORDER BY id_tdocumento')->result() as $d) {
			$cod = (int)$d->cod_sii;
			$esFactura = $d->factura === 't' || $d->factura === true || in_array($cod, self::COD_SII_FACTURAS)
				|| stripos($d->nombre_tdocumento, 'FACTURA') !== false;
			$res[] = array(
				'id_tdocumento' => (int)$d->id_tdocumento,
				'nombre' => $d->nombre_tdocumento,
				'cod_sii' => $cod,
				'es_factura' => $esFactura,
				'requiere_cliente' => $esFactura || $d->requiere === 't' || $d->requiere === true,
				'electronico' => in_array($cod, self::COD_SII_ELECTRONICOS),
			);
		}
		return $res;
	}

	// ====================== Helpers ======================

	private function conexion()
	{
		$this->load->helper('tenant');
		$db = get_tenant_db($this);
		// Los errores se manejan con excepciones para poder hacer rollback y responder JSON
		$db->db_debug = FALSE;
		return $db;
	}

	private function q($db, $sql, $binds = array())
	{
		$r = $db->query($sql, $binds);
		if ($r === FALSE) {
			$e = $db->error();
			log_message('error', 'Mcaja: ' . $e['message'] . ' | ' . $sql);
			throw new Exception($e['message'] ? trim(preg_replace('/^ERROR:\s*/', '', $e['message'])) : 'Error de base de datos', 500);
		}
		return $r;
	}

	private function primerId($db, $tabla, $pk, $defecto)
	{
		$cols = $this->columnas($db, array($tabla));
		if (!isset($cols[$tabla])) return $defecto;
		$r = $this->q($db, "SELECT min($pk) AS id FROM $tabla")->row();
		return $r && $r->id ? (int)$r->id : $defecto;
	}

	/** INSERT solo con las columnas que existen en este cliente. */
	private function insertar($db, $cols, $tabla, $fila, $pk)
	{
		$fila = array_intersect_key($fila, $cols[$tabla]);
		$sql = 'INSERT INTO ' . $tabla . ' (' . implode(', ', array_keys($fila)) . ') VALUES ('
			. implode(', ', array_fill(0, count($fila), '?')) . ')' . ($pk ? ' RETURNING ' . $pk : '');
		$r = $this->q($db, $sql, array_values($fila));
		return $pk ? (int)$r->row()->$pk : 0;
	}

	private function actualizar($db, $cols, $tabla, $fila, $pk, $id)
	{
		$fila = array_intersect_key($fila, $cols[$tabla]);
		if (!$fila) return;
		$sets = array();
		foreach (array_keys($fila) as $c) $sets[] = $c . ' = ?';
		$valores = array_values($fila);
		$valores[] = $id;
		$this->q($db, 'UPDATE ' . $tabla . ' SET ' . implode(', ', $sets) . ' WHERE ' . $pk . ' = ?', $valores);
	}

	private function columnas($db, $tablas)
	{
		$faltan = array_values(array_diff($tablas, array_keys($this->cols)));
		if ($faltan) {
			foreach ($faltan as $t) $this->cols[$t] = null;
			$rows = $this->q($db, "SELECT table_name, column_name FROM information_schema.columns
				WHERE table_schema = 'public' AND table_name IN ?", array($faltan))->result();
			foreach ($rows as $c) $this->cols[$c->table_name][$c->column_name] = true;
		}
		$res = array();
		foreach ($tablas as $t) {
			if ($this->cols[$t] !== null) $res[$t] = $this->cols[$t];
		}
		return $res;
	}

	/** "12.345.678-9" -> [12345678, '9'] */
	private function separarRut($texto)
	{
		$limpio = strtoupper(preg_replace('/[^0-9kK]/', '', (string)$texto));
		if (strlen($limpio) < 2) return array(0, '');
		$cuerpo = substr($limpio, 0, -1);
		$dv = substr($limpio, -1);
		$suma = 0;
		$mult = 2;
		for ($i = strlen($cuerpo) - 1; $i >= 0; $i--) {
			$suma += (int)$cuerpo[$i] * $mult;
			$mult = $mult === 7 ? 2 : $mult + 1;
		}
		$esperado = 11 - ($suma % 11);
		$esperado = $esperado === 11 ? '0' : ($esperado === 10 ? 'K' : (string)$esperado);
		if ($esperado !== $dv) throw new Exception('El RUT ingresado no es válido', 400);
		return array((int)$cuerpo, $dv);
	}
}

/** Acción que necesita clave de administrador (la pantalla la pide y reintenta). */
class AutorizacionRequerida extends Exception
{
	public $motivo;

	public function __construct($motivo, $mensaje = null)
	{
		$this->motivo = $motivo;
		parent::__construct($mensaje ?: 'Se requiere autorización de un administrador para: ' . $motivo, 403);
	}
}
