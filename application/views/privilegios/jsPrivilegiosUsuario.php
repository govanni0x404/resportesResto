<script>
	(function () {
		var base = "<?php echo base_url(); ?>index.php/PrivilegiosUsuario/";
		var hayTabla = <?php echo $hayTabla ? 'true' : 'false' ?>;
		if (!hayTabla) return;

		function error(x, defecto) {
			var m = defecto;
			try { m = (x.responseJSON && x.responseJSON.error) || JSON.parse(x.responseText).error || defecto; } catch (e) {}
			swal('Atención', m, 'warning');
		}

		function rol() { return parseInt($('#selectRol').val(), 10) || 0; }

		$.ajax({ url: base + 'ListadoDeRoles', type: 'POST', dataType: 'json' }).done(function (r) {
			var $s = $('#selectRol');
			$.each(r.roles || [], function (i, f) { $s.append($('<option>').val(f.id_rol).text(f.nombre_rol)); });
		}).fail(function (x) { error(x, 'No se pudieron cargar los roles'); });

		function cargar() {
			if (!rol()) { $('#divPrivilegios').hide(); return; }
			$.ajax({ url: base + 'ListarPrivilegiosPorRol', type: 'POST', data: { id_rol: rol() }, dataType: 'json' }).done(function (r) {
				var html = '';
				$.each(r.grupos || [], function (i, g) {
					var activos = g.modulos.filter(function (m) { return m.autoriza; }).length;
					html += '<div class="t-priv-grupo"><h5>' + escHtml(g.grupo) + ' <small>' + activos + ' de ' + g.modulos.length + ' habilitados</small></h5><div class="t-priv-lista">';
					$.each(g.modulos, function (j, m) {
						html += '<label>' + escHtml(m.nombre) + ' <input type="checkbox" class="privilegio-check" data-modulo="' + escHtml(m.modulo) + '"'
							+ (m.autoriza ? ' checked' : '') + '></label>';
					});
					html += '</div></div>';
				});
				$('#gruposPrivilegios').html(html);
				$('#divPrivilegios').show();
			}).fail(function (x) { error(x, 'No se pudieron cargar los permisos'); });
		}

		function guardar(modulo, autoriza, listo) {
			return $.ajax({ url: base + 'guardar', type: 'POST', dataType: 'json',
				data: { id_rol: rol(), modulo: modulo, autoriza: autoriza ? 1 : 0 } })
				.done(function () { if (listo) listo(); })
				.fail(function (x) { error(x, 'No se pudo guardar el permiso'); cargar(); });
		}

		$('#selectRol').on('change', cargar);

		$('#gruposPrivilegios').on('change', '.privilegio-check', function () {
			var $c = $(this);
			guardar($c.data('modulo'), $c.is(':checked'), function () {
				var $g = $c.closest('.t-priv-grupo'), total = $g.find('.privilegio-check').length;
				$g.find('h5 small').text($g.find('.privilegio-check:checked').length + ' de ' + total + ' habilitados');
				toastr.success('Permiso guardado');
			});
		});

		function todos(autoriza) {
			if (!rol()) return;
			swal({
				title: '¿Está seguro?',
				text: autoriza ? 'Este rol podrá entrar a todos los módulos, incluidos Usuarios y Configuraciones.'
					: 'Este rol no podrá entrar a ningún módulo de la web, ni a la Caja.',
				type: 'warning', showCancelButton: true, confirmButtonColor: '#B4432E',
				confirmButtonText: autoriza ? 'Sí, habilitar todo' : 'Sí, quitar todo', cancelButtonText: 'Cancelar'
			}, function (ok) {
				if (ok) guardar('*', autoriza, function () { toastr.success('Permisos guardados'); cargar(); });
			});
		}
		$('#btnHabilitarTodos').on('click', function () { todos(true); });
		$('#btnBloquearTodos').on('click', function () { todos(false); });
	})();
</script>
