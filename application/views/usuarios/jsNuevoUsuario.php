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
                        </fieldset>
                        <!-- FIN CAMPOS DE REGISTRO  -->
                    </div>
                </div>
            </div>
            <!-- CONTENIDO  MODAL  -->

            <!-- BOTONES  -->
            <div class="modal-footer">
                <button onclick="grabarNuevoUsuario()" type="button" class="btn btn-primary"><i class="fa fa-check-square-o"></i> GRABAR</button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times-circle"></i> SALIR</button>
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
        
        // Mostrar campos de creación
        $('#divNombreUsuario').show();
        $('#divClaveUsuario').show();
        $('#divIdUsuario').show();
        $('#divVigente').show();
        
        $('#tituloModalUsuario h4').text('REGISTRO DE USUARIOS');
        
        $('#ModalNuevoUsuario').modal({
            backdrop: 'static',
            keyboard: false
        });
    }

    function grabarNuevoUsuario() {
        if (modoEdicion) {
            var vigenteSeleccionado = $('#vigente').val();
			let nombre = $.trim($('#nombreUsuario').val());
    		let clave = $.trim($('#claveUsuario').val());
            
            if (vigenteSeleccionado == "" || vigenteSeleccionado == null) {
                showAppAlert('error', "- Debe seleccionar el estado de vigencia!", {
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
    			'vigente': vigenteSeleccionado
			};

            if(nombre === ""){
				showAppAlert('error', "- El nombre de Usuario no puede quedar vacio.", {
                    duration: 5000,
                    image: base_url + 'assets/img/errorGif.gif'
                });
			}else if(clave === ""){
				showAppAlert('error', "- La clave de Usuario no puede quedar vacia", {
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
                    showAppAlert('error', "- Se produjo un error al realizar el proceso", {
                        duration: 5000,
                        image: base_url + 'assets/img/errorGif.gif'
                    });
                });
			}
        } else {
            // Modo creación
            if ($('#nombreUsuario').val() == "" || $('#nombreUsuario').val() == null) {
                showAppAlert('error', "- Debe ingresar el nombre de usuario!", {
                    duration: 5000,
                    image: base_url + 'assets/img/errorGif.gif'
                });
                return;
            }
            
            if ($('#claveUsuario').val() == "" || $('#claveUsuario').val() == null) {
                showAppAlert('error', "- Debe ingresar la clave de usuario!", {
                    duration: 5000,
                    image: base_url + 'assets/img/errorGif.gif'
                });
                return;
            }

            var nombreUsuario = $('#nombreUsuario').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
            var claveUsuario = $('#claveUsuario').val();
			var vigenteUsuario = $('#vigente').val(); // "true" o "false"

            var miJson = {
                'id_usuario': 0,
                'nombre_usuario': nombreUsuario,
                'clave_usuario': claveUsuario,
				'vigente': vigenteUsuario
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
                    showAppAlert('error', "- Se produjo un error al realizar el proceso", {
                        duration: 5000,
                        image: base_url + 'assets/img/errorGif.gif'
                    });
                });
        }
    }
</script>