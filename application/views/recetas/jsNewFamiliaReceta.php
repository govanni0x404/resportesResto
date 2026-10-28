<style>
	/* Formularios en modales (.t-form): filas flexibles en vez de floats + <br>, etiquetas claras y móvil a una columna */
	.t-form fieldset { display: flex; flex-wrap: wrap; align-items: flex-end; margin: 0 -8px; min-width: 0; }
	.t-form fieldset > * { flex: 0 0 100%; max-width: 100%; padding-left: 8px; padding-right: 8px; }
	.t-form fieldset > [class*="col-"] { float: none; flex: 0 0 auto; margin-bottom: 12px; }
	.t-form fieldset > .table-responsive, .t-form fieldset > .nav-tabs { margin-left: 8px; margin-right: 8px; max-width: calc(100% - 16px); padding-left: 0; padding-right: 0; }
	.t-form label { font-size: 12px; text-transform: uppercase; letter-spacing: .3px; color: var(--t-texto-suave); font-weight: 700; margin-bottom: 5px; }
	.t-form .checkbox label { text-transform: none; letter-spacing: 0; font-size: 13px; color: var(--t-texto); }
	.t-form label.t-card-titulo { display: block; margin: 4px 0 10px; padding-bottom: 6px; border-bottom: 1px solid var(--t-borde-suave); }
	.t-form .form-control.input-sm { height: 36px; font-size: 14px; }
	.t-form textarea, .t-form textarea.form-control { border: 1px solid var(--t-borde, #EADFD2); border-radius: var(--t-radio-sm); padding: 8px 10px; height: auto; }
	.t-form textarea:focus { outline: none; border-color: var(--t-primario); box-shadow: 0 0 0 3px rgba(180, 67, 46, .12); }
	.t-form sup { top: -.3em; }
	.t-form sup a { cursor: pointer; font-weight: 600; text-transform: none; color: var(--t-primario); margin-left: 4px; }
	.t-form .nav-tabs { margin-bottom: 14px; }
	.t-form .tab-pane > fieldset:first-child { margin-top: 2px; }
	.modal-footer .btn i[class=""] { display: none; }
	@media (max-width: 991px) {
		.t-form fieldset > [class*="col-md-"]:not([class*="col-sm-"]):not([class*="col-xs-"]) { width: 100%; }
		.t-form fieldset > .col-md-1:empty, .t-form fieldset > .col-md-2:empty, .t-form fieldset > .col-md-5:empty, .t-form fieldset > .col-md-8:empty { display: none; }
	}
</style>
<style>
	/* La pestaña vacía solo dejaba un espacio en blanco sobre el formulario */
	#ModalNuevaFamiliaReceta .nav-with { display: none; }
</style>
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
            <div class="modal-body t-form">
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
            showAppAlert('error', "Falta Ingresar Los Siguiente(s) dato(s):\n" + msg, {
                duration: 6000,
                image: base_url + 'assets/img/errorGif.gif'
            });
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
                    
                    showAppAlert('success', "Se registró exitosamente la nueva familia!", {
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