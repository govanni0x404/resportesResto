<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Datos de factura de cada cliente (12-11-2026): direcciones (sucursal_cliente), giros (giro_cliente) y quienes solicitan
 * la factura (solicitante_cliente). Se administran en Clientes y se eligen en la ventana "Datos de la factura" de la Caja;
 * lo nuevo que se usa en la Caja se agrega a la ficha (recordar()). Migración 20261112100000.
 *
 * Largos del SII (formato DTE): DirRecep 70, GiroRecep 40, CmnaRecep 20, CiudadRecep 20, RUTSolicita. Se validan aquí
 * para que lo guardado sea exactamente lo que se envía.
 */
class Mclientefactura extends CI_Model
{
	const LARGO_DIRECCION = 70;
	const LARGO_GIRO = 40;
	const LARGO_CIUDAD = 20;
	const LARGO_NOMBRE = 80;

	private $db2 = null;
	private $hay = null;
	private $sinSecuencia = array();

	public function disponible()
	{
		if ($this->hay === null) {
			$r = $this->q("SELECT count(*) AS n FROM information_schema.columns WHERE table_schema = 'public'
				AND ((table_name = 'sucursal_cliente' AND column_name IN ('id_cliente', 'nombre_sucursal_cliente', 'ciudad', 'vigente'))
					OR (table_name = 'giro_cliente' AND column_name IN ('id_cliente', 'nombre_giro_cliente'))
					OR (table_name = 'solicitante_cliente' AND column_name IN ('id_cliente', 'rut', 'dv', 'nombre')))")->row();
			$this->hay = $r && (int)$r->n === 10;
		}
		return $this->hay;
	}

	private function exigir()
	{
		if (!$this->disponible()) {
			throw new Exception('Falta actualizar la base de datos para las direcciones, giros y solicitantes. Vuelve a ingresar al sistema en unos minutos.', 409);
		}
	}

	/** Direcciones, giros y solicitantes vigentes del cliente. */
	public function listas($idCliente)
	{
		$res = array('disponible' => $this->disponible(), 'direcciones' => array(), 'giros' => array(), 'solicitantes' => array());
		if (!$res['disponible']) return $res;
		$idCliente = (int)$idCliente;
		foreach ($this->q("SELECT s.id_sucursal_cliente AS id, trim(s.nombre_sucursal_cliente) AS direccion, COALESCE(s.id_comuna, 0) AS id_comuna,
				COALESCE(co.nombre_comuna, '') AS comuna, COALESCE(s.ciudad, '') AS ciudad, COALESCE(s.referencia_direccion, '') AS referencia
			FROM sucursal_cliente s LEFT JOIN comuna co ON co.id_comuna = s.id_comuna
			WHERE s.id_cliente = ? AND s.vigente IS NOT FALSE ORDER BY s.id_sucursal_cliente", array($idCliente))->result() as $f) {
			$res['direcciones'][] = array('id' => (int)$f->id, 'direccion' => (string)$f->direccion, 'id_comuna' => (int)$f->id_comuna,
				'comuna' => (string)$f->comuna, 'ciudad' => (string)$f->ciudad, 'referencia' => (string)$f->referencia);
		}
		foreach ($this->q("SELECT id_giro_cliente AS id, trim(nombre_giro_cliente) AS giro, trim(COALESCE(codigo_sii, '')) AS codigo
			FROM giro_cliente WHERE id_cliente = ? ORDER BY id_giro_cliente", array($idCliente))->result() as $f) {
			$res['giros'][] = array('id' => (int)$f->id, 'giro' => (string)$f->giro, 'codigo_sii' => (string)$f->codigo);
		}
		foreach ($this->q("SELECT id_solicitante_cliente AS id, rut, dv, nombre, COALESCE(cargo, '') AS cargo, COALESCE(mail, '') AS mail
			FROM solicitante_cliente WHERE id_cliente = ? AND vigente IS NOT FALSE ORDER BY nombre", array($idCliente))->result() as $f) {
			$res['solicitantes'][] = array('id' => (int)$f->id, 'rut' => (int)$f->rut, 'dv' => strtoupper((string)$f->dv), 'nombre' => (string)$f->nombre,
				'cargo' => (string)$f->cargo, 'mail' => (string)$f->mail);
		}
		return $res;
	}

	// ---------------- Direcciones ----------------

	public function guardarDireccion($idCliente, $id, $d)
	{
		$this->exigir();
		$this->exigirCliente($idCliente);
		$direccion = $this->texto($d['direccion'] ?? '');
		$idComuna = (int)($d['id_comuna'] ?? 0);
		if ($direccion === '') throw new Exception('Indica la dirección', 400);
		if (mb_strlen($direccion) > self::LARGO_DIRECCION) throw new Exception('La dirección puede tener hasta ' . self::LARGO_DIRECCION . ' caracteres (máximo del SII)', 400);
		if (!$idComuna || !$this->q('SELECT 1 FROM comuna WHERE id_comuna = ?', array($idComuna))->row()) throw new Exception('Elige la comuna', 400);
		$ciudad = $this->texto($d['ciudad'] ?? '');
		if (mb_strlen($ciudad) > self::LARGO_CIUDAD) throw new Exception('La ciudad puede tener hasta ' . self::LARGO_CIUDAD . ' caracteres (máximo del SII)', 400);
		$fila = array('nombre_sucursal_cliente' => $direccion, 'id_comuna' => $idComuna, 'ciudad' => $ciudad,
			'referencia_direccion' => mb_substr($this->texto($d['referencia'] ?? ''), 0, 80));
		$repetida = $this->q('SELECT id_sucursal_cliente FROM sucursal_cliente WHERE id_cliente = ? AND vigente IS NOT FALSE AND id_comuna = ?
			AND upper(trim(nombre_sucursal_cliente)) = upper(?) AND id_sucursal_cliente <> ? LIMIT 1', array((int)$idCliente, $idComuna, $direccion, (int)$id))->row();
		if ($repetida) throw new Exception('El cliente ya tiene esa dirección', 409);
		if ((int)$id > 0) {
			$this->propia('sucursal_cliente', 'id_sucursal_cliente', $id, $idCliente);
			$this->actualizar('sucursal_cliente', 'id_sucursal_cliente', $id, $fila);
			return array('ok' => true, 'id' => (int)$id);
		}
		$fila['id_cliente'] = (int)$idCliente;
		$fila['vigente'] = 't';
		return array('ok' => true, 'id' => $this->insertar('sucursal_cliente', 'id_sucursal_cliente', $fila));
	}

	/** Se deja no vigente (como mipdv_web): las facturas ya emitidas guardan su propia copia de la dirección. */
	public function quitarDireccion($idCliente, $id)
	{
		$this->exigir();
		$this->propia('sucursal_cliente', 'id_sucursal_cliente', $id, $idCliente);
		$this->q('UPDATE sucursal_cliente SET vigente = false WHERE id_sucursal_cliente = ?', array((int)$id));
		return array('ok' => true);
	}

	// ---------------- Giros ----------------

	public function guardarGiro($idCliente, $id, $d)
	{
		$this->exigir();
		$this->exigirCliente($idCliente);
		$giro = $this->texto($d['giro'] ?? '');
		if ($giro === '') throw new Exception('Indica el giro', 400);
		if (mb_strlen($giro) > self::LARGO_GIRO) throw new Exception('El giro puede tener hasta ' . self::LARGO_GIRO . ' caracteres (máximo del SII para el receptor)', 400);
		$codigo = preg_replace('/\D/', '', (string)($d['codigo_sii'] ?? ''));
		if (strlen($codigo) > 6) throw new Exception('El código de actividad económica tiene hasta 6 dígitos', 400);
		$repetido = $this->q('SELECT 1 FROM giro_cliente WHERE id_cliente = ? AND upper(trim(nombre_giro_cliente)) = upper(?) AND id_giro_cliente <> ? LIMIT 1',
			array((int)$idCliente, $giro, (int)$id))->row();
		if ($repetido) throw new Exception('El cliente ya tiene ese giro', 409);
		$fila = array('nombre_giro_cliente' => $giro, 'codigo_sii' => $codigo);
		if ((int)$id > 0) {
			$this->propia('giro_cliente', 'id_giro_cliente', $id, $idCliente);
			$this->actualizar('giro_cliente', 'id_giro_cliente', $id, $fila);
			return array('ok' => true, 'id' => (int)$id);
		}
		$fila['id_cliente'] = (int)$idCliente;
		return array('ok' => true, 'id' => $this->insertar('giro_cliente', 'id_giro_cliente', $fila));
	}

	/** Se borra (como mipdv_web): las facturas ya emitidas guardan su propia copia del giro. */
	public function quitarGiro($idCliente, $id)
	{
		$this->exigir();
		$this->propia('giro_cliente', 'id_giro_cliente', $id, $idCliente);
		$this->q('DELETE FROM giro_cliente WHERE id_giro_cliente = ?', array((int)$id));
		return array('ok' => true);
	}

	// ---------------- Solicitantes ----------------

	public function guardarSolicitante($idCliente, $id, $d)
	{
		$this->exigir();
		$this->exigirCliente($idCliente);
		list($rut, $dv) = $this->rut($d['rut'] ?? '');
		$nombre = $this->texto($d['nombre'] ?? '');
		if ($nombre === '') throw new Exception('Indica el nombre de quien solicita', 400);
		if (mb_strlen($nombre) > self::LARGO_NOMBRE) throw new Exception('El nombre puede tener hasta ' . self::LARGO_NOMBRE . ' caracteres', 400);
		$mail = trim((string)($d['mail'] ?? ''));
		if ($mail !== '' && !filter_var($mail, FILTER_VALIDATE_EMAIL)) throw new Exception('El correo no es válido', 400);
		$repetido = $this->q('SELECT 1 FROM solicitante_cliente WHERE id_cliente = ? AND rut = ? AND vigente IS NOT FALSE AND id_solicitante_cliente <> ? LIMIT 1',
			array((int)$idCliente, $rut, (int)$id))->row();
		if ($repetido) throw new Exception('Ese RUT ya está entre los solicitantes del cliente', 409);
		$fila = array('rut' => $rut, 'dv' => $dv, 'nombre' => $nombre, 'cargo' => mb_substr($this->texto($d['cargo'] ?? ''), 0, 60), 'mail' => mb_substr($mail, 0, 100));
		if ((int)$id > 0) {
			$this->propia('solicitante_cliente', 'id_solicitante_cliente', $id, $idCliente);
			$this->actualizar('solicitante_cliente', 'id_solicitante_cliente', $id, $fila);
			return array('ok' => true, 'id' => (int)$id);
		}
		$fila['id_cliente'] = (int)$idCliente;
		$fila['vigente'] = 't';
		return array('ok' => true, 'id' => $this->insertar('solicitante_cliente', 'id_solicitante_cliente', $fila));
	}

	public function quitarSolicitante($idCliente, $id)
	{
		$this->exigir();
		$this->propia('solicitante_cliente', 'id_solicitante_cliente', $id, $idCliente);
		$this->q('UPDATE solicitante_cliente SET vigente = false WHERE id_solicitante_cliente = ?', array((int)$id));
		return array('ok' => true);
	}

	// ---------------- Desde la Caja ----------------

	/**
	 * Lo usado en una factura que aún no está en la ficha del cliente se agrega (dirección + comuna, giro, solicitante).
	 * Lo de la ficha principal del cliente (cliente.giro / cliente.direccion) no se repite en las listas.
	 * @param array $fac datos ya validados por Mcaja::datosFactura()
	 */
	public function recordar($idCliente, $fac)
	{
		if (!$this->disponible() || (int)$idCliente <= 0) return;
		$c = $this->q('SELECT upper(trim(COALESCE(giro, \'\'))) AS giro, upper(trim(COALESCE(direccion, \'\'))) AS direccion, COALESCE(id_comuna, 0) AS id_comuna
			FROM cliente WHERE id_cliente = ?', array((int)$idCliente))->row();
		if (!$c) return;
		$dir = $this->texto($fac['direccion']);
		if ($dir !== '' && !($c->direccion === mb_strtoupper($dir) && (int)$c->id_comuna === (int)$fac['id_comuna'])
			&& !$this->q('SELECT 1 FROM sucursal_cliente WHERE id_cliente = ? AND vigente IS NOT FALSE AND id_comuna = ? AND upper(trim(nombre_sucursal_cliente)) = upper(?)',
				array((int)$idCliente, (int)$fac['id_comuna'], $dir))->row()) {
			$this->insertar('sucursal_cliente', 'id_sucursal_cliente', array('id_cliente' => (int)$idCliente, 'nombre_sucursal_cliente' => mb_substr($dir, 0, 150),
				'id_comuna' => (int)$fac['id_comuna'], 'ciudad' => mb_substr($this->texto($fac['ciudad']), 0, self::LARGO_CIUDAD), 'vigente' => 't'));
		}
		$giro = $this->texto($fac['giro']);
		if ($giro !== '' && $c->giro !== mb_strtoupper($giro)
			&& !$this->q('SELECT 1 FROM giro_cliente WHERE id_cliente = ? AND upper(trim(nombre_giro_cliente)) = upper(?)', array((int)$idCliente, $giro))->row()) {
			$this->insertar('giro_cliente', 'id_giro_cliente', array('id_cliente' => (int)$idCliente, 'nombre_giro_cliente' => mb_substr($giro, 0, 200), 'codigo_sii' => ''));
		}
		if ((int)$fac['rut_solicita'] > 0
			&& !$this->q('SELECT 1 FROM solicitante_cliente WHERE id_cliente = ? AND rut = ? AND vigente IS NOT FALSE', array((int)$idCliente, (int)$fac['rut_solicita']))->row()) {
			$this->insertar('solicitante_cliente', 'id_solicitante_cliente', array('id_cliente' => (int)$idCliente, 'rut' => (int)$fac['rut_solicita'],
				'dv' => strtoupper((string)$fac['dv_solicita']), 'nombre' => mb_substr($this->texto($fac['nombre_solicita']), 0, 80), 'vigente' => 't'));
		}
	}

	// ---------------- Apoyo ----------------

	private function exigirCliente($idCliente)
	{
		if ((int)$idCliente <= 0 || !$this->q('SELECT 1 FROM cliente WHERE id_cliente = ?', array((int)$idCliente))->row()) {
			throw new Exception('Primero graba el cliente', 400);
		}
	}

	/** El registro es del cliente indicado (no se edita el de otro cliente). */
	private function propia($tabla, $pk, $id, $idCliente)
	{
		if (!$this->q("SELECT 1 FROM $tabla WHERE $pk = ? AND id_cliente = ?", array((int)$id, (int)$idCliente))->row()) {
			throw new Exception('El registro no existe', 404);
		}
	}

	/** RUT con dígito verificador válido -> [número, dv] */
	private function rut($texto)
	{
		$limpio = strtoupper(preg_replace('/[^0-9kK]/', '', (string)$texto));
		if (strlen($limpio) < 2) throw new Exception('Indica el RUT de quien solicita (con dígito verificador)', 400);
		$cuerpo = (int)substr($limpio, 0, -1);
		$dv = substr($limpio, -1);
		$s = 1;
		for ($m = 0, $r = $cuerpo; $r; $r = (int)($r / 10)) $s = ($s + $r % 10 * (9 - $m++ % 6)) % 11;
		if ($cuerpo <= 0 || ($s ? (string)($s - 1) : 'K') !== $dv) throw new Exception('El RUT de quien solicita no es válido', 400);
		return array($cuerpo, $dv);
	}

	/** Texto en una línea, sin espacios repetidos, en mayúsculas (como la ficha del cliente) */
	private function texto($v)
	{
		return mb_strtoupper(trim(preg_replace('/\s+/u', ' ', (string)$v)), 'UTF-8');
	}

	/** INSERT con la llave por secuencia; una tabla del POS sin secuencia se numera aquí (con la tabla bloqueada). */
	private function insertar($tabla, $pk, $fila)
	{
		if (!isset($this->sinSecuencia[$tabla])) {
			$d = $this->q("SELECT column_default FROM information_schema.columns WHERE table_schema = 'public' AND table_name = ? AND column_name = ?",
				array($tabla, $pk))->row();
			$this->sinSecuencia[$tabla] = $d && $d->column_default === null;
		}
		$cols = array_keys($fila);
		if ($this->sinSecuencia[$tabla]) {
			$db = $this->db();
			$db->trans_begin();
			try {
				$this->q("LOCK TABLE $tabla IN SHARE ROW EXCLUSIVE MODE");
				$r = $this->q("INSERT INTO $tabla ($pk, " . implode(', ', $cols) . ") SELECT COALESCE(max($pk), 0) + 1, "
					. implode(', ', array_fill(0, count($cols), '?')) . " FROM $tabla RETURNING $pk", array_values($fila))->row();
				$db->trans_commit();
			} catch (Exception $e) {
				$db->trans_rollback();
				throw $e;
			}
		} else {
			$r = $this->q("INSERT INTO $tabla (" . implode(', ', $cols) . ") VALUES (" . implode(', ', array_fill(0, count($cols), '?')) . ") RETURNING $pk",
				array_values($fila))->row();
		}
		return (int)$r->$pk;
	}

	private function actualizar($tabla, $pk, $id, $fila)
	{
		$sets = array();
		foreach (array_keys($fila) as $c) $sets[] = "$c = ?";
		$this->q("UPDATE $tabla SET " . implode(', ', $sets) . " WHERE $pk = ?", array_merge(array_values($fila), array((int)$id)));
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
			log_message('error', 'Mclientefactura: ' . $e['message'] . ' | ' . $sql);
			throw new Exception($e['message'] ? trim(preg_replace('/^ERROR:\s*/', '', $e['message'])) : 'Error de base de datos', 500);
		}
		return $r;
	}
}
