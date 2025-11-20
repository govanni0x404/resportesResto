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
        <h1>Resumen De Anulaciones/Eliminaciones</h1>
        <small id="caja"></small>
        <ol class="breadcrumb">
            <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i>Reportes</a></li>
            <li class="active">Resumen De Anulaciones/Eliminaciones</li>
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
            <br>
            <div class="col-md-2">
                 <div class="checkbox checkbox-success checkbox-inline">
                     <input checked type="checkbox" id="ckTodos" value="option1">
                     <label for="ckTodos"> Todas </label>
                 </div>
                 <br>
                 <div class="radio radio-info radio-inline">
                    <input disabled name="radio" type="radio" id="opAnulaciones" value="1" name="radioInline" checked>
                    <label for="opAnulaciones"> Anulaciones </label>
                </div>
                <br>
                <div class="radio radio-info radio-inline">
                    <input disabled name="radio" type="radio" id="opEliminaciones" value="2" name="radioInline">
                    <label for="opEliminaciones"> Eliminaciones </label>
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
                        <h4>Detalle Anulaciones</h4>
                    </div>
                </div>
                <div class="table-responsive" style="margin-top:10px; padding:5px;">
                    <table id="tableDetalleAnulaciones" class="display responsive" style="width: 100%">
                        <thead>
                            <tr>
                                <th class="text text-left">N° Pedido</th>
                                <th class="text text-center">Producto</th>
                                <th class="text text-center">Cantidad</th>
                                <th class="text text-center">Total</th>
                                <th class="text text-center">Fecha/Hora</th>
                                <th class="text text-center">Anulado</th>
                                <th class="text text-center">Eliminado</th>
                                <th class="text text-center">Motivo</th>
                                <th class="text text-center">Usuario</th>

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