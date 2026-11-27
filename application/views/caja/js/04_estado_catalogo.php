		// ---------------- estado de caja ----------------
		function cargarEstado() {
			return get('estado').done(function (r) {
				resumen = r.caja;
				$('body').toggleClass('caja-sin-abrir', !resumen);   // fondo con foto mientras se pide abrir la caja
				if (!resumen) {
					$('#panelCaja').hide();
					$('#panelApertura').show();
					var sugerido = r.fondo_sugerido;
					$('#fondoInicial').val(sugerido === null || sugerido === undefined ? 0 : sugerido);
					$('#ayudaFondo').toggle(sugerido !== null && sugerido !== undefined);
					pintarApertura(r);
					if ($('#cboControlCaja').is(':visible') && !$('#cboControlCaja').val()) $('#cboControlCaja').focus();
					else $('#fondoInicial').focus();
					return;
				}
				$('#panelApertura').hide();
				$('#panelCaja').show();
				cargarPagoElectronico();   // 19-11-2026: máquina POS de la caja y cobros sin terminar
				// Cierre ciego: el informe X de la caja abierta (muestra el efectivo en caja) es solo para gerencia
				$('#btnInformeX').toggle(!resumen.cierre_ciego);
				cargarMesas();
				ajustarAltoVenta();
				var cc = resumen.caja_control;
				$('#estCaja').text((cc ? cc.nombre + ' · ' : '') + 'N° ' + resumen.id_caja)
					.attr('title', cc ? 'Lista de precios: ' + (cc.lista || 'la de Configuraciones') + ' · Bodega: ' + (cc.bodega || 'la de cada producto') : '');
				$('#estApertura').text(resumen.apertura);
				if (!catalogo) cargarCatalogo(0);
				if (!esperasIniciadas) iniciarEsperas();   // ventas en espera (ver más abajo)
			});
		}

		// Herramientas > Control de cajas: al abrir se elige empresa, sucursal y una de sus cajas que no esté en uso
		// (12-11-2026). Sin cajas en Control de cajas se abre como antes.
		var cajasControl = [];
		var apertura = { empresas: [], id_empresa: 0, id_sucursal: 0, hay_cajas: false, cajas_en_uso: 0 };
		var CLAVE_ULTIMA_CAJA = 'resto_caja_control_ultima';
		function pintarApertura(r) {
			apertura = { empresas: r.empresas || [], id_empresa: r.id_empresa || 0, id_sucursal: r.id_sucursal || 0,
				hay_cajas: !!r.hay_cajas, cajas_en_uso: r.cajas_en_uso || 0 };
			var hayEmpresas = apertura.empresas.length > 0;
			$('#grupoEmpresaApertura, #grupoSucursalApertura').toggle(hayEmpresas);
			if (hayEmpresas) {
				var $e = $('#cboEmpresaApertura').empty();
				$.each(apertura.empresas, function (i, e) {
					$e.append($('<option>').val(e.id).text((e.rut ? e.rut + ' · ' : '') + (e.nombre || 'Empresa')));
				});
				$e.val(String(apertura.id_empresa));
				pintarSucursalesApertura();
			}
			pintarCajasControl(r.cajas_control || []);
		}
		function empresaApertura() {
			return $.grep(apertura.empresas, function (e) { return e.id === apertura.id_empresa; })[0] || null;
		}
		function pintarSucursalesApertura() {
			var emp = empresaApertura();
			var $s = $('#cboSucursalApertura').empty();
			if (emp && emp.sucursales.length) {
				$.each(emp.sucursales, function (i, x) {
					$s.append($('<option>').val(x.id).text(x.nombre + (x.direccion ? ' · ' + x.direccion : '') + (x.comuna ? ', ' + x.comuna : '')));
				});
				$s.val(String(apertura.id_sucursal)).prop('disabled', false);
			} else {
				$s.append($('<option>').val('').text('Sin sucursales (Herramientas > Control de cajas)')).prop('disabled', true);
			}
		}
		function cargarCajasApertura(idSucursal) {
			$('#cboControlCaja').prop('disabled', true);
			get('cajasParaAbrir', { id_sucursal: idSucursal || 0 }).done(function (r) {
				pintarApertura(r);
			}).always(function () { $('#cboControlCaja').prop('disabled', false); });
		}
		$('#cboEmpresaApertura').on('change', function () {
			var id = Number($(this).val());
			var emp = $.grep(apertura.empresas, function (e) { return e.id === id; })[0];
			apertura.id_empresa = id;
			if (emp && emp.sucursales.length) { cargarCajasApertura(emp.sucursales[0].id); return; }
			apertura.id_sucursal = 0;
			pintarSucursalesApertura();
			pintarCajasControl([]);
		});
		$('#cboSucursalApertura').on('change', function () {
			var id = Number($(this).val());
			if (id) cargarCajasApertura(id);
		});
		function pintarCajasControl(cajas) {
			cajasControl = cajas;
			var hayEmpresas = apertura.empresas.length > 0;
			var $s = $('#cboControlCaja').empty();
			// Con empresas y sucursales la caja siempre se muestra (aunque la sucursal no tenga cajas libres)
			$('#grupoControlCaja').toggle(cajas.length > 0 || (hayEmpresas && apertura.hay_cajas));
			$('#textoApertura').html(cajas.length || (hayEmpresas && apertura.hay_cajas)
				? 'No tienes una caja abierta.<br>Elige la empresa, la sucursal y la caja, e ingresa el fondo inicial (sencillo).'
				: 'No tienes una caja abierta.<br>Ingresa el fondo inicial (sencillo) para comenzar a vender.');
			if (!cajas.length) {
				$s.append($('<option>').val('').text(apertura.id_sucursal ? 'No hay cajas libres en esta sucursal' : 'Elige la sucursal'));
				infoCajaControl();
				return;
			}
			var ultima = 0;
			try { ultima = Number(localStorage.getItem(CLAVE_ULTIMA_CAJA)) || 0; } catch (e) { ultima = 0; }
			var elegir = $.grep(cajas, function (c) { return c.id === ultima; })[0] || (cajas.length === 1 ? cajas[0] : null);
			$s.append($('<option>').val('').text('— Elige la caja —'));
			$.each(cajas, function (i, c) {
				$s.append($('<option>').val(c.id).text((c.nombre || 'Caja ' + c.caja_n) + (c.terminal ? ' · ' + c.terminal : '')));
			});
			$s.val(elegir ? String(elegir.id) : '');
			infoCajaControl();
		}
		function infoCajaControl() {
			var id = Number($('#cboControlCaja').val()) || 0;
			var c = $.grep(cajasControl, function (x) { return x.id === id; })[0];
			if (!c) {
				var enUso = apertura.cajas_en_uso;
				$('#infoControlCaja').html(enUso
					? '<i class="fa fa-exclamation-triangle"></i>' + (cajasControl.length ? '' : 'Todas las cajas de esta sucursal están en uso. ')
						+ enUso + (enUso === 1 ? ' caja abierta' : ' cajas abiertas') + ' por otros usuarios.'
					: (!cajasControl.length && apertura.id_sucursal && apertura.hay_cajas
						? '<i class="fa fa-info-circle"></i>Esta sucursal no tiene cajas de DataRestoWeb autorizadas en la intranet.' : ''));
				return;
			}
			$('#infoControlCaja').html('<i class="fa fa-tags"></i>Lista de precios: <b>' + esc(c.lista) + '</b>' + (c.cambia_lista ? '' : ' (fija)')
				+ '<br><i class="fa fa-cubes"></i>Bodega: <b>' + esc(c.bodega) + '</b>');
		}
		$('#cboControlCaja').on('change', function () {
			infoCajaControl();
			if ($(this).val()) $('#fondoInicial').focus();
		});

		$('#btnAbrirCaja').on('click', function () {
			var fondo = Number($('#fondoInicial').val());
			if (!(fondo >= 0)) { swal('Atención', 'Ingresa un fondo válido', 'warning'); return; }
			var idControl = Number($('#cboControlCaja').val()) || 0;
			if ((cajasControl.length || (apertura.empresas.length && apertura.hay_cajas)) && !idControl) {
				swal('Atención', 'Elige la empresa, la sucursal y la caja que vas a abrir', 'warning');
				return;
			}
			var $b = $(this).prop('disabled', true);
			post('abrir', { fondo: Math.round(fondo), id_control_caja: idControl }).done(function () {
				if (idControl) { try { localStorage.setItem(CLAVE_ULTIMA_CAJA, String(idControl)); } catch (e) { /* sin almacenamiento */ } }
				toastr.success('Caja abierta');
				cargarEstado();
			}).fail(function () {
				// La caja pudo quedar ocupada o no vigente: se vuelve a pedir la lista de esa sucursal
				if (apertura.empresas.length) cargarCajasApertura(apertura.id_sucursal);
				else if (cajasControl.length) cargarEstado();
			}).always(function () { $b.prop('disabled', false); });
		});

		// ---------------- catálogo ----------------
		// v1.24.11: la Caja se actualiza sola cuando en otra pestaña se graba algo del catálogo (productos, precios, agregados,
		// menús...) o de las mesas (avisarCambioDatos en menuFooter). Si hay una ventana abierta (cobro, opciones de un
		// producto...) espera a que se cierre, para no cambiar nada bajo los dedos del cajero. Al volver a esta pestaña
		// después de un rato también se actualiza (cambios hechos desde otro computador).
		var catalogoListo = 0, catalogoPendiente = false, mesasPendiente = false;
		function refrescarDatosCaja() {
			if (!catalogo) return;
			if ($('.modal.in').length || enviando) { setTimeout(refrescarDatosCaja, 1500); return; }
			if (catalogoPendiente) { catalogoPendiente = false; cargarCatalogo(listaActual, true); }
			if (mesasPendiente) { mesasPendiente = false; if (typeof cargarMesas === 'function') cargarMesas(); }
		}
		if (window.alCambiarDatos) window.alCambiarDatos(function (m) {
			if (m.tipo === 'mesas') mesasPendiente = true; else catalogoPendiente = true;
			clearTimeout(refrescarDatosCaja.t);
			refrescarDatosCaja.t = setTimeout(refrescarDatosCaja, 600);   // varias grabaciones seguidas = una sola actualización
		});
		document.addEventListener('visibilitychange', function () {
			if (document.visibilityState !== 'visible' || !catalogo) return;
			if (Date.now() - catalogoListo > 60000) { catalogoPendiente = true; refrescarDatosCaja(); }
		});
		var catalogoPedido = 0;
		function cargarCatalogo(idLista, silencioso) {
			var pedido = ++catalogoPedido;
			return get('catalogo', { id_lprecio: idLista }).done(function (r) {
				if (pedido !== catalogoPedido) return;   // llegó después de otro pedido más nuevo: se ignora
				catalogoListo = Date.now();
				modoCaja = r.modo_caja || 'ambos';
				aplicarModoCaja();
				var primeraVez = !catalogo;
				catalogo = r;
				// v1.25.3: la lista de impresoras del agente se pide al abrir la Caja (la primera impresión no la espera)
				if (primeraVez && impresionLocalActiva() && window.ImpresionLocal && ImpresionLocal.precargar) ImpresionLocal.precargar();
				prepararDelivery();   // 18-11-2026: pestaña Delivery, sectores y repartidores
				if (primeraVez) {
					var $d = $('#cboDocumento').empty();
					// Los electrónicos se habilitan si la empresa emite por Cloud Apps (credenciales y terminal)
					$.each(r.documentos, function (i, d) {
						$d.append($('<option>').val(d.id_tdocumento).prop('disabled', !d.habilitado)
							.text(d.nombre + (d.habilitado ? '' : ' (' + (d.motivo || 'requiere emisión SII') + ')')));
					});
					var disponibles = $.grep(r.documentos, function (d) { return d.habilitado; });
					var $c = $('#cboComuna').empty().append('<option value="0">— Comuna —</option>');
					$.each(r.comunas || [], function (i, c) { $c.append($('<option>').val(c.id_comuna).text(c.nombre_comuna)); });
					var porDefecto = $.grep(disponibles, function (d) { return d.id_tdocumento === r.config.id_tdocumento_defecto; })[0] || disponibles[0];
					if (porDefecto) $d.val(porDefecto.id_tdocumento);
					var $l = $('#cboLista').empty();
					$.each(r.listas_precio, function (i, l) { $l.append($('<option>').val(l.id_lprecio).text(l.nombre_lprecio)); });
				}
				$('#cboLista').val(r.id_lprecio).prop('disabled', listaBloqueada());
				listaActual = r.id_lprecio;
				// Re-precio del carro con la lista nueva
				var quitados = [];
				carro = $.grep(carro, function (l) {
					if (l.enviada) return true;   // lo enviado a la mesa conserva su precio
					var p = producto(l.id_producto);
					if (!p) { quitados.push(l.nombre); return false; }
					l.precio = p.precio;
					l.agregados = $.grep(l.agregados, function (id) { return !!agregado(id); });
					l.extras = $.grep(l.extras || [], function (id) { return $.inArray(id, l.agregados) >= 0; });   // v1.35.0
					return true;
				});
				if (quitados.length) toastr.warning('Sin precio en esta lista: ' + quitados.join(', '));
				renderFamilias();
				renderProductos();
				renderCarro();
				if (favoritosVisible()) renderFavoritos();
				// v1.24.11: la actualización automática no mueve el cursor (el cajero puede estar escribiendo)
				if (silencioso) { toastr.info('Productos y precios actualizados', '', { timeOut: 2000 }); return; }
				$('#buscarProducto').focus();
			});
		}

		function producto(id) {
			return $.grep(catalogo.productos, function (p) { return p.id_producto === id; })[0];
		}
		function agregadosDe(id) {
			return $.grep(catalogo.agregados, function (a) { return a.id_producto_maestro === id; });
		}
		function agregado(id) {
			return $.grep(catalogo.agregados, function (a) { return a.id_agregado === id; })[0];
		}
		/** v1.35.1: lo que cobra un agregado en una línea: con "Extras con cobro" solo los extra (su valor); si no, su valor */
		function valorAgregadoLinea(l, id) {
			var a = agregado(id);
			if (!a) return 0;
			if (!modoExtrasAg(producto(l.id_producto))) return a.valor;
			return l.extras && $.inArray(id, l.extras) >= 0 ? a.valor : 0;
		}
		function mensajesDe(id) {
			return $.grep(catalogo.mensajes, function (m) { return m.id_producto === id; });
		}
		// v1.24.0: menús (Productos > Menú). menuDe(id) = el menú del producto (categorías con sus productos) o null
		function menuDe(id) {
			return $.grep((catalogo && catalogo.menus) || [], function (m) { return m.id_producto === id; })[0] || null;
		}
		/**
		 * v1.36.0: promo con productos fijos (Carta > Promos, o un menú donde cada categoría tiene un solo producto y una
		 * cantidad obligatoria): lo elegido ya está decidido [{id, cantidad}]; null si hay algo que elegir.
		 */
		function menuFijo(id) {
			var m = menuDe(id);
			if (!m || !m.categorias.length) return null;
			var res = [];
			$.each(m.categorias, function (i, c) {
				if (res && c.opciones.length === 1 && c.min > 0 && c.min === c.max) res.push({ id: c.opciones[0].id, cantidad: c.min });
				else res = null;
			});
			return res;
		}
		function opcionMenu(menu, idDet) {
			var r = null;
			$.each(menu ? menu.categorias : [], function (i, c) { $.each(c.opciones, function (j, o) { if (o.id === idDet) r = o; }); });
			return r;
		}
		// Lo que se suma al precio del menú por lo elegido (costo adicional de venta), por cada menú
		function adicionalMenu(l) {
			var menu = l.menu && l.menu.length ? menuDe(l.id_producto) : null, t = 0;
			$.each(menu ? l.menu : [], function (i, e) { var o = opcionMenu(menu, e.id); if (o) t += o.adicional * e.cantidad; });
			return t;
		}
		function tieneOpciones(id) {
			return agregadosDe(id).length > 0 || mensajesDe(id).length > 0 || !!menuDe(id) || !!sushiDe(id);
		}
		// v1.25.0: promos de sushi (Productos > Promos Sushi). sushiDe(id) = la promo (piezas, reglas y catálogo) o null
		function sushiDe(id) {
			return $.grep((catalogo && catalogo.sushis) || [], function (s) { return s.id_producto === id; })[0] || null;
		}
		var SU_LISTA = { base: 'bases', relleno: 'rellenos', envoltorio: 'envoltorios', extra: 'extras', salsa: 'salsas' };   // v1.32.0: salsas
		/** v1.32.0: envoltorios de un grupo (puede llevar varios; lo de antes traía uno solo en "envoltorio") */
		function suEnvs(g) { return g.envoltorios && g.envoltorios.length ? g.envoltorios : (g.envoltorio ? [g.envoltorio] : []); }
		/** v1.32.1: bases de un grupo (puede llevar varias; lo de antes traía una sola en "base") */
		function suBases(g) { return g.bases && g.bases.length ? g.bases : (g.base ? [g.base] : []); }
		/** v1.32.1: con "cada roll se cobra según su envoltorio" la línea de la promo va en $0 (cada roll lleva su precio) */
		function precioBaseSushi(promo, sel, precio) { return promo && promo.precio_envoltorio ? 0 : precio; }
		/**
		 * v1.32.1: precio de cada roll (grupo): el de su envoltorio por roll (el más caro si lleva varios) o, si no tiene,
		 * su parte del precio de la promo (redondeo acumulado). Igual que Mcaja::preciosRollSushi.
		 */
		function preciosRollSushi(promo, grupos) {
			var p = producto(promo.id_producto), precio = p ? p.precio : 0, u = suUnidad(promo);
			var total = Math.max(1, Math.floor(promo.piezas / u)), acum = 0, antes = 0;
			return $.map(grupos || [], function (g) {
				var rolls = Math.floor(g.piezas / u);
				acum += rolls;
				var hasta = Math.round(precio * acum / total), parte = hasta - antes, pe = 0;
				antes = hasta;
				$.each(suEnvs(g), function (j, id) { var o = opcionSushi(promo, 'envoltorio', id); if (o) pe = Math.max(pe, o.precio_promo || 0); });
				return pe > 0 ? pe * rolls : parte;
			});
		}
		// v1.26.0: promo por roll: se arma y se cobra por roll (piezas_rol piezas cada uno). Por dentro los grupos siguen
		// guardando sus piezas (rolls x piezas_rol), igual que el servidor (Mcaja::eleccionSushi)
		function suEsRol(promo) { return !!promo && promo.modo === 'rol'; }
		function suUnidad(promo) { return suEsRol(promo) ? Math.max(1, promo.piezas_rol || 10) : 1; }
		/** Lo que se cobra de un grupo: por roll, sus rolls; por pieza, sus piezas */
		function unidadesGrupoSushi(promo, g) { return g.piezas / suUnidad(promo); }
		/** "3 rolls" / "1 roll" / "30 pz" */
		function textoCantSushi(promo, piezas) {
			if (!suEsRol(promo)) return piezas + ' pz';
			var r = piezas / suUnidad(promo);
			return r + (r === 1 ? ' roll' : ' rolls');
		}
		function opcionSushi(promo, tipo, id) {
			return $.grep(promo ? promo[SU_LISTA[tipo]] : [], function (o) { return o.id === id; })[0] || null;
		}
		/** Valor por pieza de un grupo (base + envoltorio + rellenos) */
		function valorPiezaSushi(promo, g) {
			var t = 0;
			$.each(suBases(g), function (i, id) { var b = opcionSushi(promo, 'base', id); if (b) t += b.valor; });   // v1.32.1: varias bases
			// v1.32.0: varios envoltorios; con precio según el envoltorio su valor no se suma (ya es el precio)
			if (!promo.precio_envoltorio) $.each(suEnvs(g), function (i, id) { var e = opcionSushi(promo, 'envoltorio', id); if (e) t += e.valor; });
			$.each(g.rellenos || [], function (i, id) { var r = opcionSushi(promo, 'relleno', id); if (r) t += r.valor; });
			return t;
		}
		// Lo que se suma al precio de la promo por lo elegido, por cada promo (Mcaja::armarLineas)
		function adicionalSushiSel(promo, sel) {
			var t = 0;
			if (!promo || !sel) return 0;
			if (promo.precio_envoltorio) $.each(preciosRollSushi(promo, sel.grupos), function (i, v) { t += v; });   // v1.32.1
			else $.each(sel.grupos || [], function (i, g) { t += unidadesGrupoSushi(promo, g) * valorPiezaSushi(promo, g); });
			$.each(sel.extras || [], function (i, e) { var o = opcionSushi(promo, 'extra', e.id); if (o) t += o.valor * e.cantidad; });
			$.each(sel.salsas || [], function (i, e) { var o = opcionSushi(promo, 'salsa', e.id); if (o) t += o.valor * e.cantidad; });   // v1.32.0
			return t;
		}
		function adicionalSushi(l) { return l.sushi ? adicionalSushiSel(sushiDe(l.id_producto), l.sushi) : 0; }
		/** Texto de un grupo: "10 PANKO · Pollo, Palta · Base Arroz" */
		function textoGrupoSushi(promo, g) {
			var envs = $.map(suEnvs(g), function (id) { var e = opcionSushi(promo, 'envoltorio', id); return e ? e.nombre : null; });
			var bases = $.map(suBases(g), function (id) { var b = opcionSushi(promo, 'base', id); return b ? b.nombre : null; }).join(' + ');
			var rell = $.map(g.rellenos || [], function (id) { var r = opcionSushi(promo, 'relleno', id); return r ? r.nombre : null; });
			return { titulo: envs.length ? envs.join(' + ') : (bases || rell[0] || ''), rellenos: rell.join(', '), base: bases };
		}
		// Precio unitario con agregados (v1.24.0: o con los adicionales del menú)
		function precioLinea(l) {
			var precio = l.sushi ? precioBaseSushi(sushiDe(l.id_producto), l.sushi, l.precio) : l.precio;   // v1.32.0
			$.each(l.agregados, function (i, id) { precio += valorAgregadoLinea(l, id); });   // v1.35.0: los extra con su precio extra
			return precio + adicionalMenu(l) + adicionalSushi(l);   // v1.25.0
		}

		function renderFamilias() {
			var usadas = {};
			$.each(catalogo.productos, function (i, p) { usadas[p.id_familia] = true; });
			var $f = $('#familias').empty();
			// Favoritos (estrella en la Carta): familia especial -1, solo si hay alguno
			var hayFavoritos = $.grep(catalogo.productos, function (p) { return p.favorito; }).length > 0;
			if (familiaActiva === -1 && !hayFavoritos) familiaActiva = 0;
			$f.append($('<button class="btn btn-sm">').toggleClass('btn-success', familiaActiva === 0).toggleClass('btn-default', familiaActiva !== 0)
				.text('Todos').data('id', 0));
			if (hayFavoritos) {
				$f.append($('<button class="btn btn-sm caja-familia-fav">').toggleClass('btn-success', familiaActiva === -1).toggleClass('btn-default', familiaActiva !== -1)
					.html('<i class="fa fa-star"></i> Favoritos').data('id', -1));
			}
			$.each(catalogo.familias, function (i, f) {
				var id = Number(f.id_familia);
				if (!usadas[id]) return;
				$f.append($('<button class="btn btn-sm">').toggleClass('btn-success', familiaActiva === id).toggleClass('btn-default', familiaActiva !== id)
					.text(f.nombre_familia).data('id', id));
			});
			renderSubfamilias();
		}
		// v1.30.3 (pedido del usuario): subfamilias de la familia elegida (las que tienen productos en venta)
		function subfamiliasCon(productos) {
			var usadas = {};
			$.each(productos, function (i, p) { if (p.id_subfamilia) usadas[p.id_subfamilia] = true; });
			return $.grep(catalogo.subfamilias || [], function (s) { return usadas[s.id_subfamilia]; });
		}
		function renderSubfamilias() {
			var $s = $('#subfamilias').empty();
			// v1.32.1: todas las subfamilias de la familia elegida (con cuántos productos tiene cada una), aunque estén vacías
			var subs = familiaActiva > 0 ? $.grep(catalogo.subfamilias || [], function (sf) { return sf.id_familia === familiaActiva; }) : [];
			var cuenta = {};
			$.each(catalogo.productos, function (i, p) { if (p.id_familia === familiaActiva && p.id_subfamilia) cuenta[p.id_subfamilia] = (cuenta[p.id_subfamilia] || 0) + 1; });
			if (!$.grep(subs, function (s) { return s.id_subfamilia === subfamiliaActiva; }).length) subfamiliaActiva = 0;
			$s.prop('hidden', !subs.length);
			if (!subs.length) return;
			$s.append('<span class="caja-sub-titulo"><i class="fa fa-level-up fa-rotate-90"></i> Subfamilia:</span>');
			$s.append($('<button class="btn btn-sm">').toggleClass('btn-success', !subfamiliaActiva).toggleClass('btn-default', !!subfamiliaActiva).text('Todas').data('id', 0));
			$.each(subs, function (i, s) {
				$s.append($('<button class="btn btn-sm">').toggleClass('btn-success', subfamiliaActiva === s.id_subfamilia).toggleClass('btn-default', subfamiliaActiva !== s.id_subfamilia)
					.text(s.nombre + ' (' + (cuenta[s.id_subfamilia] || 0) + ')').data('id', s.id_subfamilia));
			});
		}

		$('#familias').on('click', 'button', function () {
			familiaActiva = $(this).data('id');
			subfamiliaActiva = 0;
			renderFamilias();
			renderProductos();
		});
		$('#subfamilias').on('click', 'button', function () {
			subfamiliaActiva = $(this).data('id');
			renderSubfamilias();
			renderProductos();
		});

		// Grilla del modal de productos: familia elegida + filtro propio del modal
		function renderProductos() {
			if (!catalogo) return;
			var texto = $.trim($('#filtroProductos').val()).toLowerCase();
			var lista = $.grep(catalogo.productos, function (p) {
				var enFamilia = familiaActiva === -1 ? p.favorito : (!familiaActiva || p.id_familia === familiaActiva);
				if (enFamilia && familiaActiva > 0 && subfamiliaActiva && p.id_subfamilia !== subfamiliaActiva) return false;   // v1.30.3
				return enFamilia && (!texto || p.nombre.toLowerCase().indexOf(texto) >= 0);
			});
			var $c = $('#productos').empty();
			if (!lista.length) {
				$c.append('<div class="caja-vacio"><i class="pe-7s-search"></i>No se encontraron productos</div>');
				return;
			}
			var enVenta = {};
			$.each(carro, function (i, l) { enVenta[l.id_producto] = (enVenta[l.id_producto] || 0) + l.cantidad; });
			var html = '';
			$.each(lista, function (i, p) {
				var n = enVenta[p.id_producto] || 0;
				// Con imagen (cargada en la Carta) la tarjeta la muestra arriba; sin imagen, una inicial de color
				var foto = p.imagen
					? '<span class="foto"><img src="' + esc(p.imagen) + '" alt="" loading="lazy"></span>'
					: '<span class="foto sin-foto">' + esc((p.nombre || '?').charAt(0)) + '</span>';
				html += '<button type="button" class="caja-producto con-foto' + (n ? ' agregado' : '') + '" data-id="' + p.id_producto + '">'
					+ (n ? '<span class="en-venta">' + (esFraccion(p.id_producto) ? fmtCant(n) : n) + '</span>' : '')
					+ foto
					+ '<span class="nombre">' + esc(p.nombre) + '</span>'
					+ (tieneOpciones(p.id_producto) ? '<span class="opciones">+ opciones</span>' : '')
					+ '<span class="precio">' + fmt(p.precio) + '</span></button>';
			});
			$c.html(html);
		}
		$('#filtroProductos').on('input', renderProductos);

		// soloFavoritos: botón ★, abre en Favoritos. Productos y F2 abren en la familia que estaba (salvo Favoritos: Todos)
		function abrirCatalogo(soloFavoritos) {
			if (!catalogo) return;
			if ($('.caja-venta').hasClass('mesa-modo-directa')) return;   // modo venta directa: la mesa de antes no recibe productos nuevos
			if (soloFavoritos === true) {
				if (!$.grep(catalogo.productos, function (p) { return p.favorito; }).length) {
					toastr.info('Aún no hay productos favoritos. Se marcan con la estrella en la Carta.');
					return;
				}
				familiaActiva = -1;
			} else if (familiaActiva === -1) {
				familiaActiva = 0;
			}
			cerrarResultados();
			$('#filtroProductos').val('');
			renderFamilias();
			renderProductos();
			$('#modalProductos').modal('show');
		}
		$('#btnCatalogo').on('click', function () { abrirCatalogo(false); });
		// El botón ★ (en el selector, a la izquierda de Pagar) abre o cierra el panel Favoritos (ver "panel Favoritos" más abajo)
		$('#btnFavoritos').on('click', function () {
			var abrir = !favoritosVisible();
			mostrarPanel(abrir ? 'favoritos' : 'pago');
			// En pantallas angostas la columna derecha queda bajo la venta: se lleva el panel a la vista
			if (abrir && window.innerWidth < 992) $('html, body').animate({ scrollTop: $('#panelFavoritos').offset().top - 10 }, 250);
		});
		// Mesa sin pedido (sin panel lateral): la ★ de la barra de búsqueda abre el panel solo con Favoritos y lo vuelve a cerrar
		$('#btnFavMesa').on('click', function () {
			var abrir = !favoritosVisible();
			mostrarPanel(abrir ? 'favoritos' : 'pago');
			if (abrir && window.innerWidth < 992) $('#panelFavoritos')[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
		});
		$('#modalProductos').on('shown.bs.modal', function () { $('#filtroProductos').focus(); })
			.on('hidden.bs.modal', function () {
				// Producto con opciones elegido en la grilla: su modal se abre cuando este ya cerró
				if (opcionPendiente) { var id = opcionPendiente; opcionPendiente = null; abrirOpciones(id, null); }
				else $('#buscarProducto').focus();
			});
		var opcionPendiente = null;

		// Interruptor "Cerrar al seleccionar" del modal de productos: por computador; apagado por defecto (se eligen varios)
		var CLAVE_CERRAR_AL_ELEGIR = 'cajaWeb.cerrarProductosAlElegir';
		try { $('#chkCerrarAlElegir').prop('checked', localStorage.getItem(CLAVE_CERRAR_AL_ELEGIR) === '1'); } catch (e) {}
		$('#chkCerrarAlElegir').on('change', function () {
			try { localStorage.setItem(CLAVE_CERRAR_AL_ELEGIR, this.checked ? '1' : '0'); } catch (e) {}
		});

		$('#productos').on('click', '.caja-producto', function () {
			var id = Number($(this).data('id'));
			if (tieneOpciones(id)) {
				opcionPendiente = id;
				$('#modalProductos').modal('hide');
				return;
			}
			agregar(id, [], '');
			renderProductos();
			if ($('#chkCerrarAlElegir').prop('checked')) $('#modalProductos').modal('hide');
		});

		// ---------------- buscador con resultados ----------------
		var resultados = [], resultadoActivo = -1;
		function cerrarResultados() {
			resultados = [];
			resultadoActivo = -1;
			$('#resultados').removeClass('visible').empty();
		}
		function nombreFamilia(id) {
			var f = $.grep(catalogo.familias, function (x) { return Number(x.id_familia) === id; })[0];
			return f ? f.nombre_familia : '';
		}
		function renderResultados() {
			var texto = $.trim($('#buscarProducto').val()).toLowerCase();
			if (!catalogo || texto.length < 2) { cerrarResultados(); return; }
			var palabras = texto.split(/\s+/);
			resultados = $.grep(catalogo.productos, function (p) {
				var n = p.nombre.toLowerCase();
				if (p.cbarra && p.cbarra.toLowerCase() === texto) return true;
				return $.grep(palabras, function (w) { return n.indexOf(w) < 0; }).length === 0;
			}).slice(0, 40);
			resultadoActivo = resultados.length ? 0 : -1;
			var $r = $('#resultados').empty().addClass('visible');
			if (!resultados.length) { $r.html('<div class="sin-resultados">No se encontraron productos</div>'); return; }
			$r.html($.map(resultados, function (p, i) {
				return '<div class="caja-resultado' + (i === 0 ? ' activo' : '') + '" data-i="' + i + '">'
					+ '<span class="nombre">' + esc(p.nombre) + (tieneOpciones(p.id_producto) ? ' <small class="text-muted">+ opciones</small>' : '')
					+ '<span class="familia">' + (p.cbarra ? '<span class="cbarra"><i class="fa fa-barcode"></i> ' + esc(p.cbarra) + '</span>' : '') + esc(nombreFamilia(p.id_familia)) + '</span></span>'
					+ '<span class="precio">' + fmt(p.precio) + '</span></div>';
			}).join(''));
		}
		function marcarResultado(i) {
			resultadoActivo = i;
			var $items = $('#resultados .caja-resultado').removeClass('activo');
			var $a = $items.eq(i).addClass('activo');
			if ($a.length) $a[0].scrollIntoView({ block: 'nearest' });
		}
		function elegirResultado(p) {
			cerrarResultados();
			$('#buscarProducto').val('');
			elegir(p.id_producto);
			if (!tieneOpciones(p.id_producto)) $('#buscarProducto').focus();
		}

		$('#buscarProducto').on('input', renderResultados).on('keydown', function (e) {
			if (e.which === 40 || e.which === 38) {
				e.preventDefault();
				if (resultados.length) marcarResultado((resultadoActivo + (e.which === 40 ? 1 : -1) + resultados.length) % resultados.length);
			} else if (e.which === 27) {
				cerrarResultados();
			} else if (e.which === 13) {
				e.preventDefault();
				var texto = $.trim($(this).val()).toLowerCase();
				if (!texto) return;
				// Ingreso rápido "cantidad*código": 3*5 = tres del código 5; 0,350*4 = 0,350 kg del código 4
				var mult = /^(\d+(?:[.,]\d+)?)\s*\*\s*(.+)$/.exec(texto);
				if (mult) {
					var cant = Number(mult[1].replace(',', '.')), cod = $.trim(mult[2]);
					var palabras = cod.split(/\s+/);
					var pm = $.grep(catalogo.productos, function (x) { return x.cbarra && x.cbarra.toLowerCase() === cod; })[0]
						|| $.grep(catalogo.productos, function (x) { var n = x.nombre.toLowerCase(); return $.grep(palabras, function (w) { return n.indexOf(w) < 0; }).length === 0; })[0];
					if (!pm) { toastr.warning('No se encontró el producto "' + cod + '"'); return; }
					if (!(cant > 0)) { toastr.warning('Cantidad no válida'); return; }
					cerrarResultados();
					$(this).val('');
					if (tieneOpciones(pm.id_producto)) { abrirOpciones(pm.id_producto, null); toastr.info('Este producto tiene opciones: ajusta la cantidad en la venta'); return; }
					agregar(pm.id_producto, [], '', cant);
					$(this).focus();
					return;
				}
				// Lector de código de barras: escribe el código completo y termina con Enter
				var porCodigo = $.grep(catalogo.productos, function (p) { return p.cbarra && p.cbarra.toLowerCase() === texto; })[0];
				if (!porCodigo && texto.length < 2) return;
				if (!porCodigo && !resultados.length) renderResultados();
				var p = porCodigo || resultados[resultadoActivo];
				if (p) elegirResultado(p);
				else toastr.warning('No se encontró el producto "' + texto + '"');
			}
		}).on('focus', function () { if ($.trim($(this).val()).length >= 2) renderResultados(); });
		$('#resultados').on('mousedown', '.caja-resultado', function (e) {
			e.preventDefault();
			elegirResultado(resultados[$(this).data('i')]);
		});
		$(document).on('mousedown', function (e) {
			if (!$(e.target).closest('.caja-buscar').length) cerrarResultados();
		}).on('keydown', function (e) {
			// F2: todos los productos (solo en la pestaña Venta, sin otro modal abierto)
			if (e.which === 113 && ($('#tabVenta').is(':visible') || drawerMesaAbierto()) && !$('.modal.in').length) { e.preventDefault(); abrirCatalogo(); }
		});

		function elegir(id) {
			// v1.32.0 (pedido del usuario): promo de sushi fija: entra ya armada, sin preguntar (si su armado sigue sirviendo)
			var fija = sushiDe(id) ? armadoFijoSushi(sushiDe(id)) : null;
			if (fija) { agregar(id, [], '', undefined, {}, [], fija); toastr.success('Promo fija agregada (para cambiarla en esta venta, toca su nombre)'); return; }
			// v1.36.0: promo normal (todo fijo, sin agregados ni mensajes): entra al tiro
			var mf = menuFijo(id);
			if (mf && !agregadosDe(id).length && !mensajesDe(id).length) { agregar(id, [], '', undefined, {}, mf); return; }
			if (tieneOpciones(id)) abrirOpciones(id, null);
			else if (!mesa && pideValorOPeso(id)) abrirPesable(id);   // a granel en venta directa: por valor o por peso
			else agregar(id, [], '');
		}

