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
			recintosMesas(r.por_mesa || []);
		}

		// ---------- v1.30.3: ventas por recinto y mesa ----------
		var rmFilas = [];
		function recintosMesas(filas) {
			rmFilas = filas;
			var $r = $('#dsaRmRecinto'), elegido = Number($r.val()) || 0, recs = {}, orden = [];
			$.each(filas, function (i, f) { if (!recs[f.id_recinto]) { recs[f.id_recinto] = f.recinto; orden.push(f.id_recinto); } });
			orden.sort(function (x, y) { return String(recs[x]).localeCompare(String(recs[y])); });
			$r.html('<option value="0">Todos los recintos</option>' + $.map(orden, function (id) { return '<option value="' + id + '">' + h(recs[id]) + '</option>'; }).join(''))
				.val(String(recs[elegido] !== undefined ? elegido : 0));
			opcionesMesaRm();
			pintarRm();
		}
		function opcionesMesaRm() {
			var rec = Number($('#dsaRmRecinto').val()) || 0, $m = $('#dsaRmMesa'), elegida = Number($m.val()) || 0, hay = false;
			var mesas = $.grep(rmFilas, function (f) { return !rec || f.id_recinto === rec; }).slice()
				.sort(function (x, y) { return String(x.mesa).localeCompare(String(y.mesa), 'es', { numeric: true }); });
			$m.html('<option value="0">Todas las mesas</option>' + $.map(mesas, function (f) {
				if (f.id_mesa === elegida) hay = true;
				return '<option value="' + f.id_mesa + '">' + h(f.mesa + (rec ? '' : ' · ' + f.recinto)) + '</option>';
			}).join('')).val(String(hay ? elegida : 0));
		}
		function pintarRm() {
			var rec = Number($('#dsaRmRecinto').val()) || 0, mesaSel = Number($('#dsaRmMesa').val()) || 0;
			var total = sumar(rmFilas, 'venta');
			$('#dsaRecintosCard .dsa-rm-chart, #dsaRmKpis, #dsaRecintosCard .dsa-rm-filtros').toggle(rmFilas.length > 0);
			if (!rmFilas.length) {
				$('#dsaRmKpis').html('');
				grafico('dsaChartRm', { type: 'bar', data: { labels: [], datasets: [] } });
				$('#dsaRmTabla').html(vacio('Sin ventas de mesa en el período'));
				return;
			}
			// Filas a mostrar: por recinto (todos) o por mesa (un recinto, o todas si se eligió una mesa sin recinto)
			var porRecinto = !rec && !mesaSel, filas = [];
			if (porRecinto) {
				var idx = {};
				$.each(rmFilas, function (i, f) {
					if (!idx[f.id_recinto]) { idx[f.id_recinto] = { id: f.id_recinto, nombre: f.recinto, venta: 0, docs: 0, propina: 0, pedidos: 0, mesas: 0 }; filas.push(idx[f.id_recinto]); }
					var x = idx[f.id_recinto];
					x.venta += f.venta; x.docs += f.docs; x.propina += f.propina; x.pedidos += f.pedidos; x.mesas++;
				});
			} else {
				filas = $.map($.grep(rmFilas, function (f) { return !rec || f.id_recinto === rec || f.id_mesa === mesaSel; }), function (f) {
					return { id: f.id_mesa, nombre: f.mesa + (rec ? '' : ' · ' + f.recinto), venta: f.venta, docs: f.docs, propina: f.propina, pedidos: f.pedidos, mesas: 1 };
				});
			}
			filas.sort(function (x, y) { return y.venta - x.venta; });
			var base = sumar(filas, 'venta');
			var sel = mesaSel ? $.grep(rmFilas, function (f) { return f.id_mesa === mesaSel; })[0] : null;
			var ambito = sel ? { venta: sel.venta, docs: sel.docs, propina: sel.propina, pedidos: sel.pedidos }
				: { venta: base, docs: sumar(filas, 'docs'), propina: sumar(filas, 'propina'), pedidos: sumar(filas, 'pedidos') };
			$('#dsaRmSub').text(sel ? sel.mesa + ' · ' + sel.recinto : (rec ? $('#dsaRmRecinto option:selected').text() + ' · cada mesa' : 'Cada recinto · solo ventas de mesa'));
			$('#dsaRmKpis').html('<div><small>' + (sel ? 'Venta de la mesa' : 'Venta') + '</small><b>' + plata(ambito.venta) + '</b></div>'
				+ '<div><small>' + (sel ? 'Del total de mesas' : 'Pedidos atendidos') + '</small><b>' + (sel ? pct(sel.venta, total) : num(ambito.pedidos)) + '</b></div>'
				+ '<div><small>Ticket por pedido</small><b>' + plata(ambito.pedidos ? ambito.venta / ambito.pedidos : (ambito.docs ? ambito.venta / ambito.docs : 0)) + '</b></div>'
				+ '<div><small>Propinas</small><b>' + plata(ambito.propina) + '</b></div>');
			var max = 25, vista = filas.slice(0, max);
			var tt = tooltipBase();
			tt.callbacks = { label: function (it) { var f = vista[it.dataIndex]; return plata(f.venta) + ' · ' + pct(f.venta, total) + ' · ' + num(f.docs) + ' doc.'; } };
			grafico('dsaChartRm', {
				type: 'bar',
				data: { labels: $.map(vista, function (f) { return f.nombre; }), datasets: [{ data: $.map(vista, function (f) { return f.venta; }),
					backgroundColor: $.map(vista, function (f) { return sel && f.id === mesaSel ? NARANJO : AZUL; }), borderRadius: 4, borderSkipped: 'start', maxBarThickness: 34 }] },
				options: { maintainAspectRatio: false, animation: { duration: 250 }, plugins: { legend: { display: false }, tooltip: tt }, scales: ejes(),
					onClick: function (e, el) { if (el && el.length) elegirRm(vista[el[0].index]); } }
			});
			var tabla = '<table><thead><tr><th>' + (porRecinto ? 'Recinto' : 'Mesa') + '</th>' + (porRecinto ? '<th>Mesas</th>' : '') + '<th>Pedidos</th><th>Documentos</th><th>Venta</th><th>%</th><th>Ticket</th><th>Propina</th></tr></thead><tbody>'
				+ $.map(filas, function (f, i) {
					return '<tr data-i="' + i + '"' + (sel && f.id === mesaSel ? ' class="activa"' : '') + '><td>' + h(f.nombre) + '</td>' + (porRecinto ? '<td>' + num(f.mesas) + '</td>' : '')
						+ '<td>' + num(f.pedidos) + '</td><td>' + num(f.docs) + '</td><td>' + plata(f.venta) + '</td><td>' + pct(f.venta, base) + '</td>'
						+ '<td>' + plata(f.pedidos ? f.venta / f.pedidos : (f.docs ? f.venta / f.docs : 0)) + '</td><td>' + plata(f.propina) + '</td></tr>';
				}).join('') + '</tbody></table>' + (filas.length > max ? '<small class="text-muted">El gráfico muestra las ' + max + ' con más venta; la tabla, todas.</small>' : '');
			$('#dsaRmTabla').html(tabla).data('filas', filas).data('recinto', porRecinto);
		}
		// Tocar una barra o una fila: un recinto abre sus mesas; una mesa queda elegida
		function elegirRm(f) {
			if (!f) return;
			if ($('#dsaRmTabla').data('recinto')) { $('#dsaRmRecinto').val(String(f.id)); $('#dsaRmMesa').val('0'); opcionesMesaRm(); }
			else { $('#dsaRmMesa').val(String(f.id)); }
			pintarRm();
		}
		$(document).on('change', '#dsaRmRecinto', function () { $('#dsaRmMesa').val('0'); opcionesMesaRm(); pintarRm(); });
		$(document).on('change', '#dsaRmMesa', pintarRm);
		$(document).on('click', '#dsaRmTabla tbody tr', function () { elegirRm(($('#dsaRmTabla').data('filas') || [])[$(this).data('i')]); });
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
