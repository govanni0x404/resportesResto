<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Despliegue: aplica las migraciones de application/migrations/ desde la consola (10-11-2026).
 *
 * Es el camino recomendado después de subir código (CONVENCIONES.md, "Qué hacer después de desplegar"): migra
 * antes de que entre el primer usuario, en vez de que lo pague su login.
 *
 *   php index.php despliegue estado                     qué le falta a cada empresa, sin cambiar nada
 *   php index.php despliegue esquema                    aplicar lo pendiente a todas
 *   php index.php despliegue esquema <servidor> <base>  solo esa base (servidor = clave de MIPDV_DB_<SERVIDOR>_*)
 *   php index.php despliegue estado <servidor>          solo las de ese servidor
 *
 * Empresas: las del diccionario local de conexiones (application/cache/conexiones_tenant.php), que se llena solo
 * con cada login. Una empresa que nunca ha entrado desde este servidor se migra en su primer login, o dándole
 * servidor y base a este comando.
 *
 * Las credenciales de los servidores salen del entorno (MIPDV_DB_<SERVIDOR>_*) igual que en la web: si en el
 * servidor están solo como SetEnv de Apache, hay que exportarlas también en la consola.
 *
 * Devuelve 0 si todo fue bien y 1 si algo falló, para encadenarlo:
 *   git pull && php index.php despliegue esquema
 *
 * Solo consola: por HTTP responde 404 (migra bases sin pedir contraseña).
 */
class Despliegue extends CI_Controller
{
	private $fallos = 0;

	public function __construct()
	{
		parent::__construct();
		if (!is_cli()) {
			show_404();
			exit(1);
		}
		$this->load->library('ConexionesTenant');
		$this->load->library('MigracionesResto');
	}

	public function index()
	{
		$this->ayuda();
	}

	public function ayuda()
	{
		$this->linea('resportesResto -- migraciones desde la consola');
		$this->linea('  php index.php despliegue estado [servidor] [base]   ver lo pendiente, sin cambiar nada');
		$this->linea('  php index.php despliegue esquema [servidor] [base]  aplicarlo');
	}

	public function estado($servidor = '', $base = '')
	{
		$this->recorrer((string)$servidor, (string)$base, true);
	}

	public function esquema($servidor = '', $base = '')
	{
		$this->recorrer((string)$servidor, (string)$base, false);
	}

	private function recorrer($servidor, $base, $simular)
	{
		$mig = $this->migracionesresto;
		$this->linea(sprintf('Migraciones %s · versión objetivo %d · %d archivo(s) en application/migrations/',
			$simular ? '(solo revisar)' : '(aplicar)', $mig->objetivo(), count($mig->archivos())));

		$empresas = $this->empresas($servidor, $base);
		if (!$empresas) {
			$this->linea('No hay empresas que recorrer: nadie ha iniciado sesión desde este servidor todavía, o el filtro no coincide.');
			$this->linea('Indica servidor y base:  php index.php despliegue ' . ($simular ? 'estado' : 'esquema') . ' <servidor> <base>');
			exit(1);
		}

		foreach ($empresas as $e) {
			$etiqueta = $e['srv'] . '/' . $e['bd'] . ($e['nombres'] ? ' (' . implode(', ', $e['nombres']) . ')' : '');
			$db = $this->conexionestenant->conectar($e['srv'], $e['bd'], array('db_debug' => false));
			if (!$db || !$db->conn_id) {
				$this->fallos++;
				$this->linea('  FALLO      ' . $etiqueta . ': no se pudo conectar');
				continue;
			}
			$r = $mig->aplicar($db, 'despliegue_cli', $simular);
			if (!$r['ok']) {
				$this->fallos++;
				$this->linea('  FALLO      ' . $etiqueta . ': ' . $r['error']);
			} elseif ($r['ocupada']) {
				$this->linea('  OCUPADA    ' . $etiqueta . ': otro proceso está migrando esta base; vuelve a intentar');
				$this->fallos++;
			} elseif ($simular) {
				$this->linea(sprintf('  %-10s %s · versión %s', $r['pendientes'] ? count($r['pendientes']) . ' FALTAN' : 'al día',
					$etiqueta, $r['version_inicial'] === null ? 'sin tabla' : $r['version_inicial']));
				foreach ($r['pendientes'] as $archivo) $this->linea('               pendiente  ' . $archivo);
			} else {
				$this->linea(sprintf('  %-10s %s · versión %s', $r['aplicadas'] ? count($r['aplicadas']) . ' APLICADAS' : 'al día',
					$etiqueta, $r['version_final']));
				foreach ($r['aplicadas'] as $archivo) $this->linea('               aplicada   ' . $archivo);
				$this->migracionesresto->limpiarFreno($e['srv'], $e['bd']);
			}
			$this->dbmanager->close($e['srv'], $e['bd']);
		}
		$this->linea($this->fallos ? $this->fallos . ' con problemas.' : 'Listo.');
		exit($this->fallos ? 1 : 0);
	}

	/** Pares servidor/base a recorrer: los dados, o los del diccionario local (filtrados). */
	private function empresas($servidor, $base)
	{
		if ($servidor !== '' && $base !== '') {
			return array(array('srv' => $servidor, 'bd' => $base, 'nombres' => array()));
		}
		$lista = array();
		foreach ($this->conexionestenant->todas() as $nombre => $par) {
			if (!is_array($par) || !isset($par['srv'], $par['bd'])) continue;
			if ($servidor !== '' && $par['srv'] !== $servidor) continue;
			$k = $par['srv'] . '|' . $par['bd'];
			if (!isset($lista[$k])) $lista[$k] = array('srv' => (string)$par['srv'], 'bd' => (string)$par['bd'], 'nombres' => array());
			$lista[$k]['nombres'][] = (string)$nombre;
		}
		ksort($lista);
		return array_values($lista);
	}

	private function linea($texto)
	{
		fwrite(STDOUT, $texto . PHP_EOL);
	}
}
