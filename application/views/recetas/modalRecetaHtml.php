<?php /* v1.39.6 (pedido del usuario): la ventana de receta (estilos, HTML y el reparto a lo ancho) en un solo archivo, que usan
   Recetas (recetas/modalRecetas.php) y la Carta (productos/modals.php). Antes la Carta tenía una copia vieja y no le llegaban
   los cambios. El código de cada pantalla sigue en su archivo (recetas/modalRecetas.php y productos/jsReceta.php). */ ?>
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
	/* v1.37.15 (pedido del usuario): a lo ancho. Izquierda: datos de la receta y agregar insumo; derecha: la tabla y el resumen */
	#modalReceta .modal-dialog { width: 1440px; max-width: calc(100vw - 24px); margin-top: 14px; }
	#modalReceta .h-panel.rec-ancho { display: grid; grid-template-columns: minmax(520px, 6fr) 7fr; gap: 16px; align-items: start; }
	#modalReceta .rec-ancho > .rec-izq > .panel, #modalReceta .rec-ancho > .panel { margin-bottom: 12px; }
	#modalReceta .rec-izq .panel > .panel-body { padding: 12px 14px 2px; }
	#modalReceta .rec-izq fieldset > [class*="col-"] { flex: 1 1 28%; width: auto; max-width: none; margin-bottom: 10px; }
	/* Datos: nombre solo; rendimiento + unidad; precio + grupo + sección */
	#modalReceta .rec-izq fieldset > .col-md-7 { flex-basis: 100%; }
	#modalReceta .rec-izq fieldset > .col-md-2:has(#rendimientoReceta) { flex: 1 1 30%; }
	#modalReceta .rec-izq fieldset > .col-md-3:has(#cboUnMedida) { flex: 1 1 60%; }
	/* Agregar insumo: código + nombre; cantidad, unidad, costo y total en una fila; el botón abajo */
	#modalReceta .rec-izq fieldset > .col-md-3:has(#cboCodBarraInsumoReceta) { flex: 1 1 48%; }
	#modalReceta .rec-izq fieldset > .col-md-9:has(#nombreInsumoReceta) { flex: 1 1 42%; }
	#modalReceta .rec-izq fieldset > .col-md-2:has(#idInsumoReceta) { display: none; }
	#modalReceta .rec-izq fieldset > .col-md-2 { flex: 1 1 20%; }
	#modalReceta .rec-izq fieldset > .rec-agregar { flex-basis: 100%; }
	#modalReceta .rec-ancho #tabDetalleInsumosReceta .table-responsive { max-height: calc(100vh - 380px); overflow-y: auto; }
	#modalReceta #tableInsumoReceta td:nth-child(6) { white-space: nowrap; }
	@media (max-width: 1100px) { #modalReceta .h-panel.rec-ancho { grid-template-columns: 1fr; } }
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
										<div class="col-md-1" style="display:none" data-id-auto="1">
											<input disabled class="form-control input-sm input-sm" style="display: none;" name="contador" id="contador" requerido="no" value="0">
											<label for="idReceta">ID</label>
											<input type="number" disabled class="form-control input-sm input-sm" name="idReceta" id="idReceta" requerido="no" value="">
										</div>
										<div class="col-md-7">
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
												<div id="equivLineaReceta" class="rec-equiv" hidden></div>
											</div>
											<div class="col-md-2">
												<!-- v1.34.0: se puede escribir en kilos o gramos (se guarda en la unidad de receta del insumo) -->
												<label for="unidadLineaReceta">UN MEDIDA</label>
												<select id="unidadLineaReceta" class="form-control input-sm" disabled></select>
												<input type="hidden" id="unMedidaInsumoReceta" value="" />
											</div>
											<div class="col-md-2">
												<label for="costoInsumoReceta" title="Costo neto de 1 unidad de receta (ej. 1 gramo)">COSTO X UN. RECETA</label>
												<input type="text" disabled class="form-control input-sm input-sm" onkeypress="return soloNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="costoInsumoReceta" id="costoInsumoReceta" requerido="si" value="" />
											</div>
											<div class="col-md-2">
												<label for="costoTotalInsumoReceta">COSTO TOTAL</label>
												<input type="text" disabled class="form-control input-sm input-sm" onkeypress="return soloNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="costoTotalInsumoReceta" id="costoTotalInsumoReceta" requerido="si" value="" />
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
<script>
	// v1.37.15: reparte la ventana a lo ancho moviendo los mismos elementos (no cambian sus ids ni lo que hacen)
	$(function () {
		var $hp = $('#modalReceta .h-panel').first();
		if (!$hp.length || $hp.hasClass('rec-ancho')) return;
		var $paneles = $hp.children('.panel');
		if ($paneles.length < 2) return;
		var $form = $('#tabDetalleInsumosReceta').children('fieldset').slice(0, 2);
		var $card = $('<div class="panel panel-default"><div class="panel-body"><div class="t-card-titulo">Agregar insumo</div></div></div>');
		$card.children('.panel-body').append($form);
		$('<div class="rec-izq"></div>').append($paneles.eq(0)).append($card).prependTo($hp);
		$hp.addClass('rec-ancho');
	});
</script>
