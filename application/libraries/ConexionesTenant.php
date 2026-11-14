<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Diccionario de conexiones de las empresas (10-11-2026).
 *
 * Reemplaza los grupos de application/config/database.php. Igual que mipdv_web:
 *
 *   1) La intranet (api/validarCliente) dice en qué servidor y en qué base está
 *      cada empresa: `con` / `con_sucursal`, en base64, con el servidor como CLAVE
 *      ('localhost', 'aws', 'aws_test').
 *   2) DbManager (copiado de mipdv_web) tiene el diccionario de servidores: por
 *      cada clave, host / puerto / usuario / clave, leídos del entorno
 *      (MIPDV_DB_<SERVIDOR>_*). Nunca de la intranet ni del código.
 *
 * La sesión web guarda db_server / bd_name. Lo que no tiene sesión (app móvil
 * con tokens ya emitidos y agente de impresión, que se identifican por
 * nombre_conexion) usa el diccionario local nombre_conexion -> {srv, bd}, que se
 * actualiza solo en cada login. Se guarda como PHP (application/cache/
 * conexiones_tenant.php) para que no se pueda descargar como texto; solo trae
 * claves de servidor y nombres de base, nunca credenciales.
 *
 * Métodos de resolución copiados de mipdv_web/application/controllers/Login.php
 * (_extractConnectionPayload, _extractConnectionPayloadSucursal,
 * _normalizeConnectionValue, _resolveConnectionPair).
 */
class ConexionesTenant
{
	/** @var CI_Controller */
	private $CI;

	/** Conexiones recordadas solo durante esta petición (ej. las del token de la app móvil). */
	private $memoria = array();

	/** Caché del archivo leído en esta petición. */
	private $diccionario = null;

	public function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->load->library('DbManager');
	}

	// ======================================================================
	// 1) Intranet: servidor y base de la empresa
	// ======================================================================

	/**
	 * Pregunta a la intranet (validarCliente) por la base de la empresa.
	 *
	 * @param string $rutCliente  RUT con puntos y guion (como lo arma Login).
	 * @param int    $idSucursal  sucursal preferida (0 = la que diga la intranet).
	 * @param int    $idSoftware  RESTO_ID_SOFTWARE.
	 * @param string $abreviatura software.abreviatura (la intranet solo arma `con` si viene `ns`).
	 * @return array ok, db_server, bd_name, origen, id_cliente_intranet, motivo
	 */
	public function resolverDesdeIntranet($rutCliente, $idSucursal, $idSoftware, $abreviatura)
	{
		$salida = array(
			'ok' => false, 'db_server' => '', 'bd_name' => '', 'origen' => '',
			'id_cliente_intranet' => 0, 'motivo' => '', 'cajas' => array(),
		);

		$user = (string)$this->CI->config->item('validar_cliente_user');
		$pass = (string)$this->CI->config->item('validar_cliente_pass');
		if ($user === '' || $pass === '') {
			$salida['motivo'] = 'sin_credenciales_validar_cliente';
			log_message('error', '[CONEXIONES] Faltan validar_cliente_legacy_user / validar_cliente_legacy_pass en el entorno (.env.php o SetEnv).');
			return $salida;
		}

		$abreviatura = trim((string)$abreviatura);
		if ($abreviatura === '') {
			$abreviatura = 'RESTO';
		}

		$params = http_build_query(array(
			'user' => $user,
			'pass' => $pass,
			'id' => (string)$rutCliente,
			'ns' => $abreviatura,
			'isis' => '',
			'id_sucursal' => (int)$idSucursal,
			'id_software' => (int)$idSoftware,
		));

		$this->CI->load->library('ValidarClienteApi');
		$resultado = array();
		foreach (array(30, 45) as $i => $timeout) {
			$vc = $this->CI->validarclienteapi->post($params, $timeout, 'ConexionesTenant::resolverDesdeIntranet');
			$resultado = json_decode((string)$vc['body'], true);
			if (is_array($resultado) && count($resultado) > 0) {
				break;
			}
			log_message('error', sprintf(
				'[CONEXIONES] validarCliente intento %d sin datos: fuente=%s http=%d error=%s',
				$i + 1, (string)$vc['fuente'], (int)$vc['http'], (string)$vc['error']
			));
			$resultado = array();
		}
		if (empty($resultado)) {
			$salida['motivo'] = 'validar_cliente_sin_respuesta';
			return $salida;
		}

		$par = $this->_resolveConnectionPair($resultado, (int)$idSucursal);
		$salida['origen'] = $par['origen'];
		// Cajas autorizadas para este sistema (Herramientas > Control de cajas, 11-11-2026)
		$salida['cajas'] = $this->cajasAutorizadas($resultado, (int)$idSoftware);
		$salida['id_cliente_intranet'] = isset($resultado[0]['id']) ? (int)$resultado[0]['id'] : 0;

		if ($par['bd_name'] === '' || $par['db_server'] === '') {
			$salida['motivo'] = 'empresa_sin_conexion';
			log_message('error', sprintf(
				'[CONEXIONES] La intranet no informa base para rut=%s id_software=%d (con / con_sucursal vacíos para este software).',
				(string)$rutCliente, (int)$idSoftware
			));
			return $salida;
		}
		if (!$this->esValida($par['db_server'], $par['bd_name'])) {
			$salida['motivo'] = 'servidor_desconocido';
			log_message('error', sprintf(
				'[CONEXIONES] La intranet informa el servidor "%s" (base %s) y no está en el diccionario de DbManager (%s). Revisa MIPDV_DB_<SERVIDOR>_* en el entorno.',
				$par['db_server'], $par['bd_name'], implode(', ', $this->CI->dbmanager->serverKeys())
			));
			return $salida;
		}

		$salida['ok'] = true;
		$salida['db_server'] = $par['db_server'];
		$salida['bd_name'] = $par['bd_name'];
		return $salida;
	}

	/**
	 * Cajas de lista_cajas_autorizadas (validarCliente) para este sistema, como las sincroniza mipdv_web
	 * (Login::_syncControlCajaRow): una fila sin id_software se toma como de este sistema; con otro id_software se descarta.
	 * @return array [{id_control_caja, id_sucursal, caja_n, nombre_caja, terminal, vigente, caja_administrador, id_software}]
	 */
	public function cajasAutorizadas($resultado, $idSoftware)
	{
		$verdad = function ($v) { return in_array($v, array(1, '1', 't', 'true', true), true); };
		$cajas = array();
		$bloques = isset($resultado['lista_cajas_autorizadas']) ? array($resultado) : (array)$resultado;
		foreach ($bloques as $b) {
			if (!is_array($b) || !isset($b['lista_cajas_autorizadas']) || !is_array($b['lista_cajas_autorizadas'])) continue;
			foreach ($b['lista_cajas_autorizadas'] as $c) {
				if (!is_array($c) || (int)($c['id_control_caja'] ?? 0) <= 0) continue;
				$soft = (isset($c['id_software']) && $c['id_software'] !== '' && $c['id_software'] !== null) ? (int)$c['id_software'] : (int)$idSoftware;
				if ($soft !== (int)$idSoftware) continue;
				$id = (int)$c['id_control_caja'];
				$cajas[$id] = array(
					'id_control_caja' => $id,
					'id_sucursal' => (int)($c['id_sucursal'] ?? 0),
					'caja_n' => (int)($c['caja_n'] ?? 0),
					'nombre_caja' => trim((string)($c['nombre_caja'] ?? '')),
					'terminal' => trim((string)($c['nombre_terminal'] ?? '')),
					'vigente' => $verdad($c['vigente'] ?? false),
					'caja_administrador' => $verdad($c['caja_administrador'] ?? false),
					'id_software' => $soft,
				);
			}
		}
		return array_values($cajas);
	}

	/**
	 * Datos de una empresa en la intranet (validarCliente con su RUT), para Configuraciones > Datos de la Empresa
	 * (11-11-2026). No toca la sesión ni la conexión. Pide omitir_sensibles: no se necesitan los datos de respaldo.
	 * @param string $rut  RUT con o sin puntos ("76.123.456-7").
	 * @return array ok, motivo ('' | sin_credenciales_validar_cliente | validar_cliente_sin_respuesta | cliente_no_existe),
	 *               cliente (primer elemento de la respuesta: id_cliente_intranet, nombre, giro, listasucursales, con_sucursal...)
	 */
	public function consultarCliente($rut, $idSucursal, $idSoftware, $abreviatura, $timeout = 25)
	{
		$salida = array('ok' => false, 'motivo' => '', 'cliente' => null);
		$user = (string)$this->CI->config->item('validar_cliente_user');
		$pass = (string)$this->CI->config->item('validar_cliente_pass');
		if ($user === '' || $pass === '') {
			$salida['motivo'] = 'sin_credenciales_validar_cliente';
			return $salida;
		}
		$abreviatura = trim((string)$abreviatura);
		$params = http_build_query(array(
			'user' => $user,
			'pass' => $pass,
			'id' => (string)$rut,
			'ns' => $abreviatura !== '' ? $abreviatura : 'RESTO',
			'isis' => '',
			'id_sucursal' => (int)$idSucursal,
			'id_software' => (int)$idSoftware,
			'omitir_sensibles' => 1,
		));
		$this->CI->load->library('ValidarClienteApi');
		$vc = $this->CI->validarclienteapi->post($params, (int)$timeout, 'ConexionesTenant::consultarCliente');
		$resultado = json_decode((string)$vc['body'], true);
		if (!is_array($resultado) || !$resultado) {
			$salida['motivo'] = 'validar_cliente_sin_respuesta';
			return $salida;
		}
		$cliente = isset($resultado[0]) && is_array($resultado[0]) ? $resultado[0] : $resultado;
		if ((int)($cliente['id_cliente_intranet'] ?? 0) <= 0) {
			$salida['motivo'] = 'cliente_no_existe';
			return $salida;
		}
		$salida['ok'] = true;
		$salida['cliente'] = $cliente;
		return $salida;
	}

	/**
	 * Bases de datos (servidor + base) de cada sucursal de la respuesta de validarCliente (con_sucursal), ya decodificadas.
	 * @return array id_sucursal => [[srv, bd], ...]  (sucursal sin datos = no aparece)
	 */
	public function basesPorSucursal($cliente)
	{
		$res = array();
		if (!is_array($cliente) || !isset($cliente['con_sucursal']) || !is_array($cliente['con_sucursal'])) return $res;
		foreach ($cliente['con_sucursal'] as $k) {
			if (!is_array($k)) continue;
			$srv = $this->_normalizeConnectionValue($k['srv'] ?? '');
			$bd = $this->_normalizeConnectionValue($k['name'] ?? '');
			if ($srv === '' || $bd === '') continue;
			$res[(int)($k['id_sucursal'] ?? 0)][] = array($srv, $bd);
		}
		return $res;
	}

	private function _extractConnectionPayload($resultado)
	{
		$bdRaw = '';
		$srvRaw = '';
		if (is_array($resultado)) {
			if (isset($resultado['con']) && is_array($resultado['con'])) {
				foreach ($resultado['con'] as $k) {
					if (isset($k['name']) && isset($k['srv'])) {
						$bdRaw = (string)$k['name'];
						$srvRaw = (string)$k['srv'];
					}
				}
			} else {
				foreach ($resultado as $v) {
					if (!is_array($v) || !isset($v['con']) || !is_array($v['con'])) continue;
					foreach ($v['con'] as $k) {
						if (isset($k['name']) && isset($k['srv'])) {
							$bdRaw = (string)$k['name'];
							$srvRaw = (string)$k['srv'];
						}
					}
				}
			}
		}
		return array($bdRaw, $srvRaw);
	}

	private function _normalizeConnectionValue($value)
	{
		$value = trim((string)$value);
		if ($value === '') {
			return '';
		}
		$decoded = base64_decode($value, true);
		if ($decoded !== false) {
			$decoded = trim((string)$decoded);
			if ($decoded !== '') {
				return $decoded;
			}
		}
		return $value;
	}

	/* `con` agrupa por cliente y no dice qué base es de cada sucursal;
	   `con_sucursal` ([{id_sucursal,name,srv}]) sí. Si no trae la sucursal pedida
	   se usa `con`, igual que mipdv_web. */
	private function _extractConnectionPayloadSucursal($resultado, $idSucursal)
	{
		$idSucursal = (int)$idSucursal;
		if ($idSucursal <= 0 || !is_array($resultado)) {
			return array('', '');
		}
		$bloques = isset($resultado['con_sucursal']) ? array($resultado) : $resultado;
		foreach ($bloques as $v) {
			if (!is_array($v) || !isset($v['con_sucursal']) || !is_array($v['con_sucursal'])) continue;
			foreach ($v['con_sucursal'] as $k) {
				if (!is_array($k) || (int)($k['id_sucursal'] ?? 0) !== $idSucursal) continue;
				$bd = trim((string)($k['name'] ?? ''));
				$srv = trim((string)($k['srv'] ?? ''));
				if ($bd !== '' && $srv !== '') {
					return array($bd, $srv);
				}
			}
		}
		return array('', '');
	}

	private function _resolveConnectionPair($resultado, $idSucursalPreferida = 0)
	{
		$origen = 'con';
		list($bdRaw, $srvRaw) = $this->_extractConnectionPayloadSucursal($resultado, $idSucursalPreferida);
		if ($bdRaw !== '' && $srvRaw !== '') {
			$origen = 'con_sucursal';
		} else {
			list($bdRaw, $srvRaw) = $this->_extractConnectionPayload($resultado);
		}
		return array(
			'bd_name' => $this->_normalizeConnectionValue($bdRaw),
			'db_server' => $this->_normalizeConnectionValue($srvRaw),
			'origen' => $origen,
		);
	}

	// ======================================================================
	// 2) Conectar
	// ======================================================================

	/** El servidor está en el diccionario de DbManager y el nombre de base es razonable. */
	public function esValida($dbServer, $bdName)
	{
		$dbServer = trim((string)$dbServer);
		$bdName = trim((string)$bdName);
		return $dbServer !== '' && $bdName !== ''
			&& preg_match('/^[A-Za-z0-9_.\-]+$/', $bdName)
			&& $this->CI->dbmanager->serverExists($dbServer);
	}

	/**
	 * @param array $overrides ej. array('db_debug' => FALSE) para la app móvil / agente.
	 * @return CI_DB_query_builder|null
	 */
	public function conectar($dbServer, $bdName, array $overrides = array())
	{
		if (!$this->esValida($dbServer, $bdName)) {
			log_message('error', sprintf('[CONEXIONES] conexión no válida o servidor fuera del diccionario: srv="%s" bd="%s"', (string)$dbServer, (string)$bdName));
			return null;
		}
		return $this->CI->dbmanager->get(trim((string)$dbServer), trim((string)$bdName), $overrides);
	}

	/** Para lo que no tiene sesión web: agente de impresión y app móvil. */
	public function conectarPorNombre($nombreConexion, array $overrides = array())
	{
		$par = $this->buscar($nombreConexion);
		if ($par === null) {
			log_message('error', '[CONEXIONES] "' . (string)$nombreConexion . '" no está en el diccionario local: nadie de esa empresa ha iniciado sesión desde el cambio.');
			return null;
		}
		return $this->conectar($par['srv'], $par['bd'], $overrides);
	}

	// ======================================================================
	// 3) Diccionario local nombre_conexion -> {srv, bd}
	// ======================================================================

	private function _archivo()
	{
		return APPPATH . 'cache/conexiones_tenant.php';
	}

	private function _leer()
	{
		if ($this->diccionario !== null) {
			return $this->diccionario;
		}
		$this->diccionario = array();
		$f = $this->_archivo();
		if (is_file($f)) {
			$datos = @include $f;
			if (is_array($datos)) {
				$this->diccionario = $datos;
			}
		}
		return $this->diccionario;
	}

	/** Solo durante esta petición (no escribe el archivo). */
	public function recordar($nombreConexion, $dbServer, $bdName)
	{
		$nombreConexion = trim((string)$nombreConexion);
		if ($nombreConexion !== '' && $this->esValida($dbServer, $bdName)) {
			$this->memoria[$nombreConexion] = array('srv' => trim((string)$dbServer), 'bd' => trim((string)$bdName));
		}
	}

	/** @return array|null ['srv' => ..., 'bd' => ...] */
	public function buscar($nombreConexion)
	{
		$nombreConexion = trim((string)$nombreConexion);
		if ($nombreConexion === '') {
			return null;
		}
		if (isset($this->memoria[$nombreConexion])) {
			return $this->memoria[$nombreConexion];
		}
		$dic = $this->_leer();
		if (isset($dic[$nombreConexion]['srv'], $dic[$nombreConexion]['bd'])) {
			return array('srv' => (string)$dic[$nombreConexion]['srv'], 'bd' => (string)$dic[$nombreConexion]['bd']);
		}
		return null;
	}

	/** Todo el diccionario local: nombre_conexion => ['srv' => ..., 'bd' => ...]. Lo recorre el comando de despliegue. */
	public function todas()
	{
		return $this->_leer();
	}

	/** Guarda (o actualiza) la conexión de una empresa. Se llama en cada login. */
	public function guardar($nombreConexion, $dbServer, $bdName)
	{
		$nombreConexion = trim((string)$nombreConexion);
		if ($nombreConexion === '' || !$this->esValida($dbServer, $bdName)) {
			return false;
		}
		$nuevo = array('srv' => trim((string)$dbServer), 'bd' => trim((string)$bdName));
		$this->memoria[$nombreConexion] = $nuevo;

		$f = $this->_archivo();
		$fp = @fopen($f . '.lock', 'c');
		if ($fp) {
			@flock($fp, LOCK_EX);
		}
		try {
			$this->diccionario = null;   // releer bajo el bloqueo: otro login pudo escribir
			$dic = $this->_leer();
			$actual = isset($dic[$nombreConexion]) ? $dic[$nombreConexion] : null;
			if (is_array($actual) && ($actual['srv'] ?? '') === $nuevo['srv'] && ($actual['bd'] ?? '') === $nuevo['bd']) {
				return true;   // sin cambios: no se reescribe
			}
			$dic[$nombreConexion] = $nuevo + array('actualizado' => date('Y-m-d H:i:s'));
			ksort($dic);
			$php = "<?php\n// Generado por ConexionesTenant (diccionario nombre_conexion -> servidor/base). No editar a mano.\n"
				. "defined('BASEPATH') or exit('No direct script access allowed');\nreturn " . var_export($dic, true) . ";\n";
			$tmp = $f . '.' . getmypid() . '.tmp';
			if (@file_put_contents($tmp, $php) === false || !@rename($tmp, $f)) {
				@unlink($tmp);
				log_message('error', '[CONEXIONES] No se pudo escribir ' . $f);
				return false;
			}
			if (function_exists('opcache_invalidate')) {
				@opcache_invalidate($f, true);
			}
			$this->diccionario = $dic;
			return true;
		} finally {
			if ($fp) {
				@flock($fp, LOCK_UN);
				@fclose($fp);
			}
		}
	}
}
