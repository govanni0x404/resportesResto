<script>
//AGREGADOS DEL PRODUCTO
function AbrirModalAgregadosDelProducto(idProducto) {
    // Limpiar campos
    $('#codigoAgregado').val("");
    $('#cantidadAgregado').val("1");
    $('#valorAdicionalAgregado').val("0");
    $('#ckUsarDestinoDeImpresion').prop('checked', false);
    pintarDestinoAgregado();
    $('#ckVigenciaAgregado').prop('checked', true);
    // v1.24.2: al abrir, de uno en uno (como siempre)
    $('#ckVariosAgregados').prop('checked', false);
    modoVariosAgregados(false);
    
    // Obtener ID del producto actual
    var idProductoActual = $('#txtID').val();
    
    if (!idProductoActual || idProductoActual == "0" || idProductoActual == "") {
        showAppAlert('error', "Debe grabar el producto primero antes de agregar productos agregados", {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }
    
    // Cargar listado de productos disponibles
    ListadoDeAgregadosParaElProducto(null, "#cboProductoAgregadoProductos");
    
    // Cargar agregados existentes del producto
    ObtenerAgregadosDelProducto(idProductoActual);
    cargarMaxAgregados(idProductoActual);   // v1.30.3
    cargarOpcionesAgregados(idProductoActual);   // v1.35.0
    $('#valorExtraAgregado').val('0');
    
    $('#ModalAgregadosDelProducto').modal({
        backdrop: 'static',
        keyboard: false
    });
}

function ObtenerAgregadosDelProducto(idProducto) {
    console.log("Obteniendo agregados para producto ID:", idProducto);
    
    $.post(base_url + "index.php/Productos/obtenerAgregadosDelProducto", {
        id_producto_maestro: idProducto
    }, function(datos) {
        console.log("Agregados recibidos:", datos);
        
        var obj = $.parseJSON(datos);
        // v1.32.2: el recuadro del máximo cuenta los agregados activos al día
        if ($.isArray(obj)) { maxAg.activos = $.grep(obj, function (v) { return v.vigente == 't' || v.vigente === true; }).length; pintarMaxAgregados(); }
        agFilas = $.isArray(obj) ? obj : [];
        pintarTablaAgregados();
        if ($('#ckVariosAgregados').is(':checked')) pintarVariosAgregados();   // v1.24.5
    }).fail(function(xhr, status, error) {
        console.error("Error al obtener agregados:", error);
        showAppAlert('error', "Error al cargar agregados: " + error, {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
    });
}

// v1.35.0: la tabla se pinta aparte, para volver a pintarla cuando llegan las opciones (extras / categorías)
var agFilas = [];
function pintarTablaAgregados() {
    var obj = agFilas, conEx = agOpc.extras, conCat = agOpc.categorias, cols = 8 + (conEx ? 1 : 0) + (conCat ? 1 : 0);
    $('#tableAgregados .solo-ag-extras').toggle(!!conEx);
    $('#tableAgregados .solo-ag-categorias').toggle(!!conCat);
    if (!obj.length) {
        $("#detalleAgregadosDelProducto").html('<tr><td colspan="' + cols + '" class="text-center">No hay agregados para este producto</td></tr>');
        pintarLimitesCategoria();
        return;
    }
    // Con categorías: agrupados en el orden de las categorías (los sin categoría al final), con el nombre arriba
    var orden = {};
    $.each(agOpc.lista_categorias, function (i, c) { orden[c.id] = i; });
    var filas = obj.slice();
    if (conCat) filas.sort(function (a, b) {
        var oa = a.id_categoria && orden[a.id_categoria] !== undefined ? orden[a.id_categoria] : 9999;
        var ob = b.id_categoria && orden[b.id_categoria] !== undefined ? orden[b.id_categoria] : 9999;
        return oa - ob || String(a.nombre_producto).localeCompare(String(b.nombre_producto));
    });
    var tbl = '', grupo = null;
    var opcionesCat = function (sel) {
        return '<option value="0">Sin categoría</option>' + $.map(agOpc.lista_categorias, function (c) {
            return '<option value="' + c.id + '"' + (Number(sel) === c.id ? ' selected' : '') + '>' + escaparHtml(c.nombre) + (c.vigente ? '' : ' (inactiva)') + '</option>';
        }).join('');
    };
    $.each(filas, function (index, v) {
        if (conCat) {
            var g = v.id_categoria && orden[v.id_categoria] !== undefined ? v.id_categoria : 0;
            if (g !== grupo) {
                grupo = g;
                var c = $.grep(agOpc.lista_categorias, function (x) { return x.id === g; })[0];
                tbl += '<tr class="ag-grupo"><td colspan="' + cols + '"><i class="fa fa-folder-open-o"></i> ' + (c ? escaparHtml(c.nombre) : 'Sin categoría') + '</td></tr>';
            }
        }
        // AGREGAR data-id-producto para validación de duplicados
        tbl += '<tr data-id-producto="' + v.id_producto + '" data-id-agregado="' + v.id_agregado + '">';
        tbl += "<td>" + v.id_agregado + "</td>";
        tbl += "<td>" + escaparHtml(v.producto_maestro) + "</td>";
        tbl += "<td>" + escaparHtml(v.nombre_producto) + "</td>";
        // v1.35.0: la cantidad y el valor de la tabla se guardan al cambiarlos (antes no se guardaban)
        tbl += '<td style="width:12%" class="text-right"><input id="maxCantidadAgregado_' + v.id_agregado + '" data-campo="cantidad" '
            + 'class="text-right form-control cantidad-agregado-input campo-agregado" type="number" min="1" max="1000" value="' + v.cantidad + '"></td>';
        tbl += '<td style="width:12%" class="text-right"><input id="maxValorAgregado_' + v.id_agregado + '" data-campo="valor" '
            + 'class="text-right form-control valor-agregado-input campo-agregado" type="number" min="0" max="50000" value="' + v.valor + '"></td>';
        if (conEx) tbl += '<td style="width:12%" class="text-right"><input data-campo="valor_extra" title="0 = cobra su valor adicional" '
            + 'class="text-right form-control extra-agregado-input campo-agregado" type="number" min="0" max="500000" value="' + (v.valor_extra || 0) + '"></td>';
        if (conCat) tbl += '<td><select data-campo="categoria" class="form-control input-sm cat-agregado-select campo-agregado">' + opcionesCat(v.id_categoria) + '</select></td>';
        // v1.30.2: "Imprimir en su propio destino" en un agregado ya ingresado (antes solo al crearlo)
        tbl += '<td class="ag-destino-propio"><label style="margin:0; font-weight:600; cursor:pointer; white-space:nowrap">'
            + '<input type="checkbox" class="ck-destino-propio" data-id="' + v.id_agregado + '"' + (v.destino_propio ? ' checked' : '') + '> Sí</label>'
            + '<small class="text-muted" style="display:block">' + (v.destino_propio ? 'Sale en ' + escaparHtml(v.destino_agregado || 'sin destino') : 'Sale con el producto') + '</small></td>';
        tbl += "<td>" + (v.vigente == 't' ? 'SI' : 'NO') + "</td>";
        if (v.vigente == 't') {
            tbl += '<td><button onclick="DeshabilitarAgregado(' + v.id_agregado + ')" type="button" class="btn btn-block btn-danger btn-sm">'
                + '<i class="fa fa-times-circle"></i> DESHABILITAR</button></td>';
        } else {
            tbl += '<td><button onclick="HabilitarAgregado(' + v.id_agregado + ')" type="button" class="btn btn-block btn-primary btn-sm">'
                + '<i class="fa fa-check-circle"></i> HABILITAR</button></td>';
        }
        tbl += "</tr>";
    });
    $("#detalleAgregadosDelProducto").html(tbl);
    pintarLimitesCategoria();
}

// ---- v1.35.0 (pedidos del usuario): agregados extra con costo y categorías de agregados ----
var agOpc = { disponible: false, extras: false, categorias: false, lista_categorias: [], limites: {}, max_extras: 0 };
function cargarOpcionesAgregados(idProducto) {
    agOpc = { id: idProducto, disponible: false, extras: false, categorias: false, lista_categorias: [], limites: {}, max_extras: 0 };
    pintarOpcionesAgregados();
    $.post(base_url + 'index.php/Productos/opcionesAgregados', { id_producto: idProducto }, null, 'json').done(function (r) {
        if (!r || !r.ok || String(agOpc.id) !== String(idProducto)) return;
        agOpc = $.extend({ id: idProducto }, r, { limites: r.limites || {} });
        pintarOpcionesAgregados();
        pintarTablaAgregados();
    });
}
function pintarOpcionesAgregados() {
    var conEx = !!agOpc.extras, conCat = !!agOpc.categorias;
    $('#filaExtraCategoriaAg').toggle(conEx || conCat);
    $('#ModalAgregadosDelProducto').toggleClass('ag-ancho', conEx || conCat);   // más columnas en la tabla
    $('#filaExtraCategoriaAg .solo-ag-extras').toggle(conEx);
    $('#filaExtraCategoriaAg .solo-ag-categorias').toggle(conCat);
    $('#bloqueMaxExtras').toggle(conEx);
    $('#bloqueLimitesCategoria').toggle(conCat);
    var $c = $('#categoriaAgregado'), antes = $c.val();
    $c.html('<option value="0">Sin categoría</option>' + $.map($.grep(agOpc.lista_categorias, function (c) { return c.vigente; }), function (c) {
        return '<option value="' + c.id + '">' + escaparHtml(c.nombre) + '</option>';
    }).join(''));
    if (antes && $c.find('option[value="' + antes + '"]').length) $c.val(antes);
    pintarMaxExtras();
}
/** Lo que se manda al grabar un agregado nuevo (solo si la base tiene las columnas) */
function datosExtraCategoriaAg() {
    if (!agOpc.disponible) return {};
    var d = {};
    if (agOpc.extras) d.valor_extra = Math.max(0, parseInt($('#valorExtraAgregado').val(), 10) || 0);
    if (agOpc.categorias) d.id_categoria = Number($('#categoriaAgregado').val()) || 0;
    return d;
}
function pintarMaxExtras() {
    var v = Number(agOpc.max_extras) || 0;
    $('#txtMaxExtras').val(v > 0 ? v : '').attr('placeholder', '∞');
    $('#btnSinTopeExtras').toggleClass('activo', v === 0);
    $('#bloqueMaxExtras .ag-cont-btn[data-paso="-1"]').prop('disabled', v === 0);
    $('#bloqueMaxExtras .ag-cont-btn[data-paso="1"]').prop('disabled', v >= 100);
    $('#lblMaxExtras').html((maxAg.valor > 0 ? 'Elegidos los ' + maxAg.valor + ' sin costo, ' : 'Lleno el máximo de una categoría, ')
        + (v > 0 ? 'puede sumar <b>hasta ' + v + ' extra' + (v === 1 ? '' : 's') + '</b>' : 'puede sumar <b>los extras que quiera</b>')
        + ', cada uno con su <b>precio extra</b> (columna PRECIO EXTRA; 0 = su valor adicional).');
}
function guardarOpcionAg(datos, $estado, alListo) {
    var id = $('#txtID').val();
    if (!id || id === '0') return;
    if ($estado) $estado.text('Guardando…').removeClass('error');
    return $.post(base_url + 'index.php/Productos/opcionesAgregados', $.extend({ id_producto: id }, datos), null, 'json').done(function (r) {
        if ($estado) $estado.html('<i class="fa fa-check"></i> Guardado');
        if (alListo) alListo(r);
    }).fail(function (x) {
        if ($estado) $estado.text('No se guardó').addClass('error');
        showAppAlert('error', (x.responseJSON && x.responseJSON.error) || 'No se pudo guardar');
    });
}
var timerExtras = null;
function guardarMaxExtras(valor) {
    agOpc.max_extras = Math.max(0, Math.min(100, valor | 0));
    pintarMaxExtras();
    clearTimeout(timerExtras);
    $('#estadoMaxExtras').text('Guardando…').removeClass('error');
    timerExtras = setTimeout(function () { guardarOpcionAg({ accion: 'max_extras', maximo: agOpc.max_extras }, $('#estadoMaxExtras')); }, 500);
}
$(document).on('click', '#bloqueMaxExtras .ag-cont-btn', function () { guardarMaxExtras((Number(agOpc.max_extras) || 0) + Number($(this).data('paso'))); });
$(document).on('click', '#btnSinTopeExtras', function () { guardarMaxExtras(0); });
$(document).on('input', '#txtMaxExtras', function () { this.value = this.value.replace(/\D/g, '').slice(0, 3); });
$(document).on('change', '#txtMaxExtras', function () { guardarMaxExtras(parseInt(this.value, 10) || 0); });
$(document).on('keydown', '#txtMaxExtras', function (e) { if (e.which === 13) { e.preventDefault(); $(this).trigger('change').blur(); } });
$(document).on('input', '#txtExtraTodos', function () { this.value = this.value.replace(/\D/g, '').slice(0, 6); });
$(document).on('keydown', '#txtExtraTodos', function (e) { if (e.which === 13) { e.preventDefault(); $('#btnExtraTodos').click(); } });
$(document).on('click', '#btnExtraTodos', function () {
    var v = parseInt($('#txtExtraTodos').val(), 10) || 0, n = $('#detalleAgregadosDelProducto tr[data-id-agregado]').length;
    if (!n) { showAppAlert('error', 'Este producto todavía no tiene agregados'); return; }
    confirmarSistema('¿Precio extra a todos?', 'Los ' + n + ' agregados de este producto quedarán con precio extra $' + v.toLocaleString('es-CL') + (v ? '.' : ' (cobran su valor adicional).'), 'Sí, poner', function () {
        guardarOpcionAg({ accion: 'extra_todos', valor: v }, $('#estadoMaxExtras'), function () { ObtenerAgregadosDelProducto($('#txtID').val()); });
    });
});
// Un dato de una fila de la tabla (cantidad, valor, precio extra, categoría): se guarda al cambiarlo
$(document).on('change', '#detalleAgregadosDelProducto .campo-agregado', function () {
    var $i = $(this), campo = $i.data('campo'), idAg = $i.closest('tr').data('id-agregado');
    $i.prop('disabled', true);
    guardarOpcionAg({ accion: 'campo', id_agregado: idAg, campo: campo, valor: $i.val() }, null, function (r) {
        $.each(agFilas, function (i, f) { if (Number(f.id_agregado) === Number(idAg)) f[campo === 'categoria' ? 'id_categoria' : campo] = r.valor; });
        if (campo === 'categoria') pintarTablaAgregados();
        else { $i.val(r.valor); toastr.success('Guardado', '', { timeOut: 1200 }); }
    }).always(function () { $i.prop('disabled', false); });
});
/** Límite de cada categoría que tienen los agregados de este producto */
function pintarLimitesCategoria() {
    if (!agOpc.categorias) return;
    var usadas = {};
    $.each(agFilas, function (i, f) { if (f.id_categoria) usadas[f.id_categoria] = (usadas[f.id_categoria] || 0) + 1; });
    var cats = $.grep(agOpc.lista_categorias, function (c) { return usadas[c.id]; });
    $('#listaLimitesCategoria').html(cats.length ? $.map(cats, function (c) {
        var m = Number(agOpc.limites[c.id]) || 0;
        return '<label class="ag-limite" title="Cuántos de ' + escaparHtml(c.nombre) + ' puede elegir (vacío = sin límite)"><span>' + escaparHtml(c.nombre) + '</span>'
            + '<small>' + usadas[c.id] + ' agreg.</small><input type="text" inputmode="numeric" maxlength="3" class="txt-limite-cat" data-cat="' + c.id + '" value="' + (m || '') + '" placeholder="∞"></label>';
    }).join('') : '<div class="ag-limites-vacio"><i class="fa fa-info-circle"></i> Ningún agregado de este producto tiene categoría. Elígela en la columna CATEGORÍA '
        + '(o en Tablas &gt; Categorías de agregados) y aquí aparece para ponerle su límite.</div>');
}
$(document).on('input', '.txt-limite-cat', function () { this.value = this.value.replace(/\D/g, '').slice(0, 3); });
$(document).on('keydown', '.txt-limite-cat', function (e) { if (e.which === 13) { e.preventDefault(); $(this).blur(); } });
$(document).on('change', '.txt-limite-cat', function () {
    var cat = Number($(this).data('cat')), m = parseInt(this.value, 10) || 0;
    guardarOpcionAg({ accion: 'limite_categoria', id_categoria: cat, maximo: m }, null, function (r) {
        agOpc.limites[cat] = r.maximo;
        toastr.success(r.maximo ? 'Límite guardado: hasta ' + r.maximo : 'Sin límite en esa categoría', '', { timeOut: 1500 });
    });
});

$(function () { initValidacionesAgregados(); });   // v1.35.0: una sola vez (antes se volvía a enganchar en cada recarga de la tabla)
function initValidacionesAgregados() {
    // Validación para cantidad
    $(document).on('input', '.cantidad-agregado-input', function() {
        var $input = $(this);
        var value = $input.val();
        
        if (value.length > 4) {
            value = value.slice(0, 4);
        }
        
        var n = parseInt(value) || 1;
        if (n > 1000) n = 1000;
        if (n < 1) n = 1;
        
        $input.val(n);
    });
    
    // Validación para valor adicional
    $(document).on('input', '.valor-agregado-input', function() {
        var $input = $(this);
        var value = $input.val();
        
        if (value.length > 5) {
            value = value.slice(0, 5);
        }
        
        var n = parseInt(value) || 0;
        if (n > 50000) n = 50000;
        if (n < 0) n = 0;
        
        $input.val(n);
    });
}

// ---- v1.24.5: varios agregados a la vez (lista con buscador). "De a uno" funciona como siempre ----
var agElegidos = {};   // id_producto => true
function modoVariosAgregados(varios) {
    varios = !!varios;
    $('#ckVariosAgregados').prop('checked', varios);
    $('.ag-modo-btn').each(function () {
        var on = ($(this).data('modo') === 'varios') === varios;
        $(this).toggleClass('activo', on).attr('aria-selected', on ? 'true' : 'false');
    });
    $('#bloqueUnAgregado').toggle(!varios);
    $('#bloqueVariosAgregados').toggle(varios);
    $('#btnGrabarAgregado').html('<i class="fa fa-check"></i> ' + (varios ? 'GRABAR AGREGADOS' : 'GRABAR AGREGADO'));
    if (varios) { agElegidos = {}; $('#txtBuscarVariosAgregados').val(''); pintarVariosAgregados(); setTimeout(function () { $('#txtBuscarVariosAgregados').focus(); }, 50); }
}
$(document).on('click', '.ag-modo-btn', function () { modoVariosAgregados($(this).data('modo') === 'varios'); });

/** Productos que se pueden elegir: los de la lista "producto de agregado" (sin el mismo producto) */
function productosParaAgregar() {
    var maestro = String($('#txtID').val());
    return $('#cboProductoAgregadoProductos option').map(function () {
        var id = String(this.value);
        return id && id !== '0' && id !== maestro ? { id: id, nombre: $(this).text() } : null;
    }).get();
}
function idsYaAgregados() {
    var ya = {};
    $('#detalleAgregadosDelProducto tr[data-id-producto]').each(function () { ya[String($(this).data('id-producto'))] = true; });
    return ya;
}
function pintarVariosAgregados() {
    var texto = $.trim($('#txtBuscarVariosAgregados').val()).toLowerCase(), ya = idsYaAgregados();
    var todos = productosParaAgregar();
    if (!todos.length) { $('#listaVariosAgregados').html('<div class="ag-varios-vacio"><i class="fa fa-spinner fa-spin"></i> Cargando productos…</div>'); contarVariosAgregados(); return; }
    var lista = $.grep(todos, function (p) { return !texto || p.nombre.toLowerCase().indexOf(texto) >= 0; });
    var esc = function (t) { return $('<div>').text(t).html(); };
    $('#listaVariosAgregados').html(lista.length ? $.map(lista, function (p) {
        if (ya[p.id]) return '<label class="ag-op ya-esta" title="Ya es agregado de este producto"><input type="checkbox" disabled checked><span>' + esc(p.nombre) + '</span><small>Ya está</small></label>';
        return '<label class="ag-op' + (agElegidos[p.id] ? ' elegido' : '') + '" data-id="' + esc(p.id) + '"><input type="checkbox"' + (agElegidos[p.id] ? ' checked' : '') + '><span title="' + esc(p.nombre) + '">' + esc(p.nombre) + '</span></label>';
    }).join('') : '<div class="ag-varios-vacio">No hay productos con ese nombre</div>');
    contarVariosAgregados();
}
function contarVariosAgregados() {
    var n = Object.keys(agElegidos).length;
    $('#lblElegidosAg').text(n === 0 ? 'Ningún producto elegido' : (n === 1 ? '1 producto elegido' : n + ' productos elegidos'));
}
$(document).on('input', '#txtBuscarVariosAgregados', pintarVariosAgregados);
$(document).on('opciones-cargadas', '#cboProductoAgregadoProductos', function () { if ($('#ckVariosAgregados').is(':checked')) pintarVariosAgregados(); });
$(document).on('change', '#listaVariosAgregados .ag-op[data-id] input', function () {
    var $op = $(this).closest('.ag-op'), id = String($op.data('id'));
    if (this.checked) agElegidos[id] = true; else delete agElegidos[id];
    $op.toggleClass('elegido', this.checked);
    contarVariosAgregados();
});
$(document).on('click', '#btnMarcarVisiblesAg', function () {
    $('#listaVariosAgregados .ag-op[data-id]').each(function () { agElegidos[String($(this).data('id'))] = true; });
    pintarVariosAgregados();
});
$(document).on('click', '#btnLimpiarAg', function () { agElegidos = {}; pintarVariosAgregados(); });

/** Graba los agregados elegidos uno tras otro (mismo grabado que de a uno) y avisa cuántos quedaron */
function grabarVariosAgregados() {
    var productoMaestro = $('#txtID').val();
    var elegidos = Object.keys(agElegidos);   // v1.24.5: los marcados en la lista
    var cantidad = $('#cantidadAgregado').val();
    var valor = $('#valorAdicionalAgregado').val();
    if (!elegidos.length) {
        showAppAlert('error', "Elige al menos un producto agregado", { duration: 5000, image: base_url + 'assets/img/errorGif.gif' });
        return;
    }
    if (!cantidad || Number(cantidad) <= 0) {
        showAppAlert('error', "La cantidad del agregado debe ser mayor a 0", { duration: 5000, image: base_url + 'assets/img/errorGif.gif' });
        return;
    }
    if (valor === '' || valor === null) { valor = 0; $('#valorAdicionalAgregado').val('0'); }
    if (Number(valor) < 0) {
        showAppAlert('error', "El valor adicional no puede ser negativo", { duration: 5000, image: base_url + 'assets/img/errorGif.gif' });
        return;
    }
    // Se saltan los que ya están en la lista y el mismo producto
    var yaEstan = {};
    $('#detalleAgregadosDelProducto tr[data-id-producto]').each(function () { yaEstan[String($(this).data('id-producto'))] = true; });
    var nombre = function (id) { return $('#cboProductoAgregadoProductos option[value="' + id + '"]').text(); };
    var saltados = [];
    var pendientes = elegidos.filter(function (id) {
        if (String(id) === String(productoMaestro)) { saltados.push(nombre(id) + ' (es el mismo producto)'); return false; }
        if (yaEstan[String(id)]) { saltados.push(nombre(id) + ' (ya estaba)'); return false; }
        return true;
    });
    if (!pendientes.length) {
        showAppAlert('error', "No hay agregados nuevos: " + saltados.join(', '), { duration: 6000, image: base_url + 'assets/img/errorGif.gif' });
        return;
    }
    var $b = $('#btnGrabarAgregado').prop('disabled', true), ok = 0, errores = [];
    var datos = {
        'id_agregado': "",
        'cantidad': cantidad,
        'vigente': $('#ckVigenciaAgregado').is(":checked") ? 1 : 0,
        'id_producto_maestro': productoMaestro,
        'valor': valor || 0,
        'usar_destino_impresion': $('#ckUsarDestinoDeImpresion').is(":checked") ? 1 : 0
    };
    $.extend(datos, datosExtraCategoriaAg());   // v1.35.0
    (function siguiente(i) {
        if (i >= pendientes.length) {
            $b.prop('disabled', false);
            var texto = ok + (ok === 1 ? ' agregado grabado' : ' agregados grabados')
                + (saltados.length ? '. Se saltaron: ' + saltados.join(', ') : '')
                + (errores.length ? '. No se pudieron grabar: ' + errores.join(', ') : '');
            showAppAlert(errores.length && !ok ? 'error' : 'success', texto, { duration: 6000, image: base_url + 'assets/img/' + (errores.length && !ok ? 'errorGif.gif' : 'success.gif') });
            agElegidos = {};
            ObtenerAgregadosDelProducto(productoMaestro);   // al recargar, la lista de "varios" se vuelve a pintar
            return;
        }
        var id = pendientes[i];
        $.ajax({ url: base_url + "index.php/Productos/grabarAgregados", type: "POST", dataType: "json",
                 data: { prod: $.extend({}, datos, { 'id_producto': id }) } })
            .done(function (r) { if (r && r.error) errores.push(nombre(id) + ': ' + r.error); else { ok++; agregadoGrabadoEnProducto(); } })
            .fail(function (xhr) {
                var m = ''; try { m = (xhr.responseJSON && xhr.responseJSON.error) || JSON.parse(xhr.responseText).error || ''; } catch (e) {}
                errores.push(nombre(id) + (m ? ': ' + m : ''));
            })
            .always(function () { siguiente(i + 1); });
    })(0);
}

function GrabarNuevoAgregados() {
    if ($('#ckVariosAgregados').is(':checked')) { grabarVariosAgregados(); return; }   // v1.24.2
    var msg = "";
    
    // VALIDACIONES
    if (!$('#cboProductoAgregadoProductos').val() || $('#cboProductoAgregadoProductos').val() == "0") {
        msg += "- Producto Agregado\n";
    }
    
    if (!$('#cantidadAgregado').val() || $('#cantidadAgregado').val() == "0" || $('#cantidadAgregado').val() == "") {
        msg += "- Cantidad De Agregado (debe ser mayor a 0)\n";
    }
    
    // VALIDAR que el valor adicional no sea negativo (puede ser 0)
    var valorAdicional = $('#valorAdicionalAgregado').val();
    if (valorAdicional === "" || valorAdicional === null) {
        $('#valorAdicionalAgregado').val("0");
        valorAdicional = 0;
    }
    
    if (parseFloat(valorAdicional) < 0) {
        msg += "- Valor Adicional no puede ser negativo\n";
    }
    
    if (msg.length) {
        showAppAlert('error', "Falta ingresar los siguientes datos:\n" + msg, {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }
    
    var productoMaestro = $('#txtID').val();
    var productoAgregado = $('#cboProductoAgregadoProductos').val();
    var cantidadAgregado = $('#cantidadAgregado').val();
    var valorAdicionalAgregado = $('#valorAdicionalAgregado').val() || 0;
    
    console.log("=== VALIDANDO DUPLICADOS ===");
    console.log("Producto a agregar:", productoAgregado);
    console.log("Producto maestro:", productoMaestro);
    
    // Verificar si el producto ya existe en la lista (CORREGIDO)
    var yaExiste = false;
    $("#detalleAgregadosDelProducto tr").each(function() {
        
        var nombreProductoEnTabla = $(this).find("td").eq(2).text().trim();
        var nombreProductoSeleccionado = $('#cboProductoAgregadoProductos option:selected').text().trim();
        
        console.log("Comparando:", nombreProductoEnTabla, "con", nombreProductoSeleccionado);
        
        if (nombreProductoEnTabla === nombreProductoSeleccionado) {
            yaExiste = true;
            return false; // break
        }
    });
    
    if (yaExiste) {
        showAppAlert('error', "El producto ya se encuentra en la lista de agregados", {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }
    
    var miJson = {
        'id_agregado': "",
        'id_producto': productoAgregado,
        'cantidad': cantidadAgregado,
        'vigente': $('#ckVigenciaAgregado').is(":checked") ? 1 : 0,
        'id_producto_maestro': productoMaestro,
        'valor': valorAdicionalAgregado,
        'usar_destino_impresion': $('#ckUsarDestinoDeImpresion').is(":checked") ? 1 : 0,
    };
    $.extend(miJson, datosExtraCategoriaAg());   // v1.35.0
    
    console.log("Grabando agregado:", miJson);
    
    $.ajax({
        url: base_url + "index.php/Productos/grabarAgregados",
        type: "POST",
        data: { prod: miJson },
        dataType: "json",
        success: function(response) {
            console.log("✓ Agregado grabado:", response);
            agregadoGrabadoEnProducto();   // v1.34.1
            
            showAppAlert('success', "Agregado registrado exitosamente!", {
                duration: 4000,
                image: base_url + 'assets/img/success.gif'
            });
            
            // Limpiar campos
            $('#cboProductoAgregadoProductos').val("0").trigger('change');
            $('#cantidadAgregado').val("1");
            $('#valorAdicionalAgregado').val("0");
            
            // Recargar lista
            setTimeout(function() {
                ObtenerAgregadosDelProducto(productoMaestro);
            }, 300);
        },
        error: function(xhr, status, error) {
            console.error("✗ Error completo:", xhr.responseText);
            
            // 19-11-2026: el aviso del servidor (ej. agregado repetido)
            var msgSrv = ''; try { msgSrv = (xhr.responseJSON && xhr.responseJSON.error) || JSON.parse(xhr.responseText).error || ''; } catch (e) {}
            if (msgSrv) {
                showAppAlert('error', msgSrv, { duration: 6000, image: base_url + 'assets/img/errorGif.gif' });
            } else if (xhr.responseText.indexOf('llave duplicada') > -1 || 
                xhr.responseText.indexOf('duplicate key') > -1) {
                showAppAlert('error', "Este producto ya está agregado. Si no lo ves en la lista, recarga la página.", {
                    duration: 6000,
                    image: base_url + 'assets/img/errorGif.gif'
                });
            } else {
                showAppAlert('error', "Error al grabar agregado: " + error, {
                    duration: 5000,
                    image: base_url + 'assets/img/errorGif.gif'
                });
            }
        }
    });
}

function actualizarCantidadProductoAgregado() {
    var idProducto = $("#txtID").val();
    var detalleAgregados = [];
    
    $("#tableAgregados tbody tr").each(function() {
        var idAgregado = $(this).find("td").eq(0).text().trim();
        var cantidad = $(this).find(".cantidad-agregado-input").val() || 1;
        
        if (idAgregado && idAgregado !== "No hay agregados para este producto") {
            detalleAgregados.push({
                'id_agregado': idAgregado,
                'cantidad': cantidad
            });
        }
    });
    
    if (detalleAgregados.length === 0) {
        console.log("No hay agregados para actualizar");
        return;
    }
    
    console.log("Actualizando cantidades:", detalleAgregados);
    
    $.post(base_url + "index.php/Productos/actualizarCantidadProductoAgregado", {
        detalle: detalleAgregados
    })
    .done(function(datos) {
        console.log("Cantidades actualizadas:", datos);
        showAppAlert('success', "Cantidades actualizadas exitosamente!", {
            duration: 4000,
            image: base_url + 'assets/img/success.gif'
        });
    })
    .fail(function(xhr, status, error) {
        console.error("Error:", xhr.responseText);
        showAppAlert('error', "Error al actualizar cantidades: " + error, {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
    });
}

function actualizarValorAdicionalProductoAgregado() {
    var idProducto = $("#txtID").val();
    var detalleAgregados = [];
    
    $("#tableAgregados tbody tr").each(function() {
        var idAgregado = $(this).find("td").eq(0).text().trim();
        var valor = $(this).find(".valor-agregado-input").val() || 0;
        
        if (idAgregado && idAgregado !== "No hay agregados para este producto") {
            detalleAgregados.push({
                'id_agregado': idAgregado,
                'valor': valor
            });
        }
    });
    
    if (detalleAgregados.length === 0) {
        console.log("No hay agregados para actualizar");
        return;
    }
    
    console.log("Actualizando valores:", detalleAgregados);
    
    $.post(base_url + "index.php/Productos/actualizarValorAdicionalProductoAgregado", {
        detalle: detalleAgregados
    })
    .done(function(datos) {
        console.log("Valores actualizados:", datos);
        showAppAlert('success', "Valores actualizados exitosamente!", {
            duration: 4000,
            image: base_url + 'assets/img/success.gif'
        });
    })
    .fail(function(xhr, status, error) {
        console.error("Error:", xhr.responseText);
        showAppAlert('error', "Error al actualizar valores: " + error, {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
    });
}

$('#btnActualizarAgregado').on('click', function() {
    actualizarCantidadProductoAgregado();
    actualizarValorAdicionalProductoAgregado();
});

function HabilitarAgregado(id) {
    swal({
        title: "¿Está seguro?",
        text: "¿Desea habilitar este agregado?",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#B4432E",
        confirmButtonText: "Sí, habilitar",
        cancelButtonText: "Cancelar",
        closeOnConfirm: true,
        closeOnCancel: true
    }, function(isConfirm) {
        if (isConfirm) {
            var idProducto = $('#txtID').val();
            
            $.post(base_url + "index.php/Productos/HabilitarAgregado", { data: id })
                .done(function(datos) {
                    showAppAlert('success', "Agregado habilitado exitosamente!", {
                        duration: 4000,
                        image: base_url + 'assets/img/success.gif'
                    });
                    ObtenerAgregadosDelProducto(idProducto);
                })
                .fail(function() {
                    showAppAlert('error', "Error al habilitar el agregado", {
                        duration: 5000,
                        image: base_url + 'assets/img/errorGif.gif'
                    });
                });
        }
    });
}

function DeshabilitarAgregado(id) {
    swal({
        title: "¿Está seguro?",
        text: "¿Desea deshabilitar este agregado?",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#C9372C",
        confirmButtonText: "Sí, deshabilitar",
        cancelButtonText: "Cancelar",
        closeOnConfirm: true,
        closeOnCancel: true
    }, function(isConfirm) {
        if (isConfirm) {
            var idProducto = $('#txtID').val();
            
            $.post(base_url + "index.php/Productos/DeshabilitarAgregado", { data: id })
                .done(function(datos) {
                    showAppAlert('success', "Agregado deshabilitado exitosamente!", {
                        duration: 4000,
                        image: base_url + 'assets/img/success.gif'
                    });
                    ObtenerAgregadosDelProducto(idProducto);
                })
                .fail(function() {
                    showAppAlert('error', "Error al deshabilitar el agregado", {
                        duration: 5000,
                        image: base_url + 'assets/img/errorGif.gif'
                    });
                });
        }
    });
}


// v1.34.1 (pedido del usuario en cambios.md): marcar "Con Agregado" habilita Agregados del producto; al grabar un
// agregado el producto queda marcado en la base (lo hace el servidor). Si se sale sin agregar ninguno, la casilla vuelve a
// como estaba en la base. conAgregadoEnBase lo pone jsProductos al abrir el producto.
var conAgregadoEnBase = false;
function agregadoGrabadoEnProducto() {
    conAgregadoEnBase = true;
    $('#ckConAgregado').prop('checked', true);
    $('#btnAgregados').attr('disabled', false);
    if (typeof tablaCartaProductos !== 'undefined') tablaCartaProductos.ajax.reload(null, false);
}

// Detectar cierre del modal de agregados
$(document).ready(function() {
    $('#ModalAgregadosDelProducto').on('hidden.bs.modal', function () {
        // v1.34.1: editando un producto que no tenía agregados: si no se agregó ninguno, vuelve a la normalidad
        if (typeof debeAbrirAgregados !== 'undefined' && debeAbrirAgregados) return;
        if (!conAgregadoEnBase) {
            $('#ckConAgregado').prop('checked', false);
            $('#btnAgregados').attr('disabled', true);
        }
    });
    $('#ModalAgregadosDelProducto').on('hidden.bs.modal', function(e) {
        // ⭐ VERIFICAR SI FUE CANCELACIÓN MANUAL O CIERRE AUTOMÁTICO
        if ($(this).data('cancelado') === true) {
            console.log("✓ Modal de agregados cancelado manualmente");
            $(this).data('cancelado', false); // Resetear flag
            // NO ejecutar finalizarCreacionProductoConAgregados
            return;
        }
        
        // Solo ejecutar si venimos del flujo de creación Y no fue cancelado
        if (typeof debeAbrirAgregados !== 'undefined' && debeAbrirAgregados && datosProductoPendiente !== null) {
            finalizarCreacionProductoConAgregados();
        }
    });
    
    // Mostrar/ocultar botón de finalizar según contexto
    $('#ModalAgregadosDelProducto').on('shown.bs.modal', function() {
        if (typeof debeAbrirAgregados !== 'undefined' && debeAbrirAgregados) {
            $('#btnFinalizarCreacion').show();
            $('#btnCancelarAgregados').show().text('CANCELAR');
        } else {
            $('#btnFinalizarCreacion').hide();
            $('#btnCancelarAgregados').text('SALIR');
        }
    });
});

function cancelarModalAgregados() {
    if (typeof debeAbrirAgregados !== 'undefined' && debeAbrirAgregados) {
        // Marcar que fue cancelado manualmente
        $('#ModalAgregadosDelProducto').data('cancelado', true);
        
        swal({
            title: "¿Cancelar agregados?",
            text: "El producto se guardará sin agregados. ¿Desea continuar?",
            type: "warning",
            showCancelButton: true,
            confirmButtonText: "Sí, cancelar",
            cancelButtonText: "No, volver",
            closeOnConfirm: true
        }, function(isConfirm) {
            if (isConfirm) {
                // Cerrar solo el modal de agregados
                $('#ModalAgregadosDelProducto').modal('hide');
                
                // Resetear variables de flujo
                debeAbrirAgregados = false;
                datosProductoPendiente = null;
                preciosExistentes = [];
                
                // Cerrar modal principal
                $('#modalNuevoProducto').modal('hide');
                
                // Mostrar mensaje
                showAppAlert('success', "Producto guardado sin agregados", {
                    duration: 4000,
                    image: base_url + 'assets/img/success.gif'
                });
                
                // Recargar tabla
                if (typeof tablaCartaProductos !== 'undefined') {
                    tablaCartaProductos.ajax.reload();
                }
            } else {
                // Si decide no cancelar, resetear el flag
                $('#ModalAgregadosDelProducto').data('cancelado', false);
            }
        });
    } else {
        // Modo edición normal - solo cerrar
        $('#ModalAgregadosDelProducto').modal('hide');
    }
}

// Nueva función para finalizar con validación
function finalizarYCerrarAgregados() {
    var cantidadAgregados = $('#tableAgregados tbody tr[data-id-agregado]').length;   // v1.35.0: sin las filas de categoría
    var sinAgregados = cantidadAgregados === 0;
    
    if (cantidadAgregados === 0 || sinAgregados) {
        swal({
            title: "Sin agregados",
            text: "¿Desea continuar sin agregar productos? El producto quedará marcado como 'Con Agregado' pero sin productos asociados.",
            type: "warning",
            showCancelButton: true,
            confirmButtonText: "Sí, continuar",
            cancelButtonText: "No, agregar productos",
            closeOnConfirm: true
        }, function(isConfirm) {
            if (isConfirm) {
                // NO marcar como cancelado - es un cierre válido
                $('#ModalAgregadosDelProducto').data('cancelado', false);
                $('#ModalAgregadosDelProducto').modal('hide');
            }
        });
    } else {
        // Hay agregados - cerrar normalmente
        $('#ModalAgregadosDelProducto').data('cancelado', false);
        $('#ModalAgregadosDelProducto').modal('hide');
    }
}

// ---- v1.24.17: copiar los agregados de este producto a otro o varios ----
var caElegidos = {}, caOrigen = 0;
function abrirCopiarAgregados() {
    caOrigen = Number($('#txtID').val()) || 0;
    var filas = $('#detalleAgregadosDelProducto tr[data-id-producto]');
    if (!caOrigen || !filas.length) {
        showAppAlert('warning', 'Este producto todavía no tiene agregados para copiar', { duration: 4000 });
        return;
    }
    var esc = function (t) { return $('<div>').text(t).html(); };
    $('#caChips').html(filas.map(function () {
        var td = $(this).children('td');
        var cant = $(this).find('.cantidad-agregado-input').val(), valor = Number($(this).find('.valor-agregado-input').val()) || 0;
        return '<span class="ca-chip">' + esc(td.eq(2).text()) + ' <small>x' + esc(cant) + (valor ? ' · +$' + valor.toLocaleString('es-CL') : '') + '</small></span>';
    }).get().join(''));
    $('#caSubtitulo').text('De ' + ($('#txtNombre').val() || 'este producto') + ' a otro o varios productos');
    caElegidos = {};
    $('#txtBuscarCopiarAg').val('');
    $('input[name=caModo][value=0]').prop('checked', true);
    pintarCopiarAg();
    $('#ModalCopiarAgregados').modal('show');
    setTimeout(function () { $('#txtBuscarCopiarAg').focus(); }, 300);
}
function productosParaCopiar() {
    return $('#cboProductoAgregadoProductos option').map(function () {
        var id = Number(this.value);
        return id && id !== caOrigen ? { id: id, nombre: $(this).text() } : null;
    }).get();
}
function pintarCopiarAg() {
    var texto = $.trim($('#txtBuscarCopiarAg').val()).toLowerCase();
    var esc = function (t) { return $('<div>').text(t).html(); };
    var lista = $.grep(productosParaCopiar(), function (p) { return !texto || p.nombre.toLowerCase().indexOf(texto) >= 0; });
    $('#listaCopiarAg').html(lista.length ? $.map(lista, function (p) {
        var on = !!caElegidos[p.id];
        return '<label class="ag-op' + (on ? ' elegido' : '') + '" data-id="' + p.id + '"><input type="checkbox"' + (on ? ' checked' : '') + '><span>' + esc(p.nombre) + '</span></label>';
    }).join('') : '<div class="ag-varios-vacio">No hay productos con ese nombre</div>');
    contarCopiarAg();
}
function contarCopiarAg() {
    var n = Object.keys(caElegidos).length;
    $('#lblCaElegidos').text(n ? (n === 1 ? '1 producto elegido' : n + ' productos elegidos') : 'Ningún producto elegido');
    $('#btnCaCopiar').prop('disabled', !n).html('<i class="fa fa-clone"></i> ' + (n ? 'Copiar a ' + (n === 1 ? '1 producto' : n + ' productos') : 'Copiar'));
}
$(document).on('input', '#txtBuscarCopiarAg', pintarCopiarAg);
$(document).on('change', '#listaCopiarAg .ag-op input', function () {
    var $l = $(this).closest('.ag-op'), id = String($l.data('id'));
    if (this.checked) caElegidos[id] = true; else delete caElegidos[id];
    $l.toggleClass('elegido', this.checked);
    contarCopiarAg();
});
$(document).on('click', '#btnCaMarcar', function () {
    $('#listaCopiarAg .ag-op[data-id]').each(function () { caElegidos[String($(this).data('id'))] = true; });
    pintarCopiarAg();
});
$(document).on('click', '#btnCaLimpiar', function () { caElegidos = {}; pintarCopiarAg(); });
$(document).on('click', '#btnCaCopiar', function () {
    var ids = Object.keys(caElegidos);
    if (!ids.length) return;
    var $b = $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Copiando…');
    $.ajax({ url: base_url + 'index.php/Productos/copiarAgregados', type: 'POST', dataType: 'json',
        data: { id_origen: caOrigen, destinos: ids, actualizar: $('input[name=caModo]:checked').val() } })
        .done(function (r) {
            if (r && r.error) { showAppAlert('error', r.error, { duration: 5000 }); return; }
            var partes = [r.nuevos + (r.nuevos === 1 ? ' agregado nuevo' : ' agregados nuevos')];
            if (r.actualizados) partes.push(r.actualizados + (r.actualizados === 1 ? ' actualizado' : ' actualizados'));
            showAppAlert('success', 'Copiados a ' + (r.productos === 1 ? '1 producto' : r.productos + ' productos') + ': ' + partes.join(', '), { duration: 5000 });
            $('#ModalCopiarAgregados').modal('hide');
            if (typeof tablaCartaProductos !== 'undefined' && tablaCartaProductos && tablaCartaProductos.ajax) tablaCartaProductos.ajax.reload(null, false);
        })
        .fail(function (xhr) {
            var m = (xhr.responseJSON && xhr.responseJSON.error) || 'No se pudieron copiar los agregados';
            showAppAlert('error', m, { duration: 5000 });
        })
        .always(function () { contarCopiarAg(); });
});

// v1.30.1: con "Imprimir en su propio destino" se muestra a qué impresora va a salir el agregado: el destino de impresión
// que el producto agregado tiene en la Carta (ej. SALSA -> BARRA). Se lee de la tabla de la Carta, sin pedir nada al servidor.
function pintarDestinoAgregado() {
    var $i = $('#infoDestinoAgregado');
    if (!$('#ckUsarDestinoDeImpresion').is(':checked')) { $i.hide(); return; }
    var html;
    if ($('#ckVariosAgregados').is(':checked')) {
        html = 'Cada agregado sale en el destino de impresión de su producto (el que tiene en la Carta).';
    } else {
        var id = String($('#cboProductoAgregadoProductos').val() || '0'), fila = null;
        if (id !== '0' && window.tablaCartaProductos) {
            tablaCartaProductos.rows().every(function () { if (String(this.data().id_producto) === id) fila = this.data(); });
        }
        html = fila
            ? 'Sale en: <b>' + escaparHtml(fila.nombre_dimpresion || 'sin destino') + '</b> (el destino de impresión de '
                + escaparHtml(fila.nombre_producto) + ' en la Carta; para cambiarlo, edita ese producto).'
            : 'Sale en el destino de impresión que el producto agregado tiene en la Carta.';
    }
    $i.html('<i class="fa fa-print"></i> ' + html).show();
}
$(document).on('change', '#ckUsarDestinoDeImpresion, #cboProductoAgregadoProductos, #ckVariosAgregados', pintarDestinoAgregado);

// v1.30.2: marcar o desmarcar "Imprimir en su propio destino" en un agregado que ya está en la tabla (se graba al tocarlo)
$(document).on('change', '.ck-destino-propio', function () {
    var $c = $(this), activo = $c.is(':checked');
    $c.prop('disabled', true);
    $.post(base_url + 'index.php/Productos/destinoPropioAgregado', { id_agregado: $c.data('id'), id_producto_maestro: $('#txtID').val(), activo: activo ? 1 : 0 }, null, 'json')
        .done(function () {
            showAppAlert('success', activo ? 'El agregado ahora sale en su propio destino de impresión' : 'El agregado ahora sale junto al producto');
            ObtenerAgregadosDelProducto($('#txtID').val());
        })
        .fail(function (x) {
            $c.prop('checked', !activo);
            showAppAlert('error', (x.responseJSON && x.responseJSON.error) || 'No se pudo cambiar el agregado');
        })
        .always(function () { $c.prop('disabled', false); });
});

// v1.30.3 (pedido del usuario): máximo de agregados que se pueden elegir en la Caja para este producto (0 = sin límite).
// v1.32.2: contador - / + y botón "Sin límite"; se guarda solo al cambiar (sin botón Guardar)
var maxAg = { id: 0, valor: 0, activos: 0, timer: null };
function pintarMaxAgregados() {
    var v = maxAg.valor, n = maxAg.activos;
    $('#txtMaxAgregados').val(v > 0 ? v : '').attr('placeholder', '∞');
    $('#btnSinLimiteAg').toggleClass('activo', v === 0);
    $('#bloqueMaxAgregados .ag-cont-btn[data-paso="-1"]').prop('disabled', v === 0);
    $('#bloqueMaxAgregados .ag-cont-btn[data-paso="1"]').prop('disabled', v >= 100);
    $('#lblMaxAgregados').html(v > 0
        ? 'El cliente elige <b>hasta ' + v + '</b> de ' + n + (n === 1 ? ' agregado activo' : ' agregados activos') + (v >= n && n ? ' (alcanza para todos)' : '') + '.'
        : 'Sin límite: puede elegir los ' + n + (n === 1 ? ' agregado activo' : ' agregados activos') + '.');
    if (typeof pintarMaxExtras === 'function') pintarMaxExtras();   // v1.35.0: el texto de los extras depende del máximo
}
function cargarMaxAgregados(idProducto) {
    maxAg = { id: idProducto, valor: 0, activos: 0, timer: null };
    $('#estadoMaxAgregados').text('').removeClass('error');
    pintarMaxAgregados();
    $.post(base_url + 'index.php/Productos/maxAgregados', { id_producto: idProducto }, null, 'json').done(function (r) {
        if (!r || !r.ok || maxAg.id !== idProducto) return;
        maxAg.valor = r.maximo > 0 ? r.maximo : 0;
        maxAg.activos = r.agregados || 0;
        pintarMaxAgregados();
    });
}
function guardarMaxAgregados(valor) {
    var id = $('#txtID').val();
    if (!id || id === '0') return;
    maxAg.valor = Math.max(0, Math.min(100, valor | 0));
    pintarMaxAgregados();
    clearTimeout(maxAg.timer);
    $('#estadoMaxAgregados').text('Guardando…').removeClass('error');
    maxAg.timer = setTimeout(function () {   // espera a que deje de tocar + / -
        $.post(base_url + 'index.php/Productos/maxAgregados', { id_producto: id, maximo: maxAg.valor }, null, 'json').done(function () {
            $('#estadoMaxAgregados').html('<i class="fa fa-check"></i> Guardado');
        }).fail(function (x) {
            $('#estadoMaxAgregados').text('No se guardó').addClass('error');
            showAppAlert('error', (x.responseJSON && x.responseJSON.error) || 'No se pudo guardar el máximo');
        });
    }, 500);
}
$(document).on('click', '#bloqueMaxAgregados .ag-cont-btn', function () { guardarMaxAgregados(maxAg.valor + Number($(this).data('paso'))); });
$(document).on('click', '#btnSinLimiteAg', function () { guardarMaxAgregados(0); });
$(document).on('input', '#txtMaxAgregados', function () { this.value = this.value.replace(/\D/g, '').slice(0, 3); });
$(document).on('change', '#txtMaxAgregados', function () { guardarMaxAgregados(parseInt(this.value, 10) || 0); });
$(document).on('keydown', '#txtMaxAgregados', function (e) {
    if (e.which === 13) { e.preventDefault(); $(this).trigger('change').blur(); }
    else if (e.which === 38 || e.which === 40) { e.preventDefault(); guardarMaxAgregados(maxAg.valor + (e.which === 38 ? 1 : -1)); }
});
</script>
