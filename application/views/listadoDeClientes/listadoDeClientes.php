<?php
date_default_timezone_set('America/Santiago');
$date = new DateTime('now');
$date->modify('last day of this month');
$fechaActual = date('d-m-Y');
?>
<style>
    /* Mantenedores: listado con acción en la cabecera, tabla limpia y formulario del modal ordenado (solo estilos) */
    .t-lista-contenedor { padding: 0 !important; }
    .t-lista > .panel-heading { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
    .t-lista > .panel-heading .panel-title { display: flex; align-items: center; margin: 0; }
    .t-lista > .panel-heading .t-acciones { margin: 0; }
    .t-lista .t-acciones .btn i { color: inherit; margin-right: 4px; }
    .t-lista-ayuda { margin: 0; padding: 12px 18px 0; font-size: 13px; color: var(--t-texto-tenue); }
    .t-lista-ayuda i { margin-right: 4px; }
    .t-lista-tabla { padding: 12px 18px 18px; margin: 0; border: 0; }
    .t-lista-tabla table.dataTable tbody tr { cursor: pointer; }
    @media (max-width: 480px) {
        .t-lista > .panel-heading .t-acciones, .t-lista > .panel-heading .t-acciones .btn { width: 100%; }
        .t-lista-tabla { padding: 10px; }
    }

</style>
<link href="<?php echo base_url() ?>assets/dist/css/reportes.css" rel="stylesheet" type="text/css" />
<div class="content-header rep-encabezado">
    <div class="header-icon">
        <i class="pe-7s-id"></i>
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
        <div class="panel-body t-lista-contenedor">
            <!---------------------------------------------TABLAS RESUMEN STOCK POR BODEGA ------------------------------------------------------------>
            <div class="panel panel-success lobidisable t-lista">
                <div class="panel-heading">
                    <div class="panel-title">
                        <i class="pe-7s-id"></i>
                        <h4>Listado De Clientes</h4>
                    </div>
                </div>
                <div class="table-responsive t-lista-tabla">
                    <table id="tableListadoDeClientes" class="display responsive" style="width: 100%">
                        <thead>
                            <tr>
                                <th class="text text-left">N° CLIENTE</th>
                                <th class="text text-center">RUT</th>
                                <th class="text text-center">DV</th>
                                <th class="text text-center">RUT</th>
                                <th class="text text-left">NOMBRE</th>
                                <th class="text text-left">GIRO</th>
                                <th class="text text-left">DIRECCION</th>
                                <th class="text text-left">COMUNA</th>
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