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
                <div class="modal-body">
                    <div class="h-panel">
                        <div class="row">
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
                                        <input type="text" autocomplete="off" maxlength="100"
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
                                            <sup><a onclick="AbrirModalListadoMenu()" style="color: #4CAF50; cursor: pointer;">Nuevo</a></sup>
                                            <sup><a onclick="QuitarMenuDeProducto()" style="color: #f44336; cursor: pointer;">Quitar</a></sup>
                                        </label>
                                        <select id="cboMenu" class="form-control input-sm" style="width:100%"
                                            requerido="no"></select>
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
                <button onclick="GrabarProducto()" type="button" class="btn btn-primary"></i>Grabar Producto</button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class=""></i>
                    SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>

<div id="modalPrecioVentaProducto" class="modal fade" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog modal-lg"> <!-- Cambiado a modal-lg para más espacio -->
        <div class="modal-content">
            <div class="modal-header btn-success">
                <button type="button" class="close" onclick="cancelarEdicionPrecios()" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title color4 f-bold">
                    PRECIO DE VENTA Y COMISIÓN
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
                                            <th style="width:30%">LISTA DE PRECIOS</th>
                                            <th class="text-right" style="width:30%">PRECIO BRUTO</th>
                                            <th class="text-right" style="width:30%">COMISIÓN</th>
                                        </tr>
                                    </thead>
                                    <tbody id="detallePrecioVenta">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </div>
            <div class="modal-footer">
                <!-- onclick="grabarPreciosYProducto()" -->
                <button  id="btnGrabarPrecios" type="button" class="btn btn-primary">
                    <i class=""></i> Grabar Precios y Comisiones
                </button>
                <button onclick="cancelarEdicionPrecios()" type="button" class="btn btn-default">
                    <i class=""></i> Cancelar
                </button>
            </div>
        </div>
    </div>
</div>
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
                            <div class="col-md-11 ">
                                <label for="nombreFamilia">FAMILIA *</label>
                                <input type="text" class="form-control input-sm input-sm" maxlength='28'
                                    onkeypress="return sololetrasyNumeros(event)" onpaste="return false;"
                                    ondrop="return false;" autocomplete="off" name="nombreFamilia" id="nombreFamilia"
                                    requerido="si" value="" placeholder="Nombre Familia" />
                            </div>

                            <div class="col-md-11 ">
                                <label for="AliasFamilia">ALIAS FAMILIA</label>
                                <input type="text" class="form-control input-sm input-sm" maxlength='28'
                                    onkeypress="return sololetrasyNumeros(event)" onpaste="return false;"
                                    ondrop="return false;" autocomplete="off" name="AliasFamilia" id="AliasFamilia"
                                    requerido="si" value="" placeholder="Alias Familia" />
                            </div>
                        </fieldset>
                        <fieldset>
                            <div class="col-md-7">
                                <label for="cboDestinoDeImpresionFamilia">DESTINO DE IMPRESION</label>
                                <select id="cboDestinoDeImpresionFamilia" nombre="cboDestinoDeImpresionFamilia"
                                    class="form-control input-sm" style="width:100%" requerido="si"></select>
                            </div>
                            <br>
                            <div class="col-md-5">
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
                            <div class="col-md-5 ">
                                <label for="ordenFamilia">ORDEN FAMILIA</label>
                                <input type="number" class="form-control input-sm input-sm" maxlength='28'
                                    onkeypress="return sololetrasyNumeros(event)" onpaste="return false;"
                                    ondrop="return false;" autocomplete="off" name="ordenFamilia" id="ordenFamilia"
                                    requerido="si" value="" placeholder="Orden Familia" />
                            </div>
                            <br>
                            <div class="col-md-7">
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
                <button onclick="GrabarNuevaFamilia()" type="button" class="btn btn-primary"><i
                        class=""></i>GRABAR</button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><i
                        class=""></i>SALIR</button>
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
                            <div class="col-md-4 ">
                                <label for="idBodega">ID</label>
                                <input type="number" disabled class="form-control input-sm input-sm" name="idBodega"
                                    id="idBodega" requerido="no" value="">
                            </div>
                            <div class="col-md-10 ">
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
                <button onclick="GrabarNuevaBodega()" type="button" class="btn btn-primary"><i
                        class=""></i>GRABAR</button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><i
                        class=""></i>SALIR</button>
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
                            <div class="col-md-4 ">
                                <label for="idSubFamilia">ID</label>
                                <input type="number" disabled class="form-control input-sm input-sm" name="idSubFamilia"
                                    id="idSubFamilia" requerido="no" value="">
                            </div>
                        </fieldset>
                        <fieldset>
                            <div class="col-md-10">
                                <label for="cboFamiliaMsubFamilia">FAMILIA</label>
                                <select id="cboFamiliaMsubFamilia" nombre="cboFamiliaMsubFamilia"
                                    class="form-control input-sm" style="width:100%" requerido="si"></select>
                            </div>
                            <div class="col-md-10 ">
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
                <button onclick="GrabarNuevaSubFamilia()" type="button" class="btn btn-primary"><i
                        class=""></i>GRABAR</button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><i
                        class=""></i>SALIR</button>
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
                                <input type="number" min="0" max="50000"
                                    class="form-control input-sm input-sm" maxlength='28'
                                    onkeypress="return soloNumeros(event)" onpaste="return false;"
                                    ondrop="return false;" autocomplete="off" name="cantidadAgregado"
                                    id="cantidadAgregado" requerido="si" value="" />
                            </div>
                            <div class="col-md-6 ">
                                <label for="valorAdicionalAgregado">VALOR ADICIONAL</label>
                                <input type="number" min="0" max="50000"
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
                                        class=""></i>GRABAR AGREGADO</button>
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
                <button id="btnFinalizarCreacion" onclick="finalizarYCerrarAgregados()" 
                        type="button" class="btn btn-success" style="display:none;">
                    <i class=""></i> Finalizar y Guardar
                </button>   
                <!-- <button id="btnActualizarAgregado" type="button" class="btn btn-primary"><i
                        class=""></i>ACTUALIZAR AGREGADO</button> -->
                <button type="button" class="btn btn-default" onclick="cancelarModalAgregados()" data-dismiss="modal"><i
                        class=""></i>SALIR</button>
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
                                <button onclick="grabarMsgProductos()" type="button" class="btn btn-primary"><i
                                        class=""></i>GRABAR MENSAJE</button>
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
                <button type="button" class="btn btn-default" data-dismiss="modal"><i
                        class=""></i>SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>


<div id="ModalListadoMenus" class="modal fade" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- TITULO MODAL -->
            <div class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text-center">Listado de Menus</h4>
            </div>
            <!-- CONTENIDO MODAL -->
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <label>Seleccione el Menú</label>
                        <div class="pull-right">
                            <a href="#" onclick="AbrirModalNuevoMenu()">Crear</a>
                        </div>
                        <select id="cboMenuModal" class="form-control input" style="width: 100%;"></select>
                    </div>
                </div>
                <br>
                <div class="row">
                    <div class="col-md-12 text-right">
                        <a href="#" onclick="AbrirModalNuevaCategoriasMenu()">Crear categoria</a>
                    </div>
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="table-responsive">
                                <table id="tableCategoriasMenuModal" class="table table-bordered table-condensed">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th style="display:none">MenuID</th>
                                            <th>Nombre Categoria</th>
                                            <th>Costo Prom.</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="bodyCategoriasMenuModal">
                                        <!-- Se llena via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal" style="width: 130px; font-weight: bold;">Salir (Esc)</button>
            </div>
        </div>
    </div>
</div>

<div id="ModalNuevoMenu" class="modal fade" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- TITULO MODAL -->
            <div class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text-center">Menú</h4>
            </div>
            <!-- CONTENIDO MODAL -->
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-3">
                        <label>ID</label>
                        <input type="text" disabled class="form-control input-sm" id="txtIdMenu" value="">
                    </div>
                    <div class="col-md-9">
                        <label>Nombre del Menú</label>
                        <input type="text" class="form-control input-sm" id="txtNombreMenu" placeholder="Nombre Menu">
                    </div>
                </div>
                <br>
                <br>
                <div class="row" style="display:none;">
                    <div class="col-md-4">
                        <button onclick="GrabarNuevoMenu()" type="button" class="btn btn-primary btn-block">Grabar Datos</button>
                    </div>
                    <div class="col-md-4">
                        <!-- <button onclick="AbrirModalNuevoMenu()" type="button" class="btn btn-default btn-block">Nuevo</button> -->
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-default btn-block" data-dismiss="modal">Salir (Esc)</button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="table-responsive">
                                <table id="tableMenuAbajo" class="table table-bordered table-condensed">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                        </tr>
                                    </thead>
                                    <tbody id="bodyMenuAbajo">
                                        <!-- Se llena via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button onclick="GrabarNuevoMenu()" type="button" class="btn btn-primary">Grabar Datos</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Salir</button>
            </div>
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
                                    class="btn btn-primary"><i class="fa fa-plus"></i> NUEVA CATEGORIA</button>
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
                <button type="button" class="btn btn-default" data-dismiss="modal"><i
                        class=""></i>SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>

<div id="ModalNuevaCategoria" class="modal fade" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- TITULO MODAL -->
            <div class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text-center">Categorias del menú</h4>
            </div>
            <!-- CONTENIDO MODAL -->
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <label>Nombre del Menú</label>
                        <select id="cboMenusModalCategorias" class="form-control input" style="background-color: #fcf8e3; color: black; width: 100%;"></select>
                    </div>
                </div>
                <br>
                <div class="row">
                    <div class="col-md-3">
                        <label>ID</label>
                        <input type="text" disabled class="form-control input-sm" id="IdCategoria" value="">
                    </div>
                    <div class="col-md-6">
                        <label>Nombre de la Categoria</label>
                        <input type="text" class="form-control input-sm" id="txtNombreCategoria" placeholder="Nombre Categoria">
                    </div>
                    <div class="col-md-3 text-right">
                        <br>
                        <div class="checkbox checkbox-success checkbox-circle" style="margin-top: 5px;">
                            <input id="ckVigenciaCategoria" type="checkbox" checked>
                            <label for="ckVigenciaCategoria"> Vigente </label>
                        </div>
                    </div>
                </div>
                <br>
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="table-responsive">
                                <table id="tableCategoriasAbajo" class="table table-bordered table-condensed">
                                    <thead>
                                        <tr style="background-color: #E8F5E9;">
                                            <th>Código</th>
                                            <th>Nombre de la categoría</th>
                                            <th>Vigente</th>
                                        </tr>
                                    </thead>
                                    <tbody id="bodyCategoriasAbajo">
                                        <!-- Se llena via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button onclick="GrabarCategoriaMenu()" type="button" class="btn btn-primary">Grabar</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Salir</button>
            </div>
        </div>
    </div>
</div>

<div id="ModalAgregarProductosAcategorias" class="modal fade" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog modal-lg" style="width: 95%;">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div class="modal-header btn-success" style="border-bottom: 2px solid #558b2f; padding: 15px; background-color: #558b2f !important; height: 50px;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; opacity: 1; margin-top: -5px;"><span aria-hidden="true">&times;</span></button>
            </div>
            <!-- FIN TITULO  MODAL  -->

            <!-- CONTENIDO  MODAL  -->
            <div class="modal-body" style="padding: 20px; background-color: #fff;">
                <div class="row">
                    <div class="col-md-12">
                         <div id="labelMenuCategoriaDestacado" style="background-color: #558b2f; color: white; padding: 12px 20px; font-weight: bold; font-size: 18px; margin-bottom: 20px; border-radius: 6px; box-shadow: inset 0 0 10px rgba(0,0,0,0.1);">
                             <!-- Se llena via JS: MENU -> CATEGORIA -->
                         </div>
                    </div>
                </div>

                <div class="row" style="background-color: #f9fdf9; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #d6e9c6;">
                    <div class="col-md-1">
                        <label style="color: #558b2f;">ID</label>
                        <input type="text" disabled class="form-control input-sm" id="IdDetalleCategoria" style="background-color: #f5f5f5; border: 1px solid #ccc; font-weight: bold; text-align: center;">
                        <input type="hidden" id="IdDetalleCategoriaReal" value="">
                    </div>
                    <div class="col-md-6">
                        <label style="color: #558b2f;">Producto <span id="labelRecetaProducto" style="font-weight: bold; color: #5cb85c; margin-left: 15px; background: white; padding: 2px 8px; border-radius: 10px; border: 1px solid #5cb85c; font-size: 10px;">SIN RECETA</span></label>
                        <select id="cboSelectorProductosFinal" class="form-control input-sm" style="border: 1px solid #5cb85c;"></select>
                    </div>
                    <div class="col-md-2">
                        <label style="color: #558b2f;">Costo</label>
                        <input type="number" class="form-control input-sm text-right" id="CostoCategoriaMenu" value="0" style="border: 1px solid #5cb85c; font-weight: bold;">
                    </div>
                    <div class="col-md-3">
                        <label style="color: #558b2f;">$ Adicional Vta</label>
                        <input type="number" class="form-control input-sm text-right" id="AdicionalCategoriaMenu" value="0" style="border: 1px solid #5cb85c; font-weight: bold;">
                    </div>
                </div>

                <div class="row" style="background-color: #f9fdf9; padding: 10px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #d6e9c6; display: flex; align-items: center; gap: 20px;">
                    <div style="margin-left: 15px;">
                        <label style="cursor: pointer; color: #3c763d; font-weight: bold;">
                            <input type="checkbox" id="ckPermiteCambio" style="transform: scale(1.3); vertical-align: middle;"> &nbsp;Permitir Cambio
                        </label>
                    </div>
                    <div>
                        <label style="cursor: pointer; color: #3c763d; font-weight: bold;">
                            <input type="checkbox" id="ckImprimir" style="transform: scale(1.3); vertical-align: middle;"> &nbsp;Imprimir
                        </label>
                    </div>
                </div>

                <div class="row">
                     <div class="col-md-10">
                         <div class="panel panel-default" style="margin-bottom: 0; border: 1px solid #d6e9c6; box-shadow: 0 4px 8px rgba(0,0,0,0.05);">
                            <div class="table-responsive" style="height: 380px; overflow-y: auto;">
                                <table id="tableDetalleCategoria" class="table table-bordered table-condensed table-hover" style="margin-bottom: 0;">
                                    <thead>
                                        <tr style="background-color: #558b2f; color: white;">
                                            <th style="width: 50px;">ID</th>
                                            <th>Nombre del Producto</th>
                                            <th class="text-right" style="width: 120px;">Costo Receta</th>
                                            <th class="text-right" style="width: 120px;">Costo A/Venta</th>
                                            <th class="text-center" style="width: 100px;">Permite Ca</th>
                                            <th class="text-center" style="width: 80px;">Imp</th>
                                        </tr>
                                    </thead>
                                    <tbody id="DetalleCategoria">
                                        <!-- Se llena via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <style>
                            #tableDetalleCategoria tbody tr.selected {
                                background-color: #dff0d8 !important;
                                color: #3c763d !important;
                                font-weight: bold;
                            }
                        </style>
                     </div>
                     <div class="col-md-2 text-center">
                         <div style="background-color: #f8f9fa; padding: 20px 10px; border-radius: 10px; border: 1px solid #dee2e6; height: 380px; display: flex; flex-direction: column; justify-content: center; gap: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                             <button onclick="GrabarDetalleCategoriaMenu()" type="button" class="btn btn-warning btn-block" style="background-color: #f0ad4e; border-color: #eea236; color: white; height: 75px; font-weight: bold; font-size: 15px; line-height: 1.3; box-shadow: 0 4px 8px rgba(240,173,78,0.3); border-radius: 8px;">
                                 <i class="fa fa-plus-circle" style="font-size: 20px;"></i><br>AÑADIR /<br>ACTUALIZAR
                             </button>
                             
                             <button onclick="NuevoDetalleProducto()" type="button" class="btn btn-default btn-block" style="height: 50px; font-weight: bold; border: 1px solid #ced4da; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-radius: 8px;">
                                 <i class="fa fa-eraser"></i> Limpiar / Nuevo
                             </button>
                             
                             <button onclick="QuitarProductoDeCategoria()" type="button" class="btn btn-danger btn-block" style="height: 50px; font-weight: bold; box-shadow: 0 4px 8px rgba(217,83,79,0.2); border-radius: 8px;">
                                 <i class="fa fa-trash"></i> Quitar
                             </button>
                         </div>
                     </div>
                </div>

                <div class="row" style="margin-top: 20px; background-color: #f9fdf9; padding: 15px; border-radius: 8px; border: 1px solid #d6e9c6;">
                    <div class="col-md-2" style="font-weight: bold; font-size: 15px; padding-top: 10px; color: #3c763d;">
                        Ítems: <span id="lblCantProductosCategoria" class="badge" style="background-color: #5cb85c; font-size: 14px;">0</span>
                    </div>
                    <div class="col-md-6 text-center">
                        <div style="border: 2px dashed #5cb85c; padding: 8px 15px; display: inline-block; border-radius: 12px; background-color: white;">
                            <span style="font-weight: bold; color: #3c763d; margin-right: 15px; font-size: 13px;"><i class="fa fa-sliders"></i> LÍMITES DE SELECCIÓN</span>
                            Mínima <input type="number" id="CantMinima" style="width: 55px; background-color: #5cb85c; color: white; border: none; text-align: center; border-radius: 4px; font-weight: bold; height: 28px; margin: 0 5px;" value="0">
                            &nbsp;
                            Máxima <input type="number" id="CantMaxima" style="width: 55px; background-color: #5cb85c; color: white; border: none; text-align: center; border-radius: 4px; font-weight: bold; height: 28px; margin: 0 5px;" value="5">
                            &nbsp;
                            <label style="cursor: pointer; margin-left: 10px; color: #3c763d;"><input type="checkbox" id="ckEstricto" style="transform: scale(1.2); vertical-align: middle;"> &nbsp;Estricto</label>
                        </div>
                    </div>
                    <div class="col-md-4 text-right">
                        <div style="display: inline-block; vertical-align: middle; font-weight: bold; font-size: 15px; color: #3c763d; text-transform: uppercase; letter-spacing: 1px;">Costo Promedio</div>
                        <div id="lblCostoPromedioCategoria" style="display: inline-block; background-color: #4cae4c; color: white; padding: 10px 25px; font-weight: bold; font-size: 22px; margin-left: 15px; border-radius: 6px; box-shadow: 0 4px 8px rgba(0,0,0,0.2); min-width: 80px; text-align: center;">0</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="background-color: #f9f9f9; border-top: 1px solid #e5e5e5;">
                <button onclick="GrabarConfiguracionCategoriaMenu()" type="button" class="btn btn-primary" style="font-weight: bold; width: 120px;">
                    GRABAR
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal" style="font-weight: bold; width: 120px;">Salir</button>                
            </div>
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
                <button type="button" class="btn btn-primary" id="btnGrabarInsumo"><i class=""></i>
                    GRABAR</button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class=""></i>
                    SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>
<script>
// Manejar el clic del botón según el contexto
$(document).ready(function() {
    $('#btnGrabarPrecios').off('click').on('click', function() {
        var idProducto = $("#txtID").val();
        
        if (idProducto && idProducto !== "0" && idProducto !== "") {
            // Es edición de producto existente
            grabarSoloPreciosDeProducto();
        } else {
            // Es creación de nuevo producto
            grabarPreciosYProducto();
        }
    });
});
</script>