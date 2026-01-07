<?php
date_default_timezone_set('America/Santiago');
$date = new DateTime('now');
$date->modify('last day of this month');
$fechaActual = date('d-m-Y');
?>
<div class="content-header">
    <div class="header-icon">
        <i class="pe-7s-home"></i>
    </div>
    <div class="header-title">
        <h1>Detalle De Operaciones</h1>
        <small id="caja"></small>
        <ol class="breadcrumb">
            <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i>Reportes</a></li>
            <li class="active">Detalle De Operaciones</li>
        </ol>
    </div>
</div> <!-- /.Content Header (Page header) -->

<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-12 m-b-20">
        <!-- Nav tabs -->
        <ul class="nav nav-tabs">
            <div class="col-md-3">
                <br>
                <div class="control-group">
                    <div class="controls">
                        <label> Seleccione la fecha </label>
                        <div class="input-prepend input-group">
                            <span class="add-on input-group-addon"><i class="fa fa-calendar"></i></span>
                            <input type="text" id="date-range-picker" class="form-control"
                                value="<?php echo $fechaActual; ?> - <?php echo $fechaActual; ?>" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <br>
                <label>Forma De Pago:

                    <div class="checkbox checkbox-success checkbox-inline">
                        <input type="checkbox" id="ckTodasLasFpago" value="option1">
                        <label for="ckTodasLasFpago"> Todas </label>
                    </div>

                </label>
                <select class="form-control" id="cboFpago" nombre="cboFpago">
                    <option selected value="">
                        <!--Todas Las Formas De Pago-->
                    </option>
                </select>
            </div>
            <div class="col-md-3">
                <br>
                <label>Usuario:
                    <div class="checkbox checkbox-success checkbox-inline">
                        <input type="checkbox" id="ckTodosLosUsuarios" value="option1">
                        <label for="ckTodosLosUsuarios"> Todos </label>
                    </div>
                </label>
                <select class="form-control" id="cboUsuarios" nombre="cboUsuarios">
                    <option selected value="">
                        <!--Todos Los Usuarios-->
                    </option>
                </select>
            </div>
            <div class="col-md-3">
                <br>
                <br>
                <button id="btnFiltrar" style="width:5px;" class="btn btn-success btn-rounded w-md m-b-1"><i
                        class="hvr-buzz-out fa fa-search"></i></button>
            </div>
        </ul>
        <br>
        <div class="panel-body">
            <div class="row">
            </div>
            <!---------------------------------------------TABLAS RESUMEN STOCK POR BODEGA ------------------------------------------------------------>
            <div class="panel panel-success lobidisable">
                <div class="panel-heading">
                    <div class="panel-title">
                        <i class="pe-7s-cash"></i>
                        <h4>Detalle Operaciones</h4>
                    </div>
                </div>
                <div class="table-responsive" style="margin-top:10px; padding:5px;">
                    <table id="tableDetalleOperaciones" class="display responsive" style="width: 100%">
                        <thead>
                            <tr>
                                <!-- <th class="text text-center">PDF Compra</th> -->
                                <th class="text text-left">ID Venta</th>
                                <th class="text text-center">Usuario / Mesa</th>
                                <th class="text text-center">Comensales</th>
                                <th class="text text-center">Digitado</th>
                                <th class="text text-center">Observacion</th>
                                <th class="text text-center">Forma Pago</th>
                                <th class="text text-center">Total</th>
                                <th class="text text-center">Detalle</th>

                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!---------------------------------------------FIN TAB DETALLE ATENCION DE GARZONES  ------------------------------------------------------------>
</div>
</div>
</div>


<div id="modalVerDetalleDeOperaciones" class="modal fade" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModal" class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 id="tituloDetalle" class="modal-title text text-center">Detalle De Operaciones</h4>
                <input type="number" class="form-control" style="display: none;" name="id_del_vale" id="id_del_vale"
                    value="0" min="0" oninput="validity.valid||(value='');">
            </div>

            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-title">

                            </div>
                            <fieldset>
                                <div class="col-md-4 ">
                                    <label for="idTipoDocumento">DOCUMENTO</label>
                                    <input type="text" disabled class="form-control input-sm input-sm"
                                        name="idTipoDocumento" id="idTipoDocumento" requerido="no" value="">
                                </div>
                                <div class="col-md-4 ">
                                    <label for="folioDte">FOLIO</label>
                                    <input type="number" disabled class="form-control input-sm input-sm" name="folioDte"
                                        id="folioDte" requerido="no" value="">
                                </div>
                                <div class="col-md-4 ">
                                    <label for="idFormaDePago">FORMA DE PAGO
                                        <sup onclick="AbrirModalCambioFpago()"><a>CAMBIAR</a></sup>
                                    </label>
                                    <input type="text" disabled class="form-control input-sm input-sm"
                                        name="idFormaDePago" id="idFormaDePago" requerido="no" value="">
                                </div>
                            </fieldset>

                            <br>
                            <div class="panel-body table-responsive">
                                <div class="col-md-11"></div>
                                <table id="tablaModalVerDetalleDeOperaciones" class="table table-hover display"
                                    style="width:100%">
                                    <thead>
                                        <tr>
                                            <th style="width:20%" class="text-center">PRODUCTO</th>
                                            <th style="width:20%" class="text-center">CANTIDAD</th>
                                            <th style="width:20%" class="text-center">PRECIO UNITARIO</th>
                                            <!--<th style="width:10%" class="text-center">TOTAL NETO</th>
                                            <th class="text-center">TOTAL IVA</th>-->
                                            <th class="text-center">TOTAL </th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th></th>
                                            <th></th>
                                            <th></th>
                                            <!--<th></th>
                                            <th></th>-->
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                    <tbody id="VerDetalleDeOperaciones">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- FIN CONTENIDO MODAL -->

                <!-- BOTONES -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><i
                            class=""></i> SALIR</button>
                </div>
                <!-- FIN BOTONES -->
            </div>
        </div>
    </div>

    <div id="ModalCambioFormaDePago" class="modal fade" role="dialog" style="overflow-y: scroll;">
        <div class="modal-dialog">
            <div class="modal-content">
                <!-- TITULO  MODAL  -->
                <div id="tituloModal" class="modal-header btn-success">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title text text-center">CAMBIO FORMA DE PAGO VENTA
                    </h4>
                </div>
                <!-- FIN TITULO  MODAL  -->

                <!-- CONTENIDO  MODAL  -->
                <div class="modal-body">
                    <div class="h-panel">
                        <ul id="myTab" class="nav nav-with title font">
                            <li class="active"><a href="#tabDatosUsuarios" data-toggle="tab" id="tab1"
                                    class="text color1"></a></li>
                        </ul>
                        <div class="tab-pane fade active in" id="tabDatosUsuarios">
                            <fieldset>
                                <div class="col-md-8 ">
                                    <label for="Nventa">N° VENTA</label>
                                    <input type="text" disabled class="form-control input-sm input-sm" name="Nventa"
                                        id="Nventa" requerido="no" value="">
                                </div>
                                <div class="col-md-12 ">
                                    <label for="FormaDePagoActual">FORMA DE PAGO ACTUAL</label>
                                    <input disabled type="text" class="form-control input-sm input-sm" maxlength='28'
                                        onkeypress="return sololetrasyNumeros(event)" onpaste="return false;"
                                        ondrop="return false;" autocomplete="off" name="FormaDePagoActual"
                                        id="FormaDePagoActual" requerido="si" value="" />
                                </div>
                            </fieldset>
                            <fieldset>
                                <div class="col-md-12">
                                    <label for="cboNuevaFormaDePago">NUEVA FORMA DE PAGO</label>
                                    <select id="cboNuevaFormaDePago" nombre="cboNuevaFormaDePago"
                                        class="form-control input-sm" style="width:100%" requerido="si"></select>
                                </div>
                            </fieldset>
                            <!-- FIN CAMPOS DE REGISTRO  -->
                        </div>
                    </div>
                </div>
                <!-- CONTENIDO  MODAL  -->

                <!-- BOTONES  -->
                <div class="modal-footer">
                    <button onclick="actualizarFormaDePagoVenta()" type="button"
                                        class="btn btn-primary"><i class=""></i>ACTUALIZAR FORMA DE
                                        PAGO</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><i
                            class=""></i>SALIR</button>
                </div>
                <!-- FIN BOTONES  -->
            </div>
        </div>
    </div>