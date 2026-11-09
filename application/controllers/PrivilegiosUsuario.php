<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Privilegios de la web por rol. Solo gerencia (id_rol 1) entra aquí (lo exige hooks/Permisos.php vía permiso_web()).
 * Gerencia ve todo siempre; aquí habilita o quita cada módulo a los demás roles. Se guarda en la tabla permiso_web.
 */
class PrivilegiosUsuario extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model('MprivilegiosUsuario');
		$this->load->helper('permisos_web');
	}

	public function index()
	{
		$data = array(
			'nombreUsuario' => $this->session->userdata('nombre_usuario'),
			'hayTabla' => $this->MprivilegiosUsuario->hayTabla(),
		);
		$this->load->view('menuTop.php', $data);
		$this->load->view('privilegios/privilegiosUsuario.php', $data);
		$this->load->view('menuFooter.php');
		$this->load->view('privilegios/jsPrivilegiosUsuario.php', $data);
	}

	/** Roles que se pueden configurar (todos menos gerencia). */
	public function ListadoDeRoles()
	{
		$this->responder(function () {
			return array('ok' => true, 'roles' => $this->MprivilegiosUsuario->roles());
		});
	}

	/** Módulos agrupados con su estado para el rol. */
	public function ListarPrivilegiosPorRol()
	{
		$this->responder(function () {
			$idRol = $this->rol();
			$accesos = $this->MprivilegiosUsuario->accesos($idRol);
			$grupos = array();
			foreach (permisos_web_catalogo() as $grupo => $mods) {
				$lista = array();
				foreach ($mods as $m => $nombre) {
					$lista[] = array('modulo' => $m, 'nombre' => $nombre, 'autoriza' => !empty($accesos[strtolower($m)]));
				}
				$grupos[] = array('grupo' => $grupo, 'modulos' => $lista);
			}
			return array('ok' => true, 'grupos' => $grupos);
		});
	}

	/** Habilita o quita un módulo (modulo = nombre del catálogo) o todos (modulo = '*'). */
	public function guardar()
	{
		$this->responder(function () {
			$idRol = $this->rol();
			$modulo = trim((string)$this->input->post('modulo'));
			$autoriza = in_array((string)$this->input->post('autoriza'), array('1', 'true'), true);
			$mods = array();
			foreach (permisos_web_catalogo() as $grupo) foreach ($grupo as $m => $n) $mods[strtolower($m)] = $m;
			if ($modulo === '*') {
				$lista = array_values($mods);
			} elseif (isset($mods[strtolower($modulo)])) {
				$lista = array($mods[strtolower($modulo)]);
			} else {
				throw new Exception('Módulo desconocido', 400);
			}
			$this->MprivilegiosUsuario->guardar($idRol, $lista, $autoriza);
			return array('ok' => true);
		});
	}

	private function rol()
	{
		$idRol = (int)$this->input->post('id_rol');
		if ($idRol <= 0) throw new Exception('Elige un rol', 400);
		if ($idRol === 1) throw new Exception('Gerencia siempre tiene todos los permisos', 400);
		if (!$this->MprivilegiosUsuario->existeRol($idRol)) throw new Exception('El rol no existe', 400);
		return $idRol;
	}

	private function responder($fn)
	{
		header('Content-Type: application/json; charset=utf-8');
		if ($this->input->method() !== 'post') {
			http_response_code(405);
			echo json_encode(array('ok' => false, 'error' => 'Método no permitido'));
			return;
		}
		try {
			echo json_encode($fn(), JSON_UNESCAPED_UNICODE);
		} catch (Throwable $e) {
			$c = (int)$e->getCode();
			$usuario = $c >= 400 && $c < 500;
			http_response_code($usuario ? $c : 500);
			if (!$usuario) log_message('error', 'PrivilegiosUsuario: ' . $e->getMessage());
			echo json_encode(array('ok' => false, 'error' => $usuario ? $e->getMessage() : 'No se pudo completar la acción. Intenta de nuevo.'), JSON_UNESCAPED_UNICODE);
		}
	}
}
