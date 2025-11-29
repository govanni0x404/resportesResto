<script>
    var base_url = "<?php echo base_url(); ?>";
    tablaSector = $('#tablaSector').DataTable();

    $(document).ready(function () {
        ListadoDeSectores();
    });

    function ListadoDeSectores() {
        if ($.fn.dataTable.isDataTable('#tablaSector')) {
            tablaSector = $('#tablaSector').DataTable();
            tablaSector.destroy();
        }
        tablaSector = $("#tablaSector").DataTable({
            "ajax": {
                "url": base_url + "index.php/Delivery/ListadoDeSectores"
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
                mData: "id_sector",
                className: "text-center"
            },
            {
                mData: "nombre_sector",
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
        tablaSector
            .on('select', function (e, dt, type, indexes) {
                var rowData = tablaSector.rows(indexes).data().toArray();

                $("#idSector").val(rowData[0].id_sector);
                $("#nombreSector").val(rowData[0].nombre_sector);
                $('#ModalNuevoSector').modal('show');
            })
    }
</script>