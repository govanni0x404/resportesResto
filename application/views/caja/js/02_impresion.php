		// ---------------- impresión ----------------
		// Preferencia por computador (la impresora es de cada caja). Por defecto se imprime, como el POS.
		var CLAVE_IMPRIMIR = 'cajaWeb.imprimirTicket';
		function imprimeTicket() {
			// Configuraciones > Impresión de documentos > Imprimir: desmarcado, la caja no imprime boletas/facturas sola
			if (catalogo && catalogo.config && catalogo.config.imprimir_documento === false) return false;
			try { return localStorage.getItem(CLAVE_IMPRIMIR) !== '0'; } catch (e) { return true; }
		}
		$('.chk-imprimir').prop('checked', imprimeTicket()).on('change', function () {
			var on = $(this).prop('checked');
			$('.chk-imprimir').prop('checked', on);
			try { localStorage.setItem(CLAVE_IMPRIMIR, on ? '1' : '0'); } catch (e) {}
		});

		// Ancho del papel (POS80 / POS58), también por computador. Va en cookie para que el
		// servidor arme el ticket a ese ancho, incluso al abrirlo desde un enlace.
		function papelTicket() {
			return /(?:^|;\s*)caja_papel=58(?:;|$)/.test(document.cookie) ? '58' : '80';
		}
		$('.cbo-papel').val(papelTicket()).on('change', function () {
			var papel = $(this).val();
			$('.cbo-papel').val(papel);
			document.cookie = 'caja_papel=' + papel + '; path=/; max-age=315360000; SameSite=Lax';
		});

		// La página del ticket se imprime sola al cargar (?imprimir=1). En un iframe oculto no hay
		// ventana emergente que bloquear, y con Chrome --kiosk-printing sale directo a la impresora.
		// 16-11-2026: un marco por impresión (antes uno solo que se borraba en la siguiente: con boleta + comanda, o varias
		// comandas seguidas de la app, solo salía la última). Cada marco se quita solo después de un minuto.
		function imprimir(url) {
			var $f = $('<iframe class="caja-frame-impresion" aria-hidden="true">')
				.attr('src', url + (url.indexOf('?') < 0 ? '?' : '&') + 'imprimir=1').appendTo('body');
			setTimeout(function () { $f.remove(); }, 60000);
		}

		// Boleta/factura a la impresora de la caja por el agente de impresión (como las comandas). Si no hay impresora
		// de caja configurada o no se pudo enviar, se imprime desde el navegador como antes. despues() = siguiente impresión.
		// Impresión local (12-11-2026, como mipdv_web): boletas, facturas, notas de crédito y comprobantes van directo al
		// agente de impresión de este equipo (localhost:5050, ver impresionLocal.php), también al reimprimir. Está activa salvo
		// que Configuraciones > Imprimir documentos en el equipo de la caja esté desmarcado (entonces, como antes). Si el agente
		// no puede imprimir se avisa por qué; no se abre la impresión del navegador.
		function textoPlano(html) { return $('<div>').html(html).text(); }
		function impresionLocalActiva() {
			return !!(catalogo && catalogo.config && catalogo.config.impresion_local);
		}
		// Configuraciones > "Boletas, precuentas y comandas con el diseño de la página".
		// 17-11-2026: ya no por el navegador (salía el cuadro de impresión): la página se convierte en imagen y la imprime
		// el agente de impresión, directo (assets/js/impresion-local/imagenDocumento.js). Si la imagen no se puede hacer,
		// sale con el formato del agente para que no quede sin imprimir.
		function documentosEnNavegador() {
			return !!(catalogo && catalogo.config && catalogo.config.documentos_navegador);
		}
		function conImagen() {
			return documentosEnNavegador() && impresionLocalActiva() && !!window.ImagenDocumento && !!window.ImpresionLocal;
		}
		var URL_SUBIR_IMAGEN = base + 'subirImagenImpresion';
		// Imprime la página como imagen; si la imagen no se pudo hacer (o el agente no la aceptó) sale con porAgente(),
		// el formato del agente. Si el agente no respondió a tiempo no se repite: puede haber impreso igual.
		// 18-11-2026: preparada = promesa de prepararImagen() hecha antes (ej. la comanda mientras sale la boleta)
		// 18-11-2026: sube sin la sesión con la firma de la Caja (más rápido); si no, con la sesión
		var URL_SUBIR_LIBRE = "<?php echo base_url(); ?>index.php/ImagenImpresion/subir";
		function prepararImagen(urlPagina) {
			var p = ImagenDocumento.preparar(urlPagina, { subir: URL_SUBIR_IMAGEN, subirLibre: URL_SUBIR_LIBRE, firma: window.IMAGEN_IMPRESION_FIRMA || null });
			p.catch(function () {});   // el error se atiende al imprimir (sale con el formato del agente)
			return p;
		}
		// 18-11-2026: Caja abierta con ?medir=1 → aviso con lo que tardó cada paso de la impresión como imagen
		var MEDIR_IMPRESION = /[?&]medir=1(?:&|$)/.test(location.search);
		function avisoTiempos(urlPagina, destino, t) {
			if (!MEDIR_IMPRESION || !t) return;
			var s = function (n) { return ((n || 0) / 1000).toFixed(1) + ' s'; };
			var que = /ticket\//.test(urlPagina) ? 'Boleta' : (/comanda\?/.test(urlPagina) ? 'Comanda' : 'Precuenta');
			var total = (t.pagina || 0) + (t.dibujo || 0) + (t.subida || 0) + (t.impresora || 0);
			// v1.25.3: dentro de "Impresora", cuánto se esperó la lista de impresoras del agente (0 si estaba recordada)
			var ul = window.ImpresionLocal && ImpresionLocal.ultimaLista ? ImpresionLocal.ultimaLista() : null;
			toastr.info('Página ' + s(t.pagina) + ' · Dibujo ' + s(t.dibujo) + ' · Subida ' + s(t.subida)
				+ ' · Esperó turno ' + s(t.espera) + ' · Impresora ' + s(t.impresora)
				+ (ul ? ' (lista de impresoras ' + (ul.recordada ? 'recordada' : s(ul.ms)) + ')' : '') + ' · Total ' + s(total),
				'Tiempos: ' + que + ' (' + ((destino && destino.nombre) || '') + ')', { timeOut: 0, extendedTimeOut: 0, closeButton: true });
			if (window.console) console.log('Tiempos impresión', que, t);
		}
		function imprimirComoImagen(urlPagina, destino, copias, porAgente, preparada) {
			// v1.23.2: destino "sin impresión": no se espera la imagen ni se manda nada (tampoco es un error)
			if (ImpresionLocal.sinImpresion && ImpresionLocal.sinImpresion(destino)) return ImpresionLocal.imprimir(destino, []);
			return (preparada || prepararImagen(urlPagina)).then(function (urls) {
				return ImagenDocumento.enviar(urls, destino, copias || 1);
			}).then(function (res) {
				avisoTiempos(urlPagina, destino, res && res.tiempos);
				return res;
			}).catch(function (e) {
				if (e && e.tipo === 'tiempo') throw e;
				if (window.console) console.warn('Impresión como imagen:', e);
				return porAgente();
			});
		}
		function imprimirTicket(idVenta, despues) {
			if (conImagen()) {
				imprimirTicketLocal(idVenta, despues, true);
				return;
			}
			if (documentosEnNavegador() && !impresionLocalActiva()) {
				// Sin agente en este equipo: como antes, por el navegador
				imprimir(base + 'ticket/' + idVenta);
				if (despues) setTimeout(despues, 800);   // la comanda sale después del documento
				return;
			}
			if (impresionLocalActiva()) {
				imprimirTicketLocal(idVenta, despues);
				return;
			}
			imprimirTicketServidor(idVenta, despues);
		}
		// "Imprimir ticket" (Venta registrada) y el ícono de la pestaña Ventas también van al agente del equipo; con la
		// impresión local desmarcada abren el ticket en el navegador, como antes
		$(document).on('click', '#ventaOkTicket, #tablaVentas a[title="Ticket"]', function (e) {
			if (!impresionLocalActiva()) return;
			var m = /ticket\/(\d+)/.exec($(this).attr('href') || '');
			if (!m) return;
			e.preventDefault();
			imprimirTicket(Number(m[1]), bloquearImpresion('Imprimiendo documento…'));
		});
		function avisoNoImpreso(texto) {
			toastr.error(texto + ' Cuando esté listo, reimprímelo en Opciones > Documentos.', 'No se imprimió el documento',
				{ timeOut: 15000, extendedTimeOut: 5000, closeButton: true });
		}
		// imagen = true: con el diseño de la página, como imagen (si falla, con el formato del agente)
		function imprimirTicketLocal(idVenta, despues, imagen) {
			var fin = function () { if (despues) despues(); };
			if (!window.ImpresionLocal) {
				avisoNoImpreso('No se cargó el cliente de impresión local: recarga la Caja.');
				fin();
				return;
			}
			// 18-11-2026: la imagen se empieza a dibujar a la vez que se piden la impresora y el formato del agente
			var prep = imagen ? prepararImagen(base + 'ticket/' + idVenta) : null;
			$.ajax({ url: base + 'ticketLocal', type: 'POST', dataType: 'json', data: { id_venta: idVenta } })
				.done(function (r) {
					var envio = !imagen ? ImpresionLocal.imprimir(r.destino, r.operaciones)
						: imprimirComoImagen(base + 'ticket/' + idVenta, r.destino, r.copias, function () { return ImpresionLocal.imprimir(r.destino, r.operaciones); }, prep);
					envio.then(function (res) {
						toastr.success('Documento enviado a ' + res.impresora, '', { timeOut: 2500 });
						fin();
					}, function (e) {
						// No respondió a tiempo: puede haber impreso igual, por eso no se repite
						if (e && e.tipo === 'tiempo') {
							toastr.warning(textoPlano(ImpresionLocal.mensaje(e)) + ' Si no salió, reimprímelo en Opciones > Documentos.', 'Impresión', { timeOut: 15000, closeButton: true });
						} else {
							avisoNoImpreso(textoPlano(ImpresionLocal.mensaje(e)));
						}
						fin();
					});
				})
				.fail(function (x) {
					avisoNoImpreso(x && x.responseJSON && x.responseJSON.error ? x.responseJSON.error + '.' : 'No se pudo preparar el documento.');
					fin();
				});
		}
		// 16-11-2026: precuenta directa al agente de impresión del equipo (como la boleta). Sin impresión local, como antes
		// (se abre en el navegador). p = {id_mesa, sel, partes, parte, inicio, descuento, titulo}
		// 17-11-2026: con el diseño de la página, como imagen por el agente (antes por el navegador, con el cuadro de impresión)
		// 18-11-2026: listo() = avisa que terminó (quita la pantalla de "Imprimiendo…")
		function imprimirPrecuenta(p, url, listo) {
			listo = listo || function () {};
			if (!impresionLocalActiva() || !window.ImpresionLocal) { imprimir(url); setTimeout(listo, 800); return; }
			$.ajax({ url: base + 'precuentaLocal', type: 'POST', dataType: 'json', data: p })
				.done(function (r) {
					var porAgente = function () { return ImpresionLocal.imprimir(r.destino, r.operaciones); };
					(conImagen() ? imprimirComoImagen(url, r.destino, 1, porAgente) : porAgente()).then(function (res) {
						listo();
						toastr.success('Precuenta enviada a ' + res.impresora, '', { timeOut: 2500 });
					}, function (e) {
						listo();
						toastr.error(textoPlano(ImpresionLocal.mensaje(e)), 'No se imprimió la precuenta', { timeOut: 12000, closeButton: true });
					});
				})
				.fail(function (x) {
					listo();
					toastr.error((x && x.responseJSON && x.responseJSON.error) || 'No se pudo preparar la precuenta', 'Precuenta', { timeOut: 9000 });
				});
		}
		function imprimirTicketServidor(idVenta, despues) {
			$.ajax({ url: base + 'imprimirTicket', type: 'POST', dataType: 'json', data: { id_venta: idVenta } })
				.done(function (r) {
					if (r.ok) toastr.success('Boleta enviada a la impresora', '', { timeOut: 2500 });
					else {
						if (r.detalle && !r.navegador) toastr.error('Boleta: ' + r.detalle, '', { timeOut: 9000 });
						if (r.navegador) imprimir(base + 'ticket/' + idVenta);
					}
				})
				.fail(function () { imprimir(base + 'ticket/' + idVenta); })
				.always(function () { if (despues) despues(); });
		}

		// Comanda web (cocina, barra...): se imprime después del ticket, con el mensaje de cada producto.
		// Las líneas van por POST porque el mensaje no queda guardado en detalle_venta en todos los clientes.
		var CLAVE_COMANDA = 'cajaWeb.imprimirComanda';
		function imprimeComanda() {
			try { return localStorage.getItem(CLAVE_COMANDA) !== '0'; } catch (e) { return true; }
		}
		$('.chk-comanda').prop('checked', imprimeComanda()).on('change', function () {
			var on = this.checked;
			$('.chk-comanda').prop('checked', on);
			try { localStorage.setItem(CLAVE_COMANDA, on ? '1' : '0'); } catch (e) {}
		});
		// Cada destino de impresión (cocina, barra...) imprime en su propia impresora; lo envía el servidor
		function pendientesMesa() {
			return $.grep(carro, function (l) { return !l.enviada; });
		}
		// La comanda la arma y guarda el servidor al grabar el pedido (venta, envío a mesa, anulación, cambio de mesa,
		// reenvío) y devuelve su número: aquí solo se pide imprimirla. que = texto del aviso (Comanda, Anulación...).
		// 18-11-2026: listo() = avisa que terminó (quita la pantalla de "Imprimiendo…")
		function imprimirComanda(comandaId, que, preparada, listo) {
			listo = listo || function () {};
			if (!comandaId) { listo(); return; }
			if (impresionLocalActiva()) { imprimirComandaLocal(comandaId, que, preparada, listo); return; }
			$.ajax({
				url: base + 'imprimirComanda', type: 'POST', dataType: 'json', data: { comanda_id: comandaId }
			}).done(function (r) {
				var com = r.comandas || [];
				if (!com.length) toastr.info('Ningún producto tiene impresora (destino "Sin impresión"): no sale ' + (que || 'comanda').toLowerCase() + '.', '', { timeOut: 5000 });
				$.each(com, function (i, c) {
					if (c.ok) toastr.success((que || 'Comanda') + ' enviada a ' + c.destino, '', { timeOut: 2500 });
					else toastr.error('Comanda de ' + c.destino + ': ' + c.detalle, '', { timeOut: 9000 });
				});
			}).fail(function (x) {
				toastr.error('No se pudo enviar la comanda: ' + ((x.responseJSON && x.responseJSON.error) || 'error de conexión'), '', { timeOut: 9000 });
			}).always(listo);
		}
		// Impresión local (12-11-2026): las impresoras están en el local y el sistema en un servidor, así que cada comanda va
		// al agente de impresión de este equipo (localhost:5050), una por destino de impresión de los productos (cocina,
		// barra...) con todo lo de la venta o mesa junto, una tras otra. Si salen todas, el servidor la descarta.
		// 18-11-2026: prepara la comanda sin imprimirla: pide los destinos y, con "diseño de la página", empieza a dibujar y
		// subir la imagen de TODOS los destinos a la vez. Así la de la venta directa queda lista mientras sale la boleta.
		// Devuelve una promesa {r, imagenes: {id_destino: promesa de urls}}.
		function prepararComandaLocal(comandaId) {
			return new Promise(function (ok, mal) {
				$.ajax({ url: base + 'comandaLocal', type: 'POST', dataType: 'json', data: { comanda_id: comandaId } }).done(function (r) {
					var imagenes = {};
					if (conImagen()) {
						$.each(r.comandas || [], function (i, g) {
							imagenes[g.id] = prepararImagen(base + 'comanda?comanda_id=' + encodeURIComponent(comandaId) + '&solo=' + g.id);
						});
					}
					ok({ r: r, imagenes: imagenes });
				}).fail(mal);
			});
		}
		// preparada = promesa de prepararComandaLocal (opcional)
		function imprimirComandaLocal(comandaId, que, preparada, listo) {
			que = que || 'Comanda';
			listo = listo || function () {};
			if (!window.ImpresionLocal) { listo(); toastr.error('No se cargó el cliente de impresión local: recarga la Caja.', que + ' sin imprimir', { timeOut: 15000, closeButton: true }); return; }
			(preparada || prepararComandaLocal(comandaId)).then(function (p) {
				var r = p.r, grupos = r.comandas || [], i = 0, fallas = 0;
				// 19-11-2026: todos los productos con destino "Sin impresión" (o sin destino): no hay nada que imprimir, se avisa
				if (!grupos.length) toastr.info('Ningún producto tiene impresora (destino "Sin impresión"): no sale ' + que.toLowerCase() + '.', '', { timeOut: 5000 });
				// 17-11-2026: con "Boletas, precuentas y comandas con el diseño de la página", cada destino (caja, cocina,
				// barra...) recibe su comanda como imagen por el agente, directo (antes la de la caja salía por el navegador,
				// con el cuadro de impresión). Si la imagen no se puede hacer, sale con el formato del agente.
				var imagen = conImagen();
				(function siguiente() {
					if (i >= grupos.length) {
						listo();
						if (!fallas) $.ajax({ url: base + 'comandaImpresa', type: 'POST', dataType: 'json', data: { comanda_id: comandaId }, global: false });
						return;
					}
					var g = grupos[i++];
					var porAgente = function () { return ImpresionLocal.imprimir(g.destino, g.operaciones); };
					var envio = imagen ? imprimirComoImagen(base + 'comanda?comanda_id=' + encodeURIComponent(comandaId) + '&solo=' + g.id, g.destino, 1, porAgente, p.imagenes[g.id]) : porAgente();
					envio.then(function (res) {
						toastr.success(que + ' enviada a ' + g.nombre + ' (' + res.impresora + ')', '', { timeOut: 2500 });
						siguiente();
					}, function (e) {
						fallas++;
						toastr.error(textoPlano(ImpresionLocal.mensaje(e)), que + ' de ' + g.nombre + ' sin imprimir', { timeOut: 15000, closeButton: true });
						siguiente();
					});
				})();
			}, function (x) {
				listo();
				toastr.error((x && x.responseJSON && x.responseJSON.error) || 'error de conexión', 'No se pudo preparar la ' + que.toLowerCase(), { timeOut: 15000, closeButton: true });
			});
		}
		// Reenviar a cocina lo ya guardado en la mesa: idsDetalle = líneas (vacío = todo el pedido)
		function reenviarComanda(idMesa, idsDetalle) {
			postJson('reenviarComanda', { id_mesa: idMesa, ids_detalle: idsDetalle || [] }).done(function (r) {
				imprimirComandaBloqueando(r.comanda_id, 'Reenvío');
			}).fail(error);
		}

		// ---------------- pantalla "Imprimiendo…" ----------------
		// 18-11-2026 (pedido del usuario): mientras salen la boleta, la precuenta o las comandas, la pantalla queda
		// bloqueada (mouse y teclado) para que nadie toque nada en esos segundos. Cada impresión suma 1 y su listo() resta 1;
		// se quita cuando no queda ninguna. Si algo se traba, se quita sola a los 25 s (la Caja nunca queda bloqueada).
		// Las comandas que llegan solas de la app de garzones NO bloquean (no interrumpen al cajero).
		var imprimiendoN = 0, imprimiendoTope = null;
		var $bloqueo = $('<div id="bloqueoImpresion" role="alertdialog" aria-modal="true" aria-live="assertive" aria-label="Imprimiendo">'
			+ '<div class="bloqueo-caja"><div class="bloqueo-icono"><i class="fa fa-print"></i><span class="bloqueo-giro"></span></div>'
			+ '<div class="bloqueo-texto">Imprimiendo…</div><div class="bloqueo-sub">Espera un momento, no cierres ni toques la Caja</div></div></div>').appendTo('body');
		function quitarBloqueo() {
			imprimiendoN = 0;
			clearTimeout(imprimiendoTope);
			$bloqueo.removeClass('visible');
		}
		/** Muestra la pantalla y devuelve listo(): llamarlo cuando esa impresión terminó (salió bien o falló). */
		function bloquearImpresion(texto) {
			imprimiendoN++;
			$bloqueo.find('.bloqueo-texto').text(texto || 'Imprimiendo…');
			if (document.activeElement && document.activeElement.blur) document.activeElement.blur();
			$bloqueo.addClass('visible');
			clearTimeout(imprimiendoTope);
			imprimiendoTope = setTimeout(quitarBloqueo, 25000);
			var hecho = false;
			return function () {
				if (hecho) return;
				hecho = true;
				imprimiendoN = Math.max(0, imprimiendoN - 1);
				if (!imprimiendoN) {
					quitarBloqueo();
					// Vuelve al buscador de productos si no hay una ventana abierta (ej. "Venta registrada")
					if (!$('.modal.in:visible').length) $('#buscarProducto:visible').focus();
				}
			};
		}
		/** Imprime la comanda con la pantalla "Imprimiendo…" (solo si hay comanda: sin destinos no se muestra nada). */
		function imprimirComandaBloqueando(comandaId, que) {
			if (!comandaId) return;
			imprimirComanda(comandaId, que, null, bloquearImpresion('Imprimiendo ' + (que || 'comanda').toLowerCase() + '…'));
		}
		// El teclado no hace nada mientras se imprime (Enter, F2, Esc...)
		document.addEventListener('keydown', function (e) {
			if (imprimiendoN > 0) { e.preventDefault(); e.stopPropagation(); }
		}, true);

