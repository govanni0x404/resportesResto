<div id="ModalNuevoImpuesto" class="modal fade" role="dialog" style="overflow-y: scroll;">
	<div class="modal-dialog">
		<div class="modal-content">
			<!-- TITULO  MODAL  -->
			<div id="tituloModal" class="modal-header btn-success">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title text text-center">REGISTRO IMPUESTOS
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
								<label for="idImpuesto">ID</label>
								<input type="number" disabled class="form-control input-sm input-sm" name="idImpuesto" id="idImpuesto" requerido="no" value="">
							</div>
							<div class="col-md-12 ">
								<label for="nombreImpuesto">NOMBRE IMPUESTO *</label>
								<input type="text" class="form-control input-sm input-sm" maxlength='50' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="nombreImpuesto" id="nombreImpuesto" requerido="si" value="" placeholder="Nombre Impuesto" />
							</div>
						</fieldset>
						<fieldset>
							<fieldset>
								<div class="col-md-12">
									<label for="txtObservacionImpuesto">OBSERVACION</label>
									<textarea class="form-control" rows="3" id="txtObservacionImpuesto" placeholder="Observacion Impuesto"></textarea>
								</div>
							</fieldset>
						</fieldset>
						<fieldset>
							<div class="col-md-6 ">
								<label for="TasaImpuesto">TASA (%) *</label>
								<input min="0" max="60" type="number" onkeypress="return maxNumber()" class="form-control input-sm input-sm" maxlength='5' onpaste="return false;" ondrop="return false;" autocomplete="off" name="TasaImpuesto" id="TasaImpuesto" requerido="si" value="" placeholder="Tasa (%) Impuesto" />
							</div>
							<div class="col-md-6 ">
								<label for="CodigoSiiImpuesto">CODIGO SII</label>
								<input min="0" type="number" class="form-control input-sm input-sm" maxlength='50' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="CodigoSiiImpuesto" id="CodigoSiiImpuesto" requerido="si" value="" placeholder="Codigo SII Impuesto" />
							</div>
						</fieldset>

						<!-- FIN CAMPOS DE REGISTRO  -->
					</div>
				</div>
			</div>
			<!-- CONTENIDO  MODAL  -->

			<!-- BOTONES  -->
			<div class="modal-footer">
				<button onclick="grabarNuevoImpuesto()" type="button" class="btn btn-primary"><i class=""></i>GRABAR</button>
				<button type="button" class="btn btn-default" data-dismiss="modal"><i class=""></i>SALIR</button>
			</div>
			<!-- FIN BOTONES  -->
		</div>
	</div>
</div>

<style>
    @media (max-width: 991px) {
        #ModalNuevoImpuesto fieldset > [class*="col-"] {
            margin-bottom: 12px;
        }
    }
</style>

<script>
	function AbrirModalNuevoImpuesto() {
		$('#idImpuesto').val("");
		$('#nombreImpuesto').val("");
		$('#txtObservacionImpuesto').val("");
		$('#TasaImpuesto').val("0");
		$('#CodigoSiiImpuesto').val("0");
		$('#ModalNuevoImpuesto').modal({
			backdrop: 'static',
			keyboard: false
		});
	}

	function grabarNuevoImpuesto() {
		if ($('#nombreImpuesto').val() == 0) {
			showAppAlert('error',"No Ingreso El Nombre Del Impuesto!", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
		} else if ($('#TasaImpuesto').val() == 0) {
			showAppAlert('error',"No Ingreso La Tasa Del Impuesto!", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            })
		} else {
			IdImpuesto = $('#idImpuesto').val();
			NombreImpuesto = $('#nombreImpuesto').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
			ObservacionImpuesto = $('#txtObservacionImpuesto').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
			TasaImpuesto = $('#TasaImpuesto').val();
			CodigoSiiImpuesto = $('#CodigoSiiImpuesto').val();
			var miJson = {
				'id_impuesto': IdImpuesto,
				'nombre_impuesto': NombreImpuesto,
				'observacion': ObservacionImpuesto,
				'codigo_sii': CodigoSiiImpuesto,
				'valor': TasaImpuesto,
			};
			$.post(base_url + "index.php/Impuestos/grabarNuevoImpuesto", {
					'prod': miJson
				}).done(function() {
					showAppAlert('success', "Se registro Exitosamente El Nuevo Impuesto!", {
                        duration: 4000,
                        image: base_url + 'assets/img/success.gif'
                    });
					$('#ModalNuevoImpuesto').modal('hide');
					tablaImpuestos.ajax.reload();
				})
				.fail(function() {
					showAppAlert('error',"Se produjo un error al realizar el proceso, favor contacte a su administrador", {
                		duration: 5000,
                		image: base_url+ 'assets/img/errorGif.gif'
            		})
				})
				.always(function() {});
		}
	}

	function maxNumber() {
		var numberInput = document.getElementById('TasaImpuesto');
		numberInput.addEventListener('input', function() {
			var value = parseInt(numberInput.value);
			if (numberInput.value.length > 5) {
				numberInput.value = numberInput.value.slice(0, 5);
			}
			if (value > 50) {
				numberInput.value = 50;
			}
		});
	}
</script>
