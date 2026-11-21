<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * v1.28.0: reservas de mesas (ver models/Mreservas.php).
 * Acceso: módulo "Reservas" en Privilegios de Usuario (gerencia siempre).
 */
class Reservas extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Mreservas');
	}

	public function index()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) redirect(base_url());
		$disp = $this->Mreservas->disponible() && $this->Mreservas->activa();
		$this->load->view('menuTop.php', array('nombreUsuario' => $this->session->userdata('nombre_usuario')));
		$this->load->view('reservas/reservas.php', array('disponible' => $disp, 'mesas' => $disp ? $this->Mreservas->mesas() : array()));
		$this->load->view('menuFooter.php');
	}

	/** Reservas del día (?fecha=AAAA-MM-DD) y cuántas hay en cada día del mes. */
	public function listado()
	{
		$this->json(function () {
			$f = (string)$this->input->get('fecha');
			return array('reservas' => $this->Mreservas->delDia($f), 'mes' => $this->Mreservas->porDiaDelMes($f));
		});
	}

	public function guardar()
	{
		$this->json(function () {
			$d = $this->input->post('reserva');
			return array('ok' => true, 'reserva' => $this->Mreservas->guardar((int)$this->input->post('id'), is_array($d) ? $d : array(),
				(int)$this->session->userdata('id_usuario')));
		}, true);
	}

	public function estado()
	{
		$this->json(function () {
			return array('ok' => true, 'reserva' => $this->Mreservas->cambiarEstado((int)$this->input->post('id'), (string)$this->input->post('estado')));
		}, true);
	}

	private function json($accion, $soloPost = false)
	{
		$this->output->set_content_type('application/json', 'utf-8');
		if ($soloPost && $this->input->method() !== 'post') {
			return $this->output->set_status_header(405)->set_output(json_encode(array('error' => 'Método no permitido')));
		}
		try {
			if (!$this->Mreservas->disponible()) throw new Exception('Falta actualizar la base (migración de reservas). Recarga la página en un momento.', 409);
			if (!$this->Mreservas->activa()) throw new Exception('Las reservas están desactivadas. Actívalas en Configuraciones > Cocina y reservas.', 409);
			$this->output->set_output(json_encode($accion()));
		} catch (Exception $e) {
			$c = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
			$this->output->set_status_header($c)->set_output(json_encode(array('error' => $e->getMessage())));
		} catch (Throwable $e) {
			log_message('error', 'Reservas: ' . $e->getMessage() . ' en ' . basename($e->getFile()) . ':' . $e->getLine());
			$this->output->set_status_header(500)->set_output(json_encode(array('error' => 'Error interno del servidor')));
		}
	}
}
