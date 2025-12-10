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
        <h1>Salidas A Bodega</h1>
        <small id="caja"></small>
        <ol class="breadcrumb">
            <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i>Stock</a></li>
            <li class="active">Ingresos De Salidas A Bodega</li>
        </ol>
    </div>
</div>


<div class="container-padding">
    <input type="number" class="form-control" style="display: none;" name="id_rol" id="id_rol" value="0" min="0"
        oninput="validity.valid||(value='');">

    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12 m-b-20">
            <div class="panel-body">
                <fieldset>
                    <div class="col-md-4">
                        <label for="cboBodega">Bodega</label>
                        <select id="cboBodega" nombre="cboBodega" class="form-control input-sm" style="width:100%"
                            requerido="si"></select>
                    </div>
                    <div class="col-md-2">
                        <label for="Fecha">Fecha</label>
                        <input type="text" id="Fecha" class="form-control input-sm"
                            value="<?php echo $fechaActual; ?>" />
                    </div>
                    <br>
                    <!--
                    <div class="col-md-2">
                        <button id="btnValidarSalida" type="button" class="btn btn-primary"></i>Validar Stock</button>
                    </div>
                    -->
                </fieldset>
            </div>

            <div class="container-padding">
                <input type="number" class="form-control" style="display: none;" name="id_rol" id="id_rol" value="0"
                    min="0" oninput="validity.valid||(value='');">

                <div class="row">
                    <div class="col-xs-12 col-sm-12 col-md-12 m-b-20">
                        <div class="panel-body">
                            <fieldset>
                                <div class="col-md-4">
                                    <label for="cboInsumo">Insumo</label>
                                    <select id="cboInsumo" nombre="cboInsumo" class="form-control input-sm"
                                        style="width:100%" requerido="si"></select>
                                </div>

                                <div class="col-md-2">
                                    <label class="label1" for="txtStockActual">Stock actual</label>
                                    <input type="number" disabled class="text-right form-control input-sm input-sm"
                                        name="txtStockActual" id="txtStockActual" requerido="no" value="">
                                    </select>
                                </div>


                                <div class="col-md-2 ">
                                    <label for="CantidadNew">Cantidad</label>
                                    <input min="0" type="number" class="form-control input-sm input-sm" maxlength='28'
                                        /*onkeypress="return sololetrasyNumeros(event)" */ onpaste="return false;"
                                        ondrop="return false;" autocomplete="off" name="CantidadNew"
                                        id="CantidadNew" requerido="si" value="" />
                                </div>

                                <div class="col-md-2 ">
                                    <label for="UmedidaInsumo">U.Medida</label>
                                    <input disabled min="0" type="text" class="form-control input-sm input-sm"
                                        maxlength='28' /*onkeypress="return sololetrasyNumeros(event)" */
                                        onpaste="return false;" ondrop="return false;" autocomplete="off"
                                        name="UmedidaInsumo" id="UmedidaInsumo" requerido="si" value="" />
                                </div>


                                <br>
                                <div class="col-md-2">
                                    <button onclick="agregarProductoALaGrilla()" id="btnAgregarALista" type="button"
                                        class="btn btn-primary"></i>Agregar</button>
                                </div>
                            </fieldset>
                        </div>

                        <div class="container-padding">
                            <input type="number" class="form-control" style="display: none;" name="id_rol" id="id_rol"
                                value="0" min="0" oninput="validity.valid||(value='');">

                            <div class="row">
                                <div class="col-xs-12 col-sm-12 col-md-12 m-b-20">
                                    <div class="panel-body">
                                        <fieldset>
                                            <table id="tableSalidaBodega" style=""
                                                class="table table-hover table-bordered">
                                                <thead class="btn-success">
                                                    <tr>
                                                        <th style='display:none;' scope="col">id Insumo</th>
                                                        <th style="width:35%" class="text-center" scope="col">Insumo</th>
                                                        <th style='display:none;' scope="col">Stock Anterior</th>
                                                        <th style="width:15%" class="text-center" scope="col">Cantidad</th>
                                                        <th style="width:15%" class="text-center" scope="col">Quitar</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="detalleSalidaBodega">
                                                </tbody>
                                            </table>
                                            <div class="container-padding">
                                        <input type="number" class="form-control" style="display: none;" name="id_rol"
                                            id="id_rol" value="0" min="0" oninput="validity.valid||(value='');">

                                        <div class="row">
                                            <div class="">
                                                <div class="panel-body">
                                                    <fieldset>
                                                        <div class="col-md-5">
                                                        </div>
                                                        <div class="col-md-5">
                                                        </div>
                                                        <div class="col-md-2">
                                                            <button onclick="grabarSalidaBodega()" type="button"
                                                                class="btn btn-primary"></i>SALIDA DE 
                                                            BODEGA</button>
                                                        </div>
                                                        <div class="panel lobidisable">
                                                            <div class="panel-heading">
                                                                <div class="panel-title">

                                                                </div>
                                                            </div>
                                                            <br>

                                                        </div>
                                                </div>

                                        </fieldset>
                                    </div>


                                    