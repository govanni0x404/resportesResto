<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Caja web (punto de venta). Las acciones responden JSON; ticket() y pdf() imprimen documentos.
 * Cada usuario trabaja sobre su propia caja abierta (caja.nombre_computador = 'CAJA WEB').
 */
class Caja extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model('Mcaja');
	}

	public function index()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->Mcaja->registrarModulo();
		if (!$this->Mcaja->tienePermiso($this->idRol())) {
			show_error('Tu rol no tiene acceso a la Caja. Pide que te habiliten el módulo "Caja" en Privilegios de Usuario.', 403, 'Sin acceso');
		}
		$data = array(
			'nombreUsuario' => $this->session->userdata('nombre_usuario'),
		);
		$this->load->view('menuTop.php', $data);
		$this->load->view('caja/caja.php');
		$this->load->view('menuFooter.php');
		// Impresión local (12-11-2026): con Configuraciones > Imprimir documentos en el equipo de la caja, la pantalla manda
		// los documentos al agente de impresión del equipo. La dirección y la clave del agente solo van si está marcada.
		$local = false;
		try { $local = !empty($this->Mcaja->opciones()['impresion_local']); } catch (Exception $e) {}
		$this->config->load('impresion_local', true);
		$this->load->view('caja/impresionLocal.php', array('impresion_local' => $local ? array(
			'url' => (string)$this->config->item('impresion_local_url', 'impresion_local'),
			'clave' => (string)$this->config->item('impresion_local_clave', 'impresion_local'),
		) : null));
		$this->load->view('caja/js.php');
	}

	public function estado()
	{
		$this->ejecutar(function () {
			$caja = $this->Mcaja->cajaAbierta($this->idUsuario());
			if ($caja) return array('caja' => $this->resumenVisible($this->Mcaja->resumenCaja($caja)));
			// Herramientas > Control de cajas: al abrir se elige empresa, sucursal y una caja libre (sin cajas, como antes)
			return array('caja' => null, 'fondo_sugerido' => $this->Mcaja->fondoSugerido($this->idUsuario())) + $this->apertura(0);
		});
	}

	/** Cajas libres de la sucursal elegida al abrir (12-11-2026). */
	public function cajasParaAbrir()
	{
		$this->ejecutar(function () {
			$this->cajaNoAbierta();
			return $this->apertura((int)$this->input->get('id_sucursal'));
		});
	}

	/** La Caja abierta en el navegador mantiene viva la sesión (y el acceso a la intranet) mientras se trabaja en ella. */
	public function mantenerSesion()
	{
		$this->ejecutar(function () {
			$this->load->model('Mlogin');
			$this->Mlogin->renovarBearerSiCorresponde();
			return array('ok' => true);
		});
	}

	private function cajaNoAbierta()
	{
		if ($this->Mcaja->cajaAbierta($this->idUsuario())) throw new Exception('Ya tienes una caja abierta', 409);
	}

	/**
	 * Para abrir la caja: empresas y sucursales (Configuraciones > Datos de la Empresa, más la de inicio de sesión) y las
	 * cajas de Control de cajas de la sucursal elegida que no están en uso (sin turno abierto en la tabla caja).
	 * Sin la migración de empresas/sucursales: las cajas de la sucursal de la sesión, como antes.
	 */
	private function apertura($idSucursal)
	{
		$this->load->model(array('Mcontrolcaja', 'Mempresas'));
		$res = array('empresas' => array(), 'id_empresa' => 0, 'id_sucursal' => 0, 'cajas_control' => array(), 'cajas_en_uso' => 0,
			'hay_cajas' => false);
		try {
			$res['hay_cajas'] = $this->Mcontrolcaja->hayCajasVigentes();
			$empresas = $this->Mempresas->selector();
			// La empresa de inicio de sesión sin sucursales guardadas: se traen de la intranet una vez por sesión
			if ($empresas && !empty($empresas[0]['sesion']) && !$empresas[0]['sucursales'] && !$this->session->userdata('empresa_sesion_sinc')) {
				$this->session->set_userdata('empresa_sesion_sinc', 1);
				try {
					$this->Mempresas->sincronizarEmpresaSesion();
				} catch (Exception $e) {
					log_message('error', 'Caja::apertura empresa de la sesión: ' . $e->getMessage());
				}
				$empresas = $this->Mempresas->selector();
			}
			if (!$empresas) {
				$cajas = $this->Mcontrolcaja->paraAbrir($this->idUsuario());
			} else {
				$idEmpresa = null;
				$buscar = function ($id) use ($empresas, &$idEmpresa) {
					foreach ($empresas as $e) foreach ($e['sucursales'] as $s) {
						if ($s['id'] === $id) { $idEmpresa = $e['id']; return true; }
					}
					return false;
				};
				if (!$idSucursal || !$buscar($idSucursal)) {
					$idSucursal = (int)$this->session->userdata('id_sucursal');
					if (!$buscar($idSucursal)) {
						$idSucursal = 0;
						foreach ($empresas as $e) {
							if ($e['sucursales']) { $idSucursal = $e['sucursales'][0]['id']; $idEmpresa = $e['id']; break; }
						}
						if ($idEmpresa === null) $idEmpresa = $empresas[0]['id'];
					}
				}
				$res['empresas'] = $empresas;
				$res['id_empresa'] = $idEmpresa;
				$res['id_sucursal'] = $idSucursal;
				$cajas = $idSucursal ? $this->Mcontrolcaja->paraAbrir($this->idUsuario(), $idSucursal) : array();
			}
			// Solo las que no están en uso (sin turno abierto de otro usuario)
			foreach ($cajas as $c) {
				if ($c['ocupada_por'] !== '') $res['cajas_en_uso']++;
				else $res['cajas_control'][] = $c;
			}
		} catch (Exception $e) {
			log_message('error', 'Caja::apertura: ' . $e->getMessage());
		}
		return $res;
	}

	public function catalogo()
	{
		$this->ejecutar(function () {
			return $this->Mcaja->catalogo((int)$this->input->get('id_lprecio'), $this->idUsuario(), $this->idRol());
		});
	}

	public function abrir()
	{
		$this->ejecutar(function () {
			$fondo = (int)$this->input->post('fondo');
			if ($fondo < 0) throw new Exception('El fondo no puede ser negativo', 400);
			$this->Mcaja->abrirCaja($this->idUsuario(), $fondo, (int)$this->input->post('id_control_caja'));
			return array('ok' => true);
		}, true);
	}

	public function clientes()
	{
		$this->ejecutar(function () {
			return array('clientes' => $this->Mcaja->buscarClientes($this->input->get('q')));
		});
	}

	/** Factura: la ficha del cliente y los giros / direcciones / solicitantes usados antes en sus facturas. */
	public function receptorCliente()
	{
		$this->ejecutar(function () {
			$this->cajaRequerida();
			return $this->Mcaja->receptorCliente((int)$this->input->get('id_cliente'));
		});
	}

	public function guardarCliente()
	{
		$this->ejecutar(function () {
			return $this->Mcaja->guardarCliente($this->input->post());
		}, true);
	}

	/** El cajero cambió la lista de precios: si no es la pública, se pide la clave de administración. */
	public function autorizarLista()
	{
		$this->ejecutar(function () {
			$this->cajaRequerida();
			$b = $this->cuerpoJson();
			return $this->Mcaja->autorizarLista($this->idUsuario(), $this->idRol(),
				isset($b['id_lprecio']) ? (int)$b['id_lprecio'] : 0, isset($b['autorizacion']) ? $b['autorizacion'] : null);
		}, true);
	}

	public function vender()
	{
		$this->ejecutar(function () {
			$body = json_decode($this->input->raw_input_stream, true);
			if (!is_array($body)) throw new Exception('Datos de venta inválidos', 400);
			$r = $this->Mcaja->registrarVenta($this->cajaRequerida(), $this->idUsuario(), $this->idRol(), $body);
			// Venta directa: la comanda la arma el servidor con lo vendido; la pantalla solo recibe su número
			$r['comanda_id'] = $this->guardarComanda($r['comanda'], array('id_venta' => (int)$r['id_venta'], 'directa' => true,
				'observacion' => $r['observacion'], 'retira' => isset($r['retira']) ? $r['retira'] : ''));
			unset($r['comanda'], $r['observacion'], $r['retira']);
			return $r;
		}, true);
	}

	// ---------------- mesas ----------------

	public function mesas()
	{
		$this->ejecutar(function () {
			$this->cajaRequerida();
			return $this->Mcaja->mesas();
		});
	}

	public function guardarPlanoMesas()
	{
		$this->ejecutar(function () {
			$this->cajaRequerida();
			return $this->Mcaja->guardarPlanoMesas($this->cuerpoJson());
		}, true);
	}

	public function detalleMesa()
	{
		$this->ejecutar(function () {
			$this->cajaRequerida();
			return $this->Mcaja->detalleMesa((int)$this->input->get('id_mesa'));
		});
	}

	/**
	 * 14-11-2026: comandas de la app de garzones (Configuraciones > Imprimir las comandas de la app). La pantalla lo
	 * consulta cada pocos segundos con la caja abierta; cada pedido nuevo de la app vuelve como una comanda guardada y la
	 * pantalla la imprime igual que las suyas (imprimirComanda / comandaLocal), cada producto en su destino de impresión.
	 */
	public function comandasApp()
	{
		$this->ejecutar(function () {
			$this->cajaRequerida();
			$res = array();
			foreach ($this->Mcaja->tomarComandasApp() as $p) {
				$id = $this->guardarComanda($p['lineas'], array('id_mesa' => $p['id_mesa'],
					'observacion' => 'Pedido de la app' . ($p['garzon'] !== '' ? ' - ' . $p['garzon'] : '')));
				if ($id) $res[] = array('comanda_id' => $id, 'id_mesa' => $p['id_mesa'], 'mesa' => $this->Mcaja->etiquetaMesa($p['id_mesa']));
			}
			return array('comandas' => $res);
		}, true);
	}

	public function enviarMesa()
	{
		$this->ejecutar(function () {
			$b = $this->cuerpoJson();
			unset($b['desde_app']);   // marca reservada a ApiMovil/pedidoMesa
			$r = $this->Mcaja->enviarMesa($this->cajaRequerida(), $this->idUsuario(), $b, $this->idRol());
			$r['comanda_id'] = $this->guardarComanda($r['comanda'], array('id_mesa' => (int)($b['id_mesa'] ?? 0)));
			unset($r['comanda']);
			return $r;
		}, true);
	}

	// ---------------- Dividir cuenta guardado en la base (models/Mdivisioncuenta.php) ----------------

	/** División del pedido abierto de la mesa. disponible = false si falta la migración (la pantalla la guarda en el computador). */
	public function division()
	{
		$this->ejecutar(function () {
			$this->cajaRequerida();
			$this->load->model('Mdivisioncuenta');
			if (!$this->Mdivisioncuenta->disponible()) return array('disponible' => false);
			return array('disponible' => true) + $this->Mdivisioncuenta->leer((int)$this->input->get('id_mesa'));
		});
	}

	public function guardarDivision()
	{
		$this->ejecutar(function () {
			$b = $this->cuerpoJson();
			$this->cajaRequerida();
			$this->load->model('Mdivisioncuenta');
			if (!$this->Mdivisioncuenta->disponible()) throw new Exception('Falta actualizar la base de datos', 409);
			return $this->Mdivisioncuenta->guardar($this->idUsuario(), $b);
		}, true);
	}

	public function borrarDivision()
	{
		$this->ejecutar(function () {
			$b = $this->cuerpoJson();
			$this->cajaRequerida();
			$this->load->model('Mdivisioncuenta');
			if (!$this->Mdivisioncuenta->disponible()) return array('ok' => true);
			return $this->Mdivisioncuenta->borrar((int)($b['id_cotizacion'] ?? 0));
		}, true);
	}

	/** Opciones de la mesa > Cambiar garzón: usuarios que pueden atenderla. */
	public function garzones()
	{
		$this->ejecutar(function () {
			$this->cajaRequerida();
			return array('garzones' => $this->Mcaja->garzones());
		});
	}

	/** Cambia quién atiende la mesa (queda en la auditoría de caja). */
	public function cambiarGarzon()
	{
		$this->ejecutar(function () {
			$b = $this->cuerpoJson();
			$caja = $this->cajaRequerida();
			$r = $this->Mcaja->cambiarGarzonMesa($b);
			$this->load->model('Mventasespera');
			$this->Mventasespera->garzonCambiado($caja, $this->idUsuario(), $r);
			return $r;
		}, true);
	}

	public function anularLineaMesa()
	{
		$this->ejecutar(function () {
			$b = $this->cuerpoJson();
			$r = $this->Mcaja->anularLineaMesa($this->cajaRequerida(), $this->idUsuario(), $this->idRol(), $b);
			$r['comanda_id'] = $this->guardarComanda($r['anuladas'] ?? array(), array('id_mesa' => (int)($b['id_mesa'] ?? 0),
				'anulacion' => true, 'motivo' => (string)($b['motivo'] ?? '')));
			return $r;
		}, true);
	}

	public function anularPedidoMesa()
	{
		$this->ejecutar(function () {
			$b = $this->cuerpoJson();
			$r = $this->Mcaja->anularPedidoMesa($this->cajaRequerida(), $this->idUsuario(), $this->idRol(), $b);
			$r['comanda_id'] = $this->guardarComanda($r['anuladas'] ?? array(), array('id_mesa' => (int)($b['id_mesa'] ?? 0),
				'anulacion' => true, 'motivo' => (string)($b['motivo'] ?? '')));
			return $r;
		}, true);
	}

	public function moverMesa()
	{
		$this->ejecutar(function () {
			$b = $this->cuerpoJson();
			$r = $this->Mcaja->moverMesa($this->cajaRequerida(), $this->idUsuario(), $b);
			$r['comanda_id'] = $this->guardarComanda($r['movidas'] ?? array(), array('id_mesa' => (int)($b['id_mesa_destino'] ?? 0),
				'traslado_desde' => (int)($b['id_mesa'] ?? 0)));
			return $r;
		}, true);
	}

	/** Volver a enviar a cocina lo ya pedido en la mesa (todo, o las líneas indicadas). */
	public function reenviarComanda()
	{
		$this->ejecutar(function () {
			$this->cajaRequerida();
			$b = $this->cuerpoJson();
			$idMesa = (int)($b['id_mesa'] ?? 0);
			$ids = isset($b['ids_detalle']) && is_array($b['ids_detalle']) ? $b['ids_detalle'] : array();
			$lineas = $this->Mcaja->comandaReenvio($idMesa, $ids);
			if (!$lineas) throw new Exception('No hay productos enviados para volver a mandar', 400);
			return array('comanda_id' => $this->guardarComanda($lineas, array('id_mesa' => $idMesa, 'reenvio' => true)));
		}, true);
	}

	// ---------------- comandas ----------------
	// La comanda la arma el servidor en la misma acción que graba el pedido (venta, envío a mesa, anulación, cambio de
	// mesa, reenvío) y queda en la sesión; la pantalla solo recibe su número. Así no se puede mandar a cocina un pedido
	// que no está registrado, ni cambiar productos o cantidades desde el navegador.

	const SESION_COMANDAS = 'caja_comandas';

	/** Guarda la comanda y devuelve su número (null si no hay productos). */
	private function guardarComanda($lineas, $meta)
	{
		if (!is_array($lineas) || !$lineas) return null;
		$todas = $this->session->userdata(self::SESION_COMANDAS);
		$todas = is_array($todas) ? $todas : array();
		// Solo las recientes: las de más de 15 minutos o más allá de las últimas 30 se descartan
		$todas = array_filter($todas, function ($c) { return isset($c['t']) && $c['t'] > time() - 900; });
		$todas = array_slice($todas, -29, null, true);
		$id = bin2hex(random_bytes(8));
		$todas[$id] = array('t' => time(), 'lineas' => array_values($lineas)) + $meta;
		$this->session->set_userdata(self::SESION_COMANDAS, $todas);
		return $id;
	}

	/** La comanda guardada; $usar = true la quita (se imprime una sola vez). */
	private function comandaGuardada($id, $usar)
	{
		$todas = $this->session->userdata(self::SESION_COMANDAS);
		if (!is_array($todas) || !is_string($id) || !isset($todas[$id])) return null;
		$c = $todas[$id];
		if ($usar) {
			unset($todas[$id]);
			$this->session->set_userdata(self::SESION_COMANDAS, $todas);
		}
		return $c;
	}

	/** Precuenta de la mesa en ticket (impresora de la caja); ?descuento= muestra el descuento que se aplicará. */
	public function precuenta()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) redirect(base_url());
		if (!$this->Mcaja->tienePermiso($this->idRol())) show_error('Tu rol no tiene acceso a la Caja', 403, 'Sin acceso');
		// Dividir cuenta: precuenta de una parte (sel = "id_detalle:cantidad,...", la de un comensal) o de la mesa con su
		// división en partes iguales (partes, parte = cuál se destaca, inicio = partes ya cobradas)
		$sel = array();
		foreach (explode(',', (string)$this->input->get('sel')) as $par) {
			if (count($sel) >= 300) break;
			$x = explode(':', $par);
			if (count($x) === 2 && (int)$x[0] > 0 && is_numeric($x[1]) && (float)$x[1] > 0) $sel[] = array('id_detalle' => (int)$x[0], 'cantidad' => (float)$x[1]);
		}
		try {
			$d = $this->Mcaja->precuentaMesa((int)$this->input->get('id_mesa'), $sel ?: null, (int)$this->input->get('partes'));
		} catch (Exception $e) {
			show_error($e->getMessage(), 409, 'Precuenta');
		}
		if (!$d) show_error('La mesa no tiene un pedido abierto', 404);
		$d['descuento'] = max(0, (int)$this->input->get('descuento'));
		$d['papel'] = $this->papelTicket();
		$d['titulo'] = mb_substr(trim((string)$this->input->get('titulo')), 0, 60);
		$d['parte'] = max(0, (int)$this->input->get('parte'));
		$d['inicio'] = max(0, (int)$this->input->get('inicio'));
		$this->load->view('caja/precuenta.php', $d);
	}

	public function liberarMesa()
	{
		$this->ejecutar(function () {
			$this->cajaRequerida();
			return $this->Mcaja->liberarMesa($this->idUsuario(), $this->idRol(), $this->cuerpoJson());
		}, true);
	}

	private function cuerpoJson()
	{
		$body = json_decode($this->input->raw_input_stream, true);
		if (!is_array($body)) throw new Exception('Datos inválidos', 400);
		return $body;
	}

	public function ventas()
	{
		$this->ejecutar(function () {
			return array('ventas' => $this->Mcaja->ventasCaja($this->cajaRequerida()->id_caja));
		});
	}

	/** Opciones > Documentos: documentos tributarios (cod_sii > 10) de un día, para verlos y reimprimirlos. */
	public function documentosEmitidos()
	{
		$this->ejecutar(function () {
			$fecha = (string)$this->input->get('fecha');
			if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !checkdate((int)substr($fecha, 5, 2), (int)substr($fecha, 8, 2), (int)substr($fecha, 0, 4))) {
				$fecha = date('Y-m-d');
			}
			return array('fecha' => $fecha, 'documentos' => $this->Mcaja->documentosEmitidos($fecha));
		});
	}

	// ---------------- Ventas en espera y auditoría (10-11-2026, models/Mventasespera.php) ----------------

	/** Opciones > Ventas en espera: las que siguen sin resolver, de todas las cajas. disponible = false si falta la migración. */
	public function esperas()
	{
		$this->ejecutar(function () {
			$this->load->model('Mventasespera');
			if (!$this->Mventasespera->disponible()) return array('disponible' => false, 'esperas' => array());
			$caja = $this->Mcaja->cajaAbierta($this->idUsuario());
			return array('disponible' => true, 'esperas' => $this->Mventasespera->pendientes($caja ? (int)$caja->id_caja : 0));
		});
	}

	/** Opciones > Poner en espera: guarda la venta directa con un nombre. */
	public function ponerEnEspera()
	{
		$this->ejecutar(function () {
			$this->load->model('Mventasespera');
			return $this->Mventasespera->crear($this->cajaRequerida(), $this->idUsuario(), $this->cuerpoJson(), 'manual');
		}, true);
	}

	/** Saca una venta de la espera para cargarla en la venta (la pantalla exige que la venta actual esté vacía). */
	public function recuperarEspera()
	{
		$this->ejecutar(function () {
			$b = $this->cuerpoJson();
			$this->load->model('Mventasespera');
			return $this->Mventasespera->recuperar($this->cajaRequerida(), $this->idUsuario(), (int)($b['id'] ?? 0));
		}, true);
	}

	/** Anula una venta en espera con su justificación (y la clave de administrador si Configuraciones la pide para anular). */
	public function anularEspera()
	{
		$this->ejecutar(function () {
			$b = $this->cuerpoJson();
			$caja = $this->cajaRequerida();
			$this->Mcaja->autorizarAnulacionEspera($this->idUsuario(), $this->idRol(), $b['autorizacion'] ?? null);
			$this->load->model('Mventasespera');
			return $this->Mventasespera->anular($caja, $this->idUsuario(), (int)($b['id'] ?? 0), $b['motivo'] ?? '');
		}, true);
	}

	/**
	 * Salir de la Caja con una venta en curso: por el menú (confirmado por el cajero) o al recargar / cerrar la página
	 * (lo manda el navegador al salir). La venta directa pasa a espera y lo no enviado de una mesa queda como omitido.
	 * Acepta JSON o el campo "datos" (navegadores que solo pueden avisar con sendBeacon).
	 */
	public function registrarSalida()
	{
		$this->ejecutar(function () {
			$b = json_decode($this->input->raw_input_stream, true);
			if (!is_array($b)) $b = json_decode((string)$this->input->post('datos'), true);
			if (!is_array($b)) throw new Exception('Datos inválidos', 400);
			// Lo que se mandó al cerrar la página debe ser de esta misma empresa (pudo cambiarse en otra pestaña)
			$huella = md5($this->session->userdata('nombre_conexion') . '|' . $this->session->userdata('db_server') . '|' . $this->session->userdata('bd_name'));
			if (!isset($b['empresa']) || !is_string($b['empresa']) || !hash_equals($huella, $b['empresa'])) {
				throw new Exception('La venta pendiente es de otra empresa', 409);
			}
			$this->load->model('Mventasespera');
			return $this->Mventasespera->salida($this->cajaRequerida(), $this->idUsuario(), $b);
		}, true);
	}

	/** Eventos de auditoría que informa la pantalla: venta vaciada y productos de mesa no enviados que se descartan. */
	public function auditar()
	{
		$this->ejecutar(function () {
			$b = $this->cuerpoJson();
			$this->load->model('Mventasespera');
			return $this->Mventasespera->eventoPantalla($this->cajaRequerida(), $this->idUsuario(), (string)($b['evento'] ?? ''), $b);
		}, true);
	}

	public function anular()
	{
		$this->ejecutar(function () {
			$a = $this->Mcaja->anularVenta($this->cajaRequerida(), $this->idUsuario(), $this->idRol(),
				(int)$this->input->post('id_venta'), $this->input->post('motivo'), $this->input->post('autorizacion'));
			$cfg = $a['config'];
			unset($a['config']);
			$res = array('ok' => true);
			// Configuraciones > Imprimir comprobantes de anulación
			if ($cfg['imprimir_anulacion']) {
				$this->load->library('Ticket_impresion');
				$res['impresion'] = $this->enviarAImpresora($this->Mcaja->destinoCaja(), $this->ticket_impresion->anulacion($a, $this->papelTicket()));
			}
			// Configuraciones > Informes por correo > Informar anulaciones
			if ($cfg['correo_anulaciones']) {
				$m = '$' . number_format($a['total'], 0, ',', '.');
				$e = function ($t) { return htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8'); };
				$html = '<h3>Venta anulada</h3><table cellpadding="4">'
					. '<tr><td><b>Documento</b></td><td>' . $e($a['documento']) . ' N° ' . (int)$a['folio'] . '</td></tr>'
					. '<tr><td><b>Monto</b></td><td>' . $m . '</td></tr>'
					. '<tr><td><b>Fecha</b></td><td>' . $e($a['fecha']) . '</td></tr>'
					. '<tr><td><b>Anulada por</b></td><td>' . $e($a['usuario']) . '</td></tr>'
					. '<tr><td><b>Caja</b></td><td>N° ' . (int)$a['id_caja'] . '</td></tr>'
					. '<tr><td><b>Motivo</b></td><td>' . $e($a['motivo']) . '</td></tr></table>';
				list($ok, $detalle) = $this->correo('Anulación: ' . $a['documento'] . ' N° ' . (int)$a['folio'] . ' (' . $m . ')', $html);
				$res['correo'] = array('ok' => $ok, 'detalle' => $detalle);
			}
			return $res;
		}, true);
	}

	// ---------------- Créditos de clientes ----------------

	/** Clientes con deuda de crédito (pestaña Créditos). */
	public function clientesConDeuda()
	{
		$this->ejecutar(function () {
			return array('clientes' => $this->Mcaja->clientesConDeuda());
		});
	}

	/** Ventas a crédito pendientes del cliente, con su detalle, y sus últimos pagos. */
	public function creditosCliente()
	{
		$this->ejecutar(function () {
			return $this->Mcaja->creditosCliente((int)$this->input->get_post('id_cliente'));
		});
	}

	/** Registra el pago de las ventas elegidas e imprime el comprobante en la impresora de la caja. */
	public function pagarCredito()
	{
		$this->ejecutar(function () {
			$c = $this->Mcaja->registrarPagoCredito($this->cajaRequerida(), $this->idUsuario(), array(
				'id_cliente' => $this->input->post('id_cliente'),
				'id_fpago' => $this->input->post('id_fpago'),
				'ids' => (array)$this->input->post('ids'),
				'paga_con' => (int)$this->input->post('paga_con'),
				'observacion' => $this->input->post('observacion'),
			));
			$this->load->library('Ticket_impresion');
			$imp = $this->enviarAImpresora($this->Mcaja->destinoCaja(), $this->ticket_impresion->pagoCredito($c, $this->papelTicket()));
			unset($c['empresa']);
			return array('ok' => true, 'pago' => $c, 'impresion' => $imp);
		}, true);
	}

	/** Reimprime el comprobante de un pago de crédito. */
	public function reimprimirPagoCredito()
	{
		$this->ejecutar(function () {
			$c = $this->Mcaja->comprobantePagoCredito((int)$this->input->post('id_pago'));
			$this->load->library('Ticket_impresion');
			return $this->enviarAImpresora($this->Mcaja->destinoCaja(), $this->ticket_impresion->pagoCredito($c, $this->papelTicket()));
		}, true);
	}

	/** Abre la gaveta de dinero (pulso a la impresora de la caja). Puede pedir clave de administrador (Configuraciones). */
	public function abrirGaveta()
	{
		$this->ejecutar(function () {
			$caja = $this->cajaRequerida();
			$this->Mcaja->autorizarGaveta($this->idUsuario(), $this->idRol(), $this->input->post('autorizacion'));
			$this->load->library('Ticket_impresion');
			$r = $this->enviarAImpresora($this->Mcaja->destinoCaja(), Ticket_impresion::gaveta());
			if (!$r['ok']) throw new Exception('No se pudo abrir la gaveta: ' . $r['detalle'], 409);
			// Auditoría de caja: gaveta abierta sin venta (si falla, no afecta la apertura)
			$this->load->model('Mventasespera');
			$this->Mventasespera->gaveta($caja, $this->idUsuario());
			return $r;
		}, true);
	}

	/** Cierre de caja / informe X a la impresora del cierre (Configuraciones > Impresora para cierre de caja). */
	public function imprimirCierre()
	{
		$this->ejecutar(function () {
			$this->validarCajaPropia((int)$this->input->post('id_caja'), false);
			$datos = $this->Mcaja->datosCierre((int)$this->input->post('id_caja'));
			if (!$datos) throw new Exception('La caja no existe', 404);
			$this->load->library('Ticket_impresion');
			return $this->enviarAImpresora($this->Mcaja->destinoCierre(), $this->ticket_impresion->cierre($datos, $this->papelTicket()));
		}, true);
	}

	/**
	 * Manda bytes ESC/POS a un destino de impresión: por el agente de impresión si está activo, si no directo desde el
	 * servidor. Devuelve {ok, navegador, detalle}; navegador = true si hay que imprimir desde la pantalla.
	 */
	private function enviarAImpresora($destino, $bytes)
	{
		if (!$destino) return array('ok' => false, 'navegador' => true, 'detalle' => 'No hay impresora de caja en Configuraciones');
		$this->load->library('Comanda_impresion');
		$this->load->model('Mcola_impresion');
		$this->Mcola_impresion->conectar(null, false);
		if ($this->Mcola_impresion->hayAgenteActivo()) {
			$ipOk = Comanda_impresion::ipPermitida(trim((string)$destino['ip']), $destino['puerto']);
			$this->Mcola_impresion->encolar($destino['nombre'], Mcola_impresion::nombreImpresora($destino['ruta_windows']),
				$ipOk ? trim((string)$destino['ip']) : '', $ipOk ? (int)$destino['puerto'] : 0, $bytes);
			return array('ok' => true, 'navegador' => false, 'detalle' => 'en cola para el agente (' . $destino['nombre'] . ')');
		}
		list($ok, $detalle) = $this->comanda_impresion->enviar($destino, $bytes);
		return array('ok' => $ok, 'navegador' => !$ok, 'detalle' => $detalle);
	}

	/** Correo a los destinatarios de Configuraciones > Informes por correo. Devuelve [ok, detalle]. */
	private function correo($asunto, $html)
	{
		$this->load->helper('tenant');
		$this->load->library('Correo_resto');
		return $this->correo_resto->enviar($this, get_tenant_db($this), $asunto, $html);
	}

	/** Reintenta emitir ante el SII un documento electrónico pendiente. */
	public function reenviarDte()
	{
		$this->ejecutar(function () {
			$this->cajaRequerida();
			return $this->Mcaja->reenviarDte((int)$this->input->post('id_venta'));
		}, true);
	}

	/** Venta a devolver: por id_venta o por documento + folio (de cualquier caja). */
	public function devolucionVenta()
	{
		$this->ejecutar(function () {
			$this->cajaRequerida();
			return $this->Mcaja->ventaParaDevolucion((int)$this->input->get('id_venta'),
				(int)$this->input->get('id_tdocumento'), (int)$this->input->get('folio'));
		});
	}

	public function devolver()
	{
		$this->ejecutar(function () {
			$body = json_decode($this->input->raw_input_stream, true);
			if (!is_array($body)) throw new Exception('Datos de devolución inválidos', 400);
			return $this->Mcaja->registrarDevolucion($this->cajaRequerida(), $this->idUsuario(), $this->idRol(), $body);
		}, true);
	}

	public function movimientos()
	{
		$this->ejecutar(function () {
			return array('movimientos' => $this->Mcaja->movimientosCaja($this->cajaRequerida()->id_caja));
		});
	}

	public function movimiento()
	{
		$this->ejecutar(function () {
			$this->Mcaja->registrarMovimiento($this->cajaRequerida(), $this->input->post('tipo'),
				$this->input->post('monto'), $this->input->post('observacion'), !$this->esGerencia());
			return array('ok' => true);
		}, true);
	}

	public function cerrar()
	{
		$this->ejecutar(function () {
			$caja = $this->cajaRequerida();
			// No se cierra con ventas en espera pendientes (las mesas con pedido sí: siguen abiertas para el próximo turno)
			$this->load->model('Mventasespera');
			$pendientes = $this->Mventasespera->bloqueanCierre($caja->id_caja);
			if ($pendientes) {
				throw new Exception('No se puede cerrar la caja con ventas en espera pendientes ('
					. implode(', ', array_map(function ($e) { return $e->nombre; }, $pendientes))
					. '). Cárgalas y cóbralas, o anúlalas en Opciones > Ventas en espera.', 409);
			}
			$declarado = array();
			foreach ((array)$this->input->post('declarado') as $idFpago => $monto) {
				$declarado[(int)$idFpago] = (int)$monto;
			}
			$fondoSiguiente = $this->input->post('fondo_siguiente');
			// Inventario de insumos marcados "al cierre de turno": {"idInsumo:idBodega": cantidad contada}
			$r = $this->Mcaja->cerrarCaja($caja, $this->idUsuario(),
				(int)$this->input->post('efectivo_contado'),
				$fondoSiguiente === null || $fondoSiguiente === '' ? null : (int)$fondoSiguiente, $declarado,
				(array)$this->input->post('inventario'));
			// Configuraciones > Informes por correo > Enviar cierre de caja por correo
			$cfg = $this->Mcaja->opciones();
			if ($cfg['correo_cierre']) {
				$datos = $this->Mcaja->datosCierre($r['id_caja']);
				$datos['papel'] = 80;
				$datos['correo'] = true;
				$html = $this->load->view('caja/cierre_ticket.php', $datos, true);
				list($ok, $detalle) = $this->correo('Cierre de caja N° ' . $r['id_caja'] . ' - ' . date('d/m/Y H:i'), $html);
				$r['correo'] = array('ok' => $ok, 'detalle' => $detalle);
			}
			return $r;
		}, true);
	}

	/** Insumos con inventario al cierre de turno y su stock actual (para el cuadro previo al cierre). */
	public function inventarioCierre()
	{
		$this->ejecutar(function () {
			$this->cajaRequerida();
			return array('insumos' => $this->Mcaja->insumosInventarioCierre());
		});
	}

	/** Ancho del papel de la impresora de esta caja (POS80 / POS58); lo elige cada computador. */
	private function papelTicket()
	{
		$papel = $this->input->get('papel') ?: $this->input->cookie('caja_papel');
		// Si este computador no eligió el papel, el de Configuraciones > Impresión de documentos > Formato (58 u 80)
		if (!$papel) $papel = $this->Mcaja->opciones()['formato_papel'];
		return (int)$papel === 58 ? 58 : 80;
	}

	/** Ticket 80mm o 58mm listo para imprimir. */
	public function ticket($idVenta = 0)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		$this->accesoVista(function () use ($idVenta) { $this->validarVentaPropia((int)$idVenta); });
		$doc = $this->Mcaja->documento((int)$idVenta);
		if (!$doc) show_404();
		$this->load->model('Mdte');
		$doc['timbre'] = $doc['emitido'] ? $this->Mdte->timbre($doc['ted']) : null;
		$doc['papel'] = $this->papelTicket();
		$this->load->view('caja/ticket.php', $doc);
	}

	/**
	 * Líneas de la comanda enviadas por la Caja (campo "datos", JSON), ya saneadas.
	 * $porProducto = true (impresión local): también en venta directa cada producto va a SU destino de impresión.
	 */
	private function datosComanda($usar, $porProducto = false)
	{
		$id = $this->input->post('comanda_id');
		if ($id === null) $id = $this->input->get('comanda_id');
		$datos = $this->comandaGuardada((string)$id, $usar);
		if (!is_array($datos) || empty($datos['lineas']) || !is_array($datos['lineas'])) return null;
		// Sin caracteres de control: la nota la escribe el garzón y un ESC podría mandar comandos a la impresora (abrir cajón, cortar)
		$texto = function ($v, $max) { return mb_substr(trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string)$v)), 0, $max); };
		$lineas = array();
		foreach ($datos['lineas'] as $l) {
			if (!is_array($l) || empty($l['id_producto'])) continue;
			$agregados = array();
			foreach ((isset($l['agregados']) && is_array($l['agregados'])) ? $l['agregados'] : array() as $a) {
				$a = $texto($a, 80);
				if ($a !== '') $agregados[] = $a;
			}
			$lineas[] = array(
				'id_producto' => (int)$l['id_producto'],
				'nombre' => $texto(isset($l['nombre']) ? $l['nombre'] : '', 120),
				'cantidad' => max(0, (float)(isset($l['cantidad']) ? $l['cantidad'] : 0)),
				'agregados' => $agregados,
				'nota' => $texto(isset($l['nota']) ? $l['nota'] : '', 200),
			);
		}
		// Agrupa por destino de impresión; los productos sin destino (o "sin impresión") no salen en ninguna comanda
		$destinos = $this->Mcaja->destinosComanda(array_column($lineas, 'id_producto'));
		// Venta directa: la comanda sale completa en la impresora de la caja (Configuraciones), no en cocina/barra
		$caja = !$porProducto && !empty($datos['directa']) && empty($datos['id_mesa']) ? $this->Mcaja->destinoCaja() : null;
		$grupos = array();
		foreach ($lineas as $l) {
			if (!isset($destinos[$l['id_producto']])) continue;
			$d = $caja ? $caja : $destinos[$l['id_producto']];
			if (!isset($grupos[$d['id']])) $grupos[$d['id']] = array('nombre' => $d['nombre'], 'destino' => $d, 'lineas' => array());
			$grupos[$d['id']]['lineas'][] = $l;
		}
		$idVenta = isset($datos['id_venta']) ? (int)$datos['id_venta'] : 0;
		$doc = $idVenta ? $this->Mcaja->documento($idVenta) : null;
		// Quien retira (venta directa): de la venta; si la base aún no tiene la columna, el de la comanda guardada
		if ($doc && empty($doc['venta']->nombre_retira) && !empty($datos['retira'])) $doc['venta']->nombre_retira = $texto($datos['retira'], 60);
		return array(
			'grupos' => $grupos,
			'venta' => $doc ? $doc['venta'] : null,
			'observacion' => $texto(isset($datos['observacion']) ? $datos['observacion'] : '', 200),
			'mesa' => !empty($datos['id_mesa']) ? $this->Mcaja->etiquetaMesa((int)$datos['id_mesa']) : '',
			'aviso' => $this->avisoComanda($datos),
		);
	}

	/** Comanda especial: anulación, cambio de mesa o reenvío de algo ya enviado. null = comanda normal. */
	private function avisoComanda($datos)
	{
		if (!empty($datos['anulacion'])) {
			return array('tipo' => 'anulacion', 'motivo' => mb_substr(trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string)(isset($datos['motivo']) ? $datos['motivo'] : ''))), 0, 100));
		}
		if (!empty($datos['traslado_desde'])) return array('tipo' => 'traslado', 'desde' => $this->Mcaja->etiquetaMesa((int)$datos['traslado_desde']));
		if (!empty($datos['reenvio'])) return array('tipo' => 'reenvio');
		return null;
	}

	/**
	 * Comanda en pantalla (vista previa / respaldo): una hoja por destino de impresión.
	 * La impresión normal es imprimirComanda, que va directo a la impresora de cada destino.
	 */
	public function comanda()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		$c = $this->datosComanda(false);
		if (!$c) show_404();
		$c['papel'] = $this->papelTicket();
		$this->load->view('caja/comanda.php', $c);
	}

	/**
	 * Envía la comanda de cada destino de impresión a SU impresora (ruta de Windows o IP y puerto configurados
	 * en Destino de impresión). La boleta/factura sigue saliendo por la impresora de la caja.
	 * Responde por destino: [{destino, ok, detalle}].
	 */
	public function imprimirComanda()
	{
		$this->ejecutar(function () {
			$c = $this->datosComanda(false);
			if (!$c) throw new Exception('La comanda ya se imprimió o venció: vuelve a enviarla desde la mesa', 400);
			$this->load->library('Comanda_impresion');
			$this->load->model('Mcola_impresion');
			$this->Mcola_impresion->conectar(null, false);
			// Con un agente de Windows conectado, la comanda va a la cola y la imprime el equipo que tiene la impresora;
			// si no, el servidor intenta enviarla directo (ruta de Windows o IP y puerto)
			$conAgente = $this->Mcola_impresion->hayAgenteActivo();
			$res = array();
			foreach ($c['grupos'] as $g) {
				$bytes = $this->comanda_impresion->armar($g, $c['venta'], $c['observacion'], $c['mesa'], $c['aviso']);
				if ($conAgente) {
					$d = $g['destino'];
					$ipOk = Comanda_impresion::ipPermitida(trim((string)$d['ip']), $d['puerto']);
					$this->Mcola_impresion->encolar($g['nombre'], Mcola_impresion::nombreImpresora($d['ruta_windows']), $ipOk ? trim((string)$d['ip']) : '', $ipOk ? (int)$d['puerto'] : 0, $bytes);
					$ok = true;
					$detalle = 'en cola para el agente';
				} else {
					list($ok, $detalle) = $this->comanda_impresion->enviar($g['destino'], $bytes);
				}
				$res[] = array('destino' => $g['nombre'], 'ok' => $ok, 'detalle' => $detalle);
			}
			// Impresa en todos sus destinos: se descarta (no se puede volver a mandar la misma). Si alguno falló
			// queda guardada para reintentar.
			if (!in_array(false, array_column($res, 'ok'), true)) {
				$this->comandaGuardada((string)$this->input->post('comanda_id'), true);
			}
			return array('comandas' => $res);
		}, true);
	}

	/**
	 * Comanda para imprimir en el local por el agente de impresión del equipo (12-11-2026, como mipdv_web): las
	 * impresoras están en el local y el sistema en un servidor, así que la pantalla le manda cada comanda al agente
	 * (localhost:5050). Una comanda por destino de impresión de los productos (cocina, barra...), también en venta
	 * directa, con todo lo de esa venta o mesa junto. Devuelve {comandas: [{destino, nombre, operaciones}]}; la
	 * comanda queda guardada hasta que la pantalla avise que salió completa (comandaImpresa).
	 */
	public function comandaLocal()
	{
		$this->ejecutar(function () {
			$c = $this->datosComanda(false, true);
			if (!$c) throw new Exception('La comanda ya se imprimió o venció: vuelve a enviarla desde la mesa', 400);
			$this->load->library('Comanda_local');
			$res = array();
			foreach ($c['grupos'] as $g) {
				$d = $g['destino'];
				$res[] = array(
					'nombre' => $g['nombre'],
					'destino' => array('nombre' => $d['nombre'], 'ip' => $d['ip'], 'puerto' => $d['puerto'], 'ruta_windows' => $d['ruta_windows']),
					'operaciones' => $this->comanda_local->operaciones($g, $c['venta'], $c['observacion'], $c['mesa'], $c['aviso']),
				);
			}
			return array('comandas' => $res);
		}, true);
	}

	/** La comanda salió en todos sus destinos por el agente local: se descarta (no se puede volver a mandar la misma). */
	public function comandaImpresa()
	{
		$this->ejecutar(function () {
			$this->comandaGuardada((string)$this->input->post('comanda_id'), true);
			return array('ok' => true);
		}, true);
	}

	/**
	 * Boleta / factura / comprobante directo a la impresora de la caja (Configuraciones > destino de impresión de la caja),
	 * por el agente de impresión o enviada por el servidor, igual que las comandas. Responde {ok, detalle, navegador}:
	 * navegador = true cuando no hay cómo mandarla a la impresora y la pantalla debe imprimirla desde el navegador.
	 */
	public function imprimirTicket()
	{
		$this->ejecutar(function () {
			$idVenta = (int)$this->input->post('id_venta');
			if ($idVenta) $this->validarVentaPropia($idVenta);
			$doc = $idVenta ? $this->Mcaja->documento($idVenta) : null;
			if (!$doc) throw new Exception('La venta no existe', 404);
			$this->load->model('Mdte');
			$this->load->library('Ticket_impresion');
			$papel = $this->papelTicket();
			$timbre = $doc['emitido'] ? $this->Mdte->timbreMatriz($doc['ted'], Ticket_impresion::puntos($papel)) : null;
			$bytes = $this->ticket_impresion->armar($doc, $papel, $timbre);
			// Configuraciones > Impresión de documentos > Copias
			$bytes = str_repeat($bytes, $this->Mcaja->opciones()['copias_documento']);
			return $this->enviarAImpresora($this->Mcaja->destinoDocumentos(), $bytes);
		}, true);
	}

	/**
	 * Documento para imprimir en el equipo de la caja por el agente de impresión local (12-11-2026, como mipdv_web), con
	 * Configuraciones > Imprimir documentos en el equipo de la caja. Destino: Configuraciones > Impresora para cierre de
	 * caja ("La de la caja" = la impresora de la caja). Devuelve {destino, operaciones}: las operaciones de PrinterJob
	 * (Ticket_local, mismo contenido que imprimirTicket) con las copias de Configuraciones; la pantalla se las manda al
	 * agente (assets/js/impresion-local/impresionLocal.js) y, si no puede, imprime como antes (imprimirTicket).
	 */
	public function ticketLocal()
	{
		$this->ejecutar(function () {
			$idVenta = (int)$this->input->post('id_venta');
			if ($idVenta) $this->validarVentaPropia($idVenta);
			$doc = $idVenta ? $this->Mcaja->documento($idVenta) : null;
			if (!$doc) throw new Exception('La venta no existe', 404);
			$destino = $this->Mcaja->destinoCierre();
			if (!$destino) throw new Exception('No hay impresora elegida en Configuraciones > Impresora para cierre de caja', 409);
			$this->load->library('Ticket_local');
			$ops = $this->ticket_local->operaciones($doc, $this->papelTicket());
			$todas = array();
			for ($i = 0; $i < $this->Mcaja->opciones()['copias_documento']; $i++) $todas = array_merge($todas, $ops);
			return array(
				'destino' => array('nombre' => $destino['nombre'], 'ip' => $destino['ip'], 'puerto' => $destino['puerto'], 'ruta_windows' => $destino['ruta_windows']),
				'operaciones' => $todas,
			);
		}, true);
	}

	/** Cierre (caja cerrada) o informe X (caja abierta) en ticket 80mm o 58mm. */
	public function cierreTicket($idCaja = 0)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		$this->accesoVista(function () use ($idCaja) { $this->validarCajaPropia((int)$idCaja, false); });
		$datos = $this->Mcaja->datosCierre((int)$idCaja);
		if (!$datos) show_404();
		$datos['papel'] = $this->papelTicket();
		$this->load->view('caja/cierre_ticket.php', $datos);
	}

	/** Documento tamaño carta en PDF. */
	public function pdf($idVenta = 0)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		$this->accesoVista(function () use ($idVenta) { $this->validarVentaPropia((int)$idVenta); });
		$doc = $this->Mcaja->documento((int)$idVenta);
		if (!$doc) show_404();
		$v = $doc['venta'];
		$e = $doc['empresa'];
		$t = function ($s) {
			return mb_convert_encoding((string)((string)$s), 'ISO-8859-1', 'UTF-8');
		};
		$m = function ($n) {
			return '$' . number_format((float)$n, 0, ',', '.');
		};

		require('fpdf/fpdf.php');
		$pdf = new FPDF('P', 'mm', 'Letter');
		$pdf->SetTitle($t($v->nombre_tdocumento . ' ' . $v->folio));
		// Con crédito de confianza va también la copia cedible, en otra hoja
		foreach (!empty($doc['cedible']) ? array('', 'CEDIBLE') : array('') as $copia) {
			$pdf->AddPage();
			$pdf->SetMargins(15, 15, 15);

			// Empresa (izquierda) y recuadro del documento (derecha)
			$razon = $t($e ? ($e->razon_social ?: $e->nombre_fantasia) : '');
			// Nombres largos: letra más chica para no chocar con el recuadro del documento
			$tam = 13;
			do {
				$pdf->SetFont('Arial', 'B', $tam);
			} while ($pdf->GetStringWidth($razon) > 115 && --$tam > 8);
			$pdf->SetXY(15, 15);
			$pdf->Cell(110, 7, $razon, 0, 2);
			$pdf->SetFont('Arial', '', 9);
			if ($e) {
				if ($e->giro) $pdf->MultiCell(110, 4.5, $t('Giro: ' . $e->giro));
				$pdf->SetX(15);
				$pdf->Cell(110, 4.5, $t(trim($e->direccion_empresa . (!empty($e->comuna) ? ', ' . $e->comuna : ''))), 0, 2);
				if ($e->telefono) $pdf->Cell(110, 4.5, $t('Fono: ' . $e->telefono), 0, 2);
			}
			$pdf->SetDrawColor(200, 0, 0);
			$pdf->SetTextColor(200, 0, 0);
			$pdf->SetLineWidth(0.6);
			$pdf->Rect(135, 15, 66, 28);
			$pdf->SetFont('Arial', 'B', 11);
			$pdf->SetXY(135, 18);
			if ($e) $pdf->Cell(66, 6, $t('R.U.T.: ' . number_format((float)$e->rut_empresa, 0, ',', '.') . '-' . $e->dv_empresa), 0, 2, 'C');
			$pdf->MultiCell(66, 6, $t(mb_strtoupper($v->nombre_tdocumento)), 0, 'C');
			$pdf->SetX(135);
			$pdf->Cell(66, 6, $t('N° ' . $v->folio), 0, 2, 'C');
			// Oficina del SII bajo el recuadro (exigido en la representación impresa del DTE)
			if ($doc['sii'] && $doc['sii']['oficina']) {
				$pdf->SetXY(135, 44);
				$pdf->SetFont('Arial', 'B', 9);
				$pdf->Cell(66, 5, $t($doc['sii']['oficina']), 0, 2, 'C');
			}
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetDrawColor(0, 0, 0);
			$pdf->SetLineWidth(0.2);

			// Cliente
			$pdf->SetXY(15, 50);
			$pdf->SetFont('Arial', '', 9);
			$rut = $v->rut ? number_format((float)$v->rut, 0, ',', '.') . '-' . $v->dv : '';
			$filas = array(
				array('Fecha', $v->fecha_texto, 'Cajero', $v->nombre_usuario),
			);
			if (!empty($v->nombre_retira)) $filas[] = array('Retira', $v->nombre_retira, '', '');
			if ($rut) {
				$filas[] = array('Cliente', $v->nombre_cliente, 'RUT', $rut);
				$filas[] = array('Giro', $v->giro, 'Dirección', $v->direccion);
				if ($v->comuna) $filas[] = array('Comuna', $v->comuna, '', '');
				if (!empty($v->solicitante)) $filas[] = array('Solicita', $v->solicitante, '', '');
			}
			if ($doc['nc']) $filas[] = array('Ref.', $doc['referencia'], '', '');
			foreach ($filas as $f) {
				$pdf->SetFont('Arial', 'B', 9);
				$pdf->Cell(20, 6, $t($f[0]), 'LTB');
				$pdf->SetFont('Arial', '', 9);
				$pdf->Cell(75, 6, $t(mb_substr((string)$f[1], 0, 45)), 'TB');
				$pdf->SetFont('Arial', 'B', 9);
				$pdf->Cell(20, 6, $t($f[2]), 'TB');
				$pdf->SetFont('Arial', '', 9);
				$pdf->Cell(71, 6, $t(mb_substr((string)$f[3], 0, 40)), 'TBR', 1);
			}
			// Motivo de la factura (glosa libre, hasta 200 caracteres): ocupa todo el ancho
			if ($rut && !empty($v->motivo_factura)) {
				$pdf->SetFont('Arial', 'B', 9);
				$pdf->Cell(20, 6, 'Motivo', 'LTB');
				$pdf->SetFont('Arial', '', 9);
				$pdf->MultiCell(166, 6, $t($v->motivo_factura), 'TBR');
			}

			// Detalle
			$pdf->Ln(4);
			$pdf->SetFont('Arial', 'B', 9);
			$pdf->SetFillColor(235, 235, 235);
			$pdf->Cell(20, 7, 'Cant.', 1, 0, 'C', true);
			$pdf->Cell(106, 7, $t('Descripción'), 1, 0, 'L', true);
			$pdf->Cell(30, 7, 'P. Unitario', 1, 0, 'R', true);
			$pdf->Cell(30, 7, 'Total', 1, 1, 'R', true);
			$pdf->SetFont('Arial', '', 9);
			foreach ($doc['detalle'] as $d) {
				$pdf->Cell(20, 6, rtrim(rtrim(number_format((float)$d->cantidad, 3, ',', ''), '0'), ','), 'LR', 0, 'C');
				$pdf->Cell(106, 6, $t(mb_substr($d->nombre, 0, 60) . ($d->exento ? ' (EXENTO)' : '')), 'LR');
				$pdf->Cell(30, 6, $m($d->precio_mostrar), 'LR', 0, 'R');
				$pdf->Cell(30, 6, $m($d->monto_mostrar), 'LR', 1, 'R');
			}
			$pdf->Cell(186, 0, '', 'T', 1);

			// Totales
			$pdf->Ln(3);
			$totales = array(array($v->en_neto ? 'Subtotal neto' : 'Subtotal', $v->subtotal_mostrar));
			if ((int)$v->descuento_mostrar > 0) $totales[] = array('Descuento', -$v->descuento_mostrar);
			$totales[] = array('Monto neto', $v->neto);
			$totales[] = array('IVA (19%)', $v->iva);
			if ($v->monto_exento > 0) $totales[] = array('Monto exento', $v->monto_exento);
			$totales[] = array('TOTAL', $v->total);
			$propina = isset($v->valor_propina) ? (int)$v->valor_propina : 0;
			if ($propina > 0) {
				$totales[] = array('Propina', $propina);
				$totales[] = array('Total c/propina', $v->total + $propina);
			}
			foreach ($totales as $i => $tt) {
				$ultimo = $tt[0] === 'TOTAL';
				$pdf->SetFont('Arial', $ultimo ? 'B' : '', $ultimo ? 11 : 9);
				$pdf->Cell(126, 6, '');
				$pdf->Cell(30, 6, $t($tt[0]), $ultimo ? 'LTB' : 0);
				$pdf->Cell(30, 6, $m($tt[1]), $ultimo ? 'RTB' : 0, 1, 'R');
			}

			// Pagos
			if ($doc['pagos']) {
				$pdf->Ln(2);
				$pdf->SetFont('Arial', 'B', 9);
				$pdf->Cell(186, 5, $t($doc['nc'] ? 'Devuelto en' : 'Forma de pago'), 0, 1);
				$pdf->SetFont('Arial', '', 9);
				foreach ($doc['pagos'] as $p) {
					$linea = $p->nombre_fpago . ': ' . $m($p->paga_con) . ((int)$p->vuelto > 0 ? '  (vuelto ' . $m($p->vuelto) . ')' : '');
					$pdf->Cell(186, 5, $t($linea), 0, 1);
				}
			}

			$pdf->Ln(4);
			$pdf->SetFont('Arial', 'I', 8);
			if ($doc['anulada']) {
				$pdf->SetTextColor(200, 0, 0);
				$pdf->MultiCell(186, 5, $t('DOCUMENTO ANULADO. ' . $v->mensaje_pedido), 0, 'C');
				$pdf->SetTextColor(0, 0, 0);
			} elseif ($v->mensaje_pedido) {
				$pdf->MultiCell(186, 5, $t('Observación: ' . $v->mensaje_pedido));
			}
			if ($doc['electronico'] && !$doc['emitido']) {
				$pdf->MultiCell(186, 5, $t('Documento pendiente de envío al SII.'), 0, 'C');
			} elseif ($doc['emitido']) {
				// Timbre electrónico (PDF417 del TED), abajo a la izquierda como en los DTE impresos
				$this->load->model('Mdte');
				$png = $this->Mdte->timbre($doc['ted'], 'png');
				if ($png) {
					$tmp = tempnam(sys_get_temp_dir(), 'ted') . '.png';
					file_put_contents($tmp, $png);
					$pdf->Ln(4);
					$y = $pdf->GetY();
					$pdf->Image($tmp, 15, $y, 70);
					@unlink($tmp);
					$pdf->SetXY(15, $y + 37);
					$pdf->SetFont('Arial', 'B', 8);
					$pdf->Cell(70, 4, $t('Timbre Electrónico SII'), 0, 2, 'C');
					$pdf->SetFont('Arial', '', 7);
					if ($doc['sii']['resolucion']) $pdf->Cell(70, 4, $t($doc['sii']['resolucion']), 0, 2, 'C');
					$pdf->Cell(70, 4, $t('Verifique documento: ' . $doc['sii']['verificacion']), 0, 2, 'C');
					foreach ($doc['sii']['publicidad'] as $i => $linea) {
						if ($i === 0) $pdf->Ln(2);
						$pdf->Cell(70, 4, $t($linea), 0, 2, 'C');
					}
				}
			}
			// Copia cedible: recuadro de acuse de recibo (Ley 19.983) y leyenda CEDIBLE
			if ($copia) {
				$yAcuse = max($pdf->GetY() + 6, 200);
				if ($yAcuse > 215) {
					$pdf->AddPage();
					$yAcuse = 20;
				}
				// El recuadro va completo al pie, sin salto de página automático
				$pdf->SetAutoPageBreak(false);
				$pdf->SetLineWidth(0.3);
				$pdf->Rect(100, $yAcuse, 101, 44);
				$pdf->SetXY(102, $yAcuse + 2);
				$pdf->SetFont('Arial', 'B', 9);
				$pdf->Cell(97, 5, $t('ACUSE DE RECIBO'), 0, 2, 'C');
				$pdf->SetFont('Arial', '', 8);
				foreach (array(array('Nombre:', 'R.U.T.:'), array('Fecha:', 'Recinto:'), array('Firma:', '')) as $par) {
					$pdf->SetX(102);
					$pdf->Cell(48, 7, $t($par[0]), 'B');
					$pdf->Cell(1, 7, '');
					$pdf->Cell(48, 7, $t($par[1]), $par[1] ? 'B' : 0, 2);
					$pdf->SetX(102);
				}
				$pdf->SetXY(102, $yAcuse + 29);
				$pdf->SetFont('Arial', '', 6);
				$pdf->MultiCell(97, 3, $t('El acuse de recibo que se declara en este acto, de acuerdo a lo dispuesto en la letra b) del Art. 4°, y la letra c) del Art. 5° de la Ley 19.983, acredita que la entrega de mercaderías o servicio(s) prestado(s) ha(n) sido recibido(s).'));
				$pdf->SetXY(100, $yAcuse + 46);
				$pdf->SetFont('Arial', 'B', 14);
				$pdf->Cell(101, 7, 'CEDIBLE', 0, 2, 'R');
				$pdf->SetAutoPageBreak(true, 20);
			}
		}

		$pdf->Output('I', $t($v->nombre_tdocumento . '_' . $v->folio . '.pdf'));
	}

	// ---------------------------------------------------------------

	private function idUsuario()
	{
		return (int)$this->session->userdata('id_usuario');
	}

	private function idRol()
	{
		return (int)$this->session->userdata('id_rol');
	}

	/** 14-11-2026: gerencia (rol 1) ve todas las cajas y lo esperado; los demás roles, solo lo suyo y con cierre ciego. */
	private function esGerencia()
	{
		return $this->idRol() === 1;
	}

	/**
	 * Cierre ciego (QA 14-11-2026): sin gerencia no se entrega nada que permita calcular el efectivo esperado
	 * (antes Caja/estado devolvía efectivo_esperado y lo cobrado en efectivo).
	 */
	private function resumenVisible($res)
	{
		if ($this->esGerencia() || !is_array($res)) return $res;
		foreach (array('efectivo_esperado', 'efectivo_ventas', 'propinas_efectivo', 'ventas') as $k) unset($res[$k]);
		if (!empty($res['por_forma_pago']) && is_array($res['por_forma_pago'])) {
			foreach ($res['por_forma_pago'] as $i => $f) {
				if (!empty($f['efectivo'])) { $res['por_forma_pago'][$i]['monto'] = null; $res['por_forma_pago'][$i]['propina'] = null; }
			}
		}
		$res['cierre_ciego'] = true;
		return $res;
	}

	/** Sin gerencia: solo cajas propias, y el informe X (caja abierta) no, porque mostraría lo esperado. */
	private function validarCajaPropia($idCaja, $permitirAbierta)
	{
		if ($this->esGerencia()) return;
		$c = $this->Mcaja->duenoCaja((int)$idCaja);
		if (!$c) throw new Exception('La caja no existe', 404);
		if ((int)$c->id_usuario !== $this->idUsuario()) throw new Exception('Esa caja es de otro usuario', 403);
		if (!$permitirAbierta && !$c->cerrada) {
			throw new Exception('Cierre ciego: el informe X de una caja abierta solo lo puede ver gerencia', 403);
		}
	}

	/** Sin gerencia: solo documentos de ventas hechas en una caja propia. */
	private function validarVentaPropia($idVenta)
	{
		if ($this->esGerencia()) return;
		$idCaja = $this->Mcaja->cajaDeVenta((int)$idVenta);
		if ($idCaja === null) throw new Exception('La venta no existe', 404);
		$this->validarCajaPropia($idCaja, true);
	}

	/** Para las páginas (ticket, PDF): sin permiso, página de error en vez de JSON. */
	private function accesoVista($validar)
	{
		try {
			if (!$this->Mcaja->tienePermiso($this->idRol())) throw new Exception('Tu rol no tiene acceso a la Caja', 403);
			$validar();
		} catch (Exception $e) {
			$code = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 403;
			show_error(html_escape($e->getMessage()), $code);
			exit;
		}
	}

	private function cajaRequerida()
	{
		$caja = $this->Mcaja->cajaAbierta($this->idUsuario());
		if (!$caja) throw new Exception('No tienes una caja abierta', 409);
		return $caja;
	}

	/** Ejecuta la acción y responde JSON; los errores salen como {error} con su código HTTP. */
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
			if (!$this->Mcaja->tienePermiso($this->idRol())) {
				throw new Exception('Tu rol no tiene acceso a la Caja', 403);
			}
			$this->output->set_output(json_encode($accion()));
		} catch (AutorizacionRequerida $e) {
			// La pantalla pide la clave de un administrador y reintenta
			$this->output->set_status_header(403)->set_output(json_encode(array(
				'error' => $e->getMessage(), 'autorizacion' => true, 'motivo' => $e->motivo)));
		} catch (Exception $e) {
			$code = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
			$this->output->set_status_header($code)->set_output(json_encode(array('error' => $e->getMessage())));
		}
		catch (Throwable $e) {
			// Errores de PHP (tipos, métodos): JSON igual, para que la Caja muestre el mensaje y no una página HTML
			log_message('error', 'Caja web: ' . $e->getMessage() . ' en ' . basename($e->getFile()) . ':' . $e->getLine());
			$this->output->set_status_header(500)->set_output(json_encode(array('error' => 'Error interno del servidor. Revisa el registro (log).')));
		}
	}
}
