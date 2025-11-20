<script>
var base_url = "<?php echo base_url(); ?>";
tablaCartaProductos = $('#tableCartaProductos').DataTable();
var datosProductoPendiente = null; // Variable global para almacenar temporalmente los datos del producto

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
            "data": {}
        },
        "bPaginate": true,
        "bProcessing": true,
        "pageLength": 15,
        "select": true,
        "dom": "Bfrtip",
        "ordering": true,
        "filter": true,
        "info": false,
        "footer": false,
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
            {mData: "nombre_familia", className: "text-center"},
            {mData: "nombre_umedida", className: "text-center"},
            {mData: "iva", className: "text-center", render: $.fn.dataTable.render.number(',', '.', 0, '$'), visible: false},
            {mData: "impto_adicional", className: "text-center", visible: false},
            {mData: "nombre_dimpresion", className: "text-center", visible: false},
            {mData: "nombre_bodega", className: "text-center"},
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
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json",
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
    
    ObtenerUltimoIdProducto("");
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
    
    // Cargar listas de precios vacías para nuevo producto
    CargarListasDePreciosVacias();
    
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
            tbl += "<tr>";
            tbl += "<td style='display:none'>" + v.id_detalle_lprecio + "</td>";
            tbl += "<td style='width:40%'>" + v.nombre_lprecio + "</td>";
            tbl += '<td style="width:40%" class="text-right">';
            tbl += '<input class="text-right form-control precio-input" type="number" min="0" max="1000000" value="0">';
            tbl += '</td>';
            tbl += "</tr>";
        });
        
        $("#detallePrecioVenta").html(tbl);
        maxPrecioVenta();
    });
}

// FUNCIÓN MODIFICADA: Ahora valida y abre modal de precios
function GrabarProducto() {
    var msg = "";
    
    if ($('#txtNombre').val() == "" || $('#txtNombre').val() == 0) {
        msg += "- Nombre Del Producto\n";
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
    
    // Preparar datos del producto y guardarlos temporalmente
    datosProductoPendiente = {
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
    
    // Siempre cargar listas de precios al abrir el modal
    CargarListasDePreciosVacias();

    // Abrir modal de precios
    $('#modalPrecioVentaProducto').modal({
        backdrop: 'static',
        keyboard: false
    });
}

function grabarPreciosYProducto() {
    console.log("=== INICIANDO GRABACIÓN ===");
    
    if (!datosProductoPendiente) {
        swal("Error", "No hay datos del producto", "error");
        return;
    }
    
    // Recolectar precios de la tabla
    var precios = [];
    var tienePrecio = false;
    
    $("#detallePrecioVenta tr").each(function() {
        var idLista = $(this).find("td").eq(0).text().trim();
        var precio = parseInt($(this).find(".precio-input").val()) || 0;
        
        if (precio > 0) {
            tienePrecio = true;
            precios.push({
                id_lprecio: idLista,
                total: precio
            });
        }
    });
    
    console.log("Precios recolectados:", precios);
    
    if (!tienePrecio) {
        swal("Error", "Debe ingresar al menos un precio mayor a cero", "error");
        return;
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
                swal("Error", response.error, "error");
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
            
            // Mostrar éxito
            swal("¡Éxito!", "Producto guardado con " + response.precios_grabados + " precio(s)", "success");
            
            // Recargar tabla
            if (typeof tablaCartaProductos !== 'undefined') {
                tablaCartaProductos.ajax.reload();
            }
        },
        error: function(xhr, status, error) {
            console.error("✗ Error:", xhr.responseText);
            swal("Error", "No se pudo guardar: " + error, "error");
        }
    });
}

// 4. Inicializar
$(document).ready(function() {
    maxPrecioVenta();
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
        icon: "warning",
        buttons: {
            cancel: {
                text: "No, continuar editando",
                value: false,
                visible: true,
                className: "btn-secondary",
                closeModal: true,
            },
            confirm: {
                text: "Sí, cancelar",
                value: true,
                visible: true,
                className: "btn-danger",
                closeModal: true
            }
        }
    }).then((willCancel) => {
        if (willCancel) {
            // Limpiar datos pendientes
            datosProductoPendiente = null;
            
            // Cerrar ambos modales
            $('#modalPrecioVentaProducto').modal('hide');
            $('#modalNuevoProducto').modal('hide');
            
            swal("Cancelado", "No se creó el producto", "info");
        }
    });
}

// Ejecutar cuando se carga el documento
$(document).ready(function() {
    maxPrecioVenta();
});
</script>