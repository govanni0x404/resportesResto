<script>
    var base_url = "<?php echo base_url(); ?>";
    tableCierresDeCaja = $('#tableCierresDeCaja').DataTable();
    $(document).ready(function () {
        var desde = $('#fechaCaja').data('daterangepicker').startDate.format('DD-MM-YYYY');
        var hasta = $('#fechaCaja').data('daterangepicker').endDate.format('DD-MM-YYYY');
        cierresDeCaja(desde, hasta);
    });
    /*
    $('#fechaCaja').daterangepicker({
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

    function updateInput(start, end) {
        $('#fechaCaja').val(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
    }

    $('#fechaCaja').on('apply.daterangepicker', function (ev, picker) {
        updateInput(picker.startDate, picker.endDate);
    });
    */


      $('#fechaCaja').daterangepicker({
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


    $(document).on('click', function (event) {
        if (!$(event.target).closest('.daterangepicker').length && !$(event.target).is('#fechaCaja')) {
            var picker = $('#fechaCaja').data('daterangepicker');
            if (picker) {
                updateInput(picker.startDate, picker.endDate);
            }
        }
    });


    $("#btnTest").on("click", function () {
        var id_caja = $('#cboCajas').find('option:selected').attr('value');
        var desde = $('#fechaCaja').data('daterangepicker').startDate.format('DD-MM-YYYY');
        var hasta = $('#fechaCaja').data('daterangepicker').endDate.format('DD-MM-YYYY');
        cierresDeCaja(desde, hasta);
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
    
    var $wrapper = $('#tableCierresDeCaja').closest('.dataTables_wrapper');
    if ($wrapper.length === 0) {
        $wrapper = $('#tableCierresDeCaja').parent();
    }
    
    $wrapper.css('position', 'relative');
    $wrapper.append(loaderHtml);
    }

    function hideTableLoader() {
        $('#dynamic-table-loader').fadeOut(500, function() {
            $(this).remove();
        });
    }

    function cierresDeCaja(desde, hasta) {
        let tituloExport = "Ventas Por Producto";
        if ($.fn.dataTable.isDataTable('#tableCierresDeCaja')) {
            tableCierresDeCaja = $('#tableCierresDeCaja').DataTable();
            tableCierresDeCaja.destroy();
        }
        tableCierresDeCaja = $("#tableCierresDeCaja").DataTable({
            "ajax": {
                "url": base_url + "index.php/CierresDeCaja/tableCierresDeCaja",
                "type": "POST",
                "data": {
                    'desde': desde,
                    'hasta': hasta
                },
                beforeSend: function () {
                    showTableLoader("Cargando Cierres de Caja...");
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
            "lengthChange":false,
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
            {
                mData: "id_caja",
                "width": "1%",
                className: "text-center"
            },
            {
                mData: "fecha_apertura",
                "width": "1%",
                className: "text-center"
            },
            {
                mData: "fecha_rendicion",
                "width": "1%",
                className: "text-center"
            },
            {
                mData: "ventas_local",
                "width": "1%",
                className: "text-center",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },
            {
                mData: "fondo_fijo",
                "width": "1%",
                className: "text-center",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },
            {
                mData: "total_sistema",
                "width": "1%",
                className: "text-center",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },
            {
                mData: "descuento",
                "width": "1%",
                className: "text-center",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },
            {
                mData: "ventas",
                "width": "1%",
                className: "text-center",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },
            {
                mData: "cortesia",
                "width": "1%",
                className: "text-center",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },
            {
                mData: "ingresos",
                "width": "1%",
                className: "text-center",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },
            {
                mData: "egresos",
                "width": "1%",
                className: "text-center",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },
            {
                mData: "subtotal",
                "width": "1%",
                className: "text-center",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },
            {
                mData: "efectivo",
                "width": "1%",
                className: "text-center",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },
            {
                mData: "efectivo_entregado",
                "width": "1%",
                className: "text-center",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },
            {
                mData: "diferencia",
                "width": "1%",
                className: "text-center",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },
            {
                mData: "propina",
                "width": "1%",
                className: "text-center",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },
            {
                mData: "estado_caja",
                "width": "1%",
                className: "text-center"
            },
            {
                mData: "cajero",
                "width": "1%",
                className: "text-center"
            },
            {
                mData: "btn_cierre",
                "width": "1%",
                className: "text-center"
            },
            {
                mData: "test",
                "width": "1%",
                className: 'dtr-control',
                orderable: false,
                targets: -1
            }
            ],
            "drawCallback": function () {
                /*
                totalVentasEnLocal = this.api();
                $(totalVentasEnLocal.column(3).footer()).html(
                    ' $' + totalVentasEnLocal.column(3, {
                        page: 'current'
                    }).data().sum()
                )
                */
                var totalVentasEnLocal = this.api();
                var TventasLocal = totalVentasEnLocal.column(4, { page: 'current' }).data().sum();

                // Función personalizada para formatear el número con separadores de miles
                function formatNumber(num) {
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }

                var totalFormatted = formatNumber(TventasLocal.toFixed(0));

                $(totalVentasEnLocal.column(4).footer()).html(
                    '$' + totalFormatted
                );
                /*
                totalFondoFijo = this.api();
                $(totalFondoFijo.column(5).footer()).html(
                    ' $' + totalFondoFijo.column(5, {
                        page: 'current'
                    }).data().sum()
                )*/
                var totalFondoFijo = this.api();
                var TfondoFijo = totalFondoFijo.column(5, { page: 'current' }).data().sum();

                // Función personalizada para formatear el número con separadores de miles
                function formatNumber(num) {
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }

                var totalFormatted = formatNumber(TfondoFijo.toFixed(0));

                $(totalFondoFijo.column(5).footer()).html(
                    '$' + totalFormatted
                );
                /*
                totalSistema = this.api();
                $(totalSistema.column(6).footer()).html(
                    ' $' + totalSistema.column(6, {
                        page: 'current'
                    }).data().sum()
                )*/
                var totalSistema = this.api();
                var Tsistema = totalSistema.column(6, { page: 'current' }).data().sum();

                // Función personalizada para formatear el número con separadores de miles
                function formatNumber(num) {
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }

                var totalFormatted = formatNumber(Tsistema.toFixed(0));

                $(totalSistema.column(6).footer()).html(
                    '$' + totalFormatted
                );
                /*
                totalDescuentos = this.api();
                $(totalDescuentos.column(7).footer()).html(
                    ' $' + totalDescuentos.column(7, {
                        page: 'current'
                    }).data().sum()
                )*/
                var totalDescuentos = this.api();
                var Tdescuentos = totalDescuentos.column(7, { page: 'current' }).data().sum();

                // Función personalizada para formatear el número con separadores de miles
                function formatNumber(num) {
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }

                var totalFormatted = formatNumber(Tdescuentos.toFixed(0));

                $(totalDescuentos.column(7).footer()).html(
                    '$' + totalFormatted
                );
                /*
                totalVentas = this.api();
                $(totalVentas.column(8).footer()).html(
                    ' $' + totalVentas.column(8, {
                        page: 'current'
                    }).data().sum()
                )*/
                var totalVentas = this.api();
                var Tventas = totalVentas.column(8, { page: 'current' }).data().sum();

                // Función personalizada para formatear el número con separadores de miles
                function formatNumber(num) {
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }

                var totalFormatted = formatNumber(Tventas.toFixed(0));

                $(totalVentas.column(8).footer()).html(
                    '$' + totalFormatted
                );
                /*
                totalCortesias = this.api();
                $(totalCortesias.column(9).footer()).html(
                    ' $' + totalCortesias.column(9, {
                        page: 'current'
                    }).data().sum()
                )*/
                var totalCortesias = this.api();
                var Tcortesias = totalCortesias.column(9, { page: 'current' }).data().sum();

                // Función personalizada para formatear el número con separadores de miles
                function formatNumber(num) {
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }

                var totalFormatted = formatNumber(Tcortesias.toFixed(0));

                $(totalCortesias.column(9).footer()).html(
                    '$' + totalFormatted
                );

                /*
                totalIngresos = this.api();
                $(totalIngresos.column(10).footer()).html(
                    ' $' + totalIngresos.column(10, {
                        page: 'current'
                    }).data().sum()
                )
                    */

                var totalIngresos = this.api();
                var Tingresos = totalIngresos.column(10, { page: 'current' }).data().sum();

                // Función personalizada para formatear el número con separadores de miles
                function formatNumber(num) {
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }

                var totalFormatted = formatNumber(Tingresos.toFixed(0));

                $(totalIngresos.column(10).footer()).html(
                    '$' + totalFormatted
                );

                msgTotales = this.api();
                $(msgTotales.column(0).footer()).html(
                    'Totales'
                )
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
                text: 'CIERRE CONSOLIDADO',
                className: 'btn btn-danger ml-5',
                action: function () {
                    var desde = $('#fechaCaja').data('daterangepicker').startDate.format('DD-MM-YYYY');
                    var hasta = $('#fechaCaja').data('daterangepicker').endDate.format('DD-MM-YYYY');
                    var listaCajas = "";
                    var firstCaja = 0;
                    var arrayCajas = [];
                    var i = 0;
                    var data = $('#tableCierresDeCaja').DataTable().rows().data().toArray();

                    data.forEach(function () {
                        id_caja = data[i].id_caja;
                        i++;

                        if (arrayCajas.indexOf(id_caja) === -1) {
                            arrayCajas.push(id_caja);
                            test0 = id_caja;
                        }
                        if (listaCajas.length > 0) {
                            listaCajas += ",";
                        } else {
                            firstCaja = id_caja;
                        }
                        listaCajas += id_caja;

                    })
                    if (arrayCajas.length > 0) {
                        $.redirect(base_url + 'index.php/CierresDeCaja/cierreCajaPDF', {
                            'desde': desde,
                            'hasta': hasta,
                            'listaCajas': listaCajas
                        }, 'POST', 'wDteD');
                    } else {
                        swal('Error', 'No hay datos para generar el reporte.', 'error')
                    }

                }
            }]
        });
    }

    function cierreCajaPDF(id_caja) {
        $.redirect(base_url + 'index.php/CierresDeCaja/cierreCajaPDF', {
            'id_caja': id_caja
        }, 'POST', 'wDteD');
    }
</script>