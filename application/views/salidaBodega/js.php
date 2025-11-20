<script>
var base_url = "<?php echo base_url(); ?>";
$(document).ready(function() {
    $('#Fecha').daterangepicker({
        "singleDatePicker": true,
        "format": 'DD-MM-YYYY',
    });
    ListadoBodegas("");
    ListadoInsumos("");

});

$('#btnValidarSalida').on('click', function() {
    if ($('#cboBodega').val() > 0) {
        $("#cboInsumo").attr("disabled", false);
        $("#cboBodega").attr("disabled", true);
        $("#btnValidarSalida").attr("disabled", true);
    }

});


function obtenerStockActualPorBodega() {
    $.post(base_url + "index.php/SalidaBodega/obtenerStockActualPorBodega", {
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
    var Cantidad_a_Ingresar = parseFloat($('#CantidadNew').val());
    var umedidaSalida = parseFloat($('#UmedidaInsumo').val());
    ProductoComprado = $("#cboInsumo").val();
    if (ProductoComprado == null) {
        swal("Error!", "Debe Seleccionar El Insumo!", "error");
        return;
    }
    if (Cantidad_a_Ingresar == null || Cantidad_a_Ingresar <= 0 || isNaN(Cantidad_a_Ingresar)) {
        swal("Error!", "Debe Indicar La Cantidad De Salida!", "error");
        return;
    }
    $.post(base_url + "index.php/Insumos/ObtenerDatosInsumos", {
            id_insumo: ProductoComprado
        }, function(datos) {
            var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
            $.each(obj, function(index, v) { // recorro el array

                tbl = "<tr id='" + v.rnd + "'>";
                tbl = tbl + "<td style='display:none;'>" + v.id_insumo + "</td>";
                tbl = tbl + "<td>" + v.nombre_insumo + "</td>";
                tbl = tbl + "<td style='display:none;' class='text-right'>" + commaSeparateNumber($('#txtStockActual').val()) + "</td>";
                tbl = tbl + "<td class='text-right'>" + commaSeparateNumber($('#CantidadNew').val()) + "</td>";
                tbl = tbl + "<td><a  onclick='removeRow(" + v.rnd +
                    ")' href='#' class='btn btn-danger btn-icon'><i class='fa fa-trash-o'></i></a></td>";
                tbl = tbl + "</tr>";
                $('#detalleSalidaBodega').append(tbl);
                nuevoProducto();

            }); //fin each

        }).done(function() {
            //sumarTotalesTabla();
        })
        .fail(function() {
            swal("Se produjo un error al realizar la consulta");
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
            confirmButtonColor: "#DD6B55",
            cancelButtonText: "Cancelar",
            confirmButtonText: "Eliminar",
            closeOnConfirm: true
        },
        function() {
            $('#' + rnd).remove();
        });
}

function grabarSalidaBodega() {
    var IdBodega = $("#cboBodega").val();
    var FechaSalida = $('#Fecha').data('daterangepicker').endDate.format('YYYY-MM-DD');

    if (IdBodega == null || IdBodega == 0) {
        swal("Error!", "Debe Seleccionar La Bodega!", "error");
        return;
    }

    // --- construir detalle ---
    var detalleSalidaBodega = [];
    $("#tableSalidaBodega tbody tr").each(function () {
        var idInsumo = $(this).find("td:eq(0)").text();
        var stockAnterior = $(this).find("td:eq(2)").text().replace(/,/g, '');
        var cantidadNueva = $(this).find("td:eq(3)").text().replace(/,/g, '');

        detalleSalidaBodega.push({
            'id_insumo': idInsumo,
            'cantidad': cantidadNueva,
            'stock_anterior': stockAnterior
        });
    });

    // --- datos de cabecera ---
    var miJson = {
        'fecha': FechaSalida,
        'id_bodega': IdBodega,
        'observaciones': 'Salida manual'
    };

    // --- grabar cabecera ---
    $.post(base_url + "index.php/SalidaBodega/grabarSalidaBodega", {
        el_json: JSON.stringify(miJson)
    }, function (datos) {
        try {
            var obj = $.parseJSON(datos);
            if (obj.length > 0) {
                var idSalidaBodega = obj[0].id_esbodega;

                // --- grabar detalle ---
                $.ajax({
                    url: base_url + "index.php/SalidaBodega/grabarDetalleSalidaBodega",
                    type: "POST",
                    data: {
                        id: idSalidaBodega,
                        detalle: detalleSalidaBodega,
                        id_bodega_stock: IdBodega
                    },
                    dataType: "json",
                    success: function (resp) {
                        swal("Listo!", "La salida se almacenó con éxito!", "success");
                        $("#detalleSalidaBodega").empty();
                    },
                    error: function (xhr, status, error) {
                        swal("Error!", "No se pudo grabar el detalle: " + error, "error");
                    }
                });
            } else {
                swal("Error!", "No se recibió ID de salida.", "error");
            }
        } catch (e) {
            console.error(e, datos);
            swal("Error!", "No se pudo procesar la respuesta del servidor.", "error");
        }
    }).fail(function () {
        swal("Error!", "Fallo la conexión con el servidor.", "error");
    });
}

</script>