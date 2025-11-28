<script>
var base_url = "<?php echo base_url(); ?>";
tablaFamilias = $('#tablaFamilias').DataTable();

$(document).ready(function() {
    ListadoDeFamilias();
});

function ListadoDeFamilias() {
    if ($.fn.dataTable.isDataTable('#tablaFamilias')) {
        tablaFamilias = $('#tablaFamilias').DataTable();
        tablaFamilias.destroy();
    }
    tablaFamilias = $("#tablaFamilias").DataTable({
        "ajax": {
            "url": base_url + "index.php/Familias/ListadoDeFamilias"
        },
        "bPaginate": true,
        "bProcessing": true,
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
                mData: "id_familia",
                className: "text-center"
            },
            {
                mData: "nombre_familia",
                className: "text-center"
            },
            {
                mData: "alias",
                className: "text-center"
            },
            {
                mData: "venta_directa",
                className: "text-center"
            },
            {
                mData: "orden_familia",
                className: "text-center"
            },
            {
                mData: "imprimir_en_detalle_pedido",
                className: "text-center",
                visible: false
            }
        ],
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
                text: 'PDF',
                extend: 'pdf',
                exportOptions: {
                    modifier: {
                        selected: null
                    },
                    columns: [0, 1]
                },
                extend: 'pdfHtml5',
                orientation: 'landscape',
                pageSize: 'LEGAL',
                title: 'Listado De Familias',
                className: "btn btn-danger"
            },
            {
                text: 'EXCEL',
                extend: 'excel',
                exportOptions: {
                    modifier: {
                        selected: null
                    },
                    columns: [0, 1]
                },
                title: 'Listado De Familias',
                className: "btn btn-success"
            }
        ]
    });
    tablaFamilias
        .on('select', function(e, dt, type, indexes) {
            var rowData = tablaFamilias.rows(indexes).data().toArray();

            $("#idFamilia").val(rowData[0].id_familia);
            $("#nombreFamilia").val(rowData[0].nombre_familia);
            $("#AliasFamilia").val(rowData[0].alias);
            $("#ordenFamilia").val(rowData[0].orden_familia);
            if (rowData[0].venta_directa == 'SI') {
                $('#ckVentaDirectaFamilia').prop('checked', true);
            } else {
                $('#ckVentaDirectaFamilia').prop('checked', false);
            }

            if (rowData[0].imprimir_en_detalle_pedido == 't') {
                $('#ckImprimirEnDetallePedido').prop('checked', true);
            } else {
                $('#ckImprimirEnDetallePedido').prop('checked', false);
            }
            $('#ModalNuevaFamilia').modal('show');
        })
}
</script>