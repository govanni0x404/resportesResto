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
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="panel panel-default">
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
                                        <div class="panel panel-default">
                                            <div class="panel-heading">
                                                <h3 class="panel-title">Configuraciones Caja</h3>
                                            </div>
                                            <div class="panel-body">
                                                <label><input type="checkbox" name="usar_impresion_grafica_precuenta" value="1" <?php echo ($config['usar_impresion_grafica_precuenta'] ?? true) ? 'checked' : ''; ?>> Impresion grafica y precuentas</label>
                                                <input type="text" class="form-control" name="impresion_grafica_precuenta" value="<?php echo $config['impresion_grafica_precuenta'] ?? ''; ?>">
                                                <label><input type="checkbox" name="" value="1" <?php echo ($config[''] ?? true) ? 'checked' : ''; ?>> Impresora Local</label>
                                                <input type="text" class="form-control" name="impresora_local_precuenta" value="<?php echo $config['impresora_local_precuenta'] ?? ''; ?>">
                                                <label><input type="checkbox" name="imprimir_comprobante_venta" value="1" <?php echo ($config['imprimir_comprobante_venta'] ?? true) ? 'checked' : ''; ?>> Imprimir comprobante de venta</label>
                                            </div>
                                        </div>
                                        <div class="panel panel-default">
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
                                        <div class="panel panel-default">
                                            <div class="panel-heading">
                                                <h3 class="panel-title">Propinas</h3>
                                            </div>
                                            <div class="panel-body">
                                                <label><input type="checkbox" name="mostrar_propina_precuenta" value="1" <?php echo ($config['mostrar_propina_precuenta'] ?? false) ? 'checked' : ''; ?>> Mostar valor de propína en precuenta</label>
                                                <label><input type="checkbox" name="preguntar_calculo_propina_en_pago" value="1" <?php echo ($config['preguntar_calculo_propina_en_pago'] ?? false) ? 'checked' : ''; ?>> Preguntar por calculo de propina en pago</label>
                                                <label><input type="checkbox" name="mostrar_boton_propina_garzon" value="1" <?php echo ($config['mostrar_boton_propina_garzon'] ?? true) ? 'checked' : ''; ?>> Mostrar boton ver propina</label>
                                                <label><input type="checkbox" name="propina_en_delivery" value="1" <?php echo ($config['propina_en_delivery'] ?? false) ? 'checked' : ''; ?>> Calcular propina para delivery</label>
                                                <label><input type="checkbox" name="propina_en_venta_directa" value="1" <?php echo ($config['propina_en_venta_directa'] ?? false) ? 'checked' : ''; ?>> Propina en venta directa</label>
                                            </div>
                                        </div>
                                        <div class="panel panel-default">
                                            <div class="panel-heading">
                                                <h3 class="panel-title">Otros restorant</h3>
                                            </div>
                                            <div class="panel-body">
                                                <label><input type="checkbox" name="permitir_cambio_costo_insumo" value="1" <?php echo ($config['permitir_cambio_costo_insumo'] ?? true) ? 'checked' : ''; ?>> Permitir el cambio del costo de insumo</label>
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
                                    </div>
                                    <div class="col-md-4">
                                        <div class="panel panel-default">
                                            <div class="panel-heading">
                                                <h3 class="panel-title">Informes por Correo Electrónico</h3>
                                            </div>
                                            <div class="panel-body">
                                                <label><input type="checkbox" name="usar_correo_electronico" value="1" <?php echo ($config['usar_correo_electronico'] ?? false) ? 'checked' : ''; ?>> Enviar Cierre de caja por correo electrónico</label>
                                                <div class="form-group">
                                                    <input type="email" class="form-control" name="correo_electronico" placeholder="yeldmartin@gmail.com" value="<?php echo $config['correo_electronico'] ?? ''; ?>">
                                                </div>
                                                <label><input type="checkbox" name="informar_anulaciones_mail" value="1" <?php echo ($config['informar_anulaciones_mail'] ?? false) ? 'checked' : ''; ?>> Informar anulaciones y comisiones de precio</label>
                                                <button type="button" class="btn btn-orange">Probar envío</button>
                                                <p class="p">Separar los correos por coma y luego presionar enter</p>
                                            </div>
                                        </div>
                                        <div class="panel panel-default">
                                            <div class="panel-heading">
                                                <h3 class="panel-title">Imprimir cierre de caja</h3>
                                            </div>
                                            <div class="panel-body">
                                                <label><input type="checkbox" name="ver_ingresos_egresos_cierre_caja" value="1" <?php echo ($config['ver_ingresos_egresos_cierre_caja'] ?? true) ? 'checked' : ''; ?>> Ingresos y Egresos</label>
                                                <label><input type="checkbox" name="ver_creditos_clientes_cierre_caja" value="1" <?php echo ($config['ver_creditos_clientes_cierre_caja'] ?? true) ? 'checked' : ''; ?>> Creditos a clientes</label>
                                                <label><input type="checkbox" name="ver_cortesias_clientes_cierre_caja" value="1" <?php echo ($config['ver_cortesias_clientes_cierre_caja'] ?? true) ? 'checked' : ''; ?>> Cortesías</label>
                                                <label><input type="checkbox" name="ver_anulaciones_eliminaciones_cierre_caja" value="1" <?php echo ($config['ver_anulaciones_eliminaciones_cierre_caja'] ?? true) ? 'checked' : ''; ?>> Anulaciones y eliminaciones</label>
                                                <label><input type="checkbox" name="ver_detalles_atencion_cierre_caja" value="1" <?php echo ($config['ver_detalles_atencion_cierre_caja'] ?? true) ? 'checked' : ''; ?>> Detalle de atención</label>
                                            </div>
                                        </div>
                                        <div class="panel panel-default">
                                            <div class="panel-body">
                                                <label><input type="checkbox" name="llenar_forma_pago_no_efectivo_cierre" value="1" <?php echo ($config['llenar_forma_pago_no_efectivo_cierre'] ?? true) ? 'checked' : ''; ?>> Llenar forma de pago que no sea efectivo en cierre manual</label>
                                                <label><input type="checkbox" name="cierre_caja_por_forma_pago" value="1" <?php echo ($config['cierre_caja_por_forma_pago'] ?? true) ? 'checked' : ''; ?>> Cierre de caja indicando cada forma de pago</label>
                                                <label><input type="checkbox" name="imprimir_cierre_caja_impresora_termica" value="1" <?php echo ($config['imprimir_cierre_caja_impresora_termica'] ?? true) ? 'checked' : ''; ?>> Imprimir cierre de caja</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="panel panel-default">
                                            <div class="panel-body">
                                                <div class="form-group">
                                                    <label>Bloquear la selecicon de las mesas luego de</label>
                                                    <input type="number" class="form-seg_bloqueo_seleccion_mesa input-small" name="seg_bloqueo_seleccion_mesa" value="<?php echo $config['seg_bloqueo_seleccion_mesa'] ?? 1000; ?>"> Seg.
                                                </div>
                                                <div class="form-group">
                                                    <label>Enviar pedido luego de</label>
                                                    <input type="number" class="form-control input-small" name="seg_envio_pedido_mesa" value="<?php echo $config['seg_envio_pedido_mesa'] ?? 1000; ?>"> Seg.
                                                </div>
                                                <label><input type="checkbox" name="sin_imagenes_recintos" value="1" <?php echo ($config['sin_imagenes_recintos'] ?? false) ? 'checked' : ''; ?>> Sin imagenes de recintos</label>
                                                <label><input type="checkbox" name="cajero_agregar_cualquier_mesa" value="1" <?php echo ($config['cajero_agregar_cualquier_mesa'] ?? false) ? 'checked' : ''; ?>> Cajero puede agregar a cualquier mesa</label>
                                                <label><input type="checkbox" name="validar_productos_cortesia" value="1" <?php echo ($config['validar_productos_cortesia'] ?? false) ? 'checked' : ''; ?>> Validar Cortesía venta Producto</label>
                                                <div class="form-group">
                                                    <label>Familia de favoritos </label>
                                                    <input type="number" class="form-control input-small" name="id_familia_favoritos" value="<?php echo $config['id_familia_favoritos'] ?? ''; ?>"> Seg.
                                                </div>
                                            </div>
                                        </div>
                                        <div class="panel panel-default">
                                            <div class="panel-heading">
                                                <h3 class="panel-title">Descuentos</h3>
                                            </div>
                                            <div class="panel-body">
                                                <div class="form-group">
                                                    <label>Máximo Permitido</label>
                                                    <input type="number" step="0.01" class="form-control" name="por_maximodescuento" value="<?php echo $config['por_maximodescuento'] ?? ''; ?>">
                                                </div>
                                                <label><input type="checkbox" name="requiere_cliente_descuento" value="1" <?php echo ($config['requiere_cliente_descuento'] ?? false) ? 'checked' : ''; ?>> Requerir Cliente</label>
                                            </div>
                                        </div>
                                        <div class="panel panel-default">
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
                                        <div class="panel panel-default">
                                            <div class="panel-heading">
                                                <h3 class="panel-title">REQUIERE USUARIO ADMINISTRADOR PARA:</h3>
                                            </div>
                                            <div class="panel-body">
                                                <label><input type="checkbox" name="requerir_usuario_admin_credito" value="1" <?php echo ($config['requerir_usuario_admin_credito'] ?? false) ? 'checked' : ''; ?>> Ventas a credito</label>
                                                <label><input type="checkbox" name="requerir_usuario_admin_cortesia" value="1" <?php echo ($config['requerir_usuario_admin_cortesia'] ?? false) ? 'checked' : ''; ?>> cortesias</label>
                                                <label><input type="checkbox" name="requerir_usuario_admin_descuento" value="1" <?php echo ($config['requerir_usuario_admin_descuento'] ?? false) ? 'checked' : ''; ?>> Descuentos en caja</label>
                                                <label><input type="checkbox" name="boton_apertura_caja_solo_admin" value="1" <?php echo ($config['boton_apertura_caja_solo_admin'] ?? false) ? 'checked' : ''; ?>> Apertura de gaveta en caja</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab Otras Configuraciones -->
                            <div id="otras" class="tab">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="panel panel-default">
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
                                                        <select name="id_tdocumento">
                                                            <?php foreach($bodegas as $b): ?>
                                                            <option value="<?= $b->id ?>" <?= $config['id_tdocumento']==$b->id?'selected':'' ?>>
                                                                <?= $b->nombre ?>
                                                            </option>
                                                            <?php endforeach ?>
                                                        </select>
                                                    </div>
                                                </div>
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
                                        <div class="panel panel-default">
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
                                                <label><input type="checkbox" name="permitir_retiro_fondo_fijo" value="1" <?php echo ($config['permitir_retiro_fondo_fijo'] ?? false) ? 'checked' : ''; ?>> Permitir retiro fondo fijo</label>
                                                <label><input type="checkbox" name="lprecio_x_sector" value="1" <?php echo ($config['lprecio_x_sector'] ?? false) ? 'checked' : ''; ?>> Listar precios por sector</label>
                                                <label><input type="checkbox" name="agregar_comision_a_venta" value="1" <?php echo ($config['agregar_comision_a_venta'] ?? false) ? 'checked' : ''; ?>> Agregar comision a venta</label>
                                                <label><input type="checkbox" name="pedir_anfitriona" value="1" <?php echo ($config['pedir_anfitriona'] ?? false) ? 'checked' : ''; ?>> Pedir anfitriona</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="panel panel-default">
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
                                    <div class="col-md-12">
                                        <div class="panel panel-default">
                                            <div class="panel-heading">
                                                <h3 class="panel-title">Facturacion electronica</h3>
                                            </div>
                                            <div class="panel-body">
                                                <label><input type="checkbox" name="habilitar_facturacion_electronica" value="1" <?php echo ($config['habilitar_facturacion_electronica'] ?? false) ? 'checked' : ''; ?>> Habilitar factura electronica</label>
                                                <div class="form-group">
                                                    <label>EMPRESA PROVEEDORA DTE</label>
                                                    <div class="radio-group">
                                                        <label><input type="radio" name="proveedor_dte" value="1" <?php echo ($config['acepta'] ?? false) ? 'checked' : ''; ?>> ACEPTA</label>
                                                        <label><input type="radio" name="proveedor_dte" value="2" <?php echo ($config['acierto'] ?? false) ? 'checked' : ''; ?>> ACIERTO</label>
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
                                                    <select class="form-control" name="id_ind_traslado_venta">
                                                        <option value="0">GUIA DE DESPACHO ELECTRONICA</option>
                                                        <option value="1">OPERACION CONSTITUYE VENTA</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Boleta Electrónica</label>
                                                    <select class="form-control" name="id_tdocumento_boleta_electronica">
                                                        <option value="0">BOLETA ELECTRONICA</option>
                                                    </select>
                                                </div>
                                                <label><input type="checkbox" name="imprimir_pdf_dte" value="1" <?php echo ($config['imprimir_pdf_dte'] ?? false) ? 'checked' : ''; ?>> Imprimir pdf</label>
                                                <div class="form-group">
                                                    <label>Imrpesora</label>
                                                    <input type="text" class="form-control" name="ruta_dte_pdf" value="<?php echo $config['ruta_dte_pdf'] ?? '/17.0.1/caja'; ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>Formato pdf</label>
                                                    <input type="text" class="form-control" name="formato_pdf_dte" value="<?php echo $config['formato_pdf_dte'] ?? '/17.0.1/caja'; ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>Ruta dte</label>
                                                    <input type="text" class="form-control" name="ruta_dte" value="<?php echo $config['ruta_dte'] ?? '/17.0.1/caja'; ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>Copias dte</label>
                                                    <input type="number" class="form-control" name="copias_dte" value="<?php echo $config['copias_dte'] ?? 1; ?>">
                                                </div>
                                                <label><input type="checkbox" name="imprimir_copia_cedible" value="1" <?php echo ($config['imprimir_copia_cedible'] ?? false) ? 'checked' : ''; ?>> Imprimir copia cedible</label>
                                                <label><input type="checkbox" name="enviar_dte_sii" value="1" <?php echo ($config['enviar_dte_sii'] ?? false) ? 'checked' : ''; ?>> enviar dte al sii</label>
                                                <div class="form-group">
                                                    <label>Timeout</label>
                                                    <input type="number" class="form-control" name="timeout_dte" value="<?php echo $config['timeout_dte'] ?? 30; ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="panel panel-default">
                                            <div class="panel-heading">
                                                <h3 class="panel-title">Certificacionn</h3>
                                            </div>
                                            <div class="panel-body">
                                                <div class="form-group">
                                                    <label>ciudad sii</label>
                                                    <input type="text" class="form-control" name="ciudad_sii" value="<?php echo $config['ciudad_sii'] ?? ''; ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>resolucion</label>
                                                    <input type="text" class="form-control" name="resolucion_sii" value="<?php echo $config['resolucion_sii'] ?? ''; ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>año resolucion</label>
                                                    <input type="number" class="form-control" name="anio_resolucion" value="<?php echo $config['anio_resolucion'] ?? ''; ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="panel panel-default">
                                            <div class="panel-heading">
                                                <h3 class="panel-title">Acceso facturacion</h3>
                                            </div>
                                            <div class="panel-body">
                                                <div class="form-group">
                                                    <label>Usuario</label>
                                                    <input type="text" class="form-control" name="usuario_sii" value="<?php echo $config['usuario_sii'] ?? ''; ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>contraseña</label>
                                                    <input type="password" class="form-control" name="password_sii" value="<?php echo $config['password_sii'] ?? ''; ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="panel panel-default">
                                            <div class="panel-heading">
                                                <h3 class="panel-title">PARAMETROS WEB</h3>
                                            </div>
                                            <div class="panel-body">
                                                <div class="form-group">
                                                    <label>ruta api dte</label>
                                                    <input type="text" class="form-control" name="ruta_api_dte" value="<?php echo $config['ruta_api_dte'] ?? ''; ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>ruta get pdf</label>
                                                    <input type="text" class="form-control" name="ruta_get_pdf" value="<?php echo $config['ruta_get_pdf'] ?? ''; ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>ruta dte codigo qr</label>
                                                    <input type="text" class="form-control" name="ruta_dte_qr" value="<?php echo $config['ruta_dte_qr'] ?? ''; ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>ruta consulta de folios</label>
                                                    <input type="text" class="form-control" name="ruta_consulta_folios" value="<?php echo $config['ruta_consulta_folios'] ?? ''; ?>">
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
                                        <div class="panel panel-default">
                                            <div class="panel-heading">
                                                <h3 class="panel-title">Datos de la Empresa</h3>
                                            </div>
                                            <div class="panel-body">
                                                <div class="row">
                                                    <div class="col-md-6 form-group">
                                                        <label>Rut</label>
                                                        <input type="text" class="form-control" name="rut_empresa" value="<?php echo $config['rut_empresa'] ?? ''; ?>">
                                                    </div>
                                                    <div class="col-md-6 form-group">
                                                        <label>DV</label>
                                                        <input type="text" class="form-control" name="dv_empresa" value="<?php echo $config['dv_empresa'] ?? ''; ?>">
                                                    </div>
                                                    <div class="col-md-12 form-group">
                                                        <label>razon social</label>
                                                        <input type="text" class="form-control" name="razon_social" value="<?php echo $config['razon_social'] ?? ''; ?>">
                                                    </div>
                                                    <div class="col-md-12 form-group">
                                                        <label>Nombre fantasia</label>
                                                        <input type="text" class="form-control" name="nombre_fantasia" value="<?php echo $config['nombre_fantasia'] ?? ''; ?>">
                                                    </div>
                                                    <div class="col-md-6 form-group">
                                                        <label>cod act. economica</label>
                                                        <input type="text" class="form-control" name="cod_actividad_economica" value="<?php echo $config['cod_actividad_economica'] ?? ''; ?>">
                                                    </div>
                                                    <div class="col-md-6 form-group">
                                                        <label>Giro</label>
                                                        <input type="text" class="form-control" name="giro" value="<?php echo $config['giro'] ?? ''; ?>">
                                                    </div>
                                                    <div class="col-md-12 form-group">
                                                        <label>Direccion</label>
                                                        <input type="text" class="form-control" name="direccion" value="<?php echo $config['direccion'] ?? ''; ?>">
                                                    </div>
                                                    <div class="col-md-6 form-group">
                                                        <label>Comuna</label>
                                                        <select class="form-control" name="comuna">
                                                            <option value="0">COMUNA</option>
                                                            <option value="1">LINARES</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 form-group">
                                                        <label>Correo</label>
                                                        <input type="email" class="form-control" name="correo_empresa" value="<?php echo $config['correo_empresa'] ?? ''; ?>">
                                                    </div>
                                                    <div class="col-md-6 form-group">
                                                        <label>Telefono</label>
                                                        <input type="text" class="form-control" name="telefono" value="<?php echo $config['telefono'] ?? ''; ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="panel panel-default">
                                            <div class="panel-heading">
                                                <h3 class="panel-title">Sucursal</h3>
                                            </div>
                                            <div class="panel-body">
                                                <div class="row">
                                                    <div class="col-md-6 form-group">
                                                        <label>Cod sii</label>
                                                        <input type="text" class="form-control" name="cod_sucursal_sii" value="<?php echo $config['cod_sucursal_sii'] ?? ''; ?>">
                                                    </div>
                                                    <div class="col-md-6 form-group">
                                                        <label>Nombre sucursal</label>
                                                        <input type="text" class="form-control" name="nombre_sucursal" value="<?php echo $config['nombre_sucursal'] ?? ''; ?>">
                                                    </div>
                                                    <div class="col-md-12 form-group">
                                                        <label>Direccion sucursal</label>
                                                        <input type="text" class="form-control" name="direccion_sucursal" value="<?php echo $config['direccion_sucursal'] ?? ''; ?>">
                                                    </div>
                                                    <div class="col-md-6 form-group">
                                                        <label>Comuna</label>
                                                        <select class="form-control" name="comuna_sucursal">
                                                            <option value="0">COMUNA</option>
                                                            <option value="1">LINARES</option>
                                                        </select>
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
    .tabs {
        padding: 0;
        margin-bottom: 20px;
    }
    .tablink {
        background: #f1f1f1;
        border: none;
        padding: 12px 20px;
        cursor: pointer;
        font-size: 14px;
        border-right: 1px solid #ccc;
    }
    .tablink:last-child { border-right: none; }
    .tablink.active {
        background: #5cb85c;
        color: white;
    }
    .tab {
        display: none;
    }
    .tab.active {
        display: block;
    }
    .p { font-size: 11px; color: #666; margin: 5px 0; }
    .input-small { width: 80px; display: inline-block; }
    .radio-group { display: flex; }
    .radio-group label { margin-right: 20px; }
</style>

<script>
    function openTab(evt, tabName) {
        var i, tabcontent, tablinks;
        tabcontent = document.getElementsByClassName("tab");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].className = tabcontent[i].className.replace(" active", "");
        }
        tablinks = document.getElementsByClassName("tablink");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(" active", "");
        }
        document.getElementById(tabName).className += " active";
        evt.currentTarget.className += " active";
    }

    $('#configForm').submit(function(e) {
        e.preventDefault();
        $.ajax({
            type: 'POST',
            url: $(this).attr('action'),
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                alert(response.status === 'success' ? 'Guardado exitosamente' : 'Error al guardar');
                if (response.status === 'success') {
                    location.reload();
                }
            },
            error: function() {
                alert('Error en la solicitud');
            }
        });
    });
</script>