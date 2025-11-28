<script>
var base_url = "<?php echo base_url(); ?>";
tablaFamiliasReceta = $('#tablaFamiliasReceta').DataTable();

$(document).ready(function() {
    ListadoDeFamiliasReceta();
});

function ListadoDeFamiliasReceta() {
    if ($.fn.dataTable.isDataTable('#tablaFamiliasReceta')) {
        tablaFamiliasReceta = $('#tablaFamiliasReceta').DataTable();
        tablaFamiliasReceta.destroy();
    }
    tablaFamiliasReceta = $("#tablaFamiliasReceta").DataTable({
        "ajax": {
            "url": base_url + "index.php/FamiliasReceta/ListadoFamiliasReceta"
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
                mData: "id_familia_receta",
                className: "text-center"
            },
            {
                mData: "nombre_familia_receta",
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
                title: 'Listado De Familias De Receta',
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
                title: 'Listado De Familias De Receta',
                className: "btn btn-success"
            }
        ]
    });
    tablaFamiliasReceta
        .on('select', function(e, dt, type, indexes) {
            var rowData = tablaFamiliasReceta.rows(indexes).data().toArray();

            $("#idFamiliaReceta").val(rowData[0].id_familia_receta);
            $("#nombreFamiliaReceta").val(rowData[0].nombre_familia_receta);
            $('#ModalNuevaFamiliaReceta').modal('show');
        })
}
</script>