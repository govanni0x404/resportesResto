<script>
    var base_url = "<?php echo base_url(); ?>";
    tablaVentasPorProducto = $('#tableVentasPorProducto').DataTable();

  /*  $(document).ready(function () {
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


    function consultarRListadoVentasPorProducto() {
        var desde = $('#date-range-picker').data('daterangepicker').startDate.format('DD-MM-YYYY');
        var hasta = $('#date-range-picker').data('daterangepicker').endDate.format('DD-MM-YYYY');
        ListadoVentasPorProducto(desde, hasta);
        piechartProductos(desde, hasta);
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
        '<div style="width: 40px; height: 40px; border: 4px solid rgba(180, 67, 46,0.18); ' +
        'border-top-color: #B4432E; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>' +
        '<div style="color: #3A2A22; font-size: 14px;">' + (message || 'Cargando...') + '</div>' +
        '</div>';
    
    var $wrapper = $('#tableVentasPorProducto').closest('.dataTables_wrapper');
    if ($wrapper.length === 0) {
        $wrapper = $('#tableVentasPorProducto').parent();
    }
    
    $wrapper.css('position', 'relative');
    $wrapper.append(loaderHtml);
    }

    function hideTableLoader() {
        $('#dynamic-table-loader').fadeOut(500, function() {
            $(this).remove();
        });
    }

    function ListadoVentasPorProducto(desde, hasta) {
        let tituloExport = "Ventas Por Producto";
        if ($.fn.dataTable.isDataTable('#tableVentasPorProducto')) {
            tablaVentasPorProducto = $('#tableVentasPorProducto').DataTable();
            tablaVentasPorProducto.destroy();
        }
        tablaVentasPorProducto = $("#tableVentasPorProducto").DataTable({
            "ajax": {
                "url": base_url + "index.php/VentasPorProducto/ListadoVentasPorProducto",
                "type": "POST",
                "data": {
                    'desde': desde,
                    'hasta': hasta,
                },
                beforeSend: function () {
                    showTableLoader("Cargando Ventas por Productos...");
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
            "order": [[1, 'desc']],
            "responsive": {
                details: {
                    type: 'column',
                    target: -1
                }
            },
            "columns": [
                {
                    mData: "nombre_producto",
                    className: "text-left"
                },
                {
                    mData: "cantidad_vendida",
                    className: "text-center"
                },
                /*{
                    mData: "total_neto",
                    className: "text-right",
                    render: $.fn.dataTable.render.number(',', '.', 0, '$')
                },
                {
                    mData: "total_iva",
                    className: "text-right",
                    render: $.fn.dataTable.render.number(',', '.', 0, '$')
                },*/
                {
                    mData: "total_vendido",
                    className: "text-center",
                    render: $.fn.dataTable.render.number(',', '.', 0, '$')
                },

            ],
            "drawCallback": function () {
                CantidadVendido = this.api();
                $(CantidadVendido.column(1).footer()).html(
                    ' ' + CantidadVendido.column(1, {
                        page: 'current'
                    }).data().sum()
                )
                /*
                                var totalNeto = this.api();
                                var totalN = totalNeto.column(2, { page: 'current' }).data().sum();
                                function formatNumber(num) {
                                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                                }
                                var totalFormatted = formatNumber(totalN.toFixed(0));
                                $(totalNeto.column(2).footer()).html(
                                    '$' + totalFormatted
                                );
                
                                var totalIva = this.api();
                                var totalI = totalIva.column(3, { page: 'current' }).data().sum();
                                function formatNumber(num) {
                                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                                }
                                var totalFormatted = formatNumber(totalI.toFixed(0));
                                $(totalIva.column(3).footer()).html(
                                    '$' + totalFormatted
                                );
                */
                var totalVendido = this.api();
                var totalV = totalVendido.column(2, { page: 'current' }).data().sum();
                function formatNumber(num) {
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }
                var totalFormatted = formatNumber(totalV.toFixed(0));
                $(totalVendido.column(2).footer()).html(
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
            "buttons": [{
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
                    columns: [0, 1, 2]
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
                    columns: [0, 1, 2]
                }
            }]
        });

    }

    var totalesProductos = [];
    var Productos = [];
    dataProductos = {
        labels: Productos,
        datasets: [{
            data: totalesProductos,
            backgroundColor: [
                "#C0533A",
                "#E0A93B",
                "#6F8F3D",
                "#3E7C8C",
                "#8B4F6E",
                "#D98C4F",
                "#9DB07A",
                "#5B6B7F",
                "#D48A8A",
                "#CBB28B",
                "#5FA39A",
                "#6B4A3A",
            ],
        }]
    },

        configProductos = {
            type: 'doughnut',
            data: dataProductos,
            options: {
                responsive: true,
                // maintainAspectRatio: true,
                // padding: 1000,
                aspectRatio: 2,
                cutout: '58%',
                hoverOffset: 8,
                borderWidth: 2,
                borderColor: '#FFFFFF',
                hoverBorderColor: '#FFFFFF',
                layout: { padding: 8 },
                plugins: {
                    legend: {
                        display: true,
                        position: 'right',
                        labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 8, padding: 14, color: '#3A2A22', font: { size: 12 } }
                    },
                    tooltip: { backgroundColor: '#2A1A12', padding: 10, cornerRadius: 8 }
                }
            }
        };

    const chartProductos = new Chart(
        document.getElementById('chartProductos'),
        configProductos,
    );

    function piechartProductos(desde, hasta) {
        //pie chart
        $.post(base_url + "index.php/VentasPorProducto/pieVentasPorProducto", {
            'desde': desde,
            'hasta': hasta
        }, function (datos) {
            try {
                var obj = $.parseJSON(datos);
                var sum = 0;
                $.each(obj, function (index, v) {
                    Productos[index] = v.nombre_producto + ' / ' + v.porcentaje + '%';
                    totalesProductos[index] = v.monto_total;
                    sum += parseInt(v.monto_total);
                });
                if (obj.length <= 0 || sum <= 0) {
                    $('#grafico').hide();
                } else {
                    $('#grafico').show();
                }
                chartProductos.update();
            } catch (e) {
                console.log(e);
            }
        })
    }

    function cargarValidacionReportes() {
        $.post(base_url + "index.php/Configuraciones/obtenerConfiguracionesRestoWeb", {
        }, function (datos) {
            var obj = $.parseJSON(datos);
            // Las columnas Total Neto / Total Iva están comentadas en la vista: la tabla solo tiene 3 columnas
            // (0..2) y pedir column(3) hacía fallar DataTables. Solo se desglosa si esas columnas existen.
            if (tablaVentasPorProducto.columns().count() < 5) return;
            $.each(obj, function (index, v) {
                if (v.desglozar_monto_neto_iva_total_en_reportes == 't') {
                    tablaVentasPorProducto.column(2).visible(true);
                    tablaVentasPorProducto.column(3).visible(true);
                } else {
                    tablaVentasPorProducto.column(2).visible(false);
                    tablaVentasPorProducto.column(3).visible(false);

                }
            });
        }).done(function () {
        })
            .fail(function () {
                alert("Se produjo un error al realizar la consulta");
            })
            .always(function () {
            });
    }

    $('#btnConsultar').click(function () {
        cargarValidacionReportes();
    })
</script>