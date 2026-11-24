<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * v1.35.0 (pedido del usuario en cambios.md): Tablas > Categorías de agregados (Proteína, Verdura, Salsas...).
 * Cada agregado de un producto (tabla agregados) puede ir en una categoría (agregados.id_categoria_agregado_web). En la Caja,
 * con "Categorías de agregados" activado en Configuraciones, los agregados salen en un cuadro por categoría y cada producto
 * puede tener un límite por categoría (producto_categoria_agregado_web, se pone en Carta > Agregados del producto).
 * Migración 20261124140000. Sin la migración, disponible() es false y la pantalla lo avisa.
 */
class McategoriasAgregados extends CI_Model
{
	private $db2 = null;

	private function db()
	{
		if ($this->db2 === null) {
			$this->load->helper('tenant');
			$this->db2 = get_tenant_db($this);
		}
		return $this->db2;
	}

	private function q($sql, $binds = array())
	{
		$r = $this->db()->query($sql, $binds);
		if ($r === false) {
			$e = $this->db()->error();
			throw new Exception('Error de base de datos: ' . (isset($e['message']) ? $e['message'] : 'sin detalle'), 500);
		}
		return $r;
	}

	public function disponible()
	{
		return (bool)$this->q("SELECT 1 FROM information_schema.columns WHERE table_schema = 'public'
			AND table_name = 'agregados' AND column_name = 'id_categoria_agregado_web'")->row()
			&& (bool)$this->q("SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'categoria_agregado_web'")->row();
	}

	private function exigir()
	{
		if (!$this->disponible()) throw new Exception('Falta actualizar la base: cierra sesión y vuelve a entrar.', 409);
	}

	/** Categorías con cuántos agregados tiene cada una, y si está activada la opción en Configuraciones. */
	public function listar()
	{
		if (!$this->disponible()) return array('disponible' => false, 'activada' => false, 'categorias' => array());
		$cats = array();
		foreach ($this->q("SELECT c.id_categoria_agregado AS id, c.nombre, c.orden, (c.vigente IS TRUE) AS vigente,
				(SELECT count(*) FROM agregados a WHERE a.id_categoria_agregado_web = c.id_categoria_agregado AND a.eliminado IS NOT TRUE) AS agregados,
				(SELECT count(DISTINCT a.id_producto_maestro) FROM agregados a WHERE a.id_categoria_agregado_web = c.id_categoria_agregado AND a.eliminado IS NOT TRUE) AS productos
			FROM categoria_agregado_web c WHERE c.eliminado IS NOT TRUE ORDER BY c.orden, c.nombre")->result() as $c) {
			$cats[] = array('id' => (int)$c->id, 'nombre' => (string)$c->nombre, 'orden' => (int)$c->orden,
				'vigente' => $c->vigente === 't' || $c->vigente === true, 'agregados' => (int)$c->agregados, 'productos' => (int)$c->productos);
		}
		$on = $this->q('SELECT (agregados_categorias_web IS TRUE) AS on FROM configuracion LIMIT 1')->row();
		$sinCat = $this->q('SELECT count(*) AS n FROM agregados WHERE COALESCE(id_categoria_agregado_web, 0) = 0 AND eliminado IS NOT TRUE')->row();
		return array('disponible' => true, 'activada' => $on && ($on->on === 't' || $on->on === true), 'categorias' => $cats,
			'sin_categoria' => $sinCat ? (int)$sinCat->n : 0);
	}

	/** Crea o edita una categoría: {id, nombre, orden, vigente}. Devuelve el id. */
	public function guardar($d)
	{
		$this->exigir();
		$id = (int)(isset($d['id']) ? $d['id'] : 0);
		$nombre = mb_strtoupper(trim(preg_replace('/\s+/u', ' ', (string)(isset($d['nombre']) ? $d['nombre'] : ''))));
		if ($nombre === '') throw new Exception('Escribe el nombre de la categoría', 400);
		if (mb_strlen($nombre) > 60) throw new Exception('El nombre puede tener hasta 60 letras', 400);
		$orden = max(0, min(9999, (int)(isset($d['orden']) ? $d['orden'] : 0)));
		$vigente = !isset($d['vigente']) || !in_array((string)$d['vigente'], array('0', 'false', 'f', ''), true);
		if ($this->q('SELECT 1 FROM categoria_agregado_web WHERE upper(nombre) = ? AND id_categoria_agregado <> ? AND eliminado IS NOT TRUE',
			array($nombre, $id))->row()) {
			throw new Exception('Ya hay una categoría llamada ' . $nombre, 409);
		}
		if ($id > 0) {
			$this->q('UPDATE categoria_agregado_web SET nombre = ?, orden = ?, vigente = ? WHERE id_categoria_agregado = ? AND eliminado IS NOT TRUE',
				array($nombre, $orden, $vigente ? 't' : 'f', $id));
			if ($this->db()->affected_rows() < 1) throw new Exception('La categoría ya no existe. Recarga la página.', 404);
			return $id;
		}
		if ($orden === 0) {
			$m = $this->q('SELECT COALESCE(max(orden), 0) AS m FROM categoria_agregado_web WHERE eliminado IS NOT TRUE')->row();
			$orden = ($m ? (int)$m->m : 0) + 1;
		}
		$r = $this->q('INSERT INTO categoria_agregado_web (nombre, orden, vigente) VALUES (?, ?, ?) RETURNING id_categoria_agregado AS id',
			array($nombre, $orden, $vigente ? 't' : 'f'))->row();
		return (int)$r->id;
	}

	/** Borra la categoría (queda marcada eliminada) y sus agregados quedan sin categoría. */
	public function eliminar($id)
	{
		$this->exigir();
		$id = (int)$id;
		$db = $this->db();
		$db->trans_begin();
		$this->q('UPDATE categoria_agregado_web SET eliminado = true, vigente = false WHERE id_categoria_agregado = ? AND eliminado IS NOT TRUE', array($id));
		if ($db->affected_rows() < 1) { $db->trans_rollback(); throw new Exception('La categoría ya no existe. Recarga la página.', 404); }
		$this->q('UPDATE agregados SET id_categoria_agregado_web = 0 WHERE id_categoria_agregado_web = ?', array($id));
		$sueltos = $db->affected_rows();
		$this->q('DELETE FROM producto_categoria_agregado_web WHERE id_categoria_agregado = ?', array($id));
		if ($db->trans_status() === false) { $db->trans_rollback(); throw new Exception('No se pudo eliminar la categoría', 500); }
		$db->trans_commit();
		return $sueltos;
	}

	/** Todos los agregados de todos los productos, con su categoría (para la ventana "Agregados de la categoría"). */
	public function agregados()
	{
		$this->exigir();
		$res = array();
		foreach ($this->q("SELECT a.id_agregado, a.id_producto, a.id_producto_maestro, p.nombre_producto AS agregado, pm.nombre_producto AS producto,
				COALESCE(a.id_categoria_agregado_web, 0) AS id_categoria, (a.vigente IS NOT FALSE) AS vigente
			FROM agregados a JOIN producto p ON p.id_producto = a.id_producto JOIN producto pm ON pm.id_producto = a.id_producto_maestro
			WHERE a.eliminado IS NOT TRUE ORDER BY p.nombre_producto, pm.nombre_producto")->result() as $a) {
			$res[] = array('id' => (int)$a->id_agregado, 'id_producto' => (int)$a->id_producto, 'id_maestro' => (int)$a->id_producto_maestro,
				'agregado' => (string)$a->agregado, 'producto' => (string)$a->producto, 'id_categoria' => (int)$a->id_categoria,
				'vigente' => $a->vigente === 't' || $a->vigente === true);
		}
		return $res;
	}

	/**
	 * Pone varios agregados de una vez en la categoría ($poner) y saca otros de ella ($quitar, quedan sin categoría).
	 * Devuelve cuántos cambiaron.
	 */
	public function asignar($idCategoria, $poner, $quitar)
	{
		$this->exigir();
		$idCategoria = (int)$idCategoria;
		if (!$this->q('SELECT 1 FROM categoria_agregado_web WHERE id_categoria_agregado = ? AND eliminado IS NOT TRUE', array($idCategoria))->row()) {
			throw new Exception('La categoría ya no existe. Recarga la página.', 404);
		}
		$limpiar = function ($ids) {
			return array_values(array_unique(array_filter(array_map('intval', is_array($ids) ? $ids : array()), function ($x) { return $x > 0; })));
		};
		$poner = $limpiar($poner);
		$quitar = array_values(array_diff($limpiar($quitar), $poner));
		if (count($poner) + count($quitar) > 5000) throw new Exception('Demasiados agregados de una vez', 400);
		$db = $this->db();
		$db->trans_begin();
		$puestos = $quitados = 0;
		if ($poner) {
			$this->q('UPDATE agregados SET id_categoria_agregado_web = ? WHERE id_agregado IN (' . implode(',', $poner) . ') AND eliminado IS NOT TRUE
				AND COALESCE(id_categoria_agregado_web, 0) <> ?', array($idCategoria, $idCategoria));
			$puestos = $db->affected_rows();
		}
		if ($quitar) {
			$this->q('UPDATE agregados SET id_categoria_agregado_web = 0 WHERE id_agregado IN (' . implode(',', $quitar) . ') AND id_categoria_agregado_web = ?',
				array($idCategoria));
			$quitados = $db->affected_rows();
		}
		if ($db->trans_status() === false) { $db->trans_rollback(); throw new Exception('No se pudieron cambiar los agregados', 500); }
		$db->trans_commit();
		return array('puestos' => $puestos, 'quitados' => $quitados);
	}
}
