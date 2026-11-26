<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mproductos extends CI_Model
{
	function __construct()
	{
		parent::__construct();
	}

	/**
	 * v1.24.1: ¿se usa la comisión en el precio de los productos? (Configuraciones > Comisión en el precio de los
	 * productos). Sin la columna de comisión, no; sin la opción (migración 20261120160000 pendiente), sí, como antes.
	 */
	public function comisionHabilitada()
	{
		$this->load->helper('tenant'); $db = get_tenant_db($this);
		$c = function ($t, $col) use ($db) {
			return (bool)$db->query("SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = ? AND column_name = ?", array($t, $col))->row();
		};
		if (!$c('detalle_lprecio', 'monto_comision')) return false;
		if (!$c('configuracion', 'usar_comision_web')) return true;
		$r = $db->query('SELECT (usar_comision_web IS TRUE) AS ok FROM configuracion LIMIT 1')->row();
		return $r && ($r->ok === true || $r->ok === 't');
	}

	/** v1.24.1: la comisión a grabar: 0 si la opción está apagada; si no, el valor escrito entre 0 y 100. */
	public function comisionAGrabar($valor)
	{
		if (!$this->comisionHabilitada()) return 0;
		$v = str_replace(',', '.', trim((string)(is_scalar($valor) ? $valor : '')));
		return is_numeric($v) ? max(0, min(100, round((float)$v, 2))) : 0;
	}

	public function verificarColumnaExiste($tabla, $columna)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		
		$query = $this->db->query("
			SELECT EXISTS (
				SELECT 1 
				FROM information_schema.columns 
				WHERE table_name = ? 
				AND column_name = ?
			) as existe
		", array($tabla, $columna));
		
		$result = $query->row();
		return $result->existe === 't' || $result->existe === true;
	}

	public function ListadoCartaDeProductos(/*$id_familia*/)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		 $this->load->helper('tenant'); $this->db = get_tenant_db($this);
		// Favoritos de la Caja (migración 20261110190000): sin la columna el listado sigue igual, sin estrellas
		$tieneFavorito = $this->tieneColumnaFavorito();
		$tieneDestino2 = $this->db->field_exists('id_dimpresion2_web', 'producto');   // v1.30.0 (migración 20261121150000)

		// 19-11-2026 (auditoría): también los id, para que al editar cada lista se elija por número y no por nombre
		$q = "SELECT p.id_producto, p.cbarra, p.nombre_producto, f.nombre_familia,
        p.id_familia, COALESCE(p.id_subfamilia, 0) AS id_subfamilia, p.id_umedida, p.id_dimpresion, p.id_bodega, p.impto_iva, p.impto_ila,
        COALESCE(p.id_menu, 0) AS id_menu, COALESCE(p.id_receta, 0) AS id_receta, u.nombre_umedida, i.nombre_impuesto as iva, ii.nombre_impuesto as impto_adicional,
        d.nombre_dimpresion, b.nombre_bodega, p.pponderado,
        (select nombre_menu from menu m where m.id_menu=p.id_menu) as menu,
        (select nombre_subfamilia from subfamilia sf where sf.id_subfamilia=p.id_subfamilia) as subfamilia,
        (select nombre_receta from receta r where r.id_receta=p.id_receta) as receta, 
        p.vigente, p.con_agregado, p.venta_publico, p.permite_porcion, p.venta_segun_stock, p.elaboracion_propia, p.permitir_venta_sin_precio, p.venta_uno_a_uno, p.pedir_cantidad_en_venta,
        p.ver_en_dispositivos_moviles,p.permite_cortesia,
        " . ($tieneFavorito ? "(p.favorito IS TRUE)" : "false") . " AS favorito,
        " . ($tieneDestino2 ? "COALESCE(p.id_dimpresion2_web, 0)" : "0") . " AS id_dimpresion2_web
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
				'permite_cortesia' => ($key->permite_cortesia == 't' ? 'SI' : 'NO'),
				'favorito' => (($key->favorito === 't' || $key->favorito === true) ? 'SI' : 'NO'),
				'id_familia' => (int)$key->id_familia, 'id_subfamilia' => (int)$key->id_subfamilia, 'id_umedida' => (int)$key->id_umedida,
				'id_dimpresion' => (int)$key->id_dimpresion, 'id_bodega' => (int)$key->id_bodega, 'impto_iva' => (int)$key->impto_iva,
				'id_menu' => (int)$key->id_menu, 'id_receta' => (int)$key->id_receta,
				'id_dimpresion2_web' => (int)$key->id_dimpresion2_web   // v1.30.0: segundo destino de impresión (0 = no)
			)
			);
		}
		$results = array(
			"sEcho" => 1,
			"iTotalRecords" => count($data),
			"iTotalDisplayRecords" => count($data),
			"aaData" => $data,
			// La Carta muestra la columna de estrellas solo si la base ya tiene producto.favorito
			"favoritos" => $tieneFavorito
		);

		return $results;
	}

	/** ¿La base de la empresa ya tiene producto.favorito? (migración 20261110190000). Lectura del catálogo, sin locks. */
	public function tieneColumnaFavorito()
	{
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$r = $this->db->query("SELECT 1 FROM information_schema.columns
			WHERE table_schema = 'public' AND table_name = 'producto' AND column_name = 'favorito'");
		return $r && $r->num_rows() > 0;
	}

	/** Marca o desmarca un producto como favorito de la Caja (estrella del listado de la Carta). */
	public function marcarFavorito($idProducto, $favorito)
	{
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		if (!$this->tieneColumnaFavorito()) {
			return array('ok' => false, 'error' => 'Falta aplicar la migración 20261110190000_add_producto_favorito en esta base.');
		}
		$r = $this->db->query('UPDATE producto SET favorito = ' . ($favorito ? 'TRUE' : 'FALSE') . ' WHERE id_producto = ?', array((int)$idProducto));
		if (!$r || $this->db->affected_rows() < 1) {
			return array('ok' => false, 'error' => 'No se encontró el producto.');
		}
		return array('ok' => true, 'favorito' => (bool)$favorito);
	}

	public function obtenerPreciosDeVenta($idProducto){
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		
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
			WHERE d.id_producto = ?
			ORDER BY l.nombre_lprecio
		", array((int)$idProducto));
		
		return $query->result();
	}

	public function ListadoDeSubFamilias()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		 $this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$q = "SELECT id_subfamilia, nombre_subfamilia, COALESCE(id_familia, 0) AS id_familia from subfamilia order by nombre_subfamilia asc";
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function ListadoDeUmedida()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		 $this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$q = "SELECT id_umedida, nombre_umedida from umedida order by nombre_umedida asc";
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function ListadoDestinoDeImpresion()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		 $this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$q = "SELECT id_dimpresion, nombre_dimpresion from dimpresion order by nombre_dimpresion asc";
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function ListadoDeBodega()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		 $this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$q = "SELECT id_bodega, nombre_bodega from bodega order by nombre_bodega asc";
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function ListadoDeImpuestos()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$q = "SELECT id_impuesto, nombre_impuesto from impuesto 
        where nombre_impuesto ilike '%IVA%' OR nombre_impuesto ilike '%EXENTO%'
        order by nombre_impuesto asc";
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function ListadoDeImpuestosAdicionales()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		// v1.20.0: solo lo que va en el campo ILA del producto: sin impuesto adicional (valor 0) y los ILA. El IVA va en su
		// campo, y el IVA anticipado de harina y carne (IHAR / ICAR) se paga al comprar, no al vender.
		$q = "SELECT id_impuesto, nombre_impuesto, valor, COALESCE(observacion, '') AS observacion, COALESCE(codigo_sii, 0) AS codigo_sii
			FROM impuesto
			WHERE COALESCE(valor, 0) = 0 OR nombre_impuesto ILIKE 'ILA%'
			ORDER BY COALESCE(valor, 0), nombre_impuesto";
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function ListadoDeMenu()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$q = "SELECT id_menu, nombre_menu from menu order by nombre_menu asc";
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function ListadoDeReceta()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$q = "SELECT id_receta, nombre_receta from receta order by nombre_receta asc";
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function grabarNuevoProducto($id, $data)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		 $this->load->helper('tenant'); $this->db = get_tenant_db($this);
		if ($id) {
			$this->db->where('id_producto', $id);
			$this->db->update('producto', $data);
			$consulta = $this->db->query("select " . (int)$id . "  as id_producto" );
		}
		else {
			$this->db->insert('producto', $data);
			$consulta = $this->db->query("SELECT CURRVAL('producto_id_producto_seq'::regclass) as id_producto");
		}
		return $consulta->result();
	}

	public function grabarNuevaFamilia($id, $data)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		if ($id) {
			$this->db->where('id_familia', $id);
			$this->db->update('familia', $data);
			// 16-11-2026 (QA): si no se actualizó ninguna fila el registro no existe: error (404) y no "éxito"
			if ($this->db->affected_rows() < 1) {
				set_status_header(404);
				return array('error' => 'El registro ya no existe (pudo haberlo borrado otra persona). Recarga la página.');
			}
			$consulta = $this->db->query("select " . (int)$id . " as id_familia");
		} else {
			$this->db->insert('familia', $data);
			$consulta = $this->db->query("SELECT CURRVAL('familia_id_familia_seq'::regclass) as id_familia");
		}
		return $consulta->result();
	}

	public function existeCodigoBarra($cbarra, $id_producto = null)
	{
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		
		$this->db->where('cbarra', $cbarra);
		
		// Si es una edición, excluir el producto actual
		if ($id_producto !== null && $id_producto > 0) {
			$this->db->where('id_producto !=', $id_producto);
		}
		
		return $this->db->count_all_results('producto') > 0;
	}

	public function obtenerSiguienteCodigoBarra()
	{
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);

		// 18-11-2026 (pedido del usuario): correlativo propio, "el último automático + 1", sin contar los códigos propios.
		// Ej.: si va en el 5 y se crea uno con 12872847, el siguiente vuelve a ser 6. Antes era el MAYOR de todos los
		// códigos + 1: un código largo (2000000000895) arrastraba la sugerencia a 2000000000896.
		// Automático = código numérico que no pasa del id_producto más alto (los automáticos nacen con el id del producto o
		// menos; los propios, EAN y similares, son mucho más largos). Se toma el primero libre después del último.
		$query = $this->db->query(
			"WITH base AS (
				SELECT COALESCE(MAX(cbarra::bigint), 0) AS b FROM producto
				WHERE cbarra ~ '^[0-9]{1,18}$' AND cbarra::bigint <= (SELECT COALESCE(MAX(id_producto), 0) FROM producto)
			)
			SELECT COALESCE((SELECT min(n) FROM base, generate_series(base.b + 1, base.b + 5000) AS n
				WHERE NOT EXISTS (SELECT 1 FROM producto p WHERE trim(p.cbarra) = n::text)), (SELECT b FROM base) + 1) AS siguiente"
		);
		$row = $query->row();

		return ($row && $row->siguiente) ? (string)$row->siguiente : '1';
	}

	public function grabarNuevaSubFamilia($id, $data)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		if ($id) {
			$this->db->where('id_subfamilia', $id);
			$this->db->update('subfamilia', $data);
			// 16-11-2026 (QA): si no se actualizó ninguna fila el registro no existe: error (404) y no "éxito"
			if ($this->db->affected_rows() < 1) {
				set_status_header(404);
				return array('error' => 'El registro ya no existe (pudo haberlo borrado otra persona). Recarga la página.');
			}
			$consulta = $this->db->query("select " . (int)$id . " as id_subfamilia");
		} else {
			$this->db->insert('subfamilia', $data);
			$consulta = $this->db->query("SELECT CURRVAL('subfamilia_id_subfamilia_seq'::regclass) as id_subfamilia");
		}
		return $consulta->result();
	}

	public function grabarNuevaBodega($id, $data)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		 $this->load->helper('tenant'); $this->db = get_tenant_db($this);
		if ($id) {
			$this->db->where('id_bodega', $id);
			$this->db->update('bodega', $data);
			// 16-11-2026 (QA): si no se actualizó ninguna fila el registro no existe: error (404) y no "éxito"
			if ($this->db->affected_rows() < 1) {
				set_status_header(404);
				return array('error' => 'El registro ya no existe (pudo haberlo borrado otra persona). Recarga la página.');
			}
			$consulta = $this->db->query("select " . (int)$id . " as id_bodega");
		} else {
			$this->db->insert('bodega', $data);
			$consulta = $this->db->query("SELECT CURRVAL('bodega_id_bodega_seq'::regclass) as id_bodega");
		}
		return $consulta->result();
	}

	public function ListadoDeProductos(/*$id_producto*/)
	{
if (strlen((string)$this->session->userdata("nombre_usuario")) == 0) { return array(); }
		 $this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$q = "SELECT id_producto, nombre_producto, pponderado, precio_ucompra from producto order by nombre_producto asc";
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function obtenerAgregadosDelProducto($idProducto)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		 $this->load->helper('tenant'); $this->db = get_tenant_db($this);
		// v1.30.2: también "Imprimir en su propio destino" y la impresora del producto agregado (su destino o el de su familia),
		// para marcarlo en un agregado que ya existe y ver a dónde sale
		// v1.35.1: la categoría es la del producto agregado (migración 20261124150000)
		$v135 = $this->hayColumna($this->db, 'producto', 'id_categoria_agregado_web');
		$q = "SELECT a.id_agregado, a.id_producto, round(a.cantidad) as cantidad, a.valor, a.vigente, p.nombre_producto, pm.nombre_producto as producto_maestro,
			(a.usar_destino_impresion IS TRUE) AS destino_propio,
			" . ($v135 ? 'COALESCE(p.id_categoria_agregado_web, 0) AS id_categoria,' : '0 AS id_categoria,') . "
			COALESCE((SELECT d.nombre_dimpresion FROM dimpresion d LEFT JOIN familia f ON f.id_familia = p.id_familia
				WHERE d.id_dimpresion = COALESCE(NULLIF(p.id_dimpresion, 0), NULLIF(f.id_dimpresion, 0))), '') AS destino_agregado
        from agregados a
        join producto p on p.id_producto=a.id_producto
        join producto pm on pm.id_producto=a.id_producto_maestro
      	where a.id_producto_maestro = ? and a.eliminado = false order by p.nombre_producto";
		$query = $this->db->query($q, array((int)$idProducto))->result();
		foreach ($query as $f) {
			$f->destino_propio = ($f->destino_propio === 't' || $f->destino_propio === true);
			$f->id_categoria = (int)$f->id_categoria;
		}
		return $query;
	}

	/** v1.35.0: ¿existe la columna? (las opciones nuevas no rompen una base sin la migración) */
	private function hayColumna($db, $tabla, $columna)
	{
		$r = $db->query("SELECT count(*) AS n FROM information_schema.columns
			WHERE table_schema = 'public' AND table_name = ? AND column_name = ?", array($tabla, $columna))->row();
		return $r && (int)$r->n > 0;
	}

	/**
	 * v1.35.1 (pedido del usuario): lo que necesita la ventana Agregados del producto: si "Agregados extra con cobro" y
	 * "Categorías de agregados" están activados (Configuraciones), cuántos extras y el máximo por categoría, la casilla
	 * "Extras con cobro" del producto, las categorías y la categoría de cada producto (para agrupar la lista).
	 */
	public function opcionesAgregados($idProducto)
	{
		$this->load->helper('tenant'); $db = get_tenant_db($this);
		$vacio = array('ok' => true, 'disponible' => false, 'extras' => false, 'categorias' => false, 'max_extras' => 0, 'max_categoria' => 0,
			'extras_producto' => false, 'lista_categorias' => array(), 'categoria_productos' => new stdClass());
		if (!$this->hayColumna($db, 'producto', 'extras_web') || !$this->hayColumna($db, 'configuracion', 'agregados_max_extras_web')) return $vacio;
		$c = $db->query('SELECT (agregados_extra_web IS TRUE) AS e, (agregados_categorias_web IS TRUE) AS k,
			COALESCE(agregados_max_extras_web, 1) AS mx, COALESCE(agregados_max_categoria_web, 0) AS mc FROM configuracion LIMIT 1')->row();
		$p = $db->query('SELECT (extras_web IS TRUE) AS x FROM producto WHERE id_producto = ?', array((int)$idProducto))->row();
		$on = function ($v) { return $v === 't' || $v === true; };
		$cats = array();
		foreach ($db->query('SELECT id_categoria_agregado, nombre FROM categoria_agregado_web WHERE eliminado IS NOT TRUE AND vigente IS TRUE ORDER BY orden, nombre')->result() as $k) {
			$cats[] = array('id' => (int)$k->id_categoria_agregado, 'nombre' => (string)$k->nombre);
		}
		$deProd = array();
		foreach ($db->query('SELECT id_producto, id_categoria_agregado_web AS c FROM producto WHERE id_categoria_agregado_web > 0')->result() as $r) {
			$deProd[(string)(int)$r->id_producto] = (int)$r->c;
		}
		return array('ok' => true, 'disponible' => true, 'extras' => $c ? $on($c->e) : false, 'categorias' => $c ? $on($c->k) : false,
			'max_extras' => $c ? max(0, (int)$c->mx) : 1, 'max_categoria' => $c ? max(0, (int)$c->mc) : 0,
			'extras_producto' => $p ? $on($p->x) : false, 'lista_categorias' => $cats, 'categoria_productos' => (object)$deProd);
	}

	/** v1.35.1: casilla "Extras con cobro" del producto. */
	public function grabarExtrasProducto($idProducto, $activo)
	{
		$this->load->helper('tenant'); $db = get_tenant_db($this);
		if (!$this->hayColumna($db, 'producto', 'extras_web')) return array('error' => 'Falta actualizar la base: cierra sesión y vuelve a entrar.');
		$db->query('UPDATE producto SET extras_web = ? WHERE id_producto = ?', array($activo ? 't' : 'f', (int)$idProducto));
		if ($db->affected_rows() < 1) return array('error' => 'El producto ya no existe. Recarga la página.');
		return array('ok' => true, 'extras' => (bool)$activo);
	}

	/** v1.35.0: cambia la cantidad o el valor de un agregado ya ingresado, desde su fila en la tabla (antes no se guardaban). */
	public function campoAgregado($idAgregado, $idMaestro, $campo, $valor)
	{
		$this->load->helper('tenant'); $db = get_tenant_db($this);
		if (!in_array($campo, array('cantidad', 'valor'), true)) return array('error' => 'Dato desconocido');
		$v = (int)$valor;
		$v = $campo === 'cantidad' ? max(1, min(1000, $v)) : max(0, min(500000, $v));
		$db->query('UPDATE agregados SET ' . $campo . ' = ? WHERE id_agregado = ? AND id_producto_maestro = ? AND eliminado IS NOT TRUE',
			array($v, (int)$idAgregado, (int)$idMaestro));
		if ($db->affected_rows() < 1) return array('error' => 'El agregado ya no existe. Recarga la página.');
		return array('ok' => true, 'valor' => $v);
	}

	/** v1.30.3: ¿ya corrió la migración 20261123100000 (producto.max_agregados_web)? */
	private function hayMaxAgregados($db)
	{
		$r = $db->query("SELECT count(*) AS n FROM information_schema.columns
			WHERE table_schema = 'public' AND table_name = 'producto' AND column_name = 'max_agregados_web'")->row();
		return $r && (int)$r->n > 0;
	}

	/**
	 * v1.30.3: máximo de agregados a elegir en la Caja y cuántos agregados vigentes tiene.
	 * v1.43.0: "0 es 0" (-1 = sin límite, 0 = ninguno, N = hasta N) y la casilla "Permite varios agregados"
	 * (producto.varios_agregados_web; NULL = sí). Sin la migración 20261126140000, como antes (0 = sin límite).
	 */
	public function maxAgregados($idProducto)
	{
		$this->load->helper('tenant'); $db = get_tenant_db($this);
		$n = $db->query('SELECT count(*) AS n FROM agregados WHERE id_producto_maestro = ? AND eliminado IS NOT TRUE AND vigente IS NOT FALSE', array((int)$idProducto))->row();
		$max = -1;
		$varios = true;
		$hay = $this->hayMaxAgregados($db);
		$hayVarios = $this->hayColumna($db, 'producto', 'varios_agregados_web');
		if ($hay) {
			$r = $db->query('SELECT max_agregados_web AS m' . ($hayVarios ? ', (varios_agregados_web IS NOT FALSE) AS v' : '') . ' FROM producto WHERE id_producto = ?', array((int)$idProducto))->row();
			if ($r) {
				if ($r->m === null) $max = -1;
				else $max = (int)$r->m === 0 && !$hayVarios ? -1 : max(0, (int)$r->m);   // antes de la migración el 0 era "sin límite"
				if ($hayVarios) $varios = $r->v === 't' || $r->v === true;
			}
		}
		return array('ok' => true, 'maximo' => $max, 'varios' => $varios, 'agregados' => $n ? (int)$n->n : 0, 'disponible' => $hay, 'disponible_varios' => $hayVarios);
	}

	/** $max: -1 = sin límite (NULL), 0 = ninguno, N = hasta N (v1.43.0: 0 es 0) */
	public function grabarMaxAgregados($idProducto, $max)
	{
		$this->load->helper('tenant'); $db = get_tenant_db($this);
		if (!$this->hayMaxAgregados($db)) return array('error' => 'Falta actualizar la base: cierra sesión y vuelve a entrar.');
		$max = (int)$max < 0 ? null : min(100, (int)$max);
		if ($max === 0 && !$this->hayColumna($db, 'producto', 'varios_agregados_web')) $max = null;   // sin la migración: 0 era "sin límite"
		$db->query('UPDATE producto SET max_agregados_web = ? WHERE id_producto = ?', array($max, (int)$idProducto));
		if ($db->affected_rows() < 1) return array('error' => 'El producto ya no existe. Recarga la página.');
		return array('ok' => true, 'maximo' => $max === null ? -1 : $max);
	}

	/** v1.43.0: casilla "Permite varios agregados" del producto (como producto.es_menu). */
	public function grabarVariosAgregados($idProducto, $activo)
	{
		$this->load->helper('tenant'); $db = get_tenant_db($this);
		if (!$this->hayColumna($db, 'producto', 'varios_agregados_web')) return array('error' => 'Falta actualizar la base: cierra sesión y vuelve a entrar.');
		$db->query('UPDATE producto SET varios_agregados_web = ? WHERE id_producto = ?', array($activo ? 't' : 'f', (int)$idProducto));
		if ($db->affected_rows() < 1) return array('error' => 'El producto ya no existe. Recarga la página.');
		return array('ok' => true, 'varios' => (bool)$activo);
	}

	/** v1.30.2: marca o desmarca "Imprimir en su propio destino" en un agregado que ya existe (de ese producto). */
	public function destinoPropioAgregado($idAgregado, $idMaestro, $activo)
	{
		$this->load->helper('tenant'); $db = get_tenant_db($this);
		$db->query('UPDATE agregados SET usar_destino_impresion = ? WHERE id_agregado = ? AND id_producto_maestro = ? AND eliminado IS NOT TRUE',
			array($activo ? 't' : 'f', (int)$idAgregado, (int)$idMaestro));
		return $db->affected_rows() > 0;
	}

	/**
	 * v1.24.17: copia los agregados de un producto a otros (pedido del usuario). Todo o nada (transacción) y NUNCA borra:
	 * - los agregados que el destino no tiene se crean con la misma cantidad, valor, vigencia y destino de impresión;
	 * - los que ya tiene: se dejan como están, o con $actualizar se les pone la cantidad y el valor del producto de origen;
	 * - un producto no queda como agregado de sí mismo;
	 * - el destino queda marcado "Con Agregado" (si no, el POS de escritorio no los ofrece).
	 */
	public function copiarAgregados($idOrigen, array $destinos, $actualizar)
	{
		$this->load->helper('tenant'); $db = get_tenant_db($this);
		$idOrigen = (int)$idOrigen;
		$destinos = array_values(array_unique(array_filter(array_map('intval', $destinos), function ($d) use ($idOrigen) { return $d > 0 && $d !== $idOrigen; })));
		if (!$destinos) return array('error' => 'Elige al menos un producto donde copiar los agregados');
		$n = $db->query('SELECT count(*) AS n FROM agregados WHERE id_producto_maestro = ? AND eliminado IS NOT TRUE', array($idOrigen))->row();
		if (!$n || (int)$n->n === 0) return array('error' => 'Este producto no tiene agregados para copiar');
		$en = 'ARRAY[' . implode(',', $destinos) . ']::int[]';   // solo enteros (intval arriba)

		$db->trans_begin();
		$actualizados = 0;
		if ($actualizar) {
			$ok = $db->query("UPDATE agregados d SET cantidad = o.cantidad, valor = o.valor
				FROM agregados o
				WHERE o.id_producto_maestro = ? AND o.eliminado IS NOT TRUE
				  AND d.id_producto = o.id_producto AND d.id_producto_maestro = ANY($en) AND d.eliminado IS NOT TRUE", array($idOrigen));
			if ($ok === false) { $db->trans_rollback(); return array('error' => 'No se pudieron actualizar los agregados que ya tenían'); }
			$actualizados = $db->affected_rows();
		}
		$ok = $db->query("INSERT INTO agregados (id_producto, cantidad, vigente, id_producto_maestro, valor, usar_destino_impresion, eliminado,
				id_dimpresion_maestro, id_dimpresion_agregado)
			SELECT o.id_producto, o.cantidad, o.vigente, p.id_producto, o.valor, COALESCE(o.usar_destino_impresion, false), false, 0, 0
			FROM agregados o JOIN producto p ON p.id_producto = ANY($en)
			WHERE o.id_producto_maestro = ? AND o.eliminado IS NOT TRUE AND o.id_producto <> p.id_producto
			  AND NOT EXISTS (SELECT 1 FROM agregados x WHERE x.id_producto_maestro = p.id_producto AND x.id_producto = o.id_producto
				AND x.eliminado IS NOT TRUE)
			ON CONFLICT DO NOTHING", array($idOrigen));
		if ($ok === false) { $db->trans_rollback(); return array('error' => 'No se pudieron copiar los agregados'); }
		$nuevos = $db->affected_rows();
		$ok = $db->query("UPDATE producto p SET con_agregado = true WHERE p.id_producto = ANY($en) AND p.con_agregado IS NOT TRUE
			AND EXISTS (SELECT 1 FROM agregados a WHERE a.id_producto_maestro = p.id_producto AND a.eliminado IS NOT TRUE)");
		if ($ok === false || $db->trans_status() === false) { $db->trans_rollback(); return array('error' => 'No se pudieron copiar los agregados'); }
		$db->trans_commit();
		return array('ok' => true, 'productos' => count($destinos), 'nuevos' => (int)$nuevos, 'actualizados' => (int)$actualizados);
	}

	/**
	 * v1.34.1 (pedido del usuario en cambios.md): al grabar un agregado, el producto queda "Con Agregado" en la base al
	 * tiro (antes había que volver a grabar el producto, y si no, el agregado no se ofrecía). Solo si tiene alguno vigente.
	 */
	public function marcarConAgregado($idMaestro)
	{
		$this->load->helper('tenant'); $db = get_tenant_db($this);
		$db->query('UPDATE producto p SET con_agregado = true WHERE p.id_producto = ? AND p.con_agregado IS NOT TRUE
			AND EXISTS (SELECT 1 FROM agregados a WHERE a.id_producto_maestro = p.id_producto AND a.eliminado IS NOT TRUE AND a.vigente IS NOT FALSE)',
			array((int)$idMaestro));
		return $db->affected_rows() > 0;
	}

	public function grabarAgregados($id, $data)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		 $this->load->helper('tenant'); $this->db = get_tenant_db($this);
		if ($id) {
			$this->db->where('id_agregado', $id);
			$this->db->update('agregados', $data);
			// 16-11-2026 (QA): si no se actualizó ninguna fila el registro no existe: error (404) y no "éxito"
			if ($this->db->affected_rows() < 1) {
				set_status_header(404);
				return array('error' => 'El registro ya no existe (pudo haberlo borrado otra persona). Recarga la página.');
			}
			$consulta = $this->db->query("select " . (int)$id . " as id_agregado");
		} else {
			$this->db->insert('agregados', $data);
			$consulta = $this->db->query("SELECT CURRVAL('agregados_id_agregado_seq'::regclass) as id_agregado");
		}
		return $consulta->result();
	}

	public function actualizarCantidadProductoAgregado($idAgregado, $data)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		 $this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$this->db->where('id_agregado', $idAgregado);
		$this->db->update('agregados', $data);
		$consulta = $this->db->query("SELECT " . (int)$idAgregado . " as id_agregado from agregados where id_agregado=" . (int)$idAgregado);
		return $consulta->result();
	}

	public function actualizarValorAdicionalProductoAgregado($idAgregado, $data)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$this->db->where('id_agregado', $idAgregado);
		$this->db->update('agregados', $data);
		$consulta = $this->db->query("SELECT " . (int)$idAgregado . " as id_agregado from agregados where id_agregado=" . (int)$idAgregado);
		return $consulta->result();
	}

	public function HabilitarAgregado($id, $data)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		 $this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$this->db->where('id_agregado', $id);
		$r = $this->db->update('agregados', $data);
		return $r;
	}

	public function DeshabilitarAgregado($id, $data)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$this->db->where('id_agregado', $id);
		$r = $this->db->update('agregados', $data);
		return $r;
	}

	public function obtenerMensajesDelProducto($idProducto)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$q = 'SELECT mp.id_mensaje_producto, p.nombre_producto, mp.mensaje, mp.vigente
        from mensaje_producto mp
        join producto p on p.id_producto=mp.id_producto
        where mp.id_producto=' . (int)$idProducto . ' order by mp.id_mensaje_producto';
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function grabarMsgProductos($id, $data)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		if ($id) {
			$this->db->where('id_mensaje_producto', $id);
			$this->db->update('mensaje_producto', $data);
			// 16-11-2026 (QA): si no se actualizó ninguna fila el registro no existe: error (404) y no "éxito"
			if ($this->db->affected_rows() < 1) {
				set_status_header(404);
				return array('error' => 'El registro ya no existe (pudo haberlo borrado otra persona). Recarga la página.');
			}
			$consulta = $this->db->query("select " . (int)$id . " as id_mensaje_producto");
		} else {
			$this->db->insert('mensaje_producto', $data);
			$consulta = $this->db->query("SELECT CURRVAL('mensaje_producto_id_mensaje_producto_seq'::regclass) as id_mensaje_producto");
		}
		return $consulta->result();
	}

	public function HabilitarMensajeProducto($id, $data)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$this->db->where('id_mensaje_producto', $id);
		$r = $this->db->update('mensaje_producto', $data);
		return $r;
	}

	public function DeshabilitarMensajeProducto($id, $data)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		 $this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$this->db->where('id_mensaje_producto', $id);
		$r = $this->db->update('mensaje_producto', $data);
		return $r;
	}

	public function obtenerListadoMenu()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		 $this->load->helper('tenant'); $this->db = get_tenant_db($this);
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
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		if ($id) {
			$this->db->where('id_menu', $id);
			$this->db->update('menu', $data);
			// 16-11-2026 (QA): si no se actualizó ninguna fila el registro no existe: error (404) y no "éxito"
			if ($this->db->affected_rows() < 1) {
				set_status_header(404);
				return array('error' => 'El registro ya no existe (pudo haberlo borrado otra persona). Recarga la página.');
			}
			$consulta = $this->db->query("select " . (int)$id . " as id_menu");
		} else {
			$this->db->insert('menu', $data);
			$consulta = $this->db->query("SELECT CURRVAL('menu_id_menu_seq'::regclass) as id_menu");
		}
		return $consulta->result();
	}

	public function obtenerCategoriaMenu($id_menu, $soloVigentes = false)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		
		$q = "SELECT cm.id_categoria_menu, cm.id_menu, m.nombre_menu, cm.nombre_categoria_menu, cm.vigente, cm.costo_promedio_receta
			from categoria_menu cm
			join menu m on cm.id_menu=m.id_menu";
		
		$where = array();
		
		if ($id_menu > 0) {
			$where[] = "cm.id_menu=" . (int)$id_menu;
		}
		
		if ($soloVigentes) {
			$where[] = "cm.vigente = 't'";
		}
		
		if (!empty($where)) {
			$q .= " WHERE " . implode(" AND ", $where);
		}
		
		$q .= " ORDER BY m.nombre_menu, cm.nombre_categoria_menu";

		$consulta = $this->db->query($q);
		$sampleData = $consulta->result();
		$data = array();

		foreach ($sampleData as $key) {
			array_push($data, array(
				"id_categoria_menu" => $key->id_categoria_menu,
				"id_menu" => $key->id_menu,
				"nombre_menu" => $key->nombre_menu,
				"nombre_categoria_menu" => $key->nombre_categoria_menu,
				"costo_promedio_receta" => $key->costo_promedio_receta,
				'vigente' => ($key->vigente == 't' ? 'SI' : 'NO'),
				// v1.24.12: botón con el diseño del sistema (ver #ModalListadoMenus en productos/modals.php)
				'btnAgregarProductosCategoria' => ("<button onclick='AbrirModalProductosAgregadosACategoria(" . (int)$key->id_categoria_menu . ")' type='button' class='lm-btn-productos'><i class='fa fa-cutlery'></i> Productos</button>"),
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
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		if ($id) {
			$this->db->where('id_categoria_menu', $id);
			$this->db->update('categoria_menu', $data);
			// 16-11-2026 (QA): si no se actualizó ninguna fila el registro no existe: error (404) y no "éxito"
			if ($this->db->affected_rows() < 1) {
				set_status_header(404);
				return array('error' => 'El registro ya no existe (pudo haberlo borrado otra persona). Recarga la página.');
			}
			$consulta = $this->db->query("select " . (int)$id . " as id_categoria_menu");
		} else {
			$this->db->insert('categoria_menu', $data);
			$consulta = $this->db->query("SELECT CURRVAL('categoria_menu_id_categoria_menu_seq'::regclass) as id_categoria_menu");
		}
		return $consulta->result();
	}

	public function obtenerProductosQueConformanElMenu($id_categoria_menu)
	{
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$q = "SELECT dcm.id_detalle_categoria_menu, dcm.id_categoria_menu, dcm.id_producto, cm.nombre_categoria_menu, p.nombre_producto, dcm.costo_receta, dcm.costo_adicional_venta, dcm.permite_cambio, dcm.imprimir, dcm.vigente from detalle_categoria_menu dcm
        join categoria_menu cm on dcm.id_categoria_menu=cm.id_categoria_menu
        join producto p on dcm.id_producto=p.id_producto
        where dcm.vigente = 't' and cm.id_categoria_menu=" . (int)$id_categoria_menu . " order by p.nombre_producto";
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function actualizarCostoRecetaCategoriaMenu($idDetalleCategoriaMenu, $data)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		 $this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$this->db->where('id_detalle_categoria_menu', $idDetalleCategoriaMenu);
		$this->db->update('detalle_categoria_menu', $data);
		$consulta = $this->db->query("SELECT " . (int)$idDetalleCategoriaMenu . " as id_detalle_categoria_menu from detalle_categoria_menu where id_detalle_categoria_menu=" . (int)$idDetalleCategoriaMenu);
		return $consulta->result();
	}

	public function actualizarCostoAdicionalVentaCategoriaMenu($idDetalleCategoriaMenu, $data)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		 $this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$this->db->where('id_detalle_categoria_menu', $idDetalleCategoriaMenu);
		$this->db->update('detalle_categoria_menu', $data);
		$consulta = $this->db->query("SELECT " . (int)$idDetalleCategoriaMenu . " as id_detalle_categoria_menu from detalle_categoria_menu where id_detalle_categoria_menu=" . (int)$idDetalleCategoriaMenu);
		return $consulta->result();
	}

	public function grabarDetalleCategoria($id, $data)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		if ($id) {
			$this->db->where('id_detalle_categoria_menu', $id);
			$this->db->update('detalle_categoria_menu', $data);
			// 16-11-2026 (QA): si no se actualizó ninguna fila el registro no existe: error (404) y no "éxito"
			if ($this->db->affected_rows() < 1) {
				set_status_header(404);
				return array('error' => 'El registro ya no existe (pudo haberlo borrado otra persona). Recarga la página.');
			}
			$consulta = $this->db->query("select " . (int)$id . " as id_detalle_categoria_menu");
		} else {
			$this->db->insert('detalle_categoria_menu', $data);
			$consulta = $this->db->query("SELECT CURRVAL('detalle_categoria_menu_id_detalle_categoria_menu_seq'::regclass) as id_detalle_categoria_menu");
		}
		return $consulta->result();
	}
	public function listadoDeCategorias()
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$q = "SELECT id_categoria_menu, nombre_categoria_menu from categoria_menu order by nombre_categoria_menu asc";
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function ObtenerConfiguracionesCategoriaMenu($id_categoria_menu)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$q = 'SELECT cm.id_categoria_menu, cm.cantidad_minima, cm.cantidad_maxima, cm.costo_promedio_receta, cm.estricto from categoria_menu cm 
        where cm.id_categoria_menu=' . (int)$id_categoria_menu;
		$query = $this->db->query($q)->result();
		return $query;
	}

	public function actualizarConfiguracionCategoriaMenu($idCategoriaMenu, $data)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		 $this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$this->db->where('id_categoria_menu', $idCategoriaMenu);
		$this->db->update('categoria_menu', $data);
		$consulta = $this->db->query("SELECT " . (int)$idCategoriaMenu . " as categoria_menu_id_categoria_menu_seq from categoria_menu where id_categoria_menu=" . (int)$idCategoriaMenu);
		return $consulta->result();
	}

	public function ObtenerUltimoIdProducto()
    {
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		 $this->load->helper('tenant'); $this->db = get_tenant_db($this);
        // 18-11-2026: el mismo correlativo que obtenerSiguienteCodigoBarra (antes era el mayor código + 1)
        return array((object)array('new_id' => $this->obtenerSiguienteCodigoBarra()));
    }

	/**
	 * v1.20.0: recalcula neto, IVA e ILA de todos los precios de un producto con sus impuestos actuales (al cambiarle el
	 * IVA o el ILA). El total (lo que paga el cliente) no cambia. Mismo cálculo que grabarPrecioProducto().
	 */
	public function recalcularImpuestosPrecios($idProducto)
	{
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		return $this->db->query("UPDATE detalle_lprecio d
			SET neto = x.neto, iva = round(x.neto * x.piva / 100), ila = d.total - x.neto - round(x.neto * x.piva / 100)
			FROM (SELECT d2.id_detalle_lprecio, COALESCE(iv.valor, 0) AS piva,
					round(d2.total / (1 + (COALESCE(iv.valor, 0) + COALESCE(il.valor, 0)) / 100.0)) AS neto
				FROM detalle_lprecio d2
				JOIN producto p ON p.id_producto = d2.id_producto
				LEFT JOIN impuesto iv ON iv.id_impuesto = p.impto_iva
				LEFT JOIN impuesto il ON il.id_impuesto = p.impto_ila
				WHERE d2.id_producto = ?) x
			WHERE d.id_detalle_lprecio = x.id_detalle_lprecio", array((int)$idProducto));
	}

	public function grabarPrecioProducto($data, $idDetalle = null)
	{
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url());
		}
		
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		
		// VERIFICAR si existe la columna monto_comision
		$tieneComision = $this->verificarColumnaExiste('detalle_lprecio', 'monto_comision');
		
		log_message('debug', '¿Tabla tiene columna monto_comision? ' . ($tieneComision ? 'SÍ' : 'NO'));
		
		// Verificar si ya existe el precio para este producto y lista
		$this->db->where('id_producto', $data['id_producto']);
		$this->db->where('id_lprecio', $data['id_lprecio']);
		$existe = $this->db->get('detalle_lprecio');
		
		// 16-11-2026: detalle_lprecio.neto, iva e ila son obligatorios (NOT NULL). Antes solo se grababa el total y un
		// precio NUEVO daba error 500 (el producto quedaba sin precio). Se calculan con los impuestos del producto
		// (impto_iva e impto_ila, tabla impuesto): neto = total / (1 + (iva + ila) / 100).
		$total = (int)round((float)str_replace(',', '.', (string)($data['total'] ?? 0)));
		if ($total < 0) return ['error' => 'El precio no puede ser negativo'];
		$tasas = $this->db->query("SELECT COALESCE((SELECT i.valor FROM impuesto i WHERE i.id_impuesto = p.impto_iva), 0) AS iva,
				COALESCE((SELECT i.valor FROM impuesto i WHERE i.id_impuesto = p.impto_ila), 0) AS ila
			FROM producto p WHERE p.id_producto = ?", array((int)$data['id_producto']))->row();
		if (!$tasas) return ['error' => 'El producto no existe'];
		$pIva = (float)$tasas->iva;
		$pIla = (float)$tasas->ila;
		$neto = (int)round($total / (1 + ($pIva + $pIla) / 100));
		$iva = (int)round($neto * $pIva / 100);
		$ila = $total - $neto - $iva;

		// Preparar datos base (siempre presentes)
		$dataToSave = array(
			'id_producto' => $data['id_producto'],
			'id_lprecio' => $data['id_lprecio'],
			'total' => $total,
			'neto' => $neto,
			'iva' => $iva,
			'ila' => $ila,
		);
		
		// v1.24.1: con la columna, la comisión va siempre: 0 si la opción está apagada (o no vino), si no la escrita
		if ($tieneComision) {
			$dataToSave['monto_comision'] = $this->comisionAGrabar(isset($data['monto_comision']) ? $data['monto_comision'] : 0);
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

	public function DeshabilitarDetalleCategoria($id)
	{
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
		$this->db->where('id_detalle_categoria_menu', (int)$id);
		$this->db->update('detalle_categoria_menu', array('vigente' => 'f'));
		return [
            'affected' => $this->db->affected_rows(),
            'query' => $this->db->last_query(),
            'id' => $id
        ];
	}
}