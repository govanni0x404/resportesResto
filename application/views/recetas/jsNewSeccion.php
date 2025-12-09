<div id="ModalNuevaSeccion" class="modal fade" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModal" class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text text-center">REGISTRO DE SECCIONES
                </h4>
            </div>
            <!-- FIN TITULO  MODAL  -->

            <!-- CONTENIDO  MODAL  -->
            <div class="modal-body">
                <div class="h-panel">
                    <ul id="myTab" class="nav nav-with title font">
                        <li class="active"><a href="#tabDatosUsuarios" data-toggle="tab" id="tab1" class="text color1"></a></li>
                    </ul>
                    <div class="tab-pane fade active in" id="tabDatosUsuarios">
                        <fieldset>
                            <div class="col-md-4 ">
                                <label for="IdSeccion">ID</label>
                                <input type="text" disabled class="form-control input-sm input-sm" name="IdSeccion" id="IdSeccion" requerido="no" value="">
                            </div>
                            <div class="col-md-12 ">
                                <label for="txtNombreSeccion">SECCION</label>
                                <input placeholder="Nombre Seccion" type="text" class="form-control input-sm input-sm" maxlength='28' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="txtNombreSeccion" id="txtNombreSeccion" requerido="si" value="" />
                            </div>
                        </fieldset>
                        <!-- FIN CAMPOS DE REGISTRO  -->
                    </div>
                </div>
            </div>
            <!-- CONTENIDO  MODAL  -->

            <!-- BOTONES  -->
            <div class="modal-footer">
                <button onclick="GrabarNuevaSeccion()" type="button" class="btn btn-primary"><i class="fa fa-check-square-o"></i>GRABAR SECCION</button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times-circle"></i>SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>

<script>
     function crearNuevaSeccion() {
        $('#IdSeccion').val("");
        $('#txtNombreSeccion').val("");
        $('#ModalNuevaSeccion').modal({
            backdrop: 'static',
            keyboard: false
        });
    }

    function GrabarNuevaSeccion() {
        var msg = "";
        if ($('#txtNombreSeccion').val() == 0 || $('#txtNombreSeccion').val().trim() == '') {
            msg = "Nombre Sección\n";
        }
        
        if (msg.length) {
            showAppAlert('error', "SFalta Ingresar Los Siguiente(s) dato(s):\n" + msg, {
                duration: 6000,
                image: base_url + 'assets/img/errorGif.gif'
            });
            return;
        }

        var IdSeccion = $('#IdSeccion').val();
        var NombreSeccion = $('#txtNombreSeccion').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
        
        var miJson = {
            'id_seccion': IdSeccion,
            'nombre_seccion': NombreSeccion
        };

        $.ajax({
            url: base_url + "index.php/Recetas/grabarNuevaSeccion",
            type: 'POST',
            data: {'prod': miJson},
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#ModalNuevaSeccion').modal('hide');
                    
                    // Recargar el combo de secciones
                    llenarSeccionReceta(response.data[0].id_seccion);
                    
                    showAppAlert('success', "Se registró exitosamente la nueva sección!", {
                        duration: 4000,
                        image: base_url + 'assets/img/success.gif'
                    });
                } else {
                    showAppAlert('error', response.message || "Error al guardar", {
                        duration: 6000,
                        image: base_url + 'assets/img/errorGif.gif'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                showAppAlert('error', "Se produjo un error al realizar el proceso", {
                    duration: 6000,
                    image: base_url + 'assets/img/errorGif.gif'
                });
            }
        });
    }
</script>