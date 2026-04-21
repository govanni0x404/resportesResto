<script>
var base_url = "<?php echo base_url(); ?>";
tableResumenDeCompras = $('#tableResumenDeCompras').DataTable();
tableDetalleFacturas = $('#tableDetalleFacturas').DataTable();

$(document).ready(function () {
    var desde = $('#date-range-picker').data('daterangepicker').startDate.format('DD-MM-YYYY');
    var hasta = $('#date-range-picker').data('daterangepicker').endDate.format('DD-MM-YYYY');
    resumenDeCompras(desde, hasta);
});

$('#date-range-picker').daterangepicker({
    ranges: dateRange,
    format: 'DD-MM-YYYY',
    locale: {
        format: 'DD-MM-YYYY',
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
        firstDay: 1
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
    
    var $wrapper = $('#tableResumenDeCompras').closest('.dataTables_wrapper');
    if ($wrapper.length === 0) {
        $wrapper = $('#tableResumenDeCompras').parent();
    }
    
    $wrapper.css('position', 'relative');
    $wrapper.append(loaderHtml);
}

function hideTableLoader() {
    $('#dynamic-table-loader').fadeOut(200, function() {
        $(this).remove();
    });
}

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
            },
            beforeSend: function () {
                showTableLoader("Cargando Resumenes de Compras...");
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
            mData: "id_compra",
            className: "text-right",
            "visible": false
        },
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
            var totalCompras = this.api();
            var total = totalCompras.column(5, { page: 'current' }).data().sum();

            function formatNumber(num) {
                return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            }

            var totalFormatted = formatNumber(total.toFixed(0));
            $(totalCompras.column(5).footer()).html('TOTAL: $' + totalFormatted);
        },
        'language': {
            url: "https://cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json",
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
                columns: [1, 2, 3, 4, 5]
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
                columns: [1, 2, 3, 4, 5]
            }
        }]
    });
}

// SOLUCIÓN: Esperar a que termine de cargar antes de abrir el modal
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
            },
            // AGREGAR ESTO: Callback cuando termine de cargar
            "dataSrc": function(json) {
                // Abrir el modal DESPUÉS de que los datos se hayan cargado
                setTimeout(function() {
                    $('#modalDetalleFacturas').modal('show');
                }, 100);
                return json.aaData;
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

            var totalGeneralCompra = this.api();
            var totalP = totalGeneralCompra.column(3, { page: 'current' }).data().sum();

            function formatNumber(num) {
                return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            }

            var totalFormatted = formatNumber(totalP.toFixed(0));
            $(totalGeneralCompra.column(3).footer()).html('TOTAL: $' + totalFormatted);
        },
        'language': {
            url: "https://cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json",
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