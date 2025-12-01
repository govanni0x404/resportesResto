<script>
function mostrarPrivilegiosUsuario(id_usuario, nombre_usuario, id_rol) {
    $('#id_rol_selected').val(id_rol);
    $('#nombreUsuario').text('PRIVILEGIOS DE: ' + nombre_usuario);
    
    $.post(base_url + "index.php/PrivilegiosUsuario/ListarPrivilegiosDeUsuario", {
        id_usuario: id_usuario
    }, function(datos) {
        var obj = jQuery.parseJSON(datos);
        
        var tbl = "";
        $.each(obj, function(index, v) {
            var bgColor = v.autoriza == 't' || v.autoriza == true ? '#5995f0' : '#eb5757';
            var checked = v.autoriza == 't' || v.autoriza == true ? 'checked' : '';
            
            tbl += "<tr style='height: 10px; background-color:" + bgColor + ";'>";
            tbl += "<td class='text-center' style='display:none'>" + v.id_pusuario + "</td>";
            tbl += "<td class='text-center text-white' style='height:45px'>" + v.nombre_psistema + "</td>";
            tbl += "<td class='text-center text-white' style='height:45px'>" + v.nombre_vb + "</td>";
            tbl += "<td class='text-center'><input type='checkbox' class='privilegio-check' data-id='" + v.id_pusuario + "' " + checked + " style='width: 20px; height: 20px;'></td>";
            tbl += "</tr>";
        });
        
        $('#detallePrivilegios').html(tbl);
        
        // Evento para checkboxes
        $('.privilegio-check').on('change', function() {
            var id_pusuario = $(this).data('id');
            var autoriza = $(this).is(':checked');
            actualizarPrivilegio(id_pusuario, autoriza, id_usuario, nombre_usuario, id_rol);
        });
        
        $('#modalPrivilegiosUsuario').modal({
            backdrop: 'static',
            keyboard: false
        });
    }).fail(function() {
        showAppAlert('error', "Error al cargar privilegios", {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
    });
}

function actualizarPrivilegio(id_pusuario, autoriza, id_usuario, nombre_usuario, id_rol) {
    var miJson = {
        'id_pusuario': id_pusuario,
        'autoriza': autoriza
    };
    
    $.post(base_url + "index.php/PrivilegiosUsuario/actualizarPrivilegio", {
        'prod': miJson
    }).done(function(response) {
        console.log('Privilegio actualizado:', response);
        // Recargar la lista para actualizar colores
        mostrarPrivilegiosUsuario(id_usuario, nombre_usuario, id_rol);
    }).fail(function() {
        showAppAlert('error', "Error al actualizar privilegio", {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
    });
}

$('#btnHabilitarTodos').on('click', function() {
    var id_rol = $('#id_rol_selected').val();
    
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
                $('#modalPrivilegiosUsuario').modal('hide');
            }).fail(function() {
                swal("Error", "No se pudieron habilitar los privilegios", "error");
            });
        }
    });
});

$('#btnBloquearTodos').on('click', function() {
    var id_rol = $('#id_rol_selected').val();
    
    swal({
        title: "¿Está seguro?",
        text: "Se bloquearán todos los privilegios para este rol",
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
                swal("¡Bloqueado!", "Todos los privilegios han sido bloqueados", "success");
                $('#modalPrivilegiosUsuario').modal('hide');
            }).fail(function() {
                swal("Error", "No se pudieron bloquear los privilegios", "error");
            });
        }
    });
});
</script>