<script>
    var base_url = "<?php echo base_url(); ?>";
    tableDetalleDeVentasTotalizadas = $('#tableDetalleDeVentasTotalizadas').DataTable();
    tablaModalVerDetalleDeVentas = $('#tablaModalVerDetalleDeVentas').DataTable();
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
        var de = $('#date-range-picker').data('daterangepicker').startDate.format('YYYY-MM-DD');
        var a = $('#date-range-picker').data('daterangepicker').endDate.format('YYYY-MM-DD');
        resumenDetalleDeVentasTotalizadas(de, a);
        piechartVentas(de, a);
        ventasMensualesTotalizadasTopDiezDiasMasVentas(de, a);
        ventasMensualesTotalizadasTopDiezDiasMenosVentas(de, a);
    });

    function resumenDetalleDeVentasTotalizadas(desde, hasta) {
        let tituloExport = "Ventas Mensuales Totalizadas";

        if ($.fn.dataTable.isDataTable('#tableDetalleDeVentasTotalizadas')) {
            tableDetalleDeVentasTotalizadas = $('#tableDetalleDeVentasTotalizadas').DataTable();
            tableDetalleDeVentasTotalizadas.destroy();
        }

        console.log({ 'desde': desde, 'hasta': hasta });

        tableDetalleDeVentasTotalizadas = $("#tableDetalleDeVentasTotalizadas").DataTable({
            "ajax": {
                "url": base_url + "index.php/VentasMensualesTotalizadas/ventasMensualesTotalizadas",
                "type": "POST",
                "data": { 'desde': desde, 'hasta': hasta }
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
            "columns": [
                { mData: "ventas_del_dia", className: "text-center" },
                { mData: "total_documentos", className: "text-center" },
                /*{ mData: "iva", className: "text-right", render: $.fn.dataTable.render.number(',', '.', 0, '$') },
                { mData: "monto_neto", className: "text-right", render: $.fn.dataTable.render.number(',', '.', 0, '$') },*/
                { mData: "monto_total", className: "text-center", render: $.fn.dataTable.render.number(',', '.', 0, '$') },
                /*{ mData: "btn_ver_detalle", className: "text-center" },*/
            ],
            "drawCallback": function () {
                var api = this.api();

                //var totalIVA = api.column(2, { page: 'current' }).data().sum();
                //var totalMontoNeto = api.column(3, { page: 'current' }).data().sum();
                //var totalMontoTotal = api.column(4, { page: 'current' }).data().sum();
                var totalMontoTotal = api.column(2, { page: 'current' }).data().sum();


                function formatNumber(num) {
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }

                //$(api.column(2).footer()).html('$' + formatNumber(totalIVA.toFixed(0)));
                //$(api.column(3).footer()).html('$' + formatNumber(totalMontoNeto.toFixed(0)));
                //$(api.column(4).footer()).html('$' + formatNumber(totalMontoTotal.toFixed(0)));
                $(api.column(2).footer()).html('$' + formatNumber(totalMontoTotal.toFixed(0)));

            },
            'language': {
                url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json",
                select: {
                    rows: {
                        _: " (Seleccionadas %d filas)",
                        0: " (Presione una línea a seleccionar)",
                        1: " (1 Fila seleccionada)"
                    }
                }
            },
            "buttons": [
                {
                    text: 'EXCEL',
                    extend: 'excel',
                    exportOptions: {
                        modifier: {
                            selected: null
                        },
                        //columns: [0, 1, 2, 3, 4],
                        columns: [0, 1, 2],
                    },
                    title: tituloExport,
                    className: "btn btn-success"
                }
            ]
        });
    }

    function commaSeparateNumber(val) {
        if (val > 0) {
            while (/(\d+)(\d{3})/.test(val.toString())) {
                val = val.toString().replace(/(\d+)(\d{3})/, '$1' + ',' + '$2');
            }
            return val;
        }
    }


    var totalesVentas = [];
    var Ventas = [];
    dataVentas = {
        labels: Ventas,
        datasets: [{
            data: totalesVentas,
            backgroundColor: [
                "rgb(54, 162, 235)",
                "rgb(255, 99, 132)",
                "rgb(255, 159, 64)",
                "rgb(255, 205, 86)",
                "rgb(75, 192, 192)",
                "rgb(153, 102, 255)",
                "rgb(201, 203, 207)",
                "rgb(255, 0, 0)",
            ],
        }]
    },

        confiVentas = {
            type: 'pie',
            data: dataVentas,
            options: {
                responsive: true,
                // maintainAspectRatio: true,
                // padding: 1000,
                hoverOffset: 0,
                hoverBorderColor: '#000',
                plugins: {
                    legend: {
                        display: false,
                        position: 'bottom'
                    }
                }
            }
        };

    const chaVentas = new Chart(
        document.getElementById('chartVentas'),
        confiVentas,
    );

    /*
    function piechartVentas(desde, hasta) {
        //pie chart
        $.post(base_url + "index.php/VentasMensualesTotalizadas/ventasMensualesTotalizadasPie", {
            'desde': desde,
            'hasta': hasta
        }, function (datos) {
            try {
                var obj = $.parseJSON(datos);
                var sum = 0;
                $.each(obj, function (index, v) {
                    Ventas[index] = v.ventas_del_dia + '%';
                    totalesVentas[index] = v.total_documentos;
                    sum += parseInt(v.total_documentos);
                });
                if (obj.length <= 0 || sum <= 0) {
                    $('#grafico').hide();
                } else {
                    $('#grafico').show();
                }
                chartVentas.update();
            } catch (e) {
                console.log(e);
            }
        })
    }
*/
    function piechartVentas(desde, hasta) {
        $.post(base_url + "index.php/VentasMensualesTotalizadas/ventasMensualesTotalizadasPie", {
            'desde': desde,
            'hasta': hasta
        }, function (datos) {
            try {
                var obj = $.parseJSON(datos);
                var sum = 0;

                Ventas.length = 0;
                totalesVentas.length = 0;

                $.each(obj, function (index, v) {
                    Ventas.push(v.ventas_del_dia + '=');
                    totalesVentas.push(v.total_documentos);
                    sum += parseInt(v.total_documentos);
                });
                if (obj.length <= 0 || sum <= 0) {
                    $('#grafico').hide();
                } else {
                    $('#grafico').show();
                    chaVentas.data.labels = Ventas;
                    chaVentas.data.datasets[0].data = totalesVentas;
                    chaVentas.update();
                }
            } catch (e) {
                console.log('Error:', e);
            }
        });
    }

    function ventasMensualesTotalizadasTopDiezDiasMasVentas(desde, hasta) {
        $.post(base_url + "index.php/VentasMensualesTotalizadas/ventasMensualesTotalizadasTopDiezDiasMasVentas", {
            'desde': desde,
            'hasta': hasta
        }, function (data) {
            var obj = jQuery.parseJSON(data);
            tbl = "";
            var CantidadVentas = 0;
            var totalNeto = 0;
            var totalIva = 0;
            var totalVentas = 0;
            $.each(obj, function (index, v) {
                tbl += "<tr>";
                tbl += "<td class='text-center' style= 'height:10px'>" + v.ventas_del_dia + "</td>";
                tbl += "<td class='text-center' style= 'height:10px'>" + commaSeparateNumber(v.total_documentos) + "</td>";
                /*tbl += "<td class='text-center ocultarIvaVentasPorCanal' style= 'height:10px'>$" + commaSeparateNumber(v.iva) + "</td>";
                tbl += "<td class='text-center ocultarNetoVentasPorCanal' style= 'height:10px'>$" + commaSeparateNumber(v.monto_neto) + "</td>";*/
                tbl += "<td class='text-center' style= 'height:10px'>$" + commaSeparateNumber(v.monto_total) + "</td>";
                tbl += "</tr>";
                CantidadVentas += parseInt(v.total_documentos);
                //totalIva += parseInt(v.iva);
                //totalNeto += parseInt(v.monto_neto);
                totalVentas += parseInt(v.monto_total);
            });
            if (totalVentas > 0) {
                tbl += "<tr><td colspan='2' class='text text-center'><h4><b>TOTAL</b></h4></td><td class='text text-center'><h4><b>$" + commaSeparateNumber(totalVentas) + "</b></h4></td></tr>";
                //tbl += "<tr><td colspan='2' class='text text-center'><h4><b>TOTAL</b></h4></td><td class='text text-center'><h4><b>$" + commaSeparateNumber(totalIva) + "</b></h4></td><td class='text text-center'><h4><b>$" + commaSeparateNumber(totalNeto) + "</b></h4></td><td class='text text-center'><h4><b>$" + commaSeparateNumber(totalVentas) + "</b></h4></td></tr>";
            }
            $('#resumenTopDiasConMayorVenta').html(tbl);

        })
    }

    function ventasMensualesTotalizadasTopDiezDiasMenosVentas(desde, hasta) {
        $.post(base_url + "index.php/VentasMensualesTotalizadas/ventasMensualesTotalizadasTopDiezDiasMenosVentas", {
            'desde': desde,
            'hasta': hasta
        }, function (data) {
            var obj = jQuery.parseJSON(data);
            tbl = "";
            var CantidadVentas = 0;
            var totalNeto = 0;
            var totalIva = 0;
            var totalVentas = 0;
            $.each(obj, function (index, v) {
                tbl += "<tr>";
                tbl += "<td class='text-center' style= 'height:10px'>" + v.ventas_del_dia + "</td>";
                tbl += "<td class='text-center' style= 'height:10px'>" + commaSeparateNumber(v.total_documentos) + "</td>";
                /*tbl += "<td class='text-center ocultarIvaVentasPorCanal' style= 'height:10px'>$" + commaSeparateNumber(v.iva) + "</td>";
                tbl += "<td class='text-center ocultarNetoVentasPorCanal' style= 'height:10px'>$" + commaSeparateNumber(v.monto_neto) + "</td>";*/
                tbl += "<td class='text-center' style= 'height:10px'>$" + commaSeparateNumber(v.monto_total) + "</td>";
                tbl += "</tr>";
                CantidadVentas += parseInt(v.total_documentos);
                //totalIva += parseInt(v.iva);
                //totalNeto += parseInt(v.monto_neto);
                totalVentas += parseInt(v.monto_total);
            });
            if (totalVentas > 0) {
                tbl += "<tr><td colspan='2' class='text text-center'><h4><b>TOTAL</b></h4></td><td class='text text-center'><h4><b>$" + commaSeparateNumber(totalVentas) + "</b></h4></td></tr>";
                //tbl += "<tr><td colspan='2' class='text text-center'><h4><b>TOTAL</b></h4></td><td class='text text-center'><h4><b>$" + commaSeparateNumber(totalIva) + "</b></h4></td><td class='text text-center'><h4><b>$" + commaSeparateNumber(totalNeto) + "</b></h4></td><td class='text text-center'><h4><b>$" + commaSeparateNumber(totalVentas) + "</b></h4></td></tr>";
            }
            $('#resumenTopDiasConMenorVenta').html(tbl);

        })
    }

    function ResumenVentasDelDia(ventas_del_dia) {
        let tituloExport = "Ventas";
        if ($.fn.dataTable.isDataTable('#tablaModalVerDetalleDeVentas')) {
            tablaModalVerDetalleDeVentas = $('#tablaModalVerDetalleDeVentas').DataTable();
            tablaModalVerDetalleDeVentas.destroy();
        }
        tablaModalVerDetalleDeVentas = $("#tablaModalVerDetalleDeVentas").DataTable({
            "ajax": {
                "url": base_url + "index.php/VentasMensualesTotalizadas/ResumenVentasDelDia",
                "type": "POST",
                "data": {
                    'ventas_del_dia': ventas_del_dia,
                }
            },
            "bPaginate": true,
            "bProcessing": true,
            "pageLength": 9999999999,
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
                mData: "id_venta",
                className: "text-center"
            },
            {
                mData: "fecha",
                className: "text-center"
            },
            {
                mData: "nombre_cliente",
                className: "text-center",
            },
            {
                mData: "nombre_tdocumento",
                className: "text-center",
            },
            {
                mData: "folio",
                className: "text-center",
            },
            {
                mData: "total",
                className: "text-center",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },
            ],
            "drawCallback": function () {
                var montoTotal = this.api();
                var total = montoTotal.column(5, { page: 'current' }).data().sum();
                function formatNumber(num) {
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }
                var totalFormatted = formatNumber(total.toFixed(0));
                $(montoTotal.column(5).footer()).html(
                    '$' + totalFormatted
                );
            },
            'language': {
                url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json",
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
    }

    function VerModalDetalleResumenVentasDelDia(ventas_del_dia) {
        ResumenVentasDelDia(ventas_del_dia);
        $('#modalVerDetalleDeOperaciones').modal({
            backdrop: 'static',
            keyboard: false
        });
    }

</script>