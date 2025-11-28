<script>
var base_url = "<?php echo base_url(); ?>";
tablaTipoEvento = $('#tablaTipoEvento').DataTable();

$(document).ready(function() {
    ListadoTposDeEventos();
});

function ListadoTposDeEventos() {
    if ($.fn.dataTable.isDataTable('#tablaTipoEvento')) {
        tablaTipoEvento = $('#tablaTipoEvento').DataTable();
        tablaTipoEvento.destroy();
    }
    tablaTipoEvento = $("#tablaTipoEvento").DataTable({
        "ajax": {
            "url": base_url + "index.php/TipoEvento/ListadoDeTipoEventos"
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
                mData: "id_tipo_evento",
                className: "text-center"
            },
            {
                mData: "nombre_tipo_evento",
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
                title: 'Listado De Tipo De Evento',
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
                title: 'Listado De Tipo De Evento',
                className: "btn btn-success"
            }
        ]
    });
    tablaTipoEvento
        .on('select', function(e, dt, type, indexes) {
            var rowData = tablaTipoEvento.rows(indexes).data().toArray();

            $("#idTipoEvento").val(rowData[0].id_tipo_evento);
            $("#nombreTipoEvento").val(rowData[0].nombre_tipo_evento);
            $('#ModalNuevoTipoEvento').modal('show');
        })
}
</script>