<!--  MODAL NUEVO REGISTRO  -->
<div id="ModalNuevaUmedida" class="modal fade t-modal-form" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModalUsuario" class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text text-center">REGISTRO UNIDAD DE MEDIDA
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
                            <div class="col-sm-3">
                                <label for="idUmedida">ID</label>
                                <input type="number" disabled class="form-control input-sm input-sm" name="idUmedida" id="idUmedida" requerido="no" value="" />
                            </div>
                            <div class="col-sm-9">
                                <label for="nombreUmedida">UNIDAD DE MEDIDA *</label>
                                <input type="text" class="form-control input-sm input-sm" maxlength='50' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="nombreUmedida" id="nombreUmedida" requerido="si" value="" placeholder="Nombre Unidad De Medida" />
                            </div>
                        </fieldset>
                        <!-- FIN CAMPOS DE REGISTRO  -->
                    </div>
                </div>
            </div>
            <!-- CONTENIDO  MODAL  -->

            <!-- BOTONES  -->
            <div class="modal-footer">
                <button onclick="grabarNuevaUmedida()" type="button" class="btn btn-primary"><i class="fa fa-check"></i> GRABAR</button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>
<!-- FIN  MODAL NUEVO REGISTRO  -->

<script>
    var originUmedida = "";
    function AbrirModalNuevaUmedida(origin) {
        originUmedida = origin || "";
        $('#idUmedida').val("");
        $('#nombreUmedida').val("");
        $('#ModalNuevaUmedida').modal({
            backdrop: 'static',
            keyboard: false
        });
    }

    function grabarNuevaUmedida() {
        if ($('#nombreUmedida').val() == 0) {
            showAppAlert('error',"No Ingreso El Nombre De La Unidad De Medida!", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
        } else {
            idUmedida = $('#idUmedida').val();
            NombreUmedida = $('#nombreUmedida').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
            var miJson = {
                'id_umedida': idUmedida,
                'nombre_umedida': NombreUmedida,
            };
            console.log(miJson);
            $.post(base_url + "index.php/Umedida/grabarNuevaUmedida", {
                    'prod': miJson
                }).done(function(response) {
                    showAppAlert('success', "Se registro Exitosamente La Nueva Unidad De Medida!", {
                        duration: 4000,
                        image: base_url + 'assets/img/success.gif'
                    });
                    $('#ModalNuevaUmedida').modal('hide');
                    
                    if (typeof ListarUmedidas === 'function') {
						ListarUmedidas(NombreUmedida);
					}
                    
                    if (typeof ListadoDeUmedida === 'function') {
						ListadoDeUmedida(NombreUmedida);
					}

                    if (typeof llenarCboUnMedidaModalReceta === 'function') {
                        llenarCboUnMedidaModalReceta(NombreUmedida);
                    }

                    if (typeof llenarCboUnMedida === 'function') {
                        llenarCboUnMedida(NombreUmedida);
                    }
					
					if (typeof llenarUnMedida === 'function') {
                        var selInv = $("#cboUnMedInventario option:selected").attr("nombreUm");
                        var selRec = $("#cboUnMedReceta option:selected").text();

                        if (originUmedida === 'inventario') {
                            selInv = NombreUmedida;
                        } else if (originUmedida === 'receta') {
                            selRec = NombreUmedida;
                        }

						llenarUnMedida(selInv, selRec);
					}

                    if (typeof cargarUnidadesMedidaParaReceta === 'function') {
                        cargarUnidadesMedidaParaReceta(NombreUmedida);
                    }
					
					if (typeof tablaUmedida !== 'undefined') {
						tablaUmedida.ajax.reload();
                    }
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
