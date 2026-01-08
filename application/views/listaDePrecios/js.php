<script>
    var base_url = "<?php echo base_url(); ?>";
    tableResumenListaPrecios = $('#tableResumenListaPrecios').DataTable();
    $(document).ready(function() {
        ListadoListasDePrecios();
        ListadoDeFamilias();
        $('#ckTodasLasFamilias').prop('checked', true);
        $("#cboFamilias").attr("disabled", true);
    });

    $('#btnFiltrar').on('click', function() {
        var id_lprecio = $("#cboListaDePrecios").val();
        console.log(id_lprecio);
        if ($('#ckTodasLasListasDePrecios').prop('checked') == false) {
            if (id_lprecio == null || id_lprecio == 0) {
                alert("Debe seleccionar la lista de precios");
                return;
            }
        } else {
            id_lprecio = 0;
        }
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
        InformeListaDePrecios(id_lprecio, id_familia);

    });

    $('#ckTodasLasListasDePrecios').click(function() {
        if ($('#ckTodasLasListasDePrecios').prop('checked') == false) {
            $("#cboListaDePrecios").attr("disabled", false);
        } else {
            $("#cboListaDePrecios").attr("disabled", true);
        }
    });

    $('#ckTodasLasFamilias').click(function() {
        if ($('#ckTodasLasFamilias').prop('checked') == false) {
            $("#cboFamilias").attr("disabled", false);
        } else {
            $("#cboFamilias").attr("disabled", true);
        }
    });

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
    
    var $wrapper = $('#tableResumenListaPrecios').closest('.dataTables_wrapper');
    if ($wrapper.length === 0) {
        $wrapper = $('#tableResumenListaPrecios').parent();
    }
    
    $wrapper.css('position', 'relative');
    $wrapper.append(loaderHtml);
    }

    function hideTableLoader() {
        $('#dynamic-table-loader').fadeOut(200, function() {
            $(this).remove();
        });
    }

    function InformeListaDePrecios(id_lprecio, id_familia) {
        let tituloExport = "Lista De Precios";
        if ($.fn.dataTable.isDataTable('#tableResumenListaPrecios')) {
            tableResumenListaPrecios = $('#tableResumenListaPrecios').DataTable();
            tableResumenListaPrecios.destroy();
        }
        tableResumenListaPrecios = $("#tableResumenListaPrecios").DataTable({
            "ajax": {
                "url": base_url + "index.php/ListaDePrecios/InformeListaDePrecios",
                "type": "POST",
                "data": {
                    'id_lprecio': id_lprecio,
                    'id_familia': id_familia,
                },
                beforeSend: function () {
                    showTableLoader("Cargando Listas de Precios...");
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
            "pageLength": 10,
            "select": true,
            "dom": "Bfrtip",
            "ordering": true,
            "filter": true,
            "info": false,
            "footer": false,
            "responsive": {
                details: {
                    type: 'column',
                    target: -1
                }
            },
            "columns": [{
                    mData: "id_producto",
                    className: "text-center",
                    visible: false
                },
                {
                    mData: "cbarra",
                    className: "text-center"
                },
                {
                    mData: "nombre_producto",
                    className: "text-left"
                },
                {
                    mData: "nombre_familia",
                    className: "text-left"
                },
                {
                    mData: "nombre_umedida",
                    className: "text-left"
                },
                {
                    mData: "total",
                    className: "text-right",
                    render: $.fn.dataTable.render.number(',', '.', 0, '$')
                },
                // {
                //     mData: "precio_ucompra",
                //     className: "text-right",
                //     render: $.fn.dataTable.render.number(',', '.', 0, '$')
                // },
                {
                    mData: "por_ganancia",
                    className: "text-right",
                    render: function (data) {
                        return data + ' %';
                    }
                },
                {
                    mData: "costo",
                    className: "text-right",
                    render: $.fn.dataTable.render.number(',', '.', 0, '$')
                },
                {
                    mData: "por_costo",
                    className: "text-right",
                    render: function (data) {
                        return data + ' %';
                    }
                },
                {
                    mData: "monto_comision",
                    className: "text-right",
                    render: $.fn.dataTable.render.number(',', '.', 0, '$')
                },
            ],
            'language': {
                url: "https://cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json",
                // traduce la linea de abajo que quedaba en ingles
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
        tableResumenListaPrecios
            .on('select', function(e, dt, type, indexes) {
                var rowData = tableResumenListaPrecios.rows(indexes).data().toArray();

                /*$("#txtPonderado").val(rowData[0].pponderado);
                 $("#txtMargenEsperadoPonderado").val(rowData[0].por_ganancia_producto);

                $("#txtPrecioUCompra").val(rowData[0].precio_ucompra);
                $("#txtMargenEsperado").val(rowData[0].por_ganancia_producto);*/

                $("#idProducto").val(rowData[0].id_producto);
                llenarPrecios(rowData[0].id_producto);
                //calcularMargen();
                $('#modalCambioPrecio').modal('show');



            })
    }


    function ListadoListasDePrecios() {
        $.post(base_url + "index.php/ListaDePrecios/ListadoListasDePrecios", {
            id: ""
        }, function(datos) {
            var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
            tbl = "<option value='0' disabled>Seleccione La Lista De Precios</option>";
            $.each(obj, function(index, v) { // recorro el array
                tbl += '<option selected value="' + v.id_lprecio + '">' + v.nombre_lprecio + '</option>';
            }); //fin each
            $("#cboListaDePrecios").html(tbl);
            $("#cboListaDePrecios").select2();
        });
    }

    function ListadoDeFamilias() {
        $.post(base_url + "index.php/ListaDePrecios/ListadoDeFamilias", {
            id: ""
        }, function(datos) {
            var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
            tbl = "<option value='0' disabled>Seleccione La Familia</option>";
            $.each(obj, function(index, v) { // recorro el array
                tbl += '<option selected value="' + v.id_familia + '">' + v.nombre_familia + '</option>';
            }); //fin each
            $("#cboFamilias").html(tbl);
            $("#cboFamilias").select2();
        });
    }

    function crearNuevaLprecio() {
        $('#idlPrecio').val("");
        $('#nombreLprecio').val("");
        $('#ModalNuevaLprecios').modal({
            backdrop: 'static',
            keyboard: false
        });
    }

    function GrabarNuevaLprecio() {
        if ($('#nombreLprecio').val() == 0) {
            showAppAlert('error',"Debe ingresar el Nombre de la nueva Lista de Precios.", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
        } else {
            //grabar datos
            IdListaPrecios = $('#idlPrecio').val();
            NombreListaPrecios = $('#nombreLprecio').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
            var miJson = {
                'id_lprecio': IdListaPrecios,
                'nombre_lprecio': NombreListaPrecios
            };
            $.post(base_url + "index.php/ListaDePrecios/grabarNuevaListaDePrecios", {
                    'prod': miJson
                }).done(function() {
                    $('#ModalNuevaLprecios').modal('hide');
                    
                    // CAMBIO AQUÍ: Verificar si la tabla está inicializada antes de recargar
                    if ($.fn.DataTable.isDataTable('#tableResumenListaPrecios') && 
                        tableResumenListaPrecios.ajax.url()) {
                        tableResumenListaPrecios.ajax.reload();
                    } else {
                        // Si no está inicializada, actualizar solo el combo
                        ListadoListasDePrecios();
                    }
                    
                    showAppAlert('success', "Se registro Exitosamente La Nueva Lista De Precios!", {
                        duration: 4000,
                        image: base_url + 'assets/img/success.gif'
                    });
                })
                .fail(function() {
                    showAppAlert('error',"- Se produjo un error al realizar el proceso, favor contacte a su administrador", {
                        duration: 5000,
                        image: base_url+ 'assets/img/errorGif.gif'
                    });
                })
                .always(function() {});
        }
    }

    // function llenarPrecios(idProducto) {
    //     $.post(base_url + "index.php/ListaDePrecios/obtenerListayPreciosDeVenta", {
    //         id_producto: idProducto
    //     }, function(datos) {
    //         var obj = $.parseJSON(datos); // lo convierto a un objeto a json
    //         tbl = '';
    //         $.each(obj, function(index, v) { // recorro el array
    //             tbl = tbl + "<tr id='" + v.id_detalle_lprecio + "'>";
    //             tbl = tbl + "<td  style= 'display:none' >" + v.id_detalle_lprecio + "</td>";
    //             tbl = tbl + "<td  style='width:30%'' >" + v.nombre_lprecio + "</td>";
    //             tbl = tbl + '<td style="width:30%" class="text-right" ><input class="text-right form-control form-control-line" type="number" min="0" name="" value="' + v.total + '"></td>';
    //             tbl = tbl + "</tr>";


    //         }); //fin each
    //         $("#detallePrecioVenta").html(tbl);

    //     });
    // }

    function llenarPrecios(idProducto) {
    $.post(base_url + "index.php/ListaDePrecios/obtenerListayPreciosDeVenta", {
        id_producto: idProducto
    }, function(datos) {

        var obj = $.parseJSON(datos);
        var tbl = '';

        $.each(obj, function(index, v) {

            tbl += "<tr>";
            tbl += "<td style='display:none'>" + v.id_detalle_lprecio + "</td>";
            tbl += "<td>" + v.nombre_lprecio + "</td>";

            // PRECIO
            tbl += `
                <td class="text-right">
                    <input type="number" class="form-control text-right precio"
                        value="${v.total}" min="0">
                </td>
            `;

            // COMISIÓN
            tbl += `
                <td class="text-right">
                    <input type="number" class="form-control text-right comision"
                        value="${v.monto_comision || 0}" min="0">
                </td>
            `;

            tbl += "</tr>";
        });

        $("#detallePrecioVenta").html(tbl);
    });
}

    // function grabarPrecios() {
    //     var idProducto = $("#idProducto").val();
    //     var detallePrecios = [];
    //     $("#tablePrecios tbody tr").each(function(index) {
    //         $(this).children("td").each(function(index2) {
    //             valorLista = 0;
    //             switch (index2) {
    //                 case 0: //id producto
    //                     idDetalleLprecio = $(this).html();
    //                     break;
    //                 case 2: // precio
    //                     var row = $(this);
    //                     if (row.find('input[type="number"]').val()) {
    //                         valorLista = row.find('input[type="number"]').val();
    //                     }

    //                     detallePrecios.push({
    //                         'id_producto': idProducto,
    //                         'id_detalle_lprecio': idDetalleLprecio,
    //                         'total': valorLista
    //                     });
    //                     break;
    //             }
    //         });
    //     }); //fin recorrer tabla

    //     console.log(detallePrecios);
    //     $.post(base_url + "index.php/ListaDePrecios/grabarPrecios", {
    //             detalle: detallePrecios
    //         }, function() {}).done(function(datos) {
    //             console.log(datos);
    //             showAppAlert('success', "Se registro Exitosamente El Cambio De Precio!", {
    //                 duration: 4000,
    //                 image: base_url + 'assets/img/success.gif'
    //             });
    //             $('#modalCambioPrecio').modal('hide');
    //             tableResumenListaPrecios.ajax.reload();
    //         })
    //         .fail(function() {
    //             alert("Se produjo un error al realizar el proceso, favor contacte a su administrador");
    //         })
    //         .always(function() {});


    // } // fin grabar precios

    function grabarPrecios() {

    var idProducto = $("#idProducto").val();
    var detallePrecios = [];

    $("#tablePrecios tbody tr").each(function () {

        var idDetalleLprecio = $(this).find("td:eq(0)").text();
        var precio = $(this).find("input.precio").val() || 0;
        var comision = $(this).find("input.comision").val() || 0;

        detallePrecios.push({
            id_producto: idProducto,
            id_detalle_lprecio: idDetalleLprecio,
            total: precio,
            monto_comision: comision
        });
    });

    $.post(base_url + "index.php/ListaDePrecios/grabarPrecios", {
        detalle: detallePrecios
    }).done(function () {

        showAppAlert('success', "Se registraron los cambios correctamente", {
            duration: 4000,
            image: base_url + 'assets/img/success.gif'
        });

        $('#modalCambioPrecio').modal('hide');
        tableResumenListaPrecios.ajax.reload();

    }).fail(function () {
        alert("Error al grabar precios");
    });
}
</script>