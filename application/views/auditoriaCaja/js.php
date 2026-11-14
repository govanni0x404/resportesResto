<script>
	/* Consultas > Auditoría de caja (10-11-2026). Datos: AuditoriaCaja/cajas y AuditoriaCaja/detalle (models/Mauditoriacaja.php). */
	(function () {
		var base = "<?php echo base_url(); ?>index.php/AuditoriaCaja/";
		var detalle = null, filtroLinea = 'todo', pedido = 0;

		// Miles con coma, como el resto de los reportes
		function fmt(n) {
			n = Math.round(Number(n) || 0);
			return (n < 0 ? '-$' : '$') + Math.abs(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
		}
		function num(n) {
			n = Math.round((Number(n) || 0) * 1000) / 1000;
			var p = String(n).split('.');
			return p[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',') + (p[1] ? '.' + p[1] : '');
		}
		function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }
		function hora(f) { return f ? f.substr(11, 5) : ''; }
		function diaHora(f) { return f ? f.substr(8, 2) + '/' + f.substr(5, 2) + ' ' + f.substr(11, 5) : ''; }
		function vacio(t) { return '<div class="aud-vacio"><i class="fa fa-check-circle-o"></i>' + esc(t) + '</div>'; }
		function error(x) {
			var m = (x && x.responseJSON && x.responseJSON.error) || 'No se pudo cargar la auditoría';
			if (window.toastr) toastr.error(m); else alert(m);
		}

		$('#date-range-picker').daterangepicker({
			ranges: dateRange,
			startDate: moment().subtract(6, 'days'),
			endDate: moment(),
			locale: {
				format: 'DD-MM-YYYY', separator: ' - ', applyLabel: 'Aplicar', cancelLabel: 'Cancelar', fromLabel: 'Desde', toLabel: 'Hasta',
				customRangeLabel: 'Rango personalizado', weekLabel: 'S', daysOfWeek: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá'],
				monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
				firstDay: 1
			}
		});
		function rango() {
			var p = $('#date-range-picker').data('daterangepicker');
			return { desde: p.startDate.format('YYYY-MM-DD'), hasta: p.endDate.format('YYYY-MM-DD') };
		}

		// ---------------- cajas del período ----------------
		function consultar() {
			$('#audCajas tbody').html('<tr><td colspan="7" class="aud-vacio">Cargando…</td></tr>');
			$.ajax({ url: base + 'cajas', type: 'POST', data: rango(), dataType: 'json' }).done(function (r) {
				$('#audSinBitacora').toggle(!r.bitacora);
				var cajas = r.cajas || [], $t = $('#audCajas tbody').empty();
				var alertas = 0;
				$.each(cajas, function (i, c) { alertas += c.alertas; });
				$('#audCajasResumen').text(cajas.length ? cajas.length + (cajas.length === 1 ? ' caja' : ' cajas') + ' · ' + alertas + ' hechos para revisar' : '');
				if (!cajas.length) {
					$t.html('<tr><td colspan="7">' + vacio('No hay cajas abiertas en ese período') + '</td></tr>');
					$('#audDetalle').hide();
					$('#btnImprimir, #btnCsv').prop('disabled', true);
					return;
				}
				$.each(cajas, function (i, c) {
					var dif = !c.cerrada ? '<span class="aud-chip azul">Abierta</span>'
						: (c.diferencia === 0 ? '<span class="aud-chip ok"><i class="fa fa-check"></i> $0</span>'
							: '<span class="aud-chip mal"><i class="fa fa-exclamation-triangle"></i> ' + fmt(c.diferencia) + '</span>');
					var al = c.alertas ? '<span class="aud-chip alerta"><i class="fa fa-flag"></i> ' + c.alertas + '</span>' : '<span class="text-muted">—</span>';
					$t.append('<tr class="clic" data-id="' + c.id_caja + '"><td><b>N° ' + c.id_caja + '</b><span class="sub">' + esc(c.web ? 'Caja web' : c.computador) + '</span></td>'
						+ '<td>' + esc(c.usuario || '—') + '</td><td>' + esc(c.apertura) + '</td><td>' + esc(c.cierre || '—') + '</td>'
						+ '<td class="num">' + fmt(c.ventas) + '<span class="sub">' + c.docs + ' doc.</span></td>'
						+ '<td class="num">' + dif + '</td><td>' + al + '</td></tr>');
				});
				verCaja(cajas[0].id_caja);
			}).fail(function (x) {
				$('#audCajas tbody').html('<tr><td colspan="7" class="aud-vacio">No se pudieron cargar las cajas</td></tr>');
				error(x);
			});
		}
		$('#btnConsultar').on('click', consultar);
		$('#audCajas').on('click', 'tr.clic', function () { verCaja(Number($(this).data('id'))); });

		// ---------------- una caja ----------------
		function verCaja(id) {
			var p = ++pedido;
			$('#audCajas tr.clic').removeClass('activa').filter('[data-id="' + id + '"]').addClass('activa');
			$('#audDetalle').show().addClass('cargando');
			$.ajax({ url: base + 'detalle', type: 'POST', data: { id_caja: id }, dataType: 'json' }).done(function (d) {
				if (p !== pedido) return;
				detalle = d;
				pintar();
				$('#btnImprimir, #btnCsv').prop('disabled', false);
			}).fail(function (x) { if (p === pedido) error(x); }).always(function () { if (p === pedido) $('#audDetalle').removeClass('cargando'); });
		}

		function kpi(titulo, valor, sub, clase, destacado) {
			return '<div class="aud-kpi' + (destacado ? ' destacado' : '') + '"><small>' + esc(titulo) + '</small><b' + (clase ? ' class="' + clase + '"' : '') + '>' + valor + '</b>'
				+ (sub ? '<span>' + sub + '</span>' : '') + '</div>';
		}
		function pintar() {
			var d = detalle, c = d.caja, r = d.resumen, k = d.indicadores;
			$('#audTitulo').text('Caja N° ' + c.id_caja + (c.usuario ? ' · ' + c.usuario : ''));
			$('#audEstado').html(c.cerrada ? '<span class="aud-chip">Cerrada</span>' : '<span class="aud-chip azul">Abierta</span>');
			$('#audMeta').text((c.web ? 'Caja web' : c.computador) + ' · Apertura ' + c.apertura + (c.cierre ? ' · Cierre ' + c.cierre + (c.usuario_cierre ? ' por ' + c.usuario_cierre : '') : ''));

			var dif;
			if (!c.cerrada) dif = kpi('Diferencia de cierre', '—', 'La caja sigue abierta');
			else if (c.diferencia === 0) dif = kpi('Diferencia de cierre', '<i class="fa fa-check-circle"></i> $0', 'Cuadrada', 'ok');
			else dif = kpi('Diferencia de cierre', '<i class="fa fa-exclamation-triangle"></i> ' + fmt(c.diferencia), c.diferencia < 0 ? 'Faltante' : 'Sobrante', 'mal', true);
			var esperas = k.esperas + k.esperas_recuperadas + k.esperas_anuladas;
			$('#audKpis').html(
				kpi('Ventas', fmt(r.ventas), r.cantidad_ventas + ' documentos · neto de devoluciones')
				+ dif
				+ kpi('Documentos anulados', k.anuladas, fmt(k.monto_anulado), '', k.anuladas > 0)
				+ kpi('Devoluciones (NC)', k.nc, fmt(k.monto_nc), '', k.nc > 0)
				+ kpi('Descuentos', fmt(k.monto_descuentos), k.descuentos + ' documentos')
				+ kpi('Cortesías', fmt(k.monto_cortesias), k.cortesias + ' documentos')
				+ kpi('Ingresos y retiros', fmt(r.ingresos), 'Retiros ' + fmt(r.egresos))
				+ kpi('Ventas en espera', k.esperas, 'Recuperadas ' + k.esperas_recuperadas + ' · anuladas ' + k.esperas_anuladas + ' · pendientes ' + k.esperas_pendientes, '', k.esperas_anuladas > 0 || k.esperas_pendientes > 0)
				+ kpi('Productos omitidos', k.omitidos, fmt(k.monto_omitidos) + ' sin enviar a mesas', '', k.omitidos > 0)
				+ kpi('Ventas vaciadas', k.vaciadas, fmt(k.monto_vaciadas) + ' sin cobrar', '', k.vaciadas > 0)
				+ kpi('Anulados en mesas', k.anulaciones_mesa, fmt(k.monto_anulaciones_mesa), '', k.anulaciones_mesa > 0)
				+ kpi('Gaveta sin venta', k.gaveta, 'Aperturas de gaveta')
			);
			if (!d.bitacora) $('#audKpis').append('<div class="aud-kpi"><small>Bitácora de la Caja web</small><b>—</b><span>Sin ventas en espera ni omitidos: la base aún no se actualiza</span></div>');

			// Arqueo
			var $a = $('#audArqueo tbody').empty(), te = 0, tr = 0, conRendido = false;
			$.each(d.arqueo, function (i, f) {
				te += f.esperado;
				var dd = '';
				if (f.rendido !== null) {
					conRendido = true;
					tr += f.rendido;
					var x = f.rendido - f.esperado;
					dd = x === 0 ? '<span style="color:#006300"><i class="fa fa-check"></i> $0</span>' : '<span style="color:#b42318"><i class="fa fa-exclamation-triangle"></i> ' + fmt(x) + '</span>';
				}
				$a.append('<tr><td>' + esc(f.nombre) + '</td><td class="num">' + fmt(f.esperado) + '</td><td class="num">' + (f.rendido === null ? '—' : fmt(f.rendido)) + '</td><td class="num">' + (dd || '—') + '</td></tr>');
			});
			if (!d.arqueo.length) $a.html('<tr><td colspan="4">' + vacio('Sin formas de pago') + '</td></tr>');
			$('#audArqueo tfoot').html(d.arqueo.length ? '<tr><td>Total</td><td class="num">' + fmt(te) + '</td><td class="num">' + (conRendido ? fmt(tr) : '—') + '</td><td class="num">'
				+ (conRendido ? fmt(tr - te) : '—') + '</td></tr>' : '');
			$('#audArqueoSub').text(c.cerrada ? 'Esperado por el sistema contra lo rendido al cerrar' : 'Esperado por el sistema (la caja sigue abierta)');

			pintarLinea();
			pintarTablas();
		}

		// ---------------- línea de tiempo ----------------
		var ICONOS = { apertura: 'fa-unlock', cierre: 'fa-lock', ingreso: 'fa-arrow-down', retiro: 'fa-arrow-up', anulacion: 'fa-ban', anulacion_mesa: 'fa-ban',
			devolucion: 'fa-undo', cortesia: 'fa-gift', descuento: 'fa-percent', espera_creada: 'fa-pause', espera_recuperada: 'fa-play',
			espera_anulada: 'fa-ban', productos_omitidos: 'fa-exclamation-triangle', venta_vaciada: 'fa-trash', gaveta_abierta: 'fa-inbox',
			garzon_cambiado: 'fa-user' };
		var GRUPOS = { caja: ['apertura', 'cierre', 'ingreso', 'retiro', 'gaveta_abierta'], documentos: ['descuento', 'cortesia', 'devolucion'] };
		function enFiltro(h) {
			if (filtroLinea === 'todo') return true;
			if (filtroLinea === 'alerta') return h.nivel === 'alerta' || h.nivel === 'critico';
			if (filtroLinea === 'espera') return h.tipo.indexOf('espera_') === 0;
			return $.inArray(h.tipo, GRUPOS[filtroLinea] || []) >= 0;
		}
		function pintarLinea() {
			var $l = $('#audLinea').empty(), n = 0;
			$.each(detalle.linea, function (i, h) {
				if (!enFiltro(h)) return;
				n++;
				$l.append('<li class="' + esc(h.nivel) + '"><span class="hora">' + esc(h.hora) + '</span><i class="icono fa ' + (ICONOS[h.tipo] || 'fa-circle-o') + '"'
					+ (h.nivel !== 'info' ? ' title="' + (h.nivel === 'critico' ? 'Crítico' : 'Para revisar') + '"' : '') + '></i>'
					+ '<span class="titulo">' + esc(h.titulo) + '</span><span class="monto">' + (h.monto ? fmt(h.monto) : '') + '</span>'
					+ (h.texto ? '<span class="texto">' + esc(h.texto) + '</span>' : '<span class="texto"></span>')
					+ '<span class="usuario">' + esc(h.usuario) + '</span></li>');
			});
			if (!n) $l.html('<li style="display:block">' + vacio(filtroLinea === 'alerta' ? 'Sin alertas en esta caja' : 'Nada que mostrar con este filtro') + '</li>');
		}
		$('#audFiltroLinea').on('click', 'button', function () {
			filtroLinea = $(this).data('filtro');
			$(this).addClass('activo').siblings().removeClass('activo');
			if (detalle) pintarLinea();
		});

		// ---------------- tablas de detalle ----------------
		function tabla(cabeza, filas, textoVacio, pie) {
			if (!filas.length) return vacio(textoVacio);
			return '<div class="aud-scroll"><table class="aud-tabla"><thead><tr>' + $.map(cabeza, function (h) {
				return '<th' + (h.charAt(0) === '$' ? ' class="num">' + esc(h.substr(1)) : '>' + esc(h)) + '</th>';
			}).join('') + '</tr></thead><tbody>' + filas.join('') + '</tbody>' + (pie || '') + '</table></div>';
		}
		function td(t, sub) { return '<td>' + esc(t) + (sub ? '<span class="sub">' + esc(sub) + '</span>' : '') + '</td>'; }
		function tdn(n) { return '<td class="num">' + fmt(n) + '</td>'; }
		function ref(doc) { return doc.documento + (doc.folio ? ' N° ' + doc.folio : ''); }
		function pintarTablas() {
			var d = detalle, idCaja = d.caja.id_caja;
			// Anulaciones: documentos, ventas en espera y productos de mesa
			var an = [];
			$.each(d.documentos, function (i, x) {
				if (x.anulada) an.push({ f: x.fecha_anulacion || x.fecha, html: '<tr><td>' + diaHora(x.fecha_anulacion || x.fecha) + '</td>' + td('Documento') + td(ref(x), x.cliente ? 'Cliente: ' + x.cliente : '')
					+ td(x.motivo) + td(x.usuario_anulacion) + tdn(x.monto_anulado) + '</tr>' });
			});
			$.each(d.esperas, function (i, e) {
				if (e.estado === 'anulada' && e.id_caja_cierre === idCaja) an.push({ f: e.fecha_cierre, html: '<tr><td>' + diaHora(e.fecha_cierre) + '</td>' + td('Venta en espera')
					+ td('«' + e.nombre + '»', e.productos) + td(e.motivo) + td(e.usuario_cierre) + tdn(e.total) + '</tr>' });
			});
			$.each(d.anulaciones_mesa, function (i, m) {
				an.push({ f: m.fecha, html: '<tr><td>' + diaHora(m.fecha) + '</td>' + td('Producto en mesa') + td(num(m.cantidad) + '× ' + m.producto, m.mesa)
					+ td(m.motivo) + td(m.usuario) + tdn(m.total) + '</tr>' });
			});
			an.sort(function (a, b) { return a.f < b.f ? -1 : (a.f > b.f ? 1 : 0); });
			$('#audTabAnulados').html(tabla(['Hora', 'Tipo', 'Qué', 'Motivo', 'Usuario', '$Monto'], $.map(an, function (x) { return x.html; }), 'Sin anulaciones en esta caja'));

			// Ventas en espera
			var ESTADOS = { espera: '<span class="aud-chip alerta">Pendiente</span>', recuperada: '<span class="aud-chip ok">Recuperada</span>', anulada: '<span class="aud-chip mal">Anulada</span>' };
			$('#audTabEsperas').html(tabla(['Creada', 'Nombre', 'Productos', '$Total', 'Estado', 'Resuelta', 'Justificación'], $.map(d.esperas, function (e) {
				return '<tr><td>' + diaHora(e.fecha) + '<span class="sub">' + esc(e.usuario) + (e.id_caja !== idCaja ? ' · caja N° ' + e.id_caja : '') + '</span></td>'
					+ td('«' + e.nombre + '»', e.origen !== 'Manual' ? e.origen : '') + td(e.productos) + tdn(e.total) + '<td>' + (ESTADOS[e.estado] || esc(e.estado)) + '</td>'
					+ '<td>' + (e.fecha_cierre ? diaHora(e.fecha_cierre) + '<span class="sub">' + esc(e.usuario_cierre) + (e.id_caja_cierre !== idCaja ? ' · caja N° ' + e.id_caja_cierre : '') + '</span>' : '—') + '</td>'
					+ td(e.motivo || '') + '</tr>';
			}), d.bitacora ? 'Sin ventas en espera en esta caja' : 'La bitácora de la Caja web aún no está en esta base'));

			// Omitidos y vaciadas
			var ov = $.grep(d.eventos, function (e) { return e.evento === 'productos_omitidos' || e.evento === 'venta_vaciada'; });
			$('#audTabOmitidos').html(tabla(['Hora', 'Qué pasó', 'Productos', '$Monto', 'Usuario'], $.map(ov, function (e) {
				return '<tr><td>' + diaHora(e.fecha) + '</td>' + td(e.evento === 'venta_vaciada' ? 'Venta vaciada sin cobrar' : 'Omitidos' + (e.mesa ? ' en ' + e.mesa : ''),
					e.evento === 'productos_omitidos' ? e.origen : '') + td(e.lineas) + tdn(e.monto) + td(e.usuario) + '</tr>';
			}), d.bitacora ? 'Sin productos omitidos ni ventas vaciadas' : 'La bitácora de la Caja web aún no está en esta base'));

			// Descuentos, cortesías y notas de crédito
			var dc = $.grep(d.documentos, function (x) { return !x.anulada && (x.descuento > 0 || x.cortesia || x.es_nc); });
			$('#audTabDescuentos').html(tabla(['Hora', 'Documento', 'Tipo', '$Total', '$Descuento', 'Pagos', 'Usuario'], $.map(dc, function (x) {
				var tipo = x.es_nc ? 'Devolución' + (x.referencia ? ' de ' + x.referencia : '') : (x.cortesia ? 'Cortesía' : 'Descuento');
				return '<tr><td>' + hora(x.fecha) + '</td>' + td(ref(x), x.cliente) + td(tipo) + tdn(x.total) + tdn(x.descuento) + td(x.pagos) + td(x.usuario) + '</tr>';
			}), 'Sin descuentos, cortesías ni devoluciones'));

			// Ingresos y retiros
			var ti = 0, tr = 0;
			$('#audTabMovimientos').html(tabla(['Hora', 'Tipo', 'Motivo', '$Monto'], $.map(d.movimientos, function (m) {
				ti += m.ingreso; tr += m.egreso;
				return '<tr><td>' + diaHora(m.fecha) + '</td>' + td(m.ingreso > 0 ? 'Ingreso' : 'Retiro') + td(m.observacion) + tdn(m.ingreso > 0 ? m.ingreso : -m.egreso) + '</tr>';
			}), 'Sin ingresos ni retiros', '<tfoot><tr><td colspan="3">Ingresos ' + fmt(ti) + ' · retiros ' + fmt(tr) + '</td><td class="num">' + fmt(ti - tr) + '</td></tr></tfoot>'));

			// Todos los documentos
			$('#audTabDocumentos').html(tabla(['Hora', 'Documento', 'Cliente', 'Pagos', '$Total', 'Estado'], $.map(d.documentos, function (x) {
				var est = x.anulada ? '<span class="aud-chip mal">Anulada</span>' : (x.es_nc ? '<span class="aud-chip alerta">Devolución</span>' : (x.cortesia ? '<span class="aud-chip">Cortesía</span>' : ''));
				return '<tr' + (x.anulada ? ' class="anulada"' : '') + '><td>' + hora(x.fecha) + '</td>' + td(ref(x)) + td(x.cliente) + td(x.pagos) + tdn(x.anulada ? x.monto_anulado : x.total) + '<td>' + est + '</td></tr>';
			}), 'Sin documentos en esta caja'));

			var n = { anulados: an.length, esperas: d.esperas.length, omitidos: ov.length, descuentos: dc.length, movimientos: d.movimientos.length, documentos: d.documentos.length };
			$('#audTabs .badge').each(function () { var v = n[$(this).data('n')] || 0; $(this).text(v).toggle(v > 0); });
		}

		// ---------------- imprimir y exportar ----------------
		$('#btnImprimir').on('click', function () { window.print(); });
		$('#btnCsv').on('click', function () {
			if (!detalle) return;
			var c = function (v) { v = v == null ? '' : String(v); return /[";\n]/.test(v) ? '"' + v.replace(/"/g, '""') + '"' : v; };
			var filas = [['Caja', 'Fecha', 'Hecho', 'Detalle', 'Monto', 'Usuario', 'Nivel']];
			$.each(detalle.linea, function (i, h) {
				filas.push([detalle.caja.id_caja, h.fecha, h.titulo, h.texto, h.monto, h.usuario, h.nivel === 'info' ? '' : (h.nivel === 'critico' ? 'Crítico' : 'Revisar')]);
			});
			var csv = '﻿' + $.map(filas, function (f) { return $.map(f, c).join(';'); }).join('\r\n');
			var a = document.createElement('a');
			a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
			a.download = 'auditoria_caja_' + detalle.caja.id_caja + '.csv';
			document.body.appendChild(a);
			a.click();
			setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 500);
		});

		$(function () { consultar(); });
	})();
</script>
