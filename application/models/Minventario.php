<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Inventario de bodega (stock por insumo y bodega en stock_insumo).
 *
 * Cada inventario queda registrado como en el sistema de escritorio: cabecera en "inventario" y una línea por insumo
 * en "detalle_inventario" con el stock que había (actual), el que queda (nuevo) y la diferencia. El stock se mueve
 * igual que en compras y ventas: la diferencia suma a cantidad_entrada (o a cantidad_salida si es negativa) y el
 * saldo queda como entrada - salida.
 */
class Minventario extends CI_Model {

    public function __construct()
    {
        parent::__construct();
    }

    private function db()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        $this->load->helper('tenant');
        return get_tenant_db($this);
    }

    public function get_inventarios($id_bodega = null)
    {
        $db_tenant = $this->db();

        $db_tenant->select('
            i.id_inventario,
            i.fecha_movimiento,
            i.fecha_inventario,
            i.id_bodega,
            i.id_usuario,
            COALESCE(u.nombre_usuario, \'SIN USUARIO\') AS usuario
        ');
        $db_tenant->from('inventario i');
        $db_tenant->join('usuario u', 'u.id_usuario = i.id_usuario', 'left');

        if ($id_bodega) {
            $db_tenant->where('i.id_bodega', (int)$id_bodega);
        }

        $db_tenant->order_by('i.fecha_movimiento', 'DESC');

        return $db_tenant->get()->result();
    }

    public function get_bodegas()
    {
        $db_tenant = $this->db();
        $db_tenant->select('id_bodega, nombre_bodega');
        $db_tenant->from('bodega');
        $db_tenant->order_by('nombre_bodega', 'ASC');
        return $db_tenant->get()->result();
    }

    /** Insumos vigentes por nombre, código de barras o id, con su stock en la bodega. */
    public function buscarInsumos($texto, $idBodega)
    {
        $db = $this->db();
        $texto = trim((string)$texto);
        if ($texto === '') return array();
        $params = array((int)$idBodega);
        $donde = array();
        foreach (preg_split('/\s+/', mb_strtolower($texto)) as $p) {
            $donde[] = 'lower(i.nombre_insumo) LIKE ?';
            $params[] = '%' . str_replace(array('\\', '%', '_'), array('\\\\', '\%', '\_'), $p) . '%';
        }
        $filtro = '(' . implode(' AND ', $donde) . ') OR lower(COALESCE(i.cbarra, \'\')) = ?';
        $params[] = mb_strtolower($texto);
        if (ctype_digit($texto)) { $filtro .= ' OR i.id_insumo = ?'; $params[] = (int)$texto; }
        $q = "SELECT i.id_insumo, i.nombre_insumo, COALESCE(i.cbarra, '') AS cbarra,
                COALESCE(u.nombre_umedida, '') AS umedida, COALESCE(i.costo_neto_ppp, 0) AS costo,
                COALESCE((SELECT sum(s.saldo) FROM stock_insumo s WHERE s.id_insumo = i.id_insumo AND s.id_bodega = ?), 0) AS stock
            FROM insumo i
            LEFT JOIN umedida u ON u.id_umedida = i.id_umedida_inventario
            WHERE i.vigente IS TRUE AND ($filtro)
            ORDER BY i.nombre_insumo
            LIMIT 30";
        $filas = $db->query($q, $params)->result();
        foreach ($filas as $f) {
            $f->id_insumo = (int)$f->id_insumo;
            $f->stock = round((float)$f->stock, 3);
            $f->costo = round((float)$f->costo, 2);
        }
        return $filas;
    }

    /**
     * Graba el inventario y aplica el stock. $lineas = [{id_insumo, cantidad}].
     * $modo 'sumar': el stock nuevo es el actual + la cantidad; 'contar': el stock queda en la cantidad contada.
     * Devuelve el id del inventario.
     */
    public function traspasar($idBodega, $modo, $observacion, $lineas)
    {
        $db = $this->db();
        return $this->enTransaccion($db, function () use ($db, $idBodega, $modo, $observacion, $lineas) {
            $this->exigirBodega($db, $idBodega);
            $porInsumo = array();
            foreach ($lineas as $l) {
                // El mismo insumo dos veces en la lista: al sumar se acumula, al contar vale el último conteo
                $id = (int)$l['id_insumo'];
                $c = (float)$l['cantidad'];
                $porInsumo[$id] = ($modo === 'sumar' && isset($porInsumo[$id])) ? $porInsumo[$id] + $c : $c;
            }
            $ins = $db->query('SELECT id_insumo, COALESCE(costo_neto_ppp, 0) AS costo FROM insumo
                WHERE vigente IS TRUE AND id_insumo IN (' . implode(',', array_map('intval', array_keys($porInsumo))) . ')')->result();
            $costos = array();
            foreach ($ins as $i) $costos[(int)$i->id_insumo] = (float)$i->costo;
            foreach (array_keys($porInsumo) as $id) {
                if (!isset($costos[$id])) throw new Exception('El insumo ' . $id . ' no existe o no está vigente', 400);
            }

            $idInv = $this->cabecera($db, $idBodega, $observacion);
            foreach ($porInsumo as $id => $cantidad) {
                $actual = $this->stockActual($db, $id, $idBodega);
                $nuevo = $modo === 'sumar' ? $actual + $cantidad : $cantidad;
                $this->aplicar($db, $idInv, $id, $idBodega, $actual, $nuevo, $costos[$id]);
            }
            return $idInv;
        });
    }

    /** Deja en 0 el stock de todos los insumos de la bodega (queda registrado como un inventario). */
    public function stockCero($idBodega)
    {
        $db = $this->db();
        return $this->enTransaccion($db, function () use ($db, $idBodega) {
            $this->exigirBodega($db, $idBodega);
            $filas = $db->query('SELECT s.id_insumo, sum(s.saldo) AS saldo, COALESCE(max(i.costo_neto_ppp), 0) AS costo
                FROM stock_insumo s JOIN insumo i ON i.id_insumo = s.id_insumo
                WHERE s.id_bodega = ? GROUP BY s.id_insumo HAVING round(sum(s.saldo)::numeric, 6) <> 0',
                array((int)$idBodega))->result();
            if (!$filas) throw new Exception('La bodega ya tiene todo el stock en 0', 409);
            $idInv = $this->cabecera($db, $idBodega, 'STOCK EN CERO');
            foreach ($filas as $f) {
                $this->aplicar($db, $idInv, (int)$f->id_insumo, $idBodega, $this->stockActual($db, (int)$f->id_insumo, $idBodega), 0, (float)$f->costo);
            }
            return array('id_inventario' => $idInv, 'insumos' => count($filas));
        });
    }

    // ---------------- internos ----------------

    private function enTransaccion($db, $fn)
    {
        $debug = $db->db_debug;
        $db->db_debug = false;   // los errores de SQL llegan como excepción y se hace rollback
        $db->trans_begin();
        try {
            $r = $fn();
            $e = $db->error();
            if (!empty($e['code']) || !empty($e['message'])) throw new Exception($e['message'], 500);
            $db->trans_commit();
            $db->db_debug = $debug;
            return $r;
        } catch (Throwable $ex) {
            $db->trans_rollback();
            $db->db_debug = $debug;
            throw $ex;
        }
    }

    private function q($db, $sql, $params = array())
    {
        $r = $db->query($sql, $params);
        if ($r === false) {
            $e = $db->error();
            throw new Exception('Error de base de datos: ' . $e['message'], 500);
        }
        return $r;
    }

    private function exigirBodega($db, $idBodega)
    {
        if (!$this->q($db, 'SELECT 1 FROM bodega WHERE id_bodega = ?', array((int)$idBodega))->row()) {
            throw new Exception('La bodega no existe', 400);
        }
    }

    private function cabecera($db, $idBodega, $observacion)
    {
        $r = $this->q($db, 'INSERT INTO inventario (id_usuario, fecha_movimiento, id_bodega, fecha_inventario, observacion)
            VALUES (?, now(), ?, current_date, ?) RETURNING id_inventario',
            array((int)$this->session->userdata('id_usuario'), (int)$idBodega, mb_substr((string)$observacion, 0, 100)))->row();
        return (int)$r->id_inventario;
    }

    /** Stock total del insumo en la bodega, bloqueando sus filas para que nadie lo mueva mientras tanto. */
    private function stockActual($db, $idInsumo, $idBodega)
    {
        $filas = $this->q($db, 'SELECT saldo FROM stock_insumo WHERE id_insumo = ? AND id_bodega = ? FOR UPDATE',
            array((int)$idInsumo, (int)$idBodega))->result();
        $total = 0;
        foreach ($filas as $f) $total += (float)$f->saldo;
        return round($total, 6);
    }

    private function aplicar($db, $idInv, $idInsumo, $idBodega, $actual, $nuevo, $costo)
    {
        $nuevo = round((float)$nuevo, 6);
        $dif = round($nuevo - $actual, 6);
        $s = $this->q($db, 'SELECT id_stock_insumo FROM stock_insumo WHERE id_insumo = ? AND id_bodega = ? ORDER BY id_stock_insumo LIMIT 1',
            array((int)$idInsumo, (int)$idBodega))->row();
        if ($s) {
            if ($dif != 0) {
                $this->q($db, 'UPDATE stock_insumo SET cantidad_entrada = COALESCE(cantidad_entrada, 0) + ?,
                        cantidad_salida = COALESCE(cantidad_salida, 0) + ?,
                        saldo = COALESCE(cantidad_entrada, 0) + ? - (COALESCE(cantidad_salida, 0) + ?)
                    WHERE id_stock_insumo = ?',
                    array(max($dif, 0), max(-$dif, 0), max($dif, 0), max(-$dif, 0), (int)$s->id_stock_insumo));
            }
            $idStock = (int)$s->id_stock_insumo;
        } else {
            $idStock = (int)$this->q($db, 'INSERT INTO stock_insumo (id_insumo, id_bodega, cantidad_entrada, cantidad_salida, saldo)
                VALUES (?, ?, ?, ?, ?) RETURNING id_stock_insumo',
                array((int)$idInsumo, (int)$idBodega, max($dif, 0), max(-$dif, 0), $dif))->row()->id_stock_insumo;
        }
        $this->q($db, 'INSERT INTO detalle_inventario (id_inventario, id_insumo, actual, nuevo, diferencia, costo_un, id_bodega, id_stock,
                valor_venta, valor_diferencia_costo, valor_diferencia_venta)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, 0)',
            array($idInv, (int)$idInsumo, round($actual, 2), round($nuevo, 2), round($dif, 2), round($costo, 2), (int)$idBodega, $idStock,
                (int)round($dif * $costo)));
    }
}
