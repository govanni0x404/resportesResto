<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MresumenAnulaciones extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function resumenDeAnulaciones($desde, $hasta, $id_usuario, $todasLasAnulacionesYeliminaciones, $soloAnulaciones, $soloEliminaciones)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $jsDateTS = strtotime($desde);
        if ($jsDateTS !== false) {
            $de = date('Y-m-d', $jsDateTS);
        }

        $jsDateTS = strtotime($hasta);
        if ($jsDateTS !== false) {
            $a = date('Y-m-d', $jsDateTS);
        }

        $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        $q = "SELECT dc.id_cotizacion, p.nombre_producto, dc.cantidad, dc.total_bruto,
        to_char(dc.hora_anulacion, 'DD/MM/YYYY HH24:MM:SS') AS hora_anulacion,
        dc.motivo_anulacion, u.nombre_usuario, dc.eseliminado, dc.esanulado
        from cotizacion c 
        join detalle_cotizacion dc on dc.id_cotizacion=c.id_cotizacion
        join producto p on dc.id_producto=p.id_producto
        join usuario u on dc.id_garzon=u.id_usuario
        where c.fecha between '" . $de . "' and '" . $a . "'";
        if ($id_usuario > 0) {
            $q = $q . " and u.id_usuario=$id_usuario";
        }
        if ($todasLasAnulacionesYeliminaciones > 0) {
            $q = $q . " AND (dc.esanulado = true OR dc.eseliminado = true)";
        }
        if ($soloAnulaciones > 0) {
            $q = $q . " AND dc.esanulado=true";
        }
        if ($soloEliminaciones > 0) {
            $q = $q . " AND dc.eseliminado=true";
        }


        $consulta = $this->db->query($q);
        $sampleData = $consulta->result();
        $data = array();

        foreach ($sampleData as $key) {
            array_push(
                $data,
                array(
                    "id_cotizacion" => $key->id_cotizacion,
                    "nombre_producto" => $key->nombre_producto,
                    'cantidad' => $key->cantidad,
                    'total_bruto' => $key->total_bruto,
                    'hora_anulacion' => $key->hora_anulacion,
                    "esanulado" => ($key->esanulado == 't' ? 'SI' : 'NO'),
                    "eseliminado" => ($key->eseliminado == 't' ? 'SI' : 'NO'),
                    'motivo_anulacion' => $key->motivo_anulacion,
                    'nombre_usuario' => $key->nombre_usuario
                )
            );
        }
        $results = array(
            "sEcho" => 1,
            "iTotalRecords" => count($data),
            "iTotalDisplayRecords" => count($data),
            "aaData" => $data
        );

        return $results;
    }



}