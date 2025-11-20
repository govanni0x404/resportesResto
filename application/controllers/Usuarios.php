<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Usuarios extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        // Solo cargamos los modelos necesarios para la BD local
        $this->load->model(array('Musuarios', 'Mroles')); 
    }

    public function index()
    {
        if ($this->session->userdata('nombre_usuario')) {
            redirect(base_url()); //redireccionar al login
        }
        
        $id_rol = $this->session->userdata('id_rol') ? $this->session->userdata('id_rol') : 0; 
        $data = array(
            'nombreUsuario' => $this->session->userdata('nombre_usuario'),
            'modulosDisponibles' => json_decode($this->Mroles->modulosDisponibles($id_rol), true)
        );

        if ($this->session->userdata('nombre_usuario')) {
            $roles_permitidos = [1, 2, 3]; 
            $rol_acceso = (int)$this->session->userdata('id_rol');
            
            if (in_array($rol_acceso, $roles_permitidos)) { 
                $this->load->view('menuTop.php', $data);
                $this->load->view('usuarios/usuarios.php');
                $this->load->view('menuFooter.php');
                $this->load->view('usuarios/js.php');
            } else {
                redirect(base_url());
            }
        }
    }

    // LISTAR USUARIOS DEL SISTEMA (Local) - No requiere cambios.
    public function ListarUsuariosDelSistema()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        echo json_encode($this->Musuarios->ListarUsuariosDelSistema());
    }

    // LISTAR ROLES DE USUARIOS (Local) - No requiere cambios.
    public function ListarRolesUsuarios()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        $id_rol = $this->input->post('id_rol');
        echo json_encode($this->Musuarios->ListarRolesUsuarios($id_rol));
    }

    // GRABAR USUARIOS (Local)
    public function grabarUsuariosLocal()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        // Obtenemos solo los datos relevantes para la BD Local desde 'prodLocal'
        $d = $this->input->post('prodLocal');
        $id = $this->input->post('id_usuario'); // Usamos el ID enviado suelto (para UPDATE)

        $miData = array(
            'nombre_usuario' => $d['nombre_usuario'],
            'clave' => $d['clave'],
            'esadmin' => $d['esadmin'],
            'esgarzon' => $d['esgarzon'],
            'id_rol' => $d['id_rol'],
            'vigente' => $d['vigente'],
        );
        
        // Llamamos al modelo para grabar/actualizar
        $r = $this->Musuarios->grabarUsuariosLocal($id, $miData);
        
        // Devolvemos la respuesta (contiene el ID, nuevo o existente)
        echo json_encode($r);
    }
    
    // ELIMINAMOS EL MÉTODO grabarUsuariosIntranet
}

	/*public function grabarUsuariosIntranet()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		// $d = $this->input->post('prodLocal');
		$i = $this->input->post('prodIntranet');
		//$id = intval($d['id_usuario']);
		$id =  $this->input->post('id_usuario_cliente');
		$idCliente =  $this->input->post('id_cliente');

		$miDataIntranet = array(
			'correo_acceso' => $i['correo_acceso'],
			'clave_acceso' => $i['clave_acceso'],
			'nombre_usuario' => $i['nombre_usuario'],
			'id_cliente' => $idCliente,
			'id_usuario_cliente' => $id,
			//'id_usuario_cliente'=>$i['id_usuario_cliente'],
			'nombre_conexion' => $this->session->userdata('rut_cliente'),
			'id_software' => 5,
			'autoriza_cambio_conexion' => $i['autoriza_cambio_conexion'],
		);
		$t = $this->Musuarios->grabarUsuariosIntranet($id, $miDataIntranet, $idCliente);
		echo json_encode($t);
	}*/

