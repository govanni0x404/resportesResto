<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mdelivery extends CI_Model
{
  public function guardarEvidenciaDelivery($idUsuario, $idDelivery, $nombreArchivo, $nombreOriginal, $tipoMime, $tamano)
  {
    $this->load->helper('tenant'); $db = get_tenant_db($this);
    if (!$db->table_exists('delivery_evidencia')) throw new Exception('Falta actualizar la base con la migración de respaldos Delivery.', 409);
    if (!preg_match('/^[a-f0-9]{32}\.(jpg|jpeg|png|pdf)$/i', (string)$nombreArchivo) || !in_array($tipoMime, array('image/jpeg','image/png','application/pdf'), true) || (int)$tamano <= 0 || (int)$tamano > 5242880) throw new Exception('El respaldo debe ser JPG, PNG o PDF de hasta 5 MB.', 400);
    $d = $db->query("SELECT id_delivery FROM delivery_pedido WHERE id_delivery = ? AND id_repartidor = ? AND estado_entrega = 'retirado'", array((int)$idDelivery, (int)$idUsuario))->row();
    if (!$d) throw new Exception('Solo puedes adjuntar respaldo a un pedido tuyo que ya retiraste.', 403);
    $original = mb_substr(trim(preg_replace('/[\x00-\x1F\x7F]+/u', '_', basename((string)$nombreOriginal))), 0, 120);
    if ($original === '') $original = 'respaldo';
    $db->query('INSERT INTO delivery_evidencia (id_delivery, id_usuario, nombre_archivo, nombre_original, tipo_mime, tamano) VALUES (?, ?, ?, ?, ?, ?)',
      array((int)$idDelivery, (int)$idUsuario, (string)$nombreArchivo, $original, (string)$tipoMime, (int)$tamano));
    $r = $db->query('SELECT currval(pg_get_serial_sequence(\'delivery_evidencia\', \'id_delivery_evidencia\')) AS id')->row();
    return array('id_evidencia' => $r ? (int)$r->id : 0);
  }

  public function obtenerEvidenciaDelivery($idEvidencia)
  {
    $this->load->helper('tenant'); $db = get_tenant_db($this);
    if (!$db->table_exists('delivery_evidencia')) return null;
    $q = $db->query('SELECT * FROM delivery_evidencia WHERE id_delivery_evidencia = ?', array((int)$idEvidencia));
    return $q ? $q->row_array() : null;
  }

  public function guardarTarifasComision($filas, $idAdmin)
  {
    $this->load->helper('tenant'); $db = get_tenant_db($this);
    if (!$db->table_exists('delivery_tarifa_repartidor')) return false;
    $db->trans_begin();
    try {
      foreach ($filas as $id => $tarifa) {
        $id = (int)$id;
        if ($id <= 0 || !is_array($tarifa) || !$db->query('SELECT 1 FROM usuario WHERE id_usuario = ? AND vigente IS TRUE', array($id))->row()) continue;
        $tipo = isset($tarifa['tipo']) && is_scalar($tarifa['tipo']) ? (string)$tarifa['tipo'] : '';
        if ($tipo === '') { $db->delete('delivery_tarifa_repartidor', array('id_usuario' => $id)); continue; }
        if (!in_array($tipo, array('fija','porcentaje','sin_comision'), true)) continue;
        $valorRaw = isset($tarifa['valor']) && is_scalar($tarifa['valor']) ? (string)$tarifa['valor'] : '0';
        if (!is_numeric($valorRaw)) continue;
        $valor = max(0, (float)$valorRaw);
        if ($tipo === 'porcentaje') $valor = min(100, $valor);
        if ($tipo === 'sin_comision') $valor = 0;
        $db->query('INSERT INTO delivery_tarifa_repartidor (id_usuario, tipo, valor, actualizado_por, actualizado_en) VALUES (?, ?, ?, ?, now())
          ON CONFLICT (id_usuario) DO UPDATE SET tipo = EXCLUDED.tipo, valor = EXCLUDED.valor, actualizado_por = EXCLUDED.actualizado_por, actualizado_en = now()',
          array($id, $tipo, $valor, (int)$idAdmin));
      }
      if ($db->trans_status() === false) throw new Exception('No se guardaron las tarifas de Delivery.');
      $db->trans_commit();
    } catch (Throwable $e) { $db->trans_rollback(); throw $e; }
    return true;
  }

  public function tarifasComision()
  {
    $this->load->helper('tenant'); $db = get_tenant_db($this);
    if (!$db->table_exists('delivery_tarifa_repartidor')) return array();
    $q = $db->query('SELECT t.id_usuario, t.tipo, t.valor, u.nombre_usuario FROM delivery_tarifa_repartidor t JOIN usuario u ON u.id_usuario = t.id_usuario ORDER BY u.nombre_usuario');
    $r = array(); foreach (($q ? $q->result_array() : array()) as $f) $r[(int)$f['id_usuario']] = $f;
    return $r;
  }

  /** Captura la tarifa vigente solo cuando el pedido ya está entregado y el cobro está verificado. */
  public function capturarComision($idDelivery)
  {
    $this->load->helper('tenant'); $db = get_tenant_db($this);
    if (!$db->table_exists('delivery_comision')) return false;
    $desc = $db->field_exists('descuento', 'delivery_pedido') ? 'COALESCE(d.descuento, 0)' : '0';
    $r = $db->query("SELECT d.id_delivery, d.id_repartidor, d.subtotal, $desc AS descuento, c.delivery_comision_tipo, c.delivery_comision_valor,
        t.tipo AS tipo_especifico, t.valor AS valor_especifico
      FROM delivery_pedido d CROSS JOIN (SELECT delivery_comision_tipo, delivery_comision_valor FROM configuracion ORDER BY id_configuracion LIMIT 1) c
      LEFT JOIN delivery_tarifa_repartidor t ON t.id_usuario = d.id_repartidor
      WHERE d.id_delivery = ? AND d.estado_entrega = 'entregado' AND d.estado_pago = 'verificado' AND d.id_repartidor IS NOT NULL
      AND NOT EXISTS (SELECT 1 FROM delivery_comision x WHERE x.id_delivery = d.id_delivery)", array((int)$idDelivery))->row();
    if (!$r) return false;
    $tipo = $r->tipo_especifico ?: (string)$r->delivery_comision_tipo;
    $tarifa = $r->tipo_especifico ? (float)$r->valor_especifico : (float)$r->delivery_comision_valor;
    if (!in_array($tipo, array('fija','porcentaje'), true) || $tarifa <= 0) return false;
    $base = max(0, (int)$r->subtotal - (int)$r->descuento); // productos después de descuento; despacho y propina excluidos
    $monto = $tipo === 'porcentaje' ? (int)round($base * $tarifa / 100) : (int)round($tarifa);
    if ($monto <= 0) return false;
    $db->query("INSERT INTO delivery_comision (id_delivery, id_repartidor, tipo, tarifa, base, monto) VALUES (?, ?, ?, ?, ?, ?) ON CONFLICT (id_delivery) DO NOTHING",
      array((int)$idDelivery, (int)$r->id_repartidor, $tipo, $tarifa, $base, $monto));
    return $db->affected_rows() === 1;
  }

  public function listarComisiones()
  {
    $this->load->helper('tenant'); $db = get_tenant_db($this);
    if (!$db->table_exists('delivery_comision')) return array();
    $elegibles = $db->query("SELECT id_delivery FROM delivery_pedido WHERE estado_entrega = 'entregado' AND estado_pago = 'verificado' AND id_repartidor IS NOT NULL AND NOT EXISTS (SELECT 1 FROM delivery_comision c WHERE c.id_delivery = delivery_pedido.id_delivery)")->result();
    foreach ($elegibles as $e) $this->capturarComision((int)$e->id_delivery);
    $q = $db->query('SELECT c.*, p.id_delivery, u.nombre_usuario AS repartidor FROM delivery_comision c JOIN delivery_pedido p ON p.id_delivery = c.id_delivery LEFT JOIN usuario u ON u.id_usuario = c.id_repartidor ORDER BY (c.estado = \'pendiente\') DESC, c.id_delivery_comision DESC LIMIT 250');
    return $q ? $q->result_array() : array();
  }

  public function listarLiquidacionesPendientes()
  {
    $this->load->helper('tenant'); $db = get_tenant_db($this);
    if (!$db->table_exists('delivery_liquidacion')) return array();
    $evidencia = $db->table_exists('delivery_evidencia') ? '(SELECT id_delivery_evidencia FROM delivery_evidencia e WHERE e.id_delivery = dc.id_delivery ORDER BY e.fecha DESC LIMIT 1)' : '0';
    $q = $db->query("SELECT dc.id_delivery_cobro, dc.id_delivery, dc.monto, dc.fecha, p.nombre, p.total, u.nombre_usuario AS repartidor, $evidencia AS id_evidencia
      FROM delivery_cobro dc JOIN delivery_pedido p ON p.id_delivery = dc.id_delivery LEFT JOIN usuario u ON u.id_usuario = dc.id_usuario
      WHERE dc.medio_pago IN ('plataforma','transferencia','tarjeta','prepago','otro') AND dc.en_custodia_repartidor IS FALSE AND dc.estado <> 'verificada' AND dc.id_venta IS NOT NULL
      AND NOT EXISTS (SELECT 1 FROM delivery_liquidacion l WHERE l.id_delivery_cobro = dc.id_delivery_cobro)
      ORDER BY dc.fecha LIMIT 200");
    return $q ? $q->result_array() : array();
  }

  /** Confirma manualmente la liquidación externa; no ingresa netos a efectivo físico ni duplica la venta. */
  public function confirmarLiquidacionPlataforma($idUsuario, $idCobro, $p)
  {
    $this->load->helper('tenant'); $db = get_tenant_db($this);
    $accion = isset($p['accion_id']) ? (string)$p['accion_id'] : '';
    if (!preg_match('/^[a-f0-9-]{16,64}$/i', $accion)) throw new Exception('Identificador de liquidación inválido.', 400);
    $campos = array('monto_neto','cargo_plataforma','otras_retenciones'); $val = array();
    foreach ($campos as $k) {
      $x = isset($p[$k]) && is_scalar($p[$k]) ? (string)$p[$k] : '';
      if (!preg_match('/^\d+$/', $x)) throw new Exception('Los montos de liquidación deben ser enteros no negativos.', 400);
      $val[$k] = (int)$x;
    }
    $ref = mb_substr(trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string)($p['referencia'] ?? ''))), 0, 120);
    if ($ref === '') throw new Exception('Indica la referencia del depósito o liquidación.', 400);
    $db->trans_begin();
    try {
      $prev = $db->query('SELECT id_delivery_cobro FROM delivery_liquidacion WHERE accion_id = ?', array($accion))->row();
      if ($prev) {
        if ((int)$prev->id_delivery_cobro !== (int)$idCobro) throw new Exception('La clave de liquidación ya fue utilizada.', 409);
        $db->trans_commit(); return array('ok' => true, 'repetida' => true);
      }
      $c = $db->query('SELECT dc.*, p.total, p.id_delivery, p.estado_entrega FROM delivery_cobro dc JOIN delivery_pedido p ON p.id_delivery = dc.id_delivery WHERE dc.id_delivery_cobro = ? FOR UPDATE OF dc, p', array((int)$idCobro))->row();
      if (!$c || !in_array($c->medio_pago, array('plataforma','transferencia','tarjeta','prepago','otro'), true) || $c->en_custodia_repartidor === 't' || $c->en_custodia_repartidor === true || !(int)$c->id_venta) throw new Exception('No existe un cobro electrónico registrado y vendido para confirmar.', 404);
      if ($db->query('SELECT 1 FROM delivery_liquidacion WHERE id_delivery_cobro = ?', array((int)$idCobro))->row()) throw new Exception('Esta liquidación ya fue confirmada.', 409);
      $bruto = (int)$c->monto;
      if ($val['monto_neto'] + $val['cargo_plataforma'] + $val['otras_retenciones'] !== $bruto) throw new Exception('Neto recibido + cargos + otras retenciones debe cuadrar con el cobro bruto (' . $bruto . ').', 400);
      $db->query('INSERT INTO delivery_liquidacion (id_delivery_cobro, accion_id, monto_bruto, monto_neto, cargo_plataforma, otras_retenciones, referencia, id_usuario) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        array((int)$idCobro, $accion, $bruto, $val['monto_neto'], $val['cargo_plataforma'], $val['otras_retenciones'], $ref, (int)$idUsuario));
      $db->query("UPDATE delivery_cobro SET estado = 'verificada' WHERE id_delivery_cobro = ?", array((int)$idCobro));
      $db->query("UPDATE delivery_pedido SET estado_pago = 'verificado' WHERE id_delivery = ? AND NOT EXISTS (SELECT 1 FROM delivery_cobro WHERE id_delivery = ? AND estado <> 'verificada')", array((int)$c->id_delivery, (int)$c->id_delivery));
      if ($db->table_exists('delivery_evento')) $db->query('INSERT INTO delivery_evento (id_delivery, id_usuario, accion, datos) VALUES (?, ?, ?, ?::jsonb)',
        array((int)$c->id_delivery, (int)$idUsuario, 'pago_electronico_confirmado', json_encode(array('medio_pago' => $c->medio_pago, 'monto_bruto' => $bruto, 'monto_neto' => $val['monto_neto'], 'cargo_plataforma' => $val['cargo_plataforma'], 'otras_retenciones' => $val['otras_retenciones'], 'referencia' => $ref), JSON_UNESCAPED_UNICODE)));
      if ($db->trans_status() === false) throw new Exception('No se pudo confirmar la liquidación.', 500);
      $db->trans_commit();
    } catch (Throwable $e) { $db->trans_rollback(); throw $e; }
    $this->capturarComision((int)$c->id_delivery);
    return array('ok' => true, 'monto_bruto' => $bruto, 'monto_neto' => $val['monto_neto']);
  }

  public function pagarComision($caja, $idUsuario, $idComision, $accionId, $referencia = '')
  {
    $this->load->helper('tenant'); $db = get_tenant_db($this);
    if (!preg_match('/^[a-f0-9-]{16,64}$/i', (string)$accionId)) throw new Exception('Identificador de pago inválido.', 400);
    $referencia = mb_substr(trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string)$referencia)), 0, 120);
    $db->trans_begin();
    try {
      $cajaDb = $db->query('SELECT cerrada FROM caja WHERE id_caja = ? FOR UPDATE', array((int)$caja->id_caja))->row();
      if (!$cajaDb || $cajaDb->cerrada === 't' || $cajaDb->cerrada === true) throw new Exception('La caja ya no está abierta.', 409);
      $c = $db->query('SELECT * FROM delivery_comision WHERE id_delivery_comision = ? FOR UPDATE', array((int)$idComision))->row();
      if (!$c) throw new Exception('La comisión no existe.', 404);
      if ($c->accion_id === (string)$accionId && $c->estado === 'pagada') { $db->trans_commit(); return array('ok' => true, 'repetida' => true); }
      if ($c->estado !== 'pendiente') throw new Exception('La comisión ya fue pagada.', 409);
      if ((int)$c->id_repartidor === (int)$idUsuario) throw new Exception('No puedes registrar el pago de tu propia comisión.', 403);
      $db->query("UPDATE delivery_comision SET estado = 'pagada', id_caja = ?, id_pagador = ?, medio_pago = 'efectivo', referencia = NULLIF(?, ''), accion_id = ?, fecha_pago = now() WHERE id_delivery_comision = ? AND estado = 'pendiente'",
        array((int)$caja->id_caja, (int)$idUsuario, $referencia, (string)$accionId, (int)$idComision));
      if ($db->affected_rows() !== 1) throw new Exception('La comisión ya fue pagada en otra operación.', 409);
      $fila = array('id_caja' => (int)$caja->id_caja, 'ingreso' => 0, 'egreso' => (int)$c->monto,
        'observacion' => 'Comisión Delivery N° ' . (int)$c->id_delivery_comision . ' · ' . (string)$referencia,
        'descripcion_corta' => 'Comisión Delivery', 'id_ccosto' => 0, 'fecha_movimiento' => date('Y-m-d H:i:s'), 'vigente' => true);
      $cols = array_flip($db->list_fields('detalle_caja')); $db->insert('detalle_caja', array_intersect_key($fila, $cols));
      if ($db->affected_rows() !== 1) throw new Exception('No se registró el egreso de comisión en caja.', 500);
      $db->query('UPDATE caja SET ingresos = (SELECT COALESCE(sum(ingreso), 0) FROM detalle_caja WHERE id_caja = ?), egresos = (SELECT COALESCE(sum(egreso), 0) FROM detalle_caja WHERE id_caja = ?) WHERE id_caja = ?', array((int)$caja->id_caja, (int)$caja->id_caja, (int)$caja->id_caja));
      if ($db->table_exists('delivery_evento')) $db->query('INSERT INTO delivery_evento (id_delivery, id_usuario, accion, datos) VALUES (?, ?, ?, ?::jsonb)',
        array((int)$c->id_delivery, (int)$idUsuario, 'comision_pagada', json_encode(array('id_comision' => (int)$idComision, 'monto' => (int)$c->monto, 'id_caja' => (int)$caja->id_caja, 'referencia' => $referencia), JSON_UNESCAPED_UNICODE)));
      if ($db->trans_status() === false) throw new Exception('No se pudo registrar el pago de comisión.', 500);
      $db->trans_commit();
    } catch (Throwable $e) { $db->trans_rollback(); throw $e; }
    return array('ok' => true, 'monto' => (int)$c->monto);
  }

  function __construct()
  {
    parent::__construct();
  }

  /** Pedidos asignados al usuario conectado; el portal nunca consulta pedidos ajenos. */
  public function misPedidos($idUsuario)
  {
    $this->load->helper('tenant'); $db = get_tenant_db($this);
    if (!$db->table_exists('delivery_pedido') || !$db->field_exists('estado_entrega', 'delivery_pedido')) return array();
    $comandaSql = $db->field_exists('comanda_snapshot', 'delivery_pedido') ? 'd.comanda_snapshot' : "'[]'::jsonb AS comanda_snapshot";
    $pagoSql = $db->field_exists('medio_pago_previsto', 'delivery_pedido') ? 'd.medio_pago_previsto' : "'' AS medio_pago_previsto";
    $estadoPagoSql = $db->field_exists('estado_pago', 'delivery_pedido') ? 'd.estado_pago' : "'pendiente' AS estado_pago";
    $q = $db->query("SELECT d.id_delivery, d.nombre, d.telefono, d.direccion, d.referencia, d.observacion, d.total, d.id_venta,
        d.estado_entrega, $estadoPagoSql, d.estado, d.fecha, d.asignado_en, $pagoSql, $comandaSql
      FROM delivery_pedido d WHERE d.id_repartidor = ? AND d.estado_entrega NOT IN ('entregado','cancelado')
      ORDER BY d.fecha ASC LIMIT 100", array((int)$idUsuario));
    if (!$q) return array();
    $pedidos = $q->result_array();
    foreach ($pedidos as &$p) {
      $p['items'] = array();
      $p['id_venta'] = (int)$p['id_venta']; $p['total'] = (int)$p['total'];
      foreach ((array)json_decode((string)$p['comanda_snapshot'], true) as $item) {
        if (!is_array($item)) continue;
        $p['items'][] = array('nombre' => (string)(isset($item['nombre']) ? $item['nombre'] : ''),
          'cantidad' => (float)(isset($item['cantidad']) ? $item['cantidad'] : 0),
          'agregados' => array_values((array)(isset($item['agregados']) ? $item['agregados'] : array())),
          'nota' => (string)(isset($item['nota']) ? $item['nota'] : ''));
      }
      unset($p['comanda_snapshot']);
    }
    unset($p);
    return $pedidos;
  }

  /** Acepta/rechaza una asignación o confirma el retiro. Cada transición se vuelve a validar bajo bloqueo. */
  public function accionRepartidor($idUsuario, $idDelivery, $accion, $motivo = '')
  {
    $this->load->helper('tenant'); $db = get_tenant_db($this);
    if (!$db->table_exists('delivery_pedido') || !$db->field_exists('estado_entrega', 'delivery_pedido')) throw new Exception('Falta actualizar la base con la migración del portal de repartidores.', 409);
    if (!in_array($accion, array('aceptar', 'rechazar', 'retirar'), true)) throw new Exception('Acción inválida.', 400);
    $idDelivery = (int)$idDelivery;
    $db->trans_begin();
    try {
      $d = $db->query('SELECT id_delivery, estado, estado_entrega, id_repartidor FROM delivery_pedido WHERE id_delivery = ? FOR UPDATE', array($idDelivery))->row();
      if (!$d || (int)$d->id_repartidor !== (int)$idUsuario) throw new Exception('El pedido no está asignado a tu usuario.', 404);
      if ($accion === 'aceptar' || $accion === 'rechazar') {
        if ($d->estado_entrega !== 'asignado') throw new Exception('La asignación ya fue respondida o cambió.', 409);
        if ($accion === 'rechazar') {
          $motivo = mb_substr(trim(preg_replace('/[\\x00-\\x1F\\x7F]+/u', ' ', (string)$motivo)), 0, 200);
          if ($motivo === '') throw new Exception('Indica el motivo del rechazo.', 400);
          $db->query("UPDATE delivery_pedido SET id_repartidor = NULL, estado_entrega = 'sin_asignar', respondido_en = now(), motivo_rechazo = ? WHERE id_delivery = ?", array($motivo, $idDelivery));
        } else {
          $db->query("UPDATE delivery_pedido SET estado_entrega = 'aceptado', respondido_en = now(), motivo_rechazo = NULL WHERE id_delivery = ?", array($idDelivery));
        }
      } elseif ($accion === 'retirar') {
        if ($d->estado_entrega !== 'aceptado' || in_array($d->estado, array('entregado', 'anulado'), true)) throw new Exception('Solo puedes confirmar el retiro después de aceptar el pedido.', 409);
        $db->query("UPDATE delivery_pedido SET estado_entrega = 'retirado', fecha_retiro = now(), estado = 'en_camino', fecha_estado = now() WHERE id_delivery = ?", array($idDelivery));
      } else {
        throw new Exception('Usa el formulario de entrega para informar el resultado y el cobro.', 400);
      }
      if ($db->table_exists('delivery_evento')) $db->query('INSERT INTO delivery_evento (id_delivery, id_usuario, accion, datos) VALUES (?, ?, ?, ?::jsonb)',
        array($idDelivery, (int)$idUsuario, 'repartidor_' . $accion, json_encode(array('motivo' => $motivo), JSON_UNESCAPED_UNICODE)));
      if ($db->trans_status() === false) throw new Exception('No se pudo guardar la acción.', 500);
      $db->trans_commit();
    } catch (Throwable $e) { $db->trans_rollback(); throw $e; }
    return array('ok' => true);
  }

  /** Registra una declaración de entrega/cobro del propio repartidor; no ingresa dinero a la caja. */
  public function declararEntrega($idUsuario, $idDelivery, $p)
  {
    $this->load->helper('tenant'); $db = get_tenant_db($this);
    if (!$db->table_exists('delivery_cobro')) throw new Exception('Falta actualizar la base con la migración de cobros Delivery.', 409);
    $idDelivery = (int)$idDelivery;
    $accionId = trim(isset($p['accion_id']) && is_scalar($p['accion_id']) ? (string)$p['accion_id'] : '');
    if (!preg_match('/^[a-f0-9-]{16,64}$/i', $accionId)) throw new Exception('Identificador de acción inválido.', 400);
    $resultado = isset($p['resultado']) && is_scalar($p['resultado']) ? (string)$p['resultado'] : '';
    if (!in_array($resultado, array('entregado', 'no_entregado'), true)) throw new Exception('Resultado de entrega no válido.', 400);
    $medio = isset($p['medio_pago']) && is_scalar($p['medio_pago']) ? (string)$p['medio_pago'] : 'no_pagado';
    $montoRaw = isset($p['monto']) && is_scalar($p['monto']) ? (string)$p['monto'] : '';
    if (!preg_match('/^\d+$/', $montoRaw)) throw new Exception('El monto debe ser un número entero no negativo.', 400);
    $monto = (int)$montoRaw;
    $referencia = mb_substr(trim(preg_replace('/[\\x00-\\x1F\\x7F]+/u', ' ', (string)(isset($p['referencia']) ? $p['referencia'] : ''))), 0, 120);
    $motivo = mb_substr(trim(preg_replace('/[\\x00-\\x1F\\x7F]+/u', ' ', (string)(isset($p['motivo']) ? $p['motivo'] : ''))), 0, 200);
    $medios = array('efectivo', 'transferencia', 'tarjeta', 'prepago', 'plataforma', 'otro', 'no_pagado');
    if (!in_array($medio, $medios, true)) throw new Exception('Medio de pago no válido.', 400);
    if ($resultado === 'no_entregado' && $motivo === '') throw new Exception('Indica por qué no se pudo entregar.', 400);
    if ($resultado === 'no_entregado' && $monto > 0) throw new Exception('Una entrega fallida no puede declarar un cobro.', 400);
    if ($monto > 0 && $medio === 'no_pagado') throw new Exception('Selecciona el medio de pago recibido.', 400);
    if ($monto === 0 && $medio !== 'no_pagado') throw new Exception('El monto recibido debe ser mayor a cero.', 400);
    $idEvidencia = (int)(isset($p['id_evidencia']) ? $p['id_evidencia'] : 0);
    if ($monto > 0 && in_array($medio, array('transferencia','tarjeta','prepago','plataforma','otro'), true)) {
      if ($idEvidencia <= 0 || !$db->table_exists('delivery_evidencia') || !$db->query('SELECT 1 FROM delivery_evidencia WHERE id_delivery_evidencia = ? AND id_delivery = ? AND id_usuario = ?', array($idEvidencia, $idDelivery, (int)$idUsuario))->row()) {
        throw new Exception('Adjunta el comprobante del pago electrónico antes de confirmar la entrega.', 400);
      }
    }

    $db->trans_begin();
    try {
      $anterior = $db->query('SELECT id_delivery, id_usuario FROM delivery_cobro WHERE accion_id = ?', array($accionId))->row();
      if ($anterior) {
        if ((int)$anterior->id_delivery !== $idDelivery || (int)$anterior->id_usuario !== (int)$idUsuario) throw new Exception('La acción ya fue utilizada.', 409);
        $db->trans_commit();
        return array('ok' => true, 'repetido' => true);
      }
      $d = $db->query('SELECT id_delivery, total, estado_entrega, estado_pago, id_repartidor, id_venta FROM delivery_pedido WHERE id_delivery = ? FOR UPDATE', array($idDelivery))->row();
      if (!$d || (int)$d->id_repartidor !== (int)$idUsuario) throw new Exception('El pedido no está asignado a tu usuario.', 404);
      if ($d->estado_entrega !== 'retirado') throw new Exception('El pedido debe estar retirado antes de registrar su entrega.', 409);
      if ((int)$d->id_venta > 0 && $monto > 0) throw new Exception('El pedido ya tiene una venta pagada. No cobres otra vez al cliente.', 409);
      if ($monto > 0 && $monto !== (int)$d->total) throw new Exception('En esta versión el cobro debe cubrir el total exacto del pedido.', 400);
      if ((int)$d->id_venta > 0 && $medio !== 'no_pagado') throw new Exception('El pedido ya está pagado en Caja.', 409);
      $custodia = $medio === 'efectivo' && $monto > 0;
      $estadoPago = (int)$d->id_venta > 0 ? 'verificado' : ($monto > 0 ? 'declarado' : 'pendiente');
      $db->query("UPDATE delivery_pedido SET estado_entrega = ?, resultado_entrega = ?, estado_pago = ?,
          fecha_entrega = CASE WHEN ? = 'entregado' THEN now() ELSE fecha_entrega END WHERE id_delivery = ?",
        array($resultado, $resultado, $estadoPago, $resultado, $idDelivery));
      $db->query("INSERT INTO delivery_cobro (id_delivery, accion_id, id_usuario, medio_pago, monto, en_custodia_repartidor, referencia, estado)
        VALUES (?, ?, ?, ?, ?, ?, NULLIF(?, ''), CASE WHEN ? > 0 THEN 'pendiente_registro' ELSE 'verificada' END)",
        array($idDelivery, $accionId, (int)$idUsuario, $medio, $monto, $custodia ? 't' : 'f', $referencia, $monto));
      if ($db->table_exists('delivery_evento')) $db->query('INSERT INTO delivery_evento (id_delivery, id_usuario, accion, datos) VALUES (?, ?, ?, ?::jsonb)',
        array($idDelivery, (int)$idUsuario, $resultado === 'entregado' ? 'entrega_declarada' : 'entrega_fallida', json_encode(array(
          'medio_pago' => $medio, 'monto' => $monto, 'custodia_repartidor' => $custodia, 'motivo' => $motivo, 'referencia' => $referencia,
          'id_evidencia' => $idEvidencia ?: null), JSON_UNESCAPED_UNICODE)));
      if ($db->trans_status() === false) throw new Exception('No se pudo registrar la entrega.', 500);
      $db->trans_commit();
    } catch (Throwable $e) {
      $db->trans_rollback();
      $repetido = $db->query('SELECT id_delivery, id_usuario FROM delivery_cobro WHERE accion_id = ?', array($accionId))->row();
      if ($repetido && (int)$repetido->id_delivery === $idDelivery && (int)$repetido->id_usuario === (int)$idUsuario) return array('ok' => true, 'repetido' => true);
      throw $e;
    }
    $this->capturarComision($idDelivery);
    return array('ok' => true, 'pendiente_registro' => $monto > 0);
  }

  private function cobrosRendibles($db, $idUsuario, $bloquear = false)
  {
    $q = "SELECT dc.id_delivery_cobro, dc.id_delivery, dc.monto, dc.fecha, p.nombre,
        dc.monto - COALESCE((SELECT sum(rd.monto_recibido) FROM delivery_rendicion_detalle rd
          JOIN delivery_rendicion r ON r.id_delivery_rendicion = rd.id_delivery_rendicion
          WHERE rd.id_delivery_cobro = dc.id_delivery_cobro), 0)
        - COALESCE((SELECT sum(rd.monto_ajustado) FROM delivery_rendicion_detalle rd
          JOIN delivery_rendicion r ON r.id_delivery_rendicion = rd.id_delivery_rendicion
          WHERE rd.id_delivery_cobro = dc.id_delivery_cobro), 0)
        - COALESCE((SELECT sum(rd.monto_declarado) FROM delivery_rendicion_detalle rd
          JOIN delivery_rendicion r ON r.id_delivery_rendicion = rd.id_delivery_rendicion
          WHERE rd.id_delivery_cobro = dc.id_delivery_cobro AND r.estado = 'presentada'), 0) AS disponible
      FROM delivery_cobro dc JOIN delivery_pedido p ON p.id_delivery = dc.id_delivery
      WHERE dc.id_usuario = ? AND dc.en_custodia_repartidor IS TRUE AND dc.monto > 0
      ORDER BY dc.fecha, dc.id_delivery_cobro" . ($bloquear ? ' FOR UPDATE OF dc' : '');
    $res = $db->query($q, array((int)$idUsuario));
    return $res ? $res->result_array() : array();
  }

  public function saldoRendicion($idUsuario)
  {
    $this->load->helper('tenant'); $db = get_tenant_db($this);
    if (!$db->table_exists('delivery_rendicion')) return array('saldo' => 0, 'cobros' => array(), 'rendiciones' => array());
    $cobros = array(); $saldo = 0;
    foreach ($this->cobrosRendibles($db, $idUsuario) as $r) {
      $disponible = max(0, (int)$r['disponible']);
      if (!$disponible) continue;
      $saldo += $disponible;
      $cobros[] = array('id_delivery' => (int)$r['id_delivery'], 'nombre' => (string)$r['nombre'], 'disponible' => $disponible,
        'fecha' => (string)$r['fecha']);
    }
    $hist = $db->query("SELECT id_delivery_rendicion, estado, monto_esperado, monto_declarado, monto_contado, diferencia, fecha_presentada, fecha_verificada
      FROM delivery_rendicion WHERE id_repartidor = ? ORDER BY id_delivery_rendicion DESC LIMIT 30", array((int)$idUsuario));
    $rendiciones = $hist ? $hist->result_array() : array();
    return array('saldo' => $saldo, 'cobros' => $cobros, 'rendiciones' => $rendiciones);
  }

  public function rendicionesPendientes()
  {
    $this->load->helper('tenant'); $db = get_tenant_db($this);
    if (!$db->table_exists('delivery_rendicion')) return array();
    $q = $db->query("SELECT r.id_delivery_rendicion, r.id_repartidor, u.nombre_usuario AS repartidor, r.estado, r.diferencia_resuelta, r.diferencia,
		r.monto_esperado, r.monto_declarado, r.monto_contado, r.fecha_presentada, r.fecha_verificada, r.observacion, r.resolucion, r.nota_resolucion,
        COALESCE((SELECT string_agg('N° ' || p.id_delivery::text || ' $' || rd.monto_declarado::text, ', ' ORDER BY rd.id_delivery_rendicion_detalle)
          FROM delivery_rendicion_detalle rd JOIN delivery_cobro dc ON dc.id_delivery_cobro = rd.id_delivery_cobro
          JOIN delivery_pedido p ON p.id_delivery = dc.id_delivery WHERE rd.id_delivery_rendicion = r.id_delivery_rendicion), '') AS detalle
	  FROM delivery_rendicion r LEFT JOIN usuario u ON u.id_usuario = r.id_repartidor
	  WHERE r.estado = 'presentada' OR (r.estado = 'diferencia' AND r.diferencia_resuelta IS NOT TRUE) OR r.fecha_verificada > now() - interval '7 days'
	  ORDER BY (r.estado = 'presentada' OR (r.estado = 'diferencia' AND r.diferencia_resuelta IS NOT TRUE)) DESC, r.fecha_presentada DESC LIMIT 200");
    return $q ? $q->result_array() : array();
  }

  /** Declara una rendición parcial y reserva cobros para evitar presentarlos dos veces a la vez. */
  public function presentarRendicion($idUsuario, $monto, $accionId, $observacion = '')
  {
    $this->load->helper('tenant'); $db = get_tenant_db($this);
    if (!$db->table_exists('delivery_rendicion')) throw new Exception('Falta actualizar la base con la migración de rendiciones Delivery.', 409);
    $monto = (int)$monto; $accionId = trim((string)$accionId);
    if ($monto <= 0 || !preg_match('/^[a-f0-9-]{16,64}$/i', $accionId)) throw new Exception('Monto o identificador de rendición inválido.', 400);
    $observacion = mb_substr(trim(preg_replace('/[\\x00-\\x1F\\x7F]+/u', ' ', (string)$observacion)), 0, 250);
    $db->trans_begin();
    try {
      $prev = $db->query('SELECT id_delivery_rendicion, id_repartidor FROM delivery_rendicion WHERE accion_id = ?', array($accionId))->row();
      if ($prev) {
        if ((int)$prev->id_repartidor !== (int)$idUsuario) throw new Exception('La clave de rendición ya fue utilizada.', 409);
        $db->trans_commit();
        return array('ok' => true, 'id_rendicion' => (int)$prev->id_delivery_rendicion, 'repetida' => true);
      }
      $filas = $this->cobrosRendibles($db, $idUsuario, true);
      $saldo = 0;
      foreach ($filas as $r) $saldo += max(0, (int)$r['disponible']);
      if ($monto > $saldo) throw new Exception('El monto supera tu saldo pendiente de rendición ($' . number_format($saldo, 0, ',', '.') . ').', 400);
      $r = $db->query("INSERT INTO delivery_rendicion (accion_id, id_repartidor, estado, monto_esperado, monto_declarado, observacion)
        VALUES (?, ?, 'presentada', ?, ?, NULLIF(?, '')) RETURNING id_delivery_rendicion", array($accionId, (int)$idUsuario, $saldo, $monto, $observacion))->row();
      if (!$r) throw new Exception('No se pudo crear la rendición.', 500);
      $idRendicion = (int)$r->id_delivery_rendicion; $restante = $monto;
      foreach ($filas as $fila) {
        if ($restante <= 0) break;
        $disponible = max(0, (int)$fila['disponible']);
        if (!$disponible) continue;
        $asignado = min($restante, $disponible);
        $db->query('INSERT INTO delivery_rendicion_detalle (id_delivery_rendicion, id_delivery_cobro, monto_declarado) VALUES (?, ?, ?)',
          array($idRendicion, (int)$fila['id_delivery_cobro'], $asignado));
        $restante -= $asignado;
      }
      if ($restante !== 0 || $db->trans_status() === false) throw new Exception('No se pudieron reservar los cobros para esta rendición.', 409);
      if ($db->table_exists('delivery_evento')) $db->query("INSERT INTO delivery_evento (id_delivery, id_usuario, accion, datos)
        SELECT dc.id_delivery, ?, 'rendicion_presentada', json_build_object('id_rendicion', ?, 'monto', rd.monto_declarado)::jsonb
        FROM delivery_rendicion_detalle rd JOIN delivery_cobro dc ON dc.id_delivery_cobro = rd.id_delivery_cobro WHERE rd.id_delivery_rendicion = ?",
        array((int)$idUsuario, $idRendicion, $idRendicion));
      $db->trans_commit();
    } catch (Throwable $e) {
      $db->trans_rollback();
      $prev = $db->query('SELECT id_delivery_rendicion, id_repartidor FROM delivery_rendicion WHERE accion_id = ?', array($accionId))->row();
      if ($prev && (int)$prev->id_repartidor === (int)$idUsuario) return array('ok' => true, 'id_rendicion' => (int)$prev->id_delivery_rendicion, 'repetida' => true);
      throw $e;
    }
    return array('ok' => true, 'id_rendicion' => $idRendicion, 'monto_declarado' => $monto, 'estado' => 'presentada');
  }

  /** Recibe efectivo en una caja abierta; la declaración del repartidor y el monto contado quedan separados. */
  public function verificarRendicion($caja, $idVerificador, $idRendicion, $contado, $observacion = '')
  {
    $this->load->helper('tenant'); $db = get_tenant_db($this);
    $idRendicion = (int)$idRendicion; $contado = (int)$contado;
    if ($idRendicion <= 0 || $contado < 0) throw new Exception('Datos de verificación inválidos.', 400);
    $observacion = mb_substr(trim(preg_replace('/[\\x00-\\x1F\\x7F]+/u', ' ', (string)$observacion)), 0, 250);
    $db->trans_begin();
    try {
      $c = $db->query('SELECT cerrada FROM caja WHERE id_caja = ? FOR UPDATE', array((int)$caja->id_caja))->row();
      if (!$c || $c->cerrada === 't' || $c->cerrada === true) throw new Exception('La caja ya no está abierta.', 409);
      $r = $db->query('SELECT * FROM delivery_rendicion WHERE id_delivery_rendicion = ? FOR UPDATE', array($idRendicion))->row();
      if (!$r) throw new Exception('La rendición no existe.', 404);
      if ($r->estado !== 'presentada') {
        if ((int)$r->id_verificador === (int)$idVerificador && (int)$r->id_caja === (int)$caja->id_caja && (int)$r->monto_contado === $contado)
          { $db->trans_commit(); return array('ok' => true, 'repetida' => true, 'estado' => $r->estado, 'diferencia' => (int)$r->diferencia); }
        throw new Exception('Esta rendición ya fue verificada o tiene una diferencia pendiente.', 409);
      }
      if ((int)$r->id_repartidor === (int)$idVerificador) throw new Exception('El repartidor no puede verificar su propia rendición.', 403);
      $det = $db->query('SELECT id_delivery_rendicion_detalle, id_delivery_cobro, monto_declarado FROM delivery_rendicion_detalle WHERE id_delivery_rendicion = ? ORDER BY id_delivery_rendicion_detalle FOR UPDATE', array($idRendicion))->result();
      $restante = $contado; $cobros = array();
      foreach ($det as $d) {
        $recibido = min($restante, (int)$d->monto_declarado); $restante -= $recibido;
        $db->query('UPDATE delivery_rendicion_detalle SET monto_recibido = ? WHERE id_delivery_rendicion_detalle = ?', array($recibido, (int)$d->id_delivery_rendicion_detalle));
        $cobros[] = (int)$d->id_delivery_cobro;
      }
      $diferencia = $contado - (int)$r->monto_declarado;
      $estado = $diferencia === 0 ? 'verificada' : 'diferencia';
      $db->query('UPDATE delivery_rendicion SET estado = ?, id_caja = ?, monto_contado = ?, diferencia = ?, id_verificador = ?, fecha_verificada = now(), observacion = COALESCE(NULLIF(?, \'\'), observacion) WHERE id_delivery_rendicion = ?',
        array($estado, (int)$caja->id_caja, $contado, $diferencia, (int)$idVerificador, $observacion, $idRendicion));
      if ($contado > 0) {
        $fila = array('id_caja' => (int)$caja->id_caja, 'ingreso' => $contado, 'egreso' => 0, 'observacion' => 'Rendición Delivery N° ' . $idRendicion,
          'descripcion_corta' => 'Rendición Delivery', 'id_ccosto' => 0, 'fecha_movimiento' => date('Y-m-d H:i:s'), 'vigente' => true);
        $cols = array_flip($db->list_fields('detalle_caja'));
        $db->insert('detalle_caja', array_intersect_key($fila, $cols));
        if ($db->affected_rows() !== 1) throw new Exception('No se registró el ingreso de la rendición en caja.', 500);
        $db->query('UPDATE caja SET ingresos = (SELECT COALESCE(sum(ingreso), 0) FROM detalle_caja WHERE id_caja = ?), egresos = (SELECT COALESCE(sum(egreso), 0) FROM detalle_caja WHERE id_caja = ?) WHERE id_caja = ?',
          array((int)$caja->id_caja, (int)$caja->id_caja, (int)$caja->id_caja));
      }
      foreach (array_unique($cobros) as $idCobro) {
        $cobro = $db->query('SELECT id_delivery, monto FROM delivery_cobro WHERE id_delivery_cobro = ? FOR UPDATE', array($idCobro))->row();
        $recibidoCobro = $db->query('SELECT COALESCE(sum(monto_recibido), 0) AS m FROM delivery_rendicion_detalle WHERE id_delivery_cobro = ?', array($idCobro))->row();
        if ($cobro && (int)$recibidoCobro->m >= (int)$cobro->monto) {
          $db->query("UPDATE delivery_cobro SET estado = 'verificada' WHERE id_delivery_cobro = ?", array($idCobro));
          $pend = $db->query("SELECT count(*) AS n FROM delivery_cobro WHERE id_delivery = ? AND en_custodia_repartidor IS TRUE AND estado <> 'verificada'", array((int)$cobro->id_delivery))->row();
          if ($pend && (int)$pend->n === 0) {
            $db->query("UPDATE delivery_pedido SET estado_pago = 'verificado' WHERE id_delivery = ?", array((int)$cobro->id_delivery));
            $this->capturarComision((int)$cobro->id_delivery);
          }
        }
      }
      if ($db->table_exists('delivery_evento')) foreach (array_unique($cobros) as $idCobro) {
        $ev = $db->query('SELECT id_delivery FROM delivery_cobro WHERE id_delivery_cobro = ?', array($idCobro))->row();
        if ($ev) $db->query('INSERT INTO delivery_evento (id_delivery, id_usuario, accion, datos) VALUES (?, ?, ?, ?::jsonb)',
          array((int)$ev->id_delivery, (int)$idVerificador, $estado === 'verificada' ? 'rendicion_verificada' : 'rendicion_diferencia', json_encode(array(
            'id_rendicion' => $idRendicion, 'declarado' => (int)$r->monto_declarado, 'contado' => $contado, 'diferencia' => $diferencia, 'id_caja' => (int)$caja->id_caja), JSON_UNESCAPED_UNICODE)));
      }
      if ($db->trans_status() === false) throw new Exception('No se pudo verificar la rendición.', 500);
      $db->trans_commit();
    } catch (Throwable $e) { $db->trans_rollback(); throw $e; }
    return array('ok' => true, 'estado' => $estado, 'diferencia' => $diferencia, 'monto_contado' => $contado);
  }

  /** Resuelve una diferencia conservando el importe original y los movimientos de recepción. */
  public function resolverDiferencia($caja, $idUsuario, $idRendicion, $accion, $observacion = '')
  {
    $this->load->helper('tenant'); $db = get_tenant_db($this);
    if (!in_array($accion, array('deuda_reconocida', 'faltante_cobrado', 'ajuste_autorizado', 'excedente_confirmado'), true)) throw new Exception('Resolución no válida.', 400);
    $observacion = mb_substr(trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string)$observacion)), 0, 250);
    if ($observacion === '') throw new Exception('Indica el motivo de la resolución.', 400);
    $db->trans_begin();
    try {
      $c = $db->query('SELECT cerrada FROM caja WHERE id_caja = ? FOR UPDATE', array((int)$caja->id_caja))->row();
      if (!$c || $c->cerrada === 't' || $c->cerrada === true) throw new Exception('La caja ya no está abierta.', 409);
      $r = $db->query('SELECT * FROM delivery_rendicion WHERE id_delivery_rendicion = ? FOR UPDATE', array((int)$idRendicion))->row();
      if (!$r || $r->estado !== 'diferencia') throw new Exception('La diferencia no existe.', 409);
      if ($r->diferencia_resuelta === 't' || $r->diferencia_resuelta === true) {
        if ((string)$r->resolucion === $accion && (int)$r->resuelta_por === (int)$idUsuario && (string)$r->nota_resolucion === $observacion) {
          $db->trans_commit(); return array('ok' => true, 'repetida' => true, 'accion' => $accion, 'diferencia' => (int)$r->diferencia);
        }
        throw new Exception('La diferencia ya fue resuelta.', 409);
      }
      if ((int)$r->id_repartidor === (int)$idUsuario) throw new Exception('El repartidor no puede resolver su propia diferencia.', 403);
      $delta = (int)$r->diferencia;
      if ($delta < 0 && $accion === 'excedente_confirmado' || $delta > 0 && in_array($accion, array('deuda_reconocida','faltante_cobrado','ajuste_autorizado'), true)) throw new Exception('La resolución no corresponde al signo de la diferencia.', 400);
      if ($delta === 0) throw new Exception('Esta rendición no tiene diferencia.', 409);
      if ($delta < 0 && $accion === 'faltante_cobrado') {
        $restante = abs($delta);
        $det = $db->query('SELECT id_delivery_rendicion_detalle, id_delivery_cobro, monto_declarado, monto_recibido FROM delivery_rendicion_detalle WHERE id_delivery_rendicion = ? ORDER BY id_delivery_rendicion_detalle FOR UPDATE', array((int)$idRendicion))->result();
        foreach ($det as $x) {
          $deuda = max(0, (int)$x->monto_declarado - (int)$x->monto_recibido);
          $abono = min($deuda, $restante); $restante -= $abono;
          if ($abono) $db->query('UPDATE delivery_rendicion_detalle SET monto_recibido = monto_recibido + ? WHERE id_delivery_rendicion_detalle = ?', array($abono, (int)$x->id_delivery_rendicion_detalle));
        }
        if ($restante > 0) throw new Exception('No se pudo asignar todo el faltante a los cobros originales.', 409);
        $fila = array('id_caja' => (int)$caja->id_caja, 'ingreso' => abs($delta), 'egreso' => 0, 'observacion' => 'Recuperación faltante rendición Delivery N° ' . (int)$idRendicion,
          'descripcion_corta' => 'Recuperación faltante Delivery', 'id_ccosto' => 0, 'fecha_movimiento' => date('Y-m-d H:i:s'), 'vigente' => true);
        $cols = array_flip($db->list_fields('detalle_caja')); $db->insert('detalle_caja', array_intersect_key($fila, $cols));
        if ($db->affected_rows() !== 1) throw new Exception('No se registró el ingreso del faltante en caja.', 500);
        $db->query('UPDATE caja SET ingresos = (SELECT COALESCE(sum(ingreso), 0) FROM detalle_caja WHERE id_caja = ?), egresos = (SELECT COALESCE(sum(egreso), 0) FROM detalle_caja WHERE id_caja = ?) WHERE id_caja = ?', array((int)$caja->id_caja, (int)$caja->id_caja, (int)$caja->id_caja));
      } elseif ($delta < 0 && $accion === 'ajuste_autorizado') {
        $restante = abs($delta);
        $det = $db->query('SELECT id_delivery_rendicion_detalle, monto_declarado, monto_recibido, monto_ajustado FROM delivery_rendicion_detalle WHERE id_delivery_rendicion = ? ORDER BY id_delivery_rendicion_detalle FOR UPDATE', array((int)$idRendicion))->result();
        foreach ($det as $x) {
          $deuda = max(0, (int)$x->monto_declarado - (int)$x->monto_recibido - (int)$x->monto_ajustado);
          $ajuste = min($deuda, $restante); $restante -= $ajuste;
          if ($ajuste) $db->query('UPDATE delivery_rendicion_detalle SET monto_ajustado = monto_ajustado + ? WHERE id_delivery_rendicion_detalle = ?', array($ajuste, (int)$x->id_delivery_rendicion_detalle));
        }
        if ($restante > 0) throw new Exception('No se pudo asignar todo el ajuste a los cobros originales.', 409);
      }
      $db->query('UPDATE delivery_rendicion SET diferencia_resuelta = true, resolucion = ?, nota_resolucion = ?, resuelta_por = ?, resuelta_en = now() WHERE id_delivery_rendicion = ?',
		array($accion, $observacion, (int)$idUsuario, (int)$idRendicion));
      if (in_array($accion, array('faltante_cobrado','ajuste_autorizado'), true)) {
        $db->query("UPDATE delivery_cobro dc SET estado = 'verificada'
          WHERE dc.id_delivery_cobro IN (SELECT id_delivery_cobro FROM delivery_rendicion_detalle WHERE id_delivery_rendicion = ?)
          AND dc.monto <= COALESCE((SELECT sum(rd.monto_recibido + rd.monto_ajustado) FROM delivery_rendicion_detalle rd WHERE rd.id_delivery_cobro = dc.id_delivery_cobro), 0)", array((int)$idRendicion));
        $db->query("UPDATE delivery_pedido p SET estado_pago = 'verificado'
          WHERE p.id_delivery IN (SELECT dc.id_delivery FROM delivery_rendicion_detalle rd JOIN delivery_cobro dc ON dc.id_delivery_cobro = rd.id_delivery_cobro WHERE rd.id_delivery_rendicion = ?)
          AND NOT EXISTS (SELECT 1 FROM delivery_cobro dc WHERE dc.id_delivery = p.id_delivery AND dc.en_custodia_repartidor IS TRUE AND dc.estado <> 'verificada')", array((int)$idRendicion));
        $ids = $db->query('SELECT DISTINCT dc.id_delivery FROM delivery_rendicion_detalle rd JOIN delivery_cobro dc ON dc.id_delivery_cobro = rd.id_delivery_cobro WHERE rd.id_delivery_rendicion = ?', array((int)$idRendicion))->result();
        foreach ($ids as $id) $this->capturarComision((int)$id->id_delivery);
      }
      if ($db->table_exists('delivery_evento')) {
        $evs = $db->query('SELECT DISTINCT p.id_delivery FROM delivery_rendicion_detalle rd JOIN delivery_cobro dc ON dc.id_delivery_cobro = rd.id_delivery_cobro JOIN delivery_pedido p ON p.id_delivery = dc.id_delivery WHERE rd.id_delivery_rendicion = ?', array((int)$idRendicion))->result();
        foreach ($evs as $ev) $db->query('INSERT INTO delivery_evento (id_delivery, id_usuario, accion, datos) VALUES (?, ?, ?, ?::jsonb)',
          array((int)$ev->id_delivery, (int)$idUsuario, 'rendicion_diferencia_resuelta', json_encode(array('id_rendicion' => (int)$idRendicion, 'accion' => $accion, 'diferencia_original' => $delta, 'observacion' => $observacion), JSON_UNESCAPED_UNICODE)));
      }
      if ($db->trans_status() === false) throw new Exception('No se pudo resolver la diferencia.', 500);
      $db->trans_commit();
    } catch (Throwable $e) { $db->trans_rollback(); throw $e; }
    return array('ok' => true, 'accion' => $accion, 'diferencia' => $delta);
  }

  public function ListadoDeSectores()
  {
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
    $q = "SELECT s.id_sector, s.nombre_sector from sector s order by s.id_sector asc";

    $consulta = $this->db->query($q);
    $sampleData = $consulta->result();
    $data = array();

    foreach ($sampleData as $key) {
      array_push($data, array(
        "id_sector" => $key->id_sector,
        "nombre_sector" => $key->nombre_sector,
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

  public function grabarNuevoSector($id, $data)
  {
		if (strlen((string)$this->session->userdata('nombre_usuario')) == 0) {
			redirect(base_url()); //redireccionar al login
		}
		$this->load->helper('tenant'); $this->db = get_tenant_db($this);
    if ($id) {
      $this->db->where('id_sector', $id);
      $this->db->update('sector', $data);
      // 16-11-2026 (QA): si no se actualizó ninguna fila el registro no existe: error (404) y no "éxito"
      if ($this->db->affected_rows() < 1) {
      	set_status_header(404);
      	return array('error' => 'El registro ya no existe (pudo haberlo borrado otra persona). Recarga la página.');
      }
      $consulta = $this->db->query("select " . (int)$id . " as id_sector");
    } else {
      $this->db->insert('sector', $data);
      $consulta = $this->db->query("SELECT CURRVAL('sector_id_sector_seq'::regclass) as id_sector");
    }
    return $consulta->result();
  }
}
