<script>
var base_url = "<?php echo base_url(); ?>";
tablaGastoEvento = $('#tablaGastoEvento').DataTable();

$(document).ready(function() {
    ListadoGastoEventos();
});

function ListadoGastoEventos() {
    if ($.fn.dataTable.isDataTable('#tablaGastoEvento')) {
        tablaGastoEvento = $('#tablaGastoEvento').DataTable();
        tablaGastoEvento.destroy();
    }
    tablaGastoEvento = $("#tablaGastoEvento").DataTable({
        "ajax": {
            "url": base_url + "index.php/GastoEventos/ListadoDeGastosEventos"
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
                mData: "id_gasto_evento",
                className: "text-center"
            },
            {
                mData: "nombre_gasto_evento",
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
    tablaGastoEvento
        .on('select', function(e, dt, type, indexes) {
            var rowData = tablaGastoEvento.rows(indexes).data().toArray();

            $("#idGastoEvento").val(rowData[0].id_gasto_evento);
            $("#nombreGastoEvento").val(rowData[0].nombre_gasto_evento);
            $('#ModalNuevoGastoEvento').modal('show');
        })
}
</script>