<script>
var base_url = "<?php echo base_url(); ?>";
var tablaUsuariosPrivilegios = $('#tablaUsuariosPrivilegios').DataTable();

$(document).ready(function() {
    ListadoDeUsuariosPrivilegios();
});

function ListadoDeUsuariosPrivilegios() {
    if ($.fn.dataTable.isDataTable('#tablaUsuariosPrivilegios')) {
        tablaUsuariosPrivilegios = $('#tablaUsuariosPrivilegios').DataTable();
        tablaUsuariosPrivilegios.destroy();
    }
    
    tablaUsuariosPrivilegios = $("#tablaUsuariosPrivilegios").DataTable({
        "ajax": {
            "url": base_url + "index.php/PrivilegiosUsuario/ListadoDeUsuarios"
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
                mData: "id_usuario",
                className: "text-center"
            },
            {
                mData: "nombre_usuario",
                className: "text-center"
            },
            {
                mData: "nombre_rol",
                className: "text-center"
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
    
    tablaUsuariosPrivilegios.on('select', function(e, dt, type, indexes) {
        var rowData = tablaUsuariosPrivilegios.rows(indexes).data().toArray();
        var id_usuario = rowData[0].id_usuario;
        var nombre_usuario = rowData[0].nombre_usuario;
        var id_rol = rowData[0].id_rol;
        
        mostrarPrivilegiosUsuario(id_usuario, nombre_usuario, id_rol);
    });
}
</script>