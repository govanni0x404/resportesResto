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
        " . ($tieneFavorito ? "(p.favorito IS TRUE)" : "false") . " AS favorito
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
				'id_menu' => (int)$key->id_menu, 'id_receta' => (int)$key->id_receta
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
		$q = 'SELECT a.id_agregado, a.id_producto, round(a.cantidad) as cantidad, a.valor, a.vigente, p.nombre_producto, pm.nombre_producto as producto_maestro 
        from agregados a
        join producto p on p.id_producto=a.id_producto
        join producto pm on pm.id_producto=a.id_producto_maestro
      	where a.id_producto_maestro=' . (int)$idProducto . ' and a.eliminado = false order by p.nombre_producto';
		$query = $this->db->query($q)->result();
		return $query;
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