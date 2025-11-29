<script>
var base_url = "<?php echo base_url(); ?>";
tablaIngresosEvento = $('#tablaIngresosEvento').DataTable();

$(document).ready(function() {
    ListadoDeIngresoDeEventos();
});

function ListadoDeIngresoDeEventos() {
    if ($.fn.dataTable.isDataTable('#tablaIngresosEvento')) {
        tablaIngresosEvento = $('#tablaIngresosEvento').DataTable();
        tablaIngresosEvento.destroy();
    }
    tablaIngresosEvento = $("#tablaIngresosEvento").DataTable({
        "ajax": {
            "url": base_url + "index.php/IngresoEventos/ListadoDeIngresoEventos"
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
                mData: "id_ingreso_evento",
                className: "text-center"
            },
            {
                mData: "nombre_ingreso_evento",
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
    tablaIngresosEvento
        .on('select', function(e, dt, type, indexes) {
            var rowData = tablaIngresosEvento.rows(indexes).data().toArray();

            $("#idIngresoEvento").val(rowData[0].id_ingreso_evento);
            $("#nombreIngresoEvento").val(rowData[0].nombre_ingreso_evento);
            $('#ModalNuevoIngresoEvento').modal('show');
        })
}
</script>