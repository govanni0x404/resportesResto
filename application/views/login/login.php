<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DATARESTO</title>

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
            justify-content: center;
            position: relative;
            overflow-x: hidden;
            overflow-y: auto;
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
            margin: auto 0;
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

        /* ================================================================
           11-11-2026: publicidad dat@Resto / Datamaule sobre el fondo y
           "¿Olvidaste tu contraseña?" (igual que mipdv_web).
           La tarjeta de acceso no cambia: solo se ubica a la derecha.
           ================================================================ */
        .lg-layout {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 1180px;
            margin: auto 0;
            padding: 24px;
            display: flex;
            flex-direction: row-reverse;   /* en el HTML la tarjeta va primero (orden del teclado) */
            align-items: center;
            justify-content: space-between;
            gap: 48px;
        }
        .lg-layout .login-wrapper { flex: 0 0 450px; margin: 0; }
        /* Con la publicidad la página puede ser más alta que la pantalla (celular): el velo oscuro y el círculo
           decorativo quedan fijos a la pantalla, así no se corta el velo ni se alarga la página hacia abajo */
        body::before, body::after { position: fixed; }

        .lg-hero { flex: 1 1 auto; max-width: 600px; color: #fff; }
        .lg-brand { display: flex; align-items: center; gap: 18px; margin-bottom: 34px; }
        .lg-brand-logo { background: #fff; border-radius: 14px; padding: 10px 14px; box-shadow: 0 10px 25px rgba(0,0,0,.25); flex: 0 0 auto; }
        .lg-brand-logo img { display: block; width: 176px; height: auto; }
        .lg-brand-text { padding-left: 18px; border-left: 2px solid rgba(255,255,255,.25); }
        .lg-brand-name { font-family: 'Poppins', sans-serif; font-size: 28px; font-weight: 700; line-height: 1.05; letter-spacing: -.3px; color: #fff; }
        .lg-brand-sub { margin-top: 4px; font-size: 15px; color: rgba(255,255,255,.82); }

        .lg-anuncios { display: grid; }
        .lg-anuncio { grid-area: 1 / 1; opacity: 0; visibility: hidden; transform: translateY(8px); transition: opacity .5s ease, transform .5s ease, visibility .5s; }
        .lg-anuncio.is-activo { opacity: 1; visibility: visible; transform: none; }
        .lg-anuncio-tag { display: inline-block; padding: 5px 12px; margin-bottom: 14px; border-radius: 999px; background: rgba(166,220,124,.18); border: 1px solid rgba(166,220,124,.5); color: #d3f0bb; font-size: 12px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; }
        .lg-title { font-family: 'Poppins', sans-serif; font-size: 46px; font-weight: 700; line-height: 1.1; letter-spacing: -.5px; color: #fff; margin: 0 0 14px; text-shadow: 0 2px 14px rgba(0,0,0,.35); }
        .lg-title span { color: #a6dc7c; }
        .lg-lead { font-size: 17px; line-height: 1.55; color: rgba(255,255,255,.9); max-width: 520px; margin: 0 0 18px; text-shadow: 0 1px 8px rgba(0,0,0,.35); }
        .lg-anuncio-cta { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 999px; background: #fff; color: #2d3748; font-size: 14px; font-weight: 600; text-decoration: none; box-shadow: 0 8px 20px rgba(0,0,0,.25); transition: transform .2s ease; }
        .lg-anuncio-cta:hover, .lg-anuncio-cta:focus { color: #2d3748; text-decoration: none; transform: translateY(-2px); }
        .lg-anuncio-cta .fa-whatsapp { color: #22a447; font-size: 18px; }

        .lg-dots { display: flex; align-items: center; gap: 2px; margin-top: 20px; }
        .lg-dot { width: 26px; height: 26px; padding: 0; border: 0; background: none; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; }
        .lg-dot::before { content: ''; display: block; width: 9px; height: 9px; border-radius: 999px; background: rgba(255,255,255,.45); transition: width .3s ease, background .3s ease; }
        .lg-dot.is-activo::before { width: 22px; background: #a6dc7c; }
        .lg-pausa { width: 30px; height: 30px; margin-left: 6px; border-radius: 50%; border: 1px solid rgba(255,255,255,.35); background: rgba(0,0,0,.2); color: #fff; font-size: 11px; cursor: pointer; }
        .lg-dot:focus-visible, .lg-pausa:focus-visible, .lg-anuncio-cta:focus-visible, .dm-ad a:focus-visible { outline: 2px solid #a6dc7c; outline-offset: 2px; }

        .lg-features { list-style: none; display: flex; flex-wrap: wrap; gap: 8px; margin: 28px 0 0; padding: 0; }
        .lg-features li { display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 12px; background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.2); font-size: 13px; font-weight: 600; color: #fff; -webkit-backdrop-filter: blur(6px); backdrop-filter: blur(6px); }
        .lg-features i { color: #a6dc7c; font-size: 16px; }

        .dm-ad { margin-top: 22px; max-width: 560px; display: flex; flex-wrap: wrap; align-items: center; gap: 10px 18px; padding: 14px 18px; border-radius: 16px; background: rgba(255,255,255,.95); color: #2d3748; box-shadow: 0 12px 30px rgba(0,0,0,.25); }
        .dm-ad-top { display: flex; align-items: center; gap: 12px; }
        .dm-ad-ico { display: inline-flex; align-items: center; gap: 4px; padding: 6px 9px; border-radius: 10px; background: #edf6e6; color: #3d6b20; font-size: 16px; }
        .dm-ad-ico b { font-size: 12px; letter-spacing: .04em; }
        .dm-ad-title { font-weight: 700; font-size: 15px; }
        .dm-ad-contact { display: flex; flex-wrap: wrap; gap: 6px 16px; }
        .dm-ad-contact a { display: inline-flex; align-items: center; gap: 6px; color: #1a7f37; font-weight: 600; font-size: 14px; text-decoration: none; white-space: nowrap; }
        .dm-ad-contact a:hover { text-decoration: underline; }
        .dm-ad-contact .fa-whatsapp { font-size: 18px; color: #22a447; }
        .dm-ad-pie { width: 100%; font-size: 12px; color: #718096; }
        .lg-firma { margin-top: 16px; font-size: 12px; color: rgba(255,255,255,.72); }

        @media (min-width: 992px) {
            /* Más oscuro a la izquierda para que se lea la publicidad; la foto sigue siendo la misma */
            body::before { background: linear-gradient(90deg, rgba(0,0,0,.72) 0%, rgba(0,0,0,.5) 50%, rgba(0,0,0,.35) 100%); }
        }
        @media (max-width: 991px) {
            .lg-layout { flex-direction: column; gap: 4px; padding: 0 0 24px; max-width: 450px; }
            .lg-layout .login-wrapper { flex: none; width: 100%; }
            .lg-hero { width: calc(100% - 40px); max-width: none; padding: 22px 20px; border-radius: 20px; background: rgba(0,0,0,.55); -webkit-backdrop-filter: blur(6px); backdrop-filter: blur(6px); }
            .lg-brand { margin-bottom: 20px; gap: 14px; }
            .lg-brand-logo img { width: 128px; }
            .lg-brand-text { padding-left: 14px; }
            .lg-brand-name { font-size: 22px; }
            .lg-brand-sub { font-size: 13px; }
            .lg-title { font-size: 28px; }
            .lg-lead { font-size: 15px; }
            .lg-features { margin-top: 18px; gap: 8px; }
            .lg-features li { font-size: 12px; padding: 7px 10px; }
            .dm-ad { padding: 12px 14px; }
        }
        @media (prefers-reduced-motion: reduce) {
            .lg-anuncio, .lg-dot::before, .lg-anuncio-cta { transition: none; }
        }

        /* Enlace "¿Olvidaste tu contraseña?" */
        .login-opts { text-align: center; margin-top: 18px; }
        .lg-forgot { border: 0; background: none; padding: 6px 10px; border-radius: 8px; color: #3d6b20; font-size: 14px; font-weight: 600; cursor: pointer; }
        .lg-forgot:hover { text-decoration: underline; }
        .lg-forgot:focus-visible { outline: 2px solid #568c30; outline-offset: 2px; }

        /* Recuperar contraseña (ventana) */
        body.lg-modal-abierto { overflow: hidden; }
        .lg-modal { position: fixed; top: 0; right: 0; bottom: 0; left: 0; z-index: 1000; display: flex; align-items: center; justify-content: center; padding: 16px; }
        .lg-modal[hidden] { display: none; }
        .lg-modal__bg { position: absolute; top: 0; right: 0; bottom: 0; left: 0; background: rgba(15,23,42,.6); }
        .lg-modal__panel { position: relative; width: 100%; max-width: 480px; max-height: calc(100vh - 32px); overflow-y: auto; background: #fff; border-radius: 20px; padding: 28px 26px 22px; box-shadow: 0 24px 60px rgba(0,0,0,.35); animation: slideUp .3s ease-out; text-align: left; }
        .lg-modal__close { position: absolute; top: 10px; right: 12px; width: 38px; height: 38px; border: 0; border-radius: 10px; background: transparent; color: #718096; font-size: 26px; line-height: 1; cursor: pointer; }
        .lg-modal__close:hover { background: #f7fafc; color: #2d3748; }
        .lg-modal h2 { display: flex; align-items: center; gap: 10px; margin: 0 40px 6px 0; font-family: 'Poppins', sans-serif; font-size: 22px; font-weight: 700; color: #2d3748; }
        .lg-modal h2 i { color: #568c30; }
        .lg-modal .lead { margin: 0 0 18px; font-size: 14px; font-weight: 400; line-height: 1.5; color: #718096; }
        .lg-msg { display: none; margin-bottom: 14px; padding: 10px 12px; border-radius: 10px; font-size: 14px; line-height: 1.45; }
        .lg-msg.is-error { display: block; background: #fef3f2; border: 1px solid #fecdca; color: #b42318; }
        .lg-msg.is-info { display: block; background: #eff8ff; border: 1px solid #b2ddff; color: #175cd3; }
        .lg-msg.is-ok { display: block; background: #ecfdf3; border: 1px solid #abefc6; color: #027a48; }
        .lg-form-group { margin-bottom: 16px; }
        .lg-form-group > label { display: block; margin-bottom: 7px; font-size: 13px; font-weight: 600; color: #4a5568; }
        .lg-paso { display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; margin-right: 6px; border-radius: 50%; background: #568c30; color: #fff; font-size: 12px; }
        .lg-modal input[type="email"], .lg-modal input[type="text"], .lg-modal input[type="password"] { width: 100%; height: 46px; padding: 0 14px; border: 2px solid #e2e8f0; border-radius: 12px; background: #f7fafc; color: #2d3748; font-size: 15px; font-family: 'Inter', sans-serif; transition: border-color .2s ease, box-shadow .2s ease; }
        .lg-modal input:focus { outline: none; border-color: #568c30; background: #fff; box-shadow: 0 0 0 4px rgba(86,140,48,.15); }
        .lg-modal input[readonly] { background: #edf2f7; color: #4a5568; }
        .lg-form-group .err { display: none; margin-top: 5px; font-size: 12px; color: #b42318; }
        .lg-form-group.is-invalid .err { display: block; }
        .lg-form-group.is-invalid input { border-color: #f04438; }
        .lg-hp { position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden; }
        .lg-captcha { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
        .lg-captcha .q { min-width: 118px; font-size: 14px; font-weight: 600; color: #2d3748; }
        .lg-modal .lg-captcha input#rcVerificacion { flex: 0 0 72px; width: 72px; text-align: center; }
        .lg-modal .lg-captcha input#rcCodigo { flex: 1 1 140px; width: auto; text-align: center; font-size: 20px; letter-spacing: 6px; }
        .lg-icon-btn { width: 42px; height: 42px; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; color: #4a5568; cursor: pointer; }
        .lg-btn-sec { height: 42px; padding: 0 14px; border: 0; border-radius: 10px; background: #568c30; color: #fff; font-size: 14px; font-weight: 600; cursor: pointer; white-space: nowrap; }
        .lg-btn-sec:disabled, .lg-icon-btn:disabled, .lg-btn:disabled { opacity: .55; cursor: not-allowed; }
        .lg-link { padding: 0; border: 0; background: none; color: #3d6b20; font-size: 13px; font-weight: 600; text-decoration: underline; cursor: pointer; }
        .lg-link:disabled { color: #a0aec0; text-decoration: none; cursor: default; }
        .rc-pw { position: relative; }
        .lg-modal .rc-pw input { padding-right: 46px; }
        .rc-pw-toggle { position: absolute; top: 50%; right: 5px; transform: translateY(-50%); width: 36px; height: 36px; border: 0; background: transparent; color: #718096; cursor: pointer; }
        .lg-btn { width: 100%; height: 50px; margin-top: 4px; border: 0; border-radius: 12px; background: #568c30; color: #fff; font-size: 16px; font-weight: 600; cursor: pointer; }
        .lg-modal-pie { margin: 14px 0 0; font-size: 13px; color: #4a5568; }
        .lg-modal-pie a { color: #3d6b20; font-weight: 600; }
        .lg-modal button:focus-visible, .lg-modal a:focus-visible { outline: 2px solid #568c30; outline-offset: 2px; }
    </style>
</head>

<body>
<?php
    /* PUBLICIDAD DEL LOGIN (11-11-2026): los anuncios salen de application/config/login_publicidad.php.
       Si el archivo falta o la lista viene vacía, se muestra el anuncio de respaldo de aquí abajo. */
    $lgAnuncios = array();
    $lgIntervalo = 7;
    try {
        if ($this->config->load('login_publicidad', TRUE, TRUE)) {
            $lgCfg = (array)$this->config->item('login_publicidad');
            $lgAnuncios = isset($lgCfg['anuncios']) && is_array($lgCfg['anuncios']) ? $lgCfg['anuncios'] : array();
            $lgIntervalo = isset($lgCfg['intervalo_segundos']) ? (int)$lgCfg['intervalo_segundos'] : 7;
        }
    } catch (Throwable $e) {
        $lgAnuncios = array();
    }
    $lgIntervalo = max(4, min(60, $lgIntervalo));
    $lgLimpios = array();
    foreach ($lgAnuncios as $lgA) {
        if (!is_array($lgA) || trim((string)($lgA['titulo'] ?? '')) === '') continue;
        $lgUrl = trim((string)($lgA['boton_url'] ?? ''));
        if ($lgUrl !== '' && !preg_match('#^(https?://|mailto:|tel:)#i', $lgUrl)) $lgUrl = '';
        $lgLimpios[] = array(
            'etiqueta' => trim((string)($lgA['etiqueta'] ?? '')),
            'titulo' => trim((string)$lgA['titulo']),
            'titulo_destacado' => trim((string)($lgA['titulo_destacado'] ?? '')),
            'texto' => trim((string)($lgA['texto'] ?? '')),
            'boton_texto' => $lgUrl !== '' ? trim((string)($lgA['boton_texto'] ?? '')) : '',
            'boton_url' => $lgUrl,
        );
    }
    if (!$lgLimpios) {
        $lgLimpios[] = array('etiqueta' => 'dat@Resto', 'titulo' => 'Tu restaurante,', 'titulo_destacado' => 'en orden y a tiempo.',
            'texto' => 'Mesas, comandas a cocina, caja y reportes en una sola plataforma web.', 'boton_texto' => '', 'boton_url' => '');
    }
    $lgE = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
?>
    <div class="lg-layout">
    <div class="login-wrapper">
        <?php echo form_open('Login/new_user', 'id="loginForm"', 'autocomplete="off"'); ?>
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon">
                    <img src="<?php echo base_url() ?>assets/dist/img/logo_datamaule_d.png" alt="Logo Datamaule">
                </div>
                <h1 class="login-title">dat@Resto</h1>
                <p class="login-subtitle">Bienvenido de nuevo</p>
            </div>

            <?php if ($this->input->get('sesion') === 'expirada' && !$this->session->flashdata('usuario_incorrecto')): ?>
                <div class="error-message">
                    <i class="fa fa-clock-o"></i>
                    <span>Tu sesión expiró por inactividad. Vuelve a ingresar.</span>
                </div>
            <?php endif; ?>
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

                <div class="login-opts">
                    <button type="button" class="lg-forgot" id="lgOlvide">¿Olvidaste tu contraseña?</button>
                </div>
            </form>
        </div>
        <?php echo form_close(); ?>
    </div>

    <!-- Publicidad dat@Resto / Datamaule (11-11-2026): textos en application/config/login_publicidad.php -->
    <section class="lg-hero" aria-label="dat@Resto, de Datamaule">
        <div class="lg-brand">
            <div class="lg-brand-logo">
                <img src="<?php echo base_url() ?>assets/dist/img/logo_datamaule_spa.png" alt="Datamaule SpA - Soluciones Informáticas" width="208" height="82">
            </div>
            <div class="lg-brand-text">
                <div class="lg-brand-name">dat@Resto</div>
                <div class="lg-brand-sub">Sistema para restaurantes</div>
            </div>
        </div>

        <div class="lg-anuncios" id="lgAnuncios" data-intervalo="<?php echo (int)$lgIntervalo; ?>" aria-live="off">
            <?php foreach ($lgLimpios as $lgI => $lgA): ?>
                <div class="lg-anuncio<?php echo $lgI === 0 ? ' is-activo' : ''; ?>"<?php echo $lgI === 0 ? '' : ' aria-hidden="true"'; ?>>
                    <?php if ($lgA['etiqueta'] !== ''): ?><span class="lg-anuncio-tag"><?php echo $lgE($lgA['etiqueta']); ?></span><?php endif; ?>
                    <div class="lg-title"><?php echo $lgE($lgA['titulo']); ?><?php if ($lgA['titulo_destacado'] !== ''): ?><br><span><?php echo $lgE($lgA['titulo_destacado']); ?></span><?php endif; ?></div>
                    <?php if ($lgA['texto'] !== ''): ?><p class="lg-lead"><?php echo $lgE($lgA['texto']); ?></p><?php endif; ?>
                    <?php if ($lgA['boton_texto'] !== ''): ?>
                        <a class="lg-anuncio-cta" href="<?php echo $lgE($lgA['boton_url']); ?>" target="_blank" rel="noopener"<?php echo $lgI === 0 ? '' : ' tabindex="-1"'; ?>>
                            <?php if (stripos($lgA['boton_url'], 'wa.me') !== false): ?><i class="fa fa-whatsapp" aria-hidden="true"></i><?php endif; ?>
                            <?php echo $lgE($lgA['boton_texto']); ?> <i class="fa fa-long-arrow-right" aria-hidden="true"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (count($lgLimpios) > 1): ?>
        <div class="lg-dots" id="lgDots">
            <?php foreach ($lgLimpios as $lgI => $lgA): ?>
                <button type="button" class="lg-dot<?php echo $lgI === 0 ? ' is-activo' : ''; ?>" data-indice="<?php echo (int)$lgI; ?>" aria-label="Ver anuncio <?php echo (int)$lgI + 1; ?> de <?php echo count($lgLimpios); ?>"<?php echo $lgI === 0 ? ' aria-current="true"' : ''; ?>></button>
            <?php endforeach; ?>
            <button type="button" class="lg-pausa" id="lgPausa" aria-label="Pausar anuncios" title="Pausar"><i class="fa fa-pause" aria-hidden="true"></i></button>
        </div>
        <?php endif; ?>

        <ul class="lg-features">
            <li><i class="fa fa-cutlery" aria-hidden="true"></i>Mesas y comandas</li>
            <li><i class="fa fa-calculator" aria-hidden="true"></i>Caja y cierres</li>
            <li><i class="fa fa-file-text-o" aria-hidden="true"></i>Boleta electrónica</li>
            <li><i class="fa fa-bar-chart" aria-hidden="true"></i>Reportes</li>
        </ul>

        <div class="dm-ad">
            <div class="dm-ad-top">
                <span class="dm-ad-ico" aria-hidden="true"><i class="fa fa-file-text-o"></i><b>SII</b></span>
                <div class="dm-ad-title">Contrata Boleta Electrónica:</div>
            </div>
            <div class="dm-ad-contact">
                <a href="https://wa.me/56942910206" target="_blank" rel="noopener" aria-label="Escribir por WhatsApp al +56 9 4291 0206"><i class="fa fa-whatsapp" aria-hidden="true"></i>+56 9 4291 0206</a>
                <a href="https://wa.me/56940676939" target="_blank" rel="noopener" aria-label="Escribir por WhatsApp al +56 9 4067 6939"><i class="fa fa-whatsapp" aria-hidden="true"></i>+56 9 4067 6939</a>
            </div>
            <div class="dm-ad-pie">Escríbenos por WhatsApp y te asesoramos</div>
        </div>
        <div class="lg-firma">dat@Resto es un producto de Datamaule SpA · Soluciones informáticas</div>
    </section>
    </div>

    <!-- Recuperar contraseña (11-11-2026, igual que mipdv_web): correo → código → nueva contraseña -->
    <div class="lg-modal" id="lgRecuperar" hidden role="dialog" aria-modal="true" aria-labelledby="lgRcTitulo">
        <div class="lg-modal__bg" data-rc-cerrar></div>
        <div class="lg-modal__panel">
            <button type="button" class="lg-modal__close" data-rc-cerrar aria-label="Cerrar">&times;</button>
            <h2 id="lgRcTitulo"><i class="fa fa-key" aria-hidden="true"></i> Recuperar contraseña</h2>
            <p class="lead">Te enviaremos un código a tu correo para crear una nueva contraseña.</p>
            <form id="lgFormRecuperar" novalidate autocomplete="off">
                <div class="lg-msg" id="lgRcMsg" role="status" aria-live="polite"></div>

                <div id="rcPaso1">
                    <div class="lg-form-group" data-campo="correo">
                        <label for="rcCorreo"><span class="lg-paso">1</span> Correo de acceso</label>
                        <input type="email" id="rcCorreo" name="correo" maxlength="100" autocomplete="email">
                        <div class="err">Ingresa un correo válido.</div>
                    </div>
                    <!-- Campo trampa para bots: no completar -->
                    <div class="lg-hp" aria-hidden="true">
                        <label for="rcSitioWeb">Sitio web</label>
                        <input type="text" id="rcSitioWeb" name="sitio_web" tabindex="-1" autocomplete="off">
                    </div>
                    <div class="lg-form-group" data-campo="verificacion">
                        <label for="rcVerificacion">Confirma que eres una persona</label>
                        <div class="lg-captcha">
                            <span class="q" id="rcPregunta">Cargando…</span>
                            <input type="text" inputmode="numeric" id="rcVerificacion" maxlength="3" autocomplete="off">
                            <button type="button" class="lg-icon-btn" id="rcNuevaPregunta" title="Otra pregunta" aria-label="Otra pregunta"><i class="fa fa-refresh" aria-hidden="true"></i></button>
                            <button type="button" class="lg-btn-sec" id="rcEnviarCodigo"><i class="fa fa-envelope" aria-hidden="true"></i> Enviar código</button>
                        </div>
                        <div class="err">Responde la pregunta de verificación.</div>
                    </div>
                </div>

                <div class="lg-form-group" data-campo="codigo" id="rcPaso2" hidden>
                    <label for="rcCodigo"><span class="lg-paso">2</span> Ingresa el código de 6 dígitos que llegó a <b id="rcCorreoDestino"></b></label>
                    <div class="lg-captcha">
                        <input type="text" inputmode="numeric" id="rcCodigo" maxlength="6" autocomplete="one-time-code" placeholder="••••••">
                        <button type="button" class="lg-btn-sec" id="rcVerificarCodigo"><i class="fa fa-check" aria-hidden="true"></i> Verificar código</button>
                        <button type="button" class="lg-link" id="rcReenviar" disabled>Reenviar código</button>
                    </div>
                    <div class="err">Ingresa el código de 6 dígitos.</div>
                </div>

                <div id="rcPaso3" hidden>
                    <div class="lg-form-group" data-campo="password_nuevo">
                        <label for="rcClave"><span class="lg-paso">3</span> Nueva contraseña</label>
                        <div class="rc-pw">
                            <input type="password" id="rcClave" maxlength="72" autocomplete="new-password">
                            <button type="button" class="rc-pw-toggle" data-rc-ver="rcClave" aria-label="Mostrar contraseña" title="Mostrar/Ocultar"><i class="fa fa-eye" aria-hidden="true"></i></button>
                        </div>
                        <div class="err">Mínimo 8 caracteres, mayúscula, minúscula, número y carácter especial, sin años (ej: 2026).</div>
                        <small style="display:block;margin-top:5px;color:#4a5568;font-size:12px;">Mínimo 8 caracteres, con mayúscula, minúscula, número y carácter especial, sin años (ej: 2026).</small>
                    </div>
                    <div class="lg-form-group" data-campo="password_confirmacion">
                        <label for="rcClave2">Repite la nueva contraseña</label>
                        <div class="rc-pw">
                            <input type="password" id="rcClave2" maxlength="72" autocomplete="new-password">
                            <button type="button" class="rc-pw-toggle" data-rc-ver="rcClave2" aria-label="Mostrar contraseña" title="Mostrar/Ocultar"><i class="fa fa-eye" aria-hidden="true"></i></button>
                        </div>
                        <div class="err">Las contraseñas no coinciden.</div>
                    </div>
                    <button type="submit" class="lg-btn" id="rcGuardar">Guardar nueva contraseña <i class="fa fa-check" aria-hidden="true"></i></button>
                </div>

                <p class="lg-modal-pie">¿No tienes acceso a ese correo? Escríbenos a <a href="mailto:datamaule@gmail.com">datamaule@gmail.com</a></p>
            </form>
        </div>
    </div>

    <script src="<?php echo base_url() ?>assets/plugins/jQuery/jquery-1.12.4.min.js"></script>
    <script src="<?php echo base_url() ?>assets/bootstrap/js/bootstrap.min.js"></script>
    <script>
    /* Anuncios del fondo (11-11-2026): rotan solos; se detienen con el botón de pausa, al pasar el mouse o el foco,
       con la pestaña oculta y si el sistema pide menos movimiento. */
    (function () {
        var cont = document.getElementById('lgAnuncios');
        if (!cont) return;
        var anuncios = cont.querySelectorAll('.lg-anuncio');
        var puntos = document.querySelectorAll('#lgDots .lg-dot');
        var pausa = document.getElementById('lgPausa');
        if (anuncios.length < 2) return;
        var intervalo = Math.max(4, parseInt(cont.getAttribute('data-intervalo'), 10) || 7) * 1000;
        var actual = 0, timer = null, pausado = false, encima = false;
        var menosMovimiento = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        function mostrar(i) {
            actual = (i + anuncios.length) % anuncios.length;
            Array.prototype.forEach.call(anuncios, function (a, k) {
                var si = k === actual;
                a.classList.toggle('is-activo', si);
                if (si) a.removeAttribute('aria-hidden'); else a.setAttribute('aria-hidden', 'true');
                Array.prototype.forEach.call(a.querySelectorAll('a'), function (l) { if (si) l.removeAttribute('tabindex'); else l.setAttribute('tabindex', '-1'); });
            });
            Array.prototype.forEach.call(puntos, function (d, k) {
                d.classList.toggle('is-activo', k === actual);
                if (k === actual) d.setAttribute('aria-current', 'true'); else d.removeAttribute('aria-current');
            });
        }
        function detener() { if (timer) { clearInterval(timer); timer = null; } }
        function iniciar() {
            detener();
            if (pausado || encima || document.hidden) return;
            timer = setInterval(function () { mostrar(actual + 1); }, intervalo);
        }
        function marcarPausa() {
            if (!pausa) return;
            pausa.innerHTML = pausado ? '<i class="fa fa-play" aria-hidden="true"></i>' : '<i class="fa fa-pause" aria-hidden="true"></i>';
            pausa.setAttribute('aria-label', pausado ? 'Reanudar anuncios' : 'Pausar anuncios');
            pausa.title = pausado ? 'Reanudar' : 'Pausar';
        }
        Array.prototype.forEach.call(puntos, function (d) {
            d.addEventListener('click', function () { mostrar(parseInt(d.getAttribute('data-indice'), 10) || 0); iniciar(); });
        });
        if (pausa) pausa.addEventListener('click', function () { pausado = !pausado; marcarPausa(); iniciar(); });
        cont.addEventListener('mouseenter', function () { encima = true; detener(); });
        cont.addEventListener('mouseleave', function () { encima = false; iniciar(); });
        cont.addEventListener('focusin', function () { encima = true; detener(); });
        cont.addEventListener('focusout', function () { encima = false; iniciar(); });
        document.addEventListener('visibilitychange', iniciar);
        if (menosMovimiento) pausado = true;
        marcarPausa();
        iniciar();
    })();
    </script>
    <script>
    /* =================================================================
       RECUPERAR CONTRASEÑA (11-11-2026, igual que mipdv_web)
       correo + pregunta → código al correo → verificar código →
       nueva contraseña + repetición. Endpoints en Login.php
       (recuperarClave*). El código se valida solo en el servidor.
       ================================================================= */
    (function () {
        var urlBase = <?php echo json_encode(rtrim(site_url('Login'), '/') . '/'); ?>;
        var rc = document.getElementById('lgRecuperar');
        var form = document.getElementById('lgFormRecuperar');
        if (!rc || !form || !window.fetch) return;
        function $id(id) { return document.getElementById(id); }
        var correoVerificado = '';
        var timer = null;
        var ultimoFoco = null;
        // Correo sin cuenta de dat@Resto → botones bloqueados hasta cambiarlo
        var correoBloqueado = '';
        function bloquearPorCorreo(correo) {
            correoBloqueado = correo || '';
            var si = correoBloqueado !== '';
            $id('rcEnviarCodigo').disabled = si;
            $id('rcVerificacion').disabled = si;
            $id('rcNuevaPregunta').disabled = si;
        }
        function mensaje(tipo, texto) {
            var el = $id('lgRcMsg');
            el.className = 'lg-msg' + (tipo ? ' is-' + tipo : '');
            el.textContent = texto || '';
        }
        function correoValido(c) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(c); }
        function claveError(p) {
            if (p.length < 8) return 'Debe tener al menos 8 caracteres';
            if (!/[a-z]/.test(p)) return 'Debe contener al menos una letra minúscula';
            if (!/[A-Z]/.test(p)) return 'Debe contener al menos una letra mayúscula';
            if (!/\d/.test(p)) return 'Debe contener al menos un número';
            if (!/[^A-Za-z0-9]/.test(p)) return 'Debe contener al menos un carácter especial (ej: @, #, !, $)';
            if (/20\d{2}/.test(p)) return 'No debe contener años (ej: 2024, 2025, 2026)';
            return null;
        }
        function enviar(accion, datos) {
            var fd = new FormData();
            Object.keys(datos).forEach(function (k) { fd.append(k, datos[k]); });
            return fetch(urlBase + accion, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) { return r.json(); });
        }
        function ocupado(btn, si, texto) {
            if (!btn.getAttribute('data-html')) btn.setAttribute('data-html', btn.innerHTML);
            btn.disabled = si;
            btn.innerHTML = si ? texto + ' <i class="fa fa-spinner fa-spin" aria-hidden="true"></i>' : btn.getAttribute('data-html');
        }
        function marcar(campo, invalido) {
            var g = form.querySelector('[data-campo="' + campo + '"]');
            if (g) g.classList.toggle('is-invalid', !!invalido);
            return !invalido;
        }
        function cargarDesafio() {
            $id('rcPregunta').textContent = 'Cargando…';
            $id('rcVerificacion').value = '';
            fetch(urlBase + 'desafioRecuperarClave', { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (r) { $id('rcPregunta').textContent = (r && r.pregunta) || 'Error'; })
                .catch(function () { $id('rcPregunta').textContent = 'Error al cargar'; });
        }
        function ocultarClaves() {
            Array.prototype.forEach.call(form.querySelectorAll('[data-rc-ver]'), function (b) {
                var inp = $id(b.getAttribute('data-rc-ver'));
                if (inp) inp.type = 'password';
                b.querySelector('i').className = 'fa fa-eye';
                b.setAttribute('aria-label', 'Mostrar contraseña');
            });
        }
        function reiniciar() {
            correoVerificado = '';
            $id('rcPaso1').hidden = false;
            $id('rcPaso2').hidden = true;
            $id('rcPaso3').hidden = true;
            $id('rcCodigo').value = '';
            $id('rcClave').value = '';
            $id('rcClave2').value = '';
            $id('rcCorreo').readOnly = false;
            if (timer) { clearInterval(timer); timer = null; }
            bloquearPorCorreo('');
            ocultarClaves();
            Array.prototype.forEach.call(form.querySelectorAll('.is-invalid'), function (g) { g.classList.remove('is-invalid'); });
        }
        function abrir() {
            ultimoFoco = document.activeElement;
            reiniciar();
            var u = $id('username');
            var correo = u ? u.value.trim() : '';
            if (correoValido(correo)) $id('rcCorreo').value = correo;
            rc.hidden = false;
            document.body.classList.add('lg-modal-abierto');
            mensaje('', '');
            cargarDesafio();
            setTimeout(function () { $id($id('rcCorreo').value ? 'rcVerificacion' : 'rcCorreo').focus(); }, 30);
        }
        function cerrar() {
            rc.hidden = true;
            document.body.classList.remove('lg-modal-abierto');
            if (ultimoFoco && ultimoFoco.focus) ultimoFoco.focus();
        }
        function cuentaReenvio(seg) {
            var btn = $id('rcReenviar');
            btn.disabled = true;
            if (timer) clearInterval(timer);
            var restante = seg;
            btn.textContent = 'Reenviar en ' + restante + 's';
            timer = setInterval(function () {
                restante--;
                if (restante <= 0) { clearInterval(timer); timer = null; btn.disabled = false; btn.textContent = 'Reenviar código'; return; }
                btn.textContent = 'Reenviar en ' + restante + 's';
            }, 1000);
        }

        $id('lgOlvide').addEventListener('click', abrir);
        Array.prototype.forEach.call(document.querySelectorAll('[data-rc-cerrar]'), function (el) { el.addEventListener('click', cerrar); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !rc.hidden) cerrar(); });
        $id('rcNuevaPregunta').addEventListener('click', cargarDesafio);
        Array.prototype.forEach.call(form.querySelectorAll('[data-rc-ver]'), function (b) {
            b.addEventListener('click', function () {
                var inp = $id(b.getAttribute('data-rc-ver'));
                if (!inp) return;
                var ver = inp.type === 'password';
                inp.type = ver ? 'text' : 'password';
                b.querySelector('i').className = ver ? 'fa fa-eye-slash' : 'fa fa-eye';
                b.setAttribute('aria-label', ver ? 'Ocultar contraseña' : 'Mostrar contraseña');
            });
        });

        function enviarCodigo() {
            var correo = $id('rcCorreo').value.trim();
            var resp = $id('rcVerificacion').value.trim();
            if (correoBloqueado && correo.toLowerCase() === correoBloqueado.toLowerCase()) {
                marcar('correo', true);
                mensaje('error', 'Ese correo no tiene una cuenta de dat@Resto. Corrígelo para continuar.');
                return;
            }
            var ok = marcar('correo', !correoValido(correo));
            ok = marcar('verificacion', !/^[0-9]{1,3}$/.test(resp)) && ok;
            if (!ok) { mensaje('error', 'Completa tu correo y responde la pregunta para recibir el código.'); return; }
            var btn = $id('rcEnviarCodigo');
            ocupado(btn, true, 'Enviando');
            enviar('recuperarClaveEnviarCodigo', { correo: correo, verificacion: resp, sitio_web: $id('rcSitioWeb').value }).then(function (r) {
                ocupado(btn, false);
                if (r && r.ok) {
                    $id('rcCorreo').readOnly = true;
                    $id('rcCorreoDestino').textContent = correo;
                    $id('rcPaso2').hidden = false;
                    mensaje('info', r.msg);
                    cuentaReenvio(r.reenvio_seg || 60);
                    setTimeout(function () { $id('rcCodigo').focus(); }, 30);
                    return;
                }
                if (r && r.campo) marcar(r.campo, true);
                mensaje('error', (r && r.msg) || 'No fue posible enviar el código.');
                if (r && r.nuevo_desafio) cargarDesafio();
                if (r && r.no_existe) {
                    $id('rcPaso2').hidden = true;
                    $id('rcCorreo').readOnly = false;
                    if (timer) { clearInterval(timer); timer = null; }
                    bloquearPorCorreo(correo);
                    $id('rcCorreo').focus();
                }
            }).catch(function () {
                ocupado(btn, false);
                mensaje('error', 'No fue posible conectar. Intenta nuevamente.');
            });
        }
        $id('rcEnviarCodigo').addEventListener('click', enviarCodigo);
        $id('rcReenviar').addEventListener('click', enviarCodigo);
        $id('rcVerificacion').addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); enviarCodigo(); }
        });
        $id('rcCorreo').addEventListener('input', function () {
            if (!$id('rcPaso2').hidden) reiniciar();
            if (correoBloqueado && this.value.trim().toLowerCase() !== correoBloqueado.toLowerCase()) {
                bloquearPorCorreo('');
                mensaje('', '');
            }
        });

        function verificarCodigo() {
            var codigo = $id('rcCodigo').value.trim();
            if (!marcar('codigo', !/^[0-9]{6}$/.test(codigo))) { mensaje('error', 'Ingresa el código de 6 dígitos.'); return; }
            var btn = $id('rcVerificarCodigo');
            ocupado(btn, true, 'Verificando');
            enviar('recuperarClaveVerificarCodigo', { correo: $id('rcCorreo').value.trim(), codigo: codigo }).then(function (r) {
                ocupado(btn, false);
                if (r && r.ok) {
                    correoVerificado = $id('rcCorreo').value.trim();
                    $id('rcPaso1').hidden = true;
                    $id('rcPaso2').hidden = true;
                    $id('rcPaso3').hidden = false;
                    if (timer) { clearInterval(timer); timer = null; }
                    mensaje('ok', 'Correo verificado. Crea tu nueva contraseña.');
                    setTimeout(function () { $id('rcClave').focus(); }, 30);
                    return;
                }
                marcar('codigo', true);
                mensaje('error', (r && r.msg) || 'El código no es correcto.');
                if (r && r.expirado) { $id('rcCodigo').value = ''; }
            }).catch(function () {
                ocupado(btn, false);
                mensaje('error', 'No fue posible conectar. Intenta nuevamente.');
            });
        }
        $id('rcVerificarCodigo').addEventListener('click', verificarCodigo);
        $id('rcCodigo').addEventListener('input', function () { this.value = this.value.replace(/\D+/g, '').slice(0, 6); });
        $id('rcCodigo').addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); verificarCodigo(); }
        });
        Array.prototype.forEach.call(form.querySelectorAll('input'), function (el) {
            el.addEventListener('input', function () { var g = el.closest ? el.closest('.lg-form-group') : null; if (g) g.classList.remove('is-invalid'); });
        });

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!correoVerificado) { mensaje('error', 'Primero verifica tu correo con el código.'); return; }
            var p1 = $id('rcClave').value;
            var p2 = $id('rcClave2').value;
            var err = claveError(p1);
            var ok = marcar('password_nuevo', !!err);
            ok = marcar('password_confirmacion', p1 !== p2) && ok;
            if (!ok) { mensaje('error', err || 'Las contraseñas no coinciden.'); return; }
            var btn = $id('rcGuardar');
            ocupado(btn, true, 'Guardando');
            enviar('recuperarClaveGuardar', { correo: correoVerificado, password_nuevo: p1, password_confirmacion: p2 }).then(function (r) {
                ocupado(btn, false);
                if (r && r.ok) {
                    var correo = correoVerificado;
                    reiniciar();
                    $id('rcPaso1').hidden = true;
                    mensaje('ok', r.msg);
                    var u = $id('username'); var pw = $id('password');
                    if (u) u.value = correo;
                    if (pw) pw.value = '';
                    setTimeout(function () { cerrar(); if (pw) pw.focus(); }, 2500);
                    return;
                }
                if (r && r.campo) marcar(r.campo, true);
                mensaje('error', (r && r.msg) || 'No fue posible actualizar la contraseña.');
                if (r && (r.codigo_expirado || r.sin_cuenta)) { reiniciar(); cargarDesafio(); }
            }).catch(function () {
                ocupado(btn, false);
                mensaje('error', 'No fue posible conectar. Intenta nuevamente.');
            });
        });
    })();
    </script>
</body>
</html>