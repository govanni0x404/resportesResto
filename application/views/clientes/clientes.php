<div class="content-header">
    <div class="header-icon">
        <i class="pe-7s-home"></i>
    </div>
    <div class="header-title">
        <h1>Clientes</h1>
        <small id="caja"></small>
        <ol class="breadcrumb">
            <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i>Mantencion</a></li>
            <li class="active">Clientes</li>
        </ol>
    </div>
</div>


<div class="container-padding">
    <input type="number" class="form-control" style="display: none;" name="id_rol" id="id_rol" value="0" min="0" oninput="validity.valid||(value='');">

    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12 m-b-20">
            <div class="panel-body">

                <div class="panel panel-success lobidisable">
                    <div class="panel-heading">
                        <div class="panel-title">
                            <i class="pe-7s-cash"></i>
                            <h4>Listado De Clientes</h4>
                        </div>
                    </div>
                    <br>
                    <div class="col-xs-12 text-end" style="display: flex; justify-content: flex-end; width: 100%;">
                    <button onclick="AbrirModalNuevoCliente()" type="button" style="margin-right: 20px; margin-bottom:20px;" class="btn btn-primary"></i>Crear Nuevo Cliente</button>
                    </div>
                    <div class="table-responsive" style="margin-top:10px; padding:5px;">
                        <table id="tablaClientes" class="display responsive" style="width: 100%">
                            <thead>
                                <tr>
                                    <th class="text text-center">N° CLIENTE</th>
                                    <th class="text text-center">RUT</th>
                                    <th class="text text-center">DV</th>
                                    <th class="text text-center">RUT</th>
                                    <th class="text text-left">NOMBRE</th>
                                    <th class="text text-left">GIRO</th>
                                    <th class="text text-left">DIRECCION</th>
                                    <th class="text text-left">COMUNA</th>
                                    <th class="text text-center">TELEFONO</th>
                                    <th class="text text-center">LISTA DE PRECIOS</th>
                                    <th class="text text-center">MAIL</th>
                                    <th class="text text-center">CREDITO</th>
                                    <th class="text text-center">REFERNCIA DIRECCION</th>

                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>