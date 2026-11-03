<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MventasMensualesTotalizadas extends CI_Model
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

    public function ventasMensualesTotalizadas($desde, $hasta)
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); // redireccionar al login
        }

        $de = $this->fechaSql($desde);
        $a = $this->fechaSql($hasta);
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $q = "SELECT 
            DATE(v.fecha) AS ventas_del_dia,
            COUNT(v.id_venta) AS total_documentos,
            SUM(v.total) AS monto_total,
            SUM(v.neto) AS monto_neto,
            SUM(v.iva) AS iva
        FROM 
            venta v
        WHERE 
            v.fecha BETWEEN ? AND ?
        GROUP BY 
            DATE(v.fecha)
        ORDER BY 
            DATE(v.fecha)
    ";

        $consulta = $this->db->query($q, array($de, $a));
        $sampleData = $consulta->result();
        $data = array();

        foreach ($sampleData as $key) {
            array_push($data, array(
                "ventas_del_dia" => $key->ventas_del_dia,
                "total_documentos" => $key->total_documentos,
                /*"iva" => $key->iva,
                "monto_neto" => $key->monto_neto,*/
                "monto_total" => $key->monto_total,
                //'btn_ver_detalle' => ('<button onclick="VerModalDetalleResumenVentasDelDia(\'' . addslashes($key->ventas_del_dia) . '\')" type="submit" class="btn btn-success btn-md"><i class="fa fa-file-text"></i>VER VENTAS</button>'),
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

    public function ventasMensualesTotalizadasPie($desde, $hasta)
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }

        $de = $this->fechaSql($desde);
        $a = $this->fechaSql($hasta);

        $this->load->helper('tenant'); $this->db = get_tenant_db($this);

        $q = "SELECT 
            DATE(v.fecha) AS ventas_del_dia,
            COUNT(v.id_venta) AS total_documentos,
            SUM(v.total) AS monto_total,
            SUM(v.neto) AS monto_neto,
            SUM(v.iva) AS iva
        FROM 
            venta v
        WHERE 
            v.fecha BETWEEN ? AND ?
        GROUP BY 
            DATE(v.fecha)
        ORDER BY 
            DATE(v.fecha)";
        $query = $this->db->query($q, array($de, $a))->result();
        return $query;
    }

    public function ventasMensualesTotalizadasTopDiezDiasMasVentas($desde, $hasta)
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }

        $de = $this->fechaSql($desde);
        $a = $this->fechaSql($hasta);

        $this->load->helper('tenant'); $this->db = get_tenant_db($this);

        $q = "SELECT 
                DATE(v.fecha) AS ventas_del_dia,
                COUNT(v.id_venta) AS total_documentos,
                SUM(round(v.total)) AS monto_total,
                SUM(round(v.neto)) AS monto_neto,
                SUM(round(v.iva)) AS iva
            FROM 
                venta v
            WHERE 
                v.fecha BETWEEN ? AND ?
            GROUP BY 
                DATE(v.fecha)
            ORDER BY 
                total_documentos DESC
            LIMIT 10;";
        $query = $this->db->query($q, array($de, $a))->result();
        return $query;
    }

    public function ventasMensualesTotalizadasTopDiezDiasMenosVentas($desde, $hasta)
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }

        $de = $this->fechaSql($desde);
        $a = $this->fechaSql($hasta);

        $this->load->helper('tenant'); $this->db = get_tenant_db($this);

        $q = "SELECT 
            DATE(v.fecha) AS ventas_del_dia,
            COUNT(v.id_venta) AS total_documentos,
            SUM(round(v.total)) AS monto_total,
            SUM(round(v.neto)) AS monto_neto,
            SUM(round(v.iva)) AS iva
        FROM 
            venta v
        WHERE 
            v.fecha BETWEEN ? AND ?
        GROUP BY 
            DATE(v.fecha)
        ORDER BY 
            total_documentos ASC
        LIMIT 10;
        ";
        $query = $this->db->query($q, array($de, $a))->result();
        return $query;
    }

    public function ResumenVentasDelDia($ventas_del_dia)
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }

        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $q = "SELECT v.id_venta, c.nombre_cliente, t.nombre_tdocumento, v.fecha, v.folio, v.total
        FROM venta v
        JOIN cliente c ON v.id_cliente = c.id_cliente
        JOIN tdocumento t ON v.id_tdocumento = t.id_tdocumento
        WHERE DATE(v.fecha) = ?"; // antes '= .?.' era un error de sintaxis SQL

        $consulta = $this->db->query($q, array($ventas_del_dia));
        $sampleData = $consulta->result();
        $data = array();

        foreach ($sampleData as $key) {
            array_push($data, array(
                "id_venta" => $key->id_venta,
                "fecha" => $key->fecha,
                "nombre_cliente" => $key->nombre_cliente,
                "nombre_tdocumento" => $key->nombre_tdocumento,
                "folio" => $key->folio,
                'total' => $key->total,
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