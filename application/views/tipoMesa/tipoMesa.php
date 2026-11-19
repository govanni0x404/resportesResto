<style>
    /* Mantención > Recintos > Tipos de mesa (19-11-2026) */
    .tm-panel > .panel-heading { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
    .tm-panel > .panel-heading .panel-title { display: flex; align-items: center; margin: 0; }
    .tm-panel .tm-acciones .btn i { color: inherit; margin-right: 4px; }
    .tm-ayuda { margin: 0; padding: 12px 18px 0; font-size: 13px; color: var(--t-texto-tenue); }
    .tm-ayuda i { margin-right: 4px; }
    .tm-tabla { padding: 12px 18px 18px; margin: 0; border: 0; }
    .tm-tabla table { width: 100%; margin: 0; }
    .tm-tabla th { font-size: 12px; text-transform: uppercase; letter-spacing: .3px; color: var(--t-texto-suave); white-space: nowrap; }
    .tm-tabla td { vertical-align: middle !important; }
    .tm-tabla tbody tr[data-id] { cursor: pointer; }
    .tm-tabla tr.tm-no-vigente td { color: var(--t-texto-tenue); background: var(--t-fondo-alt); }
    .tm-nombre { font-weight: 600; color: var(--t-texto-fuerte); }
    .tm-ejemplo { display: inline-block; padding: 2px 10px; border-radius: 10px; font-size: 13px; font-weight: 600; white-space: nowrap;
        background: var(--t-primario-suave); color: var(--t-primario-osc); border: 1px solid var(--t-primario-borde); }
    .tm-chip { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 12px; font-weight: 600; white-space: nowrap; }
    .tm-chip.si { background: var(--t-primario-suave); color: var(--t-primario-osc); border: 1px solid var(--t-primario-borde); }
    .tm-chip.no { background: #f6f7f9; color: var(--t-texto-tenue); border: 1px solid var(--t-borde); }
    .tm-vacio { padding: 30px 10px; text-align: center; color: var(--t-texto-tenue); }
    .tm-vacio i { display: block; font-size: 30px; margin-bottom: 8px; }
    #modalTipoMesa .modal-dialog { max-width: 560px; width: auto; }
    #modalTipoMesa fieldset { margin: 0 -8px; }
    #modalTipoMesa fieldset::after { content: ""; display: table; clear: both; }
    #modalTipoMesa fieldset > [class*="col-"] { padding: 0 8px; margin-bottom: 14px; }
    #modalTipoMesa label.tm-etq { font-size: 12px; color: var(--t-texto-suave); text-transform: uppercase; letter-spacing: .3px; margin-bottom: 5px; }
    #modalTipoMesa .help-block { font-size: 12px; margin: 4px 0 0; }
    .tm-opciones { display: flex; flex-wrap: wrap; gap: 8px; }
    .tm-opciones label { display: inline-flex; align-items: center; gap: 6px; margin: 0; padding: 7px 12px; border: 1px solid var(--t-borde); border-radius: var(--t-radio-sm);
        background: var(--t-fondo-alt); font-size: 13px; font-weight: 600; color: var(--t-texto); cursor: pointer; }
    .tm-opciones input { margin: 0; }
    .tm-vista { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border: 1px dashed var(--t-borde); border-radius: var(--t-radio-sm); background: var(--t-fondo-alt); }
    .tm-vista span { font-size: 12px; color: var(--t-texto-suave); text-transform: uppercase; letter-spacing: .3px; }
    .tm-vista b { font-size: 18px; color: var(--t-texto-fuerte); }
    #modalTipoMesa .modal-footer .btn i { margin-right: 4px; }
    @media (max-width: 767px) {
        .tm-panel .tm-acciones, .tm-panel .tm-acciones .btn { width: 100%; }
        .tm-tabla { padding: 10px; }
    }
</style>
<div class="content-header">
    <div class="header-icon">
        <i class="fa fa-tags"></i>
    </div>
    <div class="header-title">
        <h1>Tipos de mesa</h1>
        <small>El tipo cambia el nombre con que la mesa sale en la Caja</small>
        <ol class="breadcrumb">
            <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i> Mantencion</a></li>
            <li class="active">Tipos de mesa</li>
        </ol>
    </div>
</div>

<div class="container-padding">
    <div class="row">
        <div class="col-xs-12 m-b-20">
            <div class="panel panel-success lobidisable tm-panel">
                <div class="panel-heading">
                    <div class="panel-title">
                        <i class="fa fa-tags"></i>
                        <h4>Tipos de mesa</h4>
                    </div>
                    <div class="tm-acciones">
                        <button id="btnNuevoTipo" type="button" class="btn btn-primary"><i class="fa fa-plus"></i> Nuevo tipo</button>
                    </div>
                </div>
                <p class="tm-ayuda"><i class="fa fa-info-circle"></i>
                    Cada tipo tiene un texto que va antes del número de la mesa. Ej: el tipo <b>Delivery</b> con texto <b>D</b> hace que la mesa 1 salga como
                    <b>Mesa D1</b>; sin texto sale como siempre, <b>Mesa 1</b>. El tipo de cada mesa se elige en <b>Herramientas &rsaquo; Mesas</b>.
                    Si en el plano de la Caja le pusiste un nombre propio a la mesa, sale ese nombre. Haz clic en una fila para editarla.
                </p>
                <div class="table-responsive tm-tabla">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Así sale la mesa 1</th>
                                <th class="text-center">Mesas</th>
                                <th class="text-center">Vigente</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTipoMesa">
                            <tr><td colspan="4" class="tm-vacio"><i class="fa fa-spinner fa-spin"></i> Cargando…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTipoMesa" tabindex="-1" role="dialog" aria-labelledby="tituloTipoMesa">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="frmTipoMesa" autocomplete="off">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="tituloTipoMesa">Nuevo tipo de mesa</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="tmId" value="0">
                    <fieldset>
                        <div class="col-sm-6">
                            <label class="tm-etq" for="tmNombre">Nombre del tipo</label>
                            <input type="text" class="form-control" name="nombre" id="tmNombre" maxlength="40" placeholder="Ej: Delivery" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="tm-etq" for="tmPrefijo">Texto antes del número</label>
                            <input type="text" class="form-control" name="prefijo" id="tmPrefijo" maxlength="20" placeholder="Ej: D (vacío = Mesa 1)">
                        </div>
                        <div class="col-sm-12">
                            <div class="tm-opciones">
                                <label><input type="checkbox" id="tmEspacio"> Con espacio antes del número</label>
                                <label><input type="checkbox" id="tmVigente" checked> Vigente</label>
                            </div>
                            <p class="help-block">Sin espacio: <b>Mesa D1</b>. Con espacio: <b>Mesa EVENT 1</b>. Un tipo no vigente no se puede elegir para mesas nuevas.</p>
                        </div>
                        <div class="col-sm-12">
                            <div class="tm-vista"><span>Así sale la mesa 1</span><b id="tmVista">Mesa 1</b></div>
                        </div>
                    </fieldset>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" id="btnGuardarTipo"><i class="fa fa-save"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
