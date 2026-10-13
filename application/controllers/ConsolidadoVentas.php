<?php
defined('BASEPATH') or exit('No direct script access allowed');

class ConsolidadoVentas extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model(array('MconsolidadoVentas', 'Mroles', 'Mconfiguraciones'));
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
        $this->load->view('consolidadoVentas/consolidadoVentas.php');
        $this->load->view('menuFooter.php');
        $this->load->view('consolidadoVentas/js.php');
    }

    public function ResumenConsolidadoVentas()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $desde = $this->input->post('desde');
        $hasta = $this->input->post('hasta');
        echo json_encode($this->MconsolidadoVentas->ResumenConsolidadoVentas($desde, $hasta));
    }

    public function MontoTotalventas()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        echo json_encode($this->MconsolidadoVentas->MontoTotalventas());
    }

    public function ResumenVentasPorFamilia()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $desde = $this->input->post('desde');
        $hasta = $this->input->post('hasta');
        echo json_encode($this->MconsolidadoVentas->ResumenVentasPorFamilia($desde, $hasta));
    }

    public function ResumenVentasPorCanal()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $desde = $this->input->post('desde');
        $hasta = $this->input->post('hasta');
        echo json_encode($this->MconsolidadoVentas->ResumenVentasPorCanal($desde, $hasta));
    }

    public function PDFConsolidadoDeVentas()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $desde = $this->input->post('desde');
        $hasta = $this->input->post('hasta');
        $idCaja = $this->input->post('id_caja');
        //$detalleDeCaja = $this->input->post('detalleDeCaja');

        $datosEmpresa = $this->MconsolidadoVentas->datosEmpresa();
        $detalleConsolidado = $this->MconsolidadoVentas->detalleConsolidado($desde, $hasta);
        $totalSistema = $this->MconsolidadoVentas->totalSistema($desde, $hasta);
        $datosVentasPorFamilia = $this->MconsolidadoVentas->datosVentasPorFamilia($desde, $hasta);
        $datosCanalDeVentas = $this->MconsolidadoVentas->datosCanalDeVentas($desde, $hasta);
        $obtenerConfiguracionesRestoWeb = $this->Mconfiguraciones->obtenerConfiguracionesRestoWeb();

        $totalCantidadPorFamilia = 0;
        $totaliVAPorFamilia = 0;
        $totalNetoPorFamilia = 0;
        $totalPorFamilia = 0;

        $totalCantidadPorCanalDeVentas = 0;
        $totaliVAPorCanalDeVentas = 0;
        $totalNetoPorCanalDeVentas = 0;
        $totalPorCanalDeVentas = 0;

        require('fpdf/fpdf.php');
        $pdf = new FPDF('P', 'mm', 'Legal', 'Detalle Consolidado De Ventas');
        $pdf->AliasNbPages();
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 10);



        $pdf->Image(FCPATH . 'assets/img/logo.png', 10, 5, 35, 35, 'PNG');

        $pdf->SetXY(78, 15);
        $pdf->Cell(0, 10, utf8_decode($datosEmpresa[0]->razon_social));

        $pdf->Ln(10);

        /*if ($idCaja > 0) {*/
        $pdf->SetX(85);

        $pdf->Cell(0, 5, utf8_decode('DETALLE CONSOLIDADO DE VENTAS '));
        $pdf->Ln();
        $pdf->SetX(85);
        $pdf->Ln(15);
        $pdf->SetX(10);
        $pdf->Cell(0, 5, utf8_decode('DESDE: ' . $desde));
        $pdf->Ln();
        $pdf->Cell(0, 5, utf8_decode('HASTA : ' . $hasta));

        foreach ($obtenerConfiguracionesRestoWeb as $validacion) {
            if ($validacion->desglozar_monto_neto_iva_total_en_reportes == 't') {
                $pdf->Ln(10);
                $pdf->Cell(0, 5, utf8_decode('CANTIDAD DE VENTAS'));
                $pdf->SetX(70);
                $pdf->Cell(0, 5, utf8_decode('$   '));
                $pdf->SetX(70);
                $pdf->Cell(40, 5, number_format($detalleConsolidado[0]->cantidad_ventas), 0, 0, 'R');
                $pdf->Ln();
                $pdf->Cell(0, 5, utf8_decode('TOTAL NETO'));
                $pdf->SetX(70);
                $pdf->Cell(0, 5, utf8_decode('$   '));
                $pdf->SetX(70);
                $pdf->Cell(40, 5, number_format($detalleConsolidado[0]->total_neto), 0, 0, 'R');
                $pdf->Ln();
                $pdf->Cell(0, 5, utf8_decode('TOTAL IVA'));
                $pdf->SetX(70);
                $pdf->Cell(0, 5, utf8_decode('$   '));
                $pdf->SetX(70);
                $pdf->Cell(40, 5, number_format($detalleConsolidado[0]->total_iva), 0, 0, 'R');
                $pdf->Ln();
                $pdf->Cell(0, 5, utf8_decode('TOTAL VENTAS '));
                $pdf->SetX(70);
                $pdf->Cell(0, 5, utf8_decode('$   '));
                $pdf->SetX(70);
                $pdf->Cell(40, 5, number_format($detalleConsolidado[0]->total_ventas), 0, 0, 'R');
                $pdf->Ln();
                $pdf->Cell(0, 5, utf8_decode('TOTAL DESCUENTOS  (-) '));
                $pdf->SetX(70);
                $pdf->Cell(0, 5, utf8_decode('$   '));
                $pdf->SetX(70);
                $pdf->Cell(40, 5, number_format($detalleConsolidado[0]->total_descuento), 0, 0, 'R');
                $pdf->Ln();
                $pdf->Cell(0, 5, utf8_decode('TOTAL GENERAL '));
                $pdf->SetX(70);
                $pdf->Cell(0, 5, utf8_decode('$   '));
                $pdf->SetX(70);
                $pdf->Cell(40, 5, number_format($detalleConsolidado[0]->total_general), 0, 0, 'R');
                $pdf->Ln(5);
                $pdf->Ln();
                $pdf->Cell(0, 5, utf8_decode('MONTO TOTAL VENTAS '));
                $pdf->SetX(70);
                $pdf->Cell(0, 5, utf8_decode('$   '));
                $pdf->SetX(70);
                $pdf->Cell(40, 5, number_format($totalSistema[0]->monto_total_ventas), 0, 0, 'R');
                $pdf->Ln(10);
            } else {
                $pdf->Ln(10);
                $pdf->Cell(0, 5, utf8_decode('CANTIDAD DE VENTAS'));
                $pdf->SetX(70);
                $pdf->Cell(0, 5, utf8_decode('$   '));
                $pdf->SetX(70);
                $pdf->Cell(40, 5, number_format($detalleConsolidado[0]->cantidad_ventas), 0, 0, 'R');
                $pdf->Ln();
                $pdf->Cell(0, 5, utf8_decode('TOTAL VENTAS '));
                $pdf->SetX(70);
                $pdf->Cell(0, 5, utf8_decode('$   '));
                $pdf->SetX(70);
                $pdf->Cell(40, 5, number_format($detalleConsolidado[0]->total_ventas), 0, 0, 'R');
                $pdf->Ln();
                $pdf->Cell(0, 5, utf8_decode('TOTAL DESCUENTOS  (-) '));
                $pdf->SetX(70);
                $pdf->Cell(0, 5, utf8_decode('$   '));
                $pdf->SetX(70);
                $pdf->Cell(40, 5, number_format($detalleConsolidado[0]->total_descuento), 0, 0, 'R');
                $pdf->Ln();
                $pdf->Cell(0, 5, utf8_decode('TOTAL GENERAL '));
                $pdf->SetX(70);
                $pdf->Cell(0, 5, utf8_decode('$   '));
                $pdf->SetX(70);
                $pdf->Cell(40, 5, number_format($detalleConsolidado[0]->total_general), 0, 0, 'R');
                $pdf->Ln(5);
                $pdf->Ln();
                $pdf->Cell(0, 5, utf8_decode('MONTO TOTAL VENTAS '));
                $pdf->SetX(70);
                $pdf->Cell(0, 5, utf8_decode('$   '));
                $pdf->SetX(70);
                $pdf->Cell(40, 5, number_format($totalSistema[0]->monto_total_ventas), 0, 0, 'R');
                $pdf->Ln(10);
            }
        }

        $pdf->Ln();
        $pdf->SetX(88);
        $pdf->Cell(0, 5, utf8_decode('VENTAS POR FAMILIA'));
        $pdf->Ln();
        $pdf->Cell(0, 5, utf8_decode('____________________________________________________________________________________________________'));
        $pdf->Ln();
        foreach ($obtenerConfiguracionesRestoWeb as $validacion) {
            if ($validacion->desglozar_monto_neto_iva_total_en_reportes == 't') {
                $pdf->SetX(15);
                $pdf->Cell(0, 5, utf8_decode('FAMILIA'));
                $pdf->SetX(55);
                $pdf->Cell(0, 5, utf8_decode('CANTIDAD DE VENTA'));
                $pdf->SetX(105);
                $pdf->Cell(0, 5, utf8_decode('TOTAL NETO'));
                $pdf->SetX(140);
                $pdf->Cell(0, 5, utf8_decode('TOTAL IVA'));
                $pdf->SetX(170);
                $pdf->Cell(0, 5, utf8_decode('TOTAL VENTAS'));
                $pdf->Ln();
                $pdf->Cell(0, 5, utf8_decode('____________________________________________________________________________________________________'));
            } else {
                $pdf->SetX(15);
                $pdf->Cell(0, 5, utf8_decode('FAMILIA'));
                $pdf->SetX(90);
                $pdf->Cell(0, 5, utf8_decode('CANTIDAD DE VENTA'));
                $pdf->SetX(175);
                $pdf->Cell(0, 5, utf8_decode('TOTAL VENTAS'));
                $pdf->Ln();
                $pdf->Cell(0, 5, utf8_decode('____________________________________________________________________________________________________'));
            }
        }

        foreach ($datosVentasPorFamilia as $row) {
            if ($row->monto_total > 0) {
                foreach ($obtenerConfiguracionesRestoWeb as $validacion) {
                    if ($validacion->desglozar_monto_neto_iva_total_en_reportes == 't') {
                        $pdf->Ln();
                        $pdf->SetX(15);
                        $pdf->Cell(0, 5, utf8_decode($row->nombre_familia));
                        $pdf->SetX(70);
                        $pdf->Cell(0, 5, number_format($row->cantidad));
                        $pdf->SetX(100);
                        $pdf->Cell(23, 5, number_format($row->monto_neto), 0, 0, 'R');
                        $pdf->SetX(130);
                        $pdf->Cell(28, 5, number_format($row->monto_iva), 0, 0, 'R');
                        $pdf->SetX(155);
                        $pdf->Cell(35, 5, number_format($row->monto_total), 0, 0, 'R');
                        $pdf->Ln();
                        $pdf->Cell(0, 5, utf8_decode('____________________________________________________________________________________________________'));
                        $totalCantidadPorFamilia += $row->cantidad;
                        $totalNetoPorFamilia += $row->monto_neto;
                        $totaliVAPorFamilia += $row->monto_iva;
                        $totalPorFamilia += $row->monto_total;
                    } else {
                        $pdf->Ln();
                        $pdf->SetX(15);
                        $pdf->Cell(0, 5, utf8_decode($row->nombre_familia));
                        $pdf->SetX(107);
                        $pdf->Cell(0, 5, number_format($row->cantidad));
                        $pdf->SetX(162);
                        $pdf->Cell(35, 5, number_format($row->monto_total), 0, 0, 'R');
                        $pdf->Ln();
                        $pdf->Cell(0, 5, utf8_decode('____________________________________________________________________________________________________'));
                        $totalCantidadPorFamilia += $row->cantidad;
                        $totalPorFamilia += $row->monto_total;
                    }
                }
            }
        }

        foreach ($obtenerConfiguracionesRestoWeb as $validacion) {
            if ($validacion->desglozar_monto_neto_iva_total_en_reportes == 't') {
                $pdf->Ln(10);
                $pdf->SetX(15);
                $pdf->Cell(0, 5, utf8_decode('TOTALES'));
                $pdf->SetX(70);
                $pdf->Cell(40, 5, number_format($totalCantidadPorFamilia));
                $pdf->SetX(110);
                $pdf->Cell(23, 5, number_format($totalNetoPorFamilia));
                $pdf->SetX(145);
                $pdf->Cell(28, 5, number_format($totaliVAPorFamilia));
                $pdf->SetX(177);
                $pdf->Cell(35, 5, number_format($totalPorFamilia));
            } else {
                $pdf->Ln(10);
                $pdf->SetX(15);
                $pdf->Cell(0, 5, utf8_decode('TOTALES'));
                $pdf->SetX(107);
                $pdf->Cell(40, 5, number_format($totalCantidadPorFamilia));
                $pdf->SetX(185);
                $pdf->Cell(35, 5, number_format($totalPorFamilia));
            }
        }

        $pdf->Ln(15);
        $pdf->SetX(88);
        $pdf->Cell(0, 5, utf8_decode('VENTAS POR CANAL'));
        $pdf->Ln();
        $pdf->Cell(0, 5, utf8_decode('____________________________________________________________________________________________________'));
        $pdf->Ln();
        foreach ($obtenerConfiguracionesRestoWeb as $validacion) {
            if ($validacion->desglozar_monto_neto_iva_total_en_reportes == 't') {
                $pdf->SetX(15);
                $pdf->Cell(0, 5, utf8_decode('CANAL DE VENTA'));
                $pdf->SetX(55);
                $pdf->Cell(0, 5, utf8_decode('CANTIDAD VENTAS'));
                $pdf->SetX(105);
                $pdf->Cell(0, 5, utf8_decode('TOTAL NETO'));
                $pdf->SetX(140);
                $pdf->Cell(0, 5, utf8_decode('TOTAL IVA'));
                $pdf->SetX(170);
                $pdf->Cell(0, 5, utf8_decode('TOTAL VENTAS'));
                $pdf->Ln();
                $pdf->Cell(0, 5, utf8_decode('____________________________________________________________________________________________________'));
            } else {
                $pdf->SetX(15);
                $pdf->Cell(0, 5, utf8_decode('CANAL DE VENTA'));
                $pdf->SetX(93);
                $pdf->Cell(0, 5, utf8_decode('CANTIDAD VENTAS'));
                $pdf->SetX(175);
                $pdf->Cell(0, 5, utf8_decode('TOTAL VENTAS'));
                $pdf->Ln();
                $pdf->Cell(0, 5, utf8_decode('____________________________________________________________________________________________________'));
            }
        }
        foreach ($datosCanalDeVentas as $row) {
            if ($row->total_ventas > 0) {
                foreach ($obtenerConfiguracionesRestoWeb as $validacion) {
                    if ($validacion->desglozar_monto_neto_iva_total_en_reportes == 't') {
                        $pdf->Ln();
                        $pdf->SetX(15);
                        $pdf->Cell(0, 5, utf8_decode($row->nombre_canal_venta));
                        $pdf->SetX(70);
                        $pdf->Cell(0, 5, number_format($row->cantidad_ventas));
                        $pdf->SetX(100);
                        $pdf->Cell(23, 5, number_format($row->total_neto), 0, 0, 'R');
                        $pdf->SetX(130);
                        $pdf->Cell(28, 5, number_format($row->total_iva), 0, 0, 'R');
                        $pdf->SetX(157);
                        $pdf->Cell(35, 5, number_format($row->total_ventas), 0, 0, 'R');
                        $pdf->Ln();
                        $pdf->Cell(0, 5, utf8_decode('____________________________________________________________________________________________________'));
                        $totalCantidadPorCanalDeVentas += $row->cantidad_ventas;
                        $totalNetoPorCanalDeVentas += $row->total_neto;
                        $totaliVAPorCanalDeVentas += $row->total_iva;
                        $totalPorCanalDeVentas += $row->total_ventas;
                    } else {
                        $pdf->Ln();
                        $pdf->SetX(15);
                        $pdf->Cell(0, 5, utf8_decode($row->nombre_canal_venta));
                        $pdf->SetX(107);
                        $pdf->Cell(0, 5, number_format($row->cantidad_ventas));
                        $pdf->SetX(163);
                        $pdf->Cell(35, 5, number_format($row->total_ventas), 0, 0, 'R');
                        $pdf->Ln();
                        $pdf->Cell(0, 5, utf8_decode('____________________________________________________________________________________________________'));
                        $totalCantidadPorCanalDeVentas += $row->cantidad_ventas;
                        $totalPorCanalDeVentas += $row->total_ventas;
                    }
                }
            }
        }
        foreach ($obtenerConfiguracionesRestoWeb as $validacion) {
            if ($validacion->desglozar_monto_neto_iva_total_en_reportes == 't') {
                $pdf->Ln(10);
                $pdf->SetX(15);
                $pdf->Cell(0, 5, utf8_decode('TOTALES'));
                $pdf->SetX(70);
                $pdf->Cell(40, 5, number_format($totalCantidadPorCanalDeVentas));
                $pdf->SetX(110);
                $pdf->Cell(23, 5, number_format($totalNetoPorCanalDeVentas));
                $pdf->SetX(145);
                $pdf->Cell(28, 5, number_format($totaliVAPorCanalDeVentas));
                $pdf->SetX(177);
                $pdf->Cell(35, 5, number_format($totalPorCanalDeVentas));
            } else {
                $pdf->Ln(10);
                $pdf->SetX(15);
                $pdf->Cell(0, 5, utf8_decode('TOTALES'));
                $pdf->SetX(107);
                $pdf->Cell(40, 5, number_format($totalCantidadPorCanalDeVentas));
                $pdf->SetX(185);
                $pdf->Cell(35, 5, number_format($totalPorCanalDeVentas));
            }
        }

        //$pdf->Ln(170);
        //$pdf->SetX(75);
        //$pdf->Cell(0, 5, utf8_decode('SOLUCIONES INFORMATICAS  DATAMAULE.CL'));


        $pdf->Output('DetalleConsolidadoVentas Desde ' . $desde . ' Hasta ' . $hasta . '.pdf', 'I');
    } //fin pdf
}
