<?php
defined('BASEPATH') or exit('No direct script access allowed');

class DestinoDeImpresion extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model(array('MdestinoDeImpresion', 'Mroles'));
	}

	public function index()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$data = array(
			'nombreUsuario' => $this->session->userdata('nombre_usuario'),
			//'modulosDisponibles' => json_decode($this->Mroles->modulosDisponibles($this->session->userdata('id_rol')), true),
			//'verificarSiEsAdmin' => json_decode($this->Mroles->verificarSiEsAdmin($this->session->userdata('id_rol')), true)
		);
		$this->load->view('menuTop.php', $data);
		$this->load->view('destinoImpresion/destinoImpresion.php');
		$this->load->view('menuFooter.php');
		$this->load->view('destinoImpresion/listadoDestinoImpresion.php');

		$this->load->view('destinoImpresion/jsNuevoDestinoDeImpresion.php');
		// Probar impresión directa en este equipo (agente local, como mipdv_web). Ver config/impresion_local.php
		$this->config->load('impresion_local', true);
		$this->load->view('destinoImpresion/probarImpresion.php', array(
			'impresion_local' => array(
				'url' => (string)$this->config->item('impresion_local_url', 'impresion_local'),
				'clave' => (string)$this->config->item('impresion_local_clave', 'impresion_local'),
			),
			'usuario' => (string)$this->session->userdata('nombre_usuario'),
			'empresa' => (string)$this->session->userdata('nombre_cliente'),
		));
		$this->load->view('funciones.php');
	}

	/**
	 * 14-11-2026: descarga el instalador del agente de impresión local (impresionWeb.exe, puerto 5050) para instalarlo en
	 * el computador de la caja de cada cliente. Está en instaladores/impresionWeb.exe; para actualizarlo se reemplaza
	 * ese archivo. Solo con sesión iniciada (y el permiso del módulo, que revisa el hook Permisos).
	 */
	public function descargarAgenteLocal()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		$ruta = FCPATH . 'instaladores' . DIRECTORY_SEPARATOR . 'impresionWeb.exe';
		if (!is_file($ruta) || !is_readable($ruta)) {
			show_error('El instalador del agente de impresión no está en el servidor (instaladores/impresionWeb.exe).', 404, 'Instalador no disponible');
			return;
		}
		$this->load->helper('download');
		force_download($ruta, NULL);
	}

	public function ListadoDestinoDeImpresionDatatable()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		echo json_encode($this->MdestinoDeImpresion->ListadoDestinoDeImpresionDatatable());
	}

	public function grabarNuevoDestinoDeImpresion()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		// La ruta y la IP se usan para imprimir desde el servidor: solo un administrador las cambia
		if ((int)$this->session->userdata('id_rol') !== 1) {
			echo json_encode(array('error' => 'Solo un administrador puede modificar los destinos de impresión'));
			return;
		}
		$d = $this->input->post('prod');
		if (!is_array($d) || trim((string)($d['nombre_dimpresion'] ?? '')) === '') {
			echo json_encode(array('error' => 'Datos inválidos: falta el nombre del destino de impresión'));
			return;
		}
		$ip = $this->limpiarIp((string)($d['ip'] ?? ''));
		if (is_array($ip)) {
			echo json_encode($ip);
			return;
		}
		$puerto = trim((string)($d['puerto'] ?? ''));
		if ($puerto !== '' && (!ctype_digit($puerto) || (int)$puerto < 1 || (int)$puerto > 65535)) {
			echo json_encode(array('error' => 'El puerto debe ser un número entre 1 y 65535 (las impresoras de red usan 9100)'));
			return;
		}
		$id = intval($d['id_dimpresion'] ?? 0);
		$miData = array(
			'nombre_dimpresion' => $d['nombre_dimpresion'],
			'ip' => $ip,
			'puerto' => $puerto,
			'ruta_windows' => trim((string)($d['ruta_windows'] ?? '')),
			// 14-11-2026: casillas que no llegan = false (antes se grababa NULL)
			'impresion_grafica_windows' => $this->siNo($d['impresion_grafica_windows'] ?? null),
			'permite_precuenta' => $this->siNo($d['permite_precuenta'] ?? null),
			'impresion_local' => $this->siNo($d['impresion_local'] ?? null),
			'usar_logo_precuenta_pos' => $this->siNo($d['usar_logo_precuenta_pos'] ?? null),
			'size_font' => max(6, min(30, (int)($d['size_font'] ?? 8) ?: 8)),
			'sin_impresion' => $this->siNo($d['sin_impresion'] ?? null),
			'usar_dimpresion_recinto' => $this->siNo($d['usar_dimpresion_recinto'] ?? null),
			'usar_dimpresion_precuenta_android' => $this->siNo($d['usar_dimpresion_precuenta_android'] ?? null),
		);
		$r = $this->MdestinoDeImpresion->grabarNuevoDestinoDeImpresion($id, $miData);
		echo json_encode($r);
	}

	/**
	 * La IP de la impresora tiene que ir con puntos (192.168.1.50). Antes la pantalla borraba los puntos y quedaba
	 * "19216810070", que no sirve para imprimir y no se puede adivinar cómo era. También se acepta una ruta de red
	 * (//equipo/impresora) o un nombre de equipo. Devuelve el texto limpio o array('error' => ...).
	 */
	private function siNo($v)
	{
		if (is_bool($v)) return $v;
		return in_array(strtolower(trim((string)$v)), array('true', 't', '1', 'on', 'si', 'sí'), true);
	}

	private function limpiarIp($ip)
	{
		$ip = trim($ip);
		if ($ip === '') return '';
		if (preg_match('/^\d+$/', $ip) && strlen($ip) > 3) {
			return array('error' => 'La IP "' . $ip . '" no tiene puntos. Escríbela completa, por ejemplo 192.168.100.70');
		}
		if (preg_match('/^[\d.]+$/', $ip) && strpos($ip, '.') !== false) {   // un número corto ("1") es el valor de relleno de siempre
			if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
				return array('error' => 'La IP "' . $ip . '" no es válida. Debe tener 4 números del 0 al 255 separados por puntos, ej. 192.168.100.70');
			}
			return $ip;
		}
		// 14-11-2026: antes '\\]' dejaba el ] escapado ("missing terminating ]") y se rechazaba todo nombre de equipo
		if (!preg_match('#^[A-Za-z0-9._/\\\\-]{1,50}$#', $ip)) {
			return array('error' => 'La IP o ruta de la impresora tiene caracteres no válidos');
		}
		return $ip;
	}
}
