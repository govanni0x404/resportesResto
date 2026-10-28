<style>
    /* Privilegios por rol: selector destacado, acciones masivas y permisos como interruptores (solo estilos) */
    .t-priv .t-lista-contenedor { padding: 0 !important; }
    .t-priv-selector { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px 20px; }
    .t-priv-selector .t-priv-campo { flex: 1 1 320px; max-width: 480px; }
    .t-priv-selector label { font-size: 12px; color: var(--t-texto-suave); text-transform: uppercase; letter-spacing: .3px; margin-bottom: 6px; }
    .t-priv-selector select.form-control { height: 44px !important; font-size: 15px !important; }
    .t-priv-selector .t-muted { flex: 1 1 240px; margin: 0 0 10px; font-size: 13px; }
    .t-priv-modulos { padding: 0 18px 18px; }
    .t-priv-modulos > .panel { margin-bottom: 0; box-shadow: none; }
    .t-priv-modulos .t-acciones { justify-content: flex-start; margin-bottom: 14px; }
    .t-priv-modulos .t-acciones .btn i { margin-right: 4px; }
    .t-priv-tabla { max-height: 600px; overflow: auto; border: 1px solid var(--t-borde-suave); border-radius: var(--t-radio-sm); }
    .t-priv-tabla .table { margin: 0; border: 0; }
    .t-priv-tabla thead th { position: sticky; top: 0; z-index: 1; text-transform: uppercase; font-size: 12px !important; letter-spacing: .3px; color: var(--t-texto-suave) !important; }
    .t-priv-tabla tbody td { padding: 10px 12px !important; }
    /* Casilla de permiso con aspecto de interruptor */
    .t-priv input.privilegio-check {
        -webkit-appearance: none; appearance: none; position: relative; display: inline-block; vertical-align: middle; margin: 0;
        width: 42px !important; height: 24px !important; border-radius: 12px; background: #DCCDBD; border: 0; outline: none;
        transition: background-color .15s; cursor: pointer;
    }
    .t-priv input.privilegio-check::after {
        content: ""; position: absolute; top: 3px; left: 3px; width: 18px; height: 18px; border-radius: 50%; background: #fff;
        box-shadow: 0 1px 2px rgba(0, 0, 0, .2); transition: left .15s;
    }
    .t-priv input.privilegio-check:checked { background: var(--t-primario); }
    .t-priv input.privilegio-check:checked::after { left: 21px; }
    .t-priv input.privilegio-check:focus-visible { box-shadow: 0 0 0 3px rgba(180, 67, 46, .25); }
    @media (max-width: 480px) {
        .t-priv-modulos { padding: 0 10px 10px; }
        .t-priv-modulos .t-acciones .btn { width: 100%; }
    }
</style>
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

<div class="container-padding t-priv">
    <input type="hidden" name="id_rol_selected" id="id_rol_selected" value="0">

    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12 m-b-20">
            <div class="panel-body t-lista-contenedor">

                <div class="panel panel-success lobidisable">
                    <div class="panel-heading">
                        <div class="panel-title">
                            <i class="pe-7s-lock"></i>
                            <h4>Gestión de Privilegios por Rol</h4>
                        </div>
                    </div>

                    <div class="panel-body">
                        <div class="t-priv-selector">
                            <div class="t-priv-campo">
                                <label for="selectRol">Seleccione un rol *</label>
                                <select class="form-control input-lg" name="selectRol" id="selectRol" style="font-size: 16px; height: 45px;">
                                    <option value="0">-- Seleccione un Rol --</option>
                                </select>
                            </div>
                            <p class="t-muted"><i class="fa fa-info-circle"></i> Los cambios se guardan al instante al activar o desactivar cada módulo.</p>
                        </div>
                    </div>

                    <div class="clearfix"></div>

                    <div id="divPrivilegios" class="t-priv-modulos" style="display: none;">
                        <div class="panel panel-success">
                            <div class="panel-heading panel-success">
                                <h4 class="panel-title">
                                    <i class="fa fa-list"></i> Módulos del Sistema
                                </h4>
                            </div>
                            <div class="panel-body">
                                <div class="t-acciones">
                                    <button type='button' class='btn btn-primary' id='btnHabilitarTodos'><i class='fa fa-check-square-o'></i> Habilitar todos los privilegios</button>
                                    <button type='button' class='btn btn-default' id='btnBloquearTodos'><i class='fa fa-square-o'></i> Quitar todos los privilegios</button>
                                </div>

                                <div class="table-responsive t-priv-tabla">
                                    <table id="tablePrivilegios" class="table table-hover table-bordered">
                                        <thead>
                                            <tr>
                                                <th class='text-center' style='display:none;'>ID PUSUARIO</th>
                                                <th class='text-center' style='display:none;'>ID PSISTEMA</th>
                                                <th class='text-center' style="width: 15%;">CÓDIGO</th>
                                                <th class='text-left' style="width: 70%;">MÓDULO</th>
                                                <th class='text-center' style="width: 15%;">AUTORIZADO</th>
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
