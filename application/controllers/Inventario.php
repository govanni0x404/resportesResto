<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Inventario: arriba se arma la lista de insumos con la cantidad y se traspasa al stock de la bodega;
 * abajo queda el historial de inventarios con su detalle. También permite dejar la bodega con stock en 0.
 */
class Inventario extends CI_Controller {

    /** Tope por línea, para atrapar cantidades escritas por error (ej. un código de barras en la cantidad). */
    const MAX_CANTIDAD = 999999;

    public function __construct()
    {
        parent::__construct();
        $this->load->model([
            'Minventario',
            'Mdetalleinventario'
        ]);
    }

    public function index()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        $data['bodegas'] = $this->Minventario->get_bodegas();
        $data['bodega'] = (int)$this->input->get('bodega');
        $this->load->view('menuTop.php', $data);
        $this->load->view('inventario/listadoInventario', $data);
        $this->load->view('menuFooter.php');
        $this->load->view('inventario/js.php', $data);
    }

    // Página para armar un inventario nuevo (buscar insumo, cantidad, lista) y traspasarlo al stock
    public function nuevo()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url());
        }
        $data['bodegas'] = $this->Minventario->get_bodegas();
        $data['bodega'] = (int)$this->input->get('bodega');
        $this->load->view('menuTop.php', $data);
        $this->load->view('inventario/nuevo', $data);
        $this->load->view('menuFooter.php');
        $this->load->view('inventario/jsNuevo.php', $data);
    }

    // AJAX: Obtener detalle de inventario
    public function detalle()
    {
        $id_inventario = (int)$this->input->post('id_inventario');
        if (!$id_inventario) {
            echo json_encode([]);
            return;
        }
        echo json_encode($this->Mdetalleinventario->get_detalle($id_inventario));
    }

    // AJAX: Filtrar por bodega
    public function listar_por_bodega()
    {
        $inventarios = $this->Minventario->get_inventarios((int)$this->input->post('id_bodega'));
        header('Content-Type: application/json');
        echo json_encode($inventarios);
    }

    // AJAX: insumos por nombre, código de barras o id, con su stock en la bodega
    public function buscar()
    {
        $this->responder(function () {
            return $this->Minventario->buscarInsumos((string)$this->input->post('texto'), (int)$this->input->post('id_bodega'));
        });
    }

    // AJAX: graba el inventario y aplica el stock
    public function traspasar()
    {
        $this->responder(function () {
            $p = json_decode((string)$this->input->raw_input_stream, true);
            if (!is_array($p)) $p = $this->input->post();
            $idBodega = isset($p['id_bodega']) ? (int)$p['id_bodega'] : 0;
            $modo = isset($p['modo']) && $p['modo'] === 'contar' ? 'contar' : 'sumar';
            $lineas = isset($p['lineas']) && is_array($p['lineas']) ? $p['lineas'] : array();
            if ($idBodega <= 0) throw new Exception('Elige la bodega', 400);
            if (!$lineas) throw new Exception('Agrega al menos un insumo a la lista', 400);
            if (count($lineas) > 2000) throw new Exception('Demasiadas líneas en un solo inventario', 400);
            $limpias = array();
            foreach ($lineas as $l) {
                $id = isset($l['id_insumo']) ? (int)$l['id_insumo'] : 0;
                $c = isset($l['cantidad']) && is_numeric($l['cantidad']) ? round((float)$l['cantidad'], 3) : null;
                if ($id <= 0 || $c === null) throw new Exception('Hay una línea sin insumo o sin cantidad', 400);
                if ($modo === 'contar' && $c < 0) throw new Exception('La cantidad contada no puede ser negativa', 400);
                if ($modo === 'sumar' && $c == 0) throw new Exception('Hay una línea con cantidad 0', 400);
                if (abs($c) > self::MAX_CANTIDAD) throw new Exception('Cantidad demasiado alta en una línea', 400);
                $limpias[] = array('id_insumo' => $id, 'cantidad' => $c);
            }
            $obs = trim((string)(isset($p['observacion']) ? $p['observacion'] : ''));
            $id = $this->Minventario->traspasar($idBodega, $modo, $obs, $limpias);
            return array('ok' => true, 'id_inventario' => $id, 'mensaje' => 'Inventario N° ' . $id . ' traspasado al stock');
        });
    }

    // AJAX: deja todo el stock de la bodega en 0. Exige escribir CERO para que no se haga por error.
    public function stockCero()
    {
        $this->responder(function () {
            $idBodega = (int)$this->input->post('id_bodega');
            if ($idBodega <= 0) throw new Exception('Elige la bodega', 400);
            if (strtoupper(trim((string)$this->input->post('confirmacion'))) !== 'CERO') {
                throw new Exception('Para confirmar escribe CERO', 400);
            }
            $r = $this->Minventario->stockCero($idBodega);
            return array('ok' => true, 'id_inventario' => $r['id_inventario'],
                'mensaje' => 'Stock en 0 para ' . $r['insumos'] . ' insumo(s). Quedó registrado como inventario N° ' . $r['id_inventario']);
        });
    }

    private function responder($fn)
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($this->input->method() !== 'post') {
            http_response_code(405);
            echo json_encode(array('ok' => false, 'error' => 'Método no permitido'));
            return;
        }
        try {
            echo json_encode($fn(), JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            $c = (int)$e->getCode();
            $usuario = $c >= 400 && $c < 500;
            http_response_code($usuario ? $c : 500);
            if (!$usuario) log_message('error', 'Inventario: ' . $e->getMessage());
            echo json_encode(array('ok' => false, 'error' => $usuario ? $e->getMessage() : 'No se pudo completar la acción. Intenta de nuevo.'), JSON_UNESCAPED_UNICODE);
        }
    }
}
