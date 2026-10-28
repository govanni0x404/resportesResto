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
    <link href="<?php echo base_url() ?>assets/dist/css/theme.css?v=23" rel="stylesheet" type="text/css" />
    <link href="<?php echo base_url() ?>assets/dist/css/tema-f.css?v=1" rel="stylesheet" type="text/css" />
    <style>
        .container {
            width: 95% !important;
            max-width: 95% !important;
            padding-left: 15px !important;
            padding-right: 15px !important;
        }
        .content-header {
            margin: 0 auto 0px !important;
        }

        @media (max-width: 991px) {
            .container {
                width: auto !important;
                max-width: none !important;
                padding-left: 15px !important;
                padding-right: 15px !important;
            }
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

    /* Fix scrolling for dropdown menus */
    /* .navbar-top-links > .dropdown > .dropdown-menu {
      max-height: 80vh;
      overflow-y: auto;
      overflow-x: visible;
      position: relative;
      z-index: 1000;
    } */

    /* Scrollable nested dropdowns */
    .dropdown-menu .dropdown-menu {
      max-height: 70vh;
      overflow-y: auto;
      overflow-x: visible;
      position: absolute;
      z-index: 1001;
    }
    
    /* Ensure dropdown items don't clip their children */
    .dropdown-menu .dropdown {
      position: relative;
      overflow: visible;
    }

    /* Responsive adjustments for tablets */
    @media (max-width: 768px) {
      .navbar-top-links > .dropdown > .dropdown-menu {
        position: fixed !important;
        top: 56px;
        left: 10px !important;
        right: 10px !important;
        margin-top: 0;
        max-height: calc(100vh - 70px) !important;
        overflow-y: auto !important;
        overflow-x: hidden !important;
      }

      .navbar-top-links .dropdown-user {
        width: auto !important;
        min-width: 0 !important;
        box-sizing: border-box;
      }

      .navbar-top-links .dropdown-user::before {
        display: none;
      }

      .dropdown-menu .dropdown-menu {
        max-height: none;
        position: static !important;
        min-width: 100%;
        width: 100%;
        overflow: visible;
        border: 0;
        box-shadow: none;
        padding-left: 12px;
      }

      .dropdown-menu .dropdown:hover > .dropdown-menu {
        display: none !important;
      }

      .dropdown-menu .dropdown.open > .dropdown-menu {
        display: block !important;
      }
    }

    /* Responsive adjustments for small phones */
    @media (max-width: 480px) {
      .navbar-top-links > .dropdown > .dropdown-menu {
        top: 52px;
        left: 8px !important;
        right: 8px !important;
        max-height: calc(100vh - 64px) !important;
      }

      .navbar-top-links .dropdown-user {
        width: auto !important;
      }
    }

    /* Estilos para dropdowns anidados */
      .dropdown-menu .dropdown .dropdown-menu {
        display: none !important;
        position: fixed;
        left: auto;
        top: auto;
        margin: 0;
        min-width: 250px;
        z-index: 2000;
        background: white;
        border: 1px solid #ddd;
        box-shadow: 0 2px 10px rgba(0,0,0,0.15);
      }

    .dropdown-menu .dropdown:hover > .dropdown-menu {
      display: block !important;
    }

    .dropdown-menu .dropdown.open > .dropdown-menu {
      display: block !important;
    }

    .dropdown-menu .dropdown > a {
      position: relative;
      padding-right: 20px;
    }

    .dropdown-menu .dropdown > a::after {
      content: '›';
      position: absolute;
      right: 5px;
      font-size: 18px;
    }

    .navbar-top-links > .dropdown {
    position: relative;
    }

      @media (min-width: 769px) {
        .navbar-top-links > .dropdown > .dropdown-menu {
        position: absolute !important;
        top: 100%;
        right: 0;
        left: auto;
        margin-top: 0;
        max-height: none !important;
        overflow: visible !important;
        }
      }
</style>


  <div id="app-loader" class="loader-backdrop hidden" aria-live="polite" aria-busy="true">
    <div class="loader-box">
      <div class="loader-spinner" role="progressbar" aria-label="Cargando…"></div>
      <div class="loader-text" data-loader-text>Cargando…</div>
    </div>
  </div>
  
  <script>
      // Manejar dropdowns anidados dentro de menús
    document.addEventListener('DOMContentLoaded', function() {
      const nestedDropdowns = document.querySelectorAll('.dropdown-menu .dropdown > a.dropdown-toggle');

        function isMobileViewport() {
          return window.innerWidth <= 768;
        }

        function resetSubmenu(menu) {
          menu.style.top = '';
          menu.style.left = '';
        }
      
      nestedDropdowns.forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          
          const li = this.closest('.dropdown');
          const menu = li.querySelector('.dropdown-menu');
          
          if (menu) {
            const isOpen = li.classList.contains('open');
            // Cerrar otros dropdowns al mismo nivel
            const siblings = li.parentElement.querySelectorAll('.dropdown.open');
            siblings.forEach(function(sibling) {
              sibling.classList.remove('open');
            });
            // Toggle del actual
            if (!isOpen) {
              li.classList.add('open');
                if (!isMobileViewport()) {
                  posicionarSubmenu(li, menu);
                } else {
                  resetSubmenu(menu);
                }
            } else {
              li.classList.remove('open');
                resetSubmenu(menu);
            }
          }
        });
        
          if (!isMobileViewport()) {
            toggle.closest('.dropdown').addEventListener('mouseenter', function() {
              const parent = toggle.closest('.dropdown-menu');
              if (parent) {
                const siblings = parent.querySelectorAll('.dropdown.open');
                siblings.forEach(function(sibling) {
                  if (sibling !== this) {
                    sibling.classList.remove('open');
                  }
                }.bind(this));
              }
            });
          }
      });
      
      function posicionarSubmenu(parentLi, menu) {
        setTimeout(function() {
          const rect = parentLi.getBoundingClientRect();
          menu.style.top = rect.top + 'px';
          menu.style.left = (rect.right) + 'px';
        }, 0);
      }

        document.addEventListener('click', function(e) {
          if (!e.target.closest('.dropdown-menu')) {
            document.querySelectorAll('.dropdown-menu .dropdown.open').forEach(function(item) {
              item.classList.remove('open');
              const menu = item.querySelector('.dropdown-menu');
              if (menu) {
                resetSubmenu(menu);
              }
            });
          }
        });

        window.addEventListener('resize', function() {
          if (isMobileViewport()) {
            document.querySelectorAll('.dropdown-menu .dropdown .dropdown-menu').forEach(function(menu) {
              resetSubmenu(menu);
            });
          }
        });
    });
  </script>
</head>

<body>
    <!-- Aviso de carga entre secciones: visible desde el inicio, menuFooter lo oculta al terminar de cargar -->
    <div id="page-loader" class="page-loader" role="status" aria-live="polite">
        <div class="page-loader-box">
            <div class="page-loader-spinner"></div>
            <div class="page-loader-text">Cargando…</div>
        </div>
    </div>
    <?php
    $__menuAccesos = array();
    $__rolId = (int)$this->session->userdata('id_rol');

    try {
        $this->load->helper('tenant'); $this->db = get_tenant_db($this);

        $q = "SELECT ps.nombre_vb, COALESCE(pu.autoriza, false) as autoriza
              FROM psistema ps
              LEFT JOIN pusuario pu 
                ON pu.id_psistema = ps.id_psistema 
               AND pu.id_rol = $__rolId
              WHERE ps.vigente = true
                AND ps.nombre_psistema = 'RESTO WEB'";
        $rows = $this->db->query($q)->result();
        foreach ($rows as $r) {
            $__menuAccesos[strtolower(trim($r->nombre_vb))] = ($r->autoriza === 't' || $r->autoriza === true);
        }
    } catch (Exception $e) {
        $__menuAccesos = array();
    }

    // La vista se incluye dentro de un método de CI_Loader: sus variables no son globales,
    // por eso se publican en $GLOBALS para que menu_visible() las lea.
    $GLOBALS['__menuAccesos'] = $__menuAccesos;
    $GLOBALS['__menuEsAdmin'] = ($__rolId === 1);

    if (!function_exists('menu_visible')) {
        function menu_visible($nombreVb)
        {
            global $__menuAccesos, $__menuEsAdmin;
            // Gerencia (rol 1) ve todo, para que nunca quede sin acceso a Privilegios de Usuario
            if ($__menuEsAdmin) {
                return true;
            }
            $k = strtolower(trim((string)$nombreVb));
            if (!is_array($__menuAccesos) || !array_key_exists($k, $__menuAccesos)) {
                return true;
            }
            return $__menuAccesos[$k] === true;
        }
    }
    ?>
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
                                <?php if ($this->session->userdata('id_rol') == 1 && menu_visible('Configuraciones')) : ?>
                                    <li class="active"><a href="<?php echo base_url() ?>index.php/Configuraciones"><i class=""></i>Configuraciones</a></li>
                                <?php endif; ?>
                                <li><a href="<?php echo base_url() ?>index.php/Login/logout_ci"><i class=" pe-7s-power"></i>&nbsp; Cerrar Sesion</a></li>
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
                    </div>
                    <div class="collapse navbar-collapse" id="navbar-menu">
                        <?php
                        $__showDashboard = menu_visible('Dashboard');
                        $__showCaja = menu_visible('Caja');

                        $__showEntradaBodega = menu_visible('EntradaBodega');
                        $__showSalidaBodega = menu_visible('SalidaBodega');
                        $__showCompras = menu_visible('Compras');
                        $__showInventario = menu_visible('Inventario');
                        $__showProcesosComprasInv = $__showEntradaBodega || $__showSalidaBodega || $__showCompras || $__showInventario;
                        $__showProcesos = $__showProcesosComprasInv;

                        $__showListaDePrecios = menu_visible('ListaDePrecios');
                        $__showCliente = menu_visible('Cliente');
                        $__showProveedor = menu_visible('Proveedor');

                        $__showAgregados = menu_visible('Agregados');
                        $__showBodegas = menu_visible('Bodegas');
                        $__showCanalDeventa = menu_visible('CanalDeventa');
                        $__showCentroCosto = menu_visible('CentroCosto');
                        $__showComuna = menu_visible('Comuna');
                        $__showDelivery = menu_visible('Delivery');
                        $__showDestinoDeImpresion = menu_visible('DestinoDeImpresion');
                        $__showGastoEventos = menu_visible('GastoEventos');
                        $__showIngresoEventos = menu_visible('IngresoEventos');
                        $__showTipoEvento = menu_visible('TipoEvento');
                        $__showFamilias = menu_visible('Familias');
                        $__showFamiliasInsumos = menu_visible('FamiliasInsumos');
                        $__showFamiliasReceta = menu_visible('FamiliasReceta');
                        $__showFormasDePago = menu_visible('FormasDePago');
                        $__showImpuestos = menu_visible('Impuestos');
                        $__showMensajesPorProductos = menu_visible('MensajesPorProductos');
                        $__showMoneda = menu_visible('Moneda');
                        $__showProvincias = menu_visible('Provincias');
                        $__showSubFamilias = menu_visible('SubFamilias');
                        $__showTurnos = menu_visible('Turnos');
                        $__showUmedida = menu_visible('Umedida');
                        $__showItemGastosMensuales = menu_visible('ItemGastosMensuales');
                        $__showUsuario = menu_visible('Usuario');

                        $__showTablasDelivery = $__showDelivery;
                        $__showTablasEventos = $__showGastoEventos || $__showIngresoEventos || $__showTipoEvento;
                        $__showTablasGruposFamilias = $__showFamilias || $__showFamiliasInsumos || $__showFamiliasReceta;
                        $__showTablas = $__showAgregados || $__showBodegas || $__showCanalDeventa || $__showCentroCosto || $__showComuna || $__showTablasDelivery || $__showDestinoDeImpresion || $__showTablasEventos || $__showTablasGruposFamilias || $__showFormasDePago || $__showImpuestos || $__showMensajesPorProductos || $__showMoneda || $__showProvincias || $__showSubFamilias || $__showTurnos || $__showUmedida || $__showItemGastosMensuales || $__showUsuario;

                        $__showInsumos = menu_visible('Insumos');
                        $__showRecetas = menu_visible('Recetas');
                        $__showProductos = menu_visible('Productos');
                        $__showRecintos = menu_visible('Recintos');
                        $__showMesasPorRecinto = menu_visible('MesasPorRecinto');
                        $__showRecintosGrupo = $__showRecintos || $__showMesasPorRecinto;

                        $__showMantencion = $__showListaDePrecios || $__showCliente || $__showProveedor || $__showTablas || $__showInsumos || $__showRecetas || $__showRecintosGrupo || $__showProductos;

                        $__showInsumosPorBodega = menu_visible('InsumosPorBodega');
                        $__showDetalleOperaciones = menu_visible('DetalleOperaciones');
                        $__showCierresDeCaja = menu_visible('CierresDeCaja');
                        $__showResumenDeCompras = menu_visible('ResumenDeCompras');
                        $__showConsolidadoVentas = menu_visible('ConsolidadoVentas');
                        $__showVentasPorProducto = menu_visible('VentasPorProducto');
                        $__showPropinasPorGarzon = menu_visible('PropinasPorGarzon');
                        $__showResumenAnulaciones = menu_visible('ResumenAnulaciones');
                        $__showListadoDeClientes = menu_visible('ListadoDeClientes');
                        $__showVentasMensualesTotalizadas = menu_visible('VentasMensualesTotalizadas');
                        $__showResumenVentasProductosMes = menu_visible('ResumenVentasProductosMes');
                        $__showResumenEntradaBodega = menu_visible('ResumenEntradaBodega');
                        $__showResumenSalidaBodega = menu_visible('ResumenSalidaBodega');
                        $__showMovimientosBodega = $__showResumenEntradaBodega || $__showResumenSalidaBodega;

                        $__showConsultas = $__showInsumosPorBodega || $__showDetalleOperaciones || $__showCierresDeCaja || $__showResumenDeCompras || $__showConsolidadoVentas || $__showVentasPorProducto || $__showPropinasPorGarzon || $__showResumenAnulaciones || $__showListadoDeClientes || $__showVentasMensualesTotalizadas || $__showResumenVentasProductosMes || $__showMovimientosBodega;

                        $__isAdmin = ((int)$this->session->userdata('id_rol') === 1);
                        $__showPrivilegiosUsuario = $__isAdmin || menu_visible('PrivilegiosUsuario');
                        $__showMesas = $__isAdmin || menu_visible('Mesas');
                        $__showHerramientas = $__showPrivilegiosUsuario || $__showMesas;

                        ?>
                        <ul class="nav navbar-nav navbar-left" data-in="" data-out="">
                            <?php if ($__showDashboard) : ?>
                                <li class="active"><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="ti-home"></i> Mis Ventas</a></li>
                            <?php endif; ?>

                            <?php if ($__showCaja) : ?>
                                <li><a href="<?php echo base_url() ?>index.php/Caja"><i class="pe-7s-cart"></i> Caja</a></li>
                            <?php endif; ?>

                            <?php if ($__showProcesos) : ?>
                                <li class="dropdown">
                                    <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="pe-7s-news-paper"></i>
                                        Procesos</a>
                                    <ul class="dropdown-menu">
                                        <?php if ($__showProcesosComprasInv) : ?>
                                            <li class="dropdown">
                                                <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="pe-7s-news-paper"></i> Compras e Inventario</a>
                                                <ul class="dropdown-menu">
                                                    <?php if ($__showEntradaBodega) : ?>
                                                        <li class="active"><a href="<?php echo base_url() ?>index.php/EntradaBodega"><i class="ti-money"></i>Entrada a Bodega</a></li>
                                                    <?php endif; ?>
                                                    <?php if ($__showSalidaBodega) : ?>
                                                        <li class="active"><a href="<?php echo base_url() ?>index.php/SalidaBodega"><i class="ti-money"></i>Salida a Bodega</a></li>
                                                    <?php endif; ?>
                                                    <?php if ($__showCompras) : ?>
                                                        <li class="active"><a href="<?php echo base_url() ?>index.php/Compras"><i class="ti-money"></i>Compras</a></li>
                                                    <?php endif; ?>
                                                    <?php if ($__showInventario) : ?>
                                                        <li class="active"><a href="<?php echo base_url() ?>index.php/Inventario"><i class="ti-money"></i>Inventario</a></li>
                                                    <?php endif; ?>
                                                </ul>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </li>
                            <?php endif; ?>

                            <?php if ($__showMantencion) : ?>
                                <li class="dropdown">
                                    <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="pe-7s-news-paper"></i> Mantencion</a>
                                    <ul class="dropdown-menu">

                                        <?php if ($__showListaDePrecios) : ?>
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/ListaDePrecios"><i class="ti-money"></i>Lista de Precios</a></li>
                                        <?php endif; ?>
                                        <?php if ($__showCliente) : ?>
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/Cliente"><i class="ti-money"></i>Clientes</a></li>
                                        <?php endif; ?>
                                        <?php if ($__showProveedor) : ?>
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/Proveedor"><i class="ti-money"></i>Proveedor</a></li>
                                        <?php endif; ?>

                                        <?php if ($__showTablas) : ?>
                                            <li class="dropdown">
                                                <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="pe-7s-news-paper"></i> Tablas</a>
                                                <ul class="dropdown-menu">
                                                    <?php if ($__showAgregados) : ?>
                                                        <li class="active"><a href="<?php echo base_url() ?>index.php/Agregados"><i class="ti-money"></i>Agregados</a></li>
                                                    <?php endif; ?>
                                                    <?php if ($__showBodegas) : ?>
                                                        <li class="active"><a href="<?php echo base_url() ?>index.php/Bodegas"><i class="ti-money"></i>Bodegas</a></li>
                                                    <?php endif; ?>
                                                    <?php if ($__showCanalDeventa) : ?>
                                                        <li class="active"><a href="<?php echo base_url() ?>index.php/CanalDeventa"><i class="ti-money"></i>Canal De venta</a></li>
                                                    <?php endif; ?>
                                                    <?php if ($__showCentroCosto) : ?>
                                                        <li class="active"><a href="<?php echo base_url() ?>index.php/CentroCosto"><i class="ti-money"></i>Centro Costo</a></li>
                                                    <?php endif; ?>
                                                    <?php if ($__showComuna) : ?>
                                                        <li class="active"><a href="<?php echo base_url() ?>index.php/Comuna"><i class="ti-money"></i>Comunas</a></li>
                                                    <?php endif; ?>
                                                    <?php if ($__showTablasDelivery) : ?>
                                                        <li class="dropdown">
                                                            <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="pe-7s-news-paper"></i> Delivery</a>
                                                            <ul class="dropdown-menu">
                                                                <?php if ($__showDelivery) : ?>
                                                                    <li class="active"><a href="<?php echo base_url() ?>index.php/Delivery"><i class="ti-money"></i>Sector</a></li>
                                                                <?php endif; ?>
                                                            </ul>
                                                        </li>
                                                    <?php endif; ?>
                                                    <?php if ($__showDestinoDeImpresion) : ?>
                                                        <li class="active"><a href="<?php echo base_url() ?>index.php/DestinoDeImpresion"><i class="ti-money"></i>Destino De Impresion</a></li>
                                                    <?php endif; ?>
                                                    <?php if ($__showTablasEventos) : ?>
                                                        <li class="dropdown">
                                                            <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="pe-7s-news-paper"></i> Eventos O Presupuestos</a>
                                                            <ul class="dropdown-menu">
                                                                <?php if ($__showGastoEventos) : ?>
                                                                    <li class="active"><a href="<?php echo base_url() ?>index.php/GastoEventos"><i class="ti-money"></i>Gastos En Eventos</a></li>
                                                                <?php endif; ?>
                                                                <?php if ($__showIngresoEventos) : ?>
                                                                    <li class="active"><a href="<?php echo base_url() ?>index.php/IngresoEventos"><i class="ti-money"></i>Ingreso En Eventos</a></li>
                                                                <?php endif; ?>
                                                                <?php if ($__showTipoEvento) : ?>
                                                                    <li class="active"><a href="<?php echo base_url() ?>index.php/TipoEvento"><i class="ti-money"></i>Tipo De Eventos</a></li>
                                                                <?php endif; ?>
                                                            </ul>
                                                        </li>
                                                    <?php endif; ?>
                                                    <?php if ($__showTablasGruposFamilias) : ?>
                                                        <li class="dropdown">
                                                            <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="pe-7s-news-paper"></i> Grupos/Familias</a>
                                                            <ul class="dropdown-menu">
                                                                <?php if ($__showFamilias) : ?>
                                                                    <li class="active"><a href="<?php echo base_url() ?>index.php/Familias"><i class="ti-money"></i>Familias Productos Carta</a></li>
                                                                <?php endif; ?>
                                                                <?php if ($__showFamiliasInsumos) : ?>
                                                                    <li class="active"><a href="<?php echo base_url() ?>index.php/FamiliasInsumos"><i class="ti-money"></i>Familias Insumos</a></li>
                                                                <?php endif; ?>
                                                                <?php if ($__showFamiliasReceta) : ?>
                                                                    <li class="active"><a href="<?php echo base_url() ?>index.php/FamiliasReceta"><i class="ti-money"></i>Familias Recetas</a></li>
                                                                <?php endif; ?>
                                                            </ul>
                                                        </li>
                                                    <?php endif; ?>
                                                    <?php if ($__showFormasDePago) : ?>
                                                        <li class="active"><a href="<?php echo base_url() ?>index.php/FormasDePago"><i class="ti-money"></i>Formas De Pago</a></li>
                                                    <?php endif; ?>
                                                    <?php if ($__showImpuestos) : ?>
                                                        <li class="active"><a href="<?php echo base_url() ?>index.php/Impuestos"><i class="ti-money"></i>Impuestos</a></li>
                                                    <?php endif; ?>
                                                    <?php if ($__showMensajesPorProductos) : ?>
                                                        <li class="active"><a href="<?php echo base_url() ?>index.php/MensajesPorProductos"><i class="ti-money"></i>Mensajes Por Productos</a></li>
                                                    <?php endif; ?>
                                                    <?php if ($__showMoneda) : ?>
                                                        <li class="active"><a href="<?php echo base_url() ?>index.php/Moneda"><i class="ti-money"></i>Moneda</a></li>
                                                    <?php endif; ?>
                                                    <?php if ($__showProvincias) : ?>
                                                        <li class="active"><a href="<?php echo base_url() ?>index.php/Provincias"><i class="ti-money"></i>Provincias</a></li>
                                                    <?php endif; ?>
                                                    <?php if ($__showSubFamilias) : ?>
                                                        <li class="active"><a href="<?php echo base_url() ?>index.php/SubFamilias"><i class="ti-money"></i>SubFamilias</a></li>
                                                    <?php endif; ?>
                                                    <?php if ($__showTurnos) : ?>
                                                        <li class="active"><a href="<?php echo base_url() ?>index.php/Turnos"><i class="ti-money"></i>Turnos</a></li>
                                                    <?php endif; ?>
                                                    <?php if ($__showUmedida) : ?>
                                                        <li class="active"><a href="<?php echo base_url() ?>index.php/Umedida"><i class="ti-money"></i>Unidad De Medida</a></li>
                                                    <?php endif; ?>
                                                    <?php if ($__showItemGastosMensuales) : ?>
                                                        <li class="active"><a href="<?php echo base_url() ?>index.php/ItemGastosMensuales"><i class="ti-money"></i>Item Gastos Mensuales</a></li>
                                                    <?php endif; ?>
                                                    <?php if ($__showUsuario) : ?>
                                                        <li class="active"><a href="<?php echo base_url() ?>index.php/Usuario"><i class="ti-money"></i>Usuario</a></li>
                                                    <?php endif; ?>
                                                </ul>
                                            </li>
                                        <?php endif; ?>

                                        <?php if ($__showInsumos) : ?>
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/Insumos"><i class="ti-money"></i>Insumos</a></li>
                                        <?php endif; ?>
                                        <?php if ($__showRecetas) : ?>
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/Recetas"><i class="ti-money"></i>Recetas</a></li>
                                        <?php endif; ?>


                                        <?php if ($__showRecintosGrupo) : ?>
                                            <li class="dropdown">
                                                <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="pe-7s-news-paper"></i> Recintos</a>
                                                <ul class="dropdown-menu">
                                                    <?php if ($__showRecintos) : ?>
                                                        <li class="active"><a href="<?php echo base_url() ?>index.php/Recintos"><i class="ti-money"></i>Recintos</a></li>
                                                    <?php endif; ?>
                                                    <?php if ($__showMesasPorRecinto) : ?>
                                                        <li class="active"><a href="<?php echo base_url() ?>index.php/MesasPorRecinto"><i class="ti-money"></i>Mesas Por Recinto</a></li>
                                                    <?php endif; ?>
                                                </ul>
                                            </li>
                                        <?php endif; ?>

                                        <?php if ($__showProductos) : ?>
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/Productos"><i class="ti-money"></i>Productos de la Carta</a></li>
                                        <?php endif; ?>




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
                            <?php endif; ?>

                            <?php if ($__showConsultas) : ?>
                                <li class="dropdown">

                                    <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="pe-7s-news-paper"></i> Consultas</a>
                                    <ul class="dropdown-menu">
                                        <?php if ($__showInsumosPorBodega) : ?>
                                            <li><a href="<?php echo base_url() ?>index.php/InsumosPorBodega"><i class="ti-alert"></i>Stock de Insumos</a></li>
                                        <?php endif; ?>
                                        <?php if ($__showDetalleOperaciones) : ?>
                                            <li><a href="<?php echo base_url() ?>index.php/DetalleOperaciones"><i class="pe-7s-cash"></i>Detalle De Ventas</a></li>
                                        <?php endif; ?>
                                        <?php if ($__showCierresDeCaja) : ?>
                                            <li><a href="<?php echo base_url() ?>index.php/CierresDeCaja"><i class="ti-clipboard"></i>Cierres de Caja</a></li>
                                        <?php endif; ?>
                                        <?php if ($__showResumenDeCompras) : ?>
                                            <li><a href="<?php echo base_url() ?>index.php/ResumenDeCompras"><i class="fa fa-crop"></i>Resumen De Compras</a></li>
                                        <?php endif; ?>
                                        <?php if ($__showConsolidadoVentas) : ?>
                                            <li><a href="<?php echo base_url() ?>index.php/ConsolidadoVentas"><i class="fa fa-crop"></i>Consolidado De Ventas</a></li>
                                        <?php endif; ?>
                                        <?php if ($__showVentasPorProducto) : ?>
                                            <li><a href="<?php echo base_url() ?>index.php/VentasPorProducto"><i class="pe-7s-cash"></i>Ventas Por Producto</a></li>
                                        <?php endif; ?>
                                        <?php if ($__showPropinasPorGarzon) : ?>
                                            <li><a href="<?php echo base_url() ?>index.php/PropinasPorGarzon"><i class="pe-7s-cash"></i>Propinas Por Garzon</a></li>
                                        <?php endif; ?>
                                        <?php if ($__showResumenAnulaciones) : ?>
                                            <li><a href="<?php echo base_url() ?>index.php/ResumenAnulaciones"><i class="pe-7s-cash"></i>Resumen De Anulaciones/Eliminaciones</a></li>
                                        <?php endif; ?>
                                        <?php if ($__showListadoDeClientes) : ?>
                                            <li><a href="<?php echo base_url() ?>index.php/ListadoDeClientes"><i class="pe-7s-cash"></i>Listado De Clientes</a></li>
                                        <?php endif; ?>
                                        <?php if ($__showVentasMensualesTotalizadas) : ?>
                                            <li><a href="<?php echo base_url() ?>index.php/VentasMensualesTotalizadas"><i class="pe-7s-cash"></i>Resumen Ventas Mensuales</a></li>
                                        <?php endif; ?>
                                        <?php if ($__showResumenVentasProductosMes) : ?>
                                            <li><a href="<?php echo base_url() ?>index.php/ResumenVentasProductosMes"><i class="pe-7s-cash"></i>Resumen De Venta de Productos Mensuales</a></li>
                                        <?php endif; ?>
                                        <?php if ($__showMovimientosBodega) : ?>
                                            <li class="dropdown">
                                                <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="pe-7s-news-paper"></i> Movimientos De Bodega</a>
                                                <ul class="dropdown-menu">
                                                    <?php if ($__showResumenEntradaBodega) : ?>
                                                        <li class="active"><a href="<?php echo base_url() ?>index.php/ResumenEntradaBodega"><i class="ti-money"></i>Entrada De Stock</a></li>
                                                    <?php endif; ?>
                                                    <?php if ($__showResumenSalidaBodega) : ?>
                                                        <li class="active"><a href="<?php echo base_url() ?>index.php/ResumenSalidaBodega"><i class="ti-money"></i>Salida De Stock</a></li>
                                                    <?php endif; ?>
                                                </ul>
                                            </li>
                                        <?php endif; ?>
                                    </ul>

                            
                            
                                </li>
                            <?php endif; ?>

                            <?php if ($__showProductos) : ?>
                                <li class="active"><a href="<?php echo base_url() ?>index.php/Productos"><i class="ti-money"></i>Carta</a></li>
                            <?php endif; ?>
                            <?php if ($__showCliente) : ?>
                                <li class="active"><a href="<?php echo base_url() ?>index.php/Cliente"><i class="ti-money"></i>Clientes</a></li>
                            <?php endif; ?>
                            <?php if ($__showProveedor) : ?>
                                <li class="active"><a href="<?php echo base_url() ?>index.php/Proveedor"><i class="ti-money"></i>Proveedor</a></li>
                            <?php endif; ?>
                            <!-- <li class="active"><a href="<?php echo base_url() ?>index.php/Insumos"><i class="ti-money"></i>Insumos</a></li>
                            <li class="active"><a href="<?php echo base_url() ?>index.php/Recetas"><i class="ti-money"></i>Recetas</a></li> -->
                            <?php if ($__showCompras) : ?>
                                <li class="active"><a href="<?php echo base_url() ?>index.php/Compras"><i class="ti-money"></i>Compras</a></li>
                            <?php endif; ?>
                            <?php if ($__showListaDePrecios) : ?>
                                <li class="active"><a href="<?php echo base_url() ?>index.php/ListaDePrecios"><i class="ti-money"></i>Precios</a></li>
                            <?php endif; ?>
                            <!--<li class="active"><a href="<?php echo base_url() ?>index.php/Usuarios"><i class="ti-user"></i>Usuarios</a></li>-->

                            <?php if ($__showHerramientas) : ?>
                                <li class="dropdown">
                                    <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="pe-7s-news-paper"></i>
                                        Herramientas</a>
                                    <ul class="dropdown-menu">
                                        <?php if ($__showPrivilegiosUsuario) : ?>
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/PrivilegiosUsuario"><i class="ti-money"></i>Privilegios</a></li>
                                        <?php endif; ?>
                                        <?php if ($__showMesas) : ?>
                                            <li class="active"><a href="<?php echo base_url() ?>index.php/Mesas"><i class="ti-money"></i>Mesas</a></li>
                                        <?php endif; ?>
                                    </ul>
                                </li>
                            <?php endif; ?>


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
