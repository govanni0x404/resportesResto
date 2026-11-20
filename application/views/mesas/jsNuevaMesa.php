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
                                <label for="tipoMesa">TIPO DE MESA
                                    <sup><a onclick="AbrirCrearTipoMesa('editar')">Nuevo</a></sup>
                                </label>
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
    /** v1.22.0: el tipo por defecto ("General") de la lista; null si la base aún no lo tiene */
    function tipoMesaDefecto(lista) { return $.grep(lista || [], function (t) { return t.defecto; })[0] || null; }
    function textoTipoMesa(t) { return t.nombre + ' · ' + t.ejemplo + (t.defecto ? ' (por defecto)' : '') + (t.vigente ? '' : ' (no vigente)'); }
    function cargarTiposMesa(actual) {
        $('#divTipoMesa').hide();
        $.ajax({ url: base_url + 'index.php/Mesas/tiposMesa', dataType: 'json', cache: false }).done(function (r) {
            if (!r || !r.disponible) return;
            tiposMesa = r.tipos || [];
            // v1.22.0: con tipo por defecto ("General") ya no hay "Sin tipo": las mesas sin tipo son de él
            var def = tipoMesaDefecto(tiposMesa);
            var $s = $('#tipoMesa').empty();
            if (!def) $s.append($('<option>').val(0).text('Sin tipo (Mesa N, como siempre)'));
            $.each(tiposMesa, function (i, t) {
                if (!t.vigente && t.id !== actual) return;
                $s.append($('<option>').val(t.id).text(textoTipoMesa(t)));
            });
            $s.val(actual || (def ? def.id : 0));
            if ($s.val() === null) $s.val(def ? def.id : 0);
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
<!-- v1.21.0: MODAL CAMBIAR VARIAS MESAS A OTRO TIPO -->
<div id="ModalTipoVarias" class="modal fade t-form-modal" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text text-center"><i class="fa fa-exchange"></i> CAMBIAR VARIAS MESAS A OTRO TIPO</h4>
            </div>
            <div class="modal-body">
                <fieldset>
                    <div class="col-md-12">
                        <label for="tvTipoNuevo">NUEVO TIPO PARA LAS MESAS MARCADAS *
                            <sup><a onclick="AbrirCrearTipoMesa('varias')">Nuevo</a></sup>
                        </label>
                        <select class="form-control input-sm" id="tvTipoNuevo"></select>
                    </div>
                    <div class="col-md-12">
                        <label>MESAS A CAMBIAR</label>
                        <div class="tv-herramientas">
                            <div>
                                <label for="tvDesde">Desde la mesa</label>
                                <input type="number" min="1" class="form-control input-sm" id="tvDesde">
                            </div>
                            <div>
                                <label for="tvHasta">Hasta la mesa</label>
                                <input type="number" min="1" class="form-control input-sm" id="tvHasta">
                            </div>
                            <button type="button" class="btn btn-info btn-sm" id="tvMarcarRango"><i class="fa fa-check-square-o"></i> Marcar ese rango</button>
                            <div>
                                <label for="tvFiltroTipo">Ver solo</label>
                                <select class="form-control input-sm" id="tvFiltroTipo" style="width:auto"></select>
                            </div>
                            <button type="button" class="btn btn-info btn-sm" id="tvTodas"><i class="fa fa-check"></i> Marcar las que se ven</button>
                            <button type="button" class="btn btn-default btn-sm" id="tvNinguna"><i class="fa fa-times"></i> Desmarcar todas</button>
                        </div>
                        <div class="tv-lista" id="tvLista"></div>
                        <p class="tv-contador" id="tvContador"></p>
                        <p class="help-block" style="font-size:12px;margin:4px 0 0">Si a una mesa le pusieron un nombre propio en el plano de la Caja, en la Caja sigue saliendo ese nombre.</p>
                    </div>
                </fieldset>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="tvGrabar"><i class="fa fa-check"></i> Cambiar mesas marcadas</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Salir</button>
            </div>
        </div>
    </div>
</div>

<script>
    // v1.21.0: cambiar el tipo de muchas mesas de una vez. Las mesas salen de la tabla ya cargada (tablaMesas)
    (function () {
        var tipos = [];
        function tipoPorId(id) { return $.grep(tipos, function (x) { return x.id === id; })[0] || null; }
        function nombreTipo(id) { var t = tipoPorId(id) || (id ? null : tipoMesaDefecto(tipos)); return t ? t.nombre : 'Sin tipo'; }
        function contar() {
            var n = $('#tvLista input:checked').length;
            $('#tvContador').text(n === 0 ? 'No hay mesas marcadas' : (n === 1 ? '1 mesa marcada' : n + ' mesas marcadas'));
            $('#tvLista .tv-mesa').each(function () { $(this).toggleClass('marcada', $(this).find('input').prop('checked')); });
        }
        function filtrar() {
            var f = $('#tvFiltroTipo').val();
            $('#tvLista .tv-mesa').each(function () { $(this).toggle(f === 'todas' || String($(this).data('tipo')) === f); });
        }
        function cargarTipos(listo) {
            $.ajax({ url: base_url + 'index.php/Mesas/tiposMesa', dataType: 'json', cache: false }).done(function (r) {
                tipos = (r && r.tipos) || [];
                $('#btnTipoVarias').toggle(!!(r && r.disponible));
                if (listo) listo(r);
            });
        }
        cargarTipos();

        /** Selects del modal: el tipo nuevo (queda elegido 'elegido') y el filtro "Ver solo" (mantiene lo que había). */
        function llenarSelects(elegido) {
            var filtro = $('#tvFiltroTipo').val() || 'todas';
            var def = tipoMesaDefecto(tipos);   // v1.22.0: con tipo por defecto no hay "Sin tipo"
            var $n = $('#tvTipoNuevo').empty();
            var $f = $('#tvFiltroTipo').empty().append($('<option>').val('todas').text('Todas las mesas'));
            if (!def) {
                $n.append($('<option>').val(0).text('Sin tipo (Mesa N, como siempre)'));
                $f.append($('<option>').val(0).text('Sin tipo'));
            }
            $.each(tipos, function (i, t) {
                if (t.vigente) $n.append($('<option>').val(t.id).text(textoTipoMesa(t)));
                $f.append($('<option>').val(t.id).text(t.nombre));
            });
            $n.val(elegido || (def ? def.id : 0));
            if ($n.val() === null) $n.val(def ? def.id : 0);
            $f.val(filtro);
            if ($f.val() === null) $f.val('todas');
        }
        // v1.21.1: al crear un tipo desde "Crear tipo de mesa" queda elegido en el select
        window.tipoMesaCreadoVarias = function (id) { cargarTipos(function () { llenarSelects(id); }); };

        window.AbrirModalTipoVarias = function () {
            cargarTipos(function (r) {
                if (!r || !r.disponible) return;
                llenarSelects(0);
                var $l = $('#tvLista').empty();
                var mesas = tablaMesas.rows().data().toArray().sort(function (a, b) { return Number(a.id_mesa) - Number(b.id_mesa); });
                $.each(mesas, function (i, m) {
                    var vig = (m.vigente === true || m.vigente === 't' || m.vigente === 'true' || m.vigente === 1);
                    $l.append($('<label class="tv-mesa">').attr('data-tipo', Number(m.id_tipo_mesa) || 0).attr('data-id', Number(m.id_mesa))
                        .append($('<input type="checkbox">').val(m.id_mesa))
                        .append($('<span>').text(m.nombre_caja || ('Mesa ' + m.id_mesa))
                            .append($('<small>').text('N° ' + m.id_mesa + ' · ' + nombreTipo(Number(m.id_tipo_mesa) || 0) + (vig ? '' : ' · inactiva')))));
                });
                if (!mesas.length) $l.append($('<p class="text-muted" style="margin:6px">No hay mesas creadas.</p>'));
                $('#tvDesde, #tvHasta').val('');
                $('#tvFiltroTipo').val('todas');
                contar();
                $('#ModalTipoVarias').modal('show');
            });
        };

        $(document).on('change', '#tvLista input', contar);
        $(document).on('change', '#tvFiltroTipo', filtrar);
        $(document).on('click', '#tvTodas', function () { $('#tvLista .tv-mesa:visible input').prop('checked', true); contar(); });
        $(document).on('click', '#tvNinguna', function () { $('#tvLista input').prop('checked', false); contar(); });
        $(document).on('click', '#tvMarcarRango', function () {
            var d = parseInt($('#tvDesde').val(), 10), h = parseInt($('#tvHasta').val(), 10);
            if (isNaN(d) && isNaN(h)) { showAppAlert('error', 'Escribe desde qué mesa y hasta qué mesa', { duration: 4000, image: base_url + 'assets/img/errorGif.gif' }); return; }
            if (isNaN(d)) d = h; if (isNaN(h)) h = d;
            if (d > h) { var x = d; d = h; h = x; }
            var n = 0;
            $('#tvLista .tv-mesa').each(function () {
                var id = Number($(this).data('id'));
                if (id >= d && id <= h) { $(this).find('input').prop('checked', true); n++; }
            });
            contar();
            if (!n) showAppAlert('error', 'No hay mesas del ' + d + ' al ' + h, { duration: 4000, image: base_url + 'assets/img/errorGif.gif' });
        });

        $(document).on('click', '#tvGrabar', function () {
            var ids = $('#tvLista input:checked').map(function () { return Number(this.value); }).get();
            if (!ids.length) { showAppAlert('error', 'Marca al menos una mesa', { duration: 4000, image: base_url + 'assets/img/errorGif.gif' }); return; }
            var idTipo = Number($('#tvTipoNuevo').val()) || 0, $b = $(this), html = $b.html();
            $b.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Cambiando…');
            $.ajax({ url: base_url + 'index.php/Mesas/cambiarTipoMesas', type: 'POST', dataType: 'json', data: { ids: ids, id_tipo_mesa: idTipo } })
                .done(function (r) {
                    if (!r || r.error) { showAppAlert('error', (r && r.error) || 'No se pudieron cambiar las mesas', { duration: 6000, image: base_url + 'assets/img/errorGif.gif' }); return; }
                    showAppAlert('success', (r.cambiadas === 1 ? 'Se cambió 1 mesa' : 'Se cambiaron ' + r.cambiadas + ' mesas') + ' a "' + $('<div>').text(nombreTipo(idTipo)).html() + '"', { duration: 4000, image: base_url + 'assets/img/success.gif' });
                    $('#ModalTipoVarias').modal('hide');
                    tablaMesas.ajax.reload();
                })
                .fail(function (x) {
                    showAppAlert('error', (x.responseJSON && x.responseJSON.error) || 'Se produjo un error al cambiar las mesas', { duration: 6000, image: base_url + 'assets/img/errorGif.gif' });
                })
                .always(function () { $b.prop('disabled', false).html(html); });
        });
    })();
</script>

<script>
    // v1.21.2: "Nuevo" tipo de mesa con el MISMO modal de Mantención > Recintos > Tipos de mesa (tipoMesa/modalTipoMesa.php).
    // Al guardarlo queda elegido en el select desde donde se abrió.
    function AbrirCrearTipoMesa(desde) {
        TipoMesaModal.abrir(null, function (r) {
            var id = Number(r && r.id) || 0;
            if (desde === 'editar') cargarTiposMesa(id);
            else if (window.tipoMesaCreadoVarias) window.tipoMesaCreadoVarias(id);
        });
    }
</script>
