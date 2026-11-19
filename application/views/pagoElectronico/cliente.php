<script>
	/**
	 * Pago electrónico (19-11-2026): llamadas del navegador al programa ApiPagoElectronico del equipo de la caja
	 * (proyecto Integracion_Maquinas_Pos, http://localhost:5005). Lo usan la Caja (cobrar) y Mantención > Pago
	 * electrónico (probar la conexión). `m` = una máquina de Mpagoelectronico::paraCaja (con el usuario y la clave de la
	 * API de Configuraciones).
	 *
	 * Rutas del programa: GET /status (sin clave), POST /online, POST /pago (la petición queda abierta hasta que la
	 * máquina responde), GET /pago/estado/<tx>, POST /pago/cancelar/<tx> y POST /pago/resolver/<tx>, con Basic Auth.
	 * El programa compara id_sucursal y terminal_id con los de su panel y, si no son iguales, responde cuál no calza.
	 */
	window.PagoPos = (function () {
		function auth(m) {
			var txt = (m.usuario_api || '') + ':' + (m.clave_api || '');
			// btoa solo acepta latin1: la clave puede tener tildes o ñ
			return 'Basic ' + btoa(unescape(encodeURIComponent(txt)));
		}
		function url(m, ruta) {
			return String(m.url_servicio || 'http://localhost:5005').replace(/\/+$/, '') + ruta;
		}

		/** fetch al programa. Nunca falla: responde {red: true} si no se pudo conectar, o {status, json}. */
		function llamar(m, metodo, ruta, cuerpo, opc) {
			opc = opc || {};
			var ctrl = window.AbortController ? new AbortController() : null;
			var tope = opc.timeoutMs ? setTimeout(function () { if (ctrl) ctrl.abort(); }, opc.timeoutMs) : null;
			if (opc.signal && ctrl) {
				if (opc.signal.aborted) ctrl.abort();
				else opc.signal.addEventListener('abort', function () { ctrl.abort(); });
			}
			var h = {};
			if (!opc.sinClave) h.Authorization = auth(m);
			if (cuerpo !== undefined) h['Content-Type'] = 'application/json';
			return fetch(url(m, ruta), {
				method: metodo, headers: h, body: cuerpo !== undefined ? JSON.stringify(cuerpo) : undefined,
				signal: ctrl ? ctrl.signal : undefined, cache: 'no-store', mode: 'cors', credentials: 'omit'
			}).then(function (r) {
				clearTimeout(tope);
				return r.text().then(function (t) {
					var j = null;
					try { j = t ? JSON.parse(t) : null; } catch (e) {}
					return { status: r.status, json: j || {} };
				});
			}, function (e) {
				clearTimeout(tope);
				return { red: true, abortado: !!(e && e.name === 'AbortError'), error: e };
			});
		}

		/** Lo que va en la petición /pago: los datos configurados en Mantención > Pago electrónico. */
		function cuerpoPago(m, monto, txId, credito) {
			var c = {
				type: m.tipo, id_sucursal: String(m.id_sucursal_pos), nombre_caja: String(m.nombre_caja_pos),
				amount: Math.round(monto), timeout: m.timeout_seg || 120, tx_id: txId
			};
			if (m.tipo === 'MERCADOPAGO') c.mp_terminal_id = String(m.terminal_id);
			else c.terminal_id = String(m.terminal_id);
			if (m.tipo === 'HAULMER') c.payment_method = credito ? 'credito' : 'debito';
			// API key: en Mercado Pago es el access token y en TUU la API key; el programa la recibe en access_token
			if (m.api_key && (m.tipo === 'MERCADOPAGO' || m.tipo === 'HAULMER')) c.access_token = m.api_key;
			return c;
		}

		// Código de autorización / comprobante, venga de la máquina que venga (Transbank, Getnet, Mercado Pago, TUU)
		var CLAVES_CODIGO = /^(authorization_?code|authorizationcode|auth_?code|codigo_?autorizacion|authorization)$/i;
		function buscarCodigo(o, prof) {
			if (!o || typeof o !== 'object' || prof > 5) return '';
			for (var k in o) {
				if (!Object.prototype.hasOwnProperty.call(o, k)) continue;
				if (CLAVES_CODIGO.test(k) && o[k] !== null && o[k] !== '' && typeof o[k] !== 'object') return String(o[k]);
			}
			for (var k2 in o) {
				if (Object.prototype.hasOwnProperty.call(o, k2) && o[k2] && typeof o[k2] === 'object') {
					var r = buscarCodigo(o[k2], prof + 1);
					if (r) return r;
				}
			}
			return '';
		}
		function codigo(j) {
			var c = buscarCodigo(j && j.result, 0) || buscarCodigo(j && j.data && j.data.response, 0) || buscarCodigo(j, 0);
			if (!c && j && j.data) {
				var p = j.data.response && j.data.response.transactions && j.data.response.transactions.payments && j.data.response.transactions.payments[0];
				c = (p && (p.reference_id || p.id)) || j.data.order_id || '';
			}
			return String(c || '').slice(0, 80);
		}
		function textoRechazo(j) {
			var res = (j && j.result) || {}, resp = res.response || (j && j.data && j.data.response) || {};
			return res.message || resp.response_message || resp.ResponseMessage || resp.status_detail
				|| (j && j.data && j.data.status_detail) || (j && j.message) || '';
		}

		/**
		 * Respuesta del programa => {resultado, mensaje, codigo}. resultado:
		 *  - 'aprobado': la máquina aprobó el pago.
		 *  - 'rechazado': la máquina lo rechazó o se canceló (no se cobró).
		 *  - 'pendiente': sin confirmar (pudo cobrarse): no se reintenta solo; queda arriba en la Caja.
		 *  - 'error': no llegó a la máquina (datos mal configurados, máquina ocupada, apagada...). mensaje = el motivo.
		 *  - 'red': no se pudo hablar con el programa.
		 */
		function interpretar(r) {
			if (r.red) return { resultado: 'red', mensaje: '' };
			var j = r.json || {};
			var msg = j.message || j.error || '';
			if (r.status === 401) {
				return { resultado: 'error', mensaje: 'El usuario o la clave de la API no coinciden con los del programa de pago (Configuraciones > General > Pago electrónico).' };
			}
			if (r.status === 403) return { resultado: 'error', mensaje: 'Dato mal configurado: ' + (msg || 'el programa rechazó la petición') };
			if (r.status === 409 && j.status === 'unresolved_previous_transaction') {
				return { resultado: 'error', bloqueada: true, mensaje: (msg || 'Hay una venta anterior sin confirmar en la máquina.')
					+ (j.pending_transaction_id ? ' (cobro ' + j.pending_transaction_id + ')' : '') };
			}
			if (r.status === 429) return { resultado: 'error', mensaje: 'La máquina está ocupada con otro cobro. Espera a que termine.' };
			if (r.status >= 400) return { resultado: 'error', mensaje: msg || ('El programa de pago respondió con error ' + r.status) };
			var estado = String(j.estado || '').toUpperCase();
			if (estado === 'APROBADO') return { resultado: 'aprobado', mensaje: '', codigo: codigo(j) };
			if (estado === 'RECHAZADO' || estado === 'ERROR' || estado === 'CANCELADO' || estado === 'TIMEOUT') {
				return { resultado: 'rechazado', mensaje: textoRechazo(j) || (estado === 'CANCELADO' ? 'Se canceló el cobro' : estado === 'TIMEOUT' ? 'Se acabó el tiempo de espera' : 'La máquina rechazó el pago') };
			}
			return { resultado: 'pendiente', mensaje: msg || 'La máquina no confirmó el pago.' };
		}

		/**
		 * Compara lo configurado en la web con lo que dice el programa en GET /status (su ID_SUCURSAL, NOMBRE_CAJA y
		 * terminales). Devuelve los datos que no son iguales, como texto (vacío = todo calza). Mercado Pago y TUU no
		 * informan su terminal en /status: eso lo valida el programa al cobrar.
		 */
		function comparar(m, s) {
			var dif = [];
			if (!s) return dif;
			var igual = function (a, b) { return String(a == null ? '' : a).trim().toUpperCase() === String(b == null ? '' : b).trim().toUpperCase(); };
			if (s.id_sucursal != null && !igual(m.id_sucursal_pos, s.id_sucursal)) {
				dif.push('ID de sucursal: en la web es "' + m.id_sucursal_pos + '" y en el programa es "' + s.id_sucursal + '".');
			}
			if (m.tipo === 'GETNET' || m.tipo === 'TRANSBANK') {
				if (s.nombre_caja != null && !igual(m.nombre_caja_pos, s.nombre_caja)) {
					dif.push('Nombre de caja: en la web es "' + m.nombre_caja_pos + '" y en el programa es "' + s.nombre_caja + '".');
				}
				var t = m.tipo === 'GETNET' ? s.terminal_getnet : s.terminal_transbank;
				if (t != null && !igual(m.terminal_id, t)) {
					dif.push('Terminal ' + (m.tipo === 'GETNET' ? 'Getnet' : 'Transbank') + ': en la web es "' + m.terminal_id + '" y en el programa es "' + (t || '(vacío)') + '".');
				}
			}
			return dif;
		}

		function sinPrograma(m) {
			return 'No se encontró el programa de pago electrónico en este equipo (' + url(m, '') + '). Revisa que ApiPagoElectronico esté abierto (ícono junto al reloj de Windows).';
		}

		return {
			url: url,
			status: function (m) { return llamar(m, 'GET', '/status', undefined, { sinClave: true, timeoutMs: 5000 }); },
			online: function (m) { return llamar(m, 'POST', '/online', { type: m.tipo }, { timeoutMs: 15000 }); },
			cobrar: function (m, monto, txId, credito, signal) { return llamar(m, 'POST', '/pago', cuerpoPago(m, monto, txId, credito), { signal: signal }); },
			estado: function (m, txId) { return llamar(m, 'GET', '/pago/estado/' + encodeURIComponent(txId), undefined, { timeoutMs: 10000 }); },
			cancelar: function (m, txId) { return llamar(m, 'POST', '/pago/cancelar/' + encodeURIComponent(txId), {}, { timeoutMs: 40000 }); },
			resolver: function (m, txId, estado, por, nota) {
				return llamar(m, 'POST', '/pago/resolver/' + encodeURIComponent(txId), { estado: estado, resuelto_por: por || 'caja web', nota: nota || '' }, { timeoutMs: 10000 });
			},
			interpretar: interpretar,
			comparar: comparar,
			sinPrograma: sinPrograma
		};
	})();
</script>
