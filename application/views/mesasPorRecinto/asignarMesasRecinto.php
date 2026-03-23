<div class="content-header">
    <div class="header-icon">
        <i class="pe-7s-map-marker"></i>
    </div>
    <div class="header-title">
        <h1>Mesas por Recinto</h1>
        <small id="caja"></small>
        <ol class="breadcrumb">
            <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i> Mantencion</a></li>
            <li class="active">Asignar Mesas a Recinto</li>
        </ol>
    </div>
</div>

<div class="container-padding">
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12 m-b-20">
            <div class="panel-body">

                <div class="panel panel-success lobidisable">
                    <div class="panel-heading">
                        <div class="panel-title">
                            <i class="pe-7s-map-marker"></i>
                            <h4>Mesas por Recinto</h4>
                        </div>
                    </div>
                    <br>
                    <div class="table-responsive" style="margin-top:10px; padding:5px;">
                        <table id="tablaMesasSinAsignar" class="display responsive" style="width: 100%">
                            <thead>
                                <tr>
                                    <th class="text text-center" style='height:10px'>ID MESA</th>
                                    <th class="text text-center" style='height:45px'>DISPONIBLE</th>
                                    <th class="text text-center" style='height:45px'>RECINTO ACTUAL</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>