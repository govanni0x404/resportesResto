<!-- MODAL REALIZAR PRODUCCION -->
<div id="ModalRealizarProduccion" class="modal fade" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <!-- TITULO MODAL -->
            <div class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
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
                        <div class="panel-heading">
                            <h5><strong>INSUMOS Y COSTOS</strong></h5>
                        </div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <fieldset>
                                        <div class="col-md-4">
                                            <label for="cboInsumoProduccion">INSUMO *</label>
                                            <div class="input-group">
                                                <select id="cboInsumoProduccion" name="cboInsumoProduccion" 
                                                        class="form-control input-sm" style="width:100%">
                                                    <option value="0">-- Seleccione --</option>
                                                </select>
                                                <span class="input-group-btn">
                                                    <button class="btn btn-success btn-sm" type="button" 
                                                            style="height: 30px;" title="Buscar Receta"
                                                            id="btnBuscarRecetaProduccion">
                                                        <i class="fa fa-search"></i>
                                                    </button>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="cboBodegaOrigenProduccion">BODEGA ORIGEN *</label>
                                            <select id="cboBodegaOrigenProduccion" name="cboBodegaOrigenProduccion" 
                                                    class="form-control input-sm" style="width:100%">
                                                <option value="0">-- Seleccione --</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label for="stockActualProduccion">STOCK BODEGA</label>
                                            <input type="number" disabled class="form-control input-sm" 
                                                   name="stockActualProduccion" id="stockActualProduccion" value="0" />
                                        </div>
                                        <div class="col-md-3">
                                            <label for="umedidaInvProduccion">U/M INV.</label>
                                            <input type="text" disabled class="form-control input-sm" 
                                                   name="umedidaInvProduccion" id="umedidaInvProduccion" value="" />
                                        </div>
                                    </fieldset>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <fieldset>
                                        <div class="col-md-3">
                                            <label for="cantidadProduccion">CANTIDAD (Receta) *</label>
                                            <input type="number" class="form-control input-sm" 
                                                   onkeypress="return soloNumerosDecimales(event)"
                                                   name="cantidadProduccion" id="cantidadProduccion" 
                                                   value="" placeholder="0" />
                                        </div>
                                        <div class="col-md-3">
                                            <label for="umedidaRecetaProduccion">U/MED RECETA</label>
                                            <input type="text" disabled class="form-control input-sm" 
                                                   name="umedidaRecetaProduccion" id="umedidaRecetaProduccion" value="" />
                                        </div>
                                        <div class="col-md-3">
                                            <label for="costoNetoProduccion">C/ NETO UN(PPP)</label>
                                            <input type="number" disabled class="form-control input-sm" 
                                                   name="costoNetoProduccion" id="costoNetoProduccion" value="0" />
                                        </div>
                                        <div class="col-md-3">
                                            <label for="costoTotalProduccion">COSTO TOTAL</label>
                                            <input type="number" disabled class="form-control input-sm" 
                                                   name="costoTotalProduccion" id="costoTotalProduccion" value="0" />
                                        </div>
                                    </fieldset>
                                </div>
                            </div>

                            <div class="row">
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
                            </div>
                        </div>
                    </div>

                    <!-- TABLA DE PRODUCCIONES -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h5><strong>HISTORIAL DE PRODUCCIÓN</strong></h5>
                                </div>
                                <div class="panel-body">
                                    <div class="table-responsive">
                                        <table id="tableProduccion" class="table table-bordered table-hover table-striped">
                                            <thead class="btn-success">
                                                <tr>
                                                    <th class="text-center">N°</th>
                                                    <th class="text-center">Insumo</th>
                                                    <th class="text-center">Cant/Receta</th>
                                                    <th class="text-center">U/M Receta</th>
                                                    <th class="text-center">Conversión</th>
                                                    <th class="text-center">U/M Inv.</th>
                                                    <th class="text-center">Costo Neto Un(PPP)</th>
                                                    <th class="text-center">Costo Total</th>
                                                    <th class="text-center">Bodega Origen</th>
                                                    <th class="text-center">Stock</th>
                                                    <th class="text-center">Le Falta</th>
                                                </tr>
                                            </thead>
                                            <tbody id="detalleProduccion">
                                                <!-- Se llenará dinámicamente -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECCION OBSERVACIONES -->
                    <div class="row">
                        <div class="col-md-12">
                            <fieldset>
                                <div class="col-md-12">
                                    <label>OBSERVACIONES</label>
                                    <textarea class="form-control" rows="3" 
                                              id="observacionesProduccion" name="observacionesProduccion"
                                              maxlength="300"></textarea>
                                </div>
                            </fieldset>
                        </div>
                    </div>

                    <!-- INSUMO ELABORADO -->
                    <div class="row" style="margin-top: 20px;">
                        <div class="col-md-12">
                            <div class="panel panel-info">
                                <div class="panel-heading">
                                    <h5><strong>INSUMO ELABORADO</strong></h5>
                                </div>
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label>Nombre del Insumo</label>
                                            <input type="text" disabled class="form-control input-sm" 
                                                   id="nombreInsumoElaborado" value="" />
                                        </div>
                                        <div class="col-md-2">
                                            <label>Rend/Real</label>
                                            <input type="text" disabled class="form-control input-sm" 
                                                   id="rendimientoReal" value="" />
                                        </div>
                                        <div class="col-md-2">
                                            <label>U/M Inven.</label>
                                            <input type="text" disabled class="form-control input-sm" 
                                                   id="umedidaInventarioElaborado" value="" />
                                        </div>
                                        <div class="col-md-2">
                                            <label>Bodega Destino</label>
                                            <input type="text" disabled class="form-control input-sm" 
                                                   id="bodegaDestinoElaborado" value="" />
                                        </div>
                                        <div class="col-md-3">
                                            <label>Costo Unitario</label>
                                            <input type="text" disabled class="form-control input-sm" 
                                                   id="costoUnitarioElaborado" value="0" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- COSTO TOTAL -->
                    <div class="row">
                        <div class="col-md-12 text-right">
                            <h3 style="color: red;">
                                <strong>COSTO TOTAL: <span id="costoTotalFinal">0</span></strong>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
            <!-- FIN CONTENIDO MODAL -->

            <!-- BOTONES -->
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" id="btnGrabarProduccion">
                    <i class="fa fa-save"></i> GRABAR DATOS
                </button>
                <button type="button" class="btn btn-info" id="btnLimpiarProduccion">
                    <i class="fa fa-eraser"></i> LIMPIAR
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="fa fa-times"></i> SALIR
                </button>
            </div>
            <!-- FIN BOTONES -->
        </div>
    </div>
</div>
<!-- FIN MODAL REALIZAR PRODUCCION -->

<script>
var base_url = "<?php echo base_url(); ?>";

// Abrir modal de producción
function AbrirModalProduccion() {
    console.log("=== ABRIENDO MODAL DE PRODUCCIÓN ===");
    
    // Limpiar campos
    $('#idProduccion').val("");
    $('#cboInsumoProduccion').val("0");
    $('#cboBodegaOrigenProduccion').val("0");
    $('#stockActualProduccion').val("0");
    $('#umedidaInvProduccion').val("");
    $('#cantidadProduccion').val("");
    $('#umedidaRecetaProduccion').val("");
    $('#costoNetoProduccion').val("0");
    $('#costoTotalProduccion').val("0");
    $('#cboBodegaDestinoProduccion').val("0");
    $('#observacionesProduccion').val("");
    $('#detalleProduccion').html("");
    $('#costoTotalFinal').text("0");
    
    // Cargar combos
    cargarInsumosParaProduccion();
    cargarBodegasParaProduccion();
    cargarHistorialProduccion();
    
    // Configurar datepicker
    $('#fechaProduccion').daterangepicker({
        "singleDatePicker": true,
        "autoApply": true,
        "locale": {
            "format": "DD-MM-YYYY",
            "daysOfWeek": ["Do", "Lu", "Ma", "Mi", "Ju", "Vi", "Sa"],
            "monthNames": [
                "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
                "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
            ]
        }
    });
    
    // Abrir modal
    $('#ModalRealizarProduccion').modal({
        backdrop: 'static',
        keyboard: false
    });
}

// Cargar insumos
function cargarInsumosParaProduccion() {
    $.post(base_url + "index.php/Insumos/ListarInsumos", {}, function(datos) {
        var obj = jQuery.parseJSON(datos);
        var tbl = '<option value="0">-- Seleccione --</option>';
        
        $.each(obj, function(index, v) {
            tbl += '<option value="' + v.id_insumo + '">' + v.nombre_insumo + '</option>';
        });
        
        $("#cboInsumoProduccion").html(tbl);
        $("#cboInsumoProduccion").select2();
    });
}

// Cargar bodegas
function cargarBodegasParaProduccion() {
    // Aquí debes tener un método en tu controlador para listar bodegas
    $.post(base_url + "index.php/Bodega/ListarBodegas", {}, function(datos) {
        var obj = jQuery.parseJSON(datos);
        var tbl = '<option value="0">-- Seleccione --</option>';
        
        $.each(obj, function(index, v) {
            tbl += '<option value="' + v.id_bodega + '">' + v.nombre_bodega + '</option>';
        });
        
        $("#cboBodegaOrigenProduccion").html(tbl);
        $("#cboBodegaOrigenProduccion").select2();
        
        $("#cboBodegaDestinoProduccion").html(tbl);
        $("#cboBodegaDestinoProduccion").select2();
    }).fail(function() {
        console.log("Error al cargar bodegas");
    });
}

// Cargar historial de producción
function cargarHistorialProduccion() {
    $.post(base_url + "index.php/Produccion/listadoProducciones", {}, function(datos) {
        var obj = jQuery.parseJSON(datos);
        
        $('#detalleProduccion').html("");
        
        if (obj && obj.length > 0) {
            $.each(obj, function(index, v) {
                var fila = "<tr>";
                fila += "<td class='text-center'>" + (index + 1) + "</td>";
                fila += "<td class='text-left'>" + (v.nombre_insumo || '-') + "</td>";
                fila += "<td class='text-center'>" + v.cantidad + "</td>";
                fila += "<td class='text-center'>-</td>"; // U/M Receta
                fila += "<td class='text-center'>-</td>"; // Conversión
                fila += "<td class='text-center'>-</td>"; // U/M Inv
                fila += "<td class='text-right'>" + v.costo_unitario + "</td>";
                fila += "<td class='text-right'>" + v.costo_total + "</td>";
                fila += "<td class='text-center'>" + (v.nombre_bodega || '-') + "</td>";
                fila += "<td class='text-center'>" + v.stock_actual + "</td>";
                fila += "<td class='text-center'>-</td>"; // Le falta
                fila += "</tr>";
                
                $('#detalleProduccion').append(fila);
            });
        } else {
            $('#detalleProduccion').html("<tr><td colspan='11' class='text-center'>No hay producciones registradas</td></tr>");
        }
    }).fail(function() {
        console.error("Error al cargar historial de producción");
    });
}

// Cuando se selecciona un insumo, obtener su stock
$('#cboInsumoProduccion, #cboBodegaOrigenProduccion').on('change', function() {
    var idInsumo = $('#cboInsumoProduccion').val();
    var idBodega = $('#cboBodegaOrigenProduccion').val();
    
    if (idInsumo > 0 && idBodega > 0) {
        $.post(base_url + "index.php/Produccion/obtenerStockActual", {
            id_insumo: idInsumo,
            id_bodega: idBodega
        }, function(datos) {
            var obj = jQuery.parseJSON(datos);
            $('#stockActualProduccion').val(obj.stock_actual || 0);
        });
        
        // Obtener datos del insumo
        $.post(base_url + "index.php/Insumos/ObtenerDatosInsumos", {
            id_insumo: idInsumo
        }, function(datos) {
            var obj = jQuery.parseJSON(datos);
            if (obj && obj.length > 0) {
                $('#nombreInsumoElaborado').val(obj[0].nombre_insumo);
            }
        });
    }
});

// Calcular costo total cuando cambia la cantidad
$('#cantidadProduccion').on('input', function() {
    var cantidad = parseFloat($(this).val()) || 0;
    var costoNeto = parseFloat($('#costoNetoProduccion').val()) || 0;
    var costoTotal = cantidad * costoNeto;
    
    $('#costoTotalProduccion').val(costoTotal.toFixed(2));
    $('#costoTotalFinal').text(costoTotal.toFixed(2));
});

// Grabar producción
$('#btnGrabarProduccion').on('click', function() {
    // Validaciones
    if ($('#cboInsumoProduccion').val() == 0) {
        showAppAlert('error', "Seleccione un insumo", {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }
    
    if ($('#cboBodegaOrigenProduccion').val() == 0) {
        showAppAlert('error', "Seleccione una bodega de origen", {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }
    
    if (!$('#cantidadProduccion').val() || $('#cantidadProduccion').val() == 0) {
        showAppAlert('error', "Ingrese la cantidad a producir", {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }
    
    // Convertir fecha de DD-MM-YYYY a YYYY-MM-DD
    var fechaProduccion = $('#fechaProduccion').val();
    var partes = fechaProduccion.split('-');
    fechaProduccion = partes[2] + '-' + partes[1] + '-' + partes[0];
    
    var miJson = {
        'fecha': fechaProduccion,
        'id_insumo': $('#cboInsumoProduccion').val(),
        'id_bodega': $('#cboBodegaOrigenProduccion').val(),
        'cantidad': $('#cantidadProduccion').val(),
        'costo_unitario': $('#costoNetoProduccion').val(),
        'costo_total': $('#costoTotalProduccion').val(),
        'observaciones': $('#observacionesProduccion').val()
    };
    
    console.log("=== DATOS DE PRODUCCIÓN ===");
    console.log(miJson);
    
    $.post(base_url + "index.php/Produccion/grabarProduccion", {
        'produccion': miJson
    })
    .done(function(response) {
        console.log("Respuesta:", response);
        
        var respuesta = typeof response === 'string' ? JSON.parse(response) : response;
        
        if (respuesta.success) {
            showAppAlert('success', "Producción registrada exitosamente", {
                duration: 4000,
                image: base_url + 'assets/img/success.gif'
            });
            
            // Recargar historial
            cargarHistorialProduccion();
            
            // Limpiar campos
            $('#cantidadProduccion').val("");
            $('#costoTotalProduccion').val("0");
            $('#costoTotalFinal').text("0");
            $('#observacionesProduccion').val("");
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

// Limpiar formulario
$('#btnLimpiarProduccion').on('click', function() {
    $('#cantidadProduccion').val("");
    $('#costoTotalProduccion').val("0");
    $('#costoTotalFinal').text("0");
    $('#observacionesProduccion').val("");
});
</script>