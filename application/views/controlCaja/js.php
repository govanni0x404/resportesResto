<script>
	// Herramientas > Control de cajas (11-11-2026)
	(function () {
		var base = "<?php echo base_url(); ?>index.php/ControlCaja/";
		var datos = { cajas: [], listas: [], bodegas: [], lista_publica: '', empresas: [], id_empresa: 0, id_sucursal: 0 };
		// 12-11-2026: empresa y sucursal elegidas arriba. Una sucursal sin cajas, o una empresa sin sucursales, se trae sola
		// de la intranet una vez al elegirla.
		var traidas = {};       // id_sucursal => true (ya se consultó la intranet en esta página)
		var empresasTraidas = {};
		var sincronizando = false;
		if (window.toastr) toastr.options.escapeHtml = true;

		function esc(s) {
			return $('<div>').text(s == null ? '' : String(s)).html();
		}
		function error(xhr, porDefecto) {
			var msg = (xhr && xhr.responseJSON && xhr.responseJSON.error) || porDefecto || 'No se pudo completar la operación';
			if (xhr && xhr.status === 401) {
				swal({ title: 'Sesión expirada', text: msg, type: 'warning' }, function () { location.href = "<?php echo base_url(); ?>"; });
				return;
			}
			swal('Error', msg, 'error');
		}
		function vacio(html) {
			$('#cuerpoControlCaja').html('<tr><td colspan="9" class="cc-vacio">' + html + '</td></tr>');
		}

		function opcionesLista(sel) {
			var h = '<option value="0">Pública' + (datos.lista_publica ? ' (' + esc(datos.lista_publica) + ')' : '') + '</option>';
			$.each(datos.listas, function (i, l) {
				h += '<option value="' + l.id_lprecio + '"' + (+l.id_lprecio === sel ? ' selected' : '') + '>' + esc(l.nombre_lprecio) + '</option>';
			});
			return h;
		}
		function opcionesBodega(sel) {
			var h = '<option value="0">Según cada producto</option>';
			$.each(datos.bodegas, function (i, b) {
				h += '<option value="' + b.id_bodega + '"' + (+b.id_bodega === sel ? ' selected' : '') + '>' + esc(b.nombre_bodega) + '</option>';
			});
			return h;
		}

		function porSucursal() {
			return !!(datos.empresas && datos.empresas.length);
		}
		function empresaElegida() {
			return $.grep(datos.empresas || [], function (e) { return e.id === datos.id_empresa; })[0] || null;
		}
		function sucursalElegida() {
			var e = empresaElegida();
			return e ? $.grep(e.sucursales, function (s) { return s.id === datos.id_sucursal; })[0] || null : null;
		}

		function pintarFiltros() {
			var hay = porSucursal();
			$('#filtrosControlCaja').toggle(hay);
			$('#tablaControlCaja').toggleClass('cc-por-sucursal', hay);
			if (!hay) { $('#ayudaControlCaja').text('Las cajas, su número, nombre y terminal vienen de la intranet.'); return; }
			var $e = $('#cboEmpresaCC').empty();
			$.each(datos.empresas, function (i, e) {
				$e.append($('<option>').val(e.id).text((e.rut ? e.rut + ' · ' : '') + (e.nombre || (e.sesion ? 'Empresa' : 'Empresa ' + e.id))
					+ (e.sesion ? ' (inicio de sesión)' : '')));
			});
			$e.val(String(datos.id_empresa));
			var emp = empresaElegida();
			var $s = $('#cboSucursalCC').empty();
			if (emp && emp.sucursales.length) {
				$.each(emp.sucursales, function (i, x) {
					$s.append($('<option>').val(x.id).text(x.nombre + (x.direccion ? ' · ' + x.direccion : '') + (x.comuna ? ', ' + x.comuna : '')
						+ (x.casa_matriz ? ' (casa matriz)' : '')));
				});
				$s.val(String(datos.id_sucursal)).prop('disabled', false);
			} else {
				$s.append($('<option>').val('').text('Sin sucursales: actualiza desde la intranet')).prop('disabled', true);
			}
			var suc = sucursalElegida();
			$('#ayudaControlCaja').text(suc
				? 'Cajas de la sucursal ' + suc.nombre + ' autorizadas en la intranet: número, nombre y terminal vienen de allá.'
				: 'Elige la empresa y la sucursal: sus cajas vienen de la intranet.');
		}

		function pintar() {
			pintarFiltros();
			if (!datos.cajas.length) {
				var emp = porSucursal() ? empresaElegida() : null;
				if (porSucursal() && emp && !emp.sucursales.length) {
					vacio('<i class="fa fa-map-marker"></i>' + esc(emp.nombre || 'Esta empresa') + ' no tiene sucursales cargadas' +
						(emp.estado && emp.estado !== 'Datos de la intranet' ? ' (' + esc(emp.estado) + ')' : '') + '.<br>' +
						'Pulsa <b>Actualizar desde la intranet</b> para traer sus sucursales y cajas.');
				} else if (porSucursal()) {
					vacio('<i class="fa fa-desktop"></i>' + (traidas[datos.id_sucursal]
						? 'La intranet no tiene cajas de dat@Resto autorizadas para esta sucursal.'
						: 'No hay cajas registradas para esta sucursal.<br>Pulsa <b>Actualizar desde la intranet</b> para traerlas.'));
				} else {
					vacio('<i class="fa fa-desktop"></i>No hay cajas registradas para este sistema.<br>' +
						'Pulsa <b>Actualizar desde la intranet</b> para traer las cajas autorizadas de la empresa.');
				}
				$('#pieControlCaja').text('');
				return;
			}
			var h = '';
			$.each(datos.cajas, function (i, c) {
				var uso = c.turno
					? '<span class="cc-chip uso" title="Turno abierto desde ' + esc(c.turno.desde) + '">N° ' + c.turno.id_caja + ' · ' + esc(c.turno.usuario || 'sin usuario') + '</span>' +
						'<span class="cc-sub">desde ' + esc(c.turno.desde) + '</span>'
					: '<span class="cc-sub">—</span>';
				h += '<tr data-id="' + c.id + '" class="' + (c.vigente ? '' : 'cc-no-vigente') + '">' +
					'<td class="text-center">' + (c.caja_n || '') + '</td>' +
					'<td><span class="cc-nombre">' + esc(c.nombre) + '</span>' + (c.administrador ? '<span class="cc-sub">Caja administrador</span>' : '') + '</td>' +
					'<td>' + esc(c.terminal) + '</td>' +
					'<td class="text-center cc-col-sucursal">' + (c.sucursal ? esc(c.sucursal) : (c.id_sucursal || '')) + '</td>' +
					'<td><select class="form-control cc-lista">' + opcionesLista(c.id_lprecio) + '</select></td>' +
					'<td><select class="form-control cc-bodega">' + opcionesBodega(c.id_bodega) + '</select></td>' +
					'<td class="text-center"><label class="cc-switch"><input type="checkbox" class="cc-permitir"' + (c.permitir_cambio_lprecio ? ' checked' : '') + '> ' +
						'<span>' + (c.permitir_cambio_lprecio ? 'Sí' : 'No') + '</span></label></td>' +
					'<td class="text-center"><span class="cc-chip ' + (c.vigente ? 'si">Sí' : 'no">No') + '</span></td>' +
					'<td>' + uso + '</td>' +
					'</tr>';
			});
			$('#cuerpoControlCaja').html(h);
			$('#pieControlCaja').text('Cajas vigentes: ' + datos.cajas.filter(function (c) { return c.vigente; }).length + ' de ' + datos.cajas.length + '.');
		}

		function cargar(idSucursal) {
			vacio('<i class="fa fa-spinner fa-spin"></i> Cargando…');
			$.ajax({ url: base + 'datos', data: { id_sucursal: idSucursal || 0 }, dataType: 'json', cache: false }).done(function (r) {
				datos = r;
				pintar();
				// Lo que aún no está en la base se pide a la intranet (una vez por sucursal / empresa en esta página)
				var emp = empresaElegida();
				if (porSucursal() && emp && !emp.sucursales.length && !empresasTraidas[emp.id]) traerEmpresa(emp.id);
				else if (porSucursal() && datos.id_sucursal && !datos.cajas.length && !traidas[datos.id_sucursal]) sincronizar(true);
			}).fail(function (xhr) {
				var msg = (xhr.responseJSON && xhr.responseJSON.error) || 'No se pudieron cargar las cajas.';
				vacio('<i class="fa fa-exclamation-triangle"></i>' + esc(msg));
				if (xhr.status === 401) error(xhr);
			});
		}

		function cajaDe(tr) {
			var id = +tr.data('id');
			return datos.cajas.filter(function (c) { return c.id === id; })[0];
		}

		// Cada cambio se guarda al instante; si falla, la fila vuelve a lo guardado
		$('#cuerpoControlCaja').on('change', 'select, input', function () {
			var tr = $(this).closest('tr');
			var c = cajaDe(tr);
			if (!c) return;
			var nuevo = {
				id: c.id,
				id_lprecio: +tr.find('.cc-lista').val() || 0,
				id_bodega: +tr.find('.cc-bodega').val() || 0,
				permitir_cambio_lprecio: tr.find('.cc-permitir').is(':checked') ? 1 : 0
			};
			tr.find('.cc-permitir').next('span').text(nuevo.permitir_cambio_lprecio ? 'Sí' : 'No');
			tr.addClass('cc-guardando').find('select, input').prop('disabled', true);
			$.ajax({ url: base + 'guardar', type: 'POST', dataType: 'json', data: nuevo }).done(function () {
				c.id_lprecio = nuevo.id_lprecio;
				c.id_bodega = nuevo.id_bodega;
				c.permitir_cambio_lprecio = !!nuevo.permitir_cambio_lprecio;
				toastr.success('Cambios guardados en ' + (c.nombre || 'la caja'));
			}).fail(function (xhr) {
				tr.find('.cc-lista').val(String(c.id_lprecio));
				tr.find('.cc-bodega').val(String(c.id_bodega));
				tr.find('.cc-permitir').prop('checked', c.permitir_cambio_lprecio).next('span').text(c.permitir_cambio_lprecio ? 'Sí' : 'No');
				error(xhr, 'No se pudieron guardar los cambios');
			}).always(function () {
				tr.removeClass('cc-guardando').find('select, input').prop('disabled', false);
			});
		});

		function ocupado(si) {
			sincronizando = si;
			$('#btnSincronizarCajas').prop('disabled', si).find('i').toggleClass('fa-spin', si);
			$('#cboEmpresaCC, #cboSucursalCC').prop('disabled', si);
			if (!si) pintarFiltros();
		}

		// Cajas de la empresa y sucursal elegidas (sin empresas: las de la empresa de la sesión, como antes)
		function sincronizar(automatico) {
			if (sincronizando) return;
			var idSucursal = datos.id_sucursal;
			if (porSucursal()) {
				var emp = empresaElegida();
				if (emp && !emp.sucursales.length) { traerEmpresa(emp.id, automatico); return; }
				traidas[idSucursal] = true;
				if (automatico) vacio('<i class="fa fa-spinner fa-spin"></i> Trayendo las cajas desde la intranet…');
			}
			ocupado(true);
			$.ajax({ url: base + 'sincronizar', type: 'POST', dataType: 'json',
				data: porSucursal() ? { id_empresa: datos.id_empresa, id_sucursal: idSucursal } : {} }).done(function (r) {
				datos = r;
				pintar();
				var s = r.resultado || {};
				if (!automatico || s.recibidas) {
					toastr.success('Cajas recibidas: ' + (s.recibidas || 0) + '. Nuevas: ' + (s.insertadas || 0) +
						', actualizadas: ' + (s.actualizadas || 0) + (s.desactivadas ? ', quedan no vigentes: ' + s.desactivadas : '') + '.');
				}
			}).fail(function (xhr) {
				if (automatico) pintar();
				error(xhr, 'No se pudo actualizar desde la intranet');
			}).always(function () { ocupado(false); });
		}

		// Empresa sin sucursales cargadas: sus datos, sucursales y cajas desde la intranet
		function traerEmpresa(idEmpresa, automatico) {
			if (sincronizando) return;
			empresasTraidas[idEmpresa] = true;
			vacio('<i class="fa fa-spinner fa-spin"></i> Trayendo las sucursales desde la intranet…');
			ocupado(true);
			$.ajax({ url: base + 'sincronizarEmpresa', type: 'POST', dataType: 'json', data: { id_empresa: idEmpresa } }).done(function (r) {
				var f = r.resultado || {};
				if (!f.ok) toastr.warning((f.rut ? f.rut + ': ' : '') + (f.mensaje || 'No se pudo consultar la intranet'));
				var emp = $.grep(r.empresas || [], function (e) { return e.id === idEmpresa; })[0];
				ocupado(false);
				// La sucursal con que se inició sesión, si es de esta empresa; si no, la primera
				var suc = emp ? ($.grep(emp.sucursales, function (x) { return x.id === datos.id_sucursal_sesion; })[0] || emp.sucursales[0]) : null;
				cargar(suc ? suc.id : 0);
			}).fail(function (xhr) {
				ocupado(false);
				pintar();
				if (!automatico) error(xhr, 'No se pudo actualizar desde la intranet');
			});
		}

		$('#btnSincronizarCajas').on('click', function () {
			if (!$(this).prop('disabled')) sincronizar(false);
		});
		$('#cboEmpresaCC').on('change', function () {
			var id = +$(this).val();
			var emp = $.grep(datos.empresas || [], function (e) { return e.id === id; })[0];
			if (!emp) return;
			if (emp.sucursales.length) { cargar(emp.sucursales[0].id); return; }
			// Sin sucursales: se muestra la empresa elegida y se piden a la intranet
			datos.id_empresa = id;
			datos.id_sucursal = 0;
			datos.cajas = [];
			pintar();
			if (!empresasTraidas[id]) traerEmpresa(id, true);
		});
		$('#cboSucursalCC').on('change', function () {
			var id = +$(this).val();
			if (id) cargar(id);
		});

		cargar(0);
	})();
</script>
