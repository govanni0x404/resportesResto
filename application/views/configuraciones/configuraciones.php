<?php
// Opciones de las listas: los datos reales de la base, con el valor guardado marcado.
// Si el valor guardado ya no existe en la tabla se muestra igual, para no cambiarlo sin querer al grabar.
// v1.29.1: botón (i) con la explicación de una opción (se ve al pasar el mouse o al tocarlo; js.php > cfgInfo)
if (!function_exists('cfg_info')) {
    function cfg_info($texto)
    {
        echo '<button type="button" class="cfg-info" aria-label="Más información" aria-expanded="false" data-info="'
            . htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') . '"><i class="fa fa-info" aria-hidden="true"></i></button>';
    }
}
if (!function_exists('cfg_opciones')) {
    function cfg_opciones($lista, $valor, $vacio)
    {
        $valor = (int)$valor;
        echo '<option value="0"' . ($valor === 0 ? ' selected' : '') . '>-- ' . html_escape($vacio) . ' --</option>';
        if ($valor !== 0 && !isset($lista[$valor])) echo '<option value="' . $valor . '" selected>N° ' . $valor . ' (no encontrado)</option>';
        foreach ($lista as $id => $nombre) {
            echo '<option value="' . (int)$id . '"' . ((int)$id === $valor ? ' selected' : '') . '>' . html_escape($nombre) . '</option>';
        }
    }
}
?>
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
                            <?php if ((int)$this->session->userdata('id_rol') === 1) : ?>
                            <button class="tablink" onclick="openTab(event, 'respaldos')"><i class="fa fa-database"></i>Respaldos</button>
                            <?php endif; ?>
                        </div>

                        <!-- novalidate: un campo inválido en una pestaña oculta impedía guardar sin mostrar nada -->
                        <form id="configForm" method="post" action="<?php echo site_url('Configuraciones/guardar'); ?>" novalidate>
                            <!-- Tab General. v1.29.1: cada opción con un texto corto y su botón (i) con la explicación completa -->
                            <div id="general" class="tab active">
                                <div class="config-grid">
                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title"><i class="fa fa-th"></i>Modo de la Caja web</h3>
                                            </div>
                                            <div class="panel-body">
                                                <?php $modoCaja = in_array($config['modo_caja_web'] ?? '', array('directa', 'mesas', 'ambos'), true) ? $config['modo_caja_web'] : 'ambos'; ?>
<div class="form-group" style="margin-top:0">
                                                <label class="cfg-titulo" for="cboModoCaja">Cómo se vende <?php cfg_info("Ambos: la Caja tiene la pestaña Venta directa y la pestaña Mesas.\nVenta directa: sin mesas, cada venta se cobra al tiro.\nMesas: toda venta se hace en una mesa. En este modo conviene dejar siempre una mesa libre para la venta rápida (por ejemplo \"Mostrador\")."); ?></label>
                                                <select class="form-control" name="modo_caja_web" id="cboModoCaja">
                                                    <option value="ambos" <?php echo $modoCaja === 'ambos' ? 'selected' : ''; ?>>Ambos: venta directa y mesas</option>
                                                    <option value="directa" <?php echo $modoCaja === 'directa' ? 'selected' : ''; ?>>Venta directa (sin mesas)</option>
                                                    <option value="mesas" <?php echo $modoCaja === 'mesas' ? 'selected' : ''; ?>>Mesas (toda venta se hace en una mesa)</option>
                                                </select>
                                                </div>
                                                <?php if (array_key_exists('agrupar_productos_web', $config)) : ?>
                                                <!-- v1.24.8: agrupar productos iguales (migración 20261120170000) -->
                                                <div class="cfg-fila"><label><input type="checkbox" name="agrupar_productos_web" value="1" <?php echo ($config['agrupar_productos_web'] ?? true) !== false ? 'checked' : ''; ?>> Juntar productos iguales en una sola fila</label><?php cfg_info("Marcada: si agregas dos pizzas iguales, quedan en una fila con cantidad 2.\nSin marcar: cada pizza va en su propia fila, para ponerle a cada una su propio mensaje (ej. \"sin cebolla\")."); ?></div>
                                                <?php endif; ?>
                                                <?php if (array_key_exists('esperas_anular_todas_web', $config)) :
                                                    $__on = function ($k) use ($config) { return in_array($config[$k] ?? null, array(true, 't', 1, '1'), true); }; ?>
                                                <!-- v1.33.0: anular varias cosas de una vez (migración 20261124120000) -->
                                                <div class="cfg-fila"><label><input type="checkbox" name="esperas_anular_todas_web" value="1" <?php echo $__on('esperas_anular_todas_web') ? 'checked' : ''; ?>> Anular todas las ventas en espera de una vez</label><?php cfg_info("Agrega el botón \"Anular todas\" en Opciones > Ventas en espera.\nSe escribe una sola justificación para todas y queda registrada en cada una (Auditoría de caja).\nSi está marcada la clave para anular una venta en espera, se pide una sola vez."); ?></div>
                                                <div class="cfg-fila"><label><input type="checkbox" name="mesas_anular_varias_web" value="1" <?php echo $__on('mesas_anular_varias_web') ? 'checked' : ''; ?>> Anular varias mesas a la vez</label><?php cfg_info("Agrega el botón \"Anular varias\" en la pestaña Mesas: tocas las mesas con pedido que quieres anular y se anulan todas juntas con un solo motivo.\nLas mesas quedan libres y a cada destino de impresión le sale su comanda de anulación.\nSi está marcada la clave para anular el pedido completo de la mesa, se pide una sola vez."); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <?php if (array_key_exists('agregados_extra_web', $config)) :
                                            $__onAg = function ($k) use ($config) { return in_array($config[$k] ?? null, array(true, 't', 1, '1'), true); }; ?>
                                        <!-- v1.35.0: agregados extra con costo y categorías de agregados (migración 20261124140000) -->
                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title"><i class="fa fa-plus-square"></i>Agregados en la Caja</h3>
                                            </div>
                                            <div class="panel-body">
                                                <div class="cfg-fila"><label><input type="checkbox" name="agregados_extra_web" value="1" <?php echo $__onAg('agregados_extra_web') ? 'checked' : ''; ?>> Agregados extra con cobro</label><?php cfg_info("En la Carta, cada producto tiene la casilla \"Permitir agregados extra con cobro\" junto a su máximo de agregados.\nEn esos productos, los agregados hasta el máximo (ej. 2) van sin costo, y se pueden sumar más cobrando el valor adicional de cada agregado.\nSin marcar: el máximo es un tope y no se puede elegir otro."); ?></div>
                                                <?php if (array_key_exists('agregados_max_extras_web', $config)) : ?>
                                                <div class="form-group cfg-numero-ag">
                                                    <label class="cfg-titulo" for="txtMaxExtrasAg">Cuántos extras se pueden sumar <?php cfg_info("Con el máximo del producto lleno (ej. 4), cuántos agregados más puede pedir el cliente, opcionales y con cobro (ej. 1 = el 5.º). Casi siempre 1 o 2.\n0 = sin tope."); ?></label>
                                                    <input type="number" class="form-control" id="txtMaxExtrasAg" name="agregados_max_extras_web" min="0" max="20" value="<?php echo (int)($config['agregados_max_extras_web'] ?? 1); ?>">
                                                </div>
                                                <?php endif; ?>
                                                <div class="cfg-fila"><label><input type="checkbox" name="agregados_categorias_web" value="1" <?php echo $__onAg('agregados_categorias_web') ? 'checked' : ''; ?>> Categorías de agregados</label><?php cfg_info("Los agregados se ordenan por categoría (Proteína, Verdura, Salsas...): en la Caja y en la Carta sale un cuadro de color por categoría, con su nombre.\nLas categorías se crean en Mantención > Categorías de agregados, donde se ponen varios productos de una vez en una categoría."); ?></div>
                                                <?php if (array_key_exists('agregados_max_categoria_web', $config)) : ?>
                                                <div class="form-group cfg-numero-ag">
                                                    <label class="cfg-titulo" for="txtMaxCategoriaAg">Máximo por categoría <?php cfg_info("Tope de agregados que puede elegir el cliente de cada categoría. Ej: máximo del producto 4 y máximo por categoría 2: elige hasta 2 de Proteína, 1 de Verdura y 1 sin categoría (4 en total).\nEs un tope fijo: también el extra con cobro lo respeta. Los sin categoría solo cuentan para el máximo del producto.\n0 = sin límite."); ?></label>
                                                    <input type="number" class="form-control" id="txtMaxCategoriaAg" name="agregados_max_categoria_web" min="0" max="20" value="<?php echo (int)($config['agregados_max_categoria_web'] ?? 0); ?>">
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <?php if (array_key_exists('usar_cocina_web', $config)) :
                                            $__activa = function ($k) use ($config) { return in_array($config[$k] ?? null, array(true, 't', 1, '1'), true); }; ?>
                                        <!-- v1.29.0: pantalla de cocina y reservas (migración 20261121140000) -->
                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title"><i class="fa fa-fire"></i>Cocina y reservas</h3>
                                            </div>
                                            <div class="panel-body">
                                                <div class="cfg-fila"><label><input type="checkbox" name="usar_cocina_web" value="1" <?php echo $__activa('usar_cocina_web') ? 'checked' : ''; ?>> Usar la pantalla de cocina</label><?php cfg_info("Para una tablet o monitor en la cocina: las comandas se ven en pantalla, el cocinero las toma (\"Siendo preparado por…\") y las marca listas.\nLas comandas en papel siguen saliendo igual.\nQuién la ve: gerencia siempre; los demás roles, si se les da \"Cocina\" en Privilegios de Usuario."); ?></div>
                                                <div class="cfg-fila"><label><input type="checkbox" name="usar_reservas_web" value="1" <?php echo $__activa('usar_reservas_web') ? 'checked' : ''; ?>> Usar reservas de mesas</label><?php cfg_info("Agrega el menú Reservas para anotar quién viene, a qué hora, cuántas personas y en qué mesa.\nQuién la ve: gerencia siempre; los demás roles, si se les da \"Reservas\" en Privilegios de Usuario."); ?></div>
                                                <div class="form-group">
                                                    <label class="cfg-titulo" for="minBloqueoReserva">Bloquear la mesa reservada (minutos antes) <?php cfg_info("Desde esos minutos antes de la hora de la reserva, la mesa sale \"Reservada\" en la Caja y no acepta pedidos.\nSe desbloquea al marcar que el cliente llegó (tocando la mesa en la Caja), que no vino o que se canceló, o cuando pasa 1 hora de la hora reservada.\n0 = no bloquear."); ?></label>
                                                    <input type="number" min="0" max="720" step="5" class="form-control" id="minBloqueoReserva" name="minutos_bloqueo_reserva_web" value="<?php echo (int)($config['minutos_bloqueo_reserva_web'] ?? 60); ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <?php if (array_key_exists('sushi_por_pieza_web', $config)) :
                                            $__sushi = function ($k) use ($config) { return in_array($config[$k] ?? null, array(true, 't', 1, '1'), true); }; ?>
                                        <!-- v1.32.0: promos de sushi (migración 20261123110000) -->
                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title"><i class="fa fa-th"></i>Promos de sushi</h3>
                                            </div>
                                            <div class="panel-body">
                                                <?php if (array_key_exists('promos_sushi_web', $config)) : ?>
                                                <!-- v1.36.0: botón Promos Sushi en la Carta (las promos normales salen siempre) -->
                                                <div class="cfg-fila"><label><input type="checkbox" name="promos_sushi_web" value="1" <?php echo $__sushi('promos_sushi_web') ? 'checked' : ''; ?>> Usar promos de sushi por roll</label><?php cfg_info("Marcada: en la Carta sale el botón \"Promos Sushi\" para armar promos de sushi por roll.\nSin marcar: ese botón no sale. Las promos normales (botón \"Promos\", ej. Completo + Bebida a un precio) salen siempre."); ?></div>
                                                <?php endif; ?>
                                                <div class="cfg-fila"><label><input type="checkbox" name="sushi_por_pieza_web" value="1" <?php echo $__sushi('sushi_por_pieza_web') ? 'checked' : ''; ?>> Armar promos por pieza</label><?php cfg_info("Además de las promos por roll, permite crear promos por pieza (ej. 30 piezas repartidas como el cliente quiera).
Pocos locales lo usan.
Apagada: en Carta > Promos Sushi solo se crean promos por roll. Las promos por pieza que ya existen se siguen vendiendo."); ?></div>
                                                <div class="cfg-fila"><label><input type="checkbox" name="sushi_bases_web" value="1" <?php echo $__sushi('sushi_bases_web') ? 'checked' : ''; ?>> Preguntar la base (arroz, etc.)</label><?php cfg_info("Marcada: en la promo se pueden agregar bases y en la Caja se pregunta la base de cada roll.
Sin marcar: no se pregunta la base (las bases que ya tengan las promos no se ofrecen)."); ?></div>
                                                <div class="cfg-fila"><label><input type="checkbox" name="sushi_salsas_web" value="1" <?php echo $__sushi('sushi_salsas_web') ? 'checked' : ''; ?>> Salsas aparte de los extras</label><?php cfg_info("Marcada: la promo tiene una sección de Salsas (soya, teriyaki…) separada de los Extras, cada una con su propio máximo.
Sin marcar: solo Extras.
En la Caja las salsas y los extras siempre son opcionales."); ?></div>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <?php if (array_key_exists('delivery_habilitado', $config)) : ?>
                                        <!-- 18-11-2026: Delivery en la Caja (migración 20261118100000; sin ella la tarjeta no aparece) -->
                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title"><i class="fa fa-motorcycle"></i>Delivery</h3>
                                            </div>
                                            <div class="panel-body">
                                                <div class="cfg-fila"><label><input type="checkbox" name="delivery_habilitado" value="1" <?php echo ($config['delivery_habilitado'] ?? null) === true || ($config['delivery_habilitado'] ?? null) === 't' ? 'checked' : ''; ?>> Usar delivery en la Caja</label><?php cfg_info("Agrega a la Caja la pestaña Delivery (aparte de Venta directa y Mesas), con los datos de entrega del cliente.\nSe puede cobrar al tiro o al entregar."); ?></div>
                                                <div class="form-group">
                                                    <label class="cfg-titulo">Costo de envío <?php cfg_info("Lo que se cobra por el envío en cada delivery (el mismo valor para todos).\n0 = no se cobra envío."); ?></label>
                                                    <input type="number" min="0" step="1" class="form-control" name="delivery_costo_envio" value="<?php echo (int)($config['delivery_costo_envio'] ?? 0); ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label class="cfg-titulo">Producto del envío <?php cfg_info("El SII pide que todo lo cobrado sea una línea del documento.\nCrea un producto (ej. \"DESPACHO\"), elígelo aquí y el envío se suma solo a cada delivery con el costo de arriba.\nSin producto, no se cobra envío."); ?></label>
                                                    <select class="form-control" name="delivery_id_producto_envio">
<?php cfg_opciones($opciones['producto'] ?? array(), $config['delivery_id_producto_envio'] ?? 0, 'SIN ENVÍO'); ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title"><i class="fa fa-money"></i>Propinas</h3>
                                            </div>
                                            <div class="panel-body">
                                                <?php /* v1.23.0: Usar propina (apagada = no sale en nada) y Propina en mesas (migración 20261120140000) */ ?>
                                                <?php $conColPropina = array_key_exists('usar_propina_web', $config); ?>
                                                <?php if ($conColPropina) : ?>
                                                <div class="cfg-fila"><label><input type="checkbox" name="usar_propina_web" id="chkUsarPropina" value="1" <?php echo ($config['usar_propina_web'] ?? true) !== false ? 'checked' : ''; ?>> <b>Usar propina</b></label><?php cfg_info("Desmárcala si el local no trabaja con propina: no se pregunta al cobrar ni sale en la precuenta ni en ningún documento."); ?></div>
                                                <div id="opcionesPropina" style="margin-left:22px">
                                                <div class="cfg-fila"><label><input type="checkbox" name="propina_mesas_web" value="1" <?php echo ($config['propina_mesas_web'] ?? true) !== false ? 'checked' : ''; ?>> Propina en mesas</label><?php cfg_info("Al cobrar una mesa, la Caja propone la propina (el cajero la puede cambiar o quitar)."); ?></div>
                                                <?php endif; ?>
                                                <div class="cfg-fila"><label><input type="checkbox" name="calcular_valor_propina" value="1" <?php echo ($config['calcular_valor_propina'] ?? false) ? 'checked' : ''; ?>> Propina sugerida en la precuenta</label><?php cfg_info("La precuenta que se entrega en la mesa muestra el valor de la propina sugerida y el total con propina."); ?></div>
                                                <div class="cfg-fila"><label><input type="checkbox" name="propina_en_venta_directa" value="1" <?php echo ($config['propina_en_venta_directa'] ?? false) ? 'checked' : ''; ?>> Propina en venta directa</label><?php cfg_info("Al cobrar una venta directa (sin mesa), la Caja también pregunta por la propina."); ?></div>
                                                <?php if ($conColPropina) : ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title"><i class="fa fa-cutlery"></i>Otros del restaurante</h3>
                                            </div>
                                            <div class="panel-body">
                                                <div class="form-group">
                                                    <label class="cfg-titulo">Mesa de venta directa <?php cfg_info("Número de la mesa \"técnica\" que el sistema usa por dentro para la venta directa. No aparece en la lista de mesas de la Caja.\nNo la cambies si no sabes cuál es."); ?></label>
                                                    <input type="text" class="form-control" name="id_mesa_venta_directa" value="<?php echo $config['id_mesa_venta_directa'] ?? ''; ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label class="cfg-titulo">Impresora de la caja <?php cfg_info("Ahí salen las boletas, facturas, comprobantes (anulación, pago de créditos), el cierre y el informe X.\nAhí también va conectada la gaveta de dinero.\nSe imprime directo por el agente de impresión del computador de la caja."); ?></label>
                                                    <?php $impCierre = trim((string)($config['nombre_impresora_cierre'] ?? '')); ?>
                                                    <select class="form-control" name="nombre_impresora_cierre">
                                                        <!-- v1.25.2: sin "LA DE LA CAJA" (era la impresora que deja el POS de escritorio, que la web no deja
                                                             elegir y a veces no existe): la caja usa siempre la que se elige aquí -->
                                                        <?php if ($impCierre === '') : ?><option value="" selected disabled>-- Elige la impresora --</option><?php endif; ?>
                                                        <?php foreach ($opciones['dimpresion'] as $nomImp) : ?>
                                                            <option value="<?php echo htmlspecialchars($nomImp, ENT_QUOTES, 'UTF-8'); ?>" <?php echo strcasecmp($nomImp, $impCierre) === 0 ? 'selected' : ''; ?>><?php echo htmlspecialchars($nomImp, ENT_QUOTES, 'UTF-8'); ?></option>
                                                        <?php endforeach; ?>
                                                        <?php if ($impCierre !== '' && !in_array(strtoupper($impCierre), array_map('strtoupper', $opciones['dimpresion']), true)) : ?>
                                                            <option value="<?php echo htmlspecialchars($impCierre, ENT_QUOTES, 'UTF-8'); ?>" selected><?php echo htmlspecialchars($impCierre, ENT_QUOTES, 'UTF-8'); ?> (no existe)</option>
                                                        <?php endif; ?>
                                                    </select>
                                                    <!-- v1.21.0: probar la gaveta de dinero (va conectada a la impresora) sin ir a la Caja -->
                                                    <div class="cfg-fila cfg-boton"><button type="button" class="btn btn-orange" id="btnProbarGaveta"><i class="fa fa-inbox"></i> Probar gaveta</button><?php cfg_info("Abre la gaveta de dinero conectada a la impresora de arriba, para ver que funciona.\nHazlo desde el computador de la caja, con el agente de impresión abierto."); ?></div>
                                                    <?php if (array_key_exists('abrir_gaveta_solo_por_pago_efectivo', $config)) : ?>
                                                    <!-- v1.24.18: la gaveta se abre sola al cobrar. Columna del POS de escritorio: vacía = activa -->
                                                    <div class="cfg-fila"><label><input type="checkbox" name="abrir_gaveta_solo_por_pago_efectivo" value="1" <?php echo ($config['abrir_gaveta_solo_por_pago_efectivo'] === null || in_array($config['abrir_gaveta_solo_por_pago_efectivo'], array(true, 't', 1, '1'), true)) ? 'checked' : ''; ?>> Abrir la gaveta solo con pagos en efectivo</label><?php cfg_info("La gaveta se abre sola al cobrar.\nMarcada: solo si parte del pago es en efectivo.\nSin marcar: en todos los cobros (también tarjeta, transferencia…)."); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                                <!-- 18-11-2026: se quitaron "Imprimir documentos en el equipo de la caja" y "con el diseño de la página":
                                                     todos los locales imprimen por el agente (impresionWeb.exe) y como imagen, siempre encendidas -->
                                                <?php if (array_key_exists('imprimir_comandas_app', $config)) : ?>
                                                <!-- 14-11-2026: comandas de la app de garzones impresas por la Caja web (solo locales sin POS de escritorio) -->
                                                <div class="cfg-fila"><label><input type="checkbox" name="imprimir_comandas_app" value="1" <?php echo ($config['imprimir_comandas_app'] ?? null) === true || ($config['imprimir_comandas_app'] ?? null) === 't' ? 'checked' : ''; ?>> Imprimir las comandas de la app de garzones</label><?php cfg_info("La Caja web imprime los pedidos que llegan desde la app de garzones, cada producto en su destino de impresión (cocina, barra…).\nMárcala solo si el local NO usa el POS de escritorio: si no, cada comanda sale dos veces."); ?></div>
                                                <?php endif; ?>
                                                <?php if (array_key_exists('imprimir_comandas_app_forzar', $config)) : ?>
                                                <!-- 16-11-2026: celulares que mandan el pedido con "imprimir": 0 (la API2 lo graba como ya impreso) -->
                                                <div class="cfg-fila" style="margin-left:22px"><label><input type="checkbox" name="imprimir_comandas_app_forzar" value="1" <?php echo ($config['imprimir_comandas_app_forzar'] ?? null) === true || ($config['imprimir_comandas_app_forzar'] ?? null) === 't' ? 'checked' : ''; ?>> También los pedidos marcados sin imprimir</label><?php cfg_info("Algunos celulares mandan el pedido diciendo que no se imprima.\nMarcada: igual se imprimen.\nOjo: si algún celular imprime él mismo por Bluetooth, esos pedidos saldrían dos veces."); ?></div>
                                                <?php endif; ?>
                                                <div class="cfg-fila"><label><input type="checkbox" name="imprimir_comprobante_anulacion" value="1" <?php echo ($config['imprimir_comprobante_anulacion'] ?? false) ? 'checked' : ''; ?>> Imprimir comprobante al anular</label><?php cfg_info("Al anular una venta en la Caja, sale un comprobante con el documento, el monto, quién la anuló y el motivo."); ?></div>
                                            </div>
                                        </div>

                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title"><i class="fa fa-key"></i>Clave de administrador</h3>
                                            </div>
                                            <div class="panel-body">
                                                <div class="form-group">
                                                    <label class="cfg-titulo" for="txtclaveAdmin">Clave de administrador <?php cfg_info("La clave que se pide en la Caja para lo que tú decidas proteger (anulaciones, descuentos, cortesías, abrir la gaveta…).\nPor seguridad no se muestra: déjala vacía para mantener la guardada, o escribe otra para cambiarla."); ?></label>
                                                    <!-- 19-11-2026: la clave guardada no se manda a la página; vacía = se mantiene -->
                                                    <input type="password" id="txtclaveAdmin" class="form-control" name="txtclaveAdmin" value="" autocomplete="new-password"
                                                        placeholder="<?php echo !empty($tiene_clave_admin) ? '•••••• (guardada; escribe otra para cambiarla)' : 'Sin clave: escribe una para crearla'; ?>">
                                                </div>
                                                <?php if (!empty($tiene_clave_admin)) : ?>
                                                <div class="cfg-fila"><label><input type="checkbox" name="borrar_clave_admin" value="1"> Quitar la clave guardada</label><?php cfg_info("Borra la clave al grabar. Lo protegido quedará sin clave hasta que crees otra."); ?></div>
                                                <?php endif; ?>
                                                <div class="cfg-fila"><label><input type="checkbox" id="chkMostrarClaveAdmin" onchange="toggleMostrarClaveAdmin()" name="mostrar_clave_admin" value="1" <?php echo ($config['mostrar_clave_admin'] ?? false) ? 'checked' : ''; ?>> Ver lo que escribo</label><?php cfg_info("Muestra la clave que estás escribiendo arriba, para revisar que esté bien."); ?></div>
                                                <?php if (array_key_exists('admin_anular_venta_web', $config)) : ?>
                                                <!-- v1.25.3 (pedido del usuario): la clave se pide por separado en cada tipo de anulación. Marcada: se
                                                     pide a todos, también a un administrador (migración 20261121100000) -->
                                                <div class="cfg-subtitulo">Pedir la clave para anular <?php cfg_info("Marcada: se pide la clave a todos, también a un administrador.\nSin marcar: se anula sin clave."); ?></div>
                                                <?php foreach (array(
                                                    'admin_anular_producto_mesa_web' => 'Un producto de la mesa',
                                                    'admin_anular_pedido_mesa_web' => 'El pedido completo de la mesa',
                                                    'admin_anular_espera_web' => 'Una venta en espera',
                                                    'admin_anular_venta_web' => 'Una venta (boleta, factura o comprobante)',
                                                    'admin_devolucion_web' => 'Una devolución (nota de crédito)',
                                                ) as $campoClave => $textoClave) : ?>
                                                <label style="margin-left:12px"><input type="checkbox" name="<?php echo $campoClave; ?>" value="1" <?php echo in_array($config[$campoClave] ?? false, array(true, 't', 1, '1'), true) ? 'checked' : ''; ?>> <?php echo $textoClave; ?></label>
                                                <?php endforeach; ?>
                                                <div class="cfg-fila"><label><input type="checkbox" name="admin_anulacion_eliminacion" value="1" <?php echo ($config['admin_anulacion_eliminacion'] ?? false) ? 'checked' : ''; ?>> Pedir la clave para anular en el POS de escritorio</label><?php cfg_info("Solo para el programa de escritorio (no la Caja web): pide la clave de administrador al anular o eliminar."); ?></div>
                                                <?php else : ?>
                                                <div class="cfg-fila"><label><input type="checkbox" name="admin_anulacion_eliminacion" value="1" <?php echo ($config['admin_anulacion_eliminacion'] ?? false) ? 'checked' : ''; ?>> Pedir la clave para anular</label><?php cfg_info("Pide la clave de administrador al anular una venta o un producto."); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title"><i class="fa fa-envelope-o"></i>Informes por correo</h3>
                                            </div>
                                            <div class="panel-body">
                                                <div class="cfg-fila"><label><input type="checkbox" name="usar_correo_electronico" value="1" <?php echo ($config['usar_correo_electronico'] ?? false) ? 'checked' : ''; ?>> Enviar el cierre de caja por correo</label><?php cfg_info("Al cerrar la caja, el informe del cierre llega a los correos de abajo."); ?></div>
                                                <div class="form-group">
                                                    <label class="cfg-titulo" for="txtCorreosInforme">Correos que reciben los informes <?php cfg_info("Uno o varios correos, separados por coma.\nEj: dueno@correo.com, contador@correo.com"); ?></label>
                                                    <input type="email" multiple class="form-control" name="correo_electronico" id="txtCorreosInforme" placeholder="ejemplo@correo.com, otro@correo.com" title="Uno o varios correos separados por coma" value="<?php echo htmlspecialchars((string)($config['correo_electronico'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                                </div>
                                                <div class="cfg-fila"><label><input type="checkbox" name="informar_anulaciones_mail" value="1" <?php echo ($config['informar_anulaciones_mail'] ?? false) ? 'checked' : ''; ?>> Avisar por correo cada anulación</label><?php cfg_info("Cada vez que se anula una venta, llega un correo con el documento, el monto, la fecha, quién la anuló y el motivo."); ?></div>
                                                <div class="cfg-fila cfg-boton"><button type="button" class="btn btn-orange" id="btnProbarCorreo">Probar envío</button><?php cfg_info("Manda un correo de prueba a los correos de arriba, para comprobar que llegan."); ?></div>
                                            </div>
                                        </div>

                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title"><i class="fa fa-print"></i>Cierre de caja</h3>
                                            </div>
                                            <div class="panel-body">
                                                <div class="cfg-subtitulo">Qué sale en el cierre impreso <?php cfg_info("Las secciones que lleva el informe de cierre de caja."); ?></div>
                                                <label><input type="checkbox" name="ver_ingresos_egresos_cierre_caja" value="1" <?php echo ($config['ver_ingresos_egresos_cierre_caja'] ?? true) ? 'checked' : ''; ?>> Ingresos y egresos de dinero</label>
                                                <label><input type="checkbox" name="ver_creditos_a_clientes_cierre_caja" value="1" <?php echo ($config['ver_creditos_a_clientes_cierre_caja'] ?? true) ? 'checked' : ''; ?>> Créditos a clientes</label>
                                                <label><input type="checkbox" name="ver_cortesias_cierre_caja" value="1" <?php echo ($config['ver_cortesias_cierre_caja'] ?? true) ? 'checked' : ''; ?>> Cortesías</label>
                                                <label><input type="checkbox" name="ver_anulaciones_eliminaciones_cierre_caja" value="1" <?php echo ($config['ver_anulaciones_eliminaciones_cierre_caja'] ?? true) ? 'checked' : ''; ?>> Anulaciones y eliminaciones</label>
                                                <hr>
                                                <div class="cfg-fila"><label><input type="checkbox" name="mostrar_recaudacion_cierre_no_efectivo" value="1" <?php echo ($config['mostrar_recaudacion_cierre_no_efectivo'] ?? true) ? 'checked' : ''; ?>> Completar solos los pagos con tarjeta y otros</label><?php cfg_info("Al cerrar, los montos de tarjeta, transferencia y otras formas que no son efectivo vienen escritos con lo que dice el sistema.\nSin marcar: el cajero los escribe él mismo, sumando sus comprobantes (cierre ciego)."); ?></div>
                                                <div class="cfg-fila"><label><input type="checkbox" name="cierre_caja_manual" value="1" <?php echo ($config['cierre_caja_manual'] ?? true) ? 'checked' : ''; ?>> Abrir el informe detallado (PDF) al cerrar</label><?php cfg_info("Al cerrar la caja se abre en otra pestaña el informe completo en PDF, para guardarlo o imprimirlo."); ?></div>
                                                <div class="cfg-fila"><label><input type="checkbox" name="imprimir_cierre_caja_impresora_termica" value="1" <?php echo ($config['imprimir_cierre_caja_impresora_termica'] ?? true) ? 'checked' : ''; ?>> Imprimir el cierre en la impresora de la caja</label><?php cfg_info("Al cerrar, el cierre sale impreso en papel en la impresora de la caja."); ?></div>
                                            </div>
                                        </div>

                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title"><i class="fa fa-percent"></i>Descuentos</h3>
                                            </div>
                                            <div class="panel-body">
                                                <div class="form-group">
                                                    <label class="cfg-titulo">Descuento máximo (%) <?php cfg_info("El porcentaje más alto de descuento que se puede dar en una venta en la Caja (venta directa, mesas y delivery).\nEj: 10 = hasta un 10%.\n0 = no se permite ningún descuento.\n100 = sin límite."); ?></label>
                                                    <input type="number" step="0.01" class="form-control" name="por_maximodescuento" min="0" max="100" placeholder="100 = sin límite" value="<?php echo $config['por_maximodescuento'] ?? ''; ?>">
                                                </div>
                                                <div class="cfg-fila"><label><input type="checkbox" name="requiere_cliente_descuento" value="1" <?php echo ($config['requiere_cliente_descuento'] ?? false) ? 'checked' : ''; ?>> Descuento solo con cliente</label><?php cfg_info("Para hacer un descuento hay que elegir un cliente con nombre (no \"Público general\"), así queda registrado a quién se le hizo."); ?></div>
                                            </div>
                                        </div>

                                        <!-- v1.24.3: se quitó "Máximo de agregados por producto en la Caja": la Caja web ya no tiene límite -->
                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title"><i class="fa fa-shield"></i>Pedir clave de administrador para</h3>
                                            </div>
                                            <div class="panel-body">
                                                <div class="cfg-fila"><label><input type="checkbox" name="requerir_usuario_admin_credito" value="1" <?php echo ($config['requerir_usuario_admin_credito'] ?? false) ? 'checked' : ''; ?>> Ventas a crédito</label><?php cfg_info("Para vender fiado (a crédito a un cliente), la Caja pide la clave de un administrador."); ?></div>
                                                <div class="cfg-fila"><label><input type="checkbox" name="requerir_usuario_admin_cortesia" value="1" <?php echo ($config['requerir_usuario_admin_cortesia'] ?? false) ? 'checked' : ''; ?>> Cortesías</label><?php cfg_info("Para regalar una venta (cortesía, sin cobro), la Caja pide la clave de un administrador."); ?></div>
                                                <div class="cfg-fila"><label><input type="checkbox" name="requerir_usuario_admin_descuento" value="1" <?php echo ($config['requerir_usuario_admin_descuento'] ?? false) ? 'checked' : ''; ?>> Descuentos</label><?php cfg_info("Para hacer un descuento en la Caja, se pide la clave de un administrador."); ?></div>
                                                <div class="cfg-fila"><label><input type="checkbox" name="boton_apertura_caja_solo_admin" value="1" <?php echo ($config['boton_apertura_caja_solo_admin'] ?? false) ? 'checked' : ''; ?>> Abrir la gaveta a mano</label><?php cfg_info("Para abrir la gaveta con el botón de la Caja (sin una venta), se pide la clave de un administrador.\nAl cobrar se sigue abriendo sola."); ?></div>
                                            </div>
                                        </div>

                                        <!-- 19-11-2026: usuario y clave de la API del programa de pago (máquinas POS de Mantención > Pago electrónico) -->
                                        <?php if (array_key_exists('pago_electronico_usuario_api', $config)) : ?>
                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title"><i class="fa fa-credit-card"></i>Pago electrónico <?php cfg_info("Usuario y clave del programa de pago de las cajas. Van en cada cobro a la máquina POS (Mantención › Pago electrónico)."); ?></h3>
                                            </div>
                                            <div class="panel-body">
                                                <div class="form-group">
                                                    <label class="cfg-titulo">Usuario API <?php cfg_info("El usuario que te entregó el proveedor del programa de pago."); ?></label>
                                                    <input type="text" class="form-control" name="pago_electronico_usuario_api" maxlength="60" autocomplete="off" placeholder="DATAMAULE"
                                                        value="<?php echo htmlspecialchars((string)($config['pago_electronico_usuario_api'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                                </div>
                                                <div class="form-group" style="margin-bottom:0">
                                                    <label class="cfg-titulo">Clave API <?php cfg_info("La clave del programa de pago. Por seguridad no se muestra: vacía = se mantiene la guardada."); ?></label>
                                                    <input type="password" class="form-control" name="pago_electronico_clave_api" maxlength="200" autocomplete="new-password"
                                                        placeholder="<?php echo !empty($pago_electronico_tiene_clave) ? '•••••• (guardada; vacía = se mantiene)' : ''; ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                </div>
                            </div>

                            <!-- Tab Otras Configuraciones -->
                            <div id="otras" class="tab">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title"><i class="fa fa-shopping-cart"></i>Compras <?php cfg_info("Lo que se propone solo al ingresar una compra. Siempre se puede cambiar en cada compra."); ?></h3>
                                            </div>
                                            <div class="panel-body">
                                                <div class="row">
                                                    <div class="col-md-4 form-group">
                                                        <label class="cfg-titulo">Documento de compra <?php cfg_info("El tipo de documento que viene elegido al ingresar una compra (ej. factura)."); ?></label>
                                                        <select class="form-control" name="id_tdocumento_compra">
<?php cfg_opciones($opciones['tdocumento'], $config['id_tdocumento_compra'] ?? 0, 'SIN DEFINIR'); ?>
</select>
                                                    </div>
                                                    <div class="col-md-4 form-group">
                                                        <label class="cfg-titulo">Forma de pago de compras <?php cfg_info("Cómo se paga normalmente a los proveedores. Viene elegida al ingresar una compra."); ?></label>
                                                        <select class="form-control" name="id_fpago_compra">
<?php cfg_opciones($opciones['fpago'], $config['id_fpago_compra'] ?? 0, 'SIN DEFINIR'); ?>
</select>
                                                    </div>
                                                    <!-- La bodega de compras se elige abajo, en "Bodegas" (antes estaba repetida y una pisaba a la otra) -->
                                                </div>
                                                <hr>
                                                <h4>Créditos a clientes</h4>
                                                <div class="row">
                                                    <div class="col-md-6 form-group">
                                                        <label class="cfg-titulo">Forma de pago "a crédito" <?php cfg_info("La forma de pago que significa \"fiado\": lo que se cobra con ella queda como deuda del cliente.\nNINGUNA = la Caja no vende a crédito."); ?></label>
                                                        <select class="form-control" name="id_fpago_creditodirecto">
<?php cfg_opciones($opciones['fpago'], $config['id_fpago_creditodirecto'] ?? 0, 'NINGUNA (sin venta a crédito)'); ?>
</select>
                                                    </div>
                                                    <div class="col-md-6 form-group">
                                                        <label class="cfg-titulo">Centro de costo de los pagos de crédito <?php cfg_info("Cuando un cliente paga su deuda en la Caja, el ingreso queda registrado en este centro de costo."); ?></label>
                                                        <select class="form-control" name="id_pagocreditodirecto">
<?php cfg_opciones($opciones['ccosto'], $config['id_pagocreditodirecto'] ?? 0, 'SIN DEFINIR'); ?>
</select>
                                                    </div>
                                                </div>
                                                <hr>
                                                <h4>Bodegas</h4>
                                                <div class="row">
                                                    <div class="col-md-4 form-group">
                                                        <label class="cfg-titulo">Bodega de compras <?php cfg_info("Donde entra la mercadería que se compra (suma stock)."); ?></label>
                                                        <select class="form-control" name="id_bodega_compra">
<?php cfg_opciones($opciones['bodega'], $config['id_bodega_compra'] ?? 0, 'SIN DEFINIR'); ?>
</select>
                                                    </div>
                                                    <div class="col-md-4 form-group">
                                                        <label class="cfg-titulo">Bodega de ventas <?php cfg_info("De donde salen los insumos cuando se vende (resta stock según la receta)."); ?></label>
                                                        <select class="form-control" name="id_bodega_venta">
<?php cfg_opciones($opciones['bodega'], $config['id_bodega_venta'] ?? 0, 'SIN DEFINIR'); ?>
</select>
                                                    </div>
                                                    <div class="col-md-4 form-group">
                                                        <label class="cfg-titulo">Bodega de mermas <?php cfg_info("Donde se registra lo que se pierde o se bota (mermas) al hacer inventario."); ?></label>
                                                        <select class="form-control" name="id_bodega_merma_inventario">
<?php cfg_opciones($opciones['bodega'], $config['id_bodega_merma_inventario'] ?? 0, 'SIN DEFINIR'); ?>
</select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title"><i class="fa fa-shopping-bag"></i>Ventas <?php cfg_info("Lo que viene elegido solo en la Caja. El cajero lo puede cambiar en cada venta."); ?></h3>
                                            </div>
                                            <div class="panel-body">
                                                <div class="form-group">
                                                    <label class="cfg-titulo">Cliente de las boletas <?php cfg_info("El cliente \"Público general\": el que va en las ventas cuando el cajero no elige a nadie."); ?></label>
                                                    <select class="form-control" name="id_cliente_publico">
<?php cfg_opciones($opciones['cliente'], $config['id_cliente_publico'] ?? 0, 'EL PRIMER CLIENTE (N° más bajo)'); ?>
</select>
                                                </div>
                                                <div class="form-group">
                                                    <label class="cfg-titulo">Lista de precios normal <?php cfg_info("La lista de precios que usa la Caja. Cambiar a otra lista en una venta pide la clave de administrador."); ?></label>
                                                    <select class="form-control" name="id_lpreciopublico">
<?php cfg_opciones($opciones['lprecio'], $config['id_lpreciopublico'] ?? 0, 'LA PRIMERA LISTA (N° más bajo)'); ?>
</select>
                                                </div>
                                                <?php if (array_key_exists('usar_comision_web', $config)) : ?>
                                                <!-- v1.24.1: comisión opcional en el precio de los productos (Productos y Lista de Precios) -->
                                                <div class="cfg-fila"><label><input type="checkbox" name="usar_comision_web" value="1" <?php echo ($config['usar_comision_web'] ?? false) === true ? 'checked' : ''; ?>> Comisión en el precio de los productos</label><?php cfg_info("Marcada: al grabar el precio de un producto (Carta y Lista de precios) aparece el recuadro de comisión.\nSin marcar: el recuadro no aparece y la comisión queda en 0."); ?></div>
                                                <?php endif; ?>
                                                <div class="form-group">
                                                    <label class="cfg-titulo">Documento de venta <?php cfg_info("El documento que viene elegido al cobrar (ej. boleta)."); ?></label>
                                                    <select class="form-control" name="id_docventa_defecto">
<?php cfg_opciones($opciones['tdocumento'], $config['id_docventa_defecto'] ?? 0, 'SIN DEFINIR'); ?>
</select>
                                                </div>
                                                <div class="form-group">
                                                    <label class="cfg-titulo">Forma de pago <?php cfg_info("La forma de pago que viene elegida al cobrar (ej. efectivo)."); ?></label>
                                                    <select class="form-control" name="id_fpago_defecto">
<?php cfg_opciones($opciones['fpago'], $config['id_fpago_defecto'] ?? 0, 'SIN DEFINIR'); ?>
</select>
                                                </div>
                                                <div class="form-group">
                                                    <label class="cfg-titulo">Forma de pago que da vuelto <?php cfg_info("Solo los pagos con esta forma pueden tener vuelto (normalmente efectivo).\nCon tarjeta o transferencia no se puede pagar de más."); ?></label>
                                                    <select class="form-control" name="vueltofpago">
<?php cfg_opciones($opciones['fpago'], $config['vueltofpago'] ?? 0, 'LA QUE SE LLAME EFECTIVO'); ?>
</select>
                                                </div>
                                                <div class="form-group">
                                                    <label class="cfg-titulo">Comprobante interno <?php cfg_info("El documento sin valor tributario que usa la Caja para cortesías y ventas internas.\nSiempre está disponible, aunque no haya facturación electrónica."); ?></label>
                                                    <select class="form-control" name="id_tdocumento_ticket">
<?php cfg_opciones($opciones['tdocumento'], $config['id_tdocumento_ticket'] ?? 0, 'SIN DEFINIR'); ?>
</select>
                                                </div>
                                                <div class="form-group">
                                                    <label class="cfg-titulo">Impuesto IVA <?php cfg_info("Cuál de los impuestos es el IVA. Con él se calculan el neto y el IVA de las boletas y facturas."); ?></label>
                                                    <select class="form-control" name="id_impuesto_iva">
<?php cfg_opciones($opciones['impuesto'], $config['id_impuesto_iva'] ?? 0, 'SIN DEFINIR'); ?>
</select>
                                                </div>
                                                <hr>
                                                <div class="cfg-fila"><label><input type="checkbox" name="permitir_retiro_fondo_fijo" value="1" <?php echo ($config['permitir_retiro_fondo_fijo'] ?? false) ? 'checked' : ''; ?>> Permitir retirar el fondo de la caja</label><?php cfg_info("Marcada: en un retiro de dinero se puede sacar también el fondo con que se abrió la caja.\nSin marcar: el fondo siempre queda en la caja."); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title"><i class="fa fa-cutlery"></i>Inventario</h3>
                                            </div>
                                            <div class="panel-body">
                                                <div class="cfg-fila"><label><input type="checkbox" name="inventario_cierre_caja" value="1" <?php echo ($config['inventario_cierre_caja'] ?? false) ? 'checked' : ''; ?>> Contar insumos al cerrar la caja</label><?php cfg_info("Al cerrar la caja, el cajero cuenta los insumos marcados \"Inventario en cierre de turno\" (en Insumos) y el stock se ajusta a lo contado."); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab Facturación Electrónica -->
                            <div id="facturacion" class="tab">
                                <div class="row t-dos-columnas">
                                    <div class="col-md-6">
                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title"><i class="fa fa-file-text-o"></i>Facturación electrónica</h3>
                                            </div>
                                            <div class="panel-body">
                                                <div class="cfg-fila"><label><input type="checkbox" name="habilitar_facturacion_electronica" value="1" <?php echo ($config['habilitar_facturacion_electronica'] ?? false) ? 'checked' : ''; ?>> Usar boleta y factura electrónica</label><?php cfg_info("Marcada: la Caja puede emitir la boleta, la factura y la nota de crédito electrónicas que se eligen abajo.\nSin marcar: solo el comprobante interno."); ?></div>
                                                <hr>
                                                <div class="form-group">
                                                    <label class="cfg-titulo">Proveedor de facturación <?php cfg_info("La empresa que envía los documentos al SII por ti."); ?></label>
                                                    <div style="display: flex; gap: 20px; margin-top: 5px;">
                                                        <?php $esDatamaule = ($dte_auth['proveedor'] ?? 'acierto') === 'datamaule'; ?>
                                                        <label style="margin:0"><input type="radio" name="proveedor_dte" value="1" <?php echo $esDatamaule ? 'checked' : ''; ?>> datamaule.cl</label>
                                                        <label style="margin:0"><input type="radio" name="proveedor_dte" value="2" <?php echo !$esDatamaule ? 'checked' : ''; ?>> nubika</label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="cfg-titulo">Factura electrónica <?php cfg_info("El tipo de documento que se usa como factura electrónica (código SII 33)."); ?></label>
                                                    <select class="form-control" name="id_tdocumento_factura">
<?php cfg_opciones($opciones['tdoc_factura'], $config['id_tdocumento_factura'] ?? 0, 'SIN DEFINIR'); ?>
</select>
                                                </div>
                                                <div class="form-group">
                                                    <label class="cfg-titulo">Nota de crédito electrónica <?php cfg_info("El documento que se emite al devolver una venta electrónica (código SII 61)."); ?></label>
                                                    <select class="form-control" name="id_tdocumento_nota_credito">
<?php cfg_opciones($opciones['tdoc_nc'], $config['id_tdocumento_nota_credito'] ?? 0, 'SIN DEFINIR'); ?>
</select>
                                                </div>
                                                <div class="form-group">
                                                    <label class="cfg-titulo">Indicador de traslado <?php cfg_info("Dato que pide el SII en la factura: si la venta traslada mercadería y de qué tipo. En un restaurante normalmente es \"Operación constituye venta\"."); ?></label>
                                                    <select class="form-control" name="id_indicador_traslado_venta">
<?php cfg_opciones($opciones['indicador_traslado'], $config['id_indicador_traslado_venta'] ?? 0, 'SIN DEFINIR'); ?>
</select>
                                                </div>
                                                <div class="form-group">
                                                    <label class="cfg-titulo">Boleta electrónica <?php cfg_info("El tipo de documento que se usa como boleta electrónica (código SII 39)."); ?></label>
                                                    <select class="form-control" name="id_tdocumento_boleta_electronica">
<?php cfg_opciones($opciones['tdoc_boleta'], $config['id_tdocumento_boleta_electronica'] ?? 0, 'SIN DEFINIR'); ?>
</select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title"><i class="fa fa-print"></i>Impresión de documentos</h3>
                                            </div>
                                            <div class="panel-body">
                                                <div class="cfg-fila"><label><input type="checkbox" name="imprimir_pdf_dte" value="1" <?php echo ($config['imprimir_pdf_dte'] ?? false) ? 'checked' : ''; ?>> Imprimir la boleta o factura al cobrar</label><?php cfg_info("Marcada: al cobrar, la boleta o factura sale impresa sola.\nSin marcar: no se imprime sola (se puede reimprimir desde Opciones > Documentos)."); ?></div>
                                                <div class="form-group">
                                                    <label class="cfg-titulo">Impresora de documentos (opcional) <?php cfg_info("Solo si las boletas y facturas deben salir en otra impresora compartida de Windows, ej. //127.0.0.1/pos80.\nSi esa impresora no está en el agente de impresión, se usa la impresora de la caja."); ?></label>
                                                    <input type="text" class="form-control" name="ruta_dte_pdf" value="<?php echo $config['ruta_dte_pdf'] ?? '/17.0.1/caja'; ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label class="cfg-titulo">Ancho del papel <?php cfg_info("El ancho del papel de las boletas en milímetros: 80 o 58.\nEs el que se usa cuando el computador de la caja no eligió el suyo."); ?></label>
                                                    <input type="text" class="form-control" name="formato_dte" value="<?php echo $config['formato_dte'] ?? ''; ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label class="cfg-titulo">Copias de cada documento <?php cfg_info("Cuántas veces se imprime cada boleta o factura (de 1 a 5)."); ?></label>
                                                    <input type="number" class="form-control" name="copias_dte" value="<?php echo $config['copias_dte'] ?? 1; ?>">
                                                </div>
                                                <div class="cfg-fila"><label><input type="checkbox" name="imprimir_copia_cedible" value="1" <?php echo ($config['imprimir_copia_cedible'] ?? false) ? 'checked' : ''; ?>> Imprimir copia cedible</label><?php cfg_info("En las facturas a crédito sale además la copia \"CEDIBLE\", la que se usa para cobrarla o venderla (factoring)."); ?></div>
                                                <div class="cfg-fila"><label><input type="checkbox" name="enviar_dte_sii" value="1" <?php echo ($config['enviar_dte_sii'] ?? false) ? 'checked' : ''; ?>> Enviar los documentos al SII</label><?php cfg_info("Marcada: cada boleta y factura electrónica se manda al SII al emitirla.\nSin marcar: no se envía (solo para pruebas)."); ?></div>
                                                <div class="form-group">
                                                    <label class="cfg-titulo">Tiempo de espera (segundos) <?php cfg_info("Cuánto espera la Caja la respuesta del proveedor de facturación antes de darse por vencida. 20 es lo normal."); ?></label>
                                                    <input type="number" class="form-control" name="timeout" value="<?php echo $config['timeout'] ?? 20; ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title"><i class="fa fa-certificate"></i>Datos del SII <?php cfg_info("Los datos de la resolución del SII que autorizó a la empresa a emitir documentos electrónicos. Salen impresos en las boletas y facturas."); ?></h3>
                                            </div>
                                            <div class="panel-body">
                                                <div class="form-group">
                                                    <label class="cfg-titulo">Comuna SII <?php cfg_info("La comuna de la oficina del SII de la empresa (sale en los documentos)."); ?></label>
                                                    <select class="form-control" id="cboIdComunaSii" name="id_comuna_sii" data-selected="<?php echo $config['id_comuna_sii'] ?? 0; ?>">
                                                        <option value="0">Seleccione la comuna</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label class="cfg-titulo">N° de resolución SII <?php cfg_info("El número de la resolución que autorizó la facturación electrónica (0 si es de prueba)."); ?></label>
                                                    <input type="text" class="form-control" name="resolucion_sii" value="<?php echo $config['resolucion_sii'] ?? ''; ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label class="cfg-titulo">Año de la resolución <?php cfg_info("El año de esa resolución."); ?></label>
                                                    <input type="number" class="form-control" name="anio_resolucion_sii" value="<?php echo $config['anio_resolucion_sii'] ?? ''; ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title"><i class="fa fa-cloud"></i>Acceso facturación (<span id="lblProveedorDte"><?php echo $esDatamaule ? 'datamaule.cl' : 'Cloud Apps'; ?></span>)</h3>
                                            </div>
                                            <div class="panel-body">
                                                <?php $tipoAuthDte = $dte_auth['tipo'] ?? 'basic'; $tokenDte = $dte_auth['token'] ?? array('tiene' => false, 'last4' => ''); ?>
                                                <div class="form-group">
                                                    <label class="cfg-titulo">Tipo de autenticación <?php cfg_info("Cómo se envía la credencial al proveedor.\nCon datamaule.cl la credencial es la API Key de la empresa (Administración → Empresas → Integración Ventas); X-API-Key es lo recomendado."); ?></label>
                                                    <select class="form-control" name="tipo_auth_dte" id="cboTipoAuthDte">
                                                        <?php foreach (($dte_auth['etiquetas'] ?? array('basic' => 'Basic (usuario y contraseña)')) as $valor => $texto) : ?>
                                                            <option value="<?php echo $valor; ?>" <?php echo $valor === $tipoAuthDte ? 'selected' : ''; ?>><?php echo htmlspecialchars($texto, ENT_QUOTES); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <?php if (empty($dte_auth['columnas'])) : ?>
                                                        <p class="help-block text-warning"><i class="fa fa-exclamation-triangle"></i> Esta base aún no tiene las columnas de la migración 20261111100000: se aplica al entrar al sistema. Mientras tanto se emite con Cloud Apps y Basic, como antes.</p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="form-group">
                                                    <?php $rutEmpresaDte = !empty($empresa['rut_empresa']) ? number_format((float)$empresa['rut_empresa'], 0, '', '') . '-' . strtoupper(trim($empresa['dv_empresa'] ?? '')) : ''; ?>
                                                    <label class="cfg-titulo" for="txtUsuarioDte">Usuario <?php cfg_info('El RUT de la empresa con guion y sin puntos' . ($rutEmpresaDte ? ' (' . $rutEmpresaDte . ').' : '.')); ?></label>
                                                    <input type="text" class="form-control" name="usuario_dte" id="txtUsuarioDte" value="<?php echo htmlspecialchars($config['usuario_dte'] ?? '', ENT_QUOTES); ?>" placeholder="<?php echo $rutEmpresaDte ? 'Ej: ' . $rutEmpresaDte : 'RUT de la empresa'; ?>">
                                                </div>
                                                <div class="form-group" id="grupoClaveDte">
                                                    <label class="cfg-titulo" for="txtClaveDte">Contraseña <?php cfg_info("La contraseña del proveedor de facturación.\nCon datamaule.cl en Basic, la contraseña es la API Key de la empresa."); ?></label>
                                                    <input type="password" class="form-control" name="clave_usuario_dte" id="txtClaveDte" value="<?php echo htmlspecialchars($config['clave_usuario_dte'] ?? '', ENT_QUOTES); ?>">
                                                </div>
                                                <div class="form-group" id="grupoTokenDte">
                                                    <label class="cfg-titulo" for="txtTokenDte">API Key <?php cfg_info("La API Key de la empresa en el proveedor. Se guarda cifrada y no se vuelve a mostrar: déjala vacía para mantener la guardada."); ?></label>
                                                    <input type="password" class="form-control" name="token_dte" id="txtTokenDte" autocomplete="new-password" value=""
                                                        placeholder="<?php echo $tokenDte['tiene'] ? 'Guardada (termina en ' . htmlspecialchars($tokenDte['last4'], ENT_QUOTES) . '). Escriba una nueva solo para cambiarla' : 'Pegue aquí la API Key de la empresa'; ?>">
                                                    <?php if (empty($dte_auth['clave_cifrado'])) : ?><p class="help-block text-danger"><i class="fa fa-exclamation-triangle"></i> Falta la variable RESTO_DTE_TOKEN_KEY en el servidor: sin ella no se puede guardar ni usar la API Key.</p><?php endif; ?>
                                                    <?php if ($tokenDte['tiene']) : ?>
                                                        <label style="font-weight:normal"><input type="checkbox" name="quitar_token_dte" value="1" id="chkQuitarTokenDte"> Quitar la API Key guardada</label>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="form-group">
                                                    <label class="cfg-titulo" for="txtTerminalDte">Terminal <?php cfg_info("El nombre del terminal registrado en el proveedor (Cloud Apps o datamaule.cl) para la caja web.\nSin terminal no se emiten boletas ni facturas."); ?></label>
                                                    <input type="text" class="form-control" name="terminal_dte" id="txtTerminalDte" maxlength="60" value="<?php echo htmlspecialchars($config['terminal_dte'] ?? '', ENT_QUOTES); ?>" placeholder="Ej: DATAPDV-CAJA1">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title"><i class="fa fa-globe"></i>Direcciones del POS de escritorio <?php cfg_info("Direcciones que usa solo el programa de escritorio para traer el PDF, el código QR y los folios. La Caja web no las usa. No las cambies si no sabes para qué son."); ?></h3>
                                            </div>
                                            <div class="panel-body">
                                                <div class="form-group">
                                                    <label class="cfg-titulo">Ruta API Get PDF</label>
                                                    <input type="text" class="form-control" name="ruta_api_get_pdf" value="<?php echo $config['ruta_api_get_pdf'] ?? ''; ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label class="cfg-titulo">Ruta API Get QR</label>
                                                    <input type="text" class="form-control" name="ruta_api_get_qr" value="<?php echo $config['ruta_api_get_qr'] ?? ''; ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label class="cfg-titulo">Ruta DTE Código QR</label>
                                                    <input type="text" class="form-control" name="ruta_dte_qr" value="<?php echo $config['ruta_dte_qr'] ?? ''; ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label class="cfg-titulo">Ruta Consulta Folio DTE</label>
                                                    <input type="text" class="form-control" name="url_consulta_folio_dte" value="<?php echo $config['url_consulta_folio_dte'] ?? ''; ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="config-card">
                                            <div class="panel-heading">
                                                <h3 class="panel-title"><i class="fa fa-list-ol"></i>Folios electrónicos <?php cfg_info("Consulta en el proveedor elegido los folios (números) que el SII autorizó para cada documento, con el acceso y el terminal de arriba. No hace falta grabar antes.\nCon datamaule.cl el rango es el del CAF en uso, desde el próximo folio que se entregará."); ?></h3>
                                            </div>
                                            <div class="panel-body">
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

                            <!-- Tab Datos de la Empresa (11-11-2026): varias empresas y sus sucursales, con los datos de la intranet (Mempresas) -->
                            <div id="empresa" class="tab">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="config-card">
                                            <div class="panel-heading emp-cabecera">
                                                <h3 class="panel-title"><i class="fa fa-building-o"></i>Empresas <?php cfg_info("Empresas de esta base y sus sucursales. Los datos vienen de la intranet de Datamaule; las empresas las registran los técnicos de Datamaule.\nLas boletas, facturas y tickets de una caja (Herramientas > Control de cajas) llevan la dirección, comuna y código SII de su sucursal. Emite la primera empresa de la lista."); ?></h3>
                                                <button type="button" class="btn btn-default btn-sm" id="btnSincronizarEmpresas"><i class="fa fa-refresh"></i> Actualizar desde la intranet</button>
                                            </div>
                                            <div class="panel-body">
                                                <div class="table-responsive emp-tabla">
                                                    <table class="table table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th>RUT</th>
                                                                <th>Empresa</th>
                                                                <th>Giro</th>
                                                                <th>Dirección</th>
                                                                <th class="text-center">Sucursales</th>
                                                                <th>Intranet</th>
                                                                <th class="text-right"></th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="cuerpoEmpresas">
                                                            <tr><td colspan="7" class="emp-vacio"><i class="fa fa-spinner fa-spin"></i> Cargando…</td></tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php if ((int)$this->session->userdata('id_rol') === 1) : ?>
                            <!-- v1.27.0: respaldos de la base (libraries/Respaldo_bd.php). Solo administradores. -->
                            <div id="respaldos" class="tab">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="config-card">
                                            <div class="panel-heading emp-cabecera">
                                                <h3 class="panel-title"><i class="fa fa-database"></i>Respaldos de la base <?php cfg_info("Copias de seguridad de la base de esta empresa, guardadas en la carpeta backups del sistema.\nSe hace una sola al día, cuando alguien entra al sistema, o con el botón Respaldar ahora.\nSe guardan las de los últimos 7 días, 4 semanas y 3 meses; las más viejas se borran solas para que no pesen.\nUn respaldo tiene todos los datos de la empresa (ventas, clientes, claves): guárdalo en un lugar seguro y no lo compartas."); ?></h3>
                                                <button type="button" class="btn btn-orange btn-sm" id="btnRespaldarAhora"><i class="fa fa-floppy-o"></i> Respaldar ahora</button>
                                            </div>
                                            <div class="panel-body">
                                                <div id="respaldoAviso" class="alert alert-danger" style="display:none"></div>
                                                <div class="table-responsive emp-tabla">
                                                    <table class="table table-hover">
                                                        <thead><tr><th>Fecha</th><th class="text-right">Tamaño</th><th class="text-right"></th></tr></thead>
                                                        <tbody id="cuerpoRespaldos">
                                                            <tr><td colspan="3" class="emp-vacio"><i class="fa fa-spinner fa-spin"></i> Cargando…</td></tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <p class="emp-nota" id="respaldoTotal"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
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

<!-- Configuraciones > Datos de la Empresa: datos de una empresa -->
<div class="modal fade" id="modalEmpresa" tabindex="-1" role="dialog" aria-labelledby="tituloModalEmpresa">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="tituloModalEmpresa"><i class="fa fa-building-o"></i> Datos de la empresa</h4>
            </div>
            <div class="modal-body emp-form">
                <p class="emp-ayuda" id="ayudaModalEmpresa"></p>
                <div class="row">
                    <div class="col-sm-4 form-group"><label>RUT</label><input type="text" class="form-control" data-campo="rut_empresa" maxlength="10"></div>
                    <div class="col-sm-2 form-group"><label>DV</label><input type="text" class="form-control" data-campo="dv_empresa" maxlength="1"></div>
                    <div class="col-sm-6 form-group"><label>Razón social</label><input type="text" class="form-control" data-campo="razon_social"></div>
                    <div class="col-sm-3 form-group"><label>Nombre fantasía</label><input type="text" class="form-control" data-campo="nombre_fantasia"></div>
                    <div class="col-sm-3 form-group"><label>Código act. económica</label><input type="text" class="form-control" data-campo="codigo_actividad_economica"></div>
                    <div class="col-sm-6 form-group"><label>Giro</label><input type="text" class="form-control" data-campo="giro"></div>
                    <div class="col-sm-12 form-group"><label>Dirección</label><input type="text" class="form-control" data-campo="direccion_empresa"></div>
                    <div class="col-sm-4 form-group"><label>Comuna</label><select class="form-control" data-campo="id_comuna"></select></div>
                    <div class="col-sm-4 form-group"><label>Correo</label><input type="email" class="form-control" data-campo="mail"></div>
                    <div class="col-sm-4 form-group"><label>Teléfono</label><input type="text" class="form-control" data-campo="telefono"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarEmpresa"><i class="fa fa-check"></i> Grabar</button>
            </div>
        </div>
    </div>
</div>

<!-- Configuraciones > Datos de la Empresa: sucursales de una empresa (de la intranet) -->
<div class="modal fade" id="modalSucursales" tabindex="-1" role="dialog" aria-labelledby="tituloModalSucursales">
    <div class="modal-dialog emp-modal-ancho" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="tituloModalSucursales"><i class="fa fa-map-marker"></i> Sucursales</h4>
            </div>
            <div class="modal-body">
                <p class="emp-ayuda"><i class="fa fa-info-circle"></i> Las sucursales y sus datos vienen de la intranet. Aquí solo se completa el código SII de la sucursal (si la intranet no lo trae) y la comuna (si no calza con las comunas del sistema). Los cambios se guardan al instante.</p>
                <div class="table-responsive">
                    <table class="table emp-sucursales">
                        <thead>
                            <tr>
                                <th>Sucursal</th>
                                <th>Dirección</th>
                                <th>Comuna</th>
                                <th>Teléfono</th>
                                <th>Código SII</th>
                                <th class="text-center">Cajas</th>
                                <th class="text-center">Vigente</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoSucursales"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Datos de la Empresa: empresas y sucursales */
    .t-config .emp-cabecera { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; }
    .t-config .emp-cabecera .btn i { margin-right: 4px; color: inherit; }
    .emp-tabla { margin: 0; border: 0; }
    .emp-tabla th, .emp-sucursales th { font-size: 12px; text-transform: uppercase; letter-spacing: .3px; color: var(--t-texto-suave); white-space: nowrap; }
    .emp-tabla td, .emp-sucursales td { vertical-align: middle !important; }
    .emp-nombre { font-weight: 600; color: var(--t-texto-fuerte); }
    .emp-rut { white-space: nowrap; }
    .emp-sub { display: block; font-size: 12px; color: var(--t-texto-tenue); }
    .emp-chip { display: inline-block; padding: 1px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; white-space: nowrap; border: 1px solid var(--t-borde); background: #f6f7f9; color: var(--t-texto-tenue); }
    .emp-chip.ok { background: var(--t-primario-suave); color: var(--t-primario-osc); border-color: var(--t-primario-borde); }
    .emp-chip.aviso { background: #fff7e0; color: #8a6200; border-color: #f0dc9c; }
    .emp-acciones { white-space: nowrap; text-align: right; }
    .emp-acciones .btn { margin-left: 4px; }
    .emp-acciones .btn i { margin-right: 3px; color: inherit; }
    .emp-vacio { padding: 26px 10px !important; text-align: center; color: var(--t-texto-tenue); }
    .emp-nota { margin: 10px 0 0; font-size: 12px; color: var(--t-texto-tenue); }
    .emp-nota i { margin-right: 4px; }
    .emp-ayuda { font-size: 13px; color: var(--t-texto-tenue); margin-bottom: 14px; }
    .emp-ayuda i { margin-right: 4px; }
    .emp-form label { font-size: 12px; color: var(--t-texto-suave); text-transform: uppercase; letter-spacing: .3px; }
    .emp-form .form-control[disabled] { background: var(--t-fondo-alt); color: var(--t-texto); cursor: not-allowed; }
    .emp-form .emp-de-intranet { margin-left: 6px; font-size: 10px; text-transform: none; letter-spacing: 0; color: var(--t-primario-osc); }
    .emp-sucursales tr.emp-no-vigente td { color: var(--t-texto-tenue); background: var(--t-fondo-alt); }
    .emp-sucursales select.form-control, .emp-sucursales input.form-control { height: 32px; padding: 4px 8px; min-width: 150px; }
    .emp-sucursales input.emp-codigo { min-width: 110px; max-width: 130px; }
    .emp-modal-ancho { width: 96%; max-width: 1100px; }
    @media (max-width: 767px) { .emp-modal-ancho { width: auto; margin: 10px; } .emp-acciones { white-space: normal; } }
</style>





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

    /* v1.29.1: cada opción con su botón (i): la explicación sale en una burbuja (js.php > cfgInfo) */
    .t-config .cfg-fila { display: flex; align-items: flex-start; gap: 2px; }
    .t-config .cfg-fila > label { flex: 1 1 auto; min-width: 0; }
    .t-config .cfg-fila > .cfg-info { margin-top: 7px; }
    .t-config .cfg-fila.cfg-boton { align-items: center; gap: 6px; margin: 8px 0 4px; }
    .t-config .cfg-fila.cfg-boton > .cfg-info { margin-top: 0; }
    .t-config .form-group > label.cfg-titulo { display: flex; align-items: center; gap: 6px; }
    .t-config .cfg-subtitulo { display: flex; align-items: center; gap: 6px; margin: 12px 6px 2px; font-size: 12px; font-weight: 700;
        color: var(--t-texto-suave); text-transform: uppercase; letter-spacing: .3px; }
    .t-config .panel-title .cfg-info { margin-left: 6px; text-transform: none; letter-spacing: 0; }
    .cfg-info {
        flex: 0 0 auto; display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; padding: 0;
        border-radius: 50%; border: 1.5px solid var(--t-primario, #15803D); background: transparent; color: var(--t-primario, #15803D);
        font-size: 11px; line-height: 1; cursor: help; vertical-align: middle; transition: background-color .15s, color .15s;
    }
    .t-config .panel-title .cfg-info i { margin: 0; font-size: 11px; color: inherit; }
    .cfg-info:hover, .cfg-info[aria-expanded="true"] { background: var(--t-primario, #15803D); color: #fff; }
    .cfg-info:focus-visible { outline: 2px solid var(--t-primario, #15803D); outline-offset: 2px; }
    #cfgBurbuja {
        position: fixed; z-index: 3000; max-width: min(340px, calc(100vw - 24px)); padding: 10px 13px; border-radius: 10px;
        background: #1f2937; color: #f9fafb; font-size: 13px; font-weight: 400; line-height: 1.5; text-transform: none; letter-spacing: 0;
        white-space: pre-line; box-shadow: 0 10px 30px rgba(0, 0, 0, .25); pointer-events: none; opacity: 0; transition: opacity .12s;
    }
    #cfgBurbuja.visible { opacity: 1; }
    @media (prefers-reduced-motion: reduce) { #cfgBurbuja { transition: none; } }
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
    /* Facturación: dos columnas fijas (izquierda y derecha) y Folios a todo el ancho, sin huecos */
    @media (min-width: 992px) {
        .t-config .tab > .row.t-dos-columnas { column-count: auto; display: flex; flex-wrap: wrap; align-items: flex-start; gap: 0 18px; }
        /* Bootstrap 3 pone ::before/::after (clearfix) en .row: con flex ocupan lugar y empujan la columna derecha abajo */
        .t-config .tab > .row.t-dos-columnas::before,
        .t-config .tab > .row.t-dos-columnas::after { display: none; content: none; }
        .t-config .tab > .row.t-dos-columnas > .col-md-6 { flex: 1 1 0; min-width: 0; width: auto; display: block; }
        .t-config .tab > .row.t-dos-columnas > .col-md-12 { flex: 0 0 100%; width: 100%; display: block; }
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
