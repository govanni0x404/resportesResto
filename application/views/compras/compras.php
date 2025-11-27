<?php
date_default_timezone_set('America/Santiago');
$date = new DateTime('now');
$date->modify('last day of this month');
$fechaActual = date('Y-m-d');
?>
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
                        <h4>Datos de la Compra</h4>
                    </div>
                </div>
                <div class="panel-body">
                    <fieldset>
                        <div class="col-md-3">
                            <label for="cboProveedorCompra">Proveedor</label>
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
                                value="<?php echo $fechaActual; ?>" />
                        </div>
                    </fieldset>
                </div>
            </div>

            <div class="panel panel-bd">
                <div class="panel-heading">
                    <div class="panel-title">
                        <h4>Detalle de Productos</h4>
                    </div>
                </div>
                <div class="panel-body">
                    <fieldset>
                        <div class="col-md-4">
                            <label for="cboProductosCompra">Producto</label>
                            <select id="cboProductosCompra" nombre="cboProductosCompra"
                                class="form-control input-sm" style="width:100%" requerido="si"></select>
                        </div>

                        <div class="col-md-2">
                            <label for="CantidadComprada">Cantidad</label>
                            <div class="input-group">
                                <input min="0" type="number" class="form-control input-sm" maxlength='28'
                                    onpaste="return false;" ondrop="return false;" autocomplete="off" 
                                    name="CantidadComprada" id="CantidadComprada" requerido="si" value="" />
                                <span class="input-group-addon" id="spanUnidadCantidad" 
                                    style="padding: 6px 12px; font-size: 14px; font-weight: 400; line-height: 1; color: #555; text-align: center; background-color: #eee; border: 1px solid #ccc; border-radius: 4px; border-left: none;"></span>
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
                                class="btn btn-primary btn-block"><i class="fa fa-plus"></i> Agregar</button>
                        </div>
                    </fieldset>
                </div>
            </div>

            <div class="panel panel-bd">
                <div class="panel-heading">
                    <div class="panel-title">
                        <h4>Lista de Productos</h4>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table id="tableCompra" class="table table-hover table-bordered">
                            <thead class="btn-success">
                                <tr>
                                    <th style='display:none;' scope="col">id producto</th>
                                    <th style="width:40%" scope="col">Producto</th>
                                    <th style="width:15%" scope="col">Cantidad</th>
                                    <th style="width:15%" scope="col">Bruto/Un</th>
                                    <th style="width:15%" scope="col">Total Bruto</th>
                                    <th style="width:10%" scope="col" class="text-center">Quitar</th>
                                </tr>
                            </thead>
                            <tbody id="detalleCompra">
                            </tbody>
                        </table>
                    </div>

                    <div class="row" style="margin-top: 20px;">
                        <div class="col-md-8">
                        </div>
                        <div class="col-md-2">
                            <label class="control-label">Total Compra:</label>
                        </div>
                        <div class="col-md-2">
                            <div class="input-group">
                                <span class="input-group-addon">$</span>
                                <input type="number" disabled
                                    class="text-right form-control input-sm"
                                    name="txtTotalCompra" id="txtTotalCompra"
                                    requerido="no" value="0">
                            </div>
                        </div>
                    </div>

                    <div class="row" style="margin-top: 30px;">
                        <div class="col-md-12 text-right">
                            <button id="GrabaIngresoDeCompra"
                                type="button"
                                class="btn btn-primary btn-lg" 
                                style="padding: 15px 40px; font-size: 16px;">
                                <i class="fa fa-save"></i> INGRESAR COMPRA
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>