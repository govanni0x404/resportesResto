<?php
$hayFolioAuto = in_array('folio_automatico', $columnas, true);
$hayFormato = in_array('formato_dte', $columnas, true);
?>
<style>
    /* Igual que las demás mantenciones: listado con botón en la cabecera y formulario en un modal */
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

    .t-form-modal fieldset { margin: 0 -8px; }
    .t-form-modal fieldset::after { content: ""; display: table; clear: both; }
    .t-form-modal fieldset > [class*="col-"] { padding: 0 8px; margin-bottom: 14px; }
    .t-form-modal fieldset label { font-size: 12px; color: var(--t-texto-suave); text-transform: uppercase; letter-spacing: .3px; margin-bottom: 5px; }
    .t-form-modal .checkbox { display: flex; align-items: center; min-height: 40px; margin: 0 0 8px; padding: 8px 12px 8px 34px;
        border: 1px solid var(--t-borde); border-radius: var(--t-radio-sm); background: var(--t-fondo-alt); }
    .t-form-modal .checkbox label { margin: 0; font-size: 13px; font-weight: 600; color: var(--t-texto); text-transform: none; letter-spacing: 0; }
    .t-form-modal .t-card-titulo { padding-top: 12px; border-top: 1px solid var(--t-borde-suave); font-size: 12px; font-weight: 700;
        color: var(--t-texto-suave); text-transform: uppercase; letter-spacing: .3px; margin: 0 0 8px; }
    .t-form-modal .modal-footer .btn i { margin-right: 4px; }
    .td-codigo { display: flex; gap: 6px; }
    .td-nota { display: block; font-size: 11px; color: var(--t-texto-tenue); margin: -4px 0 8px 2px; }
    .td-req { color: #c0392b; }
</style>
<div class="content-header">
    <div class="header-icon">
        <i class="pe-7s-file"></i>
    </div>
    <div class="header-title">
        <h1>Documentos Tributarios</h1>
        <small id="caja"></small>
        <ol class="breadcrumb">
            <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i>Mantencion</a></li>
            <li class="active">Documentos Tributarios</li>
        </ol>
    </div>
</div>

<div class="container-padding">
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12 m-b-20">
            <div class="panel-body t-lista-contenedor">
                <div class="panel panel-success lobidisable t-lista">
                    <div class="panel-heading">
                        <div class="panel-title">
                            <i class="pe-7s-file"></i>
                            <h4>Listado De Documentos Tributarios</h4>
                        </div>
                        <?php if ($esAdmin) : ?>
                            <div class="t-acciones">
                                <button type="button" class="btn btn-primary btn-crear-nuevo" id="tdNuevo"><i class="fa fa-plus"></i> Crear Nuevo Documento</button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <p class="t-lista-ayuda"><i class="fa fa-hand-pointer-o"></i>
                        <?php echo $esAdmin ? 'Haga clic en una fila para ver o editar el documento.' : 'Haga clic en una fila para ver el documento. Solo gerencia puede modificarlos.' ?></p>
                    <div class="table-responsive t-lista-tabla">
                        <table id="tablaDocumentos" class="display" style="width: 100%">
                            <thead>
                                <tr>
                                    <th class="text-center">ID</th>
                                    <th class="text-left">NOMBRE DEL DOCUMENTO</th>
                                    <th class="text-center">CÓD. SII</th>
                                    <th class="text-center">DTE</th>
                                    <th class="text-center">VENTA</th>
                                    <th class="text-center">COMPRA</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="modalDocumento" class="modal fade t-form-modal" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text-center" id="tdTitulo">REGISTRO DOCUMENTO TRIBUTARIO</h4>
            </div>
            <div class="modal-body">
                <fieldset>
                    <div class="col-xs-4 col-sm-3">
                        <label for="tdId">ID</label>
                        <input type="text" class="form-control input-sm" id="tdId" disabled placeholder="Nuevo">
                    </div>
                    <div class="col-xs-8 col-sm-9">
                        <label for="tdNombre">Nombre del documento <span class="td-req">*</span></label>
                        <input type="text" class="form-control input-sm" id="tdNombre" maxlength="50" autocomplete="off" placeholder="Ej: BOLETA ELECTRONICA">
                    </div>
                </fieldset>
                <fieldset>
                    <div class="col-xs-12 col-sm-6">
                        <label for="tdCodSii">Cód. SII <span class="td-req">*</span></label>
                        <div class="td-codigo">
                            <input type="number" class="form-control input-sm" id="tdCodSii" min="0" max="999" step="1" inputmode="numeric" placeholder="0">
                            <button type="button" class="btn btn-success btn-sm" id="tdAyudaSii" title="Ver los códigos SII"><i class="fa fa-question"></i></button>
                        </div>
                    </div>
                    <?php if ($hayFormato) : ?>
                        <div class="col-xs-12 col-sm-6">
                            <label for="tdFormato">Formato DTE <span class="td-req">*</span></label>
                            <select class="form-control input-sm" id="tdFormato">
                                <option value="80">80 mm</option>
                                <option value="58">58 mm</option>
                                <option value="CARTA">Carta</option>
                            </select>
                        </div>
                    <?php endif; ?>
                </fieldset>
                <fieldset>
                    <div class="col-xs-12">
                        <div class="checkbox checkbox-success">
                            <input id="tdElectronico" type="checkbox" disabled>
                            <label for="tdElectronico">Facturación electrónica (DTE)</label>
                        </div>
                        <small class="td-nota">Se marca sola según el código SII (33, 34, 39, 41, 46, 52, 56 y 61 son electrónicos).</small>
                    </div>
                    <div class="col-xs-12 col-sm-6">
                        <div class="checkbox checkbox-success">
                            <input id="tdRequiereCliente" type="checkbox">
                            <label for="tdRequiereCliente">Requerir cliente</label>
                        </div>
                    </div>
                    <?php if ($hayFolioAuto) : ?>
                        <div class="col-xs-12 col-sm-6">
                            <div class="checkbox checkbox-success">
                                <input id="tdFolioAuto" type="checkbox">
                                <label for="tdFolioAuto">Folio automático</label>
                            </div>
                        </div>
                    <?php endif; ?>
                </fieldset>
                <fieldset>
                    <div class="col-xs-12 col-sm-6">
                        <p class="t-card-titulo">Categoría venta</p>
                        <div class="checkbox checkbox-success">
                            <input id="tdCatBoleta" type="checkbox">
                            <label for="tdCatBoleta">Boletas</label>
                        </div>
                        <div class="checkbox checkbox-success">
                            <input id="tdCatFactura" type="checkbox">
                            <label for="tdCatFactura">Facturas</label>
                        </div>
                    </div>
                    <div class="col-xs-12 col-sm-6">
                        <p class="t-card-titulo">Compra / Venta</p>
                        <div class="checkbox checkbox-success">
                            <input id="tdVenta" type="checkbox">
                            <label for="tdVenta">Venta</label>
                        </div>
                        <div class="checkbox checkbox-success">
                            <input id="tdCompra" type="checkbox">
                            <label for="tdCompra">Compra</label>
                        </div>
                    </div>
                </fieldset>
                <small class="td-req">* Obligatorio</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> Cerrar</button>
                <?php if ($esAdmin) : ?>
                    <button type="button" class="btn btn-success" id="tdGrabar"><i class="fa fa-check"></i> Grabar</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
