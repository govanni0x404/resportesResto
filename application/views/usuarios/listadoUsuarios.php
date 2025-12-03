<script>
var base_url = "<?php echo base_url(); ?>";
var tablaUsuarios = $('#tablaUsuarios').DataTable();

$(document).ready(function() {
    ListadoDeUsuarios();
});

    function showTableLoader(message) {
    if ($('#dynamic-table-loader').length) {
        $('#dynamic-table-loader').show();
        return;
    }
    
    var loaderHtml = 
        '<div id="dynamic-table-loader" style="' +
        'position: absolute; top: 0; left: 0; right: 0; bottom: 0; ' +
        'background: rgba(255, 255, 255, 0.95); ' +
        'display: flex; flex-direction: column; align-items: center; justify-content: center; ' +
        'z-index: 100; gap: 12px;">' +
        '<div style="width: 40px; height: 40px; border: 4px solid rgba(0,123,255,0.2); ' +
        'border-top-color: #007bff; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>' +
        '<div style="color: #333; font-size: 14px;">' + (message || 'Cargando...') + '</div>' +
        '</div>';
    
    var $wrapper = $('#tablaUsuarios').closest('.dataTables_wrapper');
    if ($wrapper.length === 0) {
        $wrapper = $('#tablaUsuarios').parent();
    }
    
    $wrapper.css('position', 'relative');
    $wrapper.append(loaderHtml);
    }   

    function hideTableLoader() {
        $('#dynamic-table-loader').fadeOut(500, function() {
            $(this).remove();
        });
    }

function ListadoDeUsuarios() {
    if ($.fn.dataTable.isDataTable('#tablaUsuarios')) {
        tablaUsuarios = $('#tablaUsuarios').DataTable();
        tablaUsuarios.destroy();
    }
    tablaUsuarios = $("#tablaUsuarios").DataTable({
        "ajax": {
            "url": base_url + "index.php/Usuario/ListadoDeUsuarios",
            "data": {
            },
            beforeSend: function () {
                showTableLoader("Cargando Usuarios...");
            },
            complete: function () {
                hideTableLoader();
            },
            error: function () {
                hideTableLoader();
            }
        },
        "bPaginate": true,
        "bProcessing": false,
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
                className: "text-left"
            },
            {
                mData: "id_rol",
                className: "text-left",
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
                    var vigente = (data === true || data === "true" || data === "t" || data === 1);
                    if (vigente) {
                        return '<span class="label label-success">ACTIVO</span>';
                    } else {
                        return '<span class="label label-danger">INACTIVO</span>';
                    }
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
        
        cargarRoles();
        setTimeout(function() {
            $("#rolUsuario").val(rowData[0].id_rol);
        }, 200);

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