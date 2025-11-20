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

		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$data = array(
			'nombreUsuario' => $this->session->userdata('nombre_usuario'),
			//'modulosDisponibles' => json_decode($this->Mroles->modulosDisponibles($this->session->userdata('id_rol')), true)
		);
		$this->load->view('menuTop.php', $data);
				$this->load->view('productos/productos.php');
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
				$this->load->view('recetas/jsNewFamiliaReceta.php');
				$this->load->view('recetas/jsNewSeccion.php');
/*
		if ($this->session->userdata('nombre_usuario')) {
			if ($this->session->userdata('id_rol') == 1) {
				$this->load->view('menuTop.php', $data);
				$this->load->view('productos/productos.php');
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
				$this->load->view('recetas/jsNewFamiliaReceta.php');
				$this->load->view('recetas/jsNewSeccion.php');
			} else {
				redirect(base_url());
			}
		}*/
	}

	public function ListadoCartaDeProductos()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		// $id_familia = $this->input->post('id_familia');
		echo json_encode($this->Mproductos->ListadoCartaDeProductos(/*$id_familia*/));
	}

	public function obtenerPreciosDeVenta()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$idProducto = $this->input->post('id_producto');
		echo json_encode($this->Mproductos->obtenerPreciosDeVenta($idProducto));
	}

	public function ListadoDeSubFamilias()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		echo json_encode($this->Mproductos->ListadoDeSubFamilias());
	}


	public function ListadoDeUmedida()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		echo json_encode($this->Mproductos->ListadoDeUmedida());
	}


	public function ListadoDestinoDeImpresion()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		echo json_encode($this->Mproductos->ListadoDestinoDeImpresion());
	}


	public function ListadoDeBodega()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		echo json_encode($this->Mproductos->ListadoDeBodega());
	}


	public function ListadoDeImpuestos()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		echo json_encode($this->Mproductos->ListadoDeImpuestos());
	}

	public function ListadoDeImpuestosAdicionales()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		echo json_encode($this->Mproductos->ListadoDeImpuestosAdicionales());
	}

	public function ListadoDeReceta()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		echo json_encode($this->Mproductos->ListadoDeReceta());
	}

	public function ListadoDeMenu()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		echo json_encode($this->Mproductos->ListadoDeMenu());
	}

	public function grabarNuevoProducto()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$d = $this->input->post('prod');
		$id = intval($d['id_producto']);

			foreach ($d as $key => $value) {
		if ($value === "" || $value === null) {
			// Si el campo se espera que sea numérico, lo dejamos en 0
			if (strpos($key, 'id_') === 0 || in_array($key, [
				'precio_bruto', 'precio_ucompra', 'valor1', 'valor2',
				'stock_minimo', 'pponderado', 'valor_ila', 'orden'
			])) {
				$d[$key] = 0;
			}
		}
		}
		//$cb = intval($d['cbarra']);
		$miData = array(
			'nombre_producto' => $d['nombre_producto'],
			'cbarra' => $d['cbarra'],
			'impto_iva' => $d['impto_iva'],
			'impto_ila' => $d['impto_ila'],
			'id_familia' => $d['id_familia'],
			'id_umedida' => $d['id_umedida'],
			'precio_bruto' => $d['precio_bruto'],
			'espack' => $d['espack'],
			'vigente' => $d['vigente'],
			'inventariable' => $d['inventariable'],
			'valor_ila' => $d['valor_ila'],
			'precio_ucompra' => $d['precio_ucompra'],
			'pponderado' => $d['pponderado'],
			'stock_minimo' => $d['stock_minimo'],
			'cbarra_balanza' => $d['cbarra_balanza'],
			'con_agregado' => $d['con_agregado'],
			'venta_publico' => $d['venta_publico'],
			'descripcion_corta' => $d['descripcion_corta'],
			'permite_porcion' => $d['permite_porcion'],
			'id_subfamilia' => $d['id_subfamilia'],
			'id_dimpresion' => $d['id_dimpresion'],
			'id_receta' => $d['id_receta'],
			'orden' => $d['orden'],
			'permite_cambio' => $d['permite_cambio'],
			'valor1' => $d['valor1'],
			'valor2' => $d['valor2'],
			'id_bodega' => $d['id_bodega'],
			'es_menu' => $d['es_menu'],
			'id_menu' => $d['id_menu'],
			'venta_segun_stock' => $d['venta_segun_stock'],
			'elaboracion_propia' => $d['elaboracion_propia'],
			'permitir_venta_sin_precio' => $d['permitir_venta_sin_precio'],
			'venta_uno_a_uno' => $d['venta_uno_a_uno'],
			'pedir_cantidad_en_venta' => $d['pedir_cantidad_en_venta'],
			'detalle_preparacion' => $d['detalle_preparacion'],
			'tiempo_preparacion' => $d['tiempo_preparacion'],
			'mostrar_detalle_insumos_receta' => $d['mostrar_detalle_insumos_receta'],
			'ver_en_dispositivos_moviles' => $d['ver_en_dispositivos_moviles'],
			'permite_cortesia' => $d['permite_cortesia']
		);
		$r = $this->Mproductos->grabarNuevoProducto($id, $miData);
		echo json_encode($r);
	}

	public function obtenerEAN13()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		//$this->db = $this->load->database($this->session->userdata('rut_cliente'), TRUE);
		$q = "SELECT * from obtenerean13() as ean13";
		$consulta = $this->db->query($q);
		$sampleData = $consulta->result();
		$ean13 = "";
		foreach ($sampleData as $key) {
			$ean13 = $key->ean13;
		}
		// Usabilidade: Ao Chamar a função com os 12 algarismos retorna já com o dígito.
		echo $this->digitoEAN13($ean13);
	}
	function digitoEAN13($cod)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
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
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$d = $this->input->post('prod');
		$id = intval($d['id_familia']);
		$miData = array(
			'nombre_familia' => $d['nombre_familia'],
			'venta_directa' => $d['venta_directa'],
			'id_dimpresion' => $d['id_dimpresion'],
			'orden_familia' => $d['orden_familia'],
			'imprimir_en_detalle_pedido' => $d['imprimir_en_detalle_pedido'],
			'alias' => $d['alias'],
		);
		$r = $this->Mproductos->grabarNuevaFamilia($id, $miData);
		echo json_encode($r);
	}

	public function grabarNuevaSubFamilia()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
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
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
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
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id_producto = $this->input->post('id_producto');
		echo json_encode($this->Mproductos->ListadoDeProductos(/*$id_producto*/));
	}

	public function obtenerAgregadosDelProducto()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$idProducto = $this->input->post('id_producto_maestro');
		echo json_encode($this->Mproductos->obtenerAgregadosDelProducto($idProducto));
	}

	public function grabarAgregados()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$d = $this->input->post('prod');
		$id = intval($d['id_agregado']);
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
		echo json_encode($r);
	}

	public function actualizarCantidadProductoAgregado()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$d = $this->input->post('detalle');
		$respuesta = array();
		foreach ($d as $value) {
			$idAgregado = $value['id_agregado'];
			$miData = array(
				'cantidad' => $value['cantidad']
			);
			$r = $this->Mproductos->actualizarCantidadProductoAgregado($idAgregado, $miData);
			$respueta[] = $r;
		}
		echo json_encode($respueta);
	}

	public function actualizarValorAdicionalProductoAgregado()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$d = $this->input->post('detalle');
		$respuesta = array();
		foreach ($d as $value) {
			$idAgregado = $value['id_agregado'];
			$miData = array(
				'valor' => $value['valor']
			);
			$r = $this->Mproductos->actualizarValorAdicionalProductoAgregado($idAgregado, $miData);
			$respueta[] = $r;
		}
		echo json_encode($respueta);
	}

	public function HabilitarAgregado()
	{
		if (strlen($this->session->userdata('nombre_usuario')) > 0) {
			$id = $this->input->post('data');
			$miData = array(
				'vigente' => true
			);
			$r = $this->Mproductos->HabilitarAgregado($id, $miData);
			echo $r;
		}
	}

	public function DeshabilitarAgregado()
	{
		if (strlen($this->session->userdata('nombre_usuario')) > 0) {
			$id = $this->input->post('data');
			$miData = array(
				'vigente' => false
			);
			$r = $this->Mproductos->DeshabilitarAgregado($id, $miData);
			echo $r;
		}
	}

	public function obtenerMensajesDelProducto()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$idProducto = $this->input->post('id_producto');
		echo json_encode($this->Mproductos->obtenerMensajesDelProducto($idProducto));
	}

	public function grabarMsgProductos()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$d = $this->input->post('prod');
		$id = intval($d['id_mensaje_producto']);
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
		if (strlen($this->session->userdata('nombre_usuario')) > 0) {
			$id = $this->input->post('data');
			$miData = array(
				'vigente' => true
			);
			$r = $this->Mproductos->HabilitarMensajeProducto($id, $miData);
			echo $r;
		}
	}

	public function DeshabilitarMensajeProducto()
	{
		if (strlen($this->session->userdata('nombre_usuario')) > 0) {
			$id = $this->input->post('data');
			$miData = array(
				'vigente' => false
			);
			$r = $this->Mproductos->DeshabilitarMensajeProducto($id, $miData);
			echo $r;
		}
	}

	/* public function verificarSiExisteProductoPorNombre(){// listado de productos para agregados
      if (strlen($this->session->userdata('nombre_usuario'))==0) {
        redirect(base_url());//redireccionar al login
      }
      $nombreProducto = $this->input->post('nombre_producto');
      echo json_encode($this->Mproductos->verificarSiExisteProductoPorNombre($nombreProducto));
  }*/

	public function obtenerListadoMenu()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		echo json_encode($this->Mproductos->obtenerListadoMenu());
	}

	public function grabarNuevoMenu()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$d = $this->input->post('prod');
		$id = intval($d['id_menu']);
		$miData = array(
			'nombre_menu' => $d['nombre_menu'],
			'vigente' => $d['vigente']
		);
		$r = $this->Mproductos->grabarNuevoMenu($id, $miData);
		echo json_encode($r);
	}
	/*
      public function habilitarVigenciaMenu(){
        if (strlen($this->session->userdata('nombre_usuario'))>0) {
            $id_menu = $this->input->post('data');
            $miData=array(
              'vigente'=>true
            );
            $r=$this->Mproductos->habilitarVigenciaMenu($id_menu,$miData);
            echo $r;
        }
    }

      public function DeshabilitarVigenciaMenu(){
        if (strlen($this->session->userdata('nombre_usuario'))>0) {
            $id = $this->input->post('data');
            $miData=array(
              'vigente'=>false
            );
            $r=$this->Mproductos->DeshabilitarVigenciaMenu($id,$miData);
            echo $r;
        }
    }*/

	public function obtenerCategoriaMenu()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id_menu = $this->input->post('id_menu');
		echo json_encode($this->Mproductos->obtenerCategoriaMenu($id_menu));
	}

	public function grabarNuevaCategoria()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$d = $this->input->post('prod');
		$id = intval($d['id_categoria_menu']);
		$miData = array(
			'id_menu' => $d['id_menu'],
			'nombre_categoria_menu' => $d['nombre_categoria_menu'],
			'cantidad_minima' => 0,
			'cantidad_maxima' => 0,
			'costo_promedio_receta' => 0,
			'orden' => 0,
			'vigente' => $d['vigente'],
			'estricto' => false,
			'permite_porcion' => false,
		);
		$r = $this->Mproductos->grabarNuevaCategoria($id, $miData);
		echo json_encode($r);
	}

	public function obtenerProductosQueConformanElMenu()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id_categoria_menu = $this->input->post('id_categoria_menu');
		echo json_encode($this->Mproductos->obtenerProductosQueConformanElMenu($id_categoria_menu));
	}

	public function actualizarCostoRecetaCategoriaMenu()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$d = $this->input->post('detalle');
		$respuesta = array();
		foreach ($d as $value) {
			$idDetalleCategoriaMenu = $value['id_detalle_categoria_menu'];
			$miData = array(
				'costo_receta' => $value['costo_receta']
			);
			$r = $this->Mproductos->actualizarCostoRecetaCategoriaMenu($idDetalleCategoriaMenu, $miData);
			$respueta[] = $r;
		}
		echo json_encode($respueta);
	}

	public function actualizarCostoAdicionalVentaCategoriaMenu()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$d = $this->input->post('detalle');
		$respuesta = array();
		foreach ($d as $value) {
			$idDetalleCategoriaMenu = $value['id_detalle_categoria_menu'];
			$miData = array(
				'costo_adicional_venta' => $value['costo_adicional_venta']
			);
			$r = $this->Mproductos->actualizarCostoAdicionalVentaCategoriaMenu($idDetalleCategoriaMenu, $miData);
			$respueta[] = $r;
		}
		echo json_encode($respueta);
	}

	public function grabarDetalleCategoria()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$d = $this->input->post('prod');
		$id = intval($d['id_detalle_categoria_menu']);
		$miData = array(
			'id_categoria_menu' => $d['id_categoria_menu'],
			'id_producto' => $d['id_producto'],
			'costo_receta' =>  $d['costo_receta'],
			'costo_adicional_venta' =>  $d['costo_adicional_venta'],
			'permite_cambio' =>  $d['permite_cambio'],
			'vigente' =>  true,
			'alto_costo' => false,
			'imprimir' => $d['imprimir'],
		);
		$r = $this->Mproductos->grabarDetalleCategoria($id, $miData);
		echo json_encode($r);
	}

	public function listadoDeCategorias()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		echo json_encode($this->Mproductos->listadoDeCategorias());
	}

	public function ObtenerConfiguracionesCategoriaMenu()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$id_categoria_menu = $this->input->post('id_categoria_menu');
		echo json_encode($this->Mproductos->ObtenerConfiguracionesCategoriaMenu($id_categoria_menu));
	}

	public function actualizarConfiguracionCategoriaMenu()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$d = $this->input->post('detalle');
		$respuesta = array();
		foreach ($d as $value) {
			$idCategoriaMenu = $value['id_categoria_menu'];
			$miData = array(
				'cantidad_minima' => $value['cantidad_minima'],
				'cantidad_maxima' => $value['cantidad_maxima'],
				'costo_promedio_receta' => $value['costo_promedio_receta'],
				'estricto' => $value['estricto'],
			);
			$r = $this->Mproductos->actualizarConfiguracionCategoriaMenu($idCategoriaMenu, $miData);
			$respueta[] = $r;
		}
		echo json_encode($respueta);
	}

	public function ObtenerUltimoIdProducto()
    {
        if (strlen($this->session->userdata('nombre_usuario')) == 0) {
            redirect(base_url()); //redireccionar al login
        }
        echo json_encode($this->Mproductos->ObtenerUltimoIdProducto());
    }
	public function obtenerListasDePrecios()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$query = $this->db->query('SELECT id_lprecio as id_detalle_lprecio, nombre_lprecio FROM lprecio ORDER BY nombre_lprecio');
		echo json_encode($query->result());
	}
}