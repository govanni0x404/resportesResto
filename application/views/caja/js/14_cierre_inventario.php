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
					['Efectivo declarado por repartidores (fuera de caja)', r.efectivo_custodia_delivery || 0],
					['Pagos electrónicos pendientes de confirmar', r.delivery_pendiente_liquidacion || 0],
					['Pagos electrónicos confirmados (no efectivo)', r.delivery_liquidado_neto || 0],
					['Cargos y retenciones de plataformas (informativo)', r.delivery_cargos_plataforma || 0],
					['Comisiones Delivery pagadas (incluidas en egresos)', r.delivery_comisiones_pagadas || 0],
					['Diferencias de rendición pendientes (informativo)', r.delivery_diferencias_pendientes || 0],
					['Faltantes asumidos con ajuste autorizado (informativo)', r.delivery_diferencias_ajustadas || 0],
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
					// 19-11-2026: lo fiado lo pone el sistema (no hay comprobantes que contar)
					if (f.credito) { html += '<tr><td>' + esc(f.nombre) + ' (a crédito, lo pone el sistema)</td><td class="caja-num">' + fmt(f.monto + f.propina) + '</td></tr>'; return; }
					// Si Configuraciones no rellena las formas no efectivo, tampoco se muestra su monto: el cajero lo declara abajo
					if (!f.cortesia && catalogo && catalogo.config.llenar_no_efectivo === false) { html += '<tr><td>' + esc(f.nombre) + '</td><td class="caja-num text-muted">Se declara abajo</td></tr>'; return; }
					html += '<tr><td>' + esc(f.nombre) + (f.cortesia ? ' (no se rinde)' : '') + '</td><td class="caja-num">' + fmt(f.monto)
						+ (f.propina ? ' <small class="text-muted">+ ' + fmt(f.propina) + ' propina</small>' : '') + '</td></tr>';
				});
				$('#resumenCierre').html(html);

				var $d = $('#declaradoOtros').empty();
				$.each(r.por_forma_pago, function (i, f) {
					if (f.efectivo || f.cortesia || f.credito) return;
					// Configuraciones > Llenar forma de pago no efectivo: marcada, viene con lo del sistema; si no, el cajero lo escribe
					var llenar = !catalogo || catalogo.config.llenar_no_efectivo !== false;
					$d.append('<div class="form-group"><label>' + esc(f.nombre) + ' (vouchers / comprobantes' + (f.propina ? ', con propina' : '') + ')</label>'
						+ '<input type="number" step="1" class="form-control declarado" data-id="' + f.id_fpago + '" value="' + (llenar ? (f.monto + f.propina) : '') + '" placeholder="Escribe el total de los comprobantes"></div>');
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
		// 19-11-2026 (auditoría): ¿mandar los comprobantes (cierre, informe X, anulación, pago de crédito) por el agente de
		// este equipo? Entonces el servidor responde {local, destino, operaciones} y aquí se imprimen.
		function comprobanteLocal() { return impresionLocalActiva() && !!window.ImpresionLocal ? 1 : 0; }
		/** imp = respuesta de impresión del servidor. Si es local la imprime por el agente; devuelve true si se encargó. */
		function imprimirComprobanteLocal(imp, que) {
			if (!imp || !imp.local || !imp.destino) return false;
			ImpresionLocal.imprimir(imp.destino, imp.operaciones || []).then(function (res) {
				toastr.success(que + ' enviado a la impresora (' + res.impresora + ')', '', { timeOut: 2500 });
			}, function (e) {
				toastr.error(textoPlano(ImpresionLocal.mensaje(e)), que + ' sin imprimir', { timeOut: 12000, closeButton: true });
			});
			return true;
		}
		// Cierre / informe X a la impresora del cierre (Configuraciones > Impresora para cierre de caja); si no se puede, en pantalla
		function imprimirCierre(idCaja) {
			$.ajax({ url: base + 'imprimirCierre', type: 'POST', dataType: 'json', data: { id_caja: idCaja, local: comprobanteLocal() } })
				.done(function (r) {
					if (imprimirComprobanteLocal(r, 'Cierre')) return;
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
			// 19-11-2026: con la impresión en este equipo, la gaveta la abre el agente local (el servidor no llega a la impresora)
			var local = impresionLocalActiva() && !!window.ImpresionLocal;
			enviarConAutorizacion(function (aut) {
				return $.ajax({ url: base + 'abrirGaveta', type: 'POST', dataType: 'json', data: { autorizacion: aut, local: local ? 1 : 0 } });
			}, function (r) {
				if (r && r.local && r.destino) {
					ImpresionLocal.imprimir(r.destino, [{ accion: 'abrir_gaveta', valor: '' }], { tiempo: 15000 }).then(function () {
						toastr.success('Gaveta abierta', '', { timeOut: 2000 });
					}, function (e) {
						toastr.error(textoPlano(ImpresionLocal.mensaje(e)), 'No se pudo abrir la gaveta', { timeOut: 12000, closeButton: true });
					}).then(function () { $b.prop('disabled', false); });
					return;
				}
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
			// Cierre ciego: cada forma de pago se declara (0 si no hubo); en blanco no se da por cero.
			// 19-11-2026 (auditoría): se acepta un total negativo (ej. una devolución con tarjeta mayor que lo vendido con ella)
			var $sinDeclarar = $('.declarado').filter(function () { return $.trim($(this).val()) === '' || isNaN(Number($(this).val())); }).first();
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

