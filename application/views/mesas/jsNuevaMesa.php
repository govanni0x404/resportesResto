<!--  MODAL NUEVO REGISTRO  -->
<div id="ModalNuevaMesa" class="modal fade t-form-modal" role="dialog" style="overflow-y: scroll;">
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
                            <div class="col-md-12" id="divTipoMesa" style="display:none;">
                                <label for="tipoMesa">TIPO DE MESA</label>
                                <select class="form-control input-sm" name="tipoMesa" id="tipoMesa"></select>
                                <p class="help-block" style="font-size:12px;margin:4px 0 0">Así sale en la Caja: <b id="tipoMesaVista"></b>. Los tipos se crean en Mantención &rsaquo; Recintos &rsaquo; Tipos de mesa.</p>
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
                <button onclick="grabarNuevaMesa()" type="button" class="btn btn-primary"><i class="fa fa-check"></i> Grabar</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Salir</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>
<!-- FIN  MODAL NUEVO REGISTRO  -->

<script>
    var modoEdicion = false;
    var tiposMesa = [];

    // 19-11-2026: tipos de mesa (Mantención > Recintos > Tipos de mesa). El nombre igual que Mtipomesa::nombre
    function nombreMesaTipo(id, tipo) {
        var p = tipo ? $.trim(String(tipo.prefijo || '')) : '';
        return 'Mesa ' + (p ? p + (tipo.espacio ? ' ' : '') : '') + id;
    }
    function vistaTipoMesa() {
        var id = Number($('#tipoMesa').val()) || 0;
        var t = $.grep(tiposMesa, function (x) { return x.id === id; })[0] || null;
        $('#tipoMesaVista').text(nombreMesaTipo($('#idMesa').val(), t));
    }
    $(document).on('change', '#tipoMesa', vistaTipoMesa);
    function cargarTiposMesa(actual) {
        $('#divTipoMesa').hide();
        $.ajax({ url: base_url + 'index.php/Mesas/tiposMesa', dataType: 'json', cache: false }).done(function (r) {
            if (!r || !r.disponible) return;
            tiposMesa = r.tipos || [];
            var $s = $('#tipoMesa').empty().append($('<option>').val(0).text('Sin tipo (Mesa N, como siempre)'));
            $.each(tiposMesa, function (i, t) {
                if (!t.vigente && t.id !== actual) return;
                $s.append($('<option>').val(t.id).text(t.nombre + ' · ' + t.ejemplo + (t.vigente ? '' : ' (no vigente)')));
            });
            $s.val(actual || 0);
            if ($s.val() === null) $s.val(0);
            vistaTipoMesa();
            $('#divTipoMesa').show();
        });
    }

    function AbrirModalNuevaMesa() {
        modoEdicion = false;
        $('#idMesa').val("");
        $('#cantidadMesas').val("1");
        $('#vigente').val("true");
        
        // Mostrar campo de cantidad y ocultar otros
        $('#divCantidadMesas').show();
        $('#divIdMesa').hide();
        $('#divVigente').hide();
        $('#divTipoMesa').hide();
        
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
            if ($('#divTipoMesa').is(':visible')) miJson.id_tipo_mesa = Number($('#tipoMesa').val()) || 0;

            console.log('Datos a enviar:', miJson);
            
            $.post(base_url + "index.php/Mesas/editarMesa", {
                    'prod': miJson
                }).done(function(response) {
                    console.log('Respuesta del servidor:', response);
                    var r = response;
                    try { if (typeof r === 'string') r = JSON.parse(r); } catch (e) { r = null; }
                    if (r && !Array.isArray(r) && r.error) {
                        showAppAlert('error', r.error, { duration: 6000, image: base_url + 'assets/img/errorGif.gif' });
                        return;
                    }
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
                    var r = response;
                    try { if (typeof r === 'string') r = JSON.parse(r); } catch (e) { r = null; }
                    if (r && r.error) {
                        showAppAlert('error', r.error, { duration: 6000, image: base_url + 'assets/img/errorGif.gif' });
                        return;
                    }
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