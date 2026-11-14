<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Configuraciones > Datos de la Empresa (11-11-2026): las empresas de esta base y sus sucursales, con los datos de la intranet.
 *
 * - Las empresas las registran a mano los técnicos de Datamaule en la tabla empresa. Aquí no se agregan ni se borran.
 * - Por cada empresa con RUT se consulta la intranet (validarCliente con su RUT, ConexionesTenant::consultarCliente):
 *   razón social, nombre de fantasía, giro, dirección, comuna, teléfono y correo. Lo que la intranet trae (no vacío)
 *   reemplaza lo de la base y ya no se edita aquí (empresa.campos_intranet); el código de actividad económica y lo que la
 *   intranet no trae se siguen editando aquí.
 * - Sucursales (listasucursales): solo las de esta base de datos (con_sucursal); las que no informan base se incluyen.
 *   La intranet manda: nombre, dirección, comuna, teléfono, casa matriz y código SII. Aquí solo se completa el código SII
 *   (si la intranet no lo trae) y la comuna (si el nombre no calza con la tabla comuna).
 * - Las cajas autorizadas de esas sucursales se pasan a Control de cajas (Mcontrolcaja::sincronizar).
 * - Sin la migración 20261111200000 (disponible() = false) la pantalla avisa y todo sigue como antes.
 */
class Mempresas extends CI_Model
{
	/** campo de la tabla empresa => dato de validarCliente */
	const CAMPOS_INTRANET = array(
		'razon_social' => 'nombre',
		'nombre_fantasia' => 'nombre_fantasia',
		'giro' => 'giro',
		'direccion_empresa' => 'direccion_particular',
		'telefono' => 'telefono_cliente',
		'mail' => 'mail_cliente',
	);
	/** Campos de la empresa que se editan en la ventana (los que la intranet trajo quedan bloqueados) */
	const CAMPOS_EDITABLES = array('rut_empresa', 'dv_empresa', 'razon_social', 'nombre_fantasia', 'codigo_actividad_economica',
		'giro', 'direccion_empresa', 'id_comuna', 'mail', 'telefono');

	private $db2 = null;
	private $hay = null;
	private $comunas = null;
	private $colsEmpresa = null;

	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');
	}

	public function disponible()
	{
		if ($this->hay === null) {
			$r = $this->q("SELECT count(*) AS n FROM information_schema.columns WHERE table_schema = 'public'
				AND ((table_name = 'empresa' AND column_name IN ('id_cliente_intranet', 'campos_intranet', 'comuna_intranet', 'estado_sincronizacion', 'fecha_sincronizacion'))
					OR (table_name = 'empresa_sucursal' AND column_name IN ('id_empresa', 'id_sucursal_intranet', 'codigo_sii_manual', 'id_comuna', 'vigente')))")->row();
			$this->hay = $r && (int)$r->n === 10;
		}
		return $this->hay;
	}

	private function exigir()
	{
		if (!$this->disponible()) {
			throw new Exception('Falta actualizar la base de datos para administrar empresas y sucursales. Vuelve a ingresar al sistema en unos minutos.', 409);
		}
	}

	// ---------------- Listado ----------------

	public function listar()
	{
		$this->exigir();
		$principal = $this->q('SELECT min(id_empresa) AS id FROM empresa')->row();
		$principal = $principal ? (int)$principal->id : 0;
		$hayCajas = (bool)$this->q("SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'control_caja'
			AND column_name = 'id_control_caja_web'")->row();
		$empresas = array();
		foreach ($this->q("SELECT e.*, COALESCE(co.nombre_comuna, '') AS nombre_comuna_local,
				to_char(e.fecha_sincronizacion, 'DD/MM/YYYY HH24:MI') AS sincronizada
			FROM empresa e LEFT JOIN comuna co ON co.id_comuna = e.id_comuna ORDER BY e.id_empresa")->result() as $e) {
			$bloqueados = $this->bloqueados($e);
			$sucursales = array();
			foreach ($this->q("SELECT es.*, COALESCE(co.nombre_comuna, '') AS comuna_local" . ($hayCajas ? ",
					(SELECT count(*) FROM control_caja cc WHERE " . $this->sucursalCaja('cc') . " = es.id_sucursal_intranet AND COALESCE(cc.id_control_caja_web, 0) > 0
						AND cc.vigente IS NOT FALSE) AS cajas" : ', 0 AS cajas') . "
				FROM empresa_sucursal es LEFT JOIN comuna co ON co.id_comuna = es.id_comuna AND es.id_comuna > 0
				WHERE es.id_empresa = ?
				ORDER BY (es.vigente IS NOT FALSE) DESC, (es.casa_matriz IS TRUE) DESC, es.nombre_sucursal", array((int)$e->id_empresa))->result() as $s) {
				$sucursales[] = array(
					'id' => (int)$s->id_empresa_sucursal,
					'id_sucursal_intranet' => (int)$s->id_sucursal_intranet,
					'nombre' => (string)$s->nombre_sucursal,
					'nombre_fantasia' => (string)$s->nombre_fantasia,
					'direccion' => (string)$s->direccion,
					'comuna' => (string)$s->comuna,
					'id_comuna' => (int)$s->id_comuna,
					'comuna_local' => (string)$s->comuna_local,
					'comuna_calza' => $this->idComuna($s->comuna) > 0,
					'telefono' => (string)$s->telefono,
					'mail' => (string)$s->mail,
					'casa_matriz' => $this->bool($s->casa_matriz),
					'codigo_sii' => (int)$s->codigo_sii,
					'codigo_sii_manual' => (int)$s->codigo_sii_manual,
					'vigente' => $this->bool($s->vigente !== null ? $s->vigente : true),
					'cajas' => (int)$s->cajas,
				);
			}
			$datos = array();
			foreach (self::CAMPOS_EDITABLES as $c) $datos[$c] = isset($e->$c) ? $e->$c : null;
			$empresas[] = array(
				'id' => (int)$e->id_empresa,
				'principal' => (int)$e->id_empresa === $principal,
				'rut' => (int)$e->rut_empresa > 0 ? number_format((float)$e->rut_empresa, 0, ',', '.') . '-' . strtoupper(trim((string)$e->dv_empresa)) : '',
				'datos' => $datos,
				'comuna' => (string)$e->nombre_comuna_local,
				'comuna_intranet' => (string)$e->comuna_intranet,
				'id_cliente_intranet' => (int)$e->id_cliente_intranet,
				'sincronizada' => (string)$e->sincronizada,
				'estado' => (string)$e->estado_sincronizacion,
				'bloqueados' => $bloqueados,
				'columnas' => array_values(array_intersect(self::CAMPOS_EDITABLES, $this->columnasEmpresa())),
				'sucursales' => $sucursales,
			);
		}
		return array(
			'empresas' => $empresas,
			'comunas' => $this->q('SELECT id_comuna, nombre_comuna FROM comuna ORDER BY nombre_comuna')->result_array(),
			// Se consulta la intranet sola una vez por sesión al abrir la pestaña
			'sincronizar' => !$this->session->userdata('empresas_intranet_sinc'),
		);
	}

	// ---------------- Intranet ----------------

	/**
	 * Consulta la intranet por cada empresa con RUT y actualiza sus datos, sus sucursales y las cajas de esas sucursales.
	 * $soloEmpresa > 0: solo esa empresa (Control de cajas, al elegir una empresa sin sucursales).
	 */
	public function sincronizar($soloEmpresa = 0)
	{
		$this->exigir();
		$this->load->library('ConexionesTenant');
		$abrev = (string)$this->session->userdata('abreviatura_software');
		$resultado = array();
		$cajas = array();
		$conCajas = false;
		$consultadas = array();
		foreach ($this->q('SELECT id_empresa, COALESCE(rut_empresa, 0) AS rut, COALESCE(dv_empresa, \'\') AS dv FROM empresa'
				. ((int)$soloEmpresa > 0 ? ' WHERE id_empresa = ' . (int)$soloEmpresa : '') . ' ORDER BY id_empresa')->result() as $e) {
			$idEmpresa = (int)$e->id_empresa;
			$rut = (int)preg_replace('/\D/', '', (string)$e->rut);
			$dv = strtoupper(trim((string)$e->dv));
			$fila = array('id' => $idEmpresa, 'rut' => $rut ? number_format($rut, 0, ',', '.') . '-' . $dv : '', 'ok' => false, 'mensaje' => '', 'sucursales' => 0);
			if ($rut <= 0 || $dv === '') {
				$fila['mensaje'] = 'Sin RUT: no se puede consultar la intranet';
				$this->estado($idEmpresa, $fila['mensaje']);
				$resultado[] = $fila;
				continue;
			}
			$con = $this->conexionestenant->consultarCliente(($rut < 10000000 ? '0' : '') . number_format($rut, 0, ',', '.') . '-' . $dv,
				0, RESTO_ID_SOFTWARE, $abrev);
			if (!$con['ok']) {
				$mensajes = array(
					'cliente_no_existe' => 'La intranet no tiene registrada esta empresa',
					'validar_cliente_sin_respuesta' => 'La intranet no respondió',
					'sin_credenciales_validar_cliente' => 'Falta configurar el acceso a la intranet (validarCliente)',
				);
				$fila['mensaje'] = isset($mensajes[$con['motivo']]) ? $mensajes[$con['motivo']] : 'No se pudo consultar la intranet';
				// No se cambian los datos ni las sucursales: solo queda el aviso de la última consulta
				$this->estado($idEmpresa, $fila['mensaje']);
				$resultado[] = $fila;
				continue;
			}
			$c = $con['cliente'];
			$sucursales = $this->sucursalesDeEstaBase($c);
			$db = $this->db();
			$db->trans_begin();
			try {
				$this->guardarDesdeIntranet($idEmpresa, $c);
				$this->guardarSucursales($idEmpresa, (int)$c['id_cliente_intranet'], $sucursales);
				$db->trans_commit();
			} catch (Exception $ex) {
				$db->trans_rollback();
				log_message('error', 'Mempresas::sincronizar empresa ' . $idEmpresa . ': ' . $ex->getMessage());
				$fila['mensaje'] = 'No se pudieron guardar los datos: ' . $ex->getMessage();
				$resultado[] = $fila;
				continue;
			}
			foreach ($this->conexionestenant->cajasAutorizadas($c, RESTO_ID_SOFTWARE) as $caja) {
				if (isset($sucursales[(int)$caja['id_sucursal']])) $cajas[] = $caja;
			}
			$conCajas = true;
			$consultadas = array_merge($consultadas, array_keys($sucursales));
			$fila['ok'] = true;
			$fila['sucursales'] = count($sucursales);
			$fila['mensaje'] = 'Datos de la intranet';
			$resultado[] = $fila;
		}
		// Las cajas de esas sucursales, a Control de cajas (Herramientas)
		$resCajas = null;
		if ($conCajas && ($cajas || $consultadas)) {
			$this->load->model('Mcontrolcaja');
			try {
				// La intranet entrega todas las cajas de esas sucursales: las que no vienen quedan no vigentes
				$resCajas = $this->Mcontrolcaja->sincronizar($cajas, $consultadas);
			} catch (Exception $ex) {
				log_message('error', 'Mempresas::sincronizar cajas: ' . $ex->getMessage());
			}
		}
		if ((int)$soloEmpresa <= 0) $this->session->set_userdata('empresas_intranet_sinc', 1);
		return array('resultado' => $resultado, 'cajas' => $resCajas);
	}

	// ---------------- Control de cajas (12-11-2026): empresa y sucursal ----------------

	/** Empresas con sus sucursales vigentes, para elegir en Control de cajas. */
	public function selector()
	{
		if (!$this->disponible()) return array();
		$principal = $this->q('SELECT min(id_empresa) AS id FROM empresa')->row();
		$principal = $principal ? (int)$principal->id : 0;
		$res = array();
		$ruts = array();
		foreach ($this->q("SELECT id_empresa, COALESCE(rut_empresa, 0) AS rut, COALESCE(dv_empresa, '') AS dv, COALESCE(razon_social, '') AS razon_social,
				COALESCE(nombre_fantasia, '') AS nombre_fantasia, COALESCE(estado_sincronizacion, '') AS estado
			FROM empresa ORDER BY id_empresa")->result() as $e) {
			$sucursales = array();
			foreach ($this->q("SELECT es.id_sucursal_intranet, COALESCE(es.nombre_sucursal, '') AS nombre, COALESCE(es.direccion, '') AS direccion,
					COALESCE(co.nombre_comuna, es.comuna, '') AS comuna, (es.casa_matriz IS TRUE) AS casa_matriz
				FROM empresa_sucursal es LEFT JOIN comuna co ON co.id_comuna = es.id_comuna AND es.id_comuna > 0
				WHERE es.id_empresa = ? AND es.vigente IS NOT FALSE AND COALESCE(es.id_sucursal_intranet, 0) > 0
				ORDER BY (es.casa_matriz IS TRUE) DESC, es.nombre_sucursal", array((int)$e->id_empresa))->result() as $s) {
				$sucursales[] = array('id' => (int)$s->id_sucursal_intranet, 'nombre' => (string)$s->nombre, 'direccion' => (string)$s->direccion,
					'comuna' => (string)$s->comuna, 'casa_matriz' => $this->bool($s->casa_matriz));
			}
			$rut = (int)preg_replace('/\D/', '', (string)$e->rut);
			$ruts[$rut] = true;
			$res[] = array(
				'id' => (int)$e->id_empresa,
				'rut' => $rut > 0 ? number_format($rut, 0, ',', '.') . '-' . strtoupper(trim((string)$e->dv)) : '',
				'nombre' => (string)($e->razon_social !== '' ? $e->razon_social : $e->nombre_fantasia),
				'principal' => (int)$e->id_empresa === $principal,
				'estado' => (string)$e->estado,
				'sucursales' => $sucursales,
			);
		}
		// La empresa de inicio de sesión, si su RUT no está registrado: primera de la lista
		$rs = $this->rutSesion();
		if ($rs && !isset($ruts[$rs[0]])) {
			$sucursales = array();
			foreach ($this->q("SELECT es.id_sucursal_intranet, COALESCE(es.nombre_sucursal, '') AS nombre, COALESCE(es.direccion, '') AS direccion,
					COALESCE(co.nombre_comuna, es.comuna, '') AS comuna, (es.casa_matriz IS TRUE) AS casa_matriz
				FROM empresa_sucursal es LEFT JOIN comuna co ON co.id_comuna = es.id_comuna AND es.id_comuna > 0
				WHERE COALESCE(es.id_empresa, 0) = 0 AND es.id_cliente_intranet = ? AND es.vigente IS NOT FALSE AND COALESCE(es.id_sucursal_intranet, 0) > 0
				ORDER BY (es.casa_matriz IS TRUE) DESC, es.nombre_sucursal", array($this->idClienteSesion()))->result() as $s) {
				$sucursales[] = array('id' => (int)$s->id_sucursal_intranet, 'nombre' => (string)$s->nombre, 'direccion' => (string)$s->direccion,
					'comuna' => (string)$s->comuna, 'casa_matriz' => $this->bool($s->casa_matriz));
			}
			array_unshift($res, array(
				'id' => 0,
				'rut' => number_format($rs[0], 0, ',', '.') . '-' . $rs[1],
				'nombre' => (string)$this->session->userdata('nombre_cliente'),
				'principal' => false,
				'sesion' => true,
				'estado' => '',
				'sucursales' => $sucursales,
			));
		}
		return $res;
	}

	/**
	 * Cajas de una sucursal desde la intranet: validarCliente con el RUT de la empresa y esa sucursal. Lo que no viene de
	 * esa sucursal queda no vigente en Control de cajas.
	 * @return array insertadas, actualizadas, desactivadas, recibidas
	 */
	public function cajasDeSucursal($idEmpresa, $idSucursal)
	{
		$this->exigir();
		$idSucursal = (int)$idSucursal;
		if ((int)$idEmpresa === 0) {
			// Empresa de inicio de sesión (no está en la tabla empresa)
			$rs = $this->rutSesion();
			if (!$rs) throw new Exception('La sesión no tiene el RUT de la empresa: vuelve a ingresar', 400);
			$e = (object)array('rut' => $rs[0], 'dv' => $rs[1]);
			$es = $this->q('SELECT 1 FROM empresa_sucursal WHERE COALESCE(id_empresa, 0) = 0 AND id_cliente_intranet = ? AND id_sucursal_intranet = ?
				AND vigente IS NOT FALSE', array($this->idClienteSesion(), $idSucursal))->row();
		} else {
			$e = $this->q('SELECT COALESCE(rut_empresa, 0) AS rut, COALESCE(dv_empresa, \'\') AS dv FROM empresa WHERE id_empresa = ?', array((int)$idEmpresa))->row();
			if (!$e) throw new Exception('La empresa no existe', 404);
			$es = $this->q('SELECT 1 FROM empresa_sucursal WHERE id_empresa = ? AND id_sucursal_intranet = ? AND vigente IS NOT FALSE',
				array((int)$idEmpresa, $idSucursal))->row();
		}
		if (!$es) throw new Exception('La sucursal no es de esta empresa. Actualiza las sucursales desde la intranet.', 400);
		$rut = (int)preg_replace('/\D/', '', (string)$e->rut);
		$dv = strtoupper(trim((string)$e->dv));
		if ($rut <= 0 || $dv === '') throw new Exception('La empresa no tiene RUT: no se puede consultar la intranet', 400);
		$this->load->library('ConexionesTenant');
		$con = $this->conexionestenant->consultarCliente(($rut < 10000000 ? '0' : '') . number_format($rut, 0, ',', '.') . '-' . $dv,
			$idSucursal, RESTO_ID_SOFTWARE, (string)$this->session->userdata('abreviatura_software'));
		if (!$con['ok']) {
			throw new Exception($con['motivo'] === 'cliente_no_existe' ? 'La intranet no tiene esta empresa o esta sucursal'
				: 'No se pudo consultar la intranet en este momento. Intenta nuevamente en unos minutos.', 502);
		}
		$cajas = array();
		foreach ($this->conexionestenant->cajasAutorizadas($con['cliente'], RESTO_ID_SOFTWARE) as $c) {
			if ((int)$c['id_sucursal'] === $idSucursal) $cajas[] = $c;
		}
		$this->load->model('Mcontrolcaja');
		$r = $this->Mcontrolcaja->sincronizar($cajas, array($idSucursal));
		$r['recibidas'] = count($cajas);
		return $r;
	}

	/** Sucursales de la respuesta de validarCliente que son de esta base: las que informan otra base (con_sucursal) no. */
	private function sucursalesDeEstaBase($c)
	{
		$srvSesion = strtolower(trim((string)$this->session->userdata('db_server')));
		$bdSesion = strtolower(trim((string)$this->session->userdata('bd_name')));
		$bases = $this->conexionestenant->basesPorSucursal($c);
		$sucursales = array();
		foreach ((isset($c['listasucursales']) && is_array($c['listasucursales']) ? $c['listasucursales'] : array()) as $s) {
			$id = is_array($s) ? (int)($s['id_sucursal_cliente'] ?? 0) : 0;
			if ($id <= 0) continue;
			if (isset($bases[$id])) {
				$esta = false;
				foreach ($bases[$id] as $b) {
					if (strtolower($b[0]) === $srvSesion && strtolower($b[1]) === $bdSesion) $esta = true;
				}
				if (!$esta) continue;
			}
			$sucursales[$id] = $s;
		}
		return $sucursales;
	}

	// ---------------- Empresa de inicio de sesión (12-11-2026) ----------------
	// La empresa (cliente de la intranet) con que se inicia sesión es dueña de esta base en la intranet. Si su RUT no está en
	// la tabla empresa, igual aparece en Control de cajas (id 0) con sus sucursales y cajas. No se agrega a la tabla empresa
	// y no cambia los documentos (emite la primera empresa de la tabla). Sus sucursales quedan en empresa_sucursal con
	// id_empresa = 0 e id_cliente_intranet = el de la sesión.

	/** [rut, dv] de la empresa de la sesión, o null */
	private function rutSesion()
	{
		$t = (string)$this->session->userdata('rut_cliente_intranet');
		if ($t === '') $t = (string)$this->session->userdata('rut_cliente');
		$p = explode('-', $t);
		$num = (int)preg_replace('/\D/', '', $p[0]);
		$dv = strtoupper(trim(isset($p[1]) ? $p[1] : ''));
		return $num > 0 && $dv !== '' ? array($num, $dv) : null;
	}

	private function idClienteSesion()
	{
		return (int)$this->session->userdata('id_cliente');
	}

	/** Sus sucursales, datos y cajas desde la intranet. */
	public function sincronizarEmpresaSesion()
	{
		$this->exigir();
		$rs = $this->rutSesion();
		$fila = array('id' => 0, 'rut' => $rs ? number_format($rs[0], 0, ',', '.') . '-' . $rs[1] : '', 'ok' => false, 'mensaje' => '', 'sucursales' => 0);
		if (!$rs) { $fila['mensaje'] = 'La sesión no tiene el RUT de la empresa: vuelve a ingresar'; return $fila; }
		$this->load->library('ConexionesTenant');
		$con = $this->conexionestenant->consultarCliente(($rs[0] < 10000000 ? '0' : '') . number_format($rs[0], 0, ',', '.') . '-' . $rs[1],
			0, RESTO_ID_SOFTWARE, (string)$this->session->userdata('abreviatura_software'));
		if (!$con['ok']) {
			$fila['mensaje'] = $con['motivo'] === 'cliente_no_existe' ? 'La intranet no tiene registrada esta empresa' : 'La intranet no respondió';
			return $fila;
		}
		$c = $con['cliente'];
		$idCliente = (int)$c['id_cliente_intranet'];
		$sucursales = $this->sucursalesDeEstaBase($c);
		$db = $this->db();
		$db->trans_begin();
		try {
			$this->guardarSucursales(0, $idCliente, $sucursales);
			$db->trans_commit();
		} catch (Exception $ex) {
			$db->trans_rollback();
			throw $ex;
		}
		if ($sucursales) {
			$cajas = array();
			foreach ($this->conexionestenant->cajasAutorizadas($c, RESTO_ID_SOFTWARE) as $caja) {
				if (isset($sucursales[(int)$caja['id_sucursal']])) $cajas[] = $caja;
			}
			$this->load->model('Mcontrolcaja');
			$this->Mcontrolcaja->sincronizar($cajas, array_keys($sucursales));
		}
		$fila['ok'] = true;
		$fila['sucursales'] = count($sucursales);
		$fila['mensaje'] = 'Datos de la intranet';
		return $fila;
	}

	private function guardarDesdeIntranet($idEmpresa, $c)
	{
		$cols = $this->columnasEmpresa();
		$set = array('id_cliente_intranet' => (int)$c['id_cliente_intranet']);
		$campos = array();
		foreach (self::CAMPOS_INTRANET as $campo => $clave) {
			$v = trim((string)($c[$clave] ?? ''));
			if ($v === '' || !in_array($campo, $cols, true)) continue;
			if ($campo === 'mail' && !filter_var($v, FILTER_VALIDATE_EMAIL)) continue;
			$set[$campo] = $this->cortar($v, $this->largo('empresa', $campo));
			$campos[] = $campo;
		}
		$comuna = trim((string)($c['nombre_comuna'] ?? ''));
		$set['comuna_intranet'] = $this->cortar($comuna, 80);
		$idComuna = $this->idComuna($comuna);
		if ($idComuna > 0 && in_array('id_comuna', $cols, true)) {
			$set['id_comuna'] = $idComuna;
			$campos[] = 'id_comuna';
		}
		$set['campos_intranet'] = implode(',', $campos);
		$set['fecha_sincronizacion'] = date('Y-m-d H:i:s');
		$set['estado_sincronizacion'] = 'Datos de la intranet';
		$this->actualizar('empresa', 'id_empresa', $idEmpresa, $set);
	}

	private function guardarSucursales($idEmpresa, $idCliente, $sucursales)
	{
		$ahora = date('Y-m-d H:i:s');
		foreach ($sucursales as $id => $s) {
			$comuna = trim((string)($s['comuna'] ?? ''));
			$set = array(
				'id_empresa' => $idEmpresa,
				'id_cliente_intranet' => $idCliente,
				'nombre_sucursal' => $this->cortar(trim((string)($s['nombre_sucursal'] ?? '')) ?: 'Sucursal ' . $id, 120),
				'nombre_fantasia' => $this->cortar(trim((string)($s['nombre_fantasia'] ?? '')), 120),
				'direccion' => $this->cortar(trim((string)($s['direccion'] ?? '')), 200),
				'comuna' => $this->cortar($comuna, 80),
				'telefono' => $this->cortar(trim((string)($s['telefono'] ?? '')), 60),
				'mail' => $this->cortar(trim((string)($s['mail_sucursal'] ?? '')), 150),
				'giro' => $this->cortar(trim((string)($s['giro'] ?? '')), 200),
				'casa_matriz' => $this->bool($s['casa_matriz'] ?? false) ? 't' : 'f',
				'codigo_sii' => (int)preg_replace('/\D/', '', substr((string)($s['codigo_sii'] ?? ''), 0, 9)),
				'imprimir_en_encabezado_dte' => $this->bool($s['imprimir_en_encabezado_dte'] ?? false) ? 't' : 'f',
				'vigente' => 't',
				'fecha_sincronizacion' => $ahora,
			);
			// La comuna se toma de la tabla comuna si el nombre calza; si no, queda la elegida aquí
			$idComuna = $this->idComuna($comuna);
			if ($idComuna > 0) $set['id_comuna'] = $idComuna;
			$fila = $this->q('SELECT id_empresa_sucursal FROM empresa_sucursal WHERE id_sucursal_intranet = ? ORDER BY id_empresa_sucursal LIMIT 1',
				array((int)$id))->row();
			if ($fila) {
				$this->actualizar('empresa_sucursal', 'id_empresa_sucursal', (int)$fila->id_empresa_sucursal, $set);
			} else {
				$set['id_sucursal_intranet'] = (int)$id;
				if (!isset($set['id_comuna'])) $set['id_comuna'] = 0;
				$set['codigo_sii_manual'] = 0;
				$this->q('INSERT INTO empresa_sucursal (' . implode(', ', array_keys($set)) . ') VALUES ('
					. implode(', ', array_fill(0, count($set), '?')) . ')', array_values($set));
			}
		}
		// Las que la intranet ya no informa (o pasaron a otra base) quedan no vigentes. Sin ninguna, no se cambia nada.
		// Empresa de inicio de sesión (id_empresa 0): solo las de su cliente de la intranet
		$alcance = (int)$idEmpresa > 0 ? '' : ' AND id_cliente_intranet = ' . (int)$idCliente;
		if ($sucursales) {
			$ids = array_keys($sucursales);
			$this->q('UPDATE empresa_sucursal SET vigente = false, fecha_sincronizacion = ? WHERE id_empresa = ?' . $alcance . ' AND vigente IS NOT FALSE
				AND id_sucursal_intranet NOT IN (' . implode(',', array_fill(0, count($ids), '?')) . ')', array_merge(array($ahora, $idEmpresa), $ids));
			// Las cajas (Control de cajas) de una sucursal que ya no es vigente tampoco se ofrecen al abrir
			$cc = $this->q("SELECT count(*) AS n FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'control_caja'
				AND column_name IN ('id_sucursal', 'id_control_caja_web', 'vigente')")->row();
			if ($cc && (int)$cc->n === 3) {
				$this->q('UPDATE control_caja SET vigente = false WHERE vigente IS NOT FALSE AND COALESCE(id_control_caja_web, 0) > 0
					AND ' . $this->sucursalCaja('') . ' IN (SELECT id_sucursal_intranet FROM empresa_sucursal WHERE id_empresa = ?' . $alcance . ' AND vigente IS FALSE)', array($idEmpresa));
			}
		}
	}

	private function estado($idEmpresa, $mensaje)
	{
		$this->actualizar('empresa', 'id_empresa', $idEmpresa, array('estado_sincronizacion' => $this->cortar($mensaje, 200),
			'fecha_sincronizacion' => date('Y-m-d H:i:s')));
	}

	// ---------------- Edición ----------------

	/** Datos de una empresa: solo los campos que no vinieron de la intranet. */
	public function guardarEmpresa($id, $d)
	{
		$this->exigir();
		$e = $this->q('SELECT * FROM empresa WHERE id_empresa = ?', array((int)$id))->row();
		if (!$e) throw new Exception('La empresa no existe', 404);
		$bloqueados = $this->bloqueados($e);
		$set = array();
		foreach (array_intersect(self::CAMPOS_EDITABLES, $this->columnasEmpresa()) as $c) {
			if (in_array($c, $bloqueados, true) || !array_key_exists($c, $d)) continue;
			$v = is_string($d[$c]) ? trim($d[$c]) : $d[$c];
			if ($c === 'rut_empresa') {
				$v = (int)preg_replace('/\D/', '', (string)$v);
				if ($v <= 0) throw new Exception('Ingresa el RUT de la empresa', 400);
			} elseif ($c === 'dv_empresa') {
				$v = strtoupper((string)$v);
				if (!preg_match('/^[0-9K]$/', $v)) throw new Exception('El dígito verificador debe ser un número o K', 400);
			} elseif ($c === 'id_comuna') {
				$v = (int)$v;
				if ($v > 0 && !$this->q('SELECT 1 FROM comuna WHERE id_comuna = ?', array($v))->row()) throw new Exception('La comuna no existe', 400);
			} elseif ($c === 'mail') {
				if ($v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) throw new Exception('El correo no es válido', 400);
			} else {
				$v = $this->cortar((string)$v, $this->largo('empresa', $c));
			}
			$set[$c] = $v;
		}
		if (isset($set['rut_empresa'], $set['dv_empresa']) && !$this->rutValido($set['rut_empresa'], $set['dv_empresa'])) {
			throw new Exception('El RUT no es válido (revisa el dígito verificador)', 400);
		}
		if ($set) $this->actualizar('empresa', 'id_empresa', (int)$id, $set);
		return array('ok' => true);
	}

	/** Sucursal: el código SII (si la intranet no lo trae) y la comuna (si la de la intranet no calza). */
	public function guardarSucursal($id, $codigoSii, $idComuna)
	{
		$this->exigir();
		$s = $this->q('SELECT * FROM empresa_sucursal WHERE id_empresa_sucursal = ?', array((int)$id))->row();
		if (!$s) throw new Exception('La sucursal no existe', 404);
		$set = array();
		if ((int)$s->codigo_sii <= 0) {
			$codigo = trim((string)$codigoSii);
			if ($codigo !== '' && !preg_match('/^\d{1,9}$/', $codigo)) throw new Exception('El código SII de la sucursal es un número de hasta 9 dígitos', 400);
			$set['codigo_sii_manual'] = (int)$codigo;
		}
		if ($this->idComuna($s->comuna) <= 0) {
			$idComuna = (int)$idComuna;
			if ($idComuna > 0 && !$this->q('SELECT 1 FROM comuna WHERE id_comuna = ?', array($idComuna))->row()) throw new Exception('La comuna no existe', 400);
			$set['id_comuna'] = $idComuna;
		}
		if ($set) $this->actualizar('empresa_sucursal', 'id_empresa_sucursal', (int)$id, $set);
		return array('ok' => true);
	}

	// ---------------- Apoyo ----------------

	/** Campos que vinieron de la intranet (no se editan aquí); con la empresa en la intranet, tampoco el RUT. */
	private function bloqueados($e)
	{
		$b = array_values(array_filter(array_map('trim', explode(',', (string)$e->campos_intranet))));
		if ((int)$e->id_cliente_intranet > 0) {
			$b[] = 'rut_empresa';
			$b[] = 'dv_empresa';
		}
		return array_values(array_unique($b));
	}

	/** id_comuna de la tabla comuna con ese nombre (sin tildes ni mayúsculas), o 0 */
	private function idComuna($nombre)
	{
		if ($this->comunas === null) {
			$this->comunas = array();
			foreach ($this->q('SELECT id_comuna, nombre_comuna FROM comuna ORDER BY id_comuna')->result() as $c) {
				$k = $this->normalizar($c->nombre_comuna);
				if ($k !== '' && !isset($this->comunas[$k])) $this->comunas[$k] = (int)$c->id_comuna;
			}
		}
		$k = $this->normalizar($nombre);
		return $k !== '' && isset($this->comunas[$k]) ? $this->comunas[$k] : 0;
	}

	private function normalizar($s)
	{
		$s = mb_strtoupper(trim((string)$s), 'UTF-8');
		$s = strtr($s, array('Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N'));
		return trim(preg_replace('/\s+/', ' ', $s));
	}

	private function rutValido($rut, $dv)
	{
		$s = 1;
		for ($m = 0; $rut; $rut = (int)($rut / 10)) $s = ($s + $rut % 10 * (9 - $m++ % 6)) % 11;
		return ($s ? (string)($s - 1) : 'K') === strtoupper((string)$dv);
	}

	private function columnasEmpresa()
	{
		if ($this->colsEmpresa === null) {
			$this->colsEmpresa = array_map(function ($f) { return $f->c; }, $this->q("SELECT column_name AS c FROM information_schema.columns
				WHERE table_schema = 'public' AND table_name = 'empresa'")->result());
		}
		return $this->colsEmpresa;
	}

	private function largo($tabla, $columna)
	{
		static $cache = array();
		$k = $tabla . '.' . $columna;
		if (!isset($cache[$k])) {
			$r = $this->q("SELECT character_maximum_length AS n FROM information_schema.columns WHERE table_schema = 'public'
				AND table_name = ? AND column_name = ?", array($tabla, $columna))->row();
			$cache[$k] = $r && (int)$r->n > 0 ? (int)$r->n : 0;
		}
		return $cache[$k];
	}

	private function cortar($v, $max)
	{
		return $max > 0 ? mb_substr((string)$v, 0, $max) : (string)$v;
	}

	private function actualizar($tabla, $pk, $id, $set)
	{
		$sets = array();
		foreach (array_keys($set) as $c) $sets[] = $c . ' = ?';
		$this->q('UPDATE ' . $tabla . ' SET ' . implode(', ', $sets) . ' WHERE ' . $pk . ' = ?', array_merge(array_values($set), array((int)$id)));
	}

	/** Sucursal de la intranet de control_caja (id_sucursal_intranet, o id_sucursal sin la migración 20261112090000) */
	private function sucursalCaja($alias)
	{
		$this->load->helper('sucursal_empresa');
		return control_caja_sucursal_sql($this->db(), $alias);
	}

	private function bool($v)
	{
		return $v === true || $v === 't' || $v === 1 || $v === '1' || $v === 'true';
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
			log_message('error', 'Mempresas: ' . $e['message'] . ' | ' . $sql);
			throw new Exception($e['message'] ? trim(preg_replace('/^ERROR:\s*/', '', $e['message'])) : 'Error de base de datos', 500);
		}
		return $r;
	}
}
