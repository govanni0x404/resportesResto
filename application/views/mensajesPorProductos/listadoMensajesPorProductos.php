<script>
var base_url = "<?php echo base_url(); ?>";
tablaMsgProducto = $('#tablaMsgProducto').DataTable();

$(document).ready(function() {
    ListadoDeMensajesPorProductos();
});

function ListadoDeMensajesPorProductos() {
    if ($.fn.dataTable.isDataTable('#tablaMsgProducto')) {
        tablaMsgProducto = $('#tablaMsgProducto').DataTable();
        tablaMsgProducto.destroy();
    }
    tablaMsgProducto = $("#tablaMsgProducto").DataTable({
        "ajax": {
            "url": base_url + "index.php/MensajesPorProductos/ListadoDeMensajesPorProductos"
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
                mData: "id_mensaje_producto",
                className: "text-center"
            },
            {
                mData: "nombre_producto",
                className: "text-center"
            },
            {
                mData: "mensaje",
                className: "text-center"
            },
            {
                mData: "vigente",
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
        "buttons": [
        ]
    });
    tablaMsgProducto
        .on('select', function(e, dt, type, indexes) {
            var rowData = tablaMsgProducto.rows(indexes).data().toArray();

            $("#idMsg").val(rowData[0].id_mensaje_producto);
            $("#txtMsgProducto").val(rowData[0].mensaje);
            if (rowData[0].vigente == 'SI') {
                $('#ckVigenciaMsg').prop('checked', true);
            } else {
                $('#ckVigenciaMsg').prop('checked', false);
            }
            ListadoProductos(rowData[0].nombre_producto);
            $('#ModalMensajesDelProducto').modal('show');
        })
}
</script>