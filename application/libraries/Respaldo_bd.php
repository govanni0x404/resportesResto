<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * v1.27.0: respaldos de la base de cada empresa en la carpeta backups/ del mismo sistema.
 *
 *   backups/<servidor>_<base>/<base>_AAAA-MM-DD_HHMM_<clave>.dump
 *
 * - Formato comprimido de pg_dump (-Fc): pesa varias veces menos que un .sql y se recupera con pg_restore.
 * - Se guardan pocos: el último de cada uno de los últimos 7 días, el último de cada una de las últimas 4 semanas y
 *   el último de cada uno de los últimos 3 meses (unos 14 archivos como máximo por empresa). Los demás se borran solos.
 * - El nombre lleva una clave al azar (no se puede adivinar) y la carpeta no se sirve por la web (.htaccess, router.php,
 *   docker/*-seguridad.conf): solo se descarga desde Configuraciones > Respaldos, siendo administrador.
 * - Se hacen solos una vez al día, cuando alguien entra al sistema (Login::respaldoDiario, en segundo plano y sin
 *   frenar la Caja), y también desde la consola (php index.php respaldo todas) o con el botón "Respaldar ahora".
 *
 * Recuperar un respaldo (en el servidor):
 *   createdb -U postgres NOMBRE_NUEVO
 *   pg_restore -U postgres --no-owner -d NOMBRE_NUEVO backups/<carpeta>/<archivo>.dump
 *
 * Necesita pg_dump (paquete postgresql-client). Otra ruta: RESTO_PG_DUMP=/ruta/pg_dump en el .env.php.
 */
class Respaldo_bd
{
	const DIARIOS = 7;
	const SEMANALES = 4;
	const MENSUALES = 3;
	const EXT = '.dump';

	/** Carpeta raíz de los respaldos (la crea con su protección si no existe). */
	public function raiz()
	{
		$d = FCPATH . 'backups';
		if (!is_dir($d)) @mkdir($d, 0770, true);
		if (is_dir($d)) {
			if (!is_file($d . '/.htaccess')) @file_put_contents($d . '/.htaccess', "# Respaldos de las bases: nunca se sirven por la web\nRequire all denied\nDeny from all\n");
			if (!is_file($d . '/index.html')) @file_put_contents($d . '/index.html', '<!DOCTYPE html><title>403</title>');
		}
		return $d;
	}

	public function carpeta($servidor, $base)
	{
		$limpio = function ($t) { return substr(preg_replace('/[^A-Za-z0-9_-]+/', '_', (string)$t), 0, 60); };
		return $this->raiz() . '/' . $limpio($servidor) . '_' . $limpio($base);
	}

	/** Ruta de pg_dump, o '' si no está en este servidor. */
	public function pgDump()
	{
		$propio = trim((string)getenv('RESTO_PG_DUMP'));
		if ($propio !== '' && is_file($propio)) return $propio;
		$win = stripos(PHP_OS, 'WIN') === 0;
		$en = trim((string)@shell_exec($win ? 'where pg_dump 2>NUL' : 'command -v pg_dump 2>/dev/null'));
		if ($en !== '') return strtok($en, "\r\n");
		if ($win) {
			$todas = array_merge((array)glob('C:/Program Files/PostgreSQL/*/bin/pg_dump.exe'), (array)glob('C:/xampp/pgsql/bin/pg_dump.exe'));
			natsort($todas);
			if ($todas) return end($todas);
		}
		foreach (array('/usr/bin/pg_dump', '/usr/local/bin/pg_dump', '/usr/pgsql-10/bin/pg_dump') as $f) if (is_file($f)) return $f;
		return '';
	}

	/**
	 * Respalda la base de la conexión $db (CI_DB abierta de esa empresa).
	 * @return array ['ok' => bool, 'archivo' => nombre, 'bytes' => int, 'segundos' => float, 'error' => texto]
	 */
	public function respaldar($db, $servidor, $base)
	{
		$pgDump = $this->pgDump();
		if ($pgDump === '') return array('ok' => false, 'error' => 'Este servidor no tiene pg_dump (instala el paquete postgresql-client o indica RESTO_PG_DUMP en el .env.php)');
		$dir = $this->carpeta($servidor, $base);
		if (!is_dir($dir) && !@mkdir($dir, 0770, true)) return array('ok' => false, 'error' => 'No se pudo crear la carpeta ' . $dir);

		// Un respaldo a la vez por empresa (dos pestañas o la consola a la misma hora)
		$candado = @fopen($dir . '/.respaldando', 'c');
		if (!$candado || !flock($candado, LOCK_EX | LOCK_NB)) return array('ok' => false, 'ocupado' => true, 'error' => 'Ya se está haciendo un respaldo de esta base');

		@set_time_limit(900);
		$inicio = microtime(true);
		$nombre = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string)$base) . '_' . date('Y-m-d_Hi') . '_' . bin2hex(random_bytes(4)) . self::EXT;
		$final = $dir . '/' . $nombre;
		$tmp = $final . '.tmp';
		$puerto = (string)$db->port !== '' ? (string)$db->port : '5432';
		$cmd = escapeshellarg($pgDump) . ' -Fc -Z 9 --no-owner --no-privileges'
			. ' -h ' . escapeshellarg((string)$db->hostname) . ' -p ' . escapeshellarg($puerto)
			. ' -U ' . escapeshellarg((string)$db->username) . ' -f ' . escapeshellarg($tmp) . ' ' . escapeshellarg((string)$db->database);
		// La clave va por el entorno del proceso (no queda en la lista de procesos ni en el registro)
		$entorno = getenv();
		$entorno = is_array($entorno) ? $entorno : array();
		$entorno['PGPASSWORD'] = (string)$db->password;
		$entorno['PGCONNECT_TIMEOUT'] = '15';
		$p = @proc_open($cmd, array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $tubos, null, $entorno);
		if (!is_resource($p)) {
			flock($candado, LOCK_UN); fclose($candado);
			return array('ok' => false, 'error' => 'No se pudo ejecutar pg_dump');
		}
		fclose($tubos[0]);
		stream_get_contents($tubos[1]);
		$errores = trim((string)stream_get_contents($tubos[2]));
		fclose($tubos[1]); fclose($tubos[2]);
		$codigo = proc_close($p);

		// Un respaldo bueno de pg_dump -Fc empieza con "PGDMP"
		$cabeza = is_file($tmp) ? (string)@file_get_contents($tmp, false, null, 0, 5) : '';
		if ($codigo !== 0 || $cabeza !== 'PGDMP') {
			@unlink($tmp);
			flock($candado, LOCK_UN); fclose($candado);
			$error = $errores !== '' ? preg_replace('/\s+/', ' ', substr($errores, 0, 300)) : 'pg_dump terminó con código ' . $codigo;
			log_message('error', 'Respaldo ' . $servidor . '/' . $base . ': ' . $error);
			return array('ok' => false, 'error' => $error);
		}
		@rename($tmp, $final);
		$borrados = $this->limpiar($dir, $final);
		flock($candado, LOCK_UN); fclose($candado);
		$r = array('ok' => true, 'archivo' => $nombre, 'bytes' => (int)filesize($final), 'segundos' => round(microtime(true) - $inicio, 1), 'borrados' => $borrados);
		log_message('info', 'Respaldo ' . $servidor . '/' . $base . ': ' . $nombre . ' (' . $r['bytes'] . ' bytes, ' . $r['segundos'] . ' s)');
		return $r;
	}

	/** Respaldos de una empresa, del más nuevo al más viejo: [['archivo', 'bytes', 'fecha' (Y-m-d H:i)], ...]. */
	public function lista($servidor, $base)
	{
		return $this->listaDe($this->carpeta($servidor, $base));
	}

	/** Respaldos de una carpeta de backups/ (la consola recorre todas). */
	public function listaDe($dir)
	{
		$res = array();
		foreach ((array)glob($dir . '/*' . self::EXT) as $f) {
			$res[] = array('archivo' => basename($f), 'bytes' => (int)filesize($f), 'fecha' => date('Y-m-d H:i', filemtime($f)), 'ts' => filemtime($f));
		}
		usort($res, function ($a, $b) { return $b['ts'] - $a['ts']; });
		return $res;
	}

	public function hayDeHoy($servidor, $base)
	{
		foreach ($this->lista($servidor, $base) as $r) if (date('Y-m-d', $r['ts']) === date('Y-m-d')) return true;
		return false;
	}

	/** Ruta de un respaldo de esa empresa, o null (el nombre tiene que ser uno de su carpeta: nada de ../). */
	public function ruta($servidor, $base, $archivo)
	{
		$archivo = basename((string)$archivo);
		if (!preg_match('/^[A-Za-z0-9_-]+' . preg_quote(self::EXT, '/') . '$/', $archivo)) return null;
		$f = $this->carpeta($servidor, $base) . '/' . $archivo;
		return is_file($f) ? $f : null;
	}

	/**
	 * Deja el último de cada día (7 días), de cada semana (4) y de cada mes (3); borra los demás.
	 * @return int cuántos borró
	 */
	public function limpiar($dir, $mantener = '')
	{
		$archivos = array();
		foreach ((array)glob($dir . '/*' . self::EXT) as $f) $archivos[$f] = filemtime($f);
		// Más nuevo primero; con la misma hora (dos en el mismo segundo) manda el recién hecho
		uksort($archivos, function ($a, $b) use ($archivos, $mantener) {
			if ($archivos[$a] !== $archivos[$b]) return $archivos[$b] - $archivos[$a];
			if ($a === $mantener) return -1;
			if ($b === $mantener) return 1;
			return strcmp($b, $a);
		});
		$guardar = $mantener !== '' ? array($mantener => true) : array();
		foreach (array(array('Y-m-d', self::DIARIOS), array('o-W', self::SEMANALES), array('Y-m', self::MENSUALES)) as $regla) {
			$vistos = array();
			foreach ($archivos as $f => $ts) {
				$k = date($regla[0], $ts);
				if (isset($vistos[$k])) continue;
				if (count($vistos) >= $regla[1]) break;
				$vistos[$k] = true;
				$guardar[$f] = true;
			}
		}
		$n = 0;
		foreach ($archivos as $f => $ts) if (!isset($guardar[$f]) && @unlink($f)) $n++;
		// Restos de un respaldo que se cortó (más de un día)
		foreach ((array)glob($dir . '/*' . self::EXT . '.tmp') as $f) if (filemtime($f) < time() - 86400) @unlink($f);
		return $n;
	}
}
