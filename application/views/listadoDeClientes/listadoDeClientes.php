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
        <h1>Clientes</h1>
        <small id="caja"></small>
        <ol class="breadcrumb">
            <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i>Reportes</a></li>
            <li class="active">Listado De Clientes</li>
        </ol>
    </div>
</div> <!-- /.Content Header (Page header) -->

<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-12 m-b-20">
        <div class="panel-body">
            <div class="row">
            </div>
            <!---------------------------------------------TABLAS RESUMEN STOCK POR BODEGA ------------------------------------------------------------>
            <div class="panel panel-success lobidisable">
                <div class="panel-heading">
                    <div class="panel-title">
                        <i class="pe-7s-cash"></i>
                        <h4>CLIENTES</h4>
                    </div>
                </div>
                <div class="table-responsive" style="margin-top:10px; padding:5px;">
                    <table id="tableListadoDeClientes" class="display responsive" style="width: 100%">
                        <thead>
                            <tr>
                                <th class="text text-left">N° CLIENTE</th>
                                <th class="text text-center">RUT</th>
                                <th class="text text-center">DV</th>
                                <th class="text text-center">RUT</th>
                                <th class="text text-center">NOMBRE</th>
                                <th class="text text-center">GIRO</th>
                                <th class="text text-center">DIRECCION</th>
                                <th class="text text-center">COMUNA</th>
                                <th class="text text-center">TELEFONO</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!---------------------------------------------FIN TAB DETALLE ATENCION DE GARZONES  ------------------------------------------------------------>
</div>
</div>
</div>