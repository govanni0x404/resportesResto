<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
	<title>DATAMAULE</title>

	<!-- Favicon and touch icons -->
	<link rel="shortcut icon" href="<?php echo base_url() ?>assets/dist/img/ico/datamaule36x36.png" type="image/x-icon">
	<link rel="apple-touch-icon" type="image/x-icon" href="<?php echo base_url() ?>assets/dist/img/ico/datamaule36x36.png">
	<link rel="apple-touch-icon" type="image/x-icon" sizes="72x72" href="<?php echo base_url() ?>assets/dist/img/ico/datamaule36x36.png">
	<link rel="apple-touch-icon" type="image/x-icon" sizes="114x114" href="<?php echo base_url() ?>assets/dist/img/ico/datamaule36x36.png">
	<link rel="apple-touch-icon" type="image/x-icon" sizes="144x144" href="<?php echo base_url() ?>assets/dist/img/ico/datamaule36x36.png">

	<!-- Bootstrap -->
	<link href="<?php echo base_url() ?>assets/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
	<!-- Bootstrap rtl -->
	<!--<link href="<?php echo base_url() ?>assets/bootstrap-rtl/bootstrap-rtl.min.css" rel="stylesheet" type="text/css"/>-->
	<!-- Pe-icon-7-stroke -->
	<link href="<?php echo base_url() ?>assets/pe-icon-7-stroke/css/pe-icon-7-stroke.css" rel="stylesheet" type="text/css" />
	<link href="<?php echo base_url() ?>assets/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
	<!-- Theme style -->
	<link href="<?php echo base_url() ?>assets/dist/css/component_ui.min.css" rel="stylesheet" type="text/css" />
	<!-- Theme style rtl -->
	<!--<link href="<?php echo base_url() ?>assets/dist/css/component_ui_rtl.css" rel="stylesheet" type="text/css"/>-->
	<!-- Custom css -->
	<link href="<?php echo base_url() ?>assets/dist/css/custom.css" rel="stylesheet" type="text/css" />

	<style type="text/css">
		.fondo {
			background: url("<?php echo base_url() ?>assets/dist/img/fondoLogin.jpg") no-repeat center center fixed;
			-webkit-background-size: cover;
			-moz-background-size: cover;
			-o-background-size: cover;
			background-size: cover;
		}
		.fondo2 {
			background: url("<?php echo base_url() ?>assets/dist/img/fondo2.jpg") no-repeat center center fixed;
			-webkit-background-size: cover;
			-moz-background-size: cover;
			-o-background-size: cover;
			background-size: cover;
		}
 
	</style>
</head>

<body>
	<!-- Content Wrapper -->
	<div  style="height: 100vh; " class="col-md-9 fondo">

	</div>
	<div class="col-md-3 fondo2" style="height: 100vh; " >
				<div class="login-wrapper ">
					<?php echo form_open('Login/new_user', 'id="loginForm"', 'autocomplete="off"'); ?>
					<div class="container-center">
						<div class="panel panel-bd">
							<div class="panel-heading">
								<div class="view-header">
									<div class="header-icon">
										<i class="pe-7s-unlock"></i>
									</div>
									<div class="header-title">
										<h3>dat@Resto</h3>
										<br>
										<small><strong>Inicio De Sesion.</strong></small>
									</div>
								</div>
							</div>
							<div class="panel-body">
								<!--<form action="index.html" id="loginForm" novalidate>-->
									<div class="form-group">
										<label class="control-label">USUARIO</label>
										<div class="input-group">
											<span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
											<input id="username" type="text" class="form-control" name="username" placeholder="Nombre De Usuario">
										</div>
										<span class="help-block small">Favor Ingresar Nombre De Usuario Del Sistema</span>
									</div>
									<div class="form-group">
										<label class="control-label">CONTRASEÑA</label>
										<div class="input-group">
											<span class="input-group-addon"><i class="fa fa-key"></i></span>
											<input id="password" type="password" class="form-control" name="password" placeholder="******">
										</div>
										<span class="help-block small">Favor Ingresar Contraseña De Usuario Del Sistema</span>
									</div>

									<?php
									if ($this->session->flashdata('usuario_incorrecto')) {
										$message = $this->session->flashdata('usuario_incorrecto');
									?>
										<div class="error" style="color:red; font-weight:bold"><?php echo $message; ?>

										</div>
									<?php
									}
									?>

									<div>
										<!--<button href="" class="btn btn-success pull-right"><i class="pe-7s-right-arrow"></i> INICIAR SESION</button>-->
										<button type="submit" class="btn btn-labeled btn-success m-b-5">
											<span class="btn-label"><i class="glyphicon glyphicon-ok"></i></span>Iniciar Sesion
										</button>
									</div>

									<?php
									/*
										if (!extension_loaded('pgsql')) {
											echo "La extensión pgsql NO está cargada.";
										}

										if (extension_loaded('curl')) {
											echo "cURL está habilitado";
										} else {
											echo "cURL NO está habilitado";
										}
										*/
										?>
								<!--</form>-->


							</div>
						</div>
					</div>
					<?php echo form_close(); ?>

				</div>

	</div>

	<!-- /.content-wrapper -->
	<!-- jQuery -->
	<script src="<?php echo base_url() ?>assets/plugins/jQuery/jquery-1.12.4.min.js"></script>
	<!-- bootstrap js -->
	<script src="<?php echo base_url() ?>assets/bootstrap/js/bootstrap.min.js"></script>
</body>

</html>
