<div class="content-header">
    <div class="header-icon">
        <i class="pe-7s-lock"></i>
    </div>
    <div class="header-title">
        <h1>Privilegios por Rol</h1>
        <small id="caja"></small>
        <ol class="breadcrumb">
            <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i> Configuración</a></li>
            <li class="active">Privilegios por Rol</li>
        </ol>
    </div>
</div>

<div class="container-padding">
    <input type="hidden" name="id_rol_selected" id="id_rol_selected" value="0">

    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12 m-b-20">
            <div class="panel-body">

                <div class="panel panel-success lobidisable">
                    <div class="panel-heading">
                        <div class="panel-title">
                            <h4>Gestión de Privilegios por Rol</h4>
                        </div>
                    </div>
                    <br>
                    
                    <div class="col-md-6" style="margin-left: 15px; margin-bottom: 20px;">
                        <label for="selectRol"><strong>SELECCIONE UN ROL *</strong></label>
                        <select class="form-control input-lg" name="selectRol" id="selectRol" style="font-size: 16px; height: 45px;">
                            <option value="0">-- Seleccione un Rol --</option>
                        </select>
                    </div>
                    
                    <div class="clearfix"></div>
                    
                    <div id="divPrivilegios" style="display: none; padding: 15px;">
                        <div class="panel panel-success">
                            <div class="panel-heading panel-success" >
                                <h4 class="panel-title">
                                    <i class="fa fa-list"></i> Módulos del Sistema
                                </h4>
                            </div>
                            <div class="panel-body">
                                <div style="margin-bottom: 15px;">
                                    <button type='button' class='btn btn-primary' id='btnHabilitarTodos'><i class='fa fa-check-circle'></i> HABILITAR TODOS</button>
                                    <button type='button' class='btn btn-default' id='btnBloquearTodos'><i class='fa fa-times-circle'></i> BLOQUEAR TODOS</button>
                                </div>
                                
                                <div class="table-responsive" style="max-height:600px; overflow:auto;">
                                    <table id="tablePrivilegios" class="table table-hover table-bordered">
                                        <thead style="background-color: #568c30;">
                                            <tr>
                                                <th class='text-center' style='display:none'>ID PUSUARIO</th>
                                                <th class='text-center' style='display:none'>ID PSISTEMA</th>
                                                <th class='text-center' style="padding: 12px; width: 25%;">SISTEMA</th>
                                                <th class='text-center' style="padding: 12px; width: 60%;">MÓDULO</th>
                                                <th class='text-center' style="width: 15%;">ACCESO</th>
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
    </div>
</div>