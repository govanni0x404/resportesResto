<?php
defined('BASEPATH') or exit('No direct script access allowed');

/** Mantención de tipos de documento (boleta, factura, nota de crédito...). Grabar es solo para gerencia. */
class DocumentosTributarios extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model('MdocumentosTributarios');
	}

	public function index()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$data = array('nombreUsuario' => $this->session->userdata('nombre_usuario'));
		$data['columnas'] = $this->MdocumentosTributarios->columnas();
		$data['esAdmin'] = (int)$this->session->userdata('id_rol') === 1;
		$this->load->view('menuTop.php', $data);
		$this->load->view('documentostributarios/documentostributarios.php', $data);
		$this->load->view('menuFooter.php');
		$this->load->view('documentostributarios/js.php', $data);
	}

	public function listado()
	{
		$this->responder(function () {
			return $this->MdocumentosTributarios->listado();
		});
	}

	public function grabar()
	{
		$this->responder(function () {
			if ((int)$this->session->userdata('id_rol') !== 1) {
				throw new Exception('Solo gerencia puede modificar los tipos de documento', 403);
			}
			$p = $this->input->post();
			$nombre = mb_strtoupper(trim(preg_replace('/\s+/', ' ', (string)(isset($p['nombre']) ? $p['nombre'] : ''))));
			if ($nombre === '') throw new Exception('Falta el nombre del documento', 400);
			if (mb_strlen($nombre) > 50) throw new Exception('El nombre es demasiado largo (máximo 50 caracteres)', 400);
			$cod = isset($p['cod_sii']) ? trim((string)$p['cod_sii']) : '';
			if ($cod === '' || !ctype_digit($cod) || (int)$cod > 999) throw new Exception('El código SII debe ser un número (0 si es interno)', 400);
			$si = function ($k) use ($p) { return isset($p[$k]) && ($p[$k] === '1' || $p[$k] === 'true' || $p[$k] === 't'); };
			$d = array(
				'nombre_tdocumento' => $nombre,
				'cod_sii' => (int)$cod,
				'para_venta' => $si('para_venta'),
				'para_compra' => $si('para_compra'),
				'categoria_boleta' => $si('categoria_boleta'),
				'categoria_factura' => $si('categoria_factura'),
				'requiere_cliente' => $si('requiere_cliente'),
				'folio_automatico' => $si('folio_automatico'),
			);
			if (isset($p['formato_dte'])) {
				$f = trim((string)$p['formato_dte']);
				if (!in_array($f, array('80', '58', 'CARTA'), true)) throw new Exception('Formato DTE no válido', 400);
				$d['formato_dte'] = $f;
			}
			$id = $this->MdocumentosTributarios->grabar((int)(isset($p['id']) ? $p['id'] : 0), $d);
			return array('ok' => true, 'id' => $id, 'mensaje' => 'Documento grabado');
		});
	}

	private function responder($fn)
	{
		header('Content-Type: application/json; charset=utf-8');
		try {
			echo json_encode($fn(), JSON_UNESCAPED_UNICODE);
		} catch (Throwable $e) {
			// Errores del usuario (4xx) se muestran tal cual; los internos van al log y la pantalla ve un mensaje genérico
			$c = (int)$e->getCode();
			$usuario = $c >= 400 && $c < 500;
			http_response_code($usuario ? $c : 500);
			if (!$usuario) log_message('error', 'DocumentosTributarios: ' . $e->getMessage());
			echo json_encode(array('ok' => false, 'error' => $usuario ? $e->getMessage() : 'No se pudo grabar el documento. Intenta de nuevo.'), JSON_UNESCAPED_UNICODE);
		}
	}
}
