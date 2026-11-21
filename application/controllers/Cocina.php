<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * v1.28.0 (PRUEBA, rama prueba/pantalla-cocina): pantalla de cocina (ver models/Mcocina.php).
 * Pensada para una tablet o un monitor en la cocina: muestra las comandas por preparar, la más antigua primero, con el
 * tiempo que llevan esperando, y se marcan "Listo" con un toque. Se actualiza sola cada 5 segundos.
 * Acceso: módulo "Cocina" en Privilegios de Usuario (gerencia siempre).
 * No toca la impresión: las comandas en papel siguen saliendo igual que antes.
 */
class Cocina extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Mcocina');
	}

	public function index()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) redirect(base_url());
		$this->load->view('menuTop.php', array('nombreUsuario' => $this->session->userdata('nombre_usuario')));
		$this->load->view('cocina/cocina.php', array('disponible' => $this->Mcocina->disponible(), 'activa' => $this->Mcocina->activa(),
			'destinos' => $this->Mcocina->destinos(), 'yo' => (int)$this->session->userdata('id_usuario')));
		$this->load->view('menuFooter.php');
	}

	/** Comandas por preparar (JSON). ?destino= id de destino de impresión (0 = todos). */
	public function pendientes()
	{
		$this->json(function () {
			$this->soltarSesion();
			return array('comandas' => $this->Mcocina->pendientes((int)$this->input->get('destino')), 'hora' => date('H:i'));
		});
	}

	/** Marca "Listo" las líneas de una comanda. POST lineas[] = {origen, id}. */
	public function listo()
	{
		$this->json(function () {
			$n = $this->Mcocina->marcarListo($this->input->post('lineas'), (int)$this->session->userdata('id_usuario'));
			return array('ok' => true, 'marcadas' => $n);
		}, true);
	}

	/** v1.29.0: el usuario de la sesión toma la comanda ("Siendo preparado por"). POST lineas[], soltar=1 para dejarla libre. */
	public function tomar()
	{
		$this->json(function () {
			$n = $this->Mcocina->tomar($this->input->post('lineas'), (int)$this->session->userdata('id_usuario'),
				(string)$this->session->userdata('nombre_usuario'), (string)$this->input->post('soltar') === '1');
			return array('ok' => true, 'lineas' => $n);
		}, true);
	}

	/** Deshace el último "Listo" (vuelven a la pantalla). POST lineas[] = {origen, id}. */
	public function deshacer()
	{
		$this->json(function () {
			return array('ok' => true, 'deshechas' => $this->Mcocina->deshacer($this->input->post('lineas')));
		}, true);
	}

	private function soltarSesion()
	{
		if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
	}

	private function json($accion, $soloPost = false)
	{
		$this->output->set_content_type('application/json', 'utf-8');
		if ($soloPost && $this->input->method() !== 'post') {
			return $this->output->set_status_header(405)->set_output(json_encode(array('error' => 'Método no permitido')));
		}
		try {
			if (!$this->Mcocina->disponible()) throw new Exception('Falta actualizar la base (migración de la pantalla de cocina). Recarga la página en un momento.', 409);
			if (!$this->Mcocina->activa()) throw new Exception('La pantalla de cocina está desactivada. Actívala en Configuraciones > Cocina y reservas.', 409);
			$this->output->set_output(json_encode($accion()));
		} catch (Exception $e) {
			$c = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
			$this->output->set_status_header($c)->set_output(json_encode(array('error' => $e->getMessage())));
		} catch (Throwable $e) {
			log_message('error', 'Cocina: ' . $e->getMessage() . ' en ' . basename($e->getFile()) . ':' . $e->getLine());
			$this->output->set_status_header(500)->set_output(json_encode(array('error' => 'Error interno del servidor')));
		}
	}
}
