<style>
	/* Datos para factura del cliente (12-11-2026) */
	#ModalNuevoCliente [hidden] { display: none !important; }
	#ModalNuevoCliente .cf-ayuda { margin: -4px 0 10px; font-size: 12.5px; color: var(--t-texto-tenue, #8A7A6E); }
	#ModalNuevoCliente .cf-aviso { padding: 8px 10px; border-radius: 8px; background: var(--t-fondo-alt, #FBF8F3); color: var(--t-texto, #3A2A22); }
	#ModalNuevoCliente .cf-bloque { margin-bottom: 10px; padding: 8px 10px; border: 1px solid var(--t-borde, #EADFD2); border-radius: 8px; }
	#ModalNuevoCliente .cf-cab { display: flex; align-items: center; justify-content: space-between; gap: 8px; font-size: 13px; font-weight: 700; color: var(--t-texto, #3A2A22); }
	#ModalNuevoCliente .cf-lista { margin: 6px 0 0; padding: 0; list-style: none; }
	#ModalNuevoCliente .cf-lista li { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 6px 0; border-top: 1px solid var(--t-borde-suave, #F2EAE0); font-size: 13px; }
	#ModalNuevoCliente .cf-lista li:first-child { border-top: 0; }
	#ModalNuevoCliente .cf-lista .cf-vacio { color: var(--t-texto-tenue, #8A7A6E); font-style: italic; }
	#ModalNuevoCliente .cf-texto { min-width: 0; overflow-wrap: anywhere; }
	#ModalNuevoCliente .cf-sub { display: block; font-size: 12px; color: var(--t-texto-tenue, #8A7A6E); }
	#ModalNuevoCliente .cf-acciones { flex: 0 0 auto; white-space: nowrap; }
	#ModalNuevoCliente .cf-form { margin-top: 8px; padding-top: 10px; border-top: 1px dashed var(--t-borde, #EADFD2); }
	#ModalNuevoCliente .cf-form .row > [class*="col-"] { margin-bottom: 8px; }
	#ModalNuevoCliente .cf-form label { margin-bottom: 3px; font-size: 11px; text-transform: uppercase; letter-spacing: .3px; color: var(--t-texto-suave, #7A6A5E); }
	#ModalNuevoCliente .cf-mayus { text-transform: uppercase; }
	#ModalNuevoCliente .cf-botones { text-align: right; }
</style>
<!--  MODAL NUEVO REGISTRO  -->
<div id="ModalNuevoCliente" class="modal fade t-form-modal" role="dialog" style="overflow-y: scroll;">
	<div class="modal-dialog">
		<div class="modal-content">
			<!-- TITULO  MODAL  -->
			<div id="tituloModalUsuario" class="modal-header btn-success">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title text text-center">REGISTRO DE CLIENTES
				</h4>
			</div>
			<!-- FIN TITULO  MODAL  -->

			<!-- CONTENIDO  MODAL  -->
			<div class="modal-body">
				<div class="h-panel">
					<ul id="myTab" class="nav nav-with title font">
						<li class="active"><a href="#tabBodegas" data-toggle="tab" id="tab1" class="text color1"></a></li>
					</ul>
					<div class="tab-pane fade active in" id="tabBodegas">
						<!-- CAMPOS DE REGISTRO  -->
						<div class="t-card-titulo">Identificación</div>
						<fieldset>
							<div class="col-sm-4" style="display:none" data-id-auto="1">
								<label for="idCliente">ID</label>
								<input type="number" readonly class="form-control input-sm input-sm" name="idCliente" id="idCliente" requerido="no" value="" />
							</div>
						</fieldset>
						<fieldset>
							<div class="col-md-8 ">
								<label for="RutCliente">RUT CLIENTE *</label>
								<input min="0" type="text" class="form-control input-sm input-sm" maxlength='8' onkeypress="return soloNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="RutCliente" id="RutCliente" requerido="si" value="" placeholder="Rut Cliente" />
							</div>
							<div class="col-md-4 ">
								<label for="DvRutCliente">DV *</label>
								<input min="0" type="number" disabled class="form-control input-sm input-sm" maxlength='1' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="DvRutCliente" id="DvRutCliente" requerido="si" value="" placeholder="Dv Cliente" />
							</div>
						</fieldset>
						<fieldset>
							<div class="col-md-12 ">
								<label for="nombreCliente">NOMBRE CLIENTE *</label>
								<input type="text" class="form-control input-sm input-sm" maxlength='200' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="nombreCliente" id="nombreCliente" requerido="si" value="" placeholder="Nombre Cliente" />
							</div>
						</fieldset>
						<div class="t-card-titulo">Dirección y contacto</div>
						<fieldset>
							<div class="col-md-12 ">
								<label for="DireccionCliente">DIRECCION *</label>
								<input type="text" class="form-control input-sm input-sm" maxlength='100' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="DireccionCliente" id="DireccionCliente" requerido="si" value="" placeholder="Direccion Cliente" />
							</div>
						</fieldset>
						<fieldset>
							<div class="col-md-6">
								<label for="cboIdComuna">COMUNA *</label>
								<select id="cboIdComuna" nombre="cboIdComuna" class="form-control input-sm" style="width:100%" requerido="si"></select>
								<!-- <div class="input-group">
                                    <select id="cboIdComuna" nombre="cboIdComuna" class="form-control input-sm"
                                        style="width:100%" requerido="si"></select>
                                    
                                        <span class="input-group-btn">
                                            <button class="btn btn-success btn-sm" type="button" id="btnNuevoComunaCliente" 
                                                style="height: 30px;" title="Agregar nueva comuna"
                                                onclick="AbrirModalNuevaComuna()">
                                                <i class="fa fa-plus"></i>
                                            </button>
                                        </span>
                                </div> -->
							</div>
							<div class="col-md-6 ">
								<label for="ReferenciaDireccionCliente">REFERENIA DIRECCION</label>
								<input type="text" class="form-control input-sm input-sm" maxlength='200' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="ReferenciaDireccionCliente" id="ReferenciaDireccionCliente" requerido="si" value="" placeholder="Referencia Direccion Cliente" />
							</div>
						</fieldset>
						<fieldset>
							<div class="col-md-6 ">
								<label for="TelefonoCliente">TELEFONO</label>
								<input min="0" type="text" class="form-control input-sm input-sm" maxlength='9' onkeypress="return soloNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="TelefonoCliente" id="TelefonoCliente" requerido="si" value="" placeholder="Telefono Cliente" />
							</div>

							<div class="col-md-6 ">
								<label for="CorreoCliente">CORREO</label>
								<input type="text" class="form-control input-sm input-sm" maxlength='100' onkeypress="return sololetrasyNumerosCorreo(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="CorreoCliente" id="CorreoCliente" requerido="si" value="" placeholder="Correo Cliente" />
							</div>
						</fieldset>
						<div class="t-card-titulo">Datos comerciales</div>
						<fieldset>
							<div class="col-md-12 ">
								<label for="GiroCliente">GIRO *</label>
								<input type="text" class="form-control input-sm input-sm" maxlength='200' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="GiroCliente" id="GiroCliente" requerido="si" value="" placeholder="Giro Cliente" />
							</div>
						</fieldset>
						<fieldset>
							<div class="col-md-6">
								<label for="cboIdLprecio">LISTA DE PRECIOS *</label>
								<select id="cboIdLprecio" nombre="cboIdLprecio" class="form-control input-sm" style="width:100%" requerido="si"></select>
							</div>
							<div class="col-md-3 ">
								<label for="CreditoMaximoCliente">CRÉDITO MÁXIMO</label>
								<input min="0" type="text" class="form-control input-sm input-sm" maxlength='8' onkeypress="return soloNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="CreditoMaximoCliente" id="CreditoMaximoCliente" requerido="si" value="" placeholder="0 = sin límite" title="Monto máximo que puede deber el cliente (0 = sin límite)" />
							</div>
							<div class="col-md-3 ">
								<label for="PlazoCreditoCliente">PLAZO MÁXIMO</label>
								<select id="PlazoCreditoCliente" class="form-control input-sm" title="Días que tiene el cliente para pagar cada venta a crédito. Con una deuda vencida no se le vende más a crédito.">
									<option value="15">15 días</option>
									<option value="30" selected>30 días</option>
									<option value="45">45 días</option>
									<option value="60">60 días</option>
									<option value="90">90 días</option>
									<option value="0">Sin plazo</option>
								</select>
							</div>
						</fieldset>
						<!-- Datos para factura (12-11-2026): direcciones (sucursales), giros y quienes solicitan facturas. En la Caja,
						     al cobrar con factura, se eligen de estas listas. Ver Mclientefactura y Cliente::guardarDatoFactura -->
						<div class="t-card-titulo">Datos para factura</div>
						<p class="cf-ayuda">Direcciones (sucursales), giros y personas que solicitan facturas. En la Caja se eligen de estas listas; lo nuevo que se use en una factura se agrega aquí.</p>
						<p class="cf-ayuda cf-aviso" id="cfSinGrabar">Graba primero el cliente para agregar sus direcciones, giros y solicitantes.</p>
						<p class="cf-ayuda cf-aviso" id="cfSinTablas" hidden>Falta actualizar la base de datos para estas listas. Vuelve a ingresar al sistema en unos minutos.</p>
						<div id="cfListas" hidden>
							<div class="cf-bloque" data-tipo="direccion">
								<div class="cf-cab"><span>Direcciones / sucursales</span><button type="button" class="btn btn-default btn-xs cf-agregar"><i class="fa fa-plus"></i> Agregar</button></div>
								<ul class="cf-lista"></ul>
								<div class="cf-form" hidden>
									<div class="row">
										<div class="col-sm-7"><label>Dirección *</label><input type="text" class="form-control input-sm cf-mayus" data-campo="direccion" maxlength="70" placeholder="Calle y número (máx. 70, SII)" autocomplete="off"></div>
										<div class="col-sm-5"><label>Comuna *</label><select class="form-control input-sm" data-campo="id_comuna"></select></div>
									</div>
									<div class="row">
										<div class="col-sm-5"><label>Ciudad</label><input type="text" class="form-control input-sm cf-mayus" data-campo="ciudad" maxlength="20" autocomplete="off"></div>
										<div class="col-sm-7"><label>Referencia</label><input type="text" class="form-control input-sm cf-mayus" data-campo="referencia" maxlength="80" placeholder="Opcional (ej. local 3, bodega)" autocomplete="off"></div>
									</div>
									<div class="cf-botones"><button type="button" class="btn btn-default btn-xs cf-cancelar">Cancelar</button> <button type="button" class="btn btn-primary btn-xs cf-guardar"><i class="fa fa-check"></i> Guardar</button></div>
								</div>
							</div>
							<div class="cf-bloque" data-tipo="giro">
								<div class="cf-cab"><span>Giros</span><button type="button" class="btn btn-default btn-xs cf-agregar"><i class="fa fa-plus"></i> Agregar</button></div>
								<ul class="cf-lista"></ul>
								<div class="cf-form" hidden>
									<div class="row">
										<div class="col-sm-8"><label>Giro *</label><input type="text" class="form-control input-sm cf-mayus" data-campo="giro" maxlength="40" placeholder="Máx. 40 caracteres (SII)" autocomplete="off"></div>
										<div class="col-sm-4"><label>Código actividad</label><input type="text" class="form-control input-sm" data-campo="codigo_sii" maxlength="6" inputmode="numeric" placeholder="Opcional" autocomplete="off"></div>
									</div>
									<div class="cf-botones"><button type="button" class="btn btn-default btn-xs cf-cancelar">Cancelar</button> <button type="button" class="btn btn-primary btn-xs cf-guardar"><i class="fa fa-check"></i> Guardar</button></div>
								</div>
							</div>
							<div class="cf-bloque" data-tipo="solicitante">
								<div class="cf-cab"><span>Quienes solicitan facturas</span><button type="button" class="btn btn-default btn-xs cf-agregar"><i class="fa fa-plus"></i> Agregar</button></div>
								<ul class="cf-lista"></ul>
								<div class="cf-form" hidden>
									<div class="row">
										<div class="col-sm-4"><label>RUT *</label><input type="text" class="form-control input-sm" data-campo="rut" maxlength="12" placeholder="12345678-9" autocomplete="off"></div>
										<div class="col-sm-8"><label>Nombre *</label><input type="text" class="form-control input-sm cf-mayus" data-campo="nombre" maxlength="80" autocomplete="off"></div>
									</div>
									<div class="row">
										<div class="col-sm-5"><label>Cargo</label><input type="text" class="form-control input-sm cf-mayus" data-campo="cargo" maxlength="60" placeholder="Opcional" autocomplete="off"></div>
										<div class="col-sm-7"><label>Correo</label><input type="text" class="form-control input-sm" data-campo="mail" maxlength="100" placeholder="Opcional" autocomplete="off"></div>
									</div>
									<div class="cf-botones"><button type="button" class="btn btn-default btn-xs cf-cancelar">Cancelar</button> <button type="button" class="btn btn-primary btn-xs cf-guardar"><i class="fa fa-check"></i> Guardar</button></div>
								</div>
							</div>
						</div>
						<!-- FIN CAMPOS DE REGISTRO  -->
					</div>
				</div>
			</div>
			<!-- CONTENIDO  MODAL  -->

			<!-- BOTONES  -->
			<div class="modal-footer">
				<button onclick="grabarNuevoCliente()" type="button" class="btn btn-primary"><i class="fa fa-check"></i> Grabar</button>
				<button type="button" class="btn btn-default" data-dismiss="modal">Salir</button>
			</div>
			<!-- FIN BOTONES  -->
		</div>
	</div>
</div>
<!-- FIN  MODAL NUEVO REGISTRO  -->

<script>
	function AbrirModalNuevoCliente() {
		$('#idCliente').val("");
		$('#RutCliente').val("");
		$('#DvRutCliente').val("");
		$('#nombreCliente').val("");
		$('#DireccionCliente').val("");
		$('#ReferenciaDireccionCliente').val("");
		$('#TelefonoCliente').val("");
		$('#CorreoCliente').val("");
		$('#GiroCliente').val("");
		$('#CreditoMaximoCliente').val("0");
		$('#PlazoCreditoCliente').val("30");
		ListarComunas("");
		ListarListasDePrecios("");
		$('#ModalNuevoCliente').modal({
			backdrop: 'static',
			keyboard: false
		});
	}

	$('#RutCliente').on('input', function () {
		let rut = $(this).val();
		if (rut.length >= 7) {
			$('#DvRutCliente').val(validarDV(rut));
		} else {
			$('#DvRutCliente').val('');
		}
	});

	function obtenerDatosSII(rut, dv) {
		if ($("#idCliente").val().length == 0) {
			if (rut.length == 8) {
				div1 = rut.slice(0, 2);
				div2 = rut.slice(2, 5);
				div3 = rut.slice(5, 8);
				rut = (div1 + "." + div2 + "." + div3);
			}
			if (rut.length == 7) {
				div1 = rut.slice(0, 1);
				div2 = rut.slice(1, 4);
				div3 = rut.slice(4, 7);

				rut = (div1 + "." + div2 + "." + div3);
			}
			rutC = rut + "-" + dv;
			$.getJSON('https://siichile.herokuapp.com/consulta', {
				rut: rutC
			}, function(result) {
				if (result['error'] == undefined) {
					var i = 1;
					if (result['razon_social'].toUpperCase().replace(/['"$#%&]+/g, '') == '**') {
						$('#nombreCliente').val('Sin datos')
					} else {
						$('#nombreCliente').val(result['razon_social'].toUpperCase().replace(/['"$#%&]+/g, ''))
					}

					$.each(result['actividades'], function(index, v) { // recorro el array
						if (i == 1) {
							$('#GiroCliente').val(v.giro.toUpperCase().replace(/['"$#%&]+/g, ''))
						}
						i++;
					}); //fin each
				} else {
					$('#nombreCliente').val("")
					$('#GiroCliente').val("")
				}
			})

		}
	}

	$('#RutCliente').on('focusout', function() {
		if ($("#RutCliente").val().length < 8) {
			showAppAlert('error',"- Ingrese un rut váildo", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
		} else {
			if ($('#idCliente').val().length == 0) { //verificar si el rut existe
				rutCliente = $('#RutCliente').val();
				dv = $('#DvRutCliente').val();
				$.post(base_url + "index.php/Cliente/busquedaClientePorRut", {
					rut: rutCliente
				}, function(datos) {
					var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
					if (obj.length > 0) {
						$.each(obj, function(index, v) { // recorro el array
							showAppAlert('error',"- El Rut Ingresado : " + rutCliente + "-" + v.dv + ",\n Ya Esta Registrado. Si desea modificarlo, seleccione al Cliente", {
                                duration: 5000,
                                image: base_url+ 'assets/img/errorGif.gif'
                            });
							$("#RutCliente").val("");
							$("#DvRutCliente").val("");
						});
					} else {
						obtenerDatosSII(rutCliente, dv);
					}
				});
			}
		}
	});

	function validarDV(T) {
		var M = 0,
			S = 1;
		for (; T; T = Math.floor(T / 10))
			S = (S + T % 10 * (9 - M++ % 6)) % 11;
		return S ? S - 1 : 'k';
	}

	function grabarNuevoCliente() {
		if (!$('#RutCliente').val() || $('#RutCliente').val().trim() === '') {
	        showAppAlert('error',"No Ingreso El Rut Del Cliente!", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
			return;
		} if (!$('#nombreCliente').val() || $('#nombreCliente').val().trim() === '') {
			showAppAlert('error',"No Ingreso El Nombre Del Cliente!", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
			return;
		} if (!$('#DireccionCliente').val() || $('#DireccionCliente').val().trim() === ''){
			showAppAlert('error',"No Ingreso La Direccion Del Cliente!", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
			return;
		} if (!$('#DireccionCliente').val() || $('#DireccionCliente').val() === '' || $('#DireccionCliente').val() === '0') {
			showAppAlert('error',"No escribio la direccion Del Cliente!", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
			return;
		} if (!$('#cboIdComuna').val() || $('#cboIdComuna').val() === '' || $('#cboIdComuna').val() === '0' || $('#cboIdComuna').val() === 0) {
        	showAppAlert('error',"No Selecciono La Comuna Del Cliente!", {
            	duration: 5000,
            	image: base_url+ 'assets/img/errorGif.gif'
        	});
        return;
    	} if (!$('#GiroCliente').val() || $('#GiroCliente').val().trim() === '') {
			showAppAlert('error',"No Ingreso El Giro Del Cliente!", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
			return;
		} if (!$('#cboIdLprecio').val() || $('#cboIdLprecio').val() === '' || $('#cboIdLprecio').val() === '0') {
			showAppAlert('error',"No Selecciono La Lista De Precios Del Cliente!", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
			return;
		} if ($("#CreditoMaximoCliente").val() != '' && isNaN($("#CreditoMaximoCliente").val())) {
			showAppAlert('error',"El Correo ingresado es invalido", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
			return;
		} else {
			idCliente = $('#idCliente').val();
			NombreCliente = $('#nombreCliente').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
			RutCliente = $('#RutCliente').val();
			DvRutCliente = $('#DvRutCliente').val();
			DireccionCliente = $('#DireccionCliente').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
			ComunaCliente = $('#cboIdComuna').val();
			ReferenciaDireccion = $('#ReferenciaDireccionCliente').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
			TelefonoCliente = $('#TelefonoCliente').val();
			CorreoCliente = $('#CorreoCliente').val().toUpperCase().replace(/[,'"$#%&]+/g, '');
			GiroCliente = $('#GiroCliente').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
			IdListaDePrecios = $('#cboIdLprecio').val();
			MontoCredito = $('#CreditoMaximoCliente').val() === '' ? 0 : parseFloat($('#CreditoMaximoCliente').val());
			PlazoCredito = parseInt($('#PlazoCreditoCliente').val(), 10) || 0;

			var miJson = {
				'id_cliente': idCliente,
				'nombre_cliente': NombreCliente,
				'id_comuna': ComunaCliente,
				'rut': RutCliente,
				'dv': DvRutCliente,
				'direccion': DireccionCliente,
				'telefono': TelefonoCliente,
				'mail': CorreoCliente,
				'giro': GiroCliente,
				'id_lprecio': IdListaDePrecios,
				'creditomaximo': MontoCredito,
				'plazo_credito': PlazoCredito,
				'referencia_direccion': ReferenciaDireccion,
			};
			console.log(miJson);
			$.post(base_url + "index.php/Cliente/grabarNuevoCliente", {
					'prod': miJson
				}).done(function(respuesta) {
					var r = respuesta;
					try { if (typeof r === 'string') r = JSON.parse(r); } catch (e) { r = null; }
					if (r && !Array.isArray(r) && r.error) {
						showAppAlert('error', r.error, { duration: 6000, image: base_url + 'assets/img/errorGif.gif' });
						return;
					}
					showAppAlert('success', "Se registro Exitosamente El Nuevo Cliente!", {
                        duration: 4000,
                        image: base_url + 'assets/img/success.gif'
                    });
					$('#ModalNuevoCliente').modal('hide');
					tablaClientes.ajax.reload();
					
				})
				.fail(function() {
					showAppAlert('error',"- Se produjo un error al realizar el proceso, favor contacte a su administrador", {
                		duration: 5000,
                		image: base_url+ 'assets/img/errorGif.gif'
            		});
				})
				.always(function() {});
		}
	}

	function ListarComunas(objSelected) {
		$.post(base_url + "index.php/Comuna/ListarComunas", {
			id: ""
		}, function(datos) {
			var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
			tbl = '<option selected value="0">Seleccione La Comuna</option>';
			$.each(obj, function(index, v) { // recorro el array
				if (v.nombre_comuna == objSelected) {
					tbl = tbl + '<option selected value="' + v.id_comuna + '">' + v.nombre_comuna + '</option>';
				} else {
					tbl = tbl + '<option value="' + v.id_comuna + '">' + v.nombre_comuna + '</option>';
				}
			}); //fin each
			$("#cboIdComuna").html(tbl);
			$("#cboIdComuna").select2();
		});
	}

	function ListarListasDePrecios(objSelected) {
		$.post(base_url + "index.php/ListaDePrecios/ListadoListasDePrecios", {
			id: ""
		}, function(datos) {
			var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
			tbl = "<option selected value='0'>Seleccione La Lista De Precios</option>";
			$.each(obj, function(index, v) { // recorro el array
				if (v.nombre_lprecio == objSelected) {
					tbl = tbl + '<option selected value="' + v.id_lprecio + '">' + v.nombre_lprecio + '</option>';
				} else {
					tbl = tbl + '<option value="' + v.id_lprecio + '">' + v.nombre_lprecio + '</option>';
				}
			}); //fin each
			$("#cboIdLprecio").html(tbl);
			$("#cboIdLprecio").select2();
		});
	}

	function sololetrasyNumerosCorreo(e, espace) {
		key = e.keyCode || e.which;
		teclado = String.fromCharCode(key).toLowerCase();
		letras = "qwertyuiopasdfghjklzxcvbnm1234567890-._@";
		especiales = "8-37-38-46-164";
		teclado_especial = false;

		for (var i in especiales) {
			if (key == especiales[i]) {
				teclado_especial = true;
				break;
			}
		}

		if (espace == true) {
			if (e.keyCode == 32) {
				return false;
			}
		}

		if (letras.indexOf(teclado) == -1 && !teclado_especial) {
			return false;
		}
	}

	function soloNumeros(e, espace) {
		key = e.keyCode || e.which;
		teclado = String.fromCharCode(key).toLowerCase();
		letras = "1234567890";
		especiales = "8-37-38-46-164";
		teclado_especial = false;

		for (var i in especiales) {
			if (key == especiales[i]) {
				teclado_especial = true;
				break;
			}
		}

		if (espace == true) {
			if (e.keyCode == 32) {
				return false;
			}
		}

		if (letras.indexOf(teclado) == -1 && !teclado_especial) {
			return false;
		}
	}

	// ---------------- Datos para factura (12-11-2026): direcciones, giros y solicitantes del cliente ----------------
	var cfCliente = 0, cfDatos = null, cfComunas = null, cfEditando = null;
	var CF = {
		direccion: { lista: 'direcciones', vacio: 'Sin direcciones adicionales (se usa la de la ficha)', quitar: 'la dirección',
			texto: function (x) { return x.direccion; }, sub: function (x) { return $.grep([x.comuna, x.ciudad, x.referencia], Boolean).join(' · '); } },
		giro: { lista: 'giros', vacio: 'Sin giros adicionales (se usa el de la ficha)', quitar: 'el giro',
			texto: function (x) { return x.giro; }, sub: function (x) { return x.codigo_sii ? 'Código actividad ' + x.codigo_sii : ''; } },
		solicitante: { lista: 'solicitantes', vacio: 'Sin solicitantes registrados', quitar: 'a',
			texto: function (x) { return cfRut(x.rut, x.dv) + ' · ' + x.nombre; }, sub: function (x) { return $.grep([x.cargo, x.mail], Boolean).join(' · '); } }
	};
	function cfRut(rut, dv) { return String(rut).replace(/\B(?=(\d{3})+(?!\d))/g, '.') + '-' + String(dv || '').toUpperCase(); }
	function cfError(x) {
		if (x && x.status === 401) return;   // la sesión expiró: menuTop lleva al ingreso
		showAppAlert('error', (x && x.responseJSON && x.responseJSON.error) || 'No se pudo completar. Intenta de nuevo.', {
			duration: 5000,
			image: base_url + 'assets/img/errorGif.gif'
		});
	}
	function cfCerrarForm() {
		cfEditando = null;
		$('#cfListas .cf-form').prop('hidden', true);
		$('#cfListas .cf-agregar').prop('disabled', false);
	}
	function cfCargar() {
		cfCliente = parseInt($('#idCliente').val(), 10) || 0;
		cfCerrarForm();
		$('#cfSinTablas').prop('hidden', true);
		$('#cfSinGrabar').prop('hidden', cfCliente > 0);
		$('#cfListas').prop('hidden', cfCliente <= 0);
		if (!cfCliente) return;
		var pedido = cfCliente;
		$('#cfListas .cf-lista').html('<li class="cf-vacio">Cargando…</li>');
		$.ajax({ url: base_url + 'index.php/Cliente/listarDatosFactura', data: { id_cliente: cfCliente }, dataType: 'json', cache: false })
			.done(function (r) {
				if (pedido !== cfCliente) return;
				if (!r.disponible) { $('#cfListas').prop('hidden', true); $('#cfSinTablas').prop('hidden', false); return; }
				cfDatos = r;
				cfPintar();
			}).fail(cfError);
	}
	function cfPintar() {
		$.each(CF, function (tipo, def) {
			var $ul = $('#cfListas .cf-bloque[data-tipo="' + tipo + '"] .cf-lista').empty(), lista = cfDatos[def.lista] || [];
			if (!lista.length) $ul.append($('<li class="cf-vacio">').text(def.vacio));
			$.each(lista, function (i, x) {
				var sub = def.sub(x);
				$ul.append($('<li>').attr('data-i', i).append(
					$('<span class="cf-texto">').text(def.texto(x)).append(sub ? $('<span class="cf-sub">').text(sub) : null),
					$('<span class="cf-acciones">').append(
						$('<button type="button" class="btn btn-default btn-xs cf-editar" title="Editar"><i class="fa fa-pencil"></i></button>'), ' ',
						$('<button type="button" class="btn btn-default btn-xs cf-quitar" title="Quitar"><i class="fa fa-trash"></i></button>'))));
			});
		});
	}
	function cfConComunas(listo) {
		if (cfComunas) return listo();
		$.post(base_url + 'index.php/Comuna/ListarComunas', { id: '' }, function (datos) {
			var obj = typeof datos === 'string' ? jQuery.parseJSON(datos) : datos;
			cfComunas = $('<select>').append($('<option>').val('0').text('Seleccione la comuna'));
			$.each(obj, function (i, v) { cfComunas.append($('<option>').val(v.id_comuna).text(v.nombre_comuna)); });
			listo();
		}).fail(cfError);
	}
	function cfAbrirForm(tipo, x) {
		var $b = $('#cfListas .cf-bloque[data-tipo="' + tipo + '"]'), $f = $b.find('.cf-form');
		var abrir = function () {
			cfCerrarForm();
			cfEditando = { tipo: tipo, id: x ? x.id : 0 };
			$f.find('[data-campo]').each(function () {
				var c = $(this).data('campo'), v = x ? x[c] : '';
				if (c === 'rut') v = x ? x.rut + '-' + x.dv : '';
				if (c === 'id_comuna') v = String(x ? x.id_comuna : 0);
				$(this).val(v == null ? '' : v);
			});
			$('#cfListas .cf-agregar').prop('disabled', true);
			$f.prop('hidden', false).find('[data-campo]').first().focus();
		};
		if (tipo !== 'direccion') return abrir();
		cfConComunas(function () {
			var $s = $f.find('[data-campo="id_comuna"]');
			if (!$s.children().length) $s.html(cfComunas.html());
			abrir();
		});
	}
	$('#ModalNuevoCliente').on('show.bs.modal', function (e) { if (e.target === this) cfCargar(); });
	$('#cfListas').on('click', '.cf-agregar', function () {
		cfAbrirForm($(this).closest('.cf-bloque').data('tipo'), null);
	}).on('click', '.cf-editar', function () {
		var tipo = $(this).closest('.cf-bloque').data('tipo');
		cfAbrirForm(tipo, cfDatos[CF[tipo].lista][$(this).closest('li').data('i')]);
	}).on('click', '.cf-cancelar', cfCerrarForm).on('click', '.cf-guardar', function () {
		if (!cfEditando) return;
		var $f = $(this).closest('.cf-form'), datos = {}, falta = null, btn = this;
		$f.find('[data-campo]').each(function () { datos[$(this).data('campo')] = $.trim($(this).val()); });
		if (cfEditando.tipo === 'direccion') {
			if (!datos.direccion) falta = 'Indica la dirección';
			else if (!(Number(datos.id_comuna) > 0)) falta = 'Elige la comuna';
		} else if (cfEditando.tipo === 'giro') {
			if (!datos.giro) falta = 'Indica el giro';
			else if (datos.codigo_sii && !/^\d{1,6}$/.test(datos.codigo_sii)) falta = 'El código de actividad tiene hasta 6 dígitos';
		} else {
			var r = datos.rut.replace(/[^0-9kK]/g, '');
			if (r.length < 2 || String(validarDV(Number(r.slice(0, -1)))).toUpperCase() !== r.slice(-1).toUpperCase()) falta = 'El RUT no es válido';
			else if (!datos.nombre) falta = 'Indica el nombre';
		}
		if (falta) {
			showAppAlert('error', falta, { duration: 4000, image: base_url + 'assets/img/errorGif.gif' });
			return;
		}
		$(btn).prop('disabled', true);
		$.ajax({ url: base_url + 'index.php/Cliente/guardarDatoFactura', type: 'POST', dataType: 'json',
			data: { tipo: cfEditando.tipo, id_cliente: cfCliente, id: cfEditando.id, datos: datos } })
			.done(function () {
				showAppAlert('success', 'Guardado', { duration: 2500, image: base_url + 'assets/img/success.gif' });
				cfCargar();
			}).fail(cfError).always(function () { $(btn).prop('disabled', false); });
	}).on('click', '.cf-quitar', function () {
		var tipo = $(this).closest('.cf-bloque').data('tipo'), x = cfDatos[CF[tipo].lista][$(this).closest('li').data('i')];
		swal({
			title: '¿Quitar?',
			text: 'Se quitará ' + CF[tipo].quitar + ' ' + CF[tipo].texto(x) + ' de la ficha del cliente. Las facturas ya emitidas no cambian.',
			type: 'warning',
			showCancelButton: true,
			confirmButtonColor: '#C9372C',
			cancelButtonText: 'Cancelar',
			confirmButtonText: 'Quitar',
			closeOnConfirm: true
		}, function () {
			$.ajax({ url: base_url + 'index.php/Cliente/quitarDatoFactura', type: 'POST', dataType: 'json',
				data: { tipo: tipo, id_cliente: cfCliente, id: x.id } }).done(cfCargar).fail(cfError);
		});
	}).on('keydown', '.cf-form input', function (e) {
		if (e.which === 13) { e.preventDefault(); $(this).closest('.cf-form').find('.cf-guardar').click(); }
	});

	const validateEmail = (email) => {
		return email.match(
			/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
		);
	};
</script>
 <?php $this->load->view('comunas/jsNuevaComuna.php'); ?>