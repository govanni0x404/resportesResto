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

// Abrir modal de producción CON DATOS DEL INSUMO
function AbrirModalProduccion(idInsumo, nombreInsumo) {
    console.log("=== ABRIENDO MODAL DE PRODUCCIÓN ===");
    console.log("ID Insumo:", idInsumo);
    console.log("Nombre Insumo:", nombreInsumo);
    
    // Limpiar campos
    $('#idProduccion').val("");
    $('#cantidadRecetaProduccion').val("");
    $('#costoTotalProduccion').val("0");
    $('#observacionesProduccion').val("");
    $('#detalleInsumosRecetaProduccion').html("<tr><td colspan='10' class='text-center'>Seleccione un insumo y bodega</td></tr>");
    $('#costoTotalFinal').text("0");
    
    // Cargar combos
    cargarInsumosParaProduccion(idInsumo);
    cargarBodegasParaProduccion();
    
    // Cargar datos del insumo seleccionado
    if (idInsumo && idInsumo > 0) {
        cargarDatosInsumoProduccion(idInsumo);
    }
    
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
    })
    .on('apply.daterangepicker', function (ev, picker) {
        // 🔥 FORZAR formato correcto
        $(this).val(picker.startDate.format('DD-MM-YYYY'));
    });
    
    // Abrir modal
    $('#ModalRealizarProduccion').modal({
        backdrop: 'static',
        keyboard: false
    });
}

// Cargar datos del insumo
function cargarDatosInsumoProduccion(idInsumo) {
    $.post(base_url + "index.php/Insumos/ObtenerDatosInsumos", {
        id_insumo: idInsumo
    }, function(datos) {
        var obj = jQuery.parseJSON(datos);
        if (obj && obj.length > 0) {
            var insumo = obj[0];
            
            // Llenar datos del insumo elaborado
            $('#nombreInsumoElaborado').val(insumo.nombre_insumo);
            
            // Obtener unidad de medida
            $.post(base_url + "index.php/Insumos/ObtenerUnidadMedida", {
                id_insumo: idInsumo
            }, function(datos2) {
                var objUm = $.parseJSON(datos2);
                if (objUm && objUm.unidad_medida) {
                    $('#umedidaInventarioElaborado').val(objUm.unidad_medida);
                    $('#umedidaInvProduccion').val(objUm.unidad_medida);
                }
            });
        }
    });
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
        $("#cboInsumoProduccion").select2();
        
        // Si hay un insumo seleccionado, disparar el evento change
        if (idInsumoSeleccionado) {
            $('#cboInsumoProduccion').trigger('change');
        }
    });
}

// Cargar bodegas
function cargarBodegasParaProduccion() {
    $.post(
        base_url + "index.php/Bodegas/ListadoDeBodegas",
        {},
        function (obj) {

            console.log(obj); // ahora ya sabes qué llega

            var tbl = '<option value="0">-- Seleccione --</option>';

            $.each(obj.aaData, function (index, v) {
                tbl += `<option value="${v.id_bodega}">${v.nombre_bodega}</option>`;
            });

            $("#cboBodegaOrigenProduccion").html(tbl).select2();
            $("#cboBodegaDestinoProduccion").html(tbl).select2();
            $("#bodegaDestinoElaborado").html(tbl).select2();

        },
        'json'
    ).fail(function () {
        console.log("Error al cargar bodegas");
    });
}


// Cuando cambia el insumo o la bodega
$('#cboInsumoProduccion, #cboBodegaOrigenProduccion').on('change', function() {
    var idInsumo = $('#cboInsumoProduccion').val();
    var idBodega = $('#cboBodegaOrigenProduccion').val();
    
    console.log("Cambió insumo o bodega:", idInsumo, idBodega);
    
    if (idInsumo > 0 && idBodega > 0) {
        // Obtener stock
        $.post(base_url + "index.php/Produccion/obtenerStockActual", {
            id_insumo: idInsumo,
            id_bodega: idBodega
        }, function(datos) {
            var obj = jQuery.parseJSON(datos);
            $('#stockBodegaProduccion').val(obj.stock_actual || 0);
        });
        
        // Cargar datos del insumo
        cargarDatosInsumoProduccion(idInsumo);
    }
});

// Calcular costo total
$('#cantidadRecetaProduccion').on('input', function() {
    var cantidad = parseFloat($(this).val()) || 0;
    var costoNeto = parseFloat($('#costoNetoProduccion').val()) || 0;
    var costoTotal = cantidad * costoNeto;
    
    $('#costoTotalProduccion').val(costoTotal.toFixed(2));
    $('#costoTotalFinal').text(costoTotal.toFixed(2));
    $('#costoUnitarioElaborado').val(costoTotal.toFixed(2));
});

// Grabar producción
$('#btnGrabarProduccion').on('click', function() {
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
    
    if (!$('#cantidadRecetaProduccion').val() || $('#cantidadRecetaProduccion').val() == 0) {
        showAppAlert('error', "Ingrese la cantidad a producir", {
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
        'id_insumo': $('#cboInsumoProduccion').val(),
        'id_bodega': $('#cboBodegaOrigenProduccion').val(),
        'cantidad': $('#cantidadRecetaProduccion').val(),
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
            
            $('#cantidadRecetaProduccion').val("");
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

// Limpiar
$('#btnLimpiarProduccion').on('click', function() {
    $('#cantidadRecetaProduccion').val("");
    $('#costoTotalProduccion').val("0");
    $('#costoTotalFinal').text("0");
    $('#observacionesProduccion').val("");
});

// Funciones auxiliares si no existen
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
</script>