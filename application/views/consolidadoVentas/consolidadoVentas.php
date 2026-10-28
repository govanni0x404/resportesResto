<?php
date_default_timezone_set('America/Santiago');
$date = new DateTime('now');
$date->modify('last day of this month');
//$fechaActual = date('d-m-Y');
$fechaActual = date('d-m-Y');
?>
<link href="<?php echo base_url() ?>assets/dist/css/reportes.css" rel="stylesheet" type="text/css" />
<div class="content-header rep-encabezado">
	<div class="header-icon">
		<i class="pe-7s-home"></i>
	</div>
	<div class="header-title">
		<h1>Consolidado Ventas</h1>
		<small id="caja"></small>
		<ol class="breadcrumb">
			<li><a href="<?php echo base_url() ?>index.php/Dashboard"><i class="pe-7s-home"></i>Reportes</a></li>
			<li class="active">Consolidado Ventas</li>
		</ol>
	</div>
</div> <!-- /.Content Header (Page header) -->

<div class="row">
	<div class="col-xs-12 col-sm-12 col-md-12 m-b-20">
		<!-- Nav tabs -->
		<ul class="nav nav-tabs rep-filtros rep-panel-filtros">
			<div class="col-md-4 col-sm-6">
				<div class="control-group">
					<div class="controls">
						<label> Seleccione la fecha </label>
						<div class="input-prepend input-group">
							<span class="add-on input-group-addon"><i class="fa fa-calendar"></i></span>
							<input type="text" id="date-range-picker" class="form-control" value="<?php echo $fechaActual; ?> - <?php echo $fechaActual; ?>" />
						</div>
					</div>
				</div>
			</div>

			<div class="col-md-2 col-sm-6">
				<button id="btnConsultar" onclick="consultarResumenDeVentas()" style="width:5px;" class="btn btn-success btn-rounded w-md m-b-1"><i class="hvr-buzz-out fa fa-search"></i> Consultar</button>
			</div>
			<div class="col-md-6 col-sm-12 rep-exportar">
				<button onclick="PDFConsolidadoDeVentas()" class="btn btn-danger w-md m-b-1"><i class="hvr-buzz-out fa fa-file-pdf-o"></i> EXPORTAR PDF</button>
				<button onclick="ExcelConsolidadoDeVentas()" class="btn btn-success w-md m-b-1"><i class="hvr-buzz-out fa fa-file-excel-o"></i> EXPORTAR EXCEL</button>
			</div>
		</ul>
		<div class="panel-body rep-cuerpo">
			<div class="row">
			</div>
			<!---------------------------------------------TABLAS RESUMEN STOCK POR BODEGA ------------------------------------------------------------>
			<div class="panel panel-success lobidisable">
				<div class="panel-heading">
					<div class="panel-title">
						<i class="pe-7s-cash"></i>
						<h4>Detalle Consolidado De Ventas</h4>
					</div>
				</div>

				<div class="tab-pane fade active in" id="DetalleConsolidadoVentas">
					<div class="panel-body">
						<div class="col-md-12 col-lg-12">
							<div class="panel panel-default rep-sin-marco">
								<div class="panel-body">
									<fieldset class="consolidado-stats rep-kpis">
										<div class="col-xs-3 col-sm-3 col-md-3 col-lg-4">
											<div class="statistic-box statistic-filled-6 btn-primary">
												<h2><span class="count-number1" id="cantidadVentas">0</span><span class="slight"><i class=" fa-rotate-90 c-white"> </i></span></h2>
												<div class="small">Cantidad Ventas</div>
												<i class="pe-7s-cash statistic_icon c-white"></i>
											</div>
										</div>

										<div class="col-xs-3 col-sm-3 col-md-3 col-lg-4">
											<div class="statistic-box statistic-filled-6 btn-primary ocultarTotalNeto">
												<h2>$<span class="count-number1" id="totalNeto">0</span><span class="slight"><i class=" fa-rotate-90 c-white"> </i></span></h2>
												<div class="small">Total Neto</div>
												<i class="pe-7s-cash statistic_icon c-white"></i>
											</div>
										</div>

										<div class="col-xs-3 col-sm-3 col-md-3 col-lg-4">
											<div class="statistic-box statistic-filled-6 btn-primary ocultarTotalIva">
												<h2>$<span class="count-number1" id="totalIva">0</span><span class="slight"><i class=" fa-rotate-90 c-white"> </i></span></h2>
												<div class="small">Total Iva</div>
												<i class="pe-7s-cash statistic_icon c-white"></i>
											</div>
										</div>

										<div class="col-xs-3 col-sm-3 col-md-3 col-lg-4">
											<div class="statistic-box statistic-filled-6 btn-primary">
												<h2>$<span class="count-number1" id="totalVentas">0</span><span class="slight"><i class=" fa-rotate-90 c-white"> </i></span></h2>
												<div class="small">Total Ventas</div>
												<i class="pe-7s-cash statistic_icon c-white"></i>
											</div>
										</div>

										<div class="col-xs-3 col-sm-3 col-md-3 col-lg-4">
											<div class="statistic-box statistic-filled-6 btn-primary">
												<h2>$ -<span class="count-number1" id="totalDescuentos">0</span><span class="slight"><i class=" fa-rotate-90 c-white"> </i></span></h2>
												<div class="small">Total Descuentos</div>
												<i class="pe-7s-cash statistic_icon c-white"></i>
											</div>
										</div>

										<div class="col-xs-4 col-sm-4 col-md-4 col-lg-4">
											<div class="statistic-box statistic-filled-6 btn-primary">
												<h2>$<span class="count-number1" id="TotalGeneral">0</span><span class="slight"><i class=" fa-rotate-90 c-white"> </i></span></h2>
												<div class="small">Total General</div>
												<i class="pe-7s-cash statistic_icon c-white"></i>
											</div>
										</div>

										<div class="col-xs-4 col-sm-4 col-md-4 col-lg-4 rep-kpi-celda-primera">
											<div class="statistic-box statistic-filled-6 btn-primary rep-kpi-destacado">
												<h2>$<span class="count-number1" id="MontoVentaTotal">0</span><span class="slight"><i class=" fa-rotate-90 c-white"> </i></span></h2>
												<div class="small">Monto Venta Total</div>
												<i class="pe-7s-cash statistic_icon c-white"></i>
											</div>
										</div>
									</fieldset>
								</div>

							</div>
						</div>

						<!--<div class="col-md-5 col-lg-5">
							<div class="panel panel-default">

								<div class="panel-title">
									Total De Ventas
								</div>
								<div class="panel-body">
									<fieldset>
										<div class="col-xs-4 col-sm-4 col-md-4 col-lg-4">
											<div class="statistic-box statistic-filled-6 btn-primary">
												<h2><span class="count-number1" id="TotalVentas">000000</span><span class="slight"><i class=" fa-rotate-90 c-white"> </i></span></h2>
												<div class="small">Total Ventas</div>
												<i class="pe-7s-cash statistic_icon c-white"></i>
											</div>
										</div>

										

										

									</fieldset>
								</div>

							</div>
						</div>-->

						<div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
							<div class="panel panel-success lobidisable">
								<div class="panel-heading">
									<div class="panel-title">
										<i class="pe-7s-file"></i>
										<h4>Ventas Por Familia</h4>
									</div>
								</div>
								<div class="table-responsive">
									<table class="table">
										<thead>
											<tr>
												<th class="text text-center">Familia</th>
												<th class="text text-center">Cantidad</th>
												<th class="text text-center ocultarNetoVentasPorFamilias">Neto</th>
												<th class="text text-center ocultarIvaVentasPorFamilias">Iva</th>
												<th class="text text-center">Total</th>
											</tr>
										</thead>
										<tbody id="resumenVentasPorFamilia">

										</tbody>
									</table>
								</div>
							</div>
						</div>

						<div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
							<div class="panel panel-success lobidisable">
								<div class="panel-heading">
									<div class="panel-title">
										<i class="pe-7s-file"></i>
										<h4>Ventas Por Canal</h4>
									</div>
								</div>
								<div class="table-responsive">
									<table class="table">
										<thead>
											<tr>
												<th class="text text-center">Canal De Venta</th>
												<th class="text text-center">Cantidad Ventas</th>
												<th class="text text-center ocultarNetoVentasPorCanal">Total Neto</th>
												<th class="text text-center ocultarIvaVentasPorCanal">Total Iva</th>
												<th class="text text-center">Total Ventas</th>
											</tr>
										</thead>
										<tbody id="resumenVentasPorCanal">

										</tbody>
									</table>
								</div>
							</div>
						</div>











					</div>
				</div>


			</div>

<style>
	/* Rejilla de indicadores: se adapta al ancho y oculta las celdas cuyo indicador está oculto por permisos.
	   Tarjetas, filtros y tablas: assets/dist/css/reportes.css */
	.consolidado-stats { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 14px; margin: 0 0 4px; padding: 0; border: 0; min-width: 0; }
	.consolidado-stats > [class*="col-"] { float: none; width: auto; padding: 0; min-height: 0; }
	.consolidado-stats > [class*="col-"]:has(> .statistic-box[style*="none"]) { display: none; }
	.consolidado-stats > .rep-kpi-celda-primera { order: -1; }
	.consolidado-stats .statistic-box { margin: 0; }
	#DetalleConsolidadoVentas > .panel-body { padding: 16px 18px 4px; }
	#DetalleConsolidadoVentas > .panel-body > [class*="col-"] { padding-left: 0; padding-right: 0; }
	#DetalleConsolidadoVentas > .panel-body > .col-sm-6 { padding: 0 0 0 8px; }
	#DetalleConsolidadoVentas > .panel-body > .col-sm-6:nth-of-type(2) { padding: 0 8px 0 0; }
	@media (max-width: 767px) {
		#DetalleConsolidadoVentas > .panel-body > .col-sm-6 { padding: 0; }
	}
	@media (max-width: 480px) {
		.consolidado-stats { grid-template-columns: 1fr; }
	}
</style>
