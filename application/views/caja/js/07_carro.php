		// ---------------- carro ----------------
		// Productos fraccionables (permite_porcion, p. ej. por kilo): cantidad con hasta 3 decimales.
		// El resto se vende por unidades enteras.
		function esFraccion(id) { var p = producto(id); return !!(p && p.fraccion); }
		function pideCantidad(id) { var p = producto(id); return !!(p && (p.pedir_cantidad || p.fraccion)); }
		function ajustarCantidad(id, c) {
			c = Number(String(c).replace(',', '.'));
			if (!isFinite(c)) return 0;
			return esFraccion(id) ? Math.round(c * 1000) / 1000 : Math.round(c);
		}
		function fmtCant(c) { return String(Math.round(c * 1000) / 1000).replace('.', ','); }

		// cantidad: la del ingreso rápido "cantidad*código"; sin ella se agrega 1
		function agregar(id, agregados, nota, cantidad, notasAg, menuSel, sushiSel) {
			sushiSel = sushiSel || null;   // v1.25.0: lo armado de una promo de sushi {grupos, extras}
			notasAg = notasAg || {};
			menuSel = menuSel || [];   // v1.24.0: lo elegido del menú [{id, cantidad}]
			var p = producto(id);
			if (!p) return;
			// 18-11-2026: un delivery guardado se cobra tal como se pidió
			if (modoDelivery && modoDelivery.id) { toastr.warning('Este delivery ya está guardado: se cobra tal como se pidió'); return; }
			var dada = cantidad ? ajustarCantidad(id, cantidad) : 0;
			// Con un cobro parcial a medias, lo nuevo no entraría en ese cobro: se cancela y se avisa
			if (cobroParcial || dcCobro) {
				cobroParcial = null;
				dcCobro = null;
				actualizarModoMesa();
				toastr.info('Se canceló el cobro de una parte: al cobrar se incluirá toda la mesa');
			}
			agregados = agregados.slice().sort(function (a, b) { return a - b; });
			// Solo se suma a una línea existente si tiene exactamente las mismas opciones.
			// v1.24.8: con "Agrupar productos iguales" apagado (Configuraciones) cada producto va en su propia fila
			var agrupar = !(catalogo && catalogo.config && catalogo.config.agrupar_productos === false);
			var l = !agrupar ? null : $.grep(carro, function (x) {
				return !x.enviada && x.id_producto === id && x.nota === nota && x.agregados.join(',') === agregados.join(',')
					&& JSON.stringify(x.notas_agregados || {}) === JSON.stringify(notasAg) && JSON.stringify(x.menu || []) === JSON.stringify(menuSel)
					&& JSON.stringify(x.sushi || null) === JSON.stringify(sushiSel);
			})[0];
			// El cursor se queda en el buscador para seguir ingresando (la cantidad se cambia en la línea o con cantidad*código).
			// Un pesable ya agregado no suma 1 kg a ciegas: solo suma si se indicó la cantidad.
			if (dada > 0) {
				if (l) l.cantidad = ajustarCantidad(id, l.cantidad + dada);
				else carro.push({ id_producto: id, nombre: p.nombre, precio: p.precio, cantidad: dada, agregados: agregados, nota: nota, notas_agregados: notasAg, menu: menuSel, sushi: sushiSel });
			} else if (l) {
				if (!esFraccion(id)) l.cantidad += 1;
			} else carro.push({ id_producto: id, nombre: p.nombre, precio: p.precio, cantidad: 1, agregados: agregados, nota: nota, notas_agregados: notasAg, menu: menuSel, sushi: sushiSel });
			renderCarro();
		}

		/** v1.24.0: cantidad de un producto del menú que acompaña a qPadre de su menú (igual que Mcaja::conProductosDelMenu) */
		function cantidadHijoMenu(h, qPadre) {
			var padre = $.grep(carro, function (x) { return x.enviada && x.id_detalle === h.menu_de; })[0];
			if (!padre || !(padre.cantidad > 0)) return 0;
			var f = qPadre / padre.cantidad;
			return f >= 0.9995 ? h.cantidad : r3(h.cantidad * f);
		}
		// Líneas brutas como las guarda el servidor: el producto con su primer agregado, y cada agregado
		// siguiente como línea propia; si el producto es exento, todas sus líneas lo son
		function lineasBrutas() {
			var res = [];
			$.each(carro, function (i, l) {
				if (l.enviada) {
					var exEnv = l.exento || documentoExento(), ilaEnv = exEnv ? 0 : Number(l.ila) || 0;
					if (!cobroParcial) { res.push({ bruto: l.bruto, exento: exEnv, ila: ilaEnv }); return; }
					var q = cobroParcial[l.id_detalle] || 0;
					// v1.24.0: un producto del menú va en la proporción de su menú (Mcaja::conProductosDelMenu)
					if (!q && l.menu_de && cobroParcial[l.menu_de]) q = cantidadHijoMenu(l, cobroParcial[l.menu_de]);
					// Misma proporción que Mcaja::seleccionDeLineas()
					if (q > 0) res.push({ bruto: q >= l.cantidad - 0.0005 ? l.bruto : Math.round(l.bruto_exacto * q / l.cantidad), exento: exEnv, ila: ilaEnv });
					return;
				}
				if (cobroParcial) return;
				var p = producto(l.id_producto), exento = !!(p && p.exento) || documentoExento();
				// v1.20.0: ILA del producto (los agregados llevan el de su producto, como el exento: Mcaja::conIla)
				var ila = exento ? 0 : Number(p && p.ila) || 0;
				var primero = l.agregados.length ? agregado(l.agregados[0]) : null;
				var precioBase = l.sushi ? precioBaseSushi(sushiDe(l.id_producto), l.sushi, l.precio) : l.precio;   // v1.32.0: precio según el envoltorio
				res.push({ bruto: Math.round((precioBase + (primero ? primero.valor : 0)) * l.cantidad), exento: exento, ila: ila });
				// v1.24.0: cada producto del menú con costo adicional es una línea más (Mcaja::armarLineas)
				var menuL = l.menu && l.menu.length ? menuDe(l.id_producto) : null;
				$.each(menuL ? l.menu : [], function (j, e) {
					var o = opcionMenu(menuL, e.id);
					if (o && o.adicional) res.push({ bruto: Math.round(o.adicional * r3(l.cantidad * e.cantidad)), exento: exento, ila: ila });
				});
				// v1.25.0: promo de sushi: cada producto de cada grupo (por pieza) y cada extra es una línea (Mcaja::armarLineas)
				var suL = l.sushi ? sushiDe(l.id_producto) : null;
				$.each(suL ? l.sushi.grupos : [], function (j, g) {
					var q = r3(l.cantidad * unidadesGrupoSushi(suL, g));   // v1.26.0: por roll, los rolls
					// v1.32.0: varios envoltorios (con precio según el envoltorio, su valor no se cobra aparte)
					$.each($.map(suEnvs(g), function (id) { return [['envoltorio', id]]; }).concat([['base', g.base]], $.map(g.rellenos || [], function (id) { return [['relleno', id]]; })), function (k, par) {
						var o = par[1] ? opcionSushi(suL, par[0], par[1]) : null;
						if (par[0] === 'envoltorio' && suL.precio_envoltorio) return;
						if (o && o.valor) res.push({ bruto: Math.round(o.valor * q), exento: exento, ila: ila });
					});
				});
				$.each(suL ? [['extra', l.sushi.extras || []], ['salsa', l.sushi.salsas || []]] : [], function (j, par) {   // v1.32.0: salsas
					$.each(par[1], function (k, e) {
						var o = opcionSushi(suL, par[0], e.id);
						if (o && o.valor) res.push({ bruto: Math.round(o.valor * r3(l.cantidad * e.cantidad)), exento: exento, ila: ila });
					});
				});
				$.each(l.agregados.slice(1), function (j, id) {
					var a = agregado(id);
					if (a) res.push({ bruto: Math.round(a.valor * l.cantidad), exento: exento, ila: ila });
				});
			});
			// 18-11-2026: delivery: el envío es una línea más de la boleta (Mcaja::lineaEnvio)
			var envio = envioDelivery();
			if (envio > 0 && carro.length) {
				var e = catalogo.delivery.envio, exE = !!(e && e.exento) || documentoExento();
				res.push({ bruto: envio, exento: exE, ila: exE ? 0 : Number(e && e.ila) || 0 });
			}
			return res;
		}

		// Mismo cálculo que Mcaja::montos() (el servidor valida el pago contra su total):
		// - lo exento queda fuera del IVA (Monto Exento)
		// - v1.20.0: lo afecto se agrupa por tasa de ILA; cada grupo usa el factor 1 + (IVA + ILA) / 100 y su ILA = round(Neto x ILA)
		// - factura: neto por línea, Neto = suma - descuento neto, IVA = round(Neto x 19%), Total = Neto + IVA + ILA + Exento
		// - boleta / interno: Neto = round(Afecto / factor), IVA = Afecto - Neto - ILA, Total = suma de precios - descuento
		function totales() {
			var lineas = lineasBrutas(), subtotal = 0, subAfecto = 0;
			var tasa = catalogo ? catalogo.config.por_iva : 19;
			var grupos = {}, orden = [];
			$.each(lineas, function (i, l) {
				subtotal += l.bruto;
				if (l.exento) return;
				subAfecto += l.bruto;
				var r = Math.max(0, Number(l.ila) || 0), k = 'r' + r.toFixed(2);
				if (!grupos[k]) { grupos[k] = { tasa: r, bruto: 0, netoLineas: 0 }; orden.push(k); }
				grupos[k].bruto += l.bruto;
				grupos[k].netoLineas += Math.round(l.bruto / (1 + (tasa + r) / 100));
			});
			var valor = Math.max(0, Number($('#txtDescuento').val()) || 0);
			var descuento = tipoDesc === 'porc' ? Math.round(subtotal * Math.min(valor, 100) / 100) : Math.round(valor);
			descuento = Math.min(descuento, subtotal);
			var descAfecto = subtotal > 0 ? Math.round(descuento * subAfecto / subtotal) : 0;
			var exento = (subtotal - subAfecto) - (descuento - descAfecto);
			var d = catalogo ? documentoActual() : null, esFactura = !!(d && d.es_factura), neto = 0, ila = 0, iva;
			var resto = descAfecto;
			$.each(orden, function (i, k) {
				var g = grupos[k], f = 1 + (tasa + g.tasa) / 100;
				var desc = i === orden.length - 1 ? resto : (subAfecto > 0 ? Math.round(descAfecto * g.bruto / subAfecto) : 0);
				resto -= desc;
				var ng = esFactura ? g.netoLineas - Math.round(desc / f) : Math.round((g.bruto - desc) / f);
				neto += ng;
				ila += Math.round(ng * g.tasa / 100);
			});
			iva = esFactura ? Math.round(neto * tasa / 100) : (subAfecto - descAfecto) - neto - ila;
			return { subtotal: subtotal, descuento: descuento, exento: exento, neto: neto, iva: iva, ila: ila, total: neto + iva + ila + exento };
		}

		function renderCarro() {
			var $t = $('#lineas').empty();
			if (!carro.length) {
				$t.append('<div class="caja-carro-vacio"><i class="pe-7s-cart"></i>Agrega productos con el buscador o con el botón Productos (F2)</div>');
			}
			var unidades = 0;
			$.each(carro, function (i, l) {
				if (l.enviada) {
					// v1.24.0: producto elegido de un menú: va debajo de su menú, sin botones (se anula / cobra / mueve con el menú)
					if (l.menu_de) {
						var qCobro = cobroParcial ? (cobroParcial[l.id_detalle] || (cobroParcial[l.menu_de] ? cantidadHijoMenu(l, cobroParcial[l.menu_de]) : 0)) : null;
						$t.append('<div class="caja-linea enviada hijo-menu' + (cobroParcial && !qCobro ? ' fuera-cobro' : '') + '" data-i="' + i + '"><div class="info">'
							+ '<div class="nombre">+ ' + esc(l.nombre) + '</div>' + (l.nota ? '<div class="detalle-linea"><i>' + esc(l.nota) + '</i></div>' : '') + '</div>'
							+ '<div class="precio">' + (l.bruto ? fmt(l.precio) : '') + '</div>'
							+ '<div class="cantidad">' + fmtCant(l.cantidad) + '</div>'
							+ '<div class="subtotal">' + (l.bruto ? fmt(l.bruto) : '') + '</div><span class="quitar-hueco" aria-hidden="true"></span></div>');
						return;
					}
					unidades += l.cantidad === Math.round(l.cantidad) ? l.cantidad : 1;
					var fuera = cobroParcial && !cobroParcial[l.id_detalle];
					var parte = cobroParcial && cobroParcial[l.id_detalle] && cobroParcial[l.id_detalle] < l.cantidad ? ' · se cobran ' + fmtCant(cobroParcial[l.id_detalle]) : '';
					$t.append('<div class="caja-linea enviada' + (fuera ? ' fuera-cobro' : '') + '" data-i="' + i + '"><div class="info">'
						+ '<div class="nombre"><i class="fa fa-lock" title="Ya enviado a la mesa"></i>' + esc(l.nombre) + '</div>'
						// v1.30.3: el mensaje del producto junto al producto y el del agregado junto al agregado
						+ (l.nota ? '<div class="detalle-linea"><i>» ' + esc(l.nota) + '</i></div>' : '')
						+ (l.agregado ? '<div class="detalle-linea">+ ' + esc(l.agregado) + (l.nota_agregado ? ' <i>» ' + esc(l.nota_agregado) + '</i>' : '') + '</div>' : '')
						+ '<div class="hora">Enviado ' + esc(l.hora) + parte + '<a href="#" class="reenviar-linea" title="Vuelve a imprimir este producto en su destino (cocina, barra…)"><i class="fa fa-repeat"></i> Volver a enviar</a></div></div>'
						+ '<div class="precio">' + fmt(l.precio) + '</div>'
						+ '<div class="cantidad">' + fmtCant(l.cantidad) + '</div>'
						+ '<div class="subtotal">' + fmt(l.bruto) + '</div>'
						+ '<button class="quitar" title="Anular producto"><i class="fa fa-ban"></i></button></div>');
					return;
				}
				var fraccion = esFraccion(l.id_producto);
				// Un producto pesado cuenta como 1 ítem, no como 0,35
				unidades += fraccion ? 1 : l.cantidad;
				// v1.30.3: el mensaje del producto va primero, junto al producto (antes salía abajo de todo, después de los agregados)
				var detalle = l.nota ? ['<i class="nota-producto">» ' + esc(l.nota) + '</i>'] : [];
				detalle = detalle.concat($.map(l.agregados, function (id) {
					var a = agregado(id), na = l.notas_agregados && l.notas_agregados[id];
					return a ? '+ ' + esc(a.nombre) + (na ? ' <i>» ' + esc(na) + '</i>' : '') : null;
				}));
				// v1.24.0: lo elegido del menú
				var menuL = l.menu && l.menu.length ? menuDe(l.id_producto) : null;
				$.each(menuL ? l.menu : [], function (j, e) {
					var o = opcionMenu(menuL, e.id);
					if (o) detalle.push('<span class="del-menu">+ ' + (e.cantidad > 1 ? e.cantidad + ' ' : '') + esc(o.nombre) + (o.adicional ? ' <b>+' + fmt(o.adicional * e.cantidad) + '</b>' : '') + '</span>');
				});
				if (menuDe(l.id_producto) && !(l.menu && l.menu.length)) detalle.push('<span class="text-danger">Falta elegir los productos del menú</span>');
				// v1.25.0: lo armado de la promo de sushi
				var suD = sushiDe(l.id_producto);
				if (suD && l.sushi) {
					$.each(l.sushi.grupos, function (j, g) {
						var tx = textoGrupoSushi(suD, g), v = unidadesGrupoSushi(suD, g) * valorPiezaSushi(suD, g);
						detalle.push('<span class="del-sushi">' + textoCantSushi(suD, g.piezas) + ' <b>' + esc(tx.titulo) + '</b>' + (tx.rellenos ? ' · ' + esc(tx.rellenos) : '') + (tx.base ? ' · ' + esc(tx.base) : '') + (v ? ' <b>+' + fmt(v) + '</b>' : '') + (g.nota ? ' · <i>' + esc(g.nota) + '</i>' : '') + '</span>');
					});
					$.each([['extra', l.sushi.extras || []], ['salsa', l.sushi.salsas || []]], function (j, par) {   // v1.32.0: salsas
						$.each(par[1], function (k, e) {
							var o = opcionSushi(suD, par[0], e.id);
							if (o) detalle.push('<span class="del-sushi">+ ' + (e.cantidad > 1 ? e.cantidad + ' ' : '') + esc(o.nombre) + (o.valor ? ' <b>+' + fmt(o.valor * e.cantidad) + '</b>' : '') + '</span>');
						});
					});
				} else if (suD) detalle.push('<span class="text-danger">Falta armar ' + (suEsRol(suD) ? 'los rolls' : 'las piezas') + ' de la promo</span>');
				var nombre = tieneOpciones(l.id_producto) ? '<a href="#" class="editar" title="' + (sushiDe(l.id_producto) ? (suEsRol(sushiDe(l.id_producto)) ? 'Cambiar los rolls de la promo' : 'Cambiar las piezas de la promo') : menuDe(l.id_producto) ? 'Cambiar lo elegido del menú' : 'Cambiar agregados / indicaciones') + '">' + esc(l.nombre) + '</a>' : esc(l.nombre);
				// Mensaje para la comanda: disponible en cualquier producto, tenga o no mensajes predefinidos
				nombre += ' <a href="#" class="editar caja-btn-mensaje' + (l.nota ? ' con-mensaje' : '') + '" title="' + (l.nota ? 'Editar mensaje para la comanda' : 'Agregar mensaje para la comanda') + '"><i class="fa fa-commenting-o"></i></a>';
				var prod = producto(l.id_producto);
				if (prod && prod.exento) nombre += ' <span class="label label-default">EXENTO</span>';
				// v1.30.3/v1.32.0 (pedido del usuario): flechas para cambiar el orden (solo lo que aún no se envía; así sale en la comanda)
				var sueltas = $.map(carro, function (x, k) { return x.enviada ? null : k; }), pos = $.inArray(i, sueltas);
				var mover = sueltas.length > 1 ? '<div class="mover-linea"><button type="button" class="subir" title="Subir"' + (pos <= 0 ? ' disabled' : '') + '><i class="fa fa-chevron-up"></i></button>'
					+ '<button type="button" class="bajar" title="Bajar"' + (pos >= sueltas.length - 1 ? ' disabled' : '') + '><i class="fa fa-chevron-down"></i></button></div>' : '';
				$t.append('<div class="caja-linea" data-i="' + i + '">' + mover
					+ '<div class="info">' + (prod && prod.cbarra ? '<div class="cbarra"><i class="fa fa-barcode"></i> ' + esc(prod.cbarra) + '</div>' : '') + '<div class="nombre">' + nombre + '</div>'
					+ (detalle.length ? '<div class="detalle-linea">' + detalle.join('<br>') + '</div>' : '')
					+ '<div class="unitario">' + fmt(precioLinea(l)) + (prod && prod.umedida ? ' / ' + esc(prod.umedida) : ' c/u') + (fraccion && l.cantidad !== Math.round(l.cantidad) ? ' · ' + fmtCant(l.cantidad) + ' ' + esc(prod.umedida || '') : '') + '</div></div>'
					+ '<div class="precio">' + fmt(precioLinea(l)) + '</div>'
					+ '<div class="input-group input-group-sm cantidad">'
					+ '<span class="input-group-btn"><button class="btn btn-default menos" type="button" title="Quitar uno"><i class="fa fa-minus"></i></button></span>'
					+ '<input type="number" class="form-control cant' + (fraccion ? ' fraccion' : '') + '" min="' + (fraccion ? '0.001' : '1') + '" step="' + (fraccion ? '0.001' : '1') + '" inputmode="' + (fraccion ? 'decimal' : 'numeric') + '" value="' + l.cantidad + '"' + (fraccion ? ' title="Cantidad con decimales (ej: 0,350)"' : '') + '>'
					+ (fraccion && prod.umedida ? '<span class="input-group-addon caja-umedida">' + esc(prod.umedida) + '</span>' : '')
					+ '<span class="input-group-btn"><button class="btn btn-default mas" type="button" title="Agregar uno"><i class="fa fa-plus"></i></button></span></div>'
					+ '<div class="subtotal">' + fmt(Math.round(precioLinea(l) * l.cantidad)) + '</div>'
					+ '<button class="quitar" title="Quitar producto"><i class="fa fa-trash"></i></button></div>');
			});
			$('#cantItems, #barraItems, #modalProductosItems').text(unidades);
			$('#barraMovil').toggleClass('visible', carro.length > 0);
			$('.caja').toggleClass('con-barra', carro.length > 0);
			var t = totales();
			$('#lblSubtotal').text(fmt(t.subtotal));
			$('#lblDescuento').text(fmt(-t.descuento));
			$('#filaDescuento').toggle(t.descuento > 0);
			$('#lblConDescuento').text(fmt(t.total));
			$('#filaConDescuento').toggle(t.descuento > 0);
			$('#lblNeto').text(fmt(t.neto));
			$('#lblIva').text(fmt(t.iva));
			$("#lblIla").text(fmt(t.ila || 0)); $("#filaIla").toggle((t.ila || 0) > 0);   // v1.20.0
			$('#lblExento').text(fmt(t.exento));
			$('#filaExento').toggle(t.exento > 0);
			$('#lblTotal').text(fmt(t.total));
			$('#lblCobrar').text(carro.length ? fmt(t.total) : '');
			$('#barraTotal').text(fmt(t.total));
			$('#btnCobrar').prop('disabled', !carro.length || t.subtotal <= 0);
			$('#btnEnviarMesa, #btnEnviarMesaBarra').prop('disabled', !pendientesMesa().length);
			botonesMesa();
			pintarEnvioDelivery();   // 18-11-2026: fila del envío y "Cobrar" / "Guardar delivery"
			if (favoritosVisible()) renderFavoritos();   // cantidades en venta del panel Favoritos
		}
		// En una mesa va un paso a la vez: con productos sin enviar solo se puede "Enviar a mesa";
		// el cobro aparece cuando todo lo pedido ya está en la mesa (se cobra al final, cuando el cliente pide la cuenta)
		// En una mesa, junto a Cobrar va la impresora (precuenta) en vez del basurero (vaciar es de la venta directa)
		function botonesMesa() {
			$('#btnLimpiar').toggle(!mesa);
			$('#btnPrecuenta').toggle(!!(mesa && mesa.id_cotizacion));
			if (!mesa) { $('#btnEnviarMesa').hide(); $('#btnCobrar').show(); return; }
			var porEnviar = pendientesMesa().length > 0, hayEnviado = carro.length > 0 && !porEnviar;
			$('#btnEnviarMesa').toggle(!hayEnviado);
			$('#btnCobrar').toggle(hayEnviado);
			$('#btnPrecuenta').prop('disabled', porEnviar)
				.attr('title', porEnviar ? 'Primero envía a la mesa los productos nuevos' : 'Precuenta: imprime el detalle para que el cliente lo revise antes de pagar');
		}

		// Enter en la cantidad: la confirma y vuelve al buscador (flujo rápido con balanza / teclado)
		// Tras agregar un producto que pide cantidad el cursor queda aquí: si se sigue escribiendo el nombre de otro
		// producto o se escanea un código, eso va al buscador y no a la cantidad (antes vaciaba y borraba la línea)
		$('#lineas').on('keydown', '.cant', function (e) {
			var i = $(this).closest('[data-i]').data('i');
			if (e.key && e.key.length === 1 && !e.ctrlKey && !e.metaKey && !/[0-9.,]/.test(e.key)) {
				e.preventDefault();
				$(this).val(carro[i].cantidad);
				$('#buscarProducto').focus().val($('#buscarProducto').val() + e.key).trigger('input');
				return;
			}
			if (e.which === 13) {
				e.preventDefault();
				var texto = $.trim($(this).val()).toLowerCase();
				var escaneado = texto.length >= 4 && $.grep(catalogo.productos, function (p) { return p.cbarra && p.cbarra.toLowerCase() === texto; })[0];
				if (escaneado) {
					$(this).val(carro[i].cantidad);
					$('#buscarProducto').val('').focus();
					elegir(escaneado.id_producto);
					return;
				}
				$(this).trigger('change');
				$('#buscarProducto').focus();
			}
		});
		$('#lineas').on('change', '.cant', function () {
			var i = $(this).closest('[data-i]').data('i');
			// Vacía o inválida: se deja la cantidad que tenía (solo un 0 escrito o el basurero quitan la línea)
			if ($.trim($(this).val()) === '' || !isFinite(Number(String($(this).val()).replace(',', '.')))) { renderCarro(); return; }
			if (Number(String($(this).val()).replace(',', '.')) > 9999) {
				toastr.warning('La cantidad ' + $(this).val() + ' es demasiado alta: si escaneaste un código, ese producto no está en la Carta');
				renderCarro();
				return;
			}
			var c = ajustarCantidad(carro[i].id_producto, $(this).val());
			if (c > 0) carro[i].cantidad = c;
			renderCarro();
			if (!(c > 0)) preguntarQuitar(i);   // v1.30.3: un 0 escrito también pregunta (si dice que no, queda como estaba)
		}).on('click', '.quitar', function () {
			var i = $(this).closest('[data-i]').data('i');
			if (carro[i].enviada) { anularLineaMesa(carro[i]); return; }
			preguntarQuitar(i);
		}).on('click', '.mas, .menos', function () {
			var i = $(this).closest('[data-i]').data('i');
			// Pesables: +/- de a 0,1 (100 g); el resto de a una unidad
			var paso = esFraccion(carro[i].id_producto) ? 0.1 : 1;
			var nueva = ajustarCantidad(carro[i].id_producto, carro[i].cantidad + ($(this).hasClass('mas') ? paso : -paso));
			if (nueva <= 0) { preguntarQuitar(i); return; }   // v1.30.3: el "-" en la última unidad también pregunta
			carro[i].cantidad = nueva;
			renderCarro();
		}).on('click', '.subir, .bajar', function () {
			// Cambia de lugar con la fila de arriba o de abajo que tampoco se ha enviado (las enviadas a la mesa quedan donde están)
			var i = $(this).closest('[data-i]').data('i'), arriba = $(this).hasClass('subir'), j = i;
			do { j += arriba ? -1 : 1; } while (j >= 0 && j < carro.length && carro[j].enviada);
			if (j < 0 || j >= carro.length || carro[i].enviada) return;
			var x = carro[i]; carro[i] = carro[j]; carro[j] = x;
			renderCarro();
			$('#lineas [data-i="' + j + '"]').addClass('recien-movida').find(arriba ? '.subir' : '.bajar').focus();
		}).on('click', '.editar', function (e) {
			e.preventDefault();
			var i = $(this).closest('[data-i]').data('i');
			abrirOpciones(carro[i].id_producto, i);
		});

		/** v1.30.3 (pedido del usuario): quitar un producto de la venta pregunta antes (se borraban con un clic sin querer). */
		function preguntarQuitar(i) {
			var l = carro[i];
			if (!l) return;
			confirmarSistema('¿Quitar ' + l.nombre + '?', 'Se quita de la venta' + (l.cantidad !== 1 ? ' (' + fmtCant(l.cantidad) + ')' : '') + '.', 'Sí, quitar', function () {
				var j = carro.indexOf(l);   // la venta pudo cambiar mientras estaba la pregunta abierta
				if (j < 0) return;
				carro.splice(j, 1);
				renderCarro();
				$('#buscarProducto').focus();
			}, true);
		}

		$('#txtDescuento').on('input', renderCarro);

		// Escritorio: la venta ocupa el alto visible para que el total y Cobrar queden siempre a la vista
		function ajustarAltoVenta() {
			var $v = $('.caja-venta');
			// Dentro del drawer de mesa el alto lo da el drawer (CSS): solo se marca el modo de escritorio
			if ($v.closest('#mesaDrawer').length) { $v.toggleClass('alto-fijo', window.innerWidth >= 992).css('height', ''); return; }
			if (!$v.is(':visible') || window.innerWidth < 992) { $v.removeClass('alto-fijo').css('height', ''); return; }
			// El pie del sistema queda fijo abajo y taparía el botón Cobrar
			var $pie = $('.main-footer');
			var pie = $pie.length && $pie.css('position') === 'fixed' ? $pie.outerHeight() : 0;
			var alto = Math.max(440, window.innerHeight - $v.offset().top - pie - 16);
			$v.addClass('alto-fijo').css('height', alto);
		}
		$(window).on('resize', ajustarAltoVenta);
		$('#lnkVenta').on('shown.bs.tab', ajustarAltoVenta);
		// Lo que queda sobre la venta puede cambiar de alto después del primer cálculo (pestaña Mesas que aparece y hace
		// saltar de línea las pestañas, fuentes que terminan de cargar, barra lateral contraída/expandida): se recalcula
		if (window.ResizeObserver) {
			var observaBarra = new ResizeObserver(function () { ajustarAltoVenta(); });
			$('.caja-barra').each(function () { observaBarra.observe(this); });
		}
		if (document.fonts && document.fonts.ready) document.fonts.ready.then(function () { ajustarAltoVenta(); });
		$(window).on('load', ajustarAltoVenta);

