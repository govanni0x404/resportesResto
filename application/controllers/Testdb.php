<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Testdb extends CI_Controller {
    public function index() {
        $this->load->library('session');
        $this->session->set_userdata('nombre_conexion', 'ccerda_pruebasrestoweb');
        
        $this->load->helper('tenant');
        $db = get_tenant_db($this);
        
        if ($db->conn_id) {
            echo "Conexion Exitosa a " . $db->database;
        } else {
            echo "Fallo de conexion";
        }
    }
}
