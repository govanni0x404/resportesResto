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
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
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
        // 14-11-2026: una fila por venta. Antes el JOIN con pago_venta repetía la venta (con su total completo)
        // por cada forma de pago de un pago dividido. Las formas de pago se muestran juntas ("EFECTIVO + DEBITO").
        $q = "SELECT v.id_venta, u.nombre_usuario, co.id_mesa, count(index_comensal) AS comensales, to_char(co.fecha_transaccion, 'DD/MM/YYYY HH24:MI:SS') AS fecha_inicio, 
				to_char(date_trunc('second',sum(v.fecha_transaccion-co.fecha_transaccion)), 'HH24:MI:SS') as tiempo_atencion, co.mensaje as observacion, pg.nombre_fpago, round(v.total) as total
			from venta v
			join cotizacion co on v.id_venta = co.id_venta
			join usuario u on co.id_usuario = u.id_usuario
			join lateral (SELECT string_agg(fp.nombre_fpago, ' + ' ORDER BY pv.id_pventa) AS nombre_fpago
				FROM pago_venta pv JOIN fpago fp ON pv.id_fpago = fp.id_fpago WHERE pv.id_venta = v.id_venta) pg on pg.nombre_fpago IS NOT NULL
			where v.fecha between ? and ?";
        if ($idFpago > 0) {
            $q = $q . " and EXISTS (SELECT 1 FROM pago_venta px WHERE px.id_venta = v.id_venta AND px.id_fpago = " . (int)$idFpago . ")";
        }
        if ($idUsuario > 0) {
            $q = $q . " and u.id_usuario=" . (int)$idUsuario;
        }
        $q = $q . " group by v.id_venta, u.nombre_usuario, co.id_cotizacion, pg.nombre_fpago";


        $consulta = $this->db->query($q, array($de, $a));
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
                'btn_ver_detalle' => ('<button onclick="VerModalDetalleDeOperaciones(' . $key->id_venta . ')" type="submit" class="btn btn-success btn-md"><i class="fa fa-file-text"></i>VER DETALLE</button>'),

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
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }

        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $q = "SELECT dv.id_detalle_venta, p.nombre_producto, dv.cantidad, dv.bruto_un, round(dv.subtotal_neto_con_descuento) as neto, round(dv.monto_iva) as iva, round(dv.total_bruto_con_descuento) as total_bruto 
		from detalle_venta dv 
		join producto p on dv.id_producto=p.id_producto
		where dv.id_venta =" . (int)$id_venta;

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
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }

        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $q = "SELECT id_fpago, nombre_fpago from fpago order by nombre_fpago";

        $query = $this->db->query($q)->result();
        return $query;
    }
    public function ListarUsuarios()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }

        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $q = "SELECT id_usuario, nombre_usuario from usuario order by nombre_usuario";

        $query = $this->db->query($q)->result();
        return $query;
    }

    /**
     * Pagos de una venta (14-11-2026: uno por fila, también en pago dividido), con el monto de cada uno sin la propina.
     */
    public function DetalleFormaDePagoVenta($id_venta)
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $q = "SELECT v.id_venta, v.folio, pv.id_pventa, pv.id_fpago, fp.nombre_fpago, td.nombre_tdocumento,
                (COALESCE(pv.monto_pagado, 0) - COALESCE(pv.propina, 0)) AS monto
            FROM venta v
            JOIN pago_venta pv ON pv.id_venta = v.id_venta
            JOIN fpago fp ON fp.id_fpago = pv.id_fpago
            LEFT JOIN tdocumento td ON td.id_tdocumento = v.id_tdocumento
            WHERE v.id_venta = ?
            ORDER BY pv.id_pventa";
        return $this->db->query($q, array((int)$id_venta))->result();
    }

    /**
     * Cambia la forma de pago de UN pago de la venta (14-11-2026, QA).
     * Antes se cambiaban todos los pagos de la venta: un pago dividido (efectivo + débito) quedaba entero en una sola
     * forma. Reglas:
     *  - Con varios pagos hay que elegir cuál ($idPventa).
     *  - Ni desde ni hacia crédito directo o cortesía: el trigger f_creditodirecto solo crea la deuda al insertar el
     *    pago, así que la cuenta corriente del cliente quedaría mal; eso se hace anulando o devolviendo la venta.
     *  - Venta anulada: no.
     *  - Caja ya cerrada: solo gerencia (el cierre quedó grabado con los montos de cada forma de pago).
     * Devuelve array('ok' => true) o array('error' => mensaje).
     */
    public function actualizarFormaDePagoVenta($idVenta, $idPventa, $idFpago, $idRol)
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $this->load->helper('tenant'); $db = get_tenant_db($this);
        $db->db_debug = FALSE;
        $db->trans_begin();
        try {
            $v = $db->query("SELECT v.id_venta, COALESCE(v.mensaje_pedido, '') AS mensaje_pedido, COALESCE(c.cerrada, false) AS cerrada
                FROM venta v LEFT JOIN caja c ON c.id_caja = v.id_caja WHERE v.id_venta = ? FOR UPDATE OF v", array((int)$idVenta))->row();
            if (!$v) throw new Exception('La venta no existe');
            if (strpos($v->mensaje_pedido, 'ANULADA') === 0) throw new Exception('La venta está anulada: no se le cambia la forma de pago');
            $cerrada = $v->cerrada === 't' || $v->cerrada === true;
            if ($cerrada && (int)$idRol !== 1) throw new Exception('La caja de esta venta ya está cerrada: solo gerencia puede cambiar la forma de pago');

            $pagos = $db->query('SELECT pv.id_pventa, pv.id_fpago, fp.nombre_fpago, (fp.escortesia IS TRUE) AS cortesia
                FROM pago_venta pv JOIN fpago fp ON fp.id_fpago = pv.id_fpago WHERE pv.id_venta = ? ORDER BY pv.id_pventa FOR UPDATE OF pv',
                array((int)$idVenta))->result();
            if (!$pagos) throw new Exception('La venta no tiene pagos registrados');
            if (count($pagos) > 1 && (int)$idPventa <= 0) throw new Exception('La venta tiene varios pagos: elige cuál cambiar');
            $pago = null;
            foreach ($pagos as $p) {
                if ((int)$idPventa <= 0 || (int)$p->id_pventa === (int)$idPventa) { $pago = $p; break; }
            }
            if (!$pago) throw new Exception('El pago elegido no es de esta venta. Vuelve a abrir la venta.');

            $nueva = $db->query('SELECT id_fpago, nombre_fpago, (escortesia IS TRUE) AS cortesia FROM fpago WHERE id_fpago = ? AND vigente IS NOT FALSE',
                array((int)$idFpago))->row();
            if (!$nueva) throw new Exception('La nueva forma de pago no existe o no está vigente');
            if ((int)$nueva->id_fpago === (int)$pago->id_fpago) throw new Exception('Elige una forma de pago distinta a la actual');
            $cfg = $db->query('SELECT COALESCE(id_fpago_creditodirecto, 0) AS credito FROM configuracion LIMIT 1')->row();
            $credito = $cfg ? (int)$cfg->credito : 0;
            $esCredito = function ($id, $nombre) use ($credito) {
                return ($credito > 0 && (int)$id === $credito) || stripos((string)$nombre, 'CREDITO DIRECTO') !== false;
            };
            if ($esCredito($pago->id_fpago, $pago->nombre_fpago) || $esCredito($nueva->id_fpago, $nueva->nombre_fpago)) {
                throw new Exception('Un pago a crédito no se cambia aquí (la deuda del cliente quedaría mal): anula o devuelve la venta');
            }
            $esCortesia = function ($x) { return $x === 't' || $x === true; };
            if ($esCortesia($pago->cortesia) || $esCortesia($nueva->cortesia)) {
                throw new Exception('Una cortesía no se cambia aquí (cambia el tipo de venta): anula la venta y vuelve a hacerla');
            }
            if (!$db->query('UPDATE pago_venta SET id_fpago = ? WHERE id_pventa = ? AND id_venta = ?',
                array((int)$nueva->id_fpago, (int)$pago->id_pventa, (int)$idVenta))) {
                $e = $db->error();
                throw new Exception('No se pudo actualizar: ' . $e['message']);
            }
            $db->trans_commit();
            return array('ok' => true, 'id_venta' => (int)$idVenta, 'pago' => (string)$pago->nombre_fpago, 'nueva' => (string)$nueva->nombre_fpago);
        } catch (Exception $e) {
            $db->trans_rollback();
            return array('error' => $e->getMessage());
        }
    }
}
