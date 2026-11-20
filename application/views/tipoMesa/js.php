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
		function pintar() {
			if (!tipos.length) {
				$('#cuerpoTipoMesa').html('<tr><td colspan="4" class="tm-vacio"><i class="fa fa-tags"></i>Aún no hay tipos de mesa.<br>Pulsa <b>Nuevo tipo</b> para agregar el primero.</td></tr>');
				return;
			}
			$('#cuerpoTipoMesa').html($.map(tipos, function (t) {
				return '<tr data-id="' + t.id + '" class="' + (t.vigente ? '' : 'tm-no-vigente') + '">' +
					'<td><span class="tm-nombre">' + esc(t.nombre) + '</span>' + (t.defecto ? ' <span class="tm-chip si" title="Las mesas sin otro tipo son de este">Por defecto</span>' : '') + '</td>' +
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

		// v1.21.2: el modal es el compartido (tipoMesa/modalTipoMesa.php), el mismo que usa Mesas
		function abrir(t) {
			TipoMesaModal.abrir(t || null, function (r) { tipos = r.tipos || []; pintar(); });
		}
		$('#btnNuevoTipo').on('click', function () { abrir(null); });
		$('#cuerpoTipoMesa').on('click', 'tr[data-id]', function () {
			var id = +$(this).data('id');
			abrir($.grep(tipos, function (t) { return t.id === id; })[0]);
		});

		cargar();
	})();
</script>
