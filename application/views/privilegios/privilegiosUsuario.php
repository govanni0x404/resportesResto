<div class="content-header">
    <div class="header-icon">
        <i class="pe-7s-lock"></i>
    </div>
    <div class="header-title">
        <h1>Privilegios de Usuario</h1>
        <small id="caja"></small>
        <ol class="breadcrumb">
            <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i> Configuración</a></li>
            <li class="active">Privilegios de Usuario</li>
        </ol>
    </div>
</div>

<div class="container-padding">
    <input type="hidden" name="id_rol_selected" id="id_rol_selected" value="0">

    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12 m-b-20">
            <div class="panel-body">

                <div class="panel panel-info lobidisable">
                    <div class="panel-heading">
                        <div class="panel-title">
                            <i class="pe-7s-users"></i>
                            <h4>Seleccione un Usuario</h4>
                        </div>
                    </div>
                    <br>
                    <div class="table-responsive" style="margin-top:10px; padding:5px;">
                        <table id="tablaUsuariosPrivilegios" class="display responsive" style="width: 100%">
                            <thead>
                                <tr>
                                    <th class="text text-center">ID</th>
                                    <th class="text text-center">NOMBRE USUARIO</th>
                                    <th class="text text-center">ROL</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL PRIVILEGIOS -->
<div id="modalPrivilegiosUsuario" class="modal fade" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModal" class="modal-header btn-info">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 id="nombreUsuario" class="modal-title text text-center">PRIVILEGIOS DEL USUARIO</h4>
            </div>

            <div class="modal-body">
                <div class="container-widget">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel panel-default">
                                <br>
                                <div class="write" style="width:100%">
                                    <div class="col-md-12" style="max-height:650px; height:auto; width:100%; overflow:auto;">
                                        <table id="tableDetallePrivilegios" class="table table-hover table-bordered">
                                            <thead>
                                                <tr>
                                                    <th class='text-center' style='display:none'>ID</th>
                                                    <th class='text-center' style="padding: 12px;">MÓDULO</th>
                                                    <th class='text-center' style="padding: 12px;">DESCRIPCIÓN</th>
                                                    <th class='text-center'>ACCESO</th>
                                                </tr>
                                            </thead>
                                            <tbody id="detallePrivilegios">
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BOTONES -->
            <div class="modal-footer">
                <button type='button' class='btn btn-success text-center' id='btnHabilitarTodos'><i class='fa fa-check-circle'></i> HABILITAR TODOS</button>
                <button type='button' class='btn btn-danger text-center' id='btnBloquearTodos'><i class='fa fa-times-circle'></i> BLOQUEAR TODOS</button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times-circle"></i> SALIR</button>
            </div>
        </div>
    </div>
</div>