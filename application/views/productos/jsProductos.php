<script>
var base_url = "<?php echo base_url(); ?>";
tablaCartaProductos = $('#tableCartaProductos').DataTable();
var datosProductoPendiente = null; 
var preciosExistentes = []; 

$(document).ready(function() {
    ListadoCartaDeProductos();
});

$('#btnFiltrar').on('click', function() {
    var id_familia = $("#cboFamilias").val();
    console.log(id_familia);
    if ($('#ckTodasLasFamilias').prop('checked') == false) {
        if (id_familia == null || id_familia == 0) {
            alert("Debe seleccionar la familia");
            return;
        }
    } else {
        id_familia = 0;
    }
    ListadoCartaDeProductos(id_familia);
});

$('#ckTodasLasFamilias').click(function() {
    if ($('#ckTodasLasFamilias').prop('checked') == false) {
        $("#cboFamilias").attr("disabled", false);
    } else {
        $("#cboFamilias").attr("disabled", true);
    }
});

function ObtenerPreciosDeVenta(idProducto) {
    $.post(base_url + "index.php/Productos/obtenerPreciosYComisiones", {
        id_producto: idProducto
    }, function(datos) {
        var obj = $.parseJSON(datos);
        preciosExistentes = obj; // Guardar precios existentes
        console.log("Precios cargados:", preciosExistentes);
    });
}

function showTableLoader(message) {
    if ($('#dynamic-table-loader').length) {
        $('#dynamic-table-loader').show();
        return;
    }
    
    var loaderHtml = 
        '<div id="dynamic-table-loader" style="' +
        'position: absolute; top: 0; left: 0; right: 0; bottom: 0; ' +
        'background: rgba(255, 255, 255, 0.95); ' +
        'display: flex; flex-direction: column; align-items: center; justify-content: center; ' +
        'z-index: 100; gap: 12px;">' +
        '<div style="width: 40px; height: 40px; border: 4px solid rgba(0,123,255,0.2); ' +
        'border-top-color: #007bff; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>' +
        '<div style="color: #333; font-size: 14px;">' + (message || 'Cargando...') + '</div>' +
        '</div>';
    
    var $wrapper = $('#tableCartaProductos').closest('.dataTables_wrapper');
    if ($wrapper.length === 0) {
        $wrapper = $('#tableCartaProductos').parent();
    }
    
    $wrapper.css('position', 'relative');
    $wrapper.append(loaderHtml);
}

function hideTableLoader() {
    $('#dynamic-table-loader').fadeOut(500, function() {
        $(this).remove();
    });
}

function ListadoCartaDeProductos() {
    let tituloExport = "CARTA DE PRODUCTOS";
    if ($.fn.dataTable.isDataTable('#tableCartaProductos')) {
        tablaCartaProductos = $('#tableCartaProductos').DataTable();
        tablaCartaProductos.destroy();
    }
    tablaCartaProductos = $("#tableCartaProductos").DataTable({
        "ajax": {
            "url": base_url + "index.php/Productos/ListadoCartaDeProductos",
            "type": "POST",
            "data": {
            },
            beforeSend: function () {
                showTableLoader("Cargando productos…");
            },
            complete: function () {
                hideTableLoader();
            },
            error: function () {
                hideTableLoader();
            }
        },
        "bPaginate": true,
        "bProcessing": false,
        "pageLength": 15,
        "select": true,
        //"dom": "Bfrtip",
        "ordering": true,
        "filter": true,
        "info": false,
        "footer": false,
        "lengthChange": false,
        "responsive": {
            details: {
                type: 'column',
                target: -1
            }
        },
        "columns": [
            {mData: "id_producto", className: "text-center", visible: false},
            {mData: "cbarra", className: "text-center"},
            {mData: "nombre_producto", className: "text-left"},
            {mData: "nombre_familia", className: "text-left"},
            {mData: "nombre_umedida", className: "text-left"},
            {mData: "iva", className: "text-center", render: $.fn.dataTable.render.number(',', '.', 0, '$'), visible: false},
            {mData: "impto_adicional", className: "text-center", visible: false},
            {mData: "nombre_dimpresion", className: "text-center", visible: false},
            {mData: "nombre_bodega", className: "text-left"},
            {mData: "pponderado", className: "text-center", visible: false, render: $.fn.dataTable.render.number(',', '.', 0, '$')},
            {mData: "menu", className: "text-center", visible: false},
            {mData: "subfamilia", className: "text-center", visible: false},
            {mData: "receta", className: "text-center", visible: false},
            {mData: "vigente", className: "text-center", visible: false},
            {mData: "con_agregado", className: "text-center", visible: false},
            {mData: "venta_publico", className: "text-center", visible: false},
            {mData: "permite_porcion", className: "text-center", visible: false},
            {mData: "venta_segun_stock", className: "text-center", visible: false},
            {mData: "elaboracion_propia", className: "text-center", visible: false},
            {mData: "permitir_venta_sin_precio", className: "text-center", visible: false},
            {mData: "venta_uno_a_uno", className: "text-center", visible: false},
            {mData: "pedir_cantidad_en_venta", className: "text-center", visible: false},
            {mData: "ver_en_dispositivos_moviles", className: "text-center", visible: false},
            {mData: "permite_cortesia", className: "text-center", visible: false}
        ],
        'language': {
            url: "https://cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json",
            select: {
                rows: {
                    _: " (Seleccionadas %d filas)",
                    0: " (Presione una linea a seleccionar)",
                    1: " (1 Fila seleccionada)"
                }
            }
        },
        'buttons': []
    });
    
    tablaCartaProductos.on('select', function(e, dt, type, indexes) {
        var rowData = tablaCartaProductos.rows(indexes).data().toArray();

        $("#txtID").val(rowData[0].id_producto);
        $("#txtCbarra").val(rowData[0].cbarra);
        $("#txtNombre").val(rowData[0].nombre_producto);
        
        ListadoDeFamiliasMantenedor(rowData[0].nombre_familia);
        ListadoDeSubFamilias(rowData[0].subfamilia);
        ListadoDeUmedida(rowData[0].nombre_umedida);
        ListadoDestinoDeImpresion(rowData[0].nombre_dimpresion);
        ListadoDeBodega(rowData[0].nombre_bodega);
        ListadoDeImpuestosIva(rowData[0].iva);
        ListadoDeMenu(rowData[0].menu);
        ListadoDeReceta(rowData[0].receta);
        
        $("#extras").removeClass('hidden');

        $('#ckVigente').prop('checked', rowData[0].vigente == 'SI');
        $('#ckConAgregado').prop('checked', rowData[0].con_agregado == 'SI');
        $('#ckVentaPublico').prop('checked', rowData[0].venta_publico == 'SI');
        $('#ckPermitirFraccion').prop('checked', rowData[0].permite_porcion == 'SI');
        $('#ckVentaPorStock').prop('checked', rowData[0].venta_segun_stock == 'SI');
        $('#ckElaboracionLocal').prop('checked', rowData[0].elaboracion_propia == 'SI');
        $('#ckPermitirVentaSiempre').prop('checked', rowData[0].permitir_venta_sin_precio == 'SI');
        $('#ckVentaDirecta').prop('checked', rowData[0].venta_uno_a_uno == 'SI');
        $('#ckPedirCantidadEnVenta').prop('checked', rowData[0].pedir_cantidad_en_venta == 'SI');
        $('#ckVerDispositivomovil').prop('checked', rowData[0].ver_en_dispositivos_moviles == 'SI');
        $('#ckPermiteCortesia').prop('checked', rowData[0].permite_cortesia == 'SI');
        
        if (rowData[0].con_agregado == 'SI') {
            $("#btnAgregados").attr("disabled", false);
        } else {
            $("#btnAgregados").attr("disabled", true);
        }
        
        ObtenerPreciosDeVenta(rowData[0].id_producto);
        
        $('#modalNuevoProducto').modal('show');
    });
}

function crearNuevoProducto() {
    $('#txtID').val("");
    $('#txtCbarra').val("");
    $('#txtNombre').val("");
    
    // Limpiar precios existentes
    preciosExistentes = [];
    
    //ObtenerUltimoIdProducto("");
    ListadoDeFamiliasMantenedor("GENERAL");
    ListadoDeSubFamilias("");
    ListadoDeUmedida("UNIDAD");
    ListadoDestinoDeImpresion("CAJA");
    ListadoDeBodega("BODEGA PRINCIPAL");
    ListadoDeImpuestosIva("IVA 19%");
    ListadoDeMenu("");
    ListadoDeReceta("");
    
    $('#ckVigente').prop('checked', true);
    $('#ckVentaPublico').prop('checked', true);
    $('#ckPermitirVentaSiempre').prop('checked', false);
    $('#ckVentaDirecta').prop('checked', false);
    $('#ckConAgregado').prop('checked', false);
    $('#ckPermitirFraccion').prop('checked', false);
    $('#ckVentaPorStock').prop('checked', false);
    $('#ckElaboracionLocal').prop('checked', false);
    $('#ckPedirCantidadEnVenta').prop('checked', false);
    $('#ckVerDispositivomovil').prop('checked', true);
    $('#ckPermiteCortesia').prop('checked', false);
    
    $("#extras").addClass('hidden');
    $("#btnAgregados").attr("disabled", true);
    
    // Limpiar tabla de precios
    $("#detallePrecioVenta").html('');
    
    $('#modalNuevoProducto').modal({
        backdrop: 'static',
        keyboard: false
    });
}

$('#ckConAgregado').on('change', function() {
    var idProducto = $('#txtID').val();
    
    if ($('#ckConAgregado').prop('checked') && idProducto && idProducto != "") {
        $("#btnAgregados").attr("disabled", false);
    } else {
        $("#btnAgregados").attr("disabled", true);
    }
});

function ObtenerUltimoIdProducto(objSelected) {
    $.post(base_url + "index.php/Productos/ObtenerUltimoIdProducto", {
        id: ""
    }, function(datos) {
        var obj = jQuery.parseJSON(datos);
        tbl = "";
        $.each(obj, function(index, v) {
            tbl = v.new_id;
        });
        $("#txtCbarra").val(tbl);
    });
}

function CargarListasDePreciosVacias() {
    $.post(base_url + "index.php/Productos/obtenerListasDePrecios", {
        id: ""
    }, function(datos) {
        var obj = $.parseJSON(datos);
        var tbl = '';
        
        $.each(obj, function(index, v) {
            var precioExistente = null;
            if (preciosExistentes && preciosExistentes.length > 0) {
                precioExistente = preciosExistentes.find(function(p) {
                    return p.id_lprecio == v.id_detalle_lprecio;
                });
            }
            
            var precio = precioExistente ? precioExistente.total : 0;
            var comision = precioExistente ? precioExistente.monto_comision : 0;
            
            tbl += "<tr>";
            tbl += "<td style='display:none'>" + v.id_detalle_lprecio + "</td>";
            tbl += "<td style='width:30%'>" + v.nombre_lprecio + "</td>";
            
            // Input de precio con valor existente
            tbl += '<td style="width:30%" class="text-right">';
            tbl += '<input class="text-right form-control precio-input" type="number" min="0" max="1000000" value="' + precio + '">';
            tbl += '</td>';
            
            // Input de comisión con valor existente
            tbl += '<td style="width:30%" class="text-right">';
            tbl += '<input class="text-right form-control comision-input" type="number" min="0" max="100" step="0.01" value="' + comision + '" placeholder="0.00">';
            tbl += '</td>';
            
            tbl += "</tr>";
        });
        
        $("#detallePrecioVenta").html(tbl);
        maxPrecioVenta();
        maxComision();
    });
}

function GrabarProducto() {
    var msg = "";
    
    if ($('#txtNombre').val() == "" || $('#txtNombre').val() == 0) {
        msg += "- Nombre Del Producto\n";
    }

    if ($('#txtCbarra').val().trim() === "") {
    msg += "- Código de barras no puede estar en blanco\n";
    }

    if ($('#cboFamilia').val() == null || $('#cboFamilia').val() == 0) {
        msg += "- Familia Del Producto\n";
    }
    if ($('#cboDestinoDeImpresion').val() == 0 || $('#cboDestinoDeImpresion').val() == null) {
        msg += "- Destino De Impresion Del Producto\n";
    }
    if ($("#cboBodega").val() == 0 || $("#cboBodega").val() == null) {
        msg += "- Bodega\n";
    }
    
    if (msg.length) {
        showAppAlert('error',"- Falta Ingresar Los Siguiente(s) dato(s):\n" + msg, {
            duration: 5000,
            image: base_url+ 'assets/img/errorGif.gif'
        });
        return;
    }
    
    // Preparar datos del producto
    var datosProducto = {
        'id_producto': $('#txtID').val(),
        'nombre_producto': $('#txtNombre').val().toUpperCase().replace(/[,.'"$#%&]+/g, ''),
        'cbarra': $('#txtCbarra').val(),
        'impto_iva': $('#cboImpuestoIva').val(),
        'impto_ila': 1,
        'id_familia': $('#cboFamilia').val(),
        'id_umedida': $('#cboUmedida').val(),
        'precio_bruto': 0,
        'espack': false,
        'vigente': $('#ckVigente').is(":checked") ? 1 : 0,
        'inventariable': false,
        'valor_ila': 0,
        'precio_ucompra': 0,
        'pponderado': 1,
        'stock_minimo': 0,
        'cbarra_balanza': 0,
        'con_agregado': $('#ckConAgregado').is(":checked") ? 1 : 0,
        'venta_publico': $('#ckVentaPublico').is(":checked") ? 1 : 0,
        'descripcion_corta': "",
        'permite_porcion': $('#ckPermitirFraccion').is(":checked") ? 1 : 0,
        'id_subfamilia': $('#cboSubFamilia').val() || 0,
        'id_dimpresion': $('#cboDestinoDeImpresion').val(),
        'id_receta': $('#cboReceta').val() || 0,
        'orden': 0,
        'permite_cambio': false,
        'valor1': 0,
        'valor2': 0,
        'id_bodega': $('#cboBodega').val(),
        'es_menu': false,
        'id_menu': $('#cboMenu').val() || 0,
        'venta_segun_stock': $('#ckVentaPorStock').is(":checked") ? 1 : 0,
        'elaboracion_propia': $('#ckElaboracionLocal').is(":checked") ? 1 : 0,
        'permitir_venta_sin_precio': $('#ckPermitirVentaSiempre').is(":checked") ? 1 : 0,
        'venta_uno_a_uno': $('#ckVentaDirecta').is(":checked") ? 1 : 0,
        'pedir_cantidad_en_venta': $('#ckPedirCantidadEnVenta').is(":checked") ? 1 : 0,
        'detalle_preparacion': "",
        'tiempo_preparacion': 0,
        'mostrar_detalle_insumos_receta': false,
        'ver_en_dispositivos_moviles': $('#ckVerDispositivomovil').is(":checked") ? 1 : 0,
        'permite_cortesia': $('#ckPermiteCortesia').is(":checked") ? 1 : 0,
    };
    
    var idProducto = $('#txtID').val();
    
    if (idProducto && idProducto != "" && idProducto != "0") {
        // ES EDICIÓN - Grabar directamente SIN abrir modal
        grabarProductoExistente(datosProducto);
    } else {
        // ES CREACIÓN - Abrir modal de precios
        datosProductoPendiente = datosProducto;
        preciosExistentes = []; // Limpiar precios existentes
        CargarListasDePreciosVacias();
        $('#modalPrecioVentaProducto').modal({
            backdrop: 'static',
            keyboard: false
        });
    }
}

function grabarProductoExistente(datosProducto) {
    // Recolectar precios actuales (si existen en el array preciosExistentes)
    var precios = [];
    
    if (preciosExistentes && preciosExistentes.length > 0) {
        preciosExistentes.forEach(function(precio) {
            precios.push({
                id_lprecio: precio.id_lprecio,
                total: precio.total,
                monto_comision: precio.monto_comision
            });
        });
    }
    
    $.ajax({
        url: base_url + "index.php/Productos/grabarProductoConPrecios",
        type: "POST",
        data: { 
            producto: datosProducto,
            precios: precios // Enviar precios existentes para mantenerlos
        },
        dataType: "json",
        success: function(response) {
            console.log("✓ Producto actualizado:", response);
            
            if (response.error) {
                showAppAlert('error', response.error, {
                    duration: 5000,
                    image: base_url+ 'assets/img/errorGif.gif'
                });
                return;
            }
            
            // Si es venta directa, crear/actualizar insumo
            if (datosProducto.venta_uno_a_uno == 1) {
                GrabarNuevoInsumo();
            }
            
            // Cerrar modal
            $('#modalNuevoProducto').modal('hide');
            
            // Limpiar
            preciosExistentes = [];
            
            // Mostrar éxito
            showAppAlert('success', "Producto actualizado correctamente!", {
                duration: 4000,
                image: base_url + 'assets/img/success.gif'
            });
            
            // Recargar tabla
            if (typeof tablaCartaProductos !== 'undefined') {
                tablaCartaProductos.ajax.reload();
            }
        },
        error: function(xhr, status, error) {
            console.error("✗ Error:", xhr.responseText);
            showAppAlert('error', "No se pudo actualizar: " + error, {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
        }
    });
}

function grabarPreciosYProducto() {
    console.log("=== INICIANDO GRABACIÓN ===");
    
    if (!datosProductoPendiente) {
        showAppAlert('error', "No hay datos del producto", {
            duration: 5000,
            image: base_url+ 'assets/img/errorGif.gif'
        });
        return;
    }
    
    // Recolectar precios y comisiones de la tabla
    var precios = [];
    var tienePrecio = false;
    
    $("#detallePrecioVenta tr").each(function() {
        var idLista = $(this).find("td").eq(0).text().trim();
        var precio = parseInt($(this).find(".precio-input").val()) || 0;
        var comision = parseFloat($(this).find(".comision-input").val()) || 0;
        
        if (precio > 0) {
            tienePrecio = true;
            precios.push({
                id_lprecio: idLista,
                total: precio,
                monto_comision: comision
            });
        }
    });
    
    console.log("Precios recolectados:", precios);
    
    // Solo validar precio si no permite venta sin precio
    if (!$('#ckPermitirVentaSiempre').is(':checked')) {
        if (!tienePrecio) {
            showAppAlert('error', "Debe ingresar al menos un precio mayor a cero", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            }); 
            return;
        }
    }
    
    // Enviar todo junto
    $.ajax({
        url: base_url + "index.php/Productos/grabarProductoConPrecios",
        type: "POST",
        data: { 
            producto: datosProductoPendiente,
            precios: precios
        },
        dataType: "json",
        success: function(response) {
            console.log("✓ Respuesta:", response);
            
            if (response.error) {
                showAppAlert('error', response.error, {
                    duration: 5000,
                    image: base_url+ 'assets/img/errorGif.gif'
                }); 
                return;
            }
            
            // Si es venta directa, crear insumo
            if (datosProductoPendiente.venta_uno_a_uno == 1) {
                GrabarNuevoInsumo();
            }
            
            // Cerrar modales
            $('#modalPrecioVentaProducto').modal('hide');
            $('#modalNuevoProducto').modal('hide');
            
            // Limpiar
            datosProductoPendiente = null;
            preciosExistentes = [];
            
            // Mostrar éxito
            showAppAlert('success', "Producto creado y guardado correctamente!", {
                duration: 4000,
                image: base_url + 'assets/img/success.gif'
            });
            
            // Recargar tabla
            if (typeof tablaCartaProductos !== 'undefined') {
                tablaCartaProductos.ajax.reload();
            }
        },
        error: function(xhr, status, error) {
            console.error("✗ Error:", xhr.responseText);
            showAppAlert('error', "No se pudo guardar: " + error, {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            }); 
        }
    });
}

function maxComision() {
    // Usar delegación de eventos en el contenedor padre que siempre existe
    $(document).off('input', '.comision-input').on('input', '.comision-input', function() {
        var value = $(this).val();
        var n = parseFloat(value) || 0;
        
        // Limitar a 2 decimales
        n = Math.round(n * 100) / 100;
        
        if (n > 100) n = 100;
        if (n < 0) n = 0;
        
        $(this).val(n);
    });
}

$(document).ready(function() {
    maxPrecioVenta();
    maxComision();
    console.log("✓ Sistema de precios inicializado");
});

function GrabarNuevoInsumo() {
    var miJson = {
        'id_insumo': "",
        'cbarra': datosProductoPendiente.cbarra,
        'nombre_insumo': datosProductoPendiente.nombre_producto,
        'id_grupo_insumo': 1,
        'id_impto_iva': datosProductoPendiente.impto_iva,
        'id_impto_adicional': 1,
        'umedida_inventario': 1,
        'id_umedida_inventario': 1,
        'umedida_receta': 1,
        'id_umedida_receta': 1,
        'costo_neto_ppp': 0,
        'costo_bruto_ppp': 0,
        'precio_ucompra': 0,
        'observaciones': "",
        'inv_cierre': false
    };
    
    $.post(base_url + "index.php/Insumos/grabarInsumo", {
        'prod': miJson
    }).done(function() {
        console.log("Insumo creado correctamente");
    }).fail(function() {
        console.log("Error al crear el insumo");
    });
}

function sololetrasyNumeros(e, espace) {
    key = e.keyCode || e.which;
    teclado = String.fromCharCode(key).toLowerCase();
    letras = "qwertyuiopasdfghjklñzxcvbnm1234567890 ";
    especiales = "8-37-38-46-164";
    teclado_especial = false;

    for (var i in especiales) {
        if (key == especiales[i]) {
            teclado_especial = true;
            break;
        }
    }

    if (espace == true) {
        if (e.keyCode == 32) {
            return false;
        }
    }

    if (letras.indexOf(teclado) == -1 && !teclado_especial) {
        return false;
    }
}

function maxPrecioVenta() {
    // Usar delegación de eventos en el contenedor padre que siempre existe
    $(document).off('input', '.precio-input').on('input', '.precio-input', function() {
        var value = $(this).val();
        if (value.length > 7) {
            value = value.slice(0, 7);
        }
        var n = parseInt(value) || 0;
        if (n > 1000000) n = 1000000;
        if (n < 0) n = 0;
        $(this).val(n);
    });
}

function cancelarCreacionProducto() {
    swal({
        title: "¿Está seguro?",
        text: "Si cancela, perderá toda la información ingresada del producto",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, cancelar",
        cancelButtonText: "No, continuar editando",
        closeOnConfirm: true,
        closeOnCancel: true
    }, function(isConfirm) {

        if (isConfirm) {
            datosProductoPendiente = null;
            $('#modalPrecioVentaProducto').modal('hide');
        }
    });
}
</script>