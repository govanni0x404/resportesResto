		// ---------------- delivery (18-11-2026) ----------------
		// Pestaña aparte de venta directa y mesas (Configuraciones > Delivery), pedido del usuario. "Nuevo delivery" usa la
		// pantalla de la venta directa con los datos de entrega. En cada pedido se elige:
		// - Cobrar ahora: es una venta directa (vender con venta.delivery): sale la boleta y la comanda.
		// - Cobrar al entregar: se guarda (deliveryGuardar) y sale la comanda; después "Cobrar" en la lista lo carga en la
		//   venta (sin poder cambiar lo pedido) y se cobra con venta.id_delivery (no vuelve a salir la comanda).
		// El envío es una línea más de la boleta (Configuraciones > Delivery). La lista muestra cada pedido con WhatsApp,
		// En camino (con repartidor), Entregado (solo cobrado), Cobrar y Anular (solo sin cobrar).
		var modoDelivery = null;   // null | { nuevo: true } | { id, envio, nombre, id_cliente } (cobrando uno guardado)
		var deliveryPedidos = [], deliveryFiltro = 'abiertos', deliveryCargando = false, guardandoDelivery = false;
		var DEL_ESTADO = { preparando: 'Preparando', en_camino: 'En camino', entregado: 'Entregado', anulado: 'Anulado' };
		var DEL_TEL = /^\+?[0-9][0-9 ()-]{5,}$/;   // igual que Mcaja::datosEntrega
		function deliveryHabilitado() { return !!(catalogo && catalogo.delivery); }
		function envioDelivery() {
			if (!modoDelivery || !deliveryHabilitado()) return 0;
			if (modoDelivery.id) return Number(modoDelivery.envio) || 0;
			var e = catalogo.delivery.envio;
			return e ? Number(e.monto) || 0 : 0;
		}
		function cobrarAlEntregar() { return !!(modoDelivery && modoDelivery.nuevo && $('#delCobroEntregar').prop('checked')); }
		function opcionesSelect($s, lista, vacio) {
			var v = $s.val();
			$s.empty().append($('<option>').val(0).text(vacio));
			$.each(lista || [], function (i, x) { $s.append($('<option>').val(x.id).text(x.nombre)); });
			$s.val(v || 0);
			if (!$s.val()) $s.val(0);
		}
		var deliveryListo = false;
		function prepararDelivery() {
			var on = deliveryHabilitado();
			$('#navDelivery').toggle(on);
			if (!on) {
				if (modoDelivery) salirDelivery();
				if ($('#tabDelivery').hasClass('active')) $('#lnkVenta').tab('show');
				return;
			}
			opcionesSelect($('#delSector'), catalogo.delivery.sectores, '— Sin sector —');
			opcionesSelect($('#delRepartidor'), catalogo.delivery.repartidores, '— Sin asignar —');
			if (!deliveryListo) { deliveryListo = true; cargarDeliverys(); }   // el número de pendientes en la pestaña
		}
		function limpiarCamposDelivery() {
			$('#delNombre, #delTelefono, #delDireccion, #delReferencia').val('');
			$('#delSector, #delRepartidor').val(0);
			$('#delCobroAhora').prop('checked', true);
		}
		function pintarModoDelivery() {
			var on = !!modoDelivery;
			$('#bannerDelivery').toggle(on);
			$('#tarjetaDelivery').toggle(on);
			$('#tarjetaDelivery .del-datos').toggle(!!(modoDelivery && modoDelivery.nuevo));
			$('.caja-venta').toggleClass('delivery-bloqueado', !!(modoDelivery && modoDelivery.id));
			if (on) $('#bannerDeliveryTexto').text(modoDelivery.id ? 'Cobrando delivery N° ' + modoDelivery.id + ' · ' + modoDelivery.nombre : 'Nuevo delivery');
			var ayuda = '';
			if (modoDelivery && modoDelivery.id) ayuda = 'Se cobra lo que se pidió al tomar el delivery: los productos no se cambian.';
			else if (modoDelivery && !catalogo.delivery.envio) ayuda = 'Sin costo de envío (se configura en Configuraciones > Delivery).';
			$('#delAyuda').text(ayuda);
			aplicarModoCaja();
			renderCarro();
		}
		function pintarEnvioDelivery() {
			var e = envioDelivery();
			$('#filaEnvio').toggle(!!modoDelivery && e > 0 && carro.length > 0);
			$('#lblEnvio').text(fmt(e));
			$('#txtCobrar').text(cobrarAlEntregar() ? 'Guardar delivery' : 'Cobrar');
		}
		$('input[name="delCobro"]').on('change', pintarEnvioDelivery);
		function salirDelivery() {
			var cobrando = modoDelivery && modoDelivery.id;
			modoDelivery = null;
			limpiarVenta();
			limpiarCamposDelivery();
			pintarModoDelivery();
			// Cobrando uno guardado se pudo cambiar a su lista de precios: la venta directa vuelve a la pública
			var publica = catalogo && catalogo.config ? Number(catalogo.config.id_lprecio_publico) : 0;
			if (cobrando && publica && listaActual && listaActual !== publica) cargarCatalogo(publica);
		}
		function puedeEmpezarDelivery() {
			if (!deliveryHabilitado()) return false;
			if (mesa) { toastr.warning('Cierra la mesa antes de trabajar un delivery'); return false; }
			if (carro.length && !modoDelivery) {
				toastr.warning('Hay una venta directa a medias: cóbrala, ponla en espera o vacíala antes de trabajar un delivery');
				$('#lnkVenta').tab('show');
				return false;
			}
			return true;
		}
		$('#btnNuevoDelivery').on('click', function () {
			if (!puedeEmpezarDelivery()) return;
			// Ya se estaba tomando uno: se vuelve a él (no se borra)
			if (modoDelivery && modoDelivery.nuevo) { $('#lnkVenta').tab('show'); mostrarPanel('pago'); return; }
			if (modoDelivery && modoDelivery.id) salirDelivery();
			modoDelivery = { nuevo: true };
			limpiarVenta();
			limpiarCamposDelivery();
			pintarModoDelivery();
			$('#lnkVenta').tab('show');
			mostrarPanel('pago');
			setTimeout(function () { $('#delNombre').focus(); }, 250);
		});
		$('#btnSalirDelivery').on('click', function () {
			var volver = function () { salirDelivery(); $('#lnkDelivery').tab('show'); cargarDeliverys(); };
			if (!(modoDelivery && modoDelivery.nuevo && carro.length)) { volver(); return; }
			swal({ title: '¿Descartar este delivery?', text: 'Lo agregado no se guarda.', type: 'warning', showCancelButton: true,
				confirmButtonText: 'Sí, descartar', cancelButtonText: 'No' }, function (ok) { if (ok) volver(); });
		});
		function entregaPantalla() {
			return {
				nombre: $.trim($('#delNombre').val()), telefono: $.trim($('#delTelefono').val()), direccion: $.trim($('#delDireccion').val()),
				referencia: $.trim($('#delReferencia').val()), id_sector: Number($('#delSector').val()) || 0,
				id_repartidor: Number($('#delRepartidor').val()) || 0, observacion: $.trim($('#txtObservacion').val())
			};
		}
		function validarEntrega() {
			var e = entregaPantalla(), campo = null, msg = '';
			if (!e.nombre) { campo = '#delNombre'; msg = 'Escribe el nombre de quien recibe el delivery'; }
			else if (!DEL_TEL.test(e.telefono)) { campo = '#delTelefono'; msg = 'Escribe el teléfono del delivery (solo números)'; }
			else if (!e.direccion) { campo = '#delDireccion'; msg = 'Escribe la dirección del delivery'; }
			if (!campo) return true;
			toastr.warning(msg, 'Faltan datos del delivery');
			mostrarPanel('pago');
			setTimeout(function () { $(campo).focus(); }, 150);
			return false;
		}
		function guardarDeliveryPendiente() {
			if (guardandoDelivery || !carro.length) return;
			// 18-11-2026: el descuento se guarda con el pedido (mismas reglas que al cobrar; la autorización se pide ahora)
			if (!validarCobro()) return;
			guardandoDelivery = true;
			$('#btnCobrar').prop('disabled', true);
			var pedido = {
				delivery: entregaPantalla(), id_lprecio: Number($('#cboLista').val()), id_cliente: cliente ? cliente.id_cliente : 0,
				descuento: totales().descuento,
				detalle: $.map(carro, function (l) { return { id_producto: l.id_producto, cantidad: l.cantidad, agregados: l.agregados, extras: l.extras || [], nota: l.nota, notas_agregados: l.notas_agregados || {}, menu: l.menu || [], sushi: l.sushi || null }; })
			};
			var terminar = function () { guardandoDelivery = false; renderCarro(); };
			enviarConAutorizacion(function (aut) {
				pedido.autorizacion = aut;
				return postJson('deliveryGuardar', pedido);
			}, function (r) {
				terminar();
				toastr.success('Se cobra al entregar · ' + fmt(r.total), 'Delivery N° ' + r.id_delivery + ' guardado', { timeOut: 6000 });
				if (imprimeComanda()) imprimirComandaBloqueando(r.comanda_id, 'Comanda');
				salirDelivery();
				$('#lnkDelivery').tab('show');
				cargarDeliverys();
			}, terminar);
		}
		// "Cobrar" en la lista: se carga lo guardado en la venta (sin poder cambiarlo) y se cobra como venta directa
		function cobrarDelivery(id) {
			if (!puedeEmpezarDelivery()) return;
			if (modoDelivery && modoDelivery.nuevo && carro.length) {
				toastr.warning('Primero guarda, cobra o descarta el delivery nuevo que estás tomando');
				$('#lnkVenta').tab('show');
				return;
			}
			get('deliveryCobrar', { id_delivery: id }).done(function (d) {
				var seguir = function () {
					var lineas = [], faltan = [];
					$.each(d.detalle || [], function (i, it) {
						var p = producto(Number(it.id_producto));
						if (!p) { faltan.push(it.id_producto); return; }
						lineas.push({ id_producto: Number(it.id_producto), nombre: p.nombre, precio: p.precio, cantidad: Number(it.cantidad),
							agregados: $.grep((it.agregados || []).map(Number), function (a) { return !!agregado(a); }), nota: it.nota || '',
							extras: $.grep((it.extras || []).map(Number), function (a) { return !!agregado(a); }),   // v1.35.0
							notas_agregados: it.notas_agregados && typeof it.notas_agregados === 'object' ? it.notas_agregados : {} });
					});
					if (faltan.length || !lineas.length) {
						toastr.error('Algún producto de este delivery ya no está a la venta en su lista de precios', 'No se puede cobrar');
						return;
					}
					if (modoDelivery) salirDelivery();
					modoDelivery = { id: d.id_delivery, envio: d.costo_envio, nombre: d.nombre, id_cliente: d.id_cliente };
					limpiarVenta();
					carro = lineas;
					$('#txtObservacion').val(d.observacion || '');
					// 18-11-2026: el cliente y el descuento puestos al tomar el pedido (antes había que ponerlos de nuevo)
					if (d.cliente && Number(d.cliente.id_cliente)) {
						cliente = $.extend({}, d.cliente, { id_cliente: Number(d.cliente.id_cliente), id_comuna: Number(d.cliente.id_comuna) || 0 });
						ponerTxtCliente(cliente.nombre_cliente);
					}
					$('#txtDescuento').val(Number(d.descuento) || 0);
					tipoDesc = 'monto';
					$('[data-tipo-desc]').each(function () { $(this).toggleClass('active', $(this).data('tipo-desc') === tipoDesc); });
					pintarModoDelivery();
					$('#lnkVenta').tab('show');
					mostrarPanel('pago');
				};
				if (d.id_lprecio && d.id_lprecio !== listaActual) cargarCatalogo(d.id_lprecio).done(seguir);
				else seguir();
			});
		}

		// Lista de pedidos
		function cargarDeliverys() {
			if (!deliveryHabilitado() || deliveryCargando) return;
			deliveryCargando = true;
			$.ajax({ url: base + 'deliverys', data: { todos: deliveryFiltro === 'hoy' ? 1 : 0 }, dataType: 'json', cache: false, global: false })
				.done(function (r) { deliveryPedidos = r.pedidos || []; pintarDeliverys(); })
				.always(function () { deliveryCargando = false; });
		}
		function delPorId(id) { return $.grep(deliveryPedidos, function (d) { return d.id_delivery === id; })[0]; }
		function pintarDeliverys() {
			var $l = $('#listaDelivery').empty();
			var abiertos = $.grep(deliveryPedidos, function (d) { return d.estado !== 'entregado' && d.estado !== 'anulado'; }).length;
			$('#deliveryAbiertos').text(abiertos).toggle(abiertos > 0);
			if (!deliveryPedidos.length) {
				$l.html('<div class="del-vacio"><i class="fa fa-motorcycle fa-2x"></i><br>' + (deliveryFiltro === 'hoy' ? 'Hoy no hay deliverys.' : 'No hay deliverys por entregar.')
					+ '<br><small>Toma uno con <b>Nuevo delivery</b>.</small></div>');
				return;
			}
			$.each(deliveryPedidos, function (i, d) {
				var items = $.map(d.items || [], function (it) { return esc(fmtCant(it.cantidad) + ' x ' + it.nombre) + (it.nota ? ' <i>(' + esc(it.nota) + ')</i>' : ''); }).join('<br>');
				var pago = d.cobrado ? '<span class="del-pago cobrado">Cobrado' + (d.formas_pago ? ' · ' + esc(d.formas_pago) : '') + '</span>'
					: '<span class="del-pago pendiente">' + (d.venta_anulada ? 'Boleta anulada' : 'Por cobrar') + '</span>';
				var cerrado = d.estado === 'entregado' || d.estado === 'anulado';
				var acc = '<button type="button" class="btn btn-sm btn-success" data-del="wsp"><i class="fa fa-whatsapp"></i> WhatsApp</button>';
				if (!cerrado) {
					if (!d.cobrado) acc += '<button type="button" class="btn btn-sm btn-primary" data-del="cobrar"><i class="fa fa-money"></i> Cobrar</button>';
					if (d.estado === 'preparando') acc += '<button type="button" class="btn btn-sm btn-default" data-del="camino"><i class="fa fa-motorcycle"></i> En camino</button>';
					acc += '<button type="button" class="btn btn-sm btn-default" data-del="entregado"' + (d.cobrado ? '' : ' disabled title="Cóbralo antes de marcarlo entregado"') + '><i class="fa fa-check"></i> Entregado</button>';
					if (!d.cobrado) acc += '<button type="button" class="btn btn-sm btn-default" data-del="anular"><i class="fa fa-ban"></i> Anular</button>';
				}
				$l.append('<div class="del-pedido est-' + esc(d.estado) + '" data-id="' + d.id_delivery + '">'
					+ '<div class="del-cab"><span class="del-num">N° ' + d.id_delivery + '</span><span class="del-hora">' + esc(d.hora || '')
					+ (d.hora_entrega ? ' · entregado ' + esc(d.hora_entrega) : '') + '</span><span class="del-estado">' + esc(DEL_ESTADO[d.estado] || d.estado) + '</span></div>'
					+ '<div class="del-cliente">' + esc(d.nombre) + '</div>'
					+ '<div class="del-linea"><i class="fa fa-phone"></i> ' + esc(d.telefono || '') + '</div>'
					+ '<div class="del-linea"><i class="fa fa-map-marker"></i> ' + esc(d.direccion || '') + (d.referencia ? ' · ' + esc(d.referencia) : '')
					+ (d.sector ? ' <b>(' + esc(d.sector) + ')</b>' : '') + '</div>'
					+ (d.repartidor ? '<div class="del-linea"><i class="fa fa-user"></i> ' + esc(d.repartidor) + '</div>' : '')
					+ (d.observacion ? '<div class="del-linea"><i class="fa fa-comment-o"></i> ' + esc(d.observacion) + '</div>' : '')
					+ (d.estado === 'anulado' && d.motivo_anulacion ? '<div class="del-linea"><i class="fa fa-ban"></i> ' + esc(d.motivo_anulacion) + '</div>' : '')
					+ '<div class="del-items">' + items + (d.costo_envio ? '<br>Envío ' + fmt(d.costo_envio) : '') + '</div>'
					+ '<div class="del-total"><span>' + fmt(d.total) + '</span>' + pago + '</div>'
					+ '<div class="del-acciones">' + acc + '</div></div>');
			});
		}
		$('#listaDelivery').on('click', '[data-del]', function () {
			var id = Number($(this).closest('.del-pedido').data('id')), d = delPorId(id), que = $(this).data('del');
			if (!d) return;
			if (que === 'wsp') abrirWsp(d);
			else if (que === 'cobrar') cobrarDelivery(id);
			else if (que === 'entregado') cambiarEstadoDelivery(id, 'entregado');
			else if (que === 'camino') pedirEstadoDelivery(d, 'en_camino');
			else if (que === 'anular') pedirEstadoDelivery(d, 'anulado');
		});
		$('[data-del-filtro]').on('click', function () {
			deliveryFiltro = $(this).data('del-filtro');
			$('[data-del-filtro]').removeClass('active');
			$(this).addClass('active');
			cargarDeliverys();
		});
		$('#btnRecargarDelivery').on('click', cargarDeliverys);
		$('#lnkDelivery').on('shown.bs.tab', cargarDeliverys);

		// Estados: En camino (con repartidor), Entregado, Anulado (con motivo)
		var delEstadoPend = null;
		function pedirEstadoDelivery(d, estado) {
			delEstadoPend = { id: d.id_delivery, estado: estado };
			$('#delEstTitulo').text((estado === 'anulado' ? 'Anular' : 'En camino') + ' · delivery N° ' + d.id_delivery);
			$('#delEstGrupoRep').toggle(estado === 'en_camino');
			$('#delEstGrupoMotivo').toggle(estado === 'anulado');
			opcionesSelect($('#delEstRepartidor'), catalogo.delivery.repartidores, '— Sin asignar —');
			$('#delEstRepartidor').val(d.id_repartidor || 0);
			$('#delEstMotivo').val('');
			$('#btnDelEstConfirmar').toggleClass('btn-danger', estado === 'anulado').toggleClass('btn-primary', estado !== 'anulado')
				.text(estado === 'anulado' ? 'Anular delivery' : 'Marcar en camino');
			$('#modalDeliveryEstado').modal('show');
		}
		$('#modalDeliveryEstado').on('shown.bs.modal', function () {
			(delEstadoPend && delEstadoPend.estado === 'anulado' ? $('#delEstMotivo') : $('#delEstRepartidor')).focus();
		});
		$('#btnDelEstConfirmar').on('click', function () {
			if (!delEstadoPend) return;
			var p = delEstadoPend, extra = {};
			if (p.estado === 'en_camino') extra.id_repartidor = Number($('#delEstRepartidor').val()) || 0;
			if (p.estado === 'anulado') {
				extra.motivo = $.trim($('#delEstMotivo').val());
				if (!extra.motivo) { toastr.warning('Indica el motivo de la anulación'); $('#delEstMotivo').focus(); return; }
			}
			$('#modalDeliveryEstado').modal('hide');
			cambiarEstadoDelivery(p.id, p.estado, extra);
		});
		function cambiarEstadoDelivery(id, estado, extra) {
			postJson('deliveryEstado', $.extend({ id_delivery: id, estado: estado }, extra || {})).done(function () {
				toastr.success('Delivery N° ' + id + ': ' + DEL_ESTADO[estado].toLowerCase(), '', { timeOut: 2500 });
				cargarDeliverys();
			}).fail(error);
		}

		// Mensaje para WhatsApp: se puede copiar o abrir WhatsApp con el mensaje listo (wa.me)
		var delWsp = null;
		function textoWsp(d) {
			var l = ['*Delivery N° ' + d.id_delivery + '*', '👤 ' + d.nombre, '📞 ' + (d.telefono || ''),
				'📍 ' + (d.direccion || '') + (d.referencia ? ' (' + d.referencia + ')' : '') + (d.sector ? ' · ' + d.sector : ''), ''];
			$.each(d.items || [], function (i, it) { l.push('• ' + fmtCant(it.cantidad) + ' x ' + it.nombre + (it.nota ? ' (' + it.nota + ')' : '')); });
			if (d.costo_envio) l.push('• Envío ' + fmt(d.costo_envio));
			l.push('', '*Total: ' + fmt(d.total) + '*', d.cobrado ? '✅ Pagado' + (d.formas_pago ? ' (' + d.formas_pago + ')' : '') : '💵 Se paga al recibir');
			if (d.observacion) l.push('📝 ' + d.observacion);
			if (d.repartidor) l.push('🛵 Lo lleva: ' + d.repartidor);
			return l.join('\n');
		}
		// Celular chileno: 9 dígitos que empiezan con 9 → 569...; 8 dígitos → 569 + número
		function telWsp(t) {
			var n = String(t || '').replace(/\D/g, '');
			if (n.length === 9 && n.charAt(0) === '9') n = '56' + n;
			else if (n.length === 8) n = '569' + n;
			return n;
		}
		function linkWsp() {
			var n = telWsp(delWsp && delWsp.telefono);
			$('#btnDelWspAbrir').attr('href', n ? 'https://wa.me/' + n + '?text=' + encodeURIComponent($('#delWspTexto').val()) : '#').toggleClass('disabled', !n);
		}
		function abrirWsp(d) {
			delWsp = d;
			$('#delWspTexto').val(textoWsp(d));
			linkWsp();
			$('#modalDeliveryWsp').modal('show');
		}
		$('#delWspTexto').on('input', linkWsp);
		$('#btnDelWspCopiar').on('click', function () {
			var txt = $('#delWspTexto').val();
			var ok = function () { toastr.success('Pégalo en el chat de WhatsApp', 'Mensaje copiado', { timeOut: 3000 }); };
			var aMano = function () {
				var t = document.getElementById('delWspTexto');
				t.focus();
				t.select();
				var hecho = false;
				try { hecho = document.execCommand('copy'); } catch (e) { hecho = false; }
				if (hecho) ok(); else toastr.warning('Selecciona el texto y cópialo con Ctrl+C');
			};
			// navigator.clipboard solo existe en https o localhost: en la red del local se copia a mano
			if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(txt).then(ok, aMano);
			else aMano();
		});

