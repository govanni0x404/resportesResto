<?php
/*
 * v1.37.11 (pedido del usuario: "si hay muchos insumos, no me sé el nombre de todos"): buscador de insumos para la receta,
 * como el botón de productos de la Caja. Un botón al lado del insumo abre una ventana con buscador, las familias/grupos y los
 * insumos en tarjetas; al tocar uno queda elegido en el combo (#cboCodBarraInsumoReceta) y el cursor pasa a la cantidad.
 * Lo usan Recetas y la receta de la Carta (los dos cargan recetas/jsUnidadesReceta.php). Lee los insumos del mismo combo.
 */
?>
<style>
.bi-con-boton { position: relative; padding-right: 50px !important; }
.bi-boton { position: absolute; right: 8px; bottom: 0; width: 40px; height: 36px; border: 0; border-radius: 8px;
    background: var(--t-primario, #15803D); color: #fff; font-size: 16px; }
.bi-boton:hover { filter: brightness(1.08); }
#ModalBuscarInsumo { z-index: 1070; }
#ModalBuscarInsumo .modal-dialog { width: 1000px; max-width: calc(100vw - 24px); margin-top: 30px; }
#ModalBuscarInsumo .modal-header { border-top: 3px solid var(--t-primario, #15803D); }
.bi-barra { display: flex; gap: 8px; margin-bottom: 10px; }
.bi-buscar { position: relative; flex: 1 1 auto; }
.bi-buscar i { position: absolute; left: 12px; top: 12px; color: var(--t-texto-tenue, #9CA3AF); }
.bi-buscar input { height: 40px; padding-left: 34px; font-size: 15px; }
.bi-grupos { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px; }
.bi-grupo { padding: 5px 12px; border: 1px solid var(--t-borde, #E5E7EB); border-radius: 16px; background: #fff; font-size: 12.5px; font-weight: 700; color: var(--t-texto, #374151); }
.bi-grupo.activo { background: var(--t-primario, #15803D); border-color: var(--t-primario, #15803D); color: #fff; }
.bi-lista { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 8px; max-height: calc(100vh - 290px); overflow-y: auto; padding: 2px; }
.bi-ins { text-align: left; padding: 10px 12px; border: 1px solid var(--t-borde, #E5E7EB); border-radius: 10px; background: #fff; cursor: pointer; }
.bi-ins:hover, .bi-ins.sel { border-color: var(--t-primario, #15803D); background: var(--t-primario-suave, #E8F5EC); }
.bi-ins b { display: block; font-size: 13.5px; color: var(--t-texto-fuerte, #111827); line-height: 1.25; }
.bi-ins small { display: block; margin-top: 3px; font-size: 11.5px; color: var(--t-texto-suave, #6B7280); }
.bi-ins .bi-sub { display: inline-block; margin-top: 4px; padding: 0 6px; border-radius: 8px; background: #EFF6FF; color: #1D4ED8; font-size: 10.5px; font-weight: 700; }
.bi-vacio { grid-column: 1 / -1; padding: 30px; text-align: center; color: var(--t-texto-tenue, #9CA3AF); }
</style>
<div id="ModalBuscarInsumo" class="modal fade" role="dialog" style="overflow-y: auto;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-search"></i> Buscar insumo</h4>
            </div>
            <div class="modal-body">
                <div class="bi-barra">
                    <div class="bi-buscar"><i class="fa fa-search"></i>
                        <input type="text" class="form-control" id="biBuscar" placeholder="Escribe parte del nombre o el código (ej. har, 12)" autocomplete="off"></div>
                </div>
                <div class="bi-grupos" id="biGrupos"></div>
                <div class="bi-lista" id="biLista"></div>
            </div>
            <div class="modal-footer">
                <span class="pull-left text-muted" id="biCuenta" style="line-height:34px; font-size:12.5px"></span>
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> Salir</button>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var todos = [], grupo = '', sel = 0, visibles = [];
    var esc = function (t) { return $('<div>').text(t == null ? '' : String(t)).html(); };
    var sinTilde = function (t) { return String(t || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); };
    function leer() {
        todos = $('#cboCodBarraInsumoReceta option').map(function () {
            var id = Number(this.value);
            if (!id) return null;
            var $o = $(this);
            return { id: id, nombre: $o.attr('nombreInsumo') || $o.text(), cbarra: $o.attr('cbarra') || '', grupo: $o.attr('grupo') || '',
                unidad: $o.attr('umedida') || '', costo: Number($o.attr('costoPP')) || 0, sub: $o.attr('esReceta') === 't' || $o.attr('esReceta') === 'true' };
        }).get();
    }
    function pintarGrupos() {
        var gs = [];
        $.each(todos, function (i, x) { if (x.grupo && $.inArray(x.grupo, gs) < 0) gs.push(x.grupo); });
        gs.sort();
        $('#biGrupos').html(gs.length > 1 ? '<button type="button" class="bi-grupo' + (grupo ? '' : ' activo') + '" data-g="">Todos</button>'
            + $.map(gs, function (g) { return '<button type="button" class="bi-grupo' + (g === grupo ? ' activo' : '') + '" data-g="' + esc(g) + '">' + esc(g) + '</button>'; }).join('') : '');
    }
    function pintar() {
        var t = sinTilde($.trim($('#biBuscar').val()));
        visibles = $.grep(todos, function (x) {
            if (grupo && x.grupo !== grupo) return false;
            return !t || sinTilde(x.nombre).indexOf(t) >= 0 || sinTilde(x.cbarra).indexOf(t) >= 0;
        });
        sel = 0;
        $('#biLista').html(visibles.length ? $.map(visibles, function (x, i) {
            return '<button type="button" class="bi-ins' + (i === 0 ? ' sel' : '') + '" data-id="' + x.id + '"><b>' + esc(x.nombre) + '</b>'
                + '<small>Cód. ' + esc(x.cbarra) + (x.grupo ? ' · ' + esc(x.grupo) : '') + '</small>'
                + '<small>$' + (Math.round(x.costo * 100) / 100).toLocaleString('es-CL') + ' por ' + esc(x.unidad || 'unidad') + '</small>'
                + (x.sub ? '<span class="bi-sub">Sub receta</span>' : '') + '</button>';
        }).join('') : '<div class="bi-vacio"><i class="fa fa-search"></i><br>No hay insumos con ese nombre</div>');
        $('#biCuenta').text(visibles.length + ' de ' + todos.length + ' insumos · Enter elige el marcado');
    }
    function elegir(id) {
        $('#ModalBuscarInsumo').modal('hide');
        $('#cboCodBarraInsumoReceta').val(String(id)).trigger('change');
        setTimeout(function () { $('#cantidadInsumoReceta').focus().select(); }, 350);
    }
    window.abrirBuscadorInsumos = function () {
        leer();
        if (!todos.length) { toastr.warning('Todavía no se cargan los insumos. Intenta de nuevo en un momento.'); return; }
        grupo = '';
        $('#biBuscar').val('');
        pintarGrupos(); pintar();
        $('#ModalBuscarInsumo').modal('show');
    };
    $('#ModalBuscarInsumo').on('shown.bs.modal', function () { $('#biBuscar').focus(); });
    $('#biBuscar').on('input', pintar).on('keydown', function (e) {
        if (e.which === 13) { e.preventDefault(); if (visibles[sel]) elegir(visibles[sel].id); return; }
        var cols = Math.max(1, Math.floor(($('#biLista').width() || 600) / 198));
        var paso = { 37: -1, 39: 1, 38: -cols, 40: cols }[e.which];
        if (paso && visibles.length) {
            e.preventDefault();
            sel = Math.max(0, Math.min(visibles.length - 1, sel + paso));
            var $b = $('#biLista .bi-ins').removeClass('sel').eq(sel).addClass('sel');
            if ($b[0]) $b[0].scrollIntoView({ block: 'nearest' });
        }
    });
    $('#biGrupos').on('click', '.bi-grupo', function () { grupo = String($(this).data('g') || ''); pintarGrupos(); pintar(); $('#biBuscar').focus(); });
    $('#biLista').on('click', '.bi-ins', function () { elegir(Number($(this).data('id'))); });

    // El botón va al lado del combo del insumo (en Recetas y en la receta de la Carta)
    $(function () {
        var $s = $('#cboCodBarraInsumoReceta');
        if (!$s.length || $s.parent().find('.bi-boton').length) return;
        $s.parent().addClass('bi-con-boton').append('<button type="button" class="bi-boton" title="Buscar insumo (por nombre, código o familia)"><i class="fa fa-th-large"></i></button>');
        $(document).on('click', '.bi-boton', function (e) { e.preventDefault(); abrirBuscadorInsumos(); });
    });
})();
</script>
