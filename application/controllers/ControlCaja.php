<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Herramientas > Control de cajas (11-11-2026): las cajas autorizadas en la intranet para este sistema, con la lista de
 * precios, la bodega y si se puede cambiar la lista en la Caja. Ver Mcontrolcaja.
 * El acceso lo controla el hook Permisos (módulo "ControlCaja" en Privilegios); `datos` no empieza con listar/obtener a
 * propósito, para que también pida el permiso.
 */
class ControlCaja extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model('Mcontrolcaja');
	}

	public function index()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		$data = array('nombreUsuario' => $this->session->userdata('nombre_usuario'));
		$this->load->view('menuTop.php', $data);
		$this->load->view('controlCaja/controlCaja.php');
		$this->load->view('menuFooter.php');
		$this->load->view('controlCaja/js.php');
	}

	/**
	 * Cajas de la sucursal elegida (12-11-2026: primero se elige la empresa y la sucursal, de Configuraciones > Datos de la
	 * Empresa). Sin id_sucursal: la sucursal de la sesión, o la primera de la primera empresa. Sin empresas ni sucursales
	 * (sin la migración 20261111200000): todas las cajas, como antes.
	 */
	public function datos()
	{
		$this->ejecutar(function () {
			return $this->datosSucursal((int)$this->input->get('id_sucursal'));
		});
	}

	public function guardar()
	{
		$this->ejecutar(function () {
			$p = $this->input->post();
			return $this->Mcontrolcaja->guardar(
				isset($p['id']) ? $p['id'] : 0,
				isset($p['id_lprecio']) ? $p['id_lprecio'] : 0,
				isset($p['id_bodega']) ? $p['id_bodega'] : 0,
				!empty($p['permitir_cambio_lprecio']) && $p['permitir_cambio_lprecio'] !== 'false' && $p['permitir_cambio_lprecio'] !== '0'
			);
		}, true);
	}

	/** Actualizar desde la intranet: las cajas de la empresa y sucursal elegidas (sin empresas: las de la empresa de la sesión). */
	public function sincronizar()
	{
		$this->ejecutar(function () {
			$idEmpresa = (int)$this->input->post('id_empresa');
			$idSucursal = (int)$this->input->post('id_sucursal');
			$this->load->model('Mempresas');
			if ($idSucursal > 0 && $this->Mempresas->disponible()) {   // id_empresa 0 = la de inicio de sesión
				$r = $this->Mempresas->cajasDeSucursal($idEmpresa, $idSucursal);
				return array('resultado' => $r) + $this->datosSucursal($idSucursal);
			}
			$r = $this->Mcontrolcaja->sincronizarIntranet();
			return array('resultado' => $r) + $this->datosSucursal(0);
		}, true);
	}

	/** Empresa sin sucursales cargadas: se traen de la intranet sus datos, sucursales y cajas. */
	public function sincronizarEmpresa()
	{
		$this->ejecutar(function () {
			$this->load->model('Mempresas');
			$idEmpresa = (int)$this->input->post('id_empresa');
			if ($idEmpresa === 0) {
				$fila = $this->Mempresas->sincronizarEmpresaSesion();   // la de inicio de sesión
			} else {
				$r = $this->Mempresas->sincronizar($idEmpresa);
				$fila = isset($r['resultado'][0]) ? $r['resultado'][0] : null;
			}
			if (!$fila) throw new Exception('La empresa no existe', 404);
			return array('resultado' => $fila, 'empresas' => $this->Mempresas->selector());
		}, true);
	}

	private function datosSucursal($idSucursal)
	{
		$this->load->model('Mempresas');
		$empresas = $this->Mempresas->selector();
		if (!$empresas) {
			return $this->Mcontrolcaja->listar() + array('empresas' => array(), 'id_empresa' => 0, 'id_sucursal' => 0);
		}
		$idEmpresa = 0;
		$buscar = function ($id) use ($empresas, &$idEmpresa) {
			foreach ($empresas as $e) foreach ($e['sucursales'] as $s) {
				if ($s['id'] === $id) { $idEmpresa = $e['id']; return true; }
			}
			return false;
		};
		$sesion = (int)$this->session->userdata('id_sucursal');
		if (!$idSucursal || !$buscar($idSucursal)) {
			$idSucursal = $sesion;
			if (!$buscar($idSucursal)) {
				$idSucursal = 0;
				if (!empty($empresas[0]['sesion']) && !$empresas[0]['sucursales']) {
					$idEmpresa = 0;   // la de inicio de sesión aún sin sucursales: la pantalla las pide a la intranet
				} else {
					foreach ($empresas as $e) {
						if ($e['sucursales']) { $idSucursal = $e['sucursales'][0]['id']; $idEmpresa = $e['id']; break; }
					}
					if (!$idSucursal) $idEmpresa = $empresas[0]['id'];
				}
			}
		}
		return $this->Mcontrolcaja->listar($idSucursal) + array('empresas' => $empresas, 'id_empresa' => $idEmpresa, 'id_sucursal' => $idSucursal,
			'id_sucursal_sesion' => $sesion);
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
			log_message('error', 'ControlCaja: ' . $e->getMessage() . ' en ' . basename($e->getFile()) . ':' . $e->getLine());
			$this->output->set_status_header(500)->set_output(json_encode(array('error' => 'Error interno del servidor. Revisa el registro (log).')));
		}
	}
}
