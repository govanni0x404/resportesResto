<?php
date_default_timezone_set('America/Santiago');
$date = new DateTime('now');
$date->modify('last day of this month');
//$fechaActual = date('Y-m-d');
$fechaActual = date('d-m-Y');
?>
<div class="content-header">
	<div class="header-icon">
		<i class="pe-7s-home"></i>
	</div>
	<div class="header-title">
		<h1 id="local"></h1>
		<!--nombre fantasia-->
		<h4 id="lblNombreCliente"></h4>
		<small id="caja"></small>
		<ol class="breadcrumb">
			<!--<li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i> Home</a></li>
			<li class="active">Dashboard</li>-->
		</ol>
	</div>
</div> <!-- /.Content Header (Page header) -->

<div class="row">

	<div class="col-xs-12 col-sm-12 col-md-12 m-b-20">
		<!-- Nav tabs -->

		<ul class="nav nav-tabs">
			<div class="col-md-3">
				<br>
				<label>Sección:</label>
				<select style="width:100%; height:34px;" id="cboGraficos" nombre="cboGraficos"
					class="form-control select2">
					<option value="1"><a href="#cajaActual" data-toggle="tab"><i class="pe-7s-monitor"></i> Detalle
							Caja</a></option>

				</select>
			</div>
			<div class="col-md-4">
				<br>
				<label>Cajas:</label>
				<select class="form-control" id="cboCajas" nombre="cboCajas">
					<option selected value="">Todas Las Cajas Del Día</option>
				</select>
			</div>
			<!-- <div class="col-md-4">
				<br>
				<label>Cajas:</label>
				<select style="width:100%; height:34px;" id="cboCajas" nombre="cboCajas" class="form-control" aria-label="Default select example">
					<option selected value="">Seleccione</option>
				</select>
			</div>
			<div class="col-md-4" id="divBodegas">
				<br>
				<label>Bodegas:</label>
				<select style="width:100%; height:34px;" id="cboBodega" nombre="cboBodega" class="form-control">
					<option selected value="">Seleccione</option>
				</select>
			</div> -->
			<!--
			<div class="col-md-2">
				<input type="date" class="hidden" id="pruebaFecha" value="<?php echo $fechaActual ?>" >
				<br>

				<label>Fecha Caja:</label>
				<input onpaste="return false;" value="<?php echo $fechaActual; ?> - <?php echo $fechaActual; ?>" ondrop="return false;" autocomplete="off" type="date" name="fechaCaja" id="fechaCaja" onkeydown="return false" class="form-control">
			</div>-->
			<div class="col-md-3">
				<br>
				<div class="control-group">
					<div class="controls">
						<label> Seleccione la fecha </label>
						<div class="input-prepend input-group">
							<span class="add-on input-group-addon"><i class="fa fa-calendar"></i></span>
							<input type="text" class="hidden" id="pruebaFecha"
								value="<?php echo $fechaActual; ?> - <?php echo $fechaActual; ?>">
							<input type="text" id="date-range-picker" class="form-control"
								value="<?php echo $fechaActual; ?> - <?php echo $fechaActual; ?>" />
						</div>
					</div>
				</div>
			</div>

			<div class="col-md-2">
				<br>
				<br>
				<button id="btnTest" style="width:5px;" class="btn btn-success btn-rounded w-md m-b-1"><i
						class="hvr-buzz-out fa fa-search"></i></button>
			</div>

		</ul>
		<br>

		<!-- Tab panels -->
		<div class="tab-content">
			<!---------------------------------------------TAB CAJA ACTUAL ------------------------------------------------------------>
			<div class="tab-pane fade active in" id="cajaActual">
				<div class="panel-body">
					<div class="row">
						<fieldset>
							<ul class="nav nav-tabs">
								<div class="col-md-3">
									<br>
									<!--
									<label>Opciones:</label>
									<select onclick="VercierreCajaPDF()" style="width:100%; height:34px;" id="opciones" nombre="opciones" class="form-control select2">
										<option value="1"><a onclick="VercierreCajaPDF()" href="#ExportarPdf" data-toggle="tab"><i class="pe-7s-monitor"></i> Exportar PDF</a></option>
									</select>-->

									<button onclick="PDFDetalleCaja()" class="btn btn-danger w-md m-b-1"><i
											class="hvr-buzz-out fa fa-pdf"></i>EXPORTAR PDF</button>
								</div>
							</ul>
						</fieldset>
						<br>
						<div class="col-xs-12 col-sm-6 col-md-6 col-lg-4">
							<div class="statistic-box statistic-filled-6 btn-primary">
								<h2>$<span class="count-number1" id="totalVentas">0</span><span class="slight"><i
											class=" fa-rotate-90 c-white"> </i></span></h2>
								<div class="small">Total Ventas</div>
								<i class="pe-7s-cash statistic_icon c-white"></i>
								<!-- <div id="divTotalVenta1"><label id="lblTotalVenta1"></label> <label>test</label></div>
								<div id="divTotalVenta2"><label id="lblTotalVenta2"></label> <label>test</label></div> -->
							</div>
						</div>

						<div class="col-xs-12 col-sm-6 col-md-6 col-lg-4">
							<div class="statistic-box statistic-filled-6 btn-primary">
								<h2>$<span class="count-number2" id="fondoFijo">0</span><span class="slight"><i
											class=" fa-rotate-270 c-white"> </i></span></h2>
								<div class="small">Fondo Fijo</div>
								<i class="pe-7s-cash statistic_icon"></i>
								<!-- <div id="divCaja1"><label id="lblTest1"></label> <label>test</label></div>
								<div id="divCaja2"><label>test:</label> <label>test</label></div> -->
							</div>
						</div>

						<div class="col-xs-12 col-sm-6 col-md-6 col-lg-4">
							<div onclick="AbrirModalDetalleDescuentosDeCaja()"
								class="statistic-box statistic-filled-6 btn-primary">
								<h2>$<span class="count-number3" id="descuentos">0</span><span class="slight"><i
											class=" fa-rotate-270 c-white"> </i> </span></h2>
								<div class="small">Descuentos </div>
								<i class="pe-7s-cash statistic_icon white"></i>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-xs-12 col-sm-6 col-md-6 col-lg-4">
							<div onclick="AbrirModalDetallePropinasDeCaja()"
								class="statistic-box statistic-filled-6 btn-primary">
								<h2>$<span class="count-number4" id="propinas">0</span><span class="slight"><i
											class=" fa-rotate-270 c-white"> </i></span></h2>
								<div class="small">Propinas</div>
								<i class="pe-7s-cash statistic_icon"></i>
							</div>
						</div>

						<div class="col-xs-12 col-sm-6 col-md-6 col-lg-4">
							<div onclick="AbrirModalDetalleDeIngresosDeCaja()"
								class="statistic-box statistic-filled-6 btn-primary">
								<h2>$<span class="count-number5" id="ingresos">0</span><span class="slight"><i
											class=" fa-rotate-90 c-white"> </i></span></h2>
								<div class="small">Ingresos</div>
								<i class="pe-7s-cash statistic_icon c-white"></i>
							</div>
						</div>

						<div class="col-xs-12 col-sm-6 col-md-6 col-lg-4">
							<div onclick="AbrirModalDetalleRetiroDeCaja()"
								class="statistic-box statistic-filled-6 btn-primary">
								<h2>$<span class="count-number6" id="egresos">0</span><span class="slight"><i
											class=" fa-rotate-270 c-white"> </i> </span></h2>
								<div class="small">Retiros </div>
								<i class="pe-7s-cash statistic_icon white"></i>
							</div>
						</div>
					</div>
					<!---------------------------------------------TABLAS RESUMEN ------------------------------------------------------------>

					<div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
						<div class="panel panel-success lobidisable">
							<div class="panel-heading">
								<div class="panel-title">
									<i class="pe-7s-file"></i>
									<h4>Resumen Por Tipo De Documento</h4>
								</div>
							</div>
							<div class="table-responsive">
								<table id="tableTipoDocumento" class="table">
									<thead>
										<tr>
											<th class="text text-center">Tipo De Documento</th>
											<th class="text text-center">Cantidad</th>
											<!--<th class="text text-center ocultarNetoTdocumento">Neto</th>-->
											<!--<th class="text text-center ocultarIvaTdocumento">Iva</th>-->
											<th class="text text-center">Total</th>
										</tr>
									</thead>
									<tbody id="resumenTipoDocumento">

									</tbody>
								</table>
							</div>
						</div>
					</div>
					<div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
						<div class="panel panel-success lobidisable">
							<div class="panel-heading">
								<div class="panel-title">
									<i class="pe-7s-cash"></i>
									<h4>Resumen Por Forma De Pago</h4>
								</div>
							</div>
							<div class="table-responsive">
								<table id="tablaFormaDePago" class="table">
									<thead>
										<tr>
											<th class="text text-center">Forma De Pago</th>
											<th class="text text-center">Cantidad</th>
											<!--<th class="text text-center ocultarNetoFormaDePago">Neto</th>-->
											<!--<th class="text text-center ocultarIvaFormaDePago">Iva</th>-->
											<th class="text text-center">Total</th>
											<th class="text text-center">Propina</th>
										</tr>
									</thead>
									<tbody id="resumenFormaPago">
									</tbody>
								</table>
							</div>
						</div>
					</div>
					<div class="col-xs-12 col-sm-6 col-md-6 col-lg-6"></div>
					<div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
						<div class="panel panel-success lobidisable">
							<div class="panel-heading">
								<div class="panel-title">
									<i class="pe-7s-cash"></i>
									<h4>Cortesias</h4>
								</div>
							</div>
							<div class="table-responsive">
								<table class="table">
									<thead>
										<tr>
											<th class="text text-center">Tipo Cortesia</th>
											<th class="text text-center">Cantidad</th>
											<!--<th class="text text-center ocultarNetoCortesias">Neto</th>-->
											<!--<th class="text text-center ocultarIvaCortesias">Iva</th>-->
											<th class="text text-center">Total</th>
										</tr>
									</thead>
									<tbody id="resumenCortesias">
									</tbody>
								</table>
							</div>
						</div>
					</div>
					<div class="col-xs-12 col-sm-6 col-md-6 col-lg-12" style="width:100%;">
						<div class="panel panel-success lobidisable">
							<div class="panel-heading">
								<div class="panel-title">
									<i class="pe-7s-cash"></i>
									<h4>Resumen Ventas Por Pagar</h4>
								</div>
							</div>
							<div class="table-responsive" style="margin-top:10px; padding:5px;">
								<table id="tableVentasPorPagar" class="display responsive" style="width: 100%">
									<thead>
										<tr>
											<th class="text text-center">N° Pedido</th>
											<th class="text text-center">Mesa</th>
											<th class="text text-center">Fecha/Hora</th>
											<th class="text text-center">Garzon/a</th>
											<th class="text text-center">Cliente</th>
											<th class="text text-center">Cuenta</th>
											<th class="text text-center">Descuento</th>
											<!--<th class="text text-center">Neto</th>
											<th class="text text-center">Iva</th>-->
											<th class="text text-center">Total</th>
											<th class="text text-center">Div.</th>
											<th class="text text-center">Detalle</th>
											<th class="text text"></th>
										</tr>
									</thead>
									<tfoot>
										<tr>
											<th></th>
											<th></th>
											<th></th>
											<th></th>
											<th></th>
											<th></th>
											<th></th>
											<th></th>
											<!--<th></th>
											<th></th>-->
											<th></th>
											<th></th>
											<th></th>
										</tr>
									</tfoot>
								</table>
							</div>
						</div>
					</div>
					<div class="modal fade modal-success" id="modal-success" tabindex="-1" role="dialog">
						<div class="modal-dialog" role="document">
							<div class="modal-content">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
											aria-hidden="true">&times;</span></button>
									<h1 class="modal-title">Consumo Pedido</h1>
								</div>
								<div class="modal-body">
									<!--
									<div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
										<div class="panel panel-success lobidisable">
											<div class="panel-heading">
												<div class="panel-title">
													<i class="pe-7s-news-paper"></i>
													<h4>Productos</h4>
												</div>
											</div>
											<div class="panel-body">
											</div>
										</div>
									</div>-->
									<div class="panel panel-success lobidisable">
										<div class="panel-heading">
											<div class="panel-title">
												<i class="pe-7s-news-paper"></i>
												<h4>Productos</h4>
											</div>
										</div>
										<div class="table-responsive" style="margin-top:10px; padding:5px;">
											<table id="tableDetallePedido" class="display responsive"
												style="width: 100%">
												<thead>
													<tr>
														<th class="text text-center">Producto</th>
														<th class="text text-center">Cantidad</th>
														<th class="text text-center">Precio Unitario</th>
														<!--<th class="text text-center">Precio Neto</th>
														<th class="text text-center">Iva</th>-->
														<th class="text text-center">Total</th>
														<th class="text text-center">Hora Pedido</th>
														<th class="text text-center"></th>
													</tr>
												</thead>
												<tfoot>
													<tr>
														<th></th>
														<th></th>
														<th></th>
														<!--<th></th>
														<th></th>-->
														<th></th>
														<th></th>
														<th></th>
													</tr>
												</tfoot>
											</table>
										</div>
									</div>
								</div>
								<div class="modal-footer">
									<!--	<button type="button" class="btn btn-danger" data-dismiss="modal"><i class="pe-7s-close-circle"></i> Close</button>-->
									<button type="button" class=" btn btn-labeled btn-danger m-b-5"
										data-dismiss="modal"><span class="btn-label">
											<i class="pe-7s-close-circle"></i></span> Cerrar</button>
								</div>
							</div><!-- /.modal-content -->
						</div><!-- /.modal-dialog -->
					</div><!-- /.modal -->

					<!-- CAMBIOS NUEVOS----------------------------------------------------------------------------------------------------------------->
					<!------------------------------------------------VENTAS POR FAMILIA (GRAFICO Y TABLA)---------------------------------------------------------------------->
					<div class="col-xs-12 col-sm-6 col-md-6 col-lg-12" style="width:100%;">
						<div class="panel panel-success lobidisable">
							<div class="panel-heading">
								<div class="panel-title">
									<h4>Ventas Por Familia</h4>
								</div>
							</div>
							<div class="panel-body" id="grafico">
								<div class="col-md-2"></div>
								<div class="col-md-8">
									<canvas id="chartFamilias" style="height: auto;"></canvas>
								</div>
								<div class="col-md-2"></div>
							</div>
							<div class="panel panel-success lobidisable">
								<div class="table-responsive" style="margin-top:10px; padding:5px;">
									<table id="tableVentasPorFamilia" class="display responsive" style="width: 100%">
										<thead>
											<tr>
												<th class="hidden">Familia</th>
												<th class="text text-center">Familia</th>
												<th class="text text-center">Cantidad</th>
												<!--<th class="text text-center">Total Neto</th>
												<th class="text text-center">Total Iva</th>-->
												<th class="text text-center">Total</th>
												<th class="text text-center">Detalles</th>
												<th class="text text"></th>
											</tr>
											<div class="col-md-9"></div>
											<div class="col-md-3">
												<label id="totalFamilia"></label>
											</div>
										</thead>
										<tfoot>
											<tr>
												<th></th>
												<th></th>
												<th></th>
												<!--<th></th>
												<th></th>-->
												<th></th>
												<th></th>
												<th></th>
											</tr>
										</tfoot>
									</table>
								</div>
							</div>


							<div class="modal fade modal-success" id="modalProductos" tabindex="-1" role="dialog">
								<div class="modal-dialog" role="document">
									<div class="modal-content">
										<div class="modal-header">
											<button type="button" class="close" data-dismiss="modal"
												aria-label="Close"><span aria-hidden="true">&times;</span></button>
											<h1 class="modal-title">Productos</h1>
										</div>
										<div class="modal-body">
											<div class="panel panel-success lobidisable">
												<div class="panel-heading">
													<div class="panel-title">
														<i class="pe-7s-news-paper"></i>
														<h4>Productos</h4>
													</div>
												</div>
												<div class="table-responsive" style="margin-top:10px; padding:5px;">
													<table id="tableVentasPorProducto" class="display responsive"
														style="width: 100%">
														<thead>
															<tr>
																<th class="text text-center">Producto</th>
																<th class="text text-center">Cantidad</th>
																<!--<th class="text text-center">Total Neto</th>
																<th class="text text-center">Iva</th>-->
																<th class="text text-center">Total</th>
															</tr>
														</thead>
														<tfoot>
															<tr>
																<th></th>
																<th></th>
																<!--<th></th>
																<th></th>-->
																<th></th>
															</tr>
														</tfoot>
													</table>
												</div>
											</div>
										</div>
										<div class="modal-footer">
											<!--	<button type="button" class="btn btn-danger" data-dismiss="modal"><i class="pe-7s-close-circle"></i> Close</button>-->
											<button type="button" class=" btn btn-labeled btn-danger m-b-5"
												data-dismiss="modal"><span class="btn-label">
													<i class="pe-7s-close-circle"></i></span> Cerrar</button>
										</div>
									</div><!-- /.modal-content -->
								</div><!-- /.modal-dialog -->
							</div><!-- /.modal -->
						</div>
					</div>


					<!-----------------------------------------------------VENTAS POR MESAS------------------------------------------->
					<div class="col-xs-12 col-sm-6 col-md-6 col-lg-12" style="width:100%;">
						<div class="panel panel-success lobidisable">
							<div class="panel-heading">
								<div class="panel-title">
									<i class="pe-7s-diskette"></i>
									<h4>Ventas Por Mesas</h4>
								</div>
							</div>
							<div class="table-responsive" style="margin-top:10px; padding:5px;">
								<table id="tableVentasPorMesa" class="display responsive" style="width: 100%">
									<thead>
										<tr>
											<th class="text text-center">Mesa</th>
											<th class="text text-center">Total Ventas</th>
											<!--<th class="text text-center">Neto</th>
											<th class="text text-center">Iva</th>-->
											<th class="text text-center">Total</th>
										</tr>
										<div class="col-md-6"></div>
										<div class="col-md-3"></div>
										<div class="col-md-3">
											<label id="montoMesa"></label>
										</div>
									</thead>
									<tfoot>
										<tr>
											<th></th>
											<th></th>
											<!--<th></th>
											<th></th>-->
											<th></th>
										</tr>
									</tfoot>
								</table>
							</div>
						</div>
					</div>


					<!-----------------------------------------------------------DETALLE ATENCION GARZONES ---------------------------------------------------------------------------------------->
					<div class="col-xs-12 col-sm-6 col-md-6 col-lg-12" style="width:100%;">
						<div class="panel panel-success lobidisable">
							<div class="panel-heading">
								<div class="panel-title">
									<i class="pe-7s-diskette"></i>
									<h4>Detalle De Atencion</h4>
								</div>
							</div>
							<div class="table-responsive" style="margin-top:10px; padding:5px;">
								<table id="tableDetalleAtencion" class="display responsive" style="width: 100%">
									<thead>
										<tr>
											<th class="text text-center">Garzon</th>
											<th class="text text-center">Mesas Atendidas</th>
											<!--<th class="text text-center">Total Neto</th>
											<th class="text text-center">Total Iva</th>-->
											<th class="text text-center">Total Ventas</th>
											<th class="text text-center">Propinas</th>
											<th class="text text"></th>
											<!-- <th class="text text-center">Tiempo Promedio</th> -->
										</tr>
										<div class="col-md-6"></div>
										<div class="col-md-3">
											<label id="totalVentasGarzon"></label>
										</div>
										<div class="col-md-3">
											<label id="totalPropinasGarzon"></label>
										</div>
									</thead>
									<tfoot>
										<tr>
											<th></th>
											<th></th>
											<!--<th></th>
											<th></th>-->
											<th></th>
											<th></th>
											<th></th>
										</tr>
									</tfoot>
								</table>
							</div>
						</div>
					</div>




				</div>
			</div>
			<!---------------------------------------------FIN TAB CAJA ACTUAL ------------------------------------------------------------>
			<!---------------------------------------------TAB VENTAS POR FAMILIAS ------------------------------------------------------------>
			<!--
			<div class="tab-pane fade" id="ventasPorFamilias" style="height: auto;">
				<div class="col-xs-12 col-sm-6 col-md-6 col-lg-12" style="width:100%;">
					<div class="panel panel-success lobidisable">
						<div class="panel-heading">
							<div class="panel-title">
								<h4>Ventas Por Familia</h4>
							</div>
						</div>
						<div class="panel-body" id="grafico">
							<div class="col-md-2"></div>
							<div class="col-md-8">
								<canvas id="chartFamilias" style="height: auto;"></canvas>
							</div>
							<div class="col-md-2"></div>
						</div>
						<div class="panel panel-success lobidisable">
							<div class="table-responsive" style="margin-top:10px; padding:5px;">
								<table id="tableVentasPorFamilia" class="display responsive" style="width: 100%">
									<thead>
										<tr>
											<th class="hidden">Familia</th>
											<th class="text text-center">Familia</th>
											<th class="text text-center">Cantidad</th>
											<th class="text text-center">Total Neto</th>
											<th class="text text-center">Total Iva</th>
											<th class="text text-center">Total</th>
											<th class="text text-center">Detalles</th>
											<th class="text text"></th>
										</tr>
										<div class="col-md-9"></div>
										<div class="col-md-3">
											<label id="totalFamilia"></label>
										</div>
									</thead>
									<tfoot>
										<tr>
											<th></th>
											<th></th>
											<th></th>
											<th></th>
											<th></th>
											<th></th>
											<th></th>
											<th></th>
										</tr>
									</tfoot>
								</table>
							</div>
						</div>
					</div>
				</div>
				<div class="modal fade modal-success" id="modalProductos" tabindex="-1" role="dialog">
					<div class="modal-dialog" role="document">
						<div class="modal-content">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
								<h1 class="modal-title">Productos</h1>
							</div>
							<div class="modal-body">
								<div class="panel panel-success lobidisable">
									<div class="panel-heading">
										<div class="panel-title">
											<i class="pe-7s-news-paper"></i>
											<h4>Productos</h4>
										</div>
									</div>
									<div class="table-responsive" style="margin-top:10px; padding:5px;">
										<table id="tableVentasPorProducto" class="display responsive" style="width: 100%">
											<thead>
												<tr>
													<th class="text text-center">Producto</th>
													<th class="text text-center">Cantidad</th>
													<th class="text text-center">Total Neto</th>
													<th class="text text-center">Iva</th>
													<th class="text text-center">Total</th>
												</tr>
											</thead>
											<tfoot>
												<tr>
													<th></th>
													<th></th>
													<th></th>
													<th></th>
													<th></th>
												</tr>
											</tfoot>
										</table>
									</div>
								</div>
							</div>
							<div class="modal-footer">
								
								<button type="button" class=" btn btn-labeled btn-danger m-b-5" data-dismiss="modal"><span class="btn-label">
										<i class="pe-7s-close-circle"></i></span> Cerrar</button>
							</div>
						</div>
					</div>
				</div>
			</div>-->
			<!---------------------------------------------FIN TAB VENTAS POR FAMILIAS  ------------------------------------------------------------>

			<!---------------------------------------------TAB VENTAS POR PRODUCTO ------------------------------------------------------------>
			<div class="tab-pane fade" id="ventasPorProducto" style="height: auto;">
				<div class="col-xs-12 col-sm-6 col-md-6 col-lg-12" style="width:100%;">
					<div class="panel panel-success lobidisable">
						<div class="panel-heading">
							<div class="panel-title">
								<i class="pe-7s-cash"></i>
								<h4>Resumen Ventas Por Producto</h4>
							</div>
						</div>
						<div class="table-responsive" style="margin-top:10px; padding:5px;">
							<table id="tableVentasPorProducto" class="display responsive" style="width: 100%">
								<thead>
									<tr>
										<th class="text text-center">Producto</th>
										<th class="text text-center">Cantidad</th>
										<th class="text text-center">Total</th>
										<th class="text text"></th>
									</tr>
								</thead>
							</table>
						</div>
					</div>
				</div>
			</div>
			<!---------------------------------------------FIN TAB VENTAS POR PRODUCTO  ------------------------------------------------------------>

			<!---------------------------------------------TAB VENTAS POR MESAS ------------------------------------------------------------>
			<!--
			<div class="tab-pane fade" id="detalleDeMesas">
				<div class="col-xs-12 col-sm-6 col-md-6 col-lg-12" style="width:100%;">
					<div class="panel panel-success lobidisable">
						<div class="panel-heading">
							<div class="panel-title">
								<i class="pe-7s-diskette"></i>
								<h4>Ventas Por Mesas</h4>
							</div>
						</div>
						<div class="table-responsive" style="margin-top:10px; padding:5px;">
							<table id="tableVentasPorMesa" class="display responsive" style="width: 100%">
								<thead>
									<tr>
										<th class="text text-center">Mesa</th>
										<th class="text text-center">Total Ventas</th>
										<th class="text text-center">Neto</th>
										<th class="text text-center">Iva</th>
										<th class="text text-center">Total</th>
									</tr>
									<div class="col-md-6"></div>
									<div class="col-md-3"></div>
									<div class="col-md-3">
										<label id="montoMesa"></label>
									</div>
								</thead>
								<tfoot>
									<tr>
										<th></th>
										<th></th>
										<th></th>
										<th></th>
										<th></th>
									</tr>
								</tfoot>
							</table>
						</div>
					</div>
				</div>
			</div>
			<!---------------------------------------------FIN TAB VENTAS POR MESAS  ------------------------------------------------------------>

			<!---------------------------------------------TAB DETALLE ATENCION DE GARZONES ------------------------------------------------------------>
			<div class="tab-pane fade" id="detalleAtencionAgarzones">
				<div class="col-xs-12 col-sm-6 col-md-6 col-lg-12" style="width:100%;">
					<div class="panel panel-success lobidisable">
						<div class="panel-heading">
							<div class="panel-title">
								<i class="pe-7s-diskette"></i>
								<h4>Detalle De Atencion</h4>
							</div>
						</div>
						<div class="table-responsive" style="margin-top:10px; padding:5px;">
							<table id="tableDetalleAtencion" class="display responsive" style="width: 100%">
								<thead>
									<tr>
										<th class="text text-center">Garzon</th>
										<th class="text text-center">Mesas Atendidas</th>
										<th class="text text-center">Total Neto</th>
										<th class="text text-center">Total Iva</th>
										<th class="text text-center">Total Ventas</th>
										<th class="text text-center">Propinas</th>
										<th class="text text"></th>
										<!-- <th class="text text-center">Tiempo Promedio</th> -->
									</tr>
									<div class="col-md-6"></div>
									<div class="col-md-3">
										<label id="totalVentasGarzon"></label>
									</div>
									<div class="col-md-3">
										<label id="totalPropinasGarzon"></label>
									</div>
								</thead>
								<tfoot>
									<tr>
										<th></th>
										<th></th>
										<th></th>
										<th></th>
										<th></th>
										<th></th>
										<th></th>
									</tr>
								</tfoot>
							</table>
						</div>
					</div>
				</div>
			</div>
			<!---------------------------------------------FIN DETALLE ATENCION DE GARZONES  ------------------------------------------------------------>
		</div>
		<!---------------------------------------------FIN TAB DETALLE ATENCION DE GARZONES  ------------------------------------------------------------>
	</div>
</div>
</div>

<div class="modal fade modal-success" id="modalDetalleIngresos" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
						aria-hidden="true">&times;</span></button>
				<h1 class="modal-title">Detalle De Ingresos</h1>
			</div>
			<div class="modal-body">
				<div class="panel panel-success lobidisable">
					<div class="panel-heading">
						<div class="panel-title">
							<i class="pe-7s-news-paper"></i>
							<h4>Ingresos</h4>
						</div>
					</div>
					<div class="table-responsive">
						<table class="table">
							<thead>
								<tr>
									<th class="text text-center">N°</th>
									<th class="text text-center">Motivo Ingreso</th>
									<th class="text text-center">Monto</th>
									<th class="text text-center">Fecha/Hora</th>
									<th class="text text-center">Descripcion</th>
									<th class="text text-center">Centro De Costo</th>
								</tr>
							</thead>
							<tbody id="resumenDetalleIngresoDeCaja">
							</tbody>
						</table>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<!--	<button type="button" class="btn btn-danger" data-dismiss="modal"><i class="pe-7s-close-circle"></i> Close</button>-->
				<button type="button" class=" btn btn-labeled btn-danger m-b-5" data-dismiss="modal"><span
						class="btn-label">
						<i class="pe-7s-close-circle"></i></span> Cerrar</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<div class="modal fade modal-success" id="modalDetalleRetiros" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
						aria-hidden="true">&times;</span></button>
				<h1 class="modal-title">Detalle De Retiro</h1>
			</div>
			<div class="modal-body">
				<div class="panel panel-success lobidisable">
					<div class="panel-heading">
						<div class="panel-title">
							<i class="pe-7s-news-paper"></i>
							<h4> Retiros</h4>
						</div>
					</div>
					<div class="table-responsive">
						<table class="table">
							<thead>
								<tr>
									<th class="text text-center">N°</th>
									<th class="text text-center">Motivo Retiro</th>
									<th class="text text-center">Monto</th>
									<th class="text text-center">Fecha/Hora</th>
									<th class="text text-center">Descripcion Retiro</th>
									<th class="text text-center">Centro De Costo</th>
								</tr>
							</thead>
							<tbody id="resumenDetalleRetiroDeCaja">
							</tbody>
						</table>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<!--	<button type="button" class="btn btn-danger" data-dismiss="modal"><i class="pe-7s-close-circle"></i> Close</button>-->
				<button type="button" class=" btn btn-labeled btn-danger m-b-5" data-dismiss="modal"><span
						class="btn-label">
						<i class="pe-7s-close-circle"></i></span> Cerrar</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<div class="modal fade modal-success" id="modalDetalleDeDescuentos" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
						aria-hidden="true">&times;</span></button>
				<h1 class="modal-title">Detalle De Descuentos</h1>
			</div>
			<div class="modal-body">
				<div class="panel panel-success lobidisable">
					<div class="panel-heading">
						<div class="panel-title">
							<i class="pe-7s-news-paper"></i>
							<h4> Descuentos</h4>
						</div>
					</div>
					<div class="table-responsive">
						<table class="table">
							<thead>
								<tr>
									<th class="text text-center">N° Venta</th>
									<th class="text text-center">Cliente</th>
									<th class="text text-center">Pedido</th>
									<th class="text text-center">Total Sin Descuento</th>
									<th class="text text-center">% De Descuento</th>
									<th class="text text-center">Monto De Descuento</th>
									<th class="text text-center">Total Con Descuento</th>
								</tr>
							</thead>
							<tbody id="resumenDetalleDescuentos">
							</tbody>
						</table>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<!--	<button type="button" class="btn btn-danger" data-dismiss="modal"><i class="pe-7s-close-circle"></i> Close</button>-->
				<button type="button" class=" btn btn-labeled btn-danger m-b-5" data-dismiss="modal"><span
						class="btn-label">
						<i class="pe-7s-close-circle"></i></span> Cerrar</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->


<div class="modal fade modal-success" id="modalDetalleDePropinas" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
						aria-hidden="true">&times;</span></button>
				<h1 class="modal-title">Detalle De Propinas</h1>
			</div>
			<div class="modal-body">
				<div class="panel panel-success lobidisable">
					<div class="panel-heading">
						<div class="panel-title">
							<i class="pe-7s-news-paper"></i>
							<h4> Propinas</h4>
						</div>
					</div>
					<fieldset>
						<div class="col-md-8">
							<br>
							<label>Garzon:
								<div class="checkbox checkbox-success checkbox-inline">
									<input type="checkbox" id="ckTodosLosUsuarios" value="option1">
									<label for="ckTodosLosUsuarios"> Todos </label>
								</div>
							</label>
							<select class="form-control" id="cboUsuarios" nombre="cboUsuarios">
								<option selected value="">
									<!--Todos Los Usuarios-->
								</option>
							</select>
						</div>
						<div class="col-md-4">
							<br>
							<br>
							<button id="btnFiltrarPropinasPorUsuario" style="width:5px;"
								class="btn btn-success btn-rounded w-md m-b-1"><i
									class="hvr-buzz-out fa fa-search"></i></button>
						</div>
					</fieldset>
					<div class="table-responsive" style="margin-top:10px; padding:5px;">
						<table id="tableDetallePropinas" class="display responsive" style="width: 100%">
							<thead>
								<tr>
									<th class="text text-center">Id Garzon</th>
									<th class="text text-center">Garzon</th>
									<th class="text text-center">Forma De Pago</th>
									<th class="text text-center">Propina</th>
								</tr>
							</thead>
							<tfoot>
								<tr>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
								</tr>
							</tfoot>
						</table>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<!--	<button type="button" class="btn btn-danger" data-dismiss="modal"><i class="pe-7s-close-circle"></i> Close</button>-->
				<button type="button" class=" btn btn-labeled btn-danger m-b-5" data-dismiss="modal"><span
						class="btn-label">
						<i class="pe-7s-close-circle"></i></span> Cerrar</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->