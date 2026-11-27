<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MformasDePago extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function ListadoDeFormasDePago()
    {
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $pagoElectronico = $this->db->field_exists('pago_electronico_web', 'fpago') ? 'fp.pago_electronico_web IS TRUE' : 'false';
        $q = "SELECT fp.id_fpago, fp.nombre_fpago, fp.escortesia, fp.boleta_electronica, fp.vigente, fp.permite_propina,
        $pagoElectronico AS pago_electronico
        from fpago fp
        order by fp.id_fpago asc";

        $consulta = $this->db->query($q);
        $sampleData = $consulta->result();
        $data = array();

        foreach ($sampleData as $key) {
            array_push($data, array(
                "id_fpago" => $key->id_fpago,
                "nombre_fpago" => $key->nombre_fpago,
                "escortesia" => ($key->escortesia == 't' ? 'SI' : 'NO'),
                "boleta_electronica" => ($key->boleta_electronica == 't' ? 'SI' : 'NO'),
                "vigente" => ($key->vigente == 't' ? 'SI' : 'NO'),
                "permite_propina" => ($key->permite_propina == 't' ? 'SI' : 'NO'),
                "pago_electronico" => ($key->pago_electronico == 't' ? 'SI' : 'NO'),
            ));
        }
        $results = array(
            "sEcho" => 1,
            "iTotalRecords" => count($data),
            "iTotalDisplayRecords" => count($data),
            "aaData" => $data
        );

        return $results;
    }

    public static function esTarjeta($nombre)
    {
        return preg_match('/\b(?:confianza|fiado|directo)\b/iu', $nombre) === 0
            && (preg_match('/\bd[eé]bito\b/iu', $nombre) === 1
                || (preg_match('/\btarjeta\b/iu', $nombre) === 1 && preg_match('/\bcr[eé]dito\b/iu', $nombre) === 1));
    }

    public function grabarNuevaFormaDePago($id, $data, $pagoElectronico = null)
    {
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
        if ($pagoElectronico) {
            $credito = $this->db->field_exists('id_fpago_creditodirecto', 'configuracion')
                ? $this->db->select('id_fpago_creditodirecto')->get('configuracion', 1)->row() : null;
            if (!self::esTarjeta($data['nombre_fpago']) || !empty($data['escortesia'])
                || ($id && $credito && $id === (int)$credito->id_fpago_creditodirecto)) {
                set_status_header(400);
                return array('error' => 'Pago electrónico solo se puede activar para tarjetas de débito o crédito.');
            }
        }
        if ($pagoElectronico !== null && $this->db->field_exists('pago_electronico_web', 'fpago')) {
            $data['pago_electronico_web'] = $pagoElectronico ? 't' : 'f';
        } elseif ($pagoElectronico) {
            set_status_header(409);
            return array('error' => 'Falta actualizar la base de datos para habilitar el pago electrónico.');
        }
        if ($id) {
            $this->db->where('id_fpago', $id);
            $this->db->update('fpago', $data);
            // 16-11-2026 (QA): si no se actualizó ninguna fila el registro no existe: error (404) y no "éxito"
            if ($this->db->affected_rows() < 1) {
            	set_status_header(404);
            	return array('error' => 'El registro ya no existe (pudo haberlo borrado otra persona). Recarga la página.');
            }
            $consulta = $this->db->query("select " . (int)$id . " as id_fpago");
        } else {
            $this->db->insert('fpago', $data);
            $consulta = $this->db->query("SELECT CURRVAL('fpago_id_fpago_seq'::regclass) as id_fpago");
        }
        return $consulta->result();
    }
}
