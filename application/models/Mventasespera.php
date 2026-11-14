<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Caja web: ventas en espera y bitácora de auditoría (10-11-2026).
 *
 * - Venta en espera (caja_venta_espera): el carro de la venta directa guardado con un nombre. Se recupera (vuelve a la
 *   venta, solo si está vacía: lo controla la pantalla) o se anula con justificación. Estados: espera, recuperada, anulada.
 *   Origen: manual (Opciones > Poner en espera), menu (al salir de la Caja por el menú) o recarga (al recargar o cerrar
 *   la página con una venta en curso).
 * - Auditoría (caja_auditoria): un evento por fila. Eventos: espera_creada, espera_recuperada, espera_anulada,
 *   productos_omitidos (productos de una mesa que no se enviaron y se perdieron al salir, recargar o cambiar de mesa),
 *   venta_vaciada (el cajero vació una venta directa con productos), gaveta_abierta y garzon_cambiado.
 * - El monto de una venta en espera es el que veía el cajero en pantalla al dejarla (la venta real se calcula en el
 *   servidor al cobrar, como siempre).
 * - Las tablas las crea la migración 20261110210000. Si aún no corrió en esta base, disponible() = false y la Caja
 *   funciona como antes. Nada de DDL aquí (CONVENCIONES, Regla 6).
 */
class Mventasespera extends CI_Model
{
	const TZ = 'America/Santiago';
	const MAX_LINEAS = 300;
	/** Eventos que puede informar la pantalla (los demás los registra el servidor). */
	const EVENTOS_PANTALLA = array('venta_vaciada', 'productos_omitidos');
	const ORIGENES_OMITIDOS = array('menu', 'recarga', 'salir_mesa', 'cambio_mesa');

	private $db2 = null;
	private $hay = null;

	function __construct()
	{
		parent::__construct();
		date_default_timezone_set(self::TZ);
	}

	/** ¿Están las tablas? (lectura del catálogo, sin locks) */
	public function disponible()
	{
		if ($this->hay === null) {
			$r = $this->q("SELECT (to_regclass('public.caja_venta_espera') IS NOT NULL AND to_regclass('public.caja_auditoria') IS NOT NULL) AS ok")->row();
			$this->hay = $r && ($r->ok === 't' || $r->ok === true);
		}
		return $this->hay;
	}

	private function exigir()
	{
		if (!$this->disponible()) {
			throw new Exception('Las ventas en espera aún no están disponibles: falta actualizar la base de datos. Vuelve a ingresar al sistema en unos minutos.', 409);
		}
	}

	// ====================== Ventas en espera ======================

	/**
	 * Ventas en espera sin resolver, de todas las cajas (la más reciente primero).
	 * bloquea_cierre: impide cerrar la caja $idCajaActual (ver bloqueanCierre).
	 */
	public function pendientes($idCajaActual = 0)
	{
		$this->exigir();
		$filas = $this->q("SELECT e.id_venta_espera, e.nombre, e.origen, e.id_caja, e.id_usuario, COALESCE(u.nombre_usuario, '') AS usuario,
				to_char(e.fecha, 'YYYY-MM-DD HH24:MI:SS') AS fecha, to_char(e.fecha, 'DD/MM HH24:MI') AS fecha_texto,
				e.total, e.unidades, e.lineas, e.datos,
				(" . self::BLOQUEA_CIERRE . ") AS bloquea
			FROM caja_venta_espera e LEFT JOIN usuario u ON u.id_usuario = e.id_usuario
			WHERE e.estado = 'espera'
			ORDER BY e.fecha DESC, e.id_venta_espera DESC LIMIT 200", array((int)$idCajaActual))->result();
		$res = array();
		foreach ($filas as $f) {
			$d = json_decode((string)$f->datos, true);
			$productos = array();
			foreach (array_slice(isset($d['lineas']) && is_array($d['lineas']) ? $d['lineas'] : array(), 0, 40) as $l) {
				$productos[] = array('nombre' => (string)$l['nombre'], 'cantidad' => (float)$l['cantidad'], 'opciones' => (string)($l['opciones'] ?? ''));
			}
			$t = strtotime($f->fecha);
			$res[] = array(
				'id' => (int)$f->id_venta_espera,
				'nombre' => (string)$f->nombre,
				'origen' => (string)$f->origen,
				'id_caja' => (int)$f->id_caja,
				'usuario' => (string)$f->usuario,
				'fecha' => (string)$f->fecha_texto,
				'minutos' => $t ? max(0, (int)floor((time() - $t) / 60)) : 0,
				'total' => (int)$f->total,
				'unidades' => (float)$f->unidades,
				'lineas' => (int)$f->lineas,
				'cliente' => isset($d['cliente']['nombre_cliente']) ? (string)$d['cliente']['nombre_cliente'] : '',
				'productos' => $productos,
				'bloquea_cierre' => $f->bloquea === 't' || $f->bloquea === true,
			);
		}
		return $res;
	}

	/**
	 * La caja no se cierra con ventas en espera pendientes: las suyas y las que quedaron de cajas ya cerradas (de antes
	 * de esta regla). Las de otra caja todavía abierta las resuelve esa caja. Un solo criterio para la pantalla y el cierre.
	 */
	const BLOQUEA_CIERRE = 'NOT EXISTS (SELECT 1 FROM caja c WHERE c.id_caja = e.id_caja AND c.cerrada IS NOT TRUE AND c.id_caja <> ?)';

	/** Ventas en espera que impiden cerrar la caja $idCaja ([] si la función no está disponible). */
	public function bloqueanCierre($idCaja)
	{
		if (!$this->disponible()) return array();
		return $this->q("SELECT e.id_venta_espera, e.nombre FROM caja_venta_espera e
			WHERE e.estado = 'espera' AND " . self::BLOQUEA_CIERRE . "
			ORDER BY e.fecha, e.id_venta_espera", array((int)$idCaja))->result();
	}

	/** Opciones > Poner en espera. */
	public function crear($caja, $idUsuario, $p, $origen = 'manual')
	{
		$this->exigir();
		$db = $this->db();
		$db->trans_begin();
		try {
			$r = $this->insertarEspera($caja, $idUsuario, $p, $origen);
			$db->trans_commit();
		} catch (Exception $e) {
			$db->trans_rollback();
			throw $e;
		}
		return $r;
	}

	/** La saca de la espera para cargarla en la venta. Devuelve sus datos (el carro). */
	public function recuperar($caja, $idUsuario, $id)
	{
		$this->exigir();
		$db = $this->db();
		$db->trans_begin();
		try {
			$f = $this->q("UPDATE caja_venta_espera SET estado = 'recuperada', id_caja_cierre = ?, id_usuario_cierre = ?, fecha_cierre = ?
				WHERE id_venta_espera = ? AND estado = 'espera'
				RETURNING id_venta_espera, nombre, total, unidades, datos",
				array((int)$caja->id_caja, (int)$idUsuario, date('Y-m-d H:i:s'), (int)$id))->row();
			if (!$f) throw new Exception('Esta venta ya no está en espera: otra caja o usuario la recuperó o la anuló.', 409);
			$datos = json_decode((string)$f->datos, true);
			$this->insertarEvento($caja, $idUsuario, 'espera_recuperada', array(
				'id_venta_espera' => (int)$f->id_venta_espera, 'monto' => (int)$f->total, 'unidades' => (float)$f->unidades,
				'detalle' => array('nombre' => (string)$f->nombre),
			));
			$db->trans_commit();
		} catch (Exception $e) {
			$db->trans_rollback();
			throw $e;
		}
		return array('id' => (int)$f->id_venta_espera, 'nombre' => (string)$f->nombre, 'datos' => is_array($datos) ? $datos : array());
	}

	/** Anula una venta en espera con su justificación (Consultas > Anulación de ventas). */
	public function anular($caja, $idUsuario, $id, $motivo)
	{
		$this->exigir();
		$motivo = trim(preg_replace('/\s+/u', ' ', (string)$motivo));
		if (mb_strlen($motivo) < 5) throw new Exception('Escribe la justificación de la anulación (al menos 5 caracteres).', 400);
		$motivo = mb_substr($motivo, 0, 250);
		$db = $this->db();
		$db->trans_begin();
		try {
			$f = $this->q("UPDATE caja_venta_espera SET estado = 'anulada', id_caja_cierre = ?, id_usuario_cierre = ?, fecha_cierre = ?, motivo_anulacion = ?
				WHERE id_venta_espera = ? AND estado = 'espera'
				RETURNING id_venta_espera, nombre, total, unidades, datos",
				array((int)$caja->id_caja, (int)$idUsuario, date('Y-m-d H:i:s'), $motivo, (int)$id))->row();
			if (!$f) throw new Exception('Esta venta ya no está en espera: otra caja o usuario la recuperó o la anuló.', 409);
			$datos = json_decode((string)$f->datos, true);
			$this->insertarEvento($caja, $idUsuario, 'espera_anulada', array(
				'id_venta_espera' => (int)$f->id_venta_espera, 'monto' => (int)$f->total, 'unidades' => (float)$f->unidades,
				'motivo' => $motivo,
				'detalle' => array('nombre' => (string)$f->nombre, 'lineas' => isset($datos['lineas']) ? $datos['lineas'] : array()),
			));
			$db->trans_commit();
		} catch (Exception $e) {
			$db->trans_rollback();
			throw $e;
		}
		return array('ok' => true);
	}

	// ====================== Salida de la Caja y eventos ======================

	/**
	 * La pantalla deja la Caja con una venta en curso: por el menú (origen menu, el cajero lo confirmó) o al recargar
	 * o cerrar la página (origen recarga, lo manda el navegador al salir). La venta directa pasa a espera y los
	 * productos de mesa sin enviar quedan como omitidos. $p = {origen, espera: {...}|null, omitidos: {...}|null}.
	 * Se puede repetir sin duplicar (uid).
	 */
	public function salida($caja, $idUsuario, $p)
	{
		$this->exigir();
		$origen = isset($p['origen']) && $p['origen'] === 'menu' ? 'menu' : 'recarga';
		$res = array('espera' => null, 'omitidos' => 0);
		$db = $this->db();
		$db->trans_begin();
		try {
			if (!empty($p['espera']) && is_array($p['espera'])) {
				$res['espera'] = $this->insertarEspera($caja, $idUsuario, $p['espera'], $origen);
			}
			if (!empty($p['omitidos']) && is_array($p['omitidos'])) {
				$o = $p['omitidos'];
				$o['origen'] = $origen;
				$res['omitidos'] = $this->insertarOmitidos($caja, $idUsuario, $o);
			}
			$db->trans_commit();
		} catch (Exception $e) {
			$db->trans_rollback();
			throw $e;
		}
		return $res;
	}

	/** Eventos que informa la pantalla: venta_vaciada, productos_omitidos (al salir de una mesa sin enviar). */
	public function eventoPantalla($caja, $idUsuario, $evento, $p)
	{
		$this->exigir();
		if (!in_array($evento, self::EVENTOS_PANTALLA, true)) throw new Exception('Evento no válido', 400);
		if ($evento === 'productos_omitidos') return array('ok' => true, 'registrados' => $this->insertarOmitidos($caja, $idUsuario, $p));
		$v = $this->limpiarLineas(isset($p['lineas']) ? $p['lineas'] : array());
		if (!$v['lineas']) return array('ok' => true, 'registrados' => 0);
		$this->insertarEvento($caja, $idUsuario, 'venta_vaciada', array(
			'uid' => $this->uid(isset($p['uid']) ? $p['uid'] : ''),
			'origen' => 'manual',
			'monto' => $this->monto(isset($p['monto']) ? $p['monto'] : 0),
			'unidades' => $v['unidades'],
			'detalle' => array('lineas' => $v['lineas'], 'cliente' => $this->texto(isset($p['cliente']) ? $p['cliente'] : '', 120)),
		));
		return array('ok' => true, 'registrados' => 1);
	}

	/** Gaveta abierta desde la Caja (sin venta). Nunca interrumpe la apertura: si falla, solo queda en el log. */
	public function gaveta($caja, $idUsuario)
	{
		try {
			if (!$this->disponible()) return;
			$this->insertarEvento($caja, $idUsuario, 'gaveta_abierta', array('origen' => 'manual'));
		} catch (Exception $e) {
			log_message('error', 'Mventasespera gaveta: ' . $e->getMessage());
		}
	}

	/** Opciones de la mesa > Cambiar garzón. Si falla, solo queda en el log (el cambio ya está hecho). */
	public function garzonCambiado($caja, $idUsuario, $r)
	{
		try {
			if (!$this->disponible()) return;
			$this->insertarEvento($caja, $idUsuario, 'garzon_cambiado', array(
				'origen' => 'manual', 'id_mesa' => (int)$r['id_mesa'],
				'detalle' => array('mesa' => (string)$r['mesa'], 'de' => (string)$r['anterior'], 'a' => (string)$r['nombre']),
			));
		} catch (Exception $e) {
			log_message('error', 'Mventasespera garzonCambiado: ' . $e->getMessage());
		}
	}

	// ====================== Internos ======================

	private function insertarEspera($caja, $idUsuario, $p, $origen)
	{
		$uid = $this->uid(isset($p['uid']) ? $p['uid'] : '');
		$nombre = $this->texto(isset($p['nombre']) ? $p['nombre'] : '', 80);
		if ($nombre === '') throw new Exception('Escribe un nombre para la venta en espera.', 400);
		$v = $this->limpiarLineas(isset($p['lineas']) ? $p['lineas'] : array());
		if (!$v['lineas']) throw new Exception('La venta no tiene productos.', 400);
		$cliente = null;
		if (!empty($p['cliente']) && is_array($p['cliente']) && (int)($p['cliente']['id_cliente'] ?? 0) > 0) {
			$cliente = array(
				'id_cliente' => (int)$p['cliente']['id_cliente'],
				'nombre_cliente' => $this->texto($p['cliente']['nombre_cliente'] ?? '', 120),
				'rut' => $this->texto($p['cliente']['rut'] ?? '', 20),
			);
		}
		$datos = array(
			'lineas' => $v['lineas'],
			'cliente' => $cliente,
			'txt_cliente' => $this->texto($p['txt_cliente'] ?? '', 150),
			'descuento' => max(0, round((float)($p['descuento'] ?? 0), 2)),
			'tipo_desc' => ($p['tipo_desc'] ?? '') === 'porc' ? 'porc' : 'monto',
			'observacion' => $this->texto($p['observacion'] ?? '', 150),
			'id_tdocumento' => (int)($p['id_tdocumento'] ?? 0),
			'id_lprecio' => (int)($p['id_lprecio'] ?? 0),
		);
		if (!in_array($origen, array('manual', 'menu', 'recarga'), true)) $origen = 'manual';
		$total = $this->monto($p['total'] ?? 0);
		$ahora = date('Y-m-d H:i:s');
		$f = $this->q("INSERT INTO caja_venta_espera (uid, nombre, estado, origen, id_caja, id_usuario, fecha, total, unidades, lineas, datos)
			VALUES (?, ?, 'espera', ?, ?, ?, ?, ?, ?, ?, ?)
			ON CONFLICT (uid) DO NOTHING
			RETURNING id_venta_espera",
			array($uid, $nombre, $origen, (int)$caja->id_caja, (int)$idUsuario, $ahora, $total, $v['unidades'], count($v['lineas']), $this->json($datos)))->row();
		if (!$f) {
			// Ya llegó antes (el navegador lo mandó dos veces): se devuelve la que está
			$e = $this->q('SELECT id_venta_espera, nombre FROM caja_venta_espera WHERE uid = ?', array($uid))->row();
			return array('id' => $e ? (int)$e->id_venta_espera : 0, 'nombre' => $e ? (string)$e->nombre : $nombre, 'repetida' => true);
		}
		$this->insertarEvento($caja, $idUsuario, 'espera_creada', array(
			'uid' => 'e-' . $uid, 'origen' => $origen, 'id_venta_espera' => (int)$f->id_venta_espera,
			'monto' => $total, 'unidades' => $v['unidades'],
			'detalle' => array('nombre' => $nombre, 'lineas' => $v['lineas'], 'cliente' => $cliente ? $cliente['nombre_cliente'] : ''),
		));
		return array('id' => (int)$f->id_venta_espera, 'nombre' => $nombre);
	}

	private function insertarOmitidos($caja, $idUsuario, $o)
	{
		$v = $this->limpiarLineas(isset($o['lineas']) ? $o['lineas'] : array());
		if (!$v['lineas']) return 0;
		$origen = isset($o['origen']) && in_array($o['origen'], self::ORIGENES_OMITIDOS, true) ? $o['origen'] : 'recarga';
		$this->insertarEvento($caja, $idUsuario, 'productos_omitidos', array(
			'uid' => $this->uid(isset($o['uid']) ? $o['uid'] : ''),
			'origen' => $origen,
			'id_mesa' => (int)($o['id_mesa'] ?? 0),
			'monto' => $this->monto($o['monto'] ?? 0),
			'unidades' => $v['unidades'],
			'detalle' => array('mesa' => $this->texto($o['mesa'] ?? '', 80), 'lineas' => $v['lineas']),
		));
		return count($v['lineas']);
	}

	private function insertarEvento($caja, $idUsuario, $evento, $e)
	{
		$this->q('INSERT INTO caja_auditoria (uid, fecha, id_caja, id_usuario, evento, origen, id_mesa, id_venta_espera, monto, unidades, detalle, motivo)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
			ON CONFLICT (uid) DO NOTHING',
			array(
				isset($e['uid']) && $e['uid'] !== '' ? substr($e['uid'], 0, 48) : null,
				date('Y-m-d H:i:s'),
				(int)$caja->id_caja,
				(int)$idUsuario,
				$evento,
				isset($e['origen']) ? $e['origen'] : null,
				!empty($e['id_mesa']) ? (int)$e['id_mesa'] : null,
				!empty($e['id_venta_espera']) ? (int)$e['id_venta_espera'] : null,
				isset($e['monto']) ? (int)$e['monto'] : 0,
				isset($e['unidades']) ? (float)$e['unidades'] : 0,
				isset($e['detalle']) ? $this->json($e['detalle']) : null,
				isset($e['motivo']) ? mb_substr((string)$e['motivo'], 0, 250) : null,
			));
	}

	/** Líneas del carro con tipos y largos acotados: {id_producto, nombre, precio, cantidad, agregados, opciones, nota}. */
	private function limpiarLineas($lineas)
	{
		$res = array();
		$unidades = 0;
		foreach (is_array($lineas) ? $lineas : array() as $l) {
			if (count($res) >= self::MAX_LINEAS) break;
			if (!is_array($l)) continue;
			$id = (int)($l['id_producto'] ?? 0);
			$cant = round((float)($l['cantidad'] ?? 0), 3);
			if ($id <= 0 || $cant <= 0) continue;
			$cant = min($cant, 99999);
			$agregados = array();
			foreach (array_slice(is_array($l['agregados'] ?? null) ? $l['agregados'] : array(), 0, 20) as $a) {
				if ((int)$a > 0) $agregados[] = (int)$a;
			}
			$res[] = array(
				'id_producto' => $id,
				'nombre' => $this->texto($l['nombre'] ?? '', 120),
				'precio' => $this->monto($l['precio'] ?? 0),
				'cantidad' => $cant,
				'agregados' => $agregados,
				'opciones' => $this->texto($l['opciones'] ?? '', 200),
				// La nota se guarda tal cual (la pantalla junta líneas iguales comparando la nota)
				'nota' => mb_substr(is_scalar($l['nota'] ?? '') ? (string)($l['nota'] ?? '') : '', 0, 200),
			);
			$unidades += $cant;
		}
		return array('lineas' => $res, 'unidades' => round($unidades, 3));
	}

	private function texto($s, $largo)
	{
		if (!is_scalar($s)) return '';
		return mb_substr(trim(preg_replace('/\s+/u', ' ', (string)$s)), 0, $largo);
	}

	private function monto($n)
	{
		$n = is_numeric($n) ? (float)$n : 0;
		return (int)max(-2000000000, min(2000000000, round($n)));
	}

	private function uid($s)
	{
		$s = substr(preg_replace('/[^A-Za-z0-9_-]/', '', is_scalar($s) ? (string)$s : ''), 0, 44);
		return strlen($s) >= 8 ? $s : bin2hex(random_bytes(12));
	}

	private function json($v)
	{
		$j = json_encode($v, JSON_UNESCAPED_UNICODE | (defined('JSON_INVALID_UTF8_SUBSTITUTE') ? JSON_INVALID_UTF8_SUBSTITUTE : 0));
		if ($j === false) throw new Exception('Datos inválidos (texto no legible)', 400);
		return $j;
	}

	private function db()
	{
		if ($this->db2 === null) {
			$this->load->helper('tenant');
			$this->db2 = get_tenant_db($this);
			// Los errores se manejan con excepciones para poder hacer rollback y responder JSON
			$this->db2->db_debug = FALSE;
		}
		return $this->db2;
	}

	private function q($sql, $binds = array())
	{
		$db = $this->db();
		$r = $db->query($sql, $binds);
		if ($r === FALSE) {
			$e = $db->error();
			log_message('error', 'Mventasespera: ' . $e['message'] . ' | ' . $sql);
			throw new Exception($e['message'] ? trim(preg_replace('/^ERROR:\s*/', '', $e['message'])) : 'Error de base de datos', 500);
		}
		return $r;
	}
}
