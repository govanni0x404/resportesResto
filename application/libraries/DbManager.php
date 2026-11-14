<?php defined('BASEPATH') OR exit('No direct script access allowed');

/* Copiado de mipdv_web (application/libraries/DbManager.php) el 10-11-2026, sin cambios.
   Diccionario de servidores: las credenciales vienen del entorno (MIPDV_DB_<SERVIDOR>_*),
   y la base de cada empresa la entrega la intranet (validarCliente: con / con_sucursal). */

class DbManager
{

  /** @var CI_Controller */
  protected $CI;

  /**
   * Pool de conexiones ya creadas.
   * Clave típica: "servidor|base|usuario"
   */
  protected $pool = [];

  /**
   * Diccionario de servidores.
   *
   * IMPORTANTE (2026-09-10 — Refactor ENV):
   *   LOS VALORES SENSIBLES (hostname, username, password, port) DEBEN IR
   *   VACÍOS AQUÍ. Se completan DESDE VARIABLES DE ENTORNO (prioridad):
   *     1) Apache2 SetEnv / Nginx fastcgi_param      (producción)
   *     2) Archivo .env en la raíz del proyecto       (localhost dev)
   *   Sólo se dejan DEFAULTs NO SENSIBLES aquí: dbdriver, pconnect,
   *   char_set, dbcollat, save_queries.
   *
   *   Patrón ENV OFICIAL:
   *     MIPDV_DB_<SERVER>_HOSTNAME  /  _PORT  /  _USERNAME  /  _PASSWORD
   *   Ejemplos:
   *     MIPDV_DB_LOCALHOST_HOSTNAME  MIPDV_DB_AWS_USERNAME  MIPDV_DB_AWS_TEST_PORT
   */

  protected $servers = [
    'localhost' => [
      'dbdriver'     => 'postgre',
      'hostname'     => '',      // <-- MIPDV_DB_LOCALHOST_HOSTNAME
      'port'         => null,    // <-- MIPDV_DB_LOCALHOST_PORT   (null = default 5432)
      'username'     => '',      // <-- MIPDV_DB_LOCALHOST_USERNAME
      'password'     => '',      // <-- MIPDV_DB_LOCALHOST_PASSWORD
      'pconnect'     => false,
      'char_set'     => 'utf8',
      'dbcollat'     => 'utf8_general_ci', // (Postgre no usa, CI no se muere)
      'save_queries' => true,
    ],
    'aws' => [
      'dbdriver'     => 'postgre',
      'hostname'     => '',      // <-- MIPDV_DB_AWS_HOSTNAME
      'port'         => null,    // <-- MIPDV_DB_AWS_PORT
      'username'     => '',      // <-- MIPDV_DB_AWS_USERNAME
      'password'     => '',      // <-- MIPDV_DB_AWS_PASSWORD
      'pconnect'     => false,
      'char_set'     => 'utf8',
      'dbcollat'     => 'utf8_general_ci',
      'save_queries' => true,
    ],
    'aws_test' => [
      'dbdriver'     => 'postgre',
      'hostname'     => '',      // <-- MIPDV_DB_AWS_TEST_HOSTNAME
      'port'         => null,    // <-- MIPDV_DB_AWS_TEST_PORT
      'username'     => '',      // <-- MIPDV_DB_AWS_TEST_USERNAME
      'password'     => '',      // <-- MIPDV_DB_AWS_TEST_PASSWORD
      'pconnect'     => false,
      'char_set'     => 'utf8',
      'dbcollat'     => 'utf8_general_ci',
      'save_queries' => true,
    ],
  ];

 

  public function __construct()
  {
    if (!is_array($this->servers)) {
      $this->servers = [];
    }

    // 0) Asegura las 3 claves mínimas (por si alguien comete error borrando una key en
    //    el array $servers: "localhost", "aws", "aws_test" SON OBLIGATORIAS para todo
    //    el proyecto: Mivps, Login, ControlCaja, Dashboard, etc.)
    $this->servers += [
      'localhost' => [
        'dbdriver'     => 'postgre',
        'hostname'     => '',
        'port'         => null,
        'username'     => '',
        'password'     => '',
        'pconnect'     => false,
        'char_set'     => 'utf8',
        'dbcollat'     => 'utf8_general_ci',
        'save_queries' => true,
      ],
      'aws' => [
        'dbdriver'     => 'postgre',
        'hostname'     => '',
        'port'         => null,
        'username'     => '',
        'password'     => '',
        'pconnect'     => false,
        'char_set'     => 'utf8',
        'dbcollat'     => 'utf8_general_ci',
        'save_queries' => true,
      ],
      'aws_test' => [
        'dbdriver'     => 'postgre',
        'hostname'     => '',
        'port'         => null,
        'username'     => '',
        'password'     => '',
        'pconnect'     => false,
        'char_set'     => 'utf8',
        'dbcollat'     => 'utf8_general_ci',
        'save_queries' => true,
      ],
    ];

    $this->CI =& get_instance();

    // 1) Carga opcional de archivo .env de RAÍZ (solo para localhost dev).
    //    NUNCA sobreescribe variables ya definidas en servidor (SetEnv Apache / fastcgi_param Nginx).
    try {
      $this->_loadDotEnvFromRoot();
    } catch (\Throwable $e) {
      // Si el parseo de .env falla (líneas raras), NO queremos matar la app:
      // sigue con las ENV de servidor / hardcoded defaults.
      log_message('error', 'DbManager: _loadDotEnvFromRoot falló: ' . $e->getMessage());
    }

    // 2) Sobreescribe $servers con las variables de entorno (si existen).
    //    Regla de prioridad: ENV servidor > .env > array defaults.
    try {
      $this->_applyEnvOverridesToServers();
    } catch (\Throwable $e) {
      log_message('error', 'DbManager: _applyEnvOverridesToServers falló: ' . $e->getMessage());
    }
  }

  // ==========================================================================
  // ENVIRONMENT LAYER (compatibilidad: FPM + Apache + CLI + Archivo .env local)
  // ==========================================================================

  /**
   * Normaliza la clave de servidor (ej: "aws_test" → "AWS_TEST") para los
   * nombres ENV tipo MIPDV_DB_AWS_TEST_PASSWORD.
   */
  protected function _normalizeServerKeyForEnv($serverKey)
  {
    $k = trim((string)$serverKey);
    if ($k === '') return '';
    $k = preg_replace('/[^a-zA-Z0-9]+/', '_', $k);
    $k = trim($k, '_');
    return strtoupper($k);
  }

  /**
   * Lee una variable de entorno desde las 3 fuentes estándar del proyecto
   * (igual que _mipdv_env_raw de config.php).
   * Prioridad: $_SERVER (SetEnv / fastcgi) > $_ENV > getenv().
   */
  protected function _envReadRaw($key, $default = null)
  {
    $key = (string)$key;
    if ($key === '') return $default;

    // 1) $_SERVER: SetEnv Apache / fastcgi_param Nginx (MÁXIMA prioridad)
    if (isset($_SERVER[$key]) && is_string($_SERVER[$key]) && $_SERVER[$key] !== '') {
      return $_SERVER[$key];
    }
    // 2) $_ENV: variables_order=EGPCS / Dotenv cargado
    if (isset($_ENV[$key]) && is_string($_ENV[$key]) && $_ENV[$key] !== '') {
      return $_ENV[$key];
    }
    // 3) getenv() (PHP CLI, sistema)
    if (is_callable('getenv')) {
      $v = @getenv($key);
      if (is_string($v) && $v !== '') {
        return $v;
      }
    }
    return $default;
  }

  /**
   * Lee entero booleano. "1"/"true"/"on"/"yes" => true, todo lo demás => false
   */
  protected function _envReadBool($key, $default = false)
  {
    $raw = $this->_envReadRaw($key, null);
    if ($raw === null) return $default;
    $v = strtolower(trim((string)$raw));
    return in_array($v, ['1', 'true', 'on', 'yes', 't'], true);
  }

  /**
   * Carga ARCHIVO .env DESDE LA RAÍZ del proyecto, PERO SÓLO AQUELLAS VARIABLES
   * QUE TODAVÍA NO ESTÁN PRESENTES EN $_SERVER / $_ENV / getenv().
   *
   * - Garantiza que fastcgi_param (Nginx) y SetEnv (Apache) GANEN.
   * - Parse simple: líneas # / vacías ignora; K=V / K="V" / K='V'.
   * - Ruta búsqueda: FCPATH/.env, o falla APPPATH/.env.
   */
  protected function _loadDotEnvFromRoot()
  {
    // Construimos rutas candidatas (sin depender de getcwd)
    $paths = [];
    if (defined('FCPATH')) {
      $paths[] = rtrim(FCPATH, '/\\') . '/.env';
    }
    if (defined('APPPATH')) {
      $paths[] = rtrim(APPPATH, '/\\') . '/.env';
      $paths[] = rtrim(APPPATH, '/\\') . '/../.env';
    }
    if (isset($_SERVER['DOCUMENT_ROOT']) && !empty($_SERVER['DOCUMENT_ROOT'])) {
      $paths[] = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/.env';
    }

    $envPath = null;
    foreach ($paths as $p) {
      if (is_file($p) && is_readable($p)) {
        $envPath = $p;
        break;
      }
    }
    if (!$envPath) {
      return;
    }

    $lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
      return;
    }

    foreach ($lines as $rawLine) {
      $line = trim((string)$rawLine);
      if ($line === '' || strpos($line, '#') === 0) {
        continue;
      }
      // Quita comentarios inline
      if (strpos($line, ' #') !== false) {
        $line = rtrim(substr($line, 0, strpos($line, ' #')));
      }
      if (strpos($line, '=') === false) {
        continue;
      }
      list($k, $v) = explode('=', $line, 2);
      $k = trim((string)$k);
      $v = trim((string)$v);
      if ($k === '' || !preg_match('/^[A-Z_][A-Z0-9_]*$/i', $k)) {
        continue;
      }
      // Quita comillas simples/dobles si encierran el value
      if (strlen($v) >= 2) {
        if ((($v[0] === '"') && ($v[strlen($v) - 1] === '"')) ||
            (($v[0] === "'") && ($v[strlen($v) - 1] === "'"))) {
          $v = substr($v, 1, -1);
        }
      }
      // CLAVE: NUNCA sobrescribe si ya existe en servidor/ENV.
      if ($this->_envReadRaw($k, null) !== null) {
        continue;
      }
      // Registra en $_ENV y putenv para que _envReadRaw() la vea luego.
      $_ENV[$k] = $v;
      if (is_callable('putenv')) {
        @putenv($k . '=' . $v);
      }
    }
  }

  /**
   * Recorre $this->servers y sobreescribe campo por campo con lo que exista en ENV.
   *
   * Patrón ENV oficial (NUEVO, uso recomendado):
   *   MIPDV_DB_<SERVER_UPPER>_<CAMPO>
   *   Ejemplos:
   *     MIPDV_DB_LOCALHOST_HOSTNAME       = "datamaule.cl"
   *     MIPDV_DB_LOCALHOST_PORT           = 5432
   *     MIPDV_DB_LOCALHOST_USERNAME       = "ccerda"
   *     MIPDV_DB_LOCALHOST_PASSWORD       = "..."
   *     MIPDV_DB_AWS_HOSTNAME             = "databaseprincipal....rds.amazonaws.com"
   *     MIPDV_DB_AWS_PORT                 = 5432
   *     MIPDV_DB_AWS_USERNAME             = "postgres"
   *     MIPDV_DB_AWS_PASSWORD             = "..."
   *     MIPDV_DB_AWS_TEST_HOSTNAME        = "127.0.0.1"
   *     MIPDV_DB_AWS_TEST_PORT            = 15432
   *     MIPDV_DB_AWS_TEST_USERNAME        = "postgres"
   *     MIPDV_DB_AWS_TEST_PASSWORD        = "..."
   *
   * Campos opcionales que también se pueden sobreescribir:
   *   MIPDV_DB_<SERVER>_DATABASE (default: se pasa por DbManager::get())
   *   MIPDV_DB_<SERVER>_DBDRIVER (default: postgre)
   *   MIPDV_DB_<SERVER>_PCONNECT (0|1, default: 0)
   *   MIPDV_DB_<SERVER>_DB_DEBUG (0|1, default: segun ENVIRONMENT)
   *   MIPDV_DB_<SERVER>_CHAR_SET (default: utf8)
   *   MIPDV_DB_<SERVER>_DBCOLLAT (default: utf8_general_ci)
   *   MIPDV_DB_<SERVER>_SAVE_QUERIES (0|1, default: 1)
   *
   * RETROCOMPATIBILIDAD (legacy keys — usadas antes en datapdv/aws):
   *   AWS_RDS_HOST / AWS_RDS_PORT / AWS_RDS_USER / AWS_RDS_PASS
   *   AWS_TUNNEL_HOST / AWS_TUNNEL_PORT
   *   (Si MIPDV_DB_AWS_* no está definido, se intenta legacy keys.)
   */
  protected function _applyEnvOverridesToServers()
  {
    if (!is_array($this->servers)) {
      return;
    }

    $legacyKeyMap = [
      'aws' => [
        'hostname' => 'AWS_RDS_HOST',
        'port'     => 'AWS_RDS_PORT',
        'username' => 'AWS_RDS_USER',
        'password' => 'AWS_RDS_PASS',
      ],
      'aws_tunnel' => [
        'hostname' => 'AWS_TUNNEL_HOST',
        'port'     => 'AWS_TUNNEL_PORT',
        'username' => 'AWS_RDS_USER',
        'password' => 'AWS_RDS_PASS',
      ],
    ];

    foreach ($this->servers as $serverKey => $srv) {
      $envBase = 'MIPDV_DB_' . $this->_normalizeServerKeyForEnv($serverKey) . '_';
      $leg = $legacyKeyMap[$serverKey] ?? [];

      $candidates = [
        'hostname'     => [$envBase . 'HOSTNAME'],
        'port'         => [$envBase . 'PORT'],
        'username'     => [$envBase . 'USERNAME'],
        'password'     => [$envBase . 'PASSWORD'],
        'database'     => [$envBase . 'DATABASE'],
        'dbdriver'     => [$envBase . 'DBDRIVER'],
        'char_set'     => [$envBase . 'CHAR_SET'],
        'dbcollat'     => [$envBase . 'DBCOLLAT'],
      ];

      if (isset($leg['hostname'])) $candidates['hostname'][] = $leg['hostname'];
      if (isset($leg['port']))     $candidates['port'][]     = $leg['port'];
      if (isset($leg['username'])) $candidates['username'][] = $leg['username'];
      if (isset($leg['password'])) $candidates['password'][] = $leg['password'];

      foreach ($candidates as $field => $envKeysToTry) {
        foreach ($envKeysToTry as $ek) {
          $v = $this->_envReadRaw($ek, null);
          if ($v !== null && is_string($v) && $v !== '') {
            if ($field === 'port') {
              $srv[$field] = (int)$v;
            } else {
              $srv[$field] = $v;
            }
            break; // primera ENV que gana (MIPDV_DB_* > legacy)
          }
        }
      }

      // Booleans
      $bools = [
        'pconnect'     => $envBase . 'PCONNECT',
        'db_debug'     => $envBase . 'DB_DEBUG',
        'save_queries' => $envBase . 'SAVE_QUERIES',
      ];
      foreach ($bools as $field => $ek) {
        $raw = $this->_envReadRaw($ek, null);
        if ($raw !== null) {
          $srv[$field] = $this->_envReadBool($ek, isset($srv[$field]) ? (bool)$srv[$field] : false);
        }
      }

      $this->servers[$serverKey] = $srv;
    }
  }

  public function registerServerConfig($serverKey, array $config)
  {
    $serverKey = trim((string) $serverKey);
    if ($serverKey === '') {
      show_error('DbManager: serverKey es obligatorio');
    }

    $normalized = $config;
    if (!isset($normalized['dbdriver']) || trim((string) $normalized['dbdriver']) === '') {
      $normalized['dbdriver'] = 'postgre';
    }
    if (!isset($normalized['pconnect'])) {
      $normalized['pconnect'] = false;
    }
    if (!isset($normalized['char_set'])) {
      $normalized['char_set'] = 'utf8';
    }
    if (!isset($normalized['dbcollat'])) {
      $normalized['dbcollat'] = 'utf8_general_ci';
    }
    if (!isset($normalized['save_queries'])) {
      $normalized['save_queries'] = true;
    }

    $this->servers[$serverKey] = $normalized;
    return $serverKey;
  }

  /**
   * Obtiene una conexión por servidor + base de datos.
   *
   * @param string $servidorKey  Ej: 'localhost', 'srv_central'
   * @param string $database     Ej: 'base_datos_001'
   * @param array  $overrides    Permite sobreescribir cosas puntuales (opcional)
   * @return CI_DB_query_builder
   */
  /**
   * Claves de servidor conocidas.
   *
   * Lo usa el panel de bitacora para recorrer los tenants: sin esto no hay forma
   * de saber a que servidores se puede preguntar, porque $servers es protegido.
   * Solo lectura de las claves -- nunca de las credenciales.
   */
  public function serverKeys()
  {
    return array_keys(is_array($this->servers) ? $this->servers : []);
  }

  /** True si la clave existe: get() hace show_error() (y corta) con una desconocida. */
  public function serverExists($servidorKey)
  {
    return isset($this->servers[trim((string) $servidorKey)]);
  }

  /**
   * Host y puerto de una clave de servidor. SOLO eso.
   *
   * Lo necesita el panel de bitacora para cruzar el registro de conexiones de la
   * intranet --que entrega `db_ip` -- con las claves que usa miPDV, porque las
   * credenciales estan guardadas por clave y no por IP. Sin este cruce, saber
   * que existe la base `ferrisol_pdv` en 10.0.5.8 no alcanza para conectarse.
   *
   * Devuelve hostname y port, NUNCA username ni password. Mismo criterio que
   * serverKeys(): se expone lo justo para poder preguntar, nada mas.
   *
   * @return array|null  ['hostname' => string, 'port' => int|null] o null si la
   *                     clave no existe.
   */
  public function serverHost($servidorKey)
  {
    $k = trim((string) $servidorKey);
    if (!isset($this->servers[$k])) {
      return null;
    }
    $cfg = $this->servers[$k];
    return [
      'hostname' => isset($cfg['hostname']) ? (string) $cfg['hostname'] : '',
      'port'     => isset($cfg['port']) && $cfg['port'] !== '' ? (int) $cfg['port'] : null,
    ];
  }

  public function get($servidorKey, $database, array $overrides = [])
  {
    $servidorKey = trim((string) $servidorKey);
    $database = trim((string) $database);

    if ($servidorKey === '' || $database === '') {
      show_error('DbManager: servidor y database son obligatorios');
    }

    // 1) busca config base del servidor
    if (!isset($this->servers[$servidorKey])) {
      show_error('DbManager: servidor no existe en diccionario: ' . $servidorKey);
    }
    $srv = $this->servers[$servidorKey];

    // 2) arma config final
    $cfg = $this->buildConfig($srv, $database, $overrides);

    // 3) clave de pool (si quieres separar por usuario/host/puerto también)
    $poolKey = $servidorKey . '|' . $database . '|' . ($cfg['username'] ?? '');

    if (isset($this->pool[$poolKey])) {
      return $this->pool[$poolKey];
    }

    // 4) crea y cachea conexión (TRUE => retorna el objeto DB)
    $db = $this->CI->load->database($cfg, TRUE);

    // Forzar UTF-8 en PostgreSQL (CI3 no lo hace automáticamente como MySQL)
    $db->query("SET client_encoding TO 'UTF8'");

    /* ────────────────────────────────────────────────────────────────────
       QUIEN ESTA TRABAJANDO, para los triggers de auditoria.

       PostgreSQL solo conoce al usuario de la CONEXION, que es el mismo para
       todo el mundo. El usuario de la aplicacion se le pasa en variables de
       sesion; f_producto_auditoria() las lee con current_setting(..., true) y
       registra quien cambio que.

       Se fija al CREAR la conexion. El pool ($this->pool) vive lo que vive la
       peticion --DbManager es una libreria de CI, se instancia por request--,
       asi que en cada peticion la primera llamada a get() de ese tenant pasa
       por aqui y deja la identidad puesta para todas las consultas que vengan
       despues. Si la conexion subyacente fuera persistente, esta llamada pisa
       igual lo que hubiera dejado la peticion anterior.

       Si algo de esto falla no pasa nada: current_setting(..., true) devuelve
       NULL, la auditoria anota id_usuario 0 y deja constancia del rol de base
       en db_usuario. Nunca debe impedir abrir la conexion.
       ──────────────────────────────────────────────────────────────────── */
    $this->_fijarIdentidadSesion($db);

    // Opcional: setear el db_debug según environment
    // $db->db_debug = (ENVIRONMENT !== 'production');

    $this->pool[$poolKey] = $db;
    return $db;

  }

  /**
   * Publica la identidad de la aplicacion en variables de sesion de PostgreSQL.
   *
   * Nombres: mipdv.id_usuario, mipdv.nombre_usuario, mipdv.origen, mipdv.ip.
   * Los consume f_producto_auditoria() (migracion 20260913233000) y cualquier
   * trigger de auditoria que se agregue despues.
   *
   * Silenciosa a proposito: un fallo aqui no puede tumbar la conexion.
   */
  protected function _fijarIdentidadSesion($db)
  {
    try {
      if (!is_object($db) || !method_exists($db, 'query')) {
        return;
      }

      $idUsuario = 0;
      $nombre    = '';
      $CI = $this->CI;
      if (is_object($CI) && isset($CI->session) && is_object($CI->session)
          && method_exists($CI->session, 'userdata')) {
        $idUsuario = (int) $CI->session->userdata('id_usuario');
        $nombre    = (string) $CI->session->userdata('nombre_usuario');
      }

      /* De donde viene el cambio. Sirve para separar en la auditoria lo que
         hizo una persona en el POS de lo que hizo una integracion. */
      $origen = 'pos';
      if (PHP_SAPI === 'cli') {
        $origen = 'cli';
      } elseif (isset($_SERVER['REQUEST_URI']) && stripos((string) $_SERVER['REQUEST_URI'], '/Api') !== false) {
        $origen = 'api';
      }

      $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';

      $db->query(
        'SELECT set_config(?, ?, false), set_config(?, ?, false),
                set_config(?, ?, false), set_config(?, ?, false)',
        array(
          'mipdv.id_usuario',     (string) $idUsuario,
          'mipdv.nombre_usuario', mb_substr($nombre, 0, 120),
          'mipdv.origen',         $origen,
          'mipdv.ip',             mb_substr($ip, 0, 64),
        )
      );
    } catch (Throwable $e) {
      log_message('error', 'DbManager::_fijarIdentidadSesion: ' . $e->getMessage());
    }
  }

  /**
   * Cierra una conexión del pool.
   */
  public function close($servidorKey, $database)
  {
    $servidorKey = trim((string) $servidorKey);
    $database = trim((string) $database);

    foreach ($this->pool as $k => $db) {
      if (strpos($k, $servidorKey . '|' . $database . '|') === 0) {
        if (method_exists($db, 'close'))
          $db->close();
        unset($this->pool[$k]);
      }
    }
  }

  /**
   * Limpia todo el pool.
   */
  public function closeAll()
  {
    foreach ($this->pool as $k => $db) {
      if (method_exists($db, 'close'))
        $db->close();
      unset($this->pool[$k]);
    }
  }

  protected function buildConfig(array $srv, $database, array $overrides = [])
  {
    $cfg = $srv;

    // Base de datos dinámica
    $cfg['database'] = $database;

    // Port normalización: null / "" / "0"  ->  no usar campo en DSN CI3
    if (!isset($cfg['port']) || $cfg['port'] === null || (is_string($cfg['port']) && trim($cfg['port']) === '') || $cfg['port'] === 0) {
      unset($cfg['port']);
    } else {
      $cfg['port'] = (int)$cfg['port'];
    }

    // PostgreSQL (CI3) arma DSN como "host=... port=..." -> NO usar "host:port"
    $dbdriver = strtolower((string) ($cfg['dbdriver'] ?? ''));
    if ($dbdriver === 'postgre' || $dbdriver === 'postgres' || $dbdriver === 'pgsql') {
      if (isset($cfg['hostname']) && is_string($cfg['hostname']) && strpos($cfg['hostname'], ':') !== false) {
        $parts = explode(':', $cfg['hostname'], 2);
        $maybeHost = $parts[0] ?? '';
        $maybePort = $parts[1] ?? '';
        if ($maybeHost !== '' && ctype_digit($maybePort)) {
          $cfg['hostname'] = $maybeHost;
          if (!isset($cfg['port']) || !$cfg['port']) {
            $cfg['port'] = (int) $maybePort;
          }
        }
      }
    } else {
      if (isset($cfg['port']) && $cfg['port']) {
        // Si ya viene con "host:port" no lo dupliques
        if (isset($cfg['hostname']) && is_string($cfg['hostname']) && strpos($cfg['hostname'], ':') === false) {
          $cfg['hostname'] = $cfg['hostname'] . ':' . (int) $cfg['port'];
        }
      }
    }

    // IMPORTANTE: para no caer en el DSN PDO
    $cfg['dsn'] = '';

    // default debug
    if (!isset($cfg['db_debug'])) {
      $cfg['db_debug'] = (ENVIRONMENT !== 'production');
    }

    // overrides al final
    foreach ($overrides as $k => $v)
      $cfg[$k] = $v;

    return $cfg;
  }

}
