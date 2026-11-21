		// ---------------- dividir cuenta (Opciones de la mesa > Dividir cuenta) ----------------
		// Tres formas: por comensal (se crean comensales y se les asignan los productos arrastrándolos o con los botones; ⑂ reparte un
		// producto entre varios), partes iguales (cada parte lleva la misma fracción de cada producto) y libre (se eligen productos y
		// cantidades, como antes). Cada parte tiene su precuenta y se cobra aparte con su documento y cliente (factura: cliente con
		// sus datos y solicitante). Cada cobro es un "cobro de una parte" de la mesa (cobroParcial): el servidor separa lo cobrado y la
		// mesa sigue abierta con el resto. Lo armado se guarda en la base (Caja::division / guardarDivision, models/Mdivisioncuenta.php)
		// y se ve desde cualquier caja; si la base aún no tiene la tabla, se guarda en este computador (localStorage).
		var CLAVE_DIVISION = 'cajaWeb.division.';
		var dc = null;          // {id_cot, modo, sig, comensales: [{id, nombre, items: {id_detalle: cantidad}, pagado}], partes, pagadas: [{total, doc}]}
		var dcModo = 'comensal', dcModoPrevio = 'comensal', dcPaso = null, dcCobro = null, dcContinuar = 0, dcRepartir = 0, dcArrastre = null;
		(function () {   // limpieza: divisiones guardadas de hace más de 2 días
			try {
				for (var i = localStorage.length - 1; i >= 0; i--) {
					var k = localStorage.key(i);
					if (!k || k.indexOf(CLAVE_DIVISION) !== 0) continue;
					var v = JSON.parse(localStorage.getItem(k) || 'null');
					if (!v || Date.now() - (v.t || 0) > 2 * 86400000) localStorage.removeItem(k);
				}
			} catch (x) {}
		})();
		function r3(x) { return Math.round(x * 1000) / 1000; }
		// v1.24.0: los productos de un menú no se reparten sueltos: van con su menú (dcMonto suma su parte)
		function dcLineas() { return $.grep(carro, function (l) { return l.enviada && !l.menu_de; }); }
		function dcLineasTodas() { return $.grep(carro, function (l) { return l.enviada; }); }
		function dcLinea(id) { return $.grep(carro, function (l) { return l.enviada && l.id_detalle === id; })[0]; }
		// Lo que se cobra de una línea: completa, a su monto; una parte, en proporción (igual que Mcaja::seleccionDeLineas)
		function dcMontoLinea(l, q) { return q >= l.cantidad - 0.0005 ? l.bruto : Math.round(l.bruto_exacto * q / l.cantidad); }
		function dcMonto(items) {
			var t = 0;
			$.each(items, function (id, q) {
				var l = dcLinea(Number(id));
				if (!l) return;
				t += dcMontoLinea(l, q);
				// v1.24.0: lo elegido del menú que no viene aparte, en la proporción del menú
				if (l.es_menu) $.each(carro, function (i, h) {
					if (h.enviada && h.menu_de === l.id_detalle && items[h.id_detalle] === undefined) { var qh = cantidadHijoMenu(h, q); if (qh > 0) t += dcMontoLinea(h, qh); }
				});
			});
			return t;
		}
		function dcClave(idCot) { return CLAVE_DIVISION + EMPRESA + '.' + idCot; }
		function dcLeer(idCot) { try { return JSON.parse(localStorage.getItem(dcClave(idCot)) || 'null'); } catch (x) { return null; } }
		// Guardado en la base: cada cambio sube la versión; si otra caja guardó antes, se recibe lo suyo en vez de pisarlo
		var dcRemoto = false, dcGuardando = false, dcOtraVez = false, dcTimer = null, dcSondeo = null;
		function dcNueva(idCot, idMesa) { return { id_cot: idCot, id_mesa: idMesa, version: 0, modo: 'comensal', sig: 1, comensales: [], partes: 2, pagadas: [] }; }
		function dcNormalizar(d) {
			d.comensales = $.isArray(d.comensales) ? d.comensales : [];
			$.each(d.comensales, function (i, c) { if (!c.items || $.isArray(c.items)) c.items = {}; });
			d.pagadas = $.isArray(d.pagadas) ? d.pagadas : [];
			d.partes = Number(d.partes) || 2;
			d.sig = Number(d.sig) || 1;
			return d;
		}
		function dcDesdeServidor(r, idMesa) {
			var d = r.datos ? dcNormalizar($.extend(true, {}, r.datos)) : dcNueva(r.id_cotizacion, idMesa);
			d.id_cot = r.id_cotizacion;
			d.id_mesa = idMesa;
			d.version = r.version || 0;
			return d;
		}
		function dcDatos(d) { return { modo: d.modo, sig: d.sig, comensales: d.comensales, partes: d.partes, pagadas: d.pagadas }; }
		function dcGuardar(yaMismo) {
			if (!dc) return;
			dc.t = Date.now();
			if (!dcRemoto) { try { localStorage.setItem(dcClave(dc.id_cot), JSON.stringify(dc)); } catch (x) {} return; }
			clearTimeout(dcTimer);
			if (yaMismo) { dcTimer = null; dcEnviar(); } else dcTimer = setTimeout(dcEnviar, 300);   // al arrastrar varios seguidos, un solo envío
		}
		function dcEnviar() {
			dcTimer = null;
			if (!dc || !dcRemoto) return;
			if (dcGuardando) { dcOtraVez = true; return; }
			var d = dc;
			dcGuardando = true;
			postJson('guardarDivision', { id_mesa: d.id_mesa, id_cotizacion: d.id_cot, version: d.version || 0, datos: dcDatos(d) }).done(function (r) {
				if (r.conflicto) {
					dcOtraVez = false;
					if (dc === d) {
						dc = dcDesdeServidor({ id_cotizacion: d.id_cot, version: r.version, datos: r.datos }, d.id_mesa);
						dcAjustar();
						dcPintar();
					}
					toastr.info('Otra caja cambió esta división: se muestra lo último guardado');
					return;
				}
				d.version = r.version;
			}).fail(function (x) {
				toastr.warning('No se pudo guardar la división: ' + ((x.responseJSON && x.responseJSON.error) || 'revisa la conexión'));
			}).always(function () {
				dcGuardando = false;
				if (dcOtraVez) { dcOtraVez = false; dcEnviar(); }
			});
		}
		// Espera a que termine de guardarse lo pendiente (máx. 3 s) antes de volver a leer
		function dcTrasGuardar(fn, intentos) {
			intentos = intentos || 0;
			if ((dcGuardando || dcTimer) && intentos < 30) { setTimeout(function () { dcTrasGuardar(fn, intentos + 1); }, 100); return; }
			fn();
		}
		function dcBorrar(idCot) {
			try { localStorage.removeItem(dcClave(idCot)); } catch (x) {}
			if (dcRemoto) $.ajax({ url: base + 'borrarDivision', type: 'POST', contentType: 'application/json', data: JSON.stringify({ id_cotizacion: idCot }), dataType: 'json', global: false });
		}
		// Se lee de la base cada vez que se abre (otra caja pudo cambiarla); sin la tabla, de este computador
		function dcCargar() {
			var idMesa = mesa.id_mesa, idCot = mesa.id_cotizacion;
			var local = function () {
				var l = dcLeer(idCot);
				dc = l ? dcNormalizar(l) : dcNueva(idCot, idMesa);
				dc.id_cot = idCot;
				dc.id_mesa = idMesa;
				dcAjustar();
			};
			return $.ajax({ url: base + 'division', data: { id_mesa: idMesa }, dataType: 'json', cache: false, global: false }).then(function (r) {
				dcRemoto = !!r.disponible;
				if (!dcRemoto || !r.id_cotizacion) { local(); return; }
				dc = dcDesdeServidor(r, idMesa);
				// Lo que se armó en este computador antes de que la base tuviera la tabla pasa a la base
				var l = dcLeer(idCot);
				if (l) {
					if (!r.datos) { dc = $.extend(dcNormalizar(l), { id_cot: idCot, id_mesa: idMesa, version: 0 }); dcGuardar(true); }
					try { localStorage.removeItem(dcClave(idCot)); } catch (x) {}
				}
				dcAjustar();
			}, function () {
				dcRemoto = false;
				local();
				return $.Deferred().resolve().promise();
			});
		}
		// Lo asignado se ajusta a lo que hay hoy en la mesa (se cobró, anuló o cambió de mesa algo desde que se armó)
		function dcAjustar() {
			var usado = {};
			$.each(dc.comensales, function (i, c) {
				if (c.pagado) { c.items = {}; return; }
				$.each(c.items, function (id, q) {
					var l = dcLinea(Number(id)), libre = l ? r3(l.cantidad - (usado[id] || 0)) : 0;
					q = Math.min(q, libre);
					if (q <= 0.0005) { delete c.items[id]; return; }
					c.items[id] = r3(q);
					usado[id] = r3((usado[id] || 0) + q);
				});
			});
		}
		function dcComensal(id) { return $.grep(dc.comensales, function (c) { return c.id === id; })[0]; }
		function dcPendientes() { return $.grep(dc.comensales, function (c) { return !c.pagado; }); }
		function dcAsignado(id) {
			var t = 0;
			$.each(dc.comensales, function (i, c) { if (!c.pagado && c.items[id]) t += c.items[id]; });
			return r3(t);
		}
		function dcLibre(l) { return Math.max(0, r3(l.cantidad - dcAsignado(l.id_detalle))); }
		// Al arrastrar va de a una unidad (un pesable o lo que quede bajo 1, completo); con Mayús, todo lo que falta asignar
		function dcAsignar(id, comId, todo) {
			var l = dcLinea(id), c = dcComensal(comId);
			if (!l || !c || c.pagado) return;
			var libre = dcLibre(l);
			if (libre <= 0.0005) return;
			var q = todo || esFraccion(l.id_producto) || libre < 1 ? libre : 1;
			c.items[id] = r3((c.items[id] || 0) + q);
			dcGuardar();
			dcPintar();
		}
		function dcQuitar(id, comId) {
			var c = dcComensal(comId);
			if (!c) return;
			delete c.items[id];
			dcGuardar();
			dcPintar();
		}
		function dcMover(id, desde, hasta) {
			var a = dcComensal(desde), b = dcComensal(hasta);
			if (!a || !b || b.pagado || !a.items[id]) return;
			b.items[id] = r3((b.items[id] || 0) + a.items[id]);
			delete a.items[id];
			dcGuardar();
			dcPintar();
		}
		// ⑂: lo que falta asignar de un producto se reparte en partes iguales (en milésimas) entre los comensales elegidos
		function dcRepartirEntre(id, ids) {
			var l = dcLinea(id);
			if (!l || !ids.length) return;
			var m = Math.round(dcLibre(l) * 1000), k = ids.length, base = Math.floor(m / k), resto = m - base * k;
			$.each(ids, function (i, comId) {
				var c = dcComensal(comId), q = (base + (i < resto ? 1 : 0)) / 1000;
				if (c && !c.pagado && q > 0) c.items[id] = r3((c.items[id] || 0) + q);
			});
			dcRepartir = 0;
			dcGuardar();
			dcPintar();
		}
		function dcAgregarComensal(nombre) {
			if (dc.comensales.length >= 30) { toastr.warning('Máximo 30 comensales'); return; }
			var n = dc.sig++;
			dc.comensales.push({ id: n, nombre: $.trim(nombre).slice(0, 40) || 'Comensal ' + n, items: {}, pagado: null });
			dcGuardar();
			dcPintar();
		}
		// Partes iguales, con la misma cuenta que Mcaja::partesIguales. Cada parte, menos la última, lleva de cada producto
		// cantidad / partes que faltan, en milésimas (hacia abajo, o una milésima más si así queda más cerca de lo que le toca
		// pagar: se revisan los productos en orden) y paga su proporción; la última paga lo que queda.
		function dcMontoSim(s, qm) { var q = qm / 1000; return q >= s.c - 0.0005 ? Math.round(s.b) : Math.round(s.b * q / s.c); }
		function dcSimularPartes(lineas, n) {
			var sim = $.map(lineas, function (l) { return { id: l.id_detalle, c: r3(l.cantidad), b: l.bruto_exacto }; }), partes = [];
			for (var k = n; k >= 1; k--) {
				var monto = 0, sel = {}, qs = [], resto = 0, suma = 0;
				$.each(sim, function (i, s) { if (s.c > 0) resto += Math.round(s.b); });
				var meta = resto / k;
				$.each(sim, function (i, s) {
					qs[i] = s.c <= 0 ? 0 : (k === 1 ? Math.round(s.c * 1000) : Math.floor(Math.round(s.c * 1000) / k));
					if (qs[i] > 0) suma += dcMontoSim(s, qs[i]);
				});
				if (k > 1) {
					$.each(sim, function (i, s) {
						if (s.c <= 0 || Math.round(s.c * 1000) % k === 0) return;
						var d = dcMontoSim(s, qs[i] + 1) - (qs[i] > 0 ? dcMontoSim(s, qs[i]) : 0);
						if (Math.abs(suma + d - meta) < Math.abs(suma - meta)) { qs[i]++; suma += d; }
					});
				}
				$.each(sim, function (i, s) {
					if (s.c <= 0 || qs[i] <= 0) return;
					var q = qs[i] / 1000;
					sel[s.id] = q;
					if (q >= s.c - 0.0005) { monto += Math.round(s.b); s.c = 0; s.b = 0; return; }
					var parte = Math.round(s.b * q / s.c);
					monto += parte;
					s.b = s.b - parte;
					s.c = r3(s.c - q);
				});
				partes.push({ monto: monto, sel: sel });
			}
			return partes;
		}

		function abrirDividir() {
			if (!mesa || !mesa.id_cotizacion) return;
			if (pendientesMesa().length) { toastr.warning('Primero envía a la mesa los productos nuevos'); return; }
			var idMesa = mesa.id_mesa;
			dcTrasGuardar(function () {
				dcCargar().done(function () {
					if (!mesa || mesa.id_mesa !== idMesa) return;
					dcModo = dc.modo || 'comensal';
					dcPaso = null;
					dcRepartir = 0;
					$('#dcMesa').text(nombreMesa(mesa.id_mesa));
					dcPintar();
					$('#modalDividir').modal('show');
				});
			});
		}
		// Mientras la ventana está abierta se miran los cambios de otras cajas (cada 8 s), si aquí no se está editando nada
		$('#modalDividir').on('shown.bs.modal', function () {
			clearInterval(dcSondeo);
			dcSondeo = setInterval(function () {
				if (!dc || !dcRemoto || !mesa || dcGuardando || dcTimer || dcArrastre || dcModo === 'cobrar' || dcRepartir) return;
				if ($(document.activeElement).closest('#modalDividir').is('*') && $(document.activeElement).is('input, select, textarea')) return;
				var d = dc;
				$.ajax({ url: base + 'division', data: { id_mesa: d.id_mesa }, dataType: 'json', cache: false, global: false }).done(function (r) {
					if (dc !== d || dcGuardando || dcTimer || !r.disponible || r.id_cotizacion !== d.id_cot || (r.version || 0) <= (d.version || 0)) return;
					dc = dcDesdeServidor(r, d.id_mesa);
					dcAjustar();
					dcPintar();
				});
			}, 8000);
		}).on('hidden.bs.modal', function () { clearInterval(dcSondeo); });
		function dcPintar() {
			if (!dc || !mesa) return;
			var lineas = dcLineas(), porCobrar = 0, cobrado = 0;
			$.each(dcLineasTodas(), function (i, l) { porCobrar += l.bruto; });   // v1.24.0: con lo elegido de los menús
			$.each(dc.comensales, function (i, c) { if (c.pagado) cobrado += c.pagado.total; });
			$.each(dc.pagadas, function (i, p) { cobrado += p.total; });
			$('#dcResumen').html('Por cobrar en la mesa: <b>' + fmt(porCobrar) + '</b> · ' + esc(textoUnidades(contarUnidades(lineas)))
				+ (cobrado ? ' · Ya cobrado en partes: <b>' + fmt(cobrado) + '</b>' : ''));
			$('#dcModos').prop('hidden', dcModo === 'cobrar');
			$('#dcModos [data-dc-modo]').each(function () {
				var on = $(this).data('dc-modo') === dcModo;
				$(this).toggleClass('activo', on).attr('aria-selected', on ? 'true' : 'false');
			});
			$('#modalDividir [data-dc-vista]').prop('hidden', true).filter('[data-dc-vista="' + dcModo + '"]').prop('hidden', false);
			if (dcModo === 'comensal') dcPintarComensales(lineas);
			if (dcModo === 'iguales') dcPintarIguales(dcLineasTodas());   // v1.24.0: cada parte lleva su fracción de todo
		}
		function dcPintarComensales(lineas) {
			var hay = dc.comensales.length > 0;
			$('#dcComVacio').prop('hidden', hay).find('.dc-n').text(textoUnidades(contarUnidades(lineas)));
			$('#dcTablero').prop('hidden', !hay);
			if (!hay) return;
			var pend = dcPendientes(), sin = 0, $s = $('#dcSinLista').empty();
			$.each(lineas, function (i, l) {
				var libre = dcLibre(l);
				if (libre <= 0.0005) return;
				sin += contarUnidades([{ cantidad: libre }]);
				var chips = $.map(pend, function (c) {
					return '<button type="button" class="dc-chip" data-com="' + c.id + '" title="Asignar a ' + esc(c.nombre) + ' (con Mayús: todo)">+ ' + esc(c.nombre) + '</button>';
				}).join('') + (pend.length > 1 ? '<button type="button" class="dc-chip dividir" title="Repartir entre varios comensales">⑂ Dividir</button>' : '');
				var reparto = dcRepartir !== l.id_detalle ? '' : '<div class="dc-repartir">Repartir ' + esc(fmtCant(libre)) + ' entre:<br>'
					+ $.map(pend, function (c) { return '<label><input type="checkbox" value="' + c.id + '" checked> ' + esc(c.nombre) + '</label>'; }).join('')
					+ '<br><button type="button" class="btn btn-primary btn-xs dc-repartir-ok">Repartir</button> <button type="button" class="btn btn-default btn-xs dc-repartir-no">Cancelar</button></div>';
				$s.append('<div class="dc-prod" draggable="true" data-id="' + l.id_detalle + '" data-desde="0"><i class="fa fa-ellipsis-v dc-asa" aria-hidden="true"></i>'
					+ '<b>' + esc(l.nombre) + '</b><small>' + (l.agregado ? '+ ' + esc(l.agregado) + ' · ' : '') + '× ' + esc(fmtCant(libre)) + ' — ' + fmt(dcMontoLinea(l, libre)) + '</small>'
					+ (l.nota ? '<small><i>' + esc(l.nota) + '</i></small>' : '') + '<div class="dc-chips">' + chips + '</div>' + reparto + '</div>');
			});
			$('#dcSinN').text(sin);
			if (!$s.children().length) $s.html('<div class="dc-sin-listo"><i class="fa fa-check-circle"></i> Todo asignado</div>');
			var $c = $('#dcComLista').empty();
			$.each(dc.comensales, function (i, c) {
				if (c.pagado) {
					$c.append('<div class="dc-com pagado"><div class="dc-com-cab"><b>' + esc(c.nombre) + '</b></div><div class="dc-com-items"><i class="fa fa-check-circle"></i> Pagado · '
						+ esc(c.pagado.doc) + ' · ' + fmt(c.pagado.total) + '</div></div>');
					return;
				}
				var items = '', total = 0;
				$.each(c.items, function (id, q) {
					var l = dcLinea(Number(id));
					if (!l) return;
					var m = dcMontoLinea(l, q);
					total += m;
					items += '<div class="dc-item" draggable="true" data-id="' + l.id_detalle + '" data-desde="' + c.id + '"><span>' + esc(fmtCant(q)) + ' × ' + esc(l.nombre)
						+ (l.agregado ? ' <small class="text-muted">+ ' + esc(l.agregado) + '</small>' : '') + '</span><span class="monto">' + fmt(m) + '</span>'
						+ '<button type="button" class="dc-item-quitar" title="Devolver a sin asignar" aria-label="Quitar">&times;</button></div>';
				});
				$c.append('<div class="dc-com dc-zona" data-zona="' + c.id + '"><div class="dc-com-cab">'
					+ '<input type="text" class="dc-com-nombre" maxlength="40" value="' + esc(c.nombre) + '" aria-label="Nombre del comensal" title="Cambiar el nombre">'
					+ '<button type="button" class="dc-com-quitar" title="Quitar comensal (sus productos vuelven a sin asignar)"><i class="fa fa-trash-o"></i></button></div>'
					+ '<div class="dc-com-items">' + (items || '<div class="dc-com-vacio">Arrastra productos aquí</div>') + '</div>'
					+ '<div class="dc-com-pie"><span class="total">' + fmt(total) + '</span>'
					+ '<button type="button" class="btn btn-default btn-sm dc-com-precuenta" title="Precuenta de ' + esc(c.nombre) + '"' + (total ? '' : ' disabled') + '><i class="fa fa-print"></i></button>'
					+ '<button type="button" class="btn btn-primary btn-sm dc-com-cobrar"' + (total ? '' : ' disabled') + '><i class="fa fa-money"></i> Cobrar</button></div></div>');
			});
		}
		function dcPintarIguales(lineas) {
			var pagadas = dc.pagadas.length, minimo = Math.max(2, pagadas + 1);
			if (dc.partes < minimo) dc.partes = minimo;
			$('#dcPartesN').val(dc.partes).attr('min', minimo);
			var partes = lineas.length ? dcSimularPartes(lineas, dc.partes - pagadas) : [], filas = '', total = 0;
			$.each(dc.pagadas, function (i, p) {
				total += p.total;
				filas += '<tr class="pagada"><td><i class="fa fa-check-circle"></i> Parte ' + (i + 1) + '</td><td>Pagada · ' + esc(p.doc) + '</td><td class="num">' + fmt(p.total) + '</td><td class="acc"></td></tr>';
			});
			$.each(partes, function (j, p) {
				total += p.monto;
				filas += '<tr data-j="' + j + '"><td><i class="fa fa-user-o"></i> Parte ' + (pagadas + j + 1) + '</td><td class="text-muted">' + (j === 0 ? 'Siguiente a cobrar' : '') + '</td>'
					+ '<td class="num"><b>' + fmt(p.monto) + '</b></td><td class="acc"><button type="button" class="btn btn-default btn-sm dc-parte-precuenta" title="Precuenta de esta parte"><i class="fa fa-print"></i></button> '
					+ '<button type="button" class="btn btn-primary btn-sm dc-parte-cobrar"' + (j === 0 ? '' : ' disabled title="Las partes se cobran en orden"') + '><i class="fa fa-money"></i> Cobrar</button></td></tr>';
			});
			$('#dcPartes tbody').html(filas || '<tr><td colspan="4" class="text-muted">No queda nada por cobrar</td></tr>');
			$('#dcPartes tfoot').html('<tr><td>Total</td><td></td><td class="num">' + fmt(total) + '</td><td></td></tr>');
			$('#dcPorPersona').text(partes.length ? fmt(partes[0].monto) + ' c/u' : '');
		}
		function dcCambiarPartes(n) {
			var minimo = Math.max(2, dc.pagadas.length + 1);
			dc.partes = Math.max(minimo, Math.min(30, Math.round(Number(n) || minimo)));
			dcGuardar();
			dcPintar();
		}
		function dcImprimirPrecuenta(o) {
			if (!mesa) return;
			if (pendientesMesa().length) { toastr.warning('Primero envía a la mesa los productos nuevos'); return; }
			var url = base + 'precuenta?id_mesa=' + mesa.id_mesa + '&titulo=' + encodeURIComponent(o.titulo || '');
			var p = { id_mesa: mesa.id_mesa, titulo: o.titulo || '' };
			if (o.sel) { p.sel = $.map(o.sel, function (q, id) { return id + ':' + q; }).join(','); url += '&sel=' + encodeURIComponent(p.sel); }
			if (o.partes) { p.partes = o.partes; p.parte = o.parte; p.inicio = o.inicio || 0; url += '&partes=' + o.partes + '&parte=' + o.parte + '&inicio=' + (o.inicio || 0); }
			imprimirPrecuenta(p, url, bloquearImpresion('Imprimiendo precuenta…'));
			toastr.info('Imprimiendo precuenta: ' + o.titulo);
		}

		// ---- cobrar una parte: documento y cliente de esa parte, después el cobro de siempre ----
		function dcIrACobrar(tipo, ref, nombre, sel, monto) {
			if (pendientesMesa().length) { toastr.warning('Primero envía a la mesa los productos nuevos'); return; }
			dcPaso = { tipo: tipo, ref: ref, nombre: nombre, sel: sel, monto: monto };
			if (dcModo !== 'cobrar') dcModoPrevio = dcModo;
			dcModo = 'cobrar';
			$('#dcCobroNombre').text(nombre);
			$('#dcCobroTotal').text(fmt(monto));
			$('#dcCobroDetalle').html($.map(sel, function (q, id) {
				var l = dcLinea(Number(id));
				return l ? '<div><span>' + esc(fmtCant(q)) + ' × ' + esc(l.nombre) + (l.agregado ? ' + ' + esc(l.agregado) : '') + '</span><span>' + fmt(dcMontoLinea(l, q)) + '</span></div>' : null;
			}).join(''));
			$('#dcDocumento').html($('#cboDocumento').html()).val($('#cboDocumento').val());
			dcPintarCobro();
			dcPintar();
		}
		function dcDocumento() {
			var id = Number($('#dcDocumento').val());
			return $.grep(catalogo.documentos, function (d) { return d.id_tdocumento === id; })[0];
		}
		function dcPintarCobro() {
			var d = dcDocumento();
			$('#dcCliente').val(cliente ? ($('#txtCliente').val() || cliente.nombre_cliente) : 'Público general');
			$('#dcQuitarCliente').prop('disabled', !cliente);
			$('#dcFactura').prop('hidden', !(d && d.es_factura));
			$('#dcClienteFalta').prop('hidden', !(d && d.requiere_cliente && !cliente));
		}
		$('#dcDocumento').on('change', dcPintarCobro);
		$('#dcBuscarCliente').on('click', function () { $('#btnBuscarCliente').click(); });
		$('#dcQuitarCliente').on('click', function () { $('#btnQuitarCliente').click(); dcPintarCobro(); });
		// El buscador de clientes se abre encima de esta ventana: al cerrarlo se muestra el elegido
		$('#modalCliente').on('hidden.bs.modal', function () {
			if (!$('#modalDividir').hasClass('in')) return;
			$('body').addClass('modal-open');
			dcPintarCobro();
		});
		$('#dcVolver').on('click', function () { dcModo = dcModoPrevio; dcPaso = null; dcPintar(); });
		$('#dcCobroPrecuenta').on('click', function () { if (dcPaso) dcImprimirPrecuenta(dcPaso.precuenta || { sel: dcPaso.sel, titulo: dcPaso.nombre }); });
		$('#dcContinuar').on('click', function () {
			var p = dcPaso, d = dcDocumento();
			if (!p || !mesa) return;
			if (!d || !d.habilitado) { toastr.warning('Elige un documento disponible'); return; }
			if (d.requiere_cliente && !cliente) { toastr.warning(d.nombre + ' requiere seleccionar el cliente'); return; }
			// Factura: sus datos y quién la solicita se piden al continuar (ventana Datos de la factura, en abrirPago)
			$('#cboDocumento').val(d.id_tdocumento);
			// Si la parte es todo lo que queda en la mesa, es el cobro normal de la mesa; si no, un cobro de una parte
			var lineas = dcLineas(), todo = lineas.length > 0 && !$.grep(lineas, function (l) { return (p.sel[l.id_detalle] || 0) < l.cantidad - 0.0005; }).length;
			cobroParcial = null;
			if (!todo) {
				cobroParcial = {};
				$.each(p.sel, function (id, q) { if (q > 0) cobroParcial[id] = q; });
			}
			dcCobro = { id_mesa: mesa.id_mesa, id_cot: mesa.id_cotizacion, tipo: p.tipo, ref: p.ref, nombre: p.nombre, parcial: cobroParcial };
			dcPaso = null;
			dcModo = dcModoPrevio;
			actualizarModoMesa();
			renderCarro();
			mostrarPanel('pago');
			$('#modalDividir').one('hidden.bs.modal', abrirPago).modal('hide');
		});
		// Venta registrada (btnConfirmarPago): la parte queda pagada; con la mesa abierta, al cerrar el aviso se sigue con la división
		function dcRegistrarPago(c, r) {
			if (r.mesa_liberada !== false) {   // se cobró todo: la división ya no sirve
				dcBorrar(c.id_cot);
				if (dc && dc.id_cot === c.id_cot) dc = null;
				return;
			}
			var d = dc && dc.id_cot === c.id_cot ? dc : (!dcRemoto && dcLeer(c.id_cot) ? dcNormalizar(dcLeer(c.id_cot)) : null);
			if (!d) return;
			var doc = (r.documento || 'Documento') + (r.folio ? ' N° ' + r.folio : '');
			if (c.tipo === 'comensal') {
				$.each(d.comensales, function (i, x) { if (x.id === c.ref) { x.pagado = { total: r.total, doc: doc }; x.items = {}; } });
			} else {
				d.pagadas.push({ total: r.total, doc: doc });
			}
			dc = d;
			dcGuardar(true);
			dcContinuar = c.id_mesa;
		}
		$('#modalVentaOk').on('hidden.bs.modal', function () {
			var id = dcContinuar, intentos = 0;
			dcContinuar = 0;
			if (!id) return;
			(function esperar() {
				if (mesa && mesa.id_mesa === id && mesa.id_cotizacion && drawerMesaAbierto() && !$('.modal.in').length) { abrirDividir(); return; }
				if (++intentos < 300) setTimeout(esperar, 200);   // v1.23.0: hasta 1 minuto (la ventana del vuelto puede seguir abierta)
			})();
		});

		// ---- eventos de la ventana ----
		$('#dcModos').on('click', '[data-dc-modo]', function () {
			dcModo = $(this).data('dc-modo');
			dc.modo = dcModo;
			dcGuardar();
			dcPintar();
		});
		$('#dcCrearComensales').on('click', function () {
			dcAgregarComensal('');
			dcAgregarComensal('');
			$('#dcNuevoNombre').focus();
		});
		$('#dcNuevo').on('submit', function (e) {
			e.preventDefault();
			dcAgregarComensal($('#dcNuevoNombre').val());
			$('#dcNuevoNombre').val('').focus();
		});
		$('#dcSinLista').on('click', '.dc-chip[data-com]', function (e) {
			dcAsignar(Number($(this).closest('.dc-prod').data('id')), Number($(this).data('com')), e.shiftKey);
		}).on('click', '.dc-chip.dividir', function () {
			dcRepartir = Number($(this).closest('.dc-prod').data('id'));
			dcPintar();
		}).on('click', '.dc-repartir-ok', function () {
			var ids = $(this).closest('.dc-repartir').find('input:checked').map(function () { return Number(this.value); }).get();
			if (!ids.length) { toastr.warning('Elige al menos un comensal'); return; }
			dcRepartirEntre(Number($(this).closest('.dc-prod').data('id')), ids);
		}).on('click', '.dc-repartir-no', function () {
			dcRepartir = 0;
			dcPintar();
		});
		$('#dcComLista').on('click', '.dc-item-quitar', function () {
			var $i = $(this).closest('.dc-item');
			dcQuitar(Number($i.data('id')), Number($i.data('desde')));
		}).on('change', '.dc-com-nombre', function () {
			var c = dcComensal(Number($(this).closest('.dc-com').data('zona')));
			if (!c) return;
			c.nombre = $.trim($(this).val()).slice(0, 40) || 'Comensal ' + c.id;
			dcGuardar();
			dcPintar();
		}).on('keydown', '.dc-com-nombre', function (e) { if (e.which === 13) { e.preventDefault(); $(this).blur(); } })
		.on('click', '.dc-com-quitar', function () {
			var id = Number($(this).closest('.dc-com').data('zona'));
			dc.comensales = $.grep(dc.comensales, function (c) { return c.id !== id; });
			dcGuardar();
			dcPintar();
		}).on('click', '.dc-com-precuenta', function () {
			var c = dcComensal(Number($(this).closest('.dc-com').data('zona')));
			if (c) dcImprimirPrecuenta({ sel: c.items, titulo: c.nombre });
		}).on('click', '.dc-com-cobrar', function () {
			var c = dcComensal(Number($(this).closest('.dc-com').data('zona')));
			if (c && !$.isEmptyObject(c.items)) dcIrACobrar('comensal', c.id, c.nombre, $.extend({}, c.items), dcMonto(c.items));
		});
		// Arrastrar y soltar (escritorio). En pantallas táctiles se usan los botones bajo cada producto
		$('#modalDividir').on('dragstart', '.dc-prod, .dc-item', function (e) {
			dcArrastre = { id: Number($(this).data('id')), desde: Number($(this).data('desde')) };
			var dt = e.originalEvent.dataTransfer;
			dt.effectAllowed = 'move';
			try { dt.setData('text/plain', String(dcArrastre.id)); } catch (x) {}   // Firefox no arrastra sin datos
			$(this).addClass('dc-arrastrando');
		}).on('dragend', '.dc-prod, .dc-item', function () {
			$(this).removeClass('dc-arrastrando');
			$('#modalDividir .dc-zona').removeClass('sobre');
			dcArrastre = null;
		}).on('dragover', '.dc-zona', function (e) {
			if (!dcArrastre) return;
			e.preventDefault();
			e.originalEvent.dataTransfer.dropEffect = 'move';
			$(this).addClass('sobre');
		}).on('dragleave', '.dc-zona', function (e) {
			if (!$.contains(this, e.originalEvent.relatedTarget)) $(this).removeClass('sobre');
		}).on('drop', '.dc-zona', function (e) {
			e.preventDefault();
			$(this).removeClass('sobre');
			var a = dcArrastre, hasta = Number($(this).data('zona'));
			dcArrastre = null;
			if (!a || hasta === a.desde) return;
			if (hasta === 0) dcQuitar(a.id, a.desde);
			else if (a.desde === 0) dcAsignar(a.id, hasta, e.originalEvent.shiftKey);
			else dcMover(a.id, a.desde, hasta);
		});
		$('#dcPartesMenos').on('click', function () { dcCambiarPartes(dc.partes - 1); });
		$('#dcPartesMas').on('click', function () { dcCambiarPartes(dc.partes + 1); });
		$('#dcPartesN').on('change', function () { dcCambiarPartes($(this).val()); });
		$('#dcPartes').on('click', '.dc-parte-precuenta', function () {
			var j = Number($(this).closest('tr').data('j')), pagadas = dc.pagadas.length;
			dcImprimirPrecuenta({ partes: dc.partes - pagadas, parte: j + 1, inicio: pagadas, titulo: 'Parte ' + (pagadas + j + 1) + ' de ' + dc.partes });
		}).on('click', '.dc-parte-cobrar', function () {
			var pagadas = dc.pagadas.length, partes = dcSimularPartes(dcLineasTodas(), dc.partes - pagadas), k = pagadas + 1;
			if (!partes.length) return;
			dcIrACobrar('parte', k, 'Parte ' + k + ' de ' + dc.partes, partes[0].sel, partes[0].monto);
			dcPaso.precuenta = { partes: dc.partes - pagadas, parte: 1, inicio: pagadas, titulo: 'Parte ' + k + ' de ' + dc.partes };
		});
		$('#dcLibre').on('click', function () {
			$('#modalDividir').one('hidden.bs.modal', function () { abrirMesaProductos('parcial'); }).modal('hide');
		});
		$('#modalDividir').on('hidden.bs.modal', function () { dcPaso = null; if (dcModo === 'cobrar') dcModo = dcModoPrevio; });

		$('#btnCancelarParcial').on('click', function (e) {
			e.preventDefault();
			cobroParcial = null;
			dcCobro = null;
			actualizarModoMesa();
			renderCarro();
		});
		$('#btnPrecuenta').on('click', function () {
			if (pendientesMesa().length) { toastr.warning('Primero envía a la mesa los productos nuevos'); return; }
			var parcial = cobroParcial;
			cobroParcial = null;   // la precuenta es de toda la mesa
			var descuento = totales().descuento;
			cobroParcial = parcial;
			imprimirPrecuenta({ id_mesa: mesa.id_mesa, descuento: descuento }, base + 'precuenta?id_mesa=' + mesa.id_mesa + '&descuento=' + descuento, bloquearImpresion('Imprimiendo precuenta…'));
			toastr.info('Imprimiendo precuenta de ' + nombreMesa(mesa.id_mesa));
		});

		$('#btnAnularPedido').on('click', function () {
			var idMesa = mesa.id_mesa;
			swal({ title: 'Anular pedido de ' + nombreMesa(idMesa), text: 'Se anulan todos sus productos y la mesa queda libre. Indica el motivo:', type: 'input', showCancelButton: true, confirmButtonColor: '#C9372C', confirmButtonText: 'Anular y liberar', cancelButtonText: 'Volver', inputPlaceholder: 'Motivo' }, function (motivo) {
				if (motivo === false) return;
				if (!$.trim(motivo)) { toastr.warning('Indica el motivo de la anulación'); return; }
				enviarConAutorizacion(function (aut) {
					return postJson('anularPedidoMesa', { id_mesa: idMesa, motivo: motivo, autorizacion: aut });
				}, function (r) {
					imprimirComandaBloqueando(r.comanda_id, 'Anulación');
					toastr.success('Pedido anulado: ' + nombreMesa(idMesa) + ' quedó libre');
					limpiarVenta();
					cargarMesas();
					$('#lnkMesas').tab('show');
				});
			});
		});
		function ofrecerLiberar(id) {
			swal({ title: nombreMesa(id) + ' bloqueada', text: 'Está marcada como ocupada o en uso, pero no tiene un pedido que se pueda abrir aquí. ¿Liberarla?', type: 'warning', showCancelButton: true, confirmButtonColor: '#C9372C', confirmButtonText: 'Liberar', cancelButtonText: 'Cancelar' }, function (ok) {
				if (!ok) return;
				enviarConAutorizacion(function (aut) { return postJson('liberarMesa', { id_mesa: id, autorizacion: aut }); },
					function () { toastr.success(nombreMesa(id) + ' liberada'); cargarMesas(); });
			});
		}

