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
            <div class="modal-body">
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

                    <br>

                    <!-- SECCION INSUMOS Y COSTOS -->
                    <div class="panel panel-default">
                        <div class="panel-heading" style="background-color: #f0f0f0;">
                            <h5><strong style="font-weight: bold;">INSUMOS Y COSTOS</strong></h5>
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
                                            <label for="cantidadRecetaProduccion">CANTIDAD (Receta) *</label>
                                            <input type="number" class="form-control input-sm" 
                                                   onkeypress="return soloNumerosDecimales(event)"
                                                   name="cantidadRecetaProduccion" id="cantidadRecetaProduccion" 
                                                   value="" placeholder="0" />
                                        </div>
                                    </fieldset>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <fieldset>
                                        
                                        <div class="col-md-2">
                                            <label for="umedidaRecetaProduccion">U/MED RECETA</label>
                                            <input type="text" disabled class="form-control input-sm" 
                                                   name="umedidaRecetaProduccion" id="umedidaRecetaProduccion" value="" />
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
                                        <div class="col-md-2">
                                            <button class="btn btn-success">Agregar</button>
                                        </div>
                                        <div class="col-md-2">
                                            <button class="btn btn-default">Nuevo</button>
                                        </div>
                                        <div class="col-md-2">
                                            <button class="btn btn-danger">Quitar</button>
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
                                            <thead style="background-color: #5cb85c; color: white;">
                                                <tr>
                                                    <th class="text-center" style="width: 5%;">N</th>
                                                    <th class="text-left" style="width: 20%;">Insumo</th>
                                                    <th class="text-center" style="width: 10%;">Cant/Receta</th>
                                                    <th class="text-center" style="width: 10%;">U/M Receta</th>
                                                    <th class="text-center" style="width: 10%;">Conversión</th>
                                                    <th class="text-center" style="width: 10%;">U/M Inv.</th>
                                                    <th class="text-right" style="width: 10%;">Costo Neto Un(PPP)</th>
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
                                    <h3 style="color: red; margin-left: 30px;">
                                        <strong>COSTO TOTAL: <span id="costoTotalFinal">0</span></strong>
                                    </h3>
                                </div>
                            </fieldset>
                            <fieldset>
                                <div class="col-md-11">
                                    <label>OBSERVACIONES</label>
                                    <textarea class="form-control input-sm" rows="2" 
                                              id="observacionesProduccion" name="observacionesProduccion"
                                              maxlength="300" style="resize: none;"></textarea>
                                </div>
                                
                            </fieldset>
                        </div>
                    </div>

                    <br>

                    <!-- INSUMO ELABORADO -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel panel-default">
                                <div class="panel-heading" style="background-color: #568c30; color: white;">
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
                                                   style="background-color: #d9534f; color: white; font-weight: bold;" />
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
<!-- FIN MODAL REALIZAR PRODUCCION -->

<script>
var base_url = "<?php echo base_url(); ?>";

// Variable global para almacenar insumos de la receta
var insumosReceta = [];
var contadorInsumos = 0;

// Variable para almacenar el ID del insumo elaborado
var idInsumoElaborado = 0;

// Abrir modal de producción CON DATOS DEL INSUMO
function AbrirModalProduccion(idInsumo, nombreInsumo) {
    console.log("=== ABRIENDO MODAL DE PRODUCCIÓN ===");
    console.log("ID Insumo:", idInsumo);
    console.log("Nombre Insumo:", nombreInsumo);
    
    // Guardar el ID del insumo elaborado
    idInsumoElaborado = idInsumo;
    
    // Limpiar campos y resetear
    limpiarModalProduccion();
    
    // Cargar combos primero
    cargarBodegasParaProduccion();
    
    // Cargar insumos y luego cargar datos del insumo elaborado
    cargarInsumosParaProduccion(function() {
        // Una vez cargados los insumos, cargar datos del insumo elaborado
        if (idInsumo && idInsumo > 0) {
            cargarDatosInsumoElaborado(idInsumo);
            // Preseleccionar el insumo en el combo
            $('#cboInsumoProduccion').val(idInsumo).trigger('change');
        }
    });
    
    // Configurar datepicker
    $('#fechaProduccion').daterangepicker({
        singleDatePicker: true,
        autoApply: true,
        startDate: moment($('#fechaProduccion').val(), 'DD-MM-YYYY'),
        locale: {
            format: 'DD-MM-YYYY',
            daysOfWeek: ["Do", "Lu", "Ma", "Mi", "Ju", "Vi", "Sa"],
            monthNames: [
                "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
                "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
            ],
            firstDay: 1
        }
    }).on('apply.daterangepicker', function (ev, picker) {
        $(this).val(picker.startDate.format('DD-MM-YYYY'));
    });
    
    // Abrir modal
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
    
    // Limpiar tabla y array
    insumosReceta = [];
    contadorInsumos = 0;
    actualizarTablaInsumos();
    actualizarCostoTotal();
}

// Cargar datos del insumo ELABORADO (producto final)
function cargarDatosInsumoElaborado(idInsumo) {
    $.post(base_url + "index.php/Produccion/obtenerDatosInsumoCompleto", {
        id_insumo: idInsumo
    }, function(datos) {
        var obj = typeof datos === 'string' ? jQuery.parseJSON(datos) : datos;
        
        if (obj) {
            $('#nombreInsumoElaborado').val(obj.nombre_insumo);
            $('#umedidaInventarioElaborado').val(obj.abreviatura || obj.nombre_unidad_medida);
        }
    });
}

// Cargar insumos
function cargarInsumosParaProduccion(callback) {
    $.post(base_url + "index.php/Insumos/ListarInsumos", {}, function(datos) {
        var obj = jQuery.parseJSON(datos);
        var tbl = '<option value="0">-- Seleccione --</option>';
        
        $.each(obj, function(index, v) {
            tbl += '<option value="' + v.id_insumo + '">' + v.nombre_insumo + '</option>';
        });
        
        $("#cboInsumoProduccion").html(tbl).select2();
        
        // Ejecutar callback si existe
        if (typeof callback === 'function') {
            callback();
        }
    });
}

// Cargar bodegas
function cargarBodegasParaProduccion() {
    $.post(base_url + "index.php/Bodegas/ListadoDeBodegas", {}, function (obj) {
        var tbl = '<option value="0">-- Seleccione --</option>';
        
        $.each(obj.aaData, function (index, v) {
            tbl += `<option value="${v.id_bodega}">${v.nombre_bodega}</option>`;
        });
        
        $("#cboBodegaOrigenProduccion").html(tbl).select2();
        $("#bodegaDestinoElaborado").html(tbl).select2();
    }, 'json');
}

// Cuando cambia el insumo
$('#cboInsumoProduccion').on('change', function() {
    var idInsumo = $(this).val();
    
    console.log("Cambió insumo a:", idInsumo);
    
    if (idInsumo > 0) {
        // Obtener datos del insumo seleccionado
        $.post(base_url + "index.php/Produccion/obtenerDatosInsumoCompleto", {
            id_insumo: idInsumo
        }, function(datos) {
            console.log("Datos del insumo recibidos:", datos);
            
            var obj = typeof datos === 'string' ? jQuery.parseJSON(datos) : datos;
            
            if (obj) {
                // Llenar unidades de medida
                var unidadMedida = obj.abreviatura || obj.nombre_unidad_medida || '';
                console.log("Unidad de medida:", unidadMedida);
                
                $('#umedidaInvProduccion').val(unidadMedida);
                $('#umedidaRecetaProduccion').val(unidadMedida);
            }
            
            // Si hay bodega seleccionada, obtener stock y costo
            var idBodega = $('#cboBodegaOrigenProduccion').val();
            console.log("Bodega actual:", idBodega);
            
            if (idBodega > 0) {
                cargarStockYCosto(idInsumo, idBodega);
            }
        }).fail(function(xhr, status, error) {
            console.error("Error al obtener datos del insumo:", error);
        });
    } else {
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
    
    console.log("Cambió bodega a:", idBodega);
    console.log("Insumo actual:", idInsumo);
    
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
        id_insumo: idInsumo,
        id_bodega: idBodega
    }, function(datos) {
        console.log("Costo PPP recibido:", datos);
        var obj = typeof datos === 'string' ? jQuery.parseJSON(datos) : datos;
        var costo = parseFloat(obj.costo_ppp || 0);
        $('#costoNetoProduccion').val(costo.toFixed(2));
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
    var cantidad = parseFloat($('#cantidadRecetaProduccion').val()) || 0;
    var costoNeto = parseFloat($('#costoNetoProduccion').val()) || 0;
    var costoTotal = cantidad * costoNeto;
    
    console.log("=== Calculando costo total ===");
    console.log("Cantidad:", cantidad);
    console.log("Costo Neto:", costoNeto);
    console.log("Costo Total:", costoTotal);
    
    $('#costoTotalProduccion').val(costoTotal.toFixed(2));
}

// BOTÓN AGREGAR - Agregar insumo a la tabla
$('.btn-success:contains("Agregar")').on('click', function(e) {
    e.preventDefault();
    
    console.log("=== BOTÓN AGREGAR CLICKEADO ===");
    
    var idInsumo = $('#cboInsumoProduccion').val();
    var nombreInsumo = $('#cboInsumoProduccion option:selected').text();
    var idBodega = $('#cboBodegaOrigenProduccion').val();
    var nombreBodega = $('#cboBodegaOrigenProduccion option:selected').text();
    var cantidad = parseFloat($('#cantidadRecetaProduccion').val()) || 0;
    var umedidaReceta = $('#umedidaRecetaProduccion').val();
    var umedidaInv = $('#umedidaInvProduccion').val();
    var costoNeto = parseFloat($('#costoNetoProduccion').val()) || 0;
    var costoTotal = parseFloat($('#costoTotalProduccion').val()) || 0;
    var stock = parseFloat($('#stockBodegaProduccion').val()) || 0;
    
    console.log("Datos a agregar:");
    console.log("- ID Insumo:", idInsumo);
    console.log("- Nombre Insumo:", nombreInsumo);
    console.log("- ID Bodega:", idBodega);
    console.log("- Nombre Bodega:", nombreBodega);
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
    
    if (cantidad > stock) {
        showAppAlert('error', "La cantidad supera el stock disponible (" + stock + ")", {
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
        cantidad: cantidad,
        umedida_receta: umedidaReceta,
        umedida_inv: umedidaInv,
        conversion: 1, // Puedes calcular esto según tu lógica
        costo_neto: costoNeto,
        costo_total: costoTotal,
        stock: stock
    };
    
    console.log("Insumo agregado al array:", insumo);
    insumosReceta.push(insumo);
    console.log("Total insumos en array:", insumosReceta.length);
    
    // Actualizar tabla y totales
    actualizarTablaInsumos();
    actualizarCostoTotal();
    
    // Limpiar campos de entrada
    limpiarCamposInsumo();
    
    showAppAlert('success', "Insumo agregado correctamente", {
        duration: 2000,
        image: base_url + 'assets/img/success.gif'
    });
});

// BOTÓN NUEVO - Limpiar campos de insumo
$('.btn-default:contains("Nuevo")').on('click', function(e) {
    e.preventDefault();
    limpiarCamposInsumo();
});

// BOTÓN QUITAR - Eliminar insumo seleccionado
$('.btn-danger:contains("Quitar")').on('click', function(e) {
    e.preventDefault();
    
    // Obtener fila seleccionada
    var filaSeleccionada = $('#tableInsumosRecetaProduccion tbody tr.selected');
    
    if (filaSeleccionada.length === 0) {
        showAppAlert('error', "Seleccione un insumo de la tabla para eliminar", {
            duration: 3000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }
    
    var idFila = filaSeleccionada.data('id');
    
    // Eliminar del array
    insumosReceta = insumosReceta.filter(function(item) {
        return item.id !== idFila;
    });
    
    // Actualizar tabla y totales
    actualizarTablaInsumos();
    actualizarCostoTotal();
    
    showAppAlert('success', "Insumo eliminado correctamente", {
        duration: 2000,
        image: base_url + 'assets/img/success.gif'
    });
});

// Seleccionar fila de la tabla al hacer click
$(document).on('click', '#tableInsumosRecetaProduccion tbody tr', function() {
    if ($(this).find('td').length === 1) return; // No seleccionar fila vacía
    
    $('#tableInsumosRecetaProduccion tbody tr').removeClass('selected');
    $(this).addClass('selected');
});

// Limpiar campos de entrada de insumo
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

// Actualizar tabla de insumos
function actualizarTablaInsumos() {
    var html = '';
    
    if (insumosReceta.length === 0) {
        html = '<tr><td colspan="10" class="text-center">Seleccione un insumo y bodega</td></tr>';
    } else {
        $.each(insumosReceta, function(index, item) {
            html += '<tr data-id="' + item.id + '" style="cursor: pointer;">';
            html += '<td class="text-center">' + (index + 1) + '</td>';
            html += '<td class="text-left">' + item.nombre_insumo + '</td>';
            html += '<td class="text-center">' + parseFloat(item.cantidad).toFixed(2) + '</td>';
            html += '<td class="text-center">' + (item.umedida_receta || '') + '</td>';
            html += '<td class="text-center">' + item.conversion + '</td>';
            html += '<td class="text-center">' + (item.umedida_inv || '') + '</td>';
            html += '<td class="text-right">' + parseFloat(item.costo_neto).toFixed(2) + '</td>';
            html += '<td class="text-right">' + parseFloat(item.costo_total).toFixed(2) + '</td>';
            html += '<td class="text-center">' + item.nombre_bodega + '</td>';
            html += '<td class="text-center">' + parseFloat(item.stock).toFixed(2) + '</td>';
            html += '</tr>';
        });
    }
    
    $('#detalleInsumosRecetaProduccion').html(html);
    
    console.log("=== Tabla actualizada ===");
    console.log("Total de insumos:", insumosReceta.length);
}

// Actualizar costo total
function actualizarCostoTotal() {
    var total = 0;
    
    $.each(insumosReceta, function(index, item) {
        total += parseFloat(item.costo_total);
    });
    
    $('#costoTotalFinal').text(total.toFixed(2));
    $('#costoUnitarioElaborado').val(total.toFixed(2));
}

// Grabar producción
$('#btnGrabarProduccion').on('click', function() {
    // Validaciones
    if (insumosReceta.length === 0) {
        showAppAlert('error', "Debe agregar al menos un insumo a la receta", {
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
        'insumos': insumosReceta,
        'cantidad_producida': parseFloat($('#rendimientoReal').val()) || 1,
        'id_bodega_destino': $('#bodegaDestinoElaborado').val(),
        'costo_total': parseFloat($('#costoTotalFinal').text()) || 0,
        'observaciones': $('#observacionesProduccion').val(),
        'nombre_insumo_elaborado': $('#nombreInsumoElaborado').val()
    };
    
    console.log("=== DATOS DE PRODUCCIÓN ===");
    console.log(miJson);
    
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
            
            // Limpiar todo
            limpiarModalProduccion();
            
            // Cerrar modal después de 2 segundos
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

// Agregar estilos para fila seleccionada
$('<style>')
    .prop('type', 'text/css')
    .html('#tableInsumosRecetaProduccion tbody tr.selected { background-color: #d9edf7 !important; }')
    .appendTo('head');
</script>