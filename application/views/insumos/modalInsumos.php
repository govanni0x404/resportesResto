<!--  MODAL NUEVO REGISTRO  -->
<div id="modalInsumo" class="modal fade" role="dialog" style="overflow-y: scroll;">
	<div class="modal-dialog modal-xl">
		<div class="modal-content">
			<!-- TITULO  MODAL  -->
			<div id="tituloModalInsumo" class="modal-header btn-success">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title text text-center">REGISTRO INSUMOS
				</h4>
			</div>
			<!-- FIN TITULO  MODAL  -->

			<!-- CONTENIDO  MODAL  -->

				<div class="modal-body">
					<div class="h-panel">
						<div class="row">
							<div class="col-md-5">
							<br>
							<ul id="myTab" class="nav nav-tabs title font">
								<li class="active">
									<a href="#tabDatosInsumo" data-toggle="tab" id="tab1" class="text color1">INSUMO</a>
								</li>
							</ul>
							<div class="tab-pane fade active in" id="tabDatosInsumo">
								<br>
								<!-- CAMPOS DE REGISTRO  -->
								<fieldset>
									<div class="col-md-4">
										<label for="idInsumo">ID</label>
										<input type="number" disabled class="form-control input-sm input-sm" name="idInsumo" id="idInsumo" requerido="no" value="">
									</div>
									<div class="col-md-8">
										<label for="codBarra">CODIGO INTERNO</label>
										<input type="text" class="form-control input-sm input-sm" name="codBarra" id="codBarra" maxlength='50' onkeypress="return sololetrasyNumeros(event)" requerido="no" value="">
									</div>
								</fieldset>
								<br>
								<fieldset>
									<div class="col-md-12">
										<label for="nombreInsumo">NOMBRE INSUMO *</label>
										<input type="text" class="form-control input-sm input-sm" maxlength='50' onkeypress="return sololetrasyNumeros(event)" onpaste="return false;" ondrop="return false;" autocomplete="off" name="nombreInsumo" id="nombreInsumo" requerido="si" value="" placeholder="Nombre Insumo" />
									</div>
								</fieldset>
								<br>
								<fieldset>
									<div class="col-md-12">
										<label for="cboGrupoInsumo">FAMILIA/GRUPO INSUMO *</label>
										<select style="width:100%;" id="cboGrupoInsumo" nombre="cboGrupoInsumo" class="form-control input-sm" requerido="si">
											<option value="">--Seleccione--</option>
										</select>
									</div>
								</fieldset>
								<br>
								<fieldset>
									<div class="col-md-6">
										<label for="cboIva">IVA *</label>
										<select style="width:100%;" id="cboIva" nombre="cboIva" class="form-control input-sm" requerido="si">
											<option value="">--Seleccione--</option>
										</select>
									</div>
									<div class="col-md-6">
										<label for="cboImptoAdicional">IMP. ADICIONAL *</label>
										<select style="width:100%;" id="cboImptoAdicional" nombre="cboImptoAdicional" class="form-control input-sm" requerido="si">
											<option value="">--Seleccione--</option>
										</select>
									</div>
								</fieldset>
								<!-- FIN CAMPOS DE REGISTRO  -->
							</div>
							</div>
							<div><br></div>
							<div class="col-md-7">
							<ul id="myTab2" class="nav nav-tabs title font">
								<li id="tabUmedida" class="active">
									<a href="#tabUnidadMedidaInsumo" data-toggle="tab" id="tab2" class="text color1">U/MEDIDA</a>
								</li>
								<li id="tabStocks">
									<a href="#tabStock" data-toggle="tab" id="tab3" class="text color1">STOCK</a>
								</li>
								<li id="tabCostos">
									<a href="#tabCosto" data-toggle="tab" id="tab4" class="text color1">COSTO</a>
								</li>
								<li id="tabProd">
									<a href="#tabProduccion" data-toggle="tab" id="tab5" class="text color1">PRODUCCION</a>
								</li>
								<li id="tabReceta">
									<a href="#tabRecetas" data-toggle="tab" id="tab6" class="text color1">RECETAS</a>
								</li>
								<li id="tabObs">
									<a href="#tabObservaciones" data-toggle="tab" id="tab7" class="text color1">OBSERVACIONES</a>
								</li>
							</ul>
							<div class="tab-pane fade active in" id="tabUnidadMedidaInsumo">
								<br>
								<fieldset>
									<div class="col-md-12">
										<label>Unidades de medida compra e inventario</label>
									</div>
								</fieldset>
								<fieldset>
									<div class="col-md-6">
										<label for="medInventario">MEDIDA INVENTARIO/COMPRA (EJ. 1) *</label>
										<input type="number" class="form-control input-sm input-sm" name="medInventario" onkeypress="return soloNumerosDecimales(event)" id="medInventario" requerido="no" value="">
									</div>
									<div class="col-md-6">
										<label for="cboUnMedInventario">U. MEDIDA INVENTARIO/COMPRA *</label>
										<select style="width:100%;" id="cboUnMedInventario" nombre="cboUnMedInventario" class="form-control input-sm" requerido="si">
											<option value="">--Seleccione--</option>
										</select>
									</div>
								</fieldset>
								<br>
								<fieldset>
									<div class="col-md-6">
										<label for="medReceta">MEDIDA RECETA (EJ. 1000) *</label>
										<input type="number" class="form-control input-sm input-sm" name="medReceta" onkeypress="return soloNumerosDecimales(event)" id="medReceta" requerido="no" value="">
									</div>
									<div class="col-md-6">
										<label for="cboUnMedReceta">U. MEDIDA RECETA *</label>
										<select style="width:100%;" id="cboUnMedReceta" nombre="cboUnMedReceta" class="form-control input-sm" requerido="si">
											<option value="">--Seleccione--</option>
										</select>
									</div>
								</fieldset>
							</div>
							<div class="tab-pane fade" id="tabStock">
								<br>
								<fieldset>
									<div class="col-md-4">
										<label>Stock por Bodega</label>
									</div>
									<div class="col-md-2"></div>
									<div class="col-md-6" style="height:25px; padding:0px; margin: 0px;">
										<div class="checkbox checkbox-primary" style="height:25px; padding:0px, 0px, 0px,15px; margin:0px;">
											<input id="ckInvCierre" type="checkbox">
											<label for="ckInvCierre">
												INVENTARIO EN CIERRE DE TURNO
											</label>
										</div>
									</div>
								</fieldset>
								<fieldset>
									<div class="table-responsive" style="margin-top:10px; padding:5px;">
										<table id="tableStockInsumo" class="display responsive" style="width: 100%">
											<thead>
												<tr>
													<th class="text text-center">INSUMO</th>
													<th class="text text-left">BODEGA</th>
													<th class="text text-right">STOCK</th>
													<th class="text text-left">U/MED. INV.</th>
													<th class="text text-center"></th>
												</tr>
											</thead>
										</table>
									</div>
								</fieldset>
							</div>
							<div class="tab-pane fade" id="tabCosto">
								<br>
								<fieldset>
									<div class="col-md-12">
										<label>Costo Compras</label>
									</div>
								</fieldset>
								<fieldset>
									<div class="col-md-4">
										<label for="costoReceta">C. NETO + I. ADIC (C. RECETA)</label>
										<input type="number" class="form-control input-sm input-sm" name="costoReceta" onkeypress="return soloNumeros(event)" id="costoReceta" requerido="no" value="">
									</div>
									<div class="col-md-4">
										<label for="costoBruto">COSTO BRUTO PP (INFORM)</label>
										<input type="number" class="form-control input-sm input-sm" name="costoBruto" onkeypress="return soloNumeros(event)" id="costoBruto" requerido="no" value="">
									</div>
									<div class="col-md-4">
										<label for="costoUltCompra">C. ULT. COMPRA</label>
										<input type="number" class="form-control input-sm input-sm" name="costoUltCompra" onkeypress="return soloNumeros(event)" id="costoUltCompra" requerido="no" value="">
									</div>
								</fieldset>
								<br>
								<fieldset>
									<div class="col-md-8"></div>
									<div class="col-md-4">
										<button type="button" class="btn btn-primary" id="btnHistoricoCompras">HISTORICO DE COMPRAS</button>
									</div>
								</fieldset>
							</div>
							<div class="tab-pane fade" id="tabProduccion">
								<br>
								<fieldset>
									<div class="col-md-9">
										<label>Receta de producción</label>
										<button type="button" class="btn btn-sm" 
												id="btnCrearRecetaProduccion" 
												title="Crear nueva receta de producción" style="color: #568c30; text-decoration: underline;
												background: none; border: none; background-color: transparent;">
											<i class=""></i> Nueva
										</button>
									</div>
								</fieldset>
								<fieldset>
									<div class="col-md-6">
										<select style="width:100%;" id="cboMedReceta" nombre="cboMedReceta" class="form-control input-sm" requerido="si">
											<option value="">--Seleccione--</option>
										</select>
									</div>
									<div class="col-md-6">
										<button type="button" class="btn btn-danger btn-sm" id="btnRealizarProduccion">
											<i class="fa fa-cogs"></i> REALIZAR PRODUCCION
										</button>
									</div>
								</fieldset>
								<br>
								<fieldset>
									<ul id="myTab2" class="nav nav-tabs title font">
										<li id="tabUmStockInsumosRecetas" class="active">
											<a href="#tabUmMedidaReceta" data-toggle="tab" id="tab8" class="text color1">VER INSUMOS</a>
										</li>
										<li id="tabStockInsumoRecetas" style="height:5px;">
											<a href="#tabStockInsumoReceta" data-toggle="tab" id="tab9" class="text color1">VER PRODUCCION</a>
										</li>
									</ul>
									<div class="tab-pane fade active in" id="tabUmMedidaReceta">
										<div class="table-responsive" style="margin-top:10px; padding:5px;">
											<table id="tableUmMedidaReceta" class="display responsive" style="width: 100%">
												<thead>
													<tr>
														<th class="text text-center">INSUMO</th>
														<th class="text text-center">CANTIDAD</th>
														<th class="text text-center">U/MEDIDA</th>
														<th class="text text-center"></th>
													</tr>
												</thead>
											</table>
										</div>
									</div>
									<div class="tab-pane fade" id="tabStockInsumoReceta">
										<div class="table-responsive" style="margin-top:10px; padding:5px;">
											<table id="tableStockInsumoReceta" class="display responsive" style="width: 100%">
												<thead>
													<tr>
														<th class="text text-center">RENDIMIENTO</th>
														<th class="text text-center">U/MEDIDA</th>
														<th class="text text-center">COSTO UN</th>
														<th class="text text-center">COSTO TOTAL</th>
														<th class="text text-center">FECHA PROCESO</th>
														<th class="text text-center">FECHA/HORA INGRESO</th>
														<th class="text text-center"></th>
													</tr>
												</thead>
											</table>
										</div>
									</div>
								</fieldset>
							</div>

							<div class="tab-pane fade" id="tabRecetas">
								<br>
								<fieldset>
									<div class="col-md-12">
										<label>Listado de recetas que contienen este insumo</label>
									</div>
								</fieldset>
								<fieldset>
									<div class="table-responsive" style="margin-top:10px; padding:5px;">
										<table id="tableRecetasInsumo" class="display responsive" style="width: 100%">
											<thead>
												<tr>
													<th class="text text-left">RECETA</th>
													<th class="text text-right">CANTIDAD</th>
													<th class="text text-left">U/MEDIDA</th>
													<th class="text text-center"></th>
												</tr>
											</thead>
										</table>
									</div>
								</fieldset>
							</div>
							<div class="tab-pane fade" id="tabObservaciones">
								<br>
								<fieldset>
									<div class="col-md-12">
										<label>Observaciones</label>
									</div>
								</fieldset>
								<fieldset>
									<div class="col-md-12">
										<textarea style="resize:none;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box; width:100%; height:100px;" type='text' cols="40" maxlength='250' name="obsInsumo" onkeypress="return sololetrasyNumeros(event)" id="obsInsumo" rows="4"></textarea>
									</div>
								</fieldset>
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




<!--  MODAL NUEVO REGISTRO  -->
<div id="modalHistoricoCompras" class="modal fade" role="dialog" style="overflow-y: scroll;">
	<div class="modal-dialog modal-xl">
		<div class="modal-content">
			<!-- TITULO  MODAL  -->
			<div id="titulomodalHistoricoCompras" class="modal-header btn-success">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title text text-center">HISTORICO COMPRAS POR INSUMO
				</h4>
			</div>
			<!-- FIN TITULO  MODAL  -->

			<!-- CONTENIDO  MODAL  -->
			<div class="col-md-12">
				<fieldset>
					<div class="table-responsive" style="margin-top:10px; padding:5px;">
						<table id="tableHistoricoCompras" class="display responsive" style="width: 100%">
							<thead>
								<tr>
									<th class="text text-left">FECHA COMPRA</th>
									<th class="text text-left">NOMBRE PROVEEDOR</th>
									<th class="text text-center">CANTIDAD</th>
									<th class="text text-center">BRUTO UN.</th>
									<th class="text text-left">TIPO DOCUMENTO</th>
									<th class="text text-center">FOLIO DOC.</th>
									<th class="text text-center"></th>
									<th class="text text-center"></th>
								</tr>
							</thead>
						</table>
					</div>
				</fieldset>
			</div>
			<!-- CONTENIDO  MODAL  -->

			<!-- BOTONES  -->
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"><i class=""></i> SALIR</button>
			</div>
			<!-- FIN BOTONES  -->
		</div>
	</div>
</div>
<!-- FIN  MODAL NUEVO REGISTRO  -->

<!-- MODAL NUEVA RECETA DE PRODUCCION -->
<div id="ModalNuevaRecetaProduccion" class="modal fade" role="dialog" style="overflow-y: scroll;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- TITULO MODAL -->
            <div class="modal-header btn-success">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title text-center">REGISTRO DE RECETA DE PRODUCCIÓN</h4>
            </div>

            <!-- CONTENIDO MODAL -->
            <div class="modal-body">
                <div class="h-panel">
                    <!-- CAMPOS DE REGISTRO -->
                    <div class="row">
                        <div class="col-md-12">
                            <fieldset>
                                <div class="col-md-4">
                                    <label for="idRecetaProduccion">ID</label>
                                    <input type="number" disabled class="form-control input-sm" 
                                           name="idRecetaProduccion" id="idRecetaProduccion" value="" />
                                </div>
                            </fieldset>
                        </div>
                    </div>                    
                    <div class="row">
                        <div class="col-md-12">
                            <fieldset>
                                <div class="col-md-8">
                                    <label for="nombreRecetaProduccion">NOMBRE DE LA RECETA *</label>
                                    <input type="text" class="form-control input-sm" maxlength='50' 
                                           onkeypress="return sololetrasyNumeros(event)" 
                                           onpaste="return false;" ondrop="return false;" 
                                           autocomplete="off" name="nombreRecetaProduccion" 
                                           id="nombreRecetaProduccion" requerido="si" value="" 
                                           placeholder="Nombre de la Receta" />
                                </div>
								<div class="col-md-3">
                                    <div class="checkbox checkbox-success">
										<br>
                                            <input type="checkbox" id="ckVigenteReceta">
                                        <label for='ckVigenteReceta'>
											Vigente
										</label>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <fieldset>
                                <div class="col-md-4">
                                    <label for="cboInsumoElaborado">INSUMO ELABORADO *</label>
                                    <select id="cboInsumoElaborado" name="cboInsumoElaborado" 
                                            class="form-control input-sm" style="width:100%" requerido="si">
                                        <option value="0">-- Seleccione un Insumo --</option>
                                    </select>
                                </div>
								<div class="col-md-4">
                                    <label for="rendimientoReceta">RENDIMIENTO *</label>
                                    <input type="number" class="form-control input-sm" 
                                           onkeypress="return soloNumerosDecimales(event)" 
                                           name="rendimientoReceta" id="rendimientoReceta" 
                                           requerido="si" value="" placeholder="Ej: 1" />
                                </div>
                                <div class="col-md-4">
                                    <label for="cboUnidadMedidaReceta">U/MEDIDA INVENTARIO *</label>
                                    <select id="cboUnidadMedidaReceta" name="cboUnidadMedidaReceta" 
                                            class="form-control input-sm" style="width:100%" requerido="si">
                                        <option value="0">-- Seleccione U/Medida --</option>
                                    </select>
                                </div>
                            </fieldset>
                        </div>
                    </div>
                    <br>
                    
                    <!-- BOTON VER/AGREGAR INSUMOS -->
                    <!-- <div class="row">
                        <div class="col-md-12 text-center">
                            <button type="button" class="btn btn-info" id="btnVerAgregarInsumos">
                                <i class="fa fa-list"></i> Ver/Agregar Insumos
                            </button>
                        </div>
                    </div> -->
                    
                    <br>
                    
                    <!-- TABLA DE INSUMOS -->
                    <div class="row">
                        <div class="col-md-12">
                            <fieldset>
                                <div class="col-md-12">
                                    <div class="table-responsive">
                                        <table id="tableInsumosReceta" class="table table-bordered table-hover table-striped">
                                            <thead class="btn-success">
                                                <tr>
                                                    <th class="text-center">ID</th>
                                                    <th class="text-center">Nombre de la receta</th>
                                                    <th class="text-center">Insumo</th>
                                                    <th class="text-center">Cantidad</th>
                                                    <th class="text-center">U/Medida Inv.</th>
                                                </tr>
                                            </thead>
                                            <tbody id="detalleInsumosReceta">
                                                <!-- Las filas se llenarán dinámicamente -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                    </div>
                    
                    <!-- SECCION PARA AGREGAR INSUMOS (OCULTA INICIALMENTE) -->
                    <div id="seccionAgregarInsumos" style="display:none; margin-top: 20px;">
                        <div class="row">
                            <div class="col-md-12">
                                <h5><strong>Agregar Insumo a la Receta</strong></h5>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <fieldset>
                                    <div class="col-md-5">
                                        <label for="cboInsumoDetalle">Insumo</label>
                                        <select id="cboInsumoDetalle" name="cboInsumoDetalle" 
                                                class="form-control input-sm" style="width:100%">
                                            <option value="0">-- Seleccione --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="cantidadInsumoDetalle">Cantidad</label>
                                        <input type="number" class="form-control input-sm" 
                                               onkeypress="return soloNumerosDecimales(event)" 
                                               name="cantidadInsumoDetalle" id="cantidadInsumoDetalle" 
                                               value="" placeholder="0" />
                                    </div>
                                    <div class="col-md-3">
                                        <label for="cboUmedidaDetalle">U/Medida</label>
                                        <select id="cboUmedidaDetalle" name="cboUmedidaDetalle" 
                                                class="form-control input-sm" style="width:100%">
                                            <option value="0">-- Seleccione --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-1">
                                        <label style="visibility: hidden;">+</label>
                                        <button type="button" class="btn btn-success btn-sm btn-block" 
                                                id="btnAgregarInsumoDetalle">
                                            <i class="fa fa-plus"></i>
                                        </button>
                                    </div>
                                </fieldset>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- FIN CONTENIDO MODAL -->

            <!-- BOTONES -->
            <div class="modal-footer">
                <button onclick="grabarNuevaRecetaProduccion()" type="button" class="btn btn-primary">
                    <i class=""></i> GRABAR
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class=""></i> SALIR
                </button>
            </div>
            <!-- FIN BOTONES -->
        </div>
    </div>
</div>
<!-- FIN MODAL NUEVA RECETA DE PRODUCCION -->

<script>
	$("#btnNuevoInsumo").on("click", function() {
		tabsInsumos();
		$("#idInsumo").val("");
		$("#codBarra").val("");
		$("#nombreInsumo").val("");
		$("#cboGrupoInsumo").val(0);
		$("#cboIva").val(0);
		$("#cboImptoAdicional").val(0);
		$("#medInventario").val(0);
		$("#cboUnMedInventario").val(0);
		$("#medReceta").val(0);
		$("#cboMedReceta").val(0);
		$("#costoReceta").val(0);
		$("#costoBruto").val(0);
		$("#costoUltCompra").val(0);
		$("#obsInsumo").val("");
		$("#cboMedReceta").val(0);
		$("#tabReceta").hide();
		$("#tabProd").hide();
		$("#tabStocks").hide();
		llenarGrupoInsumos();
		llenarImpto();
		llenarUnMedida();
	});

	function llenarGrupoInsumos(objSelected, id_grupo_insumo) {
		$.post(base_url + "index.php/Insumos/llenarCboGrupoInsumo", {
			id_grupo_insumo: id_grupo_insumo
		}, function(datos) {
			var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
			tbl = "";
			tbl = '<option selected value="0">--SELECCIONE--</option>';
			$.each(obj, function(index, v) { // recorro el array
				if (v.nombre_grupo_insumo == objSelected) {
					tbl = tbl + '<option selected value="' + v.id_grupo_insumo + '">' + v.nombre_grupo_insumo + '</option>';
				} else {
					tbl = tbl + '<option value="' + v.id_grupo_insumo + '">' + v.nombre_grupo_insumo + '</option>';
				}
			}); //fin each
			$("#cboGrupoInsumo").html(tbl);
			$("#cboGrupoInsumo").select2();
		});
	}

	function llenarImpto(objSelectedIva, objSelectedAdicional, id_impuesto) {

		$.post(base_url + "index.php/Insumos/llenarCboImpto", {
			id_impuesto: id_impuesto
		}, function(datos) {
			var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
			tblIva = "";
			tblIva = '<option selected value="0">--SELECCIONE--</option>';
			tblAdicional = "";
			tblAdicional = '<option selected value="0">--SELECCIONE--</option>';
			$.each(obj, function(index, v) { // recorro el array
				if (v.nombre_impuesto == objSelectedIva) {
					tblIva = tblIva + '<option selected value="' + v.id_impuesto + '">' + v.nombre_impuesto + '</option>';
				} else {
					tblIva = tblIva + '<option value="' + v.id_impuesto + '">' + v.nombre_impuesto + '</option>';
				}
				if (v.nombre_impuesto == objSelectedAdicional) {
					tblAdicional = tblAdicional + '<option selected value="' + v.id_impuesto + '">' + v.nombre_impuesto + '</option>';
				} else {
					tblAdicional = tblAdicional + '<option value="' + v.id_impuesto + '">' + v.nombre_impuesto + '</option>';
				}
			}); //fin each
			$("#cboIva").html(tblIva);
			$("#cboIva").select2();
			$("#cboImptoAdicional").html(tblAdicional);
			$("#cboImptoAdicional").select2();
		});
	}

	function llenarUnMedida(objSelectedInventario, objSelectedReceta, id_umedida) {
		$.post(base_url + "index.php/Insumos/llenarCboUnMedida", {
			id_umedida: id_umedida
		}, function(datos) {
			var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
			tblInventario = "";
			tblInventario = '<option selected value="0">--SELECCIONE--</option>';
			tblReceta = "";
			tblReceta = '<option selected value="0">--SELECCIONE--</option>';
			$.each(obj, function(index, v) { // recorro el array
				if (v.nombre_umedida == objSelectedInventario) {
					tblInventario = tblInventario + '<option selected value="' + v.id_umedida + '" nombreUm="' + v.nombre_umedida + '">' + v.nombre_umedida + '</option>';
				} else {
					tblInventario = tblInventario + '<option value="' + v.id_umedida + '" nombreUm="' + v.nombre_umedida + '">' + v.nombre_umedida + '</option>';
				}
				if (v.nombre_umedida == objSelectedReceta) {
					tblReceta = tblReceta + '<option selected value="' + v.id_umedida + '">' + v.nombre_umedida + '</option>';
				} else {
					tblReceta = tblReceta + '<option value="' + v.id_umedida + '">' + v.nombre_umedida + '</option>';
				}
			}); //fin each

			$("#cboUnMedInventario").html(tblInventario);
			$("#cboUnMedInventario").select2();
			$("#cboUnMedReceta").html(tblReceta);
			$("#cboUnMedReceta").select2();
		});
	}

	function llenarRecetaProduccion(id_insumo) {
		$.post(base_url + "index.php/Insumos/llenarCboRecetaProduccion", {
			id_insumo: id_insumo
		}, function(datos) {
			var obj = jQuery.parseJSON(datos); // lo convierto a un objeto a json
			tbl = "";
			$.each(obj, function(index, v) { // recorro el array
				if (v.id_insumo == id_insumo) {
					tbl = tbl + '<option selected value="' + v.id_receta_produccion + '" nombreUm="' + v.nombre_receta_produccion + '">' + v.nombre_receta_produccion + '</option>';
				} else {
					tbl = tbl + '<option value="' + v.id_receta_produccion + '" nombreUm="' + v.nombre_receta_produccion + '">' + v.nombre_receta_produccion + '</option>';
				}
			}); //fin each

			$("#cboMedReceta").html(tbl);
			$("#cboMedReceta").select2();
		});
	}

	$("#btnGrabarInsumo").on("click", function() {
		id_insumo = $('#idInsumo').val();
		cbarra = $('#codBarra').val();
		nombre_insumo = $('#nombreInsumo').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
		id_grupo_insumo = $('#cboGrupoInsumo').val();
		id_impto_iva = $('#cboIva').val();
		id_impto_adicional = $('#cboImptoAdicional').val();
		umedida_inventario = $('#medInventario').val();
		id_umedida_inventario = $('#cboUnMedInventario').val();
		umedida_receta = $('#medReceta').val();
		id_umedida_receta = $('#cboUnMedReceta').val();
		costo_neto_ppp = $("#costoReceta").val();
		costo_bruto_ppp = $("#costoBruto").val();
		precio_ucompra = $("#costoUltCompra").val();
		observaciones = $("#obsInsumo").val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
		inv_cierre = ($('#ckInvCierre').is(":checked") ? 1 : 0);
		var miJson = {};
		var msg = "";

		console.log(inv_cierre);
		if ($('#nombreInsumo').val() === '') {
			showAppAlert('error',"Falta Ingresar el nombre del Insumo", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
			return;
		}
		if ($('#cboGrupoInsumo').val() == 0) {
			showAppAlert('error',"Falta Seleccionar la Familia/Grupo del Insumo", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
			return;
		}
		if ($('#cboIva').val() == 0) {
			showAppAlert('error',"Falta Seleccionar el iva del Insumo", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
			return;
		}
		if ($('#cboImptoAdicional').val() == 0) {
			showAppAlert('error',"Falta Seleccionar el Impuesto Adicional del Insumo", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
			return;
		}
		if ($('#medInventario').val() == 0) {
			showAppAlert('error',"Falta Ingresar la medida de Inventario del Insumo", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
			return;
		}
		if ($('#cboUnMedInventario').val() == 0) {
			showAppAlert('error',"Falta Seleccionar la Unidad de Medida el nombre del Insumo", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
			return;
		}
		if ($('#medReceta').val() == 0) {
			showAppAlert('error',"Falta Ingresar la medida de Receta del Insumo", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
			return;
		}
		if ($('#cboUnMedReceta').val() == 0) {
			showAppAlert('error',"Falta Seleccionar la unidad de medida de Receta del Insumo", {
                duration: 5000,
                image: base_url+ 'assets/img/errorGif.gif'
            });
			return;
		}else {
			//grabar datos

			$.post(base_url + "index.php/Insumos/getSiguienteInsumo", {}).done(function(datos) {
				var obj = $.parseJSON(datos);
				var id_insumo2 = obj[0].id_insumo;
				console.log(id_insumo2);
				if (cbarra == 0 || cbarra == '') {
					cbarra = id_insumo2
					$('#codBarra').val(id_insumo2);
					var miJson = {
						'id_insumo': id_insumo,
						'cbarra': cbarra,
						'nombre_insumo': nombre_insumo,
						'id_grupo_insumo': id_grupo_insumo,
						'id_impto_iva': id_impto_iva,
						'id_impto_adicional': id_impto_adicional,
						'umedida_inventario': umedida_inventario,
						'id_umedida_inventario': id_umedida_inventario,
						'umedida_receta': umedida_receta,
						'id_umedida_receta': id_umedida_receta,
						'costo_neto_ppp': costo_neto_ppp,
						'costo_bruto_ppp': costo_bruto_ppp,
						'precio_ucompra': precio_ucompra,
						'observaciones': observaciones,
						'inv_cierre': inv_cierre
					};
				} else {
					var miJson = {
						'id_insumo': id_insumo,
						'cbarra': cbarra,
						'nombre_insumo': nombre_insumo,
						'id_grupo_insumo': id_grupo_insumo,
						'id_impto_iva': id_impto_iva,
						'id_impto_adicional': id_impto_adicional,
						'umedida_inventario': umedida_inventario,
						'id_umedida_inventario': id_umedida_inventario,
						'umedida_receta': umedida_receta,
						'id_umedida_receta': id_umedida_receta,
						'costo_neto_ppp': costo_neto_ppp,
						'costo_bruto_ppp': costo_bruto_ppp,
						'precio_ucompra': precio_ucompra,
						'observaciones': observaciones,
						'inv_cierre': inv_cierre
					};
				}
				$.post(base_url + "index.php/Insumos/buscarInsumo", {
						'cbarra': cbarra
					}).done(function(datos) {
						var obj = $.parseJSON(datos);
						if (obj.length == 0) {
							$.post(base_url + "index.php/Insumos/grabarInsumo", {
									'prod': miJson
								}).done(function() {
									$("#idInsumo").val(id_insumo2);
									$('#modalInsumo').modal('hide');
									tableInsumos.ajax.reload();
									showAppAlert('success', "Se registro Exitosamente El Nuevo Insumo!", {
                                    	duration: 4000,
                                    	image: base_url + 'assets/img/success.gif'
                                	});
								})
								.fail(function() {
									showAppAlert('error',"Se produjo un error al realizar el proceso, favor contacte a su administrador", {
                                    	duration: 5000,
                                    	image: base_url+ 'assets/img/errorGif.gif'
                                	});
								})
						} else {
							$.each(obj, function(index, v) { // recorro el array
								if (v.cbarra == cbarra && v.id_insumo == id_insumo) {
									$.post(base_url + "index.php/Insumos/grabarInsumo", {
											'prod': miJson
										}).done(function() {
											$("#idInsumo").val(id_insumo2);
											$('#modalInsumo').modal('hide');
											tableInsumos.ajax.reload();
											showAppAlert('success', "Se Actualizó Exitosamente El Insumo", {
          									duration: 4000,
          									image: base_url+ 'assets/img/success.gif'
        									});
										})
										.fail(function() {
											showAppAlert('error', "Se produjo un error al realizar el proceso, favor contacte a su administrador", 
											{ duration: 5000, image: base_url+ 'assets/img/errorGif.gif' });
										})
								} else {
									showAppAlert('error',"El código de barras ingresado ya existe.", {
                                    	duration: 5000,
                                    	image: base_url+ 'assets/img/errorGif.gif'
                                	});
								}
							})
						}
					})
					.fail(function() {
						showAppAlert('error',"Se produjo un error al realizar el proceso, favor contacte a su administrador.", {
                          	duration: 5000,
                          	image: base_url+ 'assets/img/errorGif.gif'
						});
					});
				// $('#modalInsumo').modal('hide');
				// tableInsumos.ajax.reload();
				// swal("Listo", "Se registro Exitosamente El Nuevo Insumo", "success");
			})
		}
	});

	
	function listarHistoricoCompras(id_insumo) {
		if ($.fn.dataTable.isDataTable('#tableHistoricoCompras')) {
			tableHistoricoCompras = $('#tableHistoricoCompras').DataTable();
			tableHistoricoCompras.destroy();
		}
		tableHistoricoCompras = $("#tableHistoricoCompras").DataTable({
			"ajax": {
				"url": base_url + "index.php/Insumos/listadoHistoricoCompras",
				"type": "POST",
				"data": {
					'id_insumo': id_insumo,
				}
			},
			"bPaginate": true,
			"bProcessing": true,
			"pageLength": 10,
			"select": true,
			"dom": "t",
			"ordering": false,
			"select": {
				style: 'single', //permite elegir solo 1 fila
				style: 'os',
				selector: 'td:not(:last-child)' //esto permite que al presionar botones no se active el select de la tabla (detalle)
			},
			"responsive": {
				details: {
					type: 'column',
					target: -1
				}
			},
			"columns": [{
					mData: "fecha_compra",
					className: "text-center",
					width: "50%",
				},
				{
					mData: "nombre_proveedor",
					className: "text-left",
					width: "50%",
				},
				{
					mData: "cantidad",
					className: "text-center",
					width: "5%",
				},
				{
					mData: "bruto_unitario",
					className: "text-center",
					width: "40%",
				},
				{
					mData: "nombre_tdocumento",
					className: "text-left",
					width: "40%",
				},
				{
					mData: "numero_documento",
					className: "text-center",
					width: "40%",
				},
				{
					mData: "responsive",
					"width": "1%",
					className: 'dtr-control',
					orderable: false,
					targets: -1
				}
			],
			'language': {
				url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json",
				// traduce la linea de abajo que quedaba en ingles
				select: {
					rows: {
						_: " (Seleccionadas %d filas)",
						0: " (Presione una linea a seleccionar)",
						1: " (1 Fila seleccionada)"
					}
				}
			},
			"buttons": []
		});
	}

	function listarRecetasInsumo(id_insumo) {
		if ($.fn.dataTable.isDataTable('#tableRecetasInsumo')) {
			tableRecetasInsumo = $('#tableRecetasInsumo').DataTable();
			tableRecetasInsumo.destroy();
		}
		tableRecetasInsumo = $("#tableRecetasInsumo").DataTable({
			"ajax": {
				"url": base_url + "index.php/Insumos/listadoRecetas",
				"type": "POST",
				"data": {
					'id_insumo': id_insumo,
				}
			},
			"bPaginate": true,
			"bProcessing": true,
			"pageLength": 10,
			"select": true,
			"dom": "t",
			"ordering": false,
			"select": {
				style: 'single', //permite elegir solo 1 fila
				style: 'os',
				selector: 'td:not(:last-child)' //esto permite que al presionar botones no se active el select de la tabla (detalle)
			},
			"responsive": {
				details: {
					type: 'column',
					target: -1
				}
			},
			"columns": [{
					mData: "nombre_receta",
					className: "text-left",
					width: "50%",
				},
				{
					mData: "rendimiento",
					className: "text-center",
					width: "50%",
				},
				{
					mData: "nombre_umedida",
					className: "text-left",
					width: "5%",
				},
				{
					mData: "responsive",
					"width": "1%",
					className: 'dtr-control',
					orderable: false,
					targets: -1
				}
			],
			'language': {
				url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Spanish.json",
				// traduce la linea de abajo que quedaba en ingles
				select: {
					rows: {
						_: " (Seleccionadas %d filas)",
						0: " (Presione una linea a seleccionar)",
						1: " (1 Fila seleccionada)"
					}
				}
			},
			"buttons": []
		});
	}

	$("#tab2").on("click", function() {
		$("#tabStock").hide();
		$("#tabCosto").hide();
		$("#tabProduccion").hide();
		$("#tabRecetas").hide();
		$("#tabObservaciones").hide();
		$("#tabUnidadMedidaInsumo").show();
	});

	$("#tab3").on("click", function() {
		$("#tabUnidadMedidaInsumo").hide();
		$("#tabCosto").hide();
		$("#tabProduccion").hide();
		$("#tabRecetas").hide();
		$("#tabObservaciones").hide();
		$("#tabStock").show();
	});

	$("#tab4").on("click", function() {
		$("#tabUnidadMedidaInsumo").hide();
		$("#tabStock").hide();
		$("#tabProduccion").hide();
		$("#tabRecetas").hide();
		$("#tabObservaciones").hide();
		$("#tabCosto").show();
	});

	$("#tab5").on("click", function() {
		$("#tabUnidadMedidaInsumo").hide();
		$("#tabStock").hide();
		$("#tabCosto").hide();
		$("#tabRecetas").hide();
		$("#tabObservaciones").hide();
		$("#tabProduccion").show();
	});

	$("#tab6").on("click", function() {
		$("#tabUnidadMedidaInsumo").hide();
		$("#tabStock").hide();
		$("#tabCosto").hide();
		$("#tabProduccion").hide();
		$("#tabObservaciones").hide();
		$("#tabRecetas").show();
	});

	$("#tab7").on("click", function() {
		$("#tabUnidadMedidaInsumo").hide();
		$("#tabStock").hide();
		$("#tabCosto").hide();
		$("#tabProduccion").hide();
		$("#tabRecetas").hide();
		$("#tabObservaciones").show();
	});

	$("#tab8").on("click", function() {
		$("#tabStockInsumoReceta").hide();
		$("#tabUmMedidaReceta").show();
		tableUmMedidaReceta.columns.adjust().draw();
	});

	$("#tab9").on("click", function() {
		$("#tabUmMedidaReceta").hide();
		$("#tabStockInsumoReceta").show();
		tableStockInsumoReceta.columns.adjust().draw();
	});

	$("#btnHistoricoCompras").on("click", function() {
		var id_insumo = $("#idInsumo").val();
		var insumo = $("#nombreInsumo").val();
		var umedidaInventario = $("#cboUnMedInventario").find('option:selected').attr('nombreUm');
		listarHistoricoCompras(id_insumo);
		$('#modalHistoricoCompras').modal({
			backdrop: 'static',
			keyboard: false
		});
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

	// ========================================
// RECETA DE PRODUCCIÓN
// ========================================

// Abrir modal para crear receta de producción
$("#btnCrearRecetaProduccion").on("click", function() {
    AbrirModalNuevaRecetaProduccion();
});

function AbrirModalNuevaRecetaProduccion() {
    // Limpiar campos
    $('#idRecetaProduccion').val("");
    $('#nombreRecetaProduccion').val("");
    $('#cboInsumoElaborado').val("0");
    $('#rendimientoReceta').val("");
    $('#cboUnidadMedidaReceta').val("0");
    $('#detalleInsumosReceta').html("");
    $('#seccionInsumosReceta').hide();
    
    // Cargar combos
    cargarInsumosParaReceta();
    cargarUnidadesMedidaParaReceta();
    
    // Abrir modal
    $('#ModalNuevaRecetaProduccion').modal({
        backdrop: 'static',
        keyboard: false
    });
}

function cargarInsumosParaReceta() {
    $.post(base_url + "index.php/Insumos/ListarInsumos", {}, function(datos) {
        var obj = jQuery.parseJSON(datos);
        
        // Para el combo de insumo elaborado
        var tblElaborado = '<option value="0">-- Seleccione un Insumo --</option>';
        // Para el combo de insumos del detalle
        var tblDetalle = '<option value="0">-- Seleccione --</option>';
        
        $.each(obj, function(index, v) {
            tblElaborado += '<option value="' + v.id_insumo + '">' + v.nombre_insumo + '</option>';
            tblDetalle += '<option value="' + v.id_insumo + '">' + v.nombre_insumo + '</option>';
        });
        
        $("#cboInsumoElaborado").html(tblElaborado);
        $("#cboInsumoElaborado").select2();
        
        $("#cboInsumoDetalle").html(tblDetalle);
        $("#cboInsumoDetalle").select2();
    });
}

function cargarUnidadesMedidaParaReceta() {
    $.post(base_url + "index.php/Insumos/llenarCboUnMedida", {}, function(datos) {
        var obj = jQuery.parseJSON(datos);
        
        var tbl = '<option value="0">-- Seleccione --</option>';
        
        $.each(obj, function(index, v) {
            tbl += '<option value="' + v.id_umedida + '">' + v.nombre_umedida + '</option>';
        });
        
        $("#cboUnidadMedidaReceta").html(tbl);
        $("#cboUnidadMedidaReceta").select2();
        
        $("#cboUmedidaDetalle").html(tbl);
        $("#cboUmedidaDetalle").select2();
    });
}

function AbrirModalNuevaRecetaProduccion() {
    // Limpiar campos
    $('#idRecetaProduccion').val("");
    $('#nombreRecetaProduccion').val("");
    $('#cboInsumoElaborado').val("0");
    $('#rendimientoReceta').val("");
    $('#cboUnidadMedidaReceta').val("0");
    $('#detalleInsumosReceta').html("");
    $('#seccionAgregarInsumos').hide();
    $('#ckVigenteReceta').prop('checked', true);
    
    // Cargar combos
    cargarInsumosParaReceta();
    cargarUnidadesMedidaParaReceta();
    
    // Cargar listado de recetas existentes en la tabla
    cargarListadoRecetasEnTabla();
    
    // Abrir modal
    $('#ModalNuevaRecetaProduccion').modal({
        backdrop: 'static',
        keyboard: false
    });
}

// Función para cargar el listado de recetas en la tabla
function cargarListadoRecetasEnTabla() {
    $.post(base_url + "index.php/RecetaProduccion/ListadoRecetasProduccion", {}, function(datos) {
        var obj = jQuery.parseJSON(datos);
        
        $('#detalleInsumosReceta').html("");
        
        if (obj.aaData && obj.aaData.length > 0) {
            $.each(obj.aaData, function(index, v) {
                var fila = "<tr>";
                fila += "<td class='text-center'>" + v.id_receta_produccion + "</td>";
                fila += "<td class='text-left'>" + v.nombre_receta_produccion + "</td>";
                fila += "<td class='text-left'>" + (v.insumo_elaborado || '-') + "</td>";
                fila += "<td class='text-center'>" + v.cantidad + "</td>";
                fila += "<td class='text-center'>" + (v.nombre_umedida || '-') + "</td>";
                fila += "</tr>";
                
                $('#detalleInsumosReceta').append(fila);
            });
        } else {
            $('#detalleInsumosReceta').html("<tr><td colspan='5' class='text-center'>No hay recetas registradas</td></tr>");
        }
    }).fail(function() {
        console.error("Error al cargar el listado de recetas");
    });
}

// Toggle para mostrar/ocultar sección de agregar insumos
$('#btnVerAgregarInsumos').on('click', function() {
    $('#seccionAgregarInsumos').toggle();
});

function cargarInsumosParaReceta() {
    $.post(base_url + "index.php/Insumos/ListarInsumos", {}, function(datos) {
        var obj = jQuery.parseJSON(datos);
        
        var tblElaborado = '<option value="0">-- Seleccione un Insumo --</option>';
        var tblDetalle = '<option value="0">-- Seleccione --</option>';
        
        $.each(obj, function(index, v) {
            tblElaborado += '<option value="' + v.id_insumo + '">' + v.nombre_insumo + '</option>';
            tblDetalle += '<option value="' + v.id_insumo + '">' + v.nombre_insumo + '</option>';
        });
        
        $("#cboInsumoElaborado").html(tblElaborado);
        $("#cboInsumoElaborado").select2();
        
        $("#cboInsumoDetalle").html(tblDetalle);
        $("#cboInsumoDetalle").select2();
    });
}

function cargarUnidadesMedidaParaReceta() {
    $.post(base_url + "index.php/Insumos/llenarCboUnMedida", {}, function(datos) {
        var obj = jQuery.parseJSON(datos);
        
        var tbl = '<option value="0">-- Seleccione --</option>';
        
        $.each(obj, function(index, v) {
            tbl += '<option value="' + v.id_umedida + '">' + v.nombre_umedida + '</option>';
        });
        
        $("#cboUnidadMedidaReceta").html(tbl);
        $("#cboUnidadMedidaReceta").select2();
        
        $("#cboUmedidaDetalle").html(tbl);
        $("#cboUmedidaDetalle").select2();
    });
}

function grabarNuevaRecetaProduccion() {
    if ($('#nombreRecetaProduccion').val() === '') {
        showAppAlert('error', "Ingrese el nombre de la receta", {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }
    
    if ($('#cboInsumoElaborado').val() == 0) {
        showAppAlert('error', "Seleccione el insumo elaborado", {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }
    
    if ($('#rendimientoReceta').val() === '' || $('#rendimientoReceta').val() == 0) {
        showAppAlert('error', "Ingrese el rendimiento de la receta", {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }
    
    if ($('#cboUnidadMedidaReceta').val() == 0) {
        showAppAlert('error', "Seleccione la unidad de medida", {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
        return;
    }
    
    var id_receta = $('#idRecetaProduccion').val();
    var nombre_receta = $('#nombreRecetaProduccion').val().toUpperCase().replace(/[,.'"$#%&]+/g, '');
    var id_insumo = $('#cboInsumoElaborado').val();
    var cantidad = $('#rendimientoReceta').val();
    var id_umedida = $('#cboUnidadMedidaReceta').val();
    var id_bodega = 0;
    
    var miJson = {
        'id_receta_produccion': id_receta,
        'nombre_receta_produccion': nombre_receta,
        'id_insumo': id_insumo,
        'cantidad': cantidad,
        'id_umedida': id_umedida,
        'id_bodega': id_bodega
    };
    
    console.log("=== DATOS A ENVIAR ===");
    console.log(miJson);
    
    $.post(base_url + "index.php/RecetaProduccion/grabarRecetaProduccion", {
        'receta': miJson
    })
    .done(function(response) {
        console.log("=== RESPUESTA DEL SERVIDOR ===");
        console.log(response);
        
        try {
            var respuesta = typeof response === 'string' ? JSON.parse(response) : response;
            
            if (respuesta.error) {
                showAppAlert('error', respuesta.message || "Error al grabar la receta", {
                    duration: 5000,
                    image: base_url + 'assets/img/errorGif.gif'
                });
                return;
            }
            
            var idRecetaNueva = null;
            
            if (Array.isArray(respuesta) && respuesta.length > 0) {
                idRecetaNueva = respuesta[0].id_receta_produccion;
            } else if (respuesta.id_receta_produccion) {
                idRecetaNueva = respuesta.id_receta_produccion;
            }
            
            console.log("ID de receta nueva:", idRecetaNueva);
            
            showAppAlert('success', "Se registró exitosamente la receta de producción", {
                duration: 4000,
                image: base_url + 'assets/img/success.gif'
            });
            
            // Recargar la tabla de recetas
            cargarListadoRecetasEnTabla();
            
            // Limpiar campos
            $('#nombreRecetaProduccion').val("");
            $('#cboInsumoElaborado').val("0").trigger('change');
            $('#rendimientoReceta').val("");
            $('#cboUnidadMedidaReceta').val("0").trigger('change');
            
            // Si estamos en el modal de insumos, recargar el combo
            if ($('#cboMedReceta').length > 0) {
                var idInsumoActual = $('#idInsumo').val();
                if (idInsumoActual && idInsumoActual > 0) {
                    llenarRecetaProduccion(idInsumoActual);
                    
                    if (idRecetaNueva) {
                        setTimeout(function() {
                            $('#cboMedReceta').val(idRecetaNueva).trigger('change');
                        }, 800);
                    }
                }
            }
        } catch (e) {
            console.error("Error al parsear respuesta:", e);
            showAppAlert('error', "Error al procesar la respuesta del servidor", {
                duration: 5000,
                image: base_url + 'assets/img/errorGif.gif'
            });
        }
    })
    .fail(function(xhr, status, error) {
        console.error("=== ERROR EN LA PETICIÓN ===");
        console.error("Status:", status);
        console.error("Error:", error);
        console.error("Response:", xhr.responseText);
        
        showAppAlert('error', "Se produjo un error al grabar la receta", {
            duration: 5000,
            image: base_url + 'assets/img/errorGif.gif'
        });
    });
}

// Botón para realizar producción
$("#btnRealizarProduccion").on("click", function() {
    AbrirModalProduccion();
});
</script>
<!-- esto va en menu top para quitar las flechas en input type number 
<style>
		input::-webkit-outer-spin-button,
		input::-webkit-inner-spin-button {
			-webkit-appearance: none;
			margin: 0;
		}
		input[type=number] {
			-moz-appearance: textfield;
		}
	</style> -->
