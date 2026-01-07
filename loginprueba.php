<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>DATAMAULE - Sistema Agrícola</title>

	<link rel="shortcut icon" href="<?php echo base_url() ?>assets/dist/img/ico/datamaule36x36.png" type="image/x-icon">
	<link rel="apple-touch-icon" type="image/x-icon" href="<?php echo base_url() ?>assets/dist/img/ico/datamaule36x36.png">
	<link rel="apple-touch-icon" type="image/x-icon" sizes="72x72" href="<?php echo base_url() ?>assets/dist/img/ico/datamaule36x36.png">
	<link rel="apple-touch-icon" type="image/x-icon" sizes="114x114" href="<?php echo base_url() ?>assets/dist/img/ico/datamaule36x36.png">
	<link rel="apple-touch-icon" type="image/x-icon" sizes="144x144" href="<?php echo base_url() ?>assets/dist/img/ico/datamaule36x36.png">

	<link href="<?php echo base_url() ?>assets/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
	<link href="<?php echo base_url() ?>assets/pe-icon-7-stroke/css/pe-icon-7-stroke.css" rel="stylesheet" type="text/css" />
	<link href="<?php echo base_url() ?>assets/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
	<link href="<?php echo base_url() ?>assets/dist/css/component_ui.min.css" rel="stylesheet" type="text/css" />
	<link href="<?php echo base_url() ?>assets/dist/css/custom.css" rel="stylesheet" type="text/css" />
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

	<style type="text/css">
		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}

		html,
		body {
			height: 100%;
			font-family: 'Poppins', sans-serif;
		}

		.fondo2 {
			background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)),
				url("<?php echo base_url() ?>assets/dist/img/fondoCosecha.png") no-repeat center center;
			-webkit-background-size: cover;
			-moz-background-size: cover;
			-o-background-size: cover;
			background-size: cover;
			background-attachment: fixed;
			display: flex;
			justify-content: center;
			align-items: center;
			min-height: 100vh;
			padding: 20px;
			position: relative;
		}

		.fondo2::before {
			content: '';
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background: rgba(76, 175, 80, 0.1);
		}

		.login-wrapper {
			max-width: 450px;
			width: 100%;
			position: relative;
			z-index: 10;
			animation: slideDown 0.6s ease-out;
		}

		@keyframes slideDown {
			from {
				opacity: 0;
				transform: translateY(-30px);
			}

			to {
				opacity: 1;
				transform: translateY(0);
			}
		}

		.panel-bd {
			background-color: rgba(255, 255, 255, 0.98);
			border-radius: 20px !important;
			border: none;
			box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3),
				0 0 0 1px rgba(255, 255, 255, 0.1);
			backdrop-filter: blur(10px);
			overflow: hidden;
		}

		.panel-heading {
			padding: 40px 40px 20px 40px;
			background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
			border-radius: 20px 20px 0 0 !important;
			border-bottom: none;
			position: relative;
			text-align: center !important;
		}

		.panel-heading::after {
			content: '';
			position: absolute;
			bottom: -20px;
			left: 0;
			right: 0;
			height: 40px;
			background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
			border-radius: 0 0 50% 50%;
		}

		.panel-body {
			padding: 50px 40px 40px 40px;
			position: relative;
			z-index: 1;
		}

		.view-header {
			text-align: center !important;
			margin-bottom: 0;
		}

		.logo-container {
			display: block;
			width: 100px;
			height: 100px;
			background: rgba(255, 255, 255, 0.95);
			border-radius: 50%;
			padding: 15px;
			margin: 0 auto 20px auto;
			box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
		}

		.logo-container img {
			width: 100%;
			height: 100%;
			object-fit: contain;
		}

		.header-title {
			text-align: center !important;
			margin: 0 auto !important;
			float: none !important;
			display: block !important;
		}

		.header-title h3 {
			font-size: 28px;
			font-weight: 600;
			color: #fff;
			margin: 0 !important;
			text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
			text-align: center !important;
			float: none !important;
			display: block !important;
		}

		.form-group {
			margin-bottom: 25px;
		}

		.control-label {
			font-weight: 600;
			color: #333;
			font-size: 13px;
			text-transform: uppercase;
			letter-spacing: 0.5px;
			margin-bottom: 10px;
		}

		.input-group {
			box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
			border-radius: 10px;
			overflow: hidden;
			transition: all 0.3s ease;
		}

		.input-group:focus-within {
			box-shadow: 0 4px 12px rgba(76, 175, 80, 0.25);
			transform: translateY(-2px);
		}

		.input-group-addon {
			background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
			border: none;
			color: #fff;
			padding: 12px 15px;
		}

		.input-group-addon i {
			font-size: 18px;
		}

		.form-control {
			border: none;
			border-left: 2px solid #f0f0f0;
			padding: 12px 15px;
			height: auto;
			font-size: 15px;
			transition: all 0.3s ease;
		}

		.form-control:focus {
			box-shadow: none;
			border-left-color: #4CAF50;
			background-color: #fafafa;
		}

		.help-block {
			font-size: 12px;
			color: #666;
			margin-top: 8px;
			font-style: italic;
		}

		.error {
			background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
			color: white !important;
			font-weight: 500 !important;
			padding: 12px 20px;
			border-radius: 10px;
			margin-bottom: 20px;
			box-shadow: 0 4px 12px rgba(244, 67, 54, 0.3);
			display: flex;
			align-items: center;
			animation: shake 0.5s ease;
		}

		.error::before {
			content: '⚠';
			margin-right: 10px;
			font-size: 18px;
		}

		@keyframes shake {

			0%,
			100% {
				transform: translateX(0);
			}

			25% {
				transform: translateX(-10px);
			}

			75% {
				transform: translateX(10px);
			}
		}

		.btn-success,
		.btn-labeled.btn-success {
			border-radius: 10px;
			background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
			border: none;
			padding: 14px 20px;
			font-size: 16px;
			font-weight: 600;
			text-transform: uppercase;
			letter-spacing: 1px;
			box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
			transition: all 0.3s ease;
			position: relative;
			overflow: hidden;
		}

		.btn-success::before {
			content: '';
			position: absolute;
			top: 0;
			left: -100%;
			width: 100%;
			height: 100%;
			background: rgba(255, 255, 255, 0.2);
			transition: left 0.5s ease;
		}

		.btn-success:hover::before {
			left: 100%;
		}

		.btn-success:hover,
		.btn-labeled.btn-success:hover {
			background: linear-gradient(135deg, #45a049 0%, #388e3c 100%);
			transform: translateY(-3px);
			box-shadow: 0 10px 30px rgba(76, 175, 80, 0.5);
		}

		.btn-success:active {
			transform: translateY(-1px);
		}

		.btn-labeled {
			display: flex;
			align-items: center;
			justify-content: center;
		}

		.btn-labeled .btn-label {
			background-color: transparent;
			border: none;
			padding: 0;
			margin-right: 10px;
		}

		.btn-label i {
			font-size: 18px;
		}

		@media (max-width: 576px) {
			.login-wrapper {
				max-width: 100%;
			}

			.panel-heading {
				padding: 30px 25px 15px 25px;
			}

			.panel-body {
				padding: 40px 25px 30px 25px;
			}

			.view-header {
				text-align: center;
			}

			.logo-container {
				width: 80px;
				height: 80px;
				padding: 12px;
				margin: 0 auto 20px auto;
				display: block;
			}

			.header-title h3 {
				font-size: 24px;
				padding-bottom: 10px;
				margin: 0;
				text-align: center;
			}
		}
	</style>

</head>

<body>
	<div class="container-fluid" style="padding:0; margin:0;">
		<div class="row no-gutter">

			<div class="col-xs-12 col-md-12 fondo2 login-col">
				<div class="login-wrapper">

					<?php echo form_open('Login/new_user', 'id="loginForm"', 'autocomplete="off"'); ?>

					<div class="panel panel-bd">
						<div class="panel-heading">
							<div class="view-header">
								<div class="logo-container">
									<img src="<?php echo base_url() ?>assets/dist/img/logoDm.png" alt="Logo Sistema Agrícola">
								</div>
								<div class="header-title">
									<h3>Sistema Agrícola</h3>
								</div>
							</div>
						</div>
						<div class="panel-body">
							<div class="form-group">
								<label class="control-label">Usuario</label>
								<div class="input-group">
									<span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
									<input id="username" type="text" class="form-control" name="username" placeholder="Ingrese su usuario" required>
								</div>
								<span class="help-block small">Favor ingresar nombre de usuario del sistema</span>
							</div>

							<div class="form-group">
								<label class="control-label">Contraseña</label>
								<div class="input-group">
									<span class="input-group-addon"><i class="fa fa-key"></i></span>
									<input id="password" type="password" class="form-control" name="password" placeholder="Ingrese su contraseña" required>
								</div>
								<span class="help-block small">Favor ingresar contraseña de usuario del sistema</span>
							</div>

							<?php
							if ($this->session->flashdata('usuario_incorrecto')) {
								$message = $this->session->flashdata('usuario_incorrecto');
							?>
								<div class="error"><?php echo $message; ?></div>
							<?php
							}
							?>

							<div>
								<button type="submit" class="btn btn-labeled btn-success m-b-5" style="width: 100%;">
									<span class="btn-label"><i class="glyphicon glyphicon-ok"></i></span>
									Iniciar Sesión
								</button>
							</div>

						</div>
					</div>

					<?php echo form_close(); ?>

				</div>

			</div>
		</div>
	</div>

	<script src="<?php echo base_url() ?>assets/plugins/jQuery/jquery-1.12.4.min.js"></script>
	<script src="<?php echo base_url() ?>assets/bootstrap/js/bootstrap.min.js"></script>
	<script type="text/javascript">
		$(document).ready(function() {
			var usernameInput = document.getElementById('username');
			var passwordInput = document.getElementById('password');

			function setCustomPlaceholder(inputElement, requiredMessage) {
				if (inputElement && inputElement.setCustomValidity) {

					inputElement.oninvalid = function(e) {
						e.target.setCustomValidity("");
						if (!e.target.validity.valid) {
							e.target.setCustomValidity(requiredMessage);
						}
					};

					inputElement.oninput = function(e) {
						e.target.setCustomValidity("");
					};
				}
			}

			setCustomPlaceholder(usernameInput, '¡Atención! Debe ingresar su nombre de usuario.');
			setCustomPlaceholder(passwordInput, '¡Atención! Debe ingresar su contraseña.');

		});
	</script>
</body>

</html>