<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MlistaDePrecios extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function verificarColumnaExiste($tabla, $columna)
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }

        $this->load->helper('tenant'); $this->db = get_tenant_db($this);

        $query = $this->db->query("
			SELECT EXISTS (
				SELECT 1 
				FROM information_schema.columns 
				WHERE table_name = ? 
				AND column_name = ?
			) as existe
		", array($tabla, $columna));

        $result = $query->row();
        return $result->existe === 't' || $result->existe === true;
    }

    public function ListadoListasDePrecios()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $q = "SELECT id_lprecio, nombre_lprecio from lprecio order by nombre_lprecio asc";
        $query = $this->db->query($q)->result();
        return $query;
    }

    public function ListadoDeFamilias()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $q = "SELECT id_familia, nombre_familia from familia order by nombre_familia asc";
        $query = $this->db->query($q)->result();
        return $query;
    }

    public function InformeListaDePrecios($id_lprecio, $id_familia)
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        // $q = "SELECT p.id_producto, p.cbarra, p.nombre_producto, p.id_familia, f.nombre_familia, u.nombre_umedida, dl.total, p.precio_ucompra, dl.id_lprecio from Producto p
        // join familia f on f.id_familia=p.id_familia
        // join umedida u on p.id_umedida=u.id_umedida
        // join detalle_lprecio dl on dl.id_producto=p.id_producto
        // where p.vigente=true and p.venta_publico=true";
        $tieneComision = $this->verificarColumnaExiste('detalle_lprecio', 'monto_comision');
        $selectComision = $tieneComision ? "COALESCE(dl.monto_comision, 0) as monto_comision" : "0 as monto_comision";

        $q = "SELECT p.id_producto, p.cbarra, p.nombre_producto, p.id_familia, f.nombre_familia, u.nombre_umedida, dl.total, dl.por_ganancia, dl.costo, dl.por_costo, $selectComision,
        p.precio_ucompra, dl.id_lprecio
        FROM Producto p
        JOIN familia f ON f.id_familia = p.id_familia
        JOIN umedida u ON p.id_umedida = u.id_umedida
        JOIN detalle_lprecio dl ON dl.id_producto = p.id_producto
        WHERE p.vigente = true 
        AND p.venta_publico = true";

        if ((int)$id_familia > 0) {
            $id_familia = (int)$id_familia;
            $q = $q . " and f.id_familia=$id_familia";
        }
        if ((int)$id_lprecio > 0) {
            $id_lprecio = (int)$id_lprecio;
            $q = $q . " and dl.id_lprecio=$id_lprecio";
        }


        $consulta = $this->db->query($q);
        $sampleData = $consulta->result();
        $data = array();

        foreach ($sampleData as $key) {
            array_push($data, array(
                "id_producto" => $key->id_producto,
                "cbarra" => $key->cbarra,
                "nombre_producto" => $key->nombre_producto,
                "nombre_familia" => $key->nombre_familia,
                "nombre_umedida" => $key->nombre_umedida,
                "total" => $key->total,
                "precio_ucompra" => $key->precio_ucompra,
                "por_ganancia"    => $key->por_ganancia,
                "costo"           => $key->costo,
                "por_costo"       => $key->por_costo,
                "monto_comision"  => $key->monto_comision
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

    public function grabarNuevaListaDePrecios($id, $data)
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        if ($id) {
            $this->db->where('id_lprecio', $id);
            $this->db->update('lprecio', $data);
            // 16-11-2026 (QA): si no se actualizó ninguna fila el registro no existe: error (404) y no "éxito"
            if ($this->db->affected_rows() < 1) {
            	set_status_header(404);
            	return array('error' => 'El registro ya no existe (pudo haberlo borrado otra persona). Recarga la página.');
            }
            $consulta = $this->db->query("select " . (int)$id . " as id_lprecio");
        } else {
            $this->db->insert('lprecio', $data);
            $consulta = $this->db->query("SELECT CURRVAL('lprecio_id_lprecio_seq'::regclass) as id_lprecio");
        }
        return $consulta->result();
    }

    public function obtenerListayPreciosDeVenta($idProducto)
    {

        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $query = $this->db->query('SELECT * from detalle_lprecio  d join lprecio l on d.id_lprecio=l.id_lprecio
                                  where d.id_producto=' . (int)$idProducto . ' order by l.nombre_lprecio');
        return $query->result();
    }

    public function grabarPrecios($idDetalleLprecio, $data)
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);

        $tieneComision = $this->verificarColumnaExiste('detalle_lprecio', 'monto_comision');
        // 16-11-2026: neto, IVA e ILA se recalculan con el precio nuevo y los impuestos del producto (antes solo cambiaba
        // el total y el neto quedaba el anterior)
        $total = (int)round((float)str_replace(',', '.', (string)($data['total'] ?? 0)));
        if ($total < 0) return array('error' => 'El precio no puede ser negativo');
        $t = $this->db->query("SELECT COALESCE((SELECT i.valor FROM impuesto i WHERE i.id_impuesto = p.impto_iva), 0) AS iva,
                COALESCE((SELECT i.valor FROM impuesto i WHERE i.id_impuesto = p.impto_ila), 0) AS ila
            FROM detalle_lprecio d JOIN producto p ON p.id_producto = d.id_producto
            WHERE d.id_detalle_lprecio = ?", array((int)$idDetalleLprecio))->row();
        $pIva = $t ? (float)$t->iva : 0;
        $pIla = $t ? (float)$t->ila : 0;
        $neto = (int)round($total / (1 + ($pIva + $pIla) / 100));
        // v1.38.0: el ILA con su tasa y el IVA lo que queda (antes el ILA era "lo que sobra" y podía quedar en -1 por redondeo)
        $ila = (int)round($neto * $pIla / 100);
        $iva = $total - $neto - $ila;
        $dataToSave = array(
            'total' => $total,
            'neto' => $neto,
            'iva' => $iva,
            'ila' => $ila,
        );
        // v1.24.1: la comisión con la regla de Configuraciones (apagada = 0; encendida = la escrita, entre 0 y 100)
        if ($tieneComision) {
            $this->load->model('Mproductos');
            $dataToSave['monto_comision'] = $this->Mproductos->comisionAGrabar(array_key_exists('monto_comision', $data) ? $data['monto_comision'] : 0);
        }

        $idDetalleLprecio = (int)$idDetalleLprecio;
        $this->db->where('id_detalle_lprecio', $idDetalleLprecio);
        $this->db->update('detalle_lprecio', $dataToSave);
        $consulta = $this->db->query("SELECT id_detalle_lprecio FROM detalle_lprecio WHERE id_detalle_lprecio = ?", array($idDetalleLprecio));
        return $consulta->result();
    }

    /**
     * v1.38.0 (pedido del usuario): copia los precios de una lista a otra, de una vez.
     * $ajuste = % a sumar o restar (10 = 10% más caro, -5 = 5% más barato); $idFamilia > 0 = solo esa familia;
     * $soloSinPrecio = solo donde la lista de destino no tiene precio (0 o sin fila). Los productos sin precio en la lista de
     * origen no se tocan. Neto, IVA e ILA se recalculan con los impuestos de cada producto, como al grabar un precio.
     * Devuelve cuántos precios se actualizaron y cuántos se crearon.
     */
    public function copiarPrecios($origen, $destino, $ajuste, $idFamilia, $soloSinPrecio)
    {
        $this->load->helper('tenant'); $db = get_tenant_db($this);
        $origen = (int)$origen; $destino = (int)$destino; $idFamilia = (int)$idFamilia;
        $ajuste = max(-90, min(500, (float)str_replace(',', '.', (string)$ajuste)));
        if ($origen <= 0 || $destino <= 0) return array('error' => 'Elige la lista de origen y la de destino');
        if ($origen === $destino) return array('error' => 'La lista de origen y la de destino deben ser distintas');
        foreach (array($origen, $destino) as $l) {
            if (!$db->query('SELECT 1 FROM lprecio WHERE id_lprecio = ?', array($l))->row()) return array('error' => 'Una de las listas ya no existe. Recarga la página.');
        }
        $factor = 1 + $ajuste / 100;
        // Precios de origen (con precio y producto vigente), ya ajustados, con los impuestos del producto
        $fuente = "SELECT o.id_producto, round(o.total * ?)::int AS total,
                COALESCE((SELECT i.valor FROM impuesto i WHERE i.id_impuesto = p.impto_iva), 0) AS piva,
                COALESCE((SELECT i.valor FROM impuesto i WHERE i.id_impuesto = p.impto_ila), 0) AS pila
            FROM detalle_lprecio o JOIN producto p ON p.id_producto = o.id_producto
            WHERE o.id_lprecio = ? AND COALESCE(o.total, 0) > 0 AND p.vigente IS TRUE" . ($idFamilia > 0 ? ' AND p.id_familia = ' . $idFamilia : '');
        $calc = "round(x.total / (1 + (x.piva + x.pila) / 100.0))";
        $db->trans_begin();
        // El ILA con su tasa (0 si no tiene) y el IVA lo que queda: neto + IVA + ILA = total, sin negativos por redondeo
        $ila = "round($calc * x.pila / 100.0)";
        $db->query("UPDATE detalle_lprecio d SET total = x.total, neto = $calc, ila = $ila, iva = x.total - $calc - $ila
            FROM ($fuente) x
            WHERE d.id_lprecio = ? AND d.id_producto = x.id_producto" . ($soloSinPrecio ? ' AND COALESCE(d.total, 0) = 0' : ''),
            array($factor, $origen, $destino));
        $actualizados = $db->affected_rows();
        $db->query("INSERT INTO detalle_lprecio (id_producto, id_lprecio, total, neto, iva, ila)
            SELECT x.id_producto, ?, x.total, $calc, x.total - $calc - $ila, $ila
            FROM ($fuente) x
            WHERE NOT EXISTS (SELECT 1 FROM detalle_lprecio d WHERE d.id_lprecio = ? AND d.id_producto = x.id_producto)",
            array($destino, $factor, $origen, $destino));
        $nuevos = $db->affected_rows();
        if ($db->trans_status() === false) { $db->trans_rollback(); return array('error' => 'No se pudieron copiar los precios'); }
        $db->trans_commit();
        return array('ok' => true, 'actualizados' => (int)$actualizados, 'nuevos' => (int)$nuevos, 'ajuste' => $ajuste);
    }
}
