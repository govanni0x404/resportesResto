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
    .t-priv-grupo { margin-bottom: 16px; border: 1px solid var(--t-borde-suave); border-radius: var(--t-radio-sm); }
    .t-priv-grupo h5 { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin: 0; padding: 10px 14px;
        background: var(--t-fondo-alt); border-bottom: 1px solid var(--t-borde-suave); font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; }
    .t-priv-grupo h5 small { font-weight: 400; text-transform: none; letter-spacing: 0; color: var(--t-texto-suave); }
    .t-priv-lista { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); }
    .t-priv-lista label { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin: 0; padding: 10px 14px;
        border-bottom: 1px solid var(--t-borde-suave); font-weight: 600; cursor: pointer; }
    .t-priv-aviso { margin: 0 18px 18px; }
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
    <div class="row">
        <div class="col-xs-12 m-b-20">
            <div class="panel-body t-lista-contenedor">
                <div class="panel panel-success lobidisable">
                    <div class="panel-heading">
                        <div class="panel-title">
                            <i class="pe-7s-lock"></i>
                            <h4>Qué puede ver cada rol en la web</h4>
                        </div>
                    </div>

                    <?php if (!$hayTabla) : ?>
                        <div class="alert alert-warning t-priv-aviso" style="margin-top: 18px;">
                            <b>Falta un paso:</b> todavía no existe la tabla de permisos en esta base de datos.
                            Corre el script <code>sql/permiso_web.sql</code> y vuelve a abrir esta pantalla.
                            Mientras tanto se usan los permisos antiguos.
                        </div>
                    <?php else : ?>
                        <div class="panel-body">
                            <div class="t-priv-selector">
                                <div class="t-priv-campo">
                                    <label for="selectRol">Rol</label>
                                    <select class="form-control" id="selectRol">
                                        <option value="0">-- Seleccione un rol --</option>
                                    </select>
                                </div>
                                <p class="t-muted"><i class="fa fa-info-circle"></i> Gerencia siempre ve todo. Los cambios se guardan al instante;
                                    el usuario los ve al cambiar de pantalla.</p>
                            </div>
                        </div>

                        <div id="divPrivilegios" class="t-priv-modulos" style="display: none;">
                            <div class="t-acciones">
                                <button type="button" class="btn btn-primary" id="btnHabilitarTodos"><i class="fa fa-check-square-o"></i> Habilitar todo</button>
                                <button type="button" class="btn btn-default" id="btnBloquearTodos"><i class="fa fa-square-o"></i> Quitar todo</button>
                            </div>
                            <div id="gruposPrivilegios"></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
