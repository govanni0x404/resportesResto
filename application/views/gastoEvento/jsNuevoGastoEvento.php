<!--  MODAL NUEVO REGISTRO  -->
<div id="ModalNuevoGastoEvento" class="modal fade" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModalUsuario" class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text text-center">REGISTRO GASTOS DE EVENTOS
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
                                <label for="idGastoEvento">ID</label>
                                <input type="number" disabled class="form-control input-sm input-sm" name="idGastoEvento" id="idGastoEvento" requerido="no" value="" />
                            </div>
                            <div class="col-md-10 ">
                                <label for="nombreGastoEvento">NOMBRE GASTO EVENTO *</label>
                                <input type="text" class="form-control input-sm input-sm" maxlength='50' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="nombreGastoEvento" id="nombreGastoEvento" requerido="si" value="" placeholder="Nombre Gasto Evento" />
                            </div>
                        </fieldset>
                        <!-- FIN CAMPOS DE REGISTRO  -->
                    </div>
                </div>
            </div>
            <!-- CONTENIDO  MODAL  -->

            <!-- BOTONES  -->
            <div class="modal-footer">
                <button onclick="grabarNuevoGastoEvento()" type="button" class="btn btn-primary"><i class="fa fa-check-square-o"></i>GRABAR</button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times-circle"></i>SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>
<!-- FIN  MODAL NUEVO REGISTRO  -->

<script>
    function AbrirModalNuevoGastoEvento() {
        $('#idGastoEvento').val("");
        $('#nombreGastoEvento').val("");
        $('#ModalNuevoGastoEvento').modal({
            backdrop: 'static',
            keyboard: false
        });
    }

    function grabarNuevoGastoEvento() {
        if ($('#nombreGastoEvento').val() == 0) {
            swal("ERROR!", "No Ingreso El Nombre Del Gasto Evento!", "error");
        } else {
            idGastoEvento = $('#idGastoEvento').val();
            NombreGastoEvento = $('#nombreGastoEvento').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
            var miJson = {
                'id_gasto_evento': idGastoEvento,
                'nombre_gasto_evento': NombreGastoEvento,
            };
            console.log(miJson);
            $.post(base_url + "index.php/GastoEventos/grabarNuevoGastoEvento", {
                    'prod': miJson
                }).done(function() {
                    swal("Listo", "Se registro Exitosamente El Gasto Evento", "success");
                    $('#ModalNuevoGastoEvento').modal('hide');
                    tablaGastoEvento.ajax.reload();
                })
                .fail(function() {
                    swal("Se produjo un error al realizar el proceso, favor contacte a su administrador", "error");
                })
                .always(function() {});
        }
    }
</script>
