<div class="content-header">
    <div class="header-icon">
        <i class="pe-7s-users"></i>
    </div>
    <div class="header-title">
        <h1>Usuarios</h1>
        <small id="caja"></small>
        <ol class="breadcrumb">
            <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i> Mantencion</a></li>
            <li class="active">Usuarios</li>
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
                            <i class="pe-7s-users"></i>
                            <h4>Listado De Usuarios</h4>
                        </div>
                    </div>
                    <br>
                    <div class="col-xs-12 text-end" style="display: flex; justify-content: flex-end; width: 100%;">
                    <button onclick="AbrirModalNuevoUsuario()" type="button" style="margin-right: 20px; margin-bottom:20px;" class="btn btn-primary"><i class=""></i> Crear Nuevo Usuario</button>
                    </div>
                    <div class="table-responsive" style="margin-top:10px; padding:5px;">
                        <table id="tablaUsuarios" class="display responsive" style="width: 100%">
                            <thead>
                                <tr>
                                    <th class="text text-center">ID</th>
                                    <th class="text text-left">NOMBRE USUARIO</th>
                                    <th class="text text-left">ROL</th>
                                    <th class="text text-center">VIGENTE</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>