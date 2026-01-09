<?php
// application/models/Configuracionesgenerales_model.php
defined('BASEPATH') OR exit('No direct script access allowed');

class MconfiguracionesGenerales extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function get_configuracion() {
        $query = $this->db->get_where('configuracion', array('id_configuracion' => 1));
        $row = $query->row_array();
        if (!$row) {
            return [];
        }
        return $row;
    }

    public function update_configuracion($data) {
        $this->db->where('id_configuracion', 1);
        return $this->db->update('configuracion', $data);
    }
}
?>