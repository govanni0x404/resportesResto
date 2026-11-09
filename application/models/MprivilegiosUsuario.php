<?php
defined('BASEPATH') or exit('No direct script access allowed');

/** Permisos de la web por rol (tabla permiso_web, ver sql/permiso_web.sql). */
class MprivilegiosUsuario extends CI_Model
{
	private function db()
	{
		$this->load->helper(array('tenant', 'permisos_web'));
		return get_tenant_db($this);
	}

	public function hayTabla()
	{
		return permisos_web_tabla($this->db());
	}

	private function exigirTabla($db)
	{
		if (!permisos_web_tabla($db)) {
			throw new Exception('Falta crear la tabla de permisos: corre el script sql/permiso_web.sql en la base de datos', 409);
		}
	}

	public function roles()
	{
		$filas = $this->db()->query('SELECT id_rol, nombre_rol FROM rol WHERE id_rol <> 1 ORDER BY id_rol')->result();
		foreach ($filas as $f) $f->id_rol = (int)$f->id_rol;
		return $filas;
	}

	public function existeRol($idRol)
	{
		return (bool)$this->db()->query('SELECT 1 FROM rol WHERE id_rol = ?', array((int)$idRol))->row();
	}

	/** modulo en minúsculas => bool */
	public function accesos($idRol)
	{
		$db = $this->db();
		$this->exigirTabla($db);
		$res = array();
		foreach ($db->query('SELECT modulo, autoriza FROM permiso_web WHERE id_rol = ?', array((int)$idRol))->result() as $f) {
			$res[strtolower($f->modulo)] = ($f->autoriza === 't' || $f->autoriza === true);
		}
		return $res;
	}

	public function guardar($idRol, $modulos, $autoriza)
	{
		$db = $this->db();
		$this->exigirTabla($db);
		$debug = $db->db_debug;
		$db->db_debug = false;
		$db->trans_begin();
		foreach ($modulos as $m) {
			$ok = $db->query('INSERT INTO permiso_web (id_rol, modulo, autoriza) VALUES (?, ?, ?)
				ON CONFLICT (id_rol, modulo) DO UPDATE SET autoriza = EXCLUDED.autoriza',
				array((int)$idRol, (string)$m, $autoriza ? 't' : 'f'));
			if ($ok === false) {
				$e = $db->error();
				$db->trans_rollback();
				$db->db_debug = $debug;
				throw new Exception('permiso_web: ' . $e['message'], 500);
			}
		}
		$db->trans_commit();
		$db->db_debug = $debug;
	}
}
