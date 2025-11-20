<!--  MODAL NUEVO REGISTRO  -->
<div id="ModalNuevoProveedor" class="modal fade" role="dialog" style="overflow-y: scroll;">
	<div class="modal-dialog">
		<div class="modal-content">
			<!-- TITULO  MODAL  -->
			<div id="tituloModalUsuario" class="modal-header btn-success">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title text text-center">REGISTRO DE PROVEEDORES
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
						<fieldset>
							<div class="col-md-6 ">
								<label for="idProveedor">ID</label>
								<input type="number" disabled class="form-control input-sm input-sm" name="idProveedor" id="idProveedor" requerido="no" value="" />
							</div>
						</fieldset>
						<fieldset>
							<div class="col-md-8 ">
								<label for="RutProveedor">RUT PROVEEDOR *</label>
								<input min="0" type="text" class="form-control input-sm input-sm" maxlength='8' onkeypress="return soloNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="RutProveedor" id="RutProveedor" requerido="si" value="" placeholder="Rut Proveedor" />
							</div>
							<div class="col-md-4 ">
								<label for="DvRutProveedor">DV *</label>
								<input min="0" disabled type="text" class="form-control input-sm input-sm" maxlength='1' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="DvRutProveedor" id="DvRutProveedor" requerido="si" value="" placeholder="Dv Proveedor" />
							</div>
						</fieldset>
						<fieldset>
							<div class="col-md-12 ">
								<label for="nombreProveedor">NOMBRE PROVEEDOR *</label>
								<input type="text" class="form-control input-sm input-sm" maxlength='50' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="nombreProveedor" id="nombreProveedor" requerido="si" value="" placeholder="Nombre Proveedor" />
							</div>
						</fieldset>
						<fieldset>
							<div class="col-md-12 ">
								<label for="DireccionProveedor">DIRECCION *</label>
								<input type="text" class="form-control input-sm input-sm" maxlength='50' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="DireccionProveedor" id="DireccionProveedor" requerido="si" value="" placeholder="Direccion Proveedor" />
							</div>
						</fieldset>
						<fieldset>
							<div class="col-md-6 ">
								<label for="TelefonoProveedor">TELEFONO</label>
								<input min="0" type="text" class="form-control input-sm input-sm" maxlength='20' onkeypress="return soloNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="TelefonoProveedor" id="TelefonoProveedor" requerido="si" value="" placeholder="Telefono Proveedor" />
							</div>

							<div class="col-md-6 ">
								<label for="CorreoProveedor">CORREO</label>
								<input type="text" class="form-control input-sm input-sm" maxlength='20' onkeypress="return sololetrasyNumerosCorreo(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="CorreoProveedor" id="CorreoProveedor" requerido="si" value="" placeholder="Correo Proveedor" />
							</div>
						</fieldset>
						<fieldset>
							<div class="col-md-12 ">
								<label for="GiroProveedor">GIRO *</label>
								<input type="text" class="form-control input-sm input-sm" maxlength='50' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="GiroProveedor" id="GiroProveedor" requerido="si" value="" placeholder="Giro Proveedor" />
							</div>
						</fieldset>
						<fieldset>
							<div class="col-md-6 ">
								<label for="CreditoProveedor">CREDITO</label>
								<input min="0" type="text" class="form-control input-sm input-sm" maxlength='8' onkeypress="return soloNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="CreditoProveedor" id="CreditoProveedor" requerido="si" value="" placeholder="Credito Maximo Proveedor" />
							</div>
						</fieldset>
						<!-- FIN CAMPOS DE REGISTRO  -->
					</div>
				</div>
			</div>
			<!-- CONTENIDO  MODAL  -->

			<!-- BOTONES  -->
			<div class="modal-footer">
				<button onclick="grabarNuevoProveedor()" type="button" class="btn btn-success"><i class="fa fa-check-square-o"></i>GRABAR</button>
				<button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-times-circle"></i>SALIR</button>
			</div>
			<!-- FIN BOTONES  -->
		</div>
	</div>
</div>
<!-- FIN  MODAL NUEVO REGISTRO  -->

<script>
	function AbrirModalNuevoProveedor() {
		$('#idProveedor').val("");
		$('#RutProveedor').val("");
		$('#DvRutProveedor').val("");
		$('#nombreProveedor').val("");
		$('#DireccionProveedor').val("");
		$('#TelefonoProveedor').val("");
		$('#CorreoProveedor').val("");
		$('#GiroProveedor').val("");
		$('#CreditoProveedor').val("0");
		$('#ModalNuevoProveedor').modal({
			backdrop: 'static',
			keyboard: false
		});
	}

	$('#RutProveedor').on('change', function() {
		var rut = $('#RutProveedor').val();
		$('#DvRutProveedor').val(validarDV(rut));
	});

	function obtenerDatosSII(rut, dv) {
		if ($("#idProveedor").val().length == 0) {
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
					var maxLength = 50;
					if (result['razon_social'].toUpperCase().replace(/['"$#%&]+/g, '') == '**') {
						$('#nombreProveedor').val('Sin datos')
					} else {
						nombreProveedor = result['razon_social'].slice(0, maxLength);
						$('#nombreProveedor').val(nombreProveedor.toUpperCase().replace(/['"$#%&]+/g, ''));
					}
					if (result['actividades'].length > 0) {
						var primerGiro = result['actividades'][0].giro;
						giroProveedor = primerGiro.slice(0, maxLength);
						$('#GiroProveedor').val(giroProveedor.toUpperCase().replace(/['"$#%&]+/g, ''));
					}
					i++;
				} else {
					$('#nombreProveedor').val("")
					$('#GiroProveedor').val("")
				}
			})

		}
	}



	$('#RutProveedor').on('focusout', function() {
		if ($("#RutProveedor").val().length < 8) {
			showAppAlert('error',"- Ingrese un rut váildo!", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
			// ("#RutProveedor").val("");
		} else {
			if ($('#idProveedor').val().length == 0) { //verificar si el rut existe
				rutProveedor = $('#RutProveedor').val();
				dv = $('#DvRutProveedor').val();
				$.post(base_url + "index.php/Proveedor/busquedaProveedorPorRut", {
					rut: rutProveedor
				}, function(datos) {
					var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
					if (obj.length > 0) {
						$.each(obj, function(index, v) { // recorro el array
							showAppAlert('error',"- El Rut Ingresado : " + rutProveedor + "-" + v.dv + ",\n Ya Esta Registrado. Si desea modificarlo, seleccione al Proveedor", {
                				duration: 5000,
                				image: base_url+ 'assets/img/errorGif.gif'
            				});
							$("#RutProveedor").val("");
							$("#DvRutProveedor").val("");
						});
					} else {
						obtenerDatosSII(rutProveedor, dv);
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

	function grabarNuevoProveedor() {
		if ($('#RutProveedor').val() == 0) {
			showAppAlert('error',"- No Ingreso El Rut Del Proveedor!", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
		} else if ($('#nombreProveedor').val() == 0) {
			showAppAlert('error',"- No Ingreso El Nombre Del Proveedor!", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
		} else if ($('#DireccionProveedor').val() == 0) {
			showAppAlert('error',"- No Ingreso La Direccion Del Proveedor!", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
		} else if ($('#GiroProveedor').val() == 0) {
			showAppAlert('error',"- No Ingreso El Giro Del Proveedor!", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
		} else if ($("#CorreoProveedor").val() != '' && !validateEmail($("#CorreoProveedor").val())) {
			showAppAlert('error',"- Correo Invalido!", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
		} else {
			idProveedor = $('#idProveedor').val();
			NombreProveedor = $('#nombreProveedor').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
			RutProveedor = $('#RutProveedor').val();
			DvRutProveedor = $('#DvRutProveedor').val();
			DireccionProveedor = $('#DireccionProveedor').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
			TelefonoProveedor = $('#TelefonoProveedor').val();
			CorreoProveedor = $('#CorreoProveedor').val().toUpperCase().replace(/[,'"$#%&]+/g, '');
			GiroProveedor = $('#GiroProveedor').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
			MontoCredito = $('#CreditoProveedor').val();

			if (MontoCredito === '') {
 			   MontoCredito = '0';
			}

			var miJson = {
				'id_proveedor': idProveedor,
				'nombre_proveedor': NombreProveedor,
				'rut': RutProveedor,
				'dv': DvRutProveedor,
				'direccion': DireccionProveedor,
				'telefono': TelefonoProveedor,
				'mail': CorreoProveedor,
				'giro': GiroProveedor,
				'creditomaximo': MontoCredito,
			};
			console.log(miJson);
			$.post(base_url + "index.php/Proveedor/grabarNuevoProveedor", {
					'prod': miJson
				}).done(function() {
					showAppAlert('success', "Se registro Exitosamente El Nuevo Proveedor!", {
                        duration: 4000,
                        image: base_url + 'assets/img/success.gif'
                    });
					$('#ModalNuevoProveedor').modal('hide');
					tablaProveedor.ajax.reload();
				})
				.fail(function() {
					showAppAlert('error',"- Se produjo un error al realizar el proceso, favor contacte a su administrador!", {
                		duration: 5000,
                		image: base_url+ 'assets/img/errorGif.gif'
            		});
				})
				.always(function() {});
		}
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
