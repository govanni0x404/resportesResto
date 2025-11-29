<script>
var base_url = "<?php echo base_url(); ?>";
tablaFamiliasInsumo = $('#tablaFamiliasInsumo').DataTable();

$(document).ready(function() {
    ListadoDeFamiliasInsumo();
});

function ListadoDeFamiliasInsumo() {
    if ($.fn.dataTable.isDataTable('#tablaFamiliasInsumo')) {
        tablaFamiliasInsumo = $('#tablaFamiliasInsumo').DataTable();
        tablaFamiliasInsumo.destroy();
    }
    tablaFamiliasInsumo = $("#tablaFamiliasInsumo").DataTable({
        "ajax": {
            "url": base_url + "index.php/FamiliasInsumos/ListadoFamiliasInsumo"
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
                mData: "id_grupo_insumo",
                className: "text-center"
            },
            {
                mData: "nombre_grupo_insumo",
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
    tablaFamiliasInsumo
        .on('select', function(e, dt, type, indexes) {
            var rowData = tablaFamiliasInsumo.rows(indexes).data().toArray();

            $("#idFamiliaInsumo").val(rowData[0].id_grupo_insumo);
            $("#nombreFamiliaInsumo").val(rowData[0].nombre_grupo_insumo);
            $('#ModalNuevaFamiliaInsumo').modal('show');
        })
}
</script>