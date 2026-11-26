<?php
/*
 * v1.36.0 (pedido del usuario): Carta > Promos. Promos normales con productos fijos, ej. "Completo + Bebida a $3.990".
 * A la izquierda la lista de promos y a la derecha la promo elegida: nombre, precio, familia, activa y sus productos con su
 * cantidad. En la Caja la promo entra al tiro (todo es fijo). Servidor: Productos::promos / grabarPromo (Mpromos).
 * v1.39.5: también "El cliente elige": una lista de productos y cuántos elige (en la Caja, como un menú).
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
.pr-familia { flex: 0 1 180px; }
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
/* v1.39.8: grilla de datos sin huecos */
.pr-datos { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px 12px; align-items: end; }
.pr-datos .pr-d2 { grid-column: span 2; }
.pr-datos .pr-d4 { grid-column: 1 / -1; }
.pr-datos .form-control { width: 100%; }
.t-modal-form .pr-datos .pr-campo label.pr-activa { display: flex; align-items: center; gap: 8px; height: 36px; margin: 0; padding: 0 12px;
    border: 1px solid var(--t-borde, #D1D5DB); border-radius: 8px; width: 100%; text-transform: none; letter-spacing: 0; font-size: 14px; color: var(--t-texto, #374151); }
.pr-datos .pr-tipo-btns { display: flex; width: 100%; }
.pr-datos .pr-tipo-btns button { flex: 1 1 50%; height: 36px; padding: 0 10px; }
.t-modal-form .pr-datos label.pr-elegir { margin: 0; padding: 8px 12px; border-radius: 10px; background: var(--t-primario-suave, #E8F5EC);
    align-items: center; gap: 10px; font-size: 14px; font-weight: 600; }
.pr-datos .pr-elegir input.form-control { width: 80px; flex: 0 0 80px; height: 34px; text-align: center; }
@media (max-width: 700px) { .pr-datos { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
.pr-tipo { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-top: 14px; }
.pr-tipo-btns { display: inline-flex; border: 1px solid var(--t-borde, #D1D5DB); border-radius: 10px; overflow: hidden; }
.pr-tipo-btns button { border: 0; background: #fff; padding: 8px 14px; font-size: 13px; font-weight: 600; color: var(--t-texto-suave, #4B5563); }
.pr-tipo-btns button + button { border-left: 1px solid var(--t-borde, #D1D5DB); }
.pr-tipo-btns button.activo { background: var(--t-primario, #15803D); color: #fff; }
.pr-elegir { display: none; align-items: center; gap: 8px; font-size: 13.5px; font-weight: 600; }
.t-modal-form label.pr-elegir { text-transform: none; letter-spacing: 0; font-size: 13.5px; color: var(--t-texto, #374151); margin: 0; }
.pr-elegir input { width: 80px; height: 34px; text-align: center; }
.pr-i { color: var(--t-primario, #15803D); cursor: help; }
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
                        <!-- v1.39.8 (pedido del usuario: "que no queden espacios en blanco"): los datos en una grilla de 4 columnas.
                             Fila 1: nombre (doble), precio y activa. Fila 2: familia, subfamilia y el tipo de promo (doble).
                             Fila 3 (solo "El cliente elige"): cuántos elige. -->
                        <div class="pr-datos">
                            <div class="pr-campo pr-d2"><label for="prNombre">Nombre</label>
                                <input type="text" class="form-control" id="prNombre" maxlength="50" placeholder="Ej: Completo + Bebida"></div>
                            <div class="pr-campo"><label for="prPrecio">Precio $</label>
                                <input type="number" class="form-control" id="prPrecio" min="1" max="9999999" placeholder="0"></div>
                            <div class="pr-campo"><label>Estado</label>
                                <label class="pr-activa" title="Activa: se vende en la Caja"><input type="checkbox" id="prActiva" checked> Activa</label></div>
                            <div class="pr-campo"><label for="prFamilia">Familia</label>
                                <select class="form-control" id="prFamilia"></select></div>
                            <!-- v1.39.7 (pedido del usuario): subfamilia de la promo (opcional; las de su familia) -->
                            <div class="pr-campo"><label for="prSubfamilia">Subfamilia</label>
                                <select class="form-control" id="prSubfamilia"><option value="0">Sin subfamilia</option></select></div>
                            <!-- v1.39.5 (pedido del usuario): promo fija o el cliente elige N productos de la lista (como las de sushi) -->
                            <div class="pr-campo pr-d2"><label>Tipo de promo <i class="fa fa-info-circle pr-i" title="Fija: siempre los mismos productos y cantidades; en la Caja entra al tiro.&#10;El cliente elige: en la Caja se abre una ventana y se eligen los productos de la lista (se puede repetir uno), como las promos de sushi."></i></label>
                                <div class="pr-tipo-btns" role="group" aria-label="Tipo de promo">
                                    <button type="button" data-tipo="fija" class="activo"><i class="fa fa-lock"></i> Fija</button>
                                    <button type="button" data-tipo="elegir"><i class="fa fa-hand-pointer-o"></i> El cliente elige</button>
                                </div></div>
                            <label class="pr-elegir pr-d4" id="prElegirCaja">El cliente elige <input type="number" class="form-control" id="prElegir" min="1" max="50" value="2"> productos de la lista de abajo</label>
                        </div>
                        <div class="pr-subt" id="prSubt">Productos de la promo</div>
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
                        <div class="pr-ayuda" id="prAyuda"></div>
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
    var datos = { promos: [], productos: [], familias: [] }, actual = null, items = [], resultados = [], resSel = 0, tipo = 'fija';
    // v1.39.5: tipo de promo (fija / el cliente elige)
    function ponerTipo(t, n) {
        tipo = t === 'elegir' ? 'elegir' : 'fija';
        $('.pr-tipo-btns button').removeClass('activo').filter('[data-tipo="' + tipo + '"]').addClass('activo');
        $('#prElegirCaja').css('display', tipo === 'elegir' ? 'inline-flex' : 'none');
        if (n) $('#prElegir').val(n);
        $('#prSubt').text(tipo === 'elegir' ? 'Productos para elegir' : 'Productos de la promo');
        $('#prAyuda').html('<i class="fa fa-info-circle"></i> ' + (tipo === 'elegir'
            ? 'En la Caja se abre una ventana y se eligen ' + (Number($('#prElegir').val()) || 0) + ' productos de esta lista (se puede repetir uno). Cada producto descuenta su stock y sale en su impresora.'
            : 'En la Caja la promo se agrega al tiro, siempre con estos productos. Cada producto descuenta su stock y sale en su impresora (cocina, barra…).'));
        pintarItems();
    }
    $(document).on('click', '.pr-tipo-btns button', function () { ponerTipo($(this).data('tipo')); });
    $(document).on('input', '#prElegir', function () { ponerTipo(tipo); });
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
            pintarSubfamilias(0);
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
    // v1.39.7: subfamilias de la familia elegida
    function pintarSubfamilias(idSel) {
        var fam = Number($('#prFamilia').val()) || 0;
        var de = $.grep(datos.subfamilias || [], function (x) { return fam > 0 && (Number(x.id_familia) === fam || !Number(x.id_familia)); });
        $('#prSubfamilia').html('<option value="0">Sin subfamilia</option>' + $.map(de, function (x) {
            return '<option value="' + x.id_subfamilia + '">' + esc(x.nombre_subfamilia) + '</option>';
        }).join('')).val(String(idSel || 0));
        if ($('#prSubfamilia').val() === null) $('#prSubfamilia').val('0');
        $('#prSubfamilia').prop('disabled', !fam);
    }
    $(document).on('change', '#prFamilia', function () { pintarSubfamilias(0); });
    function nueva() {
        actual = null; items = [];
        $('#prNombre').val(''); $('#prPrecio').val(''); $('#prFamilia').val('0'); $('#prActiva').prop('checked', true); pintarSubfamilias(0);
        pintarLista(); ponerTipo('fija', 2);
        setTimeout(function () { $('#prNombre').focus(); }, 200);
    }
    function abrirPromo(id) {
        $.ajax({ url: url + 'promos', data: { id: id }, dataType: 'json', cache: false }).done(function (r) {
            actual = r.promo;
            items = $.map(actual.items, function (i) { return { id_producto: i.id_producto, nombre: i.nombre, cantidad: i.cantidad }; });
            $('#prNombre').val(actual.nombre); $('#prPrecio').val(actual.precio);
            $('#prFamilia').val(String(actual.id_familia)); $('#prActiva').prop('checked', actual.vigente); pintarSubfamilias(actual.id_subfamilia);
            pintarLista(); ponerTipo(actual.elegir > 0 ? 'elegir' : 'fija', actual.elegir > 0 ? actual.elegir : 2);
        }).fail(function (x) { error(x, 'No se pudo abrir la promo'); });
    }
    $('#btnPromoNueva').on('click', nueva);
    $('#prLista').on('click', '.pr-item', function () { abrirPromo(+$(this).data('id')); });

    // ---- productos de la promo ----
    function pintarItems() {
        $('#prItems').html(items.length ? $.map(items, function (it, i) {
            var p = producto(it.id_producto);
            return '<div class="pr-it" data-i="' + i + '"><span class="pr-it-nombre">' + esc(it.nombre) + (p && p.precio ? '<small>' + fmt(p.precio) + ' c/u</small>' : '') + '</span>'
                + (tipo === 'elegir' ? '' : '<span class="pr-cant"><button type="button" class="pr-menos" title="Uno menos"><i class="fa fa-minus"></i></button><b>' + it.cantidad + '</b>'
                + '<button type="button" class="pr-mas" title="Uno más"><i class="fa fa-plus"></i></button></span>')
                + '<button type="button" class="pr-quitar" title="Quitar de la promo"><i class="fa fa-times"></i></button></div>';
        }).join('') : '<div class="pr-vacio" style="padding:16px"><i class="fa fa-cutlery"></i>Busca arriba los productos que lleva la promo.</div>');
        pintarResumen();
    }
    function pintarResumen() {
        var normal = 0, todos = true, precio = Number($('#prPrecio').val()) || 0, n = 0;
        $.each(items, function (i, it) { var p = producto(it.id_producto); n += it.cantidad; if (p && p.precio) normal += p.precio * it.cantidad; else todos = false; });
        if (!items.length) { $('#prResumen').html(''); return; }
        if (tipo === 'elegir') {
            var k = Number($('#prElegir').val()) || 0;
            $('#prResumen').html('<b>Elige ' + k + '</b> de ' + items.length + (items.length === 1 ? ' producto' : ' productos'));
            return;
        }
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
        if (ya && tipo === 'elegir') { toastr.info(p.nombre + ' ya está en la lista'); $('#prBuscar').val(''); $('#prResultados').hide(); return; }
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
            id_familia: $('#prFamilia').val(), id_subfamilia: $('#prSubfamilia').val() || 0, vigente: $('#prActiva').is(':checked') ? 1 : 0,
            elegir: tipo === 'elegir' ? (Number($('#prElegir').val()) || 0) : 0,
            items: $.map(items, function (it) { return { id_producto: it.id_producto, cantidad: it.cantidad }; }) };
        if (!d.nombre) { toastr.warning('Escribe el nombre de la promo'); $('#prNombre').focus(); return; }
        if (!(Number(d.precio) > 0)) { toastr.warning('Escribe el precio de la promo'); $('#prPrecio').focus(); return; }
        if (!Number(d.id_familia)) { toastr.warning('Elige la familia (en la Caja sale en esa familia)'); $('#prFamilia').focus(); return; }
        if (!items.length) { toastr.warning('Agrega al menos un producto a la promo'); $('#prBuscar').focus(); return; }
        if (tipo === 'elegir' && !(d.elegir >= 1 && d.elegir <= 50)) { toastr.warning('Escribe cuántos productos elige el cliente (1 a 50)'); $('#prElegir').focus(); return; }
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
