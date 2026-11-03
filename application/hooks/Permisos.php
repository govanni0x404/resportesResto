<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Control de acceso central: corre antes de cada método de controlador (hook post_controller_constructor).
 *
 * Antes los privilegios de "Privilegios de Usuario" solo ocultaban opciones del menú; entrando por la URL o por
 * AJAX cualquier usuario con sesión podía usar cualquier pantalla. Ahora se aplica la misma regla del menú en el
 * servidor:
 *  - Gerencia (id_rol 1) entra a todo.
 *  - Módulo registrado en psistema ('RESTO WEB'): entra solo si pusuario.autoriza = true para su rol.
 *  - Módulo no registrado: se permite (igual que el menú), salvo los SENSIBLES, que exigen permiso explícito.
 *  - Las consultas de apoyo (listados para combos y buscadores) se permiten a cualquier usuario con sesión, porque
 *    otras pantallas las usan (ej. Compras lista proveedores). Excepto en reportes y módulos sensibles.
 *
 * También deja abierto un buffer de salida para mandar como text/plain lo que los controladores imprimen
 * con echo json_encode(...) (ver Permisos::cerrar), así el navegador no lo interpreta como HTML.
 */
class Permisos
{
	const SISTEMA = 'RESTO WEB';

	/** Sin sesión: tienen su propia autenticación (login, token de la app o del agente). */
	private static $publicos = array('login', 'welcome', 'apimovil', 'agenteimpresion');

	/** Cualquier usuario con sesión (trabaja solo sobre el propio usuario). */
	private static $libres = array('perfildeusuario');

	/** Solo gerencia o un rol autorizado explícitamente en Privilegios de Usuario. */
	private static $sensibles = array('usuario', 'privilegiosusuario', 'configuraciones');

	/** Reportes: sin excepción para las consultas, muestran ventas, cajas y propinas. */
	private static $reportes = array('dashboard', 'cierresdecaja', 'consolidadoventas', 'detalleoperaciones',
		'documentostributarios', 'insumosporbodega', 'listadodeclientes', 'propinasporgarzon', 'resumenanulaciones',
		'resumendecompras', 'resumenentradabodega', 'resumensalidabodega', 'resumenventasproductosmes',
		'ventasmensualestotalizadas', 'ventasporproducto');

	/** Controladores sin menú propio: se rigen por el módulo de la pantalla que los usa. */
	private static $modulo = array('imagenproducto' => 'Productos', 'produccion' => 'Insumos', 'recetaproduccion' => 'Insumos');

	/**
	 * Métodos que otras pantallas usan: basta tener cualquiera de los módulos de la lista ('*' = cualquier usuario).
	 * Configuraciones/obtenerConfiguracionesRestoWeb solo entrega cómo mostrar los montos en los reportes.
	 */
	private static $compartidos = array(
		'configuraciones/obtenerconfiguracionesrestoweb' => '*',
		'dashboard/empresa' => '*',
		'detalleoperaciones/listarusuarios' => '*',
		'cierresdecaja/cierrecajapdf' => array('CierresDeCaja', 'Caja'),
		'insumos/grabarinsumo' => array('Insumos', 'Productos'),
		'recetas/grabarreceta' => array('Recetas', 'Productos'),
	);

	/** Consultas de apoyo: listados y búsquedas de solo lectura. */
	const CONSULTA = '/^(listado|listar|llenarcbo|obtener|busqueda|buscar|get)/i';

	private $accesos = null;

	public function verificar()
	{
		if (is_cli()) return;
		ob_start();
		header('X-Content-Type-Options: nosniff');
		header('X-Frame-Options: SAMEORIGIN');

		$CI = &get_instance();
		$clase = strtolower($CI->router->fetch_class());
		$metodo = strtolower($CI->router->fetch_method());
		if (in_array($clase, self::$publicos, true)) return;

		if (strlen((string)$CI->session->userdata('nombre_usuario')) === 0 || !$CI->session->userdata('nombre_conexion')) {
			$this->negar($CI, 401, 'Tu sesión expiró. Vuelve a ingresar.');
		}
		if (in_array($clase, self::$libres, true)) return;

		$idRol = (int)$CI->session->userdata('id_rol');
		if ($idRol === 1) return;

		$clave = $clase . '/' . $metodo;
		if (isset(self::$compartidos[$clave])) {
			$mods = self::$compartidos[$clave];
			if ($mods === '*') return;
			foreach ($mods as $m) if ($this->permitido($CI, $idRol, $m, false)) return;
			$this->negar($CI, 403);
		}

		$sensible = in_array($clase, self::$sensibles, true);
		if (!$sensible && !in_array($clase, self::$reportes, true) && preg_match(self::CONSULTA, $metodo)) return;

		$modulo = isset(self::$modulo[$clase]) ? self::$modulo[$clase] : $CI->router->fetch_class();
		if (!$this->permitido($CI, $idRol, $modulo, $sensible)) $this->negar($CI, 403);
	}

	/** Manda como JSON lo que el controlador imprimió con echo, si no fijó otro tipo (hook post_controller). */
	public function cerrar()
	{
		if (is_cli() || !ob_get_level()) return;
		$cuerpo = ob_get_contents();
		if (!headers_sent() && $cuerpo !== false && $cuerpo !== '') {
			$tieneTipo = false;
			foreach (headers_list() as $h) if (stripos($h, 'content-type:') === 0) { $tieneTipo = true; break; }
			$c = ltrim($cuerpo);
			// text/plain y no application/json: las pantallas antiguas hacen $.parseJSON() sobre la respuesta y con
			// application/json jQuery ya la entrega convertida (se rompían). text/plain llega igual que antes como texto,
			// y abierta directo en el navegador se ve como texto (con nosniff), no se ejecuta como HTML.
			if (!$tieneTipo && $c !== '' && ($c[0] === '{' || $c[0] === '[') && json_decode($c) !== null) {
				header('Content-Type: text/plain; charset=utf-8');
			}
		}
		ob_end_flush();
	}

	/** Misma regla que menu_visible() del menú; los módulos sensibles exigen permiso explícito. */
	private function permitido($CI, $idRol, $modulo, $sensible)
	{
		if ($this->accesos === null) {
			$this->accesos = array();
			try {
				$CI->load->helper('tenant');
				$db = get_tenant_db($CI);
				$r = $db->query('SELECT ps.nombre_vb, COALESCE(pu.autoriza, false) AS autoriza
					FROM psistema ps LEFT JOIN pusuario pu ON pu.id_psistema = ps.id_psistema AND pu.id_rol = ?
					WHERE ps.vigente = true AND ps.nombre_psistema = ?', array($idRol, self::SISTEMA));
				if ($r) foreach ($r->result() as $f) {
					$k = strtolower(trim($f->nombre_vb));
					// Si un módulo aparece repetido basta con que una fila lo autorice
					$this->accesos[$k] = !empty($this->accesos[$k]) || $f->autoriza === 't' || $f->autoriza === true;
				}
			} catch (Throwable $e) {
				log_message('error', 'Permisos: no se pudieron leer los privilegios: ' . $e->getMessage());
			}
		}
		$k = strtolower($modulo);
		if (!array_key_exists($k, $this->accesos)) return !$sensible;
		return $this->accesos[$k];
	}

	private function negar($CI, $codigo, $mensaje = null)
	{
		if ($mensaje === null) $mensaje = 'No tienes permiso para esta sección. Pide acceso a gerencia en Privilegios de Usuario.';
		log_message('error', 'Permisos: acceso negado (' . $codigo . ') a ' . $CI->router->fetch_class() . '/' . $CI->router->fetch_method()
			. ' usuario ' . $CI->session->userdata('nombre_usuario') . ' rol ' . (int)$CI->session->userdata('id_rol'));
		while (ob_get_level()) ob_end_clean();
		$ajax = $CI->input->is_ajax_request() || $CI->input->method() === 'post'
			|| stripos((string)$CI->input->get_request_header('Accept'), 'application/json') !== false;
		if ($codigo === 401 && !$ajax) {
			redirect(base_url());
		}
		http_response_code($codigo);
		if ($ajax) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('status' => 'error', 'ok' => false, 'error' => $mensaje, 'mensaje' => $mensaje), JSON_UNESCAPED_UNICODE);
			exit;
		}
		$base = base_url();
		$h = function ($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); };
		header('Content-Type: text/html; charset=utf-8');
		echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
			. '<title>Sin permiso</title><style>body{font-family:system-ui,Segoe UI,Arial,sans-serif;background:#f4f1ec;margin:0;display:flex;'
			. 'align-items:center;justify-content:center;min-height:100vh;color:#333}.c{background:#fff;border-radius:12px;padding:32px 28px;'
			. 'max-width:420px;box-shadow:0 6px 24px rgba(0,0,0,.08);text-align:center}h1{font-size:22px;margin:0 0 10px}p{margin:0 0 22px;'
			. 'line-height:1.5}a{display:inline-block;margin:4px;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:600}'
			. '.p{background:#b4432e;color:#fff}.s{background:#eee;color:#333}</style></head><body><div class="c"><h1>Sin permiso</h1><p>'
			. $h($mensaje) . '</p><a class="p" href="javascript:history.back()">Volver</a><a class="s" href="' . $h($base . 'index.php/Caja')
			. '">Ir a la Caja</a><a class="s" href="' . $h($base . 'index.php/Login/logout_ci') . '">Cerrar sesión</a></div></body></html>';
		exit;
	}
}
