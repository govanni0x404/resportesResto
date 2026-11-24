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
    var obj = agFilas, conCat = agOpc.categorias, cols = 5;
    var activos = $.grep(obj, function (v) { return v.vigente == 't' || v.vigente === true; }).length;
    $('#lblCuantosAg').text(obj.length ? activos + ' activo' + (activos === 1 ? '' : 's') + (obj.length > activos ? ' de ' + obj.length : '') : '');
    if (!obj.length) {
        $("#detalleAgregadosDelProducto").html('<tr><td colspan="' + cols + '" class="text-center text-muted" style="padding:18px !important">Todavía no tiene agregados. Agrégalos arriba.</td></tr>');
        return;
    }
    // v1.35.1: con categorías, agrupados en el orden de las categorías (la del producto agregado), con su nombre arriba
    var orden = {};
    $.each(agOpc.lista_categorias, function (i, c) { orden[c.id] = i; });
    var filas = obj.slice();
    filas.sort(function (a, b) {
        var oa = conCat && a.id_categoria && orden[a.id_categoria] !== undefined ? orden[a.id_categoria] : 9999;
        var ob = conCat && b.id_categoria && orden[b.id_categoria] !== undefined ? orden[b.id_categoria] : 9999;
        return oa - ob || String(a.nombre_producto).localeCompare(String(b.nombre_producto));
    });
    var tbl = '', grupo = null;
    $.each(filas, function (index, v) {
        var activo = v.vigente == 't' || v.vigente === true;
        if (conCat) {
            var g = v.id_categoria && orden[v.id_categoria] !== undefined ? v.id_categoria : 0;
            if (g !== grupo) {
                grupo = g;
                var c = $.grep(agOpc.lista_categorias, function (x) { return x.id === g; })[0];
                tbl += '<tr class="ag-grupo ag-color-' + (c ? orden[g] % 6 : 'otros') + '"><td colspan="' + cols + '">' + (c ? escaparHtml(c.nombre) : 'Sin categoría') + '</td></tr>';
            }
        }
        // v1.35.4: más simple: nombre, cantidad, valor, su impresora y activo (interruptores)
        tbl += '<tr data-id-producto="' + v.id_producto + '" data-id-agregado="' + v.id_agregado + '" class="' + (activo ? '' : 'ag-inactivo') + '">';
        tbl += '<td class="ag-nombre">' + escaparHtml(v.nombre_producto) + '</td>';
        tbl += '<td><input data-campo="cantidad" class="form-control cantidad-agregado-input campo-agregado" type="number" min="1" max="1000" value="' + v.cantidad + '"></td>';
        tbl += '<td><input data-campo="valor" class="form-control valor-agregado-input campo-agregado" type="number" min="0" max="50000" value="' + v.valor + '"></td>';
        tbl += '<td class="text-center" title="' + (v.destino_propio ? 'Sale en ' + escaparHtml(v.destino_agregado || 'sin destino') : 'Sale junto con el producto') + '">'
            + '<label class="agx-mini-switch"><input type="checkbox" class="ck-destino-propio" data-id="' + v.id_agregado + '"' + (v.destino_propio ? ' checked' : '') + '><span></span></label>'
            + (v.destino_propio ? '<span class="agx-dest">' + escaparHtml(v.destino_agregado || 'sin destino') + '</span>' : '') + '</td>';
        tbl += '<td class="text-center"><label class="agx-mini-switch" title="' + (activo ? 'Activo: se ofrece en la Caja' : 'Inactivo: no se ofrece') + '">'
            + '<input type="checkbox" class="ck-activo-agregado" data-id="' + v.id_agregado + '"' + (activo ? ' checked' : '') + '><span></span></label></td>';
        tbl += "</tr>";
    });
    $("#detalleAgregadosDelProducto").html(tbl);
}
// v1.35.4 (pedido del usuario): activar / desactivar con el interruptor de la fila, al tiro y sin preguntar
$(document).on('change', '.ck-activo-agregado', function () {
    var $c = $(this), id = $c.data('id'), on = $c.is(':checked');
    $c.prop('disabled', true);
    $.post(base_url + 'index.php/Productos/' + (on ? 'HabilitarAgregado' : 'DeshabilitarAgregado'), { data: id })
        .done(function () {
            $.each(agFilas, function (i, f) { if (Number(f.id_agregado) === Number(id)) f.vigente = on ? 't' : 'f'; });
            maxAg.activos = $.grep(agFilas, function (v) { return v.vigente == 't' || v.vigente === true; }).length;
            pintarTablaAgregados();
            pintarMaxAgregados();
            toastr.success(on ? 'Agregado activo' : 'Agregado desactivado', '', { timeOut: 1200 });
        })
        .fail(function () {
            $c.prop('checked', !on).prop('disabled', false);
            showAppAlert('error', 'No se pudo cambiar el agregado');
        });
});

// ---- v1.35.1 (pedido del usuario): "Extras con cobro" del producto y categorías de agregados (cada uno solo si está
// activado en Configuraciones). Cuántos extras y el máximo por categoría se ponen en Configuraciones. ----
var agOpc = { disponible: false, extras: false, categorias: false, lista_categorias: [], categoria_productos: {}, extras_producto: false };
function cargarOpcionesAgregados(idProducto) {
    agOpc = { id: idProducto, disponible: false, extras: false, categorias: false, lista_categorias: [], categoria_productos: {}, extras_producto: false };
    pintarOpcionesAgregados();
    $.post(base_url + 'index.php/Productos/opcionesAgregados', { id_producto: idProducto }, null, 'json').done(function (r) {
        if (!r || !r.ok || String(agOpc.id) !== String(idProducto)) return;
        agOpc = $.extend({ id: idProducto }, r, { categoria_productos: r.categoria_productos || {} });
        pintarOpcionesAgregados();
        pintarTablaAgregados();
        if ($('#ckVariosAgregados').is(':checked')) pintarVariosAgregados();
    });
}
function pintarOpcionesAgregados() {
    $('#bloqueExtrasProducto').toggle(!!agOpc.extras);
    $('#ckExtrasProducto').prop('checked', !!agOpc.extras_producto);
    pintarTextoExtras();
}
function pintarTextoExtras() {
    var mx = Number(agOpc.max_extras) || 0, max = maxAg.valor;
    $('#lblExtrasProducto').attr('title', 'Encendido: con el máximo lleno, el cliente puede sumar ' + (mx ? (mx === 1 ? '1 agregado más' : 'hasta ' + mx + ' agregados más') : 'más agregados')
        + ', cobrando su valor; los del máximo van sin costo. Cuántos extras se pone en Configuraciones.');
    pintarResumenReglas();
}
/** v1.35.4: una sola frase con lo que pasa en la Caja */
function pintarResumenReglas() {
    var v = maxAg.valor, n = maxAg.activos, mx = Number(agOpc.max_extras) || 0;
    var ex = agOpc.extras && agOpc.extras_producto;
    var t = v > 0 ? 'En la Caja elige <b>hasta ' + v + '</b>' + (ex ? ' sin costo' : '') + ' de ' + n + (n === 1 ? ' agregado' : ' agregados')
        : 'En la Caja puede elegir <b>todos</b> (' + n + ')';
    if (ex && v > 0) t += ', y ' + (mx ? (mx === 1 ? '<b>1 extra</b>' : '<b>hasta ' + mx + ' extras</b>') : '<b>extras</b>') + ' con cobro';
    if (ex && v === 0) t += '. <span class="text-danger">Pon un máximo para que haya extras</span>';
    $('#lblMaxAgregados').html(t + '.');
    $('#lblNotaExtras').toggle(!!ex);   // v1.35.4: de dónde salen el máximo de extras y su costo
}
$(document).on('change', '#ckExtrasProducto', function () {
    var $c = $(this), on = $c.is(':checked');
    $c.prop('disabled', true);
    $.post(base_url + 'index.php/Productos/opcionesAgregados', { id_producto: $('#txtID').val(), accion: 'extras', valor: on ? 1 : 0 }, null, 'json').done(function () {
        agOpc.extras_producto = on;
        pintarTextoExtras();
        toastr.success(on ? 'Extras con cobro activados en este producto' : 'Extras con cobro apagados en este producto', '', { timeOut: 1800 });
    }).fail(function (x) {
        $c.prop('checked', !on);
        showAppAlert('error', (x.responseJSON && x.responseJSON.error) || 'No se pudo guardar');
    }).always(function () { $c.prop('disabled', false); });
});
// La cantidad o el valor de una fila de la tabla: se guarda al cambiarlo
$(document).on('change', '#detalleAgregadosDelProducto .campo-agregado', function () {
    var $i = $(this), campo = $i.data('campo'), idAg = $i.closest('tr').data('id-agregado');
    $i.prop('disabled', true);
    $.post(base_url + 'index.php/Productos/opcionesAgregados', { id_producto: $('#txtID').val(), accion: 'campo', id_agregado: idAg, campo: campo, valor: $i.val() }, null, 'json')
        .done(function (r) {
            $.each(agFilas, function (i, f) { if (Number(f.id_agregado) === Number(idAg)) f[campo] = r.valor; });
            $i.val(r.valor);
            toastr.success('Guardado', '', { timeOut: 1200 });
        })
        .fail(function (x) { showAppAlert('error', (x.responseJSON && x.responseJSON.error) || 'No se pudo guardar'); })
        .always(function () { $i.prop('disabled', false); });
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
    $('#btnGrabarAgregado').html('<i class="fa fa-plus"></i> ' + (varios ? 'Agregar los marcados' : 'Agregar'));
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
    var op = function (p) {
        if (ya[p.id]) return '<label class="ag-op ya-esta" title="Ya es agregado de este producto"><input type="checkbox" disabled checked><span>' + esc(p.nombre) + '</span><small>Ya está</small></label>';
        return '<label class="ag-op' + (agElegidos[p.id] ? ' elegido' : '') + '" data-id="' + esc(p.id) + '"><input type="checkbox"' + (agElegidos[p.id] ? ' checked' : '') + '><span title="' + esc(p.nombre) + '">' + esc(p.nombre) + '</span></label>';
    };
    // v1.35.1 (pedido del usuario): con categorías, los productos de cada categoría en un cuadro de color con su nombre
    var grupos = [], sinCat = lista;
    if (agOpc.categorias && lista.length) {
        $.each(agOpc.lista_categorias, function (i, c) {
            var de = $.grep(lista, function (p) { return Number(agOpc.categoria_productos[p.id]) === c.id; });
            if (de.length) grupos.push('<div class="ag-varios-cat ag-color-' + (i % 6) + '"><div class="ag-varios-cat-titulo">' + esc(c.nombre) + '</div><div class="ag-varios-cat-lista">' + $.map(de, op).join('') + '</div></div>');
        });
        sinCat = $.grep(lista, function (p) { return !$.grep(agOpc.lista_categorias, function (c) { return c.id === Number(agOpc.categoria_productos[p.id]); }).length; });
        if (grupos.length && sinCat.length) grupos.push('<div class="ag-varios-cat ag-color-otros"><div class="ag-varios-cat-titulo">Sin categoría</div><div class="ag-varios-cat-lista">' + $.map(sinCat, op).join('') + '</div></div>');
    }
    $('#listaVariosAgregados').toggleClass('con-categorias', grupos.length > 0).html(!lista.length ? '<div class="ag-varios-vacio">No hay productos con ese nombre</div>'
        : (grupos.length ? grupos.join('') : $.map(lista, op).join('')));
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
        
        var nombreProductoEnTabla = $(this).find(".ag-nombre").text().trim();   // v1.35.4
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
        return '<span class="ca-chip">' + esc($(this).find('.ag-nombre').text()) + ' <small>x' + esc(cant) + (valor ? ' · +$' + valor.toLocaleString('es-CL') : '') + '</small></span>';
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
    pintarResumenReglas();
    if (typeof pintarTextoExtras === 'function') pintarTextoExtras();   // v1.35.1: el texto de los extras depende del máximo
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
