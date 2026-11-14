<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Ejecutor de migraciones de resportesResto (10-11-2026).
 *
 * Aplica los archivos de application/migrations/ (formato CI3, ver CONVENCIONES.md y LEEME_RESTO.md) a la base
 * de una empresa. Es una versión acotada del AutoMigrationHook de mipdv_web, con sus mismas lecciones:
 *
 *  - Solo sube: ejecuta las migraciones con versión mayor que la grabada y menor o igual que la objetivo
 *    (migration_version, que config/migration.php calcula como el máximo de la carpeta). Nunca pide version(N)
 *    con N menor o igual que la actual, así que down() no corre nunca.
 *  - Una a una, releyendo la versión justo antes de cada una. Si una falla, se corta ahí: la base queda en un
 *    punto conocido y se reintenta después.
 *  - db_debug en FALSE mientras corren: con TRUE un error de SQL imprime la página de error y corta la petición.
 *    Así query() devuelve FALSE y la migración lanza (Regla 7).
 *  - Un solo proceso por base a la vez (pg_try_advisory_lock) y lock_timeout de 10 s: un ALTER que no consigue
 *    su lock falla en vez de dejar en cola a la caja (Regla 6).
 *  - La versión va en la tabla migrations_resto (config migration_table), no en "migrations".
 *
 * Dónde corre (lista blanca, como en miPDV):
 *  - Web: Login::postLoginWarmup, que menuFooter llama por AJAX una vez por sesión y empresa. No bloquea el
 *    login ni la página. Si falla, freno progresivo por base (30 s, 1, 2, 5, 10, 15 min).
 *  - Consola: php index.php despliegue esquema (controllers/Despliegue.php).
 *
 * Bitácora: application/logs/migraciones_AAAAMM.log, una línea JSON por migración aplicada o fallida.
 */
class MigracionesResto
{
	/** Clave del candado de PostgreSQL que asegura un solo proceso migrando cada base. */
	const CANDADO = 734190211;

	/** Freno tras fallos seguidos, en segundos. */
	private static $esperas = array(0, 30, 60, 120, 300, 600, 900);

	/** @var CI_Controller */
	private $CI;
	private $habilitadas = false;
	private $tabla = 'migrations_resto';
	private $carpeta;
	private $objetivo = 0;

	public function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->config->load('migration', true);
		$cfg = (array)$this->CI->config->item('migration');
		$this->habilitadas = !empty($cfg['migration_enabled']);
		if (!empty($cfg['migration_table']) && preg_match('/^[a-z_][a-z0-9_]*$/', (string)$cfg['migration_table'])) {
			$this->tabla = (string)$cfg['migration_table'];
		}
		$this->carpeta = rtrim(!empty($cfg['migration_path']) ? (string)$cfg['migration_path'] : APPPATH . 'migrations/', '/') . '/';
		$this->objetivo = isset($cfg['migration_version']) ? (int)$cfg['migration_version'] : 0;
	}

	public function objetivo()
	{
		return $this->objetivo;
	}

	/** Migraciones de la carpeta hasta la versión objetivo: [version => archivo], en orden. */
	public function archivos()
	{
		$out = array();
		foreach ((array)@scandir($this->carpeta) as $f) {
			if (preg_match('/^(\d{14})_\w+\.php$/', (string)$f, $m) && (int)$m[1] <= $this->objetivo) {
				$out[(int)$m[1]] = (string)$f;
			}
		}
		ksort($out);
		return $out;
	}

	/** Versión grabada en la base; null si la tabla todavía no existe. Solo lee: no crea nada. */
	public function versionActual($db)
	{
		$r = $db->query('SELECT to_regclass(?) IS NOT NULL AS hay', array('public.' . $this->tabla));
		$f = $r ? $r->row() : null;
		if (!$f || !($f->hay === 't' || $f->hay === true)) {
			return null;
		}
		$r = $db->query('SELECT max(version) AS v FROM public.' . $this->tabla);
		$f = $r ? $r->row() : null;
		return ($f && $f->v !== null) ? (int)$f->v : 0;
	}

	/** Lo que le falta a esa base: [version => archivo]. */
	public function pendientes($db)
	{
		$actual = (int)$this->versionActual($db);
		$out = array();
		foreach ($this->archivos() as $v => $archivo) {
			if ($v > $actual) $out[$v] = $archivo;
		}
		return $out;
	}

	/**
	 * Aplica lo pendiente a esa base. Nunca lanza: devuelve
	 * ok, base, version_inicial, version_final, pendientes[], aplicadas[], error, ocupada (otro proceso migrando).
	 */
	public function aplicar($db, $origen, $simular = false)
	{
		$res = array(
			'ok' => true, 'base' => is_object($db) ? (string)$db->database : '', 'version_inicial' => null,
			'version_final' => null, 'pendientes' => array(), 'aplicadas' => array(), 'error' => '', 'ocupada' => false,
		);
		if (!is_object($db)) {
			$res['ok'] = false;
			$res['error'] = 'sin conexión a la base';
			return $res;
		}
		if (!$this->habilitadas || $this->objetivo <= 0) {
			$res['ok'] = false;
			$res['error'] = 'migraciones deshabilitadas en config/migration.php';
			return $res;
		}

		$debugAntes = $db->db_debug;
		$db->db_debug = false;
		$candado = false;
		$timeoutAntes = null;
		$cambiado = false;
		$dbAntes = null;
		$forgeAntes = null;
		try {
			$res['version_inicial'] = $this->versionActual($db);
			$res['version_final'] = $res['version_inicial'];
			$res['pendientes'] = array_values($this->pendientes($db));
			if (!$res['pendientes'] || $simular) {
				return $res;
			}

			// Un solo proceso por base: si otro ya está migrando, este no espera
			$r = $db->query('SELECT pg_try_advisory_lock(?) AS ok', array(self::CANDADO));
			$f = $r ? $r->row() : null;
			if (!$f || !($f->ok === 't' || $f->ok === true)) {
				$res['ocupada'] = true;
				return $res;
			}
			$candado = true;

			// Un ALTER que no consigue su lock en 10 s falla en vez de bloquear a los que vienen detrás (Regla 6)
			$r = $db->query("SELECT current_setting('lock_timeout') AS v");
			$f = $r ? $r->row() : null;
			$timeoutAntes = $f ? (string)$f->v : null;
			$db->query("SELECT set_config('lock_timeout', '10s', false)");

			$this->asegurarTabla($db);

			// CI_Migration trabaja con $CI->db: mientras corre se le da la base de la empresa
			$dbAntes = isset($this->CI->db) ? $this->CI->db : null;
			$forgeAntes = isset($this->CI->dbforge) ? $this->CI->dbforge : null;
			$this->CI->db = $db;
			$cambiado = true;
			$this->CI->dbforge = $this->CI->load->dbforge($db, true);
			$this->CI->load->library('migration');

			foreach ($this->pendientes($db) as $version => $archivo) {
				// Se relee justo antes: nunca se pide una versión menor o igual que la grabada (eso ejecutaría down())
				if ($version <= (int)$this->versionActual($db)) {
					continue;
				}
				$t0 = microtime(true);
				$error = '';
				ob_start();
				try {
					$r = $this->CI->migration->version($version);
					if ($r === false) {
						$error = (string)$this->CI->migration->error_string();
					}
				} catch (Throwable $e) {
					$r = false;
					$error = $e->getMessage();
				}
				$salida = trim((string)ob_get_clean());
				$ms = (int)round((microtime(true) - $t0) * 1000);
				$res['version_final'] = $this->versionActual($db);
				if ($r === false || (int)$res['version_final'] < $version) {
					throw new RuntimeException($archivo . ': ' . ($error !== '' ? $error : 'la versión no quedó registrada'));
				}
				$res['aplicadas'][] = $archivo;
				$this->bitacora('info', array(
					'evento' => 'aplicada', 'base' => $res['base'], 'archivo' => $archivo, 'ms' => $ms,
					'origen' => $origen, 'salida' => mb_substr($salida, 0, 500),
				));
			}
		} catch (Throwable $e) {
			$res['ok'] = false;
			$res['error'] = $e->getMessage();
			$this->bitacora('error', array(
				'evento' => 'fallo', 'base' => $res['base'], 'origen' => $origen, 'error' => $res['error'],
				'version' => $res['version_final'],
			));
		} finally {
			if ($cambiado) {
				if ($dbAntes === null) unset($this->CI->db); else $this->CI->db = $dbAntes;
				if ($forgeAntes === null) unset($this->CI->dbforge); else $this->CI->dbforge = $forgeAntes;
			}
			try {
				if ($timeoutAntes !== null) {
					$db->query("SELECT set_config('lock_timeout', ?, false)", array($timeoutAntes));
				}
				if ($candado) {
					$db->query('SELECT pg_advisory_unlock(?)', array(self::CANDADO));
				}
			} catch (Throwable $e) {
				log_message('error', 'MigracionesResto: no se pudo soltar el candado: ' . $e->getMessage());
			}
			$db->db_debug = $debugAntes;
		}
		return $res;
	}

	/** Crea la tabla de versión si falta (CREATE TABLE IF NOT EXISTS no toma locks si ya existe). */
	private function asegurarTabla($db)
	{
		$t = 'public.' . $this->tabla;
		if ($db->query("CREATE TABLE IF NOT EXISTS $t (version bigint NOT NULL)") === false
			|| $db->query("INSERT INTO $t (version) SELECT 0 WHERE NOT EXISTS (SELECT 1 FROM $t)") === false) {
			$err = $db->error();
			throw new RuntimeException("no se pudo crear la tabla $t: " . (is_array($err) ? (string)$err['message'] : ''));
		}
	}

	// ======================================================================
	// Web: una vez por sesión y empresa (menuFooter -> Login::postLoginWarmup)
	// ======================================================================

	private function claveSesion($srv, $bd)
	{
		return 'migraciones_' . substr(md5($srv . '|' . $bd), 0, 12);
	}

	/** ¿Esta sesión todavía no revisó las migraciones de su empresa? No toca la base. */
	public function revisarEnSesion()
	{
		$s = isset($this->CI->session) ? $this->CI->session : null;
		if (!$this->habilitadas || $this->objetivo <= 0 || !$s) {
			return false;
		}
		$srv = (string)$s->userdata('db_server');
		$bd = (string)$s->userdata('bd_name');
		if ($srv === '' || $bd === '' || strlen((string)$s->userdata('nombre_usuario')) === 0) {
			return false;
		}
		return (int)$s->userdata($this->claveSesion($srv, $bd)) !== $this->objetivo;
	}

	/** Aplica lo pendiente en la base de la empresa de la sesión (Login::postLoginWarmup). */
	public function aplicarEnSesion()
	{
		if (!$this->revisarEnSesion()) {
			return array('ok' => true, 'aplicadas' => array(), 'omitida' => 'ya_revisada');
		}
		$s = $this->CI->session;
		$srv = (string)$s->userdata('db_server');
		$bd = (string)$s->userdata('bd_name');

		$this->CI->load->helper('tenant');
		$db = get_tenant_db($this->CI);

		// Esta sesión no lo vuelve a pedir; si falla, se reintenta en el próximo login (de cualquier usuario)
		$s->set_userdata($this->claveSesion($srv, $bd), $this->objetivo);
		// Se libera la sesión: las demás peticiones de este usuario no esperan a que terminen las migraciones
		session_write_close();

		$freno = $this->leerFreno($srv, $bd);
		if ($freno['faltan'] > 0) {
			return array('ok' => false, 'aplicadas' => array(), 'omitida' => 'freno',
				'error' => 'La última actualización de la base falló; se reintenta en ' . $freno['faltan'] . ' s.');
		}

		$res = $this->aplicar($db, 'warmup');
		if (!$res['ok']) {
			$this->anotarFallo($srv, $bd, $freno);
		} elseif (!$res['ocupada']) {
			$this->limpiarFreno($srv, $bd);
		}
		return $res;
	}

	// ======================================================================
	// Freno tras fallos (por base, compartido entre usuarios)
	// ======================================================================

	private function archivoFreno($srv, $bd)
	{
		return APPPATH . 'cache/migraciones_' . md5($srv . '|' . $bd) . '.json';
	}

	private function leerFreno($srv, $bd)
	{
		$e = array('intentos' => 0, 'ultimo' => 0, 'objetivo' => $this->objetivo, 'faltan' => 0);
		$datos = @json_decode((string)@file_get_contents($this->archivoFreno($srv, $bd)), true);
		// Un despliegue con otra versión objetivo empieza de cero
		if (is_array($datos) && (int)($datos['objetivo'] ?? 0) === $this->objetivo) {
			$e['intentos'] = (int)($datos['intentos'] ?? 0);
			$e['ultimo'] = (int)($datos['ultimo'] ?? 0);
		}
		$espera = self::$esperas[min($e['intentos'], count(self::$esperas) - 1)];
		$e['faltan'] = max(0, $espera - (time() - $e['ultimo']));
		return $e;
	}

	private function anotarFallo($srv, $bd, array $freno)
	{
		@file_put_contents($this->archivoFreno($srv, $bd), json_encode(array(
			'intentos' => $freno['intentos'] + 1, 'ultimo' => time(), 'objetivo' => $this->objetivo,
		)), LOCK_EX);
	}

	public function limpiarFreno($srv, $bd)
	{
		$f = $this->archivoFreno($srv, $bd);
		if (is_file($f)) @unlink($f);
	}

	/** Una línea JSON por evento en application/logs/migraciones_AAAAMM.log (y los errores también en el log de CI). */
	private function bitacora($nivel, array $datos)
	{
		$datos = array('fecha' => date('Y-m-d H:i:s'), 'nivel' => $nivel) + $datos;
		if (isset($this->CI->session) && !is_cli()) {
			$datos['usuario'] = (string)$this->CI->session->userdata('nombre_usuario');
		}
		@file_put_contents(APPPATH . 'logs/migraciones_' . date('Ym') . '.log',
			json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);
		if ($nivel === 'error') {
			log_message('error', 'MigracionesResto: ' . json_encode($datos, JSON_UNESCAPED_UNICODE));
		}
	}
}
