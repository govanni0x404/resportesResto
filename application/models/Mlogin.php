<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mlogin extends CI_Model
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('session');
	}

	public function getListaDeClientesParaElUsuario(){
		if (strlen($this->session->userdata('rut_cliente'))>0) {
			$this->db=$this->load->database('default', TRUE);
			$q="select u.*,c.nombre_cliente,c.nombre_fantasia
					from usuario_appweb u
					join cliente c on u.id_cliente=c.id_cliente
					where u.id_software=".$this->session->userdata('id_software')." and correo_acceso='".$this->session->userdata('correo_acceso')."'";
			return json_encode($consulta=$this->db->query($q)->result());

		}
	}

	public function obtenerListaConexionesUsuario($idUsuario){
		if (strlen($this->session->userdata('rut_cliente'))>0) {
			$this->db=$this->load->database('default', TRUE);
			$q="select  u.nombre_conexion from usuario_appweb u where u.id_cliente=$idUsuario group by u.nombre_conexion order by 1";
			return json_encode($consulta=$this->db->query($q)->result());
		}
	}

	public function login_user($username, $password,$id_software)
	{

		$this->db=$this->load->database('default', TRUE);
    //$this->db->select('usuario.*','cliente.*');
    $this->db->select('*');
    //$this->db->select('usuario.*, rol.nombre_rol');
    $this->db->from('usuario_appweb');
    $this->db->join('cliente', 'usuario_appweb.id_cliente = cliente.id_cliente');
		$this->db->join('software', 'usuario_appweb.id_software = software.id_software');
    $this->db->where('usuario_appweb.correo_acceso',$username);
    $this->db->where('usuario_appweb.clave_acceso',$password);
		$this->db->where('software.id_software',$id_software);

    $this->db->order_by('id_usuario_appweb');
    $this->db->limit(1);

    $query = $this->db->get();
		if ($query->num_rows() == 1) {
			return $query->row();
		} else {
			$this->session->set_flashdata('usuario_incorrecto', 'Los datos introducidos son incorrectos');
			redirect(base_url(), 'refresh');
		}

	}

	public function relogin_user($idEmpresa,$username,$id_software){// recibo el id empresa y correo de acceso
		$this->db=$this->load->database('default', TRUE);
		$this->db->select('*');
		$this->db->from('usuario_appweb');
		$this->db->join('cliente', 'usuario_appweb.id_cliente = cliente.id_cliente');
		$this->db->join('software', 'usuario_appweb.id_software = software.id_software');
		$this->db->where('usuario_appweb.correo_acceso',$username);
		$this->db->where('usuario_appweb.id_cliente',$idEmpresa);
		$this->db->where('software.id_software',$id_software);
		$this->db->order_by('id_usuario_appweb');
		$this->db->limit(1);

		$query = $this->db->get();
		if($query->num_rows() == 1)
		{
			return $query->row();
		}else{
			$this->session->set_flashdata('usuario_incorrecto','Los datos introducidos son incorrectos');
			redirect(base_url(),'refresh');
		}
	}


	public function obtenerSucursal($idCliente){
		$this->db=$this->load->database('default', TRUE);
		$q="select * from sucursal s where s.id_cliente=$idCliente order by 1 limit 1";
		$query = $this->db->query($q);
		return $query->row();
	}
	public function getNombreUsuario($idUsuario){
		if (strlen($this->session->userdata('rut_cliente'))>0) {
			$this->load->helper('tenant'); $this->db = get_tenant_db($this);
			//$this->load->helper('tenant'); $this->db = get_tenant_db($this);
			$query = $this->db->query("select * from usuario where id_usuario=$idUsuario");
			return $query->row();

		}
	}
	// public function login_dte()
	// {
	// 	$this->db = $this->load->database($this->session->userdata('rut_cliente'), TRUE);
	// 	$query = $this->db->query('select e.*, c.nombre_comuna,
	// 	(select co.usuario_dte from configuracion co) as user_dte,
	// 	(select co.clave_usuario_dte from configuracion co) as pass_dte
	// 	from empresa e inner join comuna c on c.id_comuna = e.id_comuna');
	// 	return $query->row();
	// }

	// public function getNombreUsuario($idUsuario)
	// {
	// 	if (strlen($this->session->userdata('rut_cliente')) > 0) {
	// 		$this->db = $this->load->database($this->session->userdata('rut_cliente'), TRUE);
	// 		$query = $this->db->query("select * from usuario where id_usuario=$idUsuario");
	// 		return $query->row();
	// 	}
	// }
}
