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
		$this->load->view('caja/js.php');
	}

	public function estado()
	{
		$this->ejecutar(function () {
			$caja = $this->Mcaja->cajaAbierta($this->idUsuario());
			if ($caja) return array('caja' => $this->Mcaja->resumenCaja($caja));
			return array('caja' => null, 'fondo_sugerido' => $this->Mcaja->fondoSugerido($this->idUsuario()));
		});
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
			$this->Mcaja->abrirCaja($this->idUsuario(), $fondo);
			return array('ok' => true);
		}, true);
	}

	public function clientes()
	{
		$this->ejecutar(function () {
			return array('clientes' => $this->Mcaja->buscarClientes($this->input->get('q')));
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
				'observacion' => $r['observacion']));
			unset($r['comanda'], $r['observacion']);
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

	public function enviarMesa()
	{
		$this->ejecutar(function () {
			$b = $this->cuerpoJson();
			$r = $this->Mcaja->enviarMesa($this->cajaRequerida(), $this->idUsuario(), $b, $this->idRol());
			$r['comanda_id'] = $this->guardarComanda($r['comanda'], array('id_mesa' => (int)($b['id_mesa'] ?? 0)));
			unset($r['comanda']);
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
		$d = $this->Mcaja->precuentaMesa((int)$this->input->get('id_mesa'));
		if (!$d) show_error('La mesa no tiene un pedido abierto', 404);
		$d['descuento'] = max(0, (int)$this->input->get('descuento'));
		$d['papel'] = $this->papelTicket();
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

	public function anular()
	{
		$this->ejecutar(function () {
			$this->Mcaja->anularVenta($this->cajaRequerida(), $this->idUsuario(), $this->idRol(),
				(int)$this->input->post('id_venta'), $this->input->post('motivo'), $this->input->post('autorizacion'));
			return array('ok' => true);
		}, true);
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
				$this->input->post('monto'), $this->input->post('observacion'));
			return array('ok' => true);
		}, true);
	}

	public function cerrar()
	{
		$this->ejecutar(function () {
			$declarado = array();
			foreach ((array)$this->input->post('declarado') as $idFpago => $monto) {
				$declarado[(int)$idFpago] = (int)$monto;
			}
			$fondoSiguiente = $this->input->post('fondo_siguiente');
			// Inventario de insumos marcados "al cierre de turno": {"idInsumo:idBodega": cantidad contada}
			return $this->Mcaja->cerrarCaja($this->cajaRequerida(), $this->idUsuario(),
				(int)$this->input->post('efectivo_contado'),
				$fondoSiguiente === null || $fondoSiguiente === '' ? null : (int)$fondoSiguiente, $declarado,
				(array)$this->input->post('inventario'));
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
		return (int)$papel === 58 ? 58 : 80;
	}

	/** Ticket 80mm o 58mm listo para imprimir. */
	public function ticket($idVenta = 0)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		$doc = $this->Mcaja->documento((int)$idVenta);
		if (!$doc) show_404();
		$this->load->model('Mdte');
		$doc['timbre'] = $doc['emitido'] ? $this->Mdte->timbre($doc['ted']) : null;
		$doc['papel'] = $this->papelTicket();
		$this->load->view('caja/ticket.php', $doc);
	}

	/** Líneas de la comanda enviadas por la Caja (campo "datos", JSON), ya saneadas. */
	private function datosComanda($usar)
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
		$caja = !empty($datos['directa']) && empty($datos['id_mesa']) ? $this->Mcaja->destinoCaja() : null;
		$grupos = array();
		foreach ($lineas as $l) {
			if (!isset($destinos[$l['id_producto']])) continue;
			$d = $caja ? $caja : $destinos[$l['id_producto']];
			if (!isset($grupos[$d['id']])) $grupos[$d['id']] = array('nombre' => $d['nombre'], 'destino' => $d, 'lineas' => array());
			$grupos[$d['id']]['lineas'][] = $l;
		}
		$idVenta = isset($datos['id_venta']) ? (int)$datos['id_venta'] : 0;
		$doc = $idVenta ? $this->Mcaja->documento($idVenta) : null;
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

	/** Cierre (caja cerrada) o informe X (caja abierta) en ticket 80mm o 58mm. */
	public function cierreTicket($idCaja = 0)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
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
			if ($rut) {
				$filas[] = array('Cliente', $v->nombre_cliente, 'RUT', $rut);
				$filas[] = array('Giro', $v->giro, 'Dirección', $v->direccion);
				if ($v->comuna) $filas[] = array('Comuna', $v->comuna, '', '');
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
