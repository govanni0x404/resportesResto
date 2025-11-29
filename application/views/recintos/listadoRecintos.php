<script>
var base_url = "<?php echo base_url(); ?>";
tablaRecinto = $('#tablaRecinto').DataTable();

$(document).ready(function() {
    ListadoDeRecinto();
});

function ListadoDeRecinto() {
    if ($.fn.dataTable.isDataTable('#tablaRecinto')) {
        tablaRecinto = $('#tablaRecinto').DataTable();
        tablaRecinto.destroy();
    }
    tablaRecinto = $("#tablaRecinto").DataTable({
        "ajax": {
            "url": base_url + "index.php/Recintos/ListadoDeRecintos"
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
                mData: "id_recinto",
                className: "text-center"
            },
            {
                mData: "nombre_recinto",
                className: "text-center"
            },
            {
                mData: "vigente",
                className: "text-center",
                visible: false
            },
            {
                mData: "nombre_dimpresion",
                className: "text-center",
                visible: false
            },
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
        "buttons": [/*{
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
                title: 'Listado De Recintos',
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
                title: 'Listado De Recintos',
                className: "btn btn-success"
            }*/
        ]
    });
    tablaRecinto
        .on('select', function(e, dt, type, indexes) {
            var rowData = tablaRecinto.rows(indexes).data().toArray();

            $("#idRecinto").val(rowData[0].id_recinto);
            $("#nombreRecinto").val(rowData[0].nombre_recinto);
            if (rowData[0].vigente == 'SI') {
                $('#ckVigente').prop('checked', true);
            } else {
                $('#ckVigente').prop('checked', false);
            }
            ListadoDestinoDeImpresion(rowData[0].nombre_dimpresion);
            $('#ModalNuevoRecinto').modal('show');
        })
}
</script>