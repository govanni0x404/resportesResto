<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Pago electrónico (19-11-2026, migración 20261119100000): la Caja cobra con tarjeta en la máquina POS por el programa
 * local ApiPagoElectronico (proyecto Integracion_Maquinas_Pos, un .exe en el equipo de la caja, http://localhost:5005).
 *
 * - Mantención > Pago electrónico (tabla pago_electronico_web): cada máquina se elige de una lista de modelos (MODELOS;
 *   del modelo sale el tipo que va en la petición) con los datos de la petición: id de sucursal, nombre de caja,
 *   terminal ID, API key (Mercado Pago y TUU) y tiempo de espera. Se cobran en ella las tarjetas de débito y crédito
 *   (formasTarjeta, por el nombre de la forma de pago). El
 *   programa compara esos datos con los de su panel: si alguno no es igual, responde cuál y la Caja lo muestra.
 * - Configuraciones > General: el usuario y la clave de la API del programa (los mismos para todas las cajas).
 * - Herramientas > Control de cajas (control_caja.id_pago_electronico_web): la máquina que usa cada caja (sin máquina, la
 *   caja cobra como antes).
 * - Cada cobro queda en pago_electronico_web_tx con la venta (para registrarla cuando se aprueba). Los que no terminaron
 *   (enviando, pendiente o aprobado sin registrar) se muestran arriba en la Caja: cancelar, volver a enviar o aprobar.
 *
 * El navegador del cajero es quien llama al programa (está en su equipo, el servidor no lo alcanza): por eso la Caja
 * recibe el usuario y la clave de la API.
 */
class Mpagoelectronico extends CI_Model
{
	/** type de ApiPagoElectronico => nombre visible */
	const TIPOS = array(
		'GETNET' => 'Getnet',
		'TRANSBANK' => 'Transbank',
		'MERCADOPAGO' => 'Mercado Pago',
		'HAULMER' => 'TUU / Haulmer',
	);
	/**
	 * Máquinas que se usan en Chile y que el programa sabe cobrar (19-11-2026). clave => [type, nombre visible]. La
	 * clave queda guardada en pago_electronico_web.modelo: no cambiarla; para otra máquina, agregar una fila.
	 */
	const MODELOS = array(
		'TRANSBANK_DESK3500' => array('TRANSBANK', 'Transbank POS Integrado · Ingenico Desk 3500'),
		'TRANSBANK_VX520' => array('TRANSBANK', 'Transbank POS Integrado · Verifone VX520 / VX520C'),
		'GETNET_A920PRO' => array('GETNET', 'Getnet · PAX A920 Pro'),
		'MERCADOPAGO_SMART' => array('MERCADOPAGO', 'Mercado Pago · Point Smart'),
		'MERCADOPAGO_SMART2' => array('MERCADOPAGO', 'Mercado Pago · Point Smart 2'),
		'MERCADOPAGO_PRO2' => array('MERCADOPAGO', 'Mercado Pago · Point Pro 2'),
		'TUU_PRO2' => array('HAULMER', 'TUU (Haulmer) · PRO2 / Kozen'),
	);
	const URL_PROGRAMA = 'http://localhost:5005';
	/** Cobros que no terminaron: se muestran arriba en la Caja */
	const SIN_TERMINAR = array('enviando', 'pendiente', 'aprobado');
	const ESTADOS = array('enviando', 'pendiente', 'aprobado', 'rechazado', 'cancelado', 'error', 'registrada');

	private $db2 = null;
	private $hay = null;
	private $hayCaja = null;

	/** ¿Están las tablas de la migración 20261119100000? */
	public function disponible()
	{
		if ($this->hay === null) {
			$r = $this->q("SELECT count(*) AS n FROM information_schema.columns WHERE table_schema = 'public'
				AND ((table_name = 'pago_electronico_web' AND column_name = 'modelo') OR (table_name = 'pago_electronico_web_tx' AND column_name = 'venta'))")->row();
			$this->hay = $r && (int)$r->n === 2;
		}
		return $this->hay;
	}

	/** ¿Tiene control_caja la columna con la máquina de cada caja? */
	public function conControlCaja()
	{
		if ($this->hayCaja === null) {
			$this->hayCaja = $this->disponible() && (bool)$this->q("SELECT 1 FROM information_schema.columns WHERE table_schema = 'public'
				AND table_name = 'control_caja' AND column_name = 'id_pago_electronico_web'")->row();
		}
		return $this->hayCaja;
	}

	private function exigir()
	{
		if (!$this->disponible()) {
			throw new Exception('El pago electrónico aún no está disponible: falta actualizar la base de datos. Vuelve a ingresar al sistema en unos minutos.', 409);
		}
	}

	/** Usuario y clave de la API del programa (Configuraciones > General). Vacíos si no están configurados. */
	public function credenciales()
	{
		$res = array('usuario_api' => '', 'clave_api' => '');
		$hay = $this->q("SELECT count(*) AS n FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'configuracion'
			AND column_name IN ('pago_electronico_usuario_api', 'pago_electronico_clave_api')")->row();
		if (!$hay || (int)$hay->n !== 2) return $res;
		$f = $this->q('SELECT COALESCE(pago_electronico_usuario_api, \'\') AS u, COALESCE(pago_electronico_clave_api, \'\') AS c FROM configuracion LIMIT 1')->row();
		if ($f) $res = array('usuario_api' => trim((string)$f->u), 'clave_api' => (string)$f->c);
		return $res;
	}

	// ---------------- Mantención > Pago electrónico ----------------

	public function listar()
	{
		$this->exigir();
		$formas = $this->formasPago();
		$nombres = array();
		foreach ($formas as $f) $nombres[$f['id_fpago']] = $f['nombre'];
		$maquinas = array();
		$cajas = $this->conControlCaja()
			? '(SELECT count(*) FROM control_caja cc WHERE cc.id_pago_electronico_web = pe.id_pago_electronico_web)' : '0';
		foreach ($this->q("SELECT pe.*, $cajas AS cajas
				FROM pago_electronico_web pe ORDER BY pe.vigente DESC, pe.nombre, pe.nombre_caja_pos, pe.id_pago_electronico_web")->result() as $f) {
			$m = $this->fila($f, false);
			$m['cajas'] = (int)$f->cajas;
			$m['formas_nombres'] = array_values(array_filter(array_map(function ($id) use ($nombres) { return isset($nombres[$id]) ? $nombres[$id] : null; }, $m['formas_pago'])));
			$maquinas[] = $m;
		}
		$modelos = array();
		foreach (self::MODELOS as $k => $v) $modelos[] = array('id' => $k, 'tipo' => $v[0], 'nombre' => $v[1]);
		// El usuario y la clave van a esta pantalla (módulo sensible) solo para "Probar conexión"
		return array('maquinas' => $maquinas, 'modelos' => $modelos, 'tarjetas' => $this->formasTarjeta(),
			'credenciales' => $this->credenciales());
	}

	/** Crea (id 0) o edita una máquina. La API key no se devuelve a esta pantalla: vacía al editar = se mantiene. */
	public function guardar($d)
	{
		$this->exigir();
		$id = (int)($d['id'] ?? 0);
		$modelo = strtoupper(trim((string)($d['modelo'] ?? '')));
		if (!isset(self::MODELOS[$modelo])) throw new Exception('Elige la máquina', 400);
		list($tipo, $nombre) = self::MODELOS[$modelo];
		$idSucursal = $this->texto($d, 'id_sucursal_pos', 30);
		if ($idSucursal === '') throw new Exception('Escribe el ID de sucursal (el mismo ID_SUCURSAL del panel del programa)', 400);
		$caja = strtoupper($this->texto($d, 'nombre_caja_pos', 60));
		if ($caja === '') throw new Exception('Escribe el nombre de la caja (el mismo NOMBRE_CAJA del panel del programa)', 400);
		$terminal = $this->texto($d, 'terminal_id', 100);
		if ($terminal === '') throw new Exception('Escribe el terminal ID (el mismo del panel del programa)', 400);
		$timeout = (int)($d['timeout_seg'] ?? 120);
		if ($timeout < 30 || $timeout > 300) throw new Exception('El tiempo de espera debe estar entre 30 y 300 segundos', 400);
		// Las formas de pago no se eligen: son las de tarjeta (formasTarjeta). La columna queda como registro.
		$formas = $this->formasTarjeta();
		if (!$formas) {
			throw new Exception('No hay formas de pago de tarjeta: crea "TARJETA DEBITO" y "TARJETA CREDITO" en Mantención > Formas De Pago', 400);
		}
		$apiKey = trim((string)($d['api_key'] ?? ''));
		if (strlen($apiKey) > 300) throw new Exception('La API key es demasiado larga', 400);
		$usaKey = in_array($tipo, array('MERCADOPAGO', 'HAULMER'), true);
		$vigente = !isset($d['vigente']) || ($d['vigente'] !== '0' && $d['vigente'] !== 'false' && $d['vigente'] !== false && $d['vigente'] !== 0);

		$valores = array('modelo' => $modelo, 'nombre' => $nombre, 'tipo' => $tipo,
			'id_sucursal_pos' => $idSucursal, 'nombre_caja_pos' => $caja, 'terminal_id' => $terminal, 'timeout_seg' => $timeout,
			'formas_pago' => implode(',', $formas), 'vigente' => $vigente ? 't' : 'f');
		if ($id > 0) {
			if (!$this->q('SELECT 1 FROM pago_electronico_web WHERE id_pago_electronico_web = ?', array($id))->row()) throw new Exception('La máquina no existe', 404);
			// Getnet y Transbank no usan API key: se borra si cambió a una de ellas
			if (!$usaKey) $valores['api_key'] = '';
			elseif ($apiKey !== '' || !empty($d['borrar_api_key'])) $valores['api_key'] = $apiKey;
			$sets = array();
			foreach ($valores as $c => $v) $sets[] = "$c = ?";
			$this->q('UPDATE pago_electronico_web SET ' . implode(', ', $sets) . ', fecha_modificacion = now() WHERE id_pago_electronico_web = ?',
				array_merge(array_values($valores), array($id)));
		} else {
			$valores['api_key'] = $usaKey ? $apiKey : '';
			$valores['url_servicio'] = self::URL_PROGRAMA;
			$r = $this->q('INSERT INTO pago_electronico_web (' . implode(', ', array_keys($valores)) . ') VALUES (' . implode(', ', array_fill(0, count($valores), '?')) . ')
				RETURNING id_pago_electronico_web', array_values($valores))->row();
			$id = (int)$r->id_pago_electronico_web;
		}
		return array('ok' => true, 'id' => $id);
	}

	/** Para el selector de Control de cajas (también las no vigentes, para mostrar la elegida). */
	public function opciones()
	{
		if (!$this->conControlCaja()) return array();
		$res = array();
		foreach ($this->q('SELECT id_pago_electronico_web, nombre, tipo, nombre_caja_pos, terminal_id, vigente FROM pago_electronico_web
				ORDER BY vigente DESC, nombre, nombre_caja_pos')->result() as $f) {
			$res[] = array('id' => (int)$f->id_pago_electronico_web, 'nombre' => $f->nombre, 'caja' => $f->nombre_caja_pos, 'terminal' => $f->terminal_id,
				'tipo' => isset(self::TIPOS[$f->tipo]) ? self::TIPOS[$f->tipo] : $f->tipo, 'vigente' => $this->bool($f->vigente));
		}
		return $res;
	}

	public function existe($id)
	{
		return $this->conControlCaja() && (bool)$this->q('SELECT 1 FROM pago_electronico_web WHERE id_pago_electronico_web = ?', array((int)$id))->row();
	}

	// ---------------- Caja ----------------

	/**
	 * La máquina de una caja de Control de cajas, con lo que el navegador necesita para llamar al programa (dirección,
	 * usuario y clave de Configuraciones, y los datos de la petición). null: la caja no usa pago electrónico (o la
	 * máquina no está vigente).
	 */
	public function paraCaja($idControlCaja)
	{
		if ((int)$idControlCaja <= 0 || !$this->conControlCaja()) return null;
		$f = $this->q('SELECT pe.* FROM control_caja cc JOIN pago_electronico_web pe ON pe.id_pago_electronico_web = cc.id_pago_electronico_web
			WHERE cc.id_control_caja = ? AND pe.vigente IS TRUE', array((int)$idControlCaja))->row();
		return $f ? $this->fila($f, true) + $this->credenciales() : null;
	}

	/** Cobros sin terminar de la caja (los muestra la barra de arriba de la Caja). */
	public function pendientes($idControlCaja)
	{
		if ((int)$idControlCaja <= 0 || !$this->disponible()) return array();
		$res = array();
		foreach ($this->q("SELECT t.*, to_char(t.fecha_creacion, 'DD/MM/YYYY HH24:MI') AS fecha, COALESCE(u.nombre_usuario, '') AS usuario
				FROM pago_electronico_web_tx t LEFT JOIN usuario u ON u.id_usuario = t.id_usuario
				WHERE t.id_control_caja = ? AND t.estado IN ('enviando', 'pendiente', 'aprobado')
				ORDER BY t.id_pago_electronico_web_tx", array((int)$idControlCaja))->result() as $f) {
			$res[] = $this->filaTx($f);
		}
		return $res;
	}

	/** Registra el cobro antes de mandarlo a la máquina (así, si algo se corta, queda arriba en la Caja). */
	public function crearTx($caja, $idUsuario, $monto, $descripcion, $venta)
	{
		$this->exigir();
		$idControl = (int)($caja->id_control_caja ?? 0);
		$m = $this->paraCaja($idControl);
		if (!$m) throw new Exception('Esta caja no usa pago electrónico (Herramientas > Control de cajas)', 409);
		$monto = (int)$monto;
		if ($monto <= 0) throw new Exception('El monto a cobrar en la máquina debe ser mayor que cero', 400);
		if (!is_array($venta) || empty($venta['token'])) throw new Exception('Faltan los datos de la venta', 400);
		$txId = 'WEB-' . bin2hex(random_bytes(12));
		$this->q('INSERT INTO pago_electronico_web_tx (tx_id, id_pago_electronico_web, id_control_caja, id_caja, id_usuario, monto, estado, descripcion, venta)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', array($txId, $m['id'], $idControl, (int)$caja->id_caja, (int)$idUsuario, $monto, 'enviando',
			mb_substr(trim((string)$descripcion), 0, 150), json_encode($venta, JSON_UNESCAPED_UNICODE)));
		return array('tx_id' => $txId, 'maquina' => $m);
	}

	/**
	 * Resultado del cobro (lo informa la Caja con lo que respondió el programa, o lo decide el cajero en la barra de
	 * pendientes). Una venta ya registrada no cambia.
	 */
	public function actualizarTx($caja, $txId, $estado, $mensaje = '', $respuesta = null, $codigo = '')
	{
		$tx = $this->txDeCaja($caja, $txId);
		if (!in_array($estado, self::ESTADOS, true) || $estado === 'registrada') throw new Exception('Estado inválido', 400);
		if ($tx['estado'] === 'registrada') throw new Exception('Esa venta ya quedó registrada', 409);
		$this->q('UPDATE pago_electronico_web_tx SET estado = ?, mensaje = ?, respuesta = COALESCE(?::jsonb, respuesta),
				codigo_autorizacion = COALESCE(NULLIF(?, \'\'), codigo_autorizacion), fecha_actualizacion = now()
			WHERE tx_id = ?', array($estado, mb_substr((string)$mensaje, 0, 400),
			$respuesta !== null ? json_encode($respuesta, JSON_UNESCAPED_UNICODE) : null, mb_substr((string)$codigo, 0, 80), $txId));
		return array('ok' => true);
	}

	/** La venta del cobro quedó registrada (Caja::vender con venta.pago_electronico_web_tx). */
	public function marcarRegistrada($caja, $txId, $idVenta)
	{
		$this->txDeCaja($caja, $txId);
		$this->q("UPDATE pago_electronico_web_tx SET estado = 'registrada', id_venta = ?, fecha_actualizacion = now() WHERE tx_id = ?",
			array((int)$idVenta, $txId));
	}

	/** El cobro con su venta guardada (para registrarla desde la barra de pendientes). */
	public function txDeCaja($caja, $txId)
	{
		$this->exigir();
		$txId = (string)$txId;
		if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/', $txId)) throw new Exception('Cobro inválido', 400);
		$f = $this->q("SELECT t.*, to_char(t.fecha_creacion, 'DD/MM/YYYY HH24:MI') AS fecha, '' AS usuario FROM pago_electronico_web_tx t WHERE t.tx_id = ?",
			array($txId))->row();
		if (!$f) throw new Exception('El cobro no existe', 404);
		// Solo la caja donde se hizo el cobro lo resuelve
		if ((int)$f->id_control_caja !== (int)($caja->id_control_caja ?? 0)) throw new Exception('Ese cobro es de otra caja', 403);
		return $this->filaTx($f, true);
	}

	// ---------------- Apoyo ----------------

	private function fila($f, $conSecretos)
	{
		$m = array(
			'id' => (int)$f->id_pago_electronico_web, 'nombre' => $f->nombre, 'tipo' => $f->tipo,
			'tipo_nombre' => isset(self::TIPOS[$f->tipo]) ? self::TIPOS[$f->tipo] : $f->tipo,
			'modelo' => (string)$f->modelo, 'url_servicio' => (string)$f->url_servicio !== '' ? $f->url_servicio : self::URL_PROGRAMA,
			'id_sucursal_pos' => $f->id_sucursal_pos, 'nombre_caja_pos' => $f->nombre_caja_pos, 'terminal_id' => $f->terminal_id,
			'timeout_seg' => (int)$f->timeout_seg,
			'formas_pago' => $this->formasTarjeta(),   // siempre las de tarjeta (también las creadas después)
			'vigente' => $this->bool($f->vigente),
			'tiene_api_key' => (string)$f->api_key !== '',
		);
		if ($conSecretos) $m['api_key'] = (string)$f->api_key;
		return $m;
	}

	private function filaTx($f, $conVenta = false)
	{
		// El id que usó el programa (Transbank no acepta el nuestro y crea otro): para consultar, cancelar o resolver
		$resp = json_decode((string)$f->respuesta, true);
		$t = array(
			'tx_programa' => is_array($resp) && !empty($resp['transaction_id']) ? (string)$resp['transaction_id'] : $f->tx_id,
			'tx_id' => $f->tx_id, 'monto' => (int)$f->monto, 'estado' => $f->estado, 'descripcion' => (string)$f->descripcion,
			'mensaje' => (string)$f->mensaje, 'codigo_autorizacion' => (string)$f->codigo_autorizacion,
			'fecha' => $f->fecha, 'usuario' => $f->usuario, 'id_pago_electronico_web' => (int)$f->id_pago_electronico_web,
		);
		if ($conVenta) $t['venta'] = json_decode((string)$f->venta, true);
		return $t;
	}

	public function formasPago()
	{
		$res = array();
		foreach ($this->q('SELECT id_fpago, nombre_fpago, (escortesia IS TRUE) AS cortesia FROM fpago ORDER BY nombre_fpago')->result() as $f) {
			if ($this->bool($f->cortesia)) continue;
			$res[] = array('id_fpago' => (int)$f->id_fpago, 'nombre' => $f->nombre_fpago);
		}
		return $res;
	}

	/**
	 * 19-11-2026 (pedido del usuario): el pago electrónico es solo para tarjetas de débito y crédito, y esas formas de
	 * pago se reconocen solas por el nombre: "TARJETA ..." o "...DEBITO...". El crédito a clientes (fiado, "CREDITO DE
	 * CONFIANZA" o la forma de crédito de Configuraciones) no es tarjeta. Las cortesías ya vienen fuera (formasPago).
	 * @return int[] id_fpago
	 */
	private $tarjetas = null;
	public function formasTarjeta()
	{
		if ($this->tarjetas !== null) return $this->tarjetas;
		$fiado = 0;
		if ($this->q("SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'configuracion'
				AND column_name = 'id_fpago_creditodirecto'")->row()) {
			$c = $this->q('SELECT COALESCE(id_fpago_creditodirecto, 0) AS id FROM configuracion LIMIT 1')->row();
			$fiado = $c ? (int)$c->id : 0;
		}
		$res = array();
		foreach ($this->formasPago() as $f) {
			$n = strtoupper(strtr($f['nombre'], array('É' => 'E', 'é' => 'E', 'Í' => 'I', 'í' => 'I')));
			if ($f['id_fpago'] === $fiado || strpos($n, 'CONFIANZA') !== false) continue;
			if (strpos($n, 'TARJETA') !== false || strpos($n, 'DEBITO') !== false) $res[] = $f['id_fpago'];
		}
		return $this->tarjetas = $res;
	}

	private function texto($d, $campo, $largo)
	{
		return mb_substr(trim(preg_replace('/[\r\n\t]+/', ' ', (string)($d[$campo] ?? ''))), 0, $largo);
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
			log_message('error', 'Mpagoelectronico: ' . $e['message'] . ' | ' . $sql);
			throw new Exception($e['message'] ? trim(preg_replace('/^ERROR:\s*/', '', $e['message'])) : 'Error de base de datos', 500);
		}
		return $r;
	}
}
