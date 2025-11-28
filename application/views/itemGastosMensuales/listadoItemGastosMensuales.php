<script>
    var base_url = "<?php echo base_url(); ?>";
    tablaItemGastosMensuales = $('#tablaItemGastosMensuales').DataTable();

    $(document).ready(function() {
        ListadoDeItemGastoMensual();
    });

    function ListadoDeItemGastoMensual() {
        if ($.fn.dataTable.isDataTable('#tablaItemGastosMensuales')) {
            tablaItemGastosMensuales = $('#tablaItemGastosMensuales').DataTable();
            tablaItemGastosMensuales.destroy();
        }
        tablaItemGastosMensuales = $("#tablaItemGastosMensuales").DataTable({
            "ajax": {
                "url": base_url + "index.php/ItemGastosMensuales/ListadoDeItemGastosMensuales"
            },
            "bPaginate": true,
            "bProcessing": true,
            "pageLength": 10,
            "select": true,
            "dom": "Bfrtip",
            "ordering": false,
            "responsive": {
                details: {
                    type: 'column',
                    target: -1
                }
            },
            "columns": [{
                    mData: "id_item_gastos",
                    className: "text-center"
                },
                {
                    mData: "nombre_item_gastos",
                    className: "text-center"
                },
                {
                    mData: "vigente",
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
                    title: 'Listado De Item Gastos Mensuales',
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
                    title: 'Listado De Item Gastos Mensuales',
                    className: "btn btn-success"
                }
            ]
        });
        tablaItemGastosMensuales
            .on('select', function(e, dt, type, indexes) {
                var rowData = tablaItemGastosMensuales.rows(indexes).data().toArray();

                $("#idItemGastoMensual").val(rowData[0].id_item_gastos);
                $("#nombreItemGastoMensual").val(rowData[0].nombre_item_gastos);
                if (rowData[0].vigente == 'SI') {
                    $('#ckVigente').prop('checked', true);
                } else {
                    $('#ckVigente').prop('checked', false);
                }
                $('#ModalNuevoItemGastoMensual').modal('show');
            })
    }
</script>