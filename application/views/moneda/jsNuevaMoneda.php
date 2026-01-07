<!--  MODAL NUEVO REGISTRO  -->
<div id="ModalNuevaMoneda" class="modal fade" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModalUsuario" class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text text-center">REGISTRO MONEDAS
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
                                <label for="idMoneda">ID</label>
                                <input type="number" disabled class="form-control input-sm input-sm" name="idMoneda" id="idMoneda" requerido="no" value="" />
                            </div>
                            <div class="col-md-12 ">
                                <label for="nombreMoneda">NOMBRE MONEDA *</label>
                                <input type="text" class="form-control input-sm input-sm" maxlength='50' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="nombreMoneda" id="nombreMoneda" requerido="si" value="" placeholder="Nombre Moneda" />
                            </div>
                        </fieldset>
                        <!-- FIN CAMPOS DE REGISTRO  -->
                    </div>
                </div>
            </div>
            <!-- CONTENIDO  MODAL  -->

            <!-- BOTONES  -->
            <div class="modal-footer">
                <button onclick="grabarNuevaMoneda()" type="button" class="btn btn-primary"><i class=""></i>GRABAR</button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class=""></i>SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>
<!-- FIN  MODAL NUEVO REGISTRO  -->

<script>
    function AbrirModalNuevaMoneda() {
        $('#idMoneda').val("");
        $('#nombreMoneda').val("");
        $('#ModalNuevaMoneda').modal({
            backdrop: 'static',
            keyboard: false
        });
    }

    function grabarNuevaMoneda() {
        if ($('#nombreMoneda').val() == 0) {
            showAppAlert('error',"No Ingreso El Nombre De La Moneda!", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
        } else {
            idMoneda = $('#idMoneda').val();
            NombreMoneda = $('#nombreMoneda').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
            var miJson = {
                'id_moneda': idMoneda,
                'nombre_moneda': NombreMoneda,
            };
            console.log(miJson);
            $.post(base_url + "index.php/Moneda/grabarNuevaMoneda", {
                    'prod': miJson
                }).done(function() {
                    showAppAlert('success', "Se registro Exitosamente La Nueva Moneda!", {
                        duration: 4000,
                        image: base_url + 'assets/img/success.gif'
                    });
                    $('#ModalNuevaMoneda').modal('hide');
                    tablaMoneda.ajax.reload();
                })
                .fail(function() {
                    showAppAlert('error',"Se produjo un error al realizar el proceso, favor contacte a su administrador", {
                        duration: 5000,
                        image: base_url+ 'assets/img/errorGif.gif'
                    });
                })
                .always(function() {});
        }
    }
</script>
