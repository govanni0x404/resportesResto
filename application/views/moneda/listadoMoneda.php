<script>
var base_url = "<?php echo base_url(); ?>";
tablaImpuestos = $('#tablaMoneda').DataTable();

$(document).ready(function() {
    ListadoDeMoneda();
});

function ListadoDeMoneda() {
    if ($.fn.dataTable.isDataTable('#tablaMoneda')) {
        tablaMoneda = $('#tablaMoneda').DataTable();
        tablaMoneda.destroy();
    }
    tablaMoneda = $("#tablaMoneda").DataTable({
        "ajax": {
            "url": base_url + "index.php/Moneda/ListadoDeMonedas"
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
                mData: "id_moneda",
                className: "text-center"
            },
            {
                mData: "nombre_moneda",
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
    tablaMoneda
        .on('select', function(e, dt, type, indexes) {
            var rowData = tablaMoneda.rows(indexes).data().toArray();

            $("#idMoneda").val(rowData[0].id_moneda);
            $("#nombreMoneda").val(rowData[0].nombre_moneda);
            $('#ModalNuevaMoneda').modal('show');
        })
}
</script>