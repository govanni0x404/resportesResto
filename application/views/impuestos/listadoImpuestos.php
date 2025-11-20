<script>
var base_url = "<?php echo base_url(); ?>";
tablaImpuestos = $('#tablaImpuestos').DataTable();

$(document).ready(function() {
    ListadoDeImpuestos();
});

function ListadoDeImpuestos() {
    if ($.fn.dataTable.isDataTable('#tablaImpuestos')) {
        tablaImpuestos = $('#tablaImpuestos').DataTable();
        tablaImpuestos.destroy();
    }
    tablaImpuestos = $("#tablaImpuestos").DataTable({
        "ajax": {
            "url": base_url + "index.php/Impuestos/ListadoDeImpuestos"
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
                mData: "id_impuesto",
                className: "text-center"
            },
            {
                mData: "nombre_impuesto",
                className: "text-center"
            },
            {
                mData: "observacion",
                className: "text-center"
            },
            {
                mData: "valor",
                className: "text-center"
            },
            {
                mData: "codigo_sii",
                className: "text-center",
                visible: false
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
        "buttons": [{
                text: 'PDF',
                extend: 'pdf',
                exportOptions: {
                    modifier: {
                        selected: null
                    },
                    columns: [0, 1, 2, 3, 4]
                },
                extend: 'pdfHtml5',
                orientation: 'landscape',
                pageSize: 'LEGAL',
                title: 'Listado De Impuestos',
                className: "btn btn-danger"
            },
            {
                text: 'EXCEL',
                extend: 'excel',
                exportOptions: {
                    modifier: {
                        selected: null
                    },
                    columns: [0, 1, 2, 3, 4]
                },
                title: 'Listado De Impuestos',
                className: "btn btn-success"
            }
        ]
    });
    tablaImpuestos
        .on('select', function(e, dt, type, indexes) {
            var rowData = tablaImpuestos.rows(indexes).data().toArray();

            $("#idImpuesto").val(rowData[0].id_impuesto);
            $("#nombreImpuesto").val(rowData[0].nombre_impuesto);
            $("#txtObservacionImpuesto").val(rowData[0].observacion);
            $("#TasaImpuesto").val(rowData[0].valor);
            $("#CodigoSiiImpuesto").val(rowData[0].codigo_sii);
            $('#ModalNuevoImpuesto').modal('show');
        })
}
</script>