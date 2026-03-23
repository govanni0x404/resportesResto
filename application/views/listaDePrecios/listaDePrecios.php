<?php
date_default_timezone_set('America/Santiago');
$date = new DateTime('now');
$date->modify('last day of this month');
$fechaActual = date('Y-m-d');
?>
<div class="content-header">
    <div class="header-icon">
        <i class="pe-7s-home"></i>
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




<div class="container-padding">
    <input type="number" class="form-control" style="display: none;" name="id_rol" id="id_rol" value="0" min="0" oninput="validity.valid||(value='');">

    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12 m-b-20">
            <div class="panel-body">
                <div class="col-md-3">
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

                <div class="col-md-3">
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
                <br>
                <div class="col-md-2">
                    <br>
                    <button id="btnFiltrar" style="width:5px;" class="btn btn-success btn-rounded w-md m-b-1"><i class="hvr-buzz-out fa fa-search"></i></button>
                </div>
                <br>
                <div class="col-md-4">
                    <button onclick="crearNuevaLprecio()" type="button" class="btn btn-primary"></i>Crear Nueva Lista De Precios</button>
                </div>
                <div class="panel lobidisable">
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
                                        <i class="pe-7s-cash"></i>
                                        <h4>PRODUCTOS</h4>
                                    </div>
                                </div>
                                <div class="table-responsive" style="margin-top:10px; padding:5px;">
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

                        <div id="ModalNuevaLprecios" class="modal fade" role="dialog" style="overflow-y: scroll;">
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
                                                    <div class="col-md-4 ">
                                                        <label for="idlPrecio">ID</label>
                                                        <input type="number" disabled class="form-control input-sm input-sm" name="idlPrecio" id="idlPrecio" requerido="no" value="">
                                                    </div>
                                                    <div class="col-md-11 ">
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
                                        <button onclick="GrabarNuevaLprecio()" type="button" class="btn btn-primary" id="btnGrabarRol"><i class=""></i>GRABAR</button>
                                        <button type="button" class="btn btn-default" data-dismiss="modal"><i class=""></i>SALIR</button>
                                    </div>
                                    <!-- FIN BOTONES  -->
                                </div>
                            </div>
                        </div>
                        <!-- FIN  MODAL NUEVO REGISTRO  -->

                        <div id="modalCambioPrecio" class="modal fade" role="dialog" style="overflow-y: scroll;">
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
                                                    <div class="panel-body table-responsive">
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
                                        <button onclick="grabarPrecios()" type="button" class="btn btn-primary">Grabar Precios</button>
                                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- End Moda Code -->