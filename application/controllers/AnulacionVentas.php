<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Consultas > Anulación de ventas (10-11-2026): ventas en espera anuladas (con la justificación que pidió la Caja) y
 * documentos anulados, en un período. Solo lectura; el cálculo está en models/Mauditoriacaja.php.
 * Los productos anulados en mesas siguen en Consultas > Resumen De Anulaciones/Eliminaciones.
 */
class AnulacionVentas extends CI_Controller
{
	public function index()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$data = array('nombreUsuario' => $this->session->userdata('nombre_usuario'));
		$this->load->view('menuTop.php', $data);
		$this->load->view('anulacionVentas/anulacionVentas.php');
		$this->load->view('menuFooter.php');
		$this->load->view('anulacionVentas/js.php');
	}

	/** POST desde, hasta, tipo (todas | espera | documento). */
	public function listado()
	{
		$this->output->set_content_type('application/json', 'utf-8');
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			$this->output->set_status_header(401)->set_output(json_encode(array('error' => 'Sesión expirada, vuelve a ingresar')));
			return;
		}
		try {
			$tipo = (string)$this->input->post('tipo');
			if (!in_array($tipo, array('todas', 'espera', 'documento'), true)) $tipo = 'todas';
			$this->load->model('Mauditoriacaja');
			$this->output->set_output(json_encode($this->Mauditoriacaja->anulaciones($this->input->post('desde'), $this->input->post('hasta'), $tipo),
				JSON_UNESCAPED_UNICODE | (defined('JSON_INVALID_UTF8_SUBSTITUTE') ? JSON_INVALID_UTF8_SUBSTITUTE : 0)));
		} catch (Throwable $e) {
			log_message('error', 'AnulacionVentas: ' . $e->getMessage() . ' en ' . basename($e->getFile()) . ':' . $e->getLine());
			$this->output->set_status_header(500)->set_output(json_encode(array('error' => 'No se pudo leer las anulaciones.')));
		}
	}
}
