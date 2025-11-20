<script>
var base_url = "<?php echo base_url(); ?>";
tablaFormasDePago = $('#tablaFormasDePago').DataTable();

$(document).ready(function() {
    ListadoDeFormasDePago();
});

function ListadoDeFormasDePago() {
    if ($.fn.dataTable.isDataTable('#tablaFormasDePago')) {
        tablaFormasDePago = $('#tablaFormasDePago').DataTable();
        tablaFormasDePago.destroy();
    }
    tablaFormasDePago = $("#tablaFormasDePago").DataTable({
        "ajax": {
            "url": base_url + "index.php/FormasDePago/ListadoDeFormasDePago"
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
                mData: "id_fpago",
                className: "text-center"
            },
            {
                mData: "nombre_fpago",
                className: "text-center"
            },
            {
                mData: "escortesia",
                className: "text-center"
            },
            {
                mData: "boleta_electronica",
                className: "text-center"
            },
            {
                mData: "vigente",
                className: "text-center"
            },
            {
                mData: "permite_propina",
                className: "text-center"
            },

        ],
        'language': {
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json",
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
                    columns: [0, 1, 2, 3, 4, 5]
                },
                extend: 'pdfHtml5',
                orientation: 'landscape',
                pageSize: 'LEGAL',
                title: 'Listado De Formas De Pago',
                className: "btn btn-danger"
            },
            {
                text: 'EXCEL',
                extend: 'excel',
                exportOptions: {
                    modifier: {
                        selected: null
                    },
                    columns: [0, 1, 2, 3, 4, 5]
                },
                title: 'Listado De Formas De Pago',
                className: "btn btn-success"
            }
        ]
    });
    tablaFormasDePago
        .on('select', function(e, dt, type, indexes) {
            var rowData = tablaFormasDePago.rows(indexes).data().toArray();

            $("#idFormaDePago").val(rowData[0].id_fpago);
            $("#nombreFormaDePago").val(rowData[0].nombre_fpago);
            if (rowData[0].escortesia == 'SI') {
                $('#ckEsCortesia').prop('checked', true);
            } else {
                $('#ckEsCortesia').prop('checked', false);
            }

            if (rowData[0].boleta_electronica == 'SI') {
                $('#ckBoletaElectronica').prop('checked', true);
            } else {
                $('#ckBoletaElectronica').prop('checked', false);
            }

            if (rowData[0].vigente == 'SI') {
                $('#ckVigente').prop('checked', true);
            } else {
                $('#ckVigente').prop('checked', false);
            }

            if (rowData[0].permite_propina == 'SI') {
                $('#ckPermitePropina').prop('checked', true);
            } else {
                $('#ckPermitePropina').prop('checked', false);
            }
            $('#ModalNuevaFormaDePago').modal('show');
        })
}
</script>