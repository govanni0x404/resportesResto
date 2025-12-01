<script>
var base_url = "<?php echo base_url(); ?>";
var tablaUsuarios = $('#tablaUsuarios').DataTable();

$(document).ready(function() {
    ListadoDeUsuarios();
});

function ListadoDeUsuarios() {
    if ($.fn.dataTable.isDataTable('#tablaUsuarios')) {
        tablaUsuarios = $('#tablaUsuarios').DataTable();
        tablaUsuarios.destroy();
    }
    tablaUsuarios = $("#tablaUsuarios").DataTable({
        "ajax": {
            "url": base_url + "index.php/Usuario/ListadoDeUsuarios"
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
                mData: "id_rol",
                className: "text-center",
                render: function(data) {
                    var roles = {
                        1: 'ADMINISTRADOR',
                        2: 'SUPERVISOR',
                        3: 'CAJERO',
                        4: 'GARZON'
                    };
                    return roles[data] || 'ROL ' + data;
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
        "buttons": []
    });
    
    tablaUsuarios.on('select', function(e, dt, type, indexes) {
        var rowData = tablaUsuarios.rows(indexes).data().toArray();
        
        // Modo edición
        modoEdicion = true;
        
        $("#idUsuario").val(rowData[0].id_usuario);
        $("#nombreUsuario").val(rowData[0].nombre_usuario);
        $("#ClaveUsuario").val(rowData[0].clave_usuario);
        
        // Convertir el valor booleano a string para el select
        var vigenteValue = rowData[0].vigente === true || rowData[0].vigente === 't' || rowData[0].vigente === 'true' ? 'true' : 'false';
        $("#vigente").val(vigenteValue);
        
        // Mostrar campos de edición y ocultar creación
        $('#divNombreUsuario').show();
        $('#divClaveUsuario').show();
        $('#divIdUsuario').show();
        $('#divVigente').show();
        
        $('#tituloModalUsuario h4').text('EDITAR USUARIO');
        
        $('#ModalNuevoUsuario').modal('show');
    });
}
</script>