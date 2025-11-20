<?php
defined('BASEPATH') or exit('No direct script access allowed');

class VentasMensualesTotalizadas extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model(array('MventasMensualesTotalizadas', 'Mroles', 'Mconfiguraciones'));
    }

    public function index()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $data = array(
            'nombreUsuario' => $this->session->userdata('nombre_usuario'),
            //'modulosDisponibles' => json_decode($this->Mroles->modulosDisponibles($this->session->userdata('id_rol')), true),
        );
        $this->load->view('menuTop.php', $data);
        $this->load->view('ventasMensualesTotalizadas/ventasMensualesTotalizadas.php', $data);
        $this->load->view('menuFooter.php');
        $this->load->view('ventasMensualesTotalizadas/js.php', $data);
    }

    public function ventasMensualesTotalizadas()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $desde = $this->input->post('desde');
        $hasta = $this->input->post('hasta');
        echo json_encode($this->MventasMensualesTotalizadas->ventasMensualesTotalizadas($desde, $hasta));
    }

    public function ventasMensualesTotalizadasPie()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $desde = $this->input->post('desde');
        $hasta = $this->input->post('hasta');
        echo json_encode($this->MventasMensualesTotalizadas->ventasMensualesTotalizadasPie($desde, $hasta));
    }

    public function ventasMensualesTotalizadasTopDiezDiasMasVentas()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $desde = $this->input->post('desde');
        $hasta = $this->input->post('hasta');
        echo json_encode($this->MventasMensualesTotalizadas->ventasMensualesTotalizadasTopDiezDiasMasVentas($desde, $hasta));
    }

    public function ventasMensualesTotalizadasTopDiezDiasMenosVentas()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $desde = $this->input->post('desde');
        $hasta = $this->input->post('hasta');
        echo json_encode($this->MventasMensualesTotalizadas->ventasMensualesTotalizadasTopDiezDiasMenosVentas($desde, $hasta));
    }

    public function ResumenVentasDelDia()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $ventas_del_dia = $this->input->post('ventas_del_dia');
        echo json_encode($this->MventasMensualesTotalizadas->ResumenVentasDelDia($ventas_del_dia));
    }
}