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
    <div class="config-db-indicator">
        <div class="config-db-indicator__title"><i class="fa fa-database"></i> Conexión actual</div>
        <div class="config-db-indicator__row">
            <span class="config-db-indicator__label">Conexión:</span>
            <span class="config-db-indicator__value"><?php echo !empty($conexion_actual) ? htmlspecialchars($conexion_actual, ENT_QUOTES, 'UTF-8') : 'N/D'; ?></span>
        </div>
        <div class="config-db-indicator__row">
            <span class="config-db-indicator__label">Base de datos:</span>
            <span class="config-db-indicator__value"><?php echo !empty($base_datos_actual) ? htmlspecialchars($base_datos_actual, ENT_QUOTES, 'UTF-8') : 'N/D'; ?></span>
        </div>
    </div>
</div>

<section class="content t-config">
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
                    <div class="panel-body">
                        <!-- Tabs -->
                        <div class="tabs">
                            <button class="tablink active" onclick="openTab(event, 'general')"><i class="fa fa-sliders"></i>General</button>
                            <button class="tablink" onclick="openTab(event, 'otras')"><i class="fa fa-cogs"></i>Valores por defecto</button>
                            <button class="tablink" onclick="openTab(event, 'facturacion')"><i class="fa fa-file-text-o"></i>Facturación Electrónica</button>
                            <button class="tablink" onclick="openTab(event, 'empresa')"><i class="fa fa-building-o"></i>Datos de la Empresa</button>
                        </div>

                        <form id="configForm" method="post" action="<?php echo site_url('Configuraciones/guardar'); ?>">
                            <!-- Tab General -->
                            <div id="general" class="tab active">
                                <p class="t-config-intro"><i class="fa fa-info-circle"></i> Opciones de la caja, precuentas, propinas y permisos. Los cambios se aplican al presionar Grabar.</p>
                                <div class="config-grid">
                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title"><i class="fa fa-desktop"></i>Ver en Caja</h3>
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
                                                <h3 class="panel-title"><i class="fa fa-file-text-o"></i>Precuentas</h3>
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
                                                <h3 class="panel-title"><i class="fa fa-money"></i>Propinas</h3>
                                            </div>
                                            <div class="panel-body">
                                                <label><input type="checkbox" name="calcular_valor_propina" value="1" <?php echo ($config['calcular_valor_propina'] ?? false) ? 'checked' : ''; ?>> Mostar valor de propína en precuenta</label>
                                                <label><input type="checkbox" name="preguntar_calculo_propina_en_pago" value="1" <?php echo ($config['preguntar_calculo_propina_en_pago'] ?? false) ? 'checked' : ''; ?>> Preguntar por calculo de propina en pago</label>
                                                <label><input type="checkbox" name="mostrar_boton_propina_garzon" value="1" <?php echo ($config['mostrar_boton_propina_garzon'] ?? true) ? 'checked' : ''; ?>> Mostrar boton ver propina</label>
                                                <label><input type="checkbox" name="propina_en_delivery" value="1" <?php echo ($config['propina_en_delivery'] ?? false) ? 'checked' : ''; ?>> Calcular propina para delivery</label>
                                                <label><input type="checkbox" name="propina_en_venta_directa" value="1" <?php echo ($config['propina_en_venta_directa'] ?? false) ? 'checked' : ''; ?>> Propina en venta directa</label>
                                            </div>
                                        </div>

                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title"><i class="fa fa-cutlery"></i>Otros del restaurante</h3>
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
                                                <h3 class="panel-title"><i class="fa fa-key"></i>Clave de administrador</h3>
                                            </div>
                                            <div class="panel-body">
                                                <div class="form-group">
                                                    <label>Clave de administrador</label>
                                                    <input type="password" id="txtclaveAdmin" class="form-control" name="txtclaveAdmin" value="<?php echo $config['pass_admin'] ?? ''; ?>">
                                                </div>
                                                <label><input type="checkbox" id="chkMostrarClaveAdmin" onchange="toggleMostrarClaveAdmin()" name="mostrar_clave_admin" value="1" <?php echo ($config['mostrar_clave_admin'] ?? false) ? 'checked' : ''; ?>> Mostrar clave</label>
                                                <label><input type="checkbox" name="admin_anulacion_eliminacion" value="1" <?php echo ($config['admin_anulacion_eliminacion'] ?? false) ? 'checked' : ''; ?>> Requerir clave admin para anulacion</label>
                                            </div>
                                        </div>

                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title"><i class="fa fa-envelope-o"></i>Informes por correo</h3>
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
                                                <h3 class="panel-title"><i class="fa fa-print"></i>Imprimir cierre de caja</h3>
                                            </div>
                                            <div class="panel-body">
                                                <label><input type="checkbox" name="ver_ingresos_egresos_cierre_caja" value="1" <?php echo ($config['ver_ingresos_egresos_cierre_caja'] ?? true) ? 'checked' : ''; ?>> Ingresos y Egresos</label>
                                                <label><input type="checkbox" name="ver_creditos_a_clientes_cierre_caja" value="1" <?php echo ($config['ver_creditos_a_clientes_cierre_caja'] ?? true) ? 'checked' : ''; ?>> Creditos a clientes</label>
                                                <label><input type="checkbox" name="ver_cortesias_cierre_caja" value="1" <?php echo ($config['ver_cortesias_cierre_caja'] ?? true) ? 'checked' : ''; ?>> Cortesías</label>
                                                <label><input type="checkbox" name="ver_anulaciones_eliminaciones_cierre_caja" value="1" <?php echo ($config['ver_anulaciones_eliminaciones_cierre_caja'] ?? true) ? 'checked' : ''; ?>> Anulaciones y eliminaciones</label>
                                                <label><input type="checkbox" name="ver_detalle_atencion_cierre_caja" value="1" <?php echo ($config['ver_detalle_atencion_cierre_caja'] ?? true) ? 'checked' : ''; ?>> Detalle de atención</label>
                                                <hr>
                                                <label><input type="checkbox" name="mostrar_recaudacion_cierre_no_efectivo" value="1" <?php echo ($config['mostrar_recaudacion_cierre_no_efectivo'] ?? true) ? 'checked' : ''; ?>> Llenar forma de pago no efectivo</label>
                                                <label><input type="checkbox" name="cierre_caja_manual" value="1" <?php echo ($config['cierre_caja_manual'] ?? true) ? 'checked' : ''; ?>> Cierre de caja detallado</label>
                                                <label><input type="checkbox" name="imprimir_cierre_caja_impresora_termica" value="1" <?php echo ($config['imprimir_cierre_caja_impresora_termica'] ?? true) ? 'checked' : ''; ?>> Imprimir cierre de caja</label>
                                            </div>
                                        </div>

                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title"><i class="fa fa-percent"></i>Descuentos</h3>
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
                                                <h3 class="panel-title"><i class="fa fa-shield"></i>Requerir administrador para</h3>
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

                            <!-- Tab Otras Configuraciones -->
                            <div id="otras" class="tab">
                                <p class="t-config-intro"><i class="fa fa-info-circle"></i> Documentos, formas de pago y bodegas que se proponen por defecto en compras y ventas.</p>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title"><i class="fa fa-shopping-cart"></i>Configuración por defecto de compras</h3>
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
                                                <h4>Créditos</h4>
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
                                                <h4>Bodegas</h4>
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
                                                <h3 class="panel-title"><i class="fa fa-shopping-bag"></i>Configuración por defecto de venta</h3>
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
                                            <div class="panel-heading">
                                                <h3 class="panel-title"><i class="fa fa-cutlery"></i>Operación de mesas e inventario</h3>
                                            </div>
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
                                <p class="t-config-intro"><i class="fa fa-info-circle"></i> Proveedor DTE, documentos electrónicos, acceso a Cloud Apps y folios autorizados por el SII.</p>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title"><i class="fa fa-file-text-o"></i>Facturación electrónica</h3>
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
                                                <h3 class="panel-title"><i class="fa fa-globe"></i>Parámetros web y DTE</h3>
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
                                                <h3 class="panel-title"><i class="fa fa-certificate"></i>Certificación SII</h3>
                                            </div>
                                            <div class="panel-body">
                                                <div class="form-group">
                                                    <label>Comuna SII</label>
                                                    <select class="form-control" id="cboIdComunaSii" name="id_comuna_sii" data-selected="<?php echo $config['id_comuna_sii'] ?? 0; ?>">
                                                        <option value="0">Seleccione la comuna</option>
                                                    </select>
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
                                                <h3 class="panel-title"><i class="fa fa-cloud"></i>Acceso facturación (Cloud Apps)</h3>
                                            </div>
                                            <div class="panel-body">
                                                <div class="form-group">
                                                    <label>Usuario</label>
                                                    <?php $rutEmpresaDte = !empty($empresa['rut_empresa']) ? number_format((float)$empresa['rut_empresa'], 0, '', '') . '-' . strtoupper(trim($empresa['dv_empresa'] ?? '')) : ''; ?>
                                                    <input type="text" class="form-control" name="usuario_dte" id="txtUsuarioDte" value="<?php echo htmlspecialchars($config['usuario_dte'] ?? '', ENT_QUOTES); ?>" placeholder="<?php echo $rutEmpresaDte ? 'Ej: ' . $rutEmpresaDte : 'RUT de la empresa'; ?>">
                                                    <p class="help-block">RUT de la empresa con guion<?php echo $rutEmpresaDte ? ' (' . $rutEmpresaDte . ')' : ''; ?>.</p>
                                                </div>
                                                <div class="form-group">
                                                    <label>Contraseña</label>
                                                    <input type="password" class="form-control" name="clave_usuario_dte" id="txtClaveDte" value="<?php echo htmlspecialchars($config['clave_usuario_dte'] ?? '', ENT_QUOTES); ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>Terminal</label>
                                                    <input type="text" class="form-control" name="terminal_dte" id="txtTerminalDte" maxlength="60" value="<?php echo htmlspecialchars($config['terminal_dte'] ?? '', ENT_QUOTES); ?>" placeholder="Ej: DATAPDV-CAJA1">
                                                    <p class="help-block">Terminal registrado en Cloud Apps para la caja web. Sin terminal no se emiten boletas ni facturas.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title"><i class="fa fa-list-ol"></i>Folios electrónicos</h3>
                                            </div>
                                            <div class="panel-body">
                                                <p class="text-muted">Consulta en Cloud Apps los folios autorizados por el SII para cada documento, con el usuario, la contraseña y el terminal de arriba (no hace falta grabar antes).</p>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-condensed" id="tablaFoliosDte">
                                                        <thead>
                                                            <tr><th>Documento</th><th class="text-center">Cód. SII</th><th>Rango autorizado</th>
                                                                <th class="text-right">Último usado</th><th class="text-right">Disponibles</th><th>Vence</th><th>Estado</th>
                                                                <th class="text-center" style="width:150px">
                                                                    <button type="button" class="btn btn-success btn-xs" id="btnFoliosTodos"><i class="fa fa-refresh"></i> Consultar todos</button>
                                                                </th></tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php if (empty($documentos_dte)) : ?>
                                                                <tr><td colspan="8" class="text-center text-muted">No hay documentos electrónicos (con código SII) en Tipos de documento.</td></tr>
                                                            <?php endif; ?>
                                                            <?php foreach ($documentos_dte as $d) : ?>
                                                                <tr data-tipo="<?php echo (int)$d->cod_sii; ?>">
                                                                    <td><?php echo htmlspecialchars($d->nombre_tdocumento, ENT_QUOTES); ?></td>
                                                                    <td class="text-center"><?php echo (int)$d->cod_sii; ?></td>
                                                                    <td class="f-rango">—</td>
                                                                    <td class="text-right f-ultimo">—</td>
                                                                    <td class="text-right f-disp">—</td>
                                                                    <td class="f-vence">—</td>
                                                                    <td class="f-estado text-muted">Sin consultar</td>
                                                                    <td class="text-center">
                                                                        <button type="button" class="btn btn-default btn-xs btn-folios"><i class="fa fa-download"></i> Obtener folios</button>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab Datos de la Empresa -->
                            <div id="empresa" class="tab">
                                <p class="t-config-intro"><i class="fa fa-info-circle"></i> Datos tributarios de la empresa y de la sucursal que aparecen en los documentos.</p>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title"><i class="fa fa-building-o"></i>Datos de la empresa</h3>
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
                                                        <select class="form-control" id="cboIdComuna" name="id_comuna" data-selected="<?php echo $empresa['id_comuna'] ?? 0; ?>">
                                                            <option value="0">Seleccione la comuna</option>
                                                        </select>
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
                                                <h3 class="panel-title"><i class="fa fa-map-marker"></i>Sucursal</h3>
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
                                                        <select class="form-control" id="cboIdComunaSucursal" name="id_comuna_sucursal" data-selected="<?php echo $empresa['id_comuna_sucursal'] ?? 0; ?>">
                                                            <option value="0">Seleccione la comuna</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer t-config-footer">
                                <button type="button" onclick="location.reload();" class="btn btn-default">Cancelar</button>
                                <button type="submit" form="configForm" class="btn btn-primary"><i class="fa fa-check"></i> Grabar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>





<style>
    /* Configuraciones: pestañas, tarjetas por tema y barra de grabado fija. Todo acotado a .t-config (solo estilos) */
    .t-config { padding-top: 0; }
    .t-config > .row > div > .panel-body { padding: 0; }
    .t-config .panel-success.lobidisable > .panel-body { padding: 0 18px 0; }

    /* Pestañas con subrayado, como el resto del sistema; en móvil se desplazan horizontalmente */
    .t-config .tabs {
        display: flex; gap: 2px; margin: 0 -18px 20px; padding: 0 10px; border-bottom: 1px solid var(--t-borde);
        overflow-x: auto; -webkit-overflow-scrolling: touch; scrollbar-width: none;
    }
    .t-config .tabs::-webkit-scrollbar { display: none; }
    .t-config .tablink {
        flex: 0 0 auto; background: none; border: 0; border-bottom: 2px solid transparent; border-radius: 0;
        padding: 13px 14px 11px; margin-bottom: -1px; font-size: 14px; font-weight: 600; color: var(--t-texto-suave);
        cursor: pointer; white-space: nowrap; transition: color .15s, border-color .15s;
    }
    .t-config .tablink i { margin-right: 6px; color: var(--t-texto-tenue); }
    .t-config .tablink:hover { color: var(--t-texto); }
    .t-config .tablink.active { color: var(--t-primario); border-bottom-color: var(--t-primario); }
    .t-config .tablink.active i { color: var(--t-primario); }
    .t-config .tablink:focus { outline: none; }
    .t-config .tablink:focus-visible { box-shadow: inset 0 0 0 2px rgba(180, 67, 46, .25); }

    .t-config .tab { display: none; animation: tConfigEntrada .25s ease; }
    .t-config .tab.active { display: block; }
    @keyframes tConfigEntrada { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
    .t-config-intro { margin: -6px 0 16px; font-size: 13px; color: var(--t-texto-tenue); }
    .t-config-intro i { margin-right: 4px; }

    /* Tarjetas de configuración */
    .t-config .config-card {
        background: #fff; border: 1px solid var(--t-borde); border-radius: var(--t-radio); box-shadow: var(--t-sombra);
        margin-bottom: 18px; align-self: start;
    }
    .t-config .config-grid .config-card { margin-bottom: 0; }
    .t-config .config-card > .panel-heading {
        background: var(--t-fondo-alt); border-bottom: 1px solid var(--t-borde-suave); padding: 11px 16px; border-radius: var(--t-radio) var(--t-radio) 0 0;
    }
    .t-config .config-card .panel-title {
        margin: 0; font-size: 12px; font-weight: 700; color: var(--t-texto-suave); text-transform: uppercase; letter-spacing: .4px;
    }
    .t-config .config-card .panel-title i { margin-right: 6px; color: var(--t-primario); font-size: 14px; }
    .t-config .config-card > .panel-body { padding: 10px 16px 14px; }

    /* Cada casilla es una fila clicable */
    .t-config .config-card label {
        display: flex; align-items: flex-start; gap: 10px; margin: 0; padding: 8px 6px; font-size: 13px; font-weight: 500;
        color: var(--t-texto); cursor: pointer; border-radius: var(--t-radio-sm); line-height: 1.35; transition: background-color .15s;
    }
    .t-config .config-card label:hover { background: var(--t-primario-suave); }
    .t-config .config-card input[type="checkbox"], .t-config .config-card input[type="radio"] {
        flex: 0 0 auto; width: 16px; height: 16px; margin: 1px 0 0; accent-color: var(--t-primario); cursor: pointer;
    }
    .t-config .config-card hr { margin: 10px 0; }
    .t-config .config-card h4 { margin: 4px 0 12px; font-size: 12px; font-weight: 700; color: var(--t-texto-suave); text-transform: uppercase; letter-spacing: .4px; }

    /* Campos de texto y selects: la etiqueta vuelve a ser un título pequeño, sin fila clicable */
    .t-config .form-group { margin: 6px 0 12px; }
    .t-config .form-group > label,
    .t-config .config-card .form-group > label {
        display: block; padding: 0; margin-bottom: 5px; font-size: 12px; font-weight: 700; color: var(--t-texto-suave);
        text-transform: uppercase; letter-spacing: .3px; cursor: default; background: none;
    }
    .t-config .form-group > label:hover { background: none; }
    .t-config .config-card .form-group label[style] { display: inline-flex; padding: 6px 10px; text-transform: none; font-size: 13px;
        border: 1px solid var(--t-borde); border-radius: var(--t-radio-sm); color: var(--t-texto); cursor: pointer; }
    .t-config .help-block { font-size: 12px; color: var(--t-texto-tenue); margin: 5px 0 0; }
    .t-config .p { font-size: 12px; color: var(--t-texto-tenue); margin: 6px 0 0; }

    .t-config .btn-orange {
        background: #fff; color: var(--t-texto); border: 1px solid var(--t-borde, #EADFD2); border-radius: var(--t-radio-sm);
        padding: 6px 12px; font-weight: 600; font-size: 12px; margin-top: 6px;
    }
    .t-config .btn-orange:hover { border-color: var(--t-primario); color: var(--t-primario); }

    /* Grilla de tarjetas: se acomodan fila por fila sin huecos */
    /* Mosaico en columnas: cada tarjeta ocupa su alto real y la siguiente se ubica justo debajo, sin huecos */
    .t-config .config-grid { display: block; column-count: 3; column-gap: 18px; }
    .t-config .config-grid > .config-card { display: inline-block; width: 100%; vertical-align: top; -webkit-column-break-inside: avoid; break-inside: avoid; page-break-inside: avoid; margin: 0 0 18px !important; }

    /* Folios */
    .t-config #tablaFoliosDte { margin-bottom: 0; }
    .t-config #tablaFoliosDte th { font-size: 12px; text-transform: uppercase; letter-spacing: .3px; color: var(--t-texto-suave); }

    /* Barra de grabado: queda visible al bajar por la página */
    .t-config .t-config-footer {
        position: sticky; bottom: 0; z-index: 5; display: flex; justify-content: flex-end; gap: 8px; flex-wrap: wrap;
        margin: 20px -18px 0; padding: 12px 18px; background: rgba(248, 250, 253, .97); border-top: 1px solid var(--t-borde);
        border-radius: 0 0 var(--t-radio) var(--t-radio); text-align: right;
    }
    .t-config .t-config-footer .btn { min-width: 120px; }
    .t-config .t-config-footer .btn i { margin-right: 4px; }

    /* Indicador de conexión: tarjeta compacta dentro del encabezado de la página (sobre la foto) */
    .content-header .config-db-indicator {
        margin-left: auto; flex: 0 1 auto; display: flex; flex-direction: column; gap: 3px; min-width: 220px; max-width: 360px;
        padding: 10px 16px; border-radius: 14px; font-size: 12px; color: #fff;
        background: rgba(255, 255, 255, .14); border: 1px solid rgba(255, 255, 255, .28);
        -webkit-backdrop-filter: blur(6px); backdrop-filter: blur(6px);
    }
    .content-header .config-db-indicator__title { font-size: 11px; font-weight: 700; color: var(--t-mostaza, #E3B04B); text-transform: uppercase; letter-spacing: .6px; margin-bottom: 2px; }
    .content-header .config-db-indicator__title i { margin-right: 4px; }
    .content-header .config-db-indicator__row { display: flex; gap: 6px; }
    .content-header .config-db-indicator__label { font-weight: 600; color: #EAD9C2; }
    .content-header .config-db-indicator__value { font-weight: 600; color: #fff; word-break: break-word; }

    /* Mismo mosaico en las otras pestañas (Valores por defecto, Facturación, Empresa): las tarjetas en col-md-6
       se apilan por columnas sin huecos; las de ancho completo (col-md-12) ocupan las dos columnas */
    @media (min-width: 992px) {
        .t-config .tab > .row { display: block; column-count: 2; column-gap: 18px; margin: 0; }
        .t-config .tab > .row > [class*="col-md-"] {
            float: none; display: inline-block; width: 100%; vertical-align: top; padding: 0;
            -webkit-column-break-inside: avoid; break-inside: avoid; page-break-inside: avoid;
        }
        .t-config .tab > .row > .col-md-12 { -webkit-column-span: all; column-span: all; display: block; }
        .t-config .tab > .row > [class*="col-md-"] > .config-card { margin: 0 0 18px; }
    }
    @media (max-width: 1199px) { .t-config .config-grid { column-count: 2; } }
    @media (max-width: 767px) {
        .t-config .config-grid { column-count: 1; }
        .t-config .config-grid > .config-card { margin-bottom: 14px !important; }
        .t-config .panel-success.lobidisable > .panel-body { padding: 0 12px; }
        .t-config .tabs { margin: 0 -12px 16px; }
        .t-config .t-config-footer { margin: 16px -12px 0; padding: 10px 12px; }
        .t-config .t-config-footer .btn { flex: 1 1 0; min-width: 0; }
        .content-header { flex-wrap: wrap; }
        .content-header .config-db-indicator { margin: 12px 0 0; max-width: none; width: 100%; }
    }
</style>
