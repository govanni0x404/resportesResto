<?php
date_default_timezone_set('America/Santiago');
$date = new DateTime('now');
$date->modify('last day of this month');
$fechaActual = date('Y-m-d');
?>
<div class="container compras-page">
    <div class="content-header">
    <div class="header-icon">
        <i class="pe-7s-home"></i>
    </div>
    <div class="header-title">
        <h1>Compras</h1>
        <small id="caja"></small>
        <ol class="breadcrumb">
            <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i></a></li>
            <li class="active">Ingresos De Compras</li>
        </ol>
    </div>
</div>


<div class="container-padding">
    <input type="number" class="form-control" style="display: none;" name="id_rol" id="id_rol" value="0" min="0"
        oninput="validity.valid||(value='');">

    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12 m-b-20">
            <div class="panel panel-bd">
                <div class="panel-heading">
                    <div class="panel-title">
                        <h4><i class="fa fa-file-text-o"></i> Datos de la Compra</h4>
                    </div>
                </div>
                <div class="panel-body">
                    <fieldset>
                        <div class="col-md-3">
                            <label for="cboProveedorCompra">Proveedor</label>
                            <sup><a onclick="AbrirModalNuevoProveedor()">Nuevo</a></sup>
                            <select id="cboProveedorCompra" nombre="cboProveedorCompra" class="form-control input-sm"
                            style="width:100%" requerido="si"></select>
                        </div>

                        <div class="col-md-3">
                            <label for="cboDocumentoCompra">Documento Compra</label>
                            <select id="cboDocumentoCompra" nombre="cboDocumentoCompra" class="form-control input-sm"
                                style="width:100%" requerido="si"></select>
                        </div>

                        <div class="col-md-3">
                            <label for="FolioDocumento">Folio Documento</label>
                            <input min="0" type="number" class="form-control input-sm input-sm" maxlength='28'
                                onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;"
                                autocomplete="off" name="FolioDocumento" id="FolioDocumento" requerido="si" value=""
                                placeholder="Folio Documento" />
                        </div>

                        <div class="col-md-3">
                            <label for="FechaCompra">Fecha Compra</label>
                            <input type="text" id="FechaCompra" class="form-control input-sm"
                                value="<?php echo date('d-m-Y'); ?>" />
                        </div>
                    </fieldset>
                </div>
            </div>

            <div class="panel panel-bd">
                <div class="panel-heading">
                    <div class="panel-title">
                        <h4><i class="fa fa-plus-square-o"></i> Detalle de Productos</h4>
                    </div>
                </div>
                <div class="panel-body">
                    <fieldset>
                        <div class="col-md-4">
                            <label for="cboProductosCompra">Producto</label>
                            <!-- <sup><a onclick="AbrirModalNuevoInsumo()">Nuevo</a></sup> -->
                            <select id="cboProductosCompra" nombre="cboProductosCompra"
                                class="form-control input-sm" style="width:100%" requerido="si"></select>
                        </div>

                        <div class="col-md-2">
                            <label for="CantidadComprada">Cantidad</label>
                            <div class="input-group">
                                <input min="0" type="number" class="form-control input-sm" maxlength='28'
                                    onpaste="return false;" ondrop="return false;" autocomplete="off" 
                                    name="CantidadComprada" id="CantidadComprada" requerido="si" value="" />
                                <span class="input-group-addon" id="spanUnidadCantidad"></span>
                            </div>
                        </div>
                        
                        <div class="col-md-2">
                            <label for="BrutoUnitario">Bruto Unitario</label>
                            <input min="0" type="number" class="form-control input-sm input-sm" maxlength='28'
                                onpaste="return false;" ondrop="return false;" autocomplete="off" 
                                name="BrutoUnitario" id="BrutoUnitario" requerido="si" value="" />
                        </div>

                        <div class="col-md-2">
                            <label for="TotalBruto">Total Bruto</label>
                            <input min="0" type="number" class="form-control input-sm input-sm" maxlength='28'
                                onpaste="return false;" ondrop="return false;" autocomplete="off" 
                                name="TotalBruto" id="TotalBruto" requerido="si" value="" />
                        </div>

                        <div class="col-md-2">
                            <label style="visibility: hidden;">Acción</label>
                            <button id="btnAgregarALista" type="button"
                                class="btn btn-primary btn-block"><i class=""></i> Agregar</button>
                        </div>
                    </fieldset>
                </div>
            </div>

            <div class="panel panel-bd">
                <div class="panel-heading">
                    <div class="panel-title">
                        <h4><i class="fa fa-shopping-cart"></i> Lista de Productos</h4>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table id="tableCompra" class="table table-hover table-bordered">
                            <thead class="btn-success">
                                <tr>
                                    <th style='display:none;' scope="col">id producto</th>
                                    <th style="width:40%" class="text-center" scope="col">Producto</th>
                                    <th style="width:15%" class="text-center" scope="col">Cantidad</th>
                                    <th style="width:15%" class="text-center" scope="col">Bruto/Un</th>
                                    <th style="width:15%" class="text-center" scope="col">Total Bruto</th>
                                    <th style="width:10%" scope="col" class="text-center">Quitar</th>
                                </tr>
                            </thead>
                            <tbody id="detalleCompra">
                            </tbody>
                        </table>
                    </div>

                    <div class="compras-total-row">
                        <div class="compras-total-row__spacer"></div>
                        <div class="compras-total-row__label">
                            <label class="control-label">Total Compra:</label>
                        </div>
                        <div class="compras-total-row__input">
                            <div class="input-group">
                                <span class="input-group-addon">$</span>
                                <input type="number" disabled
                                    class="text-right form-control input-sm"
                                    name="txtTotalCompra" id="txtTotalCompra"
                                    requerido="no" value="0">
                            </div>
                        </div>
                    </div>

                    <div class="t-acciones compras-acciones">
                        <button id="GrabaIngresoDeCompra"
                            type="button"
                            class="btn btn-primary btn-lg">
                            <i class="fa fa-check"></i> INGRESAR COMPRA
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<style>
    /* Filas de campos flexibles: inputs alineados abajo aunque las etiquetas tengan distinto largo */
    .compras-page .panel-body > fieldset { display: flex; flex-wrap: wrap; align-items: flex-end; margin: 0 -15px; min-width: 0; }
    .compras-page .panel-body > fieldset > [class*="col-"] { float: none; margin-bottom: 10px; }
    @media (max-width: 991px) { .compras-page .panel-body > fieldset > [class*="col-md-"] { width: 100%; } }
    .compras-page .panel-body label { font-size: 12px; text-transform: uppercase; letter-spacing: .3px; color: var(--t-texto-suave); font-weight: 700; margin-bottom: 5px; }
    .compras-page .panel-body sup a { cursor: pointer; font-weight: 600; text-transform: none; margin-left: 4px; }
    .compras-page .form-control.input-sm { height: 38px; font-size: 14px; }
    .compras-page #spanUnidadCantidad { min-width: 44px; font-size: 13px; font-weight: 600; }
    .compras-page #spanUnidadCantidad:empty { display: none; }
    .compras-page .input-group:has(#spanUnidadCantidad:empty) .form-control { border-radius: var(--t-radio-sm); }
    .compras-page #btnAgregarALista { height: 38px; }
    .compras-page .panel-heading h4 i { color: var(--t-primario); margin-right: 4px; }
    .compras-page #tableCompra { margin-bottom: 0; }
    .compras-page #tableCompra > thead > tr > th { background: var(--t-fondo-alt) !important; color: var(--t-texto) !important; border-color: var(--t-borde-suave) !important; }

    /* Total de la compra como indicador destacado */
    .compras-page .compras-total-row { margin: 16px 0 0 auto; max-width: 360px; background: var(--t-primario-suave); border: 1px solid var(--t-primario-borde);
        border-radius: var(--t-radio); padding: 10px 14px; }
    .compras-page .compras-total-row__label .control-label { margin: 0; font-size: 12px; text-transform: uppercase; letter-spacing: .3px; color: var(--t-primario); }
    .compras-page .compras-total-row__input { flex: 1 1 auto; }
    .compras-page .compras-total-row .input-group-addon { background: transparent; border: 0; font-size: 20px; font-weight: 800; color: var(--t-primario); padding-right: 4px; }
    .compras-page #txtTotalCompra { background: transparent; border: 0; box-shadow: none; font-size: 24px; font-weight: 800; color: var(--t-primario); height: 40px; padding-left: 0; }
    .compras-page .compras-acciones { margin: 16px 0 0; }
    @media (max-width: 767px) {
        .compras-page .compras-total-row { max-width: none; }
        .compras-page .compras-acciones .btn { width: 100%; }
    }

    .compras-total-row {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        align-items: center;
    }

    .compras-total-row__spacer {
        display: none;
    }

    .compras-total-row__label {
        margin-right: 10px;
    }

    .compras-total-row__label,
    .compras-total-row__input {
        width: auto;
    }

    @media (max-width: 991px) {
        .compras-page .panel-body fieldset > [class*="col-"] {
            margin-bottom: 12px;
        }
    }

    @media (max-width: 767px) {
        .compras-total-row {
            justify-content: space-between;
        }

        .compras-total-row__input {
            flex: 1 1 auto;
            min-width: 140px;
        }
    }
</style>

<?php $this->load->view('proveedor/jsNuevoProveedor.php'); ?>
<?php $this->load->view('insumos/js.php'); ?>
<?php $this->load->view('insumos/modalInsumos.php'); ?>