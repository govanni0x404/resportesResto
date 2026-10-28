<script>
    var base_url = "<?php echo base_url(); ?>";
    tablaDetalleOperaciones = $('#tableDetalleOperaciones').DataTable();
    tableModalVerDetalleOperaciones = $('#tablaModalVerDetalleDeOperaciones').DataTable();
    $(document).ready(function () {
        $('#ckTodasLasFpago').prop('checked', 'true');
        $('#ckTodosLosUsuarios').prop('checked', 'true');
        llenarCboUsuarios("");
        llenarCboFormasDePago("");
        $("#cboFpago").attr("disabled", true);
        $("#cboUsuarios").attr("disabled", true);

    });
/*
    $('#date-range-picker').daterangepicker({
        singleDatePicker: false,
        format: 'DD/MM/YYYY',
        "locale": {
            "format": "DD/MM/YYYY",
            "separator": " - ",
            "applyLabel": "Elegir",
            "cancelLabel": "Cancelar",
            "fromLabel": "Desde",
            "toLabel": "Hasta",
            "customRangeLabel": "Custom",
            "daysOfWeek": [
                "Dom",
                "Lun",
                "Mar",
                "Mie",
                "Jue",
                "Vie",
                "Sab"
            ],
            "monthNames": [
                "Enero",
                "Febrero",
                "Marzo",
                "Abril",
                "Mayo",
                "Junio",
                "Julio",
                "Agosto",
                "Septiembre",
                "Octubre",
                "Noviembre",
                "Diciembre"
            ],
            "firstDay": 1
        }
    });
*/
    $('#date-range-picker').daterangepicker({
        ranges: dateRange,
        format: 'DD-MM-YYYY',
        locale: {
            format: 'DD-MM-YYYY', // Formato de fecha
            separator: ' - ',
            applyLabel: 'Aplicar',
            cancelLabel: 'Cancelar',
            fromLabel: 'Desde',
            toLabel: 'Hasta',
            customRangeLabel: 'Rango personalizado',
            weekLabel: 'S',
            daysOfWeek: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá'],
            monthNames: [
                'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
               'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
            ],
            firstDay: 1 // Comienza el lunes
        }
    });
    function updateInput(start, end) {
        $('#date-range-picker').val(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
    }

    $('#date-range-picker').on('apply.daterangepicker', function (ev, picker) {
        updateInput(picker.startDate, picker.endDate);
    });

    $(document).on('click', function (event) {
        if (!$(event.target).closest('.daterangepicker').length && !$(event.target).is('#date-range-picker')) {
            var picker = $('#date-range-picker').data('daterangepicker');
            if (picker) {
                updateInput(picker.startDate, picker.endDate);
            }
        }
    });


    $('#btnFiltrar').on('click', function () {
        var de = $('#date-range-picker').data('daterangepicker').startDate.format('YYYY-MM-DD');
        var a = $('#date-range-picker').data('daterangepicker').endDate.format('YYYY-MM-DD');
        var idFpago = $("#cboFpago").val();
        console.log(idFpago);
        if ($('#ckTodasLasFpago').prop('checked') == false) {
            if (idFpago == null || idFpago == 0) {
                alert("Debe seleccionar la forma de pago");
                return;
            }
        } else {
            idFpago = 0;
        }
        var idUsuario = $("#cboUsuarios").val();
        console.log(idUsuario);
        if ($('#ckTodosLosUsuarios').prop('checked') == false) {
            if (idUsuario == null || idUsuario == 0) {
                alert("Debe seleccionar el usuario");
                return;
            }
        } else {
            idUsuario = 0;
        }
        resumenDeOperaciones(de, a, idFpago, idUsuario);

    });

    function procesarRespuesta(ajaxResponse) {
        // observa que aquÃ­ asumimos que el resultado es un objeto
        // serializado en JSON, razÃ³n por la cual tomamos este dato
        // y lo procesamos para recuperar un objeto que podamos
        // manejar fÃ¡cilmente
        if (typeof ajaxResponse == "string")
            ajaxResponse = $.parseJSON(ajaxResponse);
        return ajaxResponse;
    }

    $('#ckTodasLasFpago').click(function () {
        if ($('#ckTodasLasFpago').prop('checked') == false) {
            $("#cboFpago").attr("disabled", false);
            llenarCboFormasDePago();

        } else {
            $("#cboFpago").attr("disabled", true);
        }
    });

    $('#ckTodosLosUsuarios').click(function () {
        if ($('#ckTodosLosUsuarios').prop('checked') == false) {
            $("#cboUsuarios").attr("disabled", false);
            llenarCboUsuarios();

        } else {
            $("#cboUsuarios").attr("disabled", true);
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
        '<div style="width: 40px; height: 40px; border: 4px solid rgba(180, 67, 46,0.18); ' +
        'border-top-color: #B4432E; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>' +
        '<div style="color: #3A2A22; font-size: 14px;">' + (message || 'Cargando...') + '</div>' +
        '</div>';
    
    var $wrapper = $('#tableDetalleOperaciones').closest('.dataTables_wrapper');
    if ($wrapper.length === 0) {
        $wrapper = $('#tableDetalleOperaciones').parent();
    }
    
    $wrapper.css('position', 'relative');
    $wrapper.append(loaderHtml);
    }

    function hideTableLoader() {
        $('#dynamic-table-loader').fadeOut(500, function() {
            $(this).remove();
        });
    }

    function resumenDeOperaciones(desde, hasta, idFpago, idUsuario) {
        let tituloExport = "Detalle De Ventas";
        if ($.fn.dataTable.isDataTable('#tableDetalleOperaciones')) {
            tablaDetalleOperaciones = $('#tableDetalleOperaciones').DataTable();
            tablaDetalleOperaciones.destroy();
        }

        console.log({
            'desde': desde,
            'hasta': hasta,
            'id_Fpago': idFpago,
            'id_usuario': idUsuario
        });
        tablaDetalleOperaciones = $("#tableDetalleOperaciones").DataTable({
            "ajax": {
                "url": base_url + "index.php/DetalleOperaciones/resumenDeOperaciones",
                "type": "POST",
                "data": {
                    'desde': desde,
                    'hasta': hasta,
                    'id_Fpago': idFpago,
                    'id_usuario': idUsuario
                },
                beforeSend: function () {
                    showTableLoader("Cargando Detalle de Operaciones...");
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
            "footer": true,
            "responsive": {
                details: {
                    type: 'column',
                    target: -1
                }
            },
            "columns": [{
                mData: "id_venta",
                className: "text-center"
            },
            {
                mData: "nombre_usuario",
                className: "text-center"
            },
            {
                mData: "comensales",
                className: "text-center",
                "width": "5%",
            },
            {
                mData: "fecha_inicio",
                className: "text-center"
            },
            {
                mData: "observacion",
                className: "text-center"
            },
            {
                mData: "nombre_fpago",
                className: "text-center"
            },
            {
                mData: "total",
                className: "text-center",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },
            {
                mData: "btn_ver_detalle",
                className: "text-center"
            },
            ],
            "drawCallback": function () {
                var totalVentas = this.api();
                var total = totalVentas.column(6, { page: 'current' }).data().sum();
                function formatNumber(num) {
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }
                var totalFormatted = formatNumber(total.toFixed(0));
                $(totalVentas.column(6).footer()).html(
                    '$' + totalFormatted
                );

            },
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
            'buttons': [{
                text: "PDF",
                extend: 'pdfHtml5',
                orientation: 'landscape',
                pageSize: 'LEGAL',
                title: "Detalle De Ventas",
                className: "btn btn-danger",
                exportOptions: {
                    modifier: {
                        selected: null
                    },
                    columns: [0, 1, 2, 3, 4, 5, 6]
                }
            }, {
                text: "Excel",
                extend: 'excelHtml5',
                title: "Detalle De Ventas",
                className: "btn btn-success",
                exportOptions: {
                    modifier: {
                        selected: null
                    },
                    columns: [0, 1, 2, 3, 4, 5, 6]
                }
            }]
        });
    }

    function resumenVerDetalleDeOperaciones(id_venta) {
        let tituloExport = "Detalle Venta " + id_venta;
        if ($.fn.dataTable.isDataTable('#tablaModalVerDetalleDeOperaciones')) {
            tableModalVerDetalleOperaciones = $('#tablaModalVerDetalleDeOperaciones').DataTable();
            tableModalVerDetalleOperaciones.destroy();
        }
        tableModalVerDetalleOperaciones = $("#tablaModalVerDetalleDeOperaciones").DataTable({
            "ajax": {
                "url": base_url + "index.php/DetalleOperaciones/ResumenDetalleOperaciones",
                "type": "POST",
                "data": {
                    'id_venta': id_venta,
                }
            },
            "bPaginate": true,
            "bProcessing": true,
            "pageLength": 10,
            "select": true,
            "dom": "Bfrtip",
            "ordering": true,
            "filter": true,
            "info": false,
            "footer": true,
            "responsive": {
                details: {
                    type: 'column',
                    target: -1
                }
            },
            "columns": [{
                mData: "nombre_producto",
                className: "text-center"
            },
            {
                mData: "cantidad",
                className: "text-center"
            },
            {
                mData: "bruto_un",
                className: "text-center",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },
            {
                mData: "total_bruto",
                className: "text-center",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },
            ],
            "drawCallback": function () {
                totalCantidad = this.api();
                $(totalCantidad.column(1).footer()).html(
                    ' ' + totalCantidad.column(1, {
                        page: 'current'
                    }).data().sum()
                )

                var montoTotal = this.api();
                var total = montoTotal.column(3, { page: 'current' }).data().sum();
                function formatNumber(num) {
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }
                var totalFormatted = formatNumber(total.toFixed(0));
                $(montoTotal.column(3).footer()).html(
                    '$' + totalFormatted
                );
            },
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
            'buttons': [{
                text: "PDF",
                extend: 'pdfHtml5',
                title: tituloExport,
                className: "btn btn-danger",
                exportOptions: {
                    modifier: {
                        selected: null
                    },
                    columns: [0, 1, 2, 3]
                }
            }, {
                text: "Excel",
                extend: 'excelHtml5',
                title: tituloExport,
                className: "btn btn-success",
                exportOptions: {
                    modifier: {
                        selected: null
                    },
                    columns: [0, 1, 2, 3]
                }
            }]
        });
    }

    function VerModalDetalleDeOperaciones(id_venta) {
        resumenVerDetalleDeOperaciones(id_venta);
        DetalleFormaDePagoVenta(id_venta);
        DetalleCambioFormaDePagoVenta(id_venta);
        cargarValidacionDetalleDeOperaciones();
        $('#modalVerDetalleDeOperaciones').modal({
            backdrop: 'static',
            keyboard: false
        });
    }

    function llenarCboUsuarios() {
        $.post(base_url + "index.php/DetalleOperaciones/ListarUsuarios", {
            id: ""
        }, function (datos) {
            var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
            tbl = "<option value='0' disabled>Seleccione El Usuario</option>";
            $.each(obj, function (index, v) { // recorro el array
                tbl += '<option selected value="' + v.id_usuario + '">' + v.nombre_usuario + '</option>';
            }); //fin each
            $("#cboUsuarios").html(tbl);
            $("#cboUsuarios").select2();
        });
    }

    function llenarCboFormasDePago() {
        $.post(base_url + "index.php/DetalleOperaciones/ListarFormasDePago", {
            id: ""
        }, function (datos) {
            var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
            tbl = "<option value='0' disabled>Seleccione La Forma De Pago</option>";
            $.each(obj, function (index, v) { // recorro el array
                tbl += '<option selected value="' + v.id_fpago + '">' + v.nombre_fpago + '</option>';
            }); //fin each
            $("#cboFpago").html(tbl);
            $("#cboFpago").select2();
        });
    }

    function cargarValidacionDetalleDeOperaciones() {
        $.post(base_url + "index.php/Configuraciones/obtenerConfiguracionesRestoWeb", {}, function (datos) {
            var obj = $.parseJSON(datos);
            $.each(obj, function (index, v) {
                if (v.desglozar_monto_neto_iva_total_en_reportes == 't') {
                    tableModalVerDetalleOperaciones.column(3).visible(true);
                    tableModalVerDetalleOperaciones.column(4).visible(true);
                } else {
                    tableModalVerDetalleOperaciones.column(3).visible(false);
                    tableModalVerDetalleOperaciones.column(4).visible(false);

                }
            });
        }).done(function () { })
            .fail(function () {
                alert("Se produjo un error al realizar la consulta");
            })
            .always(function () { });
    }

    function DetalleFormaDePagoVenta(id_venta) {
        $.post(base_url + "index.php/DetalleOperaciones/DetalleFormaDePagoVenta", {
            id_venta: id_venta,
        }, function (datos) {
            var obj = $.parseJSON(datos);
            $.each(obj, function (index, v) {
                $('#idTipoDocumento').val(v.nombre_tdocumento);
                $('#folioDte').val(v.folio);
                $('#idFormaDePago').val(v.nombre_fpago);
            });
        }).done(function () { })
            .fail(function () {
                alert("Se produjo un error al realizar la consulta");
            })
            .always(function () { });
    }

    function AbrirModalCambioFpago() {
        llenarCboNuevaFormasDePagoVenta();
        $('#ModalCambioFormaDePago').modal({
            backdrop: 'static',
            keyboard: false
        });
    }

    function DetalleCambioFormaDePagoVenta(id_venta) {
        $.post(base_url + "index.php/DetalleOperaciones/DetalleFormaDePagoVenta", {
            id_venta: id_venta,
        }, function (datos) {
            var obj = $.parseJSON(datos);
            $.each(obj, function (index, v) {
                $('#Nventa').val(v.id_venta);
                $('#FormaDePagoActual').val(v.nombre_fpago);
            });
        }).done(function () { })
            .fail(function () {
                alert("Se produjo un error al realizar la consulta");
            })
            .always(function () { });
    }

    function llenarCboNuevaFormasDePagoVenta() {
        $.post(base_url + "index.php/DetalleOperaciones/ListarFormasDePago", {
            id: ""
        }, function (datos) {
            var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
            tbl = "<option value='0' disabled>Seleccione La Nueva Forma De Pago</option>";
            $.each(obj, function (index, v) { // recorro el array
                tbl += '<option selected value="' + v.id_fpago + '">' + v.nombre_fpago + '</option>';
            }); //fin each
            $("#cboNuevaFormaDePago").html(tbl);
            $("#cboNuevaFormaDePago").select2();
        });
    }

    function actualizarFormaDePagoVenta() {
        swal({
            title: "Confirmacion!",
            text: "Esta segur@ De Actualizar La Forma De Pago De La venta",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#EB360F",
            confirmButtonText: "Aceptar!",
            cancelButtonText: "Cancelar!",
            closeOnConfirm: true,
            closeOnCancel: false
        },
            function (isConfirm) {
                if (isConfirm) {
                    var msg = "";
                    if ($('#cboNuevaFormaDePago').val() == 0) {
                        msg = "La Nueva Forma De Pago De La venta\n";
                    }
                    if ($('#cboNuevaFormaDePago').val() == $('#FormaDePagoActual').val()) {
                        msg = "Otra Forma De Pago (No Se Puede Actualizar A la Misma F. De Pago Actual)\n";
                    }
                    if (msg.length) {
                        swal("Debe Seleccionar:\n" + msg);
                    } else {
                        //grabar datos
                        IdVenta = $('#Nventa').val();
                        IdNuevaFormaDePago = $('#cboNuevaFormaDePago').val();
                        var miJson = {
                            'id_venta': IdVenta,
                            'id_fpago': IdNuevaFormaDePago,
                        };
                    };
                    $.post(base_url + "index.php/DetalleOperaciones/actualizarFormaDePagoVenta", {
                        'prod': miJson
                    }, function () { }).done(function (datos) {
                        let positionError = datos.search("ERROR:");
                        if (parseFloat(positionError) > 0) {
                            swal("Error", "Se produjo un error, favor reintente \n(" + e + ")\n" + datos.substr(positionError, 150), "error");
                        } else {
                            swal("Forma De Pago Actualizada!", "Se Actualizo Correctamente!", "success");
                            $('#ModalCambioFormaDePago').modal('hide');
                            $('#modalVerDetalleDeOperaciones').modal('hide');
                            tablaDetalleOperaciones.ajax.reload();

                        }
                    }).fail(function () {
                        swal("Se produjo un error al realizar La Actualizacion, favor vuelva a intentarlo");
                    });
                    swal.close();
                } else {
                    swal.close();
                }
            });
    }
</script>