<script>
    var base_url = "<?php echo base_url(); ?>";
    tableResumenDeCompras = $('#tableResumenDeCompras').DataTable();
    tableDetalleFacturas = $('#tableDetalleFacturas').DataTable();
    $(document).ready(function () {
        // var fecha = $('#fechaCaja').val();
        // llenarCboBodegas();
        var desde = $('#date-range-picker').data('daterangepicker').startDate.format('DD-MM-YYYY');
        var hasta = $('#date-range-picker').data('daterangepicker').endDate.format('DD-MM-YYYY');
        resumenDeCompras(desde, hasta);
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


    $("#btnFiltrar").on('click', function () {
        var desde = $('#date-range-picker').data('daterangepicker').startDate.format('DD-MM-YYYY');
        var hasta = $('#date-range-picker').data('daterangepicker').endDate.format('DD-MM-YYYY');
        resumenDeCompras(desde, hasta);
    });

    function resumenDeCompras(desde, hasta) {
        let tituloExport = "Resumen De Compras";
        if ($.fn.dataTable.isDataTable('#tableResumenDeCompras')) {
            tableResumenDeCompras = $('#tableResumenDeCompras').DataTable();
            tableResumenDeCompras.destroy();
        }
        tableResumenDeCompras = $("#tableResumenDeCompras").DataTable({
            "ajax": {
                "url": base_url + "index.php/ResumenDeCompras/resumenDeCompras",
                "type": "POST",
                "data": {
                    'desde': desde,
                    'hasta': hasta
                }
            },
            "bPaginate": true,
            "bProcessing": true,
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
                mData: "id_compra",
                className: "text-right",
                "visible": false
            },
            // {
            // 	mData: "btn_compras",
            // 	"width": "1%",
            // 	className: "text-center"
            // },
            {
                mData: "nombre_proveedor",
                className: "text-center"
            },
            {
                mData: "nombre_tdocumento",
                className: "text-center"
            },
            {
                mData: "numero_documento",
                className: "text-center"
            },
            {
                mData: "fecha",
                className: "text-center"
            },
            {
                mData: "total",
                className: "text-center",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },
            {
                mData: "btnVerDetalleCompras",
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
                totalCompras = this.api();
                $(totalCompras.column(5).footer()).html(
                    ' $' + totalCompras.column(5, {
                        page: 'current'
                    }).data().sum()
                )
                    */

                var totalCompras = this.api();
                var total = totalCompras.column(5, { page: 'current' }).data().sum();

                // Función personalizada para formatear el número con separadores de miles
                function formatNumber(num) {
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }

                var totalFormatted = formatNumber(total.toFixed(0));

                $(totalCompras.column(5).footer()).html(
                    'TOTAL: $' + totalFormatted
                );

                /*
                MsgTotales = this.api();
                $(MsgTotales.column(6).footer()).html(
                    'Totales'
                )*/

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
                extend: 'pdf',
                exportOptions: {
                    modifier: {
                        selected: null
                    },
                    columns: [1, 2, 3, 4, 5]
                },
                extend: 'pdfHtml5',
                orientation: 'landscape',
                pageSize: 'LEGAL',
                title: tituloExport,
                className: "btn btn-danger"
            }]
        });
    }

    function detalleFacturas(id_compra) {
        let tituloExport = "Detalle Facturas De Compra";
        if ($.fn.dataTable.isDataTable('#tableDetalleFacturas')) {
            tableDetalleFacturas = $('#tableDetalleFacturas').DataTable();
            tableDetalleFacturas.destroy();
        }
        tableDetalleFacturas = $("#tableDetalleFacturas").DataTable({
            "ajax": {
                "url": base_url + "index.php/ResumenDeCompras/detalleFacturas",
                "type": "POST",
                "data": {
                    'id_compra': id_compra
                }
            },
            "bPaginate": true,
            "bProcessing": true,
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
                mData: "nombre_insumo",
                className: "text-left"
            },
            {
                mData: "cantidad",
                className: "text-center"
            },
            {
                mData: "bruto_unitario",
                className: "text-center",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },
            {
                mData: "total",
                className: "text-center",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
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
                totalCantidadComprada = this.api();
                $(totalCantidadComprada.column(1).footer()).html(
                    ' ' + totalCantidadComprada.column(1, {
                        page: 'current'
                    }).data().sum()
                )
                /*
                totalUnitario = this.api();
                $(totalUnitario.column(2).footer()).html(
                    ' $' + totalUnitario.column(2, {
                        page: 'current'
                    }).data().sum()
                )
                totalGeneral = this.api();
                $(totalGeneral.column(3).footer()).html(
                    ' $' + totalGeneral.column(3, {
                        page: 'current'
                    }).data().sum()
                )
                    */

                var totalGeneralCompra = this.api();
                var totalP = totalGeneralCompra.column(3, { page: 'current' }).data().sum();

                // Función personalizada para formatear el número con separadores de miles
                function formatNumber(num) {
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }

                var totalFormatted = formatNumber(totalP.toFixed(0));

                $(totalGeneralCompra.column(3).footer()).html(
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
                extend: 'pdf',
                exportOptions: {
                    modifier: {
                        selected: null
                    },
                    columns: [0, 1, 2, 3]
                },
                extend: 'pdfHtml5',
                orientation: 'landscape',
                pageSize: 'LEGAL',
                title: tituloExport,
                className: "btn btn-danger"
            }]
        });
    }

    function resumenComprasPDF(id_compra) {
        $.redirect(base_url + 'index.php/ResumenDeCompras/resumenComprasPDF', {
            'id_compra': id_compra
        }, 'POST', 'wDteD');
    }
</script>