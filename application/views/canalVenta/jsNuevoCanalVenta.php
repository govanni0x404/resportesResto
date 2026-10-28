<!--  MODAL NUEVO REGISTRO  -->
<div id="ModalNuevoCanalDeVenta" class="modal fade t-form-modal" role="dialog" style="overflow-y: scroll;">
	<div class="modal-dialog">
		<div class="modal-content">
			<!-- TITULO  MODAL  -->
			<div id="tituloModalUsuario" class="modal-header btn-success">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title text text-center">REGISTRO DE CANAL DE VENTAS
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
							<div class="col-md-4 ">
								<label for="idCanalDeVenta">ID</label>
								<input type="number" disabled class="form-control input-sm input-sm" name="idCanalDeVenta" id="idCanalDeVenta" requerido="no" value="" />
							</div>
						</fieldset>
						<fieldset>
							<div class="col-md-12">
								<label for="nombreCanalDeVenta">NOMBRE CANAL DE VENTA *</label>
								<input type="text" class="form-control input-sm input-sm" maxlength='50' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="nombreCanalDeVenta" id="nombreCanalDeVenta" requerido="si" value="" placeholder="Nombre Canal De Venta" />
							</div>
							<div class="col-md-12">
								<label for="cboIdLprecio">LISTA DE PRECIOS *</label>
								<select id="cboIdLprecio" nombre="cboIdLprecio" class="form-control input-sm" style="width:100%" requerido="si"></select>
							</div>
						</fieldset>
						<fieldset>
							<div class="col-md-6">
								<div class="btn-group" role="group" aria-label="...">
									<div class="checkbox checkbox-success">
										<br>
										<input id="ckEsCortesia" type="checkbox">
										<label for="ckEsCortesia">
											CORTESIA
										</label>
									</div>
								</div>
							</div>

							<div class="col-md-6">
								<div class="btn-group" role="group" aria-label="...">
									<div class="checkbox checkbox-success">
										<br>
										<input id="ckVigente" type="checkbox">
										<label for="ckVigente">
											VIGENTE
										</label>
									</div>
								</div>
							</div>
						</fieldset>
						<!-- FIN CAMPOS DE REGISTRO  -->
					</div>
				</div>
			</div>
			<!-- CONTENIDO  MODAL  -->

			<!-- BOTONES  -->
			<div class="modal-footer">
				<button onclick="grabarNuevoCanalDeVenta()" type="button" class="btn btn-primary"><i class="fa fa-check"></i> Grabar</button>
				<button type="button" class="btn btn-default" data-dismiss="modal">Salir</button>
			</div>
			<!-- FIN BOTONES  -->
		</div>
	</div>
</div>
<!-- FIN  MODAL NUEVO REGISTRO  -->

<script>
	function AbrirModalNuevoCentroCosto() {
		$('#idCanalDeVenta').val("");
		$('#nombreCanalDeVenta').val("");
		$('#ckEsCortesia').prop('checked', false);
		$('#ckVigente').prop('checked', true);
		ListarListasDePrecios("");
		$('#ModalNuevoCanalDeVenta').modal({
			backdrop: 'static',
			keyboard: false
		});
	}

	function grabarNuevoCanalDeVenta() {
		if ($('#nombreCanalDeVenta').val() == 0) {
	        showAppAlert('error',"No Ingreso El Nombre Del Canal De Venta!", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
		} else if ($('#cboIdLprecio').val() == 0) {
			showAppAlert('error',"No ha seleccionado ninguna lista de precios!", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
		} else {
			idCanalDeVenta = $('#idCanalDeVenta').val();
			NombreCanalDeVenta = $('#nombreCanalDeVenta').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
			idLprecio = $('#cboIdLprecio').val();
			if ($('#ckEsCortesia').is(":checked")) {
				var EsCortesia = 1
			} else {
				var EsCortesia = 0
			}
			if ($('#ckVigente').is(":checked")) {
				var Vigente = 1
			} else {
				var Vigente = 0
			}
			var miJson = {
				'id_canal_venta': idCanalDeVenta,
				'nombre_canal_venta': NombreCanalDeVenta,
				'id_lprecio': idLprecio,
				'vigente': Vigente,
				'cortesia': EsCortesia,
			};
			console.log(miJson);
			$.post(base_url + "index.php/CanalDeVenta/grabarNuevoCanalDeVenta", {
					'prod': miJson
				}).done(function() {
				    showAppAlert('success', "Se registro Exitosamente El Nuevo Canal De Venta!", {
                        duration: 4000,
                        image: base_url + 'assets/img/success.gif'
                    });
					$('#ModalNuevoCanalDeVenta').modal('hide');
					tablaCanalVenta.ajax.reload();
				})
				.fail(function() {
			        showAppAlert('error',"Se produjo un error al realizar el proceso, favor contacte a su administrador.", {
                        duration: 5000,
                        image: base_url+ 'assets/img/errorGif.gif'
                    });
				})
				.always(function() {});
		}
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
</script>
