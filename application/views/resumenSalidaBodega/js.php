<script>
    var base_url = "<?php echo base_url(); ?>";
    tablaDetalleSalidasBodega = $('#tableResumenSalidasBodega').DataTable();
    tableModalVerDetalleSalidaBodega = $('#tablaModalVerDetalleSalidaBodega').DataTable();
    $(document).ready(function () {
        $('#ckTodosLosUsuarios').prop('checked', 'true');
        $('#ckTodasLasBodegas').prop('checked', 'true');
        llenarCboUsuarios("");
        ListadoDeBodega("");
        $("#cboUsuarios").attr("disabled", true);
        $("#cboBodegas").attr("disabled", true);


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
        var id_usuario = $("#cboUsuarios").val();
        console.log(id_usuario);
        if ($('#ckTodosLosUsuarios').prop('checked') == false) {
            if (id_usuario == null || id_usuario == 0) {
                swal("Debe seleccionar el usuario");
                return;
            }
        } else {
            id_usuario = 0;
        }
        var id_bodega = $("#cboBodegas").val();
        console.log(id_bodega);
        if ($('#ckTodasLasBodegas').prop('checked') == false) {
            if (id_bodega == null || id_bodega == 0) {
                swal("Debe seleccionar la Bodega");
                return;
            }
        } else {
            id_bodega = 0;
        }
        resumenSalidaBodega(de, a, id_usuario, id_bodega);

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

    $('#ckTodosLosUsuarios').click(function () {
        if ($('#ckTodosLosUsuarios').prop('checked') == false) {
            $("#cboUsuarios").attr("disabled", false);
            llenarCboUsuarios();

        } else {
            $("#cboUsuarios").attr("disabled", true);
        }
    });

    $('#ckTodasLasBodegas').click(function () {
        if ($('#ckTodasLasBodegas').prop('checked') == false) {
            $("#cboBodegas").attr("disabled", false);
            ListadoDeBodega();

        } else {
            $("#cboBodegas").attr("disabled", true);
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
    
    var $wrapper = $('#tableResumenSalidasBodega').closest('.dataTables_wrapper');
    if ($wrapper.length === 0) {
        $wrapper = $('#tableResumenSalidasBodega').parent();
    }
    
    $wrapper.css('position', 'relative');
    $wrapper.append(loaderHtml);
    }

    function hideTableLoader() {
        $('#dynamic-table-loader').fadeOut(500, function() {
            $(this).remove();
        });
    }

    function resumenSalidaBodega(desde, hasta, id_usuario, id_bodega) {
        let tituloExport = "Ventas Por Producto";
        if ($.fn.dataTable.isDataTable('#tableResumenSalidasBodega')) {
            tablaDetalleSalidasBodega = $('#tableResumenSalidasBodega').DataTable();
            tablaDetalleSalidasBodega.destroy();
        }

        console.log({
            'desde': desde,
            'hasta': hasta,
            'id_usuario': id_usuario
        });
        tablaDetalleSalidasBodega = $("#tableResumenSalidasBodega").DataTable({
            "ajax": {
                "url": base_url + "index.php/ResumenSalidaBodega/resumenSalidaBodega",
                "type": "POST",
                "data": {
                    'desde': desde,
                    'hasta': hasta,
                    'id_usuario': id_usuario,
                    'id_bodega': id_bodega
                },
                beforeSend: function () {
                    showTableLoader("Cargando Resumen de Salidas de Bodegas...");
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
            "columns": [{
                mData: "id_esbodega",
                className: "text-center"
            },
            {
                mData: "nombre_bodega",
                className: "text-center"
            },
            {
                mData: "fecha",
                className: "text-center"
            },
            {
                mData: "nombre_usuario",
                className: "text-center"
            },
            {
                mData: "observaciones",
                className: "text-center"
            },
            {
                mData: "btn_ver_detalle",
                className: "text-center"
            },
            ],
            'language': {
                url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json",
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
                title: tituloExport,
                className: "btn btn-danger",
                exportOptions: {
                    modifier: {
                        selected: null
                    },
                    columns: [0, 1, 2, 3, 4]
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
                    columns: [0, 1, 2, 3, 4]
                }
            }]
        });
    }

    function resumenVerDetalleSalidaBodega(id_esbodega) {
        let tituloExport = "Detalle Salida Bodega";
        if ($.fn.dataTable.isDataTable('#tablaModalVerDetalleSalidaBodega')) {
            tableModalVerDetalleSalidaBodega = $('#tablaModalVerDetalleSalidaBodega').DataTable();
            tableModalVerDetalleSalidaBodega.destroy();
        }
        tableModalVerDetalleSalidaBodega = $("#tablaModalVerDetalleSalidaBodega").DataTable({
            "ajax": {
                "url": base_url + "index.php/ResumenSalidaBodega/detalleResumenSalidaBodega",
                "type": "POST",
                "data": {
                    'id_esbodega': id_esbodega,
                }
            },
            "bPaginate": true,
            "bProcessing": true,
            "pageLength": 20,
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
            "columns": [{
                mData: "nombre_insumo",
                className: "text-center"
            },
            {
                mData: "nombre_umedida",
                className: "text-center"
            },
            {
                mData: "cantidad",
                className: "text-center"
            },
            {
                mData: "stock_anterior",
                className: "text-center",
            },
            {
                mData: "nuevo_stock",
                className: "text-center",
            }
            ],
            'language': {
                url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json",
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
                title: tituloExport,
                className: "btn btn-danger",
                exportOptions: {
                    modifier: {
                        selected: null
                    },
                    columns: [0, 1, 2, 3, 4]
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
                    columns: [0, 1, 2, 3, 4]
                }
            }]
        });
    }

    function VerModalDetalleSalidaBodega(id_esbodega) {
        resumenVerDetalleSalidaBodega(id_esbodega);
        $('#modalVerDetalle').modal({
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

    function ListadoDeBodega(objSelected) {
        $.post(base_url + "index.php/Productos/ListadoDeBodega", {
            id: ""
        }, function (datos) {
            var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
            tbl = '<option selected disabled value="0">Seleccione La Bodega</option>';
            $.each(obj, function (index, v) { // recorro el array
                if (v.nombre_bodega == objSelected) {
                    tbl = tbl + '<option selected value="' + v.id_bodega + '">' + v.nombre_bodega +
                        '</option>';
                } else {
                    tbl = tbl + '<option value="' + v.id_bodega + '">' + v.nombre_bodega + '</option>';
                }
            }); //fin each
            $("#cboBodegas").html(tbl);
            $("#cboBodegas").select2();
        });
    }
</script>