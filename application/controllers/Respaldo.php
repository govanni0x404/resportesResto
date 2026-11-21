<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * v1.27.0: respaldos de las bases desde la consola (ver libraries/Respaldo_bd.php).
 *
 *   php index.php respaldo todas                     respaldar todas las empresas que han entrado desde este servidor
 *   php index.php respaldo base <servidor> <base>    solo esa base (servidor = clave de MIPDV_DB_<SERVIDOR>_*)
 *   php index.php respaldo lista                     ver los respaldos guardados y cuánto pesan
 *
 * En el VPS conviene dejarlo todas las noches en el cron (crontab -e), por ejemplo a las 4 de la mañana:
 *   0 4 * * * cd /ruta/del/proyecto && php index.php respaldo todas >> application/logs/respaldos.log 2>&1
 * Sin cron igual se hace uno al día: el primero que entra al sistema lo dispara en segundo plano.
 *
 * Solo consola: por HTTP responde 404.
 */
class Respaldo extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		if (!is_cli()) {
			show_404();
			exit(1);
		}
		$this->load->library('ConexionesTenant');
		$this->load->library('Respaldo_bd');
	}

	public function index()
	{
		$this->linea('Respaldos de las bases (carpeta backups/)');
		$this->linea('  php index.php respaldo todas                    respaldar todas las empresas');
		$this->linea('  php index.php respaldo base <servidor> <base>   solo esa base');
		$this->linea('  php index.php respaldo lista                    ver los respaldos guardados');
	}

	public function todas()
	{
		$this->recorrer($this->empresas());
	}

	public function base($servidor = '', $base = '')
	{
		if ($servidor === '' || $base === '') {
			$this->linea('Falta el servidor y la base:  php index.php respaldo base <servidor> <base>');
			exit(1);
		}
		$this->recorrer(array(array('srv' => (string)$servidor, 'bd' => (string)$base, 'nombres' => array())));
	}

	public function lista()
	{
		$total = 0;
		foreach ((array)glob($this->respaldo_bd->raiz() . '/*', GLOB_ONLYDIR) as $dir) {
			$l = $this->respaldo_bd->listaDe($dir);
			$this->linea(basename($dir) . ': ' . count($l) . ' respaldo(s)');
			foreach ($l as $r) {
				$total += $r['bytes'];
				$this->linea(sprintf('   %s  %10s  %s', $r['fecha'], $this->peso($r['bytes']), $r['archivo']));
			}
		}
		$this->linea('Total en backups/: ' . $this->peso($total));
	}

	private function recorrer($empresas)
	{
		if (!$empresas) {
			$this->linea('No hay empresas: nadie ha entrado desde este servidor todavía. Usa: php index.php respaldo base <servidor> <base>');
			exit(1);
		}
		$fallos = 0;
		foreach ($empresas as $e) {
			$etiqueta = $e['srv'] . '/' . $e['bd'] . ($e['nombres'] ? ' (' . implode(', ', $e['nombres']) . ')' : '');
			// Una base que ya no existe (o un servidor caído) no corta el recorrido: se anota y se sigue con las demás
			try {
				$db = @$this->conexionestenant->conectar($e['srv'], $e['bd'], array('db_debug' => false));
			} catch (Throwable $ex) {
				$db = null;
			}
			if (!$db || !$db->conn_id) {
				$fallos++;
				$this->linea('  FALLO  ' . $etiqueta . ': no se pudo conectar');
				continue;
			}
			$r = $this->respaldo_bd->respaldar($db, $e['srv'], $e['bd']);
			if ($r['ok']) {
				$this->linea(sprintf('  OK     %s · %s · %s s%s', $etiqueta, $this->peso($r['bytes']), $r['segundos'],
					$r['borrados'] ? ' · ' . $r['borrados'] . ' viejo(s) borrado(s)' : ''));
			} else {
				$fallos++;
				$this->linea('  FALLO  ' . $etiqueta . ': ' . $r['error']);
			}
			$this->dbmanager->close($e['srv'], $e['bd']);
		}
		$this->linea($fallos ? $fallos . ' con problemas.' : 'Listo.');
		exit($fallos ? 1 : 0);
	}

	/** Las empresas del diccionario local de conexiones (se llena con cada login), sin repetir servidor/base. */
	private function empresas()
	{
		$lista = array();
		foreach ($this->conexionestenant->todas() as $nombre => $par) {
			if (!is_array($par) || !isset($par['srv'], $par['bd'])) continue;
			$k = $par['srv'] . '|' . $par['bd'];
			if (!isset($lista[$k])) $lista[$k] = array('srv' => (string)$par['srv'], 'bd' => (string)$par['bd'], 'nombres' => array());
			$lista[$k]['nombres'][] = (string)$nombre;
		}
		ksort($lista);
		return array_values($lista);
	}

	private function peso($bytes)
	{
		return $bytes >= 1048576 ? number_format($bytes / 1048576, 1, ',', '.') . ' MB' : number_format($bytes / 1024, 0, ',', '.') . ' KB';
	}

	private function linea($texto)
	{
		fwrite(STDOUT, $texto . PHP_EOL);
	}
}
