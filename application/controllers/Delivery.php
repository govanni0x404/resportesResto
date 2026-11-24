<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Delivery extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model(array('Mdelivery', 'Mroles'));
    }

    public function index()
    {
        $this->exigirAdministracionSectores();
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $data = array(
            'nombreUsuario' => $this->session->userdata('nombre_usuario'),
            //'modulosDisponibles' => json_decode($this->Mroles->modulosDisponibles($this->session->userdata('id_rol')), true),
            //'verificarSiEsAdmin' => json_decode($this->Mroles->verificarSiEsAdmin($this->session->userdata('id_rol')), true)
        );
        $this->load->view('menuTop.php', $data);
        $this->load->view('delivery/sector.php');
        $this->load->view('menuFooter.php');
        $this->load->view('delivery/listadoDeSector.php');
        $this->load->view('delivery/jsNuevoSector.php');
        $this->load->view('funciones.php');
    }

    public function ListadoDeSectores()
    {
        $this->exigirAdministracionSectores();
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        echo json_encode($this->Mdelivery->ListadoDeSectores());
    }

    public function grabarNuevoSector()
    {
        $this->exigirAdministracionSectores();
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        $d = $this->input->post('prod');
        if (!is_array($d)) { // sin datos del formulario: no se graba nada
        	echo json_encode(array('error' => 'Datos incompletos'));
        	return;
        }
        $id = intval($d['id_sector']);
        $miData = array(
            'nombre_sector' => $d['nombre_sector'],
        );
        $r = $this->Mdelivery->grabarNuevoSector($id, $miData);
        echo json_encode($r);
    }

    /** El permiso Delivery permite usar el portal móvil; administrar sectores requiere permiso de Configuraciones. */
    private function exigirAdministracionSectores()
    {
        $idRol = (int)$this->session->userdata('id_rol');
        if ($idRol === 1) return;
        $this->load->helper('permisos_web');
        if (!permiso_web($this, 'Configuraciones', $idRol)) {
            show_error('No tienes permiso para administrar los sectores de Delivery.', 403);
            exit;
        }
    }
}
