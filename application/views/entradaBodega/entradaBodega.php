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
        <h1>Entradas A Bodega</h1>
        <small id="caja"></small>
        <ol class="breadcrumb">
            <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i>Stock</a></li>
            <li class="active">Ingresos De Entradas A Bodega</li>
        </ol>
    </div>
</div>


<style>
    .mov-bodega .mov-paso { display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 50%;
        background: var(--t-primario-suave); color: var(--t-primario); font-size: 12px; margin-right: 6px; }
    .mov-bodega .mov-fila { display: flex; flex-wrap: wrap; gap: 12px 14px; align-items: flex-end; }
    .mov-bodega .mov-fila > div { flex: 1 1 160px; min-width: 0; }
    .mov-bodega .mov-fila > .mov-ancho { flex: 2 1 260px; }
    .mov-bodega .mov-fila > .mov-boton { flex: 0 0 auto; }
    .mov-bodega .mov-fila label { display: block; }
    .mov-bodega .form-control.input-sm { height: 38px; font-size: 14px; }
    .mov-bodega .mov-fila .btn { height: 38px; padding: 0 18px; }
    .mov-bodega textarea.form-control { height: auto; }
    .mov-bodega #tableEntradaBodega { margin-bottom: 0; }
    .mov-bodega #tableEntradaBodega > thead > tr > th { background: var(--t-fondo-alt) !important; color: var(--t-texto) !important; border-color: var(--t-borde-suave) !important; }
    .mov-bodega .mov-pie { display: flex; justify-content: flex-end; margin-top: 14px; }
    .mov-bodega .mov-pie .btn { padding: 10px 22px; }
    @media (max-width: 767px) { .mov-bodega .mov-pie .btn { width: 100%; } }
</style>

<div class="container-padding mov-bodega">
    <input type="number" class="form-control" style="display: none;" name="id_rol" id="id_rol" value="0" min="0"
        oninput="validity.valid||(value='');">

    <div class="t-card">
        <div class="t-card-titulo"><span class="mov-paso">1</span>Bodega y fecha</div>
        <fieldset class="mov-fila">
            <div class="mov-ancho">
                <label for="cboBodega">Bodega</label>
                <select id="cboBodega" nombre="cboBodega" class="form-control input-sm" style="width:100%"
                    requerido="si"></select>
            </div>
            <div>
                <label for="Fecha">Fecha</label>
                <input type="text" id="Fecha" class="form-control input-sm"
                    value="<?php echo date('d-m-Y'); ?>" />
            </div>
            <!--
            <div class="col-md-2">
                <button id="btnValidarIngreso" type="button" class="btn btn-primary"></i>Validar Stock</button>
            </div>
            -->
        </fieldset>
    </div>

    <input type="number" class="form-control" style="display: none;" name="id_rol" id="id_rol" value="0"
        min="0" oninput="validity.valid||(value='');">

    <div class="t-card">
        <div class="t-card-titulo"><span class="mov-paso">2</span>Agregar insumos</div>
        <fieldset class="mov-fila">
            <div class="mov-ancho">
                <label for="cboInsumo">Insumo</label>
                <select id="cboInsumo" nombre="cboInsumo" class="form-control input-sm"
                    style="width:100%" requerido="si"></select>
            </div>

            <div>
                <label class="label1" for="txtStockActual">Stock actual</label>
                <input type="number" disabled class="text-right form-control input-sm input-sm"
                    name="txtStockActual" id="txtStockActual" requerido="no" value="">
            </div>

            <div>
                <label for="CantidadNew">Cantidad</label>
                <input min="0" type="number" class="form-control input-sm input-sm" maxlength='28'
                    /*onkeypress="return sololetrasyNumeros(event)" */ onpaste="return false;"
                    ondrop="return false;" autocomplete="off" name="CantidadNew"
                    id="CantidadNew" requerido="si" value="" />
            </div>

            <div>
                <label for="UmedidaInsumo">U.Medida</label>
                <input disabled min="0" type="text" class="form-control input-sm input-sm"
                    maxlength='28' /*onkeypress="return sololetrasyNumeros(event)" */
                    onpaste="return false;" ondrop="return false;" autocomplete="off"
                    name="UmedidaInsumo" id="UmedidaInsumo" requerido="si" value="" />
            </div>

            <div class="mov-boton">
                <button onclick="agregarProductoALaGrilla()" id="btnAgregarALista" type="button"
                    class="btn btn-primary"><i class="fa fa-plus"></i> Agregar</button>
            </div>
        </fieldset>
    </div>

    <input type="number" class="form-control" style="display: none;" name="id_rol" id="id_rol"
        value="0" min="0" oninput="validity.valid||(value='');">

    <div class="t-card">
        <div class="t-card-titulo"><span class="mov-paso">3</span>Detalle</div>
        <fieldset>
            <div class="table-responsive">
            <table id="tableEntradaBodega" style=""
                class="table table-hover table-bordered">
                <thead class="btn-success">
                    <tr>
                        <th style='display:none;' scope="col">id Insumo</th>
                        <th style="width:35%;" class="text-center" scope="col">Insumo</th>
                        <th style='display:none;' scope="col">Stock Anterior</th>
                        <th style="width:15%" class="text-center" scope="col">Cantidad</th>
                        <th style="width:15%" class="text-center" scope="col">Quitar</th>
                    </tr>
                </thead>
                <tbody id="detalleEntradaBodega">
                </tbody>
            </table>
            </div>
        </fieldset>

        <div style="margin-top: 14px;">
            <label for="txtObservaciones">Observaciones</label>
            <textarea id="txtObservaciones"
                    class="form-control input-sm"
                    rows="3"
                    maxlength="250"
                    placeholder="Ingrese observaciones de la entrada a bodega"></textarea>
        </div>

        <input type="number" class="form-control" style="display: none;" name="id_rol"
            id="id_rol" value="0" min="0" oninput="validity.valid||(value='');">

        <div class="mov-pie">
            <button onclick="grabarEntradaBodega()" type="button"
                class="btn btn-primary"><i class="fa fa-sign-in"></i> INGRESAR A BODEGA</button>
        </div>
    </div>
</div>
