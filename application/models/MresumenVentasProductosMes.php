<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MresumenVentasProductosMes extends CI_Model
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

    public function resumenVentasProductos($desde, $hasta)
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $de = $this->fechaSql($desde);
        $a = $this->fechaSql($hasta);

        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        
        $q = "SELECT
                p.id_producto,
                p.nombre_producto,
                f.nombre_familia,
                initcap(to_char(date_trunc('month', v.fecha), 'TMMonth YYYY')) AS mes,
                to_char(date_trunc('month', v.fecha), 'YYYY-MM') AS mes_clave,
                ROUND(SUM(d.cantidad)::numeric, 0) AS cantidad,
                ROUND(SUM(d.total_bruto_con_descuento)::numeric, 0) AS total
            FROM detalle_venta d
            JOIN producto p ON p.id_producto = d.id_producto
            JOIN familia f ON f.id_familia = p.id_familia
            JOIN venta v ON d.id_venta = v.id_venta
            WHERE v.fecha >= ?
              AND v.fecha <= ?
              -- 19-11-2026 (auditoría): sin cortesías (como Consolidado de ventas)
              AND EXISTS (SELECT 1 FROM pago_venta pv JOIN fpago fp ON fp.id_fpago = pv.id_fpago
                  WHERE pv.id_venta = v.id_venta AND fp.escortesia = false)
            GROUP BY
                date_trunc('month', v.fecha),
                p.id_producto,
                p.nombre_producto,
                f.nombre_familia
            ORDER BY
                date_trunc('month', v.fecha) DESC,
                total DESC";

        $consulta = $this->db->query($q, array($de, $a));
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
                // v1.20.0: detalle por producto y mes (el mes va como AAAA-MM: su nombre depende del idioma de la base)
                "btnVerDetalle" => '<button type="button" onclick="detalleProductoMes(' . (int)$key->id_producto . ', \'' . $key->mes_clave . '\')" class="btn btn-labeled btn-info btn-sm m-b-5"><span class="btn-label"><i class="glyphicon glyphicon-list-alt"></i></span>Ver Detalle</button>',
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

    /**
     * v1.20.0: ventas de un producto en un mes ($mes = 'AAAA-MM'), con el mismo criterio del resumen (sin cortesías).
     * La versión anterior estaba comentada: pegaba el id y el nombre del mes dentro del SQL (inyección SQL) y comparaba
     * el mes por su nombre, que depende del idioma de la base.
     */
    public function detalleProductoMes($id_producto, $mes)
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        $vacio = array("sEcho" => 1, "iTotalRecords" => 0, "iTotalDisplayRecords" => 0, "aaData" => array());
        if (!preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', (string)$mes) || (int)$id_producto <= 0) return $vacio;
        $desde = $mes . '-01';
        $hasta = date('Y-m-d', strtotime($desde . ' +1 month'));

        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $q = "SELECT v.id_venta,
                COALESCE(td.nombre_tdocumento, 'Venta') || ' N° ' || v.folio AS documento,
                to_char(v.fecha_transaccion, 'DD-MM-YYYY HH24:MI') AS fecha_venta,
                d.cantidad,
                ROUND(COALESCE(d.bruto_un, 0)::numeric, 0) AS precio_unitario,
                ROUND(COALESCE(d.total_bruto_con_descuento, d.subtotal, 0)::numeric, 0) AS total,
                COALESCE(c.nombre_cliente, 'PUBLICO GENERAL') AS cliente
            FROM detalle_venta d
            JOIN venta v ON d.id_venta = v.id_venta
            LEFT JOIN tdocumento td ON td.id_tdocumento = v.id_tdocumento
            LEFT JOIN cliente c ON v.id_cliente = c.id_cliente
            WHERE d.id_producto = ? AND v.fecha >= ? AND v.fecha < ?
              AND EXISTS (SELECT 1 FROM pago_venta pv JOIN fpago fp ON fp.id_fpago = pv.id_fpago
                  WHERE pv.id_venta = v.id_venta AND fp.escortesia = false)
            ORDER BY v.fecha_transaccion DESC, v.id_venta DESC";
        $data = array();
        foreach ($this->db->query($q, array((int)$id_producto, $desde, $hasta))->result() as $key) {
            $data[] = array(
                "id_venta" => $key->documento,
                "fecha_venta" => $key->fecha_venta,
                "cliente" => $key->cliente,
                "cantidad" => $key->cantidad,
                "precio_unitario" => $key->precio_unitario,
                "total" => $key->total,
                "test" => "",
            );
        }
        return array("sEcho" => 1, "iTotalRecords" => count($data), "iTotalDisplayRecords" => count($data), "aaData" => $data);
    }

    public function resumenVentasProductosPDF($desde, $hasta)
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $de = $this->fechaSql($desde);
        $a = $this->fechaSql($hasta);

        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        
        $q = "SELECT
                p.nombre_producto,
                f.nombre_familia,
                initcap(to_char(date_trunc('month', v.fecha), 'TMMonth YYYY')) AS mes,
                to_char(date_trunc('month', v.fecha), 'YYYY-MM') AS mes_clave,
                ROUND(SUM(d.cantidad)::numeric, 0) AS cantidad,
                ROUND(SUM(d.total_bruto_con_descuento)::numeric, 0) AS total
            FROM detalle_venta d
            JOIN producto p ON p.id_producto = d.id_producto
            JOIN familia f ON f.id_familia = p.id_familia
            JOIN venta v ON d.id_venta = v.id_venta
            WHERE v.fecha >= ?
              AND v.fecha <= ?
              -- 19-11-2026 (auditoría): sin cortesías (como Consolidado de ventas)
              AND EXISTS (SELECT 1 FROM pago_venta pv JOIN fpago fp ON fp.id_fpago = pv.id_fpago
                  WHERE pv.id_venta = v.id_venta AND fp.escortesia = false)
            GROUP BY
                date_trunc('month', v.fecha),
                p.nombre_producto,
                f.nombre_familia
            ORDER BY
                date_trunc('month', v.fecha) DESC,
                total DESC";

        $consulta = $this->db->query($q, array($de, $a));
        return $consulta->result();
    }
}