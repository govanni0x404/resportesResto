<script>
var base_url = "<?php echo base_url(); ?>";
tablaProvincias = $('#tablaProvincias').DataTable();

$(document).ready(function() {
    ListadoDeProvincias();
});

function ListadoDeProvincias() {
    if ($.fn.dataTable.isDataTable('#tablaProvincias')) {
        tablaProvincias = $('#tablaProvincias').DataTable();
        tablaProvincias.destroy();
    }
    tablaProvincias = $("#tablaProvincias").DataTable({
        "ajax": {
            "url": base_url + "index.php/Provincias/ListadoDeProvinciasDatatable"
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
                mData: "id_provincia",
                className: "text-center"
            },
            {
                mData: "nombre_provincia",
                className: "text-center"
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
                title: 'Listado De Provincias',
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
                title: 'Listado De Provincias',
                className: "btn btn-success"
            }
        ]
    });
    tablaProvincias
        .on('select', function(e, dt, type, indexes) {
            var rowData = tablaProvincias.rows(indexes).data().toArray();

            $("#idProvincia").val(rowData[0].id_provincia);
            $("#nombreProvincia").val(rowData[0].nombre_provincia);
            $('#ModalNuevaProvincia').modal('show');
        })
}
</script>