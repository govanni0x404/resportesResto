<div id="ModalNuevaFamiliaReceta" class="modal fade" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModal" class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text text-center">REGISTRO DE FAMILIAS
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
                                <label for="IdFamiliaReceta">ID</label>
                                <input type="text" disabled class="form-control input-sm input-sm" name="IdFamiliaReceta" id="IdFamiliaReceta" requerido="no" value="">
                            </div>
                            <div class="col-md-12 ">
                                <label for="txtNombreFamiliaReceta">FAMILIA RECETA</label>
                                <input placeholder="Nombre Familia Receta" type="text" class="form-control input-sm input-sm" maxlength='28' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="txtNombreFamiliaReceta" id="txtNombreFamiliaReceta" requerido="si" value="" />
                            </div>
                        </fieldset>
                        <br>
                        <!-- FIN CAMPOS DE REGISTRO  -->
                    </div>
                </div>
            </div>
            <!-- CONTENIDO  MODAL  -->

            <!-- BOTONES  -->
            <div class="modal-footer">
                <button onclick="GrabarNuevaFamiliaReceta()" type="button" class="btn btn-primary"><i class="fa fa-check-square-o"></i>GRABAR FAMILIA</button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times-circle"></i>SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>

<script>
     function crearNuevaFamiliaReceta() {
        $('#IdFamiliaReceta').val("");
        $('#txtNombreFamiliaReceta').val("");
        $('#ModalNuevaFamiliaReceta').modal({
            backdrop: 'static',
            keyboard: false
        });
    }

    function GrabarNuevaFamiliaReceta() {
        var msg = "";
        if ($('#txtNombreFamiliaReceta').val() == 0 || $('#txtNombreFamiliaReceta').val().trim() == '') {
            msg = "Nombre Familia\n";
        }
        
        if (msg.length) {
            swal("Error", "Falta Ingresar Los Siguiente(s) dato(s):\n" + msg, "error");
            return;
        }

        var IdFamiliaReceta = $('#IdFamiliaReceta').val();
        var NombreFamiliaReceta = $('#txtNombreFamiliaReceta').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
        
        var miJson = {
            'id_familia_receta': IdFamiliaReceta,
            'nombre_familia_receta': NombreFamiliaReceta
        };

        $.ajax({
            url: base_url + "index.php/Recetas/grabarNuevaFamiliaReceta",
            type: 'POST',
            data: {'prod': miJson},
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#ModalNuevaFamiliaReceta').modal('hide');
                    
                    // Recargar el combo de familias
                    var id_familia_actual = $('#cboGrupoReceta').val();
                    llenarFamiliaReceta(response.data[0].id_familia_receta);
                    
                    swal("Listo", "Se registró exitosamente la nueva familia", "success");
                } else {
                    swal("Error", response.message || "Error al guardar", "error");
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                swal("Error", "Se produjo un error al realizar el proceso", "error");
            }
        });
    }
</script>