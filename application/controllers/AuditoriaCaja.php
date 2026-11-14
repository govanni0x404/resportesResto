<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Consultas > Auditoría de caja (10-11-2026): cajas de un período y, de la elegida, todo lo que pasó (resumen, arqueo,
 * línea de tiempo, anulaciones, devoluciones, descuentos, cortesías, ingresos y retiros, ventas en espera, productos
 * omitidos, ventas vaciadas, gaveta). Solo lectura; el cálculo está en models/Mauditoriacaja.php.
 */
class AuditoriaCaja extends CI_Controller
{
	public function index()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$data = array('nombreUsuario' => $this->session->userdata('nombre_usuario'));
		$this->load->view('menuTop.php', $data);
		$this->load->view('auditoriaCaja/auditoriaCaja.php');
		$this->load->view('menuFooter.php');
		$this->load->view('auditoriaCaja/js.php');
	}

	/** Cajas del período (POST desde, hasta). */
	public function cajas()
	{
		$this->json(function () {
			return $this->Mauditoriacaja->cajas($this->input->post('desde'), $this->input->post('hasta'));
		});
	}

	/** Auditoría completa de una caja (POST id_caja). */
	public function detalle()
	{
		$this->json(function () {
			return $this->Mauditoriacaja->detalle((int)$this->input->post('id_caja'));
		});
	}

	private function json($accion)
	{
		$this->output->set_content_type('application/json', 'utf-8');
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			$this->output->set_status_header(401)->set_output(json_encode(array('error' => 'Sesión expirada, vuelve a ingresar')));
			return;
		}
		try {
			$this->load->model('Mauditoriacaja');
			$this->output->set_output(json_encode($accion(), JSON_UNESCAPED_UNICODE | (defined('JSON_INVALID_UTF8_SUBSTITUTE') ? JSON_INVALID_UTF8_SUBSTITUTE : 0)));
		} catch (Throwable $e) {
			$codigo = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
			if ($codigo === 500) log_message('error', 'AuditoriaCaja: ' . $e->getMessage() . ' en ' . basename($e->getFile()) . ':' . $e->getLine());
			$this->output->set_status_header($codigo)->set_output(json_encode(array('error' => $codigo === 500 ? 'No se pudo leer la auditoría de caja.' : $e->getMessage())));
		}
	}
}
