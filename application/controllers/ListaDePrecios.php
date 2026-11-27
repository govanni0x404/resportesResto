<?php
defined('BASEPATH') or exit('No direct script access allowed');

class ListaDePrecios extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model(array('MlistaDePrecios', 'Mroles'));
    }

    public function index()
    {

        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $data = array(
            'nombreUsuario' => $this->session->userdata('nombre_usuario'),
        );
        $this->load->view('menuTop.php', $data);
        $this->load->view('listaDePrecios/listaDePrecios.php');
        $this->load->view('menuFooter.php');
        // v1.24.1: Configuraciones > Comisión en el precio de los productos
        $this->load->model('Mproductos');
        $this->load->view('listaDePrecios/js.php', array('comision_habilitada' => $this->Mproductos->comisionHabilitada()));
    }


    public function ListadoListasDePrecios()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        echo json_encode($this->MlistaDePrecios->ListadoListasDePrecios());
    }

    public function ListadoDeFamilias()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        echo json_encode($this->MlistaDePrecios->ListadoDeFamilias());
    }

    public function InformeListaDePrecios()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $id_lprecio = $this->input->post('id_lprecio');
        $id_familia = $this->input->post('id_familia');
        echo json_encode($this->MlistaDePrecios->InformeListaDePrecios($id_lprecio, $id_familia));
    }

    public function grabarNuevaListaDePrecios()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $d = $this->input->post('prod');
        $id = intval($d['id_lprecio']);
        $miData = array(
            'nombre_lprecio' => $d['nombre_lprecio'],
        );
        $r = $this->MlistaDePrecios->grabarNuevaListaDePrecios($id, $miData);
        echo json_encode($r);
    }

    public function obtenerListayPreciosDeVenta()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $idProducto = $this->input->post('id_producto');
        echo json_encode($this->MlistaDePrecios->obtenerListayPreciosDeVenta($idProducto));
    }

    public function grabarPrecios()
    { // grabar precios de las distitntas listas
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $d = $this->input->post('detalle');
        $respuesta = array();
        foreach (is_array($d) ? $d : array() as $value) {
            if (!is_array($value)) continue;
            //$idProducto=$value['id_producto'];
            $idDetalleLprecio = (int)($value['id_detalle_lprecio'] ?? 0);
            $miData = array(
                'total' => $value['total'] ?? 0,
                'monto_comision' => $value['monto_comision'] ?? 0   // v1.24.1: con la opción apagada se graba 0
            );
            $r = $this->MlistaDePrecios->grabarPrecios($idDetalleLprecio, $miData);
            $respuesta[] = $r;
        }
        echo json_encode($respuesta);
    }

    /** v1.38.0: copiar los precios de una lista a otra (botón "Copiar precios"). */
    public function copiarPrecios()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) { set_status_header(401); echo json_encode(array('error' => 'Sesión expirada')); return; }
        $r = $this->MlistaDePrecios->copiarPrecios($this->input->post('origen'), $this->input->post('destino'), $this->input->post('ajuste'),
            $this->input->post('id_familia'), (string)$this->input->post('solo_sin_precio') === '1');
        if (isset($r['error'])) set_status_header(400);
        echo json_encode($r);
    }
}
