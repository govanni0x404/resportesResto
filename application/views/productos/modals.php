<div id="modalNuevoProducto" class="modal fade" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModalInsumo" class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text text-center">CARTA DE PRODUCTOS
                </h4>
            </div>
            <!-- FIN TITULO  MODAL  -->

            <!-- CONTENIDO  MODAL  -->

            <div class="col-md-12">
                <div class="modal-body">
                    <div class="h-panel">
                        <div class="col-md-5">
                            <br>
                            <ul id="myTab" class="nav nav-tabs title font">
                                <li class="active">
                                    <a href="#tabDatosInsumo" data-toggle="tab" id="tab1" class="text color1"></a>
                                </li>
                            </ul>
                            <div class="tab-pane fade active in" id="tabDatosInsumo">
                                <br>
                                <!-- CAMPOS DE REGISTRO  -->
                                <fieldset>
                                    <div class="col-md-2 ">
                                        <label for="txtID">ID</label>
                                        <input type="text" disabled class="form-control input-sm input-sm" name="txtID"
                                            id="txtID" requerido="no" value="">
                                    </div>
                                    <div class="col-md-6 ">
                                        <label for="txtCbarra">Cód. Barra</label>
                                        <input type="text" autocomplete="off" class="form-control input-sm input-sm"
                                            name="txtCbarra" id="txtCbarra" requerido="no" value="">
                                    </div>
                                </fieldset>
                                <br>
                                <fieldset>
                                    <div class="col-md-12">
                                        <label for="txtNombre">Nombre Producto</label>
                                        <input type="text" autocomplete="off" maxlength="50"
                                            class="form-control input-sm input-sm" name="txtNombre" id="txtNombre"
                                            requerido="si" value="">
                                    </div>

                                </fieldset>
                                <br>
                                <fieldset>
                                    <div class="col-md-6">
                                        <label for="cboFamilia">Familia
                                            <sup><a onclick="crearNuevaFamilia()">Nuevo</a></sup>
                                        </label>
                                        <select id="cboFamilia" nombre="cboFamilia" class="form-control input-sm"
                                            style="width:100%" requerido="si"></select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="cboSubFamilia">Sub Familia
                                            <sup><a onclick="crearNuevaSubFamilia()">Nuevo</a></sup>
                                        </label>
                                        <select id="cboSubFamilia" nombre="cboSubFamilia" class="form-control input-sm"
                                            style="width:100%" requerido="si"></select>
                                    </div>
                                </fieldset>
                                <br>
                                <fieldset>
                                    <div class="col-md-6">
                                        <label for="cboUmedida">U/Medida</label>
                                        <select id="cboUmedida" nombre="cboUmedida" class="form-control input-sm"
                                            style="width:100%" requerido="si"></select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="cboDestinoDeImpresion">Destino De Impresion </label>
                                        <select id="cboDestinoDeImpresion" nombre="cboDestinoDeImpresion"
                                            class="form-control input-sm" style="width:100%" requerido="si"></select>
                                    </div>

                                </fieldset>
                                <br>
                                <fieldset>
                                    <div class="col-md-6">
                                        <label for="cboBodega">Bodega
                                            <sup><a onclick="crearNuevaBodega()">Nuevo</a></sup>
                                        </label>
                                        <select id="cboBodega" nombre="cboBodega" class="form-control input-sm"
                                            style="width:100%" requerido="si"></select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="cboImpuestoIva">Impuesto Iva</label>
                                        <select id="cboImpuestoIva" class="form-control input-sm" style="width:100%"
                                            requerido="si"></select>
                                    </div>
                                </fieldset>
                                <br>
                                <fieldset>
                                    <div class="col-md-6">
                                        <label for="cboReceta">Receta
                                            <sup><a onclick="AbrirModalNuevoReceta()">Nuevo</a></sup>
                                        </label>
                                        <select id="cboReceta" class="form-control input-sm" style="width:100%"
                                            requerido="si"></select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="cboMenu">Menu
                                            <sup><a onclick="AbrirModalListadoMenu()">Nuevo</a></sup>
                                        </label>
                                        <select id="cboMenu" class="form-control input-sm" style="width:100%"
                                            requerido="si"></select>
                                    </div>
                                </fieldset>

                                <!-- FIN CAMPOS DE REGISTRO  -->
                            </div>
                        </div>
                        <div><br></div>
                        <div class="col-md-7">
                            <ul id="myTab2" class="nav nav-tabs title font">
                                <li id="tabUmedida" class="active">
                                    <a href="#tabUnidadMedidaInsumo" data-toggle="tab" id="tab2"
                                        class="text color1">Parametros</a>
                                </li>

                            </ul>
                            <div class="tab-pane fade active in" id="tabUnidadMedidaInsumo">
                                <br>
                                <fieldset>
                                    <div class="col-md-4">
                                        <div class="btn-group" role="group" aria-label="...">
                                            <div class="checkbox checkbox-success checkbox-circle">
                                                <input id="ckVigente" type="checkbox">
                                                <label for="ckVigente">
                                                    Vigente
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="btn-group" role="group" aria-label="...">
                                            <div class="checkbox checkbox-success checkbox-circle">
                                                <input id="ckVentaPublico" type="checkbox">
                                                <label for="ckVentaPublico">
                                                    Venta A Publico
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="btn-group" role="group" aria-label="...">
                                            <div class="checkbox checkbox-success checkbox-circle">
                                                <input id="ckPermitirVentaSiempre" type="checkbox">
                                                <label for="ckPermitirVentaSiempre">
                                                    Permitir Venta Siempre
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                                <br>
                                <fieldset>
                                    <div class="col-md-4">
                                        <div class="btn-group" role="group" aria-label="...">
                                            <div class="checkbox checkbox-success checkbox-circle">
                                                <input id="ckVentaDirecta" type="checkbox">
                                                <label for="ckVentaDirecta">
                                                    Venta Directa (1 a 1)
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="btn-group" role="group" aria-label="...">
                                            <div class="checkbox checkbox-success checkbox-circle">
                                                <input id="ckConAgregado" type="checkbox">
                                                <label for="ckConAgregado">
                                                    Con Agregado
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="btn-group" role="group" aria-label="...">
                                            <div class="checkbox checkbox-success checkbox-circle">
                                                <input id="ckPermitirFraccion" type="checkbox">
                                                <label for="ckPermitirFraccion">
                                                    Permitir Fraccion
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                                <br>
                                <fieldset>
                                    <div class="col-md-4">
                                        <div class="btn-group" role="group" aria-label="...">
                                            <div class="checkbox checkbox-success checkbox-circle">
                                                <input id="ckVentaPorStock" type="checkbox">
                                                <label for="ckVentaPorStock">
                                                    Venta Por Stock
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="btn-group" role="group" aria-label="...">
                                            <div class="checkbox checkbox-success checkbox-circle">
                                                <input id="ckElaboracionLocal" type="checkbox">
                                                <label for="ckElaboracionLocal">
                                                    Elaboracion Local
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="btn-group" role="group" aria-label="...">
                                            <div class="checkbox checkbox-success checkbox-circle">
                                                <input id="ckPedirCantidadEnVenta" type="checkbox">
                                                <label for="ckPedirCantidadEnVenta">
                                                    Pedir Cantidad En venta
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                                <br>
                                <fieldset>
                                    <div class="col-md-4">
                                        <div class="btn-group" role="group" aria-label="...">
                                            <div class="checkbox checkbox-success checkbox-circle">
                                                <input id="ckVerDispositivomovil" type="checkbox">
                                                <label for="ckVerDispositivomovil">
                                                    Ver Dispositivo Movil
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="btn-group" role="group" aria-label="...">
                                            <div class="checkbox checkbox-success checkbox-circle">
                                                <input id="ckPermiteCortesia" type="checkbox">
                                                <label for="ckPermiteCortesia">
                                                    Permite Cortesia
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                                <br>
                                <fieldset id="extras">
                                    <div class="col-md-4">
                                        <div class="btn-group" role="group" aria-label="...">
                                            <button id="btnAgregados" onclick="AbrirModalAgregadosDelProducto()"
                                                type="button" class="btn btn-success"></i>Agregados Del
                                                Producto</button>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="btn-group" role="group" aria-label="...">
                                            <button id="BtnMensajesXProducto" onclick="AbrirModalMensajesDelProducto()"
                                                type="button" class="btn btn-success"></i>Mensaje Predefinidos</button>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="btn-group" role="group" aria-label="...">
                                            <button id="BtnPrecioVenta" onclick="AbrirModalPrecioVenta()" type="button"
                                                class="btn btn-success"></i>Precio Venta</button>
                                        </div>
                                    </div>

                                </fieldset>





                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <!-- CONTENIDO  MODAL  -->

            <!-- BOTONES  -->
            <div class="modal-footer">
                <button onclick="GrabarProducto()" type="button" class="btn btn-success"></i>Grabar Producto</button>
                <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-times-circle"></i>
                    SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>

<div id="modalPrecioVentaProducto" class="modal fade" role="dialog" style="overflow-y: scroll;">
    <!--<div class="modal-dialog modal-lg">-->
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title color4 f-bold">
                    PRECIO DE VENTA
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
                                            <th style='display:none'>ID</th>
                                            <th>LISTA DE PRECIOS</th>
                                            <th class="text-right">PRECIO BRUTO UN</th>
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
                <button onclick="grabarPreciosYProducto()" type="button" class="btn btn-danger">Grabar Precios</button>
                <button type="button" class="btn btn-white" data-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div> <!-- End Moda Code -->

<div id="ModalNuevaFamilia" class="modal fade" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModal" class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text text-center">NUEVA FAMILIA
                </h4>
            </div>
            <!-- FIN TITULO  MODAL  -->

            <!-- CONTENIDO  MODAL  -->
            <div class="modal-body">
                <div class="h-panel">
                    <ul id="myTab" class="nav nav-with title font">
                        <li class="active"><a href="#tabDatosUsuarios" data-toggle="tab" id="tab1"
                                class="text color1"></a></li>
                    </ul>
                    <div class="tab-pane fade active in" id="tabDatosUsuarios">
                        <!-- CAMPOS DE REGISTRO  -->
                        <fieldset>
                            <div class="col-md-6 ">
                                <label for="idFamilia">ID</label>
                                <input type="number" disabled class="form-control input-sm input-sm" name="idFamilia"
                                    id="idFamilia" requerido="no" value="">
                            </div>
                        </fieldset>
                        <fieldset>
                            <div class="col-md-6 ">
                                <label for="nombreFamilia">FAMILIA *</label>
                                <input type="text" class="form-control input-sm input-sm" maxlength='28'
                                    onkeypress="return sololetrasyNumeros(event)" onpaste="return false;"
                                    ondrop="return false;" autocomplete="off" name="nombreFamilia" id="nombreFamilia"
                                    requerido="si" value="" placeholder="Nombre Familia" />
                            </div>

                            <div class="col-md-6 ">
                                <label for="AliasFamilia">ALIAS FAMILIA</label>
                                <input type="text" class="form-control input-sm input-sm" maxlength='28'
                                    onkeypress="return sololetrasyNumeros(event)" onpaste="return false;"
                                    ondrop="return false;" autocomplete="off" name="AliasFamilia" id="AliasFamilia"
                                    requerido="si" value="" placeholder="Alias Familia" />
                            </div>
                        </fieldset>
                        <fieldset>
                            <div class="col-md-6">
                                <label for="cboDestinoDeImpresionFamilia">DESTINO DE IMPRESION</label>
                                <select id="cboDestinoDeImpresionFamilia" nombre="cboDestinoDeImpresionFamilia"
                                    class="form-control input-sm" style="width:100%" requerido="si"></select>
                            </div>
                            <br>
                            <div class="col-md-6">
                                <div class="btn-group" role="group" aria-label="...">
                                    <div class="checkbox checkbox-success checkbox-circle">
                                        <input id="ckVentaDirectaFamilia" type="checkbox">
                                        <label for="ckVentaDirectaFamilia">
                                            VENTA DIRECTA
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                        <fieldset>
                            <div class="col-md-4 ">
                                <label for="ordenFamilia">ORDEN FAMILIA</label>
                                <input type="number" class="form-control input-sm input-sm" maxlength='28'
                                    onkeypress="return sololetrasyNumeros(event)" onpaste="return false;"
                                    ondrop="return false;" autocomplete="off" name="ordenFamilia" id="ordenFamilia"
                                    requerido="si" value="" placeholder="Orden Familia" />
                            </div>
                            <br>
                            <div class="col-md-8">
                                <div class=" btn-group" role="group" aria-label="...">
                                    <div class="checkbox checkbox-success checkbox-circle">
                                        <input id="ckImprimirEnDetallePedido" type="checkbox">
                                        <label for="ckImprimirEnDetallePedido">
                                            IMPRIMIR EN DETALLE PEDIDO
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        <!-- FIN CAMPOS DE REGISTRO  -->
                    </div>
                </div>
            </div>
            <!-- CONTENIDO  MODAL  -->

            <!-- BOTONES  -->
            <div class="modal-footer">
                <button onclick="GrabarNuevaFamilia()" type="button" class="btn btn-success"><i
                        class="fa fa-check-square-o"></i>GRABAR</button>
                <button type="button" class="btn btn-danger" data-dismiss="modal"><i
                        class="fa fa-times-circle"></i>SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>
<!-- FIN  MODAL NUEVO REGISTRO  -->
<div id="ModalNuevaBodega" class="modal fade" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModal" class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text text-center">NUEVA BODEGA
                </h4>
            </div>
            <!-- FIN TITULO  MODAL  -->

            <!-- CONTENIDO  MODAL  -->
            <div class="modal-body">
                <div class="h-panel">
                    <ul id="myTab" class="nav nav-with title font">
                        <li class="active"><a href="#tabDatosUsuarios" data-toggle="tab" id="tab1"
                                class="text color1"></a></li>
                    </ul>
                    <div class="tab-pane fade active in" id="tabDatosUsuarios">
                        <!-- CAMPOS DE REGISTRO  -->
                        <fieldset>
                            <div class="col-md-6 ">
                                <label for="idBodega">ID</label>
                                <input type="number" disabled class="form-control input-sm input-sm" name="idBodega"
                                    id="idBodega" requerido="no" value="">
                            </div>
                            <div class="col-md-6 ">
                                <label for="nombreBodega">BODEGA *</label>
                                <input type="text" class="form-control input-sm input-sm" maxlength='28'
                                    onkeypress="return sololetrasyNumeros(event)" onpaste="return false;"
                                    ondrop="return false;" autocomplete="off" name="nombreBodega" id="nombreBodega"
                                    requerido="si" value="" placeholder="Nombre Bodega" />
                            </div>
                        </fieldset>
                        <!-- FIN CAMPOS DE REGISTRO  -->
                    </div>
                </div>
            </div>
            <!-- CONTENIDO  MODAL  -->

            <!-- BOTONES  -->
            <div class="modal-footer">
                <button onclick="GrabarNuevaBodega()" type="button" class="btn btn-success"><i
                        class="fa fa-check-square-o"></i>GRABAR</button>
                <button type="button" class="btn btn-danger" data-dismiss="modal"><i
                        class="fa fa-times-circle"></i>SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>
<!-- FIN  MODAL NUEVO REGISTRO  -->

<div id="ModalNuevaSubFamilia" class="modal fade" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModal" class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text text-center">NUEVA SUB FAMILIA
                </h4>
            </div>
            <!-- FIN TITULO  MODAL  -->

            <!-- CONTENIDO  MODAL  -->
            <div class="modal-body">
                <div class="h-panel">
                    <ul id="myTab" class="nav nav-with title font">
                        <li class="active"><a href="#tabDatosUsuarios" data-toggle="tab" id="tab1"
                                class="text color1"></a></li>
                    </ul>
                    <div class="tab-pane fade active in" id="tabDatosUsuarios">
                        <!-- CAMPOS DE REGISTRO  -->
                        <fieldset>
                            <div class="col-md-6 ">
                                <label for="idSubFamilia">ID</label>
                                <input type="number" disabled class="form-control input-sm input-sm" name="idSubFamilia"
                                    id="idSubFamilia" requerido="no" value="">
                            </div>
                        </fieldset>
                        <fieldset>
                            <div class="col-md-6">
                                <label for="cboFamiliaMsubFamilia">FAMILIA</label>
                                <select id="cboFamiliaMsubFamilia" nombre="cboFamiliaMsubFamilia"
                                    class="form-control input-sm" style="width:100%" requerido="si"></select>
                            </div>
                            <div class="col-md-6 ">
                                <label for="nombreSubFamilia">SUB FAMILIA *</label>
                                <input type="text" class="form-control input-sm input-sm" maxlength='28'
                                    onkeypress="return sololetrasyNumeros(event)" onpaste="return false;"
                                    ondrop="return false;" autocomplete="off" name="nombreSubFamilia"
                                    id="nombreSubFamilia" requerido="si" value="" placeholder="Nombre SubFamilia" />
                            </div>
                        </fieldset>
                        <!-- FIN CAMPOS DE REGISTRO  -->
                    </div>
                </div>
            </div>
            <!-- CONTENIDO  MODAL  -->

            <!-- BOTONES  -->
            <div class="modal-footer">
                <button onclick="GrabarNuevaSubFamilia()" type="button" class="btn btn-success"><i
                        class="fa fa-check-square-o"></i>GRABAR</button>
                <button type="button" class="btn btn-danger" data-dismiss="modal"><i
                        class="fa fa-times-circle"></i>SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>
<!-- FIN  MODAL NUEVO REGISTRO  -->

<div id="ModalAgregadosDelProducto" class="modal fade" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModal" class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text text-center">AGREGADOS
                </h4>
            </div>
            <!-- FIN TITULO  MODAL  -->

            <!-- CONTENIDO  MODAL  -->
            <div class="modal-body">
                <div class="h-panel">
                    <ul id="myTab" class="nav nav-with title font">
                        <li class="active"><a href="#tabDatosUsuarios" data-toggle="tab" id="tab1"
                                class="text color1"></a></li>
                    </ul>
                    <div class="tab-pane fade active in" id="tabDatosUsuarios">
                        <!-- CAMPOS DE REGISTRO  -->
                        <fieldset>
                            <div class="col-md-12">
                                <label for="cboProductoAgregadoProductos">PRODUCTO DE AGREGADO</label>
                                <select id="cboProductoAgregadoProductos" nombre="cboProductoAgregadoProductos"
                                    class="form-control input-sm" style="width:100%" requerido="si"></select>
                            </div>
                        </fieldset>
                        <fieldset>
                            <div class="col-md-6 ">
                                <label for="cantidadAgregado">CANTIDAD</label>
                                <input type="number" min="0" max="50000" onkeypress="return maxNumber()"
                                    class="form-control input-sm input-sm" maxlength='28'
                                    onkeypress="return soloNumeros(event)" onpaste="return false;"
                                    ondrop="return false;" autocomplete="off" name="cantidadAgregado"
                                    id="cantidadAgregado" requerido="si" value="" />
                            </div>
                            <div class="col-md-6 ">
                                <label for="valorAdicionalAgregado">VALOR ADICIONAL</label>
                                <input type="number" min="0" max="50000" onkeypress="return maxNumber()"
                                    class="form-control input-sm input-sm" maxlength='28'
                                    onkeypress="return soloNumeros(event)" onpaste="return false;"
                                    ondrop="return false;" autocomplete="off" name="valorAdicionalAgregado"
                                    id="valorAdicionalAgregado" requerido="si" value="" />
                            </div>
                        </fieldset>
                        <fieldset>
                            <div class="col-md-6">
                                <div class="btn-group" role="group" aria-label="...">
                                    <div class="checkbox checkbox-success checkbox-circle">
                                        <input id="ckUsarDestinoDeImpresion" type="checkbox">
                                        <label for="ckUsarDestinoDeImpresion">
                                            USAR DESTINO DE IMPRESION
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="btn-group" role="group" aria-label="...">
                                    <div class="checkbox checkbox-success checkbox-circle">
                                        <input id="ckVigenciaAgregado" type="checkbox">
                                        <label for="ckVigenciaAgregado">
                                            ACTIVO/VIGENTE
                                        </label>
                                    </div>
                                </div>
                            </div>

                        </fieldset>
                        <fieldset>
                            <div class="col-md-4">
                                <button onclick="GrabarNuevoAgregados()" type="button" class="btn btn-success"><i
                                        class="fa fa-check-square-o"></i>GRABAR AGREGADO</button>
                            </div>
                            <!--<div class="col-md-4">
                                <button type="button" class="btn btn-warning"><i class="fa fa-plus"></i>NUEVO AGREGADO</button>
                            </div>-->
                        </fieldset>
                        <br>
                        <fieldset>
                            <div class="col-md-12">
                                <div class="panel panel-default">
                                    <div class="panel-body table-responsive">
                                        <table id="tableAgregados" class="table table-hover display" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>CODIGO</th>
                                                    <th>PRODUCTO DE VENTA C/AGREGADO</th>
                                                    <th>AGREGADO</th>
                                                    <th>CANTIDAD</th>
                                                    <th>VALOR ADICIONAL</th>
                                                    <th>ACTIVO</th>
                                                    <th style="width:10%">ACCION</th>
                                                </tr>
                                            </thead>
                                            <tbody id="detalleAgregadosDelProducto">
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        <!-- FIN CAMPOS DE REGISTRO  -->
                    </div>
                </div>
            </div>
            <!-- CONTENIDO  MODAL  -->

            <!-- BOTONES  -->
            <div class="modal-footer">
                <button id="btnActualizarAgregado" type="button" class="btn btn-success"><i
                        class="fa fa-check-square-o"></i>ACTUALIZAR AGREGADO</button>
                <button type="button" class="btn btn-danger" data-dismiss="modal"><i
                        class="fa fa-times-circle"></i>SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>

<div id="ModalMensajesDelProducto" class="modal fade" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModal" class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text text-center">MENSAJES AL PRODUCTO
                </h4>
            </div>
            <!-- FIN TITULO  MODAL  -->

            <!-- CONTENIDO  MODAL  -->
            <div class="modal-body">
                <div class="h-panel">
                    <ul id="myTab" class="nav nav-with title font">
                        <li class="active"><a href="#tabDatosUsuarios" data-toggle="tab" id="tab1"
                                class="text color1"></a></li>
                    </ul>
                    <div class="tab-pane fade active in" id="tabDatosUsuarios">
                        <fieldset>
                            <div class="col-md-12">
                                <label for="txtMsgProducto">Mnesaje del Producto</label>
                                <textarea onpaste="return false;" ondrop="return false;" autocomplete="off"
                                    class="form-control" rows="3" id="txtMsgProducto"
                                    placeholder="Mensaje adicional producto"></textarea>
                            </div>
                        </fieldset>
                        <fieldset>
                            <div class="col-md-6">
                                <div class="btn-group" role="group" aria-label="...">
                                    <div class="checkbox checkbox-success checkbox-circle">
                                        <input id="ckVigenciaMsg" type="checkbox">
                                        <label for="ckVigenciaMsg">
                                            ACTIVO/VIGENTE
                                        </label>
                                    </div>
                                </div>
                            </div>

                        </fieldset>
                        <fieldset>
                            <div class="col-md-4">
                                <button onclick="grabarMsgProductos()" type="button" class="btn btn-success"><i
                                        class="fa fa-check-square-o"></i>GRABAR MENSAJE</button>
                            </div>
                            <!--<div class="col-md-4">
                                <button type="button" class="btn btn-warning"><i class="fa fa-plus"></i>NUEVO AGREGADO</button>
                            </div>-->
                        </fieldset>
                        <br>
                        <fieldset>
                            <div class="col-md-12">
                                <div class="panel panel-default">
                                    <div class="panel-body table-responsive">
                                        <table id="tableMsgAgregados" class="table table-hover display"
                                            style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>CODIGO</th>
                                                    <th>PRODUCTO</th>
                                                    <th>MENSAJE</th>
                                                    <th>ACTIVO</th>
                                                    <th style="width:10%">ACCION</th>
                                                </tr>
                                            </thead>
                                            <tbody id="detalleMsgDelProducto">
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        <!-- FIN CAMPOS DE REGISTRO  -->
                    </div>
                </div>
            </div>
            <!-- CONTENIDO  MODAL  -->

            <!-- BOTONES  -->
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal"><i
                        class="fa fa-times-circle"></i>SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>


<div id="ModalListadoMenus" class="modal fade" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModal" class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text text-center">LISTADO DE MENU
                </h4>
            </div>
            <!-- FIN TITULO  MODAL  -->

            <!-- CONTENIDO  MODAL  -->
            <div class="modal-body">
                <div class="h-panel">
                    <ul id="myTab" class="nav nav-with title font">
                        <li class="active"><a href="#tabDatosUsuarios" data-toggle="tab" id="tab1"
                                class="text color1"></a></li>
                    </ul>
                    <div class="tab-pane fade active in" id="tabDatosUsuarios">
                        <fieldset>
                            <div class="col-md-4">
                                <button onclick="AbrirModalNuevoMenu()" type="button" class="btn btn-success"><i
                                        class="fa fa-plus"></i>NUEVO MENU</button>
                            </div>
                        </fieldset>
                        <br>
                        <fieldset>
                            <div class="col-md-12">
                                <div class="panel panel-default">
                                    <div class="table-responsive" style="margin-top:10px; padding:5px;">
                                        <table id="tableListadoMenu" class="display responsive" style="width: 100%">
                                            <thead>
                                                <tr>
                                                    <th class="text text-right" style='height:10px'>N°</th>
                                                    <th class="text text-center">MENU</th>
                                                    <th class="text text-center">ACTIVO</th>
                                                    <!-- <th class="text text-center">ACCION</th>-->
                                                    <th class="text text-center">CATEGORIAS</th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        <!-- FIN CAMPOS DE REGISTRO  -->
                    </div>
                </div>
            </div>
            <!-- CONTENIDO  MODAL  -->

            <!-- BOTONES  -->
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal"><i
                        class="fa fa-times-circle"></i>SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>

<div id="ModalNuevoMenu" class="modal fade" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModal" class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text text-center">REGISTRO DE MENU
                </h4>
            </div>
            <!-- FIN TITULO  MODAL  -->

            <!-- CONTENIDO  MODAL  -->
            <div class="modal-body">
                <div class="h-panel">
                    <ul id="myTab" class="nav nav-with title font">
                        <li class="active"><a href="#tabDatosUsuarios" data-toggle="tab" id="tab1"
                                class="text color1"></a></li>
                    </ul>
                    <div class="tab-pane fade active in" id="tabDatosUsuarios">
                        <fieldset>
                            <div class="col-md-6 ">
                                <label for="txtIdMenu">ID</label>
                                <input type="text" disabled class="form-control input-sm input-sm" name="txtIdMenu"
                                    id="txtIdMenu" requerido="no" value="">
                            </div>
                            <div class="col-md-6 ">
                                <label for="txtNombreMenu">MENU</label>
                                <input placeholder="Nombre Menu" type="text" class="form-control input-sm input-sm"
                                    maxlength='28' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;"
                                    ondrop="return false;" autocomplete="off" name="txtNombreMenu" id="txtNombreMenu"
                                    requerido="si" value="" />
                            </div>
                        </fieldset>
                        <fieldset>
                            <div class="col-md-6">
                                <div class="btn-group" role="group" aria-label="...">
                                    <div class="checkbox checkbox-success checkbox-circle">
                                        <input id="ckVigenciaMenu" type="checkbox">
                                        <label for="ckVigenciaMenu">
                                            ACTIVO/VIGENTE
                                        </label>
                                    </div>
                                </div>
                            </div>

                        </fieldset>
                        <br>
                        <fieldset>
                            <div class="col-md-4">
                                <button onclick="GrabarNuevoMenu()" type="button" class="btn btn-success"><i
                                        class="fa fa-check-square-o"></i>GRABAR MENU</button>
                            </div>
                        </fieldset>
                        <!-- FIN CAMPOS DE REGISTRO  -->
                    </div>
                </div>
            </div>
            <!-- CONTENIDO  MODAL  -->

            <!-- BOTONES  -->
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal"><i
                        class="fa fa-times-circle"></i>SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>

<div id="ModalListadoCategoriasMenu" class="modal fade" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModal" class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text text-center">LISTADO DE CATEGORIAS
                </h4>
            </div>
            <!-- FIN TITULO  MODAL  -->

            <!-- CONTENIDO  MODAL  -->
            <div class="modal-body">
                <div class="h-panel">
                    <ul id="myTab" class="nav nav-with title font">
                        <li class="active"><a href="#tabDatosUsuarios" data-toggle="tab" id="tab1"
                                class="text color1"></a></li>
                    </ul>
                    <div class="tab-pane fade active in" id="tabDatosUsuarios">
                        <fieldset>
                            <div class="col-md-4">
                                <button onclick="AbrirModalNuevaCategoriasMenu()" type="button"
                                    class="btn btn-success"><i class="fa fa-plus"></i> NUEVA CATEGORIA</button>
                            </div>
                        </fieldset>
                        <br>
                        <fieldset>
                            <div class="col-md-12">
                                <div class="panel panel-default">
                                    <div class="table-responsive" style="margin-top:10px; padding:5px;">
                                        <table id="tableCategoriasMenu" class="display responsive" style="width: 100%">
                                            <thead>
                                                <tr>
                                                    <th class="text text-right" style='height:10px'>N°</th>
                                                    <th class="text text-center">MENU</th>
                                                    <th class="text text-center">CATEGORIA</th>
                                                    <th class="text text-center">VIGENCIA</th>
                                                    <th class="text text-center"></th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        <!-- FIN CAMPOS DE REGISTRO  -->
                    </div>
                </div>
            </div>
            <!-- CONTENIDO  MODAL  -->

            <!-- BOTONES  -->
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal"><i
                        class="fa fa-times-circle"></i>SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>

<div id="ModalNuevaCategoria" class="modal fade" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModal" class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text text-center">REGISTRO DE CATEGORIAS
                </h4>
            </div>
            <!-- FIN TITULO  MODAL  -->

            <!-- CONTENIDO  MODAL  -->
            <div class="modal-body">
                <div class="h-panel">
                    <ul id="myTab" class="nav nav-with title font">
                        <li class="active"><a href="#tabDatosUsuarios" data-toggle="tab" id="tab1"
                                class="text color1"></a></li>
                    </ul>
                    <div class="tab-pane fade active in" id="tabDatosUsuarios">
                        <fieldset>
                            <div class="col-md-6 ">
                                <label for="IdCategoria">ID</label>
                                <input type="text" disabled class="form-control input-sm input-sm" name="IdCategoria"
                                    id="IdCategoria" requerido="no" value="">
                            </div>
                        </fieldset>
                        <fieldset>
                            <div class="col-md-6">
                                <label for="cboMenus">MENU</label>
                                <select id="cboMenus" nombre="cboMenus" class="form-control input-sm" style="width:100%"
                                    requerido="si"></select>
                            </div>
                            <div class="col-md-6 ">
                                <label for="txtNombreCategoria">CATEGORIA</label>
                                <input placeholder="Nombre Categoria" type="text" class="form-control input-sm input-sm"
                                    maxlength='28' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;"
                                    ondrop="return false;" autocomplete="off" name="txtNombreCategoria"
                                    id="txtNombreCategoria" requerido="si" value="" />
                            </div>
                        </fieldset>
                        <fieldset>
                            <div class="col-md-6">
                                <div class="btn-group" role="group" aria-label="...">
                                    <div class="checkbox checkbox-success checkbox-circle">
                                        <input id="ckVigenciaCategoria" type="checkbox">
                                        <label for="ckVigenciaCategoria">
                                            ACTIVO/VIGENTE
                                        </label>
                                    </div>
                                </div>
                            </div>

                        </fieldset>
                        <br>
                        <fieldset>
                            <div class="col-md-4">
                                <button onclick="GrabarCategoriaMenu()" type="button" class="btn btn-success"><i
                                        class="fa fa-check-square-o"></i>GRABAR CATEGORIA</button>
                            </div>
                        </fieldset>
                        <!-- FIN CAMPOS DE REGISTRO  -->
                    </div>
                </div>
            </div>
            <!-- CONTENIDO  MODAL  -->

            <!-- BOTONES  -->
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal"><i
                        class="fa fa-times-circle"></i>SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>

<div id="ModalAgregarProductosAcategorias" class="modal fade" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModal" class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text text-center">COMPOSICION DEL MENU
                </h4>
            </div>
            <!-- FIN TITULO  MODAL  -->

            <!-- CONTENIDO  MODAL  -->
            <div class="modal-body">
                <div class="h-panel">
                    <ul id="myTab" class="nav nav-with title font">
                        <li class="active"><a href="#tabDatosUsuarios" data-toggle="tab" id="tab1"
                                class="text color1"></a></li>
                    </ul>
                    <div class="tab-pane fade active in" id="tabDatosUsuarios">
                        <fieldset>
                            <div class="col-md-2 ">
                                <label for="IdDetalleCategoria">ID</label>
                                <input type="text" disabled class="form-control input-sm input-sm"
                                    name="IdDetalleCategoria" id="IdDetalleCategoria" requerido="no" value="">
                            </div>
                            <div class="col-md-10">
                                <label for="cboMenuCategoria">MENU/CATEGORIA</label>
                                <select id="cboMenuCategoria" nombre="cboMenuCategoria" class="form-control input-sm"
                                    style="width:100%" requerido="si"></select>
                            </div>
                        </fieldset>
                        <fieldset>
                            <div class="col-md-12">
                                <label for="cboListadoProductosParaCategoria">PRODUCTO</label>
                                <select id="cboListadoProductosParaCategoria" nombre="cboListadoProductosParaCategoria"
                                    class="form-control input-sm" style="width:100%" requerido="si"></select>
                            </div>
                        </fieldset>
                        <fieldset>
                            <div class="col-md-6 ">
                                <label for="CostoCategoriaMenu">COSTO</label>
                                <input min="0" type="number" class="form-control input-sm input-sm" maxlength='28'
                                    onkeypress="return sololetrasyNumeros(event)" onpaste="return false;"
                                    ondrop="return false;" autocomplete="off" name="CostoCategoriaMenu"
                                    id="CostoCategoriaMenu" requerido="si" value="" />
                            </div>
                            <div class="col-md-6 ">
                                <label for="AdicionalCategoriaMenu">COSTO</label>
                                <input min="0" type="number" class="form-control input-sm input-sm" maxlength='28'
                                    onkeypress="return sololetrasyNumeros(event)" onpaste="return false;"
                                    ondrop="return false;" autocomplete="off" name="AdicionalCategoriaMenu"
                                    id="AdicionalCategoriaMenu" requerido="si" value="" />
                            </div>
                        </fieldset>
                        <fieldset>
                            <div class="col-md-6">
                                <div class="btn-group" role="group" aria-label="...">
                                    <div class="checkbox checkbox-success checkbox-circle">
                                        <input id="ckPermitirCambio" type="checkbox">
                                        <label for="ckPermitirCambio">
                                            PERMITIR CAMBIO
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="btn-group" role="group" aria-label="...">
                                    <div class="checkbox checkbox-success checkbox-circle">
                                        <input id="ckImprimirProductoCategoria" type="checkbox">
                                        <label for="ckImprimirProductoCategoria">
                                            IMPRIMIR
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                        <br>
                        <fieldset>
                            <div class="col-md-4">
                                <button onclick="GrabarDetalleCategoriaMenu()" type="button" class="btn btn-success"><i
                                        class="fa fa-check-square-o"></i>AGREGAR A CATEGORIA</button>
                            </div>
                        </fieldset>
                        <br>
                        <fieldset>
                            <div class="col-md-12">
                                <div class="panel panel-default">
                                    <div class="panel-body table-responsive">
                                        <table id="tableDetalleCategoria" class="table table-hover display"
                                            style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th style='display:none'>N°</th>
                                                    <th style='display:none'>ID CATEGORIA</th>
                                                    <th style='display:none'>CATEGORIA</th>
                                                    <th>PRODUCTO</th>
                                                    <th>COSTO RECETA</th>
                                                    <th>COSTO A/VENTA</th>
                                                    <th>PERMITE CAMBIO</th>
                                                    <th>IMPRIMIR</th>
                                                    <th>VIGENTE</th>
                                                    <!-- <th></th>
                                                    <th></th>
                                                    <th></th>-->
                                                </tr>
                                            </thead>
                                            <tbody id="DetalleCategoria">
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                        <br>
                        <fieldset>
                            <label>LIMITE DE SELECCION</label>
                        </fieldset>
                        <br>
                        <fieldset>
                            <div class="col-md-4 ">
                                <label for="CantMinima">MINIMA</label>
                                <input min="0" type="number" class="form-control input-sm input-sm" maxlength='28'
                                    onkeypress="return sololetrasyNumeros(event)" onpaste="return false;"
                                    ondrop="return false;" autocomplete="off" name="CantMinima" id="CantMinima"
                                    requerido="si" value="" />
                            </div>
                            <div class="col-md-4 ">
                                <label for="CantMaxima">MAXIMA</label>
                                <input min="0" type="number" class="form-control input-sm input-sm" maxlength='28'
                                    onkeypress="return sololetrasyNumeros(event)" onpaste="return false;"
                                    ondrop="return false;" autocomplete="off" name="CantMaxima" id="CantMaxima"
                                    requerido="si" value="" />
                            </div>
                            <br>
                            <div class="col-md-4">
                                <div class="btn-group" role="group" aria-label="...">
                                    <div class="checkbox checkbox-success checkbox-circle">
                                        <input id="ckEstricto" type="checkbox">
                                        <label for="ckEstricto">
                                            ESTRICTO
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                        <fieldset>
                            <div class="col-md-4 ">
                                <label for="CostoPromedio">COSTO PROMEDIO</label>
                                <input min="0" type="number" class="form-control input-sm input-sm" maxlength='28'
                                    onkeypress="return sololetrasyNumeros(event)" onpaste="return false;"
                                    ondrop="return false;" autocomplete="off" name="CostoPromedio" id="CostoPromedio"
                                    requerido="si" value="" />
                            </div>
                        </fieldset>
                        <!-- FIN CAMPOS DE REGISTRO  -->
                    </div>
                </div>
            </div>
            <!-- CONTENIDO  MODAL  -->

            <!-- BOTONES  -->
            <div class="modal-footer">
                <button id="BtnActualizarDetalleMenu" type="button" class="btn btn-success"><i
                        class="fa fa-check-square-o"></i>ACTUALIZAR</button>
                <button type="button" class="btn btn-danger" data-dismiss="modal"><i
                        class="fa fa-times-circle"></i>SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>

<div id="modalReceta" class="modal fade" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModalReceta" class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text text-center">REGISTRO RECETAS
                </h4>
            </div>
            <!-- FIN TITULO  MODAL  -->

            <!-- CONTENIDO  MODAL  -->
            <div class="right">
                <input type="number" class="form-control" style="display: none;" name="cant_lineas" id="cant_lineas"
                    value="0" min="0" oninput="validity.valid||(value='');">
            </div>
            <div class="col-md-12">
                <div class="modal-body">
                    <div class="h-panel">
                        <div class="panel panel-default">
                            <div class="panel-body">
                                <div>
                                    <!-- CAMPOS DE REGISTRO  -->
                                    <fieldset>
                                        <div class="col-md-1">
                                            <input disabled class="form-control input-sm input-sm"
                                                style="display: none;" name="contador" id="contador" requerido="no"
                                                value="0">
                                            <label for="idReceta">ID</label>
                                            <input type="number" disabled class="form-control input-sm input-sm"
                                                name="idReceta" id="idReceta" requerido="no" value="">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="nombreReceta">NOMBRE RECETA</label>
                                            <input type="text" class="form-control input-sm input-sm"
                                                name="nombreReceta" id="nombreReceta" maxlength='50'
                                                onkeypress="return sololetrasyNumeros(event)" onpaste="return false;"
                                                ondrop="return false;" autocomplete="off" requerido="no" value="">
                                        </div>
                                        <div class="col-md-2">
                                            <label for="rendimientoReceta">RENDIMIENTO</label>
                                            <input type="text" class="form-control input-sm input-sm" maxlength='50'
                                                onkeypress="return soloNumeros(event)" onpaste="return false;"
                                                ondrop="return false;" autocomplete="off" name="rendimientoReceta"
                                                id="rendimientoReceta" requerido="si" value="" />
                                        </div>
                                        <div class="col-md-3">
                                            <label for="brutoSugerido">PRECIO BRUTO SUG/VEN</label>
                                            <input type="text" class="form-control input-sm input-sm" maxlength='50'
                                                onkeypress="return soloNumeros(event)" onpaste="return false;"
                                                ondrop="return false;" autocomplete="off" name="brutoSugerido"
                                                id="brutoSugerido" requerido="si" value="" />
                                        </div>
                                    </fieldset>
                                    <br>
                                    <fieldset>
                                        <div class="col-md-3">
                                            <label for="cboUnMedida">UNIDAD MEDIDA</label>
                                            <select style="width:100%;" id="cboUnMedida" nombre="cboUnMedida"
                                                class="form-control input-sm" requerido="si">
                                                <option value="">--Seleccione--</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="cboGrupoReceta">GRUPO/FAMILIA
                                                <sup><a onclick="crearNuevaFamiliaReceta()">Nuevo</a></sup>
                                            </label>
                                            <select style="width:100%;" id="cboGrupoReceta" nombre="cboGrupoReceta"
                                                class="form-control input-sm" requerido="si">
                                                <option value="">--Seleccione--</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="cboSeccionReceta">SECCION
                                                <sup><a onclick="crearNuevaSeccion()">Nuevo</a></sup>
                                            </label>
                                            <select style="width:100%;" id="cboSeccionReceta" nombre="cboSeccionReceta"
                                                class="form-control input-sm" requerido="si">
                                                <option value="">--Seleccione--</option>
                                            </select>
                                        </div>
                                    </fieldset>
                                </div>
                            </div>
                        </div>
                        <div class="panel panel-default">
                            <div class="panel-body">
                                <div class="col-md-12">
                                    <br>
                                    <ul id="myTab" class="nav nav-tabs title font">
                                        <li class="active" id="tabDetallesReceta">
                                            <a href="#tabDetalleInsumosReceta" data-toggle="tab" id="tab1"
                                                class="text color1">DETALLE DE INSUMOS DE LA RECETA</a>
                                        </li>
                                        <li class="" id="tabPreparacionRecetas">
                                            <a href="#tabPreparacionReceta" data-toggle="tab" id="tab2"
                                                class="text color1">PREPARACION DE LA RECETA</a>
                                        </li>
                                    </ul>
                                    <div class="tab-pane fade active in" id="tabDetalleInsumosReceta">
                                        <br>
                                        <!-- CAMPOS DE REGISTRO  -->
                                        <fieldset>
                                            <div class="col-md-3">
                                                <label for="cboCodBarraInsumoReceta">COD BARRA/COD INTERNO</label>
                                                <select style="width:100%;" id="cboCodBarraInsumoReceta"
                                                    nombre="cboCodBarraInsumoReceta" class="form-control input-sm"
                                                    requerido="si">
                                                    <option value="0">--Seleccione--</option>
                                                </select>
                                                <input type="hidden" disabled class="form-control input-sm input-sm"
                                                    name="cBarraI" id="cBarraI" requerido="si" value="">

                                            </div>
                                            <div class="col-md-7">
                                                <label for="nombreInsumoReceta">NOMBRE INSUMO</label>
                                                <input type="text" disabled class="form-control input-sm input-sm"
                                                    name="nombreInsumoReceta" id="nombreInsumoReceta" requerido="si"
                                                    value="">
                                            </div>
                                            <div class="col-md-2">
                                                <label for="esSubreceta">SUBRECETA</label>
                                                <input type="text" disabled class="form-control input-sm input-sm"
                                                    name="esSubreceta" id="esSubreceta" requerido="si" value="">
                                            </div>
                                        </fieldset>
                                        <br>
                                        <fieldset>
                                            <div class="col-md-2">
                                                <label for="idInsumoReceta">ID INSUMO</label>
                                                <input type="number" disabled class="form-control input-sm input-sm"
                                                    name="idInsumoReceta" id="idInsumoReceta" requerido="no" value="">
                                            </div>

                                            <div class="col-md-2">
                                                <label for="cantidadInsumoReceta">CANTIDAD</label>
                                                <input type="number" class="form-control input-sm input-sm"
                                                    onkeypress="return soloNumerosDecimales(event)"
                                                    onpaste="return false;" ondrop="return false;" autocomplete="off"
                                                    name="cantidadInsumoReceta" id="cantidadInsumoReceta" requerido="si"
                                                    value="" />
                                            </div>
                                            <div class="col-md-2">
                                                <label for="unMedidaInsumoReceta">UN MEDIDA</label>
                                                <input type="text" disabled class="form-control input-sm input-sm"
                                                    onkeypress="return soloNumeros(event)" onpaste="return false;"
                                                    ondrop="return false;" autocomplete="off"
                                                    name="unMedidaInsumoReceta" id="unMedidaInsumoReceta" requerido="si"
                                                    value="" />
                                            </div>
                                            <div class="col-md-2">
                                                <label for="costoInsumoReceta">COSTO PP UN</label>
                                                <input type="number" disabled class="form-control input-sm input-sm"
                                                    onkeypress="return soloNumeros(event)" onpaste="return false;"
                                                    ondrop="return false;" autocomplete="off" name="costoInsumoReceta"
                                                    id="costoInsumoReceta" requerido="si" value="" />
                                            </div>
                                            <div class="col-md-2">
                                                <label for="costoTotalInsumoReceta">COSTO TOTAL</label>
                                                <input type="number" disabled class="form-control input-sm input-sm"
                                                    onkeypress="return soloNumeros(event)" onpaste="return false;"
                                                    ondrop="return false;" autocomplete="off"
                                                    name="costoTotalInsumoReceta" id="costoTotalInsumoReceta"
                                                    requerido="si" value="" />
                                            </div>
                                            <div class="col-md-2">
                                                <br>
                                                <button type="button" class="btn btn-rounded btn-danger"
                                                    id="btnAgregarInsumoTabla"><i class="fa fa-check-circle"></i>
                                                    AGREGAR INSUMO</button>
                                            </div>
                                        </fieldset>
                                        <br>
                                        <fieldset>
                                            <div class="table-responsive" style="margin-top:10px; padding:5px;">
                                                <table id="tableInsumoReceta" class="display responsive"
                                                    style="width: 100%;">
                                                    <thead>
                                                        <tr>
                                                            <th style='display:none;' hidden scope="col">ID DETALLE</th>
                                                            <th style='display:none;' hidden scope="col">ID INSUMO</th>
                                                            <th scope="col">COD/BARRA</th>
                                                            <th scope="col">NOMBRE DEL INSUMO</th>
                                                            <th scope="col">SUBRECETA</th>
                                                            <th scope="col">CANTIDAD</th>
                                                            <th scope="col">UN. MEDIDA</th>
                                                            <th scope="col">COSTO PP</th>
                                                            <th scope="col">COSTO TOTAL</th>
                                                            <th scope="col"></th>
                                                        </tr>
                                                    </thead>
                                                </table>
                                            </div>
                                        </fieldset>
                                        <!-- FIN CAMPOS DE REGISTRO  -->
                                    </div>
                                    <div class="tab-pane fade" id="tabPreparacionReceta">
                                        <br>
                                        <textarea
                                            style="resize:none;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box; width:100%; height:500px;"
                                            type='text' cols="200" maxlength='10000' name="preparacionReceta"
                                            onkeypress="return sololetrasyNumeros(event)" id="preparacionReceta"
                                            rows="20"></textarea>
                                    </div>
                                </div>
                                <!-- FIN CAMPOS DE REGISTRO  -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- CONTENIDO  MODAL  -->

            <!-- BOTONES  -->
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="btnGrabarInsumo"><i class="fa fa-check-square-o"></i>
                    GRABAR</button>
                <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-times-circle"></i>
                    SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>