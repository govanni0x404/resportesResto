<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MresumenVentasProductosMes extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function resumenVentasProductos($desde, $hasta)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $jsDateTS = strtotime($desde);
        if ($jsDateTS !== false) {
            $de = date('Y-m-d', $jsDateTS);
        }

        $jsDateTS = strtotime($hasta);
        if ($jsDateTS !== false) {
            $a = date('Y-m-d', $jsDateTS);
        }

        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        
        $q = "SELECT
                p.id_producto,
                p.nombre_producto,
                f.nombre_familia,
                initcap(to_char(date_trunc('month', v.fecha), 'TMMonth YYYY')) AS mes,
                ROUND(SUM(d.cantidad)::numeric, 0) AS cantidad,
                ROUND(SUM(d.total_bruto_con_descuento)::numeric, 0) AS total
            FROM detalle_venta d
            JOIN producto p ON p.id_producto = d.id_producto
            JOIN familia f ON f.id_familia = p.id_familia
            JOIN venta v ON d.id_venta = v.id_venta
            WHERE v.fecha >= '" . $de . "'
              AND v.fecha <= '" . $a . "'
            GROUP BY
                date_trunc('month', v.fecha),
                p.id_producto,
                p.nombre_producto,
                f.nombre_familia
            ORDER BY
                date_trunc('month', v.fecha) DESC,
                total DESC";

        $consulta = $this->db->query($q);
        $sampleData = $consulta->result();
        $data = array();

        foreach ($sampleData as $key) {
            array_push($data, array(
                "id_producto" => $key->id_producto,
                "nombre_producto" => $key->nombre_producto,
                "nombre_familia" => $key->nombre_familia,
                "mes" => $key->mes,
                "cantidad" => $key->cantidad,
                "total" => $key->total,
                // "btnVerDetalle" => '<button type="button" onclick="detalleProductoMes(' . $key->id_producto . ', \'' . $key->mes . '\')" class="btn btn-labeled btn-info btn-sm m-b-5"><span class="btn-label"><i class="glyphicon glyphicon-list-alt"></i></span>Ver Detalle</button>',
                "test" => "",
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

    // public function detalleProductoMes($id_producto, $mes)
    // {
    //     if (strlen($this->session->userdata('nombre_usuario')) == 0) {
    //         redirect(base_url());
    //     }
        
    //     $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        
    //     // Convertir mes a rango de fechas
    //     $q = "SELECT
    //             v.id_venta,
    //             to_char(v.fecha, 'DD-MM-YYYY HH24:MI') as fecha_venta,
    //             d.cantidad,
    //             ROUND(d.precio_bruto::numeric, 0) as precio_unitario,
    //             ROUND(d.total_bruto_con_descuento::numeric, 0) as total,
    //             COALESCE(c.nombre_cliente, 'PUBLICO GENERAL') as cliente
    //         FROM detalle_venta d
    //         JOIN venta v ON d.id_venta = v.id_venta
    //         LEFT JOIN cliente c ON v.id_cliente = c.id_cliente
    //         WHERE d.id_producto = $id_producto
    //           AND initcap(to_char(date_trunc('month', v.fecha), 'TMMonth YYYY')) = '$mes'
    //         ORDER BY v.fecha DESC";

    //     $consulta = $this->db->query($q);
    //     $sampleData = $consulta->result();
    //     $data = array();

    //     foreach ($sampleData as $key) {
    //         array_push($data, array(
    //             "id_venta" => $key->id_venta,
    //             "fecha_venta" => $key->fecha_venta,
    //             "cliente" => $key->cliente,
    //             "cantidad" => $key->cantidad,
    //             "precio_unitario" => $key->precio_unitario,
    //             "total" => $key->total,
    //             "test" => "",
    //         ));
    //     }
        
    //     $results = array(
    //         "sEcho" => 1,
    //         "iTotalRecords" => count($data),
    //         "iTotalDisplayRecords" => count($data),
    //         "aaData" => $data
    //     );

    //     return $results;
    // }

    public function resumenVentasProductosPDF($desde, $hasta)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $jsDateTS = strtotime($desde);
        if ($jsDateTS !== false) {
            $de = date('Y-m-d', $jsDateTS);
        }

        $jsDateTS = strtotime($hasta);
        if ($jsDateTS !== false) {
            $a = date('Y-m-d', $jsDateTS);
        }

        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        
        $q = "SELECT
                p.nombre_producto,
                f.nombre_familia,
                initcap(to_char(date_trunc('month', v.fecha), 'TMMonth YYYY')) AS mes,
                ROUND(SUM(d.cantidad)::numeric, 0) AS cantidad,
                ROUND(SUM(d.total_bruto_con_descuento)::numeric, 0) AS total
            FROM detalle_venta d
            JOIN producto p ON p.id_producto = d.id_producto
            JOIN familia f ON f.id_familia = p.id_familia
            JOIN venta v ON d.id_venta = v.id_venta
            WHERE v.fecha >= '" . $de . "'
              AND v.fecha <= '" . $a . "'
            GROUP BY
                date_trunc('month', v.fecha),
                p.nombre_producto,
                f.nombre_familia
            ORDER BY
                date_trunc('month', v.fecha) DESC,
                total DESC";

        $consulta = $this->db->query($q);
        return $consulta->result();
    }
}