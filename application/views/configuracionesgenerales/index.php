<!-- application/views/configuracionesgenerales/index.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuraciones Generales</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 0; 
            background-color: #f5f5f5;
        }
        .container {
            max-width: none;
            margin: 0;
            background: white;
        }
        .header {
            background: #e0e0e0;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #ccc;
        }
        .tabs {
            display: flex;
            background: #f1f1f1;
            border-bottom: 1px solid #ccc;
            padding: 0;
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
            background: red;
            color: white;
        }
        .tab {
            display: none;
            padding: 20px;
            min-height: 600px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
        }
        .tab.active {
            display: grid;
        }
        .panel {
            background: #fafafa;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin: 5px 0;
            height: fit-content;
        }
        .panel h3 {
            margin-top: 0;
            font-size: 14px;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .left-panel { grid-row: 1 / span 8; }
        .middle-panel-top { grid-row: 1 / span 4; }
        .middle-panel-bottom { grid-row: 5 / span 4; }
        .right-panel-top { grid-row: 1 / span 3; }
        .right-panel-middle { grid-row: 4 / span 3; }
        .right-panel-bottom { grid-row: 7 / span 3; }
        .full-width { grid-column: 1 / -1; grid-row: 9; }
        input, select, textarea {
            width: 100%;
            margin: 5px 0;
            padding: 6px;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 12px;
            box-sizing: border-box;
        }
        label {
            display: block;
            margin: 8px 0 2px 0;
            font-size: 12px;
            font-weight: bold;
        }
        input[type="checkbox"], input[type="radio"] {
            width: auto;
            margin-right: 5px;
        }
        button {
            padding: 8px 16px;
            margin: 5px 2px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        }
        .btn-red { background: red; color: white; }
        .btn-green { background: green; color: white; }
        .btn-orange { background: orange; color: white; }
        .btn-blue { background: blue; color: white; }
        .p { font-size: 11px; color: #666; margin: 5px 0; }
        .nota { background: #fff3cd; padding: 5px; border-radius: 3px; font-size: 11px; border-left: 3px solid #ffc107; }
        .secret { font-weight: bold; color: red; font-size: 11px; }
        .checkbox-group { display: flex; flex-wrap: wrap; }
        .checkbox-group label { flex: 1 1 45%; margin-bottom: 5px; }
        .days-group { display: grid; grid-template-columns: repeat(4, 1fr); gap: 5px; }
        .days-group label { font-weight: normal; margin: 0; }
        .radio-group { display: flex; }
        .radio-group label { margin-right: 20px; }
        .select-small { width: auto; }
        .input-small { width: 80px; display: inline-block; }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>CONFIGURACIONES</h1>
            <div>
                <button type="submit" form="configForm" class="btn-red">Grabar</button>
                <button type="button" onclick="location.reload();" class="btn-green">Cancelar</button>
            </div>
        </div>
        <div class="tabs">
            <button class="tablink active" onclick="openTab(event, 'general')">General</button>
            <button class="tablink" onclick="openTab(event, 'otras')">Otras Configuraciones</button>
            <button class="tablink" onclick="openTab(event, 'facturacion')">Facturación Electrónica</button>
            <button class="tablink" onclick="openTab(event, 'empresa')">Datos de la Empresa</button>
        </div>

        <form id="configForm" method="post" action="<?php echo site_url('ConfiguracionesGenerales/guardar'); ?>">
            <!-- Tab General -->
            <div id="general" class="tab active">
                <div class="panel left-panel">
                    <h3>Ver en Caja</h3>
                    <label><input type="checkbox" name="ver_delivery_caja" value="1" <?php echo ($config['ver_delivery_caja'] ?? true) ? 'checked' : ''; ?>> DELIVERY</label>
                    <label><input type="checkbox" name="ver_barra_caja" value="1" <?php echo ($config['ver_barra_caja'] ?? true) ? 'checked' : ''; ?>> Venta Directa Caja</label>
                    <label><input type="checkbox" name="ver_barra_caja" value="1" <?php echo ($config['ver_barra_caja'] ?? true) ? 'checked' : ''; ?>> Numero Pedido</label>
                    <label><input type="checkbox" name="ver_barra_caja" value="1" <?php echo ($config['ver_barra_caja'] ?? true) ? 'checked' : ''; ?>> Nombre Cliente</label>
                    <label><input type="checkbox" name="ver_barra_caja" value="1" <?php echo ($config['ver_barra_caja'] ?? true) ? 'checked' : ''; ?>> Garzon</label>
                    <h3>Configuraciones Caja</h3>
                    <label><input type="text" name="garzon_emite_precuenta" value="1" <?php echo ($config['garzon_emite_precuenta'] ?? true) ? 'checked' : ''; ?>> Impresion grafica y precuentas</label>
                    <input type="text" name="num_pedido" value="<?php echo $config['num_pedido'] ?? ''; ?>">
                    <label><input type="text" name="garzon_emite_precuenta" value="1" <?php echo ($config['garzon_emite_precuenta'] ?? true) ? 'checked' : ''; ?>> Impresora Local</label>
                    <input type="text" name="num_pedido" value="<?php echo $config['num_pedido'] ?? ''; ?>">
                    <label><input type="checkbox" name="ver_barra_caja" value="1" <?php echo ($config['ver_barra_caja'] ?? true) ? 'checked' : ''; ?>> Imprimir comprobante de venta</label>
                    <h3>Precuentas</h3>
                    <label><input type="checkbox" name="omitir_productos_sin_precio_precuenta" value="1" <?php echo ($config['omitir_productos_sin_precio_precuenta'] ?? false) ? 'checked' : ''; ?>> Garzon Puede emitir Precuenta</label>
                    <label><input type="checkbox" name="mostrar_valor_propina_precuenta" value="1" <?php echo ($config['mostrar_valor_propina_precuenta'] ?? false) ? 'checked' : ''; ?>> Usar Impresora equipo local</label>
                    <label><input type="checkbox" name="preguntar_por_calculo_propina_en_pago" value="1" <?php echo ($config['preguntar_por_calculo_propina_en_pago'] ?? true) ? 'checked' : ''; ?>> Ocultar monto de venta a garzon</label>
                    <label><input type="checkbox" name="mostrar_boton_propina_garzon" value="1" <?php echo ($config['mostrar_boton_propina_garzon'] ?? false) ? 'checked' : ''; ?>> No imprimir productos sin valor</label>
                    <h3>Propinas</h3>
                    <label><input type="checkbox" name="omitir_productos_sin_precio_precuenta" value="1" <?php echo ($config['omitir_productos_sin_precio_precuenta'] ?? false) ? 'checked' : ''; ?>> Mostar valor de propína en precuenta</label>
                    <label><input type="checkbox" name="mostrar_valor_propina_precuenta" value="1" <?php echo ($config['mostrar_valor_propina_precuenta'] ?? false) ? 'checked' : ''; ?>> Preguntar por calculo de propina en pago</label>
                    <label><input type="checkbox" name="preguntar_por_calculo_propina_en_pago" value="1" <?php echo ($config['preguntar_por_calculo_propina_en_pago'] ?? true) ? 'checked' : ''; ?>> Mostrar boton ver propina</label>
                    <label><input type="checkbox" name="mostrar_boton_propina_garzon" value="1" <?php echo ($config['mostrar_boton_propina_garzon'] ?? false) ? 'checked' : ''; ?>> Calcular propina para delivery</label>
                    <label><input type="checkbox" name="mostrar_boton_propina_garzon" value="1" <?php echo ($config['mostrar_boton_propina_garzon'] ?? false) ? 'checked' : ''; ?>> Propina en venta directa</label>
                    <h3>Otros restorant</h3>
                    <label><input type="checkbox" name="abrir_gaveta_solo_por_pago_efectivo" value="1" <?php echo ($config['abrir_gaveta_solo_por_pago_efectivo'] ?? true) ? 'checked' : ''; ?>> Permitir el cambio del costo de insumo</label>
                    <label><input type="checkbox" name="omitir_mensaje_comprobante_no_valido_como_boleta" value="1" <?php echo ($config['omitir_mensaje_comprobante_no_valido_como_boleta'] ?? false) ? 'checked' : ''; ?>> Ordenar Carta</label>
                    <label><input type="text" name="garzon_emite_precuenta" value="1" <?php echo ($config['garzon_emite_precuenta'] ?? true) ? 'checked' : ''; ?>> Mesa para venta directa</label>
                    <input type="text" name="num_pedido" value="<?php echo $config['num_pedido'] ?? ''; ?>">
                    <label><input type="text" name="garzon_emite_precuenta" value="1" <?php echo ($config['garzon_emite_precuenta'] ?? true) ? 'checked' : ''; ?>> Impresora para cierre de caja</label>
                    <input type="text" name="num_pedido" value="<?php echo $config['num_pedido'] ?? ''; ?>">
                    <label><input type="checkbox" name="omitir_mensaje_comprobante_no_valido_como_boleta" value="1" <?php echo ($config['omitir_mensaje_comprobante_no_valido_como_boleta'] ?? false) ? 'checked' : ''; ?>> Imprimir comprobantes de anulacion</label>
                    <label><input type="checkbox" name="omitir_mensaje_comprobante_no_valido_como_boleta" value="1" <?php echo ($config['omitir_mensaje_comprobante_no_valido_como_boleta'] ?? false) ? 'checked' : ''; ?>> Imprimir Valor productos en destino de impresion</label>
                </div>
                <div class="panel middle-panel-top">
                    <h3>Informes por Correo Electrónico</h3>
                    <label><input type="checkbox" name="usar_correo_electronico" value="1" <?php echo ($config['usar_correo_electronico'] ?? false) ? 'checked' : ''; ?>> Enviar Cierre de caja por correo electrónico</label>
                    <input type="email" name="correo_electronico" placeholder="yeldmartin@gmail.com" value="<?php echo $config['correo_electronico'] ?? ''; ?>">
                    <label><input type="checkbox" name="informar_anulaciones_mail" value="1" <?php echo ($config['informar_anulaciones_mail'] ?? false) ? 'checked' : ''; ?>> Informar anulaciones y comisiones de precio</label>
                    <button type="button" class="btn-orange">Probar envío</button>
                    <p class="p">Separar los correos por coma y luego presionar enter</p>
                </div>
                <div class="panel middle-panel-bottom">
                    <h3>Imprimir cierre de caja</h3>
                    <label><input type="checkbox" name="ver_creditos_a_clientes_cierre_caja" value="1" <?php echo ($config['ver_creditos_a_clientes_cierre_caja'] ?? true) ? 'checked' : ''; ?>> Ingresos y Egresos</label>
                    <label><input type="checkbox" name="ver_creditos_a_clientes_cierre_caja" value="1" <?php echo ($config['ver_creditos_a_clientes_cierre_caja'] ?? true) ? 'checked' : ''; ?>> Creditos a clientes</label>
                    <label><input type="checkbox" name="ver_cortesias_cierre_caja" value="1" <?php echo ($config['ver_cortesias_cierre_caja'] ?? true) ? 'checked' : ''; ?>> Cortesías</label>
                    <label><input type="checkbox" name="ver_anulaciones_eliminaciones_cierre_caja" value="1" <?php echo ($config['ver_anulaciones_eliminaciones_cierre_caja'] ?? true) ? 'checked' : ''; ?>> Anulaciones y eliminaciones</label>
                    <label><input type="checkbox" name="ver_detalle_atencion_cierre_caja" value="1" <?php echo ($config['ver_detalle_atencion_cierre_caja'] ?? true) ? 'checked' : ''; ?>> Detalle de atención</label>
                </div>
                <div class="panel middle-panel-bottom">
                    <label><input type="checkbox" name="ver_creditos_a_clientes_cierre_caja" value="1" <?php echo ($config['ver_creditos_a_clientes_cierre_caja'] ?? true) ? 'checked' : ''; ?>> Llenar forma de pago que no sea efectivo en cierre manual</label>
                    <label><input type="checkbox" name="ver_creditos_a_clientes_cierre_caja" value="1" <?php echo ($config['ver_creditos_a_clientes_cierre_caja'] ?? true) ? 'checked' : ''; ?>> Cierre de caja indicando cada forma de pago</label>
                    <label><input type="checkbox" name="ver_cortesias_cierre_caja" value="1" <?php echo ($config['ver_cortesias_cierre_caja'] ?? true) ? 'checked' : ''; ?>> Imprimir cierre de caja</label>
                </div>
                <div class="panel right-panel-top">
                    <label><input type="text" name="sin_imagenes_de_recintos" value="1" <?php echo ($config['sin_imagenes_de_recintos'] ?? false) ? 'checked' : ''; ?>> Bloquear la selecicon de las mesas luego de</label>
                    <input type="number" name="seg_bloqueo_seleccion_mesa" value="<?php echo $config['seg_bloqueo_seleccion_mesa'] ?? 1000; ?>" class="input-small"> Seg.
                    <label><input type="text" name="sin_imagenes_de_recintos" value="1" <?php echo ($config['sin_imagenes_de_recintos'] ?? false) ? 'checked' : ''; ?>> Enviar pedido luego de</label>
                    <input type="number" name="sin_imagenes_de_recintos" value="<?php echo $config['sin_imagenes_de_recintos'] ?? 1000; ?>" class="input-small"> Seg.
                    <label><input type="checkbox" name="cajero_agrega_cualquier_mesa" value="1" <?php echo ($config['cajero_agrega_cualquier_mesa'] ?? false) ? 'checked' : ''; ?>> Sin imagenes de recintos</label>
                    <label><input type="checkbox" name="cajero_agrega_cualquier_mesa" value="1" <?php echo ($config['cajero_agrega_cualquier_mesa'] ?? false) ? 'checked' : ''; ?>> Cajero puede agregar a cualquier mesa</label>
                    <label><input type="checkbox" name="validar_cortesia_venta_producto" value="1" <?php echo ($config['validar_cortesia_venta_producto'] ?? false) ? 'checked' : ''; ?>> Validar Cortesía venta Producto</label>
                    <label><input type="text" name="sin_imagenes_de_recintos" value="1" <?php echo ($config['sin_imagenes_de_recintos'] ?? false) ? 'checked' : ''; ?>> Familia de favoritos </label>
                    <input type="number" name="seg_bloqueo_seleccion_mesa" value="<?php echo $config['seg_bloqueo_seleccion_mesa'] ?? 1000; ?>" class="input-small"> Seg.
                </div>
                <div class="panel right-panel-middle">
                    <h3>Descuentos</h3>
                    <label><input type="text" name="happy_hour" value="1" <?php echo ($config['happy_hour'] ?? false) ? 'checked' : ''; ?>> Máximo Permitido</label>
                    <input type="time" name="inicio_happy_hour" value="<?php echo $config['inicio_happy_hour'] ?? ''; ?>">
                    <label><input type="checkbox" name="validar_cortesia_venta_producto" value="1" <?php echo ($config['validar_cortesia_venta_producto'] ?? false) ? 'checked' : ''; ?>> Requerir Cliente</label>
                </div>
                <div class="panel right-panel-middle">
                    <label><input type="text" name="happy_hour" value="1" <?php echo ($config['happy_hour'] ?? false) ? 'checked' : ''; ?>> Clave de administrador</label>
                    <input type="time" name="inicio_happy_hour" value="<?php echo $config['inicio_happy_hour'] ?? ''; ?>">
                    <label><input type="checkbox" name="validar_cortesia_venta_producto" value="1" <?php echo ($config['validar_cortesia_venta_producto'] ?? false) ? 'checked' : ''; ?>> Mostrar clave</label>
                    <label><input type="checkbox" name="validar_cortesia_venta_producto" value="1" <?php echo ($config['validar_cortesia_venta_producto'] ?? false) ? 'checked' : ''; ?>> Requerir clave admin para anulacion</label>
                </div>
                <div class="panel right-panel-middle">
                    <h3>REQUIERE USUARIO ADMINISTRADOR PARA:</h3>
                    <label><input type="checkbox" name="requerir_usuario_admin_cortesia" value="1" <?php echo ($config['requerir_usuario_admin_cortesia'] ?? false) ? 'checked' : ''; ?>> Ventas a credito</label>
                    <label><input type="checkbox" name="requerir_usuario_admin_credito" value="1" <?php echo ($config['requerir_usuario_admin_credito'] ?? false) ? 'checked' : ''; ?>> cortesias</label>
                    <label><input type="checkbox" name="requerir_usuario_admin_descuento" value="1" <?php echo ($config['requerir_usuario_admin_descuento'] ?? false) ? 'checked' : ''; ?>> Descuentos en caja</label>
                    <label><input type="checkbox" name="boton_apertura_caja_solo_admin" value="1" <?php echo ($config['boton_apertura_caja_solo_admin'] ?? false) ? 'checked' : ''; ?>> Apertura de gaveta en caja</label>
                </div>




                



            <!-- Tab Otras Configuraciones -->
            <div id="otras" class="tab">
                <div class="panel left-panel">
                    <h3>Configuración por defecto de Compras</h3>
                    <label>Documento de compra</label>
                    <select name="id_tdocumento_compra">
                        <option value="0">DOCUMENTO COMPRA</option>
                        <option value="1">FACTURA ELECTRONICA</option>
                    </select>
                    <label>Formas de pago</label>
                    <select name="id_fpago_compra">
                        <option value="0">FORMA DE PAGO</option>
                        <option value="1">EFECTIVO</option>
                    </select>

                    <h3>Créditos</h3>                 
                    <label>Formas de pago credito a clientes</label>
                    <select name="id_fpago_creditodirecto">
                        <option value="0">FORMA DE PAGO crédito a clientes</option>
                        <option value="1">CREDITO DE CONFIANZA</option>
                    </select>
                    <label>Centro costo pago credito</label>
                    <select name="id_pagocreditodirecto">
                        <option value="0">CENTRO DE COSTO pago</option>
                        <option value="1">PAGO CREDITO CONFIANZA</option>
                    </select>

                    <h3>Bodegas</h3>
                    <label>Compras</label>
                    <select name="id_bodega_compra">
                        <option value="0">COMPRAS</option>
                        <option value="1">BODEGA PRINCIPAL</option>
                    </select>
                    <label>Ventas</label>
                    <select name="id_bodega_venta">
                        <option value="0">VENTAS</option>
                        <option value="1">BODEGA PRINCIPAL</option>
                    </select>
                    <label>Merma</label>
                    <select name="id_bodega_merma_inventario">
                        <option value="0">MERMA (insumo)</option>
                        <option value="1">BODEGA PRINCIPAL</option>
                    </select>

                  
                <div class="panel middle-panel-top">
                    <h3>Configuración por defecto de Venta</h3>

                    <label>cliente boleta</label>
                    <select name="id_cliente_publico">
                        <option value="0">CLIENTE BOLETA</option>
                        <option value="1">CLIENTE HABITUAL</option>
                    </select>
                    <label>Lista de precio publico</label>
                    <select name="id_lpreciopublico">
                        <option value="0">LISTA DE PRECIO A PUBLICO</option>
                        <option value="1">LISTA PUBLICA</option>
                    </select>
                    <label>documento de venta</label>
                    <select name="id_docventa_defecto">
                        <option value="0">DOCUMENTO DE VENTA (Boleta)</option>
                        <option value="1">BOLETA ELECTRONICA</option>
                    </select>
                    <label>Forma de pago por defecto</label>
                    <select name="id_fpago_defecto">
                        <option value="0">FORMA DE PAGO por defecto</option>
                        <option value="1">EFECTIVO</option>
                    </select>
                    <label>Solo otorgar vuelto por pago</label>
                    <select name="vueltofpago">
                        <option value="0">SOLICITAR VUELTO por pago(efectivo)</option>
                        <option value="1">EFECTIVO</option>
                    </select>
                    <label>Documento pago cortesias</label>
                    <select name="id_tdocumento_comprobante_interno">
                        <option value="0">DOCUMENTO PAGO CORTESIA</option>
                        <option value="1">COMPROBANTE INTERNO</option>
                    </select>
                    <label>impuestos iva</label>
                    <select name="id_tdocumento_comprobante_interno">
                        <option value="0">DOCUMENTO PAGO CORTESIA</option>
                        <option value="1">COMPROBANTE INTERNO</option>
                    </select>
                    <label><input type="checkbox" name="requerir_usuario_admin_cortesia" value="1" <?php echo ($config['requerir_usuario_admin_cortesia'] ?? false) ? 'checked' : ''; ?>> Permitir retiro fondo fijo</label>
                    <label>Documento Comprobante interno</label>
                    <select name="id_tdocumento_comprobante_interno">
                        <option value="0">DOCUMENTO PAGO CORTESIA</option>
                        <option value="1">COMPROBANTE INTERNO</option>
                    </select>
                    <label><input type="checkbox" name="requerir_usuario_admin_cortesia" value="1" <?php echo ($config['requerir_usuario_admin_cortesia'] ?? false) ? 'checked' : ''; ?>> Listar precios por sector</label>
                    <label><input type="checkbox" name="requerir_usuario_admin_cortesia" value="1" <?php echo ($config['requerir_usuario_admin_cortesia'] ?? false) ? 'checked' : ''; ?>> Agregar comision a venta</label>
                    <label><input type="checkbox" name="requerir_usuario_admin_cortesia" value="1" <?php echo ($config['requerir_usuario_admin_cortesia'] ?? false) ? 'checked' : ''; ?>> Pedir anfitriona</label>

                </div>
                <div class="panel middle-panel-bottom">
                    <label><input type="checkbox" name="requerir_usuario_admin_cortesia" value="1" <?php echo ($config['requerir_usuario_admin_cortesia'] ?? false) ? 'checked' : ''; ?>> Inventario insumos cierre de caja</label>
                    <label><input type="checkbox" name="requerir_usuario_admin_cortesia" value="1" <?php echo ($config['requerir_usuario_admin_cortesia'] ?? false) ? 'checked' : ''; ?>> Permitir repetir el producto en el menu</label>
                    <label><input type="checkbox" name="requerir_usuario_admin_cortesia" value="1" <?php echo ($config['requerir_usuario_admin_cortesia'] ?? false) ? 'checked' : ''; ?>> Pedir comensales en mesa</label>
                    <label><input type="checkbox" name="requerir_usuario_admin_cortesia" value="1" <?php echo ($config['requerir_usuario_admin_cortesia'] ?? false) ? 'checked' : ''; ?>> Imprimir hora en productos del pedido</label>
                </div>
            </div>



            <!-- Tab Facturación Electrónica -->
            <div id="facturacion" class="tab">
                <div class="panel full-width">
                    <h3>Facturacion electronica</h3>
                    <label><input type="checkbox" name="requerir_usuario_admin_cortesia" value="1" <?php echo ($config['requerir_usuario_admin_cortesia'] ?? false) ? 'checked' : ''; ?>> Habilitar factura electronica</label>
                    <label>EMPRESA PROVEEDORA DTE</label>
                    <div class="radio-group">
                        <label><input type="radio" name="acepta" value="1" <?php echo ($config['acepta'] ?? false) ? 'checked' : ''; ?>> ACEPTA</label>
                        <label><input type="radio" name="acierto" value="1" <?php echo ($config['acierto'] ?? false) ? 'checked' : ''; ?>> ACIERTO</label>
                    </div>
                    <label>Factura Electrónica</label>
                    <select name="id_tdocumento_factura">
                        <option value="0">FACTURA ELECTRONICA</option>
                    </select>
                    <label>Nota de Crédito Electrónica</label>
                    <select name="id_tdocumento_nota_credito">
                        <option value="0">NOTA DE CREDITO ELECTRONICA</option>
                        <option value="1">NOTA DE CREDITO FACTURA ELECTRONICA</option>
                    </select>
                    <label>Guía de Despacho Electrónica</label>
                    <select name="id_tdocumento_guia_despacho">
                        <option value="0">GUIA DE DESPACHO ELECTRONICA</option>
                        <option value="1">OPERACION CONSTITUYE VENTA</option>
                    </select>
                    <label>Ind traslado venta factura electronica</label>
                    <select name="id_tdocumento_guia_despacho">
                        <option value="0">GUIA DE DESPACHO ELECTRONICA</option>
                        <option value="1">OPERACION CONSTITUYE VENTA</option>
                    </select>
                    <label>Boleta Electrónica</label>
                    <select name="id_tdocumento_boleta_electronica">
                        <option value="0">BOLETA ELECTRONICA</option>
                    </select>
                    <label>Imrpesora</label>
                    <label><input type="checkbox" name="requerir_usuario_admin_cortesia" value="1" <?php echo ($config['requerir_usuario_admin_cortesia'] ?? false) ? 'checked' : ''; ?>> Imprimir pdf</label>
                    <input type="text" name="ruta_dte_pdf" value="<?php echo $config['ruta_dte_pdf'] ?? '/17.0.1/caja'; ?>">
                    <label>Formato pdf</label>
                    <input type="text" name="ruta_dte_pdf" value="<?php echo $config['ruta_dte_pdf'] ?? '/17.0.1/caja'; ?>">
                    <label>Ruta dte</label>
                    <input type="text" name="ruta_dte_pdf" value="<?php echo $config['ruta_dte_pdf'] ?? '/17.0.1/caja'; ?>">
                    <label>Copias dte</label>
                    <input type="text" name="ruta_dte_pdf" value="<?php echo $config['ruta_dte_pdf'] ?? '/17.0.1/caja'; ?>">
                    <label><input type="checkbox" name="requerir_usuario_admin_cortesia" value="1" <?php echo ($config['requerir_usuario_admin_cortesia'] ?? false) ? 'checked' : ''; ?>> Imprimir copia cedible</label>
                    <label><input type="checkbox" name="requerir_usuario_admin_cortesia" value="1" <?php echo ($config['requerir_usuario_admin_cortesia'] ?? false) ? 'checked' : ''; ?>> enviar dte al sii</label>
                    <label>Timeout</label>
                    <input type="text" name="ruta_dte_pdf" value="<?php echo $config['ruta_dte_pdf'] ?? '/17.0.1/caja'; ?>">

                    <h3>Certificacionn</h3>
                    <label>ciudad sii</label>
                    <input type="text" name="ruta_dte_pdf" value="<?php echo $config['ruta_dte_pdf'] ?? '/17.0.1/caja'; ?>">
                    <label>resolucion</label>
                    <input type="text" name="ruta_dte_pdf" value="<?php echo $config['ruta_dte_pdf'] ?? '/17.0.1/caja'; ?>">
                    <label>año resolucion</label>
                    <input type="text" name="ruta_dte_pdf" value="<?php echo $config['ruta_dte_pdf'] ?? '/17.0.1/caja'; ?>">
                    <h3>Acceso facturacion</h3>
                    <label>Usuario</label>
                    <input type="text" name="ruta_dte_pdf" value="<?php echo $config['ruta_dte_pdf'] ?? '/17.0.1/caja'; ?>">
                    <label>contraseña</label>
                    <input type="text" name="ruta_dte_pdf" value="<?php echo $config['ruta_dte_pdf'] ?? '/17.0.1/caja'; ?>">

                    <h3>PARAMETROS WEB</h3>
                    <label>ruta api dte</label>
                    <input type="text" name="ruta_dte_pdf" value="<?php echo $config['ruta_dte_pdf'] ?? '/17.0.1/caja'; ?>">
                    <label>ruta get pdf</label>
                    <input type="text" name="ruta_dte_pdf" value="<?php echo $config['ruta_dte_pdf'] ?? '/17.0.1/caja'; ?>">
                    <label>ruta dte codigo qr</label>
                    <input type="text" name="ruta_dte_pdf" value="<?php echo $config['ruta_dte_pdf'] ?? '/17.0.1/caja'; ?>">
                    <label>ruta consulta de folios</label>
                    <input type="text" name="ruta_dte_pdf" value="<?php echo $config['ruta_dte_pdf'] ?? '/17.0.1/caja'; ?>">
                </div>
            </div>



            <!-- Tab Datos de la Empresa -->
            <div id="empresa" class="tab">
                <div class="panel full-width">
                    <h3>Datos de la Empresa</h3>
                    <label>Rut</label>
                    <input type="text" name="ruta_dte_pdf" value="<?php echo $config['ruta_dte_pdf'] ?? '/17.0.1/caja'; ?>">
                    <input type="text" name="ruta_dte_pdf" value="<?php echo $config['ruta_dte_pdf'] ?? '/17.0.1/caja'; ?>">
                    <label>razon social</label>
                    <input type="text" name="ruta_dte_pdf" value="<?php echo $config['ruta_dte_pdf'] ?? '/17.0.1/caja'; ?>">
                    <label>Nombre fantasia</label>
                    <input type="text" name="ruta_dte_pdf" value="<?php echo $config['ruta_dte_pdf'] ?? '/17.0.1/caja'; ?>">
                    <label>cod act. economica</label>
                    <input type="text" name="ruta_dte_pdf" value="<?php echo $config['ruta_dte_pdf'] ?? '/17.0.1/caja'; ?>">
                    <label>Giro</label>
                    <input type="text" name="ruta_dte_pdf" value="<?php echo $config['ruta_dte_pdf'] ?? '/17.0.1/caja'; ?>">
                    <label>Direccion</label>
                    <input type="text" name="ruta_dte_pdf" value="<?php echo $config['ruta_dte_pdf'] ?? '/17.0.1/caja'; ?>">
                    <label>Comuna</label>
                    <select name="comuna">
                        <option value="0">COMUNA</option>
                        <option value="1">LINRES</option>
                    </select>
                    <label>Correo</label>
                    <input type="text" name="ruta_dte_pdf" value="<?php echo $config['ruta_dte_pdf'] ?? '/17.0.1/caja'; ?>">
                    <label>Telefono</label>
                    <input type="text" name="ruta_dte_pdf" value="<?php echo $config['ruta_dte_pdf'] ?? '/17.0.1/caja'; ?>">
                    
                    <h3>Sucursal</h3>
                    <label>Cod sii</label>
                    <input type="text" name="ruta_dte_pdf" value="<?php echo $config['ruta_dte_pdf'] ?? '/17.0.1/caja'; ?>">
                    <label>Nombre sucursal</label>
                    <input type="text" name="ruta_dte_pdf" value="<?php echo $config['ruta_dte_pdf'] ?? '/17.0.1/caja'; ?>">
                    <label>Direccion sucursal</label>
                    <input type="text" name="ruta_dte_pdf" value="<?php echo $config['ruta_dte_pdf'] ?? '/17.0.1/caja'; ?>">
                    <label>Comuna</label>
                    <select name="comuna">
                        <option value="0">COMUNA</option>
                        <option value="1">LINRES</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

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
</body>
</html>