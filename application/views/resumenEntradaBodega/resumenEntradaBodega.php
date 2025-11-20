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
        <h1>Entradas De Bodega</h1>
        <small id="caja"></small>
        <ol class="breadcrumb">
            <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i>Reportes</a></li>
            <li class="active">Movimientos De Bodega</li>
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
                <label>Bodega:
                    <div class="checkbox checkbox-success checkbox-inline">
                        <input type="checkbox" id="ckTodasLasBodegas" value="option1">
                        <label for="ckTodasLasBodegas"> Todos </label>
                    </div>
                </label>
                <select class="form-control" id="cboBodegas" nombre="cboBodegas">
                    <option selected value="">
                        <!--Todos Los Usuarios-->
                    </option>
                </select>
            </div>
            <div class="col-md-2">
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
                        <h4>Resumen De Entradas</h4>
                    </div>
                </div>
                <div class="table-responsive" style="margin-top:10px; padding:5px;">
                    <table id="tableResumenEntradasBodega" class="display responsive" style="width: 100%">
                        <thead>
                            <tr>
                                <th class="text text-center">N° Movimiento</th>
                                <th class="text text-center">Bodega</th>
                                <th class="text text-center">Fecha</th>
                                <th class="text text-center">Usuario</th>
                                <th class="text text-center">OBSERVACION</th>
                                <th class="text text-center">Detalles</th>
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
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>


            <div id="modalVerDetalle" class="modal fade" role="dialog" style="overflow-y: scroll;">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <!-- TITULO  MODAL  -->
                        <div id="tituloModal" class="modal-header btn-success">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                    aria-hidden="true">&times;</span></button>
                            <h4 id="tituloDetalle" class="modal-title text text-center">Detalle Entrada Bodega</h4>
                            <input type="number" class="form-control" style="display: none;" name="id_del_vale"
                                id="id_del_vale" value="0" min="0" oninput="validity.valid||(value='');">
                        </div>

                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="panel panel-default">
                                        <div class="panel-title">

                                        </div>
                                        <div class="panel-body table-responsive">
                                            <div class="col-md-11"></div>
                                            <table id="tablaModalVerDetalleEntradaBodega"
                                                class="table table-hover display" style="width:100%">
                                                <thead>
                                                    <tr>
                                                        <th style="width:20%" class="text-center">PRODUCTO</th>
                                                        <th style="width:20%" class="text-center">U. MEDIDA</th>
                                                        <th style="width:20%" class="text-center">CANTIDAD INGRESADA
                                                        </th>
                                                        <th style="width:20%" class="text-center">STOCK ANTERIOR</th>
                                                        <th style="width:10%" class="text-center">NUEVO STOCK</th>
                                                    </tr>
                                                </thead>
                                                <tfoot>
                                                    <tr>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                    </tr>
                                                </tfoot>
                                                <tbody id="VerDetalle">
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <!-- FIN CONTENIDO MODAL -->

                            <!-- BOTONES -->
                            <div class="modal-footer">
                                <button type="button" class="btn btn-danger" data-dismiss="modal"><i
                                        class="fa fa-times-circle-o"></i> SALIR</button>
                            </div>
                            <!-- FIN BOTONES -->
                        </div>
                    </div>
                </div>