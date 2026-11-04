<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Tipos de documento (tabla tdocumento): boleta, factura, nota de crédito, comprobante interno...
 * La Caja decide si un documento es electrónico por su código SII (Mcaja::COD_SII_ELECTRONICOS), por eso
 * "Facturación electrónica (DTE)" no se guarda: se calcula del código. Folio automático y formato DTE solo
 * existen en algunas bases; si la columna no está, la pantalla no los muestra.
 */
class MdocumentosTributarios extends CI_Model
{
	const COD_SII_ELECTRONICOS = array(33, 34, 39, 41, 46, 52, 56, 61);

	/** Columnas que maneja la pantalla, si existen en la base. */
	private static $campos = array('nombre_tdocumento', 'cod_sii', 'para_venta', 'para_compra', 'categoria_boleta',
		'categoria_factura', 'requiere_cliente', 'folio_automatico', 'formato_dte');

	private function db()
	{
		$this->load->helper('tenant');
		return get_tenant_db($this);
	}

	/** Columnas presentes de las que usa la pantalla. */
	public function columnas($db = null)
	{
		$db = $db ?: $this->db();
		return array_values(array_intersect(self::$campos, $db->list_fields('tdocumento')));
	}

	public function listado()
	{
		$db = $this->db();
		$cols = $this->columnas($db);
		$sel = array('id_tdocumento');
		foreach ($cols as $c) $sel[] = $c;
		$filas = $db->query('SELECT ' . implode(', ', $sel) . ' FROM tdocumento ORDER BY nombre_tdocumento')->result();
		foreach ($filas as $f) {
			$f->id_tdocumento = (int)$f->id_tdocumento;
			$f->cod_sii = (int)$f->cod_sii;
			$f->electronico = in_array($f->cod_sii, self::COD_SII_ELECTRONICOS, true);
			foreach (array('para_venta', 'para_compra', 'categoria_boleta', 'categoria_factura', 'requiere_cliente', 'folio_automatico') as $b) {
				if (property_exists($f, $b)) $f->$b = ($f->$b === 't' || $f->$b === true);
			}
		}
		return array('filas' => $filas, 'columnas' => $cols);
	}

	/** Crea o actualiza. $d ya viene validado por el controlador. Devuelve el id. */
	public function grabar($id, $d)
	{
		$db = $this->db();
		$cols = $this->columnas($db);
		$datos = array();
		foreach ($d as $k => $v) if (in_array($k, $cols, true)) $datos[$k] = $v;

		$debug = $db->db_debug;
		$db->db_debug = false;
		$db->trans_begin();
		try {
			$dup = $db->query('SELECT id_tdocumento FROM tdocumento WHERE upper(trim(nombre_tdocumento)) = upper(?) AND id_tdocumento <> ? LIMIT 1',
				array($datos['nombre_tdocumento'], (int)$id))->row();
			if ($dup) throw new Exception('Ya existe un documento con ese nombre', 409);
			if ($id > 0) {
				if (!$db->query('SELECT 1 FROM tdocumento WHERE id_tdocumento = ?', array((int)$id))->row()) {
					throw new Exception('El documento ya no existe', 404);
				}
				$db->where('id_tdocumento', (int)$id)->update('tdocumento', $datos);
			} else {
				// La secuencia puede estar atrasada (hay ids cargados a mano): se usa el siguiente al máximo, con la tabla
				// bloqueada para no repetirlo, y la secuencia queda al día para el sistema de escritorio
				$db->query('LOCK TABLE tdocumento IN SHARE ROW EXCLUSIVE MODE');
				$id = (int)$db->query('SELECT COALESCE(max(id_tdocumento), 0) + 1 AS id FROM tdocumento')->row()->id;
				$datos['id_tdocumento'] = $id;
				if (in_array('folio', $db->list_fields('tdocumento'), true)) $datos['folio'] = 0;
				$db->insert('tdocumento', $datos);
				$seq = $db->query("SELECT to_regclass('tdocumento_id_tdocumento_seq') IS NOT NULL AS hay")->row();
				if ($seq && ($seq->hay === 't' || $seq->hay === true)) {
					$db->query("SELECT setval('tdocumento_id_tdocumento_seq', GREATEST(?, (SELECT last_value FROM tdocumento_id_tdocumento_seq)))", array($id));
				}
			}
			$e = $db->error();
			if (!empty($e['code']) || !empty($e['message'])) throw new Exception('No se pudo grabar: ' . $e['message'], 500);
			$db->trans_commit();
		} catch (Throwable $ex) {
			// Con db_debug apagado una consulta fallida devuelve false y el ->row() siguiente lanza Error
			$db->trans_rollback();
			$db->db_debug = $debug;
			throw $ex;
		}
		$db->db_debug = $debug;
		return (int)$id;
	}
}
