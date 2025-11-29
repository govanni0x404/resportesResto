<script>
var base_url = "<?php echo base_url(); ?>";
var tablaMesasSinAsignar = $('#tablaMesasSinAsignar').DataTable();

$(document).ready(function() {
    ListadoDeMesasSinAsignar();
});

function ListadoDeMesasSinAsignar() {
    if ($.fn.dataTable.isDataTable('#tablaMesasSinAsignar')) {
        tablaMesasSinAsignar = $('#tablaMesasSinAsignar').DataTable();
        tablaMesasSinAsignar.destroy();
    }
    tablaMesasSinAsignar = $("#tablaMesasSinAsignar").DataTable({
        "ajax": {
            "url": base_url + "index.php/MesasPorRecinto/ListadoDeMesasSinAsignar"
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
                mData: "id_recinto",
                className: "text-center",
                render: function(data) {
                    return data == 0 ? '<span class="label label-warning">SIN ASIGNAR</span>' : '<span class="label label-info">RECINTO ' + data + '</span>';
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
        "buttons": []
    });
    
    tablaMesasSinAsignar.on('select', function(e, dt, type, indexes) {
        var rowData = tablaMesasSinAsignar.rows(indexes).data().toArray();
        
        var idMesa = rowData[0].id_mesa;
        var posCalculado = idMesa - 1;
        
        $("#idMesaAsignar").val(idMesa);
        $("#posCalculado").val(posCalculado);
        $("#recintoSelect").val("0");
        
        console.log('Mesa seleccionada:', idMesa, 'Pos calculado:', posCalculado);
        
        $('#ModalAsignarMesa').modal('show');
    });
}
</script>