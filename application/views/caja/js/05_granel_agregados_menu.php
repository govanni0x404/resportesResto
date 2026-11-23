		// ---------------- producto a granel (12-11-2026) ----------------
		// Fraccionable + "Pedir cantidad en venta" (Carta): en venta directa se pregunta cuánto, por valor (lo que marca la
		// balanza, en pesos) o por peso. Los números van con coma de miles y punto decimal; los campos son de texto (no
		// type=number, que falla en algunos navegadores). El total es el mismo que calcula el carro y el servidor:
		// redondeo de precio × cantidad, con la cantidad en gramos (3 decimales).
		function pideValorOPeso(id) { var p = producto(id); return !!(p && p.fraccion && p.pedir_cantidad); }
		var pesable = null;   // {id, precio, unidad, modo: 'valor' | 'peso', cantidad}
		var PES_MAX = 9999;   // misma cantidad máxima que acepta el servidor
		function milesComa(n, dec) {
			var partes = (dec ? Number(n).toFixed(dec) : String(Math.round(n))).split('.');
			return partes[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',') + (partes[1] ? '.' + partes[1] : '');
		}
		function pesoTexto(q) { return milesComa(q, 3); }
		function pesosTexto(n) { return '$' + milesComa(n, 0); }
		// "1,234.5" -> 1234.5 (la coma es solo separador de miles); vacío o inválido -> NaN
		function numeroDe(texto) {
			var t = String(texto == null ? '' : texto).replace(/,/g, '').trim();
			if (t === '' || t === '.' || !/^\d*\.?\d*$/.test(t)) return NaN;
			return Number(t);
		}
		// Formatea mientras se escribe: solo dígitos y un punto, miles con coma, máximo `dec` decimales; conserva el cursor
		function formatearEntrada(el, dec) {
			var v = el.value, pos = el.selectionStart == null ? v.length : el.selectionStart;
			var antes = v.slice(0, pos).replace(/[^0-9.]/g, '').length;
			var limpio = v.replace(/[^0-9.]/g, '');
			if (!dec) limpio = limpio.replace(/\./g, '');
			else {
				var i = limpio.indexOf('.');
				if (i >= 0) limpio = limpio.slice(0, i + 1) + limpio.slice(i + 1).replace(/\./g, '').slice(0, dec);
			}
			var partes = limpio.split('.');
			var ent = partes[0].replace(/^0+(?=\d)/, '');
			if (partes.length > 1 && ent === '') ent = '0';
			var txt = ent.replace(/\B(?=(\d{3})+(?!\d))/g, ',') + (partes.length > 1 ? '.' + partes[1] : '');
			if (txt !== v) {
				el.value = txt;
				var n = 0, k = 0;
				while (k < txt.length && n < antes) { if (/[0-9.]/.test(txt.charAt(k))) n++; k++; }
				try { el.setSelectionRange(k, k); } catch (e) { /* sin cursor */ }
			}
		}
		function totalPesable(cantidad) { return Math.round(pesable.precio * cantidad); }   // igual que lineasBrutas()
		// Por valor: el peso (en gramos) cuyo total queda más cerca del monto pedido (si empatan, el menor)
		function pesoParaMonto(monto) {
			var g = Math.floor(monto / pesable.precio * 1000), mejor = null;
			$.each([g, g + 1], function (i, gr) {
				if (gr < 1) return;
				var q = gr / 1000, dif = Math.abs(totalPesable(q) - monto);
				if (!mejor || dif < mejor.dif) mejor = { q: q, dif: dif };
			});
			return mejor ? mejor.q : 0;
		}
		function modoPesable(modo) {
			if (modo === 'valor' && !(pesable.precio > 0)) modo = 'peso';   // sin precio solo se puede por peso
			pesable.modo = modo;
			$('#modalPesable .pes-modo button').each(function () { $(this).toggleClass('activo', $(this).data('modo') === modo).attr('aria-selected', $(this).data('modo') === modo); });
			$('#modalPesable .pes-modo button[data-modo="valor"]').prop('disabled', !(pesable.precio > 0));
			var porValor = modo === 'valor';
			$('#pesableEtiqueta').text(porValor ? 'Valor a vender (lo que marca la balanza)' : 'Peso a vender');
			$('#pesablePre').text(porValor ? '$' : '');
			$('#pesableSuf').text(porValor ? '' : pesable.unidad);
			$('#pesableEntrada').toggleClass('sin-unidad', porValor);
			$('#txtPesable').attr('placeholder', porValor ? '0' : '0.000').val('');
			$('#pesableResEtiqueta').text(porValor ? 'Peso' : 'Total a cobrar');
			calcularPesable();
			setTimeout(function () { $('#txtPesable').focus(); }, 0);
		}
		function calcularPesable() {
			var porValor = pesable.modo === 'valor', txt = $.trim($('#txtPesable').val());
			var n = numeroDe(txt), msg = '', nota = '';
			pesable.cantidad = 0;
			if (txt !== '' && !(n > 0)) msg = porValor ? 'Ingresa un valor mayor a cero' : 'Ingresa un peso mayor a cero';
			else if (n > 0 && porValor) {
				var q = pesoParaMonto(n);
				if (!(q > 0)) msg = 'El valor no alcanza para 1 gramo (' + pesosTexto(pesable.precio) + ' por ' + pesable.unidad + ')';
				else if (q > PES_MAX) msg = 'El valor es demasiado alto (máximo ' + milesComa(PES_MAX, 0) + ' ' + pesable.unidad + ')';
				else {
					pesable.cantidad = q;
					var total = totalPesable(q);
					if (total !== Math.round(n)) nota = 'Se cobra ' + pesosTexto(total) + ': el peso se redondea al gramo.';
				}
			} else if (n > 0) {
				if (n < 0.001) msg = 'El peso mínimo es 0.001 ' + pesable.unidad;
				else if (n > PES_MAX) msg = 'El peso es demasiado alto (máximo ' + milesComa(PES_MAX, 0) + ' ' + pesable.unidad + ')';
				else pesable.cantidad = Math.round(n * 1000) / 1000;
			}
			$('#pesableEntrada').toggleClass('pes-error', !!msg);
			$('#pesableMsg').text(msg);
			$('#pesableNota').text(nota);
			$('#pesableRes').text(!pesable.cantidad ? '—'
				: (porValor ? pesoTexto(pesable.cantidad) + ' ' + pesable.unidad : pesosTexto(totalPesable(pesable.cantidad))));
			$('#btnConfirmarPesable').prop('disabled', !(pesable.cantidad > 0)).html('<i class="fa fa-check"></i> Agregar'
				+ (pesable.cantidad > 0 ? ' ' + pesosTexto(totalPesable(pesable.cantidad)) : ''));
		}
		function abrirPesable(id) {
			var p = producto(id);
			pesable = { id: id, precio: Number(p.precio) || 0, unidad: (p.umedida || 'kg').toLowerCase(), modo: 'valor', cantidad: 0 };
			$('#pesableTitulo').text(p.nombre);
			$('#pesablePrecio').text(pesosTexto(pesable.precio) + ' por ' + pesable.unidad);
			modoPesable('valor');
			$('#modalPesable').modal('show');
		}
		function confirmarPesable() {
			if (!pesable || !(pesable.cantidad > 0)) return;
			var id = pesable.id, q = pesable.cantidad;
			$('#modalPesable').modal('hide');
			agregar(id, [], '', q);
		}
		$('#modalPesable .pes-modo').on('click', 'button', function () {
			if (!$(this).prop('disabled')) modoPesable($(this).data('modo'));
		});
		$('#txtPesable').on('keydown', function (e) {
			if (e.which === 13) { e.preventDefault(); confirmarPesable(); return; }
			// La coma de miles la pone el campo. Una coma escrita (o la tecla decimal del teclado numérico) se toma como el
			// punto decimal en el peso; en el valor no hay decimales.
			if (e.key === ',' || e.key === '.' || e.code === 'NumpadDecimal') {
				e.preventDefault();
				if (pesable.modo === 'valor' || this.value.indexOf('.') >= 0) return;
				var ini = this.selectionStart == null ? this.value.length : this.selectionStart, fin = this.selectionEnd == null ? ini : this.selectionEnd;
				this.value = this.value.slice(0, ini) + '.' + this.value.slice(fin);
				try { this.setSelectionRange(ini + 1, ini + 1); } catch (er) { /* sin cursor */ }
				$(this).trigger('input');
			}
		}).on('input', function (e) {
			// Teclados de celular (sin keydown útil): una coma recién escrita en el peso es el punto decimal
			var oe = e.originalEvent || {}, c = this.selectionStart;
			if (pesable.modo === 'peso' && oe.data === ',' && c > 0 && this.value.charAt(c - 1) === ',') {
				var yaTiene = this.value.indexOf('.') >= 0;
				this.value = this.value.slice(0, c - 1) + (yaTiene ? '' : '.') + this.value.slice(c);
				try { this.setSelectionRange(yaTiene ? c - 1 : c, yaTiene ? c - 1 : c); } catch (er) { /* sin cursor */ }
			}
			formatearEntrada(this, pesable.modo === 'valor' ? 0 : 3);
			calcularPesable();
		});
		$('#btnConfirmarPesable').on('click', confirmarPesable);
		$('#modalPesable').on('shown.bs.modal', function () { $('#txtPesable').focus(); })
			.on('hidden.bs.modal', function () { pesable = null; $('#buscarProducto').focus(); });

		// ---------------- agregados y mensajes ----------------
		function abrirOpciones(id, indice) {
			if (sushiDe(id)) { abrirSushi(id, indice); return; }   // v1.25.0
			var p = producto(id);
			var linea = indice === null ? null : carro[indice];
			opcionEdicion = { id_producto: id, indice: indice };
			$('#opcionesTitulo').text(p.nombre);
			$('#btnConfirmarOpciones').text(linea ? 'Guardar' : 'Agregar');

			// v1.24.0: menú: por cada categoría sus productos, con la cantidad elegida (lo de la línea al editarla)
			opcionesMenu = menuDe(id);
			$('#opcionesMenu').toggle(!!opcionesMenu);
			menuElegido = {};
			if (opcionesMenu && linea && linea.menu) $.each(linea.menu, function (i, e) { menuElegido[e.id] = e.cantidad; });
			pintarMenuOpciones();

			var ags = agregadosDe(id);
			// v1.24.3: sin límite de agregados por producto (pedido del usuario)
			$('#opcionesAgregados').toggle(ags.length > 0);
			// v1.24.9: con muchos agregados (o un menú) la ventana se ensancha y van en cuadrícula hacia el lado
			$('#modalOpciones').toggleClass('opciones-ancho', ags.length > 4 || mensajesDe(id).length > 6 || !!opcionesMenu);
			// v1.30.3: máximo de agregados de este producto (Carta > Agregados); 0 = sin límite
			var maxAg = Number(p.max_agregados) || 0;
			$('#opcionesMaxAgregados').text(maxAg > 0 && ags.length > maxAg ? 'Elige hasta ' + maxAg : '');
			$('#listaAgregados').html($.map(ags, function (a) {
				var marcado = linea && $.inArray(a.id_agregado, linea.agregados) >= 0;
				// 19-11-2026: cada agregado marcado puede llevar su propio mensaje para la comanda (ej. Palta: sin sal)
				var notaAg = linea && linea.notas_agregados ? (linea.notas_agregados[a.id_agregado] || '') : '';
				// Estructura del tema (checkbox-success): el input va antes del label
				return '<div class="ag-item' + (marcado ? ' marcado' : '') + '"><div class="checkbox checkbox-success"><input type="checkbox" class="chk-agregado" id="ag' + a.id_agregado + '" value="' + a.id_agregado + '"' + (marcado ? ' checked' : '') + '>'
					+ '<label for="ag' + a.id_agregado + '">' + esc(a.nombre) + (a.valor ? ' <span class="valor">+' + fmt(a.valor) + '</span>' : '') + '</label></div>'
					+ '<input type="text" class="form-control input-sm ag-nota" data-id="' + a.id_agregado + '" maxlength="60" placeholder="Mensaje para ' + esc(a.nombre) + ' (opcional)" value="' + esc(notaAg) + '"' + (marcado ? '' : ' style="display:none"') + '></div>';
			}).join(''));
			limitarAgregados();

			// La nota guarda las indicaciones elegidas + el texto libre, separados por coma
			var partes = linea && linea.nota ? linea.nota.split(', ') : [];
			var msgs = mensajesDe(id);
			$('#opcionesMensajes').toggle(msgs.length > 0);
			$('#listaMensajes').html($.map(msgs, function (m, i) {
				var marcado = $.inArray(m.mensaje, partes) >= 0;
				return '<div class="checkbox checkbox-success"><input type="checkbox" class="chk-mensaje" id="msg' + i + '" data-i="' + i + '"' + (marcado ? ' checked' : '') + '>'
					+ '<label for="msg' + i + '">' + esc(m.mensaje) + '</label></div>';
			}).join(''));
			var textos = $.map(msgs, function (m) { return m.mensaje; });
			$('#txtNotaProducto').val($.grep(partes, function (x) { return $.inArray(x, textos) < 0; }).join(', '));
			precioOpciones();
			$('#modalOpciones').modal('show');
		}

		function precioOpciones() {
			var p = producto(opcionEdicion.id_producto);
			var precio = p.precio;
			$('#listaAgregados .chk-agregado:checked').each(function () { precio += agregado(Number(this.value)).valor; });
			// v1.24.0: adicionales de lo elegido del menú
			if (opcionesMenu) $.each(menuElegido, function (idDet, q) { var o = opcionMenu(opcionesMenu, Number(idDet)); if (o) precio += o.adicional * q; });
			$('#opcionesPrecio').text(fmt(precio));
		}

		// ---- v1.24.0: armar el menú ----
		var opcionesMenu = null, menuElegido = {};   // menú del producto abierto en Opciones y {id_detalle: cantidad}
		function reglaCategoria(c) {
			if (c.max === 1) return 'Elige 1';
			if (c.min > 0 && c.max > 0) return c.min === c.max ? 'Elige ' + c.max : 'Elige de ' + c.min + ' a ' + c.max;
			if (c.max > 0) return 'Hasta ' + c.max;
			if (c.min > 0) return 'Mínimo ' + c.min;
			return 'Opcional';
		}
		function elegidosEn(c) {
			var n = 0;
			$.each(c.opciones, function (i, o) { n += menuElegido[o.id] || 0; });
			return n;
		}
		function pintarMenuOpciones() {
			if (!opcionesMenu) { $('#listaMenu').empty(); return; }
			$('#listaMenu').html($.map(opcionesMenu.categorias, function (c) {
				var n = elegidosEn(c), falta = c.estricto && n < c.min, completa = (c.max > 0 && n >= c.max) || (!c.max && n >= Math.max(1, c.min));
				return '<div class="menu-cat' + (falta ? ' falta' : (completa ? ' completa' : '')) + '" data-cat="' + c.id + '">'
					+ '<div class="menu-cat-titulo"><span>' + esc(c.nombre) + '</span><small>' + esc(reglaCategoria(c)) + (c.estricto ? ' (obligatorio)' : '') + ' · ' + n + (c.max ? '/' + c.max : '') + '</small></div>'
					+ $.map(c.opciones, function (o) {
						var q = menuElegido[o.id] || 0;
						return '<div class="menu-op' + (q ? ' elegida' : '') + '" data-id="' + o.id + '" data-cat="' + c.id + '">'
							+ '<span class="nombre">' + esc(o.nombre) + (o.adicional ? ' <span class="adicional">+' + fmt(o.adicional) + '</span>' : '') + '</span>'
							+ (c.max === 1 ? '<i class="fa ' + (q ? 'fa-dot-circle-o' : 'fa-circle-o') + '"></i>'
								: '<span class="menu-cant"><button type="button" class="btn btn-default btn-xs menu-menos"' + (q ? '' : ' disabled') + '><i class="fa fa-minus"></i></button><b>' + q + '</b>'
									+ '<button type="button" class="btn btn-default btn-xs menu-mas"><i class="fa fa-plus"></i></button></span>')
							+ '</div>';
					}).join('') + '</div>';
			}).join(''));
			precioOpciones();
		}
		function categoriaMenu(idCat) {
			return $.grep(opcionesMenu ? opcionesMenu.categorias : [], function (c) { return c.id === idCat; })[0];
		}
		// Elegir: en una categoría de "elige 1" se cambia la elección; en las demás se suma, sin pasar del máximo
		$('#listaMenu').on('click', '.menu-op', function (e) {
			var c = categoriaMenu(Number($(this).data('cat'))), id = Number($(this).data('id'));
			if (!c) return;
			var menos = $(e.target).closest('.menu-menos').length > 0;
			if (c.max === 1) {
				var ya = !!menuElegido[id];
				$.each(c.opciones, function (i, o) { delete menuElegido[o.id]; });
				if (!ya) menuElegido[id] = 1;
			} else if (menos) {
				if (menuElegido[id] > 1) menuElegido[id]--; else delete menuElegido[id];
			} else {
				if (c.max > 0 && elegidosEn(c) >= c.max) { toastr.warning('En ' + c.nombre + ' se pueden elegir hasta ' + c.max); return; }
				menuElegido[id] = (menuElegido[id] || 0) + 1;
			}
			pintarMenuOpciones();
		});
		/** Lo elegido del menú [{id, cantidad}] en el orden del menú; null si falta algo obligatorio (avisa) */
		function eleccionMenuOpciones() {
			if (!opcionesMenu) return [];
			var res = [], error = null;
			$.each(opcionesMenu.categorias, function (i, c) {
				var n = elegidosEn(c);
				if (!error && c.estricto && n < c.min) error = 'En ' + c.nombre + ' hay que elegir ' + (c.min === 1 ? '1 producto' : 'al menos ' + c.min);
				if (!error && c.max > 0 && n > c.max) error = 'En ' + c.nombre + ' se pueden elegir hasta ' + c.max;
				$.each(c.opciones, function (j, o) { if (menuElegido[o.id]) res.push({ id: o.id, cantidad: menuElegido[o.id] }); });
			});
			if (error) { toastr.warning(error); return null; }
			if (!res.length) { toastr.warning('Elige los productos del menú'); return null; }
			return res;
		}

		// v1.24.9: tocar cualquier parte de la tarjeta del agregado lo marca o desmarca (menos su mensaje)
		$('#listaAgregados').on('click', '.ag-item', function (e) {
			if ($(e.target).closest('input, label, .ag-nota').length) return;
			if ($(this).hasClass('bloqueado')) { toastr.warning('Este producto permite elegir hasta ' + maxAgregadosAbierto() + ' agregados: quita uno para elegir otro'); return; }
			$(this).find('.chk-agregado').trigger('click');
		});
		// v1.30.3: con el máximo del producto alcanzado, los demás agregados quedan sin poder marcarse
		function maxAgregadosAbierto() { var p = opcionEdicion ? producto(opcionEdicion.id_producto) : null; return p ? Number(p.max_agregados) || 0 : 0; }
		function limitarAgregados() {
			var max = maxAgregadosAbierto(), n = $('#listaAgregados .chk-agregado:checked').length, lleno = max > 0 && n >= max;
			$('#listaAgregados .chk-agregado').each(function () {
				var bloquear = lleno && !this.checked;
				$(this).prop('disabled', bloquear).closest('.ag-item').toggleClass('bloqueado', bloquear);
			});
			if (max > 0 && $('#listaAgregados .chk-agregado').length > max) $('#opcionesMaxAgregados').text('Elige hasta ' + max + ' · ' + n + '/' + max);
		}
		$('#listaAgregados').on('change', '.chk-agregado', function () {
			var max = maxAgregadosAbierto();
			if (this.checked && max > 0 && $('#listaAgregados .chk-agregado:checked').length > max) {
				this.checked = false;
				toastr.warning('Este producto permite elegir hasta ' + max + ' agregado' + (max === 1 ? '' : 's'));
				return;
			}
			$(this).closest('.ag-item').toggleClass('marcado', this.checked).find('.ag-nota').toggle(this.checked);
			limitarAgregados();
			precioOpciones();
		});

		$('#btnConfirmarOpciones').on('click', function () {
			var id = opcionEdicion.id_producto;
			var menuSel = eleccionMenuOpciones();   // v1.24.0
			if (menuSel === null) return;
			var ags = $('#listaAgregados .chk-agregado:checked').map(function () { return Number(this.value); }).get();
			var maxAg = Number((producto(id) || {}).max_agregados) || 0;   // v1.30.3
			if (maxAg > 0 && ags.length > maxAg) { toastr.warning('Este producto permite elegir hasta ' + maxAg + ' agregado' + (maxAg === 1 ? '' : 's')); return; }
			var msgs = mensajesDe(id);
			var partes = $('#listaMensajes .chk-mensaje:checked').map(function () { return msgs[$(this).data('i')].mensaje; }).get();
			var libre = $.trim($('#txtNotaProducto').val());
			if (libre) partes.push(libre);
			var nota = partes.join(', ');
			var notasAg = {};
			$('#listaAgregados .chk-agregado:checked').each(function () {
				var t = $.trim($(this).closest('.ag-item').find('.ag-nota').val());
				if (t) notasAg[this.value] = t.slice(0, 60);
			});
			if (opcionEdicion.indice === null) {
				agregar(id, ags, nota, undefined, notasAg, menuSel);
			} else {
				carro[opcionEdicion.indice].agregados = ags;
				carro[opcionEdicion.indice].nota = nota;
				carro[opcionEdicion.indice].notas_agregados = notasAg;
				carro[opcionEdicion.indice].menu = menuSel;
				renderCarro();
			}
			$('#modalOpciones').modal('hide');
		});

		$('#modalOpciones').on('shown.bs.modal', function () { $('#txtNotaProducto').focus(); })
			.on('keydown', '#txtNotaProducto, .ag-nota', function (e) { if (e.which === 13) $('#btnConfirmarOpciones').click(); })
			.on('hidden.bs.modal', function () { $('#buscarProducto').focus(); });

