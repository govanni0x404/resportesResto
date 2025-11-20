<div id="ModalNuevoAgregado" class="modal fade" role="dialog" style="overflow-y: scroll;">
	<div class="modal-dialog">
		<div class="modal-content">
			<!-- TITULO  MODAL  -->
			<div id="tituloModal" class="modal-header btn-success">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title text text-center">REGISTRO DE AGREGADOS
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
								<label for="idAgregado">ID</label>
								<input type="number" disabled class="form-control input-sm input-sm" name="idAgregado" id="idAgregado" requerido="no" value="" />
							</div>
						</fieldset>
						<fieldset>
							<div class="col-md-12">
								<label for="cboProductoDeVentaConAgregado">PRODUCTO DE VENTA C/AGREGADO</label>
								<select id="cboProductoDeVentaConAgregado" nombre="cboProductoDeVentaConAgregado" class="form-control input-sm" style="width:100%" requerido="si"></select>
							</div>
						</fieldset>
						<fieldset>
							<div class="col-md-6">
								<label for="cboProductoAgregado">PRODUCTO DE AGREGADO</label>
								<select id="cboProductoAgregado" nombre="cboProductoAgregado" class="form-control input-sm" style="width:100%" requerido="si"></select>
							</div>

							<div class="col-md-3 ">
								<label for="cantidadAgregado">CANTIDAD</label>
								<input min="0" type="number" class="form-control input-sm input-sm" maxlength='28' onpaste="return false;" ondrop="return false;" autocomplete="off" name="cantidadAgregado" id="cantidadAgregado" requerido="si" value="" />
							</div>
							<div class="col-md-3 ">
								<label for="valorAdicionalAgregado">C. ADICIONAL</label>
								<input type="number" class="form-control input-sm input-sm" maxlength='28' onkeypress="return soloNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="valorAdicionalAgregado" id="valorAdicionalAgregado" requerido="si" value="" />
							</div>
						</fieldset>
						<fieldset>
							<div class="col-md-6">
								<div class="btn-group" role="group" aria-label="...">
									<div class="checkbox checkbox-success checkbox-circle">
										<input id="ckUsarDestinoDeImpresion" type="checkbox">
										<label for="ckUsarDestinoDeImpresion">
											USAR DESTINO DE IMPRESION
										</label>
									</div>
								</div>
							</div>

							<div class="col-md-6">
								<div class="btn-group" role="group" aria-label="...">
									<div class="checkbox checkbox-success checkbox-circle">
										<input id="ckVigenciaAgregado" type="checkbox">
										<label for="ckVigenciaAgregado">
											ACTIVO/VIGENTE
										</label>
									</div>
								</div>
							</div>

						</fieldset>
					</div>
				</div>
			</div>
			<!-- CONTENIDO  MODAL  -->

			<!-- BOTONES  -->
			<div class="modal-footer">
				<button onclick="GrabarNuevoAgregados()" type="button" class="btn btn-success"><i class="fa fa-check-square-o"></i> GRABAR AGREGADO</button>
				<button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-times-circle"></i> SALIR</button>
			</div>
			<!-- FIN BOTONES  -->
		</div>
	</div>
</div>

<script>
	function AbrirModalNuevoAgregados() {
		$('#codigoAgregado').val("");
		$('#cantidadAgregado').val("0");
		$('#valorAdicionalAgregado').val("0");
		$('#ckUsarDestinoDeImpresion').prop('checked', false);
		$('#ckVigenciaAgregado').prop('checked', true);
		ListadoProductoDeVentaConAgregado("");
		ListadoProductosCboAgregados("");
		$('#ModalNuevoAgregado').modal({
			backdrop: 'static',
			keyboard: false
		});
	}

	function GrabarNuevoAgregados() {
		ProductoMaestro = $('#cboProductoDeVentaConAgregado').val();
		ProductoAgregado = $('#cboProductoAgregado').val();
		CantidadAgregado = $('#cantidadAgregado').val();

		if (ProductoMaestro == null) {
			showAppAlert('error',"- No Selecciono El Producto De Venta Con Agregado!", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
			});
		} else if (ProductoAgregado == null) {
			showAppAlert('error',"- No Selecciono El Agregado!", {
            	duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
			});
		} else if (CantidadAgregado == 0) {
			showAppAlert('error',"- No Ingreso La Cantidad Del Agregado!", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
			});
		} else {
			//grabar datos
			IdAgregado = $('#idAgregado').val();
			ValorAdicionalAgregado = $('#valorAdicionalAgregado').val();

			if ($('#ckUsarDestinoDeImpresion').is(":checked")) {
				var usarDestinoDeImpresion = 1
			} else {
				var usarDestinoDeImpresion = 0
			}
			if ($('#ckVigenciaAgregado').is(":checked")) {
				var vigenciaAgregado = 1
			} else {
				var vigenciaAgregado = 0
			}
			var miJson = {
				'id_agregado': IdAgregado,
				'id_producto': ProductoAgregado,
				'cantidad': CantidadAgregado,
				'vigente': vigenciaAgregado,
				'id_producto_maestro': ProductoMaestro,
				'valor': ValorAdicionalAgregado,
				'usar_destino_impresion': usarDestinoDeImpresion,
			};
			$.post(base_url + "index.php/Agregados/grabarNuevoAgregado", {
					'prod': miJson
				}).done(function() {
					showAppAlert('success', "Se registro Exitosamente El Nuevo Agregado!", {
                        duration: 4000,
                        image: base_url + 'assets/img/success.gif'
                    });
					$('#ModalNuevoAgregado').modal('hide');
					tablaAgregados.ajax.reload();
				})
				.fail(function() {
					showAppAlert('error',"- Se produjo un error al realizar el proceso, favor contacte a su administrador.", {
                        duration: 5000,
                        image: base_url+ 'assets/img/errorGif.gif'
					});
				})
				.always(function() {});
		}
	}


	function ListadoProductoDeVentaConAgregado(objSelected) {
		$.post(base_url + "index.php/Productos/ListadoDeProductos", {
			id: ""
		}, function(datos) {
			var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
			tbl = '<option selected disabled value="0">SELECCIONE EL PRODUCTO</option>';
			$.each(obj, function(index, v) { // recorro el array
				if (v.id_producto == objSelected) {
					tbl = tbl + '<option selected value="' + v.id_producto + '">' + v.nombre_producto + '</option>';
				} else {
					tbl = tbl + '<option value="' + v.id_producto + '">' + v.nombre_producto + '</option>';
				}
			}); //fin each
			$("#cboProductoDeVentaConAgregado").html(tbl);
			$("#cboProductoDeVentaConAgregado").select2();
		});
	}

	function ListadoProductosCboAgregados(objSelected) {
		$.post(base_url + "index.php/Productos/ListadoDeProductos", {
			id: ""
		}, function(datos) {
			var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
			tbl = '<option selected disabled value="0">SELECCIONE EL AGREGADO</option>';
			$.each(obj, function(index, v) { // recorro el array
				if (v.id_producto == objSelected) {
					tbl = tbl + '<option selected value="' + v.id_producto + '">' + v.nombre_producto + '</option>';
				} else {
					tbl = tbl + '<option value="' + v.id_producto + '">' + v.nombre_producto + '</option>';
				}
			}); //fin each
			$("#cboProductoAgregado").html(tbl);
			$("#cboProductoAgregado").select2();
		});
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


</script>
