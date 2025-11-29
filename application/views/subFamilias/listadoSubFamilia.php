<script>
var base_url = "<?php echo base_url(); ?>";
tablaSubFamilia = $('#tablaSubFamilia').DataTable();

$(document).ready(function() {
    ListadoDeSubFamilias();
});

function ListadoDeSubFamilias() {
    if ($.fn.dataTable.isDataTable('#tablaSubFamilia')) {
        tablaSubFamilia = $('#tablaSubFamilia').DataTable();
        tablaSubFamilia.destroy();
    }
    tablaSubFamilia = $("#tablaSubFamilia").DataTable({
        "ajax": {
            "url": base_url + "index.php/SubFamilias/ListadoDeSubFamilias"
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
                mData: "id_subfamilia",
                className: "text-center"
            },
            {
                mData: "nombre_subfamilia",
                className: "text-center"
            },
            {
                mData: "nombre_familia",
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
        "buttons": [
        ]
    });
    tablaSubFamilia
        .on('select', function(e, dt, type, indexes) {
            var rowData = tablaSubFamilia.rows(indexes).data().toArray();

            $("#idSubFamilia").val(rowData[0].id_subfamilia);
            $("#nombreSubFamilia").val(rowData[0].nombre_subfamilia);
            ListadoDeFamilias(rowData[0].nombre_familia);
            $('#ModalNuevaSubFamilia').modal('show');
        })
}
</script>