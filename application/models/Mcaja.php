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
	/** Propina máxima, en % del total de la venta. */
	const PROPINA_MAX_PCT = 15;
	/** Clave de los bloqueos (pg_advisory_lock) de emisión de documentos electrónicos, junto al id de la venta. */
	const LOCK_DTE = 77001;
	// 14-11-2026: abrir caja (candado por usuario, ver abrirCaja)
	const LOCK_ABRIR_CAJA = 77003;
	// Electrónicos que emite la caja web (boleta, boleta exenta, factura, factura exenta) y la NC
	const COD_SII_EMITIBLES = array(33, 34, 39, 41);
	const COD_SII_NC = 61;
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

	/** Como el POS: la caja nueva parte con el sencillo que dejó el último cierre. */
	public function fondoSugerido($idUsuario)
	{
		$db = $this->conexion();
		$r = $this->q($db, "SELECT fondo_fijo FROM caja
			WHERE cerrada IS TRUE AND nombre_computador = ? AND id_usuario = ?
			ORDER BY id_caja DESC LIMIT 1", array(self::COMPUTADOR, (int)$idUsuario))->row();
		return $r ? (int)$r->fondo_fijo : null;
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
		// Productos fraccionables (permite_porcion, p. ej. pan por kilo): la caja acepta cantidad con decimales.
		// pedir_cantidad_en_venta: al agregarlo, el cursor va directo a la cantidad.
		$tieneUm = $this->columnas($db, array('umedida'));
		$colFraccion = isset($tiene['producto']['permite_porcion']) ? '(p.permite_porcion IS TRUE)' : 'false';
		$colPedir = isset($tiene['producto']['pedir_cantidad_en_venta']) ? '(p.pedir_cantidad_en_venta IS TRUE)' : 'false';
		// Favoritos marcados en la Carta (migración 20261110190000); sin la columna no hay favoritos
		$colFavorito = isset($tiene['producto']['favorito']) ? '(p.favorito IS TRUE)' : 'false';
		$usaUm = isset($tiene['producto']['id_umedida']) && isset($tieneUm['umedida']['nombre_umedida']);
		$q = "SELECT p.id_producto, p.nombre_producto, COALESCE(p.cbarra, '') AS cbarra,
				COALESCE(p.id_familia, 0) AS id_familia,
				COALESCE(NULLIF(dl.total, 0), p.precio_bruto, 0) AS precio, " . $this->sqlExento($db, 'p') . " AS exento, " . $this->sqlIla($db, 'p') . " AS ila,
				$colFraccion AS fraccion, $colPedir AS pedir_cantidad, $colFavorito AS favorito, " . ($usaUm ? "COALESCE(um.nombre_umedida, '')" : "''") . " AS umedida
			FROM producto p
			LEFT JOIN detalle_lprecio dl ON dl.id_producto = p.id_producto AND dl.id_lprecio = ?
			" . ($usaUm ? "LEFT JOIN umedida um ON um.id_umedida = p.id_umedida" : "") . "
			WHERE p.vigente IS TRUE AND p.venta_publico IS NOT FALSE";
		if (isset($tiene['producto']['permitir_venta_sin_precio'])) {
			$q .= " AND (COALESCE(NULLIF(dl.total, 0), p.precio_bruto, 0) > 0 OR p.permitir_venta_sin_precio IS TRUE)";
		} else {
			$q .= " AND COALESCE(NULLIF(dl.total, 0), p.precio_bruto, 0) > 0";
		}
		// Imágenes de la Carta (controlador ImagenProducto): uploads/<rut empresa>/productos/<id>.webp
		if (!class_exists('ImagenProducto', false)) require_once APPPATH . 'controllers/ImagenProducto.php';
		$imagenes = ImagenProducto::imagenesDe(ImagenProducto::carpetaSesion($this->session));
		$productos = array();
		foreach ($this->q($db, $q . " ORDER BY p.nombre_producto", array($idLprecio))->result() as $p) {
			$productos[] = array(
				'id_producto' => (int)$p->id_producto,
				'nombre' => $p->nombre_producto,
				'cbarra' => trim($p->cbarra),
				'id_familia' => (int)$p->id_familia,
				'precio' => (int)round((float)$p->precio),
				'exento' => $p->exento === 't' || $p->exento === true,
				'ila' => ($p->exento === 't' || $p->exento === true) ? 0 : (float)$p->ila,   // v1.20.0
				'fraccion' => $p->fraccion === 't' || $p->fraccion === true,
				'pedir_cantidad' => $p->pedir_cantidad === 't' || $p->pedir_cantidad === true,
				'favorito' => $p->favorito === 't' || $p->favorito === true,
				'umedida' => $this->abreviaturaUmedida($p->umedida),
				'imagen' => isset($imagenes[(int)$p->id_producto]) ? $imagenes[(int)$p->id_producto] : null,
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
				'credito' => $this->esCredito($config, $f->id_fpago, $f->nombre_fpago),
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

		// v1.24.0: menús (producto-menú => categorías con sus productos), solo de productos que están en el catálogo
		$menusCaja = array();
		$enCatalogo = array();
		foreach ($productos as $pc) $enCatalogo[$pc['id_producto']] = true;
		foreach ($this->menus($db) as $idP => $mn) {
			if (!isset($enCatalogo[$idP])) continue;
			$cats = array();
			foreach ($mn['categorias'] as $c) {
				$ops = array();
				foreach ($c['opciones'] as $o) $ops[] = array('id' => $o['id'], 'id_producto' => $o['id_producto'], 'nombre' => $o['nombre'], 'adicional' => $o['adicional']);
				$cats[] = array('id' => $c['id'], 'nombre' => $c['nombre'], 'min' => $c['min'], 'max' => $c['max'], 'estricto' => $c['estricto'], 'opciones' => $ops);
			}
			$menusCaja[] = array('id_producto' => $idP, 'id_menu' => $mn['id_menu'], 'nombre' => $mn['nombre'], 'categorias' => $cats);
		}

		// v1.25.0: promos de sushi armables (Productos > Promos Sushi), solo de productos que están en el catálogo
		$sushisCaja = array();
		foreach ($this->sushis($db) as $idP => $sp) {
			if (!isset($enCatalogo[$idP])) continue;
			$ops = array();
			foreach ($sp['opciones'] as $tipo => $lista) {
				$ops[$tipo] = array();
				foreach ($lista as $o) $ops[$tipo][] = array('id' => $o['id'], 'nombre' => $o['nombre'], 'valor' => $o['valor']);
			}
			$sushisCaja[] = array('id_producto' => $idP, 'piezas' => $sp['piezas'], 'paso' => $sp['paso'], 'max_rellenos' => $sp['max_rellenos'],
				'modo' => $sp['modo'], 'piezas_rol' => $sp['piezas_rol'],   // v1.26.0
				'bases' => $ops['base'], 'rellenos' => $ops['relleno'], 'envoltorios' => $ops['envoltorio'], 'extras' => $ops['extra']);
		}

		$config['es_admin'] = $this->esAdmin($db, $idUsuario, $idRol);
		$config['tiene_clave_admin'] = $this->claveAdminGeneral($db) !== '';
		$config['propina_max_pct'] = self::PROPINA_MAX_PCT;
		// Nunca se envía la clave; solo quién puede autorizar
		$admins = array();
		foreach ($this->q($db, 'SELECT u.id_usuario, u.nombre_usuario FROM usuario u LEFT JOIN rol r ON r.id_rol = u.id_rol
			WHERE u.vigente IS NOT FALSE AND ' . self::SQL_ES_ADMIN . ' ORDER BY u.nombre_usuario')->result() as $u) {
			$admins[] = array('id_usuario' => (int)$u->id_usuario, 'nombre' => $u->nombre_usuario);
		}

		$comunas = array();
		if ($this->columnas($db, array('comuna'))) {
			$comunas = $this->q($db, 'SELECT id_comuna, nombre_comuna FROM comuna ORDER BY nombre_comuna')->result_array();
		}

		return array(
			'id_lprecio' => $idLprecio,
			'comunas' => $comunas,
			'familias' => $familias,
			'productos' => $productos,
			'formas_pago' => $formasPago,
			'documentos' => $documentos,
			'listas_precio' => $listas,
			'agregados' => $agregados,
			'menus' => $menusCaja,   // v1.24.0
			'sushis' => $sushisCaja,   // v1.25.0
			'mensajes' => $mensajes,
			'administradores' => $admins,
			'config' => $config,
			// Emisión con la cuenta de pruebas de Cloud Apps (config/caja_local.php): la Caja lo avisa en pantalla
			'dte_prueba' => (bool)$this->dtePrueba($db),
			'modo_caja' => $this->modoCaja($db),
			// 18-11-2026: pestaña Delivery (null = apagada en Configuraciones o sin la migración 20261118100000)
			'delivery' => $this->datosDelivery($db, $config),
		);
	}

	/**
	 * v1.24.0: menús de la Carta (Productos > Menú) que se venden en la Caja: [id del producto-menú => menú].
	 * Un producto es menú si tiene id_menu (menú vigente) con al menos una categoría vigente con productos vigentes.
	 * Categoría: min / max = cuántos productos se eligen (max 0 = sin límite); estricto = hay que respetar el mínimo.
	 * Opción: el producto, su costo adicional de venta (se suma al precio del menú) y si sale en la comanda.
	 */
	private $menusCache = null;
	private function menus($db)
	{
		if ($this->menusCache !== null) return $this->menusCache;
		$this->menusCache = array();
		$t = $this->columnas($db, array('menu', 'categoria_menu', 'detalle_categoria_menu', 'producto'));
		if (!isset($t['menu'], $t['categoria_menu'], $t['detalle_categoria_menu']) || !isset($t['producto']['id_menu'])) return array();
		$cat = $t['categoria_menu'];
		$det = $t['detalle_categoria_menu'];
		$rows = $this->q($db, "SELECT p.id_producto AS id_menu_producto, m.id_menu, m.nombre_menu,
				c.id_categoria_menu, c.nombre_categoria_menu,
				" . (isset($cat['cantidad_minima']) ? 'COALESCE(c.cantidad_minima, 0)' : '0') . " AS minimo,
				" . (isset($cat['cantidad_maxima']) ? 'COALESCE(c.cantidad_maxima, 0)' : '0') . " AS maximo,
				" . (isset($cat['estricto']) ? '(c.estricto IS TRUE)' : 'false') . " AS estricto,
				d.id_detalle_categoria_menu, d.id_producto, pd.nombre_producto,
				" . (isset($det['costo_adicional_venta']) ? 'COALESCE(d.costo_adicional_venta, 0)' : '0') . " AS adicional,
				" . (isset($det['imprimir']) ? '(d.imprimir IS NOT FALSE)' : 'true') . " AS imprimir,
				COALESCE(pd.id_receta, 0) AS id_receta, COALESCE(pd.id_bodega, 0) AS id_bodega
			FROM producto p
			JOIN menu m ON m.id_menu = p.id_menu AND m.vigente IS NOT FALSE
			JOIN categoria_menu c ON c.id_menu = m.id_menu" . (isset($cat['vigente']) ? ' AND c.vigente IS NOT FALSE' : '') . "
			JOIN detalle_categoria_menu d ON d.id_categoria_menu = c.id_categoria_menu" . (isset($det['vigente']) ? ' AND d.vigente IS NOT FALSE' : '') . "
			JOIN producto pd ON pd.id_producto = d.id_producto AND pd.vigente IS TRUE
			WHERE p.vigente IS TRUE AND COALESCE(p.id_menu, 0) > 0
			ORDER BY p.id_producto, " . (isset($cat['orden']) ? 'COALESCE(c.orden, 0), ' : '') . "c.id_categoria_menu, pd.nombre_producto")->result();   // categorías en el orden en que se crearon
		$si = function ($v) { return $v === true || $v === 't'; };
		foreach ($rows as $r) {
			$idP = (int)$r->id_menu_producto;
			if (!isset($this->menusCache[$idP])) {
				$this->menusCache[$idP] = array('id_menu' => (int)$r->id_menu, 'nombre' => (string)$r->nombre_menu, 'categorias' => array());
			}
			$idC = (int)$r->id_categoria_menu;
			$cats =& $this->menusCache[$idP]['categorias'];
			if (!isset($cats[$idC])) {
				$cats[$idC] = array('id' => $idC, 'nombre' => (string)$r->nombre_categoria_menu, 'min' => max(0, (int)$r->minimo),
					'max' => max(0, (int)$r->maximo), 'estricto' => $si($r->estricto), 'opciones' => array());
			}
			$cats[$idC]['opciones'][(int)$r->id_detalle_categoria_menu] = array('id' => (int)$r->id_detalle_categoria_menu,
				'id_producto' => (int)$r->id_producto, 'nombre' => (string)$r->nombre_producto, 'adicional' => max(0, (int)$r->adicional),
				'imprimir' => $si($r->imprimir), 'id_receta' => (int)$r->id_receta, 'id_bodega' => (int)$r->id_bodega);
			unset($cats);
		}
		return $this->menusCache;
	}

	/**
	 * v1.24.0: valida lo elegido de un menú ([{id: id_detalle_categoria_menu, cantidad}], cantidad por cada menú) y
	 * devuelve [[opción, cantidad], ...] en el orden de las categorías.
	 */
	private function eleccionMenu($menu, $eleccion, $nombreMenu)
	{
		$pedidas = array();
		foreach ((is_array($eleccion) ? $eleccion : array()) as $e) {
			$id = (int)(is_array($e) && isset($e['id']) ? $e['id'] : 0);
			$q = (int)(is_array($e) && isset($e['cantidad']) ? $e['cantidad'] : 1);
			if ($id <= 0 || $q <= 0) continue;
			if ($q > 99) throw new Exception('Cantidad demasiado alta en ' . $nombreMenu, 400);
			$pedidas[$id] = (isset($pedidas[$id]) ? $pedidas[$id] : 0) + $q;
		}
		$res = array();
		foreach ($menu['categorias'] as $c) {
			$n = 0;
			foreach ($c['opciones'] as $o) {
				if (!isset($pedidas[$o['id']])) continue;
				$n += $pedidas[$o['id']];
				$res[] = array($o, $pedidas[$o['id']]);
				unset($pedidas[$o['id']]);
			}
			if ($c['max'] > 0 && $n > $c['max']) {
				throw new Exception($nombreMenu . ': en ' . $c['nombre'] . ' se pueden elegir hasta ' . $c['max'], 400);
			}
			if ($c['estricto'] && $n < $c['min']) {
				throw new Exception($nombreMenu . ': en ' . $c['nombre'] . ' hay que elegir ' . ($c['min'] === 1 ? '1 producto' : 'al menos ' . $c['min']), 400);
			}
		}
		if ($pedidas) throw new Exception('Un producto elegido de ' . $nombreMenu . ' ya no está disponible en el menú', 400);
		return $res;
	}

	/**
	 * v1.25.0: promos de sushi (migración 20261120180000): [id del producto-promo => promo]. Sin las tablas, ninguna.
	 * Promo: piezas, paso (de a cuántas piezas va cada grupo), max_rellenos (0 = sin límite) y sus opciones por tipo
	 * (base, relleno, envoltorio, extra), cada una con su producto vigente y su valor adicional.
	 */
	private $sushisCache = null;
	private function sushis($db)
	{
		if ($this->sushisCache !== null) return $this->sushisCache;
		$this->sushisCache = array();
		$t = $this->columnas($db, array('promo_sushi_web', 'promo_sushi_opcion_web'));
		if (!isset($t['promo_sushi_web'], $t['promo_sushi_opcion_web'])) return array();
		// v1.26.0: modo pieza / rol (migración 20261121110000); sin las columnas, todas por pieza
		$conRol = isset($t['promo_sushi_web']['modo'], $t['promo_sushi_web']['piezas_rol']);
		$selRol = $conRol ? 's.modo, s.piezas_rol' : "'pieza' AS modo, 10 AS piezas_rol";
		foreach ($this->q($db, "SELECT s.id_producto, s.piezas, s.paso, s.max_rellenos, $selRol FROM promo_sushi_web s
			JOIN producto p ON p.id_producto = s.id_producto AND p.vigente IS TRUE WHERE s.vigente IS TRUE")->result() as $r) {
			$rol = $r->modo === 'rol';
			$pr = max(1, (int)$r->piezas_rol);
			$this->sushisCache[(int)$r->id_producto] = array('piezas' => (int)$r->piezas, 'paso' => $rol ? $pr : max(1, (int)$r->paso),
				'max_rellenos' => max(0, (int)$r->max_rellenos),
				// v1.26.0: por roll, cada grupo va de a un roll (paso = piezas del roll) y lo elegido se cobra por roll
				'modo' => $rol ? 'rol' : 'pieza', 'piezas_rol' => $pr,
				'opciones' => array('base' => array(), 'relleno' => array(), 'envoltorio' => array(), 'extra' => array()));
		}
		if (!$this->sushisCache) return array();
		foreach ($this->q($db, "SELECT o.id_opcion, o.id_producto_promo, o.tipo, o.id_producto, o.valor, p.nombre_producto,
				COALESCE(p.id_receta, 0) AS id_receta, COALESCE(p.id_bodega, 0) AS id_bodega
			FROM promo_sushi_opcion_web o JOIN producto p ON p.id_producto = o.id_producto AND p.vigente IS TRUE
			ORDER BY o.id_producto_promo, o.tipo, o.orden, p.nombre_producto")->result() as $r) {
			$idP = (int)$r->id_producto_promo;
			if (!isset($this->sushisCache[$idP]['opciones'][$r->tipo])) continue;
			$this->sushisCache[$idP]['opciones'][$r->tipo][(int)$r->id_opcion] = array('id' => (int)$r->id_opcion,
				'id_producto' => (int)$r->id_producto, 'nombre' => (string)$r->nombre_producto, 'valor' => max(0, (int)$r->valor),
				'id_receta' => (int)$r->id_receta, 'id_bodega' => (int)$r->id_bodega);
		}
		return $this->sushisCache;
	}

	/**
	 * v1.25.0: valida lo armado de una promo de sushi (por cada promo) y lo devuelve con las opciones del servidor.
	 * $sel = {grupos: [{piezas, base, envoltorio, rellenos: [id_opcion...]}], extras: [{id, cantidad}]}.
	 * Reglas: cada grupo con piezas >= 1 y múltiplo del paso; la suma = las piezas de la promo; base y envoltorio
	 * obligatorios si la promo los ofrece; al menos un relleno si los ofrece, sin repetir y hasta max_rellenos.
	 */
	private function eleccionSushi($promo, $sel, $nombre)
	{
		$sel = is_array($sel) ? $sel : array();
		$grupos = isset($sel['grupos']) && is_array($sel['grupos']) ? array_values($sel['grupos']) : array();
		$rol = isset($promo['modo']) && $promo['modo'] === 'rol';   // v1.26.0
		if (!$grupos) throw new Exception($nombre . ': arma ' . ($rol ? 'los rolls' : 'las piezas') . ' antes de agregarla', 400);
		if (count($grupos) > $promo['piezas']) throw new Exception($nombre . ': demasiados grupos', 400);
		$op = $promo['opciones'];
		$opcion = function ($tipo, $id) use ($op, $nombre) {
			$id = (int)$id;
			if (!isset($op[$tipo][$id])) throw new Exception('Una opción de ' . $nombre . ' ya no está disponible: vuelve a armarla', 400);
			return $op[$tipo][$id];
		};
		$res = array('grupos' => array(), 'extras' => array());
		$total = 0;
		foreach ($grupos as $n => $g) {
			$g = is_array($g) ? $g : array();
			$num = $n + 1;
			$piezas = (int)(isset($g['piezas']) ? $g['piezas'] : 0);
			if ($piezas < 1) throw new Exception($nombre . ', grupo ' . $num . ': la cantidad de piezas debe ser 1 o más', 400);
			if ($piezas % $promo['paso'] !== 0) {
				throw new Exception($nombre . ', grupo ' . $num . ': ' . ($rol ? 'los rolls van completos (de ' . $promo['paso'] . ' piezas)' : 'las piezas van de a ' . $promo['paso']), 400);
			}
			$total += $piezas;
			$base = null;
			if ($op['base']) {
				if (empty($g['base'])) throw new Exception($nombre . ', grupo ' . $num . ': elige la base', 400);
				$base = $opcion('base', $g['base']);
			}
			$env = null;
			if ($op['envoltorio']) {
				if (empty($g['envoltorio'])) throw new Exception($nombre . ', grupo ' . $num . ': elige el envoltorio', 400);
				$env = $opcion('envoltorio', $g['envoltorio']);
			}
			$rellenos = array();
			foreach (isset($g['rellenos']) && is_array($g['rellenos']) ? $g['rellenos'] : array() as $idR) {
				$o = $opcion('relleno', $idR);
				if (isset($rellenos[$o['id']])) throw new Exception($nombre . ', grupo ' . $num . ': ' . $o['nombre'] . ' está repetido', 400);
				$rellenos[$o['id']] = $o;
			}
			if ($op['relleno'] && !$rellenos) throw new Exception($nombre . ', grupo ' . $num . ': elige al menos un relleno', 400);
			if ($promo['max_rellenos'] > 0 && count($rellenos) > $promo['max_rellenos']) {
				throw new Exception($nombre . ', grupo ' . $num . ': se pueden elegir hasta ' . $promo['max_rellenos'] . ' rellenos', 400);
			}
			if (!$base && !$env && !$rellenos) throw new Exception($nombre . ', grupo ' . $num . ': está vacío', 400);
			// v1.26.0: rolls = piezas / piezas del roll (por roll); por pieza no se usa
			// v1.26.1: nota del roll / grupo (sin caracteres de control, hasta 100)
			$notaG = mb_substr(trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', isset($g['nota']) && is_scalar($g['nota']) ? (string)$g['nota'] : '')), 0, 100);
			$res['grupos'][] = array('piezas' => $piezas, 'rolls' => $rol ? intdiv($piezas, $promo['piezas_rol']) : 0,
				'base' => $base, 'envoltorio' => $env, 'rellenos' => array_values($rellenos), 'nota' => $notaG);
		}
		if ($total !== $promo['piezas']) {
			// v1.26.0: por roll, el aviso en rolls
			$u = $rol ? $promo['piezas_rol'] : 1;
			$dif = intdiv(abs($promo['piezas'] - $total), $u);
			$cuanto = $dif . ($rol ? ($dif === 1 ? ' roll' : ' rolls') : ($dif === 1 ? ' pieza' : ' piezas'));
			throw new Exception($nombre . ': hay ' . intdiv($total, $u) . ' de ' . intdiv($promo['piezas'], $u) . ($rol ? ' rolls' : ' piezas')
				. ($total < $promo['piezas'] ? ', ' . ($dif === 1 ? 'falta ' : 'faltan ') . $cuanto : ', ' . ($dif === 1 ? 'sobra ' : 'sobran ') . $cuanto), 400);
		}
		$extras = array();
		foreach (isset($sel['extras']) && is_array($sel['extras']) ? $sel['extras'] : array() as $e) {
			$id = (int)(is_array($e) && isset($e['id']) ? $e['id'] : 0);
			$q = (int)(is_array($e) && isset($e['cantidad']) ? $e['cantidad'] : 0);
			if ($id <= 0 || $q <= 0) continue;
			$o = $opcion('extra', $id);
			$q += isset($extras[$id]) ? $extras[$id][1] : 0;
			if ($q > 99) throw new Exception('Cantidad demasiado alta en los extras de ' . $nombre, 400);
			$extras[$id] = array($o, $q);
		}
		$res['extras'] = array_values($extras);
		return $res;
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
		} catch (Throwable $e) {   // 19-11-2026: también errores de PHP deshacen la transacción
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
	/**
	 * v1.25.3: clave de administrador para una anulación, según su casilla en Configuraciones > Requerir clave de
	 * administrador para anular ($tipo = admin_anular_venta, admin_devolucion, admin_anular_espera,
	 * admin_anular_producto_mesa, admin_anular_pedido_mesa). Marcada: la pide a todos, también a un administrador.
	 * Desmarcada: no la pide. Sin la columna (migración sin correr), como antes: venta, devolución y espera según la
	 * casilla general (admin_anulacion); en la mesa, solo a quien no es administrador.
	 */
	private function claveAnulacion($db, $tipo, $motivo, $idUsuario, $idRol, $aut)
	{
		$c = $this->configuracion($db);
		$v = array_key_exists($tipo, $c) ? $c[$tipo] : null;
		if ($v === null) {
			if ($tipo === 'admin_anular_producto_mesa' || $tipo === 'admin_anular_pedido_mesa') {
				$this->exigirAutorizacion($db, $motivo, $idUsuario, $idRol, $aut);
			} elseif ($c['admin_anulacion']) {
				$this->exigirAutorizacion($db, $motivo, $idUsuario, $idRol, $aut, true);
			}
			return;
		}
		if ($v) $this->exigirAutorizacion($db, $motivo, $idUsuario, $idRol, $aut, true);
	}

	private function exigirAutorizacion($db, $motivo, $idUsuario, $idRol, $aut, $siempre = false)
	{
		// v1.25.3: $siempre = true pide la clave también a un administrador (Configuraciones > Requerir clave admin para
		// anulación: el usuario la marcó y al anular una mesa con un usuario administrador no la pedía)
		if (!$siempre && $this->esAdmin($db, $idUsuario, $idRol)) return;
		$clave = is_array($aut) && isset($aut['clave']) ? (string)$aut['clave'] : '';
		if ($clave === '') throw new AutorizacionRequerida($motivo);
		// 18-11-2026: límite de intentos (5 incorrectas en 10 min → 5 min de bloqueo), por cajero y empresa
		$this->load->library('Limite_intentos');
		$conteo = 'caja_admin|' . (string)$this->session->userdata('db_server') . '|' . (string)$this->session->userdata('bd_name') . '|' . (int)$idUsuario;
		$this->limite_intentos->revisar($conteo);
		$idAdmin = (int)(isset($aut['id_usuario']) ? $aut['id_usuario'] : 0);
		if ($idAdmin === 0) {
			$general = $this->claveAdminGeneral($db);
			if ($general !== '' && hash_equals($general, $clave)) { $this->limite_intentos->exito($conteo); return; }
		} else {
			$u = $this->q($db, 'SELECT COALESCE(u.clave, \'\') AS clave FROM usuario u LEFT JOIN rol r ON r.id_rol = u.id_rol
				WHERE u.id_usuario = ? AND u.vigente IS NOT FALSE AND ' . self::SQL_ES_ADMIN, array($idAdmin))->row();
			if ($u && $u->clave !== '' && hash_equals($u->clave, $clave)) {
				$this->limite_intentos->exito($conteo);
				log_message('info', 'Caja web: ' . $motivo . ' autorizado por usuario ' . $idAdmin . ' (cajero ' . (int)$idUsuario . ')');
				return;
			}
		}
		$quedan = $this->limite_intentos->fallo($conteo);
		log_message('error', 'Caja web: clave de administrador incorrecta para "' . $motivo . '" (cajero ' . (int)$idUsuario . ', quedan ' . $quedan . ')');
		if ($quedan <= 0) $this->limite_intentos->revisar($conteo);   // recién bloqueado: avisa cuánto esperar
		throw new AutorizacionRequerida($motivo, 'Clave de administrador incorrecta' . ($quedan <= 2 ? ' (quedan ' . $quedan . ' intento' . ($quedan === 1 ? '' : 's') . ')' : ''));
	}

	// ---------------- lista de precios ----------------
	// Usar una lista distinta de la pública pide la clave de administración. Al autorizarla queda permitida en la
	// sesión del cajero hasta que vuelva a la pública (al cambiar de nuevo se vuelve a pedir).

	const SESION_LISTA = 'caja_lista_autorizada';

	/** El cajero cambió la lista en la Caja: la pública no pide nada; otra pide la clave (403 autorizacion). */
	public function autorizarLista($idUsuario, $idRol, $idLprecio, $aut)
	{
		$db = $this->conexion();
		$config = $this->configuracion($db);
		$idLprecio = (int)$idLprecio;
		$l = $this->q($db, 'SELECT id_lprecio, nombre_lprecio FROM lprecio WHERE id_lprecio = ?', array($idLprecio))->row();
		if (!$l) throw new Exception('La lista de precios no existe', 400);
		if ($idLprecio === (int)$config['id_lprecio_publico']) {
			$this->session->unset_userdata(self::SESION_LISTA);
			return array('ok' => true, 'autorizada' => false);
		}
		$this->exigirListaDeCaja($config);
		$this->exigirAutorizacion($db, 'usar la lista de precios ' . $l->nombre_lprecio, $idUsuario, $idRol, $aut);
		$this->session->set_userdata(self::SESION_LISTA, $idLprecio);
		return array('ok' => true, 'autorizada' => true);
	}

	/** Control de cajas: una caja sin "Cambiar lista en caja" vende solo con su lista (ni con clave de administración). */
	private function exigirListaDeCaja($config)
	{
		if (empty($config['caja_fija_lista'])) return;
		throw new Exception('La ' . $config['caja_control']['nombre'] . ' no permite cambiar la lista de precios'
			. ' (Herramientas > Control de cajas)', 409);
	}

	/** Se permite la lista pública, la asignada al cliente de la venta o la que se autorizó en la sesión. */
	private function exigirLista($db, $config, $idUsuario, $idRol, $idLprecio, $idCliente)
	{
		$idLprecio = (int)$idLprecio;
		if ($idLprecio === (int)$config['id_lprecio_publico']) return;
		$this->exigirListaDeCaja($config);
		if ((int)$this->session->userdata(self::SESION_LISTA) === $idLprecio) return;
		if ($this->esAdmin($db, $idUsuario, $idRol)) return;
		if ($idCliente) {
			$t = $this->columnas($db, array('cliente'));
			if (isset($t['cliente']['id_lprecio'])) {
				$c = $this->q($db, 'SELECT COALESCE(id_lprecio, 0) AS l FROM cliente WHERE id_cliente = ?', array((int)$idCliente))->row();
				if ($c && (int)$c->l === $idLprecio) return;
			}
		}
		throw new Exception('Para usar esta lista de precios cámbiala de nuevo en la Caja e ingresa la clave de administración', 403);
	}

	/** v1.24.16: ficha de un cliente para editarlo desde la Caja (botón lápiz junto al cliente elegido). */
	public function fichaCliente($idCliente)
	{
		$r = $this->buscarClientes('', (int)$idCliente);
		if (!$r) throw new Exception('El cliente no existe', 404);
		$r[0]['publico'] = (int)$r[0]['id_cliente'] === (int)$this->configuracion($this->conexion())['id_cliente_publico'];
		return $r[0];
	}

	public function buscarClientes($texto, $idCliente = 0)
	{
		$db = $this->conexion();
		$texto = trim((string)$texto);
		$binds = array();
		$q = "SELECT cl.id_cliente, cl.nombre_cliente, COALESCE(cl.rut::text, '') AS rut, COALESCE(cl.dv, '') AS dv,
				COALESCE(cl.giro, '') AS giro, COALESCE(cl.direccion, '') AS direccion,
				COALESCE(cl.id_comuna, 0) AS id_comuna, COALESCE(co.nombre_comuna, '') AS comuna,
				COALESCE(cl.telefono, '') AS telefono, COALESCE(cl.mail, '') AS mail
			FROM cliente cl LEFT JOIN comuna co ON co.id_comuna = cl.id_comuna
			WHERE cl.nombre_cliente IS NOT NULL";
		if ($idCliente > 0) {
			$q .= " AND cl.id_cliente = ?";
			$binds[] = (int)$idCliente;
		} elseif ($texto !== '') {
			$q .= " AND (cl.nombre_cliente ILIKE ? OR cl.rut::text || '-' || COALESCE(cl.dv, '') ILIKE ?)";
			$like = '%' . str_replace(array('.', '%', '_'), array('', '\%', '\_'), $texto) . '%';
			$binds = array('%' . $texto . '%', $like);
		}
		return $this->q($db, $q . " ORDER BY cl.nombre_cliente LIMIT 30", $binds)->result_array();
	}

	public function guardarCliente($d)
	{
		$db = $this->conexion();
		$nombre = trim(isset($d['nombre_cliente']) ? $d['nombre_cliente'] : '');
		if ($nombre === '') throw new Exception('El nombre del cliente es obligatorio', 400);
		list($rut, $dv) = $this->separarRut(isset($d['rut']) ? $d['rut'] : '');
		// cliente.rut y cliente.dv son NOT NULL
		if (!$rut) throw new Exception('El RUT del cliente es obligatorio', 400);
		// Con id_cliente se edita un cliente existente (p. ej. completar giro y comuna para facturar)
		$idCliente = (int)(isset($d['id_cliente']) ? $d['id_cliente'] : 0);
		if ($idCliente) {
			if (!$this->q($db, 'SELECT 1 FROM cliente WHERE id_cliente = ?', array($idCliente))->row()) {
				throw new Exception('El cliente no existe', 404);
			}
			if ($idCliente === $this->configuracion($db)['id_cliente_publico']) {
				throw new Exception('El cliente de venta al público no se puede editar', 400);
			}
		}
		// 14-11-2026: comuna inexistente = mensaje claro (antes 500 con el error SQL de la clave foránea)
		if (!empty($d['id_comuna']) && !$this->q($db, 'SELECT 1 FROM comuna WHERE id_comuna = ?', array((int)$d['id_comuna']))->row()) {
			throw new Exception('La comuna elegida no existe', 400);
		}
		$existe = $this->q($db, 'SELECT id_cliente FROM cliente WHERE rut = ? AND id_cliente <> ? LIMIT 1', array($rut, $idCliente))->row();
		if ($existe) throw new Exception('Ya existe un cliente con ese RUT', 409);
		$cols = $this->columnas($db, array('cliente'));
		$fila = array(
			'nombre_cliente' => mb_substr($nombre, 0, 100),
			'rut' => $rut,
			'dv' => $dv,
			'giro' => mb_substr(trim(isset($d['giro']) ? $d['giro'] : ''), 0, 100),
			'direccion' => mb_substr(trim(isset($d['direccion']) ? $d['direccion'] : ''), 0, 100),
			'telefono' => mb_substr(trim(isset($d['telefono']) ? $d['telefono'] : ''), 0, 30),
			'mail' => mb_substr(trim(isset($d['mail']) ? $d['mail'] : ''), 0, 100),
			// Sin comuna: NULL (cliente.id_comuna tiene clave foránea a comuna; 0 no existe)
			'id_comuna' => !empty($d['id_comuna']) ? (int)$d['id_comuna'] : null,
		);
		if ($idCliente) {
			$this->actualizar($db, $cols, 'cliente', $fila, 'id_cliente', $idCliente);
			$id = $idCliente;
		} else {
			// 14-11-2026: con la lista pública (con id_lprecio 0 el cliente no salía en el mantenedor de Clientes)
			$id = $this->insertar($db, $cols, 'cliente', $fila + array('id_lprecio' => (int)$this->configuracion($db)['id_lprecio_publico'], 'creditomaximo' => 0), 'id_cliente');
		}
		return array('id_cliente' => $id, 'nombre_cliente' => $nombre, 'rut' => $rut ? $rut . '-' . $dv : '',
			'giro' => trim(isset($d['giro']) ? $d['giro'] : ''), 'direccion' => trim(isset($d['direccion']) ? $d['direccion'] : ''),
			'id_comuna' => max(0, (int)(isset($d['id_comuna']) ? $d['id_comuna'] : 0)));
	}

	public function ventasCaja($idCaja)
	{
		$db = $this->conexion();
		$enNc = $this->sqlEn($this->idsNotaCredito($db));
		$enElec = $this->sqlEn(self::COD_SII_ELECTRONICOS);
		$ventas = $this->q($db, "SELECT v.id_venta, v.folio, v.total, v.descuento, (v.es_cortesia IS TRUE) AS cortesia,
				to_char(v.fecha_transaccion, 'HH24:MI') AS hora, td.nombre_tdocumento,
				COALESCE(c.nombre_cliente, '') AS nombre_cliente,
				(COALESCE(v.mensaje_pedido, '') LIKE 'ANULADA%') AS anulada,
				(v.id_tdocumento $enNc) AS es_nc,
				(COALESCE(td.cod_sii, 0) $enElec) AS electronico,
				(COALESCE(td.cod_sii, 0) $enElec AND COALESCE(v.folio_dte, 0) = 0) AS pendiente_dte,
				COALESCE(tr.nombre_tdocumento || ' N° ' || v.folio_referencia, '') AS referencia,
				COALESCE(pg.pagos, '') AS pagos
			FROM venta v
			LEFT JOIN tdocumento td ON td.id_tdocumento = v.id_tdocumento
			-- Formas de pago agregadas una sola vez para toda la caja: la subconsulta por venta recorría pago_venta
			-- completa en cada fila (no hay índice por id_venta) y el listado tardaba ~2 s con 30 ventas
			LEFT JOIN (SELECT pv.id_venta, string_agg(f.nombre_fpago, ', ') AS pagos
				FROM pago_venta pv JOIN fpago f ON f.id_fpago = pv.id_fpago
				WHERE pv.id_venta IN (SELECT id_venta FROM venta WHERE id_caja = ?)
				GROUP BY pv.id_venta) pg ON pg.id_venta = v.id_venta
			LEFT JOIN tdocumento tr ON tr.id_tdocumento = v.id_tdocumento_referencia AND v.id_tdocumento $enNc
			LEFT JOIN cliente c ON c.id_cliente = v.id_cliente
			WHERE v.id_caja = ?
			ORDER BY v.id_venta DESC", array((int)$idCaja, (int)$idCaja))->result_array();
		foreach ($ventas as &$v) {
			foreach (array('anulada', 'cortesia', 'es_nc', 'electronico', 'pendiente_dte') as $k) {
				$v[$k] = $v[$k] === 't' || $v[$k] === true;
			}
			if ($v['anulada']) $v['pendiente_dte'] = false;
		}
		return $ventas;
	}

	/**
	 * Documentos tributarios (tdocumento.cod_sii > 10) emitidos en un día, de todas las cajas, para la ventana
	 * "Documentos emitidos" de la Caja (verlos y reimprimirlos). Solo lectura.
	 */
	public function documentosEmitidos($fecha)
	{
		$db = $this->conexion();
		$enElec = $this->sqlEn(self::COD_SII_ELECTRONICOS);
		$docs = $this->q($db, "SELECT v.id_venta, to_char(v.fecha_transaccion, 'HH24:MI') AS hora, v.id_caja,
				td.nombre_tdocumento, v.folio, v.total, COALESCE(c.nombre_cliente, '') AS nombre_cliente,
				(COALESCE(v.mensaje_pedido, '') LIKE 'ANULADA%') AS anulada,
				(td.cod_sii $enElec AND COALESCE(v.folio_dte, 0) = 0) AS pendiente_sii
			FROM venta v
			JOIN tdocumento td ON td.id_tdocumento = v.id_tdocumento
			LEFT JOIN cliente c ON c.id_cliente = v.id_cliente
			WHERE td.cod_sii > 10
			  AND v.fecha_transaccion >= ?::date AND v.fecha_transaccion < ?::date + 1
			ORDER BY v.fecha_transaccion DESC, v.id_venta DESC
			LIMIT 500", array($fecha, $fecha))->result_array();
		foreach ($docs as &$d) {
			$d['id_venta'] = (int)$d['id_venta'];
			$d['id_caja'] = (int)$d['id_caja'];
			$d['total'] = (int)round((float)$d['total']);
			$d['anulada'] = $d['anulada'] === 't' || $d['anulada'] === true;
			$d['pendiente_sii'] = !$d['anulada'] && ($d['pendiente_sii'] === 't' || $d['pendiente_sii'] === true);
		}
		return $docs;
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

		// Las NC suman en negativo: "ventas" queda neto de devoluciones, como lo recaudado por forma de pago
		$enNc = $this->sqlEn($this->idsNotaCredito($db));
		$t = $this->q($db, "SELECT COALESCE(sum(CASE WHEN es_cortesia IS TRUE THEN 0 ELSE total END), 0) AS ventas,
				COALESCE(sum(CASE WHEN es_cortesia IS TRUE THEN total ELSE 0 END), 0) AS cortesias,
				COALESCE(sum(descuento) FILTER (WHERE NOT id_tdocumento $enNc), 0) AS descuentos,
				count(*) FILTER (WHERE COALESCE(mensaje_pedido, '') NOT LIKE 'ANULADA%' AND NOT id_tdocumento $enNc) AS cantidad,
				count(*) FILTER (WHERE COALESCE(mensaje_pedido, '') LIKE 'ANULADA%') AS anuladas,
				count(*) FILTER (WHERE COALESCE(mensaje_pedido, '') NOT LIKE 'ANULADA%' AND id_tdocumento $enNc) AS cantidad_devoluciones,
				COALESCE(-sum(total) FILTER (WHERE id_tdocumento $enNc), 0) AS devoluciones
			FROM venta WHERE id_caja = ?", array($idCaja))->row();

		$m = $this->q($db, "SELECT COALESCE(sum(ingreso), 0) AS ingresos, COALESCE(sum(egreso), 0) AS egresos
			FROM detalle_caja WHERE id_caja = ?", array($idCaja))->row();

		$porFpago = array();
		$efectivoVentas = 0;
		$efectivoPropinas = 0;
		$propinas = 0;
		$creditos = 0;
		$rows = $this->q($db, "SELECT f.id_fpago, f.nombre_fpago, (f.escortesia IS TRUE) AS cortesia,
				COALESCE(sum(pv.monto_pagado - COALESCE(pv.propina, 0)), 0) AS monto, COALESCE(sum(pv.propina), 0) AS propina
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
			$esCredito = $this->esCredito($config, $r->id_fpago, $r->nombre_fpago);
			if ($esCredito) $creditos += (int)$r->monto;
			$porFpago[] = array(
				'id_fpago' => (int)$r->id_fpago,
				'nombre' => $r->nombre_fpago,
				'monto' => (int)$r->monto,
				'propina' => (int)$r->propina,
				'efectivo' => $efectivo,
				'cortesia' => $cortesia,
				// 19-11-2026 (auditoría): lo fiado no tiene vouchers que contar: en el cierre lo pone el sistema
				'credito' => $esCredito,
			);
		}

		// Pagos de crédito con tarjeta/transferencia: se rinden en esa forma de pago (los en efectivo ya son ingresos de caja)
		$creditoOtros = 0;
		foreach ($this->pagosCreditoNoEfectivo($db, $idCaja, $config) as $idF => $pc) {
			$creditoOtros += $pc['monto'];
			$hay = false;
			foreach ($porFpago as &$pf) {
				if ($pf['id_fpago'] === $idF) { $pf['monto'] += $pc['monto']; $pf['pagos_credito'] = $pc['monto']; $hay = true; }
			}
			unset($pf);
			if (!$hay) $porFpago[] = array('id_fpago' => $idF, 'nombre' => $pc['nombre'], 'monto' => $pc['monto'], 'propina' => 0,
				'efectivo' => false, 'cortesia' => false, 'pagos_credito' => $pc['monto']);
		}

		$fondo = (int)$caja->fondo_fijo_anterior ?: (int)$caja->fondo_fijo;
		return array(
			'id_caja' => $idCaja,
			// Caja de Control de cajas con que se abrió (nombre, lista y bodega), o null
			'caja_control' => isset($caja->id_control_caja) ? $this->datosControlCaja($db, (int)$caja->id_control_caja) : null,
			'pagos_credito' => $this->totalPagosCredito($db, $idCaja),
			'pagos_credito_otros' => $creditoOtros,
			'apertura' => $caja->apertura,
			'fondo' => $fondo,
			'ventas' => (int)$t->ventas,
			'cortesias' => (int)$t->cortesias,
			'descuentos' => (int)$t->descuentos,
			'cantidad_ventas' => (int)$t->cantidad,
			'anuladas' => (int)$t->anuladas,
			'devoluciones' => (int)$t->devoluciones,
			'cantidad_devoluciones' => (int)$t->cantidad_devoluciones,
			'ingresos' => (int)$m->ingresos,
			'egresos' => (int)$m->egresos,
			'propinas' => $propinas,
			'creditos' => $creditos,
			'propinas_efectivo' => $efectivoPropinas,
			'efectivo_ventas' => $efectivoVentas,
			// Las propinas (de cualquier forma de pago) se pagan a los garzones en efectivo desde la caja
			'efectivo_esperado' => $fondo + $efectivoVentas + $efectivoPropinas - $propinas + (int)$m->ingresos - (int)$m->egresos,
			'por_forma_pago' => $porFpago,
		);
	}

	/**
	 * Datos del ticket de cierre (caja cerrada) o informe X (caja abierta, sin rendición).
	 * Solo cajas de la caja web.
	 */
	public function datosCierre($idCaja)
	{
		$db = $this->conexion();
		$caja = $this->q($db, "SELECT c.*, to_char(c.fecha_movimiento, 'DD/MM/YYYY HH24:MI') AS apertura,
				to_char(c.fecha_rendicion, 'DD/MM/YYYY HH24:MI') AS cierre_texto,
				COALESCE(u.nombre_usuario, '') AS nombre_usuario
			FROM caja c LEFT JOIN usuario u ON u.id_usuario = c.id_usuario
			WHERE c.id_caja = ? AND c.nombre_computador = ?", array((int)$idCaja, self::COMPUTADOR))->row();
		if (!$caja) return null;
		$cerrada = $caja->cerrada === 't' || $caja->cerrada === true;
		$resumen = $this->resumenCaja($caja);

		$rendido = array();
		if ($cerrada) {
			foreach ($this->q($db, 'SELECT d.id_fpago, f.nombre_fpago, sum(d.monto) AS monto
				FROM detalle_pagocaja d JOIN fpago f ON f.id_fpago = d.id_fpago
				WHERE d.id_caja = ? GROUP BY 1, 2', array((int)$idCaja))->result() as $r) {
				$rendido[(int)$r->id_fpago] = array('nombre' => $r->nombre_fpago, 'monto' => (int)$r->monto);
			}
		}
		// Esperado vs rendido por forma de pago (el efectivo se compara contra lo contado en caja)
		$formas = array();
		foreach ($resumen['por_forma_pago'] as $f) {
			if ($f['cortesia']) continue;
			$formas[$f['id_fpago']] = array(
				'nombre' => $f['nombre'],
				'efectivo' => $f['efectivo'],
				'esperado' => $f['efectivo'] ? $resumen['efectivo_esperado'] : $f['monto'] + $f['propina'],
				'rendido' => null,
			);
		}
		// El efectivo siempre se cuenta (hay fondo aunque no haya ventas en efectivo)
		$config = $this->configuracion($db);
		$tieneEfectivo = false;
		foreach ($formas as $f) $tieneEfectivo = $tieneEfectivo || $f['efectivo'];
		if (!$tieneEfectivo) {
			foreach ($this->catalogoFpago($db, $config) as $f) {
				if ($f['efectivo']) {
					$formas = array($f['id_fpago'] => array('nombre' => $f['nombre'], 'efectivo' => true,
						'esperado' => $resumen['efectivo_esperado'], 'rendido' => null)) + $formas;
					break;
				}
			}
		}
		foreach ($rendido as $id => $r) {
			if (!isset($formas[$id])) {
				$formas[$id] = array('nombre' => $r['nombre'], 'efectivo' => $this->esEfectivo($config, $id, $r['nombre']), 'esperado' => 0, 'rendido' => null);
			}
			$formas[$id]['rendido'] = $formas[$id]['efectivo'] ? $r['monto'] + (int)$caja->fondo_fijo : $r['monto'];
		}
		if ($cerrada) {
			// Contado en efectivo = lo entregado + lo que quedó de fondo (aunque no haya fila rendida)
			foreach ($formas as &$f) {
				if ($f['efectivo'] && $f['rendido'] === null) $f['rendido'] = (int)$caja->efectivo_entregado + (int)$caja->fondo_fijo;
				if (!$f['efectivo'] && $f['rendido'] === null) $f['rendido'] = 0;
			}
			unset($f);
		}

		return array(
			'caja' => $caja,
			'cerrada' => $cerrada,
			'resumen' => $resumen,
			'formas' => $formas,
			'inventario' => $cerrada ? $this->inventarioCierreDeCaja($db, (int)$idCaja) : array(),
			'empresa' => $this->q($db, 'SELECT * FROM empresa ORDER BY id_empresa LIMIT 1')->row(),
			'config' => $config,
		);
	}

	/**
	 * Destino de impresión (cocina, barra...) de cada producto para la comanda web: el del producto y,
	 * si no tiene, el de su familia. Se omiten los destinos marcados sin impresión (o llamados "SIN IMPRESION").
	 * Incluye los datos de la impresora del destino (ip, puerto, ruta_windows).
	 * Devuelve [id_producto => ['id' => id_dimpresion, 'nombre' => nombre_dimpresion]].
	 */
	/**
	 * v1.30.0 (pedido del usuario): lo que sale en OTRA impresora además del destino normal del producto.
	 * - segundo:   id_producto => destino. El producto tiene "Imprimir también en otro destino" (producto.id_dimpresion2_web).
	 * - agregados: id_producto del maestro => [NOMBRE DEL AGREGADO EN MAYÚSCULAS => ['id_producto', 'destino']]. El agregado
	 *              tiene marcado "Imprimir en su propio destino" (agregados.usar_destino_impresion): sale en el destino
	 *              de su producto (ej. SALSA en BARRA) en vez de ir solo en la comanda del producto.
	 * Una sola pasada por la base para toda la comanda; sin las columnas (migración pendiente) no cambia nada.
	 */
	public function rutasComanda($idsProducto)
	{
		$res = array('segundo' => array(), 'agregados' => array());
		$ids = array_values(array_unique(array_filter(array_map('intval', (array)$idsProducto))));
		if (!$ids) return $res;
		$db = $this->conexion();
		$cols = $this->columnas($db, array('producto', 'familia', 'dimpresion', 'agregados'));
		if (!isset($cols['dimpresion'])) return $res;
		$sinImp = $this->sqlSinImpresion($db, 'd');
		$destino = function ($r) {
			return array('id' => (int)$r->id_dimpresion, 'nombre' => (string)$r->nombre_dimpresion,
				'ip' => (string)$r->ip, 'puerto' => (string)$r->puerto, 'ruta_windows' => (string)$r->ruta_windows);
		};
		if (isset($cols['producto']['id_dimpresion2_web'])) {
			foreach ($this->q($db, "SELECT p.id_producto, d.id_dimpresion, d.nombre_dimpresion, d.ip, d.puerto, d.ruta_windows
				FROM producto p JOIN dimpresion d ON d.id_dimpresion = p.id_dimpresion2_web
				WHERE p.id_producto IN ? AND COALESCE(p.id_dimpresion2_web, 0) > 0 AND NOT $sinImp", array($ids))->result() as $r) {
				$res['segundo'][(int)$r->id_producto] = $destino($r);
			}
		}
		if (isset($cols['agregados']['usar_destino_impresion'], $cols['producto']['id_dimpresion'])) {
			$famDest = isset($cols['familia']['id_dimpresion']) ? 'NULLIF(f.id_dimpresion, 0)' : 'NULL';
			foreach ($this->q($db, "SELECT a.id_producto_maestro, pa.id_producto, pa.nombre_producto,
					d.id_dimpresion, d.nombre_dimpresion, d.ip, d.puerto, d.ruta_windows
				FROM agregados a
				JOIN producto pa ON pa.id_producto = a.id_producto
				LEFT JOIN familia f ON f.id_familia = pa.id_familia
				JOIN dimpresion d ON d.id_dimpresion = COALESCE(NULLIF(pa.id_dimpresion, 0), $famDest)
				WHERE a.id_producto_maestro IN ? AND a.usar_destino_impresion IS TRUE AND a.eliminado IS NOT TRUE AND NOT $sinImp",
				array($ids))->result() as $r) {
				$res['agregados'][(int)$r->id_producto_maestro][self::nombreAgregadoComanda($r->nombre_producto)] =
					array('id_producto' => (int)$r->id_producto, 'destino' => $destino($r));
			}
		}
		return $res;
	}

	/**
	 * Nombre del agregado tal como viene en la comanda, sin su mensaje ("PALTA » sin sal", "PALTA: sin sal" o
	 * "PALTA (sin sal)" => "PALTA"), en mayúsculas, para reconocerlo.
	 */
	public static function nombreAgregadoComanda($texto)
	{
		$t = preg_split('/\s*»\s*|:\s|\s\(/u', trim((string)$texto), 2);
		return mb_strtoupper(trim(ltrim($t[0], '+')), 'UTF-8');
	}

	public function destinosComanda($idsProducto)
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', (array)$idsProducto))));
		if (!$ids) return array();
		$db = $this->conexion();
		$cols = $this->columnas($db, array('producto', 'familia', 'dimpresion'));
		if (!isset($cols['dimpresion']) || !isset($cols['producto']['id_dimpresion'])) return array();
		$famDest = isset($cols['familia']['id_dimpresion']) ? 'NULLIF(f.id_dimpresion, 0)' : 'NULL';
		$sinImp = $this->sqlSinImpresion($db, 'd');   // v1.23.2: también la ruta de Windows "SIN IMPRESION"
		$rows = $this->q($db, "SELECT p.id_producto, d.id_dimpresion, d.nombre_dimpresion, d.ip, d.puerto, d.ruta_windows
			FROM producto p
			LEFT JOIN familia f ON f.id_familia = p.id_familia
			JOIN dimpresion d ON d.id_dimpresion = COALESCE(NULLIF(p.id_dimpresion, 0), $famDest)
			WHERE p.id_producto IN ?
				AND NOT $sinImp", array($ids))->result();
		$res = array();
		foreach ($rows as $r) {
			$res[(int)$r->id_producto] = array('id' => (int)$r->id_dimpresion, 'nombre' => (string)$r->nombre_dimpresion,
				'ip' => (string)$r->ip, 'puerto' => (string)$r->puerto, 'ruta_windows' => (string)$r->ruta_windows);
		}
		return $res;
	}

	/**
	 * v1.23.2: SQL que dice si un destino de impresión NO imprime: la casilla "sin impresión", o el nombre o la ruta de
	 * Windows que empiezan con "SIN IMPRES" (ej. un destino "SIN IMPRESION" para lo que no debe salir en papel).
	 */
	private function sqlSinImpresion($db, $d = 'd')
	{
		$cols = $this->columnas($db, array('dimpresion'));
		$flag = isset($cols['dimpresion']['sin_impresion']) ? "COALESCE($d.sin_impresion, false)" : 'false';
		return "($flag OR upper(trim(COALESCE($d.nombre_dimpresion, ''))) LIKE 'SIN IMPRES%'"
			. " OR upper(trim(COALESCE($d.ruta_windows, ''))) LIKE 'SIN IMPRES%')";
	}

	/** v1.23.2: el destino como lo usan la Caja y el agente, con la marca sin_impresion. */
	private function filaDestino($r)
	{
		return array('id' => (int)$r->id_dimpresion, 'nombre' => (string)$r->nombre_dimpresion,
			'ip' => (string)$r->ip, 'puerto' => (string)$r->puerto, 'ruta_windows' => (string)$r->ruta_windows,
			'sin_impresion' => $r->sin_impresion === true || $r->sin_impresion === 't');
	}

	/** Opciones de Configuraciones que usa la Caja (ver configuracion()). */
	public function opciones()
	{
		return $this->configuracion($this->conexion());
	}

	/**
	 * Impresora del cierre de caja: la elegida en Configuraciones > Impresora para cierre de caja (nombre del destino de
	 * impresión); si no hay o no existe, la de la caja.
	 */
	public function destinoCierre()
	{
		return $this->destinoCaja();   // v1.25.2: es la misma impresora de la caja
	}

	/** v1.21.0: destino de impresión por su nombre (sin distinguir mayúsculas); null si no hay o no existe. */
	public function destinoPorNombre($nombre)
	{
		$nombre = trim((string)$nombre);
		if ($nombre === '') return null;
		$db = $this->conexion();
		$r = $this->q($db, 'SELECT d.id_dimpresion, d.nombre_dimpresion, d.ip, d.puerto, d.ruta_windows, ' . $this->sqlSinImpresion($db, 'd') . ' AS sin_impresion
			FROM dimpresion d WHERE upper(trim(d.nombre_dimpresion)) = upper(?) ORDER BY d.id_dimpresion LIMIT 1', array($nombre))->row();
		return $r ? $this->filaDestino($r) : null;
	}

	/**
	 * Impresora de boletas/facturas: la de la caja; si en Configuraciones > Impresión de documentos hay una
	 * "Ruta impresora" (ej. //127.0.0.1/pos80), se imprime en esa impresora compartida de Windows.
	 */
	public function destinoDocumentos()
	{
		$d = $this->destinoCaja();
		$ruta = $this->opciones()['ruta_documentos'];
		// Solo si algún agente de impresión tiene esa impresora: si no (ej. //127.0.0.1/caja cuando la impresora se llama
		// pos80), el trabajo quedaba pendiente para siempre y la boleta no salía. Entonces va a la impresora de la caja.
		if ($ruta !== '' && (strpos($ruta, '//') === 0 || strpos($ruta, '\\') === 0) && $this->agenteTieneImpresora($ruta)) {
			return array('id' => $d ? $d['id'] : 0, 'nombre' => $d ? $d['nombre'] : 'DOCUMENTOS', 'ip' => '', 'puerto' => '', 'ruta_windows' => $ruta,
				'sin_impresion' => stripos(trim($ruta), 'SIN IMPRES') === 0);
		}
		return $d;
	}

	/** ¿Algún agente de impresión activo informó una impresora con el nombre de esa ruta (//equipo/nombre)? */
	private function agenteTieneImpresora($ruta)
	{
		$partes = preg_split('#[\\\\/]+#', rtrim(trim((string)$ruta), '\\/'));
		$nombre = mb_strtolower(trim((string)end($partes)));
		if ($nombre === '') return false;
		$db = $this->conexion();
		$c = $this->columnas($db, array('agente_impresion'));
		if (!isset($c['agente_impresion']['impresoras'])) return false;
		foreach ($this->q($db, 'SELECT impresoras FROM agente_impresion WHERE activo')->result() as $a) {
			$lista = json_decode((string)$a->impresoras, true);
			if (!is_array($lista)) continue;
			foreach ($lista as $imp) {
				if (mb_strtolower(trim((string)$imp)) === $nombre) return true;
			}
		}
		return false;
	}

	/** Abrir la gaveta: con "Requerir administrador para: Apertura de gaveta" pide la clave de un administrador. */
	public function autorizarGaveta($idUsuario, $idRol, $aut)
	{
		$db = $this->conexion();
		if ($this->configuracion($db)['admin_gaveta']) $this->exigirAutorizacion($db, 'abrir la gaveta', $idUsuario, $idRol, $aut);
	}

	/** Anular una venta en espera: pide la clave de administrador si Configuraciones lo exige para anular (igual que anular una venta). */
	public function autorizarAnulacionEspera($idUsuario, $idRol, $aut)
	{
		$db = $this->conexion();
		$this->claveAnulacion($db, 'admin_anular_espera', 'anular una venta en espera', $idUsuario, $idRol, $aut);
	}

	/**
	 * Impresora de la caja (boletas, comprobantes, cierre y gaveta). v1.25.2: la elegida en Configuraciones > Impresora de
	 * la caja (nombre del destino de impresión). Solo si no hay ninguna elegida (o ya no existe), la que dejó el POS de
	 * escritorio (configuracion.id_dimpresion_caja). Antes la gaveta y los comprobantes iban siempre a esta última, que la
	 * web no deja elegir (en la base local es 0: no salían).
	 */
	public function destinoCaja()
	{
		$d = $this->destinoPorNombre($this->opciones()['impresora_cierre']);
		return $d ?: $this->destinoCajaPos();
	}

	/** Impresora de la caja que deja el POS de escritorio (configuracion.id_dimpresion_caja); null si no hay. */
	private function destinoCajaPos()
	{
		$db = $this->conexion();
		$c = $this->columnas($db, array('configuracion', 'dimpresion'));
		if (!isset($c['configuracion']['id_dimpresion_caja']) || !isset($c['dimpresion'])) return null;
		$r = $this->q($db, 'SELECT d.id_dimpresion, d.nombre_dimpresion, d.ip, d.puerto, d.ruta_windows, ' . $this->sqlSinImpresion($db, 'd') . ' AS sin_impresion
			FROM configuracion c JOIN dimpresion d ON d.id_dimpresion = c.id_dimpresion_caja LIMIT 1')->row();
		return $r ? $this->filaDestino($r) : null;
	}

	/** Cabecera, detalle y pagos para imprimir un documento. */
	public function documento($idVenta)
	{
		$db = $this->conexion();
		$v = $this->q($db, "SELECT v.*, to_char(v.fecha_transaccion, 'DD/MM/YYYY HH24:MI') AS fecha_texto,
				td.nombre_tdocumento, COALESCE(td.cod_sii, 0) AS cod_sii,
				c.nombre_cliente, c.rut, c.dv, c.giro, c.direccion, cc.nombre_comuna AS comuna, u.nombre_usuario
			FROM venta v
			LEFT JOIN tdocumento td ON td.id_tdocumento = v.id_tdocumento
			LEFT JOIN cliente c ON c.id_cliente = v.id_cliente
			LEFT JOIN comuna cc ON cc.id_comuna = c.id_comuna
			LEFT JOIN usuario u ON u.id_usuario = v.id_usuario
			WHERE v.id_venta = ?", array((int)$idVenta))->row();
		if (!$v) return null;

		$detalle = $this->q($db, "SELECT COALESCE(p.nombre_producto, 'Producto ' || dv.id_producto)
					|| COALESCE(' + ' || pa.nombre_producto, '') AS nombre,
				dv.cantidad, dv.bruto_un, dv.subtotal, (COALESCE(dv.poriva, 1) = 0) AS exento, COALESCE(dv.porila, 0) AS porila,
				" . (isset($this->columnas($db, array('detalle_venta'))['detalle_venta']['es_detalle_menu']) ? '(dv.es_detalle_menu IS TRUE)' : 'false') . " AS del_menu
			FROM detalle_venta dv
			LEFT JOIN producto p ON p.id_producto = dv.id_producto
			LEFT JOIN producto pa ON pa.id_producto = dv.id_agregado AND dv.id_agregado > 0
			WHERE dv.id_venta = ? ORDER BY dv.id_detalle_venta", array((int)$idVenta))->result();
		// v1.24.0: producto elegido de un menú (sale debajo del menú, con "+"; sin precio si no tiene costo adicional)
		foreach ($detalle as $dd) $dd->del_menu = $dd->del_menu === true || $dd->del_menu === 't';
		$pagos = $this->q($db, "SELECT pv.id_fpago, f.nombre_fpago, pv.monto_pagado, pv.vuelto, pv.paga_con
			FROM pago_venta pv JOIN fpago f ON f.id_fpago = pv.id_fpago
			WHERE pv.id_venta = ? ORDER BY pv.id_pventa", array((int)$idVenta))->result();
		$empresa = $this->q($db, "SELECT * FROM empresa ORDER BY id_empresa LIMIT 1")->row();

		// Venta sin cliente elegido (cliente público / 66.666.666-6): no se imprimen sus datos
		$config = $this->configuracion($db);
		// Copia cedible: factura electrónica emitida y vendida con crédito de confianza
		$conCredito = false;
		foreach ($pagos as $pg) {
			if ($this->esCredito($config, $pg->id_fpago, $pg->nombre_fpago)) $conCredito = true;
		}
		if ((int)$v->id_cliente === (int)$config['id_cliente_publico'] || (int)$v->rut === 66666666) {
			$v->nombre_cliente = $v->rut = $v->dv = $v->giro = $v->direccion = $v->comuna = '';
		}
		// Factura con giro / dirección elegidos al cobrar: se imprime con esos datos y quién la solicitó
		$v->solicitante = '';
		$v->motivo_factura = '';
		$rec = $this->hayReceptor($db) ? $this->q($db, 'SELECT * FROM venta_receptor WHERE id_venta = ?', array((int)$idVenta))->row() : null;
		if ($rec && $v->rut) {
			if (trim((string)$rec->razon_social) !== '') $v->nombre_cliente = $rec->razon_social;
			$v->giro = $rec->giro;
			$v->direccion = $rec->direccion;
			$v->comuna = $rec->comuna;
			if ((int)$rec->rut_solicita) {
				$v->solicitante = trim(number_format((float)$rec->rut_solicita, 0, ',', '.') . '-' . $rec->dv_solicita . ' ' . (string)$rec->nombre_solicita);
			}
			if (isset($rec->motivo)) $v->motivo_factura = trim((string)$rec->motivo);
		}
		if ($empresa) {
			$r = $this->q($db, 'SELECT nombre_comuna FROM comuna WHERE id_comuna = ?', array((int)$empresa->id_comuna))->row();
			$empresa->comuna = $r ? $r->nombre_comuna : '';
			// Configuraciones > Datos de la Empresa: la dirección, comuna y teléfono de la sucursal de la caja que vendió
			$this->load->helper('sucursal_empresa');
			$empresa = empresa_con_sucursal($empresa, sucursal_de_caja($db, isset($v->id_caja) ? (int)$v->id_caja : 0));
		}

		// La NC se guarda en negativo; impresa se muestra en positivo con su documento de referencia
		$esNc = in_array((int)$v->id_tdocumento, $this->idsNotaCredito($db), true);
		$referencia = '';
		if ($esNc) {
			foreach (array('subtotal', 'descuento', 'neto', 'iva', 'total', 'monto_exento') as $c) $v->$c = abs((float)$v->$c);
			foreach ($detalle as $d) {
				$d->cantidad = abs((float)$d->cantidad);
				$d->subtotal = abs((float)$d->subtotal);
			}
			foreach ($pagos as $pg) {
				foreach (array('monto_pagado', 'paga_con', 'vuelto') as $c) $pg->$c = abs((float)$pg->$c);
			}
			$r = $this->q($db, 'SELECT nombre_tdocumento, COALESCE(cod_sii, 0) AS cod_sii FROM tdocumento WHERE id_tdocumento = ?', array((int)$v->id_tdocumento_referencia))->row();
			$referencia = ($r ? $r->nombre_tdocumento : 'Documento') . ' N° ' . (int)$v->folio_referencia
				. ($v->fecha_referencia ? ' del ' . date('d/m/Y', strtotime($v->fecha_referencia)) : '');
		}

		// Montos a imprimir: la factura (y su NC) va en neto por línea, como se calculó (montos()); boletas e
		// internos con los precios brutos que vio el cliente. Lo exento va aparte, antes del total.
		$docBase = $esNc ? $r : $v;
		$enNeto = $docBase && (in_array((int)$docBase->cod_sii, array(33, 34, 46, 52, 56)) || stripos((string)$docBase->nombre_tdocumento, 'FACTURA') !== false);
		$factor = 1 + (isset($v->por_iva) && (float)$v->por_iva > 0 ? (float)$v->por_iva : $config['por_iva']) / 100;
		$v->monto_exento = abs((float)(isset($v->monto_exento) ? $v->monto_exento : 0));
		$sumaLineas = 0;
		foreach ($detalle as $d) {
			$d->exento = $d->exento === 't' || $d->exento === true;
			$neto = $enNeto && !$d->exento;
			$d->precio_mostrar = $neto ? $d->bruto_un / ($factor + max(0, (float)$d->porila) / 100) : (float)$d->bruto_un;
			$d->monto_mostrar = $neto ? (int)round($d->subtotal / ($factor + max(0, (float)$d->porila) / 100)) : (int)round($d->subtotal);
			$sumaLineas += $d->monto_mostrar;
		}
		$v->en_neto = $enNeto;
		$v->subtotal_mostrar = $enNeto ? $sumaLineas : (float)$v->subtotal;
		$v->descuento_mostrar = $enNeto ? max(0, $sumaLineas - ((float)$v->neto + $v->monto_exento)) : (float)$v->descuento;

		return array(
			'venta' => $v,
			'detalle' => $detalle,
			'pagos' => $pagos,
			'empresa' => $empresa,
			'nc' => $esNc,
			'referencia' => $referencia,
			'anulada' => strpos((string)$v->mensaje_pedido, 'ANULADA') === 0,
			'electronico' => in_array((int)$v->cod_sii, self::COD_SII_ELECTRONICOS),
			// Emitido ante el SII (folio de Cloud Apps); si no, el documento está pendiente de envío
			'emitido' => (int)$v->folio_dte > 0,
			'ted' => isset($v->ted_dte) ? (string)$v->ted_dte : '',
			'sii' => in_array((int)$v->cod_sii, self::COD_SII_ELECTRONICOS) ? $this->leyendaSii($db, (int)$v->cod_sii) : null,
			// Copia cedible: solo FACTURAS electrónicas (33 afecta, 34 exenta) vendidas con crédito de confianza; boletas no
			'cedible' => $config['copia_cedible'] && $conCredito && !$esNc && (int)$v->folio_dte > 0
				&& in_array((int)$v->cod_sii, array(33, 34), true),
		);
	}

	/**
	 * Datos que el SII exige en la representación impresa de un DTE: oficina del SII bajo el
	 * recuadro, resolución de autorización bajo el timbre y dónde verificar el documento
	 * (las boletas se verifican en el sitio del emisor; facturas y notas en www.sii.cl).
	 */
	private function leyendaSii($db, $codSii)
	{
		$c = $this->q($db, 'SELECT c.resolucion_sii, c.anio_resolucion_sii, co.nombre_comuna
			FROM configuracion c LEFT JOIN comuna co ON co.id_comuna = c.id_comuna_sii LIMIT 1')->row();
		$this->config->load('caja', true);
		$esBoleta = in_array($codSii, array(39, 41), true);
		return array(
			'oficina' => $c && $c->nombre_comuna ? 'S.I.I. - ' . mb_strtoupper($c->nombre_comuna) : '',
			'resolucion' => $c && (int)$c->resolucion_sii > 0
				? 'Resolución ' . (int)$c->resolucion_sii . ' de ' . (int)$c->anio_resolucion_sii : '',
			'verificacion' => $esBoleta ? (string)$this->config->item('caja_url_verificacion_boleta', 'caja') : 'www.sii.cl',
			'publicidad' => (array)$this->config->item('caja_ticket_publicidad', 'caja'),
		);
	}

	// ====================== Escritura ======================

	/**
	 * $idControlCaja: la caja de Herramientas > Control de cajas que eligió el cajero. Si hay cajas vigentes es obligatoria,
	 * vigente y no abierta por otro usuario; sin cajas (o sin la migración) se abre como antes.
	 */
	public function abrirCaja($idUsuario, $fondo, $idControlCaja = 0)
	{
		$db = $this->conexion();
		if ($this->cajaAbierta($idUsuario)) throw new Exception('Ya tienes una caja abierta', 409);
		$idControlCaja = (int)$idControlCaja;
		$this->load->model('Mcontrolcaja');
		// 12-11-2026: la caja se elige después de la empresa y la sucursal (de cualquier sucursal). Si hay cajas vigentes
		// de la intranet, hay que elegir una; sin cajas se abre como antes.
		$elegible = null;
		if ($this->Mcontrolcaja->hayCajasVigentes()) {
			if ($idControlCaja <= 0) throw new Exception('Elige la empresa, la sucursal y la caja que vas a abrir', 400);
			$elegible = current($this->Mcontrolcaja->paraAbrir($idUsuario, null, $idControlCaja)) ?: null;
			if (!$elegible) throw new Exception('La caja elegida ya no está vigente. Actualiza la página y elige otra.', 400);
		}
		if ($elegible === null) $idControlCaja = 0;
		$cols = $this->columnas($db, array('caja'));
		$ahora = date('Y-m-d H:i:s');
		$r = $this->q($db, 'SELECT min(id_turno) AS id FROM turno')->row();
		$db->trans_begin();
		try {
			// 14-11-2026 (QA): un doble clic abría dos cajas del mismo usuario (la revisión de arriba iba fuera de la
			// transacción). Candado por usuario hasta terminar: el segundo envío espera al primero y aquí ya la ve abierta.
			$this->q($db, 'SELECT pg_advisory_xact_lock(?, ?)', array(self::LOCK_ABRIR_CAJA, (int)$idUsuario));
			if ($this->cajaAbierta($idUsuario)) throw new Exception('Ya tienes una caja abierta', 409);
			if (!$idControlCaja) {
				$id = $this->insertarCaja($db, $cols, $idUsuario, $fondo, $ahora, $r, 0);
				$db->trans_commit();
				return $id;
			}
			// La caja elegida se bloquea mientras se abre: dos cajeros no abren la misma a la vez
			$this->q($db, 'SELECT 1 FROM control_caja WHERE id_control_caja = ? FOR UPDATE', array($idControlCaja));
			$otro = $this->q($db, "SELECT COALESCE(u.nombre_usuario, 'otro usuario') AS usuario FROM caja c LEFT JOIN usuario u ON u.id_usuario = c.id_usuario
				WHERE c.id_control_caja = ? AND c.cerrada IS NOT TRUE AND c.id_usuario <> ? LIMIT 1", array($idControlCaja, (int)$idUsuario))->row();
			if ($otro) throw new Exception('La ' . $elegible['nombre'] . ' ya está abierta por ' . $otro->usuario . '. Elige otra caja.', 409);
			$id = $this->insertarCaja($db, $cols, $idUsuario, $fondo, $ahora, $r, $idControlCaja);
			$db->trans_commit();
		} catch (Throwable $e) {   // 19-11-2026: también errores de PHP deshacen la transacción
			$db->trans_rollback();
			throw $e;
		}
		$this->cajaControl = false;
		return $id;
	}

	private function insertarCaja($db, $cols, $idUsuario, $fondo, $ahora, $r, $idControlCaja)
	{
		return $this->insertar($db, $cols, 'caja', ($idControlCaja > 0 ? array('id_control_caja' => (int)$idControlCaja) : array()) + array(
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

	/** Dueño y estado de una caja (para que un cajero solo vea sus cajas). null si no existe. */
	public function duenoCaja($idCaja)
	{
		$r = $this->q($this->conexion(), 'SELECT id_usuario, (cerrada IS TRUE) AS cerrada FROM caja WHERE id_caja = ?', array((int)$idCaja))->row();
		if (!$r) return null;
		$r->cerrada = $r->cerrada === 't' || $r->cerrada === true;
		return $r;
	}

	/** Caja en que se hizo una venta, o null si la venta no existe. */
	/**
	 * v1.24.18: ¿se abre la gaveta con esta venta? (pedido del usuario: no se abría al pagar en efectivo).
	 * Con "Abrir gaveta solo por pago en efectivo" (configuracion.abrir_gaveta_solo_por_pago_efectivo, la del POS de
	 * escritorio; sin la columna o vacía = activa) solo si la venta tiene un pago en efectivo; apagada, con cualquier cobro.
	 * Solo para una venta de ESTA caja y recién hecha (3 minutos): no sirve para abrir la gaveta sin venta y sin la clave
	 * de administrador (para eso está el botón "Abrir gaveta").
	 */
	public function gavetaConVenta($idCaja, $idVenta)
	{
		$db = $this->conexion();
		$v = $this->q($db, 'SELECT COALESCE(id_caja, 0) AS id_caja, fecha_transaccion FROM venta WHERE id_venta = ?', array((int)$idVenta))->row();
		if (!$v || (int)$v->id_caja !== (int)$idCaja) throw new Exception('La venta no es de esta caja', 403);
		$t = strtotime((string)$v->fecha_transaccion);
		if ($t === false || abs(time() - $t) > 180) return false;
		$soloEfectivo = true;
		if (isset($this->columnas($db, array('configuracion'))['configuracion']['abrir_gaveta_solo_por_pago_efectivo'])) {
			$f = $this->q($db, 'SELECT (abrir_gaveta_solo_por_pago_efectivo IS NOT FALSE) AS s FROM configuracion LIMIT 1')->row();
			$soloEfectivo = !$f || $f->s === 't' || $f->s === true;
		}
		if (!$soloEfectivo) return true;
		$config = $this->configuracion($db);
		foreach ($this->q($db, 'SELECT pv.id_fpago, COALESCE(f.nombre_fpago, \'\') AS nombre_fpago FROM pago_venta pv
				LEFT JOIN fpago f ON f.id_fpago = pv.id_fpago WHERE pv.id_venta = ?', array((int)$idVenta))->result() as $p) {
			if ($this->esEfectivo($config, $p->id_fpago, $p->nombre_fpago)) return true;
		}
		return false;
	}

	public function cajaDeVenta($idVenta)
	{
		$r = $this->q($this->conexion(), 'SELECT COALESCE(id_caja, 0) AS id_caja FROM venta WHERE id_venta = ?', array((int)$idVenta))->row();
		return $r ? (int)$r->id_caja : null;
	}

	public function registrarMovimiento($caja, $tipo, $monto, $observacion, $ocultarDisponible = false)
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
			if (!$esIngreso) {
				// Un retiro no puede sacar más efectivo del que hay en la caja (fondo + ventas en efectivo + ingresos
				// - retiros - propinas pagadas). La caja queda bloqueada para que dos retiros a la vez no lo pasen.
				$this->q($db, 'SELECT id_caja FROM caja WHERE id_caja = ? FOR UPDATE', array((int)$caja->id_caja));
				$res = $this->resumenCaja($caja);
				$disponible = (int)$res['efectivo_esperado'];
				// Configuraciones > Permitir retiro fondo fijo: si está desmarcado, el fondo inicial no se puede retirar
				$sinFondo = !$this->configuracion($db)['retiro_fondo'];
				if ($sinFondo) $disponible -= (int)$res['fondo'];
				if ($monto > $disponible && $ocultarDisponible) {
					// Cierre ciego: sin gerencia no se dice cuánto hay (con eso se sabía el efectivo esperado)
					throw new Exception('El retiro es mayor que el efectivo disponible en la caja', 400);
				}
				if ($monto > $disponible) {
					$f = '$' . number_format(max(0, $disponible), 0, ',', '.');
					throw new Exception($disponible > 0
						? 'Solo hay ' . $f . ' en efectivo ' . ($sinFondo ? 'para retirar (el fondo fijo no se puede retirar)' : 'en la caja') . ': el retiro no puede ser mayor'
						: ($sinFondo ? 'Solo queda el fondo fijo en la caja y no se puede retirar (Configuraciones > Permitir retiro fondo fijo)' : 'No hay efectivo en la caja para hacer un retiro'), 400);
				}
			}
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
		} catch (Throwable $e) {   // 19-11-2026: también errores de PHP deshacen la transacción
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
	/**
	 * Cupo de crédito del cliente (cliente.creditomaximo): lo que debe (ctacte_cliente: monto - pagado) más este crédito no
	 * puede pasarse del cupo. Cupo 0 o vacío = sin límite, como estaba antes. La fila del cliente queda bloqueada hasta el
	 * final de la venta, así dos cajas no pueden usar el mismo cupo a la vez.
	 */
	private function validarCupoCredito($db, $idCliente, $monto)
	{
		$c = $this->q($db, 'SELECT COALESCE(creditomaximo, 0) AS cupo FROM cliente WHERE id_cliente = ? FOR UPDATE', array((int)$idCliente))->row();
		$cupo = $c ? (int)$c->cupo : 0;
		// Plazo máximo (cliente.plazo_credito, días): con una venta a crédito vencida sin pagar no se le da más crédito
		$vencido = $this->deudaVencida($db, $idCliente);
		if ($vencido['monto'] > 0) {
			throw new Exception('El cliente tiene $' . number_format($vencido['monto'], 0, ',', '.') . ' en créditos vencidos (plazo de '
				. $vencido['plazo'] . ' días). Debe pagarlos en la pestaña Créditos antes de comprar a crédito otra vez.', 409);
		}
		if ($cupo <= 0) return;
		$deuda = 0;
		$hay = $this->q($db, "SELECT to_regclass('public.ctacte_cliente') IS NOT NULL AS hay")->row();
		if ($hay && ($hay->hay === 't' || $hay->hay === true)) {
			$deuda = (int)$this->q($db, 'SELECT COALESCE(sum(COALESCE(monto, 0) - COALESCE(pagado, 0)), 0) AS deuda FROM ctacte_cliente WHERE id_cliente = ?',
				array((int)$idCliente))->row()->deuda;
		}
		$disponible = max(0, $cupo - $deuda);
		if ($monto > $disponible && $cupo > 0) {
			$f = function ($n) { return '$' . number_format($n, 0, ',', '.'); };
			throw new Exception('El cliente no tiene cupo de crédito suficiente. Cupo ' . $f($cupo) . ', debe ' . $f($deuda)
				. ', disponible ' . $f($disponible) . '. Paga la diferencia con otra forma de pago o pide que le suban el cupo.', 409);
		}
	}

	// ====================== Créditos de clientes (pagos en la caja) ======================

	/** ¿Están las tablas de pagos de crédito (sql/credito_clientes.sql)? */
	private function hayPagosCredito($db)
	{
		$c = $this->columnas($db, array('pago_credito_cliente', 'detalle_pago_credito', 'ctacte_cliente'));
		return isset($c['pago_credito_cliente'], $c['detalle_pago_credito'], $c['ctacte_cliente']);
	}

	private function exigirPagosCredito($db)
	{
		if (!$this->hayPagosCredito($db)) {
			throw new Exception('Falta preparar la base para los pagos de crédito: corre el script sql/credito_clientes.sql', 409);
		}
	}

	/** Plazo del crédito del cliente en días (0 = sin plazo); 30 si la base aún no tiene la columna. */
	private function plazoCredito($db, $idCliente)
	{
		if (!isset($this->columnas($db, array('cliente'))['cliente']['plazo_credito'])) return 30;
		$r = $this->q($db, 'SELECT COALESCE(plazo_credito, 30) AS p FROM cliente WHERE id_cliente = ?', array((int)$idCliente))->row();
		return $r ? max(0, (int)$r->p) : 30;
	}

	/** Lo que el cliente debe con el plazo ya cumplido: {monto, plazo}. */
	private function deudaVencida($db, $idCliente)
	{
		$plazo = $this->plazoCredito($db, $idCliente);
		if ($plazo <= 0 || !isset($this->columnas($db, array('ctacte_cliente'))['ctacte_cliente'])) return array('monto' => 0, 'plazo' => $plazo);
		$r = $this->q($db, "SELECT COALESCE(sum(cc.monto - COALESCE(cc.pagado, 0)), 0) AS m
			FROM ctacte_cliente cc JOIN venta v ON v.id_venta = cc.id_venta
			WHERE cc.id_cliente = ? AND cc.monto > COALESCE(cc.pagado, 0)
				AND COALESCE(v.mensaje_pedido, '') NOT LIKE 'ANULADA%'
				AND v.fecha_transaccion::date + ? < current_date", array((int)$idCliente, $plazo))->row();
		return array('monto' => $r ? (int)$r->m : 0, 'plazo' => $plazo);
	}

	/** Clientes que deben algo: id, nombre, rut, deuda, vencido, ventas pendientes. */
	public function clientesConDeuda()
	{
		$db = $this->conexion();
		if (!isset($this->columnas($db, array('ctacte_cliente'))['ctacte_cliente'])) return array();
		$conPlazo = isset($this->columnas($db, array('cliente'))['cliente']['plazo_credito']);
		$plazo = $conPlazo ? 'COALESCE(c.plazo_credito, 30)' : '30';
		// Con la columna se agrupa también por el plazo (un número suelto en GROUP BY sería una posición de columna)
		$grupoPlazo = $conPlazo ? ', ' . $plazo : '';
		$filas = $this->q($db, "SELECT c.id_cliente, c.nombre_cliente, c.rut, c.dv, $plazo AS plazo,
				sum(cc.monto - COALESCE(cc.pagado, 0)) AS deuda, count(*) AS ventas,
				COALESCE(sum(cc.monto - COALESCE(cc.pagado, 0)) FILTER (WHERE $plazo > 0 AND v.fecha_transaccion::date + $plazo < current_date), 0) AS vencido
			FROM ctacte_cliente cc
			JOIN cliente c ON c.id_cliente = cc.id_cliente
			JOIN venta v ON v.id_venta = cc.id_venta
			WHERE cc.monto > COALESCE(cc.pagado, 0) AND COALESCE(v.mensaje_pedido, '') NOT LIKE 'ANULADA%'
			GROUP BY c.id_cliente, c.nombre_cliente, c.rut, c.dv$grupoPlazo
			ORDER BY vencido DESC, deuda DESC, c.nombre_cliente")->result();
		$res = array();
		foreach ($filas as $f) {
			$res[] = array('id_cliente' => (int)$f->id_cliente, 'nombre' => $f->nombre_cliente,
				'rut' => $f->rut ? number_format((float)$f->rut, 0, ',', '.') . '-' . $f->dv : '',
				'plazo' => (int)$f->plazo, 'deuda' => (int)$f->deuda, 'vencido' => (int)$f->vencido, 'ventas' => (int)$f->ventas);
		}
		return $res;
	}

	/** Ventas a crédito pendientes del cliente (con su detalle) y sus últimos pagos. */
	public function creditosCliente($idCliente)
	{
		$db = $this->conexion();
		$idCliente = (int)$idCliente;
		$cli = $this->q($db, 'SELECT id_cliente, nombre_cliente, rut, dv, COALESCE(creditomaximo, 0) AS cupo FROM cliente WHERE id_cliente = ?', array($idCliente))->row();
		if (!$cli) throw new Exception('El cliente no existe', 404);
		$plazo = $this->plazoCredito($db, $idCliente);
		$pendientes = array();
		if (isset($this->columnas($db, array('ctacte_cliente'))['ctacte_cliente'])) {
			$filas = $this->q($db, "SELECT cc.id_ctacte_cliente, cc.id_venta, cc.monto, COALESCE(cc.pagado, 0) AS pagado,
					to_char(v.fecha_transaccion, 'DD/MM/YYYY HH24:MI') AS fecha, v.fecha_transaccion::date AS dia,
					COALESCE(td.nombre_tdocumento, 'Venta') AS documento, v.folio, v.total,
					CASE WHEN ? > 0 THEN to_char(v.fecha_transaccion::date + ?, 'DD/MM/YYYY') ELSE '' END AS vence,
					CASE WHEN ? > 0 THEN current_date - (v.fecha_transaccion::date + ?) ELSE -1 END AS dias_vencido
				FROM ctacte_cliente cc
				JOIN venta v ON v.id_venta = cc.id_venta
				LEFT JOIN tdocumento td ON td.id_tdocumento = v.id_tdocumento
				WHERE cc.id_cliente = ? AND cc.monto > COALESCE(cc.pagado, 0) AND COALESCE(v.mensaje_pedido, '') NOT LIKE 'ANULADA%'
				ORDER BY v.fecha_transaccion, cc.id_ctacte_cliente", array($plazo, $plazo, $plazo, $plazo, $idCliente))->result();
			$ids = array();
			foreach ($filas as $f) $ids[] = (int)$f->id_venta;
			$lineas = array();
			if ($ids) {
				foreach ($this->q($db, "SELECT dv.id_venta, COALESCE(p.nombre_producto, 'Producto ' || dv.id_producto)
						|| COALESCE(' + ' || pa.nombre_producto, '') AS nombre, dv.cantidad, dv.subtotal
					FROM detalle_venta dv
					LEFT JOIN producto p ON p.id_producto = dv.id_producto
					LEFT JOIN producto pa ON pa.id_producto = dv.id_agregado AND dv.id_agregado > 0
					WHERE dv.id_venta " . $this->sqlEn($ids) . " AND dv.cantidad > 0 ORDER BY dv.id_detalle_venta")->result() as $l) {
					$lineas[(int)$l->id_venta][] = array('nombre' => $l->nombre, 'cantidad' => (float)$l->cantidad, 'subtotal' => (int)round((float)$l->subtotal));
				}
			}
			foreach ($filas as $f) {
				$pendientes[] = array(
					'id_ctacte' => (int)$f->id_ctacte_cliente, 'id_venta' => (int)$f->id_venta, 'fecha' => $f->fecha,
					'documento' => $f->documento, 'folio' => (int)$f->folio, 'total' => (int)$f->total,
					'monto' => (int)$f->monto, 'pagado' => (int)$f->pagado, 'saldo' => (int)$f->monto - (int)$f->pagado,
					'vence' => $f->vence, 'dias_vencido' => (int)$f->dias_vencido,
					'lineas' => isset($lineas[(int)$f->id_venta]) ? $lineas[(int)$f->id_venta] : array(),
				);
			}
		}
		$pagos = array();
		if ($this->hayPagosCredito($db)) {
			foreach ($this->q($db, "SELECT pc.id_pago_credito, to_char(pc.fecha, 'DD/MM/YYYY HH24:MI') AS fecha, pc.monto,
					COALESCE(f.nombre_fpago, '') AS fpago, COALESCE(u.nombre_usuario, '') AS usuario,
					(SELECT string_agg(COALESCE(td.nombre_tdocumento, 'Venta') || ' N° ' || v.folio, ', ' ORDER BY v.id_venta)
						FROM detalle_pago_credito d JOIN venta v ON v.id_venta = d.id_venta
						LEFT JOIN tdocumento td ON td.id_tdocumento = v.id_tdocumento WHERE d.id_pago_credito = pc.id_pago_credito) AS ventas
				FROM pago_credito_cliente pc
				LEFT JOIN fpago f ON f.id_fpago = pc.id_fpago
				LEFT JOIN usuario u ON u.id_usuario = pc.id_usuario
				WHERE pc.id_cliente = ? ORDER BY pc.id_pago_credito DESC LIMIT 20", array($idCliente))->result() as $pg) {
				$pagos[] = array('id' => (int)$pg->id_pago_credito, 'fecha' => $pg->fecha, 'monto' => (int)$pg->monto,
					'fpago' => $pg->fpago, 'usuario' => $pg->usuario, 'ventas' => (string)$pg->ventas);
			}
		}
		$deuda = 0;
		$vencido = 0;
		foreach ($pendientes as $x) { $deuda += $x['saldo']; if ($x['dias_vencido'] > 0) $vencido += $x['saldo']; }
		return array(
			'cliente' => array('id_cliente' => (int)$cli->id_cliente, 'nombre' => $cli->nombre_cliente,
				'rut' => $cli->rut ? number_format((float)$cli->rut, 0, ',', '.') . '-' . $cli->dv : '',
				'cupo' => (int)$cli->cupo, 'plazo' => $plazo, 'deuda' => $deuda, 'vencido' => $vencido),
			'pendientes' => $pendientes,
			'pagos' => $pagos,
			'listo' => $this->hayPagosCredito($db),
		);
	}

	/**
	 * Pago de créditos del cliente en la caja abierta. $p = {id_cliente, id_fpago, ids: [id_ctacte...], paga_con, observacion}.
	 * Paga el saldo completo de cada venta elegida. En efectivo entra a la caja como ingreso (centro de costo de
	 * Configuraciones > Centro costo pago crédito); con otra forma de pago se rinde en esa forma de pago al cerrar.
	 */
	public function registrarPagoCredito($caja, $idUsuario, $p)
	{
		$db = $this->conexion();
		$this->exigirPagosCredito($db);
		$config = $this->configuracion($db);
		$idCliente = (int)(isset($p['id_cliente']) ? $p['id_cliente'] : 0);
		$idFpago = (int)(isset($p['id_fpago']) ? $p['id_fpago'] : 0);
		$ids = array_values(array_unique(array_filter(array_map('intval', isset($p['ids']) ? (array)$p['ids'] : array()))));
		if ($idCliente <= 0) throw new Exception('Elige el cliente', 400);
		if (!$ids) throw new Exception('Elige al menos una venta para pagar', 400);
		$fp = null;
		foreach ($this->catalogoFpago($db, $config) as $f) if ($f['id_fpago'] === $idFpago) $fp = $f;
		if (!$fp) throw new Exception('Elige la forma de pago', 400);
		if ($fp['credito'] || $fp['cortesia']) throw new Exception('Un crédito no se paga con ' . $fp['nombre'], 400);
		$observacion = mb_substr(trim(isset($p['observacion']) ? (string)$p['observacion'] : ''), 0, 200);
		$cols = $this->columnas($db, array('pago_credito_cliente', 'detalle_pago_credito', 'detalle_caja', 'caja'));

		$db->trans_begin();
		try {
			$c = $this->q($db, 'SELECT cerrada FROM caja WHERE id_caja = ? FOR UPDATE', array((int)$caja->id_caja))->row();
			if (!$c || $c->cerrada === 't' || $c->cerrada === true) throw new Exception('La caja ya está cerrada', 409);
			// Las deudas quedan bloqueadas: dos cajas no pueden cobrar la misma venta a la vez
			$filas = $this->q($db, 'SELECT cc.id_ctacte_cliente, cc.id_venta, cc.monto, COALESCE(cc.pagado, 0) AS pagado, v.folio,
					COALESCE(v.mensaje_pedido, \'\') AS mensaje_pedido
				FROM ctacte_cliente cc JOIN venta v ON v.id_venta = cc.id_venta
				WHERE cc.id_cliente = ? AND cc.id_ctacte_cliente ' . $this->sqlEn($ids) . ' ORDER BY cc.id_ctacte_cliente FOR UPDATE OF cc',
				array($idCliente))->result();
			if (count($filas) !== count($ids)) throw new Exception('Alguna venta elegida no es un crédito de este cliente', 409);
			$total = 0;
			foreach ($filas as $f) {
				// 14-11-2026: una venta anulada no tiene deuda (el listado y el cupo ya la excluían, pero se podía pagar)
				if (strpos((string)$f->mensaje_pedido, 'ANULADA') === 0) {
					throw new Exception('La venta folio ' . (int)$f->folio . ' está anulada: no tiene deuda que pagar. Vuelve a cargar los créditos.', 409);
				}
				$saldo = (int)$f->monto - (int)$f->pagado;
				if ($saldo <= 0) throw new Exception('La venta folio ' . (int)$f->folio . ' ya estaba pagada. Vuelve a cargar los créditos.', 409);
				$total += $saldo;
			}
			$pagaCon = $fp['efectivo'] ? max($total, (int)(isset($p['paga_con']) ? $p['paga_con'] : 0)) : $total;
			$vuelto = $pagaCon - $total;
			$cli = $this->q($db, 'SELECT nombre_cliente FROM cliente WHERE id_cliente = ?', array($idCliente))->row();
			$ahora = date('Y-m-d H:i:s');

			// En efectivo: ingreso de caja (suma al efectivo esperado del cierre)
			$idDetalleCaja = 0;
			if ($fp['efectivo']) {
				$texto = 'PAGO CREDITO ' . ($cli ? $cli->nombre_cliente : '');
				$idDetalleCaja = $this->insertar($db, $cols, 'detalle_caja', array(
					'id_caja' => (int)$caja->id_caja, 'ingreso' => $total, 'egreso' => 0,
					'observacion' => mb_substr($texto, 0, 100), 'descripcion_corta' => mb_substr($texto, 0, 50),
					'id_ccosto' => (int)$this->opcionNumero($db, 'id_pagocreditodirecto'),
					'fecha_movimiento' => $ahora, 'vigente' => true,
				), 'id_detalle_caja');
				$this->q($db, 'UPDATE caja SET ingresos = (SELECT COALESCE(sum(ingreso), 0) FROM detalle_caja WHERE id_caja = ?) WHERE id_caja = ?',
					array((int)$caja->id_caja, (int)$caja->id_caja));
			}
			$idPago = $this->insertar($db, $cols, 'pago_credito_cliente', array(
				'id_cliente' => $idCliente, 'id_caja' => (int)$caja->id_caja, 'id_usuario' => (int)$idUsuario,
				'id_fpago' => $idFpago, 'monto' => $total, 'paga_con' => $pagaCon, 'vuelto' => $vuelto,
				'fecha' => $ahora, 'observacion' => $observacion, 'id_detalle_caja' => $idDetalleCaja ?: null,
			), 'id_pago_credito');
			foreach ($filas as $f) {
				$saldo = (int)$f->monto - (int)$f->pagado;
				$this->insertar($db, $cols, 'detalle_pago_credito', array('id_pago_credito' => $idPago,
					'id_ctacte_cliente' => (int)$f->id_ctacte_cliente, 'id_venta' => (int)$f->id_venta, 'monto' => $saldo), null);
				$this->q($db, 'UPDATE ctacte_cliente SET pagado = monto WHERE id_ctacte_cliente = ?', array((int)$f->id_ctacte_cliente));
			}
			$db->trans_commit();
		} catch (Throwable $e) {   // 19-11-2026: también errores de PHP deshacen la transacción
			$db->trans_rollback();
			throw $e;
		}
		return $this->comprobantePagoCredito($idPago);
	}

	/** Datos del comprobante de un pago de crédito (para imprimirlo). */
	public function comprobantePagoCredito($idPago)
	{
		$db = $this->conexion();
		$this->exigirPagosCredito($db);
		$pg = $this->q($db, "SELECT pc.*, to_char(pc.fecha, 'DD/MM/YYYY HH24:MI') AS fecha_texto, c.nombre_cliente, c.rut, c.dv,
				COALESCE(f.nombre_fpago, '') AS fpago, COALESCE(u.nombre_usuario, '') AS usuario
			FROM pago_credito_cliente pc
			JOIN cliente c ON c.id_cliente = pc.id_cliente
			LEFT JOIN fpago f ON f.id_fpago = pc.id_fpago
			LEFT JOIN usuario u ON u.id_usuario = pc.id_usuario
			WHERE pc.id_pago_credito = ?", array((int)$idPago))->row();
		if (!$pg) throw new Exception('El pago no existe', 404);
		$det = $this->q($db, "SELECT d.monto, v.folio, to_char(v.fecha_transaccion, 'DD/MM/YYYY') AS fecha, COALESCE(td.nombre_tdocumento, 'Venta') AS documento
			FROM detalle_pago_credito d JOIN venta v ON v.id_venta = d.id_venta
			LEFT JOIN tdocumento td ON td.id_tdocumento = v.id_tdocumento
			WHERE d.id_pago_credito = ? ORDER BY d.id_detalle_pago_credito", array((int)$idPago))->result_array();
		$deuda = (int)$this->q($db, "SELECT COALESCE(sum(cc.monto - COALESCE(cc.pagado, 0)), 0) AS d FROM ctacte_cliente cc
			JOIN venta v ON v.id_venta = cc.id_venta WHERE cc.id_cliente = ? AND COALESCE(v.mensaje_pedido, '') NOT LIKE 'ANULADA%'",
			array((int)$pg->id_cliente))->row()->d;
		return array(
			'id_pago' => (int)$pg->id_pago_credito, 'fecha' => $pg->fecha_texto, 'cliente' => $pg->nombre_cliente,
			'rut' => $pg->rut ? number_format((float)$pg->rut, 0, ',', '.') . '-' . $pg->dv : '',
			'fpago' => $pg->fpago, 'usuario' => $pg->usuario, 'monto' => (int)$pg->monto, 'paga_con' => (int)$pg->paga_con,
			'vuelto' => (int)$pg->vuelto, 'id_caja' => (int)$pg->id_caja, 'observacion' => (string)$pg->observacion,
			'detalle' => $det, 'deuda_restante' => $deuda,
			'empresa' => $this->q($db, 'SELECT * FROM empresa ORDER BY id_empresa LIMIT 1')->row(),
		);
	}

	/** Una opción numérica de configuracion (0 si la columna no existe). */
	private function opcionNumero($db, $columna)
	{
		if (!isset($this->columnas($db, array('configuracion'))['configuracion'][$columna])) return 0;
		$r = $this->q($db, 'SELECT COALESCE(' . $columna . ', 0) AS v FROM configuracion LIMIT 1')->row();
		return $r ? (int)$r->v : 0;
	}

	/** Pagos de crédito recibidos en la caja con formas de pago que no son efectivo: id_fpago => monto. */
	private function pagosCreditoNoEfectivo($db, $idCaja, $config)
	{
		$res = array();
		if (!$this->hayPagosCredito($db)) return $res;
		foreach ($this->q($db, 'SELECT pc.id_fpago, f.nombre_fpago, sum(pc.monto) AS monto FROM pago_credito_cliente pc
			JOIN fpago f ON f.id_fpago = pc.id_fpago WHERE pc.id_caja = ? GROUP BY 1, 2', array((int)$idCaja))->result() as $r) {
			if ($this->esEfectivo($config, $r->id_fpago, $r->nombre_fpago)) continue;
			$res[(int)$r->id_fpago] = array('nombre' => $r->nombre_fpago, 'monto' => (int)$r->monto);
		}
		return $res;
	}

	/** Total de pagos de crédito recibidos en la caja (todas las formas de pago). */
	private function totalPagosCredito($db, $idCaja)
	{
		if (!$this->hayPagosCredito($db)) return 0;
		return (int)$this->q($db, 'SELECT COALESCE(sum(monto), 0) AS m FROM pago_credito_cliente WHERE id_caja = ?', array((int)$idCaja))->row()->m;
	}

	public function registrarVenta($caja, $idUsuario, $idRol, $p)
	{
		$db = $this->conexion();
		$config = $this->configuracion($db);
		$cols = $this->columnas($db, array('venta', 'detalle_venta', 'pago_venta', 'consolidado_ventas', 'tdocumento', 'producto', 'ctacte_cliente'));
		$factor = 1 + $config['por_iva'] / 100;
		$idLprecio = !empty($p['id_lprecio']) ? (int)$p['id_lprecio'] : $config['id_lprecio_publico'];

		// --- Documento y cliente
		$idTdoc = (int)(isset($p['id_tdocumento']) ? $p['id_tdocumento'] : 0);
		$doc = null;
		foreach ($this->tiposDocumento($db) as $d) {
			if ($d['id_tdocumento'] === $idTdoc) $doc = $d;
		}
		if (!$doc) throw new Exception('Ese documento no se puede usar en la caja: revisa que la facturación electrónica esté habilitada y el documento asignado en Configuraciones', 400);
		// Electrónicos: el folio lo asigna Cloud Apps al emitir (tdocumento.folio no los refleja)
		if (!$doc['habilitado']) {
			throw new Exception($doc['nombre'] . ' no se puede emitir: ' . $doc['motivo'] . ' (Configuraciones > Facturación electrónica). Usa el comprobante interno.', 409);
		}

		$idCliente = (int)(isset($p['id_cliente']) ? $p['id_cliente'] : 0);
		$cliente = $idCliente ? $this->q($db, 'SELECT id_cliente, nombre_cliente, rut, dv, giro, direccion, COALESCE(id_comuna, 0) AS id_comuna
			FROM cliente WHERE id_cliente = ?', array($idCliente))->row() : null;
		if ($idCliente && !$cliente) throw new Exception('El cliente no existe', 400);
		$esPublico = !$cliente || (int)$cliente->id_cliente === $config['id_cliente_publico'];
		// Factura: giro, dirección, comuna y solicitante elegidos al cobrar (venta_receptor). Sin ellos, los de la ficha, como antes
		$fac = null;
		if ($doc['es_factura'] && !$esPublico && !empty($p['factura']) && is_array($p['factura']) && $this->hayReceptor($db)) {
			$fac = $this->datosFactura($db, $cliente, $p['factura']);
			$cliente->giro = $fac['giro'];
			$cliente->direccion = $fac['direccion'];
			$cliente->id_comuna = $fac['id_comuna'];
		}
		if ($doc['electronico'] && $doc['es_factura']) {
			$faltan = array();
			if ($esPublico || !(int)$cliente->rut) $faltan[] = 'RUT';
			if (!$esPublico && trim((string)$cliente->giro) === '') $faltan[] = 'giro';
			if (!$esPublico && trim((string)$cliente->direccion) === '') $faltan[] = 'dirección';
			if (!$esPublico && !(int)$cliente->id_comuna) $faltan[] = 'comuna';
			if ($faltan) throw new Exception('Para ' . $doc['nombre'] . ' el cliente necesita: ' . implode(', ', $faltan), 400);
		}
		if ($doc['requiere_cliente'] && $esPublico) {
			throw new Exception('El documento ' . $doc['nombre'] . ' requiere seleccionar un cliente', 400);
		}
		if ($doc['es_factura'] && (!$cliente || !(int)$cliente->rut || trim((string)$cliente->giro) === '' || trim((string)$cliente->direccion) === '')) {
			throw new Exception('Para emitir ' . $doc['nombre'] . ' el cliente debe tener RUT, giro y dirección', 400);
		}
		if (!$cliente) $idCliente = $config['id_cliente_publico'];

		// --- Delivery (18-11-2026): cobrar uno pendiente ($p['id_delivery']: se cobra lo guardado, no lo que mande la
		// pantalla) o uno nuevo cobrado al tomarlo ($p['delivery']: los datos de entrega)
		$idMesa = (int)(isset($p['id_mesa']) ? $p['id_mesa'] : 0);
		$pedidoDelivery = null;
		$entregaDelivery = null;
		if (!empty($p['id_delivery'])) {
			$pedidoDelivery = $this->deliveryParaCobrar((int)$p['id_delivery']);
			$p['detalle'] = $pedidoDelivery['detalle'];
			if ($pedidoDelivery['id_lprecio']) $idLprecio = $pedidoDelivery['id_lprecio'];
		} elseif (!empty($p['delivery']) && is_array($p['delivery'])) {
			if (empty($config['delivery']) || !$this->hayDelivery($db)) throw new Exception('El delivery no está habilitado (Configuraciones > Delivery)', 409);
			$entregaDelivery = $this->datosEntrega($db, $p['delivery']);
			$p['detalle'] = $this->detalleDelivery(isset($p['detalle']) ? $p['detalle'] : null);
		}
		$esDelivery = $pedidoDelivery || $entregaDelivery;
		if ($esDelivery && $idMesa) throw new Exception('Un delivery no va en una mesa', 400);

		// --- Detalle: precios del servidor; en una mesa, lo ya pedido (con el precio al que se pidió)
		if (!$idMesa && !$esDelivery && $this->modoCaja($db) === 'mesas') {
			throw new Exception('Este local vende solo por mesas: abre una mesa para cobrar (deja una libre para la venta rápida)', 409);
		}
		$cotMesa = null;
		$cotCobro = null;
		$parcial = null;
		if ($idMesa) {
			$cotMesa = $this->cotizacionDeMesa($db, $idMesa);
			if (!$cotMesa) throw new Exception('La mesa ya no tiene un pedido abierto', 409);
			if ((int)$cotMesa->id_lprecio) $idLprecio = (int)$cotMesa->id_lprecio;
			$lineas = $this->lineasDeCotizacion($db, (int)$cotMesa->id_cotizacion);
			if (!$lineas) throw new Exception('La mesa no tiene productos', 400);
			$huellaMesa = $this->huellaLineas($lineas);
			// Cobro de una parte de la mesa: [{id_detalle, cantidad}]; lo demás sigue en la mesa
			$parcial = isset($p['parcial']) && is_array($p['parcial']) && $p['parcial'] ? $this->seleccionDeLineas($lineas, $p['parcial']) : null;
			if ($parcial) $lineas = $parcial;
			$subtotal = (int)array_sum(array_column($lineas, 'bruto'));
			$notas = array();
		} else {
			list($lineas, $subtotal, $notas, $comandaVenta) = $this->armarLineas($db, $config, $cols, isset($p['detalle']) ? $p['detalle'] : null, $idLprecio);
		}
		// 18-11-2026: el envío del delivery es una línea más de la boleta (el guardado en el pedido, o el de Configuraciones)
		$subtotalProductos = $subtotal;
		$costoEnvio = 0;
		if ($esDelivery) {
			$envio = $this->lineaEnvio($db, $config, $pedidoDelivery ? $pedidoDelivery['costo_envio'] : null);
			if ($envio) {
				$lineas[] = $envio;
				$subtotal += $envio['bruto'];
				$costoEnvio = (int)$envio['bruto'];
			}
			// Pendiente: la comanda ya salió al tomar el pedido
			if ($pedidoDelivery) $comandaVenta = array();
		}

		$descuento = max(0, (int)(isset($p['descuento']) ? $p['descuento'] : 0));
		if ($descuento > $subtotal) throw new Exception('El descuento no puede superar el subtotal', 400);
		$porDescuento = $subtotal > 0 ? round($descuento * 100 / $subtotal, 2) : 0;
		// v1.24.1: tope en pesos = el máximo % del subtotal redondeado como la Caja (antes un 10% exacto de $1.005 = $101
		// daba 10,05% y se rechazaba)
		if ($config['por_maximo_descuento'] > 0 && $descuento > (int)round($subtotal * $config['por_maximo_descuento'] / 100)) {
			throw new Exception('El descuento máximo permitido es ' . $config['por_maximo_descuento'] . '%', 400);
		}
		// El trigger f_adddescuentoproducto divide por el subtotal de la venta
		if ($subtotal <= 0) throw new Exception('La venta debe tener un monto mayor a cero', 400);
		// Boleta exenta (41) y factura exenta (34): todo el documento es exento, aunque los productos sean afectos
		if (in_array($doc['cod_sii'], array(34, 41))) {
			foreach ($lineas as $i => $l) $lineas[$i]['exento'] = true;
		}
		$lineas = $this->conIla($db, $lineas);   // v1.20.0: ILA de cada producto
		$m = $this->montos($lineas, $descuento, $doc['es_factura'], $config['por_iva']);
		$total = $m['total'];
		$neto = $m['neto'];
		$iva = $m['iva'];
		$ila = $m['ila'];
		$exento = $m['exento'];
		// Boleta (39) y factura (33) afectas necesitan monto neto: solo exentos va en boleta / factura exenta
		if ($doc['electronico'] && in_array($doc['cod_sii'], array(33, 39)) && $exento > 0 && $neto < 5) {
			throw new Exception('Una ' . mb_strtolower($doc['nombre']) . ' debe incluir al menos un producto afecto a IVA. '
				. 'Para vender solo productos exentos usa ' . ($doc['es_factura'] ? 'factura exenta' : 'boleta exenta') . ' o comprobante interno.', 400);
		}

		// --- Pagos
		$fpagos = array();
		foreach ($this->catalogoFpago($db, $config) as $f) $fpagos[$f['id_fpago']] = $f;
		$pagos = array();
		$suma = 0;
		$cortesias = 0;
		$conCredito = false;
		$montoCredito = 0;
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
				$montoCredito += $monto;
			}
			if ($fpagos[$idF]['efectivo']) $idxEfectivo = count($pagos);
			$pagos[] = array('id_fpago' => $idF, 'monto' => $monto, 'vuelto' => 0, 'propina' => 0);
			$suma += $monto;
		}
		if (!$pagos) throw new Exception('Ingresa al menos un pago', 400);
		if ($cortesias && $cortesias !== count($pagos)) throw new Exception('Una cortesía no se puede mezclar con otras formas de pago', 400);
		$esCortesia = $cortesias > 0;

		$propina = max(0, (int)(isset($p['propina']) ? $p['propina'] : 0));
		if ($propina > 0 && !$config['propina'] && !$idMesa) throw new Exception('La propina en venta directa no está habilitada en Configuraciones', 400);
		if ($propina > 0 && !$config['propina_mesas'] && $idMesa) throw new Exception('La propina en mesas no está habilitada en Configuraciones', 400);   // v1.23.0
		if ($propina > 0 && $esCortesia) throw new Exception('Una cortesía no lleva propina', 400);
		$propinaMax = (int)floor($total * self::PROPINA_MAX_PCT / 100);
		if ($propina > $propinaMax) {
			throw new Exception('La propina no puede superar el ' . self::PROPINA_MAX_PCT . '% del total ($' . number_format($propinaMax, 0, ',', '.') . ')', 400);
		}

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

		// Lista de precios distinta de la pública: autorizada con la clave al cambiarla (en una mesa vale la del pedido)
		if (!$idMesa) $this->exigirLista($db, $config, $idUsuario, $idRol, $idLprecio, $idCliente);

		// Configuraciones > Descuentos > Requerir cliente: un descuento solo a un cliente identificado
		if ($descuento > 0 && $config['descuento_cliente'] && $esPublico) {
			throw new Exception('Para hacer un descuento debes elegir el cliente de la venta (Configuraciones > Descuentos > Requerir cliente)', 400);
		}

		// --- Autorizaciones de administrador (Configuraciones)
		$requiere = array();
		// 18-11-2026: el descuento guardado (y autorizado) al tomar un delivery que se cobra al entregar no se vuelve a pedir
		$descuentoYaAutorizado = $pedidoDelivery && $descuento === (int)$pedidoDelivery['descuento'];
		if ($descuento > 0 && $config['admin_descuento'] && !$descuentoYaAutorizado) $requiere[] = 'descuento';
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
		// Venta directa: nombre de quien retira el pedido (opcional, 12-11-2026); va en la comanda y en el documento
		$retira = $idMesa ? '' : mb_substr(trim(preg_replace('/[\x00-\x1F\x7F\s]+/u', ' ', (string)(isset($p['retira']) ? $p['retira'] : ''))), 0, 60);
		$ahora = date('Y-m-d H:i:s');
		$dia = date('Y-m-d');

		$db->trans_begin();
		try {
			$this->q($db, 'SET LOCAL TIME ZONE ' . $db->escape(self::TZ));
			// La caja pudo cerrarse en otra pestaña
			$c = $this->q($db, 'SELECT cerrada FROM caja WHERE id_caja = ? FOR UPDATE', array((int)$caja->id_caja))->row();
			if (!$c || $c->cerrada === 't' || $c->cerrada === true) throw new Exception('La caja ya está cerrada', 409);
			$tokenVenta = $this->reservarTokenVenta($db, $p, (int)$caja->id_caja, (int)$idUsuario);
			if ($montoCredito > 0) $this->validarCupoCredito($db, $idCliente, $montoCredito);
			// 14-11-2026: vencimiento de una venta a crédito = día de la venta + plazo del cliente (cliente.plazo_credito,
			// 15 a 90 días; 0 = sin plazo). Antes se grababa el mismo día de la venta y quedaba "vencida" desde el primer día
			// para quien leyera venta.fecha_vencimiento (el POS de escritorio, informes). Sin crédito, el día de la venta.
			$plazoVenta = $montoCredito > 0 ? $this->plazoCredito($db, $idCliente) : 0;
			$diaVence = $plazoVenta > 0 ? date('Y-m-d', strtotime($dia . ' +' . $plazoVenta . ' days')) : $dia;

			if ($idMesa) {
				// Lo de la mesa ya se consumió: no se revisa stock, pero sí que el pedido siga igual al que se cobra
				// Bloqueada por un garzón en el POS, o el pedido cambió de mesa mientras se cobraba: no se cobra
				$this->validarMesaUsable($this->bloquearMesa($db, $idMesa), $db);
				$actual = $this->cotizacionDeMesa($db, $idMesa);
				if (!$actual || (int)$actual->id_cotizacion !== (int)$cotMesa->id_cotizacion) throw new Exception('El pedido de la mesa cambió. Vuelve a abrir la mesa antes de cobrar.', 409);
				$co = $this->q($db, 'SELECT pagado FROM cotizacion WHERE id_cotizacion = ? FOR UPDATE', array((int)$cotMesa->id_cotizacion))->row();
				if (!$co || $co->pagado === 't' || $co->pagado === true) throw new Exception('Esta mesa ya fue pagada', 409);
				// Se compara línea por línea: cambiar un producto por otro del mismo precio también es un cambio
				if ($this->huellaLineas($this->lineasDeCotizacion($db, (int)$cotMesa->id_cotizacion)) !== $huellaMesa) {
					throw new Exception('El pedido de la mesa cambió. Vuelve a abrir la mesa antes de cobrar.', 409);
				}
				// Lo que se cobra pasa a un pedido hijo (id_divpedido = pedido de la mesa), como la cuenta dividida del POS
				$cotCobro = $parcial ? $this->dividirPedido($db, $cotMesa, $idMesa, $parcial) : $cotMesa;
			} else {
				$this->validarStock($db, $lineas);
			}

			$folio = $doc['electronico'] ? 0 : $this->siguienteFolio($db, $idTdoc, $doc['nombre']);

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
				'ila' => $ila,
				'total' => $total,
				'id_caja' => (int)$caja->id_caja,
				'folio' => $folio,
				'id_usuario_garzon' => $cotMesa ? (int)$cotMesa->id_usuario : (int)$idUsuario,
				'id_mesa' => $idMesa,
				'folio_cotizacion' => $cotMesa ? (int)$cotCobro->id_cotizacion : 0,
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
				'nombre_retira' => $retira === '' ? null : $retira,   // migración 20261112120000 (sin la columna no se graba)
				'es_cortesia' => $esCortesia,
				'venta_directa' => !$idMesa,
				'fecha_vencimiento' => $diaVence,
				'id_canal_venta' => $this->primerId($db, 'canal_venta', 'id_canal_venta', 1),
				'monto_exento' => $exento,
				'monto_afecto' => $esCortesia ? 0 : $total - $exento,
				'monto_cortesia' => $esCortesia ? $total : 0,
				'vigente' => true,
				'esventa' => true,
				'subtotal_bruto' => $subtotal,
				'subtotal_neto' => $neto,
				'subtotal_neto_sin_descuento' => array_sum(array_column($m['lineas'], 'neto')),
				'monto_descuento_global' => $descuento,
				'porcentaje_descuento_global' => $porDescuento,
				'descuento_global_prorrateado_bruto' => $descuento,
				'descuento_global_prorrateado_neto' => $descuento / $factor,
				'subtotal_neto_con_descuento' => $neto,
				'monto_iva' => $iva,
				'total_bruto_con_descuento' => $total,
				'folio_dte' => 0,
			), 'id_venta');
			if ($tokenVenta !== '') $this->q($db, 'UPDATE caja_venta_token SET id_venta = ? WHERE token = ?', array((int)$idVenta, $tokenVenta));

			// El descuento global se reparte entre las líneas según su monto (montos())
			foreach ($lineas as $i => $l) {
				$ml = $m['lineas'][$i];
				$descLinea = $ml['descuento'];
				$conDesc = $ml['con_descuento'];
				$netoLinea = $ml['neto'];
				$netoConDesc = $ml['neto_con_descuento'];
				$this->insertar($db, $cols, 'detalle_venta', array(
					'id_venta' => $idVenta,
					'id_producto' => $l['id_producto'],
					'nombre_producto' => mb_substr($l['nombre'], 0, 200),
					'descripcion_general' => $l['nota'] !== '' ? $l['nota'] : null,
					'cantidad' => $l['cantidad'],
					'bruto_un' => $l['unitario'],
					'bruto_un_lista' => $l['unitario'],
					'precio_unitario_bruto' => $l['unitario'],
					// v1.20.0: factor de la línea = 1 + (IVA + ILA del producto) / 100
					'precio_unitario_neto' => $l['unitario'] / $ml['factor'],
					'neto_un' => $l['unitario'] / $ml['factor'],
					'neto_unitario' => $l['unitario'] / $ml['factor'],
					'subtotal' => $l['bruto'],
					'descuento' => 0,
					'descuento_porcentual' => 0,
					'total_bruto' => $l['bruto'],
					'neto' => $netoLinea,
					'iva' => (int)round($l['bruto'] - $netoLinea - $ml['ila']),
					'ila' => (int)round($ml['ila']),
					'poriva' => $ml['exento'] ? 0 : $config['por_iva'],
					'porila' => $ml['porila'],
					'espack' => false,
					'es_merma' => false,
					'esmerma' => false,
					'id_agregado' => $l['id_agregado'],
					// v1.24.0: menú y productos elegidos del menú (mismas columnas que usa el POS de escritorio)
					'es_menu' => !empty($l['es_menu']), 'es_detalle_menu' => !empty($l['es_detalle_menu']),
					'id_menu' => isset($l['id_menu']) ? (int)$l['id_menu'] : 0,
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
					'descuento_global_prorrateado_neto' => $descLinea / $ml['factor'],
					'subtotal_neto_con_descuento' => $netoConDesc,
					'monto_iva' => $conDesc - $netoConDesc - $ml['ila_con_descuento'],
					'monto_ila' => $ml['ila_con_descuento'],
					'total_bruto_con_descuento' => $conDesc,
					'monto_exento' => $ml['exento'] ? $conDesc : 0,
				// Bodega de la caja (Control de cajas), si detalle_venta tiene la columna; sin bodega en la caja no se informa (como antes)
				) + ((int)$config['id_bodega_caja'] > 0 ? array('id_bodega' => (int)$config['id_bodega_caja']) : array()), null);
			}

			if (!$this->tieneTriggerStock($db)) {
				$this->descontarStock($db, $cols, $idVenta, (int)$config['id_bodega_caja']);
			}

			foreach ($pagos as $pg) {
				$this->insertar($db, $cols, 'pago_venta', array(
					'id_cliente' => $idCliente,
					'id_venta' => $idVenta,
					'id_fpago' => $pg['id_fpago'],
					// Solo el pago a crédito vence con el plazo del cliente; los demás quedan pagados ese día
					'fecha_vencimiento' => $fpagos[$pg['id_fpago']]['credito'] ? $diaVence : $dia,
					'numero_documento' => '',
					'valor_compra' => $total,
					// paga_con es lo que entregó el cliente; el trigger de crédito directo lo usa como deuda
					'paga_con' => $pg['monto'],
					'pagado' => 0,
					// Como el POS de escritorio: monto_pagado incluye la propina, que además va en su columna
					'propina' => $pg['propina'],
					'subtotal' => $pg['monto'] - $pg['vuelto'],
					'monto_pagado' => $pg['monto'] - $pg['vuelto'],
					'vuelto' => $pg['vuelto'],
					'fecha_movimiento' => $ahora,
					'fecha_pago' => $dia,
				), null);
				// La deuda del fiado la anota el trigger f_creditodirecto, pero solo para la forma de pago configurada.
				// Sin configurar (crédito reconocido por su nombre) se anota aquí, para que el cupo del cliente funcione.
				if ($fpagos[$pg['id_fpago']]['credito'] && !$config['id_fpago_credito'] && isset($cols['ctacte_cliente'])) {
					$this->q($db, 'INSERT INTO ctacte_cliente (id_cliente, id_venta, monto, pagado) VALUES (?, ?, ?, 0)',
						array($idCliente, $idVenta, $pg['monto']));
				}
			}

			if ($idMesa) {
				// total queda antes del descuento: los informes de garzones calculan total - descuento
				$this->q($db, 'UPDATE cotizacion SET pagado = true, id_venta = ?, id_caja = ?, subtotal = ?, total = ?, descuento = ?,
						neto = ?, iva = ?, por_descuento = ?, monto_descuento = ?, propina_sugerida = ? WHERE id_cotizacion = ?',
					array($idVenta, (int)$caja->id_caja, $subtotal, $subtotal, $descuento, $neto, $iva, $porDescuento, $descuento, $propina, (int)$cotCobro->id_cotizacion));
				$mesaLiberada = true;
				if ($parcial) {
					// Quedan productos: la mesa sigue abierta; si no, el pedido original se cierra vacío
					$mesaLiberada = !$this->recalcularCotizacion($db, (int)$cotMesa->id_cotizacion) && !$this->lineasDeCotizacion($db, (int)$cotMesa->id_cotizacion);
					if ($mesaLiberada) $this->q($db, 'UPDATE cotizacion SET pagado = true, id_venta = ?, id_caja = ? WHERE id_cotizacion = ?', array($idVenta, (int)$caja->id_caja, (int)$cotMesa->id_cotizacion));
				}
				if ($mesaLiberada) $this->desocuparMesa($db, $idMesa);
			}
			if ($fac) $this->guardarReceptor($db, $idVenta, (int)$cliente->id_cliente, $fac, $ahora);

			// 18-11-2026: delivery. Pendiente: queda cobrado con esta venta (si otra pantalla lo cobró o anuló, no se vende
			// dos veces). Nuevo: se registra ya cobrado.
			$idDelivery = null;
			if ($pedidoDelivery) {
				$this->q($db, "UPDATE delivery_pedido SET id_venta = ?, total = ?, fecha_estado = now()
					WHERE id_delivery = ? AND id_venta IS NULL AND estado <> 'anulado'",
					array($idVenta, $subtotal - $descuento, $pedidoDelivery['id_delivery']));
				if ($db->affected_rows() !== 1) throw new Exception('Ese delivery ya se cobró o se anuló en otra pantalla', 409);
				$this->guardarDescuentoDelivery($db, $pedidoDelivery['id_delivery'], $descuento);
				$idDelivery = $pedidoDelivery['id_delivery'];
			} elseif ($entregaDelivery) {
				$idDelivery = $this->insertarDelivery($db, $entregaDelivery, array('id_caja' => (int)$caja->id_caja, 'id_usuario' => (int)$idUsuario,
					'id_cliente' => $esPublico ? 0 : $idCliente, 'id_lprecio' => $idLprecio, 'detalle' => $p['detalle'],
					'subtotal' => $subtotalProductos, 'costo_envio' => $costoEnvio, 'total' => $subtotal - $descuento,
					'cobrar_al_entregar' => false, 'id_venta' => $idVenta));
				$this->guardarDescuentoDelivery($db, $idDelivery, $descuento);
			}

			$db->trans_commit();
		} catch (Throwable $e) {   // 19-11-2026: también errores de PHP deshacen la transacción
			$db->trans_rollback();
			throw $e;
		}

		// Lo nuevo de la factura (dirección, giro, solicitante) queda en la ficha del cliente. Fuera de la transacción:
		// si falla, la venta ya está hecha y solo se registra en el log
		if ($fac && $fac['guardar_ficha']) {
			try {
				$this->load->model('Mclientefactura');
				$this->Mclientefactura->recordar((int)$cliente->id_cliente, $fac);
			} catch (Throwable $e) {
				log_message('error', 'Mcaja: no se guardaron en la ficha los datos de la factura de la venta ' . (int)$idVenta . ': ' . $e->getMessage());
			}
		}

		// La emisión va fuera de la transacción: si Cloud Apps falla, la venta queda pendiente de envío
		$dte = null;
		if ($doc['electronico']) {
			$dte = $this->emitirDte($db, $idVenta);
			if ($dte['ok']) $folio = $dte['folio'];
		}
		return array('id_venta' => $idVenta, 'folio' => $folio, 'total' => $total, 'propina' => $propina,
			'vuelto' => $vuelto, 'documento' => $doc['nombre'], 'dte' => $dte,
			'mesa_liberada' => $idMesa ? !empty($mesaLiberada) : null,
			'comanda' => $idMesa ? array() : (isset($comandaVenta) ? $comandaVenta : array()),
			'observacion' => mb_substr(trim(isset($p['observacion']) ? (string)$p['observacion'] : ''), 0, 200),
			'retira' => $retira,
			'id_delivery' => $idDelivery,
			'delivery_nombre' => $entregaDelivery ? $entregaDelivery['nombre'] : ($pedidoDelivery ? $pedidoDelivery['nombre'] : ''),
			// 18-11-2026: dirección, referencia y teléfono para la comanda del delivery (para el repartidor)
			'delivery_entrega' => $entregaDelivery ? $this->entregaComanda($entregaDelivery) : null);
	}

	/** Valida los productos pedidos y arma las líneas con los precios del servidor. Devuelve [lineas, subtotal, notas]. */
	private function armarLineas($db, $config, $cols, $items, $idLprecio)
	{
		$items = is_array($items) ? $items : array();
		if (!$items) throw new Exception('La venta no tiene productos', 400);
		$ids = array();
		foreach ($items as $it) $ids[] = (int)$it['id_producto'];
		$selVss = isset($cols['producto']['venta_segun_stock']) ? '(p.venta_segun_stock IS TRUE)' : 'false';
		$selFraccion = isset($cols['producto']['permite_porcion']) ? '(p.permite_porcion IS TRUE)' : 'false';
		// 14-11-2026: sin la columna, como el catálogo: un producto sin precio no se vende
		$selSinPrecio = isset($cols['producto']['permitir_venta_sin_precio']) ? '(p.permitir_venta_sin_precio IS TRUE)' : 'false';
		$productos = array();
		$q = "SELECT p.id_producto, p.nombre_producto, COALESCE(p.id_receta, 0) AS id_receta, COALESCE(p.id_bodega, 0) AS id_bodega,
				COALESCE(NULLIF(dl.total, 0), p.precio_bruto, 0) AS precio, $selVss AS segun_stock, $selFraccion AS fraccion,
				$selSinPrecio AS sin_precio_ok,
				" . $this->sqlExento($db, 'p') . " AS exento
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
		$comanda = array();   // lo que sale en cocina/barra: armado aquí con los nombres de la base, no los del navegador
		$subtotal = 0;
		$menus = $this->menus($db);   // v1.24.0
		$sushis = $this->sushis($db);   // v1.25.0
		foreach ($items as $it) {
			$id = (int)$it['id_producto'];
			$cantidad = round((float)$it['cantidad'], 3);
			if (!isset($productos[$id])) throw new Exception('El producto ' . $id . ' no existe o no está vigente', 400);
			$prod = $productos[$id];
			if ($cantidad <= 0) throw new Exception('Cantidad inválida para ' . $prod->nombre_producto, 400);
			// 14-11-2026 (QA): el catálogo de la Caja ya escondía los productos sin precio, pero el servidor los vendía a $0
			// si llegaban igual (venta armada a mano, o el precio se borró con la pantalla abierta). Solo pasan los marcados
			// "permitir venta sin precio" en el producto.
			if ((float)$prod->precio <= 0 && !($prod->sin_precio_ok === 't' || $prod->sin_precio_ok === true)) {
				throw new Exception($prod->nombre_producto . ' no tiene precio en esta lista de precios: asígnale uno en Lista de Precios antes de venderlo', 400);
			}
			// Solo los productos fraccionables (por kilo, porción) aceptan decimales: si no, una cantidad como 0,001
			// dejaría el producto en $0 sin pasar por el descuento ni su autorización
			$fraccion = $prod->fraccion === 't' || $prod->fraccion === true;
			if (!$fraccion && abs($cantidad - round($cantidad)) > 0.0001) {
				throw new Exception($prod->nombre_producto . ' se vende por unidad: la cantidad debe ser un número entero', 400);
			}
			if ($cantidad > 9999) throw new Exception('Cantidad demasiado alta para ' . $prod->nombre_producto, 400);
			$segunStock = $prod->segun_stock === 't' || $prod->segun_stock === true;
			// Los agregados siguen al producto: si el producto es exento, su línea completa lo es
			$exento = $prod->exento === 't' || $prod->exento === true;

			// v1.24.0: MENÚ. La línea del menú (su precio) y una línea por cada producto elegido: en $0 o con su costo
			// adicional de venta. Cada producto elegido sale en la comanda de su destino (cocina, barra…) con el nombre
			// del menú; la línea del menú no sale en la comanda.
			// v1.24.1: un pedido que no trae "menu" (la app móvil actual no conoce los menús) vende el menú como un producto,
			// igual que antes; la Caja web siempre lo manda (lo elegido) y ahí se valida
			// v1.25.0: PROMO DE SUSHI. Igual que el menú: la línea de la promo (su precio, no sale en la comanda) y por cada
			// grupo una línea por producto (envoltorio, base y rellenos) con cantidad = piezas y su valor adicional por pieza;
			// los extras, una línea cada uno. Todas apuntan a la promo (es_detalle_menu), así mesas, cobros por partes y
			// anulaciones las mueven juntas. A cocina va una línea por grupo ("10 x PANKO: Pollo, Palta..."), en la impresora
			// de la promo. Un pedido sin "sushi" (app móvil, POS de escritorio) vende la promo como un producto, igual que antes.
			if (isset($sushis[$id]) && array_key_exists('sushi', $it)) {
				if (!empty($it['agregados'])) throw new Exception($prod->nombre_producto . ' es una promo de sushi: los extras se eligen dentro de la promo', 400);
				$sel = $this->eleccionSushi($sushis[$id], $it['sushi'], $prod->nombre_producto);
				$nota = mb_substr(trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', isset($it['nota']) ? (string)$it['nota'] : '')), 0, 150);
				$bodegaCaja = !empty($config['id_bodega_caja']) ? (int)$config['id_bodega_caja'] : 0;
				$unitario = (int)round((float)$prod->precio);
				$bruto = (int)round($unitario * $cantidad);
				$subtotal += $bruto;
				$idxPromo = count($lineas);
				$lineas[] = array(
					'id_producto' => $id, 'nombre' => $prod->nombre_producto, 'cantidad' => $cantidad,
					'unitario' => $unitario, 'bruto' => $bruto, 'id_agregado' => 0, 'nota' => $nota, 'segun_stock' => $segunStock,
					'recetas' => array(array((int)$prod->id_receta, $bodegaCaja ?: (int)$prod->id_bodega)), 'exento' => $exento,
					'es_menu' => true, 'id_menu' => 0,
				);
				$hijo = function ($o, $cant, $notaLinea, $imprimir) use (&$lineas, &$subtotal, $exento, $id, $idxPromo, $bodegaCaja) {
					$b = (int)round($o['valor'] * $cant);
					$subtotal += $b;
					$lineas[] = array(
						'id_producto' => $o['id_producto'], 'nombre' => $o['nombre'], 'cantidad' => $cant,
						'unitario' => $o['valor'], 'bruto' => $b, 'id_agregado' => 0, 'nota' => mb_substr($notaLinea, 0, 250),
						'segun_stock' => false, 'recetas' => array(array($o['id_receta'], $bodegaCaja ?: $o['id_bodega'])),
						'exento' => $exento, 'ila_de' => $id,
						'es_detalle_menu' => true, 'id_menu' => 0, 'del_menu' => $idxPromo, 'imprimir' => $imprimir,
					);
				};
				$resumen = array();
				// v1.26.0: promo por roll: cada línea del grupo va con la cantidad de rolls (no de piezas) y su valor por roll;
				// en cocina sale "2 x ROLL PANKO" y el mensaje empieza con "Roll:" (también en anulaciones y reenvíos)
				$porRol = $sushis[$id]['modo'] === 'rol';
				// v1.26.1: cada roll (grupo) puede llevar su nota; la nota de la promo completa va UNA sola vez, en el primero
				foreach ($sel['grupos'] as $ng => $g) {
					$cantGrupo = round($cantidad * ($porRol ? $g['rolls'] : $g['piezas']), 3);
					$nombresRellenos = array_map(function ($o) { return $o['nombre']; }, $g['rellenos']);
					$titulo = $g['envoltorio'] ? $g['envoltorio']['nombre'] : ($g['base'] ? $g['base']['nombre'] : $nombresRellenos[0]);
					$desc = ($porRol ? 'Roll: ' : '') . ($nombresRellenos ? implode(', ', $nombresRellenos) : 'Sin relleno') . ($g['base'] ? ' · Base ' . $g['base']['nombre'] : '');
					// La primera línea del grupo lleva la descripción completa y es la que sale en comandas, reenvíos y anulaciones
					$partes = array();
					if ($g['envoltorio']) $partes[] = $g['envoltorio'];
					if ($g['base']) $partes[] = $g['base'];
					foreach ($g['rellenos'] as $o) $partes[] = $o;
					$notaG = $g['nota'] !== '' ? ' · Nota: ' . $g['nota'] : '';
					$notaPromo = $ng === 0 && $nota !== '' ? ' · Nota promo: ' . $nota : '';
					// Lo guardado en la línea (sale así en reenvíos y anulaciones)
					foreach ($partes as $k => $o) $hijo($o, $cantGrupo, $k === 0 ? $desc . $notaG . ' (' . $prod->nombre_producto . $notaPromo . ')' : '', $k === 0);
					$comanda[] = array('id_producto' => $id, 'nombre' => ($porRol ? 'Roll ' : '') . $titulo, 'cantidad' => $cantGrupo,
						// el nombre ya dice "Roll": la nota sin el "Roll:" del principio
						'nota' => ($porRol ? mb_substr($desc, 6) : $desc) . $notaG . ' (' . $prod->nombre_producto . $notaPromo . ')', 'agregados' => array());
					$resumen[] = ($porRol ? $g['rolls'] . ($g['rolls'] === 1 ? ' roll ' : ' rolls ') : $g['piezas'] . ' ') . $titulo
						. ' (' . ($porRol ? mb_substr($desc, 6) : $desc) . $notaG . ')';
				}
				foreach ($sel['extras'] as $par) {
					list($o, $q) = $par;
					$cantExtra = round($cantidad * $q, 3);
					$hijo($o, $cantExtra, 'Extra de ' . $prod->nombre_producto, true);
					$comanda[] = array('id_producto' => $id, 'nombre' => $o['nombre'], 'cantidad' => $cantExtra,
						'nota' => 'Extra de ' . $prod->nombre_producto, 'agregados' => array());
					$resumen[] = ($q > 1 ? $q . ' ' : '') . $o['nombre'];
				}
				$notas[] = $prod->nombre_producto . ': ' . implode('; ', $resumen) . ($nota !== '' ? ' (' . $nota . ')' : '');
				continue;
			}

			if (isset($menus[$id]) && array_key_exists('menu', $it)) {
				if (!empty($it['agregados'])) throw new Exception($prod->nombre_producto . ' es un menú: no lleva agregados', 400);
				$elegidosMenu = $this->eleccionMenu($menus[$id], isset($it['menu']) ? $it['menu'] : array(), $prod->nombre_producto);
				$nota = mb_substr(trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', isset($it['nota']) ? (string)$it['nota'] : '')), 0, 150);
				$bodega = !empty($config['id_bodega_caja']) ? (int)$config['id_bodega_caja'] : (int)$prod->id_bodega;
				$unitario = (int)round((float)$prod->precio);
				$bruto = (int)round($unitario * $cantidad);
				$subtotal += $bruto;
				$idxMenu = count($lineas);
				$lineas[] = array(
					'id_producto' => $id, 'nombre' => $prod->nombre_producto, 'cantidad' => $cantidad,
					'unitario' => $unitario, 'bruto' => $bruto, 'id_agregado' => 0, 'nota' => $nota, 'segun_stock' => $segunStock,
					'recetas' => array(array((int)$prod->id_receta, $bodega)), 'exento' => $exento,
					'es_menu' => true, 'id_menu' => (int)$menus[$id]['id_menu'],
				);
				$etiqueta = 'Menú: ' . $prod->nombre_producto . ($nota !== '' ? ' · ' . $nota : '');
				$nombresMenu = array();
				foreach ($elegidosMenu as $par) {
					list($o, $q) = $par;
					$cantComp = round($cantidad * $q, 3);
					$brutoComp = (int)round($o['adicional'] * $cantComp);
					$subtotal += $brutoComp;
					$lineas[] = array(
						'id_producto' => $o['id_producto'], 'nombre' => $o['nombre'], 'cantidad' => $cantComp,
						'unitario' => $o['adicional'], 'bruto' => $brutoComp, 'id_agregado' => 0, 'nota' => '',
						'segun_stock' => false,
						'recetas' => array(array($o['id_receta'], !empty($config['id_bodega_caja']) ? (int)$config['id_bodega_caja'] : $o['id_bodega'])),
						'exento' => $exento, 'ila_de' => $id,
						'es_detalle_menu' => true, 'id_menu' => (int)$menus[$id]['id_menu'], 'del_menu' => $idxMenu,
						'imprimir' => $o['imprimir'],   // "Imprimir" del producto en el menú (no sale en comandas ni anulaciones)
					);
					$nombresMenu[] = ($q > 1 ? $q . ' ' : '') . $o['nombre'];
					if ($o['imprimir']) {
						$comanda[] = array('id_producto' => $o['id_producto'], 'nombre' => $o['nombre'], 'cantidad' => $cantComp,
							'nota' => $etiqueta, 'agregados' => array());
					}
				}
				$notas[] = $prod->nombre_producto . ($nombresMenu ? ': ' . implode(', ', $nombresMenu) : '') . ($nota !== '' ? ' (' . $nota . ')' : '');
				continue;
			}

			$elegidos = array();
			foreach ((isset($it['agregados']) && is_array($it['agregados']) ? $it['agregados'] : array()) as $idAg) {
				if (!isset($agregadosPorMaestro[$id][(int)$idAg])) {
					throw new Exception('Un agregado de ' . $prod->nombre_producto . ' ya no está disponible', 400);
				}
				$elegidos[(int)$idAg] = $agregadosPorMaestro[$id][(int)$idAg];
			}
			$elegidos = array_values($elegidos);
			// v1.24.3: sin límite de agregados por producto (pedido del usuario; antes Configuraciones > Máximo de agregados).
			// Solo un tope técnico alto para que una venta armada a mano no traiga cientos.
			if (count($elegidos) > 100) throw new Exception('Demasiados agregados en ' . $prod->nombre_producto, 400);

			// 19-11-2026: la nota del producto llega hasta 150 caracteres, para que en la misma fila quepa el mensaje de su
			// primer agregado (detalle_cotizacion.mensaje es de 250; antes se cortaba a 200 y en el reenvío se perdía)
			$nota = mb_substr(trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', isset($it['nota']) ? (string)$it['nota'] : '')), 0, 150);
			// 19-11-2026: mensaje de cada agregado (ej. Palta: sin sal), sale en la comanda junto al agregado
			$notasAg = array();
			foreach ((isset($it['notas_agregados']) && is_array($it['notas_agregados']) ? $it['notas_agregados'] : array()) as $idAg => $t) {
				$t = is_scalar($t) ? mb_substr(trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string)$t)), 0, 60) : '';
				if ($t !== '') $notasAg[(int)$idAg] = $t;
			}
			$notaDe = function ($a) use ($notasAg) {
				return isset($notasAg[(int)$a->id_agregado]) ? $notasAg[(int)$a->id_agregado] : '';
			};
			$nombres = array();
			foreach ($elegidos as $a) $nombres[] = '+' . $a->nombre_producto . ($notaDe($a) !== '' ? ' (' . $notaDe($a) . ')' : '');
			if ($nota !== '' || $nombres) {
				$notas[] = $prod->nombre_producto . ($nombres ? ' ' . implode(' ', $nombres) : '') . ($nota !== '' ? ': ' . $nota : '');
			}

			$comanda[] = array('id_producto' => $id, 'nombre' => $prod->nombre_producto, 'cantidad' => $cantidad, 'nota' => $nota,
				'agregados' => array_map(function ($a) use ($notaDe) { return $a->nombre_producto . ($notaDe($a) !== '' ? ' » ' . $notaDe($a) : ''); }, $elegidos));
			$primero = $elegidos ? array_shift($elegidos) : null;
			// El primer agregado va en la misma fila del producto: su mensaje se guarda junto al del producto
			$notaFila = $nota;
			if ($primero && $notaDe($primero) !== '') {
				// El mensaje del agregado siempre cabe entero: si no alcanza, se acorta la nota del producto (250 = la columna)
				$sufijo = $primero->nombre_producto . ': ' . $notaDe($primero);
				$cabe = max(0, 250 - mb_strlen($sufijo) - 3);
				$notaFila = $nota !== '' ? mb_substr($nota, 0, $cabe) . ' | ' . $sufijo : mb_substr($sufijo, 0, 250);
			}
			$unitario = (int)round((float)$prod->precio) + ($primero ? (int)$primero->valor : 0);
			$bruto = (int)round($unitario * $cantidad);
			$subtotal += $bruto;
			// Bodega de la caja (Control de cajas); sin bodega en la caja, la de cada producto (como antes)
			$bodega = !empty($config['id_bodega_caja']) ? (int)$config['id_bodega_caja'] : (int)$prod->id_bodega;
			$recetas = array(array((int)$prod->id_receta, $bodega));
			if ($primero) $recetas[] = array((int)$primero->id_receta, $bodega);
			$lineas[] = array(
				'id_producto' => $id, 'nombre' => $prod->nombre_producto, 'cantidad' => $cantidad,
				'unitario' => $unitario, 'bruto' => $bruto, 'id_agregado' => $primero ? (int)$primero->id_producto : 0,
				'nota' => $notaFila, 'segun_stock' => $segunStock, 'recetas' => $recetas, 'exento' => $exento,
			);
			foreach ($elegidos as $a) {
				$bruto = (int)round((int)$a->valor * $cantidad);
				$subtotal += $bruto;
				$lineas[] = array(
					'id_producto' => (int)$a->id_producto, 'nombre' => $a->nombre_producto, 'cantidad' => $cantidad,
					'unitario' => (int)$a->valor, 'bruto' => $bruto, 'id_agregado' => 0, 'nota' => $notaDe($a),
					'segun_stock' => $segunStock, 'recetas' => array(array((int)$a->id_receta, !empty($config['id_bodega_caja']) ? (int)$config['id_bodega_caja'] : (int)$a->id_bodega)),
					'exento' => $exento,
					'ila_de' => $id,   // v1.20.0: el agregado lleva el ILA de su producto, igual que el exento
				);
			}
		}
		return array($lineas, $subtotal, $notas, $comanda);
	}

	/** Reintenta la emisión de un documento electrónico pendiente. */
	public function reenviarDte($idVenta)
	{
		$db = $this->conexion();
		$v = $this->q($db, 'SELECT v.id_venta, COALESCE(v.folio_dte, 0) AS folio_dte, COALESCE(v.mensaje_pedido, \'\') AS mensaje_pedido,
				COALESCE(td.cod_sii, 0) AS cod_sii, v.nombre_computador
			FROM venta v JOIN tdocumento td ON td.id_tdocumento = v.id_tdocumento WHERE v.id_venta = ?', array((int)$idVenta))->row();
		if (!$v || $v->nombre_computador !== self::COMPUTADOR) throw new Exception('Venta no encontrada', 404);
		if (!in_array((int)$v->cod_sii, self::COD_SII_ELECTRONICOS)) throw new Exception('No es un documento electrónico', 400);
		if ((int)$v->folio_dte > 0) throw new Exception('El documento ya fue emitido', 409);
		if (strpos($v->mensaje_pedido, 'ANULADA') === 0) throw new Exception('La venta está anulada', 409);
		// Dos reenvíos a la vez (dos pestañas o cajas) emitirían dos documentos: solo uno puede estar enviando
		$lock = $this->q($db, 'SELECT pg_try_advisory_lock(?, ?) AS ok', array(self::LOCK_DTE, (int)$idVenta))->row();
		if (!$lock || ($lock->ok !== 't' && $lock->ok !== true)) throw new Exception('Este documento ya se está enviando, espera unos segundos', 409);
		try {
			// Pudo emitirse mientras se esperaba
			$f = $this->q($db, 'SELECT COALESCE(folio_dte, 0) AS f FROM venta WHERE id_venta = ?', array((int)$idVenta))->row();
			if ($f && (int)$f->f > 0) throw new Exception('El documento ya fue emitido', 409);
			$r = $this->dte()->emitir($db, (int)$idVenta);
		} finally {
			$this->q($db, 'SELECT pg_advisory_unlock(?, ?)', array(self::LOCK_DTE, (int)$idVenta));
		}
		if (!$r['ok']) throw new Exception('No se pudo emitir: ' . $r['mensaje'], 502);
		return $r;
	}

	/**
	 * Primera emisión de una venta o NC con el mismo bloqueo que el reenvío: si alguien aprieta "Reenviar" mientras
	 * Cloud Apps todavía responde, espera y no se emite el documento dos veces.
	 */
	private function emitirDte($db, $idVenta)
	{
		$this->q($db, 'SELECT pg_advisory_lock(?, ?)', array(self::LOCK_DTE, (int)$idVenta));
		try {
			$f = $this->q($db, 'SELECT COALESCE(folio_dte, 0) AS f FROM venta WHERE id_venta = ?', array((int)$idVenta))->row();
			if ($f && (int)$f->f > 0) return array('ok' => true, 'folio' => (int)$f->f, 'mensaje' => 'Ya emitido');
			return $this->dte()->emitir($db, (int)$idVenta);
		} finally {
			$this->q($db, 'SELECT pg_advisory_unlock(?, ?)', array(self::LOCK_DTE, (int)$idVenta));
		}
	}

	private function dte()
	{
		$this->load->model('Mdte');
		return $this->Mdte;
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
		$this->claveAnulacion($db, 'admin_anular_venta', 'anular venta', $idUsuario, $idRol, $autorizacion);
		$cols = $this->columnas($db, array('venta', 'ctacte_cliente', 'consolidado_ventas', 'anulacion_ticket'));

		// Si el documento electrónico se está enviando al SII (venta recién hecha o reenvío), no se anula: podría
		// quedar emitido en el SII y borrado aquí. El bloqueo se mantiene hasta terminar la anulación.
		$lock = $this->q($db, 'SELECT pg_try_advisory_lock(?, ?) AS ok', array(self::LOCK_DTE, (int)$idVenta))->row();
		if (!$lock || ($lock->ok !== 't' && $lock->ok !== true)) {
			throw new Exception('El documento se está enviando al SII en este momento. Espera unos segundos y vuelve a intentar.', 409);
		}
		try {
			return $this->anularVentaBloqueada($db, $config, $cols, $caja, $idUsuario, $idVenta, $motivo) + array('config' => $config);
		} finally {
			$this->q($db, 'SELECT pg_advisory_unlock(?, ?)', array(self::LOCK_DTE, (int)$idVenta));
		}
	}

	private function anularVentaBloqueada($db, $config, $cols, $caja, $idUsuario, $idVenta, $motivo)
	{
		$db->trans_begin();
		try {
			$v = $this->q($db, 'SELECT v.id_venta, v.id_caja, v.id_tdocumento, v.folio, v.total, COALESCE(v.mensaje_pedido, \'\') AS mensaje_pedido,
					v.fecha_transaccion::date AS dia, COALESCE(v.folio_dte, 0) AS folio_dte,
					COALESCE((SELECT td.cod_sii FROM tdocumento td WHERE td.id_tdocumento = v.id_tdocumento), 0) AS cod_sii
				FROM venta v WHERE v.id_venta = ? FOR UPDATE', array((int)$idVenta))->row();
			if (!$v || (int)$v->id_caja !== (int)$caja->id_caja) throw new Exception('La venta no pertenece a tu caja abierta', 404);
			if (strpos($v->mensaje_pedido, 'ANULADA') === 0) throw new Exception('La venta ya estaba anulada', 409);
			// Un documento ya informado al SII no se borra: se compensa con nota de crédito
			if (in_array((int)$v->cod_sii, self::COD_SII_ELECTRONICOS) && (int)$v->folio_dte > 0) {
				throw new Exception('Un documento electrónico emitido no se puede anular. Usa Devolver para emitir la nota de crédito.', 409);
			}
			// Anular una NC interna sí se puede (revierte su devolución); una venta con NC vigentes, no
			$idsNc = $this->idsNotaCredito($db);
			if (!in_array((int)$v->id_tdocumento, $idsNc, true)) {
				$nc = $this->q($db, 'SELECT string_agg(folio::text, \', \') AS folios FROM venta
					WHERE id_tdocumento ' . $this->sqlEn($idsNc) . ' AND id_tdocumento_referencia = ? AND folio_referencia = ? AND fecha_referencia = ?
					AND COALESCE(mensaje_pedido, \'\') NOT LIKE \'ANULADA%\'',
					array((int)$v->id_tdocumento, (int)$v->folio, $v->dia))->row();
				if ($nc && $nc->folios) throw new Exception('La venta tiene devoluciones (NC N° ' . $nc->folios . '). Anula primero la nota de crédito.', 409);
			}

			// Un crédito que el cliente ya pagó (total o en parte) no se anula: el pago quedaría sin su deuda
			if ($this->hayPagosCredito($db)) {
				$pc = $this->q($db, 'SELECT count(*) AS n FROM detalle_pago_credito WHERE id_venta = ?', array((int)$idVenta))->row();
				if ($pc && (int)$pc->n > 0) throw new Exception('Esta venta a crédito ya tiene pagos registrados del cliente: no se puede anular. Usa Devolver.', 409);
			}

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
				'monto_exento_con_descuento', 'monto_afecto_con_descuento',
				// 14-11-2026: la propina también se anula (antes quedaba y la sumaban propinas por garzón y el cierre)
				'valor_propina', 'propina_final', 'monto_comision_propina') as $c) {
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
		} catch (Throwable $e) {   // 19-11-2026: también errores de PHP deshacen la transacción
			$db->trans_rollback();
			throw $e;
		}
		// Datos para el comprobante de anulación (impreso y/o por correo, según Configuraciones)
		$td = $this->q($db, 'SELECT nombre_tdocumento FROM tdocumento WHERE id_tdocumento = ?', array((int)$v->id_tdocumento))->row();
		$u = $this->q($db, 'SELECT nombre_usuario FROM usuario WHERE id_usuario = ?', array((int)$idUsuario))->row();
		return array(
			'id_venta' => (int)$idVenta,
			'documento' => $td ? (string)$td->nombre_tdocumento : 'Documento',
			'folio' => (int)$v->folio,
			'total' => (int)$v->total,
			'motivo' => $motivo,
			'usuario' => $u ? (string)$u->nombre_usuario : '',
			'fecha' => date('d/m/Y H:i'),
			'id_caja' => (int)$caja->id_caja,
		);
	}

	/**
	 * Cierre con el mismo criterio del POS de escritorio:
	 * - $efectivoContado es todo el efectivo en caja (fondo incluido, ya pagadas las propinas).
	 * - $fondoSiguiente queda en caja como sencillo del próximo turno (caja.fondo_fijo);
	 *   el resto se entrega (efectivo_entregado) y es lo que se rinde en efectivo.
	 * - $declarado = {id_fpago: monto} para formas no efectivo, tal cual los vouchers (con propina).
	 * - diferencia = rendido + (fondo siguiente - fondo inicial) - (ventas + ingresos - egresos).
	 */
	public function cerrarCaja($caja, $idUsuario, $efectivoContado, $fondoSiguiente, $declarado, $inventario = array())
	{
		$db = $this->conexion();
		// Las mesas con pedido NO impiden el cierre: el pedido no es de ninguna caja hasta que se cobra (cotizacion.id_caja
		// se graba al pagar), así que sigue abierto y se cobra en el turno siguiente. Lo que sí lo impide son las ventas
		// en espera pendientes (Caja::cerrar, Mventasespera::bloqueanCierre).
		$cols = $this->columnas($db, array('caja', 'detalle_pagocaja'));
		// Inventario de insumos al cierre: se exige todo lo marcado en el insumo y se valida antes de tocar nada
		$filasInventario = $this->insumosInventarioCierre();
		$contados = $this->validarInventarioCierre($filasInventario, is_array($inventario) ? $inventario : array());
		$res = $this->resumenCaja($caja);
		$fondo = $res['fondo'];
		$efectivoContado = (int)$efectivoContado;
		$fondoSiguiente = $fondoSiguiente === null ? min($fondo, $efectivoContado) : (int)$fondoSiguiente;
		if ($fondoSiguiente < 0) throw new Exception('El fondo para el próximo turno no puede ser negativo', 400);
		if ($fondoSiguiente > $efectivoContado) throw new Exception('El fondo para el próximo turno no puede superar el efectivo contado', 400);
		$entregado = $efectivoContado - $fondoSiguiente;

		$rendido = array();
		$efectivoAsignado = false;
		foreach ($res['por_forma_pago'] as $f) {
			if ($f['cortesia']) continue;
			if ($f['efectivo']) {
				$rendido[$f['id_fpago']] = $efectivoAsignado ? 0 : $entregado;
				$efectivoAsignado = true;
			} elseif (!empty($f['credito'])) {
				// 19-11-2026 (auditoría): lo fiado no se declara (no hay comprobantes): se rinde lo del sistema, sin faltante falso
				$rendido[$f['id_fpago']] = $f['monto'] + $f['propina'];
			} else {
				$rendido[$f['id_fpago']] = isset($declarado[$f['id_fpago']]) ? (int)$declarado[$f['id_fpago']] : $f['monto'] + $f['propina'];
			}
		}
		if (!$efectivoAsignado && $entregado !== 0) {
			// Sin ventas en efectivo pero se entrega efectivo: se rinde en la forma de pago efectivo
			$config = $this->configuracion($db);
			foreach ($this->catalogoFpago($db, $config) as $f) {
				if ($f['efectivo']) { $rendido[$f['id_fpago']] = $entregado; break; }
			}
		}
		$suma = $res['ventas'] + $res['ingresos'] - $res['egresos'] + $res['pagos_credito_otros'];
		$diferencia = array_sum($rendido) + ($fondoSiguiente - $fondo) - $suma;
		// Efectivo cobrado (con propina) y lo que queda tras pagar todas las propinas, como el POS
		$efectivoVenta = $res['efectivo_ventas'] + $res['propinas_efectivo'];
		$ahora = date('Y-m-d H:i:s');

		$db->trans_begin();
		try {
			$c = $this->q($db, 'SELECT cerrada FROM caja WHERE id_caja = ? FOR UPDATE', array((int)$caja->id_caja))->row();
			if (!$c) throw new Exception('La caja no existe', 404);
			if ($c->cerrada === 't' || $c->cerrada === true) throw new Exception('La caja ya está cerrada', 409);
			// 19-11-2026 (auditoría): los totales se calcularon antes de bloquear la caja. Si justo entró una venta, un pago o
			// un movimiento (otra pestaña o equipo), no se cierra con números viejos: se pide revisar y volver a cerrar.
			$ahoraRes = $this->resumenCaja($caja);
			foreach (array('ventas', 'cantidad_ventas', 'devoluciones', 'ingresos', 'egresos', 'propinas', 'pagos_credito') as $k) {
				if ((isset($ahoraRes[$k]) ? $ahoraRes[$k] : null) != (isset($res[$k]) ? $res[$k] : null)) {
					throw new Exception('Mientras cerrabas se registró otra venta o movimiento en esta caja. Revisa el cierre y vuelve a cerrar.', 409);
				}
			}
			$this->actualizar($db, $cols, 'caja', array(
				'cerrada' => true,
				'fecha_rendicion' => $ahora,
				'id_usuario_rendicion' => (int)$idUsuario,
				'fondo_fijo_anterior' => $fondo,
				'fondo_fijo' => $fondoSiguiente,
				'monto_entregado' => $entregado,
				'efectivo_entregado' => $entregado,
				'ventas' => $res['ventas'],
				'ventas_local' => $res['ventas'],
				'total_sistema' => $res['ventas'],
				'diferencia' => $diferencia,
				'descuento' => $res['descuentos'],
				'cortesia' => $res['cortesias'],
				'efectivo_fpago' => $efectivoVenta - $res['propinas'],
				'efectivo_venta' => $efectivoVenta,
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
			// El inventario contado reemplaza al stock del sistema; va en la misma transacción que el cierre
			$resInventario = $this->aplicarInventarioCierre($db, (int)$caja->id_caja, $filasInventario, $contados);
			$db->trans_commit();
		} catch (Throwable $e) {   // 19-11-2026: también errores de PHP deshacen la transacción
			$db->trans_rollback();
			throw $e;
		}
		return array('id_caja' => (int)$caja->id_caja, 'diferencia' => $diferencia, 'rendido' => array_sum($rendido),
			'esperado' => $suma, 'entregado' => $entregado, 'fondo_siguiente' => $fondoSiguiente, 'inventario' => $resInventario);
	}

	// ====================== Inventario de insumos al cierre ======================

	/**
	 * Insumos marcados con "Inventario en cierre de turno" y su stock por bodega (stock_insumo).
	 * Cada fila: {clave "idInsumo:idBodega", id_insumo, nombre, id_bodega, bodega, umedida, stock, existe}.
	 * Por insumo se listan las bodegas donde hay (o hubo) movimiento; si no tiene ninguna, va en la bodega
	 * principal con stock 0 para que igual se cuente. Devuelve [] si el cliente no tiene las tablas.
	 */
	public function insumosInventarioCierre()
	{
		$db = $this->conexion();
		// Configuraciones > Inventario insumos cierre de caja: desmarcado, no se pide inventario al cerrar
		if (!$this->configuracion($db)['inventario_cierre']) return array();
		$c = $this->columnas($db, array('insumo', 'stock_insumo', 'bodega', 'inventario_turno', 'detalle_inventario_turno'));
		if (!isset($c['insumo']['inventario_cierre_turno'], $c['stock_insumo'], $c['bodega'], $c['inventario_turno'], $c['detalle_inventario_turno'])) return array();

		$filas = $this->q($db, "SELECT i.id_insumo, i.nombre_insumo, COALESCE(um.nombre_umedida, '') AS umedida,
				s.id_bodega, b.nombre_bodega, COALESCE(s.saldo, 0) AS saldo,
				(s.id_stock_insumo IS NOT NULL AND (COALESCE(s.cantidad_entrada, 0) <> 0 OR COALESCE(s.cantidad_salida, 0) <> 0 OR COALESCE(s.saldo, 0) <> 0)) AS con_mov
			FROM insumo i
			LEFT JOIN umedida um ON um.id_umedida = i.id_umedida_inventario
			LEFT JOIN stock_insumo s ON s.id_insumo = i.id_insumo
			LEFT JOIN bodega b ON b.id_bodega = s.id_bodega
			WHERE i.vigente IS NOT FALSE AND i.inventario_cierre_turno IS TRUE
			ORDER BY b.nombre_bodega, i.nombre_insumo, i.id_insumo")->result();

		$porInsumo = array();
		foreach ($filas as $f) $porInsumo[(int)$f->id_insumo][] = $f;
		$principal = $this->q($db, 'SELECT id_bodega, nombre_bodega FROM bodega ORDER BY id_bodega LIMIT 1')->row();

		$res = array();
		foreach ($porInsumo as $idInsumo => $lista) {
			$usar = array_values(array_filter($lista, function ($f) { return $f->id_bodega !== null && ($f->con_mov === 't' || $f->con_mov === true); }));
			if (!$usar) {
				// Sin movimientos en ninguna bodega: se cuenta en la primera fila existente o en la bodega principal
				$f0 = $lista[0];
				if ($f0->id_bodega === null && $principal) {
					$f0 = (object)array('id_insumo' => $idInsumo, 'nombre_insumo' => $f0->nombre_insumo, 'umedida' => $f0->umedida,
						'id_bodega' => $principal->id_bodega, 'nombre_bodega' => $principal->nombre_bodega, 'saldo' => 0, 'sin_fila' => true);
				}
				if ($f0->id_bodega === null) continue;
				$usar = array($f0);
			}
			foreach ($usar as $f) {
				$res[] = array(
					'clave' => $idInsumo . ':' . (int)$f->id_bodega,
					'id_insumo' => $idInsumo,
					'nombre' => $f->nombre_insumo,
					'id_bodega' => (int)$f->id_bodega,
					'bodega' => (string)$f->nombre_bodega,
					'umedida' => $this->abreviaturaUmedida($f->umedida),
					'stock' => round((float)$f->saldo, 3),
				);
			}
		}
		usort($res, function ($a, $b) {
			return strcmp($a['bodega'] . '|' . $a['nombre'], $b['bodega'] . '|' . $b['nombre']);
		});
		return $res;
	}

	/** Exige un número >= 0 por cada insumo marcado. Devuelve [clave => cantidad contada]. */
	private function validarInventarioCierre($filas, $inventario)
	{
		$contados = array();
		$faltan = array();
		foreach ($filas as $f) {
			$v = isset($inventario[$f['clave']]) ? str_replace(',', '.', trim((string)$inventario[$f['clave']])) : '';
			if ($v === '' || !is_numeric($v) || (float)$v < 0) { $faltan[] = $f['nombre']; continue; }
			$contados[$f['clave']] = round((float)$v, 3);
		}
		if ($faltan) {
			throw new Exception('Falta el inventario de: ' . implode(', ', array_slice($faltan, 0, 5)) . (count($faltan) > 5 ? ' y ' . (count($faltan) - 5) . ' más' : ''), 400);
		}
		return $contados;
	}

	/**
	 * El stock contado reemplaza al del sistema. Se conserva saldo = entrada - salida: una diferencia positiva
	 * se suma a la entrada y una negativa a la salida. Queda registrado en inventario_turno (una por bodega)
	 * y detalle_inventario_turno (stock del sistema antes, cantidad contada).
	 * Devuelve {insumos, con_diferencia}. Debe llamarse dentro de la transacción del cierre.
	 */
	private function aplicarInventarioCierre($db, $idCaja, $filas, $contados)
	{
		if (!$filas) return array('insumos' => 0, 'con_diferencia' => 0);
		$cols = $this->columnas($db, array('stock_insumo', 'inventario_turno', 'detalle_inventario_turno'));
		$ahora = date('Y-m-d H:i:s');
		$turnos = array();
		$conDiferencia = 0;
		foreach ($filas as $f) {
			$idBodega = $f['id_bodega'];
			if (!isset($turnos[$idBodega])) {
				$turnos[$idBodega] = $this->insertar($db, $cols, 'inventario_turno',
					array('id_bodega' => $idBodega, 'id_caja' => $idCaja, 'fecha_hora' => $ahora), 'id_inventario_turno');
			}
			$contado = $contados[$f['clave']];
			// Se bloquea la fila: el stock puede haber cambiado por ventas desde que se abrió el cuadro de inventario
			$s = $this->q($db, 'SELECT id_stock_insumo, COALESCE(cantidad_entrada, 0) AS entrada, COALESCE(cantidad_salida, 0) AS salida
				FROM stock_insumo WHERE id_insumo = ? AND id_bodega = ? ORDER BY id_stock_insumo LIMIT 1 FOR UPDATE',
				array($f['id_insumo'], $idBodega))->row();
			$actual = $s ? round((float)$s->entrada - (float)$s->salida, 6) : 0.0;
			$dif = round($contado - $actual, 6);
			if (abs($dif) > 0.0005) $conDiferencia++;
			if ($s) {
				$this->q($db, 'UPDATE stock_insumo SET cantidad_entrada = ?, cantidad_salida = ?, saldo = ? WHERE id_stock_insumo = ?',
					array(round((float)$s->entrada + max($dif, 0), 6), round((float)$s->salida + max(-$dif, 0), 6), $contado, (int)$s->id_stock_insumo));
			} else {
				$this->insertar($db, $cols, 'stock_insumo', array('id_insumo' => $f['id_insumo'], 'id_bodega' => $idBodega,
					'cantidad_entrada' => max($dif, 0), 'cantidad_salida' => max(-$dif, 0), 'saldo' => $contado), null);
			}
			$this->insertar($db, $cols, 'detalle_inventario_turno', array('id_inventario_turno' => $turnos[$idBodega],
				'id_insumo' => $f['id_insumo'], 'stock' => $actual, 'cantidad' => $contado, 'fecha_hora' => $ahora), null);
		}
		return array('insumos' => count($filas), 'con_diferencia' => $conDiferencia);
	}

	/** Comparación guardada al cerrar una caja: [{nombre, bodega, umedida, stock, contado, diferencia}]. */
	private function inventarioCierreDeCaja($db, $idCaja)
	{
		$c = $this->columnas($db, array('inventario_turno', 'detalle_inventario_turno', 'insumo', 'bodega'));
		if (!isset($c['inventario_turno'], $c['detalle_inventario_turno'], $c['insumo'], $c['bodega'])) return array();
		$res = array();
		foreach ($this->q($db, "SELECT i.nombre_insumo, COALESCE(um.nombre_umedida, '') AS umedida, b.nombre_bodega,
				COALESCE(d.stock, 0) AS stock, COALESCE(d.cantidad, 0) AS contado
			FROM inventario_turno t
			JOIN detalle_inventario_turno d ON d.id_inventario_turno = t.id_inventario_turno
			JOIN insumo i ON i.id_insumo = d.id_insumo
			LEFT JOIN umedida um ON um.id_umedida = i.id_umedida_inventario
			LEFT JOIN bodega b ON b.id_bodega = t.id_bodega
			WHERE t.id_caja = ? ORDER BY b.nombre_bodega, i.nombre_insumo", array((int)$idCaja))->result() as $r) {
			$res[] = array('nombre' => $r->nombre_insumo, 'bodega' => (string)$r->nombre_bodega, 'umedida' => $this->abreviaturaUmedida($r->umedida),
				'stock' => round((float)$r->stock, 3), 'contado' => round((float)$r->contado, 3), 'diferencia' => round((float)$r->contado - (float)$r->stock, 3));
		}
		return $res;
	}

	// ====================== Devoluciones (nota de crédito interna) ======================

	const NC_NOMBRE = 'NOTA DE CREDITO INTERNA';

	/**
	 * Venta a devolver con lo que queda disponible por línea.
	 * Se busca por id_venta o por documento + folio (puede ser de otra caja o del POS de escritorio).
	 */
	public function ventaParaDevolucion($idVenta, $idTdoc = 0, $folio = 0)
	{
		$db = $this->conexion();
		return $this->datosDevolucion($db, (int)$idVenta, (int)$idTdoc, (int)$folio, false);
	}

	private function datosDevolucion($db, $idVenta, $idTdoc, $folio, $bloquear)
	{
		$idsNc = $this->idsNotaCredito($db);
		$sel = "SELECT v.id_venta, v.id_tdocumento, v.folio, v.id_cliente, v.id_lprecio, v.subtotal, v.total,
				COALESCE(td.cod_sii, 0) AS cod_sii, COALESCE(v.folio_dte, 0) AS folio_dte,
				COALESCE(v.por_descuento, 0) AS por_descuento, (v.es_cortesia IS TRUE) AS cortesia,
				COALESCE(v.mensaje_pedido, '') AS mensaje_pedido, v.fecha_transaccion::date AS dia,
				to_char(v.fecha_transaccion, 'DD/MM/YYYY HH24:MI') AS fecha_texto,
				td.nombre_tdocumento, COALESCE(c.nombre_cliente, '') AS nombre_cliente
			FROM venta v
			LEFT JOIN tdocumento td ON td.id_tdocumento = v.id_tdocumento
			LEFT JOIN cliente c ON c.id_cliente = v.id_cliente";
		if ($idVenta > 0) {
			$v = $this->q($db, $sel . ' WHERE v.id_venta = ?', array($idVenta))->row();
		} else {
			if ($idTdoc <= 0 || $folio <= 0) throw new Exception('Indica el documento y el folio', 400);
			// Si un folio se repite (contadores reiniciados), se toma el más reciente
			$v = $this->q($db, $sel . ' WHERE v.id_tdocumento = ? AND v.folio = ? ORDER BY v.id_venta DESC LIMIT 1',
				array($idTdoc, $folio))->row();
		}
		if (!$v) throw new Exception('No se encontró la venta', 404);
		if ($bloquear) $this->q($db, 'SELECT 1 FROM venta WHERE id_venta = ? FOR UPDATE', array((int)$v->id_venta));
		if (in_array((int)$v->id_tdocumento, $idsNc, true)) throw new Exception('Una nota de crédito no se puede devolver', 409);
		$electronico = in_array((int)$v->cod_sii, self::COD_SII_ELECTRONICOS);
		// Solo se emitió ante el SII si tiene folio DTE (las del POS de escritorio también lo tienen)
		if ($electronico && (int)$v->folio_dte <= 0) {
			throw new Exception('El documento aún no se emite ante el SII. Reenvíalo o anúlalo en vez de devolver.', 409);
		}
		if (strpos($v->mensaje_pedido, 'ANULADA') === 0) throw new Exception('La venta está anulada', 409);
		if ($v->cortesia === 't' || $v->cortesia === true) throw new Exception('Una cortesía no tiene monto que devolver', 409);
		if ((int)$v->total <= 0 || (int)$v->subtotal <= 0) throw new Exception('La venta no tiene monto que devolver', 409);
		// Venta a crédito que el cliente ya pagó (total o en parte) en la pestaña Créditos: no se devuelve ni se anula
		if ($this->hayPagosCredito($db)) {
			$pc = $this->q($db, 'SELECT count(*) AS n FROM detalle_pago_credito WHERE id_venta = ?', array((int)$v->id_venta))->row();
			if ($pc && (int)$pc->n > 0) {
				throw new Exception('Esta venta a crédito ya fue pagada por el cliente (pestaña Créditos): no se puede anular ni devolver.', 409);
			}
		}

		$lineas =$this->q($db, "SELECT dv.id_detalle_venta, dv.id_producto, COALESCE(dv.id_agregado, 0) AS id_agregado,
				COALESCE(p.nombre_producto, 'Producto ' || dv.id_producto) || COALESCE(' + ' || pa.nombre_producto, '') AS nombre,
				dv.cantidad, dv.bruto_un, dv.subtotal, (COALESCE(dv.poriva, 1) = 0) AS exento, COALESCE(dv.porila, 0) AS porila
			FROM detalle_venta dv
			LEFT JOIN producto p ON p.id_producto = dv.id_producto
			LEFT JOIN producto pa ON pa.id_producto = dv.id_agregado AND dv.id_agregado > 0
			WHERE dv.id_venta = ? AND dv.cantidad > 0 ORDER BY dv.id_detalle_venta", array((int)$v->id_venta))->result();

		// Lo ya devuelto en notas de crédito vigentes que referencian esta venta
		$devuelto = array();
		$montoDevuelto = 0;
		if ($idsNc) {
			$ref = 'nc.id_tdocumento ' . $this->sqlEn($idsNc) . ' AND nc.id_tdocumento_referencia = ? AND nc.folio_referencia = ? AND nc.fecha_referencia = ?
				AND COALESCE(nc.mensaje_pedido, \'\') NOT LIKE \'ANULADA%\'';
			$binds = array((int)$v->id_tdocumento, (int)$v->folio, $v->dia);
			$r = $this->q($db, "SELECT COALESCE(sum(-nc.total), 0) AS monto FROM venta nc WHERE $ref", $binds)->row();
			$montoDevuelto = (int)$r->monto;
			foreach ($this->q($db, "SELECT d.id_producto, COALESCE(d.id_agregado, 0) AS id_agregado, d.bruto_un, sum(-d.cantidad) AS cantidad
				FROM venta nc JOIN detalle_venta d ON d.id_venta = nc.id_venta
				WHERE $ref GROUP BY 1, 2, 3", $binds)->result() as $d) {
				$devuelto[$d->id_producto . ':' . $d->id_agregado . ':' . (int)$d->bruto_un] = (float)$d->cantidad;
			}
		}

		$res = array();
		foreach ($lineas as $l) {
			// Lo devuelto se descuenta de las líneas iguales (producto, agregado y precio) en orden
			$k = $l->id_producto . ':' . $l->id_agregado . ':' . (int)$l->bruto_un;
			$ya = isset($devuelto[$k]) ? min((float)$l->cantidad, $devuelto[$k]) : 0;
			if (isset($devuelto[$k])) $devuelto[$k] -= $ya;
			$res[] = array(
				'id_detalle_venta' => (int)$l->id_detalle_venta,
				'id_producto' => (int)$l->id_producto,
				'id_agregado' => (int)$l->id_agregado,
				'nombre' => $l->nombre,
				'cantidad' => (float)$l->cantidad,
				'devuelto' => $ya,
				'disponible' => round((float)$l->cantidad - $ya, 3),
				'bruto_un' => (int)$l->bruto_un,
				'exento' => $l->exento === 't' || $l->exento === true,
				'ila' => max(0.0, (float)$l->porila),   // v1.20.0: el ILA con que se vendió (la NC devuelve el mismo)
			);
		}

		$pagos = array();
		foreach ($this->q($db, 'SELECT pv.id_fpago, f.nombre_fpago, sum(pv.monto_pagado - COALESCE(pv.propina, 0)) AS monto
			FROM pago_venta pv JOIN fpago f ON f.id_fpago = pv.id_fpago
			WHERE pv.id_venta = ? GROUP BY 1, 2 ORDER BY 1', array((int)$v->id_venta))->result() as $pg) {
			$pagos[] = array('id_fpago' => (int)$pg->id_fpago, 'nombre_fpago' => $pg->nombre_fpago, 'monto' => (int)$pg->monto);
		}

		return array(
			'id_venta' => (int)$v->id_venta,
			'id_tdocumento' => (int)$v->id_tdocumento,
			'documento' => $v->nombre_tdocumento,
			'cod_sii' => (int)$v->cod_sii,
			// Devolución de un documento electrónico: NC electrónica (61), si la empresa emite por Cloud Apps
			'nc_electronica' => $electronico && $this->idNotaCreditoElectronica($db) > 0 && $this->dteDisponible($db),
			'folio' => (int)$v->folio,
			'fecha' => $v->fecha_texto,
			'dia' => $v->dia,
			'id_cliente' => (int)$v->id_cliente,
			'id_lprecio' => (int)$v->id_lprecio,
			'cliente' => $v->nombre_cliente,
			'subtotal' => (int)$v->subtotal,
			'total' => (int)$v->total,
			'por_descuento' => (float)$v->por_descuento,
			// Proporción pagada sobre el precio de lista (descuento global)
			'factor' => (int)$v->total / (int)$v->subtotal,
			// La NC de una factura informa el IVA como factura: IVA = round(Neto x 19%)
			'es_factura' => in_array((int)$v->cod_sii, self::COD_SII_FACTURAS) || stripos((string)$v->nombre_tdocumento, 'FACTURA') !== false,
			'monto_devuelto' => $montoDevuelto,
			'disponible' => max(0, (int)$v->total - $montoDevuelto),
			'lineas' => $res,
			'pagos' => $pagos,
		);
	}

	/**
	 * Devuelve productos de una venta: nota de crédito interna en la caja abierta.
	 * Se registra como venta en negativo, así el trigger devuelve el stock (cantidad negativa),
	 * el pago negativo baja lo recaudado por esa forma de pago y, si es crédito, la deuda del cliente.
	 *
	 * $p = {id_venta, lineas: [{id_detalle_venta, cantidad}], id_fpago, motivo, autorizacion}
	 */
	public function registrarDevolucion($caja, $idUsuario, $idRol, $p)
	{
		$db = $this->conexion();
		$config = $this->configuracion($db);
		$cols = $this->columnas($db, array('venta', 'detalle_venta', 'pago_venta', 'consolidado_ventas', 'tdocumento'));
		$factorIva = 1 + $config['por_iva'] / 100;

		$motivo = trim(isset($p['motivo']) ? (string)$p['motivo'] : '');
		if ($motivo === '') throw new Exception('Indica el motivo de la devolución', 400);
		$idFpago = (int)(isset($p['id_fpago']) ? $p['id_fpago'] : 0);
		$fpagos = array();
		foreach ($this->catalogoFpago($db, $config) as $f) $fpagos[$f['id_fpago']] = $f;
		if (!isset($fpagos[$idFpago]) || $fpagos[$idFpago]['cortesia']) throw new Exception('Elige cómo se devuelve el dinero', 400);
		$pedidas = array();
		foreach ((isset($p['lineas']) && is_array($p['lineas']) ? $p['lineas'] : array()) as $l) {
			$cant = round((float)(isset($l['cantidad']) ? $l['cantidad'] : 0), 3);
			if ($cant > 0) $pedidas[(int)$l['id_detalle_venta']] = $cant;
		}
		if (!$pedidas) throw new Exception('Indica qué productos se devuelven', 400);

		$this->claveAnulacion($db, 'admin_devolucion', 'devolución', $idUsuario, $idRol, isset($p['autorizacion']) ? $p['autorizacion'] : null);

		$ahora = date('Y-m-d H:i:s');
		$dia = date('Y-m-d');
		$db->trans_begin();
		try {
			$this->q($db, 'SET LOCAL TIME ZONE ' . $db->escape(self::TZ));
			$c = $this->q($db, 'SELECT cerrada FROM caja WHERE id_caja = ? FOR UPDATE', array((int)$caja->id_caja))->row();
			if (!$c || $c->cerrada === 't' || $c->cerrada === true) throw new Exception('La caja ya está cerrada', 409);

			// Con la venta bloqueada, dos devoluciones simultáneas no superan lo vendido
			$v = $this->datosDevolucion($db, (int)(isset($p['id_venta']) ? $p['id_venta'] : 0), 0, 0, true);
			$ncElectronica = $v['nc_electronica'];
			// Un documento electrónico solo se devuelve con nota de crédito electrónica (si no, el SII no se entera)
			if (in_array((int)$v['cod_sii'], self::COD_SII_ELECTRONICOS) && !$ncElectronica) {
				throw new Exception('Para devolver ' . $v['documento'] . ' se necesita la facturación electrónica habilitada, la nota de crédito electrónica asignada y el terminal de Cloud Apps (Configuraciones > Facturación electrónica)', 409);
			}
			$idNc = $ncElectronica ? $this->idNotaCreditoElectronica($db) : $this->tipoNotaCredito($db, true);
			$nombreNc = $ncElectronica ? $this->q($db, 'SELECT nombre_tdocumento FROM tdocumento WHERE id_tdocumento = ?', array($idNc))->row()->nombre_tdocumento : self::NC_NOMBRE;
			if ($fpagos[$idFpago]['credito'] && (int)$v['id_cliente'] === (int)$config['id_cliente_publico']) {
				throw new Exception('Devolver a ' . $fpagos[$idFpago]['nombre'] . ' requiere que la venta tenga cliente', 400);
			}
			// Su cuenta corriente: devolver "a crédito" solo baja la deuda de una venta que se hizo a crédito
			$ctacte = isset($this->columnas($db, array('ctacte_cliente'))['ctacte_cliente'])
				? $this->q($db, 'SELECT id_ctacte_cliente, monto, COALESCE(pagado, 0) AS pagado FROM ctacte_cliente
					WHERE id_venta = ? AND monto > 0 ORDER BY id_ctacte_cliente LIMIT 1 FOR UPDATE', array((int)$v['id_venta']))->row()
				: null;
			if ($fpagos[$idFpago]['credito'] && !$ctacte) {
				throw new Exception('La venta no se hizo a crédito: elige otra forma de devolver el dinero', 400);
			}
			// 19-11-2026 (auditoría): una venta fiada que el cliente aún debe no se devuelve en efectivo/tarjeta (salía plata de
			// la caja y la deuda quedaba igual): se devuelve "a crédito", que baja la deuda. Ya pagada, se devuelve como cualquier otra.
			if ($ctacte && !$fpagos[$idFpago]['credito'] && (int)$ctacte->monto - (int)$ctacte->pagado > 0) {
				$nombresCredito = array();
				foreach ($fpagos as $fp) if (!empty($fp['credito'])) $nombresCredito[] = $fp['nombre'];
				throw new Exception('Esta venta se hizo a crédito y el cliente aún debe $' . number_format((int)$ctacte->monto - (int)$ctacte->pagado, 0, ',', '.')
					. ': devuélvela con ' . ($nombresCredito ? implode(' o ', array_unique($nombresCredito)) : 'la forma de pago de crédito') . ' (así baja su deuda)', 409);
			}

			$lineas = array();
			$subtotal = 0;
			$completa = true;
			foreach ($v['lineas'] as $l) {
				$cant = isset($pedidas[$l['id_detalle_venta']]) ? $pedidas[$l['id_detalle_venta']] : 0;
				unset($pedidas[$l['id_detalle_venta']]);
				if ($cant > $l['disponible'] + 0.0001) {
					throw new Exception('De ' . $l['nombre'] . ' solo quedan ' . $l['disponible'] . ' por devolver', 409);
				}
				if ($cant + 0.0001 < $l['disponible']) $completa = false;
				if ($cant <= 0) continue;
				$bruto = (int)round($l['bruto_un'] * $cant);
				$subtotal += $bruto;
				$lineas[] = $l + array('devolver' => $cant, 'bruto' => $bruto);
			}
			if ($pedidas) throw new Exception('Hay productos que no pertenecen a la venta', 400);
			if (!$lineas || $subtotal <= 0) throw new Exception('Indica qué productos se devuelven', 400);

			// Se devuelve lo pagado: con el mismo descuento global de la venta. Si se devuelve
			// todo lo que queda, el total cuadra exacto con lo pagado (sin residuos de redondeo).
			$total = $completa ? $v['disponible'] : min($v['disponible'], (int)round($subtotal * $v['factor']));
			if ($total <= 0) throw new Exception('La venta ya fue devuelta por completo', 409);
			$descuento = $subtotal - $total;
			// Se devuelve lo pagado: la parte exenta en proporción a las líneas exentas devueltas
			$subExento = 0;
			foreach ($lineas as $l) if ($l['exento']) $subExento += $l['bruto'];
			$exento = $subExento >= $subtotal ? $total : (int)round($total * $subExento / $subtotal);
			// La devolución de un documento exento es exenta completa
			if (in_array((int)$v['cod_sii'], array(34, 41))) {
				$exento = $total;
				foreach ($lineas as $i => $l) $lineas[$i]['exento'] = true;
			}
			// v1.20.0: con ILA el afecto se reparte entre IVA e ILA según las líneas devueltas (sin ILA, como siempre)
			$des = $this->desgloseAfecto($lineas, $total - $exento, $config['por_iva'], $v['es_factura']);
			$neto = $des['neto'];
			$iva = $des['iva'];
			$ila = $des['ila'];
			$m = $this->montos($lineas, $descuento, false, $config['por_iva']);

			// La NC electrónica toma su folio de Cloud Apps al emitirse
			$folio = $ncElectronica ? 0 : $this->siguienteFolio($db, $idNc, self::NC_NOMBRE);
			$texto = 'DEVOLUCION ' . $v['documento'] . ' N° ' . $v['folio'] . ': ' . $motivo;

			$idVenta = $this->insertar($db, $cols, 'venta', array(
				'id_tdocumento' => $idNc,
				// La nota de crédito siempre lleva cliente: el de la venta o, si no tiene, el CLIENTE HABITUAL
				'id_cliente' => $v['id_cliente'] > 0 ? $v['id_cliente'] : (int)$config['id_cliente_publico'],
				'id_lprecio' => $v['id_lprecio'],
				'id_usuario' => (int)$idUsuario,
				'fecha' => $dia . ' 00:00:00',
				'fecha_transaccion' => $ahora,
				'esconsignacion' => false,
				'subtotal' => -$subtotal,
				'descuento' => -$descuento,
				'neto' => -$neto,
				'iva' => -$iva,
				'ila' => -$ila,
				'total' => -$total,
				'id_caja' => (int)$caja->id_caja,
				'folio' => $folio,
				'id_usuario_garzon' => (int)$idUsuario,
				'id_mesa' => 0,
				'id_fpagopropina' => 0,
				'valor_propina' => 0,
				'propina_final' => 0,
				'por_comision_propina' => 0,
				'monto_comision_propina' => 0,
				// Mismo % de la venta: f_adddescuentoproducto lo reparte en las líneas
				'por_descuento' => $v['por_descuento'],
				'monto_descuento' => -$descuento,
				'subtotal_condescuento' => -$total,
				'nombre_computador' => self::COMPUTADOR,
				'version_pdv' => self::COMPUTADOR,
				'mensaje_pedido' => mb_substr($texto, 0, 200),
				'observacion' => $texto,
				'es_cortesia' => false,
				'venta_directa' => true,
				'fecha_vencimiento' => $dia,
				'id_tdocumento_referencia' => $v['id_tdocumento'],
				'folio_referencia' => $v['folio'],
				'fecha_referencia' => $v['dia'],
				'id_canal_venta' => $this->primerId($db, 'canal_venta', 'id_canal_venta', 1),
				'monto_exento' => -$exento,
				'monto_afecto' => -($total - $exento),
				'monto_cortesia' => 0,
				'vigente' => true,
				'esventa' => true,
				'subtotal_bruto' => -$subtotal,
				'subtotal_neto' => -$neto,
				'subtotal_neto_sin_descuento' => -array_sum(array_column($m['lineas'], 'neto')),
				'monto_descuento_global' => -$descuento,
				'porcentaje_descuento_global' => $v['por_descuento'],
				'descuento_global_prorrateado_bruto' => -$descuento,
				'descuento_global_prorrateado_neto' => -$descuento / $factorIva,
				'subtotal_neto_con_descuento' => -$neto,
				'monto_iva' => -$iva,
				'total_bruto_con_descuento' => -$total,
				'folio_dte' => 0,
			), 'id_venta');
			$bodegaNc = $this->bodegaDeVenta($db, (int)$v['id_venta']);
			// La nota de crédito va al mismo receptor de la factura (giro, dirección y solicitante elegidos al venderla)
			if ($this->hayReceptor($db)) {
				$this->q($db, 'INSERT INTO venta_receptor (id_venta, id_cliente, razon_social, giro, direccion, id_comuna, comuna, ciudad, contacto,
						rut_solicita, dv_solicita, nombre_solicita, fecha)
					SELECT ?, id_cliente, razon_social, giro, direccion, id_comuna, comuna, ciudad, contacto, rut_solicita, dv_solicita, nombre_solicita, ?
					FROM venta_receptor WHERE id_venta = ? ON CONFLICT (id_venta) DO NOTHING', array((int)$idVenta, $ahora, (int)$v['id_venta']));
			}

			foreach ($lineas as $i => $l) {
				$ml = $m['lineas'][$i];
				$descLinea = $ml['descuento'];
				$conDesc = $ml['con_descuento'];
				$netoLinea = $ml['neto'];
				$netoConDesc = $ml['neto_con_descuento'];
				$this->insertar($db, $cols, 'detalle_venta', array(
					'id_venta' => $idVenta,
					'id_producto' => $l['id_producto'],
					'nombre_producto' => mb_substr($l['nombre'], 0, 200),
					'cantidad' => -$l['devolver'],
					'bruto_un' => $l['bruto_un'],
					'bruto_un_lista' => $l['bruto_un'],
					'precio_unitario_bruto' => $l['bruto_un'],
					'precio_unitario_neto' => $l['bruto_un'] / $ml['factor'],
					'neto_un' => $l['bruto_un'] / $ml['factor'],
					'neto_unitario' => $l['bruto_un'] / $ml['factor'],
					'subtotal' => -$l['bruto'],
					'descuento' => 0,
					'descuento_porcentual' => 0,
					'total_bruto' => -$l['bruto'],
					'neto' => -$netoLinea,
					'iva' => -(int)round($l['bruto'] - $netoLinea - $ml['ila']),
					'ila' => -(int)round($ml['ila']),
					'poriva' => $ml['exento'] ? 0 : $config['por_iva'],
					'porila' => $ml['porila'],
					'espack' => false,
					'es_merma' => false,
					'esmerma' => false,
					'id_agregado' => $l['id_agregado'],
					'id_lprecio' => $v['id_lprecio'],
					'nombre_computador' => self::COMPUTADOR,
					'fecha_transaccion' => $ahora,
					'fecha_pedido' => $ahora,
					'fecha_venta' => $dia,
					'subtotal_neto' => -$netoLinea,
					'subtotal_neto_sin_descuento' => -$netoLinea,
					'monto_descuento_global' => -$descLinea,
					'porcentaje_descuento_global' => $v['por_descuento'],
					'descuento_global_prorrateado_bruto' => -$descLinea,
					'descuento_global_prorrateado_neto' => -($descLinea / $ml['factor']),
					'subtotal_neto_con_descuento' => -$netoConDesc,
					'monto_iva' => -($conDesc - $netoConDesc - $ml['ila_con_descuento']),
					'monto_ila' => -$ml['ila_con_descuento'],
					'total_bruto_con_descuento' => -$conDesc,
					'monto_exento' => $ml['exento'] ? -$conDesc : 0,
				) + ($bodegaNc > 0 ? array('id_bodega' => $bodegaNc) : array()), null);
			}

			// Con trigger: la cantidad negativa devuelve los insumos. Sin trigger: igual, con signo negativo, a la bodega
			// de donde salieron (la de la caja que vendió, Control de cajas; o la de cada producto, como antes)
			if (!$this->tieneTriggerStock($db)) {
				$this->descontarStock($db, $cols, $idVenta, $bodegaNc);
			}

			// Pago negativo: sale de la forma de pago elegida (y si es crédito, baja la deuda vía trigger)
			$this->insertar($db, $cols, 'pago_venta', array(
				'id_cliente' => $v['id_cliente'],
				'id_venta' => $idVenta,
				'id_fpago' => $idFpago,
				'fecha_vencimiento' => $dia,
				'numero_documento' => '',
				'valor_compra' => -$total,
				'paga_con' => -$total,
				'pagado' => 0,
				'propina' => 0,
				'subtotal' => -$total,
				'monto_pagado' => -$total,
				'vuelto' => 0,
				'fecha_movimiento' => $ahora,
				'fecha_pago' => $dia,
			), null);

			// Devolución a crédito: baja la deuda de la venta original (la pestaña Créditos y el plazo miran esa línea).
			// La línea negativa que deja el trigger queda saldada para que la deuda no se descuente dos veces.
			if ($fpagos[$idFpago]['credito'] && $ctacte) {
				$this->q($db, 'UPDATE ctacte_cliente SET pagado = LEAST(monto, COALESCE(pagado, 0) + ?) WHERE id_ctacte_cliente = ?',
					array($total, (int)$ctacte->id_ctacte_cliente));
				$this->q($db, 'UPDATE ctacte_cliente SET pagado = monto WHERE id_venta = ? AND monto < 0', array((int)$idVenta));
			}

			$db->trans_commit();
			log_message('info', 'Caja web: devolución ' . $v['documento'] . ' ' . $v['folio'] . ' por $' . $total . ' (usuario ' . (int)$idUsuario . ')');
		} catch (Throwable $e) {   // 19-11-2026: también errores de PHP deshacen la transacción
			$db->trans_rollback();
			throw $e;
		}

		$dte = null;
		if ($ncElectronica) {
			$dte = $this->emitirDte($db, $idVenta);
			if ($dte['ok']) $folio = $dte['folio'];
		}
		return array('id_venta' => $idVenta, 'folio' => $folio, 'total' => $total, 'documento' => $nombreNc,
			'forma_pago' => $fpagos[$idFpago]['nombre'], 'dte' => $dte);
	}

	/** Tipos de NC: la interna y las electrónicas (cod_sii 61). */
	private function idsNotaCredito($db)
	{
		$ids = array();
		$interna = $this->tipoNotaCredito($db, false);
		if ($interna) $ids[] = $interna;
		foreach ($this->q($db, 'SELECT id_tdocumento FROM tdocumento WHERE cod_sii = ?', array(self::COD_SII_NC))->result() as $r) {
			$ids[] = (int)$r->id_tdocumento;
		}
		return array_values(array_unique($ids));
	}

	/** NC electrónica: la asignada en Configuraciones > Facturación electrónica, y solo con la facturación habilitada. */
	private function idNotaCreditoElectronica($db)
	{
		$config = $this->configuracion($db);
		if (!$config['facturacion_electronica'] || $config['id_nc'] <= 0) return 0;
		// Tiene que ser una nota de crédito electrónica (código SII 61): si en Configuraciones quedó la NC INTERNA,
		// se mandaba al SII un documento interno y Cloud Apps lo rechazaba ("La factura requiere un cliente con RUT")
		$r = $this->q($db, 'SELECT 1 FROM tdocumento WHERE id_tdocumento = ? AND COALESCE(cod_sii, 0) = 61', array((int)$config['id_nc']))->row();
		return $r ? (int)$config['id_nc'] : 0;
	}

	/** La empresa emite por Cloud Apps (credenciales y terminal configurados). */
	private function dtePrueba($db)
	{
		try {
			$c = $this->dte()->configuracion($db);
			return $c && !empty($c['prueba']);
		} catch (Throwable $e) {
			return false;
		}
	}

	private $dteMotivoCache = null;
	private function dteMotivo($db)
	{
		if ($this->dteMotivoCache === null) {
			try { $this->dteMotivoCache = (string)$this->dte()->motivoSinEmision($db); } catch (Throwable $e) { $this->dteMotivoCache = ''; }
			if ($this->dteMotivoCache === '') $this->dteMotivoCache = 'requiere emisión SII';
		}
		return $this->dteMotivoCache;
	}

	private function dteDisponible($db)
	{
		if ($this->dteConfig === null) $this->dteConfig = $this->dte()->configuracion($db) ? 1 : 0;
		return $this->dteConfig === 1;
	}
	private $dteConfig = null;

	/** "IN (1, 2)" para listas de enteros (nunca vacía). */
	private function sqlEn($ids)
	{
		$ids = array_map('intval', (array)$ids);
		return 'IN (' . ($ids ? implode(', ', $ids) : '-1') . ')';
	}

	/** Tipo de documento de la NC interna; se crea la primera vez que se usa (no es para venta ni electrónico). */
	private function tipoNotaCredito($db, $crear)
	{
		$r = $this->q($db, 'SELECT id_tdocumento FROM tdocumento WHERE upper(trim(nombre_tdocumento)) = ? ORDER BY id_tdocumento LIMIT 1',
			array(self::NC_NOMBRE))->row();
		if ($r) return (int)$r->id_tdocumento;
		if (!$crear) return 0;
		$cols = $this->columnas($db, array('tdocumento'));
		return (int)$this->insertar($db, $cols, 'tdocumento', array(
			'nombre_tdocumento' => self::NC_NOMBRE,
			'cod_sii' => 0,
			'folio' => 0,
			'folio_final' => 0,
			'para_venta' => false,
			'para_compra' => false,
			'boleta_fiscal' => false,
			'categoria_boleta' => false,
			'categoria_factura' => false,
			'requiere_cliente' => false,
		), 'id_tdocumento');
	}

	/**
	 * Correlativo tras el mayor folio emitido: si el contador quedó atrás (se reinició o lo movió
	 * otro sistema) no se repiten folios. El UPDATE bloquea la fila, así dos cajas no toman el mismo.
	 */
	private function siguienteFolio($db, $idTdoc, $nombre)
	{
		$r = $this->q($db, 'UPDATE tdocumento SET folio = GREATEST(COALESCE(folio, 0),
				COALESCE((SELECT max(v.folio) FROM venta v WHERE v.id_tdocumento = ?), 0)) + 1
			WHERE id_tdocumento = ?
			RETURNING folio, COALESCE(folio_final, 0) AS folio_final', array($idTdoc, $idTdoc))->row();
		if (!$r) throw new Exception('Tipo de documento no encontrado', 400);
		$folio = (int)$r->folio;
		if ($r->folio_final > 0 && $folio > $r->folio_final) {
			throw new Exception('No quedan folios disponibles para ' . $nombre, 409);
		}
		return $folio;
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

	/**
	 * v1.27.0: insumos vigentes con stock mínimo (insumo.stock_minimo_web > 0) cuyo stock total (todas las bodegas) llegó
	 * a ese número o bajó de él. Los más cerca de acabarse primero. Sin la migración 20261121120000: lista vacía.
	 */
	public function stockBajo()
	{
		$db = $this->conexion();
		$c = $this->columnas($db, array('insumo', 'stock_insumo'));
		if (!isset($c['insumo']['stock_minimo_web'], $c['stock_insumo'])) return array();
		$filas = $this->q($db, "SELECT i.id_insumo, i.nombre_insumo AS nombre, i.stock_minimo_web AS minimo, COALESCE(s.saldo, 0) AS saldo,
				COALESCE(um.nombre_umedida, '') AS unidad
			FROM insumo i
			LEFT JOIN (SELECT id_insumo, sum(saldo) AS saldo FROM stock_insumo GROUP BY id_insumo) s ON s.id_insumo = i.id_insumo
			LEFT JOIN umedida um ON um.id_umedida = i.id_umedida_inventario
			WHERE COALESCE(i.stock_minimo_web, 0) > 0 AND i.vigente IS NOT FALSE AND COALESCE(s.saldo, 0) <= i.stock_minimo_web
			ORDER BY COALESCE(s.saldo, 0) / i.stock_minimo_web, i.nombre_insumo
			LIMIT 30")->result();
		return array_map(function ($f) {
			return array('id_insumo' => (int)$f->id_insumo, 'nombre' => $f->nombre, 'minimo' => round((float)$f->minimo, 3),
				'saldo' => round((float)$f->saldo, 3), 'unidad' => $f->unidad);
		}, $filas);
	}

	private function tieneTriggerStock($db)
	{
		$r = $this->q($db, "SELECT 1 FROM pg_trigger t JOIN pg_proc p ON p.oid = t.tgfoid
			WHERE t.tgrelid = 'public.detalle_venta'::regclass AND p.proname = 'f_addconsolidado' AND NOT t.tgisinternal LIMIT 1")->row();
		return (bool)$r;
	}

	/**
	 * Para bases sin el trigger del POS: mismo efecto que f_addconsolidado.
	 * $idBodega > 0: todo sale de esa bodega (la de la caja, Control de cajas); 0 = la bodega de cada producto (como antes).
	 */
	private function descontarStock($db, $cols, $idVenta, $idBodega = 0)
	{
		foreach ($this->lineasStock($db, $idVenta, $idBodega) as $l) {
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

	/** Devuelve a la bodega de donde salió cada línea (consolidado_ventas); sin registro, a la del producto (como antes). */
	private function revertirStock($db, $idVenta)
	{
		foreach ($this->lineasStock($db, $idVenta, 0, true) as $l) {
			$this->moverStock($db, (int)$l->id_receta, (int)$l->id_bodega, (float)$l->cantidad, -1);
			$this->moverStock($db, (int)$l->id_receta_agregado, (int)$l->id_bodega, (float)$l->cantidad, -1);
		}
	}

	private function lineasStock($db, $idVenta, $idBodega = 0, $segunConsolidado = false)
	{
		$bodega = 'COALESCE(p.id_bodega, 0)';
		if ($idBodega > 0) {
			$bodega = (int)$idBodega;
		} elseif ($segunConsolidado) {
			$c = $this->columnas($db, array('consolidado_ventas'));
			if (isset($c['consolidado_ventas']['id_dventa'], $c['consolidado_ventas']['id_bodega'])) {
				$bodega = 'COALESCE((SELECT cv.id_bodega FROM consolidado_ventas cv WHERE cv.id_dventa = d.id_detalle_venta AND cv.id_bodega > 0
					ORDER BY cv.id_bodega LIMIT 1), p.id_bodega, 0)';
			}
		}
		return $this->q($db, 'SELECT d.id_detalle_venta, d.id_producto, d.cantidad, COALESCE(p.id_receta, 0) AS id_receta,
				' . $bodega . ' AS id_bodega, COALESCE(pa.id_receta, 0) AS id_receta_agregado
			FROM detalle_venta d
			JOIN producto p ON p.id_producto = d.id_producto
			LEFT JOIN producto pa ON pa.id_producto = d.id_agregado AND d.id_agregado > 0
			WHERE d.id_venta = ?', array((int)$idVenta))->result();
	}

	/**
	 * Bodega de donde salió el stock de una venta, para devolverlo ahí con la nota de crédito: la única bodega de su
	 * consolidado_ventas; si salió de varias (bodega de cada producto) o no hay registro, 0 = la de cada producto (como antes).
	 * Sin consolidado, la bodega de la caja con que se vendió (Control de cajas).
	 */
	private function bodegaDeVenta($db, $idVenta)
	{
		$c = $this->columnas($db, array('consolidado_ventas', 'venta', 'caja', 'control_caja'));
		if (isset($c['consolidado_ventas']['id_venta'], $c['consolidado_ventas']['id_bodega'])) {
			$r = $this->q($db, 'SELECT min(id_bodega) AS b, count(DISTINCT id_bodega) AS n FROM consolidado_ventas
				WHERE id_venta = ? AND id_bodega > 0', array((int)$idVenta))->row();
			if ($r && (int)$r->n > 0) return (int)$r->n === 1 ? (int)$r->b : 0;
		}
		if (isset($c['venta']['id_caja'], $c['caja']['id_control_caja'], $c['control_caja']['id_bodega'])) {
			$r = $this->q($db, 'SELECT COALESCE(cc.id_bodega, 0) AS b FROM venta v JOIN caja c ON c.id_caja = v.id_caja
				JOIN control_caja cc ON cc.id_control_caja = c.id_control_caja WHERE v.id_venta = ?', array((int)$idVenta))->row();
			if ($r && (int)$r->b > 0 && $this->q($db, 'SELECT 1 FROM bodega WHERE id_bodega = ?', array((int)$r->b))->row()) return (int)$r->b;
		}
		return 0;
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

	// ====================== Delivery (18-11-2026) ======================
	// Módulo aparte de venta directa y mesas, sin mesa (pedido del usuario). Tabla propia de la web, delivery_pedido
	// (migración 20261118100000); el POS de escritorio no lo usa. En cada pedido se elige:
	// - cobrar ahora: es una venta directa con los datos de entrega (registrarVenta con $p['delivery']);
	// - cobrar al entregar: se guarda el pedido y sale la comanda (guardarDeliveryPendiente); al cobrarlo es una venta
	//   directa con lo guardado (registrarVenta con $p['id_delivery']) y no vuelve a salir la comanda.
	// El costo de envío es una línea de la boleta: el producto y el valor de Configuraciones > Delivery.

	const DELIVERY_ESTADOS = array('preparando', 'en_camino', 'entregado', 'anulado');

	private function hayDelivery($db)
	{
		return isset($this->columnas($db, array('delivery_pedido'))['delivery_pedido']);
	}

	/** Lo que la pestaña Delivery necesita (sectores, repartidores y el envío); null si el delivery está apagado. */
	private function datosDelivery($db, $config)
	{
		if (empty($config['delivery']) || !$this->hayDelivery($db)) return null;
		$sectores = array();
		if (isset($this->columnas($db, array('sector'))['sector'])) {
			foreach ($this->q($db, "SELECT id_sector, nombre_sector FROM sector WHERE trim(COALESCE(nombre_sector, '')) <> '' ORDER BY nombre_sector")->result() as $s) {
				$sectores[] = array('id' => (int)$s->id_sector, 'nombre' => (string)$s->nombre_sector);
			}
		}
		$repartidores = array();
		foreach ($this->q($db, "SELECT id_usuario, nombre_usuario FROM usuario WHERE vigente IS NOT FALSE AND trim(COALESCE(nombre_usuario, '')) <> ''
			ORDER BY nombre_usuario")->result() as $u) {
			$repartidores[] = array('id' => (int)$u->id_usuario, 'nombre' => (string)$u->nombre_usuario);
		}
		$envio = null;
		try {
			$l = $this->lineaEnvio($db, $config);
			if ($l) {
				$l = $this->conIla($db, array($l))[0];   // v1.20.0: la pantalla calcula el envío con su ILA, como el servidor
				$envio = array('id_producto' => $l['id_producto'], 'nombre' => $l['nombre'], 'monto' => $l['bruto'], 'exento' => $l['exento'], 'ila' => $l['ila']);
			}
		} catch (Exception $e) {
			$envio = null;   // producto del envío borrado: sin envío (al cobrar se avisa)
		}
		return array('sectores' => $sectores, 'repartidores' => $repartidores, 'envio' => $envio);
	}

	/**
	 * Línea de la boleta con el costo de envío (Configuraciones > Delivery: producto y valor). $monto = el guardado en un
	 * pedido pendiente (null = el de Configuraciones). null si no hay envío.
	 */
	private function lineaEnvio($db, $config, $monto = null)
	{
		$monto = $monto === null ? (int)$config['delivery_costo_envio'] : (int)$monto;
		$id = (int)$config['delivery_producto_envio'];
		if ($monto <= 0 || $id <= 0) return null;
		$p = $this->q($db, 'SELECT p.id_producto, p.nombre_producto, ' . $this->sqlExento($db, 'p') . ' AS exento FROM producto p WHERE p.id_producto = ?',
			array($id))->row();
		if (!$p) throw new Exception('El producto del costo de envío (Configuraciones > Delivery) ya no existe: elige otro', 409);
		return array(
			'id_producto' => $id, 'nombre' => (string)$p->nombre_producto, 'cantidad' => 1, 'unitario' => $monto, 'bruto' => $monto,
			'id_agregado' => 0, 'nota' => '', 'segun_stock' => false, 'recetas' => array(), 'exento' => $p->exento === 't' || $p->exento === true,
			'es_envio' => true,
		);
	}

	/** Datos de entrega revisados (nombre, teléfono, dirección obligatorios; sector y repartidor deben existir). */
	private function datosEntrega($db, $d)
	{
		$d = is_array($d) ? $d : array();
		$texto = function ($k, $max) use ($d) {
			return mb_substr(trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string)(isset($d[$k]) && is_scalar($d[$k]) ? $d[$k] : ''))), 0, $max);
		};
		$e = array(
			'nombre' => $texto('nombre', 120), 'telefono' => $texto('telefono', 30), 'direccion' => $texto('direccion', 250),
			'referencia' => $texto('referencia', 250), 'observacion' => $texto('observacion', 250),
			'id_sector' => (int)(isset($d['id_sector']) ? $d['id_sector'] : 0), 'id_repartidor' => (int)(isset($d['id_repartidor']) ? $d['id_repartidor'] : 0),
		);
		if ($e['nombre'] === '') throw new Exception('Falta el nombre de quien recibe el delivery', 400);
		if (!preg_match('/^\+?[0-9][0-9 ()-]{5,}$/', $e['telefono'])) throw new Exception('Falta el teléfono del delivery (solo números)', 400);
		if ($e['direccion'] === '') throw new Exception('Falta la dirección del delivery', 400);
		if ($e['id_sector'] && !$this->q($db, 'SELECT 1 FROM sector WHERE id_sector = ?', array($e['id_sector']))->row()) {
			throw new Exception('El sector elegido ya no existe', 400);
		}
		if ($e['id_repartidor'] && !$this->q($db, 'SELECT 1 FROM usuario WHERE id_usuario = ? AND vigente IS NOT FALSE', array($e['id_repartidor']))->row()) {
			throw new Exception('El repartidor elegido ya no existe o no está vigente', 400);
		}
		return $e;
	}

	/** Lo pedido tal como llega de la pantalla, solo lo necesario para volver a armarlo al cobrar. */
	private function detalleDelivery($items)
	{
		$res = array();
		foreach (is_array($items) ? $items : array() as $it) {
			if (!is_array($it) || empty($it['id_producto'])) continue;
			$res[] = array(
				'id_producto' => (int)$it['id_producto'], 'cantidad' => round((float)(isset($it['cantidad']) ? $it['cantidad'] : 0), 3),
				'agregados' => array_values(array_map('intval', isset($it['agregados']) && is_array($it['agregados']) ? $it['agregados'] : array())),
				'nota' => mb_substr(trim((string)(isset($it['nota']) && is_scalar($it['nota']) ? $it['nota'] : '')), 0, 200),
			);
			// 19-11-2026: mensaje de cada agregado {id_agregado: texto}
			$na = array();
			foreach (isset($it['notas_agregados']) && is_array($it['notas_agregados']) ? $it['notas_agregados'] : array() as $k => $t) {
				if (is_scalar($t) && trim((string)$t) !== '' && count($na) < 20) $na[(string)(int)$k] = mb_substr(trim((string)$t), 0, 60);
			}
			if ($na) $res[count($res) - 1]['notas_agregados'] = $na;
			// v1.25.0: lo armado de una promo de sushi (armarLineas lo valida)
			if (isset($it['sushi']) && is_array($it['sushi'])) $res[count($res) - 1]['sushi'] = $it['sushi'];
			if (count($res) >= 200) break;
		}
		return $res;
	}

	/** Graba el pedido (dentro de la transacción de quien llama). Devuelve id_delivery. */
	private function insertarDelivery($db, $e, $extra)
	{
		$r = $this->q($db, 'INSERT INTO delivery_pedido (id_caja, id_usuario, id_cliente, nombre, telefono, direccion, referencia, id_sector,
				observacion, id_lprecio, detalle, subtotal, costo_envio, total, cobrar_al_entregar, id_venta, estado, id_repartidor, fecha_estado)
			VALUES (?, ?, ?, ?, ?, ?, ?, NULLIF(?, 0), ?, ?, ?::jsonb, ?, ?, ?, ?, ?, \'preparando\', NULLIF(?, 0), now()) RETURNING id_delivery',
			array((int)$extra['id_caja'], (int)$extra['id_usuario'], $extra['id_cliente'] ? (int)$extra['id_cliente'] : null,
				$e['nombre'], $e['telefono'], $e['direccion'], $e['referencia'], $e['id_sector'], $e['observacion'], (int)$extra['id_lprecio'],
				json_encode($extra['detalle']), (int)$extra['subtotal'], (int)$extra['costo_envio'], (int)$extra['total'],
				!empty($extra['cobrar_al_entregar']) ? 't' : 'f', $extra['id_venta'] ? (int)$extra['id_venta'] : null, $e['id_repartidor']))->row();
		return (int)$r->id_delivery;
	}

	/**
	 * Delivery a cobrar al entregar: se guarda el pedido (con los precios del servidor) y se devuelve la comanda para
	 * imprimirla. El descuento se aplica al cobrarlo. Devuelve {id_delivery, total, comanda, observacion}.
	 */
	public function guardarDeliveryPendiente($caja, $idUsuario, $idRol, $p)
	{
		$db = $this->conexion();
		$config = $this->configuracion($db);
		if (empty($config['delivery']) || !$this->hayDelivery($db)) throw new Exception('El delivery no está habilitado (Configuraciones > Delivery)', 409);
		$cols = $this->columnas($db, array('producto'));
		$entrega = $this->datosEntrega($db, isset($p['delivery']) ? $p['delivery'] : null);
		$idLprecio = !empty($p['id_lprecio']) ? (int)$p['id_lprecio'] : $config['id_lprecio_publico'];
		$idCliente = (int)(isset($p['id_cliente']) ? $p['id_cliente'] : 0);
		if ($idCliente && !$this->q($db, 'SELECT 1 FROM cliente WHERE id_cliente = ?', array($idCliente))->row()) throw new Exception('El cliente no existe', 400);
		$detalle = $this->detalleDelivery(isset($p['detalle']) ? $p['detalle'] : null);
		list($lineas, $subtotal, $notas, $comanda) = $this->armarLineas($db, $config, $cols, $detalle, $idLprecio);
		$this->validarStock($db, $lineas);
		$envio = $this->lineaEnvio($db, $config);
		$costoEnvio = $envio ? (int)$envio['bruto'] : 0;
		// 18-11-2026: el descuento puesto al tomar el pedido queda guardado (antes se perdía y había que ponerlo de nuevo al
		// cobrar). Mismas reglas que al vender; la autorización se pide ahora y al cobrarlo no se vuelve a pedir.
		$descuento = max(0, (int)(isset($p['descuento']) ? $p['descuento'] : 0));
		if ($descuento > 0) {
			if (!$this->hayDescuentoDelivery($db)) {
				throw new Exception('Para guardar un delivery con descuento falta aplicar las migraciones (php index.php despliegue esquema)', 409);
			}
			$bruto = $subtotal + $costoEnvio;
			if ($descuento > $bruto) throw new Exception('El descuento no puede superar el subtotal', 400);
			if ($config['por_maximo_descuento'] > 0 && $descuento > (int)round($bruto * $config['por_maximo_descuento'] / 100)) {   // v1.24.1
				throw new Exception('El descuento máximo permitido es ' . $config['por_maximo_descuento'] . '%', 400);
			}
			if ($config['descuento_cliente'] && (!$idCliente || $idCliente === $config['id_cliente_publico'])) {
				throw new Exception('Para hacer un descuento debes elegir el cliente de la venta (Configuraciones > Descuentos > Requerir cliente)', 400);
			}
			if ($config['admin_descuento']) {
				$this->exigirAutorizacion($db, 'descuento', $idUsuario, $idRol, isset($p['autorizacion']) ? $p['autorizacion'] : null);
			}
		}
		$total = $subtotal + $costoEnvio - $descuento;
		$db->trans_begin();
		try {
			$id = $this->insertarDelivery($db, $entrega, array('id_caja' => (int)$caja->id_caja, 'id_usuario' => (int)$idUsuario,
				'id_cliente' => $idCliente, 'id_lprecio' => $idLprecio, 'detalle' => $detalle, 'subtotal' => $subtotal,
				'costo_envio' => $costoEnvio, 'total' => $total, 'cobrar_al_entregar' => true, 'id_venta' => null));
			$this->guardarDescuentoDelivery($db, $id, $descuento);
			$db->trans_commit();
		} catch (Throwable $e) {   // 19-11-2026: también errores de PHP deshacen la transacción
			$db->trans_rollback();
			throw $e;
		}
		return array('id_delivery' => $id, 'total' => $total, 'comanda' => $comanda, 'observacion' => $entrega['observacion'],
			'entrega' => $this->entregaComanda($entrega));
	}

	/** delivery_pedido.descuento (migración 20261118120000). */
	private function hayDescuentoDelivery($db)
	{
		$c = $this->columnas($db, array('delivery_pedido'));
		return isset($c['delivery_pedido']['descuento']);
	}

	/** Graba el descuento del pedido (NULL = sin descuento). Sin la columna no hace nada (el delivery funciona igual). */
	private function guardarDescuentoDelivery($db, $idDelivery, $descuento)
	{
		if (!$this->hayDescuentoDelivery($db)) return;
		$this->q($db, 'UPDATE delivery_pedido SET descuento = NULLIF(?, 0) WHERE id_delivery = ?', array(max(0, (int)$descuento), (int)$idDelivery));
	}

	/** 18-11-2026: lo de la entrega que va impreso en la comanda del delivery. */
	private function entregaComanda($e)
	{
		return array('direccion' => $e['direccion'], 'referencia' => $e['referencia'], 'telefono' => $e['telefono']);
	}

	/**
	 * Pedidos para la pestaña Delivery: los de hoy y los que siguen abiertos (no entregados ni anulados) de otros días.
	 * $todos = true: también los entregados y anulados de hoy (si no, solo los abiertos).
	 */
	public function listarDelivery($todos = false)
	{
		$db = $this->conexion();
		if (!$this->hayDelivery($db)) return array();
		$tieneSector = isset($this->columnas($db, array('sector'))['sector']);
		$q = "SELECT d.id_delivery, to_char(d.fecha AT TIME ZONE ?, 'DD/MM HH24:MI') AS hora, d.nombre, d.telefono, d.direccion, d.referencia,
				d.id_sector, " . ($tieneSector ? 's.nombre_sector' : "''") . " AS sector, d.observacion, d.detalle, d.subtotal, d.costo_envio, d.total,
				d.cobrar_al_entregar, d.id_venta, d.estado, d.id_repartidor, r.nombre_usuario AS repartidor, u.nombre_usuario AS atendio,
				to_char(d.fecha_entrega AT TIME ZONE ?, 'HH24:MI') AS hora_entrega, d.motivo_anulacion,
				v.folio_dte, td.nombre_tdocumento AS documento, (COALESCE(v.mensaje_pedido, '') LIKE 'ANULADA%') AS venta_anulada,
				(SELECT string_agg(f.nombre_fpago, ', ' ORDER BY f.nombre_fpago) FROM pago_venta pv JOIN fpago f ON f.id_fpago = pv.id_fpago
					WHERE pv.id_venta = d.id_venta) AS formas_pago
			FROM delivery_pedido d
			" . ($tieneSector ? 'LEFT JOIN sector s ON s.id_sector = d.id_sector' : '') . "
			LEFT JOIN usuario r ON r.id_usuario = d.id_repartidor
			LEFT JOIN usuario u ON u.id_usuario = d.id_usuario
			LEFT JOIN venta v ON v.id_venta = d.id_venta
			LEFT JOIN tdocumento td ON td.id_tdocumento = v.id_tdocumento
			WHERE (d.estado NOT IN ('entregado', 'anulado') AND d.fecha > now() - interval '7 days')"
			. ($todos ? " OR (d.fecha AT TIME ZONE ?)::date = (now() AT TIME ZONE ?)::date" : '') . "
			ORDER BY d.id_delivery DESC LIMIT 300";
		$binds = array(self::TZ, self::TZ);
		if ($todos) { $binds[] = self::TZ; $binds[] = self::TZ; }
		// Nombres de los productos para mostrar lo pedido y armar el mensaje de WhatsApp
		$filas = $this->q($db, $q, $binds)->result_array();
		$ids = array();
		foreach ($filas as $f) foreach ((array)json_decode((string)$f['detalle'], true) as $it) $ids[(int)$it['id_producto']] = true;
		$nombres = array();
		if ($ids) {
			foreach ($this->q($db, 'SELECT id_producto, nombre_producto FROM producto WHERE id_producto IN (' . implode(',', array_map('intval', array_keys($ids))) . ')')->result() as $pr) {
				$nombres[(int)$pr->id_producto] = (string)$pr->nombre_producto;
			}
		}
		foreach ($filas as &$f) {
			$items = array();
			foreach ((array)json_decode((string)$f['detalle'], true) as $it) {
				$items[] = array('nombre' => isset($nombres[(int)$it['id_producto']]) ? $nombres[(int)$it['id_producto']] : ('Producto ' . (int)$it['id_producto']),
					'cantidad' => (float)$it['cantidad'], 'nota' => (string)(isset($it['nota']) ? $it['nota'] : ''));
			}
			$f['items'] = $items;
			unset($f['detalle']);
			foreach (array('id_delivery', 'id_sector', 'subtotal', 'costo_envio', 'total', 'id_venta', 'id_repartidor', 'folio_dte') as $k) $f[$k] = (int)$f[$k];
			$f['cobrar_al_entregar'] = $f['cobrar_al_entregar'] === 't' || $f['cobrar_al_entregar'] === true;
			$f['venta_anulada'] = $f['venta_anulada'] === 't' || $f['venta_anulada'] === true;
			$f['cobrado'] = $f['id_venta'] > 0 && !$f['venta_anulada'];
		}
		unset($f);
		return $filas;
	}

	/** Lo guardado de un pedido pendiente, para cargarlo en la venta y cobrarlo. */
	public function deliveryParaCobrar($idDelivery)
	{
		$db = $this->conexion();
		if (!$this->hayDelivery($db)) throw new Exception('El delivery no está habilitado', 409);
		$d = $this->q($db, 'SELECT * FROM delivery_pedido WHERE id_delivery = ?', array((int)$idDelivery))->row_array();
		if (!$d) throw new Exception('El delivery no existe', 404);
		if ($d['estado'] === 'anulado') throw new Exception('El delivery está anulado', 409);
		if ((int)$d['id_venta']) throw new Exception('El delivery ya está cobrado', 409);
		return array('id_delivery' => (int)$d['id_delivery'], 'id_lprecio' => (int)$d['id_lprecio'], 'id_cliente' => (int)$d['id_cliente'],
			'detalle' => (array)json_decode((string)$d['detalle'], true), 'costo_envio' => (int)$d['costo_envio'], 'nombre' => $d['nombre'],
			'observacion' => (string)$d['observacion'],
			// 18-11-2026: el descuento puesto al tomarlo, y el cliente (para mostrarlo en la venta al cobrar)
			'descuento' => isset($d['descuento']) ? (int)$d['descuento'] : 0,
			'cliente' => (int)$d['id_cliente'] ? $this->q($db, "SELECT cl.id_cliente, cl.nombre_cliente, COALESCE(cl.rut::text, '') AS rut, COALESCE(cl.dv, '') AS dv,
					COALESCE(cl.giro, '') AS giro, COALESCE(cl.direccion, '') AS direccion, COALESCE(cl.id_comuna, 0) AS id_comuna,
					COALESCE(cl.telefono, '') AS telefono, COALESCE(cl.mail, '') AS mail
				FROM cliente cl WHERE cl.id_cliente = ?", array((int)$d['id_cliente']))->row_array() : null);
	}

	/**
	 * Cambia el estado: preparando → en_camino → entregado (entregado solo cobrado), o anulado (solo sin cobrar, o con la
	 * boleta ya anulada en Ventas). $idRepartidor > 0 lo cambia (null = como estaba).
	 */
	public function cambiarEstadoDelivery($idDelivery, $estado, $idRepartidor, $motivo)
	{
		$db = $this->conexion();
		if (!$this->hayDelivery($db)) throw new Exception('El delivery no está habilitado', 409);
		if (!in_array($estado, self::DELIVERY_ESTADOS, true)) throw new Exception('Estado inválido', 400);
		$db->trans_begin();
		try {
			$d = $this->q($db, "SELECT d.id_delivery, d.estado, d.id_venta, (COALESCE(v.mensaje_pedido, '') LIKE 'ANULADA%') AS venta_anulada
				FROM delivery_pedido d LEFT JOIN venta v ON v.id_venta = d.id_venta WHERE d.id_delivery = ? FOR UPDATE OF d", array((int)$idDelivery))->row();
			if (!$d) throw new Exception('El delivery no existe', 404);
			if ($d->estado === 'anulado') throw new Exception('El delivery ya está anulado', 409);
			$cobrado = (int)$d->id_venta > 0 && !($d->venta_anulada === 't' || $d->venta_anulada === true);
			if ($estado === 'entregado' && !$cobrado) throw new Exception('Cobra el delivery antes de marcarlo entregado', 409);
			if ($estado === 'anulado' && $cobrado) {
				throw new Exception('Este delivery ya está cobrado: anula primero su boleta en Opciones > Ventas, y después el delivery', 409);
			}
			if ($idRepartidor !== null && $idRepartidor > 0 && !$this->q($db, 'SELECT 1 FROM usuario WHERE id_usuario = ? AND vigente IS NOT FALSE', array((int)$idRepartidor))->row()) {
				throw new Exception('El repartidor elegido ya no existe o no está vigente', 400);
			}
			$motivo = mb_substr(trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string)$motivo)), 0, 200);
			if ($estado === 'anulado' && $motivo === '') throw new Exception('Indica el motivo de la anulación', 400);
			$this->q($db, 'UPDATE delivery_pedido SET estado = ?, fecha_estado = now(),
					fecha_entrega = CASE WHEN ? = \'entregado\' THEN now() ELSE fecha_entrega END,
					id_repartidor = CASE WHEN ? THEN NULLIF(?, 0) ELSE id_repartidor END,
					motivo_anulacion = CASE WHEN ? = \'anulado\' THEN ? ELSE motivo_anulacion END
				WHERE id_delivery = ?',
				array($estado, $estado, $idRepartidor !== null ? 't' : 'f', (int)$idRepartidor, $estado, $motivo, (int)$idDelivery));
			$db->trans_commit();
		} catch (Throwable $e) {   // 19-11-2026: también errores de PHP deshacen la transacción
			$db->trans_rollback();
			throw $e;
		}
		return array('ok' => true);
	}

	// ====================== Mesas ======================

	/**
	 * Modo de la Caja web del cliente (Configuraciones): 'directa' (sin mesas), 'mesas' (solo se vende por mesa) o 'ambos'.
	 * Sin configurar se comporta como antes: 'ambos'. Un cliente sin tablas de mesas siempre es 'directa'.
	 */
	public function modoCaja($db = null)
	{
		$db = $db ?: $this->conexion();
		if (!$this->tieneMesas($db)) return 'directa';
		$c = $this->columnas($db, array('configuracion'));
		$modo = '';
		if (isset($c['configuracion']['modo_caja_web'])) {
			$r = $this->q($db, "SELECT trim(COALESCE(modo_caja_web, '')) AS m FROM configuracion LIMIT 1")->row();
			$modo = $r ? $r->m : '';
		}
		return in_array($modo, array('directa', 'mesas', 'ambos'), true) ? $modo : 'ambos';
	}

	/** Las tablas de mesas existen en este cliente. */
	private function tieneMesas($db)
	{
		return count($this->columnas($db, array('mesa', 'cotizacion', 'detalle_cotizacion'))) === 3;
	}

	/**
	 * Pedido abierto de una mesa o null.
	 * 14-11-2026 (API2 de la app de garzones): la app puede dejar mesa.id_cotizacion = 0 con el pedido abierto
	 * (desbloqueomesa.php) y lo vuelve a encontrar por cotizacion.id_mesa (verificarMesaNuevo.php). La web hace lo
	 * mismo: si la mesa no apunta a un pedido, busca el último pedido abierto CON productos de esa mesa (no las partes de
	 * dividir cuenta) y vuelve a amarrar la mesa, para no abrir un segundo pedido encima del de la app.
	 */
	private function cotizacionDeMesa($db, $idMesa)
	{
		$c = $this->q($db, "SELECT c.* FROM mesa m JOIN cotizacion c ON c.id_cotizacion = m.id_cotizacion
			WHERE m.id_mesa = ? AND m.id_cotizacion > 0 AND c.pagado IS NOT TRUE AND c.vigente IS NOT FALSE
				AND c.eseliminado IS NOT TRUE AND c.esanulado IS NOT TRUE", array((int)$idMesa))->row();
		if ($c) return $c;
		$c = $this->q($db, "SELECT c.* FROM mesa m JOIN cotizacion c ON c.id_mesa = m.id_mesa
			WHERE m.id_mesa = ? AND COALESCE(m.id_cotizacion, 0) = 0 AND COALESCE(c.id_divpedido, 0) = 0
				AND c.pagado IS NOT TRUE AND c.vigente IS NOT FALSE AND c.eseliminado IS NOT TRUE AND c.esanulado IS NOT TRUE
				AND EXISTS (SELECT 1 FROM detalle_cotizacion d WHERE d.id_cotizacion = c.id_cotizacion
					AND d.vigente IS NOT FALSE AND d.esanulado IS NOT TRUE AND d.eseliminado IS NOT TRUE)
			ORDER BY c.id_cotizacion DESC LIMIT 1", array((int)$idMesa))->row();
		if ($c) {
			$this->q($db, 'UPDATE mesa SET id_cotizacion = ?, disponible = false WHERE id_mesa = ? AND COALESCE(id_cotizacion, 0) = 0',
				array((int)$c->id_cotizacion, (int)$idMesa));
		}
		return $c;
	}

	/**
	 * 14-11-2026 (API2): pedidos "fantasma" de una mesa. La app manda primero la cabecera y después las líneas, sin
	 * transacción: si falla una línea queda un pedido abierto sin productos (con el total que mandó el teléfono), la
	 * mesa aparece ocupada y no se puede ni cobrar ni liberar; y aunque se libere, verificarMesaNuevo.php lo vuelve a
	 * amarrar a la mesa. Se cierran los pedidos de la mesa sin productos vigentes que tengan más de 2 minutos (las
	 * líneas de un envío en curso llegan en milisegundos). No toca las partes de dividir cuenta.
	 */
	private function cerrarPedidosVacios($db, $idMesa, $sinEspera = false)
	{
		$cols = $this->columnas($db, array('cotizacion'));
		$motivo = isset($cols['cotizacion']['motivo_anulacion'])
			? ", motivo_anulacion = 'Pedido sin productos (envío incompleto de la app), cerrado por la Caja web'" : '';
		$this->q($db, "UPDATE cotizacion c SET vigente = false, eseliminado = true$motivo
			WHERE c.id_mesa = ? AND COALESCE(c.id_divpedido, 0) = 0
				AND c.pagado IS NOT TRUE AND c.vigente IS NOT FALSE AND c.eseliminado IS NOT TRUE AND c.esanulado IS NOT TRUE
				AND (? OR c.fecha_transaccion < now() - interval '2 minutes')
				AND NOT EXISTS (SELECT 1 FROM detalle_cotizacion d WHERE d.id_cotizacion = c.id_cotizacion
					AND d.vigente IS NOT FALSE AND d.esanulado IS NOT TRUE AND d.eseliminado IS NOT TRUE)", array((int)$idMesa, (bool)$sinEspera));
	}

	private function bloquearMesa($db, $idMesa)
	{
		$m = $this->q($db, 'SELECT * FROM mesa WHERE id_mesa = ? AND vigente IS NOT FALSE FOR UPDATE', array((int)$idMesa))->row();
		if (!$m) throw new Exception('La mesa no existe o no está vigente', 404);
		return $m;
	}

	private function desocuparMesa($db, $idMesa)
	{
		$this->cerrarPedidosVacios($db, $idMesa);
		$this->q($db, 'UPDATE mesa SET disponible = true, id_cotizacion = 0, id_garzon = 0, bloqueo_caja = false, bloqueo_pedido = false
			WHERE id_mesa = ?', array((int)$idMesa));
	}

	/** Líneas vigentes de un pedido, con la misma forma que armarLineas(). El stock se descuenta al cobrar (trigger de venta). */
	private function lineasDeCotizacion($db, $idCotizacion)
	{
		// v1.24.0: menús (es_menu / es_detalle_menu) y de qué menú es cada producto elegido
		$cd = $this->columnas($db, array('detalle_cotizacion'));
		$cd = isset($cd['detalle_cotizacion']) ? $cd['detalle_cotizacion'] : array();
		$selMenu = (isset($cd['es_menu']) ? '(d.es_menu IS TRUE)' : 'false') . ' AS es_menu, '
			. (isset($cd['es_detalle_menu']) ? '(d.es_detalle_menu IS TRUE)' : 'false') . ' AS es_detalle_menu, '
			. (isset($cd['id_menu']) ? 'COALESCE(d.id_menu, 0)' : '0') . ' AS id_menu, '
			. (isset($cd['id_detalle_menu_web']) ? 'COALESCE(d.id_detalle_menu_web, 0)' : '0') . ' AS menu_de, '
			. (isset($cd['imprimir']) ? '(d.imprimir IS NOT FALSE)' : 'true') . ' AS imprimir, ';
		$rows = $this->q($db, "SELECT $selMenu d.id_detalle_cotizacion, d.id_producto, COALESCE(p.nombre_producto, 'Producto ' || d.id_producto) AS nombre,
				pa.nombre_producto AS agregado, d.cantidad, COALESCE(d.bruto_un, 0) AS bruto_un, COALESCE(d.total_bruto, 0) AS total_bruto,
				COALESCE(d.id_agregado, 0) AS id_agregado, COALESCE(d.mensaje, '') AS nota, " . $this->sqlExento($db, 'p') . " AS exento, " . $this->sqlIla($db, 'p') . " AS ila,
				to_char(d.fecha_movimiento, 'HH24:MI') AS hora
			FROM detalle_cotizacion d
			LEFT JOIN producto p ON p.id_producto = d.id_producto
			LEFT JOIN producto pa ON pa.id_producto = d.id_agregado AND d.id_agregado > 0
			WHERE d.id_cotizacion = ? AND d.vigente IS NOT FALSE AND d.esanulado IS NOT TRUE AND d.eseliminado IS NOT TRUE
			ORDER BY d.id_detalle_cotizacion", array((int)$idCotizacion))->result();
		$lineas = array();
		$si = function ($v) { return $v === true || $v === 't'; };
		$ultimoMenu = 0;
		$vigentes = array();
		foreach ($rows as $r) $vigentes[(int)$r->id_detalle_cotizacion] = true;
		foreach ($rows as $r) {
			$cantidad = (float)$r->cantidad;
			$bruto = (float)$r->total_bruto > 0 ? (int)round((float)$r->total_bruto) : (int)round((float)$r->bruto_un * $cantidad);
			// v1.24.0: el menú de un producto elegido; sin la columna (o de otro sistema), el último menú anterior
			$esMenu = $si($r->es_menu);
			$esDetMenu = $si($r->es_detalle_menu);
			if ($esMenu) $ultimoMenu = (int)$r->id_detalle_cotizacion;
			$menuDe = 0;
			if ($esDetMenu) {
				$menuDe = (int)$r->menu_de > 0 ? (int)$r->menu_de : $ultimoMenu;
				if (!isset($vigentes[$menuDe])) $menuDe = 0;   // su menú ya no está (anulado): queda suelto
			} elseif (!$esMenu) {
				$ultimoMenu = 0;
			}
			$lineas[] = array(
				'es_menu' => $esMenu, 'es_detalle_menu' => $esDetMenu, 'id_menu' => (int)$r->id_menu, 'menu_de' => $menuDe,
				'imprimir' => $si($r->imprimir),
				'id_detalle' => (int)$r->id_detalle_cotizacion, 'id_producto' => (int)$r->id_producto, 'nombre' => $r->nombre,
				'agregado' => (string)$r->agregado, 'cantidad' => $cantidad, 'unitario' => (int)round((float)$r->bruto_un), 'bruto' => $bruto,
				// Monto sin redondear (el POS guarda decimales): las partes se calculan sobre él, igual en todos lados
				'bruto_exacto' => (float)$r->total_bruto > 0 ? (float)$r->total_bruto : (float)$r->bruto_un * $cantidad,
				'id_agregado' => (int)$r->id_agregado, 'nota' => $r->nota, 'hora' => $r->hora, 'segun_stock' => false, 'recetas' => array(),
				'exento' => $r->exento === 't' || $r->exento === true,
				'ila' => ($r->exento === 't' || $r->exento === true) ? 0 : (float)$r->ila,   // v1.20.0
			);
		}
		return $lineas;
	}

	/** Mesas vigentes con su estado (libre, ocupada o bloqueada), agrupables por recinto. */
	public function mesas()
	{
		$db = $this->conexion();
		if (!$this->tieneMesas($db)) return array('habilitado' => false, 'mesas' => array());
		// Modo venta directa: sin pestaña Mesas, salvo que queden mesas abiertas de antes (para cobrarlas o anularlas)
		$modo = $this->modoCaja($db);
		if ($modo === 'directa' && !$this->mesasAbiertas($db)) return array('habilitado' => false, 'mesas' => array(), 'modo' => $modo);
		// La mesa técnica de venta directa no es una mesa real
		$cfg = $this->columnas($db, array('configuracion'));
		$directa = 0;
		if (isset($cfg['configuracion']['id_mesa_venta_directa'])) {
			$c = $this->q($db, 'SELECT id_mesa_venta_directa AS id FROM configuracion LIMIT 1')->row();
			$directa = $c ? (int)$c->id : 0;
		}
		// La posición en el plano es opcional: la tabla se crea al guardar el primer plano
		$plano = $this->columnas($db, array('mesa_plano'));
		$conPlano = isset($plano['mesa_plano']);
		$selPlano = $conPlano ? 'pl.x, pl.y, ' . (isset($plano['mesa_plano']['nombre']) ? 'pl.nombre' : 'NULL AS nombre') : 'NULL AS x, NULL AS y, NULL AS nombre';
		$joinPlano = $conPlano ? 'LEFT JOIN mesa_plano pl ON pl.id_mesa = m.id_mesa' : '';
		// 19-11-2026: nombre según el tipo de mesa (Mantención > Recintos > Tipos de mesa, ej. "Mesa D1")
		$this->load->model('Mtipomesa');
		$joinPlano .= ' ' . $this->Mtipomesa->joinSql('m', 'tmw');
		$selPlano .= ', ' . $this->Mtipomesa->nombreSql('m', 'tmw') . ' AS nombre_tipo, '
			. $this->Mtipomesa->idSql('m') . ' AS id_tipo_mesa';   // v1.22.0: sin tipo = el por defecto
		$rows = $this->q($db, "SELECT $selPlano, m.id_mesa, m.disponible, m.bloqueo_caja, m.bloqueo_pedido, COALESCE(m.id_cotizacion, 0) AS ref_cotizacion,
				COALESCE(r.id_recinto, 0) AS id_recinto, COALESCE(r.nombre_recinto, 'Sin recinto') AS recinto,
				c.id_cotizacion, c.total, u.nombre_usuario AS atiende,
				EXTRACT(EPOCH FROM (now() - c.fecha_transaccion))::bigint AS segundos
			FROM mesa m
			LEFT JOIN recinto r ON r.id_recinto = m.id_recinto
			-- 14-11-2026: si la mesa no apunta a un pedido (la app de garzones la dejó en 0), el último pedido abierto con
			-- productos de esa mesa, igual que cotizacionDeMesa() y verificarMesaNuevo.php de la app
			-- 19-11-2026: en dos partes (antes un OR): con el OR la base recorría todas las cotizaciones por cada mesa (~3 s al
			-- entrar a Mesas). Así la primera va por la clave y la segunda por ix_cotizacion_abierta_mesa (migración 20261119120000).
			LEFT JOIN LATERAL (
				(SELECT cc.* FROM cotizacion cc
					WHERE m.id_cotizacion > 0 AND cc.id_cotizacion = m.id_cotizacion
						AND cc.pagado IS NOT TRUE AND cc.vigente IS NOT FALSE AND cc.eseliminado IS NOT TRUE AND cc.esanulado IS NOT TRUE)
				UNION ALL
				(SELECT cc.* FROM cotizacion cc
					WHERE COALESCE(m.id_cotizacion, 0) = 0 AND cc.id_mesa = m.id_mesa AND COALESCE(cc.id_divpedido, 0) = 0
						AND cc.pagado IS NOT TRUE AND cc.vigente IS NOT FALSE AND cc.eseliminado IS NOT TRUE AND cc.esanulado IS NOT TRUE
						AND EXISTS (SELECT 1 FROM detalle_cotizacion d WHERE d.id_cotizacion = cc.id_cotizacion
							AND d.vigente IS NOT FALSE AND d.esanulado IS NOT TRUE AND d.eseliminado IS NOT TRUE)
					ORDER BY cc.id_cotizacion DESC LIMIT 1)
				LIMIT 1) c ON true
			LEFT JOIN usuario u ON u.id_usuario = c.id_usuario
			$joinPlano
			WHERE m.vigente IS NOT FALSE AND r.vigente IS NOT FALSE AND m.id_mesa <> ?
			ORDER BY COALESCE(r.id_recinto, 999999), m.id_mesa", array($directa))->result();
		$res = array();
		foreach ($rows as $m) {
			$bloqueada = $m->bloqueo_caja === 't' || $m->bloqueo_pedido === 't' || $m->bloqueo_caja === true || $m->bloqueo_pedido === true;
			$libre = ($m->disponible === 't' || $m->disponible === true) && !(int)$m->ref_cotizacion;
			$estado = $bloqueada ? 'bloqueada' : ($m->id_cotizacion ? 'ocupada' : ($libre ? 'libre' : 'bloqueada'));
			$res[] = array(
				'id_mesa' => (int)$m->id_mesa, 'id_recinto' => (int)$m->id_recinto, 'recinto' => $m->recinto, 'estado' => $estado,
				'total' => (int)$m->total, 'segundos' => (int)$m->segundos, 'atiende' => (string)$m->atiende,
				'con_pedido' => (bool)$m->id_cotizacion,
				'x' => $m->x === null ? null : (int)$m->x, 'y' => $m->y === null ? null : (int)$m->y,
				'nombre' => trim((string)$m->nombre),
				'nombre_tipo' => (string)$m->nombre_tipo,   // sin nombre propio en el plano, el del tipo de mesa
				'id_tipo_mesa' => (int)$m->id_tipo_mesa,
			);
		}
		// v1.29.0: mesas libres bloqueadas por una reserva
		$reservas = $this->bloqueosReserva($db);
		if ($reservas) {
			foreach ($res as $i => $mm) {
				if ($mm['estado'] === 'libre' && isset($reservas[$mm['id_mesa']])) $res[$i]['reserva'] = $reservas[$mm['id_mesa']];
			}
		}
		// 19-11-2026: filtro "Tipo de mesa" arriba de las mesas: los tipos vigentes y los que tenga alguna mesa
		$usados = array();
		foreach ($res as $m) $usados[$m['id_tipo_mesa']] = true;
		$tiposMesa = array();
		foreach ($this->Mtipomesa->opciones() as $t) {
			if ($t['vigente'] || isset($usados[$t['id']])) $tiposMesa[] = array('id' => $t['id'], 'nombre' => $t['nombre']);
		}
		// Modo venta directa con pedidos de antes: solo esas mesas (para cobrarlas o anularlas), sin mesas libres
		// ni recintos vacíos. Abrir pedidos nuevos ya lo impide enviarMesa.
		if ($modo === 'directa') {
			$res = array_values(array_filter($res, function ($m) { return $m['con_pedido']; }));
			$conMesas = array();
			foreach ($res as $m) $conMesas[$m['id_recinto']] = true;
		}
		// También los recintos que aún no tienen mesas
		$sectores = array();
		foreach ($this->q($db, 'SELECT id_recinto, nombre_recinto FROM recinto WHERE vigente IS NOT FALSE ORDER BY id_recinto')->result() as $r) {
			if ($modo === 'directa' && !isset($conMesas[(int)$r->id_recinto])) continue;
			$sectores[] = array('id' => (int)$r->id_recinto, 'nombre' => $r->nombre_recinto);
		}
		return array('habilitado' => true, 'mesas' => $res, 'sectores' => $sectores, 'modo' => $modo, 'solo_pendientes' => $modo === 'directa',
			'tipos_mesa' => $tiposMesa);
	}

	/** Nombre visible de una mesa (ej: "Terraza 2"); sin nombre propio, el de su tipo ("Mesa D1") o "Mesa N". */
	public function etiquetaMesa($idMesa)
	{
		$db = $this->conexion();
		$c = $this->columnas($db, array('mesa_plano'));
		$r = isset($c['mesa_plano']['nombre']) ? $this->q($db, 'SELECT nombre FROM mesa_plano WHERE id_mesa = ?', array((int)$idMesa))->row() : null;
		if ($r && trim((string)$r->nombre) !== '') return trim($r->nombre);
		// 19-11-2026: sin nombre propio, según el tipo de mesa (ej. "Mesa D1")
		$this->load->model('Mtipomesa');
		$t = $this->q($db, 'SELECT ' . $this->Mtipomesa->nombreSql('m', 'tmw') . ' AS nombre FROM mesa m ' . $this->Mtipomesa->joinSql('m', 'tmw')
			. ' WHERE m.id_mesa = ?', array((int)$idMesa))->row();
		return $t && $t->nombre !== '' ? $t->nombre : 'Mesa ' . (int)$idMesa;
	}

	/** Guarda dónde va cada mesa en el plano de su sector y su nombre visible: [{id_mesa, x, y, nombre}] (x, y en píxeles). */
	public function guardarPlanoMesas($p)
	{
		$db = $this->conexion();
		if (!$this->tieneMesas($db)) throw new Exception('Este cliente no tiene mesas', 400);
		$posiciones = isset($p['posiciones']) && is_array($p['posiciones']) ? $p['posiciones'] : array();
		// 16-11-2026: la tabla la crea la migración 20261116100000 (antes CREATE/ALTER en cada guardado: bloqueaba la tabla)
		$plano = $this->columnas($db, array('mesa_plano'));
		if (!isset($plano['mesa_plano']['nombre'])) {
			throw new Exception('Falta la tabla del plano de mesas en esta base: hay que correr las migraciones (php index.php despliegue esquema)', 409);
		}
		$db->trans_begin();
		try {
			foreach ($posiciones as $pos) {
				$id = (int)($pos['id_mesa'] ?? 0);
				$this->q($db, 'DELETE FROM mesa_plano WHERE id_mesa = ?', array($id));
				$nombre = mb_substr(trim((string)(isset($pos['nombre']) ? $pos['nombre'] : '')), 0, 40);
				$this->q($db, 'INSERT INTO mesa_plano (id_mesa, x, y, nombre) VALUES (?, ?, ?, ?)',
					array($id, max(0, min(5000, (int)($pos['x'] ?? 0))), max(0, min(5000, (int)($pos['y'] ?? 0))), $nombre === '' ? null : $nombre));
			}
			$db->trans_commit();
		} catch (Throwable $e) {   // 19-11-2026: también errores de PHP deshacen la transacción
			$db->trans_rollback();
			throw $e;
		}
		return array('ok' => true);
	}

	/**
	 * Pedido de una mesa para cargarlo en la Caja. $validar = false después de guardar algo: la operación ya quedó hecha
	 * y un bloqueo que aparezca justo ahí no debe mostrarse como error (el usuario reintentaría y la duplicaría).
	 */
	public function detalleMesa($idMesa, $validar = true)
	{
		$db = $this->conexion();
		if (!$this->tieneMesas($db)) throw new Exception('Este cliente no tiene mesas', 400);
		if ($validar) $this->validarMesaUsable($this->q($db, 'SELECT * FROM mesa WHERE id_mesa = ? AND vigente IS NOT FALSE', array((int)$idMesa))->row(), $db);
		$c = $this->cotizacionDeMesa($db, $idMesa);
		if (!$c) return array('id_mesa' => (int)$idMesa, 'id_cotizacion' => 0, 'id_lprecio' => 0, 'segundos' => 0, 'lineas' => array());
		$t = $this->q($db, 'SELECT EXTRACT(EPOCH FROM (now() - fecha_transaccion))::bigint AS s FROM cotizacion WHERE id_cotizacion = ?', array((int)$c->id_cotizacion))->row();
		return array('id_mesa' => (int)$idMesa, 'id_cotizacion' => (int)$c->id_cotizacion, 'id_lprecio' => (int)$c->id_lprecio,
			'id_garzon' => (int)$c->id_usuario, 'mensaje' => $this->mensajeDeCotizacion($db, (int)$c->id_cotizacion),
			'segundos' => (int)$t->s, 'lineas' => $this->lineasDeCotizacion($db, (int)$c->id_cotizacion));
	}

	/**
	 * 19-11-2026: mensaje general de la mesa (cotizacion.observaciones_generales, la misma columna del POS de escritorio).
	 * Sale impreso en cada comanda de la mesa (envíos y reenvíos). '' si no hay o la base no tiene la columna.
	 */
	private function mensajeDeCotizacion($db, $idCot)
	{
		$c = $this->columnas($db, array('cotizacion'));
		if ($idCot <= 0) return '';
		// v1.20.0: la app de garzones (DataRestoAPI, recibirNuevaCotizacion) deja el mensaje general en cotizacion.mensaje; la
		// web, en observaciones_generales. Se muestra el de la web y, si no hay, el que mandó el celular.
		$cols = array();
		if (isset($c['cotizacion']['observaciones_generales'])) $cols[] = "NULLIF(trim(observaciones_generales), '')";
		if (isset($c['cotizacion']['mensaje'])) $cols[] = "NULLIF(trim(mensaje), '')";
		if (!$cols) return '';
		$r = $this->q($db, 'SELECT COALESCE(' . implode(', ', $cols) . ', \'\') AS m FROM cotizacion WHERE id_cotizacion = ?', array((int)$idCot))->row();
		return $r ? trim((string)$r->m) : '';
	}

	/**
	 * v1.22.1: el mensaje general de la mesa sale en la comanda UNA vez. Si ya salió impreso (cotizacion.mensaje_impreso_web
	 * es igual al de ahora), los productos que se agregan después van solo con su nota o su agregado. Si el mensaje cambió,
	 * sale el nuevo en el próximo envío. Sin la migración 20261120130000 sale en cada envío, como antes.
	 */
	private function mensajeParaComanda($db, $cols, $idCot)
	{
		$mensaje = $this->mensajeDeCotizacion($db, $idCot);
		if (!isset($cols['cotizacion']['mensaje_impreso_web'])) return $mensaje;
		$ya = $this->q($db, "SELECT COALESCE(mensaje_impreso_web, '') AS m FROM cotizacion WHERE id_cotizacion = ?", array((int)$idCot))->row();
		if ($ya && trim((string)$ya->m) === $mensaje) return '';
		$this->q($db, 'UPDATE cotizacion SET mensaje_impreso_web = ? WHERE id_cotizacion = ?', array($mensaje === '' ? null : $mensaje, (int)$idCot));
		return $mensaje;
	}

	/** 19-11-2026: datos de la mesa para la comanda: recinto y N° del pedido abierto (0 si ya no hay). */
	public function infoComandaMesa($idMesa)
	{
		$db = $this->conexion();
		$r = $this->q($db, "SELECT COALESCE(r.nombre_recinto, '') AS recinto FROM mesa m LEFT JOIN recinto r ON r.id_recinto = m.id_recinto
			WHERE m.id_mesa = ?", array((int)$idMesa))->row();
		$c = $this->cotizacionDeMesa($db, (int)$idMesa);
		return array('recinto' => $r ? trim((string)$r->recinto) : '', 'pedido' => $c ? (int)$c->id_cotizacion : 0);
	}

	/** El mensaje general del pedido abierto de la mesa (para la comanda). */
	public function mensajeMesa($idMesa)
	{
		$db = $this->conexion();
		$c = $this->cotizacionDeMesa($db, (int)$idMesa);
		return $c ? $this->mensajeDeCotizacion($db, (int)$c->id_cotizacion) : '';
	}

	/** Guarda (o borra, con '') el mensaje general del pedido abierto de la mesa. */
	public function guardarMensajeMesa($idMesa, $texto)
	{
		$db = $this->conexion();
		if (!$this->tieneMesas($db)) throw new Exception('Este cliente no tiene mesas', 400);
		$c = $this->columnas($db, array('cotizacion'));
		if (!isset($c['cotizacion']['observaciones_generales'])) throw new Exception('Esta base no tiene dónde guardar el mensaje de la mesa', 409);
		$cot = $this->cotizacionDeMesa($db, (int)$idMesa);
		if (!$cot) throw new Exception('La mesa no tiene un pedido abierto: el mensaje se guarda al enviar los productos', 400);
		$texto = self::textoMensajeMesa($texto);
		$this->q($db, 'UPDATE cotizacion SET observaciones_generales = ? WHERE id_cotizacion = ?', array($texto === '' ? null : $texto, (int)$cot->id_cotizacion));
		return array('mensaje' => $texto);
	}

	public static function textoMensajeMesa($t)
	{
		return mb_substr(trim(preg_replace('/\s+/u', ' ', preg_replace('/[\x00-\x1F\x7F]+/u', ' ', is_scalar($t) ? (string)$t : ''))), 0, 200);
	}

	/** Opciones de la mesa > Cambiar garzón: usuarios vigentes, los marcados como garzón primero. */
	public function garzones()
	{
		$db = $this->conexion();
		$c = $this->columnas($db, array('usuario'));
		$u = isset($c['usuario']) ? $c['usuario'] : array();
		// v1.20.0: garzón = marcado en el POS (esgarzon) o con rol GARZON. Los usuarios creados en la web solo tienen el rol
		// (la pantalla de Usuarios no marca esgarzon) y salían mezclados con los cajeros.
		$porRol = isset($u['id_rol']) ? "EXISTS (SELECT 1 FROM rol r WHERE r.id_rol = usuario.id_rol AND r.nombre_rol ILIKE 'GARZ%')" : 'false';
		$esGarzon = isset($u['esgarzon']) ? "(esgarzon IS TRUE OR $porRol)" : $porRol;
		$vigente = isset($u['vigente']) ? 'WHERE vigente IS NOT FALSE' : '';
		$res = array();
		foreach ($this->q($db, "SELECT id_usuario, COALESCE(nombre_usuario, '') AS nombre, $esGarzon AS garzon FROM usuario $vigente
			ORDER BY 3 DESC, 2")->result() as $f) {
			$res[] = array('id_usuario' => (int)$f->id_usuario, 'nombre' => (string)$f->nombre, 'garzon' => $f->garzon === 't' || $f->garzon === true);
		}
		return $res;
	}

	/**
	 * Cambia quién atiende la mesa: el garzón del pedido (cotizacion.id_usuario, el que queda en la venta al cobrar y en
	 * Propinas por garzón) y el de la mesa (mesa.id_garzon). Lo ya enviado conserva quién lo pidió (detalle_cotizacion).
	 */
	public function cambiarGarzonMesa($p)
	{
		$db = $this->conexion();
		if (!$this->tieneMesas($db)) throw new Exception('Este cliente no tiene mesas', 400);
		$idMesa = (int)(isset($p['id_mesa']) ? $p['id_mesa'] : 0);
		$idGarzon = (int)(isset($p['id_garzon']) ? $p['id_garzon'] : 0);
		$c = $this->columnas($db, array('usuario'));
		$vigente = isset($c['usuario']['vigente']) ? ' AND vigente IS NOT FALSE' : '';
		$g = $this->q($db, 'SELECT id_usuario, COALESCE(nombre_usuario, \'\') AS nombre FROM usuario WHERE id_usuario = ?' . $vigente, array($idGarzon))->row();
		if (!$g) throw new Exception('El garzón elegido no existe o no está vigente', 400);
		$db->trans_begin();
		try {
			$this->validarMesaUsable($this->bloquearMesa($db, $idMesa), $db);
			$cot = $this->cotizacionDeMesa($db, $idMesa);
			if (!$cot) throw new Exception('La mesa ya no tiene un pedido abierto', 409);
			$anterior = (int)$cot->id_usuario;
			$this->q($db, 'UPDATE cotizacion SET id_usuario = ? WHERE id_cotizacion = ?', array($idGarzon, (int)$cot->id_cotizacion));
			$this->q($db, 'UPDATE mesa SET id_garzon = ? WHERE id_mesa = ?', array($idGarzon, $idMesa));
			$db->trans_commit();
		} catch (Throwable $e) {   // 19-11-2026: también errores de PHP deshacen la transacción
			$db->trans_rollback();
			throw $e;
		}
		$a = $this->q($db, 'SELECT COALESCE(nombre_usuario, \'\') AS nombre FROM usuario WHERE id_usuario = ?', array($anterior))->row();
		return array('id_mesa' => $idMesa, 'mesa' => $this->etiquetaMesa($idMesa), 'id_garzon' => $idGarzon, 'nombre' => (string)$g->nombre,
			'anterior' => $a ? (string)$a->nombre : '');
	}

	/** Rechaza mesas bloqueadas o que quedaron marcadas ocupadas sin un pedido abierto. */
	/**
	 * v1.29.0: mesas libres bloqueadas por una reserva (Reservas). Una reserva "por llegar" bloquea su mesa desde
	 * Configuraciones > minutos antes de la hora hasta 1 hora después, o hasta que se marque Llegó / No vino / Cancelada.
	 * Con reservas apagadas, sin la tabla o con 0 minutos: nada. Devuelve id_mesa => [id, nombre, hora, personas].
	 */
	private function bloqueosReserva($db, $idMesa = 0)
	{
		$c = $this->columnas($db, array('reserva_web'));
		if (!isset($c['reserva_web'])) return array();
		$filtro = $idMesa > 0 ? ' AND r.id_mesa = ' . (int)$idMesa : ' AND r.id_mesa > 0';
		$res = array();
		foreach ($this->q($db, "SELECT DISTINCT ON (r.id_mesa) r.id_mesa, r.id_reserva, r.nombre, to_char(r.hora, 'HH24:MI') AS hora, r.personas
				FROM reserva_web r,
					(SELECT COALESCE((to_jsonb(c) ->> 'usar_reservas_web')::boolean, false) AS activa,
						COALESCE((to_jsonb(c) ->> 'minutos_bloqueo_reserva_web')::int, 60) AS minutos FROM configuracion c LIMIT 1) k
				WHERE k.activa AND k.minutos > 0 AND r.estado = 'pendiente' $filtro
					AND (now() AT TIME ZONE ?) BETWEEN (r.fecha + r.hora) - k.minutos * interval '1 minute' AND (r.fecha + r.hora) + interval '60 minutes'
				ORDER BY r.id_mesa, r.fecha, r.hora", array(self::TZ))->result() as $r) {
			$res[(int)$r->id_mesa] = array('id' => (int)$r->id_reserva, 'nombre' => $r->nombre, 'hora' => $r->hora, 'personas' => (int)$r->personas);
		}
		return $res;
	}

	/** Lanza 409 si la mesa libre está bloqueada por una reserva (enviar pedidos, mover productos a ella). */
	private function exigirSinReserva($db, $idMesa)
	{
		$b = $this->bloqueosReserva($db, $idMesa);
		if (!isset($b[$idMesa])) return;
		$r = $b[$idMesa];
		throw new Exception('Mesa bloqueada por reserva: ' . $r['nombre'] . ' a las ' . $r['hora'] . ' (' . $r['personas']
			. ($r['personas'] === 1 ? ' persona' : ' personas') . '). Si el cliente llegó, márcalo tocando la mesa.', 409);
	}

	/** v1.29.0: el cliente de la reserva llegó (desde la mesa en la Caja): la mesa se desbloquea. */
	public function reservaLlego($idMesa, $idReserva)
	{
		$db = $this->conexion();
		$c = $this->columnas($db, array('reserva_web'));
		if (!isset($c['reserva_web'])) throw new Exception('Este local no usa reservas', 400);
		$this->q($db, "UPDATE reserva_web SET estado = 'llego' WHERE id_reserva = ? AND id_mesa = ? AND estado = 'pendiente'", array((int)$idReserva, (int)$idMesa));
		if ($db->affected_rows() < 1) throw new Exception('La reserva ya no está pendiente en esa mesa', 409);
		return array('ok' => true);
	}

	private function validarMesaUsable($m, $db)
	{
		if (!$m) throw new Exception('La mesa no existe o no está vigente', 404);
		if ($m->bloqueo_caja === 't' || $m->bloqueo_pedido === 't' || $m->bloqueo_caja === true || $m->bloqueo_pedido === true) {
			throw new Exception('La mesa ' . (int)$m->id_mesa . ' está bloqueada', 409);
		}
		$libre = ($m->disponible === 't' || $m->disponible === true) && !(int)$m->id_cotizacion;
		if (!$libre && !$this->cotizacionDeMesa($db, (int)$m->id_mesa)) {
			throw new Exception('La mesa ' . (int)$m->id_mesa . ' figura ocupada pero no tiene un pedido abierto. Libérala primero.', 409);
		}
	}

	/**
	 * Agrega productos a la mesa; el primer envío la ocupa y hace correr su tiempo.
	 * impreso = true: la comanda la imprime la Caja web. Con false, f_manejo_stock_menu descontaría `stock` por su
	 * cuenta y el POS de escritorio volvería a imprimirla. El stock de insumos se descuenta al cobrar.
	 */
	public function enviarMesa($caja, $idUsuario, $p, $idRol = 0)
	{
		$db = $this->conexion();
		if (!$this->tieneMesas($db)) throw new Exception('Este cliente no tiene mesas', 400);
		$idMesa = (int)(isset($p['id_mesa']) ? $p['id_mesa'] : 0);
		if ($this->modoCaja($db) === 'directa') throw new Exception('Este local usa la Caja en modo venta directa: las mesas están desactivadas (Configuraciones)', 409);
		$config = $this->configuracion($db);
		$cols = $this->columnas($db, array('cotizacion', 'detalle_cotizacion', 'producto'));
		$idLprecio = !empty($p['id_lprecio']) ? (int)$p['id_lprecio'] : $config['id_lprecio_publico'];
		$actual = $this->cotizacionDeMesa($db, $idMesa);
		if ($actual && (int)$actual->id_lprecio) $idLprecio = (int)$actual->id_lprecio;
		// La lista se fija al abrir el pedido: en ese momento se exige la autorización si no es la pública
		else $this->exigirLista($db, $config, $idUsuario, $idRol, $idLprecio, 0);
		list($lineas, $subtotal, , $comandaMesa) = $this->armarLineas($db, $config, $cols, isset($p['detalle']) ? $p['detalle'] : null, $idLprecio);
		$destinos = $this->destinosComanda(array_column($lineas, 'id_producto'));
		$ahora = date('Y-m-d H:i:s');

		$db->trans_begin();
		try {
			$this->q($db, 'SET LOCAL TIME ZONE ' . $db->escape(self::TZ));
			$this->validarMesaUsable($this->bloquearMesa($db, $idMesa), $db);
			$this->validarStock($db, $lineas);
			$cot = $this->cotizacionDeMesa($db, $idMesa);
			if (!$cot) $this->exigirSinReserva($db, $idMesa);   // v1.29.0: mesa libre bloqueada por reserva
			if ($cot) {
				$idCot = (int)$cot->id_cotizacion;
			} else {
				$td = $this->q($db, 'SELECT id_tdocumento FROM cotizacion ORDER BY id_cotizacion DESC LIMIT 1')->row();
				$idCot = $this->insertar($db, $cols, 'cotizacion', array(
					'id_tdocumento' => $td ? (int)$td->id_tdocumento : (int)$this->primerId($db, 'tdocumento', 'id_tdocumento', 1),
					'id_cliente' => $config['id_cliente_publico'],
					'id_lprecio' => $idLprecio,
					'id_usuario' => (int)$idUsuario,
					'esconsignacion' => false,
					'fecha_transaccion' => $ahora,
					'vencimiento' => $ahora,
					'fecha' => date('Y-m-d'),
					'subtotal' => 0, 'descuento' => 0, 'neto' => 0, 'iva' => 0, 'ila' => 0, 'total' => 0,
					'id_caja' => 0, 'folio' => 0, 'id_venta' => 0, 'id_mesa' => $idMesa, 'id_divpedido' => 0,
					'pagado' => false, 'vigente' => true, 'eseliminado' => false, 'esanulado' => false, 'esdelivery' => false,
					'propina_sugerida' => 0, 'por_descuento' => 0, 'monto_descuento' => 0, 'precuenta_impresa' => false,
					'venta_directa' => false, 'index_comensal' => 0,
					'id_canal_venta' => $this->primerId($db, 'canal_venta', 'id_canal_venta', 1),
					'nombre_computador' => self::COMPUTADOR,
				), 'id_cotizacion');
				$this->q($db, 'UPDATE mesa SET disponible = false, id_cotizacion = ?, id_garzon = ? WHERE id_mesa = ?', array($idCot, (int)$idUsuario, $idMesa));
			}
			$idsMenu = array();   // v1.24.0: índice de la línea del menú => su id_detalle_cotizacion
			foreach ($lineas as $iLin => $l) {
				$idsMenu[$iLin] = $this->insertar($db, $cols, 'detalle_cotizacion', array(
					// v1.24.0: menú y productos elegidos del menú (cada uno apunta a la línea de su menú)
					'es_menu' => !empty($l['es_menu']), 'es_detalle_menu' => !empty($l['es_detalle_menu']),
					'id_menu' => isset($l['id_menu']) ? (int)$l['id_menu'] : 0,
					'id_detalle_menu_web' => isset($l['del_menu'], $idsMenu[$l['del_menu']]) ? $idsMenu[$l['del_menu']] : null,
					'id_cotizacion' => $idCot, 'id_producto' => $l['id_producto'], 'cantidad' => $l['cantidad'],
					'bruto_un' => $l['unitario'], 'subtotal' => 0, 'descuento' => 0, 'descuento_porcentual' => 0, 'total_bruto' => $l['bruto'],
					'poriva' => 0, 'id_agregado' => $l['id_agregado'], 'mensaje' => mb_substr($l['nota'], 0, 250),
					'vigente' => true, 'impreso' => true, 'imprimir' => !isset($l['imprimir']) || $l['imprimir'], 'fecha_hora_impresion' => $ahora,
					// 14-11-2026: los pedidos que llegan de la app móvil (ApiMovil/pedidoMesa) quedan marcados
					'desde_android' => !empty($p['desde_app']),
					'id_garzon' => (int)$idUsuario, 'nombre_computador' => !empty($p['desde_app']) ? 'APP MOVIL' : self::COMPUTADOR,
					'id_dimpresion' => isset($destinos[$l['id_producto']]) ? $destinos[$l['id_producto']]['id'] : 0,
				), !empty($l['es_menu']) ? 'id_detalle_cotizacion' : null);
			}
			// 19-11-2026: mensaje general de la mesa escrito ANTES del primer envío (mesa sin pedido). Con el pedido ya abierto el
			// mensaje se cambia solo con guardarMensajeMesa: aquí no se pisa (auditoría: otra caja o el POS de escritorio pudo
			// escribir una alergia mientras esta pantalla tenía la mesa abierta, y el envío la borraba).
			$obs = isset($p['observacion']) ? self::textoMensajeMesa($p['observacion']) : '';
			if ($obs !== '' && isset($cols['cotizacion']['observaciones_generales'])) {
				$this->q($db, "UPDATE cotizacion SET observaciones_generales = ? WHERE id_cotizacion = ? AND COALESCE(trim(observaciones_generales), '') = ''",
					array($obs, $idCot));
			}
			$this->recalcularCotizacion($db, $idCot);
			$mensajeComanda = $this->mensajeParaComanda($db, $cols, $idCot);
			$db->trans_commit();
		} catch (Throwable $e) {   // 19-11-2026: también errores de PHP deshacen la transacción
			$db->trans_rollback();
			throw $e;
		}
		return array('id_cotizacion' => $idCot, 'detalle' => $this->detalleMesa($idMesa, false), 'comanda' => $comandaMesa,
			'mensaje_mesa' => $mensajeComanda);
	}

	/**
	 * 14-11-2026: comandas de la app de garzones (API2) que nadie ha impreso.
	 * La app graba las líneas con desde_android = true e impreso = false y hasta ahora solo el POS de escritorio las
	 * imprimía. Con Configuraciones > "Imprimir las comandas de la app" la Caja web las toma: las marca impresas en la
	 * misma transacción (FOR UPDATE SKIP LOCKED: con dos cajas abiertas cada línea la toma una sola) y devuelve por mesa
	 * las líneas para armar la comanda; la Caja las imprime como las suyas, cada producto en su destino de impresión.
	 * Solo líneas de las últimas 3 horas de pedidos abiertos (al encender la opción no salen pedidos viejos).
	 * f_manejo_stock_menu solo actúa al insertar: marcar impreso no vuelve a descontar stock.
	 */
	public function tomarComandasApp()
	{
		$db = $this->conexion();
		$config = $this->configuracion($db);
		if (!$this->tieneMesas($db) || !$config['comandas_app']) return array();
		$cols = $this->columnas($db, array('detalle_cotizacion'));
		if (!isset($cols['detalle_cotizacion']['desde_android'], $cols['detalle_cotizacion']['impreso'])) return array();
		// 16-11-2026: con "imprimir también los que el celular marca como no imprimir", se toman además las líneas que la
		// API2 grabó con impreso = true por "imprimir": 0. Se reconocen porque no tienen fecha_hora_impresion (nadie las
		// imprimió); la web, la API móvil y el POS sí la ponen al imprimir. Sin esa columna no se puede: se ignora.
		$forzar = $config['comandas_app_forzar'] && isset($cols['detalle_cotizacion']['fecha_hora_impresion']);
		$pendiente = $forzar ? '(d.impreso IS NOT TRUE OR d.fecha_hora_impresion IS NULL)' : 'd.impreso IS NOT TRUE';
		$db->trans_begin();
		try {
			$this->q($db, 'SET LOCAL TIME ZONE ' . $db->escape(self::TZ));
			$rows = $this->q($db, "SELECT d.id_detalle_cotizacion, d.id_producto, d.cantidad, COALESCE(d.mensaje, '') AS nota,
					COALESCE(p.nombre_producto, 'Producto ' || d.id_producto) AS nombre, pa.nombre_producto AS agregado,
					c.id_mesa, c.id_cotizacion, COALESCE(u.nombre_usuario, '') AS garzon
				FROM detalle_cotizacion d
				JOIN cotizacion c ON c.id_cotizacion = d.id_cotizacion
				LEFT JOIN producto p ON p.id_producto = d.id_producto
				LEFT JOIN producto pa ON pa.id_producto = d.id_agregado AND d.id_agregado > 0
				LEFT JOIN usuario u ON u.id_usuario = c.id_usuario
				WHERE d.desde_android IS TRUE AND $pendiente
					AND d.vigente IS NOT FALSE AND d.esanulado IS NOT TRUE AND d.eseliminado IS NOT TRUE
					AND c.pagado IS NOT TRUE AND c.vigente IS NOT FALSE AND c.esanulado IS NOT TRUE AND c.eseliminado IS NOT TRUE
					AND COALESCE(d.fecha_movimiento, c.fecha_transaccion) > now() - interval '3 hours'
				ORDER BY d.id_detalle_cotizacion
				LIMIT 300
				FOR UPDATE OF d SKIP LOCKED")->result();
			if (!$rows) {
				$db->trans_rollback();
				return array();
			}
			$ids = array_map(function ($r) { return (int)$r->id_detalle_cotizacion; }, $rows);
			$set = 'impreso = true' . (isset($cols['detalle_cotizacion']['fecha_hora_impresion']) ? ', fecha_hora_impresion = now()' : '');
			$this->q($db, 'UPDATE detalle_cotizacion SET ' . $set . ' WHERE id_detalle_cotizacion ' . $this->sqlEn($ids));
			$db->trans_commit();
		} catch (Throwable $e) {   // 19-11-2026: también errores de PHP deshacen la transacción
			$db->trans_rollback();
			throw $e;
		}
		$porMesa = array();
		foreach ($rows as $r) {
			$k = (int)$r->id_cotizacion;
			if (!isset($porMesa[$k])) $porMesa[$k] = array('id_mesa' => (int)$r->id_mesa, 'garzon' => trim((string)$r->garzon), 'lineas' => array());
			$porMesa[$k]['lineas'][] = array(
				'id_producto' => (int)$r->id_producto, 'nombre' => $r->nombre, 'cantidad' => (float)$r->cantidad,
				'agregados' => $r->agregado !== null && $r->agregado !== '' ? array($r->agregado) : array(), 'nota' => $r->nota,
			);
		}
		return array_values($porMesa);
	}

	private function recalcularCotizacion($db, $idCot)
	{
		$suma = (int)array_sum(array_column($this->lineasDeCotizacion($db, $idCot), 'bruto'));
		$this->q($db, 'UPDATE cotizacion SET subtotal = ?, total = ? WHERE id_cotizacion = ?', array($suma, $suma, $idCot));
		return $suma;
	}

	/**
	 * Valida una selección [{id_detalle, cantidad}] sobre las líneas vigentes y devuelve esas líneas con la cantidad
	 * elegida (cantidad null = la línea completa). El monto de una parte es proporcional: round(bruto x parte / cantidad).
	 */
	private function seleccionDeLineas($lineas, $seleccion)
	{
		$porId = array();
		foreach ($lineas as $l) $porId[$l['id_detalle']] = $l;
		$res = array();
		foreach ($seleccion as $s) {
			$id = (int)(isset($s['id_detalle']) ? $s['id_detalle'] : 0);
			if (!isset($porId[$id])) throw new Exception('Un producto elegido ya no está en la mesa. Vuelve a abrirla.', 409);
			$l = $porId[$id];
			$q = isset($s['cantidad']) && $s['cantidad'] !== null && $s['cantidad'] !== '' ? round((float)$s['cantidad'], 3) : $l['cantidad'];
			if ($q <= 0) continue;
			if ($q > $l['cantidad'] + 0.0005) throw new Exception('La cantidad elegida de ' . $l['nombre'] . ' supera la del pedido', 400);
			if ($q < $l['cantidad'] - 0.0005) {
				// Misma cuenta que partirLinea(): la parte cobrada y la que se separa del pedido coinciden
				$l['bruto'] = (int)round($l['bruto_exacto'] * $q / $l['cantidad']);
				$l['bruto_exacto'] = (float)$l['bruto'];
				$l['cantidad'] = $q;
			}
			$res[] = $l;
			unset($porId[$id]);
		}
		if (!$res) throw new Exception('Elige al menos un producto', 400);
		return $this->conProductosDelMenu($lineas, $res);
	}

	/**
	 * v1.24.0: un menú y lo elegido en él no se separan. Por cada menú elegido se agregan sus productos (los que no
	 * vinieron ya en la selección) en la misma proporción, justo después del menú; un producto de menú elegido sin su
	 * menú se rechaza.
	 */
	private function conProductosDelMenu($lineas, $sel)
	{
		$hijos = array();
		$cantMenu = array();
		foreach ($lineas as $l) {
			if (!empty($l['menu_de'])) $hijos[$l['menu_de']][] = $l;
			if (!empty($l['es_menu'])) $cantMenu[$l['id_detalle']] = $l['cantidad'];
		}
		if (!$hijos) return $sel;
		$elegidos = array();
		foreach ($sel as $l) $elegidos[$l['id_detalle']] = $l;
		$res = array();
		foreach ($sel as $l) {
			if (!empty($l['menu_de'])) {
				if (!isset($elegidos[$l['menu_de']])) {
					throw new Exception($l['nombre'] . ' es parte de un menú: elige el menú completo', 400);
				}
				continue;   // va junto a su menú, más abajo
			}
			$res[] = $l;
			if (empty($l['es_menu']) || empty($hijos[$l['id_detalle']])) continue;
			$total = isset($cantMenu[$l['id_detalle']]) ? $cantMenu[$l['id_detalle']] : $l['cantidad'];
			$f = $total > 0 ? $l['cantidad'] / $total : 1;
			foreach ($hijos[$l['id_detalle']] as $h) {
				if (isset($elegidos[$h['id_detalle']])) { $res[] = $elegidos[$h['id_detalle']]; continue; }
				if ($f < 0.9995) {
					$q = round($h['cantidad'] * $f, 3);
					if ($q <= 0) continue;
					$h['bruto'] = (int)round($h['bruto_exacto'] * $q / $h['cantidad']);
					$h['bruto_exacto'] = (float)$h['bruto'];
					$h['cantidad'] = $q;
				}
				$res[] = $h;
			}
		}
		return $res;
	}

	/**
	 * v1.24.0: partirLinea() de toda una selección. La copia de un producto de menú apunta a la copia de su menú, para
	 * que en el pedido de destino sigan juntos.
	 */
	private function partirSeleccion($db, $sel, $idCotDestino, $cambios = array())
	{
		$nuevos = array();
		foreach ($sel as $l) {
			$c = $cambios;
			if (!empty($l['menu_de']) && isset($nuevos[$l['menu_de']])) $c['id_detalle_menu_web'] = $nuevos[$l['menu_de']];
			$nuevos[$l['id_detalle']] = $this->partirLinea($db, $l['id_detalle'], $l['cantidad'], $idCotDestino, $c);
		}
		return $nuevos;
	}

	/**
	 * Pasa $cantidad de una línea al pedido $idCotDestino (puede ser el mismo pedido, para anular solo una parte).
	 * Línea completa: se mueve tal cual. Parte: la original queda con el resto y se inserta una copia con lo elegido.
	 * $cambios se aplica a lo que se mueve (ej: marcarlo anulado).
	 */
	private function partirLinea($db, $idDetalle, $cantidad, $idCotDestino, $cambios = array())
	{
		$cols = $this->columnas($db, array('detalle_cotizacion'));
		$d = $this->q($db, 'SELECT * FROM detalle_cotizacion WHERE id_detalle_cotizacion = ? FOR UPDATE', array((int)$idDetalle))->row_array();
		if (!$d) throw new Exception('Un producto elegido ya no está en la mesa. Vuelve a abrirla.', 409);
		$total = (float)$d['cantidad'];
		if ($cantidad >= $total - 0.0005) {
			$this->actualizar($db, $cols, 'detalle_cotizacion', array_merge(array('id_cotizacion' => (int)$idCotDestino), $cambios), 'id_detalle_cotizacion', (int)$idDetalle);
			return (int)$idDetalle;
		}
		$bruto = (float)$d['total_bruto'] > 0 ? (float)$d['total_bruto'] : (float)$d['bruto_un'] * $total;
		$parte = (int)round($bruto * $cantidad / $total);
		$this->actualizar($db, $cols, 'detalle_cotizacion', array('cantidad' => round($total - $cantidad, 3), 'total_bruto' => $bruto - $parte),
			'id_detalle_cotizacion', (int)$idDetalle);
		unset($d['id_detalle_cotizacion']);
		// impreso = true: la copia no debe volver a imprimirse ni descontar stock por el trigger de detalle_cotizacion
		$d = array_merge($d, array('id_cotizacion' => (int)$idCotDestino, 'cantidad' => $cantidad, 'total_bruto' => $parte, 'impreso' => true), $cambios);
		return $this->insertar($db, $cols, 'detalle_cotizacion', $d, 'id_detalle_cotizacion');
	}

	/** Pedido nuevo con los datos de $cot (lista de precios, quien atiende, hora de llegada) para $idMesa. */
	private function copiarPedido($db, $cot, $idMesa, $idDivpedido)
	{
		$cols = $this->columnas($db, array('cotizacion'));
		$c = (array)$cot;
		unset($c['id_cotizacion']);
		$c = array_merge($c, array('id_mesa' => (int)$idMesa, 'id_divpedido' => (int)$idDivpedido, 'subtotal' => 0, 'total' => 0,
			'descuento' => 0, 'neto' => 0, 'iva' => 0, 'pagado' => false, 'id_venta' => 0, 'vigente' => true, 'esanulado' => false,
			'eseliminado' => false, 'precuenta_impresa' => false, 'nombre_computador' => self::COMPUTADOR));
		// 19-11-2026 (auditoría): el mensaje general es de la mesa de origen: pasar productos a OTRA mesa no se lo lleva
		// (en el cobro de una parte, misma mesa, sí se conserva)
		if (array_key_exists('observaciones_generales', $c) && (int)$idMesa !== (int)$cot->id_mesa) $c['observaciones_generales'] = null;
		return $this->insertar($db, $cols, 'cotizacion', $c, 'id_cotizacion');
	}

	/** Separa lo que se va a cobrar en un pedido hijo de la mesa. Devuelve ese pedido. */
	private function dividirPedido($db, $cotMesa, $idMesa, $seleccion)
	{
		$idHijo = $this->copiarPedido($db, $cotMesa, $idMesa, (int)$cotMesa->id_cotizacion);
		$this->partirSeleccion($db, $seleccion, $idHijo);   // v1.24.0: el menú y lo elegido van juntos
		$this->recalcularCotizacion($db, $idHijo);
		return $this->q($db, 'SELECT * FROM cotizacion WHERE id_cotizacion = ?', array($idHijo))->row();
	}

	/**
	 * Cambiar o dividir mesa: pasa productos (o todo) a otra mesa. Si se pasa todo a una mesa libre, el pedido
	 * completo cambia de mesa y conserva su hora. Si la mesa destino está ocupada, se agregan a su pedido.
	 */
	public function moverMesa($caja, $idUsuario, $p)
	{
		$db = $this->conexion();
		$idMesa = (int)($p['id_mesa'] ?? 0);
		$idDestino = (int)($p['id_mesa_destino'] ?? 0);
		if (!$idDestino || $idDestino === $idMesa) throw new Exception('Elige la mesa a la que pasan los productos', 400);
		$db->trans_begin();
		try {
			$this->q($db, 'SET LOCAL TIME ZONE ' . $db->escape(self::TZ));
			// Siempre en el mismo orden, para que dos cajas no se bloqueen entre sí
			$ids = array($idMesa, $idDestino);
			sort($ids);
			foreach ($ids as $id) $this->validarMesaUsable($this->bloquearMesa($db, $id), $db);
			$cot = $this->cotizacionDeMesa($db, $idMesa);
			if (!$cot) throw new Exception('La mesa ya no tiene un pedido abierto', 409);
			$lineas = $this->lineasDeCotizacion($db, (int)$cot->id_cotizacion);
			$sel = isset($p['lineas']) && is_array($p['lineas']) && $p['lineas'] ? $p['lineas'] : array_map(function ($l) { return array('id_detalle' => $l['id_detalle']); }, $lineas);
			$sel = $this->seleccionDeLineas($lineas, $sel);
			$todo = count($sel) === count($lineas) && !array_filter($sel, function ($l) use ($lineas) {
				foreach ($lineas as $o) if ($o['id_detalle'] === $l['id_detalle']) return $l['cantidad'] < $o['cantidad'] - 0.0005;
				return true;
			});
			$destino = $this->cotizacionDeMesa($db, $idDestino);
			if (!$destino) $this->exigirSinReserva($db, $idDestino);   // v1.29.0: no se pasan productos a una mesa reservada
			if ($todo && !$destino) {
				$this->q($db, 'UPDATE cotizacion SET id_mesa = ? WHERE id_cotizacion = ?', array($idDestino, (int)$cot->id_cotizacion));
				$this->q($db, 'UPDATE mesa SET disponible = false, id_cotizacion = ?, id_garzon = ? WHERE id_mesa = ?', array((int)$cot->id_cotizacion, (int)$idUsuario, $idDestino));
				$this->desocuparMesa($db, $idMesa);
			} else {
				if ($destino) {
					$idCotDestino = (int)$destino->id_cotizacion;
				} else {
					$idCotDestino = $this->copiarPedido($db, $cot, $idDestino, 0);
					$this->q($db, 'UPDATE mesa SET disponible = false, id_cotizacion = ?, id_garzon = ? WHERE id_mesa = ?', array($idCotDestino, (int)$idUsuario, $idDestino));
				}
				$this->partirSeleccion($db, $sel, $idCotDestino);   // v1.24.0: el menú y lo elegido van juntos
				$this->recalcularCotizacion($db, $idCotDestino);
				$this->recalcularCotizacion($db, (int)$cot->id_cotizacion);
				if (!$this->lineasDeCotizacion($db, (int)$cot->id_cotizacion)) {
					$this->q($db, "UPDATE cotizacion SET vigente = false, eseliminado = true, motivo_anulacion = ? WHERE id_cotizacion = ?",
						array('Pasado a la mesa ' . $idDestino, (int)$cot->id_cotizacion));
					$this->desocuparMesa($db, $idMesa);
				}
			}
			$db->trans_commit();
		} catch (Throwable $e) {   // 19-11-2026: también errores de PHP deshacen la transacción
			$db->trans_rollback();
			throw $e;
		}
		$libre = !$this->cotizacionDeMesa($db, $idMesa);
		return array('liberada' => $libre, 'detalle' => $libre ? null : $this->detalleMesa($idMesa, false), 'movidas' => $this->lineasAnulacion($sel));
	}

	/** Identifica el contenido exacto de un pedido (líneas, productos, cantidades y montos). */
	private function huellaLineas($lineas)
	{
		return md5(json_encode(array_map(function ($l) {
			return array($l['id_detalle'], $l['id_producto'], round($l['cantidad'], 3), round($l['bruto_exacto'], 2), $l['id_agregado']);
		}, $lineas)));
	}

	/** Mesas con pedido abierto (para no cerrar la caja con mesas sin cobrar). */
	private function mesasAbiertas($db)
	{
		if (!$this->tieneMesas($db)) return array();
		return array_map('intval', array_column($this->q($db, "SELECT m.id_mesa FROM mesa m JOIN cotizacion c ON c.id_cotizacion = m.id_cotizacion
			WHERE m.id_cotizacion > 0 AND m.vigente IS NOT FALSE AND c.pagado IS NOT TRUE AND c.vigente IS NOT FALSE
				AND c.eseliminado IS NOT TRUE AND c.esanulado IS NOT TRUE ORDER BY m.id_mesa")->result_array(), 'id_mesa'));
	}

	/** Datos para imprimir la precuenta de una mesa (en la impresora de la caja, antes de pagar). */
	/**
	 * Precuenta de la mesa. $seleccion (Dividir cuenta): solo esa parte [{id_detalle, cantidad}], con la misma cuenta del cobro.
	 * $partes >= 2: además, cuánto paga cada una de esas partes iguales (partesIguales).
	 */
	public function precuentaMesa($idMesa, $seleccion = null, $partes = 0)
	{
		$db = $this->conexion();
		$cot = $this->cotizacionDeMesa($db, $idMesa);
		if (!$cot) return null;
		$c = $this->columnas($db, array('cotizacion'));
		$marca = array();
		if (isset($c['cotizacion']['precuenta_impresa'])) $marca[] = 'precuenta_impresa = true';
		if (isset($c['cotizacion']['fecha_hora_precuenta'])) $marca[] = 'fecha_hora_precuenta = now()';
		if ($marca) $this->q($db, 'UPDATE cotizacion SET ' . implode(', ', $marca) . ' WHERE id_cotizacion = ?', array((int)$cot->id_cotizacion));
		$t = $this->q($db, "SELECT to_char(fecha_transaccion, 'DD/MM/YYYY HH24:MI') AS desde FROM cotizacion WHERE id_cotizacion = ?", array((int)$cot->id_cotizacion))->row();
		$config = $this->configuracion($db);
		$lineas = $this->lineasDeCotizacion($db, (int)$cot->id_cotizacion);
		if ($seleccion) $lineas = $this->seleccionDeLineas($lineas, $seleccion);
		return array(
			'id_mesa' => (int)$idMesa, 'nombre_mesa' => $this->etiquetaMesa($idMesa), 'desde' => $t ? $t->desde : '', 'lineas' => $lineas,
			'partes' => !$seleccion && (int)$partes >= 2 ? $this->partesIguales($lineas, (int)$partes) : array(),
			// v1.23.0: la propina sugerida sale en la precuenta solo con "Propina en mesas" y "Mostrar propina en la
			// precuenta" (Configuraciones > Propinas; antes esa casilla no se respetaba y la propina salía siempre)
			'por_propina' => $config['propina_mesas'] && $config['calcular_propina'] ? (float)$config['por_propina'] : 0,
			'con_propina' => $config['propina_mesas'] && $config['calcular_propina'],
			// v1.23.0: el mensaje general de la mesa también sale en la precuenta
			'mensaje' => $this->mensajeDeCotizacion($db, (int)$cot->id_cotizacion),
			'empresa' => $this->empresaPrecuenta($db),
		);
	}

	/** Empresa de la precuenta, con la dirección de la sucursal de la caja abierta del usuario (como los documentos). */
	private function empresaPrecuenta($db)
	{
		$e = $this->q($db, 'SELECT * FROM empresa ORDER BY id_empresa LIMIT 1')->row();
		$caja = $e ? $this->cajaAbierta((int)$this->session->userdata('id_usuario')) : null;
		if (!$caja) return $e;
		$this->load->helper('sucursal_empresa');
		return empresa_con_sucursal($e, sucursal_de_caja($db, (int)$caja->id_caja));
	}

	/**
	 * Dividir cuenta > Partes iguales: cuánto paga cada parte si se cobran una tras otra, igual que lo cobra la Caja (js.php,
	 * dcSimularPartes, misma cuenta paso a paso). Cada parte, menos la última, lleva de cada producto cantidad / partes que
	 * faltan, en milésimas (hacia abajo, o una milésima más si así queda más cerca de lo que le toca: se revisan los productos
	 * en orden) y paga su proporción (seleccionDeLineas / partirLinea); la última paga lo que queda. Devuelve los montos.
	 */
	private function partesIguales($lineas, $n)
	{
		$n = max(1, min(50, (int)$n));
		// Redondeo como Math.round de la Caja (floor(x + 0,5)): así la precuenta muestra lo mismo que la pantalla
		$r = function ($x) { return (int)floor($x + 0.5); };
		$r3 = function ($x) { return floor($x * 1000 + 0.5) / 1000; };
		$sim = array();
		foreach ($lineas as $l) $sim[] = array('c' => $r3((float)$l['cantidad']), 'b' => (float)$l['bruto_exacto']);
		$monto1 = function ($s, $qm) use ($r) {
			$q = $qm / 1000;
			return $q >= $s['c'] - 0.0005 ? $r($s['b']) : $r($s['b'] * $q / $s['c']);
		};
		$montos = array();
		for ($k = $n; $k >= 1; $k--) {
			$resto = 0;
			foreach ($sim as $s) if ($s['c'] > 0) $resto += $r($s['b']);
			$meta = $resto / $k;
			$qs = array();
			$suma = 0;
			foreach ($sim as $i => $s) {
				$cm = $r($s['c'] * 1000);
				$qs[$i] = $s['c'] <= 0 ? 0 : ($k === 1 ? $cm : (int)floor($cm / $k));
				if ($qs[$i] > 0) $suma += $monto1($s, $qs[$i]);
			}
			if ($k > 1) {
				foreach ($sim as $i => $s) {
					if ($s['c'] <= 0 || $r($s['c'] * 1000) % $k === 0) continue;
					$d = $monto1($s, $qs[$i] + 1) - ($qs[$i] > 0 ? $monto1($s, $qs[$i]) : 0);
					if (abs($suma + $d - $meta) < abs($suma - $meta)) { $qs[$i]++; $suma += $d; }
				}
			}
			$monto = 0;
			foreach ($sim as $i => $s) {
				if ($s['c'] <= 0 || $qs[$i] <= 0) continue;
				$q = $qs[$i] / 1000;
				if ($q >= $s['c'] - 0.0005) {
					$monto += $r($s['b']);
					$sim[$i] = array('c' => 0, 'b' => 0);
					continue;
				}
				$parte = $r($s['b'] * $q / $s['c']);
				$monto += $parte;
				$sim[$i] = array('c' => $r3($s['c'] - $q), 'b' => $s['b'] - $parte);
			}
			$montos[] = $monto;
		}
		return $montos;
	}

	/** Anula un producto ya enviado. Si era el último, el pedido se anula y la mesa queda libre. */
	public function anularLineaMesa($caja, $idUsuario, $idRol, $p)
	{
		$db = $this->conexion();
		$idMesa = (int)($p['id_mesa'] ?? 0);
		$idDetalle = (int)($p['id_detalle'] ?? 0);
		$motivo = trim((string)(isset($p['motivo']) ? $p['motivo'] : ''));
		if ($motivo === '') throw new Exception('Indica el motivo de la anulación', 400);
		$this->claveAnulacion($db, 'admin_anular_producto_mesa', 'anular un producto de la mesa', $idUsuario, $idRol, isset($p['autorizacion']) ? $p['autorizacion'] : null);
		$db->trans_begin();
		try {
			$this->q($db, 'SET LOCAL TIME ZONE ' . $db->escape(self::TZ));
			$this->validarMesaUsable($this->bloquearMesa($db, $idMesa), $db);
			$cot = $this->cotizacionDeMesa($db, $idMesa);
			if (!$cot) throw new Exception('La mesa ya no tiene un pedido abierto', 409);
			// Con cantidad menor a la de la línea se anula solo esa parte (ej: 1 de 3 bebidas).
			// 16-11-2026 (QA): una cantidad negativa o que no es número se rechaza (antes -1 anulaba la línea completa);
			// sin cantidad (o vacía) se anula la línea completa, como siempre.
			$cantTxt = isset($p['cantidad']) ? trim((string)$p['cantidad']) : '';
			if ($cantTxt !== '' && (!is_numeric($cantTxt) || (float)$cantTxt <= 0)) {
				throw new Exception('La cantidad a anular debe ser mayor que 0', 400);
			}
			$cantidad = $cantTxt === '' ? 0 : (float)$cantTxt;
			$anuladas = $this->seleccionDeLineas($this->lineasDeCotizacion($db, (int)$cot->id_cotizacion),
				array(array('id_detalle' => $idDetalle, 'cantidad' => $cantidad > 0 ? $cantidad : null)));
			// v1.24.0: anular un menú anula también lo elegido en él (en la misma proporción)
			$this->partirSeleccion($db, $anuladas, (int)$cot->id_cotizacion, array(
				'vigente' => false, 'esanulado' => true, 'motivo_anulacion' => mb_substr($motivo, 0, 100), 'hora_anulacion' => date('Y-m-d H:i:s'),
				'id_caja' => (int)$caja->id_caja, 'id_cajero' => (int)$idUsuario,
			));
			$quedan = count($this->lineasDeCotizacion($db, (int)$cot->id_cotizacion));
			$this->recalcularCotizacion($db, (int)$cot->id_cotizacion);
			if (!$quedan) {
				$this->q($db, 'UPDATE cotizacion SET vigente = false, esanulado = true, motivo_anulacion = ? WHERE id_cotizacion = ?',
					array(mb_substr($motivo, 0, 100), (int)$cot->id_cotizacion));
				$this->desocuparMesa($db, $idMesa);
			}
			$db->trans_commit();
		} catch (Throwable $e) {   // 19-11-2026: también errores de PHP deshacen la transacción
			$db->trans_rollback();
			throw $e;
		}
		return array('liberada' => !$quedan, 'detalle' => $this->detalleMesa($idMesa, false), 'anuladas' => $this->lineasAnulacion($anuladas));
	}

	/** Anula el pedido completo de una mesa ocupada y la deja libre. El stock no se toca: se descuenta recién al cobrar. */
	public function anularPedidoMesa($caja, $idUsuario, $idRol, $p)
	{
		$db = $this->conexion();
		$idMesa = (int)($p['id_mesa'] ?? 0);
		$motivo = mb_substr(trim((string)(isset($p['motivo']) ? $p['motivo'] : '')), 0, 100);
		if ($motivo === '') throw new Exception('Indica el motivo de la anulación', 400);
		$this->claveAnulacion($db, 'admin_anular_pedido_mesa', 'anular el pedido de la mesa', $idUsuario, $idRol, isset($p['autorizacion']) ? $p['autorizacion'] : null);
		$db->trans_begin();
		try {
			$this->q($db, 'SET LOCAL TIME ZONE ' . $db->escape(self::TZ));
			$this->validarMesaUsable($this->bloquearMesa($db, $idMesa), $db);
			$cot = $this->cotizacionDeMesa($db, $idMesa);
			if (!$cot) throw new Exception('La mesa ya no tiene un pedido abierto', 409);
			$anuladas = $this->lineasDeCotizacion($db, (int)$cot->id_cotizacion);
			$this->q($db, "UPDATE detalle_cotizacion SET vigente = false, esanulado = true, motivo_anulacion = ?, hora_anulacion = now(), id_caja = ?, id_cajero = ?
				WHERE id_cotizacion = ? AND vigente IS NOT FALSE AND esanulado IS NOT TRUE AND eseliminado IS NOT TRUE",
				array($motivo, (int)$caja->id_caja, (int)$idUsuario, (int)$cot->id_cotizacion));
			$this->q($db, 'UPDATE cotizacion SET vigente = false, esanulado = true, motivo_anulacion = ? WHERE id_cotizacion = ?', array($motivo, (int)$cot->id_cotizacion));
			$this->desocuparMesa($db, $idMesa);
			$db->trans_commit();
		} catch (Throwable $e) {   // 19-11-2026: también errores de PHP deshacen la transacción
			$db->trans_rollback();
			throw $e;
		}
		return array('ok' => true, 'anuladas' => $this->lineasAnulacion($anuladas));
	}

	/** Líneas anuladas con la forma que espera la comanda. */
	/** Reenvío a cocina: lo ya guardado en el pedido de la mesa (todo, o solo las líneas pedidas). */
	public function comandaReenvio($idMesa, $idsDetalle)
	{
		$db = $this->conexion();
		$cot = $this->cotizacionDeMesa($db, (int)$idMesa);
		if (!$cot) throw new Exception('La mesa no tiene un pedido abierto', 409);
		$lineas = $this->lineasDeCotizacion($db, (int)$cot->id_cotizacion);
		if ($idsDetalle) {
			$ids = array_map('intval', (array)$idsDetalle);
			// v1.24.0: volver a enviar un menú manda lo elegido en él
			$lineas = array_values(array_filter($lineas, function ($l) use ($ids) {
				return in_array((int)$l['id_detalle'], $ids, true) || (!empty($l['menu_de']) && in_array((int)$l['menu_de'], $ids, true));
			}));
		}
		return $this->lineasAnulacion($lineas);
	}

	/**
	 * Líneas para la comanda (reenvío o anulación). v1.24.0: un menú con productos elegidos no sale (salen sus productos,
	 * cada uno a su destino, con el nombre del menú).
	 */
	private function lineasAnulacion($lineas)
	{
		$nombres = array();
		$conHijos = array();
		$sushi = array();   // v1.25.1: promo de sushi (menú sin id_menu) => su producto
		foreach ($lineas as $l) {
			if (!empty($l['es_menu'])) $nombres[$l['id_detalle']] = $l['nombre'];
			if (!empty($l['es_menu']) && empty($l['id_menu'])) $sushi[$l['id_detalle']] = (int)$l['id_producto'];
			if (!empty($l['menu_de'])) $conHijos[$l['menu_de']] = true;
		}
		$res = array();
		foreach ($lineas as $l) {
			if (!empty($l['es_menu']) && isset($conHijos[$l['id_detalle']])) continue;
			if (!empty($l['menu_de']) && isset($l['imprimir']) && !$l['imprimir']) continue;   // producto del menú que no se imprime
			$nota = $l['nota'];
			$idProd = $l['id_producto'];
			if (!empty($l['menu_de']) && isset($sushi[$l['menu_de']])) {
				// v1.25.1: un grupo de una promo de sushi sale igual que en la comanda al enviarla: su nota ya dice la promo
				// ("Relleno - Base X (PROMO)") y va a la impresora de la promo
				$idProd = $sushi[$l['menu_de']];
			} elseif (!empty($l['menu_de']) && isset($nombres[$l['menu_de']])) {
				$nota = 'Menú: ' . $nombres[$l['menu_de']] . ($nota !== '' ? ' · ' . $nota : '');
			}
			$res[] = array('id_producto' => $idProd, 'nombre' => $l['nombre'], 'cantidad' => $l['cantidad'],
				'nota' => $nota, 'agregados' => $l['agregado'] !== '' ? array($l['agregado']) : array());
		}
		return $res;
	}

	/** Libera una mesa bloqueada o marcada ocupada sin pedido abierto. Nunca toca una mesa con pedido abierto. */
	public function liberarMesa($idUsuario, $idRol, $p)
	{
		$db = $this->conexion();
		$idMesa = (int)($p['id_mesa'] ?? 0);
		$this->exigirAutorizacion($db, 'liberar una mesa', $idUsuario, $idRol, isset($p['autorizacion']) ? $p['autorizacion'] : null);
		$db->trans_begin();
		try {
			$this->bloquearMesa($db, $idMesa);
			// Un pedido sin productos (envío incompleto de la app) no impide liberar la mesa; liberarla a mano lo cierra ya
			$this->cerrarPedidosVacios($db, $idMesa, true);
			$cot = $this->cotizacionDeMesa($db, $idMesa);
			if ($cot && $this->lineasDeCotizacion($db, (int)$cot->id_cotizacion)) {
				throw new Exception('La mesa tiene un pedido abierto: cóbralo o anula sus productos', 409);
			}
			$this->desocuparMesa($db, $idMesa);
			$db->trans_commit();
		} catch (Throwable $e) {   // 19-11-2026: también errores de PHP deshacen la transacción
			$db->trans_rollback();
			throw $e;
		}
		return array('ok' => true);
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
			'id_tdocumento_ticket' => 'id_comprobante',
			// Documentos electrónicos asignados en Configuraciones > Facturación electrónica
			'id_tdocumento_boleta_electronica' => 'id_boleta',
			'id_tdocumento_factura' => 'id_factura',
			'id_tdocumento_nota_credito' => 'id_nc',
			'id_impuesto_iva' => 'id_impuesto_iva',
			'copias_dte' => 'copias_documento',
			// 18-11-2026: delivery (migración 20261118100000)
			'delivery_id_producto_envio' => 'delivery_producto_envio',
			'delivery_costo_envio' => 'delivery_costo_envio',
		);
		// Opciones on/off de Configuraciones
		$flags = array(
			'propina_en_venta_directa' => 'propina',
			'calcular_valor_propina' => 'calcular_propina',
			// v1.23.0: usar propina (si no, no sale en nada) y propina en mesas (migración 20261120140000)
			'usar_propina_web' => 'usar_propina',
			'propina_mesas_web' => 'propina_mesas',
			'agrupar_productos_web' => 'agrupar_productos',   // v1.24.8 (migración 20261120170000)
			'requerir_usuario_admin_descuento' => 'admin_descuento',
			'requerir_admin_descuento' => 'admin_descuento_clave',
			'requerir_usuario_admin_cortesia' => 'admin_cortesia',
			'requerir_usuario_admin_credito' => 'admin_credito',
			'admin_anulacion_eliminacion' => 'admin_anulacion',
			// v1.25.3: clave de administrador por tipo de anulación (migración 20261121100000)
			'admin_anular_venta_web' => 'admin_anular_venta',
			'admin_devolucion_web' => 'admin_devolucion',
			'admin_anular_espera_web' => 'admin_anular_espera',
			'admin_anular_producto_mesa_web' => 'admin_anular_producto_mesa',
			'admin_anular_pedido_mesa_web' => 'admin_anular_pedido_mesa',
			'imprimir_cierre_caja_impresora_termica' => 'imprimir_cierre_termica',
			'habilitar_facturacion_electronica' => 'facturacion_electronica',
			'imprimir_copia_cedible' => 'copia_cedible',
			'requiere_cliente_descuento' => 'descuento_cliente',
			'inventario_cierre_caja' => 'inventario_cierre',
			'mostrar_recaudacion_cierre_no_efectivo' => 'llenar_no_efectivo',
			'cierre_caja_manual' => 'cierre_detallado',
			'ver_ingresos_egresos_cierre_caja' => 'cierre_ingresos',
			'ver_creditos_a_clientes_cierre_caja' => 'cierre_creditos',
			'ver_cortesias_cierre_caja' => 'cierre_cortesias',
			'ver_anulaciones_eliminaciones_cierre_caja' => 'cierre_anulaciones',
			'imprimir_comprobante_anulacion' => 'imprimir_anulacion',
			'permitir_retiro_fondo_fijo' => 'retiro_fondo',
			'boton_apertura_caja_solo_admin' => 'admin_gaveta',
			'imprimir_pdf_dte' => 'imprimir_documento',
			'usar_correo_electronico' => 'correo_cierre',
			'informar_anulaciones_mail' => 'correo_anulaciones',
			'delivery_habilitado' => 'delivery',   // 18-11-2026: pestaña Delivery en la Caja
		);
		// Opciones de texto (impresora del cierre, impresora de documentos, papel 58/80)
		$textos = array(
			'nombre_impresora_cierre' => 'impresora_cierre',
			'ruta_dte_pdf' => 'ruta_documentos',
			'formato_dte' => 'formato_papel',
		);
		// Si la base no tiene la columna, se mantiene lo que la caja hacía antes de existir la opción
		$siFalta = array('inventario_cierre', 'llenar_no_efectivo', 'cierre_detallado', 'cierre_ingresos', 'cierre_creditos',
			'cierre_cortesias', 'cierre_anulaciones', 'retiro_fondo', 'imprimir_documento', 'usar_propina', 'propina_mesas', 'agrupar_productos');
		$config = array_fill_keys(array_values($mapa), 0) + array_fill_keys(array_values($flags), false)
			+ array_fill_keys(array_values($textos), '');
		$config['por_iva'] = 19;
		$existentes = array_keys(isset($cols['configuracion']) ? $cols['configuracion'] : array());
		$sel = array_values(array_intersect(array_keys($mapa), $existentes));
		$selFlags = array_values(array_intersect(array_keys($flags), $existentes));
		$selTextos = array_values(array_intersect(array_keys($textos), $existentes));
		foreach ($siFalta as $k) {
			if (!in_array(array_search($k, $flags), $existentes, true)) $config[$k] = true;
		}
		if ($sel || $selFlags || $selTextos) {
			$campos = array_merge($sel, $selTextos);
			foreach ($selFlags as $c) $campos[] = "($c IS TRUE) AS $c";
			$fila = $this->q($db, 'SELECT ' . implode(', ', $campos) . ' FROM configuracion LIMIT 1')->row_array();
			foreach ($sel as $c) {
				if (isset($fila[$c]) && $fila[$c] !== null) $config[$mapa[$c]] = $c === 'por_iva' ? (float)$fila[$c] : (int)$fila[$c];
			}
			foreach ($selFlags as $c) {
				$config[$flags[$c]] = isset($fila[$c]) && ($fila[$c] === 't' || $fila[$c] === true);
			}
			foreach ($selTextos as $c) {
				$config[$textos[$c]] = trim((string)(isset($fila[$c]) ? $fila[$c] : ''));
			}
		}
		// IVA: el porcentaje del impuesto elegido en Configuraciones (Impuestos IVA); si no hay, el de configuracion.por_iva
		if ($config['id_impuesto_iva'] > 0 && isset($this->columnas($db, array('impuesto'))['impuesto']['valor'])) {
			$iva = $this->q($db, 'SELECT valor FROM impuesto WHERE id_impuesto = ?', array($config['id_impuesto_iva']))->row();
			if ($iva && (float)$iva->valor > 0) $config['por_iva'] = (float)$iva->valor;
		}
		// v1.25.3: sin la migración 20261121100000, null = como antes (ver claveAnulacion)
		foreach (array('admin_anular_venta', 'admin_devolucion', 'admin_anular_espera', 'admin_anular_producto_mesa', 'admin_anular_pedido_mesa') as $k) {
			if (!in_array(array_search($k, $flags), $existentes, true)) $config[$k] = null;
		}
		$config['copias_documento'] = max(1, min(5, (int)$config['copias_documento']));
		// v1.23.0: sin "Usar propina" no hay propina en ninguna parte (venta directa, mesas, precuenta)
		$config['propina'] = $config['usar_propina'] && $config['propina'];
		$config['propina_mesas'] = $config['usar_propina'] && $config['propina_mesas'];
		// Documentos de la caja directo al agente de impresión del equipo (impresionWeb.exe, localhost:5050), como mipdv_web.
		// 18-11-2026 (usuario: "todos usan el .exe"): siempre encendida. Se quitó la casilla de Configuraciones; la columna
		// impresion_local_caja queda en la base pero ya no se lee.
		$config['impresion_local'] = true;
		// 14-11-2026: imprimir las comandas de la app de garzones (migración 20261114130000; NULL o sin la columna = apagada)
		$config['comandas_app'] = false;
		if (in_array('imprimir_comandas_app', $existentes, true)) {
			$ca = $this->q($db, 'SELECT (imprimir_comandas_app IS TRUE) AS ok FROM configuracion LIMIT 1')->row();
			if ($ca) $config['comandas_app'] = $ca->ok === 't' || $ca->ok === true;
		}
		// Boletas, precuentas y comandas con el diseño de la página, como imagen por el agente. 18-11-2026: siempre encendida
		// (se quitó la casilla; documentos_en_navegador ya no se lee). Si la imagen falla, sale con el formato del agente.
		$config['documentos_navegador'] = true;
		// 16-11-2026: también los pedidos que el celular manda con "imprimir": 0 (migración 20261116110000; NULL = apagada)
		$config['comandas_app_forzar'] = false;
		if ($config['comandas_app'] && in_array('imprimir_comandas_app_forzar', $existentes, true)) {
			$cf = $this->q($db, 'SELECT (imprimir_comandas_app_forzar IS TRUE) AS ok FROM configuracion LIMIT 1')->row();
			if ($cf) $config['comandas_app_forzar'] = $cf->ok === 't' || $cf->ok === true;
		}
		// Bases sin la opción: se mantiene la facturación electrónica disponible
		if (!in_array('habilitar_facturacion_electronica', $existentes)) $config['facturacion_electronica'] = true;
		$config['admin_descuento'] = $config['admin_descuento'] || $config['admin_descuento_clave'];
		unset($config['admin_descuento_clave']);
		if (!$config['id_cliente_publico']) {
			$r = $this->q($db, 'SELECT min(id_cliente) AS id FROM cliente')->row();
			$config['id_cliente_publico'] = $r ? (int)$r->id : 0;
		}
		// Herramientas > Control de cajas: la caja elegida al abrir el turno trae su lista de precios (reemplaza a la pública
		// de Configuraciones), su bodega para el stock y si el cajero puede cambiar la lista
		$config['caja_control'] = null;
		$config['id_bodega_caja'] = 0;
		$config['caja_fija_lista'] = false;
		$cc = $this->cajaControl($db);
		if ($cc) {
			if ($cc['id_lprecio'] > 0) $config['id_lprecio_publico'] = $cc['id_lprecio'];
			$config['id_bodega_caja'] = $cc['id_bodega'];
			$config['caja_fija_lista'] = !$cc['permitir_cambio_lista'];
			$config['caja_control'] = $cc;
		}
		if (!$config['id_lprecio_publico']) {
			$r = $this->q($db, 'SELECT min(id_lprecio) AS id FROM lprecio')->row();
			$config['id_lprecio_publico'] = $r ? (int)$r->id : 0;
		}
		return $config;
	}

	/**
	 * Caja de Herramientas > Control de cajas con que se abrió el turno abierto del usuario de la sesión
	 * (caja.id_control_caja, migración 20261111190000). null: sin la migración o turno abierto sin caja elegida (como antes).
	 * Una lista o bodega que ya no existe no se usa (queda la pública / la de cada producto).
	 */
	private $cajaControl = false;   // false = aún no se lee en esta petición
	private function cajaControl($db)
	{
		if ($this->cajaControl !== false) return $this->cajaControl;
		$this->cajaControl = null;
		$c = $this->columnas($db, array('caja', 'control_caja'));
		if (!isset($c['caja']['id_control_caja'], $c['control_caja']['id_lprecio'], $c['control_caja']['id_bodega'],
			$c['control_caja']['permitir_cambio_lprecio_caja'], $c['control_caja']['nombre_caja'])) return null;
		$idUsuario = (int)$this->session->userdata('id_usuario');
		if ($idUsuario <= 0) return null;
		$c = $this->q($db, "SELECT COALESCE(id_control_caja, 0) AS id FROM caja WHERE cerrada IS NOT TRUE AND nombre_computador = ? AND id_usuario = ?
			ORDER BY id_caja DESC LIMIT 1", array(self::COMPUTADOR, $idUsuario))->row();
		return $this->cajaControl = $c ? $this->datosControlCaja($db, (int)$c->id) : null;
	}

	/** Nombre, lista, bodega y cambio de lista de una caja de Control de cajas (null si no existe o falta la migración). */
	private function datosControlCaja($db, $idControlCaja)
	{
		if ($idControlCaja <= 0) return null;
		$c = $this->columnas($db, array('control_caja'));
		if (!isset($c['control_caja']['id_lprecio'], $c['control_caja']['id_bodega'], $c['control_caja']['permitir_cambio_lprecio_caja'],
			$c['control_caja']['nombre_caja'])) return null;
		$r = $this->q($db, "SELECT cc.id_control_caja, COALESCE(cc.caja_n, 0) AS caja_n, COALESCE(cc.nombre_caja, '') AS nombre_caja,
				COALESCE(l.id_lprecio, 0) AS id_lprecio, COALESCE(l.nombre_lprecio, '') AS lista,
				COALESCE(b.id_bodega, 0) AS id_bodega, COALESCE(b.nombre_bodega, '') AS bodega,
				(cc.permitir_cambio_lprecio_caja IS TRUE) AS permitir
			FROM control_caja cc
			LEFT JOIN lprecio l ON l.id_lprecio = cc.id_lprecio AND cc.id_lprecio > 0
			LEFT JOIN bodega b ON b.id_bodega = cc.id_bodega AND cc.id_bodega > 0
			WHERE cc.id_control_caja = ?", array((int)$idControlCaja))->row();
		if (!$r) return null;
		return array(
			'id' => (int)$r->id_control_caja,
			'caja_n' => (int)$r->caja_n,
			'nombre' => $r->nombre_caja !== '' ? $r->nombre_caja : 'Caja ' . (int)$r->caja_n,
			'id_lprecio' => (int)$r->id_lprecio,
			'lista' => (string)$r->lista,
			'id_bodega' => (int)$r->id_bodega,
			'bodega' => (string)$r->bodega,
			'permitir_cambio_lista' => $r->permitir === 't' || $r->permitir === true,
		);
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
				'credito' => $this->esCredito($config, $f->id_fpago, $f->nombre_fpago),
			);
		}
		return $res;
	}

	/**
	 * Forma de pago de crédito a clientes (fiado): la de Configuraciones. Si no está configurada (0), la que se llama
	 * "CREDITO..." sin ser tarjeta (ej. CREDITO DE CONFIANZA), igual que el efectivo se reconoce por su nombre.
	 */
	private function esCredito($config, $idFpago, $nombre)
	{
		if ($config['id_fpago_credito'] > 0) return (int)$idFpago === $config['id_fpago_credito'];
		$n = strtoupper(strtr((string)$nombre, array('É' => 'E', 'é' => 'E')));
		return strpos($n, 'CREDITO') !== false && strpos($n, 'TARJETA') === false;
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
		$tipos = $this->q($db, $q . ' ORDER BY id_tdocumento')->result();
		// En la caja se vende solo con: el comprobante interno (siempre) y, con la facturación electrónica habilitada, la
		// boleta y la factura ASIGNADAS en Configuraciones > Facturación electrónica. Sin facturación electrónica, o sin
		// asignar, esos documentos no se pueden usar.
		$config = $this->configuracion($db);
		$asignados = $config['facturacion_electronica'] ? array_filter(array($config['id_boleta'], $config['id_factura'])) : array();
		$tipos = array_values(array_filter($tipos, function ($d) use ($config, $asignados) {
			$id = (int)$d->id_tdocumento;
			if (in_array($id, $asignados, true)) return true;
			return $config['id_comprobante']
				? $id === $config['id_comprobante']
				: stripos($d->nombre_tdocumento, 'COMPROBANTE') !== false && stripos($d->nombre_tdocumento, 'NOTA') === false;
		}));
		$res = array();
		foreach ($tipos as $d) {
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
				// Los electrónicos (ya filtrados: habilitados y asignados) solo si la empresa emite por Cloud Apps
				'habilitado' => !in_array($cod, self::COD_SII_ELECTRONICOS)
					|| (in_array($cod, self::COD_SII_EMITIBLES) && $this->dteDisponible($db)),
			);
			$i = count($res) - 1;
			// Por qué no se puede usar (ej. "Falta el terminal en Acceso facturación"), para mostrarlo en la Caja
			$res[$i]['motivo'] = $res[$i]['habilitado'] ? '' : (in_array($cod, self::COD_SII_EMITIBLES) ? $this->dteMotivo($db) : 'no se emite desde la caja web');
		}
		return $res;
	}

	// ====================== Helpers ======================

	/** Abreviatura para mostrar junto a la cantidad (kg, g, lt...); vacía para "unidad". */
	private function abreviaturaUmedida($nombre)
	{
		$n = strtoupper(trim((string)$nombre));
		if ($n === '' || strpos($n, 'UNID') === 0) return '';
		$mapa = array('KILO' => 'kg', 'GRAMO' => 'g', 'LITRO' => 'lt', 'MILILITRO' => 'ml', 'METRO' => 'm', 'CENTIMETRO' => 'cm');
		foreach ($mapa as $k => $abr) {
			if (strpos($n, $k) === 0) return $abr;
		}
		return strtolower(mb_substr($n, 0, 6));
	}

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

	/**
	 * Expresión SQL "el producto es exento de IVA": producto.impto_iva apunta a impuesto y el exento
	 * es el de valor 0. Sin esas columnas (otras versiones de la base) todo es afecto.
	 */
	private function sqlExento($db, $alias)
	{
		$c = $this->columnas($db, array('producto', 'impuesto'));
		if (!isset($c['producto']['impto_iva']) || !isset($c['impuesto']['valor'])) return 'false';
		return "COALESCE((SELECT i.valor = 0 FROM impuesto i WHERE i.id_impuesto = $alias.impto_iva), false)";
	}

	/**
	 * Montos del documento a partir de las líneas brutas (precio de lista, IVA incluido si es afecto).
	 * $lineas: [['bruto' => int, 'exento' => bool], ...]; $descuento: descuento global en pesos (bruto).
	 *
	 * - Exentos: su monto queda fuera del IVA y se informa como Monto Exento.
	 * - Facturas (precio neto): cada línea afecta pasa a neto (round(bruto / 1,19)); Neto = suma de líneas
	 *   - descuento neto; IVA = round(Neto x 19%); Total = Neto + IVA + Exento.
	 * - Boletas y documentos internos (precio bruto): Neto = round(Afecto / 1,19); IVA = Afecto - Neto;
	 *   Total = Afecto + Exento (la suma de los precios que ve el cliente).
	 * El descuento global se reparte entre lo afecto y lo exento según su peso. La pantalla de venta
	 * (totales() en views/caja/js.php) repite este cálculo: si se cambia aquí, se cambia allá.
	 */
	private function montos($lineas, $descuento, $esFactura, $porIva)
	{
		$subtotal = 0;
		$subAfecto = 0;
		// v1.20.0: lo afecto se agrupa por tasa de ILA (impuesto adicional del producto, 0 = sin ILA). La base del ILA es
		// la misma del IVA (el neto): bruto = neto x (1 + (IVA + ILA) / 100). Sin productos con ILA hay un solo grupo
		// (tasa 0) y los montos son exactamente los de antes.
		$grupos = array();
		foreach ($lineas as $l) {
			$subtotal += $l['bruto'];
			if (empty($l['exento'])) {
				$subAfecto += $l['bruto'];
				$k = $this->claveIla($l);
				if (!isset($grupos[$k])) $grupos[$k] = array('tasa' => $this->tasaIlaLinea($l), 'bruto' => 0, 'neto_lineas' => 0);
				$grupos[$k]['bruto'] += $l['bruto'];
				$grupos[$k]['neto_lineas'] += (int)round($l['bruto'] / (1 + ($porIva + $grupos[$k]['tasa']) / 100));
			}
		}
		$subExento = $subtotal - $subAfecto;
		$descuento = min(max(0, (int)$descuento), $subtotal);
		$descAfecto = $subtotal > 0 ? (int)round($descuento * $subAfecto / $subtotal) : 0;
		$descExento = $descuento - $descAfecto;
		$exento = $subExento - $descExento;

		// Descuento de lo afecto repartido entre los grupos según su monto (el último lleva el resto)
		$resto = $descAfecto;
		$claves = array_keys($grupos);
		$ultimaClave = end($claves);
		foreach ($grupos as $k => $g) {
			$d = $k === $ultimaClave ? $resto : ($subAfecto > 0 ? (int)round($descAfecto * $g['bruto'] / $subAfecto) : 0);
			$resto -= $d;
			$grupos[$k]['descuento'] = $d;
		}

		$neto = 0;
		$ila = 0;
		foreach ($grupos as $k => $g) {
			$f = 1 + ($porIva + $g['tasa']) / 100;
			if ($esFactura) {
				$ng = $g['neto_lineas'] - (int)round($g['descuento'] / $f);
			} else {
				$ng = (int)round(($g['bruto'] - $g['descuento']) / $f);
			}
			$grupos[$k]['neto'] = $ng;
			$grupos[$k]['ila'] = (int)round($ng * $g['tasa'] / 100);
			$neto += $ng;
			$ila += $grupos[$k]['ila'];
		}
		if ($esFactura) {
			$iva = (int)round($neto * $porIva / 100);
		} else {
			$iva = ($subAfecto - $descAfecto) - $neto - $ila;
		}

		// Descuento por línea (dentro de su grupo, afecto o exento) y montos de cada línea para detalle_venta
		$detalle = array();
		$resto = array('a' => $descAfecto, 'e' => $descExento);
		$base = array('a' => $subAfecto, 'e' => $subExento);
		$ultimo = array();
		foreach ($lineas as $i => $l) $ultimo[empty($l['exento']) ? 'a' : 'e'] = $i;
		foreach ($lineas as $i => $l) {
			$g = empty($l['exento']) ? 'a' : 'e';
			$total = $g === 'a' ? $descAfecto : $descExento;
			$desc = $i === $ultimo[$g] ? $resto[$g] : ($base[$g] > 0 ? (int)round($total * $l['bruto'] / $base[$g]) : 0);
			$resto[$g] -= $desc;
			$conDesc = $l['bruto'] - $desc;
			$tasaIla = $g === 'e' ? 0.0 : $this->tasaIlaLinea($l);
			$factorLinea = 1 + ($porIva + $tasaIla) / 100;
			$netoLinea = $g === 'e' ? $l['bruto'] : $l['bruto'] / $factorLinea;
			$netoConDesc = $g === 'e' ? $conDesc : $conDesc / $factorLinea;
			$detalle[] = array(
				'descuento' => $desc,
				'con_descuento' => $conDesc,
				'exento' => $g === 'e',
				'neto' => $netoLinea,
				'neto_con_descuento' => $netoConDesc,
				// v1.20.0: ILA de la línea (tasa y montos, sin y con descuento) y su factor bruto/neto
				'porila' => $tasaIla,
				'factor' => $g === 'e' ? 1 : $factorLinea,
				'ila' => $netoLinea * $tasaIla / 100,
				'ila_con_descuento' => $netoConDesc * $tasaIla / 100,
			);
		}

		return array(
			'subtotal' => $subtotal,
			'descuento' => $descuento,
			'exento' => $exento,
			'neto' => $neto,
			'iva' => $iva,
			'ila' => $ila,
			'total' => $neto + $iva + $ila + $exento,
			'lineas' => $detalle,
		);
	}

	/** v1.20.0: tasa de ILA de una línea (0 si no tiene o es exenta). */
	private function tasaIlaLinea($l)
	{
		return !empty($l['exento']) ? 0.0 : max(0.0, (float)(isset($l['ila']) ? $l['ila'] : 0));
	}

	/** v1.20.0: clave del grupo de ILA de una línea ('r0' = sin ILA). Texto, para que PHP no la convierta en índice. */
	private function claveIla($l)
	{
		return 'r' . number_format($this->tasaIlaLinea($l), 2, '.', '');
	}

	/**
	 * v1.20.0: agrega a cada línea la tasa de ILA de su producto ('ila', en %): producto.impto_ila -> impuesto.valor.
	 * Lo exento no lleva ILA. Sin la columna impto_ila (otras versiones de la base) todo queda sin ILA, como antes.
	 */
	private function conIla($db, $lineas)
	{
		$c = $this->columnas($db, array('producto', 'impuesto'));
		$ids = array();
		$de = function ($l) { return (int)(isset($l['ila_de']) ? $l['ila_de'] : $l['id_producto']); };
		foreach ($lineas as $l) if (empty($l['exento']) && $de($l) > 0) $ids[$de($l)] = true;
		$tasas = array();
		if ($ids && isset($c['producto']['impto_ila']) && isset($c['impuesto']['valor'])) {
			foreach ($this->q($db, 'SELECT p.id_producto, COALESCE(i.valor, 0) AS tasa FROM producto p
					LEFT JOIN impuesto i ON i.id_impuesto = p.impto_ila
					WHERE p.id_producto IN (' . implode(',', array_keys($ids)) . ')')->result() as $r) {
				$tasas[(int)$r->id_producto] = max(0.0, (float)$r->tasa);
			}
		}
		foreach ($lineas as $i => $l) {
			$lineas[$i]['ila'] = empty($l['exento']) && isset($tasas[$de($l)]) ? $tasas[$de($l)] : 0.0;
		}
		return $lineas;
	}

	/** v1.20.0: expresión SQL con la tasa de ILA del producto (0 si no tiene). Para el catálogo y las mesas de la Caja. */
	private function sqlIla($db, $alias)
	{
		$c = $this->columnas($db, array('producto', 'impuesto'));
		if (!isset($c['producto']['impto_ila']) || !isset($c['impuesto']['valor'])) return '0';
		return "COALESCE((SELECT i.valor FROM impuesto i WHERE i.id_impuesto = $alias.impto_ila), 0)";
	}

	/**
	 * v1.20.0: neto, IVA e ILA de un monto afecto ya fijado (una nota de crédito que devuelve lo pagado), repartido entre
	 * las tasas de ILA de las líneas según su monto. Sin ILA es netoDesdeAfecto() de siempre.
	 */
	private function desgloseAfecto($lineas, $afecto, $porIva, $comoFactura)
	{
		$grupos = array();
		$base = 0;
		foreach ($lineas as $l) {
			if (!empty($l['exento'])) continue;
			$k = $this->claveIla($l);
			if (!isset($grupos[$k])) $grupos[$k] = array('tasa' => $this->tasaIlaLinea($l), 'bruto' => 0);
			$grupos[$k]['bruto'] += $l['bruto'];
			$base += $l['bruto'];
		}
		if (!$grupos || $afecto <= 0) return array('neto' => 0, 'iva' => 0, 'ila' => 0);
		$resto = $afecto;
		$claves = array_keys($grupos);
		$ultimaClave = end($claves);
		$neto = 0;
		$ila = 0;
		foreach ($grupos as $k => $g) {
			$af = $k === $ultimaClave ? $resto : ($base > 0 ? (int)round($afecto * $g['bruto'] / $base) : 0);
			$resto -= $af;
			if ($g['tasa'] <= 0) {
				$ng = $this->netoDesdeAfecto($af, $porIva, $comoFactura && count($grupos) === 1);
				$ig = 0;
			} else {
				$ng = (int)round($af / (1 + ($porIva + $g['tasa']) / 100));
				$ig = (int)round($ng * $g['tasa'] / 100);
			}
			$neto += $ng;
			$ila += $ig;
		}
		return array('neto' => $neto, 'iva' => $afecto - $neto - $ila, 'ila' => $ila);
	}

	/**
	 * Neto de un monto afecto (IVA incluido) ya fijado, como el de una nota de crédito que devuelve lo pagado.
	 * Si debe cuadrar como factura, se busca el neto con Neto + round(Neto x 19%) = Afecto; algunos montos no
	 * tienen ese neto exacto y quedan con IVA = Afecto - Neto.
	 */
	private function netoDesdeAfecto($afecto, $porIva, $comoFactura)
	{
		$neto = (int)round($afecto / (1 + $porIva / 100));
		if ($comoFactura) {
			foreach (array(0, -1, 1, -2, 2) as $d) {
				if (($neto + $d) + (int)round(($neto + $d) * $porIva / 100) === $afecto) return $neto + $d;
			}
		}
		return $neto;
	}

	/**
	 * 14-11-2026: evita registrar dos veces el mismo cobro (doble clic, reintento por red lenta, dos pestañas).
	 * La pantalla manda un token por cobro y lo repite al reintentar. Se inserta en la transacción de la venta:
	 * un segundo envío espera a que termine el primero y, si ya quedó grabado, se rechaza con 409.
	 * Sin la tabla (migración 20261114100000 no aplicada) o sin token, todo sigue como antes. Devuelve el token usado o ''.
	 */
	private function reservarTokenVenta($db, $p, $idCaja, $idUsuario)
	{
		$token = isset($p['token']) ? preg_replace('/[^A-Za-z0-9_-]/', '', (string)$p['token']) : '';
		if (strlen($token) < 16 || strlen($token) > 64) return '';
		$t = $this->columnas($db, array('caja_venta_token'));
		if (!isset($t['caja_venta_token']['token'])) return '';
		$ok = $this->q($db, 'INSERT INTO caja_venta_token (token, id_caja, id_usuario) VALUES (?, ?, ?)
			ON CONFLICT (token) DO NOTHING RETURNING token', array($token, (int)$idCaja, (int)$idUsuario))->row();
		if ($ok) return $token;
		$prev = $this->q($db, 'SELECT v.id_venta, v.folio FROM caja_venta_token t LEFT JOIN venta v ON v.id_venta = t.id_venta
			WHERE t.token = ?', array($token))->row();
		$cual = ($prev && $prev->id_venta) ? ' (venta ' . (int)$prev->id_venta . ($prev->folio ? ', folio ' . (int)$prev->folio : '') . ')' : '';
		throw new Exception('Este cobro ya se registró' . $cual . '. No se volvió a cobrar: revisa Documentos antes de intentarlo de nuevo.', 409);
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
	// ---------------- Datos de la factura (venta_receptor, migración 20261111180000) ----------------

	/** ¿Existe la tabla venta_receptor? Si falta, la factura usa la ficha del cliente, como antes. */
	private $hayReceptor = null;
	private $hayMotivoReceptor = null;
	/** venta_receptor.motivo (migración 20261112100000): glosa libre de la factura */
	private function hayMotivoReceptor($db)
	{
		if ($this->hayMotivoReceptor === null) {
			$r = $this->q($db, "SELECT count(*) AS n FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'venta_receptor' AND column_name = 'motivo'")->row();
			$this->hayMotivoReceptor = $r && (int)$r->n > 0;
		}
		return $this->hayMotivoReceptor;
	}
	private function hayReceptor($db)
	{
		if ($this->hayReceptor === null) {
			$r = $this->q($db, "SELECT to_regclass('public.venta_receptor') IS NOT NULL AS ok")->row();
			$this->hayReceptor = $r && ($r->ok === 't' || $r->ok === true);
		}
		return $this->hayReceptor;
	}

	/**
	 * Para la ventana "Datos de la factura": la ficha del cliente y lo usado antes en sus facturas
	 * (combinaciones de giro / dirección / comuna y quiénes las solicitaron), lo más reciente primero.
	 */
	public function receptorCliente($idCliente)
	{
		$db = $this->conexion();
		$c = $this->q($db, "SELECT c.id_cliente, COALESCE(c.nombre_cliente, '') AS nombre_cliente, COALESCE(c.rut, 0) AS rut, COALESCE(c.dv, '') AS dv,
				COALESCE(c.giro, '') AS giro, COALESCE(c.direccion, '') AS direccion, COALESCE(c.id_comuna, 0) AS id_comuna,
				COALESCE(co.nombre_comuna, '') AS comuna
			FROM cliente c LEFT JOIN comuna co ON co.id_comuna = c.id_comuna WHERE c.id_cliente = ?", array((int)$idCliente))->row();
		if (!$c) throw new Exception('El cliente no existe', 404);
		$res = array(
			'disponible' => $this->hayReceptor($db),
			'cliente' => array('id_cliente' => (int)$c->id_cliente, 'rut' => (int)$c->rut, 'dv' => strtoupper(trim((string)$c->dv)),
				'razon_social' => trim((string)$c->nombre_cliente), 'giro' => trim((string)$c->giro), 'direccion' => trim((string)$c->direccion),
				'id_comuna' => (int)$c->id_comuna, 'comuna' => trim((string)$c->comuna)),
			'anteriores' => array(),
			'solicitantes' => array(),
		);
		if (!$res['disponible']) return $res;
		foreach ($this->q($db, "SELECT razon_social, giro, direccion, id_comuna, comuna, ciudad, contacto, to_char(max(fecha), 'DD/MM/YYYY') AS fecha
				FROM venta_receptor WHERE id_cliente = ?
				GROUP BY razon_social, giro, direccion, id_comuna, comuna, ciudad, contacto
				ORDER BY max(fecha) DESC LIMIT 10", array((int)$idCliente))->result() as $r) {
			$res['anteriores'][] = array('razon_social' => $r->razon_social, 'giro' => $r->giro, 'direccion' => $r->direccion,
				'id_comuna' => (int)$r->id_comuna, 'comuna' => $r->comuna, 'ciudad' => $r->ciudad, 'contacto' => $r->contacto, 'fecha' => $r->fecha);
		}
		$historial = array();
		foreach ($this->q($db, "SELECT rut_solicita, dv_solicita, (array_agg(nombre_solicita ORDER BY fecha DESC))[1] AS nombre
				FROM venta_receptor WHERE id_cliente = ? AND COALESCE(rut_solicita, 0) > 0
				GROUP BY rut_solicita, dv_solicita ORDER BY max(fecha) DESC LIMIT 15", array((int)$idCliente))->result() as $r) {
			$historial[] = array('rut' => (int)$r->rut_solicita, 'dv' => (string)$r->dv_solicita, 'nombre' => (string)$r->nombre);
		}
		// Listas para elegir (12-11-2026): la ficha del cliente, sus direcciones / giros / solicitantes (Clientes) y lo usado
		// en sus facturas anteriores, sin repetir
		$this->load->model('Mclientefactura');
		$ficha = $this->Mclientefactura->listas((int)$idCliente);
		$res['guardar_ficha'] = $ficha['disponible'];
		$vistos = array();
		$res['giros'] = array();
		$giros = array(array('giro' => $res['cliente']['giro'], 'origen' => 'ficha'));
		foreach ($ficha['giros'] as $g) $giros[] = array('giro' => $g['giro'], 'origen' => 'lista');
		foreach ($res['anteriores'] as $a) $giros[] = array('giro' => $a['giro'], 'origen' => 'anterior');
		foreach ($giros as $g) {
			$g['giro'] = trim((string)$g['giro']);
			$k = mb_strtoupper($g['giro']);
			if ($g['giro'] === '' || isset($vistos[$k])) continue;
			$vistos[$k] = 1;
			$res['giros'][] = $g;
		}
		$vistos = array();
		$res['direcciones'] = array();
		$c = $res['cliente'];
		$dirs = array(array('direccion' => $c['direccion'], 'id_comuna' => $c['id_comuna'], 'comuna' => $c['comuna'], 'ciudad' => '', 'origen' => 'ficha'));
		foreach ($ficha['direcciones'] as $d) $dirs[] = array('direccion' => $d['direccion'], 'id_comuna' => $d['id_comuna'], 'comuna' => $d['comuna'], 'ciudad' => $d['ciudad'], 'origen' => 'lista');
		foreach ($res['anteriores'] as $a) $dirs[] = array('direccion' => $a['direccion'], 'id_comuna' => $a['id_comuna'], 'comuna' => $a['comuna'], 'ciudad' => (string)$a['ciudad'], 'origen' => 'anterior');
		foreach ($dirs as $d) {
			$d['direccion'] = trim((string)$d['direccion']);
			$k = mb_strtoupper($d['direccion']) . '|' . (int)$d['id_comuna'];
			if ($d['direccion'] === '' || isset($vistos[$k])) continue;
			$vistos[$k] = 1;
			$res['direcciones'][] = $d;
		}
		$vistos = array();
		foreach (array_merge($ficha['solicitantes'], $historial) as $s) {
			if (isset($vistos[$s['rut']])) continue;
			$vistos[$s['rut']] = 1;
			$res['solicitantes'][] = array('rut' => (int)$s['rut'], 'dv' => strtoupper((string)$s['dv']), 'nombre' => (string)$s['nombre']);
		}
		return $res;
	}

	/** Valida y limpia los datos de la factura que manda la Caja (todos obligatorios, salvo ciudad y contacto). */
	private function datosFactura($db, $cliente, $f)
	{
		$t = function ($k, $max) use ($f) {
			return mb_substr(trim(preg_replace('/\s+/u', ' ', (string)(isset($f[$k]) ? $f[$k] : ''))), 0, $max);
		};
		$r = array('razon_social' => $t('razon_social', 100), 'giro' => $t('giro', 80), 'direccion' => $t('direccion', 100),
			'id_comuna' => (int)(isset($f['id_comuna']) ? $f['id_comuna'] : 0), 'comuna' => '', 'ciudad' => $t('ciudad', 40),
			'contacto' => $t('contacto', 80), 'rut_solicita' => 0, 'dv_solicita' => '', 'nombre_solicita' => $t('nombre_solicita', 80),
			// Motivo de la solicitud: glosa libre que se imprime en la factura (opcional)
			'motivo' => $t('motivo', 200),
			// Lo nuevo (dirección, giro, solicitante) se agrega a la ficha del cliente, salvo que se desmarque
			'guardar_ficha' => !isset($f['guardar_ficha']) || !in_array($f['guardar_ficha'], array(false, 0, '0', 'false'), true));
		if ($r['razon_social'] === '') $r['razon_social'] = mb_substr(trim((string)$cliente->nombre_cliente), 0, 100);
		if ($r['giro'] === '') throw new Exception('Indica el giro del cliente para la factura', 400);
		if ($r['direccion'] === '') throw new Exception('Indica la dirección del cliente para la factura', 400);
		$co = $r['id_comuna'] ? $this->q($db, 'SELECT nombre_comuna FROM comuna WHERE id_comuna = ?', array($r['id_comuna']))->row() : null;
		if (!$co) throw new Exception('Indica la comuna del cliente para la factura', 400);
		$r['comuna'] = mb_substr(trim((string)$co->nombre_comuna), 0, 40);
		$rutSol = trim((string)(isset($f['rut_solicita']) ? $f['rut_solicita'] : ''));
		if ($rutSol === '') throw new Exception('Indica el RUT de quien solicita la factura', 400);
		try {
			list($r['rut_solicita'], $r['dv_solicita']) = $this->separarRut($rutSol);
		} catch (Exception $e) {
			throw new Exception('El RUT de quien solicita la factura no es válido', 400);
		}
		if ($r['nombre_solicita'] === '') throw new Exception('Indica el nombre de quien solicita la factura', 400);
		return $r;
	}

	private function guardarReceptor($db, $idVenta, $idCliente, $fac, $fecha)
	{
		if ($this->hayMotivoReceptor($db)) {
			$this->q($db, 'INSERT INTO venta_receptor (id_venta, id_cliente, razon_social, giro, direccion, id_comuna, comuna, ciudad, contacto,
					rut_solicita, dv_solicita, nombre_solicita, fecha, motivo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
				ON CONFLICT (id_venta) DO NOTHING',
				array((int)$idVenta, (int)$idCliente, $fac['razon_social'], $fac['giro'], $fac['direccion'], (int)$fac['id_comuna'], $fac['comuna'],
					$fac['ciudad'], $fac['contacto'], (int)$fac['rut_solicita'], $fac['dv_solicita'], $fac['nombre_solicita'], $fecha, $fac['motivo']));
			return;
		}
		$this->q($db, 'INSERT INTO venta_receptor (id_venta, id_cliente, razon_social, giro, direccion, id_comuna, comuna, ciudad, contacto,
				rut_solicita, dv_solicita, nombre_solicita, fecha) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
			ON CONFLICT (id_venta) DO NOTHING',
			array((int)$idVenta, (int)$idCliente, $fac['razon_social'], $fac['giro'], $fac['direccion'], (int)$fac['id_comuna'], $fac['comuna'],
				$fac['ciudad'], $fac['contacto'], (int)$fac['rut_solicita'], $fac['dv_solicita'], $fac['nombre_solicita'], $fecha));
	}

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
