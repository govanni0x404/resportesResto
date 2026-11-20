<style>
    /* Mantenedores: listado con acción en la cabecera, tabla limpia y formulario del modal ordenado (solo estilos) */
    .t-lista-contenedor { padding: 0 !important; }
    .t-lista > .panel-heading { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
    .t-lista > .panel-heading .panel-title { display: flex; align-items: center; margin: 0; }
    .t-lista > .panel-heading .t-acciones { margin: 0; }
    .t-lista .t-acciones .btn i { color: inherit; margin-right: 4px; }
    .t-lista-ayuda { margin: 0; padding: 12px 18px 0; font-size: 13px; color: var(--t-texto-tenue); }
    .t-lista-ayuda i { margin-right: 4px; }
    .t-lista-tabla { padding: 12px 18px 18px; margin: 0; border: 0; }
    .t-lista-tabla table.dataTable tbody tr { cursor: pointer; }
    @media (max-width: 480px) {
        .t-lista > .panel-heading .t-acciones, .t-lista > .panel-heading .t-acciones .btn { width: 100%; }
        .t-lista-tabla { padding: 10px; }
    }

    .t-form-modal .h-panel > ul.nav { display: none; }
    .t-form-modal fieldset { margin: 0 -8px; }
    .t-form-modal fieldset::after { content: ""; display: table; clear: both; }
    .t-form-modal fieldset > [class*="col-"] { padding: 0 8px; margin-bottom: 14px; }
    .t-form-modal fieldset label { font-size: 12px; color: var(--t-texto-suave); text-transform: uppercase; letter-spacing: .3px; margin-bottom: 5px; }
    .t-form-modal .btn-group { display: block; }
    .t-form-modal .checkbox { display: flex; align-items: center; min-height: 40px; margin: 0; padding: 8px 12px 8px 34px;
        border: 1px solid var(--t-borde); border-radius: var(--t-radio-sm); background: var(--t-fondo-alt); }
    .t-form-modal .checkbox br { display: none; }
    .t-form-modal .checkbox label { margin: 0; font-size: 13px; font-weight: 600; color: var(--t-texto); text-transform: none; letter-spacing: 0; }
    .t-form-modal .t-card-titulo { padding-top: 12px; border-top: 1px solid var(--t-borde-suave); }
    .t-form-modal .t-card-titulo:first-child { padding-top: 0; border-top: 0; }
    .t-form-modal .modal-footer .btn i { margin-right: 4px; }

    /* v1.21.0: cambiar varias mesas a otro tipo */
    .t-lista > .panel-heading .t-acciones { display: flex; flex-wrap: wrap; gap: 8px; }
    .tv-herramientas { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 8px; margin-bottom: 10px; }
    .tv-herramientas .form-control { width: 90px; }
    .tv-herramientas label { display: block; margin-bottom: 3px; }
    .tv-lista { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 6px; max-height: 320px; overflow-y: auto;
        padding: 6px; border: 1px solid var(--t-borde); border-radius: var(--t-radio-sm); background: var(--t-fondo-alt); }
    .tv-mesa { display: flex; align-items: flex-start; gap: 6px; margin: 0; padding: 6px 8px; border-radius: var(--t-radio-sm);
        background: var(--t-fondo, #fff); border: 1px solid var(--t-borde-suave); cursor: pointer; font-weight: 600; text-transform: none !important;
        letter-spacing: 0 !important; color: var(--t-texto) !important; font-size: 13px !important; }
    .tv-mesa input { margin: 3px 0 0; }
    .tv-mesa small { display: block; font-weight: 400; color: var(--t-texto-tenue); }
    .tv-contador { font-size: 13px; color: var(--t-texto-suave); margin: 8px 0 0; }
    /* v1.21.2: "Nuevo" junto al título del select, igual que en la carta de productos */
    .t-form-modal label sup a { cursor: pointer; text-transform: none; font-weight: 600; margin-left: 4px; }
    /* Herramientas para marcar mesas: una tarjeta como las del sistema */
    .tv-herramientas { padding: 10px 12px; border: 1px solid var(--t-borde); border-radius: var(--t-radio-sm); background: var(--t-tinte, #F7FBF8); }
    .tv-herramientas label { font-size: 11px !important; }
    .tv-herramientas .btn i, #ModalTipoVarias .modal-title i { margin-right: 4px; }
    .tv-mesa:hover { border-color: var(--t-primario-borde); }
    .tv-mesa.marcada { border-color: var(--t-primario); background: var(--t-primario-suave); }
    .tv-mesa input { accent-color: var(--t-primario); }
    /* v1.23.1: mesa de venta directa (Configuraciones > Otros del restaurante) */
    #tablaMesas tbody tr.mesa-vd > td { background: #FFF7E6 !important; }
    #tablaMesas tbody tr.mesa-vd > td:first-child { box-shadow: inset 4px 0 0 #D97706; }
    .mesa-vd-etiqueta { display: inline-block; margin-left: 6px; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 700;
        letter-spacing: .3px; white-space: nowrap; background: #FDE7C2; color: #92400E; border: 1px solid #F5C77E; vertical-align: middle; }
    .mesa-vd-etiqueta i { margin-right: 3px; }
    .tv-mesa.vd { border-style: dashed; border-color: #D97706; }
    @media (max-width: 480px) { .tv-lista { grid-template-columns: repeat(2, 1fr); } }
</style>
<div class="content-header">
    <div class="header-icon">
        <i class="pe-7s-coffee"></i>
    </div>
    <div class="header-title">
        <h1>Mesas</h1>
        <small id="caja"></small>
        <ol class="breadcrumb">
            <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i> Mantencion</a></li>
            <li class="active">Mesas</li>
        </ol>
    </div>
</div>

<div class="container-padding">
    <input type="number" class="form-control" style="display: none;" name="id_rol" id="id_rol" value="0" min="0" oninput="validity.valid||(value='');">

    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12 m-b-20">
            <div class="panel-body t-lista-contenedor">

                <div class="panel panel-success lobidisable t-lista">
                    <div class="panel-heading">
                        <div class="panel-title">
                            <i class="pe-7s-coffee"></i>
                            <h4>Listado De Mesas</h4>
                        </div>
                        <div class="t-acciones">
                            <button onclick="AbrirModalNuevaMesa()" type="button" class="btn btn-primary btn-crear-nuevo"><i class="fa fa-plus"></i> Crear Nueva Mesa</button>
                            <!-- v1.21.0: cambiar el tipo de muchas mesas de una vez (no una por una) -->
                            <button onclick="AbrirModalTipoVarias()" type="button" class="btn btn-info" id="btnTipoVarias" style="display:none"><i class="fa fa-exchange"></i> Cambiar varias mesas a otro tipo</button>
                        </div>
                    </div>
                    <p class="t-lista-ayuda"><i class="fa fa-hand-pointer-o"></i> Haga clic en una fila para ver o editar el registro.</p>
                    <div class="table-responsive t-lista-tabla">
                        <table id="tablaMesas" class="display responsive" style="width: 100%">
                            <thead>
                                <tr>
                                    <th class="text text-center">ID</th>
                                    <th class="text text-center">NOMBRE EN LA CAJA</th>
                                    <th class="text text-center">TIPO DE MESA</th>
                                    <th class="text text-center">DISPONIBLE</th>
                                    <th class="text text-center">VIGENTE</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>