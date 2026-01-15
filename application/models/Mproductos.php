<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mproductos extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}

	public function verificarColumnaExiste($tabla, $columna)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		
		$query = $this->db->query("
			SELECT EXISTS (
				SELECT 1 
				FROM information_schema.columns 
				WHERE table_name = '$tabla' 
				AND column_name = '$columna'
			) as existe
		");
		
		$result = $query->row();
		return $result->existe === 't' || $result->existe === true;
	}

	public function ListadoCartaDeProductos(/*$id_familia*/)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		 $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);

		$q = "SELECT p.id_producto, p.cbarra, p.nombre_producto, f.nombre_familia, u.nombre_umedida, i.nombre_impuesto as iva, ii.nombre_impuesto as impto_adicional,
        d.nombre_dimpresion, b.nombre_bodega, p.pponderado,
        (select nombre_menu from menu m where m.id_menu=p.id_menu) as menu,
        (select nombre_subfamilia from subfamilia sf where sf.id_subfamilia=p.id_subfamilia) as subfamilia,
        (select nombre_receta from receta r where r.id_receta=p.id_receta) as receta, 
        p.vigente, p.con_agregado, p.venta_publico, p.permite_porcion, p.venta_segun_stock, p.elaboracion_propia, p.permitir_venta_sin_precio, p.venta_uno_a_uno, p.pedir_cantidad_en_venta,
        p.ver_en_dispositivos_moviles,p.permite_cortesia
        from producto p
        join familia f on p.id_familia=f.id_familia
        join umedida u on p.id_umedida=u.id_umedida
        join impuesto i on p.impto_iva=i.id_impuesto
        join impuesto ii on p.impto_ila=ii.id_impuesto
        join dimpresion d on p.id_dimpresion=d.id_dimpresion
        join bodega b on p.id_bodega=b.id_bodega
        order by p.nombre_producto asc";
		/*if ($id_familia > 0) {
				  $q = $q . " and p.id_familia=$id_familia";
			  }
			  $q = $q . "order by p.nombre_producto asc";*/
		$consulta = $this->db->query($q);
		$sampleData = $consulta->result();
		$data = array();

		foreach ($sampleData as $key) {
			array_push($data, array(
				"id_producto" => $key->id_producto,
				"cbarra" => $key->cbarra,
				"nombre_producto" => $key->nombre_producto,
				"nombre_familia" => $key->nombre_familia,
				"nombre_umedida" => $key->nombre_umedida,
				"iva" => $key->iva,
				"impto_adicional" => $key->impto_adicional,
				"nombre_dimpresion" => $key->nombre_dimpresion,
				"nombre_bodega" => $key->nombre_bodega,
				"pponderado" => $key->pponderado,
				"menu" => $key->menu,
				"subfamilia" => $key->subfamilia,
				"receta" => $key->receta,
				'vigente' => ($key->vigente == 't' ? 'SI' : 'NO'),
				'con_agregado' => ($key->con_agregado == 't' ? 'SI' : 'NO'),
				'venta_publico' => ($key->venta_publico == 't' ? 'SI' : 'NO'),
				'permite_porcion' => ($key->permite_porcion == 't' ? 'SI' : 'NO'),
				'venta_segun_stock' => ($key->venta_segun_stock == 't' ? 'SI' : 'NO'),
				'elaboracion_propia' => ($key->elaboracion_propia == 't' ? 'SI' : 'NO'),
				'permitir_venta_sin_precio' => ($key->permitir_venta_sin_precio == 't' ? 'SI' : 'NO'),
				'venta_uno_a_uno' => ($key->venta_uno_a_uno == 't' ? 'SI' : 'NO'),
				'pedir_cantidad_en_venta' => ($key->pedir_cantidad_en_venta == 't' ? 'SI' : 'NO'),
				'ver_en_dispositivos_moviles' => ($key->ver_en_dispositivos_moviles == 't' ? 'SI' : 'NO'),
				'permite_cortesia' => ($key->permite_cortesia == 't' ? 'SI' : 'NO')
			)
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

	public function obtenerPreciosDeVenta($idProducto){
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		
		// Verificar si existe la columna
		$tieneComision = $this->verificarColumnaExiste('detalle_lprecio', 'monto_comision');
		
		// Construir query dinámicamente
		if ($tieneComision) {
			$select = 'd.id_detalle_lprecio, d.id_lprecio, l.nombre_lprecio, d.total, COALESCE(d.monto_comision, 0) as monto_comision';
		} else {
			$select = 'd.id_detalle_lprecio, d.id_lprecio, l.nombre_lprecio, d.total, 0 as monto_comision';
		}
		
		$query = $this->db->query("
			SELECT $select 
			FROM detalle_lprecio d 
			JOIN lprecio l ON d.id_lprecio=l.id_lprecio
			WHERE d.id_producto = $idProducto
			ORDER BY l.nombre_lprecio
		");
		
		return $query->result();
	}

	public function ListadoDeSubFamilias()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		 $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$q = "SELECT id_subfamilia, nombre_subfamilia from subfamilia order by nombre_subfamilia asc";
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function ListadoDeUmedida()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		 $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$q = "SELECT id_umedida, nombre_umedida from umedida order by nombre_umedida asc";
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function ListadoDestinoDeImpresion()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		 $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$q = "SELECT id_dimpresion, nombre_dimpresion from dimpresion order by nombre_dimpresion asc";
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function ListadoDeBodega()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		 $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$q = "SELECT id_bodega, nombre_bodega from bodega order by nombre_bodega asc";
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function ListadoDeImpuestos()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$q = "SELECT id_impuesto, nombre_impuesto from impuesto 
        where nombre_impuesto ilike '%IVA%' OR nombre_impuesto ilike '%EXENTO%'
        order by nombre_impuesto asc";
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function ListadoDeImpuestosAdicionales()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$q = "SELECT id_impuesto, nombre_impuesto from impuesto
        order by nombre_impuesto asc";
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function ListadoDeMenu()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$q = "SELECT id_menu, nombre_menu from menu order by nombre_menu asc";
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function ListadoDeReceta()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$q = "SELECT id_receta, nombre_receta from receta order by nombre_receta asc";
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function grabarNuevoProducto($id, $data)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		 $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		if ($id) {
			$this->db->where('id_producto', $id);
			$this->db->update('producto', $data);
			$consulta = $this->db->query("select " . $id . "  as id_producto" );
		}
		else {
			$this->db->insert('producto', $data);
			$consulta = $this->db->query("SELECT CURRVAL('producto_id_producto_seq'::regclass) as id_producto");
		}
		return $consulta->result();
	}

	public function grabarNuevaFamilia($id, $data)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		if ($id) {
			$this->db->where('id_familia', $id);
			$this->db->update('familia', $data);
			$consulta = $this->db->query("select " . $id . " as id_familia");
		} else {
			$this->db->insert('familia', $data);
			$consulta = $this->db->query("SELECT CURRVAL('familia_id_familia_seq'::regclass) as id_familia");
		}
		return $consulta->result();
	}

	public function existeCodigoBarra($cbarra, $id_producto = null)
	{
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		
		$this->db->where('cbarra', $cbarra);
		
		// Si es una edición, excluir el producto actual
		if ($id_producto !== null && $id_producto > 0) {
			$this->db->where('id_producto !=', $id_producto);
		}
		
		return $this->db->count_all_results('producto') > 0;
	}

	public function obtenerSiguienteCodigoBarra()
	{
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);

		// Obtener el último código de barras como número
		$query = $this->db->query("
			SELECT COALESCE(MAX(CAST(cbarra AS INTEGER)), 0) + 1 as siguiente_codigo
			FROM producto
			WHERE cbarra ~ '^[0-9]+$'
		");

		$row = $query->row();
		return $row ? $row->siguiente_codigo : 1;
	}

	public function grabarNuevaSubFamilia($id, $data)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		if ($id) {
			$this->db->where('id_subfamilia', $id);
			$this->db->update('subfamilia', $data);
			$consulta = $this->db->query("select " . $id . " as id_subfamilia");
		} else {
			$this->db->insert('subfamilia', $data);
			$consulta = $this->db->query("SELECT CURRVAL('subfamilia_id_subfamilia_seq'::regclass) as id_subfamilia");
		}
		return $consulta->result();
	}

	public function grabarNuevaBodega($id, $data)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		 $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		if ($id) {
			$this->db->where('id_bodega', $id);
			$this->db->update('bodega', $data);
			$consulta = $this->db->query("select " . $id . " as id_bodega");
		} else {
			$this->db->insert('bodega', $data);
			$consulta = $this->db->query("SELECT CURRVAL('bodega_id_bodega_seq'::regclass) as id_bodega");
		}
		return $consulta->result();
	}

	public function ListadoDeProductos(/*$id_producto*/)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		 $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$q = "SELECT id_producto, nombre_producto from producto order by nombre_producto asc";
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function obtenerAgregadosDelProducto($idProducto)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		 $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$q = 'SELECT a.id_agregado, a.id_producto, round(a.cantidad) as cantidad, a.valor, a.vigente, p.nombre_producto, pm.nombre_producto as producto_maestro 
        from agregados a
        join producto p on p.id_producto=a.id_producto
        join producto pm on pm.id_producto=a.id_producto_maestro
      	where a.id_producto_maestro=' . $idProducto . ' and a.eliminado = false order by p.nombre_producto';
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function grabarAgregados($id, $data)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		 $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		if ($id) {
			$this->db->where('id_agregado', $id);
			$this->db->update('agregados', $data);
			$consulta = $this->db->query("select " . $id . " as id_agregado");
		} else {
			$this->db->insert('agregados', $data);
			$consulta = $this->db->query("SELECT CURRVAL('agregados_id_agregado_seq'::regclass) as id_agregado");
		}
		return $consulta->result();
	}

	public function actualizarCantidadProductoAgregado($idAgregado, $data)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		 $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$this->db->where('id_agregado', $idAgregado);
		$this->db->update('agregados', $data);
		$consulta = $this->db->query("SELECT $idAgregado as id_agregado from agregados where id_agregado=$idAgregado");
		return $consulta->result();
	}

	public function actualizarValorAdicionalProductoAgregado($idAgregado, $data)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$this->db->where('id_agregado', $idAgregado);
		$this->db->update('agregados', $data);
		$consulta = $this->db->query("SELECT $idAgregado as id_agregado from agregados where id_agregado=$idAgregado");
		return $consulta->result();
	}

	public function HabilitarAgregado($id, $data)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		 $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$this->db->where('id_agregado', $id);
		$r = $this->db->update('agregados', $data);
		return $r;
	}

	public function DeshabilitarAgregado($id, $data)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$this->db->where('id_agregado', $id);
		$r = $this->db->update('agregados', $data);
		return $r;
	}

	public function obtenerMensajesDelProducto($idProducto)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$q = 'SELECT mp.id_mensaje_producto, p.nombre_producto, mp.mensaje, mp.vigente
        from mensaje_producto mp
        join producto p on p.id_producto=mp.id_producto
        where mp.id_producto=' . $idProducto . ' order by mp.id_mensaje_producto';
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function grabarMsgProductos($id, $data)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		if ($id) {
			$this->db->where('id_mensaje_producto', $id);
			$this->db->update('mensaje_producto', $data);
			$consulta = $this->db->query("select " . $id . " as id_mensaje_producto");
		} else {
			$this->db->insert('mensaje_producto', $data);
			$consulta = $this->db->query("SELECT CURRVAL('mensaje_producto_id_mensaje_producto_seq'::regclass) as id_mensaje_producto");
		}
		return $consulta->result();
	}

	public function HabilitarMensajeProducto($id, $data)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$this->db->where('id_mensaje_producto', $id);
		$r = $this->db->update('mensaje_producto', $data);
		return $r;
	}

	public function DeshabilitarMensajeProducto($id, $data)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		 $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$this->db->where('id_mensaje_producto', $id);
		$r = $this->db->update('mensaje_producto', $data);
		return $r;
	}

	public function obtenerListadoMenu()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		 $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$q = "SELECT * from menu order by nombre_menu";
		$consulta = $this->db->query($q);
		$sampleData = $consulta->result();
		$data = array();

		foreach ($sampleData as $key) {
			array_push($data, array(
				"id_menu" => $key->id_menu,
				"nombre_menu" => $key->nombre_menu,
				'vigente' => ($key->vigente == 't' ? 'SI' : 'NO'),
				// 'btn_accion' => ($key->vigente == 't' ? "<button type='button' class='btn-block btn-danger text-center'><i class='fa fa-times-circle'></i> DESHABILITAR MENU</button>" : "<button onclick='habilitarVigenciaMenu(" . $key->id_menu . ")' type='button' class='btn-block btn-primary text-center'><i class='fa fa-check-circle'></i> HABILITAR MENU</button>"),
				'btnCategoriasMenu' => ("<button  onclick='AbrirModalListadoCategoriasMenu(" . $key->id_menu . ")' type='button' class='btn-block btn-warning text-center'>CATEGORIAS</button>"),

			)
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

	public function grabarNuevoMenu($id, $data)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		if ($id) {
			$this->db->where('id_menu', $id);
			$this->db->update('menu', $data);
			$consulta = $this->db->query("select " . $id . " as id_menu");
		} else {
			$this->db->insert('menu', $data);
			$consulta = $this->db->query("SELECT CURRVAL('menu_id_menu_seq'::regclass) as id_menu");
		}
		return $consulta->result();
	}

	public function obtenerCategoriaMenu($id_menu)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		
		$q = "SELECT cm.id_categoria_menu, cm.id_menu, m.nombre_menu, cm.nombre_categoria_menu, cm.vigente
			from categoria_menu cm
			join menu m on cm.id_menu=m.id_menu";
		
		// MODIFICAR AQUÍ: Solo filtrar si id_menu > 0
		if ($id_menu > 0) {
			$q = $q . " WHERE cm.id_menu=$id_menu";
		}
		
		$q = $q . " ORDER BY m.nombre_menu, cm.nombre_categoria_menu";  // Ordenar por menú y categoría

		$consulta = $this->db->query($q);
		$sampleData = $consulta->result();
		$data = array();

		foreach ($sampleData as $key) {
			array_push($data, array(
				"id_categoria_menu" => $key->id_categoria_menu,
				"id_menu" => $key->id_menu,
				"nombre_menu" => $key->nombre_menu,  // Agregar nombre del menú
				"nombre_categoria_menu" => $key->nombre_categoria_menu,
				'vigente' => ($key->vigente == 't' ? 'SI' : 'NO'),
				'btnAgregarProductosCategoria' => ("<button onclick='AbrirModalProductosAgregadosACategoria(" . $key->id_categoria_menu . ")' type='button' class='btn-block btn-warning text-center'>AGREGAR PRODUCTOS</button>"),
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

	public function grabarNuevaCategoria($id, $data)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		if ($id) {
			$this->db->where('id_categoria_menu', $id);
			$this->db->update('categoria_menu', $data);
			$consulta = $this->db->query("select " . $id . " as id_categoria_menu");
		} else {
			$this->db->insert('categoria_menu', $data);
			$consulta = $this->db->query("SELECT CURRVAL('categoria_menu_id_categoria_menu_seq'::regclass) as id_categoria_menu");
		}
		return $consulta->result();
	}

	public function obtenerProductosQueConformanElMenu($id_categoria_menu)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$q = 'SELECT dcm.id_detalle_categoria_menu, dcm.id_categoria_menu, cm.nombre_categoria_menu, p.nombre_producto, dcm.costo_receta, dcm.costo_adicional_venta, dcm.permite_cambio, dcm.imprimir, dcm.vigente from detalle_categoria_menu dcm
        join categoria_menu cm on dcm.id_categoria_menu=cm.id_categoria_menu
        join producto p on dcm.id_producto=p.id_producto
        where cm.id_categoria_menu=' . $id_categoria_menu . ' order by p.nombre_producto';
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function actualizarCostoRecetaCategoriaMenu($idDetalleCategoriaMenu, $data)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		 $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$this->db->where('id_detalle_categoria_menu', $idDetalleCategoriaMenu);
		$this->db->update('detalle_categoria_menu', $data);
		$consulta = $this->db->query("SELECT $idDetalleCategoriaMenu as id_detalle_categoria_menu from detalle_categoria_menu where id_detalle_categoria_menu=$idDetalleCategoriaMenu");
		return $consulta->result();
	}

	public function actualizarCostoAdicionalVentaCategoriaMenu($idDetalleCategoriaMenu, $data)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		 $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$this->db->where('id_detalle_categoria_menu', $idDetalleCategoriaMenu);
		$this->db->update('detalle_categoria_menu', $data);
		$consulta = $this->db->query("SELECT $idDetalleCategoriaMenu as id_detalle_categoria_menu from detalle_categoria_menu where id_detalle_categoria_menu=$idDetalleCategoriaMenu");
		return $consulta->result();
	}

	public function grabarDetalleCategoria($id, $data)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		if ($id) {
			$this->db->where('id_detalle_categoria_menu', $id);
			$this->db->update('detalle_categoria_menu', $data);
			$consulta = $this->db->query("select " . $id . " as id_detalle_categoria_menu");
		} else {
			$this->db->insert('detalle_categoria_menu', $data);
			$consulta = $this->db->query("SELECT CURRVAL('detalle_categoria_menu_id_detalle_categoria_menu_seq'::regclass) as id_detalle_categoria_menu");
		}
		return $consulta->result();
	}
	public function listadoDeCategorias()
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$q = "SELECT id_categoria_menu, nombre_categoria_menu from categoria_menu order by nombre_categoria_menu asc";
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function ObtenerConfiguracionesCategoriaMenu($id_categoria_menu)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$q = 'SELECT cm.id_categoria_menu, cm.cantidad_minima, cm.cantidad_maxima, cm.costo_promedio_receta, cm.estricto from categoria_menu cm 
        where cm.id_categoria_menu=' . $id_categoria_menu . '';
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function actualizarConfiguracionCategoriaMenu($idCategoriaMenu, $data)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		 $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		$this->db->where('id_categoria_menu', $idCategoriaMenu);
		$this->db->update('categoria_menu', $data);
		$consulta = $this->db->query("SELECT $idCategoriaMenu as categoria_menu_id_categoria_menu_seq from categoria_menu where id_categoria_menu=$idCategoriaMenu");
		return $consulta->result();
	}

	public function ObtenerUltimoIdProducto()
    {
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		 $this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
        $query = $this->db->query("SELECT last_value+1 as new_id from producto_id_producto_seq");
        return $query->result();
    }

	public function grabarPrecioProducto($data, $idDetalle = null)
	{
		if (strlen($this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		
		$this->db = $this->load->database($this->session->userdata('nombre_conexion'), TRUE);
		
		// VERIFICAR si existe la columna monto_comision
		$tieneComision = $this->verificarColumnaExiste('detalle_lprecio', 'monto_comision');
		
		log_message('debug', '¿Tabla tiene columna monto_comision? ' . ($tieneComision ? 'SÍ' : 'NO'));
		
		// Verificar si ya existe el precio para este producto y lista
		$this->db->where('id_producto', $data['id_producto']);
		$this->db->where('id_lprecio', $data['id_lprecio']);
		$existe = $this->db->get('detalle_lprecio');
		
		// Preparar datos base (siempre presentes)
		$dataToSave = array(
			'id_producto' => $data['id_producto'],
			'id_lprecio' => $data['id_lprecio'],
			'total' => $data['total']
		);
		
		// SOLO agregar comisión si la columna existe
		if ($tieneComision && isset($data['monto_comision'])) {
			$dataToSave['monto_comision'] = $data['monto_comision'];
			log_message('debug', 'Agregando comisión: ' . $data['monto_comision']);
		} else if (!$tieneComision && isset($data['monto_comision'])) {
			log_message('warning', 'Columna monto_comision no existe - omitiendo valor');
		}
		
		if ($existe->num_rows() > 0) {
			// Actualizar
			$this->db->where('id_producto', $data['id_producto']);
			$this->db->where('id_lprecio', $data['id_lprecio']);
			$this->db->update('detalle_lprecio', $dataToSave);
			
			return [
				'updated' => true, 
				'id_producto' => $data['id_producto'], 
				'id_lprecio' => $data['id_lprecio'],
				'tiene_comision' => $tieneComision
			];
		} else {
			// Insertar
			$this->db->insert('detalle_lprecio', $dataToSave);
			
			$consulta = $this->db->query("SELECT CURRVAL('detalle_lprecio_id_detalle_lprecio_seq'::regclass) as id_detalle_lprecio");
			
			if ($consulta->num_rows() > 0) {
				return [
					'inserted' => true, 
					'id_detalle_lprecio' => $consulta->row()->id_detalle_lprecio,
					'tiene_comision' => $tieneComision
				];
			}
		}
		
		return ['error' => 'No se pudo grabar el precio'];
	}
}