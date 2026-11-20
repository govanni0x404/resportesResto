<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mmesas extends CI_Model
{
  function __construct()
  {
    parent::__construct();
  }

  public function ListadoDeMesas()
  {
    if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url());
    }
    //$this->load->helper('tenant'); $this->db = get_tenant_db($this);
    $this->load->helper('tenant'); $this->db = get_tenant_db($this);
      
    // 19-11-2026: el tipo de mesa y el nombre con que sale en la Caja (Mtipomesa; sin la migración, "Mesa N")
    $this->load->model('Mtipomesa');
    $conTipo = $this->Mtipomesa->conMesa();
    // 19-11-2026 (auditoría): el nombre propio del plano de la Caja manda sobre el del tipo, como en la Caja
    $conPlano = (bool)$this->db->query("SELECT 1 FROM information_schema.columns WHERE table_schema = 'public'
      AND table_name = 'mesa_plano' AND column_name = 'nombre'")->row();
    $q = "SELECT m.id_mesa, m.disponible, m.bloqueo_caja, m.id_cotizacion, 
                 m.bloqueo_pedido, m.id_garzon, m.id_recinto, m.vigente,
                 " . ($conTipo ? $this->Mtipomesa->idSql('m') . " AS id_tipo_mesa, COALESCE(tm.nombre, '') AS tipo_mesa"
                    : "0 AS id_tipo_mesa, '' AS tipo_mesa") . ", " . ($conPlano ? "COALESCE(NULLIF(trim(pl.nombre), ''), " . $this->Mtipomesa->nombreSql('m', 'tm') . ")" : $this->Mtipomesa->nombreSql('m', 'tm')) . " AS nombre_caja
          FROM mesa m
          " . $this->Mtipomesa->joinSql('m', 'tm') . "
          " . ($conPlano ? 'LEFT JOIN mesa_plano pl ON pl.id_mesa = m.id_mesa' : '') . "
          ORDER BY m.id_mesa ASC";

    $consulta = $this->db->query($q);
    $sampleData = $consulta->result();
    $data = array();

    foreach ($sampleData as $key) {
      array_push($data, array(
        "id_mesa" => $key->id_mesa,
        "disponible" => $key->disponible,
        "bloqueo_caja" => $key->bloqueo_caja,
        "id_cotizacion" => $key->id_cotizacion,
        "bloqueo_pedido" => $key->bloqueo_pedido,
        "id_garzon" => $key->id_garzon,
        "id_recinto" => $key->id_recinto,
        "vigente" => $key->vigente,
        "id_tipo_mesa" => (int)$key->id_tipo_mesa,
        "tipo_mesa" => (string)$key->tipo_mesa,
        "nombre_caja" => (string)$key->nombre_caja,
      ));
    }
    
    $results = array(
      "sEcho" => 1,
      "iTotalRecords" => count($data),
      "iTotalDisplayRecords" => count($data),
      "aaData" => $data
    );

    return $results;
  }

  const MAX_POR_VEZ = 50;
  const MAX_MESAS = 300;

  public function crearMultiplesMesas($cantidad)
  {
    if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url());
    }
    //$this->load->helper('tenant'); $this->db = get_tenant_db($this);
    $this->load->helper('tenant'); $this->db = get_tenant_db($this);
    
    $resultados = array();
    $cantidad = max(1, min(self::MAX_POR_VEZ, (int)$cantidad));

    // Todo o nada, y con la tabla bloqueada para que dos pantallas a la vez no pasen el máximo
    $this->db->trans_begin();
    $this->db->query('LOCK TABLE mesa IN SHARE ROW EXCLUSIVE MODE');
    $hay = (int)$this->db->query('SELECT count(*) AS n FROM mesa')->row()->n;
    if ($hay + $cantidad > self::MAX_MESAS) {
      $this->db->trans_rollback();
      return array('error' => 'No se pueden tener más de ' . self::MAX_MESAS . ' mesas (hay ' . $hay . '). Reactiva mesas desactivadas en vez de crear nuevas.');
    }

    $this->load->model('Mtipomesa');
    $idTipo = $this->Mtipomesa->conMesa() ? $this->Mtipomesa->idParaGrabar(0) : null;
    for ($i = 0; $i < $cantidad; $i++) {
      $data = array(
        'disponible' => true,
        'bloqueo_caja' => false,
        'id_cotizacion' => 0,
        'bloqueo_pedido' => false,
        'id_garzon' => 0,
        'id_recinto' => 0,
        'vigente' => true
      );
      // v1.22.0: las mesas nuevas nacen con el tipo por defecto ("General")
      if ($idTipo) $data['id_tipo_mesa_web'] = $idTipo;
      
      $this->db->insert('mesa', $data);
      $consulta = $this->db->query("SELECT CURRVAL('mesa_id_mesa_seq'::regclass) as id_mesa");
      $resultado = $consulta->result();
      array_push($resultados, $resultado[0]);
    }
    if ($this->db->trans_status() === FALSE) {
      $this->db->trans_rollback();
      return array('error' => 'No se pudieron crear las mesas');
    }
    $this->db->trans_commit();

    return array(
      'success' => true,
      'cantidad_creada' => $cantidad,
      'mesas' => $resultados
    );
  }

  public function editarMesa($id, $data)
  {
      if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
          redirect(base_url());
      }
      //$this->load->helper('tenant'); $this->db = get_tenant_db($this);
      $this->load->helper('tenant'); $this->db = get_tenant_db($this);
      
      $this->db->where('id_mesa', $id);
      $exists = $this->db->get('mesa');
      
      if ($exists->num_rows() > 0) {
          // 14-11-2026: una mesa con pedido abierto no se desactiva (el pedido quedaba sin mesa visible)
          $m = $exists->row();
          $ocupada = (int)($m->id_cotizacion ?? 0) > 0 || (isset($m->disponible) && ($m->disponible === 'f' || $m->disponible === false));
          if (isset($data['vigente']) && $data['vigente'] === false && $ocupada) {
              return array('error' => 'La mesa ' . (int)$id . ' tiene un pedido abierto: ciérrala o cóbrala antes de desactivarla');
          }
          $this->db->where('id_mesa', $id);
          $this->db->update('mesa', $data);
          
          // Verificar si se actualizó
          if ($this->db->affected_rows() > 0) {
              error_log('Mesa actualizada correctamente. ID: ' . $id);
          } else {
              error_log('No se actualizó ninguna fila. ID: ' . $id);
          }
      } else {
          error_log('Mesa no encontrada. ID: ' . $id);
      }
      
      $consulta = $this->db->query("SELECT " . (int)$id . " as id_mesa");
      return $consulta->result();
  }

  /** v1.21.0: pone el mismo tipo de mesa (0 = sin tipo) a varias mesas, todo o nada. */
  public function cambiarTipo(array $ids, $idTipo)
  {
    $this->load->helper('tenant'); $this->db = get_tenant_db($this);
    $ids = array_map('intval', $ids);
    $this->db->trans_begin();
    $this->db->where_in('id_mesa', $ids);
    $this->load->model('Mtipomesa');
    $this->db->update('mesa', array('id_tipo_mesa_web' => $this->Mtipomesa->idParaGrabar($idTipo)));   // v1.22.0: 0 = el por defecto
    $n = $this->db->affected_rows();
    if ($this->db->trans_status() === FALSE) {
      $this->db->trans_rollback();
      return array('error' => 'No se pudieron cambiar las mesas');
    }
    $this->db->trans_commit();
    return array('ok' => true, 'cambiadas' => (int)$n);
  }
}
