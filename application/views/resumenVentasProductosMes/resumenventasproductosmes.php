<?php
date_default_timezone_set('America/Santiago');
$fechaActual = date('d-m-Y');
?>
<div class="content-header">
    <div class="header-icon">
        <i class="pe-7s-graph2"></i>
    </div>
    <div class="header-title">
        <h1>Resumen De Ventas Por Producto y Mes</h1>
        <small id="caja"></small>
        <ol class="breadcrumb">
            <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i>Reportes</a></li>
            <li class="active">Resumen Ventas Productos Por Mes</li>
        </ol>
    </div>
</div>

<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-12 m-b-20">
        <ul class="nav nav-tabs">
            <div class="col-md-7"></div>
            <div class="col-md-3">
                <br>
                <div class="control-group">
                    <div class="controls">
                        <label>Seleccione el período</label>
                        <div class="input-prepend input-group">
                            <span class="add-on input-group-addon"><i class="fa fa-calendar"></i></span>
                            <input type="text" id="date-range-picker" class="form-control"
                                value="<?php echo $fechaActual; ?> - <?php echo $fechaActual; ?>" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <br><br>
                <button id="btnFiltrar" class="btn btn-success btn-rounded w-md m-b-1">
                    <i class="hvr-buzz-out fa fa-search"></i>
                </button>
            </div>
        </ul>
        <br>
        
        <div class="panel-body">
            <!-- TABLA PRINCIPAL -->
            <div class="panel panel-success lobidisable">
                <div class="panel-heading">
                    <div class="panel-title">
                        <i class="pe-7s-graph2"></i>
                        <h4>Resumen De Ventas Por Producto y Mes</h4>
                    </div>
                </div>
                <div class="table-responsive" style="margin-top:10px; padding:5px;">
                    <table id="tableResumenVentasProductos" class="display responsive" style="width: 100%">
                        <thead>
                            <tr>
                                <th class="hidden">ID</th>
                                <th class="text text-left">Producto</th>
                                <th class="text text-center">Familia</th>
                                <th class="text text-center">Mes</th>
                                <th class="text text-center">Cantidad</th>
                                <th class="text text-center">Total</th>
                                <!-- <th class="text text-center">Acciones</th> -->
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
                                <!-- <th></th> -->
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- MODAL DETALLE -->
            <div class="modal fade modal-info" id="modalDetalleProducto" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            <h1 class="modal-title">Detalle de Ventas del Producto</h1>
                        </div>
                        <div class="modal-body">
                            <div class="panel panel-info lobidisable">
                                <div class="panel-heading">
                                    <div class="panel-title">
                                        <i class="pe-7s-note2"></i>
                                        <h4>Ventas Detalladas</h4>
                                    </div>
                                </div>
                                <div class="table-responsive" style="margin-top:10px; padding:5px;">
                                    <table id="tableDetalleProducto" class="display responsive" style="width: 100%">
                                        <thead>
                                            <tr>
                                                <th class="text text-center">N° Venta</th>
                                                <th class="text text-center">Fecha</th>
                                                <th class="text text-left">Cliente</th>
                                                <th class="text text-center">Cantidad</th>
                                                <th class="text text-center">Precio Unit.</th>
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
                                                <th></th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default btn-lg" data-dismiss="modal">
                                <i class="fa fa-times"></i> Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>