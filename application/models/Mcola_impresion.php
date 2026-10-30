<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Cola de impresión para el agente de Windows (assets/agente-impresion/agente.ps1).
 * El sistema deja cada comanda en `cola_impresion` y el agente de cada local la pide por HTTPS y la imprime
 * en la impresora de Windows (o de red) que corresponde. Las tablas se crean solas la primera vez.
 */
class Mcola_impresion extends CI_Model
{
	const SEGUNDOS_ACTIVO = 90;     // un agente se considera conectado si preguntó hace menos de esto
	const MINUTOS_VENCE = 10;       // trabajo sin imprimir tras este tiempo => error

	private $db = null;

	/** Con sesión (administración y Caja) o por nombre de conexión (agente, que no tiene sesión). */
	public function conectar($nombreConexion = null)
	{
		$this->load->helper('tenant');
		if ($nombreConexion === null) {
			$db = get_tenant_db($this);
		} else {
			include(APPPATH . 'config/database.php');
			if (!preg_match('/^[A-Za-z0-9_-]+$/', $nombreConexion) || !isset($db[$nombreConexion])) return false;
			$db = $this->load->database($db[$nombreConexion], true);
		}
		$db->db_debug = false;
		$this->db = $db;
		$this->asegurarTablas();
		return $db;
	}

	private function asegurarTablas()
	{
		$this->db->query("CREATE TABLE IF NOT EXISTS agente_impresion (
			id_agente serial PRIMARY KEY,
			nombre text NOT NULL DEFAULT '',
			token_hash text NOT NULL,
			impresoras text NOT NULL DEFAULT '[]',
			ultimo_contacto timestamp NULL,
			creado timestamp NOT NULL DEFAULT now(),
			activo boolean NOT NULL DEFAULT true)");
		$this->db->query("CREATE TABLE IF NOT EXISTS cola_impresion (
			id_cola serial PRIMARY KEY,
			destino text NOT NULL DEFAULT '',
			impresora text NOT NULL DEFAULT '',
			ip text NOT NULL DEFAULT '',
			puerto integer NOT NULL DEFAULT 0,
			datos_b64 text NOT NULL,
			estado text NOT NULL DEFAULT 'pendiente',
			intentos integer NOT NULL DEFAULT 0,
			id_agente integer NULL,
			error text NOT NULL DEFAULT '',
			creado timestamp NOT NULL DEFAULT now(),
			reclamado timestamp NULL,
			impreso timestamp NULL)");
	}

	/** Nombre de impresora de Windows a partir de la ruta del destino: "//equipo/COCINA" => "COCINA". */
	public static function nombreImpresora($ruta)
	{
		$ruta = trim((string)$ruta);
		if ($ruta === '' || strlen($ruta) <= 1) return '';
		$partes = preg_split('#[\\\\/]+#', rtrim($ruta, '\\/'));
		return trim((string)end($partes));
	}

	// ---------- agentes ----------

	/** Crea un agente y devuelve su clave completa ("<conexion>.<secreto>"); el secreto solo se ve esta vez. */
	public function crearAgente($nombre, $nombreConexion)
	{
		$secreto = bin2hex(random_bytes(20));
		$this->db->query('INSERT INTO agente_impresion (nombre, token_hash) VALUES (?, ?)', array(mb_substr(trim($nombre), 0, 60), hash('sha256', $secreto)));
		return $nombreConexion . '.' . $secreto;
	}

	public function agenteDeSecreto($secreto)
	{
		return $this->db->query('SELECT * FROM agente_impresion WHERE activo AND token_hash = ?', array(hash('sha256', (string)$secreto)))->row();
	}

	public function revocarAgente($id)
	{
		$this->db->query('UPDATE agente_impresion SET activo = false WHERE id_agente = ?', array((int)$id));
	}

	public function agentes()
	{
		return $this->db->query("SELECT id_agente, nombre, impresoras, to_char(ultimo_contacto, 'DD/MM/YYYY HH24:MI:SS') AS ultimo,
				COALESCE(ultimo_contacto > now() - interval '" . self::SEGUNDOS_ACTIVO . " seconds', false) AS conectado
			FROM agente_impresion WHERE activo ORDER BY id_agente")->result();
	}

	public function hayAgenteActivo()
	{
		$r = $this->db->query("SELECT 1 FROM agente_impresion WHERE activo AND ultimo_contacto > now() - interval '" . self::SEGUNDOS_ACTIVO . " seconds' LIMIT 1");
		return $r && $r->num_rows() > 0;
	}

	// ---------- cola ----------

	public function encolar($destino, $impresora, $ip, $puerto, $bytes)
	{
		$this->db->query('INSERT INTO cola_impresion (destino, impresora, ip, puerto, datos_b64) VALUES (?, ?, ?, ?, ?)',
			array((string)$destino, (string)$impresora, (string)$ip, (int)$puerto, base64_encode($bytes)));
		return (int)$this->db->insert_id();
	}

	/** El agente pregunta: registra sus impresoras y se lleva (marca como "imprimiendo") los trabajos que le tocan. */
	public function pendientes($agente, $impresoras)
	{
		$lista = array();
		foreach ((array)$impresoras as $n) { $n = trim((string)$n); if ($n !== '') $lista[] = mb_strtolower($n); }
		$this->db->query('UPDATE agente_impresion SET ultimo_contacto = now(), impresoras = ? WHERE id_agente = ?',
			array(json_encode(array_values($impresoras)), (int)$agente->id_agente));
		// Mantenimiento: lo que se quedó "imprimiendo" sin respuesta vuelve a la cola (hasta 3 intentos); lo viejo vence
		$this->db->query("UPDATE cola_impresion SET estado = 'pendiente' WHERE estado = 'imprimiendo' AND intentos < 3 AND reclamado < now() - interval '2 minutes'");
		$this->db->query("UPDATE cola_impresion SET estado = 'error', error = 'La impresora no respondió' WHERE estado = 'imprimiendo' AND intentos >= 3 AND reclamado < now() - interval '2 minutes'");
		$this->db->query("UPDATE cola_impresion SET estado = 'error', error = 'Ninguna PC con el agente tiene esta impresora' WHERE estado = 'pendiente' AND creado < now() - interval '" . self::MINUTOS_VENCE . " minutes'");
		if (!$lista) $lista = array('');
		$r = $this->db->query("UPDATE cola_impresion SET estado = 'imprimiendo', intentos = intentos + 1, id_agente = ?, reclamado = now()
			WHERE id_cola IN (SELECT id_cola FROM cola_impresion WHERE estado = 'pendiente'
				AND (impresora = '' OR lower(impresora) IN ?) ORDER BY id_cola LIMIT 10 FOR UPDATE SKIP LOCKED)
			RETURNING id_cola, destino, impresora, ip, puerto, datos_b64", array((int)$agente->id_agente, $lista));
		return $r ? $r->result() : array();
	}

	public function resultado($agente, $idCola, $ok, $error)
	{
		if ($ok) {
			$this->db->query("UPDATE cola_impresion SET estado = 'impreso', impreso = now(), error = '' WHERE id_cola = ? AND id_agente = ?", array((int)$idCola, (int)$agente->id_agente));
		} else {
			$this->db->query("UPDATE cola_impresion SET estado = 'error', error = ? WHERE id_cola = ? AND id_agente = ?", array(mb_substr((string)$error, 0, 300), (int)$idCola, (int)$agente->id_agente));
		}
	}

	public function ultimos($n = 15)
	{
		return $this->db->query("SELECT c.destino, c.estado, c.error, to_char(c.creado, 'DD/MM HH24:MI:SS') AS creado, a.nombre AS agente
			FROM cola_impresion c LEFT JOIN agente_impresion a ON a.id_agente = c.id_agente ORDER BY c.id_cola DESC LIMIT " . (int)$n)->result();
	}
}
