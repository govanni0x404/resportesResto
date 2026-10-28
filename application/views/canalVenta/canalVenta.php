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
</style>
<div class="content-header">
    <div class="header-icon">
        <i class="pe-7s-share"></i>
    </div>
    <div class="header-title">
        <h1>Canal De Venta</h1>
        <small id="caja"></small>
        <ol class="breadcrumb">
            <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i>Mantencion</a></li>
            <li class="active">Canal De Venta</li>
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
                            <i class="pe-7s-share"></i>
                            <h4>Listado De Canal De Ventas</h4>
                        </div>
                        <div class="t-acciones">
                            <button onclick="AbrirModalNuevoCentroCosto()" type="button" class="btn btn-primary"><i class="fa fa-plus"></i> Crear Nuevo Canal De Venta</button>
                        </div>
                    </div>
                    <p class="t-lista-ayuda"><i class="fa fa-hand-pointer-o"></i> Haga clic en una fila para ver o editar el registro.</p>
                    <div class="table-responsive t-lista-tabla">
                        <table id="tablaCanalVenta" class="display responsive" style="width: 100%">
                            <thead>
                                <tr>
                                    <th class="text text-center">N° CANAL DE VENTA</th>
                                    <th class="text text-left">CANAL DE VENTA</th>
                                    <th class="text text-center">VIGENTE</th>
                                    <th class="text text-center">CORTESIA</th>
                                    <th class="text text-center">LISTA DE PRECIOS</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>