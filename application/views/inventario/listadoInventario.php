<div class="content-header">
    <div class="header-icon">
        <i class="pe-7s-box2"></i>
    </div>
    <div class="header-title">
        <h1>Inventario</h1>
        <ol class="breadcrumb">
            <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i>Inicio</a></li>
            <li class="active">Inventario</li>
        </ol>
    </div>
</div>

<div class="container-padding inv-vista">
    <div class="panel panel-success">
        <div class="panel-heading">
            <div class="panel-title">
                <i class="pe-7s-box2"></i>
                <h4>Gestión de Inventarios</h4>
            </div>
        </div>

        <div class="panel-body">
            <!-- Selector de Bodega y Botones -->
            <div class="row m-b-20">
                <div class="col-md-3">
                    <label class="inv-etiqueta">Bodega</label>
                    <select class="form-control" id="selectBodega">
                        <?php if(isset($bodegas)): ?>
                            <?php foreach($bodegas as $b): ?>
                                <option value="<?= $b->id_bodega ?>"><?= $b->nombre_bodega ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <!-- <div class="col-md-9 text-right">
                    <button class="btn btn-success" id="btnNuevoInventario">
                        <i class="fa fa-plus"></i> Nuevo Inventario
                    </button>
                    <button class="btn btn-primary" id="btnSalir">
                        <i class="fa fa-sign-out"></i> Salir (Esc)
                    </button>
                </div> -->
            </div>

            <div class="row">
                <!-- TABLA IZQUIERDA - INVENTARIOS REALIZADOS -->
                <div class="col-xs-12 col-sm-4 col-md-3">
                    <div class="inv-caja">
                    <div class="inv-caja-titulo">
                        <i class="fa fa-history"></i> Inventarios realizados
                    </div>
                    <div class="inv-scroll" style="max-height: 500px;">
                        <table class="table table-hover table-bordered table-condensed" style="margin-bottom: 0;">
                            <thead style="background: #fff;">
                                <tr>
                                    <th class="text-center" style="width: 50px;">N. Inventario</th>
                                    <th>Fecha/Hora</th>
                                    <th>Usuario</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyInventarios">
                                <!-- <?php if(isset($inventarios) && count($inventarios) > 0): ?>
                                    <?php foreach ($inventarios as $inv): ?>
                                    <tr class="inventario-row" 
                                        data-id="<?= $inv->id_inventario ?>"
                                        style="cursor: pointer;">
                                        <td class="text-center"><?= $inv->id_inventario ?></td>
                                        <td style="font-size: 11px;"><?= date('d/m/Y H:i:s', strtotime($inv->fecha_movimiento)) ?></td>
                                        <td style="font-size: 11px;"><?= strtoupper($inv->usuario) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center">
                                            <em>No hay inventarios registrados</em>
                                        </td>
                                    </tr>
                                <?php endif; ?> -->
                                <tr>
                                    <td colspan="3" class="text-center">
                                        <em>Cargando Inventarios</em>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    </div>
                </div>

                <!-- TABLA DERECHA - DETALLE -->
                <div class="col-xs-12 col-sm-8 col-md-9">
                    <div class="inv-caja">
                    <div class="inv-caja-titulo inv-caja-titulo-flex">
                        <span><i class="fa fa-list-ul"></i> Detalle</span>
                        <!-- Checkbox Solo Diferencias -->
                        <label class="inv-check">
                            <input type="checkbox" id="chkSoloDiferencias"> 
                            Sólo ver con diferencias
                        </label>
                    </div>

                    <!-- Tabla de detalle -->
                    <div class="inv-scroll" style="max-height: 450px;">
                        <table class="table table-striped table-bordered table-condensed" 
                               id="tablaDetalle" 
                               style="margin-bottom: 0; font-size: 11px;">
                            <thead style="background: #B4432E; color: white;">
                                <!-- <tr>
                                    <th class="text-center" style="width: 30px; vertical-align: middle;">
                                        <input type="checkbox" id="chkSelectAll">
                                    </th> -->
                                    <th style="vertical-align: middle;">Producto</th>
                                    <!-- <th class="text-center" style="width: 80px; vertical-align: middle;">Un. Med.</th> -->
                                    <th class="text-right" style="width: 80px; vertical-align: middle;">Actual</th>
                                    <th class="text-right" style="width: 80px; vertical-align: middle;">Nuevo</th>
                                    <th class="text-right" style="width: 80px; vertical-align: middle;">Diferencias</th>
                                    <th class="text-right" style="width: 100px; vertical-align: middle;">Costo UN.</th>
                                    <th class="text-right" style="width: 100px; vertical-align: middle;">Dif. Costo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="8" class="text-center" style="padding: 40px;">
                                        <em class="text-muted">
                                            Seleccione un inventario de la lista para ver el detalle
                                        </em>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Barra de búsqueda y total -->
                    <div class="inv-pie">
                        <div class="row">
                            <div class="col-md-5">
                                <label class="inv-etiqueta">Nombre Insumo</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" 
                                           id="txtBuscarInsumo" 
                                           placeholder="Buscar...">
                                    <span class="input-group-btn">
                                        <button class="btn btn-default" type="button">
                                            <i class="fa fa-search"></i>
                                        </button>
                                    </span>
                                </div>
                            </div>
                            <!-- <div class="col-md-1">
                                <label style="margin-bottom: 5px; font-size: 11px;">&nbsp;</label>
                                <button class="btn btn-sm btn-danger btn-block" id="btnEliminar">
                                    <i class=""></i> Eliminar
                                </button>
                            </div> -->
                            <div class="col-md-4 col-md-offset-3 text-right">
                                <div class="t-kpi inv-total">
                                    <small>Total Dif. Costo</small>
                                    <b><span class="badge badge-danger" id="totalDifCosto">
                                        0
                                    </span></b>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Inventario: dos tarjetas (historial y detalle) con cabecera suave y pie con búsqueda + total */
    .inv-vista .inv-caja { border: 1px solid var(--t-borde); border-radius: var(--t-radio); background: #fff; overflow: hidden; margin-bottom: 14px; }
    .inv-vista .inv-caja-titulo { background: var(--t-fondo-alt); border-bottom: 1px solid var(--t-borde-suave); padding: 10px 14px;
        font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: var(--t-texto-suave); }
    .inv-vista .inv-caja-titulo i { color: var(--t-primario); margin-right: 4px; }
    .inv-vista .inv-caja-titulo-flex { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px 12px; }
    .inv-vista .inv-check { margin: 0; font-weight: 600; text-transform: none; letter-spacing: 0; color: var(--t-texto); font-size: 13px; cursor: pointer; }
    .inv-vista .inv-check input { margin: 0 4px 0 0; vertical-align: -2px; }
    .inv-vista .inv-scroll { overflow: auto; }
    .inv-vista .inv-scroll .table { margin-bottom: 0; border: 0; }
    .inv-vista .inv-scroll .table > thead > tr > th { position: sticky; top: 0; z-index: 1; background: var(--t-fondo-alt) !important; color: var(--t-texto) !important; }
    .inv-vista #tablaDetalle { font-size: 12px; }
    .inv-vista .inv-pie { border-top: 1px solid var(--t-borde-suave); padding: 12px 14px 2px; background: #fff; }
    .inv-vista .inv-pie [class*="col-"] { margin-bottom: 10px; }
    .inv-vista .inv-etiqueta { font-size: 12px; text-transform: uppercase; letter-spacing: .3px; color: var(--t-texto-suave); font-weight: 700; margin-bottom: 5px; }
    .inv-vista .inv-total { display: inline-block; min-width: 200px; text-align: right; box-shadow: none; }
    .inv-vista .inv-total #totalDifCosto { background: none; color: var(--t-peligro); font-size: 22px; font-weight: 800; padding: 0; border-radius: 0; }
    .inv-vista .inv-total b { line-height: 1.2; }
    @media (max-width: 991px) {
        .inv-vista .inv-total { display: block; min-width: 0; text-align: left; }
        .inv-vista .text-right .inv-total { text-align: left; }
    }
    @media (max-width: 991px) {
        #tablaDetalle th,
        #tablaDetalle td {
            white-space: nowrap;
        }
    }

    .inventario-row { cursor: pointer; }
    .inventario-row:hover {
        background-color: var(--t-primario-suave) !important;
    }
    
    .inventario-row.selected {
        background-color: var(--t-primario) !important;
        color: white !important;
    }
    
    .inventario-row.selected td {
        color: white !important;
        font-weight: bold;
    }

    .table-condensed > thead > tr > th,
    .table-condensed > tbody > tr > th,
    .table-condensed > tfoot > tr > th,
    .table-condensed > thead > tr > td,
    .table-condensed > tbody > tr > td,
    .table-condensed > tfoot > tr > td {
        padding: 4px;
    }

    .text-right {
        text-align: right !important;
    }

    .text-center {
        text-align: center !important;
    }

    .m-b-20 {
        margin-bottom: 20px;
    }

    /* Filas con diferencias en VERDE (positivo) */
    tr.diferencia-positiva {
        background-color: #EEF3E4 !important;
    }

    /* Filas con diferencias en AMARILLO (negativo) */
    tr.diferencia-negativa {
        background-color: #FFF4DF !important;
    }

    /* Filas SIN diferencias */
    tr.sin-diferencia {
        background-color: white !important;
    }
</style>