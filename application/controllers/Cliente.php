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
        $id = intval($d['id_cliente'] ?? 0);
        $miData = array(
            'nombre_cliente' => $d['nombre_cliente'] ?? '',
            'id_comuna' => $d['id_comuna'] ?? '',
            'rut' => $d['rut'] ?? '',
            'dv' => $d['dv'] ?? '',
            'direccion' => $d['direccion'] ?? '',
            'telefono' => $d['telefono'] ?? '',
            'mail' => $d['mail'] ?? '',
            'giro' => $d['giro'] ?? '',
            'id_lprecio' => $d['id_lprecio'] ?? '',
            'creditomaximo' => $d['creditomaximo'] ?? '',
            'id_sector' => 0,
            'referencia_direccion' => $d['referencia_direccion'] ?? '',
        );
        // Plazo máximo del crédito en días (0 = sin plazo); solo si la base ya tiene la columna (sql/credito_clientes.sql)
        $this->load->helper('tenant');
        if (isset($d['plazo_credito']) && get_tenant_db($this)->field_exists('plazo_credito', 'cliente')) {
            $miData['plazo_credito'] = in_array((int)$d['plazo_credito'], array(0, 15, 30, 45, 60, 90), true) ? (int)$d['plazo_credito'] : 30;
        }
        // 14-11-2026 (QA): RUT con dígito verificador correcto y sin repetir; correo válido si viene.
        $this->load->helper(array('tenant', 'rut'));
        $db = get_tenant_db($this);
        $rut = rut_validar_unico($db, 'cliente', 'id_cliente', $id, $d['rut'] ?? '', $d['dv'] ?? '', 'cliente');
        if (isset($rut['error'])) { echo json_encode($rut); return; }
        list($miData['rut'], $miData['dv']) = $rut;
        $miData['nombre_cliente'] = trim((string)($d['nombre_cliente'] ?? ''));
        if ($miData['nombre_cliente'] === '') { echo json_encode(array('error' => 'El nombre del cliente es obligatorio')); return; }
        $miData['mail'] = trim((string)($d['mail'] ?? ''));
        if ($miData['mail'] !== '' && !filter_var($miData['mail'], FILTER_VALIDATE_EMAIL)) {
            echo json_encode(array('error' => 'El correo del cliente no es válido')); return;
        }
        $miData['creditomaximo'] = (int)($d['creditomaximo'] ?? 0);
        if ($miData['creditomaximo'] < 0) { echo json_encode(array('error' => 'El crédito máximo no puede ser negativo')); return; }
        $miData['id_lprecio'] = (int)($d['id_lprecio'] ?? 0);
        if (!$db->query('SELECT 1 FROM lprecio WHERE id_lprecio = ?', array($miData['id_lprecio']))->row()) {
            echo json_encode(array('error' => 'La lista de precios elegida no existe')); return;
        }
        $miData['id_comuna'] = (int)($d['id_comuna'] ?? 0);
        if ($miData['id_comuna'] > 0 && !$db->query('SELECT 1 FROM comuna WHERE id_comuna = ?', array($miData['id_comuna']))->row()) {
            echo json_encode(array('error' => 'La comuna elegida no existe')); return;
        }
        if ($miData['id_comuna'] <= 0) $miData['id_comuna'] = null;   // FK a comuna: 0 no existe
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

    // ---------- Datos para factura (12-11-2026): direcciones, giros y solicitantes del cliente ----------

    /** Listas del cliente (consulta: la usa la ficha del cliente). */
    public function listarDatosFactura()
    {
        $id = (int)$this->input->get_post('id_cliente');
        $this->responderJson(function () use ($id) {
            $this->load->model('Mclientefactura');
            return $this->Mclientefactura->listas($id);
        });
    }

    /** POST tipo (direccion | giro | solicitante), id_cliente, id (0 = nuevo), datos{} */
    public function guardarDatoFactura()
    {
        $this->responderJson(function () {
            $this->load->model('Mclientefactura');
            $idCliente = (int)$this->input->post('id_cliente');
            $id = (int)$this->input->post('id');
            $d = $this->input->post('datos');
            if (!is_array($d)) $d = array();
            switch ((string)$this->input->post('tipo')) {
                case 'direccion': return $this->Mclientefactura->guardarDireccion($idCliente, $id, $d);
                case 'giro': return $this->Mclientefactura->guardarGiro($idCliente, $id, $d);
                case 'solicitante': return $this->Mclientefactura->guardarSolicitante($idCliente, $id, $d);
            }
            throw new Exception('Dato no reconocido', 400);
        }, true);
    }

    /** POST tipo, id_cliente, id */
    public function quitarDatoFactura()
    {
        $this->responderJson(function () {
            $this->load->model('Mclientefactura');
            $idCliente = (int)$this->input->post('id_cliente');
            $id = (int)$this->input->post('id');
            switch ((string)$this->input->post('tipo')) {
                case 'direccion': return $this->Mclientefactura->quitarDireccion($idCliente, $id);
                case 'giro': return $this->Mclientefactura->quitarGiro($idCliente, $id);
                case 'solicitante': return $this->Mclientefactura->quitarSolicitante($idCliente, $id);
            }
            throw new Exception('Dato no reconocido', 400);
        }, true);
    }

    private function responderJson($accion, $soloPost = false)
    {
        $this->output->set_content_type('application/json', 'utf-8');
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            return $this->output->set_status_header(401)->set_output(json_encode(array('error' => 'Sesión expirada, vuelve a ingresar')));
        }
        if ($soloPost && $this->input->method() !== 'post') {
            return $this->output->set_status_header(405)->set_output(json_encode(array('error' => 'Método no permitido')));
        }
        try {
            $this->output->set_output(json_encode($accion()));
        } catch (Exception $e) {
            $code = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            $this->output->set_status_header($code)->set_output(json_encode(array('error' => $e->getMessage())));
        } catch (Throwable $e) {
            log_message('error', 'Cliente datos factura: ' . $e->getMessage() . ' en ' . basename($e->getFile()) . ':' . $e->getLine());
            $this->output->set_status_header(500)->set_output(json_encode(array('error' => 'Error interno del servidor. Revisa el registro (log).')));
        }
    }
}
