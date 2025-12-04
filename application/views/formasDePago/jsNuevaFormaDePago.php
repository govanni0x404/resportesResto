<div id="ModalNuevaFormaDePago" class="modal fade" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModal" class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text text-center">REGISTRO FORMAS DE PAGO
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
                        <!-- CAMPOS DE REGISTRO  -->
                        <fieldset>
                            <div class="col-md-4 ">
                                <label for="idFormaDePago">ID</label>
                                <input type="number" disabled class="form-control input-sm input-sm" name="idFormaDePago" id="idFormaDePago" requerido="no" value="">
                            </div>
                            <div class="col-md-10 ">
                                <label for="nombreFormaDePago">FORMA DE PAGO *</label>
                                <input type="text" class="form-control input-sm input-sm" maxlength='50' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="nombreFormaDePago" id="nombreFormaDePago" requerido="si" value="" placeholder="Nombre Forma De Pago" />
                            </div>
                        </fieldset>
                        <fieldset>
                            <div class="col-md-6">
                                <div class="btn-group" role="group" aria-label="...">
                                    <div class="checkbox checkbox-success checkbox-circle">
                                        <input id="ckEsCortesia" type="checkbox">
                                        <label for="ckEsCortesia">
                                            ES CORTESIA
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class=" btn-group" role="group" aria-label="...">
                                    <div class="checkbox checkbox-success checkbox-circle">
                                        <input id="ckBoletaElectronica" type="checkbox">
                                        <label for="ckBoletaElectronica">
                                            BOLETA ELECTRONICA
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                        <fieldset>
                            <div class="col-md-6">
                                <div class="btn-group" role="group" aria-label="...">
                                    <div class="checkbox checkbox-success checkbox-circle">
                                        <input id="ckVigente" type="checkbox">
                                        <label for="ckVigente">
                                            VIGENTE
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class=" btn-group" role="group" aria-label="...">
                                    <div class="checkbox checkbox-success checkbox-circle">
                                        <input id="ckPermitePropina" type="checkbox">
                                        <label for="ckPermitePropina">
                                            PERMITE PROPINA
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        <!-- FIN CAMPOS DE REGISTRO  -->
                    </div>
                </div>
            </div>
            <!-- CONTENIDO  MODAL  -->

            <!-- BOTONES  -->
            <div class="modal-footer">
                <button onclick="GrabarNuevaFormaDePago()" type="button" class="btn btn-primary"><i class="fa fa-check-square-o"></i>GRABAR</button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times-circle"></i>SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>
<script>
    function AbrirModalNuevaFormaDePago() {
        $('#idFormaDePago').val("");
        $('#nombreFormaDePago').val("");
        $('#ckEsCortesia').prop('checked', false);
        $('#ckBoletaElectronica').prop('checked', false);
        $('#ckVigente').prop('checked', true);
        $('#ckPermiteCortesia').prop('checked', false);
        $('#ModalNuevaFormaDePago').modal({
            backdrop: 'static',
            keyboard: false
        });
    }

    function GrabarNuevaFormaDePago() {
        if ($('#nombreFormaDePago').val() == 0) {
            showAppAlert('error',"- No Ingreso El Nombre De La Forma De Pago!", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
        } else {
            IdFormaDePago = $('#idFormaDePago').val();
            NombreFormaDePago = $('#nombreFormaDePago').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
            if ($('#ckEsCortesia').is(":checked")) {
                var EsCortesia = 1
            } else {
                var EsCortesia = 0
            }
            if ($('#ckBoletaElectronica').is(":checked")) {
                var BoletaElectronica = 1
            } else {
                var BoletaElectronica = 0
            }
            if ($('#ckVigente').is(":checked")) {
                var Vigente = 1
            } else {
                var Vigente = 0
            }
            if ($('#ckPermitePropina').is(":checked")) {
                var PermitePropina = 1
            } else {
                var PermitePropina = 0
            }
            var miJson = {
                'id_fpago': IdFormaDePago,
                'nombre_fpago': NombreFormaDePago,
                'escortesia': EsCortesia,
                'boleta_electronica': BoletaElectronica,
                'vigente': Vigente,
                'permite_propina': PermitePropina
            };
            $.post(base_url + "index.php/FormasDePago/grabarNuevaFormaDePago", {
                    'prod': miJson
                }).done(function() {
                    showAppAlert('success', "Se registro Exitosamente La Nueva Forma De Pago!", {
                        duration: 4000,
                        image: base_url + 'assets/img/success.gif'
                    });
                    $('#ModalNuevaFormaDePago').modal('hide');
                    tablaFormasDePago.ajax.reload();
                })
                .fail(function() {
                    showAppAlert('error',"- Se produjo un error al realizar el proceso, favor contacte a su administrador", {
                        duration: 5000,
                        image: base_url+ 'assets/img/errorGif.gif'
                    });
                })
                .always(function() {});
        }
    }
</script>
