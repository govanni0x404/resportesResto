		// ---------------- v1.25.0: armar una promo de sushi ----------------
		// La promo tiene N piezas que se reparten en grupos; cada grupo = sus piezas + base + rellenos + envoltorio (todas
		// sus piezas iguales). Las configuradas se calculan SIEMPRE sumando los grupos (no hay contador aparte). Los extras
		// (salsas…) van aparte. El servidor vuelve a validar todo (Mcaja::eleccionSushi).
		var su = null;   // { id_producto, promo, indice, grupos: [{piezas, base, envoltorio, rellenos}], extras: {id: cantidad}, ed: {idx, g} }
		function suConfiguradas(excepto) {
			var t = 0;
			$.each(su.grupos, function (i, g) { if (i !== excepto) t += g.piezas; });
			return t;
		}
		/** Máximo de piezas para el grupo que se edita (lo que queda + las suyas, si ya existía) */
		function suMaxEditor() {
			return su.promo.piezas - suConfiguradas(su.ed && su.ed.idx !== null ? su.ed.idx : -1);
		}
		function suGrupoNuevo(piezas) {
			var p = su.promo;
			return { piezas: piezas, base: p.bases.length === 1 ? p.bases[0].id : 0, envoltorio: p.envoltorios.length === 1 ? p.envoltorios[0].id : 0, rellenos: [], nota: '' };
		}
		/** Piezas sugeridas para un grupo nuevo: un "roll" (el paso, o 10 si va de a 1) o lo que falte si es menos */
		function suPiezasSugeridas() {
			var falta = su.promo.piezas - suConfiguradas(-1), paso = su.promo.paso;
			if (suEsRol(su.promo)) return Math.max(0, Math.min(suUnidad(su.promo), falta));   // v1.26.0: 1 roll
			return Math.max(0, Math.min(paso > 1 ? paso : 10, falta));
		}

		function abrirSushi(id, indice) {
			var promo = sushiDe(id), p = producto(id);
			if (!promo || !p) return;
			if (!promo.rellenos.length && !promo.envoltorios.length && !promo.bases.length) {
				toastr.warning('Esta promo todavía no tiene bases, rellenos ni envoltorios: agrégalos en Productos > Promos Sushi');
				return;
			}
			var linea = indice === null ? null : carro[indice];
			su = { id_producto: id, promo: promo, indice: indice, grupos: [], extras: {}, ed: null };
			if (linea && linea.sushi) {
				su.grupos = $.map(linea.sushi.grupos, function (g) { return { piezas: g.piezas, base: g.base || 0, envoltorio: g.envoltorio || 0, rellenos: (g.rellenos || []).slice(), nota: g.nota || '' }; });
				$.each(linea.sushi.extras || [], function (i, e) { su.extras[e.id] = e.cantidad; });
			}
			var rol = suEsRol(promo);   // v1.26.0
			$('#sushiTitulo').text(p.nombre + ' · ' + (rol ? textoCantSushi(promo, promo.piezas) + ' (' + promo.piezas + ' piezas)' : promo.piezas + ' piezas'));
			$('#suTitGrupos').text(rol ? 'Rolls' : 'Grupos de piezas');
			$('#btnSushiNuevo').html('<i class="fa fa-plus"></i> ' + (rol ? 'Agregar roll' : 'Agregar grupo'));
			$('#suTitCant').text(rol ? 'Rolls' : 'Piezas');
			$('#txtNotaSushi').val(linea ? linea.nota || '' : '');
			$('#btnConfirmarSushi').text(linea ? 'Guardar' : 'Agregar');
			$('#suExtrasCaja').toggle(promo.extras.length > 0);
			// Promo nueva: se abre directo el primer grupo para armar
			if (!su.grupos.length) su.ed = { idx: null, g: suGrupoNuevo(suPiezasSugeridas()) };
			pintarSushi();
			$('#modalSushi').modal('show');
		}

		function pintarSushi() {
			var promo = su.promo, total = promo.piezas, conf = suConfiguradas(-1), pend = total - conf;
			var pct = total ? Math.round(conf * 100 / total) : 0;
			var u = suUnidad(promo);   // v1.26.0: por roll, las cifras van en rolls
			$('#suTotal').text(total / u);
			$('#suConfiguradas').text(conf / u);
			$('#suPendientes').text(pend < 0 ? 0 : pend / u);
			$('#suUnidadTotal').text(suEsRol(promo) ? 'Rolls' : 'Total');
			$('#suPendCifra').toggleClass('listo', pend === 0);
			$('#suBarra').css('width', Math.min(100, pct) + '%');
			$('#suPorcentaje').text(pct + '% configurado' + (pend === 0 ? ' · ¡listo!' : ''));

			// Grupos
			$('#suGrupos').html(su.grupos.length ? $.map(su.grupos, function (g, i) {
				var tx = textoGrupoSushi(promo, g), v = unidadesGrupoSushi(promo, g) * valorPiezaSushi(promo, g);
				var editando = su.ed && su.ed.idx === i;
				return '<div class="su-grupo' + (editando ? ' editando' : '') + '" data-i="' + i + '"><div class="su-grupo-cab">'
					+ '<span class="su-pz">' + textoCantSushi(promo, g.piezas) + '</span><span class="su-env">' + esc(tx.titulo) + '</span>'
					+ '<button type="button" class="btn btn-default btn-xs su-editar" title="Editar"><i class="fa fa-pencil"></i></button>'
					+ '<button type="button" class="btn btn-default btn-xs su-duplicar" title="Duplicar"><i class="fa fa-clone"></i></button>'
					+ '<button type="button" class="btn btn-default btn-xs su-eliminar" title="Eliminar"><i class="fa fa-trash text-danger"></i></button></div>'
					+ '<div class="su-grupo-det">' + (tx.rellenos ? esc(tx.rellenos) : '<i>Sin relleno</i>') + (tx.base ? ' · Base ' + esc(tx.base) : '')
					+ (v ? ' · <b>+' + fmt(v) + '</b>' : '') + '</div>'
					+ (g.nota ? '<div class="su-grupo-nota"><i class="fa fa-comment-o"></i> ' + esc(g.nota) + '</div>' : '') + '</div>';   // v1.26.1
			}).join('') : '<div class="su-vacio">Todavía no hay ' + (suEsRol(promo) ? 'rolls' : 'grupos') + '. Arma el primero a la derecha.</div>');
			$('#btnSushiNuevo').prop('disabled', pend <= 0 || !!(su.ed && su.ed.idx === null));

			// Extras
			$('#suExtras').html($.map(promo.extras, function (o) {
				var q = su.extras[o.id] || 0;
				return '<div class="su-extra" data-id="' + o.id + '"><span class="nombre">' + esc(o.nombre) + (o.valor ? ' <small class="text-muted">+' + fmt(o.valor) + '</small>' : '') + '</span>'
					+ '<button type="button" class="btn btn-default btn-xs su-ex-menos"' + (q ? '' : ' disabled') + '><i class="fa fa-minus"></i></button>'
					+ '<b class="q">' + q + '</b><button type="button" class="btn btn-default btn-xs su-ex-mas"><i class="fa fa-plus"></i></button></div>';
			}).join(''));

			pintarEditorSushi();
			var extras = $.map(su.extras, function (q, id) { return { id: Number(id), cantidad: q }; });
			$('#sushiPrecio').text(fmt(producto(su.id_producto).precio + adicionalSushiSel(promo, { grupos: su.grupos, extras: extras })));
			$('#btnConfirmarSushi').prop('disabled', pend !== 0 || !!su.ed);
		}

		function pintarEditorSushi() {
			var promo = su.promo;
			$('#suEditor').toggle(!!su.ed);
			if (!su.ed) return;
			var g = su.ed.g, max = suMaxEditor(), paso = promo.paso, rol = suEsRol(promo), u = suUnidad(promo);
			$('#suEditorTit').text(su.ed.idx === null ? (rol ? 'Nuevo roll' : 'Nuevo grupo') : 'Editando grupo ' + (su.ed.idx + 1));
			// v1.26.0: por roll el número que se ve y se escribe son rolls (por dentro, piezas = rolls x piezas del roll)
			if (!$('#suPiezas').is(':focus')) $('#suPiezas').val(g.piezas ? g.piezas / u : '');
			// v1.26.1: nota de este roll / grupo
			$('#suTitNotaGrupo').text(rol ? 'Nota de este roll' : 'Nota de este grupo');
			if (!$('#suNotaGrupo').is(':focus')) $('#suNotaGrupo').val(g.nota || '');
			$('#suPiezasAyuda').text(rol ? 'Quedan ' + (max / u) + (max / u === 1 ? ' roll' : ' rolls') + ' · de ' + u + ' piezas' : 'Quedan ' + max + (paso > 1 ? ' · de a ' + paso : ''));
			// Atajos: lo que queda y algunos tamaños de roll
			var atajos = [];
			if (paso > 1) { for (var k = paso; k <= max && atajos.length < 5; k += paso) atajos.push(k); }
			else $.each([5, 8, 10], function (i, k) { if (k <= max) atajos.push(k); });
			if (max > 0 && $.inArray(max, atajos) < 0) atajos.push(max);
			$('#suAtajos').html($.map(atajos, function (k) {
				var n = rol ? k / u : k;
				return '<button type="button" class="btn btn-' + (k === g.piezas ? 'success' : 'default') + ' btn-sm su-atajo" data-n="' + k + '">' + (k === max ? (rol ? 'Todos (' + n + ')' : 'Todas (' + n + ')') : n) + '</button>';
			}).join(' '));
			$('#suMenos').prop('disabled', g.piezas <= paso);
			$('#suMas').prop('disabled', g.piezas + paso > max);

			var secciones = [['base', 'Base', promo.bases], ['relleno', 'Rellenos', promo.rellenos], ['envoltorio', 'Envuelto en', promo.envoltorios]];
			$.each(secciones, function (i, sc) {
				var tipo = sc[0], lista = sc[2], $sec = $('#suSec' + tipo.charAt(0).toUpperCase() + tipo.slice(1));
				$sec.toggle(lista.length > 0);
				if (!lista.length) return;
				var multiple = tipo === 'relleno', n = multiple ? g.rellenos.length : (g[tipo] ? 1 : 0);
				var tope = multiple && promo.max_rellenos > 0 && n >= promo.max_rellenos;
				var ayuda = multiple ? (promo.max_rellenos > 0 ? n + '/' + promo.max_rellenos : n + ' elegidos') : 'Elige 1';
				$sec.find('.su-sec-tit small').text(ayuda).toggleClass('falta', n === 0);
				$sec.find('.su-chips').html($.map(lista, function (o) {
					var on = multiple ? $.inArray(o.id, g.rellenos) >= 0 : g[tipo] === o.id;
					return '<span class="su-chip' + (on ? ' on' : (tope ? ' off' : '')) + '" data-id="' + o.id + '">' + esc(o.nombre)
						+ (o.valor ? ' <small>+' + fmt(o.valor) + (rol ? '/roll' : '/pz') + '</small>' : '') + '</span>';
				}).join(''));
			});
		}

		function suPonerPiezas(n) {
			if (!su || !su.ed) return;
			var max = suMaxEditor(), paso = su.promo.paso;
			n = Math.floor(Number(n) || 0);
			if (paso > 1) n = Math.round(n / paso) * paso;
			su.ed.g.piezas = Math.max(0, Math.min(n, max));
			pintarSushi();
		}
		$('#suMenos').on('click', function () { suPonerPiezas(su.ed.g.piezas - su.promo.paso); });
		$('#suMas').on('click', function () { suPonerPiezas(su.ed.g.piezas + su.promo.paso); });
		$('#suAtajos').on('click', '.su-atajo', function () { suPonerPiezas($(this).data('n')); });
		$('#suPiezas').on('input', function () {
			this.value = this.value.replace(/\D/g, '').slice(0, 3);
			var u = suUnidad(su.promo), n = (Number(this.value) || 0) * u, max = suMaxEditor();   // v1.26.0: por roll se escriben rolls
			if (n > max) { this.value = max / u; n = max; toastr.info('Solo quedan ' + textoCantSushi(su.promo, max).replace(' pz', ' piezas')); }
			su.ed.g.piezas = n;
			pintarSushi();
		}).on('blur', function () { if (su && su.ed) { suPonerPiezas((Number(this.value) || 0) * suUnidad(su.promo)); } });

		$('#suNotaGrupo').on('input', function () { if (su && su.ed) su.ed.g.nota = this.value.slice(0, 100); })
			.on('keydown', function (e) { if (e.which === 13) { e.preventDefault(); $('#btnSushiGuardarGrupo').click(); } });
		$('#suEditor').on('click', '.su-chip', function () {
			var tipo = $(this).closest('.su-chips').data('tipo'), id = Number($(this).data('id')), g = su.ed.g;
			if (tipo === 'relleno') {
				var k = $.inArray(id, g.rellenos);
				if (k >= 0) g.rellenos.splice(k, 1);
				else if (su.promo.max_rellenos > 0 && g.rellenos.length >= su.promo.max_rellenos) { toastr.warning('Máximo ' + su.promo.max_rellenos + ' rellenos por grupo'); return; }
				else g.rellenos.push(id);   // no se puede repetir: se marca o se desmarca
			} else {
				g[tipo] = g[tipo] === id ? 0 : id;
			}
			pintarSushi();
		});

		$('#btnSushiNuevo').on('click', function () {
			if (su.promo.piezas - suConfiguradas(-1) <= 0) { toastr.info(suEsRol(su.promo) ? 'Ya están todos los rolls configurados' : 'Ya están todas las piezas configuradas'); return; }
			su.ed = { idx: null, g: suGrupoNuevo(suPiezasSugeridas()) };
			pintarSushi();
		});
		$('#suGrupos').on('click', '.su-editar', function () {
			var i = Number($(this).closest('.su-grupo').data('i')), g = su.grupos[i];
			su.ed = { idx: i, g: { piezas: g.piezas, base: g.base, envoltorio: g.envoltorio, rellenos: g.rellenos.slice(), nota: g.nota || '' } };
			pintarSushi();
		}).on('click', '.su-duplicar', function () {
			// Copia base, rellenos y envoltorio en un grupo nuevo (independiente) con las piezas que alcancen
			var g = su.grupos[Number($(this).closest('.su-grupo').data('i'))], falta = su.promo.piezas - suConfiguradas(-1);
			if (falta <= 0) { toastr.warning('No quedan piezas: baja las piezas de otro grupo para duplicar este'); return; }
			var paso = su.promo.paso, piezas = Math.min(g.piezas, falta);
			if (paso > 1) piezas = Math.floor(piezas / paso) * paso;
			if (piezas <= 0) { toastr.warning('No quedan piezas suficientes para duplicar este grupo'); return; }
			su.ed = { idx: null, g: { piezas: piezas, base: g.base, envoltorio: g.envoltorio, rellenos: g.rellenos.slice(), nota: g.nota || '' } };
			pintarSushi();
			toastr.info('Grupo duplicado: cambia lo que quieras y guárdalo');
		}).on('click', '.su-eliminar', function () {
			var i = Number($(this).closest('.su-grupo').data('i'));
			su.grupos.splice(i, 1);   // sus piezas vuelven a quedar pendientes
			if (su.ed && su.ed.idx !== null) su.ed = su.ed.idx === i ? null : { idx: su.ed.idx > i ? su.ed.idx - 1 : su.ed.idx, g: su.ed.g };
			pintarSushi();
		});
		$('#btnSushiCancelarGrupo').on('click', function () { su.ed = null; pintarSushi(); });
		$('#btnSushiGuardarGrupo').on('click', function () {
			var g = su.ed.g, promo = su.promo, max = suMaxEditor();
			var rolG = suEsRol(promo);
			if (g.piezas < 1) { toastr.warning(rolG ? 'Indica cuántos rolls' : 'Indica cuántas piezas lleva el grupo'); return; }
			if (g.piezas % promo.paso !== 0) { toastr.warning(rolG ? 'Los rolls van completos' : 'Las piezas van de a ' + promo.paso); return; }
			if (g.piezas > max) { toastr.warning('Solo quedan ' + textoCantSushi(promo, max).replace(' pz', ' piezas')); return; }
			if (promo.bases.length && !g.base) { toastr.warning('Elige la base'); return; }
			if (promo.rellenos.length && !g.rellenos.length) { toastr.warning('Elige al menos un relleno'); return; }
			if (promo.envoltorios.length && !g.envoltorio) { toastr.warning('Elige con qué se envuelve'); return; }
			var copia = { piezas: g.piezas, base: g.base, envoltorio: g.envoltorio, rellenos: g.rellenos.slice(), nota: $.trim(g.nota || '').slice(0, 100) };
			if (su.ed.idx === null) su.grupos.push(copia); else su.grupos[su.ed.idx] = copia;
			su.ed = null;
			// Si aún faltan piezas se abre el siguiente grupo, para seguir rápido
			if (promo.piezas - suConfiguradas(-1) > 0) su.ed = { idx: null, g: suGrupoNuevo(suPiezasSugeridas()) };
			pintarSushi();
		});
		$('#suExtras').on('click', '.su-ex-mas, .su-ex-menos', function () {
			var id = Number($(this).closest('.su-extra').data('id')), q = su.extras[id] || 0;
			q += $(this).hasClass('su-ex-mas') ? 1 : -1;
			if (q > 99) q = 99;
			if (q > 0) su.extras[id] = q; else delete su.extras[id];
			pintarSushi();
		});

		$('#btnConfirmarSushi').on('click', function () {
			if (su.ed) { toastr.warning('Guarda o cancela el grupo que estás armando'); return; }
			var pend = su.promo.piezas - suConfiguradas(-1);
			if (pend !== 0) {
				var cp = function (n) { return textoCantSushi(su.promo, n).replace(' pz', ' piezas'); };
				toastr.warning(pend > 0 ? 'Faltan ' + cp(pend) + ' por configurar' : 'Sobran ' + cp(-pend)); return;
			}
			var sel = {
				grupos: $.map(su.grupos, function (g) { return { piezas: g.piezas, base: g.base || 0, envoltorio: g.envoltorio || 0, rellenos: g.rellenos.slice(), nota: g.nota || '' }; }),
				extras: $.map(su.promo.extras, function (o) { return su.extras[o.id] ? { id: o.id, cantidad: su.extras[o.id] } : null; })
			};
			var nota = $.trim($('#txtNotaSushi').val());
			if (su.indice === null) agregar(su.id_producto, [], nota, undefined, {}, [], sel);
			else { carro[su.indice].sushi = sel; carro[su.indice].nota = nota; renderCarro(); }
			$('#modalSushi').modal('hide');
		});
		$('#modalSushi').on('hidden.bs.modal', function () { su = null; $('#buscarProducto').focus(); })
			.on('keydown', '#txtNotaSushi', function (e) { if (e.which === 13) $('#btnConfirmarSushi').click(); });

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

