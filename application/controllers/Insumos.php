<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Insumos extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model(array('Minsumos', 'Mroles'));
	}

	public function index()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}

		$data = array(
			'nombreUsuario' => $this->session->userdata('nombre_usuario'),
			//'modulosDisponibles' => json_decode($this->Mroles->modulosDisponibles($this->session->userdata('id_rol')), true)
		);
		$this->load->view('menuTop.php', $data);
				$this->load->view('insumos/insumos.php');
				$this->load->view('menuFooter.php');
				$this->load->view('insumos/modalInsumos.php');
				$this->load->view('insumos/js.php');
				$this->load->view('produccion/modalProduccion.php');
/*
		if ($this->session->userdata('nombre_usuario')) {
			if ($this->session->userdata('id_rol') == 1) {
				$this->load->view('menuTop.php', $data);
				$this->load->view('insumos/insumos.php');
				$this->load->view('menuFooter.php');
				$this->load->view('insumos/modalInsumos.php');
				$this->load->view('insumos/js.php');
			} else {
				redirect(base_url());
			}
		}*/
	}

	public function listadoInsumos()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		echo json_encode($this->Minsumos->listadoInsumos());
	}

	public function llenarCboGrupoInsumo()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		echo json_encode($this->Minsumos->llenarCboGrupoInsumo());
	}

	public function llenarCboImpto()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		echo json_encode($this->Minsumos->llenarCboImpto());
	}

	public function llenarCboUnMedida()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		echo json_encode($this->Minsumos->llenarCboUnMedida());
	}

	public function llenarCboRecetaProduccion()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id = $this->input->post('id_insumo');
		echo json_encode($this->Minsumos->llenarCboRecetaProduccion($id));
	}

	public function getSiguienteInsumo()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		echo json_encode($this->Minsumos->getSiguienteInsumo());
	}

	public function grabarInsumo()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$d = $this->input->post('prod');
		$id = intval($d['id_insumo']);
		$miData = array(
			'cbarra' => $d['cbarra'],
			'nombre_insumo' => $d['nombre_insumo'],
			'id_grupo_insumo' => $d['id_grupo_insumo'],
			'id_impto_iva' => $d['id_impto_iva'],
			'id_impto_adicional' => $d['id_impto_adicional'],
			'umedida_inventario' => $d['umedida_inventario'],
			'id_umedida_inventario' => $d['id_umedida_inventario'],
			'umedida_receta' => $d['umedida_receta'],
			'id_umedida_receta' => $d['id_umedida_receta'],
			'costo_neto_ppp' => $d['costo_neto_ppp'],
			'costo_bruto_ppp' => $d['costo_bruto_ppp'],
			'precio_ucompra' => $d['precio_ucompra'],
			'observaciones' => $d['observaciones'],
			'inventario_cierre_turno' => $d['inv_cierre']
		);
		$r = $this->Minsumos->grabarInsumo($id, $miData);
		// Una sola respuesta JSON: antes se imprimían dos objetos seguidos (JSON inválido). El modelo devuelve
		// un arreglo de filas ([{id_insumo}]), por eso el id se lee de la primera fila.
		$idGrabado = (is_array($r) && isset($r[0]->id_insumo)) ? $r[0]->id_insumo : null;
		if ($idGrabado !== null) {
			echo json_encode(array(
				'success' => true,
				'status' => 'success',
				'id_insumo' => $idGrabado,
				'message' => 'Insumo grabado exitosamente',
				'msg' => 'Insumo grabado correctamente'
			));
		} else {
			echo json_encode(array('success' => false, 'status' => 'error', 'msg' => 'Error al grabar insumo'));
		}
	}

	public function buscarInsumo()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}

		$cbarra = $this->input->post('cbarra');
		echo json_encode($this->Minsumos->buscarInsumo($cbarra));
	}

	public function listadoStockInsumo()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id = $this->input->post('id_insumo');
		echo json_encode($this->Minsumos->listadoStockInsumo($id));
	}

	public function listadoHistoricoCompras()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id = $this->input->post('id_insumo');
		echo json_encode($this->Minsumos->listadoHistoricoCompras($id));
	}

	public function listadoInsumosReceta()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id = $this->input->post('id_insumo');
		echo json_encode($this->Minsumos->listadoInsumosReceta($id));
	}

	public function listadoInsumoProduccion()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id = $this->input->post('id_insumo');
		echo json_encode($this->Minsumos->listadoInsumoProduccion($id));
	}

	public function listadoRecetas()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id = $this->input->post('id_insumo');
		echo json_encode($this->Minsumos->listadoRecetas($id));
	}

	public function ListarInsumos()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		echo json_encode($this->Minsumos->ListarInsumos());
	}

	public function ObtenerDatosInsumos()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id = $this->input->post('id_insumo');
		echo json_encode($this->Minsumos->ObtenerDatosInsumos($id));
	}
    public function ObtenerUnidadMedida()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        $id_insumo = $this->input->post('id_insumo');
        $unidad = $this->Minsumos->obtenerUnidadMedidaPorId($id_insumo);
        
        echo json_encode($unidad);
    }
}