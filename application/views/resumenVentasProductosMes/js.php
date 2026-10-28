<script>
var base_url = "<?php echo base_url(); ?>";
var tableResumenVentasProductos;
var tableDetalleProducto;

$(document).ready(function () {
    var desde = $('#date-range-picker').data('daterangepicker').startDate.format('DD-MM-YYYY');
    var hasta = $('#date-range-picker').data('daterangepicker').endDate.format('DD-MM-YYYY');
    resumenVentasProductos(desde, hasta);
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

$("#btnFiltrar").on('click', function () {
    var desde = $('#date-range-picker').data('daterangepicker').startDate.format('DD-MM-YYYY');
    var hasta = $('#date-range-picker').data('daterangepicker').endDate.format('DD-MM-YYYY');
    resumenVentasProductos(desde, hasta);
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
    
    var $wrapper = $('#tableResumenVentasProductos').closest('.dataTables_wrapper');
    if ($wrapper.length === 0) {
        $wrapper = $('#tableResumenVentasProductos').parent();
    }
    
    $wrapper.css('position', 'relative');
    $wrapper.append(loaderHtml);
}

function hideTableLoader() {
    $('#dynamic-table-loader').fadeOut(200, function() {
        $(this).remove();
    });
}

function resumenVentasProductos(desde, hasta) {
    let tituloExport = "Resumen De Ventas Por Producto y Mes";
    
    if ($.fn.dataTable.isDataTable('#tableResumenVentasProductos')) {
        tableResumenVentasProductos = $('#tableResumenVentasProductos').DataTable();
        tableResumenVentasProductos.destroy();
    }
    
    tableResumenVentasProductos = $("#tableResumenVentasProductos").DataTable({
        "ajax": {
            "url": base_url + "index.php/ResumenVentasProductosMes/resumenVentasProductos",
            "type": "POST",
            "data": {
                'desde': desde,
                'hasta': hasta
            },
            beforeSend: function () {
                showTableLoader("Cargando resumen de ventas...");
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
        "info": true,
        "responsive": {
            details: {
                type: 'column',
                target: -1
            }
        },
        "columns": [
            {
                mData: "id_producto",
                className: "text-right",
                "visible": false
            },
            {
                mData: "nombre_producto",
                className: "text-left"
            },
            {
                mData: "nombre_familia",
                className: "text-center"
            },
            {
                mData: "mes",
                className: "text-center"
            },
            {
                mData: "cantidad",
                className: "text-center",
                render: $.fn.dataTable.render.number(',', '.', 0)
            },
            {
                mData: "total",
                className: "text-center",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },
            // {
            //     mData: "btnVerDetalle",
            //     "width": "10%",
            //     className: "text-center"
            // },
            {
                mData: "test",
                "width": "1%",
                className: 'dtr-control',
                orderable: false,
                targets: -1
            }
        ],
        "drawCallback": function () {
            var api = this.api();
            
            // Total cantidad
            var totalCantidad = api.column(4, { page: 'current' }).data().sum();
            $(api.column(4).footer()).html('<strong>' + totalCantidad.toLocaleString('es-CL') + '</strong>');
            
            // Total ventas
            var totalVentas = api.column(5, { page: 'current' }).data().sum();
            var totalFormatted = totalVentas.toLocaleString('es-CL', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
            $(api.column(5).footer()).html('<strong>$' + totalFormatted + '</strong>');
        },
        'language': {
            url: "https://cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json"
        },
        "buttons": [
            {
                text: "Excel",
                extend: 'excelHtml5',
                exportOptions: {
                    columns: [1, 2, 3, 4, 5]
                },
                title: tituloExport,
                className: "btn btn-success"
            },
            {
                text: "PDF",
                extend: 'pdfHtml5',
                orientation: 'landscape',
                pageSize: 'LEGAL',
                exportOptions: {
                    columns: [1, 2, 3, 4, 5]
                },
                title: tituloExport,
                className: "btn btn-danger"
            }
        ]
    });
}

function detalleProductoMes(id_producto, mes) {
    if ($.fn.dataTable.isDataTable('#tableDetalleProducto')) {
        tableDetalleProducto = $('#tableDetalleProducto').DataTable();
        tableDetalleProducto.destroy();
    }
    
    tableDetalleProducto = $("#tableDetalleProducto").DataTable({
        "ajax": {
            "url": base_url + "index.php/ResumenVentasProductosMes/detalleProductoMes",
            "type": "POST",
            "data": {
                'id_producto': id_producto,
                'mes': mes
            },
            "dataSrc": function(json) {
                setTimeout(function() {
                    $('#modalDetalleProducto').modal('show');
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
        "info": true,
        "responsive": {
            details: {
                type: 'column',
                target: -1
            }
        },
        "columns": [
            {
                mData: "id_venta",
                className: "text-center"
            },
            {
                mData: "fecha_venta",
                className: "text-center"
            },
            {
                mData: "cliente",
                className: "text-left"
            },
            {
                mData: "cantidad",
                className: "text-center",
                render: $.fn.dataTable.render.number(',', '.', 0)
            },
            {
                mData: "precio_unitario",
                className: "text-right",
                render: $.fn.dataTable.render.number(',', '.', 0, '$')
            },
            {
                mData: "total",
                className: "text-right",
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
            var api = this.api();
            
            var totalCantidad = api.column(3, { page: 'current' }).data().sum();
            $(api.column(3).footer()).html('<strong>' + totalCantidad.toLocaleString('es-CL') + '</strong>');
            
            var total = api.column(5, { page: 'current' }).data().sum();
            var totalFormatted = total.toLocaleString('es-CL', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
            $(api.column(5).footer()).html('<strong>$' + totalFormatted + '</strong>');
        },
        'language': {
            url: "https://cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json"
        },
        "buttons": [
            {
                text: "PDF",
                extend: 'pdfHtml5',
                orientation: 'landscape',
                pageSize: 'LEGAL',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5]
                },
                title: "Detalle Ventas Producto",
                className: "btn btn-danger"
            },
            {
                text: "Excel",
                extend: 'excelHtml5',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5]
                },
                title: "Detalle Ventas Producto",
                className: "btn btn-success"
            }
        ]
    });
}
</script>