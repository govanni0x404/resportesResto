<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MuMedida extends CI_Model
{
  function __construct()
  {
    parent::__construct();
  }

  public function ListadoDeUmedida()
  {
    if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $this->load->helper('tenant'); $this->db = get_tenant_db($this);
    // v1.34.0: con su equivalencia (tipo y factor), si la migración 20261124130000 ya corrió
    $data = array();
    foreach ($this->equivalencias() as $u) {
      $data[] = array(
        "id_umedida" => $u['id_umedida'],
        "nombre_umedida" => $u['nombre_umedida'],
        "tipo" => $u['tipo'],
        "factor" => $u['factor'],
        "equivalencia" => $u['clave'],
        "equivale" => self::textoEquivalencia($u['tipo'], $u['factor']),
      );
    }
    $results = array(
      "sEcho" => 1,
      "iTotalRecords" => count($data),
      "iTotalDisplayRecords" => count($data),
      "aaData" => $data
    );

    return $results;
  }

  // ---------------- v1.34.0: equivalencias (1 KILOGRAMO = 1000 GRAMO) ----------------

  /** Equivalencias que se pueden elegir: clave => array(tipo, factor en la unidad base: gramos, mililitros o unidades). */
  const EQUIVALENCIAS = array(
    'kilo' => array('peso', 1000),
    'gramo' => array('peso', 1),
    'litro' => array('volumen', 1000),
    'mililitro' => array('volumen', 1),
    'unidad' => array('unidad', 1),
  );

  /** Nombres que se reconocen solos (igual que la migración 20261124130000). */
  const NOMBRES = array(
    'kilo' => array('KILOGRAMO', 'KILOGRAMOS', 'KILO', 'KILOS', 'KG', 'KGS', 'KGR'),
    'gramo' => array('GRAMO', 'GRAMOS', 'GR', 'GRS', 'G', 'GRAMAJE'),
    'litro' => array('LITRO', 'LITROS', 'LT', 'LTS', 'L', 'LTR'),
    'mililitro' => array('MILILITRO', 'MILILITROS', 'ML', 'MLS', 'CC', 'CM3'),
    'unidad' => array('UNIDAD', 'UNIDADES', 'UN', 'UND', 'UNID', 'U', 'UNI'),
  );

  /** La equivalencia que corresponde al nombre ('kilo', 'gramo'...) o '' si no se reconoce. */
  public static function equivalenciaPorNombre($nombre)
  {
    $n = preg_replace('/[.\s]+/u', '', mb_strtoupper(trim((string)$nombre)));
    foreach (self::NOMBRES as $clave => $nombres) if (in_array($n, $nombres, true)) return $clave;
    return '';
  }

  /** Clave de la equivalencia para un tipo y factor ('' si no tiene). */
  public static function claveEquivalencia($tipo, $factor)
  {
    foreach (self::EQUIVALENCIAS as $clave => $e) {
      if ($e[0] === $tipo && abs((float)$factor - $e[1]) < 1e-9) return $clave;
    }
    return '';
  }

  /** Texto para la pantalla: "1.000 gramos", "1 mililitro"... ('' sin equivalencia). */
  public static function textoEquivalencia($tipo, $factor)
  {
    if (!$tipo || !$factor) return '';
    $base = array('peso' => array('gramo', 'gramos'), 'volumen' => array('mililitro', 'mililitros'), 'unidad' => array('unidad', 'unidades'));
    if (!isset($base[$tipo])) return '';
    $f = (float)$factor;
    return rtrim(rtrim(number_format($f, 3, ',', '.'), '0'), ',') . ' ' . $base[$tipo][$f == 1 ? 0 : 1];
  }

  /** ¿Ya corrió la migración 20261124130000? */
  public function conEquivalencias()
  {
    $this->load->helper('tenant'); $db = get_tenant_db($this);
    $r = $db->query("SELECT count(*) AS n FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'umedida'
      AND column_name IN ('tipo_web', 'factor_web')")->row();
    return $r && (int)$r->n === 2;
  }

  /**
   * Todas las unidades con su equivalencia: [{id_umedida, nombre_umedida, tipo, factor, clave}]. Sin la migración, la
   * equivalencia sale del nombre (así la pantalla igual convierte kilos y gramos).
   */
  public function equivalencias()
  {
    $this->load->helper('tenant'); $db = get_tenant_db($this);
    $con = $this->conEquivalencias();
    $filas = $db->query('SELECT id_umedida, nombre_umedida' . ($con ? ', tipo_web, factor_web' : '') . ' FROM umedida ORDER BY id_umedida')->result();
    $res = array();
    foreach ($filas as $f) {
      $tipo = $con ? (string)$f->tipo_web : '';
      $factor = $con ? (float)$f->factor_web : 0;
      if (!$con) {
        $c = self::equivalenciaPorNombre($f->nombre_umedida);
        if ($c !== '') list($tipo, $factor) = self::EQUIVALENCIAS[$c];
      }
      if (!$tipo || $factor <= 0) { $tipo = ''; $factor = 0; }
      $res[] = array('id_umedida' => (int)$f->id_umedida, 'nombre_umedida' => (string)$f->nombre_umedida,
        'tipo' => $tipo ?: null, 'factor' => $factor > 0 ? $factor : null, 'clave' => self::claveEquivalencia($tipo, $factor));
    }
    return $res;
  }

  /** Equivalencia de una unidad por id: array(tipo, factor) o null. */
  public function equivalenciaDe($idUmedida)
  {
    foreach ($this->equivalencias() as $u) {
      if ($u['id_umedida'] === (int)$idUmedida) return $u['tipo'] && $u['factor'] ? array($u['tipo'], (float)$u['factor']) : null;
    }
    return null;
  }

  public function grabarNuevaUmedida($id, $data)
  {
    if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
      redirect(base_url()); //redireccionar al login
    }
    $this->load->helper('tenant'); $this->db = get_tenant_db($this);
    if ($id) {
      $this->db->where('id_umedida', $id);
      $this->db->update('umedida', $data);
      // 16-11-2026 (QA): si no se actualizó ninguna fila el registro no existe: error (404) y no "éxito"
      if ($this->db->affected_rows() < 1) {
      	set_status_header(404);
      	return array('error' => 'El registro ya no existe (pudo haberlo borrado otra persona). Recarga la página.');
      }
      $consulta = $this->db->query("select " . (int)$id . " as id_umedida");
    } else {
      $this->db->insert('umedida', $data);
      $consulta = $this->db->query("SELECT CURRVAL('umedida_id_umedida_seq'::regclass) as id_umedida");
    }
    return $consulta->result();
  }
}