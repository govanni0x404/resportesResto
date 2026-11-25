<?php $this->load->view('umedida/jsEquivalencias.php'); /* v1.34.0 */ ?>
<style>
	/* Formularios en modales (.t-form): filas flexibles en vez de floats + <br>, etiquetas claras y móvil a una columna */
	.t-form fieldset { display: flex; flex-wrap: wrap; align-items: flex-end; margin: 0 -8px; min-width: 0; }
	.t-form fieldset > * { flex: 0 0 100%; max-width: 100%; padding-left: 8px; padding-right: 8px; }
	.t-form fieldset > [class*="col-"] { float: none; flex: 0 0 auto; margin-bottom: 12px; }
	.t-form fieldset > .table-responsive, .t-form fieldset > .nav-tabs { margin-left: 8px; margin-right: 8px; max-width: calc(100% - 16px); padding-left: 0; padding-right: 0; }
	.t-form label { font-size: 12px; text-transform: uppercase; letter-spacing: .3px; color: var(--t-texto-suave); font-weight: 700; margin-bottom: 5px; }
	.t-form .checkbox label { text-transform: none; letter-spacing: 0; font-size: 13px; color: var(--t-texto); }
	.t-form label.t-card-titulo { display: block; margin: 4px 0 10px; padding-bottom: 6px; border-bottom: 1px solid var(--t-borde-suave); }
	.t-form .form-control.input-sm { height: 36px; font-size: 14px; }
	.t-form textarea, .t-form textarea.form-control { border: 1px solid var(--t-borde, #EADFD2); border-radius: var(--t-radio-sm); padding: 8px 10px; height: auto; }
	.t-form textarea:focus { outline: none; border-color: var(--t-primario); box-shadow: 0 0 0 3px rgba(180, 67, 46, .12); }
	.t-form sup { top: -.3em; }
	.t-form sup a { cursor: pointer; font-weight: 600; text-transform: none; color: var(--t-primario); margin-left: 4px; }
	.t-form .nav-tabs { margin-bottom: 14px; }
	.t-form .tab-pane > fieldset:first-child { margin-top: 2px; }
	.modal-footer .btn i[class=""] { display: none; }
	@media (max-width: 991px) {
		.t-form fieldset > [class*="col-md-"]:not([class*="col-sm-"]):not([class*="col-xs-"]) { width: 100%; }
		.t-form fieldset > .col-md-1:empty, .t-form fieldset > .col-md-2:empty, .t-form fieldset > .col-md-5:empty, .t-form fieldset > .col-md-8:empty { display: none; }
	}
</style>
<style>
    /* Modal de producción: secciones en tarjetas, total destacado y cabecera legible con el tema blanco */
    #ModalRealizarProduccion .modal-header .close, #ModalCantidadProduccion .modal-header .close { color: var(--t-texto-tenue) !important; }
    #ModalRealizarProduccion .panel { box-shadow: none; margin-bottom: 14px; }
    #ModalRealizarProduccion .panel > .panel-heading { padding: 10px 16px; }
    #ModalRealizarProduccion .panel > .panel-heading h5 { margin: 0; font-size: 12px; text-transform: uppercase; letter-spacing: .4px; color: var(--t-texto-suave); }
    #ModalRealizarProduccion .panel > .panel-heading h5 strong { font-weight: 700 !important; }
    #ModalRealizarProduccion .panel-elaborado { border-color: var(--t-primario-borde); }
    #ModalRealizarProduccion .panel-elaborado > .panel-heading { background: var(--t-primario-suave) !important; }
    #ModalRealizarProduccion .panel-elaborado > .panel-heading h5 { color: var(--t-primario); }
    #ModalRealizarProduccion #tableInsumosRecetaProduccion { margin-bottom: 0; }
    #ModalRealizarProduccion #tableInsumosRecetaProduccion > thead > tr > th { background: var(--t-fondo-alt) !important; color: var(--t-texto) !important; }
    #ModalRealizarProduccion .prod-total { display: inline-block; min-width: 220px; text-align: right; margin: 0; }
    #ModalRealizarProduccion .prod-total b { font-size: 26px; }
    #ModalRealizarProduccion #costoUnitarioElaborado { background-color: var(--t-primario-suave) !important; color: var(--t-primario) !important;
        border-color: var(--t-primario-borde); font-size: 16px; }
    #ModalRealizarProduccion .panel-body .row > [class*="col-"] { margin-bottom: 10px; }
    @media (max-width: 991px) {
        #ModalRealizarProduccion .botones-produccion { margin-top: 0; text-align: left; }
        #ModalRealizarProduccion .prod-total { display: block; min-width: 0; text-align: left; }
    }
</style>
<!-- MODAL REALIZAR PRODUCCION -->
<div id="ModalRealizarProduccion" class="modal fade" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <!-- TITULO MODAL -->
            <div class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title text-center">PRODUCCIÓN</h4>
            </div>

            <!-- CONTENIDO MODAL -->
            <div class="modal-body t-form">
                <div class="h-panel">
                    <!-- DATOS DE PRODUCCION -->
                    <div class="row">
                        <div class="col-md-12">
                            <fieldset>
                                <div class="col-md-3">
                                    <label for="idProduccion">ID (Automático)</label>
                                    <input type="number" disabled class="form-control input-sm" 
                                           name="idProduccion" id="idProduccion" value="" />
                                </div>
                                <div class="col-md-3">
                                    <label for="fechaProduccion">FECHA PROCESO *</label>
                                    <input type="text" class="form-control input-sm" 
                                           name="fechaProduccion" id="fechaProduccion" 
                                           value="<?php echo date('d-m-Y'); ?>" />
                                </div>
                            </fieldset>
                        </div>
                    </div>


                    <!-- SECCION INSUMOS Y COSTOS -->
                    <div class="panel panel-default">
                        <div class="panel-heading prod-cabeza">
                            <h5><strong style="font-weight: bold;">INSUMOS Y COSTOS</strong></h5>
                            <!-- v1.34.0 (pedido del usuario): carga los insumos de la receta de producción del insumo -->
                            <button type="button" class="btn btn-success btn-sm" id="btnCargarRecetaProduccion"
                                    title="Carga los insumos de la receta de producción, según la cantidad a producir (Rend/Real)">
                                <i class="fa fa-list-alt"></i> Cargar receta de producción
                            </button>
                        </div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <fieldset>
                                        <div class="col-md-4">
                                            <label for="cboInsumoProduccion">INSUMO *</label>
                                            <!-- <div class="input-group"> -->
                                                <select id="cboInsumoProduccion" name="cboInsumoProduccion" 
                                                        class="form-control input-sm" style="width:100%">
                                                    <option value="0">-- Seleccione --</option>
                                                </select>
                                                <!-- <span class="input-group-btn">
                                                    <button class="btn btn-success btn-sm" type="button" 
                                                            style="height: 30px;" title="Buscar Receta"
                                                            id="btnBuscarRecetaProduccion">
                                                        <i class="fa fa-search"></i>
                                                    </button>
                                                </span> -->
                                            
                                        </div>
                                        <div class="col-md-2">
                                            <label for="cboBodegaOrigenProduccion">BODEGA ORIGEN *</label>
                                            <select id="cboBodegaOrigenProduccion" name="cboBodegaOrigenProduccion" 
                                                    class="form-control input-sm" style="width:100%">
                                                <option value="0">-- Seleccione --</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label for="stockBodegaProduccion">STOCK BODEGA</label>
                                            <input type="number" disabled class="form-control input-sm" 
                                                   name="stockBodegaProduccion" id="stockBodegaProduccion" value="0" />
                                        </div>
                                        <div class="col-md-2">
                                            <label for="umedidaInvProduccion">U/M INV.</label>
                                            <input type="text" disabled class="form-control input-sm" 
                                                   name="umedidaInvProduccion" id="umedidaInvProduccion" value="" />
                                        </div>
                                        <div class="col-md-2">
                                            <label for="cantidadRecetaProduccion">CANTIDAD *</label>
                                            <input type="number" class="form-control input-sm" 
                                                   onkeypress="return soloNumerosDecimales(event)"
                                                   name="cantidadRecetaProduccion" id="cantidadRecetaProduccion" 
                                                   value="" placeholder="0" />
                                            <div id="equivProduccion" class="prod-equiv" hidden></div>
                                        </div>
                                    </fieldset>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <fieldset>
                                        
                                        <div class="col-md-2">
                                            <!-- v1.34.0: se puede escribir en kilos o gramos; se descuenta en la unidad de inventario -->
                                            <label for="unidadProduccion">UNIDAD</label>
                                            <select id="unidadProduccion" class="form-control input-sm" disabled></select>
                                            <input type="hidden" name="umedidaRecetaProduccion" id="umedidaRecetaProduccion" value="" />
                                        </div>
                                        <div class="col-md-2">
                                            <label for="costoNetoProduccion">C/ NETO UN(PPP)</label>
                                            <input type="number" disabled class="form-control input-sm" 
                                                   name="costoNetoProduccion" id="costoNetoProduccion" value="0" />
                                        </div>
                                        <div class="col-md-2">
                                            <label for="costoTotalProduccion">COSTO TOTAL</label>
                                            <input type="number" disabled class="form-control input-sm" 
                                                   name="costoTotalProduccion" id="costoTotalProduccion" value="0" />
                                        </div>
                                        <div class="col-md-6 text-right botones-produccion">
                                            <div class="btn-group">
                                                <button class="btn btn-success btn-sm">
                                                    <i class=""></i> Agregar
                                                </button>
                                                <button class="btn btn-default btn-sm">
                                                    <i class=""></i> Nuevo
                                                </button>
                                                <button class="btn btn-danger btn-sm">
                                                    <i class=""></i> Quitar
                                                </button>
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>
                            </div>

                            <!-- <div class="row">
                                <div class="col-md-12">
                                    <fieldset>
                                        <div class="col-md-3">
                                            <label for="cboBodegaDestinoProduccion">BODEGA DESTINO</label>
                                            <select id="cboBodegaDestinoProduccion" name="cboBodegaDestinoProduccion" 
                                                    class="form-control input-sm" style="width:100%">
                                                <option value="0">-- Seleccione --</option>
                                            </select>
                                        </div>
                                    </fieldset>
                                </div>
                            </div> -->
                        </div>
                    </div>

                    <!-- TABLA DE INSUMOS DE LA RECETA -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel panel-default">
                                <div class="panel-body" style="padding: 5px;">
                                    <div class="table-responsive">
                                        <table id="tableInsumosRecetaProduccion" class="table table-bordered table-hover table-sm" style="font-size: 12px;">
                                            <thead style="background-color: #B4432E; color: white;">
                                                <tr>
                                                    <th class="text-center" style="width: 5%;">N</th>
                                                    <th class="text-left" style="width: 15%;">Insumo</th>
                                                    <th class="text-center" style="width: 7%;">Cant/Receta</th>
                                                    <th class="text-center" style="width: 7%;">U/M Receta</th>
                                                    <th class="text-center" style="width: 7%;" title="Lo que se descuenta del stock, en la unidad de inventario">Cant/Inv.</th>
                                                    <th class="text-center" style="width: 8%;">U/M Inv.</th>
                                                    <th class="text-right" style="width: 7%;">Costo Neto Un(PPP)</th>
                                                    <th class="text-right" style="width: 10%;">Costo Total</th>
                                                    <th class="text-center" style="width: 10%;">Bodega Origen</th>
                                                    <th class="text-center" style="width: 5%;">Stock</th>
                                                </tr>
                                            </thead>
                                            <tbody id="detalleInsumosRecetaProduccion">
                                                <tr>
                                                    <td colspan="10" class="text-center">No hay datos</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CANTIDAD Y OBSERVACIONES -->
                    <div class="row">
                        <div class="col-md-12">
                            <fieldset>
                                <div class="col-md-2">
                                    <label>CANTIDAD:</label>
                                    <input type="number" disabled class="form-control input-sm" 
                                           id="cantidadTotalProduccion" value="1" />
                                </div>
                                <div class="col-md-10 text-right">
                                    <div class="t-kpi destacado prod-total">
                                        <small>Costo total</small>
                                        <b><span id="costoTotalFinal">0</span></b>
                                    </div>
                                </div>
                            </fieldset>
                            <fieldset>
                                <div class="col-md-12">
                                    <label>OBSERVACIONES</label>
                                    <textarea class="form-control input-sm" rows="2" 
                                              id="observacionesProduccion" name="observacionesProduccion"
                                              maxlength="300" style="resize: none;"></textarea>
                                </div>
                                
                            </fieldset>
                        </div>
                    </div>


                    <!-- INSUMO ELABORADO -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel panel-default panel-elaborado">
                                <div class="panel-heading" style="background-color: #B4432E; color: white;">
                                    <h5><strong>INSUMO ELABORADO</strong></h5>
                                </div>
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label>Nombre del Insumo</label>
                                            <!-- <div class="input-group"> -->
                                                <input type="text" disabled class="form-control input-sm" 
                                                       id="nombreInsumoElaborado" value="" />
                                                <!-- <span class="input-group-btn">
                                                    <button class="btn btn-success btn-sm" type="button" 
                                                            style="height: 30px;">
                                                        <i class="fa fa-check"></i>
                                                    </button>
                                                </span> -->
                                        </div>
                                        <div class="col-md-2">
                                            <label>Rend/Real</label>
                                            <input type="text" class="form-control input-sm" 
                                                   id="rendimientoReal" value="1" />
                                        </div>
                                        <div class="col-md-2">
                                            <label>U/M Inven.</label>
                                            <input type="text" disabled class="form-control input-sm" 
                                                   id="umedidaInventarioElaborado" value="" />
                                        </div>
                                        <div class="col-md-2">
                                            <label>Bodega Destino</label>
                                            <select class="form-control input-sm" id="bodegaDestinoElaborado">
                                                <option value="0">-- Seleccione --</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label>Costo Unitario</label>
                                            <input type="text" disabled class="form-control input-sm" 
                                                   id="costoUnitarioElaborado" value="0.00" 
                                                   style="background-color: var(--t-primario-suave, #FBEEE8); color: var(--t-primario, #B4432E); border-color: var(--t-primario-borde, #EFD2C6); font-weight: bold;" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- COSTO TOTAL -->
                    
                </div>
            </div>
            <!-- FIN CONTENIDO MODAL -->

            <!-- BOTONES -->
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="btnGrabarProduccion">
                    <i class=""></i> GRABAR DATOS
                </button>
                <!-- <button type="button" class="btn btn-info" id="btnLimpiarProduccion">
                    <i class="fa fa-eraser"></i> LIMPIAR
                </button> -->
                <!-- <button type="button" class="btn btn-success" id="btnBuscarReceta">
                    BUSCAR RECETA
                </button> -->
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class=""></i> SALIR
                </button>
            </div>
            <!-- FIN BOTONES -->
        </div>
    </div>
</div>
<style>
    /* v1.34.0 */
    .prod-cabeza { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
    .prod-cabeza h5 { margin: 0; }
    .prod-equiv { margin-top: 4px; font-size: 12px; color: var(--t-primario, #15803D); }
    .prod-equiv.mal { color: #C9372C; }
    #tableInsumosRecetaProduccion tr.sin-stock td { color: #C9372C; }
    .botones-produccion {
    margin-top: 24px;   /* baja los botones */
}

.botones-produccion .btn {
    min-width: 90px;
}
</style>
<!-- FIN MODAL REALIZAR PRODUCCION -->

<script>
var base_url = "<?php echo base_url(); ?>";

// Variables globales
var insumosReceta = [];
var contadorInsumos = 0;
var idInsumoElaborado = 0;
// v1.34.0: unidades del insumo elegido ({id_umedida_receta, id_umedida_inventario, umedida_receta, umedida_inventario,
// nombre_umedida_receta, nombre_umedida_inventario}) y la receta de producción cargada (para reescalar con Rend/Real)
var insProd = null;
var recetaCargada = null;

// Abrir modal de producción
function AbrirModalProduccion(idInsumo, nombreInsumo) {
    console.log("=== ABRIENDO MODAL DE PRODUCCIÓN ===");
    console.log("ID Insumo:", idInsumo);
    console.log("Nombre Insumo:", nombreInsumo);
    
    idInsumoElaborado = idInsumo;
    limpiarModalProduccion();
    
    // Cargar combos
    cargarBodegasParaProduccion();
    cargarInsumosParaProduccion(idInsumo);
    
    // Configurar datepicker
    $('#fechaProduccion').daterangepicker({
        singleDatePicker: true,
        autoApply: true,
        startDate: moment($('#fechaProduccion').val(), 'DD-MM-YYYY'),
        locale: {
            format: 'DD-MM-YYYY',
            daysOfWeek: ["Do", "Lu", "Ma", "Mi", "Ju", "Vi", "Sa"],
            monthNames: ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
                "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"],
            firstDay: 1
        }
    }).on('apply.daterangepicker', function (ev, picker) {
        $(this).val(picker.startDate.format('DD-MM-YYYY'));
    });
    
    $('#ModalRealizarProduccion').modal({
        backdrop: 'static',
        keyboard: false
    });
}

// Limpiar modal
function limpiarModalProduccion() {
    $('#idProduccion').val("");
    $('#cantidadRecetaProduccion').val("");
    $('#stockBodegaProduccion').val("0");
    $('#umedidaInvProduccion').val("");
    $('#umedidaRecetaProduccion').val("");
    $('#costoNetoProduccion').val("0");
    $('#costoTotalProduccion').val("0");
    $('#observacionesProduccion').val("");
    $('#nombreInsumoElaborado').val("");
    $('#rendimientoReal').val("1");
    $('#umedidaInventarioElaborado').val("");
    $('#costoUnitarioElaborado').val("0.00");
    $('#cboBodegaOrigenProduccion').val(0);
    $('#bodegaDestinoElaborado').val(0);
    
    insumosReceta = [];
    contadorInsumos = 0;
    insProd = null;
    recetaCargada = null;
    $('#unidadProduccion').empty().prop('disabled', true);
    $('#equivProduccion').prop('hidden', true);
    actualizarTablaInsumos();
    actualizarCostoTotal();
}

// v1.34.0: datos de conversión de un insumo (lo que devuelve obtenerDatosInsumoCompleto o la receta de producción)
function insumoConversion(o) {
    return { id_umedida_receta: o.id_umedida_receta, id_umedida_inventario: o.id_umedida_inventario,
        umedida_receta: o.m_receta, umedida_inventario: o.m_inventario,
        nombre_umedida_receta: o.nombre_umedida_receta || o.umedida_receta || '',
        nombre_umedida_inventario: o.nombre_umedida_inventario || o.umedida_inventario || '' };
}
function pintarUnidadesProduccion() {
    var $u = $('#unidadProduccion').empty();
    if (!insProd) { $u.prop('disabled', true); calcularCostoTotal(); return; }
    Unidades.listo(function () {
        $u.empty();
        $.each(Unidades.delInsumo(insProd), function (i, u) { $u.append($('<option>').val(u.id).text(u.nombre || insProd.nombre_umedida_receta)); });
        if (!$u.children().length) $u.append($('<option>').val(insProd.id_umedida_receta).text(insProd.nombre_umedida_receta));
        $u.val(String(insProd.id_umedida_receta)).prop('disabled', $u.children().length < 2);
        calcularCostoTotal();
    });
}
$(document).on('change', '#unidadProduccion', function () { calcularCostoTotal(); });
/** Lo escrito: {receta: cantidad en la unidad de receta, inv: en la de inventario} o null si no se puede convertir. */
function cantidadesProduccion() {
    if (!insProd) return null;
    var cant = parseFloat($('#cantidadRecetaProduccion').val()) || 0;
    var rec = Unidades.aReceta(insProd, cant, $('#unidadProduccion').val());
    if (rec === null) return null;
    rec = Math.round(rec * 1000) / 1000;
    return { escrita: cant, receta: rec, inv: Math.round(Unidades.recetaAInventario(insProd, rec) * 1000000) / 1000000 };
}

// Cargar insumos
function cargarInsumosParaProduccion(idInsumoSeleccionado) {
    $.post(base_url + "index.php/Insumos/ListarInsumos", {}, function(datos) {
        var obj = jQuery.parseJSON(datos);
        var tbl = '<option value="0">-- Seleccione --</option>';
        
        $.each(obj, function(index, v) {
            var selected = (v.id_insumo == idInsumoSeleccionado) ? 'selected' : '';
            tbl += '<option value="' + v.id_insumo + '" ' + selected + '>' + v.nombre_insumo + '</option>';
        });
        
        $("#cboInsumoProduccion").html(tbl);
        
        // Destruir select2 si existe y reinicializar
        if ($("#cboInsumoProduccion").data('select2')) {
            $("#cboInsumoProduccion").select2('destroy');
        }
        $("#cboInsumoProduccion").select2();
        
        // Si hay insumo seleccionado, cargar sus datos
        if (idInsumoSeleccionado && idInsumoSeleccionado > 0) {
            setTimeout(function() {
                $('#cboInsumoProduccion').val(idInsumoSeleccionado).trigger('change');
                cargarDatosInsumoElaborado(idInsumoSeleccionado);
            }, 100);
        }
    });
}

// Cargar bodegas
function cargarBodegasParaProduccion() {
    $.post(base_url + "index.php/Bodegas/ListadoDeBodegas", {}, function (obj) {
        var tbl = '<option value="0">-- Seleccione --</option>';
        
        $.each(obj.aaData, function (index, v) {
            tbl += '<option value="' + v.id_bodega + '">' + v.nombre_bodega + '</option>';
        });
        
        $("#cboBodegaOrigenProduccion").html(tbl);
        $("#bodegaDestinoElaborado").html(tbl);
        
        // Reinicializar select2
        if ($("#cboBodegaOrigenProduccion").data('select2')) {
            $("#cboBodegaOrigenProduccion").select2('destroy');
        }
        $("#cboBodegaOrigenProduccion").select2();
        
        if ($("#bodegaDestinoElaborado").data('select2')) {
            $("#bodegaDestinoElaborado").select2('destroy');
        }
        $("#bodegaDestinoElaborado").select2();
    }, 'json');
}

// Cargar datos del insumo elaborado (producto final)
function cargarDatosInsumoElaborado(idInsumo) {
    console.log("=== Cargando datos insumo elaborado ===");
    console.log("ID Insumo:", idInsumo);
    
    $.post(base_url + "index.php/Produccion/obtenerDatosInsumoCompleto", {
        id_insumo: idInsumo
    }, function(datos) {
        console.log("Datos insumo elaborado recibidos:", datos);
        
        var obj = typeof datos === 'string' ? jQuery.parseJSON(datos) : datos;
        
        if (obj) {
            var unidadMedida = obj.abreviatura || obj.nombre_unidad_medida || '';
            console.log("Nombre insumo elaborado:", obj.nombre_insumo);
            console.log("Unidad medida elaborado:", unidadMedida);
            
            $('#nombreInsumoElaborado').val(obj.nombre_insumo);
            $('#umedidaInventarioElaborado').val(obj.umedida_inventario || '');
        }
    }).fail(function(xhr, status, error) {
        console.error("Error al cargar datos del insumo elaborado:", error);
    });
}

// Cuando cambia el insumo en el combo
$('#cboInsumoProduccion').on('change', function() {
    var idInsumo = $(this).val();
    
    console.log("=== Cambió insumo ===");
    console.log("ID Insumo:", idInsumo);
    
    if (idInsumo > 0) {
        $.post(base_url + "index.php/Produccion/obtenerDatosInsumoCompleto", {
            id_insumo: idInsumo
        }, function(datos) {
            console.log("Datos del insumo recibidos:", datos);
            
            var obj = typeof datos === 'string' ? jQuery.parseJSON(datos) : datos;
            
            if (obj) {
                $('#umedidaInvProduccion').val(obj.umedida_inventario || '');
                $('#umedidaRecetaProduccion').val(obj.umedida_receta || '');
                insProd = insumoConversion(obj);   // v1.34.0
                pintarUnidadesProduccion();
            }
            
            // Si hay bodega seleccionada, cargar stock y costo
            var idBodega = $('#cboBodegaOrigenProduccion').val();
            if (idBodega > 0) {
                cargarStockYCosto(idInsumo, idBodega);
            }
        }).fail(function(xhr, status, error) {
            console.error("Error al obtener datos del insumo:", error);
            console.error("XHR:", xhr.responseText);
        });
    } else {
        insProd = null;   // v1.34.0
        pintarUnidadesProduccion();
        $('#umedidaInvProduccion').val("");
        $('#umedidaRecetaProduccion').val("");
        $('#stockBodegaProduccion').val("0");
        $('#costoNetoProduccion').val("0");
        $('#costoTotalProduccion').val("0");
    }
});

// Cuando cambia la bodega
$('#cboBodegaOrigenProduccion').on('change', function() {
    var idBodega = $(this).val();
    var idInsumo = $('#cboInsumoProduccion').val();
    
    console.log("=== Cambió bodega ===");
    console.log("ID Bodega:", idBodega);
    console.log("ID Insumo:", idInsumo);
    
    if (idInsumo > 0 && idBodega > 0) {
        cargarStockYCosto(idInsumo, idBodega);
    } else {
        $('#stockBodegaProduccion').val("0");
        $('#costoNetoProduccion').val("0");
        $('#costoTotalProduccion').val("0");
    }
});

// Cargar stock y costo PPP
function cargarStockYCosto(idInsumo, idBodega) {
    console.log("=== Cargando stock y costo ===");
    console.log("ID Insumo:", idInsumo);
    console.log("ID Bodega:", idBodega);
    
    // Obtener stock
    $.post(base_url + "index.php/Produccion/obtenerStockActual", {
        id_insumo: idInsumo,
        id_bodega: idBodega
    }, function(datos) {
        console.log("Stock recibido:", datos);
        var obj = typeof datos === 'string' ? jQuery.parseJSON(datos) : datos;
        var stock = parseFloat(obj.stock_actual || 0);
        $('#stockBodegaProduccion').val(stock.toFixed(2));
        console.log("Stock establecido:", stock);
    }).fail(function(xhr, status, error) {
        console.error("Error al obtener stock:", error);
    });
    
    // Obtener costo PPP
    $.post(base_url + "index.php/Produccion/obtenerCostoPPP", {
        id_insumo: idInsumo
    }, function(datos) {
        console.log("Costo PPP recibido:", datos);
        var obj = typeof datos === 'string' ? jQuery.parseJSON(datos) : datos;
        var costo = 0;

        if (obj && obj.costo_ppp !== null && obj.costo_ppp !== undefined) {
            costo = parseFloat(obj.costo_ppp);
        }

        $('#costoNetoProduccion').val(costo.toFixed(2));
        calcularCostoTotal();

        console.log("Costo PPP establecido:", costo);
        
        // Recalcular costo total si hay cantidad
        calcularCostoTotal();
    }).fail(function(xhr, status, error) {
        console.error("Error al obtener costo PPP:", error);
    });
}

// Calcular costo total cuando cambia la cantidad
$('#cantidadRecetaProduccion').on('input', function() {
    console.log("Cambió cantidad a:", $(this).val());
    calcularCostoTotal();
});

function calcularCostoTotal() {
    // v1.34.0: el costo PPP es por unidad de INVENTARIO (ej. el kilo): se multiplica por lo que se descuenta del stock
    var c = cantidadesProduccion(), costoNeto = parseFloat($('#costoNetoProduccion').val()) || 0, $h = $('#equivProduccion');
    $('#costoTotalProduccion').val(c ? (c.inv * costoNeto).toFixed(2) : '0');
    if (c && c.escrita > 0 && insProd) {
        var stock = parseFloat($('#stockBodegaProduccion').val()) || 0, falta = c.inv > stock + 0.0000001;
        $h.toggleClass('mal', falta).html('<i class="fa fa-exchange"></i> Se descuentan <b>' + Unidades.fmt(c.inv) + ' ' + insProd.nombre_umedida_inventario + '</b>'
            + (falta ? ' (hay ' + Unidades.fmt(stock) + ')' : '')).prop('hidden', false);
    } else if (insProd && parseFloat($('#cantidadRecetaProduccion').val()) > 0) {
        $h.addClass('mal').html('No se puede convertir esa unidad a la del insumo').prop('hidden', false);
    } else $h.prop('hidden', true);
}

// BOTÓN AGREGAR
$('.btn-success:contains("Agregar")').on('click', function(e) {
    e.preventDefault();
    
    console.log("=== BOTÓN AGREGAR CLICKEADO ===");
    
    var idInsumo = $('#cboInsumoProduccion').val();
    var nombreInsumo = $('#cboInsumoProduccion option:selected').text();
    var idBodega = $('#cboBodegaOrigenProduccion').val();
    var nombreBodega = $('#cboBodegaOrigenProduccion option:selected').text();
    var cant = cantidadesProduccion();   // v1.34.0: {receta, inv}
    var cantidad = cant ? cant.receta : 0;
    var umedidaReceta = $('#umedidaRecetaProduccion').val();
    var umedidaInv = $('#umedidaInvProduccion').val();
    var costoNeto = parseFloat($('#costoNetoProduccion').val()) || 0;
    var costoTotal = parseFloat($('#costoTotalProduccion').val()) || 0;
    var stock = parseFloat($('#stockBodegaProduccion').val()) || 0;
    
    console.log("Datos a agregar:");
    console.log("- ID Insumo:", idInsumo);
    console.log("- Nombre:", nombreInsumo);
    console.log("- ID Bodega:", idBodega);
    console.log("- Bodega:", nombreBodega);
    console.log("- Cantidad:", cantidad);
    console.log("- U/M Receta:", umedidaReceta);
    console.log("- U/M Inv:", umedidaInv);
    console.log("- Costo Neto:", costoNeto);
    console.log("- Costo Total:", costoTotal);
    console.log("- Stock:", stock);
    
    // Validaciones
    if (idInsumo == 0) {
        showAppAlert('error', "Seleccione un insumo", {
            duration: 3000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }
    
    if (idBodega == 0) {
        showAppAlert('error', "Seleccione una bodega de origen", {
            duration: 3000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }
    
    if (cantidad <= 0) {
        showAppAlert('error', "Ingrese una cantidad válida", {
            duration: 3000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }
    
    if (cant.inv > stock + 0.0000001) {
        showAppAlert('error', "Se necesitan " + Unidades.fmt(cant.inv) + " " + umedidaInv + " y en la bodega hay " + Unidades.fmt(stock), {
            duration: 3000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }
    
    // Agregar al array
    contadorInsumos++;
    var insumo = {
        id: contadorInsumos,
        id_insumo: idInsumo,
        nombre_insumo: nombreInsumo,
        id_bodega: idBodega,
        nombre_bodega: nombreBodega,
        cantidad: cantidad,                 // en la unidad de receta (lo que recibe el servidor)
        cantidad_inv: cant.inv,             // lo que se descuenta del stock
        conv: insProd,
        umedida_receta: umedidaReceta,
        umedida_inv: umedidaInv,
        costo_neto: costoNeto,
        costo_total: costoTotal,
        stock: stock
    };
    
    console.log("Insumo agregado:", insumo);
    insumosReceta.push(insumo);
    console.log("Total insumos:", insumosReceta.length);
    
    // Actualizar tabla y totales
    actualizarTablaInsumos();
    actualizarCostoTotal();
    
    // Limpiar campos
    limpiarCamposInsumo();
    
    showAppAlert('success', "Insumo agregado correctamente", {
        duration: 2000,
        image: base_url + 'assets/img/success.gif'
    });
});

// BOTÓN NUEVO
$('.btn-default:contains("Nuevo")').on('click', function(e) {
    e.preventDefault();
    limpiarCamposInsumo();
});

// BOTÓN QUITAR
$('.btn-danger:contains("Quitar")').on('click', function(e) {
    e.preventDefault();
    
    var filaSeleccionada = $('#tableInsumosRecetaProduccion tbody tr.selected');
    
    if (filaSeleccionada.length === 0) {
        showAppAlert('error', "Seleccione un insumo de la tabla para eliminar", {
            duration: 3000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }
    
    var idFila = filaSeleccionada.data('id');
    
    insumosReceta = insumosReceta.filter(function(item) {
        return item.id !== idFila;
    });
    
    actualizarTablaInsumos();
    actualizarCostoTotal();
    
    showAppAlert('success', "Insumo eliminado correctamente", {
        duration: 2000,
        image: base_url + 'assets/img/success.gif'
    });
});

// Seleccionar fila
$(document).on('click', '#tableInsumosRecetaProduccion tbody tr', function() {
    if ($(this).find('td').length === 1) return;
    
    $('#tableInsumosRecetaProduccion tbody tr').removeClass('selected');
    $(this).addClass('selected');
});

// Limpiar campos de insumo
function limpiarCamposInsumo() {
    $('#cboInsumoProduccion').val(0).trigger('change');
    $('#cboBodegaOrigenProduccion').val(0).trigger('change');
    $('#cantidadRecetaProduccion').val("");
    $('#stockBodegaProduccion').val("0");
    $('#umedidaInvProduccion').val("");
    $('#umedidaRecetaProduccion').val("");
    $('#costoNetoProduccion').val("0");
    $('#costoTotalProduccion').val("0");
}

// Actualizar tabla
function actualizarTablaInsumos() {
    var html = '';
    
    if (insumosReceta.length === 0) {
        html = '<tr><td colspan="10" class="text-center">Seleccione un insumo y bodega</td></tr>';
    } else {
        $.each(insumosReceta, function(index, item) {
            html += '<tr data-id="' + item.id + '" style="cursor: pointer;"' + (item.cantidad_inv > item.stock + 0.0000001 ? ' class="sin-stock" title="No alcanza el stock de la bodega"' : '') + '>';
            html += '<td class="text-center">' + (index + 1) + '</td>';
            html += '<td class="text-left">' + item.nombre_insumo + '</td>';
            html += '<td class="text-center">' + Unidades.fmt(item.cantidad) + '</td>';
            html += '<td class="text-center">' + (item.umedida_receta || '') + '</td>';
            html += '<td class="text-center">' + Unidades.fmt(item.cantidad_inv) + '</td>';
            html += '<td class="text-center">' + (item.umedida_inv || '') + '</td>';
            html += '<td class="text-right">' + parseFloat(item.costo_neto).toFixed(2) + '</td>';
            html += '<td class="text-right">' + parseFloat(item.costo_total).toFixed(2) + '</td>';
            html += '<td class="text-center">' + item.nombre_bodega + '</td>';
            html += '<td class="text-center">' + parseFloat(item.stock).toFixed(2) + '</td>';
            html += '</tr>';
        });
    }
    
    $('#detalleInsumosRecetaProduccion').html(html);
    console.log("Tabla actualizada. Total insumos:", insumosReceta.length);
}

// Actualizar costo total
function actualizarCostoTotal() {
    var total = 0;
    
    $.each(insumosReceta, function(index, item) {
        total += parseFloat(item.costo_total);
    });
    
    $('#costoTotalFinal').text(total.toFixed(2));
    // v1.34.0: costo de 1 unidad de lo producido (antes mostraba el costo total)
    var producido = parseFloat($('#rendimientoReal').val()) || 0;
    $('#costoUnitarioElaborado').val(producido > 0 ? (total / producido).toFixed(2) : total.toFixed(2));
    
    console.log("Costo total actualizado:", total.toFixed(2));
}

// ---- v1.34.0 (pedido del usuario): cargar la receta de producción del insumo ----
// Las cantidades de la receta son para producir receta.cantidad (su rendimiento); se ajustan a Rend/Real.
function lineaDesdeReceta(l, escala, idBodega, stock, costo) {
    var conv = insumoConversion(l);
    var qty = (Number(l.cantidad) || 0) * escala;
    var rec = Unidades.aReceta(conv, qty, l.id_umedida);
    if (rec === null) return null;
    rec = Math.round(rec * 1000) / 1000;
    var inv = Math.round(Unidades.recetaAInventario(conv, rec) * 1000000) / 1000000;
    contadorInsumos++;
    return { id: contadorInsumos, id_insumo: l.id_insumo, nombre_insumo: l.nombre_insumo, id_bodega: idBodega,
        nombre_bodega: $('#cboBodegaOrigenProduccion option[value="' + idBodega + '"]').text() || ('Bodega ' + idBodega),
        cantidad: rec, cantidad_inv: inv, conv: conv, umedida_receta: conv.nombre_umedida_receta, umedida_inv: conv.nombre_umedida_inventario,
        costo_neto: costo, costo_total: Math.round(inv * costo * 100) / 100, stock: stock, de_receta: l };
}
/** v1.37.0: lo que rinde la receta, en la unidad de inventario de lo producido (la del campo Rend/Real) */
function rindeReceta(rec) { return Number(rec.cantidad_inventario) || Number(rec.cantidad) || 1; }
function reescalarReceta() {
    if (!recetaCargada) return;
    var escala = (parseFloat($('#rendimientoReal').val()) || 0) / rindeReceta(recetaCargada);
    $.each(insumosReceta, function (i, it) {
        if (!it.de_receta) return;
        var n = lineaDesdeReceta(it.de_receta, escala, it.id_bodega, it.stock, it.costo_neto);
        contadorInsumos--;
        if (n) { it.cantidad = n.cantidad; it.cantidad_inv = n.cantidad_inv; it.costo_total = n.costo_total; }
    });
    actualizarTablaInsumos();
    actualizarCostoTotal();
}
$(document).on('input', '#rendimientoReal', function () { reescalarReceta(); actualizarCostoTotal(); });
$('#btnCargarRecetaProduccion').on('click', function () {
    var $b = $(this).prop('disabled', true);
    $.post(base_url + "index.php/Produccion/recetaParaProducir", { id_insumo: idInsumoElaborado }, null, 'json').done(function (r) {
        var rec = r && r.receta;
        if (!rec || !rec.lineas || !rec.lineas.length) {
            showAppAlert('error', "Este insumo no tiene receta de producción con insumos. Créala en el insumo (pestaña Receta).", { duration: 6000, image: base_url + 'assets/img/errorGif.gif' });
            return;
        }
        var bodegaElegida = Number($('#cboBodegaOrigenProduccion').val()) || 0;
        if (!bodegaElegida && !Number(rec.id_bodega) && $.grep(rec.lineas, function (l) { return !Number(l.id_bodega); }).length) {
            showAppAlert('error', "Elige primero la BODEGA ORIGEN de donde salen los insumos", { duration: 5000, image: base_url + 'assets/img/errorGif.gif' });
            return;
        }
        // Rend/Real: si sigue en 1 (lo de siempre), se usa el rendimiento de la receta
        if ((parseFloat($('#rendimientoReal').val()) || 0) <= 1 && rindeReceta(rec) > 0) $('#rendimientoReal').val(rindeReceta(rec));
        var escala = (parseFloat($('#rendimientoReal').val()) || 0) / rindeReceta(rec);
        Unidades.listo(function () {
            var pedidos = [], nuevas = [], malas = [];
            $.each(rec.lineas, function (i, l) {
                // v1.37.3 (pedido del usuario): cada insumo sale de la bodega que dice su receta; sin bodega, de la bodega origen
                var idBodega = Number(l.id_bodega) || bodegaElegida || Number(rec.id_bodega);
                var d = { stock: 0, costo: 0 };
                pedidos.push($.post(base_url + "index.php/Produccion/obtenerStockActual", { id_insumo: l.id_insumo, id_bodega: idBodega }, null, 'json')
                    .done(function (x) { d.stock = parseFloat(x && x.stock_actual) || 0; }));
                pedidos.push($.post(base_url + "index.php/Produccion/obtenerCostoPPP", { id_insumo: l.id_insumo }, null, 'json')
                    .done(function (x) { d.costo = parseFloat(x && x.costo_ppp) || 0; }));
                nuevas.push(function () {
                    var it = lineaDesdeReceta(l, escala, idBodega, d.stock, d.costo);
                    if (it) insumosReceta.push(it); else malas.push(l.nombre_insumo + ' (' + (l.nombre_umedida || '?') + ')');
                });
            });
            $.when.apply($, pedidos).always(function () {
                insumosReceta = $.grep(insumosReceta, function (it) { return !it.de_receta; });   // vuelve a cargar: reemplaza la anterior
                $.each(nuevas, function (i, f) { f(); });
                recetaCargada = rec;
                actualizarTablaInsumos();
                actualizarCostoTotal();
                showAppAlert('success', "Receta cargada: " + rec.nombre_receta_produccion + " (para " + Unidades.fmt($('#rendimientoReal').val()) + " " + ($('#umedidaInventarioElaborado').val() || '') + ")", { duration: 3500, image: base_url + 'assets/img/success.gif' });
                if (malas.length) showAppAlert('error', "No se pudo convertir la unidad de: " + malas.join(', ') + ". Revisa la receta de producción.", { duration: 8000, image: base_url + 'assets/img/errorGif.gif' });
            });
        });
    }).fail(function () {
        showAppAlert('error', "No se pudo cargar la receta de producción", { duration: 5000, image: base_url + 'assets/img/errorGif.gif' });
    }).always(function () { $b.prop('disabled', false); });
});

// GRABAR PRODUCCIÓN
$('#btnGrabarProduccion').on('click', function() {
    console.log("=== GRABAR PRODUCCIÓN ===");
    
    if (insumosReceta.length === 0) {
        showAppAlert('error', "Debe agregar al menos un insumo a la receta", {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }
    
    if ($.grep(insumosReceta, function (it) { return it.cantidad_inv > it.stock + 0.0000001; }).length) {   // v1.34.0
        showAppAlert('error', "Hay insumos sin stock suficiente en su bodega (en rojo en la lista)", {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }

    if ($('#bodegaDestinoElaborado').val() == 0) {
        showAppAlert('error', "Seleccione una bodega de destino para el producto elaborado", {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }
    
    var fechaProduccion = $('#fechaProduccion').val();
    var partes = fechaProduccion.split('-');
    fechaProduccion = partes[2] + '-' + partes[1] + '-' + partes[0];
    
    var miJson = {
        'fecha': fechaProduccion,
        'id_insumo_elaborado': idInsumoElaborado,
        'insumos': insumosReceta,
        'cantidad_producida': parseFloat($('#rendimientoReal').val()) || 1,
        'id_bodega_destino': $('#bodegaDestinoElaborado').val(),
        'costo_total': parseFloat($('#costoTotalFinal').text()) || 0,
        'observaciones': $('#observacionesProduccion').val(),
        'nombre_insumo_elaborado': $('#nombreInsumoElaborado').val()
    };
    
    console.log("Datos a enviar:", miJson);
    
    $.post(base_url + "index.php/Produccion/grabarProduccion", {
        'produccion': JSON.stringify(miJson)
    })
    .done(function(response) {
        console.log("Respuesta:", response);
        
        var respuesta = typeof response === 'string' ? JSON.parse(response) : response;
        
        if (respuesta.success) {
            showAppAlert('success', "Producción registrada exitosamente", {
                duration: 4000,
                image: base_url + 'assets/img/success.gif'
            });
            
            limpiarModalProduccion();
            
            setTimeout(function() {
                $('#ModalRealizarProduccion').modal('hide');
            }, 2000);
        } else {
            showAppAlert('error', respuesta.message || "Error al registrar producción", {
                duration: 5000,
                image: base_url + 'assets/img/errorGif.gif'
            });
        }
    })
    .fail(function(xhr, status, error) {
        console.error("Error:", error);
        console.error("Response:", xhr.responseText);
        showAppAlert('error', "Error al registrar producción", {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
    });
});

// Funciones auxiliares
if (typeof soloNumerosDecimales !== 'function') {
    function soloNumerosDecimales(e) {
        var key = e.keyCode || e.which;
        var teclado = String.fromCharCode(key).toLowerCase();
        var letras = "1234567890.";
        var especiales = "8-37-38-46-164";
        
        for (var i in especiales) {
            if (key == especiales[i]) return true;
        }
        
        return letras.indexOf(teclado) !== -1;
    }
}

// Estilos
$('<style>')
    .prop('type', 'text/css')
    .html('#tableInsumosRecetaProduccion tbody tr.selected { background-color: #FBEEE8 !important; }')
    .appendTo('head');
</script>