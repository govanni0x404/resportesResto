<div class="content-header">
    <div class="header-icon">
        <i class="pe-7s-config"></i>
    </div>
    <div class="header-title">
        <h1>Configuraciones Generales</h1>
        <small id="caja"></small>
        <ol class="breadcrumb">
            <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i>Mantencion</a></li>
            <li class="active">Configuraciones Generales</li>
        </ol>
    </div>
</div>

<section class="content">
        <input type="number" class="form-control" style="display: none;" name="id_rol" id="id_rol" value="0" min="0" oninput="validity.valid||(value='');">
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12 m-b-20">
            <div class="panel-body">

                <div class="panel panel-success lobidisable">
                    <div class="panel-heading">
                        <div class="panel-title">
                            <i class="pe-7s-config"></i>
                            <h4>Configuraciones del Sistema</h4>
                        </div>
                    </div>
                    <br>
                    <div class="panel-body">
                        <!-- Tabs -->
                        <div class="tabs">
                            <button class="tablink active" onclick="openTab(event, 'general')">General</button>
                            <button class="tablink" onclick="openTab(event, 'otras')">Otras Configuraciones</button>
                            <button class="tablink" onclick="openTab(event, 'facturacion')">Facturación Electrónica</button>
                            <button class="tablink" onclick="openTab(event, 'empresa')">Datos de la Empresa</button>
                        </div>

                        <form id="configForm" method="post" action="<?php echo site_url('ConfiguracionesGenerales/guardar'); ?>">
                            <!-- Tab General -->
                            <div id="general" class="tab active">
                                <div class="config-grid">
                                    <!-- Columna 1 -->
                                    <div class="config-col">
                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title">Ver en Caja</h3>
                                            </div>
                                            <div class="panel-body">
                                                <label><input type="checkbox" name="ver_delivery_caja" value="1" <?php echo ($config['ver_delivery_caja'] ?? true) ? 'checked' : ''; ?>> DELIVERY</label>
                                                <label><input type="checkbox" name="ver_barra_caja" value="1" <?php echo ($config['ver_barra_caja'] ?? true) ? 'checked' : ''; ?>> Venta Directa Caja</label>
                                                <label><input type="checkbox" name="mostraridpedido" value="1" <?php echo ($config['mostraridpedido'] ?? true) ? 'checked' : ''; ?>> Numero Pedido</label>
                                                <label><input type="checkbox" name="mostrar_nombre_cliente_venta" value="1" <?php echo ($config['mostrar_nombre_cliente_venta'] ?? true) ? 'checked' : ''; ?>> Nombre Cliente</label>
                                                <label><input type="checkbox" name="mostrar_nombre_garzon_venta" value="1" <?php echo ($config['mostrar_nombre_garzon_venta'] ?? true) ? 'checked' : ''; ?>> Garzon</label>
                                            </div>
                                        </div>

                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title">Precuentas</h3>
                                            </div>
                                            <div class="panel-body">
                                                <label><input type="checkbox" name="garzon_emite_precuenta" value="1" <?php echo ($config['garzon_emite_precuenta'] ?? false) ? 'checked' : ''; ?>> Garzon Puede emitir Precuenta</label>
                                                <label><input type="checkbox" name="usar_impresora_local_precuenta" value="1" <?php echo ($config['usar_impresora_local_precuenta'] ?? false) ? 'checked' : ''; ?>> Usar Impresora equipo local</label>
                                                <label><input type="checkbox" name="ocultar_cuenta_garzon" value="1" <?php echo ($config['ocultar_cuenta_garzon'] ?? true) ? 'checked' : ''; ?>> Ocultar monto de venta a garzon</label>
                                                <label><input type="checkbox" name="omitir_productos_sin_precio_precuenta" value="1" <?php echo ($config['omitir_productos_sin_precio_precuenta'] ?? false) ? 'checked' : ''; ?>> No imprimir productos sin valor</label>
                                            </div>
                                        </div>

                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title">Propinas</h3>
                                            </div>
                                            <div class="panel-body">
                                                <label><input type="checkbox" name="calcular_valor_propina" value="1" <?php echo ($config['calcular_valor_propina'] ?? false) ? 'checked' : ''; ?>> Mostar valor de propína en precuenta</label>
                                                <label><input type="checkbox" name="preguntar_calculo_propina_en_pago" value="1" <?php echo ($config['preguntar_calculo_propina_en_pago'] ?? false) ? 'checked' : ''; ?>> Preguntar por calculo de propina en pago</label>
                                                <label><input type="checkbox" name="mostrar_boton_propina_garzon" value="1" <?php echo ($config['mostrar_boton_propina_garzon'] ?? true) ? 'checked' : ''; ?>> Mostrar boton ver propina</label>
                                                <label><input type="checkbox" name="propina_en_delivery" value="1" <?php echo ($config['propina_en_delivery'] ?? false) ? 'checked' : ''; ?>> Calcular propina para delivery</label>
                                                <label><input type="checkbox" name="propina_en_venta_directa" value="1" <?php echo ($config['propina_en_venta_directa'] ?? false) ? 'checked' : ''; ?>> Propina en venta directa</label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Columna 2 -->
                                    <div class="config-col">
                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title">Otros restorant</h3>
                                            </div>
                                            <div class="panel-body">
                                                <label><input type="checkbox" name="permitircambiarppp" value="1" <?php echo ($config['permitircambiarppp'] ?? true) ? 'checked' : ''; ?>> Permitir el cambio del costo de insumo</label>
                                                <label><input type="checkbox" name="ordenar_carta" value="1" <?php echo ($config['ordenar_carta'] ?? false) ? 'checked' : ''; ?>> Ordenar Carta</label>
                                                <div class="form-group">
                                                    <label>Mesa para venta directa</label>
                                                    <input type="text" class="form-control" name="id_mesa_venta_directa" value="<?php echo $config['id_mesa_venta_directa'] ?? ''; ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>Impresora para cierre de caja</label>
                                                    <input type="text" class="form-control" name="nombre_impresora_cierre" value="<?php echo $config['nombre_impresora_cierre'] ?? ''; ?>">
                                                </div>
                                                <label><input type="checkbox" name="imprimir_comprobante_anulacion" value="1" <?php echo ($config['imprimir_comprobante_anulacion'] ?? false) ? 'checked' : ''; ?>> Imprimir comprobantes de anulacion</label>
                                                <label><input type="checkbox" name="imprimir_valor_producto_cocina" value="1" <?php echo ($config['imprimir_valor_producto_cocina'] ?? false) ? 'checked' : ''; ?>> Imprimir Valor productos en destino de impresion</label>
                                            </div>
                                        </div>

                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title">Clave de administrador</h3>
                                            </div>
                                            <div class="panel-body">
                                                <div class="form-group">
                                                    <label>Clave de administrador</label>
                                                    <input type="password" class="form-control" name="txtclaveAdmin" value="<?php echo $config['pass_admin'] ?? ''; ?>">
                                                </div>
                                                <label><input type="checkbox" name="mostrar_clave_admin" value="1" <?php echo ($config['mostrar_clave_admin'] ?? false) ? 'checked' : ''; ?>> Mostrar clave</label>
                                                <label><input type="checkbox" name="admin_anulacion_eliminacion" value="1" <?php echo ($config['admin_anulacion_eliminacion'] ?? false) ? 'checked' : ''; ?>> Requerir clave admin para anulacion</label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Columna 3 -->
                                    <div class="config-col">
                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title">Informes por Correo</h3>
                                            </div>
                                            <div class="panel-body">
                                                <label><input type="checkbox" name="usar_correo_electronico" value="1" <?php echo ($config['usar_correo_electronico'] ?? false) ? 'checked' : ''; ?>> Enviar Cierre de caja por correo</label>
                                                <div class="form-group">
                                                    <input type="email" class="form-control" name="correo_electronico" placeholder="ejemplo@correo.com" value="<?php echo $config['correo_electronico'] ?? ''; ?>">
                                                </div>
                                                <label><input type="checkbox" name="informar_anulaciones_mail" value="1" <?php echo ($config['informar_anulaciones_mail'] ?? false) ? 'checked' : ''; ?>> Informar anulaciones y comisiones</label>
                                                <button type="button" class="btn btn-orange">Probar envío</button>
                                                <p class="p">Separar por coma para múltiples correos</p>
                                            </div>
                                        </div>

                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title">Imprimir cierre de caja</h3>
                                            </div>
                                            <div class="panel-body">
                                                <label><input type="checkbox" name="ver_ingresos_egresos_cierre_caja" value="1" <?php echo ($config['ver_ingresos_egresos_cierre_caja'] ?? true) ? 'checked' : ''; ?>> Ingresos y Egresos</label>
                                                <label><input type="checkbox" name="ver_creditos_a_clientes_cierre_caja" value="1" <?php echo ($config['ver_creditos_a_clientes_cierre_caja'] ?? true) ? 'checked' : ''; ?>> Creditos a clientes</label>
                                                <label><input type="checkbox" name="ver_cortesias_cierre_caja" value="1" <?php echo ($config['ver_cortesias_cierre_caja'] ?? true) ? 'checked' : ''; ?>> Cortesías</label>
                                                <label><input type="checkbox" name="ver_anulaciones_eliminaciones_cierre_caja" value="1" <?php echo ($config['ver_anulaciones_eliminaciones_cierre_caja'] ?? true) ? 'checked' : ''; ?>> Anulaciones y eliminaciones</label>
                                                <label><input type="checkbox" name="ver_detalle_atencion_cierre_caja" value="1" <?php echo ($config['ver_detalle_atencion_cierre_caja'] ?? true) ? 'checked' : ''; ?>> Detalle de atención</label>
                                                <hr style="margin: 10px 0;">
                                                <label><input type="checkbox" name="mostrar_recaudacion_cierre_no_efectivo" value="1" <?php echo ($config['mostrar_recaudacion_cierre_no_efectivo'] ?? true) ? 'checked' : ''; ?>> Llenar forma de pago no efectivo</label>
                                                <label><input type="checkbox" name="cierre_caja_manual" value="1" <?php echo ($config['cierre_caja_manual'] ?? true) ? 'checked' : ''; ?>> Cierre de caja detallado</label>
                                                <label><input type="checkbox" name="imprimir_cierre_caja_impresora_termica" value="1" <?php echo ($config['imprimir_cierre_caja_impresora_termica'] ?? true) ? 'checked' : ''; ?>> Imprimir cierre de caja</label>
                                            </div>
                                        </div>

                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title">Descuentos</h3>
                                            </div>
                                            <div class="panel-body">
                                                <div class="form-group">
                                                    <label>Máximo Permitido (%)</label>
                                                    <input type="number" step="0.01" class="form-control" name="por_maximodescuento" value="<?php echo $config['por_maximodescuento'] ?? ''; ?>">
                                                </div>
                                                <label><input type="checkbox" name="requiere_cliente_descuento" value="1" <?php echo ($config['requiere_cliente_descuento'] ?? false) ? 'checked' : ''; ?>> Requerir Cliente</label>
                                            </div>
                                        </div>

                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title">REQUERIR ADMIN PARA:</h3>
                                            </div>
                                            <div class="panel-body">
                                                <label><input type="checkbox" name="requerir_usuario_admin_credito" value="1" <?php echo ($config['requerir_usuario_admin_credito'] ?? false) ? 'checked' : ''; ?>> Ventas a credito</label>
                                                <label><input type="checkbox" name="requerir_usuario_admin_cortesia" value="1" <?php echo ($config['requerir_usuario_admin_cortesia'] ?? false) ? 'checked' : ''; ?>> cortesias</label>
                                                <label><input type="checkbox" name="requerir_usuario_admin_descuento" value="1" <?php echo ($config['requerir_usuario_admin_descuento'] ?? false) ? 'checked' : ''; ?>> Descuentos en caja</label>
                                                <label><input type="checkbox" name="boton_apertura_caja_solo_admin" value="1" <?php echo ($config['boton_apertura_caja_solo_admin'] ?? false) ? 'checked' : ''; ?>> Apertura de gaveta</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab Otras Configuraciones -->
                            <div id="otras" class="tab">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title">Configuración por defecto de Compras</h3>
                                            </div>
                                            <div class="panel-body">
                                                <div class="row">
                                                    <div class="col-md-4 form-group">
                                                        <label>Documento de compra</label>
                                                        <select class="form-control" name="id_tdocumento_compra">
                                                            <option value="0">DOCUMENTO COMPRA</option>
                                                            <option value="1">FACTURA ELECTRONICA</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4 form-group">
                                                        <label>Formas de pago</label>
                                                        <select class="form-control" name="id_fpago_compra">
                                                            <option value="0">FORMA DE PAGO</option>
                                                            <option value="1">EFECTIVO</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4 form-group">
                                                        <label>Bodega por defecto</label>
                                                        <select class="form-control" name="id_bodega_compra">
                                                            <option value="0">SELECCIONE BODEGA</option>
                                                            <?php foreach($bodegas as $b): ?>
                                                            <option value="<?= $b->id ?>" <?= ($config['id_bodega_compra'] ?? 0)==$b->id?'selected':'' ?>>
                                                                <?= $b->nombre ?>
                                                            </option>
                                                            <?php endforeach ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <hr>
                                                <h4 style="font-size: 14px; font-weight: 700; color: #4a5568; margin-bottom: 15px;">Créditos</h4>
                                                <div class="row">
                                                    <div class="col-md-6 form-group">
                                                        <label>Formas de pago credito a clientes</label>
                                                        <select class="form-control" name="id_fpago_creditodirecto">
                                                            <option value="0">FORMA DE PAGO crédito a clientes</option>
                                                            <option value="1">CREDITO DE CONFIANZA</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 form-group">
                                                        <label>Centro costo pago credito</label>
                                                        <select class="form-control" name="id_pagocreditodirecto">
                                                            <option value="0">CENTRO DE COSTO pago</option>
                                                            <option value="1">PAGO CREDITO CONFIANZA</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <hr>
                                                <h4 style="font-size: 14px; font-weight: 700; color: #4a5568; margin-bottom: 15px;">Bodegas</h4>
                                                <div class="row">
                                                    <div class="col-md-4 form-group">
                                                        <label>Compras</label>
                                                        <select class="form-control" name="id_bodega_compra">
                                                            <option value="0">COMPRAS</option>
                                                            <option value="1">BODEGA PRINCIPAL</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4 form-group">
                                                        <label>Ventas</label>
                                                        <select class="form-control" name="id_bodega_venta">
                                                            <option value="0">VENTAS</option>
                                                            <option value="1">BODEGA PRINCIPAL</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4 form-group">
                                                        <label>Merma</label>
                                                        <select class="form-control" name="id_bodega_merma_inventario">
                                                            <option value="0">MERMA (insumo)</option>
                                                            <option value="1">BODEGA PRINCIPAL</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title">Configuración por defecto de Venta</h3>
                                            </div>
                                            <div class="panel-body">
                                                <div class="form-group">
                                                    <label>cliente boleta</label>
                                                    <select class="form-control" name="id_cliente_publico">
                                                        <option value="0">CLIENTE BOLETA</option>
                                                        <option value="1">CLIENTE HABITUAL</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Lista de precio publico</label>
                                                    <select class="form-control" name="id_lpreciopublico">
                                                        <option value="0">LISTA DE PRECIO A PUBLICO</option>
                                                        <option value="1">LISTA PUBLICA</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>documento de venta</label>
                                                    <select class="form-control" name="id_docventa_defecto">
                                                        <option value="0">DOCUMENTO DE VENTA (Boleta)</option>
                                                        <option value="1">BOLETA ELECTRONICA</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Forma de pago por defecto</label>
                                                    <select class="form-control" name="id_fpago_defecto">
                                                        <option value="0">FORMA DE PAGO por defecto</option>
                                                        <option value="1">EFECTIVO</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Solo otorgar vuelto por pago</label>
                                                    <select class="form-control" name="vueltofpago">
                                                        <option value="0">SOLICITAR VUELTO por pago(efectivo)</option>
                                                        <option value="1">EFECTIVO</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Documento pago cortesias</label>
                                                    <select class="form-control" name="id_tdocumento_comprobante_interno">
                                                        <option value="0">DOCUMENTO PAGO CORTESIA</option>
                                                        <option value="1">COMPROBANTE INTERNO</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>impuestos iva</label>
                                                    <select class="form-control" name="id_impuesto_iva">
                                                        <option value="0">IMPUESTOS IVA</option>
                                                        <option value="1">IVA 19%</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Documento Comprobante interno</label>
                                                    <select class="form-control" name="id_tdocumento_comprobante_interno">
                                                        <option value="0">DOCUMENTO PAGO CORTESIA</option>
                                                        <option value="1">COMPROBANTE INTERNO</option>
                                                    </select>
                                                </div>
                                                <hr>
                                                <label><input type="checkbox" name="permitir_retiro_fondo_fijo" value="1" <?php echo ($config['permitir_retiro_fondo_fijo'] ?? false) ? 'checked' : ''; ?>> Permitir retiro fondo fijo</label>
                                                <label><input type="checkbox" name="lprecio_x_sector" value="1" <?php echo ($config['lprecio_x_sector'] ?? false) ? 'checked' : ''; ?>> Listar precios por sector</label>
                                                <label><input type="checkbox" name="agregar_comision_a_venta" value="1" <?php echo ($config['agregar_comision_a_venta'] ?? false) ? 'checked' : ''; ?>> Agregar comision a venta</label>
                                                <label><input type="checkbox" name="pedir_anfitriona" value="1" <?php echo ($config['pedir_anfitriona'] ?? false) ? 'checked' : ''; ?>> Pedir anfitriona</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="config-card">
                                            <div class="panel-body">
                                                <label><input type="checkbox" name="inventario_cierre_caja" value="1" <?php echo ($config['inventario_cierre_caja'] ?? false) ? 'checked' : ''; ?>> Inventario insumos cierre de caja</label>
                                                <label><input type="checkbox" name="permitir_repetir_producto_menu" value="1" <?php echo ($config['permitir_repetir_producto_menu'] ?? false) ? 'checked' : ''; ?>> Permitir repetir el producto en el menu</label>
                                                <label><input type="checkbox" name="pedir_comensales_en_mesa" value="1" <?php echo ($config['pedir_comensales_en_mesa'] ?? false) ? 'checked' : ''; ?>> Pedir comensales en mesa</label>
                                                <label><input type="checkbox" name="imprimir_hora_detalle_productos_pedido" value="1" <?php echo ($config['imprimir_hora_detalle_productos_pedido'] ?? false) ? 'checked' : ''; ?>> Imprimir hora en productos del pedido</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab Facturación Electrónica -->
                            <div id="facturacion" class="tab">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title">Facturación Electrónica</h3>
                                            </div>
                                            <div class="panel-body">
                                                <label><input type="checkbox" name="habilitar_facturacion_electronica" value="1" <?php echo ($config['habilitar_facturacion_electronica'] ?? false) ? 'checked' : ''; ?>> Habilitar factura electronica</label>
                                                <hr>
                                                <div class="form-group">
                                                    <label>EMPRESA PROVEEDORA DTE</label>
                                                    <div style="display: flex; gap: 20px; margin-top: 5px;">
                                                        <label style="margin:0"><input type="radio" name="proveedor_dte" value="1" <?php echo ($config['acepta'] ?? false) ? 'checked' : ''; ?>> ACEPTA</label>
                                                        <label style="margin:0"><input type="radio" name="proveedor_dte" value="2" <?php echo ($config['acierto'] ?? false) ? 'checked' : ''; ?>> ACIERTO</label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Factura Electrónica</label>
                                                    <select class="form-control" name="id_tdocumento_factura">
                                                        <option value="0">FACTURA ELECTRONICA</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Nota de Crédito Electrónica</label>
                                                    <select class="form-control" name="id_tdocumento_nota_credito">
                                                        <option value="0">NOTA DE CREDITO ELECTRONICA</option>
                                                        <option value="1">NOTA DE CREDITO FACTURA ELECTRONICA</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Guía de Despacho Electrónica</label>
                                                    <select class="form-control" name="id_tdocumento_guia_despacho">
                                                        <option value="0">GUIA DE DESPACHO ELECTRONICA</option>
                                                        <option value="1">OPERACION CONSTITUYE VENTA</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Ind traslado venta factura electronica</label>
                                                    <select class="form-control" name="id_indicador_traslado_venta">
                                                        <option value="0">GUIA DE DESPACHO ELECTRONICA</option>
                                                        <option value="1" <?= ($config['id_indicador_traslado_venta'] ?? 0) == 1 ? 'selected' : '' ?>>OPERACION CONSTITUYE VENTA</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Boleta Electrónica</label>
                                                    <select class="form-control" name="id_tdocumento_boleta_electronica">
                                                        <option value="0">BOLETA ELECTRONICA</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title">Parámetros Web y DTE</h3>
                                            </div>
                                            <div class="panel-body">
                                                <div class="form-group">
                                                    <label>Ruta API Get PDF</label>
                                                    <input type="text" class="form-control" name="ruta_api_get_pdf" value="<?php echo $config['ruta_api_get_pdf'] ?? ''; ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>Ruta API Get QR</label>
                                                    <input type="text" class="form-control" name="ruta_api_get_qr" value="<?php echo $config['ruta_api_get_qr'] ?? ''; ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>Ruta DTE Código QR</label>
                                                    <input type="text" class="form-control" name="ruta_dte_qr" value="<?php echo $config['ruta_dte_qr'] ?? ''; ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>Ruta Consulta Folio DTE</label>
                                                    <input type="text" class="form-control" name="url_consulta_folio_dte" value="<?php echo $config['url_consulta_folio_dte'] ?? ''; ?>">
                                                </div>
                                                <hr>
                                                <label><input type="checkbox" name="imprimir_pdf_dte" value="1" <?php echo ($config['imprimir_pdf_dte'] ?? false) ? 'checked' : ''; ?>> Imprimir pdf</label>
                                                <div class="form-group">
                                                    <label>Ruta Impresora/PDF</label>
                                                    <input type="text" class="form-control" name="ruta_dte_pdf" value="<?php echo $config['ruta_dte_pdf'] ?? '/17.0.1/caja'; ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>Formato pdf</label>
                                                    <input type="text" class="form-control" name="formato_dte" value="<?php echo $config['formato_dte'] ?? ''; ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>Copias dte</label>
                                                    <input type="number" class="form-control" name="copias_dte" value="<?php echo $config['copias_dte'] ?? 1; ?>">
                                                </div>
                                                <label><input type="checkbox" name="imprimir_copia_cedible" value="1" <?php echo ($config['imprimir_copia_cedible'] ?? false) ? 'checked' : ''; ?>> Imprimir copia cedible</label>
                                                <label><input type="checkbox" name="enviar_dte_sii" value="1" <?php echo ($config['enviar_dte_sii'] ?? false) ? 'checked' : ''; ?>> enviar dte al sii</label>
                                                <div class="form-group">
                                                    <label>Timeout (Seg)</label>
                                                    <input type="number" class="form-control" name="timeout" value="<?php echo $config['timeout'] ?? 20; ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title">Certificación SII</h3>
                                            </div>
                                            <div class="panel-body">
                                                <div class="form-group">
                                                    <label>Comuna SII</label>
                                                    <input type="number" class="form-control" name="id_comuna_sii" value="<?php echo $config['id_comuna_sii'] ?? ''; ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>Resolución SII</label>
                                                    <input type="text" class="form-control" name="resolucion_sii" value="<?php echo $config['resolucion_sii'] ?? ''; ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>Año resolución</label>
                                                    <input type="number" class="form-control" name="anio_resolucion_sii" value="<?php echo $config['anio_resolucion_sii'] ?? ''; ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title">Acceso Facturación</h3>
                                            </div>
                                            <div class="panel-body">
                                                <div class="form-group">
                                                    <label>Usuario</label>
                                                    <input type="text" class="form-control" name="usuario_dte" value="<?php echo $config['usuario_dte'] ?? ''; ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>Contraseña</label>
                                                    <input type="password" class="form-control" name="clave_usuario_dte" value="<?php echo $config['clave_usuario_dte'] ?? ''; ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab Datos de la Empresa -->
                            <div id="empresa" class="tab">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title">Datos de la Empresa</h3>
                                            </div>
                                            <div class="panel-body">
                                                <div class="row">
                                                    <div class="col-md-4 form-group">
                                                        <label>Rut</label>
                                                        <input type="text" class="form-control" name="rut_empresa" value="<?php echo $empresa['rut_empresa'] ?? ''; ?>">
                                                    </div>
                                                    <div class="col-md-2 form-group">
                                                        <label>DV</label>
                                                        <input type="text" class="form-control" name="dv_empresa" maxlength="1" value="<?php echo $empresa['dv_empresa'] ?? ''; ?>">
                                                    </div>
                                                    <div class="col-md-6 form-group">
                                                        <label>Razón Social</label>
                                                        <input type="text" class="form-control" name="razon_social" value="<?php echo $empresa['razon_social'] ?? ''; ?>">
                                                    </div>
                                                    <div class="col-md-6 form-group">
                                                        <label>Nombre Fantasía</label>
                                                        <input type="text" class="form-control" name="nombre_fantasia" value="<?php echo $empresa['nombre_fantasia'] ?? ''; ?>">
                                                    </div>
                                                    <div class="col-md-3 form-group">
                                                        <label>Código Act. Económica</label>
                                                        <input type="text" class="form-control" name="codigo_actividad_economica" value="<?php echo $empresa['codigo_actividad_economica'] ?? ''; ?>">
                                                    </div>
                                                    <div class="col-md-3 form-group">
                                                        <label>Giro</label>
                                                        <input type="text" class="form-control" name="giro" value="<?php echo $empresa['giro'] ?? ''; ?>">
                                                    </div>
                                                    <div class="col-md-12 form-group">
                                                        <label>Dirección</label>
                                                        <input type="text" class="form-control" name="direccion_empresa" value="<?php echo $empresa['direccion_empresa'] ?? ''; ?>">
                                                    </div>
                                                    <div class="col-md-4 form-group">
                                                        <label>Comuna</label>
                                                        <input type="number" class="form-control" name="id_comuna" value="<?php echo $empresa['id_comuna'] ?? ''; ?>">
                                                    </div>
                                                    <div class="col-md-4 form-group">
                                                        <label>Correo</label>
                                                        <input type="email" class="form-control" name="mail" value="<?php echo $empresa['mail'] ?? ''; ?>">
                                                    </div>
                                                    <div class="col-md-4 form-group">
                                                        <label>Teléfono</label>
                                                        <input type="text" class="form-control" name="telefono" value="<?php echo $empresa['telefono'] ?? ''; ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title">Sucursal</h3>
                                            </div>
                                            <div class="panel-body">
                                                <div class="row">
                                                    <div class="col-md-4 form-group">
                                                        <label>Código SII Sucursal</label>
                                                        <input type="number" class="form-control" name="codigo_sii" value="<?php echo $empresa['codigo_sii'] ?? ''; ?>">
                                                    </div>
                                                    <div class="col-md-4 form-group">
                                                        <label>Código Sucursal</label>
                                                        <input type="text" class="form-control" name="codigo_sucursal" value="<?php echo $empresa['codigo_sucursal'] ?? ''; ?>">
                                                    </div>
                                                    <div class="col-md-4 form-group">
                                                        <label>Nombre Sucursal</label>
                                                        <input type="text" class="form-control" name="nombre_sucursal" value="<?php echo $empresa['nombre_sucursal'] ?? ''; ?>">
                                                    </div>
                                                    <div class="col-md-8 form-group">
                                                        <label>Dirección Sucursal</label>
                                                        <input type="text" class="form-control" name="direccion_sucursal" value="<?php echo $empresa['direccion_sucursal'] ?? ''; ?>">
                                                    </div>
                                                    <div class="col-md-4 form-group">
                                                        <label>Comuna Sucursal</label>
                                                        <input type="number" class="form-control" name="id_comuna_sucursal" value="<?php echo $empresa['id_comuna_sucursal'] ?? ''; ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" form="configForm" class="btn btn-primary">Grabar</button>
                                <button type="button" onclick="location.reload();" class="btn btn-default">Cancelar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>



<style>
    /* Estilos Modernos para Configuraciones */
    .content-header {
        padding: 20px 25px !important;
        margin-bottom: 20px !important;
    }
    
    .panel-success.lobidisable {
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        border: none;
    }

    .panel-success > .panel-heading {
        background: #558B2F !important;
        color: white !important;
        border-radius: 12px 12px 0 0 !important;
        padding: 15px 20px;
    }

    .tabs {
        background: #f8f9fa;
        padding: 5px;
        border-radius: 8px;
        margin-bottom: 25px;
        display: inline-flex;
        border: 1px solid #e9ecef;
    }
    
    .tablink {
        background: transparent;
        border: none;
        padding: 10px 25px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        color: #7f8c8d;
        border-radius: 6px;
        transition: all 0.3s ease;
    }
    
    .tablink:hover {
        color: #34495e;
        background: rgba(26, 188, 156, 0.05);
    }
    
    .tablink.active {
        background: #558B2F;
        color: white;
        box-shadow: 0 4px 10px rgba(26, 188, 156, 0.2);
    }

    .tab {
        display: none;
        animation: fadeIn 0.4s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .tab.active {
        display: block;
    }

    /* Cards de configuración */
    .config-card {
        background: white;
        border: 1px solid #edf2f7;
        border-radius: 10px;
        margin-bottom: 20px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .config-card:hover {
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }

    .config-card .panel-heading {
        background: #fcfcfc;
        border-bottom: 1px solid #edf2f7;
        padding: 12px 15px;
        border-radius: 10px 10px 0 0;
    }

    .config-card .panel-title {
        font-size: 14px;
        font-weight: 700;
        color: #2d3748;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .config-card .panel-body {
        padding: 15px;
    }

    /* Checkbox styling */
    .config-card label {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
        font-weight: 500;
        color: #4a5568;
        cursor: pointer;
        font-size: 13px;
        transition: color 0.2s ease;
    }

    .config-card label:hover {
        color: #558B2F;
    }

    .config-card input[type="checkbox"] {
        margin-right: 10px;
        width: 16px;
        height: 16px;
        accent-color: #558B2F;
    }

    .form-group label {
        font-size: 12px;
        color: #718096;
        margin-bottom: 5px;
        text-transform: uppercase;
        font-weight: 600;
    }

    .form-control {
        border-radius: 6px;
        border: 1px solid #e2e8f0;
        padding: 8px 12px;
        height: auto;
        font-size: 13px;
    }

    .form-control:focus {
        border-color: #558B2F;
        box-shadow: 0 0 0 3px rgba(26, 188, 156, 0.1);
    }

    .btn-orange {
        background: #f39c12;
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 12px;
        margin-top: 10px;
    }

    .p { font-size: 11px; color: #a0aec0; margin: 5px 0; }
    
    .modal-footer {
        border-top: 1px solid #edf2f7;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 0 0 12px 12px;
    }

    .btn-primary {
        background: #558B2F;
        border: none;
        padding: 10px 30px;
        font-weight: 700;
        border-radius: 6px;
        box-shadow: 0 4px 10px rgba(26, 188, 156, 0.2);
    }

    .btn-primary:hover {
        background: #16a085;
        transform: translateY(-1px);
    }

    /* Ajuste de columnas para evitar espacios en blanco */
    .config-grid {
        display: flex;
        flex-wrap: wrap;
        margin: 0 -10px;
    }
    
    .config-col {
        flex: 1;
        min-width: 300px;
        padding: 0 10px;
    }

    @media (max-width: 992px) {
        .config-col {
            flex: 0 0 50%;
        }
    }

    @media (max-width: 768px) {
        .config-col {
            flex: 0 0 100%;
        }
        .tabs {
            display: flex;
            flex-direction: column;
        }
        .tablink {
            border-right: none;
            border-bottom: 1px solid #e9ecef;
        }
    }
</style>