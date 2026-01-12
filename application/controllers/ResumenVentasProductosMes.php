<?php
defined('BASEPATH') or exit('No direct script access allowed');

class ResumenVentasProductosMes extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model(array('MresumenVentasProductosMes'));
    }

    public function index()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $data = array(
            'nombreUsuario' => $this->session->userdata('nombre_usuario'),
        );
        
        $this->load->view('menuTop.php', $data);
        $this->load->view('resumenVentasProductosMes/resumenventasproductosmes.php');
        $this->load->view('menuFooter.php');
        $this->load->view('resumenVentasProductosMes/js.php');
    }

    public function resumenVentasProductos()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $desde = $this->input->post('desde');
        $hasta = $this->input->post('hasta');
        
        echo json_encode($this->MresumenVentasProductosMes->resumenVentasProductos($desde, $hasta));
    }

    public function detalleProductoMes()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $id_producto = $this->input->post('id_producto');
        $mes = $this->input->post('mes');
        
        echo json_encode($this->MresumenVentasProductosMes->detalleProductoMes($id_producto, $mes));
    }

    public function resumenVentasProductosPDF()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }

        $desde = $this->input->post('desde');
        $hasta = $this->input->post('hasta');

        $resumenVentas = $this->MresumenVentasProductosMes->resumenVentasProductosPDF($desde, $hasta);

        require('fpdf/fpdf.php');
        $pdf = new FPDF('L', 'mm', 'Legal');

        $pdf->AliasNbPages();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 14);

        $pdf->Cell(0, 10, utf8_decode('RESUMEN DE VENTAS POR PRODUCTO Y MES'), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 5, utf8_decode('Período: ' . $desde . ' al ' . $hasta), 0, 1, 'C');
        $pdf->Ln(5);

        // Cabecera de tabla
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(80, 7, 'Producto', 1, 0, 'C');
        $pdf->Cell(50, 7, 'Familia', 1, 0, 'C');
        $pdf->Cell(60, 7, 'Mes', 1, 0, 'C');
        $pdf->Cell(30, 7, 'Cantidad', 1, 0, 'C');
        $pdf->Cell(35, 7, 'Total', 1, 1, 'C');

        // Datos
        $pdf->SetFont('Arial', '', 8);
        $totalGeneral = 0;
        
        foreach ($resumenVentas as $row) {
            $pdf->Cell(80, 6, utf8_decode(substr($row->nombre_producto, 0, 40)), 1, 0, 'L');
            $pdf->Cell(50, 6, utf8_decode($row->nombre_familia), 1, 0, 'L');
            $pdf->Cell(60, 6, utf8_decode($row->mes), 1, 0, 'C');
            $pdf->Cell(30, 6, number_format($row->cantidad, 0), 1, 0, 'R');
            $pdf->Cell(35, 6, '$' . number_format($row->total, 0), 1, 1, 'R');
            
            $totalGeneral += $row->total;
        }

        // Total
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(220, 7, 'TOTAL GENERAL', 1, 0, 'R');
        $pdf->Cell(35, 7, '$' . number_format($totalGeneral, 0), 1, 1, 'R');

        $pdf->SetTitle("Resumen Ventas Productos Por Mes");
        
        ob_start();
        $pdf->Output('I', 'Resumen_Ventas_Productos_Mes.pdf');
        ob_end_flush();
    }
}