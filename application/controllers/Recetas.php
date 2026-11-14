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
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
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
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		echo json_encode($this->Mrecetas->listadoRecetas());
	}

	public function llenarCboInsumos()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		echo json_encode($this->Mrecetas->llenarCboInsumos());
	}

	public function llenarCboUnMedida()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		echo json_encode($this->Mrecetas->llenarCboUnMedida());
	}

	public function llenarFamiliaReceta()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		echo json_encode($this->Mrecetas->llenarFamiliaReceta());
	}

	public function llenarSeccionReceta()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		echo json_encode($this->Mrecetas->llenarSeccionReceta());
	}

	public function getInsumosReceta()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		$id = $this->input->post('id_receta');
		echo json_encode($this->Mrecetas->getInsumosReceta($id));
	}

	public function grabarReceta()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
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

			// 14-11-2026: rendimiento > 0. Con 0 el trigger f_addptocomprado (del POS) divide por cero al grabar
			// CUALQUIER compra (hace update receta set costo_porcion=costo_insumos/rendimiento sin WHERE).
			$rend = $this->numero(isset($d['rendimiento']) ? $d['rendimiento'] : '');
			if ($rend === null || $rend <= 0) {
				throw new Exception('El rendimiento de la receta debe ser mayor que 0');
			}
			$miData['rendimiento'] = $rend;

			// Insumos: id_insumo entero > 0 y cantidad numérica > 0; lo demás se rechaza antes de tocar la base
			$lineas = array();
			$detalles = $this->input->post('detalleReceta');
			foreach (is_array($detalles) ? $detalles : array() as $value) {
				$idInsumo = isset($value['id_insumo']) ? (int)$value['id_insumo'] : 0;
				if ($idInsumo <= 0) continue;
				$cantidad = $this->numero(isset($value['cantidad']) ? $value['cantidad'] : '');
				if ($cantidad === null || $cantidad <= 0) {
					throw new Exception('Hay un insumo sin cantidad (o con cantidad 0) en la receta');
				}
				$lineas[] = array(
					'id_insumo' => $idInsumo,
					'cantidad' => $cantidad,
					'costo_pp' => (float)$this->numero(isset($value['costo_pp']) ? $value['costo_pp'] : 0),
					'costo_total' => (float)$this->numero(isset($value['costo_total']) ? $value['costo_total'] : 0),
					'es_receta' => isset($value['es_receta']) && strtoupper(trim((string)$value['es_receta'])) === 'SI',
				);
			}

			if (is_array($detalles) && !$lineas) throw new Exception('La receta no tiene insumos válidos');

			// Cabecera e insumos en una sola transacción: si algo falla no se pierden los insumos que ya tenía
			$id_receta_real = $this->Mrecetas->grabarRecetaCompleta($id, $miData, $lineas, is_array($detalles));
			$detalles_guardados = count($lineas);

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
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
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
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
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
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
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

	/** Número escrito en la pantalla ("1.5", "1,5" o "1.234,5") o null si no es un número. */
	private function numero($v)
	{
		$v = trim((string)$v);
		if ($v === '') return null;
		if (is_numeric($v)) return (float)$v;
		if (preg_match('/^-?[\d.]+,\d+$/', $v)) $v = str_replace(array('.', ','), array('', '.'), $v);   // 1.234,5
		else $v = str_replace(',', '', $v);                                                          // 1,234.5
		return is_numeric($v) ? (float)$v : null;
	}
}
