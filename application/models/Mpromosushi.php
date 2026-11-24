<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * v1.25.0: Promociones de sushi armables (Productos > Promos Sushi). Ver la migración 20261120180000_promo_sushi_web.
 *
 * Una promo es un producto de la Carta (se vende en la Caja con su precio) más sus reglas en promo_sushi_web y su
 * catálogo en promo_sushi_opcion_web: bases, rellenos, envoltorios y extras (salsas, etc.). Cada opción es un producto
 * (con receta, si la tiene, para descontar stock) y un valor adicional (0 = no cobra).
 * La venta (lo que eligió el cliente) la arma y valida Mcaja::armarLineas.
 */
class Mpromosushi extends CI_Model
{
	const TIPOS = array('base', 'relleno', 'envoltorio', 'extra', 'salsa');   // v1.32.0: salsa (migración 20261123110000)

	private function db()
	{
		$this->load->helper('tenant');
		return get_tenant_db($this);
	}

	/** ¿La base ya tiene las tablas (migración 20261120180000)? Lectura del catálogo, sin locks. */
	public function disponible($db = null)
	{
		$db = $db ?: $this->db();
		$r = $db->query("SELECT count(*) AS n FROM information_schema.tables
			WHERE table_schema = 'public' AND table_name IN ('promo_sushi_web', 'promo_sushi_opcion_web')")->row();
		return $r && (int)$r->n === 2;
	}

	/** v1.26.0: ¿ya tiene promos por roll (migración 20261121110000)? */
	private function tieneRol($db)
	{
		$r = $db->query("SELECT count(*) AS n FROM information_schema.columns
			WHERE table_schema = 'public' AND table_name = 'promo_sushi_web' AND column_name IN ('modo', 'piezas_rol')")->row();
		return $r && (int)$r->n === 2;
	}

	/** v1.32.0: ¿ya tiene promo fija, precio por envoltorio, máximos y salsas (migración 20261123110000)? */
	private function conOpciones($db)
	{
		$r = $db->query("SELECT count(*) AS n FROM information_schema.columns WHERE table_schema = 'public'
			AND ((table_name = 'promo_sushi_web' AND column_name IN ('fija_web', 'max_salsas_web')) OR (table_name = 'promo_sushi_opcion_web' AND column_name = 'precio_promo_web'))")->row();
		return $r && (int)$r->n === 3;
	}

	/** v1.32.0: columnas nuevas para los SELECT (sin la migración: como antes) */
	private function selOpciones($db, $s = 's')
	{
		return $this->conOpciones($db)
			? "$s.fija_web, ($s.armado_fijo_web IS NOT NULL) AS con_armado, $s.precio_envoltorio_web, $s.max_envoltorios_web, $s.max_extras_web, $s.max_salsas_web"
			: 'false AS fija_web, false AS con_armado, false AS precio_envoltorio_web, 1 AS max_envoltorios_web, 0 AS max_extras_web, 0 AS max_salsas_web';
	}

	/** v1.32.0: Configuraciones > Promos de sushi */
	private function configSushi($db)
	{
		$this->load->model('Mcaja');
		return $this->Mcaja->opcionesSushi();
	}

	private static function si($v) { return $v === true || $v === 't'; }

	/** Columnas de modo para los SELECT (sin la migración: todas por pieza) */
	private function selModo($db, $s = 's')
	{
		return $this->tieneRol($db) ? "$s.modo, $s.piezas_rol" : "'pieza'::varchar AS modo, 10 AS piezas_rol";
	}

	private function sinTablas()
	{
		return array('error' => 'Las promos de sushi todavía no están instaladas en esta base. Cierra sesión y vuelve a entrar para que se actualice.');
	}

	/** Todo lo que necesita la ventana: promos, familias, destinos de impresión y productos (para elegir opciones). */
	public function datos()
	{
		$db = $this->db();
		if (!$this->disponible($db)) return $this->sinTablas();
		$promos = $db->query("SELECT s.id_producto, p.nombre_producto AS nombre, s.piezas, s.paso, s.max_rellenos, " . $this->selModo($db) . ", " . $this->selOpciones($db) . ",
				(p.vigente IS TRUE) AS vigente, COALESCE(p.precio_bruto, 0) AS precio,
				(SELECT count(*) FROM promo_sushi_opcion_web o WHERE o.id_producto_promo = s.id_producto) AS opciones
			FROM promo_sushi_web s JOIN producto p ON p.id_producto = s.id_producto
			ORDER BY s.piezas, p.nombre_producto")->result();
		foreach ($promos as $p) {
			$p->id_producto = (int)$p->id_producto; $p->piezas = (int)$p->piezas; $p->paso = (int)$p->paso;
			$p->max_rellenos = (int)$p->max_rellenos; $p->precio = (int)$p->precio; $p->opciones = (int)$p->opciones;
			$p->modo = $p->modo === 'rol' ? 'rol' : 'pieza'; $p->piezas_rol = max(1, (int)$p->piezas_rol);   // v1.26.0
			$p->vigente = $p->vigente === true || $p->vigente === 't';
			$p->fija = self::si($p->fija_web) && self::si($p->con_armado);   // v1.32.0
			unset($p->fija_web, $p->con_armado, $p->precio_envoltorio_web, $p->max_envoltorios_web, $p->max_extras_web, $p->max_salsas_web);
		}
		$productos = $db->query("SELECT p.id_producto, p.nombre_producto AS nombre, COALESCE(f.nombre_familia, '') AS familia,
				COALESCE(p.id_familia, 0) AS id_familia, (p.vigente IS TRUE) AS vigente
			FROM producto p LEFT JOIN familia f ON f.id_familia = p.id_familia
			WHERE p.id_producto NOT IN (SELECT id_producto FROM promo_sushi_web)
			ORDER BY p.nombre_producto")->result();
		foreach ($productos as $p) { $p->id_producto = (int)$p->id_producto; $p->id_familia = (int)$p->id_familia; $p->vigente = $p->vigente === true || $p->vigente === 't'; }
		return array(
			'promos' => $promos,
			'productos' => $productos,
			'familias' => $db->query('SELECT id_familia, nombre_familia FROM familia ORDER BY nombre_familia')->result(),
			'destinos' => $db->query('SELECT id_dimpresion, nombre_dimpresion FROM dimpresion ORDER BY nombre_dimpresion')->result(),
			'con_rol' => $this->tieneRol($db),   // v1.26.0
			'con_opciones' => $this->conOpciones($db),   // v1.32.0
			'config' => $this->configSushi($db),         // v1.32.0: por pieza, bases y salsas (Configuraciones)
		);
	}

	/** Una promo con su catálogo agrupado por tipo. */
	public function promo($idPromo)
	{
		$db = $this->db();
		if (!$this->disponible($db)) return $this->sinTablas();
		$p = $db->query("SELECT s.id_producto, p.nombre_producto AS nombre, s.piezas, s.paso, s.max_rellenos, " . $this->selModo($db) . ", " . $this->selOpciones($db) . ", (p.vigente IS TRUE) AS vigente,
				COALESCE(p.precio_bruto, 0) AS precio, COALESCE(p.id_familia, 0) AS id_familia, COALESCE(p.id_dimpresion, 0) AS id_dimpresion
			FROM promo_sushi_web s JOIN producto p ON p.id_producto = s.id_producto WHERE s.id_producto = ?", array((int)$idPromo))->row();
		if (!$p) return array('error' => 'La promo ya no existe. Recarga la página.');
		$opciones = array();
		foreach (self::TIPOS as $t) $opciones[$t] = array();
		$conOp = $this->conOpciones($db);
		foreach ($db->query("SELECT o.id_opcion, o.tipo, o.id_producto, o.valor, " . ($conOp ? 'COALESCE(o.precio_promo_web, 0)' : '0') . " AS precio_promo,
				p.nombre_producto AS nombre, (p.vigente IS TRUE) AS vigente,
				COALESCE(p.id_receta, 0) AS id_receta
			FROM promo_sushi_opcion_web o JOIN producto p ON p.id_producto = o.id_producto
			WHERE o.id_producto_promo = ? ORDER BY o.tipo, o.orden, p.nombre_producto", array((int)$idPromo))->result() as $o) {
			if (!isset($opciones[$o->tipo])) continue;
			$opciones[$o->tipo][] = array('id_opcion' => (int)$o->id_opcion, 'id_producto' => (int)$o->id_producto, 'nombre' => $o->nombre,
				'valor' => (int)$o->valor, 'precio_promo' => (int)$o->precio_promo, 'vigente' => $o->vigente === true || $o->vigente === 't', 'con_receta' => (int)$o->id_receta > 0);
		}
		return array('promo' => array('id_producto' => (int)$p->id_producto, 'nombre' => $p->nombre, 'piezas' => (int)$p->piezas,
			'paso' => (int)$p->paso, 'max_rellenos' => (int)$p->max_rellenos, 'vigente' => $p->vigente === true || $p->vigente === 't',
			'precio' => (int)$p->precio, 'id_familia' => (int)$p->id_familia, 'id_dimpresion' => (int)$p->id_dimpresion,
			'modo' => $p->modo === 'rol' ? 'rol' : 'pieza', 'piezas_rol' => max(1, (int)$p->piezas_rol),
			// v1.32.0
			'fija' => self::si($p->fija_web) && self::si($p->con_armado), 'precio_envoltorio' => self::si($p->precio_envoltorio_web),
			'max_envoltorios' => max(1, (int)$p->max_envoltorios_web), 'max_extras' => max(0, (int)$p->max_extras_web), 'max_salsas' => max(0, (int)$p->max_salsas_web)),
			'opciones' => $opciones);
	}

	/**
	 * Crea o edita una promo. Nueva: crea el producto (IVA, ILA, unidad y bodega = los más usados en la Carta; se pueden
	 * cambiar después en la ficha del producto) y le pone el precio en todas las listas de precios. Todo o nada.
	 */
	public function grabarPromo($d)
	{
		$db = $this->db();
		if (!$this->disponible($db)) return $this->sinTablas();
		$id = (int)($d['id_producto'] ?? 0);
		$nombre = mb_strtoupper(mb_substr(trim(preg_replace('/\s+/u', ' ', (string)($d['nombre'] ?? ''))), 0, 100));
		$piezas = (int)($d['piezas'] ?? 0);
		$paso = max(1, (int)($d['paso'] ?? 1));
		$maxRellenos = (int)($d['max_rellenos'] ?? 3);
		$precio = (int)round((float)($d['precio'] ?? 0));
		$idFamilia = (int)($d['id_familia'] ?? 0);
		$idDimpresion = (int)($d['id_dimpresion'] ?? 0);
		$vigente = !isset($d['vigente']) || in_array((string)$d['vigente'], array('1', 'true', 't'), true);
		// v1.32.0: precio según el envoltorio, máximos y quitar "fija" (dejarla fija se hace desde la Caja, armándola)
		$precioEnv = in_array((string)($d['precio_envoltorio'] ?? ''), array('1', 'true', 't'), true);
		$maxEnv = (int)($d['max_envoltorios'] ?? 1);
		$maxExtras = (int)($d['max_extras'] ?? 0);
		$maxSalsas = (int)($d['max_salsas'] ?? 0);
		$quitarFija = isset($d['fija']) && in_array((string)$d['fija'], array('0', 'false', 'f'), true);
		if ($maxEnv < 1 || $maxEnv > 5) return array('error' => 'Los envoltorios por roll deben ser de 1 a 5');
		if ($maxExtras < 0 || $maxExtras > 99 || $maxSalsas < 0 || $maxSalsas > 99) return array('error' => 'El máximo de extras y de salsas debe ser de 0 (sin límite) a 99');
		$conOp = $this->conOpciones($db);
		if (($precioEnv || $maxEnv > 1 || $maxExtras || $maxSalsas) && !$conOp) return array('error' => 'Falta actualizar la base: cierra sesión y vuelve a entrar.');
		if ((string)($d['modo'] ?? 'pieza') !== 'rol' && $id <= 0) {
			$cfgS = $this->configSushi($db);
			if (!$cfgS['por_pieza']) return array('error' => 'Las promos por pieza están apagadas (Configuraciones > Promos de sushi)');
		}
		// v1.26.0: por roll: llegan los rolls y las piezas de cada roll; piezas = rolls x piezas_rol y cada grupo va de a un roll
		$modo = (string)($d['modo'] ?? 'pieza') === 'rol' ? 'rol' : 'pieza';
		$piezasRol = 10;
		if ($modo === 'rol') {
			if (!$this->tieneRol($db)) return array('error' => 'Las promos por roll todavía no están instaladas en esta base. Cierra sesión y vuelve a entrar para que se actualice.');
			$rolls = (int)($d['rolls'] ?? 0);
			$piezasRol = (int)($d['piezas_rol'] ?? 10);
			if ($rolls < 1 || $rolls > 50) return array('error' => 'Los rolls deben ser de 1 a 50');
			if ($piezasRol < 1 || $piezasRol > 100) return array('error' => 'Las piezas por roll deben ser de 1 a 100');
			$piezas = $rolls * $piezasRol;
			$paso = $piezasRol;
		}

		if ($nombre === '') return array('error' => 'Escribe el nombre de la promo');
		if ($piezas < 1 || $piezas > 500) return array('error' => 'Las piezas deben ser de 1 a 500');
		if ($paso > 100) return array('error' => '"De a cuántas piezas" debe ser de 1 a 100');
		if ($piezas % $paso !== 0) return array('error' => 'Las ' . $piezas . ' piezas no se pueden repartir de a ' . $paso . ': usa un número que divida a ' . $piezas);
		if ($maxRellenos < 0 || $maxRellenos > 20) return array('error' => 'El máximo de rellenos debe ser de 0 (sin límite) a 20');
		if ($precio <= 0) return array('error' => 'El precio debe ser mayor a 0');
		if (!$db->query('SELECT 1 FROM familia WHERE id_familia = ?', array($idFamilia))->row()) return array('error' => 'Elige la familia');
		if ($idDimpresion && !$db->query('SELECT 1 FROM dimpresion WHERE id_dimpresion = ?', array($idDimpresion))->row()) return array('error' => 'El destino de impresión no existe');

		$db->trans_begin();
		try {
			if ($id > 0) {
				$antes = $db->query('SELECT ' . $this->selModo($db, 'promo_sushi_web') . ' FROM promo_sushi_web WHERE id_producto = ? FOR UPDATE', array($id))->row();
				if (!$antes) throw new Exception('La promo ya no existe. Recarga la página.');
				// v1.26.0: el modo no se cambia (los valores de sus opciones son por pieza o por roll)
				if ($antes->modo !== $modo) throw new Exception('Esta promo es ' . ($antes->modo === 'rol' ? 'por roll' : 'por pieza') . ': no se puede cambiar. Crea otra promo.');
				$this->ok($db->query('UPDATE producto SET nombre_producto = ?, id_familia = ?, id_dimpresion = ?, vigente = ?, precio_bruto = ?
					WHERE id_producto = ?', array($nombre, $idFamilia, $idDimpresion, $vigente ? 't' : 'f', $precio, $id)));
				$this->ok($db->query('UPDATE promo_sushi_web SET piezas = ?, paso = ?, max_rellenos = ?, vigente = ? WHERE id_producto = ?',
					array($piezas, $paso, $maxRellenos, $vigente ? 't' : 'f', $id)));
				if ($modo === 'rol') $this->ok($db->query('UPDATE promo_sushi_web SET piezas_rol = ? WHERE id_producto = ?', array($piezasRol, $id)));
			} else {
				$id = $this->crearProducto($db, array('nombre' => $nombre, 'id_familia' => $idFamilia, 'id_dimpresion' => $idDimpresion,
					'precio' => $precio, 'venta_publico' => true, 'vigente' => $vigente));
				$this->ok($db->query('INSERT INTO promo_sushi_web (id_producto, piezas, paso, max_rellenos, vigente) VALUES (?, ?, ?, ?, ?)',
					array($id, $piezas, $paso, $maxRellenos, $vigente ? 't' : 'f')));
				if ($modo === 'rol') $this->ok($db->query("UPDATE promo_sushi_web SET modo = 'rol', piezas_rol = ? WHERE id_producto = ?", array($piezasRol, $id)));
			}
			if ($conOp) {
				$this->ok($db->query('UPDATE promo_sushi_web SET precio_envoltorio_web = ?, max_envoltorios_web = ?, max_extras_web = ?, max_salsas_web = ?'
					. ($quitarFija ? ', fija_web = false' : '') . ' WHERE id_producto = ?',
					array($precioEnv ? 't' : 'f', $maxEnv, $maxExtras, $maxSalsas, $id)));
			}
			$this->precioEnTodasLasListas($db, $id, $precio);
			if ($db->trans_status() === false) throw new Exception('No se pudo grabar la promo');
			$db->trans_commit();
		} catch (Exception $e) {
			$db->trans_rollback();
			return array('error' => $e->getMessage());
		}
		return array('ok' => true, 'id_producto' => $id);
	}

	/** Agrega un producto existente a una sección (base, relleno, envoltorio o extra) de la promo. */
	public function agregarOpcion($idPromo, $tipo, $idProducto, $valor)
	{
		$db = $this->db();
		if (!$this->disponible($db)) return $this->sinTablas();
		$v = $this->validarOpcion($db, $idPromo, $tipo, $valor);
		if (isset($v['error'])) return $v;
		$idProducto = (int)$idProducto;
		$prod = $db->query('SELECT nombre_producto FROM producto WHERE id_producto = ?', array($idProducto))->row();
		if (!$prod) return array('error' => 'Elige un producto');
		if ($idProducto === (int)$idPromo) return array('error' => 'La promo no puede ser parte de sí misma');
		if ($db->query('SELECT 1 FROM promo_sushi_web WHERE id_producto = ?', array($idProducto))->row()) return array('error' => 'Otra promo de sushi no puede ser una opción');
		if ($db->query('SELECT 1 FROM promo_sushi_opcion_web WHERE id_producto_promo = ? AND tipo = ? AND id_producto = ?',
			array((int)$idPromo, $tipo, $idProducto))->row()) {
			return array('error' => $prod->nombre_producto . ' ya está en esta sección');
		}
		$r = $db->query('INSERT INTO promo_sushi_opcion_web (id_producto_promo, tipo, id_producto, valor, orden)
			VALUES (?, ?, ?, ?, (SELECT COALESCE(max(orden), 0) + 1 FROM promo_sushi_opcion_web WHERE id_producto_promo = ? AND tipo = ?))
			ON CONFLICT DO NOTHING RETURNING id_opcion', array((int)$idPromo, $tipo, $idProducto, $v['valor'], (int)$idPromo, $tipo))->row();
		if (!$r) return array('error' => $prod->nombre_producto . ' ya está en esta sección');
		return array('ok' => true, 'id_opcion' => (int)$r->id_opcion);
	}

	/**
	 * Crea el producto de una opción (ej. relleno "POLLO") y lo agrega a la sección. El producto queda con la familia y el
	 * destino de impresión de la promo y sin "venta al público" (no aparece suelto en la Caja). Si ya hay un producto con
	 * ese nombre exacto, se usa ese en vez de crear otro igual.
	 */
	public function crearOpcion($idPromo, $tipo, $nombre, $valor)
	{
		$db = $this->db();
		if (!$this->disponible($db)) return $this->sinTablas();
		$v = $this->validarOpcion($db, $idPromo, $tipo, $valor);
		if (isset($v['error'])) return $v;
		$nombre = mb_strtoupper(mb_substr(trim(preg_replace('/\s+/u', ' ', (string)$nombre)), 0, 100));
		if ($nombre === '') return array('error' => 'Escribe el nombre');
		$existe = $db->query('SELECT id_producto FROM producto WHERE upper(trim(nombre_producto)) = ? ORDER BY (vigente IS TRUE) DESC, id_producto LIMIT 1',
			array($nombre))->row();
		$creado = false;
		if ($existe) {
			$idProducto = (int)$existe->id_producto;
		} else {
			$promo = $db->query('SELECT COALESCE(id_familia, 0) AS id_familia, COALESCE(id_dimpresion, 0) AS id_dimpresion FROM producto WHERE id_producto = ?',
				array((int)$idPromo))->row();
			$db->trans_begin();
			try {
				$idProducto = $this->crearProducto($db, array('nombre' => $nombre, 'id_familia' => (int)$promo->id_familia,
					'id_dimpresion' => (int)$promo->id_dimpresion, 'precio' => 0, 'venta_publico' => false, 'vigente' => true));
				if ($db->trans_status() === false) throw new Exception('No se pudo crear el producto');
				$db->trans_commit();
			} catch (Exception $e) {
				$db->trans_rollback();
				return array('error' => $e->getMessage());
			}
			$creado = true;
		}
		$r = $this->agregarOpcion($idPromo, $tipo, $idProducto, $v['valor']);
		if (isset($r['error'])) return $r;
		$r['creado'] = $creado;
		$r['id_producto'] = $idProducto;
		return $r;
	}

	public function valorOpcion($idOpcion, $valor)
	{
		$db = $this->db();
		if (!$this->disponible($db)) return $this->sinTablas();
		$valor = (int)round((float)$valor);
		if ($valor < 0 || $valor > 1000000) return array('error' => 'El valor debe ser de 0 a 1.000.000');
		$db->query('UPDATE promo_sushi_opcion_web SET valor = ? WHERE id_opcion = ?', array($valor, (int)$idOpcion));
		if ($db->affected_rows() < 1) return array('error' => 'La opción ya no existe. Recarga la página.');
		return array('ok' => true);
	}

	/** v1.32.0: precio de la promo con este envoltorio (precio según el envoltorio). */
	public function precioOpcion($idOpcion, $precio)
	{
		$db = $this->db();
		if (!$this->conOpciones($db)) return array('error' => 'Falta actualizar la base: cierra sesión y vuelve a entrar.');
		$precio = (int)round((float)$precio);
		if ($precio < 0 || $precio > 10000000) return array('error' => 'El precio debe ser de 0 a 10.000.000');
		$db->query('UPDATE promo_sushi_opcion_web SET precio_promo_web = ? WHERE id_opcion = ?', array($precio, (int)$idOpcion));
		if ($db->affected_rows() < 1) return array('error' => 'La opción ya no existe. Recarga la página.');
		return array('ok' => true);
	}

	/**
	 * v1.32.0 (pedido del usuario): agrega varios productos de una vez a una sección, elegidos uno a uno o toda una
	 * familia. Los que ya estaban (o no se pueden: la promo misma, otra promo) se saltan. Devuelve cuántos se agregaron.
	 */
	public function agregarVarias($idPromo, $tipo, $ids, $idFamilia, $valor)
	{
		$db = $this->db();
		if (!$this->disponible($db)) return $this->sinTablas();
		$v = $this->validarOpcion($db, $idPromo, $tipo, $valor);
		if (isset($v['error'])) return $v;
		$ids = array_values(array_unique(array_filter(array_map('intval', is_array($ids) ? $ids : array()))));
		if ((int)$idFamilia > 0) {
			foreach ($db->query('SELECT id_producto FROM producto WHERE id_familia = ? AND vigente IS TRUE ORDER BY nombre_producto', array((int)$idFamilia))->result() as $r) {
				$ids[] = (int)$r->id_producto;
			}
			$ids = array_values(array_unique($ids));
		}
		if (!$ids) return array('error' => (int)$idFamilia > 0 ? 'Esa familia no tiene productos vigentes' : 'Elige al menos un producto');
		if (count($ids) > 300) return array('error' => 'Son demasiados productos de una vez (máximo 300)');
		$en = 'ARRAY[' . implode(',', $ids) . ']::int[]';   // solo enteros (intval arriba)
		$db->query("INSERT INTO promo_sushi_opcion_web (id_producto_promo, tipo, id_producto, valor, orden)
			SELECT ?, ?, p.id_producto, ?, (SELECT COALESCE(max(orden), 0) FROM promo_sushi_opcion_web WHERE id_producto_promo = ? AND tipo = ?)
				+ row_number() OVER (ORDER BY p.nombre_producto)
			FROM producto p
			WHERE p.id_producto = ANY($en) AND p.id_producto <> ?
				AND NOT EXISTS (SELECT 1 FROM promo_sushi_web s WHERE s.id_producto = p.id_producto)
				AND NOT EXISTS (SELECT 1 FROM promo_sushi_opcion_web o WHERE o.id_producto_promo = ? AND o.tipo = ? AND o.id_producto = p.id_producto)
			ON CONFLICT DO NOTHING",
			array((int)$idPromo, $tipo, $v['valor'], (int)$idPromo, $tipo, (int)$idPromo, (int)$idPromo, $tipo));
		$n = (int)$db->affected_rows();
		return array('ok' => true, 'agregados' => $n, 'saltados' => count($ids) - $n);
	}

	/** Saca la opción de la promo (el producto no se borra; las ventas ya hechas conservan lo que se pidió). */
	public function quitarOpcion($idOpcion)
	{
		$db = $this->db();
		if (!$this->disponible($db)) return $this->sinTablas();
		$db->query('DELETE FROM promo_sushi_opcion_web WHERE id_opcion = ?', array((int)$idOpcion));
		return array('ok' => true);
	}

	/** Copia las opciones de otra promo (las que ya tiene se dejan como están). Nunca borra. */
	public function copiarCatalogo($idOrigen, $idDestino)
	{
		$db = $this->db();
		if (!$this->disponible($db)) return $this->sinTablas();
		if ((int)$idOrigen === (int)$idDestino) return array('error' => 'Elige otra promo');
		if (!$db->query('SELECT 1 FROM promo_sushi_web WHERE id_producto = ?', array((int)$idDestino))->row()) return array('error' => 'La promo ya no existe');
		// v1.26.0: entre una promo por pieza y una por roll los valores no significan lo mismo (por pieza / por roll)
		if ($this->tieneRol($db)) {
			$m = $db->query('SELECT count(DISTINCT modo) AS n FROM promo_sushi_web WHERE id_producto IN (?, ?)', array((int)$idOrigen, (int)$idDestino))->row();
			if ($m && (int)$m->n > 1) return array('error' => 'Solo se copian opciones entre promos del mismo tipo (por pieza o por roll): los valores son por pieza en unas y por roll en otras');
		}
		$pp = $this->conOpciones($db) ? ', precio_promo_web' : '';   // v1.32.0: también el precio con ese envoltorio
		$db->query('INSERT INTO promo_sushi_opcion_web (id_producto_promo, tipo, id_producto, valor, orden' . $pp . ')
			SELECT ?, o.tipo, o.id_producto, o.valor, o.orden' . ($pp ? ', o.precio_promo_web' : '') . ' FROM promo_sushi_opcion_web o
			WHERE o.id_producto_promo = ? AND o.id_producto <> ?
			ON CONFLICT DO NOTHING', array((int)$idDestino, (int)$idOrigen, (int)$idDestino));
		return array('ok' => true, 'nuevas' => (int)$db->affected_rows());
	}

	// ------------------------------------------------------------------

	private function validarOpcion($db, $idPromo, $tipo, $valor)
	{
		if (!in_array($tipo, self::TIPOS, true)) return array('error' => 'Sección inválida');
		if (!$db->query('SELECT 1 FROM promo_sushi_web WHERE id_producto = ?', array((int)$idPromo))->row()) return array('error' => 'Graba la promo primero');
		$valor = (int)round((float)$valor);
		if ($valor < 0 || $valor > 1000000) return array('error' => 'El valor debe ser de 0 a 1.000.000');
		return array('valor' => $valor);
	}

	/** Inserta un producto con los datos más usados de la Carta (IVA, ILA, unidad, bodega). Devuelve su id. */
	private function crearProducto($db, $d)
	{
		$base = $db->query('SELECT impto_iva, impto_ila, COALESCE(valor_ila, 0) AS valor_ila, id_umedida, id_bodega FROM producto
			WHERE vigente IS TRUE AND impto_iva IS NOT NULL AND id_umedida IS NOT NULL
			GROUP BY 1, 2, 3, 4, 5 ORDER BY count(*) DESC LIMIT 1')->row();
		if (!$base) throw new Exception('Crea al menos un producto normal en la Carta antes (se copian de él el IVA, la unidad y la bodega)');
		$this->load->model('Mproductos');
		$cbarra = $this->Mproductos->obtenerSiguienteCodigoBarra();
		$r = $db->query('INSERT INTO producto (nombre_producto, cbarra, impto_iva, impto_ila, valor_ila, id_familia, id_umedida, precio_bruto,
				id_dimpresion, id_bodega, vigente, venta_publico, inventariable, permitir_venta_sin_precio, elaboracion_propia, con_agregado,
				es_menu, id_menu, id_subfamilia, id_receta)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, false, ?, true, false, false, 0, 0, 0) RETURNING id_producto',
			array($d['nombre'], $cbarra, (int)$base->impto_iva, (int)$base->impto_ila, (float)$base->valor_ila, (int)$d['id_familia'],
				(int)$base->id_umedida, (int)$d['precio'], (int)$d['id_dimpresion'], (int)$base->id_bodega, $d['vigente'] ? 't' : 'f',
				$d['venta_publico'] ? 't' : 'f', $d['precio'] > 0 ? 'f' : 't'))->row();
		if (!$r) throw new Exception('No se pudo crear el producto ' . $d['nombre']);
		return (int)$r->id_producto;
	}

	/** El mismo precio en todas las listas, con neto, IVA e ILA (igual que Mproductos::grabarPrecioProducto). */
	private function precioEnTodasLasListas($db, $idProducto, $total)
	{
		$this->ok($db->query("UPDATE detalle_lprecio d SET total = x.total, neto = x.neto, iva = round(x.neto * x.piva / 100),
				ila = x.total - x.neto - round(x.neto * x.piva / 100)
			FROM (SELECT ?::int AS total, COALESCE(iv.valor, 0) AS piva,
					round(?::int / (1 + (COALESCE(iv.valor, 0) + COALESCE(il.valor, 0)) / 100.0)) AS neto
				FROM producto p LEFT JOIN impuesto iv ON iv.id_impuesto = p.impto_iva LEFT JOIN impuesto il ON il.id_impuesto = p.impto_ila
				WHERE p.id_producto = ?) x
			WHERE d.id_producto = ?", array($total, $total, (int)$idProducto, (int)$idProducto)));
		$this->ok($db->query("INSERT INTO detalle_lprecio (id_producto, id_lprecio, total, neto, iva, ila)
			SELECT ?, l.id_lprecio, x.total, x.neto, round(x.neto * x.piva / 100), x.total - x.neto - round(x.neto * x.piva / 100)
			FROM lprecio l, (SELECT ?::int AS total, COALESCE(iv.valor, 0) AS piva,
					round(?::int / (1 + (COALESCE(iv.valor, 0) + COALESCE(il.valor, 0)) / 100.0)) AS neto
				FROM producto p LEFT JOIN impuesto iv ON iv.id_impuesto = p.impto_iva LEFT JOIN impuesto il ON il.id_impuesto = p.impto_ila
				WHERE p.id_producto = ?) x
			WHERE NOT EXISTS (SELECT 1 FROM detalle_lprecio d WHERE d.id_producto = ? AND d.id_lprecio = l.id_lprecio)",
			array((int)$idProducto, $total, $total, (int)$idProducto, (int)$idProducto)));
	}

	private function ok($r)
	{
		if ($r === false) throw new Exception('No se pudo grabar la promo');
		return $r;
	}
}
