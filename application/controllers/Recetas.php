<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Recetas extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model(array('Mrecetas', 'Mroles'));
	}

	public function index()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}

		$data = array(
			'nombreUsuario' => $this->session->userdata('nombre_usuario'),
		);

		$this->load->view('menuTop.php', $data);
		$this->load->view('recetas/recetas.php');
		$this->load->view('menuFooter.php');
		$this->load->view('recetas/modalRecetas.php');
		$this->load->view('recetas/js.php');
		$this->load->view('recetas/jsNewFamiliaReceta.php');
		$this->load->view('recetas/jsNewSeccion.php');
	}

	public function listadoRecetas()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		echo json_encode($this->Mrecetas->listadoRecetas());
	}

	public function llenarCboInsumos()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		echo json_encode($this->Mrecetas->llenarCboInsumos());
	}

	public function llenarCboUnMedida()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		echo json_encode($this->Mrecetas->llenarCboUnMedida());
	}

	public function llenarFamiliaReceta()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		echo json_encode($this->Mrecetas->llenarFamiliaReceta());
	}

	public function llenarSeccionReceta()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		echo json_encode($this->Mrecetas->llenarSeccionReceta());
	}

	public function getInsumosReceta()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		$id = $this->input->post('id_receta');
		echo json_encode($this->Mrecetas->getInsumosReceta($id));
	}

	public function grabarReceta()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(array('status' => 'error', 'message' => 'Sesión no válida'));
			return;
		}

		try {
			$d = $this->input->post('prod');
			$id = intval($d['id_receta']);
			
			$miData = array(
				'nombre_receta' => $d['nombre_receta'],
				'rendimiento' => $d['rendimiento'],
				'precio_venta' => $d['precio_venta'],
				'id_umedida' => $d['id_umedida'],
				'id_familia_receta' => $d['id_familia_receta'],
				'id_seccion' => $d['id_seccion'],
				'preparacion' => $d['preparacion'],
			);

			// Guardamos la receta y obtenemos el ID
			$id_receta_real = $this->Mrecetas->grabarReceta($id, $miData);

			// Grabar detalles si existen
			$detalles = $this->input->post('detalleReceta');
			$detalles_guardados = 0;
			
			if (is_array($detalles) && count($detalles) > 0) {
				// Si es una edición, marcar todos los detalles anteriores como no vigentes
				if ($id > 0) {
					$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
					$this->db->where('id_receta', $id_receta_real);
					$this->db->update('detalle_receta', array('vigente' => false));
				}
				
				// Ahora guardar los detalles nuevos/actualizados
				foreach ($detalles as $value) {
					$miDataDetalle = array(
						'id_receta' => $id_receta_real,
						'id_insumo' => $value['id_insumo'],
						'cantidad' => $value['cantidad'],
						'costo_pp' => $value['costo_pp'],
						'costo_total' => $value['costo_total'],
						'es_receta' => $value['es_receta'] == 'SI' ? 'true' : 'false',
						'vigente' => true,
						'um_receta' => 0,
					);
					
					// Siempre insertar como nuevo (ya marcamos los anteriores como no vigentes)
					$this->Mrecetas->grabarDetalleReceta(0, $miDataDetalle);
					$detalles_guardados++;
				}
			}

			echo json_encode(array(
				'status' => 'success',
				'message' => 'Receta guardada exitosamente',
				'id_receta' => $id_receta_real,
				'detalles_guardados' => $detalles_guardados
			));
			
		} catch (Exception $e) {
			echo json_encode(array(
				'status' => 'error',
				'message' => 'Error al guardar: ' . $e->getMessage()
			));
		}
	}

	public function updateInsumoDelete()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		$idReceta = $this->input->post('id_receta');
		$idDetalle = $this->input->post('detalleReceta');
		$idInsumo = $this->input->post('id_insumo');

		$r = $this->Mrecetas->updateInsumoDelete($idReceta, $idDetalle, $idInsumo);
		echo json_encode($r);
	}

	public function grabarNuevaFamiliaReceta()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(array('status' => 'error', 'message' => 'Sesión no válida'));
			return;
		}
		
		try {
			$d = $this->input->post('prod');
			$id = intval($d['id_familia_receta']);
			$miData = array(
				'nombre_familia_receta' => $d['nombre_familia_receta'],
			);
			$r = $this->Mrecetas->grabarNuevaFamiliaReceta($id, $miData);
			echo json_encode(array('status' => 'success', 'data' => $r));
		} catch (Exception $e) {
			echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
		}
	}

	public function grabarNuevaSeccion()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(array('status' => 'error', 'message' => 'Sesión no válida'));
			return;
		}
		
		try {
			$d = $this->input->post('prod');
			$id = intval($d['id_seccion']);
			$miData = array(
				'nombre_seccion' => $d['nombre_seccion'],
			);
			$r = $this->Mrecetas->grabarNuevaSeccion($id, $miData);
			echo json_encode(array('status' => 'success', 'data' => $r));
		} catch (Exception $e) {
			echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
		}
	}
}