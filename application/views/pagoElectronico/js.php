<script>
	// Mantención > Pago electrónico (19-11-2026)
	(function () {
		var base = "<?php echo base_url(); ?>index.php/PagoElectronico/";
		var datos = { maquinas: [], modelos: [], tarjetas: [], credenciales: {} };
		var editando = null;
		var pruebaEnCurso = false;
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
				$('#cuerpoPagoElectronico').html('<tr><td colspan="5" class="pe-vacio"><i class="fa fa-credit-card"></i>Aún no hay máquinas.<br>Pulsa <b>Nueva máquina</b> para agregar la primera.</td></tr>');
				return;
			}
			var h = '';
			$.each(datos.maquinas, function (i, m) {
				h += '<tr data-id="' + m.id + '" class="' + (m.vigente ? '' : 'pe-no-vigente') + '">' +
					'<td><span class="pe-nombre">' + esc(m.nombre) + '</span>' + (m.tiene_api_key ? '<span class="pe-sub"><i class="fa fa-key"></i> Con API key</span>' : '') + '</td>' +
					'<td>' + esc(m.id_sucursal_pos) + ' / ' + esc(m.nombre_caja_pos) + '</td>' +
					'<td>' + esc(m.terminal_id) + '</td>' +
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
				$('#cuerpoPagoElectronico').html('<tr><td colspan="5" class="pe-vacio"><i class="fa fa-exclamation-triangle"></i>' +
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
			aplicarModelo();
			$('#modalPagoElectronico').modal('show');
		}
		$('#modalPagoElectronico').on('shown.bs.modal', function () { $('#peModelo').focus(); });
		$('#modalPagoElectronico').on('hide.bs.modal', function (e) {
			if (pruebaEnCurso) e.preventDefault();
		});
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
			if (pruebaEnCurso) return;
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

		// La prueba usa los datos del formulario y queda en el historial de ApiPagoElectronico.
		function aviso(tipo, html) {
			$('#resultadoPrueba').html('<div class="alert alert-' + tipo + '" style="margin:0;text-align:left">' + html + '</div>');
		}
		function mostrarResultadoPrueba(r, txId) {
			var resultado = PagoPos.interpretar(r);
			var referencia = '<br>Referencia del intento: <strong>' + esc((r.json && r.json.transaction_id) || txId) + '</strong>';
			var mensaje = resultado.mensaje ? '<br>' + esc(resultado.mensaje) : '';
			if (resultado.resultado === 'aprobado') {
				aviso('success', '<strong>Cobro de $100 aprobado.</strong>'
					+ (resultado.codigo ? '<br>Autorización: ' + esc(resultado.codigo) : '') + referencia);
			} else if (r.red || resultado.resultado === 'pendiente' || (r.status >= 500 && r.status !== 503)
				|| (r.json && r.json.estado === 'TIMEOUT')) {
				aviso('warning', '<strong>ApiPagoElectronico no confirmó el cobro de $100.</strong>' + mensaje + referencia
					+ '<br>Revisa el terminal o el historial de ApiPagoElectronico antes de repetir la prueba.');
			} else {
				aviso('danger', '<strong>Cobro de $100 no aprobado.</strong>' + mensaje + referencia);
			}
		}

		$('#btnProbarPos').on('click', async function () {
			var $guardar = $('#btnGuardarPos');
			if (pruebaEnCurso || $guardar.prop('disabled') || !$('#frmPagoElectronico')[0].reportValidity()) return;
			var c = datos.credenciales || {};
			var m = {
				tipo: tipoElegido(), url_servicio: 'http://localhost:5005', usuario_api: c.usuario_api, clave_api: c.clave_api,
				id_sucursal_pos: $.trim($('#peSucursal').val()), nombre_caja_pos: $.trim($('#peCaja').val()).toUpperCase(),
				terminal_id: $.trim($('#peTerminal').val()), timeout_seg: Number($('#peTimeout').val()),
				api_key: $.trim($('#peApiKey').val())
			};
			if (!m.tipo || !m.id_sucursal_pos || !m.nombre_caja_pos || !m.terminal_id) {
				aviso('warning', 'Completa la máquina, el ID de sucursal, el nombre de caja y el terminal.');
				return;
			}
			var $b = $(this);
			pruebaEnCurso = true;
			$b.add($guardar).prop('disabled', true);
			aviso('info', '<i class="fa fa-spinner fa-spin"></i> Verificando ApiPagoElectronico en el puerto 5005…');
			try {
				var s = await PagoPos.status(m);
				if (s.red || s.status !== 200 || s.json.status !== 'ok') {
					aviso('danger', '<i class="fa fa-times-circle"></i> ' + esc(s.mensaje || PagoPos.sinPrograma(m))
						+ '<p style="margin-top:8px"><a class="alert-link" href="' + esc(PagoPos.url(m, '/status')) + '" target="_blank" rel="noopener noreferrer">Abrir el estado de ApiPagoElectronico</a></p>');
					return;
				}
				if (!m.usuario_api || !m.clave_api) {
					aviso('warning', 'ApiPagoElectronico está iniciado. Completa el usuario y la clave API en <b>Configuraciones &rsaquo; General &rsaquo; Pago electrónico</b> para enviar el cobro.');
					return;
				}
				if (!window.confirm('ApiPagoElectronico está iniciado. ¿Enviar un cobro real de $100 al terminal ' + m.terminal_id + '?')) {
					aviso('info', 'ApiPagoElectronico está iniciado. Prueba cancelada.');
					return;
				}
				if (!m.api_key && editando && editando.tipo === m.tipo && editando.tiene_api_key && !$('#btnBorrarApiKey').data('borrar')) {
					var clave = await $.ajax({ url: base + 'clavePrueba', type: 'POST', dataType: 'json', data: { id: editando.id, tipo: m.tipo } });
					m.api_key = clave.api_key;
				}
				var txId = 'PRUEBA-' + Array.from(window.crypto.getRandomValues(new Uint8Array(12)), function (byte) {
					return byte.toString(16).padStart(2, '0');
				}).join('');
				aviso('info', '<i class="fa fa-spinner fa-spin"></i> Enviando $100 al terminal ' + esc(m.terminal_id) + '…');
				var r = await PagoPos.cobrar(m, 100, txId, false, undefined, (m.timeout_seg + 30) * 1000);
				mostrarResultadoPrueba(r, txId);
			} catch (xhr) {
				aviso('danger', esc((xhr.responseJSON && xhr.responseJSON.error) || 'No se pudo completar la prueba. Revisa el estado en ApiPagoElectronico.'));
			} finally {
				pruebaEnCurso = false;
				$b.add($guardar).prop('disabled', false);
			}
		});

		cargar();
	})();
	// v1.39.x: la ayuda que cambia según la máquina (Terminal ID, API key) se ve en su (i)
	$(document).on('mouseenter focus', '#modalPagoElectronico .pe-i[data-de]', function () {
		var t = $.trim($($(this).data('de')).text());
		$(this).attr('title', t || 'Sin ayuda para esta máquina');
	});
</script>
