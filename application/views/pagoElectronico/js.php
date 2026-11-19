<script>
	// Mantención > Pago electrónico (19-11-2026)
	(function () {
		var base = "<?php echo base_url(); ?>index.php/PagoElectronico/";
		var datos = { maquinas: [], modelos: [], formas_pago: [], formas_sugeridas: [], credenciales: {} };
		var editando = null;
		if (window.toastr) toastr.options.escapeHtml = true;

		// Qué es el terminal ID y la API key en cada tipo de máquina (la clave del panel del programa)
		var AYUDA = {
			GETNET: { terminal: 'El <code>GETNET_TERMINAL_ID</code> del programa.' },
			TRANSBANK: { terminal: 'El <code>TRANSBANK_TERMINAL_ID</code> del programa.' },
			MERCADOPAGO: { terminal: 'El terminal Point (ej. <code>NEWLAND_N950__XXXX</code>), uno de los <code>MP_TERMINAL_ID</code> del programa. La caja debe estar en <code>ALLOWED_MP</code>.',
				apiKey: 'El <b>access token</b> de Mercado Pago. El programa lo usa si tiene <code>ALLOW_MP_TOKEN_IN_REQUEST=true</code>; si no, usa el de su panel.' },
			HAULMER: { terminal: 'El número de serie de la máquina TUU, uno de los <code>TUU_DEVICES</code> del programa.',
				apiKey: 'La <b>API key</b> de TUU. El programa la usa si tiene <code>ALLOW_TUU_KEY_IN_REQUEST=true</code>; si no, usa la de su panel.' }
		};

		function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }
		function error(xhr, porDefecto) {
			var msg = (xhr && xhr.responseJSON && xhr.responseJSON.error) || porDefecto || 'No se pudo completar la operación';
			if (xhr && xhr.status === 401) {
				swal({ title: 'Sesión expirada', text: msg, type: 'warning' }, function () { location.href = "<?php echo base_url(); ?>"; });
				return;
			}
			swal('Error', msg, 'error');
		}
		function modelo(id) {
			return $.grep(datos.modelos, function (x) { return x.id === id; })[0] || null;
		}

		function pintar() {
			var c = datos.credenciales || {};
			$('#avisoCredenciales').toggle(!c.usuario_api || !c.clave_api);
			if (!datos.maquinas.length) {
				$('#cuerpoPagoElectronico').html('<tr><td colspan="6" class="pe-vacio"><i class="fa fa-credit-card"></i>Aún no hay máquinas.<br>Pulsa <b>Nueva máquina</b> para agregar la primera.</td></tr>');
				return;
			}
			var h = '';
			$.each(datos.maquinas, function (i, m) {
				h += '<tr data-id="' + m.id + '" class="' + (m.vigente ? '' : 'pe-no-vigente') + '">' +
					'<td><span class="pe-nombre">' + esc(m.nombre) + '</span>' + (m.tiene_api_key ? '<span class="pe-sub"><i class="fa fa-key"></i> Con API key</span>' : '') + '</td>' +
					'<td>' + esc(m.id_sucursal_pos) + ' / ' + esc(m.nombre_caja_pos) + '</td>' +
					'<td>' + esc(m.terminal_id) + '</td>' +
					'<td>' + $.map(m.formas_nombres, function (n) { return '<span class="pe-chip fp">' + esc(n) + '</span>'; }).join('') + '</td>' +
					'<td class="text-center">' + m.cajas + '</td>' +
					'<td class="text-center"><span class="pe-chip ' + (m.vigente ? 'si">Sí' : 'no">No') + '</span></td>' +
					'</tr>';
			});
			$('#cuerpoPagoElectronico').html(h);
		}

		function cargar() {
			$.ajax({ url: base + 'datos', dataType: 'json', cache: false }).done(function (r) {
				datos = r;
				var $m = $('#peModelo').empty().append($('<option>').val('').text('Elige la máquina…'));
				$.each(datos.modelos, function (i, x) { $m.append($('<option>').val(x.id).text(x.nombre)); });
				pintar();
			}).fail(function (xhr) {
				$('#cuerpoPagoElectronico').html('<tr><td colspan="6" class="pe-vacio"><i class="fa fa-exclamation-triangle"></i>' +
					esc((xhr.responseJSON && xhr.responseJSON.error) || 'No se pudieron cargar las máquinas.') + '</td></tr>');
				if (xhr.status === 401) error(xhr);
			});
		}

		function tipoElegido() {
			var x = modelo($('#peModelo').val());
			return x ? x.tipo : '';
		}
		function aplicarModelo() {
			var a = AYUDA[tipoElegido()] || {};
			$('#ayudaTerminal').html(a.terminal || 'El terminal de la máquina, igual que en el panel del programa.');
			$('#grupoApiKey').toggle(!!a.apiKey);
			$('#ayudaApiKey').html(a.apiKey || '');
		}
		$('#peModelo').on('change', aplicarModelo);

		function abrir(m) {
			editando = m || null;
			$('#resultadoPrueba').empty();
			$('#tituloPagoElectronico').text(m ? 'Editar máquina' : 'Nueva máquina');
			$('#peId').val(m ? m.id : 0);
			$('#peModelo').val(m ? m.modelo : '');
			$('#peSucursal').val(m ? m.id_sucursal_pos : '');
			$('#peCaja').val(m ? m.nombre_caja_pos : '');
			$('#peTimeout').val(m ? m.timeout_seg : 120);
			$('#peTerminal').val(m ? m.terminal_id : '');
			$('#peApiKey').val('').attr('placeholder', m && m.tiene_api_key ? '•••••• (guardada; escribe otra para cambiarla)' : '');
			$('#btnBorrarApiKey').toggle(!!(m && m.tiene_api_key)).data('borrar', false);
			$('#peVigente').prop('checked', m ? m.vigente : true);
			var marcadas = m ? m.formas_pago : datos.formas_sugeridas;
			var $f = $('#peFormas').empty();
			$.each(datos.formas_pago, function (i, f) {
				$f.append($('<label>').append($('<input type="checkbox" name="formas_pago[]">').val(f.id_fpago).prop('checked', marcadas.indexOf(f.id_fpago) >= 0))
					.append(document.createTextNode(f.nombre)));
			});
			if (!datos.formas_pago.length) $f.html('<span class="text-muted">No hay formas de pago (Mantención &rsaquo; Formas de pago).</span>');
			aplicarModelo();
			$('#modalPagoElectronico').modal('show');
		}
		$('#modalPagoElectronico').on('shown.bs.modal', function () { $('#peModelo').focus(); });
		$('#btnNuevaMaquina').on('click', function () { abrir(null); });
		$('#cuerpoPagoElectronico').on('click', 'tr[data-id]', function () {
			var id = +$(this).data('id');
			abrir($.grep(datos.maquinas, function (m) { return m.id === id; })[0]);
		});
		$('#btnBorrarApiKey').on('click', function () {
			$(this).data('borrar', true).hide();
			$('#peApiKey').val('').attr('placeholder', 'Se quitará al guardar');
		});

		$('#frmPagoElectronico').on('submit', function (e) {
			e.preventDefault();
			// La casilla desmarcada no viaja: vigente se manda siempre (1 o 0)
			var d = $.grep($(this).serializeArray(), function (x) { return x.name !== 'vigente'; });
			d.push({ name: 'vigente', value: $('#peVigente').is(':checked') ? 1 : 0 });
			if ($('#btnBorrarApiKey').data('borrar')) d.push({ name: 'borrar_api_key', value: 1 });
			var $b = $('#btnGuardarPos').prop('disabled', true);
			$.ajax({ url: base + 'guardar', type: 'POST', dataType: 'json', data: $.param(d) }).done(function (r) {
				datos = r;
				pintar();
				$('#modalPagoElectronico').modal('hide');
				toastr.success('Máquina guardada');
			}).fail(function (xhr) { error(xhr, 'No se pudo guardar la máquina'); })
				.always(function () { $b.prop('disabled', false); });
		});

		// ---- Probar conexión: desde ESTE equipo (debe ser el de la caja) con los datos del formulario ----
		function aviso(tipo, html) {
			$('#resultadoPrueba').html('<div class="alert alert-' + tipo + '" style="margin:0;text-align:left">' + html + '</div>');
		}
		$('#btnProbarPos').on('click', function () {
			var c = datos.credenciales || {};
			var m = {
				tipo: tipoElegido(), url_servicio: 'http://localhost:5005', usuario_api: c.usuario_api, clave_api: c.clave_api,
				id_sucursal_pos: $.trim($('#peSucursal').val()), nombre_caja_pos: $.trim($('#peCaja').val()).toUpperCase(),
				terminal_id: $.trim($('#peTerminal').val())
			};
			if (!m.tipo) { aviso('warning', '<i class="fa fa-hand-pointer-o"></i> Elige la máquina para probar.'); return; }
			if (!m.usuario_api || !m.clave_api) {
				aviso('warning', '<i class="fa fa-key"></i> Falta el usuario o la clave de la API en <b>Configuraciones &rsaquo; General &rsaquo; Pago electrónico</b>.');
				return;
			}
			var $b = $(this).prop('disabled', true);
			aviso('info', '<i class="fa fa-spinner fa-spin"></i> Probando el programa de pago de este equipo…');
			PagoPos.status(m).then(function (s) {
				if (s.red || s.status !== 200) { aviso('danger', '<i class="fa fa-times-circle"></i> ' + esc(PagoPos.sinPrograma(m))); return null; }
				var dif = $.map(PagoPos.comparar(m, s.json), esc);
				return PagoPos.online(m).then(function (o) {
					var lineas = [];
					if (o.red) lineas.push('<i class="fa fa-times-circle"></i> El programa no respondió la consulta de la máquina.');
					else if (o.status === 401) lineas.push('<i class="fa fa-times-circle"></i> El <b>usuario o la clave API</b> de Configuraciones no coinciden con los del programa.');
					else lineas.push('<i class="fa ' + (o.json.online === false || o.status >= 400 ? 'fa-exclamation-triangle' : 'fa-check-circle') + '"></i> ' +
						esc(o.json.message || (o.status < 400 ? 'Máquina disponible' : 'La máquina no está disponible')));
					$.each(dif, function (i, x) { lineas.push('<i class="fa fa-times-circle"></i> ' + x); });
					var ok = !o.red && o.status < 400 && o.json.online !== false && !dif.length;
					aviso(ok ? 'success' : 'warning', (ok ? '<b>Todo bien.</b> ' : '') + lineas.join('<br>'));
				});
			}).then(function () { $b.prop('disabled', false); }, function () { $b.prop('disabled', false); });
		});

		cargar();
	})();
</script>
