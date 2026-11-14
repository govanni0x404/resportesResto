<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Emisión electrónica con Cloud Apps o con facturacion.datamaule.cl (SimpleFactura), según
 * Configuraciones > Empresa proveedora DTE. SimpleFactura recibe el mismo JSON de miFacturador
 * (newDte) y responde igual, así que el documento es el mismo; cambian la URL y la credencial
 * (tipo de autenticación: X-API-Key, Bearer, Token o Basic; ver helpers/dte_auth_helper.php).
 * El proveedor asigna el folio (Folio "0") desde el CAF del terminal y devuelve el TED.
 * Boletas (39): detalle en montos brutos, como lo pide el formato de boleta del SII; el neto y el IVA son
 * informativos: Neto = round(Afecto / 1,19) e IVA = Afecto - Neto, igual que la pantalla y el ticket.
 * Facturas y notas de crédito: detalle en neto (IndMntNeto = 1); Neto e IVA = round(Neto x 19%) los
 * calcula la venta (Mcaja::montos). Productos exentos: línea con IndExe = 1 y su suma en MntExe.
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
		$this->load->helper('dte_auth');
	}

	/** URL de un método del proveedor: newDte o folios (Cloud Apps: getFolios; datamaule: cafInfo). */
	private function urlProveedor($proveedor, $metodo)
	{
		if ($proveedor === 'datamaule') {
			$base = rtrim((string)$this->config->item('caja_dte_url_datamaule', 'caja'), '/');
			if ($base === '') $base = 'https://facturacion.datamaule.cl/api/v1/integracion';
			return $base . ($metodo === 'folios' ? '/cafInfo/' : '/newDte/');
		}
		$url = (string)$this->config->item('caja_dte_url', 'caja');
		return $metodo === 'folios' ? preg_replace('#/newDte/?$#', '/getFolios/', $url) : $url;
	}

	/** Cuenta demo de Cloud Apps: con este usuario se emite en pruebas (Cloud Apps timbra, pero no va al SII). */
	const RUT_DEMO = '66666666-6';
	const RAZON_DEMO = 'EMPRESA DEMO';

	/**
	 * Credenciales, terminal y emisor. null si la empresa no tiene emisión configurada (ver motivoSinEmision()).
	 * Todo sale de Configuraciones > Facturación electrónica > Acceso facturación: usuario (RUT válido) y terminal
	 * son obligatorios, más la contraseña (Basic) o la API Key (X-API-Key, Bearer, Token). El usuario debe ser el RUT
	 * de la empresa, salvo la cuenta demo (66666666-6), que emite como EMPRESA DEMO y no envía al SII.
	 * La URL depende de la empresa proveedora (Cloud Apps o facturacion.datamaule.cl).
	 */
	public function configuracion($db)
	{
		$empresa = $db->query('SELECT e.*, COALESCE(c.nombre_comuna, \'\') AS nombre_comuna
			FROM empresa e LEFT JOIN comuna c ON c.id_comuna = e.id_comuna ORDER BY e.id_empresa LIMIT 1')->row();
		if (!$empresa) return null;
		$acceso = $this->accesoFacturacion($db);
		$usuario = $acceso['usuario'];
		if ($this->motivoAcceso($usuario, $acceso['clave'], $acceso['terminal'], $acceso['tipo'], $acceso['token']) !== '') return null;
		$auth = dte_auth_headers($acceso['tipo'], $usuario, $acceso['clave'], $acceso['token']);
		if (!$auth['ok']) return null;
		$rut = number_format((float)$empresa->rut_empresa, 0, '', '') . '-' . strtoupper(trim((string)$empresa->dv_empresa));
		$demo = $this->normalizarRut($usuario) === $this->normalizarRut(self::RUT_DEMO);
		// Cada empresa emite con su propio RUT: un usuario de otra empresa firmaría documentos ajenos
		if (!$demo && $this->normalizarRut($usuario) !== $this->normalizarRut($rut)) {
			log_message('error', 'Caja web DTE: usuario_dte (' . $usuario . ') no coincide con el RUT de la empresa (' . $rut . ')');
			return null;
		}
		$enviaSii = 0;
		if (!$demo) {
			$enviaSii = 1;
			if ($this->tieneColumna($db, 'configuracion', 'enviar_dte_sii')) {
				$r = $db->query('SELECT (enviar_dte_sii IS NOT FALSE) AS envia FROM configuracion LIMIT 1')->row();
				$enviaSii = $r && ($r->envia === 't' || $r->envia === true) ? 1 : 0;
			}
		}
		$prueba = $demo ? array('rut_emisor' => self::RUT_DEMO, 'razon_social' => self::RAZON_DEMO) : null;
		return array(
			'usuario' => $usuario,
			'terminal' => $acceso['terminal'],
			'proveedor' => $acceso['proveedor'],
			'url' => $this->urlProveedor($acceso['proveedor'], 'newDte'),
			'auth_headers' => $auth['headers'],
			'envia_sii' => $enviaSii,
			'prueba' => $demo,
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

	/**
	 * Cuánto se espera la respuesta de Cloud Apps: Configuraciones > Impresión de documentos > Timeout (seg).
	 * Vacío o 0 = 20 segundos; se acota entre 5 y 60 para no dejar la caja colgada.
	 */
	private function segundosEspera($db)
	{
		if (!$this->tieneColumna($db, 'configuracion', 'timeout')) return 20;
		$r = $db->query('SELECT COALESCE(timeout, 0) AS t FROM configuracion LIMIT 1')->row();
		$t = $r ? (int)$r->t : 0;
		return $t > 0 ? max(5, min(60, $t)) : 20;
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

		$nombreProveedor = dte_proveedor_nombre($cfg['proveedor']);
		$ch = curl_init($cfg['url']);
		curl_setopt_array($ch, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_HTTPHEADER => array_merge($cfg['auth_headers'], array(
				'Content-Type: application/json',
				'Accept: application/json',
			)),
			CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT => $this->segundosEspera($db),
		));
		$raw = curl_exec($ch);
		$errorCurl = curl_error($ch);
		$http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($raw === false) {
			$msg = 'Sin respuesta de ' . $nombreProveedor . ($errorCurl ? ': ' . $errorCurl : '');
			$this->registrarFalla($db, $idVenta, $msg);
			return array('ok' => false, 'folio' => 0, 'mensaje' => $msg);
		}
		$json = json_decode($raw, true);
		$r = is_array($json) && isset($json[0]) && is_array($json[0]) ? $json[0] : (is_array($json) ? $json : array());
		$status = isset($r['status']) ? (int)$r['status'] : (isset($r['error']) ? (int)$r['error'] : -1);
		$folio = isset($r['folio']) ? (int)$r['folio'] : 0;
		$retorno = isset($r['retorno']) ? (string)$r['retorno'] : (isset($r['msg']) ? (string)$r['msg'] : (isset($r['detail']) && is_string($r['detail']) ? $r['detail'] : ''));

		if ($status !== 0 || $folio <= 0) {
			$msg = $retorno !== '' ? $retorno : 'Respuesta inesperada de ' . $nombreProveedor;
			log_message('error', 'Caja web DTE venta ' . (int)$idVenta . ' (HTTP ' . $http . '): ' . substr($raw, 0, 500));
			$this->registrarFalla($db, $idVenta, $msg);
			return array('ok' => false, 'folio' => 0, 'mensaje' => $msg);
		}

		$ted = stripos($retorno, '<TED') !== false ? $retorno : '';
		if ($ted === '') log_message('error', 'Caja web DTE venta ' . (int)$idVenta . ': folio ' . $folio . ' emitido sin TED: ' . substr($raw, 0, 500));
		// SimpleFactura informa trackId y trackid; Cloud Apps, trackid
		$trackid = isset($r['trackid']) ? (int)$r['trackid'] : (isset($r['trackId']) ? (int)$r['trackId'] : 0);
		$this->registrarEmision($db, (int)$idVenta, $folio, $trackid, $ted);
		return array('ok' => true, 'folio' => $folio, 'trackid' => $trackid, 'mensaje' => 'Documento emitido');
	}

	/** Arma el JSON newDte desde la venta guardada (las NC se guardan en negativo). */
	private function documento($db, $idVenta, $cfg)
	{
		$v = $db->query("SELECT v.*, td.cod_sii, (v.fecha_transaccion AT TIME ZONE 'America/Santiago')::date AS dia,
				(SELECT COALESCE(cf.por_iva, 19) FROM configuracion cf LIMIT 1) AS por_iva,
				(SELECT COALESCE(cf.id_cliente_publico, 0) FROM configuracion cf LIMIT 1) AS id_cliente_publico,
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
				abs(dv.cantidad) AS cantidad, abs(dv.subtotal) AS subtotal, (COALESCE(dv.poriva, 1) = 0) AS exento
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
		// Boleta afecta: el detalle va con IVA incluido y el neto / IVA se derivan del total
		$brutos = $esBoleta && !$exento;
		// Lo exento por producto (Monto Exento) queda fuera del IVA; el resto es el monto afecto
		$montoExento = $exento ? $total : min($total, (int)round(abs((float)$v->monto_exento)));
		$afecto = $total - $montoExento;
		if ($exento) {
			$neto = $total;
			$iva = 0;
		} elseif ($brutos) {
			$neto = (int)round($afecto / $factor);
			$iva = $afecto - $neto;
		} else {
			// Factura / NC: el neto guardado al vender (montos() en Mcaja) ya cumple IVA = round(Neto x 19%).
			// Ventas anteriores a ese cálculo: se busca el neto que cuadre con el monto afecto.
			$neto = (int)round(abs((float)$v->neto));
			if ($neto + (int)round($neto * $tasa / 100) !== $afecto) {
				$neto = (int)round($afecto / $factor);
				foreach (array(0, -1, 1, -2, 2) as $d) {
					if (($neto + $d) + (int)round(($neto + $d) * $tasa / 100) === $afecto) { $neto += $d; break; }
				}
			}
			$iva = $afecto - $neto;
		}

		// Detalle bruto (boletas y exentos) o neto (facturas y NC); las líneas exentas van con su monto
		// (IndExe = 1). El descuento global de lo afecto va en la misma base que su detalle y es la
		// diferencia entre la suma de las líneas afectas y el afecto (o el neto) del documento.
		$detalle = array();
		$sumaAfecto = 0;
		$sumaExento = 0;
		foreach ($lineas as $i => $l) {
			$lineaExenta = !$exento && ($l->exento === 't' || $l->exento === true);
			$monto = ($exento || $brutos || $lineaExenta) ? (int)round((float)$l->subtotal) : (int)round((float)$l->subtotal / $factor);
			if ($lineaExenta) $sumaExento += $monto; else $sumaAfecto += $monto;
			$detalle[] = array('cantidad' => (float)$l->cantidad, 'monto' => $monto, 'nombre' => $l->nombre, 'exento' => $lineaExenta);
		}
		// El descuento que tocó a lo exento se aplica sobre las mismas líneas exentas, desde la última
		$descExento = $exento ? 0 : $sumaExento - $montoExento;
		for ($i = count($detalle) - 1; $i >= 0 && $descExento !== 0; $i--) {
			if (!$detalle[$i]['exento']) continue;
			$baja = $descExento > 0 ? min($descExento, $detalle[$i]['monto']) : $descExento;
			$detalle[$i]['monto'] -= $baja;
			$descExento -= $baja;
		}
		$descuento = $sumaAfecto > 0 ? $sumaAfecto - ($exento ? $total : ($brutos ? $afecto : $neto)) : 0;
		$hayDescuento = (int)abs($v->descuento) > 0;
		if ($descuento < 0 || ($descuento > 0 && !$hayDescuento)) {
			// Solo diferencias de redondeo: se ajusta la última línea afecta para que cuadre exacto
			for ($i = count($detalle) - 1; $i >= 0; $i--) {
				if (!$detalle[$i]['exento']) { $detalle[$i]['monto'] -= $descuento; break; }
			}
			$descuento = 0;
		}

		$det = array();
		foreach ($detalle as $i => $d) {
			$qty = $d['cantidad'] > 0 ? $d['cantidad'] : 1;
			$linea = array(
				'NroLinDet' => (string)($i + 1),
				'NmbItem' => $this->texto($d['nombre'], 80),
				'QtyItem' => $this->numero($qty),
				'UnmdItem' => 'UN',
				'PrcItem' => round($d['monto'] / $qty, 6),
				'MontoItem' => $d['monto'],
				'DscItem' => '',
			);
			if ($d['exento']) $linea['IndExe'] = '1';
			$det[] = $linea;
		}

		// Cliente público (el de configuracion, 66.666.666-6 o el 6.666.666-6 de algunas bases): receptor genérico
		$rutRecep = (int)$v->rut;
		$publico = $rutRecep <= 0 || in_array($rutRecep, array(66666666, 6666666), true)
			|| ((int)$v->id_cliente_publico > 0 && (int)$v->id_cliente === (int)$v->id_cliente_publico);
		$conCliente = !$publico;
		if ($conCliente && !$this->dvValido($rutRecep, (string)$v->dv)) {
			if (!$esBoleta) throw new Exception('El RUT del cliente (' . $rutRecep . '-' . trim((string)$v->dv) . ') no es válido');
			$conCliente = false;   // en boleta el receptor es opcional: se emite como público
		}
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
		// Datos elegidos en la Caja al cobrar (venta_receptor, migración 20261111180000): giro, dirección, comuna, contacto y
		// quién solicita la factura. Sin esa tabla o sin fila para la venta, los de la ficha del cliente, como antes.
		$rutSolicita = '';
		if ($conCliente) {
			$hay = $db->query("SELECT to_regclass('public.venta_receptor') IS NOT NULL AS ok");
			$hay = $hay ? $hay->row() : null;
			$rec = ($hay && ($hay->ok === 't' || $hay->ok === true)) ? $db->query('SELECT * FROM venta_receptor WHERE id_venta = ?', array($idVenta)) : null;
			$rec = $rec ? $rec->row() : null;
			if ($rec) {
				if (trim((string)$rec->razon_social) !== '') $receptor['RznSocRecep'] = $this->texto($rec->razon_social, 100);
				$receptor['GiroRecep'] = $this->texto($rec->giro, 40);
				$receptor['DirRecep'] = $this->texto($rec->direccion, 70);
				$receptor['CmnaRecep'] = $this->texto($rec->comuna, 20);
				if (trim((string)$rec->ciudad) !== '') $receptor['CiudadRecep'] = $this->texto($rec->ciudad, 20);
				if (trim((string)$rec->contacto) !== '') $receptor['Contacto'] = $this->texto($rec->contacto, 80);
				if ((int)$rec->rut_solicita > 0) $rutSolicita = (int)$rec->rut_solicita . '-' . strtoupper(trim((string)$rec->dv_solicita));
			}
		}

		$idDoc = array(
			'TipoDTE' => (string)$cod,
			'Folio' => '0',
			'FchEmis' => $v->dia,
			'FchVenc' => $v->dia,
			'FmaPago' => ($v->credito === 't' || $v->credito === true) ? '2' : '1',
		);
		// En boletas el detalle va bruto: sin indicador de montos netos
		if (!$brutos) $idDoc['IndMntNeto'] = '1';

		$doc = array(
			'Encabezado' => array(
				'IdDoc' => $idDoc,
				'Emisor' => $this->emisorDeSucursal($db, $v, $cfg['emisor']),
				'Receptor' => $receptor,
			),
			'Totales' => $this->totalesDte($exento, $total, $neto, $iva, $montoExento, $tasa),
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
		// Quién solicita la factura (elegido en la Caja): va en el Encabezado, después del Receptor (orden del esquema del SII)
		if ($rutSolicita !== '') {
			$enc = array();
			foreach ($doc['Encabezado'] as $k => $val) {
				$enc[$k] = $val;
				if ($k === 'Receptor') $enc['RUTSolicita'] = $rutSolicita;
			}
			$doc['Encabezado'] = $enc;
		}
		return $doc;
	}

	/** Bloque Totales: Monto Neto e IVA solo si hay monto afecto; Monto Exento solo si hay exento. */
	private function totalesDte($docExento, $total, $neto, $iva, $montoExento, $tasa)
	{
		if ($docExento) return array('MntNeto' => $total, 'TasaIVA' => '0', 'Iva' => 0, 'MntExe' => $total, 'MntTotal' => $total);
		$t = array();
		if ($total - $montoExento > 0) {
			$t['MntNeto'] = $neto;
			$t['TasaIVA'] = $this->numero($tasa);
			$t['Iva'] = $iva;
		}
		if ($montoExento > 0) $t['MntExe'] = $montoExento;
		$t['MntTotal'] = $total;
		return $t;
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
		$ok = $db->query('UPDATE venta SET folio = ?, folio_dte = ?, trackid_dte = ?, ted_dte = ? WHERE id_venta = ?',
			array($folio, $folio, $trackid, $ted !== '' ? $ted : null, $idVenta));
		if (!$ok) {
			// El documento ya existe en Cloud Apps: queda en el log para conciliarlo a mano (no reenviar)
			log_message('error', 'Caja web DTE venta ' . (int)$idVenta . ': EMITIDO folio ' . (int)$folio . ' trackid ' . (int)$trackid . ' pero no se pudo guardar en la venta');
		}
		$this->documentoElectronico($db, $idVenta, array('folio' => $folio, 'enviado' => true));
	}

	/** Dígito verificador de un RUT chileno (módulo 11). */
	private function dvValido($rut, $dv)
	{
		$suma = 0;
		$mult = 2;
		for ($n = (int)$rut; $n > 0; $n = intdiv($n, 10)) {
			$suma += ($n % 10) * $mult;
			$mult = $mult === 7 ? 2 : $mult + 1;
		}
		$r = 11 - ($suma % 11);
		$esperado = $r === 11 ? '0' : ($r === 10 ? 'K' : (string)$r);
		return strtoupper(trim($dv)) === $esperado;
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

	/**
	 * Configuraciones > Acceso facturación: usuario (RUT), contraseña, terminal, tipo de autenticación,
	 * API Key / token (descifrado) y empresa proveedora.
	 */
	private function accesoFacturacion($db)
	{
		$u = $c = $t = '';
		if ($this->tieneColumna($db, 'configuracion', 'usuario_dte') && $this->tieneColumna($db, 'configuracion', 'clave_usuario_dte')) {
			$r = $db->query("SELECT trim(COALESCE(usuario_dte, '')) AS u, trim(COALESCE(clave_usuario_dte, '')) AS c FROM configuracion LIMIT 1")->row();
			if ($r) { $u = $r->u; $c = $r->c; }
		}
		if ($this->tieneColumna($db, 'configuracion', 'terminal_dte')) {
			$r = $db->query("SELECT trim(COALESCE(terminal_dte, '')) AS t FROM configuracion LIMIT 1")->row();
			$t = $r ? $r->t : '';
		}
		$tipo = dte_auth_tipo($db);
		return array(
			'usuario' => self::rutUsuario($u),
			'clave' => $c,
			'terminal' => $t,
			'tipo' => $tipo,
			'token' => $tipo === 'basic' ? '' : dte_auth_token($db),
			'proveedor' => dte_proveedor_actual($db),
		);
	}

	/**
	 * '' si el acceso sirve; si no, qué falta (para mostrarlo en pantalla). El usuario (RUT de la empresa) se pide
	 * siempre: con él se comprueba que se emita con el RUT de esta empresa y se reconoce la cuenta demo.
	 * Basic necesita la contraseña; X-API-Key, Bearer y Token, la API Key.
	 */
	public function motivoAcceso($usuario, $clave, $terminal, $tipoAuth = 'basic', $token = '')
	{
		if (trim((string)$usuario) === '') return 'Falta el usuario (RUT) en Acceso facturación';
		if (!self::rutValido($usuario)) return 'El usuario de Acceso facturación no es un RUT válido (' . $usuario . ')';
		if ($tipoAuth === 'basic' || !in_array($tipoAuth, dte_auth_tipos_validos(), true)) {
			if ((string)$clave === '') return 'Falta la contraseña en Acceso facturación';
		} elseif (trim((string)$token) === '') {
			return 'Falta la API Key en Acceso facturación';
		}
		if (trim((string)$terminal) === '') return 'Falta el terminal en Acceso facturación';
		return '';
	}

	/** Por qué la empresa no puede emitir ('' si puede): lo muestra la Caja. */
	public function motivoSinEmision($db)
	{
		$a = $this->accesoFacturacion($db);
		if ($a['tipo'] !== 'basic' && $a['token'] === '' && dte_auth_clave_cifrado() === '' && dte_auth_token_info($db)['tiene']) {
			return 'Falta RESTO_DTE_TOKEN_KEY en el servidor para leer la API Key guardada';
		}
		$m = $this->motivoAcceso($a['usuario'], $a['clave'], $a['terminal'], $a['tipo'], $a['token']);
		if ($m !== '') return $m;
		return $this->configuracion($db) ? '' : 'El usuario de Acceso facturación no es el RUT de esta empresa';
	}

	/** Usuario de Cloud Apps = RUT sin puntos y con guion: "66.666.666-6" o "666666666" => "66666666-6". */
	public static function rutUsuario($u)
	{
		$u = strtoupper(preg_replace('/[^0-9kK-]/', '', (string)$u));
		if ($u !== '' && strpos($u, '-') === false && strlen($u) > 1) $u = substr($u, 0, -1) . '-' . substr($u, -1);
		return $u;
	}

	/** RUT chileno con dígito verificador correcto ("76193938-6", "66.666.666-6"). */
	public static function rutValido($rut)
	{
		$r = strtoupper(preg_replace('/[^0-9kK]/', '', (string)$rut));
		if (strlen($r) < 2) return false;
		$cuerpo = substr($r, 0, -1);
		$dv = substr($r, -1);
		if (!ctype_digit($cuerpo)) return false;
		$suma = 0;
		$mul = 2;
		for ($i = strlen($cuerpo) - 1; $i >= 0; $i--) {
			$suma += (int)$cuerpo[$i] * $mul;
			$mul = $mul === 7 ? 2 : $mul + 1;
		}
		$res = 11 - ($suma % 11);
		$esperado = $res === 11 ? '0' : ($res === 10 ? 'K' : (string)$res);
		return $dv === $esperado;
	}

	/**
	 * Configuraciones > Datos de la Empresa (11-11-2026): el documento de una caja de Control de cajas lleva la dirección,
	 * comuna y código SII (CdgSIISucur) de su sucursal, si es de la empresa que emite. Sin sucursal, el emisor de siempre.
	 */
	private function emisorDeSucursal($db, $v, $emisor)
	{
		$this->load->helper('sucursal_empresa');
		$s = sucursal_de_caja($db, isset($v->id_caja) ? (int)$v->id_caja : 0);
		if (!$s) return $emisor;
		$res = array();
		foreach ($emisor as $k => $valor) {
			if ($k === 'DirOrigen' && $s->codigo_sii > 0) $res['CdgSIISucur'] = (string)$s->codigo_sii;
			if ($s->direccion !== '') {
				if ($k === 'DirOrigen') $valor = $this->texto($s->direccion, 70);
				if (($k === 'CmnaOrigen' || $k === 'CiudadOrigen') && $s->comuna !== '') $valor = $this->texto($s->comuna, 20);
			}
			$res[$k] = $valor;
		}
		return $res;
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
	 * Folios disponibles para un tipo de documento y terminal, en el proveedor indicado
	 * (Cloud Apps: getFolios; datamaule.cl: cafInfo). Usa los datos de la pantalla aunque no estén grabados.
	 * Devuelve {ok, mensaje, rangos: [{desde, hasta, ultimo, disponibles, vence}], disponibles}.
	 */
	public function consultarFolios($proveedor, $tipoAuth, $usuario, $clave, $token, $terminal, $tipoDte)
	{
		$proveedor = in_array($proveedor, dte_proveedores_validos(), true) ? $proveedor : 'acierto';
		$nombre = dte_proveedor_nombre($proveedor);
		$usuario = self::rutUsuario($usuario);
		$falta = $this->motivoAcceso($usuario, $clave, $terminal, $tipoAuth, $token);
		if ($falta !== '') return array('ok' => false, 'mensaje' => $falta);
		if ($tipoDte <= 0) return array('ok' => false, 'mensaje' => 'Tipo de documento no válido');
		$auth = dte_auth_headers($tipoAuth, $usuario, $clave, $token);
		if (!$auth['ok']) return array('ok' => false, 'mensaje' => $auth['mensaje']);

		$cuerpo = $proveedor === 'datamaule'
			? array(array('tipoDTE' => (string)$tipoDte, 'terminal' => $terminal))
			: array(array('tipoDTE' => (string)$tipoDte, 'terminal' => $terminal, 'reobtener' => 0));
		$ch = curl_init($this->urlProveedor($proveedor, 'folios'));
		curl_setopt_array($ch, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_HTTPHEADER => array_merge($auth['headers'], array(
				'Content-Type: application/json',
				'Accept: application/json',
			)),
			CURLOPT_POSTFIELDS => json_encode($cuerpo),
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT => 15,
		));
		$raw = curl_exec($ch);
		$errorCurl = curl_error($ch);
		curl_close($ch);
		if ($raw === false) return array('ok' => false, 'mensaje' => 'Sin respuesta de ' . $nombre . ($errorCurl ? ': ' . $errorCurl : ''));

		$json = json_decode($raw, true);
		if (!is_array($json)) return array('ok' => false, 'mensaje' => 'Respuesta inválida de ' . $nombre);
		// SimpleFactura: errores de autenticación en cafInfo vienen como {"detail": "..."} (HTTP 401/403)
		if (isset($json['detail']) && is_string($json['detail'])) return array('ok' => false, 'mensaje' => $json['detail']);
		$filas = isset($json[0]) ? $json : array($json);
		$rangos = array();
		$errores = array();
		foreach ($filas as $f) {
			if (!is_array($f)) continue;
			if ($proveedor === 'datamaule') {
				// cafInfo: próximo folio, disponibles en el CAF en uso y en todos los CAF vigentes del terminal
				$retorno = trim((string)($f['retorno'] ?? ''));
				$siguiente = (int)($f['nextFolio'] ?? 0);
				if ($retorno !== '' || $siguiente <= 0) {
					$errores[] = $retorno !== '' ? $retorno : (isset($f['msg']) ? (string)$f['msg'] : 'Sin folios');
					continue;
				}
				$enUso = (int)($f['disponibles'] ?? 0);
				$total = isset($f['disponible_total']) ? (int)$f['disponible_total'] : $enUso;
				$rangos[] = array(
					'desde' => $siguiente,                       // próximo folio que se entregará
					'hasta' => $siguiente + max(0, $enUso - 1),  // último folio del CAF en uso
					'ultimo' => $siguiente - 1,
					'disponibles' => max(0, $total),
					'vence' => isset($f['vencimiento']) ? (string)$f['vencimiento'] : '',
				);
				continue;
			}
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
		if (!$rangos) return array('ok' => false, 'mensaje' => $errores ? implode(' | ', array_unique($errores)) : $nombre . ' no devolvió folios');
		$disponibles = 0;
		foreach ($rangos as $r) $disponibles += $r['disponibles'];
		return array('ok' => true, 'mensaje' => 'Folios disponibles', 'rangos' => $rangos, 'disponibles' => $disponibles,
			'proveedor' => $proveedor);
	}

	// ====================== Timbre (TED → PDF417) ======================

	/** Código de barras del TED: PNG (PDF) o data URI del PNG (ticket HTML). null si no hay TED. */
	/**
	 * Timbre como matriz de módulos PDF417 (para dibujarlo en la impresora térmica) o null. $maxPuntos = ancho del papel en
	 * puntos: se elige un código más alto y angosto hasta que cada módulo mida 2 puntos (con medidas desparejas no se lee).
	 */
	public function timbreMatriz($ted, $maxPuntos = 512)
	{
		if (!$ted || stripos($ted, '<TED') === false) return null;
		require_once APPPATH . 'third_party/tcpdf_barcodes/tcpdf_barcodes_2d.php';
		$codigo = mb_convert_encoding($ted, 'ISO-8859-1', 'UTF-8');
		$a = null;
		foreach (array(2, 1.5, 1.2, 1, 0.8, 0.65, 0.5, 0.4) as $proporcion) {
			$b = new TCPDF2DBarcode($codigo, 'PDF417,' . $proporcion . ',5');
			$a = $b->getBarcodeArray();
			if (!empty($a['num_cols']) && $a['num_cols'] * 2 <= $maxPuntos) break;
		}
		return !empty($a['num_cols']) ? $a : null;
	}

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
