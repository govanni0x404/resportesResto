<script>
	/* =====================================================================
	   Dashboard · Análisis del período (10-11-2026)
	   Pide Dashboard/analitica con los mismos filtros del Dashboard (caja y período) y dibuja KPIs, gráficos y
	   rankings. Se actualiza al entrar y con el botón Consultar. Números: miles con coma y decimales con punto
	   (como el resto del Dashboard). Textos que vienen de la base se escapan antes de entrar al HTML.
	   ===================================================================== */
	(function ($) {
		var URL = "<?php echo base_url(); ?>index.php/Dashboard/analitica";
		var AZUL = '#2a78d6', NARANJO = '#eb6834', GRILLA = '#e1e0d9', TENUE = '#898781';
		var DIAS = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
		var graficos = {};
		var pedido = 0;

		// ---------- formato ----------
		function num(n, dec) {
			n = Number(n) || 0;
			var p = Math.abs(n).toFixed(dec || 0).split('.');
			p[0] = p[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
			return (n < 0 ? '-' : '') + p.join('.');
		}
		function plata(n) { n = Math.round(Number(n) || 0); return (n < 0 ? '-$' : '$') + num(Math.abs(n)); }
		function compacto(n) {
			var a = Math.abs(n);
			if (a >= 1e6) return '$' + num(n / 1e6, a >= 1e7 ? 0 : 1) + 'M';
			if (a >= 1e4) return '$' + num(n / 1e3, 0) + 'K';
			return plata(n);
		}
		function pct(parte, total, dec) { return total ? num(parte / total * 100, dec === undefined ? 1 : dec) + '%' : '—'; }
		function h(s) {
			return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
				return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
			});
		}
		function fechaCorta(iso) { var p = String(iso).split('-'); return p[2] + '-' + p[1]; }
		function fechaLarga(iso) { var p = String(iso).split('-'); return p[2] + '-' + p[1] + '-' + p[0]; }
		function diaSemana(iso) { var p = String(iso).split('-'); return DIAS[new Date(+p[0], +p[1] - 1, +p[2]).getDay()]; }
		function duracion(seg) {
			if (seg == null) return '—';
			var m = Math.round(seg / 60);
			return m >= 60 ? Math.floor(m / 60) + ' h ' + (m % 60) + ' min' : m + ' min';
		}
		function vacio(texto) { return '<div class="dsa-vacio">' + h(texto) + '</div>'; }

		// ---------- carga ----------
		function filtros() {
			var dr = $('#date-range-picker').data('daterangepicker');
			var hoy = (window.moment ? moment().format('DD-MM-YYYY') : '');
			return {
				id_caja: Number($('#cboCajas').val()) || 0,
				desde: dr ? dr.startDate.format('DD-MM-YYYY') : hoy,
				hasta: dr ? dr.endDate.format('DD-MM-YYYY') : hoy
			};
		}
		function cargar() {
			var mio = ++pedido;
			// Mientras recarga, se mantiene lo anterior atenuado (sin parpadeo)
			$('#dsa').addClass('cargando');
			$.ajax({ url: URL, type: 'POST', data: filtros(), dataType: 'json' }).done(function (r) {
				if (mio !== pedido) return;
				$('#dsaError').hide();
				dibujar(r);
			}).fail(function (x) {
				if (mio !== pedido) return;
				var msg = (x.responseJSON && x.responseJSON.error) || 'No se pudo cargar el análisis del período.';
				$('#dsaError').text(msg).show();
			}).always(function () {
				if (mio === pedido) $('#dsa').removeClass('cargando');
			});
		}

		function dibujar(r) {
			var p = r.periodo, a = r.actual;
			$('#dsaPeriodo').text(p.dias === 1 ? fechaLarga(p.desde) : fechaLarga(p.desde) + ' al ' + fechaLarga(p.hasta) + ' · ' + p.dias + ' días');
			kpis(r);
			porHora(r.por_hora, a.venta_bruta);
			porDia(r);
			rank('#dsaTop', r.productos.top, function (x) { return x.monto; }, function (x) {
				return num(x.cantidad, x.cantidad % 1 ? 2 : 0) + ' uds · ' + x.familia;
			}, 'Sin ventas de productos en el período');
			rank('#dsaFamilias', r.familias, function (x) { return x.monto; }, function (x) {
				return pct(x.monto, sumar(r.familias, 'monto')) + ' · ' + num(x.cantidad, x.cantidad % 1 ? 2 : 0) + ' uds · ' + x.productos + (x.productos === 1 ? ' producto' : ' productos');
			}, 'Sin ventas en el período');
			menos(r.productos);
			mesaDirecta(r);
			rank('#dsaGarzones', r.garzones, function (x) { return x.venta; }, function (x) {
				return 'Propina ' + plata(x.propina) + ' · ' + x.docs + (x.docs === 1 ? ' documento' : ' documentos')
					+ ' · ticket ' + plata(x.docs ? x.venta / x.docs : 0) + (x.docs_mesa ? ' · ' + x.docs_mesa + ' de mesa' : '');
			}, 'Sin ventas en el período');
			rank('#dsaPagos', r.formas_pago, function (x) { return x.monto; }, function (x) {
				return pct(x.monto, sumar(r.formas_pago, 'monto')) + ' · ' + x.docs + (x.docs === 1 ? ' documento' : ' documentos');
			}, 'Sin pagos en el período');
			control(a);
		}
		function sumar(lista, campo) { var t = 0; $.each(lista || [], function (i, x) { t += Number(x[campo]) || 0; }); return t; }

		// ---------- KPIs con comparación ----------
		function delta(actual, anterior, p) {
			var ref = p.dias === 1 ? fechaLarga(p.anterior_desde) : fechaLarga(p.anterior_desde) + ' al ' + fechaLarga(p.anterior_hasta);
			if (!anterior) return '<span>Sin datos para comparar con ' + h(ref) + '</span>';
			var d = (actual - anterior) / Math.abs(anterior) * 100;
			var clase = d > 0.05 ? 'sube' : (d < -0.05 ? 'baja' : '');
			var icono = clase === 'sube' ? 'fa-arrow-up' : (clase === 'baja' ? 'fa-arrow-down' : 'fa-minus');
			return '<b class="' + clase + '"><i class="fa ' + icono + '" aria-hidden="true"></i> ' + (d > 0 ? '+' : '') + num(d, 1) + '%</b>'
				+ '<span>vs ' + h(ref) + '</span>';
		}
		function kpis(r) {
			var a = r.actual, b = r.anterior, p = r.periodo;
			var tiles = [
				{ etiqueta: 'Venta neta', valor: plata(a.venta_neta), delta: delta(a.venta_neta, b.venta_neta, p), heroe: true },
				{ etiqueta: 'Documentos', valor: num(a.documentos), delta: delta(a.documentos, b.documentos, p) },
				{ etiqueta: 'Ticket promedio', valor: plata(a.ticket_promedio), delta: delta(a.ticket_promedio, b.ticket_promedio, p) },
				{ etiqueta: 'Propinas', valor: plata(a.propinas), delta: '<span>' + pct(a.propinas, a.venta_bruta) + ' de la venta bruta</span>' }
			];
			$('#dsaKpis').html($.map(tiles, function (t) {
				return '<div class="dsa-kpi' + (t.heroe ? ' heroe' : '') + '"><div class="dsa-kpi-etiqueta">' + h(t.etiqueta) + '</div>'
					+ '<div class="dsa-kpi-valor">' + h(t.valor) + '</div><div class="dsa-kpi-delta">' + t.delta + '</div></div>';
			}).join(''));
		}

		// ---------- gráficos (Chart.js 3, cargado en menuFooter) ----------
		function grafico(id, config) {
			if (graficos[id]) { graficos[id].destroy(); delete graficos[id]; }
			if (!window.Chart) return;
			graficos[id] = new Chart(document.getElementById(id), config);
		}
		function ejes() {
			return {
				x: { grid: { display: false }, ticks: { color: TENUE, font: { size: 11 } } },
				y: { beginAtZero: true, grid: { color: GRILLA, lineWidth: 1, drawBorder: false }, ticks: { color: TENUE, font: { size: 11 }, maxTicksLimit: 5, callback: function (v) { return compacto(v); } } }
			};
		}
		function tooltipBase() {
			return { backgroundColor: '#2A1D17', padding: 10, cornerRadius: 8, displayColors: false, titleFont: { weight: '600' }, bodyFont: { weight: '700' } };
		}
		function porHora(filas, ventaBruta) {
			var tabla = '<table><thead><tr><th>Hora</th><th>Documentos</th><th>Venta</th></tr></thead><tbody>';
			if (!filas.length) {
				grafico('dsaChartHora', { type: 'bar', data: { labels: [], datasets: [] } });
				$('#dsaTablaHora').html(vacio('Sin ventas en el período'));
				return;
			}
			// Horas continuas entre la primera y la última con venta (las vacías en 0, para ver los huecos)
			var porH = {}, min = 23, max = 0;
			$.each(filas, function (i, f) { porH[f.h] = f; min = Math.min(min, f.h); max = Math.max(max, f.h); });
			var labels = [], montos = [], docs = [];
			for (var x = min; x <= max; x++) {
				labels.push((x < 10 ? '0' : '') + x + ':00');
				montos.push(porH[x] ? porH[x].monto : 0);
				docs.push(porH[x] ? porH[x].docs : 0);
				tabla += '<tr><td>' + labels[labels.length - 1] + '</td><td>' + num(docs[docs.length - 1]) + '</td><td>' + plata(montos[montos.length - 1]) + '</td></tr>';
			}
			$('#dsaTablaHora').html(tabla + '</tbody></table>');
			var tt = tooltipBase();
			tt.callbacks = {
				title: function (it) { var i = it[0].dataIndex, hh = min + i; return (hh < 10 ? '0' : '') + hh + ':00 – ' + (hh < 10 ? '0' : '') + hh + ':59'; },
				label: function (it) { return plata(it.raw) + ' · ' + docs[it.dataIndex] + ' doc. · ' + pct(it.raw, ventaBruta) + ' del día'; }
			};
			grafico('dsaChartHora', {
				type: 'bar',
				data: { labels: labels, datasets: [{ data: montos, backgroundColor: AZUL, hoverBackgroundColor: '#1c5cab', borderRadius: 4, borderSkipped: 'start', maxBarThickness: 24 }] },
				options: { maintainAspectRatio: false, animation: { duration: 250 }, plugins: { legend: { display: false }, tooltip: tt }, scales: ejes() }
			});
		}
		function porDia(r) {
			var dias = r.por_dia, p = r.periodo, $c = $('#dsaDia');
			if (graficos.dsaChartDia) { graficos.dsaChartDia.destroy(); delete graficos.dsaChartDia; }
			if (p.dias === 1) {
				// Un solo día: la hora punta y el rango de atención en vez de un gráfico de un punto
				$('#dsaDiaTitulo').text('Hora punta');
				$('#dsaDiaSub').text('La hora con más venta del día');
				var top = null;
				$.each(r.por_hora, function (i, f) { if (!top || f.monto > top.monto) top = f; });
				if (!top) { $c.html(vacio('Sin ventas en el período')); return; }
				var hh = (top.h < 10 ? '0' : '') + top.h;
				var primera = r.por_hora[0].h, ultima = r.por_hora[r.por_hora.length - 1].h;
				$c.html('<div class="dsa-resumen-dia"><div class="dsa-kpi-valor">' + hh + ':00 – ' + hh + ':59</div>'
					+ '<div class="dsa-mini"><div><small>Venta en esa hora</small><b>' + plata(top.monto) + '</b></div>'
					+ '<div><small>Del total del día</small><b>' + pct(top.monto, r.actual.venta_bruta) + '</b></div>'
					+ '<div><small>Documentos</small><b>' + num(top.docs) + '</b></div>'
					+ '<div><small>Horas con venta</small><b>' + (primera < 10 ? '0' : '') + primera + ':00 a ' + (ultima < 10 ? '0' : '') + ultima + ':59</b></div></div></div>');
				return;
			}
			$('#dsaDiaTitulo').text('Ventas por día');
			$('#dsaDiaSub').text('Venta bruta de cada día del período');
			if (!dias.length) { $c.html(vacio('Sin ventas en el período')); return; }
			// Días continuos (los sin venta en 0) y el mejor día
			var porD = {}, mejor = null;
			$.each(dias, function (i, d) { porD[d.dia] = d; if (!mejor || d.monto > mejor.monto) mejor = d; });
			var labels = [], montos = [], isos = [], f = new Date(p.desde + 'T12:00:00'), fin = new Date(p.hasta + 'T12:00:00');
			while (f <= fin) {
				var iso = f.getFullYear() + '-' + ('0' + (f.getMonth() + 1)).slice(-2) + '-' + ('0' + f.getDate()).slice(-2);
				isos.push(iso); labels.push(fechaCorta(iso)); montos.push(porD[iso] ? porD[iso].monto : 0);
				f.setDate(f.getDate() + 1);
			}
			$c.html('<div class="dsa-chart" style="height:180px"><canvas id="dsaChartDia" role="img" aria-label="Ventas por día"></canvas></div>'
				+ '<div class="dsa-kpi-delta" style="margin-top:8px"><span>Mejor día: <b>' + h(diaSemana(mejor.dia)) + ' ' + h(fechaLarga(mejor.dia)) + '</b> · ' + plata(mejor.monto) + '</span>'
				+ '<span>Promedio diario: <b>' + plata(r.actual.venta_bruta / p.dias) + '</b></span></div>'
				+ '<details class="dsa-tabla"><summary>Ver como tabla</summary><table><thead><tr><th>Día</th><th>Documentos</th><th>Venta</th></tr></thead><tbody>'
				+ $.map(isos, function (iso, i) { return '<tr><td>' + h(diaSemana(iso).slice(0, 3) + ' ' + fechaLarga(iso)) + '</td><td>' + num(porD[iso] ? porD[iso].docs : 0) + '</td><td>' + plata(montos[i]) + '</td></tr>'; }).join('')
				+ '</tbody></table></details>');
			var tt = tooltipBase();
			tt.mode = 'index'; tt.intersect = false;
			tt.callbacks = {
				title: function (it) { var iso = isos[it[0].dataIndex]; return diaSemana(iso) + ' ' + fechaLarga(iso); },
				label: function (it) { var d = porD[isos[it.dataIndex]]; return plata(it.raw) + ' · ' + (d ? d.docs : 0) + ' doc.'; }
			};
			grafico('dsaChartDia', {
				type: 'line',
				data: { labels: labels, datasets: [{ data: montos, borderColor: AZUL, backgroundColor: 'rgba(42, 120, 214, .10)', fill: true, borderWidth: 2, tension: 0,
					pointRadius: labels.length > 31 ? 0 : 3, pointHoverRadius: 5, pointBackgroundColor: AZUL, pointBorderColor: '#fff', pointBorderWidth: 2 }] },
				options: { maintainAspectRatio: false, animation: { duration: 250 }, interaction: { mode: 'index', intersect: false },
					plugins: { legend: { display: false }, tooltip: tt }, scales: ejes() }
			});
		}

		// ---------- listas ----------
		// maxFijo: escala común con otra lista (los menos vendidos se miden contra el más vendido)
		function rank(sel, lista, valor, sub, textoVacio, maxFijo) {
			lista = lista || [];
			if (!lista.length) { $(sel).html(vacio(textoVacio)); return; }
			var max = maxFijo || 0;
			$.each(lista, function (i, x) { max = Math.max(max, Number(valor(x)) || 0); });
			$(sel).html('<div class="dsa-rank">' + $.map(lista, function (x) {
				var v = Number(valor(x)) || 0, ancho = max > 0 ? Math.max(0, v / max * 100) : 0, s = sub(x);
				return '<div class="dsa-rank-fila" title="' + h(x.nombre + ': ' + plata(v) + ' · ' + s) + '">'
					+ '<span class="dsa-rank-nombre">' + h(x.nombre) + '</span><span class="dsa-rank-valor">' + plata(v) + '</span>'
					+ '<span class="dsa-rank-barra"><i style="width:' + ancho.toFixed(2) + '%"></i></span>'
					+ '<span class="dsa-rank-sub">' + h(s) + '</span></div>';
			}).join('') + '</div>');
		}
		function menos(pr) {
			var sinVenta = pr.sin_venta > 0
				? '<div class="dsa-kpi-delta" style="margin-top:12px"><span><b>' + num(pr.sin_venta) + '</b> ' + (pr.sin_venta === 1 ? 'producto' : 'productos') + ' de la carta sin ventas en el período</span></div>' : '';
			// Con 10 o menos productos vendidos la lista repetiría el top
			if (pr.con_venta <= pr.top.length) {
				$('#dsaMenos').html(vacio(pr.con_venta ? 'Todos los productos vendidos ya están en el top' : 'Sin ventas de productos en el período') + sinVenta);
				return;
			}
			rank('#dsaMenos', pr.menos, function (x) { return x.monto; }, function (x) {
				return num(x.cantidad, x.cantidad % 1 ? 2 : 0) + ' uds · ' + x.familia;
			}, '', pr.top.length ? pr.top[0].monto : 0);
			$('#dsaMenosSub').text('Con al menos una venta · barra comparada con el más vendido');
			$('#dsaMenos').append(sinVenta);
		}
		function mesaDirecta(r) {
			var a = r.actual, m = r.mesas, total = a.venta_mesa + a.venta_directa, $c = $('#dsaMesa');
			if (!total) { $c.html(vacio('Sin ventas en el período')); return; }
			var partes = [
				{ nombre: 'Mesa', monto: a.venta_mesa, color: AZUL },
				{ nombre: 'Venta directa', monto: a.venta_directa, color: NARANJO }
			];
			var seg = $.map(partes, function (x) {
				if (!x.monto) return '';
				var share = x.monto / total * 100, txt = share >= 12 ? num(share, 0) + '%' : '';
				return '<span style="flex:' + x.monto + ' 1 0; background:' + x.color + '" title="' + h(x.nombre + ': ' + plata(x.monto) + ' (' + num(share, 1) + '%)') + '">' + txt + '</span>';
			}).join('');
			var leyenda = $.map(partes, function (x) {
				return '<span><i style="background:' + x.color + '"></i>' + h(x.nombre) + ' · ' + plata(x.monto) + '</span>';
			}).join('');
			$c.html('<div class="dsa-seg" role="img" aria-label="' + h('Mesa ' + pct(a.venta_mesa, total) + ', venta directa ' + pct(a.venta_directa, total)) + '">' + seg + '</div>'
				+ '<div class="dsa-leyenda">' + leyenda + '</div>'
				+ '<div class="dsa-mini">'
				+ '<div><small>Mesas atendidas</small><b>' + num(m.mesas_atendidas) + '</b></div>'
				+ '<div><small>Ticket por mesa</small><b>' + plata(m.mesas_atendidas ? a.venta_mesa / m.mesas_atendidas : 0) + '</b></div>'
				+ '<div><small>Ticket venta directa</small><b>' + plata(a.docs_directa ? a.venta_directa / a.docs_directa : 0) + '</b></div>'
				+ '<div><small>Tiempo promedio en mesa</small><b>' + duracion(m.tiempo_promedio_seg) + '</b></div>'
				+ '</div>');
		}
		function control(a) {
			var base = a.venta_bruta;
			var filas = [
				{ nombre: 'Descuentos', monto: a.descuentos, docs: a.docs_descuento },
				{ nombre: 'Cortesías', monto: a.cortesias, docs: a.docs_cortesia },
				{ nombre: 'Devoluciones (NC)', monto: a.devoluciones, docs: a.docs_devolucion },
				{ nombre: 'Anulaciones', monto: a.anulado, docs: a.docs_anulados },
				{ nombre: 'Propinas', monto: a.propinas, docs: null }
			];
			$('#dsaControl').html('<div class="dsa-medidores">' + $.map(filas, function (f) {
				var share = base ? Math.min(100, f.monto / base * 100) : 0;
				return '<div class="dsa-medidor" title="' + h(f.nombre + ': ' + plata(f.monto) + ' · ' + pct(f.monto, base) + ' de la venta bruta') + '">'
					+ '<span class="dsa-medidor-nombre">' + h(f.nombre) + '</span>'
					+ '<span class="dsa-medidor-barra" role="meter" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' + share.toFixed(1) + '" aria-label="' + h(f.nombre) + '"><i style="width:' + (f.monto > 0 ? share.toFixed(2) : 0) + '%"></i></span>'
					+ '<span class="dsa-medidor-cifra"><b>' + plata(f.monto) + '</b> · ' + pct(f.monto, base)
					+ (f.docs !== null ? ' · ' + num(f.docs) + (f.docs === 1 ? ' doc.' : ' docs.') : '') + '</span></div>';
			}).join('') + '</div>');
		}

		// ---------- disparadores ----------
		$(function () {
			cargar();
			$('#btnTest').on('click', cargar);
		});
	})(window.jQuery);
</script>
