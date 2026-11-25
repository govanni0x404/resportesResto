<?php
/*
 * v1.37.0 (pedido del usuario: "la receta de producción, cuánto sale"): editor completo de la receta de producción de un
 * insumo elaborado (Insumos > pestaña Producción > Nueva / Editar). Antes la ventana solo grababa el nombre y el rendimiento:
 * no se le podían poner sus insumos ni se sabía cuánto costaba.
 *
 * Cada insumo se escribe en la unidad que se quiera (ej. 500 GRAMO de una harina que se guarda en KILOGRAMO) y el costo se
 * calcula con su costo promedio por unidad de inventario. Abajo: cuánto cuesta la receta y cuánto sale 1 unidad de lo que
 * produce. Servidor: RecetaProduccion::editor / guardarCompleta (MrecetaProduccion).
 */
?>
<style>
#ModalRecetaProduccion .modal-dialog { width: 1320px; max-width: calc(100vw - 24px); margin-top: 16px; }
#ModalRecetaProduccion .modal-header { border-top: 3px solid var(--t-primario, #15803D); }
.rp-grid { display: grid; grid-template-columns: minmax(250px, 3fr) 9fr; gap: 18px; align-items: start; }
.rp-caja { padding: 12px 14px; border: 1px solid var(--t-borde, #E5E7EB); border-radius: 10px; margin-bottom: 12px; }
.rp-caja > b { display: block; margin-bottom: 8px; font-size: 13.5px; }
.rp-campo { margin-bottom: 10px; }
.rp-campo label { display: block; margin-bottom: 4px; font-size: 11.5px; text-transform: uppercase; letter-spacing: .3px; color: var(--t-texto-suave, #6B7280); }
.rp-campo .form-control { height: 36px; }
.rp-fila { display: flex; gap: 8px; align-items: flex-end; flex-wrap: wrap; }
.rp-fila > .rp-campo { margin-bottom: 0; }
.rp-crece { flex: 1 1 160px; min-width: 0; }
.rp-num { flex: 0 0 110px; }
.rp-uni { flex: 0 0 140px; }
.rp-bod { flex: 0 0 190px; }
.rp-tabla select.rp-b { width: 180px; }
.rp-stock { display: block; margin-top: 2px; font-size: 11.5px; color: var(--t-texto-suave, #6B7280); }
.rp-campo .rp-stock { position: absolute; }
.rp-bod { position: relative; }
.rp-sin-stock { color: #B91C1C !important; }
.rp-elaborado { font-size: 15px; font-weight: 800; color: var(--t-texto-fuerte, #111827); }
.rp-elaborado small { display: block; font-size: 12px; font-weight: 400; color: var(--t-texto-suave, #6B7280); }
.rp-recetas { display: flex; flex-direction: column; gap: 6px; }
.rp-receta { text-align: left; padding: 8px 10px; border: 1px solid var(--t-borde, #E5E7EB); border-radius: 8px; background: #fff; font-size: 13px; cursor: pointer; }
.rp-receta.activa { border-color: var(--t-primario, #15803D); background: var(--t-primario-suave, #E8F5EC); font-weight: 700; }
.rp-receta.inactiva { opacity: .55; }
.rp-tabla { border: 1px solid var(--t-borde, #E5E7EB); border-radius: 10px; overflow: hidden; }
.rp-tabla table { margin: 0; }
.rp-tabla th { font-size: 11.5px; text-transform: uppercase; letter-spacing: .3px; color: var(--t-texto-suave, #6B7280); background: var(--t-fondo-alt, #F9FAFB); }
.rp-tabla td { vertical-align: middle !important; }
.rp-tabla td small { display: block; color: var(--t-texto-suave, #6B7280); font-size: 11.5px; }
.rp-tabla input { width: 90px; height: 30px; text-align: right; display: inline-block; }
.rp-tabla select { width: 120px; height: 30px; display: inline-block; padding: 2px 6px; }
.rp-quitar { border: 0; background: transparent; color: #B91C1C; font-size: 15px; }
.rp-total { display: flex; flex-wrap: wrap; gap: 8px 22px; align-items: baseline; margin-top: 12px; padding: 12px 14px; border-radius: 10px;
    background: var(--t-primario-suave, #E8F5EC); border: 1px solid var(--t-primario-borde, #BBE3C8); font-size: 14px; }
.rp-total b { font-size: 17px; }
.rp-total small { flex: 1 1 100%; font-size: 12px; color: var(--t-texto-suave, #4B5563); }
.rp-aviso { color: #B45309; }
.rp-vacio { padding: 18px; text-align: center; color: var(--t-texto-tenue, #9CA3AF); }
@media (max-width: 900px) { .rp-grid { grid-template-columns: 1fr; } }
</style>
<div id="ModalRecetaProduccion" class="modal fade" role="dialog" style="overflow-y: auto;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-flask"></i> Receta de producción</h4>
            </div>
            <div class="modal-body">
                <div class="rp-grid">
                    <div>
                        <div class="rp-caja">
                            <div class="rp-elaborado" id="rpElaborado"></div>
                        </div>
                        <div class="rp-caja">
                            <b>Recetas de este insumo</b>
                            <div class="rp-recetas" id="rpRecetas"></div>
                            <button type="button" class="btn btn-default btn-sm" id="rpNueva" style="margin-top:8px"><i class="fa fa-plus"></i> Nueva receta</button>
                        </div>
                    </div>
                    <div>
                        <div class="rp-caja">
                            <div class="rp-fila">
                                <div class="rp-campo rp-crece"><label for="rpNombre">Nombre de la receta</label>
                                    <input type="text" class="form-control" id="rpNombre" maxlength="50" placeholder="Ej: Masa de pizza"></div>
                                <div class="rp-campo rp-num"><label for="rpRinde">Rinde</label>
                                    <input type="number" class="form-control" id="rpRinde" min="0" step="any" placeholder="Ej: 2"></div>
                                <div class="rp-campo rp-uni"><label for="rpRindeUnidad">Unidad</label>
                                    <select class="form-control" id="rpRindeUnidad"></select></div>
                                <label style="margin:0 0 8px 4px; font-weight:600; cursor:pointer"><input type="checkbox" id="rpVigente" checked> Vigente</label>
                            </div>
                        </div>
                        <div class="rp-caja">
                            <b>Insumos que lleva</b>
                            <div class="rp-fila">
                                <div class="rp-campo rp-crece"><label for="rpInsumo">Insumo</label>
                                    <select class="form-control" id="rpInsumo" style="width:100%"></select></div>
                                <div class="rp-campo rp-num"><label for="rpCantidad">Cantidad</label>
                                    <input type="number" class="form-control" id="rpCantidad" min="0" step="any" placeholder="0"></div>
                                <div class="rp-campo rp-uni"><label for="rpUnidad">Unidad</label>
                                    <select class="form-control" id="rpUnidad"></select></div>
                                <!-- v1.37.3 (pedido del usuario): de qué bodega se descuenta al producir -->
                                <div class="rp-campo rp-bod"><label for="rpBodega">Sale de la bodega</label>
                                    <select class="form-control" id="rpBodega"></select><span class="rp-stock" id="rpBodegaStock"></span></div>
                                <button type="button" class="btn btn-success" id="rpAgregar" style="height:36px"><i class="fa fa-plus"></i> Agregar</button>
                            </div>
                            <div class="rp-tabla" style="margin-top:24px">
                                <table class="table table-hover">
                                    <thead><tr><th>Insumo</th><th class="text-right">Cantidad</th><th>Unidad</th><th>Bodega</th><th class="text-right">Costo</th><th></th></tr></thead>
                                    <tbody id="rpLineas"></tbody>
                                </table>
                            </div>
                            <div class="rp-total" id="rpTotal"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> Salir</button>
                <button type="button" class="btn btn-success" id="rpGuardar"><i class="fa fa-check"></i> Guardar receta</button>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var url = '<?php echo base_url(); ?>index.php/RecetaProduccion/';   // base_url se define más abajo en la página de Insumos
    var ed = { elaborado: null, recetas: [], insumos: [] }, actual = 0, lineas = [];
    var esc = function (t) { return $('<div>').text(t == null ? '' : String(t)).html(); };
    var pesos = function (n) { return '$' + Math.round(Number(n) || 0).toLocaleString('es-CL'); };
    var pesos2 = function (n) { return '$' + (Math.round((Number(n) || 0) * 100) / 100).toLocaleString('es-CL'); };
    function insumo(id) { return $.grep(ed.insumos, function (i) { return i.id_insumo === Number(id); })[0]; }
    function conv(i) {
        return { id_umedida_receta: i.id_umedida_receta, id_umedida_inventario: i.id_umedida_inventario,
            umedida_receta: i.umedida_receta, umedida_inventario: i.umedida_inventario, nombre_umedida_receta: i.nombre_umedida_receta };
    }
    /** Cantidad en la unidad de inventario del insumo (la del costo y del stock); null si la unidad no sirve */
    function aInventario(i, cant, idU) {
        var r = Unidades.aReceta(conv(i), cant, idU);
        return r === null ? null : Unidades.recetaAInventario(conv(i), r);
    }
    /** Opciones de unidad para un insumo: las que se pueden convertir a las suyas */
    function opcionesUnidad(i, sel) {
        var lista = Unidades.delInsumo(conv(i));
        if (!lista.length) lista = [{ id: i.id_umedida_inventario, nombre: i.nombre_umedida_inventario }];
        if (!$.grep(lista, function (u) { return u.id === i.id_umedida_inventario; }).length && i.id_umedida_inventario) lista.push({ id: i.id_umedida_inventario, nombre: i.nombre_umedida_inventario });
        return $.map(lista, function (u) { return '<option value="' + u.id + '"' + (Number(sel) === u.id ? ' selected' : '') + '>' + esc(u.nombre || Unidades.nombre(u.id)) + '</option>'; }).join('');
    }

    // ---- v1.37.3: bodega de cada insumo (con el stock que tiene ahí) ----
    function stockDe(idIns, idBod) { var x = ed.stock && ed.stock[idIns]; return x && x[idBod] !== undefined ? Number(x[idBod]) : 0; }
    /** La bodega que se propone: la que tiene más stock de ese insumo (o la primera) */
    function bodegaSugerida(idIns) {
        var mejor = ed.bodegas.length ? ed.bodegas[0].id : 0, max = -Infinity;
        $.each(ed.bodegas, function (k, b) { var st = stockDe(idIns, b.id); if (st > max) { max = st; mejor = b.id; } });
        return mejor;
    }
    function opcionesBodega(i, sel) {
        return $.map(ed.bodegas, function (b) {
            return '<option value="' + b.id + '"' + (Number(sel) === b.id ? ' selected' : '') + '>' + esc(b.nombre) + '</option>';
        }).join('');
    }

    /** "Hay 2,6 KILOGRAMO" del insumo en esa bodega */
    function textoStock(i, idBod) { return 'Hay ' + Unidades.fmt(stockDe(i.id_insumo, idBod)) + ' ' + esc(i.nombre_umedida_inventario || ''); }
    function pintarStockNuevo() {
        var i = insumo($('#rpInsumo').val());
        $('#rpBodegaStock').html(i && Number($('#rpBodega').val()) ? textoStock(i, Number($('#rpBodega').val())) : '');
    }
    $(document).on('change', '#rpBodega', pintarStockNuevo);

    function abrir(idInsumo, idReceta) {
        if (!Number(idInsumo)) { showAppAlert('error', 'Graba el insumo antes de crear su receta de producción'); return; }
        $('#rpLineas').html('<tr><td colspan="6" class="rp-vacio"><i class="fa fa-spinner fa-spin"></i> Cargando…</td></tr>');
        $('#ModalRecetaProduccion').modal({ backdrop: 'static', keyboard: false });
        Unidades.listo(function () {
            $.ajax({ url: url + 'editor', data: { id_insumo: idInsumo }, dataType: 'json', cache: false }).done(function (r) {
                ed = r;
                var e = r.elaborado;
                $('#rpElaborado').html(esc(e.nombre_insumo) + '<small>Se guarda en ' + esc(e.nombre_umedida_inventario || '?') + ' · costo actual ' + pesos2(e.costo) + ' por ' + esc(e.nombre_umedida_inventario || 'unidad') + '</small>');
                $('#rpInsumo').html('<option value="0">Busca un insumo…</option>' + $.map(r.insumos, function (i) {
                    return '<option value="' + i.id_insumo + '">' + esc(i.nombre_insumo) + '</option>';
                }).join(''));
                if ($.fn.select2) $('#rpInsumo').select2({ dropdownParent: $('#ModalRecetaProduccion') });
                if (idReceta) cargarReceta(idReceta);
                else if (r.recetas.length && idReceta !== 0) cargarReceta(r.recetas[0].id);
                else nueva();
            }).fail(function (x) { $('#ModalRecetaProduccion').modal('hide'); showAppAlert('error', (x.responseJSON && x.responseJSON.error) || 'No se pudo abrir la receta'); });
        });
    }
    window.abrirRecetaProduccion = abrir;

    function pintarRecetas() {
        $('#rpRecetas').html(ed.recetas.length ? $.map(ed.recetas, function (r) {
            return '<button type="button" class="rp-receta' + (r.id === actual ? ' activa' : '') + (r.vigente ? '' : ' inactiva') + '" data-id="' + r.id + '">'
                + esc(r.nombre) + ' <small>· rinde ' + Unidades.fmt(r.cantidad) + ' ' + esc(Unidades.nombre(r.id_umedida)) + (r.vigente ? '' : ' · no vigente') + '</small></button>';
        }).join('') : '<div class="rp-vacio" style="padding:8px">Todavía no tiene recetas.</div>');
    }
    function pintarRinde(sel) {
        var e = ed.elaborado;
        $('#rpRindeUnidad').html(opcionesUnidad(e, sel || e.id_umedida_inventario));
    }
    function nueva() {
        actual = 0; lineas = [];
        // v1.37.2 (pedido del usuario): una receta nueva parte en blanco (antes traía el nombre del insumo)
        $('#rpNombre').val(''); $('#rpRinde').val(''); $('#rpVigente').prop('checked', true);
        $('#rpCantidad').val(''); $('#rpInsumo').val('0').trigger('change');
        pintarRinde(); pintarRecetas(); pintarLineas();
        setTimeout(function () { $('#rpNombre').focus(); }, 300);
    }
    function cargarReceta(id) {
        $.ajax({ url: url + 'editor', data: { id: id }, dataType: 'json', cache: false }).done(function (r) {
            var rec = r.receta;
            actual = rec.id;
            $('#rpNombre').val(rec.nombre); $('#rpRinde').val(rec.cantidad); $('#rpVigente').prop('checked', rec.vigente);
            pintarRinde(rec.id_umedida);
            lineas = $.map(rec.lineas, function (l) {
                if (!insumo(l.id_insumo)) return null;
                // v1.37.3: las recetas de antes no tenían bodega: se propone la que tiene más stock (revísala y guarda)
                if (!l.id_bodega || !$.grep(ed.bodegas, function (b) { return b.id === l.id_bodega; }).length) l.id_bodega = bodegaSugerida(l.id_insumo);
                return [l];
            });
            pintarRecetas(); pintarLineas();
        }).fail(function (x) { showAppAlert('error', (x.responseJSON && x.responseJSON.error) || 'No se pudo abrir la receta'); });
    }
    $('#rpRecetas').on('click', '.rp-receta', function () { cargarReceta(+$(this).data('id')); });
    $('#rpNueva').on('click', nueva);

    // ---- insumos de la receta ----
    $('#rpInsumo').on('change', function () {
        var i = insumo($(this).val());
        $('#rpUnidad').html(i ? opcionesUnidad(i, i.id_umedida_receta || i.id_umedida_inventario) : '');
        $('#rpBodega').html(i ? opcionesBodega(i, bodegaSugerida(i.id_insumo)) : '');
        pintarStockNuevo();
        if (i) $('#rpCantidad').focus();
    });
    function agregar() {
        var i = insumo($('#rpInsumo').val()), q = Number(String($('#rpCantidad').val()).replace(',', '.')), u = Number($('#rpUnidad').val()), bo = Number($('#rpBodega').val());
        if (!i) { toastr.warning('Elige el insumo'); return; }
        if (!bo) { toastr.warning('Elige de qué bodega sale'); return; }
        if (!(q > 0)) { toastr.warning('Escribe la cantidad'); $('#rpCantidad').focus(); return; }
        var ya = $.grep(lineas, function (l) { return l.id_insumo === i.id_insumo && l.id_umedida === u && l.id_bodega === bo; })[0];
        if (ya) ya.cantidad = Math.round((ya.cantidad + q) * 1000) / 1000;
        else lineas.push({ id_insumo: i.id_insumo, cantidad: q, id_umedida: u, id_bodega: bo });
        $('#rpCantidad').val('');
        $('#rpInsumo').val('0').trigger('change');
        pintarLineas();
    }
    $('#rpAgregar').on('click', agregar);
    $('#rpCantidad').on('keydown', function (e) { if (e.which === 13) { e.preventDefault(); agregar(); } });
    function costoLinea(l) { var i = insumo(l.id_insumo), inv = aInventario(i, l.cantidad, l.id_umedida); return inv === null ? 0 : inv * i.costo; }
    function pintarLineas() {
        $('#rpLineas').html(lineas.length ? $.map(lineas, function (l, k) {
            var i = insumo(l.id_insumo), inv = aInventario(i, l.cantidad, l.id_umedida), c = costoLinea(l);
            var detalle = inv === null ? '<small class="rp-aviso">Unidad sin equivalencia</small>'
                : (l.id_umedida !== i.id_umedida_inventario ? '<small>= ' + Unidades.fmt(inv) + ' ' + esc(i.nombre_umedida_inventario) + '</small>' : '');
            return '<tr data-k="' + k + '"><td><b>' + esc(i.nombre_insumo) + '</b><small>' + pesos2(i.costo) + ' por ' + esc(i.nombre_umedida_inventario || 'unidad') + '</small></td>'
                + '<td class="text-right"><input type="number" class="form-control rp-q" min="0" step="any" value="' + l.cantidad + '">' + detalle + '</td>'
                + '<td><select class="form-control rp-u">' + opcionesUnidad(i, l.id_umedida) + '</select></td>'
                + '<td><select class="form-control rp-b' + (inv !== null && inv > stockDe(i.id_insumo, l.id_bodega) + 0.0000001 ? ' rp-sin-stock' : '') + '"'
                + (inv !== null && inv > stockDe(i.id_insumo, l.id_bodega) + 0.0000001 ? ' title="En esta bodega no alcanza para una receta"' : '') + '>' + opcionesBodega(i, l.id_bodega) + '</select>'
                + '<span class="rp-stock' + (inv !== null && inv > stockDe(i.id_insumo, l.id_bodega) + 0.0000001 ? ' rp-sin-stock' : '') + '">' + textoStock(i, l.id_bodega) + '</span></td>'
                + '<td class="text-right rp-c">' + pesos(c) + '</td>'
                + '<td><button type="button" class="rp-quitar" title="Quitar"><i class="fa fa-times"></i></button></td></tr>';
        }).join('') : '<tr><td colspan="6" class="rp-vacio">Agrega arriba los insumos que lleva la receta.</td></tr>');
        pintarTotal();
    }
    function pintarTotal() {
        var total = 0, sinCosto = [];
        $.each(lineas, function (k, l) { total += costoLinea(l); var i = insumo(l.id_insumo); if (!(i.costo > 0)) sinCosto.push(i.nombre_insumo); });
        // Cuánto sale: la receta completa y 1 unidad de lo que produce
        var e = ed.elaborado, rinde = Number(String($('#rpRinde').val()).replace(',', '.')) || 0;
        var rindeInv = rinde > 0 ? aInventario(e, rinde, Number($('#rpRindeUnidad').val())) : 0;
        var t = 'Costo de la receta: <b>' + pesos(total) + '</b>';
        if (rindeInv > 0) t += '<span>Rinde ' + Unidades.fmt(rinde) + ' ' + esc($('#rpRindeUnidad option:selected').text()) + ' → <b>' + pesos2(total / rindeInv) + '</b> por ' + esc(e.nombre_umedida_inventario || 'unidad') + '</span>';
        else t += '<span class="rp-aviso">Escribe cuánto rinde para saber el costo por ' + esc(e.nombre_umedida_inventario || 'unidad') + '</span>';
        if (sinCosto.length) t += '<small class="rp-aviso"><i class="fa fa-exclamation-triangle"></i> Sin costo todavía (cómpralos o ponles costo): ' + esc(sinCosto.join(', ')) + '</small>';
        else t += '<small>Con el costo promedio actual de cada insumo. Al producir, el costo real se calcula igual y queda en el insumo elaborado.</small>';
        $('#rpTotal').html(t);
    }
    $('#rpRinde, #rpRindeUnidad').on('input change', pintarTotal);
    // Al escribir solo cambian su costo y el total (la tabla se vuelve a pintar al salir del campo)
    $('#rpLineas').on('input', '.rp-q', function () {
        var l = lineas[+$(this).closest('tr').data('k')];
        l.cantidad = Number(String(this.value).replace(',', '.')) || 0;
        $(this).closest('tr').find('.rp-c').text(pesos(costoLinea(l)));
        pintarTotal();
    }).on('change', '.rp-q', pintarLineas).on('change', '.rp-b', function () {
        lineas[+$(this).closest('tr').data('k')].id_bodega = Number(this.value);
        pintarLineas();
    }).on('change', '.rp-u', function () {
        lineas[+$(this).closest('tr').data('k')].id_umedida = Number(this.value);
        pintarLineas();
    }).on('click', '.rp-quitar', function () {
        lineas.splice(+$(this).closest('tr').data('k'), 1);
        pintarLineas();
    });

    // ---- guardar ----
    $('#rpGuardar').on('click', function () {
        var d = { id: actual, id_insumo: ed.elaborado.id_insumo, nombre: $.trim($('#rpNombre').val()), cantidad: $('#rpRinde').val(),
            id_umedida: $('#rpRindeUnidad').val(), vigente: $('#rpVigente').is(':checked') ? 1 : 0,
            lineas: $.map(lineas, function (l) { return { id_insumo: l.id_insumo, cantidad: l.cantidad, id_umedida: l.id_umedida, id_bodega: l.id_bodega }; }) };
        if (!d.nombre) { toastr.warning('Escribe el nombre de la receta'); $('#rpNombre').focus(); return; }
        if (!(Number(d.cantidad) > 0)) { toastr.warning('Escribe cuánto rinde la receta'); $('#rpRinde').focus(); return; }
        if (!lineas.length) { toastr.warning('Agrega al menos un insumo'); return; }
        var $b = $(this).prop('disabled', true);
        $.ajax({ url: url + 'guardarCompleta', type: 'POST', dataType: 'json', data: d }).done(function (r) {
            showAppAlert('success', 'Receta guardada: cuesta ' + pesos(r.costo) + (r.costo_unidad ? ' (' + pesos2(r.costo_unidad) + ' por ' + r.unidad + ')' : ''));
            var idI = ed.elaborado.id_insumo;
            $.ajax({ url: url + 'editor', data: { id_insumo: idI }, dataType: 'json', cache: false }).done(function (x) { ed.recetas = x.recetas; actual = r.id; pintarRecetas(); });
            if (typeof llenarRecetaProduccion === 'function') {
                llenarRecetaProduccion(idI);
                setTimeout(function () { $('#cboMedReceta').val(String(r.id)).trigger('change'); }, 700);
            }
        }).fail(function (x) { showAppAlert('error', (x.responseJSON && x.responseJSON.error) || 'No se pudo guardar la receta'); })
            .always(function () { $b.prop('disabled', false); });
    });

    // Botones de la pestaña Producción del insumo
    $(document).on('click', '#btnCrearRecetaProduccion', function () { abrir($('#idInsumo').val(), 0); });
    $(document).on('click', '#btnEditarRecetaProduccion', function () {
        var id = Number($('#cboMedReceta').val()) || 0;
        abrir($('#idInsumo').val(), id || null);
    });
})();
</script>
