<style>
    /* v1.35.0: Tablas > Categorías de agregados */
    .ca-panel > .panel-heading { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
    .ca-panel > .panel-heading .panel-title { display: flex; align-items: center; margin: 0; }
    .ca-panel .ca-acciones .btn i { color: inherit; margin-right: 4px; }
    .ca-ayuda { margin: 0; padding: 12px 18px 0; font-size: 13px; color: var(--t-texto-tenue); }
    .ca-ayuda i { margin-right: 4px; }
    .ca-aviso { margin: 12px 18px 0; padding: 10px 14px; border-radius: 10px; font-size: 13px; background: #FFFBEB; border: 1px solid #FCD34D; color: #92400E; }
    .ca-aviso a { font-weight: 700; color: #92400E; text-decoration: underline; }
    .ca-tabla { padding: 12px 18px 18px; margin: 0; border: 0; }
    .ca-tabla table { width: 100%; margin: 0; }
    .ca-tabla th { font-size: 12px; text-transform: uppercase; letter-spacing: .3px; color: var(--t-texto-suave); white-space: nowrap; }
    .ca-tabla td { vertical-align: middle !important; }
    .ca-tabla tr.ca-no-vigente td { color: var(--t-texto-tenue); background: var(--t-fondo-alt); }
    .ca-nombre { font-weight: 700; color: var(--t-texto-fuerte); text-transform: uppercase; }
    .ca-chip { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 12px; font-weight: 600; white-space: nowrap; }
    .ca-chip.si { background: var(--t-primario-suave); color: var(--t-primario-osc); border: 1px solid var(--t-primario-borde); }
    .ca-chip.no { background: #f6f7f9; color: var(--t-texto-tenue); border: 1px solid var(--t-borde); }
    .ca-botones { display: flex; gap: 6px; justify-content: flex-end; flex-wrap: wrap; }
    .ca-botones .btn i { margin-right: 3px; }
    .ca-vacio { padding: 30px 10px; text-align: center; color: var(--t-texto-tenue); }
    .ca-vacio i { display: block; font-size: 30px; margin-bottom: 8px; }
    /* Ventanas */
    .ca-modal .modal-header { border-top: 3px solid var(--t-primario); }
    .ca-modal label.ca-etq { font-size: 12px; color: var(--t-texto-suave); text-transform: uppercase; letter-spacing: .3px; margin-bottom: 5px; }
    .ca-modal fieldset { margin: 0 -8px; }
    .ca-modal fieldset::after { content: ""; display: table; clear: both; }
    .ca-modal fieldset > [class*="col-"] { padding: 0 8px; margin-bottom: 14px; }
    .ca-modal .modal-footer .btn i { margin-right: 4px; }
    #modalCategoriaAg .modal-dialog { max-width: 520px; width: auto; }
    #modalAsignarAg .modal-dialog { width: 860px; max-width: calc(100vw - 24px); }
    .ca-filtros { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px; }
    .ca-filtros .ca-buscar { flex: 1 1 220px; position: relative; }
    .ca-filtros .ca-buscar i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--t-texto-tenue); }
    .ca-filtros .ca-buscar input { padding-left: 32px; }
    .ca-filtros select { flex: 0 1 240px; }
    .ca-filtros .btn i { margin-right: 4px; }
    .ca-lista { max-height: 52vh; overflow-y: auto; border: 1px solid var(--t-borde); border-radius: 10px; }
    .ca-op { display: flex; align-items: center; gap: 10px; margin: 0; padding: 8px 12px; border-bottom: 1px solid var(--t-borde-suave, #EEF0F3); cursor: pointer; font-weight: 400; }
    .ca-op:last-child { border-bottom: 0; }
    .ca-op:hover { background: var(--t-fondo-alt); }
    .ca-op.marcado { background: var(--t-primario-suave); }
    .ca-op input { margin: 0; flex: 0 0 auto; width: 17px; height: 17px; }
    .ca-op .ca-ag { flex: 1 1 auto; min-width: 0; }
    .ca-op .ca-ag b { display: block; font-size: 13.5px; color: var(--t-texto-fuerte); }
    .ca-op .ca-ag small { display: block; font-size: 12px; color: var(--t-texto-suave); }
    .ca-op .ca-actual { flex: 0 0 auto; font-size: 11.5px; padding: 2px 8px; border-radius: 9px; background: #f6f7f9; border: 1px solid var(--t-borde); color: var(--t-texto-suave); white-space: nowrap; }
    .ca-op .ca-actual.esta { background: var(--t-primario-suave); border-color: var(--t-primario-borde); color: var(--t-primario-osc); font-weight: 700; }
    .ca-pie { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px; margin-top: 8px; font-size: 13px; color: var(--t-texto-suave); }
    .ca-pie b { color: var(--t-texto-fuerte); }
    @media (max-width: 767px) {
        .ca-panel .ca-acciones, .ca-panel .ca-acciones .btn { width: 100%; }
        .ca-tabla { padding: 10px; }
        .ca-botones { justify-content: flex-start; }
        .ca-filtros select { flex: 1 1 100%; }
    }
</style>
<div class="content-header">
    <div class="header-icon">
        <i class="fa fa-th-large"></i>
    </div>
    <div class="header-title">
        <h1>Categorías de agregados</h1>
        <small>Ordena los agregados (Proteína, Verdura, Salsas...) para que en la Caja salgan en un cuadro por categoría</small>
        <ol class="breadcrumb">
            <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i> Mantencion</a></li>
            <li class="active">Categorías de agregados</li>
        </ol>
    </div>
</div>

<div class="container-padding">
    <div class="row">
        <div class="col-xs-12 m-b-20">
            <div class="panel panel-success lobidisable ca-panel">
                <div class="panel-heading">
                    <div class="panel-title">
                        <i class="fa fa-th-large"></i>
                        <h4>Categorías de agregados</h4>
                    </div>
                    <div class="ca-acciones">
                        <button id="btnNuevaCategoriaAg" type="button" class="btn btn-success"><i class="fa fa-plus"></i> Nueva categoría</button>
                    </div>
                </div>
                <div class="ca-aviso" id="avisoCategoriasAg" style="display:none"></div>
                <p class="ca-ayuda"><i class="fa fa-info-circle"></i>
                    Crea las categorías y con <b>Productos</b> pones varios productos de una vez en una categoría (busca, por ejemplo, <b>pollo</b> y marca todos).
                    La categoría vale para ese producto en todos los productos donde es agregado. El <b>orden</b> es el orden de los cuadros en la Caja.
                    Cuántos se pueden elegir de cada categoría se pone en <b>Configuraciones &rsaquo; Agregados en la Caja</b>.
                </p>
                <div class="table-responsive ca-tabla">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:70px">Orden</th>
                                <th>Categoría</th>
                                <th class="text-center">Productos</th>
                                <th class="text-center" title="En cuántos productos se ofrecen como agregado">Se ofrecen en</th>
                                <th class="text-center">Activa</th>
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoCategoriasAg">
                            <tr><td colspan="6" class="ca-vacio"><i class="fa fa-spinner fa-spin"></i> Cargando…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Crear / editar categoría -->
<div class="modal fade ca-modal" id="modalCategoriaAg" tabindex="-1" role="dialog" aria-labelledby="tituloCategoriaAg">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="frmCategoriaAg" autocomplete="off">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="tituloCategoriaAg">Nueva categoría</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="caId" value="0">
                    <fieldset>
                        <div class="col-sm-8">
                            <label class="ca-etq" for="caNombre">Nombre</label>
                            <input type="text" class="form-control" id="caNombre" maxlength="60" placeholder="Ej: Proteína" required>
                        </div>
                        <div class="col-sm-4">
                            <label class="ca-etq" for="caOrden">Orden</label>
                            <input type="number" class="form-control" id="caOrden" min="0" max="9999" placeholder="Al final">
                        </div>
                        <div class="col-sm-12">
                            <div class="op-switches">
                                <label class="op-switch">
                                    <input id="caVigente" type="checkbox" checked>
                                    <span class="op-switch-pista" aria-hidden="true"></span>
                                    <span class="op-switch-texto">
                                        <b><i class="fa fa-check-circle"></i> Activa</b>
                                        <small>Sale como cuadro en la Caja. Apagada: sus productos salen en "Otros agregados".</small>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </fieldset>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> Cancelar</button>
                    <button type="submit" class="btn btn-success" id="btnGuardarCategoriaAg"><i class="fa fa-save"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Poner varios agregados de una vez en una categoría -->
<div class="modal fade ca-modal" id="modalAsignarAg" tabindex="-1" role="dialog" aria-labelledby="tituloAsignarAg">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="tituloAsignarAg">Productos de la categoría</h4>
            </div>
            <div class="modal-body">
                <p class="ca-ayuda" style="padding:0 0 10px">Marca los productos que van en esta categoría y presiona <b>Guardar</b>.
                    Los que ya están en otra categoría se cambian a esta. Desmarca uno para dejarlo sin categoría.</p>
                <div class="ca-filtros">
                    <div class="ca-buscar"><i class="fa fa-search"></i><input type="text" class="form-control" id="caBuscar" placeholder="Buscar producto (ej. pollo)"></div>
                    <select class="form-control" id="caFiltroProducto"><option value="1">Los que son agregados</option><option value="0">Todos los productos</option></select>
                    <select class="form-control" id="caFiltroEstado">
                        <option value="todos">Todos</option>
                        <option value="sin">Sin categoría</option>
                        <option value="esta">En esta categoría</option>
                    </select>
                </div>
                <div class="ca-filtros">
                    <button type="button" class="btn btn-default btn-sm" id="caMarcarVisibles"><i class="fa fa-check-square-o"></i> Marcar los que se ven</button>
                    <button type="button" class="btn btn-default btn-sm" id="caDesmarcarVisibles"><i class="fa fa-square-o"></i> Desmarcar los que se ven</button>
                </div>
                <div class="ca-lista" id="caLista"></div>
                <div class="ca-pie"><span id="caContador"></span><span id="caCambios"></span></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> Cancelar</button>
                <button type="button" class="btn btn-success" id="btnGuardarAsignarAg"><i class="fa fa-save"></i> Guardar</button>
            </div>
        </div>
    </div>
</div>
