<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Agente de impresión de Windows. Dos caras:
 *  - Administración (con sesión): index, crear, revocar, instalador. Se entra desde Destino de impresión.
 *  - API del agente (sin sesión, con clave "<conexion>.<secreto>"): pendientes y resultado.
 */
class AgenteImpresion extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model('Mcola_impresion');
	}

	// ---------------- administración ----------------

	public function index()
	{
		$this->sesion();
		$this->Mcola_impresion->conectar();
		$this->load->view('menuTop.php', array('nombreUsuario' => $this->session->userdata('nombre_usuario')));
		$this->load->view('agenteImpresion/agente.php', array(
			'agentes' => $this->Mcola_impresion->agentes(),
			'ultimos' => $this->Mcola_impresion->ultimos(15),
		));
		$this->load->view('menuFooter.php');
	}

	public function crear()
	{
		$this->sesion(true);
		$this->Mcola_impresion->conectar();
		$nombre = trim((string)$this->input->post('nombre'));
		if ($nombre === '') return $this->json(array('error' => 'Escribe un nombre para el equipo (ej: Caja, Cocina)'), 400);
		$token = $this->Mcola_impresion->crearAgente($nombre, $this->session->userdata('nombre_conexion'));
		$this->json(array('token' => $token));
	}

	public function revocar()
	{
		$this->sesion(true);
		$this->Mcola_impresion->conectar();
		$this->Mcola_impresion->revocarAgente((int)$this->input->post('id_agente'));
		$this->json(array('ok' => true));
	}

	/** Descarga instalar.bat ya configurado con la dirección del sistema y la clave del equipo. */
	public function instalador()
	{
		$this->sesion(true);
		$token = trim((string)$this->input->post('token'));
		list($conexion, $secreto) = array_pad(explode('.', $token, 2), 2, '');
		if ($conexion !== $this->session->userdata('nombre_conexion') || !$this->Mcola_impresion->conectar() || !$this->Mcola_impresion->agenteDeSecreto($secreto)) {
			show_error('Clave no válida', 400);
		}
		$url = base_url() . 'index.php/AgenteImpresion/';
		$script = base_url() . 'assets/agente-impresion/agente.ps1';
		$bat = "@echo off\r\n"
			. "setlocal\r\n"
			. "net session >nul 2>&1\r\n"
			. "if %errorlevel% neq 0 (\r\n"
			. "  echo Pidiendo permisos de administrador...\r\n"
			. "  powershell -NoProfile -Command \"Start-Process -FilePath '%~f0' -Verb RunAs\"\r\n"
			. "  exit /b\r\n"
			. ")\r\n"
			. "set DIR=%ProgramData%\\RestoWebAgente\r\n"
			. "if not exist \"%DIR%\" mkdir \"%DIR%\"\r\n"
			. "echo Descargando el agente...\r\n"
			. "powershell -NoProfile -ExecutionPolicy Bypass -Command \"[Net.ServicePointManager]::SecurityProtocol=[Net.SecurityProtocolType]::Tls12; Invoke-WebRequest -UseBasicParsing '" . $script . "' -OutFile '%DIR%\\agente.ps1'\"\r\n"
			. "if not exist \"%DIR%\\agente.ps1\" (\r\n"
			. "  echo No se pudo descargar el agente. Revisa la conexion a internet.\r\n"
			. "  pause\r\n"
			. "  exit /b 1\r\n"
			. ")\r\n"
			. "echo {\"url\":\"" . $url . "\",\"token\":\"" . $token . "\"}> \"%DIR%\\config.json\"\r\n"
			. "schtasks /End /TN RestoWebAgenteImpresion >nul 2>&1\r\n"
			. "schtasks /Create /TN RestoWebAgenteImpresion /TR \"powershell -NoProfile -WindowStyle Hidden -ExecutionPolicy Bypass -File \\\"%DIR%\\agente.ps1\\\"\" /SC ONSTART /RU SYSTEM /RL HIGHEST /F\r\n"
			. "schtasks /Run /TN RestoWebAgenteImpresion\r\n"
			. "echo.\r\n"
			. "echo Listo: el agente quedo instalado y se inicia solo con Windows. Registro: %DIR%\\agente.log\r\n"
			. "pause\r\n";
		$this->output->set_content_type('application/octet-stream')
			->set_header('Content-Disposition: attachment; filename="instalar-agente-impresion.bat"')
			->set_output($bat);
	}

	// ---------------- API del agente ----------------

	/** Devuelve las comandas que le tocan a las impresoras de este equipo. */
	public function pendientes()
	{
		$agente = $this->agente();
		$imp = json_decode((string)$this->input->post('impresoras'), true);
		$trabajos = $this->Mcola_impresion->pendientes($agente, is_array($imp) ? $imp : array());
		$this->json(array('trabajos' => array_map(function ($t) {
			return array('id' => (int)$t->id_cola, 'destino' => $t->destino, 'impresora' => $t->impresora,
				'ip' => $t->ip, 'puerto' => (int)$t->puerto, 'datos' => $t->datos_b64);
		}, $trabajos)));
	}

	public function resultado()
	{
		$agente = $this->agente();
		$this->Mcola_impresion->resultado($agente, (int)$this->input->post('id'), (string)$this->input->post('ok') === '1', (string)$this->input->post('error'));
		$this->json(array('ok' => true));
	}

	// ---------------- utilidades ----------------

	private function agente()
	{
		if ($this->input->method() !== 'post') return $this->terminar(405, 'Método no permitido');
		list($conexion, $secreto) = array_pad(explode('.', trim((string)$this->input->post('token')), 2), 2, '');
		if ($conexion === '' || $secreto === '' || !$this->Mcola_impresion->conectar($conexion)) return $this->terminar(401, 'Clave no válida');
		$agente = $this->Mcola_impresion->agenteDeSecreto($secreto);
		if (!$agente) return $this->terminar(401, 'Clave no válida o equipo revocado');
		return $agente;
	}

	private function sesion($soloPost = false)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			if ($soloPost) return $this->terminar(401, 'Sesión expirada, vuelve a ingresar');
			redirect(base_url());
		}
		if ($soloPost && $this->input->method() !== 'post') $this->terminar(405, 'Método no permitido');
	}

	private function json($datos, $codigo = 200)
	{
		$this->output->set_status_header($codigo)->set_content_type('application/json', 'utf-8')->set_output(json_encode($datos));
	}

	private function terminar($codigo, $mensaje)
	{
		$this->json(array('error' => $mensaje), $codigo);
		$this->output->_display();
		exit;
	}
}
