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
							<div class="col-sm-4">
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
							<div class="col-md-6 ">
								<label for="CreditoMaximoCliente">CREDITO</label>
								<input min="0" type="text" class="form-control input-sm input-sm" maxlength='8' onkeypress="return soloNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="CreditoMaximoCliente" id="CreditoMaximoCliente" requerido="si" value="" placeholder="Credito Maximo Cliente" />
							</div>
						</fieldset>
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
				'referencia_direccion': ReferenciaDireccion,
			};
			console.log(miJson);
			$.post(base_url + "index.php/Cliente/grabarNuevoCliente", {
					'prod': miJson
				}).done(function() {
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

	const validateEmail = (email) => {
		return email.match(
			/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
		);
	};
</script>
 <?php $this->load->view('comunas/jsNuevaComuna.php'); ?>