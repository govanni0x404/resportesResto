<div class="content-header">
    <div class="header-icon">
        <i class="pe-7s-box2"></i>
    </div>
    <div class="header-title">
        <h1>Nuevo inventario</h1>
        <ol class="breadcrumb">
            <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i>Inicio</a></li>
            <li><a href="<?php echo base_url() ?>index.php/Inventario">Inventario</a></li>
            <li class="active">Nuevo inventario</li>
        </ol>
    </div>
</div>

<div class="container-padding inv-vista">
    <div class="panel panel-success">
        <div class="panel-heading inv-cabecera">
            <div class="panel-title">
                <i class="pe-7s-box2"></i>
                <h4>Nuevo inventario</h4>
            </div>
            <div class="inv-botones">
                <a class="btn btn-default" id="invVolver" href="<?php echo base_url() ?>index.php/Inventario"><i class="fa fa-arrow-left"></i> Volver al historial</a>
            </div>
        </div>

        <div class="panel-body">
            <div class="row">
                <div class="col-xs-12 col-sm-4 col-md-3">
                    <label class="inv-etiqueta" for="selectBodega">Bodega</label>
                    <select class="form-control" id="selectBodega">
                        <?php foreach ($bodegas as $b) : ?>
                            <option value="<?= (int)$b->id_bodega ?>"<?= (int)$b->id_bodega === (int)$bodega ? ' selected' : '' ?>><?= html_escape($b->nombre_bodega) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-xs-12 col-sm-8 col-md-9">
                    <label class="inv-etiqueta">¿Cómo se aplica la cantidad?</label>
                    <div class="inv-modos">
                        <label class="inv-modo"><input type="radio" name="modoInventario" value="sumar" checked> <b>Sumar al stock</b><small>El stock actual + la cantidad (ej. llegó mercadería)</small></label>
                        <label class="inv-modo"><input type="radio" name="modoInventario" value="contar"> <b>Dejar en lo contado</b><small>El stock queda igual a la cantidad (conteo físico)</small></label>
                    </div>
                </div>
            </div>

            <div class="inv-caja inv-nuevo">
                <div class="inv-agregar">
                    <div class="inv-buscador">
                        <label class="inv-etiqueta" for="invBuscar">Insumo</label>
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-search"></i></span>
                            <input type="text" class="form-control" id="invBuscar" autocomplete="off" placeholder="Nombre, código de barras o N° del insumo">
                        </div>
                        <div class="inv-resultados" id="invResultados"></div>
                    </div>
                    <div class="inv-elegido" id="invElegido"><span class="text-muted">Busca y elige un insumo</span></div>
                    <div class="inv-cantidad">
                        <label class="inv-etiqueta" for="invCantidad">Cantidad</label>
                        <input type="number" class="form-control" id="invCantidad" step="any" inputmode="decimal" placeholder="0" disabled>
                    </div>
                    <div class="inv-boton">
                        <button type="button" class="btn btn-success btn-block" id="invAgregar" disabled><i class="fa fa-arrow-down"></i> Agregar</button>
                    </div>
                </div>
                <div class="inv-scroll" style="max-height: 460px;">
                    <table class="table table-bordered table-condensed" id="tablaNuevo">
                        <thead>
                            <tr>
                                <th>Insumo</th>
                                <th class="text-center" style="width: 90px;">Unidad</th>
                                <th class="text-right" style="width: 110px;">Stock actual</th>
                                <th class="text-right" style="width: 130px;">Cantidad</th>
                                <th class="text-right" style="width: 110px;">Stock nuevo</th>
                                <th class="text-right" style="width: 100px;">Diferencia</th>
                                <th class="text-center" style="width: 54px;"></th>
                            </tr>
                        </thead>
                        <tbody id="tbodyNuevo"></tbody>
                    </table>
                </div>
                <div class="inv-pie inv-pie-nuevo">
                    <div class="inv-obs">
                        <label class="inv-etiqueta" for="invObservacion">Observación</label>
                        <input type="text" class="form-control" id="invObservacion" maxlength="100" placeholder="Opcional (ej. llegada de proveedor, conteo mensual)">
                    </div>
                    <div class="inv-acciones">
                        <span class="inv-cuenta" id="invCuenta">0 insumos</span>
                        <button type="button" class="btn btn-default" id="invVaciar"><i class="fa fa-eraser"></i> Vaciar lista</button>
                        <button type="button" class="btn btn-primary" id="invTraspasar" disabled><i class="fa fa-exchange"></i> Traspasar inventario</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .inv-vista .inv-cabecera { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
    .inv-vista .inv-cabecera .panel-title { display: flex; align-items: center; margin: 0; }
    .inv-vista .inv-botones { display: flex; gap: 8px; flex-wrap: wrap; }
    .inv-vista .inv-botones .btn { font-weight: 600; }
    .inv-vista .inv-botones .btn i { color: inherit; margin-right: 4px; }
    .inv-vista .inv-etiqueta { font-size: 12px; text-transform: uppercase; letter-spacing: .3px; color: var(--t-texto-suave); font-weight: 700; margin-bottom: 5px; }
    .inv-vista .inv-caja { border: 1px solid var(--t-borde); border-radius: var(--t-radio); background: #fff; margin-bottom: 14px; }
    .inv-vista .inv-scroll { overflow: auto; }
    .inv-vista .inv-scroll .table { margin-bottom: 0; border: 0; }
    .inv-vista .inv-scroll .table > thead > tr > th { position: sticky; top: 0; z-index: 1; background: var(--t-fondo-alt) !important; color: var(--t-texto) !important; }
    .inv-vista .inv-modos { display: flex; gap: 10px; flex-wrap: wrap; }
    .inv-vista .inv-modo { flex: 1 1 240px; display: flex; flex-wrap: wrap; align-items: baseline; gap: 2px 8px; margin: 0; padding: 9px 12px; border: 1px solid var(--t-borde);
        border-radius: var(--t-radio-sm); background: var(--t-fondo-alt); cursor: pointer; font-weight: 400; }
    .inv-vista .inv-modo input { margin: 0; }
    .inv-vista .inv-modo small { flex-basis: 100%; color: var(--t-texto-tenue); font-size: 12px; padding-left: 20px; }
    .inv-vista .inv-modo:has(input:checked) { border-color: var(--t-primario); background: var(--t-primario-suave); }
    .inv-vista .inv-nuevo { margin-top: 16px; }
    .inv-vista .inv-agregar { display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; padding: 14px; border-bottom: 1px solid var(--t-borde-suave); }
    .inv-vista .inv-buscador { position: relative; flex: 2 1 300px; }
    .inv-vista .inv-elegido { flex: 2 1 240px; min-height: 34px; display: flex; flex-direction: column; justify-content: center; padding: 4px 10px;
        border: 1px dashed var(--t-borde); border-radius: var(--t-radio-sm); font-size: 13px; }
    .inv-vista .inv-elegido b { font-size: 14px; }
    .inv-vista .inv-elegido small { color: var(--t-texto-suave); }
    .inv-vista .inv-cantidad { flex: 0 1 140px; }
    .inv-vista .inv-cantidad input { font-size: 16px; font-weight: 700; text-align: right; }
    .inv-vista .inv-boton { flex: 0 1 130px; }
    .inv-vista .inv-resultados { display: none; position: absolute; left: 0; right: 0; top: 100%; z-index: 30; max-height: 300px; overflow: auto; background: #fff;
        border: 1px solid var(--t-borde); border-radius: 0 0 var(--t-radio-sm) var(--t-radio-sm); box-shadow: 0 8px 20px rgba(0,0,0,.12); }
    .inv-vista .inv-resultados .inv-res { display: flex; justify-content: space-between; gap: 10px; padding: 8px 12px; cursor: pointer; border-bottom: 1px solid var(--t-borde-suave); }
    .inv-vista .inv-resultados .inv-res:hover, .inv-vista .inv-resultados .inv-res.activo { background: var(--t-primario-suave); }
    .inv-vista .inv-resultados .inv-res small { color: var(--t-texto-suave); white-space: nowrap; }
    .inv-vista .inv-resultados .inv-nada { padding: 10px 12px; color: var(--t-texto-tenue); }
    .inv-vista #tablaNuevo { font-size: 13px; }
    .inv-vista #tablaNuevo td { vertical-align: middle; }
    .inv-vista #tablaNuevo .inv-cant { width: 110px; margin-left: auto; text-align: right; font-weight: 700; height: 30px; padding: 4px 8px; }
    .inv-vista #tablaNuevo .positivo { color: #2e7d32; font-weight: 700; }
    .inv-vista #tablaNuevo .negativo { color: var(--t-peligro); font-weight: 700; }
    .inv-vista .inv-pie-nuevo { display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; padding: 12px 14px; border-top: 1px solid var(--t-borde-suave); }
    .inv-vista .inv-obs { flex: 1 1 280px; }
    .inv-vista .inv-acciones { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .inv-vista .inv-acciones .btn { font-weight: 600; }
    .inv-vista .inv-acciones .btn i { margin-right: 4px; }
    .inv-vista #invTraspasar { padding: 8px 18px; }
    .inv-vista .inv-cuenta { color: var(--t-texto-suave); font-size: 13px; margin-right: 4px; }
    @media (max-width: 767px) {
        .inv-vista .inv-cantidad, .inv-vista .inv-boton { flex: 1 1 45%; }
        .inv-vista .inv-acciones, .inv-vista .inv-acciones .btn, .inv-vista .inv-botones, .inv-vista .inv-botones .btn { width: 100%; }
    }
</style>
