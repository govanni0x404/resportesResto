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
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
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
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$desde = $this->input->post('desde');
		$hasta = $this->input->post('hasta');
		echo json_encode($this->MpropinasPorGarzon->resumenDePropinasEntreFechas($desde, $hasta));
	}

    public function resumenTotalPropinaPorGarzon()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$desde = $this->input->post('desde');
		$hasta = $this->input->post('hasta');
		echo json_encode($this->MpropinasPorGarzon->resumenTotalPropinaPorGarzon($desde, $hasta));
	}

    public function resumenTotalPropinaPorFormaDePago()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$desde = $this->input->post('desde');
		$hasta = $this->input->post('hasta');
		echo json_encode($this->MpropinasPorGarzon->resumenTotalPropinaPorFormaDePago($desde, $hasta));
	}

    
    public function PDFResumenDePropinasPorGarzon()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
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



        $pdf->Image(base_url() . 'assets/img/logo.png', 10, 5, 35, 35, 'PNG');

        $pdf->SetXY(78, 15);
        $pdf->Cell(0, 10, utf8_decode($datosEmpresa[0]->razon_social));

        $pdf->Ln(10);

        /*if ($idCaja > 0) {*/
        $pdf->SetX(85);

        $pdf->Cell(0, 5, utf8_decode('Detalle De Propinas Por Garzon '));
        $pdf->Ln();
        $pdf->SetX(85);
        $pdf->Ln(15);
        $pdf->SetX(10);
        $pdf->Cell(0, 5, utf8_decode('DESDE: ' . $desde));
        $pdf->Ln();
        $pdf->Cell(0, 5, utf8_decode('HASTA : ' . $hasta));

		$pdf->Ln();
        $pdf->SetX(88);
        $pdf->Cell(0, 5, utf8_decode('PROPINAS ENTRE FECHAS'));
        $pdf->Ln();
        $pdf->Cell(0, 5, utf8_decode('____________________________________________________________________________________________________'));
        $pdf->Ln();
       
                $pdf->SetX(15);
                $pdf->Cell(0, 5, utf8_decode('N° DE CAJA'));
                $pdf->SetX(55);
                $pdf->Cell(0, 5, utf8_decode('FECHA'));
                $pdf->SetX(105);
                $pdf->Cell(0, 5, utf8_decode('GARZON'));
                $pdf->SetX(140);
                $pdf->Cell(0, 5, utf8_decode('PROPINA'));
                $pdf->SetX(170);
                $pdf->Cell(0, 5, utf8_decode('FORMA DE PAGO'));
                $pdf->Ln();
                $pdf->Cell(0, 5, utf8_decode('____________________________________________________________________________________________________'));

       /* foreach ($resumenDePropinasEntreFechas as $row) {
            if ($row->id_caja > 0) {
                        $pdf->Ln();
                        $pdf->SetX(15);
                        $pdf->Cell(0, 5, utf8_decode($row->id_caja));
                        $pdf->SetX(70);
                        /*$pdf->Cell(0, 5, utf8_decode($row->fecha));
                        $pdf->SetX(100);
                        $pdf->Cell(23, 5, utf8_decode($row->nombre_usuario), 0, 0, 'R');
                        $pdf->SetX(130);
                        $pdf->Cell(28, 5, number_format($row->total_propina), 0, 0, 'R');
                        $pdf->SetX(155);
                        $pdf->Cell(35, 5, number_format($row->nombre_fpago), 0, 0, 'R');
                        $pdf->Ln();
                        $pdf->Cell(0, 5, utf8_decode('____________________________________________________________________________________________________'));
                        
    
                }
			}
            */
        

        

       

    



        $pdf->Output('Detalle De Propinas Por Garzon Desde ' . $desde . ' Hasta ' . $hasta . '.pdf', 'I');
    } //fin pdf

}