/*
 * 17-11-2026: boletas, precuentas y comandas con el diseño de la página (views/caja/ticket.php, precuenta.php y
 * comanda.php) impresas como IMAGEN por el agente de impresión (impresionWeb.exe), directo y sin el cuadro de
 * impresión del navegador.
 *
 * El agente solo acepta imágenes por dirección web: con la orden img_url las descarga él mismo. Se probó con el botón
 * "Probar imagen" de Destinos de impresión el 17-11-2026; con data URL o base64 no imprime nada.
 *
 * Pasos:
 * 1. La página se abre en un marco oculto (?imagen=1, sin window.print).
 * 2. Cada hoja (.ticket) se dibuja con html2canvas al ancho del papel (576 puntos en 80 mm, 384 en 58 mm) y se pasa a
 *    blanco y negro puro (la térmica no tiene grises).
 * 3. Se sube al servidor (Caja/subirImagenImpresion), que la guarda 10 minutos.
 * 4. Al agente se le manda img_url con ImagenImpresion/ver/<clave>.png, más avance y corte.
 *
 * Uso: ImagenDocumento.imprimir(urlPagina, destino, { subir: urlSubida, copias: 1, hojas: 'todas' })
 *   devuelve la promesa de ImpresionLocal.imprimir ({impresora}). Si falla, quien llama decide qué hacer
 *   (js.php imprime con el formato del agente, para que no quede sin imprimir).
 */
(function (global) {
	'use strict';

	var UMBRAL = 170;   // 0..255: más claro que esto queda blanco

	function cargarMarco(url) {
		return new Promise(function (ok, mal) {
			var f = document.createElement('iframe');
			f.setAttribute('aria-hidden', 'true');
			// Fuera de la pantalla pero con tamaño real: html2canvas necesita que la página esté dibujada
			f.style.cssText = 'position:fixed;left:-10000px;top:0;width:420px;height:2000px;border:0;visibility:hidden;';
			var listo = false;
			var t = setTimeout(function () { if (!listo) { listo = true; f.remove(); mal(new Error('La página del documento no cargó a tiempo')); } }, 20000);
			// Primero se agrega y después se le da la dirección: Chrome avisa "cargado" también por la página en blanco
			// inicial del marco, y ese aviso se ignora
			document.body.appendChild(f);
			f.onload = function () {
				if (listo) return;
				var href = '';
				try { href = String(f.contentWindow.location.href); } catch (e) { href = ''; }
				if (href === '' || href === 'about:blank') return;
				listo = true;
				clearTimeout(t);
				ok(f);
			};
			f.src = url + (url.indexOf('?') < 0 ? '?' : '&') + 'imagen=1';
		});
	}

	/** Espera las imágenes de la página (el timbre PDF417 es una imagen). */
	function esperarImagenes(doc) {
		var imgs = Array.prototype.slice.call(doc.images || []);
		return Promise.all(imgs.map(function (im) {
			if (im.complete) return null;
			return new Promise(function (ok) { im.onload = im.onerror = function () { ok(); }; });
		}));
	}

	/** Estilos de impresión aplicados en pantalla: fondo blanco, sin botones y letra gruesa (como al imprimir). */
	function estilosImpresion(doc) {
		var s = doc.createElement('style');
		s.textContent = 'body{background:#fff!important;margin:0!important}.acciones{display:none!important}'
			+ '.ticket{margin:0!important;box-shadow:none!important}'
			+ (doc.querySelector('.destino') ? '' : '.ticket{font-weight:bold}');   // boleta y precuenta en negrita, como en el papel
		doc.head.appendChild(s);
	}

	function blancoYNegro(canvas) {
		var ctx = canvas.getContext('2d');
		var img = ctx.getImageData(0, 0, canvas.width, canvas.height), d = img.data;
		for (var i = 0; i < d.length; i += 4) {
			// Lo transparente cuenta como blanco
			var lum = d[i + 3] < 128 ? 255 : (0.299 * d[i] + 0.587 * d[i + 1] + 0.114 * d[i + 2]);
			var v = lum < UMBRAL ? 0 : 255;
			d[i] = d[i + 1] = d[i + 2] = v;
			d[i + 3] = 255;
		}
		ctx.putImageData(img, 0, 0);
		return canvas;
	}

	/** html2canvas redondea (ej. 577 en vez de 576): se deja exactamente del ancho del papel */
	function alAncho(canvas, ancho) {
		if (canvas.width === ancho) return canvas;
		var c = document.createElement('canvas');
		c.width = ancho;
		c.height = Math.max(1, Math.round(canvas.height * ancho / canvas.width));
		var ctx = c.getContext('2d');
		ctx.fillStyle = '#fff';
		ctx.fillRect(0, 0, c.width, c.height);
		ctx.drawImage(canvas, 0, 0, c.width, c.height);
		return c;
	}

	function aBlob(canvas) {
		return new Promise(function (ok, mal) {
			canvas.toBlob(function (b) { b ? ok(b) : mal(new Error('No se pudo crear la imagen')); }, 'image/png');
		});
	}

	/** Sube la imagen y devuelve la dirección completa que descargará el agente. */
	function subir(urlSubida, blob) {
		var fd = new FormData();
		fd.append('imagen', blob, 'documento.png');
		// fetch: menuTop.php le agrega la cabecera X-CSRF-Token
		return fetch(urlSubida, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) {
			return r.json().catch(function () { return {}; }).then(function (j) {
				if (!r.ok || !j.url) throw new Error((j && j.error) || ('No se pudo subir la imagen (' + r.status + ')'));
				return new URL(j.url, location.href).href;
			});
		});
	}

	/** Dibuja las hojas de la página: [canvas, ...] */
	async function dibujar(urlPagina) {
		if (!global.html2canvas) throw new Error('No se cargó html2canvas: recarga la página');
		var f = await cargarMarco(urlPagina);
		try {
			var doc = f.contentDocument;
			if (!doc || !doc.body) throw new Error('No se pudo leer la página del documento');
			estilosImpresion(doc);
			await esperarImagenes(doc);
			if (doc.fonts && doc.fonts.ready) await doc.fonts.ready;
			var hojas = Array.prototype.slice.call(doc.querySelectorAll('.ticket'));
			if (!hojas.length) throw new Error('El documento no tiene nada que imprimir');
			var res = [];
			for (var i = 0; i < hojas.length; i++) {
				var el = hojas[i];
				var mm = el.offsetWidth / 96 * 25.4;
				var ancho = mm < 66 ? 384 : 576;   // 58 mm o 80 mm
				var c = await global.html2canvas(el, {
					scale: ancho / el.offsetWidth, backgroundColor: '#ffffff', logging: false,
					windowWidth: doc.documentElement.scrollWidth, windowHeight: doc.documentElement.scrollHeight
				});
				res.push(blancoYNegro(alAncho(c, ancho)));
			}
			return res;
		} finally {
			f.remove();
		}
	}

	/**
	 * Imprime la página como imagen en el destino (impresora) por el agente.
	 * opciones: subir (url de Caja/subirImagenImpresion), copias (veces que sale todo, por defecto 1).
	 */
	async function imprimir(urlPagina, destino, opciones) {
		opciones = opciones || {};
		if (!global.ImpresionLocal || !global.PrinterJob) throw new Error('No se cargó el cliente de impresión local: recarga la Caja');
		var hojas = await dibujar(urlPagina);
		var urls = [];
		for (var i = 0; i < hojas.length; i++) urls.push(await subir(opciones.subir, await aBlob(hojas[i])));
		var job = new global.PrinterJob({});
		var copias = Math.max(1, Math.min(5, opciones.copias || 1));
		for (var k = 0; k < copias; k++) {
			urls.forEach(function (u) { job.img_url(u).feed(3).cut(); });
		}
		// Imágenes largas: el agente las descarga y las convierte, le damos más tiempo que a un texto
		return global.ImpresionLocal.imprimir(destino, job.operaciones, { tiempo: 60000 });
	}

	global.ImagenDocumento = { imprimir: imprimir, dibujar: dibujar };
})(window);
