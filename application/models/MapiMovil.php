<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Datos para la app móvil: lectura del catálogo y registro de cajas/ventas.
 *
 * El esquema no es igual en todos los clientes (ej: fpago.escortesia vs
 * fpago.es_cortesia), por eso cada columna opcional se revisa contra
 * information_schema antes de usarla.
 */
class MapiMovil extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}

	public function validarUsuario($correo, $clave, $idSoftware)
	{
		$db = $this->load->database('default', TRUE);
		$db->select('*');
		$db->from('usuario_appweb');
		$db->join('cliente', 'usuario_appweb.id_cliente = cliente.id_cliente');
		$db->join('software', 'usuario_appweb.id_software = software.id_software');
		$db->where('usuario_appweb.correo_acceso', $correo);
		$db->where('usuario_appweb.clave_acceso', $clave);
		$db->where('software.id_software', $idSoftware);
		$db->order_by('id_usuario_appweb');
		$db->limit(1);
		$fila = $db->get()->row();
		if (!$fila || empty($fila->nombre_conexion)) {
			return null;
		}

		return array(
			'id_usuario' => (int)$fila->id_usuario_cliente,
			'id_cliente' => (int)$fila->id_cliente,
			'nombre_usuario' => isset($fila->nombre_usuario) ? $fila->nombre_usuario : $correo,
			'nombre_cliente' => isset($fila->nombre_cliente) ? $fila->nombre_cliente : '',
			'nombre_fantasia' => isset($fila->nombre_fantasia) ? $fila->nombre_fantasia : '',
			'nombre_conexion' => $fila->nombre_conexion,
		);
	}

	public function catalogo($nombreConexion, $soloMoviles)
	{
		$db = $this->conectarTenant($nombreConexion);
		if (!$db) {
			return array('error' => 'No se pudo conectar a la base de datos del cliente');
		}

		$cols = $this->columnas($db, array('producto', 'familia', 'fpago', 'configuracion', 'usuario', 'cliente', 'agregados', 'mensaje_producto'));
		$tiene = function ($tabla, $columna) use ($cols) {
			return isset($cols[$tabla][$columna]);
		};

		// Familias
		$orden = $tiene('familia', 'orden_familia') ? 'COALESCE(orden_familia, 0)' : '0';
		$q = "SELECT id_familia, nombre_familia, $orden AS orden FROM familia";
		if ($tiene('familia', 'vigente')) $q .= " WHERE vigente IS NOT FALSE";
		$familias = array();
		foreach ($db->query($q . " ORDER BY 3, 2")->result() as $f) {
			$familias[] = array(
				'id_familia' => (int)$f->id_familia,
				'nombre' => $f->nombre_familia,
				'orden' => (int)$f->orden,
			);
		}

		$subfamilias = array();
		foreach ($db->query("SELECT id_subfamilia, nombre_subfamilia, COALESCE(id_familia, 0) AS id_familia FROM subfamilia ORDER BY 2")->result() as $s) {
			$subfamilias[] = array(
				'id_subfamilia' => (int)$s->id_subfamilia,
				'nombre' => $s->nombre_subfamilia,
				'id_familia' => (int)$s->id_familia,
			);
		}

		// Productos vendibles. La columna "visible en móviles" cambia de nombre según la versión del POS.
		$colMovil = null;
		foreach (array('ver_en_dispositivos_moviles', 'permitir_dispositivo_movil') as $c) {
			if ($tiene('producto', $c)) { $colMovil = $c; break; }
		}
		$selMovil = $colMovil ? "(p.$colMovil IS TRUE)::int" : '1';
		$selPesable = $tiene('producto', 'es_pesable') ? '(p.es_pesable IS TRUE)::int' : '0';
		$q = "SELECT p.id_producto, p.nombre_producto, p.cbarra, COALESCE(p.id_familia, 0) AS id_familia,
				COALESCE(p.id_subfamilia, 0) AS id_subfamilia, $selMovil AS movil, $selPesable AS pesable
			FROM producto p
			WHERE p.vigente IS TRUE AND p.venta_publico IS NOT FALSE";
		if ($soloMoviles && $colMovil) $q .= " AND p.$colMovil IS TRUE";
		$productos = array();
		$idsProductos = array();
		foreach ($db->query($q . " ORDER BY p.nombre_producto")->result() as $p) {
			$idsProductos[(int)$p->id_producto] = true;
			$productos[] = array(
				'id_producto' => (int)$p->id_producto,
				'nombre' => $p->nombre_producto,
				'cbarra' => $p->cbarra,
				'id_familia' => (int)$p->id_familia,
				'id_subfamilia' => (int)$p->id_subfamilia,
				'movil' => (int)$p->movil === 1,
				'pesable' => (int)$p->pesable === 1,
			);
		}

		$listas = array();
		foreach ($db->query("SELECT id_lprecio, nombre_lprecio FROM lprecio ORDER BY nombre_lprecio")->result() as $l) {
			$listas[] = array('id_lprecio' => (int)$l->id_lprecio, 'nombre' => $l->nombre_lprecio);
		}

		$precios = array();
		foreach ($db->query("SELECT id_producto, id_lprecio, total FROM detalle_lprecio WHERE total > 0 AND id_producto IS NOT NULL AND id_lprecio IS NOT NULL")->result() as $d) {
			if (!isset($idsProductos[(int)$d->id_producto])) continue;
			$precios[] = array(
				'id_producto' => (int)$d->id_producto,
				'id_lprecio' => (int)$d->id_lprecio,
				'total' => round((float)$d->total),
			);
		}

		// Formas de pago
		$colCortesia = $tiene('fpago', 'escortesia') ? 'escortesia' : ($tiene('fpago', 'es_cortesia') ? 'es_cortesia' : null);
		$selCortesia = $colCortesia ? "($colCortesia IS TRUE)::int" : '0';
		$q = "SELECT id_fpago, nombre_fpago, $selCortesia AS cortesia FROM fpago";
		if ($tiene('fpago', 'vigente')) $q .= " WHERE vigente IS NOT FALSE";
		$formasPago = array();
		foreach ($db->query($q . " ORDER BY id_fpago")->result() as $fp) {
			$formasPago[] = array(
				'id_fpago' => (int)$fp->id_fpago,
				'nombre' => $fp->nombre_fpago,
				'cortesia' => (int)$fp->cortesia === 1,
			);
		}

		// Configuración general (columnas opcionales)
		$config = array('id_lprecio_publico' => 0, 'por_iva' => 19, 'id_fpago_defecto' => 0);
		$mapa = array('id_lpreciopublico' => 'id_lprecio_publico', 'por_iva' => 'por_iva', 'id_fpago_defecto' => 'id_fpago_defecto');
		$selConfig = array();
		foreach ($mapa as $columna => $clave) {
			if ($tiene('configuracion', $columna)) $selConfig[] = $columna;
		}
		if ($selConfig) {
			$fila = $db->query("SELECT " . implode(', ', $selConfig) . " FROM configuracion LIMIT 1")->row_array();
			foreach ($selConfig as $columna) {
				if (isset($fila[$columna])) $config[$mapa[$columna]] = (float)$fila[$columna];
			}
		}

		// Cajeros. Nunca se envía la clave: solo un hash para validar offline en el teléfono.
		$usuarios = array();
		$q = "SELECT id_usuario, nombre_usuario, COALESCE(clave, '') AS clave"
			. ($tiene('usuario', 'esadmin') ? ", (esadmin IS TRUE)::int AS esadmin" : ", 0 AS esadmin")
			. " FROM usuario";
		if ($tiene('usuario', 'vigente')) $q .= " WHERE vigente IS NOT FALSE";
		foreach ($db->query($q . " ORDER BY nombre_usuario")->result() as $u) {
			$usuarios[] = array(
				'id_usuario' => (int)$u->id_usuario,
				'nombre' => $u->nombre_usuario,
				'esadmin' => (int)$u->esadmin === 1,
				'clave_hash' => $u->clave === '' ? null : $this->hashClave($nombreConexion, (int)$u->id_usuario, $u->clave),
			);
		}

		$clientes = array();
		if (isset($cols['cliente'])) {
			$selRut = $tiene('cliente', 'rut') && $tiene('cliente', 'dv') ? "COALESCE(rut::text || '-' || dv, '')" : "''";
			$selTel = $tiene('cliente', 'telefono') ? "COALESCE(telefono, '')" : "''";
			$q = "SELECT id_cliente, nombre_cliente, $selRut AS rut, $selTel AS telefono FROM cliente WHERE nombre_cliente IS NOT NULL";
			if ($tiene('cliente', 'vigente')) $q .= " AND vigente IS NOT FALSE";
			foreach ($db->query($q . " ORDER BY nombre_cliente")->result() as $c) {
				$clientes[] = array(
					'id_cliente' => (int)$c->id_cliente,
					'nombre' => $c->nombre_cliente,
					'rut' => $c->rut,
					'telefono' => $c->telefono,
				);
			}
		}

		// Agregados (extras con valor adicional) y mensajes por producto: solo existen en clientes restaurante
		$agregados = array();
		if (isset($cols['agregados'])) {
			$q = "SELECT a.id_agregado, a.id_producto_maestro, a.id_producto, p.nombre_producto,
					COALESCE(a.cantidad, 1) AS cantidad, COALESCE(a.valor, 0) AS valor
				FROM agregados a JOIN producto p ON p.id_producto = a.id_producto
				WHERE 1 = 1";
			if ($tiene('agregados', 'vigente')) $q .= " AND a.vigente IS NOT FALSE";
			if ($tiene('agregados', 'eliminado')) $q .= " AND a.eliminado IS NOT TRUE";
			foreach ($db->query($q . " ORDER BY a.id_producto_maestro, p.nombre_producto")->result() as $a) {
				if (!isset($idsProductos[(int)$a->id_producto_maestro])) continue;
				$agregados[] = array(
					'id_agregado' => (int)$a->id_agregado,
					'id_producto_maestro' => (int)$a->id_producto_maestro,
					'id_producto' => (int)$a->id_producto,
					'nombre' => $a->nombre_producto,
					'cantidad' => (float)$a->cantidad,
					'valor' => round((float)$a->valor),
				);
			}
		}

		$mensajes = array();
		if (isset($cols['mensaje_producto'])) {
			$q = "SELECT id_mensaje_producto, id_producto, mensaje FROM mensaje_producto WHERE mensaje IS NOT NULL";
			if ($tiene('mensaje_producto', 'vigente')) $q .= " AND vigente IS NOT FALSE";
			foreach ($db->query($q . " ORDER BY id_producto, id_mensaje_producto")->result() as $m) {
				if (!isset($idsProductos[(int)$m->id_producto])) continue;
				$mensajes[] = array(
					'id_mensaje' => (int)$m->id_mensaje_producto,
					'id_producto' => (int)$m->id_producto,
					'mensaje' => $m->mensaje,
				);
			}
		}

		$db->close();
		return array(
			'familias' => $familias,
			'subfamilias' => $subfamilias,
			'productos' => $productos,
			'listas_precio' => $listas,
			'precios' => $precios,
			'formas_pago' => $formasPago,
			'config' => $config,
			'usuarios' => $usuarios,
			'clientes' => $clientes,
			'agregados' => $agregados,
			'mensajes' => $mensajes,
		);
	}

	/**
	 * Hash con el que la app valida la clave del cajero sin conexión.
	 * Debe calcularse igual en la app: sha256("<conexion>:<id_usuario>:<clave>").
	 */
	// ====================== Subida de cajas y ventas ======================
	//
	// Se escribe en las mismas tablas del POS (caja, venta, detalle_venta, pago_venta,
	// detalle_caja, detalle_pagocaja) para que los reportes web las muestren igual.
	// La tabla movil_sync guarda qué uuid del teléfono ya se registró (no se duplica
	// aunque el teléfono reenvíe). Las columnas que no existen en un cliente se omiten.

	const COMPUTADOR = 'APP MOVIL';

	public function subir($nombreConexion, $body, $opciones)
	{
		$db = $this->conectarTenant($nombreConexion);
		if (!$db) {
			return array('error' => 'No se pudo conectar a la base de datos del cliente');
		}
		try {
			$zona = new DateTimeZone(!empty($opciones['zona']) ? $opciones['zona'] : 'America/Santiago');
		} catch (Exception $e) {
			return array('error' => 'api_movil_zona inválida');
		}

		$cols = $this->columnas($db, array(
			'venta', 'detalle_venta', 'pago_venta', 'caja', 'detalle_pagocaja', 'detalle_caja', 'consolidado_ventas',
			'ctacte_cliente', 'movil_sync', 'tdocumento', 'configuracion', 'usuario', 'cliente', 'turno', 'canal_venta', 'fpago',
		));
		foreach (array('venta', 'detalle_venta', 'pago_venta', 'caja', 'tdocumento') as $t) {
			if (!isset($cols[$t])) return array('error' => 'La base del cliente no tiene la tabla ' . $t);
		}
		if (!isset($cols['movil_sync'])) {
			$ok = $db->query("CREATE TABLE IF NOT EXISTS movil_sync (
				clave varchar(100) PRIMARY KEY, tipo varchar(20) NOT NULL, id_web integer NOT NULL DEFAULT 0,
				estado varchar(20) NOT NULL DEFAULT 'ok', fecha timestamp NOT NULL DEFAULT now())");
			if (!$ok) {
				$e = $db->error();
				return array('error' => 'No se pudo crear la tabla movil_sync: ' . $e['message']);
			}
		}

		$caja = $body['caja'];
		$movimientos = isset($body['movimientos']) && is_array($body['movimientos']) ? $body['movimientos'] : array();
		$ventas = isset($body['ventas']) && is_array($body['ventas']) ? $body['ventas'] : array();
		$porIva = isset($body['por_iva']) ? (float)$body['por_iva'] : 19;

		$db->trans_begin();
		try {
			$this->q($db, 'SET LOCAL TIME ZONE ' . $db->escape($zona->getName()));
			$ctx = $this->contexto($db, $cols, $opciones);
			$ctx['zona'] = $zona;
			$ctx['por_iva'] = $porIva;

			$idCaja = $this->subirCaja($db, $cols, $ctx, $caja);
			$this->subirMovimientos($db, $cols, $ctx, $idCaja, $caja['uuid'], $movimientos);

			$resultado = array();
			foreach ($ventas as $v) {
				$resultado[] = $this->subirVenta($db, $cols, $ctx, $idCaja, $v);
			}

			$cerrada = false;
			if (!empty($body['cierre']) && is_array($body['cierre'])) {
				$cerrada = $this->cerrarCajaWeb($db, $cols, $ctx, $idCaja, $caja, $body['cierre']);
			}

			$db->trans_commit();
			return array('id_caja' => $idCaja, 'ventas' => $resultado, 'caja_cerrada' => $cerrada);
		} catch (Exception $e) {
			$db->trans_rollback();
			log_message('error', 'ApiMovil subir (' . $nombreConexion . '): ' . $e->getMessage());
			return array('error' => $e->getMessage(), 'status' => $e->getCode() >= 400 ? $e->getCode() : 500);
		}
	}

	/** Datos de la empresa que se repiten en cada venta. */
	private function contexto($db, $cols, $opciones)
	{
		$ctx = array();

		$idTdoc = isset($opciones['id_tdocumento']) ? (int)$opciones['id_tdocumento'] : 0;
		if (!$idTdoc) {
			$r = $this->q($db, "SELECT id_tdocumento FROM tdocumento
				WHERE upper(nombre_tdocumento) LIKE '%COMPROBANTE INTERNO%' ORDER BY id_tdocumento LIMIT 1")->row();
			if (!$r) {
				throw new Exception('No existe el tipo de documento COMPROBANTE INTERNO (tabla tdocumento). '
					. 'Créalo o configura api_movil_id_tdocumento.', 409);
			}
			$idTdoc = (int)$r->id_tdocumento;
		}
		$ctx['id_tdocumento'] = $idTdoc;

		$pub = 0;
		if (isset($cols['configuracion']['id_cliente_publico'])) {
			$r = $this->q($db, 'SELECT id_cliente_publico FROM configuracion LIMIT 1')->row();
			$pub = $r ? (int)$r->id_cliente_publico : 0;
		}
		if (!$pub && isset($cols['cliente'])) {
			$r = $this->q($db, 'SELECT min(id_cliente) AS id FROM cliente')->row();
			$pub = $r ? (int)$r->id : 0;
		}
		$ctx['id_cliente_publico'] = $pub;

		$ctx['usuarios'] = array();
		if (isset($cols['usuario'])) {
			foreach ($this->q($db, 'SELECT id_usuario FROM usuario ORDER BY id_usuario')->result() as $u) {
				$ctx['usuarios'][(int)$u->id_usuario] = true;
			}
		}

		$ctx['fpagos'] = array();
		if (isset($cols['fpago'])) {
			foreach ($this->q($db, 'SELECT id_fpago FROM fpago')->result() as $f) {
				$ctx['fpagos'][(int)$f->id_fpago] = true;
			}
		}

		$ctx['id_turno'] = 0;
		if (isset($cols['turno'])) {
			$r = $this->q($db, 'SELECT min(id_turno) AS id FROM turno')->row();
			$ctx['id_turno'] = $r && $r->id ? (int)$r->id : 0;
		}
		$ctx['id_canal_venta'] = 1;
		if (isset($cols['canal_venta'])) {
			$r = $this->q($db, 'SELECT min(id_canal_venta) AS id FROM canal_venta')->row();
			if ($r && $r->id) $ctx['id_canal_venta'] = (int)$r->id;
		}
		return $ctx;
	}

	private function subirCaja($db, $cols, $ctx, $caja)
	{
		$clave = 'caja:' . $caja['uuid'];
		$m = $this->mapa($db, $clave);
		if ($m) return (int)$m->id_web;

		$apertura = $this->fechaLocal($ctx, isset($caja['fecha_apertura']) ? $caja['fecha_apertura'] : null);
		$fondo = (int)(isset($caja['fondo_inicial']) ? $caja['fondo_inicial'] : 0);
		$id = $this->insertar($db, $cols, 'caja', array(
			'fecha' => substr($apertura, 0, 10) . ' 00:00:00',
			'id_usuario' => $this->usuarioValido($ctx, isset($caja['id_usuario']) ? $caja['id_usuario'] : 0),
			'ingresos' => 0,
			'egresos' => 0,
			'cerrada' => false,
			'fondo_fijo' => $fondo,
			'fondo_fijo_anterior' => $fondo,
			'fecha_movimiento' => $apertura,
			'id_turno' => $ctx['id_turno'],
			'nombre_computador' => self::COMPUTADOR,
		), 'id_caja');
		$this->q($db, "INSERT INTO movil_sync (clave, tipo, id_web) VALUES (?, 'caja', ?)", array($clave, $id));
		return $id;
	}

	/** Ingresos y retiros de dinero (detalle_caja). */
	private function subirMovimientos($db, $cols, $ctx, $idCaja, $uuidCaja, $movimientos)
	{
		$ingresos = 0;
		$egresos = 0;
		foreach ($movimientos as $mv) {
			$monto = (int)(isset($mv['monto']) ? $mv['monto'] : 0);
			$esIngreso = isset($mv['tipo']) && $mv['tipo'] === 'ingreso';
			if ($esIngreso) $ingresos += $monto; else $egresos += $monto;

			$clave = 'mov:' . $uuidCaja . ':' . (int)$mv['id'];
			if (!isset($cols['detalle_caja']) || $this->mapa($db, $clave)) continue;
			$obs = trim(isset($mv['observacion']) ? (string)$mv['observacion'] : '');
			$id = $this->insertar($db, $cols, 'detalle_caja', array(
				'id_caja' => $idCaja,
				'ingreso' => $esIngreso ? $monto : 0,
				'egreso' => $esIngreso ? 0 : $monto,
				'observacion' => mb_substr($obs, 0, 100),
				'descripcion_corta' => mb_substr($obs, 0, 50),
				'id_ccosto' => 0,
				'fecha_movimiento' => $this->fechaLocal($ctx, isset($mv['fecha']) ? $mv['fecha'] : null),
				'vigente' => true,
			), 'id_detalle_caja');
			$this->q($db, "INSERT INTO movil_sync (clave, tipo, id_web) VALUES (?, 'movimiento', ?)", array($clave, $id));
		}
		$this->actualizar($db, $cols, 'caja', array('ingresos' => $ingresos, 'egresos' => $egresos), 'id_caja', $idCaja);
	}

	private function subirVenta($db, $cols, $ctx, $idCaja, $v)
	{
		$uuid = isset($v['uuid']) ? (string)$v['uuid'] : '';
		if ($uuid === '' || strlen($uuid) > 80) {
			return array('uuid' => $uuid, 'error' => 'Venta sin uuid');
		}
		$clave = 'venta:' . $uuid;
		$anulada = !empty($v['anulada']);
		$m = $this->mapa($db, $clave);

		if ($m) {
			$idVenta = (int)$m->id_web;
			if ($anulada && $m->estado === 'ok' && $idVenta) {
				$this->anularVentaWeb($db, $cols, $ctx, $idVenta, $v);
				$this->q($db, "UPDATE movil_sync SET estado = 'anulada' WHERE clave = ?", array($clave));
			}
			$r = $idVenta ? $this->q($db, 'SELECT folio FROM venta WHERE id_venta = ?', array($idVenta))->row() : null;
			return array('uuid' => $uuid, 'id_venta' => $idVenta, 'folio' => $r ? (int)$r->folio : 0);
		}

		if ($anulada) {
			// Se anuló antes de llegar a la web: no se registra la venta
			$this->q($db, "INSERT INTO movil_sync (clave, tipo, id_web, estado) VALUES (?, 'venta', 0, 'anulada')", array($clave));
			return array('uuid' => $uuid, 'id_venta' => 0, 'folio' => 0);
		}

		// Si una venta falla (ej: producto borrado en la web) no bloquea al resto del lote
		$this->q($db, 'SAVEPOINT venta_movil');
		try {
			$r = $this->insertarVenta($db, $cols, $ctx, $idCaja, $v);
			$this->q($db, "INSERT INTO movil_sync (clave, tipo, id_web) VALUES (?, 'venta', ?)", array($clave, $r['id_venta']));
			$this->q($db, 'RELEASE SAVEPOINT venta_movil');
			return array('uuid' => $uuid) + $r;
		} catch (Exception $e) {
			$this->q($db, 'ROLLBACK TO SAVEPOINT venta_movil');
			$msg = trim(preg_replace('/\s+/', ' ', preg_replace('/^ERROR:\s*/', '', $e->getMessage())));
			if (strpos($msg, 'producto') !== false && strpos($msg, 'foreign key') !== false) {
				$msg = 'Un producto de la venta ya no existe en la web (' . $msg . ')';
			}
			return array('uuid' => $uuid, 'error' => $msg);
		}
	}

	private function insertarVenta($db, $cols, $ctx, $idCaja, $v)
	{
		$fecha = $this->fechaLocal($ctx, isset($v['fecha']) ? $v['fecha'] : null);
		$dia = substr($fecha, 0, 10);
		$factor = 1 + $ctx['por_iva'] / 100;
		$subtotal = (int)$v['subtotal'];
		$descuento = (int)$v['descuento'];
		$total = (int)$v['total'];
		$neto = (int)$v['neto'];
		$iva = (int)$v['iva'];
		$cortesia = !empty($v['cortesia']);
		$porDescuento = $subtotal > 0 ? round($descuento * 100 / $subtotal, 2) : 0;
		$idUsuario = $this->usuarioValido($ctx, isset($v['id_cajero']) ? $v['id_cajero'] : 0);
		$idCliente = !empty($v['id_cliente']) ? (int)$v['id_cliente'] : $ctx['id_cliente_publico'];
		$detalle = isset($v['detalle']) && is_array($v['detalle']) ? $v['detalle'] : array();
		$pagos = isset($v['pagos']) && is_array($v['pagos']) ? $v['pagos'] : array();
		if (!$detalle) throw new Exception('Venta sin productos');

		$notas = array();
		foreach ($detalle as $d) {
			if (!empty($d['nota'])) $notas[] = (isset($d['nombre']) ? $d['nombre'] : '') . ': ' . $d['nota'];
		}
		if (!empty($v['nombre_cliente'])) array_unshift($notas, 'Cliente: ' . $v['nombre_cliente']);
		$observacion = implode(' | ', $notas);

		$r = $this->q($db, 'UPDATE tdocumento SET folio = coalesce(folio, 0) + 1 WHERE id_tdocumento = ? RETURNING folio',
			array($ctx['id_tdocumento']))->row();
		if (!$r) throw new Exception('No existe el tipo de documento ' . $ctx['id_tdocumento']);
		$folio = (int)$r->folio;

		$idVenta = $this->insertar($db, $cols, 'venta', array(
			'id_tdocumento' => $ctx['id_tdocumento'],
			'id_cliente' => $idCliente,
			'id_lprecio' => (int)$v['id_lprecio'],
			'id_usuario' => $idUsuario,
			'fecha' => $dia . ' 00:00:00', // como el POS: los reportes filtran por día
			'fecha_transaccion' => $fecha,
			'esconsignacion' => false,
			'subtotal' => $subtotal,
			'descuento' => $descuento,
			'neto' => $neto,
			'iva' => $iva,
			'ila' => 0,
			'total' => $total,
			'id_caja' => $idCaja,
			'folio' => $folio,
			'id_usuario_garzon' => $idUsuario,
			'id_mesa' => 0,
			'por_descuento' => $porDescuento,
			'monto_descuento' => $descuento,
			'subtotal_condescuento' => $total,
			'nombre_computador' => self::COMPUTADOR,
			'version_pdv' => self::COMPUTADOR,
			'mensaje_pedido' => $observacion === '' ? null : mb_substr($observacion, 0, 200),
			'observacion' => $observacion === '' ? null : $observacion,
			'es_cortesia' => $cortesia,
			'venta_directa' => true,
			'fecha_vencimiento' => $dia,
			'id_canal_venta' => $ctx['id_canal_venta'],
			'monto_exento' => 0,
			'monto_afecto' => $cortesia ? 0 : $total,
			'monto_cortesia' => $cortesia ? $total : 0,
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
		), 'id_venta');

		// El descuento global se reparte entre las líneas según su monto
		$repartido = 0;
		$n = count($detalle);
		foreach (array_values($detalle) as $i => $d) {
			$cantidad = (float)$d['cantidad'];
			$unitario = (int)$d['precio_unitario'];
			$bruto = (int)$d['total'];
			$descLinea = $i === $n - 1 ? $descuento - $repartido
				: ($subtotal > 0 ? (int)round($descuento * $bruto / $subtotal) : 0);
			$repartido += $descLinea;
			$conDesc = $bruto - $descLinea;
			$netoLinea = $bruto / $factor;
			$netoConDesc = $conDesc / $factor;
			$this->insertar($db, $cols, 'detalle_venta', array(
				'id_venta' => $idVenta,
				'id_producto' => (int)$d['id_producto'],
				'nombre_producto' => isset($d['nombre']) ? mb_substr($d['nombre'], 0, 200) : null,
				'descripcion_general' => !empty($d['nota']) ? $d['nota'] : null,
				'cantidad' => $cantidad,
				'bruto_un' => $unitario,
				'bruto_un_lista' => $unitario,
				'precio_unitario_bruto' => $unitario,
				'precio_unitario_neto' => $unitario / $factor,
				'neto_un' => $unitario / $factor,
				'neto_unitario' => $unitario / $factor,
				'subtotal' => $bruto,
				'descuento' => 0,
				'descuento_porcentual' => 0,
				'total_bruto' => $bruto,
				'neto' => $netoLinea,
				'iva' => (int)round($bruto - $netoLinea),
				'ila' => 0,
				'poriva' => $ctx['por_iva'],
				'porila' => 0,
				// Los agregados van como línea propia del producto agregado (descuenta su stock/receta)
				'id_agregado' => 0,
				'id_lprecio' => (int)$v['id_lprecio'],
				'nombre_computador' => self::COMPUTADOR,
				'fecha_transaccion' => $fecha,
				'fecha_pedido' => $fecha,
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

		foreach ($pagos as $p) {
			$idFpago = (int)$p['id_fpago'];
			if ($ctx['fpagos'] && !isset($ctx['fpagos'][$idFpago])) {
				throw new Exception('La forma de pago ' . $idFpago . ' no existe en la web');
			}
			$monto = (int)$p['monto'];
			$vuelto = (int)(isset($p['vuelto']) ? $p['vuelto'] : 0);
			$this->insertar($db, $cols, 'pago_venta', array(
				'id_cliente' => $idCliente,
				'id_venta' => $idVenta,
				'id_fpago' => $idFpago,
				'fecha_vencimiento' => $dia,
				'numero_documento' => '',
				'valor_compra' => $total,
				'paga_con' => $monto + $vuelto,
				'pagado' => 0,
				'propina' => 0,
				'subtotal' => $monto,
				'monto_pagado' => $monto,
				'vuelto' => $vuelto,
				'fecha_movimiento' => $fecha,
				'fecha_pago' => $dia,
			), null);
		}

		return array('id_venta' => $idVenta, 'folio' => $folio);
	}

	/**
	 * Venta anulada en el teléfono después de subirla: se borran sus líneas y pagos
	 * (los reportes no filtran por vigente) y la cabecera queda en 0 con el motivo.
	 * Nota: no revierte descuentos de stock que hayan hecho los triggers del POS.
	 */
	private function anularVentaWeb($db, $cols, $ctx, $idVenta, $v)
	{
		$r = $this->q($db, 'SELECT total FROM venta WHERE id_venta = ?', array($idVenta))->row();
		if (!$r) return;
		$motivo = trim(isset($v['motivo_anulacion']) ? (string)$v['motivo_anulacion'] : '');

		$this->q($db, 'DELETE FROM pago_venta WHERE id_venta = ?', array($idVenta));
		if (isset($cols['ctacte_cliente']['id_venta'])) {
			$this->q($db, 'DELETE FROM ctacte_cliente WHERE id_venta = ?', array($idVenta));
		}
		if (isset($cols['consolidado_ventas']['id_venta'])) {
			$this->q($db, 'DELETE FROM consolidado_ventas WHERE id_venta = ?', array($idVenta));
		}
		$this->q($db, 'DELETE FROM detalle_venta WHERE id_venta = ?', array($idVenta));

		$cero = array();
		foreach (array('subtotal', 'descuento', 'neto', 'iva', 'total', 'monto_descuento', 'subtotal_condescuento',
			'monto_afecto', 'monto_cortesia', 'subtotal_bruto', 'subtotal_neto', 'subtotal_neto_sin_descuento',
			'monto_descuento_global', 'subtotal_neto_con_descuento', 'monto_iva', 'total_bruto_con_descuento') as $c) {
			$cero[$c] = 0;
		}
		$this->actualizar($db, $cols, 'venta', $cero + array(
			'vigente' => false,
			'motivo_anulacion' => mb_substr($motivo, 0, 200),
			'fecha_anulacion' => $this->fechaLocal($ctx, isset($v['fecha_anulacion']) ? $v['fecha_anulacion'] : null),
			'id_usuario_anulacion' => $this->usuarioValido($ctx, isset($v['id_cajero']) ? $v['id_cajero'] : 0),
			'observacion' => 'ANULADA EN APP MOVIL. Total original $' . (int)$r->total . ($motivo !== '' ? '. Motivo: ' . $motivo : ''),
		), 'id_venta', $idVenta);
	}

	/**
	 * Cierre de caja con el mismo criterio del POS:
	 * detalle_pagocaja = lo rendido por forma de pago (efectivo = contado - fondo),
	 * diferencia = rendido - (ventas + ingresos - egresos).
	 */
	private function cerrarCajaWeb($db, $cols, $ctx, $idCaja, $caja, $c)
	{
		$clave = 'caja:' . $caja['uuid'];
		$m = $this->mapa($db, $clave);
		if ($m && $m->estado === 'cerrada') return true;

		$fondo = (int)(isset($caja['fondo_inicial']) ? $caja['fondo_inicial'] : 0);
		$ventas = (int)$c['ventas'];
		$ingresos = (int)$c['ingresos'];
		$egresos = (int)$c['egresos'];
		$efectivoRendido = (int)$c['efectivo_contado'] - $fondo;
		$suma = $ventas + $ingresos - $egresos;
		$cierre = $this->fechaLocal($ctx, isset($c['fecha_cierre']) ? $c['fecha_cierre'] : null);

		$rendido = array();
		$efectivoAsignado = false;
		foreach ((isset($c['por_forma_pago']) && is_array($c['por_forma_pago']) ? $c['por_forma_pago'] : array()) as $f) {
			if (!empty($f['cortesia'])) continue;
			$id = (int)$f['id_fpago'];
			if ($ctx['fpagos'] && !isset($ctx['fpagos'][$id])) continue;
			if (!empty($f['efectivo'])) {
				$monto = $efectivoAsignado ? 0 : $efectivoRendido;
				$efectivoAsignado = true;
			} else {
				$monto = (int)$f['monto'];
			}
			$rendido[$id] = (isset($rendido[$id]) ? $rendido[$id] : 0) + $monto;
		}
		$diferencia = array_sum($rendido) - $suma;

		$this->actualizar($db, $cols, 'caja', array(
			'cerrada' => true,
			'fecha_rendicion' => $cierre,
			'id_usuario_rendicion' => $this->usuarioValido($ctx, isset($caja['id_usuario']) ? $caja['id_usuario'] : 0),
			'monto_entregado' => $efectivoRendido,
			'efectivo_entregado' => $efectivoRendido,
			'ventas' => $ventas,
			'ventas_local' => $ventas,
			'total_sistema' => $ventas,
			'diferencia' => $diferencia,
			'descuento' => (int)$c['descuentos'],
			'descuentos' => (int)$c['descuentos'],
			'cortesia' => (int)$c['cortesias'],
			'efectivo_fpago' => (int)$c['efectivo_ventas'],
			'efectivo_venta' => (int)$c['efectivo_ventas'],
			'suma_detalle_caja' => $suma,
			'ingresos' => $ingresos,
			'egresos' => $egresos,
			'propina' => 0,
		), 'id_caja', $idCaja);

		if (isset($cols['detalle_pagocaja'])) {
			$this->q($db, 'DELETE FROM detalle_pagocaja WHERE id_caja = ?', array($idCaja));
			foreach ($rendido as $idFpago => $monto) {
				$this->insertar($db, $cols, 'detalle_pagocaja', array(
					'id_caja' => $idCaja,
					'id_fpago' => $idFpago,
					'monto' => $monto,
					'numero_documento' => '0',
					'fecha_vencimiento' => substr($cierre, 0, 10) . ' 00:00:00',
					'fecha_movimiento' => $cierre,
				), null);
			}
		}
		$this->q($db, "UPDATE movil_sync SET estado = 'cerrada' WHERE clave = ?", array($clave));
		return true;
	}

	// ---------- helpers de escritura ----------

	private function q($db, $sql, $binds = array())
	{
		$r = $db->query($sql, $binds);
		if ($r === FALSE) {
			$e = $db->error();
			throw new Exception($e['message'] ? $e['message'] : 'Error de base de datos');
		}
		return $r;
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

	private function mapa($db, $clave)
	{
		return $this->q($db, 'SELECT id_web, estado FROM movil_sync WHERE clave = ? FOR UPDATE', array($clave))->row();
	}

	/** Usuario POS que existe en la web; si no (ej: se vendió con el usuario web), el primero. */
	private function usuarioValido($ctx, $id)
	{
		$id = (int)$id;
		if (!$ctx['usuarios'] || isset($ctx['usuarios'][$id])) return $id;
		reset($ctx['usuarios']);
		return key($ctx['usuarios']);
	}

	/** ISO 8601 del teléfono (UTC) -> hora local del negocio, como guarda el POS. */
	private function fechaLocal($ctx, $iso)
	{
		try {
			$d = new DateTime($iso ? (string)$iso : 'now');
		} catch (Exception $e) {
			$d = new DateTime('now');
		}
		$d->setTimezone($ctx['zona']);
		return $d->format('Y-m-d H:i:s');
	}

	private function hashClave($nombreConexion, $idUsuario, $clave)
	{
		return hash('sha256', $nombreConexion . ':' . $idUsuario . ':' . $clave);
	}

	private function conectarTenant($nombreConexion)
	{
		// Mismas credenciales que usa tenant_helper.php, pero sin depender de la sesión
		include(APPPATH . 'config/database.php');
		if (!isset($db[$nombreConexion])) {
			log_message('error', 'ApiMovil: conexión no registrada en database.php: ' . $nombreConexion);
			return null;
		}
		$config = $db[$nombreConexion];
		$config['db_debug'] = FALSE; // que un error de BD no devuelva HTML a la app
		$conn = $this->load->database($config, TRUE);
		return ($conn && $conn->conn_id) ? $conn : null;
	}

	private function columnas($db, $tablas)
	{
		$db->select('table_name, column_name');
		$db->from('information_schema.columns');
		$db->where('table_schema', 'public');
		$db->where_in('table_name', $tablas);
		$res = array();
		foreach ($db->get()->result() as $c) {
			$res[$c->table_name][$c->column_name] = true;
		}
		return $res;
	}
}
