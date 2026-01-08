<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MlistaDePrecios extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function ListadoListasDePrecios()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        $q = "SELECT id_lprecio, nombre_lprecio from lprecio order by nombre_lprecio asc";
        $query = $this->db->query($q)->result();
        return $query;
    }

    public function ListadoDeFamilias()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        $q = "SELECT id_familia, nombre_familia from familia order by nombre_familia asc";
        $query = $this->db->query($q)->result();
        return $query;
    }

    public function InformeListaDePrecios($id_lprecio, $id_familia)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        // $q = "SELECT p.id_producto, p.cbarra, p.nombre_producto, p.id_familia, f.nombre_familia, u.nombre_umedida, dl.total, p.precio_ucompra, dl.id_lprecio from Producto p
        // join familia f on f.id_familia=p.id_familia
        // join umedida u on p.id_umedida=u.id_umedida
        // join detalle_lprecio dl on dl.id_producto=p.id_producto
        // where p.vigente=true and p.venta_publico=true";
        $q = "SELECT p.id_producto, p.cbarra, p.nombre_producto, p.id_familia, f.nombre_familia, u.nombre_umedida, dl.total, dl.por_ganancia, dl.costo, dl.por_costo, dl.monto_comision,
        p.precio_ucompra, dl.id_lprecio
        FROM Producto p
        JOIN familia f ON f.id_familia = p.id_familia
        JOIN umedida u ON p.id_umedida = u.id_umedida
        JOIN detalle_lprecio dl ON dl.id_producto = p.id_producto
        WHERE p.vigente = true 
        AND p.venta_publico = true";

        if ($id_familia > 0) {
            $q = $q . " and f.id_familia=$id_familia";
        }
        if ($id_lprecio > 0) {
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
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        if ($id) {
            $this->db->where('id_lprecio', $id);
            $this->db->update('lprecio', $data);
            $consulta = $this->db->query("select " . $id . " as id_lprecio");
        } else {
            $this->db->insert('lprecio', $data);
            $consulta = $this->db->query("SELECT CURRVAL('lprecio_id_lprecio_seq'::regclass) as id_lprecio");
        }
        return $consulta->result();
    }

    public function obtenerListayPreciosDeVenta($idProducto)
    {

        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        $query = $this->db->query('SELECT * from detalle_lprecio  d join lprecio l on d.id_lprecio=l.id_lprecio
                                  where d.id_producto=' . $idProducto . ' order by l.nombre_lprecio');
        return $query->result();
    }

    public function grabarPrecios($idDetalleLprecio, $data)
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        $this->db->where('id_detalle_lprecio', $idDetalleLprecio);
        $this->db->update('detalle_lprecio', $data);
        $consulta = $this->db->query("select $idDetalleLprecio as id_detalle_lprecio from detalle_lprecio where id_detalle_lprecio=$idDetalleLprecio");
        return $consulta->result();
    }
}
