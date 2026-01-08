<div id="ModalNuevaFamilia" class="modal fade" role="dialog" style="overflow-y: scroll;">
	<div class="modal-dialog">
		<div class="modal-content">
			<!-- TITULO  MODAL  -->
			<div id="tituloModal" class="modal-header btn-success">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title text text-center">REGISTRO FAMILIAS PRODUCTOS
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
							<div class="col-md-6 ">
								<label for="idFamilia">ID</label>
								<input type="number" disabled class="form-control input-sm input-sm" name="idFamilia" id="idFamilia" requerido="no" value="">
							</div>
						</fieldset>
						<fieldset>
							<div class="col-md-12 ">
								<label for="nombreFamilia">FAMILIA *</label>
								<input type="text" class="form-control input-sm input-sm" maxlength='50' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="nombreFamilia" id="nombreFamilia" requerido="si" value="" placeholder="Nombre Familia" />
							</div>

							<div class="col-md-12 ">
								<label for="AliasFamilia">ALIAS FAMILIA *</label>
								<input type="text" class="form-control input-sm input-sm" maxlength='50' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="AliasFamilia" id="AliasFamilia" requerido="si" value="" placeholder="Alias Familia" />
							</div>
						</fieldset>
						<fieldset>
							<div class="col-md-8">
								<label for="ordenFamilia">ORDEN FAMILIA</label>
								<input min="0" type="text" class="form-control input-sm input-sm" maxlength='4' onkeypress="return soloNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="ordenFamilia" id="ordenFamilia" requerido="si" value="" placeholder="Orden Familia" />
							</div>
							<br>
							<div class="col-md-4">
								<div class="btn-group" role="group" aria-label="...">
									<div class="checkbox checkbox-success checkbox-circle">
										<input id="ckVentaDirectaFamilia" type="checkbox">
										<label for="ckVentaDirectaFamilia">
											VENTA PUBLICO
										</label>
									</div>
								</div>
							</div>
							<div class="col-md-4">
								<div class="btn-group" role="group" aria-label="...">
									<div class="checkbox checkbox-success checkbox-circle">
										<input id="ckPiePedido" type="checkbox">
										<label for="ckPiePedido">
											IMPRIMIR PIE PEDIDO
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
				<button onclick="GrabarNuevaFamilia()" type="button" class="btn btn-primary"><i class=""></i>GRABAR</button>
				<button type="button" class="btn btn-default" data-dismiss="modal"><i class=""></i>SALIR</button>
			</div>
			<!-- FIN BOTONES  -->
		</div>
	</div>
</div>
<script>
	function AbrirModalNuevaFamilia() {
		$('#idFamilia').val("");
		$('#nombreFamilia').val("");
		$('#AliasFamilia').val("");
		$('#ckVentaDirectaFamilia').prop('checked', true);
		$('#ordenFamilia').val("0");
		$('#ckImprimirEnDetallePedido').prop('checked', false);
		$('#ModalNuevaFamilia').modal({
			backdrop: 'static',
			keyboard: false
		});
	}

	function GrabarNuevaFamilia() {
		if ($('#nombreFamilia').val().trim() === '') {
			showAppAlert('error',"No Ingreso El nombre de la Familia!", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
			return;
		} 
		
		if ($('#AliasFamilia').val().trim() === '') {
        	$('#AliasFamilia').val($('#nombreFamilia').val().trim());
    	}
		
		IdFamilia = parseInt($('#idFamilia').val())||0;
		NombreFamilia = $('#nombreFamilia').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
		AliasFamilia = $('#AliasFamilia').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
		
		if ($('#ckVentaDirectaFamilia').is(":checked")) {
			var VentaDirectaFamilia = 1
		} else {
			var VentaDirectaFamilia = 0
		}
		OrdenFamilia = parseInt($('#ordenFamilia').val())||0;
		if ($('#ckPiePedido').is(":checked")) {
			var piePedido = 1
		} else {
			var piePedido = 0
		}
		var miJson = {
			'id_familia': IdFamilia,
			'nombre_familia': NombreFamilia,
			'venta_directa': VentaDirectaFamilia,
			'orden_familia': OrdenFamilia,
			'imprimir_en_detalle_pedido': piePedido,
			'alias': AliasFamilia,
		};
		console.log(miJson);
		$.post(base_url + "index.php/Familias/grabarNuevaFamilia", {
				'prod': miJson
			}).done(function() {
				showAppAlert('success', "Se registro Exitosamente La Nueva Familia!", {
                       duration: 4000,
                       image: base_url + 'assets/img/success.gif'
                   });
				$('#ModalNuevaFamilia').modal('hide');
				tablaFamilias.ajax.reload();
			})
			.fail(function() {
				showAppAlert('error',"Se produjo un error al realizar el proceso, favor contacte a su administrador!", {
               		duration: 5000,
           	 		image: base_url+ 'assets/img/errorGif.gif'
           		});
			})
			.always(function() {});
	}
</script>
