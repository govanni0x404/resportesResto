<script>
function cargarRoles(callback) {
    $.get(base_url + "index.php/Usuario/ListadoRoles", function(response) {
        if (typeof response === "string") {
            try {
                response = JSON.parse(response);
            } catch(e) {
                console.error("ERROR: la respuesta NO es JSON");
                return;
            }
        }

        console.log("Roles cargados en modal:", response);

        // Limpiar select
        $("#rolUsuario").empty();
        $("#rolUsuario").append('<option value="">Seleccione...</option>');

        // Cargar opciones
        response.forEach(function(r) {
            $("#rolUsuario").append(
                '<option value="' + r.id_rol + '">' + r.nombre_rol + '</option>'
            );
        });
        
        if (callback && typeof callback === 'function') {
            callback();
        }
    });
}
</script>

<!--  MODAL NUEVO REGISTRO  -->
<div id="ModalNuevoUsuario" class="modal fade" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModalUsuario" class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text text-center">REGISTRO DE USUARIOS
                </h4>
            </div>
            <!-- FIN TITULO  MODAL  -->

            <!-- CONTENIDO  MODAL  -->
            <div class="modal-body">
                <div class="h-panel">
                    <div class="tab-pane fade active in">
                        <!-- CAMPOS DE REGISTRO  -->
                        <fieldset>
                            <div class="col-md-12" id="divIdUsuario" style="display:none;">
                                <label for="idUsuario">ID</label>
                                <input type="number" disabled class="form-control input-sm" name="idUsuario" id="idUsuario" value="" />
                            </div>
                            <div class="col-md-12" id="divNombreUsuario">
                                <label for="nombreUsuario">NOMBRE USUARIO *</label>
                                <input type="text" class="form-control input-sm" maxlength='200' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="nombreUsuario" id="nombreUsuario" requerido="si" value="" placeholder="Nombre Usuario" />
                            </div>
                            <div class="col-md-12" id="divClaveUsuario">
                                <label for="claveUsuario">CLAVE USUARIO *</label>
                                <input type="password" class="form-control input-sm" maxlength='200' autocomplete="off" name="claveUsuario" id="claveUsuario" requerido="si" value="" placeholder="Clave Usuario" />
                            </div>
                            <div class="col-md-12" id="divVigente">
                                <label for="vigente">VIGENTE *</label>
                                <select class="form-control input-sm" name="vigente" id="vigente" requerido="si">
                                    <option value="true">SI</option>
                                    <option value="false">NO</option>
                                </select>
                            </div>
							<div class="col-md-12" id="divRolUsuario">
							    <label for="rolUsuario">ROL *</label>
    							<select class="form-control input-sm" name="rolUsuario" id="rolUsuario" requerido="si">
        							<option value="">Seleccione...</option>
    							</select>
							</div>
                        </fieldset>
                        <!-- FIN CAMPOS DE REGISTRO  -->
                    </div>
                </div>
            </div>
            <!-- CONTENIDO  MODAL  -->

            <!-- BOTONES  -->
            <div class="modal-footer">
                <button onclick="grabarNuevoUsuario()" type="button" class="btn btn-primary"><i class=""></i> GRABAR</button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class=""></i> SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>
<!-- FIN  MODAL NUEVO REGISTRO  -->

<script>
    var modoEdicion = false;

    function AbrirModalNuevoUsuario() {
        modoEdicion = false;
        $('#idUsuario').val("");
        $('#nombreUsuario').val("");
        $('#claveUsuario').val("");
        $('#vigente').val("true");
		
		cargarRoles();
    	$("#rolUsuario").val("");
        
        // Mostrar campos de creación
        $('#divNombreUsuario').show();
        $('#divClaveUsuario').show();
        $('#divIdUsuario').show();
        $('#divVigente').show();
        
        $('#tituloModalUsuario h4').text('REGISTRO DE USUARIOS');
        
        cargarRoles(function() {

            $("#rolUsuario").val("");

            $('#ModalNuevoUsuario').modal({
                backdrop: 'static',
                keyboard: false
            });
        });
    }

    function grabarNuevoUsuario() {
        if (modoEdicion) {
            var vigenteSeleccionado = $('#vigente').val();
			let nombre = $.trim($('#nombreUsuario').val());
    		let clave = $.trim($('#claveUsuario').val());
			var idRol = $("#rolUsuario").val();
            
            if (vigenteSeleccionado == "" || vigenteSeleccionado == null) {
                showAppAlert('error', "Debe seleccionar el estado de vigencia!", {
                    duration: 5000,
                    image: base_url + 'assets/img/errorGif.gif'
                });
                return;
            }

            var idUsuario = $('#idUsuario').val();

            var miJson = {
    			'id_usuario': idUsuario,
    			'nombre_usuario': $('#nombreUsuario').val(),
    			'clave_usuario': $('#claveUsuario').val(),
    			'vigente': vigenteSeleccionado,
				'id_rol': idRol
			};

            if(nombre === ""){
				showAppAlert('error', "El nombre de Usuario no puede quedar vacio.", {
                    duration: 5000,
                    image: base_url + 'assets/img/errorGif.gif'
                });
			}else if(clave === ""){
				showAppAlert('error', "La clave de Usuario no puede quedar vacia", {
                    duration: 5000,
                    image: base_url + 'assets/img/errorGif.gif'
                });
			}else{
				console.log('Datos a enviar:', miJson);
            
            	$.post(base_url + "index.php/Usuario/editarUsuario", {
                	'prod': miJson
                }).done(function(response) {
                    console.log('Respuesta del servidor:', response);
                    showAppAlert('success', "Se actualizó exitosamente el usuario!", {
                        duration: 4000,
                        image: base_url + 'assets/img/success.gif'
                    });
                    $('#ModalNuevoUsuario').modal('hide');
                    tablaUsuarios.ajax.reload();
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    console.error('Error:', textStatus, errorThrown);
                    showAppAlert('error', "Se produjo un error al realizar el proceso", {
                        duration: 5000,
                        image: base_url + 'assets/img/errorGif.gif'
                    });
                });
			}
        } else {
            // Modo creación
            if ($('#nombreUsuario').val() == "" || $('#nombreUsuario').val() == null) {
                showAppAlert('error', "Debe ingresar el nombre de usuario!", {
                    duration: 5000,
                    image: base_url + 'assets/img/errorGif.gif'
                });
                return;
            }
            
            if ($('#claveUsuario').val() == "" || $('#claveUsuario').val() == null) {
                showAppAlert('error', "Debe ingresar la clave de usuario!", {
                    duration: 5000,
                    image: base_url + 'assets/img/errorGif.gif'
                });
                return;
            }

			if ($("#rolUsuario").val() == "" || $("#rolUsuario").val() == null) {
			    showAppAlert('error', "Debe seleccionar un rol!", {
        			duration: 5000,
        			image: base_url + 'assets/img/errorGif.gif'
    			});
    		return;
			}

            var nombreUsuario = $('#nombreUsuario').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
            var claveUsuario = $('#claveUsuario').val();
			var vigenteUsuario = $('#vigente').val(); // "true" o "false"
			var idRol = $("#rolUsuario").val();

            var miJson = {
                'id_usuario': 0,
                'nombre_usuario': nombreUsuario,
                'clave_usuario': claveUsuario,
				'vigente': vigenteUsuario,
				'id_rol': idRol
            };

            console.log('Datos a enviar:', miJson);
            
            $.post(base_url + "index.php/Usuario/grabarNuevoUsuario", {
                    'prod': miJson
                }).done(function(response) {
                    console.log('Respuesta del servidor:', response);
                    showAppAlert('success', "Se creó exitosamente el nuevo usuario!", {
                        duration: 4000,
                        image: base_url + 'assets/img/success.gif'
                    });
                    $('#ModalNuevoUsuario').modal('hide');
                    tablaUsuarios.ajax.reload();
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    console.error('Error:', textStatus, errorThrown);
                    showAppAlert('error', "Se produjo un error al realizar el proceso", {
                        duration: 5000,
                        image: base_url + 'assets/img/errorGif.gif'
                    });
                });
        }
    }
</script>