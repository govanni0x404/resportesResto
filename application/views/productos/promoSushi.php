<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!-- v1.25.0: Promos de sushi armables (Productos > botón "Promos Sushi"). Modelo: Mpromosushi. En la Caja: js.php abrirSushi -->
<style>
#ModalPromosSushi .modal-dialog { width: 980px; max-width: calc(100vw - 24px); }
#ModalPromosSushi .modal-content { border: 0; border-radius: 14px; overflow: hidden; box-shadow: 0 24px 60px rgba(17, 24, 39, .28); }
#ModalPromosSushi .modal-footer { padding: 12px 20px; background: #fff; border-top: 1px solid var(--t-borde, #E5E7EB); }
#ModalPromosSushi .modal-footer .btn { min-width: 120px; height: 40px; border-radius: 9px; font-weight: 700; }
.ps-elegir { display: flex; gap: 10px; align-items: center; }
/* v1.26.0: arriba, promos por pieza o por roll */
.ps-modo { display: inline-flex; padding: 4px; margin-bottom: 12px; border-radius: 12px; background: var(--t-fondo-alt, #F3F4F6); }
.ps-modo button { border: 0; background: transparent; padding: 8px 18px; border-radius: 9px; font-weight: 800; color: var(--t-texto-suave, #4B5563); }
.ps-modo button.on { background: #fff; color: var(--t-primario, #15803D); box-shadow: 0 1px 4px rgba(17, 24, 39, .12); }
.ps-modo button b { margin-left: 6px; padding: 0 7px; border-radius: 10px; background: rgba(0, 0, 0, .07); font-size: 12px; }
.ps-solo-rol { display: none; }
#ModalPromosSushi.modo-rol .ps-solo-rol { display: block; }
#ModalPromosSushi.modo-rol .ps-solo-pieza { display: none; }
.ps-elegir select { flex: 1; height: 42px; font-size: 15px; font-weight: 600; }
.ps-elegir .btn { height: 42px; border-radius: 9px; font-weight: 700; white-space: nowrap; }
.ps-form { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px 12px; }
.ps-form .ps-ancho { grid-column: span 2; }
.ps-form label { display: block; margin-bottom: 4px; font-size: 12px; font-weight: 700; color: var(--t-texto-suave, #4B5563); }
.ps-form label small { font-weight: 500; color: var(--t-texto-tenue, #6B7280); }
.ps-form .form-control { height: 38px; }
.ps-form .ps-check { display: flex; align-items: center; gap: 8px; margin-top: 24px; font-weight: 600; }
@media (max-width: 760px) { .ps-form { grid-template-columns: 1fr 1fr; } .ps-form .ps-ancho { grid-column: span 2; } }
.ps-pie-form { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-top: 12px; }
.ps-pie-form small { color: var(--t-texto-tenue, #6B7280); }
.ps-pie-form .btn { height: 38px; border-radius: 9px; font-weight: 700; }
.ps-bloqueado { opacity: .5; pointer-events: none; }
.ps-tabs { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px; }
.ps-tab { border: 1px solid var(--t-borde, #E5E7EB); background: #fff; border-radius: 20px; padding: 7px 14px; font-weight: 700; cursor: pointer; }
.ps-tab.on { background: var(--t-primario, #15803D); border-color: var(--t-primario, #15803D); color: #fff; }
.ps-tab b { display: inline-block; min-width: 20px; margin-left: 4px; padding: 0 6px; border-radius: 10px; background: rgba(0, 0, 0, .08); font-size: 12px; }
.ps-ayuda-tipo { margin: 0 0 10px; font-size: 13px; color: var(--t-texto-suave, #4B5563); }
.ps-lista { display: flex; flex-direction: column; gap: 6px; max-height: 260px; overflow-y: auto; margin-bottom: 12px; }
.ps-op { display: flex; align-items: center; gap: 10px; padding: 7px 10px; border: 1px solid var(--t-borde, #E5E7EB); border-radius: 10px; background: #fff; }
.ps-op .nombre { flex: 1; font-weight: 700; }
.ps-op .nombre small { font-weight: 500; color: var(--t-texto-tenue, #6B7280); }
.ps-op .ps-valor { display: flex; align-items: center; gap: 4px; font-size: 12px; color: var(--t-texto-suave, #4B5563); }
.ps-op .ps-valor input { width: 90px; height: 30px; text-align: right; }
.ps-op .btn { padding: 3px 9px; }
.ps-vacio { padding: 16px; text-align: center; color: var(--t-texto-tenue, #6B7280); border: 1px dashed var(--t-borde, #E5E7EB); border-radius: 10px; }
.ps-agregar { display: grid; grid-template-columns: minmax(0, 1fr) 110px auto; gap: 8px; align-items: end; }
.ps-agregar + .ps-agregar { margin-top: 8px; }
.ps-agregar label { display: block; margin-bottom: 3px; font-size: 12px; font-weight: 700; color: var(--t-texto-suave, #4B5563); }
.ps-agregar .form-control, .ps-agregar .btn { height: 38px; }
.ps-agregar .btn { border-radius: 9px; font-weight: 700; white-space: nowrap; }
@media (max-width: 600px) { .ps-agregar { grid-template-columns: 1fr; } }
.ps-copiar { display: flex; gap: 8px; align-items: center; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--t-borde-suave, #EEF0F3); }
.ps-copiar select { flex: 1; height: 36px; }
.ps-copiar .btn { height: 36px; border-radius: 9px; font-weight: 700; white-space: nowrap; }
</style>

<div id="ModalPromosSushi" class="modal fade t-modal-form" role="dialog" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="lm-cab">
                <div class="lm-cab-icono"><i class="fa fa-th"></i></div>
                <div>
                    <h4>Promos de Sushi</h4>
                    <small id="psSubtitulo">Promos por piezas (20, 30, 40…): en la Caja el cliente arma sus rolls con base, rellenos y envoltorio</small>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="lm-cuerpo">
                <div class="lm-tarjeta">
                    <!-- v1.26.0: separar las promos por pieza y por roll -->
                    <div class="ps-modo" id="psModo">
                        <button type="button" data-modo="pieza" class="on"><i class="fa fa-th"></i> Por pieza <b>0</b></button>
                        <button type="button" data-modo="rol"><i class="fa fa-circle-o"></i> Por roll <b>0</b></button>
                    </div>
                    <div class="ps-elegir">
                        <select id="cboPromoSushi" class="form-control"></select>
                        <button type="button" class="btn btn-default" id="btnPsNueva"><i class="fa fa-plus"></i> Nueva promo</button>
                    </div>
                </div>

                <div class="lm-tarjeta">
                    <div class="lm-titulo"><span><i class="fa fa-tag"></i><span id="psTituloForm">Nueva promo</span></span></div>
                    <div class="ps-form">
                        <div class="ps-ancho"><label for="psNombre">Nombre</label><input type="text" class="form-control" id="psNombre" maxlength="100" placeholder="Ej: PROMO 30 PIEZAS MIXTAS"></div>
                        <div class="ps-solo-pieza"><label for="psPiezas">Piezas</label><input type="text" inputmode="numeric" class="form-control" id="psPiezas" placeholder="30"></div>
                        <div class="ps-solo-rol"><label for="psRolls">Rolls <small id="psRollsPiezas"></small></label><input type="text" inputmode="numeric" class="form-control" id="psRolls" placeholder="3"></div>
                        <div><label for="psPrecio">Precio <small>(todas las listas)</small></label><input type="text" inputmode="numeric" class="form-control" id="psPrecio" placeholder="9500"></div>
                        <div class="ps-solo-pieza"><label for="psPaso">De a cuántas piezas <small>(1 = libre)</small></label><input type="text" inputmode="numeric" class="form-control" id="psPaso" value="1"></div>
                        <div class="ps-solo-rol"><label for="psPiezasRol">Piezas por roll</label><input type="text" inputmode="numeric" class="form-control" id="psPiezasRol" value="10"></div>
                        <div><label for="psMaxRellenos">Máx. rellenos <small>(0 = sin límite)</small></label><input type="text" inputmode="numeric" class="form-control" id="psMaxRellenos" value="3"></div>
                        <div><label for="psFamilia">Familia</label><select class="form-control" id="psFamilia"></select></div>
                        <div><label for="psDestino">Destino de impresión</label><select class="form-control" id="psDestino"></select></div>
                        <div><label class="ps-check"><input type="checkbox" id="psVigente" checked> Vigente (se vende en la Caja)</label></div>
                    </div>
                    <div class="ps-pie-form">
                        <small id="psAyudaForm"><i class="fa fa-info-circle"></i> "De a cuántas piezas": con 10, cada grupo es un roll completo de 10 (10, 20, 30…). Con 1, cualquier cantidad.</small>
                        <button type="button" class="btn btn-success" id="btnPsGrabar"><i class="fa fa-save"></i> Grabar promo</button>
                    </div>
                </div>

                <div class="lm-tarjeta ps-bloqueado" id="psCatalogo">
                    <div class="lm-titulo"><span><i class="fa fa-list"></i>Lo que el cliente puede elegir</span></div>
                    <div class="ps-tabs" id="psTabs">
                        <span class="ps-tab on" data-tipo="relleno">Rellenos <b>0</b></span>
                        <span class="ps-tab" data-tipo="envoltorio">Envoltorios <b>0</b></span>
                        <span class="ps-tab" data-tipo="base">Bases <b>0</b></span>
                        <span class="ps-tab" data-tipo="extra">Salsas y extras <b>0</b></span>
                    </div>
                    <p class="ps-ayuda-tipo" id="psAyudaTipo"></p>
                    <div class="ps-lista" id="psLista"></div>
                    <div class="ps-agregar">
                        <div><label for="psProducto">Agregar un producto que ya existe</label><select class="form-control" id="psProducto"></select></div>
                        <div><label for="psValorExistente" class="ps-lbl-valor">Valor $</label><input type="text" inputmode="numeric" class="form-control" id="psValorExistente" value="0"></div>
                        <button type="button" class="btn btn-default" id="btnPsAgregar"><i class="fa fa-plus"></i> Agregar</button>
                    </div>
                    <div class="ps-agregar">
                        <div><label for="psNuevoNombre">…o crear uno nuevo</label><input type="text" class="form-control" id="psNuevoNombre" maxlength="100" placeholder="Ej: POLLO"></div>
                        <div><label for="psValorNuevo" class="ps-lbl-valor">Valor $</label><input type="text" inputmode="numeric" class="form-control" id="psValorNuevo" value="0"></div>
                        <button type="button" class="btn btn-success" id="btnPsCrear"><i class="fa fa-magic"></i> Crear y agregar</button>
                    </div>
                    <div class="ps-copiar">
                        <select class="form-control" id="psCopiarDe"></select>
                        <button type="button" class="btn btn-default" id="btnPsCopiar"><i class="fa fa-clone"></i> Copiar sus opciones a esta promo</button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Salir</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var URL_PS = base_url + 'index.php/Productos/';
    var ps = { datos: null, promo: null, opciones: null, tipo: 'relleno', modo: 'pieza' };   // v1.26.0: modo = pieza | rol
    // v1.26.0: en una promo por roll el valor de las opciones es por roll
    var AYUDA_ROL = {
        relleno: 'Lo que va dentro del roll (pollo, kanikama, palta…). El cliente elige hasta el máximo de rellenos por roll. El valor es por roll (0 = no se cobra).',
        envoltorio: 'Con qué se envuelve (queso, panko, palta, nori…). El cliente elige 1 por roll. El valor es por roll (0 = no se cobra).',
        base: 'Opcional. Si no agregas bases, no se pregunta (ej. siempre arroz). Si hay una sola, se elige sola. El valor es por roll.',
        extra: 'Salsas y extras aparte de los rolls (soya, teriyaki, jengibre…). El cliente elige cuántas. El valor es por unidad (0 = no se cobra).'
    };
    var esRol = function () { return ps.modo === 'rol'; };
    var porQue = function () { return ps.tipo === 'extra' ? 'c/u' : (esRol() ? 'x roll' : 'x pieza'); };
    var AYUDA = {
        relleno: 'Lo que va dentro del roll (pollo, kanikama, palta…). El cliente elige hasta el máximo de rellenos por grupo. El valor es por pieza (0 = no se cobra).',
        envoltorio: 'Con qué se envuelve (queso, panko, palta, nori…). El cliente elige 1 por grupo. El valor es por pieza (0 = no se cobra).',
        base: 'Opcional. Si no agregas bases, no se pregunta (ej. siempre arroz). Si hay una sola, se elige sola. El valor es por pieza.',
        extra: 'Salsas y extras aparte de las piezas (soya, teriyaki, jengibre…). El cliente elige cuántas. El valor es por unidad (0 = no se cobra).'
    };
    var esc = function (t) { return $('<div>').text(t == null ? '' : t).html(); };
    var pesos = function (n) { return '$' + Number(n || 0).toLocaleString('es-CL'); };
    var entero = function (v) { return parseInt(String(v || '').replace(/\D/g, ''), 10) || 0; };
    function error(x, def) {
        var m = (x && x.responseJSON && x.responseJSON.error) || def || 'No se pudo completar';
        showAppAlert('error', m, { duration: 5000 });
    }
    function post(accion, datos) { return $.ajax({ url: URL_PS + accion, type: 'POST', dataType: 'json', data: datos || {} }); }

    window.abrirPromosSushi = function () {
        cargarDatos(0).done(function () { $('#ModalPromosSushi').modal('show'); });
    };

    /** v1.26.0: la lista de promos del modo elegido arriba (por pieza o por roll) */
    function pintarListaPromos() {
        var d = ps.datos;
        if (!d) return;
        $('#psModo button').each(function () {
            var m = $(this).data('modo');
            $(this).toggleClass('on', m === ps.modo).find('b').text($.grep(d.promos, function (p) { return p.modo === m; }).length);
        });
        $('#psModo button[data-modo="rol"]').toggle(!!d.con_rol);
        $('#ModalPromosSushi').toggleClass('modo-rol', esRol());
        $('#psSubtitulo').text(esRol()
            ? 'Promos por roll (3 rolls = 30 piezas, 5 rolls = 50): en la Caja el cliente arma cada roll con base, rellenos y envoltorio'
            : 'Promos por piezas (20, 30, 40…): en la Caja el cliente arma sus rolls con base, rellenos y envoltorio');
        $('#psAyudaForm').html(esRol()
            ? '<i class="fa fa-info-circle"></i> Por roll: con 3 rolls de 10 piezas la promo trae 30 piezas. En la Caja se arma roll por roll.'
            : '<i class="fa fa-info-circle"></i> "De a cuántas piezas": con 10, cada grupo es un roll completo de 10 (10, 20, 30…). Con 1, cualquier cantidad.');
        $('#cboPromoSushi').html('<option value="0">— Nueva promo ' + (esRol() ? 'por roll' : 'por pieza') + ' —</option>' + $.map(d.promos, function (p) {
            if (p.modo !== ps.modo) return null;
            var cuanto = p.modo === 'rol' ? (p.piezas / p.piezas_rol) + ' rolls (' + p.piezas + ' pz)' : p.piezas + ' pz';
            return '<option value="' + p.id_producto + '">' + esc(p.nombre) + ' · ' + cuanto + ' · ' + pesos(p.precio) + (p.vigente ? '' : ' (no vigente)') + '</option>';
        }).join(''));
    }
    function rollsPiezas() {
        var r = entero($('#psRolls').val()), pr = entero($('#psPiezasRol').val()) || 10;
        $('#psRollsPiezas').text(r ? '(= ' + (r * pr) + ' piezas)' : '');
    }
    $('#psRolls, #psPiezasRol').on('input', rollsPiezas);

    function cargarDatos(elegir) {
        return post('obtenerPromosSushi').done(function (d) {
            ps.datos = d;
            // Al abrir una promo, arriba queda su modo
            var elegida = elegir ? $.grep(d.promos, function (p) { return p.id_producto === elegir; })[0] : null;
            if (elegida) ps.modo = elegida.modo;
            if (!d.con_rol) ps.modo = 'pieza';
            pintarListaPromos();
            $('#psFamilia').html('<option value="0">Elige…</option>' + $.map(d.familias, function (f) { return '<option value="' + f.id_familia + '">' + esc(f.nombre_familia) + '</option>'; }).join(''));
            $('#psDestino').html('<option value="0">Según la familia</option>' + $.map(d.destinos, function (x) { return '<option value="' + x.id_dimpresion + '">' + esc(x.nombre_dimpresion) + '</option>'; }).join(''));
            pintarProductos();
            elegirPromo(elegir || 0);
        }).fail(function (x) { error(x, 'No se pudieron cargar las promos'); });
    }

    function elegirPromo(id) {
        $('#cboPromoSushi').val(String(id));
        if (!id) {
            ps.promo = null; ps.opciones = null;
            $('#psTituloForm').text('Nueva promo');
            $('#psNombre').val(''); $('#psPiezas').val(''); $('#psPrecio').val(''); $('#psPaso').val('1'); $('#psMaxRellenos').val('3');
            $('#psRolls').val(''); $('#psPiezasRol').val('10'); rollsPiezas();
            $('#psNombre').attr('placeholder', esRol() ? 'Ej: PROMO 3 ROLLS' : 'Ej: PROMO 30 PIEZAS MIXTAS');
            var sushi = $.grep(ps.datos.familias, function (f) { return /sushi/i.test(f.nombre_familia); })[0];
            $('#psFamilia').val(sushi ? String(sushi.id_familia) : '0'); $('#psDestino').val('0'); $('#psVigente').prop('checked', true);
            pintarCatalogo();
            setTimeout(function () { $('#psNombre').focus(); }, 300);
            return;
        }
        post('obtenerPromoSushi', { id_producto: id }).done(function (r) {
            ps.promo = r.promo; ps.opciones = r.opciones;
            var p = r.promo;
            $('#psTituloForm').text('Editar ' + p.nombre);
            $('#psNombre').val(p.nombre); $('#psPiezas').val(p.piezas); $('#psPrecio').val(p.precio); $('#psPaso').val(p.paso); $('#psMaxRellenos').val(p.max_rellenos);
            $('#psRolls').val(p.modo === 'rol' ? p.piezas / p.piezas_rol : ''); $('#psPiezasRol').val(p.piezas_rol || 10); rollsPiezas();
            $('#psFamilia').val(String(p.id_familia)); $('#psDestino').val(String(p.id_dimpresion)); $('#psVigente').prop('checked', p.vigente);
            pintarCatalogo();
        }).fail(function (x) { error(x, 'No se pudo abrir la promo'); });
    }

    function recargarPromo() { if (ps.promo) elegirPromo(ps.promo.id_producto); }

    function pintarProductos() {
        var usados = {};
        $.each(ps.opciones ? ps.opciones[ps.tipo] : [], function (i, o) { usados[o.id_producto] = true; });
        $('#psProducto').html('<option value="0">Elige un producto…</option>' + $.map(ps.datos ? ps.datos.productos : [], function (p) {
            if (usados[p.id_producto] || !p.vigente) return null;
            return '<option value="' + p.id_producto + '">' + esc(p.nombre) + (p.familia ? ' · ' + esc(p.familia) : '') + '</option>';
        }).join(''));
        if ($.fn.select2) {
            if ($('#psProducto').data('select2')) $('#psProducto').select2('destroy');
            $('#psProducto').select2({ width: '100%', dropdownParent: $('#ModalPromosSushi') });
        }
    }

    function pintarCatalogo() {
        $('#psCatalogo').toggleClass('ps-bloqueado', !ps.promo);
        $('#psTabs .ps-tab').each(function () {
            var t = $(this).data('tipo');
            $(this).toggleClass('on', t === ps.tipo).find('b').text(ps.opciones ? ps.opciones[t].length : 0);
        });
        $('#psAyudaTipo').text(ps.promo ? (esRol() ? AYUDA_ROL : AYUDA)[ps.tipo] : 'Graba la promo primero; después agrega sus rellenos, envoltorios, bases y extras.');
        $('.ps-lbl-valor').text('Valor $ ' + porQue());
        var lista = ps.opciones ? ps.opciones[ps.tipo] : [];
        $('#psLista').html(lista.length ? $.map(lista, function (o) {
            return '<div class="ps-op" data-id="' + o.id_opcion + '"><span class="nombre">' + esc(o.nombre)
                + (o.vigente ? '' : ' <small class="text-danger">(producto no vigente: no se ofrece)</small>')
                + (o.con_receta ? ' <small><i class="fa fa-book"></i> con receta</small>' : '') + '</span>'
                + '<span class="ps-valor">' + porQue() + ' $<input type="text" inputmode="numeric" class="form-control input-sm ps-valor-input" value="' + o.valor + '"></span>'
                + '<button type="button" class="btn btn-default btn-sm ps-quitar" title="Quitar de la promo"><i class="fa fa-times text-danger"></i></button></div>';
        }).join('') : '<div class="ps-vacio">' + (ps.promo ? 'Todavía no hay nada aquí' : '—') + '</div>');
        $('#psCopiarDe').html('<option value="0">Copiar las opciones de otra promo…</option>' + $.map(ps.datos ? ps.datos.promos : [], function (p) {
            // v1.26.0: solo de promos del mismo tipo (los valores son por pieza o por roll)
            return (ps.promo && p.id_producto === ps.promo.id_producto) || p.modo !== ps.modo ? null : '<option value="' + p.id_producto + '">' + esc(p.nombre) + ' (' + p.opciones + ' opciones)</option>';
        }).join(''));
        pintarProductos();
    }

    $('#cboPromoSushi').on('change', function () { elegirPromo(Number(this.value)); });
    $('#btnPsNueva').on('click', function () { elegirPromo(0); });
    // v1.26.0: cambiar arriba entre promos por pieza y por roll
    $('#psModo').on('click', 'button', function () {
        var m = $(this).data('modo');
        if (m === ps.modo) return;
        ps.modo = m;
        pintarListaPromos();
        elegirPromo(0);
    });
    $('#psTabs').on('click', '.ps-tab', function () { ps.tipo = $(this).data('tipo'); pintarCatalogo(); });
    $('#ModalPromosSushi').on('input', '#psPiezas, #psPrecio, #psPaso, #psMaxRellenos, #psRolls, #psPiezasRol, #psValorExistente, #psValorNuevo, .ps-valor-input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 9);
    });

    $('#btnPsGrabar').on('click', function () {
        var datos = {
            id_producto: ps.promo ? ps.promo.id_producto : 0, nombre: $.trim($('#psNombre').val()), piezas: entero($('#psPiezas').val()),
            precio: entero($('#psPrecio').val()), paso: entero($('#psPaso').val()) || 1, max_rellenos: entero($('#psMaxRellenos').val()),
            id_familia: Number($('#psFamilia').val()) || 0, id_dimpresion: Number($('#psDestino').val()) || 0, vigente: $('#psVigente').is(':checked') ? 1 : 0,
            modo: ps.modo, rolls: entero($('#psRolls').val()), piezas_rol: entero($('#psPiezasRol').val()) || 10   // v1.26.0
        };
        if (esRol()) { datos.piezas = datos.rolls * datos.piezas_rol; datos.paso = datos.piezas_rol; }
        if (!datos.nombre) { showAppAlert('warning', 'Escribe el nombre de la promo', { duration: 4000 }); return; }
        if (esRol() && !datos.rolls) { showAppAlert('warning', 'Indica cuántos rolls trae la promo', { duration: 4000 }); return; }
        if (!datos.piezas) { showAppAlert('warning', 'Indica cuántas piezas trae la promo', { duration: 4000 }); return; }
        if (!datos.precio) { showAppAlert('warning', 'Indica el precio', { duration: 4000 }); return; }
        if (!datos.id_familia) { showAppAlert('warning', 'Elige la familia', { duration: 4000 }); return; }
        var $b = $(this).prop('disabled', true);
        post('grabarPromoSushi', datos).done(function (r) {
            showAppAlert('success', datos.id_producto ? 'Promo actualizada' : 'Promo creada: ahora agrégale sus rellenos y envoltorios', { duration: 4000 });
            cargarDatos(r.id_producto);
            if (typeof tablaCartaProductos !== 'undefined' && tablaCartaProductos && tablaCartaProductos.ajax) tablaCartaProductos.ajax.reload(null, false);
        }).fail(function (x) { error(x, 'No se pudo grabar la promo'); }).always(function () { $b.prop('disabled', false); });
    });

    $('#btnPsAgregar').on('click', function () {
        var id = Number($('#psProducto').val()) || 0;
        if (!ps.promo) return;
        if (!id) { showAppAlert('warning', 'Elige un producto', { duration: 3000 }); return; }
        var $b = $(this).prop('disabled', true);
        post('agregarOpcionSushi', { id_promo: ps.promo.id_producto, tipo: ps.tipo, id_producto: id, valor: entero($('#psValorExistente').val()) })
            .done(function () { $('#psValorExistente').val('0'); recargarPromo(); })
            .fail(function (x) { error(x, 'No se pudo agregar'); }).always(function () { $b.prop('disabled', false); });
    });

    $('#btnPsCrear').on('click', function () {
        var nombre = $.trim($('#psNuevoNombre').val());
        if (!ps.promo) return;
        if (!nombre) { showAppAlert('warning', 'Escribe el nombre (ej. POLLO)', { duration: 3000 }); return; }
        var $b = $(this).prop('disabled', true);
        post('crearOpcionSushi', { id_promo: ps.promo.id_producto, tipo: ps.tipo, nombre: nombre, valor: entero($('#psValorNuevo').val()) })
            .done(function (r) {
                showAppAlert('success', r.creado ? 'Producto ' + nombre.toUpperCase() + ' creado y agregado. Si quieres que descuente stock, ponle su receta en la Carta.' : 'Ya existía un producto con ese nombre: se agregó ese', { duration: 5000 });
                $('#psNuevoNombre').val('').focus(); $('#psValorNuevo').val('0');
                if (r.creado) cargarDatos(ps.promo.id_producto); else recargarPromo();
            })
            .fail(function (x) { error(x, 'No se pudo crear'); }).always(function () { $b.prop('disabled', false); });
    });
    $('#psNuevoNombre').on('keydown', function (e) { if (e.which === 13) $('#btnPsCrear').click(); });

    $('#psLista').on('change', '.ps-valor-input', function () {
        var id = Number($(this).closest('.ps-op').data('id'));
        post('valorOpcionSushi', { id_opcion: id, valor: entero(this.value) })
            .done(function () { showAppAlert('success', 'Valor actualizado', { duration: 2000 }); recargarPromo(); })
            .fail(function (x) { error(x, 'No se pudo cambiar el valor'); recargarPromo(); });
    }).on('click', '.ps-quitar', function () {
        var id = Number($(this).closest('.ps-op').data('id'));
        post('quitarOpcionSushi', { id_opcion: id }).done(recargarPromo).fail(function (x) { error(x, 'No se pudo quitar'); });
    });

    $('#btnPsCopiar').on('click', function () {
        var origen = Number($('#psCopiarDe').val()) || 0;
        if (!ps.promo || !origen) { showAppAlert('warning', 'Elige de qué promo copiar', { duration: 3000 }); return; }
        post('copiarCatalogoSushi', { id_origen: origen, id_destino: ps.promo.id_producto }).done(function (r) {
            showAppAlert('success', r.nuevas ? r.nuevas + (r.nuevas === 1 ? ' opción copiada' : ' opciones copiadas') : 'Ya tenía todas esas opciones', { duration: 4000 });
            cargarDatos(ps.promo.id_producto);
        }).fail(function (x) { error(x, 'No se pudo copiar'); });
    });
})();
</script>
