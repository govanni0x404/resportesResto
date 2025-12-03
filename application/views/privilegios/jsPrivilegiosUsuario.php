<script>
var base_url = "<?php echo base_url(); ?>";

$(document).ready(function() {
    cargarRoles();
});

function cargarRoles() {
    $.post(base_url + "index.php/PrivilegiosUsuario/ListadoDeRoles")
        .done(function(response) {
            var roles = JSON.parse(response);
            var select = $('#selectRol');
            select.empty();
            select.append('<option value="0">-- Seleccione un Rol --</option>');
            
            roles.forEach(function(rol) {
                select.append('<option value="' + rol.id_rol + '">' + rol.nombre_rol + '</option>');
            });
        })
        .fail(function() {
            showAppAlert('error', "Error al cargar los roles", {
                duration: 5000,
                image: base_url + 'assets/img/errorGif.gif'
            });
        });
}

$('#selectRol').on('change', function() {
    var id_rol = $(this).val();
    
    if (id_rol == "0") {
        $('#divPrivilegios').hide();
        return;
    }
    
    $('#id_rol_selected').val(id_rol);
    mostrarPrivilegiosPorRol(id_rol);
});

function mostrarPrivilegiosPorRol(id_rol) {
    $.post(base_url + "index.php/PrivilegiosUsuario/ListarPrivilegiosPorRol", {
        id_rol: id_rol
    }, function(datos) {
        var obj = JSON.parse(datos);
        
        if (obj.length == 0) {
            showAppAlert('warning', "No hay módulos disponibles", {
                duration: 3000,
                image: base_url + 'assets/img/errorGif.gif'
            });
            $('#divPrivilegios').hide();
            return;
        }
        
        var tbl = "";
        $.each(obj, function(index, v) {
            var autoriza = v.autoriza == 't' || v.autoriza == true;
            var bgColor = autoriza ? '#' : '#';
            var checked = autoriza ? 'checked' : '';
            var id_pusuario = v.id_pusuario ? v.id_pusuario : 0;
            
            tbl += "<tr style='background-color:" + bgColor + ";'>";
            tbl += "<td class='text-center' style='display:none'>" + id_pusuario + "</td>";
            tbl += "<td class='text-center' style='display:none'>" + v.id_psistema + "</td>";
            tbl += "<td class='text-center' style='padding: 10px; font-weight: bold;'>" + id_pusuario + "</td>";
            tbl += "<td class='text-left' style='padding: 10px;'>" + v.nombre_psistema + "</td>";
            tbl += "<td class='text-center'><input type='checkbox' class='privilegio-check' data-id-pusuario='" + id_pusuario + "' data-id-psistema='" + v.id_psistema + "' data-id-rol='" + id_rol + "' " + checked + " style='width: 20px; height: 20px; cursor: pointer;'></td>";
            tbl += "</tr>";
        });
        
        $('#detallePrivilegios').html(tbl);
        $('#divPrivilegios').show();
        
        // Evento para checkboxes
        $('.privilegio-check').on('change', function() {
            var id_pusuario = $(this).data('id-pusuario');
            var id_psistema = $(this).data('id-psistema');
            var id_rol = $(this).data('id-rol');
            var autoriza = $(this).is(':checked');
            
            if (id_pusuario == 0) {
                // No existe el registro, hay que crearlo
                crearPrivilegio(id_rol, id_psistema, autoriza);
            } else {
                // Ya existe, solo actualizar
                actualizarPrivilegio(id_pusuario, autoriza, id_rol);
            }
        });
        
    }).fail(function() {
        showAppAlert('error', "Error al cargar privilegios", {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
    });
}

function crearPrivilegio(id_rol, id_psistema, autoriza) {
    var miJson = {
        'id_rol': id_rol,
        'id_psistema': id_psistema,
        'autoriza': autoriza
    };
    
    $.post(base_url + "index.php/PrivilegiosUsuario/crearPrivilegio", {
        'prod': miJson
    }).done(function(response) {
        console.log('Privilegio creado:', response);
        mostrarPrivilegiosPorRol(id_rol);
    }).fail(function() {
        showAppAlert('error', "Error al crear privilegio", {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
    });
}

function actualizarPrivilegio(id_pusuario, autoriza, id_rol) {
    var miJson = {
        'id_pusuario': id_pusuario,
        'autoriza': autoriza
    };
    
    $.post(base_url + "index.php/PrivilegiosUsuario/actualizarPrivilegio", {
        'prod': miJson
    }).done(function(response) {
        console.log('Privilegio actualizado:', response);
        mostrarPrivilegiosPorRol(id_rol);
    }).fail(function() {
        showAppAlert('error', "Error al actualizar privilegio", {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
    });
}

$('#btnHabilitarTodos').on('click', function() {
    var id_rol = $('#id_rol_selected').val();
    
    if (id_rol == "0") {
        showAppAlert('warning', "Seleccione un rol primero", {
            duration: 3000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }
    
    swal({
        title: "¿Está seguro?",
        text: "Se habilitarán todos los privilegios para este rol",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#5cb85c",
        confirmButtonText: "Sí, habilitar",
        cancelButtonText: "Cancelar",
        closeOnConfirm: false
    }, function(isConfirm) {
        if (isConfirm) {
            $.post(base_url + "index.php/PrivilegiosUsuario/HabilitarTodosLosPrivilegios", {
                'id_rol': id_rol
            }).done(function() {
                swal("¡Habilitado!", "Todos los privilegios han sido habilitados", "success");
                mostrarPrivilegiosPorRol(id_rol);
            }).fail(function() {
                swal("Error", "No se pudieron habilitar los privilegios", "error");
            });
        }
    });
});

$('#btnBloquearTodos').on('click', function() {
    var id_rol = $('#id_rol_selected').val();
    
    if (id_rol == "0") {
        showAppAlert('warning', "Seleccione un rol primero", {
            duration: 3000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }
    
    swal({
        title: "¿Está seguro?",
        text: "Se Deshabilitaran todos los privilegios para este rol",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d9534f",
        confirmButtonText: "Sí, bloquear",
        cancelButtonText: "Cancelar",
        closeOnConfirm: false
    }, function(isConfirm) {
        if (isConfirm) {
            $.post(base_url + "index.php/PrivilegiosUsuario/BloquearTodosLosPrivilegios", {
                'id_rol': id_rol
            }).done(function() {
                swal("¡Bloqueado!", "Todos los privilegios han sido Deshabilitados", "success");
                mostrarPrivilegiosPorRol(id_rol);
            }).fail(function() {
                swal("Error", "No se pudieron Deshabilitar los privilegios", "error");
            });
        }
    });
});
</script>