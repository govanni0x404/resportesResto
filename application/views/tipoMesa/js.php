<script>
	// Mantención > Recintos > Tipos de mesa (19-11-2026)
	(function () {
		var base = "<?php echo base_url(); ?>index.php/TipoMesa/";
		var tipos = [];
		if (window.toastr) toastr.options.escapeHtml = true;

		function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }
		function error(xhr, porDefecto) {
			var msg = (xhr && xhr.responseJSON && xhr.responseJSON.error) || porDefecto || 'No se pudo completar la operación';
			if (xhr && xhr.status === 401) {
				swal({ title: 'Sesión expirada', text: msg, type: 'warning' }, function () { location.href = "<?php echo base_url(); ?>"; });
				return;
			}
			swal('Error', msg, 'error');
		}
		// Igual que Mtipomesa::nombre
		function nombreMesa(n, prefijo, espacio) {
			prefijo = $.trim(String(prefijo || '').replace(/\s+/g, ' '));
			return 'Mesa ' + (prefijo ? prefijo + (espacio ? ' ' : '') : '') + n;
		}

		function pintar() {
			if (!tipos.length) {
				$('#cuerpoTipoMesa').html('<tr><td colspan="4" class="tm-vacio"><i class="fa fa-tags"></i>Aún no hay tipos de mesa.<br>Pulsa <b>Nuevo tipo</b> para agregar el primero.</td></tr>');
				return;
			}
			$('#cuerpoTipoMesa').html($.map(tipos, function (t) {
				return '<tr data-id="' + t.id + '" class="' + (t.vigente ? '' : 'tm-no-vigente') + '">' +
					'<td><span class="tm-nombre">' + esc(t.nombre) + '</span></td>' +
					'<td><span class="tm-ejemplo">' + esc(t.ejemplo) + '</span></td>' +
					'<td class="text-center">' + t.mesas + '</td>' +
					'<td class="text-center"><span class="tm-chip ' + (t.vigente ? 'si">Sí' : 'no">No') + '</span></td></tr>';
			}).join(''));
		}
		function cargar() {
			$.ajax({ url: base + 'datos', dataType: 'json', cache: false }).done(function (r) {
				tipos = r.tipos || [];
				pintar();
			}).fail(function (xhr) {
				$('#cuerpoTipoMesa').html('<tr><td colspan="4" class="tm-vacio"><i class="fa fa-exclamation-triangle"></i>' +
					esc((xhr.responseJSON && xhr.responseJSON.error) || 'No se pudieron cargar los tipos de mesa.') + '</td></tr>');
				if (xhr.status === 401) error(xhr);
			});
		}

		function vista() {
			$('#tmVista').text(nombreMesa(1, $('#tmPrefijo').val(), $('#tmEspacio').is(':checked')));
		}
		$('#tmPrefijo').on('input', vista);
		$('#tmEspacio').on('change', vista);

		function abrir(t) {
			$('#tituloTipoMesa').text(t ? 'Editar tipo de mesa' : 'Nuevo tipo de mesa');
			$('#tmId').val(t ? t.id : 0);
			$('#tmNombre').val(t ? t.nombre : '');
			$('#tmPrefijo').val(t ? t.prefijo : '');
			$('#tmEspacio').prop('checked', t ? t.espacio : false);
			$('#tmVigente').prop('checked', t ? t.vigente : true);
			vista();
			$('#modalTipoMesa').modal('show');
		}
		$('#modalTipoMesa').on('shown.bs.modal', function () { $('#tmNombre').focus(); });
		$('#btnNuevoTipo').on('click', function () { abrir(null); });
		$('#cuerpoTipoMesa').on('click', 'tr[data-id]', function () {
			var id = +$(this).data('id');
			abrir($.grep(tipos, function (t) { return t.id === id; })[0]);
		});

		$('#frmTipoMesa').on('submit', function (e) {
			e.preventDefault();
			var d = {
				id: $('#tmId').val(), nombre: $.trim($('#tmNombre').val()), prefijo: $.trim($('#tmPrefijo').val()),
				espacio: $('#tmEspacio').is(':checked') ? 1 : 0, vigente: $('#tmVigente').is(':checked') ? 1 : 0
			};
			if (!d.nombre) { toastr.warning('Escribe el nombre del tipo de mesa'); $('#tmNombre').focus(); return; }
			var $b = $('#btnGuardarTipo').prop('disabled', true);
			$.ajax({ url: base + 'guardar', type: 'POST', dataType: 'json', data: d }).done(function (r) {
				tipos = r.tipos || [];
				pintar();
				$('#modalTipoMesa').modal('hide');
				toastr.success('Tipo de mesa guardado');
			}).fail(function (xhr) { error(xhr, 'No se pudo guardar el tipo de mesa'); })
				.always(function () { $b.prop('disabled', false); });
		});

		cargar();
	})();
</script>
