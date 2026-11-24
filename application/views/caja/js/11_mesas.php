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
				$('.leyenda-reserva').toggle($.grep(r.mesas, function (m) { return !!m.reserva; }).length > 0);   // v1.29.0
				sectoresLista = r.sectores || [];
				pintarFiltroTipoMesa(r.tipos_mesa || []);
				$('#btnAnularVariasMesas').toggle(!!(catalogo && catalogo.config && catalogo.config.anular_varias_mesas) && !anularMesas);
				if (anularMesas) {
					$.each(anularMesas, function (id) {
						var m = $.grep(r.mesas, function (x) { return x.id_mesa === Number(id); })[0];
						if (!m || !m.con_pedido) delete anularMesas[id];   // otra caja la cobró o anuló entretanto
					});
					pintarBarraAnular();
				}
				renderMesas();
			});
		}
		// v1.33.0: mesas elegidas para anular juntas (null = no se está eligiendo)
		var anularMesas = null;
		function nombreMesa(id) {
			var m = $.grep(mesasLista, function (x) { return x.id_mesa === id; })[0];
			// 19-11-2026: sin nombre propio, el del tipo de mesa (ej. "Mesa D1")
			return m && m.nombre ? m.nombre : (m && m.nombre_tipo ? m.nombre_tipo : 'Mesa ' + id);
		}
		function mesasDelSector() {
			return $.grep(mesasLista, function (m) { return m.id_recinto === sectorActual; });
		}
		// 19-11-2026: filtro por tipo de mesa ('' = todas, 0 = sin tipo). También al organizar el plano (pedido del usuario):
		// se mueven solo las de ese tipo; las demás quedan donde estaban.
		var tipoMesaFiltro = '';
		// 19-11-2026 (auditoría): se recuerda solo mientras esté abierta la pestaña (sessionStorage); al día siguiente la
		// Caja parte mostrando todas las mesas y no esconde las de otros tipos ocupadas desde la app
		try { tipoMesaFiltro = sessionStorage.getItem('cajaWeb.tipoMesa') || ''; localStorage.removeItem('cajaWeb.tipoMesa'); } catch (e) {}
		function pintarFiltroTipoMesa(tipos) {
			var $f = $('#filtroTipoMesa');
			if (!tipos.length) { tipoMesaFiltro = ''; $f.hide(); return; }
			var sinTipo = $.grep(mesasLista, function (m) { return !m.id_tipo_mesa; }).length > 0;
			$f.empty().append($('<option>').val('').text('Todos los tipos de mesa'));
			$.each(tipos, function (i, t) { $f.append($('<option>').val(String(t.id)).text(t.nombre)); });
			if (sinTipo) $f.append($('<option>').val('0').text('Sin tipo'));
			if (!$f.find('option').filter(function () { return this.value === tipoMesaFiltro; }).length) tipoMesaFiltro = '';
			$f.val(tipoMesaFiltro).show();
		}
		function mesasVisibles(lista) {
			if (tipoMesaFiltro === '') return lista;
			var id = Number(tipoMesaFiltro);
			return $.grep(lista, function (m) { return (m.id_tipo_mesa || 0) === id; });
		}
		$('#filtroTipoMesa').on('change', function () {
			tipoMesaFiltro = String($(this).val() || '');
			try { sessionStorage.setItem('cajaWeb.tipoMesa', tipoMesaFiltro); } catch (e) {}
			renderMesas();
		});
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
			var $g = $('#mesasGrilla').addClass('mesas-plano').empty(), ahora = Date.now(), todas = mesasDelSector();
			posicionarPorDefecto(todas);   // con todas: filtrar por tipo no mueve las mesas en el plano
			var lista = mesasVisibles(todas);
			if (todas.length && !lista.length) $g.append('<div class="mesas-vacio">Este sector no tiene mesas de este tipo. Elige otro tipo arriba o <b>Todos los tipos de mesa</b>.</div>');
			else if (!lista.length) $g.append('<div class="mesas-vacio">Este sector aún no tiene mesas. Asígnalas en <a href="<?php echo base_url(); ?>index.php/MesasPorRecinto">Mesas por recinto</a>.</div>');
			$.each(lista, function (i, m) {
				var $c = $('<button type="button" class="mesa-card">').addClass(m.estado).toggleClass('con-pedido', !!m.con_pedido).toggleClass('sel-anular', !!(anularMesas && anularMesas[m.id_mesa])).toggleClass('actual', !!mesa && mesa.id_mesa === m.id_mesa)
					.attr('data-id', m.id_mesa).attr('data-estado', m.estado).css({ left: m.x, top: m.y })
					.append($('<div class="num">').toggleClass('largo', nombreMesa(m.id_mesa).length > 9).text(nombreMesa(m.id_mesa)).attr('title', nombreMesa(m.id_mesa)));
				if (m.con_pedido) {
					$c.append($('<div class="total">').text(fmt(m.total)))
						.append($('<div class="tiempo">').attr('data-inicio', ahora - m.segundos * 1000).text(fmtTiempo(m.segundos * 1000)))
						.toggleClass('demora', m.estado === 'ocupada' && m.segundos >= 3600 && m.segundos < 7200).toggleClass('critica', m.estado === 'ocupada' && m.segundos >= 7200)
						.append($('<div class="atiende">').text(m.atiende));
				} else if (m.reserva) {
					// v1.29.0: mesa libre bloqueada por una reserva (Reservas)
					$c.addClass('reservada').attr('data-reserva', m.reserva.id)
						.attr('title', 'Bloqueada por reserva: ' + m.reserva.nombre + ' a las ' + m.reserva.hora + ' (' + m.reserva.personas + ')')
						.append($('<div class="estado">').text('Reservada ' + m.reserva.hora))
						.append($('<div class="atiende">').text(m.reserva.nombre + ' · ' + m.reserva.personas + ' pers.'));
				} else {
					$c.append($('<div class="estado">').text(m.estado === 'libre' ? 'Libre' : 'Bloqueada'));
				}
				$g.append($c);
			});
			ajustarAltoPlano(todas);
		}
		function renombrarMesa(id) {
			var m = $.grep(mesasLista, function (x) { return x.id_mesa === id; })[0];
			if (!m) return;
			var porDefecto = m.nombre_tipo || ('Mesa ' + id);
			swal({ title: 'Nombre de la mesa ' + id, text: 'Ej: Terraza 2, Barra 1. Déjalo vacío para usar "' + porDefecto + '".', type: 'input', inputValue: m.nombre || '', inputPlaceholder: porDefecto, showCancelButton: true, confirmButtonText: 'Usar este nombre', cancelButtonText: 'Cancelar' }, function (v) {
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
			if (activo) $('#btnAnularVariasMesas').hide();
			else $('#btnAnularVariasMesas').toggle(!!(catalogo && catalogo.config && catalogo.config.anular_varias_mesas));
			$('#btnActualizarMesas').toggle(!activo);
			$('#btnCancelarPlano, #btnGuardarPlano, #btnAlinearMesas').toggle(activo);
			$('#mesasGrilla').toggleClass('editando', activo);
			$('#mesasAyuda').text(activo ? 'Arrastra cada mesa a su lugar o tócala para cambiarle el nombre, y presiona Guardar plano.' : 'Toca una mesa libre para tomar el pedido, o una ocupada para agregar productos o cobrar.');
		}
		$('#btnOrganizarMesas').on('click', function () { modoPlano(true); renderMesas(); });
		$('#btnCancelarPlano').on('click', function () { modoPlano(false); cargarMesas(); });
		// v1.34.1 (pedido del usuario): alinear las mesas del sector en filas parejas. Respeta el orden en que están (de
		// arriba hacia abajo y de izquierda a derecha); con un tipo de mesa elegido, solo esas. Se guarda con Guardar plano.
		$('#btnAlinearMesas').on('click', function () {
			var lista = mesasVisibles(mesasDelSector());
			if (!lista.length) return;
			var ancho = $('#mesasGrilla').width() || 900, cols = Math.max(1, Math.floor((ancho - MESA_GAP) / (MESA_W + MESA_GAP)));
			var fila = function (m) { return Math.round((Number(m.y) || 0) / (MESA_H + MESA_GAP)); };
			lista = lista.slice().sort(function (a, b) { return fila(a) - fila(b) || (Number(a.x) || 0) - (Number(b.x) || 0); });
			// Con un tipo elegido, se ponen debajo de las demás para no taparlas
			var y0 = MESA_GAP;
			if (tipoMesaFiltro !== '') {
				$.each(mesasDelSector(), function (i, m) { if ($.inArray(m, lista) < 0) y0 = Math.max(y0, (Number(m.y) || 0) + MESA_H + MESA_GAP); });
			}
			$.each(lista, function (i, m) {
				m.x = MESA_GAP + (i % cols) * (MESA_W + MESA_GAP);
				m.y = y0 + Math.floor(i / cols) * (MESA_H + MESA_GAP);
			});
			renderMesas();
			toastr.info('Mesas alineadas. Presiona Guardar plano para dejarlas así');
		});
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
			// 18-11-2026: salvo tomando o cobrando un delivery, que usa la pantalla de la venta directa
			$('#lnkVenta').parent().toggle(!soloMesas || !!modoDelivery);
			// Sin la venta a la vista tampoco se ve su panel Opciones: Ingresos / Retiros, Créditos y Cierre quedan arriba
			$('.caja-tabs').toggleClass('mostrar-opciones', soloMesas);
			if (soloMesas && !modoDelivery && !$('#tabMesas').hasClass('active') && $('#tabVenta').hasClass('active')) $('#lnkMesas').tab('show');
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
			// 19-11-2026: mensaje general de la mesa; en una mesa sin pedido se conserva el que se escribió antes de enviar
			var mensajePrevio = mesa && mesa.id_mesa === d.id_mesa && !d.id_cotizacion ? (mesa.mensaje || '') : '';
			mesa = { id_mesa: d.id_mesa, id_cotizacion: d.id_cotizacion, id_garzon: d.id_garzon || 0, inicio: d.id_cotizacion ? Date.now() - d.segundos * 1000 : 0,
				mensaje: d.id_cotizacion ? (d.mensaje || '') : mensajePrevio };
			pintarMensajeMesa();
			carro = $.map(d.lineas, function (l) {
				return { enviada: true, id_detalle: l.id_detalle, id_producto: l.id_producto, nombre: l.nombre, precio: l.unitario, bruto: l.bruto, bruto_exacto: l.bruto_exacto,
					// v1.30.3: nota = solo el mensaje del producto; el de su agregado va aparte (nota_agregado)
					cantidad: l.cantidad, agregados: [], agregado: l.agregado, nota: l.nota_producto !== undefined ? l.nota_producto : l.nota, nota_agregado: l.nota_agregado || '', exento: l.exento, ila: l.ila || 0, hora: l.hora,
					es_menu: !!l.es_menu, menu_de: l.menu_de || 0 };   // v1.24.0
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
			ponerTxtCliente('');
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
			ponerTxtCliente(v.txtCliente);
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
		// v1.24.6: con pedido baja hasta 720 (el contenido se compacta solo, ver @container mesa en caja.php)
		var ANCHO_MESA = { sin: { clave: 'cajaWeb.anchoMesaSinPedido', min: 420 }, con: { clave: 'cajaWeb.anchoMesaConPedido', min: 720 } };
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
			w = Math.round(Math.max(anchoMesaActual().min, Math.min(window.innerWidth - 24, w)));   // v1.24.6: hasta casi toda la pantalla
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

		// ---- v1.24.13: vista rápida de la mesa ----
		// Con el mouse 0,6 s sobre una mesa con pedido aparece al lado una tarjeta con lo que lleva (productos, cantidades,
		// total). Solo para mirar: no abre ni bloquea la mesa. Se cierra al sacar el mouse (de la mesa y de la tarjeta) o con
		// Esc. El clic en la mesa la abre como siempre. Sin mouse (pantallas táctiles) no aparece.
		var vm = { timer: null, cerrar: null, id: 0, pedido: 0, cache: {} };
		var $vm = $('<div class="vista-mesa" id="vistaMesa" role="dialog" aria-live="polite" hidden></div>').appendTo('body');
		function cerrarVistaMesa() {
			clearTimeout(vm.timer); clearTimeout(vm.cerrar);
			vm.id = 0; vm.pedido++;
			$vm.attr('hidden', true).removeClass('visible');
		}
		function ubicarVistaMesa($card) {
			var r = $card[0].getBoundingClientRect(), w = $vm.outerWidth(), h = $vm.outerHeight(), m = 10;
			var x = r.right + m;
			if (x + w > window.innerWidth - m) x = r.left - w - m;   // no cabe a la derecha: a la izquierda
			if (x < m) x = Math.max(m, Math.min(window.innerWidth - w - m, r.left));
			var y = Math.max(m, Math.min(window.innerHeight - h - m, r.top + r.height / 2 - h / 2));
			$vm.css({ left: x, top: y });
		}
		function pintarVistaMesa(d, $card) {
			var m = $.grep(mesasLista, function (x) { return x.id_mesa === d.id_mesa; })[0] || {};
			var total = 0, unidades = 0, filas = '';
			$.each(d.lineas || [], function (i, l) {
				total += Number(l.bruto) || 0;
				var hijo = !!l.menu_de;
				if (!hijo) unidades += l.cantidad === Math.round(l.cantidad) ? l.cantidad : 1;
				var det = (l.agregado ? '<small>+ ' + esc(l.agregado) + '</small>' : '') + (l.nota ? '<small><i>' + esc(l.nota) + '</i></small>' : '');
				filas += '<tr class="' + (hijo ? 'hijo' : '') + '"><td class="cant">' + (hijo ? '' : fmtCant(l.cantidad)) + '</td>'
					+ '<td class="prod">' + (hijo ? '+ ' + (l.cantidad > 1 ? fmtCant(l.cantidad) + ' ' : '') : '') + esc(l.nombre) + det + '</td>'
					+ '<td class="monto">' + (hijo && !l.bruto ? '' : fmt(l.bruto)) + '</td></tr>';
			});
			var info = $.grep([m.atiende ? 'Atiende ' + m.atiende : '', d.segundos ? fmtTiempo(d.segundos * 1000) : ''], function (x) { return !!x; }).join(' · ');
			$vm.html('<div class="vm-cab"><div><b>' + esc(d.nombre || nombreMesa(d.id_mesa)) + '</b>' + (info ? '<small>' + esc(info) + '</small>' : '') + '</div>'
					+ '<span class="vm-ojo" title="Solo para mirar: haz clic en la mesa para abrirla"><i class="fa fa-eye"></i></span></div>'
				+ (d.mensaje ? '<div class="vm-mensaje"><i class="fa fa-commenting-o"></i> ' + esc(d.mensaje) + '</div>' : '')
				+ (filas ? '<div class="vm-lista"><table>' + filas + '</table></div>' : '<div class="vm-vacio">La mesa no tiene productos</div>')
				+ '<div class="vm-pie"><span>' + unidades + (unidades === 1 ? ' producto' : ' productos') + '</span><b>' + fmt(total) + '</b></div>');
			$vm.removeAttr('hidden');
			ubicarVistaMesa($card);
			$vm.addClass('visible');
		}
		function pedirVistaMesa(id, $card) {
			var pedido = ++vm.pedido, c = vm.cache[id];
			if (c && Date.now() - c.t < 8000) { pintarVistaMesa(c.d, $card); return; }   // recién mirada: sin volver a pedir
			$.ajax({ url: base + 'vistaMesa', data: { id_mesa: id }, dataType: 'json', cache: false, global: false }).done(function (d) {
				if (pedido !== vm.pedido || vm.id !== id) return;   // el mouse ya se fue a otra parte
				vm.cache[id] = { t: Date.now(), d: d };
				pintarVistaMesa(d, $card);
			});
		}
		$('#mesasGrilla').on('pointerenter', '.mesa-card', function (e) {
			if (e.originalEvent && e.originalEvent.pointerType && e.originalEvent.pointerType !== 'mouse') return;
			if (editandoPlano || anularMesas || !$(this).hasClass('con-pedido')) return;
			var $c = $(this), id = Number($c.attr('data-id'));
			clearTimeout(vm.cerrar);
			if (vm.id === id && !$vm.attr('hidden')) return;   // ya se está mostrando esta
			cerrarVistaMesa();
			vm.id = id;
			vm.timer = setTimeout(function () { pedirVistaMesa(id, $c); }, 600);   // v1.24.15: 0,6 s (antes 1,5: muy largo)
		}).on('pointerleave', '.mesa-card', function () {
			clearTimeout(vm.timer);
			vm.cerrar = setTimeout(cerrarVistaMesa, 250);   // margen para pasar el mouse a la tarjeta
		}).on('pointerdown', '.mesa-card', cerrarVistaMesa);
		$vm.on('pointerenter', function () { clearTimeout(vm.cerrar); })
			.on('pointerleave', function () { vm.cerrar = setTimeout(cerrarVistaMesa, 250); });
		$(document).on('keydown', function (e) { if (e.which === 27 && !$vm.attr('hidden')) cerrarVistaMesa(); });
		$(window).on('scroll resize', cerrarVistaMesa);
		$('a[data-toggle="tab"]').on('show.bs.tab', cerrarVistaMesa);

		// ---- v1.33.0 (pedido del usuario): anular varias mesas a la vez (Configuraciones > Anular varias mesas a la vez) ----
		function mesasElegidasAnular() {
			return $.grep(mesasLista, function (m) { return !!(anularMesas && anularMesas[m.id_mesa]); });
		}
		function pintarBarraAnular() {
			var sel = mesasElegidasAnular(), total = 0;
			$.each(sel, function (i, m) { total += m.total || 0; });
			$('#mesasAnularTexto').html(sel.length
				? '<b>' + (sel.length === 1 ? '1 mesa elegida' : sel.length + ' mesas elegidas') + '</b> · ' + fmt(total) + ' · toca otra para sumarla o quitarla'
				: 'Toca las mesas con pedido que quieres anular (puedes cambiar de sector)');
			$('#btnAnularMesasConfirmar').prop('disabled', !sel.length)
				.html('<i class="fa fa-ban"></i> ' + (sel.length > 1 ? 'Anular ' + sel.length + ' mesas' : 'Anular'));
		}
		function modoAnularMesas(activo) {
			anularMesas = activo ? {} : null;
			cerrarVistaMesa();
			$('#mesasAnularBarra').prop('hidden', !activo);
			$('#mesasGrilla').toggleClass('anulando', activo);
			$('#btnAnularVariasMesas, #btnOrganizarMesas').toggle(!activo);
			if (activo) pintarBarraAnular();
			renderMesas();
		}
		$('#btnAnularVariasMesas').on('click', function () {
			if (mesa && pendientesMesa().length) { toastr.warning('Primero envía o quita los productos sin enviar de ' + nombreMesa(mesa.id_mesa)); return; }
			modoAnularMesas(true);
		});
		$('#btnAnularMesasCancelar').on('click', function () { modoAnularMesas(false); });
		$('#lnkMesas').on('hidden.bs.tab', function () { if (anularMesas) modoAnularMesas(false); });
		$('#btnAnularMesasTodas').on('click', function () {
			if (!anularMesas) return;
			$.each(mesasVisibles(mesasDelSector()), function (i, m) { if (m.con_pedido && m.estado !== 'bloqueada') anularMesas[m.id_mesa] = true; });
			pintarBarraAnular();
			renderMesas();
		});
		$('#btnAnularMesasConfirmar').on('click', function () {
			var sel = mesasElegidasAnular();
			if (!sel.length) return;
			var nombres = $.map(sel, function (m) { return nombreMesa(m.id_mesa); }), total = 0;
			$.each(sel, function (i, m) { total += m.total || 0; });
			swal({ title: 'Anular ' + (sel.length === 1 ? '1 mesa' : sel.length + ' mesas'),
				text: nombres.join(', ') + ' (' + fmt(total) + ').\nSe anulan todos sus productos y quedan libres. Indica el motivo:',
				type: 'input', showCancelButton: true, confirmButtonColor: '#C9372C', confirmButtonText: 'Anular y liberar', cancelButtonText: 'Volver', inputPlaceholder: 'Motivo' }, function (motivo) {
				if (motivo === false) return;
				if (!$.trim(motivo)) { toastr.warning('Indica el motivo de la anulación'); return; }
				var ids = $.map(sel, function (m) { return m.id_mesa; });
				enviarConAutorizacion(function (aut) {
					return postJson('anularVariasMesas', { ids: ids, motivo: motivo, autorizacion: aut });
				}, function (r) {
					var anuladas = $.map(r.mesas || [], function (m) { return m.id_mesa; });
					// Una comanda de anulación por mesa, una tras otra (cada una a sus destinos de impresión)
					var comandas = $.map(r.mesas || [], function (m) { return m.comanda_id || null; });
					if (comandas.length) {
						var fin = bloquearImpresion('Imprimiendo anulaciones…'), k = 0;
						(function siguiente() {
							if (k >= comandas.length) { fin(); return; }
							imprimirComanda(comandas[k++], 'Anulación', null, siguiente);
						})();
					}
					toastr.success(anuladas.length === 1 ? nombreMesa(anuladas[0]) + ' anulada y libre' : anuladas.length + ' mesas anuladas y libres');
					$.each(r.fallidas || [], function (i, f) { toastr.error(nombreMesa(f.id_mesa) + ': ' + f.error, '', { timeOut: 9000 }); });
					if (mesa && $.inArray(mesa.id_mesa, anuladas) >= 0) limpiarVenta();   // la mesa que estaba abierta ya no tiene pedido
					modoAnularMesas(false);
					cargarMesas();
				});
			});
		});

		$('#mesasGrilla').on('click', '.mesa-card', function () {
			if (editandoPlano) return;
			var id = Number($(this).attr('data-id'));
			if (anularMesas) {   // v1.33.0: eligiendo mesas para anular
				if (!$(this).hasClass('con-pedido') || $(this).attr('data-estado') === 'bloqueada') { toastr.info('Solo se anulan mesas con pedido'); return; }
				if (anularMesas[id]) delete anularMesas[id]; else anularMesas[id] = true;
				$(this).toggleClass('sel-anular', !!anularMesas[id]);
				pintarBarraAnular();
				return;
			}
			if ($(this).attr('data-estado') === 'bloqueada') { ofrecerLiberar(id); return; }
			// v1.29.0: mesa bloqueada por reserva: solo se abre si llegó el cliente
			if ($(this).attr('data-reserva')) {
				var mr = $.grep(mesasLista, function (x) { return x.id_mesa === id; })[0], rv = mr && mr.reserva;
				if (!rv) return;
				swal({ title: 'Mesa bloqueada por reserva', text: rv.nombre + ' reservó ' + nombreMesa(id) + ' a las ' + rv.hora + ' (' + rv.personas
					+ (rv.personas === 1 ? ' persona' : ' personas') + ').\n¿Llegó el cliente?', type: 'info', showCancelButton: true,
					confirmButtonText: 'Sí, llegó: abrir la mesa', cancelButtonText: 'No' }, function (ok) {
					if (!ok) return;
					$.ajax({ url: base + 'reservaLlego', type: 'POST', dataType: 'json', data: { id_mesa: id, id_reserva: rv.id } })
						.done(function () { delete mr.reserva; abrirMesa(id, !!mesa); })
						.fail(function (x) { toastr.error((x.responseJSON && x.responseJSON.error) || 'No se pudo marcar la llegada'); });
				});
				return;
			}
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
			var detalle = $.map(enviadas, function (l) { return { id_producto: l.id_producto, cantidad: l.cantidad, agregados: l.agregados, extras: l.extras || [], nota: l.nota, notas_agregados: l.notas_agregados || {}, menu: l.menu || [], sushi: l.sushi || null }; });
			enviando = true;
			$('#btnEnviarMesa, #btnEnviarMesaBarra, #btnCobrar').prop('disabled', true);
			postJson('enviarMesa', { id_mesa: idMesa, id_lprecio: Number($('#cboLista').val()), detalle: detalle,
				// 19-11-2026: el mensaje viaja solo en el primer envío (mesa sin pedido); después se guarda con su botón
				observacion: mesa.id_cotizacion ? '' : (mesa.mensaje || '') }).done(function (r) {
				if (imprimeComanda()) imprimirComandaBloqueando(r.comanda_id, 'Comanda');
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

		// 19-11-2026: mensaje general de la mesa (ej. "Alérgico al maní", "Traer todo junto"). v1.22.1: sale impreso una vez, en
		// la primera comanda después de escribirlo (Mcaja::mensajeParaComanda); en los reenvíos sale siempre. Con pedido se guarda al tiro; sin pedido, se guarda con el primer envío.
		function pintarMensajeMesa() {
			var t = mesa && mesa.mensaje ? mesa.mensaje : '';
			$('#btnMensajeMesa').toggleClass('con-mensaje', !!t).attr('title', t ? 'Mensaje de la mesa: ' + t + ' (clic para cambiarlo)' : 'Mensaje para toda la mesa: sale una vez, en la próxima comanda');
			$('#bannerMesaMensaje').text(t ? t : 'Mensaje a la mesa');
		}
		$('#btnMensajeMesa').on('click', function () {
			if (!mesa) return;
			var idMesa = mesa.id_mesa;
			swal({ title: 'Mensaje para ' + nombreMesa(idMesa), text: 'Sale impreso una vez, en la próxima comanda de la mesa (cocina, barra…). Los productos que agregues después van sin él. Déjalo vacío para quitarlo.', type: 'input',
				inputValue: mesa.mensaje || '', inputPlaceholder: 'Ej: alérgico al maní, traer todo junto', showCancelButton: true,
				confirmButtonText: 'Guardar', cancelButtonText: 'Cancelar', closeOnConfirm: true }, function (v) {
				if (v === false || !mesa || mesa.id_mesa !== idMesa) return;
				var t = $.trim(v).replace(/\s+/g, ' ').slice(0, 200);
				if (!mesa.id_cotizacion) {
					mesa.mensaje = t;
					pintarMensajeMesa();
					if (t) toastr.info('El mensaje sale en la comanda al enviar los productos');
					return;
				}
				postJson('guardarMensajeMesa', { id_mesa: idMesa, mensaje: t }).done(function (r) {
					if (!mesa || mesa.id_mesa !== idMesa) return;
					mesa.mensaje = r.mensaje || '';
					pintarMensajeMesa();
					toastr.success(r.mensaje ? 'Mensaje de la mesa guardado: sale en la próxima comanda' : 'Mensaje de la mesa quitado');
				}).fail(error);
			});
		});

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
			mp = { modo: modo, lineas: lineas || $.grep(carro, function (l) { return l.enviada && !l.menu_de; }), enviando: false };   // v1.24.0: lo del menú va con su menú
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
				// v1.30.3 (pedido del usuario): anular parte con 1 (o lo que haya, si es menos de 1); se sube con + para anular más
				var inicial = modo === 'parcial' ? (cobroParcial && cobroParcial[l.id_detalle] ? cobroParcial[l.id_detalle] : 0) : (modo === 'anular' ? Math.min(1, l.cantidad) : l.cantidad);
				return '<div class="mp-linea" data-i="' + i + '"><div class="mp-nombre">' + esc(l.nombre) + (l.nota ? '<small>» ' + esc(l.nota) + '</small>' : '') + (l.agregado ? '<small>+ ' + esc(l.agregado) + (l.nota_agregado ? ' » ' + esc(l.nota_agregado) : '') + '</small>' : '')
					+ $.map($.grep(carro, function (h) { return h.enviada && h.menu_de === l.id_detalle; }), function (h) { return '<small>+ ' + esc(h.nombre) + '</small>'; }).join('') + '</div>'
					+ '<div class="input-group input-group-sm"><span class="input-group-btn"><button class="btn btn-default mp-menos" type="button"><i class="fa fa-minus"></i></button></span>'
					+ '<input type="number" class="form-control mp-cant" min="' + (modo === 'anular' ? (fraccion ? '0.001' : '1') : '0') + '" max="' + l.cantidad + '" step="' + (fraccion ? '0.001' : '1') + '" value="' + inicial + '">'
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
				var q = Math.max(minimoMp(l), Math.min(l.cantidad, Number(String($(this).find('.mp-cant').val()).replace(',', '.')) || 0));   // v1.32.1: anular nunca en 0
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
		// v1.32.1 (pedido del usuario): al anular nunca queda en 0: lo mínimo es 1 (o 0,001 si se vende por peso)
		function minimoMp(l) { return mp && mp.modo === 'anular' ? Math.min(l.cantidad, l.cantidad !== Math.round(l.cantidad) ? 0.001 : 1) : 0; }
		$('#mpLineas').on('change', '.mp-cant', function () {
			var l = mp.lineas[$(this).closest('.mp-linea').data('i')], v = Number(String(this.value).replace(',', '.')) || 0, min = minimoMp(l);
			if (v < min) { this.value = min; toastr.info('Se anula como mínimo ' + fmtCant(min)); }
			if (v > l.cantidad) this.value = l.cantidad;
			actualizarMp();
		}).on('input', '.mp-cant', actualizarMp).on('click', '.mp-mas, .mp-menos', function () {
			var $c = $(this).closest('.mp-linea').find('.mp-cant'), l = mp.lineas[$(this).closest('.mp-linea').data('i')];
			var paso = l.cantidad !== Math.round(l.cantidad) ? 0.1 : 1;
			var v = (Number($c.val()) || 0) + ($(this).hasClass('mp-mas') ? paso : -paso);
			$c.val(Math.round(Math.max(minimoMp(l), Math.min(l.cantidad, v)) * 1000) / 1000);
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
					imprimirComandaBloqueando(r.comanda_id, 'Cambio de mesa');
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
				imprimirComandaBloqueando(r.comanda_id, 'Anulación');
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
