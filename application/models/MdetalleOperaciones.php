<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MdetalleOperaciones extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function resumenDeOperaciones($desde, $hasta, $idFpago, $idUsuario)
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
        $q = "SELECT v.id_venta, u.nombre_usuario, co.id_mesa, count(index_comensal) AS comensales, to_char(co.fecha_transaccion, 'DD/MM/YYYY HH24:MM:SS') AS fecha_inicio, 
				to_char(date_trunc('second',sum(v.fecha_transaccion-co.fecha_transaccion)), 'HH24:MM:SS') as tiempo_atencion, co.mensaje as observacion, fp.nombre_fpago, round(v.total) as total
			from venta v
			join cotizacion co on v.id_venta = co.id_venta
			join usuario u on co.id_usuario = u.id_usuario
			join pago_venta pv on v.id_venta = pv.id_venta
			join fpago fp on pv.id_fpago = fp.id_fpago
			where v.fecha between '" . $de . "' and '" . $a . "'";
        if ($idFpago > 0) {
            $q = $q . " and pv.id_fpago=$idFpago";
        }
        if ($idUsuario > 0) {
            $q = $q . " and u.id_usuario=$idUsuario";
        }
        $q = $q . " group by v.id_venta, u.nombre_usuario, co.id_cotizacion, fp.id_fpago";


        $consulta = $this->db->query($q);
        $sampleData = $consulta->result();
        $data = array();

        foreach ($sampleData as $key) {
            array_push($data, array(
                // "btn_compras" => '<button onclick="resumenComprasPDF(' . $key->id_compra . ')" type="submit" class="btn btn-danger"><i class="fa fa-file-text-o"></i></button>',
                "id_venta" => $key->id_venta,
                "nombre_usuario" => $key->nombre_usuario . ' / ' . $key->id_mesa,
                "comensales" => $key->comensales,
                'fecha_inicio' => $key->fecha_inicio . ' / ' . $key->tiempo_atencion,
                'observacion' => $key->observacion == null ? '-' : $key->observacion,
                'nombre_fpago' => $key->nombre_fpago,
                'total' => $key->total,
                'btn_ver_detalle' => ('<button onclick="cargarValidacionDetalleDeOperaciones() + VerModalDetalleDeOperaciones(' . $key->id_venta . ')" type="submit" class="btn btn-success btn-md"><i class="fa fa-file-text"></i>VER DETALLE</button>'),

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

    public function ResumenDetalleOperaciones($id_venta)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }

        $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        $q = "SELECT dv.id_detalle_venta, p.nombre_producto, dv.cantidad, dv.bruto_un, round(dv.subtotal_neto_con_descuento) as neto, round(dv.monto_iva) as iva, round(dv.total_bruto_con_descuento) as total_bruto 
		from detalle_venta dv 
		join producto p on dv.id_producto=p.id_producto
		where dv.id_venta =$id_venta";

        $consulta = $this->db->query($q);
        $sampleData = $consulta->result();
        $data = array();

        foreach ($sampleData as $key) {
            array_push($data, array(
                "nombre_producto" => $key->nombre_producto,
                "cantidad" => $key->cantidad,
                "bruto_un" => $key->bruto_un,
                /*"neto" => $key->neto,
                "iva" => $key->iva,*/
                'total_bruto' => $key->total_bruto,
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

    public function ListarFormasDePago()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }

        $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        $q = "SELECT id_fpago, nombre_fpago from fpago order by nombre_fpago";

        $query = $this->db->query($q)->result();
        return $query;
    }
    public function ListarUsuarios()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }

        $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        $q = "SELECT id_usuario, nombre_usuario from usuario order by nombre_usuario";

        $query = $this->db->query($q)->result();
        return $query;
    }

    public function DetalleFormaDePagoVenta($id_venta)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        $q = 'SELECT  v.id_venta, v.folio, pv.id_fpago, fp.nombre_fpago, td.nombre_tdocumento from venta v
        join pago_venta pv on pv.id_venta=v.id_venta
        join fpago fp on fp.id_fpago=pv.id_fpago
        join tdocumento td on td.id_tdocumento=v.id_tdocumento
        where v.id_venta=' . $id_venta . '';

        $query = $this->db->query($q)->result();
        return $query;
    }

    public function actualizarFormaDePagoVenta($id, $data)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        $this->db->where('id_venta', $id);
        $this->db->update('pago_venta', $data);
        $consulta = $this->db->query("select " . $id . " as id_venta");
        return $consulta->result();
    }
}