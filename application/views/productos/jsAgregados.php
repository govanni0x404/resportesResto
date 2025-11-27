<script>
//AGREGADOS DEL PRODUCTO
function AbrirModalAgregadosDelProducto(idProducto) {
    // Limpiar campos
    $('#codigoAgregado').val("");
    $('#cantidadAgregado').val("1");
    $('#valorAdicionalAgregado').val("0");
    $('#ckUsarDestinoDeImpresion').prop('checked', false);
    $('#ckVigenciaAgregado').prop('checked', true);
    
    // Obtener ID del producto actual
    var idProductoActual = $('#txtID').val();
    
    if (!idProductoActual || idProductoActual == "0" || idProductoActual == "") {
        showAppAlert('error', "Debe grabar el producto primero antes de agregar productos agregados", {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }
    
    // Cargar listado de productos disponibles
    ListadoDeAgregadosParaElProducto();
    
    // Cargar agregados existentes del producto
    ObtenerAgregadosDelProducto(idProductoActual);
    
    $('#ModalAgregadosDelProducto').modal({
        backdrop: 'static',
        keyboard: false
    });
}

function ObtenerAgregadosDelProducto(idProducto) {
    console.log("Obteniendo agregados para producto ID:", idProducto);
    
    $.post(base_url + "index.php/Productos/obtenerAgregadosDelProducto", {
        id_producto_maestro: idProducto
    }, function(datos) {
        console.log("Agregados recibidos:", datos);
        
        var obj = $.parseJSON(datos);
        var tbl = '';
        
        if (obj.length === 0) {
            tbl = '<tr><td colspan="7" class="text-center">No hay agregados para este producto</td></tr>';
        } else {
            $.each(obj, function(index, v) {
                // AGREGAR data-id-producto para validación de duplicados
                tbl += '<tr data-id-producto="' + v.id_producto + '">';
                tbl += "<td>" + v.id_agregado + "</td>";
                tbl += "<td>" + v.producto_maestro + "</td>";
                tbl += "<td>" + v.nombre_producto + "</td>";
                tbl += '<td style="width:15%" class="text-right">';
                tbl += '<input id="maxCantidadAgregado_' + v.id_agregado + '" ';
                tbl += 'class="text-right form-control cantidad-agregado-input" ';
                tbl += 'type="number" min="1" max="1000" value="' + v.cantidad + '">';
                tbl += '</td>';
                tbl += '<td style="width:15%" class="text-right">';
                tbl += '<input id="maxValorAgregado_' + v.id_agregado + '" ';
                tbl += 'class="text-right form-control valor-agregado-input" ';
                tbl += 'type="number" min="0" max="50000" value="' + v.valor + '">';
                tbl += '</td>';
                tbl += "<td>" + (v.vigente == 't' ? 'SI' : 'NO') + "</td>";
                
                if (v.vigente == 't') {
                    tbl += '<td><button onclick="DeshabilitarAgregado(' + v.id_agregado + ')" ';
                    tbl += 'type="button" class="btn btn-block btn-danger btn-sm">';
                    tbl += '<i class="fa fa-times-circle"></i> DESHABILITAR</button></td>';
                } else {
                    tbl += '<td><button onclick="HabilitarAgregado(' + v.id_agregado + ')" ';
                    tbl += 'type="button" class="btn btn-block btn-primary btn-sm">';
                    tbl += '<i class="fa fa-check-circle"></i> HABILITAR</button></td>';
                }
                
                tbl += "</tr>";
            });
        }
        
        $("#detalleAgregadosDelProducto").html(tbl);
        initValidacionesAgregados();
    }).fail(function(xhr, status, error) {
        console.error("Error al obtener agregados:", error);
        showAppAlert('error', "Error al cargar agregados: " + error, {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
    });
}

function initValidacionesAgregados() {
    // Validación para cantidad
    $(document).on('input', '.cantidad-agregado-input', function() {
        var $input = $(this);
        var value = $input.val();
        
        if (value.length > 4) {
            value = value.slice(0, 4);
        }
        
        var n = parseInt(value) || 1;
        if (n > 1000) n = 1000;
        if (n < 1) n = 1;
        
        $input.val(n);
    });
    
    // Validación para valor adicional
    $(document).on('input', '.valor-agregado-input', function() {
        var $input = $(this);
        var value = $input.val();
        
        if (value.length > 5) {
            value = value.slice(0, 5);
        }
        
        var n = parseInt(value) || 0;
        if (n > 50000) n = 50000;
        if (n < 0) n = 0;
        
        $input.val(n);
    });
}

function GrabarNuevoAgregados() {
    var msg = "";
    
    // VALIDACIONES
    if (!$('#cboProductoAgregadoProductos').val() || $('#cboProductoAgregadoProductos').val() == "0") {
        msg += "- Producto Agregado\n";
    }
    
    if (!$('#cantidadAgregado').val() || $('#cantidadAgregado').val() == "0" || $('#cantidadAgregado').val() == "") {
        msg += "- Cantidad De Agregado (debe ser mayor a 0)\n";
    }
    
    // VALIDAR que el valor adicional no sea negativo (puede ser 0)
    var valorAdicional = $('#valorAdicionalAgregado').val();
    if (valorAdicional === "" || valorAdicional === null) {
        $('#valorAdicionalAgregado').val("0");
        valorAdicional = 0;
    }
    
    if (parseFloat(valorAdicional) < 0) {
        msg += "- Valor Adicional no puede ser negativo\n";
    }
    
    if (msg.length) {
        showAppAlert('error', "Falta ingresar los siguientes datos:\n" + msg, {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }
    
    var productoMaestro = $('#txtID').val();
    var productoAgregado = $('#cboProductoAgregadoProductos').val();
    var cantidadAgregado = $('#cantidadAgregado').val();
    var valorAdicionalAgregado = $('#valorAdicionalAgregado').val() || 0;
    
    console.log("=== VALIDANDO DUPLICADOS ===");
    console.log("Producto a agregar:", productoAgregado);
    console.log("Producto maestro:", productoMaestro);
    
    // Verificar si el producto ya existe en la lista (CORREGIDO)
    var yaExiste = false;
    $("#detalleAgregadosDelProducto tr").each(function() {
        
        var nombreProductoEnTabla = $(this).find("td").eq(2).text().trim();
        var nombreProductoSeleccionado = $('#cboProductoAgregadoProductos option:selected').text().trim();
        
        console.log("Comparando:", nombreProductoEnTabla, "con", nombreProductoSeleccionado);
        
        if (nombreProductoEnTabla === nombreProductoSeleccionado) {
            yaExiste = true;
            return false; // break
        }
    });
    
    if (yaExiste) {
        showAppAlert('error', "El producto ya se encuentra en la lista de agregados", {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }
    
    var miJson = {
        'id_agregado': "",
        'id_producto': productoAgregado,
        'cantidad': cantidadAgregado,
        'vigente': $('#ckVigenciaAgregado').is(":checked") ? 1 : 0,
        'id_producto_maestro': productoMaestro,
        'valor': valorAdicionalAgregado,
        'usar_destino_impresion': $('#ckUsarDestinoDeImpresion').is(":checked") ? 1 : 0,
    };
    
    console.log("Grabando agregado:", miJson);
    
    $.ajax({
        url: base_url + "index.php/Productos/grabarAgregados",
        type: "POST",
        data: { prod: miJson },
        dataType: "json",
        success: function(response) {
            console.log("✓ Agregado grabado:", response);
            
            showAppAlert('success', "Agregado registrado exitosamente!", {
                duration: 4000,
                image: base_url + 'assets/img/success.gif'
            });
            
            // Limpiar campos
            $('#cboProductoAgregadoProductos').val("0").trigger('change');
            $('#cantidadAgregado').val("1");
            $('#valorAdicionalAgregado').val("0");
            
            // Recargar lista
            setTimeout(function() {
                ObtenerAgregadosDelProducto(productoMaestro);
            }, 300);
        },
        error: function(xhr, status, error) {
            console.error("✗ Error completo:", xhr.responseText);
            
            // Detectar error de duplicado
            if (xhr.responseText.indexOf('llave duplicada') > -1 || 
                xhr.responseText.indexOf('duplicate key') > -1) {
                showAppAlert('error', "Este producto ya está agregado. Si no lo ves en la lista, recarga la página.", {
                    duration: 6000,
                    image: base_url + 'assets/img/errorGif.gif'
                });
            } else {
                showAppAlert('error', "Error al grabar agregado: " + error, {
                    duration: 5000,
                    image: base_url + 'assets/img/errorGif.gif'
                });
            }
        }
    });
}

function actualizarCantidadProductoAgregado() {
    var idProducto = $("#txtID").val();
    var detalleAgregados = [];
    
    $("#tableAgregados tbody tr").each(function() {
        var idAgregado = $(this).find("td").eq(0).text().trim();
        var cantidad = $(this).find(".cantidad-agregado-input").val() || 1;
        
        if (idAgregado && idAgregado !== "No hay agregados para este producto") {
            detalleAgregados.push({
                'id_agregado': idAgregado,
                'cantidad': cantidad
            });
        }
    });
    
    if (detalleAgregados.length === 0) {
        console.log("No hay agregados para actualizar");
        return;
    }
    
    console.log("Actualizando cantidades:", detalleAgregados);
    
    $.post(base_url + "index.php/Productos/actualizarCantidadProductoAgregado", {
        detalle: detalleAgregados
    })
    .done(function(datos) {
        console.log("Cantidades actualizadas:", datos);
        showAppAlert('success', "Cantidades actualizadas exitosamente!", {
            duration: 4000,
            image: base_url + 'assets/img/success.gif'
        });
    })
    .fail(function(xhr, status, error) {
        console.error("Error:", xhr.responseText);
        showAppAlert('error', "Error al actualizar cantidades: " + error, {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
    });
}

function actualizarValorAdicionalProductoAgregado() {
    var idProducto = $("#txtID").val();
    var detalleAgregados = [];
    
    $("#tableAgregados tbody tr").each(function() {
        var idAgregado = $(this).find("td").eq(0).text().trim();
        var valor = $(this).find(".valor-agregado-input").val() || 0;
        
        if (idAgregado && idAgregado !== "No hay agregados para este producto") {
            detalleAgregados.push({
                'id_agregado': idAgregado,
                'valor': valor
            });
        }
    });
    
    if (detalleAgregados.length === 0) {
        console.log("No hay agregados para actualizar");
        return;
    }
    
    console.log("Actualizando valores:", detalleAgregados);
    
    $.post(base_url + "index.php/Productos/actualizarValorAdicionalProductoAgregado", {
        detalle: detalleAgregados
    })
    .done(function(datos) {
        console.log("Valores actualizados:", datos);
        showAppAlert('success', "Valores actualizados exitosamente!", {
            duration: 4000,
            image: base_url + 'assets/img/success.gif'
        });
    })
    .fail(function(xhr, status, error) {
        console.error("Error:", xhr.responseText);
        showAppAlert('error', "Error al actualizar valores: " + error, {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
    });
}

$('#btnActualizarAgregado').on('click', function() {
    actualizarCantidadProductoAgregado();
    actualizarValorAdicionalProductoAgregado();
});

function HabilitarAgregado(id) {
    swal({
        title: "¿Está seguro?",
        text: "¿Desea habilitar este agregado?",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#5cb85c",
        confirmButtonText: "Sí, habilitar",
        cancelButtonText: "Cancelar",
        closeOnConfirm: true,
        closeOnCancel: true
    }, function(isConfirm) {
        if (isConfirm) {
            var idProducto = $('#txtID').val();
            
            $.post(base_url + "index.php/Productos/HabilitarAgregado", { data: id })
                .done(function(datos) {
                    showAppAlert('success', "Agregado habilitado exitosamente!", {
                        duration: 4000,
                        image: base_url + 'assets/img/success.gif'
                    });
                    ObtenerAgregadosDelProducto(idProducto);
                })
                .fail(function() {
                    showAppAlert('error', "Error al habilitar el agregado", {
                        duration: 5000,
                        image: base_url + 'assets/img/errorGif.gif'
                    });
                });
        }
    });
}

function DeshabilitarAgregado(id) {
    swal({
        title: "¿Está seguro?",
        text: "¿Desea deshabilitar este agregado?",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d9534f",
        confirmButtonText: "Sí, deshabilitar",
        cancelButtonText: "Cancelar",
        closeOnConfirm: true,
        closeOnCancel: true
    }, function(isConfirm) {
        if (isConfirm) {
            var idProducto = $('#txtID').val();
            
            $.post(base_url + "index.php/Productos/DeshabilitarAgregado", { data: id })
                .done(function(datos) {
                    showAppAlert('success', "Agregado deshabilitado exitosamente!", {
                        duration: 4000,
                        image: base_url + 'assets/img/success.gif'
                    });
                    ObtenerAgregadosDelProducto(idProducto);
                })
                .fail(function() {
                    showAppAlert('error', "Error al deshabilitar el agregado", {
                        duration: 5000,
                        image: base_url + 'assets/img/errorGif.gif'
                    });
                });
        }
    });
}

function ListadoDeAgregadosParaElProducto(objSelected) {
    $.post(base_url + "index.php/Productos/ListadoDeProductos", { id: "" }, function(datos) {
        var obj = jQuery.parseJSON(datos);
        var tbl = '<option value="0">SELECCIONE EL AGREGADO</option>';
        
        $.each(obj, function(index, v) {
            if (v.nombre_producto == objSelected) {
                tbl += '<option selected value="' + v.id_producto + '">' + v.nombre_producto + '</option>';
            } else {
                tbl += '<option value="' + v.id_producto + '">' + v.nombre_producto + '</option>';
            }
        });
        
        $("#cboProductoAgregadoProductos").html(tbl);
        
        // Reinicializar select2 si existe
        if ($.fn.select2) {
            $("#cboProductoAgregadoProductos").select2();
        }
    });
}

// Inicializar validaciones al cargar
$(document).ready(function() {
    initValidacionesAgregados();
});
</script>