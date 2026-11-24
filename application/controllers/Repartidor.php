<?php
defined('BASEPATH') or exit('No direct script access allowed');

/** Portal móvil del repartidor. El hook de permisos exige el módulo Delivery y el modelo limita los pedidos al usuario. */
class Repartidor extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Mdelivery');
	}

	public function index()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) === 0) redirect(base_url());
		$this->load->view('menuTop.php', array('nombreUsuario' => $this->session->userdata('nombre_usuario')));
		$this->load->view('delivery/repartidor.php', array('idUsuario' => (int)$this->session->userdata('id_usuario')));
		$this->load->view('menuFooter.php');
	}

	public function pedidos()
	{
		$this->json(function () { return array('pedidos' => $this->Mdelivery->misPedidos((int)$this->session->userdata('id_usuario'))); });
	}

	public function accion()
	{
		$this->json(function () {
			if ($this->input->method() !== 'post') throw new Exception('Método no permitido.', 405);
			$b = json_decode($this->input->raw_input_stream, true);
			if (!is_array($b)) $b = $this->input->post();
			return $this->Mdelivery->accionRepartidor((int)$this->session->userdata('id_usuario'),
				(int)(isset($b['id_delivery']) ? $b['id_delivery'] : 0), (string)(isset($b['accion']) ? $b['accion'] : ''),
				(string)(isset($b['motivo']) ? $b['motivo'] : ''));
		}, true);
	}

	public function entrega()
	{
		$this->json(function () {
			if ($this->input->method() !== 'post') throw new Exception('Método no permitido.', 405);
			$b = json_decode($this->input->raw_input_stream, true);
			if (!is_array($b)) $b = $this->input->post();
			return $this->Mdelivery->declararEntrega((int)$this->session->userdata('id_usuario'),
				(int)(isset($b['id_delivery']) ? $b['id_delivery'] : 0), is_array($b) ? $b : array());
		}, true);
	}

	/** Guarda un respaldo privado; los bytes no quedan dentro de una carpeta pública del sitio. */
	public function subirEvidencia()
	{
		$this->json(function () {
			if ($this->input->method() !== 'post') throw new Exception('Método no permitido.', 405);
			if (empty($_FILES['evidencia']['name'])) throw new Exception('Selecciona un archivo de respaldo.', 400);
			$dir = APPPATH . 'cache/delivery_evidencia/';
			if (!is_dir($dir) && !@mkdir($dir, 0750, true)) throw new Exception('No se pudo preparar el almacenamiento privado de respaldos.', 500);
			if (!is_writable($dir)) throw new Exception('El almacenamiento privado de respaldos no tiene permisos de escritura.', 500);
			$this->load->library('upload');
			$this->upload->initialize(array('upload_path' => $dir, 'allowed_types' => 'jpg|jpeg|png|pdf', 'max_size' => 5120,
				'encrypt_name' => true, 'file_ext_tolower' => true, 'remove_spaces' => true, 'detect_mime' => true), true);
			if (!$this->upload->do_upload('evidencia')) throw new Exception(strip_tags($this->upload->display_errors('', '')), 400);
			$f = $this->upload->data();
			$path = $dir . $f['file_name'];
			$fi = finfo_open(FILEINFO_MIME_TYPE); $mime = $fi ? finfo_file($fi, $path) : ''; if ($fi) finfo_close($fi);
			$ext = strtolower(pathinfo($f['file_name'], PATHINFO_EXTENSION));
			$extMime = array('jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'pdf' => 'application/pdf');
			$tamano = (int)filesize($path);
			if (!isset($extMime[$ext]) || $mime !== $extMime[$ext] || $tamano <= 0 || $tamano > 5 * 1024 * 1024) { @unlink($path); throw new Exception('El archivo no coincide con un respaldo JPG, PNG o PDF válido de hasta 5 MB.', 400); }
			$idDelivery = (int)$this->input->post('id_delivery');
			try {
				return $this->Mdelivery->guardarEvidenciaDelivery((int)$this->session->userdata('id_usuario'), $idDelivery, $f['file_name'],
					(string)$f['client_name'], (string)$mime, $tamano);
			} catch (Throwable $e) { @unlink($path); throw $e; }
		}, true);
	}

	/** Abre un respaldo solo a su repartidor o a un usuario que administra Configuraciones. */
	public function verEvidencia($id = 0)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) === 0) show_error('Debes iniciar sesión.', 401);
		$e = $this->Mdelivery->obtenerEvidenciaDelivery((int)$id);
		if (!$e) show_404();
		$idUsuario = (int)$this->session->userdata('id_usuario');
		$idRol = (int)$this->session->userdata('id_rol');
		$autorizado = (int)$e['id_usuario'] === $idUsuario || $idRol === 1;
		if (!$autorizado) {
			$this->load->helper('permisos_web');
			$autorizado = permiso_web($this, 'Configuraciones', $idRol);
		}
		if (!$autorizado) show_error('No tienes permiso para ver este respaldo.', 403);
		$nombre = (string)$e['nombre_archivo'];
		if (!preg_match('/^[a-f0-9]{32}\.(jpg|jpeg|png|pdf)$/i', $nombre)) show_404();
		$path = APPPATH . 'cache/delivery_evidencia/' . $nombre;
		if (!is_file($path)) show_404();
		$this->output->set_content_type($e['tipo_mime'])->set_header('X-Content-Type-Options: nosniff')
			->set_header('Content-Disposition: inline; filename="respaldo-delivery-' . (int)$e['id_delivery'] . '.' . pathinfo($nombre, PATHINFO_EXTENSION) . '"')
			->set_output(file_get_contents($path));
	}

	public function rendicion()
	{
		$this->json(function () { return array('rendicion' => $this->Mdelivery->saldoRendicion((int)$this->session->userdata('id_usuario'))); });
	}

	public function presentarRendicion()
	{
		$this->json(function () {
			if ($this->input->method() !== 'post') throw new Exception('Método no permitido.', 405);
			$b = json_decode($this->input->raw_input_stream, true);
			if (!is_array($b)) $b = $this->input->post();
			$accionId = isset($b['accion_id']) && is_scalar($b['accion_id']) ? (string)$b['accion_id'] : '';
			$obs = isset($b['observacion']) && is_scalar($b['observacion']) ? (string)$b['observacion'] : '';
			return $this->Mdelivery->presentarRendicion((int)$this->session->userdata('id_usuario'),
				(int)(isset($b['monto']) ? $b['monto'] : 0), $accionId, $obs);
		}, true);
	}

	private function json($accion, $soloPost = false)
	{
		$this->output->set_content_type('application/json', 'utf-8');
		try { $this->output->set_output(json_encode($accion(), JSON_UNESCAPED_UNICODE)); }
		catch (Throwable $e) {
			$c = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
			if ($c >= 500) log_message('error', 'Repartidor: ' . $e->getMessage());
			$this->output->set_status_header($c)->set_output(json_encode(array('ok' => false, 'error' => $c >= 500 ? 'No se pudo completar la acción.' : $e->getMessage()), JSON_UNESCAPED_UNICODE));
		}
	}
}
