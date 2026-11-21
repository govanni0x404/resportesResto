<?php /* v1.28.0 (PRUEBA): pantalla de cocina. Controlador Cocina, modelo Mcocina. */ ?>
<style>
	:root {
		--k-fondo: #f4f1ec; --k-tarjeta: #ffffff; --k-borde: #e5ddd3; --k-texto: #2A1D17; --k-suave: #7A6A5E;
		--k-verde: #15803d; --k-verde-fondo: #e7f6ec; --k-amarillo: #a16207; --k-amarillo-fondo: #fef3c7;
		--k-rojo: #b91c1c; --k-rojo-fondo: #fee2e2; --k-boton: #15803d;
	}
	/* Modo noche (pantalla de cocina con luz baja): botón "Oscuro" */
	body.k-oscuro { --k-fondo: #16110e; --k-tarjeta: #241b16; --k-borde: #3a2e27; --k-texto: #f5ede6; --k-suave: #b9a89b;
		--k-verde-fondo: #12351f; --k-amarillo-fondo: #3d2f0a; --k-rojo-fondo: #45161a; --k-verde: #4ade80; --k-amarillo: #facc15; --k-rojo: #f87171; }
	body.k-oscuro .content-wrapper, body.k-oscuro .content { background: var(--k-fondo); }
	.k-barra { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin: 0 0 14px; }
	.k-barra h1 { margin: 0 auto 0 0; font-size: 22px; font-weight: 800; color: var(--k-texto); display: flex; align-items: center; gap: 10px; }
	.k-barra h1 .k-cuenta { font-size: 14px; font-weight: 700; padding: 3px 10px; border-radius: 99px; background: var(--k-verde); color: #fff; }
	.k-barra h1 .k-prueba { font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 6px; background: var(--k-amarillo-fondo); color: var(--k-amarillo); }
	.k-barra select, .k-barra .btn { height: 40px; font-size: 14px; }
	.k-barra select.form-control { width: auto; min-width: 190px; border-radius: 8px; }
	.k-aviso { padding: 10px 14px; border-radius: 10px; background: var(--k-rojo-fondo); color: var(--k-rojo); margin-bottom: 12px; display: none; }
	.k-grilla { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 14px; align-items: start; }
	.k-vacio { grid-column: 1 / -1; text-align: center; padding: 60px 16px; color: var(--k-suave); font-size: 18px; }
	.k-vacio i { display: block; font-size: 44px; margin-bottom: 10px; opacity: .5; }
	.k-com { background: var(--k-tarjeta); border: 1px solid var(--k-borde); border-top: 6px solid var(--k-verde); border-radius: 12px;
		box-shadow: 0 4px 14px rgba(42, 29, 23, .07); display: flex; flex-direction: column; overflow: hidden; color: var(--k-texto); }
	.k-com.amarilla { border-top-color: var(--k-amarillo); }
	.k-com.roja { border-top-color: var(--k-rojo); }
	.k-com.nueva { animation: kNueva 1.2s ease 2; }
	@keyframes kNueva { 50% { box-shadow: 0 0 0 4px var(--k-verde); } }
	.k-cab { padding: 10px 14px 8px; display: flex; justify-content: space-between; gap: 8px; border-bottom: 1px dashed var(--k-borde); }
	.k-cab b { font-size: 18px; line-height: 1.2; display: block; }
	.k-cab small { color: var(--k-suave); font-size: 12px; }
	.k-tiempo { font-size: 20px; font-weight: 800; font-variant-numeric: tabular-nums; white-space: nowrap; color: var(--k-verde); }
	.k-com.amarilla .k-tiempo { color: var(--k-amarillo); }
	.k-com.roja .k-tiempo { color: var(--k-rojo); }
	.k-msg { margin: 8px 14px 0; padding: 6px 10px; border-radius: 8px; background: var(--k-amarillo-fondo); color: var(--k-amarillo); font-weight: 700; font-size: 13px; }
	.k-lineas { list-style: none; margin: 0; padding: 8px 14px 10px; }
	.k-lineas li { padding: 6px 0; border-bottom: 1px solid var(--k-borde); font-size: 16px; line-height: 1.3; cursor: pointer; }
	.k-lineas li:last-child { border-bottom: 0; }
	.k-lineas li .k-cant { display: inline-block; min-width: 34px; font-weight: 800; }
	.k-lineas li .k-nota { display: block; margin-left: 34px; font-size: 13px; color: var(--k-rojo); font-weight: 600; }
	.k-lineas li .k-dest { float: right; font-size: 11px; color: var(--k-suave); text-transform: uppercase; margin-left: 6px; }
	.k-lineas li.hecha { text-decoration: line-through; opacity: .45; }
	.k-pie { padding: 0 14px 14px; margin-top: auto; display: flex; gap: 8px; }
	.k-pie .btn { margin: 0; height: 48px; font-size: 16px; font-weight: 800; border-radius: 10px; }
	.k-pie .k-listo { flex: 1 1 60%; background: var(--k-boton); border-color: var(--k-boton); color: #fff; }
	.k-pie .k-tomar { flex: 1 1 40%; background: var(--k-tarjeta); border: 2px solid #1f5fa8; color: #1f5fa8; }
	.k-pie .k-tomar.mia { border-color: var(--k-suave); color: var(--k-suave); }
	/* v1.29.0: "Siendo preparado por" */
	.k-prep { margin: 8px 14px 0; padding: 6px 10px; border-radius: 8px; background: #e8f0fb; color: #1f5fa8; font-weight: 700; font-size: 13px; }
	body.k-oscuro .k-prep { background: #13294a; color: #93c5fd; }
	.k-com.tomada { border-top-color: #1f5fa8; }
	.k-deshacer { position: fixed; left: 50%; bottom: 24px; transform: translateX(-50%); z-index: 2000; display: none; align-items: center; gap: 14px;
		padding: 12px 16px; border-radius: 12px; background: #2A1D17; color: #fff; box-shadow: 0 10px 30px rgba(0,0,0,.3); max-width: calc(100% - 32px); }
	.k-deshacer .btn { font-weight: 700; }
	.k-barra .btn { margin: 0; }
	/* v1.29.0: cocina sin el panel lateral (botón "Ocultar menú"): toda la pantalla para las comandas */
	html body.k-sin-menu { --t-sidebar: 0px !important; }
	html body.k-sin-menu nav.navbar.bootsnav, html body.k-sin-menu .main-header .top-nav, html body.k-sin-menu .main-header,
	html body.k-sin-menu .main-footer { display: none !important; }
	html body.k-sin-menu .content-wrapper { margin-left: 0 !important; padding-left: 0 !important; width: 100% !important; }
	html body.k-sin-menu .content-wrapper > .content, html body.k-sin-menu .content { padding-top: 14px !important; }
	@media (max-width: 600px) { .k-barra h1 { width: 100%; } .k-barra select.form-control { flex: 1 1 100%; min-width: 0; width: 100%; }
		.k-barra .btn { flex: 1 1 0; width: auto; min-width: 0; padding: 6px 4px; font-size: 13px; } }
	@media (prefers-reduced-motion: reduce) { .k-com.nueva { animation: none; } }
</style>

<div class="k-barra">
	<h1><i class="fa fa-fire" aria-hidden="true"></i> Cocina <span class="k-cuenta" id="kCuenta">0</span> <span class="k-prueba">PRUEBA</span></h1>
	<label class="sr-only" for="kDestino">Destino</label>
	<select id="kDestino" class="form-control">
		<option value="0">Todos los destinos</option>
		<?php foreach ($destinos as $d) : ?>
		<option value="<?php echo (int)$d['id']; ?>"><?php echo html_escape($d['nombre']); ?></option>
		<?php endforeach; ?>
	</select>
	<button type="button" class="btn btn-default" id="kSonido" title="Sonido al llegar una comanda nueva"><i class="fa fa-volume-up"></i> Sonido</button>
	<button type="button" class="btn btn-default" id="kOscuro" title="Colores para cocina con poca luz"><i class="fa fa-moon-o"></i> Oscuro</button>
	<button type="button" class="btn btn-default" id="kMenu" title="Esconder o mostrar el menú de la izquierda"><i class="fa fa-columns"></i> <span>Ocultar menú</span></button>
	<button type="button" class="btn btn-default" id="kCompleta" title="Pantalla completa"><i class="fa fa-arrows-alt"></i> Pantalla completa</button>
</div>
<div class="k-aviso" id="kAviso" role="alert"></div>
<?php if ($disponible && !$activa) : ?>
<div class="k-aviso" style="display:block">La pantalla de cocina está desactivada. Gerencia la activa en <b>Configuraciones &gt; General &gt; Cocina y reservas</b>.</div>
<?php endif; ?>
<?php if (!$disponible) : ?>
<div class="k-aviso" style="display:block">Falta actualizar la base de esta empresa (migración de la pantalla de cocina). Recarga la página en un momento.</div>
<?php endif; ?>
<div class="k-grilla" id="kGrilla" aria-live="polite">
	<div class="k-vacio"><i class="fa fa-spinner fa-spin"></i> Cargando…</div>
</div>
<div class="k-deshacer" id="kDeshacer" role="status"><span id="kDeshacerTexto"></span> <button type="button" class="btn btn-warning btn-sm">Deshacer</button></div>

<script>
	// jQuery se carga al final de la página (menuFooter): se espera a que termine de cargar
	document.addEventListener('DOMContentLoaded', function () {
		var URL = '<?php echo base_url() ?>index.php/Cocina/';
		var YO = <?php echo (int)$yo; ?>;
		var ACTIVA = <?php echo ($disponible && $activa) ? 'true' : 'false'; ?>;
		var CLAVE = 'restoWeb.cocina.';
		var guardar = function (k, v) { try { localStorage.setItem(CLAVE + k, v); } catch (e) { /* sin almacenamiento */ } };
		var leer = function (k, d) { try { var v = localStorage.getItem(CLAVE + k); return v === null ? d : v; } catch (e) { return d; } };
		var vistas = {}, primera = true, pidiendo = false, sonido = leer('sonido', '1') === '1', ultimoListo = null, timerDeshacer = null;
		var tachadas = {};   // líneas tocadas una a una (solo en esta pantalla)

		$('#kDestino').val(leer('destino', '0'));
		if (!$('#kDestino').val()) $('#kDestino').val('0');
		if (leer('oscuro', '0') === '1') $('body').addClass('k-oscuro');
		function pintarSonido() { $('#kSonido').html(sonido ? '<i class="fa fa-volume-up"></i> Sonido' : '<i class="fa fa-volume-off"></i> Sin sonido'); }
		pintarSonido();

		function esc(t) { return $('<span>').text(t == null ? '' : String(t)).html(); }
		function tiempo(s) {
			var m = Math.floor(s / 60);
			return m < 60 ? m + ' min' : Math.floor(m / 60) + ' h ' + (m % 60) + ' min';
		}
		function cant(n) { return (Math.round(n * 100) / 100).toString().replace('.', ','); }
		function pitido() {
			if (!sonido) return;
			try {
				var c = new (window.AudioContext || window.webkitAudioContext)(), o = c.createOscillator(), g = c.createGain();
				o.type = 'sine'; o.frequency.value = 880; g.gain.value = 0.15;
				o.connect(g); g.connect(c.destination); o.start(); o.stop(c.currentTime + 0.25);
				setTimeout(function () { c.close(); }, 600);
			} catch (e) { /* sin audio */ }
		}

		function pintar(comandas) {
			var $g = $('#kGrilla').empty(), nuevas = 0, ahora = {};
			$('#kCuenta').text(comandas.length);
			document.title = (comandas.length ? '(' + comandas.length + ') ' : '') + 'Cocina';
			if (!comandas.length) {
				$g.append('<div class="k-vacio"><i class="fa fa-check-circle-o"></i>No hay comandas por preparar.</div>');
			}
			$.each(comandas, function (i, c) {
				ahora[c.clave] = true;
				var esNueva = !primera && !vistas[c.clave];
				if (esNueva) nuevas++;
				var color = c.segundos >= 1200 ? ' roja' : (c.segundos >= 600 ? ' amarilla' : '');
				var mia = c.tomado_por && c.tomado_id === YO;
				var $c = $('<article class="k-com' + color + (c.tomado_por ? ' tomada' : '') + (esNueva ? ' nueva' : '') + '">').data('c', c);
				$c.append('<div class="k-cab"><div><b>' + esc(c.titulo) + '</b><small>' + esc(c.detalle) + '</small></div>'
					+ '<div class="k-tiempo" title="Tiempo esperando">' + tiempo(c.segundos) + '</div></div>');
				if (c.tomado_por) $c.append('<div class="k-prep"><i class="fa fa-user"></i> Siendo preparado por ' + esc(c.tomado_por)
					+ ' · hace ' + tiempo(c.tomado_seg) + '</div>');
				if (c.mensaje) $c.append('<div class="k-msg"><i class="fa fa-exclamation-circle"></i> ' + esc(c.mensaje) + '</div>');
				var $ul = $('<ul class="k-lineas">');
				$.each(c.lineas, function (j, l) {
					var k = c.origen + ':' + l.id;
					$ul.append('<li data-k="' + k + '" class="' + (tachadas[k] ? 'hecha' : '') + '" title="Tocar para tachar">'
						+ (l.destino ? '<span class="k-dest">' + esc(l.destino) + '</span>' : '')
						+ '<span class="k-cant">' + cant(l.cantidad) + '</span>' + esc(l.nombre)
						+ (l.nota ? '<span class="k-nota">' + esc(l.nota) + '</span>' : '') + '</li>');
				});
				$c.append($ul);
				$c.append('<div class="k-pie">'
					+ (mia ? '<button type="button" class="btn k-tomar mia" data-soltar="1" title="Dejarla libre para otro cocinero"><i class="fa fa-hand-paper-o"></i> Soltar</button>'
						: '<button type="button" class="btn k-tomar" title="Avisar que la estoy preparando yo"><i class="fa fa-hand-rock-o"></i> Tomar</button>')
					+ '<button type="button" class="btn k-listo"><i class="fa fa-check"></i> Listo</button></div>');
				$g.append($c);
			});
			vistas = ahora;
			if (nuevas) pitido();
			primera = false;
		}

		function cargar() {
			if (pidiendo || !ACTIVA) return;
			pidiendo = true;
			$.ajax({ url: URL + 'pendientes', data: { destino: $('#kDestino').val() }, dataType: 'json', global: false })
				.done(function (r) { $('#kAviso').hide(); pintar(r.comandas || []); })
				.fail(function (x) {
					if (x.status === 401) return;   // la sesión venció: menuTop lleva al ingreso
					$('#kAviso').text((x.responseJSON && x.responseJSON.error) || 'Sin conexión con el servidor: se reintenta solo.').show();
				})
				.always(function () { pidiendo = false; });
		}

		function lineasDe(c) { return $.map(c.lineas, function (l) { return { origen: c.origen, id: l.id }; }); }

		$('#kGrilla').on('click', '.k-lineas li', function () {
			var k = $(this).data('k');
			tachadas[k] = !tachadas[k];
			$(this).toggleClass('hecha', !!tachadas[k]);
		}).on('click', '.k-listo', function () {
			var $c = $(this).closest('.k-com'), c = $c.data('c');
			$(this).prop('disabled', true);
			$.ajax({ url: URL + 'listo', type: 'POST', data: { lineas: lineasDe(c) }, dataType: 'json', global: false })
				.done(function () {
					$c.fadeOut(150, function () { $c.remove(); $('#kCuenta').text($('#kGrilla .k-com').length); });
					ultimoListo = c;
					$('#kDeshacerTexto').text(c.titulo + ' lista');
					$('#kDeshacer').css('display', 'flex');
					clearTimeout(timerDeshacer);
					timerDeshacer = setTimeout(function () { $('#kDeshacer').hide(); ultimoListo = null; }, 10000);
				})
				.fail(function (x) {
					$c.find('.k-listo').prop('disabled', false);
					$('#kAviso').text((x.responseJSON && x.responseJSON.error) || 'No se pudo marcar como lista').show();
				});
		});
		// v1.29.0: tomar / soltar la comanda
		$('#kGrilla').on('click', '.k-tomar', function () {
			var $b = $(this), c = $b.closest('.k-com').data('c'), soltar = $b.data('soltar') ? '1' : '0';
			if (soltar === '0' && c.tomado_por && c.tomado_id !== YO
				&& !confirm(c.tomado_por + ' ya la está preparando. ¿La tomas tú?')) return;
			$b.prop('disabled', true);
			$.ajax({ url: URL + 'tomar', type: 'POST', data: { lineas: lineasDe(c), soltar: soltar }, dataType: 'json', global: false })
				.always(function () { primera = true; vistas = {}; cargar(); })
				.fail(function (x) { $('#kAviso').text((x.responseJSON && x.responseJSON.error) || 'No se pudo tomar la comanda').show(); });
		});
		$('#kDeshacer .btn').on('click', function () {
			if (!ultimoListo) return;
			$.ajax({ url: URL + 'deshacer', type: 'POST', data: { lineas: lineasDe(ultimoListo) }, dataType: 'json', global: false })
				.always(function () { $('#kDeshacer').hide(); ultimoListo = null; cargar(); });
		});
		$('#kDestino').on('change', function () { guardar('destino', $(this).val()); primera = true; cargar(); });
		$('#kSonido').on('click', function () { sonido = !sonido; guardar('sonido', sonido ? '1' : '0'); pintarSonido(); if (sonido) pitido(); });
		$('#kOscuro').on('click', function () { $('body').toggleClass('k-oscuro'); guardar('oscuro', $('body').hasClass('k-oscuro') ? '1' : '0'); });
		// v1.29.0: esconder del todo el menú de la izquierda (queda recordado en esta pantalla)
		function sinMenu(si) {
			$('body').toggleClass('k-sin-menu', si);
			$('#kMenu span').text(si ? 'Mostrar menú' : 'Ocultar menú');
			$(window).trigger('resize');
		}
		sinMenu(leer('sinMenu', '0') === '1');
		$('#kMenu').on('click', function () { var si = !$('body').hasClass('k-sin-menu'); guardar('sinMenu', si ? '1' : '0'); sinMenu(si); });
		$('#kCompleta').on('click', function () {
			var d = document.documentElement;
			if (!document.fullscreenElement && d.requestFullscreen) { d.requestFullscreen().catch(function () {}); sinMenu(true); }
			else if (document.exitFullscreen) { document.exitFullscreen(); sinMenu(leer('sinMenu', '0') === '1'); }
		});

		if (!ACTIVA) { $('#kGrilla').empty(); return; }
		cargar();
		setInterval(cargar, 5000);
	});
</script>
