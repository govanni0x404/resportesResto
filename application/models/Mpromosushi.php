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
	const TIPOS = array('base', 'relleno', 'envoltorio', 'extra');

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

	private function sinTablas()
	{
		return array('error' => 'Las promos de sushi todavía no están instaladas en esta base. Cierra sesión y vuelve a entrar para que se actualice.');
	}

	/** Todo lo que necesita la ventana: promos, familias, destinos de impresión y productos (para elegir opciones). */
	public function datos()
	{
		$db = $this->db();
		if (!$this->disponible($db)) return $this->sinTablas();
		$promos = $db->query("SELECT s.id_producto, p.nombre_producto AS nombre, s.piezas, s.paso, s.max_rellenos,
				(p.vigente IS TRUE) AS vigente, COALESCE(p.precio_bruto, 0) AS precio,
				(SELECT count(*) FROM promo_sushi_opcion_web o WHERE o.id_producto_promo = s.id_producto) AS opciones
			FROM promo_sushi_web s JOIN producto p ON p.id_producto = s.id_producto
			ORDER BY s.piezas, p.nombre_producto")->result();
		foreach ($promos as $p) {
			$p->id_producto = (int)$p->id_producto; $p->piezas = (int)$p->piezas; $p->paso = (int)$p->paso;
			$p->max_rellenos = (int)$p->max_rellenos; $p->precio = (int)$p->precio; $p->opciones = (int)$p->opciones;
			$p->vigente = $p->vigente === true || $p->vigente === 't';
		}
		$productos = $db->query("SELECT p.id_producto, p.nombre_producto AS nombre, COALESCE(f.nombre_familia, '') AS familia,
				(p.vigente IS TRUE) AS vigente
			FROM producto p LEFT JOIN familia f ON f.id_familia = p.id_familia
			WHERE p.id_producto NOT IN (SELECT id_producto FROM promo_sushi_web)
			ORDER BY p.nombre_producto")->result();
		foreach ($productos as $p) { $p->id_producto = (int)$p->id_producto; $p->vigente = $p->vigente === true || $p->vigente === 't'; }
		return array(
			'promos' => $promos,
			'productos' => $productos,
			'familias' => $db->query('SELECT id_familia, nombre_familia FROM familia ORDER BY nombre_familia')->result(),
			'destinos' => $db->query('SELECT id_dimpresion, nombre_dimpresion FROM dimpresion ORDER BY nombre_dimpresion')->result(),
		);
	}

	/** Una promo con su catálogo agrupado por tipo. */
	public function promo($idPromo)
	{
		$db = $this->db();
		if (!$this->disponible($db)) return $this->sinTablas();
		$p = $db->query("SELECT s.id_producto, p.nombre_producto AS nombre, s.piezas, s.paso, s.max_rellenos, (p.vigente IS TRUE) AS vigente,
				COALESCE(p.precio_bruto, 0) AS precio, COALESCE(p.id_familia, 0) AS id_familia, COALESCE(p.id_dimpresion, 0) AS id_dimpresion
			FROM promo_sushi_web s JOIN producto p ON p.id_producto = s.id_producto WHERE s.id_producto = ?", array((int)$idPromo))->row();
		if (!$p) return array('error' => 'La promo ya no existe. Recarga la página.');
		$opciones = array();
		foreach (self::TIPOS as $t) $opciones[$t] = array();
		foreach ($db->query("SELECT o.id_opcion, o.tipo, o.id_producto, o.valor, p.nombre_producto AS nombre, (p.vigente IS TRUE) AS vigente,
				COALESCE(p.id_receta, 0) AS id_receta
			FROM promo_sushi_opcion_web o JOIN producto p ON p.id_producto = o.id_producto
			WHERE o.id_producto_promo = ? ORDER BY o.tipo, o.orden, p.nombre_producto", array((int)$idPromo))->result() as $o) {
			$opciones[$o->tipo][] = array('id_opcion' => (int)$o->id_opcion, 'id_producto' => (int)$o->id_producto, 'nombre' => $o->nombre,
				'valor' => (int)$o->valor, 'vigente' => $o->vigente === true || $o->vigente === 't', 'con_receta' => (int)$o->id_receta > 0);
		}
		return array('promo' => array('id_producto' => (int)$p->id_producto, 'nombre' => $p->nombre, 'piezas' => (int)$p->piezas,
			'paso' => (int)$p->paso, 'max_rellenos' => (int)$p->max_rellenos, 'vigente' => $p->vigente === true || $p->vigente === 't',
			'precio' => (int)$p->precio, 'id_familia' => (int)$p->id_familia, 'id_dimpresion' => (int)$p->id_dimpresion), 'opciones' => $opciones);
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
				if (!$db->query('SELECT 1 FROM promo_sushi_web WHERE id_producto = ? FOR UPDATE', array($id))->row()) throw new Exception('La promo ya no existe. Recarga la página.');
				$this->ok($db->query('UPDATE producto SET nombre_producto = ?, id_familia = ?, id_dimpresion = ?, vigente = ?, precio_bruto = ?
					WHERE id_producto = ?', array($nombre, $idFamilia, $idDimpresion, $vigente ? 't' : 'f', $precio, $id)));
				$this->ok($db->query('UPDATE promo_sushi_web SET piezas = ?, paso = ?, max_rellenos = ?, vigente = ? WHERE id_producto = ?',
					array($piezas, $paso, $maxRellenos, $vigente ? 't' : 'f', $id)));
			} else {
				$id = $this->crearProducto($db, array('nombre' => $nombre, 'id_familia' => $idFamilia, 'id_dimpresion' => $idDimpresion,
					'precio' => $precio, 'venta_publico' => true, 'vigente' => $vigente));
				$this->ok($db->query('INSERT INTO promo_sushi_web (id_producto, piezas, paso, max_rellenos, vigente) VALUES (?, ?, ?, ?, ?)',
					array($id, $piezas, $paso, $maxRellenos, $vigente ? 't' : 'f')));
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
		$db->query('INSERT INTO promo_sushi_opcion_web (id_producto_promo, tipo, id_producto, valor, orden)
			SELECT ?, o.tipo, o.id_producto, o.valor, o.orden FROM promo_sushi_opcion_web o
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
