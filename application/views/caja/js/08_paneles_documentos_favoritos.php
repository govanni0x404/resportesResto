		// ---------------- paneles laterales de la venta (Pagar / Opciones) ----------------
		// Los botones de arriba muestran un panel y ocultan el otro. Pagar es el cobro de siempre; Opciones abre
		// Ingresos / Retiros, Créditos y Cierre (esas pestañas ya no se muestran arriba).
		function mostrarPanel(nombre) {
			// .caja-venta puede estar en la pestaña Venta o en el drawer de mesa: se busca por la venta, no por la pestaña
			if ($('.caja-venta .caja-panel.activo').data('panel') === nombre) return;
			// La ★ del selector tiene su propio botón (abre y cierra): mientras se ve Favoritos, Pagar y Opciones quedan sin marcar y se marca la ★
			$('.caja-panel-selector [data-panel]').each(function () {
				var on = $(this).data('panel') === nombre;
				$(this).toggleClass('active', on).attr('aria-selected', on ? 'true' : 'false');
			});
			$('#btnFavoritos').toggleClass('is-activo', nombre === 'favoritos').attr('aria-pressed', nombre === 'favoritos' ? 'true' : 'false');
			$('.caja-venta .caja-panel').removeClass('activo desde-izq')
				.filter('[data-panel="' + nombre + '"]').addClass('activo').toggleClass('desde-izq', nombre === 'pago');
			if (nombre === 'favoritos') renderFavoritos();
			if (nombre === 'opciones' && resumen) cargarEsperas(true);   // cantidad de ventas en espera al día
			aplicarFavMesa();
		}
		// Mesa sin pedido: el panel lateral se ve solo mientras Favoritos está abierto (★ de la barra de búsqueda). Al agregar un
		// producto sin «Fijar», o al volver a Pagar, se cierra solo. Mientras se ve, el panel de la mesa toma el ancho con pedido.
		function aplicarFavMesa() {
			var abierto = $('.caja-venta').hasClass('mesa-sin-pedido') && favoritosVisible();
			var cambio = $('.caja-venta').hasClass('mesa-fav-abierto') !== abierto;
			$('.caja-venta').toggleClass('mesa-fav-abierto', abierto);
			$('#btnFavMesa').toggleClass('is-activo', abierto).attr('aria-pressed', abierto ? 'true' : 'false');
			if (mesa) {
				var sinPedido = !mesa.id_cotizacion && !abierto;
				if ($('#mesaDrawer').hasClass('sin-pedido') !== sinPedido) { $('#mesaDrawer').toggleClass('sin-pedido', sinPedido); aplicarAnchoMesa(); }
			}
			if (cambio) ajustarAltoVenta();
		}
		$('.caja-panel-selector').on('click', '[data-panel]', function () { mostrarPanel($(this).data('panel')); });
		$('#panelOpciones').on('click', '[data-abrir]', function () {
			var destino = $(this).data('abrir');
			// Documentos se abre en una ventana encima: el panel Opciones queda como estaba
			if (destino === 'documentos') { abrirDocumentos(); return; }
			// Ventas en espera: ventanas encima, el panel Opciones queda como estaba
			if (destino === 'espera') { abrirPonerEnEspera(); return; }
			if (destino === 'esperas') { abrirEsperas(); return; }
			if (destino === 'ingreso' || destino === 'retiro') {
				// Deja elegido el tipo de movimiento y el cursor en el monto
				var tipo = destino === 'ingreso' ? 'ingreso' : 'egreso';
				$('input[name=tipoMov]').each(function () {
					this.checked = this.value === tipo;
					$(this).closest('label').toggleClass('active', this.checked);
				});
				$('#lnkMovimientos').one('shown.bs.tab', function () { $('#montoMov').focus(); }).tab('show');
			} else if (destino === 'creditos') {
				$('#lnkCreditos').tab('show');
			} else if (destino === 'cierre') {
				$('#lnkCierre').tab('show');
			} else if (destino === 'ventas') {
				$('#lnkVentas').tab('show');   // la pestaña sigue igual (anular, devolver, reenviar al SII); arriba no se muestra
			}
			// Al volver a la venta queda a la vista el panel de cobro
			mostrarPanel('pago');
		});

		// ---------------- documentos emitidos (Opciones > Documentos) ----------------
		// Documentos tributarios (tdocumento.cod_sii > 10) de un día, de todas las cajas: verlos (el mismo ticket que se
		// imprime, o el PDF) y reimprimirlos con imprimirTicket, igual que al terminar una venta. Solo lectura.
		var docs = [], docActual = 0;
		function hoyIso() {
			var d = new Date();
			return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2);
		}
		function docPorId(id) { return $.grep(docs, function (d) { return d.id_venta === id; })[0]; }
		function abrirDocumentos() {
			if (!$('#docFecha').val()) $('#docFecha').val(hoyIso());
			$('#docBuscar').val('');
			verDocumento(0);
			$('#modalDocumentos').modal('show');
			cargarDocumentos();
		}
		function cargarDocumentos() {
			$('#tablaDocumentos tbody').html('<tr><td colspan="6" class="text-center text-muted">Cargando…</td></tr>');
			$('#docResumen').text('');
			get('documentosEmitidos', { fecha: $('#docFecha').val() }).done(function (r) {
				docs = r.documentos || [];
				if (r.fecha) $('#docFecha').val(r.fecha);
				if (docActual && !docPorId(docActual)) verDocumento(0);
				renderDocumentos();
			}).fail(function () {
				$('#tablaDocumentos tbody').html('<tr><td colspan="6" class="text-center text-danger">No se pudieron cargar los documentos</td></tr>');
			});
		}
		function renderDocumentos() {
			var texto = $.trim($('#docBuscar').val()).toLowerCase();
			var lista = $.grep(docs, function (d) {
				return !texto || String(d.folio).indexOf(texto) >= 0 || String(d.nombre_cliente || '').toLowerCase().indexOf(texto) >= 0
					|| String(d.nombre_tdocumento || '').toLowerCase().indexOf(texto) >= 0;
			});
			$('#docResumen').text(lista.length + (lista.length === 1 ? ' documento' : ' documentos') + (texto ? ' de ' + docs.length : ''));
			var $t = $('#tablaDocumentos tbody').empty();
			if (!lista.length) {
				$t.append('<tr><td colspan="6" class="text-center text-muted">' + (docs.length ? 'Ningún documento coincide con la búsqueda' : 'No hay boletas ni facturas emitidas ese día') + '</td></tr>');
				return;
			}
			$.each(lista, function (i, d) {
				$t.append('<tr class="doc-fila' + (d.id_venta === docActual ? ' activo' : '') + (d.anulada ? ' anulada' : '') + '" data-id="' + d.id_venta + '">'
					+ '<td>' + esc(d.hora) + '</td>'
					+ '<td>' + esc(d.nombre_tdocumento) + (d.anulada ? ' <span class="label label-danger">Anulada</span>' : '')
					+ (d.pendiente_sii ? ' <span class="label label-warning">Pendiente SII</span>' : '') + '</td>'
					+ '<td>' + esc(d.folio) + '</td><td>' + esc(d.nombre_cliente) + '</td>'
					+ '<td class="caja-num">' + fmt(d.total) + '</td>'
					+ '<td class="text-center" style="white-space:nowrap">'
					+ '<button type="button" class="btn btn-xs btn-default doc-ver" title="Ver"><i class="fa fa-eye"></i></button> '
					+ '<button type="button" class="btn btn-xs btn-default doc-imprimir" title="Reimprimir"' + (d.anulada ? ' disabled' : '') + '><i class="fa fa-print"></i></button>'
					+ '</td></tr>');
			});
		}
		function verDocumento(id) {
			docActual = id;
			$('#tablaDocumentos tbody tr.doc-fila').each(function () { $(this).toggleClass('activo', Number($(this).data('id')) === id); });
			$('#docVistaVacia').toggle(!id);
			$('#docFrame').toggle(!!id).attr('src', id ? base + 'ticket/' + id : 'about:blank');
			$('#docVistaAcciones').toggle(!!id);
			if (!id) return;
			var d = docPorId(id);
			$('#docPdf').attr('href', base + 'pdf/' + id);
			$('#docReimprimir').prop('disabled', !!(d && d.anulada));   // una anulada se puede ver, no reimprimir
		}
		function reimprimirDocumento(id, $b) {
			var d = docPorId(id);
			if (!id || (d && d.anulada)) return;
			$b.prop('disabled', true);
			var listo = bloquearImpresion('Imprimiendo documento…');
			imprimirTicket(id, function () { $b.prop('disabled', false); listo(); });
		}
		$('#docFecha').on('change', cargarDocumentos);
		$('#docBuscar').on('input', renderDocumentos);
		$('#tablaDocumentos').on('click', 'tr.doc-fila', function (e) {
			var id = Number($(this).data('id'));
			if ($(e.target).closest('.doc-imprimir').length) { reimprimirDocumento(id, $(e.target).closest('.doc-imprimir')); return; }
			verDocumento(id);
		});
		$('#docReimprimir').on('click', function () { reimprimirDocumento(docActual, $(this)); });
		$('#modalDocumentos').on('hidden.bs.modal', function () { verDocumento(0); });

		// ---------------- panel Favoritos ----------------
		// Productos con «Favorito En Caja» en la Carta (catalogo.productos[].favorito), con el precio de la lista elegida.
		// Un clic agrega igual que el buscador (elegir). «Fijar»: se queda en Favoritos; si no, vuelve a Pagar.
		// Filtro por familia y vista íconos / lista; Fijar y la vista se recuerdan en este computador.
		var CLAVE_FAV_FIJAR = 'cajaWeb.favoritosFijar', CLAVE_FAV_VISTA = 'cajaWeb.favoritosVista';
		var favVista = 'iconos';
		var escAttr = window.escHtml || esc;   // escHtml (menuFooter) también escapa comillas: va en atributos
		try {
			favVista = localStorage.getItem(CLAVE_FAV_VISTA) === 'lista' ? 'lista' : 'iconos';
			$('#favFijar').prop('checked', localStorage.getItem(CLAVE_FAV_FIJAR) === '1');
		} catch (e) {}
		function favoritosVisible() { return $('#panelFavoritos').hasClass('activo'); }
		function favEstado(icono, titulo, texto) {
			return '<div class="fav-estado"><i class="fa ' + icono + ' fav-estado-icono" aria-hidden="true"></i>'
				+ '<div class="fav-estado-titulo">' + titulo + '</div>' + (texto ? '<div class="fav-estado-texto">' + texto + '</div>' : '') + '</div>';
		}
		function renderFavoritos() {
			var $l = $('#favLista');
			if (!catalogo) {
				$('#favResumen').text('');
				$('#favToolbar').prop('hidden', true);
				$l.html(favEstado('fa-refresh', 'Cargando productos…', ''));
				return;
			}
			var favs = $.grep(catalogo.productos, function (p) { return p.favorito; });
			var lista = $.trim($('#cboLista option:selected').text());
			$('#favResumen').text(favs.length + (favs.length === 1 ? ' producto' : ' productos') + (lista ? ' · Lista ' + lista : ''));
			$('#favToolbar').prop('hidden', !favs.length);
			if (!favs.length) {
				$l.html(favEstado('fa-star-o', 'Aún no hay productos favoritos',
					'Marca la opción Favorito En Caja (y Venta a público) en la ficha del producto para verlo aquí.'));
				return;
			}
			// Familias que tienen favoritos; se mantiene la elegida si sigue teniendo
			var $f = $('#favFamilia'), elegida = Number($f.val()) || 0, usadas = {};
			$.each(favs, function (i, p) { usadas[p.id_familia] = true; });
			var opciones = '<option value="0">Todas las familias</option>';
			$.each(catalogo.familias, function (i, f) {
				var id = Number(f.id_familia);
				if (usadas[id]) opciones += '<option value="' + id + '">' + esc(f.nombre_familia) + '</option>';
			});
			$f.html(opciones).val(String(usadas[elegida] ? elegida : 0));
			elegida = Number($f.val()) || 0;
			// v1.30.3 (pedido del usuario): subfamilia, con las que tienen favoritos en la familia elegida
			var $sf = $('#favSubfamilia'), subElegida = Number($sf.val()) || 0;
			var subs = subfamiliasCon($.grep(favs, function (p) { return !elegida || p.id_familia === elegida; }));
			var opSub = '<option value="0">Todas las subfamilias</option>';
			$.each(subs, function (i, s) {
				var fam = !elegida ? nombreFamilia(s.id_familia) : '';
				opSub += '<option value="' + s.id_subfamilia + '">' + esc(s.nombre + (fam ? ' (' + fam + ')' : '')) + '</option>';
			});
			$sf.html(opSub).val(String($.grep(subs, function (s) { return s.id_subfamilia === subElegida; }).length ? subElegida : 0)).prop('hidden', !subs.length);
			subElegida = Number($sf.val()) || 0;
			$('.fav-vista-btn').each(function () {
				var on = $(this).data('fav-vista') === favVista;
				$(this).toggleClass('is-activo', on).attr('aria-pressed', on ? 'true' : 'false');
			});
			var enVenta = {};
			$.each(carro, function (i, l) { enVenta[l.id_producto] = (enVenta[l.id_producto] || 0) + l.cantidad; });
			var html = '';
			$.each(favs, function (i, p) {
				if (elegida && p.id_familia !== elegida) return;
				if (subElegida && p.id_subfamilia !== subElegida) return;   // v1.30.3
				var n = enVenta[p.id_producto] || 0;
				var img = p.imagen
					? '<img src="' + escAttr(p.imagen) + '" alt="" loading="lazy">'
					: '<span class="fav-img-ph">' + esc((p.nombre || '?').charAt(0)) + '</span>';
				html += '<button type="button" class="fav-item' + (n ? ' en-venta' : '') + '" data-id="' + p.id_producto + '" title="' + escAttr(p.nombre) + '">'
					+ (n ? '<span class="fav-cant">' + (esFraccion(p.id_producto) ? fmtCant(n) : n) + '</span>' : '')
					+ '<span class="fav-img">' + img + '</span>'
					+ '<span class="fav-nombre">' + esc(p.nombre) + '</span>'
					+ '<span class="fav-pie"><span class="fav-precio">' + fmt(p.precio) + '</span>'
					+ (tieneOpciones(p.id_producto) ? '<span class="fav-opciones">+ opciones</span>' : '') + '</span>'
					+ '<span class="fav-ok" aria-hidden="true"><i class="fa fa-check"></i></span></button>';
			});
			$l.html('<div class="fav-grid' + (favVista === 'lista' ? ' fav-grid--lista' : '') + '">'
				+ (html || favEstado('fa-filter', subElegida ? 'Sin favoritos en esta subfamilia' : 'Sin favoritos en esta familia', '')) + '</div>');
		}
		$('#favLista').on('click', '.fav-item', function () {
			var id = Number($(this).data('id'));
			if (tieneOpciones(id)) { elegir(id); return; }   // abre sus opciones; al confirmar se agrega
			elegir(id);   // agregar() -> renderCarro() vuelve a dibujar el panel con la cantidad nueva
			if (!$('#favFijar').prop('checked')) { mostrarPanel('pago'); return; }
			var $item = $('#favLista .fav-item[data-id="' + id + '"]').removeClass('is-agregado');
			if ($item.length) { $item[0].offsetWidth; $item.addClass('is-agregado'); }   // reinicia la animación
		});
		$('#favFijar').on('change', function () {
			try { localStorage.setItem(CLAVE_FAV_FIJAR, this.checked ? '1' : '0'); } catch (e) {}
		});
		$('#favFamilia, #favSubfamilia').on('change', renderFavoritos);
		$('.fav-vista-btn').on('click', function () {
			favVista = $(this).data('fav-vista') === 'lista' ? 'lista' : 'iconos';
			try { localStorage.setItem(CLAVE_FAV_VISTA, favVista); } catch (e) {}
			renderFavoritos();
		});
		// Actualizar: vuelve a pedir el catálogo de la lista actual (favoritos marcados en la Carta y precios)
		$('#btnFavRecargar').on('click', function () {
			var $b = $(this).prop('disabled', true).addClass('is-cargando');
			cargarCatalogo(listaActual || 0).always(function () { $b.prop('disabled', false).removeClass('is-cargando'); });
		});
		$('#barraMovil').on('click', function () {
			$('html, body').animate({ scrollTop: $('.caja-carro').offset().top - 10 }, 250);
		});
		$('[data-tipo-desc]').on('click', function () {
			tipoDesc = $(this).data('tipo-desc');
			$('[data-tipo-desc]').removeClass('active');
			$(this).addClass('active');
			renderCarro();
		});

		function limpiarVenta() {
			var eraMesa = !!mesa;
			// 19-11-2026 (auditoría): venta nueva = token de cobro nuevo (antes quedaba el de un cobro que sí se registró pero
			// cuya respuesta no llegó, y todas las ventas siguientes daban "Este cobro ya se registró" hasta recargar)
			tokenCobro = null;
			carro = [];
			mesa = null;
			cobroParcial = null;
			dcCobro = null;
			actualizarModoMesa();
			cliente = null;
			datosFactura = null;
			ponerTxtCliente('');
			$('#txtDescuento').val(0);
			$('#txtObservacion').val('');
			$('#txtRetira').val('');
			$('#buscarProducto').val('');
			cerrarResultados();
			renderCarro();
			if (eraMesa) {
				// La mesa se trabajaba en su drawer: se cierra y vuelve la venta directa que había quedado guardada
				cerrarDrawerMesa();
				if (restaurarVentaDirecta()) return;
			}
			// La lista de una mesa no pasa a la venta directa: se vuelve a la pública
			var publica = catalogo && catalogo.config ? Number(catalogo.config.id_lprecio_publico) : 0;
			if (eraMesa && publica && listaActual && listaActual !== publica) cargarCatalogo(publica);
			$('#buscarProducto').focus();
		}

		$('#btnLimpiar').on('click', function () {
			if (mesa) { $('#btnSalirMesa').click(); return; }
			// 18-11-2026: cobrando un delivery guardado no se vacía (es lo pedido): se vuelve a la lista
			if (modoDelivery && modoDelivery.id) { $('#btnSalirDelivery').click(); return; }
			if (!carro.length) return;
			swal({ title: '¿Vaciar la venta?', type: 'warning', showCancelButton: true, confirmButtonText: 'Sí, vaciar', cancelButtonText: 'No' },
				function (ok) {
					if (!ok) return;
					// Auditoría de caja: venta directa vaciada con sus productos
					auditar('venta_vaciada', { lineas: lineasParaGuardar(carro), monto: totales().total, cliente: cliente ? cliente.nombre_cliente : '' });
					limpiarVenta();
				});
		});

