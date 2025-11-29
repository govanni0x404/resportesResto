<script>
var base_url = "<?php echo base_url(); ?>";
tablaCentroCosto = $('#tablaCentroCosto').DataTable();

$(document).ready(function() {
    ListadoDeCentroCosto();
});

function ListadoDeCentroCosto() {
    if ($.fn.dataTable.isDataTable('#tablaCentroCosto')) {
        tablaCentroCosto = $('#tablaCentroCosto').DataTable();
        tablaCentroCosto.destroy();
    }
    tablaCentroCosto = $("#tablaCentroCosto").DataTable({
        "ajax": {
            "url": base_url + "index.php/CentroCosto/ListadoDeCentroDeCostos"
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
                mData: "id_ccosto",
                className: "text-center"
            },
            {
                mData: "nombre_ccosto",
                className: "text-center"
            },
            {
                mData: "esingreso",
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
    tablaCentroCosto
        .on('select', function(e, dt, type, indexes) {
            var rowData = tablaCentroCosto.rows(indexes).data().toArray();

            $("#idCentroCosto").val(rowData[0].id_ccosto);
            $("#nombreCentroCosto").val(rowData[0].nombre_ccosto);
            if (rowData[0].esingreso == 'SI') {
                $('#ckEsIngreso').prop('checked', true);
            } else {
                $('#ckEsIngreso').prop('checked', false);
            }
            $('#ModalNuevoCentroCosto').modal('show');
        })
}
</script>