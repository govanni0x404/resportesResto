<?php
defined('BASEPATH') or exit('No direct script access allowed');

class PropinasPorGarzon extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model(array('MpropinasPorGarzon', 'Mroles', 'MconsolidadoVentas' ));
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
		$this->load->view('propinasPorGarzon/propinasPorGarzon.php');
		$this->load->view('menuFooter.php');
		$this->load->view('propinasPorGarzon/js.php');
	}

	public function resumenDePropinasEntreFechas()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$desde = $this->input->post('desde');
		$hasta = $this->input->post('hasta');
		echo json_encode($this->MpropinasPorGarzon->resumenDePropinasEntreFechas($desde, $hasta));
	}

    public function resumenTotalPropinaPorGarzon()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$desde = $this->input->post('desde');
		$hasta = $this->input->post('hasta');
		echo json_encode($this->MpropinasPorGarzon->resumenTotalPropinaPorGarzon($desde, $hasta));
	}

    public function resumenTotalPropinaPorFormaDePago()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$desde = $this->input->post('desde');
		$hasta = $this->input->post('hasta');
		echo json_encode($this->MpropinasPorGarzon->resumenTotalPropinaPorFormaDePago($desde, $hasta));
	}

    
    public function PDFResumenDePropinasPorGarzon()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $desde = $this->input->post('desde');
        $hasta = $this->input->post('hasta');
  

        $datosEmpresa = $this->MconsolidadoVentas->datosEmpresa();
        $resumenDePropinasEntreFechas = $this->MpropinasPorGarzon->resumenDePropinasEntreFechas($desde, $hasta);
        $resumenTotalPropinaPorGarzon = $this->MpropinasPorGarzon->resumenTotalPropinaPorGarzon($desde, $hasta);
        $resumenTotalPropinaPorFormaDePago = $this->MpropinasPorGarzon->resumenTotalPropinaPorFormaDePago($desde, $hasta);


        $totalPropinasEntreFechas = 0;
        $totalPropinasPorGarzon = 0;
        $totalPropinasPorFormasDePago = 0;

        require('fpdf/fpdf.php');
        $pdf = new FPDF('P', 'mm', 'Legal', 'Detalle De Propinas Por Garzon');
        $pdf->AliasNbPages();
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 10);



        $pdf->Image(FCPATH . 'assets/img/logo.png', 10, 5, 35, 35, 'PNG');

        $pdf->SetXY(78, 15);
        $pdf->Cell(0, 10, mb_convert_encoding((string)($datosEmpresa[0]->razon_social), 'ISO-8859-1', 'UTF-8'));

        $pdf->Ln(10);

        /*if ($idCaja > 0) {*/
        $pdf->SetX(85);

        $pdf->Cell(0, 5, mb_convert_encoding((string)('Detalle De Propinas Por Garzon '), 'ISO-8859-1', 'UTF-8'));
        $pdf->Ln();
        $pdf->SetX(85);
        $pdf->Ln(15);
        $pdf->SetX(10);
        $pdf->Cell(0, 5, mb_convert_encoding((string)('DESDE: ' . $desde), 'ISO-8859-1', 'UTF-8'));
        $pdf->Ln();
        $pdf->Cell(0, 5, mb_convert_encoding((string)('HASTA : ' . $hasta), 'ISO-8859-1', 'UTF-8'));

		$pdf->Ln();
        $pdf->SetX(88);
        $pdf->Cell(0, 5, mb_convert_encoding((string)('PROPINAS ENTRE FECHAS'), 'ISO-8859-1', 'UTF-8'));
        $pdf->Ln();
        $pdf->Cell(0, 5, mb_convert_encoding((string)('____________________________________________________________________________________________________'), 'ISO-8859-1', 'UTF-8'));
        $pdf->Ln();
       
                $pdf->SetX(15);
                $pdf->Cell(0, 5, mb_convert_encoding((string)('N° DE CAJA'), 'ISO-8859-1', 'UTF-8'));
                $pdf->SetX(55);
                $pdf->Cell(0, 5, mb_convert_encoding((string)('FECHA'), 'ISO-8859-1', 'UTF-8'));
                $pdf->SetX(105);
                $pdf->Cell(0, 5, mb_convert_encoding((string)('GARZON'), 'ISO-8859-1', 'UTF-8'));
                $pdf->SetX(140);
                $pdf->Cell(0, 5, mb_convert_encoding((string)('PROPINA'), 'ISO-8859-1', 'UTF-8'));
                $pdf->SetX(170);
                $pdf->Cell(0, 5, mb_convert_encoding((string)('FORMA DE PAGO'), 'ISO-8859-1', 'UTF-8'));
                $pdf->Ln();
                $pdf->Cell(0, 5, mb_convert_encoding((string)('____________________________________________________________________________________________________'), 'ISO-8859-1', 'UTF-8'));

        // Filas del detalle: antes el ciclo estaba comentado y el PDF salía solo con encabezados.
        // El modelo devuelve el formato de DataTables (filas en 'aaData', cada una como array).
        $filasPropinas = isset($resumenDePropinasEntreFechas['aaData']) ? $resumenDePropinasEntreFechas['aaData'] : array();
        foreach ($filasPropinas as $row) {
            $pdf->Ln();
            $pdf->SetX(15);
            $pdf->Cell(0, 5, mb_convert_encoding((string)($row['id_caja']), 'ISO-8859-1', 'UTF-8'));
            $pdf->SetX(55);
            $pdf->Cell(0, 5, mb_convert_encoding((string)($row['fecha']), 'ISO-8859-1', 'UTF-8'));
            $pdf->SetX(105);
            $pdf->Cell(0, 5, mb_convert_encoding(substr((string)($row['nombre_usuario']), 0, 20), 'ISO-8859-1', 'UTF-8'));
            $pdf->SetX(140);
            $pdf->Cell(0, 5, number_format((float)$row['total_propina']));
            $pdf->SetX(170);
            $pdf->Cell(0, 5, mb_convert_encoding((string)($row['nombre_fpago']), 'ISO-8859-1', 'UTF-8'));
            $totalPropinasEntreFechas += (float)$row['total_propina'];
        }
        $pdf->Ln();
        $pdf->Cell(0, 5, mb_convert_encoding((string)('____________________________________________________________________________________________________'), 'ISO-8859-1', 'UTF-8'));
        $pdf->Ln();
        $pdf->SetX(105);
        $pdf->Cell(0, 5, mb_convert_encoding((string)('TOTAL'), 'ISO-8859-1', 'UTF-8'));
        $pdf->SetX(140);
        $pdf->Cell(0, 5, number_format($totalPropinasEntreFechas));
        

        

       

    



        $pdf->Output('Detalle De Propinas Por Garzon Desde ' . $desde . ' Hasta ' . $hasta . '.pdf', 'I');
    } //fin pdf

}