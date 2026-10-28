<script>
    var base_url = "<?php echo base_url(); ?>";
    tablePropinasEntreFechas = $('#tablePropinasEntreFechas').DataTable();
    $(document).ready(function () {
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
        PropinasEntreFechas(desde, hasta);
        resumenTotalPropinaPorGarzon(desde, hasta);
        resumenTotalPropinaPorFormaDePago(desde, hasta);

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
    
    var $wrapper = $('#tablePropinasEntreFechas').closest('.dataTables_wrapper');
    if ($wrapper.length === 0) {
        $wrapper = $('#tablePropinasEntreFechas').parent();
    }
    
    $wrapper.css('position', 'relative');
    $wrapper.append(loaderHtml);
    }

    function hideTableLoader() {
        $('#dynamic-table-loader').fadeOut(500, function() {
            $(this).remove();
        });
    }

    function PropinasEntreFechas(desde, hasta) {
        if ($.fn.dataTable.isDataTable('#tablePropinasEntreFechas')) {
            tablePropinasEntreFechas = $('#tablePropinasEntreFechas').DataTable();
            tablePropinasEntreFechas.destroy();
        }
        tablePropinasEntreFechas = $("#tablePropinasEntreFechas").DataTable({
            "ajax": {
                "url": base_url + "index.php/PropinasPorGarzon/resumenDePropinasEntreFechas",
                "type": "POST",
                "data": {
                    'desde': desde,
                    'hasta': hasta
                },
                beforeSend: function () {
                    showTableLoader("Cargando Propinas Por Garzon...");
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
            "footer": false,
            "responsive": {
                details: {
                    type: 'column',
                    target: -1
                }
            },
            "columns": [{
                mData: "id_caja",
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
                mData: "total_propina",
                className: "text-center",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')

            },
            {
                mData: "nombre_fpago",
                className: "text-center"
            }

            ],
            "drawCallback": function () {
                var totalPropinas = this.api();
                var totalP = totalPropinas.column(3, { page: 'current' }).data().sum();
                function formatNumber(num) {
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }
                var totalFormatted = formatNumber(totalP.toFixed(0));
                $(totalPropinas.column(3).footer()).html(
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
            "buttons": [{
                text: "PDF",
                extend: 'pdfHtml5',
                orientation: 'landscape',
                pageSize: 'LEGAL',
                title: "Propinas Por Garzon",
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
                title: "Propinas Por Garzon",
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


    function resumenTotalPropinaPorGarzon(desde, hasta) {
        $.post(base_url + "index.php/PropinasPorGarzon/resumenTotalPropinaPorGarzon", {
            'desde': desde,
            'hasta': hasta
        }, function (data) {
            var obj = jQuery.parseJSON(data);
            tbl = "";
            var totalPropina = 0;
            $.each(obj, function (index, v) {
                tbl += "<tr>";
                tbl += "<td class='text-center' style= 'height:10px' >" + v.nombre_usuario + "</td>";
                tbl += "<td class='text-center' style= 'height:10px'>" + commaSeparateNumber(v.total_propina) + "</td>";
                tbl += "</tr>";
                totalPropina += parseInt(v.total_propina);
            });
            if (totalPropina > 0) {
                tbl += "<tr><td colspan='1' class='text text-left'><h4><b>TOTAL</b></h4></td><td class='text text-right ocultarNetoTdocumento'><h4><b>$" + commaSeparateNumber(totalPropina) + "</b></h4></td></tr>";

            }
            $('#resumenTotalPorGarzon').html(tbl);

        })
    }

    function resumenTotalPropinaPorFormaDePago(desde, hasta) {
        $.post(base_url + "index.php/PropinasPorGarzon/resumenTotalPropinaPorFormaDePago", {
            'desde': desde,
            'hasta': hasta
        }, function (data) {
            var obj = jQuery.parseJSON(data);
            tbl = "";
            var totalPropina = 0;
            $.each(obj, function (index, v) {
                tbl += "<tr>";
                tbl += "<td class='text-center' style= 'height:10px' >" + v.nombre_fpago + "</td>";
                tbl += "<td class='text-center' style= 'height:10px'>" + commaSeparateNumber(v.total_propina) + "</td>";
                tbl += "</tr>";
                totalPropina += parseInt(v.total_propina);
            });
            if (totalPropina > 0) {
                tbl += "<tr><td colspan='1' class='text text-left'><h4><b>TOTAL</b></h4></td><td class='text text-right ocultarNetoTdocumento'><h4><b>$" + commaSeparateNumber(totalPropina) + "</b></h4></td></tr>";

            }
            $('#resumenTotalPorFormaDePago').html(tbl);

        })
    }

    function commaSeparateNumber(val) {
        if (val > 0) {
            while (/(\d+)(\d{3})/.test(val.toString())) {
                val = val.toString().replace(/(\d+)(\d{3})/, '$1' + ',' + '$2');
            }
            return val;
        }
    }

    function PDFResumenDePropinasPorGarzon() {
        var desde = $('#date-range-picker').data('daterangepicker').startDate.format('DD-MM-YYYY');
        var hasta = $('#date-range-picker').data('daterangepicker').endDate.format('DD-MM-YYYY');
        $.redirect(base_url + 'index.php/PropinasPorGarzon/PDFResumenDePropinasPorGarzon', {
            'desde': desde,
            'hasta': hasta,
        }, 'POST', 'wDteD');
    }

</script>