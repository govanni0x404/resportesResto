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
		var mesa = null;         // Venta por mesa: {id_mesa, id_cotizacion, inicio (ms)}; null en venta directa
		var cobroParcial = null; // Cobro de una parte de la mesa: {id_detalle: cantidad}; null = toda la mesa
		var cerrandoCaja = false;
		// Los avisos llevan nombres de mesas, productos y destinos escritos por usuarios: siempre como texto
		if (window.toastr) toastr.options.escapeHtml = true;

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
		// Boleta exenta (41) y factura exenta (34): todo es exento (igual que Mcaja::registrarVenta)
		function documentoExento() {
			var d = catalogo ? documentoActual() : null;
			return !!(d && (d.cod_sii === 34 || d.cod_sii === 41));
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
		function lineasComanda() {
			return $.map(pendientesMesa(), function (l) {
				return {
					id_producto: l.id_producto, nombre: l.nombre, cantidad: l.cantidad, nota: l.nota || '',
					agregados: $.map(l.agregados, function (id) { var a = agregado(id); return a ? a.nombre : null; })
				};
			});
		}
		// Cada destino de impresión (cocina, barra...) imprime en su propia impresora; lo envía el servidor
		function pendientesMesa() {
			return $.grep(carro, function (l) { return !l.enviada; });
		}
		// aviso: {anulacion: true, motivo} | {traslado_desde: idMesa} | {reenvio: true}; sin aviso es la comanda normal
		function imprimirComanda(idVenta, lineas, observacion, idMesa, aviso) {
			if (!lineas.length) return;
			aviso = aviso || {};
			var anulacion = !!aviso.anulacion;
			var que = anulacion ? 'Anulación' : (aviso.traslado_desde ? 'Cambio de mesa' : (aviso.reenvio ? 'Reenvío' : 'Comanda'));
			$.ajax({
				url: base + 'imprimirComanda', type: 'POST', dataType: 'json',
				data: { datos: JSON.stringify({ id_venta: idVenta, id_mesa: idMesa || 0, lineas: lineas, observacion: observacion || '', anulacion: anulacion, motivo: aviso.motivo || '', traslado_desde: aviso.traslado_desde || 0, reenvio: !!aviso.reenvio }) }
			}).done(function (r) {
				var com = r.comandas || [];
				$.each(com, function (i, c) {
					if (c.ok) toastr.success(que + ' enviada a ' + c.destino, '', { timeOut: 2500 });
					else toastr.error('Comanda de ' + c.destino + ': ' + c.detalle, '', { timeOut: 9000 });
				});
			}).fail(function (x) {
				toastr.error('No se pudo enviar la comanda: ' + ((x.responseJSON && x.responseJSON.error) || 'error de conexión'), '', { timeOut: 9000 });
			});
		}

		// ---------------- estado de caja ----------------
		function cargarEstado() {
			return get('estado').done(function (r) {
				resumen = r.caja;
				if (!resumen) {
					$('#panelCaja').hide();
					$('#panelApertura').show();
					var sugerido = r.fondo_sugerido;
					$('#fondoInicial').val(sugerido === null || sugerido === undefined ? 0 : sugerido);
					$('#ayudaFondo').toggle(sugerido !== null && sugerido !== undefined);
					$('#fondoInicial').focus();
					return;
				}
				$('#panelApertura').hide();
				$('#panelCaja').show();
				cargarMesas();
				ajustarAltoVenta();
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
				$('#avisoDtePrueba').toggle(!!r.dte_prueba);
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
					if (l.enviada) return true;   // lo enviado a la mesa conserva su precio
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

		// Grilla del modal de productos: familia elegida + filtro propio del modal
		function renderProductos() {
			if (!catalogo) return;
			var texto = $.trim($('#filtroProductos').val()).toLowerCase();
			var lista = $.grep(catalogo.productos, function (p) {
				return (!familiaActiva || p.id_familia === familiaActiva) && (!texto || p.nombre.toLowerCase().indexOf(texto) >= 0);
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

		function abrirCatalogo() {
			if (!catalogo) return;
			cerrarResultados();
			$('#filtroProductos').val('');
			renderFamilias();
			renderProductos();
			$('#modalProductos').modal('show');
		}
		$('#btnCatalogo').on('click', abrirCatalogo);
		$('#modalProductos').on('shown.bs.modal', function () { $('#filtroProductos').focus(); })
			.on('hidden.bs.modal', function () {
				// Producto con opciones elegido en la grilla: su modal se abre cuando este ya cerró
				if (opcionPendiente) { var id = opcionPendiente; opcionPendiente = null; abrirOpciones(id, null); }
				else $('#buscarProducto').focus();
			});
		var opcionPendiente = null;

		$('#productos').on('click', '.caja-producto', function () {
			var id = Number($(this).data('id'));
			if (tieneOpciones(id)) {
				opcionPendiente = id;
				$('#modalProductos').modal('hide');
				return;
			}
			agregar(id, [], '');
			renderProductos();
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
			if (e.which === 113 && $('#tabVenta').is(':visible') && !$('.modal.in').length) { e.preventDefault(); abrirCatalogo(); }
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
			// La factura calcula el IVA sobre el neto: el total puede variar unos pesos
			renderCarro();
		});

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
		function agregar(id, agregados, nota, cantidad) {
			var p = producto(id);
			if (!p) return;
			var dada = cantidad ? ajustarCantidad(id, cantidad) : 0;
			// Con un cobro parcial a medias, lo nuevo no entraría en ese cobro: se cancela y se avisa
			if (cobroParcial) {
				cobroParcial = null;
				actualizarModoMesa();
				toastr.info('Se canceló el cobro de una parte: al cobrar se incluirá toda la mesa');
			}
			agregados = agregados.slice().sort(function (a, b) { return a - b; });
			// Solo se suma a una línea existente si tiene exactamente las mismas opciones
			var l = $.grep(carro, function (x) {
				return !x.enviada && x.id_producto === id && x.nota === nota && x.agregados.join(',') === agregados.join(',');
			})[0];
			// El cursor se queda en el buscador para seguir ingresando (la cantidad se cambia en la línea o con cantidad*código).
			// Un pesable ya agregado no suma 1 kg a ciegas: solo suma si se indicó la cantidad.
			if (dada > 0) {
				if (l) l.cantidad = ajustarCantidad(id, l.cantidad + dada);
				else carro.push({ id_producto: id, nombre: p.nombre, precio: p.precio, cantidad: dada, agregados: agregados, nota: nota });
			} else if (l) {
				if (!esFraccion(id)) l.cantidad += 1;
			} else carro.push({ id_producto: id, nombre: p.nombre, precio: p.precio, cantidad: 1, agregados: agregados, nota: nota });
			renderCarro();
		}

		// Líneas brutas como las guarda el servidor: el producto con su primer agregado, y cada agregado
		// siguiente como línea propia; si el producto es exento, todas sus líneas lo son
		function lineasBrutas() {
			var res = [];
			$.each(carro, function (i, l) {
				if (l.enviada) {
					if (!cobroParcial) { res.push({ bruto: l.bruto, exento: l.exento || documentoExento() }); return; }
					var q = cobroParcial[l.id_detalle] || 0;
					// Misma proporción que Mcaja::seleccionDeLineas()
					if (q > 0) res.push({ bruto: q >= l.cantidad ? l.bruto : Math.round(l.bruto_exacto * q / l.cantidad), exento: l.exento || documentoExento() });
					return;
				}
				if (cobroParcial) return;
				var p = producto(l.id_producto), exento = !!(p && p.exento) || documentoExento();
				var primero = l.agregados.length ? agregado(l.agregados[0]) : null;
				res.push({ bruto: Math.round((l.precio + (primero ? primero.valor : 0)) * l.cantidad), exento: exento });
				$.each(l.agregados.slice(1), function (j, id) {
					var a = agregado(id);
					if (a) res.push({ bruto: Math.round(a.valor * l.cantidad), exento: exento });
				});
			});
			return res;
		}

		// Mismo cálculo que Mcaja::montos() (el servidor valida el pago contra su total):
		// - lo exento queda fuera del IVA (Monto Exento)
		// - factura: neto por línea, Neto = suma - descuento neto, IVA = round(Neto x 19%), Total = Neto + IVA + Exento
		// - boleta / interno: Neto = round(Afecto / 1,19), IVA = Afecto - Neto, Total = suma de precios - descuento
		function totales() {
			var lineas = lineasBrutas(), subtotal = 0, subAfecto = 0;
			$.each(lineas, function (i, l) { subtotal += l.bruto; if (!l.exento) subAfecto += l.bruto; });
			var valor = Math.max(0, Number($('#txtDescuento').val()) || 0);
			var descuento = tipoDesc === 'porc' ? Math.round(subtotal * Math.min(valor, 100) / 100) : Math.round(valor);
			descuento = Math.min(descuento, subtotal);
			var tasa = catalogo ? catalogo.config.por_iva : 19, factor = 1 + tasa / 100;
			var descAfecto = subtotal > 0 ? Math.round(descuento * subAfecto / subtotal) : 0;
			var exento = (subtotal - subAfecto) - (descuento - descAfecto);
			var d = catalogo ? documentoActual() : null, neto, iva;
			if (d && d.es_factura) {
				var netoLineas = 0;
				$.each(lineas, function (i, l) { if (!l.exento) netoLineas += Math.round(l.bruto / factor); });
				neto = subAfecto > 0 ? netoLineas - Math.round(descAfecto / factor) : 0;
				iva = Math.round(neto * tasa / 100);
			} else {
				var afecto = subAfecto - descAfecto;
				neto = Math.round(afecto / factor);
				iva = afecto - neto;
			}
			return { subtotal: subtotal, descuento: descuento, exento: exento, neto: neto, iva: iva, total: neto + iva + exento };
		}

		function renderCarro() {
			var $t = $('#lineas').empty();
			if (!carro.length) {
				$t.append('<div class="caja-carro-vacio"><i class="pe-7s-cart"></i>Agrega productos con el buscador o con el botón Productos (F2)</div>');
			}
			var unidades = 0;
			$.each(carro, function (i, l) {
				if (l.enviada) {
					unidades += l.cantidad === Math.round(l.cantidad) ? l.cantidad : 1;
					var fuera = cobroParcial && !cobroParcial[l.id_detalle];
					var parte = cobroParcial && cobroParcial[l.id_detalle] && cobroParcial[l.id_detalle] < l.cantidad ? ' · se cobran ' + fmtCant(cobroParcial[l.id_detalle]) : '';
					$t.append('<div class="caja-linea enviada' + (fuera ? ' fuera-cobro' : '') + '" data-i="' + i + '"><div class="info">'
						+ '<div class="nombre"><i class="fa fa-lock" title="Ya enviado a la mesa"></i>' + esc(l.nombre) + '</div>'
						+ (l.agregado ? '<div class="detalle-linea">+ ' + esc(l.agregado) + '</div>' : '')
						+ (l.nota ? '<div class="detalle-linea"><i>' + esc(l.nota) + '</i></div>' : '')
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
				var detalle = $.map(l.agregados, function (id) { var a = agregado(id); return a ? '+ ' + esc(a.nombre) : null; });
				if (l.nota) detalle.push('<i>' + esc(l.nota) + '</i>');
				var nombre = tieneOpciones(l.id_producto) ? '<a href="#" class="editar" title="Cambiar agregados / indicaciones">' + esc(l.nombre) + '</a>' : esc(l.nombre);
				// Mensaje para la comanda: disponible en cualquier producto, tenga o no mensajes predefinidos
				nombre += ' <a href="#" class="editar caja-btn-mensaje' + (l.nota ? ' con-mensaje' : '') + '" title="' + (l.nota ? 'Editar mensaje para la comanda' : 'Agregar mensaje para la comanda') + '"><i class="fa fa-commenting-o"></i></a>';
				var prod = producto(l.id_producto);
				if (prod && prod.exento) nombre += ' <span class="label label-default">EXENTO</span>';
				$t.append('<div class="caja-linea" data-i="' + i + '">'
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
			$('#lblExento').text(fmt(t.exento));
			$('#filaExento').toggle(t.exento > 0);
			$('#lblTotal').text(fmt(t.total));
			$('#lblCobrar').text(carro.length ? fmt(t.total) : '');
			$('#barraTotal').text(fmt(t.total));
			$('#btnCobrar').prop('disabled', !carro.length || t.subtotal <= 0);
			$('#btnEnviarMesa').prop('disabled', !pendientesMesa().length);
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
			if (c > 0) carro[i].cantidad = c; else carro.splice(i, 1);
			renderCarro();
		}).on('click', '.quitar', function () {
			var i = $(this).closest('[data-i]').data('i');
			if (carro[i].enviada) { anularLineaMesa(carro[i]); return; }
			carro.splice(i, 1);
			renderCarro();
		}).on('click', '.mas, .menos', function () {
			var i = $(this).closest('[data-i]').data('i');
			// Pesables: +/- de a 0,1 (100 g); el resto de a una unidad
			var paso = esFraccion(carro[i].id_producto) ? 0.1 : 1;
			carro[i].cantidad = ajustarCantidad(carro[i].id_producto, carro[i].cantidad + ($(this).hasClass('mas') ? paso : -paso));
			if (carro[i].cantidad <= 0) carro.splice(i, 1);
			renderCarro();
		}).on('click', '.editar', function (e) {
			e.preventDefault();
			var i = $(this).closest('[data-i]').data('i');
			abrirOpciones(carro[i].id_producto, i);
		});

		$('#txtDescuento').on('input', renderCarro);

		// Escritorio: la venta ocupa el alto visible para que el total y Cobrar queden siempre a la vista
		function ajustarAltoVenta() {
			var $v = $('.caja-venta');
			if (!$v.is(':visible') || window.innerWidth < 992) { $v.removeClass('alto-fijo').css('height', ''); return; }
			// El pie del sistema queda fijo abajo y taparía el botón Cobrar
			var $pie = $('.main-footer');
			var pie = $pie.length && $pie.css('position') === 'fixed' ? $pie.outerHeight() : 0;
			var alto = Math.max(440, window.innerHeight - $v.offset().top - pie - 16);
			$v.addClass('alto-fijo').css('height', alto);
		}
		$(window).on('resize', ajustarAltoVenta);
		$('#lnkVenta').on('shown.bs.tab', ajustarAltoVenta);
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
			mesa = null;
			cobroParcial = null;
			actualizarModoMesa();
			cliente = null;
			$('#txtCliente').val('');
			$('#txtDescuento').val(0);
			$('#txtObservacion').val('');
			$('#buscarProducto').val('');
			cerrarResultados();
			renderCarro();
			$('#buscarProducto').focus();
		}

		$('#btnLimpiar').on('click', function () {
			if (mesa) { $('#btnSalirMesa').click(); return; }
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
			if (!pendiente) return;
			autorizacionPendiente = null; // no cancelar al cerrar: se reintenta
			var aut = { id_usuario: Number($('#cboAutorizador').val()), clave: clave };
			// Se reintenta con el modal ya cerrado: si la clave es incorrecta debe poder abrirse de nuevo
			$('#modalAutorizacion').one('hidden.bs.modal', function () { pendiente.continuar(aut); }).modal('hide');
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
			// En una mesa se cobra lo enviado: lo que falte se envía primero
			if (mesa && pendientesMesa().length) enviarAMesa(true); else abrirPago();
		});
		// Antes del pago se pregunta por la propina (si corresponde): Sí = monto sugerido o el que se escriba, No = 0
		function abrirPago() {
			var cfg = catalogo.config;
			if (!validarCobro()) return;
			if (!mesa) { mostrarPago(0); return; }   // la propina es solo para las mesas
			var sugerida = propinaSugerida();
			$('#propinaPregunta').text(fmt(totales().total));
			$('#propinaPorcentaje').text(cfg.por_propina > 0 ? 'Sugerida: ' + cfg.por_propina + '% (' + fmt(sugerida) + ')' : 'Escribe el monto de la propina');
			$('#txtPropinaPregunta').val(sugerida || '');
			$('#modalPropina').modal('show');
		}
		$('#modalPropina').on('shown.bs.modal', function () { $('#btnPropinaSi').focus(); });
		$('#txtPropinaPregunta').on('keydown', function (e) { if (e.which === 13) { e.preventDefault(); $('#btnPropinaSi').click(); } });
		$('#btnPropinaSi, #btnPropinaNo').on('click', function () {
			var monto = this.id === 'btnPropinaSi' ? Math.max(0, Math.round(Number($('#txtPropinaPregunta').val()) || 0)) : 0;
			// El pago se abre cuando este modal terminó de cerrarse (Bootstrap 3 no apila bien dos modales)
			$('#modalPropina').one('hidden.bs.modal', function () { mostrarPago(monto); }).modal('hide');
		});
		function validarCobro() {
			var d = documentoActual();
			if (!d || !d.habilitado) { swal('Atención', 'No hay documentos de venta disponibles (tdocumento.para_venta)', 'warning'); return false; }
			if (d.requiere_cliente && !cliente) {
				swal('Falta el cliente', d.nombre + ' requiere seleccionar un cliente', 'warning');
				return false;
			}
			var t = totales();
			if (catalogo.config.por_maximo_descuento > 0 && t.subtotal > 0 && t.descuento * 100 / t.subtotal > catalogo.config.por_maximo_descuento) {
				swal('Descuento', 'El descuento máximo permitido es ' + catalogo.config.por_maximo_descuento + '%', 'warning');
				return false;
			}
			return true;
		}
		// propinaElegida: monto respondido en el modal de propina; null si no se preguntó
		function mostrarPago(propinaElegida) {
			var t = totales();
			pagos = [];
			// Propina sugerida (Configuraciones: propina en venta directa / calcular valor de propina)
			var cfg = catalogo.config;
			var conPropina = !!mesa;   // venta directa sin propina: solo se cobra propina en las mesas
			$('#bloquePropina').toggle(conPropina);
			$('#lblPorPropina').text(cfg.por_propina > 0 ? '(sugerida ' + cfg.por_propina + '%)' : '');
			propina = propinaElegida !== null ? propinaElegida : (conPropina && (cfg.calcular_propina || mesa) ? propinaSugerida() : 0);
			$('#chkPropina').prop('checked', propina > 0).prop('disabled', false);
			$('#txtPropina').val(propina).prop('disabled', propina === 0);
			// Efectivo primero, cortesías al final; el resto en el orden de la base
			var orden = function (f) { return f.cortesia ? 2 : (f.efectivo ? 0 : 1); };
			var formas = catalogo.formas_pago.slice().sort(function (a, b) { return orden(a) - orden(b); });
			var $b = $('#botonesFpago').empty();
			$.each(formas, function (i, f) {
				var icono = f.cortesia ? 'fa-gift' : (f.efectivo ? 'fa-money' : (/confianza/i.test(f.nombre) ? 'fa-handshake-o' : (/tarjeta|debito|credito/i.test(f.nombre) ? 'fa-credit-card' : 'fa-exchange')));
				$b.append($('<button type="button" class="btn">').toggleClass('cortesia', !!f.cortesia).data('id', f.id_fpago)
					.html('<i class="fa ' + icono + '"></i> ' + esc(f.nombre)));
			});
			var defecto = fpago(catalogo.config.id_fpago_defecto);
			if (!defecto || defecto.cortesia) defecto = $.grep(catalogo.formas_pago, function (f) { return f.efectivo; })[0] || formas[0];
			renderPagos();
			elegirFpago(defecto.id_fpago);
			$('#modalPago').modal('show');
		}
		$('#modalPago').on('shown.bs.modal', function () { $('#montoPago').focus().select(); });

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
			if (!montoTocado && fpagoSel && !fpago(fpagoSel).cortesia) $('#montoPago').val(faltaPagar());
		}

		// ---- Forma de pago elegida (columna izquierda) y monto a cargar (columna derecha) ----
		var fpagoSel = null, montoTocado = false;
		function faltaPagar() { return Math.max(0, totalAPagar() - sumaPagos(null)); }
		function elegirFpago(id) {
			fpagoSel = id;
			var f = fpago(id);
			$('#botonesFpago .btn').each(function () { $(this).toggleClass('activa', $(this).data('id') === id); });
			$('#nombreFpagoSel').text(f.nombre.toLowerCase());
			montoTocado = false;
			$('#montoPago').val(f.cortesia ? totales().total : faltaPagar()).prop('readonly', !!f.cortesia);
			var billetes = '';
			if (f.efectivo) {
				$.each([1000, 2000, 5000, 10000, 20000], function (j, v) {
					billetes += '<button type="button" class="btn btn-default" data-v="' + v + '">+' + fmt(v) + '</button>';
				});
			}
			$('#billetesPago').html(billetes);
		}
		$('#botonesFpago').on('click', 'button', function () {
			elegirFpago($(this).data('id'));
			$('#montoPago').focus().select();
		});
		$('#montoPago').on('input', function () { montoTocado = true; })
			.on('keydown', function (e) { if (e.which === 13) { e.preventDefault(); $('#btnAgregarPago').click(); } });
		$('#billetesPago').on('click', 'button', function () {
			// El primer billete reemplaza el saldo sugerido; los siguientes se suman
			var actual = montoTocado ? (Math.round(Number($('#montoPago').val())) || 0) : 0;
			montoTocado = true;
			$('#montoPago').val(actual + Number($(this).data('v'))).focus();
		});

		// Suma el monto a la lista de pagos (si la forma ya está, se acumula en la misma fila)
		function agregarPago(id, monto) {
			var f = fpago(id);
			if (f.cortesia) { pagos = [{ id_fpago: id, monto: totales().total }]; return; }
			if (monto <= 0) return;
			// Una forma normal reemplaza la cortesía y descarta filas en 0
			pagos = $.grep(pagos, function (p) { return !fpago(p.id_fpago).cortesia && p.monto > 0; });
			var existente = $.grep(pagos, function (p) { return p.id_fpago === id; })[0];
			if (existente) { existente.monto += monto; existente.tocado = true; }
			else pagos.push({ id_fpago: id, monto: monto, tocado: monto !== faltaPagar() });
		}
		$('#btnAgregarPago').on('click', function () {
			agregarPago(fpagoSel, Math.max(0, Math.round(Number($('#montoPago').val())) || 0));
			renderPagos();
			// Queda lista la misma forma con el saldo que falta
			if (!fpago(fpagoSel).cortesia) { montoTocado = false; $('#montoPago').val(faltaPagar()); }
			if (!$('#btnConfirmarPago').prop('disabled')) $('#btnConfirmarPago').focus();
			else $('#montoPago').focus().select();
		});

		// Suma de los pagos, sin contar la fila "excepto"
		function sumaPagos(excepto) {
			var suma = 0;
			$.each(pagos, function (i, p) { if (p !== excepto) suma += p.monto; });
			return suma;
		}

		// Pago exacto: completa lo que falta con la forma elegida (sin vuelto) y registra la venta de inmediato
		$('#btnPagoExacto').on('click', function () {
			if (enviando) return;
			var f = fpago(fpagoSel);
			if (f.cortesia) {
				pagos = [{ id_fpago: f.id_fpago, monto: totales().total }];
			} else {
				pagos = $.grep(pagos, function (p) { return !fpago(p.id_fpago).cortesia && p.monto > 0; });
				var sobra = sumaPagos(null) - totalAPagar();
				if (sobra > 0) {
					// Lo cargado ya pasa del total: se rebaja hasta dejarlo exacto, empezando por el último pago
					for (var i = pagos.length - 1; i >= 0 && sobra > 0; i--) {
						var baja = Math.min(sobra, pagos[i].monto);
						pagos[i].monto -= baja;
						sobra -= baja;
					}
					pagos = $.grep(pagos, function (p) { return p.monto > 0; });
				}
				agregarPago(f.id_fpago, faltaPagar());
			}
			renderPagos();
			if (!$('#btnConfirmarPago').prop('disabled')) $('#btnConfirmarPago').click();
		});

		function renderPagos() {
			var $f = $('#filasPago').empty();
			if (!pagos.length) $f.html('<div class="vacio">Aún no hay pagos. Elige la forma de pago, ingresa el monto y presiona <b>Agregar pago</b>, o usa <b>Pago exacto</b>.</div>');
			$.each(pagos, function (i, p) {
				var f = fpago(p.id_fpago);
				$f.append('<div class="pago-fila" data-i="' + i + '"><span class="nombre">' + esc(f.nombre) + '</span>'
					+ '<input type="number" min="0" step="1" class="form-control monto" value="' + p.monto + '"' + (f.cortesia ? ' readonly' : '') + '>'
					+ '<button type="button" class="btn btn-default quitar" title="Quitar pago"><i class="fa fa-times"></i></button></div>');
			});
			// La cortesía no lleva propina
			var cortesia = esCortesia();
			$('#chkPropina, #txtPropina').prop('disabled', cortesia);
			if (!cortesia) $('#txtPropina').prop('disabled', !$('#chkPropina').prop('checked'));
			actualizarResumenPago();
		}

		function actualizarResumenPago() {
			var total = totalAPagar();
			// Título = lo que se cobra en total; con propina se detalla la venta y la propina
			var prop = esCortesia() ? 0 : propina;
			$('#pagoTotal').text(fmt(total));
			$('#pagoTotalDetalle').text(prop > 0 ? 'Venta ' + fmt(totales().total) + ' + propina ' + fmt(prop) : '');
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
			// Mismas reglas que Mcaja::registrarVenta: el vuelto sale del último pago en efectivo y la propina
			// debe caber en un solo pago (ese mismo o, sin efectivo, el último)
			var validos = $.grep(pagos, function (p) { return p.monto > 0; }), idxEf = -1;
			$.each(validos, function (i, p) { if (fpago(p.id_fpago).efectivo) idxEf = i; });
			var vueltoValido = vuelto === 0 || (idxEf >= 0 && validos[idxEf].monto > vuelto);
			var idxProp = idxEf >= 0 ? idxEf : validos.length - 1;
			var propinaCabe = prop === 0 || falta > 0 || (idxProp >= 0 && validos[idxProp].monto - (idxProp === idxEf ? vuelto : 0) >= prop);
			$('#pagoVuelto').toggleClass('text-danger', !vueltoValido);
			$('#cajaFalta').toggleClass('cubierto', falta === 0);
			$('#avisoReglaPago').text(!vueltoValido ? 'El vuelto debe salir de un solo pago en efectivo mayor que el vuelto.'
				: (!propinaCabe ? 'La propina debe quedar dentro de un solo pago: agrega el efectivo como un solo monto o paga la propina en un pago aparte.' : '')).toggle(!vueltoValido || !propinaCabe);
			$('#btnConfirmarPago').prop('disabled', !pagos.length || falta > 0 || !vueltoValido || !propinaCabe || enviando);
		}

		$('#filasPago').on('input', '.monto', function () {
			var p = pagos[$(this).closest('[data-i]').data('i')];
			p.monto = Math.max(0, Math.round(Number($(this).val()) || 0));
			p.tocado = true;
			actualizarResumenPago();
		}).on('click', '.quitar', function () {
			pagos.splice($(this).closest('[data-i]').data('i'), 1);
			renderPagos();
			if (!montoTocado && fpagoSel && !fpago(fpagoSel).cortesia) $('#montoPago').val(faltaPagar());
		});

		$('#btnConfirmarPago').on('click', function () {
			if (enviando) return;
			var t = totales();
			var idMesa = mesa ? mesa.id_mesa : 0;
			var venta = {
				id_mesa: idMesa,
				parcial: idMesa && cobroParcial ? $.map(cobroParcial, function (q, id) { return { id_detalle: Number(id), cantidad: q }; }) : null,
				id_tdocumento: Number($('#cboDocumento').val()),
				id_lprecio: Number($('#cboLista').val()),
				id_cliente: cliente ? cliente.id_cliente : 0,
				descuento: t.descuento,
				propina: esCortesia() ? 0 : propina,
				observacion: $.trim($('#txtObservacion').val()),
				detalle: $.map(carro, function (l) { return { id_producto: l.id_producto, cantidad: l.cantidad, agregados: l.agregados, nota: l.nota }; }),
				pagos: $.map(pagos, function (p) { return p.monto > 0 ? { id_fpago: p.id_fpago, monto: p.monto } : null; })
			};
			var comanda = lineasComanda();
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
				// La comanda sale después del ticket para no cruzar los dos diálogos de impresión
				// En una mesa la comanda ya salió al enviar el pedido
				if (imprimeComanda() && !idMesa) imprimirComanda(r.id_venta, comanda, venta.observacion);
				limpiarVenta();
				cargarEstado();
				// Se cobró una parte: la mesa sigue abierta con el resto
				if (idMesa && r.mesa_liberada === false) { cargarMesas(); abrirMesa(idMesa); }
				else if (idMesa) cargarMesas();
			}, terminar);
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

		// ---------------- mesas ----------------
		function fmtTiempo(ms) {
			var s = Math.max(0, Math.floor(ms / 1000)), d = Math.floor(s / 86400), h = Math.floor(s / 3600) % 24, m = Math.floor(s / 60) % 60;
			var p = function (n) { return (n < 10 ? '0' : '') + n; };
			return (d ? d + 'd ' : '') + p(h) + ':' + p(m) + ':' + p(s % 60);
		}
		var mesasLista = [], sectoresLista = [], sectorActual = null, editandoPlano = false, arrastre = null;
		var MESA_W = 130, MESA_H = 112, MESA_GAP = 14, PLANO_PASO = 10;
		try { var sectorGuardado = localStorage.getItem('cajaWeb.sectorMesas'); if (sectorGuardado !== null) sectorActual = Number(sectorGuardado); } catch (e) {}

		// Sin aviso de carga (global: false): la grilla se refresca sola y no debe bloquear la pantalla
		function cargarMesas() {
			return $.ajax({ url: base + 'mesas', dataType: 'json', global: false }).done(function (r) {
				$('#navMesas').toggle(!!r.habilitado);
				var ocupadas = $.grep(r.mesas, function (m) { return m.estado === 'ocupada'; }).length;
				$('#mesasOcupadas').text(ocupadas).toggle(ocupadas > 0);
				if (editandoPlano) return;   // no pisar lo que se está moviendo
				mesasLista = r.mesas;
				sectoresLista = r.sectores || [];
				renderMesas();
			});
		}
		function nombreMesa(id) {
			var m = $.grep(mesasLista, function (x) { return x.id_mesa === id; })[0];
			return m && m.nombre ? m.nombre : 'Mesa ' + id;
		}
		function mesasDelSector() {
			return $.grep(mesasLista, function (m) { return m.id_recinto === sectorActual; });
		}
		function renderSectores() {
			var sectores = [], vistos = {};
			var agregar = function (id, nombre) { vistos[id] = { id: id, nombre: nombre, ocupadas: 0 }; sectores.push(vistos[id]); };
			$.each(sectoresLista, function (i, x) { agregar(x.id, x.nombre); });
			$.each(mesasLista, function (i, m) {
				if (!vistos[m.id_recinto]) agregar(m.id_recinto, m.recinto);
				if (m.estado === 'ocupada') vistos[m.id_recinto].ocupadas++;
			});
			if (!vistos[sectorActual] && sectores.length) sectorActual = sectores[0].id;
			$('#mesasSectores').html($.map(sectores, function (s) {
				return '<button type="button" data-id="' + s.id + '" class="' + (s.id === sectorActual ? 'activo' : '') + '"' + (editandoPlano ? ' disabled' : '') + '>' + esc(s.nombre)
					+ (s.ocupadas ? '<span class="cuenta">' + s.ocupadas + '</span>' : '') + '</button>';
			}).join(''));
		}
		// Sin posición guardada, las mesas se ordenan en filas según el ancho disponible
		function posicionarPorDefecto(mesas) {
			var ancho = $('#mesasGrilla').width() || 900, cols = Math.max(1, Math.floor((ancho - MESA_GAP) / (MESA_W + MESA_GAP)));
			$.each(mesas, function (i, m) {
				if (m.x !== null && m.y !== null) return;
				m.x = MESA_GAP + (i % cols) * (MESA_W + MESA_GAP);
				m.y = MESA_GAP + Math.floor(i / cols) * (MESA_H + MESA_GAP);
			});
		}
		function ajustarAltoPlano(mesas) {
			var alto = 340;
			$.each(mesas, function (i, m) { alto = Math.max(alto, m.y + MESA_H + MESA_GAP); });
			$('#mesasGrilla').css('height', alto + (editandoPlano ? 150 : 0));
		}
		function renderMesas() {
			renderSectores();
			var $g = $('#mesasGrilla').addClass('mesas-plano').empty(), ahora = Date.now(), lista = mesasDelSector();
			posicionarPorDefecto(lista);
			if (!lista.length) $g.append('<div class="mesas-vacio">Este sector aún no tiene mesas. Asígnalas en <a href="<?php echo base_url(); ?>index.php/MesasPorRecinto">Mesas por recinto</a>.</div>');
			$.each(lista, function (i, m) {
				var $c = $('<button type="button" class="mesa-card">').addClass(m.estado).toggleClass('actual', !!mesa && mesa.id_mesa === m.id_mesa)
					.attr('data-id', m.id_mesa).attr('data-estado', m.estado).css({ left: m.x, top: m.y })
					.append($('<div class="num">').toggleClass('largo', !!m.nombre && m.nombre.length > 9).text(nombreMesa(m.id_mesa)).attr('title', nombreMesa(m.id_mesa)));
				if (m.con_pedido) {
					$c.append($('<div class="total">').text(fmt(m.total)))
						.append($('<div class="tiempo">').attr('data-inicio', ahora - m.segundos * 1000).text(fmtTiempo(m.segundos * 1000)))
						.toggleClass('demora', m.estado === 'ocupada' && m.segundos >= 3600 && m.segundos < 7200).toggleClass('critica', m.estado === 'ocupada' && m.segundos >= 7200)
						.append($('<div class="atiende">').text(m.atiende));
				} else {
					$c.append($('<div class="estado">').text(m.estado === 'libre' ? 'Libre' : 'Bloqueada'));
				}
				$g.append($c);
			});
			ajustarAltoPlano(lista);
		}
		function renombrarMesa(id) {
			var m = $.grep(mesasLista, function (x) { return x.id_mesa === id; })[0];
			if (!m) return;
			swal({ title: 'Nombre de la mesa ' + id, text: 'Ej: Terraza 2, Barra 1. Déjalo vacío para usar "Mesa ' + id + '".', type: 'input', inputValue: m.nombre || '', inputPlaceholder: 'Mesa ' + id, showCancelButton: true, confirmButtonText: 'Usar este nombre', cancelButtonText: 'Cancelar' }, function (v) {
				if (v === false) return;
				m.nombre = $.trim(v).substring(0, 40);
				renderMesas();
				toastr.info('Presiona Guardar plano para guardar el nombre');
			});
		}
		$('#mesasSectores').on('click', 'button', function () {
			sectorActual = Number($(this).attr('data-id'));
			try { localStorage.setItem('cajaWeb.sectorMesas', sectorActual); } catch (e) {}
			renderMesas();
		});

		// Organizar: se arrastran las mesas del sector y se guardan todas juntas
		function modoPlano(activo) {
			editandoPlano = activo;
			$('#btnOrganizarMesas').toggle(!activo);
			$('#btnCancelarPlano, #btnGuardarPlano').toggle(activo);
			$('#mesasGrilla').toggleClass('editando', activo);
			$('#mesasAyuda').text(activo ? 'Arrastra cada mesa a su lugar o tócala para cambiarle el nombre, y presiona Guardar plano.' : 'Toca una mesa libre para tomar el pedido, o una ocupada para agregar productos o cobrar.');
		}
		$('#btnOrganizarMesas').on('click', function () { modoPlano(true); renderMesas(); });
		$('#btnCancelarPlano').on('click', function () { modoPlano(false); cargarMesas(); });
		$('#lnkMesas').on('hidden.bs.tab', function () { if (editandoPlano) { modoPlano(false); cargarMesas(); } });
		$('#btnGuardarPlano').on('click', function () {
			var $b = $(this).prop('disabled', true);
			var posiciones = $.map(mesasDelSector(), function (m) { return { id_mesa: m.id_mesa, x: m.x, y: m.y, nombre: m.nombre || '' }; });
			postJson('guardarPlanoMesas', { posiciones: posiciones }).done(function () {
				toastr.success('Plano guardado');
				modoPlano(false);
				cargarMesas();
			}).fail(error).always(function () { $b.prop('disabled', false); });
		});
		$('#mesasGrilla').on('pointerdown', '.mesa-card', function (e) {
			if (!editandoPlano) return;
			e.preventDefault();
			var ev = e.originalEvent, c = this.getBoundingClientRect();
			arrastre = { $c: $(this).addClass('arrastrando'), id: Number($(this).attr('data-id')), dx: ev.clientX - c.left, dy: ev.clientY - c.top, x0: ev.clientX, y0: ev.clientY, movio: false };
		});
		$(document).on('pointermove', function (e) {
			if (!arrastre) return;
			var ev = e.originalEvent, g = $('#mesasGrilla')[0], r = g.getBoundingClientRect();
			if (Math.abs(ev.clientX - arrastre.x0) + Math.abs(ev.clientY - arrastre.y0) > 4) arrastre.movio = true;
			if (!arrastre.movio) return;
			var paso = function (v) { return Math.max(0, Math.round(v / PLANO_PASO) * PLANO_PASO); };
			var maxX = Math.max(0, g.clientWidth - MESA_W);
			var x = Math.min(maxX, paso(ev.clientX - r.left + g.scrollLeft - arrastre.dx)), y = paso(ev.clientY - r.top + g.scrollTop - arrastre.dy);
			arrastre.$c.css({ left: x, top: y });
			var m = $.grep(mesasLista, function (o) { return o.id_mesa === arrastre.id; })[0];
			if (m) { m.x = x; m.y = y; }
		}).on('pointerup pointercancel', function () {
			if (!arrastre) return;
			arrastre.$c.removeClass('arrastrando');
			var tocada = !arrastre.movio ? arrastre.id : 0;
			arrastre = null;
			// Tocar sin arrastrar: cambiar el nombre visible de la mesa (se guarda con el plano)
			if (tocada) renombrarMesa(tocada);
			ajustarAltoPlano(mesasDelSector());
		});
		$('#lnkMesas').on('shown.bs.tab', cargarMesas);
		setInterval(function () { if ($('#mesasGrilla').is(':visible')) cargarMesas(); }, 20000);
		// Ocupadas: ámbar desde 1 hora y rojo desde 2
		function colorPorTiempo($tiempo, ms) {
			$tiempo.closest('.mesa-card').toggleClass('demora', ms >= 3600000 && ms < 7200000).toggleClass('critica', ms >= 7200000);
		}
		setInterval(function () {
			$('#mesasGrilla .tiempo').each(function () {
				var ms = Date.now() - Number($(this).attr('data-inicio'));
				$(this).text(fmtTiempo(ms));
				colorPorTiempo($(this), ms);
			});
			if (mesa && mesa.inicio) $('#bannerMesaTiempo').text(fmtTiempo(Date.now() - mesa.inicio));
		}, 1000);

		function actualizarModoMesa() {
			$('#bannerMesa').toggle(!!mesa);
			$('#btnEnviarMesa').toggle(!!mesa);
			$('#cboLista').prop('disabled', !!(mesa && mesa.id_cotizacion));
			if (!mesa) return;
			$('#bannerMesaNum').text(nombreMesa(mesa.id_mesa));
			$('#btnPrecuenta, #grupoOpcionesMesa').toggle(!!mesa.id_cotizacion);
			$('#avisoCobroParcial').toggle(!!cobroParcial);
			if (!mesa.inicio) $('#bannerMesaTiempo').text('Sin enviar');
			ajustarAltoVenta();
		}
		// Lo enviado viene del servidor; lo que aún no se envía queda en el carro
		function aplicarDetalleMesa(d) {
			cobroParcial = null;
			mesa = { id_mesa: d.id_mesa, id_cotizacion: d.id_cotizacion, inicio: d.id_cotizacion ? Date.now() - d.segundos * 1000 : 0 };
			carro = $.map(d.lineas, function (l) {
				return { enviada: true, id_detalle: l.id_detalle, id_producto: l.id_producto, nombre: l.nombre, precio: l.unitario, bruto: l.bruto, bruto_exacto: l.bruto_exacto,
					cantidad: l.cantidad, agregados: [], agregado: l.agregado, nota: l.nota, exento: l.exento, hora: l.hora };
			}).concat(pendientesMesa());
			actualizarModoMesa();
			renderCarro();
		}
		// descartar: la mesa anterior tenía productos sin enviar que se pierden (solo si la nueva se abre bien)
		function abrirMesa(id, descartar) {
			get('detalleMesa', { id_mesa: id }).done(function (d) {
				if (descartar) carro = [];
				aplicarDetalleMesa(d);
				$('#lnkVenta').tab('show');
				if (d.id_lprecio && d.id_lprecio !== Number($('#cboLista').val())) cargarCatalogo(d.id_lprecio);
				$('#buscarProducto').focus();
			});
		}
		$('#mesasGrilla').on('click', '.mesa-card', function () {
			if (editandoPlano) return;
			var id = Number($(this).attr('data-id'));
			if ($(this).attr('data-estado') === 'bloqueada') { ofrecerLiberar(id); return; }
			if (mesa && mesa.id_mesa === id) { $('#lnkVenta').tab('show'); return; }
			if (mesa && pendientesMesa().length) {
				swal({ title: 'Hay productos sin enviar', text: 'Se perderán los que no enviaste a ' + nombreMesa(mesa.id_mesa) + '.', type: 'warning', showCancelButton: true, confirmButtonText: 'Continuar', cancelButtonText: 'Cancelar' },
					function (ok) { if (ok) abrirMesa(id, true); });
				return;
			}
			// Una venta directa en curso se lleva a la mesa elegida (sus productos quedan sin enviar)
			abrirMesa(id, !!mesa);
		});
		$('#btnSalirMesa').on('click', function () {
			var salir = function () { limpiarVenta(); $('#lnkMesas').tab('show'); };
			if (!pendientesMesa().length) { salir(); return; }
			swal({ title: '¿Salir sin enviar?', text: 'Se perderán los productos que no enviaste a la mesa.', type: 'warning', showCancelButton: true, confirmButtonText: 'Salir', cancelButtonText: 'Quedarme' },
				function (ok) { if (ok) salir(); });
		});
		function postJson(accion, datos) {
			return $.ajax({ url: base + accion, type: 'POST', contentType: 'application/json', data: JSON.stringify(datos), dataType: 'json' });
		}
		$('#btnEnviarMesa').on('click', function () { enviarAMesa(false); });
		function enviarAMesa(luegoCobrar) {
			if (enviando || !mesa) return;
			var comanda = lineasComanda(), idMesa = mesa.id_mesa, enviadas = pendientesMesa();
			var detalle = $.map(enviadas, function (l) { return { id_producto: l.id_producto, cantidad: l.cantidad, agregados: l.agregados, nota: l.nota }; });
			enviando = true;
			$('#btnEnviarMesa, #btnCobrar').prop('disabled', true);
			postJson('enviarMesa', { id_mesa: idMesa, id_lprecio: Number($('#cboLista').val()), detalle: detalle }).done(function (r) {
				if (imprimeComanda()) imprimirComanda(0, comanda, '', idMesa);
				// Se conserva lo que se agregó mientras se enviaba
				carro = $.grep(carro, function (l) { return !l.enviada && $.inArray(l, enviadas) < 0; });
				aplicarDetalleMesa(r.detalle);
				cargarMesas();
				toastr.success('Pedido enviado a ' + nombreMesa(idMesa));
				enviando = false;
				if (luegoCobrar) { renderCarro(); abrirPago(); return; }
				if (pendientesMesa().length) { toastr.info('Quedan productos agregados sin enviar'); return; }
				limpiarVenta();
				$('#lnkMesas').tab('show');
			}).fail(function (x) {
				error(x);
				enviando = false;
				renderCarro();
			});
		}
		function anularLineaMesa(l) { abrirMesaProductos('anular', [l]); }

		// ---- ventana de productos de la mesa: mover/dividir, cobrar una parte, anular parte de un producto ----
		var mp = null;   // {modo, lineas}
		var MP_TEXTOS = {
			mover: { titulo: 'Cambiar o dividir', boton: 'Pasar a la mesa', ayuda: 'Elige qué productos pasan a la otra mesa. Si pasas todo a una mesa libre, el pedido completo cambia de mesa y conserva su hora.' },
			parcial: { titulo: 'Cobrar solo una parte', boton: 'Cobrar lo elegido', ayuda: 'Elige lo que se paga ahora. Lo demás sigue en la mesa para cobrarlo después.' },
			anular: { titulo: 'Anular producto', boton: 'Anular', ayuda: 'Ya fue enviado a la mesa. Elige cuántos se anulan; se avisa a su destino de impresión.' }
		};
		function abrirMesaProductos(modo, lineas) {
			if (!mesa || !mesa.id_cotizacion) return;
			if (modo !== 'anular' && pendientesMesa().length) { toastr.warning('Primero envía a la mesa los productos nuevos'); return; }
			mp = { modo: modo, lineas: lineas || $.grep(carro, function (l) { return l.enviada; }), enviando: false };
			var t = MP_TEXTOS[modo];
			$('#mpTitulo').text(t.titulo + (modo === 'anular' ? '' : ' · ' + nombreMesa(mesa.id_mesa)));
			$('#mpAyuda').text(t.ayuda);
			$('#mpConfirmar').text(t.boton).toggleClass('btn-danger', modo === 'anular').toggleClass('btn-primary', modo !== 'anular');
			$('#mpCampoDestino').toggle(modo === 'mover');
			$('#mpCampoMotivo').toggle(modo === 'anular');
			$('#mpMotivo').val('');
			$('#mpResumenTexto').text(modo === 'anular' ? 'Se anula' : (modo === 'mover' ? 'Pasa a la otra mesa' : 'A cobrar ahora'));
			$('#mpLineas').html($.map(mp.lineas, function (l, i) {
				var fraccion = l.cantidad !== Math.round(l.cantidad);
				var inicial = modo === 'parcial' ? (cobroParcial && cobroParcial[l.id_detalle] ? cobroParcial[l.id_detalle] : 0) : l.cantidad;
				return '<div class="mp-linea" data-i="' + i + '"><div class="mp-nombre">' + esc(l.nombre) + (l.agregado ? '<small>+ ' + esc(l.agregado) + '</small>' : '') + (l.nota ? '<small>' + esc(l.nota) + '</small>' : '') + '</div>'
					+ '<div class="input-group input-group-sm"><span class="input-group-btn"><button class="btn btn-default mp-menos" type="button"><i class="fa fa-minus"></i></button></span>'
					+ '<input type="number" class="form-control mp-cant" min="0" max="' + l.cantidad + '" step="' + (fraccion ? '0.001' : '1') + '" value="' + inicial + '">'
					+ '<span class="input-group-btn"><button class="btn btn-default mp-mas" type="button"><i class="fa fa-plus"></i></button></span></div>'
					+ '<div class="mp-de">de ' + fmtCant(l.cantidad) + '</div><div class="mp-monto"></div></div>';
			}).join(''));
			actualizarMp();
			if (modo === 'mover') {
				var $d = $('#mpDestino').html('<option value="">Cargando mesas…</option>');
				cargarMesas().always(function () {
					$d.empty().append('<option value="">Elige una mesa</option>');
					var grupos = {};
					$.each(mesasLista, function (i, m) {
						if (m.id_mesa === mesa.id_mesa || m.estado === 'bloqueada') return;
						if (!grupos[m.recinto]) grupos[m.recinto] = $('<optgroup>').attr('label', m.recinto).appendTo($d);
						grupos[m.recinto].append($('<option>').val(m.id_mesa).text(nombreMesa(m.id_mesa) + (m.con_pedido ? ' · ocupada (' + fmt(m.total) + ')' : ' · libre')));
					});
				});
			}
			$('#modalMesaProductos').modal('show');
		}
		function cantidadesMp() {
			var res = [];
			$('#mpLineas .mp-linea').each(function () {
				var l = mp.lineas[$(this).data('i')];
				var q = Math.max(0, Math.min(l.cantidad, Number(String($(this).find('.mp-cant').val()).replace(',', '.')) || 0));
				res.push({ linea: l, cantidad: Math.round(q * 1000) / 1000 });
			});
			return res;
		}
		function actualizarMp() {
			var total = 0, hay = false;
			$.each(cantidadesMp(), function (i, c) {
				var monto = c.cantidad >= c.linea.cantidad ? c.linea.bruto : Math.round(c.linea.bruto_exacto * c.cantidad / c.linea.cantidad);
				if (c.cantidad > 0) { total += monto; hay = true; }
				$('#mpLineas .mp-linea').eq(i).find('.mp-monto').text(c.cantidad > 0 ? fmt(monto) : '');
			});
			$('#mpResumenMonto').text(fmt(total));
			$('#mpConfirmar').prop('disabled', !hay);
		}
		$('#mpLineas').on('input change', '.mp-cant', actualizarMp).on('click', '.mp-mas, .mp-menos', function () {
			var $c = $(this).closest('.mp-linea').find('.mp-cant'), l = mp.lineas[$(this).closest('.mp-linea').data('i')];
			var paso = l.cantidad !== Math.round(l.cantidad) ? 0.1 : 1;
			var v = (Number($c.val()) || 0) + ($(this).hasClass('mp-mas') ? paso : -paso);
			$c.val(Math.round(Math.max(0, Math.min(l.cantidad, v)) * 1000) / 1000);
			actualizarMp();
		});
		$('#mpConfirmar').on('click', function () {
			if (!mp || mp.enviando) return;
			var idMesa = mesa.id_mesa;
			var sel = $.grep(cantidadesMp(), function (c) { return c.cantidad > 0; });
			if (!sel.length) return;
			var lineas = $.map(sel, function (c) { return { id_detalle: c.linea.id_detalle, cantidad: c.cantidad }; });
			if (mp.modo === 'parcial') {
				var todo = sel.length === mp.lineas.length && !$.grep(sel, function (c) { return c.cantidad < c.linea.cantidad; }).length;
				cobroParcial = null;
				if (!todo) { cobroParcial = {}; $.each(lineas, function (i, l) { cobroParcial[l.id_detalle] = l.cantidad; }); }
				actualizarModoMesa();
				renderCarro();
				$('#modalMesaProductos').one('hidden.bs.modal', abrirPago).modal('hide');
				return;
			}
			if (mp.modo === 'mover') {
				var destino = Number($('#mpDestino').val());
				if (!destino) { toastr.warning('Elige la mesa a la que pasan los productos'); return; }
				$(this).prop('disabled', true);
				postJson('moverMesa', { id_mesa: idMesa, id_mesa_destino: destino, lineas: lineas }).done(function (r) {
					$('#modalMesaProductos').modal('hide');
					toastr.success('Productos pasados a ' + nombreMesa(destino));
					imprimirComanda(0, r.movidas || [], '', destino, { traslado_desde: idMesa });
					cargarMesas();
					if (r.liberada) { limpiarVenta(); $('#lnkMesas').tab('show'); } else aplicarDetalleMesa(r.detalle);
				}).fail(error).always(function () { $('#mpConfirmar').prop('disabled', false); });
				return;
			}
			var motivo = $.trim($('#mpMotivo').val());
			if (!motivo) { toastr.warning('Indica el motivo de la anulación'); $('#mpMotivo').focus(); return; }
			if (mp.enviando) return;   // doble clic: una sola anulación
			mp.enviando = true;
			$('#modalMesaProductos').modal('hide');
			enviarConAutorizacion(function (aut) {
				return postJson('anularLineaMesa', { id_mesa: idMesa, id_detalle: lineas[0].id_detalle, cantidad: lineas[0].cantidad, motivo: motivo, autorizacion: aut });
			}, function (r) {
				cargarMesas();
				imprimirComanda(0, r.anuladas, '', idMesa, { anulacion: true, motivo: motivo });
				if (r.liberada) { toastr.info(nombreMesa(idMesa) + ' quedó libre'); limpiarVenta(); $('#lnkMesas').tab('show'); return; }
				aplicarDetalleMesa(r.detalle);
			});
		});
		$('#grupoOpcionesMesa').on('click', 'a', function (e) { e.preventDefault(); });
		$('#btnMoverMesa').on('click', function () { abrirMesaProductos('mover'); });
		// Volver a enviar a cocina/barra algo ya pedido (papel cortado, se perdió la comanda...). No cambia el pedido.
		function lineaComanda(l) {
			return { id_producto: l.id_producto, nombre: l.nombre, cantidad: l.cantidad, nota: l.nota || '', agregados: l.agregado ? [l.agregado] : [] };
		}
		$('#lineas').on('click', '.reenviar-linea', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var l = carro[$(this).closest('[data-i]').data('i')];
			imprimirComanda(0, [lineaComanda(l)], '', mesa.id_mesa, { reenvio: true });
		});
		$('#btnReenviarTodo').on('click', function () {
			var enviadas = $.grep(carro, function (l) { return l.enviada; });
			if (enviadas.length) imprimirComanda(0, $.map(enviadas, lineaComanda), '', mesa.id_mesa, { reenvio: true });
		});
		$('#btnCobrarParte').on('click', function () { abrirMesaProductos('parcial'); });
		$('#btnCancelarParcial').on('click', function (e) {
			e.preventDefault();
			cobroParcial = null;
			actualizarModoMesa();
			renderCarro();
		});
		$('#btnPrecuenta').on('click', function () {
			if (pendientesMesa().length) { toastr.warning('Primero envía a la mesa los productos nuevos'); return; }
			var parcial = cobroParcial;
			cobroParcial = null;   // la precuenta es de toda la mesa
			var descuento = totales().descuento;
			cobroParcial = parcial;
			imprimir(base + 'precuenta?id_mesa=' + mesa.id_mesa + '&descuento=' + descuento);
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
					imprimirComanda(0, r.anuladas, '', idMesa, { anulacion: true, motivo: $.trim(motivo) });
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
								? (v.electronico
									? '<button class="btn btn-xs btn-danger devolver" data-id="' + v.id_venta + '" data-anular="1" title="Anula con nota de crédito electrónica (todo o algunos productos)"><i class="fa fa-ban"></i> Anular</button> '
									: '<button class="btn btn-xs btn-warning devolver" data-id="' + v.id_venta + '" title="Devolver productos"><i class="fa fa-undo"></i> Devolver</button> ') : '')
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

		// anularTodo: anular un documento electrónico = nota de crédito por todo lo que queda (se puede ajustar)
		var devAnularTodo = false;
		function abrirDevolucion(idVenta, anularTodo) {
			devAnularTodo = !!anularTodo;
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
				// Anular: queda marcado todo lo que se puede devolver
				if (devAnularTodo && r.disponible > 0) $('#btnDevTodo').click();
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
		$('#tablaVentas').on('click', '.devolver', function () { abrirDevolucion($(this).data('id'), !!$(this).data('anular')); });
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
			$('#lblDiferencia').text(fmt(dif)).css('color', dif === 0 ? '#5E7F3A' : (dif < 0 ? '#C9372C' : '#A66C25'));
			var entregar = (Number($('#efectivoContado').val()) || 0) - (Number($('#fondoSiguiente').val()) || 0);
			$('#lblEntregar').text(fmt(entregar)).css('color', entregar < 0 ? '#C9372C' : '');
		}

		$('#lnkCierre').on('shown.bs.tab', cargarCierre);
		$('#btnInformeX').on('click', function () {
			if (resumen) imprimir(base + 'cierreTicket/' + resumen.id_caja);
		});
		$('#tabCierre').on('input', 'input', calcularDiferencia);

		// ---------------- inventario de insumos al cierre ----------------
		// Los insumos marcados con "Inventario en cierre de turno" se cuentan antes de cerrar la caja: lo contado
		// reemplaza al stock del sistema (lo hace el servidor al cerrar). Aquí solo se pide y se compara.
		var invFilas = [];
		function fmtInv(n) { return String(Math.round(n * 1000) / 1000).replace('.', ','); }
		function numInv(v) { v = String(v).replace(',', '.').trim(); return v === '' || isNaN(Number(v)) || Number(v) < 0 ? null : Number(v); }

		function actualizarFilaInv($tr) {
			var f = invFilas[$tr.data('i')], c = numInv($tr.find('.inv-contado').val());
			var $d = $tr.find('.inv-dif').removeClass('cero mas menos');
			if (c === null) { $d.text('—'); return; }
			var dif = Math.round((c - f.stock) * 1000) / 1000;
			$d.addClass(dif === 0 ? 'cero' : (dif < 0 ? 'menos' : 'mas')).text((dif > 0 ? '+' : '') + fmtInv(dif));
		}
		function actualizarResumenInv() {
			var faltan = 0, difs = 0;
			$('#invCierreFilas tr[data-i]').each(function () {
				var c = numInv($(this).find('.inv-contado').val());
				if (c === null) { faltan++; return; }
				if (Math.round((c - invFilas[$(this).data('i')].stock) * 1000) !== 0) difs++;
			});
			$('#invCierreResumen').toggleClass('listo', faltan === 0)
				.text(faltan ? 'Faltan ' + faltan + ' por contar' : (difs ? difs + ' con diferencia' : 'Sin diferencias'));
		}

		$('#invCierreFilas').on('input', '.inv-contado', function () {
			$(this).removeClass('falta');
			actualizarFilaInv($(this).closest('tr'));
			actualizarResumenInv();
		}).on('keydown', '.inv-contado', function (e) {
			if (e.which !== 13) return;
			e.preventDefault();
			var $sig = $('#invCierreFilas .inv-contado').eq($('#invCierreFilas .inv-contado').index(this) + 1);
			if ($sig.length) $sig.focus().select(); else $('#btnInvCierreContinuar').click();
		});

		// Pide el inventario (si hay insumos marcados) y llama a continuar(inventario) con {clave: cantidad};
		// sin insumos marcados continúa directo con {}. Cancelar el cuadro no cierra nada.
		// No se cierra la caja con mesas abiertas (el servidor también lo impide)
		function pedirInventarioCierre(continuar) {
			cargarMesas().always(function () {
				var abiertas = $.grep(mesasLista, function (m) { return m.con_pedido; });
				if (abiertas.length) {
					swal('Hay mesas abiertas', 'Cobra o anula el pedido de: ' + $.map(abiertas, function (m) { return nombreMesa(m.id_mesa); }).join(', ') + ', antes de cerrar la caja.', 'warning');
					return;
				}
				pedirInventarioCierreTras(continuar);
			});
		}
		function pedirInventarioCierreTras(continuar) {
			get('inventarioCierre').done(function (r) {
				invFilas = r.insumos || [];
				if (!invFilas.length) { continuar({}); return; }
				var html = '', bodega = null;
				$.each(invFilas, function (i, f) {
					if (f.bodega !== bodega) { bodega = f.bodega; html += '<tr class="inv-bodega"><td colspan="4"><i class="fa fa-archive"></i> ' + esc(f.bodega) + '</td></tr>'; }
					html += '<tr data-i="' + i + '"><td><span class="inv-nombre">' + esc(f.nombre) + '</span>' + (f.umedida ? '<span class="inv-unidad">' + esc(f.umedida) + '</span>' : '') + '</td>'
						+ '<td class="inv-stock">' + fmtInv(f.stock) + '</td>'
						+ '<td><input type="text" inputmode="decimal" autocomplete="off" class="form-control inv-contado" placeholder="0"></td>'
						+ '<td class="inv-dif">—</td></tr>';
				});
				$('#invCierreFilas').html(html);
				var listo = null;
				$('#btnInvCierreContinuar').off('click').on('click', function () {
					var inv = {}, primero = null;
					$('#invCierreFilas tr[data-i]').each(function () {
						var $in = $(this).find('.inv-contado'), c = numInv($in.val());
						if (c === null) { $in.addClass('falta'); if (!primero) primero = $in; return; }
						inv[invFilas[$(this).data('i')].clave] = Math.round(c * 1000) / 1000;
					});
					if (primero) { primero.focus(); toastr.warning('Ingresa la cantidad contada de todos los insumos (0 si no queda nada)'); return; }
					listo = inv;
					$('#modalInventarioCierre').modal('hide');
				});
				$('#modalInventarioCierre').off('shown.bs.modal hidden.bs.modal')
					.on('shown.bs.modal', function () { $('#invCierreFilas .inv-contado').first().focus(); })
					// Se continúa recién al terminar de cerrar el cuadro, para no encimar el aviso de confirmación
					.on('hidden.bs.modal', function () { if (listo) { var inv = listo; listo = null; continuar(inv); } })
					.modal('show');
				actualizarResumenInv();
			});
		}

		$('#btnCerrarCaja').on('click', function () {
			var efectivo = $('#efectivoContado').val();
			if (efectivo === '' || Number(efectivo) < 0) { swal('Atención', 'Ingresa el efectivo contado', 'warning'); return; }
			var fondoSig = $('#fondoSiguiente').val();
			if (fondoSig === '' || Number(fondoSig) < 0) { swal('Atención', 'Ingresa el fondo que queda para el próximo turno', 'warning'); return; }
			if (Number(fondoSig) > Number(efectivo)) { swal('Atención', 'El fondo para el próximo turno no puede superar el efectivo contado', 'warning'); return; }
			var datos = { efectivo_contado: Math.round(Number(efectivo)), fondo_siguiente: Math.round(Number(fondoSig)) };
			$('.declarado').each(function () { datos['declarado[' + $(this).data('id') + ']'] = Math.round(Number($(this).val()) || 0); });
			pedirInventarioCierre(function (inventario) {
			$.each(inventario, function (clave, cant) { datos['inventario[' + clave + ']'] = cant; });
			var conInventario = !$.isEmptyObject(inventario);
			swal({
				title: '¿Cerrar la caja?',
				text: 'Diferencia: ' + $('#lblDiferencia').text() + '. Después de cerrar no podrás registrar más ventas en esta caja.'
					+ (conInventario ? ' El stock de los insumos contados quedará igual a lo que ingresaste.' : ''),
				type: 'warning',
				showCancelButton: true,
				closeOnConfirm: false,
				confirmButtonText: 'Cerrar caja',
				cancelButtonText: 'Cancelar'
			}, function (ok) {
				if (!ok || cerrandoCaja) return;
				cerrandoCaja = true;
				var idCaja = resumen.id_caja;
				var cierreTermica = catalogo && catalogo.config.imprimir_cierre_termica;
				post('cerrar', datos).always(function () { cerrandoCaja = false; }).done(function (r) {
					// Como el POS: si Configuraciones lo indica, el cierre sale también en la térmica
					if (cierreTermica) imprimir(base + 'cierreTicket/' + idCaja);
					var inv = r.inventario && r.inventario.insumos
						? ' Inventario: ' + r.inventario.insumos + ' insumo(s) actualizado(s)' + (r.inventario.con_diferencia ? ', ' + r.inventario.con_diferencia + ' con diferencia.' : ', sin diferencias.') : '';
					swal({ title: 'Caja cerrada', text: 'Entregar ' + fmt(r.entregado) + ' en efectivo. Quedan ' + fmt(r.fondo_siguiente)
						+ ' de fondo para el próximo turno. Diferencia: ' + fmt(r.diferencia) + '.' + inv, type: 'success' }, function () {
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
		});

		cargarEstado();
	})();
</script>
