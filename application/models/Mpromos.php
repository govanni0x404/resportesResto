<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * v1.36.0 (pedido del usuario): promos normales (Carta > Promos), ej. "Completo + Bebida a $3.990".
 * v1.39.5: fijas o "el cliente elige N" (promo_web.elegir).
 * v1.41.0 (pedido del usuario: "que tengan su tabla y no esté sobre el menú"): una promo es un producto de la Carta (con
 * su precio, en todas las listas) + promo_web (cuál es promo y si el cliente elige) + promo_producto_web (sus productos:
 * fija = cada uno con su cantidad; "elige N" = la lista, cantidad 1). Ya no usa menu / categoria_menu / detalle_categoria_menu
 * (migración 20261126120000, que pasó las promos que había). En la Caja, Mcaja::promosNormales la arma con la misma forma
 * que un menú y la venta queda como antes: la línea de la promo con su precio y una línea por producto en $0 (stock e
 * impresora de cada producto), unidas en detalle_promo_web.
 */
class Mpromos extends CI_Model
{
	const MAX_ITEMS = 30;

	private function db()
	{
		$this->load->helper('tenant');
		return get_tenant_db($this);
	}

	private function ok($r)
	{
		if ($r === false) throw new Exception('Error de base de datos');
		return $r;
	}

	private function hayTabla($db, $t)
	{
		return (bool)$db->query("SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = ?", array($t))->row();
	}

	/** Las promos funcionan con promo_web y promo_producto_web (migraciones 20261124160000 y 20261126120000). */
	public function disponible($db = null)
	{
		$db = $db ?: $this->db();
		return $this->hayTabla($db, 'promo_web') && $this->hayTabla($db, 'promo_producto_web');
	}

	/** v1.39.5: promo_web.elegir (migración 20261126100000): N > 0 = el cliente elige N productos de la lista. */
	private function conElegir($db)
	{
		return (bool)$db->query("SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'promo_web' AND column_name = 'elegir'")->row();
	}

	/** Promos, productos que se pueden poner en una promo, familias y subfamilias. */
	public function datos()
	{
		$db = $this->db();
		if (!$this->disponible($db)) return array('disponible' => false, 'promos' => array(), 'productos' => array(), 'familias' => array());
		$promos = array();
		$elegir = $this->conElegir($db) ? 'COALESCE(w.elegir, 0)' : '0';
		foreach ($db->query("SELECT w.id_producto, p.nombre_producto AS nombre, COALESCE(p.precio_bruto, 0) AS precio, (p.vigente IS TRUE) AS vigente,
				$elegir AS elegir,
				CASE WHEN $elegir > 0 THEN 'Elige ' || $elegir || ': ' || COALESCE(string_agg(pd.nombre_producto, ' / ' ORDER BY pd.nombre_producto), '')
				ELSE COALESCE(string_agg(CASE WHEN pp.cantidad > 1 THEN pp.cantidad || ' ' ELSE '' END || pd.nombre_producto, ' + ' ORDER BY pp.orden, pp.id_promo_producto_web), '') END AS detalle
			FROM promo_web w JOIN producto p ON p.id_producto = w.id_producto
			LEFT JOIN promo_producto_web pp ON pp.id_producto_promo = w.id_producto AND pp.vigente IS TRUE
			LEFT JOIN producto pd ON pd.id_producto = pp.id_producto
			GROUP BY w.id_producto, p.nombre_producto, p.precio_bruto, p.vigente" . ($elegir === '0' ? '' : ', w.elegir') . "
			ORDER BY p.nombre_producto")->result() as $r) {
			$promos[] = array('id_producto' => (int)$r->id_producto, 'nombre' => (string)$r->nombre, 'precio' => (int)$r->precio,
				'vigente' => $r->vigente === 't' || $r->vigente === true, 'detalle' => (string)$r->detalle, 'elegir' => (int)$r->elegir);
		}
		$sushi = $this->hayTabla($db, 'promo_sushi_web');
		$productos = array();
		// El precio de la lista pública (Configuraciones), o el del producto. Ni menús ni promos dentro de una promo.
		foreach ($db->query("SELECT p.id_producto, p.nombre_producto AS nombre, COALESCE(f.nombre_familia, '') AS familia,
				COALESCE(NULLIF((SELECT dl.total FROM detalle_lprecio dl WHERE dl.id_producto = p.id_producto
					AND dl.id_lprecio = (SELECT id_lpreciopublico FROM configuracion LIMIT 1) LIMIT 1), 0), p.precio_bruto, 0) AS precio
			FROM producto p LEFT JOIN familia f ON f.id_familia = p.id_familia
			WHERE p.vigente IS TRUE AND COALESCE(p.id_menu, 0) = 0
			  AND p.id_producto NOT IN (SELECT id_producto FROM promo_web)"
			. ($sushi ? " AND p.id_producto NOT IN (SELECT id_producto FROM promo_sushi_web)" : '') . "
			ORDER BY p.nombre_producto")->result() as $r) {
			$productos[] = array('id_producto' => (int)$r->id_producto, 'nombre' => (string)$r->nombre, 'familia' => (string)$r->familia, 'precio' => (int)$r->precio);
		}
		return array('disponible' => true, 'promos' => $promos, 'productos' => $productos,
			'familias' => $db->query('SELECT id_familia, nombre_familia FROM familia ORDER BY nombre_familia')->result(),
			// v1.39.7 (pedido del usuario): la promo también puede tener subfamilia (las de su familia)
			'subfamilias' => $db->query('SELECT id_subfamilia, nombre_subfamilia, COALESCE(id_familia, 0) AS id_familia FROM subfamilia ORDER BY nombre_subfamilia')->result());
	}

	/** Una promo con sus productos. */
	public function promo($id)
	{
		$db = $this->db();
		if (!$this->disponible($db)) return array('error' => 'Falta actualizar la base: cierra sesión y vuelve a entrar.');
		$p = $db->query('SELECT w.id_producto, ' . ($this->conElegir($db) ? 'COALESCE(w.elegir, 0)' : '0') . ' AS elegir,
				p.nombre_producto AS nombre, COALESCE(p.precio_bruto, 0) AS precio,
				COALESCE(p.id_familia, 0) AS id_familia, COALESCE(p.id_subfamilia, 0) AS id_subfamilia, (p.vigente IS TRUE) AS vigente
			FROM promo_web w JOIN producto p ON p.id_producto = w.id_producto WHERE w.id_producto = ?', array((int)$id))->row();
		if (!$p) return array('error' => 'La promo ya no existe. Recarga la página.');
		$items = array();
		foreach ($db->query('SELECT pp.id_producto, pd.nombre_producto AS nombre, GREATEST(pp.cantidad, 1) AS cantidad
			FROM promo_producto_web pp JOIN producto pd ON pd.id_producto = pp.id_producto
			WHERE pp.id_producto_promo = ? AND pp.vigente IS TRUE ORDER BY pp.orden, pp.id_promo_producto_web', array((int)$p->id_producto))->result() as $r) {
			$items[] = array('id_producto' => (int)$r->id_producto, 'nombre' => (string)$r->nombre, 'cantidad' => (int)$p->elegir > 0 ? 1 : (int)$r->cantidad);
		}
		return array('ok' => true, 'promo' => array('id_producto' => (int)$p->id_producto, 'nombre' => (string)$p->nombre, 'precio' => (int)$p->precio,
			'id_familia' => (int)$p->id_familia, 'id_subfamilia' => (int)$p->id_subfamilia, 'vigente' => $p->vigente === 't' || $p->vigente === true,
			'items' => $items, 'elegir' => (int)$p->elegir));
	}

	/** Crea o edita una promo: {id_producto, nombre, precio, id_familia, id_subfamilia, vigente, elegir, items: [{id_producto, cantidad}]}. */
	public function grabar($d)
	{
		$db = $this->db();
		if (!$this->disponible($db)) return array('error' => 'Falta actualizar la base: cierra sesión y vuelve a entrar.');
		$id = (int)($d['id_producto'] ?? 0);
		$nombre = mb_strtoupper(mb_substr(trim(preg_replace('/\s+/u', ' ', (string)($d['nombre'] ?? ''))), 0, 50));
		$precio = (int)round((float)($d['precio'] ?? 0));
		$idFamilia = (int)($d['id_familia'] ?? 0);
		$vigente = !isset($d['vigente']) || in_array((string)$d['vigente'], array('1', 'true', 't'), true);
		// v1.39.5: 0 = promo fija; N = el cliente elige N productos de la lista (se puede repetir un producto)
		$elegir = (int)($d['elegir'] ?? 0);
		if ($elegir < 0 || $elegir > 50) return array('error' => 'La cantidad a elegir debe ser de 1 a 50');
		if ($elegir > 0 && !$this->conElegir($db)) return array('error' => 'Falta actualizar la base para las promos a elegir: cierra sesión y vuelve a entrar.');
		if ($nombre === '') return array('error' => 'Escribe el nombre de la promo');
		if ($precio <= 0) return array('error' => 'El precio debe ser mayor a 0');
		if (!$db->query('SELECT 1 FROM familia WHERE id_familia = ?', array($idFamilia))->row()) return array('error' => 'Elige la familia');
		$idSub = (int)($d['id_subfamilia'] ?? 0);   // v1.39.7: opcional (0 = sin subfamilia)
		if ($idSub > 0) {
			$sf = $db->query('SELECT COALESCE(id_familia, 0) AS id_familia FROM subfamilia WHERE id_subfamilia = ?', array($idSub))->row();
			if (!$sf) return array('error' => 'La subfamilia ya no existe. Recarga la página.');
			if ((int)$sf->id_familia > 0 && (int)$sf->id_familia !== $idFamilia) return array('error' => 'Esa subfamilia es de otra familia');
		}
		// Los productos de la promo (el mismo producto dos veces se junta; en "elige" cada uno va una vez)
		$items = array();
		foreach (is_array($d['items'] ?? null) ? $d['items'] : array() as $it) {
			$ip = (int)($it['id_producto'] ?? 0);
			$q = (int)($it['cantidad'] ?? 0);
			if ($ip <= 0) continue;
			if ($elegir > 0) $q = 1;
			if ($q < 1 || $q > 50) return array('error' => 'La cantidad de cada producto debe ser de 1 a 50');
			$items[$ip] = $elegir > 0 ? 1 : (isset($items[$ip]) ? $items[$ip] : 0) + $q;
		}
		if (!$items) return array('error' => 'Agrega al menos un producto a la promo');
		if (count($items) > self::MAX_ITEMS) return array('error' => 'Una promo puede tener hasta ' . self::MAX_ITEMS . ' productos distintos');
		if (isset($items[$id])) return array('error' => 'La promo no puede llevarse a sí misma');
		$nombres = array();
		foreach ($db->query('SELECT id_producto, nombre_producto, COALESCE(id_menu, 0) AS id_menu,
				(id_producto IN (SELECT id_producto FROM promo_web)) AS es_promo FROM producto
			WHERE vigente IS TRUE AND id_producto IN (' . implode(',', array_map('intval', array_keys($items))) . ')')->result() as $r) {
			if ((int)$r->id_menu > 0 || $r->es_promo === 't' || $r->es_promo === true) return array('error' => $r->nombre_producto . ' es un menú o una promo: no puede ir dentro de otra promo');
			$nombres[(int)$r->id_producto] = (string)$r->nombre_producto;
		}
		if (count($nombres) !== count($items)) return array('error' => 'Algún producto de la promo ya no existe o no está vigente. Recarga la página.');

		$db->trans_begin();
		try {
			if ($id > 0) {
				if (!$db->query('SELECT 1 FROM promo_web WHERE id_producto = ? FOR UPDATE', array($id))->row()) throw new Exception('La promo ya no existe. Recarga la página.');
				$this->ok($db->query('UPDATE producto SET nombre_producto = ?, id_familia = ?, id_subfamilia = ?, vigente = ?, precio_bruto = ?, es_menu = false, id_menu = 0
					WHERE id_producto = ?', array($nombre, $idFamilia, $idSub, $vigente ? 't' : 'f', $precio, $id)));
			} else {
				$id = $this->crearProducto($db, $nombre, $idFamilia, $precio, $vigente);
				if ($idSub > 0) $this->ok($db->query('UPDATE producto SET id_subfamilia = ? WHERE id_producto = ?', array($idSub, $id)));
				$this->ok($db->query('INSERT INTO promo_web (id_producto, id_menu) VALUES (?, 0)', array($id)));
			}
			if ($this->conElegir($db)) $this->ok($db->query('UPDATE promo_web SET elegir = ? WHERE id_producto = ?', array($elegir > 0 ? $elegir : null, $id)));
			// Sus productos: se reusa la fila que ya tenía cada producto; las que sobran quedan no vigentes (no se borran)
			$antes = array();
			foreach ($db->query('SELECT id_promo_producto_web, id_producto FROM promo_producto_web WHERE id_producto_promo = ? ORDER BY id_promo_producto_web', array($id))->result() as $r) {
				if (!isset($antes[(int)$r->id_producto])) $antes[(int)$r->id_producto] = (int)$r->id_promo_producto_web;
				else $this->ok($db->query('UPDATE promo_producto_web SET vigente = false WHERE id_promo_producto_web = ?', array((int)$r->id_promo_producto_web)));
			}
			$orden = 0;
			foreach ($items as $ip => $q) {
				$orden++;
				if (isset($antes[$ip])) {
					$this->ok($db->query('UPDATE promo_producto_web SET cantidad = ?, orden = ?, vigente = true WHERE id_promo_producto_web = ?', array($q, $orden, $antes[$ip])));
					unset($antes[$ip]);
				} else {
					$this->ok($db->query('INSERT INTO promo_producto_web (id_producto_promo, id_producto, cantidad, orden, vigente) VALUES (?, ?, ?, ?, true)', array($id, $ip, $q, $orden)));
				}
			}
			foreach ($antes as $idFila) $this->ok($db->query('UPDATE promo_producto_web SET vigente = false WHERE id_promo_producto_web = ?', array($idFila)));
			$this->precioEnTodasLasListas($db, $id, $precio);
			if ($db->trans_status() === false) throw new Exception('No se pudo grabar la promo');
			$db->trans_commit();
		} catch (Exception $e) {
			$db->trans_rollback();
			return array('error' => $e->getMessage());
		}
		return array('ok' => true, 'id_producto' => $id);
	}

	/** Producto nuevo para la promo (IVA, unidad y bodega del producto más común, como las promos de sushi). */
	private function crearProducto($db, $nombre, $idFamilia, $precio, $vigente)
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
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, true, false, false, true, false, false, 0, 0, 0) RETURNING id_producto',
			array($nombre, $cbarra, (int)$base->impto_iva, (int)$base->impto_ila, (float)$base->valor_ila, $idFamilia,
				(int)$base->id_umedida, $precio, (int)$base->id_bodega, $vigente ? 't' : 'f'))->row();
		if (!$r) throw new Exception('No se pudo crear el producto ' . $nombre);
		return (int)$r->id_producto;
	}

	/** El mismo precio en todas las listas, con neto, IVA e ILA (igual que Mpromosushi). */
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
}
