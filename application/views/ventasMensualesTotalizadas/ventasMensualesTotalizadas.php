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
        <h1>Detalle De Ventas Totalizadas</h1>
        <small id="caja"></small>
        <ol class="breadcrumb">
            <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i>Reportes</a></li>
            <li class="active">Detalle De Ventas Totalizadas</li>
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
                        <h4>Detalle De Ventas Totalizadas</h4>
                    </div>
                </div>
                <div class="table-responsive" style="margin-top:10px; padding:5px;">
                    <table id="tableDetalleDeVentasTotalizadas" class="display responsive" style="width: 100%">
                        <thead>
                            <tr>
                                <th class="text text-center">Dia</th>
                                <th class="text text-center">Cantidad Ventas</th>
                                <!--<th class="text text-center">Iva</th>
                                <th class="text text-center">Neto</th>-->
                                <th class="text text-center">Total</th>
                                <!--<th class="text text-center">Ver Ventas</th>-->

                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th></th>
                                <th></th>
                                <!--<th></th>
                                <th></th>-->
                                <th></th>
                                <!--<th></th>-->
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="panel-body" id="grafico">
                <div class="col-md-2"></div>
                <div class="col-md-8">
                    <canvas id="chartVentas" style="height: auto;"></canvas>
                </div>
                <div class="col-md-2"></div>
            </div>
        </div>
    </div>

    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
        <div class="panel panel-success lobidisable">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="pe-7s-file"></i>
                    <h4>Top 10 Dias Con Mayor Venta</h4>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="text text-center">Dia</th>
                            <th class="text text-center">Cantidad Ventas</th>
                            <!--<th class="text text-center">Iva</th>
                            <th class="text text-center">Neto</th>-->
                            <th class="text text-center">Total</th>
                        </tr>
                    </thead>
                    <tbody id="resumenTopDiasConMayorVenta">

                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
        <div class="panel panel-success lobidisable">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="pe-7s-file"></i>
                    <h4>Top 10 Dias Con Menor Venta</h4>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="text text-center">Dia</th>
                            <th class="text text-center">Cantidad Ventas</th>
                            <!--<th class="text text-center">Iva</th>
                            <th class="text text-center">Neto</th>-->
                            <th class="text text-center">Total</th>
                        </tr>
                    </thead>
                    <tbody id="resumenTopDiasConMenorVenta">

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
</div>


<div id="modalVerDetalleVentasDelDia" class="modal fade" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModal" class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 id="tituloDetalle" class="modal-title text text-center">VENTAS DEL DIA</h4>
                <input type="number" class="form-control" style="display: none;" name="id_del_vale" id="id_del_vale"
                    value="0" min="0" oninput="validity.valid||(value='');">
            </div>

            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-title">
                            </div>
                            <div class="panel-body table-responsive">
                                <div class="col-md-11"></div>
                                <table id="tablaModalVerDetalleDeVentas" class="table table-hover display"
                                    style="width:100%">
                                    <thead>
                                        <tr>
                                            <th style="width:20%" class="text-center">N° VENTA</th>
                                            <th style="width:20%" class="text-center">FECHA</th>
                                            <th style="width:20%" class="text-center">CLIENTE</th>
                                            <th style="width:10%" class="text-center">DOCUMENTO</th>
                                            <th class="text-center">FOLIO</th>
                                            <th class="text-center">TOTAL </th>
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
                                    <tbody id="VerDetalleVentasDelDia">
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