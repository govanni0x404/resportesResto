		// ---------------- cliente ----------------
		var timerCliente = null;
		// v1.24.16: el lápiz junto al cliente elegido abre su ficha para cambiarle datos sin salir de la Caja
		function clienteEditable() {
			var pub = catalogo && catalogo.config ? Number(catalogo.config.id_cliente_publico) : 0;
			return !!(cliente && Number(cliente.id_cliente) && Number(cliente.id_cliente) !== pub);
		}
		function ponerTxtCliente(t) {
			$('#txtCliente').val(t);
			$('#btnEditarCliente').prop('hidden', !clienteEditable());
		}
		$('#btnEditarCliente').on('click', function () {
			if (!clienteEditable()) return;
			get('fichaCliente', { id_cliente: cliente.id_cliente }).done(function (c) {
				formClienteNuevo();
				$('#modalCliente').modal('show');
				editarCliente(c);
			});
		});
		$('#btnBuscarCliente').on('click', function () {
			formClienteNuevo();
			$('#modalCliente').modal('show');
			$('#modalCliente a[href="#tabBuscarCliente"]').tab('show');
			setTimeout(function () { $('#txtBuscarCliente').val('').focus(); buscarClientes(''); }, 300);
		});
		$('#txtCliente').on('click', function () { $('#btnBuscarCliente').click(); });
		$('#btnQuitarCliente').on('click', function () {
			cliente = null;
			ponerTxtCliente('');
		});
		$('#txtBuscarCliente').on('input', function () {
			clearTimeout(timerCliente);
			var q = $(this).val();
			timerCliente = setTimeout(function () { buscarClientes(q); }, 250);
		});

		function buscarClientes(q) {
			get('clientes', { q: q }).done(function (r) {
				var $r = $('#resultadosCliente').empty();
				if (!r.clientes.length) $r.append('<div class="list-group-item text-muted">Sin resultados</div>');
				$.each(r.clientes, function (i, c) {
					var rut = c.rut ? c.rut + '-' + c.dv : '';
					var publico = Number(c.id_cliente) === catalogo.config.id_cliente_publico;
					var extra = $.grep([rut, c.giro, c.direccion, c.comuna], function (x) { return !!x; });
					$r.append($('<a href="#" class="list-group-item">')
						.data('cliente', { id_cliente: Number(c.id_cliente), nombre_cliente: c.nombre_cliente, rut: rut })
						.data('ficha', c)
						.html('<span class="cli-datos"><b>' + esc(c.nombre_cliente) + '</b><small>' + esc(extra.join(' · ')) + '</small></span>'
							+ (publico ? '<span class="cli-publico">Venta al público</span>'
								: '<button type="button" class="btn btn-editar-cliente" title="Cambiar los datos de este cliente"><i class="fa fa-pencil"></i>Editar</button>')));
				});
			});
		}

		function usarCliente(c) {
			if (!cliente || !c || cliente.id_cliente !== c.id_cliente) datosFactura = null;
			cliente = c;
			ponerTxtCliente(c.nombre_cliente + (c.rut ? ' (' + c.rut + ')' : ''));
			$('#modalCliente').modal('hide');
		}

		$('#resultadosCliente').on('click', 'a', function (e) {
			e.preventDefault();
			usarCliente($(this).data('cliente'));
		});

		// El mismo formulario sirve para crear y para editar (id_cliente oculto)
		function formClienteNuevo() {
			var $f = $('#frmCliente');
			$f[0].reset();
			$f.find('[name=id_cliente]').val('');
			$('#cboComuna').val('0').trigger('change');
			$('#tabClienteForm').html('<i class="fa fa-plus"></i> Nuevo');
			$('#btnCancelarEdicion').hide();
			$('#cliEditando').prop('hidden', true);
			$('#btnGuardarCliente').html('<i class="fa fa-check"></i> Guardar y usar');
		}
		$('#resultadosCliente').on('click', '.btn-editar-cliente', function (e) {
			e.preventDefault();
			e.stopPropagation();
			editarCliente($(this).closest('a').data('ficha'));
		});
		function editarCliente(c) {
			var $f = $('#frmCliente');
			$f.find('[name=id_cliente]').val(c.id_cliente);
			$f.find('[name=nombre_cliente]').val(c.nombre_cliente);
			$f.find('[name=rut]').val(c.rut ? c.rut + '-' + c.dv : '');
			$f.find('[name=giro]').val(c.giro);
			$f.find('[name=direccion]').val(c.direccion);
			$f.find('[name=telefono]').val(c.telefono);
			$f.find('[name=mail]').val(c.mail);
			$('#cboComuna').val(String(c.id_comuna || 0)).trigger('change');
			$('#tabClienteForm').html('<i class="fa fa-pencil"></i> Editar').tab('show');
			$('#btnCancelarEdicion').show();
			$('#cliEditandoNombre').text(c.nombre_cliente);
			$('#cliEditando').prop('hidden', false);
			$('#btnGuardarCliente').html('<i class="fa fa-check"></i> Guardar cambios');
			setTimeout(function () { $f.find('[name=direccion]').focus(); }, 250);
		}
		$('#btnCancelarEdicion').on('click', function () {
			formClienteNuevo();
			$('#modalCliente a[href="#tabBuscarCliente"]').tab('show');
		});

		$('#frmCliente').on('submit', function (e) {
			e.preventDefault();
			var $f = $(this);
			var editando = !!$f.find('[name=id_cliente]').val();
			post('guardarCliente', $f.serialize()).done(function (r) {
				formClienteNuevo();
				toastr.success(editando ? 'Cliente actualizado' : 'Cliente creado');
				// v1.24.16: si se editó el cliente de esta venta, los datos de factura se vuelven a tomar de la ficha nueva
				if (editando && cliente && Number(cliente.id_cliente) === Number(r.id_cliente)) datosFactura = null;
				usarCliente({ id_cliente: Number(r.id_cliente), nombre_cliente: r.nombre_cliente, rut: r.rut,
					giro: r.giro, direccion: r.direccion, id_comuna: Number(r.id_comuna) || 0 });
			});
		});

		// ---------------- autorización de administrador ----------------
		// enviar(aut) hace la petición; si el servidor responde 403 {autorizacion}, se pide la clave y se reintenta.
		var autorizacionPendiente = null;
		function enviarConAutorizacion(enviar, listo, alFallar, aut) {
			return enviar(aut || null).done(listo).fail(function (xhr) {
				var r = xhr.responseJSON;
				if (xhr.status === 403 && r && r.autorizacion) {
					pedirAutorizacion(r, aut ? r.error : '', function (nueva) {
						enviarConAutorizacion(enviar, listo, alFallar, nueva);
					}, alFallar);
				} else {
					error(xhr);
					if (alFallar) alFallar();
				}
			});
		}

		function pedirAutorizacion(r, mensajeError, continuar, cancelar) {
			var $c = $('#cboAutorizador').empty();
			var soloClaveGeneral = /^usar la lista de precios/.test(r.motivo || '') && catalogo.config.tiene_clave_admin;
			if (catalogo.config.tiene_clave_admin) $c.append($('<option>').val(0).text('Clave de administración'));
			if (!soloClaveGeneral) $.each(catalogo.administradores, function (i, a) { $c.append($('<option>').val(a.id_usuario).text(a.nombre)); });
			if (!$c.children().length) {
				swal('Sin administradores', 'Se requiere autorización para ' + r.motivo + ', pero no hay usuarios administradores ni clave de administración configurada.', 'error');
				if (cancelar) cancelar();
				return;
			}
			autorizacionPendiente = { continuar: continuar, cancelar: cancelar };
			$('#autorizacionMotivo').text('Se requiere autorización de un administrador para: ' + r.motivo + '.');
			$('#autorizacionError').text(mensajeError || '').toggle(!!mensajeError);
			$('#txtClaveAdmin').val('');
			$('#modalAutorizacion').modal('show');
		}

		$('#modalAutorizacion').on('shown.bs.modal', function () { $('#txtClaveAdmin').focus(); })
			.on('hidden.bs.modal', function () {
				// Si quedó otro modal abierto debajo (cobro), se mantiene el scroll del modal
				if ($('.modal.in').length) $('body').addClass('modal-open');
				if (autorizacionPendiente && autorizacionPendiente.cancelar) autorizacionPendiente.cancelar();
				autorizacionPendiente = null;
			});

		$('#frmAutorizacion').on('submit', function (e) {
			e.preventDefault();
			var clave = $('#txtClaveAdmin').val();
			if (!clave) { $('#txtClaveAdmin').focus(); return; }
			var pendiente = autorizacionPendiente;
			if (!pendiente) return;
			autorizacionPendiente = null; // no cancelar al cerrar: se reintenta
			var aut = { id_usuario: Number($('#cboAutorizador').val()), clave: clave };
			// Se reintenta con el modal ya cerrado: si la clave es incorrecta debe poder abrirse de nuevo
			$('#modalAutorizacion').one('hidden.bs.modal', function () { pendiente.continuar(aut); }).modal('hide');
		});

		// ---------------- pago ----------------
		function esCortesia() {
			return pagos.length > 0 && fpago(pagos[0].id_fpago).cortesia;
		}
		function totalAPagar() {
			return totales().total + (esCortesia() ? 0 : propina);
		}
		function propinaMaxima() {
			return Math.floor(totales().total * (catalogo.config.propina_max_pct || 15) / 100);
		}
		/** v1.23.0: ¿esta venta lleva propina? Mesa: "Propina en mesas"; venta directa: "Propina en venta directa" (las dos con "Usar propina") */
		function conPropinaAqui() {
			return mesa ? !!catalogo.config.propina_mesas : !!catalogo.config.propina;
		}
		function propinaSugerida() {
			return Math.min(Math.round(totales().total * catalogo.config.por_propina / 100), propinaMaxima());
		}

		$('#btnCobrar').on('click', function () {
			// En una mesa solo se cobra lo ya enviado
			if (mesa && pendientesMesa().length) { toastr.warning('Primero envía a la mesa los productos nuevos'); return; }
			// 18-11-2026: delivery nuevo: primero sus datos; "Cobrar al entregar" lo guarda sin cobrar
			if (modoDelivery && modoDelivery.nuevo && !validarEntrega()) return;
			if (cobrarAlEntregar()) { guardarDeliveryPendiente(); return; }
			abrirPago();
		});
		// Antes del pago se pregunta por la propina (si corresponde): Sí = monto sugerido o el que se escriba, No = 0
		function abrirPago(conFactura) {
			var cfg = catalogo.config;
			if (!validarCobro()) return;
			// Factura: primero sus datos (giro, dirección, quién la solicita); después la propina y el pago
			if (conFactura !== true && documentoActual().es_factura && cliente) { pedirDatosFactura(function () { abrirPago(true); }); return; }
			// v1.23.0: la propina se pregunta solo donde Configuraciones la habilita (Usar propina + en mesas / en venta directa)
			if (!conPropinaAqui()) { mostrarPago(0); return; }
			var sugerida = propinaSugerida();
			$('#propinaPregunta').text(fmt(totales().total));
			$('#propinaPorcentaje').text((cfg.por_propina > 0 ? 'Sugerida: ' + cfg.por_propina + '% (' + fmt(sugerida) + ')' : 'Escribe el monto de la propina')
				+ ' · Máximo ' + (cfg.propina_max_pct || 15) + '% (' + fmt(propinaMaxima()) + ')');
			$('#txtPropinaPregunta').val(sugerida || '');
			$('#modalPropina').modal('show');
		}
		$('#modalPropina').on('shown.bs.modal', function () { $('#btnPropinaSi').focus(); });

		// ---------------- datos de la factura ----------------
		// Al cobrar con factura (venta directa, mesa o una parte de la mesa) se piden razón social, giro, dirección, comuna,
		// contacto y quién la solicita (RUT y nombre). Se proponen los de la ficha del cliente o los de sus facturas anteriores;
		// el servidor los guarda con la venta (venta_receptor) y la emisión y el ticket usan esos datos. Si la base aún no tiene
		// esa tabla (migración pendiente), la factura sale con la ficha del cliente, como antes.
		var datosFactura = null, facInfo = null, facListo = null, facConfirmado = false;
		function rutTexto(rut, dv) { return rut ? rut + '-' + String(dv || '').toUpperCase() : ''; }
		function rutValido(texto) {
			var t = String(texto || '').replace(/[^0-9kK]/g, '').toUpperCase();
			if (t.length < 2) return false;
			var cuerpo = t.slice(0, -1), dv = t.slice(-1), suma = 0, mult = 2;
			for (var i = cuerpo.length - 1; i >= 0; i--) { suma += Number(cuerpo.charAt(i)) * mult; mult = mult === 7 ? 2 : mult + 1; }
			var r = 11 - (suma % 11);
			return dv === (r === 11 ? '0' : (r === 10 ? 'K' : String(r)));
		}
		// Listas del cliente (12-11-2026): giro, dirección (sucursal) y solicitante se eligen de la ficha del cliente (Clientes >
		// Datos para factura) y de sus facturas anteriores. "Otro…" muestra los campos para escribirlo; lo nuevo se agrega a la
		// ficha al vender (salvo que se desmarque "Guardar en la ficha"). Los campos de texto siguen siendo los que se envían.
		function facClave(t) { return $.trim(String(t || '')).toUpperCase(); }
		function facOrigen(o) { return o === 'ficha' ? ' (ficha)' : (o === 'anterior' ? ' (factura anterior)' : ''); }
		function rutConPuntos(rut, dv) { return rut ? String(rut).replace(/\B(?=(\d{3})+(?!\d))/g, '.') + '-' + String(dv || '').toUpperCase() : ''; }
		function facListas(r) {
			var $g = $('#facGiroSel').empty();
			$.each(r.giros || [], function (i, g) { $g.append($('<option>').val(i).text(g.giro + facOrigen(g.origen))); });
			$g.append($('<option>').val('otro').text('Otro giro…'));
			var $d = $('#facDirSel').empty();
			$.each(r.direcciones || [], function (i, d) {
				$d.append($('<option>').val(i).text($.grep([d.direccion, d.comuna, d.ciudad], Boolean).join(', ') + facOrigen(d.origen)));
			});
			$d.append($('<option>').val('otro').text('Otra dirección…'));
			var $s = $('#facSolSel').empty();
			if ((r.solicitantes || []).length) $s.append($('<option>').val('').text('Elige quién solicita…'));
			$.each(r.solicitantes || [], function (i, x) { $s.append($('<option>').val(i).text(rutConPuntos(x.rut, x.dv) + ' · ' + x.nombre)); });
			$s.append($('<option>').val('otro').text('Otra persona…'));
		}
		// Deja cada lista en el valor que tienen los campos (o en "Otro…" si no está en la lista)
		function facSincronizar() {
			if (!facInfo) return;
			var giro = facClave($('#facGiro').val()), gi = -1;
			$.each(facInfo.giros || [], function (i, g) { if (gi < 0 && facClave(g.giro) === giro) gi = i; });
			$('#facGiroSel').val(gi >= 0 ? String(gi) : 'otro');
			$('#facGiro').prop('hidden', gi >= 0);
			var dir = facClave($('#facDireccion').val()), com = Number($('#facComuna').val()) || 0, di = -1;
			$.each(facInfo.direcciones || [], function (i, d) { if (di < 0 && facClave(d.direccion) === dir && Number(d.id_comuna) === com) di = i; });
			$('#facDirSel').val(di >= 0 ? String(di) : 'otro');
			$('#facDirOtra').prop('hidden', di >= 0);
			var rut = $('#facRutSol').val().replace(/[^0-9kK]/g, '').toUpperCase(), si = -1;
			$.each(facInfo.solicitantes || [], function (i, x) { if (si < 0 && rut && String(x.rut) + String(x.dv).toUpperCase() === rut) si = i; });
			var sinElegir = !rut && (facInfo.solicitantes || []).length;
			$('#facSolSel').val(si >= 0 ? String(si) : (sinElegir ? '' : 'otro'));
			$('#facSolOtro').prop('hidden', si >= 0 || sinElegir);
		}
		function facLlenar(d) {
			$('#facRazon').val(d.razon_social || '');
			$('#facGiro').val(d.giro || '');
			$('#facDireccion').val(d.direccion || '');
			$('#facComuna').val(String(d.id_comuna || 0));
			if (!$('#facComuna').val()) $('#facComuna').val('0');
			$('#facCiudad').val(d.ciudad || '');
			$('#facContacto').val(d.contacto || '');
			facSincronizar();
		}
		function pedirDatosFactura(listo) {
			$.ajax({ url: base + 'receptorCliente', data: { id_cliente: cliente.id_cliente }, dataType: 'json', cache: false }).done(function (r) {
				if (!r.disponible) { datosFactura = null; listo(); return; }   // sin la tabla: con la ficha del cliente, como antes
				facInfo = r;
				var c = r.cliente;
				$('#facRut').text(rutTexto(c.rut, c.dv) + ' · ' + c.razon_social);
				$('#facComuna').html($('#cboComuna').html());
				var $p = $('#facPrevios').empty().append($('<option>').val('ficha').text('Ficha del cliente' + (c.giro || c.direccion ? ': ' + $.grep([c.giro, c.direccion, c.comuna], Boolean).join(' · ') : '')));
				$.each(r.anteriores, function (i, a) {
					$p.append($('<option>').val(i).text('Factura del ' + a.fecha + ': ' + $.grep([a.giro, a.direccion, a.comuna], Boolean).join(' · ')));
				});
				$('#facPreviosGrupo').prop('hidden', !r.anteriores.length);
				facListas(r);
				$('#facGuardarGrupo').prop('hidden', !r.guardar_ficha);
				// Lo ya elegido en esta venta (por ejemplo, si se canceló el pago); si no, la última factura del cliente o su ficha
				var mismo = datosFactura && datosFactura.id_cliente === c.id_cliente;
				$('#facRutSol').val(mismo ? datosFactura.rut_solicita : '');
				$('#facNombreSol').val(mismo ? datosFactura.nombre_solicita : '');
				$('#facMotivo').val(mismo ? (datosFactura.motivo || '') : '');
				$('#facGuardarFicha').prop('checked', mismo ? datosFactura.guardar_ficha !== false : true);
				if (mismo) facLlenar(datosFactura);
				else if (r.anteriores.length) { facLlenar(r.anteriores[0]); $p.val('0'); }
				else facLlenar(c);
				$('#frmFactura .has-error').removeClass('has-error');
				facListo = listo;
				facConfirmado = false;
				$('#modalFactura').modal('show');
			}).fail(error);
		}
		$('#facPrevios').on('change', function () {
			var v = $(this).val();
			if (!facInfo) return;
			facLlenar(v === 'ficha' ? facInfo.cliente : facInfo.anteriores[Number(v)]);
		});
		$('#facGiroSel').on('change', function () {
			var v = $(this).val(), g = facInfo && facInfo.giros[Number(v)];
			$('#facGiro').val(v === 'otro' || !g ? '' : g.giro).prop('hidden', v !== 'otro');
			if (v === 'otro') $('#facGiro').focus();
		});
		$('#facDirSel').on('change', function () {
			var v = $(this).val(), d = facInfo && facInfo.direcciones[Number(v)];
			$('#facDirOtra').prop('hidden', v !== 'otro');
			if (v === 'otro' || !d) {
				$('#facDireccion').val('');
				$('#facComuna').val('0');
				$('#facCiudad').val('');
				$('#facDireccion').focus();
				return;
			}
			$('#facDireccion').val(d.direccion);
			$('#facComuna').val(String(d.id_comuna || 0));
			if (!$('#facComuna').val()) $('#facComuna').val('0');
			$('#facCiudad').val(d.ciudad || '');
			$('#facDirOtra .has-error, #facCiudad').closest('.form-group').removeClass('has-error');
		});
		$('#facSolSel').on('change', function () {
			var v = $(this).val(), x = facInfo && facInfo.solicitantes[Number(v)];
			$('#facSolOtro').prop('hidden', v !== 'otro');
			$('#facRutSol').val(x && v !== 'otro' && v !== '' ? rutTexto(x.rut, x.dv) : '');
			$('#facNombreSol').val(x && v !== 'otro' && v !== '' ? x.nombre : '');
			$('#facSolOtro .has-error').removeClass('has-error');
			if (v === 'otro') $('#facRutSol').focus();
		});
		// RUT de un solicitante anterior: se completa su nombre
		$('#facRutSol').on('input change', function () {
			var t = $(this).val().replace(/[^0-9kK]/g, '').toUpperCase();
			var s = facInfo && $.grep(facInfo.solicitantes, function (x) { return String(x.rut) + String(x.dv).toUpperCase() === t; })[0];
			if (s && !$.trim($('#facNombreSol').val())) $('#facNombreSol').val(s.nombre).closest('.form-group').removeClass('has-error');
		});
		$('#modalFactura').on('shown.bs.modal', function () {
			var vacio = $('#frmFactura').find('#facGiro, #facDireccion, #facRutSol, #facNombreSol').filter(':visible').filter(function () { return !$.trim($(this).val()); }).first();
			if (!vacio.length && $('#facSolSel').val() === '') vacio = $('#facSolSel');
			(vacio.length ? vacio : $('#btnFacturaContinuar')).focus();
		}).on('hidden.bs.modal', function () {
			var f = facListo, ok = facConfirmado;
			facListo = null;
			facConfirmado = false;
			if (ok && f) f();
		});
		$('#frmFactura').on('submit', function (e) { e.preventDefault(); $('#btnFacturaContinuar').click(); })
			.on('input change', '.form-control', function () { $(this).closest('.form-group').removeClass('has-error'); });
		$('#btnFacturaContinuar').on('click', function () {
			if (!facInfo || !cliente) return;
			var falta = null;
			var exigir = function (sel, msg, ok) {
				var bien = ok !== undefined ? ok : !!$.trim($(sel).val());
				$(sel).closest('.form-group').toggleClass('has-error', !bien);
				if (!bien && !falta) falta = { sel: sel, msg: msg };
			};
			exigir('#facRazon', 'Indica la razón social');
			exigir($('#facGiro').prop('hidden') ? '#facGiroSel' : '#facGiro', 'Indica el giro', !!$.trim($('#facGiro').val()));
			exigir($('#facDirOtra').prop('hidden') ? '#facDirSel' : '#facDireccion', 'Indica la dirección', !!$.trim($('#facDireccion').val()));
			exigir('#facComuna', 'Elige la comuna', Number($('#facComuna').val()) > 0);
			if ($('#facSolSel').val() === '') exigir('#facSolSel', 'Elige quién solicita la factura', false);
			exigir('#facRutSol', 'Indica el RUT de quien solicita la factura (válido)', rutValido($('#facRutSol').val()));
			exigir('#facNombreSol', 'Indica el nombre de quien solicita la factura');
			if (falta) {
				// Un dato de la lista que no sirve (por ejemplo, sin comuna): se muestra para corregirlo
				if (falta.sel === '#facComuna') $('#facDirSel').val('otro'), $('#facDirOtra').prop('hidden', false);
				toastr.warning(falta.msg);
				$(falta.sel).focus();
				return;
			}
			datosFactura = {
				id_cliente: cliente.id_cliente,
				razon_social: $.trim($('#facRazon').val()),
				giro: $.trim($('#facGiro').val()),
				direccion: $.trim($('#facDireccion').val()),
				id_comuna: Number($('#facComuna').val()),
				ciudad: $.trim($('#facCiudad').val()),
				contacto: $.trim($('#facContacto').val()),
				rut_solicita: $.trim($('#facRutSol').val()).toUpperCase(),
				nombre_solicita: $.trim($('#facNombreSol').val()),
				motivo: $.trim($('#facMotivo').val()),
				guardar_ficha: !!facInfo.guardar_ficha && $('#facGuardarFicha').prop('checked')
			};
			facConfirmado = true;
			$('#modalFactura').modal('hide');
		});
		$('#txtPropinaPregunta').on('keydown', function (e) { if (e.which === 13) { e.preventDefault(); $('#btnPropinaSi').click(); } });
		$('#btnPropinaSi, #btnPropinaNo').on('click', function () {
			var monto = this.id === 'btnPropinaSi' ? Math.max(0, Math.round(Number($('#txtPropinaPregunta').val()) || 0)) : 0;
			if (monto > propinaMaxima()) {
				toastr.warning('La propina no puede superar el ' + (catalogo.config.propina_max_pct || 15) + '% del total (' + fmt(propinaMaxima()) + ')');
				$('#txtPropinaPregunta').val(propinaMaxima()).focus().select();
				return;
			}
			// El pago se abre cuando este modal terminó de cerrarse (Bootstrap 3 no apila bien dos modales)
			$('#modalPropina').one('hidden.bs.modal', function () { mostrarPago(monto); }).modal('hide');
		});
		function validarCobro() {
			var d = documentoActual();
			if (!d || !d.habilitado) { swal('Atención', 'No hay documentos de venta disponibles (tdocumento.para_venta)', 'warning'); return false; }
			if (d.requiere_cliente && !cliente) {
				swal('Falta el cliente', d.nombre + ' requiere seleccionar un cliente', 'warning');
				return false;
			}
			var t = totales();
			// v1.24.1: tope en pesos = el máximo % del subtotal redondeado (igual que el descuento en %): un 10% exacto pasa
			// v1.30.3: 0 = no se permite descuento; sin límite = 100
			var tope = Number(catalogo.config.por_maximo_descuento);
			if (!isFinite(tope)) tope = 100;
			if (tope < 100 && t.subtotal > 0 && t.descuento > Math.round(t.subtotal * tope / 100)) {
				swal('Descuento', tope <= 0 ? 'No se permiten descuentos (Configuraciones > Descuento máximo está en 0)' : 'El descuento máximo permitido es ' + tope + '%', 'warning');
				return false;
			}
			// Configuraciones > Descuentos > Requerir cliente
			if (catalogo.config.descuento_cliente && t.descuento > 0 && (!cliente || Number(cliente.id_cliente) === catalogo.config.id_cliente_publico)) {
				swal('Falta el cliente', 'Para hacer un descuento debes elegir el cliente de la venta', 'warning');
				return false;
			}
			return true;
		}
		// propinaElegida: monto respondido en el modal de propina; null si no se preguntó
		function mostrarPago(propinaElegida) {
			var t = totales();
			pagos = [];
			$('#grupoRetira').toggle(!mesa);   // quien retira: solo venta directa
			// Propina sugerida (Configuraciones: propina en venta directa / calcular valor de propina)
			var cfg = catalogo.config;
			var conPropina = conPropinaAqui();   // v1.23.0: según Configuraciones (antes solo en mesas)
			$('#bloquePropina').toggle(conPropina);
			$('#lblPorPropina').text(cfg.por_propina > 0 ? '(sugerida ' + cfg.por_propina + '%)' : '');
			propina = propinaElegida !== null ? propinaElegida : (conPropina && (cfg.calcular_propina || mesa) ? propinaSugerida() : 0);
			$('#chkPropina').prop('checked', propina > 0).prop('disabled', false);
			$('#txtPropina').val(propina).prop('disabled', propina === 0);
			// Efectivo primero, cortesías al final; el resto en el orden de la base
			var orden = function (f) { return f.cortesia ? 2 : (f.efectivo ? 0 : 1); };
			var formas = catalogo.formas_pago.slice().sort(function (a, b) { return orden(a) - orden(b); });
			var $b = $('#botonesFpago').empty();
			$.each(formas, function (i, f) {
				var icono = f.cortesia ? 'fa-gift' : (f.efectivo ? 'fa-money' : (/confianza/i.test(f.nombre) ? 'fa-handshake-o' : (/tarjeta|debito|credito/i.test(f.nombre) ? 'fa-credit-card' : 'fa-exchange')));
				$b.append($('<button type="button" class="btn">').toggleClass('cortesia', !!f.cortesia).data('id', f.id_fpago)
					.html('<i class="fa ' + icono + '"></i> ' + esc(f.nombre)));
			});
			var defecto = fpago(catalogo.config.id_fpago_defecto);
			if (!defecto || defecto.cortesia) defecto = $.grep(catalogo.formas_pago, function (f) { return f.efectivo; })[0] || formas[0];
			renderPagos();
			elegirFpago(defecto.id_fpago);
			$('#modalPago').modal('show');
		}
		$('#modalPago').on('shown.bs.modal', function () { $('#montoPago').focus().select(); });
		// Enter en "quien retira" no registra nada: vuelve al monto (ahí Enter agrega el pago)
		$('#txtRetira').on('keydown', function (e) { if (e.which === 13) { e.preventDefault(); $('#montoPago').focus().select(); } });

		$('#chkPropina').on('change', function () {
			propina = this.checked ? (propinaSugerida() || propina) : 0;
			$('#txtPropina').val(propina).prop('disabled', !this.checked);
			if (this.checked) $('#txtPropina').focus().select();
			ajustarPagoUnico();
		});
		$('#txtPropina').on('input', function () {
			propina = Math.max(0, Math.round(Number($(this).val()) || 0));
			if (propina > propinaMaxima()) {
				propina = propinaMaxima();
				$(this).val(propina);
				toastr.warning('La propina no puede superar el ' + (catalogo.config.propina_max_pct || 15) + '% del total (' + fmt(propina) + ')');
			}
			ajustarPagoUnico();
		});

		// Con un solo pago cargado automáticamente, se ajusta al nuevo total con propina
		function ajustarPagoUnico() {
			if (pagos.length === 1 && !pagos[0].tocado && !fpago(pagos[0].id_fpago).cortesia) pagos[0].monto = totalAPagar();
			renderPagos();
			if (!montoTocado && fpagoSel && !fpago(fpagoSel).cortesia) $('#montoPago').val(faltaPagar());
		}

		// ---- Forma de pago elegida (columna izquierda) y monto a cargar (columna derecha) ----
		var fpagoSel = null, montoTocado = false;
		function faltaPagar() { return Math.max(0, totalAPagar() - sumaPagos(null)); }
		function elegirFpago(id) {
			fpagoSel = id;
			var f = fpago(id);
			$('#botonesFpago .btn').each(function () { $(this).toggleClass('activa', $(this).data('id') === id); });
			$('#nombreFpagoSel').text(f.nombre.toLowerCase());
			montoTocado = false;
			$('#montoPago').val(f.cortesia ? totales().total : faltaPagar()).prop('readonly', !!f.cortesia);
			var billetes = '';
			if (f.efectivo) {
				$.each([1000, 2000, 5000, 10000, 20000], function (j, v) {
					billetes += '<button type="button" class="btn btn-default" data-v="' + v + '">+' + fmt(v) + '</button>';
				});
			}
			$('#billetesPago').html(billetes);
		}
		$('#botonesFpago').on('click', 'button', function () {
			elegirFpago($(this).data('id'));
			$('#montoPago').focus().select();
		});
		$('#montoPago').on('input', function () { montoTocado = true; })
			.on('keydown', function (e) { if (e.which === 13) { e.preventDefault(); $('#btnAgregarPago').click(); } });
		$('#billetesPago').on('click', 'button', function () {
			// El primer billete reemplaza el saldo sugerido; los siguientes se suman
			var actual = montoTocado ? (Math.round(Number($('#montoPago').val())) || 0) : 0;
			montoTocado = true;
			$('#montoPago').val(actual + Number($(this).data('v'))).focus();
		});

		// Suma el monto a la lista de pagos (si la forma ya está, se acumula en la misma fila)
		function agregarPago(id, monto) {
			var f = fpago(id);
			if (f.cortesia) { pagos = [{ id_fpago: id, monto: totales().total }]; return; }
			if (monto <= 0) return;
			// Una forma normal reemplaza la cortesía y descarta filas en 0
			pagos = $.grep(pagos, function (p) { return !fpago(p.id_fpago).cortesia && p.monto > 0; });
			var existente = $.grep(pagos, function (p) { return p.id_fpago === id; })[0];
			if (existente) { existente.monto += monto; existente.tocado = true; }
			else pagos.push({ id_fpago: id, monto: monto, tocado: monto !== faltaPagar() });
		}
		// 17-11-2026: formas que ya tienen un pago (no se repiten: el segundo pago va con otra forma)
		function fpagoUsada(id) {
			return $.grep(pagos, function (p) { return p.id_fpago === id && p.monto > 0 && !fpago(id).cortesia; }).length > 0;
		}
		$('#btnAgregarPago').on('click', function () {
			if (fpagoUsada(fpagoSel)) { toastr.warning('Esa forma de pago ya está agregada: elige otra o corrige el monto en la lista'); return; }
			agregarPago(fpagoSel, Math.max(0, Math.round(Number($('#montoPago').val())) || 0));
			renderPagos();
			// 17-11-2026: si falta, queda elegida la siguiente forma libre (efectivo primero) con el saldo; antes quedaba la
			// misma y "Pago exacto" o Enter sumaban el resto a esa forma (débito + efectivo terminaba todo en débito)
			if (!fpago(fpagoSel).cortesia && faltaPagar() > 0) {
				var libre = null;
				$('#botonesFpago .btn').each(function () {
					var id = $(this).data('id');
					if (!libre && !fpago(id).cortesia && !fpagoUsada(id)) libre = id;
				});
				if (libre) elegirFpago(libre);
			}
			if (!fpago(fpagoSel).cortesia) { montoTocado = false; $('#montoPago').val(faltaPagar()); }
			if (!$('#btnConfirmarPago').prop('disabled')) $('#btnConfirmarPago').focus();
			else $('#montoPago').focus().select();
		});

		// Suma de los pagos, sin contar la fila "excepto"
		function sumaPagos(excepto) {
			var suma = 0;
			$.each(pagos, function (i, p) { if (p !== excepto) suma += p.monto; });
			return suma;
		}

		// Pago exacto: completa lo que falta con la forma elegida (sin vuelto) y registra la venta de inmediato
		$('#btnPagoExacto').on('click', function () {
			if (enviando) return;
			var f = fpago(fpagoSel);
			if (f.cortesia) {
				pagos = [{ id_fpago: f.id_fpago, monto: totales().total }];
			} else {
				pagos = $.grep(pagos, function (p) { return !fpago(p.id_fpago).cortesia && p.monto > 0; });
				var sobra = sumaPagos(null) - totalAPagar();
				if (sobra > 0) {
					// Lo cargado ya pasa del total: se rebaja hasta dejarlo exacto, empezando por el último pago
					for (var i = pagos.length - 1; i >= 0 && sobra > 0; i--) {
						var baja = Math.min(sobra, pagos[i].monto);
						pagos[i].monto -= baja;
						sobra -= baja;
					}
					pagos = $.grep(pagos, function (p) { return p.monto > 0; });
				}
				agregarPago(f.id_fpago, faltaPagar());
			}
			renderPagos();
			if (!$('#btnConfirmarPago').prop('disabled')) $('#btnConfirmarPago').click();
		});

		function renderPagos() {
			var $f = $('#filasPago').empty();
			if (!pagos.length) $f.html('<div class="vacio">Aún no hay pagos. Elige la forma de pago, ingresa el monto y presiona <b>Agregar pago</b>, o usa <b>Pago exacto</b>.</div>');
			$.each(pagos, function (i, p) {
				var f = fpago(p.id_fpago);
				$f.append('<div class="pago-fila" data-i="' + i + '"><span class="nombre">' + esc(f.nombre) + '</span>'
					+ '<input type="number" min="0" step="1" class="form-control monto" value="' + p.monto + '"' + (f.cortesia ? ' readonly' : '') + '>'
					+ '<button type="button" class="btn btn-default quitar" title="Quitar pago"><i class="fa fa-times"></i></button></div>');
			});
			// 17-11-2026: las formas ya agregadas quedan bloqueadas (quitando su fila se liberan)
			$('#botonesFpago .btn').each(function () {
				var usada = fpagoUsada($(this).data('id'));
				$(this).prop('disabled', usada).toggleClass('usada', usada).attr('title', usada ? 'Ya está agregada' : null);
			});
			// La cortesía no lleva propina
			var cortesia = esCortesia();
			$('#chkPropina, #txtPropina').prop('disabled', cortesia);
			if (!cortesia) $('#txtPropina').prop('disabled', !$('#chkPropina').prop('checked'));
			actualizarResumenPago();
		}

		function actualizarResumenPago() {
			var total = totalAPagar();
			// Título = lo que se cobra en total; con propina se detalla la venta y la propina
			var prop = esCortesia() ? 0 : propina;
			$('#pagoTotal').text(fmt(total));
			$('#pagoTotalDetalle').text(prop > 0 ? 'Venta ' + fmt(totales().total) + ' + propina ' + fmt(prop) : '');
			var pagado = 0, efectivo = 0;
			$.each(pagos, function (i, p) {
				pagado += p.monto;
				if (fpago(p.id_fpago).efectivo) efectivo += p.monto;
			});
			var vuelto = Math.max(0, pagado - total);
			var falta = Math.max(0, total - pagado);
			$('#pagoTotalConPropina').text(fmt(total));
			$('#pagoPagado').text(fmt(pagado));
			$('#pagoFalta').text(fmt(falta));
			$('#pagoVuelto').text(fmt(vuelto));
			// Mismas reglas que Mcaja::registrarVenta: el vuelto sale del último pago en efectivo y la propina
			// debe caber en un solo pago (ese mismo o, sin efectivo, el último)
			var validos = $.grep(pagos, function (p) { return p.monto > 0; }), idxEf = -1;
			$.each(validos, function (i, p) { if (fpago(p.id_fpago).efectivo) idxEf = i; });
			var vueltoValido = vuelto === 0 || (idxEf >= 0 && validos[idxEf].monto > vuelto);
			var idxProp = idxEf >= 0 ? idxEf : validos.length - 1;
			var propinaCabe = prop === 0 || falta > 0 || (idxProp >= 0 && validos[idxProp].monto - (idxProp === idxEf ? vuelto : 0) >= prop);
			$('#pagoVuelto').toggleClass('text-danger', !vueltoValido);
			$('#cajaFalta').toggleClass('cubierto', falta === 0);
			$('#avisoReglaPago').text(!vueltoValido ? 'El vuelto debe salir de un solo pago en efectivo mayor que el vuelto.'
				: (!propinaCabe ? 'La propina debe quedar dentro de un solo pago: agrega el efectivo como un solo monto o paga la propina en un pago aparte.' : '')).toggle(!vueltoValido || !propinaCabe);
			$('#btnConfirmarPago').prop('disabled', !pagos.length || falta > 0 || !vueltoValido || !propinaCabe || enviando);
		}

		$('#filasPago').on('input', '.monto', function () {
			var p = pagos[$(this).closest('[data-i]').data('i')];
			p.monto = Math.max(0, Math.round(Number($(this).val()) || 0));
			p.tocado = true;
			actualizarResumenPago();
		}).on('click', '.quitar', function () {
			pagos.splice($(this).closest('[data-i]').data('i'), 1);
			renderPagos();
			if (!montoTocado && fpagoSel && !fpago(fpagoSel).cortesia) $('#montoPago').val(faltaPagar());
		});

		// 14-11-2026: token del cobro. Se repite si se reintenta (doble clic, red lenta) y el servidor no registra dos
		// veces la misma venta; se renueva cuando la venta queda registrada.
		var tokenCobro = null;
		function nuevoTokenCobro() {
			var a = new Uint8Array(16);
			if (window.crypto && window.crypto.getRandomValues) window.crypto.getRandomValues(a);
			else for (var i = 0; i < a.length; i++) a[i] = Math.floor(Math.random() * 256);
			return Array.prototype.map.call(a, function (b) { return ('0' + b.toString(16)).slice(-2); }).join('');
		}

		$('#btnConfirmarPago').on('click', function () {
			if (enviando) return;
			if (!tokenCobro) tokenCobro = nuevoTokenCobro();
			var t = totales();
			var idMesa = mesa ? mesa.id_mesa : 0;
			// Dividir cuenta: si se está cobrando una de sus partes, al registrarse la venta queda marcada como pagada
			var dcEnCobro = dcCobro && mesa && dcCobro.id_mesa === mesa.id_mesa && dcCobro.parcial === cobroParcial ? dcCobro : null;
			var venta = {
				id_mesa: idMesa,
				parcial: idMesa && cobroParcial ? $.map(cobroParcial, function (q, id) { return { id_detalle: Number(id), cantidad: q }; }) : null,
				id_tdocumento: Number($('#cboDocumento').val()),
				id_lprecio: Number($('#cboLista').val()),
				id_cliente: cliente ? cliente.id_cliente : (modoDelivery && modoDelivery.id_cliente) || 0,
				descuento: t.descuento,
				propina: esCortesia() ? 0 : propina,
				observacion: $.trim($('#txtObservacion').val()),
				// Venta directa: nombre de quien retira (opcional), va en la comanda y en el documento.
				// 18-11-2026: en un delivery, el nombre de quien recibe
				retira: idMesa ? '' : (modoDelivery ? (modoDelivery.nuevo ? $.trim($('#delNombre').val()) : modoDelivery.nombre) : $.trim($('#txtRetira').val())).replace(/\s+/g, ' ').slice(0, 60),
				// 18-11-2026: delivery nuevo (sus datos) o uno guardado que se cobra ahora (su número)
				delivery: modoDelivery && modoDelivery.nuevo ? entregaPantalla() : null,
				id_delivery: modoDelivery && modoDelivery.id ? modoDelivery.id : 0,
				// Factura: razón social, giro, dirección, comuna y solicitante elegidos en la ventana Datos de la factura
				factura: documentoActual().es_factura && cliente && datosFactura && datosFactura.id_cliente === cliente.id_cliente ? datosFactura : null,
				detalle: $.map(carro, function (l) { return { id_producto: l.id_producto, cantidad: l.cantidad, agregados: l.agregados, extras: l.extras || [], nota: l.nota, notas_agregados: l.notas_agregados || {}, menu: l.menu || [], sushi: l.sushi || null }; }),
				pagos: $.map(pagos, function (p) { return p.monto > 0 ? { id_fpago: p.id_fpago, monto: p.monto } : null; }),
				token: tokenCobro
			};
			var ctx = { t: t, idMesa: idMesa, dcEnCobro: dcEnCobro };
			// 19-11-2026: pago electrónico. Lo que va con las formas de pago de la máquina POS de esta caja se cobra
			// primero en la máquina; la venta se registra cuando la máquina lo aprueba (sección pago electrónico)
			var montoPos = montoEnMaquina(venta.pagos);
			if (montoPos > 0) { cobrarEnMaquina(venta, montoPos, ctx); return; }
			enviarVenta(venta, ctx);
		});

		/**
		 * Registra la venta (Caja::vender), con la autorización de un administrador si hace falta.
		 * ctx: {t, idMesa, dcEnCobro} de la pantalla; ctx.replay = venta guardada de un cobro con tarjeta (barra de pendientes).
		 */
		function enviarVenta(venta, ctx, alFallar) {
			enviando = true;
			actualizarResumenPago();
			var terminar = function () { enviando = false; actualizarResumenPago(); };
			var t0Venta = performance.now();
			enviarConAutorizacion(function (aut) {
				venta.autorizacion = aut;
				t0Venta = performance.now();
				return $.ajax({ url: base + 'vender', type: 'POST', contentType: 'application/json', data: JSON.stringify(venta), dataType: 'json' })
					.fail(function (x) {
						// 19-11-2026 (auditoría): "Este cobro ya se registró" = la venta SÍ quedó guardada (se cortó la respuesta y se
						// reintentó). Se cierra el cobro y se limpia la pantalla para no volver a cobrarla; el aviso dice qué venta es.
						var msg = x && x.responseJSON && x.responseJSON.error ? String(x.responseJSON.error) : '';
						if (x && x.status === 409 && /ya se registr/i.test(msg) && !ctx.replay) {
							$('#modalPago').modal('hide');
							limpiarVenta();
							cargarEstado();
							if ($('#mesasGrilla').is(':visible') || ctx.idMesa) cargarMesas();
						}
					});
			}, function (r) {
				terminar();
				// v1.25.3: con ?medir=1, cuánto tardó el cobro y, dentro de él, la boleta electrónica (Cloud Apps)
				if (MEDIR_IMPRESION) {
					var seg = function (n) { return ((n || 0) / 1000).toFixed(1) + ' s'; };
					toastr.info('Cobro ' + seg(performance.now() - t0Venta) + (r && r.dte && r.dte.ms != null ? ' · Boleta electrónica (Cloud Apps) ' + seg(r.dte.ms) : ' · Sin boleta electrónica'),
						'Tiempos: venta', { timeOut: 0, extendedTimeOut: 0, closeButton: true });
				}
				ventaRegistrada(r, ctx);
			}, function () {
				terminar();
				if (alFallar) alFallar();
			});
		}

		// v1.23.0: vuelto en grande (pago en efectivo). Recibido = total + propina + vuelto. Se cierra con Listo o Enter.
		function mostrarVuelto(r) {
			$('#vueltoMonto').text(fmt(r.vuelto));
			$('#vueltoTotal').text(fmt((r.total || 0) + (r.propina || 0)));
			$('#vueltoRecibido').text(fmt((r.total || 0) + (r.propina || 0) + r.vuelto));
			$('#vueltoImprimiendo').removeClass('listo').html('<i class="fa fa-print"></i> Imprimiendo el documento…').toggle(imprimeTicket());
			$('body').addClass('vuelto-abierto');
			$('#modalVuelto').modal('show');
		}
		$('#modalVuelto').on('hidden.bs.modal', function () {
			$('body').removeClass('vuelto-abierto');
			if (!$('.modal.in:visible').length) $('#buscarProducto:visible').focus();
		});
		function vueltoImpreso() {
			$('#vueltoImprimiendo').addClass('listo').html('<i class="fa fa-check"></i> Documento enviado a la impresora');
		}
		$('#modalVuelto').on('shown.bs.modal', function () { $('#btnVueltoListo').focus(); });

		// v1.24.18: la gaveta se abre sola al cobrar en efectivo (o con cualquier cobro si en Configuraciones está apagada
		// "Abrir gaveta solo por pago en efectivo"). Lo decide el servidor; va antes de la boleta para que se abra al tiro.
		function abrirGavetaVenta(idVenta) {
			if (!idVenta) return;
			var local = impresionLocalActiva() && !!window.ImpresionLocal;
			$.ajax({ url: base + 'gavetaVenta', type: 'POST', dataType: 'json', global: false, data: { id_venta: idVenta, local: local ? 1 : 0 } })
				.done(function (g) {
					if (!g || !g.abrir) {
						if (g && g.detalle) toastr.warning(g.detalle, 'No se abrió la gaveta', { timeOut: 8000 });
						return;
					}
					if (g.local && g.destino) {
						ImpresionLocal.imprimir(g.destino, [{ accion: 'abrir_gaveta', valor: '' }], { tiempo: 15000 }).then(null, function (e) {
							toastr.error(textoPlano(ImpresionLocal.mensaje(e)), 'No se pudo abrir la gaveta', { timeOut: 10000, closeButton: true });
						});
					} else if (!g.ok && g.detalle) {
						toastr.error(g.detalle, 'No se pudo abrir la gaveta', { timeOut: 10000, closeButton: true });
					}
				});
		}

		function ventaRegistrada(r, ctx) {
			abrirGavetaVenta(r && r.id_venta);
			if (ctx.replay) { ventaPendienteRegistrada(r, ctx); return; }
			var t = ctx.t, idMesa = ctx.idMesa, dcEnCobro = ctx.dcEnCobro;
			if (ctx.pagoElectronico) cargarPagoElectronico();   // el cobro con tarjeta ya no está pendiente
			tokenCobro = null;   // venta registrada: el próximo cobro lleva otro token
			if (dcEnCobro) dcRegistrarPago(dcEnCobro, r);
			$('#txtRetira').val('');   // el nombre es de esta venta
			$('#modalPago').modal('hide');
			if (r.total !== t.total) toastr.warning('El total se recalculó con los precios vigentes: ' + fmt(r.total));
			$('#ventaOkTitulo').text('Venta registrada');
			// Mesa cobrada completa: se vuelve al panel de mesas, no a la venta directa
			var mesaCerrada = idMesa && r.mesa_liberada !== false;
			$('#btnNuevaVenta').text(mesaCerrada ? 'Volver a mesas' : 'Nueva venta');
			mostrarResultadoDte(r.dte);
			$('#ventaOkDoc').text(r.documento + (r.folio ? ' N° ' + r.folio : '') + ' · ' + fmt(r.total) + (r.propina > 0 ? ' + propina ' + fmt(r.propina) : ''));
			$('#ventaOkVuelto').text(r.vuelto > 0 ? 'Vuelto: ' + fmt(r.vuelto) : '');
			$('#ventaOkTicket').attr('href', base + 'ticket/' + r.id_venta + '?imprimir=1');
			$('#ventaOkPdf').attr('href', base + 'pdf/' + r.id_venta);
			// Impresión local (12-11-2026): el documento sale solo por el agente del equipo, así que no se muestra
			// "Venta registrada" y la caja queda lista para la siguiente venta (el vuelto queda en el aviso). Si el SII no
			// recibió el documento o este equipo no imprime, se muestra la ventana como antes.
			var sinVentana = impresionLocalActiva() && imprimeTicket() && !(r.dte && !r.dte.ok);
			if (sinVentana) {
				// v1.23.0: con vuelto, ventana grande con el vuelto mientras se imprime el documento (antes era un aviso chico)
				if (r.vuelto > 0) mostrarVuelto(r);
				else toastr.success($('#ventaOkDoc').text(), 'Venta registrada', { timeOut: 3500 });
			} else {
				$('#modalVentaOk').modal('show');
			}
			// La comanda sale después de la boleta (en la cola del agente quedan en ese orden).
			// En una mesa la comanda ya salió al enviar el pedido.
			var comandaVenta = imprimeComanda() && !idMesa ? r.comanda_id : null;
			// 18-11-2026: con "diseño de la página" la comanda se prepara (imagen dibujada y subida) mientras sale la
			// boleta; se imprime igual después de ella
			var comandaLista = null;
			if (comandaVenta && imprimeTicket() && conImagen()) {
				comandaLista = prepararComandaLocal(comandaVenta);
				comandaLista.catch(function () {});
			}
			// 18-11-2026: pantalla "Imprimiendo…" hasta que salen la boleta y la comanda
			var listoVenta = (imprimeTicket() || comandaVenta)
				? bloquearImpresion(imprimeTicket() ? 'Imprimiendo ' + (r.documento ? r.documento.toLowerCase() : 'documento') + '…' : 'Imprimiendo comanda…')
				: function () {};
			// v1.23.0: con la ventana del vuelto abierta, ella misma avisa cuando terminó de imprimir
			if (sinVentana && r.vuelto > 0) { var listoSinVuelto = listoVenta; listoVenta = function () { listoSinVuelto(); vueltoImpreso(); }; }
			var conComanda = function () {
				if (comandaVenta) {
					if (imprimeTicket()) $bloqueo.find('.bloqueo-texto').text('Imprimiendo comanda…');
					imprimirComanda(comandaVenta, 'Comanda', comandaLista, listoVenta);
				} else listoVenta();
			};
			if (imprimeTicket()) imprimirTicket(r.id_venta, conComanda);
			else conComanda();
			// 18-11-2026: delivery cobrado: se sale del modo delivery y se vuelve a su lista
			var eraDelivery = !!modoDelivery;
			if (eraDelivery) {
				salirDelivery();
				if (r.id_delivery) toastr.success('Puedes enviarle el mensaje por WhatsApp desde la lista', 'Delivery N° ' + r.id_delivery + ' cobrado', { timeOut: 6000 });
				$('#lnkDelivery').tab('show');
				cargarDeliverys();
			} else limpiarVenta();
			cargarEstado();
			// Se cobró una parte: la mesa sigue abierta con el resto
			if (idMesa && r.mesa_liberada === false) { cargarMesas(); abrirMesa(idMesa); }
			else if (mesaCerrada) { cargarMesas(); $('#lnkMesas').tab('show'); }
			// Sin la ventana: lo mismo que al cerrarla (foco en el buscador; dividir cuenta sigue con la parte siguiente)
			if (sinVentana) $('#modalVentaOk').triggerHandler('hidden.bs.modal');
		}

