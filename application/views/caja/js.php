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
		var modoCaja = 'ambos';  // Configuraciones: 'directa' | 'mesas' | 'ambos'
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
		function imprimir(url) {
			$('#frameImpresion').remove();
			$('<iframe id="frameImpresion" class="caja-frame-impresion" aria-hidden="true">')
				.attr('src', url + (url.indexOf('?') < 0 ? '?' : '&') + 'imprimir=1').appendTo('body');
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
		function imprimirTicket(idVenta, despues) {
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
			imprimirTicket(Number(m[1]));
		});
		function avisoNoImpreso(texto) {
			toastr.error(texto + ' Cuando esté listo, reimprímelo en Opciones > Documentos.', 'No se imprimió el documento',
				{ timeOut: 15000, extendedTimeOut: 5000, closeButton: true });
		}
		function imprimirTicketLocal(idVenta, despues) {
			var fin = function () { if (despues) despues(); };
			if (!window.ImpresionLocal) {
				avisoNoImpreso('No se cargó el cliente de impresión local: recarga la Caja.');
				fin();
				return;
			}
			$.ajax({ url: base + 'ticketLocal', type: 'POST', dataType: 'json', data: { id_venta: idVenta } })
				.done(function (r) {
					ImpresionLocal.imprimir(r.destino, r.operaciones).then(function (res) {
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
		function imprimirComanda(comandaId, que) {
			if (!comandaId) return;
			if (impresionLocalActiva()) { imprimirComandaLocal(comandaId, que); return; }
			$.ajax({
				url: base + 'imprimirComanda', type: 'POST', dataType: 'json', data: { comanda_id: comandaId }
			}).done(function (r) {
				var com = r.comandas || [];
				$.each(com, function (i, c) {
					if (c.ok) toastr.success((que || 'Comanda') + ' enviada a ' + c.destino, '', { timeOut: 2500 });
					else toastr.error('Comanda de ' + c.destino + ': ' + c.detalle, '', { timeOut: 9000 });
				});
			}).fail(function (x) {
				toastr.error('No se pudo enviar la comanda: ' + ((x.responseJSON && x.responseJSON.error) || 'error de conexión'), '', { timeOut: 9000 });
			});
		}
		// Impresión local (12-11-2026): las impresoras están en el local y el sistema en un servidor, así que cada comanda va
		// al agente de impresión de este equipo (localhost:5050), una por destino de impresión de los productos (cocina,
		// barra...) con todo lo de la venta o mesa junto, una tras otra. Si salen todas, el servidor la descarta.
		function imprimirComandaLocal(comandaId, que) {
			que = que || 'Comanda';
			if (!window.ImpresionLocal) { toastr.error('No se cargó el cliente de impresión local: recarga la Caja.', que + ' sin imprimir', { timeOut: 15000, closeButton: true }); return; }
			$.ajax({ url: base + 'comandaLocal', type: 'POST', dataType: 'json', data: { comanda_id: comandaId } }).done(function (r) {
				var grupos = r.comandas || [], i = 0, fallas = 0;
				(function siguiente() {
					if (i >= grupos.length) {
						if (!fallas) $.ajax({ url: base + 'comandaImpresa', type: 'POST', dataType: 'json', data: { comanda_id: comandaId }, global: false });
						return;
					}
					var g = grupos[i++];
					ImpresionLocal.imprimir(g.destino, g.operaciones).then(function (res) {
						toastr.success(que + ' enviada a ' + g.nombre + ' (' + res.impresora + ')', '', { timeOut: 2500 });
						siguiente();
					}, function (e) {
						fallas++;
						toastr.error(textoPlano(ImpresionLocal.mensaje(e)), que + ' de ' + g.nombre + ' sin imprimir', { timeOut: 15000, closeButton: true });
						siguiente();
					});
				})();
			}).fail(function (x) {
				toastr.error((x.responseJSON && x.responseJSON.error) || 'error de conexión', 'No se pudo preparar la ' + que.toLowerCase(), { timeOut: 15000, closeButton: true });
			});
		}
		// Reenviar a cocina lo ya guardado en la mesa: idsDetalle = líneas (vacío = todo el pedido)
		function reenviarComanda(idMesa, idsDetalle) {
			postJson('reenviarComanda', { id_mesa: idMesa, ids_detalle: idsDetalle || [] }).done(function (r) {
				imprimirComanda(r.comanda_id, 'Reenvío');
			}).fail(error);
		}

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
						? '<i class="fa fa-info-circle"></i>Esta sucursal no tiene cajas de dat@Resto autorizadas en la intranet.' : ''));
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
		var catalogoPedido = 0;
		function cargarCatalogo(idLista) {
			var pedido = ++catalogoPedido;
			return get('catalogo', { id_lprecio: idLista }).done(function (r) {
				if (pedido !== catalogoPedido) return;   // llegó después de otro pedido más nuevo: se ignora
				modoCaja = r.modo_caja || 'ambos';
				aplicarModoCaja();
				var primeraVez = !catalogo;
				catalogo = r;
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
				var enFamilia = familiaActiva === -1 ? p.favorito : (!familiaActiva || p.id_familia === familiaActiva);
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
			if (tieneOpciones(id)) abrirOpciones(id, null);
			else if (!mesa && pideValorOPeso(id)) abrirPesable(id);   // a granel en venta directa: por valor o por peso
			else agregar(id, [], '');
		}

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

		// Cambiar la lista de precios: la pública se usa directo; otra pide la clave de administración (Configuraciones).
		// Si se cancela o la clave no es válida, vuelve a la lista que estaba.
		var listaActual = null;
		// Control de cajas: una caja sin "Cambiar lista en caja" trabaja solo con su lista; en una mesa con pedido vale la del pedido
		function listaFija() {
			return !!(catalogo && catalogo.config && catalogo.config.caja_fija_lista);
		}
		function listaBloqueada() {
			return !!(mesa && mesa.id_cotizacion) || listaFija();
		}
		$('#cboLista').on('change', function () {
			var $s = $(this), nueva = Number($s.val()), anterior = listaActual;
			$s.prop('disabled', true);
			enviarConAutorizacion(function (aut) {
				return postJson('autorizarLista', { id_lprecio: nueva, autorizacion: aut });
			}, function () {
				$s.prop('disabled', listaBloqueada());
				cargarCatalogo(nueva);
			}, function () {
				$s.val(anterior).prop('disabled', listaBloqueada());
			});
		});
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
			if (cobroParcial || dcCobro) {
				cobroParcial = null;
				dcCobro = null;
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
			$('#btnEnviarMesa, #btnEnviarMesaBarra').prop('disabled', !pendientesMesa().length);
			botonesMesa();
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
			imprimirTicket(id, function () { $b.prop('disabled', false); });
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
			$('.fav-vista-btn').each(function () {
				var on = $(this).data('fav-vista') === favVista;
				$(this).toggleClass('is-activo', on).attr('aria-pressed', on ? 'true' : 'false');
			});
			var enVenta = {};
			$.each(carro, function (i, l) { enVenta[l.id_producto] = (enVenta[l.id_producto] || 0) + l.cantidad; });
			var html = '';
			$.each(favs, function (i, p) {
				if (elegida && p.id_familia !== elegida) return;
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
				+ (html || favEstado('fa-filter', 'Sin favoritos en esta familia', '')) + '</div>');
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
		$('#favFamilia').on('change', renderFavoritos);
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
			carro = [];
			mesa = null;
			cobroParcial = null;
			dcCobro = null;
			actualizarModoMesa();
			cliente = null;
			datosFactura = null;
			$('#txtCliente').val('');
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
			if (!carro.length) return;
			swal({ title: '¿Vaciar la venta?', type: 'warning', showCancelButton: true, confirmButtonText: 'Sí, vaciar', cancelButtonText: 'No' },
				function (ok) {
					if (!ok) return;
					// Auditoría de caja: venta directa vaciada con sus productos
					auditar('venta_vaciada', { lineas: lineasParaGuardar(carro), monto: totales().total, cliente: cliente ? cliente.nombre_cliente : '' });
					limpiarVenta();
				});
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
			if (!cliente || !c || cliente.id_cliente !== c.id_cliente) datosFactura = null;
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
			var soloClaveGeneral = /^usar la lista de precios/.test(r.motivo || '') && catalogo.config.tiene_clave_admin;
			if (catalogo.config.tiene_clave_admin) $c.append($('<option>').val(0).text('Clave de administración'));
			if (!soloClaveGeneral) $.each(catalogo.administradores, function (i, a) { $c.append($('<option>').val(a.id_usuario).text(a.nombre)); });
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
		function propinaMaxima() {
			return Math.floor(totales().total * (catalogo.config.propina_max_pct || 15) / 100);
		}
		function propinaSugerida() {
			return Math.min(Math.round(totales().total * catalogo.config.por_propina / 100), propinaMaxima());
		}

		$('#btnCobrar').on('click', function () {
			// En una mesa solo se cobra lo ya enviado
			if (mesa && pendientesMesa().length) { toastr.warning('Primero envía a la mesa los productos nuevos'); return; }
			abrirPago();
		});
		// Antes del pago se pregunta por la propina (si corresponde): Sí = monto sugerido o el que se escriba, No = 0
		function abrirPago(conFactura) {
			var cfg = catalogo.config;
			if (!validarCobro()) return;
			// Factura: primero sus datos (giro, dirección, quién la solicita); después la propina y el pago
			if (conFactura !== true && documentoActual().es_factura && cliente) { pedirDatosFactura(function () { abrirPago(true); }); return; }
			if (!mesa) { mostrarPago(0); return; }   // la propina es solo para las mesas
			var sugerida = propinaSugerida();
			$('#propinaPregunta').text(fmt(totales().total));
			$('#propinaPorcentaje').text((cfg.por_propina > 0 ? 'Sugerida: ' + cfg.por_propina + '% (' + fmt(sugerida) + ')' : 'Escribe el monto de la propina')
				+ ' · Máximo ' + (cfg.propina_max_pct || 15) + '% (' + fmt(propinaMaxima()) + ')');
			$('#txtPropinaPregunta').val(sugerida || '');
			$('#modalPropina').modal('show');
		}
		$('#modalPropina').on('shown.bs.modal', function () { $('#btnPropinaSi').focus(); });

		// ---------------- datos de la factura ----------------
		// Al cobrar con factura (venta directa, mesa o una parte de la mesa) se piden razón social, giro, dirección, comuna,
		// contacto y quién la solicita (RUT y nombre). Se proponen los de la ficha del cliente o los de sus facturas anteriores;
		// el servidor los guarda con la venta (venta_receptor) y la emisión y el ticket usan esos datos. Si la base aún no tiene
		// esa tabla (migración pendiente), la factura sale con la ficha del cliente, como antes.
		var datosFactura = null, facInfo = null, facListo = null, facConfirmado = false;
		function rutTexto(rut, dv) { return rut ? rut + '-' + String(dv || '').toUpperCase() : ''; }
		function rutValido(texto) {
			var t = String(texto || '').replace(/[^0-9kK]/g, '').toUpperCase();
			if (t.length < 2) return false;
			var cuerpo = t.slice(0, -1), dv = t.slice(-1), suma = 0, mult = 2;
			for (var i = cuerpo.length - 1; i >= 0; i--) { suma += Number(cuerpo.charAt(i)) * mult; mult = mult === 7 ? 2 : mult + 1; }
			var r = 11 - (suma % 11);
			return dv === (r === 11 ? '0' : (r === 10 ? 'K' : String(r)));
		}
		// Listas del cliente (12-11-2026): giro, dirección (sucursal) y solicitante se eligen de la ficha del cliente (Clientes >
		// Datos para factura) y de sus facturas anteriores. "Otro…" muestra los campos para escribirlo; lo nuevo se agrega a la
		// ficha al vender (salvo que se desmarque "Guardar en la ficha"). Los campos de texto siguen siendo los que se envían.
		function facClave(t) { return $.trim(String(t || '')).toUpperCase(); }
		function facOrigen(o) { return o === 'ficha' ? ' (ficha)' : (o === 'anterior' ? ' (factura anterior)' : ''); }
		function rutConPuntos(rut, dv) { return rut ? String(rut).replace(/\B(?=(\d{3})+(?!\d))/g, '.') + '-' + String(dv || '').toUpperCase() : ''; }
		function facListas(r) {
			var $g = $('#facGiroSel').empty();
			$.each(r.giros || [], function (i, g) { $g.append($('<option>').val(i).text(g.giro + facOrigen(g.origen))); });
			$g.append($('<option>').val('otro').text('Otro giro…'));
			var $d = $('#facDirSel').empty();
			$.each(r.direcciones || [], function (i, d) {
				$d.append($('<option>').val(i).text($.grep([d.direccion, d.comuna, d.ciudad], Boolean).join(', ') + facOrigen(d.origen)));
			});
			$d.append($('<option>').val('otro').text('Otra dirección…'));
			var $s = $('#facSolSel').empty();
			if ((r.solicitantes || []).length) $s.append($('<option>').val('').text('Elige quién solicita…'));
			$.each(r.solicitantes || [], function (i, x) { $s.append($('<option>').val(i).text(rutConPuntos(x.rut, x.dv) + ' · ' + x.nombre)); });
			$s.append($('<option>').val('otro').text('Otra persona…'));
		}
		// Deja cada lista en el valor que tienen los campos (o en "Otro…" si no está en la lista)
		function facSincronizar() {
			if (!facInfo) return;
			var giro = facClave($('#facGiro').val()), gi = -1;
			$.each(facInfo.giros || [], function (i, g) { if (gi < 0 && facClave(g.giro) === giro) gi = i; });
			$('#facGiroSel').val(gi >= 0 ? String(gi) : 'otro');
			$('#facGiro').prop('hidden', gi >= 0);
			var dir = facClave($('#facDireccion').val()), com = Number($('#facComuna').val()) || 0, di = -1;
			$.each(facInfo.direcciones || [], function (i, d) { if (di < 0 && facClave(d.direccion) === dir && Number(d.id_comuna) === com) di = i; });
			$('#facDirSel').val(di >= 0 ? String(di) : 'otro');
			$('#facDirOtra').prop('hidden', di >= 0);
			var rut = $('#facRutSol').val().replace(/[^0-9kK]/g, '').toUpperCase(), si = -1;
			$.each(facInfo.solicitantes || [], function (i, x) { if (si < 0 && rut && String(x.rut) + String(x.dv).toUpperCase() === rut) si = i; });
			var sinElegir = !rut && (facInfo.solicitantes || []).length;
			$('#facSolSel').val(si >= 0 ? String(si) : (sinElegir ? '' : 'otro'));
			$('#facSolOtro').prop('hidden', si >= 0 || sinElegir);
		}
		function facLlenar(d) {
			$('#facRazon').val(d.razon_social || '');
			$('#facGiro').val(d.giro || '');
			$('#facDireccion').val(d.direccion || '');
			$('#facComuna').val(String(d.id_comuna || 0));
			if (!$('#facComuna').val()) $('#facComuna').val('0');
			$('#facCiudad').val(d.ciudad || '');
			$('#facContacto').val(d.contacto || '');
			facSincronizar();
		}
		function pedirDatosFactura(listo) {
			$.ajax({ url: base + 'receptorCliente', data: { id_cliente: cliente.id_cliente }, dataType: 'json', cache: false }).done(function (r) {
				if (!r.disponible) { datosFactura = null; listo(); return; }   // sin la tabla: con la ficha del cliente, como antes
				facInfo = r;
				var c = r.cliente;
				$('#facRut').text(rutTexto(c.rut, c.dv) + ' · ' + c.razon_social);
				$('#facComuna').html($('#cboComuna').html());
				var $p = $('#facPrevios').empty().append($('<option>').val('ficha').text('Ficha del cliente' + (c.giro || c.direccion ? ': ' + $.grep([c.giro, c.direccion, c.comuna], Boolean).join(' · ') : '')));
				$.each(r.anteriores, function (i, a) {
					$p.append($('<option>').val(i).text('Factura del ' + a.fecha + ': ' + $.grep([a.giro, a.direccion, a.comuna], Boolean).join(' · ')));
				});
				$('#facPreviosGrupo').prop('hidden', !r.anteriores.length);
				facListas(r);
				$('#facGuardarGrupo').prop('hidden', !r.guardar_ficha);
				// Lo ya elegido en esta venta (por ejemplo, si se canceló el pago); si no, la última factura del cliente o su ficha
				var mismo = datosFactura && datosFactura.id_cliente === c.id_cliente;
				$('#facRutSol').val(mismo ? datosFactura.rut_solicita : '');
				$('#facNombreSol').val(mismo ? datosFactura.nombre_solicita : '');
				$('#facMotivo').val(mismo ? (datosFactura.motivo || '') : '');
				$('#facGuardarFicha').prop('checked', mismo ? datosFactura.guardar_ficha !== false : true);
				if (mismo) facLlenar(datosFactura);
				else if (r.anteriores.length) { facLlenar(r.anteriores[0]); $p.val('0'); }
				else facLlenar(c);
				$('#frmFactura .has-error').removeClass('has-error');
				facListo = listo;
				facConfirmado = false;
				$('#modalFactura').modal('show');
			}).fail(error);
		}
		$('#facPrevios').on('change', function () {
			var v = $(this).val();
			if (!facInfo) return;
			facLlenar(v === 'ficha' ? facInfo.cliente : facInfo.anteriores[Number(v)]);
		});
		$('#facGiroSel').on('change', function () {
			var v = $(this).val(), g = facInfo && facInfo.giros[Number(v)];
			$('#facGiro').val(v === 'otro' || !g ? '' : g.giro).prop('hidden', v !== 'otro');
			if (v === 'otro') $('#facGiro').focus();
		});
		$('#facDirSel').on('change', function () {
			var v = $(this).val(), d = facInfo && facInfo.direcciones[Number(v)];
			$('#facDirOtra').prop('hidden', v !== 'otro');
			if (v === 'otro' || !d) {
				$('#facDireccion').val('');
				$('#facComuna').val('0');
				$('#facCiudad').val('');
				$('#facDireccion').focus();
				return;
			}
			$('#facDireccion').val(d.direccion);
			$('#facComuna').val(String(d.id_comuna || 0));
			if (!$('#facComuna').val()) $('#facComuna').val('0');
			$('#facCiudad').val(d.ciudad || '');
			$('#facDirOtra .has-error, #facCiudad').closest('.form-group').removeClass('has-error');
		});
		$('#facSolSel').on('change', function () {
			var v = $(this).val(), x = facInfo && facInfo.solicitantes[Number(v)];
			$('#facSolOtro').prop('hidden', v !== 'otro');
			$('#facRutSol').val(x && v !== 'otro' && v !== '' ? rutTexto(x.rut, x.dv) : '');
			$('#facNombreSol').val(x && v !== 'otro' && v !== '' ? x.nombre : '');
			$('#facSolOtro .has-error').removeClass('has-error');
			if (v === 'otro') $('#facRutSol').focus();
		});
		// RUT de un solicitante anterior: se completa su nombre
		$('#facRutSol').on('input change', function () {
			var t = $(this).val().replace(/[^0-9kK]/g, '').toUpperCase();
			var s = facInfo && $.grep(facInfo.solicitantes, function (x) { return String(x.rut) + String(x.dv).toUpperCase() === t; })[0];
			if (s && !$.trim($('#facNombreSol').val())) $('#facNombreSol').val(s.nombre).closest('.form-group').removeClass('has-error');
		});
		$('#modalFactura').on('shown.bs.modal', function () {
			var vacio = $('#frmFactura').find('#facGiro, #facDireccion, #facRutSol, #facNombreSol').filter(':visible').filter(function () { return !$.trim($(this).val()); }).first();
			if (!vacio.length && $('#facSolSel').val() === '') vacio = $('#facSolSel');
			(vacio.length ? vacio : $('#btnFacturaContinuar')).focus();
		}).on('hidden.bs.modal', function () {
			var f = facListo, ok = facConfirmado;
			facListo = null;
			facConfirmado = false;
			if (ok && f) f();
		});
		$('#frmFactura').on('submit', function (e) { e.preventDefault(); $('#btnFacturaContinuar').click(); })
			.on('input change', '.form-control', function () { $(this).closest('.form-group').removeClass('has-error'); });
		$('#btnFacturaContinuar').on('click', function () {
			if (!facInfo || !cliente) return;
			var falta = null;
			var exigir = function (sel, msg, ok) {
				var bien = ok !== undefined ? ok : !!$.trim($(sel).val());
				$(sel).closest('.form-group').toggleClass('has-error', !bien);
				if (!bien && !falta) falta = { sel: sel, msg: msg };
			};
			exigir('#facRazon', 'Indica la razón social');
			exigir($('#facGiro').prop('hidden') ? '#facGiroSel' : '#facGiro', 'Indica el giro', !!$.trim($('#facGiro').val()));
			exigir($('#facDirOtra').prop('hidden') ? '#facDirSel' : '#facDireccion', 'Indica la dirección', !!$.trim($('#facDireccion').val()));
			exigir('#facComuna', 'Elige la comuna', Number($('#facComuna').val()) > 0);
			if ($('#facSolSel').val() === '') exigir('#facSolSel', 'Elige quién solicita la factura', false);
			exigir('#facRutSol', 'Indica el RUT de quien solicita la factura (válido)', rutValido($('#facRutSol').val()));
			exigir('#facNombreSol', 'Indica el nombre de quien solicita la factura');
			if (falta) {
				// Un dato de la lista que no sirve (por ejemplo, sin comuna): se muestra para corregirlo
				if (falta.sel === '#facComuna') $('#facDirSel').val('otro'), $('#facDirOtra').prop('hidden', false);
				toastr.warning(falta.msg);
				$(falta.sel).focus();
				return;
			}
			datosFactura = {
				id_cliente: cliente.id_cliente,
				razon_social: $.trim($('#facRazon').val()),
				giro: $.trim($('#facGiro').val()),
				direccion: $.trim($('#facDireccion').val()),
				id_comuna: Number($('#facComuna').val()),
				ciudad: $.trim($('#facCiudad').val()),
				contacto: $.trim($('#facContacto').val()),
				rut_solicita: $.trim($('#facRutSol').val()).toUpperCase(),
				nombre_solicita: $.trim($('#facNombreSol').val()),
				motivo: $.trim($('#facMotivo').val()),
				guardar_ficha: !!facInfo.guardar_ficha && $('#facGuardarFicha').prop('checked')
			};
			facConfirmado = true;
			$('#modalFactura').modal('hide');
		});
		$('#txtPropinaPregunta').on('keydown', function (e) { if (e.which === 13) { e.preventDefault(); $('#btnPropinaSi').click(); } });
		$('#btnPropinaSi, #btnPropinaNo').on('click', function () {
			var monto = this.id === 'btnPropinaSi' ? Math.max(0, Math.round(Number($('#txtPropinaPregunta').val()) || 0)) : 0;
			if (monto > propinaMaxima()) {
				toastr.warning('La propina no puede superar el ' + (catalogo.config.propina_max_pct || 15) + '% del total (' + fmt(propinaMaxima()) + ')');
				$('#txtPropinaPregunta').val(propinaMaxima()).focus().select();
				return;
			}
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
			// Configuraciones > Descuentos > Requerir cliente
			if (catalogo.config.descuento_cliente && t.descuento > 0 && (!cliente || Number(cliente.id_cliente) === catalogo.config.id_cliente_publico)) {
				swal('Falta el cliente', 'Para hacer un descuento debes elegir el cliente de la venta', 'warning');
				return false;
			}
			return true;
		}
		// propinaElegida: monto respondido en el modal de propina; null si no se preguntó
		function mostrarPago(propinaElegida) {
			var t = totales();
			pagos = [];
			$('#grupoRetira').toggle(!mesa);   // quien retira: solo venta directa
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
		// Enter en "quien retira" no registra nada: vuelve al monto (ahí Enter agrega el pago)
		$('#txtRetira').on('keydown', function (e) { if (e.which === 13) { e.preventDefault(); $('#montoPago').focus().select(); } });

		$('#chkPropina').on('change', function () {
			propina = this.checked ? (propinaSugerida() || propina) : 0;
			$('#txtPropina').val(propina).prop('disabled', !this.checked);
			if (this.checked) $('#txtPropina').focus().select();
			ajustarPagoUnico();
		});
		$('#txtPropina').on('input', function () {
			propina = Math.max(0, Math.round(Number($(this).val()) || 0));
			if (propina > propinaMaxima()) {
				propina = propinaMaxima();
				$(this).val(propina);
				toastr.warning('La propina no puede superar el ' + (catalogo.config.propina_max_pct || 15) + '% del total (' + fmt(propina) + ')');
			}
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

		// 14-11-2026: token del cobro. Se repite si se reintenta (doble clic, red lenta) y el servidor no registra dos
		// veces la misma venta; se renueva cuando la venta queda registrada.
		var tokenCobro = null;
		function nuevoTokenCobro() {
			var a = new Uint8Array(16);
			if (window.crypto && window.crypto.getRandomValues) window.crypto.getRandomValues(a);
			else for (var i = 0; i < a.length; i++) a[i] = Math.floor(Math.random() * 256);
			return Array.prototype.map.call(a, function (b) { return ('0' + b.toString(16)).slice(-2); }).join('');
		}

		$('#btnConfirmarPago').on('click', function () {
			if (enviando) return;
			if (!tokenCobro) tokenCobro = nuevoTokenCobro();
			var t = totales();
			var idMesa = mesa ? mesa.id_mesa : 0;
			// Dividir cuenta: si se está cobrando una de sus partes, al registrarse la venta queda marcada como pagada
			var dcEnCobro = dcCobro && mesa && dcCobro.id_mesa === mesa.id_mesa && dcCobro.parcial === cobroParcial ? dcCobro : null;
			var venta = {
				id_mesa: idMesa,
				parcial: idMesa && cobroParcial ? $.map(cobroParcial, function (q, id) { return { id_detalle: Number(id), cantidad: q }; }) : null,
				id_tdocumento: Number($('#cboDocumento').val()),
				id_lprecio: Number($('#cboLista').val()),
				id_cliente: cliente ? cliente.id_cliente : 0,
				descuento: t.descuento,
				propina: esCortesia() ? 0 : propina,
				observacion: $.trim($('#txtObservacion').val()),
				// Venta directa: nombre de quien retira (opcional), va en la comanda y en el documento
				retira: idMesa ? '' : $.trim($('#txtRetira').val()).replace(/\s+/g, ' ').slice(0, 60),
				// Factura: razón social, giro, dirección, comuna y solicitante elegidos en la ventana Datos de la factura
				factura: documentoActual().es_factura && cliente && datosFactura && datosFactura.id_cliente === cliente.id_cliente ? datosFactura : null,
				detalle: $.map(carro, function (l) { return { id_producto: l.id_producto, cantidad: l.cantidad, agregados: l.agregados, nota: l.nota }; }),
				pagos: $.map(pagos, function (p) { return p.monto > 0 ? { id_fpago: p.id_fpago, monto: p.monto } : null; }),
				token: tokenCobro
			};
			enviando = true;
			actualizarResumenPago();
			var terminar = function () { enviando = false; actualizarResumenPago(); };
			enviarConAutorizacion(function (aut) {
				venta.autorizacion = aut;
				return $.ajax({ url: base + 'vender', type: 'POST', contentType: 'application/json', data: JSON.stringify(venta), dataType: 'json' });
			}, function (r) {
				terminar();
				tokenCobro = null;   // venta registrada: el próximo cobro lleva otro token
				if (dcEnCobro) dcRegistrarPago(dcEnCobro, r);
				$('#txtRetira').val('');   // el nombre es de esta venta
				$('#modalPago').modal('hide');
				if (r.total !== t.total) toastr.warning('El total se recalculó con los precios vigentes: ' + fmt(r.total));
				$('#ventaOkTitulo').text('Venta registrada');
				// Mesa cobrada completa: se vuelve al panel de mesas, no a la venta directa
				var mesaCerrada = idMesa && r.mesa_liberada !== false;
				$('#btnNuevaVenta').text(mesaCerrada ? 'Volver a mesas' : 'Nueva venta');
				mostrarResultadoDte(r.dte);
				$('#ventaOkDoc').text(r.documento + (r.folio ? ' N° ' + r.folio : '') + ' · ' + fmt(r.total) + (r.propina > 0 ? ' + propina ' + fmt(r.propina) : ''));
				$('#ventaOkVuelto').text(r.vuelto > 0 ? 'Vuelto: ' + fmt(r.vuelto) : '');
				$('#ventaOkTicket').attr('href', base + 'ticket/' + r.id_venta + '?imprimir=1');
				$('#ventaOkPdf').attr('href', base + 'pdf/' + r.id_venta);
				// Impresión local (12-11-2026): el documento sale solo por el agente del equipo, así que no se muestra
				// "Venta registrada" y la caja queda lista para la siguiente venta (el vuelto queda en el aviso). Si el SII no
				// recibió el documento o este equipo no imprime, se muestra la ventana como antes.
				var sinVentana = impresionLocalActiva() && imprimeTicket() && !(r.dte && !r.dte.ok);
				if (sinVentana) {
					// toastr de la caja escapa el HTML: el vuelto va en el título (en negrita)
					if (r.vuelto > 0) toastr.success('Venta registrada · ' + $('#ventaOkDoc').text(), 'Vuelto: ' + fmt(r.vuelto), { timeOut: 8000 });
					else toastr.success($('#ventaOkDoc').text(), 'Venta registrada', { timeOut: 3500 });
				} else {
					$('#modalVentaOk').modal('show');
				}
				// La comanda sale después de la boleta (en la cola del agente quedan en ese orden).
				// En una mesa la comanda ya salió al enviar el pedido.
				var comandaVenta = imprimeComanda() && !idMesa ? r.comanda_id : null;
				var conComanda = function () { if (comandaVenta) imprimirComanda(comandaVenta, 'Comanda'); };
				if (imprimeTicket()) imprimirTicket(r.id_venta, conComanda);
				else conComanda();
				limpiarVenta();
				cargarEstado();
				// Se cobró una parte: la mesa sigue abierta con el resto
				if (idMesa && r.mesa_liberada === false) { cargarMesas(); abrirMesa(idMesa); }
				else if (mesaCerrada) { cargarMesas(); $('#lnkMesas').tab('show'); }
				// Sin la ventana: lo mismo que al cerrarla (foco en el buscador; dividir cuenta sigue con la parte siguiente)
				if (sinVentana) $('#modalVentaOk').triggerHandler('hidden.bs.modal');
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
				// Configuraciones > Modo de la caja web cambió con la caja abierta: se aplica al tiro, salvo que haya una venta
				// o una mesa a medio armar (se aplica en la próxima revisión, cuando ya no la haya)
				if (r.modo && r.modo !== modoCaja && !hayVentaEnCurso() && !drawerMesaAbierto()) { modoCaja = r.modo; aplicarModoCaja(); }
				$('#navMesas').toggle(!!r.habilitado);
				// Venta directa con mesas de antes: la pestaña solo muestra esas mesas, para cobrarlas o anularlas
				var soloPendientes = !!(r.habilitado && r.solo_pendientes);
				$('#lnkMesasTexto').text(soloPendientes ? 'Mesas por cobrar' : 'Mesas y recintos');
				$('.caja-mesas').toggleClass('solo-pendientes', soloPendientes);
				$('#mesasAyuda').text(soloPendientes
					? 'La caja está en modo venta directa (Configuraciones): quedan estas mesas con pedido para cobrarlas o anularlas. No se abren mesas nuevas.'
					: 'Toca una mesa libre para tomar el pedido, o una con pedido para agregar productos, pedir la precuenta o cobrar.');
				// Sin mesas (modo venta directa y ya se cobraron todas): de vuelta a la venta
				if (!r.habilitado && $('#tabMesas').hasClass('active') && !drawerMesaAbierto()) $('#lnkVenta').tab('show');
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
				var $c = $('<button type="button" class="mesa-card">').addClass(m.estado).toggleClass('con-pedido', !!m.con_pedido).toggleClass('actual', !!mesa && mesa.id_mesa === m.id_mesa)
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
			$('#btnActualizarMesas').toggle(!activo);
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
		// Al volver a esta pestaña del navegador (por ejemplo, después de cambiar Configuraciones en otra) se revisa el modo de la caja
		document.addEventListener('visibilitychange', function () { if (!document.hidden && resumen) cargarMesas(); });
		// 14-11-2026: cada 5 s (antes 20) mientras se ven las mesas, para que un pedido de la app de garzones o de otra caja
		// aparezca sin recargar la página; además el botón Actualizar lo hace al tiro.
		var cargandoMesas = false;
		function refrescarMesas() {
			if (cargandoMesas || editandoPlano) return $.Deferred().resolve().promise();
			cargandoMesas = true;
			return cargarMesas().always(function () { cargandoMesas = false; });
		}
		setInterval(function () { if (!document.hidden && $('#mesasGrilla').is(':visible')) refrescarMesas(); }, 5000);
		$('#btnActualizarMesas').on('click', function () {
			var $i = $(this).find('.fa').addClass('fa-spin'), $b = $(this).prop('disabled', true);
			refrescarMesas().always(function () { $i.removeClass('fa-spin'); $b.prop('disabled', false); });
		});
		// 14-11-2026: comandas de la app de garzones (Configuraciones > Imprimir las comandas de la app). Con la caja
		// abierta se pregunta cada 10 s si llegaron pedidos de la app sin imprimir; cada uno sale como una comanda más,
		// cada producto en su destino de impresión. Con dos cajas abiertas cada pedido lo imprime una sola.
		var buscandoComandasApp = false;
		setInterval(function () {
			if (buscandoComandasApp || document.hidden || !resumen || !(catalogo && catalogo.config && catalogo.config.comandas_app)) return;
			buscandoComandasApp = true;
			$.ajax({ url: base + 'comandasApp', type: 'POST', dataType: 'json', global: false }).done(function (r) {
				var com = (r && r.comandas) || [];
				$.each(com, function (i, c) { imprimirComanda(c.comanda_id, 'Comanda app ' + (c.mesa || ('mesa ' + c.id_mesa))); });
				if (com.length && $('#mesasGrilla').is(':visible')) cargarMesas();
			}).always(function () { buscandoComandasApp = false; });
		}, 10000);
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

		// Modo Mesas: la pestaña Venta solo existe dentro de una mesa; sin mesa abierta se trabaja en la grilla
		function aplicarModoCaja() {
			var soloMesas = modoCaja === 'mesas';
			// Las mesas se trabajan en su drawer: en modo solo mesas la pestaña Venta no se muestra nunca
			$('#lnkVenta').parent().toggle(!soloMesas);
			// Sin la venta a la vista tampoco se ve su panel Opciones: Ingresos / Retiros, Créditos y Cierre quedan arriba
			$('.caja-tabs').toggleClass('mostrar-opciones', soloMesas);
			if (soloMesas && !$('#tabMesas').hasClass('active') && $('#tabVenta').hasClass('active')) $('#lnkMesas').tab('show');
		}
		function actualizarModoMesa() {
			aplicarModoCaja();
			$('#bannerMesa').toggle(!!mesa);
			// Mesa sin pedido: se oculta el panel lateral (no hay nada que cobrar) y el envío queda junto al total
			$('.caja-venta').toggleClass('mesa-sin-pedido', !!(mesa && !mesa.id_cotizacion));
			// Modo venta directa: una mesa con pedido de antes solo se cobra o se anula (el servidor no acepta productos nuevos)
			$('.caja-venta').toggleClass('mesa-modo-directa', !!(mesa && modoCaja === 'directa'));
			botonesMesa();
			$('#cboLista').prop('disabled', listaBloqueada());
			if (!mesa) return;
			// Sin pedido el panel de la mesa es más angosto (ancho propio de cada caso, ver "ancho del drawer de mesa")
			$('#mesaDrawer').toggleClass('sin-pedido', !mesa.id_cotizacion);
			aplicarAnchoMesa();
			aplicarFavMesa();   // con Favoritos abierto en una mesa sin pedido, el panel de la mesa va con su ancho con pedido
			$('#bannerMesaNum').text(nombreMesa(mesa.id_mesa));
			// Opciones de la mesa: se usan cuando ya hay un pedido enviado
			$('#opcionesMesa .opcion-mesa').prop('disabled', !mesa.id_cotizacion);
			$('#opcionesMesaAyuda').toggle(!mesa.id_cotizacion);
			var mAtiende = $.grep(mesasLista, function (x) { return x.id_mesa === mesa.id_mesa; })[0];
			$('#opcionGarzonActual').text(mAtiende && mAtiende.atiende ? 'Atiende ' + mAtiende.atiende : 'Quién atiende la mesa');
			$('#avisoCobroParcial').toggle(!!(cobroParcial || dcCobro));
			$('#avisoCobroParcialTexto').text(dcCobro ? 'Cobrando: ' + dcCobro.nombre : 'Cobrando una parte');
			if (!mesa.inicio) $('#bannerMesaTiempo').text('Sin enviar');
			ajustarAltoVenta();
		}
		// Lo enviado viene del servidor; lo que aún no se envía queda en el carro
		function aplicarDetalleMesa(d) {
			cobroParcial = null;
			dcCobro = null;
			mesa = { id_mesa: d.id_mesa, id_cotizacion: d.id_cotizacion, id_garzon: d.id_garzon || 0, inicio: d.id_cotizacion ? Date.now() - d.segundos * 1000 : 0 };
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
				// La venta directa en curso no pasa a la mesa: se guarda y vuelve al cerrar la mesa
				if (!mesa) guardarVentaDirecta();
				if (descartar) {
					auditarOmitidos('cambio_mesa');   // lo no enviado de la mesa anterior se pierde: queda en la auditoría
					carro = [];
				}
				aplicarDetalleMesa(d);
				abrirDrawerMesa();
				if (d.id_lprecio && d.id_lprecio !== Number($('#cboLista').val())) cargarCatalogo(d.id_lprecio);
				$('#buscarProducto').focus();
			});
		}

		// ---------------- drawer de mesa ----------------
		// La venta de una mesa se trabaja en un panel que sale por la derecha, sobre la pestaña Mesas: se mueve ahí
		// .caja-venta (mover nodos conserva ids y eventos, así que todo funciona igual: agregar, enviar, precuenta,
		// cobrar, opciones de la mesa) y vuelve a la pestaña Venta al cerrar. La venta directa que hubiera se guarda
		// al abrir la mesa y se recupera al cerrarla (limpiarVenta).
		var $anclaVenta = $('<span id="anclaVenta" hidden></span>').insertBefore('#tabVenta > .caja-venta');
		var ventaDirectaGuardada = null;
		function drawerMesaAbierto() { return $('#mesaDrawer').hasClass('abierto'); }
		function abrirDrawerMesa() {
			if (!mesa) return;
			var m = $.grep(mesasLista, function (x) { return x.id_mesa === mesa.id_mesa; })[0];
			$('#mesaDrawerNombre').text(nombreMesa(mesa.id_mesa));
			$('#mesaDrawerInfo').text(m ? $.grep([m.recinto, m.atiende ? 'Atiende ' + m.atiende : ''], function (x) { return !!x; }).join(' · ') : '');
			if (!drawerMesaAbierto()) {
				$('#mesaDrawerCuerpo').append($('.caja-venta'));
				if (!$('#tabMesas').hasClass('active')) $('#lnkMesas').tab('show');
				$('#mesaDrawer, #mesaDrawerFondo').addClass('abierto');
				$('#mesaDrawer').attr('aria-hidden', 'false');
				$('body').addClass('mesa-drawer-abierto');
			}
			mostrarPanel('pago');
			ajustarAltoVenta();
		}
		function cerrarDrawerMesa() {
			if (!drawerMesaAbierto()) return;
			$('#mesaDrawer, #mesaDrawerFondo').removeClass('abierto');
			$('#mesaDrawer').attr('aria-hidden', 'true');
			$('body').removeClass('mesa-drawer-abierto');
			// La venta vuelve a su pestaña cuando el drawer terminó de salir (si no se volvió a abrir otra mesa)
			setTimeout(function () {
				if (drawerMesaAbierto()) return;
				$anclaVenta.after($('.caja-venta'));
				ajustarAltoVenta();
			}, 260);
		}
		function guardarVentaDirecta() {
			ventaDirectaGuardada = {
				carro: carro, cliente: cliente, txtCliente: $('#txtCliente').val(), descuento: $('#txtDescuento').val(), tipoDesc: tipoDesc,
				observacion: $('#txtObservacion').val(), documento: $('#cboDocumento').val(), lista: listaActual
			};
			carro = [];
			cliente = null;
			cobroParcial = null;
			$('#txtCliente').val('');
			$('#txtDescuento').val(0);
			$('#txtObservacion').val('');
		}
		// Devuelve true si había una venta directa guardada (y la deja como estaba, con su lista de precios)
		function restaurarVentaDirecta() {
			var v = ventaDirectaGuardada;
			ventaDirectaGuardada = null;
			if (!v) return false;
			carro = v.carro;
			cliente = v.cliente;
			$('#txtCliente').val(v.txtCliente);
			$('#txtDescuento').val(v.descuento);
			tipoDesc = v.tipoDesc;
			$('[data-tipo-desc]').each(function () { $(this).toggleClass('active', $(this).data('tipo-desc') === tipoDesc); });
			$('#txtObservacion').val(v.observacion);
			if (v.documento) $('#cboDocumento').val(v.documento);
			if (v.lista && v.lista !== listaActual) cargarCatalogo(v.lista);   // re-precia con su lista y redibuja
			else renderCarro();
			return true;
		}
		// ---- ancho del drawer de mesa ----
		// Sin pedido va a la mitad (CSS .sin-pedido); con pedido, el de siempre. El borde izquierdo se arrastra (o se mueve con las
		// flechas) para ajustarlo y queda guardado en este computador, por separado para cada caso. Doble clic: vuelve al de siempre.
		var ANCHO_MESA = { sin: { clave: 'cajaWeb.anchoMesaSinPedido', min: 420 }, con: { clave: 'cajaWeb.anchoMesaConPedido', min: 760 } };
		function anchoMesaActual() { return $('#mesaDrawer').hasClass('sin-pedido') ? ANCHO_MESA.sin : ANCHO_MESA.con; }
		function aplicarAnchoMesa() {
			var d = document.getElementById('mesaDrawer');
			if (!d) return;
			if (window.innerWidth < 992) { d.style.width = ''; return; }   // en pantallas angostas ocupa todo el ancho
			var w = 0;
			try { w = Number(localStorage.getItem(anchoMesaActual().clave)) || 0; } catch (x) {}
			d.style.width = w ? w + 'px' : '';
		}
		function fijarAnchoMesa(w, guardar) {
			var d = document.getElementById('mesaDrawer'), $d = $(d), suelto = !$d.hasClass('redimensionando');
			w = Math.round(Math.max(anchoMesaActual().min, Math.min(window.innerWidth - 80, w)));
			$d.addClass('redimensionando');   // sin animación: el ancho queda al tiro (y se puede medir)
			d.style.width = w + 'px';
			// Se guarda el ancho que de verdad quedó (el CSS no deja pasar del espacio disponible)
			if (guardar) try { localStorage.setItem(anchoMesaActual().clave, Math.round(d.getBoundingClientRect().width)); } catch (x) {}
			if (suelto) setTimeout(function () { $d.removeClass('redimensionando'); }, 0);
		}
		$(window).on('resize', aplicarAnchoMesa);
		var arrastreAncho = false;
		$('#mesaDrawerTirador').on('pointerdown', function (e) {
			var o = e.originalEvent;
			if (window.innerWidth < 992 || o.button !== 0) return;
			e.preventDefault();
			arrastreAncho = true;
			try { this.setPointerCapture(o.pointerId); } catch (x) {}
			$('#mesaDrawer').addClass('redimensionando');
		}).on('pointermove', function (e) {
			if (arrastreAncho) fijarAnchoMesa(window.innerWidth - e.originalEvent.clientX, false);
		}).on('pointerup pointercancel', function () {
			if (!arrastreAncho) return;
			arrastreAncho = false;
			$('#mesaDrawer').removeClass('redimensionando');
			fijarAnchoMesa(document.getElementById('mesaDrawer').getBoundingClientRect().width, true);
		}).on('dblclick', function () {
			try { localStorage.removeItem(anchoMesaActual().clave); } catch (x) {}
			aplicarAnchoMesa();
		}).on('keydown', function (e) {
			if (e.which !== 37 && e.which !== 39) return;   // flecha izquierda agranda, derecha achica
			e.preventDefault();
			fijarAnchoMesa(document.getElementById('mesaDrawer').getBoundingClientRect().width + (e.which === 37 ? 40 : -40), true);
		});

		// Cerrar (X, clic fuera o Esc) es lo mismo que "Volver a mesas": avisa si hay productos sin enviar
		$('#btnCerrarDrawerMesa, #mesaDrawerFondo').on('click', function () { $('#btnSalirMesa').click(); });
		$(document).on('keydown', function (e) {
			if (e.which !== 27 || !drawerMesaAbierto() || $('.modal.in').length || $('.sweet-overlay:visible').length) return;
			if ($(e.target).is('input, select, textarea')) return;   // Esc en el buscador cierra sus resultados, no la mesa
			$('#btnSalirMesa').click();
		});

		$('#mesasGrilla').on('click', '.mesa-card', function () {
			if (editandoPlano) return;
			var id = Number($(this).attr('data-id'));
			if ($(this).attr('data-estado') === 'bloqueada') { ofrecerLiberar(id); return; }
			if (mesa && mesa.id_mesa === id) { abrirDrawerMesa(); return; }
			if (mesa && pendientesMesa().length) {
				swal({ title: 'Hay productos sin enviar', text: 'Se perderán los que no enviaste a ' + nombreMesa(mesa.id_mesa) + '.', type: 'warning', showCancelButton: true, confirmButtonText: 'Continuar', cancelButtonText: 'Cancelar' },
					function (ok) { if (ok) abrirMesa(id, true); });
				return;
			}
			// La venta directa en curso se guarda aparte y vuelve al cerrar la mesa (ver guardarVentaDirecta)
			abrirMesa(id, !!mesa);
		});
		$('#btnSalirMesa').on('click', function () {
			var salir = function () { limpiarVenta(); $('#lnkMesas').tab('show'); };
			if (!pendientesMesa().length) { salir(); return; }
			swal({ title: '¿Salir sin enviar?', text: 'Se perderán los productos que no enviaste a la mesa (quedan registrados en la auditoría de caja).', type: 'warning', showCancelButton: true, confirmButtonText: 'Salir', cancelButtonText: 'Quedarme' },
				function (ok) { if (!ok) return; auditarOmitidos('salir_mesa'); salir(); });
		});
		function postJson(accion, datos) {
			return $.ajax({ url: base + accion, type: 'POST', contentType: 'application/json', data: JSON.stringify(datos), dataType: 'json' });
		}
		$('#btnEnviarMesa, #btnEnviarMesaBarra').on('click', function () { enviarAMesa(false); });
		function enviarAMesa(luegoCobrar) {
			if (enviando || !mesa) return;
			var idMesa = mesa.id_mesa, enviadas = pendientesMesa();
			var detalle = $.map(enviadas, function (l) { return { id_producto: l.id_producto, cantidad: l.cantidad, agregados: l.agregados, nota: l.nota }; });
			enviando = true;
			$('#btnEnviarMesa, #btnEnviarMesaBarra, #btnCobrar').prop('disabled', true);
			postJson('enviarMesa', { id_mesa: idMesa, id_lprecio: Number($('#cboLista').val()), detalle: detalle }).done(function (r) {
				if (imprimeComanda()) imprimirComanda(r.comanda_id, 'Comanda');
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
					imprimirComanda(r.comanda_id, 'Cambio de mesa');
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
				imprimirComanda(r.comanda_id, 'Anulación');
				if (r.liberada) { toastr.info(nombreMesa(idMesa) + ' quedó libre'); limpiarVenta(); $('#lnkMesas').tab('show'); return; }
				aplicarDetalleMesa(r.detalle);
			});
		});
		// Opciones de la mesa (panel Opciones del panel de la mesa): cada botón hace lo suyo y la venta vuelve a Pagar
		$('#opcionesMesa').on('click', '.opcion-mesa', function () { mostrarPanel('pago'); });
		$('#btnPrecuentaOpcion').on('click', function () { $('#btnPrecuenta').click(); });
		$('#btnMoverMesa').on('click', function () { abrirMesaProductos('mover'); });
		// Cambiar garzón: quién atiende la mesa (y queda en la venta al cobrar)
		$('#btnCambiarGarzon').on('click', function () {
			if (!mesa || !mesa.id_cotizacion) return;
			var idMesa = mesa.id_mesa;
			get('garzones').done(function (r) {
				if (!mesa || mesa.id_mesa !== idMesa) return;
				var $s = $('#garzonNuevo').empty(), actual = '';
				$.each(r.garzones || [], function (i, g) {
					if (g.id_usuario === mesa.id_garzon) actual = g.nombre;
					$s.append($('<option>').val(g.id_usuario).text(g.nombre + (g.garzon ? '' : ' (no marcado como garzón)')));
				});
				$s.val(String(mesa.id_garzon));
				$('#garzonMesa').text(nombreMesa(idMesa));
				$('#garzonActual').text(actual || '—');
				$('#modalGarzon').modal('show');
			});
		});
		$('#modalGarzon').on('shown.bs.modal', function () { $('#garzonNuevo').focus(); })
			.on('hidden.bs.modal', function () { $('#buscarProducto').focus(); });
		$('#frmGarzon').on('submit', function (e) {
			e.preventDefault();
			if (!mesa) return;
			var id = Number($('#garzonNuevo').val()), idMesa = mesa.id_mesa;
			if (!id) return;
			if (id === mesa.id_garzon) { $('#modalGarzon').modal('hide'); return; }
			var $b = $('#btnGuardarGarzon').prop('disabled', true);
			postJson('cambiarGarzon', { id_mesa: idMesa, id_garzon: id }).done(function (r) {
				$('#modalGarzon').modal('hide');
				if (mesa && mesa.id_mesa === idMesa) mesa.id_garzon = r.id_garzon;
				toastr.success('Ahora atiende ' + r.nombre + ' en ' + r.mesa);
				// Lista de mesas al día: "Atiende" en la mesa, en el panel y en la opción
				cargarMesas().always(function () {
					if (!mesa || mesa.id_mesa !== idMesa) return;
					var m = $.grep(mesasLista, function (x) { return x.id_mesa === idMesa; })[0];
					$('#mesaDrawerInfo').text(m ? $.grep([m.recinto, m.atiende ? 'Atiende ' + m.atiende : ''], function (x) { return !!x; }).join(' · ') : '');
					actualizarModoMesa();
				});
			}).fail(error).always(function () { $b.prop('disabled', false); });
		});
		// Volver a enviar a cocina/barra algo ya pedido (papel cortado, se perdió la comanda...). No cambia el pedido.
		$('#lineas').on('click', '.reenviar-linea', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var l = carro[$(this).closest('[data-i]').data('i')];
			if (l && l.id_detalle) reenviarComanda(mesa.id_mesa, [l.id_detalle]);
		});
		$('#btnReenviarTodo').on('click', function () {
			var enviadas = $.grep(carro, function (l) { return l.enviada; });
			if (enviadas.length) reenviarComanda(mesa.id_mesa, []);
		});
		$('#btnCobrarParte').on('click', function () { abrirDividir(); });
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
		function dcLineas() { return $.grep(carro, function (l) { return l.enviada; }); }
		function dcLinea(id) { return $.grep(carro, function (l) { return l.enviada && l.id_detalle === id; })[0]; }
		// Lo que se cobra de una línea: completa, a su monto; una parte, en proporción (igual que Mcaja::seleccionDeLineas)
		function dcMontoLinea(l, q) { return q >= l.cantidad - 0.0005 ? l.bruto : Math.round(l.bruto_exacto * q / l.cantidad); }
		function dcMonto(items) {
			var t = 0;
			$.each(items, function (id, q) { var l = dcLinea(Number(id)); if (l) t += dcMontoLinea(l, q); });
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
			$.each(lineas, function (i, l) { porCobrar += l.bruto; });
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
			if (dcModo === 'iguales') dcPintarIguales(lineas);
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
			if (o.sel) url += '&sel=' + encodeURIComponent($.map(o.sel, function (q, id) { return id + ':' + q; }).join(','));
			if (o.partes) url += '&partes=' + o.partes + '&parte=' + o.parte + '&inicio=' + (o.inicio || 0);
			imprimir(url);
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
				if (++intentos < 30) setTimeout(esperar, 200);
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
			var pagadas = dc.pagadas.length, partes = dcSimularPartes(dcLineas(), dc.partes - pagadas), k = pagadas + 1;
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
					imprimirComanda(r.comanda_id, 'Anulación');
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
				}, function (r) {
					toastr.success('Venta folio ' + folio + ' anulada');
					if (r && r.impresion) {
						if (r.impresion.ok) toastr.success('Comprobante de anulación enviado a la impresora', '', { timeOut: 2500 });
						else toastr.error('Comprobante de anulación: ' + r.impresion.detalle, '', { timeOut: 9000 });
					}
					if (r && r.correo && !r.correo.ok) toastr.error('Correo de la anulación: ' + r.correo.detalle, '', { timeOut: 9000 });
					cargarVentas();
				});
			});
		});

		// ---------------- créditos de clientes ----------------
		// Lista de clientes con deuda; al elegir uno se ven sus ventas a crédito pendientes (con su detalle), se marcan las
		// que paga y se registra el pago (queda en la caja y sale el comprobante en la impresora de caja).
		var cred = { clientes: [], cliente: null, pendientes: [] };
		function cargarClientesCredito() {
			return get('clientesConDeuda').done(function (r) {
				cred.clientes = r.clientes || [];
				pintarClientesCredito();
			});
		}
		function pintarClientesCredito() {
			var q = $.trim($('#credBuscar').val()).toUpperCase().replace(/\./g, '');
			var lista = $.grep(cred.clientes, function (c) {
				return !q || c.nombre.toUpperCase().indexOf(q) >= 0 || c.rut.replace(/\./g, '').indexOf(q) >= 0;
			});
			if (!lista.length) {
				$('#credClientes').html('<div class="text-muted text-center" style="padding:20px">' + (cred.clientes.length ? 'Ningún cliente coincide' : 'Ningún cliente debe créditos') + '</div>');
				return;
			}
			$('#credClientes').html($.map(lista, function (c) {
				return '<a class="list-group-item' + (cred.cliente && cred.cliente.id_cliente === c.id_cliente ? ' active' : '') + '" data-id="' + c.id_cliente + '">'
					+ '<span class="cred-monto">' + fmt(c.deuda) + '</span><b>' + esc(c.nombre) + '</b><br><small class="text-muted">'
					+ esc(c.rut) + ' · ' + c.ventas + ' venta(s)</small>'
					+ (c.vencido ? '<br><span class="cred-vencido"><i class="fa fa-exclamation-triangle"></i> Vencido ' + fmt(c.vencido) + '</span>' : '')
					+ '</a>';
			}).join(''));
		}
		$('#credBuscar').on('input', pintarClientesCredito);
		$('#credClientes').on('click', '.list-group-item', function () { cargarCreditosCliente(Number($(this).data('id'))); });
		$('#lnkCreditos').on('shown.bs.tab', function () { cargarClientesCredito(); if (cred.cliente) cargarCreditosCliente(cred.cliente.id_cliente); });

		function cargarCreditosCliente(id) {
			$('#credDetalle').html('<p class="text-muted text-center" style="margin:30px 0"><i class="fa fa-spinner fa-spin"></i> Cargando…</p>');
			return get('creditosCliente', { id_cliente: id }).done(function (r) {
				cred.cliente = r.cliente;
				cred.pendientes = r.pendientes || [];
				pintarClientesCredito();
				pintarCreditosCliente(r);
			});
		}
		function pintarCreditosCliente(r) {
			var c = r.cliente;
			$('#credTitulo').text(c.nombre);
			$('#credSubtitulo').text((c.rut ? 'RUT ' + c.rut + ' · ' : '') + (c.plazo > 0 ? 'Plazo ' + c.plazo + ' días' : 'Sin plazo') + (c.cupo > 0 ? ' · Cupo ' + fmt(c.cupo) : ''));
			var h = '<div class="cred-resumen"><div><small>Debe</small><b>' + fmt(c.deuda) + '</b></div>'
				+ '<div><small>Vencido</small><b' + (c.vencido ? ' style="color:#C9372C"' : '') + '>' + fmt(c.vencido) + '</b></div>'
				+ (c.cupo > 0 ? '<div><small>Cupo disponible</small><b>' + fmt(Math.max(0, c.cupo - c.deuda)) + '</b></div>' : '') + '</div>';
			if (!r.listo) h += '<div class="alert alert-warning">Para registrar pagos falta preparar la base: corre el script <code>sql/credito_clientes.sql</code>.</div>';
			if (!cred.pendientes.length) {
				h += '<p class="text-muted text-center" style="margin:20px 0"><i class="fa fa-check-circle"></i> No tiene ventas a crédito pendientes.</p>';
			} else {
				h += '<table class="table table-condensed cred-tabla"><thead><tr><th style="width:30px"><input type="checkbox" id="credTodas" title="Todas"></th>'
					+ '<th>Fecha</th><th>Documento</th><th>Vence</th><th class="caja-num">Total</th><th class="caja-num">Pagado</th><th class="caja-num">Saldo</th></tr></thead><tbody>';
				$.each(cred.pendientes, function (i, v) {
					var vencida = v.dias_vencido > 0;
					h += '<tr class="' + (vencida ? 'cred-vence' : '') + '"><td><input type="checkbox" class="cred-chk" data-i="' + i + '"></td>'
						+ '<td>' + esc(v.fecha) + '</td>'
						+ '<td><span class="cred-ver" data-i="' + i + '" title="Ver productos"><i class="fa fa-caret-right"></i> ' + esc(v.documento) + ' N° ' + v.folio + '</span></td>'
						+ '<td>' + (v.vence ? esc(v.vence) + (vencida ? ' <small style="color:#C9372C">(' + v.dias_vencido + ' días vencida)</small>' : '') : '—') + '</td>'
						+ '<td class="caja-num">' + fmt(v.monto) + '</td><td class="caja-num">' + fmt(v.pagado) + '</td><td class="caja-num"><b>' + fmt(v.saldo) + '</b></td></tr>';
					h += '<tr class="cred-lineas" data-i="' + i + '" style="display:none"><td colspan="7">' + ($.map(v.lineas, function (l) {
						return esc(fmtCant(l.cantidad)) + ' x ' + esc(l.nombre) + ' — ' + fmt(l.subtotal);
					}).join('<br>') || '<span class="text-muted">Sin detalle</span>') + '</td></tr>';
				});
				h += '</tbody></table>';
				var formas = $.grep(catalogo ? catalogo.formas_pago : [], function (f) { return !f.credito && !f.cortesia; });
				h += '<div class="cred-pago"><div class="row">'
					+ '<div class="col-sm-4"><small class="text-muted">A pagar (<span id="credCant">0</span> venta/s)</small><div class="cred-total" id="credTotal">$0</div></div>'
					+ '<div class="col-sm-4 form-group"><label>Forma de pago</label><select class="form-control" id="credFpago">' + $.map(formas, function (f) {
						return '<option value="' + f.id_fpago + '" data-efectivo="' + (f.efectivo ? 1 : 0) + '">' + esc(f.nombre) + '</option>';
					}).join('') + '</select></div>'
					+ '<div class="col-sm-4 form-group" id="credPagaConGrupo"><label>Paga con</label><input type="number" min="0" step="1" class="form-control" id="credPagaCon" placeholder="Monto entregado"><small class="text-muted" id="credVuelto"></small></div>'
					+ '</div><div class="form-group"><input type="text" class="form-control" id="credObs" maxlength="200" placeholder="Observación (opcional)"></div>'
					+ '<button class="btn btn-success btn-lg btn-block" id="btnPagarCredito" disabled' + (r.listo ? '' : ' title="Falta correr sql/credito_clientes.sql"') + '><i class="fa fa-check"></i> Registrar pago</button></div>';
			}
			if (r.pagos && r.pagos.length) {
				h += '<h5 style="margin-top:22px"><b>Últimos pagos</b></h5><table class="table table-condensed"><thead><tr><th>Fecha</th><th>Ventas</th><th>Forma de pago</th><th>Cajero</th><th class="caja-num">Monto</th><th></th></tr></thead><tbody>'
					+ $.map(r.pagos, function (pg) {
						return '<tr><td>' + esc(pg.fecha) + '</td><td><small>' + esc(pg.ventas) + '</small></td><td>' + esc(pg.fpago) + '</td><td>' + esc(pg.usuario) + '</td>'
							+ '<td class="caja-num">' + fmt(pg.monto) + '</td><td><button class="btn btn-default btn-xs cred-reimprimir" data-id="' + pg.id + '" title="Reimprimir comprobante"><i class="fa fa-print"></i></button></td></tr>';
					}).join('') + '</tbody></table>';
			}
			$('#credDetalle').html(h);
			$('#credDetalle').data('listo', !!r.listo);
			actualizarPagoCredito();
		}
		function creditosElegidos() {
			return $.map($('#credDetalle .cred-chk:checked').toArray(), function (el) { return cred.pendientes[$(el).data('i')]; });
		}
		function actualizarPagoCredito() {
			var elegidos = creditosElegidos(), total = 0;
			$.each(elegidos, function (i, v) { total += v.saldo; });
			$('#credTotal').text(fmt(total));
			$('#credCant').text(elegidos.length);
			$('#credTodas').prop('checked', elegidos.length > 0 && elegidos.length === cred.pendientes.length);
			var efectivo = $('#credFpago option:selected').data('efectivo') === 1;
			$('#credPagaConGrupo').toggle(efectivo);
			var pagaCon = Number($('#credPagaCon').val()) || 0;
			$('#credVuelto').text(efectivo && pagaCon > total && total > 0 ? 'Vuelto: ' + fmt(pagaCon - total) : (efectivo && pagaCon && pagaCon < total ? 'Falta ' + fmt(total - pagaCon) : ''));
			$('#btnPagarCredito').prop('disabled', !total || !$('#credDetalle').data('listo') || (efectivo && pagaCon > 0 && pagaCon < total));
		}
		$('#credDetalle')
			.on('change', '#credTodas', function () { $('#credDetalle .cred-chk').prop('checked', this.checked); actualizarPagoCredito(); })
			.on('change', '.cred-chk, #credFpago', actualizarPagoCredito)
			.on('input', '#credPagaCon', actualizarPagoCredito)
			.on('click', '.cred-ver', function () {
				var i = $(this).data('i');
				$('#credDetalle .cred-lineas[data-i="' + i + '"]').toggle();
				$(this).find('i').toggleClass('fa-caret-right fa-caret-down');
			})
			.on('click', '.cred-reimprimir', function () {
				post('reimprimirPagoCredito', { id_pago: $(this).data('id') }).done(function (r) {
					if (r.ok) toastr.success('Comprobante enviado a la impresora', '', { timeOut: 2500 });
					else toastr.error('Comprobante: ' + r.detalle, '', { timeOut: 9000 });
				});
			})
			.on('click', '#btnPagarCredito', function () {
				var elegidos = creditosElegidos();
				if (!elegidos.length || !cred.cliente) return;
				var total = 0;
				$.each(elegidos, function (i, v) { total += v.saldo; });
				var $fp = $('#credFpago option:selected');
				var datos = { id_cliente: cred.cliente.id_cliente, id_fpago: $fp.val(), paga_con: Number($('#credPagaCon').val()) || 0, observacion: $('#credObs').val() };
				$.each(elegidos, function (i, v) { datos['ids[' + i + ']'] = v.id_ctacte; });
				swal({
					title: '¿Registrar el pago?',
					text: cred.cliente.nombre + ' paga ' + fmt(total) + ' con ' + $fp.text() + ' (' + elegidos.length + ' venta/s).',
					type: 'warning', showCancelButton: true, closeOnConfirm: true,
					confirmButtonText: 'Registrar pago', cancelButtonText: 'Cancelar'
				}, function (ok) {
					if (!ok) return;
					var $b = $('#btnPagarCredito').prop('disabled', true);
					post('pagarCredito', datos).done(function (r) {
						var p = r.pago;
						toastr.success('Pago registrado: ' + fmt(p.monto) + (p.vuelto ? '. Vuelto ' + fmt(p.vuelto) : ''), '', { timeOut: 5000 });
						if (r.impresion && !r.impresion.ok) toastr.error('Comprobante: ' + r.impresion.detalle, '', { timeOut: 9000 });
						cargarClientesCredito();
						cargarCreditosCliente(cred.cliente.id_cliente);
						cargarEstado();
					}).fail(function () { $b.prop('disabled', false); });
				});
			});

		// ---------------- movimientos ----------------
		var efectivoDisponible = null;
		function cargarDisponible() {
			get('estado').done(function (r) {
				// Cierre ciego (sin gerencia): el servidor no manda lo esperado y no se muestra lo disponible
				efectivoDisponible = r && r.caja && r.caja.efectivo_esperado !== undefined && r.caja.efectivo_esperado !== null ? Number(r.caja.efectivo_esperado) : null;
				// Configuraciones > Permitir retiro fondo fijo: desmarcado, el fondo no se puede retirar
				var sinFondo = catalogo && catalogo.config.retiro_fondo === false;
				if (efectivoDisponible !== null && sinFondo) efectivoDisponible -= Number(r.caja.fondo) || 0;
				$('#movDisponible').text(efectivoDisponible === null ? '' : (sinFondo ? 'Disponible para retirar (sin el fondo fijo): ' : 'Efectivo disponible en la caja: ') + fmt(Math.max(0, efectivoDisponible)));
			});
		}
		function cargarMovimientos() {
			cargarDisponible();
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
			if (datos.tipo !== 'ingreso' && efectivoDisponible !== null && datos.monto > efectivoDisponible) {
				swal('Retiro', 'Solo hay ' + fmt(Math.max(0, efectivoDisponible)) + ' en efectivo en la caja: el retiro no puede ser mayor', 'warning');
				return;
			}
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
				if (imprimeTicket()) imprimirTicket(r.id_venta);
				if ($('#tabVentas').hasClass('active')) cargarVentas();
				cargarEstado();
			}, function () { $b.prop('disabled', false); });
		});

		// ---------------- cierre ----------------
		function cargarCierre() {
			avisarEsperasCierre();
			cargarEstado().done(function () {
				if (!resumen) return;
				var r = resumen;
				// Cierre ciego: no se muestra nada que permita saber cuánto efectivo debería haber (total de ventas, lo cobrado
				// en efectivo ni el efectivo esperado). El cajero cuenta y escribe lo que hay; la diferencia se calcula al cerrar.
				var filas = [
					['Fondo inicial', r.fondo],
					['Ventas', r.cantidad_ventas + ' documento' + (r.cantidad_ventas === 1 ? '' : 's'), true],
					['Devoluciones (' + r.cantidad_devoluciones + ')', -r.devoluciones],
					['Descuentos otorgados', r.descuentos],
					['Cortesías', r.cortesias],
					['Propinas (se pagan a los garzones desde la caja)', r.propinas],
					['Ventas anuladas', r.anuladas, true],
					['Pagos de créditos recibidos', r.pagos_credito || 0],
					['Ingresos de caja' + (r.pagos_credito ? ' (incluye los pagos de crédito en efectivo)' : ''), r.ingresos],
					['Retiros de caja', -r.egresos]
				];
				var html = '';
				$.each(filas, function (i, f) {
					html += '<tr><td>' + esc(f[0]) + '</td><td class="caja-num">' + (f[2] ? f[1] : fmt(f[1])) + '</td></tr>';
				});
				html += '<tr><th colspan="2">Recaudado por forma de pago</th></tr>';
				$.each(r.por_forma_pago, function (i, f) {
					if (f.efectivo) { html += '<tr><td>' + esc(f.nombre) + '</td><td class="caja-num text-muted">Se cuenta en caja</td></tr>'; return; }
					// Si Configuraciones no rellena las formas no efectivo, tampoco se muestra su monto: el cajero lo declara abajo
					if (!f.cortesia && catalogo && catalogo.config.llenar_no_efectivo === false) { html += '<tr><td>' + esc(f.nombre) + '</td><td class="caja-num text-muted">Se declara abajo</td></tr>'; return; }
					html += '<tr><td>' + esc(f.nombre) + (f.cortesia ? ' (no se rinde)' : '') + '</td><td class="caja-num">' + fmt(f.monto)
						+ (f.propina ? ' <small class="text-muted">+ ' + fmt(f.propina) + ' propina</small>' : '') + '</td></tr>';
				});
				$('#resumenCierre').html(html);

				var $d = $('#declaradoOtros').empty();
				$.each(r.por_forma_pago, function (i, f) {
					if (f.efectivo || f.cortesia) return;
					// Configuraciones > Llenar forma de pago no efectivo: marcada, viene con lo del sistema; si no, el cajero lo escribe
					var llenar = !catalogo || catalogo.config.llenar_no_efectivo !== false;
					$d.append('<div class="form-group"><label>' + esc(f.nombre) + ' (vouchers / comprobantes' + (f.propina ? ', con propina' : '') + ')</label>'
						+ '<input type="number" min="0" step="1" class="form-control declarado" data-id="' + f.id_fpago + '" value="' + (llenar ? (f.monto + f.propina) : '') + '" placeholder="Escribe el total de los comprobantes"></div>');
				});
				$('#efectivoContado').val('');   // lo escribe el cajero (cierre ciego)
				$('#fondoSiguiente').val(r.fondo);
				calcularDiferencia();
				setTimeout(function () { $('#efectivoContado').focus(); }, 200);
			});
		}

		// Cierre ciego: solo lo que declara el cajero (lo esperado y la diferencia no se muestran antes de cerrar)
		function calcularDiferencia() {
			if (!resumen) return;
			var declarado = Number($('#efectivoContado').val()) || 0;
			$('.declarado').each(function () { declarado += Number($(this).val()) || 0; });
			$('#lblDeclarado').text(fmt(declarado));
			var entregar = (Number($('#efectivoContado').val()) || 0) - (Number($('#fondoSiguiente').val()) || 0);
			$('#lblEntregar').text(fmt(entregar)).css('color', entregar < 0 ? '#C9372C' : '');
		}

		$('#lnkCierre').on('shown.bs.tab', cargarCierre);
		// Botón "Cerrar caja" de la franja de arriba: va al cierre (si hay una mesa abierta en su panel, primero se cierra el panel)
		$('#btnIrCierre').on('click', function () {
			if (drawerMesaAbierto()) { toastr.info('Cierra primero el panel de la mesa'); return; }
			if ($('#tabCierre').hasClass('active')) cargarCierre(); else $('#lnkCierre').tab('show');
		});
		// Cierre / informe X a la impresora del cierre (Configuraciones > Impresora para cierre de caja); si no se puede, en pantalla
		function imprimirCierre(idCaja) {
			$.ajax({ url: base + 'imprimirCierre', type: 'POST', dataType: 'json', data: { id_caja: idCaja } })
				.done(function (r) {
					if (r.ok) toastr.success('Cierre enviado a la impresora', '', { timeOut: 2500 });
					else {
						if (r.detalle && !r.navegador) toastr.error('Cierre: ' + r.detalle, '', { timeOut: 9000 });
						if (r.navegador) imprimir(base + 'cierreTicket/' + idCaja);
					}
				})
				.fail(function () { imprimir(base + 'cierreTicket/' + idCaja); });
		}
		$(document).on('click', '#btnAbrirGaveta', function () {
			var $b = $(this).prop('disabled', true);
			enviarConAutorizacion(function (aut) {
				return $.ajax({ url: base + 'abrirGaveta', type: 'POST', dataType: 'json', data: { autorizacion: aut } });
			}, function () {
				toastr.success('Gaveta abierta', '', { timeOut: 2000 });
				$b.prop('disabled', false);
			}, function () { $b.prop('disabled', false); });
		});
		$('#btnInformeX').on('click', function () {
			if (resumen) imprimirCierre(resumen.id_caja);
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
		// No se cierra la caja con ventas en espera pendientes (el servidor también lo impide). Las mesas con pedido sí:
		// siguen abiertas para cobrarlas en el próximo turno; continuar recibe sus nombres para avisarlo al confirmar.
		function pedirInventarioCierre(continuar) {
			cargarEsperas().done(function () {
				var pendientes = esperasDisponible ? $.grep(esperas, function (e) { return e.bloquea_cierre; }) : [];
				if (pendientes.length) {
					swal({
						title: 'Hay ventas en espera',
						text: 'No se puede cerrar la caja con ventas en espera pendientes: ' + $.map(pendientes, function (e) { return e.nombre; }).join(', ')
							+ '. Cárgalas y cóbralas, o anúlalas.',
						type: 'warning',
						showCancelButton: true,
						confirmButtonText: 'Ver ventas en espera',
						cancelButtonText: 'Cerrar'
					}, function (ok) { if (ok) setTimeout(abrirEsperas, 150); });
					return;
				}
				cargarMesas().always(function () {
					var conPedido = $.map($.grep(mesasLista || [], function (m) { return m.con_pedido; }), function (m) { return nombreMesa(m.id_mesa); });
					pedirInventarioCierreTras(function (inventario) { continuar(inventario, conPedido); });
				});
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
			// Cierre ciego: cada forma de pago se declara (0 si no hubo); en blanco no se da por cero
			var $sinDeclarar = $('.declarado').filter(function () { return $(this).val() === '' || Number($(this).val()) < 0; }).first();
			if ($sinDeclarar.length) {
				swal('Atención', 'Ingresa el total de ' + $.trim($sinDeclarar.closest('.form-group').find('label').text().replace(/\s*\(.*$/, '')) + ' (0 si no hubo)', 'warning');
				return;
			}
			var datos = { efectivo_contado: Math.round(Number(efectivo)), fondo_siguiente: Math.round(Number(fondoSig)) };
			$('.declarado').each(function () { datos['declarado[' + $(this).data('id') + ']'] = Math.round(Number($(this).val()) || 0); });
			pedirInventarioCierre(function (inventario, mesasConPedido) {
			$.each(inventario, function (clave, cant) { datos['inventario[' + clave + ']'] = cant; });
			var conInventario = !$.isEmptyObject(inventario);
			swal({
				title: '¿Cerrar la caja?',
				text: 'Después de cerrar no podrás registrar más ventas en esta caja.'
					+ (conInventario ? ' El stock de los insumos contados quedará igual a lo que ingresaste.' : '')
					+ (mesasConPedido.length ? (mesasConPedido.length === 1 ? ' Queda 1 mesa con pedido (' : ' Quedan ' + mesasConPedido.length + ' mesas con pedido (')
						+ mesasConPedido.join(', ') + '): ' + (mesasConPedido.length === 1 ? 'sigue abierta' : 'siguen abiertas') + ' para cobrarla' + (mesasConPedido.length === 1 ? '' : 's') + ' en el próximo turno.' : ''),
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
				// Configuraciones > Cierre de caja detallado: abre el informe PDF al cerrar
				var cierrePdf = !catalogo || catalogo.config.cierre_detallado !== false;
				post('cerrar', datos).always(function () { cerrandoCaja = false; }).done(function (r) {
					// Como el POS: si Configuraciones lo indica, el cierre sale también en la térmica
					if (cierreTermica) imprimirCierre(idCaja);
					if (r.correo) {
						if (r.correo.ok) toastr.success('Cierre enviado por correo', '', { timeOut: 4000 });
						else toastr.error('Correo del cierre: ' + r.correo.detalle, '', { timeOut: 9000 });
					}
					var inv = r.inventario && r.inventario.insumos
						? ' Inventario: ' + r.inventario.insumos + ' insumo(s) actualizado(s)' + (r.inventario.con_diferencia ? ', ' + r.inventario.con_diferencia + ' con diferencia.' : ', sin diferencias.') : '';
					swal({ title: 'Caja cerrada', text: 'Entregar ' + fmt(r.entregado) + ' en efectivo. Quedan ' + fmt(r.fondo_siguiente)
						+ ' de fondo para el próximo turno. Diferencia: ' + fmt(r.diferencia) + '.' + inv, type: 'success' }, function () {
						// Informe de cierre con el reporte existente (si Configuraciones lo pide: cierre de caja detallado)
						if (cierrePdf) $.redirect("<?php echo base_url(); ?>index.php/CierresDeCaja/cierreCajaPDF", { id_caja: idCaja }, 'POST', '_blank');
						limpiarVenta();
						catalogo = null;
						$('#panelCaja a[href="#tabVenta"]').tab('show');
						cargarEstado();
					});
				});
			});
			});
		});

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
				return { id_producto: l.id_producto, nombre: l.nombre, precio: l.precio, cantidad: l.cantidad, agregados: (l.agregados || []).slice(),
					nota: l.nota || '', opciones: $.map(l.agregados || [], function (id) { var a = agregado(id); return a ? a.nombre : null; }).join(', ') };
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
				return { id_producto: l.id_producto, nombre: l.nombre, precio: l.precio, cantidad: l.cantidad, agregados: (l.agregados || []).slice(), nota: l.nota || '' };
			});
			cliente = d.cliente && d.cliente.id_cliente ? d.cliente : null;
			$('#txtCliente').val(cliente ? (d.txt_cliente || cliente.nombre_cliente) : '');
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
	})();
</script>
