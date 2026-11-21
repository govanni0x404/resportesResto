<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * v1.28.0: reservas de mesas (tabla reserva_web, migración 20261121130000).
 * Estados: pendiente (por llegar), llego, cancelada, no_vino.
 */
class Mreservas extends CI_Model
{
	const ESTADOS = array('pendiente', 'llego', 'cancelada', 'no_vino');

	private function db()
	{
		$this->load->helper('tenant');
		return get_tenant_db($this);
	}

	public function disponible()
	{
		return $this->db()->table_exists('reserva_web');
	}

	/** v1.29.0: ¿está encendida en Configuraciones > Cocina y reservas? */
	public function activa()
	{
		$this->load->helper('permisos_web');
		return opcion_config_activa($this, 'usar_reservas_web');
	}

	/** Reservas de un día (Y-m-d), por hora. */
	public function delDia($fecha)
	{
		$f = $this->fecha($fecha);
		return array_map(array($this, 'fila'), $this->db()->query("SELECT r.*, to_char(r.hora, 'HH24:MI') AS hora_txt,
				COALESCE(u.nombre_usuario, '') AS usuario
			FROM reserva_web r LEFT JOIN usuario u ON u.id_usuario = r.id_usuario
			WHERE r.fecha = ? ORDER BY r.hora, r.id_reserva", array($f))->result());
	}

	/** Cuántas reservas por día (no canceladas) en el mes de $fecha: para marcar el calendario. */
	public function porDiaDelMes($fecha)
	{
		$f = $this->fecha($fecha);
		$res = array();
		foreach ($this->db()->query("SELECT fecha::text AS dia, count(*) AS n, sum(personas) AS personas FROM reserva_web
				WHERE date_trunc('month', fecha) = date_trunc('month', ?::date) AND estado <> 'cancelada' GROUP BY fecha", array($f))->result() as $r) {
			$res[$r->dia] = array('reservas' => (int)$r->n, 'personas' => (int)$r->personas);
		}
		return $res;
	}

	/** Mesas para elegir (id y recinto). */
	public function mesas()
	{
		return array_map(function ($m) { return array('id' => (int)$m->id_mesa, 'nombre' => 'Mesa ' . (int)$m->id_mesa . ($m->recinto !== '' ? ' · ' . $m->recinto : '')); },
			$this->db()->query("SELECT m.id_mesa, COALESCE(r.nombre_recinto, '') AS recinto FROM mesa m LEFT JOIN recinto r ON r.id_recinto = m.id_recinto
				WHERE m.vigente IS NOT FALSE ORDER BY r.nombre_recinto, m.id_mesa")->result());
	}

	/** Crea o cambia una reserva. Valida todo: devuelve la reserva grabada. */
	public function guardar($id, $d, $idUsuario)
	{
		$db = $this->db();
		$nombre = trim(preg_replace('/\s+/u', ' ', (string)($d['nombre'] ?? '')));
		if ($nombre === '') throw new Exception('Falta el nombre de quien reserva', 400);
		$fecha = $this->fecha($d['fecha'] ?? '');
		$hora = trim((string)($d['hora'] ?? ''));
		if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hora)) throw new Exception('La hora debe ser como 21:30', 400);
		$personas = (int)($d['personas'] ?? 0);
		if ($personas < 1 || $personas > 200) throw new Exception('Las personas deben ser entre 1 y 200', 400);
		$telefono = preg_replace('/[^0-9+ ]/', '', (string)($d['telefono'] ?? ''));
		$idMesa = (int)($d['id_mesa'] ?? 0);
		if ($idMesa > 0 && !$db->query('SELECT 1 FROM mesa WHERE id_mesa = ?', array($idMesa))->row()) throw new Exception('Esa mesa no existe', 400);
		// La misma mesa no se reserva dos veces a menos de 2 horas
		if ($idMesa > 0) {
			$choque = $db->query("SELECT nombre, to_char(hora, 'HH24:MI') AS h FROM reserva_web WHERE id_mesa = ? AND fecha = ? AND id_reserva <> ?
				AND estado IN ('pendiente', 'llego') AND abs(extract(epoch FROM (hora - ?::time))) < 7200 LIMIT 1",
				array($idMesa, $fecha, (int)$id, $hora))->row();
			if ($choque) throw new Exception('La mesa ' . $idMesa . ' ya está reservada a las ' . $choque->h . ' (' . $choque->nombre . ')', 409);
		}
		$datos = array('fecha' => $fecha, 'hora' => $hora, 'nombre' => mb_substr($nombre, 0, 80), 'telefono' => mb_substr($telefono, 0, 30),
			'personas' => $personas, 'id_mesa' => $idMesa, 'nota' => mb_substr(trim((string)($d['nota'] ?? '')), 0, 250));
		if ($id > 0) {
			$db->where('id_reserva', (int)$id)->update('reserva_web', $datos);
			if ($db->affected_rows() < 1) throw new Exception('La reserva ya no existe', 404);
		} else {
			$datos['id_usuario'] = (int)$idUsuario;
			$db->insert('reserva_web', $datos);
			$id = (int)$db->insert_id('reserva_web_id_reserva_seq');
		}
		return $this->una($id);
	}

	public function cambiarEstado($id, $estado)
	{
		if (!in_array($estado, self::ESTADOS, true)) throw new Exception('Estado no válido', 400);
		$db = $this->db();
		$db->where('id_reserva', (int)$id)->update('reserva_web', array('estado' => $estado));
		if ($db->affected_rows() < 1) throw new Exception('La reserva ya no existe', 404);
		return $this->una($id);
	}

	private function una($id)
	{
		$r = $this->db()->query("SELECT r.*, to_char(r.hora, 'HH24:MI') AS hora_txt, '' AS usuario FROM reserva_web r WHERE id_reserva = ?", array((int)$id))->row();
		return $r ? $this->fila($r) : null;
	}

	private function fila($r)
	{
		return array('id' => (int)$r->id_reserva, 'fecha' => $r->fecha, 'hora' => $r->hora_txt, 'nombre' => $r->nombre, 'telefono' => $r->telefono,
			'personas' => (int)$r->personas, 'id_mesa' => (int)$r->id_mesa, 'nota' => $r->nota, 'estado' => $r->estado, 'usuario' => $r->usuario);
	}

	private function fecha($f)
	{
		$f = trim((string)$f);
		if ($f === '') return date('Y-m-d');
		$d = DateTime::createFromFormat('!Y-m-d', $f);
		if (!$d || $d->format('Y-m-d') !== $f) throw new Exception('La fecha no es válida', 400);
		return $f;
	}
}
