<script>
var base_url = "<?php echo base_url(); ?>";
tablaMesas = $('#tablaMesas').DataTable();

$(document).ready(function() {
    ListadoDeMesas();
});

function ListadoDeMesas() {
    if ($.fn.dataTable.isDataTable('#tablaMesas')) {
        tablaMesas = $('#tablaMesas').DataTable();
        tablaMesas.destroy();
    }
    tablaMesas = $("#tablaMesas").DataTable({
        "ajax": {
            "url": base_url + "index.php/Mesas/ListadoDeMesas"
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
                mData: "id_mesa",
                className: "text-center"
            },
            {
                mData: "disponible",
                className: "text-center",
                render: function(data) {
                    return data ? '<span class="label label-success">DISPONIBLE</span>' : '<span class="label label-danger">OCUPADA</span>';
                }
            },
            {
                mData: "vigente",
                className: "text-center",
                render: function(data) {
                    return data ? '<span class="label label-success">ACTIVO</span>' : '<span class="label label-danger">INACTIVO</span>';
                }
            }
        ],
        'language': {
            url: "https://cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json",
            select: {
                rows: {
                    _: " (Seleccionadas %d filas)",
                    0: " (Presione una línea a seleccionar)",
                    1: " (1 Fila seleccionada)"
                }
            }
        },
        "buttons": [
        ]
    });
    tablaMesas
        .on('select', function(e, dt, type, indexes) {
            var rowData = tablaMesas.rows(indexes).data().toArray();

            // Modo edición
            modoEdicion = true;
            
            $("#idMesa").val(rowData[0].id_mesa);
            
            // Convertir el valor booleano a string para el select
            var vigenteValue = rowData[0].vigente === true || rowData[0].vigente === 't' || rowData[0].vigente === 'true' ? 'true' : 'false';
            $("#vigente").val(vigenteValue);
            
            console.log('Vigente original:', rowData[0].vigente);
            console.log('Vigente convertido:', vigenteValue);
            
            // Mostrar campos de edición y ocultar cantidad
            $('#divCantidadMesas').hide();
            $('#divIdMesa').show();
            $('#divVigente').show();
            
            $('#tituloModalMesa h4').text('EDITAR MESA');
            
            $('#ModalNuevaMesa').modal('show');
        })
}
</script>