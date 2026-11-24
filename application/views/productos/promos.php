<?php
/*
 * v1.36.0 (pedido del usuario): Carta > Promos. Promos normales con productos fijos, ej. "Completo + Bebida a $3.990".
 * A la izquierda la lista de promos y a la derecha la promo elegida: nombre, precio, familia, activa y sus productos con su
 * cantidad. En la Caja la promo entra al tiro (todo es fijo). Servidor: Productos::promos / grabarPromo (Mpromos).
 */
?>
<style>
#ModalPromos .modal-dialog { width: 1180px; max-width: calc(100vw - 24px); margin-top: 16px; }
#ModalPromos .modal-header { border-top: 3px solid var(--t-primario, #15803D); }
#ModalPromos .modal-body { padding: 16px 18px; }
.pr-grid { display: grid; grid-template-columns: minmax(280px, 4fr) 7fr; gap: 18px; align-items: start; }
.pr-lista-cab { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 8px; }
.pr-lista-cab b { font-size: 14px; }
.pr-lista { max-height: calc(100vh - 230px); overflow-y: auto; display: flex; flex-direction: column; gap: 6px; }
.pr-item { text-align: left; width: 100%; padding: 9px 12px; border: 1px solid var(--t-borde, #E5E7EB); border-radius: 10px; background: #fff; cursor: pointer; }
.pr-item:hover { border-color: var(--t-primario-borde, #BBE3C8); }
.pr-item.activa { border-color: var(--t-primario, #15803D); background: var(--t-primario-suave, #E8F5EC); }
.pr-item.inactiva { opacity: .6; }
.pr-item .pr-item-top { display: flex; justify-content: space-between; gap: 8px; font-weight: 700; font-size: 13.5px; color: var(--t-texto-fuerte, #111827); }
.pr-item small { display: block; margin-top: 2px; font-size: 12px; color: var(--t-texto-suave, #6B7280); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.pr-vacio { padding: 26px 10px; text-align: center; color: var(--t-texto-tenue, #9CA3AF); font-size: 13px; }
.pr-vacio i { display: block; font-size: 26px; margin-bottom: 6px; }
.pr-editor { padding: 14px 16px; border: 1px solid var(--t-borde, #E5E7EB); border-radius: 12px; }
.pr-fila { display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; }
.pr-campo label { display: block; margin-bottom: 4px; font-size: 11.5px; text-transform: uppercase; letter-spacing: .3px; color: var(--t-texto-suave, #6B7280); }
.pr-campo .form-control { height: 36px; }
.pr-nombre { flex: 1 1 260px; }
.pr-precio { flex: 0 0 130px; }
.pr-familia { flex: 0 1 200px; }
.t-modal-form label.pr-activa { text-transform: none; letter-spacing: 0; font-size: 13.5px; color: var(--t-texto, #374151); }
.pr-activa { display: inline-flex; align-items: center; gap: 6px; height: 36px; margin: 0; font-weight: 600; cursor: pointer; }
.pr-activa input { accent-color: var(--t-primario, #15803D); width: 16px; height: 16px; margin: 0; }
.pr-subt { margin: 16px 0 8px; font-weight: 700; font-size: 14px; }
.pr-buscar { position: relative; }
.pr-buscar > i { position: absolute; left: 11px; top: 11px; color: var(--t-texto-tenue, #9CA3AF); }
.pr-buscar input { padding-left: 32px; height: 36px; }
.pr-resultados { position: absolute; left: 0; right: 0; top: 38px; z-index: 20; max-height: 260px; overflow-y: auto; background: #fff;
    border: 1px solid var(--t-borde, #E5E7EB); border-radius: 10px; box-shadow: 0 8px 20px rgba(17, 24, 39, .12); display: none; }
.pr-res { display: flex; justify-content: space-between; gap: 8px; padding: 8px 12px; cursor: pointer; font-size: 13px; }
.pr-res:hover, .pr-res.sel { background: var(--t-primario-suave, #E8F5EC); }
.pr-res small { color: var(--t-texto-suave, #6B7280); white-space: nowrap; }
.pr-items { margin-top: 10px; border: 1px solid var(--t-borde, #E5E7EB); border-radius: 10px; }
.pr-it { display: flex; align-items: center; gap: 10px; padding: 7px 10px; border-bottom: 1px solid var(--t-borde-suave, #EEF0F3); }
.pr-it:last-child { border-bottom: 0; }
.pr-it-nombre { flex: 1 1 auto; min-width: 0; font-weight: 600; font-size: 13.5px; }
.pr-it-nombre small { display: block; font-weight: 400; color: var(--t-texto-suave, #6B7280); font-size: 11.5px; }
.pr-cant { display: inline-flex; align-items: center; height: 30px; border: 1px solid var(--t-borde, #D1D5DB); border-radius: 8px; overflow: hidden; }
.pr-cant button { width: 28px; height: 100%; border: 0; background: transparent; color: var(--t-texto-suave, #4B5563); }
.pr-cant button:hover { background: var(--t-primario-suave, #E8F5EC); }
.pr-cant b { min-width: 30px; text-align: center; font-size: 14px; }
.pr-quitar { border: 0; background: transparent; color: #B91C1C; font-size: 15px; padding: 4px 6px; }
.pr-pie { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; margin-top: 14px; }
.pr-resumen { font-size: 13px; color: var(--t-texto-suave, #4B5563); }
.pr-resumen b { color: var(--t-texto-fuerte, #111827); }
.pr-resumen .pr-ahorro { color: var(--t-primario, #15803D); font-weight: 700; }
.pr-ayuda { margin-top: 10px; font-size: 12px; color: var(--t-texto-tenue, #6B7280); }
@media (max-width: 860px) { .pr-grid { grid-template-columns: 1fr; } .pr-lista { max-height: 220px; } }
</style>
<div id="ModalPromos" class="modal fade t-modal-form" role="dialog" style="overflow-y: auto;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-tags"></i> PROMOS</h4>
            </div>
            <div class="modal-body">
                <div class="pr-grid">
                    <div>
                        <div class="pr-lista-cab"><b>Promos</b>
                            <button type="button" class="btn btn-success btn-sm" id="btnPromoNueva"><i class="fa fa-plus"></i> Nueva promo</button></div>
                        <div class="pr-lista" id="prLista"></div>
                    </div>
                    <div class="pr-editor">
                        <div class="pr-fila">
                            <div class="pr-campo pr-nombre"><label for="prNombre">Nombre</label>
                                <input type="text" class="form-control" id="prNombre" maxlength="50" placeholder="Ej: Completo + Bebida"></div>
                            <div class="pr-campo pr-precio"><label for="prPrecio">Precio $</label>
                                <input type="number" class="form-control" id="prPrecio" min="1" max="9999999" placeholder="0"></div>
                            <div class="pr-campo pr-familia"><label for="prFamilia">Familia</label>
                                <select class="form-control" id="prFamilia"></select></div>
                            <label class="pr-activa" title="Activa: se vende en la Caja"><input type="checkbox" id="prActiva" checked> Activa</label>
                        </div>
                        <div class="pr-subt">Productos de la promo</div>
                        <div class="pr-buscar">
                            <i class="fa fa-search"></i>
                            <input type="text" class="form-control" id="prBuscar" placeholder="Busca un producto para agregarlo (ej. completo, bebida)" autocomplete="off">
                            <div class="pr-resultados" id="prResultados"></div>
                        </div>
                        <div class="pr-items" id="prItems"></div>
                        <div class="pr-pie">
                            <span class="pr-resumen" id="prResumen"></span>
                            <button type="button" class="btn btn-success" id="btnPromoGuardar"><i class="fa fa-check"></i> Guardar promo</button>
                        </div>
                        <div class="pr-ayuda"><i class="fa fa-info-circle"></i> En la Caja la promo se agrega al tiro, siempre con estos productos. Cada producto descuenta su stock
                            y sale en su impresora (cocina, barra…). Para cambiarla, edítala aquí.</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> SALIR</button>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var url = base_url + 'index.php/Productos/';
    var datos = { promos: [], productos: [], familias: [] }, actual = null, items = [], resultados = [], resSel = 0;
    var esc = function (t) { return $('<div>').text(t == null ? '' : String(t)).html(); };
    var fmt = function (n) { return '$' + (Number(n) || 0).toLocaleString('es-CL'); };
    function error(x, porDefecto) { showAppAlert('error', (x && x.responseJSON && x.responseJSON.error) || porDefecto); }
    function producto(id) { return $.grep(datos.productos, function (p) { return p.id_producto === id; })[0]; }

    window.abrirPromos = function () {
        $('#ModalPromos').modal('show');
        cargar(null);
    };
    function cargar(elegir) {
        $('#prLista').html('<div class="pr-vacio"><i class="fa fa-spinner fa-spin"></i> Cargando…</div>');
        $.ajax({ url: url + 'promos', dataType: 'json', cache: false }).done(function (r) {
            if (!r.disponible) { $('#prLista').html('<div class="pr-vacio"><i class="fa fa-database"></i>Falta actualizar la base: cierra sesión y vuelve a entrar.</div>'); return; }
            datos = r;
            $('#prFamilia').html('<option value="0">Elige…</option>' + $.map(r.familias, function (f) { return '<option value="' + f.id_familia + '">' + esc(f.nombre_familia) + '</option>'; }).join(''));
            pintarLista();
            if (elegir) abrirPromo(elegir); else if (!actual) nueva();
        }).fail(function (x) { error(x, 'No se pudieron cargar las promos'); });
    }
    function pintarLista() {
        $('#prLista').html(datos.promos.length ? $.map(datos.promos, function (p) {
            return '<button type="button" class="pr-item' + (actual && actual.id_producto === p.id_producto ? ' activa' : '') + (p.vigente ? '' : ' inactiva') + '" data-id="' + p.id_producto + '">'
                + '<span class="pr-item-top"><span>' + esc(p.nombre) + '</span><span>' + fmt(p.precio) + '</span></span>'
                + '<small>' + (p.vigente ? '' : 'Inactiva · ') + esc(p.detalle || 'Sin productos') + '</small></button>';
        }).join('') : '<div class="pr-vacio"><i class="fa fa-tags"></i>Aún no hay promos.<br>Presiona <b>Nueva promo</b>.</div>');
    }
    function nueva() {
        actual = null; items = [];
        $('#prNombre').val(''); $('#prPrecio').val(''); $('#prFamilia').val('0'); $('#prActiva').prop('checked', true);
        pintarLista(); pintarItems();
        setTimeout(function () { $('#prNombre').focus(); }, 200);
    }
    function abrirPromo(id) {
        $.ajax({ url: url + 'promos', data: { id: id }, dataType: 'json', cache: false }).done(function (r) {
            actual = r.promo;
            items = $.map(actual.items, function (i) { return { id_producto: i.id_producto, nombre: i.nombre, cantidad: i.cantidad }; });
            $('#prNombre').val(actual.nombre); $('#prPrecio').val(actual.precio);
            $('#prFamilia').val(String(actual.id_familia)); $('#prActiva').prop('checked', actual.vigente);
            pintarLista(); pintarItems();
        }).fail(function (x) { error(x, 'No se pudo abrir la promo'); });
    }
    $('#btnPromoNueva').on('click', nueva);
    $('#prLista').on('click', '.pr-item', function () { abrirPromo(+$(this).data('id')); });

    // ---- productos de la promo ----
    function pintarItems() {
        $('#prItems').html(items.length ? $.map(items, function (it, i) {
            var p = producto(it.id_producto);
            return '<div class="pr-it" data-i="' + i + '"><span class="pr-it-nombre">' + esc(it.nombre) + (p && p.precio ? '<small>' + fmt(p.precio) + ' c/u</small>' : '') + '</span>'
                + '<span class="pr-cant"><button type="button" class="pr-menos" title="Uno menos"><i class="fa fa-minus"></i></button><b>' + it.cantidad + '</b>'
                + '<button type="button" class="pr-mas" title="Uno más"><i class="fa fa-plus"></i></button></span>'
                + '<button type="button" class="pr-quitar" title="Quitar de la promo"><i class="fa fa-times"></i></button></div>';
        }).join('') : '<div class="pr-vacio" style="padding:16px"><i class="fa fa-cutlery"></i>Busca arriba los productos que lleva la promo.</div>');
        pintarResumen();
    }
    function pintarResumen() {
        var normal = 0, todos = true, precio = Number($('#prPrecio').val()) || 0, n = 0;
        $.each(items, function (i, it) { var p = producto(it.id_producto); n += it.cantidad; if (p && p.precio) normal += p.precio * it.cantidad; else todos = false; });
        if (!items.length) { $('#prResumen').html(''); return; }
        var t = '<b>' + n + (n === 1 ? ' producto' : ' productos') + '</b>';
        if (normal > 0 && todos) {
            t += ' · por separado ' + fmt(normal);
            if (precio > 0 && precio < normal) t += ' · <span class="pr-ahorro">el cliente ahorra ' + fmt(normal - precio) + '</span>';
        }
        $('#prResumen').html(t);
    }
    $('#prPrecio').on('input', pintarResumen);
    $('#prItems').on('click', '.pr-mas, .pr-menos, .pr-quitar', function () {
        var i = +$(this).closest('.pr-it').data('i'), it = items[i];
        if ($(this).hasClass('pr-quitar')) items.splice(i, 1);
        else if ($(this).hasClass('pr-mas')) it.cantidad = Math.min(50, it.cantidad + 1);
        else if (it.cantidad > 1) it.cantidad--; else items.splice(i, 1);
        pintarItems();
    });
    function agregarItem(p) {
        var ya = $.grep(items, function (x) { return x.id_producto === p.id_producto; })[0];
        if (ya) ya.cantidad = Math.min(50, ya.cantidad + 1);
        else items.push({ id_producto: p.id_producto, nombre: p.nombre, cantidad: 1 });
        $('#prBuscar').val('').focus();
        $('#prResultados').hide();
        pintarItems();
    }
    function buscar() {
        var t = $.trim($('#prBuscar').val()).toLowerCase();
        if (t.length < 1) { $('#prResultados').hide(); return; }
        resultados = $.grep(datos.productos, function (p) { return p.nombre.toLowerCase().indexOf(t) >= 0 && (!actual || p.id_producto !== actual.id_producto); }).slice(0, 30);
        resSel = 0;
        $('#prResultados').show().html(resultados.length ? $.map(resultados, function (p, i) {
            return '<div class="pr-res' + (i === 0 ? ' sel' : '') + '" data-i="' + i + '"><span>' + esc(p.nombre) + '</span><small>' + esc(p.familia) + (p.precio ? ' · ' + fmt(p.precio) : '') + '</small></div>';
        }).join('') : '<div class="pr-res" style="cursor:default">No hay productos con ese nombre</div>');
    }
    $('#prBuscar').on('input focus', buscar).on('keydown', function (e) {
        if (!resultados.length || !$('#prResultados').is(':visible')) return;
        if (e.which === 40 || e.which === 38) {
            e.preventDefault();
            resSel = Math.max(0, Math.min(resultados.length - 1, resSel + (e.which === 40 ? 1 : -1)));
            $('#prResultados .pr-res').removeClass('sel').eq(resSel).addClass('sel');
        } else if (e.which === 13) { e.preventDefault(); agregarItem(resultados[resSel]); }
        else if (e.which === 27) { e.stopPropagation(); $('#prResultados').hide(); }
    }).on('blur', function () { setTimeout(function () { $('#prResultados').hide(); }, 150); });
    $('#prResultados').on('mousedown', '.pr-res[data-i]', function (e) { e.preventDefault(); agregarItem(resultados[+$(this).data('i')]); });

    // ---- guardar ----
    $('#btnPromoGuardar').on('click', function () {
        var d = { id_producto: actual ? actual.id_producto : 0, nombre: $.trim($('#prNombre').val()), precio: $('#prPrecio').val(),
            id_familia: $('#prFamilia').val(), vigente: $('#prActiva').is(':checked') ? 1 : 0,
            items: $.map(items, function (it) { return { id_producto: it.id_producto, cantidad: it.cantidad }; }) };
        if (!d.nombre) { toastr.warning('Escribe el nombre de la promo'); $('#prNombre').focus(); return; }
        if (!(Number(d.precio) > 0)) { toastr.warning('Escribe el precio de la promo'); $('#prPrecio').focus(); return; }
        if (!Number(d.id_familia)) { toastr.warning('Elige la familia (en la Caja sale en esa familia)'); $('#prFamilia').focus(); return; }
        if (!items.length) { toastr.warning('Agrega al menos un producto a la promo'); $('#prBuscar').focus(); return; }
        var $b = $(this).prop('disabled', true);
        $.ajax({ url: url + 'grabarPromo', type: 'POST', dataType: 'json', data: d }).done(function (r) {
            showAppAlert('success', 'Promo guardada');
            actual = { id_producto: r.id_producto };
            cargar(r.id_producto);
            if (typeof tablaCartaProductos !== 'undefined') tablaCartaProductos.ajax.reload(null, false);
        }).fail(function (x) { error(x, 'No se pudo guardar la promo'); })
            .always(function () { $b.prop('disabled', false); });
    });
})();
</script>
