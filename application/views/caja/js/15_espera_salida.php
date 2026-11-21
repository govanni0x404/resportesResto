		// ---------------- ventas en espera, salida de la caja y auditoría (10-11-2026) ----------------
		// - Opciones > Poner en espera: la venta directa se guarda con un nombre y la caja queda libre.
		// - Opciones > Ventas en espera: se carga una (solo con la venta actual vacía) o se anula con justificación.
		// - Salir de la Caja por el menú (o cambiar de empresa) con una venta en curso: se avisa; la venta directa pasa a
		//   espera y lo no enviado de una mesa queda como omitido en la auditoría.
		// - Recargar o cerrar la página: lo mismo sin preguntar (el navegador no deja mostrar una ventana propia). Se manda
		//   al salir (fetch keepalive o sendBeacon) y se reenvía al volver por si no alcanzó a llegar (el uid evita duplicados).
		// - Vaciar una venta directa y salir de una mesa sin enviar también quedan en la auditoría.
		// Servidor: Caja::esperas, ponerEnEspera, recuperarEspera, anularEspera, registrarSalida y auditar (models/Mventasespera.php).
		var USUARIO = <?php echo (int)$this->session->userdata('id_usuario'); ?>;
		var EMPRESA = '<?php echo md5($this->session->userdata('nombre_conexion') . '|' . $this->session->userdata('db_server') . '|' . $this->session->userdata('bd_name')); ?>';
		var CLAVE_SALIDA = 'cajaWeb.salidaPendiente';
		var ORIGEN_ESPERA = { menu: 'Al salir de la caja', recarga: 'Al recargar la página' };
		var esperas = [], esperasDisponible = false, esperasIniciadas = false, esperaAnulando = null;
		var destinoSalida = null, saliendo = false, salidaEnviada = false;

		function nuevoUid() {
			var a = new Uint8Array(16), c = window.crypto || window.msCrypto;
			if (c && c.getRandomValues) c.getRandomValues(a);
			else for (var i = 0; i < a.length; i++) a[i] = Math.floor(Math.random() * 256);
			return Array.prototype.map.call(a, function (b) { return ('0' + b.toString(16)).slice(-2); }).join('');
		}
		// Un producto pesado cuenta como 1 ítem (igual que el carro)
		function contarUnidades(lista) {
			var u = 0;
			$.each(lista, function (i, l) { u += l.cantidad === Math.round(l.cantidad) ? l.cantidad : 1; });
			return u;
		}
		function textoUnidades(n) { return n === 1 ? '1 producto' : fmtCant(n) + ' productos'; }
		function montoLineas(lista) {
			var t = 0;
			$.each(lista, function (i, l) { t += Math.round(precioLinea(l) * l.cantidad); });
			return t;
		}
		// Líneas sin enviar, con el nombre de sus opciones para que la auditoría se lea aunque cambie la carta
		function lineasParaGuardar(lista) {
			return $.map(lista, function (l) {
				if (l.enviada) return null;
				var menuL = l.menu && l.menu.length ? menuDe(l.id_producto) : null;   // v1.24.0
				return { id_producto: l.id_producto, nombre: l.nombre, precio: l.precio, cantidad: l.cantidad, agregados: (l.agregados || []).slice(),
					nota: l.nota || '', notas_agregados: l.notas_agregados || {}, menu: (l.menu || []).slice(), sushi: l.sushi || null,
					opciones: $.map(l.agregados || [], function (id) { var a = agregado(id); return a ? a.nombre : null; })
						.concat($.map(menuL ? l.menu : [], function (e) { var o = opcionMenu(menuL, e.id); return o ? o.nombre : null; })).join(', ') };
			});
		}
		// La venta directa en curso: la que está en pantalla, o la que quedó guardada mientras se atiende una mesa
		function ventaDirectaActual() {
			if (!mesa) {
				if (!carro.length) return null;
				return { lineas: lineasParaGuardar(carro), cliente: cliente, txt_cliente: $('#txtCliente').val(), descuento: $('#txtDescuento').val(),
					tipo_desc: tipoDesc, observacion: $('#txtObservacion').val(), id_tdocumento: Number($('#cboDocumento').val()) || 0,
					id_lprecio: listaActual || 0, total: totales().total };
			}
			var v = ventaDirectaGuardada;
			if (!v || !v.carro || !v.carro.length) return null;
			var sub = montoLineas(v.carro), valor = Math.max(0, Number(v.descuento) || 0);
			var desc = Math.min(sub, v.tipoDesc === 'porc' ? Math.round(sub * Math.min(valor, 100) / 100) : Math.round(valor));
			return { lineas: lineasParaGuardar(v.carro), cliente: v.cliente, txt_cliente: v.txtCliente, descuento: v.descuento, tipo_desc: v.tipoDesc,
				observacion: v.observacion, id_tdocumento: Number(v.documento) || 0, id_lprecio: v.lista || 0, total: sub - desc };
		}
		// Productos agregados a la mesa abierta que todavía no se enviaron
		function omitidosMesa() {
			if (!mesa) return null;
			var p = pendientesMesa();
			if (!p.length) return null;
			return { id_mesa: mesa.id_mesa, mesa: nombreMesa(mesa.id_mesa), lineas: lineasParaGuardar(p), monto: montoLineas(p) };
		}
		function hayVentaEnCurso() { return !!(resumen && (ventaDirectaActual() || omitidosMesa())); }
		function nombreEsperaSugerido(v) {
			var d = new Date(), hora = ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2);
			return (v && v.cliente && v.cliente.nombre_cliente ? v.cliente.nombre_cliente : 'Venta') + ' ' + hora;
		}
		function auditar(evento, datos) {
			if (!esperasDisponible || !datos) return;
			$.ajax({ url: base + 'auditar', type: 'POST', contentType: 'application/json', dataType: 'json', global: false,
				data: JSON.stringify($.extend({ evento: evento, uid: nuevoUid() }, datos)) });
		}
		function auditarOmitidos(origen) {
			var o = omitidosMesa();
			if (o) auditar('productos_omitidos', $.extend(o, { origen: origen }));
		}

		// ---- lista de ventas en espera ----
		function cargarEsperas(silencioso) {
			return $.ajax({ url: base + 'esperas', dataType: 'json', cache: false, global: false }).done(function (r) {
				esperasDisponible = !!r.disponible;
				esperas = r.esperas || [];
				pintarEsperas();
			}).fail(function (x) { if (!silencioso) error(x); });
		}
		function iniciarEsperas() {
			esperasIniciadas = true;
			var listo = function () { if (esperasDisponible) esperarCatalogo(reenviarSalidaPendiente); };
			cargarEsperas(true).done(function () {
				listo();
				// Recién actualizado el sistema, la base se pone al día en segundo plano al entrar: se vuelve a mirar
				if (!esperasDisponible) setTimeout(function () { cargarEsperas(true).done(listo); }, 8000);
			});
		}
		function esperarCatalogo(listo, intentos) {
			intentos = intentos || 0;
			if (catalogo) { listo(); return; }
			if (intentos < 100) setTimeout(function () { esperarCatalogo(listo, intentos + 1); }, 300);
		}
		function pintarEsperas() {
			var n = esperas.length;
			$('#opcionesEspera').prop('hidden', !esperasDisponible);
			$('#esperasBadge, #esperasBadgeSel').text(n).prop('hidden', !n);
			if ($('#modalEsperas').hasClass('in') && !$('#esperasVistaLista').prop('hidden')) renderEsperas();
		}
		function esperaPorId(id) { return $.grep(esperas, function (e) { return e.id === id; })[0]; }
		function ventaActualVacia() { return !mesa && !carro.length; }
		function productosEspera(e) {
			return $.map(e.productos || [], function (p) { return fmtCant(p.cantidad) + '× ' + p.nombre + (p.opciones ? ' (' + p.opciones + ')' : ''); }).join(' · ');
		}
		function renderEsperas() {
			var vacia = ventaActualVacia();
			$('#esperasAviso').prop('hidden', vacia || !esperas.length).find('span')
				.text(mesa ? 'Estás atendiendo una mesa: ciérrala para cargar una venta en espera.'
					: 'Para cargar una venta en espera, la venta actual debe estar vacía (cóbrala, déjala en espera o vacíala).');
			var $l = $('#esperasLista').empty();
			if (!esperas.length) {
				$l.html('<div class="esp-vacio"><i class="fa fa-clock-o"></i>No hay ventas en espera</div>');
				return;
			}
			$.each(esperas, function (i, e) {
				var hace = e.minutos < 1 ? 'recién' : (e.minutos < 60 ? 'hace ' + e.minutos + ' min'
					: 'hace ' + Math.floor(e.minutos / 60) + ' h' + (e.minutos % 60 ? ' ' + (e.minutos % 60) + ' min' : ''));
				$l.append('<div class="esp-item" data-id="' + e.id + '">'
					+ '<div class="esp-cabeza"><span class="esp-nombre">' + esc(e.nombre) + '</span>'
					+ (ORIGEN_ESPERA[e.origen] ? '<span class="esp-origen ' + esc(e.origen) + '">' + esc(ORIGEN_ESPERA[e.origen]) + '</span>' : '')
					+ (e.cliente ? '<span class="text-muted"><i class="fa fa-user"></i> ' + esc(e.cliente) + '</span>' : '') + '</div>'
					+ '<div class="esp-total">' + fmt(e.total) + '</div>'
					+ '<div class="esp-productos">' + esc(textoUnidades(e.unidades) + ': ' + productosEspera(e)) + '</div>'
					+ '<div class="esp-meta">' + esc(e.fecha + ' (' + hace + ') · ' + (e.usuario || 'Usuario ' + e.id_usuario) + ' · Caja N° ' + e.id_caja) + '</div>'
					+ '<div class="esp-acciones">'
					+ '<button type="button" class="btn btn-default btn-sm esp-anular"><i class="fa fa-ban"></i> Anular</button>'
					+ '<button type="button" class="btn btn-primary btn-sm esp-cargar"' + (vacia ? '' : ' disabled title="La venta actual debe estar vacía"') + '><i class="fa fa-download"></i> Cargar</button>'
					+ '</div></div>');
			});
		}
		function verListaEsperas() {
			esperaAnulando = null;
			$('#esperasVistaAnular').prop('hidden', true);
			$('#esperasVistaLista').prop('hidden', false);
			$('#esperasTitulo').html('<i class="fa fa-clock-o"></i> Ventas en espera');
		}
		function abrirEsperas() {
			verListaEsperas();
			$('#esperasAviso').prop('hidden', true);
			$('#esperasLista').html('<div class="esp-vacio">Cargando…</div>');
			$('#modalEsperas').modal('show');
			cargarEsperas().done(renderEsperas);
		}
		function verAnularEspera(id) {
			var e = esperaPorId(id);
			if (!e) return;
			esperaAnulando = e;
			$('#anularEsperaDetalle').html('<b>' + esc(e.nombre) + '</b> · ' + fmt(e.total) + '<br><small class="text-muted">'
				+ esc(textoUnidades(e.unidades) + ': ' + productosEspera(e)) + '</small><br><small class="text-muted">'
				+ esc(e.fecha + ' · ' + (e.usuario || '') + ' · Caja N° ' + e.id_caja) + '</small>');
			$('#anularEsperaMotivo').val('');
			$('#esperasVistaLista').prop('hidden', true);
			$('#esperasVistaAnular').prop('hidden', false);
			$('#esperasTitulo').html('<i class="fa fa-ban"></i> Anular venta en espera');
			setTimeout(function () { $('#anularEsperaMotivo').focus(); }, 50);
		}
		$('#esperasLista').on('click', '.esp-cargar', function () {
			cargarEspera(Number($(this).closest('.esp-item').data('id')), $(this));
		}).on('click', '.esp-anular', function () {
			verAnularEspera(Number($(this).closest('.esp-item').data('id')));
		});
		$('#btnAnularEsperaVolver').on('click', function () { verListaEsperas(); renderEsperas(); });
		$('#esperasVistaAnular').on('submit', function (ev) {
			ev.preventDefault();
			var e = esperaAnulando, motivo = $.trim($('#anularEsperaMotivo').val());
			if (!e) return;
			if (motivo.length < 5) {
				toastr.warning('Escribe la justificación de la anulación (al menos 5 caracteres)');
				$('#anularEsperaMotivo').focus();
				return;
			}
			var $b = $('#btnAnularEsperaConfirmar').prop('disabled', true);
			// Si Configuraciones pide clave de administrador para anular, se pide aquí también (misma ventana de autorización)
			enviarConAutorizacion(function (aut) {
				return postJson('anularEspera', { id: e.id, motivo: motivo, autorizacion: aut });
			}, function () {
				$b.prop('disabled', false);
				toastr.success('Venta en espera anulada: ' + e.nombre);
				verListaEsperas();
				cargarEsperas(true).always(renderEsperas);
			}, function () {
				$b.prop('disabled', false);
				cargarEsperas(true);
			});
		});
		$('#modalEsperas').on('hidden.bs.modal', function () { verListaEsperas(); $('#buscarProducto').focus(); });

		// ---- cargar una venta en espera ----
		function cargarEspera(id, $b) {
			if (!ventaActualVacia()) { toastr.warning('La venta actual debe estar vacía para cargar una venta en espera'); return; }
			if ($b) $b.prop('disabled', true);
			postJson('recuperarEspera', { id: id }).done(function (r) {
				$('#modalEsperas').modal('hide');
				aplicarVentaEspera(r.datos || {});
				// Si se cargó desde el Cierre (Hay ventas en espera > Ver ventas en espera), se vuelve a la venta para cobrarla
				if (!$('#tabVenta').hasClass('active')) $('#panelCaja a[href="#tabVenta"]').tab('show');
				toastr.success('Venta cargada: ' + r.nombre);
				cargarEsperas(true);
			}).fail(function (x) {
				error(x);
				if ($b) $b.prop('disabled', false);
				cargarEsperas(true);
			});
		}
		function aplicarVentaEspera(d) {
			carro = $.map(d.lineas || [], function (l) {
				return { id_producto: l.id_producto, nombre: l.nombre, precio: l.precio, cantidad: l.cantidad, agregados: (l.agregados || []).slice(), nota: l.nota || '',
					notas_agregados: l.notas_agregados && typeof l.notas_agregados === 'object' ? l.notas_agregados : {},
					menu: $.isArray(l.menu) ? l.menu : [], sushi: l.sushi && $.isArray(l.sushi.grupos) ? l.sushi : null };   // v1.24.0 / v1.25.0
			});
			cliente = d.cliente && d.cliente.id_cliente ? d.cliente : null;
			ponerTxtCliente(cliente ? (d.txt_cliente || cliente.nombre_cliente) : '');
			$('#txtDescuento').val(d.descuento || 0);
			tipoDesc = d.tipo_desc === 'porc' ? 'porc' : 'monto';
			$('[data-tipo-desc]').each(function () { $(this).toggleClass('active', $(this).data('tipo-desc') === tipoDesc); });
			$('#txtObservacion').val(d.observacion || '');
			var $doc = $('#cboDocumento option[value="' + Number(d.id_tdocumento || 0) + '"]');
			if ($doc.length && !$doc.prop('disabled')) $('#cboDocumento').val(d.id_tdocumento);
			if (!$('#tabVenta').hasClass('active')) $('#lnkVenta').tab('show');
			mostrarPanel('pago');
			// Precios vigentes de la lista actual (lo que ya no está en la carta se quita con aviso)
			var quitados = [];
			carro = $.grep(carro, function (l) {
				var p = producto(l.id_producto);
				if (!p) { quitados.push(l.nombre); return false; }
				l.precio = p.precio;
				l.agregados = $.grep(l.agregados, function (id) { return !!agregado(id); });
				return true;
			});
			if (quitados.length) toastr.warning('Ya no están a la venta en esta lista: ' + quitados.join(', '));
			renderCarro();
			// Si se dejó con otra lista de precios, se vuelve a ella (pide la clave si no es la pública, como siempre)
			var lista = Number(d.id_lprecio || 0);
			if (lista && lista !== listaActual && listaFija()) {
				toastr.info('Esta venta se dejó con otra lista de precios: se cobra con la lista de esta caja');
			} else if (lista && lista !== listaActual && $('#cboLista option[value="' + lista + '"]').length) $('#cboLista').val(lista).trigger('change');
		}

		// ---- poner en espera ----
		function abrirPonerEnEspera() {
			if (mesa) { toastr.info('Solo la venta directa se puede dejar en espera'); return; }
			if (!carro.length) { toastr.info('Agrega productos a la venta para dejarla en espera'); return; }
			var v = ventaDirectaActual();
			$('#esperaResumen').text(textoUnidades(contarUnidades(v.lineas)) + ' · ' + fmt(v.total) + (cliente ? ' · ' + cliente.nombre_cliente : ''));
			$('#esperaNombre').val(nombreEsperaSugerido(v));
			$('#modalEspera').modal('show');
		}
		$('#modalEspera').on('shown.bs.modal', function () { $('#esperaNombre').focus().select(); })
			.on('hidden.bs.modal', function () { $('#buscarProducto').focus(); });
		$('#frmEspera').on('submit', function (ev) {
			ev.preventDefault();
			var nombre = $.trim($('#esperaNombre').val());
			if (!nombre) { $('#esperaNombre').focus(); return; }
			var v = mesa ? null : ventaDirectaActual();
			if (!v) { $('#modalEspera').modal('hide'); return; }
			v.uid = nuevoUid();
			v.nombre = nombre;
			var $b = $('#btnGuardarEspera').prop('disabled', true);
			postJson('ponerEnEspera', v).done(function (r) {
				$('#modalEspera').modal('hide');
				limpiarVenta();
				mostrarPanel('pago');
				toastr.success('Venta en espera: ' + r.nombre);
				cargarEsperas(true);
			}).fail(error).always(function () { $b.prop('disabled', false); });
		});

		// ---- salir de la caja con una venta en curso ----
		function pedirSalida(destino) {
			var v = ventaDirectaActual(), o = omitidosMesa();
			destinoSalida = { destino: destino, venta: v, omitidos: o };
			$('#salidaDirecta').prop('hidden', !v);
			if (v) {
				$('#salidaDirectaResumen').text(textoUnidades(contarUnidades(v.lineas)) + ' · ' + fmt(v.total));
				$('#salidaNombre').val(nombreEsperaSugerido(v));
				$('#salidaNombreGrupo').prop('hidden', !esperasDisponible);
				$('#salidaDirectaTexto').text(esperasDisponible ? 'Al salir quedará en Opciones > Ventas en espera para recuperarla.'
					: 'No se puede dejar en espera porque falta actualizar la base de datos: si sales, esta venta se pierde.');
			}
			$('#salidaMesa').prop('hidden', !o);
			if (o) {
				$('#salidaMesaNombre').text(o.mesa);
				$('#salidaMesaResumen').text(textoUnidades(contarUnidades(o.lineas)) + ' · ' + fmt(o.monto));
			}
			$('#btnSalidaConfirmar').html(v && esperasDisponible ? '<i class="fa fa-pause"></i> Poner en espera y salir' : '<i class="fa fa-sign-out"></i> Salir de la caja');
			$('#modalSalida').modal('show');
		}
		$('#modalSalida').on('shown.bs.modal', function () {
			if (!$('#salidaDirecta').prop('hidden') && esperasDisponible) $('#salidaNombre').focus().select();
			else $('#btnSalidaQuedarme').focus();
		});
		$('#frmSalida').on('submit', function (ev) {
			ev.preventDefault();
			var s = destinoSalida;
			if (!s) return;
			var p = { origen: 'menu', empresa: EMPRESA, espera: null, omitidos: null };
			if (s.venta && esperasDisponible) {
				var nombre = $.trim($('#salidaNombre').val());
				if (!nombre) { $('#salidaNombre').focus(); return; }
				p.espera = $.extend({}, s.venta, { uid: nuevoUid(), nombre: nombre });
			}
			if (s.omitidos && esperasDisponible) p.omitidos = $.extend({}, s.omitidos, { uid: nuevoUid() });
			var seguir = function () {
				destinoSalida = null;
				// Lo pendiente ya quedó guardado: la pantalla queda vacía (así al cerrar la página no se manda de nuevo)
				ventaDirectaGuardada = null;
				limpiarVenta();
				if (typeof s.destino === 'function') {
					$('#modalSalida').one('hidden.bs.modal', s.destino).modal('hide');
					return;
				}
				saliendo = true;
				$('#modalSalida').modal('hide');
				location.href = s.destino;
			};
			if (!p.espera && !p.omitidos) { seguir(); return; }
			var $b = $('#btnSalidaConfirmar').prop('disabled', true);
			$.ajax({ url: base + 'registrarSalida', type: 'POST', contentType: 'application/json', data: JSON.stringify(p), dataType: 'json' })
				.done(function (r) {
					if (r && r.espera && r.espera.nombre) toastr.success('Venta en espera: ' + r.espera.nombre);
					seguir();
				}).fail(error).always(function () { $b.prop('disabled', false); });
		});
		$('#modalSalida').on('hidden.bs.modal', function () { if (!saliendo) destinoSalida = null; });
		// Enlaces que salen de la Caja (menú lateral, perfil, cerrar sesión): se revisan antes que el resto de los manejadores
		document.addEventListener('click', function (ev) {
			if (ev.defaultPrevented || ev.button !== 0 || ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.altKey) return;
			var a = ev.target && ev.target.closest ? ev.target.closest('a[href]') : null;
			if (!a || (a.target && a.target !== '_self') || a.hasAttribute('download')) return;
			var href = a.getAttribute('href') || '';
			if (!href || href.charAt(0) === '#' || /^(javascript|mailto|tel):/i.test(href)) return;
			var url;
			try { url = new URL(a.href, location.href); } catch (x) { return; }
			if (url.origin === location.origin && url.pathname === location.pathname && url.search === location.search) return;
			if (!hayVentaEnCurso()) return;
			ev.preventDefault();
			ev.stopPropagation();
			pedirSalida(a.href);
		}, true);
		// Cambiar de empresa (menú del usuario): primero se resuelve la venta en curso
		$(document).on('show.bs.modal', '#modalCambioClienteSucursal', function (ev) {
			if (!hayVentaEnCurso()) return;
			ev.preventDefault();
			pedirSalida(function () { $('#modalCambioClienteSucursal').modal('show'); });
		});

		// ---- recargar o cerrar la página ----
		function datosSalidaAutomatica() {
			if (!resumen || !esperasDisponible) return null;
			var v = ventaDirectaActual(), o = omitidosMesa();
			if (!v && !o) return null;
			return {
				origen: 'recarga', empresa: EMPRESA, usuario: USUARIO, t: Date.now(),
				espera: v ? $.extend(v, { uid: nuevoUid(), nombre: nombreEsperaSugerido(v) }) : null,
				omitidos: o ? $.extend(o, { uid: nuevoUid() }) : null
			};
		}
		function enviarAlCerrar(p) {
			var url = base + 'registrarSalida', cuerpo = JSON.stringify(p);
			try {
				if (window.fetch && window.Request && 'keepalive' in Request.prototype && cuerpo.length < 60000) {
					fetch(url, { method: 'POST', keepalive: true, credentials: 'same-origin', headers: { 'Content-Type': 'application/json' }, body: cuerpo });
					return;
				}
			} catch (x) {}
			try {
				if (navigator.sendBeacon) {
					var f = new FormData();
					f.append('csrf_web', $('meta[name="csrf-web"]').attr('content') || '');
					f.append('datos', cuerpo);
					navigator.sendBeacon(url, f);
				}
			} catch (x) {}
		}
		function alSalirDeLaPagina() {
			if (saliendo || salidaEnviada) return;
			// 19-11-2026: cobrando en la máquina, la venta ya está guardada en el cobro (no se deja también en espera)
			if (pagoPos.cobrando) return;
			var p = datosSalidaAutomatica();
			if (!p) return;
			salidaEnviada = true;
			try { sessionStorage.setItem(CLAVE_SALIDA, JSON.stringify(p)); } catch (x) {}
			enviarAlCerrar(p);
			// Si al final la página no se cierra (por ejemplo, una descarga), la venta ya quedó en espera: se vacía aquí
			setTimeout(function () {
				if (!salidaEnviada) return;
				salidaEnviada = false;
				ventaDirectaGuardada = null;
				if (mesa || carro.length) limpiarVenta();
				reenviarSalidaPendiente();
			}, 4000);
		}
		window.addEventListener('beforeunload', alSalirDeLaPagina);
		window.addEventListener('pagehide', alSalirDeLaPagina);
		// Al volver a la Caja: se confirma lo que se mandó al salir (si ya había llegado, el servidor no lo duplica)
		function reenviarSalidaPendiente() {
			var p = null;
			try { p = JSON.parse(sessionStorage.getItem(CLAVE_SALIDA) || 'null'); } catch (x) {}
			if (!p) return;
			var borrar = function () { try { sessionStorage.removeItem(CLAVE_SALIDA); } catch (x) {} };
			if (p.usuario !== USUARIO || !p.t || Date.now() - p.t > 12 * 3600000) { borrar(); return; }
			if (p.empresa !== EMPRESA) return;   // se cambió de empresa: queda para cuando vuelva a la de esa venta
			$.ajax({ url: base + 'registrarSalida', type: 'POST', contentType: 'application/json', data: JSON.stringify(p), dataType: 'json', global: false })
				.done(function (r) { borrar(); avisarSalidaAutomatica(p, r || {}); })
				.fail(function (x) { if (x.status === 400) borrar(); });
		}
		function avisarSalidaAutomatica(p, r) {
			if (r.omitidos && p.omitidos) {
				toastr.warning((p.omitidos.mesa ? p.omitidos.mesa + ': ' : '') + textoUnidades(contarUnidades(p.omitidos.lineas || []))
					+ ' sin enviar quedaron registrados como omitidos en la auditoría de caja.', '', { timeOut: 10000 });
			}
			var e = r.espera;
			if (!e || !e.id) return;
			cargarEsperas(true);
			swal({
				title: 'La venta quedó en espera',
				text: 'Al recargar o salir de la página, la venta directa en curso se guardó como «' + e.nombre + '» ('
					+ fmt(p.espera ? p.espera.total : 0) + '). ¿Quieres cargarla de nuevo?',
				type: 'info', showCancelButton: true, confirmButtonText: 'Cargarla', cancelButtonText: 'Después'
			}, function (ok) { if (ok) cargarEspera(e.id); });
		}
		// Al cerrar la caja se recuerdan las ventas en espera de esta caja que siguen sin resolver
		function avisarEsperasCierre() {
			if (!esperasDisponible) return;
			cargarEsperas(true).done(function () {
				var n = $.grep(esperas, function (e) { return e.bloquea_cierre; }).length;
				if (n) toastr.warning('Hay ' + n + ' venta(s) en espera pendientes: para cerrar la caja primero cárgalas y cóbralas, o anúlalas (Opciones > Ventas en espera).', '', { timeOut: 10000 });
			});
		}

		// La sesión no vence mientras la Caja está abierta (ventas, mesas y pedidos): ver restoSesion en menuTop
		if (window.restoSesion) restoSesion.mantener(base + 'mantenerSesion');
		cargarEstado();
