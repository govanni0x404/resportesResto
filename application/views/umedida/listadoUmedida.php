<script>
var base_url = "<?php echo base_url(); ?>";
tablaUmedida = $('#tablaUmedida').DataTable();

$(document).ready(function() {
    ListadoDeUmedida();
});

function ListadoDeUmedida() {
    if ($.fn.dataTable.isDataTable('#tablaUmedida')) {
        tablaUmedida = $('#tablaUmedida').DataTable();
        tablaUmedida.destroy();
    }
    tablaUmedida = $("#tablaUmedida").DataTable({
        "ajax": {
            "url": base_url + "index.php/Umedida/ListadoDeUmedida"
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
                mData: "id_umedida",
                className: "text-center"
            },
            {
                mData: "nombre_umedida",
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
    tablaUmedida
        .on('select', function(e, dt, type, indexes) {
            var rowData = tablaUmedida.rows(indexes).data().toArray();

            $("#idUmedida").val(rowData[0].id_umedida);
            $("#nombreUmedida").val(rowData[0].nombre_umedida);
            $('#ModalNuevaUmedida').modal('show');
        })
}
</script>