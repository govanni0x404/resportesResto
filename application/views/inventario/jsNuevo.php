<script>
    const BASE_URL = "<?= base_url() ?>";
</script>

<script>
$(document).ready(function () {

    // ================= Nuevo inventario =================
    // Se busca el insumo, se escribe la cantidad y se agrega a la lista; al traspasar se aplica al stock de la bodega.
    let lista = [];            // {id_insumo, nombre, umedida, stock, cantidad}
    let resultados = [], activo = -1, elegido = null, timerBusqueda = null, buscandoN = 0, traspasando = false;

    const modo = () => $('input[name=modoInventario]:checked').val();
    const num = (v) => { const n = parseFloat(String(v).replace(',', '.')); return isFinite(n) ? n : NaN; };
    const fmtCant = (n) => (Math.round(n * 1000) / 1000).toLocaleString('es-CL', { maximumFractionDigits: 3 });
    const nuevoStock = (l) => modo() === 'sumar' ? l.stock + l.cantidad : l.cantidad;

    function mensajeError(x, defecto) {
        try { return (x.responseJSON && x.responseJSON.error) || JSON.parse(x.responseText).error || defecto; } catch (e) { return defecto; }
    }

    function buscar() {
        const texto = $.trim($('#invBuscar').val());
        if (texto.length < 2) { $('#invResultados').hide().empty(); resultados = []; return; }
        const n = ++buscandoN;
        $.ajax({ url: BASE_URL + 'index.php/Inventario/buscar', type: 'POST', dataType: 'json', global: false,
            data: { texto: texto, id_bodega: $('#selectBodega').val() } }).done(function (r) {
            if (n !== buscandoN) return;   // llegó una búsqueda más nueva
            resultados = r || [];
            activo = resultados.length ? 0 : -1;
            pintarResultados();
        });
    }
    function pintarResultados() {
        if (!resultados.length) { $('#invResultados').html('<div class="inv-nada">No se encontraron insumos</div>').show(); return; }
        $('#invResultados').html(resultados.map((r, i) =>
            `<div class="inv-res${i === activo ? ' activo' : ''}" data-i="${i}"><span>${escHtml(r.nombre_insumo)}</span>`
            + `<small>Stock: ${fmtCant(r.stock)} ${escHtml(r.umedida)}</small></div>`).join('')).show();
    }
    function elegir(r) {
        elegido = r;
        $('#invResultados').hide().empty();
        $('#invBuscar').val(r.nombre_insumo);
        $('#invElegido').html(`<b>${escHtml(r.nombre_insumo)}</b><small>Stock actual: ${fmtCant(r.stock)} ${escHtml(r.umedida)}`
            + (r.cbarra ? ` · Código: ${escHtml(r.cbarra)}` : '') + '</small>');
        $('#invCantidad, #invAgregar').prop('disabled', false);
        $('#invCantidad').val('').focus();
    }
    function soltar() {
        elegido = null;
        $('#invElegido').html('<span class="text-muted">Busca y elige un insumo</span>');
        $('#invCantidad').val('').prop('disabled', true);
        $('#invAgregar').prop('disabled', true);
    }

    $('#invBuscar').on('input', function () {
        if (elegido && $(this).val() !== elegido.nombre_insumo) soltar();
        clearTimeout(timerBusqueda);
        timerBusqueda = setTimeout(buscar, 250);
    }).on('keydown', function (e) {
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            if (!resultados.length) return;
            e.preventDefault();
            activo = (activo + (e.key === 'ArrowDown' ? 1 : -1) + resultados.length) % resultados.length;
            pintarResultados();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if ($('#invResultados').is(':visible') && resultados[activo]) elegir(resultados[activo]);
            else if (elegido) $('#invCantidad').focus();
        } else if (e.key === 'Escape') {
            $('#invResultados').hide();
        }
    });
    $('#invResultados').on('mousedown', '.inv-res', function (e) { e.preventDefault(); elegir(resultados[$(this).data('i')]); });
    $('#invBuscar').on('blur', function () { setTimeout(function () { $('#invResultados').hide(); }, 150); });

    function agregar() {
        if (!elegido) { $('#invBuscar').focus(); return; }
        const c = num($('#invCantidad').val());
        if (!isFinite(c) || (modo() === 'sumar' && c === 0) || (modo() === 'contar' && c < 0)) {
            toastr.warning(modo() === 'sumar' ? 'Escribe la cantidad a sumar' : 'Escribe la cantidad contada (0 o más)');
            $('#invCantidad').focus();
            return;
        }
        if (Math.abs(c) > 999999) { toastr.warning('La cantidad es demasiado alta'); return; }
        const ya = lista.find(l => l.id_insumo === elegido.id_insumo);
        if (ya) {
            // Mismo insumo: al sumar se acumula, al contar se reemplaza el conteo
            ya.cantidad = modo() === 'sumar' ? Math.round((ya.cantidad + c) * 1000) / 1000 : c;
            toastr.info(elegido.nombre_insumo + ': cantidad actualizada');
        } else {
            lista.unshift({ id_insumo: elegido.id_insumo, nombre: elegido.nombre_insumo, umedida: elegido.umedida, stock: elegido.stock, cantidad: c });
        }
        pintarLista();
        $('#invBuscar').val('');
        soltar();
        $('#invBuscar').focus();
    }
    $('#invAgregar').on('click', agregar);
    $('#invCantidad').on('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); agregar(); } });

    function pintarLista() {
        if (!lista.length) {
            $('#tbodyNuevo').html('<tr class="inv-vacio"><td colspan="7" class="text-center text-muted"><em>La lista está vacía: busca un insumo, escribe la cantidad y presiona Agregar (o Enter)</em></td></tr>');
        } else {
            $('#tbodyNuevo').html(lista.map((l, i) => {
                const nuevo = nuevoStock(l), dif = nuevo - l.stock;
                return `<tr data-i="${i}"><td>${escHtml(l.nombre)}</td><td class="text-center">${escHtml(l.umedida)}</td>`
                    + `<td class="text-right">${fmtCant(l.stock)}</td>`
                    + `<td class="text-right"><input type="number" step="any" class="form-control inv-cant" value="${l.cantidad}"></td>`
                    + `<td class="text-right"><b>${fmtCant(nuevo)}</b></td>`
                    + `<td class="text-right ${dif > 0 ? 'positivo' : (dif < 0 ? 'negativo' : '')}">${dif > 0 ? '+' : ''}${fmtCant(dif)}</td>`
                    + `<td class="text-center"><button type="button" class="btn btn-xs btn-default inv-quitar" title="Quitar de la lista"><i class="fa fa-trash"></i></button></td></tr>`;
            }).join(''));
        }
        $('#invCuenta').text(lista.length + (lista.length === 1 ? ' insumo' : ' insumos'));
        $('#invTraspasar').prop('disabled', !lista.length || traspasando);
    }
    $('#tbodyNuevo').on('change', '.inv-cant', function () {
        const l = lista[$(this).closest('tr').data('i')], c = num($(this).val());
        if (isFinite(c) && !(modo() === 'contar' && c < 0) && Math.abs(c) <= 999999) l.cantidad = c;
        pintarLista();
    }).on('click', '.inv-quitar', function () {
        lista.splice($(this).closest('tr').data('i'), 1);
        pintarLista();
    });
    $('input[name=modoInventario]').on('change', pintarLista);

    $('#invVaciar').on('click', function () {
        if (!lista.length) return;
        swal({ title: '¿Vaciar la lista?', text: 'Se quitan los ' + lista.length + ' insumos agregados. No se cambia el stock.', type: 'warning',
            showCancelButton: true, confirmButtonText: 'Sí, vaciar', cancelButtonText: 'No' }, function (ok) { if (ok) { lista = []; pintarLista(); } });
    });

    // Al cambiar de bodega el stock mostrado ya no sirve: se pide confirmación si hay una lista armada
    let bodegaAnterior = $('#selectBodega').val();
    $('#selectBodega').on('change', function () {
        if (!lista.length) { bodegaAnterior = $(this).val(); soltar(); $('#invBuscar').val(''); return; }
        const nueva = $(this).val();
        $(this).val(bodegaAnterior);
        swal({ title: '¿Cambiar de bodega?', text: 'La lista tiene el stock de la bodega anterior y se va a vaciar.', type: 'warning',
            showCancelButton: true, confirmButtonText: 'Sí, cambiar', cancelButtonText: 'No' }, function (ok) {
            if (!ok) return;
            lista = []; pintarLista(); soltar(); $('#invBuscar').val('');
            bodegaAnterior = nueva;
            $('#selectBodega').val(nueva).trigger('change');
        });
    });

    $('#invTraspasar').on('click', function () {
        if (!lista.length || traspasando) return;
        const bodega = $('#selectBodega option:selected').text();
        const texto = modo() === 'sumar'
            ? 'Se suman las cantidades al stock de ' + bodega + ' (' + lista.length + ' insumos).'
            : 'El stock de ' + lista.length + ' insumos de ' + bodega + ' queda igual a lo contado.';
        swal({ title: '¿Traspasar el inventario?', text: texto, type: 'warning', showCancelButton: true,
            confirmButtonText: 'Sí, traspasar', cancelButtonText: 'Revisar', closeOnConfirm: false, showLoaderOnConfirm: true }, function (ok) {
            if (!ok) return;
            traspasando = true;
            pintarLista();
            $.ajax({ url: BASE_URL + 'index.php/Inventario/traspasar', type: 'POST', contentType: 'application/json', dataType: 'json',
                data: JSON.stringify({ id_bodega: Number($('#selectBodega').val()), modo: modo(), observacion: $.trim($('#invObservacion').val()),
                    lineas: lista.map(l => ({ id_insumo: l.id_insumo, cantidad: l.cantidad })) }) }).done(function (r) {
                lista = [];
                // Listo: se vuelve al historial de la bodega, donde aparece el inventario recién hecho
                swal({ title: 'Inventario traspasado', text: r.mensaje, type: 'success', confirmButtonText: 'Ver historial' }, function () {
                    window.location.href = BASE_URL + 'index.php/Inventario?bodega=' + encodeURIComponent($('#selectBodega').val());
                });
            }).fail(function (x) {
                swal('No se traspasó', mensajeError(x, 'No se pudo traspasar el inventario'), 'error');
            }).always(function () { traspasando = false; pintarLista(); });
        });
    });

    // Si se sale de la página con una lista armada sin traspasar, el navegador pide confirmación
    $(window).on('beforeunload', function () { if (lista.length && !traspasando) return 'La lista no se ha traspasado'; });

    pintarLista();
    $('#invBuscar').focus();
});
</script>
