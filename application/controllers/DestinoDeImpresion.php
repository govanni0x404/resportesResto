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
		$this->load->view('funciones.php');
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
			'ruta_windows' => $d['ruta_windows'],
			'impresion_grafica_windows' => $d['impresion_grafica_windows'],
			'permite_precuenta' => $d['permite_precuenta'],
			'impresion_local' => $d['impresion_local'],
			'usar_logo_precuenta_pos' => $d['usar_logo_precuenta_pos'],
			'size_font' => $d['size_font'],
			'sin_impresion' => $d['sin_impresion'],
			'usar_dimpresion_recinto' => $d['usar_dimpresion_recinto'],
			'usar_dimpresion_precuenta_android' => $d['usar_dimpresion_precuenta_android'],
		);
		$r = $this->MdestinoDeImpresion->grabarNuevoDestinoDeImpresion($id, $miData);
		echo json_encode($r);
	}

	/**
	 * La IP de la impresora tiene que ir con puntos (192.168.1.50). Antes la pantalla borraba los puntos y quedaba
	 * "19216810070", que no sirve para imprimir y no se puede adivinar cómo era. También se acepta una ruta de red
	 * (//equipo/impresora) o un nombre de equipo. Devuelve el texto limpio o array('error' => ...).
	 */
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
		if (!preg_match('#^[A-Za-z0-9._\-/\\]{1,50}$#', $ip)) {
			return array('error' => 'La IP o ruta de la impresora tiene caracteres no válidos');
		}
		return $ip;
	}
}
