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
        <h1>Resumen De Compras</h1>
        <small id="caja"></small>
        <ol class="breadcrumb">
            <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i>Reportes</a></li>
            <li class="active">Resumen De Compras</li>
        </ol>
    </div>
</div> <!-- /.Content Header (Page header) -->

<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-12 m-b-20">
        <!-- Nav tabs -->
        <ul class="nav nav-tabs">
            <div class="col-md-4">
                <!-- <br>
				<label>Bodegas:</label>
				<select class="form-control" id="cboBodegas" nombre="cboBodegas">
					<option selected value="">Todas Las Bodegas</option>
				</select> -->
            </div>
            <div class="col-md-3">
            </div>
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
                        <h4>Resumen De Compras</h4>
                    </div>
                </div>
                <div class="table-responsive" style="margin-top:10px; padding:5px;">
                    <table id="tableResumenDeCompras" class="display responsive" style="width: 100%">
                        <thead>
                            <tr>
                                <th class="hidden"></th>
                                <!-- <th class="text text-center">PDF Compra</th> -->
                                <th class="text text-left">Proveedor</th>
                                <th class="text text-center">Documento</th>
                                <th class="text text-center">Número De Documento</th>
                                <th class="text text-center">Fecha Compra</th>
                                <th class="text text-center">Total</th>
                                <th class="text text-center">Detalles</th>
                                <th class="text text-center"></th>
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

            <!---------------------------------------------TABLAS RESUMEN FACTURAS DE COMPRA ------------------------------------------------------------>
            <div class="modal fade modal-success" id="modalDetalleFacturas" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                    aria-hidden="true">&times;</span></button>
                            <h1 class="modal-title">Detalle Compra</h1>
                        </div>
                        <div class="modal-body">
                            <div class="panel panel-success lobidisable">
                                <div class="panel-heading">
                                    <div class="panel-title">
                                        <i class="pe-7s-news-paper"></i>
                                        <h4>Productos</h4>
                                    </div>
                                </div>
                                <div class="table-responsive" style="margin-top:10px; padding:5px;">
                                    <table id="tableDetalleFacturas" class="display responsive" style="width: 100%">
                                        <thead>
                                            <tr>
                                                <th class="text text-left">Insumo</th>
                                                <th class="text text-center">Cantidad</th>
                                                <th class="text text-center">Bruto Unitario</th>
                                                <th class="text text-center">Total</th>
                                                <th class="text text-center"></th>
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
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <!--	<button type="button" class="btn btn-danger" data-dismiss="modal"><i class="pe-7s-close-circle"></i> Close</button>-->
                            <button type="button" class=" btn btn-labeled btn-danger m-b-5" data-dismiss="modal"><span
                                    class="btn-label">
                                    <i class="pe-7s-close-circle"></i></span> Cerrar</button>
                        </div>
                    </div><!-- /.modal-content -->
                </div><!-- /.modal-dialog -->
            </div><!-- /.modal -->
        </div>
        <!---------------------------------------------FIN TAB CAJA ACTUAL ------------------------------------------------------------>
    </div>
    <!---------------------------------------------FIN TAB DETALLE ATENCION DE GARZONES  ------------------------------------------------------------>
</div>
</div>
</div>