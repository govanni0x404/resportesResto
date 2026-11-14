<style>
    /* Herramientas > Control de cajas (11-11-2026) */
    .cc-panel > .panel-heading { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
    .cc-panel > .panel-heading .panel-title { display: flex; align-items: center; margin: 0; }
    .cc-panel .cc-acciones .btn i { color: inherit; margin-right: 4px; }
    .cc-ayuda { margin: 0; padding: 12px 18px 0; font-size: 13px; color: var(--t-texto-tenue); }
    .cc-ayuda i { margin-right: 4px; }
    .cc-tabla { padding: 12px 18px 18px; margin: 0; border: 0; }
    .cc-tabla table { width: 100%; margin: 0; }
    .cc-tabla th { font-size: 12px; text-transform: uppercase; letter-spacing: .3px; color: var(--t-texto-suave); white-space: nowrap; }
    .cc-tabla td { vertical-align: middle !important; }
    .cc-tabla select.form-control { min-width: 170px; height: 34px; padding: 4px 8px; }
    .cc-tabla tr.cc-no-vigente td { color: var(--t-texto-tenue); background: var(--t-fondo-alt); }
    .cc-tabla tr.cc-guardando { opacity: .6; }
    .cc-nombre { font-weight: 600; color: var(--t-texto-fuerte); }
    .cc-sub { display: block; font-size: 12px; color: var(--t-texto-tenue); }
    .cc-chip { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 12px; font-weight: 600; white-space: nowrap; }
    .cc-chip.si { background: var(--t-primario-suave); color: var(--t-primario-osc); border: 1px solid var(--t-primario-borde); }
    .cc-chip.no { background: #f6f7f9; color: var(--t-texto-tenue); border: 1px solid var(--t-borde); }
    .cc-chip.uso { background: #fff7e0; color: #8a6200; border: 1px solid #f0dc9c; }
    .cc-switch { display: inline-flex; align-items: center; gap: 6px; margin: 0; font-weight: 400; cursor: pointer; white-space: nowrap; }
    .cc-switch input { margin: 0; width: 16px; height: 16px; }
    .cc-vacio { padding: 30px 10px; text-align: center; color: var(--t-texto-tenue); }
    .cc-vacio i { display: block; font-size: 30px; margin-bottom: 8px; }
    .cc-pie { padding: 0 18px 14px; font-size: 12px; color: var(--t-texto-tenue); }
    .cc-filtros { display: flex; flex-wrap: wrap; gap: 12px 16px; padding: 14px 18px 0; }
    .cc-filtro { flex: 1 1 280px; max-width: 460px; }
    .cc-filtro label { display: block; font-size: 12px; text-transform: uppercase; letter-spacing: .3px; color: var(--t-texto-suave); margin-bottom: 5px; }
    .cc-por-sucursal .cc-col-sucursal { display: none; }
    @media (max-width: 767px) {
        .cc-panel .cc-acciones, .cc-panel .cc-acciones .btn { width: 100%; }
        .cc-tabla { padding: 10px; }
    }
</style>
<div class="content-header">
    <div class="header-icon">
        <i class="fa fa-desktop"></i>
    </div>
    <div class="header-title">
        <h1>Control de cajas</h1>
        <small>Cajas autorizadas en la intranet: lista de precios y bodega de cada una</small>
        <ol class="breadcrumb">
            <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i> Herramientas</a></li>
            <li class="active">Control de cajas</li>
        </ol>
    </div>
</div>

<div class="container-padding">
    <div class="row">
        <div class="col-xs-12 m-b-20">
            <div class="panel panel-success lobidisable cc-panel">
                <div class="panel-heading">
                    <div class="panel-title">
                        <i class="fa fa-desktop"></i>
                        <h4>Cajas</h4>
                    </div>
                    <div class="cc-acciones">
                        <button id="btnSincronizarCajas" type="button" class="btn btn-primary"><i class="fa fa-refresh"></i> Actualizar desde la intranet</button>
                    </div>
                </div>
                <!-- 12-11-2026: primero la empresa y la sucursal (Configuraciones > Datos de la Empresa); la tabla muestra las cajas de esa sucursal -->
                <div class="cc-filtros" id="filtrosControlCaja" style="display:none">
                    <div class="cc-filtro">
                        <label for="cboEmpresaCC">Empresa</label>
                        <select id="cboEmpresaCC" class="form-control"></select>
                    </div>
                    <div class="cc-filtro">
                        <label for="cboSucursalCC">Sucursal</label>
                        <select id="cboSucursalCC" class="form-control"></select>
                    </div>
                </div>
                <p class="cc-ayuda"><i class="fa fa-info-circle"></i>
                    <span id="ayudaControlCaja">Las cajas, su número, nombre y terminal vienen de la intranet.</span> Aquí se elige la lista de precios y la bodega de cada caja;
                    los cambios se guardan al instante. Al abrir la Caja, el cajero elige una de las cajas vigentes y se usan su lista y su bodega.
                </p>
                <div class="table-responsive cc-tabla">
                    <table class="table table-hover" id="tablaControlCaja">
                        <thead>
                            <tr>
                                <th class="text-center">Caja N°</th>
                                <th>Nombre</th>
                                <th>Terminal</th>
                                <th class="text-center cc-col-sucursal">Sucursal</th>
                                <th>Lista de precios</th>
                                <th>Bodega</th>
                                <th class="text-center">Cambiar lista en caja</th>
                                <th class="text-center">Vigente</th>
                                <th>En uso</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoControlCaja">
                            <tr><td colspan="9" class="cc-vacio"><i class="fa fa-spinner fa-spin"></i> Cargando…</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="cc-pie" id="pieControlCaja"></div>
            </div>
        </div>
    </div>
</div>
