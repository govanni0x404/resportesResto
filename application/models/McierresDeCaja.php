<?php
defined('BASEPATH') or exit('No direct script access allowed');

class McierresDeCaja extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function tableCierresDeCaja($desde, $hasta)
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
        $q = "SELECT c.id_caja, c.cerrada, to_char(c.fecha, 'DD-MM-YYYY') as fecha_apertura, COALESCE(to_char(c.fecha_rendicion, 'DD-MM-YYYY'),'0') as fecha_rendicion, fondo_fijo, 
			/*coalesce(c.ventas, 0) as total_sistema,*/ c.descuento, coalesce(c.ventas, 0) as ventas, c.cortesia, c.ventas_local, c.ingresos, c.egresos, 
			c.efectivo_fpago as subtotal, c.efectivo_fpago as efectivo, c.efectivo_entregado, c.diferencia, c.propina, u.nombre_usuario as cajero, sum(fondo_fijo+c.ventas_local) as total_sistema
			from caja c
			join usuario u on c.id_usuario = u.id_usuario
			where c.fecha between '" . $de . "' and '" . $a . "'
            group by c.id_caja, u.nombre_usuario
			order by c.id_caja desc";

        $consulta = $this->db->query($q);
        $sampleData = $consulta->result();
        $data = array();

        foreach ($sampleData as $key) {
            array_push($data, array(
                "id_caja" => $key->id_caja,
                "btn_cierre" => $key->cerrada == 't' ? '<button onclick="cierreCajaPDF(' . $key->id_caja . ')" type="submit" class="btn btn-danger"><i class="fa fa-file-text-o"></i></button>' : '<button disabled onclick="cierreCajaPDF(' . $key->id_caja . ')" type="submit" class="btn btn-danger"><i class="fa fa-file-text-o"></i></button>',
                "fecha_apertura" => $key->fecha_apertura,
                "fecha_rendicion" => $key->fecha_rendicion > 0 ? $key->fecha_rendicion : 'CAJA ABIERTA',
                'fondo_fijo' => $key->fondo_fijo,
                'total_sistema' => $key->cerrada == 't' ? $key->total_sistema : 0,
                'descuento' => $key->cerrada == 't' ? $key->descuento : 0,
                'ventas' => $key->cerrada == 't' ? $key->ventas : 0,
                'cortesia' => $key->cerrada == 't' ? $key->cortesia : 0,
                'ventas_local' => $key->cerrada == 't' ? $key->ventas_local : 0,
                'ingresos' => $key->cerrada == 't' ? $key->ingresos : 0,
                'egresos' => $key->cerrada == 't' ? $key->egresos : 0,
                'subtotal' => $key->cerrada == 't' ? $key->subtotal : 0,
                'efectivo' => $key->cerrada == 't' ? $key->efectivo : 0,
                'efectivo_entregado' => $key->cerrada == 't' ? $key->efectivo_entregado : 0,
                'diferencia' => $key->cerrada == 't' ? $key->diferencia : 0,
                'propina' => $key->cerrada == 't' ? $key->propina : 0,
                'estado_caja' => $key->cerrada == "t" ? 'CERRADA' : 'ABIERTA',
                'cajero' => $key->cajero,
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

    public function datosEmpresa()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }

        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $q = "SELECT * from empresa";
        $consulta = $this->db->query($q);
        return $consulta->result();
    }

    public function datosCaja($id_caja, $listadoCajas)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }

        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        if ($id_caja) {
            $q = "SELECT c.*, t.nombre_turno, u.nombre_usuario, to_char(c.fecha, 'DD/MM/YYYY') as fecha_caja, to_char(c.fecha_movimiento, 'DD/MM/YYYY')||' '||c.fecha_movimiento :: timestamp(0) :: time as fecha_apertura, to_char(c.fecha_rendicion, 'DD/MM/YYYY')||' '||c.fecha_rendicion :: timestamp(0) :: time as fecha_rendicion,
			c.ingresos, c.egresos, (c.ventas_local) as ventas, (suma_detalle_caja) as total_recaudacion, c.fondo_fijo_anterior as sencillo_apertura, c.fondo_fijo as sencillo_cierre, c.suma_detalle_caja, sum(c.cortesia) as cortesia
			from caja c
			join turno t on c.id_turno = t.id_turno
			join usuario u on c.id_usuario = u.id_usuario
			where c.id_caja = $id_caja
			group by c.id_caja, t.id_turno, u.id_usuario";
        } else {
            $q = "SELECT sum(c.ingresos) as ingresos, sum(c.egresos) as egresos, sum(c.ventas_local) as ventas, sum(c.suma_detalle_caja) as total_recaudacion, 
			sum(c.fondo_fijo_anterior) as sencillo_apertura, sum(c.fondo_fijo) as sencillo_cierre, sum(c.cortesia) as cortesia
			from caja c
			where c.id_caja in ($listadoCajas)";
        }

        $consulta = $this->db->query($q);
        return $consulta->result();
    }

    public function datosFormaPago($id_caja, $listadoCajas)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }

       $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $q = "SELECT fp.id_fpago, fp.nombre_fpago, sum(dpc.monto) as monto
		from detalle_pagocaja dpc 
		join fpago fp on dpc.id_fpago = fp.id_fpago";
        if ($id_caja) {
            $q = $q . " where dpc.id_caja = $id_caja 
			group by fp.id_fpago 
			order by fp.nombre_fpago asc";
        } else {
            $q = $q . " where dpc.id_caja in ($listadoCajas) 
			group by fp.id_fpago 
			order by fp.nombre_fpago asc";
        }
        $consulta = $this->db->query($q);
        return $consulta->result();
    }

    public function datosCortesias($id_caja, $listadoCajas)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }

        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $q = "SELECT fp.id_fpago, fp.nombre_fpago, sum(pv.monto_pagado) as monto, fp.escortesia, fp.vigente
        from pago_venta pv
        join venta v on pv.id_venta = v.id_venta
        join fpago fp on pv.id_fpago = fp.id_fpago";
        if ($id_caja) {
            $q = $q . " where v.id_caja = $id_caja 
			group by fp.id_fpago 
			order by fp.nombre_fpago asc";
        } else {
            $q = $q . " where v.id_caja in ($listadoCajas) 
			group by fp.id_fpago 
			order by fp.nombre_fpago asc";
        }
        $consulta = $this->db->query($q);
        return $consulta->result();
    }

    public function datosFamiliaProducto($id_caja, $listadoCajas)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }

        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $q = "SELECT f.id_familia, f.nombre_familia, sum(dv.total_bruto) AS total_familia
		from detalle_venta dv
		join venta v on dv.id_venta = v.id_venta
		join caja c on v.id_caja = c.id_caja
		join producto p on dv.id_producto = p.id_producto
		join familia f on p.id_familia = f.id_familia";
        if ($id_caja) {
            $q = $q . " where c.id_caja = $id_caja
			group by f.id_familia";
        } else {
            $q = $q . " where c.id_caja in ($listadoCajas)
			group by f.id_familia";
        }

        $consulta = $this->db->query($q);
        return $consulta->result();
    }

    public function datosProductos($id_caja, $listadoCajas)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }

        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $q = "SELECT f.id_familia, count(p.id_producto) as cantidad, p.nombre_producto, sum(dv.total_bruto) as total_producto
		from detalle_venta dv
		join venta v on dv.id_venta = v.id_venta
		join caja c on v.id_caja = c.id_caja
		join producto p on dv.id_producto = p.id_producto
		join familia f on p.id_familia = f.id_familia";
        if ($id_caja) {
            $q = $q . " where c.id_caja = $id_caja
			group by p.id_producto, f.id_familia";
        } else {
            $q = $q . " where c.id_caja in ($listadoCajas)
			group by p.id_producto, f.id_familia";
        }
        $consulta = $this->db->query($q);
        return $consulta->result();
    }

    public function totalVentasProductos($id_caja, $listadoCajas)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }

        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $q = "SELECT sum(dv.subtotal) as total_ventas, sum(dv.monto_descuento_global) AS descuentos
		from detalle_venta dv
		join venta v on dv.id_venta = v.id_venta
		join caja c on v.id_caja = c.id_caja";

        if ($id_caja) {
            $q = $q . " where c.id_caja = $id_caja";
        } else {
            $q = $q . " where c.id_caja in ($listadoCajas)";
        }
        $consulta = $this->db->query($q);
        return $consulta->result();
    }

    public function detalleCaja($id_caja, $listadoCajas)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }

        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $q = "SELECT * from detalle_caja";
        if ($id_caja) {
            $q = $q . " where id_caja = $id_caja";
        } else {
            $q = $q . " where id_caja in ($listadoCajas)";
        }
        $consulta = $this->db->query($q);
        return $consulta->result();
    }

    public function detalleCotizacion($id_caja, $listadoCajas)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }

        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $q = "SELECT p.nombre_producto, co.id_mesa, to_char(fecha_movimiento, 'HH24:MM') as hora, dc.cantidad, dc.* 
		from detalle_cotizacion dc
		join cotizacion co on dc.id_cotizacion = co.id_cotizacion
		join producto p on dc.id_producto = p.id_producto";
        if ($id_caja) {
            $q = $q . " where dc.id_caja = $id_caja";
        } else {
            $q = $q . " where dc.id_caja in ($listadoCajas)";
        }
        $consulta = $this->db->query($q);
        return $consulta->result();
    }

    public function detallePropinas($id_caja, $listadoCajas)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }

        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $q = "SELECT u.nombre_usuario, sum(v.valor_propina) as propina
        from venta v
        join usuario u on v.id_usuario_garzon = u.id_usuario";
        if ($id_caja) {
            $q = $q . " where v.id_caja = $id_caja 
			group by u.nombre_usuario";
        } else {
            $q = $q . " where v.id_caja in ($listadoCajas )
			group by u.nombre_usuario";
        }
        $consulta = $this->db->query($q);
        return $consulta->result();
    }

    public function detalleAtencion($id_caja, $listadoCajas)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }

        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $q = "SELECT u.nombre_usuario, count(co.id_mesa) as mesas_atendidas, sum(co.total - co.descuento) as ventas, co.venta_directa,
		date_trunc('second',sum(v.fecha_transaccion-co.fecha_transaccion)/count(v.id_mesa)) as promedio_atencion
		from cotizacion co
		join usuario u on co.id_usuario = u.id_usuario
		join venta v on co.id_venta = v.id_venta
		join caja ca on v.id_caja = ca.id_caja";
        if ($id_caja) {
            $q = $q . " where co.vigente = true and v.id_caja = $id_caja  
			group by u.nombre_usuario, co.venta_directa";
        } else {
            $q = $q . " where co.vigente = true and v.id_caja in ($listadoCajas)
			group by u.nombre_usuario, co.venta_directa";
        }
        $consulta = $this->db->query($q);
        return $consulta->result();
    }
}