<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>RestoWebadmin</title>

    <!-- Favicon and touch icons -->
    <link rel="shortcut icon" href="<?php echo base_url() ?>assets/dist/img/ico/datamaule36x36.png" type="image/x-icon">
    <link rel="apple-touch-icon" type="image/x-icon" href="<?php echo base_url() ?>assets/dist/img/ico/datamaule36x36.png">
    <link rel="apple-touch-icon" type="image/x-icon" sizes="72x72" href="<?php echo base_url() ?>assets/dist/img/ico/datamaule36x36.png">
    <link rel="apple-touch-icon" type="image/x-icon" sizes="114x114" href="<?php echo base_url() ?>assets/dist/img/ico/datamaule36x36.png">
    <link rel="apple-touch-icon" type="image/x-icon" sizes="144x144" href="<?php echo base_url() ?>assets/dist/img/ico/datamaule36x36.png">

    <!-- Start Global Mandatory Style
        =====================================================================-->
    <!-- <link href="<?php echo base_url() ?>assets/dist/css/base.css" rel="stylesheet" type="text/css" /> -->
    <!-- End Global Mandatory Style
        =====================================================================-->
    <!-- Start page Label Plugins
        =====================================================================-->
    <!-- Toastr css -->
    <!-- <link href="<?php echo base_url() ?>assets/plugins/toastr/toastr.min.css" rel="stylesheet" type="text/css" /> -->
    <!-- Emojionearea -->
    <!-- <link href="<?php echo base_url() ?>assets/plugins/emojionearea/emojionearea.min.css" rel="stylesheet" type="text/css" /> -->
    <!-- Monthly css -->
    <!-- <link href="<?php echo base_url() ?>assets/plugins/monthly/monthly.min.css" rel="stylesheet" type="text/css" /> -->
    <!-- amchat css -->
    <!-- <link href="<?php echo base_url() ?>assets/plugins/amcharts/export.css" rel="stylesheet" type="text/css" /> -->
    <!-- DATATABLES CSS -->
    <!-- <link href="<?php echo base_url() ?>assets/plugins/datatables/datatables.css" rel="stylesheet" type="text/css" /> -->
    <!-- <link href="https://cdn.datatables.net/1.13.3/css/jquery.dataTables.css"> -->
    <!-- End page Label Plugins
        =====================================================================-->
    <!-- Start Theme Layout Style
        =====================================================================-->
    <!-- Theme style -->
    <!-- <link href="<?php echo base_url() ?>assets/dist/css/component_ui.css" rel="stylesheet" type="text/css" /> -->
    <!-- Theme style rtl -->
    <!--<link href="<?php echo base_url() ?>assets/dist/css/component_ui_rtl.css" rel="stylesheet" type="text/css"/>-->
    <!-- Custom css -->
    <!-- <link href="<?php echo base_url() ?>assets/dist/css/custom.css" rel="stylesheet" type="text/css" /> -->
    <!-- <link href="<?php echo base_url() ?>assets/bootstrap/css/bootstrap.css" rel="stylesheet" type="text/css" /> -->

    <!-- End Theme Layout Style



        =====================================================================-->


    <!-- <link href="<?php echo base_url() ?>assets/dist/css/imagenes.css" rel="stylesheet" type="text/css" /> -->

    <!-- ========== Css Files ========== -->
    <link href="<?php echo base_url() ?>assets/bootstrap/css/bootstrap.css" rel="stylesheet" type="text/css" />
    <link href="<?php echo base_url() ?>assets/dist/css/base.css" rel="stylesheet" type="text/css" />
    <!-- <link href="<?php echo base_url() ?>assets/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" /> -->
    <link href="<?php echo base_url() ?>assets/plugins/emojionearea/emojionearea.min.css" rel="stylesheet" type="text/css" />
    <link href="<?php echo base_url() ?>assets/plugins/monthly/monthly.min.css" rel="stylesheet" type="text/css" />
    <link href="<?php echo base_url() ?>assets/plugins/amcharts/export.css" rel="stylesheet" type="text/css" />
    <link href="<?php echo base_url() ?>assets/plugins/toastr/toastr.min.css" rel="stylesheet" type="text/css" />
    <link href="<?php echo base_url() ?>assets/dist/css/component_ui.min.css" rel="stylesheet" type="text/css" />
    <!-- <link id=defaultTheme href="<?php echo base_url() ?>assets/dist/css/skins/skin-dark.css" rel="stylesheet" type="text/css" /> -->
    <!--carpeta asset/dist/skins-->
    <link href="<?php echo base_url() ?>assets/dist/css/custom.css" rel="stylesheet" type="text/css" />
    <link href="<?php echo base_url() ?>assets/plugins/dropzone/dist/min/dropzone.min.css" rel="stylesheet" type="text/css" />
    <link href="<?php echo base_url() ?>assets/plugins/datatables/datatables.css" rel="stylesheet" type="text/css" />
    <!-- <link href="<?php echo base_url() ?>assets/plugins/datatables/buttons.bootstrap.css" rel="stylesheet" type="text/css" /> -->
    <link href="<?php echo base_url() ?>assets/plugins/datatables/responsive.dataTables.css" rel="stylesheet" type="text/css" />
    <!-- <link href="https://cdn.datatables.net/responsive/2.2.7/css/responsive.dataTables.min.css" rel="stylesheet" type="text/css"> -->
    <link href="<?php echo base_url() ?>assets/plugins/sweetalert/sweetalert.css" rel="stylesheet" type="text/css" />
    <link href="<?php echo base_url() ?>/assets/plugins/select2/select2.css" rel="stylesheet" />
    <link href="<?php echo base_url() ?>/assets/plugins/date-range-picker/daterangepicker.css" rel="stylesheet" />
    <style>
        .container {
            width: 95% !important;
            max-width: 95% !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
        .content-header {
            margin: 0 auto 0px !important;
        }
    
            /* ===========================
           Toast flotante #appAlert
           =========================== */
        #appAlert.app-alert{
          position:fixed;
          top:14px;
          right:14px;
          z-index:2147483647;

          /* Layout */
          max-width:360px;
          padding:12px 40px 12px 12px;
          border:1px solid var(--bd, #e5e7eb);
          border-radius:10px;
          box-shadow:0 10px 28px rgba(0,0,0,.15);
          line-height:1.35;
          font-size:14px;

          /* Paleta por variables */
          --bg:#fff;          /* fondo por defecto */
          --fg:#111;          /* texto por defecto */
          --bd:#e5e7eb;       /* borde por defecto */
          background:var(--bg);
          color:var(--fg);

          display:none;       /* se muestra con fadeIn en tu JS */
        }

        /* Variantes de estado (solo cambian variables) */
        #appAlert.alert--success{ --bg:#1abc9c; --fg:#fff; --bd:#16a085; }
        #appAlert.alert--error  { --bg:#e74c3c; --fg:#fff; --bd:#c0392b; }

        /* Asegurar color en textos internos y el botón cerrar */
        #appAlert h4,
        #appAlert span,
        #appAlert .app-alert__close{ color:var(--fg); }

        /* Tipografía interna */
        #appAlert h4{ margin:0 0 4px; font-size:15px; font-weight:700; }

        /* Imagen e ícono opcionales */
        #appAlert .app-alert__img{
          width:32px; height:32px; border-radius:50%;
          object-fit:cover; margin-right:8px; vertical-align:top;
        }
        #appAlert i.fa{ margin-right:8px; font-size:18px; vertical-align:top; }

        /* Botón cerrar */
        #appAlert .app-alert__close{
          position:absolute; top:6px; right:8px;
          border:0; background:transparent; font-size:18px;
          cursor:pointer; line-height:1;
        }
        #appAlert .app-alert__close:hover{ opacity:.85; }

        /* Utilidades de posición que tu JS puede agregar opcionalmente */
        .kode-alert-top-right   { top:14px;  right:14px;  left:auto;  bottom:auto; transform:none; }
        .kode-alert-top-left    { top:14px;  left:14px;   right:auto; bottom:auto; transform:none; }
        .kode-alert-bottom-right{ bottom:14px; right:14px; top:auto; left:auto; transform:none; }
        .kode-alert-bottom-left { bottom:14px; left:14px;  top:auto;  right:auto; transform:none; }
        .kode-alert-top         { top:14px; left:50%; right:auto; bottom:auto; transform:translateX(-50%); }
        .kode-alert-bottom      { bottom:14px; left:50%; right:auto; top:auto; transform:translateX(-50%); }

        /* Responsive */
        @media (max-width:480px){
          #appAlert.app-alert{ left:12px; right:12px; max-width:none; }
        }
    </style>
    <style>

    /* /spiner/ */
    /* Fondo oscuro de pantalla completa */
    .loader-backdrop {
      position: fixed; inset: 0; z-index: 9999;
      background: rgba(0,0,0,.25);
      display: grid; place-items: center;
    }
    .loader-backdrop.hidden { display: none; }

    /* Caja opcional para texto */
    .loader-box {
      display: grid; gap: 12px; place-items: center;
      padding: 24px 28px; border-radius: 12px;
      background: rgba(30,30,30,.85); color: #fff;
      box-shadow: 0 10px 30px rgba(0,0,0,.4);
    }

    /* Spinner tipo “ring” (como la imagen) */
    .loader-spinner {
      width: 44px; height: 44px;
      border: 4px solid rgba(255,255,255,.25);
      border-top-color: #fff;
      border-radius: 50%;
      animation: spin 0.9s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    .loader-text { font: 14px/1.2 system-ui, -apple-system, Segoe UI, Roboto, sans-serif; opacity: .9; }


    /*fin spiner */
</style>


  <div id="app-loader" class="loader-backdrop hidden" aria-live="polite" aria-busy="true">
    <div class="loader-box">
      <div class="loader-spinner" role="progressbar" aria-label="Cargando…"></div>
      <div class="loader-text" data-loader-text>Cargando…</div>
    </div>
  </div>
</head>

<body>
    <div class="wrapper animsition">
        <!-- main header -->
        <header class="main-header">
            <!-- top navigation -->
            <nav class="navbar top-nav">
                <div class="container">
                    <div class="navbar-header hidden-xs" id="contenedorLogo">
                        <a class="navbar-brand"> <img src="<?php echo base_url() ?>assets/dist/img/19.png" id="logoHeader"></a>

                    </div>
                    <!-- Start Atribute Navigation -->
                    <div class="attr-nav">


                        <ul>
                            <!--	<li class="search"><a href="#"><i class="ti-search"></i></a></li>
                            <li class="side-menu"><a href="#"><i class="ti-align-left"></i></a></li>-->

                        </ul>

                    </div>

                    <!-- End Atribute Navigation -->
                    <!-- /.navbar-header -->
                    <ul class="nav navbar-top-links navbar-right">




                        <!--	<li class="dropdown">
                            <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                                <i class="dropdowm-icon ti-flag-alt"></i>
                                <span class="label label-danger">9</span>
                            </a>
                            <ul class="dropdown-menu dropdown-tasks">
                                <li class="rad-dropmenu-header"><a href="#">Your Task</a></li>
                                <li>
                                    <a href="#">
                                        <div>
                                            <p>
                                                <strong>Task 1</strong>
                                                <span class="pull-right sm-text">40% Complete</span>
                                            </p>
                                            <div class="progress progress-striped active">
                                                <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100" style="width: 40%">
                                                    <span class="sr-only">40% Complete (success)</span>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a href="#">
                                        <div>
                                            <p>
                                                <strong>Task 2</strong>
                                                <span class="pull-right sm-text">20% Complete</span>
                                            </p>
                                            <div class="progress progress-striped active">
                                                <div class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100" style="width: 20%">
                                                    <span class="sr-only">20% Complete</span>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a href="#">
                                        <div>
                                            <p>
                                                <strong>Task 3</strong>
                                                <span class="pull-right sm-text">60% Complete</span>
                                            </p>
                                            <div class="progress progress-striped active">
                                                <div class="progress-bar progress-bar-warning" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 60%">
                                                    <span class="sr-only">60% Complete (warning)</span>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a href="#">
                                        <div>
                                            <p>
                                                <strong>Task 4</strong>
                                                <span class="pull-right sm-text">80% Complete</span>
                                            </p>
                                            <div class="progress progress-striped active">
                                                <div class="progress-bar progress-bar-danger" role="progressbar" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100" style="width: 80%">
                                                    <span class="sr-only">80% Complete (danger)</span>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li class="rad-dropmenu-footer"><a href="#">See All Tasks</a></li>
                            </ul>

                        </li>-->

                        <li class="dropdown">
                            <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                                <i class="dropdowm-icon ti-settings"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-user">
                                <!--
                                <li><a href="profile.html"><i class="ti-user"></i>&nbsp; Perfil</a></li>
                                <li><a href="lockscreen.html"><i class="ti-lock"></i>&nbsp; Bloquear</a></li>
                            -->
                                <?php
                                if (!empty($empresas)) {
                                ?>
                                    <li><a onclick="cambiarEmpresa()" href="#"><i class=" pe-7s-refresh"></i>&nbsp; Cambiar
                                            Empresa</a></li>
                                <?php
                                }
                                ?>
                                <?php if ($this->session->userdata('id_rol') == 1) : ?>
                                    <!--<li><a href="<?php echo base_url() ?>index.php/Usuarios"><i class="ti-user"></i>&nbsp; Usuarios</a></li> -->
                                    <!--<li><a href="<?php echo base_url() ?>index.php/Roles"><i class="ti-user"></i>&nbsp; Roles De Usuarios</a></li>-->
                                    <li><a href="<?php echo base_url() ?>index.php/Configuraciones"><i class="ti-gear"></i>&nbsp; Configuraciones</a></li>
                                <?php endif; ?>
                                <!-- <li><a href="<?php echo base_url() ?>index.php/PerfilDeUsuario"><i
                                            class="ti-user"></i>&nbsp; Perfil De Usuario</a></li>-->
                                <li><a href="<?php echo base_url() ?>index.php/Login/logout_ci"><i class=" pe-7s-power"></i>&nbsp; Cerrar Sesion</a></li>
                                <li class="active"><a href="<?php echo base_url() ?>index.php/ConfiguracionesGenerales"><i class=""></i>Configuraciones</a></li>
                            </ul>
                            <div class="modal fade modal-success" id="modalCambioClienteSucursal" tabindex="-1" role="dialog">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                            <h1 class="modal-title">Cambiar Empresa</h1>
                                        </div>
                                        <div class="modal-body">
                                            <form action="<?php echo base_url() ?>Login/relogin" method="post">
                                                <fieldset>

                                                    <label for="">Empresa</label><br>
                                                    <input style="display:none" type="text" name="username" value="<?php echo $this->session->userdata('correo_acceso') ?>">
                                                    <?php if (!empty($empresas)) { ?>
                                                        <select style="width:80%" onchange="this.form.submit()" name="empresa" class="color9-bg color4" name="">
                                                            <?php
                                                            foreach ($empresas as $v) {
                                                                if ($v['nombre_cliente'] == $this->session->userdata('nombre_cliente')) {
                                                            ?>
                                                                    <option selected value='<?php echo $v['id_cliente']; ?>'>
                                                                        <?php echo $v['nombre_cliente'] . " - " . $v['nombre_fantasia']; ?>
                                                                    </option>
                                                                <?php
                                                                } else {
                                                                ?>
                                                                    <option value='<?php echo $v['id_cliente']; ?>'>
                                                                        <?php echo $v['nombre_cliente'] . " - " . $v['nombre_fantasia']; ?>
                                                                    </option>
                                                            <?php
                                                                }
                                                            } //end foreach
                                                            ?>
                                                        </select>
                                                    <?php
                                                    } ?>

                                                </fieldset>
                                                <fieldset>

                                                    <?php if (!empty($conexiones)) { ?>
                                                        <label for="">Conexión :</label><br>
                                                        <input style="display:none" type="text" name="username" value="<?php echo $this->session->userdata('correo_acceso') ?>">

                                                        <select style="width:80%" onchange="this.form.submit()" name="nConexion" class="color9-bg color4" name="">
                                                            <?php
                                                            foreach ($conexiones as $v) {
                                                                if ($v['nombre_conexion'] == $this->session->userdata('nombre_conexion')) {
                                                            ?>
                                                                    <option selected value='<?php echo $v['nombre_conexion']; ?>'>
                                                                        <?php echo $v['nombre_conexion']; ?>
                                                                    </option>
                                                                <?php
                                                                } else {
                                                                ?>
                                                                    <option value='<?php echo $v['nombre_conexion']; ?>'>
                                                                        <?php echo $v['nombre_conexion']; ?>
                                                                    </option>
                                                            <?php
                                                                }
                                                            } //end foreach
                                                            ?>
                                                        </select>
                                                    <?php
                                                    } ?>

                                                </fieldset>







                                            </form>
                                        </div>
                                        <div class="modal-footer">
                                            <!--	<button type="button" class="btn btn-danger" data-dismiss="modal"><i class="pe-7s-close-circle"></i> Close</button>-->
                                            <button type="button" class=" btn btn-labeled btn-danger m-b-5" data-dismiss="modal"><span class="btn-label">
                                                    <i class="pe-7s-close-circle"></i></span> Cerrar</button>
                                        </div>
                                    </div><!-- /.modal-content -->
                                </div><!-- /.modal-dialog -->
                            </div><!-- /.modal -->




















                            <!-- /.dropdown-user -->
                        </li> <!-- /.dropdown -->
                    </ul> <!-- /.navbar-top-links -->
                </div> <!-- /. container -->
            </nav> <!-- /. top navigation -->
            <!--  main navigation -->
            <nav class="navbar navbar-default navbar-mobile navbar-sticky bootsnav">
                <!-- Start Top Search -->
                <div class="top-search">
                    <div class="container">
                        <div class="input-group">
                            <span class="input-group-addon"><i class="ti-search"></i></span>
                            <input type="text" class="form-control" placeholder="Search">
                            <span class="input-group-addon close-search"><i class="ti-close"></i></span>
                        </div>
                    </div>
                </div>
                <div class="container">
                    <div class="navbar-header">
                        <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navbar-menu">
                            <i class="fa fa-bars"></i>
                        </button>
                        <a class="navbar-brand hidden-md hidden-lg" href="#brand"><img src="<?php echo base_url() ?>assets/dist/img/logo2.png" class="logo" alt=""></a>
                    </div>
                    <div class="collapse navbar-collapse" id="navbar-menu">
                        <ul class="nav navbar-nav navbar-left" data-in="" data-out="">
                            <li class="active"><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="ti-home"></i> Mis Ventas</a></li>

                            <li class="dropdown">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="pe-7s-news-paper"></i>
                                    Procesos</a>
                                <ul class="dropdown-menu">
                                    <li class="dropdown">
                                        <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="pe-7s-news-paper"></i> Compras e Inventario</a>
                                        <ul class="dropdown-menu">
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/EntradaBodega"><i class="ti-money"></i>Entrada a Bodega</a></li>
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/SalidaBodega"><i class="ti-money"></i>Salida a Bodega</a></li>
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/Compras"><i class="ti-money"></i>Compras</a></li>
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/Inventario"><i class="ti-money"></i>Inventario</a></li>
                                        </ul>
                                    </li>                                
                                </ul>
                            </li>

                            <li class="dropdown">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="pe-7s-news-paper"></i> Mantencion</a>
                                <ul class="dropdown-menu">

                                    <!-- <li class="active"><a href="<?php echo base_url() ?>index.php/Cliente"><i class="ti-money"></i>Clientes</a></li>
                                    <li class="active"><a href="<?php echo base_url() ?>index.php/Proveedor"><i class="ti-money"></i>Proveedor</a></li> -->
                                    
                                    <!-- <li class="dropdown">
                                        <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="pe-7s-news-paper"></i> Usuarios</a>
                                        <ul class="dropdown-menu">
                                        </ul>
                                    </li> -->
                                    <li class="active"><a href="<?php echo base_url() ?>index.php/ListaDePrecios"><i class="ti-money"></i>Lista de Precios</a></li>
                                    <li class="active"><a href="<?php echo base_url() ?>index.php/Cliente"><i class="ti-money"></i>Clientes</a></li>
                                    <li class="active"><a href="<?php echo base_url() ?>index.php/Proveedor"><i class="ti-money"></i>Proveedor</a></li>

                                    <li class="dropdown">
                                        <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="pe-7s-news-paper"></i> Tablas</a>
                                        <ul class="dropdown-menu">
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/Agregados"><i class="ti-money"></i>Agregados</a></li>
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/Bodegas"><i class="ti-money"></i>Bodegas</a></li>
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/CanalDeventa"><i class="ti-money"></i>Canal De venta</a></li>
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/CentroCosto"><i class="ti-money"></i>Centro Costo</a></li>
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/Comuna"><i class="ti-money"></i>Comunas</a></li>
                                            <li class="dropdown">
                                                <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="pe-7s-news-paper"></i> Delivery</a>
                                                <ul class="dropdown-menu">
                                                    <li class="active"><a href="<?php echo base_url() ?>index.php/Delivery"><i class="ti-money"></i>Sector</a></li>
                                                </ul>
                                            </li>
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/DestinoDeImpresion"><i class="ti-money"></i>Destino De Impresion</a></li>
                                            <li class="dropdown">
                                                <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="pe-7s-news-paper"></i> Eventos O Presupuestos</a>
                                                <ul class="dropdown-menu">
                                                    <li class="active"><a href="<?php echo base_url() ?>index.php/GastoEventos"><i class="ti-money"></i>Gastos En Eventos</a></li>
                                                    <li class="active"><a href="<?php echo base_url() ?>index.php/IngresoEventos"><i class="ti-money"></i>Ingreso En Eventos</a></li>
                                                    <li class="active"><a href="<?php echo base_url() ?>index.php/TipoEvento"><i class="ti-money"></i>Tipo De Eventos</a></li>
                                                </ul>
                                            </li>
                                            <li class="dropdown">
                                                <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="pe-7s-news-paper"></i> Grupos/Familias</a>
                                                <ul class="dropdown-menu">
                                                    <li class="active"><a href="<?php echo base_url() ?>index.php/Familias"><i class="ti-money"></i>Familias Productos Carta</a></li>
                                                    <li class="active"><a href="<?php echo base_url() ?>index.php/FamiliasInsumos"><i class="ti-money"></i>Familias Insumos</a></li>
                                                    <li class="active"><a href="<?php echo base_url() ?>index.php/FamiliasReceta"><i class="ti-money"></i>Familias Recetas</a></li>
                                                </ul>
                                            </li>

                                            <li class="active"><a href="<?php echo base_url() ?>index.php/FormasDePago"><i class="ti-money"></i>Formas De Pago</a></li>
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/Impuestos"><i class="ti-money"></i>Impuestos</a></li>
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/MensajesPorProductos"><i class="ti-money"></i>Mensajes Por Productos</a></li>
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/Moneda"><i class="ti-money"></i>Moneda</a></li>
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/Provincias"><i class="ti-money"></i>Provincias</a></li>
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/SubFamilias"><i class="ti-money"></i>SubFamilias</a></li>
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/Turnos"><i class="ti-money"></i>Turnos</a></li>
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/Umedida"><i class="ti-money"></i>Unidad De Medida</a></li>
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/ItemGastosMensuales"><i class="ti-money"></i>Item Gastos Mensuales</a></li>
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/Usuario"><i class="ti-money"></i>Usuario</a></li>
                                        </ul>
                                    </li>


                                    <li class="active"><a href="<?php echo base_url() ?>index.php/Insumos"><i class="ti-money"></i>Insumos</a></li>
                                    <li class="active"><a href="<?php echo base_url() ?>index.php/Recetas"><i class="ti-money"></i>Recetas</a></li>


                                    <li class="dropdown">
                                        <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="pe-7s-news-paper"></i> Recintos</a>
                                        <ul class="dropdown-menu">
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/Recintos"><i class="ti-money"></i>Recintos</a></li>
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/MesasPorRecinto"><i class="ti-money"></i>Mesas Por Recinto</a></li>
                                        </ul>
                                    </li>

                                    <li class="active"><a href="<?php echo base_url() ?>index.php/Productos"><i class="ti-money"></i>Productos de la Carta</a></li>




                                    <?php if ($this->session->userdata('id_rol') == 1) : ?>
                                        <!--
                            <li class="dropdown">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i
                                        class="pe-7s-news-paper"></i> Mantenedores</a>
                                <ul class="dropdown-menu">

                                    <li class="active"><a href="<?php echo base_url() ?>index.php/Cliente"><i
                                                class="ti-money"></i>Clientes</a></li>
                                    <li class="active"><a href="<?php echo base_url() ?>index.php/Proveedor"><i
                                                class="ti-money"></i>Proveedor</a></li>
                                    <li class="dropdown">
                                        <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i
                                                class="pe-7s-news-paper"></i> Tablas</a>
                                        <ul class="dropdown-menu">
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/Agregados"><i
                                                        class="ti-money"></i>Agregados</a></li>
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/Bodegas"><i
                                                        class="ti-money"></i>Bodegas</a></li>
                                            <li class="active"><a
                                                    href="<?php echo base_url() ?>index.php/CanalDeventa"><i
                                                        class="ti-money"></i>Canal De venta</a></li>
                                            <li class="active"><a
                                                    href="<?php echo base_url() ?>index.php/CentroCosto"><i
                                                        class="ti-money"></i>Centro Costo</a></li>
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/Comuna"><i
                                                        class="ti-money"></i>Comunas</a></li>
                                            <li class="dropdown">
                                                <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i
                                                        class="pe-7s-news-paper"></i> Delivery</a>
                                                <ul class="dropdown-menu">
                                                    <li class="active"><a
                                                            href="<?php echo base_url() ?>index.php/Delivery"><i
                                                                class="ti-money"></i>Sector</a></li>
                                                </ul>
                                            </li>
                                            <li class="active"><a
                                                    href="<?php echo base_url() ?>index.php/DestinoDeImpresion"><i
                                                        class="ti-money"></i>Destino De Impresion</a></li>
                                            <li class="dropdown">
                                                <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i
                                                        class="pe-7s-news-paper"></i> Eventos O Presupuestos</a>
                                                <ul class="dropdown-menu">
                                                    <li class="active"><a
                                                            href="<?php echo base_url() ?>index.php/GastoEventos"><i
                                                                class="ti-money"></i>Gastos En Eventos</a></li>
                                                    <li class="active"><a
                                                            href="<?php echo base_url() ?>index.php/IngresoEventos"><i
                                                                class="ti-money"></i>Ingreso En Eventos</a></li>
                                                    <li class="active"><a
                                                            href="<?php echo base_url() ?>index.php/TipoEvento"><i
                                                                class="ti-money"></i>Tipo De Eventos</a></li>
                                                </ul>
                                            </li>
                                            <li class="dropdown">
                                                <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i
                                                        class="pe-7s-news-paper"></i> Familias</a>
                                                <ul class="dropdown-menu">
                                                    <li class="active"><a
                                                            href="<?php echo base_url() ?>index.php/Familias"><i
                                                                class="ti-money"></i>Familias Productos Carta</a></li>
                                                    <li class="active"><a
                                                            href="<?php echo base_url() ?>index.php/FamiliasInsumos"><i
                                                                class="ti-money"></i>Familias Insumos</a></li>
                                                    <li class="active"><a
                                                            href="<?php echo base_url() ?>index.php/FamiliasReceta"><i
                                                                class="ti-money"></i>Familias Recetas</a></li>
                                                </ul>
                                            </li>

                                            <li class="active"><a
                                                    href="<?php echo base_url() ?>index.php/FormasDePago"><i
                                                        class="ti-money"></i>Formas De Pago</a></li>
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/Impuestos"><i
                                                        class="ti-money"></i>Impuestos</a></li>
                                            <li class="active"><a
                                                    href="<?php echo base_url() ?>index.php/MensajesPorProductos"><i
                                                        class="ti-money"></i>Mensajes Por Productos</a></li>
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/Moneda"><i
                                                        class="ti-money"></i>Moneda</a></li>
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/Provincias"><i
                                                        class="ti-money"></i>Provincias</a></li>
                                            <li class="active"><a
                                                    href="<?php echo base_url() ?>index.php/SubFamilias"><i
                                                        class="ti-money"></i>SubFamilias</a></li>
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/Turnos"><i
                                                        class="ti-money"></i>Turnos</a></li>
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/Umedida"><i
                                                        class="ti-money"></i>Unidad De Medida</a></li>
                                            <li class="active"><a
                                                    href="<?php echo base_url() ?>index.php/ItemGastosMensuales"><i
                                                        class="ti-money"></i>Item Gastos Mensuales</a></li>
                                        </ul>
                                    </li>




                                    <li class="dropdown">
                                        <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i
                                                class="pe-7s-news-paper"></i> Recintos</a>
                                        <ul class="dropdown-menu">
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/Recintos"><i
                                                        class="ti-money"></i>Recintos</a></li>
                                        </ul>
                                    </li>-->
                                    <?php endif; ?>


                                </ul>
                            </li>

                            <li class="dropdown">

                                <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="pe-7s-news-paper"></i> Consultas</a>
                                <ul class="dropdown-menu">
                                    <li><a href="<?php echo base_url() ?>index.php/CierresDeCaja"><i class="ti-clipboard"></i>Cierres de Caja</a></li>
                                    <li><a href="<?php echo base_url() ?>index.php/InsumosPorBodega"><i class="ti-alert"></i>Stock Por Bodega</a></li>
                                    <li><a href="<?php echo base_url() ?>index.php/ResumenDeCompras"><i class="fa fa-crop"></i>Resumen De Compras</a></li>
                                    <li><a href="<?php echo base_url() ?>index.php/ConsolidadoVentas"><i class="fa fa-crop"></i>Consolidado De Ventas</a></li>
                                    <li><a href="<?php echo base_url() ?>index.php/DetalleOperaciones"><i class="pe-7s-cash"></i>Detalle De Operaciones</a></li>
                                    <li><a href="<?php echo base_url() ?>index.php/VentasPorProducto"><i class="pe-7s-cash"></i>Ventas Por Producto</a></li>
                                    <li><a href="<?php echo base_url() ?>index.php/PropinasPorGarzon"><i class="pe-7s-cash"></i>Propinas Por Garzon</a></li>
                                    <li><a href="<?php echo base_url() ?>index.php/ResumenAnulaciones"><i class="pe-7s-cash"></i>Resumen De Anulaciones/Eliminaciones</a></li>
                                    <li><a href="<?php echo base_url() ?>index.php/ListadoDeClientes"><i class="pe-7s-cash"></i>Listado De Clientes</a></li>
                                    <li><a href="<?php echo base_url() ?>index.php/VentasMensualesTotalizadas"><i class="pe-7s-cash"></i>Resumen Ventas Mensuales</a></li>
                                    <li><a href="<?php echo base_url() ?>index.php/ResumenVentasProductosMes"><i class="pe-7s-cash"></i>Resumen De Venta de Productos Mensuales</a></li>
                                    <li class="dropdown">
                                        <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="pe-7s-news-paper"></i> Movimientos De Bodega</a>
                                        <ul class="dropdown-menu">
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/ResumenEntradaBodega"><i class="ti-money"></i>Entrada De Stock</a></li>
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/ResumenSalidaBodega"><i class="ti-money"></i>Salida De Stock</a></li>
                                        </ul>
                                    </li>
                                </ul>

                            
                            
                            <li class="active"><a href="<?php echo base_url() ?>index.php/Productos"><i class="ti-money"></i>Carta</a></li>
                            <li class="active"><a href="<?php echo base_url() ?>index.php/Cliente"><i class="ti-money"></i>Clientes</a></li>
                            <li class="active"><a href="<?php echo base_url() ?>index.php/Proveedor"><i class="ti-money"></i>Proveedor</a></li>
                            <!-- <li class="active"><a href="<?php echo base_url() ?>index.php/Insumos"><i class="ti-money"></i>Insumos</a></li>
                            <li class="active"><a href="<?php echo base_url() ?>index.php/Recetas"><i class="ti-money"></i>Recetas</a></li> -->
                            <li class="active"><a href="<?php echo base_url() ?>index.php/Compras"><i class="ti-money"></i>Compras</a></li>
                            <li class="active"><a href="<?php echo base_url() ?>index.php/ListaDePrecios"><i class="ti-money"></i>Precios</a></li>
                            <!--<li class="active"><a href="<?php echo base_url() ?>index.php/Usuarios"><i class="ti-user"></i>Usuarios</a></li>-->

                            <li class="dropdown">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="pe-7s-news-paper"></i>
                                    Herramientas</a>
                                <ul class="dropdown-menu">
                                    <li class="active"><a href="<?php echo base_url() ?>index.php/PrivilegiosUsuario"><i class="ti-money"></i>Privilegios</a></li>
                                    <li class="active"><a href="<?php echo base_url() ?>index.php/Mesas"><i class="ti-money"></i>Mesas</a></li>
                                </ul>
                            </li>


                            <!-- <li class="dropdown">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="pe-7s-news-paper"></i>
                                    Stock</a>
                                <ul class="dropdown-menu">
                                    <li class="active"><a href="<?php echo base_url() ?>index.php/EntradaBodega"><i class="ti-money"></i>Entrada Bodega</a></li>
                                    <li class="active"><a href="<?php echo base_url() ?>index.php/SalidaBodega"><i class="ti-money"></i>Salida Bodega</a></li>


                                </ul>
                            </li> -->



                            <?php if ($this->session->userdata('id_rol') == 1) : ?>
                                <!--
                            <li class="active"><a href="<?php echo base_url() ?>index.php/Productos"><i
                                        class="ti-money"></i>Carta</a></li>
                            <li class="active"><a href="<?php echo base_url() ?>index.php/Insumos"><i
                                        class="ti-money"></i>Insumos</a></li>
                            <li class="active"><a href="<?php echo base_url() ?>index.php/Recetas"><i
                                        class="ti-money"></i>Recetas</a></li>
                            <li class="active"><a href="<?php echo base_url() ?>index.php/ListaDePrecios"><i
                                        class="ti-money"></i>Lista De Precios</a></li>
                            <li class="active"><a href="<?php echo base_url() ?>index.php/Compras"><i
                                        class="ti-money"></i>Compras</a></li>-->
                            <?php endif; ?>
                        </ul>
                    </div>
                    <!-- /.navbar-collapse -->
                </div>

            </nav> <!-- /. main navigation -->
            <div class="clearfix"></div>
            <script type="text/javascript">
                function cambiarEmpresa() {
                    $('#modalCambioClienteSucursal').modal({
                        backdrop: 'static',
                        keyboard: false
                    });
                }
            </script>

        </header> <!-- /. main header -->


        <!-- /.content-wrapper -->
        <div class="content-wrapper">
            <div class="container">
                <!-- main content -->
                <div class="content">

        <div id="appAlert" class="app-alert" role="alert" aria-live="polite" aria-hidden="true">
        <button class="app-alert__close" aria-label="Cerrar notificación">&times;</button>
      <i id="appAlertIcon" class="fa" aria-hidden="true" style="display:none"></i>
      <img id="appAlertImg" class="app-alert__img" alt="" style="display:none">
      <div class="app-alert__content">
        <h4 id="appAlertTitle"></h4>
        <span id="appAlertBody"></span>
      </div>
    </div>