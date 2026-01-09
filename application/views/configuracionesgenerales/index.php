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

        <form id="configForm" method="post" action="<?php echo site_url('ConfiguracionesGenerales/guardar'); ?>">
            <!-- Tab General -->
            <div id="general" class="tab active">
                <div class="panel left-panel">
                    <h3>Ver en Caja</h3>
                    <label><input type="checkbox" name="delivery" value="1" <?php echo ($config['ver_delivery_caja'] ?? true) ? 'checked' : ''; ?>> DELIVERY</label>
                    <label><input type="checkbox" name="ventaDirecta" value="1" <?php echo ($config[''] ?? true) ? 'checked' : ''; ?>> Venta Directa Caja</label>
                    <label><input type="checkbox" name="numPedido" value="1" <?php echo ($config[''] ?? true) ? 'checked' : ''; ?>> Numero Pedido</label>
                    <label><input type="checkbox" name="nombreCliente" value="1" <?php echo ($config['mostrar_nombre_cliente_venta'] ?? true) ? 'checked' : ''; ?>> Nombre Cliente</label>
                    <label><input type="checkbox" name="nombreGarzon" value="1" <?php echo ($config['mostrar_nombre_garzon_venta'] ?? true) ? 'checked' : ''; ?>> Garzon</label>
                    <h3>Configuraciones Caja</h3>
                    <label><input type="text" name="impresionGrafica" value="1" <?php echo ($config[''] ?? true) ? 'checked' : ''; ?>> Impresion grafica y precuentas</label>
                    <input type="text" name="txtimpresionGrafica" value="<?php echo $config[''] ?? ''; ?>">
                    <label><input type="text" name="impresionLocal" value="1" <?php echo ($config[''] ?? true) ? 'checked' : ''; ?>> Impresora Local</label>
                    <input type="text" name="txtimpresionLocal" value="<?php echo $config[''] ?? ''; ?>">
                    <label><input type="checkbox" name="comprobanteVenta" value="1" <?php echo ($config['imprimir_comprobante_anulacion '] ?? true) ? 'checked' : ''; ?>> Imprimir comprobante de venta</label>
                    <h3>Precuentas</h3>
                    <label><input type="checkbox" name="precuentaGarzon" value="1" <?php echo ($config['garzon_emite_precuenta '] ?? false) ? 'checked' : ''; ?>> Garzon Puede emitir Precuenta</label>
                    <label><input type="checkbox" name="impresoraLocal" value="1" <?php echo ($config[''] ?? false) ? 'checked' : ''; ?>> Usar Impresora equipo local</label>
                    <label><input type="checkbox" name="montoGarzon" value="1" <?php echo ($config['ocultar_cuenta_garzon '] ?? true) ? 'checked' : ''; ?>> Ocultar monto de venta a garzon</label>
                    <label><input type="checkbox" name="montoSinValor" value="1" <?php echo ($config[''] ?? false) ? 'checked' : ''; ?>> No imprimir productos sin valor</label>
                    <h3>Propinas</h3>
                    <label><input type="checkbox" name="propinaPrecuenta" value="1" <?php echo ($config[''] ?? false) ? 'checked' : ''; ?>> Mostar valor de propína en precuenta</label>
                    <label><input type="checkbox" name="calculoPropina" value="1" <?php echo ($config['calcular_valor_propina '] ?? false) ? 'checked' : ''; ?>> Preguntar por calculo de propina en pago</label>
                    <label><input type="checkbox" name="botonPropina" value="1" <?php echo ($config[''] ?? true) ? 'checked' : ''; ?>> Mostrar boton ver propina</label>
                    <label><input type="checkbox" name="calculoPropinaDelivery" value="1" <?php echo ($config['propina_en_delivery'] ?? false) ? 'checked' : ''; ?>> Calcular propina para delivery</label>
                    <label><input type="checkbox" name="propinaEnVentaDirecta" value="1" <?php echo ($config['propina_en_venta_directa '] ?? false) ? 'checked' : ''; ?>> Propina en venta directa</label>
                    <h3>Otros restorant</h3>
                    <label><input type="checkbox" name="cambioCostoInsumo" value="1" <?php echo ($config[''] ?? true) ? 'checked' : ''; ?>> Permitir el cambio del costo de insumo</label>
                    <label><input type="checkbox" name="ordenarCarta" value="1" <?php echo ($config['ordenar_carta '] ?? false) ? 'checked' : ''; ?>> Ordenar Carta</label>
                    <label><input type="text" name="mesaDirecta" value="1" <?php echo ($config['id_mesa_venta_directa'] ?? true) ? 'checked' : ''; ?>> Mesa para venta directa</label>
                    <input type="text" name="txtmesaDirecta" value="<?php echo $config['id_mesa_venta_directa'] ?? ''; ?>">
                    <label><input type="text" name="impresoraCierreCaja" value="1" <?php echo ($config[''] ?? true) ? 'checked' : ''; ?>> Impresora para cierre de caja</label>
                    <input type="text" name="txtimpresoraCierreCaja" value="<?php echo $config[''] ?? ''; ?>">
                    <label><input type="checkbox" name="comprobanteAnulacion" value="1" <?php echo ($config['imprimir_comprobante_anulacion'] ?? false) ? 'checked' : ''; ?>> Imprimir comprobantes de anulacion</label>
                    <label><input type="checkbox" name="valorProductos" value="1" <?php echo ($config['imprimir_valor_producto_cocina'] ?? false) ? 'checked' : ''; ?>> Imprimir Valor productos en destino de impresion</label>
                </div>
                <div class="panel middle-panel-top">
                    <h3>Informes por Correo Electrónico</h3>
                    <label><input type="checkbox" name="cierreCajaCorreo" value="1" <?php echo ($config[''] ?? false) ? 'checked' : ''; ?>> Enviar Cierre de caja por correo electrónico</label>
                    <input type="email" name="txtcierreCajaCorreo" placeholder="yeldmartin@gmail.com" value="<?php echo $config['correo_electronico'] ?? ''; ?>">
                    <label><input type="checkbox" name="anulacionesCorreo" value="1" <?php echo ($config['informar_anulaciones_mail'] ?? false) ? 'checked' : ''; ?>> Informar anulaciones y comisiones de precio</label>
                    <button type="button" class="btn-orange">Probar envío</button>
                    <p class="p">Separar los correos por coma y luego presionar enter</p>
                </div>
                <div class="panel middle-panel-bottom">
                    <h3>Imprimir cierre de caja</h3>
                    <label><input type="checkbox" name="ingresosYEgresos" value="1" <?php echo ($config[''] ?? true) ? 'checked' : ''; ?>> Ingresos y Egresos</label>
                    <label><input type="checkbox" name="creditosClientes" value="1" <?php echo ($config[''] ?? true) ? 'checked' : ''; ?>> Creditos a clientes</label>
                    <label><input type="checkbox" name="cortesiasClientes" value="1" <?php echo ($config[''] ?? true) ? 'checked' : ''; ?>> Cortesías</label>
                    <label><input type="checkbox" name="anulacionesYEliminaciones" value="1" <?php echo ($config[''] ?? true) ? 'checked' : ''; ?>> Anulaciones y eliminaciones</label>
                    <label><input type="checkbox" name="detallesDeAtencion" value="1" <?php echo ($config[''] ?? true) ? 'checked' : ''; ?>> Detalle de atención</label>
                </div>
                <div class="panel middle-panel-bottom">
                    <label><input type="checkbox" name="formasDePagoCierre" value="1" <?php echo ($config[''] ?? true) ? 'checked' : ''; ?>> Llenar forma de pago que no sea efectivo en cierre manual</label>
                    <label><input type="checkbox" name="cierreDeCajaFormaDePago" value="1" <?php echo ($config[''] ?? true) ? 'checked' : ''; ?>> Cierre de caja indicando cada forma de pago</label>
                    <label><input type="checkbox" name="imprimirCierreDeCaja" value="1" <?php echo ($config['imprimir_cierre_caja_impresora_termica'] ?? true) ? 'checked' : ''; ?>> Imprimir cierre de caja</label>
                </div>
                <div class="panel right-panel-top">
                    <label><input type="text" name="bloqueoMesas" value="1" <?php echo ($config[''] ?? false) ? 'checked' : ''; ?>> Bloquear la selecicon de las mesas luego de</label>
                    <input type="number" name="txtbloqueoMesas" value="<?php echo $config['seg_bloqueo_seleccion_mesa'] ?? 1000; ?>" class="input-small"> Seg.
                    <label><input type="text" name="envioPedidoMesas" value="1" <?php echo ($config[''] ?? false) ? 'checked' : ''; ?>> Enviar pedido luego de</label>
                    <input type="number" name="txtenvioPedidoMesas" value="<?php echo $config[''] ?? 1000; ?>" class="input-small"> Seg.
                    <label><input type="checkbox" name="imgRecinto" value="1" <?php echo ($config[''] ?? false) ? 'checked' : ''; ?>> Sin imagenes de recintos</label>
                    <label><input type="checkbox" name="cajaroMesaAgregar" value="1" <?php echo ($config[''] ?? false) ? 'checked' : ''; ?>> Cajero puede agregar a cualquier mesa</label>
                    <label><input type="checkbox" name="validarCortesiaProductos" value="1" <?php echo ($config['validar_productos_cortesia'] ?? false) ? 'checked' : ''; ?>> Validar Cortesía venta Producto</label>
                    <label><input type="text" name="familiaFavoritos" value="1" <?php echo ($config[''] ?? false) ? 'checked' : ''; ?>> Familia de favoritos </label>
                    <input type="number" name="txtfamiliaFavoritos" value="<?php echo $config[''] ?? 1000; ?>" class="input-small"> Seg.
                </div>
                <div class="panel right-panel-middle">
                    <h3>Descuentos</h3>
                    <label><input type="text" name="maximoDescuento" value="1" <?php echo ($config['por_maximodescuento'] ?? false) ? 'checked' : ''; ?>> Máximo Permitido</label>
                    <input type="time" name="txtmaximoDescuento" value="<?php echo $config['por_maximodescuento'] ?? ''; ?>">
                    <label><input type="checkbox" name="requerirClienteDescuento" value="1" <?php echo ($config['requiere_cliente_descuento '] ?? false) ? 'checked' : ''; ?>> Requerir Cliente</label>
                </div>
                <div class="panel right-panel-middle">
                    <label><input type="text" name="claveAdmin" value="1" <?php echo ($config['pass_admin'] ?? false) ? 'checked' : ''; ?>> Clave de administrador</label>
                    <input type="time" name="txtclaveAdmin" value="<?php echo $config['pass_admin'] ?? ''; ?>">
                    <label><input type="checkbox" name="mostrarClaveAdmin" value="1" <?php echo ($config[''] ?? false) ? 'checked' : ''; ?>> Mostrar clave</label>
                    <label><input type="checkbox" name="claveAdminAnulacion" value="1" <?php echo ($config['admin_anulacion_eliminacion'] ?? false) ? 'checked' : ''; ?>> Requerir clave admin para anulacion</label>
                </div>
                <div class="panel right-panel-middle">
                    <h3>REQUIERE USUARIO ADMINISTRADOR PARA:</h3>
                    <label><input type="checkbox" name="claveAdminVentaCredito" value="1" <?php echo ($config['requerir_usuario_admin_credito'] ?? false) ? 'checked' : ''; ?>> Ventas a credito</label>
                    <label><input type="checkbox" name="claveAdminVentaCortesia" value="1" <?php echo ($config['requerir_usuario_admin_cortesia'] ?? false) ? 'checked' : ''; ?>> cortesias</label>
                    <label><input type="checkbox" name="claveAdminDescuentoCaja" value="1" <?php echo ($config['requerir_usuario_admin_descuento'] ?? false) ? 'checked' : ''; ?>> Descuentos en caja</label>
                    <label><input type="checkbox" name="claveAdminDescuentoAbrirGaveta" value="1" <?php echo ($config[''] ?? false) ? 'checked' : ''; ?>> Apertura de gaveta en caja</label>
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
                    <label><input type="checkbox" name="fondoFijo" value="1" <?php echo ($config['permitir_retiro_fondo_fijo'] ?? false) ? 'checked' : ''; ?>> Permitir retiro fondo fijo</label>
                    <label>Documento Comprobante interno</label>
                    <select name="id_tdocumento_comprobante_interno">
                        <option value="0">DOCUMENTO PAGO CORTESIA</option>
                        <option value="1">COMPROBANTE INTERNO</option>
                    </select>
                    <label><input type="checkbox" name="preciosPorSector" value="1" <?php echo ($config['lprecio_x_sector'] ?? false) ? 'checked' : ''; ?>> Listar precios por sector</label>
                    <label><input type="checkbox" name="comisionVenta" value="1" <?php echo ($config['agregar_comision_a_venta'] ?? false) ? 'checked' : ''; ?>> Agregar comision a venta</label>
                    <label><input type="checkbox" name="pedirAnfitriona" value="1" <?php echo ($config['pedir_anfitriona'] ?? false) ? 'checked' : ''; ?>> Pedir anfitriona</label>

                </div>
                <div class="panel middle-panel-bottom">
                    <label><input type="checkbox" name="insumosCierreDeCaja" value="1" <?php echo ($config['inventario_cierre_caja'] ?? false) ? 'checked' : ''; ?>> Inventario insumos cierre de caja</label>
                    <label><input type="checkbox" name="repetirProductoMenu" value="1" <?php echo ($config['permitir_repetir_producto_menu'] ?? false) ? 'checked' : ''; ?>> Permitir repetir el producto en el menu</label>
                    <label><input type="checkbox" name="comensalesMesa" value="1" <?php echo ($config['pedir_comensales_en_mesa'] ?? false) ? 'checked' : ''; ?>> Pedir comensales en mesa</label>
                    <label><input type="checkbox" name="horaPedido" value="1" <?php echo ($config['imprimir_hora_detalle_productos_pedido'] ?? false) ? 'checked' : ''; ?>> Imprimir hora en productos del pedido</label>
                </div>
            </div>



            <!-- Tab Facturación Electrónica -->
            <div id="facturacion" class="tab">
                <div class="panel full-width">
                    <h3>Facturacion electronica</h3>
                    <label><input type="checkbox" name="habilitarFactura" value="1" <?php echo ($config['habilitar_facturacion_electronica'] ?? false) ? 'checked' : ''; ?>> Habilitar factura electronica</label>
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
                    <label><input type="checkbox" name="imprimirPdfDte" value="1" <?php echo ($config['imprimir_pdf_dte'] ?? false) ? 'checked' : ''; ?>> Imprimir pdf</label>
                    <input type="text" name="ruta_dte_pdf" value="<?php echo $config['ruta_dte_pdf'] ?? '/17.0.1/caja'; ?>">
                    <label>Formato pdf</label>
                    <input type="text" name="ruta_dte_pdf" value="<?php echo $config['ruta_dte_pdf'] ?? '/17.0.1/caja'; ?>">
                    <label>Ruta dte</label>
                    <input type="text" name="ruta_dte_pdf" value="<?php echo $config['ruta_dte_pdf'] ?? '/17.0.1/caja'; ?>">
                    <label>Copias dte</label>
                    <input type="text" name="ruta_dte_pdf" value="<?php echo $config['ruta_dte_pdf'] ?? '/17.0.1/caja'; ?>">
                    <label><input type="checkbox" name="imprimirCopiaCedible" value="1" <?php echo ($config['imprimir_copia_cedible'] ?? false) ? 'checked' : ''; ?>> Imprimir copia cedible</label>
                    <label><input type="checkbox" name="enviarDteSii" value="1" <?php echo ($config['enviar_dte_sii'] ?? false) ? 'checked' : ''; ?>> enviar dte al sii</label>
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