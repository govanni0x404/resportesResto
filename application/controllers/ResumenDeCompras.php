<?php
defined('BASEPATH') or exit('No direct script access allowed');

class ResumenDeCompras extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model(array('MresumenDeCompras', 'Mroles'));
	}

	public function index()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$data = array(
			'nombreUsuario' => $this->session->userdata('nombre_usuario'),
			//'modulosDisponibles' => json_decode($this->Mroles->modulosDisponibles($this->session->userdata('id_rol')), true)
		);
		$this->load->view('menuTop.php', $data);
		$this->load->view('resumendecompras/resumendecompras.php');
		$this->load->view('menuFooter.php');
		$this->load->view('resumendecompras/js.php');
	}

	public function resumenDeCompras()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$desde = $this->input->post('desde');
		$hasta = $this->input->post('hasta');
		echo json_encode($this->MresumenDeCompras->resumenDeCompras($desde, $hasta));
	}

	public function detalleFacturas()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id_compra = $this->input->post('id_compra');
		echo json_encode($this->MresumenDeCompras->detalleFacturas($id_compra));
	}

	public function resumenComprasPDF()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}

		$idCompra = $this->input->post('id_compra');
		$listadoCompras = $this->input->post('listaCompras');
		$desde = $this->input->post('desde');
		$hasta = $this->input->post('hasta');

		$resumenDeComprasPDF = $this->MresumenDeCompras->resumenDeComprasPDF($idCompra, $listadoCompras);
		if (empty($resumenDeComprasPDF)) {
			show_error('La compra no existe', 404, 'Resumen de compras');
		}

		require('fpdf/fpdf.php');
		$pdf = new FPDF('P', 'mm', 'Legal', 'Cierre De Caja');

		$pdf->AliasNbPages();
		$pdf->AddPage();
		$pdf->SetFont('Arial', '', 11);

		$pdf->SetXY(80, 15);
		$pdf->Cell(0, 5, mb_convert_encoding((string)('RESUMEN DE COMPRAS'), 'ISO-8859-1', 'UTF-8'));

		$pdf->Ln(15);
		$pdf->Cell(0, 5, mb_convert_encoding((string)('PROVEEDOR:  ' . $resumenDeComprasPDF[0]->nombre_proveedor), 'ISO-8859-1', 'UTF-8'));
		$pdf->Ln();
		$pdf->Cell(0, 5, mb_convert_encoding((string)($resumenDeComprasPDF[0]->nombre_tdocumento . ' N°  ' . $resumenDeComprasPDF[0]->numero_documento), 'ISO-8859-1', 'UTF-8'));
		$pdf->Ln();
		$pdf->Cell(0, 5, mb_convert_encoding((string)('FECHA DE COMPRA:  ' . $resumenDeComprasPDF[0]->fecha), 'ISO-8859-1', 'UTF-8'));
		$pdf->Ln();
		$pdf->Cell(0, 5, mb_convert_encoding((string)('TOTAL COMPRA'), 'ISO-8859-1', 'UTF-8'));
		$pdf->SetX(42);
		$pdf->Cell(0, 5, mb_convert_encoding((string)('='), 'ISO-8859-1', 'UTF-8'));
		$pdf->SetX(80);
		$pdf->Cell(0, 5, mb_convert_encoding((string)('$   '), 'ISO-8859-1', 'UTF-8'));
		$pdf->SetX(68);
		$pdf->Cell(40, 5, number_format($resumenDeComprasPDF[0]->total), 0, 0, 'R');

		// v1.20.1: detalle de lo comprado (antes el PDF traía solo el encabezado y el total)
		$detalle = $this->MresumenDeCompras->detalleFacturas((int)$idCompra);
		$filas = isset($detalle['aaData']) ? $detalle['aaData'] : array();
		if ($filas) {
			$pdf->Ln(10);
			$pdf->SetFont('Arial', 'B', 9);
			$pdf->Cell(95, 6, 'INSUMO', 1);
			$pdf->Cell(25, 6, 'CANTIDAD', 1, 0, 'R');
			$pdf->Cell(30, 6, 'P. UNITARIO', 1, 0, 'R');
			$pdf->Cell(30, 6, 'TOTAL', 1, 1, 'R');
			$pdf->SetFont('Arial', '', 9);
			foreach ($filas as $f) {
				$cant = rtrim(rtrim(number_format((float)$f['cantidad'], 3, ',', '.'), '0'), ',');
				$pdf->Cell(95, 6, mb_convert_encoding(mb_substr((string)$f['nombre_insumo'], 0, 55), 'ISO-8859-1', 'UTF-8'), 1);
				$pdf->Cell(25, 6, $cant, 1, 0, 'R');
				$pdf->Cell(30, 6, '$ ' . number_format((float)$f['bruto_unitario'], 0, ',', '.'), 1, 0, 'R');
				$pdf->Cell(30, 6, '$ ' . number_format((float)$f['total'], 0, ',', '.'), 1, 1, 'R');
			}
		}

		$pdf->SetTitle('Resumen de compra');

		ob_start();
		$pdf->Output('I', 'Resumen de compra.pdf');
		ob_end_flush();
	}
}