<script>
var base_url = "<?php echo base_url(); ?>";
$(document).ready(function() {
    $('#Fecha').daterangepicker({
        "singleDatePicker": true,
        "autoApply": true,
        "format": "DD-MM-YYYY",
        "locale": {
            "format": "DD-MM-YYYY",
            "separator": " - ",
            "applyLabel": "Aplicar",
            "cancelLabel": "Cancelar",
            "fromLabel": "Desde",
            "toLabel": "Hasta",
            "customRangeLabel": "Rango personalizado",
            "weekLabel": "S",
            "daysOfWeek": ["Do", "Lu", "Ma", "Mi", "Ju", "Vi", "Sa"],
            "monthNames": [
                "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
                "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
            ],
            "firstDay": 1
        }
    });
    ListadoBodegas("");
    ListadoInsumos("");
});

$('#btnValidarIngreso').on('click', function() {
    if ($('#cboBodega').val() > 0) {
        $("#cboInsumo").attr("disabled", false);
        $("#cboBodega").attr("disabled", true);
        $("#btnValidarIngreso").attr("disabled", true);
    }

});


function obtenerStockActualPorBodega() {
    $.post(base_url + "index.php/EntradaBodega/obtenerStockActualPorBodega", {
        'id_insumo': $('#cboInsumo').val(),
        'id_bodega': $('#cboBodega').val(),
    }, function(datos) {
        var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
        $.each(obj, function(index, v) { // recorro el array
            $('#txtStockActual').val(v.saldo);
            $('#UmedidaInsumo').val(v.nombre_umedida);

        }); //fin each
    });
}

$('#cboInsumo').on('change', function() {
    obtenerStockActualPorBodega();
});


function agregarProductoALaGrilla() {

    if ($('#cboBodega').val() === '' || $('#cboBodega').val() === null || $('#cboBodega').val() == 0) {
        showAppAlert('error', "Debe seleccionar la Bodega antes de agregar productos!", {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }

    if ($('#cboInsumo').val() === '' || $('#cboInsumo').val() === null || $('#cboInsumo').val() == 0) {
        showAppAlert('error', "Debe seleccionar el insumo antes de agregar productos!", {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }
    
    var Cantidad_a_Ingresar = parseFloat($('#CantidadNew').val());
    var umedidaIngreso = parseFloat($('#UmedidaInsumo').val());
    ProductoComprado = $("#cboInsumo").val();

    if (ProductoComprado == null) {
        showAppAlert('error',"Debe Seleccionar El Insumo!", {
            duration: 5000,
            image: base_url+ 'assets/img/errorGif.gif'
        });
        return;
    }
    if (Cantidad_a_Ingresar == null || Cantidad_a_Ingresar <= 0 || isNaN(Cantidad_a_Ingresar)) {
        showAppAlert('error',"Debe Indicar La Cantidad De Ingreso!", {
            duration: 5000,
            image: base_url+ 'assets/img/errorGif.gif'
        });
        return;
    }
    $.post(base_url + "index.php/Insumos/ObtenerDatosInsumos", {
            id_insumo: ProductoComprado
        }, function(datos) {
            var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
            $.each(obj, function(index, v) { // recorro el array

                tbl = "<tr id='" + v.rnd + "'>";
                tbl = tbl + "<td style='display:none;'>" + v.id_insumo + "</td>";
                tbl = tbl + "<td class='text-center'>" + v.nombre_insumo + "</td>";
                tbl = tbl + "<td style='display:none;' class='text-center'>" + commaSeparateNumber($('#txtStockActual').val()) + "</td>";
                tbl = tbl + "<td class='text-center'>" + commaSeparateNumber($('#CantidadNew').val()) + "</td>";
                tbl = tbl + "<td class='text-center' ><a  onclick='removeRow(" + v.rnd +
                    ")' href='#' class='btn btn-danger btn-icon'><i class='fa fa-trash-o'></i></a></td>";
                tbl = tbl + "</tr>";
                $('#detalleEntradaBodega').append(tbl);
                nuevoProducto();

            }); //fin each

        }).done(function() {
            //sumarTotalesTabla();
            $("#txtObservaciones").val("");
        })
        .fail(function() {
            showAppAlert('error',"Se produjo un error al realizar la consulta!" ,{
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
        })
        .always(function() {
            //alert( "finished" );
        });
}

function nuevoProducto() {
    ListadoInsumos("cboInsumo", "");
    $('#CantidadNew').val("0");
    $('#txtStockActual').val("0");
    $('#UmedidaInsumo').val("");
}

function removeRow(rnd) {
    swal({
            title: "Esta seguro?",
            text: "Confirma que desea quitar el producto de la lista?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#C9372C",
            cancelButtonText: "Cancelar",
            confirmButtonText: "Eliminar",
            closeOnConfirm: true
        },
        function() {
            $('#' + rnd).remove();
        });
}

function grabarEntradaBodega() {
    var IdBodega = $("#cboBodega").val();
    var FechaIngreso = $('#Fecha').data('daterangepicker').endDate.format('YYYY-MM-DD');

    if (IdBodega == null || IdBodega == 0) {
        showAppAlert('error',"Debe Seleccionar La Bodega!" ,{
            duration: 5000,
            image: base_url+ 'assets/img/errorGif.gif'
        });
        return;
    }

    // --- construir detalle ---
    var detalleEntradaBodega = [];
    $("#tableEntradaBodega tbody tr").each(function () {
        var idInsumo = $(this).find("td:eq(0)").text();
        var stockAnterior = $(this).find("td:eq(2)").text().replace(/,/g, '');
        var cantidadNueva = $(this).find("td:eq(3)").text().replace(/,/g, '');

        detalleEntradaBodega.push({
            'id_insumo': idInsumo,
            'cantidad': cantidadNueva,
            'stock_anterior': stockAnterior
        });
    });

    // // --- datos de cabecera ---
    // var miJson = {
    //     'fecha': FechaIngreso,
    //     'id_bodega': IdBodega,
    //     'observaciones': 'Entrada manual'
    // };

    var Observaciones = $('#txtObservaciones').val().trim();
    var miJson = {
        'fecha': FechaIngreso,
        'id_bodega': IdBodega,
        'observaciones': Observaciones
    };

    // --- grabar cabecera ---
    $.post(base_url + "index.php/EntradaBodega/grabarEntradaBodega", {
        el_json: JSON.stringify(miJson)
    }, function (datos) {
        try {
            var obj = $.parseJSON(datos);
            if (obj.length > 0) {
                var idEntradaBodega = obj[0].id_esbodega;

                // --- grabar detalle ---
                $.ajax({
                    url: base_url + "index.php/EntradaBodega/grabarDetalleEntradaBodega",
                    type: "POST",
                    data: {
                        id: idEntradaBodega,
                        detalle: detalleEntradaBodega,
                        id_bodega_stock: IdBodega
                    },
                    dataType: "json",
                    success: function (resp) {
                        showAppAlert('success', "La entrada a Bodega se almacenó con éxito!", {
                            duration: 4000,
                            image: base_url + 'assets/img/success.gif'
                        });
                        $("#detalleEntradaBodega").empty();
                        $("#txtObservaciones").val("");
                    },
                    error: function (xhr, status, error) {
                        showAppAlert('error',"No se pudo grabar el detalle: " + error, {
                            duration: 5000,
                            image: base_url+ 'assets/img/errorGif.gif'
                        });
                    }
                });
            } else {
                showAppAlert('error',"No se recibió ID de entrada.", {
                    duration: 5000,
                    image: base_url+ 'assets/img/errorGif.gif'
                });
            }
        } catch (e) {
            console.error(e, datos);
            showAppAlert('error',"No se pudo procesar la respuesta del servidor", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
        }
    }).fail(function () {
        showAppAlert('error',"Fallo la conexión con el servidor.", {
            duration: 5000,
            image: base_url+ 'assets/img/errorGif.gif'
        });
    });
}

</script>