/*
 * Impresión directa en el equipo (12-11-2026), como mipdv_web: el navegador le manda el trabajo al agente de impresión
 * local (LocalPrintClient.js + printerJob.js). Lo usan Destino de impresión (Probar impresión) y la Caja (documentos).
 *
 *   ImpresionLocal.imprimir(destino, operaciones, opciones) -> Promise({ impresora })
 *     destino = { nombre, ip, puerto, ruta_windows } (un destino de impresión)
 *     operaciones = [{ accion, valor }] (las de PrinterJob)
 *   Los errores traen .tipo: 'conexion' (no hay agente), 'clave', 'impresora' (el equipo no la tiene), 'agente'
 *   (el agente respondió con error) o 'tiempo' (no respondió a tiempo: puede haber impreso igual).
 *
 * Impresora: la de Windows de "Ruta para Windows" (//equipo/POS80 => POS80) si este equipo la tiene; si no, la IP y el
 * puerto (impresora de red). Mismo orden que el agente de la cola (assets/agente-impresion/agente.ps1).
 */
(function (global) {
	'use strict';

	function error(tipo, mensaje) { var e = new Error(mensaje); e.tipo = tipo; return e; }
	function esc(t) {
		return String(t == null ? '' : t).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
	}
	function urlAgente() { return global.LocalPrintClient ? global.LocalPrintClient.getBaseUrl() : 'http://localhost:5050'; }

	/** "//equipo/POS80" => "POS80" (como Mcola_impresion::nombreImpresora) */
	function nombreImpresora(ruta) {
		ruta = String(ruta || '').trim();
		if (ruta.length <= 1) return '';
		var partes = ruta.replace(/[\\\/]+$/, '').split(/[\\\/]+/);
		return partes[partes.length - 1] || '';
	}
	function esIp(t) {
		t = String(t || '').trim();
		return /^(\d{1,3}\.){3}\d{1,3}$/.test(t) && t.split('.').every(function (n) { return Number(n) <= 255; });
	}
	/** Ancho del papel por el nombre de la impresora (como mipdv_web: 58 si el nombre lo dice, si no 80) */
	function anchoMm(nombre) {
		var s = String(nombre || '').toLowerCase();
		return /(^|[^0-9])58([^0-9]|$)/.test(s) || s.indexOf('58mm') >= 0 ? 58 : 80;
	}

	/** POST al agente con tiempo límite. Respuesta en JSON (o texto). */
	function llamar(ruta, cuerpo, ms) {
		if (!global.LocalPrintClient) return Promise.reject(error('conexion', 'No se cargó el cliente de impresión local'));
		var ctrl = typeof AbortController === 'function' ? new AbortController() : null;
		var reloj = ctrl ? setTimeout(function () { ctrl.abort(); }, ms) : null;
		return fetch(global.LocalPrintClient.buildUrl(ruta), {
			method: 'POST',
			headers: global.LocalPrintClient.buildHeaders(),
			body: JSON.stringify(cuerpo || {}),
			signal: ctrl ? ctrl.signal : undefined
		}).then(function (r) {
			return r.text().then(function (texto) {
				var datos = texto;
				try { datos = JSON.parse(texto); } catch (e) {}
				if (r.status === 401 || r.status === 403) throw error('clave', 'El agente rechazó la clave');
				if (!r.ok) {
					var e = error('agente', (datos && (datos.error || datos.mensaje || datos.message)) || ('el agente respondió ' + r.status));
					e.status = r.status;
					throw e;
				}
				return datos;
			});
		}, function (e) {
			if (e && e.name === 'AbortError') throw error('tiempo', 'El agente de impresión no respondió a tiempo');
			throw error('conexion', 'No hay conexión con el agente de impresión de este equipo');
		}).finally(function () { if (reloj) clearTimeout(reloj); });
	}

	/**
	 * Impresoras de este equipo según el agente (null = el agente no tiene la lista).
	 * v1.25.3: pedirle la lista al agente puede tardar varios segundos (Windows revisa cada impresora). Antes se recordaba
	 * solo 30 segundos: lo que se imprimía después de una pausa (anular, reenviar a cocina) esperaba la lista y tardaba
	 * ~7 s en vez de ~2 s. Ahora se recuerda y se renueva sola en segundo plano cada 5 minutos, cuando no se está
	 * imprimiendo (el agente atiende de a una petición). Al imprimir se usa la recordada; si en ella no está la impresora,
	 * se vuelve a pedir una vez (forzar = true). ultimaLista = cuánto tardó y si vino recordada (para ?medir=1).
	 */
	var listaCache = null, imprimiendo = 0, ultimaLista = { ms: 0, recordada: false };
	var RENOVAR_LISTA = 5 * 60 * 1000;
	function pedirLista() {
		var t0 = Date.now();
		var p = llamar('impresoras', {}, 8000).then(function (r) {
			ultimaLista = { ms: Date.now() - t0, recordada: false };
			return r && Array.isArray(r.impresoras) ? r.impresoras : (Array.isArray(r) ? r : null);
		}, function (e) {
			if (e.tipo === 'agente' && e.status === 404) return null;   // agente sin lista de impresoras
			throw e;
		});
		listaCache = { t: Date.now(), promesa: p };
		p.catch(function () { if (listaCache && listaCache.promesa === p) listaCache = null; });   // un error no se recuerda
		return p;
	}
	function listaImpresoras(forzar) {
		if (!forzar && listaCache) {
			ultimaLista = { ms: 0, recordada: true };
			return listaCache.promesa;
		}
		return pedirLista();
	}
	// Renovación en segundo plano (solo si ya se pidió alguna vez y no hay nada imprimiéndose)
	setInterval(function () {
		if (!listaCache || imprimiendo > 0 || Date.now() - listaCache.t < RENOVAR_LISTA) return;
		var anterior = listaCache;
		pedirLista().catch(function () { listaCache = anterior; });   // si falla, se sigue con la que había
	}, 60000);
	/** Pide la lista ahora (al abrir la Caja), para que la primera impresión no la espere. */
	function precargar() {
		if (!listaCache) pedirLista().catch(function () {});
	}

	/** Conexión de PrinterJob para el destino: impresora de Windows de este equipo o IP y puerto */
	function conexion(destino) {
		var ruta = String(destino.ruta_windows || '').trim(), ip = String(destino.ip || '').trim(), puerto = parseInt(destino.puerto, 10) || 0;
		var buscado = nombreImpresora(ruta).toLowerCase(), completa = ruta.replace(/\//g, '\\').toLowerCase();
		var buscar = function (lista) {
			return (lista || []).filter(function (i) {
				var n = String((i && i.nombre) || '').toLowerCase();
				return n !== '' && (n === buscado || n === completa || nombreImpresora(n) === buscado);
			})[0];
		};
		return listaImpresoras().then(function (lista) {
			// v1.25.3: la lista recordada no tiene la impresora (se conectó después): se vuelve a pedir una vez
			if (lista && buscado && !buscar(lista) && ultimaLista.recordada) return listaImpresoras(true);
			return lista;
		}).then(function (lista) {
			if (lista && buscado) {
				var hallada = buscar(lista);
				if (hallada) {
					return { job: { tipo_conexion: hallada.tipo_conexion || 'usb', dispositivo: hallada.nombre, ip: hallada.ip, puerto: hallada.puerto },
						impresora: hallada.nombre, nombre: hallada.nombre };
				}
			}
			if (esIp(ip) && puerto > 0) return { job: { tipo_conexion: 'ethernet', ip: ip, puerto: puerto }, impresora: ip + ':' + puerto, nombre: ruta };
			if (!lista && buscado) return { job: { tipo_conexion: 'usb', dispositivo: nombreImpresora(ruta) }, impresora: nombreImpresora(ruta), nombre: ruta };
			var nombres = (lista || []).map(function (i) { return i && i.nombre ? i.nombre : null; }).filter(Boolean);
			var e = error('impresora', (buscado ? 'Este equipo no tiene la impresora ' + nombreImpresora(ruta) : 'El destino "' + (destino.nombre || '') + '" no tiene ruta para Windows')
				+ (esIp(ip) && puerto > 0 ? '' : ' y no tiene IP y puerto de una impresora de red'));
			e.impresoras = nombres;
			throw e;
		});
	}

	/** Envía las operaciones al destino. opciones.raster_max_width (si no, según el nombre de la impresora). */
	/**
	 * v1.23.2: destino que NO imprime (casilla "sin impresión" de Destinos de impresión, o nombre / ruta de Windows que
	 * empiezan con "SIN IMPRES"): no se llama al agente y no es un error.
	 */
	function sinImpresion(destino) {
		if (!destino) return false;
		var es = function (t) { return /^\s*sin\s*impres/i.test(String(t || '')); };
		return destino.sin_impresion === true || es(destino.ruta_windows) || es(destino.nombre);
	}

	function imprimir(destino, operaciones, opciones) {
		opciones = opciones || {};
		if (sinImpresion(destino)) return Promise.resolve({ impresora: (destino.nombre || 'destino') + ' (sin impresión)', sin_impresion: true });
		imprimiendo++;
		var fin = function () { imprimiendo = Math.max(0, imprimiendo - 1); };
		return conexion(destino).then(function (con) {
			var raster = opciones.raster_max_width || (String(con.nombre).toLowerCase().indexOf('pos80') >= 0 || anchoMm(con.nombre) === 58 ? 384 : 576);
			var trabajo = Object.assign({ raster_max_width: raster, operaciones: operaciones }, con.job);
			return llamar('imprimir', trabajo, opciones.tiempo || 30000).then(function (r) {
				if (r === false || (r && typeof r === 'object' && (r.ok === false || r.error || r.estado === 'error' || r.status === 'error'))) {
					throw error('agente', (r && (r.error || r.mensaje || r.message)) || 'el agente no confirmó la impresión');
				}
				return { impresora: con.impresora, nombre: con.nombre };
			});
		}).then(function (r) { fin(); return r; }, function (e) { fin(); throw e; });
	}

	/** Mensaje para la persona (HTML escapado) */
	function mensaje(e) {
		var t = e && e.tipo;
		if (t === 'clave') return 'El agente de impresión de este equipo rechazó la clave. Revisa RESTO_IMPRESION_LOCAL_CLAVE en la configuración del servidor.';
		if (t === 'conexion') return 'No hay conexión con el agente de impresión de este equipo (' + esc(urlAgente()) + '). Verifica que esté instalado y abierto, y que el navegador permita el acceso a la red local.';
		if (t === 'impresora') return esc(e.message) + '.' + (e.impresoras && e.impresoras.length ? ' Impresoras de este equipo: ' + esc(e.impresoras.join(', ')) : '');
		if (t === 'tiempo') return 'El agente de impresión no respondió a tiempo. Revisa la impresora antes de volver a imprimir.';
		return 'No se pudo imprimir: ' + esc(e && e.message ? e.message : e);
	}

	global.ImpresionLocal = { imprimir: imprimir, sinImpresion: sinImpresion, conexion: conexion, mensaje: mensaje, nombreImpresora: nombreImpresora, esIp: esIp, anchoMm: anchoMm, url: urlAgente,
		precargar: precargar, ultimaLista: function () { return ultimaLista; } };
})(window);
