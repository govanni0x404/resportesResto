<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MventasPorProducto extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function ListadoVentasPorProducto($desde, $hasta)
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
        $q = "SELECT p.nombre_producto,sum(dv.cantidad) as cantidad_vendida,  round(sum(dv.subtotal_neto_con_descuento)) as total_neto, round(sum(dv.monto_iva)) as total_iva,
        sum(dv.total_bruto_con_descuento) as total_vendido
        from producto p
        join detalle_venta dv on p.id_producto = dv.id_producto
        join detalle_lprecio lp on p.id_producto = lp.id_producto
        join venta v on dv.id_venta = v.id_venta
        where dv.total_bruto_con_descuento>0 and  v.fecha between '" . $de . "' and '" . $a . "'
        group by p.nombre_producto
        order by p.nombre_producto";

        $consulta = $this->db->query($q);
        $sampleData = $consulta->result();
        $data = array();

        foreach ($sampleData as $key) {
            array_push($data, array(
                "nombre_producto" => $key->nombre_producto,
                "cantidad_vendida" => $key->cantidad_vendida,
                /*"total_neto" => $key->total_neto,
                "total_iva" => $key->total_iva,*/
                "total_vendido" => $key->total_vendido
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

    public function pieVentasPorProducto($desde, $hasta)
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

        $q = "SELECT p.nombre_producto, sum(dv.total_bruto) as monto_total, 
        round(((sum(dv.cantidad)/
            (select sum(dve.cantidad) 
            from detalle_venta dve 
            join venta ve on dve.id_venta = ve.id_venta 
            where ve.fecha between '" . $de . "' and '" . $a . "'))*100),3) as porcentaje
        from producto p
        join detalle_venta dv on p.id_producto = dv.id_producto
        join venta v on dv.id_venta = v.id_venta
		where v.fecha between '" . $de . "' and '" . $a . "'
        group by p.nombre_producto";



        $query = $this->db->query($q)->result();
        return $query;
    }
}