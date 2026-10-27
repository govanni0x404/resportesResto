<script>
	(function () {
		var base = "<?php echo base_url(); ?>index.php/Caja/";
		var catalogo = null;
		var carro = [];          // [{id_producto, nombre, precio, cantidad, agregados: [id_agregado], nota}]
		var cliente = null;      // {id_cliente, nombre_cliente, rut}
		var tipoDesc = 'monto';
		var familiaActiva = 0;
		var pagos = [];          // [{id_fpago, monto}]
		var resumen = null;
		var enviando = false;
		var propina = 0;
		var opcionEdicion = null; // {id_producto, indice} mientras el modal de opciones está abierto

		// ---------------- utilidades ----------------
		function fmt(n) {
			n = Math.round(Number(n) || 0);
			return (n < 0 ? '-$' : '$') + Math.abs(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
		}
		function esc(s) {
			return $('<div>').text(s == null ? '' : String(s)).html();
		}
		function fmtCant(n) {
			return String(Math.round(n * 1000) / 1000).replace('.', ',');
		}
		function error(xhr) {
			var msg = (xhr && xhr.responseJSON && xhr.responseJSON.error) || 'No se pudo completar la operación';
			if (xhr && xhr.status === 401) {
				swal({ title: 'Sesión expirada', text: msg, type: 'warning' }, function () { location.href = "<?php echo base_url(); ?>"; });
				return;
			}
			swal('Error', msg, 'error');
		}
		function get(accion, datos) {
			return $.ajax({ url: base + accion, data: datos, dataType: 'json', cache: false }).fail(error);
		}
		function post(accion, datos) {
			return $.ajax({ url: base + accion, type: 'POST', data: datos, dataType: 'json' }).fail(error);
		}
		function fpago(id) {
			return $.grep(catalogo.formas_pago, function (f) { return f.id_fpago === id; })[0];
		}
		function documentoActual() {
			var id = Number($('#cboDocumento').val());
			return $.grep(catalogo.documentos, function (d) { return d.id_tdocumento === id; })[0];
		}

		// ---------------- impresión ----------------
		// Preferencia por computador (la impresora es de cada caja). Por defecto se imprime, como el POS.
		var CLAVE_IMPRIMIR = 'cajaWeb.imprimirTicket';
		function imprimeTicket() {
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
		function imprimir(url) {
			$('#frameImpresion').remove();
			$('<iframe id="frameImpresion" class="caja-frame-impresion" aria-hidden="true">')
				.attr('src', url + (url.indexOf('?') < 0 ? '?' : '&') + 'imprimir=1').appendTo('body');
		}

		// ---------------- estado de caja ----------------
		function cargarEstado() {
			return get('estado').done(function (r) {
				resumen = r.caja;
				if (!resumen) {
					$('#panelCaja').hide();
					$('#panelApertura').show();
					$('#infoCaja').text('Sin caja abierta');
					var sugerido = r.fondo_sugerido;
					$('#fondoInicial').val(sugerido === null || sugerido === undefined ? 0 : sugerido);
					$('#ayudaFondo').toggle(sugerido !== null && sugerido !== undefined);
					$('#fondoInicial').focus();
					return;
				}
				$('#panelApertura').hide();
				$('#panelCaja').show();
				$('#infoCaja').text('Caja N° ' + resumen.id_caja + ' abierta');
				$('#estCaja').text('N° ' + resumen.id_caja);
				$('#estApertura').text(resumen.apertura);
				$('#estFondo').text(fmt(resumen.fondo));
				$('#estVentas').text(fmt(resumen.ventas + resumen.devoluciones) + ' (' + resumen.cantidad_ventas + ')'
					+ (resumen.cantidad_devoluciones ? ' · devol. ' + fmt(resumen.devoluciones) : ''));
				if (!catalogo) cargarCatalogo(0);
			});
		}

		$('#btnAbrirCaja').on('click', function () {
			var fondo = Number($('#fondoInicial').val());
			if (!(fondo >= 0)) { swal('Atención', 'Ingresa un fondo válido', 'warning'); return; }
			var $b = $(this).prop('disabled', true);
			post('abrir', { fondo: Math.round(fondo) }).done(function () {
				toastr.success('Caja abierta');
				cargarEstado();
			}).always(function () { $b.prop('disabled', false); });
		});

		// ---------------- catálogo ----------------
		function cargarCatalogo(idLista) {
			return get('catalogo', { id_lprecio: idLista }).done(function (r) {
				var primeraVez = !catalogo;
				catalogo = r;
				if (primeraVez) {
					var $d = $('#cboDocumento').empty();
					// Los electrónicos se habilitan si la empresa emite por Cloud Apps (credenciales y terminal)
					$.each(r.documentos, function (i, d) {
						$d.append($('<option>').val(d.id_tdocumento).prop('disabled', !d.habilitado)
							.text(d.nombre + (d.habilitado ? '' : ' (requiere emisión SII)')));
					});
					var disponibles = $.grep(r.documentos, function (d) { return d.habilitado; });
					var $c = $('#cboComuna').empty().append('<option value="0">— Comuna —</option>');
					$.each(r.comunas || [], function (i, c) { $c.append($('<option>').val(c.id_comuna).text(c.nombre_comuna)); });
					var porDefecto = $.grep(disponibles, function (d) { return d.id_tdocumento === r.config.id_tdocumento_defecto; })[0] || disponibles[0];
					if (porDefecto) $d.val(porDefecto.id_tdocumento);
					var $l = $('#cboLista').empty();
					$.each(r.listas_precio, function (i, l) { $l.append($('<option>').val(l.id_lprecio).text(l.nombre_lprecio)); });
				}
				$('#cboLista').val(r.id_lprecio);
				// Re-precio del carro con la lista nueva
				var quitados = [];
				carro = $.grep(carro, function (l) {
					var p = producto(l.id_producto);
					if (!p) { quitados.push(l.nombre); return false; }
					l.precio = p.precio;
					l.agregados = $.grep(l.agregados, function (id) { return !!agregado(id); });
					return true;
				});
				if (quitados.length) toastr.warning('Sin precio en esta lista: ' + quitados.join(', '));
				renderFamilias();
				renderProductos();
				renderCarro();
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
		function mensajesDe(id) {
			return $.grep(catalogo.mensajes, function (m) { return m.id_producto === id; });
		}
		function tieneOpciones(id) {
			return agregadosDe(id).length > 0 || mensajesDe(id).length > 0;
		}
		// Precio unitario con agregados
		function precioLinea(l) {
			var precio = l.precio;
			$.each(l.agregados, function (i, id) { var a = agregado(id); if (a) precio += a.valor; });
			return precio;
		}

		function renderFamilias() {
			var usadas = {};
			$.each(catalogo.productos, function (i, p) { usadas[p.id_familia] = true; });
			var $f = $('#familias').empty();
			$f.append($('<button class="btn btn-sm">').toggleClass('btn-success', familiaActiva === 0).toggleClass('btn-default', familiaActiva !== 0)
				.text('Todos').data('id', 0));
			$.each(catalogo.familias, function (i, f) {
				var id = Number(f.id_familia);
				if (!usadas[id]) return;
				$f.append($('<button class="btn btn-sm">').toggleClass('btn-success', familiaActiva === id).toggleClass('btn-default', familiaActiva !== id)
					.text(f.nombre_familia).data('id', id));
			});
		}

		$('#familias').on('click', 'button', function () {
			familiaActiva = $(this).data('id');
			renderFamilias();
			renderProductos();
		});

		function renderProductos() {
			var texto = $.trim($('#buscarProducto').val()).toLowerCase();
			var lista = $.grep(catalogo.productos, function (p) {
				if (texto) return p.nombre.toLowerCase().indexOf(texto) >= 0 || (p.cbarra && p.cbarra.toLowerCase() === texto);
				return !familiaActiva || p.id_familia === familiaActiva;
			});
			var $c = $('#productos').empty();
			if (!lista.length) {
				$c.append('<div class="caja-vacio"><i class="pe-7s-search"></i>No se encontraron productos</div>');
				return;
			}
			var html = '';
			$.each(lista.slice(0, 300), function (i, p) {
				html += '<button type="button" class="caja-producto" data-id="' + p.id_producto + '">'
					+ '<span class="nombre">' + esc(p.nombre) + '</span>'
					+ (tieneOpciones(p.id_producto) ? '<span class="opciones">+ opciones</span>' : '')
					+ '<span class="precio">' + fmt(p.precio) + '</span></button>';
			});
			$c.html(html);
		}

		$('#buscarProducto').on('input', renderProductos).on('keydown', function (e) {
			if (e.which !== 13) return;
			e.preventDefault();
			var texto = $.trim($(this).val()).toLowerCase();
			if (!texto) return;
			// Lector de código de barras: termina con Enter
			var porCodigo = $.grep(catalogo.productos, function (p) { return p.cbarra && p.cbarra.toLowerCase() === texto; });
			var visibles = $.grep(catalogo.productos, function (p) { return p.nombre.toLowerCase().indexOf(texto) >= 0; });
			var p = porCodigo[0] || (visibles.length === 1 ? visibles[0] : null);
			if (p) {
				elegir(p.id_producto);
				$(this).val('');
				renderProductos();
			} else if (!visibles.length) {
				toastr.warning('No se encontró el producto "' + texto + '"');
			}
		});

		$('#productos').on('click', '.caja-producto', function () {
			elegir(Number($(this).data('id')));
		});

		function elegir(id) {
			if (tieneOpciones(id)) abrirOpciones(id, null);
			else agregar(id, [], '');
		}

		// ---------------- agregados y mensajes ----------------
		function abrirOpciones(id, indice) {
			var p = producto(id);
			var linea = indice === null ? null : carro[indice];
			opcionEdicion = { id_producto: id, indice: indice };
			$('#opcionesTitulo').text(p.nombre);
			$('#btnConfirmarOpciones').text(linea ? 'Guardar' : 'Agregar');

			var ags = agregadosDe(id);
			var max = catalogo.config.max_agregados;
			$('#opcionesAgregados').toggle(ags.length > 0);
			$('#opcionesMaxAgregados').text(max > 0 ? '(máximo ' + max + ')' : '');
			$('#listaAgregados').html($.map(ags, function (a) {
				var marcado = linea && $.inArray(a.id_agregado, linea.agregados) >= 0;
				// Estructura del tema (checkbox-success): el input va antes del label
				return '<div class="checkbox checkbox-success"><input type="checkbox" class="chk-agregado" id="ag' + a.id_agregado + '" value="' + a.id_agregado + '"' + (marcado ? ' checked' : '') + '>'
					+ '<label for="ag' + a.id_agregado + '">' + esc(a.nombre) + (a.valor ? ' <span class="valor">+' + fmt(a.valor) + '</span>' : '') + '</label></div>';
			}).join(''));

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
			$('#opcionesPrecio').text(fmt(precio));
		}

		$('#listaAgregados').on('change', '.chk-agregado', function () {
			var max = catalogo.config.max_agregados;
			if (this.checked && max > 0 && $('#listaAgregados .chk-agregado:checked').length > max) {
				this.checked = false;
				toastr.warning('Máximo ' + max + ' agregado(s) por producto');
			}
			precioOpciones();
		});

		$('#btnConfirmarOpciones').on('click', function () {
			var id = opcionEdicion.id_producto;
			var ags = $('#listaAgregados .chk-agregado:checked').map(function () { return Number(this.value); }).get();
			var msgs = mensajesDe(id);
			var partes = $('#listaMensajes .chk-mensaje:checked').map(function () { return msgs[$(this).data('i')].mensaje; }).get();
			var libre = $.trim($('#txtNotaProducto').val());
			if (libre) partes.push(libre);
			var nota = partes.join(', ');
			if (opcionEdicion.indice === null) {
				agregar(id, ags, nota);
			} else {
				carro[opcionEdicion.indice].agregados = ags;
				carro[opcionEdicion.indice].nota = nota;
				renderCarro();
			}
			$('#modalOpciones').modal('hide');
		});

		$('#modalOpciones').on('shown.bs.modal', function () { $('#txtNotaProducto').focus(); })
			.on('keydown', '#txtNotaProducto', function (e) { if (e.which === 13) $('#btnConfirmarOpciones').click(); })
			.on('hidden.bs.modal', function () { $('#buscarProducto').focus(); });

		$('#cboLista').on('change', function () { cargarCatalogo(Number($(this).val())); });
		$('#cboDocumento').on('change', function () {
			var d = documentoActual();
			if (d && d.requiere_cliente && !cliente) toastr.info(d.nombre + ' requiere seleccionar un cliente');
		});

		// ---------------- carro ----------------
		function agregar(id, agregados, nota) {
			var p = producto(id);
			if (!p) return;
			agregados = agregados.slice().sort(function (a, b) { return a - b; });
			// Solo se suma a una línea existente si tiene exactamente las mismas opciones
			var l = $.grep(carro, function (x) {
				return x.id_producto === id && x.nota === nota && x.agregados.join(',') === agregados.join(',');
			})[0];
			if (l) l.cantidad += 1;
			else carro.push({ id_producto: id, nombre: p.nombre, precio: p.precio, cantidad: 1, agregados: agregados, nota: nota });
			renderCarro();
		}

		function totales() {
			var subtotal = 0;
			$.each(carro, function (i, l) { subtotal += Math.round(precioLinea(l) * l.cantidad); });
			var valor = Math.max(0, Number($('#txtDescuento').val()) || 0);
			var descuento = tipoDesc === 'porc' ? Math.round(subtotal * Math.min(valor, 100) / 100) : Math.round(valor);
			descuento = Math.min(descuento, subtotal);
			var total = subtotal - descuento;
			var factor = 1 + (catalogo ? catalogo.config.por_iva : 19) / 100;
			var neto = Math.round(total / factor);
			return { subtotal: subtotal, descuento: descuento, total: total, neto: neto, iva: total - neto };
		}

		function renderCarro() {
			var $t = $('#lineas').empty();
			if (!carro.length) {
				$t.append('<div class="caja-carro-vacio"><i class="pe-7s-cart"></i>Toca un producto para agregarlo a la venta</div>');
			}
			var unidades = 0;
			$.each(carro, function (i, l) {
				unidades += l.cantidad;
				var detalle = $.map(l.agregados, function (id) { var a = agregado(id); return a ? '+ ' + esc(a.nombre) : null; });
				if (l.nota) detalle.push('<i>' + esc(l.nota) + '</i>');
				var nombre = tieneOpciones(l.id_producto) ? '<a href="#" class="editar" title="Cambiar agregados / indicaciones">' + esc(l.nombre) + '</a>' : esc(l.nombre);
				$t.append('<div class="caja-linea" data-i="' + i + '">'
					+ '<div class="info"><div class="nombre">' + nombre + '</div>'
					+ (detalle.length ? '<div class="detalle-linea">' + detalle.join('<br>') + '</div>' : '')
					+ '<div class="unitario">' + fmt(precioLinea(l)) + ' c/u</div></div>'
					+ '<div class="input-group input-group-sm cantidad">'
					+ '<span class="input-group-btn"><button class="btn btn-default menos" type="button" title="Quitar uno"><i class="fa fa-minus"></i></button></span>'
					+ '<input type="number" class="form-control cant" min="1" step="1" value="' + l.cantidad + '">'
					+ '<span class="input-group-btn"><button class="btn btn-default mas" type="button" title="Agregar uno"><i class="fa fa-plus"></i></button></span></div>'
					+ '<div class="subtotal">' + fmt(Math.round(precioLinea(l) * l.cantidad)) + '</div>'
					+ '<button class="quitar" title="Quitar producto"><i class="fa fa-trash"></i></button></div>');
			});
			$('#cantItems, #barraItems').text(unidades);
			$('#barraMovil').toggleClass('visible', carro.length > 0);
			$('.caja').toggleClass('con-barra', carro.length > 0);
			var t = totales();
			$('#lblSubtotal').text(fmt(t.subtotal));
			$('#lblDescuento').text(fmt(-t.descuento));
			$('#lblNeto').text(fmt(t.neto));
			$('#lblIva').text(fmt(t.iva));
			$('#lblTotal').text(fmt(t.total));
			$('#lblCobrar').text(carro.length ? fmt(t.total) : '');
			$('#barraTotal').text(fmt(t.total));
			$('#btnCobrar').prop('disabled', !carro.length || t.subtotal <= 0);
		}

		$('#lineas').on('change', '.cant', function () {
			var i = $(this).closest('[data-i]').data('i');
			var c = Math.round(Number($(this).val()));
			if (c > 0) carro[i].cantidad = c; else carro.splice(i, 1);
			renderCarro();
		}).on('click', '.quitar', function () {
			carro.splice($(this).closest('[data-i]').data('i'), 1);
			renderCarro();
		}).on('click', '.mas, .menos', function () {
			var i = $(this).closest('[data-i]').data('i');
			carro[i].cantidad += $(this).hasClass('mas') ? 1 : -1;
			if (carro[i].cantidad < 1) carro.splice(i, 1);
			renderCarro();
		}).on('click', '.editar', function (e) {
			e.preventDefault();
			var i = $(this).closest('[data-i]').data('i');
			abrirOpciones(carro[i].id_producto, i);
		});

		$('#txtDescuento').on('input', renderCarro);
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
			carro = [];
			cliente = null;
			$('#txtCliente').val('');
			$('#txtDescuento').val(0);
			$('#txtObservacion').val('');
			$('#buscarProducto').val('');
			renderProductos();
			renderCarro();
			$('#buscarProducto').focus();
		}

		$('#btnLimpiar').on('click', function () {
			if (!carro.length) return;
			swal({ title: '¿Vaciar la venta?', type: 'warning', showCancelButton: true, confirmButtonText: 'Sí, vaciar', cancelButtonText: 'No' },
				function (ok) { if (ok) limpiarVenta(); });
		});

		// ---------------- cliente ----------------
		var timerCliente = null;
		$('#btnBuscarCliente').on('click', function () {
			formClienteNuevo();
			$('#modalCliente').modal('show');
			$('#modalCliente a[href="#tabBuscarCliente"]').tab('show');
			setTimeout(function () { $('#txtBuscarCliente').val('').focus(); buscarClientes(''); }, 300);
		});
		$('#txtCliente').on('click', function () { $('#btnBuscarCliente').click(); });
		$('#btnQuitarCliente').on('click', function () {
			cliente = null;
			$('#txtCliente').val('');
		});
		$('#txtBuscarCliente').on('input', function () {
			clearTimeout(timerCliente);
			var q = $(this).val();
			timerCliente = setTimeout(function () { buscarClientes(q); }, 250);
		});

		function buscarClientes(q) {
			get('clientes', { q: q }).done(function (r) {
				var $r = $('#resultadosCliente').empty();
				if (!r.clientes.length) $r.append('<div class="list-group-item text-muted">Sin resultados</div>');
				$.each(r.clientes, function (i, c) {
					var rut = c.rut ? c.rut + '-' + c.dv : '';
					var publico = Number(c.id_cliente) === catalogo.config.id_cliente_publico;
					$r.append($('<a href="#" class="list-group-item">')
						.data('cliente', { id_cliente: Number(c.id_cliente), nombre_cliente: c.nombre_cliente, rut: rut })
						.data('ficha', c)
						.html((publico ? '' : '<button type="button" class="btn btn-xs btn-default pull-right btn-editar-cliente" title="Editar"><i class="fa fa-pencil"></i></button>')
							+ '<b>' + esc(c.nombre_cliente) + '</b> <small class="text-muted">' + esc(rut) + (c.giro ? ' · ' + esc(c.giro) : '')
							+ (c.comuna ? ' · ' + esc(c.comuna) : '') + '</small>'));
				});
			});
		}

		function usarCliente(c) {
			cliente = c;
			$('#txtCliente').val(c.nombre_cliente + (c.rut ? ' (' + c.rut + ')' : ''));
			$('#modalCliente').modal('hide');
		}

		$('#resultadosCliente').on('click', 'a', function (e) {
			e.preventDefault();
			usarCliente($(this).data('cliente'));
		});

		// El mismo formulario sirve para crear y para editar (id_cliente oculto)
		function formClienteNuevo() {
			var $f = $('#frmCliente');
			$f[0].reset();
			$f.find('[name=id_cliente]').val('');
			$('#cboComuna').val('0').trigger('change');
			$('#tabClienteForm').text('Nuevo');
			$('#btnCancelarEdicion').hide();
		}
		$('#resultadosCliente').on('click', '.btn-editar-cliente', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var c = $(this).closest('a').data('ficha');
			var $f = $('#frmCliente');
			$f.find('[name=id_cliente]').val(c.id_cliente);
			$f.find('[name=nombre_cliente]').val(c.nombre_cliente);
			$f.find('[name=rut]').val(c.rut ? c.rut + '-' + c.dv : '');
			$f.find('[name=giro]').val(c.giro);
			$f.find('[name=direccion]').val(c.direccion);
			$f.find('[name=telefono]').val(c.telefono);
			$f.find('[name=mail]').val(c.mail);
			$('#cboComuna').val(String(c.id_comuna || 0)).trigger('change');
			$('#tabClienteForm').text('Editar').tab('show');
			$('#btnCancelarEdicion').show();
		});
		$('#btnCancelarEdicion').on('click', function () {
			formClienteNuevo();
			$('#modalCliente a[href="#tabBuscarCliente"]').tab('show');
		});

		$('#frmCliente').on('submit', function (e) {
			e.preventDefault();
			var $f = $(this);
			var editando = !!$f.find('[name=id_cliente]').val();
			post('guardarCliente', $f.serialize()).done(function (r) {
				formClienteNuevo();
				toastr.success(editando ? 'Cliente actualizado' : 'Cliente creado');
				usarCliente({ id_cliente: r.id_cliente, nombre_cliente: r.nombre_cliente, rut: r.rut });
			});
		});

		// ---------------- autorización de administrador ----------------
		// enviar(aut) hace la petición; si el servidor responde 403 {autorizacion}, se pide la clave y se reintenta.
		var autorizacionPendiente = null;
		function enviarConAutorizacion(enviar, listo, alFallar, aut) {
			return enviar(aut || null).done(listo).fail(function (xhr) {
				var r = xhr.responseJSON;
				if (xhr.status === 403 && r && r.autorizacion) {
					pedirAutorizacion(r, aut ? r.error : '', function (nueva) {
						enviarConAutorizacion(enviar, listo, alFallar, nueva);
					}, alFallar);
				} else {
					error(xhr);
					if (alFallar) alFallar();
				}
			});
		}

		function pedirAutorizacion(r, mensajeError, continuar, cancelar) {
			var $c = $('#cboAutorizador').empty();
			if (catalogo.config.tiene_clave_admin) $c.append($('<option>').val(0).text('Clave de administración'));
			$.each(catalogo.administradores, function (i, a) { $c.append($('<option>').val(a.id_usuario).text(a.nombre)); });
			if (!$c.children().length) {
				swal('Sin administradores', 'Se requiere autorización para ' + r.motivo + ', pero no hay usuarios administradores ni clave de administración configurada.', 'error');
				if (cancelar) cancelar();
				return;
			}
			autorizacionPendiente = { continuar: continuar, cancelar: cancelar };
			$('#autorizacionMotivo').text('Se requiere autorización de un administrador para: ' + r.motivo + '.');
			$('#autorizacionError').text(mensajeError || '').toggle(!!mensajeError);
			$('#txtClaveAdmin').val('');
			$('#modalAutorizacion').modal('show');
		}

		$('#modalAutorizacion').on('shown.bs.modal', function () { $('#txtClaveAdmin').focus(); })
			.on('hidden.bs.modal', function () {
				// Si quedó otro modal abierto debajo (cobro), se mantiene el scroll del modal
				if ($('.modal.in').length) $('body').addClass('modal-open');
				if (autorizacionPendiente && autorizacionPendiente.cancelar) autorizacionPendiente.cancelar();
				autorizacionPendiente = null;
			});

		$('#frmAutorizacion').on('submit', function (e) {
			e.preventDefault();
			var clave = $('#txtClaveAdmin').val();
			if (!clave) { $('#txtClaveAdmin').focus(); return; }
			var pendiente = autorizacionPendiente;
			autorizacionPendiente = null; // no cancelar al cerrar: se reintenta
			$('#modalAutorizacion').modal('hide');
			pendiente.continuar({ id_usuario: Number($('#cboAutorizador').val()), clave: clave });
		});

		// ---------------- pago ----------------
		function esCortesia() {
			return pagos.length > 0 && fpago(pagos[0].id_fpago).cortesia;
		}
		function totalAPagar() {
			return totales().total + (esCortesia() ? 0 : propina);
		}
		function propinaSugerida() {
			return Math.round(totales().total * catalogo.config.por_propina / 100);
		}

		$('#btnCobrar').on('click', function () {
			var d = documentoActual();
			if (!d || !d.habilitado) { swal('Atención', 'No hay documentos de venta disponibles (tdocumento.para_venta)', 'warning'); return; }
			if (d.requiere_cliente && !cliente) {
				swal('Falta el cliente', d.nombre + ' requiere seleccionar un cliente', 'warning');
				return;
			}
			var t = totales();
			if (catalogo.config.por_maximo_descuento > 0 && t.subtotal > 0 && t.descuento * 100 / t.subtotal > catalogo.config.por_maximo_descuento) {
				swal('Descuento', 'El descuento máximo permitido es ' + catalogo.config.por_maximo_descuento + '%', 'warning');
				return;
			}
			pagos = [];
			$('#pagoTotal').text(fmt(t.total));
			// Propina sugerida (Configuraciones: propina en venta directa / calcular valor de propina)
			var cfg = catalogo.config;
			$('#bloquePropina').toggle(cfg.propina);
			$('#lblPorPropina').text(cfg.por_propina > 0 ? '(sugerida ' + cfg.por_propina + '%)' : '');
			propina = cfg.propina && cfg.calcular_propina ? propinaSugerida() : 0;
			$('#chkPropina').prop('checked', propina > 0).prop('disabled', false);
			$('#txtPropina').val(propina).prop('disabled', propina === 0);
			var $b = $('#botonesFpago').empty();
			$.each(catalogo.formas_pago, function (i, f) {
				$b.append($('<button type="button" class="btn">').addClass(f.cortesia ? 'btn-warning' : (f.efectivo ? 'btn-success' : 'btn-primary'))
					.text(f.nombre).data('id', f.id_fpago));
			});
			renderPagos();
			$('#modalPago').modal('show');
		});

		$('#chkPropina').on('change', function () {
			propina = this.checked ? (propinaSugerida() || propina) : 0;
			$('#txtPropina').val(propina).prop('disabled', !this.checked);
			if (this.checked) $('#txtPropina').focus().select();
			ajustarPagoUnico();
		});
		$('#txtPropina').on('input', function () {
			propina = Math.max(0, Math.round(Number($(this).val()) || 0));
			ajustarPagoUnico();
		});

		// Con un solo pago cargado automáticamente, se ajusta al nuevo total con propina
		function ajustarPagoUnico() {
			if (pagos.length === 1 && !pagos[0].tocado && !fpago(pagos[0].id_fpago).cortesia) pagos[0].monto = totalAPagar();
			renderPagos();
		}

		$('#botonesFpago').on('click', 'button', function () {
			var f = fpago($(this).data('id'));
			var total = totalAPagar();
			if (f.cortesia) {
				pagos = [{ id_fpago: f.id_fpago, monto: totales().total }];
			} else {
				// Se descartan la cortesía y las filas en 0 que quedaron de un cambio anterior
				pagos = $.grep(pagos, function (p) { return !fpago(p.id_fpago).cortesia && p.monto > 0; });
				var existente = $.grep(pagos, function (p) { return p.id_fpago === f.id_fpago; })[0];
				if (existente) {
					// Tocar de nuevo la misma forma de pago la deja cubriendo el saldo exacto
					existente.monto = Math.max(0, total - sumaPagos(existente));
					existente.billetes = false;
					existente.tocado = false;
				} else if (pagos.length && sumaPagos(null) >= total) {
					// Ya estaba todo pagado: es un cambio de forma de pago, no un pago adicional
					var ultimo = pagos[pagos.length - 1];
					ultimo.id_fpago = f.id_fpago;
					ultimo.monto = Math.max(0, total - sumaPagos(ultimo));
					ultimo.billetes = false;
					ultimo.tocado = false;
				} else {
					pagos.push({ id_fpago: f.id_fpago, monto: Math.max(0, total - sumaPagos(null)) });
				}
			}
			renderPagos();
			$('#filasPago input').last().focus().select();
		});

		// Suma de los pagos, sin contar la fila "excepto"
		function sumaPagos(excepto) {
			var suma = 0;
			$.each(pagos, function (i, p) { if (p !== excepto) suma += p.monto; });
			return suma;
		}

		// El último pago (o la forma por defecto) cubre exactamente lo que falta: sin vuelto
		$('#btnPagoExacto').on('click', function () {
			var total = totalAPagar();
			pagos = $.grep(pagos, function (p) { return p.monto > 0 || pagos.length === 1; });
			if (!pagos.length) {
				var defecto = fpago(catalogo.config.id_fpago_defecto);
				if (!defecto || defecto.cortesia) defecto = $.grep(catalogo.formas_pago, function (f) { return f.efectivo; })[0] || catalogo.formas_pago[0];
				pagos.push({ id_fpago: defecto.id_fpago, monto: 0 });
			}
			var ultimo = pagos[pagos.length - 1];
			if (fpago(ultimo.id_fpago).cortesia) {
				ultimo.monto = totales().total;
			} else {
				ultimo.monto = Math.max(0, total - sumaPagos(ultimo));
				ultimo.billetes = false;
				ultimo.tocado = false;
			}
			renderPagos();
			$('#btnConfirmarPago').focus();
		});

		function renderPagos() {
			var $f = $('#filasPago').empty();
			$.each(pagos, function (i, p) {
				var f = fpago(p.id_fpago);
				var rapidos = '';
				if (f.efectivo) {
					$.each([1000, 2000, 5000, 10000, 20000], function (j, b) {
						rapidos += '<button type="button" class="btn btn-xs btn-default sumar" data-v="' + b + '">+' + fmt(b) + '</button> ';
					});
				}
				$f.append('<div data-i="' + i + '">'
					+ '<div class="pago-fila"><span class="nombre">' + esc(f.nombre) + '</span>'
					+ '<input type="number" min="0" step="1" class="form-control monto" value="' + p.monto + '"' + (f.cortesia ? ' readonly' : '') + '>'
					+ '<button type="button" class="btn btn-default quitar" title="Quitar"><i class="fa fa-times"></i></button></div>'
					+ (rapidos ? '<div style="margin:-2px 0 8px">' + rapidos + '</div>' : '') + '</div>');
			});
			// La cortesía no lleva propina
			var cortesia = esCortesia();
			$('#chkPropina, #txtPropina').prop('disabled', cortesia);
			if (!cortesia) $('#txtPropina').prop('disabled', !$('#chkPropina').prop('checked'));
			actualizarResumenPago();
		}

		function actualizarResumenPago() {
			var total = totalAPagar();
			var pagado = 0, efectivo = 0;
			$.each(pagos, function (i, p) {
				pagado += p.monto;
				if (fpago(p.id_fpago).efectivo) efectivo += p.monto;
			});
			var vuelto = Math.max(0, pagado - total);
			var falta = Math.max(0, total - pagado);
			$('#pagoTotalConPropina').text(fmt(total));
			$('#pagoPagado').text(fmt(pagado));
			$('#pagoFalta').text(fmt(falta));
			$('#pagoVuelto').text(fmt(vuelto));
			var vueltoValido = vuelto === 0 || efectivo > vuelto;
			$('#pagoVuelto').toggleClass('text-danger', !vueltoValido).toggleClass('text-success', vueltoValido);
			$('#btnConfirmarPago').prop('disabled', !pagos.length || falta > 0 || !vueltoValido || enviando);
		}

		$('#filasPago').on('input', '.monto', function () {
			var p = pagos[$(this).closest('[data-i]').data('i')];
			p.monto = Math.max(0, Math.round(Number($(this).val()) || 0));
			p.tocado = true;
			actualizarResumenPago();
		}).on('click', '.quitar', function () {
			pagos.splice($(this).closest('[data-i]').data('i'), 1);
			renderPagos();
		}).on('click', '.sumar', function () {
			var i = $(this).closest('[data-i]').data('i');
			// El primer billete reemplaza el saldo cargado automáticamente
			if (!pagos[i].billetes) { pagos[i].monto = 0; pagos[i].billetes = true; }
			pagos[i].tocado = true;
			pagos[i].monto += Number($(this).data('v'));
			renderPagos();
		});

		$('#btnConfirmarPago').on('click', function () {
			if (enviando) return;
			var t = totales();
			var venta = {
				id_tdocumento: Number($('#cboDocumento').val()),
				id_lprecio: Number($('#cboLista').val()),
				id_cliente: cliente ? cliente.id_cliente : 0,
				descuento: t.descuento,
				propina: esCortesia() ? 0 : propina,
				observacion: $.trim($('#txtObservacion').val()),
				detalle: $.map(carro, function (l) { return { id_producto: l.id_producto, cantidad: l.cantidad, agregados: l.agregados, nota: l.nota }; }),
				pagos: $.map(pagos, function (p) { return p.monto > 0 ? { id_fpago: p.id_fpago, monto: p.monto } : null; })
			};
			enviando = true;
			actualizarResumenPago();
			var terminar = function () { enviando = false; actualizarResumenPago(); };
			enviarConAutorizacion(function (aut) {
				venta.autorizacion = aut;
				return $.ajax({ url: base + 'vender', type: 'POST', contentType: 'application/json', data: JSON.stringify(venta), dataType: 'json' });
			}, function (r) {
				terminar();
				$('#modalPago').modal('hide');
				if (r.total !== t.total) toastr.warning('El total se recalculó con los precios vigentes: ' + fmt(r.total));
				$('#ventaOkTitulo').text('Venta registrada');
				$('#btnNuevaVenta').text('Nueva venta');
				mostrarResultadoDte(r.dte);
				$('#ventaOkDoc').text(r.documento + (r.folio ? ' N° ' + r.folio : '') + ' · ' + fmt(r.total) + (r.propina > 0 ? ' + propina ' + fmt(r.propina) : ''));
				$('#ventaOkVuelto').text(r.vuelto > 0 ? 'Vuelto: ' + fmt(r.vuelto) : '');
				$('#ventaOkTicket').attr('href', base + 'ticket/' + r.id_venta + '?imprimir=1');
				$('#ventaOkPdf').attr('href', base + 'pdf/' + r.id_venta);
				$('#modalVentaOk').modal('show');
				if (imprimeTicket()) imprimir(base + 'ticket/' + r.id_venta);
				limpiarVenta();
				cargarEstado();
			}, terminar);
		});

		$('#modalVentaOk').on('hidden.bs.modal', function () { $('#buscarProducto').focus(); });

		// Emisión electrónica: si Cloud Apps no respondió, la venta queda pendiente y se reenvía desde Ventas
		function mostrarResultadoDte(dte) {
			var $a = $('#ventaOkAviso').hide();
			if (dte && !dte.ok) {
				$a.html('<i class="fa fa-exclamation-triangle"></i> Pendiente de envío al SII: ' + esc(dte.mensaje)
					+ '<br><small>Reenvíalo desde la pestaña Ventas.</small>').show();
			}
		}

		// ---------------- ventas de la caja ----------------
		function cargarVentas() {
			get('ventas').done(function (r) {
				var $t = $('#tablaVentas tbody').empty();
				if (!r.ventas.length) $t.append('<tr><td colspan="7" class="text-center text-muted">Aún no hay ventas en esta caja</td></tr>');
				$.each(r.ventas, function (i, v) {
					var acciones = '<a class="btn btn-xs btn-default" target="_blank" href="' + base + 'ticket/' + v.id_venta + '?imprimir=1" title="Ticket"><i class="fa fa-print"></i></a> '
						+ '<a class="btn btn-xs btn-default" target="_blank" href="' + base + 'pdf/' + v.id_venta + '" title="PDF"><i class="fa fa-file-pdf-o"></i></a> '
						+ (v.anulada ? '<span class="label label-danger">Anulada</span>'
							: (v.pendiente_dte
								? '<button class="btn btn-xs btn-info reenviar" data-id="' + v.id_venta + '" title="Reintentar la emisión ante el SII"><i class="fa fa-refresh"></i> Reenviar</button> ' : '')
							+ (!v.es_nc && !v.cortesia && v.total > 0 && !v.pendiente_dte
								? '<button class="btn btn-xs btn-warning devolver" data-id="' + v.id_venta + '" title="Devolver productos"><i class="fa fa-undo"></i> Devolver</button> ' : '')
							// Un documento electrónico ya emitido no se anula: se devuelve con nota de crédito
							+ (v.electronico && !v.pendiente_dte
								? (v.es_nc ? '<span class="label label-success" title="Emitida ante el SII: no se anula">Emitida</span>' : '')
								: '<button class="btn btn-xs btn-danger anular" data-id="' + v.id_venta + '" data-folio="' + v.folio + '">Anular</button>'));
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
					return $.ajax({ url: base + 'anular', type: 'POST', dataType: 'json', data: { id_venta: id, motivo: motivo, autorizacion: aut } });
				}, function () {
					toastr.success('Venta folio ' + folio + ' anulada');
					cargarVentas();
				});
			});
		});

		// ---------------- movimientos ----------------
		function cargarMovimientos() {
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
			var $b = $(this).prop('disabled', true);
			post('movimiento', datos).done(function () {
				toastr.success('Movimiento registrado');
				$('#montoMov, #obsMov').val('');
				cargarMovimientos();
			}).always(function () { $b.prop('disabled', false); });
		});

		// ---------------- devoluciones (nota de crédito interna) ----------------
		var devolucion = null;

		function abrirDevolucion(idVenta) {
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
		$('#tablaVentas').on('click', '.devolver', function () { abrirDevolucion($(this).data('id')); });
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
				if (imprimeTicket()) imprimir(base + 'ticket/' + r.id_venta);
				if ($('#tabVentas').hasClass('active')) cargarVentas();
				cargarEstado();
			}, function () { $b.prop('disabled', false); });
		});

		// ---------------- cierre ----------------
		function cargarCierre() {
			cargarEstado().done(function () {
				if (!resumen) return;
				var r = resumen;
				var filas = [
					['Fondo inicial', r.fondo],
					['Ventas (' + r.cantidad_ventas + ')', r.ventas + r.devoluciones],
					['Devoluciones (' + r.cantidad_devoluciones + ')', -r.devoluciones],
					['Descuentos otorgados', r.descuentos],
					['Cortesías', r.cortesias],
					['Propinas (se pagan a los garzones desde la caja)', r.propinas],
					['Ventas anuladas', r.anuladas, true],
					['Ingresos de caja', r.ingresos],
					['Retiros de caja', -r.egresos]
				];
				var html = '';
				$.each(filas, function (i, f) {
					html += '<tr><td>' + esc(f[0]) + '</td><td class="caja-num">' + (f[2] ? f[1] : fmt(f[1])) + '</td></tr>';
				});
				html += '<tr><th colspan="2">Recaudado por forma de pago</th></tr>';
				$.each(r.por_forma_pago, function (i, f) {
					html += '<tr><td>' + esc(f.nombre) + (f.cortesia ? ' (no se rinde)' : '') + '</td><td class="caja-num">' + fmt(f.monto)
						+ (f.propina ? ' <small class="text-muted">+ ' + fmt(f.propina) + ' propina</small>' : '') + '</td></tr>';
				});
				html += '<tr class="info"><td><b>Efectivo esperado en caja</b>' + (r.propinas ? ' <small>(ya pagadas las propinas)</small>' : '') + '</td><td class="caja-num"><b>' + fmt(r.efectivo_esperado) + '</b></td></tr>';
				$('#resumenCierre').html(html);

				var $d = $('#declaradoOtros').empty();
				$.each(r.por_forma_pago, function (i, f) {
					if (f.efectivo || f.cortesia) return;
					$d.append('<div class="form-group"><label>' + esc(f.nombre) + ' (vouchers / comprobantes' + (f.propina ? ', con propina' : '') + ')</label>'
						+ '<input type="number" min="0" step="1" class="form-control declarado" data-id="' + f.id_fpago + '" value="' + (f.monto + f.propina) + '"></div>');
				});
				$('#efectivoContado').val(r.efectivo_esperado);
				$('#fondoSiguiente').val(Math.min(r.fondo, Math.max(0, r.efectivo_esperado)));
				calcularDiferencia();
			});
		}

		function calcularDiferencia() {
			if (!resumen) return;
			var esperado = resumen.efectivo_esperado, declarado = Number($('#efectivoContado').val()) || 0;
			$.each(resumen.por_forma_pago, function (i, f) { if (!f.efectivo && !f.cortesia) esperado += f.monto + f.propina; });
			$('.declarado').each(function () { declarado += Number($(this).val()) || 0; });
			var dif = declarado - esperado;
			$('#lblEsperado').text(fmt(esperado));
			$('#lblDeclarado').text(fmt(declarado));
			$('#lblDiferencia').text(fmt(dif)).css('color', dif === 0 ? '#37a000' : (dif < 0 ? '#d9534f' : '#f0ad4e'));
			var entregar = (Number($('#efectivoContado').val()) || 0) - (Number($('#fondoSiguiente').val()) || 0);
			$('#lblEntregar').text(fmt(entregar)).css('color', entregar < 0 ? '#d9534f' : '');
		}

		$('#lnkCierre').on('shown.bs.tab', cargarCierre);
		$('#btnInformeX').on('click', function () {
			if (resumen) imprimir(base + 'cierreTicket/' + resumen.id_caja);
		});
		$('#tabCierre').on('input', 'input', calcularDiferencia);

		$('#btnCerrarCaja').on('click', function () {
			var efectivo = $('#efectivoContado').val();
			if (efectivo === '' || Number(efectivo) < 0) { swal('Atención', 'Ingresa el efectivo contado', 'warning'); return; }
			var fondoSig = $('#fondoSiguiente').val();
			if (fondoSig === '' || Number(fondoSig) < 0) { swal('Atención', 'Ingresa el fondo que queda para el próximo turno', 'warning'); return; }
			if (Number(fondoSig) > Number(efectivo)) { swal('Atención', 'El fondo para el próximo turno no puede superar el efectivo contado', 'warning'); return; }
			var datos = { efectivo_contado: Math.round(Number(efectivo)), fondo_siguiente: Math.round(Number(fondoSig)) };
			$('.declarado').each(function () { datos['declarado[' + $(this).data('id') + ']'] = Math.round(Number($(this).val()) || 0); });
			swal({
				title: '¿Cerrar la caja?',
				text: 'Diferencia: ' + $('#lblDiferencia').text() + '. Después de cerrar no podrás registrar más ventas en esta caja.',
				type: 'warning',
				showCancelButton: true,
				closeOnConfirm: false,
				confirmButtonText: 'Cerrar caja',
				cancelButtonText: 'Cancelar'
			}, function (ok) {
				if (!ok) return;
				var idCaja = resumen.id_caja;
				var cierreTermica = catalogo && catalogo.config.imprimir_cierre_termica;
				post('cerrar', datos).done(function (r) {
					// Como el POS: si Configuraciones lo indica, el cierre sale también en la térmica
					if (cierreTermica) imprimir(base + 'cierreTicket/' + idCaja);
					swal({ title: 'Caja cerrada', text: 'Entregar ' + fmt(r.entregado) + ' en efectivo. Quedan ' + fmt(r.fondo_siguiente)
						+ ' de fondo para el próximo turno. Diferencia: ' + fmt(r.diferencia), type: 'success' }, function () {
						// Informe de cierre con el reporte existente
						$.redirect("<?php echo base_url(); ?>index.php/CierresDeCaja/cierreCajaPDF", { id_caja: idCaja }, 'POST', '_blank');
						limpiarVenta();
						catalogo = null;
						$('#panelCaja a[href="#tabVenta"]').tab('show');
						cargarEstado();
					});
				});
			});
		});

		cargarEstado();
	})();
</script>
