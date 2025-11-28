<div id="ModalMensajesDelProducto" class="modal fade" role="dialog" style="overflow-y: scroll;">
	<div class="modal-dialog">
		<div class="modal-content">
			<!-- TITULO  MODAL  -->
			<div id="tituloModal" class="modal-header btn-success">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title text text-center">MENSAJES AL PRODUCTO
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
						<fieldset>
							<div class="col-md-6 ">
								<label for="idMsg">ID</label>
								<input type="number" disabled class="form-control input-sm input-sm" name="idMsg" id="idMsg" requerido="no" value="" />
							</div>
						</fieldset>
						<fieldset>
							<div class="col-md-12">
								<label for="cboProducto">PRODUCTO</label>
								<select id="cboProducto" nombre="cboProducto" class="form-control input-sm" style="width:100%" requerido="si"></select>
							</div>
						</fieldset>
						<fieldset>
							<div class="col-md-12">
								<label for="txtMsgProducto">Mnesaje del Producto</label>
								<textarea onpaste="return false;" maxlength="50" ondrop="return false;" autocomplete="off" class="form-control" rows="3" id="txtMsgProducto" placeholder="Mensaje adicional producto"></textarea>
							</div>
						</fieldset>
						<fieldset>
							<div class="col-md-6">
								<div class="btn-group" role="group" aria-label="...">
									<div class="checkbox checkbox-success checkbox-circle">
										<input id="ckVigenciaMsg" type="checkbox">
										<label for="ckVigenciaMsg">
											ACTIVO/VIGENTE
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
				<button onclick="grabarMsgProductos()" type="button" class="btn btn-primary"><i class="fa fa-check-square-o"></i>GRABAR MENSAJE</button>
				<button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times-circle"></i>SALIR</button>
			</div>
			<!-- FIN BOTONES  -->
		</div>
	</div>
</div>
<script>
	//MENSAJES DEL PRODUCTO
	function AbrirModalMensajesDelProducto() {
		$('#idMsg').val("");
		$('#txtMsgProducto').val("");
		$('#ckVigenciaMsg').prop('checked', true);
		ListadoProductos("");
		$('#ModalMensajesDelProducto').modal({
			backdrop: 'static',
			keyboard: false
		});
	}

	function grabarMsgProductos() {
		if ($('#cboProducto').val() == null) {
			showAppAlert('error',"- No Selecciono el Producto!", {
            	duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
		} else if ($('#txtMsgProducto').val() == 0) {
			showAppAlert('error',"- No Ingreso El Mensaje Del Producto!", {
            	duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
		} else {
			//grabar datos
			idMsg = $('#idMsg').val();
			productoConMsg = $('#cboProducto').val();
			MsgProducto = $('#txtMsgProducto').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
			if ($('#ckVigenciaMsg').is(":checked")) {
				var vigenciaMsg = 1
			} else {
				var vigenciaMsg = 0
			}
			var miJson = {
				'id_mensaje_producto': idMsg,
				'id_producto': productoConMsg,
				'mensaje': MsgProducto,
				'vigente': vigenciaMsg,
			};
			$.post(base_url + "index.php/MensajesPorProductos/grabarMsgProductos", {
					'prod': miJson
				}).done(function() {
					showAppAlert('success', "Se registro Exitosamente El Nuevo Mensaje!", {
                        duration: 4000,
                        image: base_url + 'assets/img/success.gif'
                    });
					$('#ModalMensajesDelProducto').modal('hide');
					tablaMsgProducto.ajax.reload();
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

	function ListadoProductos(objSelected) {
		$.post(base_url + "index.php/Productos/ListadoDeProductos", {
			id: ""
		}, function(datos) {
			var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
			tbl = '<option selected disabled value="0">SELECCIONE EL PRODUCTO</option>';
			$.each(obj, function(index, v) { // recorro el array
				if (v.nombre_producto == objSelected) {
					tbl = tbl + '<option selected value="' + v.id_producto + '">' + v.nombre_producto + '</option>';
				} else {
					tbl = tbl + '<option value="' + v.id_producto + '">' + v.nombre_producto + '</option>';
				}
			}); //fin each
			$("#cboProducto").html(tbl);
			$("#cboProducto").select2();
		});
	}
</script>
