		// ---------------- pago electrónico (19-11-2026) ----------------
		// Pedido del usuario: la caja que tiene máquina POS (Herramientas > Control de cajas; datos en Mantención > Pago
		// electrónico) cobra en ella lo que va con sus formas de pago (tarjeta de débito / crédito), al presionar Pago exacto
		// o Confirmar. El navegador llama al programa ApiPagoElectronico de este equipo (window.PagoPos):
		//  1. GET /status: el programa está abierto y sus datos (sucursal, caja, terminal) son los configurados. Si alguno
		//     no es igual, se muestra cuál y no se cobra.
		//  2. Caja/pagoElectronicoIniciar: el cobro queda registrado con la venta ANTES de ir a la máquina.
		//  3. POST /pago: la pantalla queda bloqueada ("Cobrando en la máquina") y solo se puede cancelar.
		//  4. Aprobado: se registra la venta (Caja::vender con pago_electronico_tx). Rechazado: se avisa y se puede
		//     cobrar de otra forma. Sin confirmar (se canceló la espera, se cortó la conexión): queda arriba, en la barra de
		//     cobros sin terminar, con Cancelar, Volver a enviar y Aprobar.
		var pagoPos = { maquina: null, formas: [], pendientes: [], usuario: '', cobrando: null, ocupado: false };

		function cargarPagoElectronico() {
			return $.ajax({ url: base + 'pagoElectronico', dataType: 'json', cache: false, global: false }).done(function (r) {
				pagoPos.maquina = r.maquina || null;
				pagoPos.formas = r.formas_electronicas || [];
				pagoPos.pendientes = r.pendientes || [];
				pagoPos.usuario = r.usuario || '';
				pintarPendientesPos();
			});
		}
		function esFormaMaquina(id) {
			// v1.39.10 (pedido del usuario): sin máquina asignada a esta caja, las tarjetas se cobran como antes (a mano)
			return !!pagoPos.maquina && pagoPos.formas.indexOf(Number(id)) >= 0;
		}
		function montoEnMaquina(lista) {
			var suma = 0;
			$.each(lista || [], function (i, p) { if (esFormaMaquina(p.id_fpago)) suma += Math.round(Number(p.monto) || 0); });
			return suma;
		}
		// TUU distingue débito y crédito: crédito si la forma de pago se llama "crédito"
		function esCreditoPos(lista) {
			return $.grep(lista || [], function (p) {
				var f = catalogo ? fpago(p.id_fpago) : null;
				return esFormaMaquina(p.id_fpago) && f && /CR[EÉ]DITO/i.test(f.nombre);
			}).length > 0;
		}
		function descripcionVenta(v) {
			var n = v.detalle ? v.detalle.length : 0;
			var que = v.id_mesa ? nombreMesa(v.id_mesa) + (v.parcial ? ' (una parte)' : '')
				: (v.delivery || v.id_delivery ? 'Delivery' + (v.id_delivery ? ' N° ' + v.id_delivery : '') : 'Venta directa');
			return que + (n ? ' · ' + n + (n === 1 ? ' producto' : ' productos') : '') + (v.retira ? ' · ' + v.retira : '');
		}
		function avisoHtml(titulo, html, tipo) {
			swal({ title: titulo, text: html, type: tipo || 'error', html: true });
		}
		// SweetAlert abre mal una ventana desde el botón de otra: se espera a que se cierre la primera
		function preguntar(titulo, html, boton, siOk, tipo) {
			swal({ title: titulo, text: html, type: tipo || 'warning', html: true, showCancelButton: true, confirmButtonText: boton, cancelButtonText: 'Volver' },
				function (ok) { if (ok) setTimeout(siOk, 250); });
		}

		// ---- pantalla "Cobrando en la máquina" ----
		var $bloqueoPos = $('<div id="bloqueoPos" role="alertdialog" aria-modal="true" aria-live="assertive" aria-label="Cobrando en la máquina">'
			+ '<div class="bloqueo-caja"><div class="bloqueo-icono"><i class="fa fa-credit-card"></i><span class="bloqueo-giro"></span></div>'
			+ '<div class="pos-monto"></div><div class="bloqueo-texto"></div><div class="bloqueo-sub"></div><div class="pos-tiempo"></div>'
			+ '<div class="pos-botones"><button type="button" class="btn btn-danger btn-lg pos-cancelar"><i class="fa fa-times"></i> Cancelar cobro</button>'
			+ '<button type="button" class="btn btn-default pos-dejar" style="display:none"><i class="fa fa-clock-o"></i> Dejar pendiente y seguir</button></div>'
			+ '</div></div>').appendTo('body');
		var posReloj = null;
		function mostrarBloqueoPos(monto, texto, sub, conCancelar) {
			$bloqueoPos.find('.pos-monto').text(fmt(monto));
			textoBloqueoPos(texto, sub);
			$bloqueoPos.find('.pos-cancelar').toggle(!!conCancelar).prop('disabled', false).html('<i class="fa fa-times"></i> Cancelar cobro');
			$bloqueoPos.find('.pos-dejar').hide();
			if (document.activeElement && document.activeElement.blur) document.activeElement.blur();
			$bloqueoPos.addClass('visible');
			if (conCancelar) {
				var inicio = Date.now();
				clearInterval(posReloj);
				var tic = function () { $bloqueoPos.find('.pos-tiempo').text('Esperando la máquina · ' + fmtTiempo(Date.now() - inicio)); };
				tic();
				posReloj = setInterval(tic, 1000);
				$bloqueoPos.find('.pos-cancelar').focus();
			} else {
				clearInterval(posReloj);
				$bloqueoPos.find('.pos-tiempo').text('');
			}
		}
		function textoBloqueoPos(texto, sub) {
			$bloqueoPos.find('.bloqueo-texto').text(texto || '');
			$bloqueoPos.find('.bloqueo-sub').text(sub || '');
		}
		function ocultarBloqueoPos() {
			clearInterval(posReloj);
			$bloqueoPos.removeClass('visible');
		}
		// Mientras se cobra, el teclado solo sirve para los botones de la pantalla (Cancelar / Dejar pendiente)
		document.addEventListener('keydown', function (e) {
			if (!$bloqueoPos.hasClass('visible')) return;
			if ($(e.target).closest('#bloqueoPos').length && (e.which === 13 || e.which === 32 || e.which === 9)) return;
			e.preventDefault();
			e.stopPropagation();
		}, true);
		window.addEventListener('beforeunload', function (e) {
			if (!pagoPos.cobrando) return;
			e.preventDefault();
			e.returnValue = 'Hay un cobro en curso en la máquina de pago';
			return e.returnValue;
		});

		// Cancelar: se le pide al programa; la respuesta final llega por /pago. Si no se puede (o tarda), se puede dejar
		// pendiente: queda arriba para resolverlo después.
		$bloqueoPos.on('click', '.pos-cancelar', function () {
			var c = pagoPos.cobrando;
			if (!c || !c.tx || c.cancelando) return;
			c.cancelando = true;
			$(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Cancelando…');
			textoBloqueoPos('Cancelando el cobro…', 'Si el cliente ya pagó, la máquina puede terminar igual el pago.');
			PagoPos.cancelar(c.m, c.tx).then(function (r) {
				if (pagoPos.cobrando !== c) return;   // ya terminó
				var noSe = r.red || r.status >= 400;
				if (noSe) {
					textoBloqueoPos('No se pudo cancelar desde la Caja', ((r.json && (r.json.error || r.json.message)) || 'El programa no respondió.')
						+ ' Cancela el cobro en la máquina, o déjalo pendiente para resolverlo después.');
					$bloqueoPos.find('.pos-cancelar').hide();
				}
				setTimeout(function () { if (pagoPos.cobrando === c) $bloqueoPos.find('.pos-dejar').show().focus(); }, noSe ? 0 : 15000);
			});
		});
		$bloqueoPos.on('click', '.pos-dejar', function () {
			var c = pagoPos.cobrando;
			if (!c) return;
			if (c.ctrl) c.ctrl.abort();
			c.terminar({ red: true, dejado: true });
		});

		/**
		 * Cobra `monto` de la venta en la máquina. ctx.replay = desde la barra de pendientes (Volver a enviar): no toca la
		 * venta que esté en pantalla.
		 */
		function cobrarEnMaquina(venta, monto, ctx) {
			var m = pagoPos.maquina;
			if (pagoPos.cobrando) return;
			if (!m) {
				avisoHtml('Falta asignar una máquina POS', 'Asigna una máquina vigente a esta caja en <b>Herramientas &rsaquo; Control de cajas</b>.', 'warning');
				return;
			}
			var credito = esCreditoPos(venta.pagos);
			if (m.tipo === 'HAULMER' && credito && esDebitoPos(venta.pagos)) {
				avisoHtml('TUU requiere un tipo de tarjeta por cobro', 'Elige débito o crédito para el monto que se envía a la máquina.', 'warning');
				return;
			}
			if (!ctx.replay && pagoPos.pendientes.length) {
				avisoHtml('Hay un cobro con tarjeta sin terminar', 'Arriba de la venta quedó un cobro de <b>' + fmt(pagoPos.pendientes[0].monto) + '</b> sin terminar ('
					+ esc(pagoPos.pendientes[0].descripcion) + ').<br>Resuélvelo (Cancelar, Volver a enviar o Aprobar) antes de cobrar otra venta con tarjeta.', 'warning');
				return;
			}
			pagoPos.cobrando = { monto: monto };
			enviando = true;
			actualizarResumenPago();
			mostrarBloqueoPos(monto, 'Conectando con la máquina…', 'Revisando el programa de pago de este equipo', false);
			var fallarAntes = function (html) {
				pagoPos.cobrando = null;
				ocultarBloqueoPos();
				enviando = false;
				actualizarResumenPago();
				avisoHtml('No se pudo cobrar en la máquina', html);
			};
			if (!m.usuario_api || !m.clave_api) {
				fallarAntes('Falta el <b>usuario</b> o la <b>clave de la API</b> del programa de pago en <b>Configuraciones &rsaquo; General &rsaquo; Pago electrónico</b>.');
				return;
			}
			PagoPos.status(m).then(function (s) {
				if (s.red || s.status !== 200) { fallarAntes(esc(s.mensaje || PagoPos.sinPrograma(m))); return; }
				var dif = PagoPos.comparar(m, s.json);
				if (dif.length) {
					fallarAntes('Estos datos de <b>Pago electrónico</b> (menú) no son iguales a los del programa de pago:<br><br>'
						+ $.map(dif, function (x) { return '&bull; ' + esc(x); }).join('<br>'));
					return;
				}
				var desc = descripcionVenta(venta);
				postJson('pagoElectronicoIniciar', { monto: monto, descripcion: desc, venta: venta, id_maquina: m.id }).done(function (r) {
					// La venta (con su token) quedó guardada en el cobro: la próxima venta lleva otro token
					if (!ctx.replay) tokenCobro = null;
					pagoPos.pendientes.push({ tx_id: r.tx_id, tx_programa: r.tx_id, monto: monto, estado: 'enviando', descripcion: desc, fecha: '', mensaje: '' });
					enviarAMaquina(r.maquina, r.tx_id, monto, credito, venta, ctx);
				}).fail(function (xhr) {
					fallarAntes(esc((xhr.responseJSON && xhr.responseJSON.error) || 'No se pudo registrar el cobro. Revisa la conexión.'));
				});
			});
		}

		function esDebitoPos(lista) {
			return $.grep(lista || [], function (p) {
				var f = catalogo ? fpago(p.id_fpago) : null;
				return esFormaMaquina(p.id_fpago) && f && /D[EÉ]BITO/i.test(f.nombre);
			}).length > 0;
		}

		function enviarAMaquina(m, txId, monto, credito, venta, ctx) {
			var ctrl = window.AbortController ? new AbortController() : null;
			var c = pagoPos.cobrando = { tx: txId, monto: monto, m: m, ctrl: ctrl, cancelando: false, hecho: false };
			mostrarBloqueoPos(monto, 'Cobrando en la máquina', 'Pide al cliente que pague con su tarjeta en la máquina ' + (m.nombre || m.tipo_nombre) + '.', true);
			c.terminar = function (r) {
				if (c.hecho) return;   // "Dejar pendiente" ya lo terminó: la respuesta tardía no cambia nada
				c.hecho = true;
				var res = PagoPos.interpretar(r);
				if (res.resultado === 'red') {
					res = { resultado: 'pendiente', mensaje: r.dejado ? 'Se dejó de esperar la respuesta de la máquina.' : 'Se cortó la conexión con el programa de pago mientras cobraba.' };
				}
				mostrarBloqueoPos(monto, 'Guardando el resultado…', '', false);
				var j = r.json || null;
				informarCobro(txId, res.resultado, res.mensaje, j, res.codigo).always(function () {
					pagoPos.cobrando = null;
					ocultarBloqueoPos();
					enviando = false;
					actualizarResumenPago();
					alTerminarCobro(res, txId, venta, ctx);
				});
			};
			PagoPos.cobrar(m, monto, txId, credito, ctrl ? ctrl.signal : undefined).then(c.terminar);
		}

		function informarCobro(txId, estado, mensaje, respuesta, codigo) {
			return postJson('pagoElectronicoActualizar', { tx_id: txId, estado: estado, mensaje: mensaje || '', respuesta: respuesta || null, codigo: codigo || '' })
				.fail(function (xhr) { if (xhr.status === 401) error(xhr); });
		}

		function alTerminarCobro(res, txId, venta, ctx) {
			if (res.resultado === 'aprobado') {
				toastr.success(res.codigo ? 'Código de autorización ' + res.codigo : 'Registrando la venta…', 'Pago aprobado en la máquina');
				var v = $.extend({}, venta, { pago_electronico_tx: txId });
				enviarVenta(v, $.extend({}, ctx, { pagoElectronico: true }), function () {
					// No se pudo registrar (ej. faltó la autorización): el pago queda arriba con "Registrar venta"
					cargarPagoElectronico();
					toastr.warning('El pago quedó aprobado arriba de la venta: usa "Registrar venta" cuando se pueda.', 'Venta sin registrar', { timeOut: 12000, closeButton: true });
				});
				return;
			}
			cargarPagoElectronico();
			if (res.resultado === 'rechazado') {
				avisoHtml('Pago no aprobado', esc(res.mensaje || 'La máquina no aprobó el pago.') + '<br><br>No se cobró nada: puedes intentar de nuevo o cobrar con otra forma de pago.');
			} else if (res.resultado === 'error') {
				avisoHtml('No se pudo cobrar en la máquina', esc(res.mensaje) + '<br><br>No se cobró nada.');
			} else {
				// Sin confirmar: la venta queda arriba (no se pierde) y la pantalla queda libre para seguir atendiendo
				if (!ctx.replay) {
					$('#modalPago').modal('hide');
					if (modoDelivery) salirDelivery();
					else if (!ctx.idMesa) limpiarVenta();
				}
				avisoHtml('Cobro sin confirmar', esc(res.mensaje) + '<br><br>La venta quedó <b>arriba</b>, en cobros sin terminar. Revisa la máquina: si el pago salió aprobado usa <b>Aprobar</b>; si no, <b>Volver a enviar</b> o <b>Cancelar</b>.', 'warning');
			}
		}

		/** Venta registrada desde la barra de pendientes: sale el documento (y la comanda de la venta directa). */
		function ventaPendienteRegistrada(r, ctx) {
			toastr.success((r.documento || 'Venta') + (r.folio ? ' N° ' + r.folio : '') + ' · ' + fmt(r.total), 'Venta registrada', { timeOut: 6000 });
			if (r.dte && !r.dte.ok) toastr.warning(r.dte.mensaje || '', 'Pendiente de envío al SII: reenvíalo desde Ventas', { timeOut: 15000, closeButton: true });
			var comandaVenta = imprimeComanda() && !ctx.idMesa ? r.comanda_id : null;
			var listoImp = (imprimeTicket() || comandaVenta) ? bloquearImpresion('Imprimiendo…') : function () {};
			// v1.39.4: la barra de cobros se recarga después de imprimir, no al mismo tiempo
			var listo = function () { listoImp(); setTimeout(cargarPagoElectronico, 300); };
			var conComanda = function () { if (comandaVenta) imprimirComanda(comandaVenta, 'Comanda', null, listo); else listo(); };
			if (imprimeTicket()) imprimirTicket(r.id_venta, conComanda);
			else conComanda();
			cargarEstado();
			cargarMesas();
			if (r.id_delivery) cargarDeliverys();
		}

		// ---- barra de cobros sin terminar (arriba de Venta directa / Mesas y recintos / Delivery) ----
		function pintarPendientesPos() {
			var $b = $('#pePendientes');
			if (!pagoPos.pendientes.length) { $b.hide().empty(); return; }
			var h = '';
			$.each(pagoPos.pendientes, function (i, p) {
				var aprobado = p.estado === 'aprobado';
				h += '<div class="pe-pendiente' + (aprobado ? ' aprobado' : '') + '" data-tx="' + esc(p.tx_id) + '">'
					+ '<i class="fa ' + (aprobado ? 'fa-check-circle' : 'fa-exclamation-triangle') + ' pe-ico" aria-hidden="true"></i>'
					+ '<div class="pe-info"><b>' + (aprobado ? 'Pago aprobado: falta registrar la venta' : 'Cobro con tarjeta sin terminar') + '</b>'
					+ '<small>' + esc(p.descripcion || 'Venta') + (p.fecha ? ' · ' + esc(p.fecha) : '') + (p.usuario ? ' · ' + esc(p.usuario) : '') + '</small>'
					+ (p.mensaje ? '<small>' + esc(p.mensaje) + '</small>' : '') + '</div>'
					+ '<div class="pe-monto">' + fmt(p.monto) + '</div>'
					+ '<div class="pe-acc">'
					+ (aprobado
						? '<button type="button" class="btn btn-success btn-sm pe-registrar"><i class="fa fa-check"></i> Registrar venta</button>'
						: '<button type="button" class="btn btn-success btn-sm pe-aprobar" title="La máquina aprobó el pago (revisa el comprobante): se registra la venta"><i class="fa fa-check"></i> Aprobar</button>'
							+ '<button type="button" class="btn btn-primary btn-sm pe-reenviar" title="Revisa la máquina y, si no se cobró, vuelve a enviar el cobro"><i class="fa fa-repeat"></i> Volver a enviar</button>')
					+ '<button type="button" class="btn btn-default btn-sm pe-cancelar"><i class="fa fa-times"></i> Cancelar</button>'
					+ '</div></div>';
			});
			$b.html(h).show();
		}
		function pendientePos(el) {
			var tx = $(el).closest('[data-tx]').data('tx');
			return $.grep(pagoPos.pendientes, function (p) { return p.tx_id === tx; })[0] || null;
		}
		/** Qué dice el programa del cobro: {resultado: aprobado|rechazado|pendiente|error|noexiste|red} o null sin máquina. */
		function consultarPos(p) {
			var m = pagoPos.maquina;
			if (!m) return Promise.resolve(null);
			return PagoPos.estado(m, p.tx_programa || p.tx_id).then(function (r) {
				if (r.red) return { resultado: 'red' };
				if (r.status === 404) return { resultado: 'noexiste' };
				return PagoPos.interpretar(r);
			});
		}
		function ocupadoPos(si) {
			pagoPos.ocupado = si;
			$('#pePendientes .btn').prop('disabled', si);
		}
		// La venta guardada en el cobro, para registrarla o volver a enviarla
		function conVentaPos(p, siOk) {
			ocupadoPos(true);
			get('pagoElectronicoVenta', { tx_id: p.tx_id }).done(function (t) {
				ocupadoPos(false);
				if (!t.venta) { swal('Sin datos', 'Este cobro no tiene la venta guardada: cancélalo y vuelve a hacer la venta.', 'error'); return; }
				siOk(t.venta);
			}).fail(function () { ocupadoPos(false); });
		}
		function registrarPendientePos(p) {
			conVentaPos(p, function (venta) {
				var v = $.extend({}, venta, { pago_electronico_tx: p.tx_id });
				enviarVenta(v, { replay: true, idMesa: v.id_mesa || 0 }, function () { cargarPagoElectronico(); });
			});
		}
		function aprobarPendientePos(p, nota) {
			ocupadoPos(true);
			informarCobro(p.tx_id, 'aprobado', nota, null, '').done(function () {
				ocupadoPos(false);
				p.estado = 'aprobado';
				registrarPendientePos(p);
			}).fail(function (xhr) { ocupadoPos(false); error(xhr); });
		}

		$('#pePendientes').on('click', '.pe-registrar', function () {
			var p = pendientePos(this);
			if (p && !pagoPos.ocupado) registrarPendientePos(p);
		});

		// Aprobar directo: el cajero vio el comprobante aprobado en la máquina
		$('#pePendientes').on('click', '.pe-aprobar', function () {
			var p = pendientePos(this);
			if (!p || pagoPos.ocupado) return;
			preguntar('¿La máquina aprobó el pago?', 'Hazlo solo si la máquina <b>aprobó</b> el pago de <b>' + fmt(p.monto) + '</b> (revisa el comprobante).<br>Se registrará la venta: '
				+ esc(p.descripcion) + '.', 'Sí, aprobar', function () {
				ocupadoPos(true);
				consultarPos(p).then(function (c) {
					ocupadoPos(false);
					var m = pagoPos.maquina, nota = 'Aprobado por ' + (pagoPos.usuario || 'el cajero') + ' desde la Caja';
					if (c && c.resultado === 'rechazado') {
						preguntar('La máquina dice que no se aprobó', esc(c.mensaje || 'La máquina rechazó este pago.') + '<br><br>¿Aprobar igual? Solo si tienes el comprobante aprobado.',
							'Aprobar igual', function () { aprobarPendientePos(p, nota + ' (la máquina lo informó rechazado)'); }, 'error');
						return;
					}
					// Sin confirmar en el programa: se resuelve allá también, para que la máquina pueda volver a cobrar
					if (m && c && c.resultado === 'pendiente') PagoPos.resolver(m, p.tx_programa || p.tx_id, 'APROBADO', pagoPos.usuario, 'Aprobado desde la Caja web');
					aprobarPendientePos(p, nota);
				});
			});
		});

		// Volver a enviar: primero se pregunta a la máquina, para no cobrar dos veces
		$('#pePendientes').on('click', '.pe-reenviar', function () {
			var p = pendientePos(this);
			if (!p || pagoPos.ocupado || pagoPos.cobrando) return;
			var m = pagoPos.maquina;
			if (!m) { swal('Sin máquina', 'Esta caja ya no tiene máquina de pago electrónico (Herramientas > Control de cajas). Usa Aprobar o Cancelar.', 'warning'); return; }
			ocupadoPos(true);
			consultarPos(p).then(function (c) {
				ocupadoPos(false);
				if (!c || c.resultado === 'red') { avisoHtml('No se pudo revisar la máquina', esc(PagoPos.sinPrograma(m))); return; }
				if (c.resultado === 'aprobado') {
					toastr.info('La máquina ya había aprobado este pago: se registra la venta sin cobrar de nuevo.', 'Pago aprobado', { timeOut: 8000 });
					informarCobro(p.tx_id, 'aprobado', 'La máquina lo aprobó', null, c.codigo).done(function () { registrarPendientePos(p); });
					return;
				}
				if (c.resultado === 'pendiente') {
					avisoHtml('La máquina aún no confirma', esc(c.mensaje || 'El cobro sigue sin confirmar en la máquina.')
						+ '<br><br>Espera unos segundos y vuelve a intentar. Si ves el comprobante aprobado usa <b>Aprobar</b>; si el cliente no pagó, <b>Cancelar</b>.', 'warning');
					return;
				}
				// Transbank no usa nuestro número de cobro: si el programa nunca respondió, no se sabe si se cobró
				if (c.resultado === 'noexiste' && m.tipo === 'TRANSBANK' && (p.tx_programa || p.tx_id) === p.tx_id && p.estado !== 'error') {
					avisoHtml('No se puede confirmar con la máquina', 'La máquina Transbank no informó este cobro, así que no se sabe si se pagó.<br><br>'
						+ 'Revisa la máquina: si el pago salió aprobado usa <b>Aprobar</b>; si no, <b>Cancelar</b> y vuelve a cobrar la venta.', 'warning');
					return;
				}
				conVentaPos(p, function (venta) {
					if (montoEnMaquina(venta.pagos) !== p.monto) {
						avisoHtml('Cambió la configuración de las tarjetas', 'Revisa el estado en la máquina y resuelve el cobro antes de iniciar otro.', 'warning');
						return;
					}
					informarCobro(p.tx_id, 'cancelado', 'Se volvió a enviar con otro cobro', null, '').done(function () {
						pagoPos.pendientes = $.grep(pagoPos.pendientes, function (x) { return x.tx_id !== p.tx_id; });
						pintarPendientesPos();
						cobrarEnMaquina(venta, p.monto, { replay: true, idMesa: venta.id_mesa || 0 });
					}).fail(error);
				});
			});
		});

		// Cancelar: la venta no se registra. Si la máquina dice que cobró, se avisa antes.
		$('#pePendientes').on('click', '.pe-cancelar', function () {
			var p = pendientePos(this);
			if (!p || pagoPos.ocupado) return;
			var aprobado = p.estado === 'aprobado';
			var cancelar = function () {
				var m = pagoPos.maquina, txp = p.tx_programa || p.tx_id;
				ocupadoPos(true);
				var fin = function () {
					informarCobro(p.tx_id, 'cancelado', 'Cancelado por ' + (pagoPos.usuario || 'el cajero') + ' desde la Caja', null, '').done(function () {
						toastr.info('La venta no se registró', 'Cobro cancelado');
					}).always(function () { ocupadoPos(false); cargarPagoElectronico(); });
				};
				if (!m || aprobado) { fin(); return; }
				// En el programa: se cancela si sigue en curso y se marca rechazada, para que la máquina pueda volver a cobrar
				PagoPos.cancelar(m, txp).then(function () {
					return PagoPos.resolver(m, txp, 'RECHAZADO', pagoPos.usuario, 'Cancelado desde la Caja web');
				}).then(fin, fin);
			};
			preguntar(aprobado ? '¡La máquina ya cobró este pago!' : '¿Cancelar el cobro?',
				aprobado ? 'Si cancelas, la venta de <b>' + fmt(p.monto) + '</b> no se registra y debes <b>anular el pago en la máquina</b>.<br>¿Cancelar igual?'
					: 'La venta de <b>' + fmt(p.monto) + '</b> (' + esc(p.descripcion) + ') no se registrará.<br>Úsalo si el cliente <b>no pagó</b>. Si la máquina aprobó el pago, usa <b>Aprobar</b>.',
				'Sí, cancelar', function () {
					if (aprobado) { cancelar(); return; }
					ocupadoPos(true);
					consultarPos(p).then(function (c) {
						ocupadoPos(false);
						if (c && c.resultado === 'aprobado') {
							preguntar('La máquina dice que el pago fue APROBADO', 'Lo más probable es que el cliente sí pagó <b>' + fmt(p.monto) + '</b>. Usa <b>Aprobar</b> para registrar la venta.<br><br>'
								+ '¿Cancelar igual? (deberás anular el pago en la máquina)', 'Cancelar igual', cancelar, 'error');
							return;
						}
						cancelar();
					});
				}, aprobado ? 'error' : 'warning');
		});

		$('#modalVentaOk').on('shown.bs.modal', function () { $('#btnNuevaVenta').focus(); })
			.on('hidden.bs.modal', function () { $('#buscarProducto').focus(); });

		// Emisión electrónica: si Cloud Apps no respondió, la venta queda pendiente y se reenvía desde Ventas
		function mostrarResultadoDte(dte) {
			var $a = $('#ventaOkAviso').hide();
			if (dte && !dte.ok) {
				$a.html('<i class="fa fa-exclamation-triangle"></i> Pendiente de envío al SII: ' + esc(dte.mensaje)
					+ '<br><small>Reenvíalo desde la pestaña Ventas.</small>').show();
			}
		}

