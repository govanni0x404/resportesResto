<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Cliente extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model(array('Mclientes', 'Mroles'));
    }

    public function index()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $data = array(
            'nombreUsuario' => $this->session->userdata('nombre_usuario'),
            //'modulosDisponibles' => json_decode($this->Mroles->modulosDisponibles($this->session->userdata('id_rol')), true),
            //'verificarSiEsAdmin' => json_decode($this->Mroles->verificarSiEsAdmin($this->session->userdata('id_rol')), true)
        );
        $this->load->view('menuTop.php', $data);
        $this->load->view('clientes/clientes.php');
        $this->load->view('menuFooter.php');
        $this->load->view('clientes/listadoClientes.php');
        $this->load->view('clientes/jsNuevoCliente.php');
        $this->load->view('funciones.php');
    }

    public function ListadoDeClientes()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        echo json_encode($this->Mclientes->ListadoDeClientes());
    }

    public function grabarNuevoCliente()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $d = $this->input->post('prod');
        if (!is_array($d)) { // sin datos del formulario: no se graba nada
        	echo json_encode(array('error' => 'Datos incompletos'));
        	return;
        }
        $id = intval($d['id_cliente']);
        $miData = array(
            'nombre_cliente' => $d['nombre_cliente'],
            'id_comuna' => $d['id_comuna'],
            'rut' => $d['rut'],
            'dv' => $d['dv'],
            'direccion' => $d['direccion'],
            'telefono' => $d['telefono'],
            'mail' => $d['mail'],
            'giro' => $d['giro'],
            'id_lprecio' => $d['id_lprecio'],
            'creditomaximo' => $d['creditomaximo'],
            'id_sector' => 0,
            'referencia_direccion' => $d['referencia_direccion'],
        );
        // Plazo máximo del crédito en días (0 = sin plazo); solo si la base ya tiene la columna (sql/credito_clientes.sql)
        $this->load->helper('tenant');
        if (isset($d['plazo_credito']) && get_tenant_db($this)->field_exists('plazo_credito', 'cliente')) {
            $miData['plazo_credito'] = in_array((int)$d['plazo_credito'], array(0, 15, 30, 45, 60, 90), true) ? (int)$d['plazo_credito'] : 30;
        }
        $r = $this->Mclientes->grabarNuevoCliente($id, $miData);
        echo json_encode($r);
    }

    public function busquedaClientePorRut()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $rut = $this->input->post('rut');
        echo json_encode($this->Mclientes->busquedaClientePorRut($rut));
    }
}
