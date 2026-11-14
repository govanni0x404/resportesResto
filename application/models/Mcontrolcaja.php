<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Herramientas > Control de cajas (11-11-2026), como control_caja de mipdv_web.
 *
 * - Las cajas vienen de la intranet (validarCliente > lista_cajas_autorizadas, ver ConexionesTenant::cajasAutorizadas):
 *   N° de caja, nombre, terminal, sucursal y si está vigente. Esos datos los manda la intranet y aquí no se editan.
 * - Aquí se configura la lista de precios, la bodega y si el cajero puede cambiar la lista en la Caja.
 * - Al abrir la Caja el cajero elige la caja (caja.id_control_caja): Mcaja toma de ahí la lista de precios (la de la
 *   caja reemplaza a la pública de Configuraciones) y la bodega de donde sale el stock.
 * - Sin la migración 20261111190000 (disponible() = false) no hay nada que configurar y la Caja abre como antes.
 */
class Mcontrolcaja extends CI_Model
{
	private $db2 = null;
	private $hay = null;

	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');
	}

	/**
	 * ¿Están control_caja (con sus columnas) y caja.id_control_caja? Solo se usan las filas que vienen de la intranet
	 * (id_control_caja_web): si la base ya tenía una tabla control_caja con otras filas, esas no se tocan ni se ofrecen.
	 */
	public function disponible()
	{
		if ($this->hay === null) {
			$r = $this->q("SELECT count(*) AS n FROM information_schema.columns WHERE table_schema = 'public'
				AND ((table_name = 'control_caja' AND column_name IN ('id_control_caja_web', 'id_lprecio', 'id_bodega', 'permitir_cambio_lprecio_caja', 'nombre_caja'))
					OR (table_name = 'caja' AND column_name = 'id_control_caja'))")->row();
			$this->hay = $r && (int)$r->n === 6;
		}
		return $this->hay;
	}

	private function exigir()
	{
		if (!$this->disponible()) {
			throw new Exception('Control de cajas aún no está disponible: falta actualizar la base de datos. Vuelve a ingresar al sistema en unos minutos.', 409);
		}
	}

	// ---------------- Sincronización con la intranet ----------------

	/**
	 * Guarda las cajas de la intranet: actualiza las que ya están (por id_control_caja_web) y agrega las nuevas, sin tocar
	 * lo configurado aquí (lista, bodega, cambio de lista). Las de las mismas sucursales que ya no vienen quedan no vigentes.
	 * Una lista vacía no cambia nada (puede ser una respuesta incompleta de la intranet), salvo que se indiquen las
	 * sucursales consultadas: la intranet respondió por ellas y lo que no viene de esas sucursales queda no vigente.
	 * @param array $sucursalesConsultadas id de sucursal de la intranet cuya lista de cajas vino completa (12-11-2026)
	 * @return array insertadas, actualizadas, desactivadas
	 */
	public function sincronizar($cajas, $sucursalesConsultadas = array())
	{
		$res = array('insertadas' => 0, 'actualizadas' => 0, 'desactivadas' => 0);
		$consultadas = array_values(array_filter(array_map('intval', (array)$sucursalesConsultadas), function ($x) { return $x > 0; }));
		if (!$this->disponible() || !is_array($cajas) || (!$cajas && !$consultadas)) return $res;
		$db = $this->db();
		$ahora = date('Y-m-d H:i:s');
		$ids = array();
		$sucursales = array_fill_keys($consultadas, true);
		// Una tabla control_caja anterior (del POS) podría no tener secuencia en la llave: entonces se numera aquí
		$d = $this->q("SELECT column_default FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'control_caja'
			AND column_name = 'id_control_caja'")->row();
		$conId = $d && $d->column_default === null;
		$conSucIntranet = (bool)$this->q("SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'control_caja'
			AND column_name = 'id_sucursal_intranet'")->row();
		// Largo de los textos (una tabla anterior puede tenerlos más cortos)
		$largo = array('nombre_caja' => 80, 'terminal' => 80);
		foreach ($this->q("SELECT column_name, character_maximum_length AS n FROM information_schema.columns WHERE table_schema = 'public'
				AND table_name = 'control_caja' AND column_name IN ('nombre_caja', 'terminal')")->result() as $f) {
			if ((int)$f->n > 0) $largo[$f->column_name] = (int)$f->n;
		}
		$db->trans_begin();
		try {
			if ($conId) $this->q('LOCK TABLE control_caja IN SHARE ROW EXCLUSIVE MODE');
			foreach ($cajas as $c) {
				$idWeb = (int)($c['id_control_caja'] ?? 0);
				if ($idWeb <= 0) continue;
				$ids[] = $idWeb;
				$sucursales[(int)($c['id_sucursal'] ?? 0)] = true;
				$nombre = trim((string)($c['nombre_caja'] ?? ''));
				$nombre = mb_substr($nombre !== '' ? $nombre : 'Caja ' . (int)($c['caja_n'] ?? 0), 0, $largo['nombre_caja']);
				$valores = array((int)($c['id_sucursal'] ?? 0), (int)($c['caja_n'] ?? 0), $nombre, mb_substr(trim((string)($c['terminal'] ?? '')), 0, $largo['terminal']),
					!empty($c['vigente']) ? 't' : 'f', !empty($c['caja_administrador']) ? 't' : 'f', (int)($c['id_software'] ?? RESTO_ID_SOFTWARE), $ahora);
				// La sucursal de la intranet va en id_sucursal_intranet (migración 20261112090000) y, como antes, en id_sucursal
				if ($conSucIntranet) $valores[] = (int)($c['id_sucursal'] ?? 0);
				$fila = $this->q('SELECT id_control_caja FROM control_caja WHERE id_control_caja_web = ? ORDER BY id_control_caja LIMIT 1', array($idWeb))->row();
				if ($fila) {
					$this->q('UPDATE control_caja SET id_sucursal = ?, caja_n = ?, nombre_caja = ?, terminal = ?, vigente = ?, caja_administrador = ?,
							id_software = ?, fecha_sincronizacion = ?' . ($conSucIntranet ? ', id_sucursal_intranet = ?' : '') . ' WHERE id_control_caja = ?',
						array_merge($valores, array((int)$fila->id_control_caja)));
					$res['actualizadas']++;
				} else {
					$this->q('INSERT INTO control_caja (id_sucursal, caja_n, nombre_caja, terminal, vigente, caja_administrador, id_software, fecha_sincronizacion,
							' . ($conSucIntranet ? 'id_sucursal_intranet, ' : '') . 'id_control_caja_web, id_lprecio, id_bodega, permitir_cambio_lprecio_caja' . ($conId ? ', id_control_caja' : '') . ')
						SELECT ?, ?, ?, ?, ?, ?, ?, ?, ' . ($conSucIntranet ? '?, ' : '') . '?, 0, 0, false' . ($conId ? ', COALESCE(max(id_control_caja), 0) + 1 FROM control_caja' : ''),
						array_merge($valores, array($idWeb)));
					$res['insertadas']++;
				}
			}
			if ($sucursales) {
				$suc = array_keys($sucursales);
				$r = $this->q('UPDATE control_caja SET vigente = false, fecha_sincronizacion = ?
					WHERE vigente IS NOT FALSE AND COALESCE(id_control_caja_web, 0) > 0
						AND (id_software IS NULL OR id_software = ?)
						AND COALESCE(' . $this->sucursalSql('') . ', 0) IN (' . implode(',', array_fill(0, count($suc), '?')) . ')'
						. ($ids ? ' AND id_control_caja_web NOT IN (' . implode(',', array_fill(0, count($ids), '?')) . ')' : ''),
					array_merge(array($ahora, (int)RESTO_ID_SOFTWARE), $suc, $ids));
				$res['desactivadas'] = (int)$db->affected_rows();
			}
			$db->trans_commit();
		} catch (Exception $e) {
			$db->trans_rollback();
			throw $e;
		}
		return $res;
	}

	/** Las cajas que trajo el login (sesión): se guardan una vez por sesión, sin volver a llamar a la intranet. */
	public function sincronizarSesion()
	{
		if (!$this->disponible() || $this->session->userdata('cajas_intranet_sinc')) return;
		$cajas = $this->session->userdata('cajas_intranet');
		if (!is_array($cajas)) return;
		try {
			$this->sincronizar($cajas);
			$this->session->set_userdata('cajas_intranet_sinc', 1);
		} catch (Exception $e) {
			log_message('error', 'Mcontrolcaja::sincronizarSesion: ' . $e->getMessage());
		}
	}

	/** Botón "Actualizar desde la intranet": vuelve a preguntar a validarCliente por las cajas de esta empresa. */
	public function sincronizarIntranet()
	{
		$this->exigir();
		$rut = (string)$this->session->userdata('rut_cliente_intranet');
		if ($rut === '') {
			// Sesión iniciada antes de este cambio: el RUT como lo arma el login (con puntos y guion)
			$partes = explode('-', (string)$this->session->userdata('rut_cliente'));
			$num = (int)preg_replace('/\D/', '', $partes[0]);
			$rut = ($num < 10000000 ? '0' : '') . number_format($num, 0, ',', '.') . '-' . (isset($partes[1]) ? $partes[1] : '');
		}
		$this->load->library('ConexionesTenant');
		$con = $this->conexionestenant->resolverDesdeIntranet($rut, (int)$this->session->userdata('id_sucursal'), RESTO_ID_SOFTWARE,
			(string)$this->session->userdata('abreviatura_software'));
		if (!$con['ok'] && empty($con['cajas'])) {
			throw new Exception('No se pudo consultar la intranet en este momento. Intenta nuevamente en unos minutos.', 502);
		}
		$r = $this->sincronizar($con['cajas']);
		$this->session->set_userdata(array('cajas_intranet' => $con['cajas'], 'cajas_intranet_sinc' => 1));
		$r['recibidas'] = count($con['cajas']);
		return $r;
	}

	// ---------------- Pantalla Control de cajas ----------------

	/** @param int|null $idSucursal sucursal de la intranet elegida en la pantalla (12-11-2026); null = todas (sin empresas/sucursales) */
	public function listar($idSucursal = null)
	{
		$this->exigir();
		$this->sincronizarSesion();
		$cajas = array();
		// Nombre de la sucursal (Configuraciones > Datos de la Empresa, migración 20261111200000); sin la tabla, solo el N°
		$conSucursal = (bool)$this->q("SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'empresa_sucursal'
			AND column_name = 'id_sucursal_intranet'")->row();
		$suc = $this->sucursalSql('cc');
		foreach ($this->q("SELECT cc.id_control_caja, COALESCE(cc.id_control_caja_web, 0) AS id_web, COALESCE($suc, 0) AS id_sucursal,
				" . ($conSucursal ? "COALESCE((SELECT es.nombre_sucursal FROM empresa_sucursal es WHERE es.id_sucursal_intranet = $suc
					ORDER BY es.id_empresa_sucursal LIMIT 1), '')" : "''") . " AS nombre_sucursal,
				COALESCE(cc.caja_n, 0) AS caja_n, COALESCE(cc.nombre_caja, '') AS nombre_caja, COALESCE(cc.terminal, '') AS terminal,
				(cc.vigente IS NOT FALSE) AS vigente, (cc.caja_administrador IS TRUE) AS caja_administrador,
				COALESCE(cc.id_lprecio, 0) AS id_lprecio, COALESCE(cc.id_bodega, 0) AS id_bodega,
				(cc.permitir_cambio_lprecio_caja IS TRUE) AS permitir_cambio_lprecio,
				to_char(cc.fecha_sincronizacion, 'DD/MM/YYYY HH24:MI') AS sincronizada,
				t.id_caja AS turno, t.usuario AS turno_usuario, t.desde AS turno_desde
			FROM control_caja cc
			LEFT JOIN LATERAL (SELECT c.id_caja, COALESCE(u.nombre_usuario, '') AS usuario, to_char(c.fecha_movimiento, 'DD/MM/YYYY HH24:MI') AS desde
				FROM caja c LEFT JOIN usuario u ON u.id_usuario = c.id_usuario
				WHERE c.id_control_caja = cc.id_control_caja AND c.cerrada IS NOT TRUE ORDER BY c.id_caja DESC LIMIT 1) t ON true
			WHERE COALESCE(cc.id_control_caja_web, 0) > 0 AND (cc.id_software IS NULL OR cc.id_software = ?)
				" . ($idSucursal !== null ? "AND COALESCE($suc, 0) = " . (int)$idSucursal : '') . "
			ORDER BY (cc.vigente IS NOT FALSE) DESC, $suc, cc.caja_n, cc.id_control_caja", array((int)RESTO_ID_SOFTWARE))->result() as $f) {
			$cajas[] = array(
				'id' => (int)$f->id_control_caja, 'id_web' => (int)$f->id_web, 'id_sucursal' => (int)$f->id_sucursal,
				'sucursal' => (string)$f->nombre_sucursal,
				'caja_n' => (int)$f->caja_n, 'nombre' => $f->nombre_caja, 'terminal' => $f->terminal,
				'vigente' => $this->bool($f->vigente), 'administrador' => $this->bool($f->caja_administrador),
				'id_lprecio' => (int)$f->id_lprecio, 'id_bodega' => (int)$f->id_bodega, 'permitir_cambio_lprecio' => $this->bool($f->permitir_cambio_lprecio),
				'sincronizada' => (string)$f->sincronizada,
				'turno' => $f->turno ? array('id_caja' => (int)$f->turno, 'usuario' => $f->turno_usuario, 'desde' => $f->turno_desde) : null,
			);
		}
		return array('cajas' => $cajas, 'listas' => $this->listas(), 'bodegas' => $this->bodegas(), 'lista_publica' => $this->listaPublica());
	}

	public function guardar($id, $idLprecio, $idBodega, $permitir)
	{
		$this->exigir();
		$id = (int)$id;
		$idLprecio = (int)$idLprecio;
		$idBodega = (int)$idBodega;
		if (!$this->q('SELECT 1 FROM control_caja WHERE id_control_caja = ?', array($id))->row()) throw new Exception('La caja no existe', 404);
		if ($idLprecio > 0 && !$this->q('SELECT 1 FROM lprecio WHERE id_lprecio = ?', array($idLprecio))->row()) throw new Exception('La lista de precios no existe', 400);
		if ($idBodega > 0 && !$this->q('SELECT 1 FROM bodega WHERE id_bodega = ?', array($idBodega))->row()) throw new Exception('La bodega no existe', 400);
		$this->q('UPDATE control_caja SET id_lprecio = ?, id_bodega = ?, permitir_cambio_lprecio_caja = ? WHERE id_control_caja = ?',
			array($idLprecio, $idBodega, $permitir ? 't' : 'f', $id));
		return array('ok' => true);
	}

	// ---------------- Apertura de la Caja ----------------

	/**
	 * Cajas que se pueden elegir al abrir: las vigentes de este sistema, con su lista y bodega. `ocupada`: otro usuario tiene
	 * un turno abierto con esa caja (no se puede elegir). Lista vacía = no hay control de cajas: se abre como antes.
	 * @param int|null $idSucursal 12-11-2026: la sucursal elegida al abrir; null = la de la sesión (como antes)
	 * @param int $idControlCaja > 0: solo esa caja, de cualquier sucursal (para validarla al abrir)
	 */
	public function paraAbrir($idUsuario, $idSucursal = null, $idControlCaja = 0)
	{
		if (!$this->disponible()) return array();
		$this->sincronizarSesion();
		$publica = $this->listaPublica();
		$res = array();
		$suc = $this->sucursalSql('cc');
		foreach ($this->q("SELECT cc.id_control_caja, COALESCE(cc.caja_n, 0) AS caja_n, COALESCE(cc.nombre_caja, '') AS nombre_caja,
				COALESCE(cc.terminal, '') AS terminal, COALESCE($suc, 0) AS id_sucursal,
				COALESCE(l.nombre_lprecio, '') AS lista, COALESCE(b.nombre_bodega, '') AS bodega,
				(cc.permitir_cambio_lprecio_caja IS TRUE) AS cambia_lista,
				(SELECT COALESCE(u.nombre_usuario, 'otro usuario') FROM caja c LEFT JOIN usuario u ON u.id_usuario = c.id_usuario
					WHERE c.id_control_caja = cc.id_control_caja AND c.cerrada IS NOT TRUE AND c.id_usuario <> ? ORDER BY c.id_caja DESC LIMIT 1) AS ocupada_por
			FROM control_caja cc
			LEFT JOIN lprecio l ON l.id_lprecio = cc.id_lprecio AND cc.id_lprecio > 0
			LEFT JOIN bodega b ON b.id_bodega = cc.id_bodega AND cc.id_bodega > 0
			WHERE COALESCE(cc.id_control_caja_web, 0) > 0 AND cc.vigente IS NOT FALSE AND (cc.id_software IS NULL OR cc.id_software = ?)
				" . ((int)$idControlCaja > 0 ? 'AND cc.id_control_caja = ' . (int)$idControlCaja
					: ($idSucursal !== null ? "AND COALESCE($suc, 0) = " . (int)$idSucursal
					: "AND (COALESCE($suc, 0) = 0 OR " . (int)$this->session->userdata('id_sucursal') . " = 0 OR $suc = " . (int)$this->session->userdata('id_sucursal') . ')')) . "
			ORDER BY cc.caja_n, cc.id_control_caja",
			array((int)$idUsuario, (int)RESTO_ID_SOFTWARE))->result() as $f) {
			$res[] = array(
				'id' => (int)$f->id_control_caja, 'caja_n' => (int)$f->caja_n, 'nombre' => $f->nombre_caja, 'terminal' => $f->terminal,
				'lista' => $f->lista !== '' ? $f->lista : $publica . ' (la de Configuraciones)',
				'bodega' => $f->bodega !== '' ? $f->bodega : 'la de cada producto',
				'cambia_lista' => $this->bool($f->cambia_lista),
				'ocupada_por' => $f->ocupada_por !== null ? (string)$f->ocupada_por : '',
			);
		}
		return $res;
	}

	/** ¿Hay alguna caja vigente de la intranet para este sistema? Entonces al abrir hay que elegir una (12-11-2026). */
	public function hayCajasVigentes()
	{
		if (!$this->disponible()) return false;
		$this->sincronizarSesion();
		return (bool)$this->q('SELECT 1 FROM control_caja WHERE COALESCE(id_control_caja_web, 0) > 0 AND vigente IS NOT FALSE
			AND (id_software IS NULL OR id_software = ?) LIMIT 1', array((int)RESTO_ID_SOFTWARE))->row();
	}

	/** Valida la caja elegida al abrir: vigente, de este sistema y libre. */
	public function validarParaAbrir($id, $idUsuario)
	{
		foreach ($this->paraAbrir($idUsuario) as $c) {
			if ($c['id'] !== (int)$id) continue;
			if ($c['ocupada_por'] !== '') throw new Exception('La ' . ($c['nombre'] ?: 'caja') . ' ya está abierta por ' . $c['ocupada_por'] . '. Elige otra caja.', 409);
			return $c;
		}
		throw new Exception('Elige una caja vigente para abrir', 400);
	}

	// ---------------- Apoyo ----------------

	private function listas()
	{
		return $this->q('SELECT id_lprecio, nombre_lprecio FROM lprecio ORDER BY nombre_lprecio')->result_array();
	}

	private function bodegas()
	{
		$q = 'SELECT id_bodega, nombre_bodega FROM bodega';
		$c = $this->q("SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'bodega' AND column_name = 'vigente'")->row();
		if ($c) $q .= ' WHERE vigente IS NOT FALSE';
		return $this->q($q . ' ORDER BY nombre_bodega')->result_array();
	}

	/** Nombre de la lista pública de Configuraciones (la que usa una caja sin lista propia). */
	private function listaPublica()
	{
		$c = $this->q("SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'configuracion' AND column_name = 'id_lpreciopublico'")->row();
		$r = $c ? $this->q('SELECT l.nombre_lprecio FROM configuracion cf JOIN lprecio l ON l.id_lprecio = cf.id_lpreciopublico LIMIT 1')->row() : null;
		if (!$r) $r = $this->q('SELECT nombre_lprecio FROM lprecio ORDER BY id_lprecio LIMIT 1')->row();
		return $r ? (string)$r->nombre_lprecio : '';
	}

	/** Sucursal de la intranet de la caja: id_sucursal_intranet (migración 20261112090000) o, sin ella, id_sucursal */
	private function sucursalSql($alias)
	{
		$this->load->helper('sucursal_empresa');
		return control_caja_sucursal_sql($this->db(), $alias);
	}

	private function bool($v)
	{
		return $v === true || $v === 't' || $v === 1 || $v === '1';
	}

	private function db()
	{
		if ($this->db2 === null) {
			$this->load->helper('tenant');
			$this->db2 = get_tenant_db($this);
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
			log_message('error', 'Mcontrolcaja: ' . $e['message'] . ' | ' . $sql);
			throw new Exception($e['message'] ? trim(preg_replace('/^ERROR:\s*/', '', $e['message'])) : 'Error de base de datos', 500);
		}
		return $r;
	}
}
