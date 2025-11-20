<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MconsolidadoVentas extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function ResumenConsolidadoVentas($desde, $hasta)
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
        }$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        $q = "SELECT count(v.id_venta) as cantidad_ventas, round(sum(v.neto)) as total_neto, round(sum(v.iva)) as total_iva, round(sum(v.monto_descuento)) as total_descuento, round(sum(v.total)) as total_ventas,
		round(sum(v.total + v.monto_descuento)) as total_general
		from venta v 
		join pago_venta pv on v.id_venta = pv.id_venta
		join fpago fp on pv.id_fpago = fp.id_fpago
		join canal_venta cv on v.id_canal_venta = cv.id_canal_venta
		where fp.escortesia = false and v.fecha between '" . $de . "' and '" . $a . "'";
        $query = $this->db->query($q)->result();
        return $query;
    }

    public function MontoTotalventas()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        $q = " SELECT round(sum(v.total)) as monto_total_ventas from venta v";
        $query = $this->db->query($q)->result();
        return $query;
    }


    public function ResumenVentasPorFamilia($desde, $hasta)
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
        }$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);

        $q = "SELECT f.id_familia, f.nombre_familia, sum(dv.cantidad) as cantidad, 
		COALESCE(sum(dv.total_bruto_con_descuento), 0) as monto_total, round(sum(dv.subtotal_neto_con_descuento)) as monto_neto, 
		round(sum(dv.monto_iva)) as monto_iva
		from detalle_venta dv 
		join venta v on dv.id_venta = v.id_venta
		join producto p on dv.id_producto = p.id_producto
		join familia f on p.id_familia = f.id_familia
		join caja ca on v.id_caja = ca.id_caja
		join pago_venta pv on v.id_venta = pv.id_venta
		join fpago fp on pv.id_fpago = fp.id_fpago
		where fp.escortesia = false and fp.vigente = true and v.fecha between '" . $de . "' and '" . $a . "'
		group by f.id_familia";
        $query = $this->db->query($q)->result();
        return $query;
    }

    public function ResumenVentasPorCanal($desde, $hasta)
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
        }$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        $q = "SELECT cv.nombre_canal_venta, round(sum(dv.subtotal_neto_con_descuento)) as total_neto, round(sum(dv.monto_iva)) as total_iva, round(sum(dv.total_bruto_con_descuento)) as total_ventas, count(v.id_venta) as cantidad_ventas
		from venta v
		join detalle_venta dv on v.id_venta = dv.id_venta
		join pago_venta pv on v.id_venta = pv.id_venta
		join fpago fp on pv.id_fpago = fp.id_fpago
		join canal_venta cv on v.id_canal_venta = cv.id_canal_venta
		where fp.vigente = true and fp.escortesia = false and v.fecha between '" . $de . "' and '" . $a . "'
		group by cv.nombre_canal_venta";

        $query = $this->db->query($q)->result();
        return $query;
    }

    public function datosEmpresa()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        $q = "SELECT * from empresa";
        $consulta = $this->db->query($q);
        return $consulta->result();
    }

    public function datosVentasPorFamilia($desde, $hasta)
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
        }$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        $q = "SELECT  f.nombre_familia, sum(dv.cantidad) as cantidad, 
		COALESCE(sum(dv.total_bruto), 0) as monto_total, round(sum(dv.neto)) as monto_neto, 
		round(sum(dv.iva)) as monto_iva
		from detalle_venta dv 
		join venta v on dv.id_venta = v.id_venta
		join producto p on dv.id_producto = p.id_producto
		join familia f on p.id_familia = f.id_familia
		join caja ca on v.id_caja = ca.id_caja
		join pago_venta pv on v.id_venta = pv.id_venta
		join fpago fp on pv.id_fpago = fp.id_fpago
		where fp.escortesia = false and v.fecha between '" . $de . "' and '" . $a . "'
		group by f.nombre_familia
		order by f.nombre_familia";
        $query = $this->db->query($q)->result();
        return $query;
    }

    public function datosCanalDeVentas($desde, $hasta)
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
        $q = "SELECT cv.nombre_canal_venta, round(sum(dv.neto)) as total_neto, round(sum(dv.iva)) as total_iva, round(sum(dv.total_bruto)) as total_ventas, count(v.id_venta) as cantidad_ventas,
		round(sum(v.total)) as monto_total_ventas
		from venta v
		join detalle_venta dv on v.id_venta = dv.id_venta
		join pago_venta pv on v.id_venta = pv.id_venta
		join fpago fp on pv.id_fpago = fp.id_fpago
		join canal_venta cv on v.id_canal_venta = cv.id_canal_venta
		where fp.vigente = true and fp.escortesia = false and v.fecha between '" . $de . "' and '" . $a . "'
		group by cv.nombre_canal_venta";
        $query = $this->db->query($q)->result();
        return $query;
    }

    public function detalleConsolidado($desde, $hasta)
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
        }$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        $q = "SELECT count(v.id_venta) as cantidad_ventas, round(sum(v.neto)) as total_neto, round(sum(v.iva)) as total_iva, round(sum(v.monto_descuento)) as total_descuento, round(sum(v.total)) as total_ventas,
		round(sum(v.total + v.monto_descuento)) as total_general
		from venta v 
		join pago_venta pv on v.id_venta = pv.id_venta
		join fpago fp on pv.id_fpago = fp.id_fpago
		join canal_venta cv on v.id_canal_venta = cv.id_canal_venta
		where fp.escortesia = false and v.fecha between '" . $de . "' and '" . $a . "'";
        $query = $this->db->query($q)->result();
        return $query;
    }

    public function totalSistema()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
    $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        $q = " SELECT round(sum(v.total)) as monto_total_ventas from venta v";
        $query = $this->db->query($q)->result();
        return $query;
    }
}