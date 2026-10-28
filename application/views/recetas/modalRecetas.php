<style>
	/* Formularios en modales (.t-form): filas flexibles en vez de floats + <br>, etiquetas claras y móvil a una columna */
	.t-form fieldset { display: flex; flex-wrap: wrap; align-items: flex-end; margin: 0 -8px; min-width: 0; }
	.t-form fieldset > * { flex: 0 0 100%; max-width: 100%; padding-left: 8px; padding-right: 8px; }
	.t-form fieldset > [class*="col-"] { float: none; flex: 0 0 auto; margin-bottom: 12px; }
	.t-form fieldset > .table-responsive, .t-form fieldset > .nav-tabs { margin-left: 8px; margin-right: 8px; max-width: calc(100% - 16px); padding-left: 0; padding-right: 0; }
	.t-form label { font-size: 12px; text-transform: uppercase; letter-spacing: .3px; color: var(--t-texto-suave); font-weight: 700; margin-bottom: 5px; }
	.t-form .checkbox label { text-transform: none; letter-spacing: 0; font-size: 13px; color: var(--t-texto); }
	.t-form label.t-card-titulo { display: block; margin: 4px 0 10px; padding-bottom: 6px; border-bottom: 1px solid var(--t-borde-suave); }
	.t-form .form-control.input-sm { height: 36px; font-size: 14px; }
	.t-form textarea, .t-form textarea.form-control { border: 1px solid var(--t-borde, #EADFD2); border-radius: var(--t-radio-sm); padding: 8px 10px; height: auto; }
	.t-form textarea:focus { outline: none; border-color: var(--t-primario); box-shadow: 0 0 0 3px rgba(180, 67, 46, .12); }
	.t-form sup { top: -.3em; }
	.t-form sup a { cursor: pointer; font-weight: 600; text-transform: none; color: var(--t-primario); margin-left: 4px; }
	.t-form .nav-tabs { margin-bottom: 14px; }
	.t-form .tab-pane > fieldset:first-child { margin-top: 2px; }
	.modal-footer .btn i[class=""] { display: none; }
	@media (max-width: 991px) {
		.t-form fieldset > [class*="col-md-"]:not([class*="col-sm-"]):not([class*="col-xs-"]) { width: 100%; }
		.t-form fieldset > .col-md-1:empty, .t-form fieldset > .col-md-2:empty, .t-form fieldset > .col-md-5:empty, .t-form fieldset > .col-md-8:empty { display: none; }
	}
</style>
<style>
	/* Modal de recetas: datos generales y detalle de insumos como dos tarjetas */
	#modalReceta > .modal-dialog > .modal-content > .col-md-12 { float: none; padding: 0; }
	#modalReceta .panel { box-shadow: none; margin-bottom: 14px; }
	#modalReceta .panel > .panel-body { padding: 14px 16px 4px; }
	#modalReceta .panel > .panel-body > .col-md-12 { float: none; padding: 0; }
	#modalReceta .rec-agregar { flex: 0 0 auto; }
	#modalReceta #btnAgregarInsumoTabla { width: 100%; height: 36px; padding: 0 10px; white-space: normal; line-height: 1.1;
		background: var(--t-primario); border-color: var(--t-primario); }
	#modalReceta #btnAgregarInsumoTabla:hover, #modalReceta #btnAgregarInsumoTabla:focus { background: var(--t-primario-osc); border-color: var(--t-primario-osc); }
	#modalReceta #preparacionReceta { border: 1px solid var(--t-borde, #EADFD2); border-radius: var(--t-radio-sm); padding: 10px 12px; }
	#modalReceta #preparacionReceta:focus { outline: none; border-color: var(--t-primario); box-shadow: 0 0 0 3px rgba(180, 67, 46, .12); }
	#modalReceta .modal-footer { clear: both; }
</style>
<!--  MODAL NUEVO REGISTRO  -->
<div id="modalReceta" class="modal fade" role="dialog" style="overflow-y: scroll;">
	<div class="modal-dialog modal-xl">
		<div class="modal-content">
			<!-- TITULO  MODAL  -->
			<div id="tituloModalReceta" class="modal-header btn-success">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title text text-center">REGISTRO RECETAS
				</h4>
			</div>
			<!-- FIN TITULO  MODAL  -->

			<!-- CONTENIDO  MODAL  -->
			<div class="right">
				<input type="number" class="form-control" style="display: none;" name="cant_lineas" id="cant_lineas" value="0" min="0" oninput="validity.valid||(value='');">
			</div>
			<div class="col-md-12">
				<div class="modal-body t-form">
					<div class="h-panel">
						<div class="panel panel-default">
							<div class="panel-body">
								<div class="t-card-titulo">Datos de la receta</div>
								<div>
									<!-- CAMPOS DE REGISTRO  -->
									<fieldset>
										<div class="col-md-1">
											<input disabled class="form-control input-sm input-sm" style="display: none;" name="contador" id="contador" requerido="no" value="0">
											<label for="idReceta">ID</label>
											<input type="number" disabled class="form-control input-sm input-sm" name="idReceta" id="idReceta" requerido="no" value="">
										</div>
										<div class="col-md-6">
											<label for="nombreReceta">NOMBRE RECETA</label>
											<input type="text" class="form-control input-sm input-sm" name="nombreReceta" id="nombreReceta" maxlength='50' onkeypress="return sololetrasyNumeros(event)"  onpaste="return false;" ondrop="return false;" autocomplete="off" requerido="no" value="">
										</div>
										<div class="col-md-2">
											<label for="rendimientoReceta">RENDIMIENTO</label>
											<input type="text" class="form-control input-sm input-sm" maxlength='50' onkeypress="return soloNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="rendimientoReceta" id="rendimientoReceta" requerido="si" value="" />
										</div>
										<div class="col-md-3">
											<label for="cboUnMedida">UNIDAD MEDIDA</label>
											<sup><a onclick="AbrirModalNuevaUmedida()">Nuevo</a></sup>
											<select style="width:100%;" id="cboUnMedida" nombre="cboUnMedida" class="form-control input-sm" requerido="si">
												<option value="">--Seleccione--</option>
											</select>
										</div>
										<!-- <div class="col-md-3">
											<label for="brutoSugerido">PRECIO BRUTO SUG/VEN</label>
											<input type="text" class="form-control input-sm input-sm" maxlength='50' onkeypress="return soloNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="brutoSugerido" id="brutoSugerido" requerido="si" value="" />
										</div> -->
									</fieldset>
									<fieldset>
										<div class="col-md-3">
											<label for="brutoSugerido">PRECIO BRUTO SUG/VEN</label>
											<input type="text" class="form-control input-sm input-sm" maxlength='50' onkeypress="return soloNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="brutoSugerido" id="brutoSugerido" requerido="si" value="" />
										</div>
										<!-- <div class="col-md-3">
											<label for="cboUnMedida">UNIDAD MEDIDA</label>
											<select style="width:100%;" id="cboUnMedida" nombre="cboUnMedida" class="form-control input-sm" requerido="si">
												<option value="">--Seleccione--</option>
											</select>
										</div> -->
										<div class="col-md-6">
											<label for="cboGrupoReceta">GRUPO/FAMILIA
												  <sup><a onclick="crearNuevaFamiliaReceta()">Nuevo</a></sup>
											</label>
											<select style="width:100%;" id="cboGrupoReceta" nombre="cboGrupoReceta" class="form-control input-sm" requerido="si">
												<option value="">--Seleccione--</option>
											</select>
										</div>
										<div class="col-md-3">
											<label for="cboSeccionReceta">SECCION
												<sup><a onclick="crearNuevaSeccion()">Nuevo</a></sup>
											</label>
											<select style="width:100%;" id="cboSeccionReceta" nombre="cboSeccionReceta" class="form-control input-sm" requerido="si">
												<option value="">--Seleccione--</option>
											</select>
										</div>
									</fieldset>
								</div>
							</div>
						</div>
						<div class="panel panel-default">
							<div class="panel-body">
								<div class="col-md-12">
									<ul id="myTab" class="nav nav-tabs title font">
										<li class="active" id="tabDetallesReceta">
											<a href="#tabDetalleInsumosReceta" data-toggle="tab" id="tab1" class="text color1">DETALLE DE INSUMOS DE LA RECETA</a>
										</li>
										<li class="" id="tabPreparacionRecetas">
											<a href="#tabPreparacionReceta" data-toggle="tab" id="tab2" class="text color1">PREPARACION DE LA RECETA</a>
										</li>
									</ul>
									<div class="tab-pane fade active in" id="tabDetalleInsumosReceta">
										<!-- CAMPOS DE REGISTRO  -->
										<fieldset>
											<div class="col-md-3">
												<label for="cboCodBarraInsumoReceta">COD BARRA/COD INTERNO</label>
												<select style="width:100%;" id="cboCodBarraInsumoReceta" nombre="cboCodBarraInsumoReceta" class="form-control input-sm" requerido="si">
													<option value="0">--Seleccione--</option>
												</select>
												<input type="hidden" disabled class="form-control input-sm input-sm" name="cBarraI" id="cBarraI" requerido="si" value="">

											</div>
											<div class="col-md-9">
												<label for="nombreInsumoReceta">NOMBRE INSUMO</label>
												<input type="text" disabled class="form-control input-sm input-sm" name="nombreInsumoReceta" id="nombreInsumoReceta" requerido="si" value="">
											</div>
											<!-- <div class="col-md-2">
												<label for="esSubreceta">SUBRECETA</label>
												<input type="text" disabled class="form-control input-sm input-sm" name="esSubreceta" id="esSubreceta" requerido="si" value="">
											</div> -->
										</fieldset>
										<fieldset>
											<div class="col-md-2">
												<label for="idInsumoReceta">ID INSUMO</label>
												<input type="number" disabled class="form-control input-sm input-sm" name="idInsumoReceta" id="idInsumoReceta" requerido="no" value="">
											</div>

											<div class="col-md-2">
												<label for="cantidadInsumoReceta">CANTIDAD</label>
												<input type="number" class="form-control input-sm input-sm" onkeypress="return soloNumerosDecimales(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="cantidadInsumoReceta" id="cantidadInsumoReceta" requerido="si" value="" />
											</div>
											<div class="col-md-2">
												<label for="unMedidaInsumoReceta">UN MEDIDA</label>
												<input type="text" disabled class="form-control input-sm input-sm" onkeypress="return soloNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="unMedidaInsumoReceta" id="unMedidaInsumoReceta" requerido="si" value="" />
											</div>
											<div class="col-md-2">
												<label for="costoInsumoReceta">COSTO PP UN</label>
												<input type="number" disabled class="form-control input-sm input-sm" onkeypress="return soloNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="costoInsumoReceta" id="costoInsumoReceta" requerido="si" value="" />
											</div>
											<div class="col-md-2">
												<label for="costoTotalInsumoReceta">COSTO TOTAL</label>
												<input type="number" disabled class="form-control input-sm input-sm" onkeypress="return soloNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="costoTotalInsumoReceta" id="costoTotalInsumoReceta" requerido="si" value="" />
											</div>
											<div class="col-md-2 rec-agregar">
												<button type="button" class="btn btn-rounded btn-danger" id="btnAgregarInsumoTabla"><i class="fa fa-check-circle"></i> AGREGAR INSUMO</button>
											</div>
										</fieldset>
										<fieldset>
											<div class="table-responsive" style="margin-top:10px; padding:5px;">
												<table id="tableInsumoReceta" class="display responsive" style="width: 100%;">
													<thead>
														<tr>
															<th style='display:none;' hidden scope="col">ID DETALLE</th>
															<th style='display:none;' hidden scope="col">ID INSUMO</th>
															<th scope="col">COD/BARRA</th>
															<th scope="col">NOMBRE DEL INSUMO</th>
															<th scope="col">SUBRECETA</th>
															<th scope="col">CANTIDAD</th>
															<th scope="col">UN. MEDIDA</th>
															<th scope="col">COSTO PP</th>
															<th scope="col">COSTO TOTAL</th>
															<th scope="col"></th>
														</tr>
													</thead>
												</table>
											</div>
										</fieldset>
										<!-- FIN CAMPOS DE REGISTRO  -->
									</div>
									<div class="tab-pane fade" id="tabPreparacionReceta">
										<textarea style="resize:none;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box; width:100%; height:500px;" type='text' cols="200" maxlength='10000' name="preparacionReceta" onkeypress="return sololetrasyNumeros(event)" id="preparacionReceta" rows="20"></textarea>
									</div>
								</div>
								<!-- FIN CAMPOS DE REGISTRO  -->
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- CONTENIDO  MODAL  -->

			<!-- BOTONES  -->
			<div class="modal-footer">
				<button type="button" class="btn btn-primary" id="btnGrabarInsumo"><i class=""></i> GRABAR</button>
				<button type="button" class="btn btn-default" data-dismiss="modal"><i class=""></i> SALIR</button>
			</div>
			<!-- FIN BOTONES  -->
		</div>
	</div>
</div>
<!-- FIN  MODAL NUEVO REGISTRO  -->
 <?php $this->load->view('umedida/jsNuevaUmedida.php'); ?>

<script>
	var tableInsumoReceta = $("#tableInsumoReceta").on("draw.dt", function() {
		$(this).find(".dataTables_empty").parents('tbody').empty();
	}).DataTable({
		"dom": "t"
	});

	$("#tab1").on("click", function() {
		$("#tabPreparacionReceta").hide();
		$("#tabDetalleInsumosReceta").show();
	});

	$("#tab2").on("click", function() {
		$("#tabDetalleInsumosReceta").hide();
		$("#tabPreparacionReceta").show();
	});

	$("#btnNuevaReceta").on("click", function() {
		tableInsumoReceta.clear().draw();
		
		$("#tabDetalleInsumosReceta").addClass('active');
		$('#tabDetalleInsumosReceta').addClass('in');
		$("#tabPreparacionReceta").removeClass('active');
		$('#tabPreparacionReceta').removeClass('in');

		$("#tabDetallesReceta").addClass('active');
		$('#tabDetallesReceta').addClass('in');
		$("#tabPreparacionRecetas").removeClass('active');
		$('#tabPreparacionRecetas').removeClass('in');

		$("#tabDetalleInsumosReceta").show();
		$("#tabPreparacionReceta").hide();

		$("#idReceta").val("");
		$("#nombreReceta").val("");
		$("#rendimientoReceta").val("");
		$("#brutoSugerido").val("");
		$('#esSubreceta').val("");
		$('#idInsumoReceta').val("");
		$("#nombreInsumoReceta").val("");
		$('#cantidadInsumoReceta').val("");
		$('#unMedidaInsumoReceta').val("");
		$('#costoInsumoReceta').val("");
		$('#costoTotalInsumoReceta').val("");
		$('#cBarraI').val("");
		$("#preparacionReceta").val("");
		llenarCboInsumos(0);
		llenarCboUnMedida(0);
		llenarFamiliaReceta(0);
		llenarSeccionReceta(0);
		$('#modalReceta').modal({
			backdrop: 'static',
			keyboard: false
		});
	});

	$("#cboCodBarraInsumoReceta").on('change', function(e) {
		var cBarra = $('#cboCodBarraInsumoReceta').find('option:selected').attr('cbarra');
		var id_insumo = $('#cboCodBarraInsumoReceta').find('option:selected').attr('value');
		var nombre_insumo = $('#cboCodBarraInsumoReceta').find('option:selected').attr('nombreInsumo');
		var esreceta = $('#cboCodBarraInsumoReceta').find('option:selected').attr('esReceta');
		var nombre_umedida = $('#cboCodBarraInsumoReceta').find('option:selected').attr('umedida');
		var costo_neto_ppp = $('#cboCodBarraInsumoReceta').find('option:selected').attr('costoPP');
		if (esreceta == 't') {
			esreceta = 'SI'
		} else {
			esreceta = 'NO'
		}
		if ($('#cboCodBarraInsumoReceta').val() == 0) {
			$("#nombreInsumoReceta").val("");
			$('#esSubreceta').val("");
			$('#idInsumoReceta').val("");
			$('#cantidadInsumoReceta').val("");
			$('#unMedidaInsumoReceta').val("");
			$('#costoInsumoReceta').val("");
			$('#costoTotalInsumoReceta').val("");
			$('#cBarraI').val("");
		} else {
			$('#cantidadInsumoReceta').val("");
			$('#costoTotalInsumoReceta').val("");
			$("#nombreInsumoReceta").val(nombre_insumo);
			$('#esSubreceta').val(esreceta);
			$('#idInsumoReceta').val(id_insumo);
			$('#unMedidaInsumoReceta').val(nombre_umedida);
			$('#costoInsumoReceta').val(costo_neto_ppp);
			$('#cBarraI').val(cBarra);
		}
	});

	$("#cantidadInsumoReceta").on('keyup', function() {
		var cantidad = parseFloat($("#cantidadInsumoReceta").val()) || 0;
		var costo_unitario = parseFloat($('#costoInsumoReceta').val()) || 0;

		var total = cantidad * costo_unitario;
		$('#costoTotalInsumoReceta').val(total.toFixed(2));
	});

	$("#btnAgregarInsumoTabla").on('click', function() {
		agregarInsumoReceta();
	});

// REEMPLAZA LA SECCIÓN $("#btnGrabarInsumo").on("click", function() { ... });

	$("#btnGrabarInsumo").on("click", function() {
		var id_receta = $('#idReceta').val();
		var nombre_receta = $('#nombreReceta').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
		var rendimiento = $('#rendimientoReceta').val();
		var precio_venta = $('#brutoSugerido').val();
		var id_umedida = $('#cboUnMedida').val();
		var id_familia_receta = $('#cboGrupoReceta').val();
		var id_seccion = $('#cboSeccionReceta').val();
		var preparacion = $('#preparacionReceta').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
		var msg = "";

		// Validaciones
		if ($('#nombreReceta').val() == 0) {
			showAppAlert('error', "Falta Ingresar el Nombre de la Receta", {
                duration: 6000,
                image: base_url + 'assets/img/errorGif.gif'
            });
			return;
		}
		if ($('#rendimientoReceta').val() == 0) {
			showAppAlert('error', "Falta Ingresar el Rendimiento de la Receta", {
                duration: 6000,
                image: base_url + 'assets/img/errorGif.gif'
            });
			return;
		}
		if ($('#cboUnMedida').val() == 0) {
			showAppAlert('error', "Falta Ingresar la Unidad de Medida de la Receta", {
                duration: 6000,
                image: base_url + 'assets/img/errorGif.gif'
            });
			return;
		}
		if ($('#brutoSugerido').val() == 0) {
			showAppAlert('error', "Falta Ingresar el Precio Bruto de la Receta", {
                duration: 6000,
                image: base_url + 'assets/img/errorGif.gif'
            });
			return;
		}
		if ($('#cboGrupoReceta').val() == 0) {
			showAppAlert('error', "Falta Seleccionar el Grupo/Familia de la Receta", {
                duration: 6000,
                image: base_url + 'assets/img/errorGif.gif'
            });
			return;
		}
		if ($('#cboSeccionReceta').val() == 0) {
			showAppAlert('error', "Falta Seleccionar la Seccion de la Receta", {
                duration: 6000,
                image: base_url + 'assets/img/errorGif.gif'
            });
			return;
		}
		// Preparar JSON de la receta
		var miJson = {
			'id_receta': id_receta,
			'nombre_receta': nombre_receta,
			'rendimiento': rendimiento,
			'precio_venta': precio_venta,
			'id_umedida': id_umedida,
			'id_familia_receta': id_familia_receta,
			'id_seccion': id_seccion,
			'preparacion': preparacion
		};

		// Preparar detalles de la receta
		var detalleReceta = [];
		$("#tableInsumoReceta tbody tr").each(function(index) {
			var id_detalle_receta = 0;
			var id_insumo = 0;
			var es_receta = '';
			var cantidad = 0;
			var costo_pp = 0;
			var costo_total = 0;

			$(this).children("td").each(function(index2) {
				switch (index2) {
					case 0: // id detalle
						id_detalle_receta = $(this).html();
						break;
					case 1: // id insumo
						id_insumo = $(this).html();
						break;
					case 4: // es receta
						es_receta = $(this).html();
						break;
					case 5: // cantidad
						var row = $(this);
						if (row.find('input[type="text"]').val()) {
							cantidad = row.find('input[type="text"]').val().replace(/,/g, '');
						}
						break;
					case 7: // precio un
						costo_pp = $(this).html().replace(/,/g, '');
						break;
					case 8: // total
						costo_total = $(this).html().replace(/,/g, '');
						break;
				}
			});

			// Agregar al array solo si tiene datos válidos
			if (id_insumo > 0) {
				detalleReceta.push({
					'id_detalle_receta': id_detalle_receta,
					'id_receta': id_receta,
					'id_insumo': id_insumo,
					'cantidad': cantidad,
					'costo_pp': costo_pp,
					'costo_total': costo_total,
					'es_receta': es_receta
				});
			}
		});

		console.log('Datos a enviar:', {receta: miJson, detalles: detalleReceta});

		// Enviar datos al servidor
		$.ajax({
			url: base_url + "index.php/Recetas/grabarReceta",
			type: 'POST',
			data: {
				'prod': miJson,
				'detalleReceta': detalleReceta
			},
			dataType: 'json',
			success: function(response) {
				console.log('Respuesta del servidor:', response);
				
				if (response.status === 'success') {
					// Cerrar el modal
					$('#modalReceta').modal('hide');
					
					// Recargar la tabla
					tableRecetas.ajax.reload(null, false);
					
					// Mostrar mensaje de éxito
					showAppAlert('success', response.message, {
                    	duration: 4000,
                        image: base_url + 'assets/img/success.gif'
                    });
				} else {
					showAppAlert('error', response.message || "Error al guardar la receta", {
                        duration: 6000,
                        image: base_url + 'assets/img/errorGif.gif'
                    });
				}
			},
			error: function(xhr, status, error) {
				console.error('Error AJAX:', {xhr: xhr, status: status, error: error});
				showAppAlert('error', "Se produjo un error al realizar el proceso. Detalle: " + error, {
                    duration: 6000,
                    image: base_url + 'assets/img/errorGif.gif'
                });
			}
		});
	});

	function agregarInsumoReceta() {
		var id_detalle_receta = 0;
		var cbarra = $('#cBarraI').val();
		var id_insumo = $('#idInsumoReceta').val();
		var nombre_insumo = $('#nombreInsumoReceta').val();
		var subreceta = $('#esSubreceta').val();
		var cantidad_insumo = $('#cantidadInsumoReceta').val();
		var uMedida = $('#unMedidaInsumoReceta').val();
		var costoInsumo = parseFloat($('#costoInsumoReceta').val());
		var costoTotal = 0; // se calculará en la tabla
		var errorexiste = 0;
		//RECORRE LA TABLA EN BUSCA DEL PRODUCTO EN CASO DE QUE YA ESTE, SI ESTA NO DEJA AGREGAR
		$("#tableInsumoReceta tbody tr").each(function(index) {
			$(this).children("td").each(function(index2) {
				switch (index2) {
					case 1:
						var idInsumo = $(this).html();
						if (idInsumo == id_insumo) {
							errorexiste = 1;
						}
						break;
				}
			});
		});
		//COMPROBACIONES
		if (id_insumo == 0) {
			showAppAlert('error', "Elija un insumo por favor", {
                duration: 6000,
                image: base_url + 'assets/img/errorGif.gif'
            });
		} else if (cantidad_insumo == '') {
			showAppAlert('error', "Ingrese una cantidad", {
            	duration: 6000,
                image: base_url + 'assets/img/errorGif.gif'
            });
		} else if (errorexiste == 1) {
			showAppAlert('error', "El producto ya se encuentra en la lista", {
            	duration: 6000,
                image: base_url + 'assets/img/errorGif.gif'
            });
		} else {
			rnd = $("#contador").val();
			$('#cboCodBarraInsumoReceta').val(0).trigger('change');
			$("#nombreInsumoReceta").val("");
			$('#esSubreceta').val("");
			$('#idInsumoReceta').val("");
			$('#cantidadInsumoReceta').val("");
			$('#unMedidaInsumoReceta').val("");
			$('#costoInsumoReceta').val("");
			$('#costoTotalInsumoReceta').val("");
			$('#cBarraI').val("");
			addToTable(rnd, id_detalle_receta, id_insumo, cbarra, nombre_insumo, subreceta, cantidad_insumo, uMedida, costoInsumo, costoTotal);
		}
	}

	function addToTable(rnd, id_detalle_receta, id_insumo, cbarra, nombre_insumo, subreceta, cantidad_insumo, uMedida, costoInsumo, costoTotal) {
		var tbl;
		tbl = "<tr id='" + rnd + "' style='border-width:10px;'>";
		tbl = tbl + "<td style='display:none;' vertical-align:middle;'>" + id_detalle_receta + "</td>";
		tbl = tbl + "<td style='display:none;' vertical-align:middle;'>" + id_insumo + "</td>";
		tbl = tbl + "<td class='text-left'>" + cbarra + "</td>";
		tbl = tbl + "<td class='text-left'>" + nombre_insumo + "</td>";
		tbl = tbl + "<td class='text-left'>" + subreceta + "</td>";
		tbl = tbl + "<td style='vertical-align:middle; height:50px;' class='text-left'> <a  onclick='aumentarCantidad(" + rnd + ")' href='#'  class='btn_cantidad'><i class='fa fa-plus-square' style='font-size: 18px;'></i></a>   <input style='width:70px; margin-right:4px' class='text-right' onpaste='return false;' ondrop='return false;' autocomplete='off' onkeyup='actualizarCantidadTXT(" + rnd + ")'  id='txt" + rnd + "' type='text' pattern='\d*' maxlength='5' style='width: 100px;  margin: 10px;' class='input-sm' name='' onkeypress='validate(event)' value='" + cantidad_insumo + "'><a  onclick='disminuirCantidad(" + rnd + ")' href='#' class='btn_cantidad'><i class='fa fa-minus-square' style='font-size: 18px;'></i></a></td>";
		tbl = tbl + "<td style='vertical-align:middle;' class='text-left'>" + uMedida + "</td>";
		tbl = tbl + "<td style='vertical-align:middle;' class='text-right'>" + costoInsumo + "</td>";
		tbl = tbl + "<td style='vertical-align:middle;' class='text-right montoTotal'>" + costoTotal + "</td>";
		tbl = tbl + "<td style='vertical-align:middle;' class='text-center'><a  onclick='removeRow(" + rnd + ")' href='#' class='btn btn-danger btn-icon'><i class='fa fa-trash-o'></i></a></td>";
		tbl = tbl + "</tr>";
		$('#tableInsumoReceta').append(tbl);
		$("#contador").val(parseInt(rnd + 1));
		calcularDatos(rnd);
	}

	function llenarCboUnMedida(id_umedida) {
		$.post(base_url + "index.php/Recetas/llenarCboUnMedida", {
			'id_umedida': id_umedida,
		}, function(datos) {
			var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
			tbl = "";
			tbl = "<option selected value = 0>Seleccione La Unidad De Medida</option>";
			$.each(obj, function(index, v) { // recorro el array
				if (v.id_umedida == id_umedida || v.nombre_umedida == id_umedida) {
					tbl = tbl + '<option selected value="' + v.id_umedida + '">' + v.nombre_umedida + '</option>';
				} else {
					tbl = tbl + '<option value="' + v.id_umedida + '">' + v.nombre_umedida + '</option>';
				}
			}); //fin each

			$("#cboUnMedida").html(tbl);
			$("#cboUnMedida").select2();
		});
	}

	function llenarFamiliaReceta(id_familia_receta) {
		$.post(base_url + "index.php/Recetas/llenarFamiliaReceta", {
			'id_familia_receta': id_familia_receta,
		}, function(datos) {
			var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
			tbl = "";
			tbl = "<option selected value = 0>Seleccione La Familia Receta</option>";
			$.each(obj, function(index, v) { // recorro el array
				if (v.id_familia_receta == id_familia_receta) {
					tbl = tbl + '<option selected value="' + v.id_familia_receta + '">' + v.nombre_familia_receta + '</option>';
				} else {
					tbl = tbl + '<option value="' + v.id_familia_receta + '">' + v.nombre_familia_receta + '</option>';
				}
			}); //fin each

			$("#cboGrupoReceta").html(tbl);
			$("#cboGrupoReceta").select2();
		});
	}

	function llenarSeccionReceta(id_seccion) {
		$.post(base_url + "index.php/Recetas/llenarSeccionReceta", {
			'id_seccion': id_seccion,
		}, function(datos) {
			var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
			tbl = "";
			tbl = "<option selected value = 0>Seleccione La Seccion</option>";
			$.each(obj, function(index, v) { // recorro el array
				if (v.id_seccion == id_seccion) {
					tbl = tbl + '<option selected value="' + v.id_seccion + '">' + v.nombre_seccion + '</option>';
				} else {
					tbl = tbl + '<option value="' + v.id_seccion + '">' + v.nombre_seccion + '</option>';
				}
			}); //fin each

			$("#cboSeccionReceta").html(tbl);
			$("#cboSeccionReceta").select2();
		});
	}

	function llenarCboInsumos(id_insumo) {
		$.post(base_url + "index.php/Recetas/llenarCboInsumos", {
			'id_insumo': id_insumo,
		}, function(datos) {
			var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
			tbl = "";
			tbl = "<option selected value = 0>Seleccione El Insumo</option>";
			$.each(obj, function(index, v) { // recorro el array
				if (v.id_insumo == id_insumo) {
					tbl = tbl + '<option selected value="' + v.id_insumo + '" cbarra="' + v.cbarra + '" nombreInsumo="' + v.nombre_insumo + '" esReceta="' + v.esreceta + '" umedida="' + v.nombre_umedida + '" costoPP="' + v.costo_neto_ppp + '">' + v.cbarra + '/' +  v.nombre_insumo + '</option>';
				} else {
					tbl = tbl + '<option value="' + v.id_insumo + '" cbarra="' + v.cbarra + '"  nombreInsumo="' + v.nombre_insumo + '" esReceta="' + v.esreceta + '" umedida="' + v.nombre_umedida + '" costoPP="' + v.costo_neto_ppp + '">' + v.cbarra +  '/' + v.nombre_insumo + '</option>';
				}
			}); //fin each

			$("#cboCodBarraInsumoReceta").html(tbl);
			$("#cboCodBarraInsumoReceta").select2();
		});
	}

	// Función para manejar la selección del insumo en el combobox
	$("#cboCodBarraInsumoReceta").on('change', function() {
		// Obtener la opción seleccionada
		var selectedOption = $(this).find('option:selected');
		
		// Obtener los datos de los atributos personalizados (data attributes)
		var id_insumo = selectedOption.val();
		var cbarra = selectedOption.attr('cbarra');
		var nombre_insumo = selectedOption.attr('nombreInsumo');
		var es_receta = selectedOption.attr('esReceta');
		var umedida = selectedOption.attr('umedida');
		var costoPP = selectedOption.attr('costoPP');

		// Asignar los valores a los campos de texto ocultos/visibles del modal
		if (id_insumo != 0) {
			$("#idInsumoReceta").val(id_insumo);
			$("#cBarraI").val(cbarra);
			$("#nombreInsumoReceta").val(nombre_insumo);
			$("#esSubreceta").val(es_receta === 't' ? 'SI' : 'NO'); // Asumiendo 't' es true
			$("#unMedidaInsumoReceta").val(umedida);
			$("#costoInsumoReceta").val(costoPP);
			
			// Inicializar cantidad y calcular el costo total
			$("#cantidadInsumoReceta").val(1); // Puedes poner 1 como cantidad inicial
			
			// Disparar el cálculo inicial
			$("#cantidadInsumoReceta").trigger('keyup'); 
			
		} else {
			// Limpiar campos si selecciona la opción "Seleccione El Insumo"
			$("#idInsumoReceta").val("");
			$("#cBarraI").val("");
			$("#nombreInsumoReceta").val("");
			$("#esSubreceta").val("");
			$("#unMedidaInsumoReceta").val("");
			$("#costoInsumoReceta").val("");
			$("#cantidadInsumoReceta").val("");
			$("#costoTotalInsumoReceta").val("");
		}
	});

	function sololetrasyNumeros(e, espace) {
		key = e.keyCode || e.which;
		teclado = String.fromCharCode(key).toLowerCase();
		letras = "qwertyuiopasdfghjklñzxcvbnm1234567890 ";
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

	function soloNumerosDecimales(e, espace) {
		key = e.keyCode || e.which;
		teclado = String.fromCharCode(key).toLowerCase();
		letras = "1234567890.";
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

	function removeRow(rnd) {
		swal({
				title: "Esta seguro?",
				text: "Confirma que desea quitar el insumo de la lista?",
				type: "warning",
				showCancelButton: true,
				confirmButtonColor: "#C9372C",
				cancelButtonText: "Cancelar",
				confirmButtonText: "Eliminar",
				closeOnConfirm: true
			},
			function() {
				var cant_lineas = $('#cant_lineas').val();
				$('#' + rnd).remove();
				$('#cant_lineas').val(parseInt(cant_lineas) - 1);
				showAppAlert('success', "Insumo Eliminado Correctamente", {
                	duration: 4000,
                    image: base_url + 'assets/img/success.gif'
                });
			});
	}

	function aumentarCantidad(rnd) {
		var cantidad = parseFloat($('#txt' + rnd).val());
		if (isNaN(cantidad) || cantidad == null || cantidad == "") {
			$('#txt' + rnd).val(0.1);
			cantidad = 0.1;
		}
		cantidad++;
		$('#txt' + rnd).val(cantidad);
		calcularDatos(rnd);

	}

	function disminuirCantidad(rnd) {
		var cantidad = parseFloat($('#txt' + rnd).val());
		if (isNaN(cantidad) || cantidad == null || cantidad == "") {
			$('#txt' + rnd).val(0.1);
			cantidad = 0.1;
		}
		cantidad--;
		if (cantidad < 0.1) {
			cantidad = 0.1;
		}
		$('#txt' + rnd).val(cantidad);
		calcularDatos(rnd);
	}

	function calcularDatos(rnd) {
		var costoUn = parseFloat($('#' + rnd).children('td')[7].innerHTML.replace(/,/g, ''));
		var cantidad = parseFloat($('#txt' + rnd).val());

		if (isNaN(cantidad) || cantidad == null || cantidad == "" || parseFloat(cantidad) < 0) {
			$('#txt' + rnd).val(0.1); //monto minimo
			cantidad = 0.1;
		}
		var totalLinea = costoUn * cantidad;
		$('#' + rnd).children('td')[8].innerHTML = totalLinea.toFixed(2);
	}

	function validate(evt) {
		var theEvent = evt || window.event;

		// Handle paste
		if (theEvent.type === 'paste') {
			key = event.clipboardData.getData('text/plain');
		} else {
			// Handle key press
			var key = theEvent.keyCode || theEvent.which;
			key = String.fromCharCode(key);
		}
	}

	function actualizarCantidadTXT(rnd) {
		calcularDatos(rnd);
	}
</script>
