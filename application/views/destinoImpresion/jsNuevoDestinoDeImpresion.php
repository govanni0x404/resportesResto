<div id="ModalNuevoDestinoDeImpresion" class="modal fade" role="dialog" style="overflow-y: scroll;">
	<div class="modal-dialog">
		<div class="modal-content">
			<!-- TITULO  MODAL  -->
			<div id="tituloModal" class="modal-header btn-success">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title text text-center">REGISTRO DESTINO DE IMPRESION
				</h4>
			</div>
			<!-- FIN TITULO  MODAL  -->

			<!-- CONTENIDO  MODAL  -->
			<div class="modal-body">
				<div class="h-panel">
					<ul id="myTab" class="nav nav-with title font">
						<li class="active"><a href="#tabDatosUsuarios" data-toggle="tab" id="tab1" class="text color1"></a></li>
					</ul>
					<div class="tab-pane fade active in" id="tabDatosUsuarios">
						<!-- CAMPOS DE REGISTRO  -->
						<fieldset>
							<div class="col-md-4 ">
								<label for="idDestinoDeImpresion">ID</label>
								<input type="number" disabled class="form-control input-sm input-sm" name="idDestinoDeImpresion" id="idDestinoDeImpresion" requerido="no" value="">
							</div>
						</fieldset>
						<fieldset>
							<div class="col-md-10 ">
								<label for="nombreDestinoDeImpresion">NOMBRE DESTINO IMPRESION *</label>
								<input type="text" class="form-control input-sm input-sm" maxlength='50' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="nombreDestinoDeImpresion" id="nombreDestinoDeImpresion" requerido="si" value="" placeholder="Nombre Destino De Impresion" />
							</div>

							<div class="col-md-10 ">
								<label for="rutaImpresora">RUTA DE LA IMPRESORA/IP *</label>
								<input type="text" class="form-control input-sm input-sm" maxlength='50' onkeypress="return rutas(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="rutaImpresora" id="rutaImpresora" requerido="si" value="" placeholder="Ruta Destino De Impresion" />
							</div>
						</fieldset>
						<fieldset>
							<div class="col-md-6 ">
								<label for="PuertoImpresora">PUERTO</label>
								<input type="text" class="form-control input-sm input-sm" maxlength='50' onkeypress="return soloNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="PuertoImpresora" id="PuertoImpresora" requerido="si" value="" placeholder="Puerto Destino De Impresion" />
							</div>

							<div class="col-md-6 ">
								<label for="RutaWindowsImpresora">RUTA PARA WINDOWS *</label>
								<input type="text" class="form-control input-sm input-sm" maxlength='50' onkeypress="return rutas(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="RutaWindowsImpresora" id="RutaWindowsImpresora" requerido="si" value="" placeholder="Ruta Windows Destino De Impresion" />
							</div>
						</fieldset>
						<fieldset>
							<div class="col-md-4">
								<div class=" btn-group" role="group" aria-label="...">
									<div class="checkbox checkbox-success checkbox-circle">
										<input id="ckImpresionGraficaWindows" type="checkbox">
										<label for="ckImpresionGraficaWindows">
											IMPRESION GRAFICA PARA WINDOWS
										</label>
									</div>
								</div>
							</div>

							<div class="col-md-4">
								<div class=" btn-group" role="group" aria-label="...">
									<div class="checkbox checkbox-success checkbox-circle">
										<input id="ckPermitirImpresionLocal" type="checkbox">
										<label for="ckPermitirImpresionLocal">
											PERMITIR IMPRESION LOCAL (MODO POS)
										</label>
									</div>
								</div>
							</div>

							<div class="col-md-4">
								<div class=" btn-group" role="group" aria-label="...">
									<div class="checkbox checkbox-success checkbox-circle">
										<input id="ckUsarLogoPrecuenta" type="checkbox">
										<label for="ckUsarLogoPrecuenta">
											USAR LOGO PRECUENTA (MODO POS)
										</label>
									</div>
								</div>
							</div>
						</fieldset>
						<fieldset>
							<div class="col-md-4">
								<div class=" btn-group" role="group" aria-label="...">
									<div class="checkbox checkbox-success checkbox-circle">
										<input id="ckNoImprimirComprobantes" type="checkbox">
										<label for="ckNoImprimirComprobantes">
											NO IMPRIMIR COMPROBANTE
										</label>
									</div>
								</div>
							</div>

							<div class="col-md-4">
								<div class=" btn-group" role="group" aria-label="...">
									<div class="checkbox checkbox-success checkbox-circle">
										<input id="CkReemplazarPorDestinoRecinto" type="checkbox">
										<label for="CkReemplazarPorDestinoRecinto">
											REEMPLAZAR POR DESTINO DEL RECINTO
										</label>
									</div>
								</div>
							</div>

							<div class="col-md-4">
								<div class=" btn-group" role="group" aria-label="...">
									<div class="checkbox checkbox-success checkbox-circle">
										<input id="CkPermitirImpresionPrecuenta" type="checkbox">
										<label for="CkPermitirImpresionPrecuenta">
											PERMITIR IMPRESION PRECUENTA (TABLET)
										</label>
									</div>
								</div>
							</div>


						</fieldset>
						<fieldset>
							<div class="col-md-6 ">
								<label for="ZiseFont">TAMAÑO FUENTE</label>
								<select id="cboZiseFont" nombre="cboZiseFont" class="form-control input-sm" style="width:100%" requerido="si">

								</select>
							</div>
						</fieldset>

						<!-- FIN CAMPOS DE REGISTRO  -->
					</div>
				</div>
			</div>
			<!-- CONTENIDO  MODAL  -->

			<!-- BOTONES  -->
			<div class="modal-footer">
				<button onclick="grabarNuevoDestinoDeImpresion()" type="button" class="btn btn-primary"><i class="fa fa-check-square-o"></i>GRABAR</button>
				<button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times-circle"></i>SALIR</button>
			</div>
			<!-- FIN BOTONES  -->
		</div>
	</div>
</div>
<script>
	function AbrirModalNuevoDestinoDeImpresion() {
		$('#idDestinoDeImpresion').val("");
		$('#nombreDestinoDeImpresion').val("");
		$('#rutaImpresora').val("");
		$('#PuertoImpresora').val("");
		$('#RutaWindowsImpresora').val("");
		$('#ckImpresionGraficaWindows').prop('checked', false);
		$('#ckPermitirImpresionLocal').prop('checked', false);
		$('#ckUsarLogoPrecuenta').prop('checked', false);
		$('#ckNoImprimirComprobantes').prop('checked', false);
		$('#CkReemplazarPorDestinoRecinto').prop('checked', false);
		$('#CkPermitirImpresionPrecuenta').prop('checked', false);
		$('#cboZiseFont').val("");
		sizeDimpresion();
		$("#cboZiseFont").prop('disabled', true);
		$('#ModalNuevoDestinoDeImpresion').modal({
			backdrop: 'static',
			keyboard: false
		});
	}

	function grabarNuevoDestinoDeImpresion() {
		if ($('#nombreDestinoDeImpresion').val() == 0) {
			showAppAlert('error',"- No Ingreso El Nombre Del Destino De Impresion!", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
			return;
		} if ($('#RutaWindowsImpresora').val() === '') {
			showAppAlert('error',"- No Ingreso La Ruta De Impresion!", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
			return;
		} else {
			IdDestinoDeImpresion = $('#idDestinoDeImpresion').val();
			NombreDestinoDeImpresion = $('#nombreDestinoDeImpresion').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
			rutaImpresora = $('#rutaImpresora').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
			RutaWindowsImpresora = $('#RutaWindowsImpresora').val();
			PuertoImpresora = $('#PuertoImpresora').val();
			ZiseFont = $('#cboZiseFont').val();
			if ($('#ckImpresionGraficaWindows').is(":checked")) {
				var impresionGraficaWindows = 1
			} else {
				var impresionGraficaWindows = 0
			}
			if ($('#ckPermitirImpresionLocal').is(":checked")) {
				var permitirImpresionLocal = 1
			} else {
				var permitirImpresionLocal = 0
			}
			if ($('#ckUsarLogoPrecuenta').is(":checked")) {
				var usarLogoPrecuenta = 1
			} else {
				var usarLogoPrecuenta = 0
			}
			if ($('#ckNoImprimirComprobantes').is(":checked")) {
				var noImprimirComprobantes = 1
			} else {
				var noImprimirComprobantes = 0
			}
			if ($('#CkReemplazarPorDestinoRecinto').is(":checked")) {
				var reemplazarPorDestinoRecinto = 1
			} else {
				var reemplazarPorDestinoRecinto = 0
			}
			if ($('#CkPermitirImpresionPrecuenta').is(":checked")) {
				var permitirImpresionPrecuenta = 1
			} else {
				var permitirImpresionPrecuenta = 0
			}
			var miJson = {
				'id_dimpresion': IdDestinoDeImpresion,
				'nombre_dimpresion': NombreDestinoDeImpresion,
				'ip': rutaImpresora,
				'puerto': PuertoImpresora,
				'ruta_windows': RutaWindowsImpresora,
				'impresion_grafica_windows': impresionGraficaWindows,
				'permite_precuenta': permitirImpresionPrecuenta,
				'impresion_local': permitirImpresionLocal,
				'usar_logo_precuenta_pos': usarLogoPrecuenta,
				'sin_impresion': noImprimirComprobantes,
				'usar_dimpresion_recinto': reemplazarPorDestinoRecinto,
				'usar_dimpresion_precuenta_android': permitirImpresionPrecuenta,
				'size_font': ZiseFont,
			};
			$.post(base_url + "index.php/DestinoDeImpresion/grabarNuevoDestinoDeImpresion", {
					'prod': miJson
				}).done(function() {
					showAppAlert('success', "Se registro Exitosamente El Nuevo Destino De Impresion!", {
                        duration: 4000,
                        image: base_url + 'assets/img/success.gif'
                    });
					$('#ModalNuevoDestinoDeImpresion').modal('hide');
					tablaDestinoImpresion.ajax.reload();
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

	function ListadoDestinoDeImpresion(objSelected) {
		$.post(base_url + "index.php/Productos/ListadoDestinoDeImpresion", {
			id: ""
		}, function(datos) {
			var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
			tbl = '<option selected disabled value="0">Seleccione El Destino De Impresion</option>';
			$.each(obj, function(index, v) { // recorro el array
				if (v.nombre_dimpresion == objSelected) {
					tbl = tbl + '<option selected value="' + v.id_dimpresion + '">' + v.nombre_dimpresion + '</option>';
				} else {
					tbl = tbl + '<option value="' + v.id_dimpresion + '">' + v.nombre_dimpresion + '</option>';
				}
			}); //fin each
			$("#cboDestinoDeImpresionFamilia").html(tbl);
		});
	}

	$("#ckImpresionGraficaWindows").change(function() {
        if (this.checked) {
			$("#cboZiseFont").prop('disabled', false);
			$("#ckUsarLogoPrecuenta").prop('checked', false);
			$("#ckUsarLogoPrecuenta").prop('disabled', true);
        } else {
            $("#cboZiseFont").prop('disabled', true);
			$("#ckUsarLogoPrecuenta").prop('disabled', false);
        }
    });

	function sizeDimpresion(objSelected) {
		var tbl = '';
		tbl = tbl + '<option value="0">0</option>';
		tbl = tbl + '<option selected value="8">8</option>';
		tbl = tbl + '<option value="9">9</option>';
		tbl = tbl + '<option value="10">10</option>';
		tbl = tbl + '<option value="11">11</option>';
		tbl = tbl + '<option value="12">12</option>';
		tbl = tbl + '<option value="13">13</option>';
		tbl = tbl + '<option value="14">14</option>';
		$("#cboZiseFont").html(tbl);
		$("#cboZiseFont").select2();
	}
</script>
