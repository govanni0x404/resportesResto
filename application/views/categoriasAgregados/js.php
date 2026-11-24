<script>
	// v1.35.0: Tablas > Categorías de agregados
	(function () {
		var base = "<?php echo base_url(); ?>index.php/CategoriasAgregados/";
		var cats = [], todos = [], actual = null, marcados = {}, originales = {};
		if (window.toastr) toastr.options.escapeHtml = true;

		function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }
		function error(xhr, porDefecto) {
			var msg = (xhr && xhr.responseJSON && xhr.responseJSON.error)
				|| (xhr && xhr.status === 403 ? 'No tienes permiso para cambiar las categorías de agregados' : porDefecto);
			if (xhr && xhr.status === 401) {
				swal({ title: 'Sesión expirada', text: msg, type: 'warning' }, function () { location.href = "<?php echo base_url(); ?>"; });
				return;
			}
			showAppAlert('error', msg);
		}
		function aplicar(r) {
			if (!r.disponible) {
				$('#avisoCategoriasAg').html('<i class="fa fa-exclamation-triangle"></i> Falta actualizar la base de datos: cierra sesión y vuelve a entrar.').show();
				$('#btnNuevaCategoriaAg').prop('disabled', true);
				$('#cuerpoCategoriasAg').html('<tr><td colspan="6" class="ca-vacio"><i class="fa fa-database"></i>Sin actualizar la base no se pueden crear categorías.</td></tr>');
				return;
			}
			cats = r.categorias || [];
			$('#avisoCategoriasAg').toggle(!r.activada).html(r.activada ? '' : '<i class="fa fa-info-circle"></i> La opción está apagada: en la Caja los agregados salen todos juntos. '
				+ 'Actívala en <a href="<?php echo base_url(); ?>index.php/Configuraciones">Configuraciones &rsaquo; Agregados en la Caja &rsaquo; Categorías de agregados</a>.');
			pintar(r.sin_categoria || 0);
		}
		function pintar(sinCat) {
			if (!cats.length) {
				$('#cuerpoCategoriasAg').html('<tr><td colspan="6" class="ca-vacio"><i class="fa fa-th-large"></i>Aún no hay categorías.<br>Presiona <b>Nueva categoría</b> para crear la primera (ej. Proteína).</td></tr>');
				return;
			}
			$('#cuerpoCategoriasAg').html($.map(cats, function (c) {
				return '<tr data-id="' + c.id + '" class="' + (c.vigente ? '' : 'ca-no-vigente') + '" title="Toca la fila para editarla">'
					+ '<td class="text-center">' + c.orden + '</td>'
					+ '<td><span class="ca-nombre">' + esc(c.nombre) + '</span></td>'
					+ '<td class="text-center">' + c.agregados + '</td>'
					+ '<td class="text-center">' + c.productos + '</td>'
					+ '<td class="text-center"><span class="ca-chip ' + (c.vigente ? 'si">Sí' : 'no">No') + '</span></td>'
					+ '<td><div class="ca-botones">'
					+ '<button type="button" class="btn btn-success btn-sm ca-asignar" title="Poner varios productos en esta categoría"><i class="fa fa-list-ul"></i> Productos</button>'
					+ '</div></td></tr>';
			}).join('') + (sinCat ? '<tr><td colspan="6" class="ca-ayuda" style="padding:10px 8px"><i class="fa fa-info-circle"></i> ' + sinCat
				+ (sinCat === 1 ? ' producto que se usa como agregado no tiene' : ' productos que se usan como agregado no tienen') + ' categoría: en la Caja salen en el cuadro "Otros agregados".</td></tr>' : ''));
		}
		function cargar() {
			$.ajax({ url: base + 'datos', dataType: 'json', cache: false }).done(aplicar).fail(function (xhr) {
				$('#cuerpoCategoriasAg').html('<tr><td colspan="6" class="ca-vacio"><i class="fa fa-exclamation-triangle"></i>'
					+ esc((xhr.responseJSON && xhr.responseJSON.error) || 'No se pudieron cargar las categorías.') + '</td></tr>');
				if (xhr.status === 401) error(xhr);
			});
		}
		function categoria(id) { return $.grep(cats, function (c) { return c.id === id; })[0]; }

		// ---- Crear / editar ----
		function abrir(c) {
			$('#tituloCategoriaAg').text(c ? 'Editar categoría' : 'Nueva categoría');
			$('#caId').val(c ? c.id : 0);
			$('#caNombre').val(c ? c.nombre : '');
			$('#caOrden').val(c ? c.orden : '');
			$('#caVigente').prop('checked', c ? !!c.vigente : true);
			$('#modalCategoriaAg').modal('show');
		}
		$('#modalCategoriaAg').on('shown.bs.modal', function () { $('#caNombre').focus(); });
		$('#btnNuevaCategoriaAg').on('click', function () { abrir(null); });
		// v1.35.2 (pedido del usuario): se edita tocando la fila; no se borra, se deja no activa
		$('#cuerpoCategoriasAg').on('click', 'tr[data-id]', function (e) {
			if ($(e.target).closest('.ca-asignar').length) return;
			abrir(categoria(+$(this).data('id')));
		});
		$('#frmCategoriaAg').on('submit', function (e) {
			e.preventDefault();
			var d = { id: $('#caId').val(), nombre: $.trim($('#caNombre').val()), orden: $('#caOrden').val(), vigente: $('#caVigente').is(':checked') ? 1 : 0 };
			if (!d.nombre) { toastr.warning('Escribe el nombre de la categoría'); $('#caNombre').focus(); return; }
			var $b = $('#btnGuardarCategoriaAg').prop('disabled', true);
			$.ajax({ url: base + 'guardar', type: 'POST', dataType: 'json', data: d }).done(function (r) {
				$('#modalCategoriaAg').modal('hide');
				showAppAlert('success', 'Categoría guardada');
				aplicar(r);
			}).fail(function (xhr) { error(xhr, 'No se pudo guardar la categoría'); })
				.always(function () { $b.prop('disabled', false); });
		});
		// ---- Poner varios agregados en una categoría ----
		function abrirAsignar(c) {
			actual = c;
			$('#tituloAsignarAg').text('Productos de ' + c.nombre);
			$('#caBuscar').val('');
			$('#caFiltroEstado').val('todos');
			$('#caLista').html('<div class="ca-vacio"><i class="fa fa-spinner fa-spin"></i> Cargando productos…</div>');
			$('#caContador, #caCambios').text('');
			$('#modalAsignarAg').modal('show');
			$.ajax({ url: base + 'agregados', dataType: 'json', cache: false }).done(function (r) {
				todos = r.agregados || [];
				marcados = {}; originales = {};
				$.each(todos, function (i, a) { if (a.id_categoria === c.id) { marcados[a.id] = true; originales[a.id] = true; } });
				$('#caFiltroProducto').val('1');
				pintarLista();
			}).fail(function (xhr) { $('#modalAsignarAg').modal('hide'); error(xhr, 'No se pudieron cargar los productos'); });
		}
		$('#modalAsignarAg').on('shown.bs.modal', function () { $('#caBuscar').focus(); });
		$('#cuerpoCategoriasAg').on('click', '.ca-asignar', function () { abrirAsignar(categoria(+$(this).closest('tr').data('id'))); });
		function visibles() {
			var t = $.trim($('#caBuscar').val()).toLowerCase(), p = +$('#caFiltroProducto').val(), e = $('#caFiltroEstado').val();
			return $.grep(todos, function (a) {
				if (p && !a.usado && !marcados[a.id]) return false;   // "Los que son agregados" (y los ya marcados)
				if (e === 'sin' && a.id_categoria) return false;
				if (e === 'esta' && a.id_categoria !== actual.id) return false;
				return !t || a.agregado.toLowerCase().indexOf(t) >= 0;
			});
		}
		function pintarLista() {
			var lista = visibles();
			$('#caLista').html(lista.length ? $.map(lista, function (a) {
				var c = a.id_categoria ? categoria(a.id_categoria) : null, m = !!marcados[a.id];
				var tag = a.id_categoria === actual.id ? '<span class="ca-actual esta">En esta</span>'
					: (c ? '<span class="ca-actual" title="Al guardar marcado, se cambia a ' + esc(actual.nombre) + '">' + esc(c.nombre) + '</span>' : '<span class="ca-actual">Sin categoría</span>');
				return '<label class="ca-op' + (m ? ' marcado' : '') + '" data-id="' + a.id + '"><input type="checkbox"' + (m ? ' checked' : '') + '>'
					+ '<span class="ca-ag"><b>' + esc(a.agregado) + '</b><small>' + (a.usado ? 'Agregado en ' + a.usado + (a.usado === 1 ? ' producto' : ' productos') : 'Todavía no es agregado de ningún producto') + '</small></span>' + tag + '</label>';
			}).join('') : '<div class="ca-vacio">No hay productos con ese filtro.</div>');
			contar();
		}
		function cambios() {
			var poner = [], quitar = [];
			$.each(todos, function (i, a) {
				if (marcados[a.id] && !originales[a.id]) poner.push(a.id);
				if (!marcados[a.id] && originales[a.id]) quitar.push(a.id);
			});
			return { poner: poner, quitar: quitar };
		}
		function contar() {
			var n = Object.keys(marcados).length, c = cambios();
			$('#caContador').html('<b>' + n + '</b> ' + (n === 1 ? 'producto' : 'productos') + ' en ' + esc(actual.nombre));
			$('#caCambios').text(c.poner.length || c.quitar.length ? 'Por guardar: ' + (c.poner.length ? c.poner.length + ' a poner' : '')
				+ (c.poner.length && c.quitar.length ? ' y ' : '') + (c.quitar.length ? c.quitar.length + ' a quitar' : '') : 'Sin cambios');
		}
		$('#caBuscar').on('input', pintarLista);
		$('#caFiltroProducto, #caFiltroEstado').on('change', pintarLista);
		$('#caLista').on('change', '.ca-op input', function () {
			var id = +$(this).closest('.ca-op').data('id');
			if (this.checked) marcados[id] = true; else delete marcados[id];
			$(this).closest('.ca-op').toggleClass('marcado', this.checked);
			contar();
		});
		$('#caMarcarVisibles').on('click', function () { $.each(visibles(), function (i, a) { marcados[a.id] = true; }); pintarLista(); });
		$('#caDesmarcarVisibles').on('click', function () { $.each(visibles(), function (i, a) { delete marcados[a.id]; }); pintarLista(); });
		$('#btnGuardarAsignarAg').on('click', function () {
			var c = cambios();
			if (!c.poner.length && !c.quitar.length) { $('#modalAsignarAg').modal('hide'); return; }
			var $b = $(this).prop('disabled', true);
			$.ajax({ url: base + 'asignar', type: 'POST', dataType: 'json', data: { id_categoria: actual.id, poner: c.poner, quitar: c.quitar } }).done(function (r) {
				$('#modalAsignarAg').modal('hide');
				showAppAlert('success', (r.puestos ? r.puestos + (r.puestos === 1 ? ' producto puesto' : ' productos puestos') + ' en ' + actual.nombre : '')
					+ (r.puestos && r.quitados ? ' y ' : '') + (r.quitados ? r.quitados + (r.quitados === 1 ? ' quitado' : ' quitados') : '') || 'Sin cambios');
				aplicar(r);
			}).fail(function (xhr) { error(xhr, 'No se pudieron guardar los productos'); })
				.always(function () { $b.prop('disabled', false); });
		});

		cargar();
	})();
</script>
