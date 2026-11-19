<?php
defined('BASEPATH') or exit('No direct script access allowed');

class FormasDePago extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model(array('MformasDePago', 'Mroles'));
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
        $this->load->view('formasDePago/formasDePago.php');
        $this->load->view('menuFooter.php');
        $this->load->view('formasDePago/listadoFormasDePago.php');
        $this->load->view('formasDePago/jsNuevaFormaDePago.php');
        $this->load->view('funciones.php');
    }

    public function ListadoDeFormasDePago()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        echo json_encode($this->MformasDePago->ListadoDeFormasDePago());
    }

    public function grabarNuevaFormaDePago()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $d = $this->input->post('prod');
        if (!is_array($d)) { // sin datos del formulario: no se graba nada
        	echo json_encode(array('error' => 'Datos incompletos'));
        	return;
        }
        $id = intval($d['id_fpago']);
		// 19-11-2026 (auditoría): nombre repetido = aviso claro (antes un error genérico de la base, o se grababa repetido)
		$this->load->helper('tenant');
		$dbDup = get_tenant_db($this);
		$nombreDup = trim((string)$d['nombre_fpago']);
		if ($nombreDup === '') { set_status_header(400); echo json_encode(array('error' => 'Escribe el nombre')); return; }
		if ($dbDup->query('SELECT 1 FROM fpago WHERE upper(trim(nombre_fpago)) = upper(trim(?)) AND id_fpago <> ? LIMIT 1',
			array($nombreDup, (int)$id))->row()) {
			set_status_header(409);
			echo json_encode(array('error' => 'Ya existe una forma de pago llamada "' . $nombreDup . '"'));
			return;
		}
        $miData = array(
            'nombre_fpago' => $d['nombre_fpago'],
            'escortesia' => $d['escortesia'],
            'boleta_electronica' => $d['boleta_electronica'],
            'vigente' => $d['vigente'],
            'comision' => 0,
            'permite_propina' => $d['permite_propina'],
        );
        $r = $this->MformasDePago->grabarNuevaFormaDePago($id, $miData);
        echo json_encode($r);
    }
}