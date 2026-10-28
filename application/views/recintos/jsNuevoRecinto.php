<div id="ModalNuevoRecinto" class="modal fade t-form-modal" role="dialog" style="overflow-y: scroll;">
	<div class="modal-dialog">
		<div class="modal-content">
			<!-- TITULO  MODAL  -->
			<div id="tituloModal" class="modal-header btn-success">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title text text-center">REGISTRO RECINTO
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
								<label for="idRecinto">ID</label>
								<input type="number" disabled class="form-control input-sm input-sm" name="idRecinto" id="idRecinto" requerido="no" value="">
							</div>
						</fieldset>
						<fieldset>
							<div class="col-md-12 ">
								<label for="nombreRecinto">RECINTO *</label>
								<input type="text" class="form-control input-sm input-sm" maxlength='50' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="nombreRecinto" id="nombreRecinto" requerido="si" value="" placeholder="Nombre Recinto" />
							</div>
							<div class="col-md-12">
								<label for="cboIdDestinoDeImpresion">DESTINO DE IMPRESION *</label>
								<select id="cboIdDestinoDeImpresion" nombre="cboIdDestinoDeImpresion" class="form-control input-sm" style="width:100%" requerido="si"></select>
							</div>
						</fieldset>
						<fieldset>
							<div class="col-md-6">
								<div class="btn-group" role="group" aria-label="...">
									<div class="checkbox checkbox-success">
										<br>
										<input id="ckVigente" type="checkbox">
										<label for="ckVigente">
											Vigente
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
				<button onclick="grabarNuevoRecinto()" type="button" class="btn btn-primary"><i class="fa fa-check"></i> Grabar</button>
				<button type="button" class="btn btn-default" data-dismiss="modal">Salir</button>
			</div>
			<!-- FIN BOTONES  -->
		</div>
	</div>
</div>
<script>
	function AbrirModalNuevoRecinto() {
		$('#idRecinto').val("");
		$('#nombreRecinto').val("");
		$('#ckVigente').prop('checked', true);
		ListadoDestinoDeImpresion("");
		$('#ModalNuevoRecinto').modal({
			backdrop: 'static',
			keyboard: false
		});
	}

	function grabarNuevoRecinto() {
		if ($('#nombreRecinto').val() == 0) {
			showAppAlert('error',"No Ingreso El Nombre Del Recinto!", {
            	duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
		} 
		// else if ($('#cboIdDestinoDeImpresion').val() == null) {
		// 	showAppAlert('error',"No Selecciono El Destino De Impresion!", {
        //     	duration: 5000,
        //         image: base_url+ 'assets/img/errorGif.gif'
        //     });
		else {
			IdRecinto = $('#idRecinto').val();
			NombreRecinto = $('#nombreRecinto').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
			IdDestinoDeImpresion = $('#cboIdDestinoDeImpresion').val();
			if ($('#ckVigente').is(":checked")) {
				var Vigente = 1
			} else {
				var Vigente = 0
			}
			var miJson = {
				'id_recinto': IdRecinto,
				'nombre_recinto': NombreRecinto,
				'id_dimpresion': IdDestinoDeImpresion,
				'vigente': Vigente
			};
			$.post(base_url + "index.php/Recintos/grabarNuevoRecinto", {
					'prod': miJson
				}).done(function() {
					showAppAlert('success', "Se registro Exitosamente El Nuevo Recinto!", {
                        duration: 4000,
                        image: base_url + 'assets/img/success.gif'
                    });
					$('#ModalNuevoRecinto').modal('hide');
					tablaRecinto.ajax.reload();
				})
				.fail(function() {
					showAppAlert('error',"Se produjo un error al realizar el proceso, favor contacte a su administrador", {
            			duration: 5000,
                		image: base_url+ 'assets/img/errorGif.gif'
            		});
				})
				.always(function() {});
		}
	}

	function ListadoDestinoDeImpresion() {
		$.post(base_url + "index.php/Productos/ListadoDestinoDeImpresion", {
			id: ""
		}, function(datos) {

			var obj = jQuery.parseJSON(datos);
			var tbl = '';
			var ultimoId = null;

			$.each(obj, function(index, v) {
				tbl += '<option value="' + v.id_dimpresion + '">' + v.nombre_dimpresion + '</option>';
				ultimoId = v.id_dimpresion;
			});

			$("#cboIdDestinoDeImpresion").html(tbl).select2();

			if (ultimoId !== null) {
				$("#cboIdDestinoDeImpresion").val(ultimoId).trigger('change');
			}
	});
}
</script>
