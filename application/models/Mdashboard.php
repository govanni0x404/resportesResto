<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mdashboard extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function empresa()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        $q = "SELECT * from empresa";
        $query = $this->db->query($q)->result();
        return $query;
    }

    public function totalVentas($id_caja, $desde, $hasta)
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
        $q = "SELECT coalesce(sum(v.total), 0) as total
			from venta v
			join caja c on v.id_caja = c.id_caja
			join pago_venta pv on v.id_venta = pv.id_venta
			join fpago fp on pv.id_fpago = fp.id_fpago
			where fp.escortesia = false and c.fecha between '" . $de . "' and '" . $a . "'";
        if ($id_caja > 0) {
            $q = $q . " and c.id_caja = $id_caja";
        }
        $query = $this->db->query($q)->result();
        return $query;
    }

    public function resumenCaja($id_caja, $desde, $hasta)
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
        $q = "SELECT coalesce(sum(dc.ingreso),0) as ingresos, coalesce(sum(dc.egreso),0) as egresos,";
        if ($id_caja > 0) {
            $q = $q . "
				(SELECT coalesce(sum(ca.fondo_fijo), 0) as fondo_fijo from caja ca where ca.fecha between '" . $de . "' and '" . $a . "' and ca.id_caja =  $id_caja),
				(SELECT coalesce(sum(vv.valor_propina), 0) as propina from venta vv join caja cc on vv.id_caja = cc.id_caja where cc.fecha between '" . $de . "' and '" . $a . "' and cc.id_caja =  $id_caja),
				(SELECT coalesce(sum(vee.monto_descuento), 0) as descuento from venta vee join caja caa on vee.id_caja = caa.id_caja where caa.fecha between '" . $de . "' and '" . $a . "' and caa.id_caja =  $id_caja)
				from caja c
				join detalle_caja dc on dc.id_caja = c.id_caja
				where c.fecha between '" . $de . "' and '" . $a . "' and c.id_caja = $id_caja";
        } else {
            $q = $q . "
				(SELECT coalesce(sum(ca.fondo_fijo), 0) as fondo_fijo from caja ca where ca.fecha between '" . $de . "' and '" . $a . "'),
				(SELECT coalesce(sum(vv.valor_propina), 0) as propina from venta vv join caja cc on vv.id_caja = cc.id_caja where cc.fecha between '" . $de . "' and '" . $a . "'),
				(SELECT coalesce(sum(vee.monto_descuento), 0) as descuento from venta vee join caja caa on vee.id_caja = caa.id_caja where caa.fecha between '" . $de . "' and '" . $a . "')
				from caja c
				join detalle_caja dc on dc.id_caja = c.id_caja
				where c.fecha between '" . $de . "' and '" . $a . "'";
        }
        $query = $this->db->query($q)->result();
        return $query;
    }

    public function resumenTipoDocumento($id_caja, $desde, $hasta)
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
        $q = "SELECT td.id_tdocumento, td.nombre_tdocumento, count(v.id_tdocumento) as cantidad, round(sum(v.total)) as total,
        round(sum(v.neto)) as neto, round(sum(v.iva)) as iva
        from venta v
        join tdocumento td on v.id_tdocumento = td.id_tdocumento
        join caja c on v.id_caja = c.id_caja
        join pago_venta pv on v.id_venta = pv.id_venta
        join fpago fp on pv.id_fpago = fp.id_fpago
        where fp.escortesia = false and c.fecha between '" . $de . "' and '" . $a . "'";
        if ($id_caja > 0) {
            $q = $q . " and c.id_caja = $id_caja
            group by td.id_tdocumento";
        } else {
            $q = $q . " group by td.id_tdocumento";
        }
        $query = $this->db->query($q)->result();
        return $query;
    }

    public function resumenFormaPago($id_caja, $desde, $hasta)
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
        $q = "SELECT fp.nombre_fpago, count(fp.id_fpago) as cantidad, round(sum(v.total)) as total, round(sum(pv.propina)) as propina,  round(sum(v.neto)) as neto, round(sum(v.iva)) as iva
            from pago_venta pv
			join fpago fp on pv.id_fpago = fp.id_fpago
			join venta v on pv.id_venta = v.id_venta
			join caja c on v.id_caja = c.id_caja
			where fp.escortesia = false and c.fecha between '" . $de . "' and '" . $a . "'";
        if ($id_caja > 0) {
            $q = $q . " and c.id_caja = $id_caja
			group by fp.id_fpago
			order by total desc, fp.id_fpago asc";
        } else {
            $q = $q . " group by fp.id_fpago
			order by  total desc, fp.id_fpago asc";
        }
        $query = $this->db->query($q)->result();
        return $query;
    }

    public function resumenCortesias($id_caja, $desde, $hasta)
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
        $q = "SELECT fp.nombre_fpago, count(fp.id_fpago) as cantidad, round(sum(v.total)) as total, round(sum(pv.propina)) as propina,  round(sum(v.neto)) as neto, round(sum(v.iva)) as iva
            from pago_venta pv
			join fpago fp on pv.id_fpago = fp.id_fpago
			join venta v on pv.id_venta = v.id_venta
			join caja c on v.id_caja = c.id_caja
			where fp.escortesia = true and c.fecha between '" . $de . "' and '" . $a . "'";
        if ($id_caja > 0) {
            $q = $q . " and c.id_caja = $id_caja
			group by fp.id_fpago
			order by total desc, fp.id_fpago asc";
        } else {
            $q = $q . " group by fp.id_fpago
			order by  total desc, fp.id_fpago asc";
        }
        $query = $this->db->query($q)->result();
        return $query;
    }

    public function resumenVentasPorPagar()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }

        $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        $q = "SELECT co.id_cotizacion, co.id_mesa, to_char(co.fecha_transaccion, 'DD-MM-YYYY / HH24:MI') as fecha_hora, u.nombre_usuario as garzon, cl.nombre_cliente, co.total as cuenta, co.descuento, (co.total - co.descuento) as total, co.id_divpedido, co.neto, co.iva
			from cotizacion co
			join detalle_cotizacion dc on co.id_cotizacion = dc.id_cotizacion
			join usuario u on co.id_usuario = u.id_usuario
			join cliente cl on co.id_cliente = cl.id_cliente
			where co.id_venta = 0 and co.pagado = false and co.eseliminado = false and co.esanulado = false and co.vigente = true
			group by co.id_cotizacion, u.nombre_usuario, cl.nombre_cliente
			order by co.id_mesa, co.id_cotizacion";
        $consulta = $this->db->query($q);
        $sampleData = $consulta->result();
        $data = array();

        foreach ($sampleData as $key) {
            array_push($data, array(
                "id_cotizacion" => $key->id_cotizacion,
                "id_mesa" => $key->id_mesa,
                "fecha_hora" => $key->fecha_hora /*.'<br><br><sup>'.$key->detalle.'</sup>'*/ ,
                'garzon' => $key->garzon,
                'nombre_cliente' => $key->nombre_cliente,
                'cuenta' => $key->cuenta,
                'descuento' => $key->descuento,
                /*'neto' => $key->neto,
                'iva' => $key->iva,*/
                'total' => $key->total,
                'id_divpedido' => ($key->id_divpedido == 0 ? '<h5>NO</h5>' : '<h5>SI</h5>'),
                'btnVerMesas' => '<button type="button" onclick="detalleMesas(' . $key->id_cotizacion . ')" class="btn btn-labeled btn-success m-b-5" data-toggle="modal" data-target="#modal-success"><span class="btn-label"><i class="glyphicon glyphicon-ok"></i></span>Ver Detalle</button>',
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

    public function detalleMesas($id_cotizacion)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }

        $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        $q = "SELECT p.nombre_producto, sum(dc.cantidad) as cantidad, dc.bruto_un, round(sum(dc.total_bruto)) as total_bruto, to_char(dc.fecha_movimiento, 'HH24:MI') as hora_pedido, dc.mensaje, round(sum(dc.neto)) as neto, round(sum(dc.iva)) as iva
			from detalle_cotizacion dc
			join producto p on dc.id_producto = p.id_producto
			where dc.id_cotizacion =$id_cotizacion" .
            " group by p.nombre_producto, dc.bruto_un, hora_pedido, dc.mensaje
			order by hora_pedido asc";

        $consulta = $this->db->query($q);
        $sampleData = $consulta->result();
        $data = array();

        foreach ($sampleData as $key) {
            array_push($data, array(
                "nombre_producto" => ($key->mensaje > 0 ? $key->nombre_producto . ' <br><sup>MENSAJE PEDIDO: ' . $key->mensaje . '</sup>' : $key->nombre_producto),
                "cantidad" => $key->cantidad,
                "bruto_un" => $key->bruto_un,
                /*"neto" => $key->neto,
                "iva" => $key->iva,*/
                'total_bruto' => $key->total_bruto,
                'hora_pedido' => $key->hora_pedido,
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

    public function detalleAtencion($id_caja, $desde, $hasta)
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
        $q = "SELECT u.nombre_usuario, count(co.id_mesa) as mesas_atendidas, round(sum(co.total - co.monto_descuento)) as ventas,
        round(sum(v.valor_propina)) as propina, co.venta_directa, round(sum(v.neto)) as neto, round(sum(v.iva)) as iva
        from cotizacion co
        join usuario u on co.id_usuario = u.id_usuario
        join venta v on co.id_venta = v.id_venta
        join caja ca on v.id_caja = ca.id_caja
        join pago_venta pv on v.id_venta = pv.id_venta
        join fpago fp on pv.id_fpago = fp.id_fpago
        where fp.escortesia = false and ca.fecha  between '" . $de . "' and '" . $a . "'";
        if ($id_caja > 0) {
            $q = $q . " and v.id_caja = $id_caja
            group by u.nombre_usuario, co.venta_directa";
        } else {
            $q = $q . " group by u.nombre_usuario, co.venta_directa";
        }

        $consulta = $this->db->query($q);
        $sampleData = $consulta->result();
        $data = array();

        foreach ($sampleData as $key) {
            array_push($data, array(
                "nombre_usuario" => ($key->venta_directa == 'f' ? $key->nombre_usuario : 'VENTA DIRECTA'),
                "mesas_atendidas" => $key->mesas_atendidas,
                /*"neto" => $key->neto,
                "iva" => $key->iva,*/
                "ventas" => $key->ventas,
                "propina" => $key->propina,
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

    public function pieVentasPorFamilia($id_caja, $desde, $hasta)
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
        $q = "SELECT f.nombre_familia, sum(dv.total_bruto_con_descuento) as monto_total,
			round(((sum(dv.cantidad)/
				(select sum(dve.cantidad)
				from detalle_venta dve
				join venta ve on dve.id_venta = ve.id_venta
				join caja caj on ve.id_caja = caj.id_caja
				where caj.fecha between '" . $de . "' and '" . $a . "'))*100),3) as porcentaje
			from familia f
			join producto p on f.id_familia = p.id_familia
			join detalle_venta dv on p.id_producto = dv.id_producto
			join venta v on dv.id_venta = v.id_venta
			join caja ca on v.id_caja = ca.id_caja
			where ca.fecha between '" . $de . "' and '" . $a . "'";
        if ($id_caja > 0) {
            $q = $q . " and ca.id_caja = $id_caja
				group by f.nombre_familia";
        } else {
            $q = $q . " group by f.nombre_familia";
        }

        $query = $this->db->query($q)->result();
        return $query;
    }

    public function tableVentasPorFamilia($id_caja, $desde, $hasta)
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
			where fp.escortesia = false and fp.vigente = true and ca.fecha between '" . $de . "' and '" . $a . "'";
        if ($id_caja > 0) {
            $q = $q . " and ca.id_caja = $id_caja
			group by f.id_familia";
        } else {
            $q = $q . " group by f.id_familia";
        }

        $consulta = $this->db->query($q);
        $sampleData = $consulta->result();
        $data = array();

        foreach ($sampleData as $key) {
            array_push($data, array(
                "id_familia" => $key->id_familia,
                "nombre_familia" => $key->nombre_familia,
                "cantidad" => $key->cantidad,
                /* "monto_neto" => $key->monto_neto,
                 "monto_iva" => $key->monto_iva,*/
                "monto_total" => $key->monto_total,
                "btnVerDetalleFamilia" => '<button type="button" onclick="ventasPorProductos(' . $key->id_familia . ')" class="btn btn-labeled btn-success m-b-5" data-toggle="modal" data-target="#modalProductos"><span class="btn-label"><i class="glyphicon glyphicon-ok"></i></span>Ver Detalle</button>',
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

    public function ventasPorProducto($id_caja, $id_familia, $desde, $hasta)
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
        $q = "SELECT p.nombre_producto, cast(sum(dv.cantidad)as INT) as cantidad, round(sum(dv.total_bruto)) as monto_total, round(sum(dv.neto)) as monto_neto, round(sum(dv.iva)) as monto_iva
        from producto p
        join familia f on p.id_familia = f.id_familia
        join detalle_venta dv on p.id_producto = dv.id_producto
        join venta v on dv.id_venta = v.id_venta
        join caja ca on v.id_caja = ca.id_caja
        join pago_venta pv on v.id_venta = pv.id_venta
        join fpago fp on pv.id_fpago = fp.id_fpago
        where fp.escortesia = false and fp.vigente = true and ca.fecha between '" . $de . "' and '" . $a . "' and f.id_familia = $id_familia";
        if ($id_caja > 0) {
            $q = $q . " and ca.id_caja = $id_caja
                group by p.nombre_producto";
        } else {
            $q = $q . " group by p.nombre_producto";
        }

        $consulta = $this->db->query($q);
        $sampleData = $consulta->result();
        $data = array();

        foreach ($sampleData as $key) {
            array_push($data, array(
                "nombre_producto" => $key->nombre_producto,
                "cantidad" => $key->cantidad,
                /*"monto_neto" => $key->monto_neto,
                "monto_iva" => $key->monto_iva,*/
                "monto_total" => $key->monto_total
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

    public function ventasPorMesas($id_caja, $desde, $hasta)
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
        $q = "SELECT v.id_mesa, count(v.id_mesa) as cantidad, round(sum(v.neto)) as monto_neto, round(sum(v.iva)) as monto_iva,
            round(sum(v.total)) as monto_total
            from venta v
			join pago_venta pv on v.id_venta = pv.id_venta
			join fpago fp on pv.id_fpago = fp.id_fpago
			join caja c on v.id_caja = c.id_caja
			where fp.escortesia = false and vigente = true and c.fecha between '" . $de . "' and '" . $a . "'";
        if ($id_caja > 0) {
            $q = $q . " and c.id_caja = $id_caja
				group by v.id_mesa
				order by v.id_mesa";
        } else {
            $q = $q . " group by v.id_mesa
				order by v.id_mesa";
        }

        $consulta = $this->db->query($q);
        $sampleData = $consulta->result();
        $data = array();

        foreach ($sampleData as $key) {
            array_push($data, array(
                "id_mesa" => ($key->id_mesa == 0 ? 'VENTA DIRECTA' : $key->id_mesa),
                "cantidad" => $key->cantidad,
                /*"monto_neto" => $key->monto_neto,
                "monto_iva" => $key->monto_iva,*/
                "monto_total" => $key->monto_total,

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

    public function cajasAbiertas($desde, $hasta)
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
        $q = "SELECT c.id_caja, u.nombre_usuario, to_char(c.fecha, 'DD/MM/YYYY') as fecha
			from caja c
			join usuario u on u.id_usuario = c.id_usuario
			where c.fecha between '" . $de . "' and '" . $a . "'";

        $query = $this->db->query($q)->result();
        return $query;
    }

    public function datosEmpresa()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }

        $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        $q = "SELECT * from empresa";
        $consulta = $this->db->query($q);
        return $consulta->result();
    }

    public function datosCaja($desde, $hasta, $idCaja)
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
        /*
        $q = "SELECT sum(c.fondo_fijo) as fondo_fijo, sum(c.descuento) as descuento, sum(c.propina) as propina,
        sum(c.ingresos) as ingresos, sum(c.egresos) as egresos, to_char(c.fecha,'dd-mm-yyyy') as fecha
        from caja c
        where c.fecha between '" . $de . "' and '" . $a . "'";*/

        $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        $q = "SELECT coalesce(sum(dc.ingreso),0) as ingresos, coalesce(sum(dc.egreso),0) as egresos,";
        if ($idCaja > 0) {
            $q = $q . "
				(SELECT coalesce(sum(ca.fondo_fijo), 0) as fondo_fijo from caja ca where ca.fecha between '" . $de . "' and '" . $a . "' and ca.id_caja =  $idCaja),
				(SELECT coalesce(sum(vv.valor_propina), 0) as propina from venta vv join caja cc on vv.id_caja = cc.id_caja where cc.fecha between '" . $de . "' and '" . $a . "' and cc.id_caja =  $idCaja),
				(SELECT coalesce(sum(vee.monto_descuento), 0) as descuento from venta vee join caja caa on vee.id_caja = caa.id_caja where caa.fecha between '" . $de . "' and '" . $a . "' and caa.id_caja =  $idCaja)
				from caja c
				join detalle_caja dc on dc.id_caja = c.id_caja
				where c.fecha between '" . $de . "' and '" . $a . "' and c.id_caja = $idCaja";
        } else {
            $q = $q . "
				(SELECT coalesce(sum(ca.fondo_fijo), 0) as fondo_fijo from caja ca where ca.fecha between '" . $de . "' and '" . $a . "'),
				(SELECT coalesce(sum(vv.valor_propina), 0) as propina from venta vv join caja cc on vv.id_caja = cc.id_caja where cc.fecha between '" . $de . "' and '" . $a . "'),
				(SELECT coalesce(sum(vee.monto_descuento), 0) as descuento from venta vee join caja caa on vee.id_caja = caa.id_caja where caa.fecha between '" . $de . "' and '" . $a . "')
				from caja c
				join detalle_caja dc on dc.id_caja = c.id_caja
				where c.fecha between '" . $de . "' and '" . $a . "'";
        }
        $consulta = $this->db->query($q);
        return $consulta->result();
    }

    public function datosTipoDte($desde, $hasta, $idCaja)
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

        $q = "SELECT td.id_tdocumento, td.nombre_tdocumento as nombre_tdocumento, count(v.id_tdocumento) as cantidad, round(sum(v.subtotal)) as total, 
			round(sum(v.neto)) as neto, round(sum(v.iva)) as iva
			from venta v
			join tdocumento td on v.id_tdocumento = td.id_tdocumento
			join caja c on v.id_caja = c.id_caja
			join pago_venta pv on v.id_venta = pv.id_venta
			join fpago fp on pv.id_fpago = fp.id_fpago
		   where c.fecha between '" . $de . "' and '" . $a . "'";
        if ($idCaja > 0) {
            $q = $q . " and c.id_caja = $idCaja
			group by td.id_tdocumento, nombre_tdocumento";
        } else {
            $q = $q . " group by td.id_tdocumento, nombre_tdocumento";
        }

        $query = $this->db->query($q)->result();
        return $query;
    }

    public function datosFpago($desde, $hasta, $idCaja)
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

        $q = "SELECT fp.nombre_fpago as nombre_fpago, count(fp.id_fpago) as cantidad, round(sum(v.subtotal)) as total, round(sum(pv.propina)) as propina,  round(sum(v.neto)) as neto, round(sum(v.iva)) as iva
			from pago_venta pv
			join fpago fp on pv.id_fpago = fp.id_fpago
			join venta v on pv.id_venta = v.id_venta
			join caja c on v.id_caja = c.id_caja
			where  c.fecha between '" . $de . "' and '" . $a . "'";
        if ($idCaja > 0) {
            $q = $q . " and c.id_caja = $idCaja
				group by fp.id_fpago";
        } else {
            $q = $q . " group by fp.id_fpago";
        }

        $query = $this->db->query($q)->result();
        return $query;
    }

    public function datosCortesias($desde, $hasta, $idCaja)
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

        $q = "SELECT fp.nombre_fpago, count(fp.id_fpago) as cantidad, round(sum(v.subtotal)) as total, round(sum(pv.propina)) as propina,  round(sum(v.neto)) as neto, round(sum(v.iva)) as iva
			from pago_venta pv
			join fpago fp on pv.id_fpago = fp.id_fpago
			join venta v on pv.id_venta = v.id_venta
			join caja c on v.id_caja = c.id_caja
			where fp.escortesia = true and c.fecha between '" . $de . "' and '" . $a . "'";
        if ($idCaja > 0) {
            $q = $q . " and c.id_caja = $idCaja
					group by fp.id_fpago";
        } else {
            $q = $q . " group by fp.id_fpago";
        }
        $query = $this->db->query($q)->result();
        return $query;
    }

    public function totalSistema($desde, $hasta, $idCaja)
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

        //$q = " SELECT sum(v.total) as monto_total_ventas from venta v";
        $q = "SELECT coalesce(round(sum(v.subtotal)), 0) as monto_total_ventas
			from venta v
			join caja c on v.id_caja = c.id_caja
			join pago_venta pv on v.id_venta = pv.id_venta
			join fpago fp on pv.id_fpago = fp.id_fpago
			where fp.escortesia = false and c.fecha between '" . $de . "' and '" . $a . "'";
        if ($idCaja > 0) {
            $q = $q . " and c.id_caja = $idCaja";
        }
        $query = $this->db->query($q)->result();
        return $query;
    }



    public function obtenerDetalleDeIngresos($id_caja, $desde, $hasta)
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

        $q = "SELECT c.id_caja,dc.id_detalle_caja as numero_ingreso, dc.ingreso, dc.observacion, dc.descripcion_corta, cc.nombre_ccosto,
        to_char(dc.fecha_movimiento, 'DD/MM/YYYY HH24:MM:SS') AS fecha_hora_ingreso
		from caja c
		join detalle_caja dc on c.id_caja=dc.id_caja
		join ccosto cc on cc.id_ccosto=dc.id_ccosto
		where dc.ingreso>0 and c.fecha between '" . $de . "' and '" . $a . "'";
        if ($id_caja > 0) {
            $q = $q . " and c.id_caja = $id_caja";
        }
        $q = $q . "order by dc.id_detalle_caja asc";
        $query = $this->db->query($q)->result();
        return $query;
    }
    public function obtenerDetalleDeRetiros($id_caja, $desde, $hasta)
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

        $q = "SELECT c.id_caja,dc.id_detalle_caja as numero_ingreso, dc.egreso, dc.observacion, dc.descripcion_corta, cc.nombre_ccosto,
        to_char(dc.fecha_movimiento, 'DD/MM/YYYY HH24:MM:SS') AS fecha_hora_retiro
		from caja c
		join detalle_caja dc on c.id_caja=dc.id_caja
		join ccosto cc on cc.id_ccosto=dc.id_ccosto
		where dc.egreso>0 and c.fecha between '" . $de . "' and '" . $a . "'";
        if ($id_caja > 0) {
            $q = $q . " and c.id_caja = $id_caja 
			order by dc.id_detalle_caja asc";
        } else {
            $q = $q . " order by dc.id_detalle_caja asc";
        }
        $query = $this->db->query($q)->result();
        return $query;
    }

    public function obtenerDetalleDeDescuentos($id_caja, $desde, $hasta)
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

        $q = "SELECT vee.id_venta, round(vee.por_descuento) as por_descuento, vee.monto_descuento, c.nombre_cliente, vee.subtotal, vee.total, vee.folio_cotizacion, vee.id_mesa
        from venta vee 
        join caja caa on vee.id_caja = caa.id_caja
        join cliente c on vee.id_cliente = c.id_cliente 
        where vee.monto_descuento>0 and caa.fecha between '" . $de . "' and '" . $a . "'";
        if ($id_caja > 0) {
            $q = $q . " and vee.id_caja = $id_caja 
			order by vee.id_venta asc";
        } else {
            $q = $q . " order by vee.id_venta asc";
        }
        $query = $this->db->query($q)->result();
        return $query;
    }

    public function obtenerDetalleDePropinas($id_caja, $desde, $hasta, $id_usuario)
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
        $q = "SELECT v.id_usuario_garzon, u.nombre_usuario, fp.nombre_fpago, round(sum(v.valor_propina)) as total_propina 
        FROM venta v
        JOIN usuario u ON V.id_usuario_garzon=u.id_usuario
        JOIN fpago fp ON V.id_fpagopropina=fp.id_fpago
        where v.fecha between '" . $de . "' and '" . $a . "'";
        if ($id_caja > 0) {
            $q = $q . " and v.id_caja = $id_caja ";
        }
        if ($id_usuario > 0) {
            $q = $q . " and v.id_usuario_garzon = $id_usuario ";
        }
        $q = $q . " group by v.id_usuario_garzon, u.nombre_usuario, fp.nombre_fpago";


        $consulta = $this->db->query($q);
        $sampleData = $consulta->result();
        $data = array();

        foreach ($sampleData as $key) {
            array_push($data, array(
                "id_usuario_garzon" => $key->id_usuario_garzon,
                "nombre_usuario" => $key->nombre_usuario,
                "nombre_fpago" => $key->nombre_fpago,
                "total_propina" => $key->total_propina
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