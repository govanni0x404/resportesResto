<script>
    var base_url = "<?php echo base_url(); ?>";
    tablaDetalleAnulaciones = $('#tableDetalleAnulaciones').DataTable();
    $(document).ready(function () {
        $("#cboUsuarios").attr("disabled", true);
        $('#ckTodosLosUsuarios').prop('checked', 'true');
        llenarCboUsuarios("");
        $("#ckTodos").attr("checked", true);
        $("#opAnulaciones").attr("checked", false);
        $("#opEliminaciones").attr("checked", false);
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
        var desde = $('#date-range-picker').data('daterangepicker').startDate.format('YYYY-MM-DD');
        var hasta = $('#date-range-picker').data('daterangepicker').endDate.format('YYYY-MM-DD');
        var id_usuario = $("#cboUsuarios").val();
        console.log(id_usuario);
        if ($('#ckTodosLosUsuarios').prop('checked') == false) {
            if (id_usuario == null || id_usuario == 0) {
                alert("Debe seleccionar el usuario");
                return;
            }
        } else {
            id_usuario = 0;
        }
        if ($('#ckTodos').prop('checked')) {
            todasLasAnulacionesYeliminaciones = 1;
            soloEliminaciones = 0;
            soloAnulaciones = 0;
        } else {
            todasLasAnulacionesYeliminaciones = 0;
            radioValue = $("input[name='radio']:checked").val();
            //console.log("radioValue "+radioValue);
            if (radioValue == 1) {
                soloEliminaciones = 0;
                soloAnulaciones = 1;
            } else {
                soloEliminaciones = 1;
                soloAnulaciones = 0;
            }
        }
        resumenDeAnulaciones(desde, hasta, id_usuario, todasLasAnulacionesYeliminaciones, soloAnulaciones, soloEliminaciones)

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

    $('#ckTodos').click(function () {
        llenarCboUsuarios();
        if ($('#ckTodos').prop('checked') == false) {
            $("#opAnulaciones").attr("disabled", false);
            $("#opAnulaciones").attr("checked", false);

            $("#opEliminaciones").attr("disabled", false);
            $("#opEliminaciones").attr("checked", false);

        } else {
            $("#opAnulaciones").attr("disabled", true);
            $("#opEliminaciones").attr("disabled", true);
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
    
    var $wrapper = $('#tableDetalleAnulaciones').closest('.dataTables_wrapper');
    if ($wrapper.length === 0) {
        $wrapper = $('#tableDetalleAnulaciones').parent();
    }
    
    $wrapper.css('position', 'relative');
    $wrapper.append(loaderHtml);
    }

    function hideTableLoader() {
        $('#dynamic-table-loader').fadeOut(500, function() {
            $(this).remove();
        });
    }

    function resumenDeAnulaciones(desde, hasta, id_usuario, todasLasAnulacionesYeliminaciones, soloAnulaciones, soloEliminaciones) {
        let tituloExport = "Resumen Anulaciones";
        if ($.fn.dataTable.isDataTable('#tableDetalleAnulaciones')) {
            tablaDetalleAnulaciones = $('#tableDetalleAnulaciones').DataTable();
            tablaDetalleAnulaciones.destroy();
        }
        tablaDetalleAnulaciones = $("#tableDetalleAnulaciones").DataTable({
            "ajax": {
                "url": base_url + "index.php/ResumenAnulaciones/resumenDeAnulaciones",
                "type": "POST",
                "data": {
                    'desde': desde,
                    'hasta': hasta,
                    'id_usuario': id_usuario,
                    'todasLasAnulacionesYeliminaciones': todasLasAnulacionesYeliminaciones,
                    'soloAnulaciones': soloAnulaciones,
                    'soloEliminaciones': soloEliminaciones
                },
                beforeSend: function () {
                    showTableLoader("Cargando Resumen de Anulaciones/Eliminaciones...");
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
            "pageLength": 20,
            "select": true,
            "dom": "Bfrtip",
            "ordering": true,
            "filter": true,
            "info": false,
            "footer": false,
            "order": [[0, 'desc']],
            "responsive": {
                details: {
                    type: 'column',
                    target: -1
                }
            },
            "columns": [{
                mData: "id_cotizacion",
                className: "text-center"
            },
            {
                mData: "nombre_producto",
                className: "text-left"
            },
            {
                mData: "cantidad",
                className: "text-center"
            },
            {
                mData: "total_bruto",
                className: "text-center",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },
            {
                mData: "hora_anulacion",
                className: "text-center"
            },
            {
                mData: "esanulado",
                className: "text-center"
            },
            {
                mData: "eseliminado",
                className: "text-center"
            },
            {
                mData: "motivo_anulacion",
                className: "text-center"
            },
            {
                mData: "nombre_usuario",
                className: "text-center",
            },
            ],
            "drawCallback": function () {
                /*
                totalAnulacion = this.api();
                $(totalAnulacion.column(3).footer()).html(
                    'Total En Anulaciones: $' + totalAnulacion.column(3, {
                        page: 'current'
                    }).data().sum()
                )*/

                var totalAnulacion = this.api();
                var totalA = totalAnulacion.column(3, { page: 'current' }).data().sum();

                // Función personalizada para formatear el número con separadores de miles
                function formatNumber(num) {
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }

                var totalFormatted = formatNumber(totalA.toFixed(0));

                $(totalAnulacion.column(3).footer()).html(
                    'TOTAL: $' + totalFormatted
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
                title: tituloExport,
                className: "btn btn-danger",
                exportOptions: {
                    modifier: {
                        selected: null
                    },
                    columns: [0, 1, 2, 3, 4, 5, 6, 7, 8]
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
                    columns: [0, 1, 2, 3, 4, 5, 6, 7, 8]
                }
            }]
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
</script>