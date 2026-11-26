<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Productos extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model(array('Mproductos', 'Mroles'));
	}

	public function index()
	{

		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$data = array(
			'nombreUsuario' => $this->session->userdata('nombre_usuario'),
			//'modulosDisponibles' => json_decode($this->Mroles->modulosDisponibles($this->session->userdata('id_rol')), true)
		);
		$this->load->view('menuTop.php', $data);
				// v1.36.0: el botón Promos Sushi solo con Configuraciones > Promos de sushi > "Usar promos de sushi"
				$this->load->model('Mcaja');
				$sushiCfg = $this->Mcaja->opcionesSushi();
				$this->load->view('productos/productos.php', array('promosSushi' => !empty($sushiCfg['activas'])));
				$this->load->view('menuFooter.php');
				$this->load->view('productos/funciones.php');
				$this->load->view('productos/jsProductos.php');
				$this->load->view('productos/modals.php');
				$this->load->view('productos/jsOtros.php');
				$this->load->view('productos/jsAgregados.php');
				$this->load->view('productos/jsMensajesPorProducto.php');
				$this->load->view('productos/jsPrecioVenta.php');
				$this->load->view('productos/jsMenu.php');
				$this->load->view('productos/jsReceta.php');
				$this->load->view('productos/jsImagen.php');
				$this->load->view('productos/promoSushi.php');   // v1.25.0
				$this->load->view('productos/promos.php');   // v1.36.0: promos normales
				$this->load->view('recetas/jsNewFamiliaReceta.php');
				$this->load->view('recetas/jsNewSeccion.php');
	}

	public function ListadoCartaDeProductos()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['error' => 'Sesión expirada']);
			return; 
		}
		echo json_encode($this->Mproductos->ListadoCartaDeProductos());
	}

	/** Estrella del listado de la Carta: marca o desmarca el producto como favorito de la Caja. */
	public function marcarFavorito()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['ok' => false, 'error' => 'Sesión expirada']);
			return;
		}
		$id = (int)$this->input->post('id_producto');
		if ($id <= 0) {
			echo json_encode(['ok' => false, 'error' => 'Producto no válido']);
			return;
		}
		echo json_encode($this->Mproductos->marcarFavorito($id, (int)$this->input->post('favorito') === 1));
	}

	public function obtenerPreciosDeVenta()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		$idProducto = $this->input->post('id_producto');
		echo json_encode($this->Mproductos->obtenerPreciosDeVenta($idProducto));
	}

	public function obtenerPreciosYComisiones()
	{
    	if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
        	redirect(base_url());
    	}
    	$idProducto = $this->input->post('id_producto');
    	echo json_encode($this->Mproductos->obtenerPreciosDeVenta($idProducto));
	}

	public function ListadoDeSubFamilias()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		echo json_encode($this->Mproductos->ListadoDeSubFamilias());
	}


	public function ListadoDeUmedida()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		echo json_encode($this->Mproductos->ListadoDeUmedida());
	}


	public function ListadoDestinoDeImpresion()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		echo json_encode($this->Mproductos->ListadoDestinoDeImpresion());
	}


	public function ListadoDeBodega()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		echo json_encode($this->Mproductos->ListadoDeBodega());
	}


	public function ListadoDeImpuestos()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		echo json_encode($this->Mproductos->ListadoDeImpuestos());
	}

	public function ListadoDeImpuestosAdicionales()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		echo json_encode($this->Mproductos->ListadoDeImpuestosAdicionales());
	}

	public function ListadoDeReceta()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		echo json_encode($this->Mproductos->ListadoDeReceta());
	}

	public function ListadoDeMenu()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		echo json_encode($this->Mproductos->ListadoDeMenu());
	}

	public function grabarProductoConPrecios()	{
		
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		
		$producto = $this->input->post('producto');
		$precios = $this->input->post('precios');
		
		log_message('debug', 'Producto recibido: ' . print_r($producto, true));
		log_message('debug', 'Precios recibidos: ' . print_r($precios, true));
		
		if (empty($producto)) {
			echo json_encode(['error' => 'No se recibieron datos del producto']);
			return;
		}
		
		// Validar campos del producto
		foreach ($producto as $key => $value) {
			if ($value === "" || $value === null) {
				if (strpos($key, 'id_') === 0 || in_array($key, [
					'precio_bruto', 'precio_ucompra', 'valor1', 'valor2',
					'stock_minimo', 'pponderado', 'valor_ila', 'orden'
				])) {
					$producto[$key] = 0;
				}
			}
		}

		$id = intval($producto['id_producto']);

		// 18-11-2026: producto nuevo con el código automático (el que sugirió la pantalla, sin cambiarlo) o sin código: si
		// ese número ya lo tomó otro producto mientras tanto (otra pantalla, un reintento), se usa el siguiente libre en vez
		// de rechazarlo. Un código escrito a mano que ya existe se sigue rechazando.
		$producto['cbarra'] = trim((string)$producto['cbarra']);
		if ($id <= 0 && ($producto['cbarra'] === '' || !empty($producto['cbarra_auto']))
			&& ($producto['cbarra'] === '' || $this->Mproductos->existeCodigoBarra($producto['cbarra'], $id))) {
			$producto['cbarra'] = $this->Mproductos->obtenerSiguienteCodigoBarra();
		}
		unset($producto['cbarra_auto']);

		// VALIDAR CODIGO DE BARRAS DUPLICADO - Pasando el ID del producto actual
		if ($this->Mproductos->existeCodigoBarra($producto['cbarra'], $id)) {
			echo json_encode([
				'success' => false,
				'error' => 'El código de barras ya existe en otro producto'
			]);
			return;
		}
		
		$miData = array(
			'nombre_producto' => $producto['nombre_producto'],
			'cbarra' => $producto['cbarra'],
			'impto_iva' => $producto['impto_iva'],
			'impto_ila' => $producto['impto_ila'],
			'id_familia' => $producto['id_familia'],
			'id_umedida' => $producto['id_umedida'],
			'precio_bruto' => $producto['precio_bruto'],
			'espack' => $producto['espack'],
			'vigente' => $producto['vigente'],
			'inventariable' => $producto['inventariable'],
			'valor_ila' => $producto['valor_ila'],
			'precio_ucompra' => $producto['precio_ucompra'],
			'pponderado' => $producto['pponderado'],
			'stock_minimo' => $producto['stock_minimo'],
			'cbarra_balanza' => $producto['cbarra_balanza'],
			'con_agregado' => $producto['con_agregado'],
			'venta_publico' => $producto['venta_publico'],
			'descripcion_corta' => $producto['descripcion_corta'],
			'permite_porcion' => $producto['permite_porcion'],
			'id_subfamilia' => $producto['id_subfamilia'],
			'id_dimpresion' => $producto['id_dimpresion'],
			'id_receta' => $producto['id_receta'],
			'orden' => $producto['orden'],
			'permite_cambio' => $producto['permite_cambio'],
			'valor1' => $producto['valor1'],
			'valor2' => $producto['valor2'],
			'id_bodega' => $producto['id_bodega'],
			// v1.24.0: es menú si tiene un menú elegido (antes la pantalla mandaba siempre false)
			'es_menu' => (int)($producto['id_menu'] ?? 0) > 0 ? 't' : 'f',
			'id_menu' => max(0, (int)($producto['id_menu'] ?? 0)),
			'venta_segun_stock' => $producto['venta_segun_stock'],
			'elaboracion_propia' => $producto['elaboracion_propia'],
			'permitir_venta_sin_precio' => $producto['permitir_venta_sin_precio'],
			'venta_uno_a_uno' => $producto['venta_uno_a_uno'],
			'pedir_cantidad_en_venta' => $producto['pedir_cantidad_en_venta'],
			'detalle_preparacion' => $producto['detalle_preparacion'],
			'tiempo_preparacion' => $producto['tiempo_preparacion'],
			'mostrar_detalle_insumos_receta' => $producto['mostrar_detalle_insumos_receta'],
			'ver_en_dispositivos_moviles' => $producto['ver_en_dispositivos_moviles'],
			'permite_cortesia' => $producto['permite_cortesia']
		);
		// v1.30.0: "Imprimir también en otro destino" (0 = no). Solo si la pantalla lo mandó y la migración creó la columna
		if (array_key_exists('id_dimpresion2_web', $producto)) {
			$this->load->helper('tenant');
			if (get_tenant_db($this)->field_exists('id_dimpresion2_web', 'producto')) {
				$d2 = max(0, (int)$producto['id_dimpresion2_web']);
				$miData['id_dimpresion2_web'] = $d2 === (int)$producto['id_dimpresion'] ? 0 : $d2;   // el mismo destino no se imprime dos veces
			}
		}
		// Favorito de la Caja: solo si llegó del formulario y la base ya tiene la columna (migración 20261110190000)
		if (isset($producto['favorito']) && $this->Mproductos->tieneColumnaFavorito()) {
			$miData['favorito'] = ((string)$producto['favorito'] === '1') ? 'true' : 'false';
		}
		
		// v1.20.0: el formulario ya tiene el ILA (Impuesto adicional). Se graba el elegido, con su tasa en valor_ila; sin
		// elegir o 0 = el impuesto "sin ILA" (valor 0). Los precios (detalle_lprecio) se recalculan con él más abajo.
		// Un envío sin el campo (pantalla antigua en caché) conserva el ILA del producto al editar, como antes.
		if (!empty($producto['ila_en_formulario'])) {
			$this->load->helper('tenant');
			$dbIla = get_tenant_db($this);
			$idIla = (int)$producto['impto_ila'];
			$imp = $idIla > 0 ? $dbIla->query('SELECT id_impuesto, COALESCE(valor, 0) AS valor, nombre_impuesto FROM impuesto WHERE id_impuesto = ?', array($idIla))->row() : null;
			if (!$imp) {
				$imp = $dbIla->query("SELECT id_impuesto, 0 AS valor FROM impuesto WHERE COALESCE(valor, 0) = 0 ORDER BY (nombre_impuesto ILIKE '%EXENTO%') DESC, id_impuesto LIMIT 1")->row();
			} elseif ((float)$imp->valor > 0 && stripos((string)$imp->nombre_impuesto, 'ILA') !== 0) {
				echo json_encode(['success' => false, 'error' => 'El impuesto adicional elegido no es un ILA']);
				return;
			}
			if (!$imp) {
				echo json_encode(['success' => false, 'error' => 'No hay un impuesto "sin ILA" (valor 0) en la tabla de impuestos']);
				return;
			}
			$miData['impto_ila'] = (int)$imp->id_impuesto;
			$miData['valor_ila'] = (float)$imp->valor;
		} elseif ($id > 0) {
			unset($miData['impto_ila'], $miData['valor_ila']);
		} else {
			// v1.34.1: la Carta ya no tiene el ILA: un producto nuevo queda con el impuesto "sin ILA" (valor 0)
			$this->load->helper('tenant');
			$sinIla = get_tenant_db($this)->query("SELECT id_impuesto FROM impuesto WHERE COALESCE(valor, 0) = 0 ORDER BY (nombre_impuesto ILIKE '%EXENTO%') DESC, id_impuesto LIMIT 1")->row();
			if (!$sinIla) {
				echo json_encode(['success' => false, 'error' => 'No hay un impuesto "sin ILA" (valor 0) en la tabla de impuestos']);
				return;
			}
			$miData['impto_ila'] = (int)$sinIla->id_impuesto;
			$miData['valor_ila'] = 0;
		}
		unset($producto['ila_en_formulario']);

		// Grabar producto
		$resultadoProducto = $this->Mproductos->grabarNuevoProducto($id, $miData);
		
		if (empty($resultadoProducto)) {
			echo json_encode(['error' => 'Error al grabar producto']);
			return;
		}
		
		$idProducto = $resultadoProducto[0]->id_producto;
		log_message('debug', 'Producto grabado con ID: ' . $idProducto);
		// v1.20.0: con el IVA / ILA del producto ya grabados, el neto, IVA e ILA de todos sus precios quedan al día
		if ($id > 0) $this->Mproductos->recalcularImpuestosPrecios((int)$idProducto);
		
		$preciosGrabados = array();
		if (!empty($precios) && is_array($precios)) {
			foreach ($precios as $precio) {
				// Permitir guardar el precio incluso si es 0
				if (isset($precio['total'])) {
					$dataPrecio = array(
						'id_producto' => $idProducto,
						'id_lprecio' => (int)$precio['id_lprecio'],
						'total' => (int)$precio['total']
					);
				
					if (isset($precio['monto_comision']) && $precio['monto_comision'] >= 0) {
						$dataPrecio['monto_comision'] = (float)$precio['monto_comision'];
					}
				
					$resultadoPrecio = $this->Mproductos->grabarPrecioProducto($dataPrecio);
					$preciosGrabados[] = $resultadoPrecio;
				}
			}
		}

		log_message('debug', 'Precios y comisiones grabados: ' . count($preciosGrabados));

		echo json_encode([
			'success' => true,
			'id_producto' => $idProducto,
			'cbarra' => $producto['cbarra'],   // 18-11-2026: el código con que quedó (puede ser el siguiente libre)
			'precios_grabados' => count($preciosGrabados)
		]);
	}

	public function obtenerEAN13()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		//$this->db = $this->load->database($this->session->userdata('rut_cliente'), TRUE);
		// v1.19.1: $this->db no existe en este controlador (no hay conexión por defecto); se usa la base de la empresa.
		$this->load->helper('tenant');
		$q = "SELECT * from obtenerean13() as ean13";
		$consulta = get_tenant_db($this)->query($q);
		$sampleData = $consulta->result();
		$ean13 = "";
		foreach ($sampleData as $key) {
			$ean13 = $key->ean13;
		}
		// Usabilidade: Ao Chamar a função com os 12 algarismos retorna já com o dígito.
		echo $this->digitoEAN13($ean13);
	}

	public function obtenerSiguienteCodigoBarra()
	{
		$codigo = $this->Mproductos->obtenerSiguienteCodigoBarra();
		echo json_encode(['codigo' => $codigo]);
	}

	function digitoEAN13($cod)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		$impar = 0;
		$par = 0;
		$cnt = 1;
		$arr = str_split($cod);
		foreach ($arr as $k => $v) {
			if ($cnt % 2 == 0) {
				$par = ($par + $v);
			} else {
				$impar = ($impar + $v);
			}
			$cnt++;
		}
		$par = ($par * 3);
		$res = (floor(($par + $impar) / 10));
		$res = ($res + 1) * 10 - ($par + $impar);
		if (floor($res) % 10 == 0) {
			$res = '0';
		}
		return $cod . $res;
	}

	public function grabarNuevaFamilia()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		$d = $this->input->post('prod');
		$id = intval($d['id_familia']);
		$miData = array(
			'nombre_familia' => $d['nombre_familia'],
			'venta_directa' => $d['venta_directa'],
			'id_dimpresion' => empty($d['id_dimpresion']) ? null : $d['id_dimpresion'],
			'orden_familia' => $d['orden_familia'],
			'imprimir_en_detalle_pedido' => $d['imprimir_en_detalle_pedido'],
			'alias' => $d['alias'],
		);
		$r = $this->Mproductos->grabarNuevaFamilia($id, $miData);
		echo json_encode($r);
	}

	public function grabarNuevaSubFamilia()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		$d = $this->input->post('prod');
		$id = intval($d['id_subfamilia']);
		$miData = array(
			'nombre_subfamilia' => $d['nombre_subfamilia'],
			'id_familia' => $d['id_familia']
		);
		$r = $this->Mproductos->grabarNuevaSubFamilia($id, $miData);
		echo json_encode($r);
	}

	public function grabarNuevaBodega()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		$d = $this->input->post('prod');
		$id = intval($d['id_bodega']);
		$miData = array(
			'nombre_bodega' => $d['nombre_bodega'],
		);
		$r = $this->Mproductos->grabarNuevaBodega($id, $miData);
		echo json_encode($r);
	}

	public function ListadoDeProductos()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		$id_producto = $this->input->post('id_producto');
		echo json_encode($this->Mproductos->ListadoDeProductos(/*$id_producto*/));
	}

	public function obtenerAgregadosDelProducto()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		$idProducto = $this->input->post('id_producto_maestro');
		echo json_encode($this->Mproductos->obtenerAgregadosDelProducto($idProducto));
	}

	/**
	 * v1.30.3: máximo de agregados a elegir en la Caja para un producto (0 = sin límite).
	 * Sin "maximo" en el POST lo lee; con "maximo" lo graba.
	 */
	public function maxAgregados()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(array('ok' => false, 'error' => 'Sesión expirada'));
			return;
		}
		$id = (int)$this->input->post('id_producto');
		$max = $this->input->post('maximo');
		$r = $max === null ? $this->Mproductos->maxAgregados($id) : $this->Mproductos->grabarMaxAgregados($id, (int)$max);
		if (isset($r['error'])) http_response_code(400);
		echo json_encode($r);
	}

	/**
	 * v1.35.1 (pedidos del usuario): extras con cobro y categorías de agregados en la ventana Agregados del producto.
	 * accion: (ninguna) = leer las opciones; extras (casilla del producto); campo (cantidad o valor de una fila).
	 */
	public function opcionesAgregados()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			set_status_header(401);
			echo json_encode(array('ok' => false, 'error' => 'Sesión expirada'));
			return;
		}
		$id = (int)$this->input->post('id_producto');
		switch ((string)$this->input->post('accion')) {
			case 'extras': $r = $this->Mproductos->grabarExtrasProducto($id, (string)$this->input->post('valor') === '1'); break;
			case 'varios': $r = $this->Mproductos->grabarVariosAgregados($id, (string)$this->input->post('valor') === '1'); break;   // v1.43.0
			case 'campo': $r = $this->Mproductos->campoAgregado((int)$this->input->post('id_agregado'), $id, (string)$this->input->post('campo'), $this->input->post('valor')); break;
			default: $r = $this->Mproductos->opcionesAgregados($id);
		}
		if (isset($r['error'])) set_status_header(400);
		echo json_encode($r);
	}

	/** v1.30.2: casilla "Sale en su destino" de un agregado ya ingresado (tabla de agregados del producto). */
	public function destinoPropioAgregado()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(array('ok' => false, 'error' => 'Sesión expirada'));
			return;
		}
		$ok = $this->Mproductos->destinoPropioAgregado((int)$this->input->post('id_agregado'), (int)$this->input->post('id_producto_maestro'),
			in_array((string)$this->input->post('activo'), array('1', 'true'), true));
		if (!$ok) http_response_code(404);
		echo json_encode($ok ? array('ok' => true) : array('ok' => false, 'error' => 'El agregado ya no existe. Recarga la lista.'));
	}

	public function grabarAgregados()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		$d = $this->input->post('prod');
		if (!is_array($d)) { echo json_encode(['error' => 'Datos incompletos']); return; }
		$id = intval($d['id_agregado']);
		// v1.24.2: un producto no puede ser agregado de sí mismo
		if ((int)($d['id_producto'] ?? 0) <= 0 || (int)$d['id_producto'] === (int)($d['id_producto_maestro'] ?? 0)) {
			set_status_header(400);
			echo json_encode(['error' => 'Elige un producto distinto al que estás editando']);
			return;
		}
		// 19-11-2026 (auditoría): el mismo agregado dos veces en un producto = aviso claro (antes un error de la base)
		$this->load->helper('tenant');
		$dbDup = get_tenant_db($this);
		if ($dbDup->query('SELECT 1 FROM agregados WHERE id_producto_maestro = ? AND id_producto = ? AND id_agregado <> ? AND eliminado IS NOT TRUE LIMIT 1',
			array((int)$d['id_producto_maestro'], (int)$d['id_producto'], $id))->row()) {
			set_status_header(409);
			echo json_encode(['error' => 'Este producto ya está agregado. Si no lo ves en la lista, recarga la página.']);
			return;
		}
		$miData = array(
			'id_producto' => $d['id_producto'],
			'cantidad' => $d['cantidad'],
			'vigente' => $d['vigente'],
			'id_producto_maestro' => $d['id_producto_maestro'],
			'valor' => $d['valor'],
			'usar_destino_impresion' => $d['usar_destino_impresion'],
			'eliminado' => false,
			'id_dimpresion_maestro' => 0,
			'id_dimpresion_agregado' => 0,
		);
		$r = $this->Mproductos->grabarAgregados($id, $miData);
		// v1.34.1: el producto queda "Con Agregado" en cuanto tiene uno
		if (is_array($r) && !isset($r['error'])) $this->Mproductos->marcarConAgregado((int)$miData['id_producto_maestro']);
		echo json_encode($r);
	}

	/** v1.24.17: copia los agregados de este producto a otros (ventana Agregados > "Copiar a otros productos"). */
	public function copiarAgregados()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			set_status_header(401);
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		$r = $this->Mproductos->copiarAgregados((int)$this->input->post('id_origen'), (array)$this->input->post('destinos'),
			$this->input->post('actualizar') === '1');
		if (isset($r['error'])) set_status_header(400);
		echo json_encode($r);
	}

	public function actualizarCantidadProductoAgregado()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		$d = $this->input->post('detalle');
		$respuesta = array();
		foreach ($d as $value) {
			$idAgregado = $value['id_agregado'];
			$miData = array(
				'cantidad' => $value['cantidad']
			);
			$r = $this->Mproductos->actualizarCantidadProductoAgregado($idAgregado, $miData);
			$respuesta[] = $r;
		}
		echo json_encode($respuesta);
	}

	public function actualizarPreciosYComisiones()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		
		$idProducto = $this->input->post('id_producto');
		$precios = $this->input->post('precios');
		
		log_message('debug', 'Actualizando precios de producto: ' . $idProducto);
		log_message('debug', 'Precios recibidos: ' . print_r($precios, true));
		
		if (empty($precios)) {
			echo json_encode(['error' => 'No se recibieron precios']);
			return;
		}
		
		$preciosGrabados = array();
		
		foreach ($precios as $precio) {
			$dataPrecio = array(
				'id_producto' => $idProducto,
				'id_lprecio' => (int)$precio['id_lprecio'],
				'total' => (int)$precio['total']
			);
			
			// Solo agregar comisión si viene en el array
			if (isset($precio['monto_comision'])) {
				$dataPrecio['monto_comision'] = (float)$precio['monto_comision'];
			}
			
			$resultadoPrecio = $this->Mproductos->grabarPrecioProducto($dataPrecio);
			$preciosGrabados[] = $resultadoPrecio;
		}
		
		log_message('debug', 'Precios actualizados: ' . count($preciosGrabados));
		
		echo json_encode([
			'success' => true,
			'precios_actualizados' => count($preciosGrabados)
		]);
	}

	public function actualizarValorAdicionalProductoAgregado()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		$d = $this->input->post('detalle');
		$respuesta = array();
		foreach ($d as $value) {
			$idAgregado = $value['id_agregado'];
			$miData = array(
				'valor' => $value['valor']
			);
			$r = $this->Mproductos->actualizarValorAdicionalProductoAgregado($idAgregado, $miData);
			$respuesta[] = $r;
		}
		echo json_encode($respuesta);
	}

	public function HabilitarAgregado()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) > 0) {
			$id = $this->input->post('data');
			$miData = array(
				'vigente' => 't'
			);
			$r = $this->Mproductos->HabilitarAgregado($id, $miData);
			echo $r;
		}
	}

	public function DeshabilitarAgregado()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) > 0) {
			$id = $this->input->post('data');
			$miData = array(
				'vigente' => 'f'
			);
			$r = $this->Mproductos->DeshabilitarAgregado($id, $miData);
			echo $r;
		}
	}

	public function obtenerMensajesDelProducto()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		$idProducto = $this->input->post('id_producto');
		echo json_encode($this->Mproductos->obtenerMensajesDelProducto($idProducto));
	}

	public function grabarMsgProductos()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		$d = $this->input->post('prod');
		// La pantalla de Productos no envía id_mensaje_producto (siempre es un mensaje nuevo)
		$id = intval($d['id_mensaje_producto'] ?? 0);
		$miData = array(
			'id_producto' => $d['id_producto'],
			'mensaje' => $d['mensaje'],
			'vigente' => $d['vigente']
		);
		$r = $this->Mproductos->grabarMsgProductos($id, $miData);
		echo json_encode($r);
	}

	public function HabilitarMensajeProducto()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) > 0) {
			$id = $this->input->post('data');
			$miData = array(
				'vigente' => 't'
			);
			$r = $this->Mproductos->HabilitarMensajeProducto($id, $miData);
			echo $r;
		}
	}

	public function DeshabilitarMensajeProducto()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) > 0) {
			$id = $this->input->post('data');
			$miData = array(
				'vigente' => 'f'
			);
			$r = $this->Mproductos->DeshabilitarMensajeProducto($id, $miData);
			echo $r;
		}
	}

	public function obtenerListadoMenu()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		echo json_encode($this->Mproductos->obtenerListadoMenu());
	}

	/** v1.24.0: error de validación de menús: código 400 y {error}, para que la pantalla lo muestre. */
	private function errorMenu($mensaje)
	{
		set_status_header(400);
		echo json_encode(array('error' => $mensaje));
	}

	/** v1.24.0: texto de un nombre de menú / categoría: sin espacios de más, en mayúsculas y hasta $max letras. */
	private function nombreMenu($t, $max)
	{
		// sin < > (los nombres se muestran en tablas de la pantalla) ni caracteres de control
		$t = preg_replace('/[<>\x00-\x1F\x7F]+/u', ' ', is_scalar($t) ? (string)$t : '');
		return mb_substr(mb_strtoupper(trim(preg_replace('/\s+/u', ' ', $t))), 0, $max);
	}

	public function grabarNuevoMenu()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		$d = $this->input->post('prod');
		if (!is_array($d)) return $this->errorMenu('Datos inválidos');
		$id = intval($d['id_menu'] ?? 0);
		// v1.24.0: nombre obligatorio y sin repetir
		$nombre = $this->nombreMenu($d['nombre_menu'] ?? '', 50);
		if ($nombre === '') return $this->errorMenu('Escribe el nombre del menú');
		$this->load->helper('tenant');
		$db = get_tenant_db($this);
		if ($db->query('SELECT 1 FROM menu WHERE upper(trim(nombre_menu)) = ? AND id_menu <> ?', array($nombre, $id))->row()) {
			return $this->errorMenu('Ya hay un menú llamado "' . $nombre . '"');
		}
		$vig = $d['vigente'] ?? 1;
		$miData = array(
			'nombre_menu' => $nombre,
			'vigente' => ($vig === true || $vig === 't' || (string)$vig === '1' || $vig === 'true') ? 't' : 'f'
		);
		$r = $this->Mproductos->grabarNuevoMenu($id, $miData);
		echo json_encode($r);
	}

	public function obtenerCategoriaMenu()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		$id_menu = $this->input->post('id_menu');
		$solo_vigentes = $this->input->post('solo_vigentes') == '1';
		echo json_encode($this->Mproductos->obtenerCategoriaMenu($id_menu, $solo_vigentes));
	}

	public function grabarNuevaCategoria()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		$d = $this->input->post('prod');
		if (!is_array($d)) return $this->errorMenu('Datos inválidos');
		$id = intval($d['id_categoria_menu'] ?? 0);
		$vigente = isset($d['vigente']) ? ($d['vigente'] == 1 || $d['vigente'] === 't' ? 't' : 'f') : 't';
		// v1.24.0: el menú debe existir; nombre obligatorio y sin repetir dentro del mismo menú
		$idMenu = (int)($d['id_menu'] ?? 0);
		$d['nombre_categoria_menu'] = $this->nombreMenu($d['nombre_categoria_menu'] ?? '', 50);
		$d['id_menu'] = $idMenu;
		if ($d['nombre_categoria_menu'] === '') return $this->errorMenu('Escribe el nombre de la categoría');
		$this->load->helper('tenant');
		$db = get_tenant_db($this);
		if (!$db->query('SELECT 1 FROM menu WHERE id_menu = ?', array($idMenu))->row()) return $this->errorMenu('Elige un menú que exista');
		if ($db->query('SELECT 1 FROM categoria_menu WHERE id_menu = ? AND upper(trim(nombre_categoria_menu)) = ? AND id_categoria_menu <> ?',
			array($idMenu, $d['nombre_categoria_menu'], $id))->row()) {
			return $this->errorMenu('Ese menú ya tiene una categoría "' . $d['nombre_categoria_menu'] . '"');
		}
		if ($id) {
			// Edición: solo actualizar nombre, menú y vigente (preservar configuraciones existentes)
			$miData = array(
				'id_menu' => $d['id_menu'],
				'nombre_categoria_menu' => $d['nombre_categoria_menu'],
				'vigente' => $vigente,
			);
		} else {
			// Inserción: inicializar todos los campos
			$miData = array(
				'id_menu' => $d['id_menu'],
				'nombre_categoria_menu' => $d['nombre_categoria_menu'],
				'cantidad_minima' => 0,
				'cantidad_maxima' => 0,
				'costo_promedio_receta' => 0,
				'orden' => 0,
				'vigente' => $vigente,
				'estricto' => 'f',
				'permite_porcion' => 'f',
			);
		}
		$r = $this->Mproductos->grabarNuevaCategoria($id, $miData);
		echo json_encode($r);
	}

	public function listadoDeCategorias()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		echo json_encode($this->Mproductos->listadoDeCategorias());
	}

	public function obtenerProductosQueConformanElMenu()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		$id_categoria_menu = $this->input->post('id_categoria_menu');
		echo json_encode($this->Mproductos->obtenerProductosQueConformanElMenu($id_categoria_menu));
	}

	public function actualizarCostoRecetaCategoriaMenu()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		$d = $this->input->post('detalle');
		if (!is_array($d)) return $this->errorMenu('Datos inválidos');
		$respuesta = array();
		foreach ($d as $value) {
			if (!is_array($value)) continue;
			$idDetalleCategoriaMenu = (int)($value['id_detalle_categoria_menu'] ?? 0);
			$miData = array(
				'costo_receta' => is_numeric($value['costo_receta'] ?? null) ? max(0, (float)$value['costo_receta']) : 0   // v1.24.0
			);
			$r = $this->Mproductos->actualizarCostoRecetaCategoriaMenu($idDetalleCategoriaMenu, $miData);
			$respuesta[] = $r;
		}
		echo json_encode($respuesta);
	}

	public function actualizarCostoAdicionalVentaCategoriaMenu()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		$d = $this->input->post('detalle');
		if (!is_array($d)) return $this->errorMenu('Datos inválidos');
		foreach ($d as $value) {
			if (!is_array($value)) continue;
			$idDetalleCategoriaMenu = (int)($value['id_detalle_categoria_menu'] ?? 0);
			$miData = array(
				'costo_adicional_venta' => is_numeric($value['costo_adicional_venta'] ?? null) ? max(0, (int)round((float)$value['costo_adicional_venta'])) : 0   // v1.24.0
			);
			$this->Mproductos->actualizarCostoAdicionalVentaCategoriaMenu($idDetalleCategoriaMenu, $miData);
		}
	}

	public function grabarDetalleCategoria()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		$d = $this->input->post('prod');
		if (!is_array($d)) return $this->errorMenu('Datos inválidos');
		$id = intval($d['id_detalle_categoria_menu'] ?? 0);
		$idCat = (int)($d['id_categoria_menu'] ?? 0);
		$idProd = (int)($d['id_producto'] ?? 0);
		// v1.24.0: la categoría y el producto deben existir; el producto no puede ser otro menú ni estar dos veces en la categoría
		$this->load->helper('tenant');
		$db = get_tenant_db($this);
		if (!$db->query('SELECT 1 FROM categoria_menu WHERE id_categoria_menu = ?', array($idCat))->row()) return $this->errorMenu('La categoría ya no existe: recarga la página');
		$prod = $db->query('SELECT nombre_producto, COALESCE(id_menu, 0) AS id_menu FROM producto WHERE id_producto = ?', array($idProd))->row();
		if (!$prod) return $this->errorMenu('Elige un producto que exista');
		if ((int)$prod->id_menu > 0) return $this->errorMenu($prod->nombre_producto . ' es un menú: no puede ir dentro de otro menú');
		if ($db->query("SELECT 1 FROM detalle_categoria_menu WHERE id_categoria_menu = ? AND id_producto = ? AND vigente IS NOT FALSE AND id_detalle_categoria_menu <> ?",
			array($idCat, $idProd, $id))->row()) {
			return $this->errorMenu($prod->nombre_producto . ' ya está en esta categoría');
		}
		$numero = function ($v) { return is_numeric($v) ? max(0, (float)$v) : 0; };
		$si = function ($v) { return ($v === true || $v === 't' || $v === 'true' || (string)$v === '1') ? 't' : 'f'; };
		$miData = array(
			'id_categoria_menu' => $idCat,
			'id_producto' => $idProd,
			'costo_receta' => $numero($d['costo_receta'] ?? 0),
			'costo_adicional_venta' => (int)round($numero($d['costo_adicional_venta'] ?? 0)),
			// v1.24.0: antes se grababan siempre en "sí" (desmarcar "imprimir" no servía: igual salía en la comanda)
			'permite_cambio' => $si($d['permite_cambio'] ?? true),
			'vigente' => 't',
			'alto_costo' => 'f',
			'imprimir' => $si($d['imprimir'] ?? true),
		);
		$r = $this->Mproductos->grabarDetalleCategoria($id, $miData);
		echo json_encode($r);
	}

	public function ObtenerConfiguracionesCategoriaMenu()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		$id_categoria_menu = $this->input->post('id_categoria_menu');
		echo json_encode($this->Mproductos->ObtenerConfiguracionesCategoriaMenu($id_categoria_menu));
	}

	public function actualizarConfiguracionCategoriaMenu()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		$d = $this->input->post('detalle');
		if (!is_array($d)) return $this->errorMenu('Datos inválidos');
		$respuesta = array();
		foreach ($d as $value) {
			if (!is_array($value)) continue;
			$idCategoriaMenu = (int)($value['id_categoria_menu'] ?? 0);
			// v1.24.0: mínimo y máximo enteros desde 0 (máximo 0 = sin límite) y el máximo no menor que el mínimo
			$min = is_numeric($value['cantidad_minima'] ?? null) ? (int)$value['cantidad_minima'] : 0;
			$max = is_numeric($value['cantidad_maxima'] ?? null) ? (int)$value['cantidad_maxima'] : 0;
			if ($min < 0 || $max < 0 || $min > 50 || $max > 50) return $this->errorMenu('La cantidad mínima y máxima van de 0 a 50');
			if ($max > 0 && $max < $min) return $this->errorMenu('La cantidad máxima no puede ser menor que la mínima');
			$est = $value['estricto'] ?? false;
			$miData = array(
				'cantidad_minima' => $min,
				'cantidad_maxima' => $max,
				'costo_promedio_receta' => is_numeric($value['costo_promedio_receta'] ?? null) ? max(0, (float)$value['costo_promedio_receta']) : 0,
				'estricto' => ($est === true || $est === 't' || $est === 'true' || (string)$est === '1') ? 't' : 'f',
			);
			$r = $this->Mproductos->actualizarConfiguracionCategoriaMenu($idCategoriaMenu, $miData);
			$respuesta[] = $r;
		}
		echo json_encode($respuesta);
	}

	public function ObtenerUltimoIdProducto()
    {
        if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        echo json_encode($this->Mproductos->ObtenerUltimoIdProducto());
    }

	public function obtenerListasDePrecios()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$query = $this->db->query('SELECT id_lprecio as id_detalle_lprecio, nombre_lprecio FROM lprecio ORDER BY nombre_lprecio');
		echo json_encode($query->result());
	}

	public function verificarColumnaComision()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		
		$existe = $this->Mproductos->verificarColumnaExiste('detalle_lprecio', 'monto_comision');
		
		echo json_encode([
			'existe' => $existe,
			'habilitada' => $this->Mproductos->comisionHabilitada(),   // v1.24.1: Configuraciones
			'tabla' => 'detalle_lprecio',
			'columna' => 'monto_comision'
		]);
	}

	public function DeshabilitarDetalleCategoria()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		$id = $this->input->post('id');
		echo json_encode($this->Mproductos->DeshabilitarDetalleCategoria($id));
	}

	// ====================== v1.25.0: Promos de sushi (Mpromosushi) ======================

	/** Respuesta JSON de las promos de sushi: sesión vencida = 401; un error del modelo = 400. */
	private function respuestaSushi($fn)
	{
		header('Content-Type: application/json; charset=utf-8');
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			set_status_header(401);
			echo json_encode(['error' => 'Sesión expirada']);
			return;
		}
		$this->load->model('Mpromosushi');
		$r = $fn($this->Mpromosushi, $this->input);
		if (isset($r['error'])) set_status_header(400);
		echo json_encode($r);
	}

	/** v1.36.0: promos normales (Carta > Promos). Sin id: la lista; con id: una promo. */
	public function promos()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) { set_status_header(401); echo json_encode(array('error' => 'Sesión expirada')); return; }
		$this->load->model('Mpromos');
		$id = (int)$this->input->get_post('id');
		$r = $id > 0 ? $this->Mpromos->promo($id) : $this->Mpromos->datos();
		if (isset($r['error'])) set_status_header(400);
		echo json_encode($r);
	}

	public function grabarPromo()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) { set_status_header(401); echo json_encode(array('error' => 'Sesión expirada')); return; }
		$this->load->model('Mpromos');
		$r = $this->Mpromos->grabar($this->input->post());
		if (isset($r['error'])) set_status_header(400);
		echo json_encode($r);
	}

	public function obtenerPromosSushi()
	{
		$this->respuestaSushi(function ($m) { return $m->datos(); });
	}

	public function obtenerPromoSushi()
	{
		$this->respuestaSushi(function ($m, $in) { return $m->promo((int)$in->post('id_producto')); });
	}

	public function grabarPromoSushi()
	{
		$this->respuestaSushi(function ($m, $in) {
			return $m->grabarPromo(array(
				'id_producto' => $in->post('id_producto'), 'nombre' => $in->post('nombre'), 'piezas' => $in->post('piezas'),
				'paso' => $in->post('paso'), 'max_rellenos' => $in->post('max_rellenos'), 'precio' => $in->post('precio'),
				'id_familia' => $in->post('id_familia'), 'id_dimpresion' => $in->post('id_dimpresion'), 'vigente' => $in->post('vigente'),
				'modo' => $in->post('modo'), 'rolls' => $in->post('rolls'), 'piezas_rol' => $in->post('piezas_rol'),   // v1.26.0
				// v1.32.0: precio según el envoltorio, máximos y quitar "fija"
				'precio_envoltorio' => $in->post('precio_envoltorio'), 'max_envoltorios' => $in->post('max_envoltorios'),
				'max_extras' => $in->post('max_extras'), 'max_salsas' => $in->post('max_salsas'), 'fija' => $in->post('fija'),
			));
		});
	}

	public function agregarOpcionSushi()
	{
		$this->respuestaSushi(function ($m, $in) {
			return $m->agregarOpcion((int)$in->post('id_promo'), (string)$in->post('tipo'), (int)$in->post('id_producto'), $in->post('valor'));
		});
	}

	public function crearOpcionSushi()
	{
		$this->respuestaSushi(function ($m, $in) {
			return $m->crearOpcion((int)$in->post('id_promo'), (string)$in->post('tipo'), (string)$in->post('nombre'), $in->post('valor'));
		});
	}

	public function valorOpcionSushi()
	{
		$this->respuestaSushi(function ($m, $in) { return $m->valorOpcion((int)$in->post('id_opcion'), $in->post('valor')); });
	}

	/** v1.32.1: un límite de lo que el cliente puede elegir (desde la pestaña de esa sección) */
	public function limitePromoSushi()
	{
		$this->respuestaSushi(function ($m, $in) { return $m->guardarLimite((int)$in->post('id_promo'), (string)$in->post('tipo'), $in->post('valor')); });
	}

	/** v1.32.1: quitar "fija" a una promo */
	public function quitarFijaSushi()
	{
		$this->respuestaSushi(function ($m, $in) { return $m->quitarFija((int)$in->post('id_promo')); });
	}

	/** v1.32.0: precio de la promo con ese envoltorio */
	public function precioOpcionSushi()
	{
		$this->respuestaSushi(function ($m, $in) { return $m->precioOpcion((int)$in->post('id_opcion'), $in->post('precio')); });
	}

	/** v1.32.0: agregar varios productos a la vez, o toda una familia */
	public function agregarVariasSushi()
	{
		$this->respuestaSushi(function ($m, $in) {
			return $m->agregarVarias((int)$in->post('id_promo'), (string)$in->post('tipo'), $in->post('ids'), (int)$in->post('id_familia'), $in->post('valor'));
		});
	}

		public function quitarOpcionSushi()
	{
		$this->respuestaSushi(function ($m, $in) { return $m->quitarOpcion((int)$in->post('id_opcion')); });
	}

	public function copiarCatalogoSushi()
	{
		$this->respuestaSushi(function ($m, $in) { return $m->copiarCatalogo((int)$in->post('id_origen'), (int)$in->post('id_destino')); });
	}
}
