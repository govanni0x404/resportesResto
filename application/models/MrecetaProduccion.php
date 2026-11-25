<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MrecetaProduccion extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function ListadoRecetasProduccion()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        
        $q = "SELECT 
                rp.id_receta_produccion,
                rp.nombre_receta_produccion,
                rp.cantidad,
                i.nombre_insumo as insumo_elaborado,
                um.nombre_umedida,
                rp.id_insumo,
                rp.id_bodega,
                rp.id_umedida,
                rp.vigente
            FROM receta_produccion rp
            LEFT JOIN insumo i ON rp.id_insumo = i.id_insumo
            LEFT JOIN umedida um ON rp.id_umedida = um.id_umedida
            WHERE rp.vigente = true
            ORDER BY rp.nombre_receta_produccion ASC";

        $consulta = $this->db->query($q);
        $sampleData = $consulta->result();
        $data = array();

        foreach ($sampleData as $key) {
            array_push($data, array(
                "id_receta_produccion" => $key->id_receta_produccion,
                "nombre_receta_produccion" => $key->nombre_receta_produccion,
                "cantidad" => $key->cantidad,
                "insumo_elaborado" => $key->insumo_elaborado,
                "nombre_umedida" => $key->nombre_umedida,
                "id_insumo" => $key->id_insumo,
                "id_bodega" => $key->id_bodega,
                "id_umedida" => $key->id_umedida,
                "responsive" => ""
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

    public function grabarRecetaProduccion($id, $data)
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        
        if ($id && $id > 0) {
            // Actualizar
            $this->db->where('id_receta_produccion', $id);
            $this->db->update('receta_produccion', $data);
            return array(
                array('id_receta_produccion' => $id)
            );
        } else {
            // Insertar
            $this->db->insert('receta_produccion', $data);
            $consulta = $this->db->query("SELECT CURRVAL('receta_produccion_id_receta_produccion_seq'::regclass) as id_receta_produccion");
            return $consulta->result_array();
        }
    }

    public function buscarRecetaPorNombre($nombre)
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        
        $q = "SELECT * FROM receta_produccion 
              WHERE UPPER(nombre_receta_produccion) = UPPER(?)
              AND vigente = true";
        
        $consulta = $this->db->query($q, array($nombre));
        return $consulta->result();
    }

    public function obtenerDetalleReceta($id_receta_produccion)
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        
        $q = "SELECT 
                drp.id_detalle_receta_produccion,
                drp.id_receta_produccion,
                drp.id_insumo,
                i.nombre_insumo,
                drp.cantidad,
                drp.id_umedida,
                um.nombre_umedida
            FROM detalle_receta_produccion drp
            JOIN insumo i ON drp.id_insumo = i.id_insumo
            JOIN umedida um ON drp.id_umedida = um.id_umedida
            WHERE drp.id_receta_produccion = " . intval($id_receta_produccion);

        $consulta = $this->db->query($q);
        $sampleData = $consulta->result();
        $data = array();

        foreach ($sampleData as $key) {
            array_push($data, array(
                "id_detalle_receta_produccion" => $key->id_detalle_receta_produccion,
                "id_insumo" => $key->id_insumo,
                "nombre_insumo" => $key->nombre_insumo,
                "cantidad" => $key->cantidad,
                "nombre_umedida" => $key->nombre_umedida,
                "responsive" => ""
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

    public function grabarDetalleReceta($data)
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $this->db->insert('detalle_receta_produccion', $data);
        return $this->db->insert_id();
    }

    public function eliminarDetalleReceta($id_detalle)
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        $this->db->where('id_detalle_receta_produccion', $id_detalle);
        return $this->db->delete('detalle_receta_produccion');
    }

    // ====================== v1.37.0: receta de producción completa (pedido del usuario) ======================
    // Antes la ventana solo grababa el encabezado: no había cómo ponerle sus insumos ni se sabía cuánto costaba.

    private function dbt()
    {
        $this->load->helper('tenant');
        return get_tenant_db($this);
    }

    /** Factor de una unidad a otra del mismo tipo (1 KILOGRAMO = 1000 GRAMO); null si no se pueden convertir. */
    private function factor($desde, $hacia)
    {
        $desde = (int)$desde; $hacia = (int)$hacia;
        if ($desde && $desde === $hacia) return 1.0;
        $this->load->model('MuMedida');
        $a = $this->MuMedida->equivalenciaDe($desde);
        $b = $this->MuMedida->equivalenciaDe($hacia);
        if (!$a || !$b || $a[0] !== $b[0] || $b[1] <= 0) return null;
        return $a[1] / $b[1];
    }

    /**
     * Una cantidad escrita en $idUnidad, pasada a la unidad de INVENTARIO del insumo (la del stock y del costo).
     * $ins = fila con id_umedida_inventario, id_umedida_receta, m_inventario, m_receta. null si no se puede.
     * Misma regla que Unidades.aReceta + Unidades.recetaAInventario (views/umedida/jsEquivalencias.php).
     */
    public function aInventario($ins, $cantidad, $idUnidad)
    {
        $inv = (int)$ins->id_umedida_inventario; $rec = (int)$ins->id_umedida_receta; $id = (int)$idUnidad;
        $mInv = (float)$ins->m_inventario > 0 ? (float)$ins->m_inventario : 1.0;
        $mRec = (float)$ins->m_receta > 0 ? (float)$ins->m_receta : 1.0;
        $q = (float)$cantidad;
        if (!$id || $id === $inv) return $q;
        if ($id === $rec) return $q * $mInv / $mRec;
        $f = $inv ? $this->factor($id, $inv) : null;
        if ($f !== null) return $q * $f;
        $g = $rec ? $this->factor($id, $rec) : null;
        if ($g !== null) return $q * $g * $mInv / $mRec;
        return null;
    }

    private function sqlInsumo($where)
    {
        return "SELECT i.id_insumo, i.nombre_insumo, COALESCE(i.id_umedida_inventario, 0) AS id_umedida_inventario,
                COALESCE(i.id_umedida_receta, 0) AS id_umedida_receta,
                COALESCE(NULLIF(i.umedida_inventario, 0), 1) AS m_inventario, COALESCE(NULLIF(i.umedida_receta, 0), 1) AS m_receta,
                COALESCE(i.costo_neto_ppp, 0) AS costo, COALESCE(ui.nombre_umedida, '') AS nombre_umedida_inventario,
                COALESCE(ur.nombre_umedida, '') AS nombre_umedida_receta
            FROM insumo i
            LEFT JOIN umedida ui ON ui.id_umedida = i.id_umedida_inventario
            LEFT JOIN umedida ur ON ur.id_umedida = i.id_umedida_receta
            WHERE $where";
    }

    private function filaInsumo($r)
    {
        return array('id_insumo' => (int)$r->id_insumo, 'nombre_insumo' => (string)$r->nombre_insumo,
            'id_umedida_inventario' => (int)$r->id_umedida_inventario, 'id_umedida_receta' => (int)$r->id_umedida_receta,
            'umedida_inventario' => (float)$r->m_inventario, 'umedida_receta' => (float)$r->m_receta,
            'nombre_umedida_inventario' => (string)$r->nombre_umedida_inventario, 'nombre_umedida_receta' => (string)$r->nombre_umedida_receta,
            'costo' => round((float)$r->costo, 4));
    }

    /** Las recetas del insumo elaborado y los insumos que se pueden usar (con su unidad y costo por unidad de inventario). */
    public function datosEditor($idElaborado)
    {
        $db = $this->dbt();
        $el = $db->query($this->sqlInsumo('i.id_insumo = ?'), array((int)$idElaborado))->row();
        if (!$el) return array('error' => 'El insumo ya no existe. Recarga la página.');
        $recetas = array();
        foreach ($db->query('SELECT id_receta_produccion AS id, nombre_receta_produccion AS nombre, cantidad, id_umedida, (vigente IS NOT FALSE) AS vigente
            FROM receta_produccion WHERE id_insumo = ? ORDER BY vigente DESC, id_receta_produccion DESC', array((int)$idElaborado))->result() as $r) {
            $recetas[] = array('id' => (int)$r->id, 'nombre' => (string)$r->nombre, 'cantidad' => (float)$r->cantidad,
                'id_umedida' => (int)$r->id_umedida, 'vigente' => $r->vigente === 't' || $r->vigente === true);
        }
        $insumos = array();
        foreach ($db->query($this->sqlInsumo('i.id_insumo <> ? AND i.vigente IS NOT FALSE') . ' ORDER BY i.nombre_insumo', array((int)$idElaborado))->result() as $r) {
            $insumos[] = $this->filaInsumo($r);
        }
        return array('ok' => true, 'elaborado' => $this->filaInsumo($el), 'recetas' => $recetas, 'insumos' => $insumos);
    }

    /** Una receta con sus líneas. */
    public function completa($id)
    {
        $db = $this->dbt();
        $r = $db->query('SELECT id_receta_produccion AS id, nombre_receta_produccion AS nombre, id_insumo, cantidad, id_umedida,
            (vigente IS NOT FALSE) AS vigente FROM receta_produccion WHERE id_receta_produccion = ?', array((int)$id))->row();
        if (!$r) return array('error' => 'La receta ya no existe. Recarga la página.');
        $lineas = array();
        foreach ($db->query('SELECT id_insumo, cantidad, id_umedida FROM detalle_receta_produccion
            WHERE id_receta_produccion = ? AND vigente IS NOT FALSE ORDER BY id_detalle_receta_produccion', array((int)$id))->result() as $l) {
            $lineas[] = array('id_insumo' => (int)$l->id_insumo, 'cantidad' => (float)$l->cantidad, 'id_umedida' => (int)$l->id_umedida);
        }
        return array('ok' => true, 'receta' => array('id' => (int)$r->id, 'nombre' => (string)$r->nombre, 'id_insumo' => (int)$r->id_insumo,
            'cantidad' => (float)$r->cantidad, 'id_umedida' => (int)$r->id_umedida, 'vigente' => $r->vigente === 't' || $r->vigente === true,
            'lineas' => $lineas));
    }

    /**
     * Graba la receta completa: {id, nombre, id_insumo (elaborado), cantidad (rinde), id_umedida, vigente,
     * lineas: [{id_insumo, cantidad, id_umedida}]}. Revisa que cada unidad se pueda convertir a la del insumo y devuelve
     * el costo (en la unidad de inventario de cada insumo × su costo promedio).
     */
    public function guardarCompleta($d)
    {
        $db = $this->dbt();
        $id = (int)($d['id'] ?? 0);
        $idEl = (int)($d['id_insumo'] ?? 0);
        $nombre = mb_strtoupper(mb_substr(trim(preg_replace('/\s+/u', ' ', (string)($d['nombre'] ?? ''))), 0, 50));
        $rinde = (float)str_replace(',', '.', (string)($d['cantidad'] ?? 0));
        $idUm = (int)($d['id_umedida'] ?? 0);
        $vigente = !isset($d['vigente']) || in_array((string)$d['vigente'], array('1', 'true', 't'), true);
        $el = $db->query($this->sqlInsumo('i.id_insumo = ?'), array($idEl))->row();
        if (!$el) return array('error' => 'Elige el insumo elaborado');
        if ($nombre === '') return array('error' => 'Escribe el nombre de la receta');
        if (!($rinde > 0) || $rinde > 1000000) return array('error' => 'Escribe cuánto rinde la receta (mayor que 0)');
        if ($this->aInventario($el, $rinde, $idUm) === null) {
            return array('error' => 'La unidad del rendimiento no se puede pasar a ' . ($el->nombre_umedida_inventario ?: 'la unidad del insumo'));
        }
        $lineas = array();
        foreach (is_array($d['lineas'] ?? null) ? $d['lineas'] : array() as $l) {
            $ii = (int)($l['id_insumo'] ?? 0);
            $q = (float)str_replace(',', '.', (string)($l['cantidad'] ?? 0));
            if ($ii <= 0) continue;
            if ($ii === $idEl) return array('error' => 'La receta no puede llevar el mismo insumo que produce');
            if (!($q > 0) || $q > 1000000) return array('error' => 'Cada insumo debe tener una cantidad mayor que 0');
            $lineas[] = array('id_insumo' => $ii, 'cantidad' => $q, 'id_umedida' => (int)($l['id_umedida'] ?? 0));
        }
        if (!$lineas) return array('error' => 'Agrega al menos un insumo a la receta');
        if (count($lineas) > 100) return array('error' => 'Demasiados insumos en una receta');
        $ins = array();
        foreach ($db->query($this->sqlInsumo('i.id_insumo IN (' . implode(',', array_map(function ($l) { return (int)$l['id_insumo']; }, $lineas)) . ')'))->result() as $r) {
            $ins[(int)$r->id_insumo] = $r;
        }
        $costo = 0.0;
        foreach ($lineas as $k => $l) {
            if (!isset($ins[$l['id_insumo']])) return array('error' => 'Un insumo de la receta ya no existe. Recarga la página.');
            $inv = $this->aInventario($ins[$l['id_insumo']], $l['cantidad'], $l['id_umedida']);
            if ($inv === null) return array('error' => 'La unidad elegida para ' . $ins[$l['id_insumo']]->nombre_insumo . ' no se puede pasar a su unidad');
            if (!$l['id_umedida']) $lineas[$k]['id_umedida'] = (int)$ins[$l['id_insumo']]->id_umedida_inventario;
            $costo += $inv * (float)$ins[$l['id_insumo']]->costo;
        }
        if (!$idUm) $idUm = (int)$el->id_umedida_inventario;

        $db->trans_begin();
        if ($id > 0) {
            $db->query('UPDATE receta_produccion SET nombre_receta_produccion = ?, id_insumo = ?, cantidad = ?, id_umedida = ?, vigente = ?
                WHERE id_receta_produccion = ?', array($nombre, $idEl, $rinde, $idUm, $vigente ? 't' : 'f', $id));
            if ($db->affected_rows() < 1) { $db->trans_rollback(); return array('error' => 'La receta ya no existe. Recarga la página.'); }
            $db->query('DELETE FROM detalle_receta_produccion WHERE id_receta_produccion = ?', array($id));
        } else {
            $r = $db->query('INSERT INTO receta_produccion (nombre_receta_produccion, id_insumo, id_bodega, cantidad, id_umedida, vigente)
                VALUES (?, ?, 0, ?, ?, ?) RETURNING id_receta_produccion', array($nombre, $idEl, $rinde, $idUm, $vigente ? 't' : 'f'))->row();
            $id = (int)$r->id_receta_produccion;
        }
        foreach ($lineas as $l) {
            $db->query('INSERT INTO detalle_receta_produccion (id_receta_produccion, id_insumo, id_bodega, cantidad, id_umedida, vigente)
                VALUES (?, ?, 0, ?, ?, true)', array($id, $l['id_insumo'], $l['cantidad'], $l['id_umedida']));
        }
        // El insumo producido es una sub receta (sale "SI" en Insumos y Recetas)
        $db->query('UPDATE insumo SET esreceta = true WHERE id_insumo = ? AND esreceta IS NOT TRUE', array($idEl));
        if ($db->trans_status() === false) { $db->trans_rollback(); return array('error' => 'No se pudo grabar la receta'); }
        $db->trans_commit();
        $rindeInv = $this->aInventario($el, $rinde, $idUm);
        return array('ok' => true, 'id' => $id, 'costo' => round($costo, 2),
            'costo_unidad' => $rindeInv > 0 ? round($costo / $rindeInv, 2) : 0, 'unidad' => (string)$el->nombre_umedida_inventario);
    }
}