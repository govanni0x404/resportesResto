<script>
var base_url = "<?php echo base_url(); ?>";
tablaTurnos = $('#tablaTurnos').DataTable();

$(document).ready(function() {
    ListadoDeTurnos();
});

function ListadoDeTurnos() {
    if ($.fn.dataTable.isDataTable('#tablaTurnos')) {
        tablaTurnos = $('#tablaTurnos').DataTable();
        tablaTurnos.destroy();
    }
    tablaTurnos = $("#tablaTurnos").DataTable({
        "ajax": {
            "url": base_url + "index.php/Turnos/ListadoDeTurnos"
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
                mData: "id_turno",
                className: "text-center"
            },
            {
                mData: "nombre_turno",
                className: "text-center"
            },
            {
                mData: "cierre_turno",
                className: "text-center"
            },
            {
                mData: "informe_z",
                className: "text-center"
            },
            {
                mData: "impedir_cierre_con_mesas_abiertas",
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
                    columns: [0, 1, 2, 3, 4]
                },
                extend: 'pdfHtml5',
                orientation: 'landscape',
                pageSize: 'LEGAL',
                title: 'Listado De Turnos',
                className: "btn btn-danger"
            },
            {
                text: 'EXCEL',
                extend: 'excel',
                exportOptions: {
                    modifier: {
                        selected: null
                    },
                    columns: [0, 1, 2, 3, 4]
                },
                title: 'Listado De Turnos',
                className: "btn btn-success"
            }
        ]
    });
    tablaTurnos
        .on('select', function(e, dt, type, indexes) {
            var rowData = tablaTurnos.rows(indexes).data().toArray();

            $("#idTurnos").val(rowData[0].id_turno);
            $("#nombreTurno").val(rowData[0].nombre_turno);
            if (rowData[0].cierre_turno == 'SI') {
                $('#ckCierreTurno').prop('checked', true);
            } else {
                $('#ckCierreTurno').prop('checked', false);
            }
            if (rowData[0].informe_z == 'SI') {
                $('#ckInformeZ').prop('checked', true);
            } else {
                $('#ckInformeZ').prop('checked', false);
            }
            if (rowData[0].impedir_cierre_con_mesas_abiertas == 'SI') {
                $('#ckImprimirCierreConMesasAbiertas').prop('checked', true);
            } else {
                $('#ckImprimirCierreConMesasAbiertas').prop('checked', false);
            }
            $('#ModalNuevoTurno').modal('show');
        })
}
</script>