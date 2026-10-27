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
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
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

	public function vender()
	{
		$this->ejecutar(function () {
			$body = json_decode($this->input->raw_input_stream, true);
			if (!is_array($body)) throw new Exception('Datos de venta inválidos', 400);
			return $this->Mcaja->registrarVenta($this->cajaRequerida(), $this->idUsuario(), $this->idRol(), $body);
		}, true);
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
			return $this->Mcaja->cerrarCaja($this->cajaRequerida(), $this->idUsuario(),
				(int)$this->input->post('efectivo_contado'),
				$fondoSiguiente === null || $fondoSiguiente === '' ? null : (int)$fondoSiguiente, $declarado);
		}, true);
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
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		$doc = $this->Mcaja->documento((int)$idVenta);
		if (!$doc) show_404();
		$this->load->model('Mdte');
		$doc['timbre'] = $doc['emitido'] ? $this->Mdte->timbre($doc['ted']) : null;
		$doc['papel'] = $this->papelTicket();
		$this->load->view('caja/ticket.php', $doc);
	}

	/** Cierre (caja cerrada) o informe X (caja abierta) en ticket 80mm o 58mm. */
	public function cierreTicket($idCaja = 0)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
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
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		$doc = $this->Mcaja->documento((int)$idVenta);
		if (!$doc) show_404();
		$v = $doc['venta'];
		$e = $doc['empresa'];
		$t = function ($s) {
			return utf8_decode((string)$s);
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
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
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
	}
}
