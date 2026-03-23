<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MresumenSalidaBodega extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}

	public function resumenSalidaBodega($desde, $hasta, $id_usuario, $id_bodega)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }

        /*
        $jsDateTS = strtotime($desde);
        if ($jsDateTS !== false) {
            $de = date('Y-m-d', $jsDateTS);
        }

        $jsDateTS = strtotime($hasta);
        if ($jsDateTS !== false) {
            $a = date('Y-m-d', $jsDateTS);
        }
            */

        $de = $desde; 
        $a = $hasta . ' 23:59:59'; // Incluye hasta el final del día

        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $q = "SELECT eb.id_esbodega, b.nombre_bodega, eb.fecha, u.nombre_usuario, to_char(eb.fecha_transaccion, 'HH24:MI:SS') AS hora, eb.observaciones
        from esbodega eb 
		join bodega b on eb.id_bodega=b.id_bodega
		join usuario u on eb.id_usuario=u.id_usuario
        where eb.entrada_a_bodega=false and eb.fecha_transaccion between '" . $de . "' and '" . $a . "'";
        if ($id_usuario > 0) {
            $q = $q . " and u.id_usuario=$id_usuario";
        }
		if ($id_bodega > 0) {
            $q = $q . " and b.id_bodega=$id_bodega";
        }
        $consulta = $this->db->query($q);
        $sampleData = $consulta->result();
        $data = array();

        foreach ($sampleData as $key) {
            array_push($data, array(
                "id_esbodega" => $key->id_esbodega,
                "nombre_bodega" => $key->nombre_bodega,
                'fecha' => $key->fecha . ' / ' . $key->hora,
                'nombre_usuario' => $key->nombre_usuario,
				'observaciones' => $key->observaciones,
                'btn_ver_detalle' => ('<button onclick="VerModalDetalleSalidaBodega(' . $key->id_esbodega . ')" type="submit" class="btn btn-success btn-md"><i class="fa fa-file-text"></i>VER DETALLE</button>'),
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

	public function detalleResumenSalidaBodega($id_esbodega)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }

        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $q = "SELECT deb.id_esbodega, i.nombre_insumo, u.nombre_umedida, deb.cantidad, deb.stock_anterior, deb.nuevo_stock from detalle_esbodega deb
		left join insumo i on deb.id_insumo=i.id_insumo
        left join umedida u on i.id_umedida_inventario=u.id_umedida
		where deb.id_esbodega=$id_esbodega";

        $consulta = $this->db->query($q);
        $sampleData = $consulta->result();
        $data = array();

        foreach ($sampleData as $key) {
            array_push($data, array(
                "nombre_insumo" => $key->nombre_insumo,
                "nombre_umedida" => $key->nombre_umedida,
                "cantidad" => $key->cantidad,
                "stock_anterior" => $key->stock_anterior,
                "nuevo_stock" => $key->nuevo_stock
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

	
}