<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MventasMensualesTotalizadas extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function ventasMensualesTotalizadas($desde, $hasta)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); // redireccionar al login
        }

        $de = date('Y-m-d', strtotime($desde));
        $a = date('Y-m-d', strtotime($hasta));
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
            v.fecha BETWEEN '$de' AND '$a'
        GROUP BY 
            DATE(v.fecha)
        ORDER BY 
            DATE(v.fecha)
    ";

        $consulta = $this->db->query($q);
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
            v.fecha BETWEEN '$de' AND '$a'
        GROUP BY 
            DATE(v.fecha)
        ORDER BY 
            DATE(v.fecha)";
        $query = $this->db->query($q)->result();
        return $query;
    }

    public function ventasMensualesTotalizadasTopDiezDiasMasVentas($desde, $hasta)
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
                v.fecha BETWEEN '$de' AND '$a'
            GROUP BY 
                DATE(v.fecha)
            ORDER BY 
                total_documentos DESC
            LIMIT 10;";
        $query = $this->db->query($q)->result();
        return $query;
    }

    public function ventasMensualesTotalizadasTopDiezDiasMenosVentas($desde, $hasta)
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
            v.fecha BETWEEN '$de' AND '$a'
        GROUP BY 
            DATE(v.fecha)
        ORDER BY 
            total_documentos ASC
        LIMIT 10;
        ";
        $query = $this->db->query($q)->result();
        return $query;
    }

    public function ResumenVentasDelDia($ventas_del_dia)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }

        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $q = "SELECT v.id_venta, c.nombre_cliente, t.nombre_tdocumento, v.fecha, v.folio, v.total
        FROM venta v
        JOIN cliente c ON v.id_cliente = c.id_cliente
        JOIN tdocumento t ON v.id_tdocumento = t.id_tdocumento
        WHERE v.fecha = .'$ventas_del_dia'.";
        ;

        $consulta = $this->db->query($q);
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