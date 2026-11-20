<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Tipos de mesa (19-11-2026, migración 20261119110000): Mantención > Recintos > Tipos de mesa. El tipo de cada mesa
 * (mesa.id_tipo_mesa_web, se elige en Herramientas > Mesas) cambia su nombre: "Mesa " + texto + número.
 * Ej: sin tipo o con texto vacío => "Mesa 1"; texto "D" => "Mesa D1"; texto "EVENT" con espacio => "Mesa EVENT 1".
 * El nombre propio del plano de la Caja (mesa_plano.nombre) sigue mandando sobre este. Ver nombreSql / nombre.
 */
class Mtipomesa extends CI_Model
{
	private $db2 = null;
	private $hay = null;
	private $hayMesa = null;
	private $hayDefecto = null;
	private $idDefecto = null;

	/** ¿Está la tabla de la migración 20261119110000? */
	public function disponible()
	{
		if ($this->hay === null) {
			$this->hay = (bool)$this->q("SELECT 1 FROM information_schema.columns WHERE table_schema = 'public'
				AND table_name = 'tipo_mesa_web' AND column_name = 'espacio'")->row();
		}
		return $this->hay;
	}

	/** ¿Tiene mesa la columna con su tipo? */
	public function conMesa()
	{
		if ($this->hayMesa === null) {
			$this->hayMesa = $this->disponible() && (bool)$this->q("SELECT 1 FROM information_schema.columns WHERE table_schema = 'public'
				AND table_name = 'mesa' AND column_name = 'id_tipo_mesa_web'")->row();
		}
		return $this->hayMesa;
	}

	/** v1.22.0: ¿está la columna del tipo por defecto (migración 20261120120000)? */
	public function conDefecto()
	{
		if ($this->hayDefecto === null) {
			$this->hayDefecto = $this->disponible() && (bool)$this->q("SELECT 1 FROM information_schema.columns WHERE table_schema = 'public'
				AND table_name = 'tipo_mesa_web' AND column_name = 'por_defecto'")->row();
		}
		return $this->hayDefecto;
	}

	/** v1.22.0: id del tipo por defecto ("General"); 0 si no hay. */
	public function idDefecto()
	{
		if ($this->idDefecto === null) {
			$r = $this->conDefecto() ? $this->q('SELECT id_tipo_mesa_web FROM tipo_mesa_web WHERE por_defecto ORDER BY id_tipo_mesa_web LIMIT 1')->row() : null;
			$this->idDefecto = $r ? (int)$r->id_tipo_mesa_web : 0;
		}
		return $this->idDefecto;
	}

	/**
	 * v1.22.0: el tipo de la mesa en SQL. Una mesa sin tipo (ej. creada por el POS de escritorio) cuenta como del tipo
	 * por defecto. Sin la migración, la columna tal cual.
	 */
	public function idSql($mesa = 'm')
	{
		if (!$this->conMesa()) return '0';
		$d = $this->idDefecto();
		return $d > 0 ? "COALESCE($mesa.id_tipo_mesa_web, $d)" : "COALESCE($mesa.id_tipo_mesa_web, 0)";
	}

	/** v1.22.0: el tipo que se graba en la mesa: el elegido, o el por defecto si viene 0. NULL si no hay ninguno. */
	public function idParaGrabar($idTipo)
	{
		$idTipo = (int)$idTipo;
		if ($idTipo > 0) return $idTipo;
		$d = $this->idDefecto();
		return $d > 0 ? $d : null;
	}

	private function exigir()
	{
		if (!$this->disponible()) {
			throw new Exception('Los tipos de mesa aún no están disponibles: falta actualizar la base de datos. Vuelve a ingresar al sistema en unos minutos.', 409);
		}
	}

	/** El nombre de una mesa según su tipo (sin el nombre propio del plano). */
	public static function nombre($idMesa, $prefijo, $espacio)
	{
		$prefijo = trim((string)$prefijo);
		return 'Mesa ' . ($prefijo === '' ? '' : $prefijo . ($espacio ? ' ' : '')) . (int)$idMesa;
	}

	/**
	 * Lo mismo en SQL, para los SELECT de mesas: $mesa = alias de mesa, $tipo = alias de tipo_mesa_web (unido con
	 * joinSql). Sin la migración, "Mesa N" como siempre.
	 */
	public function nombreSql($mesa = 'm', $tipo = 'tm')
	{
		if (!$this->conMesa()) return "('Mesa ' || $mesa.id_mesa)";
		return "('Mesa ' || CASE WHEN COALESCE(trim($tipo.prefijo), '') = '' THEN ''
			ELSE trim($tipo.prefijo) || CASE WHEN $tipo.espacio IS TRUE THEN ' ' ELSE '' END END || $mesa.id_mesa)";
	}

	public function joinSql($mesa = 'm', $tipo = 'tm')
	{
		return $this->conMesa() ? "LEFT JOIN tipo_mesa_web $tipo ON $tipo.id_tipo_mesa_web = " . $this->idSql($mesa) : '';
	}

	/** Pantalla de Mantención: los tipos con cuántas mesas tiene cada uno. */
	public function listar()
	{
		$this->exigir();
		$cuenta = $this->conMesa() ? '(SELECT count(*) FROM mesa m WHERE ' . $this->idSql('m') . ' = t.id_tipo_mesa_web AND m.vigente IS NOT FALSE)' : '0';
		$res = array();
		// v1.22.0: el por defecto va primero
		$orden = $this->conDefecto() ? 't.por_defecto DESC, ' : '';
		foreach ($this->q("SELECT t.*, $cuenta AS mesas FROM tipo_mesa_web t ORDER BY {$orden}t.vigente DESC, t.nombre, t.id_tipo_mesa_web")->result() as $f) {
			$res[] = $this->fila($f) + array('mesas' => (int)$f->mesas);
		}
		return array('tipos' => $res);
	}

	/** Para el select de Herramientas > Mesas: solo los vigentes (y el que ya tenga la mesa, aunque no lo esté). */
	public function opciones()
	{
		if (!$this->disponible()) return array();
		$res = array();
		$orden = $this->conDefecto() ? 'por_defecto DESC, ' : '';
		foreach ($this->q("SELECT * FROM tipo_mesa_web ORDER BY {$orden}nombre, id_tipo_mesa_web")->result() as $f) $res[] = $this->fila($f);
		return $res;
	}

	public function existe($id)
	{
		return $this->disponible() && (int)$id > 0
			&& (bool)$this->q('SELECT 1 FROM tipo_mesa_web WHERE id_tipo_mesa_web = ?', array((int)$id))->row();
	}

	public function guardar($d)
	{
		$this->exigir();
		$id = (int)(isset($d['id']) ? $d['id'] : 0);
		$nombre = mb_substr(trim(preg_replace('/\s+/u', ' ', (string)(isset($d['nombre']) ? $d['nombre'] : ''))), 0, 40);
		if ($nombre === '') throw new Exception('Escribe el nombre del tipo de mesa', 400);
		$prefijo = mb_substr(trim(preg_replace('/\s+/u', ' ', (string)(isset($d['prefijo']) ? $d['prefijo'] : ''))), 0, 20);
		if ($prefijo !== '' && !preg_match('/^[\p{L}\p{N} ._#-]+$/u', $prefijo)) {
			throw new Exception('El texto del nombre solo puede tener letras, números, espacios, punto, guion o #', 400);
		}
		$espacio = $this->si(isset($d['espacio']) ? $d['espacio'] : false);
		$vigente = $this->si(isset($d['vigente']) ? $d['vigente'] : true);
		$otro = $this->q('SELECT id_tipo_mesa_web FROM tipo_mesa_web WHERE upper(nombre) = upper(?) AND id_tipo_mesa_web <> ?', array($nombre, $id))->row();
		if ($otro) throw new Exception('Ya hay un tipo de mesa llamado "' . $nombre . '"', 409);
		$b = array($nombre, $prefijo, $espacio ? 't' : 'f', $vigente ? 't' : 'f');
		if ($id > 0) {
			if (!$this->existe($id)) throw new Exception('El tipo de mesa no existe', 404);
			// v1.22.0: el tipo por defecto no se desactiva (las mesas sin tipo son de él)
			if (!$vigente && $id === $this->idDefecto()) {
				throw new Exception('El tipo por defecto no se puede desactivar: las mesas sin otro tipo son de él', 409);
			}
			$this->q('UPDATE tipo_mesa_web SET nombre = ?, prefijo = ?, espacio = ?, vigente = ?, fecha_modificacion = now() WHERE id_tipo_mesa_web = ?',
				array_merge($b, array($id)));
		} else {
			$id = (int)$this->q('INSERT INTO tipo_mesa_web (nombre, prefijo, espacio, vigente) VALUES (?, ?, ?, ?) RETURNING id_tipo_mesa_web', $b)->row()->id_tipo_mesa_web;
		}
		return array('id' => $id);
	}

	private function fila($f)
	{
		return array(
			'id' => (int)$f->id_tipo_mesa_web, 'nombre' => (string)$f->nombre, 'prefijo' => (string)$f->prefijo,
			'espacio' => $this->si($f->espacio), 'vigente' => $this->si($f->vigente),
			'defecto' => isset($f->por_defecto) && $this->si($f->por_defecto),   // v1.22.0
			'ejemplo' => self::nombre(1, $f->prefijo, $this->si($f->espacio)),
		);
	}

	private function si($v)
	{
		return $v === true || $v === 't' || $v === 'true' || $v === '1' || $v === 1 || $v === 'on';
	}

	private function db()
	{
		if ($this->db2 === null) {
			$this->load->helper('tenant');
			$this->db2 = get_tenant_db($this);
		}
		return $this->db2;
	}

	private function q($sql, $binds = array())
	{
		$db = $this->db();
		// La conexión es la misma de los otros modelos: db_debug se apaga solo para esta consulta
		$antes = $db->db_debug;
		$db->db_debug = FALSE;
		$r = $db->query($sql, $binds);
		$db->db_debug = $antes;
		if ($r === FALSE) {
			$e = $db->error();
			log_message('error', 'Mtipomesa: ' . $e['message'] . ' | ' . $sql);
			throw new Exception('No se pudo completar la operación en la base de datos', 500);
		}
		return $r;
	}
}
