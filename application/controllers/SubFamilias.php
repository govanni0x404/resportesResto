<?php
defined('BASEPATH') or exit('No direct script access allowed');

class SubFamilias extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model(array('MsubFamilias', 'Mroles'));
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
		$this->load->view('subFamilias/subFamilias.php');
		$this->load->view('menuFooter.php');
		$this->load->view('subFamilias/listadoSubFamilia.php');
		$this->load->view('subFamilias/jsNuevaSubFamilia.php');
		$this->load->view('funciones.php');
	}

	public function ListadoDeSubFamilias()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		echo json_encode($this->MsubFamilias->ListadoDeSubFamilias());
	}

	public function grabarNuevaSubFamilia()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$d = $this->input->post('prod');
		if (!is_array($d)) { echo json_encode(array('error' => 'Datos incompletos')); return; }
		$id = intval($d['id_subfamilia']);
		// 19-11-2026 (auditoría): nombre repetido = aviso claro (antes un error genérico de la base, o se grababa repetido)
		$this->load->helper('tenant');
		$dbDup = get_tenant_db($this);
		$nombreDup = trim((string)$d['nombre_subfamilia']);
		if ($nombreDup === '') { set_status_header(400); echo json_encode(array('error' => 'Escribe el nombre')); return; }
		if ($dbDup->query('SELECT 1 FROM subfamilia WHERE upper(trim(nombre_subfamilia)) = upper(trim(?)) AND id_subfamilia <> ? AND id_familia = ? LIMIT 1',
			array($nombreDup, (int)$id, (int)$d['id_familia']))->row()) {
			set_status_header(409);
			echo json_encode(array('error' => 'Ya existe una subfamilia en esa familia llamada "' . $nombreDup . '"'));
			return;
		}

		$miData = array(
			'nombre_subfamilia' => $d['nombre_subfamilia'],
			'id_familia' => $d['id_familia']
		);
		$r = $this->MsubFamilias->grabarNuevaSubFamilia($id, $miData);
		echo json_encode($r);
	}

	public function ListadoSubFamiliasXFamilias()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $id_familia = $this->input->post('id_familia');
        echo json_encode($this->MsubFamilias->ListadoSubFamiliasXFamilias($id_familia));
    }
}