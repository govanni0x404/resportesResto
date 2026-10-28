<?php
date_default_timezone_set('America/Santiago');
$date = new DateTime('now');
$date->modify('last day of this month');
$fechaActual = date('Y-m-d');
?>
<style>
    /* Perfil de usuario: dos tarjetas claras con formulario ordenado (solo estilos) */
    .t-perfil > .row > div > .panel > .panel-body { padding: 0 18px 18px; }
    .t-perfil .nav-tabs { margin: 0 -18px 18px; padding: 0 10px; }
    .t-perfil .tab-pane > br { display: none; }
    .t-perfil fieldset { margin: 0 -8px; }
    .t-perfil fieldset::after { content: ""; display: table; clear: both; }
    .t-perfil fieldset > [class*="col-"] { padding: 0 8px; margin-bottom: 14px; }
    .t-perfil .t-perfil-card { height: 100%; }
    .t-perfil .t-perfil-card .panel-body { padding: 16px 18px 6px; }
    .t-perfil .t-perfil-card .panel-body > br { display: none; }
    .t-perfil label { font-size: 12px; color: var(--t-texto-suave); text-transform: uppercase; letter-spacing: .3px; margin-bottom: 5px; }
    .t-perfil .btn-group { display: block; }
    .t-perfil .checkbox { display: flex; align-items: center; min-height: 38px; margin: 0; padding: 8px 12px 8px 34px;
        border: 1px solid var(--t-borde); border-radius: var(--t-radio-sm); background: var(--t-fondo-alt); }
    .t-perfil .checkbox br { display: none; }
    .t-perfil .checkbox label { margin: 0; font-size: 13px; color: var(--t-texto); text-transform: none; letter-spacing: 0; }
    /* Botón "Mostrar" alineado con el campo de la clave */
    .t-perfil .t-perfil-clave .checkbox { margin-top: 22px; }
    @media (max-width: 991px) { .t-perfil .t-perfil-clave .checkbox { margin-top: 0; } }
    @media (max-width: 480px) { .t-perfil #btnGrabarConfiguraciones { width: 100%; } }
</style>

<!--
<div class="content-header">
	<div class="header-icon">
		<i class="pe-7s-home"></i>
	</div>
	<div class="header-title">
		<h1 id="local"></h1>
	
		<h4 id="lblNombreCliente"></h4>
		<small id="caja"></small>
		<ol class="breadcrumb">
		
		</ol>
	</div>
</div> -->

<div class="content-header">
    <div class="header-icon">
        <i class="pe-7s-user"></i>
    </div>
    <div class="header-title">
        <h1>Perfil de usuario: <?php echo $this->session->userdata('nombre_usuario') ?></h1>
        <small id="caja"></small>
        <ol class="breadcrumb">
            <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i> Inicio</a></li>
            <li class="active">Mi perfil</li>
        </ol>
    </div>
</div>



<?php $fechaActual = date('d-m-Y') ?>
<div class="container-padding t-perfil">

    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-body">
                    <div class="h-panel">
                        <ul id="myTab" class="nav nav-tabs">
                            <li class="active"><a href="#tabDatosDelUsuario" data-toggle="tab" id="tab1"><i class="fa fa-user"></i> Información de usuario</a></li>

                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane fade active in" id="tabDatosDelUsuario">
                                <br>
                                <fieldset>
                                    <div class="col-md-6 col-lg-6">
                                        <div class="panel panel-default t-perfil-card">
                                            <div class="panel-heading"><div class="panel-title"><h4><i class="pe-7s-id"></i> Perfil</h4></div></div>
                                            <div class="panel-body">
                                                <fieldset>
                                                    <div class="col-md-12 text-success">
                                                        <label for="nombreDeUsuario">Nombre De Usuario</label>
                                                        <input type="text" class="form-control input-sm input-sm" maxlength='28' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="nombreDeUsuario" id="nombreDeUsuario" requerido="si" value="" placeholder="Nombre De Usuario" />
                                                    </div>
                                                </fieldset>
                                                <fieldset>
                                                    <div class="col-md-12 text-success">
                                                        <label for="CorreoDeAcceso">Correo De Acceso</label>
                                                        <input type="text" class="form-control input-sm input-sm" maxlength='28' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="CorreoDeAcceso" id="CorreoDeAcceso" requerido="si" value="" placeholder="CorreoDeAcceso" />
                                                    </div>
                                                </fieldset>
                                                <fieldset>
                                                    <div class="col-md-8 text-success">
                                                        <label for="ClaveDeUsuario">Clave De Acceso</label>
                                                        <input type="password" class="form-control input-sm input-sm" maxlength='28' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="ClaveDeUsuario" id="ClaveDeUsuario" requerido="si" value="" placeholder="Clave De Usuario" />
                                                    </div>
                                                    <div class="col-md-4 text-success t-perfil-clave">
                                                        <div class="btn-group" role="group" aria-label="...">
                                                            <div class="checkbox checkbox-success">
                                                                <br>
                                                                <input id="ckMostrarClaveUsuario" type="checkbox">
                                                                <label for="ckMostrarClaveUsuario">
                                                                    Mostrar
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </fieldset>
                                                <br>
                                                <fieldset>
                                                    <div class="col-md-12 ">
                                                        <button onclick="GrabarActualizacionNombreYcontraseña()" type="button" class="btn btn-success" id="btnGrabarConfiguraciones"><i class="fa fa-check"></i> Actualizar información</button>
                                                    </div>
                                                </fieldset>
                                            </div>
                                        </div>
                                    </div>


                                    <div class="col-md-6 col-lg-6">
                                        <div class="panel panel-default t-perfil-card">
                                            <div class="panel-heading"><div class="panel-title"><h4><i class="pe-7s-lock"></i> Privilegios</h4></div></div>
                                            <div class="panel-body">
                                                <fieldset>
                                                    <div class="col-md-12 text-success">
                                                        <label for="RolUsuario">Rol De Acceso</label>
                                                        <input type="text" class="form-control input-sm input-sm" maxlength='28' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="RolUsuario" id="RolUsuario" requerido="si" value="" placeholder="CorreoDeAcceso" />
                                                    </div>
                                                </fieldset>

                                                <fieldset>
                                                    <div class="col-md-12 text-success">
                                                        <div class="btn-group" role="group" aria-label="...">
                                                            <div class="checkbox checkbox-success">
                                                                <br>
                                                                <input id="ckEsAdmin" type="checkbox">
                                                                <label for="ckEsAdmin">
                                                                    Es Admin
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </fieldset>
                                                <fieldset>
                                                    <div class="col-md-12 ">
                                                        <label for="IdDeUsuario">N° Usuario</label>
                                                        <input type="text" class="form-control input-sm input-sm" maxlength='28' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="IdDeUsuario" id="IdDeUsuario" requerido="si" value="" placeholder="Nombre De Usuario" />
                                                    </div>
                                                </fieldset>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="clearfix"></div>

                                    <!--
             <div class="col-md-4 col-lg-4">
                <div class="panel panel-default btn-success">
                    <div class="panel-title text-center">
                        Accesos
                    </div>
                    <div class="panel-body">
                        <fieldset>
                          
                        </fieldset>

                    </div>
                </div>-->

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>