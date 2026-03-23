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
        <h1>Configuraciones</h1>
        <small id="caja"></small>
        <ol class="breadcrumb">
            <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i></a></li>
            <li class="active">Panel De Confguracion</li>
        </ol>
    </div>
</div> <!-- /.Content Header (Page header) -->


<div class="content-header">

    <button type="button" class="btn btn-success" id="btnGrabarConfiguraciones"><i class="fa fa-check-square-o"></i>GRABAR CONFIGURACIONES</button>

</div> <!-- /.Content Header (Page header) -->





<div class="container-padding">
    <!-- Start Row -->
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-body">
                    <div class="h-panel">
                        <!--Tabs para los formularios-->
                        <ul id="myTab" class="nav nav-tabs">
                            <li class="active"><a href="#tabConfiguracionReportes" data-toggle="tab" id="tab1">Informacion Reportes</a></li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane fade active in" id="tabConfiguracionReportes">
                                <div class="tab-pane fade active in" id="tabConfiguracion">
                                    <fieldset>
                                        <div class="col-md-12">
                                            <div class="btn-group" role="group" aria-label="...">
                                                <div class="checkbox checkbox-success">
                                                    <br>
                                                    <input id="ckDesglozarTodosLosMontosEnReportes" type="checkbox">
                                                    <label for="ckDesglozarTodosLosMontosEnReportes">
                                                        Desglozar Monto Neto, Monto Iva Y Monto Total En Reportes
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </fieldset>
                                    <fieldset>
                                        <div class="col-md-12">
                                            <div class="btn-group" role="group" aria-label="...">
                                                <div class="checkbox checkbox-success">
                                                    <br>
                                                    <input id="ckDesglozarSoloMontoTotalEnReportes" type="checkbox">
                                                    <label for="ckDesglozarSoloMontoTotalEnReportes">
                                                        Desglozar Monto Total En Reportes
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </fieldset>
                                </div><!-- fin tab cliente-->
                            </div>
                        </div>
                        </fieldset>
                    </div><!-- fin tab cliente-->
                </div><!-- end tabs content -->
            </div><!-- end tabs-->
        </div>
    </div>
</div>
</div>
</div>