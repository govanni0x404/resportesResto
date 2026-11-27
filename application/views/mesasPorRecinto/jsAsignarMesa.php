<!--  MODAL ASIGNAR MESA A RECINTO  -->
<div id="ModalAsignarMesa" class="modal fade t-form-modal" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- TITULO  MODAL  -->
            <div id="tituloModalAsignar" class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text text-center">ASIGNAR MESA A RECINTO
                </h4>
            </div>
            <!-- FIN TITULO  MODAL  -->

            <!-- CONTENIDO  MODAL  -->
            <div class="modal-body">
                <div class="h-panel">
                    <div class="tab-pane fade active in">
                        <!-- CAMPOS DE ASIGNACIÓN  -->
                        <fieldset>
                            <div class="col-md-6">
                                <label for="idMesaAsignar">ID MESA</label>
                                <input type="number" disabled class="form-control input-sm" name="idMesaAsignar" id="idMesaAsignar" value="" />
                            </div>
                            <div class="col-md-12">
                                <label for="recintoSelect">SELECCIONAR RECINTO *</label>
                                <select class="form-control input-sm" name="recintoSelect" id="recintoSelect" requerido="si">
                                    <option value="0">-- Seleccione un Recinto --</option>
                                </select>
                            </div>
                        </fieldset>
                        <!-- FIN CAMPOS DE ASIGNACIÓN  -->
                    </div>
                </div>
            </div>
            <!-- CONTENIDO  MODAL  -->

            <!-- BOTONES  -->
            <div class="modal-footer">
                <button onclick="asignarMesa()" type="button" class="btn btn-primary"><i class="fa fa-check"></i> Asignar</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Salir</button>
            </div>
            <!-- FIN BOTONES  -->
        </div>
    </div>
</div>
<!-- FIN  MODAL ASIGNAR MESA A RECINTO  -->

<script>
    var recintoSeleccionado = 0;

    function cargarRecintos() {
        $.post(base_url + "index.php/MesasPorRecinto/ListadoDeRecintos")
            .done(function(response) {
                var recintos = JSON.parse(response);
                var select = $('#recintoSelect');
                select.empty();
                select.append('<option value="0">-- Seleccione un Recinto --</option>');
                
                recintos.forEach(function(recinto) {
                    select.append('<option value="' + recinto.id_recinto + '">' + recinto.nombre_recinto + '</option>');
                });
                select.val(recintoSeleccionado);
            })
            .fail(function() {
                showAppAlert('error', "Error al cargar los recintos", {
                    duration: 5000,
                    image: base_url + 'assets/img/errorGif.gif'
                });
            });
    }

    function asignarMesa() {
        var idMesa = $('#idMesaAsignar').val();
        var idRecinto = $('#recintoSelect').val();
        
        if (idRecinto == "0" || idRecinto == "" || idRecinto == null) {
            showAppAlert('error', "Debe seleccionar un recinto!", {
                duration: 5000,
                image: base_url + 'assets/img/errorGif.gif'
            });
            return;
        }

        var miJson = {
            'id_mesa': idMesa,
            'id_recinto': idRecinto
        };

        console.log('Datos a enviar:', miJson);
        
        $.post(base_url + "index.php/MesasPorRecinto/asignarMesaARecinto", {
                'prod': miJson
            }).done(function(response) {
                console.log('Respuesta del servidor:', response);
                // El servidor responde {success:false, message} o {error} cuando no pudo asignar
                var r = null;
                try { r = typeof response === 'string' ? JSON.parse(response) : response; } catch (e) {}
                if (r && (r.error || r.success === false)) {
                    showAppAlert('error', r.error || r.message || "No se pudo asignar la mesa", {
                        duration: 5000,
                        image: base_url + 'assets/img/errorGif.gif'
                    });
                    return;
                }
                showAppAlert('success', "Mesa asignada exitosamente al recinto!", {
                    duration: 4000,
                    image: base_url + 'assets/img/success.gif'
                });
                $('#ModalAsignarMesa').modal('hide');
                //tablaMesasSinAsignar.ajax.reload();
                tablaMesasSinAsignar.ajax.reload(null, false);
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                console.error('Error:', textStatus, errorThrown);
                showAppAlert('error', "Se produjo un error al realizar el proceso", {
                    duration: 5000,
                    image: base_url + 'assets/img/errorGif.gif'
                });
            });
    }

    // Cargar recintos al abrir el modal
    $('#ModalAsignarMesa').on('show.bs.modal', function() {
        cargarRecintos();
    });
</script>
<!-- v1.38.3 (pedido del usuario): asignar varias mesas a un recinto de una vez -->
<div id="ModalAsignarVarias" class="modal fade" role="dialog" style="overflow-y: auto;">
    <div class="modal-dialog" style="width: 760px; max-width: calc(100vw - 24px); margin-top: 30px;">
        <div class="modal-content">
            <div class="modal-header" style="border-top: 3px solid var(--t-primario, #15803D);">
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-th"></i> Asignar varias mesas a un recinto</h4>
            </div>
            <div class="modal-body">
                <div class="mv-fila">
                    <div class="mv-campo mv-crece"><label for="mvRecinto">Recinto</label><select class="form-control" id="mvRecinto"></select></div>
                    <div class="mv-campo"><label for="mvFiltro">Mostrar</label>
                        <select class="form-control" id="mvFiltro"><option value="sin">Sin recinto</option><option value="todas">Todas las mesas</option></select></div>
                </div>
                <div class="mv-fila">
                    <div class="mv-campo"><label for="mvDesde">Desde mesa</label><input type="number" class="form-control" id="mvDesde" min="1"></div>
                    <div class="mv-campo"><label for="mvHasta">Hasta mesa</label><input type="number" class="form-control" id="mvHasta" min="1"></div>
                    <button type="button" class="btn btn-default" id="mvRango" title="Marca las mesas de ese rango"><i class="fa fa-check-square-o"></i> Marcar rango</button>
                    <button type="button" class="btn btn-default" id="mvTodas"><i class="fa fa-check-square"></i> Todas</button>
                    <button type="button" class="btn btn-default" id="mvNinguna"><i class="fa fa-square-o"></i> Ninguna</button>
                </div>
                <div class="mv-lista" id="mvLista"></div>
                <div class="mv-pie" id="mvCuenta"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> Cancelar</button>
                <button type="button" class="btn btn-success" id="mvAsignar"><i class="fa fa-check"></i> Asignar</button>
            </div>
        </div>
    </div>
</div>
<style>
.mr-barra { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px; padding: 12px 18px 0; }
.mv-fila { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 8px; margin-bottom: 10px; }
.mv-campo label { display: block; margin-bottom: 4px; font-size: 11.5px; text-transform: uppercase; letter-spacing: .3px; color: var(--t-texto-suave, #6B7280); }
.mv-campo .form-control { height: 36px; }
.mv-crece { flex: 1 1 220px; }
.mv-campo input { width: 100px; }
.mv-fila .btn { height: 36px; }
.mv-lista { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 6px; max-height: calc(100vh - 380px); overflow-y: auto;
    padding: 8px; border: 1px solid var(--t-borde, #E5E7EB); border-radius: 10px; }
.mv-mesa { display: flex; flex-direction: column; gap: 2px; margin: 0; padding: 8px 10px; border: 1px solid var(--t-borde, #E5E7EB); border-radius: 8px; cursor: pointer; font-weight: 400; background: #fff; }
.mv-mesa.marcada { border-color: var(--t-primario, #15803D); background: var(--t-primario-suave, #E8F5EC); }
.mv-mesa b { font-size: 14px; }
.mv-mesa b input { margin-right: 6px; accent-color: var(--t-primario, #15803D); }
.mv-mesa small { font-size: 11px; color: var(--t-texto-suave, #6B7280); }
.mv-pie { margin-top: 8px; font-size: 13px; color: var(--t-texto-suave, #4B5563); }
.mv-vacio { grid-column: 1 / -1; padding: 20px; text-align: center; color: var(--t-texto-tenue, #9CA3AF); }
</style>
<script>
(function () {
    var mesas = [], marcadas = {};
    var esc = function (t) { return $('<div>').text(t == null ? '' : String(t)).html(); };
    function visibles() {
        var f = $('#mvFiltro').val();
        return $.grep(mesas, function (m) { return f === 'todas' || !Number(m.id_recinto); });
    }
    function pintar() {
        var v = visibles();
        $('#mvLista').html(v.length ? $.map(v, function (m) {
            var id = Number(m.id_mesa), on = !!marcadas[id];
            return '<label class="mv-mesa' + (on ? ' marcada' : '') + '" data-id="' + id + '"><b><input type="checkbox"' + (on ? ' checked' : '') + '>Mesa ' + id + '</b>'
                + '<small>' + (Number(m.id_recinto) ? esc(m.nombre_recinto || 'Recinto ' + m.id_recinto) : 'Sin recinto') + '</small></label>';
        }).join('') : '<div class="mv-vacio">' + ($('#mvFiltro').val() === 'sin' ? 'Todas las mesas ya tienen recinto. Elige "Todas las mesas" para cambiarlas.' : 'No hay mesas.') + '</div>');
        var n = Object.keys(marcadas).length;
        $('#mvCuenta').text(n ? n + (n === 1 ? ' mesa marcada' : ' mesas marcadas') : 'Marca las mesas que van en el recinto.');
    }
    window.abrirAsignarVarias = function () {
        marcadas = {};
        $('#mvDesde, #mvHasta').val('');
        $('#mvFiltro').val('sin');
        $('#mvLista').html('<div class="mv-vacio"><i class="fa fa-spinner fa-spin"></i> Cargando…</div>');
        $('#ModalAsignarVarias').modal('show');
        $.post(base_url + 'index.php/MesasPorRecinto/ListadoDeRecintos').done(function (r) {
            var rs = typeof r === 'string' ? JSON.parse(r) : r;
            $('#mvRecinto').html('<option value="0">-- Elige el recinto --</option>' + $.map(rs, function (x) {
                return '<option value="' + x.id_recinto + '">' + esc(x.nombre_recinto) + '</option>';
            }).join(''));
        });
        $.post(base_url + 'index.php/MesasPorRecinto/ListadoDeMesas').done(function (r) {
            var d = typeof r === 'string' ? JSON.parse(r) : r;
            mesas = d.aaData || [];
            pintar();
        });
    };
    $('#mvFiltro').on('change', pintar);
    $('#mvLista').on('change', '.mv-mesa input', function () {
        var id = Number($(this).closest('.mv-mesa').data('id'));
        if (this.checked) marcadas[id] = true; else delete marcadas[id];
        pintar();
    });
    $('#mvTodas').on('click', function () { $.each(visibles(), function (i, m) { marcadas[Number(m.id_mesa)] = true; }); pintar(); });
    $('#mvNinguna').on('click', function () { marcadas = {}; pintar(); });
    $('#mvRango').on('click', function () {
        var a = Number($('#mvDesde').val()), b = Number($('#mvHasta').val());
        if (!a || !b) { toastr.warning('Escribe desde qué mesa y hasta qué mesa'); return; }
        if (a > b) { var t = a; a = b; b = t; }
        var n = 0;
        $.each(visibles(), function (i, m) { var id = Number(m.id_mesa); if (id >= a && id <= b) { marcadas[id] = true; n++; } });
        if (!n) toastr.warning('No hay mesas en ese rango con el filtro "' + $('#mvFiltro option:selected').text() + '"');
        pintar();
    });
    $('#mvAsignar').on('click', function () {
        var rec = Number($('#mvRecinto').val()), ids = $.map(Object.keys(marcadas), Number);
        if (!rec) { toastr.warning('Elige el recinto'); $('#mvRecinto').focus(); return; }
        if (!ids.length) { toastr.warning('Marca al menos una mesa'); return; }
        var nombre = $('#mvRecinto option:selected').text(), $b = $(this).prop('disabled', true);
        $.ajax({ url: base_url + 'index.php/MesasPorRecinto/asignarVarias', type: 'POST', dataType: 'json', data: { ids: ids, id_recinto: rec } }).done(function (r) {
            $('#ModalAsignarVarias').modal('hide');
            setTimeout(function () {
                swal({ title: '¡Mesas asignadas!', type: 'success', confirmButtonText: 'Aceptar',
                    text: r.asignadas + (r.asignadas === 1 ? ' mesa quedó' : ' mesas quedaron') + ' en ' + nombre + '.' });
            }, 400);
            if (typeof tablaMesasSinAsignar !== 'undefined') tablaMesasSinAsignar.ajax.reload(null, false);
        }).fail(function (x) { showAppAlert('error', (x.responseJSON && x.responseJSON.error) || 'No se pudieron asignar las mesas'); })
            .always(function () { $b.prop('disabled', false); });
    });
})();
</script>
