<div id="ModalNuevoIngresoEvento" class="modal fade" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModalUsuario" class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text text-center">REGISTRO INGRESOS DE EVENTOS
                </h4>
            </div>
            <!-- FIN TITULO  MODAL  -->

            <!-- CONTENIDO  MODAL  -->
            <div class="modal-body">
                <div class="h-panel">
                    <ul id="myTab" class="nav nav-with title font">
                        <li class="active"><a href="#tabBodegas" data-toggle="tab" id="tab1" class="text color1"></a></li>
                    </ul>
                    <div class="tab-pane fade active in" id="tabBodegas">
                        <!-- CAMPOS DE REGISTRO  -->
                        <fieldset>
                            <div class="col-md-4 ">
                                <label for="idIngresoEvento">ID</label>
                                <input type="number" disabled class="form-control input-sm input-sm" name="idIngresoEvento" id="idIngresoEvento" requerido="no" value="" />
                            </div>
                            <div class="col-md-10 ">
                                <label for="nombreIngresoEvento">NOMBRE INGRESO EVENTO *</label>
                                <input type="text" class="form-control input-sm input-sm" maxlength='50' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="nombreIngresoEvento" id="nombreIngresoEvento" requerido="si" value="" placeholder="Nombre Ingreso De Evento" />
                            </div>
                        </fieldset>
                        <!-- FIN CAMPOS DE REGISTRO  -->
                    </div>
                </div>
            </div>
            <!-- CONTENIDO  MODAL  -->

            <!-- BOTONES  -->
            <div class="modal-footer">
                <button onclick="grabarNuevoIngresoEvento()" type="button" class="btn btn-primary"><i class=""></i>GRABAR</button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class=""></i>SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>
<!-- FIN  MODAL NUEVO REGISTRO  -->

<script>
    function AbrirModalNuevoIngresoEvento() {
        $('#idIngresoEvento').val("");
        $('#nombreIngresoEvento').val("");
        $('#ModalNuevoIngresoEvento').modal({
            backdrop: 'static',
            keyboard: false
        });
    }

    function grabarNuevoIngresoEvento() {
        if ($('#nombreIngresoEvento').val() == 0) {
            showAppAlert('error',"No Ingreso El nombre del Evento!", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
        } else {
            idIngresoEvento = $('#idIngresoEvento').val();
            NombreIngresoEvento = $('#nombreIngresoEvento').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
            var miJson = {
                'id_ingreso_evento': idIngresoEvento,
                'nombre_ingreso_evento': NombreIngresoEvento,
            };
            console.log(miJson);
            $.post(base_url + "index.php/IngresoEventos/grabarNuevoIngresoEvento", {
                    'prod': miJson
                }).done(function() {
                    showAppAlert('success', "Se registro Exitosamente El Nuevo Evento!", {
                        duration: 4000,
                        image: base_url + 'assets/img/success.gif'
                    });
                    $('#ModalNuevoIngresoEvento').modal('hide');
                    tablaIngresosEvento.ajax.reload();
                })
                .fail(function() {
                    swal("Se produjo un error al realizar el proceso, favor contacte a su administrador", "error");
                })
                .always(function() {});
        }
    }
</script>
