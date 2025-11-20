<?php
date_default_timezone_set('America/Santiago');
$date = new DateTime('now');
$date->modify('last day of this month');
$fechaActual = date('Y-m-d');
?>

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
        <i class="ti-user"></i>
    </div>
    <div class="header-title">
        <h1>PERFIL DEL USUARIO: <?php echo $this->session->userdata('nombre_usuario') ?></h1>
        <small id="caja"></small>
        <ol class="breadcrumb">
        </ol>
    </div>
</div>



<?php $fechaActual = date('d-m-Y') ?>
<div class="container-padding">

    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-body">
                    <div class="h-panel">
                        <ul id="myTab" class="nav nav-tabs">
                            <li class="active"><a href="#tabDatosDelUsuario" data-toggle="tab" id="tab1">INFORMACION DE USUARIO</a></li>

                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane fade active in" id="tabDatosDelUsuario">
                                <br>
                                <fieldset>
                                    <div class="col-md-6 col-lg-6">
                                        <div class="panel panel-default btn-success">
                                            <div class="panel-title text-center">
                                                Perfil
                                            </div>
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
                                                    <div class="col-md-8 text-success ">
                                                        <label for="ClaveDeUsuario">Clave De Acceso</label>
                                                        <input type="password" class="form-control input-sm input-sm" maxlength='28' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="ClaveDeUsuario" id="ClaveDeUsuario" requerido="si" value="" placeholder="Clave De Usuario" />
                                                    </div>
                                                    <div class="col-md-4 text-success">
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
                                                        <button onclick="GrabarActualizacionNombreYcontraseña()" type="button" class="btn btn-success" id="btnGrabarConfiguraciones"><i class="fa fa-check-square-o"></i> ACTUALIZAR INFORMACION</button>
                                                    </div>
                                                </fieldset>
                                            </div>
                                        </div>
                                    </div>


                                    <div class="col-md-6 col-lg-6">
                                        <div class="panel panel-default btn-success">
                                            <div class="panel-title text-center">
                                                Privilegios
                                            </div>
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