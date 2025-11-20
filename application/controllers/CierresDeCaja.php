<?php
defined('BASEPATH') or exit('No direct script access allowed');

class CierresDeCaja extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model(array('McierresDeCaja', 'Mroles'));
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
        $this->load->view('cierresdecaja/cierresdecaja.php');
        $this->load->view('menuFooter.php');
        $this->load->view('cierresdecaja/js.php');
    }

    public function tableCierresDeCaja()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $desde = $this->input->post('desde');
        $hasta = $this->input->post('hasta');
        echo json_encode($this->McierresDeCaja->tableCierresDeCaja($desde, $hasta));
    }

    public function cierreCajaPDF()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        $idCaja = $this->input->post('id_caja');
        $listadoCajas = $this->input->post('listaCajas');
        $desde = $this->input->post('desde');
        $hasta = $this->input->post('hasta');

        $datosEmpresa = $this->McierresDeCaja->datosEmpresa();
        $datosCaja = $this->McierresDeCaja->datosCaja($idCaja, $listadoCajas);
        $datosFormaPago = $this->McierresDeCaja->datosFormaPago($idCaja, $listadoCajas);
        $datosCortesias = $this->McierresDeCaja->datosCortesias($idCaja, $listadoCajas);
        $datosFamiliaProducto = $this->McierresDeCaja->datosFamiliaProducto($idCaja, $listadoCajas);
        $datosProductos = $this->McierresDeCaja->datosProductos($idCaja, $listadoCajas);
        $totalVentasProductos = $this->McierresDeCaja->totalVentasProductos($idCaja, $listadoCajas);
        $detalleCaja = $this->McierresDeCaja->detalleCaja($idCaja, $listadoCajas);
        $detalleCotizacion = $this->McierresDeCaja->detalleCotizacion($idCaja, $listadoCajas);
        $detallePropinas = $this->McierresDeCaja->detallePropinas($idCaja, $listadoCajas);
        $detalleAtencion = $this->McierresDeCaja->detalleAtencion($idCaja, $listadoCajas);

        $efectivo = 0;
        $transferencia = 0;
        $tarjetaDebito = 0;
        $tarjetaCredito = 0;
        $creditoConfianza = 0;
        $totalCajero = 0;
        $totalIngresos = 0;
        $totalEgresos = 0;
        $totalPropinas = 0;
        $totalAtencion = 0;

        foreach ($datosFormaPago as $row) {
            if ($row->id_fpago == 1) {
                $efectivo = $row->monto;
            }
            if ($row->id_fpago == 2) {
                $transferencia = $row->monto;
            }
            if ($row->id_fpago == 3) {
                $tarjetaCredito = $row->monto;
            }
            if ($row->id_fpago == 4) {
                $tarjetaDebito = $row->monto;
            }
            if ($row->id_fpago == 5) {
                $creditoConfianza = $row->monto;
            }
            $totalCajero += $row->monto;
        }

        $totalDetalleCajero = $totalCajero - $datosCaja[0]->sencillo_apertura + $datosCaja[0]->sencillo_cierre;
        $diferencia = $totalDetalleCajero - $datosCaja[0]->total_recaudacion;

        require ('fpdf/fpdf.php');

        $pdf = new FPDF('P', 'mm', 'Legal', 'Cierre De Caja');

        $pdf->AliasNbPages();
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 11);

        $pdf->Image(base_url() . 'assets/img/logo.png', 10, 5, 35, 35, 'PNG');

        // Cambiar la fuente a Arial, negrita, tamaño 12 para los encabezados
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetXY(75, 15);
        $pdf->SetTextColor(0, 128, 0);
        $pdf->Cell(0, 5, utf8_decode($datosEmpresa[0]->razon_social));

        $pdf->Ln(10);
        $pdf->SetTextColor(0, 0, 0);

        if ($idCaja > 0) {
            $pdf->SetX(80);
            $pdf->Cell(0, 5, utf8_decode('CUADRATURA DE CAJA '));
            $pdf->Ln();
            $pdf->SetX(85);
            $pdf->Cell(0, 5, utf8_decode('CAJA N°  00000' . $datosCaja[0]->id_caja));
            $pdf->Ln();
            $pdf->SetX(80);
            $pdf->Cell(0, 5, utf8_decode('TURNO  : ' . $datosCaja[0]->nombre_turno));
        } else if ($listadoCajas != null) {
            $pdf->SetX(82);
            $pdf->Cell(0, 5, utf8_decode('CUADRATURA DE CAJAS '));
            $pdf->Ln();
            $pdf->SetX(92);
            $pdf->Cell(0, 5, utf8_decode('CONSOLIDADA'));
        }
        $pdf->SetFont('Arial', '', 11);
        if ($idCaja > 0) {
            $pdf->Ln(15);
            $pdf->SetFont('Arial', 'B', 11);

            $pdf->Cell(0, 5, utf8_decode('NOMBRE CAJERO'));
            $pdf->SetX(50);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(0, 5, utf8_decode(':   ' . $datosCaja[0]->nombre_usuario));
            $pdf->Ln();
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(0, 5, utf8_decode('SUPERVISOR'));
            $pdf->SetX(50);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(0, 5, utf8_decode(':   ' . $datosCaja[0]->nombre_usuario));
            $pdf->Ln();
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(0, 5, utf8_decode('FECHA CAJA'));
            $pdf->SetX(50);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(0, 5, utf8_decode(':   ' . $datosCaja[0]->fecha_caja));
            $pdf->Ln();
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(0, 5, utf8_decode('FECHA APERTURA'));
            $pdf->SetX(50);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(0, 5, utf8_decode(':   ' . $datosCaja[0]->fecha_apertura));
            $pdf->Ln();
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(0, 5, utf8_decode('FECHA RENDICION'));
            $pdf->SetX(50);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(0, 5, utf8_decode(':   ' . $datosCaja[0]->fecha_rendicion));
        } else if ($listadoCajas != null) {
            $pdf->Ln(15);
            $pdf->SetX(10);
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(40, 5, utf8_decode('DESDE: ' . $desde), 1);
            $pdf->Ln();
            $pdf->Cell(40, 5, utf8_decode('HASTA : ' . $hasta), 1);
            $pdf->SetFont('Arial', '', 11);
        }
        $pdf->Ln(15);
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(60, 5, utf8_decode('VENTAS TURNO'), 1);
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(10, 5, utf8_decode('$'), 1, 0, 'C');
        $pdf->Cell(40, 5, number_format($datosCaja[0]->ventas), 1, 0, 'R');
        $pdf->Ln();
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(60, 5, utf8_decode('INGRESOS'), 1);
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(10, 5, utf8_decode('$'), 1, 0, 'C');
        $pdf->Cell(40, 5, number_format($datosCaja[0]->ingresos), 1, 0, 'R');
        $pdf->Ln();
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(60, 5, utf8_decode('EGRESOS'), 1);
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(10, 5, utf8_decode('$'), 1, 0, 'C');
        $pdf->Cell(40, 5, number_format($datosCaja[0]->egresos), 1, 0, 'R');
        $pdf->Ln(5);
        $pdf->Ln();
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(60, 5, utf8_decode('TOTAL RECAUDACION'), 1);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(10, 5, utf8_decode('$'), 1, 0, 'C');
        $pdf->Cell(40, 5, number_format($datosCaja[0]->total_recaudacion), 1, 0, 'R');
        $pdf->SetFont('Arial', '', 11);
        $pdf->Ln(15);
        $pdf->SetX(10);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 5, utf8_decode('DETALLE RENDICION CAJERO'));
        $pdf->Ln(8);
        $pdf->SetFont('Arial', '', 11);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(60, 5, utf8_decode('SENCILLO APERTURA '), 1);
        $pdf->SetX(70);
        $pdf->Cell(10, 5, utf8_decode('$'), 1, 0, 'C');
        $pdf->SetX(70);
        $pdf->Cell(50, 5, number_format($datosCaja[0]->sencillo_apertura), 1, 0, 'R');
        $pdf->Ln();
        $pdf->Cell(60, 5, utf8_decode('SENCILLO CIERRE '), 1);
        $pdf->SetX(70);
        $pdf->Cell(10, 5, utf8_decode('$'), 1, 0, 'C');
        $pdf->SetX(70);
        $pdf->Cell(50, 5, number_format($datosCaja[0]->sencillo_cierre), 1, 0, 'R');
        $pdf->Ln();
        $nfpago = "";
        foreach ($datosFormaPago as $row) {
            if ($row->id_fpago == 1) {
                $nfpago = "EFECTIVO A RENDIR";
                $pdf->Cell(60, 5, utf8_decode($nfpago), 1);
                $pdf->SetX(70);
            } else {
                $pdf->Cell(60, 5, utf8_decode($row->nombre_fpago), 1);
            }
            $pdf->Cell(10, 5, utf8_decode('$'), 1, 0, 'C');
            $pdf->Cell(40, 5, number_format($row->monto), 1, 0, 'R');
            $pdf->Ln();
        }
        $pdf->Ln();
        $pdf->SetFont('Arial', 'B', 11);

        // Establece el color de fondo en blanco (RGB: 255, 255, 255)
        $pdf->SetFillColor(255, 255, 255);

        // Establece el color del texto en rojo (RGB: 255, 0, 0)
        $pdf->SetTextColor(0, 0, 0);

        // Crea las celdas con el fondo blanco y el texto rojo
        $pdf->Cell(60, 5, utf8_decode('TOTAL'),1);
        $pdf->SetX(70);
        $pdf->Cell(10, 5, utf8_decode('$'), 1, 0, 'C', 1);
        $pdf->SetX(70);
        $pdf->Cell(50, 5, number_format($totalDetalleCajero), 1, 0, 'R');

        $pdf->Ln(8);
        $pdf->Cell(60, 5, utf8_decode('DIFERENCIA'), 1);
        $pdf->SetX(70);
        $pdf->Cell(10, 5, utf8_decode('$'), 1, 0, 'C');
        $pdf->SetX(70);
        $pdf->Cell(50, 5, number_format($diferencia), 1, 0, 'R');
        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Ln();
        $pdf->SetX(10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 5, utf8_decode('FORMAS DE PAGO'));
        $pdf->Ln(3);
        $pdf->SetFont('Arial', '', 11);
        $pdf->SetTextColor(0, 0, 0);
        foreach ($datosCortesias as $row) {
            if ($row->monto > 0 && $row->escortesia == 'f' and $row->vigente == 't') {
                $pdf->Ln();
                $pdf->Cell(60, 5, utf8_decode($row->nombre_fpago), 1);
                $pdf->SetX(70);
                $pdf->Cell(20, 5, utf8_decode('$'), 1, 0, 'C');
                $pdf->SetX(70);
                $pdf->Cell(50, 5, number_format($row->monto), 1, 0, 'R');
            }
        }
        $pdf->Ln();
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln();
        $pdf->SetX(10);
        $pdf->Cell(0, 5, utf8_decode('CORTESIAS'));
        $pdf->Ln(3);
        $pdf->SetFont('Arial', '', 11);
        $pdf->SetTextColor(0, 0, 0);
        foreach ($datosCortesias as $row) {
            if ($row->monto > 0 && $row->escortesia == 't' and $row->vigente == 't') {
                $pdf->Ln();
                $pdf->Cell(60, 5, utf8_decode($row->nombre_fpago), 1);
                $pdf->Cell(20, 5, utf8_decode('$'), 1, 0, 'C');
                $pdf->Cell(50, 5, number_format($row->monto), 1, 0, 'R');
                //$pdf->Ln();
            }
        }
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Ln();
        $pdf->Cell(60, 5, utf8_decode('TOTAL CORTESIAS'), 1);
        $pdf->Cell(20, 5, utf8_decode('$'), 1, 0, 'C');
        $pdf->Cell(30, 5, number_format($datosCaja[0]->cortesia), 1, 0, 'R');

        $pdf->Ln(10);
        $pdf->SetX(80);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 5, utf8_decode('VENTAS POR FAMILIA'));
        $pdf->SetFont('Arial', '', 11);
        $pdf->SetTextColor(0, 0, 0);

        foreach ($datosFamiliaProducto as $row) {
            $pdf->Ln();
            $pdf->SetX(30);
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(90, 5, substr(utf8_decode($row->nombre_familia), 0, 30), 1);
            $pdf->Cell(20, 5, utf8_decode('$'), 1, 0, 'C');
            $pdf->Cell(25, 5, number_format($row->total_familia), 1, 0, 'R');
            $pdf->SetFont('Arial', '', 11);

            foreach ($datosProductos as $row2) {
                if ($row2->id_familia == $row->id_familia) {
                    $pdf->Ln();
                    $pdf->SetX(30);
                    $pdf->Cell(90, 5, substr(utf8_decode($row2->nombre_producto), 0, 30), 1);
                    $pdf->Cell(15, 5, utf8_decode('X' . $row2->cantidad), 1);
                    $pdf->Cell(10, 5, utf8_decode('$'), 1, 0, 'C');
                    $pdf->Cell(20, 5, number_format($row2->total_producto), 1, 0, 'R');
                }
            }
        }

        $pdf->Ln();
        $pdf->Ln();
        $pdf->SetX(30);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(60, 5, utf8_decode('VENTAS'), 1);
        $pdf->Cell(20, 5, utf8_decode('$'), 1, 0, 'C');
        $pdf->Cell(55, 5, number_format($totalVentasProductos[0]->total_ventas), 1, 0, 'R');
        $pdf->Ln();
        $pdf->SetX(30);
        $pdf->Cell(60, 5, utf8_decode('DESCUENTOS'), 1);
        $pdf->Cell(20, 5, utf8_decode('$'), 1, 0, 'C');
        $pdf->Cell(55, 5, number_format($totalVentasProductos[0]->descuentos), 1, 0, 'R');
        $pdf->Ln();
        $pdf->SetX(30);
        $pdf->Cell(60, 5, utf8_decode('CORTESIAS'), 1);
        $pdf->Cell(20, 5, utf8_decode('$'), 1, 0, 'C');
        $pdf->Cell(55, 5, number_format($datosCaja[0]->cortesia), 1, 0, 'R');
        $pdf->Ln();
        $pdf->SetX(30);
        $pdf->Cell(60, 5, utf8_decode('TOTAL VENTAS'), 1);
        $pdf->Cell(20, 5, utf8_decode('$'), 1, 0, 'C');
        $pdf->Cell(55, 5, number_format($totalVentasProductos[0]->total_ventas - $datosCaja[0]->cortesia - $totalVentasProductos[0]->descuentos), 1, 0, 'R');
        $pdf->Ln();

        $pdf->Ln(10);
        if ($detalleCaja != null) {
            $pdf->Ln();
            $pdf->SetX(80);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(0, 5, utf8_decode('DETALLE DE INGRESOS'));
            $pdf->SetFont('Arial', '', 11);
            $pdf->SetTextColor(0, 0, 0);
            foreach ($detalleCaja as $row) {
                if ($row->ingreso > 0) {
                    $pdf->Ln();
                    $pdf->SetX(30);
                    $pdf->Cell(60, 5, substr(utf8_decode($row->observacion), 0, 30), 1);
                    $pdf->Cell(20, 5, utf8_decode('$'), 1, 0, 'C');
                    $pdf->Cell(55, 5, number_format($row->ingreso), 1, 0, 'R');
                }
                $totalIngresos += $row->ingreso;
            }
            $pdf->Ln(10);
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->SetX(30);
            $pdf->Cell(60, 5, utf8_decode('TOTAL INGRESOS'), 1);
            $pdf->Cell(20, 5, utf8_decode('$'), 1, 0, 'C');
            $pdf->Cell(55, 5, number_format($totalIngresos), 1, 0, 'R');

            $pdf->Ln(10);
            $pdf->SetX(80);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(0, 5, utf8_decode('DETALLE DE RETIROS'));
            foreach ($detalleCaja as $row) {
                if ($row->egreso > 0) {
                    $pdf->SetFont('Arial', '', 11);
                    $pdf->SetTextColor(0, 0, 0);
                    $pdf->Ln();
                    $pdf->SetX(30);
                    $pdf->Cell(60, 5, substr(utf8_decode($row->observacion), 0, 30), 1);
                    $pdf->Cell(20, 5, utf8_decode('$'), 1, 0, 'C');
                    $pdf->Cell(55, 5, number_format($row->egreso), 1, 0, 'R');
                }
                $totalEgresos += $row->egreso;
            }
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Ln(10);
            $pdf->SetX(30);
            $pdf->Cell(60, 5, utf8_decode('TOTAL RETIROS'), 1);
            $pdf->Cell(20, 5, utf8_decode('$'), 1, 0, 'C');
            $pdf->Cell(55, 5, number_format($totalEgresos), 1, 0, 'R');
        }
        $pdf->Ln(10);
        if ($detalleCotizacion != null) {
            $pdf->Ln();
            $pdf->SetX(78);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(0, 5, utf8_decode('DETALLE DE ANULACIONES'));
            $pdf->Ln();
            $pdf->SetFont('Arial', '', 11);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetX(30);
            $pdf->Cell(80, 5, utf8_decode('PRODUCTO'), 1);
            $pdf->Cell(18, 5, utf8_decode('HORA'), 1);
            $pdf->Cell(15, 5, utf8_decode('MESA'), 1);
            $pdf->Cell(22, 5, utf8_decode('CANTIDAD'), 1);
            foreach ($detalleCotizacion as $row) {
                if ($row->esanulado == 't') {
                    $pdf->Ln();
                    $pdf->SetX(30);
                    $pdf->Cell(80, 5, substr(utf8_decode($row->nombre_producto), 0, 25), 1);
                    $pdf->Cell(18, 5, substr(utf8_decode($row->hora), 0, 25), 1);
                    $pdf->Cell(15, 5, substr(utf8_decode($row->id_mesa), 0, 25), 1, 0, 'C');
                    $pdf->Cell(22, 5, substr(utf8_decode($row->cantidad), 0, 25), 1, 0, 'C');
                }
            }
            $pdf->Ln();
            $pdf->Ln();
            $pdf->SetX(78);
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(0, 5, utf8_decode('DETALLE DE ELIMINACIONES'));
            $pdf->Ln();
            $pdf->SetFont('Arial', '', 11);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetX(30);
            $pdf->Cell(80, 5, utf8_decode('PRODUCTO'), 1);
            $pdf->Cell(18, 5, utf8_decode('HORA'), 1);
            $pdf->Cell(15, 5, utf8_decode('MESA'), 1);
            $pdf->Cell(22, 5, utf8_decode('CANTIDAD'), 1);
            foreach ($detalleCotizacion as $row) {
                if ($row->eseliminado == 't') {
                    $pdf->Ln();
                    $pdf->SetX(30);
                    $pdf->Cell(80, 5, substr(utf8_decode($row->nombre_producto), 0, 25), 1);
                    $pdf->Cell(18, 5, utf8_decode($row->hora), 1);
                    $pdf->Cell(15, 5, utf8_decode($row->id_mesa), 1, 0, 'C');
                    $pdf->Cell(22, 5, utf8_decode($row->cantidad), 1, 0, 'C');
                }
            }
            $pdf->Ln(10);
        }
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Ln();
        $pdf->SetX(80);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 5, utf8_decode('DETALLE DE PROPINAS'));
        $pdf->SetFont('Arial', '', 11);
        $pdf->SetTextColor(0, 0, 0);
        $totalPropinas = 0;
        foreach ($detallePropinas as $row) {
            if ($row->propina > 0) {
                $pdf->Ln();
                $pdf->SetX(30);
                $pdf->Cell(60, 5, utf8_decode($row->nombre_usuario), 1);
                $pdf->Cell(20, 5, utf8_decode('$'), 1, 0, 'C');
                $pdf->Cell(55, 5, number_format($row->propina), 1, 0, 'R');
            }
            $totalPropinas += $row->propina;
        }

        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetX(30);
        $pdf->Cell(60, 5, utf8_decode('TOTAL PROPINAS'), 1);
        $pdf->Cell(20, 5, utf8_decode('$'), 1, 0, 'C');
        $pdf->Cell(55, 5, number_format($totalPropinas), 1, 0, 'R');


        $pdf->Ln(10);
        if ($detalleAtencion != null) {
            $pdf->Ln();
            $pdf->SetX(80);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(0, 5, utf8_decode('DETALLE DE ATENCION'));
            $pdf->Ln();
            $pdf->SetFont('Arial', '', 11);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetX(30);
            $pdf->Cell(65, 5, utf8_decode('GARZON'), 1);
            $pdf->Cell(15, 5, utf8_decode('MESAS'), 1);
            $pdf->Cell(26, 5, utf8_decode('VENTAS'), 1);
            $pdf->Cell(30, 5, utf8_decode('T.PROM.ES'), 1);
            foreach ($detalleAtencion as $row) {
                if ($row->venta_directa == 'f') {
                    $pdf->Ln();
                    $pdf->SetX(30);
                    $pdf->Cell(65, 5, utf8_decode($row->nombre_usuario), 1);
                    $pdf->Cell(15, 5, number_format($row->mesas_atendidas), 1);
                    $pdf->Cell(26, 5, number_format($row->ventas), 1);
                    $pdf->Cell(30, 5, utf8_decode($row->promedio_atencion), 1);
                    $totalAtencion += $row->ventas;
                }
            }
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Ln(10);
            $pdf->SetX(30);
            $pdf->Cell(60, 5, utf8_decode('TOTAL VENTAS'), 1);
            $pdf->Cell(20, 5, utf8_decode('$'), 1, 0, 'C');
            $pdf->Cell(55, 5, number_format($totalAtencion), 1, 0, 'C');
        }


        $pdf->Ln(20);
        $pdf->SetX(45);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 5, utf8_decode('*****************************************************************************'));
        $pdf->Ln();
        $pdf->SetX(75);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 5, utf8_decode('* Sr. cliente favor compare formas de'));
        $pdf->Ln();
        $pdf->SetX(83);
        $pdf->Cell(0, 5, utf8_decode('pago respecto a lo recibido.'));
        $pdf->Ln();
        $pdf->SetX(45);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 5, utf8_decode('*****************************************************************************'));
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(20);
        $pdf->SetX(105);
        $pdf->Cell(0, 5, utf8_decode('CAJA'));
        $pdf->Ln();
        $pdf->SetX(95);
        $pdf->Cell(0, 5, utf8_decode('FIRMA CAJERO'));

        $pdf->Ln(20);
        $pdf->SetX(63);
        $pdf->Cell(0, 5, utf8_decode('SOLUCIONES INFORMATICAS DATAMAULE SPA'));

        //------------------------------------------------------//
        if ($idCaja > 0) {
            $pdf->SetTitle("Cierre de Caja N.  00000" . $datosCaja[0]->id_caja);

            ob_start();
            $pdf->Output('I', 'Cierre de Caja N.  00000' . $datosCaja[0]->id_caja . '.pdf');
            ob_end_flush();
        } else if ($listadoCajas != null) {
            $pdf->SetTitle("Cierre de Caja Consolidado");

            ob_start();
            $pdf->Output('I', 'Cierre de Caja Consolidado.pdf');
            ob_end_flush();
        }
    }
}
