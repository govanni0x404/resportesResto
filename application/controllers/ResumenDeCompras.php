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
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
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
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$desde = $this->input->post('desde');
		$hasta = $this->input->post('hasta');
		echo json_encode($this->MresumenDeCompras->resumenDeCompras($desde, $hasta));
	}

	public function detallePorFamilia()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		echo json_encode($this->MresumenDeCompras->detallePorFamilia());
	}

	public function detalleFacturas()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id_compra = $this->input->post('id_compra');
		echo json_encode($this->MresumenDeCompras->detalleFacturas($id_compra));
	}

	public function resumenComprasPDF()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}

		$idCompra = $this->input->post('id_compra');
		$listadoCompras = $this->input->post('listaCompras');
		$desde = $this->input->post('desde');
		$hasta = $this->input->post('hasta');

		$resumenDeComprasPDF = $this->MresumenDeCompras->resumenDeComprasPDF($idCompra, $listadoCompras);

		require('fpdf/fpdf.php');
		$pdf = new FPDF('P', 'mm', 'Legal', 'Cierre De Caja');

		$pdf->AliasNbPages();
		$pdf->AddPage();
		$pdf->SetFont('Arial', '', 11);

		$pdf->SetXY(80, 15);
		$pdf->Cell(0, 5, utf8_decode('RESUMEN DE COMPRAS'));

		$pdf->Ln(15);
		$pdf->Cell(0, 5, utf8_decode('PROVEEDOR:  ' . $resumenDeComprasPDF[0]->nombre_proveedor));
		$pdf->Ln();
		$pdf->Cell(0, 5, utf8_decode($resumenDeComprasPDF[0]->nombre_tdocumento . ' N°  ' . $resumenDeComprasPDF[0]->numero_documento));
		$pdf->Ln();
		$pdf->Cell(0, 5, utf8_decode('FECHA DE COMPRA:  ' . $resumenDeComprasPDF[0]->fecha));
		$pdf->Ln();
		$pdf->Cell(0, 5, utf8_decode('TOTAL COMPRA'));
		$pdf->SetX(42);
		$pdf->Cell(0, 5, utf8_decode('='));
		$pdf->SetX(80);
		$pdf->Cell(0, 5, utf8_decode('$   '));
		$pdf->SetX(68);
		$pdf->Cell(40, 5, number_format($resumenDeComprasPDF[0]->total), 0, 0, 'R');


		$pdf->SetTitle("Cierre de Caja Consolidado");

		ob_start();
		$pdf->Output('I', 'Cierre de Caja Consolidado.pdf');
		ob_end_flush();
	}
}