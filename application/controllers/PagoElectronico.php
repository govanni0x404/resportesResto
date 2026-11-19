<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Mantención > Pago electrónico (19-11-2026): las máquinas POS y los datos que la Caja manda en la petición al programa
 * ApiPagoElectronico del equipo de la caja. Ver Mpagoelectronico. Módulo sensible (tiene la clave del programa): solo
 * gerencia o un rol autorizado en Privilegios.
 */
class PagoElectronico extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model('Mpagoelectronico');
	}

	public function index()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		$data = array('nombreUsuario' => $this->session->userdata('nombre_usuario'));
		$this->load->view('menuTop.php', $data);
		$this->load->view('pagoElectronico/pagoElectronico.php');
		$this->load->view('menuFooter.php');
		$this->load->view('pagoElectronico/cliente.php');
		$this->load->view('pagoElectronico/js.php');
	}

	public function datos()
	{
		$this->ejecutar(function () {
			return $this->Mpagoelectronico->listar();
		});
	}

	public function guardar()
	{
		$this->ejecutar(function () {
			$r = $this->Mpagoelectronico->guardar($this->input->post());
			return $r + $this->Mpagoelectronico->listar();
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
			log_message('error', 'PagoElectronico: ' . $e->getMessage() . ' en ' . basename($e->getFile()) . ':' . $e->getLine());
			$this->output->set_status_header(500)->set_output(json_encode(array('error' => 'Error interno del servidor. Revisa el registro (log).')));
		}
	}
}
