		// ---------------- ventas de la caja ----------------
		function cargarVentas() {
			get('ventas').done(function (r) {
				var $t = $('#tablaVentas tbody').empty();
				if (!r.ventas.length) $t.append('<tr><td colspan="7" class="text-center text-muted">Aún no hay ventas en esta caja</td></tr>');
				$.each(r.ventas, function (i, v) {
					// 19-11-2026: "Anular Boleta electronica", "Anular Comprobante"...: el botón dice qué documento anula
					var docNombre = String(v.nombre_tdocumento || 'documento').toLowerCase();
					var anularTxt = 'Anular ' + docNombre.charAt(0).toUpperCase() + docNombre.slice(1);
					var acciones = '<a class="btn btn-xs btn-default" target="_blank" href="' + base + 'ticket/' + v.id_venta + '?imprimir=1" title="Ticket"><i class="fa fa-print"></i></a> '
						+ '<a class="btn btn-xs btn-default" target="_blank" href="' + base + 'pdf/' + v.id_venta + '" title="PDF"><i class="fa fa-file-pdf-o"></i></a> '
						+ (v.anulada ? '<span class="label label-danger">Anulada</span>'
							: (v.pendiente_dte
								? '<button class="btn btn-xs btn-info reenviar" data-id="' + v.id_venta + '" title="Reintentar la emisión ante el SII"><i class="fa fa-refresh"></i> Reenviar</button> ' : '')
							+ (!v.es_nc && !v.cortesia && v.total > 0 && !v.pendiente_dte
								? (v.electronico
									? '<button class="btn btn-xs btn-danger devolver" data-id="' + v.id_venta + '" data-anular="1" title="Anula con nota de crédito electrónica (todo o algunos productos)"><i class="fa fa-ban"></i> ' + esc(anularTxt) + '</button> '
									: '<button class="btn btn-xs btn-warning devolver" data-id="' + v.id_venta + '" title="Devolver productos"><i class="fa fa-undo"></i> Devolver</button> ') : '')
							// Un documento electrónico ya emitido no se anula: se devuelve con nota de crédito
							+ (v.electronico && !v.pendiente_dte
								? (v.es_nc ? '<span class="label label-success" title="Emitida ante el SII: no se anula">Emitida</span>' : '')
								: '<button class="btn btn-xs btn-danger anular" data-id="' + v.id_venta + '" data-folio="' + v.folio + '" title="Anula el documento completo"><i class="fa fa-ban"></i> ' + esc(anularTxt) + '</button>'));
					$t.append('<tr class="' + (v.anulada ? 'anulada' : '') + (v.es_nc ? ' nc' : '') + '">'
						+ '<td>' + esc(v.hora) + '</td><td>' + esc(v.nombre_tdocumento) + (v.cortesia ? ' <span class="label label-warning">Cortesía</span>' : '')
						+ (v.es_nc && v.referencia ? '<br><small class="text-muted">Ref. ' + esc(v.referencia) + '</small>' : '')
						+ (v.pendiente_dte ? ' <span class="label label-danger">Pendiente SII</span>' : '') + '</td>'
						+ '<td>' + esc(v.folio) + '</td><td>' + esc(v.nombre_cliente) + '</td><td>' + esc(v.pagos) + '</td>'
						+ '<td class="caja-num">' + fmt(v.total) + '</td><td class="acciones" style="white-space:nowrap">' + acciones + '</td></tr>');
				});
			});
		}

		$('#lnkVentas').on('shown.bs.tab', cargarVentas);

		$('#tablaVentas').on('click', '.reenviar', function () {
			var $b = $(this).prop('disabled', true);
			post('reenviarDte', { id_venta: $b.data('id') }).done(function (r) {
				toastr.success('Documento emitido: folio ' + r.folio);
				cargarVentas();
			}).always(function () { $b.prop('disabled', false); });
		});

		$('#tablaVentas').on('click', '.anular', function () {
			var id = $(this).data('id'), folio = $(this).data('folio');
			swal({
				title: 'Anular venta folio ' + folio,
				text: 'Se devuelve el stock y se elimina el pago. Indica el motivo:',
				type: 'input',
				showCancelButton: true,
				closeOnConfirm: false,
				confirmButtonText: 'Anular',
				cancelButtonText: 'Cancelar',
				inputPlaceholder: 'Motivo'
			}, function (motivo) {
				if (motivo === false) return false;
				if ($.trim(motivo) === '') { swal.showInputError('Debes indicar el motivo'); return false; }
				swal.close();
				enviarConAutorizacion(function (aut) {
					return $.ajax({ url: base + 'anular', type: 'POST', dataType: 'json', data: { id_venta: id, motivo: motivo, autorizacion: aut, local: comprobanteLocal() } });
				}, function (r) {
					toastr.success('Venta folio ' + folio + ' anulada');
					if (r && r.impresion && imprimirComprobanteLocal(r.impresion, 'Comprobante de anulación')) {
						// sale por el agente de este equipo
					} else if (r && r.impresion) {
						if (r.impresion.ok) toastr.success('Comprobante de anulación enviado a la impresora', '', { timeOut: 2500 });
						else toastr.error('Comprobante de anulación: ' + r.impresion.detalle, '', { timeOut: 9000 });
					}
					if (r && r.correo && !r.correo.ok) toastr.error('Correo de la anulación: ' + r.correo.detalle, '', { timeOut: 9000 });
					cargarVentas();
				});
			});
		});

		// ---------------- créditos de clientes ----------------
		// Lista de clientes con deuda; al elegir uno se ven sus ventas a crédito pendientes (con su detalle), se marcan las
		// que paga y se registra el pago (queda en la caja y sale el comprobante en la impresora de caja).
		var cred = { clientes: [], cliente: null, pendientes: [] };
		function cargarClientesCredito() {
			return get('clientesConDeuda').done(function (r) {
				cred.clientes = r.clientes || [];
				pintarClientesCredito();
			});
		}
		function pintarClientesCredito() {
			var q = $.trim($('#credBuscar').val()).toUpperCase().replace(/\./g, '');
			var lista = $.grep(cred.clientes, function (c) {
				return !q || c.nombre.toUpperCase().indexOf(q) >= 0 || c.rut.replace(/\./g, '').indexOf(q) >= 0;
			});
			if (!lista.length) {
				$('#credClientes').html('<div class="text-muted text-center" style="padding:20px">' + (cred.clientes.length ? 'Ningún cliente coincide' : 'Ningún cliente debe créditos') + '</div>');
				return;
			}
			$('#credClientes').html($.map(lista, function (c) {
				return '<a class="list-group-item' + (cred.cliente && cred.cliente.id_cliente === c.id_cliente ? ' active' : '') + '" data-id="' + c.id_cliente + '">'
					+ '<span class="cred-monto">' + fmt(c.deuda) + '</span><b>' + esc(c.nombre) + '</b><br><small class="text-muted">'
					+ esc(c.rut) + ' · ' + c.ventas + ' venta(s)</small>'
					+ (c.vencido ? '<br><span class="cred-vencido"><i class="fa fa-exclamation-triangle"></i> Vencido ' + fmt(c.vencido) + '</span>' : '')
					+ '</a>';
			}).join(''));
		}
		$('#credBuscar').on('input', pintarClientesCredito);
		$('#credClientes').on('click', '.list-group-item', function () { cargarCreditosCliente(Number($(this).data('id'))); });
		$('#lnkCreditos').on('shown.bs.tab', function () { cargarClientesCredito(); if (cred.cliente) cargarCreditosCliente(cred.cliente.id_cliente); });

		function cargarCreditosCliente(id) {
			$('#credDetalle').html('<p class="text-muted text-center" style="margin:30px 0"><i class="fa fa-spinner fa-spin"></i> Cargando…</p>');
			return get('creditosCliente', { id_cliente: id }).done(function (r) {
				cred.cliente = r.cliente;
				cred.pendientes = r.pendientes || [];
				pintarClientesCredito();
				pintarCreditosCliente(r);
			});
		}
		function pintarCreditosCliente(r) {
			var c = r.cliente;
			$('#credTitulo').text(c.nombre);
			$('#credSubtitulo').text((c.rut ? 'RUT ' + c.rut + ' · ' : '') + (c.plazo > 0 ? 'Plazo ' + c.plazo + ' días' : 'Sin plazo') + (c.cupo > 0 ? ' · Cupo ' + fmt(c.cupo) : ''));
			var h = '<div class="cred-resumen"><div><small>Debe</small><b>' + fmt(c.deuda) + '</b></div>'
				+ '<div><small>Vencido</small><b' + (c.vencido ? ' style="color:#C9372C"' : '') + '>' + fmt(c.vencido) + '</b></div>'
				+ (c.cupo > 0 ? '<div><small>Cupo disponible</small><b>' + fmt(Math.max(0, c.cupo - c.deuda)) + '</b></div>' : '') + '</div>';
			if (!r.listo) h += '<div class="alert alert-warning">Para registrar pagos falta preparar la base: corre el script <code>sql/credito_clientes.sql</code>.</div>';
			if (!cred.pendientes.length) {
				h += '<p class="text-muted text-center" style="margin:20px 0"><i class="fa fa-check-circle"></i> No tiene ventas a crédito pendientes.</p>';
			} else {
				h += '<table class="table table-condensed cred-tabla"><thead><tr><th style="width:30px"><input type="checkbox" id="credTodas" title="Todas"></th>'
					+ '<th>Fecha</th><th>Documento</th><th>Vence</th><th class="caja-num">Total</th><th class="caja-num">Pagado</th><th class="caja-num">Saldo</th></tr></thead><tbody>';
				$.each(cred.pendientes, function (i, v) {
					var vencida = v.dias_vencido > 0;
					h += '<tr class="' + (vencida ? 'cred-vence' : '') + '"><td><input type="checkbox" class="cred-chk" data-i="' + i + '"></td>'
						+ '<td>' + esc(v.fecha) + '</td>'
						+ '<td><span class="cred-ver" data-i="' + i + '" title="Ver productos"><i class="fa fa-caret-right"></i> ' + esc(v.documento) + ' N° ' + v.folio + '</span></td>'
						+ '<td>' + (v.vence ? esc(v.vence) + (vencida ? ' <small style="color:#C9372C">(' + v.dias_vencido + ' días vencida)</small>' : '') : '—') + '</td>'
						+ '<td class="caja-num">' + fmt(v.monto) + '</td><td class="caja-num">' + fmt(v.pagado) + '</td><td class="caja-num"><b>' + fmt(v.saldo) + '</b></td></tr>';
					h += '<tr class="cred-lineas" data-i="' + i + '" style="display:none"><td colspan="7">' + ($.map(v.lineas, function (l) {
						return esc(fmtCant(l.cantidad)) + ' x ' + esc(l.nombre) + ' — ' + fmt(l.subtotal);
					}).join('<br>') || '<span class="text-muted">Sin detalle</span>') + '</td></tr>';
				});
				h += '</tbody></table>';
				var formas = $.grep(catalogo ? catalogo.formas_pago : [], function (f) { return !f.credito && !f.cortesia; });
				h += '<div class="cred-pago"><div class="row">'
					+ '<div class="col-sm-4"><small class="text-muted">A pagar (<span id="credCant">0</span> venta/s)</small><div class="cred-total" id="credTotal">$0</div></div>'
					+ '<div class="col-sm-4 form-group"><label>Forma de pago</label><select class="form-control" id="credFpago">' + $.map(formas, function (f) {
						return '<option value="' + f.id_fpago + '" data-efectivo="' + (f.efectivo ? 1 : 0) + '">' + esc(f.nombre) + '</option>';
					}).join('') + '</select></div>'
					+ '<div class="col-sm-4 form-group" id="credPagaConGrupo"><label>Paga con</label><input type="number" min="0" step="1" class="form-control" id="credPagaCon" placeholder="Monto entregado"><small class="text-muted" id="credVuelto"></small></div>'
					+ '</div><div class="form-group"><input type="text" class="form-control" id="credObs" maxlength="200" placeholder="Observación (opcional)"></div>'
					+ '<button class="btn btn-success btn-lg btn-block" id="btnPagarCredito" disabled' + (r.listo ? '' : ' title="Falta correr sql/credito_clientes.sql"') + '><i class="fa fa-check"></i> Registrar pago</button></div>';
			}
			if (r.pagos && r.pagos.length) {
				h += '<h5 style="margin-top:22px"><b>Últimos pagos</b></h5><table class="table table-condensed"><thead><tr><th>Fecha</th><th>Ventas</th><th>Forma de pago</th><th>Cajero</th><th class="caja-num">Monto</th><th></th></tr></thead><tbody>'
					+ $.map(r.pagos, function (pg) {
						return '<tr><td>' + esc(pg.fecha) + '</td><td><small>' + esc(pg.ventas) + '</small></td><td>' + esc(pg.fpago) + '</td><td>' + esc(pg.usuario) + '</td>'
							+ '<td class="caja-num">' + fmt(pg.monto) + '</td><td><button class="btn btn-default btn-xs cred-reimprimir" data-id="' + pg.id + '" title="Reimprimir comprobante"><i class="fa fa-print"></i></button></td></tr>';
					}).join('') + '</tbody></table>';
			}
			$('#credDetalle').html(h);
			$('#credDetalle').data('listo', !!r.listo);
			actualizarPagoCredito();
		}
		function creditosElegidos() {
			return $.map($('#credDetalle .cred-chk:checked').toArray(), function (el) { return cred.pendientes[$(el).data('i')]; });
		}
		function actualizarPagoCredito() {
			var elegidos = creditosElegidos(), total = 0;
			$.each(elegidos, function (i, v) { total += v.saldo; });
			$('#credTotal').text(fmt(total));
			$('#credCant').text(elegidos.length);
			$('#credTodas').prop('checked', elegidos.length > 0 && elegidos.length === cred.pendientes.length);
			var efectivo = $('#credFpago option:selected').data('efectivo') === 1;
			$('#credPagaConGrupo').toggle(efectivo);
			var pagaCon = Number($('#credPagaCon').val()) || 0;
			$('#credVuelto').text(efectivo && pagaCon > total && total > 0 ? 'Vuelto: ' + fmt(pagaCon - total) : (efectivo && pagaCon && pagaCon < total ? 'Falta ' + fmt(total - pagaCon) : ''));
			$('#btnPagarCredito').prop('disabled', !total || !$('#credDetalle').data('listo') || (efectivo && pagaCon > 0 && pagaCon < total));
		}
		$('#credDetalle')
			.on('change', '#credTodas', function () { $('#credDetalle .cred-chk').prop('checked', this.checked); actualizarPagoCredito(); })
			.on('change', '.cred-chk, #credFpago', actualizarPagoCredito)
			.on('input', '#credPagaCon', actualizarPagoCredito)
			.on('click', '.cred-ver', function () {
				var i = $(this).data('i');
				$('#credDetalle .cred-lineas[data-i="' + i + '"]').toggle();
				$(this).find('i').toggleClass('fa-caret-right fa-caret-down');
			})
			.on('click', '.cred-reimprimir', function () {
				post('reimprimirPagoCredito', { id_pago: $(this).data('id'), local: comprobanteLocal() }).done(function (r) {
					if (imprimirComprobanteLocal(r, 'Comprobante')) return;
					if (r.ok) toastr.success('Comprobante enviado a la impresora', '', { timeOut: 2500 });
					else toastr.error('Comprobante: ' + r.detalle, '', { timeOut: 9000 });
				});
			})
			.on('click', '#btnPagarCredito', function () {
				var elegidos = creditosElegidos();
				if (!elegidos.length || !cred.cliente) return;
				var total = 0;
				$.each(elegidos, function (i, v) { total += v.saldo; });
				var $fp = $('#credFpago option:selected');
				var datos = { id_cliente: cred.cliente.id_cliente, id_fpago: $fp.val(), paga_con: Number($('#credPagaCon').val()) || 0, observacion: $('#credObs').val(), local: comprobanteLocal() };
				$.each(elegidos, function (i, v) { datos['ids[' + i + ']'] = v.id_ctacte; });
				swal({
					title: '¿Registrar el pago?',
					text: cred.cliente.nombre + ' paga ' + fmt(total) + ' con ' + $fp.text() + ' (' + elegidos.length + ' venta/s).',
					type: 'warning', showCancelButton: true, closeOnConfirm: true,
					confirmButtonText: 'Registrar pago', cancelButtonText: 'Cancelar'
				}, function (ok) {
					if (!ok) return;
					var $b = $('#btnPagarCredito').prop('disabled', true);
					post('pagarCredito', datos).done(function (r) {
						var p = r.pago;
						toastr.success('Pago registrado: ' + fmt(p.monto) + (p.vuelto ? '. Vuelto ' + fmt(p.vuelto) : ''), '', { timeOut: 5000 });
						if (!imprimirComprobanteLocal(r.impresion, 'Comprobante de pago') && r.impresion && !r.impresion.ok) toastr.error('Comprobante: ' + r.impresion.detalle, '', { timeOut: 9000 });
						cargarClientesCredito();
						cargarCreditosCliente(cred.cliente.id_cliente);
						cargarEstado();
					}).fail(function () { $b.prop('disabled', false); });
				});
			});

		// ---------------- movimientos ----------------
		var efectivoDisponible = null;
		function cargarDisponible() {
			get('estado').done(function (r) {
				// Cierre ciego (sin gerencia): el servidor no manda lo esperado y no se muestra lo disponible
				efectivoDisponible = r && r.caja && r.caja.efectivo_esperado !== undefined && r.caja.efectivo_esperado !== null ? Number(r.caja.efectivo_esperado) : null;
				// Configuraciones > Permitir retiro fondo fijo: desmarcado, el fondo no se puede retirar
				var sinFondo = catalogo && catalogo.config.retiro_fondo === false;
				if (efectivoDisponible !== null && sinFondo) efectivoDisponible -= Number(r.caja.fondo) || 0;
				$('#movDisponible').text(efectivoDisponible === null ? '' : (sinFondo ? 'Disponible para retirar (sin el fondo fijo): ' : 'Efectivo disponible en la caja: ') + fmt(Math.max(0, efectivoDisponible)));
			});
		}
		function cargarMovimientos() {
			cargarDisponible();
			get('movimientos').done(function (r) {
				var $t = $('#tablaMovimientos tbody').empty();
				if (!r.movimientos.length) $t.append('<tr><td colspan="4" class="text-center text-muted">Sin movimientos</td></tr>');
				$.each(r.movimientos, function (i, m) {
					$t.append('<tr><td>' + esc(m.fecha) + '</td><td>' + esc(m.observacion) + '</td>'
						+ '<td class="caja-num">' + (Number(m.ingreso) ? fmt(m.ingreso) : '') + '</td>'
						+ '<td class="caja-num">' + (Number(m.egreso) ? fmt(m.egreso) : '') + '</td></tr>');
				});
			});
		}

		$('#lnkMovimientos').on('shown.bs.tab', cargarMovimientos);

		$('#btnMovimiento').on('click', function () {
			var datos = { tipo: $('input[name=tipoMov]:checked').val(), monto: Math.round(Number($('#montoMov').val())), observacion: $.trim($('#obsMov').val()) };
			if (!(datos.monto > 0)) { swal('Atención', 'Ingresa un monto mayor a cero', 'warning'); return; }
			if (!datos.observacion) { swal('Atención', 'Indica el motivo', 'warning'); return; }
			if (datos.tipo !== 'ingreso' && efectivoDisponible !== null && datos.monto > efectivoDisponible) {
				swal('Retiro', 'Solo hay ' + fmt(Math.max(0, efectivoDisponible)) + ' en efectivo en la caja: el retiro no puede ser mayor', 'warning');
				return;
			}
			var $b = $(this).prop('disabled', true);
			post('movimiento', datos).done(function () {
				toastr.success('Movimiento registrado');
				$('#montoMov, #obsMov').val('');
				cargarMovimientos();
			}).always(function () { $b.prop('disabled', false); });
		});

		// ---------------- devoluciones (nota de crédito interna) ----------------
		var devolucion = null;

		// anularTodo: anular un documento electrónico = nota de crédito por todo lo que queda (se puede ajustar)
		var devAnularTodo = false;
		function abrirDevolucion(idVenta, anularTodo) {
			devAnularTodo = !!anularTodo;
			devolucion = null;
			$('#devContenido').hide();
			$('#devMotivo').val('');
			$('#btnConfirmarDevolucion').prop('disabled', true);
			var $d = $('#devDocumento').empty();
			$.each(catalogo.documentos, function (i, d) { $d.append($('<option>').val(d.id_tdocumento).text(d.nombre)); });
			$d.val($('#cboDocumento').val());
			$('#devFolio').val('');
			$('#devBusqueda').toggle(!idVenta);
			$('#modalDevolucion').modal('show');
			if (idVenta) cargarDevolucion({ id_venta: idVenta });
		}

		function cargarDevolucion(params) {
			get('devolucionVenta', params).done(function (r) {
				devolucion = r;
				$('#devInfo').html('<b>' + esc(r.documento) + ' N° ' + esc(r.folio) + '</b> · ' + esc(r.fecha)
					+ (r.cliente ? ' · ' + esc(r.cliente) : '')
					+ '<br>Pagado ' + fmt(r.total) + ' en ' + esc($.map(r.pagos, function (p) { return p.nombre_fpago; }).join(', ') || '—')
					+ (r.monto_devuelto ? ' · <span class="text-danger">Ya devuelto ' + fmt(r.monto_devuelto) + '</span>' : '')
					+ '<br><small>Se emitirá: <b>' + (r.nc_electronica ? 'Nota de crédito electrónica (SII)' : 'Nota de crédito interna') + '</b></small>');
				var $t = $('#devLineas tbody').empty();
				$.each(r.lineas, function (i, l) {
					var entero = Math.round(l.cantidad) === l.cantidad;
					$t.append('<tr class="' + (l.disponible > 0 ? '' : 'agotada') + '"><td>' + esc(l.nombre) + '<br><small class="text-muted">' + fmt(l.bruto_un) + ' c/u'
						+ '<span class="visible-xs-inline"> · vendido ' + l.cantidad + (l.devuelto ? ' · devuelto ' + l.devuelto : '') + '</span></small></td>'
						+ '<td class="text-center hidden-xs">' + l.cantidad + '</td><td class="text-center hidden-xs">' + (l.devuelto || '—') + '</td>'
						+ '<td><input type="number" class="form-control input-sm dev-cant" data-i="' + i + '" min="0" max="' + l.disponible + '" step="' + (entero ? 1 : 0.001) + '" value="0"'
						+ (l.disponible > 0 ? '' : ' disabled') + '></td>'
						+ '<td class="caja-num dev-monto">$0</td></tr>');
				});
				// Por defecto se devuelve en la misma forma de pago, si fue una sola
				var $f = $('#devFpago').empty();
				$.each(catalogo.formas_pago, function (i, f) { if (!f.cortesia) $f.append($('<option>').val(f.id_fpago).text(f.nombre)); });
				var efectivo = $.grep(catalogo.formas_pago, function (f) { return f.efectivo; })[0];
				$f.val(r.pagos.length === 1 && $f.find('option[value="' + r.pagos[0].id_fpago + '"]').length ? r.pagos[0].id_fpago : (efectivo ? efectivo.id_fpago : $f.val()));
				$('#devContenido').show();
				if (r.disponible <= 0) toastr.info('Esta venta ya fue devuelta por completo');
				// Anular: queda marcado todo lo que se puede devolver
				if (devAnularTodo && r.disponible > 0) $('#btnDevTodo').click();
				calcularDevolucion();
			});
		}

		// Misma regla del servidor: precio con el descuento de la venta; si se devuelve todo lo que queda, lo pagado exacto
		function calcularDevolucion() {
			if (!devolucion) return;
			var subtotal = 0, completa = true, hay = false;
			$('#devLineas .dev-cant').each(function () {
				var l = devolucion.lineas[$(this).data('i')];
				var cant = Math.min(Math.max(Number($(this).val()) || 0, 0), l.disponible);
				if (cant + 0.0001 < l.disponible) completa = false;
				if (cant > 0) hay = true;
				var bruto = Math.round(l.bruto_un * cant);
				subtotal += bruto;
				$(this).closest('tr').find('.dev-monto').text(fmt(Math.round(bruto * devolucion.factor)));
			});
			var total = !hay ? 0 : (completa ? devolucion.disponible : Math.min(devolucion.disponible, Math.round(subtotal * devolucion.factor)));
			$('#devTotal').text(fmt(total));
			$('#btnConfirmarDevolucion').prop('disabled', total <= 0);
		}

		$('#btnDevolucionBuscar').on('click', function () { abrirDevolucion(0); });
		$('#tablaVentas').on('click', '.devolver', function () { abrirDevolucion($(this).data('id'), !!$(this).data('anular')); });
		$('#modalDevolucion').on('shown.bs.modal', function () {
			if ($('#devBusqueda').is(':visible')) $('#devFolio').focus();
		});
		$('#devBusqueda').on('submit', function (e) {
			e.preventDefault();
			var folio = Number($('#devFolio').val());
			if (!(folio > 0)) { toastr.warning('Ingresa el folio'); return; }
			$('#devContenido').hide();
			cargarDevolucion({ id_tdocumento: $('#devDocumento').val(), folio: folio });
		});
		$('#devLineas').on('input', '.dev-cant', calcularDevolucion);
		$('#btnDevTodo').on('click', function () {
			$('#devLineas .dev-cant:enabled').each(function () { $(this).val(devolucion.lineas[$(this).data('i')].disponible); });
			calcularDevolucion();
		});

		$('#btnConfirmarDevolucion').on('click', function () {
			if (!devolucion) return;
			var motivo = $.trim($('#devMotivo').val());
			if (!motivo) { toastr.warning('Indica el motivo de la devolución'); $('#devMotivo').focus(); return; }
			var datos = {
				id_venta: devolucion.id_venta,
				id_fpago: Number($('#devFpago').val()),
				motivo: motivo,
				lineas: []
			};
			$('#devLineas .dev-cant').each(function () {
				var cant = Number($(this).val()) || 0;
				if (cant > 0) datos.lineas.push({ id_detalle_venta: devolucion.lineas[$(this).data('i')].id_detalle_venta, cantidad: cant });
			});
			var $b = $(this).prop('disabled', true);
			enviarConAutorizacion(function (aut) {
				datos.autorizacion = aut;
				return $.ajax({ url: base + 'devolver', type: 'POST', contentType: 'application/json', data: JSON.stringify(datos), dataType: 'json' });
			}, function (r) {
				$b.prop('disabled', false);
				$('#modalDevolucion').modal('hide');
				$('#ventaOkTitulo').text('Devolución registrada');
				$('#btnNuevaVenta').text('Listo');
				mostrarResultadoDte(r.dte);
				$('#ventaOkDoc').text(r.documento + (r.folio ? ' N° ' + r.folio : '') + ' · ' + fmt(r.total) + ' en ' + r.forma_pago);
				var efectivo = $.grep(catalogo.formas_pago, function (f) { return f.efectivo && f.nombre === r.forma_pago; }).length;
				$('#ventaOkVuelto').text(efectivo ? 'Entregar al cliente: ' + fmt(r.total) : '');
				$('#ventaOkTicket').attr('href', base + 'ticket/' + r.id_venta + '?imprimir=1');
				$('#ventaOkPdf').attr('href', base + 'pdf/' + r.id_venta);
				$('#modalVentaOk').modal('show');
				if (imprimeTicket()) imprimirTicket(r.id_venta, bloquearImpresion('Imprimiendo documento…'));
				if ($('#tabVentas').hasClass('active')) cargarVentas();
				cargarEstado();
			}, function () { $b.prop('disabled', false); });
		});

