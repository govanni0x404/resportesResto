<script>
var base_url = "<?php echo base_url(); ?>";
var compraValidada = false; // Variable para controlar el estado de validación

$(document).ready(function() {
    console.log("=== DOCUMENTO LISTO ===");
    
    $('#FechaCompra').daterangepicker({
        "singleDatePicker": true,
        "format": 'DD-MM-YYYY',
    });
    ListadoTipoDeDocumento("FACTURA ELECTRONICA");
    ListadoInsumos("");
    ListarProveedores("");
    
    // Todos los campos habilitados desde el inicio
    $('#GrabaIngresoDeCompra').attr("disabled", true);
    
    console.log("Botón GrabaIngresoDeCompra encontrado:", $('#GrabaIngresoDeCompra').length);
    console.log("Botón btnAgregarALista encontrado:", $('#btnAgregarALista').length);
});

// Variable para controlar si estamos en proceso de validación
var validandoCompra = false;

// Validar cuando se selecciona un producto
$('#cboProductosCompra').on('change', function() {
    var idInsumo = $(this).val();
    
    // Si no hay producto seleccionado, salir
    if (!idInsumo || idInsumo == 0) {
        $('#spanUnidadCantidad').text('');
        return;
    }
    
    // Si ya está validada la compra, solo cargar la unidad de medida
    if (compraValidada) {
        cargarUnidadMedida(idInsumo);
        return;
    }
    
    // Si estamos en proceso de validación, no hacer nada
    if (validandoCompra) {
        return;
    }
    
    // Verificar que los campos de cabecera estén llenos
    var idProveedor = $('#cboProveedorCompra').val();
    var idTdocumento = $('#cboDocumentoCompra').val();
    var NumeroDocumento = $('#FolioDocumento').val();
    var fechaSeleccionada = $('#FechaCompra').val();
    
    if (!idProveedor || idProveedor == 0) {
        // Usar setTimeout para asegurar que el combo se limpie después del evento
        setTimeout(function() {
            $('#cboProductosCompra').val(null).trigger('change');
        }, 100);
        showAppAlert('error',"- Debe seleccionar el proveedor primero", {
            duration: 5000,
            image: base_url+ 'assets/img/errorGif.gif'
        });
        return;
    }
    
    if (!idTdocumento || idTdocumento == 0) {
        setTimeout(function() {
            $('#cboProductosCompra').val(null).trigger('change');
        }, 100);
        showAppAlert('error',"- Debe seleccionar el documento de compra primero", {
            duration: 5000,
            image: base_url+ 'assets/img/errorGif.gif'
        });
        return;
    }
    
    if (!NumeroDocumento || NumeroDocumento == 0 || NumeroDocumento.length == 0) {
        setTimeout(function() {
            $('#cboProductosCompra').val(null).trigger('change');
        }, 100);
        showAppAlert('error',"- Debe indicar el folio dle documento primero", {
            duration: 5000,
            image: base_url+ 'assets/img/errorGif.gif'
        });
        return;
    }
    
    if (!fechaSeleccionada || fechaSeleccionada == 'undefined' || fechaSeleccionada.length == 0) {
        setTimeout(function() {
            $('#cboProductosCompra').val(null).trigger('change');
        }, 100);
        showAppAlert('error',"- Debe indicar la fecha de compra primero", {
            duration: 5000,
            image: base_url+ 'assets/img/errorGif.gif'
        });
        return;
    }
    
    // Guardar el producto seleccionado
    var productoSeleccionado = idInsumo;
    
    // Marcar que estamos validando
    validandoCompra = true;
    
    // Validar la compra
    ValidarCompra(function(success) {
        validandoCompra = false;
        
        if (success) {
            // Si la validación fue exitosa, cargar unidad de medida
            cargarUnidadMedida(productoSeleccionado);
        } else {
            // Si falló, limpiar la selección
            setTimeout(function() {
                $('#cboProductosCompra').val(null).trigger('change');
            }, 100);
            $('#spanUnidadCantidad').text('');
        }
    });
});

function cargarUnidadMedida(idInsumo) {
    $.post(base_url + "index.php/Insumos/ObtenerUnidadMedida", {
        id_insumo: idInsumo
    }, function(datos) {
        try {
            var obj = $.parseJSON(datos);
            if (obj.unidad_medida) {
                $('#spanUnidadCantidad').text(obj.unidad_medida);
                $('#CantidadComprada').attr('placeholder', '');
            } else {
                $('#spanUnidadCantidad').text(''); 
                $('#spanUnidadBruto').text('');
            }
        } catch (e) {
            console.log("Error al obtener unidad de medida");
        }
    });
}

function ValidarCompra(callback) {
    var idProveedor = $('#cboProveedorCompra').val();
    var idTdocumento = $('#cboDocumentoCompra').val();
    var NumeroDocumento = $('#FolioDocumento').val();

    var miData = {
        'id_proveedor': idProveedor,
        'id_tdocumento': idTdocumento,
        'numero_documento': NumeroDocumento
    };

    $.post(base_url + "index.php/Compras/verificarSiExisteLaCompra", {
            midata: miData
        }, function(datos) {
            var obj = jQuery.parseJSON(datos);
            var idCompra = 0;
            var fecha = "";
            
            $.each(obj, function(index, v) {
                idCompra = v.id_compra;
                fecha = v.fecha;
            });

            if (idCompra > 0) {
                showAppAlert('COMPRA DUPLICADA',"- El documento indicado ya se encuentra registrado en otra compra del proveedor. Fue ingresada el día: " + fecha, {
                    duration: 5000,
                    image: base_url+ 'assets/img/errorGif.gif'
                }); 
                
                $('#FolioDocumento').val('');
                compraValidada = false;
                
                if (callback) callback(false);
            } else {
                // Validación exitosa - bloquear cabecera
                compraValidada = true;
                bloquearCabecera();
                
                /*swal({
                    title: "Validación Exitosa",
                    text: "La compra puede ser registrada. Ahora puede agregar productos.",
                    type: "success",
                    timer: 1500,
                    showConfirmButton: false
                });
                */
                if (callback) callback(true);
            }
        })
        .fail(function() {
            showAppAlert('error',"- Se produjo un error al verificar la compra", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
            if (callback) callback(false);
        });
}

function calcularValores() {
    var cantidadComprada = 0;
    var precioBrutoUn = 0;
    var totalBruto = 0;

    cantidadComprada = $('#CantidadComprada').val();
    if (cantidadComprada.length == 0) {
        cantidadComprada = 0;
    }
    precioBrutoUn = $('#BrutoUnitario').val();
    if (precioBrutoUn.length == 0) {
        precioBrutoUn = 0;
    }
    if (cantidadComprada == 0 || precioBrutoUn == 0) {
        $('#TotalBruto').val(0);
    } else {
        totalBruto = parseFloat(cantidadComprada) * parseFloat(precioBrutoUn);
        $('#TotalBruto').val(totalBruto);
    }
}

$('#TotalBruto').click(function() {
    calcularValores();
})

$('#CantidadComprada, #BrutoUnitario').on('input', function() {
    calcularValores();
});

function agregarProductoALaGrilla() {

    var idProveedor = $('#cboProveedorCompra').val();
    if (!idProveedor || idProveedor == 0) {
        showAppAlert('error', "- Debe seleccionar el proveedor!", {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }

    var idDocumento = $('#cboDocumentoCompra').val();
    if (!idDocumento || idDocumento == 0) {
        showAppAlert('error', "- Debe seleccionar el documento de compra!", {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }

    var folio = $('#FolioDocumento').val();
    if (!folio || folio.length == 0 || folio == "0") {
        showAppAlert('error', "- Debe ingresar el folio del documento!", {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }

    var ProductoComprado = $("#cboProductosCompra").val();
    if (!ProductoComprado || ProductoComprado == 0) {
        showAppAlert('error', "- Debe seleccionar el producto comprado!", {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }

    if (!compraValidada) {
        showAppAlert('error',"- Debe seleccionar un producto para validar la compra primero", {
            duration: 5000,
            image: base_url+ 'assets/img/errorGif.gif'
        });
        return;
    }
    
    var TotalCompra;
    var CantidadCompra = parseFloat($('#CantidadComprada').val());
    var BrutoUn = parseFloat($('#BrutoUnitario').val());
    var BrutoTotal = parseFloat($('#TotalBruto').val());
    var ProductoComprado = $("#cboProductosCompra").val();
    
    if (ProductoComprado == null || ProductoComprado == 0) {
        showAppAlert('error',"- Debe seleccionar el producto comprado!", {
            duration: 5000,
            image: base_url+ 'assets/img/errorGif.gif'
        });
        return;
    }
    if (CantidadCompra == null || CantidadCompra <= 0 || isNaN(CantidadCompra)) {
        showAppAlert('error',"- Debe indicar la cantidad comprada!", {
            duration: 5000,
            image: base_url+ 'assets/img/errorGif.gif'
        });
        return;
    }

    if (BrutoUn == null || BrutoUn <= 0 || isNaN(BrutoUn)) {
        showAppAlert('error',"- Debe indicar el precio bruto de la compra!", {
            duration: 5000,
            image: base_url+ 'assets/img/errorGif.gif'
        });
        return;
    }

    if (BrutoTotal == null || BrutoTotal <= 0 || isNaN(BrutoTotal)) {
        showAppAlert('error',"- Debe indicar el precio total de la compra!", {
            duration: 5000,
            image: base_url+ 'assets/img/errorGif.gif'
        });
        return;
    }
    
    $.post(base_url + "index.php/Insumos/ObtenerDatosInsumos", {
            id_insumo: ProductoComprado
        }, function(datos) {
            var obj = jQuery.parseJSON(datos);
            $.each(obj, function(index, v) {
                tbl = "<tr id='" + v.rnd + "'>";
                tbl = tbl + "<td style='display:none;'>" + v.id_insumo + "</td>";
                tbl = tbl + "<td class='text-center'>" + v.nombre_insumo + "</td>";
                tbl = tbl + "<td class='text-center'>" + commaSeparateNumber($('#CantidadComprada').val()) + " " + (v.unidad_medida ? v.unidad_medida : "") + "</td>";
                tbl = tbl + "<td class='text-center'>" + commaSeparateNumber($('#BrutoUnitario').val()) + "</td>";
                tbl = tbl + "<td class='text-center TotalBRUTO'>" + commaSeparateNumber($('#TotalBruto').val()) + "</td>";
                tbl = tbl + "<td class='text-center'><a onclick='removeRow(" + v.rnd + ")' href='#' class='btn btn-danger btn-icon'><i class='fa fa-trash-o'></i></a></td>";
                tbl = tbl + "</tr>";
                $('#detalleCompra').append(tbl);
                nuevoProducto();
            });
        }).done(function() {
            sumarTotalesTabla();
        })
        .fail(function() {
            showAppAlert('error',"- Se produjo un error al obtener los datos del producto", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
        });
}

function bloquearCabecera() {
    $("#cboProveedorCompra").attr("disabled", true);
    $("#cboDocumentoCompra").attr("disabled", true);
    $("#FolioDocumento").attr("disabled", true);
    $("#FechaCompra").attr("disabled", true);
}

function desbloquearCabecera() {
    $("#cboProveedorCompra").attr("disabled", false);
    $("#cboDocumentoCompra").attr("disabled", false);
    $("#FolioDocumento").attr("disabled", false);
    $("#FechaCompra").attr("disabled", false);
    compraValidada = false;
}

function sumarTotalesTabla() {
    var totalCompra = 0;
    $('.TotalBRUTO').each(function() {
        totalCompra += parseFloat($(this).text().replace(/,/g, ''), 10);
    });
    if (totalCompra > 0) {
        $('#GrabaIngresoDeCompra').attr("disabled", false);
    } else {
        $('#GrabaIngresoDeCompra').attr("disabled", true);
    }
    $('#txtTotalCompra').val(totalCompra.toFixed(0));
}

$('#btnAgregarALista').click(function() {
    agregarProductoALaGrilla();
})

$('#GrabaIngresoDeCompra').click(function() {
    console.log("=== BOTÓN GRABAR COMPRA PRESIONADO ===");
    GrabarIngresoDeCompra();
})

function GrabarIngresoDeCompra() {
    console.log("=== FUNCIÓN GrabarIngresoDeCompra INICIADA ===");
    
    if (!compraValidada) {
        console.log("ERROR: compraValidada =", compraValidada);
        showAppAlert('error',"- Debe agregar al menos un producto antes de grabar!", {
            duration: 5000,
            image: base_url+ 'assets/img/errorGif.gif'
        });
        return;
    }
    
    if ($('#cboProveedorCompra').val() == 0) {
        showAppAlert('error',"- No seleccionó el proveedor!", {
            duration: 5000,
            image: base_url+ 'assets/img/errorGif.gif'
        });
        return;
    }
    if ($('#cboDocumentoCompra').val() == 0) {
        showAppAlert('error',"- No seleccionó el documento de compra!", {
            duration: 5000,
            image: base_url+ 'assets/img/errorGif.gif'
        });
        return;
    }
    if ($('#FolioDocumento').val() == 0) {
        showAppAlert('error',"- No ingreso el folio del documento!", {
            duration: 5000,
            image: base_url+ 'assets/img/errorGif.gif'
        });
        return;
    }

    var detalleCompra = [];

    // Recorre la tabla para armar el detalle de la compra
    $("#detalleCompra tr").each(function(index) {
        var idInsumo, Cantidad, PrecioBrutoUn, PrecioBrutoTotal;

        $(this).children("td").each(function(index2) {
            switch (index2) {
                case 0:
                    idInsumo = $(this).html();
                    break;
                case 1:
                    // Saltar el nombre del producto
                    break;
                case 2:
                    // Cantidad - eliminar unidad de medida y comas
                    var textoCompleto = $(this).text().trim();
                    // Extraer solo el número (antes del espacio si hay unidad)
                    Cantidad = textoCompleto.split(' ')[0].replace(/,/g, '');
                    break;
                case 3:
                    PrecioBrutoUn = $(this).text().replace(/,/g, '').trim();
                    break;
                case 4:
                    PrecioBrutoTotal = $(this).text().replace(/,/g, '').trim();
                    detalleCompra.push({
                        'id_insumo': idInsumo,
                        'cantidad': Cantidad,
                        'bruto_unitario': PrecioBrutoUn,
                        'bruto_total': PrecioBrutoTotal,
                    });
                    break;
            }
        });
    });

    // Validar que hay productos en el detalle
    if (detalleCompra.length === 0) {
        showAppAlert('error',"- Debe agregar al menos un producto a la compra!", {
            duration: 5000,
            image: base_url+ 'assets/img/errorGif.gif'
        });
        return;
    }

    var idProveedor = $('#cboProveedorCompra').val();
    var idDocumentoCompra = $('#cboDocumentoCompra').val();
    var FolioDocumento = $('#FolioDocumento').val();
    var FechaCompra = $('#FechaCompra').val();
    var TotalCompra = $('#txtTotalCompra').val();

    console.log("Fecha original:", FechaCompra);

    // Lógica para el formato de fecha - más robusta
    if (FechaCompra.indexOf('/') > -1) {
        // Formato DD/MM/YYYY o MM/DD/YYYY
        var partes = FechaCompra.split('/');
        FechaCompra = partes[2] + '-' + partes[1] + '-' + partes[0];
    } else if (FechaCompra.indexOf('-') > -1) {
        var partes = FechaCompra.split('-');
        // Si el primer elemento tiene 2 dígitos, es DD-MM-YYYY
        if (partes[0].length == 2) {
            FechaCompra = partes[2] + '-' + partes[1] + '-' + partes[0];
        }
        // Si ya está en YYYY-MM-DD, no hacer nada
    }
    
    console.log("Fecha convertida:", FechaCompra);

    var miJson = {
        'id_proveedor': idProveedor,
        'fecha': FechaCompra,
        'id_tdocumento': idDocumentoCompra,
        'numero_documento': FolioDocumento,
        'total': TotalCompra,
    };

    console.log("=== DATOS A ENVIAR ===");
    console.log("Detalle a enviar:", detalleCompra);
    console.log("JSON compra:", miJson);
    console.log("Cantidad de items:", detalleCompra.length);

    // Deshabilitar botón para evitar doble click
    $('#GrabaIngresoDeCompra').attr("disabled", true);

    // PASO 1: GRABAR CABECERA DE COMPRA Y OBTENER EL ID
    $.post(base_url + "index.php/Compras/grabarCompra", {
            el_json: miJson
        }, function() {})
        .done(function(datos) {
            console.log("=== RESPUESTA GRABAR COMPRA ===");
            console.log("Respuesta grabarCompra:", datos);
            
            try {
                var obj = $.parseJSON(datos);
                console.log("Objeto parseado:", obj);
                
                // Verificar si es un error de duplicado
                if (obj.code === 'DUPLICATE') {
                    showAppAlert('COMPRA DUPLICADA',obj.message + "\nFecha: " + obj.fecha, {
                        duration: 5000,
                        image: base_url+ 'assets/img/errorGif.gif'
                    });
                    $('#GrabaIngresoDeCompra').attr("disabled", false);
                    return;
                }
                
                // Verificar si hay un error general
                if (obj.error) {
                    showAppAlert('error', obj.error, {
                        duration: 5000,
                        image: base_url+ 'assets/img/errorGif.gif'
                    });
                    $('#GrabaIngresoDeCompra').attr("disabled", false);
                    return;
                }
                
                var idCompra = 0;

                if (obj.id) { 
                    idCompra = obj.id;
                } else if (obj.id_compra) {
                    idCompra = obj.id_compra;
                } else {
                    $.each(obj, function(index, ve) {
                        if (ve.id) {
                            idCompra = ve.id;
                        } else if (ve.id_compra) {
                            idCompra = ve.id_compra;
                        }
                    });
                }
                
                console.log("ID Compra obtenido:", idCompra);

                if (idCompra == 0) {
                    showAppAlert('error',"- No se pudo obtener el ID de la compra. La cabecera NO se grabó", {
                        duration: 5000,
                        image: base_url+ 'assets/img/errorGif.gif'
                    });
                    $('#GrabaIngresoDeCompra').attr("disabled", false);
                    return;
                }

                // PASO 2: GRABAR DETALLE DE COMPRA
                $.post(base_url + "index.php/Compras/grabarDetalleCompra", {
                        id_compra: idCompra,
                        detalle: detalleCompra
                    }, function() {})
                    .done(function(datos) {
                        console.log("Respuesta grabarDetalleCompra:", datos);
                        try {
                             var detalleObj = $.parseJSON(datos);
                             if (detalleObj.errors && detalleObj.errors.length > 0) {
                                showAppAlert('error',"- Se grabó la cabecera, pero hubo errores al grabar algunos detalles.", {
                                    duration: 5000,
                                    image: base_url+ 'assets/img/errorGif.gif'
                                });
                             } else {
                                nuevaCompra();
                                showAppAlert('success', "El ingreso de la compra se ingresó con éxito!", {
                                    duration: 4000,
                                    image: base_url + 'assets/img/success.gif'
                                });
                             }
                        } catch (e) {
                            showAppAlert('success', "El ingreso de la compra se ingresó con éxito!", {
                                duration: 4000,
                                image: base_url + 'assets/img/success.gif'
                            });
                            nuevaCompra();
                        }
                    })
                    .fail(function(jqXHR, textStatus, errorThrown) {
                        console.error("Error en grabarDetalleCompra:", textStatus, errorThrown);
                        showAppAlert('error',"- Se produjo un error al grabar el detalle de la compra (posiblemente la cabecera sí se grabó)", {
                            duration: 5000,
                            image: base_url+ 'assets/img/errorGif.gif'
                        });
                        $('#GrabaIngresoDeCompra').attr("disabled", false);
                    });

            } catch (e) {
                let positionError = datos.search("ERROR:");
                let errorMessage = datos.substr(positionError, 150);
                showAppAlert('error',"- Se produjo un error al procesar la respuesta de la cabecera, favor reintente \n(" + e + ")\n" + errorMessage, {
                    duration: 5000,
                    image: base_url+ 'assets/img/errorGif.gif'
                });
                $('#GrabaIngresoDeCompra').attr("disabled", false);
            }
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
            console.error("Error en grabarCompra:", textStatus, errorThrown);
            showAppAlert('error',"- Se produjo un error al grabar la cabecera de la compra. Favor contacte a su administrador", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
            $('#GrabaIngresoDeCompra').attr("disabled", false);
        });
}

function nuevaCompra() {
    ListarProveedores("");
    ListadoTipoDeDocumento("");
    $('#FolioDocumento').val("0");
    
    // Obtener fecha actual en formato DD-MM-YYYY
    var hoy = new Date();
    var dia = ("0" + hoy.getDate()).slice(-2);
    var mes = ("0" + (hoy.getMonth() + 1)).slice(-2);
    var anio = hoy.getFullYear();
    var fechaFormateada = dia + '-' + mes + '-' + anio;
    
    $('#FechaCompra').val(fechaFormateada);

    ListadoInsumos("");
    $('#CantidadComprada').val("0");
    $('#BrutoUnitario').val("0");
    $('#TotalBruto').val("");
    $('#detalleCompra').html("");
    $('#txtTotalCompra').val("0");
    
    // Desbloquear cabecera
    desbloquearCabecera();
    $('#GrabaIngresoDeCompra').attr("disabled", true);
}

function nuevoProducto() {
    ListadoInsumos("");
    $('#CantidadComprada').val("1");
    $('#BrutoUnitario').val("0");
    $('#TotalBruto').val("");
}

function removeRow(rnd) {
    swal({
            title: "¿Está seguro?",
            text: "¿Confirma que desea quitar el producto de la lista?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            cancelButtonText: "Cancelar",
            confirmButtonText: "Eliminar",
            closeOnConfirm: true
        },
        function() {
            $('#' + rnd).remove();
            sumarTotalesTabla();
            
            // Si se eliminan todos los productos, desbloquear cabecera
            if ($('#detalleCompra tr').length === 0) {
                desbloquearCabecera();
            }
        });
}
</script>