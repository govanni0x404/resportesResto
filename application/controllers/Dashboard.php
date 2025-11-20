<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dashboard extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model(array('Mdashboard', 'Mroles', 'Mconfiguraciones'));
	}

	public function index()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$data = array(
			'nombreUsuario' => $this->session->userdata('nombre_usuario'),
			 //'modulosDisponibles' => json_decode($this->Mroles->modulosDisponibles($this->session->userdata('id_rol')), true),
			//'obtenerDesglozeTodosLosMontos' => json_decode($this->Mconfiguraciones->obtenerDesglozeTodosLosMontos(), true),
			// 'obtenerDesglozeSoloMontoTotal' => json_decode($this->Mconfiguraciones->obtenerDesglozeSoloMontoTotal(), true)
			//'id_rol' => $this->Mroles->verificarSiEsAdmin()

		);
		$this->load->view('menuTop.php', $data);
		$this->load->view('dashboard.php', $data);
		$this->load->view('menuFooter.php');
		$this->load->view('js.php', $data);
	}

	public function empresa()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		echo json_encode($this->Mdashboard->empresa());
	}

	public function totalVentas()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id_caja = $this->input->post('id_caja');
		//$fecha = $this->input->post('fecha');
		$desde = $this->input->post('desde');
		$hasta = $this->input->post('hasta');

		echo json_encode($this->Mdashboard->totalVentas($id_caja, $desde, $hasta));
	}

	public function resumenCaja()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id_caja = $this->input->post('id_caja');
		//$fecha = $this->input->post('fecha');
		$desde = $this->input->post('desde');
		$hasta = $this->input->post('hasta');
		echo json_encode($this->Mdashboard->resumenCaja($id_caja, $desde, $hasta));
	}

	public function resumenTipoDocumento()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id_caja = $this->input->post('id_caja');
		//$fecha = $this->input->post('fecha');
		$desde = $this->input->post('desde');
		$hasta = $this->input->post('hasta');
		echo json_encode($this->Mdashboard->resumenTipoDocumento($id_caja, $desde, $hasta));
	}

	public function resumenFormaPago()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id_caja = $this->input->post('id_caja');
		//$fecha = $this->input->post('fecha');
		$desde = $this->input->post('desde');
		$hasta = $this->input->post('hasta');
		echo json_encode($this->Mdashboard->resumenFormaPago($id_caja, $desde, $hasta));
	}

	public function resumenCortesias()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id_caja = $this->input->post('id_caja');
		//$fecha = $this->input->post('fecha');
		$desde = $this->input->post('desde');
		$hasta = $this->input->post('hasta');
		echo json_encode($this->Mdashboard->resumenCortesias($id_caja, $desde, $hasta));
	}

	public function resumenVentasPorPagar()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		// $fecha = $this->input->post('fecha');
		echo json_encode($this->Mdashboard->resumenVentasPorPagar(/*$fecha*/));
	}

	public function detalleMesas()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id_cotizacion = $this->input->post('id_cotizacion');
		echo json_encode($this->Mdashboard->detalleMesas($id_cotizacion));
	}

	public function ventasPorMesas()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id_caja = $this->input->post('id_caja');
		//$fecha = $this->input->post('fecha');
		$desde = $this->input->post('desde');
		$hasta = $this->input->post('hasta');
		echo json_encode($this->Mdashboard->ventasPorMesas($id_caja, $desde, $hasta));
	}

	public function detalleAtencion()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id_caja = $this->input->post('id_caja');
		//$fecha = $this->input->post('fecha');
		$desde = $this->input->post('desde');
		$hasta = $this->input->post('hasta');
		echo json_encode($this->Mdashboard->detalleAtencion($id_caja, $desde, $hasta));
	}

	public function pieVentasPorFamilia()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id_caja = $this->input->post('id_caja');
		//$fecha = $this->input->post('fecha');
		$desde = $this->input->post('desde');
		$hasta = $this->input->post('hasta');
		echo json_encode($this->Mdashboard->pieVentasPorFamilia($id_caja, $desde, $hasta));
	}

	public function tableVentasPorFamilia()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id_caja = $this->input->post('id_caja');
		//$fecha = $this->input->post('fecha');
		$desde = $this->input->post('desde');
		$hasta = $this->input->post('hasta');
		echo json_encode($this->Mdashboard->tableVentasPorFamilia($id_caja, $desde, $hasta));
	}

	public function ventasPorProducto()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id_caja = $this->input->post('id_caja');
		$id_familia = $this->input->post('id_familia');
		//$fecha = $this->input->post('fecha');
		$desde = $this->input->post('desde');
		$hasta = $this->input->post('hasta');
		echo json_encode($this->Mdashboard->ventasPorProducto($id_caja, $id_familia, $desde, $hasta));
	}

	public function cajasAbiertas()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		//$fecha = $this->input->post('fecha');
		$desde = $this->input->post('desde');
		$hasta = $this->input->post('hasta');
		echo json_encode($this->Mdashboard->cajasAbiertas($desde, $hasta));
	}

	public function PDFDetalleCaja()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$desde = $this->input->post('desde');
		$hasta = $this->input->post('hasta');
		$idCaja = $this->input->post('id_caja');
		//$detalleDeCaja = $this->input->post('detalleDeCaja');

		$datosEmpresa = $this->Mdashboard->datosEmpresa();
		$datosCaja = $this->Mdashboard->datosCaja($desde, $hasta, $idCaja);
		$totalSistema = $this->Mdashboard->totalSistema($desde, $hasta, $idCaja);
		$datosTipoDte = $this->Mdashboard->datosTipoDte($desde, $hasta, $idCaja);
		$datosFpago = $this->Mdashboard->datosFpago($desde, $hasta, $idCaja);
		$datosCortesias = $this->Mdashboard->datosCortesias($desde, $hasta, $idCaja);
		$obtenerConfiguracionesRestoWeb = $this->Mconfiguraciones->obtenerConfiguracionesRestoWeb();

		$totalVentasPorTipoDte = 0;
		$totaliVAPorTipoDte = 0;
		$totalNetoPorTipoDte = 0;
		$totalPorTipoDte = 0;

		$totalCantidadPorFpago = 0;
		$totaliVAPorFpago = 0;
		$totalNetoPorFpago = 0;
		$totalPorFpago = 0;
		$totalPropinaPorFpago = 0;

		$totalCantidadCortesias = 0;
		$totaliVAPorCortesias = 0;
		$totalNetoPorCortesias = 0;
		$totalCortesias = 0;

		require('fpdf/fpdf.php');
		$pdf = new FPDF('P', 'mm', 'Legal', 'Detalle De Caja');

		$pdf->AliasNbPages();
		$pdf->AddPage();
		$pdf->Image(base_url() . 'assets/img/logo.png', 10, 5, 35, 35, 'PNG');

		$pdf->SetFont('Arial', 'B', 12);
		$pdf->SetXY(78, 15);
		$pdf->SetTextColor(0, 0, 0);
		$pdf->MultiCell(0, 10, utf8_decode($datosEmpresa[0]->razon_social));
		$pdf->Ln(5);

		$pdf->SetX(95);
		$pdf->Cell(0, 5, utf8_decode('DETALLE DE CAJA '));
		$pdf->Ln();
		$pdf->SetX(85);
		$pdf->Ln(15);
		$pdf->SetX(15);
		$pdf->SetFont('Arial', 'B', 11);
		$pdf->Cell(40, 5, utf8_decode('DESDE: ' . $desde), 1);
		$pdf->Ln();
		$pdf->SetX(15);
		$pdf->Cell(40, 5, utf8_decode('HASTA : ' . $hasta), 1);
		$pdf->SetFont('Arial', '', 11);
		$pdf->Ln(10);

		$pdf->SetX(15);
		$pdf->SetFont('Arial', 'B', 11);
		$pdf->Cell(60, 5, utf8_decode('TOTAL VENTAS'),1);
		$pdf->SetFont('Arial', '', 11);
		$pdf->Cell(20, 5, utf8_decode('$   '),1,0,'C');
		$pdf->Cell(40, 5, number_format($totalSistema[0]->monto_total_ventas), 1, 0, 'R');
		$pdf->Ln();
		$pdf->SetX(15);
		$pdf->SetFont('Arial', 'B', 11);
		$pdf->Cell(60, 5, utf8_decode('FONDO FIJO'),1);
		$pdf->SetFont('Arial', '', 11);
		$pdf->Cell(20, 5, utf8_decode('$   '),1,0,'C');
		$pdf->Cell(40, 5, number_format($datosCaja[0]->fondo_fijo), 1, 0, 'R');
		$pdf->Ln();
		$pdf->SetX(15);
		$pdf->SetFont('Arial', 'B', 11);
		$pdf->Cell(60, 5, utf8_decode('DESCUENTOS'),1);
		$pdf->SetFont('Arial', '', 11);
		$pdf->Cell(20, 5, utf8_decode('$   '),1,0,'C');
		$pdf->Cell(40, 5, number_format($datosCaja[0]->descuento), 1, 0, 'R');
		$pdf->Ln();
		$pdf->SetX(15);
		$pdf->SetFont('Arial', 'B', 11);
		$pdf->Cell(60, 5, utf8_decode('PROPINAS'),1);
		$pdf->SetFont('Arial', '', 11);
		$pdf->Cell(20, 5, utf8_decode('$   '),1,0,'C');
		$pdf->Cell(40, 5, number_format($datosCaja[0]->propina), 1, 0, 'R');
		$pdf->Ln();
		$pdf->SetX(15);
		$pdf->SetFont('Arial', 'B', 11);
		$pdf->Cell(60, 5, utf8_decode('INGRESOS'),1);
		$pdf->SetFont('Arial', '', 11);
		$pdf->Cell(20, 5, utf8_decode('$   '),1,0,'C');
		$pdf->Cell(40, 5, number_format($datosCaja[0]->ingresos), 1, 0, 'R');
		$pdf->Ln();
		$pdf->SetX(15);
		$pdf->SetFont('Arial', 'B', 11);
		$pdf->Cell(60, 5, utf8_decode('RETIROS'),1);
		$pdf->SetFont('Arial', '', 11);
		$pdf->Cell(20, 5, utf8_decode('$   '),1,0,'C');
		$pdf->Cell(40, 5, number_format($datosCaja[0]->egresos), 1, 0, 'R');
		$pdf->Ln(10);

		$pdf->SetFont('Arial', 'B', 11);
		$pdf->Ln();
		$pdf->SetX(80);
		$pdf->Cell(0, 5, utf8_decode('RESUMEN POR TIPO DE DOCUMENTO'));
		$pdf->Ln();
		foreach ($obtenerConfiguracionesRestoWeb as $validacion) {
			if ($validacion->desglozar_monto_neto_iva_total_en_reportes == 't') {
				$pdf->SetX(10);
				$pdf->Cell(55, 5, utf8_decode('TIPO DE DOCUMENTO'),1);
				$pdf->Cell(30, 5, utf8_decode('CANTIDAD'),1,0,'C');
				$pdf->Cell(40, 5, utf8_decode('TOTAL NETO'),1,0,'C');
				$pdf->Cell(35, 5, utf8_decode('TOTAL IVA'),1,0,'C');
				$pdf->Cell(40, 5, utf8_decode('TOTAL VENTAS'),1,0,'C');
			} else {
				$pdf->SetX(15);
				$pdf->Cell(80, 5, utf8_decode('TIPO DE DOCUMENTO'),1,0,'C');
				$pdf->Cell(40, 5, utf8_decode('CANTIDAD'),1,0,'C');
				$pdf->Cell(80, 5, utf8_decode('TOTAL VENTAS'),1,0,'C');
			}
		}
		$pdf->SetFont('Arial', '', 11);
		foreach ($datosTipoDte as $row) {
			if ($row->cantidad > 0) {
				foreach ($obtenerConfiguracionesRestoWeb as $validacion) {
					if ($validacion->desglozar_monto_neto_iva_total_en_reportes == 't') {
						$pdf->Ln(5);
						$pdf->SetX(10);
						$pdf->Cell(55, 5, utf8_decode($row->nombre_tdocumento),1,0,'C');
						$pdf->Cell(30, 5, number_format($row->cantidad),1,0,'C');
						$pdf->Cell(40, 5, number_format($row->total),1,0,'C');
						$totalVentasPorTipoDte += $row->cantidad;
						$totalPorTipoDte += $row->total;
						if ($row->id_tdocumento == 5) {
							$pdf->Cell(40, 5, number_format($row->total),1,0,'C');
							$pdf->Cell(35, 5, number_format('0'),1,0,'C');
							$totalNetoPorTipoDte += $row->total;
							$totaliVAPorTipoDte += 0;
						} else {
							$pdf->Cell(40, 5, number_format($row->neto));
							$pdf->Cell(35, 5, number_format($row->iva));
							$totalNetoPorTipoDte += $row->neto;
							$totaliVAPorTipoDte += $row->iva;
						}
					} else {
						$pdf->SetX(15);
						$pdf->Cell(0, 5, utf8_decode($row->nombre_tdocumento));
						$pdf->SetX(110);
						$pdf->Cell(0, 5, number_format($row->cantidad));
						$pdf->SetX(185);
						$pdf->Cell(0, 5, number_format($row->total));
						$totalVentasPorTipoDte += $row->cantidad;
						$totalPorTipoDte += $row->total;
					}
				}
			}
		}

		$pdf->SetFont('Arial', 'B', 11);
		foreach ($obtenerConfiguracionesRestoWeb as $validacion) {
			if ($validacion->desglozar_monto_neto_iva_total_en_reportes == 't') {

				$pdf->Ln(10);
				$pdf->SetX(10);
				$pdf->Cell(55, 5, utf8_decode('TOTALES'),1,0,'C');
				$pdf->Cell(30, 5, number_format($totalVentasPorTipoDte),1,0,'C');
				$pdf->Cell(40, 5, number_format($totalNetoPorTipoDte),1,0,'C');
				$pdf->Cell(35, 5, number_format($totaliVAPorTipoDte),1,0,'C');
				$pdf->Cell(40, 5, number_format($totalPorTipoDte),1,0,'C');
			} else {
				$pdf->Ln(10);
				$pdf->SetX(10);
				$pdf->Cell(80, 5, utf8_decode('TOTALES'),1,0,'C');
				$pdf->Cell(40, 5, number_format($totalVentasPorTipoDte),1,0,'C');
				$pdf->Cell(80, 5, number_format($totalPorTipoDte),1,0,'C');
			}
		}

		$pdf->Ln(15);
		$pdf->SetX(80);
		$pdf->Cell(0, 5, utf8_decode('RESUMEN POR FORMA DE PAGO'));
		$pdf->Ln();
		$pdf->SetFont('Arial', 'B', 11);
		foreach ($obtenerConfiguracionesRestoWeb as $validacion) {
			if ($validacion->desglozar_monto_neto_iva_total_en_reportes == 't') {
				$pdf->SetX(10);
				$pdf->Cell(55, 5, utf8_decode('FORMA DE PAGO'),1,0,'C');
				$pdf->Cell(30, 5, utf8_decode('CANTIDAD'),1,0,'C');
				$pdf->Cell(40, 5, utf8_decode('TOTAL NETO'),1,0,'C');
				$pdf->Cell(40, 5, utf8_decode('TOTAL IVA'),1,0,'C');
				$pdf->Cell(40, 5, utf8_decode('TOTAL VENTAS'),1,0,'C');
				$pdf->Cell(40, 5, utf8_decode('PROPINAS'),1,0,'C');
			} else {
				$pdf->SetX(15);
				$pdf->Cell(0, 5, utf8_decode('FORMA DE PAGO'));
				$pdf->SetX(80);
				$pdf->Cell(0, 5, utf8_decode('CANTIDAD'));
				$pdf->SetX(125);
				$pdf->Cell(0, 5, utf8_decode('TOTAL VENTAS'));
				$pdf->SetX(180);
				$pdf->Cell(0, 5, utf8_decode('PROPINAS'));
				$pdf->Ln();
			}
		}
		$pdf->SetFont('Arial', '', 11);
		foreach ($datosFpago as $row) {
			if ($row->cantidad > 0) {
				foreach ($obtenerConfiguracionesRestoWeb as $validacion) {
					if ($validacion->desglozar_monto_neto_iva_total_en_reportes == 't') {
						$pdf->Ln(5);
						$pdf->SetX(10);
						$pdf->Cell(55, 5, utf8_decode($row->nombre_fpago),1,0,'C');
						$pdf->Cell(30, 5, number_format($row->cantidad),1,0,'C');
						$pdf->Cell(40, 5, number_format($row->neto),1,0,'C');
						$pdf->Cell(40, 5, number_format($row->iva),1,0,'C');
						$pdf->Cell(40, 5, number_format($row->total),1,0,'C');
						$pdf->Cell(40, 5, number_format($row->propina),1,0,'C');
						$totalCantidadPorFpago += $row->cantidad;
						$totalNetoPorFpago += $row->neto;
						$totaliVAPorFpago += $row->iva;
						$totalPorFpago += $row->total;
						$totalPropinaPorFpago += $row->propina;
					} else {
						$pdf->Ln(5);
						$pdf->SetX(20);
						$pdf->Cell(0, 5, utf8_decode($row->nombre_fpago));
						$pdf->SetX(90);
						$pdf->Cell(0, 5, number_format($row->cantidad));
						$pdf->SetX(133);
						$pdf->Cell(0, 5, number_format($row->total));
						$pdf->SetX(190);
						$pdf->Cell(0, 5, number_format($row->propina));
						$pdf->Ln();
						$pdf->Cell(0, 5, utf8_decode('____________________________________________________________________________________________________'));
						$totalCantidadPorFpago += $row->cantidad;
						$totalPorFpago += $row->total;
						$totalPropinaPorFpago += $row->propina;
					}
				}
			}
		}
		$pdf->SetFont('Arial', 'B', 11);
		foreach ($obtenerConfiguracionesRestoWeb as $validacion) {
			if ($validacion->desglozar_monto_neto_iva_total_en_reportes == 't') {
				$pdf->Ln(10);
				$pdf->SetX(10);
				$pdf->Cell(55, 5, utf8_decode('TOTALES'),1,0,'C');
				$pdf->Cell(30, 5, number_format($totalCantidadPorFpago),1,0,'C');
				$pdf->Cell(30, 5, number_format($totalNetoPorFpago),1,0,'C');
				$pdf->Cell(30, 5, number_format($totaliVAPorFpago),1,0,'C');
				$pdf->Cell(30, 5, number_format($totalPorFpago),1,0,'C');
				$pdf->Cell(30, 5, number_format($totalPropinaPorFpago),1,0,'C');
			} else {
				$pdf->Ln(10);
				$pdf->SetX(20);
				$pdf->Cell(0, 5, utf8_decode('TOTALES'));
				$pdf->SetX(90);
				$pdf->Cell(40, 5, number_format($totalCantidadPorFpago));
				$pdf->SetX(133);
				$pdf->Cell(40, 5, number_format($totalPorFpago));
				$pdf->SetX(190);
				$pdf->Cell(40, 5, number_format($totalPropinaPorFpago));
			}
		}


		$pdf->Ln(15);
		$pdf->SetX(85);
		$pdf->Cell(0, 5, utf8_decode('RESUMEN CORTESIAS'));
		$pdf->Ln();
		foreach ($obtenerConfiguracionesRestoWeb as $validacion) {
			if ($validacion->desglozar_monto_neto_iva_total_en_reportes == 't') {
				$pdf->SetX(10);
				$pdf->Cell(55, 5, utf8_decode('TIPO DE CORTESIA'),1,0,'C');
				$pdf->Cell(30, 5, utf8_decode('CANTIDAD'),1,0,'C');
				$pdf->Cell(40, 5, utf8_decode('TOTAL NETO'),1,0,'C');
				$pdf->Cell(40, 5, utf8_decode('TOTAL IVA'),1,0,'C');
				$pdf->Cell(40, 5, utf8_decode('TOTAL VENTAS'),1,0,'C');
				$pdf->Ln();
			} else {
				$pdf->SetX(15);
				$pdf->Cell(0, 5, utf8_decode('TIPO DE CORTESIA'));
				$pdf->SetX(100);
				$pdf->Cell(0, 5, utf8_decode('CANTIDAD'));
				$pdf->SetX(175);
				$pdf->Cell(0, 5, utf8_decode('TOTAL VENTAS'));
				$pdf->Ln();
				$pdf->Cell(0, 5, utf8_decode('____________________________________________________________________________________________________'));
			}
		}
		foreach ($datosCortesias as $row) {
			if ($row->cantidad > 0) {
				foreach ($obtenerConfiguracionesRestoWeb as $validacion) {
					if ($validacion->desglozar_monto_neto_iva_total_en_reportes == 't') {
						$pdf->Ln(5);
						$pdf->SetX(10);
						$pdf->Cell(55, 5, utf8_decode($row->nombre_fpago),1,0,'C');
						$pdf->Cell(30, 5, number_format($row->cantidad),1,0,'C');
						$pdf->Cell(40, 5, number_format($row->neto),1,0,'C');
						$pdf->Cell(40, 5, number_format($row->iva),1,0,'C');
						$pdf->Cell(40, 5, number_format($row->total),1,0,'C');
						$pdf->Ln();
						$totalCantidadCortesias += $row->cantidad;
						$totalNetoPorCortesias += $row->neto;
						$totaliVAPorCortesias += $row->iva;
						$totalCortesias += $row->total;
					} else {
						$pdf->Ln(5);
						$pdf->SetX(15);
						$pdf->Cell(0, 5, utf8_decode($row->nombre_fpago));
						$pdf->SetX(100);
						$pdf->Cell(0, 5, number_format($row->cantidad));
						$pdf->SetX(180);
						$pdf->Cell(0, 5, number_format($row->total));
						$pdf->Ln();
						$pdf->Cell(0, 5, utf8_decode('____________________________________________________________________________________________________'));
						$totalCantidadCortesias += $row->cantidad;
						$totalCortesias += $row->total;
					}
				}
			}
		}
		foreach ($obtenerConfiguracionesRestoWeb as $validacion) {
			if ($validacion->desglozar_monto_neto_iva_total_en_reportes == 't') {
				$pdf->Ln(10);
				$pdf->SetX(10);
				$pdf->Cell(55, 5, utf8_decode('TOTALES'),1,0,'C');
				$pdf->Cell(30, 5, number_format($totalCantidadCortesias),1,0,'C');
				$pdf->Cell(40, 5, number_format($totalNetoPorCortesias),1,0,'C');
				$pdf->Cell(40, 5, number_format($totaliVAPorCortesias),1,0,'C');
				$pdf->Cell(40, 5, number_format($totalCortesias),1,0,'C');
			}else{
				$pdf->Ln(10);
				$pdf->SetX(15);
				$pdf->Cell(0, 5, utf8_decode('TOTALES'));
				$pdf->SetX(105);
				$pdf->Cell(40, 5, number_format($totalCantidadCortesias));
				$pdf->SetX(185);
				$pdf->Cell(40, 5, number_format($totalCortesias));
			}
		}
		$pdf->Output('DetalleDeCaja Desde ' . $desde . ' Hasta ' . $hasta . '.pdf', 'I');
	} //fin pdf


	public function obtenerDetalleDeIngresos()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id_caja = $this->input->post('id_caja');
		$desde = $this->input->post('desde');
		$hasta = $this->input->post('hasta');
		echo json_encode($this->Mdashboard->obtenerDetalleDeIngresos($id_caja, $desde, $hasta));
	}
	public function obtenerDetalleDeRetiros()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id_caja = $this->input->post('id_caja');
		$desde = $this->input->post('desde');
		$hasta = $this->input->post('hasta');
		echo json_encode($this->Mdashboard->obtenerDetalleDeRetiros($id_caja, $desde, $hasta));
	}

	public function obtenerDetalleDeDescuentos()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id_caja = $this->input->post('id_caja');
		$desde = $this->input->post('desde');
		$hasta = $this->input->post('hasta');
		echo json_encode($this->Mdashboard->obtenerDetalleDeDescuentos($id_caja, $desde, $hasta));
	}

	public function obtenerDetalleDePropinas()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id_caja = $this->input->post('id_caja');
		$id_usuario = $this->input->post('id_usuario');
		$desde = $this->input->post('desde');
		$hasta = $this->input->post('hasta');
		echo json_encode($this->Mdashboard->obtenerDetalleDePropinas($id_caja, $desde, $hasta,$id_usuario));
	}
}