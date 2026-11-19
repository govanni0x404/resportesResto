<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MventasPorProducto extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    /**
     * Fecha recibida (DD-MM-YYYY o YYYY-MM-DD) a Y-m-d. Si viene vacía o inválida usa hoy: antes PHP 8 avisaba
     * "Undefined variable $de/$a" y la consulta iba con NULL.
     */
    private function fechaSql($fecha)
    {
        $ts = strtotime((string)$fecha);
        return date('Y-m-d', $ts !== false ? $ts : time());
    }

    public function ListadoVentasPorProducto($desde, $hasta)
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }

        $de = $this->fechaSql($desde);
        $a = $this->fechaSql($hasta);

        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $q = "SELECT p.nombre_producto,sum(dv.cantidad) as cantidad_vendida,  round(sum(dv.subtotal_neto_con_descuento)) as total_neto, round(sum(dv.monto_iva)) as total_iva,
        sum(dv.total_bruto_con_descuento) as total_vendido
        from producto p
        join detalle_venta dv on p.id_producto = dv.id_producto
        -- sin join a detalle_lprecio: no se usaba y con más de una lista de precios duplicaba cantidades y totales
        join venta v on dv.id_venta = v.id_venta
        -- 19-11-2026 (auditoría): sin el filtro > 0 (dejaba fuera las devoluciones / notas de crédito, que restan) y sin
        -- cortesías (como Consolidado de ventas)
        where v.fecha between ? and ? and EXISTS (SELECT 1 FROM pago_venta pv JOIN fpago fp ON fp.id_fpago = pv.id_fpago WHERE pv.id_venta = v.id_venta AND fp.escortesia = false)
        group by p.nombre_producto
        order by p.nombre_producto";

        $consulta = $this->db->query($q, array($de, $a));
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
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }

        $de = $this->fechaSql($desde);
        $a = $this->fechaSql($hasta);

        $this->load->helper('tenant'); $this->db = get_tenant_db($this);

        $q = "SELECT p.nombre_producto, sum(dv.total_bruto) as monto_total, 
        round(((sum(dv.cantidad)/
            (select sum(dve.cantidad) 
            from detalle_venta dve 
            join venta ve on dve.id_venta = ve.id_venta 
            where ve.fecha between ? and ? and EXISTS (SELECT 1 FROM pago_venta pv JOIN fpago fp ON fp.id_fpago = pv.id_fpago WHERE pv.id_venta = ve.id_venta AND fp.escortesia = false)))*100),3) as porcentaje
        from producto p
        join detalle_venta dv on p.id_producto = dv.id_producto
        join venta v on dv.id_venta = v.id_venta
		where v.fecha between ? and ? and EXISTS (SELECT 1 FROM pago_venta pv JOIN fpago fp ON fp.id_fpago = pv.id_fpago WHERE pv.id_venta = v.id_venta AND fp.escortesia = false)
        group by p.nombre_producto";



        $query = $this->db->query($q, array($de, $a, $de, $a))->result();
        return $query;
    }
}