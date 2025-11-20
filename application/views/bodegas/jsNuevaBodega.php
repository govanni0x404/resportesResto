<!--  MODAL NUEVO REGISTRO  -->
<div id="ModalNuevaBodega" class="modal fade" role="dialog" style="overflow-y: scroll;">
	<div class="modal-dialog">
		<div class="modal-content">
			<!-- TITULO  MODAL  -->
			<div id="tituloModalUsuario" class="modal-header btn-success">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title text text-center">REGISTRO DE BODEGAS
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
								<label for="idBodega">ID</label>
								<input type="number" disabled class="form-control input-sm input-sm" name="idBodega" id="idBodega" requerido="no" value="" />
							</div>
							<div class="col-md-6 ">
								<label for="nombreBodega">NOMBRE BODEGA *</label>
								<input type="text" class="form-control input-sm input-sm" maxlength='50' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="nombreBodega" id="nombreBodega" requerido="si" value="" placeholder="Nombre Bodega" />
							</div>
						</fieldset>
						<!-- FIN CAMPOS DE REGISTRO  -->
					</div>
				</div>
			</div>
			<!-- CONTENIDO  MODAL  -->

			<!-- BOTONES  -->
			<div class="modal-footer">
				<button onclick="GrabarNuevaBodega()" type="button" class="btn btn-success"><i class="fa fa-check-square-o"></i>GRABAR</button>
				<button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-times-circle"></i>SALIR</button>
			</div>
			<!-- FIN BOTONES  -->
		</div>
	</div>
</div>
<!-- FIN  MODAL NUEVO REGISTRO  -->

<script>
	function AbrirModalNuevaBodega() {
		$('#idBodega').val("");
		$('#nombreBodega').val("");
		$('#ModalNuevaBodega').modal({
			backdrop: 'static',
			keyboard: false
		});
	}

	function GrabarNuevaBodega() {
		if ($('#nombreBodega').val() == 0) {
			swal("ERROR!", "No Ingreso El Nombre De La Bodega!", "error");
		} else {
			idBodega = $('#idBodega').val();
			NombreBodega = $('#nombreBodega').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
			var miJson = {
				'id_bodega': idBodega,
				'nombre_bodega': NombreBodega
			};
			console.log(miJson);
			$.post(base_url + "index.php/Bodegas/grabarNuevaBodega", {
					'prod': miJson
				}).done(function() {
					swal("Listo", "Se registro Exitosamente La Nueva Bodega", "success");
					$('#ModalNuevaBodega').modal('hide');
					tablaBodegas.ajax.reload();
				})
				.fail(function() {
					swal("Se produjo un error al realizar el proceso, favor contacte a su administrador", "error");
				})
				.always(function() {});
		}
	}

</script>
