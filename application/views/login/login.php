<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DATAMAULE - Login</title>

    <link rel="shortcut icon" href="<?php echo base_url() ?>assets/dist/img/ico/datamaule36x36.png" type="image/x-icon">
    
    <script src="https://ajax.googleapis.com/ajax/libs/webfont/1.6.26/webfont.js"></script>
    <script>
        WebFont.load({
            google: {
                families: ['Poppins:300,400,500,600,700', 'Inter:300,400,500,600,700']
            }
        });
    </script>

    <link href="<?php echo base_url() ?>assets/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="<?php echo base_url() ?>assets/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
    
    <style type="text/css">
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Poppins', sans-serif;
            background: url("<?php echo base_url() ?>assets/dist/img/imgfondoo.jpg") no-repeat center center fixed;
            -webkit-background-size: cover;
            -moz-background-size: cover;
            -o-background-size: cover;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Elementos decorativos de fondo - overlay oscuro para mejor contraste */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 1;
        }

        body::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: rgba(231, 76, 60, 0.1);
            bottom: -200px;
            left: -200px;
            animation: float 8s ease-in-out infinite;
            z-index: 2;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .login-wrapper {
            width: 100%;
            max-width: 450px;
            padding: 20px;
            position: relative;
            z-index: 10;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 50px 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Header */
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-icon {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            animation: pulse 2s ease-in-out infinite;
            padding: 15px;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .login-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .login-title {
            font-size: 32px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 8px;
            font-family: 'Poppins', sans-serif;
            letter-spacing: -0.5px;
        }

        .login-subtitle {
            font-size: 15px;
            color: #718096;
            font-weight: 400;
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 24px;
            position: relative;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            font-size: 16px;
            transition: color 0.3s ease;
        }

        .form-control {
            width: 100%;
            height: 52px;
            padding: 0 16px 0 48px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            color: #2d3748;
            background: #f7fafc;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
        }

        .form-control:focus {
            outline: none;
            border-color: #e74c3c;
            background: white;
            box-shadow: 0 0 0 4px rgba(231, 76, 60, 0.1);
        }

        .form-control:focus + .input-icon {
            color: #e74c3c;
        }

        .form-control::placeholder {
            color: #cbd5e0;
        }

        .help-text {
            font-size: 12px;
            color: #a0aec0;
            margin-top: 6px;
            display: block;
        }

        /* Error Message */
        .error-message {
            background: linear-gradient(135deg, #fc5c65 0%, #ec4067 100%);
            color: white;
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 12px rgba(236, 64, 103, 0.3);
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .error-message i {
            font-size: 18px;
        }

        /* Button */
        .btn-login {
            width: 100%;
            height: 52px;
            /*background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);*/
			background: #568c30;
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(86, 140, 48);
			/*box-shadow: 0 10px 25px rgba(231, 76, 60, 0.3);*/
            margin-top: 8px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            /*box-shadow: 0 15px 30px rgba(231, 76, 60, 0.4);*/
            /*background: linear-gradient(135deg, #c0392b 0%, #a93226 100%);*/
        }

        .btn-login:active {
            transform: translateY(0);
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-card {
                padding: 40px 30px;
            }

            .login-title {
                font-size: 28px;
            }

            .login-subtitle {
                font-size: 14px;
            }
        }

        /* Remove default Bootstrap styles that might conflict */
        .form-control:focus {
            border-color: #e74c3c !important;
            box-shadow: 0 0 0 4px rgba(231, 76, 60, 0.1) !important;
        }
    </style>
</head>

<body>
    <div class="login-wrapper">
        <?php echo form_open('Login/new_user', 'id="loginForm"', 'autocomplete="off"'); ?>
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon">
                    <img src="<?php echo base_url() ?>assets/dist/img/logoDatamaule.jpg" alt="Logo Datamaule">
                </div>
                <h1 class="login-title">dat@Resto</h1>
                <p class="login-subtitle">Bienvenido de nuevo</p>
            </div>

            <?php if ($this->session->flashdata('usuario_incorrecto')): ?>
                <div class="error-message">
                    <i class="fa fa-exclamation-circle"></i>
                    <span><?php echo $this->session->flashdata('usuario_incorrecto'); ?></span>
                </div>
            <?php endif; ?>

            <form>
                <div class="form-group">
                    <label class="form-label">Usuario</label>
                    <div class="input-wrapper">
                        <input 
                            id="username" 
                            type="text" 
                            class="form-control" 
                            name="username" 
                            placeholder="Ingresa tu usuario"
                            required
                        >
                        <i class="fa fa-user input-icon"></i>
                    </div>
                    <span class="help-text">Ingresa tu nombre de usuario del sistema</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Contraseña</label>
                    <div class="input-wrapper">
                        <input 
                            id="password" 
                            type="password" 
                            class="form-control" 
                            name="password" 
                            placeholder="••••••••"
                            required
                        >
                        <i class="fa fa-key input-icon"></i>
                    </div>
                    <span class="help-text">Ingresa tu contraseña del sistema</span>
                </div>

                <button type="submit" class="btn-login">
                    Iniciar Sesión
                </button>
            </form>
        </div>
        <?php echo form_close(); ?>
    </div>

    <script src="<?php echo base_url() ?>assets/plugins/jQuery/jquery-1.12.4.min.js"></script>
    <script src="<?php echo base_url() ?>assets/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>