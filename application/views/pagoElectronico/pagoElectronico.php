<style>
    /* Mantención > Pago electrónico (19-11-2026) */
    .pe-panel > .panel-heading { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
    .pe-panel > .panel-heading .panel-title { display: flex; align-items: center; margin: 0; }
    .pe-panel .pe-acciones .btn i { color: inherit; margin-right: 4px; }
    .pe-ayuda { margin: 0; padding: 12px 18px 0; font-size: 13px; color: var(--t-texto-tenue); }
    .pe-ayuda i { margin-right: 4px; }
    .pe-tabla { padding: 12px 18px 18px; margin: 0; border: 0; }
    .pe-tabla table { width: 100%; margin: 0; }
    .pe-tabla th { font-size: 12px; text-transform: uppercase; letter-spacing: .3px; color: var(--t-texto-suave); white-space: nowrap; }
    .pe-tabla td { vertical-align: middle !important; }
    .pe-tabla tbody tr { cursor: pointer; }
    .pe-tabla tr.pe-no-vigente td { color: var(--t-texto-tenue); background: var(--t-fondo-alt); }
    .pe-nombre { font-weight: 600; color: var(--t-texto-fuerte); }
    .pe-sub { display: block; font-size: 12px; color: var(--t-texto-tenue); }
    .pe-chip { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 12px; font-weight: 600; white-space: nowrap; margin: 1px 2px 1px 0; }
    .pe-chip.si { background: var(--t-primario-suave); color: var(--t-primario-osc); border: 1px solid var(--t-primario-borde); }
    .pe-chip.no { background: #f6f7f9; color: var(--t-texto-tenue); border: 1px solid var(--t-borde); }
    .pe-chip.fp { background: #eef4ff; color: #2c4f8a; border: 1px solid #cfdcf3; }
    .pe-i-titulo { margin-left: 8px; color: var(--t-primario, #15803D); cursor: help; font-size: 15px; }
    .pe-vacio { padding: 30px 10px; text-align: center; color: var(--t-texto-tenue); }
    .pe-vacio i { display: block; font-size: 30px; margin-bottom: 8px; }
    #modalPagoElectronico .modal-dialog { width: 1100px; max-width: calc(100vw - 24px); }
    /* v1.39.x: a lo ancho (grilla de 6) y la ayuda en el (i) */
    #modalPagoElectronico .pe-grid { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 12px 14px; align-items: end; }
    #modalPagoElectronico .pe-c1 { grid-column: span 1; } #modalPagoElectronico .pe-c2 { grid-column: span 2; }
    #modalPagoElectronico .pe-c3 { grid-column: span 3; } #modalPagoElectronico .pe-c4 { grid-column: span 4; }
    #modalPagoElectronico .pe-c6 { grid-column: 1 / -1; }
    #modalPagoElectronico .pe-sep { margin-top: 4px; padding-top: 12px; border-top: 1px solid var(--t-borde-suave); font-size: 13px; font-weight: 700;
        color: var(--t-texto-fuerte); text-transform: uppercase; letter-spacing: .3px; }
    #modalPagoElectronico .pe-i { color: var(--t-primario, #15803D); cursor: help; margin-left: 3px; font-size: 13px; }
    #modalPagoElectronico .pe-oculta { display: none; }
    #modalPagoElectronico .pe-c .pe-formas label { height: 36px; }
    @media (max-width: 860px) { #modalPagoElectronico .pe-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        #modalPagoElectronico .pe-c3, #modalPagoElectronico .pe-c4 { grid-column: 1 / -1; } }
    #modalPagoElectronico fieldset { margin: 0 -8px; }
    #modalPagoElectronico fieldset::after { content: ""; display: table; clear: both; }
    #modalPagoElectronico fieldset > [class*="col-"] { padding: 0 8px; margin-bottom: 14px; }
    #modalPagoElectronico label { font-size: 12px; color: var(--t-texto-suave); text-transform: uppercase; letter-spacing: .3px; margin-bottom: 5px; }
    #modalPagoElectronico .help-block { font-size: 12px; margin: 4px 0 0; }
    #modalPagoElectronico .pe-titulo { margin: 4px 0 12px; padding-top: 12px; border-top: 1px solid var(--t-borde-suave); font-size: 13px; font-weight: 700;
        color: var(--t-texto-fuerte); text-transform: uppercase; letter-spacing: .3px; }
    #modalPagoElectronico .pe-titulo:first-child { padding-top: 0; border-top: 0; }
    #modalPagoElectronico .pe-titulo small { text-transform: none; letter-spacing: 0; font-weight: 400; color: var(--t-texto-tenue); }
    .pe-formas { display: flex; flex-wrap: wrap; gap: 8px; }
    .pe-formas label { display: inline-flex; align-items: center; gap: 6px; margin: 0; padding: 7px 12px; border: 1px solid var(--t-borde); border-radius: var(--t-radio-sm);
        background: var(--t-fondo-alt); font-size: 13px; font-weight: 600; color: var(--t-texto); text-transform: none; letter-spacing: 0; cursor: pointer; }
    .pe-formas input { margin: 0; }
    .pe-clave { display: flex; gap: 6px; }
    .pe-clave .form-control { flex: 1 1 auto; }
    #resultadoPrueba { margin: 0 0 12px; }
    #resultadoPrueba:empty { display: none; }
    #modalPagoElectronico .modal-footer { display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-end; }
    #modalPagoElectronico .modal-footer .btn { margin: 0; }
    #modalPagoElectronico .modal-footer .btn i { margin-right: 4px; }
    #btnProbarPos { margin-right: auto !important; }
    @media (max-width: 767px) {
        .pe-panel .pe-acciones, .pe-panel .pe-acciones .btn { width: 100%; }
        .pe-tabla { padding: 10px; }
        #btnProbarPos { margin-right: 0 !important; width: 100%; }
    }
</style>
<div class="content-header">
    <div class="header-icon">
        <i class="fa fa-credit-card"></i>
    </div>
    <div class="header-title">
        <h1>Pago electrónico</h1>
        <small>Máquinas POS y los datos que la Caja envía al programa de pago</small>
        <ol class="breadcrumb">
            <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i> Inicio</a></li>
            <li class="active">Pago electrónico</li>
        </ol>
    </div>
</div>

<div class="container-padding">
    <div class="row">
        <div class="col-xs-12 m-b-20">
            <div class="panel panel-success lobidisable pe-panel">
                <div class="panel-heading">
                    <div class="panel-title">
                        <i class="fa fa-credit-card"></i>
                        <h4>Máquinas de pago</h4>
                        <i class="fa fa-info-circle pe-i-titulo" title="Cada máquina tiene los datos que la Caja envía al programa de pago del equipo de la caja (localhost:5005/panel); si alguno no es igual, la Caja muestra cuál.&#10;&#10;1. En Herramientas › Control de cajas, elige qué máquina usa cada caja.&#10;2. En Mantención › Formas de pago, marca Pago electrónico en cada tarjeta de débito o crédito que quieras cobrar en el POS.&#10;&#10;Haz clic en una máquina para editarla."></i>
                    </div>
                    <div class="pe-acciones">
                        <button id="btnNuevaMaquina" type="button" class="btn btn-primary"><i class="fa fa-plus"></i> Nueva máquina</button>
                    </div>
                </div>
                <div class="alert alert-warning" id="avisoCredenciales" style="display:none;margin:12px 18px 0">
                    <i class="fa fa-key"></i> Falta el <b>usuario</b> o la <b>clave de la API</b> del programa de pago. Configúralos en
                    <a href="<?php echo base_url() ?>index.php/Configuraciones">Configuraciones &rsaquo; General &rsaquo; Pago electrónico</a>.
                </div>
                <div class="table-responsive pe-tabla">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Máquina</th>
                                <th>Sucursal / Caja</th>
                                <th>Terminal ID</th>
                                <th class="text-center">Cajas</th>
                                <th class="text-center">Vigente</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoPagoElectronico">
                            <tr><td colspan="5" class="pe-vacio"><i class="fa fa-spinner fa-spin"></i> Cargando…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPagoElectronico" tabindex="-1" role="dialog" aria-labelledby="tituloPagoElectronico">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="frmPagoElectronico" autocomplete="off">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="tituloPagoElectronico">Nueva máquina</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="peId" value="0">
                    <!-- v1.39.x (pedido del usuario): a lo ancho y con las explicaciones en el (i) en vez de texto bajo cada campo -->
                    <div class="pe-grid">
                        <div class="pe-c pe-c4">
                            <label for="peModelo">Máquina de pago</label>
                            <select class="form-control" name="modelo" id="peModelo" required></select>
                        </div>
                        <div class="pe-c pe-c2">
                            <label>Estado <i class="fa fa-info-circle pe-i" title="Para cobrar con una tarjeta marcada como Pago electrónico, la caja necesita una máquina vigente."></i></label>
                            <div class="pe-formas"><label><input type="checkbox" name="vigente" id="peVigente" value="1" checked> Vigente</label></div>
                        </div>
                        <div class="pe-c pe-c6 pe-sep">Datos de la petición <i class="fa fa-info-circle pe-i" title="Deben ser iguales a los del panel del programa de pago (ApiPagoElectronico). Si alguno no es igual, la Caja muestra cuál."></i></div>
                        <div class="pe-c pe-c1">
                            <label for="peSucursal">ID de sucursal <i class="fa fa-info-circle pe-i" title="El ID_SUCURSAL del programa."></i></label>
                            <input type="text" class="form-control" name="id_sucursal_pos" id="peSucursal" maxlength="30" required>
                        </div>
                        <div class="pe-c pe-c1">
                            <label for="peCaja">Nombre de caja <i class="fa fa-info-circle pe-i" title="El NOMBRE_CAJA del programa."></i></label>
                            <input type="text" class="form-control" name="nombre_caja_pos" id="peCaja" maxlength="60" placeholder="CAJA1" style="text-transform:uppercase" required>
                        </div>
                        <div class="pe-c pe-c1">
                            <label for="peTimeout">Espera (seg) <i class="fa fa-info-circle pe-i" title="Cuántos segundos espera la Caja la respuesta de la máquina. Entre 30 y 300."></i></label>
                            <input type="number" class="form-control" name="timeout_seg" id="peTimeout" min="30" max="300" step="1" value="120" required>
                        </div>
                        <div class="pe-c pe-c3">
                            <label for="peTerminal">Terminal ID <i class="fa fa-info-circle pe-i" data-de="#ayudaTerminal"></i></label>
                            <input type="text" class="form-control" name="terminal_id" id="peTerminal" maxlength="100" required>
                            <p class="help-block pe-oculta" id="ayudaTerminal"></p>
                        </div>
                        <div class="pe-c pe-c6" id="grupoApiKey" style="display:none">
                            <label for="peApiKey">API key <i class="fa fa-info-circle pe-i" data-de="#ayudaApiKey"></i></label>
                            <div class="pe-clave">
                                <input type="password" class="form-control" name="api_key" id="peApiKey" maxlength="300" autocomplete="new-password">
                                <button type="button" class="btn btn-default" id="btnBorrarApiKey" style="display:none" title="Quitar la API key guardada"><i class="fa fa-eraser"></i></button>
                            </div>
                            <p class="help-block pe-oculta" id="ayudaApiKey"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div id="resultadoPrueba" role="status" style="width:100%"></div>
                    <button type="button" class="btn btn-default" id="btnProbarPos" title="Comprueba ApiPagoElectronico y permite enviar un cobro real de $100 desde este equipo"><i class="fa fa-plug"></i> Probar máquina</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" id="btnGuardarPos"><i class="fa fa-save"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
