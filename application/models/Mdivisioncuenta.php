<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Caja web > Dividir cuenta guardado en la base (tabla caja_division, migración 20261110220000), para que lo armado en una
 * caja se vea desde cualquier otra. Una fila por pedido abierto de la mesa; `version` evita que dos cajas se pisen: si
 * alguien guardó antes, se devuelve lo que está para que la pantalla lo muestre (conflicto) en vez de sobrescribirlo.
 * Lo que se guarda lo arma la pantalla (js.php, dividir cuenta); aquí se acotan tipos y largos. Si la tabla aún no existe,
 * disponible() = false y la pantalla lo guarda solo en ese computador.
 */
class Mdivisioncuenta extends CI_Model
{
	const TZ = 'America/Santiago';
	const DIAS_LIMPIEZA = 7;

	private $db2 = null;
	private $hay = null;

	function __construct()
	{
		parent::__construct();
		date_default_timezone_set(self::TZ);
	}

	public function disponible()
	{
		if ($this->hay === null) {
			$r = $this->q("SELECT to_regclass('public.caja_division') IS NOT NULL AS ok")->row();
			$this->hay = $r && ($r->ok === 't' || $r->ok === true);
		}
		return $this->hay;
	}

	/** Pedido abierto de la mesa (la misma condición que Mcaja::cotizacionDeMesa) o 0. */
	private function pedidoDeMesa($idMesa)
	{
		$r = $this->q("SELECT c.id_cotizacion FROM mesa m JOIN cotizacion c ON c.id_cotizacion = m.id_cotizacion
			WHERE m.id_mesa = ? AND m.id_cotizacion > 0 AND c.pagado IS NOT TRUE AND c.vigente IS NOT FALSE
				AND c.eseliminado IS NOT TRUE AND c.esanulado IS NOT TRUE", array((int)$idMesa))->row();
		return $r ? (int)$r->id_cotizacion : 0;
	}

	/** División del pedido abierto de la mesa: {id_cotizacion, version, datos|null}. */
	public function leer($idMesa)
	{
		$idCot = $this->pedidoDeMesa($idMesa);
		if (!$idCot) return array('id_cotizacion' => 0, 'version' => 0, 'datos' => null);
		$f = $this->q('SELECT version, datos FROM caja_division WHERE id_cotizacion = ?', array($idCot))->row();
		return array('id_cotizacion' => $idCot, 'version' => $f ? (int)$f->version : 0,
			// Sin "true": los objetos vacíos siguen siendo {} (los productos de un comensal sin nada asignado)
			'datos' => $f ? json_decode((string)$f->datos) : null);
	}

	/**
	 * Guarda la división si nadie la cambió desde la versión que tenía la pantalla. Devuelve {version} o, si otra caja
	 * guardó antes, {conflicto: true, version, datos} con lo que está.
	 */
	public function guardar($idUsuario, $p)
	{
		$idMesa = (int)($p['id_mesa'] ?? 0);
		$idCot = (int)($p['id_cotizacion'] ?? 0);
		$version = (int)($p['version'] ?? 0);
		if (!$idCot || $this->pedidoDeMesa($idMesa) !== $idCot) {
			throw new Exception('La mesa ya no tiene ese pedido abierto: vuelve a abrirla.', 409);
		}
		$json = json_encode($this->limpiar(isset($p['datos']) && is_array($p['datos']) ? $p['datos'] : array()),
			JSON_UNESCAPED_UNICODE | (defined('JSON_INVALID_UTF8_SUBSTITUTE') ? JSON_INVALID_UTF8_SUBSTITUTE : 0));
		if ($json === false || strlen($json) > 200000) throw new Exception('La división es demasiado grande', 400);
		$ahora = date('Y-m-d H:i:s');
		if ($version <= 0) {
			$f = $this->q('INSERT INTO caja_division (id_cotizacion, id_mesa, datos, version, id_usuario, fecha) VALUES (?, ?, ?, 1, ?, ?)
				ON CONFLICT (id_cotizacion) DO NOTHING RETURNING version', array($idCot, $idMesa, $json, (int)$idUsuario, $ahora))->row();
		} else {
			$f = $this->q('UPDATE caja_division SET datos = ?, version = version + 1, id_mesa = ?, id_usuario = ?, fecha = ?
				WHERE id_cotizacion = ? AND version = ? RETURNING version', array($json, $idMesa, (int)$idUsuario, $ahora, $idCot, $version))->row();
		}
		// De paso se borran las divisiones viejas (mesas que se cerraron sin pasar por aquí)
		$this->q('DELETE FROM caja_division WHERE fecha < ?', array(date('Y-m-d H:i:s', strtotime('-' . self::DIAS_LIMPIEZA . ' days'))));
		if ($f) return array('version' => (int)$f->version);
		$actual = $this->leer($idMesa);
		return array('conflicto' => true, 'version' => $actual['version'], 'datos' => $actual['datos']);
	}

	/** La mesa se cerró (todo cobrado): se borra su división, si ese pedido ya no está abierto en ninguna mesa. */
	public function borrar($idCot)
	{
		$this->q("DELETE FROM caja_division WHERE id_cotizacion = ? AND NOT EXISTS (
				SELECT 1 FROM mesa m JOIN cotizacion c ON c.id_cotizacion = m.id_cotizacion
				WHERE m.id_cotizacion = ? AND c.pagado IS NOT TRUE AND c.vigente IS NOT FALSE AND c.eseliminado IS NOT TRUE AND c.esanulado IS NOT TRUE)",
			array((int)$idCot, (int)$idCot));
		return array('ok' => true);
	}

	/** Tipos y largos acotados: {modo, sig, comensales: [{id, nombre, items: {id_detalle: cantidad}, pagado}], partes, pagadas}. */
	private function limpiar($d)
	{
		$modo = isset($d['modo']) && in_array($d['modo'], array('comensal', 'iguales', 'libre'), true) ? $d['modo'] : 'comensal';
		$comensales = array();
		$vistos = array();
		$maxId = 0;
		foreach (array_slice(isset($d['comensales']) && is_array($d['comensales']) ? $d['comensales'] : array(), 0, 30) as $c) {
			if (!is_array($c)) continue;
			$id = (int)($c['id'] ?? 0);
			if ($id <= 0 || isset($vistos[$id])) continue;
			$vistos[$id] = true;
			$maxId = max($maxId, $id);
			$items = array();
			foreach (isset($c['items']) && is_array($c['items']) ? $c['items'] : array() as $k => $q) {
				if (count($items) >= 300) break;
				$k = (int)$k;
				$q = round(is_numeric($q) ? (float)$q : 0, 3);
				if ($k > 0 && $q > 0 && $q <= 99999) $items[(string)$k] = $q;
			}
			$pagado = null;
			if (!empty($c['pagado']) && is_array($c['pagado'])) {
				$pagado = array('total' => (int)($c['pagado']['total'] ?? 0), 'doc' => $this->texto($c['pagado']['doc'] ?? '', 80));
			}
			$nombre = $this->texto($c['nombre'] ?? '', 40);
			$comensales[] = array('id' => $id, 'nombre' => $nombre !== '' ? $nombre : 'Comensal ' . $id, 'items' => (object)$items, 'pagado' => $pagado);
		}
		$pagadas = array();
		foreach (array_slice(isset($d['pagadas']) && is_array($d['pagadas']) ? $d['pagadas'] : array(), 0, 30) as $p) {
			if (is_array($p)) $pagadas[] = array('total' => (int)($p['total'] ?? 0), 'doc' => $this->texto($p['doc'] ?? '', 80));
		}
		return array(
			'modo' => $modo,
			'sig' => max((int)($d['sig'] ?? 1), $maxId + 1),
			'comensales' => $comensales,
			'partes' => max(2, min(30, (int)($d['partes'] ?? 2))),
			'pagadas' => $pagadas,
		);
	}

	private function texto($s, $largo)
	{
		return is_scalar($s) ? mb_substr(trim(preg_replace('/\s+/u', ' ', (string)$s)), 0, $largo) : '';
	}

	private function q($sql, $binds = array())
	{
		if ($this->db2 === null) {
			$this->load->helper('tenant');
			$this->db2 = get_tenant_db($this);
			$this->db2->db_debug = FALSE;
		}
		$r = $this->db2->query($sql, $binds);
		if ($r === FALSE) {
			$e = $this->db2->error();
			log_message('error', 'Mdivisioncuenta: ' . $e['message'] . ' | ' . $sql);
			throw new Exception($e['message'] ? trim(preg_replace('/^ERROR:\s*/', '', $e['message'])) : 'Error de base de datos', 500);
		}
		return $r;
	}
}
