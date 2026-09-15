<div class="content-header">
    <div class="header-icon">
        <i class="pe-7s-box2"></i>
    </div>
    <br>
    <div class="header-title">
        <h1>Inventario</h1>
        <ol class="breadcrumb">
            <li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i>Inicio</a></li>
            <li class="active">Inventario</li>
        </ol>
    </div>
</div>

<div class="container-padding">
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
                    <label>Bodega:</label>
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
                    <div style="background: #e9ecef; padding: 8px; font-weight: bold; border: 1px solid #ddd;">
                        INVENTARIOS REALIZADOS
                    </div>
                    <div style="max-height: 500px; overflow-y: auto; overflow-x: auto; border: 1px solid #ddd; border-top: none;">
                        <table class="table table-hover table-bordered table-condensed" style="margin-bottom: 0;">
                            <thead style="background: #f8f9fa;">
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

                <!-- TABLA DERECHA - DETALLE -->
                <div class="col-xs-12 col-sm-8 col-md-9">
                    <!-- Checkbox Solo Diferencias -->
                    <div style="padding: 8px; background: white; border: 1px solid #ddd;">
                        <label style="margin: 0;">
                            <input type="checkbox" id="chkSoloDiferencias"> 
                            Sólo ver con diferencias
                        </label>
                    </div>

                    <div style="background: #e9ecef; padding: 8px; font-weight: bold; border: 1px solid #ddd; border-top: none;">
                        DETALLE
                    </div>

                    <!-- Tabla de detalle -->
                    <div style="max-height: 450px; overflow-y: auto; overflow-x: auto; border: 1px solid #ddd; border-top: none;">
                        <table class="table table-striped table-bordered table-condensed" 
                               id="tablaDetalle" 
                               style="margin-bottom: 0; font-size: 11px;">
                            <thead style="background: #568c30; color: white;">
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
                    <div style="padding: 10px; background: white; border: 1px solid #ddd; border-top: none;">
                        <div class="row">
                            <div class="col-md-5">
                                <label style="margin-bottom: 5px; font-size: 11px;">Nombre Insumo</label>
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
                            <div class="col-md-4 text-right">
                                <label style="margin-bottom: 5px; font-size: 11px;">Total Dif. Costo</label>
                                <div>
                                    <span class="badge badge-danger" id="totalDifCosto" 
                                          style="font-size: 16px; padding: 8px 15px; background: #d9534f;">
                                        0
                                    </span>
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
    @media (max-width: 991px) {
        #tablaDetalle th,
        #tablaDetalle td {
            white-space: nowrap;
        }
    }

    .inventario-row:hover {
        background-color: #d4edda !important;
    }
    
    .inventario-row.selected {
        background-color: #5bc0de !important;
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
        background-color: #d4edda !important;
    }

    /* Filas con diferencias en AMARILLO (negativo) */
    tr.diferencia-negativa {
        background-color: #fff3cd !important;
    }

    /* Filas SIN diferencias */
    tr.sin-diferencia {
        background-color: white !important;
    }
</style>