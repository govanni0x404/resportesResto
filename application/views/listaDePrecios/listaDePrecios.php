<?php
date_default_timezone_set('America/Santiago');
$date = new DateTime('now');
$date->modify('last day of this month');
$fechaActual = date('Y-m-d');
?>
<div class="content-header">
    <div class="header-icon">
        <i class="pe-7s-cash"></i>
    </div>
    <div class="header-title">
        <h1>Lista De Precios</h1>
        <small id="caja"></small>
        <ol class="breadcrumb">
            <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i>Reportes</a></li>
            <li class="active">Lista De Precios</li>
        </ol>
    </div>
</div> <!-- /.Content Header (Page header) -->




<div class="container-padding lista-precios-page">
    <input type="number" class="form-control" style="display: none;" name="id_rol" id="id_rol" value="0" min="0" oninput="validity.valid||(value='');">

    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12 m-b-20">
            <div class="panel-body">
                <div class="row lista-precios-filtros t-filtros">
                <div class="col-xs-12 col-sm-6 col-md-3 lista-precios-filtros__item">
                    <br>
                    <label>Lista De Precios:
                        
                        <div class="checkbox checkbox-success checkbox-inline">
                            <input type="checkbox" id="ckTodasLasListasDePrecios" value="option1">
                            <label for="ckTodasLasListasDePrecios"> Todas </label>
                        </div>

                    </label>
                    <select class="form-control" id="cboListaDePrecios" nombre="cboListaDePrecios">
                        <option selected value="">
                            <!--Todas Las Formas De Pago-->
                        </option>
                    </select>
                </div>

                <div class="col-xs-12 col-sm-6 col-md-3 lista-precios-filtros__item">
                    <br>
                    <label>Familias:

                        <div class="checkbox checkbox-success checkbox-inline">
                            <input type="checkbox" id="ckTodasLasFamilias" value="option1">
                            <label for="ckTodasLasFamilias"> Todas </label>
                        </div>

                    </label>
                    <select class="form-control" id="cboFamilias" nombre="cboFamilias">
                        <option selected value="">
                            <!--Todas Las Formas De Pago-->
                        </option>
                    </select>
                </div>

                <div class="col-xs-12 col-sm-6 col-md-2 lista-precios-filtros__item lista-precios-filtros__item--boton">
                    <br>
                    <button id="btnFiltrar" class="btn btn-success btn-rounded w-md m-b-1 lista-precios-btn-filtrar"><i class="hvr-buzz-out fa fa-search"></i><span class="lista-precios-btn-filtrar__text"> Filtrar</span></button>
                </div>

                <div class="col-xs-12 col-sm-6 col-md-4 lista-precios-filtros__item lista-precios-filtros__item--accion">
                    <br>
                    <button onclick="crearNuevaLprecio()" type="button" class="btn btn-primary lista-precios-btn-crear"><i class="fa fa-plus"></i> Crear Nueva Lista De Precios</button>
                </div>
                </div>
                <div class="panel lobidisable lista-precios-vacio">
                    <div class="panel-heading">
                        <div class="panel-title">

                        </div>
                    </div>
                    <br>

                </div>
            </div>


            <div class="container-padding">
                <input type="number" class="form-control" style="display: none;" name="id_rol" id="id_rol" value="0" min="0" oninput="validity.valid||(value='');">

                <div class="row">
                    <div class="col-xs-12 col-sm-12 col-md-12 m-b-20">
                        <div class="panel-body">

                            <div class="panel panel-success lobidisable">
                                <div class="panel-heading">
                                    <div class="panel-title">
                                        <i class="pe-7s-menu"></i>
                                        <h4>Productos</h4>
                                    </div>
                                </div>
                                <div class="table-responsive lista-precios-tabla-principal">
                                    <table id="tableResumenListaPrecios" class="display responsive" style="width: 100%">
                                        <thead>
                                            <tr>
                                                <th class="text text-center" style='height:10px'>N° PRODUCTO</th>
                                                <th class="text text-center" style='height:45px'>CODIGO DE BARRAS</th>
                                                <th class="text text-left" style='height:45px'>PRODUCTO</th>
                                                <th class="text text-left" style='height:45px'>FAMILIA</th>
                                                <th class="text text-left" style='height:45px'>UNIDAD DE MEDIDA</th>
                                                <th class="text text-right" style='height:45px'>PRECIO DE VENTA</th>
                                                <!-- <th class="text text-right" style='height:45px'>PRECIO DE COMPRA</th> -->
                                                <th class="text text-right" style='height:45px'>Ganancia</th>
                                                <th class="text text-right" style='height:45px'>Costo</th>
                                                <th class="text text-right" style='height:45px'>% Costo</th>
                                                <th class="text text-right" style='height:45px'>Monto Comision</th>
                                            </tr>
                                        </thead>
                                        <tfoot>
                                            <tr>
                                                <th></th>
                                                <th></th>
                                                <th></th>
                                                <th></th>
                                                <th></th>
                                                <th></th>
                                                <th></th>
                                                <th></th>
                                                <th></th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>


                                </div>
                            </div>
                        </div>

                        <div id="ModalNuevaLprecios" class="modal fade t-modal-form" role="dialog" style="overflow-y: scroll;">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <!-- TITULO  MODAL  -->
                                    <div id="tituloModal" class="modal-header btn-success">
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        <h4 class="modal-title text text-center">CREAR NUEVA LISTA DE PRECIOS
                                        </h4>
                                    </div>
                                    <!-- FIN TITULO  MODAL  -->

                                    <!-- CONTENIDO  MODAL  -->
                                    <div class="modal-body">
                                        <div class="h-panel">
                                            <ul id="myTab" class="nav nav-with title font">
                                                <li class="active"><a href="#tabDatosUsuarios" data-toggle="tab" id="tab1" class="text color1"></a></li>
                                            </ul>
                                            <div class="tab-pane fade active in" id="tabDatosUsuarios">
                                                <!-- CAMPOS DE REGISTRO  -->
                                                <fieldset>
                                                    <div class="col-sm-3">
                                                        <label for="idlPrecio">ID</label>
                                                        <input type="number" disabled class="form-control input-sm input-sm" name="idlPrecio" id="idlPrecio" requerido="no" value="">
                                                    </div>
                                                    <div class="col-sm-9">
                                                        <label for="nombreLprecio">NOMBRE LISTA DE PRECIOS *</label>
                                                        <input type="text" class="form-control input-sm input-sm" maxlength='28' onpaste="return false;" ondrop="return false;" autocomplete="off" name="nombreLprecio" id="nombreLprecio" requerido="si" value="" placeholder="Nombre Lista Precios" />
                                                    </div>
                                                </fieldset>
                                                <!-- FIN CAMPOS DE REGISTRO  -->
                                            </div>
                                        </div>
                                    </div>
                                    <!-- CONTENIDO  MODAL  -->

                                    <!-- BOTONES  -->
                                    <div class="modal-footer">
                                        <button onclick="GrabarNuevaLprecio()" type="button" class="btn btn-primary" id="btnGrabarRol"><i class="fa fa-check"></i> GRABAR</button>
                                        <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> SALIR</button>
                                    </div>
                                    <!-- FIN BOTONES  -->
                                </div>
                            </div>
                        </div>
                        <!-- FIN  MODAL NUEVO REGISTRO  -->

                        <div id="modalCambioPrecio" class="modal fade t-modal-form" role="dialog" style="overflow-y: scroll;">
                            <!--<div class="modal-dialog modal-lg">-->
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header btn-success">
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        <h4 class="modal-title color4 f-bold">
                                            CAMBIO DE PRECIO
                                        </h4>
                                    </div>
                                    <div class="modal-body">
                                        <input hidden type="text" name="idProducto" id="idProducto" requerido="no" value="">
                                        <fieldset>
                                            <div class="col-md-12">
                                                <div class="panel panel-default">
                                                    <div class="panel-body table-responsive lista-precios-tabla-modal">
                                                        <table id="tablePrecios" class="table table-hover display" style="width:100%">
                                                            <thead>
                                                                <tr>
                                                                    <th style= 'display:none'>ID</th>
                                                                    <th>LISTA DE PRECIOS</th>
                                                                    <th class="text-right">PRECIO BRUTO UN</th>
                                                                    <th class="text-right">MONTO COMISIÓN</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody id="detallePrecioVenta">
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </fieldset>



                                    </div><!-- end body modal--->
                                    <div class="modal-footer">
                                        <button onclick="grabarPrecios()" type="button" class="btn btn-primary"><i class="fa fa-check"></i> Grabar Precios</button>
                                        <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> Cancelar</button>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- End Moda Code -->

<style>
    /* Diseño (solo estilos) */
    .lista-precios-filtros.t-filtros { margin: 0 0 16px; padding: 12px 4px 2px; }
    .lista-precios-filtros__item > br { display: none; }
    .lista-precios-filtros__item > label { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .lista-precios-filtros__item > label .checkbox { margin: 0; }
    .lista-precios-filtros__item .checkbox label { text-transform: none; letter-spacing: 0; font-weight: 600; }
    .lista-precios-filtros__item--accion { margin-left: auto; text-align: right; }
    .lista-precios-vacio { display: none; }
    .lista-precios-page .container-padding > .row > [class*="col-"] > .panel-body { padding: 0; }
    .lista-precios-tabla-principal { padding: 6px 18px 18px; margin: 0; border: 0; }
    .t-modal-form .modal-header { border-top: 3px solid var(--t-primario, #B4432E); }
    .t-modal-form .modal-title { text-align: left; }
    .t-modal-form .h-panel > .nav { display: none; }
    .t-modal-form .tab-pane > fieldset { margin: 0 -8px; }
    .t-modal-form fieldset::after { content: ""; display: table; clear: both; }
    .t-modal-form fieldset > [class*="col-"] { padding: 0 8px; margin-bottom: 14px; }
    .t-modal-form label { font-size: 12px; text-transform: uppercase; letter-spacing: .3px; color: var(--t-texto-suave, #7A6A5E); margin-bottom: 5px; }
    .t-modal-form .modal-body .panel { box-shadow: none; margin-bottom: 0; }
    .t-modal-form .modal-footer .btn { min-width: 110px; }
    /* Cambio de precio: sin recuadro dentro del modal y encabezados en una línea */
    #modalCambioPrecio .modal-dialog { max-width: 640px; }
    #modalCambioPrecio .modal-body .panel { border: 0; border-radius: 0; background: none; }
    #modalCambioPrecio .modal-body .panel-body { padding: 0; }
    #modalCambioPrecio fieldset > [class*="col-"] { margin-bottom: 0; }
    #modalCambioPrecio #tablePrecios { margin: 0; }
    #modalCambioPrecio #tablePrecios th { white-space: nowrap; }
    #modalCambioPrecio #tablePrecios td { padding: 8px; }
    #modalCambioPrecio #tablePrecios input { text-align: right; font-weight: 700; }

    .lista-precios-filtros {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        margin-bottom: 10px;
    }

    .lista-precios-filtros__item {
        margin-bottom: 12px;
    }

    .lista-precios-btn-filtrar,
    .lista-precios-btn-crear {
        min-height: 38px;
    }

    .lista-precios-tabla-principal,
    .lista-precios-tabla-modal {
        overflow-x: auto;
        overflow-y: hidden;
    }

    .lista-precios-tabla-principal table,
    .lista-precios-tabla-modal table {
        width: max-content !important;
        min-width: 100%;
    }

    @media (max-width: 991px) {
        #ModalNuevaLprecios fieldset > [class*="col-"] {
            margin-bottom: 12px;
        }
    }

    @media (max-width: 768px) {
        .lista-precios-page .panel-body {
            padding-left: 10px;
            padding-right: 10px;
        }

        .lista-precios-filtros__item--boton,
        .lista-precios-filtros__item--accion {
            margin-top: 0;
        }

        .lista-precios-btn-filtrar,
        .lista-precios-btn-crear {
            width: 100%;
        }

        .lista-precios-filtros__item--accion { margin-left: 0; text-align: left; }

        .lista-precios-tabla-principal { padding: 6px 10px 12px; }

        .lista-precios-tabla-principal table thead th,
        .lista-precios-tabla-principal table tbody td {
            white-space: nowrap;
            font-size: 12px;
            padding: 8px 6px;
        }

        .lista-precios-tabla-modal table thead th,
        .lista-precios-tabla-modal table tbody td {
            white-space: nowrap;
            font-size: 12px;
        }

        .lista-precios-tabla-modal input.form-control {
            min-width: 110px;
        }
    }

    @media (max-width: 480px) {
        .lista-precios-btn-filtrar__text {
            display: inline;
        }

        .lista-precios-tabla-principal table thead th,
        .lista-precios-tabla-principal table tbody td {
            font-size: 11px;
            padding: 7px 5px;
        }
    }
</style>
