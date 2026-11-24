<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * v1.35.0 (pedido del usuario en cambios.md): Tablas > Categorías de agregados. Crear las categorías (Proteína, Verdura...)
 * y poner varios agregados de una vez en una categoría. Ver McategoriasAgregados. El acceso lo controla el hook Permisos
 * (módulo "CategoriasAgregados" en Privilegios).
 */
class CategoriasAgregados extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model('McategoriasAgregados');
	}

	public function index()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		$data = array('nombreUsuario' => $this->session->userdata('nombre_usuario'));
		$this->load->view('menuTop.php', $data);
		$this->load->view('categoriasAgregados/categoriasAgregados.php');
		$this->load->view('menuFooter.php');
		$this->load->view('categoriasAgregados/js.php');
	}

	public function datos()
	{
		$this->ejecutar(function () {
			return $this->McategoriasAgregados->listar();
		});
	}

	public function guardar()
	{
		$this->ejecutar(function () {
			$id = $this->McategoriasAgregados->guardar($this->input->post());
			return array('id' => $id) + $this->McategoriasAgregados->listar();
		}, true);
	}

	public function eliminar()
	{
		$this->ejecutar(function () {
			$sueltos = $this->McategoriasAgregados->eliminar((int)$this->input->post('id'));
			return array('sin_categoria' => $sueltos) + $this->McategoriasAgregados->listar();
		}, true);
	}

	public function agregados()
	{
		$this->ejecutar(function () {
			return array('agregados' => $this->McategoriasAgregados->agregados());
		});
	}

	public function asignar()
	{
		$this->ejecutar(function () {
			$r = $this->McategoriasAgregados->asignar((int)$this->input->post('id_categoria'),
				$this->input->post('poner'), $this->input->post('quitar'));
			return $r + $this->McategoriasAgregados->listar();
		}, true);
	}

	/** Responde JSON; los errores salen como {error} con su código HTTP. */
	private function ejecutar($accion, $soloPost = false)
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
			log_message('error', 'CategoriasAgregados: ' . $e->getMessage() . ' en ' . basename($e->getFile()) . ':' . $e->getLine());
			$this->output->set_status_header(500)->set_output(json_encode(array('error' => 'Error interno del servidor. Revisa el registro (log).')));
		}
	}
}
