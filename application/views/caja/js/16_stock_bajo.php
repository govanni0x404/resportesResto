		// ---------------- v1.27.0: aviso de stock bajo ----------------
		// Franja roja arriba (#stockBajo) con los insumos que llegaron a su stock mínimo (Insumos > stock mínimo).
		// Solo avisa: no tapa nada, no impide vender y se puede cerrar con la X (vuelve si cambia la lista).
		// Para no tocar la impresión: se consulta sola (global: false, sin aviso de carga), unos segundos DESPUÉS de cada
		// venta o envío a la mesa, al abrir la Caja y cada 3 minutos. Caja::stockBajo suelta la sesión al tiro.
		(function () {
			var $b = $('#stockBajo'), cerrada = '', ultima = '', timer = null, pidiendo = false;
			function cantidad(n, u) {
				var t = (Math.round(n * 100) / 100).toString().replace('.', ',');
				return t + (u ? ' ' + u.toLowerCase() : '');
			}
			function pintar(lista) {
				var firma = $.map(lista, function (i) { return i.id_insumo + ':' + i.saldo; }).join('|');
				ultima = firma;
				if (!lista.length || firma === cerrada) { $b.hide().empty(); return; }
				var $t = $('<div class="sb-texto">').append($('<b>').text(lista.length === 1 ? 'Queda poco: ' : 'Quedan pocos: '));
				$.each(lista, function (i, x) {
					var agotado = x.saldo <= 0;
					$t.append($('<span class="sb-item">').toggleClass('agotado', agotado)
						.text(x.nombre + ' (' + (agotado ? 'sin stock' : cantidad(x.saldo, x.unidad)) + ', mínimo ' + cantidad(x.minimo, x.unidad) + ')'));
				});
				$b.empty().append('<i class="fa fa-exclamation-triangle sb-ico" aria-hidden="true"></i>', $t,
					$('<button type="button" class="sb-cerrar" title="Ocultar este aviso" aria-label="Ocultar el aviso de stock bajo">&times;</button>')).show();
			}
			function cargar() {
				if (pidiendo || document.hidden) return;
				pidiendo = true;
				$.ajax({ url: base + 'stockBajo', dataType: 'json', global: false })
					.done(function (r) { pintar((r && r.insumos) || []); })
					.always(function () { pidiendo = false; });
			}
			function luego(ms) {
				clearTimeout(timer);
				timer = setTimeout(cargar, ms);
			}
			$b.on('click', '.sb-cerrar', function () { cerrada = ultima; $b.hide(); });
			// Después de vender o enviar a la mesa (el stock bajó): espera 5 s para que la impresión termine primero
			$(document).ajaxComplete(function (e, xhr, o) {
				if (o && /\/(vender|enviarMesa|deliveryGuardar|deliveryCobrar|anular)(\?|$)/.test(o.url || '')) luego(5000);
			});
			document.addEventListener('visibilitychange', function () { if (!document.hidden) luego(1500); });
			setInterval(cargar, 180000);
			luego(3000);
		})();
