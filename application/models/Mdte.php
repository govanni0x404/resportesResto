<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Emisión electrónica con Cloud Apps (mismo formato que el POS y AppCaja).
 * Cloud Apps asigna el folio (Folio "0") desde el CAF del terminal y devuelve el TED.
 * Los montos van netos en el detalle (IndMntNeto = 1), como los documentos del POS.
 */
class Mdte extends CI_Model
{
	// Documentos sin IVA: el total va como exento
	const COD_EXENTOS = array(34, 41);
	const COD_NC = 61;

	function __construct()
	{
		parent::__construct();
		$this->config->load('caja', true);
	}

	/**
	 * Credenciales, terminal y emisor. null si la empresa no tiene emisión configurada.
	 */
	public function configuracion($db)
	{
		$prueba = $this->config->item('caja_dte_pruebas', 'caja');
		$empresa = $db->query('SELECT e.*, COALESCE(c.nombre_comuna, \'\') AS nombre_comuna
			FROM empresa e LEFT JOIN comuna c ON c.id_comuna = e.id_comuna ORDER BY e.id_empresa LIMIT 1')->row();
		if (!$empresa) return null;

		if (is_array($prueba) && !empty($prueba['usuario'])) {
			$usuario = $prueba['usuario'];
			$clave = $prueba['clave'];
			$terminal = $prueba['terminal'];
			$enviaSii = isset($prueba['envia_sii']) ? (int)$prueba['envia_sii'] : 0;
		} else {
			list($usuario, $clave) = $this->credenciales($db);
			// Terminal de Cloud Apps: Configuraciones > Facturación (o config/caja.php por conexión)
			$terminal = '';
			if ($this->tieneColumna($db, 'configuracion', 'terminal_dte')) {
				$r = $db->query("SELECT trim(COALESCE(terminal_dte, '')) AS t FROM configuracion LIMIT 1")->row();
				$terminal = $r ? $r->t : '';
			}
			if ($terminal === '') {
				$terminales = (array)$this->config->item('caja_dte_terminales', 'caja');
				$conexion = (string)$this->session->userdata('nombre_conexion');
				$terminal = isset($terminales[$conexion]) ? trim($terminales[$conexion]) : '';
			}
			$enviaSii = 1;
			if ($this->tieneColumna($db, 'configuracion', 'enviar_dte_sii')) {
				$r = $db->query('SELECT (enviar_dte_sii IS NOT FALSE) AS envia FROM configuracion LIMIT 1')->row();
				$enviaSii = $r && ($r->envia === 't' || $r->envia === true) ? 1 : 0;
			}
		}
		$rut = number_format((float)$empresa->rut_empresa, 0, '', '') . '-' . strtoupper(trim($empresa->dv_empresa));
		// El usuario de Cloud Apps es el RUT de la empresa: si no está cargado, se usa el de la empresa
		if ($usuario === '' && (int)$empresa->rut_empresa > 0) $usuario = $rut;
		if ($usuario === '' || $clave === '' || $terminal === '') return null;
		// Cada empresa emite con su propio RUT: un usuario de otra empresa firmaría documentos ajenos
		if (!(is_array($prueba) && !empty($prueba['usuario'])) && $this->normalizarRut($usuario) !== $this->normalizarRut($rut)) {
			log_message('error', 'Caja web DTE: usuario_dte (' . $usuario . ') no coincide con el RUT de la empresa (' . $rut . ')');
			return null;
		}
		return array(
			'usuario' => $usuario,
			'clave' => $clave,
			'terminal' => $terminal,
			'envia_sii' => $enviaSii,
			'prueba' => is_array($prueba) && !empty($prueba['usuario']),
			'emisor' => array(
				'RUTEmisor' => is_array($prueba) && !empty($prueba['rut_emisor']) ? $prueba['rut_emisor'] : $rut,
				'RznSoc' => $this->texto(is_array($prueba) && !empty($prueba['razon_social']) ? $prueba['razon_social'] : $empresa->razon_social, 100),
				'GiroEmis' => $this->texto($empresa->giro, 80),
				'DirOrigen' => $this->texto($empresa->direccion_empresa, 70),
				'CmnaOrigen' => $this->texto($empresa->nombre_comuna, 20),
				'CiudadOrigen' => $this->texto($empresa->nombre_comuna, 20),
			),
		);
	}

	/**
	 * Emite la venta ya guardada y persiste folio, track ID y TED.
	 * Devuelve {ok, folio, mensaje}. No lanza excepciones: si falla, la venta queda pendiente.
	 */
	public function emitir($db, $idVenta, $cfg = null)
	{
		try {
			return $this->emitirVenta($db, $idVenta, $cfg);
		} catch (Throwable $e) {
			log_message('error', 'Caja web DTE venta ' . (int)$idVenta . ': ' . $e->getMessage() . ' en ' . basename($e->getFile()) . ':' . $e->getLine());
			try { $this->registrarFalla($db, $idVenta, $e->getMessage()); } catch (Throwable $e2) {}
			return array('ok' => false, 'folio' => 0, 'mensaje' => 'Error interno al emitir: ' . $e->getMessage());
		}
	}

	private function emitirVenta($db, $idVenta, $cfg)
	{
		$cfg = $cfg ?: $this->configuracion($db);
		if (!$cfg) return array('ok' => false, 'folio' => 0, 'mensaje' => 'La emisión electrónica no está configurada');
		try {
			$payload = $this->documento($db, (int)$idVenta, $cfg);
		} catch (Exception $e) {
			$this->registrarFalla($db, $idVenta, $e->getMessage());
			return array('ok' => false, 'folio' => 0, 'mensaje' => $e->getMessage());
		}

		$ch = curl_init($this->config->item('caja_dte_url', 'caja'));
		curl_setopt_array($ch, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_HTTPHEADER => array(
				'Authorization: Basic ' . base64_encode($cfg['usuario'] . ':' . $cfg['clave']),
				'Content-Type: application/json',
				'Accept: application/json',
			),
			CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT => 20,
		));
		$raw = curl_exec($ch);
		$errorCurl = curl_error($ch);
		curl_close($ch);

		if ($raw === false) {
			$msg = 'Sin respuesta de Cloud Apps' . ($errorCurl ? ': ' . $errorCurl : '');
			$this->registrarFalla($db, $idVenta, $msg);
			return array('ok' => false, 'folio' => 0, 'mensaje' => $msg);
		}
		$json = json_decode($raw, true);
		$r = is_array($json) && isset($json[0]) && is_array($json[0]) ? $json[0] : (is_array($json) ? $json : array());
		$status = isset($r['status']) ? (int)$r['status'] : (isset($r['error']) ? (int)$r['error'] : -1);
		$folio = isset($r['folio']) ? (int)$r['folio'] : 0;
		$retorno = isset($r['retorno']) ? (string)$r['retorno'] : (isset($r['msg']) ? (string)$r['msg'] : '');

		if ($status !== 0 || $folio <= 0) {
			$msg = $retorno !== '' ? $retorno : 'Respuesta inesperada de Cloud Apps';
			log_message('error', 'Caja web DTE venta ' . (int)$idVenta . ': ' . substr($raw, 0, 500));
			$this->registrarFalla($db, $idVenta, $msg);
			return array('ok' => false, 'folio' => 0, 'mensaje' => $msg);
		}

		$ted = stripos($retorno, '<TED') !== false ? $retorno : '';
		$trackid = isset($r['trackid']) ? (int)$r['trackid'] : 0;
		$this->registrarEmision($db, (int)$idVenta, $folio, $trackid, $ted);
		return array('ok' => true, 'folio' => $folio, 'trackid' => $trackid, 'mensaje' => 'Documento emitido');
	}

	/** Arma el JSON newDte desde la venta guardada (las NC se guardan en negativo). */
	private function documento($db, $idVenta, $cfg)
	{
		$v = $db->query("SELECT v.*, td.cod_sii, (v.fecha_transaccion AT TIME ZONE 'America/Santiago')::date AS dia,
				(SELECT COALESCE(cf.por_iva, 19) FROM configuracion cf LIMIT 1) AS por_iva,
				c.rut, c.dv, c.nombre_cliente, c.giro, c.direccion, COALESCE(co.nombre_comuna, '') AS nombre_comuna,
				EXISTS (SELECT 1 FROM pago_venta pv JOIN configuracion cf ON cf.id_fpago_creditodirecto = pv.id_fpago
					WHERE pv.id_venta = v.id_venta) AS credito
			FROM venta v
			JOIN tdocumento td ON td.id_tdocumento = v.id_tdocumento
			LEFT JOIN cliente c ON c.id_cliente = v.id_cliente
			LEFT JOIN comuna co ON co.id_comuna = c.id_comuna
			WHERE v.id_venta = ?", array($idVenta));
		$v = $v ? $v->row() : null;
		if (!$v) throw new Exception('No se pudo leer la venta');
		$cod = (int)$v->cod_sii;
		$exento = in_array($cod, self::COD_EXENTOS);
		$esBoleta = in_array($cod, array(39, 41));
		$esNc = $cod === self::COD_NC;

		$q = $db->query("SELECT COALESCE(p.nombre_producto, 'Producto') || COALESCE(' + ' || pa.nombre_producto, '') AS nombre,
				abs(dv.cantidad) AS cantidad, abs(dv.subtotal) AS subtotal
			FROM detalle_venta dv
			LEFT JOIN producto p ON p.id_producto = dv.id_producto
			LEFT JOIN producto pa ON pa.id_producto = dv.id_agregado AND dv.id_agregado > 0
			WHERE dv.id_venta = ? ORDER BY dv.id_detalle_venta", array($idVenta));
		if (!$q) throw new Exception('No se pudo leer el detalle de la venta');
		$lineas = $q->result();
		if (!$lineas) throw new Exception('La venta no tiene detalle');

		$total = (int)abs($v->total);
		$tasa = (float)$v->por_iva > 0 ? (float)$v->por_iva : 19;
		$factor = 1 + $tasa / 100;
		// Neto e IVA que cuadran con el total pagado: IVA = round(neto * 19%) y neto + IVA = total
		if ($exento) {
			$neto = $total;
			$iva = 0;
		} else {
			$neto = (int)round($total / $factor);
			foreach (array(0, -1, 1, -2, 2) as $d) {
				if (($neto + $d) + (int)round(($neto + $d) * $tasa / 100) === $total) { $neto += $d; break; }
			}
			$iva = $total - $neto;
		}

		// Detalle en neto; el descuento global (neto) es la diferencia con el neto del total
		$detalle = array();
		$sumaLineas = 0;
		foreach ($lineas as $i => $l) {
			$monto = $exento ? (int)round((float)$l->subtotal) : (int)round((float)$l->subtotal / $factor);
			$sumaLineas += $monto;
			$detalle[] = array('cantidad' => (float)$l->cantidad, 'monto' => $monto, 'nombre' => $l->nombre);
		}
		$descuento = $sumaLineas - $neto;
		$hayDescuento = (int)abs($v->descuento) > 0;
		if ($descuento < 0 || ($descuento > 0 && !$hayDescuento)) {
			// Solo diferencias de redondeo: se ajusta la última línea para que cuadre exacto
			$detalle[count($detalle) - 1]['monto'] -= $descuento;
			$descuento = 0;
		}

		$det = array();
		foreach ($detalle as $i => $d) {
			$qty = $d['cantidad'] > 0 ? $d['cantidad'] : 1;
			$det[] = array(
				'NroLinDet' => (string)($i + 1),
				'NmbItem' => $this->texto($d['nombre'], 80),
				'QtyItem' => $this->numero($qty),
				'UnmdItem' => 'UN',
				'PrcItem' => round($d['monto'] / $qty, 6),
				'MontoItem' => $d['monto'],
				'DscItem' => '',
			);
		}

		$conCliente = (int)$v->rut > 0 && (int)$v->rut !== 66666666;
		$receptor = array('DirRecep' => '', 'RznSocRecep' => '', 'CmnaRecep' => '', 'RUTRecep' => '66666666-6', 'GiroRecep' => '');
		if ($conCliente) {
			$receptor = array(
				'DirRecep' => $this->texto($v->direccion, 70),
				'RznSocRecep' => $this->texto($v->nombre_cliente, 100),
				'CmnaRecep' => $this->texto($v->nombre_comuna, 20),
				'RUTRecep' => (int)$v->rut . '-' . strtoupper(trim((string)$v->dv)),
				'GiroRecep' => $this->texto($v->giro, 40),
			);
		} elseif (!$esBoleta && !$esNc) {
			throw new Exception('La factura requiere un cliente con RUT');
		}

		$doc = array(
			'Encabezado' => array(
				'IdDoc' => array(
					'TipoDTE' => (string)$cod,
					'Folio' => '0',
					'FchEmis' => $v->dia,
					'FchVenc' => $v->dia,
					'FmaPago' => ($v->credito === 't' || $v->credito === true) ? '2' : '1',
					'IndMntNeto' => '1',
				),
				'Emisor' => $cfg['emisor'],
				'Receptor' => $receptor,
			),
			'Totales' => $exento
				? array('MntNeto' => $total, 'TasaIVA' => '0', 'Iva' => 0, 'MntExe' => $total, 'MntTotal' => $total)
				: array('MntNeto' => $neto, 'TasaIVA' => $this->numero($tasa), 'Iva' => $iva, 'MntTotal' => $total),
			'Detalle' => $det,
			'DescuentoRecargo' => array(
				'NroLinDR' => '1',
				'TpoMov' => 'D',
				'GlosaDR' => 'DESCUENTO GLOBAL',
				'TpoValor' => '$',
				'ValorDR' => (string)$descuento,
			),
			'wsDte' => array(
				'enviaSII' => (string)$cfg['envia_sii'],
				'tipoReturn' => 'TED',
				'folioInterno' => (string)$idVenta,
				'marcaUTF8' => '1',
				'terminal' => $cfg['terminal'],
			),
		);

		if ($esNc) {
			$ref = $db->query('SELECT td.cod_sii FROM tdocumento td WHERE td.id_tdocumento = ?', array((int)$v->id_tdocumento_referencia))->row();
			if (!$ref || !(int)$v->folio_referencia) throw new Exception('La nota de crédito no tiene documento de referencia');
			$motivo = trim(preg_replace('/^DEVOLUCION [^:]*:\s*/', '', (string)$v->mensaje_pedido));
			$doc['Referencia'] = array(array(
				'NroLinRef' => '1',
				'TpoDocRef' => (string)(int)$ref->cod_sii,
				'FolioRef' => (string)(int)$v->folio_referencia,
				'FchRef' => $v->fecha_referencia,
				// 1 = anula el documento (devolución total), 3 = corrige montos (parcial)
				'CodRef' => $this->esDevolucionTotal($db, $v) ? '1' : '3',
				'RazonRef' => $this->texto($motivo !== '' ? $motivo : 'DEVOLUCION', 90),
			));
		}
		return $doc;
	}

	/** La NC cubre todo lo pagado en el documento original. */
	private function esDevolucionTotal($db, $nc)
	{
		$r = $db->query("SELECT o.total AS original,
				(SELECT COALESCE(sum(-n.total), 0) FROM venta n
					WHERE n.id_tdocumento_referencia = o.id_tdocumento AND n.folio_referencia = o.folio
					AND n.fecha_referencia = o.fecha_transaccion::date AND n.id_venta <= ?
					AND COALESCE(n.mensaje_pedido, '') NOT LIKE 'ANULADA%') AS devuelto
			FROM venta o WHERE o.id_tdocumento = ? AND o.folio = ? AND o.fecha_transaccion::date = ?
			ORDER BY o.id_venta DESC LIMIT 1",
			array((int)$nc->id_venta, (int)$nc->id_tdocumento_referencia, (int)$nc->folio_referencia, $nc->fecha_referencia))->row();
		return $r && (int)$r->devuelto >= (int)$r->original;
	}

	private function registrarEmision($db, $idVenta, $folio, $trackid, $ted)
	{
		$db->query('UPDATE venta SET folio = ?, folio_dte = ?, trackid_dte = ?, ted_dte = ? WHERE id_venta = ?',
			array($folio, $folio, $trackid, $ted !== '' ? $ted : null, $idVenta));
		$this->documentoElectronico($db, $idVenta, array('folio' => $folio, 'enviado' => true));
	}

	private function registrarFalla($db, $idVenta, $mensaje)
	{
		log_message('error', 'Caja web DTE venta ' . (int)$idVenta . ' pendiente: ' . $mensaje);
		$this->documentoElectronico($db, $idVenta, array('enviado' => false));
	}

	/** Registro en documento_electronico, como el POS (si la tabla existe). */
	private function documentoElectronico($db, $idVenta, $datos)
	{
		if (!$this->tieneColumna($db, 'documento_electronico', 'id_venta')) return;
		$datos['fecha_movimiento'] = date('Y-m-d H:i:s');
		$existe = $db->query('SELECT 1 FROM documento_electronico WHERE id_venta = ? LIMIT 1', array((int)$idVenta))->row();
		if ($existe) {
			$db->where('id_venta', (int)$idVenta)->update('documento_electronico', $datos);
		} else {
			$v = $db->query('SELECT id_caja FROM venta WHERE id_venta = ?', array((int)$idVenta))->row();
			$db->insert('documento_electronico', $datos + array(
				'id_venta' => (int)$idVenta,
				'id_caja' => $v ? (int)$v->id_caja : 0,
				'folio' => isset($datos['folio']) ? $datos['folio'] : 0,
			));
		}
	}

	private function credenciales($db)
	{
		foreach (array('configuracion', 'empresa') as $t) {
			if ($this->tieneColumna($db, $t, 'usuario_dte') && $this->tieneColumna($db, $t, 'clave_usuario_dte')) {
				$r = $db->query("SELECT trim(COALESCE(usuario_dte, '')) AS u, trim(COALESCE(clave_usuario_dte, '')) AS c FROM $t LIMIT 1")->row();
				if ($r && $r->u !== '' && $r->c !== '') return array($r->u, $r->c);
			}
		}
		return array('', '');
	}

	private function tieneColumna($db, $tabla, $columna)
	{
		static $cache = array();
		$k = $tabla . '.' . $columna;
		if (!isset($cache[$k])) {
			$cache[$k] = (bool)$db->query('SELECT 1 FROM information_schema.columns WHERE table_schema = \'public\'
				AND table_name = ? AND column_name = ?', array($tabla, $columna))->row();
		}
		return $cache[$k];
	}

	public function normalizarRut($rut)
	{
		return ltrim(strtoupper(preg_replace('/[^0-9kK]/', '', (string)$rut)), '0');
	}

	/** Texto para el SII: sin saltos ni espacios dobles, recortado. */
	private function texto($s, $max)
	{
		$s = trim(preg_replace('/\s+/u', ' ', (string)$s));
		return mb_substr($s, 0, $max);
	}

	private function numero($n)
	{
		return rtrim(rtrim(number_format((float)$n, 3, '.', ''), '0'), '.');
	}

	// ====================== Folios ======================

	/**
	 * Folios disponibles en Cloud Apps para un tipo de documento y terminal.
	 * Devuelve {ok, mensaje, rangos: [{desde, hasta, ultimo, disponibles, vence}], disponibles}.
	 */
	public function consultarFolios($usuario, $clave, $terminal, $tipoDte)
	{
		if ($usuario === '' || $clave === '') return array('ok' => false, 'mensaje' => 'Falta el usuario o la clave de facturación');
		if ($terminal === '') return array('ok' => false, 'mensaje' => 'Falta el terminal');
		if ($tipoDte <= 0) return array('ok' => false, 'mensaje' => 'Tipo de documento no válido');

		$url = preg_replace('#/newDte/?$#', '/getFolios/', $this->config->item('caja_dte_url', 'caja'));
		$ch = curl_init($url);
		curl_setopt_array($ch, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_HTTPHEADER => array(
				'Authorization: Basic ' . base64_encode($usuario . ':' . $clave),
				'Content-Type: application/json',
				'Accept: application/json',
			),
			CURLOPT_POSTFIELDS => json_encode(array(array('tipoDTE' => (string)$tipoDte, 'terminal' => $terminal, 'reobtener' => 0))),
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT => 15,
		));
		$raw = curl_exec($ch);
		$errorCurl = curl_error($ch);
		curl_close($ch);
		if ($raw === false) return array('ok' => false, 'mensaje' => 'Sin respuesta de Cloud Apps' . ($errorCurl ? ': ' . $errorCurl : ''));

		$json = json_decode($raw, true);
		if (!is_array($json)) return array('ok' => false, 'mensaje' => 'Respuesta inválida de Cloud Apps');
		$filas = isset($json[0]) ? $json : array($json);
		$rangos = array();
		$errores = array();
		foreach ($filas as $f) {
			if (!is_array($f)) continue;
			$error = isset($f['error']) ? (string)$f['error'] : (isset($f['status']) ? (string)$f['status'] : '');
			if ($error !== '0' || !isset($f['desde'], $f['hasta'])) {
				$msg = isset($f['msg']) ? $f['msg'] : (isset($f['retorno']) ? $f['retorno'] : 'Sin folios');
				// No mostrar la clave cifrada que Cloud Apps devuelve en algunos errores
				$errores[] = trim(preg_replace('/KEY invalido\s+\S+/i', 'clave inválida', (string)$msg));
				continue;
			}
			$desde = (int)$f['desde'];
			$hasta = (int)$f['hasta'];
			$ultimo = isset($f['ultimo']) ? (int)$f['ultimo'] : $desde - 1;
			$rangos[] = array(
				'desde' => $desde,
				'hasta' => $hasta,
				// Cloud Apps informa desde-1 cuando el rango no se ha usado
				'ultimo' => $ultimo >= $desde ? $ultimo : 0,
				'disponibles' => max(0, $hasta - max($ultimo, $desde - 1)),
				'vence' => isset($f['fecha_vencimiento']) ? $f['fecha_vencimiento'] : '',
			);
		}
		if (!$rangos) return array('ok' => false, 'mensaje' => $errores ? implode(' | ', array_unique($errores)) : 'Cloud Apps no devolvió folios');
		$disponibles = 0;
		foreach ($rangos as $r) $disponibles += $r['disponibles'];
		return array('ok' => true, 'mensaje' => 'Folios disponibles', 'rangos' => $rangos, 'disponibles' => $disponibles);
	}

	// ====================== Timbre (TED → PDF417) ======================

	/** Código de barras del TED: PNG (PDF) o data URI del PNG (ticket HTML). null si no hay TED. */
	public function timbre($ted, $formato = 'datauri')
	{
		if (!$ted || stripos($ted, '<TED') === false) return null;
		require_once APPPATH . 'third_party/tcpdf_barcodes/tcpdf_barcodes_2d.php';
		// El SII pide el TED en ISO-8859-1 y corrección de errores nivel 5
		$codigo = mb_convert_encoding($ted, 'ISO-8859-1', 'UTF-8');
		$b = new TCPDF2DBarcode($codigo, 'PDF417,,5');
		$png = $b->getBarcodePngData(2, 2);
		if (!$png) return null;
		return $formato === 'png' ? $png : 'data:image/png;base64,' . base64_encode($png);
	}
}
