<!--  MODAL NUEVO REGISTRO  -->
<div id="ModalNuevaMesa" class="modal fade" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModalMesa" class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text text-center">REGISTRO MESAS
                </h4>
            </div>
            <!-- FIN TITULO  MODAL  -->

            <!-- CONTENIDO  MODAL  -->
            <div class="modal-body">
                <div class="h-panel">
                    <ul id="myTab" class="nav nav-with title font">
                        <li class="active"><a href="#tabMesas" data-toggle="tab" id="tab1" class="text color1"></a></li>
                    </ul>
                    <div class="tab-pane fade active in" id="tabMesas">
                        <!-- CAMPOS DE REGISTRO  -->
                        <fieldset>
                            <div class="col-md-12" id="divIdMesa" style="display:none;">
                                <label for="idMesa">ID MESA</label>
                                <input type="number" disabled class="form-control input-sm" name="idMesa" id="idMesa" requerido="no" value="" />
                            </div>
                            <div class="col-md-12" id="divCantidadMesas">
                                <label for="cantidadMesas">CANTIDAD DE MESAS A CREAR *</label>
                                <input type="number" class="form-control input-sm" name="cantidadMesas" id="cantidadMesas" requerido="si" value="1" min="1" placeholder="Ingrese cantidad de mesas" />
                            </div>
                            <div class="col-md-12" id="divVigente" style="display:none;">
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
                <button onclick="grabarNuevaMesa()" type="button" class="btn btn-primary"><i class="fa fa-check-square-o"></i> GRABAR</button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times-circle"></i> SALIR</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>
<!-- FIN  MODAL NUEVO REGISTRO  -->

<script>
    var modoEdicion = false;

    function AbrirModalNuevaMesa() {
        modoEdicion = false;
        $('#idMesa').val("");
        $('#cantidadMesas').val("1");
        $('#vigente').val("true");
        
        // Mostrar campo de cantidad y ocultar otros
        $('#divCantidadMesas').show();
        $('#divIdMesa').hide();
        $('#divVigente').hide();
        
        $('#tituloModalMesa h4').text('CREAR NUEVAS MESAS');
        
        $('#ModalNuevaMesa').modal({
            backdrop: 'static',
            keyboard: false
        });
    }

    function grabarNuevaMesa() {
        if (modoEdicion) {
            // Modo edición - actualizar una sola mesa
            var vigenteSeleccionado = $('#vigente').val();
            
            if (vigenteSeleccionado == "" || vigenteSeleccionado == null) {
                showAppAlert('error', "- Debe seleccionar el estado de vigencia!", {
                    duration: 5000,
                    image: base_url + 'assets/img/errorGif.gif'
                });
                return;
            }

            var idMesa = $('#idMesa').val();

            var miJson = {
                'id_mesa': idMesa,
                'vigente': vigenteSeleccionado
            };

            console.log('Datos a enviar:', miJson);
            
            $.post(base_url + "index.php/Mesas/editarMesa", {
                    'prod': miJson
                }).done(function(response) {
                    console.log('Respuesta del servidor:', response);
                    showAppAlert('success', "Se actualizó exitosamente la mesa!", {
                        duration: 4000,
                        image: base_url + 'assets/img/success.gif'
                    });
                    $('#ModalNuevaMesa').modal('hide');
                    tablaMesas.ajax.reload();
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    console.error('Error:', textStatus, errorThrown);
                    showAppAlert('error', "- Se produjo un error al realizar el proceso, favor contacte a su administrador", {
                        duration: 5000,
                        image: base_url + 'assets/img/errorGif.gif'
                    });
                })
                .always(function() {});

        } else {
            // Modo creación - crear múltiples mesas
            var cantidad = parseInt($('#cantidadMesas').val());
            
            if (isNaN(cantidad) || cantidad < 1) {
                showAppAlert('error', "Debe ingresar una cantidad válida de mesas (mínimo 1)!", {
                    duration: 5000,
                    image: base_url + 'assets/img/errorGif.gif'
                });
                return;
            }

            var miJson = {
                'cantidad': cantidad
            };

            console.log(miJson);
            $.post(base_url + "index.php/Mesas/crearMultiplesMesas", {
                    'prod': miJson
                }).done(function(response) {
                    showAppAlert('success', "Se crearon exitosamente " + cantidad + " mesa(s)!", {
                        duration: 4000,
                        image: base_url + 'assets/img/success.gif'
                    });
                    $('#ModalNuevaMesa').modal('hide');
                    tablaMesas.ajax.reload();
                })
                .fail(function() {
                    showAppAlert('error', "- Se produjo un error al realizar el proceso, favor contacte a su administrador", {
                        duration: 5000,
                        image: base_url + 'assets/img/errorGif.gif'
                    });
                })
                .always(function() {});
        }
    }
</script>