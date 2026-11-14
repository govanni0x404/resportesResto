<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Usuario extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model(array('Musuarios'));
    }

    public function index()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        $data = array(
            'nombreUsuario' => $this->session->userdata('nombre_usuario'),
        );
        $this->load->view('menuTop.php', $data);
        $this->load->view('usuarios/usuarios.php');
        $this->load->view('menuFooter.php');
        $this->load->view('usuarios/listadoUsuarios.php');
        $this->load->view('usuarios/jsNuevoUsuario.php');
        $this->load->view('funciones.php');
    }

    public function ListadoDeUsuarios()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }

        try {
            $resultado = $this->Musuarios->ListadoDeUsuarios();
            echo json_encode($resultado);
        } catch (Exception $e) {
            echo json_encode(array(
                'error' => true,
                'message' => $e->getMessage()
            ));
        }
    }

    public function grabarNuevoUsuario()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }

        $d = $this->input->post('prod');
        if (!is_array($d)) { // sin datos del formulario: no se graba nada
        	echo json_encode(array('error' => 'Datos incompletos'));
        	return;
        }
        $id = intval($d['id_usuario'] ?? 0);
        $vigente_bool = (($d['vigente'] ?? '') === 'true' || ($d['vigente'] ?? '') === true || ($d['vigente'] ?? '') === 't');

        $miData = array(
            'nombre_usuario' => trim((string)($d['nombre_usuario'] ?? '')),
            'clave' => trim((string)($d['clave_usuario'] ?? '')),
            'id_rol' => intval($d['id_rol'] ?? 0),
            'vigente' => $vigente_bool
        );

        $err = $this->validarUsuario($id, $miData, $id <= 0);
        if ($err !== '') {
            echo json_encode(array('error' => $err));
            return;
        }

        if ($id > 0) {
            // Edición por la ruta antigua: no se tocan esadmin/esgarzon (antes se dejaban en false)
            $r = $this->Musuarios->editarUsuario($id, $miData);
        } else {
            $miData['esadmin'] = false;
            $miData['esgarzon'] = false;
            $r = $this->Musuarios->grabarNuevoUsuario(0, $miData);
        }
        echo json_encode($r);
    }

    public function editarUsuario()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }

        $d = $this->input->post('prod');
        if (!is_array($d) || intval($d['id_usuario'] ?? 0) <= 0) {
            echo json_encode(array('error' => 'Datos incompletos'));
            return;
        }
        $id = intval($d['id_usuario']);

        // Convertir vigente a boolean
        $vigente_value = $d['vigente'] ?? '';
        $vigente_bool = ($vigente_value === 'true' || $vigente_value === true || $vigente_value === 't');

        // Datos que ahora sí se pueden editar
        $miData = array(
            'nombre_usuario' => trim((string)($d['nombre_usuario'] ?? '')),
            'clave'          => trim((string)($d['clave_usuario'] ?? '')),
            'vigente'        => $vigente_bool
        );
        // Sin rol elegido (combo aún sin cargar) se conserva el rol actual en vez de grabar id_rol = 0
        if (intval($d['id_rol'] ?? 0) > 0) {
            $miData['id_rol'] = intval($d['id_rol']);
        }

        $err = $this->validarUsuario($id, $miData, false);
        if ($err !== '') {
            echo json_encode(array('error' => $err));
            return;
        }

        $r = $this->Musuarios->editarUsuario($id, $miData);
        echo json_encode($r);
    }

    /**
     * 14-11-2026: reglas para crear/editar usuarios (antes se grababa lo que llegara).
     *  - Nombre y clave obligatorios; en edición, clave vacía = se conserva la actual.
     *  - El rol debe existir en la tabla rol.
     *  - Solo gerencia (rol 1) puede dar el rol 1, editar a un usuario de gerencia o cambiar su propio rol.
     * Deja $data listo para grabar (quita la clave vacía en edición). Devuelve '' si está bien o el mensaje de error.
     */
    private function validarUsuario($id, array &$data, $esNuevo)
    {
        $this->load->helper('tenant');
        $db = get_tenant_db($this);
        $miRol = (int)$this->session->userdata('id_rol');
        $miId = (int)$this->session->userdata('id_usuario');

        if ($data['nombre_usuario'] === '') return 'El nombre de usuario es obligatorio';
        if (mb_strlen($data['nombre_usuario']) > 50) return 'El nombre de usuario es demasiado largo';
        if ($data['clave'] === '') {
            if ($esNuevo) return 'La clave es obligatoria';
            unset($data['clave']);
        }

        $actual = null;
        if (!$esNuevo) {
            $actual = $db->query('SELECT id_usuario, id_rol FROM usuario WHERE id_usuario = ?', array((int)$id))->row();
            if (!$actual) return 'El usuario no existe';
        }

        if (array_key_exists('id_rol', $data)) {
            $rol = (int)$data['id_rol'];
            if ($rol <= 0) return 'Debe elegir un rol';
            if (!$db->query('SELECT 1 FROM rol WHERE id_rol = ?', array($rol))->row()) return 'El rol elegido no existe';
        }

        if ($miRol !== 1) {
            if (isset($data['id_rol']) && (int)$data['id_rol'] === 1) return 'Solo gerencia puede asignar el rol de gerencia';
            if ($actual && (int)$actual->id_rol === 1) return 'Solo gerencia puede modificar a un usuario de gerencia';
            if ($actual && (int)$actual->id_usuario === $miId && isset($data['id_rol']) && (int)$data['id_rol'] !== (int)$actual->id_rol) {
                return 'No puede cambiar su propio rol';
            }
        }
        return '';
    }

    public function ListadoRoles()
    {
        $this->load->helper('tenant'); $db = get_tenant_db($this);

        $query = $db->query("SELECT id_rol, nombre_rol FROM rol ORDER BY nombre_rol ASC");

        echo json_encode($query->result());
    }
}